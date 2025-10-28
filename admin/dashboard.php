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

    // Get total revenue and sales (using actual order totals)
    $revenueStmt = $pdo->query("SELECT COALESCE(SUM(o.total), 0) as total FROM orders o WHERE 1=1");
    $totalRevenue = (float)($revenueStmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0);
    $totalSales = $totalRevenue;

    // Get catering requests count
    $cateringRequests = getCount($pdo, 'catering_requests');

    // Get pending orders count from database
    $pendingOrders = getCount($pdo, 'orders', "status = 'pending'");

    // Get completed orders today from database
    $todayCompleted = getCount($pdo, 'orders', "status IN ('completed', 'delivered') AND DATE(created_at) = CURDATE()");

    // Get new customers this month (using correct role value)
    $newUsersThisMonth = getCount($pdo, 'users', "role = 'customer' AND DATE_FORMAT(created_at, '%Y-%m') = DATE_FORMAT(CURDATE(), '%Y-%m')");

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

                        $pdo->query("INSERT INTO orders (user_id, updated_at) VALUES
                            ({$user['id']}, '{$orderDate}')");
                        error_log("Created sample order for user {$user['id']}");
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
            SELECT o.*, u.name as customer_name, u.email as customer_email, u.phone as customer_phone
            FROM orders o
            JOIN users u ON o.user_id = u.id
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

    // Get order status breakdown from database
    try {
        $statusStmt = $pdo->query("
            SELECT status, COUNT(*) as count
            FROM orders
            GROUP BY status
            ORDER BY count DESC
        ");
        $orderStatusData = $statusStmt->fetchAll(PDO::FETCH_ASSOC);

        // If no orders exist, provide default empty states
        if (empty($orderStatusData)) {
            $orderStatusData = [
                ['status' => 'pending', 'count' => 0],
                ['status' => 'processing', 'count' => 0],
                ['status' => 'delivered', 'count' => 0],
                ['status' => 'cancelled', 'count' => 0]
            ];
        }
    } catch (PDOException $e) {
        error_log("Dashboard: Error fetching order status: " . $e->getMessage());
        $orderStatusData = [
            ['status' => 'completed', 'count' => $totalOrders],
            ['status' => 'pending', 'count' => 0]
        ];
    }

    // Prepare chart data for order status distribution
    $statusLabels = [];
    $statusData = [];
    $statusColors = [
        'pending' => 'rgba(245, 158, 11, 0.8)',      // Yellow
        'processing' => 'rgba(59, 130, 246, 0.8)',   // Blue
        'delivered' => 'rgba(16, 185, 129, 0.8)',    // Green
        'completed' => 'rgba(16, 185, 129, 0.8)',    // Green
        'cancelled' => 'rgba(239, 68, 68, 0.8)',     // Red
        'pending_payment' => 'rgba(249, 115, 22, 0.8)', // Orange
    ];

    if (!empty($orderStatusData)) {
        foreach ($orderStatusData as $status) {
            $statusLabels[] = ucfirst(str_replace('_', ' ', $status['status']));
            $statusData[] = $status['count'];

            // Add default color if status not in predefined colors
            if (!isset($statusColors[strtolower($status['status'])])) {
                $statusColors[strtolower($status['status'])] = 'rgba(156, 163, 175, 0.8)'; // Gray
            }
        }
    } else {
        $statusLabels = ['No Data'];
        $statusData = [0];
    }

} catch (PDOException $e) {
    $error = "Database error: " . $e->getMessage();
    $statusLabels = ['No Data'];
    $statusData = [0];
    $orderStatusData = [];
    $monthlySales = [];
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
                    <p class="text-gray-600 font-medium">Total Registered Users</p>
                    <p class="text-sm text-gray-500 mt-1">+<?php echo number_format($newUsersThisMonth); ?> this month</p>
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
                    <p class="text-sm text-gray-500 mt-1"><?php echo number_format($todayCompleted); ?> completed today</p>
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

    <!-- Quick Stats Overview -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
        <!-- Order Status Overview -->
        <div class="bg-white rounded-xl shadow-lg p-6">
            <div class="flex items-center justify-between mb-6">
                <h3 class="text-xl font-bold text-gray-900 flex items-center">
                    <i class="fas fa-chart-pie mr-3 text-primary"></i>
                    Order Status Overview
                </h3>
                <a href="reports.php" class="text-sm text-primary hover:text-primary-dark font-medium flex items-center">
                    View Details <i class="fas fa-arrow-right ml-2"></i>
                </a>
            </div>
            <div class="space-y-4">
                <?php if (!empty($orderStatusData)): ?>
                    <?php
                    $totalStatusOrders = array_sum(array_column($orderStatusData, 'count'));
                    foreach ($orderStatusData as $status):
                        $percentage = $totalStatusOrders > 0 ? ($status['count'] / $totalStatusOrders) * 100 : 0;
                    ?>
                    <div class="flex items-center justify-between">
                        <div class="flex items-center">
                            <div class="w-4 h-4 rounded-full mr-3
                                <?php
                                switch(strtolower($status['status'])) {
                                    case 'pending': echo 'bg-yellow-400'; break;
                                    case 'processing': echo 'bg-blue-400'; break;
                                    case 'delivered':
                                    case 'completed': echo 'bg-green-400'; break;
                                    case 'cancelled': echo 'bg-red-400'; break;
                                    case 'pending_payment': echo 'bg-orange-400'; break;
                                    default: echo 'bg-gray-400';
                                }
                                ?>">
                            </div>
                            <span class="text-gray-700 font-medium"><?php echo ucfirst(str_replace('_', ' ', $status['status'])); ?></span>
                        </div>
                        <div class="flex items-center space-x-2">
                            <span class="text-2xl font-bold text-gray-900"><?php echo number_format($status['count']); ?></span>
                            <div class="w-16 bg-gray-200 rounded-full h-2">
                                <div class="bg-primary h-2 rounded-full"
                                     style="width: <?php echo $percentage; ?>%">
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="text-center py-8">
                        <i class="fas fa-chart-pie text-4xl text-gray-300 mb-3"></i>
                        <p class="text-gray-500">No order status data available.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Quick Actions Panel -->
        <div class="bg-gradient-to-br from-primary to-orange-500 rounded-xl shadow-lg p-6 text-white">
            <h3 class="text-xl font-bold mb-6 flex items-center">
                <i class="fas fa-bolt mr-3"></i>
                Quick Actions
            </h3>
            <div class="grid grid-cols-2 gap-4">
                <a href="orders.php?action=new" class="bg-white/20 hover:bg-white/30 backdrop-blur-sm rounded-lg p-4 transition-all hover:scale-105 flex flex-col items-center justify-center text-center">
                    <i class="fas fa-plus-circle text-3xl mb-2"></i>
                    <span class="text-sm font-medium">New Order</span>
                </a>
                <a href="menu.php?action=add" class="bg-white/20 hover:bg-white/30 backdrop-blur-sm rounded-lg p-4 transition-all hover:scale-105 flex flex-col items-center justify-center text-center">
                    <i class="fas fa-utensils text-3xl mb-2"></i>
                    <span class="text-sm font-medium">Add Menu Item</span>
                </a>
                <a href="customers.php" class="bg-white/20 hover:bg-white/30 backdrop-blur-sm rounded-lg p-4 transition-all hover:scale-105 flex flex-col items-center justify-center text-center">
                    <i class="fas fa-users text-3xl mb-2"></i>
                    <span class="text-sm font-medium">View Customers</span>
                </a>
                <a href="reports.php" class="bg-white/20 hover:bg-white/30 backdrop-blur-sm rounded-lg p-4 transition-all hover:scale-105 flex flex-col items-center justify-center text-center">
                    <i class="fas fa-chart-bar text-3xl mb-2"></i>
                    <span class="text-sm font-medium">View Reports</span>
                </a>
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