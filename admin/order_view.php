<?php
// Set page title and include header
$page_title = 'Order Details';
$page_description = 'View detailed order information';

// Include database connection and functions
require_once '../includes/config.php';
require_once 'includes/functions.php';

// Check if order ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['message'] = ['type' => 'error', 'text' => 'Order ID is required'];
    header('Location: orders.php');
    exit();
}

$order_id = (int)$_GET['id'];

// Fetch order details with related information
$query = "SELECT o.*,
                u.name as customer_name,
                u.email as customer_email,
                u.phone as customer_phone,
                GROUP_CONCAT(oi.item_name SEPARATOR '|||') as item_names,
                GROUP_CONCAT(oi.quantity SEPARATOR '|||') as item_quantities,
                GROUP_CONCAT(oi.price SEPARATOR '|||') as item_prices,
                GROUP_CONCAT(oi.total SEPARATOR '|||') as item_totals,
                COUNT(oi.id) as item_count,
                (SELECT SUM(total) FROM order_items WHERE order_id = o.id) as subtotal,
                (SELECT SUM(total) FROM order_items WHERE order_id = o.id) + COALESCE(o.delivery_fee, 0) as total_amount
         FROM orders o
         LEFT JOIN users u ON o.user_id = u.id
         LEFT JOIN order_items oi ON o.id = oi.order_id
         WHERE o.id = :order_id
         GROUP BY o.id";

$params = [':order_id' => $order_id];

try {
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$order) {
        $_SESSION['message'] = ['type' => 'error', 'text' => 'Order not found'];
        header('Location: orders.php');
        exit();
    }
} catch (PDOException $e) {
    error_log("Error fetching order details: " . $e->getMessage());
    $_SESSION['message'] = ['type' => 'error', 'text' => 'Database error occurred'];
    header('Location: orders.php');
    exit();
}

// Include header
include 'includes/header.php';
?>

