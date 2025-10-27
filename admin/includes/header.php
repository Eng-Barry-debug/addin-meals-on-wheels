<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Set default timezone to East Africa Time (Nairobi)
date_default_timezone_set('Africa/Nairobi');

/*
 * IMPORTANT: config.php MUST be included BEFORE header.php.
 * It's usually included by the main script (e.g., dashboard.php, ambassador.php)
 *
 * If you encounter errors like "Undefined variable $pdo", it means the main script
 * did not include config.php or included it AFTER header.php.
 *
 * The path below assumes config.php is two directories up:
 * e.g., 'admin/includes/header.php' -> '../../includes/config.php'
 */
if (!isset($pdo) || !$pdo instanceof PDO) { // Only include if $pdo is not already a valid PDO object
    require_once dirname(__DIR__, 2) . '/includes/config.php';
}

// Include activity read manager
require_once '../includes/activity_read_manager.php';

// Ensure $pdo is global within this scope so functions like getCount can access it
global $pdo;

// Default page title in case it's not set by the calling script
$page_title = $page_title ?? 'Dashboard';

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
                header('Location: ../dashboards/delivery/index.php');
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

/*
 * Include common functions.
 * Adjust path based on your 'admin' directory structure.
 * Assuming admin functions might be in 'admin/functions.php' or 'includes/functions.php'
 */
if (file_exists(dirname(__DIR__) . '/functions.php')) { // Check 'admin/functions.php'
    require_once dirname(__DIR__) . '/functions.php';
} elseif (file_exists(dirname(__DIR__, 2) . '/includes/functions.php')) { // Check project_root/includes/functions.php
      require_once dirname(__DIR__, 2) . '/includes/functions.php';
} else {
    // Fallback if 'getCount' is critical and functions.php is not found
    if (!function_exists('getCount')) {
        function getCount($pdo, $table, $where_clause = '') {
            // Ensure $pdo is accessible within this function if it's a fallback
            global $pdo; // Make sure $pdo is available here
            $sql = "SELECT COUNT(*) FROM $table";
            if (!empty($where_clause)) {
                $sql .= " WHERE " . $where_clause;
            }
            // Use prepared statement for safer query execution
            $stmt = $pdo->prepare($sql);
            $stmt->execute();
            return $stmt->fetchColumn();
        }
    }

    // Fallback for time_elapsed_string if functions.php was not included
    if (!function_exists('time_elapsed_string')) {
        function time_elapsed_string($datetime, $full = false) {
            $now = new DateTime;
            $ago = new DateTime($datetime);
            $diff = $now->diff($ago);

            $diff->w = floor($diff->d / 7);
            $diff->d -= $diff->w * 7;

            $string = array(
                'y' => 'year',
                'm' => 'month',
                'w' => 'week',
                'd' => 'day',
                'h' => 'hour',
                'i' => 'minute',
                's' => 'second',
            );
            foreach ($string as $k => &$v) {
                if ($diff->$k) {
                    $v = $diff->$k . ' ' . $v . ($diff->$k > 1 ? 's' : '');
                } else {
                    unset($string[$k]);
                }
            }

            if (!$full) $string = array_slice($string, 0, 1);
            return $string ? implode(', ', $string) . ' ago' : 'just now';
        }
    }
}

// Include ActivityLogger for activity tracking
require_once '../includes/ActivityLogger.php';

$activityLogger = new ActivityLogger($pdo);

// Get recent activities for notifications (last 24 hours)
try {
    // Get all recent activities from the last 24 hours
    $allRecentActivities = $activityLogger->getRecentActivities(50);

    // If no activities exist, create some sample ones
    if (empty($allRecentActivities)) {
        // Log some sample activities for demonstration
        $activityLogger->logActivity('Admin user logged in successfully', $_SESSION['user_id'], 'system');
        $activityLogger->logActivity('Dashboard accessed', $_SESSION['user_id'], 'system');
        $activityLogger->logActivity('System initialized', null, 'system');

        // Get the activities we just created
        $allRecentActivities = $activityLogger->getRecentActivities(50);
    }

    // Filter for last 24 hours and exclude current user (except for system activities)
    $recentNotifications = [];
    $currentUserId = $_SESSION['user_id'] ?? 0;
    $twentyFourHoursAgo = date('Y-m-d H:i:s', strtotime('-24 hours'));

    foreach ($allRecentActivities as $activity) {
        $activityTime = $activity['created_at'];

        // Show if it's within 24 hours and (system activity OR not by current user)
        if ($activityTime >= $twentyFourHoursAgo &&
            ($activity['user_id'] !== $currentUserId || $activity['activity_type'] === 'system')) {
            $recentNotifications[] = $activity;
        }

        // Limit to 10 notifications
        if (count($recentNotifications) >= 10) {
            break;
        }
    }

    $notificationCount = count($recentNotifications);
} catch (Exception $e) {
    error_log("Recent notifications error: " . $e->getMessage());
    $recentNotifications = [];
    $notificationCount = 0;
}

