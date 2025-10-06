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
<div class="bg-gradient-to-br from-red-600 via-red-700 to-red-800 text-white">
    <div class="container mx-auto px-6 py-8">
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between">
            <div>
                <nav class="flex items-center space-x-2 text-sm mb-4">
                    <a href="orders.php" class="hover:text-red-200 transition-colors">
                        <i class="fas fa-arrow-left mr-1"></i> Back to Orders
                    </a>
                    <span class="text-red-200">/</span>
                    <span>Order #<?= htmlspecialchars($order['order_number']) ?></span>
                </nav>
                <h1 class="text-3xl md:text-4xl font-bold mb-2">Order Details</h1>
                <p class="text-lg opacity-90">Complete order information and management options</p>
            </div>
            <div class="mt-4 lg:mt-0 flex space-x-3">
                <a href="order_edit.php?id=<?= $order['id'] ?>"
                   class="bg-white text-red-600 hover:bg-gray-100 px-6 py-3 rounded-lg font-semibold transition-colors duration-200 flex items-center shadow-lg">
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

<!-- Delete Confirmation Modal -->
<div id="deleteModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
    <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
        <div class="mt-3 text-center">
            <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-red-100 mb-4">
                <i class="fas fa-exclamation-triangle text-red-600 text-xl"></i>
            </div>
            <h3 class="text-lg font-medium text-gray-900 mb-2">Delete Order</h3>
            <div class="mt-2 px-7 py-3">
                <p class="text-sm text-gray-500">
                    Are you sure you want to delete order <span id="deleteOrderNumber" class="font-semibold"></span>?
                    This action cannot be undone.
                </p>
            </div>
            <div class="items-center px-4 py-3 flex justify-center space-x-3">
                <button id="confirmDeleteBtn" class="px-4 py-2 bg-red-600 text-white text-base font-medium rounded-md shadow-sm hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-300">
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
            $item_names = !empty($order['item_names']) ? explode(', ', $order['item_names']) : [];
            $item_quantities = !empty($order['item_quantities']) ? explode(', ', $order['item_quantities']) : [];
            $item_prices = !empty($order['item_prices']) ? explode(', ', $order['item_prices']) : [];
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
            $item_names = !empty($order['item_names']) ? explode(', ', $order['item_names']) : [];
            $item_quantities = !empty($order['item_quantities']) ? explode(', ', $order['item_quantities']) : [];
            $item_prices = !empty($order['item_prices']) ? explode(', ', $order['item_prices']) : [];
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

    // Open receipt in new window and print
    const receiptWindow = window.open('', '_blank', 'width=400,height=600');
    receiptWindow.document.write(generateReceiptHTML(receiptData));
    receiptWindow.document.close();
    receiptWindow.print();
}

// Email invoice functionality
function emailInvoice() {
    const button = document.querySelector('button[onclick="emailInvoice()"]');
    if (confirm('Do you want to send this invoice to the customer via email?')) {
        // Show loading state
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
            console.log('Response headers:', response.headers);
            if (!response.ok) {
                throw new Error('HTTP error! status: ' + response.status);
            }
            return response.json();
        })
        .then(data => {
            console.log('Response data:', data);
            if (data.success) {
                alert('Invoice sent successfully to customer!');
            } else {
                alert('Error sending invoice: ' + (data.message || 'Unknown error'));
            }
        })
        .catch(error => {
            console.error('Email sending error:', error);
            alert('Error sending invoice: ' + error.message);
        })
        .finally(() => {
            // Restore button state
            button.innerHTML = originalText;
            button.disabled = false;
        });
    }
}

