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
    // Basic Order Statistics
    $stmt = $pdo->prepare("
        SELECT
            COUNT(*) as total_orders,
            SUM(total_amount) as total_revenue,
            AVG(total_amount) as avg_order_value
        FROM orders
        WHERE DATE(created_at) BETWEEN ? AND ?
    ");
    $stmt->execute([$start_date, $end_date]);
    $basic_stats = $stmt->fetch(PDO::FETCH_ASSOC);

    $analytics_data['total_orders'] = $basic_stats['total_orders'] ?? 0;
    $analytics_data['total_revenue'] = $basic_stats['total_revenue'] ?? 0;
    $analytics_data['avg_order_value'] = $basic_stats['avg_order_value'] ?? 0;

    // Order Status Distribution
    $stmt = $pdo->prepare("
        SELECT status, COUNT(*) as count
        FROM orders
        WHERE DATE(created_at) BETWEEN ? AND ?
        GROUP BY status
    ");
    $stmt->execute([$start_date, $end_date]);
    $status_distribution = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $analytics_data['status_distribution'] = [];
    foreach ($status_distribution as $status) {
        $analytics_data['status_distribution'][$status['status']] = $status['count'];
    }

    // Daily Sales Trend
    $stmt = $pdo->prepare("
        SELECT
            DATE(created_at) as order_date,
            COUNT(*) as order_count,
            SUM(total_amount) as daily_revenue
        FROM orders
        WHERE DATE(created_at) BETWEEN ? AND ?
        GROUP BY DATE(created_at)
        ORDER BY order_date
    ");
    $stmt->execute([$start_date, $end_date]);
    $daily_sales = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $analytics_data['daily_sales'] = $daily_sales;

    // Top Customers
    $stmt = $pdo->prepare("
        SELECT
            u.name,
            u.email,
            COUNT(o.id) as order_count,
            SUM(o.total_amount) as total_spent
        FROM orders o
        JOIN users u ON o.user_id = u.id
        WHERE DATE(o.created_at) BETWEEN ? AND ?
        GROUP BY u.id
        ORDER BY total_spent DESC
        LIMIT 10
    ");
    $stmt->execute([$start_date, $end_date]);
    $analytics_data['top_customers'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Popular Menu Items (if menu_items table exists)
    try {
        $stmt = $pdo->prepare("
            SELECT
                mi.name,
                mi.category,
                COUNT(oi.id) as order_count,
                SUM(oi.quantity) as total_quantity,
                SUM(oi.total) as total_revenue
            FROM order_items oi
            JOIN menu_items mi ON oi.item_id = mi.id
            JOIN orders o ON oi.order_id = o.id
            WHERE DATE(o.created_at) BETWEEN ? AND ?
            GROUP BY mi.id
            ORDER BY total_revenue DESC
            LIMIT 10
        ");
        $stmt->execute([$start_date, $end_date]);
        $analytics_data['popular_items'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        // Menu items table might not exist in current schema
        $analytics_data['popular_items'] = [];
    }

    // Payment Method Distribution
    $stmt = $pdo->prepare("
        SELECT
            payment_method,
            COUNT(*) as count,
            SUM(total_amount) as total_amount
        FROM orders
        WHERE DATE(created_at) BETWEEN ? AND ?
        GROUP BY payment_method
    ");
    $stmt->execute([$start_date, $end_date]);
    $analytics_data['payment_methods'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Customer Growth (new customers per month)
    $stmt = $pdo->prepare("
        SELECT
            DATE_FORMAT(created_at, '%Y-%m') as month,
            COUNT(*) as new_customers
        FROM users
        WHERE role = 'customer' AND DATE(created_at) BETWEEN ? AND ?
        GROUP BY DATE_FORMAT(created_at, '%Y-%m')
        ORDER BY month
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
        'customer_growth' => []
    ];
}

// 6. Include Header
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
            <div class="bg-white rounded-xl shadow-lg p-6 border-l-4 border-purple-500">
                <div class="flex items-center">
                    <div class="p-3 bg-purple-100 rounded-lg">
                        <i class="fas fa-users text-2xl text-purple-600"></i>
                    </div>
                    <div class="ml-4">
                        <h3 class="text-2xl font-bold text-gray-900"><?php echo number_format(count($analytics_data['top_customers'])); ?></h3>
                        <p class="text-gray-600">Active Customers</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Main Content Area -->
<div class="container mx-auto px-6 py-8">
    <!-- Charts Section -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
        <!-- Sales Trend Chart -->
        <div class="bg-white rounded-xl shadow-lg p-6">
            <h3 class="text-lg font-semibold text-gray-800 mb-4">Sales Trend</h3>
            <div class="h-64">
                <canvas id="salesChart"></canvas>
            </div>
        </div>

        <!-- Order Status Distribution -->
        <div class="bg-white rounded-xl shadow-lg p-6">
            <h3 class="text-lg font-semibold text-gray-800 mb-4">Order Status Distribution</h3>
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
            <h3 class="text-lg font-semibold text-gray-800 mb-4">Popular Menu Items</h3>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Item</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Category</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Orders</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Revenue</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php if (!empty($analytics_data['popular_items'])): ?>
                            <?php foreach ($analytics_data['popular_items'] as $item): ?>
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($item['name']); ?></div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <?php echo htmlspecialchars($item['category'] ?? 'N/A'); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        <?php echo number_format($item['total_quantity']); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                        Ksh <?php echo number_format($item['total_revenue'], 0); ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="4" class="px-6 py-4 text-center text-sm text-gray-500">
                                    <?php echo empty($analytics_data['popular_items']) ? 'No menu items data available.' : 'No items ordered in selected period.'; ?>
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
            <h3 class="text-lg font-semibold text-gray-800 mb-4">Customer Growth</h3>
            <div class="space-y-4">
                <?php if (!empty($analytics_data['customer_growth'])): ?>
                    <?php foreach ($analytics_data['customer_growth'] as $growth): ?>
                        <div class="flex items-center justify-between">
                            <div class="flex items-center">
                                <div class="w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center mr-3">
                                    <i class="fas fa-user-plus text-green-600"></i>
                                </div>
                                <div>
                                    <p class="text-sm font-medium text-gray-900"><?php echo date('F Y', strtotime($growth['month'] . '-01')); ?></p>
                                </div>
                            </div>
                            <div class="text-right">
                                <p class="text-sm font-medium text-gray-900">+<?php echo number_format($growth['new_customers']); ?></p>
                                <p class="text-xs text-gray-500">new customers</p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p class="text-center text-gray-500">No customer growth data available.</p>
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

<!-- Chart.js and Date Range Picker -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.min.js"></script>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.css">

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

    // Status Distribution Chart
    const statusLabels = <?php echo json_encode(array_keys($analytics_data['status_distribution'])); ?>;
    const statusData = <?php echo json_encode(array_values($analytics_data['status_distribution'])); ?>;

    const statusCtx = document.getElementById('statusChart').getContext('2d');
    const statusChart = new Chart(statusCtx, {
        type: 'doughnut',
        data: {
            labels: statusLabels.map(status => status.charAt(0).toUpperCase() + status.slice(1)),
            datasets: [{
                data: statusData,
                backgroundColor: [
                    'rgba(59, 130, 246, 0.8)',
                    'rgba(16, 185, 129, 0.8)',
                    'rgba(245, 158, 11, 0.8)',
                    'rgba(239, 68, 68, 0.8)',
                    'rgba(156, 163, 175, 0.8)'
                ],
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

<!-- Close HTML structure as required by header.php -->
</div>
</div>
</div>
</body>
</html>