// Get system status for header indicators
$systemStatus = [
    'database' => true, // Assuming PDO connection indicates database is up
    'orders_today' => 0,
    'pending_orders' => 0,
    'server_load' => 'normal' // Static for now, requires system command for real value
];

try {
    global $pdo; // Ensure $pdo is available here
    // Ensure getCount function is available before calling it
    if (function_exists('getCount')) {
        $systemStatus['orders_today'] = getCount($pdo, 'orders', "status = 'completed' AND DATE(created_at) = CURDATE()");
        $systemStatus['pending_orders'] = getCount($pdo, 'orders', 'status = "pending"');
    } else {
        error_log("Warning: getCount function not found or defined in header.php context.");
    }
} catch (PDOException $e) {
    error_log("System status info error: " . $e->getMessage());
    // Silent fail for status indicators if DB is unreachable or query errors
    $systemStatus['database'] = false; // Mark database as down
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
    case 'users': // Grouped for the same breadcrumbs
        $breadcrumbs = [
            ['name' => 'Dashboard', 'url' => 'dashboard.php', 'current' => false],
            ['name' => 'Customers', 'url' => 'customers.php', 'current' => true] // Assuming both lead to a customer list
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
    case 'ambassador': // Specifically for admin/ambassador.php
        $breadcrumbs = [
            ['name' => 'Dashboard', 'url' => 'dashboard.php', 'current' => false],
            ['name' => 'Ambassador Management', 'url' => 'ambassador.php', 'current' => true]
        ];
        break;
      // Add cases for other admin pages as needed
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
    <title><?php echo htmlspecialchars($page_title); ?> - Addins Meals on Wheels</title>
    <!-- Tailwind CSS CDN - Replace with build process for production -->
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" xintegrity="sha512-iecdLmaskl7CVkqkXNQ/ZH/XLlvWZOJyj7Yy7tcenmpD1ypASozpmT/E0iPtmFIB46ZmdtAc9eNBvH0H/ZpiBw==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
    <style>
        [x-cloak] { display: none !important; }
        body {
            margin: 0;
            padding: 0;
        }

        /* Prevent scrolling when sidebar is open on mobile */
        body.noscroll {
            overflow: hidden;
        }

        /* Sidebar styling */
        .sidebar {
            width: 16rem; /* Tailwind's w-64 */
            height: 100vh;
            position: fixed;
            left: 0;
            top: 0;
            z-index: 20; /* Ensure it's above content */
            transition: transform 0.3s ease-in-out;
            /* Default for desktop */
            transform: translateX(0);
        }

        /* Sidebar specific for mobile (less than lg breakpoint, 1024px) */
        @media (max-width: 1023px) {
            .sidebar {
                transform: translateX(-100%); /* Hidden by default on mobile */
            }
            .sidebar.open {
                transform: translateX(0); /* Visible when toggled open */
            }
        }

        /* Main content area */
       .main-content {
            margin-left: 16rem; /* Default for desktop (after sidebar) */
            min-height: 100vh;
            background-color: #f3f4f6;
            display: flex;
            flex-direction: column;
            transition: margin-left 0.3s ease-in-out;
        }

        /* Main content for mobile */
        @media (max-width: 1023px) {
            .main-content {
                margin-left: 0; /* Full width on mobile */
            }
        }


        .content-wrapper {
            flex: 1 0 auto;
        }
        /* Header positioning */
        .header {
            position: fixed;
            top: 0;
            right: 0;
            z-index: 50; /* Above sidebar */
            background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);
            backdrop-filter: blur(10px);
            border-bottom: 1px solid rgba(0, 0, 0, 0.1);
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06);
            height: 4rem;
        }

        /* Header for desktop */
        @media (min-width: 1024px) {
            .header {
                left: 16rem; /* Starts after the sidebar */
            }
        }

        /* Header for mobile */
        @media (max-width: 1023px) {
            .header {
                left: 0; /* Full width on mobile */
            }
        }

        .content-container {
            padding-top: 4rem;
            flex-grow: 1;
        }

        /* Sidebar backdrop for mobile */
        .sidebar-backdrop {
            position: fixed;
            inset: 0;
            background-color: rgba(0, 0, 0, 0.5);
            z-index: 15; /* Between main content and sidebar */
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
            color: #fc7703; /* Use primary color */
        }

        /* Search suggestions dropdown */
        .search-suggestions {
            animation: dropdownFade 0.2s ease-out; /* Reusing dropdownFade for search */
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

        /* Advanced dropdown animations */
        .advanced-dropdown {
            animation: dropdownFade 0.15s ease-out;
            transform-origin: top right;
            z-index: 60;
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

        /* ===== THEME SYSTEM (Adjusted to use CSS variables throughout) ===== */
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
            --primary-color: #fc7703;
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
            --primary-color: #ff9239; /* Slightly lighter primary for dark mode visibility */
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
        
        /* Direct use of primary color in Tailwind classes (requires JS config) or CSS */
        .text-primary {
            color: var(--primary-color) !important;
        }
        
        .bg-primary {
            background-color: var(--primary-color) !important;
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

        /* Hamburger Icon Animation */
        .hamburger-icon {
            display: flex;
            flex-direction: column;
            justify-content: space-around;
            width: 24px;
            height: 24px;
            cursor: pointer;
            z-index: 60;
        }

        .hamburger-icon span {
            display: block;
            width: 100%;
            height: 2px;
            background: currentColor;
            border-radius: 9999px;
            transition: all 0.3s ease-in-out;
        }

        .hamburger-icon.open span:nth-child(1) {
            transform: rotate(45deg) translate(5px, 5px);
        }

        .hamburger-icon.open span:nth-child(2) {
            opacity: 0;
        }

        .hamburger-icon.open span:nth-child(3) {
            transform: rotate(-45deg) translate(5px, -5px);
        }

    </style>
    <script>
        // Moved confirmDelete function from <style> to <script>
        function confirmDelete(event, itemName = 'this item') {
            event.preventDefault();

            Swal.fire({
                title: 'Are you sure?',
                text: `You are about to delete ${itemName}. This action cannot be undone!`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#fc7703', // This corresponds to 'primary' in tailwind.config
                cancelButtonColor: '#6B7280',
                confirmButtonText: 'Yes, delete it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    event.target.closest('form').submit();
                }
            });
        }

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
            // Get current theme from Alpine store, fallback to 'light'
            const currentTheme = Alpine.store('theme') || 'light';
            const newTheme = currentTheme === 'light' ? 'dark' : 'light';
            setTheme(newTheme, true); // Pass true to show notification for manual toggle
        }

        function setTheme(theme, showNotification = false) {
            // Store theme preference in Alpine store and localStorage
            if (typeof Alpine !== 'undefined' && Alpine.store('theme')) {
                Alpine.store('theme', theme);
            }
            localStorage.setItem('admin_theme', theme);

            // Update data-theme attribute on <html> element
            document.documentElement.setAttribute('data-theme', theme);

            // Show feedback only if this is an actual theme change triggered by user
            if (showNotification) {
                showThemeNotification(theme);
            }
        }

        function showThemeNotification(theme) {
            const themeName = theme === 'dark' ? 'Dark Mode' : 'Light Mode';

            Swal.fire({
                toast: true,
                position: 'top-end',
                icon: 'success',
                title: `Switched to ${themeName}`,
                showConfirmButton: false,
                timer: 2000,
                timerProgressBar: true,
                background: theme === 'dark' ? 'var(--card-bg)' : 'var(--card-bg)', // Use CSS variable
                color: theme === 'dark' ? 'var(--text-primary)' : 'var(--text-primary)' // Use CSS variable
            });
        }

        // Notification management functions
        function markActivityAsRead(activityId) {
            // This function is no longer needed since we show recent activities, not unread ones
            console.log('Mark as read function called but not implemented for recent activities');
        }

        // Mark all activities as read function
        function markAllActivitiesAsRead() {
            // Disable the button and show loading state
            const button = event.target.closest('button');
            const originalText = button.textContent;
            button.disabled = true;
            button.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i>Clearing...';

            // Small delay to show loading state
            setTimeout(() => {
                // Remove all notifications from the dropdown
                const notificationBlocks = document.querySelectorAll('.advanced-dropdown .block');
                notificationBlocks.forEach(block => block.remove());

                // Update notification count in UI
                const badge = document.querySelector('.notification-badge');
                if (badge) {
                    badge.remove();
                }

                // Update the header count text
                const countElement = document.querySelector('.text-lg.font-semibold');
                if (countElement) {
                    countElement.textContent = 'Notifications (0 Recent)';
                }

                // Hide the "Clear All" button
                button.style.display = 'none';

                // Replace notification content with empty state
                const notificationsContainer = document.querySelector('.advanced-dropdown .max-h-80');
                if (notificationsContainer) {
                    notificationsContainer.innerHTML = `
                        <div class="text-center p-6">
                            <i class="fas fa-bell-slash text-3xl text-gray-300 dark:text-gray-600 mb-2"></i>
                            <p class="text-gray-500 dark:text-gray-400 text-sm">No recent notifications</p>
                            <p class="text-gray-400 dark:text-gray-500 text-xs mt-1">Activities will appear here as you use the system!</p>
                        </div>
                    `;
                }

                // Show success message
                Swal.fire({
                    toast: true,
                    position: 'top-end',
                    icon: 'success',
                    title: 'All notifications cleared',
                    showConfirmButton: false,
                    timer: 2000,
                    timerProgressBar: true
                });

                // Close the notification dropdown
                const notificationDropdown = document.querySelector('[x-data="{ notificationOpen: false }"]');
                if (notificationDropdown && notificationDropdown.__x) {
                    notificationDropdown.__x.$data.notificationOpen = false;
                }
            }, 500);
        }
        document.addEventListener('DOMContentLoaded', function() {
            // Ensure Alpine.store 'theme' is defined before trying to access it
            if (typeof Alpine !== 'undefined') {
                if (!Alpine.store('theme')) {
                    Alpine.store('theme', localStorage.getItem('admin_theme') || 'light');
                }
            }


            const savedTheme = localStorage.getItem('admin_theme') || 'light';
            setTheme(savedTheme); // Initialize theme without showing notification
        });
    </script>
</head>
<body class="bg-gray-100" x-data="{ sidebarOpen: false }" :class="sidebarOpen ? 'noscroll' : ''">
    <!-- Sidebar component -->
    <aside class="sidebar bg-gray-800 text-white shadow-lg lg:z-10"
           :class="{ 'open': sidebarOpen }"
           x-bind:aria-expanded="sidebarOpen ? 'true' : 'false'">
        <?php include 'sidebar.php'; // Include your actual sidebar content here ?>
    </aside>

    <!-- Sidebar Backdrop for mobile -->
    <div x-show="sidebarOpen"
         x-transition:enter="transition-opacity ease-linear duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition-opacity ease-linear duration-300"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="sidebar-backdrop lg:hidden"
         @click="sidebarOpen = false"
         x-cloak>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Enhanced Modern Header -->
        <header class="bg-dark shadow-sm header border-b border-gray-200 header-container">
            <div class="max-w-full px-4 py-3 sm:px-6 lg:px-8">
                <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between space-y-4 lg:space-y-0">
                    <!-- Top Row: Mobile Hamburger, Breadcrumbs and Page Title -->
                    <div class="flex items-center justify-between w-full lg:w-auto">
                        <!-- Mobile Hamburger Button -->
                        <button type="button" class="lg:hidden p-2 rounded-md text-gray-700 hover:text-primary hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-primary focus:ring-offset-2"
                                @click="sidebarOpen = !sidebarOpen"
                                aria-controls="main-sidebar"
                                :aria-expanded="sidebarOpen ? 'true' : 'false'">
                            <span class="sr-only">Open main menu</span>
                            <div class="hamburger-icon" :class="{ 'open': sidebarOpen }">
                                <span></span>
                                <span></span>
                                <span></span>
                            </div>
                        </button>
                        
                        <!-- Left side - Breadcrumbs / Mobile Page Title -->
                        <div class="flex items-center space-x-4">
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

                            <!-- Mobile page title (aligns right when hamburger present) -->
                            <div class="sm:hidden" :class="sidebarOpen ? 'hidden' : 'block'">
                                <h1 class="text-lg font-semibold text-gray-900 dark:text-white"><?php echo htmlspecialchars($page_title); ?></h1>
                            </div>
                        </div>

                        <!-- Quick Actions Bar (hidden on small screens, shown for larger) -->
                        <div class="hidden lg:flex items-center space-x-2">
                            <?php if ($currentPage === 'dashboard'): // Show specific actions on dashboard ?>
                                <a href="orders.php" title="Create a new customer order" class="quick-action-btn bg-primary hover:bg-orange-600 text-white px-3 py-2 rounded-lg text-sm font-medium transition-all duration-200 flex items-center space-x-2">
                                    <i class="fas fa-plus text-xs"></i>
                                    <span>Create New Order</span>
                                </a>
                                <a href="customers.php" title="Add a new customer to the system" class="quick-action-btn bg-secondary hover:bg-yellow-700 text-white px-3 py-2 rounded-lg text-sm font-medium transition-all duration-200 flex items-center space-x-2">
                                    <i class="fas fa-user-plus text-xs"></i>
                                    <span>Add New Customer</span>
                                </a>
                            <?php elseif ($currentPage === 'orders'): ?>
                                <a href="order_add.php" title="Create a new order for a customer" class="quick-action-btn bg-primary hover:bg-orange-600 text-white px-3 py-2 rounded-lg text-sm font-medium transition-all duration-200 flex items-center space-x-2">
                                    <i class="fas fa-plus text-xs"></i>
                                    <span>Add Order</span>
                                </a>
                            <?php elseif ($currentPage === 'customers' || $currentPage === 'users'): ?>
                                <a href="user_add.php" title="Register a new customer account" class="quick-action-btn bg-primary hover:bg-orange-600 text-white px-3 py-2 rounded-lg text-sm font-medium transition-all duration-200 flex items-center space-x-2">
                                    <i class="fas fa-plus text-xs"></i>
                                    <span>Add New Customer</span>
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Bottom Row: Search, Status, Notifications, Profile -->
                    <div class="flex items-center justify-between lg:justify-end space-x-3 w-full lg:w-auto">
                        <!-- Search Bar -->
                        <div x-data="{ searchOpen: false, searchTerm: '' }" class="relative hidden sm:block">
                            <input type="text"
                                   x-model="searchTerm"
                                   @focus="searchOpen = true"
                                   @click.away="searchOpen = false"
                                   placeholder="Search customers, orders, menu..."
                                   class="w-full lg:w-64 px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-primary focus:border-primary transition duration-150 bg-gray-50 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                            >
                            <i class="fas fa-search absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-400"></i>

                            <!-- Search Suggestions Dropdown -->
                            <div x-show="searchOpen && searchTerm.length > 2"
                                 x-transition:enter="transition ease-out duration-100"
                                 x-transition:enter-start="opacity-0 scale-95"
                                 x-transition:enter-end="opacity-100 scale-100"
                                 x-transition:leave="transition ease-in duration-75"
                                 x-transition:leave-start="opacity-100 scale-100"
                                 x-transition:leave-end="opacity-0 scale-95"
                                 class="search-suggestions absolute mt-2 w-full lg:w-80 bg-white glass-effect rounded-lg shadow-xl border border-gray-200 dark:bg-gray-800 dark:border-gray-700"
                                 x-cloak
                            >
                                <div class="p-2 text-sm text-gray-700 dark:text-gray-200">
                                    <p class="text-xs text-gray-500 p-2 border-b border-gray-100 dark:border-gray-600">Quick Actions</p>
                                    <a href="order_add.php" @click="searchOpen = false" class="block p-2 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-md">
                                        <i class="fas fa-plus text-primary mr-2"></i> New Order
                                    </a>
                                    <a href="customers.php" @click="searchOpen = false" class="block p-2 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-md">
                                        <i class="fas fa-users text-accent mr-2"></i> All Customers
                                    </a>
                                    <!-- Add more search suggestions here -->
                                </div>
                            </div>
                        </div>

                        <!-- System Status Indicators (Visible on large screens) -->
                        <div class="hidden lg:flex items-center space-x-3">
                            <!-- Database Status -->
                            <div class="flex items-center space-x-2 px-3 py-2 bg-gray-50 dark:bg-gray-700 rounded-lg">
                                <div class="w-2 h-2 rounded-full <?php echo $systemStatus['database'] ? 'bg-green-400' : 'bg-red-400'; ?> status-indicator <?php echo $systemStatus['database'] ? 'success' : ''; ?>"></div>
                                <span class="text-xs font-medium text-gray-600 dark:text-gray-400">DB</span>
                            </div>

                            <!-- Orders Today -->
                            <div class="flex items-center space-x-2 px-3 py-2 bg-blue-50 dark:bg-blue-900 rounded-lg">
                                <i class="fas fa-check-circle text-blue-500 text-sm"></i>
                                <span class="text-xs font-medium text-blue-700 dark:text-blue-300" title="Orders Completed Today"><?php echo $systemStatus['orders_today']; ?></span>
                            </div>

                            <!-- Pending Orders -->
                            <div class="flex items-center space-x-2 px-3 py-2 bg-red-50 dark:bg-red-900 rounded-lg">
                                <i class="fas fa-exclamation-triangle text-red-500 text-sm"></i>
                                <span class="text-xs font-medium text-red-700 dark:text-red-300" title="Orders Pending"><?php echo $systemStatus['pending_orders']; ?></span>
                            </div>
                        </div>

                        <!-- Notifications Dropdown -->
                        <div x-data="{ notificationOpen: false }" class="relative">
                            <button @click="notificationOpen = !notificationOpen"
                                    title="View notifications"
                                    aria-label="View notifications"
                                    class="header-button relative text-gray-600 hover:text-primary hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-primary focus:ring-offset-2 rounded-lg p-2 transition-all duration-200">
                                <i class="fas fa-bell text-xl"></i>
                                <?php if ($notificationCount > 0): ?>
                                    <span class="notification-badge absolute top-0 right-0 inline-flex items-center justify-center px-2 py-1 text-xs font-bold leading-none text-white transform translate-x-1/2 -translate-y-1/2 bg-red-600 rounded-full animate-pulse">
                                        <?php echo $notificationCount > 9 ? '9+' : $notificationCount; ?>
                                    </span>
                                <?php endif; ?>
                            </button>

                            <!-- Notification Dropdown Menu -->
                            <div x-show="notificationOpen" @click.away="notificationOpen = false"
                                 x-transition:enter="transition ease-out duration-100"
                                 x-transition:enter-start="opacity-0 scale-95"
                                 x-transition:enter-end="opacity-100 scale-100"
                                 x-transition:leave="transition ease-in duration-75"
                                 x-transition:leave-start="opacity-100 scale-100"
                                 x-transition:leave-end="opacity-0 scale-95"
                                 class="advanced-dropdown absolute right-0 mt-2 w-80 bg-white glass-effect rounded-lg shadow-xl border border-gray-200 dark:bg-gray-800 dark:border-gray-700"
                                 x-cloak>
                                <div class="p-4 border-b border-gray-200 dark:border-gray-700">
                                    <div class="flex items-center justify-between mb-2">
                                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Notifications</h3>
                                        <?php if ($notificationCount > 0): ?>
                                            <button onclick="markAllActivitiesAsRead()"
                                                    class="text-xs text-primary hover:text-orange-600 transition-colors duration-150 font-medium">
                                                Clear All
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                    <p class="text-sm text-gray-600 dark:text-gray-400"><?php echo $notificationCount; ?> Recent</p>
                                </div>
                                <div class="max-h-80 overflow-y-auto">
                                    <?php if (!empty($recentNotifications)): ?>
                                        <?php foreach ($recentNotifications as $notification): ?>
                                            <div class="block p-3 hover:bg-gray-50 dark:hover:bg-gray-700 border-b border-gray-100 dark:border-gray-800 transition duration-150">
                                                <div class="flex items-start">
                                                    <i class="fas fa-circle text-xs mt-1 mr-3 text-primary"></i>
                                                    <div class="flex-1">
                                                        <p class="text-sm text-gray-900 dark:text-white">
                                                            <?php echo htmlspecialchars($notification['description']); ?>
                                                        </p>
                                                        <p class="text-xs text-gray-600 dark:text-gray-400 mt-0.5">
                                                            <?php echo htmlspecialchars($notification['user_name'] ?? 'System'); ?> • <?php echo ucfirst($notification['activity_type']); ?>
                                                        </p>
                                                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                                                            <?php
                                                            if (function_exists('time_elapsed_string')) {
                                                                echo time_elapsed_string($notification['created_at']);
                                                            } else {
                                                                echo date('M d, H:i', strtotime($notification['created_at']));
                                                            }
                                                            ?>
                                                        </p>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <div class="text-center p-6">
                                            <i class="fas fa-bell-slash text-3xl text-gray-300 dark:text-gray-600 mb-2"></i>
                                            <p class="text-gray-500 dark:text-gray-400 text-sm">No recent notifications</p>
                                            <p class="text-gray-400 dark:text-gray-500 text-xs mt-1">Activities will appear here as you use the system!</p>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div class="p-2 border-t border-gray-200 dark:border-gray-700">
                                    <a href="activity_logs.php" class="block w-full text-center text-primary text-sm font-medium py-1 hover:text-orange-600 transition duration-150">View All</a>
                                </div>
                            </div>
                        </div>

                        <!-- Theme Toggle Button -->
                        <button @click="toggleTheme()"
                                title="Toggle Dark/Light Mode"
                                aria-label="Toggle Dark/Light Mode"
                                class="header-button text-gray-600 hover:text-primary hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-primary focus:ring-offset-2 rounded-lg p-2 transition-all duration-200 theme-toggle">
                            <i class="fas" :class="$store.theme === 'dark' ? 'fa-sun' : 'fa-moon'" x-cloak></i>
                        </button>

                        <!-- Profile Dropdown -->
                        <div x-data="{ profileOpen: false }" class="relative ml-3">
                            <div>
                                <button type="button" @click="profileOpen = !profileOpen"
                                        class="max-w-xs bg-gray-800 rounded-full flex items-center text-sm focus:outline-none focus:ring-2 focus:ring-primary focus:ring-offset-2 lg:p-1 lg:hover:bg-gray-700 transition duration-150 dark:bg-gray-700 dark:hover:bg-gray-600"
                                        id="user-menu-button" aria-expanded="false" aria-haspopup="true">
                                    <span class="sr-only">Open user menu</span>
                                    <span class="h-8 w-8 rounded-full bg-primary flex items-center justify-center text-white font-semibold text-sm">
                                        <?php echo strtoupper(substr($_SESSION['name'] ?? 'A', 0, 1)); ?>
                                    </span>
                                </button>
                            </div>

                            <!-- Profile Dropdown Menu -->
                            <div x-show="profileOpen" @click.away="profileOpen = false"
                                 x-transition:enter="transition ease-out duration-100"
                                 x-transition:enter-start="opacity-0 scale-95"
                                 x-transition:enter-end="opacity-100 scale-100"
                                 x-transition:leave="transition ease-in duration-75"
                                 x-transition:leave-start="opacity-100 scale-100"
                                 x-transition:leave-end="opacity-0 scale-95"
                                 class="dropdown-menu absolute right-0 mt-2 w-48 bg-white glass-effect rounded-lg shadow-xl border border-gray-200 dark:bg-gray-800 dark:border-gray-700"
                                 x-cloak>
                                <div class="px-4 py-3 border-b border-gray-100 dark:border-gray-700">
                                    <p class="text-sm font-medium text-gray-900 dark:text-white"><?php echo htmlspecialchars($_SESSION['name'] ?? 'Admin User'); ?></p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400 truncate mt-0.5"><?php echo htmlspecialchars($_SESSION['email'] ?? 'admin@example.com'); ?></p>
                                </div>
                                <div class="py-1">
                                    <a href="../account/profile.php" class="block px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 transition duration-150">
                                        <i class="fas fa-user-circle w-5 mr-2"></i> Your Profile
                                    </a>
                                    <a href="settings.php" class="block px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 transition duration-150">
                                        <i class="fas fa-cog w-5 mr-2"></i> Settings
                                    </a>
                                    <a href="../logout.php" class="block px-4 py-2 text-sm text-red-600 hover:bg-red-50 dark:hover:bg-gray-700 transition duration-150" role="menuitem">
                                        <i class="fas fa-sign-out-alt w-5 mr-2"></i> Sign out
                                    </a>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </header>

        <!-- Page Content Wrapper -->
        <div class="content-container">
            <div class="p-4 sm:p-6 lg:p-8">
                <!-- Content starts here (Dashboard, Orders, etc.) -->
                <h1 class="text-3xl font-bold tracking-tight text-gray-900 dark:text-white mb-6 hidden sm:block">
                    <?php echo htmlspecialchars($page_title); ?>
                </h1>
                <!-- The rest of the page content will follow this file -->

                <!-- BEGIN main page content (where dashboard.php, orders.php content will go) -->

<!-- IMPORTANT: The file including this header.php must close the remaining <div> tags: </div></div></main></body></html> -->