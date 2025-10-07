<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include database configuration
require_once '../../includes/config.php';

// Check if user is logged in and is ambassador
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'ambassador') {
    header('Location: ../../auth/login.php');
    exit();
}

// Default page title
$page_title = $page_title ?? 'Ambassador Dashboard';

// Include activity logger for notifications
require_once '../../includes/ActivityLogger.php';
$activityLogger = new ActivityLogger($pdo);

// Get ambassador-specific data for header
try {
    global $pdo;

    // For demo purposes until database is updated with referral system
    // TODO: Update with proper queries once created_by column is added
    $totalReferrals = 0;
    $activeReferrals = 0;

    // Get unread notifications (this should work)
    $notificationCount = 0;
    if (isset($_SESSION['user_id'])) {
        $notificationStmt = $pdo->prepare("
            SELECT COUNT(*) as count
            FROM activity_logs
            WHERE user_id = ? AND is_read = 0
        ");
        $notificationStmt->execute([$_SESSION['user_id']]);
        $notificationCount = $notificationStmt->fetch(PDO::FETCH_ASSOC)['count'];
    }

    // Get recent ambassador activities (demo data for now)
    $recentActivities = [];

} catch (PDOException $e) {
    error_log("Ambassador header data error: " . $e->getMessage());
    $totalReferrals = 0;
    $activeReferrals = 0;
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
            ['name' => 'Ambassador Dashboard', 'url' => 'index.php', 'current' => true]
        ];
        break;
    case 'referrals':
        $breadcrumbs = [
            ['name' => 'Dashboard', 'url' => 'index.php', 'current' => false],
            ['name' => 'My Referrals', 'url' => 'referrals.php', 'current' => true]
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

        /* Ambassador theme colors */
        :root {
            --primary: #8B5CF6; /* Purple for ambassadors */
            --primary-dark: #7C3AED;
            --secondary: #EC4899; /* Pink for networking */
            --accent: #10B981; /* Green for success */
            --success: #10B981;
            --warning: #F59E0B;
            --danger: #EF4444;
        }

        body {
            background-color: #F9FAFB;
        }

        /* Sidebar styles for ambassador */
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
    </style>

    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#8B5CF6',
                        'primary-dark': '#7C3AED',
                        secondary: '#EC4899',
                        accent: '#10B981',
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
                        <i class="fas fa-handshake text-primary text-sm"></i>
                    </div>
                    <span class="text-white text-lg font-bold">Ambassador Hub</span>
                </div>
            </div>

            <!-- User Info -->
            <div class="px-4 py-4 border-b border-white/20">
                <div class="flex items-center space-x-3">
                    <div class="w-10 h-10 bg-white/20 rounded-full flex items-center justify-center">
                        <span class="text-white font-semibold">
                            <?php echo strtoupper(substr($_SESSION['username'] ?? $_SESSION['user_name'] ?? 'A', 0, 1)); ?>
                        </span>
                    </div>
                    <div>
                        <div class="text-white text-sm font-medium"><?php echo $_SESSION['username'] ?? $_SESSION['user_name'] ?? 'Ambassador'; ?></div>
                        <div class="text-white/70 text-xs">Brand Ambassador</div>
                    </div>
                </div>
            </div>

            <!-- Navigation -->
            <nav class="flex-1 px-2 py-4 space-y-1">
                <a href="index.php" class="flex items-center px-3 py-2 text-white hover:bg-white/20 rounded-lg transition-colors <?php echo $currentPage === 'index' || $currentPage === 'dashboard' ? 'bg-white/20' : ''; ?>">
                    <i class="fas fa-tachometer-alt mr-3"></i>
                    Dashboard
                </a>

                <a href="referrals.php" class="flex items-center px-3 py-2 text-white hover:bg-white/20 rounded-lg transition-colors <?php echo $currentPage === 'referrals' ? 'bg-white/20' : ''; ?>">
                    <i class="fas fa-users mr-3"></i>
                    My Referrals
                    <?php if ($totalReferrals > 0): ?>
                        <span class="ml-auto bg-green-500 text-white text-xs px-2 py-1 rounded-full"><?php echo $totalReferrals; ?></span>
                    <?php endif; ?>
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

                <a href="../chat.php" class="flex items-center px-3 py-2 text-white hover:bg-white/20 rounded-lg transition-colors">
                    <i class="fas fa-headset mr-3"></i>
                    Support
                </a>
            </nav>

            <!-- Logout -->
            <div class="p-4 border-t border-white/20">
                <a href="../../auth/logout.php" class="flex items-center px-3 py-2 text-white hover:bg-red-600 rounded-lg transition-colors w-full">
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
                        <!-- Quick Actions for Ambassador -->
                        <?php if ($currentPage === 'index' || $currentPage === 'dashboard'): ?>
                            <a href="referrals.php"
                               class="bg-primary hover:bg-primary-dark text-white px-3 py-2 rounded-lg text-sm font-medium transition-all duration-200 flex items-center space-x-2">
                                <i class="fas fa-users text-xs"></i>
                                <span>View Referrals</span>
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
                                        <?php echo strtoupper(substr($_SESSION['username'] ?? $_SESSION['user_name'] ?? 'A', 0, 1)); ?>
                                    </span>
                                </div>
                                <span class="hidden md:block text-sm font-medium"><?php echo $_SESSION['username'] ?? $_SESSION['user_name'] ?? 'Ambassador'; ?></span>
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
                                <a href="../../auth/logout.php" class="block px-4 py-2 text-red-600 hover:bg-red-50">
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
