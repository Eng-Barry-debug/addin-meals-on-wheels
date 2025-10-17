<?php
// Start session and check delivery authentication
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Set default timezone to East Africa Time (Nairobi)
date_default_timezone_set('Africa/Nairobi');

// Ensure PDO connection is available
if (!isset($pdo) || !$pdo instanceof PDO) {
    require_once dirname(__DIR__, 2) . '/admin/includes/config.php';
}

global $pdo;

// Check if user is logged in and is delivery personnel
if (!isset($_SESSION['user_id']) || ($_SESSION['user_role'] !== 'delivery' && $_SESSION['user_role'] !== 'driver')) {
    header('Location: ../auth/login.php');
    exit();
}

// Default page title
$page_title = $page_title ?? 'Delivery Dashboard';

// Include activity logger for notifications
require_once dirname(__DIR__, 3) . '/includes/ActivityLogger.php';
require_once 'functions.php';
$activityLogger = new ActivityLogger($pdo);

// Get delivery-specific data for header
try {
    global $pdo;

    // Get pending deliveries count
    $headerPendingDeliveries = getCount($pdo, 'orders', 'status = "out_for_delivery"');

    // Get completed deliveries today
    $completedToday = getCount($pdo, 'orders', 'status = "delivered" AND DATE(updated_at) = CURDATE()');

    // Get unread notifications
    $notificationCount = 0;
    if (isset($_SESSION['user_id'])) {
        $notificationStmt = $pdo->prepare("
            SELECT COUNT(*) as count
            FROM activity_logs
            WHERE is_read = 0 AND user_id = ?
        ");
        $notificationStmt->execute([$_SESSION['user_id']]);
        $notificationCount = $notificationStmt->fetch(PDO::FETCH_ASSOC)['count'];
    }

    // Get recent delivery activities
    $recentActivitiesStmt = $pdo->prepare("
        SELECT al.*, u.name as user_name
        FROM activity_logs al
        LEFT JOIN users u ON al.user_id = u.id
        WHERE al.activity_type = 'delivery'
        AND al.created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
        ORDER BY al.created_at DESC
        LIMIT 5
    ");
    $recentActivitiesStmt->execute();
    $recentActivities = $recentActivitiesStmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    error_log("Delivery header data error: " . $e->getMessage());
    $headerPendingDeliveries = 0;
    $completedToday = 0;
    $notificationCount = 0;
    $recentActivities = [];
}

// Breadcrumb generation based on current page
$currentPage = basename($_SERVER['PHP_SELF'], '.php');
$breadcrumbs = [];

switch($currentPage) {
    case 'index':
    case 'dashboard':
        $breadcrumbs = [
            ['name' => 'Delivery Dashboard', 'url' => 'index.php', 'current' => true]
        ];
        break;
    case 'deliveries':
        $breadcrumbs = [
            ['name' => 'Dashboard', 'url' => 'index.php', 'current' => false],
            ['name' => 'My Deliveries', 'url' => 'deliveries.php', 'current' => true]
        ];
        break;
    case 'routes':
        $breadcrumbs = [
            ['name' => 'Dashboard', 'url' => 'index.php', 'current' => false],
            ['name' => 'Delivery Routes', 'url' => 'routes.php', 'current' => true]
        ];
        break;
    case 'earnings':
        $breadcrumbs = [
            ['name' => 'Dashboard', 'url' => 'index.php', 'current' => false],
            ['name' => 'My Earnings', 'url' => 'earnings.php', 'current' => true]
        ];
        break;
    case 'profile':
        $breadcrumbs = [
            ['name' => 'Dashboard', 'url' => 'index.php', 'current' => false],
            ['name' => 'My Profile', 'url' => 'profile.php', 'current' => true]
        ];
        break;
    default:
        $breadcrumbs = [
            ['name' => 'Dashboard', 'url' => 'index.php', 'current' => false],
            ['name' => ucfirst(str_replace('_', ' ', $currentPage)), 'url' => $_SERVER['PHP_SELF'], 'current' => true]
        ];
}
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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
    <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>

    <style>
        [x-cloak] { display: none !important; }

        /* Delivery theme colors */
        :root {
            --primary: #10B981; /* Emerald green for delivery */
            --primary-dark: #059669;
            --secondary: #F59E0B; /* Amber for earnings */
            --accent: #3B82F6; /* Blue for routes */
            --success: #10B981;
            --warning: #F59E0B;
            --danger: #EF4444;
        }

        body {
            background-color: #F9FAFB;
        }

        /* Sidebar styles for delivery */
        .sidebar {
            width: 16rem;
            height: 100vh;
            position: fixed;
            left: 0;
            top: 0;
            z-index: 10;
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
        }

        .main-content {
            margin-left: 16rem;
            min-height: 100vh;
            background-color: #F9FAFB;
        }

        .header {
            position: fixed;
            top: 0;
            right: 0;
            left: 16rem;
            z-index: 50;
            background: white;
            border-bottom: 1px solid #E5E7EB;
            height: 4rem;
        }

        .content-container {
            padding-top: 4rem;
            padding: 1.5rem;
        }

        @media (max-width: 1024px) {
            .sidebar {
                transform: translateX(-100%);
                transition: transform 0.3s ease-in-out;
            }
            .sidebar.open {
                transform: translateX(0);
            }
            .main-content, .header {
                left: 0;
            }
        }

        /* Notification animations */
        .notification-badge {
            animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
        }

        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.7; }
        }

        /* Status indicators */
        .status-indicator {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            display: inline-block;
            margin-right: 8px;
        }

        .status-active { background-color: var(--success); }
        .status-inactive { background-color: #6B7280; }
        .status-busy { background-color: var(--warning); }
    </style>

    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#10B981',
                        'primary-dark': '#059669',
                        secondary: '#F59E0B',
                        accent: '#3B82F6',
                    }
                }
            }
        }

        // Show alert from PHP session
        <?php if (isset($_SESSION['message'])): ?>
        document.addEventListener('DOMContentLoaded', function() {
            const Toast = Swal.mixin({
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 3000,
                timerProgressBar: true,
            });

            Toast.fire({
                icon: '<?php echo $_SESSION['message']['type']; ?>',
                title: '<?php echo addslashes($_SESSION['message']['text']); ?>'
            });
        });
        <?php unset($_SESSION['message']); ?>
        <?php endif; ?>

        // Delivery-specific functions
        function updateDeliveryStatus(orderId, status) {
            fetch('api/update_delivery_status.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ order_id: orderId, status: status })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Status Updated',
                        text: `Order ${orderId} status updated to ${status}`,
                        showConfirmButton: false,
                        timer: 2000
                    });
                    location.reload();
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: data.message
                    });
                }
            })
            .catch(error => {
                console.error('Error:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'Network Error',
                    text: 'Could not update delivery status.'
                });
            });
        }

        function confirmDelivery(orderId) {
            Swal.fire({
                title: 'Confirm Delivery',
                text: `Mark order ${orderId} as delivered?`,
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#10B981',
                cancelButtonColor: '#6B7280',
                confirmButtonText: 'Yes, Delivered'
            }).then((result) => {
                if (result.isConfirmed) {
                    updateDeliveryStatus(orderId, 'delivered');
                }
            });
        }
    </script>
