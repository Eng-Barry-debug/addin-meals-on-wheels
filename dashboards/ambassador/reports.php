<?php
// reports.php - Ambassador Performance Reports

// Start session and check ambassador authentication
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include database connection and functions
require_once '../../includes/config.php';
require_once 'includes/functions.php';

// Check if user is logged in and is ambassador
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'ambassador') {
    header('Location: ../../auth/login.php');
    exit();
}

// Set page title
$page_title = 'Performance Reports';

// Initialize variables
$user_id = $_SESSION['user_id'];
$date_range = $_GET['range'] ?? '30days';
$start_date = $_GET['start_date'] ?? '';
$end_date = $_GET['end_date'] ?? '';

// Calculate date range
$end_date = $end_date ?: date('Y-m-d');
switch($date_range) {
    case '7days':
        $start_date = date('Y-m-d', strtotime('-7 days'));
        break;
    case '30days':
        $start_date = date('Y-m-d', strtotime('-30 days'));
        break;
    case '90days':
        $start_date = date('Y-m-d', strtotime('-90 days'));
        break;
    case 'all':
        $start_date = '2024-01-01'; // Or whenever ambassadors started
        break;
    default:
        $start_date = $start_date ?: date('Y-m-d', strtotime('-30 days'));
}

// Get ambassador performance data
try {
    global $pdo;

    // Overall Performance Metrics
    $stmt = $pdo->prepare("
        SELECT
            COUNT(*) as total_referrals,
            SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as successful_referrals,
            SUM(CASE WHEN status = 'completed' THEN commission_amount ELSE 0 END) as total_commissions,
            AVG(CASE WHEN status = 'completed' THEN DATEDIFF(completed_at, created_at) END) as avg_completion_days
        FROM referrals
        WHERE ambassador_id = ? AND created_at BETWEEN ? AND ?
    ");
    $stmt->execute([$user_id, $start_date . ' 00:00:00', $end_date . ' 23:59:59']);
    $overall_metrics = $stmt->fetch(PDO::FETCH_ASSOC);

    // Monthly Performance Breakdown
    $stmt = $pdo->prepare("
        SELECT
            DATE_FORMAT(created_at, '%Y-%m') as month,
            COUNT(*) as referrals_count,
            SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as successful_count,
            SUM(CASE WHEN status = 'completed' THEN commission_amount ELSE 0 END) as monthly_commissions
        FROM referrals
        WHERE ambassador_id = ? AND created_at BETWEEN ? AND ?
        GROUP BY DATE_FORMAT(created_at, '%Y-%m')
        ORDER BY month DESC
    ");
    $stmt->execute([$user_id, $start_date . ' 00:00:00', $end_date . ' 23:59:59']);
    $monthly_performance = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Referral Sources Performance
    $stmt = $pdo->prepare("
        SELECT
            referral_source,
            COUNT(*) as referrals_count,
            SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as successful_count,
            SUM(CASE WHEN status = 'completed' THEN commission_amount ELSE 0 END) as source_commissions
        FROM referrals
        WHERE ambassador_id = ? AND created_at BETWEEN ? AND ?
        GROUP BY referral_source
        ORDER BY referrals_count DESC
    ");
    $stmt->execute([$user_id, $start_date . ' 00:00:00', $end_date . ' 23:59:59']);
    $source_performance = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Top Performing Referral Codes
    $stmt = $pdo->prepare("
        SELECT
            referral_code,
            COUNT(*) as usage_count,
            SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as successful_uses,
            SUM(CASE WHEN status = 'completed' THEN commission_amount ELSE 0 END) as code_commissions
        FROM referrals
        WHERE ambassador_id = ? AND created_at BETWEEN ? AND ?
        GROUP BY referral_code
        ORDER BY usage_count DESC
        LIMIT 10
    ");
    $stmt->execute([$user_id, $start_date . ' 00:00:00', $end_date . ' 23:59:59']);
    $top_codes = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Ambassador Rank (comparing with other ambassadors)
    $stmt = $pdo->prepare("
        SELECT COUNT(*) + 1 as ambassador_rank
        FROM ambassadors a
        WHERE a.status = 'approved'
        AND (
            SELECT COUNT(*) FROM referrals r WHERE r.ambassador_id = a.user_id AND r.status = 'completed'
        ) > (
            SELECT COUNT(*) FROM referrals r WHERE r.ambassador_id = ? AND r.status = 'completed'
        )
    ");
    $stmt->execute([$user_id]);
    $rank_data = $stmt->fetch(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $error_message = 'Error loading performance data: ' . $e->getMessage();
    error_log("Ambassador reports error: " . $e->getMessage());
}

// Include activity logger
require_once '../../includes/ActivityLogger.php';
$activityLogger = new ActivityLogger($pdo);

// Log reports page access
$activityLogger->log('ambassador', 'reports_view', 'Ambassador accessed performance reports', 'user', $user_id);

// Include header
require_once 'includes/header.php';
?>

<!-- Reports Header -->
<div class="bg-gradient-to-br from-purple-600 via-purple-700 to-pink-600 text-white">
    <div class="px-6 py-12">
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between">
            <div>
                <h1 class="text-4xl md:text-5xl font-bold mb-3">Performance Reports</h1>
                <p class="text-xl opacity-90">Track your ambassador performance and earnings</p>
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
                        class="px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                    <option value="7days" <?php echo $date_range === '7days' ? 'selected' : ''; ?>>Last 7 Days</option>
                    <option value="30days" <?php echo $date_range === '30days' ? 'selected' : ''; ?>>Last 30 Days</option>
                    <option value="90days" <?php echo $date_range === '90days' ? 'selected' : ''; ?>>Last 90 Days</option>
                    <option value="all" <?php echo $date_range === 'all' ? 'selected' : ''; ?>>All Time</option>
                </select>
            </div>

            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">Start Date</label>
                <input type="date" name="start_date" value="<?php echo $start_date; ?>"
                       class="px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent">
            </div>

            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">End Date</label>
                <input type="date" name="end_date" value="<?php echo $end_date; ?>"
                       class="px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent">
            </div>

            <div>
                <button type="submit" class="bg-purple-600 hover:bg-purple-700 text-white px-6 py-3 rounded-lg font-semibold transition-colors">
                    <i class="fas fa-search mr-2"></i>
                    Update Report
                </button>
            </div>
        </form>
    </div>

    <!-- Key Performance Metrics -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <div class="bg-white rounded-xl shadow-lg p-6 border-l-4 border-purple-500">
            <div class="flex items-center">
                <div class="p-3 bg-purple-100 rounded-lg">
                    <i class="fas fa-users text-2xl text-purple-600"></i>
                </div>
                <div class="ml-4">
                    <h3 class="text-3xl font-bold text-gray-900 mb-1"><?php echo number_format($overall_metrics['total_referrals'] ?? 0); ?></h3>
                    <p class="text-gray-600 font-medium">Total Referrals</p>
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
                    <h3 class="text-3xl font-bold text-gray-900 mb-1"><?php echo number_format($overall_metrics['successful_referrals'] ?? 0); ?></h3>
                    <p class="text-gray-600 font-medium">Successful</p>
                    <p class="text-sm text-gray-500 mt-1">
                        <?php
                        $success_rate = $overall_metrics['total_referrals'] > 0 ?
                            round(($overall_metrics['successful_referrals'] / $overall_metrics['total_referrals']) * 100, 1) : 0;
                        echo $success_rate . '% success rate';
                        ?>
                    </p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-lg p-6 border-l-4 border-yellow-500">
            <div class="flex items-center">
                <div class="p-3 bg-yellow-100 rounded-lg">
                    <i class="fas fa-money-bill-wave text-2xl text-yellow-600"></i>
                </div>
                <div class="ml-4">
                    <h3 class="text-3xl font-bold text-gray-900 mb-1">KES <?php echo number_format($overall_metrics['total_commissions'] ?? 0, 0); ?></h3>
                    <p class="text-gray-600 font-medium">Total Commissions</p>
                    <p class="text-sm text-gray-500 mt-1">From successful referrals</p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-lg p-6 border-l-4 border-blue-500">
            <div class="flex items-center">
                <div class="p-3 bg-blue-100 rounded-lg">
                    <i class="fas fa-trophy text-2xl text-blue-600"></i>
                </div>
                <div class="ml-4">
                    <h3 class="text-3xl font-bold text-gray-900 mb-1">#<?php echo $rank_data['ambassador_rank'] ?? 'N/A'; ?></h3>
                    <p class="text-gray-600 font-medium">Ambassador Rank</p>
                    <p class="text-sm text-gray-500 mt-1">Among all ambassadors</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts and Analytics -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
        <!-- Monthly Performance Chart -->
        <div class="bg-white rounded-xl shadow-lg p-6">
            <h3 class="text-xl font-bold text-gray-900 mb-6 flex items-center">
                <i class="fas fa-calendar mr-3 text-purple-600"></i>
                Monthly Performance
            </h3>

            <?php if (!empty($monthly_performance)): ?>
                <div class="space-y-4">
                    <?php foreach (array_reverse($monthly_performance) as $month): ?>
                        <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                            <div class="flex items-center space-x-3">
                                <div class="w-10 h-10 bg-purple-100 rounded-full flex items-center justify-center">
                                    <span class="text-sm font-semibold text-purple-600"><?php echo date('M', strtotime($month['month'] . '-01')); ?></span>
                                </div>
                                <div>
                                    <p class="font-medium text-gray-900"><?php echo date('F Y', strtotime($month['month'] . '-01')); ?></p>
                                    <p class="text-sm text-gray-500"><?php echo $month['referrals_count']; ?> referrals</p>
                                </div>
                            </div>
                            <div class="text-right">
                                <div class="text-sm font-semibold text-gray-900">
                                    KES <?php echo number_format($month['monthly_commissions'] ?? 0, 0); ?>
                                </div>
                                <div class="text-xs text-gray-500">
                                    <?php echo $month['successful_count']; ?> successful
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

        <!-- Referral Sources -->
        <div class="bg-white rounded-xl shadow-lg p-6">
            <h3 class="text-xl font-bold text-gray-900 mb-6 flex items-center">
                <i class="fas fa-bullhorn mr-3 text-purple-600"></i>
                Referral Sources Performance
            </h3>

            <?php if (!empty($source_performance)): ?>
                <div class="space-y-4">
                    <?php foreach ($source_performance as $source): ?>
                        <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                            <div class="flex items-center space-x-3">
                                <div class="w-10 h-10 bg-purple-100 rounded-full flex items-center justify-center">
                                    <i class="fas fa-share text-purple-600"></i>
                                </div>
                                <div>
                                    <p class="font-medium text-gray-900 capitalize"><?php echo str_replace('_', ' ', $source['referral_source']); ?></p>
                                    <p class="text-sm text-gray-500"><?php echo $source['referrals_count']; ?> referrals</p>
                                </div>
                            </div>
                            <div class="text-right">
                                <div class="text-sm font-semibold text-gray-900">
                                    KES <?php echo number_format($source['source_commissions'] ?? 0, 0); ?>
                                </div>
                                <div class="text-xs text-gray-500">
                                    <?php echo $source['successful_count']; ?> successful
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="text-center py-8">
                    <i class="fas fa-share-alt text-4xl text-gray-300 mb-3"></i>
                    <p class="text-gray-500">No source data available.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Top Referral Codes -->
    <div class="bg-white rounded-xl shadow-lg p-6 mb-8">
        <h3 class="text-xl font-bold text-gray-900 mb-6 flex items-center">
            <i class="fas fa-hashtag mr-3 text-purple-600"></i>
            Top Performing Referral Codes
        </h3>

        <?php if (!empty($top_codes)): ?>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                <?php foreach ($top_codes as $index => $code): ?>
                    <div class="bg-gradient-to-br from-purple-50 to-pink-50 rounded-lg p-4 border border-purple-200 <?php echo $index === 0 ? 'ring-2 ring-purple-400' : ''; ?>">
                        <div class="flex items-center justify-between mb-2">
                            <div class="flex items-center space-x-2">
                                <?php if ($index === 0): ?>
                                    <div class="w-5 h-5 bg-yellow-500 rounded-full flex items-center justify-center">
                                        <i class="fas fa-crown text-white text-xs"></i>
                                    </div>
                                <?php endif; ?>
                                <span class="font-mono font-bold text-purple-800"><?php echo htmlspecialchars($code['referral_code']); ?></span>
                            </div>
                            <span class="text-xs bg-purple-100 text-purple-700 px-2 py-1 rounded-full">
                                #<?php echo $index + 1; ?>
                            </span>
                        </div>

                        <div class="space-y-1 text-sm">
                            <div class="flex justify-between">
                                <span class="text-gray-600">Total Uses:</span>
                                <span class="font-semibold"><?php echo $code['usage_count']; ?></span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600">Successful:</span>
                                <span class="font-semibold text-green-600"><?php echo $code['successful_uses']; ?></span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600">Earnings:</span>
                                <span class="font-semibold text-purple-600">KES <?php echo number_format($code['code_commissions'], 0); ?></span>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="text-center py-8">
                <i class="fas fa-hashtag text-4xl text-gray-300 mb-3"></i>
                <p class="text-gray-500">No referral code data available yet.</p>
                <p class="text-sm text-gray-400 mt-2">Start sharing your referral codes to see performance here.</p>
            </div>
        <?php endif; ?>
    </div>

    <!-- Performance Insights -->
    <div class="bg-gradient-to-r from-purple-50 to-pink-50 rounded-xl p-6 border border-purple-200">
        <h3 class="text-xl font-bold text-gray-900 mb-4 flex items-center">
            <i class="fas fa-lightbulb mr-3 text-purple-600"></i>
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
                    if (($overall_metrics['successful_referrals'] ?? 0) > 0) {
                        $success_rate = ($overall_metrics['successful_referrals'] / $overall_metrics['total_referrals']) * 100;
                        if ($success_rate >= 80) {
                            echo "Excellent success rate of " . round($success_rate, 1) . "%! 🎉";
                        } elseif ($success_rate >= 60) {
                            echo "Great success rate of " . round($success_rate, 1) . "%";
                        } else {
                            echo "Good performance with room for growth";
                        }
                    } else {
                        echo "Start making referrals to see insights";
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
                    if (count($monthly_performance) >= 2) {
                        $recent_months = array_slice($monthly_performance, 0, 2);
                        $earnings_trend = array_column($recent_months, 'monthly_commissions');
                        if ($earnings_trend[0] > $earnings_trend[1]) {
                            echo "Commissions trending upward! 📈";
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
                    $target_monthly = 10; // Example target
                    $avg_monthly = count($monthly_performance) > 0 ?
                        array_sum(array_column($monthly_performance, 'referrals_count')) / count($monthly_performance) : 0;

                    if ($avg_monthly >= $target_monthly) {
                        echo "Exceeded monthly referral target! 🌟";
                    } else {
                        $needed = $target_monthly - $avg_monthly;
                        echo "Aim for " . round($needed) . " more referrals per month";
                    }
                    ?>
                </p>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
