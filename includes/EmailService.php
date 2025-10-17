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
        // Configure SMTP settings - these should be moved to config or environment variables in production
        $this->smtp_host = 'smtp.gmail.com'; // Change to your SMTP server
        $this->smtp_port = 587;
        $this->smtp_username = 'your-email@gmail.com'; // Replace with actual email
        $this->smtp_password = 'your-app-password'; // Replace with actual password
        $this->from_email = 'noreply@addinsmeals.com';
        $this->from_name = 'Addins Meals on Wheels';
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
        $subject = "Receipt for Order #{$orderData['order_number']} - Addins Meals on Wheels";
        $message = $this->generateReceiptEmailContent($orderData);
        return $this->sendEmail($to, $subject, $message);
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
        // We use the simpler method of interpolating the main $order array,
        // rather than mapping to a new array as done in the unused global function.

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
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .header { background: #dc2626; color: white; padding: 20px; text-align: center; }
                .content { padding: 20px; max-width: 600px; margin: 0 auto; }
                .customer-info, .order-info { background: #f8f9fa; padding: 15px; margin: 20px 0; border-radius: 5px; }
                table { width: 100%; border-collapse: collapse; margin: 20px 0; }
                th, td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
                th { background-color: #e9ecef; }
                .total { font-weight: bold; font-size: 18px; color: #dc2626; }
                .footer { text-align: center; margin-top: 30px; padding-top: 20px; border-top: 1px solid #ddd; color: #666; }
            </style>
        </head>
        <body>
            <div class='header'>
                <h1>INVOICE</h1>
                <p>Order #{$order['order_number']}</p>
            </div>

            <div class='content'>
                <div class='customer-info'>
                    <h3>Bill To:</h3>
                    <p><strong>" . htmlspecialchars($order['customer_name'] ?? 'N/A') . "</strong></p>
                    <p>" . htmlspecialchars($order['customer_email'] ?? 'N/A') . "</p>
                    " . (!empty($order['customer_phone']) ? "<p>" . htmlspecialchars($order['customer_phone']) . "</p>" : "") . "
                </div>

                <div class='order-info'>
                    <h3>Order Details:</h3>
                    <p><strong>Date:</strong> {$invoiceDate}</p>
                    <p><strong>Status:</strong> {$orderStatus}</p>
                    <p><strong>Payment Method:</strong> {$paymentMethod}</p>
                    " . (!empty($order['delivery_address']) ? "<p><strong>Delivery Address:</strong> " . htmlspecialchars($order['delivery_address']) . "</p>" : "") . "
                </div>

                <table>
                    <thead>
                        <tr>
                            <th>Item</th>
                            <th style='text-align: center;'>Qty</th>
                            <th style='text-align: right;'>Unit Price</th>
                            <th style='text-align: right;'>Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        " . $this->generateOrderItemsHTML($order) . "
                    </tbody>
                </table>

                <table style='margin-left: auto; width: 300px;'>
                    <tr>
                        <td>Subtotal:</td>
                        <td style='text-align: right;'>KES {$subtotalFormatted}</td>
                    </tr>
                    <tr>
                        <td>Delivery Fee:</td>
                        <td style='text-align: right;'>{$deliveryFeeFormatted}</td>
                    </tr>
                    <tr>
                        <td><strong>TOTAL:</strong></td>
                        <td style='text-align: right;' class='total'>KES {$totalAmountFormatted}</td>
                    </tr>
                </table>

                <div class='footer'>
                    <p>Thank you for choosing Addins Meals on Wheels!</p>
                    <p>This is an official invoice for your recent order.</p>
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

            $html .= "<div class='item'><span>{$name} x{$qty}</span> <span>KES " . number_format($total, 2) . "</span></div>";
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
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; }
                .header { background: #28a745; color: white; padding: 20px; text-align: center; }
                .content { padding: 20px; }
                .customer-info { background: #f8f9fa; padding: 15px; margin: 20px 0; border-radius: 5px; }
                .payment-info { margin: 20px 0; padding: 15px; background-color: #e9ecef; border-radius: 5px; }
                .items { margin: 20px 0; }
                .item { display: flex; justify-content: space-between; padding: 10px 0; border-bottom: 1px solid #eee; }
                .total { font-weight: bold; font-size: 18px; color: #28a745; text-align: center; margin-top: 20px; }
                .footer { text-align: center; margin-top: 30px; padding-top: 20px; border-top: 1px solid #ddd; color: #666; }
            </style>
        </head>
        <body>
            <div class='header'>
                <h1>PAYMENT RECEIPT</h1>
                <p>Order #{$order['order_number']}</p>
            </div>

            <div class='content'>
                <div class='customer-info'>
                    <h3>Customer Information:</h3>
                    <p><strong>Name:</strong> " . htmlspecialchars($order['customer_name'] ?? 'N/A') . "</p>
                    <p><strong>Email:</strong> " . htmlspecialchars($order['customer_email'] ?? 'N/A') . "</p>
                    <p><strong>Phone:</strong> " . htmlspecialchars($order['customer_phone'] ?? 'N/A') . "</p>
                    <p><strong>Order Date:</strong> {$receiptDate}</p>
                </div>

                <div class='items'>
                    <h3>Order Items:</h3>
                    " . $this->generateReceiptItemsHTML($order) . "
                </div>

                <div class='payment-info'>
                    <h3>Payment Information:</h3>
                    <p><strong>Payment Method:</strong> {$paymentMethod}</p>
                    " . (!empty($order['payment_status']) ? "<p><strong>Payment Status:</strong> " . ucfirst($order['payment_status']) . "</p>" : "") . "
                    " . (!empty($order['payment_reference']) ? "<p><strong>Payment Reference:</strong> " . htmlspecialchars($order['payment_reference']) . "</p>" : "") . "
                </div>

                <div class='total'>
                    TOTAL PAID: KES {$totalPaidFormatted}
                </div>

                <div class='footer'>
                    <p>Thank you for your payment!</p>
                    <p>Addins Meals on Wheels</p>
                    <p>Order #{$order['order_number']} | Generated: " . date('M j, Y \a\t g:i A') . "</p>
                </div>
            </div>
        </body>
        </html>";
    }
}
?>
