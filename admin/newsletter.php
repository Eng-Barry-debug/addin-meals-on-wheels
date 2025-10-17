<?php
// admin/newsletter.php - Admin interface for managing newsletter subscriptions

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
$page_title = 'Newsletter Management';
$page_description = 'Manage newsletter subscriptions and view subscriber list';

// 3. Include Core Dependencies
require_once dirname(__DIR__) . '/includes/config.php';
require_once 'includes/functions.php';

// 4. Handle POST Requests
$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (isset($_POST['unsubscribe_email'])) {
            $email = trim($_POST['email']);
            $stmt = $pdo->prepare("UPDATE newsletter_subscriptions SET is_active = FALSE, unsubscribed_at = CURRENT_TIMESTAMP WHERE email = ?");
            $stmt->execute([$email]);
            $message = 'Email unsubscribed successfully!';
            $message_type = 'success';
        }

        if (isset($_POST['export_subscribers'])) {
            // Export subscribers to CSV
            $stmt = $pdo->prepare("SELECT email, subscription_date, created_at FROM newsletter_subscriptions WHERE is_active = TRUE ORDER BY created_at DESC");
            $stmt->execute();
            $subscribers = $stmt->fetchAll(PDO::FETCH_ASSOC);

            header('Content-Type: text/csv');
            header('Content-Disposition: attachment; filename="newsletter_subscribers_' . date('Y-m-d') . '.csv"');

            $output = fopen('php://output', 'w');
            fputcsv($output, ['Email', 'Subscription Date', 'Signup Date']);

            foreach ($subscribers as $subscriber) {
                fputcsv($output, [
                    $subscriber['email'],
                    $subscriber['subscription_date'],
                    $subscriber['created_at']
                ]);
            }
            fclose($output);
            exit();
        }

    } catch (Exception $e) {
        $message = $e->getMessage();
        $message_type = 'error';
    }

    // Redirect to prevent form resubmission
    if ($message) {
        $_SESSION['message'] = ['type' => $message_type, 'text' => $message];
    }
    header('Location: newsletter.php');
    exit();
}

