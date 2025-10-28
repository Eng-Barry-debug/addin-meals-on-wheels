<?php

// Include PHPMailer autoloader
require_once __DIR__ . '/../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

/**
 * Service class for sending various types of order-related emails (Invoices, Receipts).
 * Uses PHPMailer for reliable email delivery with SMTP support.
 */
class EmailService {
    private $smtp_host;
    private $smtp_port;
    private $smtp_username;
    private $smtp_password;
    private $from_email;
    private $from_name;

    public function __construct() {
        // Load SMTP settings from config.php
        global $smtp_config;
        $this->smtp_host = $smtp_config['host'] ?? 'smtp.gmail.com';
        $this->smtp_port = $smtp_config['port'] ?? 587;
        $this->smtp_username = $smtp_config['username'] ?? 'barrackbarry2023@gmail.com';
        $this->smtp_password = $smtp_config['password'] ?? 'qwhbkksamjgzsfuw';
        $this->from_email = $smtp_config['from_email'] ?? 'addinsmeals@gmail.com';
        $this->from_name = $smtp_config['from_name'] ?? 'Addins Meals on Wheels';
    }

    /**
     * Sends the final email using PHPMailer for reliable delivery.
     * @param string $to Recipient email address.
     * @param string $subject Email subject line.
     * @param string $message HTML content of the email.
     * @return bool True on success, false on failure.
     */
    public function sendEmail($to, $subject, $message, $attachments = []) {
        try {
            $mail = new PHPMailer(true);

            // Server settings
            $mail->isSMTP();
            $mail->Host = $this->smtp_host;
            $mail->SMTPAuth = true;
            $mail->Username = $this->smtp_username;
            $mail->Password = $this->smtp_password;
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = $this->smtp_port;

            // Recipients
            $mail->setFrom($this->from_email, $this->from_name);
            $mail->addAddress($to);

            // Content
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body = $message;
            $mail->AltBody = strip_tags($message); // Plain text version

            // Send
            $mail->send();
            error_log("Email sent successfully to: $to via PHPMailer");
            return true;

        } catch (Exception $e) {
            error_log("PHPMailer Error: {$mail->ErrorInfo}");
            error_log("Failed to send email to: $to - " . $e->getMessage());
            return false;
        }
    }

    /**
     * Sends the invoice email to the customer.
     * @param string $to Customer email.
     * @param array $orderData Order details.
     * @return bool
     */
    public function sendInvoiceEmail($to, $orderData) {
        // Recalculate totals to ensure accuracy
        $orderData = $this->recalculateOrderTotals($orderData);

        $subject = "Invoice for Order #{$orderData['order_number']} - Addins Meals on Wheels";
        $message = $this->generateInvoiceEmailContent($orderData);
        return $this->sendEmail($to, $subject, $message);
    }

    /**
     * Sends the receipt email to the customer.
     * @param string $to Customer email.
     * @param array $orderData Order details.
     * @return bool
     */
    public function sendReceiptEmail($to, $orderData) {
        // Recalculate totals to ensure accuracy
        $orderData = $this->recalculateOrderTotals($orderData);

        $subject = "Receipt for Order #{$orderData['order_number']} - Addins Meals on Wheels";
        $message = $this->generateReceiptEmailContent($orderData);
        return $this->sendEmail($to, $subject, $message);
    }

    /**
     * Recalculates order totals to ensure accuracy
     * @param array $orderData Order data
     * @return array Updated order data
     */
    private function recalculateOrderTotals($orderData) {
        // Recalculate item totals
        if (!empty($orderData['item_totals'])) {
            $itemTotalsString = $orderData['item_totals'];
            $totalItems = array_map('floatval', explode(', ', $itemTotalsString));
            $orderData['subtotal'] = array_sum($totalItems);
        } else {
            $orderData['subtotal'] = 0;
        }

        // Ensure delivery fee is numeric
        $orderData['delivery_fee'] = floatval($orderData['delivery_fee'] ?? 0);

        // Recalculate total amount
        $orderData['total_amount'] = $orderData['subtotal'] + $orderData['delivery_fee'];

        return $orderData;
    }

