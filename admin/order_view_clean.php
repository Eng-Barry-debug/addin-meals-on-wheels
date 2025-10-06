<?php
// Start session and check admin authentication
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit();
}

// Include database connection and header
require_once 'includes/header.php';
require_once '../includes/config.php';

// Get order ID from URL
$order_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$order_id) {
    $_SESSION['message'] = ['type' => 'error', 'text' => 'Invalid order ID'];
    header('Location: orders.php');
    exit();
}

try {
    // Fetch order details with a single query
    $stmt = $pdo->prepare("
        SELECT o.*,
               CONCAT(o.item_names, ', Total: ', o.total_amount) as order_summary
        FROM orders o
        WHERE o.id = ?
    ");
    $stmt->execute([$order_id]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$order) {
        $_SESSION['message'] = ['type' => 'error', 'text' => 'Order not found'];
        header('Location: orders.php');
        exit();
    }

} catch (PDOException $e) {
    error_log("Error fetching order: " . $e->getMessage());
    $_SESSION['message'] = ['type' => 'error', 'text' => 'Database error occurred'];
    header('Location: orders.php');
    exit();
}
?>

<div class="container mx-auto px-6 py-8">
    <!-- Header -->
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-8">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">Order Details</h1>
            <p class="text-gray-600 mt-1">Order #<?= htmlspecialchars($order['order_number']) ?></p>
        </div>
        <div class="mt-4 md:mt-0 flex space-x-3">
            <a href="orders.php" class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-lg font-semibold transition-colors duration-200 flex items-center">
                <i class="fas fa-arrow-left mr-2"></i>
                Back to Orders
            </a>
            <a href="order_edit.php?id=<?= $order['id'] ?>" class="bg-white text-red-600 hover:bg-gray-100 px-6 py-3 rounded-lg font-semibold transition-colors duration-200 flex items-center shadow-lg">
                <i class="fas fa-edit mr-2"></i>
                Edit Order
            </a>
            <button onclick="printOrder()" class="bg-green-600 hover:bg-green-700 text-white px-6 py-3 rounded-lg font-semibold transition-colors duration-200 flex items-center shadow-lg">
                <i class="fas fa-print mr-2"></i>
                Print Order
            </button>
            <button onclick="generateInvoice()" class="bg-purple-600 hover:bg-purple-700 text-white px-6 py-3 rounded-lg font-semibold transition-colors duration-200 flex items-center shadow-lg">
                <i class="fas fa-file-invoice-dollar mr-2"></i>
                Generate Invoice
            </button>
        </div>
    </div>

    <!-- Order Status Card -->
    <div class="bg-white rounded-xl shadow-lg p-6 mb-8 border-l-4 <?php
        echo match($order['status']) {
            'pending' => 'border-yellow-500',
            'processing' => 'border-blue-500',
            'confirmed' => 'border-green-500',
            'delivered' => 'border-purple-500',
            'cancelled' => 'border-red-500',
            default => 'border-gray-500'
        };
    ?>">
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center">
            <div class="flex items-center space-x-4 mb-4 md:mb-0">
                <div class="w-12 h-12 rounded-full flex items-center justify-center <?php
                    echo match($order['status']) {
                        'pending' => 'bg-yellow-100',
                        'processing' => 'bg-blue-100',
                        'confirmed' => 'bg-green-100',
                        'delivered' => 'bg-purple-100',
                        'cancelled' => 'bg-red-100',
                        default => 'bg-gray-100'
                    };
                ?>">
                    <i class="fas <?php
                        echo match($order['status']) {
                            'pending' => 'fa-clock text-yellow-600',
                            'processing' => 'fa-cog text-blue-600',
                            'confirmed' => 'fa-check-circle text-green-600',
                            'delivered' => 'fa-truck text-purple-600',
                            'cancelled' => 'fa-times-circle text-red-600',
                            default => 'fa-question-circle text-gray-600'
                        };
                    ?> text-xl"></i>
                </div>
                <div>
                    <h2 class="text-xl font-bold text-gray-900">Order Status</h2>
                    <p class="text-gray-600 capitalize"><?php echo htmlspecialchars($order['status']); ?></p>
                </div>
            </div>
            <div class="text-right">
                <span class="text-2xl font-bold text-red-600">
                    KES <?= number_format($order['total_amount'] ?? 0, 2) ?>
                </span>
                <p class="text-sm text-gray-600">Total Amount</p>
            </div>
        </div>
    </div>

    <!-- Order Details Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
        <!-- Customer Information -->
        <div class="bg-white rounded-xl shadow-lg p-6">
            <h3 class="text-xl font-bold text-gray-900 mb-6 flex items-center">
                <i class="fas fa-user mr-3 text-blue-600"></i>
                Customer Information
            </h3>
            <div class="space-y-4">
                <div class="flex items-center space-x-3">
                    <div class="w-10 h-10 bg-blue-100 rounded-full flex items-center justify-center">
                        <i class="fas fa-user text-blue-600"></i>
                    </div>
                    <div>
                        <p class="font-medium text-gray-900"><?php echo htmlspecialchars($order['customer_name']); ?></p>
                        <p class="text-sm text-gray-600">Customer Name</p>
                    </div>
                </div>

                <div class="flex items-center space-x-3">
                    <div class="w-10 h-10 bg-green-100 rounded-full flex items-center justify-center">
                        <i class="fas fa-envelope text-green-600"></i>
                    </div>
                    <div>
                        <p class="font-medium text-gray-900"><?php echo htmlspecialchars($order['customer_email']); ?></p>
                        <p class="text-sm text-gray-600">Email Address</p>
                    </div>
                </div>

                <div class="flex items-center space-x-3">
                    <div class="w-10 h-10 bg-purple-100 rounded-full flex items-center justify-center">
                        <i class="fas fa-phone text-purple-600"></i>
                    </div>
                    <div>
                        <p class="font-medium text-gray-900"><?php echo htmlspecialchars($order['customer_phone']); ?></p>
                        <p class="text-sm text-gray-600">Phone Number</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Order Information -->
        <div class="bg-white rounded-xl shadow-lg p-6">
            <h3 class="text-xl font-bold text-gray-900 mb-6 flex items-center">
                <i class="fas fa-shopping-cart mr-3 text-green-600"></i>
                Order Information
            </h3>
            <div class="space-y-4">
                <div class="flex justify-between">
                    <span class="text-gray-600">Order Number:</span>
                    <span class="font-semibold text-gray-900">#<?= htmlspecialchars($order['order_number']) ?></span>
                </div>

                <div class="flex justify-between">
                    <span class="text-gray-600">Order Date:</span>
                    <span class="font-semibold text-gray-900"><?php echo date('M j, Y \a\t g:i A', strtotime($order['created_at'])); ?></span>
                </div>

                <div class="flex justify-between">
                    <span class="text-gray-600">Payment Method:</span>
                    <span class="font-semibold text-gray-900"><?php echo ucfirst(str_replace('_', ' ', $order['payment_method'])); ?></span>
                </div>

                <div class="flex justify-between">
                    <span class="text-gray-600">Payment Status:</span>
                    <span class="px-3 py-1 rounded-full text-sm font-semibold <?php
                        echo match($order['payment_status'] ?? 'pending') {
                            'paid' => 'bg-green-200 text-green-800',
                            'pending' => 'bg-yellow-200 text-yellow-800',
                            'failed' => 'bg-red-200 text-red-800',
                            default => 'bg-gray-200 text-gray-800'
                        };
                    ?>">
                        <?php echo ucfirst($order['payment_status'] ?? 'pending'); ?>
                    </span>
                </div>
            </div>
        </div>
    </div>

    <!-- Delivery Information -->
    <div class="bg-white rounded-xl shadow-lg p-6 mb-8">
        <h3 class="text-xl font-bold text-gray-900 mb-6 flex items-center">
            <i class="fas fa-map-marker-alt mr-3 text-purple-600"></i>
            Delivery Information
        </h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <h4 class="font-semibold text-gray-900 mb-2">Delivery Address</h4>
                <p class="text-gray-700 bg-gray-50 p-3 rounded-lg">
                    <?php echo htmlspecialchars($order['delivery_address']); ?>
                </p>
            </div>
        </div>
    </div>

    <!-- Order Items -->
    <div class="bg-white rounded-xl shadow-lg p-6 mb-8">
        <h3 class="text-xl font-bold text-gray-900 mb-6 flex items-center">
            <i class="fas fa-list mr-3 text-indigo-600"></i>
            Order Items
        </h3>

        <?php
        // Split the concatenated data back into arrays
        $item_names = !empty($order['item_names']) ? explode(', ', $order['item_names']) : [];
        $item_quantities = !empty($order['item_quantities']) ? explode(', ', $order['item_quantities']) : [];
        $item_prices = !empty($order['item_prices']) ? explode(', ', $order['item_prices']) : [];
        $item_totals = !empty($order['item_totals']) ? explode(', ', $order['item_totals']) : [];
        $item_count = count($item_names);
        ?>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Item</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Quantity</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Unit Price</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php if ($item_count > 0): ?>
                        <?php for ($i = 0; $i < $item_count; $i++): ?>
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($item_names[$i] ?? 'Unknown Item'); ?></div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900"><?php echo htmlspecialchars($item_quantities[$i] ?? '0'); ?></div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900">KES <?php echo number_format($item_prices[$i] ?? 0, 2); ?></div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-semibold text-gray-900">KES <?php echo number_format($item_totals[$i] ?? 0, 2); ?></div>
                            </td>
                        </tr>
                        <?php endfor; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="4" class="px-6 py-4 text-center text-gray-500">
                                No items found in this order.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Financial Summary -->
    <div class="bg-white rounded-xl shadow-lg p-6">
        <h3 class="text-xl font-bold text-gray-900 mb-6 flex items-center">
            <i class="fas fa-calculator mr-3 text-red-600"></i>
            Financial Summary
        </h3>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div class="text-center p-4 bg-gray-50 rounded-lg">
                <p class="text-sm text-gray-600 mb-1">Subtotal</p>
                <p class="text-2xl font-bold text-gray-900">KES <?php echo number_format($order['subtotal'] ?? 0, 2); ?></p>
            </div>

            <div class="text-center p-4 bg-gray-50 rounded-lg">
                <p class="text-sm text-gray-600 mb-1">Delivery Fee</p>
                <p class="text-2xl font-semibold <?php echo (($order['delivery_fee'] ?? 0) === 0) ? 'text-green-600' : 'text-gray-900'; ?>">
                    <?php echo (($order['delivery_fee'] ?? 0) === 0) ? 'FREE' : 'KES ' . number_format($order['delivery_fee'] ?? 0, 2); ?>
                </p>
            </div>

            <div class="text-center p-4 bg-red-50 rounded-lg border-2 border-red-200">
                <p class="text-sm text-gray-600 mb-1">Total Amount</p>
                <p class="text-3xl font-bold text-red-600">KES <?php echo number_format($order['total_amount'] ?? 0, 2); ?></p>
            </div>
        </div>
    </div>
</div>

<script>
// Print order functionality
function printOrder() {
    console.log('Print Order function called');
    try {
        window.print();
        console.log('Print dialog opened');
    } catch (error) {
        console.error('Print error:', error);
        alert('Error opening print dialog: ' + error.message);
    }
}

// Generate invoice functionality
function generateInvoice() {
    console.log('Generate Invoice function called');
    try {
        // Get order data
        const orderData = {
            orderNumber: '<?= $order['order_number'] ?>',
            customerName: '<?= htmlspecialchars($order['customer_name']) ?>',
            customerEmail: '<?= htmlspecialchars($order['customer_email']) ?>',
            customerPhone: '<?= htmlspecialchars($order['customer_phone']) ?>',
            orderDate: '<?= date('M j, Y \a\t g:i A', strtotime($order['created_at'])) ?>',
            deliveryAddress: '<?= htmlspecialchars($order['delivery_address']) ?>',
            paymentMethod: '<?= ucfirst(str_replace('_', ' ', $order['payment_method'])) ?>',
            subtotal: <?= $order['subtotal'] ?? 0 ?>,
            deliveryFee: <?= $order['delivery_fee'] ?? 0 ?>,
            totalAmount: <?= $order['total_amount'] ?? 0 ?>,
            status: '<?= ucfirst($order['status']) ?>',
            items: [
                <?php
                $item_names = !empty($order['item_names']) ? explode(', ', $order['item_names']) : [];
                $item_quantities = !empty($order['item_quantities']) ? explode(', ', $order['item_quantities']) : [];
                $item_prices = !empty($order['item_prices']) ? explode(', ', $item_prices) : [];
                $item_totals = !empty($order['item_totals']) ? explode(', ', $order['item_totals']) : [];

                for ($i = 0; $i < count($item_names); $i++):
                ?>
                {
                    name: '<?= htmlspecialchars($item_names[$i] ?? '') ?>',
                    quantity: '<?= htmlspecialchars($item_quantities[$i] ?? '0') ?>',
                    unitPrice: <?= $item_prices[$i] ?? 0 ?>,
                    total: <?= $item_totals[$i] ?? 0 ?>
                }<?php if ($i < count($item_names) - 1): ?>,<?php endif; ?>
                <?php endfor; ?>
            ]
        };

        console.log('Order data prepared:', orderData);

        // Create popup window
        const popup = window.open('', '_blank', 'width=800,height=600,scrollbars=yes');
        if (!popup) {
            alert('Please allow popups for this site to generate invoices');
            return;
        }

        // Generate HTML content
        const html = generateInvoiceHTML(orderData);
        popup.document.write(html);
        popup.document.close();

        // Print after content loads
        popup.onload = function() {
            popup.print();
        };

        console.log('Invoice popup created and print triggered');

    } catch (error) {
        console.error('Invoice generation error:', error);
        alert('Error generating invoice: ' + error.message);
    }
}

// Generate invoice HTML
function generateInvoiceHTML(data) {
    return `
        <!DOCTYPE html>
        <html>
        <head>
            <title>Invoice #${data.orderNumber}</title>
            <meta charset="utf-8">
            <style>
                body {
                    font-family: Arial, sans-serif;
                    margin: 20px;
                    color: #333;
                    line-height: 1.6;
                }
                .invoice-header {
                    text-align: center;
                    margin-bottom: 30px;
                    border-bottom: 3px solid #dc2626;
                    padding-bottom: 20px;
                }
                .invoice-title {
                    color: #dc2626;
                    font-size: 32px;
                    font-weight: bold;
                    margin: 0;
                }
                .invoice-subtitle {
                    color: #666;
                    font-size: 18px;
                    margin: 10px 0 0 0;
                }
                .company-info {
                    text-align: center;
                    margin-bottom: 40px;
                    padding: 20px;
                    background-color: #f8f9fa;
                    border-radius: 8px;
                }
                .company-name {
                    font-size: 24px;
                    font-weight: bold;
                    color: #dc2626;
                    margin-bottom: 10px;
                }
                .company-details {
                    color: #666;
                    margin: 5px 0;
                }
                .info-section {
                    display: inline-block;
                    width: 48%;
                    margin-bottom: 30px;
                    vertical-align: top;
                }
                .info-title {
                    font-weight: bold;
                    color: #dc2626;
                    margin-bottom: 15px;
                    font-size: 16px;
                }
                .info-item {
                    margin: 8px 0;
                    padding: 5px 0;
                }
                .delivery-info {
                    margin: 25px 0;
                    padding: 20px;
                    background-color: #f8f9fa;
                    border-radius: 8px;
                    border-left: 4px solid #dc2626;
                }
                table {
                    width: 100%;
                    border-collapse: collapse;
                    margin: 25px 0;
                    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
                }
                th, td {
                    padding: 15px;
                    text-align: left;
                    border-bottom: 1px solid #ddd;
                }
                th {
                    background-color: #dc2626;
                    color: white;
                    font-weight: bold;
                    text-transform: uppercase;
                    font-size: 12px;
                }
                .text-right { text-align: right; }
                .total-row {
                    font-weight: bold;
                    background-color: #f8f9fa;
                    font-size: 16px;
                }
                .grand-total {
                    font-size: 20px;
                    color: #dc2626;
                    font-weight: bold;
                }
                .summary-table {
                    margin-left: auto;
                    width: 350px;
                    background-color: #f8f9fa;
                    border-radius: 8px;
                    overflow: hidden;
                }
                .footer {
                    text-align: center;
                    margin-top: 50px;
                    padding-top: 25px;
                    border-top: 2px solid #ddd;
                    color: #666;
                    font-size: 14px;
                }
                .thank-you {
                    font-size: 18px;
                    color: #dc2626;
                    font-weight: bold;
                    margin-bottom: 10px;
                }
                @media print {
                    body { margin: 0; }
                    .no-print { display: none; }
                }
            </style>
        </head>
        <body>
            <div class="invoice-header">
                <h1 class="invoice-title">INVOICE</h1>
                <p class="invoice-subtitle">Order #${data.orderNumber}</p>
            </div>

            <div class="company-info">
                <div class="company-name">Addis Ababa Meals on Wheels</div>
                <div class="company-details">Delicious Food Delivery Service</div>
                <div class="company-details">📞 +251 911 123 456 | 📧 info@addisababameals.com</div>
                <div class="company-details">🏢 Bole, Addis Ababa, Ethiopia</div>
            </div>

            <div style="display: flex; justify-content: space-between; margin-bottom: 30px;">
                <div class="info-section">
                    <div class="info-title">Bill To:</div>
                    <div class="info-item"><strong>${data.customerName}</strong></div>
                    <div class="info-item">${data.customerEmail}</div>
                    <div class="info-item">${data.customerPhone}</div>
                </div>

                <div class="info-section">
                    <div class="info-title">Order Details:</div>
                    <div class="info-item"><strong>Order #:</strong> ${data.orderNumber}</div>
                    <div class="info-item"><strong>Date:</strong> ${data.orderDate}</div>
                    <div class="info-item"><strong>Status:</strong> ${data.status}</div>
                    <div class="info-item"><strong>Payment:</strong> ${data.paymentMethod}</div>
                </div>
            </div>

            <div class="delivery-info">
                <div class="info-title">Delivery Address:</div>
                <div>${data.deliveryAddress}</div>
            </div>

            <table>
                <thead>
                    <tr>
                        <th>Item Description</th>
                        <th class="text-right">Qty</th>
                        <th class="text-right">Unit Price</th>
                        <th class="text-right">Total</th>
                    </tr>
                </thead>
                <tbody>
                    ${data.items.map(item => `
                        <tr>
                            <td>${item.name}</td>
                            <td class="text-right">${item.quantity}</td>
                            <td class="text-right">ETB ${item.unitPrice.toFixed(2)}</td>
                            <td class="text-right">ETB ${item.total.toFixed(2)}</td>
                        </tr>
                    `).join('')}
                </tbody>
            </table>

            <table class="summary-table">
                <tr>
                    <td style="padding: 15px; font-weight: bold;">Subtotal:</td>
                    <td class="text-right" style="padding: 15px;">ETB ${data.subtotal.toFixed(2)}</td>
                </tr>
                <tr>
                    <td style="padding: 15px; font-weight: bold;">Delivery Fee:</td>
                    <td class="text-right" style="padding: 15px;">${data.deliveryFee === 0 ? 'FREE' : 'ETB ' + data.deliveryFee.toFixed(2)}</td>
                </tr>
                <tr style="background-color: #dc2626; color: white;">
                    <td style="padding: 20px; font-weight: bold; font-size: 18px;">TOTAL AMOUNT:</td>
                    <td class="text-right grand-total" style="padding: 20px; color: white;">ETB ${data.totalAmount.toFixed(2)}</td>
                </tr>
            </table>

            <div class="footer">
                <div class="thank-you">Thank you for choosing Addis Ababa Meals on Wheels!</div>
                <p>We appreciate your business and look forward to serving you again.</p>
                <p><strong>Invoice generated on:</strong> ${new Date().toLocaleDateString('en-US', {
                    year: 'numeric',
                    month: 'long',
                    day: 'numeric',
                    hour: '2-digit',
                    minute: '2-digit'
                })}</p>
            </div>
        </body>
        </html>
    `;
}
</script>

<style>
@media print {
    .no-print {
        display: none !important;
    }

    body {
        background: white !important;
    }

    .bg-gradient-to-br {
        background: #dc2626 !important;
        -webkit-print-color-adjust: exact;
    }
}
</style>

<?php include 'includes/footer.php'; ?>