<!-- Dashboard Header -->
<div class="bg-gradient-to-br from-primary via-primary-dark to-secondary text-white">
    <div class="container mx-auto px-6 py-8">
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between">
            <div>
                <nav class="flex items-center space-x-2 text-sm mb-4">
                    <a href="orders.php" class="hover:text-primary transition-colors">
                        <i class="fas fa-arrow-left mr-1"></i> Back to Orders
                    </a>
                    <span class="text-primary">/</span>
                    <span>Order #<?= htmlspecialchars($order['order_number']) ?></span>
                </nav>
                <h1 class="text-3xl md:text-4xl font-bold mb-2">Order Details</h1>
                <p class="text-lg opacity-90">Complete order information and management options</p>
            </div>
            <div class="mt-4 lg:mt-0 flex space-x-3">
                <a href="order_edit.php?id=<?= $order['id'] ?>"
                   class="bg-white text-primary hover:bg-gray-100 px-6 py-3 rounded-lg font-semibold transition-colors duration-200 flex items-center shadow-lg">
                    <i class="fas fa-edit mr-2"></i>
                    Edit Order
                </a>
                <button onclick="generateInvoice()" class="bg-purple-600 hover:bg-purple-700 text-white px-6 py-3 rounded-lg font-semibold transition-colors duration-200 flex items-center shadow-lg">
                    <i class="fas fa-file-invoice-dollar mr-2"></i>
                    Generate Invoice
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Main Content -->
<div class="container mx-auto px-6 py-8">
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
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between">
            <div class="flex items-center space-x-4">
                <div class="w-16 h-16 rounded-full flex items-center justify-center shadow-lg <?php
                    echo match($order['status']) {
                        'pending' => 'bg-yellow-100 text-yellow-600',
                        'processing' => 'bg-blue-100 text-blue-600',
                        'confirmed' => 'bg-green-100 text-green-600',
                        'delivered' => 'bg-purple-100 text-purple-600',
                        'cancelled' => 'bg-red-100 text-red-600',
                        default => 'bg-gray-100 text-gray-600'
                    };
                ?>">
                    <i class="fas fa-<?php
                        echo match($order['status']) {
                            'pending' => 'clock',
                            'processing' => 'cog',
                            'confirmed' => 'check-circle',
                            'delivered' => 'truck',
                            'cancelled' => 'times-circle'
                        };
                    ?> text-2xl"></i>
                </div>
                <div>
                    <h2 class="text-2xl font-bold text-gray-900">Order #<?= htmlspecialchars($order['order_number']) ?></h2>
                    <p class="text-gray-600">Placed on <?= date('M j, Y \a\t g:i A', strtotime($order['created_at'])) ?></p>
                    <div class="flex items-center space-x-2 mt-2">
                        <span class="px-4 py-2 rounded-full text-sm font-semibold <?php
                            echo match($order['status']) {
                                'pending' => 'bg-yellow-200 text-yellow-800',
                                'processing' => 'bg-blue-200 text-blue-800',
                                'confirmed' => 'bg-green-200 text-green-800',
                                'delivered' => 'bg-purple-200 text-purple-800',
                                'cancelled' => 'bg-red-200 text-red-800',
                                default => 'bg-gray-200 text-gray-800'
                            };
                        ?>">
                            <i class="fas fa-<?php
                                echo match($order['status']) {
                                    'pending' => 'clock',
                                    'processing' => 'cog',
                                    'confirmed' => 'check-circle',
                                    'delivered' => 'truck',
                                    'cancelled' => 'times-circle'
                                };
                            ?> mr-2 text-xs"></i>
                            <?php echo ucfirst($order['status']); ?>
                        </span>
                        <span class="text-2xl font-bold text-red-600">
                            KES <?= number_format($order['total_amount'] ?? 0, 2) ?>
                        </span>
                    </div>
                </div>
            </div>

            <!-- Status Update Actions -->
            <div class="mt-6 lg:mt-0 flex flex-wrap gap-3">
                <?php if ($order['status'] === 'pending'): ?>
                    <a href="update_status.php?id=<?= $order['id'] ?>&status=processing"
                       class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg font-semibold transition-colors duration-200 flex items-center">
                        <i class="fas fa-play mr-2"></i>
                        Start Processing
                    </a>
                <?php endif; ?>

                <?php if (in_array($order['status'], ['processing', 'confirmed'])): ?>
                    <a href="update_status.php?id=<?= $order['id'] ?>&status=delivered"
                       class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg font-semibold transition-colors duration-200 flex items-center">
                        <i class="fas fa-check mr-2"></i>
                        Mark Delivered
                    </a>
                <?php endif; ?>

                <?php if ($order['status'] !== 'cancelled' && $order['status'] !== 'delivered'): ?>
                    <a href="update_status.php?id=<?= $order['id'] ?>&status=cancelled"
                       onclick="return confirm('Are you sure you want to cancel this order?')"
                       class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg font-semibold transition-colors duration-200 flex items-center">
                        <i class="fas fa-times mr-2"></i>
                        Cancel Order
                    </a>
                <?php endif; ?>

                <button onclick="confirmDelete(<?= $order['id'] ?>, '<?= htmlspecialchars($order['order_number']) ?>')"
                        class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-lg font-semibold transition-colors duration-200 flex items-center">
                    <i class="fas fa-trash mr-2"></i>
                    Delete Order
                </button>
            </div>

            <!-- Invoice & Report Actions -->
            <div class="mt-6 lg:mt-0">
                <div class="bg-gray-50 rounded-lg p-4">
                    <h4 class="text-sm font-semibold text-gray-700 mb-3 flex items-center">
                        <i class="fas fa-file-invoice-dollar mr-2"></i>
                        Invoice & Reports
                    </h4>
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-2">
                        <button onclick="generateInvoice()"
                                class="bg-purple-600 hover:bg-purple-700 text-white px-3 py-2 rounded-lg font-semibold transition-colors duration-200 flex items-center justify-center text-sm">
                            <i class="fas fa-file-invoice-dollar mr-1"></i>
                            Invoice
                        </button>
                        <button onclick="generateReceipt()"
                                class="bg-green-600 hover:bg-green-700 text-white px-3 py-2 rounded-lg font-semibold transition-colors duration-200 flex items-center justify-center text-sm">
                            <i class="fas fa-receipt mr-1"></i>
                            Receipt
                        </button>
                        <button onclick="emailInvoice()"
                                class="bg-blue-600 hover:bg-blue-700 text-white px-3 py-2 rounded-lg font-semibold transition-colors duration-200 flex items-center justify-center text-sm">
                            <i class="fas fa-envelope mr-1"></i>
                            Email Invoice
                        </button>
                        <button onclick="emailReceipt()"
                                class="bg-orange-600 hover:bg-orange-700 text-white px-3 py-2 rounded-lg font-semibold transition-colors duration-200 flex items-center justify-center text-sm">
                            <i class="fas fa-paper-plane mr-1"></i>
                            Email Receipt
                        </button>
                    </div>
                </div>
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

                <?php if (!empty($order['notes'])): ?>
                <div class="flex items-start space-x-3">
                    <div class="w-10 h-10 bg-yellow-100 rounded-full flex items-center justify-center">
                        <i class="fas fa-sticky-note text-yellow-600"></i>
                    </div>
                    <div>
                        <p class="font-medium text-gray-900">Order Notes</p>
                        <p class="text-sm text-gray-600 mt-1"><?php echo htmlspecialchars($order['notes']); ?></p>
                    </div>
                </div>
                <?php endif; ?>
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

                <?php if (!empty($order['payment_reference'])): ?>
                <div class="flex justify-between">
                    <span class="text-gray-600">Payment Reference:</span>
                    <span class="font-semibold text-gray-900"><?php echo htmlspecialchars($order['payment_reference']); ?></span>
                </div>
                <?php endif; ?>
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

            <?php if ($order['delivery_instructions']): ?>
            <div>
                <h4 class="font-semibold text-gray-900 mb-2">Delivery Instructions</h4>
                <p class="text-gray-700 bg-yellow-50 p-3 rounded-lg border-l-4 border-yellow-400">
                    <?php echo htmlspecialchars($order['delivery_instructions']); ?>
                </p>
            </div>
            <?php endif; ?>
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
        $item_names = !empty($order['item_names']) ? explode('|||', $order['item_names']) : [];
        $item_quantities = !empty($order['item_quantities']) ? explode('|||', $order['item_quantities']) : [];
        $item_prices = !empty($order['item_prices']) ? explode('|||', $order['item_prices']) : [];
        $item_totals = !empty($order['item_totals']) ? explode('|||', $order['item_totals']) : [];
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