    /**
     * Generates the HTML for the item rows in the Invoice table.
     * NOTE: This function was moved inside the class to resolve the Call to undefined method error.
     * @param array $order Order data array including grouped item strings.
     * @return string HTML table rows.
     */
    private function generateOrderItemsHTML($order) {
        if (empty($order['item_names'])) {
            return '<tr><td colspan="4">No items found</td></tr>';
        }

        // It is critical that all these arrays have the same number of elements
        $item_names = explode(', ', $order['item_names']);
        $item_quantities = explode(', ', $order['item_quantities']);
        $item_prices = explode(', ', $order['item_prices']);
        $item_totals = explode(', ', $order['item_totals']);

        $html = '';
        $count = count($item_names);
        // Only iterate up to the minimum count to prevent index errors
        for ($i = 0; $i < $count; $i++) {
            $html .= "
            <tr>
                <td>" . htmlspecialchars($item_names[$i] ?? '') . "</td>
                <td style='text-align: center;'>" . ($item_quantities[$i] ?? 0) . "</td>
                <td style='text-align: right;'>KES " . number_format($item_prices[$i] ?? 0, 2) . "</td>
                <td style='text-align: right;'>KES " . number_format($item_totals[$i] ?? 0, 2) . "</td>
            </tr>";
        }
        return $html;
    }

