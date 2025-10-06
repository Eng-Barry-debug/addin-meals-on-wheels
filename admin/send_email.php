<?php
// Send Email functionality for invoices and receipts
require_once '../includes/config.php';
require_once '../includes/EmailService.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit();
}

$action = $_POST['action'] ?? '';
$order_id = (int)($_POST['order_id'] ?? 0);
$customer_email = trim($_POST['customer_email'] ?? '');

if (!$action || !$order_id || !$customer_email) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
    exit();
}

// Verify order exists and get order details
try {
    $stmt = $pdo->prepare("
        SELECT o.*, u.name as customer_name, u.email as customer_email,
               GROUP_CONCAT(oi.item_name SEPARATOR ', ') as item_names,
               GROUP_CONCAT(oi.quantity SEPARATOR ', ') as item_quantities,
               GROUP_CONCAT(oi.price SEPARATOR ', ') as item_prices,
               GROUP_CONCAT(oi.total SEPARATOR ', ') as item_totals,
               COUNT(oi.id) as item_count,
               SUM(oi.total) as subtotal
        FROM orders o
        LEFT JOIN users u ON o.user_id = u.id
        LEFT JOIN order_items oi ON o.id = oi.order_id
        WHERE o.id = :order_id
        GROUP BY o.id
    ");

    $stmt->execute([':order_id' => $order_id]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$order) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Order not found']);
        exit();
    }

} catch (PDOException $e) {
    error_log("Error fetching order for email: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error']);
    exit();
}

// Initialize email service
try {
    if (!class_exists('EmailService')) {
        require_once '../includes/EmailService.php';
    }
    $emailService = new EmailService();
} catch (Exception $e) {
    error_log("EmailService initialization error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Email service not available']);
    exit();
}

$subject = '';
$message = '';
$success = false;

try {
    switch ($action) {
        case 'send_invoice':
            $subject = "Invoice for Order #{$order['order_number']} - Addins Meals on Wheels";
            $message = generateInvoiceEmail($order);
            $success = $emailService->sendEmail($customer_email, $subject, $message);
            break;

        case 'send_receipt':
            $subject = "Receipt for Order #{$order['order_number']} - Addins Meals on Wheels";
            $message = generateReceiptEmail($order);
            $success = $emailService->sendEmail($customer_email, $subject, $message);
            break;

        default:
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
            exit();
    }

    if ($success) {
        echo json_encode([
            'success' => true,
            'message' => ucfirst(str_replace('_', ' ', $action)) . ' sent successfully to ' . $customer_email
        ]);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Failed to send email - check email configuration']);
    }

} catch (Exception $e) {
    error_log("Email sending error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Email service error: ' . $e->getMessage()]);
}

function generateInvoiceEmail($order) {
    $invoiceData = [
        'orderNumber' => $order['order_number'],
        'customerName' => $order['customer_name'],
        'customerEmail' => $order['customer_email'],
        'orderDate' => date('M j, Y \a\t g:i A', strtotime($order['created_at'])),
        'deliveryAddress' => $order['delivery_address'],
        'paymentMethod' => ucfirst(str_replace('_', ' ', $order['payment_method'])),
        'status' => ucfirst($order['status']),
        'subtotal' => $order['subtotal'] ?? 0,
        'deliveryFee' => $order['delivery_fee'] ?? 0,
        'totalAmount' => $order['total_amount'] ?? 0,
        'items' => []
    ];

    // Parse item data
    if (!empty($order['item_names'])) {
        $item_names = explode(', ', $order['item_names']);
        $item_quantities = explode(', ', $order['item_quantities']);
        $item_prices = explode(', ', $order['item_prices']);
        $item_totals = explode(', ', $order['item_totals']);

        for ($i = 0; $i < count($item_names); $i++) {
            $invoiceData['items'][] = [
                'name' => $item_names[$i] ?? '',
                'quantity' => $item_quantities[$i] ?? '0',
                'unitPrice' => $item_prices[$i] ?? 0,
                'total' => $item_totals[$i] ?? 0
            ];
        }
    }

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
    <body>
            <h1>INVOICE</h1>
            <p>Order #{$order['order_number']}</p>
        </div>

        <div class="company-info">
            <h2>Addins Meals on Wheels</h2>
            <p>Delicious Food Delivery Service</p>
            <p>  +254 112 855 900 |   info@addinsmeals.com</p>
        </div>

        <div style="display: flex; justify-content: space-between; margin: 20px 0;">
            <div class="info-section">
                <div class="info-title">Bill To:</div>
                <div class="info-item"><strong>{$order['customer_name']}</strong></div>
                <div class="info-item">{$order['customer_email']}</div>
                <div class="info-item">{$order['customer_phone']}</div>
            </div>

            <div class="info-section">
                <div class="info-title">Order Details:</div>
                <div class="info-item"><strong>Order #:</strong> {$invoiceData['orderNumber']}</div>
                <div class="info-item"><strong>Date:</strong> {$invoiceData['orderDate']}</div>
                <div class="info-item"><strong>Status:</strong> {$invoiceData['status']}</div>
                <div class="info-item"><strong>Payment:</strong> {$invoiceData['paymentMethod']}</div>
                <?php if (!empty($order['payment_status'])): ?>
                <div class="info-item"><strong>Payment Status:</strong> <?= ucfirst($order['payment_status']) ?></div>
                <?php endif; ?>
                <?php if (!empty($order['payment_reference'])): ?>
                <div class="info-item"><strong>Reference:</strong> <?= htmlspecialchars($order['payment_reference']) ?></div>
                <?php endif; ?>
            </div>
        </div>

        <div class="delivery-info" style="margin: 20px 0; padding: 15px; background-color: #f8f9fa; border-radius: 5px;">
            <div class="info-title">Delivery Information:</div>
            <div><strong>Address:</strong> {$invoiceData['deliveryAddress']}</div>
            <?php if (!empty($order['delivery_instructions'])): ?>
            <div><strong>Instructions:</strong> <?= htmlspecialchars($order['delivery_instructions']) ?></div>
            <?php endif; ?>
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
                " . implode('', array_map(function($item) {
                    return "
                    <tr>
                        <td>{$item['name']}</td>
                        <td style='text-align: center;'>{$item['quantity']}</td>
                        <td style='text-align: right;'>KES " . number_format($item['unitPrice'], 2) . "</td>
                        <td style='text-align: right;'>KES " . number_format($item['total'], 2) . "</td>
                    </tr>";
                }, $invoiceData['items'])) . "
            </tbody>
        </table>

        <table style='margin-left: auto; width: 300px;'>
            <tr>
                <td>Subtotal:</td>
                <td style='text-align: right;'>KES " . number_format($invoiceData['subtotal'], 2) . "</td>
            </tr>
            <tr>
                <td>Delivery Fee:</td>
                <td style='text-align: right;'>" . ($invoiceData['deliveryFee'] === 0 ? 'FREE' : 'KES ' . number_format($invoiceData['deliveryFee'], 2)) . "</td>
            </tr>
            <tr>
                <td><strong>TOTAL:</strong></td>
                <td style='text-align: right;' class='total'>KES " . number_format($invoiceData['totalAmount'], 2) . "</td>
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

function generateReceiptEmail($order) {
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
                <p><strong>Name:</strong> {$order['customer_name']}</p>
                <p><strong>Email:</strong> {$order['customer_email']}</p>
                <p><strong>Phone:</strong> {$order['customer_phone']}</p>
                <p><strong>Order Date:</strong> " . date('M j, Y \a\t g:i A', strtotime($order['created_at'])) . "</p>
            </div>

            <div class='items'>
                <h3>Order Items:</h3>
                " . (!empty($order['item_names']) ?
                    implode('', array_map(function($name, $qty, $price, $total) {
                        return "
                        <div class='item'>
                            <div>
                                <strong>{$name}</strong> x{$qty}
                                <br><small>Unit Price: KES " . number_format($price, 2) . "</small>
                            </div>
                            <div><strong>KES " . number_format($total, 2) . "</strong></div>
                        </div>";
                    }, explode(', ', $order['item_names']), explode(', ', $order['item_quantities']), explode(', ', $order['item_prices'] ?? []), explode(', ', $order['item_totals'] ?? []))) :
                    '<p>No items found</p>'
                ) . "
            </div>

            <div class='payment-info' style='margin: 20px 0; padding: 15px; background-color: #e9ecef; border-radius: 5px;'>
                <h3>Payment Information:</h3>
                <p><strong>Payment Method:</strong> " . ucfirst(str_replace('_', ' ', $order['payment_method'])) . "</p>
                <?php if (!empty($order['payment_status'])): ?>
                <p><strong>Payment Status:</strong> " . ucfirst($order['payment_status']) . "</p>
                <?php endif; ?>
                <?php if (!empty($order['payment_reference'])): ?>
                <p><strong>Payment Reference:</strong> {$order['payment_reference']}</p>
                <?php endif; ?>
            </div>

            <div class='total'>
                TOTAL PAID: KES " . number_format($order['total_amount'] ?? 0, 2) . "
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
?>
