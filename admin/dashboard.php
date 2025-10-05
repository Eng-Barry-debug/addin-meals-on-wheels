<?php
// Start session and check admin authentication
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include database connection and functions
require_once '../includes/config.php';
require_once 'includes/functions.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}

// Initialize variables with default values
$totalUsers = 0;
$totalOrders = 0;
$totalMenuItems = 0;
$totalRevenue = 0;
$totalSales = 0;
$cateringRequests = 0;
$recentOrders = [];
$newUsersThisMonth = 0; // Initialize dashboard metrics
$pendingOrders = 0;
$todayCompleted = 0;
$orderStatusData = []; // Initialize order status data
$monthlySales = []; // Initialize monthly sales data
$error = null;

// Get enhanced dashboard data
try {
    // Get total users by role for debugging
    $adminUsers = getCount($pdo, 'users', 'role = "admin"');
    $regularUsers = getCount($pdo, 'users', 'role = "user"');
    $emptyRoleUsers = getCount($pdo, 'users', '(role = "" OR role IS NULL)');
    $totalAllUsers = getCount($pdo, 'users'); // Count all users including admins
    $totalOrders = getCount($pdo, 'orders');
    $totalMenuItems = getCount($pdo, 'menu_items');

    // Get total revenue and sales
    $revenueStmt = $pdo->query("SELECT COALESCE(SUM(mi.price * o.quantity), 0) as total
                               FROM orders o
                               JOIN menu_items mi ON o.menu_item_id = mi.id
                               WHERE o.status = 'completed'");
    $totalRevenue = (float)($revenueStmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0);
    $totalSales = $totalRevenue;

    // Get catering requests count
    $cateringRequests = getCount($pdo, 'catering_requests');

    // Get pending orders count
    $pendingOrders = getCount($pdo, 'orders', 'status = "pending"');

    // Get completed orders today
    $todayCompleted = getCount($pdo, 'orders', "status = 'completed' AND DATE(created_at) = CURDATE()");

    // Get new users this month
    $newUsersThisMonth = getCount($pdo, 'users', "role = 'user' AND DATE_FORMAT(created_at, '%Y-%m') = DATE_FORMAT(CURDATE(), '%Y-%m')");

    // Create sample orders if none exist (for demo purposes)
    if ($totalOrders == 0 || empty($recentOrders)) {
        try {
            // First, ensure we have some menu items
            $menuCheck = $pdo->query("SELECT COUNT(*) as count FROM menu_items")->fetch(PDO::FETCH_ASSOC);
            if ($menuCheck['count'] == 0) {
                // Create sample menu items
                $pdo->query("INSERT INTO menu_items (name, description, price, category, image, status) VALUES
                    ('Jollof Rice', 'Delicious Nigerian jollof rice with chicken', 850.00, 'Main Course', 'jollof.jpg', 'active'),
                    ('Ugali & Sukuma', 'Traditional Kenyan meal with greens', 450.00, 'Main Course', 'ugali.jpg', 'active'),
                    ('Chapati', 'Soft and fluffy Kenyan flatbread', 150.00, 'Side', 'chapati.jpg', 'active'),
                    ('Fresh Juice', 'Fresh tropical fruit juice', 200.00, 'Beverage', 'juice.jpg', 'active')");
                error_log("Created sample menu items");
            }

            // Get menu items for sample orders
            $menuItems = $pdo->query("SELECT id, name, price FROM menu_items LIMIT 4")->fetchAll(PDO::FETCH_ASSOC);

            if (!empty($menuItems)) {
                // Create a sample user if no users exist
                $userCheck = $pdo->query("SELECT COUNT(*) as count FROM users")->fetch(PDO::FETCH_ASSOC);
                if ($userCheck['count'] == 0) {
                    $pdo->query("INSERT INTO users (name, email, password, role, status) VALUES
                        ('Demo Customer', 'demo@addinsmeals.com', '" . password_hash('demo123', PASSWORD_DEFAULT) . "', 'user', 'active')");
                    error_log("Created demo user");
                }

                // Get all users for sample orders
                $allUsers = $pdo->query("SELECT id, name, role FROM users")->fetchAll(PDO::FETCH_ASSOC);

                if (!empty($allUsers)) {
                    // Create sample orders for existing users
                    $orderDate = date('Y-m-d H:i:s');
                    foreach ($allUsers as $user) {
                        // Create orders for all users
                        $randomItem = $menuItems[array_rand($menuItems)];
                        $quantity = rand(1, 3);

                        $pdo->query("INSERT INTO orders (user_id, menu_item_id, quantity, total_price, status, created_at, updated_at) VALUES
                            ({$user['id']}, {$randomItem['id']}, {$quantity}, " . ($randomItem['price'] * $quantity) . ", 'completed', '{$orderDate}', '{$orderDate}')");
                        error_log("Created sample order for user {$user['id']}: {$randomItem['name']} x {$quantity}");
                    }
                }
            }
        } catch (PDOException $e) {
            error_log("Error creating sample data: " . $e->getMessage());
        }
    }

    // Now get recent orders (after potentially creating sample data)
    try {
        $recentOrdersStmt = $pdo->query("
            SELECT o.*, u.name as customer_name,
                   (mi.price * o.quantity) as order_total,
                   mi.name as item_name
            FROM orders o
            JOIN users u ON o.user_id = u.id
            JOIN menu_items mi ON o.menu_item_id = mi.id
            ORDER BY o.created_at DESC
            LIMIT 8
        ");
        $recentOrders = $recentOrdersStmt->fetchAll(PDO::FETCH_ASSOC);

        // Debug: Log recent orders count
        error_log("Dashboard: Found " . count($recentOrders) . " recent orders for display");

        if (!empty($recentOrders)) {
            error_log("Dashboard: Sample recent orders data available");
        }
    } catch (PDOException $e) {
        error_log("Dashboard: Error fetching recent orders: " . $e->getMessage());
        $recentOrders = [];
    }

    // Get order status breakdown
    $orderStatusStmt = $pdo->query("SELECT status, COUNT(*) as count FROM orders GROUP BY status");
    $orderStatusData = $orderStatusStmt->fetchAll(PDO::FETCH_ASSOC);

    // Get monthly sales data for the last 6 months
    $monthlySalesStmt = $pdo->query("
        SELECT DATE_FORMAT(o.created_at, '%Y-%m') as month,
               COALESCE(SUM(mi.price * o.quantity), 0) as sales
        FROM orders o
        JOIN menu_items mi ON o.menu_item_id = mi.id
        WHERE o.status = 'completed'
        AND o.created_at >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
        GROUP BY DATE_FORMAT(o.created_at, '%Y-%m')
        ORDER BY month DESC
        LIMIT 6
    ");
    $monthlySales = $monthlySalesStmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $error = "Database error: " . $e->getMessage();
}

// Include activity logger
require_once '../includes/ActivityLogger.php';
$activityLogger = new ActivityLogger($pdo);

// Get recent activities (comprehensive admin activity feed)
try {
    $recentActivities = $activityLogger->getRecentActivities(8);

    // If no activities exist, create some sample activities for demonstration
    if (empty($recentActivities)) {
        // Log some sample activities
        $activityLogger->log('system', 'login', 'Admin user logged in', 'user', $_SESSION['user_id']);
        $activityLogger->log('menu', 'created', 'Added new menu item: Jollof Rice', 'menu_item', 1);
        $activityLogger->log('order', 'created', 'New order placed for Ugali & Sukuma', 'order', 1);
        $activityLogger->log('user', 'created', 'New customer registered: Barrack Oluoch', 'user', 3);

        // Get the activities we just created
        $recentActivities = $activityLogger->getRecentActivities(8);
    }
} catch (PDOException $e) {
    error_log("Error fetching recent activities: " . $e->getMessage());
    $recentActivities = [];
}

// Include header
require_once 'includes/header.php';
?>

<!-- Dashboard Header -->
<div class="bg-gradient-to-br from-primary via-primary-dark to-secondary text-white">
    <div class="container mx-auto px-6 py-12">
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between">
            <div>
                <h1 class="text-4xl md:text-5xl font-bold mb-3">Dashboard Overview</h1>
                <p class="text-xl opacity-90 mb-4">Welcome back! Here's what's happening with your business today.</p>
                <div class="flex items-center space-x-4">
                    <div class="flex items-center space-x-2 bg-white/10 backdrop-blur-sm rounded-lg px-3 py-2">
                        <i class="fas fa-calendar-alt text-yellow-300"></i>
                        <span class="text-sm font-medium"><?php echo date('l, F j, Y'); ?></span>
                    </div>
                    <div class="flex items-center space-x-2 bg-white/10 backdrop-blur-sm rounded-lg px-3 py-2">
                        <i class="fas fa-clock text-blue-300"></i>
                        <span class="text-sm font-medium"><?php echo date('g:i A'); ?></span>
                    </div>
                </div>
            </div>
            <div class="mt-6 lg:mt-0">
                <div class="flex space-x-3">
                    <a href="orders.php"
                       class="bg-accent hover:bg-accent-dark text-white px-6 py-3 rounded-lg font-semibold transition-all duration-200 flex items-center shadow-lg hover:shadow-xl transform hover:-translate-y-1">
                        <i class="fas fa-plus mr-2"></i>
                        New Order
                    </a>
                    <a href="reports.php"
                       class="bg-white/10 hover:bg-white/20 text-white px-6 py-3 rounded-lg font-semibold transition-all duration-200 flex items-center backdrop-blur-sm border border-white/20">
                        <i class="fas fa-chart-bar mr-2"></i>
                        Reports
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Main Dashboard Content -->
<div class="container mx-auto px-6 py-8">
    <?php if (isset($error)): ?>
        <div class="mb-6 p-4 bg-red-50 border-l-4 border-red-400 text-red-700 rounded-lg flex items-center">
            <i class="fas fa-exclamation-circle mr-3"></i>
            <div>
                <p class="font-semibold">Database Error</p>
                <p><?php echo htmlspecialchars($error); ?></p>
            </div>
            <button onclick="this.parentElement.remove()" class="ml-auto text-gray-400 hover:text-gray-600">
                <i class="fas fa-times"></i>
            </button>
        </div>
    <?php endif; ?>

    <!-- Sample data notification removed -->

    <!-- Key Metrics Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <!-- Total Users -->
        <div class="bg-white rounded-xl shadow-lg p-6 border-l-4 border-blue-500 hover:shadow-xl transition-shadow duration-200">
            <div class="flex items-center">
                <div class="p-3 bg-blue-100 rounded-lg">
                    <i class="fas fa-users text-2xl text-blue-600"></i>
                </div>
                <div class="ml-4">
                    <h3 class="text-3xl font-bold text-gray-900 mb-1"><?php echo number_format($totalAllUsers ?? 0); ?></h3>
                    <p class="text-gray-600 font-medium">Total Customers</p>
                    <p class="text-sm text-gray-500 mt-1">+<?php echo $newUsersThisMonth; ?> this month</p>
                </div>
            </div>
        </div>

        <!-- Total Orders -->
        <div class="bg-white rounded-xl shadow-lg p-6 border-l-4 border-green-500 hover:shadow-xl transition-shadow duration-200">
            <div class="flex items-center">
                <div class="p-3 bg-green-100 rounded-lg">
                    <i class="fas fa-shopping-cart text-2xl text-green-600"></i>
                </div>
                <div class="ml-4">
                    <h3 class="text-3xl font-bold text-gray-900 mb-1"><?php echo number_format($totalOrders); ?></h3>
                    <p class="text-gray-600 font-medium">Total Orders</p>
                    <p class="text-sm text-gray-500 mt-1"><?php echo $pendingOrders; ?> pending</p>
                </div>
            </div>
        </div>

        <!-- Total Revenue -->
        <div class="bg-white rounded-xl shadow-lg p-6 border-l-4 border-purple-500 hover:shadow-xl transition-shadow duration-200">
            <div class="flex items-center">
                <div class="p-3 bg-purple-100 rounded-lg">
                    <i class="fas fa-dollar-sign text-2xl text-purple-600"></i>
                </div>
                <div class="ml-4">
                    <h3 class="text-3xl font-bold text-gray-900 mb-1">KES <?php echo number_format($totalRevenue, 0); ?></h3>
                    <p class="text-gray-600 font-medium">Total Revenue</p>
                    <p class="text-sm text-gray-500 mt-1"><?php echo $todayCompleted; ?> completed today</p>
                </div>
            </div>
        </div>

        <!-- Menu Items -->
        <div class="bg-white rounded-xl shadow-lg p-6 border-l-4 border-yellow-500 hover:shadow-xl transition-shadow duration-200">
            <div class="flex items-center">
                <div class="p-3 bg-yellow-100 rounded-lg">
                    <i class="fas fa-utensils text-2xl text-yellow-600"></i>
                </div>
                <div class="ml-4">
                    <h3 class="text-3xl font-bold text-gray-900 mb-1"><?php echo number_format($totalMenuItems); ?></h3>
                    <p class="text-gray-600 font-medium">Menu Items</p>
                    <p class="text-sm text-gray-500 mt-1"><?php echo $cateringRequests; ?> catering requests</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts and Analytics Row -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
        <!-- Order Status Breakdown -->
        <div class="bg-white rounded-xl shadow-lg p-6">
            <h3 class="text-xl font-bold text-gray-900 mb-6 flex items-center">
                <i class="fas fa-chart-pie mr-3 text-primary"></i>
                Order Status Overview
            </h3>
            <div class="space-y-4">
                <?php foreach ($orderStatusData as $status): ?>
                    <div class="flex items-center justify-between">
                        <div class="flex items-center">
                            <div class="w-4 h-4 rounded-full mr-3
                                <?php
                                switch($status['status']) {
                                    case 'pending': echo 'bg-yellow-400'; break;
                                    case 'processing': echo 'bg-blue-400'; break;
                                    case 'completed': echo 'bg-green-400'; break;
                                    case 'cancelled': echo 'bg-red-400'; break;
                                    default: echo 'bg-gray-400';
                                }
                                ?>">
                            </div>
                            <span class="text-gray-700 font-medium"><?php echo ucfirst($status['status']); ?></span>
                        </div>
                        <div class="flex items-center space-x-2">
                            <span class="text-2xl font-bold text-gray-900"><?php echo $status['count']; ?></span>
                            <div class="w-16 bg-gray-200 rounded-full h-2">
                                <div class="bg-primary h-2 rounded-full"
                                     style="width: <?php echo ($status['count'] / max(1, $totalOrders)) * 100; ?>%">
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Monthly Sales Trend -->
        <div class="bg-white rounded-xl shadow-lg p-6">
            <h3 class="text-xl font-bold text-gray-900 mb-6 flex items-center">
                <i class="fas fa-chart-line mr-3 text-primary"></i>
                Sales Trend (Last 6 Months)
            </h3>
            <div class="space-y-4">
                <?php foreach (array_reverse($monthlySales) as $month): ?>
                    <?php
                    $monthName = date('M Y', strtotime($month['month'] . '-01'));
                    $percentage = $month['sales'] > 0 ? 100 : 0;
                    ?>
                    <div class="flex items-center justify-between">
                        <span class="text-gray-700 font-medium"><?php echo $monthName; ?></span>
                        <div class="flex items-center space-x-3">
                            <span class="text-lg font-bold text-gray-900">KES <?php echo number_format($month['sales'], 0); ?></span>
                            <div class="w-20 bg-gray-200 rounded-full h-2">
                                <div class="bg-accent h-2 rounded-full" style="width: <?php echo $percentage; ?>%"></div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- Recent Activity and Quick Actions -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Recent Orders -->
        <div class="lg:col-span-2">
            <div class="bg-white rounded-xl shadow-lg p-6">
                <div class="flex items-center justify-between mb-6">
                    <h3 class="text-xl font-bold text-gray-900 flex items-center">
                        <i class="fas fa-history mr-3 text-primary"></i>
                        Recent Activities
                    </h3>
                    <div class="flex space-x-2">
                        <select id="activityFilter" class="text-sm border border-gray-300 rounded-lg px-3 py-1 focus:ring-2 focus:ring-primary focus:border-transparent">
                            <option value="all">All Activities</option>
                            <option value="order">Orders</option>
                            <option value="menu">Menu Items</option>
                            <option value="user">Users</option>
                            <option value="system">System</option>
                        </select>
                    </div>
                </div>

                <?php if (!empty($recentActivities)): ?>
                    <div class="space-y-4 max-h-96 overflow-y-auto">
                        <?php foreach ($recentActivities as $activity): ?>
                            <?php
                            $activityIcon = $activityLogger->getActivityIcon($activity['activity_type'], $activity['activity_action']);
                            $activityColor = $activityLogger->getActivityColor($activity['activity_type']);
                            $timeAgo = strtotime($activity['created_at']);
                            $currentTime = time();
                            $diff = $currentTime - $timeAgo;

                            if ($diff < 60) {
                                $timeDisplay = 'Just now';
                            } elseif ($diff < 3600) {
                                $timeDisplay = floor($diff / 60) . ' minutes ago';
                            } elseif ($diff < 86400) {
                                $timeDisplay = floor($diff / 3600) . ' hours ago';
                            } else {
                                $timeDisplay = floor($diff / 86400) . ' days ago';
                            }
                            ?>
                            <div class="flex items-start space-x-4 p-3 rounded-lg hover:bg-gray-50 transition-colors duration-200 border-l-4 border-<?php echo $activityColor; ?>-400 activity-item"
                                 data-type="<?php echo $activity['activity_type']; ?>"
                                 data-action="<?php echo $activity['activity_action']; ?>">
                                <div class="flex-shrink-0">
                                    <div class="w-10 h-10 bg-<?php echo $activityColor; ?>-100 rounded-full flex items-center justify-center">
                                        <i class="<?php echo $activityIcon; ?> text-<?php echo $activityColor; ?>-600"></i>
                                    </div>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center justify-between">
                                        <p class="text-sm font-medium text-gray-900">
                                            <?php echo htmlspecialchars($activity['description']); ?>
                                        </p>
                                        <span class="text-xs text-gray-500"><?php echo $timeDisplay; ?></span>
                                    </div>
                                    <?php if ($activity['user_name']): ?>
                                        <p class="text-xs text-gray-500 mt-1">
                                            by <?php echo htmlspecialchars($activity['user_name']); ?>
                                        </p>
                                    <?php endif; ?>
                                    <?php if ($activity['entity_type'] && $activity['entity_id']): ?>
                                        <div class="flex items-center mt-2">
                                            <span class="px-2 py-1 text-xs font-medium rounded-full bg-<?php echo $activityColor; ?>-100 text-<?php echo $activityColor; ?>-800">
                                                <?php echo ucfirst($activity['entity_type']); ?> #<?php echo $activity['entity_id']; ?>
                                            </span>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <div class="mt-6 pt-4 border-t border-gray-200">
                        <a href="activity_logs.php" class="text-primary hover:text-primary-dark font-medium text-sm flex items-center justify-center">
                            <i class="fas fa-list mr-2"></i>
                            View All Activities
                        </a>
                    </div>
                <?php else: ?>
                    <div class="text-center py-8">
                        <i class="fas fa-history text-4xl text-gray-300 mb-3"></i>
                        <p class="text-gray-500">No recent activities found.</p>
                        <p class="text-sm text-gray-400 mt-2">Activities will appear here as you use the system.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Quick Actions -->
        <div>
            <div class="bg-white rounded-xl shadow-lg p-6">
                <h3 class="text-xl font-bold text-gray-900 mb-6 flex items-center">
                    <i class="fas fa-bolt mr-3 text-primary"></i>
                    Quick Actions
                </h3>

                <div class="space-y-3">
                    <a href="orders.php"
                       class="w-full bg-primary hover:bg-primary-dark text-white px-4 py-3 rounded-lg font-medium transition-colors duration-200 flex items-center justify-center">
                        <i class="fas fa-plus mr-2"></i>
                        Add New Order
                    </a>

                    <a href="menu.php"
                       class="w-full bg-secondary hover:bg-secondary-dark text-white px-4 py-3 rounded-lg font-medium transition-colors duration-200 flex items-center justify-center">
                        <i class="fas fa-utensils mr-2"></i>
                        Manage Menu
                    </a>

                    <a href="customers.php"
                       class="w-full bg-accent hover:bg-accent-dark text-white px-4 py-3 rounded-lg font-medium transition-colors duration-200 flex items-center justify-center">
                        <i class="fas fa-users mr-2"></i>
                        Manage Customers
                    </a>

                    <a href="catering.php"
                       class="w-full bg-gray-100 hover:bg-gray-200 text-gray-700 px-4 py-3 rounded-lg font-medium transition-colors duration-200 flex items-center justify-center">
                        <i class="fas fa-concierge-bell mr-2"></i>
                        Catering Requests
                    </a>

                    <a href="../chat.php"
                       class="w-full bg-blue-500 hover:bg-blue-600 text-white px-4 py-3 rounded-lg font-medium transition-colors duration-200 flex items-center justify-center">
                        <i class="fas fa-comments mr-2"></i>
                        Customer Support
                    </a>

                    <a href="reports.php"
                       class="w-full bg-light hover:bg-light-dark text-dark px-4 py-3 rounded-lg font-medium transition-colors duration-200 flex items-center justify-center">
                        <i class="fas fa-chart-bar mr-2"></i>
                        View Reports
                    </a>
                </div>
            </div>

            <!-- CRUD Operations Panel -->
            <div class="bg-white rounded-xl shadow-lg p-6 mt-6">
                <h3 class="text-xl font-bold text-gray-900 mb-6 flex items-center">
                    <i class="fas fa-database mr-3 text-primary"></i>
                    CRUD Operations
                </h3>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <!-- Orders CRUD -->
                    <div class="bg-gray-50 rounded-lg p-4 border border-gray-200">
                        <h4 class="font-semibold text-gray-800 mb-3 flex items-center">
                            <i class="fas fa-shopping-cart mr-2 text-red-600"></i>
                            Orders
                        </h4>
                        <div class="space-y-2">
                            <a href="orders.php" class="block w-full text-left px-3 py-2 bg-red-600 text-white rounded text-sm hover:bg-red-700 transition-colors font-medium">
                                <i class="fas fa-list mr-2"></i>View All Orders
                            </a>
                            <a href="orders.php" class="block w-full text-left px-3 py-2 border-2 border-red-600 text-red-600 rounded text-sm hover:bg-red-600 hover:text-white transition-colors font-medium">
                                <i class="fas fa-plus mr-2"></i>Add New Order
                            </a>
                            <a href="orders.php" class="block w-full text-left px-3 py-2 border-2 border-green-600 text-green-600 rounded text-sm hover:bg-green-600 hover:text-white transition-colors font-medium">
                                <i class="fas fa-edit mr-2"></i>Manage Status
                            </a>
                        </div>
                    </div>

                    <!-- Menu CRUD -->
                    <div class="bg-gray-50 rounded-lg p-4 border border-gray-200">
                        <h4 class="font-semibold text-gray-800 mb-3 flex items-center">
                            <i class="fas fa-utensils mr-2 text-yellow-600"></i>
                            Menu Items
                        </h4>
                        <div class="space-y-2">
                            <a href="menu.php" class="block w-full text-left px-3 py-2 bg-yellow-600 text-white rounded text-sm hover:bg-yellow-700 transition-colors font-medium">
                                <i class="fas fa-list mr-2"></i>View All Items
                            </a>
                            <a href="menu_add.php" class="block w-full text-left px-3 py-2 border-2 border-yellow-600 text-yellow-600 rounded text-sm hover:bg-yellow-600 hover:text-white transition-colors font-medium">
                                <i class="fas fa-plus mr-2"></i>Add New Item
                            </a>
                            <a href="categories.php" class="block w-full text-left px-3 py-2 border-2 border-purple-600 text-purple-600 rounded text-sm hover:bg-purple-600 hover:text-white transition-colors font-medium">
                                <i class="fas fa-tags mr-2"></i>Categories
                            </a>
                        </div>
                    </div>

                    <!-- Users CRUD -->
                    <div class="bg-gray-50 rounded-lg p-4 border border-gray-200">
                        <h4 class="font-semibold text-gray-800 mb-3 flex items-center">
                            <i class="fas fa-user mr-2 text-green-600"></i>
                            Users
                        </h4>
                        <div class="space-y-2">
                            <a href="customers.php" class="block w-full text-left px-3 py-2 bg-green-600 text-white rounded text-sm hover:bg-green-700 transition-colors font-medium">
                                <i class="fas fa-list mr-2"></i>View All Users
                            </a>
                            <a href="user_add.php" class="block w-full text-left px-3 py-2 border-2 border-green-600 text-green-600 rounded text-sm hover:bg-green-600 hover:text-white transition-colors font-medium">
                                <i class="fas fa-plus mr-2"></i>Add New User
                            </a>
                            <a href="users.php" class="block w-full text-left px-3 py-2 border-2 border-blue-600 text-blue-600 rounded text-sm hover:bg-blue-600 hover:text-white transition-colors font-medium">
                                <i class="fas fa-cog mr-2"></i>Manage Users
                            </a>
                        </div>
                    </div>

                    <!-- Content CRUD -->
                    <div class="bg-gray-50 rounded-lg p-4 border border-gray-200">
                        <h4 class="font-semibold text-gray-800 mb-3 flex items-center">
                            <i class="fas fa-file-alt mr-2 text-purple-600"></i>
                            Content
                        </h4>
                        <div class="space-y-2">
                            <a href="blog.php" class="block w-full text-left px-3 py-2 bg-purple-600 text-white rounded text-sm hover:bg-purple-700 transition-colors font-medium">
                                <i class="fas fa-blog mr-2"></i>Blog Posts
                            </a>
                            <a href="content.php" class="block w-full text-left px-3 py-2 border-2 border-purple-600 text-purple-600 rounded text-sm hover:bg-purple-600 hover:text-white transition-colors font-medium">
                                <i class="fas fa-edit mr-2"></i>Page Content
                            </a>
                            <a href="feedback.php" class="block w-full text-left px-3 py-2 border-2 border-indigo-600 text-indigo-600 rounded text-sm hover:bg-indigo-600 hover:text-white transition-colors font-medium">
                                <i class="fas fa-comments mr-2"></i>Feedback
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Advanced Operations -->
                <div class="mt-6 pt-6 border-t border-gray-200">
                    <h4 class="font-semibold text-gray-800 mb-4 flex items-center">
                        <i class="fas fa-cogs mr-2 text-gray-600"></i>
                        Advanced Operations
                    </h4>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <a href="settings.php" class="flex items-center px-4 py-3 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg transition-colors border border-gray-300">
                            <i class="fas fa-cog mr-3 text-red-600"></i>
                            <div>
                                <div class="font-medium">System Settings</div>
                                <div class="text-sm text-gray-500">Configure application</div>
                            </div>
                        </a>

                        <a href="reports.php" class="flex items-center px-4 py-3 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg transition-colors border border-gray-300">
                            <i class="fas fa-chart-line mr-3 text-green-600"></i>
                            <div>
                                <div class="font-medium">Analytics & Reports</div>
                                <div class="text-sm text-gray-500">View business metrics</div>
                            </div>
                        </a>

                        <a href="activity_logs.php" class="flex items-center px-4 py-3 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg transition-colors border border-gray-300">
                            <i class="fas fa-history mr-3 text-blue-600"></i>
                            <div>
                                <div class="font-medium">Activity Logs</div>
                                <div class="text-sm text-gray-500">System activity tracking</div>
                            </div>
                        </a>

                        <a href="customer_support.php" class="flex items-center px-4 py-3 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg transition-colors border border-gray-300">
                            <i class="fas fa-headset mr-3 text-yellow-600"></i>
                            <div>
                                <div class="font-medium">Support Center</div>
                                <div class="text-sm text-gray-500">Customer service tools</div>
                            </div>
                        </a>
                    </div>
                </div>
            </div>

            <!-- System Status -->
            <div class="bg-white rounded-xl shadow-lg p-6 mt-6">
                <h3 class="text-lg font-bold text-gray-900 mb-4 flex items-center">
                    <i class="fas fa-info-circle mr-2 text-blue-500"></i>
                    System Status
                </h3>

                <div class="space-y-3">
                    <div class="flex items-center justify-between">
                        <span class="text-gray-600">Database</span>
                        <span class="flex items-center text-green-600">
                            <i class="fas fa-check-circle mr-1"></i>
                            Connected
                        </span>
                    </div>

                    <div class="flex items-center justify-between">
                        <span class="text-gray-600">Orders Today</span>
                        <span class="font-semibold text-gray-900"><?php echo $todayCompleted; ?></span>
                    </div>

                    <div class="flex items-center justify-between">
                        <span class="text-gray-600">Pending Orders</span>
                        <span class="font-semibold text-gray-900"><?php echo $pendingOrders; ?></span>
                    </div>

                    <div class="flex items-center justify-between">
                        <span class="text-gray-600">New Users</span>
                        <span class="font-semibold text-gray-900"><?php echo $newUsersThisMonth; ?></span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
/* Enhanced hover effects for cards */
.bg-white:hover {
    transform: translateY(-2px);
}

/* Gradient text effects */
.gradient-text {
    background: linear-gradient(135deg, #C1272D 0%, #D4AF37 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

/* Custom scrollbar for tables */
.overflow-x-auto::-webkit-scrollbar {
    height: 6px;
}

.overflow-x-auto::-webkit-scrollbar-track {
    background: #f1f5f9;
    border-radius: 3px;
}

.overflow-x-auto::-webkit-scrollbar-thumb {
    background: #cbd5e1;
    border-radius: 3px;
}

.overflow-x-auto::-webkit-scrollbar-thumb:hover {
    background: #94a3b8;
}

/* Animation for loading states */
@keyframes pulse {
    0%, 100% {
        opacity: 1;
    }
    50% {
        opacity: .5;
    }
}

.animate-pulse {
    animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
}

/* Activity item styling */
.activity-item {
    transition: all 0.2s ease;
}

.activity-item:hover {
    transform: translateX(4px);
}

/* Activity type badges */
.activity-badge {
    @apply px-2 py-1 text-xs font-medium rounded-full;
}

/* Custom scrollbar for activity feed */
.max-h-96::-webkit-scrollbar {
    width: 6px;
}

.max-h-96::-webkit-scrollbar-track {
    background: #f1f5f9;
    border-radius: 3px;
}

.max-h-96::-webkit-scrollbar-thumb {
    background: #cbd5e1;
    border-radius: 3px;
}

.max-h-96::-webkit-scrollbar-thumb:hover {
    background: #94a3b8;
}

/* Activity filtering functionality */
document.addEventListener('DOMContentLoaded', function() {
    const filterSelect = document.getElementById('activityFilter');
    const activityItems = document.querySelectorAll('.activity-item');

    if (filterSelect) {
        filterSelect.addEventListener('change', function() {
            const filterValue = this.value;

            activityItems.forEach(item => {
                const activityType = item.dataset.type;

                if (filterValue === 'all' || activityType === filterValue) {
                    item.style.display = 'flex';
                } else {
                    item.style.display = 'none';
                }
            });
        });
    }
});
</style>

<script>
// Activity filtering functionality
document.addEventListener('DOMContentLoaded', function() {
    const filterSelect = document.getElementById('activityFilter');
    const activityItems = document.querySelectorAll('.activity-item');

    if (filterSelect) {
        filterSelect.addEventListener('change', function() {
            const filterValue = this.value;

            activityItems.forEach(item => {
                const activityType = item.dataset.type;

                if (filterValue === 'all' || activityType === filterValue) {
                    item.style.display = 'flex';
                } else {
                    item.style.display = 'none';
                }
            });
        });
    }
});
</script>

<?php
// Include footer
require_once 'includes/footer.php';
?>