<?php
// admin/notifications.php - Notification management interface
$page_title = 'Notifications Management';
$page_description = 'View and manage system notifications';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    // If not logged in, redirect to login
    if (!isset($_SESSION['user_id'])) {
        header('Location: ../login.php');
        exit();
    }
    // If logged in but not admin, redirect to appropriate dashboard
    if (isset($_SESSION['role'])) {
        switch ($_SESSION['role']) {
            case 'driver':
            case 'delivery':
                exit();
            case 'ambassador':
                header('Location: ../dashboards/ambassador/index.php');
                exit();
            case 'customer':
                header('Location: ../account/customerdashboard.php');
                exit();
            default:
                header('Location: ../login.php');
                exit();
        }
    } else {
        header('Location: ../login.php');
        exit();
    }
}

// Include notification functions
require_once dirname(__DIR__) . '/includes/notifications.php';

// Handle POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['mark_all_read'])) {
        markAllNotificationsAsRead($_SESSION['user_id']);
        $_SESSION['message'] = ['type' => 'success', 'text' => 'All notifications marked as read'];
    } elseif (isset($_POST['delete_notification']) && isset($_POST['notification_id'])) {
        deleteNotification($_POST['notification_id'], $_SESSION['user_id']);
        $_SESSION['message'] = ['type' => 'success', 'text' => 'Notification deleted'];
    } elseif (isset($_POST['delete_read_notifications'])) {
        // Delete all read notifications for current user
        global $pdo;
        $stmt = $pdo->prepare("DELETE FROM notifications WHERE user_id = ? AND is_read = TRUE");
        $stmt->execute([$_SESSION['user_id']]);
        $_SESSION['message'] = ['type' => 'success', 'text' => 'All read notifications deleted'];
    }

    header('Location: notifications.php');
    exit();
}

// Get notification statistics
$notificationStats = getNotificationStats($_SESSION['user_id']);

// Get notifications for display
$notifications = getUserNotifications($_SESSION['user_id'], 50, false);

// Helper function for notification type icons
function getNotificationIcon($type) {
    $icons = [
        'info' => 'fa-info-circle text-blue-500',
        'success' => 'fa-check-circle text-green-500',
        'warning' => 'fa-exclamation-triangle text-yellow-500',
        'error' => 'fa-times-circle text-red-500',
        'system' => 'fa-cog text-gray-500'
    ];
    return $icons[$type] ?? 'fa-info-circle text-blue-500';
}

// Helper function for priority badges
function getPriorityBadge($priority) {
    $badges = [
        'low' => 'bg-gray-100 text-gray-800',
        'medium' => 'bg-blue-100 text-blue-800',
        'high' => 'bg-orange-100 text-orange-800',
        'urgent' => 'bg-red-100 text-red-800'
    ];
    return $badges[$priority] ?? 'bg-gray-100 text-gray-800';
}