<!-- Email Invoice Confirmation Modal -->
<div id="emailInvoiceModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4 animate__animated animate__fadeIn">
    <div class="bg-white rounded-2xl shadow-2xl max-w-md w-full max-h-[90vh] overflow-y-auto animate__animated animate__zoomIn">
        <!-- Modal Header -->
        <div class="bg-gradient-to-r from-blue-600 to-blue-700 p-6 text-white rounded-t-2xl">
            <div class="flex items-center justify-between">
                <h3 class="text-xl font-bold flex items-center">
                    <i class="fas fa-envelope mr-2"></i>Send Invoice Email
                </h3>
                <button type="button" onclick="closeEmailInvoiceModal()" class="text-white hover:text-gray-200 text-2xl">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        </div>

        <!-- Modal Body -->
        <div class="p-6 text-gray-700">
            <div class="text-center mb-4">
                <div class="w-16 h-16 bg-blue-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-file-invoice-dollar text-2xl text-blue-600"></i>
                </div>
                <h4 class="text-lg font-semibold text-gray-900 mb-2">Send Invoice to Customer</h4>
                <p class="text-sm text-gray-600 mb-4">
                    Are you sure you want to send the invoice for order <strong>#<?= htmlspecialchars($order['order_number']) ?></strong> to the customer via email?
                </p>
                <div class="bg-gray-50 rounded-lg p-3 mb-4 text-left">
                    <div class="text-sm">
                        <div class="flex justify-between mb-1">
                            <span class="text-gray-600">Customer:</span>
                            <span class="font-medium text-gray-900"><?php echo htmlspecialchars($order['customer_name']); ?></span>
                        </div>
                        <div class="flex justify-between mb-1">
                            <span class="text-gray-600">Email:</span>
                            <span class="font-medium text-gray-900"><?php echo htmlspecialchars($order['customer_email']); ?></span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600">Total Amount:</span>
                            <span class="font-bold text-red-600">KES <?php echo number_format($order['total_amount'] ?? 0, 2); ?></span>
                        </div>
                    </div>
                </div>
                <p class="text-xs text-gray-500">This will send a professional invoice email with order details and payment information.</p>
            </div>
        </div>

        <!-- Modal Footer -->
        <div class="p-6 border-t border-gray-200 flex gap-3 justify-end">
            <button type="button" onclick="closeEmailInvoiceModal()"
                    class="group relative bg-gray-500 hover:bg-gray-600 text-white px-6 py-3 rounded-xl font-semibold transition-all duration-300 flex items-center justify-center shadow-lg hover:shadow-xl transform hover:-translate-y-0.5">
                <div class="absolute inset-0 bg-gradient-to-r from-white/0 via-white/10 to-white/0 opacity-0 group-hover:opacity-100 transition-opacity duration-300 rounded-xl"></div>
                <span class="relative z-10 font-medium">Cancel</span>
            </button>
            <button type="button" onclick="confirmEmailInvoice()"
                    class="group relative bg-gradient-to-r from-blue-600 to-blue-700 hover:from-blue-700 hover:to-blue-800 text-white px-6 py-3 rounded-xl font-semibold transition-all duration-300 flex items-center justify-center shadow-lg hover:shadow-xl transform hover:-translate-y-0.5">
                <div class="absolute inset-0 bg-gradient-to-r from-white/0 via-white/10 to-white/0 opacity-0 group-hover:opacity-100 transition-opacity duration-300 rounded-xl"></div>
                <i class="fas fa-paper-plane mr-2 relative z-10"></i>
                <span class="relative z-10 font-medium">Send Invoice</span>
            </button>
        </div>
    </div>
</div>