    /**
     * Generates the HTML content for the Invoice email.
     * @param array $order Order data.
     * @return string Complete HTML document.
     */
    private function generateInvoiceEmailContent($order) {
        $invoiceDate = date('M j, Y \a\t g:i A', strtotime($order['created_at']));
        $orderStatus = ucfirst($order['status'] ?? 'N/A');
        $paymentMethod = ucfirst(str_replace('_', ' ', $order['payment_method'] ?? 'N/A'));
        $deliveryFeeFormatted = (($order['delivery_fee'] ?? 0) === 0 ? 'FREE' : 'KES ' . number_format($order['delivery_fee'] ?? 0, 2));
        $totalAmountFormatted = number_format($order['total_amount'] ?? 0, 2);
        $subtotalFormatted = number_format($order['subtotal'] ?? 0, 2);

        return "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='utf-8'>
            <title>Invoice #{$order['order_number']}</title>
            <style>
                * { margin: 0; padding: 0; box-sizing: border-box; }
                body {
                    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
                    line-height: 1.6;
                    color: #333;
                    background-color: #fff;
                    font-size: 14px;
                }

                .invoice-container {
                    max-width: 800px;
                    margin: 0 auto;
                    padding: 40px;
                    background: #fff;
                    box-shadow: 0 0 20px rgba(0,0,0,0.1);
                }

                .invoice-header {
                    text-align: center;
                    margin-bottom: 40px;
                    padding-bottom: 30px;
                    border-bottom: 3px solid #2c3e50;
                    background: linear-gradient(135deg, #34495e 0%, #2c3e50 100%);
                    color: white;
                    border-radius: 10px 10px 0 0;
                    padding: 30px;
                }

                .invoice-title {
                    font-size: 36px;
                    font-weight: 300;
                    margin-bottom: 10px;
                    letter-spacing: 2px;
                    text-transform: uppercase;
                }

                .invoice-subtitle {
                    font-size: 18px;
                    opacity: 0.9;
                    font-weight: 300;
                }

                .company-section {
                    text-align: center;
                    margin-bottom: 40px;
                    padding: 20px;
                }

                .company-info h2 {
                    font-size: 24px;
                    margin-bottom: 8px;
                    color: #2c3e50;
                    font-weight: 600;
                }

                .company-info .tagline {
                    font-size: 16px;
                    color: #7f8c8d;
                    margin-bottom: 5px;
                    font-style: italic;
                }

                .company-info .contact {
                    font-size: 14px;
                    color: #95a5a6;
                }

                .invoice-details {
                    display: grid;
                    grid-template-columns: 1fr 1fr;
                    gap: 40px;
                    margin-bottom: 40px;
                }

                .detail-section {
                    background: #f8f9fa;
                    padding: 25px;
                    border-radius: 8px;
                    border-left: 4px solid #3498db;
                }

                .detail-title {
                    font-size: 16px;
                    font-weight: 600;
                    color: #2c3e50;
                    margin-bottom: 15px;
                    text-transform: uppercase;
                    letter-spacing: 1px;
                }

                .detail-item {
                    margin-bottom: 8px;
                    display: flex;
                    justify-content: space-between;
                }

                .detail-label {
                    font-weight: 500;
                    color: #7f8c8d;
                }

                .detail-value {
                    font-weight: 600;
                    color: #2c3e50;
                }

                .delivery-info {
                    background: linear-gradient(135deg, #e8f4f8 0%, #d1ecf1 100%);
                    padding: 20px;
                    border-radius: 8px;
                    margin-bottom: 30px;
                    border-left: 4px solid #16a085;
                }

                .delivery-title {
                    font-size: 16px;
                    font-weight: 600;
                    color: #2c3e50;
                    margin-bottom: 10px;
                }

                .delivery-address {
                    font-size: 15px;
                    color: #34495e;
                    line-height: 1.5;
                }

                .items-section {
                    margin-bottom: 30px;
                }

                .items-title {
                    font-size: 20px;
                    font-weight: 600;
                    color: #2c3e50;
                    margin-bottom: 20px;
                    padding-bottom: 10px;
                    border-bottom: 2px solid #ecf0f1;
                }

                table {
                    width: 100%;
                    border-collapse: collapse;
                    background: white;
                    border-radius: 8px;
                    overflow: hidden;
                    box-shadow: 0 4px 6px rgba(0,0,0,0.1);
                }

                th {
                    background: linear-gradient(135deg, #34495e 0%, #2c3e50 100%);
                    color: white;
                    padding: 18px 15px;
                    text-align: left;
                    font-weight: 600;
                    font-size: 14px;
                    text-transform: uppercase;
                    letter-spacing: 0.5px;
                }

                td {
                    padding: 15px;
                    border-bottom: 1px solid #ecf0f1;
                    font-size: 14px;
                }

                tbody tr:nth-child(even) {
                    background-color: #f8f9fa;
                }

                .text-right { text-align: right; }
                .text-center { text-align: center; }

                .totals-section {
                    display: flex;
                    justify-content: flex-end;
                    margin-bottom: 40px;
                }

                .totals-table {
                    background: #f8f9fa;
                    border-radius: 8px;
                    overflow: hidden;
                    min-width: 300px;
                    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
                }

                .totals-table tr {
                    border-bottom: 1px solid #dee2e6;
                }

                .totals-table td {
                    padding: 12px 20px;
                    font-size: 14px;
                }

                .total-row {
                    background: linear-gradient(135deg, #e74c3c 0%, #c0392b 100%);
                    color: white;
                    font-weight: 600;
                }

                .total-row td {
                    font-size: 16px;
                    padding: 15px 20px;
                }

                .footer {
                    text-align: center;
                    margin-top: 50px;
                    padding-top: 30px;
                    border-top: 2px solid #ecf0f1;
                    background: #f8f9fa;
                    border-radius: 0 0 10px 10px;
                    padding: 30px;
                }

                .footer-text {
                    font-size: 16px;
                    color: #2c3e50;
                    margin-bottom: 10px;
                    font-weight: 500;
                }

                .footer-subtext {
                    font-size: 14px;
                    color: #7f8c8d;
                }

                .thank-you {
                    font-size: 18px;
                    color: #27ae60;
                    font-weight: 600;
                    margin-bottom: 20px;
                }

                @media (max-width: 768px) {
                    .invoice-container { padding: 20px; }
                    .invoice-details { grid-template-columns: 1fr; gap: 20px; }
                    .company-section { flex-direction: column; text-align: center; }
                }
            </style>
        </head>
        <body>
            <div class='invoice-container'>
                <div class='invoice-header'>
                    <h1 class='invoice-title'>Invoice</h1>
                    <p class='invoice-subtitle'>Order #{$order['order_number']}</p>
                </div>

                <div class='company-section'>
                    <div class='company-info'>
                        <div style='font-size: 28px; color: #2c3e50; font-weight: 700; margin-bottom: 5px;'>
                            🍽️ Addins Meals
                        </div>
                        <p class='tagline'>Delicious Food Delivery Service</p>
                        <p class='contact'>📞 +254 112 855 900 | 📧 info@addinsmeals.com</p>
                    </div>
                </div>

                <div class='invoice-details'>
                    <div class='detail-section'>
                        <div class='detail-title'>Bill To</div>
                        <div class='detail-item'>
                            <span class='detail-label'>Customer:</span>
                            <span class='detail-value'>" . htmlspecialchars($order['customer_name'] ?? 'N/A') . "</span>
                        </div>
                        <div class='detail-item'>
                            <span class='detail-label'>Email:</span>
                            <span class='detail-value'>" . htmlspecialchars($order['customer_email'] ?? 'N/A') . "</span>
                        </div>
                        <div class='detail-item'>
                            <span class='detail-label'>Phone:</span>
                            <span class='detail-value'>" . htmlspecialchars($order['customer_phone'] ?? 'N/A') . "</span>
                        </div>
                    </div>

                    <div class='detail-section'>
                        <div class='detail-title'>Order Details</div>
                        <div class='detail-item'>
                            <span class='detail-label'>Order Number:</span>
                            <span class='detail-value'>#{$order['order_number']}</span>
                        </div>
                        <div class='detail-item'>
                            <span class='detail-label'>Order Date:</span>
                            <span class='detail-value'>{$invoiceDate}</span>
                        </div>
                        <div class='detail-item'>
                            <span class='detail-label'>Status:</span>
                            <span class='detail-value'>{$orderStatus}</span>
                        </div>
                        <div class='detail-item'>
                            <span class='detail-label'>Payment Method:</span>
                            <span class='detail-value'>{$paymentMethod}</span>
                        </div>
                    </div>
                </div>

                <div class='delivery-info'>
                    <div class='delivery-title'>Delivery Information</div>
                    <div class='delivery-address'>" . htmlspecialchars($order['delivery_address'] ?? 'N/A') . "</div>
                </div>

                <div class='items-section'>
                    <div class='items-title'>Order Items</div>
                    <table>
                        <thead>
                            <tr>
                                <th>Item</th>
                                <th class='text-center'>Qty</th>
                                <th class='text-right'>Unit Price</th>
                                <th class='text-right'>Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            " . $this->generateOrderItemsHTML($order) . "
                        </tbody>
                    </table>
                </div>

                <div class='totals-section'>
                    <table class='totals-table'>
                        <tr>
                            <td>Subtotal:</td>
                            <td class='text-right'>KES {$subtotalFormatted}</td>
                        </tr>
                        <tr>
                            <td>Delivery Fee:</td>
                            <td class='text-right'>{$deliveryFeeFormatted}</td>
                        </tr>
                        <tr class='total-row'>
                            <td><strong>Total Amount:</strong></td>
                            <td class='text-right'><strong>KES {$totalAmountFormatted}</strong></td>
                        </tr>
                    </table>
                </div>

                <div class='footer'>
                    <div class='thank-you'>Thank you for choosing Addins Meals on Wheels!</div>
                    <p class='footer-text'>We appreciate your business and look forward to serving you again.</p>
                    <p class='footer-subtext'>This invoice was generated on " . date('M j, Y \a\t g:i A') . " | For inquiries, please contact us at +254 112 855 900</p>
                </div>
            </div>
        </body>
        </html>";
    }

    /**
     * Generates the HTML for the item details in the Receipt.
     * NOTE: This function was moved inside the class to resolve the Call to undefined method error.
     * @param array $order Order data array including grouped item strings.
     * @return string HTML item list.
     */
    private function generateReceiptItemsHTML($order) {
        if (empty($order['item_names'])) {
            return '<p>No items found</p>';
        }

        $item_names = explode(', ', $order['item_names']);
        $item_quantities = explode(', ', $order['item_quantities']);
        $item_prices = explode(', ', $order['item_prices'] ?? []);
        $item_totals = explode(', ', $order['item_totals'] ?? []);

        $html = '';
        $count = count($item_names);
        for ($i = 0; $i < $count; $i++) {
            // Use safe access to array elements with null coalescing operator and htmlspecialchars
            $name = htmlspecialchars($item_names[$i] ?? 'N/A');
            $qty = $item_quantities[$i] ?? '0';
            $unitPrice = $item_prices[$i] ?? 0;
            $total = $item_totals[$i] ?? 0;

            $html .= "<div class='item'><span class='item-name'>{$name} x{$qty}</span> <span class='item-total'>KES " . number_format($total, 2) . "</span></div>";
        }
        return $html;
    }

    /**
     * Generates the HTML content for the Receipt email.
     * @param array $order Order data.
     * @return string Complete HTML document.
     */
    private function generateReceiptEmailContent($order) {
        $receiptDate = date('M j, Y \a\t g:i A', strtotime($order['created_at']));
        $totalPaidFormatted = number_format($order['total_amount'] ?? 0, 2);
        $paymentMethod = ucfirst(str_replace('_', ' ', $order['payment_method'] ?? 'N/A'));

        return "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='utf-8'>
            <title>Receipt #{$order['order_number']}</title>
            <style>
                * { margin: 0; padding: 0; box-sizing: border-box; }
                body {
                    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
                    line-height: 1.6;
                    color: #333;
                    background-color: #f8f9fa;
                    font-size: 14px;
                }

                .receipt-container {
                    max-width: 600px;
                    margin: 0 auto;
                    background: #fff;
                    box-shadow: 0 4px 6px rgba(0,0,0,0.1);
                    border-radius: 10px;
                    overflow: hidden;
                }

                .receipt-header {
                    text-align: center;
                    background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
                    color: white;
                    padding: 30px;
                    border-radius: 10px 10px 0 0;
                }

                .receipt-title {
                    font-size: 32px;
                    font-weight: 300;
                    margin-bottom: 10px;
                    letter-spacing: 2px;
                    text-transform: uppercase;
                }

                .receipt-subtitle {
                    font-size: 16px;
                    opacity: 0.9;
                    font-weight: 300;
                }

                .company-section {
                    text-align: center;
                    padding: 20px;
                    border-bottom: 2px solid #e9ecef;
                }

                .company-info h2 {
                    font-size: 20px;
                    margin-bottom: 8px;
                    color: #2c3e50;
                    font-weight: 600;
                }

                .company-info .tagline {
                    font-size: 14px;
                    color: #7f8c8d;
                    font-style: italic;
                }

                .company-info .contact {
                    font-size: 12px;
                    color: #95a5a6;
                    margin-top: 8px;
                }

                .receipt-details {
                    padding: 25px;
                    background: #f8f9fa;
                    border-bottom: 1px solid #e9ecef;
                }

                .detail-section {
                    margin-bottom: 20px;
                }

                .detail-title {
                    font-size: 14px;
                    font-weight: 600;
                    color: #2c3e50;
                    margin-bottom: 10px;
                    text-transform: uppercase;
                    letter-spacing: 1px;
                }

                .detail-item {
                    margin-bottom: 6px;
                    display: flex;
                    justify-content: space-between;
                }

                .detail-label {
                    font-weight: 500;
                    color: #7f8c8d;
                }

                .detail-value {
                    font-weight: 600;
                    color: #2c3e50;
                }

                .items-section {
                    padding: 25px;
                    background: white;
                }

                .items-title {
                    font-size: 18px;
                    font-weight: 600;
                    color: #2c3e50;
                    margin-bottom: 20px;
                    padding-bottom: 10px;
                    border-bottom: 2px solid #e9ecef;
                }

                .item {
                    display: flex;
                    justify-content: space-between;
                    padding: 12px 0;
                    border-bottom: 1px solid #f1f3f4;
                    font-size: 14px;
                }

                .item:last-child {
                    border-bottom: none;
                }

                .item-name {
                    flex: 1;
                    font-weight: 500;
                    color: #2c3e50;
                }

                .item-total {
                    font-weight: 600;
                    color: #28a745;
                }

                .payment-info {
                    background: linear-gradient(135deg, #e8f5e8 0%, #d4edda 100%);
                    padding: 20px 25px;
                    border-top: 1px solid #e9ecef;
                }

                .payment-title {
                    font-size: 14px;
                    font-weight: 600;
                    color: #2c3e50;
                    margin-bottom: 10px;
                }

                .payment-item {
                    margin-bottom: 6px;
                    display: flex;
                    justify-content: space-between;
                }

                .payment-label {
                    font-weight: 500;
                    color: #28a745;
                }

                .payment-value {
                    font-weight: 600;
                    color: #2c3e50;
                }

                .total-section {
                    background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
                    color: white;
                    padding: 20px 25px;
                    text-align: center;
                }

                .total-title {
                    font-size: 16px;
                    font-weight: 500;
                    margin-bottom: 8px;
                }

                .total-amount {
                    font-size: 28px;
                    font-weight: 700;
                    letter-spacing: 1px;
                }

                .footer {
                    text-align: center;
                    padding: 25px;
                    background: #f8f9fa;
                    border-top: 1px solid #e9ecef;
                }

                .footer-text {
                    font-size: 16px;
                    color: #28a745;
                    margin-bottom: 10px;
                    font-weight: 600;
                }

                .footer-subtext {
                    font-size: 12px;
                    color: #7f8c8d;
                }

                .thank-you {
                    font-size: 14px;
                    color: #2c3e50;
                    font-weight: 500;
                }

                @media (max-width: 768px) {
                    .receipt-container {
                        margin: 10px;
                        border-radius: 5px;
                    }
                    .receipt-header {
                        padding: 20px;
                    }
                    .receipt-title {
                        font-size: 24px;
                    }
                }
            </style>
        </head>
        <body>
            <div class='receipt-container'>
                <div class='receipt-header'>
                    <h1 class='receipt-title'>Payment Receipt</h1>
                    <p class='receipt-subtitle'>Order #{$order['order_number']}</p>
                </div>

                <div class='company-section'>
                    <div class='company-info'>
                        <h2>Addins Meals on Wheels</h2>
                        <p class='tagline'>Thank you for your payment!</p>
                        <p class='contact'>📞 +254 112 855 900 | 📧 info@addinsmeals.com</p>
                    </div>
                </div>

                <div class='receipt-details'>
                    <div class='detail-section'>
                        <div class='detail-title'>Customer Information</div>
                        <div class='detail-item'>
                            <span class='detail-label'>Name:</span>
                            <span class='detail-value'>" . htmlspecialchars($order['customer_name'] ?? 'N/A') . "</span>
                        </div>
                        <div class='detail-item'>
                            <span class='detail-label'>Email:</span>
                            <span class='detail-value'>" . htmlspecialchars($order['customer_email'] ?? 'N/A') . "</span>
                        </div>
                        <div class='detail-item'>
                            <span class='detail-label'>Phone:</span>
                            <span class='detail-value'>" . htmlspecialchars($order['customer_phone'] ?? 'N/A') . "</span>
                        </div>
                        <div class='detail-item'>
                            <span class='detail-label'>Order Date:</span>
                            <span class='detail-value'>{$receiptDate}</span>
                        </div>
                    </div>
                </div>

                <div class='items-section'>
                    <div class='items-title'>Order Items</div>
                    " . $this->generateReceiptItemsHTML($order) . "
                </div>

                <div class='payment-info'>
                    <div class='payment-title'>Payment Information</div>
                    <div class='payment-item'>
                        <span class='payment-label'>Payment Method:</span>
                        <span class='payment-value'>{$paymentMethod}</span>
                    </div>
                    " . (!empty($order['payment_status']) ? "<div class='payment-item'>
                        <span class='payment-label'>Payment Status:</span>
                        <span class='payment-value'>" . ucfirst($order['payment_status']) . "</span>
                    </div>" : "") . "
                    " . (!empty($order['payment_reference']) ? "<div class='payment-item'>
                        <span class='payment-label'>Payment Reference:</span>
                        <span class='payment-value'>" . htmlspecialchars($order['payment_reference']) . "</span>
                    </div>" : "") . "
                </div>

                <div class='total-section'>
                    <div class='total-title'>Total Paid</div>
                    <div class='total-amount'>KES {$totalPaidFormatted}</div>
                </div>

                <div class='footer'>
                    <div class='footer-text'>Thank you for your payment!</div>
                    <p class='footer-subtext'>Addins Meals on Wheels</p>
                    <p class='thank-you'>Order #{$order['order_number']} | Generated: " . date('M j, Y \a\t g:i A') . "</p>
                </div>
            </div>
        </body>
        </html>";
    }
}
?>
