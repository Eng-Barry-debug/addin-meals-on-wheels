<?php
// reports.php - Delivery Performance Reports

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
$page_title = 'Performance Reports';

// Initialize variables
$user_id = $_SESSION['user_id'];
$date_range = $_GET['range'] ?? '7days';
$start_date = $_GET['start_date'] ?? '';
$end_date = $_GET['end_date'] ?? '';

// Calculate date range
$end_date = $end_date ?: date('Y-m-d');
switch($date_range) {
    case 'today':
        $start_date = date('Y-m-d');
        break;
    case '7days':
        $start_date = date('Y-m-d', strtotime('-7 days'));
        break;
    case '30days':
        $start_date = date('Y-m-d', strtotime('-30 days'));
        break;
    case '90days':
        $start_date = date('Y-m-d', strtotime('-90 days'));
        break;
    default:
        $start_date = $start_date ?: date('Y-m-d', strtotime('-7 days'));
}

// Get performance data
try {
    global $pdo;

    // Overall Performance Metrics
    $stmt = $pdo->prepare("
        SELECT
            COUNT(*) as total_deliveries,
            SUM(CASE WHEN status = 'delivered' THEN 1 ELSE 0 END) as completed_deliveries,
            AVG(CASE WHEN status = 'delivered' THEN TIMESTAMPDIFF(MINUTE, created_at, updated_at) END) as avg_delivery_time,
            SUM(CASE WHEN status = 'delivered' THEN total * 0.1 ELSE 0 END) as total_earnings
        FROM orders
        WHERE updated_at BETWEEN ? AND ?
    ");
    $stmt->execute([$start_date . ' 00:00:00', $end_date . ' 23:59:59']);
    $overall_metrics = $stmt->fetch(PDO::FETCH_ASSOC);

    // Daily Performance Breakdown
    $stmt = $pdo->prepare("
        SELECT
            DATE(updated_at) as date,
            COUNT(*) as deliveries_count,
            SUM(CASE WHEN status = 'delivered' THEN 1 ELSE 0 END) as completed_count,
            AVG(CASE WHEN status = 'delivered' THEN TIMESTAMPDIFF(MINUTE, created_at, updated_at) END) as avg_time,
            SUM(CASE WHEN status = 'delivered' THEN total * 0.1 ELSE 0 END) as daily_earnings
        FROM orders
        WHERE updated_at BETWEEN ? AND ?
        GROUP BY DATE(updated_at)
        ORDER BY date DESC
    ");
    $stmt->execute([$start_date . ' 00:00:00', $end_date . ' 23:59:59']);
    $daily_performance = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Performance by Time of Day
    $stmt = $pdo->prepare("
        SELECT
            HOUR(updated_at) as hour,
            COUNT(*) as deliveries_count,
            AVG(CASE WHEN status = 'delivered' THEN TIMESTAMPDIFF(MINUTE, created_at, updated_at) END) as avg_time
        FROM orders
        WHERE status = 'delivered' AND updated_at BETWEEN ? AND ?
        GROUP BY HOUR(updated_at)
        ORDER BY hour
    ");
    $stmt->execute([$start_date . ' 00:00:00', $end_date . ' 23:59:59']);
    $hourly_performance = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Customer Ratings (if you have a ratings system)
    $stmt = $pdo->prepare("
        SELECT AVG(rating) as avg_rating, COUNT(*) as total_ratings
        FROM delivery_ratings
        WHERE delivery_person_id = ? AND created_at BETWEEN ? AND ?
    ");
    $stmt->execute([$user_id, $start_date . ' 00:00:00', $end_date . ' 23:59:59']);
    $rating_data = $stmt->fetch(PDO::FETCH_ASSOC);

    // Top Performing Routes
    $stmt = $pdo->prepare("
        SELECT
            delivery_zone,
            COUNT(*) as deliveries_count,
            AVG(CASE WHEN status = 'delivered' THEN TIMESTAMPDIFF(MINUTE, created_at, updated_at) END) as avg_time
        FROM orders
        WHERE status = 'delivered' AND updated_at BETWEEN ? AND ?
        GROUP BY delivery_zone
        ORDER BY deliveries_count DESC
        LIMIT 10
    ");
    $stmt->execute([$start_date . ' 00:00:00', $end_date . ' 23:59:59']);
    $route_performance = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $error_message = 'Error loading performance data: ' . $e->getMessage();
    error_log("Delivery reports error: " . $e->getMessage());
}

// Include activity logger
require_once '../../includes/ActivityLogger.php';
$activityLogger = new ActivityLogger($pdo);

// Log reports page access
$activityLogger->log('delivery', 'reports_view', 'Delivery person accessed performance reports', 'user', $user_id);

// Include header
require_once 'includes/header.php';
?>

<!-- Reports Header -->
<div class="bg-gradient-to-br from-primary via-primary-dark to-green-700 text-white">
    <div class="px-6 py-12">
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between">
            <div>
                <h1 class="text-4xl md:text-5xl font-bold mb-3">Performance Reports</h1>
                <p class="text-xl opacity-90">Track your delivery performance and analytics</p>
            </div>
            <div class="mt-6 lg:mt-0">
                <div class="flex items-center space-x-2 bg-white/10 backdrop-blur-sm rounded-lg px-3 py-2">
                    <i class="fas fa-chart-line text-yellow-300"></i>
                    <span class="text-sm font-medium">Period: <?php echo date('M j', strtotime($start_date)) . ' - ' . date('M j, Y', strtotime($end_date)); ?></span>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Main Reports Content -->
<div class="px-6 py-8">
    <!-- Date Range Filter -->
    <div class="bg-white rounded-xl shadow-lg p-6 mb-8">
        <form method="GET" class="flex flex-wrap gap-4 items-end">
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">Quick Range</label>
                <select name="range" onchange="this.form.submit()"
                        class="px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                    <option value="today" <?php echo $date_range === 'today' ? 'selected' : ''; ?>>Today</option>
                    <option value="7days" <?php echo $date_range === '7days' ? 'selected' : ''; ?>>Last 7 Days</option>
                    <option value="30days" <?php echo $date_range === '30days' ? 'selected' : ''; ?>>Last 30 Days</option>
                    <option value="90days" <?php echo $date_range === '90days' ? 'selected' : ''; ?>>Last 90 Days</option>
                </select>
            </div>

            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">Start Date</label>
                <input type="date" name="start_date" value="<?php echo $start_date; ?>"
                       class="px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
            </div>

            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">End Date</label>
                <input type="date" name="end_date" value="<?php echo $end_date; ?>"
                       class="px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
            </div>

            <div>
                <button type="submit" class="bg-primary hover:bg-primary-dark text-white px-6 py-3 rounded-lg font-semibold transition-colors">
                    <i class="fas fa-search mr-2"></i>
                    Update Report
                </button>
            </div>
        </form>
    </div>

    <!-- Key Performance Metrics -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <div class="bg-white rounded-xl shadow-lg p-6 border-l-4 border-blue-500">
            <div class="flex items-center">
                <div class="p-3 bg-blue-100 rounded-lg">
                    <i class="fas fa-box text-2xl text-blue-600"></i>
                </div>
                <div class="ml-4">
                    <h3 class="text-3xl font-bold text-gray-900 mb-1"><?php echo number_format($overall_metrics['total_deliveries'] ?? 0); ?></h3>
                    <p class="text-gray-600 font-medium">Total Deliveries</p>
                    <p class="text-sm text-gray-500 mt-1">In selected period</p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-lg p-6 border-l-4 border-green-500">
            <div class="flex items-center">
                <div class="p-3 bg-green-100 rounded-lg">
                    <i class="fas fa-check-circle text-2xl text-green-600"></i>
                </div>
                <div class="ml-4">
                    <h3 class="text-3xl font-bold text-gray-900 mb-1"><?php echo number_format($overall_metrics['completed_deliveries'] ?? 0); ?></h3>
                    <p class="text-gray-600 font-medium">Completed</p>
                    <p class="text-sm text-gray-500 mt-1">
                        <?php
                        $completion_rate = $overall_metrics['total_deliveries'] > 0 ?
                            round(($overall_metrics['completed_deliveries'] / $overall_metrics['total_deliveries']) * 100, 1) : 0;
                        echo $completion_rate . '% completion rate';
                        ?>
                    </p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-lg p-6 border-l-4 border-purple-500">
            <div class="flex items-center">
                <div class="p-3 bg-purple-100 rounded-lg">
                    <i class="fas fa-clock text-2xl text-purple-600"></i>
                </div>
                <div class="ml-4">
                    <h3 class="text-3xl font-bold text-gray-900 mb-1">
                        <?php echo isset($overall_metrics['avg_delivery_time']) ? round($overall_metrics['avg_delivery_time']) : 0; ?>m
                    </h3>
                    <p class="text-gray-600 font-medium">Avg. Delivery Time</p>
                    <p class="text-sm text-gray-500 mt-1">From pickup to delivery</p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-lg p-6 border-l-4 border-yellow-500">
            <div class="flex items-center">
                <div class="p-3 bg-yellow-100 rounded-lg">
                    <i class="fas fa-money-bill-wave text-2xl text-yellow-600"></i>
                </div>
                <div class="ml-4">
                    <h3 class="text-3xl font-bold text-gray-900 mb-1">KES <?php echo number_format($overall_metrics['total_earnings'] ?? 0, 0); ?></h3>
                    <p class="text-gray-600 font-medium">Total Earnings</p>
                    <p class="text-sm text-gray-500 mt-1">10% commission</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts and Analytics -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
        <!-- Daily Performance Chart -->
        <div class="bg-white rounded-xl shadow-lg p-6">
            <h3 class="text-xl font-bold text-gray-900 mb-6 flex items-center">
                <i class="fas fa-calendar mr-3 text-primary"></i>
                Daily Performance
            </h3>

            <?php if (!empty($daily_performance)): ?>
                <div class="space-y-4">
                    <?php foreach (array_reverse($daily_performance) as $day): ?>
                        <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                            <div class="flex items-center space-x-3">
                                <div class="w-10 h-10 bg-primary/10 rounded-full flex items-center justify-center">
                                    <span class="text-sm font-semibold text-primary"><?php echo date('j', strtotime($day['date'])); ?></span>
                                </div>
                                <div>
                                    <p class="font-medium text-gray-900"><?php echo date('M j, Y', strtotime($day['date'])); ?></p>
                                    <p class="text-sm text-gray-500"><?php echo $day['deliveries_count']; ?> deliveries</p>
                                </div>
                            </div>
                            <div class="text-right">
                                <div class="text-sm font-semibold text-gray-900">
                                    KES <?php echo number_format($day['daily_earnings'] ?? 0, 0); ?>
                                </div>
                                <div class="text-xs text-gray-500">
                                    <?php echo isset($day['avg_time']) ? round($day['avg_time']) . 'm avg' : 'N/A'; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="text-center py-8">
                    <i class="fas fa-chart-bar text-4xl text-gray-300 mb-3"></i>
                    <p class="text-gray-500">No performance data available for the selected period.</p>
                </div>
            <?php endif; ?>
        </div>

        <!-- Hourly Performance -->
        <div class="bg-white rounded-xl shadow-lg p-6">
            <h3 class="text-xl font-bold text-gray-900 mb-6 flex items-center">
                <i class="fas fa-clock mr-3 text-primary"></i>
                Performance by Hour
            </h3>

            <?php if (!empty($hourly_performance)): ?>
                <div class="space-y-3">
                    <?php foreach ($hourly_performance as $hour): ?>
                        <div class="flex items-center justify-between">
                            <span class="text-sm font-medium text-gray-700">
                                <?php echo date('g A', strtotime($hour['hour'] . ':00:00')); ?>
                            </span>
                            <div class="flex items-center space-x-3">
                                <div class="w-24 bg-gray-200 rounded-full h-2">
                                    <div class="bg-primary h-2 rounded-full"
                                         style="width: <?php echo min(($hour['deliveries_count'] / max(array_column($hourly_performance, 'deliveries_count'))) * 100, 100); ?>%">
                                    </div>
                                </div>
                                <span class="text-sm text-gray-600"><?php echo $hour['deliveries_count']; ?> deliveries</span>
                                <span class="text-xs text-gray-500">
                                    <?php echo isset($hour['avg_time']) ? round($hour['avg_time']) . 'm' : 'N/A'; ?>
                                </span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="text-center py-8">
                    <i class="fas fa-clock text-4xl text-gray-300 mb-3"></i>
                    <p class="text-gray-500">No hourly data available.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Detailed Analytics -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
        <!-- Route Performance -->
        <div class="bg-white rounded-xl shadow-lg p-6">
            <h3 class="text-xl font-bold text-gray-900 mb-6 flex items-center">
                <i class="fas fa-route mr-3 text-primary"></i>
                Top Performing Routes
            </h3>

            <?php if (!empty($route_performance)): ?>
                <div class="space-y-4">
                    <?php foreach ($route_performance as $index => $route): ?>
                        <div class="flex items-center justify-between p-3 <?php echo $index === 0 ? 'bg-yellow-50 border border-yellow-200' : 'bg-gray-50'; ?> rounded-lg">
                            <div class="flex items-center space-x-3">
                                <?php if ($index === 0): ?>
                                    <div class="w-6 h-6 bg-yellow-500 rounded-full flex items-center justify-center">
                                        <i class="fas fa-trophy text-white text-xs"></i>
                                    </div>
                                <?php else: ?>
                                    <div class="w-6 h-6 bg-gray-300 rounded-full flex items-center justify-center">
                                        <span class="text-xs font-semibold text-gray-600"><?php echo $index + 1; ?></span>
                                    </div>
                                <?php endif; ?>
                                <div>
                                    <p class="font-medium text-gray-900"><?php echo htmlspecialchars($route['delivery_zone'] ?: 'Various Locations'); ?></p>
                                    <p class="text-sm text-gray-500"><?php echo $route['deliveries_count']; ?> deliveries</p>
                                </div>
                            </div>
                            <div class="text-right">
                                <div class="text-sm font-semibold text-gray-900">
                                    <?php echo isset($route['avg_time']) ? round($route['avg_time']) . 'm avg' : 'N/A'; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="text-center py-8">
                    <i class="fas fa-map-marked-alt text-4xl text-gray-300 mb-3"></i>
                    <p class="text-gray-500">No route performance data available.</p>
                </div>
            <?php endif; ?>
        </div>

        <!-- Customer Ratings -->
        <div class="bg-white rounded-xl shadow-lg p-6">
            <h3 class="text-xl font-bold text-gray-900 mb-6 flex items-center">
                <i class="fas fa-star mr-3 text-primary"></i>
                Customer Feedback
            </h3>

            <div class="text-center">
                <div class="mb-4">
                    <div class="text-5xl font-bold text-yellow-500 mb-2">
                        <?php echo isset($rating_data['avg_rating']) ? number_format($rating_data['avg_rating'], 1) : 'N/A'; ?>
                    </div>
                    <div class="flex items-center justify-center space-x-1 mb-2">
                        <?php
                        $avg_rating = $rating_data['avg_rating'] ?? 0;
                        for ($i = 1; $i <= 5; $i++) {
                            if ($i <= $avg_rating) {
                                echo '<i class="fas fa-star text-yellow-500"></i>';
                            } elseif ($i - 0.5 <= $avg_rating) {
                                echo '<i class="fas fa-star-half-alt text-yellow-500"></i>';
                            } else {
                                echo '<i class="far fa-star text-gray-300"></i>';
                            }
                        }
                        ?>
                    </div>
                    <p class="text-gray-600">
                        <?php echo $rating_data['total_ratings'] ?? 0; ?> total ratings
                    </p>
                </div>

                <?php if (($rating_data['total_ratings'] ?? 0) > 0): ?>
                    <div class="space-y-2">
                        <?php for ($i = 5; $i >= 1; $i--): ?>
                            <div class="flex items-center justify-between text-sm">
                                <span class="text-gray-600"><?php echo $i; ?> stars</span>
                                <div class="w-24 bg-gray-200 rounded-full h-2 ml-3">
                                    <div class="bg-yellow-500 h-2 rounded-full" style="width: 20%"></div>
                                </div>
                            </div>
                        <?php endfor; ?>
                    </div>
                <?php else: ?>
                    <p class="text-gray-500 mt-4">No ratings yet. Complete more deliveries to get customer feedback!</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Performance Insights -->
    <div class="mt-8">
        <div class="bg-gradient-to-r from-primary/5 to-secondary/5 rounded-xl p-6 border border-primary/20">
            <h3 class="text-xl font-bold text-gray-900 mb-4 flex items-center">
                <i class="fas fa-lightbulb mr-3 text-primary"></i>
                Performance Insights
            </h3>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div class="text-center">
                    <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-3">
                        <i class="fas fa-thumbs-up text-2xl text-green-600"></i>
                    </div>
                    <h4 class="font-semibold text-gray-800 mb-2">Strength</h4>
                    <p class="text-sm text-gray-600">
                        <?php
                        if (($overall_metrics['completed_deliveries'] ?? 0) > 0) {
                            $completion_rate = ($overall_metrics['completed_deliveries'] / $overall_metrics['total_deliveries']) * 100;
                            if ($completion_rate >= 95) {
                                echo "Excellent completion rate of " . round($completion_rate, 1) . "%";
                            } elseif ($completion_rate >= 90) {
                                echo "Good completion rate of " . round($completion_rate, 1) . "%";
                            } else {
                                echo "Maintaining steady delivery performance";
                            }
                        } else {
                            echo "Start completing deliveries to see insights";
                        }
                        ?>
                    </p>
                </div>

                <div class="text-center">
                    <div class="w-16 h-16 bg-blue-100 rounded-full flex items-center justify-center mx-auto mb-3">
                        <i class="fas fa-chart-line text-2xl text-blue-600"></i>
                    </div>
                    <h4 class="font-semibold text-gray-800 mb-2">Trend</h4>
                    <p class="text-sm text-gray-600">
                        <?php
                        if (count($daily_performance) >= 2) {
                            $recent_days = array_slice($daily_performance, 0, 3);
                            $earnings_trend = array_column($recent_days, 'daily_earnings');
                            if ($earnings_trend[0] > $earnings_trend[count($earnings_trend) - 1]) {
                                echo "Earnings trending upward 📈";
                            } else {
                                echo "Consistent performance maintained";
                            }
                        } else {
                            echo "Need more data to show trends";
                        }
                        ?>
                    </p>
                </div>

                <div class="text-center">
                    <div class="w-16 h-16 bg-purple-100 rounded-full flex items-center justify-center mx-auto mb-3">
                        <i class="fas fa-bullseye text-2xl text-purple-600"></i>
                    </div>
                    <h4 class="font-semibold text-gray-800 mb-2">Goal</h4>
                    <p class="text-sm text-gray-600">
                        <?php
                        $target_daily = 10; // Example target
                        $avg_daily = count($daily_performance) > 0 ?
                            array_sum(array_column($daily_performance, 'deliveries_count')) / count($daily_performance) : 0;

                        if ($avg_daily >= $target_daily) {
                            echo "Exceeded daily delivery target! 🎉";
                        } else {
                            $needed = $target_daily - $avg_daily;
                            echo "Aim for " . round($needed) . " more deliveries per day";
                        }
                        ?>
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
