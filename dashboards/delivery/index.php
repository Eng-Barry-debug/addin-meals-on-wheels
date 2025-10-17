<?php
// Start session and check delivery authentication
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include database connection and functions
require_once '../../admin/includes/config.php';
require_once 'includes/functions.php';

// Check if user is logged in and is delivery personnel
if (!isset($_SESSION['user_id']) || ($_SESSION['user_role'] !== 'delivery' && $_SESSION['user_role'] !== 'driver')) {
    header('Location: ../../auth/login.php');
    exit();
}

// Set page title
$page_title = 'Delivery Dashboard';

// Initialize variables with default values
$totalDeliveries = 0;
$pendingDeliveries = 0;
$completedToday = 0;
$totalEarnings = 0;
$availableDeliveries = 0;
$recentDeliveries = [];
$error = null;

// Get delivery dashboard data
try {
    global $pdo;

    // Get delivery statistics
    $pendingDeliveries = getCount($pdo, 'orders', 'status = "out_for_delivery"');
    $completedToday = getCount($pdo, 'orders', 'status = "delivered" AND DATE(updated_at) = CURDATE()');
    $totalDeliveries = getCount($pdo, 'orders', 'status IN ("delivered", "out_for_delivery")');

    // Get total earnings (simplified calculation)
    $earningsStmt = $pdo->query("SELECT COALESCE(SUM(total * 0.1), 0) as total FROM orders WHERE status = 'delivered'");
    $totalEarnings = (float)($earningsStmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0);

    // Get available deliveries (orders ready for pickup)
    $availableDeliveries = getCount($pdo, 'orders', 'status = "ready_for_delivery"');

    // Get recent deliveries for current delivery person
    $recentDeliveriesStmt = $pdo->prepare("
        SELECT o.*, u.name as customer_name,
               CASE
                   WHEN o.status = 'out_for_delivery' THEN 'On Route'
                   WHEN o.status = 'delivered' THEN 'Delivered'
                   ELSE o.status
               END as display_status
        FROM orders o
        JOIN users u ON o.user_id = u.id
        WHERE o.status IN ('out_for_delivery', 'delivered')
        ORDER BY o.updated_at DESC
        LIMIT 10
    ");
    $recentDeliveriesStmt->execute();
    $recentDeliveries = $recentDeliveriesStmt->fetchAll(PDO::FETCH_ASSOC);

    // Get delivery performance metrics
    $todayStart = date('Y-m-d 00:00:00');
    $todayEnd = date('Y-m-d 23:59:59');

    $todayDeliveriesStmt = $pdo->prepare("
        SELECT
            COUNT(*) as total_today,
            AVG(TIMESTAMPDIFF(MINUTE, created_at, updated_at)) as avg_delivery_time
        FROM orders
        WHERE status = 'delivered'
        AND updated_at BETWEEN ? AND ?
    ");
    $todayDeliveriesStmt->execute([$todayStart, $todayEnd]);
    $todayStats = $todayDeliveriesStmt->fetch(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $error = "Database error: " . $e->getMessage();
    error_log("Delivery dashboard error: " . $e->getMessage());
}

// Include activity logger
require_once '../../includes/ActivityLogger.php';
$activityLogger = new ActivityLogger($pdo);

// Log dashboard access
$activityLogger->log('delivery', 'dashboard_view', 'Delivery person accessed dashboard', 'user', $_SESSION['user_id']);

// Include header
require_once 'includes/header.php';
?>

<!-- Dashboard Header -->
<div class="bg-gradient-to-br from-primary via-primary-dark to-green-700 text-white">
    <div class="px-6 py-12">
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between">
            <div>
                <h1 class="text-4xl md:text-5xl font-bold mb-3">Delivery Dashboard</h1>
                <p class="text-xl opacity-90 mb-4">Track your deliveries and manage your routes</p>
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
                    <a href="deliveries.php"
                       class="bg-white/20 hover:bg-white/30 text-white px-6 py-3 rounded-lg font-semibold transition-all duration-200 flex items-center backdrop-blur-sm border border-white/20">
                        <i class="fas fa-box mr-2"></i>
                        View Deliveries
                    </a>
                    <a href="routes.php"
                       class="bg-accent hover:bg-blue-600 text-white px-6 py-3 rounded-lg font-semibold transition-all duration-200 flex items-center">
                        <i class="fas fa-route mr-2"></i>
                        My Routes
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Main Dashboard Content -->
<div class="px-6 py-8">
    <?php if (isset($error)): ?>
        <div class="mb-6 p-4 bg-red-50 border-l-4 border-red-400 text-red-700 rounded-lg flex items-center">
            <i class="fas fa-exclamation-circle mr-3"></i>
            <div>
                <p class="font-semibold">Database Error</p>
                <p><?php echo htmlspecialchars($error); ?></p>
            </div>
        </div>
    <?php endif; ?>

    <!-- Key Metrics Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <!-- Pending Deliveries -->
        <div class="bg-white rounded-xl shadow-lg p-6 border-l-4 border-yellow-500 hover:shadow-xl transition-shadow duration-200">
            <div class="flex items-center">
                <div class="p-3 bg-yellow-100 rounded-lg">
                    <i class="fas fa-clock text-2xl text-yellow-600"></i>
                </div>
                <div class="ml-4">
                    <h3 class="text-3xl font-bold text-gray-900 mb-1"><?php echo number_format($pendingDeliveries); ?></h3>
                    <p class="text-gray-600 font-medium">Pending Deliveries</p>
                    <p class="text-sm text-gray-500 mt-1">Awaiting pickup</p>
                </div>
            </div>
        </div>

        <!-- Completed Today -->
        <div class="bg-white rounded-xl shadow-lg p-6 border-l-4 border-green-500 hover:shadow-xl transition-shadow duration-200">
            <div class="flex items-center">
                <div class="p-3 bg-green-100 rounded-lg">
                    <i class="fas fa-check-circle text-2xl text-green-600"></i>
                </div>
                <div class="ml-4">
                    <h3 class="text-3xl font-bold text-gray-900 mb-1"><?php echo number_format($completedToday); ?></h3>
                    <p class="text-gray-600 font-medium">Completed Today</p>
                    <p class="text-sm text-gray-500 mt-1">Deliveries finished</p>
                </div>
            </div>
        </div>

        <!-- Total Deliveries -->
        <div class="bg-white rounded-xl shadow-lg p-6 border-l-4 border-blue-500 hover:shadow-xl transition-shadow duration-200">
            <div class="flex items-center">
                <div class="p-3 bg-blue-100 rounded-lg">
                    <i class="fas fa-box text-2xl text-blue-600"></i>
                </div>
                <div class="ml-4">
                    <h3 class="text-3xl font-bold text-gray-900 mb-1"><?php echo number_format($totalDeliveries); ?></h3>
                    <p class="text-gray-600 font-medium">Total Deliveries</p>
                    <p class="text-sm text-gray-500 mt-1">All time deliveries</p>
                </div>
            </div>
        </div>

        <!-- Total Earnings -->
        <div class="bg-white rounded-xl shadow-lg p-6 border-l-4 border-purple-500 hover:shadow-xl transition-shadow duration-200">
            <div class="flex items-center">
                <div class="p-3 bg-purple-100 rounded-lg">
                    <i class="fas fa-money-bill-wave text-2xl text-purple-600"></i>
                </div>
                <div class="ml-4">
                    <h3 class="text-3xl font-bold text-gray-900 mb-1">KES <?php echo number_format($totalEarnings, 0); ?></h3>
                    <p class="text-gray-600 font-medium">Total Earnings</p>
                    <p class="text-sm text-gray-500 mt-1">From deliveries</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Available Deliveries Alert -->
    <?php if ($availableDeliveries > 0): ?>
        <div class="mb-8 p-4 bg-green-50 border-l-4 border-green-400 rounded-lg">
            <div class="flex items-center justify-between">
                <div class="flex items-center">
                    <i class="fas fa-bell text-green-600 mr-3"></i>
                    <div>
                        <p class="font-semibold text-green-800">New deliveries available!</p>
                        <p class="text-green-700"><?php echo $availableDeliveries; ?> orders are ready for pickup</p>
                    </div>
                </div>
                <a href="deliveries.php" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg font-medium transition-colors">
                    View Available
                </a>
            </div>
        </div>
    <?php endif; ?>

    <!-- Charts and Analytics Row -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
        <!-- Recent Deliveries -->
        <div class="bg-white rounded-xl shadow-lg p-6">
            <h3 class="text-xl font-bold text-gray-900 mb-6 flex items-center">
                <i class="fas fa-history mr-3 text-primary"></i>
                Recent Deliveries
            </h3>

            <?php if (!empty($recentDeliveries)): ?>
                <div class="space-y-4 max-h-96 overflow-y-auto">
                    <?php foreach ($recentDeliveries as $delivery): ?>
                        <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg">
                            <div class="flex items-center space-x-3">
                                <div class="w-10 h-10 bg-primary/10 rounded-full flex items-center justify-center">
                                    <i class="fas fa-box text-primary"></i>
                                </div>
                                <div>
                                    <p class="font-medium text-gray-900"><?php echo htmlspecialchars($delivery['customer_name']); ?></p>
                                    <p class="text-sm text-gray-500">Order #<?php echo $delivery['id']; ?></p>
                                </div>
                            </div>
                            <div class="text-right">
                                <span class="px-3 py-1 text-sm font-medium rounded-full
                                    <?php
                                    switch($delivery['status']) {
                                        case 'out_for_delivery': echo 'bg-yellow-100 text-yellow-800'; break;
                                        case 'delivered': echo 'bg-green-100 text-green-800'; break;
                                        default: echo 'bg-gray-100 text-gray-800';
                                    }
                                    ?>">
                                    <?php echo $delivery['display_status']; ?>
                                </span>
                                <p class="text-xs text-gray-500 mt-1"><?php echo date('M j, g:i A', strtotime($delivery['updated_at'])); ?></p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="mt-6 pt-4 border-t border-gray-200">
                    <a href="deliveries.php" class="text-primary hover:text-primary-dark font-medium text-sm flex items-center justify-center">
                        <i class="fas fa-list mr-2"></i>
                        View All Deliveries
                    </a>
                </div>
            <?php else: ?>
                <div class="text-center py-8">
                    <i class="fas fa-box-open text-4xl text-gray-300 mb-3"></i>
                    <p class="text-gray-500">No deliveries yet.</p>
                    <p class="text-sm text-gray-400 mt-2">Your delivery history will appear here.</p>
                </div>
            <?php endif; ?>
        </div>

        <!-- Delivery Performance -->
        <div class="bg-white rounded-xl shadow-lg p-6">
            <h3 class="text-xl font-bold text-gray-900 mb-6 flex items-center">
                <i class="fas fa-chart-line mr-3 text-primary"></i>
                Today's Performance
            </h3>

            <div class="space-y-6">
                <!-- Today's Stats -->
                <div class="grid grid-cols-2 gap-4">
                    <div class="text-center p-4 bg-green-50 rounded-lg">
                        <div class="text-2xl font-bold text-green-600"><?php echo $todayStats['total_today'] ?? 0; ?></div>
                        <div class="text-sm text-green-700">Deliveries Today</div>
                    </div>
                    <div class="text-center p-4 bg-blue-50 rounded-lg">
                        <div class="text-2xl font-bold text-blue-600">
                            <?php echo isset($todayStats['avg_delivery_time']) ? round($todayStats['avg_delivery_time']) : 0; ?>m
                        </div>
                        <div class="text-sm text-blue-700">Avg. Time</div>
                    </div>
                </div>

                <!-- Performance Indicators -->
                <div class="space-y-3">
                    <div class="flex items-center justify-between">
                        <span class="text-gray-700">On-time Delivery Rate</span>
                        <div class="flex items-center space-x-2">
                            <div class="w-24 bg-gray-200 rounded-full h-2">
                                <div class="bg-green-500 h-2 rounded-full" style="width: 95%"></div>
                            </div>
                            <span class="text-sm font-medium text-gray-900">95%</span>
                        </div>
                    </div>

                    <div class="flex items-center justify-between">
                        <span class="text-gray-700">Customer Satisfaction</span>
                        <div class="flex items-center space-x-2">
                            <div class="w-24 bg-gray-200 rounded-full h-2">
                                <div class="bg-yellow-500 h-2 rounded-full" style="width: 88%"></div>
                            </div>
                            <span class="text-sm font-medium text-gray-900">88%</span>
                        </div>
                    </div>

                    <div class="flex items-center justify-between">
                        <span class="text-gray-700">Active Routes</span>
                        <span class="text-sm font-medium text-gray-900">3 Routes</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Actions and Status -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
        <!-- Quick Actions -->
        <div class="bg-white rounded-xl shadow-lg p-6">
            <h3 class="text-xl font-bold text-gray-900 mb-6 flex items-center">
                <i class="fas fa-bolt mr-3 text-primary"></i>
                Quick Actions
            </h3>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <a href="deliveries.php"
                   class="bg-primary hover:bg-primary-dark text-white px-4 py-3 rounded-lg font-medium transition-colors duration-200 flex items-center justify-center">
                    <i class="fas fa-box mr-2"></i>
                    View Deliveries
                </a>

                <a href="routes.php"
                   class="bg-accent hover:bg-blue-600 text-white px-4 py-3 rounded-lg font-medium transition-colors duration-200 flex items-center justify-center">
                    <i class="fas fa-route mr-2"></i>
                    Manage Routes
                </a>

                <a href="earnings.php"
                   class="bg-secondary hover:bg-yellow-600 text-white px-4 py-3 rounded-lg font-medium transition-colors duration-200 flex items-center justify-center">
                    <i class="fas fa-money-bill-wave mr-2"></i>
                    View Earnings
                </a>

                <a href="profile.php"
                   class="bg-gray-100 hover:bg-gray-200 text-gray-700 px-4 py-3 rounded-lg font-medium transition-colors duration-200 flex items-center justify-center">
                    <i class="fas fa-user mr-2"></i>
                    My Profile
                </a>
            </div>
        </div>

        <!-- Current Status -->
        <div class="bg-white rounded-xl shadow-lg p-6">
            <h3 class="text-xl font-bold text-gray-900 mb-6 flex items-center">
                <i class="fas fa-info-circle mr-3 text-primary"></i>
                Current Status
            </h3>

            <div class="space-y-4">
                <div class="flex items-center justify-between p-3 bg-green-50 rounded-lg">
                    <div class="flex items-center">
                        <div class="status-indicator status-active"></div>
                        <span class="font-medium text-green-800">Status: Active</span>
                    </div>
                    <button class="bg-green-600 hover:bg-green-700 text-white px-3 py-1 rounded text-sm transition-colors">
                        Available
                    </button>
                </div>

                <div class="flex items-center justify-between p-3 bg-blue-50 rounded-lg">
                    <span class="font-medium text-blue-800">Current Location</span>
                    <span class="text-sm text-blue-600">Nairobi CBD</span>
                </div>

                <div class="flex items-center justify-between p-3 bg-yellow-50 rounded-lg">
                    <span class="font-medium text-yellow-800">Next Pickup</span>
                    <span class="text-sm text-yellow-600">2:30 PM</span>
                </div>

                <div class="pt-4 border-t border-gray-200">
                    <div class="flex items-center justify-between text-sm text-gray-600">
                        <span>Vehicle: Motorcycle</span>
                        <span>License: KCB 123D</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Today's Schedule -->
    <div class="mt-8">
        <div class="bg-white rounded-xl shadow-lg p-6">
            <h3 class="text-xl font-bold text-gray-900 mb-6 flex items-center">
                <i class="fas fa-calendar mr-3 text-primary"></i>
                Today's Schedule
            </h3>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <!-- Morning Shift -->
                <div class="text-center p-4 bg-blue-50 rounded-lg">
                    <i class="fas fa-sun text-2xl text-blue-600 mb-2"></i>
                    <h4 class="font-semibold text-blue-800 mb-1">Morning</h4>
                    <p class="text-sm text-blue-600">8:00 AM - 2:00 PM</p>
                    <p class="text-xs text-blue-500 mt-2">5 deliveries completed</p>
                </div>

                <!-- Afternoon Shift -->
                <div class="text-center p-4 bg-yellow-50 rounded-lg">
                    <i class="fas fa-cloud-sun text-2xl text-yellow-600 mb-2"></i>
                    <h4 class="font-semibold text-yellow-800 mb-1">Afternoon</h4>
                    <p class="text-sm text-yellow-600">2:00 PM - 8:00 PM</p>
                    <p class="text-xs text-yellow-500 mt-2">3 deliveries pending</p>
                </div>

                <!-- Evening Shift -->
                <div class="text-center p-4 bg-gray-50 rounded-lg">
                    <i class="fas fa-moon text-2xl text-gray-600 mb-2"></i>
                    <h4 class="font-semibold text-gray-800 mb-1">Evening</h4>
                    <p class="text-sm text-gray-600">8:00 PM - 12:00 AM</p>
                    <p class="text-xs text-gray-500 mt-2">Off duty</p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
