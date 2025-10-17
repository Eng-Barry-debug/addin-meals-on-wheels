<?php
// includes/AirtelService.php - Airtel Money API integration for STK Push

require_once 'config.php';

class AirtelService {
    private $config;
    private $access_token;

    public function __construct() {
        global $current_airtel;
        $this->config = $current_airtel;

        // Check if Airtel Money is properly configured
        if (!isAirtelConfigured()) {
            throw new Exception('Airtel Money credentials are not properly configured. Please update your configuration.');
        }
    }

    /**
     * Get Airtel Money access token
     */
    private function getAccessToken() {
        if ($this->access_token) {
            return $this->access_token;
        }

        $url = $this->config['base_url'] . '/auth/oauth2/token';

        $credentials = base64_encode($this->config['consumer_key'] . ':' . $this->config['consumer_secret']);

        $headers = [
            'Authorization: Basic ' . $credentials,
            'Content-Type: application/x-www-form-urlencoded'
        ];

        $data = http_build_query([
            'grant_type' => 'client_credentials'
        ]);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($http_code !== 200) {
            throw new Exception('Failed to get Airtel Money access token. HTTP Code: ' . $http_code);
        }

        $data = json_decode($response, true);

        if (!isset($data['access_token'])) {
            throw new Exception('Invalid response from Airtel Money API');
        }

        $this->access_token = $data['access_token'];
        return $this->access_token;
    }

    /**
     * Format phone number for Airtel Money API
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
     * Generate STK Push password for Airtel Money
     */
    private function generatePassword($timestamp) {
        return base64_encode($this->config['shortcode'] . $this->config['passkey'] . $timestamp);
    }

    /**
     * Initiate Airtel Money STK Push
     */
    public function initiateSTKPush($phone_number, $amount, $account_reference, $transaction_desc) {
        try {
            $access_token = $this->getAccessToken();
            $timestamp = date('YmdHis');
            $password = $this->generatePassword($timestamp);

            $url = $this->config['base_url'] . '/merchant/v1/payments/initiate';

            // Format phone number for Airtel Money API
            $phone = $this->formatPhoneNumber($phone_number);

            $request_data = [
                'subscriber_msisdn' => $phone,
                'amount' => $amount,
                'currency' => 'KES',
                'shortcode' => $this->config['shortcode'],
                'command_id' => 'CustomerPayBillOnline',
                'account_number' => $account_reference,
                'transaction_desc' => $transaction_desc
            ];

            $headers = [
                'Authorization: Bearer ' . $access_token,
                'Content-Type: application/json',
                'X-Country: KE',
                'X-Currency: KES'
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
                throw new Exception('Airtel Money STK Push request failed. HTTP Code: ' . $http_code . '. Response: ' . $response);
            }

            $response_data = json_decode($response, true);

            if (!isset($response_data['status']['code']) || $response_data['status']['code'] !== 'DP009000') {
                $error_message = $response_data['status']['message'] ?? 'Airtel Money STK Push request failed';
                throw new Exception($error_message);
            }

            return [
                'success' => true,
                'message' => 'Airtel Money STK Push sent successfully',
                'transaction_id' => $response_data['data']['transaction_id'] ?? null,
                'response_code' => $response_data['status']['code'],
                'response_description' => $response_data['status']['message']
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Query Airtel Money STK Push status
     */
    public function querySTKPush($transaction_id) {
        try {
            $access_token = $this->getAccessToken();

            $url = $this->config['base_url'] . '/merchant/v1/payments/' . $transaction_id;

            $headers = [
                'Authorization: Bearer ' . $access_token,
                'Content-Type: application/json',
                'X-Country: KE',
                'X-Currency: KES'
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
                throw new Exception('Airtel Money STK Push query failed. HTTP Code: ' . $http_code);
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
