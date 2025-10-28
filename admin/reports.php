<?php
// reports.php - Admin Reports & Analytics
// This file provides comprehensive reporting and analytics for the admin dashboard.

// 1. Session Start and Authentication
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}

// 2. Page Configuration
$page_title = 'Reports & Analytics';
$page_description = 'Comprehensive business insights and analytics';

// 3. Include Core Dependencies
require_once dirname(__DIR__) . '/includes/config.php';
require_once 'includes/functions.php';
require_once dirname(__DIR__) . '/includes/ActivityLogger.php';

// Initialize ActivityLogger
$activityLogger = new ActivityLogger($pdo);

// 4. Initialize Date Range (default to last 30 days)
$end_date = date('Y-m-d');
$start_date = date('Y-m-d', strtotime('-30 days'));

// Process date filter if submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['date_range'])) {
    $date_range = explode(' - ', $_POST['date_range']);
    if (count($date_range) === 2) {
        $start_date = date('Y-m-d', strtotime(trim($date_range[0])));
        $end_date = date('Y-m-d', strtotime(trim($date_range[1])));
    }
}

// 5. Retrieve Analytics Data
$analytics_data = [];

try {
    // Basic Order Statistics - Fix column name from total_amount to total
    $stmt = $pdo->prepare("
        SELECT
            COUNT(*) as total_orders,
            COALESCE(SUM(total), 0) as total_revenue,
            COALESCE(AVG(total), 0) as avg_order_value
        FROM orders
        WHERE DATE(created_at) BETWEEN ? AND ?
    ");
    $stmt->execute([$start_date, $end_date]);
    $basic_stats = $stmt->fetch(PDO::FETCH_ASSOC);

    $analytics_data['total_orders'] = $basic_stats['total_orders'] ?? 0;
    $analytics_data['total_revenue'] = $basic_stats['total_revenue'] ?? 0;
    $analytics_data['avg_order_value'] = $basic_stats['avg_order_value'] ?? 0;

    // Sales Trend (Last 6 Months) - matching dashboard.php calculation
    $stmt = $pdo->prepare("
        SELECT
            DATE_FORMAT(created_at, '%Y-%m') as month,
            COUNT(*) as order_count,
            COALESCE(SUM(total), 0) as sales
        FROM orders
        WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
        GROUP BY DATE_FORMAT(created_at, '%Y-%m')
        ORDER BY month DESC
        LIMIT 6
    ");
    $stmt->execute();
    $analytics_data['monthly_sales_trend'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Order Status Distribution - Enhanced query with proper error handling
    try {
        $statusStmt = $pdo->prepare("
            SELECT status, COUNT(*) as count
            FROM orders
            WHERE DATE(created_at) BETWEEN ? AND ?
            GROUP BY status
            ORDER BY count DESC
        ");
        $statusStmt->execute([$start_date, $end_date]);
        $status_distribution = $statusStmt->fetchAll(PDO::FETCH_ASSOC);

        // Ensure we have data structure for the chart
        if (empty($status_distribution)) {
            $analytics_data['status_distribution'] = [];
        } else {
            $analytics_data['status_distribution'] = [];
            foreach ($status_distribution as $status) {
                $analytics_data['status_distribution'][$status['status']] = $status['count'];
            }
        }
    } catch (PDOException $e) {
        error_log("Reports: Error fetching order status distribution: " . $e->getMessage());
        $analytics_data['status_distribution'] = [];
    }

    // Daily Sales Trend - Fix column name from total_amount to total
    $stmt = $pdo->prepare("
        SELECT
            DATE(created_at) as order_date,
            COUNT(*) as order_count,
            COALESCE(SUM(total), 0) as daily_revenue
        FROM orders
        WHERE DATE(created_at) BETWEEN ? AND ?
        GROUP BY DATE(created_at)
        ORDER BY order_date
    ");
    $stmt->execute([$start_date, $end_date]);
    $daily_sales = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $analytics_data['daily_sales'] = $daily_sales;

    // Top Customers - Fix column name from total_amount to total
    $stmt = $pdo->prepare("
        SELECT
            u.name,
            u.email,
            COUNT(o.id) as order_count,
            COALESCE(SUM(o.total), 0) as total_spent
        FROM orders o
        JOIN users u ON o.user_id = u.id
        WHERE DATE(o.created_at) BETWEEN ? AND ?
        GROUP BY u.id
        ORDER BY total_spent DESC
        LIMIT 10
    ");
    $stmt->execute([$start_date, $end_date]);
    $analytics_data['top_customers'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get total active customers - Fixed to use correct role value
    $stmt = $pdo->prepare("SELECT COUNT(*) as active_customers FROM users WHERE role = 'customer' AND status = 'active'");
    $stmt->execute();
    $customer_count = $stmt->fetch(PDO::FETCH_ASSOC);
    $analytics_data['active_customers'] = $customer_count['active_customers'] ?? 0;

    // Popular Menu Items - Fixed to use correct column names and joins
    try {
        $stmt = $pdo->prepare("
            SELECT
                mi.name,
                c.name as category,
                COUNT(oi.id) as order_count,
                SUM(oi.quantity) as total_quantity,
                COALESCE(SUM(oi.total), 0) as total_revenue
            FROM order_items oi
            JOIN menu_items mi ON oi.menu_item_id = mi.id
            JOIN categories c ON mi.category_id = c.id
            JOIN orders o ON oi.order_id = o.id
            WHERE DATE(o.created_at) BETWEEN ? AND ?
            GROUP BY mi.id, mi.name, c.name
            ORDER BY total_revenue DESC
            LIMIT 10
        ");
        $stmt->execute([$start_date, $end_date]);
        $analytics_data['popular_items'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        // Log error and provide empty array
        error_log("Popular Menu Items query error: " . $e->getMessage());
        $analytics_data['popular_items'] = [];
    }

    // Payment Method Distribution - Fix column name from total_amount to total
    $stmt = $pdo->prepare("
        SELECT
            payment_method,
            COUNT(*) as count,
            COALESCE(SUM(total), 0) as total_amount
        FROM orders
        WHERE DATE(created_at) BETWEEN ? AND ?
        GROUP BY payment_method
    ");
    $stmt->execute([$start_date, $end_date]);
    $analytics_data['payment_methods'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Customer Growth (new customers per month) - Fixed role value
    $stmt = $pdo->prepare("
        SELECT
            DATE_FORMAT(created_at, '%Y-%m') as month,
            COUNT(*) as new_customers,
            DATE_FORMAT(created_at, '%M %Y') as month_name
        FROM users
        WHERE role = 'customer' AND DATE(created_at) BETWEEN ? AND ?
        GROUP BY DATE_FORMAT(created_at, '%Y-%m'), DATE_FORMAT(created_at, '%M %Y')
        ORDER BY month DESC
    ");
    $stmt->execute([$start_date, $end_date]);
    $analytics_data['customer_growth'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    error_log("Reports data error: " . $e->getMessage());
    $analytics_data = [
        'total_orders' => 0,
        'total_revenue' => 0,
        'avg_order_value' => 0,
        'status_distribution' => [],
        'daily_sales' => [],
        'top_customers' => [],
        'popular_items' => [],
        'payment_methods' => [],
        'customer_growth' => [],
        'monthly_sales_trend' => [],
        'active_customers' => 0
    ];
}

// 6. Include Header (loads CSS, JS, and navigation)
include 'includes/header.php';
?>

<!-- Dashboard Header -->
<div class="bg-gradient-to-br from-primary via-primary-dark to-secondary text-white mt-0">
    <div class="container mx-auto px-6 py-8">
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between">
            <div>
                <h1 class="text-3xl md:text-4xl font-bold mb-2"><?php echo htmlspecialchars($page_title); ?></h1>
                <p class="text-lg opacity-90"><?php echo htmlspecialchars($page_description); ?></p>
            </div>
            <div class="mt-4 lg:mt-0">
                <form method="POST" class="flex items-center space-x-4">
                    <div class="relative">
                        <input type="text"
                               name="date_range"
                               id="date-range"
                               class="bg-white/10 backdrop-blur-sm border border-white/20 rounded-lg px-4 py-2 text-white placeholder-white/70 focus:outline-none focus:ring-2 focus:ring-white/50"
                               value="<?php echo date('m/d/Y', strtotime($start_date)) . ' - ' . date('m/d/Y', strtotime($end_date)); ?>"
                               readonly>
                        <i class="fas fa-calendar-alt absolute right-3 top-1/2 transform -translate-y-1/2 text-white/70"></i>
                    </div>
                    <button type="submit" class="bg-white/20 hover:bg-white/30 text-white px-4 py-2 rounded-lg transition-colors">
                        <i class="fas fa-filter mr-2"></i>Filter
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Key Metrics Cards -->
<div class="bg-gray-50 py-8">
    <div class="container mx-auto px-6">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
            <!-- Total Orders -->
            <div class="bg-white rounded-xl shadow-lg p-6 border-l-4 border-blue-500">
                <div class="flex items-center">
                    <div class="p-3 bg-blue-100 rounded-lg">
                        <i class="fas fa-shopping-cart text-2xl text-blue-600"></i>
                    </div>
                    <div class="ml-4">
                        <h3 class="text-2xl font-bold text-gray-900"><?php echo number_format($analytics_data['total_orders']); ?></h3>
                        <p class="text-gray-600">Total Orders</p>
                    </div>
                </div>
            </div>

            <!-- Total Revenue -->
            <div class="bg-white rounded-xl shadow-lg p-6 border-l-4 border-green-500">
                <div class="flex items-center">
                    <div class="p-3 bg-green-100 rounded-lg">
                        <i class="fas fa-money-bill-wave text-2xl text-green-600"></i>
                    </div>
                    <div class="ml-4">
                        <h3 class="text-2xl font-bold text-gray-900">Ksh <?php echo number_format($analytics_data['total_revenue'], 0); ?></h3>
                        <p class="text-gray-600">Total Revenue</p>
                    </div>
                </div>
            </div>

            <!-- Average Order Value -->
            <div class="bg-white rounded-xl shadow-lg p-6 border-l-4 border-yellow-500">
                <div class="flex items-center">
                    <div class="p-3 bg-yellow-100 rounded-lg">
                        <i class="fas fa-chart-line text-2xl text-yellow-600"></i>
                    </div>
                    <div class="ml-4">
                        <h3 class="text-2xl font-bold text-gray-900">Ksh <?php echo number_format($analytics_data['avg_order_value'], 0); ?></h3>
                        <p class="text-gray-600">Avg Order Value</p>
                    </div>
                </div>
            </div>

            <!-- Active Customers -->
            <div class="bg-white rounded-xl shadow-lg p-6 border-l-4 border-purple-500 hover:shadow-xl transition-shadow">
                <div class="flex items-center justify-between">
                    <div class="flex items-center">
                        <div class="p-3 bg-gradient-to-br from-purple-100 to-purple-200 rounded-lg">
                            <i class="fas fa-users text-2xl text-purple-600"></i>
                        </div>
                        <div class="ml-4">
                            <h3 class="text-2xl font-bold text-gray-900"><?php echo number_format($analytics_data['active_customers']); ?></h3>
                            <p class="text-gray-600">Active Customers</p>
                            <?php
                            // Calculate customer engagement rate
                            $totalCustomers = $analytics_data['active_customers'];
                            $customersWithOrders = count($analytics_data['top_customers']);
                            if ($totalCustomers > 0 && $customersWithOrders > 0) {
                                $engagementRate = ($customersWithOrders / $totalCustomers) * 100;
                                echo '<p class="text-xs text-purple-600 mt-1"><i class="fas fa-chart-pie mr-1"></i>' . number_format($engagementRate, 1) . '% engagement</p>';
                            }
                            ?>
                        </div>
                    </div>
                    <div class="text-right">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-purple-100 text-purple-800">
                            <i class="fas fa-check-circle mr-1"></i>Active
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Main Content Area -->
<div class="container mx-auto px-6 py-8">
    <!-- Charts Section -->
    <div class="grid grid-cols-1 lg:grid-cols-2 xl:grid-cols-4 gap-8 mb-8">
        <!-- Sales Trend Chart -->
        <div class="bg-white rounded-xl shadow-lg p-6">
            <h3 class="text-lg font-semibold text-gray-800 mb-4">Sales Trend</h3>
            <div class="h-64">
                <canvas id="salesChart"></canvas>
            </div>
        </div>

        <!-- Sales Trend (Last 6 Months) - Matching dashboard.php -->
        <div class="bg-white rounded-xl shadow-lg p-6">
            <h3 class="text-xl font-bold text-gray-900 mb-6 flex items-center">
                <i class="fas fa-chart-line mr-3 text-primary"></i>
                Sales Trend (Last 6 Months)
            </h3>
            <div class="space-y-4">
                <?php if (!empty($analytics_data['monthly_sales_trend'])): ?>
                    <?php foreach (array_reverse($analytics_data['monthly_sales_trend']) as $month): ?>
                        <?php
                        $monthName = date('M Y', strtotime($month['month'] . '-01'));
                        $percentage = $month['sales'] > 0 ? 100 : 0;
                        ?><div class="flex items-center justify-between">
                            <span class="text-gray-700 font-medium"><?php echo $monthName; ?></span>
                            <div class="flex items-center space-x-3">
                                <span class="text-lg font-bold text-gray-900">Ksh <?php echo number_format($month['sales'], 0); ?></span>
                                <div class="w-20 bg-gray-200 rounded-full h-2">
                                    <div class="bg-accent h-2 rounded-full" style="width: <?php echo $percentage; ?>%"></div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="text-center py-8">
                        <i class="fas fa-chart-line text-4xl text-gray-300 mb-3"></i>
                        <p class="text-gray-500">No sales data available for the last 6 months.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Order Status Distribution -->
        <div class="bg-white rounded-xl shadow-lg p-6">
            <h3 class="text-lg font-semibold text-gray-800 mb-4">Order Status Distribution</h3>
            <div class="space-y-4">
                <?php if (!empty($analytics_data['status_distribution'])): ?>
                    <?php
                    $totalStatusOrders = array_sum(array_values($analytics_data['status_distribution']));
                    foreach ($analytics_data['status_distribution'] as $status => $count):
                        $percentage = $totalStatusOrders > 0 ? ($count / $totalStatusOrders) * 100 : 0;
                    ?>
                    <div class="flex items-center justify-between">
                        <div class="flex items-center">
                            <div class="w-4 h-4 rounded-full mr-3
                                <?php
                                switch(strtolower($status)) {
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
                            <span class="text-gray-700 font-medium"><?php echo ucfirst(str_replace('_', ' ', $status)); ?></span>
                        </div>
                        <div class="flex items-center space-x-2">
                            <span class="text-sm font-bold text-gray-900"><?php echo number_format($count); ?></span>
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
                        <p class="text-gray-500">No order status data available for the selected period.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Order Status Chart -->
        <div class="bg-white rounded-xl shadow-lg p-6">
            <h3 class="text-lg font-semibold text-gray-800 mb-4">Status Chart</h3>
            <div class="h-64">
                <canvas id="statusChart"></canvas>
            </div>
        </div>
    </div>

    <!-- Detailed Reports Section -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
        <!-- Top Customers -->
        <div class="bg-white rounded-xl shadow-lg p-6">
            <h3 class="text-lg font-semibold text-gray-800 mb-4">Top Customers</h3>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Customer</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Orders</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Total Spent</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php if (!empty($analytics_data['top_customers'])): ?>
                            <?php foreach ($analytics_data['top_customers'] as $customer): ?>
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center">
                                            <div class="flex-shrink-0 h-10 w-10">
                                                <div class="h-10 w-10 rounded-full bg-primary flex items-center justify-center text-white font-semibold">
                                                    <?php echo strtoupper(substr($customer['name'], 0, 1)); ?>
                                                </div>
                                            </div>
                                            <div class="ml-4">
                                                <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($customer['name']); ?></div>
                                                <div class="text-sm text-gray-500"><?php echo htmlspecialchars($customer['email']); ?></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        <?php echo number_format($customer['order_count']); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                        Ksh <?php echo number_format($customer['total_spent'], 0); ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="3" class="px-6 py-4 text-center text-sm text-gray-500">
                                    No customer data available for the selected period.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Popular Menu Items -->
        <div class="bg-white rounded-xl shadow-lg p-6">
            <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center">
                <i class="fas fa-fire text-orange-500 mr-2"></i>
                Popular Menu Items
            </h3>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Rank</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Item</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Category</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Qty Sold</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Revenue</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php if (!empty($analytics_data['popular_items'])): ?>
                            <?php foreach ($analytics_data['popular_items'] as $index => $item): 
                                $rank = $index + 1;
                                // Determine rank badge color
                                $rankBadgeClass = '';
                                $rankIcon = '';
                                if ($rank === 1) {
                                    $rankBadgeClass = 'bg-yellow-100 text-yellow-800 border-yellow-300';
                                    $rankIcon = '<i class="fas fa-crown text-yellow-500 mr-1"></i>';
                                } elseif ($rank === 2) {
                                    $rankBadgeClass = 'bg-gray-100 text-gray-800 border-gray-300';
                                    $rankIcon = '<i class="fas fa-medal text-gray-400 mr-1"></i>';
                                } elseif ($rank === 3) {
                                    $rankBadgeClass = 'bg-orange-100 text-orange-800 border-orange-300';
                                    $rankIcon = '<i class="fas fa-award text-orange-400 mr-1"></i>';
                                } else {
                                    $rankBadgeClass = 'bg-blue-50 text-blue-700 border-blue-200';
                                }
                                
                                // Category badge colors
                                $categoryColors = [
                                    'Breakfast' => 'bg-yellow-100 text-yellow-800',
                                    'Lunch' => 'bg-green-100 text-green-800',
                                    'Dinner' => 'bg-purple-100 text-purple-800',
                                    'Appetizers' => 'bg-pink-100 text-pink-800',
                                    'Beverages' => 'bg-blue-100 text-blue-800',
                                    'Specials' => 'bg-red-100 text-red-800',
                                    'Dessert' => 'bg-indigo-100 text-indigo-800'
                                ];
                                $categoryClass = $categoryColors[$item['category']] ?? 'bg-gray-100 text-gray-800';
                            ?>
                                <tr class="hover:bg-gray-50 transition-colors">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-semibold border <?php echo $rankBadgeClass; ?>">
                                            <?php echo $rankIcon; ?>#<?php echo $rank; ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="flex items-center">
                                            <div class="flex-shrink-0 h-10 w-10 bg-gradient-to-br from-primary to-orange-400 rounded-lg flex items-center justify-center text-white font-bold">
                                                <?php echo strtoupper(substr($item['name'], 0, 1)); ?>
                                            </div>
                                            <div class="ml-3">
                                                <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($item['name']); ?></div>
                                                <div class="text-xs text-gray-500"><?php echo number_format($item['order_count']); ?> orders</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?php echo $categoryClass; ?>">
                                            <?php echo htmlspecialchars($item['category'] ?? 'N/A'); ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center">
                                            <i class="fas fa-box text-gray-400 mr-2"></i>
                                            <span class="text-sm font-semibold text-gray-900"><?php echo number_format($item['total_quantity']); ?></span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center">
                                            <i class="fas fa-coins text-green-500 mr-2"></i>
                                            <span class="text-sm font-bold text-green-600">Ksh <?php echo number_format($item['total_revenue'], 0); ?></span>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="px-6 py-8 text-center">
                                    <div class="flex flex-col items-center justify-center">
                                        <i class="fas fa-utensils text-4xl text-gray-300 mb-3"></i>
                                        <p class="text-sm text-gray-500 font-medium">No menu items data available</p>
                                        <p class="text-xs text-gray-400 mt-1">Orders with menu items will appear here</p>
                                    </div>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Payment Methods & Customer Growth -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
        <!-- Payment Methods -->
        <div class="bg-white rounded-xl shadow-lg p-6">
            <h3 class="text-lg font-semibold text-gray-800 mb-4">Payment Methods</h3>
            <div class="space-y-4">
                <?php if (!empty($analytics_data['payment_methods'])): ?>
                    <?php foreach ($analytics_data['payment_methods'] as $payment): ?>
                        <div class="flex items-center justify-between">
                            <div class="flex items-center">
                                <div class="w-10 h-10 bg-gray-100 rounded-lg flex items-center justify-center mr-3">
                                    <i class="fas fa-credit-card text-gray-600"></i>
                                </div>
                                <div>
                                    <p class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars(ucfirst($payment['payment_method'])); ?></p>
                                    <p class="text-xs text-gray-500"><?php echo number_format($payment['count']); ?> orders</p>
                                </div>
                            </div>
                            <div class="text-right">
                                <p class="text-sm font-medium text-gray-900">Ksh <?php echo number_format($payment['total_amount'], 0); ?></p>
                                <p class="text-xs text-gray-500">
                                    <?php
                                    $percentage = $analytics_data['total_revenue'] > 0 ?
                                        ($payment['total_amount'] / $analytics_data['total_revenue']) * 100 : 0;
                                    echo number_format($percentage, 1) . '%';
                                    ?>
                                </p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p class="text-center text-gray-500">No payment data available.</p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Customer Growth -->
        <div class="bg-white rounded-xl shadow-lg p-6">
            <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center justify-between">
                <span class="flex items-center">
                    <i class="fas fa-chart-line text-green-500 mr-2"></i>
                    Customer Growth
                </span>
                <?php if (!empty($analytics_data['customer_growth'])): ?>
                    <span class="text-xs font-normal text-gray-500">
                        Total: <?php echo number_format(array_sum(array_column($analytics_data['customer_growth'], 'new_customers'))); ?> new
                    </span>
                <?php endif; ?>
            </h3>
            <div class="space-y-3">
                <?php if (!empty($analytics_data['customer_growth'])): ?>
                    <?php 
                    // Calculate max for progress bar scaling
                    $maxCustomers = max(array_column($analytics_data['customer_growth'], 'new_customers'));
                    $totalNewCustomers = array_sum(array_column($analytics_data['customer_growth'], 'new_customers'));
                    
                    foreach ($analytics_data['customer_growth'] as $index => $growth): 
                        $percentage = $maxCustomers > 0 ? ($growth['new_customers'] / $maxCustomers) * 100 : 0;
                        $sharePercentage = $totalNewCustomers > 0 ? ($growth['new_customers'] / $totalNewCustomers) * 100 : 0;
                        
                        // Calculate growth trend (compare with previous month if available)
                        $trend = null;
                        $trendIcon = '';
                        $trendClass = '';
                        if ($index < count($analytics_data['customer_growth']) - 1) {
                            $prevMonth = $analytics_data['customer_growth'][$index + 1];
                            if ($growth['new_customers'] > $prevMonth['new_customers']) {
                                $trend = 'up';
                                $trendIcon = '<i class="fas fa-arrow-up text-green-500 text-xs ml-1"></i>';
                                $trendClass = 'text-green-600';
                            } elseif ($growth['new_customers'] < $prevMonth['new_customers']) {
                                $trend = 'down';
                                $trendIcon = '<i class="fas fa-arrow-down text-red-500 text-xs ml-1"></i>';
                                $trendClass = 'text-red-600';
                            } else {
                                $trend = 'stable';
                                $trendIcon = '<i class="fas fa-minus text-gray-400 text-xs ml-1"></i>';
                                $trendClass = 'text-gray-600';
                            }
                        }
                    ?>
                        <div class="bg-gradient-to-r from-green-50 to-emerald-50 rounded-lg p-4 border border-green-100 hover:shadow-md transition-shadow">
                            <div class="flex items-center justify-between mb-2">
                                <div class="flex items-center">
                                    <div class="w-10 h-10 bg-gradient-to-br from-green-500 to-emerald-600 rounded-lg flex items-center justify-center mr-3 shadow-sm">
                                        <i class="fas fa-user-plus text-white"></i>
                                    </div>
                                    <div>
                                        <p class="text-sm font-semibold text-gray-900"><?php echo $growth['month_name']; ?></p>
                                        <p class="text-xs text-gray-500"><?php echo number_format($sharePercentage, 1); ?>% of total growth</p>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <div class="flex items-center justify-end">
                                        <span class="text-2xl font-bold <?php echo $trendClass ?: 'text-green-600'; ?>">
                                            +<?php echo number_format($growth['new_customers']); ?>
                                        </span>
                                        <?php echo $trendIcon; ?>
                                    </div>
                                    <p class="text-xs text-gray-500">new customers</p>
                                </div>
                            </div>
                            <!-- Progress Bar -->
                            <div class="w-full bg-green-200 rounded-full h-2 overflow-hidden">
                                <div class="bg-gradient-to-r from-green-500 to-emerald-600 h-2 rounded-full transition-all duration-500 ease-out shadow-sm"
                                     style="width: <?php echo $percentage; ?>%">
                                </div>
                            </div>
                            <div class="flex items-center justify-between mt-1">
                                <span class="text-xs text-gray-500">
                                    <?php 
                                    if ($growth['new_customers'] == $maxCustomers) {
                                        echo '<i class="fas fa-star text-yellow-500 mr-1"></i>Peak month';
                                    } elseif ($percentage >= 75) {
                                        echo '<i class="fas fa-fire text-orange-500 mr-1"></i>High growth';
                                    } elseif ($percentage >= 50) {
                                        echo '<i class="fas fa-check-circle text-green-500 mr-1"></i>Good growth';
                                    } else {
                                        echo '<i class="fas fa-info-circle text-blue-500 mr-1"></i>Moderate';
                                    }
                                    ?>
                                </span>
                                <span class="text-xs font-medium text-green-700"><?php echo number_format($percentage, 0); ?>%</span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    
                    <!-- Growth Summary -->
                    <?php if (count($analytics_data['customer_growth']) > 1): ?>
                        <div class="mt-4 p-3 bg-blue-50 rounded-lg border border-blue-100">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center">
                                    <i class="fas fa-info-circle text-blue-500 mr-2"></i>
                                    <span class="text-xs font-medium text-blue-900">Growth Insight</span>
                                </div>
                                <span class="text-xs text-blue-700">
                                    Avg: <?php echo number_format($totalNewCustomers / count($analytics_data['customer_growth']), 1); ?> customers/month
                                </span>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                <?php else: ?>
                    <div class="text-center py-8">
                        <div class="flex flex-col items-center justify-center">
                            <i class="fas fa-users text-4xl text-gray-300 mb-3"></i>
                            <p class="text-sm text-gray-500 font-medium">No customer growth data available</p>
                            <p class="text-xs text-gray-400 mt-1">New customer registrations will appear here</p>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Export Options -->
    <div class="bg-white rounded-xl shadow-lg p-6">
        <h3 class="text-lg font-semibold text-gray-800 mb-4">Export Reports</h3>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <a href="export_reports.php?type=sales&start=<?php echo $start_date; ?>&end=<?php echo $end_date; ?>"
               class="bg-primary hover:bg-primary-dark text-white px-4 py-2 rounded-lg transition-colors inline-flex items-center justify-center">
                <i class="fas fa-file-export mr-2"></i> Export Sales Report
            </a>
            <a href="export_reports.php?type=customers&start=<?php echo $start_date; ?>&end=<?php echo $end_date; ?>"
               class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg transition-colors inline-flex items-center justify-center">
                <i class="fas fa-users mr-2"></i> Export Customers Report
            </a>
            <a href="export_reports.php?type=products&start=<?php echo $start_date; ?>&end=<?php echo $end_date; ?>"
               class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg transition-colors inline-flex items-center justify-center">
                <i class="fas fa-utensils mr-2"></i> Export Products Report
            </a>
        </div>
    </div>
</div>

<!-- jQuery (required for daterangepicker) -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<!-- Moment.js (required for daterangepicker) -->
<script src="https://cdn.jsdelivr.net/npm/moment@2.29.4/moment.min.js"></script>
<!-- Chart.js and Date Range Picker -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.css">
<script src="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.min.js"></script>

<script>
// Date Range Picker
$(document).ready(function() {
    $('input[name="date_range"]').daterangepicker({
        opens: 'left',
        startDate: moment('<?php echo $start_date; ?>'),
        endDate: moment('<?php echo $end_date; ?>'),
        locale: {
            format: 'MM/DD/YYYY'
        }
    });

    // Prepare chart data
    const chartLabels = <?php echo json_encode(array_column($analytics_data['daily_sales'], 'order_date')); ?>;
    const orderData = <?php echo json_encode(array_column($analytics_data['daily_sales'], 'order_count')); ?>;
    const revenueData = <?php echo json_encode(array_column($analytics_data['daily_sales'], 'daily_revenue')); ?>;

    // Monthly sales trend data (Last 6 Months)
    const monthlyTrendLabels = <?php echo json_encode(array_column($analytics_data['monthly_sales_trend'], 'month')); ?>;
    const monthlyTrendData = <?php echo json_encode(array_column($analytics_data['monthly_sales_trend'], 'sales')); ?>;
    const monthlyTrendOrderCounts = <?php echo json_encode(array_column($analytics_data['monthly_sales_trend'], 'order_count')); ?>;

    // Sales Trend Chart
    const salesCtx = document.getElementById('salesChart').getContext('2d');
    const salesChart = new Chart(salesCtx, {
        type: 'line',
        data: {
            labels: chartLabels.map(date => new Date(date).toLocaleDateString('en-US', { month: 'short', day: 'numeric' })),
            datasets: [{
                label: 'Orders',
                data: orderData,
                borderColor: 'rgb(59, 130, 246)',
                backgroundColor: 'rgba(59, 130, 246, 0.1)',
                tension: 0.3,
                fill: true
            }, {
                label: 'Revenue (Ksh)',
                data: revenueData,
                borderColor: 'rgb(16, 185, 129)',
                backgroundColor: 'rgba(16, 185, 129, 0.1)',
                tension: 0.3,
                yAxisID: 'y1',
                type: 'line'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        precision: 0
                    }
                },
                y1: {
                    beginAtZero: true,
                    position: 'right',
                    ticks: {
                        callback: function(value) {
                            return 'Ksh ' + value.toLocaleString();
                        }
                    }
                }
            },
            plugins: {
                legend: {
                    display: true,
                    position: 'top'
                }
            }
        }
    });

    // Enhanced Status Distribution Chart with proper color coding
    const statusLabels = <?php echo json_encode(array_keys($analytics_data['status_distribution'])); ?>;
    const statusData = <?php echo json_encode(array_values($analytics_data['status_distribution'])); ?>;

    // Enhanced color mapping matching dashboard.php
    const statusColors = {
        'pending': 'rgba(245, 158, 11, 0.8)',      // Yellow
        'processing': 'rgba(59, 130, 246, 0.8)',   // Blue
        'delivered': 'rgba(16, 185, 129, 0.8)',    // Green
        'completed': 'rgba(16, 185, 129, 0.8)',    // Green
        'cancelled': 'rgba(239, 68, 68, 0.8)',     // Red
        'pending_payment': 'rgba(249, 115, 22, 0.8)', // Orange
    };

    // Generate color array based on status labels
    const chartColors = statusLabels.map(status => {
        const statusKey = status.toLowerCase().replace(' ', '_');
        return statusColors[statusKey] || 'rgba(156, 163, 175, 0.8)'; // Default gray
    });

    const statusCtx = document.getElementById('statusChart').getContext('2d');
    const statusChart = new Chart(statusCtx, {
        type: 'doughnut',
        data: {
            labels: statusLabels.map(status => status.charAt(0).toUpperCase() + status.slice(1)),
            datasets: [{
                data: statusData,
                backgroundColor: chartColors,
                borderWidth: 0
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'right',
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            const label = context.label || '';
                            const value = context.raw || 0;
                            const total = context.dataset.data.reduce((a, b) => a + b, 0);
                            const percentage = Math.round((value / total) * 100);
                            return `${label}: ${value} (${percentage}%)`;
                        }
                    }
                }
            }
        }
    });
});
</script>

<?php
// Include footer
include 'includes/footer.php';
?>
