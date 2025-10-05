<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Initialize cart if not already done
if (!isset($cart) && file_exists(__DIR__ . '/config.php')) {
    require_once __DIR__ . '/config.php';
    require_once __DIR__ . '/Cart.php';
    try {
        $cart = new Cart($pdo);
    } catch (Exception $e) {
        error_log('Cart initialization error: ' . $e->getMessage());
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Addins Meals on Wheels - Delicious Food Delivered</title>
    <style>
        [x-cloak] { display: none !important; }
        .transition {
            transition-property: background-color, border-color, color, fill, stroke, opacity, box-shadow, transform;
            transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1);
            transition-duration: 150ms;
        }
        .ease-out { transition-timing-function: cubic-bezier(0, 0, 0.2, 1); }
        .ease-in { transition-timing-function: cubic-bezier(0.4, 0, 1, 1); }
    </style>
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="/uploads/menu/Addin-logo.jpeg">
    <!-- Tailwind CSS CDN for faster loading -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#C1272D',
                        secondary: '#FF8C00',
                        dark: '#1A1A1A',
                        light: '#F5E6D3',
                        accent: '#2E5E3A',
                        neutral: '#212121',
                    },
                    fontFamily: {
                        heading: ['Poppins', 'Inter', 'sans-serif'],
                        body: ['Open Sans', 'Roboto', 'sans-serif'],
                    }
                }
            }
        }
    </script>
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Alpine.js -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <script>
        // Debug Alpine.js loading
        document.addEventListener('alpine:init', () => {
            console.log('Alpine.js loaded successfully');
        });

        // Fallback check if Alpine is available
        document.addEventListener('DOMContentLoaded', function() {
            if (typeof Alpine !== 'undefined') {
                console.log('Alpine.js is available');
                // Alpine.start() is already called by the Alpine.js library itself
                // No need to call it again here to avoid double initialization
            } else {
                console.error('Alpine.js failed to load');
            }
        });
    </script>
    <!-- Custom JavaScript for cart functionality -->
    <script>
    // Function to update cart count
    function updateCartCount() {
        try {
            // Update desktop cart count
            const desktopBadge = document.querySelector('.cart-count-desktop');
            if (desktopBadge) {
                fetch('/api/cart_count.php')
                    .then(response => {
                        if (!response.ok) {
                            throw new Error('Network response was not ok');
                        }
                        return response.json();
                    })
                    .then(data => {
                        if (data && data.success) {
                            const count = data.count || 0;
                            desktopBadge.textContent = count > 99 ? '99+' : count;
                            desktopBadge.style.display = count > 0 ? 'flex' : 'none';
                        }
                    })
                    .catch(error => console.error('Error updating cart count:', error));
            }

            // Update mobile cart count
            const mobileBadge = document.querySelector('.cart-count-mobile');
            if (mobileBadge) {
                fetch('/api/cart_count.php')
                    .then(response => {
                        if (!response.ok) {
                            throw new Error('Network response was not ok');
                        }
                        return response.json();
                    })
                    .then(data => {
                        if (data && data.success) {
                            const count = data.count || 0;
                            mobileBadge.textContent = count > 99 ? '99+' : count;
                            mobileBadge.style.display = count > 0 ? 'flex' : 'none';
                        }
                    })
                    .catch(error => console.error('Error updating mobile cart count:', error));
            }
        } catch (error) {
            console.error('Error in updateCartCount function:', error);
        }
    }
    function updateNotificationCount() {
        <?php if (isset($_SESSION['user_id'])): ?>
        fetch('/api/customerdashboard_api.php?action=get_dashboard_stats')
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const unreadCount = data.stats.unread_messages;

                    // Update desktop notification badge
                    const desktopBadge = document.getElementById('notificationBadge');
                    if (desktopBadge) {
                        if (unreadCount > 0) {
                            desktopBadge.textContent = unreadCount > 99 ? '99+' : unreadCount;
                            desktopBadge.classList.remove('hidden');
                        } else {
                            desktopBadge.classList.add('hidden');
                        }
                    }

                    // Update mobile notification badge
                    const mobileBadge = document.getElementById('notificationBadgeMobile');
                    if (mobileBadge) {
                        if (unreadCount > 0) {
                            mobileBadge.textContent = unreadCount > 99 ? '99+' : unreadCount;
                            mobileBadge.classList.remove('hidden');
                        } else {
                            mobileBadge.classList.add('hidden');
                        }
                    }
                }
            })
            .catch(error => console.error('Error updating notification count:', error));
        <?php endif; ?>
    }

    // Function to set active navigation
    function setActiveNavigation() {
        const currentPath = window.location.pathname;
        const navLinks = document.querySelectorAll('nav a[href]');

        navLinks.forEach(link => {
            const href = link.getAttribute('href');
            if (href && (currentPath === href || (href !== '/' && currentPath.startsWith(href)))) {
                link.classList.add('border-b-2', 'border-secondary', 'text-secondary');
                link.classList.remove('hover:bg-primary', 'hover:text-white');
            } else if (href && href !== '/cart.php') {
                link.classList.remove('border-b-2', 'border-secondary', 'text-secondary');
                link.classList.add('hover:bg-primary', 'hover:text-white');
            }
        });
    }

    // Update cart count when the page loads
    document.addEventListener('DOMContentLoaded', function() {
        updateCartCount();
        updateNotificationCount();
        setActiveNavigation();

        // Listen for custom cart update event
        document.addEventListener('cartUpdated', function() {
            updateCartCount();
        });

        // Listen for notification update event
        document.addEventListener('notificationsUpdated', function() {
            updateNotificationCount();
        });

        // Auto-update notifications every 30 seconds
        <?php if (isset($_SESSION['user_id'])): ?>
        setInterval(updateNotificationCount, 30000);
        <?php endif; ?>
    });
    </script>