<!-- Email Receipt Confirmation Modal -->
<div id="emailReceiptModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4 animate__animated animate__fadeIn">
    <div class="bg-white rounded-2xl shadow-2xl max-w-md w-full max-h-[90vh] overflow-y-auto animate__animated animate__zoomIn">
        <!-- Modal Header -->
        <div class="bg-gradient-to-r from-orange-600 to-orange-700 p-6 text-white rounded-t-2xl">
            <div class="flex items-center justify-between">
                <h3 class="text-xl font-bold flex items-center">
                    <i class="fas fa-receipt mr-2"></i>Send Receipt Email
                </h3>
                <button type="button" onclick="closeEmailReceiptModal()" class="text-white hover:text-gray-200 text-2xl">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        </div>

        <!-- Modal Body -->
        <div class="p-6 text-gray-700">
            <div class="text-center mb-4">
                <div class="w-16 h-16 bg-orange-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-receipt text-2xl text-orange-600"></i>
                </div>
                <h4 class="text-lg font-semibold text-gray-900 mb-2">Send Receipt to Customer</h4>
                <p class="text-sm text-gray-600 mb-4">
                    Are you sure you want to send the receipt for order <strong>#<?= htmlspecialchars($order['order_number']) ?></strong> to the customer via email?
                </p>
                <div class="bg-gray-50 rounded-lg p-3 mb-4 text-left">
                    <div class="text-sm">
                        <div class="flex justify-between mb-1">
                            <span class="text-gray-600">Customer:</span>
                            <span class="font-medium text-gray-900"><?php echo htmlspecialchars($order['customer_name']); ?></span>
                        </div>
                        <div class="flex justify-between mb-1">
                            <span class="text-gray-600">Email:</span>
                            <span class="font-medium text-gray-900"><?php echo htmlspecialchars($order['customer_email']); ?></span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600">Total Amount:</span>
                            <span class="font-bold text-red-600">KES <?php echo number_format($order['total_amount'] ?? 0, 2); ?></span>
                        </div>
                    </div>
                </div>
                <p class="text-xs text-gray-500">This will send a compact receipt email with order summary and payment details.</p>
            </div>
        </div>

        <!-- Modal Footer -->
        <div class="p-6 border-t border-gray-200 flex gap-3 justify-end">
            <button type="button" onclick="closeEmailReceiptModal()"
                    class="group relative bg-gray-500 hover:bg-gray-600 text-white px-6 py-3 rounded-xl font-semibold transition-all duration-300 flex items-center justify-center shadow-lg hover:shadow-xl transform hover:-translate-y-0.5">
                <div class="absolute inset-0 bg-gradient-to-r from-white/0 via-white/10 to-white/0 opacity-0 group-hover:opacity-100 transition-opacity duration-300 rounded-xl"></div>
                <span class="relative z-10 font-medium">Cancel</span>
            </button>
            <button type="button" onclick="confirmEmailReceipt()"
                    class="group relative bg-gradient-to-r from-orange-600 to-orange-700 hover:from-orange-700 hover:to-orange-800 text-white px-6 py-3 rounded-xl font-semibold transition-all duration-300 flex items-center justify-center shadow-lg hover:shadow-xl transform hover:-translate-y-0.5">
                <div class="absolute inset-0 bg-gradient-to-r from-white/0 via-white/10 to-white/0 opacity-0 group-hover:opacity-100 transition-opacity duration-300 rounded-xl"></div>
                <i class="fas fa-paper-plane mr-2 relative z-10"></i>
                <span class="relative z-10 font-medium">Send Receipt</span>
            </button>
        </div>
    </div>
</div>

<!-- Success Message Modal -->
<div id="successModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-[60] p-4 animate__animated animate__fadeIn">
    <div class="bg-white rounded-2xl shadow-2xl max-w-md w-full max-h-[90vh] overflow-y-auto animate__animated animate__zoomIn">
        <!-- Modal Header -->
        <div class="bg-gradient-to-r from-green-600 to-green-700 p-6 text-white rounded-t-2xl">
            <div class="flex items-center justify-between">
                <h3 class="text-xl font-bold flex items-center">
                    <i class="fas fa-check-circle mr-2"></i>Success!
                </h3>
                <button type="button" onclick="closeSuccessModal()" class="text-white hover:text-gray-200 text-2xl">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        </div>

        <!-- Modal Body -->
        <div class="p-6 text-gray-700">
            <div class="text-center mb-4">
                <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-check-circle text-2xl text-green-600"></i>
                </div>
                <h4 class="text-lg font-semibold text-gray-900 mb-2" id="successTitle">Email Sent Successfully!</h4>
                <p class="text-sm text-gray-600" id="successMessage">
                    The email has been sent to the customer successfully.
                </p>
            </div>
        </div>

        <!-- Modal Footer -->
        <div class="p-6 border-t border-gray-200 flex gap-3 justify-center">
            <button type="button" onclick="closeSuccessModal()"
                    class="group relative bg-gradient-to-r from-green-600 to-green-700 hover:from-green-700 hover:to-green-800 text-white px-8 py-3 rounded-xl font-semibold transition-all duration-300 flex items-center justify-center shadow-lg hover:shadow-xl transform hover:-translate-y-0.5">
                <div class="absolute inset-0 bg-gradient-to-r from-white/0 via-white/10 to-white/0 opacity-0 group-hover:opacity-100 transition-opacity duration-300 rounded-xl"></div>
                <i class="fas fa-check mr-2 relative z-10"></i>
                <span class="relative z-10 font-medium">Okay</span>
            </button>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div id="deleteModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
    <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
        <div class="mt-3 text-center">
            <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-orange-100 mb-4">
                <i class="fas fa-exclamation-triangle text-orange-600 text-xl"></i>
            </div>
            <h3 class="text-lg font-medium text-gray-900 mb-2">Delete Order</h3>
            <div class="mt-2 px-7 py-3">
                <p class="text-sm text-gray-500">
                    Are you sure you want to delete order <span id="deleteOrderNumber" class="font-semibold"></span>?
                    This action cannot be undone.
                </p>
            </div>
            <div class="items-center px-4 py-3 flex justify-center space-x-3">
                <button id="confirmDeleteBtn" class="px-4 py-2 bg-primary text-white text-base font-medium rounded-md shadow-sm hover:bg-primary-dark focus:outline-none focus:ring-2 focus:ring-primary">
                    Delete Order
                </button>
                <button onclick="closeDeleteModal()" class="px-4 py-2 bg-gray-300 text-gray-700 text-base font-medium rounded-md shadow-sm hover:bg-gray-400 focus:outline-none focus:ring-2 focus:ring-gray-300">
                    Cancel
                </button>
            </div>
        </div>
    </div>
