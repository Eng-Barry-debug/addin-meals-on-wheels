<?php
// Start session if not already started
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
$page_title = 'Ambassador Dashboard';

// Initialize variables to prevent undefined variable errors
$totalReferrals = 0;
$activeReferrals = 0;
$monthlyEarnings = 0;
$recentReferrals = [];
$error = '';

// For now, show demo data until database is updated with referral system
// TODO: Update database with created_by column for proper referral tracking
$totalReferrals = 0; // Will be calculated from users.created_by once column is added
$activeReferrals = 0; // Will be calculated from users.created_by once column is added
$monthlyEarnings = 0; // Will be calculated from orders with referral commissions

// Get ambassador statistics (demo data for now)
try {
    global $pdo;

    // For demo purposes, show that the dashboard loads
    // In production, these would use the created_by column:
    // $totalReferrals = getCount($pdo, 'users', 'created_by = ' . $_SESSION['user_id']);
    // $activeReferrals = getCount($pdo, 'users', 'created_by = ' . $_SESSION['user_id'] . ' AND status = "active"');

    // Demo data until database is updated
    $totalReferrals = 0;
    $activeReferrals = 0;

    // Get recent referrals (demo data)
    $recentReferrals = [
        [
            'name' => 'Demo User',
            'email' => 'demo@example.com',
            'status' => 'active',
            'referral_date' => date('Y-m-d H:i:s'),
            'display_status' => 'Active'
        ]
    ];

    // Get monthly earnings (demo data)
    $monthlyEarnings = 0;

} catch (PDOException $e) {
    $error = "Database connection issue. Please ensure the 'created_by' column is added to the users table using the migration script.";
    error_log("Ambassador dashboard error: " . $e->getMessage());
}

// Include activity logger
require_once '../../includes/ActivityLogger.php';
$activityLogger = new ActivityLogger($pdo);

// Log dashboard access
$activityLogger->log('user', 'dashboard_view', 'Ambassador accessed dashboard', 'user', $_SESSION['user_id']);

// Include header
require_once 'includes/header.php';
?>

