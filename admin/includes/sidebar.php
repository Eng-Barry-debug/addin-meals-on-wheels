<?php
// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}
?>
<!-- Sidebar -->
<aside class="fixed inset-y-0 left-0 z-20 w-64 bg-gray-800 text-white transform transition-transform duration-300 ease-in-out lg:translate-x-0">
    <div class="flex flex-col h-full">
        <!-- Logo -->
        <div class="flex items-center justify-between h-16 px-6 border-b border-gray-700">
            <a href="dashboard.php" class="flex items-center">
                <img src="/assets/img/logo.png" alt="Addins Meals" class="h-8 w-auto">
                <span class="ml-3 text-xl font-semibold">Addins Meals</span>
            </a>
        </div>

        <!-- Navigation -->
        <nav class="flex-1 overflow-y-auto py-4">
            <div class="px-4 space-y-1">
                <a href="dashboard.php" class="flex items-center px-4 py-3 text-sm font-medium rounded-md group <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'bg-primary text-white' : 'text-gray-300 hover:bg-gray-700 hover:text-white'; ?>">
                    <i class="fas fa-tachometer-alt mr-3 text-lg"></i>
                    <span>Dashboard</span>
                </a>
                <a href="orders.php" class="flex items-center px-4 py-3 text-sm font-medium rounded-md group <?php echo basename($_SERVER['PHP_SELF']) == 'orders.php' ? 'bg-primary text-white' : 'text-gray-300 hover:bg-gray-700 hover:text-white'; ?>">
                    <i class="fas fa-shopping-cart mr-3 text-lg"></i>
                    <span>Orders</span>
                </a>
                <a href="menu.php" class="flex items-center px-4 py-3 text-sm font-medium rounded-md group <?php echo basename($_SERVER['PHP_SELF']) == 'menu.php' ? 'bg-primary text-white' : 'text-gray-300 hover:bg-gray-700 hover:text-white'; ?>">
                    <i class="fas fa-utensils mr-3 text-lg"></i>
                    <span>Menu Items</span>
                </a>
                <a href="categories.php" class="flex items-center px-4 py-3 text-sm font-medium rounded-md group <?php echo basename($_SERVER['PHP_SELF']) == 'categories.php' ? 'bg-primary text-white' : 'text-gray-300 hover:bg-gray-700 hover:text-white'; ?>">
                    <i class="fas fa-tags mr-3 text-lg"></i>
                    <span>Categories</span>
                </a>
                <a href="customers.php" class="flex items-center px-4 py-3 text-sm font-medium rounded-md group <?php echo basename($_SERVER['PHP_SELF']) == 'customers.php' ? 'bg-primary text-white' : 'text-gray-300 hover:bg-gray-700 hover:text-white'; ?>">
                    <i class="fas fa-users mr-3 text-lg"></i>
                    <span>Customers</span>
                </a>
                <a href="ambassador.php" class="flex items-center px-4 py-3 text-sm font-medium rounded-md group <?php echo basename($_SERVER['PHP_SELF']) == 'ambassador.php' ? 'bg-primary text-white' : 'text-gray-300 hover:bg-gray-700 hover:text-white'; ?>">
                    <i class="fas fa-handshake mr-3 text-lg"></i>
                    <span>Ambassador</span>
                </a>
                <a href="reports.php" class="flex items-center px-4 py-3 text-sm font-medium rounded-md group <?php echo basename($_SERVER['PHP_SELF']) == 'reports.php' ? 'bg-primary text-white' : 'text-gray-300 hover:bg-gray-700 hover:text-white'; ?>">
                    <i class="fas fa-chart-bar mr-3 text-lg"></i>
                    <span>Reports</span>
                </a>
                <a href="blog.php" class="flex items-center px-4 py-3 text-sm font-medium rounded-md group <?php echo basename($_SERVER['PHP_SELF']) == 'blog.php' ? 'bg-primary text-white' : 'text-gray-300 hover:bg-gray-700 hover:text-white'; ?>">
                    <i class="fas fa-blog mr-3 text-lg"></i>
                    <span>Blogs</span>
                </a>
                <a href="settings.php" class="flex items-center px-4 py-3 text-sm font-medium rounded-md group <?php echo basename($_SERVER['PHP_SELF']) == 'settings.php' ? 'bg-primary text-white' : 'text-gray-300 hover:bg-gray-700 hover:text-white'; ?>">
                    <i class="fas fa-cog mr-3 text-lg"></i>
                    <span>Settings</span>
                </a>
                <a href="feedback.php" class="flex items-center px-4 py-3 text-sm font-medium rounded-md group <?php echo basename($_SERVER['PHP_SELF']) == 'feedback.php' ? 'bg-primary text-white' : 'text-gray-300 hover:bg-gray-700 hover:text-white'; ?>">
                    <i class="fas fa-comment-alt mr-3 text-lg"></i>
                    <span>Feedback</span>
                    <span class="ml-auto px-2 py-0.5 text-xs font-medium rounded-full bg-secondary text-white">New</span>
                </a>
                <a href="customer_support.php" class="flex items-center px-4 py-3 text-sm font-medium rounded-md group <?php echo basename($_SERVER['PHP_SELF']) == 'customer_support.php' ? 'bg-primary text-white' : 'text-gray-300 hover:bg-gray-700 hover:text-white'; ?>">
                    <i class="fas fa-comments mr-3 text-lg"></i>
                    <span>Customer Support</span>
                </a>
            </div>
        </nav>

        <!-- User Profile -->
        <div class="p-4 border-t border-gray-700">
            <div class="flex items-center">
                <div class="h-10 w-10 rounded-full bg-primary flex items-center justify-center text-white font-medium">
                    <?php echo strtoupper(substr($_SESSION['username'] ?? 'A', 0, 1)); ?>
                </div>
                <div class="ml-3">
                    <p class="text-sm font-medium text-white"><?php echo $_SESSION['username'] ?? 'Admin'; ?></p>
                    <a href="profile.php" class="text-xs font-medium text-gray-400 hover:text-white">View Profile</a>
                </div>
            </div>
        </div>
    </div>
</aside>
