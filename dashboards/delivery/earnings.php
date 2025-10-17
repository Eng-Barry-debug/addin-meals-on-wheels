<?php
// Start session and check delivery authentication
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include database connection and functions
require_once '../../admin/includes/config.php';

// Check if user is logged in and is delivery personnel
if (!isset($_SESSION['user_id']) || ($_SESSION['user_role'] !== 'delivery' && $_SESSION['user_role'] !== 'driver')) {
    header('Location: ../../auth/login.php');
    exit();
}

// Set page title
$page_title = 'My Earnings';

// Initialize variables
$todayEarnings = 0;
$weeklyEarnings = 0;
$monthlyEarnings = 0;
$totalEarnings = 0;
$pendingPayments = 0;
$recentPayments = [];
$earningsData = [];
$error = null;

// Get earnings data
try {
    global $pdo;

    // Get today's earnings
    $todayStmt = $pdo->prepare("
        SELECT COALESCE(SUM(total * 0.1), 0) as earnings
        FROM orders
        WHERE status = 'delivered' AND DATE(updated_at) = CURDATE()
    ");
    $todayStmt->execute();
    $todayEarnings = (float)($todayStmt->fetch(PDO::FETCH_ASSOC)['earnings'] ?? 0);

    // Get weekly earnings (last 7 days)
    $weeklyStmt = $pdo->prepare("
        SELECT COALESCE(SUM(total * 0.1), 0) as earnings
        FROM orders
        WHERE status = 'delivered' AND updated_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
    ");
    $weeklyStmt->execute();
    $weeklyEarnings = (float)($weeklyStmt->fetch(PDO::FETCH_ASSOC)['earnings'] ?? 0);

    // Get monthly earnings
    $monthlyStmt = $pdo->prepare("
        SELECT COALESCE(SUM(total * 0.1), 0) as earnings
        FROM orders
        WHERE status = 'delivered' AND DATE_FORMAT(updated_at, '%Y-%m') = DATE_FORMAT(CURDATE(), '%Y-%m')
    ");
    $monthlyStmt->execute();
    $monthlyEarnings = (float)($monthlyStmt->fetch(PDO::FETCH_ASSOC)['earnings'] ?? 0);

    // Get total earnings
    $totalStmt = $pdo->prepare("
        SELECT COALESCE(SUM(total * 0.1), 0) as earnings
        FROM orders
        WHERE status = 'delivered'
    ");
    $totalStmt->execute();
    $totalEarnings = (float)($totalStmt->fetch(PDO::FETCH_ASSOC)['earnings'] ?? 0);

    // Get pending payments (deliveries completed but not yet paid)
    $pendingStmt = $pdo->prepare("
        SELECT COUNT(*) as count, COALESCE(SUM(total * 0.1), 0) as amount
        FROM orders
        WHERE status = 'delivered' AND payment_status = 'pending'
    ");
    $pendingStmt->execute();
    $pendingData = $pendingStmt->fetch(PDO::FETCH_ASSOC);
    $pendingPayments = $pendingData['count'];

    // Get recent payments (last 10 payments)
    $paymentsStmt = $pdo->prepare("
        SELECT
            DATE(updated_at) as payment_date,
            COUNT(*) as deliveries_count,
            SUM(total * 0.1) as payment_amount,
            'paid' as status
        FROM orders
        WHERE status = 'delivered' AND payment_status = 'paid'
        GROUP BY DATE(updated_at)
        ORDER BY payment_date DESC
        LIMIT 10
    ");
    $paymentsStmt->execute();
    $recentPayments = $paymentsStmt->fetchAll(PDO::FETCH_ASSOC);

    // Get daily earnings for the last 30 days (for chart)
    $chartStmt = $pdo->prepare("
        SELECT
            DATE(updated_at) as date,
            COALESCE(SUM(total * 0.1), 0) as earnings,
            COUNT(*) as deliveries
        FROM orders
        WHERE status = 'delivered' AND updated_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
        GROUP BY DATE(updated_at)
        ORDER BY date ASC
    ");
    $chartStmt->execute();
    $earningsData = $chartStmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $error = "Database error: " . $e->getMessage();
    error_log("Delivery earnings page error: " . $e->getMessage());
}

// Handle withdrawal request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['request_withdrawal'])) {
    $amount = (float)$_POST['amount'];
    $payment_method = $_POST['payment_method'];

    if ($amount > 0 && $amount <= $totalEarnings) {
        // In a real implementation, this would create a withdrawal request
        $_SESSION['message'] = ['type' => 'success', 'text' => 'Withdrawal request submitted successfully. You will receive payment within 24 hours.'];
    } else {
        $_SESSION['message'] = ['type' => 'error', 'text' => 'Invalid withdrawal amount.'];
    }

    header('Location: earnings.php');
    exit();
}

// Include header
require_once 'includes/header.php';
?>

<!-- Page Header -->
<div class="bg-gradient-to-br from-primary via-primary-dark to-green-700 text-white">
    <div class="px-6 py-8">
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between">
            <div>
                <h1 class="text-3xl md:text-4xl font-bold mb-2">My Earnings</h1>
                <p class="text-lg opacity-90">Track your delivery earnings and payment history</p>
            </div>
            <div class="mt-4 lg:mt-0">
                <div class="bg-white/20 backdrop-blur-sm rounded-lg px-6 py-3">
                    <div class="text-center">
                        <p class="text-sm opacity-90">Available for Withdrawal</p>
                        <p class="text-2xl font-bold">KES <?php echo number_format($totalEarnings, 2); ?></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Main Content -->
<div class="px-6 py-8">
    <?php if (isset($_SESSION['message'])): ?>
        <div class="mb-6 p-4 rounded-lg <?php echo $_SESSION['message']['type'] === 'success' ? 'bg-green-50 text-green-800 border-l-4 border-green-400' : 'bg-red-50 text-red-800 border-l-4 border-red-400'; ?>">
            <?php echo htmlspecialchars($_SESSION['message']['text']); ?>
            <?php unset($_SESSION['message']); ?>
        </div>
    <?php endif; ?>

    <?php if (isset($error)): ?>
        <div class="mb-6 p-4 bg-red-50 border-l-4 border-red-400 text-red-700 rounded-lg">
            <p class="font-semibold">Error</p>
            <p><?php echo htmlspecialchars($error); ?></p>
        </div>
    <?php endif; ?>

    <!-- Earnings Overview Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <div class="bg-white rounded-xl shadow-lg p-6 border-l-4 border-green-500">
            <div class="flex items-center">
                <div class="p-3 bg-green-100 rounded-lg">
                    <i class="fas fa-calendar-day text-2xl text-green-600"></i>
                </div>
                <div class="ml-4">
                    <h3 class="text-2xl font-bold text-gray-900">KES <?php echo number_format($todayEarnings, 2); ?></h3>
                    <p class="text-gray-600 font-medium">Today's Earnings</p>
                    <p class="text-sm text-gray-500 mt-1"><?php echo count(array_filter($earningsData, function($day) { return $day['date'] == date('Y-m-d'); })); ?> deliveries</p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-lg p-6 border-l-4 border-blue-500">
            <div class="flex items-center">
                <div class="p-3 bg-blue-100 rounded-lg">
                    <i class="fas fa-calendar-week text-2xl text-blue-600"></i>
                </div>
                <div class="ml-4">
                    <h3 class="text-2xl font-bold text-gray-900">KES <?php echo number_format($weeklyEarnings, 2); ?></h3>
                    <p class="text-gray-600 font-medium">This Week</p>
                    <p class="text-sm text-gray-500 mt-1">Last 7 days</p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-lg p-6 border-l-4 border-purple-500">
            <div class="flex items-center">
                <div class="p-3 bg-purple-100 rounded-lg">
                    <i class="fas fa-calendar-alt text-2xl text-purple-600"></i>
                </div>
                <div class="ml-4">
                    <h3 class="text-2xl font-bold text-gray-900">KES <?php echo number_format($monthlyEarnings, 2); ?></h3>
                    <p class="text-gray-600 font-medium">This Month</p>
                    <p class="text-sm text-gray-500 mt-1"><?php echo date('F Y'); ?></p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-lg p-6 border-l-4 border-yellow-500">
            <div class="flex items-center">
                <div class="p-3 bg-yellow-100 rounded-lg">
                    <i class="fas fa-coins text-2xl text-yellow-600"></i>
                </div>
                <div class="ml-4">
                    <h3 class="text-2xl font-bold text-gray-900">KES <?php echo number_format($totalEarnings, 2); ?></h3>
                    <p class="text-gray-600 font-medium">Total Earnings</p>
                    <p class="text-sm text-gray-500 mt-1">All time</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Pending Payments Alert -->
    <?php if ($pendingPayments > 0): ?>
        <div class="mb-8 p-4 bg-yellow-50 border-l-4 border-yellow-400 rounded-lg">
            <div class="flex items-center justify-between">
                <div class="flex items-center">
                    <i class="fas fa-clock text-yellow-600 mr-3"></i>
                    <div>
                        <p class="font-semibold text-yellow-800">Pending Payments</p>
                        <p class="text-yellow-700"><?php echo $pendingPayments; ?> deliveries awaiting payment processing</p>
                    </div>
                </div>
                <span class="bg-yellow-600 text-white px-3 py-1 rounded-full text-sm font-medium">
                    Pending Review
                </span>
            </div>
        </div>
    <?php endif; ?>

    <!-- Earnings Tabs -->
    <div class="mb-8">
        <div class="border-b border-gray-200">
            <nav class="-mb-px flex space-x-8">
                <button onclick="showEarningsTab('overview')" class="earnings-tab-button active whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm border-secondary text-secondary">
                    Earnings Overview
                </button>
                <button onclick="showEarningsTab('history')" class="earnings-tab-button whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300">
                    Payment History
                </button>
                <button onclick="showEarningsTab('withdraw')" class="earnings-tab-button whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300">
                    Request Withdrawal
                </button>
            </nav>
        </div>
    </div>

    <!-- Earnings Overview Tab -->
    <div id="overview-tab" class="earnings-tab-content">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            <!-- Earnings Chart -->
            <div class="bg-white rounded-xl shadow-lg p-6">
                <h3 class="text-xl font-bold text-gray-900 mb-6 flex items-center">
                    <i class="fas fa-chart-line mr-3 text-secondary"></i>
                    30-Day Earnings Trend
                </h3>

                <div class="h-64">
                    <canvas id="earningsChart"></canvas>
                </div>

                <div class="mt-4 grid grid-cols-2 gap-4 text-center">
                    <div class="p-3 bg-green-50 rounded-lg">
                        <div class="text-lg font-bold text-green-600">
                            <?php echo array_sum(array_column($earningsData, 'deliveries')); ?>
                        </div>
                        <div class="text-sm text-green-700">Total Deliveries</div>
                    </div>
                    <div class="p-3 bg-blue-50 rounded-lg">
                        <div class="text-lg font-bold text-blue-600">
                            KES <?php echo number_format(array_sum(array_column($earningsData, 'earnings')), 2); ?>
                        </div>
                        <div class="text-sm text-blue-700">Total Earned</div>
                    </div>
                </div>
            </div>

            <!-- Performance Metrics -->
            <div class="bg-white rounded-xl shadow-lg p-6">
                <h3 class="text-xl font-bold text-gray-900 mb-6 flex items-center">
                    <i class="fas fa-trophy mr-3 text-secondary"></i>
                    Performance Metrics
                </h3>

                <div class="space-y-4">
                    <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg">
                        <div class="flex items-center">
                            <i class="fas fa-star text-yellow-500 mr-3"></i>
                            <span class="font-medium text-gray-900">Customer Rating</span>
                        </div>
                        <div class="flex items-center">
                            <span class="text-2xl font-bold text-gray-900 mr-2">4.8</span>
                            <div class="flex">
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                    <i class="fas fa-star text-yellow-400 text-sm"></i>
                                <?php endfor; ?>
                            </div>
                        </div>
                    </div>

                    <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg">
                        <div class="flex items-center">
                            <i class="fas fa-clock text-blue-500 mr-3"></i>
                            <span class="font-medium text-gray-900">On-time Rate</span>
                        </div>
                        <span class="text-2xl font-bold text-gray-900">96%</span>
                    </div>

                    <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg">
                        <div class="flex items-center">
                            <i class="fas fa-thumbs-up text-green-500 mr-3"></i>
                            <span class="font-medium text-gray-900">Success Rate</span>
                        </div>
                        <span class="text-2xl font-bold text-gray-900">98%</span>
                    </div>

                    <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg">
                        <div class="flex items-center">
                            <i class="fas fa-route text-purple-500 mr-3"></i>
                            <span class="font-medium text-gray-900">Avg. Route Efficiency</span>
                        </div>
                        <span class="text-2xl font-bold text-gray-900">94%</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Payment History Tab -->
    <div id="history-tab" class="earnings-tab-content hidden">
        <?php if (!empty($recentPayments)): ?>
            <div class="bg-white rounded-xl shadow-lg p-6">
                <h3 class="text-xl font-bold text-gray-900 mb-6 flex items-center">
                    <i class="fas fa-history mr-3 text-secondary"></i>
                    Recent Payments
                </h3>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Deliveries</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php foreach ($recentPayments as $payment): ?>
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        <?php echo date('M j, Y', strtotime($payment['payment_date'])); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        <?php echo $payment['deliveries_count']; ?> deliveries
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 font-medium">
                                        KES <?php echo number_format($payment['payment_amount'], 2); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                            <?php echo ucfirst($payment['status']); ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php else: ?>
            <div class="text-center py-12">
                <i class="fas fa-money-bill-wave text-6xl text-gray-300 mb-4"></i>
                <h3 class="text-xl font-medium text-gray-900 mb-2">No payment history</h3>
                <p class="text-gray-600">Your payment history will appear here after completing deliveries.</p>
            </div>
        <?php endif; ?>
    </div>

    <!-- Withdrawal Tab -->
    <div id="withdraw-tab" class="earnings-tab-content hidden">
        <div class="bg-white rounded-xl shadow-lg p-8 max-w-md mx-auto">
            <div class="text-center mb-8">
                <i class="fas fa-wallet text-6xl text-secondary mb-4"></i>
                <h3 class="text-2xl font-bold text-gray-900 mb-2">Request Withdrawal</h3>
                <p class="text-gray-600">Withdraw your earnings to your preferred payment method</p>
            </div>

            <form method="POST" class="space-y-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Available Balance</label>
                    <div class="text-3xl font-bold text-secondary mb-2">
                        KES <?php echo number_format($totalEarnings, 2); ?>
                    </div>
                    <p class="text-sm text-gray-600">Minimum withdrawal: KES 500</p>
                </div>

                <div>
                    <label for="amount" class="block text-sm font-medium text-gray-700 mb-2">Withdrawal Amount</label>
                    <input type="number"
                           id="amount"
                           name="amount"
                           min="500"
                           max="<?php echo $totalEarnings; ?>"
                           step="0.01"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-secondary focus:border-transparent"
                           placeholder="Enter amount to withdraw"
                           required>
                </div>

                <div>
                    <label for="payment_method" class="block text-sm font-medium text-gray-700 mb-2">Payment Method</label>
                    <select id="payment_method"
                            name="payment_method"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-secondary focus:border-transparent"
                            required>
                        <option value="">Select payment method</option>
                        <option value="mpesa">M-Pesa</option>
                        <option value="bank">Bank Transfer</option>
                        <option value="paypal">PayPal</option>
                    </select>
                </div>

                <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 rounded-lg">
                    <div class="flex">
                        <i class="fas fa-info-circle text-yellow-400 mt-0.5 mr-3"></i>
                        <div class="text-sm text-yellow-700">
                            <p class="font-medium">Processing Time</p>
                            <p>Withdrawals are processed within 24 hours during business days.</p>
                        </div>
                    </div>
                </div>

                <button type="submit"
                        name="request_withdrawal"
                        class="w-full bg-secondary hover:bg-yellow-600 text-white px-6 py-3 rounded-lg font-semibold transition-colors flex items-center justify-center">
                    <i class="fas fa-paper-plane mr-2"></i>
                    Request Withdrawal
                </button>
            </form>
        </div>
    </div>
</div>

<script>
function showEarningsTab(tabName) {
    // Hide all earnings tabs
    document.querySelectorAll('.earnings-tab-content').forEach(tab => {
        tab.classList.add('hidden');
    });

    // Remove active state from all buttons
    document.querySelectorAll('.earnings-tab-button').forEach(button => {
        button.classList.remove('border-secondary', 'text-secondary');
        button.classList.add('border-transparent', 'text-gray-500');
    });

    // Show selected tab
    document.getElementById(tabName + '-tab').classList.remove('hidden');

    // Add active state to clicked button
    event.target.classList.remove('border-transparent', 'text-gray-500');
    event.target.classList.add('border-secondary', 'text-secondary');
}

// Chart.js for earnings chart
document.addEventListener('DOMContentLoaded', function() {
    const ctx = document.getElementById('earningsChart');
    if (ctx) {
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: <?php echo json_encode(array_column($earningsData, 'date')); ?>,
                datasets: [{
                    label: 'Daily Earnings (KES)',
                    data: <?php echo json_encode(array_column($earningsData, 'earnings')); ?>,
                    borderColor: '#F59E0B',
                    backgroundColor: 'rgba(245, 158, 11, 0.1)',
                    tension: 0.4,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return 'KES ' + value;
                            }
                        }
                    }
                },
                plugins: {
                    legend: {
                        display: false
                    }
                }
            }
        });
    }
});

// Auto-refresh every 60 seconds
setInterval(function() {
    location.reload();
}, 60000);
</script>

<?php require_once 'includes/footer.php'; ?>