// Email receipt functionality
function emailReceipt() {
    const button = document.querySelector('button[onclick="emailReceipt()"]');
    if (confirm('Do you want to send this receipt to the customer via email?')) {
        // Show loading state
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
            console.log('Response headers:', response.headers);
            if (!response.ok) {
                throw new Error('HTTP error! status: ' + response.status);
            }
            return response.json();
        })
        .then(data => {
            console.log('Response data:', data);
            if (data.success) {
                alert('Receipt sent successfully to customer!');
            } else {
                alert('Error sending receipt: ' + (data.message || 'Unknown error'));
            }
        })
        .catch(error => {
            console.error('Email sending error:', error);
            alert('Error sending receipt: ' + error.message);
        })
        .finally(() => {
            // Restore button state
            button.innerHTML = originalText;
            button.disabled = false;
        });
    }
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
                <div class="receipt-title">RECEIPT</div>
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
                body { font-family: Arial, sans-serif; margin: 20px; color: #333; }
                .invoice-header { text-align: center; margin-bottom: 30px; border-bottom: 2px solid #dc2626; padding-bottom: 20px; }
                .invoice-title { color: #dc2626; font-size: 28px; font-weight: bold; margin: 0; }
                .invoice-subtitle { color: #666; font-size: 16px; margin: 5px 0 0 0; }
                .company-info { text-align: center; margin-bottom: 30px; }
                .customer-info, .order-info { margin-bottom: 20px; }
                .info-section { display: inline-block; width: 48%; vertical-align: top; }
                .info-title { font-weight: bold; color: #dc2626; margin-bottom: 10px; }
                .info-item { margin: 5px 0; }
                table { width: 100%; border-collapse: collapse; margin: 20px 0; }
                th, td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
                th { background-color: #f8f9fa; font-weight: bold; color: #333; }
                .text-right { text-align: right; }
                .total-row { font-weight: bold; background-color: #f8f9fa; }
                .grand-total { font-size: 18px; color: #dc2626; font-weight: bold; }
                .footer { text-align: center; margin-top: 40px; padding-top: 20px; border-top: 1px solid #ddd; color: #666; font-size: 12px; }
                @media print { body { margin: 0; } .no-print { display: none; } }
            </style>
        </head>
        <body>
            <div class="invoice-header">
                <h1 class="invoice-title">INVOICE</h1>
                <p class="invoice-subtitle">Order #${data.orderNumber}</p>
            </div>

            <div class="company-info">
                <h2>Addins Meals on Wheels</h2>
                <p>Delicious Food Delivery Service</p>
                <p>📞 +254 112 855 900 | 📧 info@addinsmeals.com</p>
            </div>

            <div style="display: flex; justify-content: space-between; margin: 20px 0;">
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
                    <div class="info-item"><strong>Payment Status:</strong> ${data.paymentStatus}</div>
                </div>
            </div>

            <div class="delivery-info" style="margin: 20px 0; padding: 15px; background-color: #f8f9fa; border-radius: 5px;">
                <div class="info-title">Delivery Information:</div>
                <div><strong>Address:</strong> ${data.deliveryAddress}</div>
            </div>

            <table>
                <thead>
                    <tr>
                        <th>Item</th>
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
                            <td class="text-right">KES ${item.unitPrice.toFixed(2)}</td>
                            <td class="text-right">KES ${item.total.toFixed(2)}</td>
                        </tr>
                    `).join('')}
                </tbody>
            </table>

            <table style="margin-left: auto; width: 300px;">
                <tr>
                    <td>Subtotal:</td>
                    <td class="text-right">KES ${data.subtotal.toFixed(2)}</td>
                </tr>
                <tr>
                    <td>Delivery Fee:</td>
                    <td class="text-right">${data.deliveryFee === 0 ? 'FREE' : 'KES ' + data.deliveryFee.toFixed(2)}</td>
                </tr>
                <tr class="total-row">
                    <td><strong>TOTAL:</strong></td>
                    <td class="text-right grand-total">KES ${data.totalAmount.toFixed(2)}</td>
                </tr>
            </table>

            <div class="footer">
                <p>Thank you for choosing Addins Meals on Wheels!</p>
                <p>This invoice was generated on ${new Date().toLocaleDateString()}</p>
            </div>
        </body>
        </html>
    `;
}

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

// Close modal when clicking outside
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
        background: #dc2626 !important;
        -webkit-print-color-adjust: exact;
    }
}
</style>

<?php include 'includes/footer.php'; ?>