</head>
<body class="bg-light">
    <!-- Navigation -->
    <nav class="bg-dark text-white shadow-md sticky top-0 z-50 relative">
        <div class="container mx-auto px-4 py-3">
            <div class="flex justify-between items-center">
                <!-- Logo -->
                <a href="/" class="flex items-center space-x-2 group">
                    <img src="/uploads/menu/Addin-logo.jpeg" alt="Addins Meals on Wheels" class="h-12 transition-transform group-hover:scale-105">
                    <span class="text-xl font-bold">Addins Meals</span>
                </a>

                <!-- Desktop Navigation -->
                <div class="hidden md:flex items-center space-x-1">
                    <a href="/index.php" class="px-4 py-2 rounded-md hover:bg-secondary hover:text-dark transition-colors duration-200 font-medium <?php echo (basename($_SERVER['PHP_SELF']) == 'index.php' || $_SERVER['REQUEST_URI'] == '/' || $_SERVER['PHP_SELF'] == '/index.php') ? 'bg-secondary text-dark' : ''; ?>">Home</a>
                    <a href="/menu.php" class="px-4 py-2 rounded-md hover:bg-secondary hover:text-dark transition-colors duration-200 font-medium <?php echo basename($_SERVER['PHP_SELF']) == 'menu.php' ? 'bg-secondary text-dark' : ''; ?>">Menu</a>
                    <a href="/catering.php" class="px-4 py-2 rounded-md hover:bg-secondary hover:text-dark transition-colors duration-200 font-medium <?php echo basename($_SERVER['PHP_SELF']) == 'catering.php' ? 'bg-secondary text-dark' : ''; ?>">Catering</a>
                    <a href="/blog.php" class="px-4 py-2 rounded-md hover:bg-secondary hover:text-dark transition-colors duration-200 font-medium <?php echo basename($_SERVER['PHP_SELF']) == 'blog.php' ? 'bg-secondary text-dark' : ''; ?>">Blog</a>
                    <a href="/about.php" class="px-4 py-2 rounded-md hover:bg-secondary hover:text-dark transition-colors duration-200 font-medium <?php echo basename($_SERVER['PHP_SELF']) == 'about.php' ? 'bg-secondary text-dark' : ''; ?>">About Us</a>
                    <a href="/contact.php" class="px-4 py-2 rounded-md hover:bg-secondary hover:text-dark transition-colors duration-200 font-medium <?php echo basename($_SERVER['PHP_SELF']) == 'contact.php' ? 'bg-secondary text-dark' : ''; ?>">Contact</a>
                    <a href="/ambassador.php" class="px-4 py-2 rounded-md hover:bg-secondary hover:text-dark transition-colors duration-200 font-medium ml-2 <?php echo basename($_SERVER['PHP_SELF']) == 'ambassador.php' ? 'bg-secondary text-dark' : ''; ?>">Ambassador</a>
                    <a href="/cart.php" class="relative p-2 rounded-full hover:bg-secondary hover:text-dark transition-colors duration-200 ml-2 <?php echo basename($_SERVER['PHP_SELF']) == 'cart.php' ? 'bg-secondary text-dark' : ''; ?>">
                        <i class="fas fa-shopping-cart text-xl"></i>
                        <span class="cart-count-desktop absolute -top-1 -right-1 bg-accent text-secondary text-xs font-bold rounded-full h-5 w-5 flex items-center justify-center">0</span>
                    </a>
                    <?php if (isset($_SESSION['user_id'])): ?>
                    <!-- Notifications Bell -->
                    <a href="/chat.php" class="relative p-2 rounded-full hover:bg-secondary hover:text-dark transition-colors duration-200 ml-2 <?php echo basename($_SERVER['PHP_SELF']) == 'chat.php' ? 'bg-secondary text-dark' : ''; ?>" title="Notifications">
                        <i class="fas fa-bell text-xl"></i>
                        <span class="notification-count-desktop absolute -top-1 -right-1 bg-red-500 text-white text-xs font-bold rounded-full h-5 w-5 flex items-center justify-center hidden" id="notificationBadge">0</span>
                    </a>
                    <?php endif; ?>
                    <?php if (isset($_SESSION['user_id'])): ?>
                    <!-- User Dropdown -->
                    <div x-data="{ open: false }" class="relative">
                        <button 
                            @click="open = !open" 
                            @keydown.escape="open = false"
                            class="flex items-center space-x-2 px-4 py-2 bg-secondary text-dark rounded-md hover:bg-opacity-90 transition-colors duration-200 font-medium focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-secondary"
                            :aria-expanded="open"
                            aria-haspopup="true"
                        >
                            <span><?php echo htmlspecialchars(explode('@', $_SESSION['email'] ?? 'User')[0]); ?></span>
                            <i class="fas fa-chevron-down text-xs transition-transform duration-200" :class="{ 'transform rotate-180': open }"></i>
                        </button>
                        <div 
                            x-show="open"
                            x-transition:enter="transition ease-out duration-100"
                            x-transition:enter-start="opacity-0 scale-95"
                            x-transition:enter-end="opacity-100 scale-100"
                            x-transition:leave="transition ease-in duration-75"
                            x-transition:leave-start="opacity-100 scale-100"
                            x-transition:leave-end="opacity-0 scale-95"
                            @click.away="open = false"
                            x-cloak
                            x-cloak
                            class="origin-top-right absolute right-0 mt-2 w-48 rounded-md shadow-lg bg-white ring-1 ring-black ring-opacity-5 focus:outline-none z-50"
                            role="menu"
                            aria-orientation="vertical"
                            tabindex="-1"
                        >
                            <a href="/account/customerdashboard.php" class="block px-4 py-2 text-gray-800 hover:bg-gray-100"><i class="fas fa-user-circle mr-2"></i>My Account</a>
                            <a href="/account/orders.php" class="block px-4 py-2 text-gray-800 hover:bg-gray-100"><i class="fas fa-shopping-bag mr-2"></i>My Orders</a>
                            <a href="/chat.php" class="block px-4 py-2 text-gray-800 hover:bg-gray-100"><i class="fas fa-comments mr-2"></i>Chat Support</a>
                            <div class="border-t border-gray-200 my-1"></div>
                            <a href="/auth/logout.php" class="block px-4 py-2 text-red-600 hover:bg-gray-100"><i class="fas fa-sign-out-alt mr-2"></i>Logout</a>
                        </div>
                    </div>
                    <?php else: ?>
                    <a href="/auth/login.php" class="ml-2 px-6 py-2 bg-primary text-white rounded-md hover:bg-opacity-90 transition-colors duration-200 font-medium">Login</a>
                    <?php endif; ?>
                </div>

                <!-- Mobile menu button -->
                <div class="md:hidden" x-data="{ mobileMenuOpen: false }" x-effect="console.log('Mobile menu state:', mobileMenuOpen)">
                    <button
                        @click="mobileMenuOpen = !mobileMenuOpen; console.log('Hamburger clicked, menu open:', mobileMenuOpen)"
                        :aria-expanded="mobileMenuOpen"
                        type="button"
                        class="text-white focus:outline-none focus:ring-2 focus:ring-white/30 p-3 hover:bg-primary rounded-lg transition-all duration-200 shadow-lg hover:shadow-xl"
                        aria-label="Toggle mobile menu"
                    >
                        <i class="fas fa-bars text-2xl" x-show="!mobileMenuOpen" x-transition:enter="transition-transform duration-300" x-transition:enter-start="rotate-90 opacity-0" x-transition:enter-end="rotate-0 opacity-100" x-cloak></i>
                        <i class="fas fa-times text-2xl" x-show="mobileMenuOpen" x-transition:enter="transition-transform duration-300" x-transition:enter-start="-rotate-90 opacity-0" x-transition:enter-end="rotate-0 opacity-100" x-cloak></i>
                    </button>
                </div>
            </div>

            <!-- Mobile Navigation -->
            <div
                x-show="mobileMenuOpen"
                x-transition:enter="transition ease-out duration-300"
                x-transition:enter-start="opacity-0 transform -translate-y-4 scale-95"
                x-transition:enter-end="opacity-100 transform translate-y-0 scale-100"
                x-transition:leave="transition ease-in duration-200"
                x-transition:leave-start="opacity-100 transform translate-y-0 scale-100"
                x-transition:leave-end="opacity-0 transform -translate-y-4 scale-95"
                @click.away="mobileMenuOpen = false"
                x-cloak
                class="md:hidden absolute top-full left-0 right-0 bg-dark/95 backdrop-blur-md shadow-2xl border-t border-white/20"
                style="z-index: 9999;"
                x-effect="console.log('Mobile menu visibility:', mobileMenuOpen)"
            >
                <div class="container mx-auto px-4 py-6 space-y-2">
                    <a href="/index.php" class="block py-4 px-4 rounded-xl hover:bg-secondary hover:text-dark transition-all duration-200 font-medium text-lg">
                        <i class="fas fa-home mr-3"></i>Home
                    </a>
                    <a href="/menu.php" class="block py-4 px-4 rounded-xl hover:bg-secondary hover:text-dark transition-all duration-200 font-medium text-lg">
                        <i class="fas fa-utensils mr-3"></i>Menu
                    </a>
                    <a href="/catering.php" class="block py-4 px-4 rounded-xl hover:bg-secondary hover:text-dark transition-all duration-200 font-medium text-lg">
                        <i class="fas fa-concierge-bell mr-3"></i>Catering
                    </a>
                    <a href="/blog.php" class="block py-4 px-4 rounded-xl hover:bg-secondary hover:text-dark transition-all duration-200 font-medium text-lg">
                        <i class="fas fa-blog mr-3"></i>Blog
                    </a>
                    <a href="/about.php" class="block py-4 px-4 rounded-xl hover:bg-secondary hover:text-dark transition-all duration-200 font-medium text-lg">
                        <i class="fas fa-info-circle mr-3"></i>About Us
                    </a>
                    <a href="/contact.php" class="block py-4 px-4 rounded-xl hover:bg-secondary hover:text-dark transition-all duration-200 font-medium text-lg">
                        <i class="fas fa-phone mr-3"></i>Contact
                    </a>
                    <a href="/ambassador.php" class="block py-4 px-4 rounded-xl hover:bg-secondary hover:text-dark transition-all duration-200 font-medium text-lg">
                        <i class="fas fa-crown mr-3"></i>Become an Ambassador
                    </a>

                    <!-- Cart Link for Mobile -->
                    <div class="border-t border-white/30 my-3"></div>
                    <a href="/cart.php" class="flex items-center py-4 px-4 rounded-xl hover:bg-secondary hover:text-dark transition-all duration-200 text-lg">
                        <i class="fas fa-shopping-cart mr-3"></i>
                        <span>Cart</span>
                        <span class="cart-count-mobile ml-auto bg-accent text-secondary text-sm font-bold rounded-full h-6 w-6 flex items-center justify-center">0</span>
                    </a>

                    <?php if (isset($_SESSION['user_id'])): ?>
                    <!-- Notifications for Mobile -->
                    <a href="/chat.php" class="flex items-center py-4 px-4 rounded-xl hover:bg-secondary hover:text-dark transition-all duration-200 text-lg">
                        <i class="fas fa-bell mr-3"></i>
                        <span>Notifications</span>
                        <span class="notification-count-mobile ml-auto bg-red-500 text-white text-sm font-bold rounded-full h-6 w-6 flex items-center justify-center hidden" id="notificationBadgeMobile">0</span>
                    </a>
                    <?php endif; ?>

                    <?php if (isset($_SESSION['user_id'])): ?>
                    <!-- User Menu for Mobile -->
                    <div class="border-t border-white/30 my-3"></div>
                    <div class="space-y-2">
                        <a href="/account/customerdashboard.php" class="block py-3 px-4 text-white hover:bg-secondary hover:text-dark rounded-xl transition-all duration-200 text-lg">
                            <i class="fas fa-user-circle mr-3"></i>My Account
                        </a>
                        <a href="/account/orders.php" class="block py-3 px-4 text-white hover:bg-secondary hover:text-dark rounded-xl transition-all duration-200 text-lg">
                            <i class="fas fa-shopping-bag mr-3"></i>My Orders
                        </a>
                        <a href="/chat.php" class="block py-3 px-4 text-white hover:bg-secondary hover:text-dark rounded-xl transition-all duration-200 text-lg">
                            <i class="fas fa-comments mr-3"></i>Chat Support
                        </a>
                        <a href="/auth/logout.php" class="block py-3 px-4 text-red-400 hover:bg-red-600 hover:text-white rounded-xl transition-all duration-200 text-lg">
                            <i class="fas fa-sign-out-alt mr-3"></i>Logout
                        </a>
                    </div>
                    <?php else: ?>
                    <div class="border-t border-white/30 my-3"></div>
                    <a href="/auth/login.php" class="block py-4 px-4 bg-secondary text-dark text-center rounded-xl hover:bg-opacity-90 transition-all duration-200 font-medium text-lg">
                        <i class="fas fa-sign-in-alt mr-3"></i>Login
                    </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </nav>

    <main>