</div>

<script>
// Delete confirmation functions
function confirmDelete(orderId, orderNumber) {
    document.getElementById('deleteOrderNumber').textContent = orderNumber;
    document.getElementById('confirmDeleteBtn').onclick = function() {
        window.location.href = 'orders.php?_method=DELETE&id=' + orderId;
    };
    document.getElementById('deleteModal').classList.remove('hidden');
}

function closeDeleteModal() {
    document.getElementById('deleteModal').classList.add('hidden');
}

// Print order functionality
function printOrder() {
    window.print();
}

// Generate invoice functionality
function generateInvoice() {
    // Create invoice data
    const invoiceData = {
        orderId: <?= $order['id'] ?>,
        orderNumber: '<?= $order['order_number'] ?>',
        customerName: '<?= htmlspecialchars($order['customer_name']) ?>',
        customerEmail: '<?= htmlspecialchars($order['customer_email']) ?>',
        customerPhone: '<?= htmlspecialchars($order['customer_phone']) ?>',
        orderDate: '<?= date('M j, Y \a\t g:i A', strtotime($order['created_at'])) ?>',
        deliveryAddress: '<?= htmlspecialchars($order['delivery_address']) ?>',
        paymentMethod: '<?= ucfirst(str_replace('_', ' ', $order['payment_method'])) ?>',
        paymentStatus: '<?= ucfirst($order['payment_status'] ?? 'pending') ?>',
        status: '<?= ucfirst($order['status']) ?>',
        subtotal: <?= $order['subtotal'] ?? 0 ?>,
        deliveryFee: <?= $order['delivery_fee'] ?? 0 ?>,
        totalAmount: <?= $order['total_amount'] ?? 0 ?>,
        items: [
            <?php
            $item_names = !empty($order['item_names']) ? explode('|||', $order['item_names']) : [];
            $item_quantities = !empty($order['item_quantities']) ? explode('|||', $order['item_quantities']) : [];
            $item_prices = !empty($order['item_prices']) ? explode('|||', $order['item_prices']) : [];
            $item_totals = !empty($order['item_totals']) ? explode('|||', $order['item_totals']) : [];

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

    // Open invoice in new window and print
    const invoiceWindow = window.open('', '_blank', 'width=800,height=600');
    invoiceWindow.document.write(generateInvoiceHTML(invoiceData));
    invoiceWindow.document.close();
    invoiceWindow.print();
}

// Generate receipt functionality
function generateReceipt() {
    // Create receipt data
    const receiptData = {
        orderId: <?= $order['id'] ?>,
        orderNumber: '<?= $order['order_number'] ?>',
        customerName: '<?= htmlspecialchars($order['customer_name']) ?>',
        orderDate: '<?= date('M j, Y \a\t g:i A', strtotime($order['created_at'])) ?>',
        status: '<?= ucfirst($order['status']) ?>',
        paymentMethod: '<?= ucfirst(str_replace('_', ' ', $order['payment_method'])) ?>',
        paymentStatus: '<?= ucfirst($order['payment_status'] ?? 'pending') ?>',
        totalAmount: <?= $order['total_amount'] ?? 0 ?>,
        subtotal: <?= $order['subtotal'] ?? 0 ?>,
        deliveryFee: <?= $order['delivery_fee'] ?? 0 ?>,
        items: [
            <?php
            $item_names = !empty($order['item_names']) ? explode('|||', $order['item_names']) : [];
            $item_quantities = !empty($order['item_quantities']) ? explode('|||', $order['item_quantities']) : [];
            $item_prices = !empty($order['item_prices']) ? explode('|||', $order['item_prices']) : [];
            $item_totals = !empty($order['item_totals']) ? explode('|||', $order['item_totals']) : [];

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

    // Open receipt in new window and print
    const receiptWindow = window.open('', '_blank', 'width=400,height=600');
    receiptWindow.document.write(generateReceiptHTML(receiptData));
    receiptWindow.document.close();
    receiptWindow.print();
}

// Email invoice functionality
function emailInvoice() {
    document.getElementById('emailInvoiceModal').classList.remove('hidden');
    document.getElementById('emailInvoiceModal').classList.add('animate__fadeIn', 'animate__zoomIn');
}

function closeEmailInvoiceModal() {
    document.getElementById('emailInvoiceModal').classList.add('hidden');
    document.getElementById('emailInvoiceModal').classList.remove('animate__fadeIn', 'animate__zoomIn');
}

function confirmEmailInvoice() {
    closeEmailInvoiceModal();

    const button = document.querySelector('button[onclick="emailInvoice()"]');
    const originalText = button.innerHTML;
    button.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i> Sending...';
    button.disabled = true;

    // Send email via AJAX
    fetch('send_email.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: new URLSearchParams({
            'action': 'send_invoice',
            'order_id': <?= $order['id'] ?>,
            'customer_email': '<?= $order['customer_email'] ?>'
        })
    })
    .then(response => {
        console.log('Response status:', response.status);
        if (!response.ok) {
            throw new Error('HTTP error! status: ' + response.status);
        }
        return response.json();
    })
    .then(data => {
        console.log('Response data:', data);
        if (data.success) {
            showSuccessModal('Invoice Sent Successfully!', 'The invoice email has been sent to the customer successfully.');
        } else {
            showSuccessModal('Error', data.message || 'Unknown error occurred while sending the invoice.');
        }
    })
    .catch(error => {
        console.error('Email sending error:', error);
        showSuccessModal('Error', 'Error sending invoice: ' + error.message);
    })
    .finally(() => {
        // Restore button state
        button.innerHTML = originalText;
        button.disabled = false;
    });
}

// Email receipt functionality
function emailReceipt() {
    document.getElementById('emailReceiptModal').classList.remove('hidden');
    document.getElementById('emailReceiptModal').classList.add('animate__fadeIn', 'animate__zoomIn');
}

function closeEmailReceiptModal() {
    document.getElementById('emailReceiptModal').classList.add('hidden');
    document.getElementById('emailReceiptModal').classList.remove('animate__fadeIn', 'animate__zoomIn');
}

function confirmEmailReceipt() {
    closeEmailReceiptModal();

    const button = document.querySelector('button[onclick="emailReceipt()"]');
    const originalText = button.innerHTML;
    button.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i> Sending...';
    button.disabled = true;

    // Send email via AJAX
    fetch('send_email.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: new URLSearchParams({
            'action': 'send_receipt',
            'order_id': <?= $order['id'] ?>,
            'customer_email': '<?= $order['customer_email'] ?>'
        })
    })
    .then(response => {
        console.log('Response status:', response.status);
        if (!response.ok) {
            throw new Error('HTTP error! status: ' + response.status);
        }
        return response.json();
    })
    .then(data => {
        console.log('Response data:', data);
        if (data.success) {
            showSuccessModal('Receipt Sent Successfully!', 'The receipt email has been sent to the customer successfully.');
        } else {
            showSuccessModal('Error', data.message || 'Unknown error occurred while sending the receipt.');
        }
    })
    .catch(error => {
        console.error('Email sending error:', error);
        showSuccessModal('Error', 'Error sending receipt: ' + error.message);
    })
    .finally(() => {
        // Restore button state
        button.innerHTML = originalText;
        button.disabled = false;
    });
}

