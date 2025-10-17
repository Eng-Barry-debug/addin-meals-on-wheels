<?php
// notifications.php - Ambassador Notifications

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
$page_title = 'Ambassador Notifications';

// Initialize variables
$user_id = $_SESSION['user_id'];
$notifications = [];
$error_message = '';

// Handle notification actions
if (isset($_GET['action']) && isset($_GET['id'])) {
    $action = $_GET['action'];
    $notification_id = (int)$_GET['id'];

    try {
        if ($action === 'mark_read') {
            $stmt = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?");
            $stmt->execute([$notification_id, $user_id]);
        } elseif ($action === 'mark_unread') {
            $stmt = $pdo->prepare("UPDATE notifications SET is_read = 0 WHERE id = ? AND user_id = ?");
            $stmt->execute([$notification_id, $user_id]);
        } elseif ($action === 'delete') {
            $stmt = $pdo->prepare("DELETE FROM notifications WHERE id = ? AND user_id = ?");
            $stmt->execute([$notification_id, $user_id]);
        }

        // Redirect to avoid re-submission
        header('Location: notifications.php');
        exit();
    } catch (PDOException $e) {
        $error_message = 'Error updating notification: ' . $e->getMessage();
    }
}

// Get notifications for current ambassador
try {
    $stmt = $pdo->prepare("
        SELECT n.*, r.referral_code, r.status as referral_status, u.name as referrer_name
        FROM notifications n
        LEFT JOIN referrals r ON n.reference_id = r.id AND n.type IN ('referral_update', 'referral_approved', 'commission_earned')
        LEFT JOIN ambassadors a ON r.ambassador_id = a.user_id
        LEFT JOIN users u ON a.user_id = u.id
        WHERE n.user_id = ?
        ORDER BY n.created_at DESC
        LIMIT 50
    ");
    $stmt->execute([$user_id]);
    $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Mark notifications as read when viewed
    $stmt = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ? AND is_read = 0");
    $stmt->execute([$user_id]);

} catch (PDOException $e) {
    $error_message = 'Error loading notifications: ' . $e->getMessage();
}

// Count unread notifications
try {
    $stmt = $pdo->prepare("SELECT COUNT(*) as unread_count FROM notifications WHERE user_id = ? AND is_read = 0");
    $stmt->execute([$user_id]);
    $unread_count = $stmt->fetch(PDO::FETCH_ASSOC)['unread_count'];
} catch (PDOException $e) {
    $unread_count = 0;
}

// Include activity logger
require_once '../../includes/ActivityLogger.php';
$activityLogger = new ActivityLogger($pdo);

// Log notifications page access
$activityLogger->log('ambassador', 'notifications_view', 'Ambassador accessed notifications', 'user', $user_id);

// Include header
require_once 'includes/header.php';
?>

<!-- Notifications Header -->
<div class="bg-gradient-to-br from-purple-600 via-purple-700 to-pink-600 text-white">
    <div class="px-6 py-12">
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between">
            <div>
                <h1 class="text-4xl md:text-5xl font-bold mb-3">Notifications</h1>
                <p class="text-xl opacity-90">Stay updated with your ambassador activities</p>
            </div>
            <div class="mt-6 lg:mt-0">
                <div class="flex items-center space-x-4">
                    <?php if ($unread_count > 0): ?>
                        <div class="flex items-center space-x-2 bg-red-500/20 backdrop-blur-sm rounded-lg px-3 py-2">
                            <i class="fas fa-bell text-red-300"></i>
                            <span class="text-sm font-medium"><?php echo $unread_count; ?> unread</span>
                        </div>
                    <?php endif; ?>
                    <div class="flex items-center space-x-2 bg-white/10 backdrop-blur-sm rounded-lg px-3 py-2">
                        <i class="fas fa-clock text-yellow-300"></i>
                        <span class="text-sm font-medium">Updated: <?php echo date('g:i A'); ?></span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Main Notifications Content -->
<div class="px-6 py-8">
    <?php if ($error_message): ?>
        <div class="mb-6 p-4 bg-red-50 border-l-4 border-red-400 text-red-700 rounded-lg flex items-center">
            <i class="fas fa-exclamation-circle mr-3"></i>
            <span><?php echo htmlspecialchars($error_message); ?></span>
        </div>
    <?php endif; ?>

    <!-- Quick Actions -->
    <div class="mb-6 flex flex-wrap gap-3">
        <button onclick="markAllAsRead()" class="bg-purple-600 hover:bg-purple-700 text-white px-4 py-2 rounded-lg font-medium transition-colors">
            <i class="fas fa-check-double mr-2"></i>
            Mark All as Read
        </button>
        <button onclick="clearAllNotifications()" class="bg-gray-100 hover:bg-gray-200 text-gray-700 px-4 py-2 rounded-lg font-medium transition-colors">
            <i class="fas fa-trash mr-2"></i>
            Clear All
        </button>
    </div>

    <?php if (empty($notifications)): ?>
        <!-- Empty State -->
        <div class="text-center py-16">
            <div class="mx-auto w-24 h-24 bg-gray-100 rounded-full flex items-center justify-center mb-6">
                <i class="fas fa-bell-slash text-4xl text-gray-400"></i>
            </div>
            <h3 class="text-xl font-semibold text-gray-900 mb-2">No notifications yet</h3>
            <p class="text-gray-600 mb-6">You'll receive notifications about referral updates, campaigns, and ambassador news here.</p>
            <a href="index.php" class="bg-purple-600 hover:bg-purple-700 text-white px-6 py-3 rounded-lg font-medium transition-colors inline-flex items-center">
                <i class="fas fa-arrow-left mr-2"></i>
                Back to Dashboard
            </a>
        </div>
    <?php else: ?>
        <!-- Notifications List -->
        <div class="space-y-4">
            <?php foreach ($notifications as $notification): ?>
                <div class="bg-white rounded-xl shadow-lg p-6 <?php echo !$notification['is_read'] ? 'border-l-4 border-purple-500' : ''; ?>">
                    <div class="flex items-start justify-between">
                        <div class="flex items-start space-x-4">
                            <!-- Notification Icon -->
                            <div class="flex-shrink-0">
                                <?php
                                $icon_class = 'fas fa-info-circle';
                                $icon_bg = 'bg-blue-100';
                                $icon_color = 'text-blue-600';

                                switch($notification['type']) {
                                    case 'referral_update':
                                        $icon_class = 'fas fa-users';
                                        $icon_bg = 'bg-green-100';
                                        $icon_color = 'text-green-600';
                                        break;
                                    case 'referral_approved':
                                        $icon_class = 'fas fa-check-circle';
                                        $icon_bg = 'bg-emerald-100';
                                        $icon_color = 'text-emerald-600';
                                        break;
                                    case 'commission_earned':
                                        $icon_class = 'fas fa-money-bill-wave';
                                        $icon_bg = 'bg-yellow-100';
                                        $icon_color = 'text-yellow-600';
                                        break;
                                    case 'campaign_invite':
                                        $icon_class = 'fas fa-bullhorn';
                                        $icon_bg = 'bg-purple-100';
                                        $icon_color = 'text-purple-600';
                                        break;
                                    case 'ambassador_update':
                                        $icon_class = 'fas fa-star';
                                        $icon_bg = 'bg-pink-100';
                                        $icon_color = 'text-pink-600';
                                        break;
                                }
                                ?>
                                <div class="w-10 h-10 <?php echo $icon_bg; ?> rounded-full flex items-center justify-center">
                                    <i class="<?php echo $icon_class; ?> <?php echo $icon_color; ?>"></i>
                                </div>
                            </div>

                            <!-- Notification Content -->
                            <div class="flex-grow">
                                <div class="flex items-start justify-between">
                                    <div>
                                        <h4 class="font-semibold text-gray-900 mb-1">
                                            <?php echo htmlspecialchars($notification['title']); ?>
                                        </h4>
                                        <p class="text-gray-600 text-sm leading-relaxed mb-2">
                                            <?php echo htmlspecialchars($notification['message']); ?>
                                        </p>

                                        <?php if ($notification['referral_code']): ?>
                                            <p class="text-xs text-gray-500">
                                                Referral Code: <?php echo htmlspecialchars($notification['referral_code']); ?>
                                                <?php if ($notification['referral_status']): ?>
                                                    • Status: <?php echo ucfirst(htmlspecialchars($notification['referral_status'])); ?>
                                                <?php endif; ?>
                                            </p>
                                        <?php endif; ?>
                                    </div>

                                    <!-- Notification Actions -->
                                    <div class="flex items-center space-x-2 ml-4">
                                        <?php if (!$notification['is_read']): ?>
                                            <a href="?action=mark_read&id=<?php echo $notification['id']; ?>"
                                               class="text-purple-600 hover:text-purple-700 text-sm font-medium">
                                                Mark as Read
                                            </a>
                                        <?php else: ?>
                                            <a href="?action=mark_unread&id=<?php echo $notification['id']; ?>"
                                               class="text-gray-500 hover:text-gray-700 text-sm font-medium">
                                                Mark as Unread
                                            </a>
                                        <?php endif; ?>

                                        <a href="?action=delete&id=<?php echo $notification['id']; ?>"
                                           class="text-red-500 hover:text-red-700 text-sm font-medium"
                                           onclick="return confirm('Are you sure you want to delete this notification?')">
                                            Delete
                                        </a>
                                    </div>
                                </div>

                                <!-- Timestamp -->
                                <div class="flex items-center justify-between mt-3 pt-3 border-t border-gray-100">
                                    <span class="text-xs text-gray-500">
                                        <?php echo date('M j, Y \a\t g:i A', strtotime($notification['created_at'])); ?>
                                    </span>

                                    <?php if (!$notification['is_read']): ?>
                                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-purple-100 text-purple-800">
                                            Unread
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Load More Button (if needed) -->
        <?php if (count($notifications) >= 50): ?>
            <div class="text-center mt-8">
                <button class="bg-gray-100 hover:bg-gray-200 text-gray-700 px-6 py-3 rounded-lg font-medium transition-colors">
                    Load More Notifications
                </button>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<script>
// Mark all notifications as read
function markAllAsRead() {
    if (confirm('Mark all notifications as read?')) {
        window.location.href = 'notifications.php?action=mark_all_read';
    }
}

// Clear all notifications
function clearAllNotifications() {
    if (confirm('Are you sure you want to delete all notifications? This cannot be undone.')) {
        window.location.href = 'notifications.php?action=clear_all';
    }
}

// Auto-refresh notifications every 30 seconds
setInterval(function() {
    // Check for new notifications without full page reload
}, 30000);
</script>

<?php require_once 'includes/footer.php'; ?>