// 5. Get newsletter statistics and subscriber list
try {
    // Get total subscribers
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM newsletter_subscriptions WHERE is_active = TRUE");
    $stmt->execute();
    $total_subscribers = $stmt->fetchColumn();

    // Get recent subscribers
    $stmt = $pdo->prepare("SELECT * FROM newsletter_subscriptions WHERE is_active = TRUE ORDER BY created_at DESC LIMIT 10");
    $stmt->execute();
    $recent_subscribers = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get subscription stats by month
    $stmt = $pdo->prepare("
        SELECT
            DATE_FORMAT(created_at, '%Y-%m') as month,
            COUNT(*) as count
        FROM newsletter_subscriptions
        WHERE is_active = TRUE
        GROUP BY DATE_FORMAT(created_at, '%Y-%m')
        ORDER BY month DESC
        LIMIT 6
    ");
    $stmt->execute();
    $monthly_stats = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $total_subscribers = 0;
    $recent_subscribers = [];
    $monthly_stats = [];
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
                <form method="POST" class="inline">
                    <button type="submit" name="export_subscribers"
                           class="bg-white/20 hover:bg-white/30 text-white px-4 py-2 rounded-lg transition-colors">
                        <i class="fas fa-download mr-2"></i>Export CSV
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Main Content Area -->
<div class="container mx-auto px-6 py-8">
    <!-- Feedback Messages -->
    <?php if (isset($_SESSION['message'])): ?>
        <div class="mb-6 p-4 <?php echo $_SESSION['message']['type'] === 'success' ? 'bg-green-50 border-green-200 text-green-700' : 'bg-red-50 border-red-200 text-red-700'; ?> border rounded-lg">
            <p><?php echo htmlspecialchars($_SESSION['message']['text']); ?></p>
        </div>
        <?php unset($_SESSION['message']); ?>
    <?php endif; ?>

    <!-- Statistics Cards -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        <div class="bg-white rounded-xl shadow-lg p-6 border">
            <div class="flex items-center">
                <div class="p-3 bg-blue-100 rounded-full">
                    <i class="fas fa-envelope text-blue-600 text-xl"></i>
                </div>
                <div class="ml-4">
                    <h3 class="text-lg font-semibold text-gray-800">Total Subscribers</h3>
                    <p class="text-2xl font-bold text-blue-600"><?php echo number_format($total_subscribers); ?></p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-lg p-6 border">
            <div class="flex items-center">
                <div class="p-3 bg-green-100 rounded-full">
                    <i class="fas fa-user-plus text-green-600 text-xl"></i>
                </div>
                <div class="ml-4">
                    <h3 class="text-lg font-semibold text-gray-800">This Month</h3>
                    <p class="text-2xl font-bold text-green-600"><?php echo end($monthly_stats)['count'] ?? 0; ?></p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-lg p-6 border">
            <div class="flex items-center">
                <div class="p-3 bg-purple-100 rounded-full">
                    <i class="fas fa-chart-line text-purple-600 text-xl"></i>
                </div>
                <div class="ml-4">
                    <h3 class="text-lg font-semibold text-gray-800">Growth Rate</h3>
                    <p class="text-2xl font-bold text-purple-600">
                        <?php
                        if (count($monthly_stats) >= 2) {
                            $current = end($monthly_stats)['count'];
                            $previous = prev($monthly_stats)['count'] ?? 0;
                            $growth = $previous > 0 ? (($current - $previous) / $previous) * 100 : 0;
                            echo number_format($growth, 1) . '%';
                        } else {
                            echo '0%';
                        }
                        ?>
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Subscribers -->
    <div class="bg-white rounded-xl shadow-lg p-6 mb-8">
        <h3 class="text-lg font-semibold text-gray-800 mb-4">Recent Subscribers</h3>

        <?php if (empty($recent_subscribers)): ?>
            <p class="text-gray-500 text-center py-8">No subscribers yet. Newsletter subscriptions will appear here.</p>
        <?php else: ?>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Subscription Date</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Signup Date</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($recent_subscribers as $subscriber): ?>
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                    <?php echo htmlspecialchars($subscriber['email']); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <?php echo date('M j, Y', strtotime($subscriber['subscription_date'])); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <?php echo date('M j, Y', strtotime($subscriber['created_at'])); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <form method="POST" class="inline">
                                        <input type="hidden" name="email" value="<?php echo htmlspecialchars($subscriber['email']); ?>">
                                        <button type="submit" name="unsubscribe_email"
                                                onclick="return confirm('Are you sure you want to unsubscribe this email?')"
                                                class="text-red-600 hover:text-red-900">
                                            Unsubscribe
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

    <!-- Monthly Growth Chart -->
    <?php if (!empty($monthly_stats)): ?>
    <div class="bg-white rounded-xl shadow-lg p-6">
        <h3 class="text-lg font-semibold text-gray-800 mb-4">Subscription Growth (Last 6 Months)</h3>
        <div class="flex items-end space-x-2 h-48">
            <?php foreach (array_reverse($monthly_stats) as $stat): ?>
                <div class="flex-1 flex flex-col items-center">
                    <div class="bg-primary text-white text-xs px-2 py-1 rounded-t text-center w-full mb-1">
                        <?php echo date('M', strtotime($stat['month'] . '-01')); ?>
                    </div>
                    <div class="bg-primary/20 w-full flex-1 flex items-end justify-center rounded-t">
                        <div class="bg-primary text-white text-xs px-1 py-1 rounded text-center w-12"
                             style="height: <?php echo max(($stat['count'] / max(array_column($monthly_stats, 'count'))) * 100, 5); ?>%">
                            <?php echo $stat['count']; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>
</div>

<!-- Close HTML structure as required by header.php -->
</div>
</div>
</div>
</body>
</html>