// Breadcrumb for this page
$breadcrumbs = [
    ['name' => 'Dashboard', 'url' => 'dashboard.php', 'current' => false],
    ['name' => 'Notifications', 'url' => 'notifications.php', 'current' => true]
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?> - Addins Meals on Wheels</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
    <style>
        [x-cloak] { display: none !important; }
        .glass-effect {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        [data-theme="dark"] .glass-effect {
            background: rgba(30, 41, 59, 0.95);
            border: 1px solid rgba(51, 65, 85, 0.2);
        }
    </style>
</head>
<body class="bg-gray-100">
    <!-- Sidebar -->
    <aside class="sidebar bg-gray-800 text-white shadow-lg lg:z-10">
        <?php include 'sidebar.php'; ?>
    </aside>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Header -->
        <header class="bg-white shadow-sm header border-b border-gray-200">
            <div class="max-w-full px-4 py-3 sm:px-6 lg:px-8">
                <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between">
                    <div class="flex items-center justify-between w-full lg:w-auto">
                        <button type="button" class="lg:hidden p-2 rounded-md text-gray-700 hover:text-primary hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-primary">
                            <span class="sr-only">Open main menu</span>
                            <i class="fas fa-bars text-xl"></i>
                        </button>

                        <nav class="hidden sm:flex items-center space-x-2 text-sm">
                            <?php foreach ($breadcrumbs as $index => $breadcrumb): ?>
                                <?php if (!$breadcrumb['current']): ?>
                                    <a href="<?php echo $breadcrumb['url']; ?>" class="text-gray-600 hover:text-primary">
                                        <?php echo htmlspecialchars($breadcrumb['name']); ?>
                                    </a>
                                    <?php if ($index < count($breadcrumbs) - 1): ?>
                                        <i class="fas fa-chevron-right text-gray-400 text-xs"></i>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <span class="text-primary font-medium"><?php echo htmlspecialchars($breadcrumb['name']); ?></span>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </nav>

                        <div class="sm:hidden">
                            <h1 class="text-lg font-semibold text-gray-900"><?php echo htmlspecialchars($page_title); ?></h1>
                        </div>
                    </div>

                    <div class="flex items-center space-x-3">
                        <!-- Quick Actions -->
                        <div class="hidden lg:flex items-center space-x-2">
                            <button onclick="markAllAsRead()" class="bg-blue-500 hover:bg-blue-600 text-white px-3 py-2 rounded-lg text-sm">
                                <i class="fas fa-check-double mr-1"></i>Mark All Read
                            </button>
                            <button onclick="deleteReadNotifications()" class="bg-red-500 hover:bg-red-600 text-white px-3 py-2 rounded-lg text-sm">
                                <i class="fas fa-trash mr-1"></i>Delete Read
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </header>

        <!-- Page Content -->
        <div class="content-container">
            <div class="p-4 sm:p-6 lg:p-8">
                <h1 class="text-3xl font-bold tracking-tight text-gray-900 mb-6 hidden sm:block">
                    <?php echo htmlspecialchars($page_title); ?>
                </h1>

                <!-- Notification Stats -->
                <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
                    <div class="bg-white rounded-xl shadow-lg p-6 border">
                        <div class="flex items-center">
                            <div class="p-3 bg-blue-100 rounded-full">
                                <i class="fas fa-bell text-blue-600 text-xl"></i>
                            </div>
                            <div class="ml-4">
                                <h3 class="text-lg font-semibold text-gray-800">Total Notifications</h3>
                                <p class="text-2xl font-bold text-blue-600"><?php echo $notificationStats['total']; ?></p>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white rounded-xl shadow-lg p-6 border">
                        <div class="flex items-center">
                            <div class="p-3 bg-orange-100 rounded-full">
                                <i class="fas fa-envelope text-orange-600 text-xl"></i>
                            </div>
                            <div class="ml-4">
                                <h3 class="text-lg font-semibold text-gray-800">Unread</h3>
                                <p class="text-2xl font-bold text-orange-600"><?php echo $notificationStats['unread']; ?></p>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white rounded-xl shadow-lg p-6 border">
                        <div class="flex items-center">
                            <div class="p-3 bg-gray-100 rounded-full">
                                <i class="fas fa-cog text-gray-600 text-xl"></i>
                            </div>
                            <div class="ml-4">
                                <h3 class="text-lg font-semibold text-gray-800">System</h3>
                                <p class="text-2xl font-bold text-gray-600"><?php echo $notificationStats['system']; ?></p>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white rounded-xl shadow-lg p-6 border">
                        <div class="flex items-center">
                            <div class="p-3 bg-red-100 rounded-full">
                                <i class="fas fa-exclamation-triangle text-red-600 text-xl"></i>
                            </div>
                            <div class="ml-4">
                                <h3 class="text-lg font-semibold text-gray-800">Warnings/Errors</h3>
                                <p class="text-2xl font-bold text-red-600"><?php echo $notificationStats['warnings'] + $notificationStats['errors']; ?></p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Notifications List -->
                <div class="bg-white rounded-xl shadow-lg p-6">
                    <div class="flex items-center justify-between mb-6">
                        <h3 class="text-lg font-semibold text-gray-800">Recent Notifications</h3>
                        <div class="flex space-x-2">
                            <button onclick="markAllAsRead()" class="bg-blue-500 hover:bg-blue-600 text-white px-3 py-1 rounded text-sm lg:hidden">
                                <i class="fas fa-check-double mr-1"></i>Mark All Read
                            </button>
                            <button onclick="deleteReadNotifications()" class="bg-red-500 hover:bg-red-600 text-white px-3 py-1 rounded text-sm lg:hidden">
                                <i class="fas fa-trash mr-1"></i>Delete Read
                            </button>
                        </div>
                    </div>

                    <?php if (empty($notifications)): ?>
                        <div class="text-center py-12">
                            <i class="fas fa-bell-slash text-gray-300 text-4xl mb-4"></i>
                            <p class="text-gray-500 text-lg">No notifications yet</p>
                            <p class="text-gray-400 text-sm">Notifications will appear here when there are system updates or important messages.</p>
                        </div>
                    <?php else: ?>
                        <div class="space-y-4">
                            <?php foreach ($notifications as $notification): ?>
                                <div class="border rounded-lg p-4 <?php echo $notification['is_read'] ? 'bg-gray-50 border-gray-200' : 'bg-blue-50 border-blue-200'; ?>">
                                    <div class="flex items-start justify-between">
                                        <div class="flex items-start space-x-3 flex-1">
                                            <div class="flex-shrink-0">
                                                <i class="fas <?php echo getNotificationIcon($notification['type']); ?> text-lg"></i>
                                            </div>
                                            <div class="flex-1 min-w-0">
                                                <div class="flex items-center space-x-2 mb-2">
                                                    <h4 class="font-semibold text-gray-900 <?php echo $notification['is_read'] ? '' : 'text-blue-900'; ?>">
                                                        <?php echo htmlspecialchars($notification['title']); ?>
                                                    </h4>
                                                    <span class="px-2 py-1 text-xs font-medium rounded-full <?php echo getPriorityBadge($notification['priority']); ?>">
                                                        <?php echo ucfirst($notification['priority']); ?>
                                                    </span>
                                                    <?php if (!$notification['is_read']): ?>
                                                        <span class="px-2 py-1 text-xs font-medium rounded-full bg-blue-100 text-blue-800">
                                                            Unread
                                                        </span>
                                                    <?php endif; ?>
                                                </div>

                                                <p class="text-gray-700 text-sm mb-2">
                                                    <?php echo htmlspecialchars($notification['message']); ?>
                                                </p>

                                                <div class="flex items-center space-x-4 text-xs text-gray-500">
                                                    <span>
                                                        <i class="fas fa-clock mr-1"></i>
                                                        <?php echo date('M j, Y g:i A', strtotime($notification['created_at'])); ?>
                                                    </span>
                                                    <?php if ($notification['action_url']): ?>
                                                        <a href="<?php echo htmlspecialchars($notification['action_url']); ?>" class="text-blue-600 hover:text-blue-800">
                                                            <i class="fas fa-external-link-alt mr-1"></i>View Details
                                                        </a>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="flex items-center space-x-2 ml-4">
                                            <?php if (!$notification['is_read']): ?>
                                                <form method="POST" class="inline">
                                                    <input type="hidden" name="notification_id" value="<?php echo $notification['id']; ?>">
                                                    <button type="submit" name="mark_read" class="text-blue-600 hover:text-blue-800 p-1" title="Mark as Read">
                                                        <i class="fas fa-check"></i>
                                                    </button>
                                                </form>
                                            <?php endif; ?>

                                            <form method="POST" class="inline">
                                                <input type="hidden" name="notification_id" value="<?php echo $notification['id']; ?>">
                                                <button type="submit" name="delete_notification"
                                                        onclick="return confirm('Delete this notification?')"
                                                        class="text-red-600 hover:text-red-800 p-1" title="Delete">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Mark all notifications as read
        function markAllAsRead() {
            if (confirm('Mark all notifications as read?')) {
                document.getElementById('markAllReadForm').submit();
            }
        }

        // Delete all read notifications
        function deleteReadNotifications() {
            if (confirm('Delete all read notifications? This action cannot be undone.')) {
                document.getElementById('deleteReadForm').submit();
            }
        }

        // Auto-refresh notifications every 30 seconds
        setInterval(function() {
            // You could implement AJAX refresh here if needed
        }, 30000);
    </script>

    <!-- Hidden forms for bulk actions -->
    <form id="markAllReadForm" method="POST" style="display: none;">
        <input type="hidden" name="mark_all_read" value="1">
    </form>

    <form id="deleteReadForm" method="POST" style="display: none;">
        <input type="hidden" name="delete_read_notifications" value="1">
    </form>

    <?php if (isset($_SESSION['message'])): ?>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            Swal.fire({
                toast: true,
                position: 'top-end',
                icon: '<?php echo $_SESSION['message']['type']; ?>',
                title: '<?php echo addslashes($_SESSION['message']['text']); ?>',
                showConfirmButton: false,
                timer: 3000,
                timerProgressBar: true
            });
        });
    </script>
    <?php unset($_SESSION['message']); ?>
    <?php endif; ?>
</body>
</html>
