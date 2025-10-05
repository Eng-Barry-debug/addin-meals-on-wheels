<?php
require_once 'includes/config.php';

// Initialize variables
$order = null;
$error = '';
$order_id = null;

// Check if order_id or order_number is provided
if (isset($_GET['order_id']) && is_numeric($_GET['order_id'])) {
    $order_id = (int)$_GET['order_id'];
} elseif (isset($_GET['order_number']) && !empty($_GET['order_number'])) {
    $order_number = trim($_GET['order_number']);

    // Find order by order number
    try {
        $stmt = $pdo->prepare("SELECT id FROM orders WHERE order_number = :order_number");
        $stmt->execute([':order_number' => $order_number]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($result) {
            $order_id = $result['id'];
        }
    } catch (PDOException $e) {
        $error = 'Error finding order. Please try again.';
    }
}

// If no valid order identifier provided, show tracking form
if (!$order_id) {
    $page_title = 'Order Tracking - Addins Meals on Wheels';
    include 'includes/header.php';
    ?>

    <!-- Order Tracking Hero -->
    <section class="relative h-80 overflow-hidden bg-gradient-to-br from-blue-600 via-blue-700 to-blue-800">
        <div class="absolute inset-0 bg-black/20"></div>
        <div class="relative z-10 h-full flex items-center justify-center">
            <div class="container mx-auto px-4 text-center text-white">
                <div class="max-w-3xl mx-auto">
                    <h1 class="text-4xl md:text-5xl font-bold mb-6">Track Your Order</h1>
                    <p class="text-xl mb-8">Enter your order number to check the status and progress of your delivery</p>

                    <!-- Order Tracking Form -->
                    <div class="max-w-md mx-auto">
                        <form method="GET" action="" class="bg-white/10 backdrop-blur-sm rounded-xl p-6">
                            <div class="mb-4">
                                <label for="order_number" class="block text-sm font-medium mb-2">Order Number</label>
                                <input type="text" id="order_number" name="order_number"
                                       placeholder="e.g., ORD-20231004-ABCD"
                                       class="w-full px-4 py-3 bg-white/20 border border-white/30 rounded-lg text-white placeholder-white/70 focus:outline-none focus:ring-2 focus:ring-white/50 focus:border-transparent"
                                       required>
                            </div>
                            <button type="submit" class="w-full bg-white text-blue-600 font-bold py-3 px-6 rounded-lg hover:bg-gray-100 transition-colors">
                                Track Order
                            </button>
                        </form>

                        <div class="mt-6 text-sm text-blue-100">
                            <p>Don't have your order number? <a href="/account/orders.php" class="underline hover:text-white">View your orders</a></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Alternative Tracking Options -->
    <section class="py-16 bg-light">
        <div class="container mx-auto px-4">
            <div class="max-w-4xl mx-auto text-center">
                <h2 class="text-3xl font-bold mb-8">Other Ways to Track</h2>
                <div class="grid md:grid-cols-3 gap-8">
                    <div class="bg-white rounded-lg shadow-md p-6">
                        <div class="w-16 h-16 bg-primary rounded-full flex items-center justify-center mx-auto mb-4">
                            <i class="fas fa-user-circle text-white text-2xl"></i>
                        </div>
                        <h3 class="text-xl font-bold mb-3">My Account</h3>
                        <p class="text-gray-600 mb-4">View all your orders and track their progress</p>
                        <a href="/account/orders.php" class="text-primary font-semibold hover:text-primary-dark">View Orders →</a>
                    </div>

                    <div class="bg-white rounded-lg shadow-md p-6">
                        <div class="w-16 h-16 bg-secondary rounded-full flex items-center justify-center mx-auto mb-4">
                            <i class="fas fa-phone text-white text-2xl"></i>
                        </div>
                        <h3 class="text-xl font-bold mb-3">Call Us</h3>
                        <p class="text-gray-600 mb-4">Speak directly with our customer service team</p>
                        <a href="tel:+254700123456" class="text-primary font-semibold hover:text-primary-dark">+254 700 123 456</a>
                    </div>

                    <div class="bg-white rounded-lg shadow-md p-6">
                        <div class="w-16 h-16 bg-accent rounded-full flex items-center justify-center mx-auto mb-4">
                            <i class="fas fa-comments text-white text-2xl"></i>
                        </div>
                        <h3 class="text-xl font-bold mb-3">Live Chat</h3>
                        <p class="text-gray-600 mb-4">Get instant help with your order status</p>
                        <a href="/chat.php" class="text-primary font-semibold hover:text-primary-dark">Start Chat →</a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <?php
    include 'includes/footer.php';
    exit;
}

// Fetch order details
try {
    $stmt = $pdo->prepare("
        SELECT o.*, oi.*, mi.name as menu_item_name, mi.image as menu_item_image
        FROM orders o
        LEFT JOIN order_items oi ON o.id = oi.order_id
        LEFT JOIN menu_items mi ON oi.menu_item_id = mi.id
        WHERE o.id = :order_id
        ORDER BY oi.id ASC
    ");
    $stmt->execute([':order_id' => $order_id]);
    $orderData = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($orderData)) {
        $error = 'Order not found. Please check your order number and try again.';
    } else {
        // Group order items
        $order = [
            'id' => $orderData[0]['id'],
            'order_number' => $orderData[0]['order_number'],
            'customer_name' => $orderData[0]['customer_name'],
            'customer_email' => $orderData[0]['customer_email'],
            'customer_phone' => $orderData[0]['customer_phone'],
            'delivery_address' => $orderData[0]['delivery_address'],
            'delivery_instructions' => $orderData[0]['delivery_instructions'],
            'subtotal' => $orderData[0]['subtotal'],
            'delivery_fee' => $orderData[0]['delivery_fee'],
            'total' => $orderData[0]['total'],
            'payment_method' => $orderData[0]['payment_method'],
            'status' => $orderData[0]['status'],
            'created_at' => $orderData[0]['created_at'],
            'items' => []
        ];

        foreach ($orderData as $row) {
            if ($row['menu_item_id']) {
                $order['items'][] = [
                    'id' => $row['id'],
                    'menu_item_id' => $row['menu_item_id'],
                    'item_name' => $row['menu_item_name'] ?: $row['item_name'],
                    'quantity' => $row['quantity'],
                    'price' => $row['price'],
                    'total' => $row['total'],
                    'image' => $row['menu_item_image']
                ];
            }
        }
    }

} catch (PDOException $e) {
    $error = 'Error loading order details. Please try again later.';
    error_log('Order tracking error: ' . $e->getMessage());
}

$page_title = 'Order Tracking - ' . ($order['order_number'] ?? "Order #$order_id");
include 'includes/header.php';
?>

<!-- Order Tracking Page -->
<section class="py-12 bg-light">
    <div class="container mx-auto px-4">
        <div class="max-w-4xl mx-auto">
            <?php if ($error): ?>
                <!-- Error State -->
                <div class="bg-white rounded-2xl shadow-lg p-12 text-center">
                    <div class="max-w-md mx-auto">
                        <i class="fas fa-search text-6xl text-gray-300 mb-6"></i>
                        <h2 class="text-2xl font-bold text-gray-700 mb-4">Order Not Found</h2>
                        <p class="text-gray-600 mb-8"><?php echo htmlspecialchars($error); ?></p>

                        <div class="space-y-4">
                            <a href="/order-tracking.php" class="block w-full bg-primary text-white text-center py-3 rounded-lg hover:bg-opacity-90 transition-colors font-semibold">
                                Try Another Order Number
                            </a>
                            <a href="/account/orders.php" class="block w-full border border-primary text-primary text-center py-3 rounded-lg hover:bg-primary hover:text-white transition-colors font-semibold">
                                View My Orders
                            </a>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <!-- Order Tracking Header -->
                <div class="bg-white rounded-t-2xl shadow-lg p-6 border-b border-gray-200">
                    <div class="flex flex-col md:flex-row md:items-center md:justify-between">
                        <div>
                            <h1 class="text-2xl md:text-3xl font-bold text-dark mb-2">
                                Order #<?php echo htmlspecialchars($order['order_number'] ?? $order_id); ?>
                            </h1>
                            <p class="text-gray-600">
                                Placed on <?php echo date('F j, Y \a\t g:i A', strtotime($order['created_at'])); ?>
                            </p>
                        </div>
                        <div class="mt-4 md:mt-0">
                            <span class="px-4 py-2 rounded-full text-sm font-semibold <?php
                                echo match($order['status']) {
                                    'pending' => 'bg-yellow-100 text-yellow-800',
                                    'processing' => 'bg-blue-100 text-blue-800',
                                    'confirmed' => 'bg-green-100 text-green-800',
                                    'delivered' => 'bg-purple-100 text-purple-800',
                                    'cancelled' => 'bg-red-100 text-red-800',
                                    default => 'bg-gray-100 text-gray-800'
                                };
                            ?>">
                                <?php echo ucfirst(htmlspecialchars($order['status'])); ?>
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Order Progress Tracker -->
                <div class="bg-white shadow-lg p-6 mb-6">
                    <h2 class="text-xl font-bold text-dark mb-6 text-center">Order Progress</h2>

                    <div class="relative">
                        <!-- Progress Bar -->
                        <div class="flex justify-between items-center mb-4">
                            <?php
                            $statuses = ['pending', 'processing', 'confirmed', 'delivered'];
                            $current_status_index = array_search($order['status'], $statuses);
                            $progress_percentage = (($current_status_index + 1) / count($statuses)) * 100;
                            ?>

                            <div class="w-full bg-gray-200 rounded-full h-3 absolute top-6 left-0">
                                <div class="bg-gradient-to-r from-primary to-secondary h-3 rounded-full transition-all duration-500"
                                     style="width: <?php echo $progress_percentage; ?>%"></div>
                            </div>

                            <?php foreach ($statuses as $index => $status): ?>
                            <div class="flex flex-col items-center relative z-10 <?php echo $index <= $current_status_index ? 'text-primary' : 'text-gray-400'; ?>">
                                <div class="w-12 h-12 rounded-full border-4 flex items-center justify-center mb-2 <?php
                                    echo $index <= $current_status_index ? 'bg-primary border-primary text-white' : 'bg-white border-gray-300';
                                ?>">
                                    <i class="fas fa-<?php
                                        echo match($status) {
                                            'pending' => 'clock',
                                            'processing' => 'cog',
                                            'confirmed' => 'check-circle',
                                            'delivered' => 'truck'
                                        };
                                    ?> text-lg"></i>
                                </div>
                                <span class="text-sm font-medium"><?php echo ucfirst($status); ?></span>
                                <?php if ($index <= $current_status_index): ?>
                                <div class="w-2 h-2 bg-primary rounded-full mt-1"></div>
                                <?php endif; ?>
                            </div>
                            <?php endforeach; ?>
                        </div>

                        <!-- Status Description -->
                        <div class="text-center mt-6 p-4 rounded-lg <?php
                            echo match($order['status']) {
                                'pending' => 'bg-yellow-50 text-yellow-800',
                                'processing' => 'bg-blue-50 text-blue-800',
                                'confirmed' => 'bg-green-50 text-green-800',
                                'delivered' => 'bg-purple-50 text-purple-800',
                                default => 'bg-gray-50 text-gray-800'
                            };
                        ?>">
                            <p class="font-semibold">
                                <?php
                                echo match($order['status']) {
                                    'pending' => 'Your order is being reviewed and will be confirmed shortly.',
                                    'processing' => 'Your order is being prepared by our kitchen team.',
                                    'confirmed' => 'Your order has been confirmed and is ready for delivery.',
                                    'delivered' => 'Your order has been delivered successfully. Thank you for choosing Addins!',
                                    default => 'Order status: ' . ucfirst($order['status'])
                                };
                                ?>
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Order Details -->
                <div class="grid lg:grid-cols-3 gap-6">
                    <!-- Order Summary -->
                    <div class="lg:col-span-2">
                        <div class="bg-white rounded-xl shadow-lg overflow-hidden">
                            <!-- Customer Information -->
                            <div class="p-6 border-b border-gray-200">
                                <h3 class="text-lg font-bold text-dark mb-4 flex items-center">
                                    <i class="fas fa-user-circle mr-2 text-primary"></i>
                                    Customer Information
                                </h3>
                                <div class="grid md:grid-cols-2 gap-4">
                                    <div>
                                        <p class="text-sm text-gray-600">Name</p>
                                        <p class="font-semibold"><?php echo htmlspecialchars($order['customer_name']); ?></p>
                                    </div>
                                    <div>
                                        <p class="text-sm text-gray-600">Phone</p>
                                        <p class="font-semibold"><?php echo htmlspecialchars($order['customer_phone']); ?></p>
                                    </div>
                                </div>
                            </div>

                            <!-- Delivery Information -->
                            <div class="p-6 border-b border-gray-200">
                                <h3 class="text-lg font-bold text-dark mb-4 flex items-center">
                                    <i class="fas fa-map-marker-alt mr-2 text-primary"></i>
                                    Delivery Information
                                </h3>
                                <div class="space-y-3">
                                    <div>
                                        <p class="text-sm text-gray-600">Delivery Address</p>
                                        <p class="font-semibold"><?php echo nl2br(htmlspecialchars($order['delivery_address'])); ?></p>
                                    </div>
                                    <?php if (!empty($order['delivery_instructions'])): ?>
                                    <div>
                                        <p class="text-sm text-gray-600">Special Instructions</p>
                                        <p class="font-semibold"><?php echo nl2br(htmlspecialchars($order['delivery_instructions'])); ?></p>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <!-- Order Items -->
                            <div class="p-6">
                                <h3 class="text-lg font-bold text-dark mb-4 flex items-center">
                                    <i class="fas fa-shopping-bag mr-2 text-primary"></i>
                                    Order Items
                                </h3>

                                <div class="space-y-4">
                                    <?php foreach ($order['items'] as $item): ?>
                                    <div class="flex items-center justify-between py-3 border-b border-gray-100 last:border-0">
                                        <div class="flex items-center space-x-4">
                                            <?php if (!empty($item['image'])): ?>
                                                <img src="<?php echo htmlspecialchars($item['image']); ?>"
                                                     alt="<?php echo htmlspecialchars($item['item_name']); ?>"
                                                     class="w-12 h-12 rounded-lg object-cover">
                                            <?php endif; ?>
                                            <div>
                                                <h4 class="font-semibold text-gray-900"><?php echo htmlspecialchars($item['item_name']); ?></h4>
                                                <p class="text-sm text-gray-600">Qty: <?php echo $item['quantity']; ?> × KES <?php echo number_format($item['price'], 2); ?></p>
                                            </div>
                                        </div>
                                        <div class="text-right">
                                            <p class="font-semibold text-gray-900">KES <?php echo number_format($item['total'], 2); ?></p>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                </div>

                                <!-- Order Totals -->
                                <div class="border-t border-gray-200 mt-6 pt-6 space-y-3">
                                    <div class="flex justify-between">
                                        <span class="text-gray-600">Subtotal</span>
                                        <span class="font-medium">KES <?php echo number_format($order['subtotal'], 2); ?></span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-gray-600">Delivery Fee</span>
                                        <span class="font-medium <?php echo $order['delivery_fee'] === 0 ? 'text-green-600' : ''; ?>">
                                            <?php echo $order['delivery_fee'] === 0 ? 'Free' : 'KES ' . number_format($order['delivery_fee'], 2); ?>
                                        </span>
                                    </div>
                                    <div class="border-t border-gray-200 pt-3">
                                        <div class="flex justify-between text-xl font-bold">
                                            <span>Total</span>
                                            <span class="text-primary">KES <?php echo number_format($order['total'], 2); ?></span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Order Actions & Status -->
                    <div class="space-y-6">
                        <!-- Current Status Card -->
                        <div class="bg-white rounded-xl shadow-lg p-6">
                            <h3 class="text-lg font-bold text-dark mb-4 text-center">Current Status</h3>
                            <div class="text-center">
                                <div class="w-16 h-16 mx-auto mb-4 rounded-full flex items-center justify-center <?php
                                    echo match($order['status']) {
                                        'pending' => 'bg-yellow-100 text-yellow-600',
                                        'processing' => 'bg-blue-100 text-blue-600',
                                        'confirmed' => 'bg-green-100 text-green-600',
                                        'delivered' => 'bg-purple-100 text-purple-600',
                                        default => 'bg-gray-100 text-gray-600'
                                    };
                                ?>">
                                    <i class="fas fa-<?php
                                        echo match($order['status']) {
                                            'pending' => 'clock',
                                            'processing' => 'cog',
                                            'confirmed' => 'check-circle',
                                            'delivered' => 'truck'
                                        };
                                    ?> text-2xl"></i>
                                </div>
                                <h4 class="font-bold text-lg mb-2"><?php echo ucfirst($order['status']); ?></h4>
                                <p class="text-gray-600 text-sm">
                                    <?php
                                    echo match($order['status']) {
                                        'pending' => 'Your order is being reviewed and will be confirmed shortly.',
                                        'processing' => 'Your order is being prepared by our kitchen team.',
                                        'confirmed' => 'Your order has been confirmed and is ready for delivery.',
                                        'delivered' => 'Your order has been delivered successfully. Thank you for choosing Addins!',
                                        default => 'Order status: ' . ucfirst($order['status'])
                                    };
                                    ?>
                                </p>
                            </div>
                        </div>

                        <!-- Quick Actions -->
                        <div class="bg-white rounded-xl shadow-lg p-6">
                            <h3 class="text-lg font-bold text-dark mb-4 text-center">Need Help?</h3>
                            <div class="space-y-3">
                                <a href="/contact.php" class="block w-full bg-primary text-white text-center py-3 rounded-lg hover:bg-opacity-90 transition-colors font-semibold">
                                    <i class="fas fa-comments mr-2"></i>
                                    Contact Support
                                </a>
                                <a href="tel:+254700123456" class="block w-full border border-primary text-primary text-center py-3 rounded-lg hover:bg-primary hover:text-white transition-colors font-semibold">
                                    <i class="fas fa-phone mr-2"></i>
                                    Call Us
                                </a>
                                <a href="/account/orders.php" class="block w-full bg-secondary text-white text-center py-3 rounded-lg hover:bg-opacity-90 transition-colors font-semibold">
                                    <i class="fas fa-list mr-2"></i>
                                    View All Orders
                                </a>
                            </div>
                        </div>

                        <!-- Order Reference -->
                        <div class="bg-gray-50 rounded-xl p-6 text-center">
                            <h4 class="font-bold text-gray-700 mb-2">Order Reference</h4>
                            <p class="text-sm text-gray-600 mb-2"><?php echo htmlspecialchars($order['order_number'] ?? "Order #$order_id"); ?></p>
                            <p class="text-xs text-gray-500">Keep this number for your records</p>
                        </div>
                    </div>
                </div>

                <!-- Estimated Delivery Time (if applicable) -->
                <?php if (in_array($order['status'], ['processing', 'confirmed'])): ?>
                <div class="mt-6 bg-white rounded-xl shadow-lg p-6">
                    <div class="flex items-center justify-center space-x-4">
                        <div class="w-12 h-12 bg-green-100 rounded-full flex items-center justify-center">
                            <i class="fas fa-clock text-green-600"></i>
                        </div>
                        <div class="text-center">
                            <h4 class="font-bold text-gray-700">Estimated Delivery</h4>
                            <p class="text-lg font-semibold text-primary">
                                <?php
                                $delivery_time = strtotime($order['created_at']) + (45 * 60); // 45 minutes from order time
                                echo date('g:i A', $delivery_time);
                                ?>
                            </p>
                            <p class="text-sm text-gray-600">Today</p>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</section>

<!-- Help Section -->
<section class="py-16 bg-primary text-white">
    <div class="container mx-auto px-4 text-center">
        <div class="max-w-3xl mx-auto">
            <h2 class="text-3xl font-bold mb-4">Need Help with Your Order?</h2>
            <p class="text-xl mb-8 text-primary-light">Our customer service team is here to help you with any questions or concerns about your order.</p>

            <div class="grid md:grid-cols-3 gap-8 mb-8">
                <div class="text-center">
                    <div class="w-16 h-16 bg-white/20 rounded-full flex items-center justify-center mx-auto mb-4">
                        <i class="fas fa-phone text-2xl text-white"></i>
                    </div>
                    <h3 class="font-bold mb-2">Call Us</h3>
                    <p class="text-primary-light mb-2">+254 700 123 456</p>
                    <p class="text-sm text-primary-light/80">Mon-Fri, 9AM-6PM</p>
                </div>

                <div class="text-center">
                    <div class="w-16 h-16 bg-white/20 rounded-full flex items-center justify-center mx-auto mb-4">
                        <i class="fas fa-comments text-2xl text-white"></i>
                    </div>
                    <h3 class="font-bold mb-2">Live Chat</h3>
                    <p class="text-primary-light mb-2">Available 24/7</p>
                    <p class="text-sm text-primary-light/80">Instant response</p>
                </div>

                <div class="text-center">
                    <div class="w-16 h-16 bg-white/20 rounded-full flex items-center justify-center mx-auto mb-4">
                        <i class="fas fa-envelope text-2xl text-white"></i>
                    </div>
                    <h3 class="font-bold mb-2">Email Support</h3>
                    <p class="text-primary-light mb-2">orders@addinsmeals.com</p>
                    <p class="text-sm text-primary-light/80">Response within 24 hours</p>
                </div>
            </div>

            <a href="/contact.php" class="inline-flex items-center bg-white text-primary font-bold py-4 px-8 rounded-lg hover:bg-gray-100 transition-colors duration-300 text-lg">
                <i class="fas fa-comments mr-2"></i>
                Get Help Now
            </a>
        </div>
    </div>
</section>

<?php include 'includes/footer.php'; ?>
