<?php
require_once 'includes/config.php';

// Check if order_id is provided
if (!isset($_GET['order_id']) || !is_numeric($_GET['order_id'])) {
    header('Location: /');
    exit();
}

$order_id = (int)$_GET['order_id'];

try {
    // Fetch order details
    $stmt = $pdo->prepare("
        SELECT o.*, oi.*, mi.name as menu_item_name, mi.image as menu_item_image
        FROM orders o
        LEFT JOIN order_items oi ON o.id = oi.order_id
        LEFT JOIN menu_items mi ON oi.menu_item_id = mi.id
        WHERE o.id = :order_id AND o.user_id = :user_id
    ");
    $stmt->execute([
        ':order_id' => $order_id,
        ':user_id' => $_SESSION['user_id'] ?? 0
    ]);

    $orderData = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($orderData)) {
        header('Location: /');
        exit();
    }

    // Group order items by order
    $order = null;
    $orderItems = [];

    foreach ($orderData as $row) {
        if (!$order) {
            $order = [
                'id' => $row['id'],
                'order_number' => $row['order_number'],
                'customer_name' => $row['customer_name'],
                'customer_email' => $row['customer_email'],
                'customer_phone' => $row['customer_phone'],
                'delivery_address' => $row['delivery_address'],
                'delivery_instructions' => $row['delivery_instructions'],
                'subtotal' => $row['subtotal'],
                'delivery_fee' => $row['delivery_fee'],
                'total' => $row['total'],
                'payment_method' => $row['payment_method'],
                'status' => $row['status'],
                'created_at' => $row['created_at']
            ];
        }

        if ($row['menu_item_id']) {
            $orderItems[] = [
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

    $page_title = "Order Confirmation - " . ($order['order_number'] ?? "Order #$order_id");

} catch (PDOException $e) {
    error_log("Order confirmation error: " . $e->getMessage());
    header('Location: /');
    exit();
}
?>

<?php include 'includes/header.php'; ?>

<!-- Order Confirmation Hero -->
<section class="relative h-64 overflow-hidden bg-gradient-to-br from-green-600 via-green-700 to-green-800">
    <div class="absolute inset-0 bg-black/20"></div>
    <div class="relative z-10 h-full flex items-center justify-center">
        <div class="container mx-auto px-4 text-center text-white">
            <div class="max-w-3xl mx-auto">
                <div class="mb-6">
                    <i class="fas fa-check-circle text-6xl text-green-300 mb-4"></i>
                    <h1 class="text-4xl md:text-5xl font-bold mb-4">Order Confirmed!</h1>
                    <p class="text-xl text-green-100">Thank you for your order. We've received it and will start preparing your delicious meal.</p>
                </div>

                <?php if (isset($order['order_number'])): ?>
                <div class="inline-block bg-white/20 backdrop-blur-sm rounded-full px-6 py-3">
                    <span class="text-lg font-semibold">Order #<?php echo htmlspecialchars($order['order_number']); ?></span>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<!-- Order Details -->
<section class="py-16 bg-light">
    <div class="container mx-auto px-4">
        <div class="max-w-4xl mx-auto">
            <!-- Order Summary Card -->
            <div class="bg-white rounded-2xl shadow-xl overflow-hidden mb-8">
                <!-- Order Header -->
                <div class="bg-gradient-to-r from-primary to-secondary p-6 text-white">
                    <div class="flex flex-col md:flex-row md:items-center md:justify-between">
                        <div>
                            <h2 class="text-2xl font-bold mb-2">Order Summary</h2>
                            <p class="text-green-100">Placed on <?php echo date('F j, Y \a\t g:i A', strtotime($order['created_at'])); ?></p>
                        </div>
                        <div class="mt-4 md:mt-0">
                            <span class="inline-flex items-center px-4 py-2 rounded-full text-sm font-semibold <?php
                                echo match($order['status']) {
                                    'pending' => 'bg-yellow-100 text-yellow-800',
                                    'processing' => 'bg-blue-100 text-blue-800',
                                    'confirmed' => 'bg-green-100 text-green-800',
                                    'delivered' => 'bg-purple-100 text-purple-800',
                                    default => 'bg-gray-100 text-gray-800'
                                };
                            ?>">
                                <i class="fas fa-circle mr-2 text-xs"></i>
                                <?php echo ucfirst(htmlspecialchars($order['status'])); ?>
                            </span>
                        </div>
                    </div>
                </div>

                <div class="p-6">
                    <!-- Customer Information -->
                    <div class="grid md:grid-cols-2 gap-8 mb-8">
                        <div>
                            <h3 class="text-lg font-bold text-dark mb-4 flex items-center">
                                <i class="fas fa-user-circle mr-2 text-primary"></i>
                                Customer Information
                            </h3>
                            <div class="space-y-3">
                                <div class="flex items-center">
                                    <i class="fas fa-user text-gray-400 mr-3 w-4"></i>
                                    <span><?php echo htmlspecialchars($order['customer_name']); ?></span>
                                </div>
                                <div class="flex items-center">
                                    <i class="fas fa-envelope text-gray-400 mr-3 w-4"></i>
                                    <span><?php echo htmlspecialchars($order['customer_email']); ?></span>
                                </div>
                                <div class="flex items-center">
                                    <i class="fas fa-phone text-gray-400 mr-3 w-4"></i>
                                    <span><?php echo htmlspecialchars($order['customer_phone']); ?></span>
                                </div>
                            </div>
                        </div>

                        <div>
                            <h3 class="text-lg font-bold text-dark mb-4 flex items-center">
                                <i class="fas fa-map-marker-alt mr-2 text-primary"></i>
                                Delivery Information
                            </h3>
                            <div class="space-y-3">
                                <div class="flex items-start">
                                    <i class="fas fa-location-dot text-gray-400 mr-3 w-4 mt-0.5"></i>
                                    <span><?php echo nl2br(htmlspecialchars($order['delivery_address'])); ?></span>
                                </div>
                                <?php if (!empty($order['delivery_instructions'])): ?>
                                <div class="flex items-start">
                                    <i class="fas fa-clipboard-list text-gray-400 mr-3 w-4 mt-0.5"></i>
                                    <span><?php echo nl2br(htmlspecialchars($order['delivery_instructions'])); ?></span>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Order Items -->
                    <div class="border-t pt-6">
                        <h3 class="text-lg font-bold text-dark mb-4 flex items-center">
                            <i class="fas fa-shopping-bag mr-2 text-primary"></i>
                            Order Items
                        </h3>

                        <div class="space-y-4">
                            <?php foreach ($orderItems as $item): ?>
                            <div class="flex items-center justify-between py-4 border-b border-gray-200 last:border-0">
                                <div class="flex items-center space-x-4">
                                    <?php if (!empty($item['image'])): ?>
                                        <img src="<?php echo htmlspecialchars($item['image']); ?>"
                                             alt="<?php echo htmlspecialchars($item['item_name']); ?>"
                                             class="w-16 h-16 rounded-lg object-cover">
                                    <?php endif; ?>
                                    <div>
                                        <h4 class="font-semibold text-gray-900"><?php echo htmlspecialchars($item['item_name']); ?></h4>
                                        <p class="text-sm text-gray-600">Quantity: <?php echo $item['quantity']; ?> × KES <?php echo number_format($item['price'], 2); ?></p>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <p class="font-semibold text-gray-900">KES <?php echo number_format($item['total'], 2); ?></p>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>

                        <!-- Order Totals -->
                        <div class="border-t border-gray-300 mt-6 pt-6 space-y-3">
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
                            <div class="border-t border-gray-300 pt-3">
                                <div class="flex justify-between text-xl font-bold">
                                    <span>Total</span>
                                    <span class="text-primary">KES <?php echo number_format($order['total'], 2); ?></span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- What's Next Section -->
            <div class="grid md:grid-cols-3 gap-6 mb-8">
                <div class="bg-white rounded-lg shadow-md p-6 text-center">
                    <div class="w-16 h-16 bg-blue-100 rounded-full flex items-center justify-center mx-auto mb-4">
                        <i class="fas fa-clock text-blue-600 text-2xl"></i>
                    </div>
                    <h3 class="font-bold text-lg mb-2">Order Processing</h3>
                    <p class="text-gray-600 text-sm">We'll start preparing your order within 15 minutes</p>
                </div>

                <div class="bg-white rounded-lg shadow-md p-6 text-center">
                    <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
                        <i class="fas fa-truck text-green-600 text-2xl"></i>
                    </div>
                    <h3 class="font-bold text-lg mb-2">Fast Delivery</h3>
                    <p class="text-gray-600 text-sm">Your meal will be delivered hot and fresh to your doorstep</p>
                </div>

                <div class="bg-white rounded-lg shadow-md p-6 text-center">
                    <div class="w-16 h-16 bg-purple-100 rounded-full flex items-center justify-center mx-auto mb-4">
                        <i class="fas fa-phone text-purple-600 text-2xl"></i>
                    </div>
                    <h3 class="font-bold text-lg mb-2">Live Updates</h3>
                    <p class="text-gray-600 text-sm">We'll send you SMS updates about your order status</p>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="text-center space-y-4">
                <div class="flex flex-col sm:flex-row gap-4 justify-center">
                    <a href="/menu.php" class="inline-flex items-center justify-center px-8 py-3 bg-primary text-white font-bold rounded-lg hover:bg-opacity-90 transition-colors">
                        <i class="fas fa-utensils mr-2"></i>
                        Order Again
                    </a>
                    <a href="/account/orders.php" class="inline-flex items-center justify-center px-8 py-3 bg-secondary text-white font-bold rounded-lg hover:bg-opacity-90 transition-colors">
                        <i class="fas fa-list mr-2"></i>
                        View All Orders
                    </a>
                    <a href="/contact.php" class="inline-flex items-center justify-center px-8 py-3 border-2 border-primary text-primary font-bold rounded-lg hover:bg-primary hover:text-white transition-colors">
                        <i class="fas fa-comments mr-2"></i>
                        Need Help?
                    </a>
                </div>

                <!-- Order Number for Reference -->
                <div class="mt-8 p-4 bg-gray-50 rounded-lg">
                    <p class="text-sm text-gray-600">
                        <strong>Order Reference:</strong> <?php echo htmlspecialchars($order['order_number'] ?? "Order #$order_id"); ?>
                    </p>
                    <p class="text-xs text-gray-500 mt-1">
                        Keep this number for your records and any inquiries about your order.
                    </p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Newsletter Signup CTA -->
<section class="py-16 bg-primary text-white">
    <div class="container mx-auto px-4 text-center">
        <div class="max-w-3xl mx-auto">
            <h2 class="text-3xl font-bold mb-4">Never Miss Our Special Offers!</h2>
            <p class="text-xl mb-8 text-primary-light">Subscribe to our newsletter and get exclusive deals, new menu items, and delicious recipes delivered to your inbox.</p>

            <form action="/subscribe.php" method="POST" class="max-w-md mx-auto flex flex-col sm:flex-row gap-3">
                <input type="email" name="email" placeholder="Enter your email address" required
                       class="flex-1 px-4 py-3 rounded-lg focus:outline-none focus:ring-2 focus:ring-white text-gray-900">
                <button type="submit" class="bg-secondary text-white px-6 py-3 rounded-lg hover:bg-opacity-90 transition-colors font-semibold">
                    Subscribe
                </button>
            </form>
        </div>
    </div>
</section>

<?php include 'includes/footer.php'; ?>
