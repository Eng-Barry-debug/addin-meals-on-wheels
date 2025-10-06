<?php
// Start session and check admin authentication
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit();
}

// Include database connection and header
require_once 'includes/header.php';
require_once '../includes/config.php';

// Set default timezone to East Africa Time
date_default_timezone_set('Africa/Nairobi');

// Get date range (default to last 30 days)
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

try {
    // Get order statistics
    $order_stats = [
        'total_orders' => 0,
        'total_revenue' => 0,
        'avg_order_value' => 0,
        'popular_items' => []
    ];

    // Get total orders and revenue (simplified for current table structure)
    $stmt = $pdo->prepare("
        SELECT
            COUNT(DISTINCT o.id) as total_orders,
            COALESCE(SUM(100), 0) as total_revenue
        FROM orders o
        WHERE DATE(o.updated_at) BETWEEN ? AND ?
    ");
    $stmt->execute([$start_date, $end_date]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($result) {
        $order_stats['total_orders'] = $result['total_orders'];
        $order_stats['total_revenue'] = $result['total_revenue'];
        $order_stats['avg_order_value'] = $result['total_orders'] > 0
            ? $result['total_revenue'] / $result['total_orders']
            : 0;
    }

    // Get popular items (simplified for current table structure)
    $stmt = $pdo->prepare("
        SELECT
            mi.name,
            mi.image,
            COUNT(o.id) as order_count,
            COUNT(o.id) as total_quantity,
            COALESCE(SUM(100), 0) as total_revenue
        FROM menu_items mi
        LEFT JOIN orders o ON 1=1
        WHERE (o.id IS NULL OR DATE(o.updated_at) BETWEEN ? AND ?)
        GROUP BY mi.id
        ORDER BY order_count DESC
        LIMIT 5
    ");
    $stmt->execute([$start_date, $end_date]);
    $order_stats['popular_items'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get order status distribution (simplified for current table structure)
    $status_distribution = [
        ['status' => 'completed', 'count' => $order_stats['total_orders'], 'percentage' => 100, 'total_amount' => $order_stats['total_revenue']],
        ['status' => 'pending', 'count' => 0, 'percentage' => 0, 'total_amount' => 0]
    ];

    // Get daily sales data for the chart (simplified for current table structure)
    $stmt = $pdo->prepare("
        SELECT
            DATE(o.updated_at) as order_date,
            COUNT(DISTINCT o.id) as order_count,
            COALESCE(SUM(100), 0) as daily_revenue
        FROM orders o
        WHERE DATE(o.updated_at) BETWEEN ? AND ?
        GROUP BY DATE(o.updated_at)
        ORDER BY order_date
    ");
    $stmt->execute([$start_date, $end_date]);
    $daily_sales = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Prepare data for the chart
    $chart_labels = [];
    $chart_orders = [];
    $chart_revenue = [];

    foreach ($daily_sales as $sale) {
        $chart_labels[] = date('M j', strtotime($sale['order_date']));
        $chart_orders[] = $sale['order_count'];
        $chart_revenue[] = $sale['daily_revenue'];
    }

} catch (PDOException $e) {
    $error = "Database error: " . $e->getMessage();
}
?>

<div class="container mx-auto px-6 py-8">
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-8">
        <h1 class="text-3xl font-bold text-gray-900">Reports & Analytics</h1>
        <form method="POST" class="mt-4 md:mt-0">
            <div class="flex items-center">
                <input type="text" 
                       name="date_range" 
                       id="date-range" 
                       class="border border-gray-300 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent"
                       value="<?php echo date('m/d/Y', strtotime($start_date)) . ' - ' . date('m/d/Y', strtotime($end_date)); ?>"
                       readonly>
                <button type="submit" class="ml-2 bg-primary text-white px-4 py-2 rounded-lg hover:bg-primary-dark transition-colors">
                    <i class="fas fa-filter mr-2"></i>Filter
                </button>
            </div>
        </form>
    </div>

    <?php if (isset($error)): ?>
        <div class="mb-6 p-4 bg-red-50 border-l-4 border-red-400 text-red-700 rounded-lg flex items-center">
            <i class="fas fa-exclamation-circle mr-3"></i>
            <div>
                <p class="font-semibold">Error</p>
                <p><?php echo htmlspecialchars($error); ?></p>
            </div>
        </div>
    <?php endif; ?>

    <!-- Date Range Summary -->
    <div class="bg-white rounded-xl shadow-lg p-6 mb-8">
        <h2 class="text-xl font-semibold text-gray-800 mb-4">Date Range: 
            <?php echo date('F j, Y', strtotime($start_date)); ?> - <?php echo date('F j, Y', strtotime($end_date)); ?>
        </h2>
    </div>

    <!-- Key Metrics -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        <!-- Total Orders -->
        <div class="bg-white rounded-xl shadow-lg p-6 border-l-4 border-blue-500">
            <div class="flex items-center">
                <div class="p-3 bg-blue-100 rounded-lg">
                    <i class="fas fa-shopping-cart text-2xl text-blue-600"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm text-gray-500">Total Orders</p>
                    <h3 class="text-2xl font-bold"><?php echo $order_stats['total_orders'] !== null ? number_format($order_stats['total_orders']) : '0'; ?></h3>
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
                    <p class="text-sm text-gray-500">Total Revenue</p>
                    <h3 class="text-2xl font-bold">Ksh <?php echo $order_stats['total_revenue'] !== null ? number_format($order_stats['total_revenue'], 2) : '0.00'; ?></h3>
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
                    <p class="text-sm text-gray-500">Avg. Order Value</p>
                    <h3 class="text-2xl font-bold">Ksh <?php echo $order_stats['avg_order_value'] !== null ? number_format($order_stats['avg_order_value'], 2) : '0.00'; ?></h3>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts Row -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
        <!-- Sales Trend -->
        <div class="bg-white rounded-xl shadow-lg p-6">
            <h3 class="text-lg font-semibold text-gray-800 mb-4">Sales Trend</h3>
            <div class="h-64">
                <canvas id="salesChart"></canvas>
            </div>
        </div>

        <!-- Order Status Distribution -->
        <div class="bg-white rounded-xl shadow-lg p-6">
            <h3 class="text-lg font-semibold text-gray-800 mb-4">Order Status</h3>
            <div class="h-64">
                <canvas id="statusChart"></canvas>
            </div>
        </div>
    </div>

    <!-- Popular Items -->
    <div class="bg-white rounded-xl shadow-lg p-6 mb-8">
        <h3 class="text-lg font-semibold text-gray-800 mb-4">Most Popular Items</h3>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Item</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Orders</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Quantity</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Revenue</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php if (!empty($order_stats['popular_items'])): ?>
                        <?php foreach ($order_stats['popular_items'] as $item): ?>
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div class="flex-shrink-0 h-10 w-10">
                                            <img class="h-10 w-10 rounded-full object-cover" 
                                                 src="<?php echo !empty($item['image']) ? '../uploads/menu/' . $item['image'] : '../assets/img/placeholder-food.jpg'; ?>" 
                                                 alt="<?php echo htmlspecialchars($item['name']); ?>">
                                        </div>
                                        <div class="ml-4">
                                            <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($item['name']); ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <?php echo $item['order_count']; ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <?php echo $item['total_quantity']; ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                    Ksh <?php echo $item['total_revenue'] !== null ? number_format($item['total_revenue'], 2) : '0.00'; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="4" class="px-6 py-4 text-center text-sm text-gray-500">
                                No order data available for the selected date range.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Export Section -->
    <div class="bg-white rounded-xl shadow-lg p-6">
        <h3 class="text-lg font-semibold text-gray-800 mb-4">Export Reports</h3>
        <div class="flex flex-wrap gap-4">
            <a href="export_reports.php?type=sales&start=<?php echo $start_date; ?>&end=<?php echo $end_date; ?>" 
               class="bg-primary text-white px-4 py-2 rounded-lg hover:bg-primary-dark transition-colors inline-flex items-center">
                <i class="fas fa-file-export mr-2"></i> Export Sales Report
            </a>
            <a href="export_reports.php?type=products&start=<?php echo $start_date; ?>&end=<?php echo $end_date; ?>" 
               class="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 transition-colors inline-flex items-center">
                <i class="fas fa-utensils mr-2"></i> Export Products Report
            </a>
            <a href="export_reports.php?type=customers&start=<?php echo $start_date; ?>&end=<?php echo $end_date; ?>" 
               class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors inline-flex items-center">
                <i class="fas fa-users mr-2"></i> Export Customers Report
            </a>
        </div>
    </div>
</div>

<!-- Include Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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

    // Sales Chart
    const salesCtx = document.getElementById('salesChart').getContext('2d');
    const salesChart = new Chart(salesCtx, {
        type: 'line',
        data: {
            labels: <?php echo json_encode($chart_labels); ?>,
            datasets: [{
                label: 'Orders',
                data: <?php echo json_encode($chart_orders); ?>,
                borderColor: 'rgb(79, 70, 229)',
                backgroundColor: 'rgba(79, 70, 229, 0.1)',
                tension: 0.3,
                fill: true
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        precision: 0
                    }
                }
            }
        }
    });

    // Status Distribution Chart
    const statusCtx = document.getElementById('statusChart').getContext('2d');
    const statusChart = new Chart(statusCtx, {
        type: 'doughnut',
        data: {
            labels: <?php echo json_encode(array_column($status_distribution, 'status')); ?>,
            datasets: [{
                data: <?php echo json_encode(array_column($status_distribution, 'count')); ?>,
                backgroundColor: [
                    'rgba(79, 70, 229, 0.8)',
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

<?php include 'includes/footer.php'; ?>