// Success modal functions
function showSuccessModal(title, message) {
    document.getElementById('successTitle').textContent = title;
    document.getElementById('successMessage').textContent = message;

    // Change modal styling based on success or error
    const modal = document.getElementById('successModal');
    const header = modal.querySelector('.bg-gradient-to-r');
    const icon = modal.querySelector('.fas');
    const button = modal.querySelector('button');

    if (title.toLowerCase().includes('error') || title.toLowerCase().includes('failed')) {
        // Error styling
        header.className = 'bg-gradient-to-r from-red-600 to-red-700 p-6 text-white rounded-t-2xl';
        icon.className = 'fas fa-exclamation-triangle text-2xl text-red-600';
        button.className = 'group relative bg-gradient-to-r from-red-600 to-red-700 hover:from-red-700 hover:to-red-800 text-white px-8 py-3 rounded-xl font-semibold transition-all duration-300 flex items-center justify-center shadow-lg hover:shadow-xl transform hover:-translate-y-0.5';
        button.innerHTML = '<div class="absolute inset-0 bg-gradient-to-r from-white/0 via-white/10 to-white/0 opacity-0 group-hover:opacity-100 transition-opacity duration-300 rounded-xl"></div><i class="fas fa-times mr-2 relative z-10"></i><span class="relative z-10 font-medium">Close</span>';
    } else {
        // Success styling (default)
        header.className = 'bg-gradient-to-r from-green-600 to-green-700 p-6 text-white rounded-t-2xl';
        icon.className = 'fas fa-check-circle text-2xl text-green-600';
        button.className = 'group relative bg-gradient-to-r from-green-600 to-green-700 hover:from-green-700 hover:to-green-800 text-white px-8 py-3 rounded-xl font-semibold transition-all duration-300 flex items-center justify-center shadow-lg hover:shadow-xl transform hover:-translate-y-0.5';
        button.innerHTML = '<div class="absolute inset-0 bg-gradient-to-r from-white/0 via-white/10 to-white/0 opacity-0 group-hover:opacity-100 transition-opacity duration-300 rounded-xl"></div><i class="fas fa-check mr-2 relative z-10"></i><span class="relative z-10 font-medium">Okay</span>';
    }

    modal.classList.remove('hidden');
    modal.classList.add('animate__fadeIn', 'animate__zoomIn');
}

