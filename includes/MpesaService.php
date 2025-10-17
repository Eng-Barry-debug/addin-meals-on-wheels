<?php
// includes/MpesaService.php - M-Pesa API integration for STK Push

require_once 'config.php';

class MpesaService {
    private $config;
    private $access_token;

    public function __construct() {
        global $current_mpesa;
        $this->config = $current_mpesa;

        // Check if M-Pesa is properly configured
        if (!isMpesaConfigured()) {
            throw new Exception('M-Pesa credentials are not properly configured. Please update your configuration.');
        }
    }

    /**
     * Get M-Pesa access token
     */
    private function getAccessToken() {
        if ($this->access_token) {
            return $this->access_token;
        }

        $url = $this->config['base_url'] . '/oauth/v1/generate?grant_type=client_credentials';

        $credentials = base64_encode($this->config['consumer_key'] . ':' . $this->config['consumer_secret']);

        $headers = [
            'Authorization: Basic ' . $credentials,
            'Content-Type: application/json'
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($http_code !== 200) {
            throw new Exception('Failed to get M-Pesa access token. HTTP Code: ' . $http_code);
        }

        $data = json_decode($response, true);

        if (!isset($data['access_token'])) {
            throw new Exception('Invalid response from M-Pesa API');
        }

        $this->access_token = $data['access_token'];
        return $this->access_token;
    }

    /**
     * Format phone number for M-Pesa API
     */
    private function formatPhoneNumber($phone_number) {
        // Remove all non-numeric characters
        $phone = preg_replace('/[^0-9]/', '', $phone_number);

        // Handle different phone number formats
        if (strlen($phone) === 10 && substr($phone, 0, 1) === '0') {
            // Convert 07XXXXXXXX to 2547XXXXXXXX
            $phone = '254' . substr($phone, 1);
        } elseif (strlen($phone) === 9) {
            // Add country code for numbers like 7XXXXXXXX
            $phone = '254' . $phone;
        } elseif (strlen($phone) === 12 && substr($phone, 0, 3) === '254') {
            // Already in correct format
            $phone = $phone;
        } else {
            // Invalid format, throw exception
            throw new Exception('Invalid phone number format: ' . $phone_number);
        }

        return $phone;
    }

    /**
     * Generate STK Push password
     */
    private function generatePassword($timestamp) {
        return base64_encode($this->config['shortcode'] . $this->config['passkey'] . $timestamp);
    }

    /**
     * Initiate STK Push
     */
    public function initiateSTKPush($phone_number, $amount, $account_reference, $transaction_desc) {
        try {
            // Log the request
            error_log("Initiating STK Push for phone: $phone_number, amount: $amount, ref: $account_reference");
            
            $access_token = $this->getAccessToken();
            $timestamp = date('YmdHis');
            $password = $this->generatePassword($timestamp);

            // Ensure the amount is a valid number
            $amount = (float) $amount;
            if ($amount <= 0) {
                throw new Exception('Invalid amount specified');
            }

            $url = $this->config['base_url'] . '/mpesa/stkpush/v1/processrequest';

            // Format phone number for M-Pesa API
            $phone = $this->formatPhoneNumber($phone_number);
            
            // Get the current domain
            $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://';
            $host = $_SERVER['HTTP_HOST'];
            $callback_url = $protocol . $host . '/mpesa_callback.php';
            
            error_log("Using callback URL: $callback_url");

            $request_data = [
                'BusinessShortCode' => $this->config['shortcode'],
                'Password' => $password,
                'Timestamp' => $timestamp,
                'TransactionType' => 'CustomerPayBillOnline',
                'Amount' => $amount,
                'PartyA' => $phone,
                'PartyB' => $this->config['shortcode'],
                'PhoneNumber' => $phone,
                'CallBackURL' => $callback_url,
                'AccountReference' => $account_reference,
                'TransactionDesc' => substr($transaction_desc, 0, 13) // Max 13 chars
            ];

            $headers = [
                'Authorization: Bearer ' . $access_token,
                'Content-Type: application/json'
            ];

            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL => $url,
                CURLOPT_HTTPHEADER => $headers,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => json_encode($request_data),
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_SSL_VERIFYPEER => true,
                CURLOPT_SSL_VERIFYHOST => 2,
                CURLOPT_CAINFO => __DIR__ . '/cacert.pem', // Path to your CA certificate
                CURLOPT_TIMEOUT => 30,
                CURLOPT_CONNECTTIMEOUT => 10
            ]);

            $response = curl_exec($ch);
            $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curl_error = curl_error($ch);
            curl_close($ch);

            error_log("M-Pesa API Response - Status: $http_code, Response: $response");

            if ($curl_error) {
                throw new Exception('Curl error: ' . $curl_error);
            }

            if ($http_code !== 200) {
                throw new Exception('STK Push request failed. HTTP Code: ' . $http_code . '. Response: ' . $response);
            }

            $response_data = json_decode($response, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new Exception('Invalid JSON response from M-Pesa API');
            }

            if (!isset($response_data['ResponseCode']) || $response_data['ResponseCode'] !== '0') {
                $error_message = $response_data['errorMessage'] ?? ($response_data['errorMessage'] ?? 'STK Push request failed');
                throw new Exception($error_message);
            }

            if (!isset($response_data['CheckoutRequestID'])) {
                throw new Exception('Invalid response from M-Pesa API: Missing CheckoutRequestID');
            }

            return [
                'success' => true,
                'message' => 'STK Push sent successfully',
                'checkout_request_id' => $response_data['CheckoutRequestID'],
                'response_code' => $response_data['ResponseCode'],
                'response_description' => $response_data['ResponseDescription'] ?? '',
                'merchant_request_id' => $response_data['MerchantRequestID'] ?? '',
                'customer_message' => $response_data['CustomerMessage'] ?? ''
            ];

        } catch (Exception $e) {
            error_log('STK Push Error: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to initiate payment: ' . $e->getMessage(),
                'error_code' => $e->getCode()
            ];
        }
    }

    /**
     * Query STK Push status
     */
    public function querySTKPush($checkout_request_id) {
        try {
            $access_token = $this->getAccessToken();
            $timestamp = date('YmdHis');
            $password = $this->generatePassword($timestamp);

            $url = $this->config['base_url'] . '/mpesa/stkpushquery/v1/query';

            $request_data = [
                'BusinessShortCode' => $this->config['shortcode'],
                'Password' => $password,
                'Timestamp' => $timestamp,
                'CheckoutRequestID' => $checkout_request_id
            ];

            $headers = [
                'Authorization: Bearer ' . $access_token,
                'Content-Type: application/json'
            ];

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($request_data));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

            $response = curl_exec($ch);
            $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($http_code !== 200) {
                throw new Exception('STK Push query failed. HTTP Code: ' . $http_code);
            }

            return json_decode($response, true);

        } catch (Exception $e) {
            return [
                'error' => $e->getMessage()
            ];
        }
    }
}
?>
