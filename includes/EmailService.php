<?php
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

    public function sendEmail($to, $subject, $message, $attachments = []) {
        try {
            // For now, we'll use PHP's built-in mail function
            // In production, you should use PHPMailer or similar library for better reliability

            $headers = "MIME-Version: 1.0" . "\r\n";
            $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
            $headers .= "From: {$this->from_name} <{$this->from_email}>" . "\r\n";
            $headers .= "Reply-To: {$this->from_email}" . "\r\n";

            // Basic email sending (you should replace this with a proper email service)
            $success = mail($to, $subject, $message, $headers);

            if ($success) {
                error_log("Email sent successfully to: $to");
                return true;
            } else {
                error_log("Failed to send email to: $to - mail() function returned false");
                return false;
            }

        } catch (Exception $e) {
            error_log("Email sending error: " . $e->getMessage());
            return false;
        }
    }

    public function sendInvoiceEmail($to, $orderData) {
        $subject = "Invoice for Order #{$orderData['order_number']} - Addis Ababa Meals";
        $message = $this->generateInvoiceEmailContent($orderData);

        return $this->sendEmail($to, $subject, $message);
    }

    public function sendReceiptEmail($to, $orderData) {
        $subject = "Receipt for Order #{$orderData['order_number']} - Addis Ababa Meals";
        $message = $this->generateReceiptEmailContent($orderData);

        return $this->sendEmail($to, $subject, $message);
    }

    private function generateInvoiceEmailContent($order) {
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
                .customer-info { background: #f8f9fa; padding: 15px; margin: 20px 0; border-radius: 5px; }
                .order-info { background: #e9ecef; padding: 15px; margin: 20px 0; border-radius: 5px; }
                table { width: 100%; border-collapse: collapse; margin: 20px 0; }
                th, td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
                th { background-color: #f8f9fa; }
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
                    <p><strong>{$order['customer_name']}</strong></p>
                    <p>{$order['customer_email']}</p>
                </div>

                <div class='order-info'>
                    <h3>Order Details:</h3>
                    <p><strong>Date:</strong> " . date('M j, Y \a\t g:i A', strtotime($order['created_at'])) . "</p>
                    <p><strong>Status:</strong> " . ucfirst($order['status']) . "</p>
                    <p><strong>Payment Method:</strong> " . ucfirst(str_replace('_', ' ', $order['payment_method'])) . "</p>
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
                        <td style='text-align: right;'>KES " . number_format($order['subtotal'] ?? 0, 2) . "</td>
                    </tr>
                    <tr>
                        <td>Delivery Fee:</td>
                        <td style='text-align: right;'>" . (($order['delivery_fee'] ?? 0) === 0 ? 'FREE' : 'KES ' . number_format($order['delivery_fee'] ?? 0, 2)) . "</td>
                    </tr>
                    <tr>
                        <td><strong>TOTAL:</strong></td>
                        <td style='text-align: right;' class='total'>KES " . number_format($order['total_amount'] ?? 0, 2) . "</td>
                    </tr>
                </table>

                <div class='footer'>
                    <p>Thank you for choosing Addis Ababa Meals on Wheels!</p>
                    <p>This is an official invoice for your recent order.</p>
                </div>
            </div>
        </body>
        </html>";
    }

    private function generateReceiptEmailContent($order) {
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
                .items { margin: 20px 0; }
                .total { font-weight: bold; font-size: 18px; color: #28a745; text-align: center; margin-top: 20px; }
                .footer { text-align: center; margin-top: 30px; padding-top: 20px; border-top: 1px solid #ddd; color: #666; }
            </style>
        </head>
        <body>
            <div class="company-info">
                <h2>Addins Meals on Wheels</h2>
                <p>Delicious Food Delivery Service</p>
                <p>📞 +254 112 855 900 | 📧 info@addinsmeals.com</p>
            </div>
            <div class='customer-info'>
                <h3>Customer:</h3>
                <p><strong>{$order['customer_name']}</strong></p>
                <p>Date: " . date('M j, Y \a\t g:i A', strtotime($order['created_at'])) . "</p>
                    <h3>Order Items:</h3>
                    " . $this->generateReceiptItemsHTML($order) . "
                </div>

                <div class='total'>
                    TOTAL PAID: KES " . number_format($order['total_amount'] ?? 0, 2) . "
                </div>

                <div class='footer'>
                    <p>Thank you for your payment!</p>
                    <p>Addins Meals on Wheels</p>
                </div>
            </div>
        </body>
        </html>";
    }

    private function generateOrderItemsHTML($order) {
        if (empty($order['item_names'])) {
            return '<tr><td colspan="4">No items found</td></tr>';
        }

        $item_names = explode(', ', $order['item_names']);
        $item_quantities = explode(', ', $order['item_quantities']);
        $item_prices = explode(', ', $order['item_prices']);
        $item_totals = explode(', ', $order['item_totals']);

        $html = '';
        for ($i = 0; $i < count($item_names); $i++) {
            $html .= "
            <tr>
                <td>{$item_names[$i]}</td>
                <td style='text-align: center;'>{$item_quantities[$i]}</td>
                <td style='text-align: right;'>KES " . number_format($item_prices[$i] ?? 0, 2) . "</td>
                <td style='text-align: right;'>KES " . number_format($item_totals[$i] ?? 0, 2) . "</td>
            </tr>";
        }
        return $html;
    }

    private function generateReceiptItemsHTML($order) {
        if (empty($order['item_names'])) {
            return '<p>No items found</p>';
        }

        $item_names = explode(', ', $order['item_names']);
        $item_quantities = explode(', ', $order['item_quantities']);

        $html = '';
        for ($i = 0; $i < count($item_names); $i++) {
            $html .= "<div class='item'><span>{$item_names[$i]} x{$item_quantities[$i]}</span></div>";
        }
        return $html;
    }
}
?>
