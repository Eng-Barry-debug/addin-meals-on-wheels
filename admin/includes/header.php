<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Set default timezone to East Africa Time (Nairobi)
date_default_timezone_set('Africa/Nairobi');

// Include database connection
require_once dirname(__DIR__, 2) . '/includes/config.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}

// Include activity logger for notifications
require_once dirname(__DIR__, 2) . '/includes/ActivityLogger.php';
$activityLogger = new ActivityLogger($pdo);

// Include functions for utility functions
require_once 'functions.php';

// Make PDO globally available for functions
global $pdo;

// Get notification count (recent activities in last 24 hours)
try {
    $notificationStmt = $pdo->prepare("
        SELECT COUNT(*) as count
        FROM activity_logs
        WHERE created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
        AND user_id != ?
    ");
    $notificationStmt->execute([$_SESSION['user_id'] ?? 0]);
    $notificationCount = $notificationStmt->fetch(PDO::FETCH_ASSOC)['count'];
} catch (PDOException $e) {
    $notificationCount = 0;
}

// Get recent notifications for dropdown
try {
    $recentNotificationsStmt = $pdo->prepare("
        SELECT al.*, u.name as user_name
        FROM activity_logs al
        LEFT JOIN users u ON al.user_id = u.id
        WHERE al.created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
        ORDER BY al.created_at DESC
        LIMIT 5
    ");
    $recentNotificationsStmt->execute();
    $recentNotifications = $recentNotificationsStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $recentNotifications = [];
}

// Get system status for header indicators
$systemStatus = [
    'database' => true,
    'orders_today' => 0,
    'pending_orders' => 0,
    'server_load' => 'normal'
];

try {
    $systemStatus['orders_today'] = getCount($pdo, 'orders', "status = 'completed' AND DATE(created_at) = CURDATE()");
    $systemStatus['pending_orders'] = getCount($pdo, 'orders', 'status = "pending"');
} catch (PDOException $e) {
    // Silent fail for status indicators
}

// Breadcrumb generation based on current page
$currentPage = basename($_SERVER['PHP_SELF'], '.php');
$breadcrumbs = [];

switch($currentPage) {
    case 'dashboard':
        $breadcrumbs = [
            ['name' => 'Dashboard', 'url' => 'dashboard.php', 'current' => true]
        ];
        break;
    case 'orders':
        $breadcrumbs = [
            ['name' => 'Dashboard', 'url' => 'dashboard.php', 'current' => false],
            ['name' => 'Orders', 'url' => 'orders.php', 'current' => true]
        ];
        break;
    case 'customers':
    case 'users':
        $breadcrumbs = [
            ['name' => 'Dashboard', 'url' => 'dashboard.php', 'current' => false],
            ['name' => 'Customers', 'url' => 'customers.php', 'current' => true]
        ];
        break;
    case 'menu':
        $breadcrumbs = [
            ['name' => 'Dashboard', 'url' => 'dashboard.php', 'current' => false],
            ['name' => 'Menu Management', 'url' => 'menu.php', 'current' => true]
        ];
        break;
    case 'customer_support':
        $breadcrumbs = [
            ['name' => 'Dashboard', 'url' => 'dashboard.php', 'current' => false],
            ['name' => 'Customer Support', 'url' => 'customer_support.php', 'current' => true]
        ];
        break;
    default:
        $breadcrumbs = [
            ['name' => 'Dashboard', 'url' => 'dashboard.php', 'current' => false],
            ['name' => ucfirst(str_replace('_', ' ', $currentPage)), 'url' => $_SERVER['PHP_SELF'], 'current' => true]
        ];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - Addins Meals on Wheels</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" integrity="sha512-iecdLmaskl7CVkqkXNQ/ZH/XLlvWZOJyj7Yy7tcenmpD1ypASozpmT/E0iPtmFIB46ZmdtAc9eNBvH0H/ZpiBw==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
    <style>
        [x-cloak] { display: none !important; }
        body {
            margin: 0;
            padding: 0;
        }
        .sidebar {
            width: 16rem;
            height: 100vh;
            position: fixed;
            left: 0;
            top: 0;
            z-index: 10;
        }
        .main-content {
            margin-left: 16rem;
            min-height: 100vh;
            background-color: #f3f4f6;
            display: flex;
            flex-direction: column;
        }
        .content-wrapper {
            flex: 1 0 auto;
        }
        /* Ensure header is always on top */
        .header {
            position: fixed;
            top: 0;
            right: 0;
            left: 16rem;
            z-index: 50;
            background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);
            backdrop-filter: blur(10px);
            border-bottom: 1px solid rgba(0, 0, 0, 0.1);
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06);
        }
        .content-container {
            padding-top: 4rem;
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

        /* Enhanced dropdown animations */
        .dropdown-menu {
            transform-origin: top right;
            animation: dropdownFade 0.15s ease-out;
        }

        @keyframes dropdownFade {
            from {
                opacity: 0;
                transform: scale(0.95) translateY(-4px);
            }
            to {
                opacity: 1;
                transform: scale(1) translateY(0);
            }
        }

        /* Notification badge pulse animation */
        .notification-badge {
            animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
        }

        /* Modern button hover effects */
        .header-button {
            transition: all 0.2s ease;
            position: relative;
            overflow: hidden;
        }

        .header-button::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: left 0.5s ease;
        }

        /* Advanced header animations */
        @keyframes slideInFromTop {
            from {
                transform: translateY(-100%);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        /* Confirm before delete */
        function confirmDelete(event, itemName = 'this item') {
            event.preventDefault();

            Swal.fire({
                title: 'Are you sure?',
                text: `You are about to delete ${itemName}. This action cannot be undone!`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#C1272D',
                cancelButtonColor: '#6B7280',
                confirmButtonText: 'Yes, delete it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    event.target.closest('form').submit();
                }
            });
        }

        @keyframes pulse {
            0%, 100% {
                opacity: 1;
            }
            50% {
                opacity: 0.7;
            }
        }

        @keyframes shimmer {
            0% {
                background-position: -200% 0;
            }
            100% {
                background-position: 200% 0;
            }
        }

        /* Header container animation */
        .header-container {
            animation: slideInFromTop 0.3s ease-out;
        }

        /* Quick actions hover effects */
        .quick-action-btn {
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
        }

        .quick-action-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
        }

        .quick-action-btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.3), transparent);
            transition: left 0.6s ease;
        }

        .quick-action-btn:hover::before {
            left: 100%;
        }

        /* Breadcrumb animations */
        .breadcrumb-item {
            transition: all 0.2s ease;
        }

        .breadcrumb-item:not(.current):hover {
            transform: translateY(-1px);
            color: #C1272D;
        }

        /* Search suggestions dropdown */
        .search-suggestions {
            animation: fadeInScale 0.2s ease-out;
            transform-origin: top center;
            z-index: 60;
        }

        /* Status indicator animations */
        .status-indicator {
            animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
        }

        .status-indicator.success {
            animation: none;
        }

        /* Theme toggle animation */
        .theme-toggle {
            transition: all 0.3s ease;
        }

        .theme-toggle:hover {
            transform: rotate(180deg) scale(1.1);
        }

        /* Notification badge pulse */
        .notification-badge {
            animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
        }

        /* Advanced dropdown animations */
        .advanced-dropdown {
            animation: fadeInScale 0.15s ease-out;
            transform-origin: top right;
            z-index: 60;
        }

        /* Mobile slide animations */
        @media (max-width: 1024px) {
            .mobile-menu {
                transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            }

            .mobile-menu.open {
                transform: translateX(0);
            }

            .mobile-menu.closed {
                transform: translateX(-100%);
            }
        }

        /* Glass morphism effect for modern look */
        .glass-effect {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        /* Ensure Alpine.js dropdowns appear above header */
        [x-show].absolute {
            z-index: 60 !important;
        }

        /* ===== THEME SYSTEM ===== */
        :root {
            --bg-primary: #ffffff;
            --bg-secondary: #f8fafc;
            --bg-tertiary: #f1f5f9;
            --text-primary: #1e293b;
            --text-secondary: #64748b;
            --text-muted: #94a3b8;
            --border-color: #e2e8f0;
            --shadow-color: rgba(0, 0, 0, 0.1);
            --card-bg: #ffffff;
            --input-bg: #ffffff;
            --sidebar-bg: #ffffff;
        }

        /* Dark theme variables */
        [data-theme="dark"] {
            --bg-primary: #0f172a;
            --bg-secondary: #1e293b;
            --bg-tertiary: #334155;
            --text-primary: #f1f5f9;
            --text-secondary: #cbd5e1;
            --text-muted: #94a3b8;
            --border-color: #334155;
            --shadow-color: rgba(0, 0, 0, 0.3);
            --card-bg: #1e293b;
            --input-bg: #334155;
            --sidebar-bg: #1e293b;
        }

        /* Apply theme variables to elements */
        body {
            background-color: var(--bg-primary);
            color: var(--text-primary);
            transition: background-color 0.3s ease, color 0.3s ease;
        }

        .main-content {
            background-color: var(--bg-secondary);
        }

        .bg-white {
            background-color: var(--card-bg) !important;
        }

        .bg-gray-50 {
            background-color: var(--bg-tertiary) !important;
        }

        .bg-gray-100 {
            background-color: var(--bg-tertiary) !important;
        }

        .text-gray-900 {
            color: var(--text-primary) !important;
        }

        .text-gray-700 {
            color: var(--text-secondary) !important;
        }

        .text-gray-600 {
            color: var(--text-secondary) !important;
        }

        .text-gray-500 {
            color: var(--text-muted) !important;
        }

        .border-gray-200 {
            border-color: var(--border-color) !important;
        }

        .border-gray-300 {
            border-color: var(--border-color) !important;
        }

        /* Input fields */
        input, textarea, select {
            background-color: var(--input-bg) !important;
            color: var(--text-primary) !important;
            border-color: var(--border-color) !important;
        }

        /* Sidebar theme */
        .sidebar {
            background-color: var(--sidebar-bg) !important;
        }

        /* Header theme */
        .header {
            background: linear-gradient(135deg, var(--card-bg) 0%, var(--bg-secondary) 100%) !important;
        }

        /* Shadow adjustments for dark theme */
        [data-theme="dark"] .shadow-lg {
            box-shadow: 0 10px 15px -3px var(--shadow-color), 0 4px 6px -2px var(--shadow-color) !important;
        }

        [data-theme="dark"] .shadow-xl {
            box-shadow: 0 20px 25px -5px var(--shadow-color), 0 10px 10px -5px var(--shadow-color) !important;
        }

        /* Smooth transitions for theme changes */
        *, *::before, *::after {
            transition: background-color 0.3s ease, color 0.3s ease, border-color 0.3s ease;
        }

        /* Hide dropdowns by default to prevent flash */
        .dropdown-content {
            display: none;
        }

        /* Show dropdowns only when x-show is true */
        [x-show="themeOpen"] .dropdown-content,
        [x-show="chatOpen"] .dropdown-content,
        [x-show="notificationOpen"] .dropdown-content,
        [x-show="profileOpen"] .dropdown-content {
            display: block;
        }

        /* Alternative: Hide all dropdown divs initially */
        .theme-dropdown,
        .chat-dropdown,
        .notification-dropdown,
        .profile-dropdown {
            display: none;
        }

        /* Show when their respective x-show conditions are met */
        [x-show="themeOpen"] .theme-dropdown,
        [x-show="chatOpen"] .chat-dropdown,
        [x-show="notificationOpen"] .notification-dropdown,
        [x-show="profileOpen"] .profile-dropdown {
            display: block;
        }
    </style>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#fc7703',
                        secondary: '#D4AF37',
                        dark: '#1A1A1A',
                        light: '#F5E6D3',
                        accent: '#2E5E3A'
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
                didOpen: (toast) => {
                    toast.addEventListener('mouseenter', Swal.stopTimer)
                    toast.addEventListener('mouseleave', Swal.resumeTimer)
                }
            });
            
            Toast.fire({
                icon: '<?php echo $_SESSION['message']['type']; ?>',
                title: '<?php echo addslashes($_SESSION['message']['text']); ?>'
            });
        });
        <?php unset($_SESSION['message']); ?>
        <?php endif; ?>

        // Theme management functions
        function toggleTheme() {
            const currentTheme = Alpine.store('theme') || 'light';
            const newTheme = currentTheme === 'light' ? 'dark' : 'light';
            setTheme(newTheme);
        }

        function setTheme(theme) {
            // Store theme preference
            Alpine.store('theme', theme);
            localStorage.setItem('admin_theme', theme);

            // Update UI
            document.documentElement.setAttribute('data-theme', theme);

            // Update button state
            const themeButton = document.querySelector('[x-data*="themeOpen"] button');
            if (themeButton) {
                themeButton.classList.toggle('text-yellow-400', theme === 'dark');
                themeButton.classList.toggle('text-gray-600', theme === 'light');
            }

            // Show feedback only if this is an actual theme change, not initialization
            if (window.themeInitialized) {
                showThemeNotification(theme);
            }
        }

        function showThemeNotification(theme) {
            const themeName = theme === 'dark' ? 'Dark Mode' : 'Light Mode';

            // Show a toast notification instead of console.log
            Swal.fire({
                toast: true,
                position: 'top-end',
                icon: 'success',
                title: `Switched to ${themeName}`,
                showConfirmButton: false,
                timer: 2000,
                timerProgressBar: true,
                background: theme === 'dark' ? '#1e293b' : '#ffffff',
                color: theme === 'dark' ? '#f1f5f9' : '#1e293b'
            });
        }

        // Initialize theme on page load
        document.addEventListener('DOMContentLoaded', function() {
            const savedTheme = localStorage.getItem('admin_theme') || 'light';
            window.themeInitialized = true; // Mark that we're initializing, not switching
            setTheme(savedTheme);

            // Ensure dropdowns are properly closed on page load
            window.dropdownsInitialized = true;

            // Initialize Alpine.js store if not exists
            if (!Alpine.store('theme')) {
                Alpine.store('theme', 'light');
            }
        });
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
         x-transition:leave-end="opacity-0">
    </div>

    <!-- Sidebar -->
    <?php include 'sidebar.php'; ?>
                    <i class="fas fa-tachometer-alt mr-1"></i> Dashboard
                </a>
                <a href="orders.php" class="px-3 py-4 text-sm font-medium border-b-2 border-transparent hover:border-gray-300 hover:text-gray-700 <?php echo basename($_SERVER['PHP_SELF']) == 'orders.php' ? 'border-primary text-primary' : 'text-gray-500'; ?>">
                </a>
                <a href="menu.php" class="px-3 py-4 text-sm font-medium border-b-2 border-transparent hover:border-gray-300 hover:text-gray-700 <?php echo basename($_SERVER['PHP_SELF']) == 'menu.php' ? 'border-primary text-primary' : 'text-gray-500'; ?>">
                    <i class="fas fa-utensils mr-1"></i> Menu Items
                </a>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Enhanced Modern Header -->
        <header class="bg-dark shadow-sm header border-b border-gray-200 header-container">
            <div class="max-w-full px-4 py-3 sm:px-6 lg:px-8">
                <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between space-y-4 lg:space-y-0">
                    <!-- Top Row: Breadcrumbs and Quick Actions -->
                    <div class="flex items-center justify-between">
                        <!-- Left side - Breadcrumbs and Mobile menu -->
                        <div class="flex items-center space-x-4">
                            <button @click="sidebarOpen = !sidebarOpen"
                                    title="Open navigation menu"
                                    aria-label="Open navigation menu"
                                    class="header-button text-gray-600 hover:text-gray-900 hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-primary focus:ring-offset-2 rounded-lg p-2 lg:hidden transition-all duration-200">
                                <i class="fas fa-bars text-xl" aria-hidden="true"></i>
                            </button>

                            <!-- Breadcrumb Navigation -->
                            <nav class="hidden sm:flex items-center space-x-2 text-sm">
                                <?php foreach ($breadcrumbs as $index => $breadcrumb): ?>
                                    <?php if (!$breadcrumb['current']): ?>
                                        <a href="<?php echo $breadcrumb['url']; ?>"
                                           class="breadcrumb-item text-gray-600 hover:text-primary transition-colors duration-200">
                                            <?php echo htmlspecialchars($breadcrumb['name']); ?>
                                        </a>
                                        <?php if ($index < count($breadcrumbs) - 1): ?>
                                            <i class="fas fa-chevron-right text-gray-400 text-xs"></i>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span class="breadcrumb-item current text-primary font-medium"><?php echo htmlspecialchars($breadcrumb['name']); ?></span>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </nav>

                            <!-- Mobile page title -->
                            <div class="sm:hidden">
                                <h1 class="text-lg font-semibold text-gray-900"><?php echo $page_title; ?></h1>
                            </div>
                        </div>

                        <!-- Quick Actions Bar -->
                        <div class="hidden lg:flex items-center space-x-2">
                            <?php if (basename($_SERVER['PHP_SELF']) === 'dashboard.php'): ?>
                                <a href="orders.php" title="Create a new customer order" class="quick-action-btn bg-primary hover:bg-primary-dark text-white px-3 py-2 rounded-lg text-sm font-medium transition-all duration-200 flex items-center space-x-2">
                                    <i class="fas fa-plus text-xs"></i>
                                    <span>Create New Order</span>
                                </a>
                                <a href="customers.php" title="Add a new customer to the system" class="quick-action-btn bg-secondary hover:bg-secondary-dark text-white px-3 py-2 rounded-lg text-sm font-medium transition-all duration-200 flex items-center space-x-2">
                                    <i class="fas fa-user-plus text-xs"></i>
                                    <span>Add New Customer</span>
                                </a>
                            <?php elseif (basename($_SERVER['PHP_SELF']) === 'orders.php'): ?>
                                <a href="order_add.php" title="Create a new order for a customer" class="quick-action-btn bg-primary hover:bg-primary-dark text-white px-3 py-2 rounded-lg text-sm font-medium transition-all duration-200 flex items-center space-x-2">
                                    <i class="fas fa-plus text-xs"></i>
                                    <span>Add Order</span>
                                </a>
                            <?php elseif (basename($_SERVER['PHP_SELF']) === 'customers.php'): ?>
                                <a href="user_add.php" title="Register a new customer account" class="quick-action-btn bg-primary hover:bg-primary-dark text-white px-3 py-2 rounded-lg text-sm font-medium transition-all duration-200 flex items-center space-x-2">
                                    <i class="fas fa-plus text-xs"></i>
                                    <span>Add New Customer</span>
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Bottom Row: Search, Status, Notifications, Profile -->
                    <div class="flex items-center justify-between lg:justify-end space-x-3">
                        <!-- System Status Indicators -->
                        <div class="hidden lg:flex items-center space-x-3">
                            <!-- Database Status -->
                            <div class="flex items-center space-x-2 px-3 py-2 bg-gray-50 rounded-lg">
                                <div class="w-2 h-2 rounded-full <?php echo $systemStatus['database'] ? 'bg-green-400' : 'bg-red-400'; ?> status-indicator <?php echo $systemStatus['database'] ? 'success' : ''; ?>"></div>
                                <span class="text-xs font-medium text-gray-600">DB</span>
                            </div>

                            <!-- Orders Today -->
                            <div class="flex items-center space-x-2 px-3 py-2 bg-blue-50 rounded-lg">
                                <i class="fas fa-shopping-cart text-blue-500 text-sm"></i>
                                <span class="text-xs font-medium text-blue-700"><?php echo $systemStatus['orders_today']; ?></span>
                            </div>

                            <!-- Pending Orders -->
                            <div class="flex items-center space-x-2 px-3 py-2 bg-yellow-50 rounded-lg">
                                <i class="fas fa-clock text-yellow-500 text-sm"></i>
                                <span class="text-xs font-medium text-yellow-700"><?php echo $systemStatus['pending_orders']; ?></span>
                            </div>
                        </div>

                        <!-- Theme Toggle -->
                        <div class="relative" x-data="{ themeOpen: false, currentTheme: 'light' }" x-init="currentTheme = $store.theme || 'light'; themeOpen = false">
                            <button @click="themeOpen = !themeOpen; toggleTheme()"
                                    title="Switch between light and dark theme"
                                    aria-label="Toggle between light and dark theme modes"
                                    :class="currentTheme === 'dark' ? 'text-yellow-400 hover:text-yellow-300 bg-gray-100' : 'text-gray-600 hover:text-gray-900 hover:bg-gray-100'"
                                    class="theme-toggle header-button focus:outline-none focus:ring-2 focus:ring-primary focus:ring-offset-2 rounded-lg p-2 transition-all duration-300 relative">
                                <div class="relative">
                                    <div class="h-10 w-10 rounded-full bg-gradient-to-br from-primary to-secondary flex items-center justify-center text-white font-semibold shadow-lg ring-2 ring-white">
                                        <i class="fas fa-moon text-lg transition-all duration-300" aria-hidden="true"></i>
                                    </div>
                                    <div :class="currentTheme === 'dark' ? 'bg-yellow-400' : 'bg-gray-300'" class="absolute -inset-1 rounded-full opacity-20 transition-all duration-300"></div>
                                </div>
                            </button>

                            <!-- Theme Selection Dropdown -->
                            <div x-show="themeOpen"
                                 @click.away="themeOpen = false"
                                 x-transition:enter="transition ease-out duration-100"
                                 x-transition:enter-start="transform opacity-0 scale-95"
                                 x-transition:enter-end="transform opacity-100 scale-100"
                                 x-transition:leave="transition ease-in duration-75"
                                 x-transition:leave-start="transform opacity-100 scale-100"
                                 x-transition:leave-end="transform opacity-0 scale-95"
                                 class="absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-lg z-50 border border-gray-200 p-3 theme-dropdown">

                                <div class="mb-3">
                                    <h4 class="text-sm font-medium text-gray-900 mb-2">Theme Settings</h4>
                                    <p class="text-xs text-gray-600">Choose your preferred display mode</p>
                                </div>

                                <div class="space-y-2">
                                    <!-- Light Theme Option -->
                                    <button @click="setTheme('light')"
                                            :class="currentTheme === 'light' ? 'bg-primary text-white' : 'bg-gray-50 text-gray-700 hover:bg-gray-100'"
                                            class="w-full flex items-center space-x-3 p-3 rounded-lg transition-all duration-200">
                                        <div class="w-8 h-8 bg-gray-100 rounded-full flex items-center justify-center">
                                            <i class="fas fa-sun text-gray-600 text-sm"></i>
                                        </div>
                                        <div class="flex-1 text-left">
                                            <div class="text-sm font-medium">Light Mode</div>
                                            <div class="text-xs opacity-75">Bright and clear interface</div>
                                        </div>
                                        <div x-show="currentTheme === 'light'" class="w-4 h-4 bg-white rounded-full flex items-center justify-center">
                                            <i class="fas fa-check text-xs text-primary"></i>
                                        </div>
                                    </button>

                                    <!-- Dark Theme Option -->
                                    <button @click="setTheme('dark')"
                                            :class="currentTheme === 'dark' ? 'bg-primary text-white' : 'bg-gray-50 text-gray-700 hover:bg-gray-100'"
                                            class="w-full flex items-center space-x-3 p-3 rounded-lg transition-all duration-200">
                                        <div class="w-8 h-8 bg-gray-700 rounded-full flex items-center justify-center">
                                            <i class="fas fa-moon text-gray-200 text-sm"></i>
                                        </div>
                                        <div class="flex-1 text-left">
                                            <div class="text-sm font-medium">Dark Mode</div>
                                            <div class="text-xs opacity-75">Easy on the eyes</div>
                                        </div>
                                        <div x-show="currentTheme === 'dark'" class="w-4 h-4 bg-white rounded-full flex items-center justify-center">
                                            <i class="fas fa-check text-xs text-primary"></i>
                                        </div>
                                    </button>
                                </div>

                                <div class="mt-4 pt-3 border-t border-gray-200">
                                    <div class="flex items-center justify-between text-xs text-gray-600">
                                        <span>Auto-detect system theme</span>
                                        <button class="w-8 h-8 rounded-full bg-gray-100 hover:bg-gray-200 flex items-center justify-center transition-colors">
                                            <i class="fas fa-magic text-xs text-gray-600"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Customer Support -->
                        <div class="relative" x-data="{ chatOpen: false }" x-init="chatOpen = false">
                            <a href="../chat.php"
                               @click="chatOpen = true"
                               title="Access customer support chat"
                               aria-label="Open customer support chat"
                               class="header-button text-gray-600 hover:text-primary hover:bg-primary/10 focus:outline-none focus:ring-2 focus:ring-primary focus:ring-offset-2 rounded-lg p-2 transition-all duration-200 relative">
                                <div class="h-10 w-10 rounded-full bg-gradient-to-br from-green-500 to-green-600 flex items-center justify-center text-white font-semibold shadow-lg ring-2 ring-white">
                                    <i class="fas fa-headset text-lg" aria-hidden="true"></i>
                                </div>
                                <span class="absolute -top-1 -right-1 w-3 h-3 bg-green-400 border-2 border-white rounded-full animate-pulse" title="Chat support available"></span>
                            </a>

                            <!-- Chat Status Tooltip -->
                            <div x-show="chatOpen"
                                 @click.away="chatOpen = false"
                                 x-transition:enter="transition ease-out duration-100"
                                 x-transition:enter-start="transform opacity-0 scale-95"
                                 x-transition:enter-end="transform opacity-100 scale-100"
                                 x-transition:leave="transition ease-in duration-75"
                                 x-transition:leave-start="transform opacity-100 scale-100"
                                 x-transition:leave-end="transform opacity-0 scale-95"
                                 class="absolute right-0 mt-2 w-64 bg-white rounded-lg shadow-lg z-50 border border-gray-200 p-3 chat-dropdown">
                                <div class="flex items-center space-x-2 mb-2">
                                    <div class="w-8 h-8 bg-green-100 rounded-full flex items-center justify-center">
                                        <i class="fas fa-headset text-green-600 text-sm"></i>
                                    </div>
                                    <div>
                                        <h4 class="text-sm font-medium text-gray-900">Customer Support</h4>
                                        <p class="text-xs text-green-600">● Online</p>
                                    </div>
                                </div>
                                <p class="text-xs text-gray-600 mb-3">Get instant help from our support team</p>
                                <a href="../chat.php" class="w-full bg-primary text-white text-sm font-medium py-2 px-3 rounded-lg hover:bg-primary-dark transition-colors text-center block">
                                    Start Chat
                                </a>
                            </div>
                        </div>

                        <!-- Notifications -->
                        <div class="relative" x-data="{ notificationOpen: false }" x-init="notificationOpen = false">
                            <button @click="notificationOpen = !notificationOpen"
                                    title="View notifications and activity logs"
                                    aria-label="View notifications and activity logs"
                                    class="header-button text-gray-600 hover:text-gray-900 hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-primary focus:ring-offset-2 rounded-lg p-2 relative transition-all duration-200">
                                <div class="h-10 w-10 rounded-full bg-gradient-to-br from-blue-500 to-blue-600 flex items-center justify-center text-white font-semibold shadow-lg ring-2 ring-white">
                                    <i class="far fa-bell text-lg" aria-hidden="true"></i>
                                </div>
                                <?php if ($notificationCount > 0): ?>
                                    <span title="<?php echo $notificationCount; ?> new notifications" class="notification-badge absolute -top-1 -right-1 bg-red-500 text-white text-xs rounded-full h-5 w-5 flex items-center justify-center font-medium animate-pulse">
                                        <?php echo min($notificationCount, 99); ?>
                                    </span>
                                <?php endif; ?>
                            </button>

                            <!-- Enhanced Notifications Dropdown -->
                            <div x-show="notificationOpen"
                                 @click.away="notificationOpen = false"
                                 x-transition:enter="transition ease-out duration-100"
                                 x-transition:enter-start="transform opacity-0 scale-95"
                                 x-transition:enter-end="transform opacity-100 scale-100"
                                 x-transition:leave="transition ease-in duration-75"
                                 x-transition:leave-start="transform opacity-100 scale-100"
                                 x-transition:leave-end="transform opacity-0 scale-95"
                                 class="absolute right-0 mt-2 w-96 bg-white rounded-lg shadow-xl z-50 border border-gray-200 advanced-dropdown notification-dropdown">

                                <!-- Header with Summary -->
                                <div class="p-4 border-b border-gray-200 bg-gradient-to-r from-primary/5 to-secondary/5">
                                    <div class="flex items-center justify-between mb-2">
                                        <h3 class="text-lg font-semibold text-gray-900 flex items-center">
                                            <i class="far fa-bell mr-2 text-primary"></i>
                                            Notifications
                                        </h3>
                                        <div class="flex items-center space-x-2">
                                            <span title="New notifications in the last 24 hours" class="px-3 py-1 text-xs font-medium rounded-full bg-primary text-white">
                                                <?php echo $notificationCount; ?> new
                                            </span>
                                            <button title="Mark all as read" class="text-gray-400 hover:text-primary transition-colors">
                                                <i class="fas fa-check-double text-sm"></i>
                                            </button>
                                        </div>
                                    </div>
                                    <div class="flex items-center justify-between text-sm text-gray-600">
                                        <span>Activity summary for today</span>
                                        <span class="font-medium"><?php echo date('M j, Y'); ?></span>
                                    </div>
                                </div>

                                <?php if (!empty($recentNotifications)): ?>
                                    <div class="max-h-80 overflow-y-auto">
                                        <?php
                                        $groupedNotifications = [];
                                        foreach ($recentNotifications as $notification) {
                                            $type = $notification['activity_type'] ?? 'general';
                                            if (!isset($groupedNotifications[$type])) {
                                                $groupedNotifications[$type] = [];
                                            }
                                            $groupedNotifications[$type][] = $notification;
                                        }
                                        ?>

                                        <?php foreach ($groupedNotifications as $type => $notifications): ?>
                                            <?php
                                            $typeIcon = $activityLogger->getActivityIcon($type, 'general');
                                            $typeColor = $activityLogger->getActivityColor($type);
                                            $typeLabel = ucfirst(str_replace('_', ' ', $type));
                                            ?>
                                            <!-- Notification Type Section -->
                                            <div class="p-3 bg-gray-50 border-b border-gray-100">
                                                <div class="flex items-center space-x-2 mb-2">
                                                    <div class="w-6 h-6 bg-<?php echo $typeColor; ?>-100 rounded-full flex items-center justify-center">
                                                        <i class="<?php echo $typeIcon; ?> text-<?php echo $typeColor; ?>-600 text-xs"></i>
                                                    </div>
                                                    <h4 class="text-sm font-medium text-gray-900"><?php echo $typeLabel; ?></h4>
                                                    <span class="px-2 py-0.5 text-xs font-medium rounded-full bg-<?php echo $typeColor; ?>-100 text-<?php echo $typeColor; ?>-700">
                                                        <?php echo count($notifications); ?>
                                                    </span>
                                                </div>

                                                <?php foreach ($notifications as $notification): ?>
                                                    <div class="ml-8 mb-2 p-3 bg-white rounded-lg border border-gray-100 hover:shadow-sm transition-all duration-200">
                                                        <div class="flex items-start justify-between">
                                                            <div class="flex-1">
                                                                <p class="text-sm font-medium text-gray-900 leading-relaxed">
                                                                    <?php echo htmlspecialchars($notification['description']); ?>
                                                                </p>
                                                                <div class="flex items-center mt-2 space-x-3 text-xs text-gray-500">
                                                                    <span class="flex items-center">
                                                                        <i class="fas fa-clock mr-1"></i>
                                                                        <?php echo date('M j, g:i A', strtotime($notification['created_at'])); ?>
                                                                    </span>
                                                                    <?php if (isset($notification['user_name'])): ?>
                                                                        <span class="flex items-center">
                                                                            <i class="fas fa-user mr-1"></i>
                                                                            <?php echo htmlspecialchars($notification['user_name']); ?>
                                                                        </span>
                                                                    <?php endif; ?>
                                                                </div>
                                                            </div>
                                                            <div class="ml-2">
                                                                <button title="Mark as read" class="w-6 h-6 rounded-full bg-gray-100 hover:bg-primary hover:text-white flex items-center justify-center transition-colors">
                                                                    <i class="fas fa-check text-xs"></i>
                                                                </button>
                                                            </div>
                                                        </div>
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>

                                    <!-- Footer Actions -->
                                    <div class="p-4 border-t border-gray-200 bg-gray-50">
                                        <div class="flex items-center justify-between">
                                            <button class="text-sm text-gray-600 hover:text-primary transition-colors flex items-center">
                                                <i class="fas fa-sliders-h mr-2"></i>
                                                Notification Settings
                                            </button>
                                            <a href="activity_logs.php"
                                               title="View complete activity history and logs"
                                               class="text-sm text-primary hover:text-primary-dark font-medium flex items-center transition-colors">
                                                View All Activities
                                                <i class="fas fa-arrow-right ml-2" aria-hidden="true"></i>
                                            </a>
                                        </div>
                                    </div>
                                <?php else: ?>
                                    <!-- Empty State -->
                                    <div class="p-8 text-center">
                                        <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                                            <i class="fas fa-bell-slash text-2xl text-gray-400"></i>
                                        </div>
                                        <h4 class="text-lg font-medium text-gray-900 mb-2">No new notifications</h4>
                                        <p class="text-sm text-gray-500 mb-4">You're all caught up! Check back later for updates.</p>
                                        <a href="activity_logs.php" class="text-sm text-primary hover:text-primary-dark font-medium">
                                            View Activity History
                                        </a>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Enhanced User Profile -->
                        <div class="relative" x-data="{ profileOpen: false }" x-init="profileOpen = false">
                            <button @click="profileOpen = !profileOpen"
                                    title="User menu and account options"
                                    aria-label="Open user menu with profile, settings, and logout options"
                                    class="header-button flex items-center space-x-3 focus:outline-none focus:ring-2 focus:ring-primary focus:ring-offset-2 rounded-lg p-2 hover:bg-gray-100 transition-all duration-200">
                                <div class="h-10 w-10 rounded-full bg-gradient-to-br from-primary to-secondary flex items-center justify-center text-white font-semibold shadow-lg ring-2 ring-white" role="img" aria-label="User avatar">
                                    <?php echo strtoupper(substr($_SESSION['username'] ?? $_SESSION['user_name'] ?? 'A', 0, 1)); ?>
                                </div>
                                <div class="hidden md:block text-left">
                                    <div class="text-sm font-medium text-gray-900"><?php echo $_SESSION['username'] ?? $_SESSION['user_name'] ?? 'Admin'; ?></div>
                                    <div class="text-xs text-gray-500"><?php echo ucfirst($_SESSION['user_role'] ?? 'admin'); ?> Account</div>
                                </div>
                                <i class="fas fa-chevron-down text-xs text-gray-500 hidden md:inline transition-transform duration-200" :class="{ 'rotate-180': profileOpen }" aria-hidden="true"></i>
                            </button>

                            <!-- Enhanced Profile Dropdown -->
                            <div x-show="profileOpen"
                                 @click.away="profileOpen = false"
                                 x-transition:enter="transition ease-out duration-100"
                                 x-transition:enter-start="transform opacity-0 scale-95"
                                 x-transition:enter-end="transform opacity-100 scale-100"
                                 x-transition:leave="transition ease-in duration-75"
                                 x-transition:leave-start="transform opacity-100 scale-100"
                                 x-transition:leave-end="transform opacity-0 scale-95"
                                 class="absolute right-0 mt-2 w-64 bg-white rounded-lg shadow-lg z-50 border border-gray-200 advanced-dropdown profile-dropdown">

                                <!-- User Info Header -->
                                <div class="p-4 border-b border-gray-200">
                                    <div class="flex items-center space-x-3">
                                        <div class="h-12 w-12 rounded-full bg-gradient-to-br from-primary to-secondary flex items-center justify-center text-white font-semibold text-lg shadow-lg">
                                            <?php echo strtoupper(substr($_SESSION['username'] ?? $_SESSION['user_name'] ?? 'A', 0, 1)); ?>
                                        </div>
                                        <div>
                                            <div class="text-sm font-medium text-gray-900"><?php echo $_SESSION['username'] ?? $_SESSION['user_name'] ?? 'Admin'; ?></div>
                                            <div class="text-xs text-gray-500"><?php echo $_SESSION['user_email'] ?? ''; ?></div>
                                            <div class="text-xs text-primary font-medium mt-1"><?php echo ucfirst($_SESSION['user_role'] ?? 'admin'); ?></div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Quick Stats -->
                                <div class="p-4 bg-gray-50">
                                    <div class="grid grid-cols-2 gap-4 text-center">
                                        <div>
                                            <div class="text-lg font-bold text-primary"><?php echo $systemStatus['orders_today']; ?></div>
                                            <div class="text-xs text-gray-600">Orders Today</div>
                                        </div>
                                        <div>
                                            <div class="text-lg font-bold text-secondary"><?php echo $systemStatus['pending_orders']; ?></div>
                                            <div class="text-xs text-gray-600">Pending</div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Menu Items -->
                                <div class="py-2">
                                    <a href="profile.php" title="View and edit your profile information" class="flex items-center px-4 py-3 text-sm text-gray-700 hover:bg-gray-100 transition-colors duration-200">
                                        <i class="fas fa-user-circle mr-3 text-gray-400" aria-hidden="true"></i>
                                        View Profile
                                    </a>
                                    <a href="settings.php" title="Configure your account settings and preferences" class="flex items-center px-4 py-3 text-sm text-gray-700 hover:bg-gray-100 transition-colors duration-200">
                                        <i class="fas fa-cog mr-3 text-gray-400" aria-hidden="true"></i>
                                        Account Settings
                                    </a>
                                    <a href="activity_logs.php" title="Review your recent account activity and system logs" class="flex items-center px-4 py-3 text-sm text-gray-700 hover:bg-gray-100 transition-colors duration-200">
                                        <i class="fas fa-history mr-3 text-gray-400" aria-hidden="true"></i>
                                        Activity History
                                    </a>
                                </div>

                                <!-- Divider -->
                                <div class="border-t border-gray-200"></div>

                                <!-- Logout -->
                                <div class="py-2">
                                    <a href="../auth/logout.php"
                                       title="Sign out of your account and end session"
                                       class="flex items-center px-4 py-3 text-sm text-red-600 hover:bg-red-50 transition-colors duration-200">
                                        <i class="fas fa-sign-out-alt mr-3" aria-hidden="true"></i>
                                        Sign Out
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </header>

        <!-- Page Content -->
        <div class="content-container">
