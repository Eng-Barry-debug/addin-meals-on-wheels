<?php
// Include configuration and check login
require_once __DIR__ . '/../includes/config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: /auth/login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$page_title = 'My Orders';

// Pagination
$orders_per_page = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $orders_per_page;

// Filter by status
$status_filter = $_GET['status'] ?? 'all';
$status_condition = '';
$params = [];

if ($status_filter !== 'all') {
    $status_condition = "WHERE o.status = :status";
    $params[':status'] = $status_filter;
}

// Get total orders count
$count_sql = "SELECT COUNT(*) as total FROM orders o $status_condition";
$count_stmt = $pdo->prepare($count_sql);
if (!empty($params)) {
    foreach ($params as $key => $value) {
        $count_stmt->bindValue($key, $value);
    }
}
$count_stmt->execute();
$total_orders = $count_stmt->fetch(PDO::FETCH_ASSOC)['total'];
$total_pages = ceil($total_orders / $orders_per_page);

// Fetch orders with pagination
$sql = "
    SELECT o.*, COUNT(oi.id) as item_count
    FROM orders o
    LEFT JOIN order_items oi ON o.id = oi.order_id
    $status_condition
    GROUP BY o.id
    ORDER BY o.created_at DESC
    LIMIT :offset, :limit
";

$stmt = $pdo->prepare($sql);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->bindValue(':limit', $orders_per_page, PDO::PARAM_INT);

if (!empty($params)) {
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
}

$stmt->execute();
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

include '../includes/header.php';
?>