</head>
<body class="bg-gray-100" x-data="{ sidebarOpen: window.innerWidth >= 1024 }" @resize.window="sidebarOpen = window.innerWidth >= 1024">
    <!-- Mobile sidebar backdrop -->
    <div x-show="sidebarOpen && window.innerWidth < 1024"
         @click="sidebarOpen = false"
         class="fixed inset-0 z-40 bg-black bg-opacity-50 lg:hidden"
         x-transition:enter="transition-opacity ease-linear duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition-opacity ease-linear duration-300"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         x-cloak>
    </div>

    <!-- Sidebar -->
    <div class="sidebar" x-show="sidebarOpen" x-transition:enter="transition-transform duration-300" x-transition:enter-start="-translate-x-full" x-transition:enter-end="translate-x-0">
        <div class="flex flex-col h-full">
            <!-- Logo -->
            <div class="flex items-center justify-center h-16 px-4 bg-white/10">
                <div class="flex items-center space-x-2">
                    <div class="w-8 h-8 bg-white rounded-full flex items-center justify-center">
                        <i class="fas fa-truck text-primary text-sm"></i>
                    </div>
                    <span class="text-white text-lg font-bold">Delivery Hub</span>
                </div>
            </div>

            <!-- User Info -->
            <div class="px-4 py-4 border-b border-white/20">
                <div class="flex items-center space-x-3">
                    <div class="w-10 h-10 bg-white/20 rounded-full flex items-center justify-center">
                        <span class="text-white font-semibold">
                            <?php echo strtoupper(substr($_SESSION['username'] ?? $_SESSION['user_name'] ?? 'D', 0, 1)); ?>
                        </span>
                    </div>
                    <div>
                        <div class="text-white text-sm font-medium"><?php echo $_SESSION['username'] ?? $_SESSION['user_name'] ?? 'Delivery Person'; ?></div>
                        <div class="text-white/70 text-xs">Delivery Personnel</div>
                    </div>
                </div>
            </div>

            <!-- Navigation -->
            <nav class="flex-1 px-2 py-4 space-y-1">
                <a href="index.php" class="flex items-center px-3 py-2 text-white hover:bg-white/20 rounded-lg transition-colors <?php echo $currentPage === 'index' || $currentPage === 'dashboard' ? 'bg-white/20' : ''; ?>">
                    <i class="fas fa-tachometer-alt mr-3"></i>
                    Dashboard
                </a>

                <a href="deliveries.php" class="flex items-center px-3 py-2 text-white hover:bg-white/20 rounded-lg transition-colors <?php echo $currentPage === 'deliveries' ? 'bg-white/20' : ''; ?>">
                    <i class="fas fa-box mr-3"></i>
                    My Deliveries
                    <?php if ($headerPendingDeliveries > 0): ?>
                        <span class="ml-auto bg-red-500 text-white text-xs px-2 py-1 rounded-full"><?php echo $headerPendingDeliveries; ?></span>
                    <?php endif; ?>
                </a>

                <a href="routes.php" class="flex items-center px-3 py-2 text-white hover:bg-white/20 rounded-lg transition-colors <?php echo $currentPage === 'routes' ? 'bg-white/20' : ''; ?>">
                    <i class="fas fa-route mr-3"></i>
                    Delivery Routes
                </a>

                <a href="earnings.php" class="flex items-center px-3 py-2 text-white hover:bg-white/20 rounded-lg transition-colors <?php echo $currentPage === 'earnings' ? 'bg-white/20' : ''; ?>">
                    <i class="fas fa-money-bill-wave mr-3"></i>
                    My Earnings
                </a>

                <div class="border-t border-white/20 my-4"></div>

                <a href="profile.php" class="flex items-center px-3 py-2 text-white hover:bg-white/20 rounded-lg transition-colors <?php echo $currentPage === 'profile' ? 'bg-white/20' : ''; ?>">
                    <i class="fas fa-user mr-3"></i>
                    My Profile
                </a>

                <a href="help.php" class="flex items-center px-3 py-2 text-white hover:bg-white/20 rounded-lg transition-colors">
                    <i class="fas fa-headset mr-3"></i>
                    Support
                </a>
            </nav>

            <!-- Logout -->
            <div class="p-4 border-t border-white/20">
                <a href="../auth/logout.php" class="flex items-center px-3 py-2 text-white hover:bg-red-600 rounded-lg transition-colors w-full">
                    <i class="fas fa-sign-out-alt mr-3"></i>
                    Sign Out
                </a>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Header -->
        <header class="header">
            <div class="max-w-full px-4 py-3 sm:px-6 lg:px-8">
                <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between space-y-4 lg:space-y-0">
                    <!-- Top Row: Mobile menu toggle and breadcrumbs -->
                    <div class="flex items-center justify-between">
                        <button @click="sidebarOpen = !sidebarOpen"
                                class="text-gray-600 hover:text-gray-900 hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-primary focus:ring-offset-2 rounded-lg p-2 lg:hidden transition-all duration-200">
                            <i class="fas fa-bars text-xl"></i>
                        </button>

                        <!-- Breadcrumbs -->
                        <nav class="hidden sm:flex items-center space-x-2 text-sm">
                            <?php foreach ($breadcrumbs as $index => $breadcrumb): ?>
                                <?php if (!$breadcrumb['current']): ?>
                                    <a href="<?php echo $breadcrumb['url']; ?>"
                                       class="text-gray-600 hover:text-primary transition-colors duration-200">
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
                    </div>

                    <!-- Right side: Quick actions and notifications -->
                    <div class="flex items-center space-x-3">
                        <!-- Quick Actions for Delivery -->
                        <?php if ($currentPage === 'index' || $currentPage === 'dashboard'): ?>
                            <a href="deliveries.php"
                               class="bg-primary hover:bg-primary-dark text-white px-3 py-2 rounded-lg text-sm font-medium transition-all duration-200 flex items-center space-x-2">
                                <i class="fas fa-box text-xs"></i>
                                <span>View Deliveries</span>
                            </a>
                        <?php endif; ?>

                        <!-- Notifications -->
                        <div class="relative" x-data="{ notificationOpen: false }">
                            <button @click="notificationOpen = !notificationOpen"
                                    class="text-gray-600 hover:text-gray-900 hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-primary focus:ring-offset-2 rounded-lg p-2 relative transition-all duration-200">
                                <i class="far fa-bell text-xl"></i>
                                <?php if ($notificationCount > 0): ?>
                                    <span class="notification-badge absolute -top-1 -right-1 bg-red-500 text-white text-xs rounded-full h-5 w-5 flex items-center justify-center">
                                        <?php echo min($notificationCount, 99); ?>
                                    </span>
                                <?php endif; ?>
                            </button>

                            <!-- Notifications Dropdown -->
                            <div x-show="notificationOpen" @click.away="notificationOpen = false"
                                 class="absolute right-0 mt-2 w-80 bg-white rounded-lg shadow-lg z-50 border border-gray-200" x-cloak>
                                <div class="p-4 border-b border-gray-200">
                                    <h3 class="text-lg font-semibold text-gray-900">Notifications</h3>
                                </div>
                                <div class="max-h-64 overflow-y-auto">
                                    <?php if (!empty($recentActivities)): ?>
                                        <?php foreach ($recentActivities as $activity): ?>
                                            <div class="p-3 border-b border-gray-100 hover:bg-gray-50">
                                                <p class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($activity['description']); ?></p>
                                                <p class="text-xs text-gray-500 mt-1"><?php echo date('M j, g:i A', strtotime($activity['created_at'])); ?></p>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <div class="p-4 text-center text-gray-500">
                                            <p>No recent notifications</p>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <!-- Profile -->
                        <div class="relative" x-data="{ profileOpen: false }">
                            <button @click="profileOpen = !profileOpen"
                                    class="flex items-center space-x-2 text-gray-700 hover:text-gray-900 focus:outline-none focus:ring-2 focus:ring-primary focus:ring-offset-2 rounded-lg p-1">
                                <div class="w-8 h-8 bg-primary rounded-full flex items-center justify-center">
                                    <span class="text-white font-semibold text-sm">
                                        <?php echo strtoupper(substr($_SESSION['username'] ?? $_SESSION['user_name'] ?? 'D', 0, 1)); ?>
                                    </span>
                                </div>
                                <span class="hidden md:block text-sm font-medium"><?php echo $_SESSION['username'] ?? $_SESSION['user_name'] ?? 'Delivery'; ?></span>
                                <i class="fas fa-chevron-down text-xs"></i>
                            </button>

                            <!-- Profile Dropdown -->
                            <div x-show="profileOpen" @click.away="profileOpen = false"
                                 class="absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-lg z-50 border border-gray-200" x-cloak>
                                <a href="profile.php" class="block px-4 py-2 text-gray-700 hover:bg-gray-100">
                                    <i class="fas fa-user mr-2"></i>My Profile
                                </a>
                                <a href="earnings.php" class="block px-4 py-2 text-gray-700 hover:bg-gray-100">
                                    <i class="fas fa-money-bill-wave mr-2"></i>My Earnings
                                </a>
                                <div class="border-t border-gray-100"></div>
                                <a href="../auth/logout.php" class="block px-4 py-2 text-red-600 hover:bg-red-50">
                                    <i class="fas fa-sign-out-alt mr-2"></i>Sign Out
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </header>

        <!-- Page Content -->
        <main class="content-container">