function closeSuccessModal() {
    document.getElementById('successModal').classList.add('hidden');
    document.getElementById('successModal').classList.remove('animate__fadeIn', 'animate__zoomIn');
}

// Generate receipt HTML (compact version)
function generateReceiptHTML(data) {
    return `
        <!DOCTYPE html>
        <html>
        <head>
            <title>Receipt #${data.orderNumber}</title>
            <meta charset="utf-8">
            <style>
                body { font-family: 'Courier New', monospace; margin: 10px; color: #333; font-size: 11px; line-height: 1.3; }
                .receipt-header { text-align: center; margin-bottom: 15px; border-bottom: 2px solid #000; padding-bottom: 8px; }
                .receipt-title { font-size: 18px; font-weight: bold; margin: 0; letter-spacing: 2px; }
                .company-name { font-size: 14px; margin: 3px 0; font-weight: bold; }
                .receipt-info { margin: 8px 0; }
                .info-line { margin: 2px 0; }
                .items-section { margin: 10px 0; border-top: 1px dashed #000; padding-top: 8px; }
                .item-row { display: flex; justify-content: space-between; margin: 3px 0; padding: 2px 0; }
                .item-name { flex: 1; }
                .item-details { text-align: right; }
                .subtotal-section { margin: 8px 0; padding: 5px 0; border-top: 1px dashed #000; }
                .total-section { margin-top: 10px; padding-top: 8px; border-top: 2px solid #000; font-weight: bold; font-size: 14px; }
                .grand-total { font-size: 16px; font-weight: bold; }
                .footer { text-align: center; margin-top: 15px; font-size: 9px; border-top: 1px solid #ccc; padding-top: 8px; }
                .payment-info { margin: 8px 0; padding: 5px; background-color: #f5f5f5; }
                @media print { body { margin: 0; } }
            </style>
        </head>
        <body>
            <div class="receipt-header">
                <div style="display: flex; align-items: center; justify-content: center; margin-bottom: 10px;">
                    <img src="../assets/img/Addin-logo.jpeg" alt="Addins Logo" style="max-width: 60px; max-height: 40px; margin-right: 10px;">
                    <div class="receipt-title">RECEIPT</div>
                </div>
                <div class="company-name">Addins Meals on Wheels</div>
                <div>Order #${data.orderNumber}</div>
            </div>

            <div class="receipt-info">
                <div class="info-line"><strong>Customer:</strong> ${data.customerName}</div>
                <div class="info-line"><strong>Date:</strong> ${data.orderDate}</div>
                <div class="info-line"><strong>Status:</strong> ${data.status}</div>
            </div>

            <div class="items-section">
                <div><strong>Order Items:</strong></div>
                ${data.items.map(item => `
                    <div class="item-row">
                        <div class="item-name">${item.name} x${item.quantity}</div>
                        <div class="item-details">KES ${item.total.toFixed(2)}</div>
                    </div>
                `).join('')}
            </div>

            <div class="subtotal-section">
                <div class="item-row">
                    <span>Subtotal:</span>
                    <span>KES ${data.subtotal.toFixed(2)}</span>
                </div>
                <div class="item-row">
                    <span>Delivery Fee:</span>
                    <span>${data.deliveryFee === 0 ? 'FREE' : 'KES ' + data.deliveryFee.toFixed(2)}</span>
                </div>
            </div>

            <div class="total-section">
                <div class="item-row">
                    <span><strong>TOTAL:</strong></span>
                    <span class="grand-total">KES ${data.totalAmount.toFixed(2)}</span>
                </div>
            </div>

            <div class="payment-info">
                <div><strong>Payment:</strong> ${data.paymentMethod}</div>
                <div><strong>Status:</strong> ${data.paymentStatus}</div>
            </div>

            <div class="footer">
                <div>Thank you for your order!</div>
                <div>Addins Meals on Wheels</div>
                <div>📞 +254 112 855 900</div>
                <div>Generated: ${new Date().toLocaleDateString()}</div>
            </div>
        </body>
        </html>
    `;
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
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    margin-bottom: 40px;
                    padding: 20px;
                }

                .company-logo {
                    max-width: 120px;
                    max-height: 80px;
                    margin-right: 30px;
                    filter: brightness(0) invert(1);
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

                tbody tr:hover {
                    background-color: #e8f4f8;
                    transition: background-color 0.3s ease;
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

                @media print {
                    body { margin: 0; }
                    .invoice-container {
                        box-shadow: none;
                        padding: 20px;
                    }
                    .no-print { display: none; }
                }

                @media (max-width: 768px) {
                    .invoice-container { padding: 20px; }
                    .invoice-details { grid-template-columns: 1fr; gap: 20px; }
                    .company-section { flex-direction: column; text-align: center; }
                    .company-logo { margin-right: 0; margin-bottom: 20px; }
                }
            </style>
        </head>
        <body>
            <div class="invoice-container">
                <div class="invoice-header">
                    <h1 class="invoice-title">Invoice</h1>
                    <p class="invoice-subtitle">Order #${data.orderNumber}</p>
                </div>

                <div class="company-section">
                    <img src="../assets/img/Addin-logo.jpeg" alt="Addins Meals on Wheels Logo" class="company-logo">
                    <div class="company-info">
                        <h2>Addins Meals on Wheels</h2>
                        <p class="tagline">Delicious Food Delivery Service</p>
                        <p class="contact">📞 +254 112 855 900 | 📧 info@addinsmeals.com</p>
                    </div>
                </div>

                <div class="invoice-details">
                    <div class="detail-section">
                        <div class="detail-title">Bill To</div>
                        <div class="detail-item">
                            <span class="detail-label">Customer:</span>
                            <span class="detail-value">${data.customerName}</span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Email:</span>
                            <span class="detail-value">${data.customerEmail}</span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Phone:</span>
                            <span class="detail-value">${data.customerPhone}</span>
                        </div>
                    </div>

                    <div class="detail-section">
                        <div class="detail-title">Order Details</div>
                        <div class="detail-item">
                            <span class="detail-label">Order Number:</span>
                            <span class="detail-value">${data.orderNumber}</span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Order Date:</span>
                            <span class="detail-value">${data.orderDate}</span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Status:</span>
                            <span class="detail-value">${data.status}</span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Payment Method:</span>
                            <span class="detail-value">${data.paymentMethod}</span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Payment Status:</span>
                            <span class="detail-value">${data.paymentStatus}</span>
                        </div>
                    </div>
                </div>

                <div class="delivery-info">
                    <div class="delivery-title">Delivery Information</div>
                    <div class="delivery-address">${data.deliveryAddress}</div>
                </div>

                <div class="items-section">
                    <div class="items-title">Order Items</div>
                    <table>
                        <thead>
                            <tr>
                                <th>Description</th>
                                <th class="text-center">Qty</th>
                                <th class="text-right">Unit Price</th>
                                <th class="text-right">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${data.items.map(item => `
                                <tr>
                                    <td>${item.name}</td>
                                    <td class="text-center">${item.quantity}</td>
                                    <td class="text-right">KES ${item.unitPrice.toFixed(2)}</td>
                                    <td class="text-right">KES ${item.total.toFixed(2)}</td>
                                </tr>
                            `).join('')}
                        </tbody>
                    </table>
                </div>

                <div class="totals-section">
                    <table class="totals-table">
                        <tr>
                            <td>Subtotal:</td>
                            <td class="text-right">KES ${data.subtotal.toFixed(2)}</td>
                        </tr>
                        <tr>
                            <td>Delivery Fee:</td>
                            <td class="text-right">${data.deliveryFee === 0 ? 'FREE' : 'KES ' + data.deliveryFee.toFixed(2)}</td>
                        </tr>
                        <tr class="total-row">
                            <td><strong>Total Amount:</strong></td>
                            <td class="text-right"><strong>KES ${data.totalAmount.toFixed(2)}</strong></td>
                        </tr>
                    </table>
                </div>

                <div class="footer">
                    <div class="thank-you">Thank you for choosing Addins Meals on Wheels!</div>
                    <p class="footer-text">We appreciate your business and look forward to serving you again.</p>
                    <p class="footer-subtext">This invoice was generated on ${new Date().toLocaleDateString()} | For inquiries, please contact us at +254 112 855 900</p>
                </div>
            </div>
        </body>
        </html>
    `;
}

// Close modals when clicking outside
document.getElementById('emailInvoiceModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeEmailInvoiceModal();
    }
});

document.getElementById('emailReceiptModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeEmailReceiptModal();
    }
});

document.getElementById('successModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeSuccessModal();
    }
});

// Delete confirmation functions (existing)
function confirmDelete(orderId, orderNumber) {
    document.getElementById('deleteOrderNumber').textContent = orderNumber;
    document.getElementById('confirmDeleteBtn').onclick = function() {
        window.location.href = 'orders.php?_method=DELETE&id=' + orderId;
    };
    document.getElementById('deleteModal').classList.remove('hidden');
}

function closeDeleteModal() {
    document.getElementById('deleteModal').classList.add('hidden');
}

// Close modal when clicking outside (existing)
document.getElementById('deleteModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeDeleteModal();
    }
});
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
        background: linear-gradient(135deg, #fc7703 0%, #D4AF37 100%) !important;
        -webkit-print-color-adjust: exact;
    }
}
</style>

<?php include 'includes/footer.php'; ?>