<!-- Orders Page -->
<section class="py-12 bg-light">
    <div class="container mx-auto px-4">
        <div class="max-w-6xl mx-auto">
            <!-- Page Header -->
            <div class="flex flex-col md:flex-row md:items-center md:justify-between mb-8">
                <div>
                    <h1 class="text-3xl md:text-4xl font-bold text-dark mb-2">My Orders</h1>
                    <p class="text-gray-600">View your order history and track current orders</p>
                </div>
                <div class="mt-4 md:mt-0 flex flex-col sm:flex-row gap-3">
                    <a href="/menu.php" class="inline-flex items-center justify-center px-6 py-3 bg-primary text-white font-bold rounded-lg hover:bg-opacity-90 transition-colors">
                        <i class="fas fa-plus mr-2"></i> New Order
                    </a>
                    <a href="/cart.php" class="inline-flex items-center justify-center px-6 py-3 border-2 border-primary text-primary font-bold rounded-lg hover:bg-primary hover:text-white transition-colors">
                        <i class="fas fa-shopping-cart mr-2"></i> View Cart
                    </a>
                    <a href="/order-tracking.php" class="inline-flex items-center justify-center px-6 py-3 bg-accent text-white font-bold rounded-lg hover:bg-opacity-90 transition-colors">
                        <i class="fas fa-search mr-2"></i> Track Order
                    </a>
                </div>
            </div>

            <?php if (empty($orders)): ?>
                <!-- No Orders State -->
                <div class="bg-white rounded-2xl shadow-lg p-12 text-center">
                    <div class="max-w-md mx-auto">
                        <i class="fas fa-shopping-bag text-6xl text-gray-300 mb-6"></i>
                        <h2 class="text-2xl font-bold text-gray-700 mb-4">No orders yet</h2>
                        <p class="text-gray-600 mb-8">You haven't placed any orders yet. Start your culinary journey with us!</p>
                        <a href="/menu.php" class="inline-flex items-center justify-center px-8 py-4 bg-gradient-to-r from-primary to-secondary text-white font-bold rounded-lg hover:shadow-lg transform hover:-translate-y-1 transition-all duration-300">
                            <i class="fas fa-utensils mr-2"></i>
                            Browse Our Menu
                        </a>
                    </div>
                </div>
            <?php else: ?>
                <!-- Orders Filter Tabs -->
                <div class="bg-white rounded-t-2xl shadow-lg mb-6">
                    <div class="border-b border-gray-200">
                        <nav class="flex overflow-x-auto">
                            <a href="?status=all" class="whitespace-nowrap px-6 py-4 text-sm font-medium border-b-2 <?php echo $status_filter === 'all' ? 'border-primary text-primary' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'; ?>">
                                All Orders (<?php echo $total_orders; ?>)
                            </a>
                            <a href="?status=pending" class="whitespace-nowrap px-6 py-4 text-sm font-medium border-b-2 <?php echo $status_filter === 'pending' ? 'border-primary text-primary' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'; ?>">
                                Pending
                            </a>
                            <a href="?status=processing" class="whitespace-nowrap px-6 py-4 text-sm font-medium border-b-2 <?php echo $status_filter === 'processing' ? 'border-primary text-primary' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'; ?>">
                                Processing
                            </a>
                            <a href="?status=delivered" class="whitespace-nowrap px-6 py-4 text-sm font-medium border-b-2 <?php echo $status_filter === 'delivered' ? 'border-primary text-primary' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'; ?>">
                                Delivered
                            </a>
                        </nav>
                    </div>
                </div>

                <!-- Orders List -->
                <div class="space-y-6">
                    <?php foreach ($orders as $order): ?>
                    <div class="bg-white rounded-xl shadow-lg overflow-hidden hover:shadow-xl transition-shadow duration-300">
                        <!-- Order Header -->
                        <div class="bg-gradient-to-r from-gray-50 to-gray-100 px-6 py-4 border-b border-gray-200">
                            <div class="flex flex-col md:flex-row md:items-center md:justify-between">
                                <div class="flex items-center space-x-4">
                                    <div>
                                        <h3 class="text-lg font-bold text-gray-900">
                                            Order #<?php echo htmlspecialchars($order['order_number'] ?? $order['id']); ?>
                                        </h3>
                                        <p class="text-sm text-gray-600">
                                            Placed on <?php echo date('M j, Y \a\t g:i A', strtotime($order['created_at'])); ?>
                                        </p>
                                    </div>
                                </div>
                                <div class="mt-3 md:mt-0 flex items-center space-x-3">
                                    <span class="px-3 py-1 rounded-full text-sm font-semibold <?php
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
                                    <span class="text-lg font-bold text-primary">
                                        KES <?php echo number_format($order['total'], 2); ?>
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- Order Details -->
                        <div class="p-6">
                            <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
                                <!-- Items Count -->
                                <div class="flex items-center">
                                    <div class="w-10 h-10 bg-primary/10 rounded-full flex items-center justify-center mr-3">
                                        <i class="fas fa-shopping-bag text-primary"></i>
                                    </div>
                                    <div>
                                        <p class="text-sm text-gray-600">Items</p>
                                        <p class="font-semibold"><?php echo $order['item_count']; ?> items</p>
                                    </div>
                                </div>

                                <!-- Payment Method -->
                                <div class="flex items-center">
                                    <div class="w-10 h-10 bg-secondary/10 rounded-full flex items-center justify-center mr-3">
                                        <i class="fas fa-credit-card text-secondary"></i>
                                    </div>
                                    <div>
                                        <p class="text-sm text-gray-600">Payment</p>
                                        <p class="font-semibold"><?php echo ucfirst(str_replace('_', ' ', $order['payment_method'])); ?></p>
                                    </div>
                                </div>

                                <!-- Delivery Fee -->
                                <div class="flex items-center">
                                    <div class="w-10 h-10 bg-accent/10 rounded-full flex items-center justify-center mr-3">
                                        <i class="fas fa-truck text-accent"></i>
                                    </div>
                                    <div>
                                        <p class="text-sm text-gray-600">Delivery</p>
                                        <p class="font-semibold <?php echo $order['delivery_fee'] === 0 ? 'text-green-600' : ''; ?>">
                                            <?php echo $order['delivery_fee'] === 0 ? 'Free' : 'KES ' . number_format($order['delivery_fee'], 2); ?>
                                        </p>
                                    </div>
                                </div>

                                <!-- Actions -->
                                <div class="flex items-center">
                                    <div class="w-10 h-10 bg-gray-100 rounded-full flex items-center justify-center mr-3">
                                        <i class="fas fa-eye text-gray-600"></i>
                                    </div>
                                    <div>
                                        <p class="text-sm text-gray-600">Actions</p>
                                        <a href="order-details.php?id=<?php echo $order['id']; ?>"
                                           class="font-semibold text-primary hover:text-primary-dark">
                                            View Details
                                        </a>
                                    </div>
                                </div>
                            </div>

                            <!-- Quick Actions -->
                            <div class="flex flex-wrap gap-3 pt-4 border-t border-gray-200">
                                <?php if ($order['status'] === 'delivered'): ?>
                                <a href="order-details.php?id=<?php echo $order['id']; ?>"
                                   class="inline-flex items-center px-4 py-2 bg-green-100 text-green-800 rounded-lg hover:bg-green-200 transition-colors">
                                    <i class="fas fa-star mr-2"></i>
                                    Rate Order
                                </a>
                                <?php endif; ?>

                                <?php if (in_array($order['status'], ['delivered', 'cancelled'])): ?>
                                <a href="/menu.php"
                                   class="inline-flex items-center px-4 py-2 bg-primary text-white rounded-lg hover:bg-opacity-90 transition-colors">
                                    <i class="fas fa-redo mr-2"></i>
                                    Reorder
                                </a>
                                <?php endif; ?>

                                <a href="order-details.php?id=<?php echo $order['id']; ?>"
                                   class="inline-flex items-center px-4 py-2 border border-primary text-primary rounded-lg hover:bg-primary hover:text-white transition-colors">
                                    <i class="fas fa-eye mr-2"></i>
                                    View Details
                                </a>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>

                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                <div class="mt-8 flex justify-center">
                    <nav class="flex items-center space-x-2">
                        <?php if ($page > 1): ?>
                        <a href="?page=<?php echo $page - 1; ?><?php echo $status_filter !== 'all' ? '&status=' . $status_filter : ''; ?>"
                           class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
                            <i class="fas fa-chevron-left mr-1"></i> Previous
                        </a>
                        <?php endif; ?>

                        <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                        <a href="?page=<?php echo $i; ?><?php echo $status_filter !== 'all' ? '&status=' . $status_filter : ''; ?>"
                           class="px-4 py-2 border rounded-lg transition-colors <?php echo $i === $page ? 'bg-primary text-white border-primary' : 'border-gray-300 hover:bg-gray-50'; ?>">
                            <?php echo $i; ?>
                        </a>
                        <?php endfor; ?>

                        <?php if ($page < $total_pages): ?>
                        <a href="?page=<?php echo $page + 1; ?><?php echo $status_filter !== 'all' ? '&status=' . $status_filter : ''; ?>"
                           class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
                            Next <i class="fas fa-chevron-right ml-1"></i>
                        </a>
                        <?php endif; ?>
                    </nav>
                </div>
                <?php endif; ?>

                <!-- Order Summary Stats -->
                <div class="mt-8 grid grid-cols-2 md:grid-cols-4 gap-6">
                    <div class="bg-white rounded-lg shadow-md p-6 text-center">
                        <div class="text-2xl font-bold text-primary mb-2"><?php echo $total_orders; ?></div>
                        <div class="text-gray-600">Total Orders</div>
                    </div>
                    <div class="bg-white rounded-lg shadow-md p-6 text-center">
                        <div class="text-2xl font-bold text-green-600 mb-2">
                            <?php
                            $delivered_count = $pdo->prepare("SELECT COUNT(*) FROM orders WHERE user_id = :user_id AND status = 'delivered'");
                            $delivered_count->execute([':user_id' => $user_id]);
                            echo $delivered_count->fetchColumn();
                            ?>
                        </div>
                        <div class="text-gray-600">Delivered</div>
                    </div>
                    <div class="bg-white rounded-lg shadow-md p-6 text-center">
                        <div class="text-2xl font-bold text-blue-600 mb-2">
                            <?php
                            $pending_count = $pdo->prepare("SELECT COUNT(*) FROM orders WHERE user_id = :user_id AND status = 'pending'");
                            $pending_count->execute([':user_id' => $user_id]);
                            echo $pending_count->fetchColumn();
                            ?>
                        </div>
                        <div class="text-gray-600">Pending</div>
                    </div>
                    <div class="bg-white rounded-lg shadow-md p-6 text-center">
                        <div class="text-2xl font-bold text-purple-600 mb-2">
                            KES <?php
                            $total_spent = $pdo->prepare("SELECT SUM(total) FROM orders WHERE user_id = :user_id AND status = 'delivered'");
                            $total_spent->execute([':user_id' => $user_id]);
                            echo number_format($total_spent->fetchColumn() ?: 0, 2);
                            ?>
                        </div>
                        <div class="text-gray-600">Total Spent</div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<?php include '../includes/footer.php'; ?>