<!-- Dashboard Header -->
<div class="bg-gradient-to-br from-purple-600 via-purple-700 to-indigo-800 text-white">
    <div class="px-6 py-12">
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between">
            <div>
                <h1 class="text-4xl md:text-5xl font-bold mb-3">Ambassador Hub</h1>
                <p class="text-xl opacity-90 mb-4">Track your referrals and manage your network</p>
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
                    <a href="referrals.php"
                       class="bg-white/20 hover:bg-white/30 text-white px-6 py-3 rounded-lg font-semibold transition-all duration-200 flex items-center backdrop-blur-sm border border-white/20">
                        <i class="fas fa-users mr-2"></i>
                        View Referrals
                    </a>
                    <a href="earnings.php"
                       class="bg-accent hover:bg-blue-600 text-white px-6 py-3 rounded-lg font-semibold transition-all duration-200 flex items-center">
                        <i class="fas fa-money-bill-wave mr-2"></i>
                        My Earnings
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
        <!-- Total Referrals -->
        <div class="bg-white rounded-xl shadow-lg p-6 border-l-4 border-purple-500 hover:shadow-xl transition-shadow duration-200">
            <div class="flex items-center">
                <div class="p-3 bg-purple-100 rounded-lg">
                    <i class="fas fa-users text-2xl text-purple-600"></i>
                </div>
                <div class="ml-4">
                    <h3 class="text-3xl font-bold text-gray-900 mb-1"><?php echo number_format($totalReferrals); ?></h3>
                    <p class="text-gray-600 font-medium">Total Referrals</p>
                    <p class="text-sm text-gray-500 mt-1">People you've referred</p>
                </div>
            </div>
        </div>

        <!-- Active Referrals -->
        <div class="bg-white rounded-xl shadow-lg p-6 border-l-4 border-green-500 hover:shadow-xl transition-shadow duration-200">
            <div class="flex items-center">
                <div class="p-3 bg-green-100 rounded-lg">
                    <i class="fas fa-user-check text-2xl text-green-600"></i>
                </div>
                <div class="ml-4">
                    <h3 class="text-3xl font-bold text-gray-900 mb-1"><?php echo number_format($activeReferrals); ?></h3>
                    <p class="text-gray-600 font-medium">Active Referrals</p>
                    <p class="text-sm text-gray-500 mt-1">Currently active</p>
                </div>
            </div>
        </div>

        <!-- Monthly Earnings -->
        <div class="bg-white rounded-xl shadow-lg p-6 border-l-4 border-yellow-500 hover:shadow-xl transition-shadow duration-200">
            <div class="flex items-center">
                <div class="p-3 bg-yellow-100 rounded-lg">
                    <i class="fas fa-coins text-2xl text-yellow-600"></i>
                </div>
                <div class="ml-4">
                    <h3 class="text-3xl font-bold text-gray-900 mb-1">KES <?php echo number_format($monthlyEarnings, 0); ?></h3>
                    <p class="text-gray-600 font-medium">This Month</p>
                    <p class="text-sm text-gray-500 mt-1">Commission earned</p>
                </div>
            </div>
        </div>

        <!-- Conversion Rate -->
        <div class="bg-white rounded-xl shadow-lg p-6 border-l-4 border-blue-500 hover:shadow-xl transition-shadow duration-200">
            <div class="flex items-center">
                <div class="p-3 bg-blue-100 rounded-lg">
                    <i class="fas fa-percentage text-2xl text-blue-600"></i>
                </div>
                <div class="ml-4">
                    <h3 class="text-3xl font-bold text-gray-900 mb-1">
                        <?php echo $totalReferrals > 0 ? round(($activeReferrals / $totalReferrals) * 100) : 0; ?>%
                    </h3>
                    <p class="text-gray-600 font-medium">Conversion Rate</p>
                    <p class="text-sm text-gray-500 mt-1">Active vs total referrals</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Referrals and Performance -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
        <!-- Recent Referrals -->
        <div class="bg-white rounded-xl shadow-lg p-6">
            <h3 class="text-xl font-bold text-gray-900 mb-6 flex items-center">
                <i class="fas fa-history mr-3 text-purple-600"></i>
                Recent Referrals
            </h3>

            <?php if (!empty($recentReferrals)): ?>
                <div class="space-y-4 max-h-96 overflow-y-auto">
                    <?php foreach ($recentReferrals as $referral): ?>
                        <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg">
                            <div class="flex items-center space-x-3">
                                <div class="w-10 h-10 bg-purple-100 rounded-full flex items-center justify-center">
                                    <span class="text-purple-600 font-semibold">
                                        <?php echo strtoupper(substr($referral['name'], 0, 1)); ?>
                                    </span>
                                </div>
                                <div>
                                    <p class="font-medium text-gray-900"><?php echo htmlspecialchars($referral['name']); ?></p>
                                    <p class="text-sm text-gray-500"><?php echo htmlspecialchars($referral['email']); ?></p>
                                </div>
                            </div>
                            <div class="text-right">
                                <span class="px-3 py-1 text-sm font-medium rounded-full
                                    <?php
                                    switch($referral['status']) {
                                        case 'active': echo 'bg-green-100 text-green-800'; break;
                                        case 'inactive': echo 'bg-yellow-100 text-yellow-800'; break;
                                        default: echo 'bg-gray-100 text-gray-800';
                                    }
                                    ?>">
                                    <?php echo $referral['display_status']; ?>
                                </span>
                                <p class="text-xs text-gray-500 mt-1"><?php echo date('M j, g:i A', strtotime($referral['referral_date'])); ?></p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="mt-6 pt-4 border-t border-gray-200">
                    <a href="referrals.php" class="text-purple-600 hover:text-purple-700 font-medium text-sm flex items-center justify-center">
                        <i class="fas fa-list mr-2"></i>
                        View All Referrals
                    </a>
                </div>
            <?php else: ?>
                <div class="text-center py-8">
                    <i class="fas fa-users text-4xl text-gray-300 mb-3"></i>
                    <p class="text-gray-500">No referrals yet.</p>
                    <p class="text-sm text-gray-400 mt-2">Start referring people to see your network grow.</p>
                </div>
            <?php endif; ?>
        </div>

        <!-- Performance Metrics -->
        <div class="bg-white rounded-xl shadow-lg p-6">
            <h3 class="text-xl font-bold text-gray-900 mb-6 flex items-center">
                <i class="fas fa-chart-line mr-3 text-purple-600"></i>
                Performance Overview
            </h3>

            <div class="space-y-6">
                <!-- Monthly Progress -->
                <div class="grid grid-cols-2 gap-4">
                    <div class="text-center p-4 bg-purple-50 rounded-lg">
                        <div class="text-2xl font-bold text-purple-600"><?php echo $totalReferrals; ?></div>
                        <div class="text-sm text-purple-700">Total Referrals</div>
                    </div>
                    <div class="text-center p-4 bg-green-50 rounded-lg">
                        <div class="text-2xl font-bold text-green-600"><?php echo $activeReferrals; ?></div>
                        <div class="text-sm text-green-700">Active</div>
                    </div>
                </div>

                <!-- Achievement Indicators -->
                <div class="space-y-3">
                    <div class="flex items-center justify-between">
                        <span class="text-gray-700">Ambassador Level</span>
                        <span class="text-sm font-medium text-purple-600">Bronze</span>
                    </div>

                    <div class="flex items-center justify-between">
                        <span class="text-gray-700">Monthly Goal</span>
                        <div class="flex items-center space-x-2">
                            <div class="w-24 bg-gray-200 rounded-full h-2">
                                <div class="bg-purple-500 h-2 rounded-full" style="width: <?php echo min(($totalReferrals / 10) * 100, 100); ?>%"></div>
                            </div>
                            <span class="text-sm font-medium text-gray-900"><?php echo $totalReferrals; ?>/10</span>
                        </div>
                    </div>

                    <div class="flex items-center justify-between">
                        <span class="text-gray-700">Success Rate</span>
                        <div class="flex items-center space-x-2">
                            <div class="w-24 bg-gray-200 rounded-full h-2">
                                <div class="bg-green-500 h-2 rounded-full" style="width: <?php echo $totalReferrals > 0 ? ($activeReferrals / $totalReferrals) * 100 : 0; ?>%"></div>
                            </div>
                            <span class="text-sm font-medium text-gray-900">
                                <?php echo $totalReferrals > 0 ? round(($activeReferrals / $totalReferrals) * 100) : 0; ?>%
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Actions and Resources -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
        <!-- Quick Actions -->
        <div class="bg-white rounded-xl shadow-lg p-6">
            <h3 class="text-xl font-bold text-gray-900 mb-6 flex items-center">
                <i class="fas fa-bolt mr-3 text-purple-600"></i>
                Quick Actions
            </h3>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <a href="referrals.php"
                   class="bg-purple-600 hover:bg-purple-700 text-white px-4 py-3 rounded-lg font-medium transition-colors duration-200 flex items-center justify-center">
                    <i class="fas fa-users mr-2"></i>
                    View Referrals
                </a>

                <a href="earnings.php"
                   class="bg-green-600 hover:bg-green-700 text-white px-4 py-3 rounded-lg font-medium transition-colors duration-200 flex items-center justify-center">
                    <i class="fas fa-money-bill-wave mr-2"></i>
                    View Earnings
                </a>

                <a href="profile.php"
                   class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-3 rounded-lg font-medium transition-colors duration-200 flex items-center justify-center">
                    <i class="fas fa-user mr-2"></i>
                    My Profile
                </a>

                <a href="resources.php"
                   class="bg-gray-100 hover:bg-gray-200 text-gray-700 px-4 py-3 rounded-lg font-medium transition-colors duration-200 flex items-center justify-center">
                    <i class="fas fa-book mr-2"></i>
                    Resources
                </a>
            </div>
        </div>

        <!-- Ambassador Resources -->
        <div class="bg-white rounded-xl shadow-lg p-6">
            <h3 class="text-xl font-bold text-gray-900 mb-6 flex items-center">
                <i class="fas fa-graduation-cap mr-3 text-purple-600"></i>
                Ambassador Resources
            </h3>

            <div class="space-y-4">
                <div class="flex items-center justify-between p-3 bg-purple-50 rounded-lg">
                    <div class="flex items-center">
                        <i class="fas fa-share-alt text-purple-600 mr-3"></i>
                        <span class="font-medium text-purple-800">Referral Guide</span>
                    </div>
                    <a href="guide.php" class="text-purple-600 hover:text-purple-700 text-sm font-medium">View</a>
                </div>

                <div class="flex items-center justify-between p-3 bg-blue-50 rounded-lg">
                    <div class="flex items-center">
                        <i class="fas fa-chart-bar text-blue-600 mr-3"></i>
                        <span class="font-medium text-blue-800">Performance Tips</span>
                    </div>
                    <a href="tips.php" class="text-blue-600 hover:text-blue-700 text-sm font-medium">View</a>
                </div>

                <div class="flex items-center justify-between p-3 bg-green-50 rounded-lg">
                    <div class="flex items-center">
                        <i class="fas fa-question-circle text-green-600 mr-3"></i>
                        <span class="font-medium text-green-800">FAQ</span>
                    </div>
                    <a href="faq.php" class="text-green-600 hover:text-green-700 text-sm font-medium">View</a>
                </div>

                <div class="pt-4 border-t border-gray-200">
                    <div class="text-center">
                        <a href="contact.php" class="text-purple-600 hover:text-purple-700 font-medium text-sm">
                            <i class="fas fa-headset mr-2"></i>
                            Need Help? Contact Support
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Monthly Progress -->
    <div class="mt-8">
        <div class="bg-white rounded-xl shadow-lg p-6">
            <h3 class="text-xl font-bold text-gray-900 mb-6 flex items-center">
                <i class="fas fa-calendar mr-3 text-purple-600"></i>
                Monthly Progress
            </h3>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <!-- This Month -->
                <div class="text-center p-4 bg-purple-50 rounded-lg">
                    <i class="fas fa-calendar-day text-2xl text-purple-600 mb-2"></i>
                    <h4 class="font-semibold text-purple-800 mb-1">This Month</h4>
                    <p class="text-sm text-purple-600"><?php echo date('F Y'); ?></p>
                    <p class="text-xs text-purple-500 mt-2"><?php echo $totalReferrals; ?> referrals made</p>
                </div>

                <!-- Last Month -->
                <div class="text-center p-4 bg-blue-50 rounded-lg">
                    <i class="fas fa-calendar-week text-2xl text-blue-600 mb-2"></i>
                    <h4 class="font-semibold text-blue-800 mb-1">Last Month</h4>
                    <p class="text-sm text-blue-600"><?php echo date('F Y', strtotime('-1 month')); ?></p>
                    <p class="text-xs text-blue-500 mt-2">Growth tracking</p>
                </div>

                <!-- Goals -->
                <div class="text-center p-4 bg-green-50 rounded-lg">
                    <i class="fas fa-trophy text-2xl text-green-600 mb-2"></i>
                    <h4 class="font-semibold text-green-800 mb-1">Monthly Goal</h4>
                    <p class="text-sm text-green-600">10 Referrals</p>
                    <p class="text-xs text-green-500 mt-2"><?php echo round(($totalReferrals / 10) * 100); ?>% complete</p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
