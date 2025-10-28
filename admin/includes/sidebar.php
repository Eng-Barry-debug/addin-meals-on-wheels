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
                <img src="/assets/img/Addin-logo.jpeg" alt="Addins Meals" class="h-8 w-auto">
                <span class="ml-3 text-xl font-semibold">Addins Meals</span>
            </a>
        </div>

        <!-- Navigation -->
        <nav class="flex-1 overflow-y-auto py-4" id="sidebar-nav">
            <div class="px-4 space-y-2">
                <!-- Dashboard & Overview -->
                <div class="menu-section" data-section="overview">
                    <a href="dashboard.php" class="flex items-center px-4 py-3 text-sm font-medium rounded-md group <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'bg-primary text-white' : 'text-gray-300 hover:bg-gray-700 hover:text-white'; ?>">
                        <i class="fas fa-tachometer-alt mr-3 text-lg"></i>
                        <span>Dashboard</span>
                    </a>
                </div>

                <!-- Operations Section -->
                <div class="menu-section" data-section="operations">
                    <button class="section-toggle w-full flex items-center justify-between px-4 py-2 text-sm font-medium text-gray-400 hover:text-white hover:bg-gray-700 rounded-md transition-colors" data-target="operations">
                        <div class="flex items-center">
                            <i class="fas fa-cogs mr-3 text-lg"></i>
                            <span>Operations</span>
                        </div>
                        <i class="fas fa-chevron-down transition-transform duration-200"></i>
                    </button>
                    <div class="section-content hidden pl-8 space-y-1 mt-1">
                        <a href="orders.php" class="flex items-center px-4 py-2 text-sm font-medium rounded-md group <?php echo basename($_SERVER['PHP_SELF']) == 'orders.php' ? 'bg-primary/70 text-white' : 'text-gray-400 hover:bg-gray-700 hover:text-white'; ?>">
                            <i class="fas fa-shopping-cart mr-3 text-sm"></i>
                            <span>Orders</span>
                        </a>
                        <a href="customers.php" class="flex items-center px-4 py-2 text-sm font-medium rounded-md group <?php echo basename($_SERVER['PHP_SELF']) == 'customers.php' ? 'bg-primary/70 text-white' : 'text-gray-400 hover:bg-gray-700 hover:text-white'; ?>">
                            <i class="fas fa-users mr-3 text-sm"></i>
                            <span>Customers</span>
                        </a>
                        <a href="customer_support.php" class="flex items-center px-4 py-2 text-sm font-medium rounded-md group <?php echo basename($_SERVER['PHP_SELF']) == 'customer_support.php' ? 'bg-primary/70 text-white' : 'text-gray-400 hover:bg-gray-700 hover:text-white'; ?>">
                            <i class="fas fa-comments mr-3 text-sm"></i>
                            <span>Customer Support</span>
                        </a>
                    </div>
                </div>

                <!-- Product Management Section -->
                <div class="menu-section" data-section="products">
                    <button class="section-toggle w-full flex items-center justify-between px-4 py-2 text-sm font-medium text-gray-400 hover:text-white hover:bg-gray-700 rounded-md transition-colors" data-target="products">
                        <div class="flex items-center">
                            <i class="fas fa-box mr-3 text-lg"></i>
                            <span>Product Management</span>
                        </div>
                        <i class="fas fa-chevron-down transition-transform duration-200"></i>
                    </button>
                    <div class="section-content hidden pl-8 space-y-1 mt-1">
                        <a href="menu.php" class="flex items-center px-4 py-2 text-sm font-medium rounded-md group <?php echo basename($_SERVER['PHP_SELF']) == 'menu.php' ? 'bg-primary/70 text-white' : 'text-gray-400 hover:bg-gray-700 hover:text-white'; ?>">
                            <i class="fas fa-utensils mr-3 text-sm"></i>
                            <span>Menu Items</span>
                        </a>
                        <a href="categories.php" class="flex items-center px-4 py-2 text-sm font-medium rounded-md group <?php echo basename($_SERVER['PHP_SELF']) == 'categories.php' ? 'bg-primary/70 text-white' : 'text-gray-400 hover:bg-gray-700 hover:text-white'; ?>">
                            <i class="fas fa-tags mr-3 text-sm"></i>
                            <span>Categories</span>
                        </a>
                        <a href="reviews.php" class="flex items-center px-4 py-2 text-sm font-medium rounded-md group <?php echo basename($_SERVER['PHP_SELF']) == 'reviews.php' ? 'bg-primary/70 text-white' : 'text-gray-400 hover:bg-gray-700 hover:text-white'; ?>">
                            <i class="fas fa-star mr-3 text-sm"></i>
                            <span>Reviews</span>
                            <?php
                            try {
                                $stmt = $pdo->prepare("SELECT COUNT(*) FROM customer_reviews WHERE status = 'pending'");
                                $stmt->execute();
                                $pending_count = $stmt->fetchColumn();
                                if ($pending_count > 0):
                            ?>
                                <span class="ml-auto px-2 py-0.5 text-xs font-medium rounded-full bg-red-500 text-white"><?php echo $pending_count; ?></span>
                            <?php endif; } catch (Exception $e) { } ?>
                        </a>
                    </div>
                </div>

                <!-- Marketing & Content Section -->
                <div class="menu-section" data-section="marketing">
                    <button class="section-toggle w-full flex items-center justify-between px-4 py-2 text-sm font-medium text-gray-400 hover:text-white hover:bg-gray-700 rounded-md transition-colors" data-target="marketing">
                        <div class="flex items-center">
                            <i class="fas fa-bullhorn mr-3 text-lg"></i>
                            <span>Marketing & Content</span>
                        </div>
                        <i class="fas fa-chevron-down transition-transform duration-200"></i>
                    </button>
                    <div class="section-content hidden pl-8 space-y-1 mt-1">
                        <a href="ambassador.php" class="flex items-center px-4 py-2 text-sm font-medium rounded-md group <?php echo basename($_SERVER['PHP_SELF']) == 'ambassador.php' ? 'bg-primary/70 text-white' : 'text-gray-400 hover:bg-gray-700 hover:text-white'; ?>">
                            <i class="fas fa-handshake mr-3 text-sm"></i>
                            <span>Ambassador</span>
                        </a>
                        <a href="success_stories.php" class="flex items-center px-4 py-2 text-sm font-medium rounded-md group <?php echo basename($_SERVER['PHP_SELF']) == 'success_stories.php' ? 'bg-primary/70 text-white' : 'text-gray-400 hover:bg-gray-700 hover:text-white'; ?>">
                            <i class="fas fa-star mr-3 text-sm"></i>
                            <span>Success Stories</span>
                        </a>
                        <a href="blog.php" class="flex items-center px-4 py-2 text-sm font-medium rounded-md group <?php echo basename($_SERVER['PHP_SELF']) == 'blog.php' ? 'bg-primary/70 text-white' : 'text-gray-400 hover:bg-gray-700 hover:text-white'; ?>">
                            <i class="fas fa-blog mr-3 text-sm"></i>
                            <span>Blogs</span>
                        </a>
                        <a href="blog_comments.php" class="flex items-center px-4 py-2 text-sm font-medium rounded-md group <?php echo basename($_SERVER['PHP_SELF']) == 'blog_comments.php' ? 'bg-primary/70 text-white' : 'text-gray-400 hover:bg-gray-700 hover:text-white'; ?>">
                            <i class="fas fa-comments mr-3 text-sm"></i>
                            <span>Blog Comments</span>
                            <?php
                            try {
                                $stmt = $pdo->prepare("SELECT COUNT(*) FROM blog_comments WHERE status = 'pending'");
                                $stmt->execute();
                                $pending_comments = $stmt->fetchColumn();
                                if ($pending_comments > 0):
                            ?>
                                <span class="ml-auto px-2 py-0.5 text-xs font-medium rounded-full bg-red-500 text-white"><?php echo $pending_comments; ?></span>
                            <?php endif; } catch (Exception $e) { } ?>
                        </a>
                        <a href="newsletter.php" class="flex items-center px-4 py-2 text-sm font-medium rounded-md group <?php echo basename($_SERVER['PHP_SELF']) == 'newsletter.php' ? 'bg-primary/70 text-white' : 'text-gray-400 hover:bg-gray-700 hover:text-white'; ?>">
                            <i class="fas fa-envelope mr-3 text-sm"></i>
                            <span>Newsletter</span>
                            <?php
                            try {
                                $stmt = $pdo->prepare("SELECT COUNT(*) FROM newsletter_subscriptions WHERE is_active = TRUE");
                                $stmt->execute();
                                $subscriber_count = $stmt->fetchColumn();
                                if ($subscriber_count > 0):
                            ?>
                                <span class="ml-auto px-2 py-0.5 text-xs font-medium rounded-full bg-blue-500 text-white"><?php echo $subscriber_count; ?></span>
                            <?php endif; } catch (Exception $e) { } ?>
                        </a>
                        <a href="create_newsletter.php" class="flex items-center px-4 py-2 text-sm font-medium rounded-md group <?php echo basename($_SERVER['PHP_SELF']) == 'create_newsletter.php' ? 'bg-primary/70 text-white' : 'text-gray-400 hover:bg-gray-700 hover:text-white'; ?>">
                            <i class="fas fa-edit mr-3 text-sm"></i>
                            <span>Create Newsletter</span>
                            <?php
                            try {
                                $stmt = $pdo->prepare("SELECT COUNT(*) FROM newsletter_campaigns WHERE status IN ('draft', 'scheduled')");
                                $stmt->execute();
                                $pending_campaigns = $stmt->fetchColumn();
                                if ($pending_campaigns > 0):
                            ?>
                                <span class="ml-auto px-2 py-0.5 text-xs font-medium rounded-full bg-orange-500 text-white"><?php echo $pending_campaigns; ?></span>
                            <?php endif; } catch (Exception $e) { } ?>
                        </a>
                    </div>
                </div>

                <!-- Administration Section -->
                <div class="menu-section" data-section="admin">
                    <button class="section-toggle w-full flex items-center justify-between px-4 py-2 text-sm font-medium text-gray-400 hover:text-white hover:bg-gray-700 rounded-md transition-colors" data-target="admin">
                        <div class="flex items-center">
                            <i class="fas fa-shield-alt mr-3 text-lg"></i>
                            <span>Administration</span>
                        </div>
                        <i class="fas fa-chevron-down transition-transform duration-200"></i>
                    </button>
                    <div class="section-content hidden pl-8 space-y-1 mt-1">
                        <a href="teams.php" class="flex items-center px-4 py-2 text-sm font-medium rounded-md group <?php echo basename($_SERVER['PHP_SELF']) == 'teams.php' ? 'bg-primary/70 text-white' : 'text-gray-400 hover:bg-gray-700 hover:text-white'; ?>">
                            <i class="fas fa-user-tie mr-3 text-sm"></i>
                            <span>Team Members</span>
                        </a>
                        <a href="reports.php" class="flex items-center px-4 py-2 text-sm font-medium rounded-md group <?php echo basename($_SERVER['PHP_SELF']) == 'reports.php' ? 'bg-primary/70 text-white' : 'text-gray-400 hover:bg-gray-700 hover:text-white'; ?>">
                            <i class="fas fa-chart-bar mr-3 text-sm"></i>
                            <span>Reports</span>
                        </a>
                        <a href="settings.php" class="flex items-center px-4 py-2 text-sm font-medium rounded-md group <?php echo basename($_SERVER['PHP_SELF']) == 'settings.php' ? 'bg-primary/70 text-white' : 'text-gray-400 hover:bg-gray-700 hover:text-white'; ?>">
                            <i class="fas fa-cog mr-3 text-sm"></i>
                            <span>Settings</span>
                        </a>
                        <a href="feedback.php" class="flex items-center px-4 py-2 text-sm font-medium rounded-md group <?php echo basename($_SERVER['PHP_SELF']) == 'feedback.php' ? 'bg-primary/70 text-white' : 'text-gray-400 hover:bg-gray-700 hover:text-white'; ?>">
                            <i class="fas fa-comment-alt mr-3 text-sm"></i>
                            <span>Feedback</span>
                            <span class="ml-auto px-2 py-0.5 text-xs font-medium rounded-full bg-secondary text-white">New</span>
                        </a>
                    </div>
                </div>
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

    <!-- Sidebar Scroll Position Management -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const sidebarNav = document.getElementById('sidebar-nav');
            const currentPage = '<?php echo basename($_SERVER['PHP_SELF']); ?>';

            // Initialize collapsible sections
            initializeCollapsibleSections();

            // Restore saved scroll position
            const savedScrollPosition = localStorage.getItem('sidebarScrollPosition');
            if (savedScrollPosition) {
                sidebarNav.scrollTop = parseInt(savedScrollPosition);
            }

            // Find and highlight the active menu item, then scroll to it
            const menuItems = sidebarNav.querySelectorAll('a');
            let activeItem = null;

            menuItems.forEach(item => {
                // Check if this is the current page
                const href = item.getAttribute('href');
                if (href && href.includes(currentPage)) {
                    activeItem = item;
                    return;
                }
            });

            // If we found the active item, scroll it into view and expand its section
            if (activeItem) {
                // Add a slight delay to ensure the page is fully loaded
                setTimeout(() => {
                    // Find the section containing this item and expand it
                    const section = activeItem.closest('.menu-section');
                    if (section) {
                        const sectionToggle = section.querySelector('.section-toggle');
                        const sectionContent = section.querySelector('.section-content');
                        if (sectionToggle && sectionContent) {
                            expandSection(sectionToggle, sectionContent);
                        }
                    }

                    // Scroll the active item into view
                    activeItem.scrollIntoView({
                        behavior: 'smooth',
                        block: 'center'
                    });

                    // Also add a temporary highlight effect
                    activeItem.style.backgroundColor = 'rgba(252, 119, 3, 0.2)';
                    setTimeout(() => {
                        activeItem.style.backgroundColor = '';
                    }, 2000);
                }, 100);
            }

            // Save scroll position when user scrolls or navigates away
            let scrollTimeout;
            sidebarNav.addEventListener('scroll', function() {
                clearTimeout(scrollTimeout);
                scrollTimeout = setTimeout(() => {
                    localStorage.setItem('sidebarScrollPosition', sidebarNav.scrollTop);
                }, 150);
            });

            // Save scroll position before page unload
            window.addEventListener('beforeunload', function() {
                localStorage.setItem('sidebarScrollPosition', sidebarNav.scrollTop);
            });

            // Also save on link clicks (for SPA-like behavior)
            menuItems.forEach(item => {
                item.addEventListener('click', function() {
                    localStorage.setItem('sidebarScrollPosition', sidebarNav.scrollTop);
                });
            });

            // Initialize collapsible sections functionality
            function initializeCollapsibleSections() {
                const sectionToggles = document.querySelectorAll('.section-toggle');

                sectionToggles.forEach(toggle => {
                    toggle.addEventListener('click', function() {
                        const targetId = this.getAttribute('data-target');
                        const sectionContent = this.parentNode.querySelector('.section-content');

                        if (sectionContent.classList.contains('hidden')) {
                            expandSection(this, sectionContent);
                        } else {
                            collapseSection(this, sectionContent);
                        }
                    });
                });

                // Auto-expand section containing current page on load
                const currentSection = document.querySelector(`[data-section="${getCurrentSection()}"]`);
                if (currentSection) {
                    const toggle = currentSection.querySelector('.section-toggle');
                    const content = currentSection.querySelector('.section-content');
                    if (toggle && content) {
                        expandSection(toggle, content);
                    }
                }
            }

            function expandSection(toggle, content) {
                content.classList.remove('hidden');
                const chevron = toggle.querySelector('.fas');
                chevron.classList.remove('fa-chevron-down');
                chevron.classList.add('fa-chevron-up');
                toggle.classList.add('text-white');
                toggle.classList.remove('text-gray-400');
            }

            function collapseSection(toggle, content) {
                content.classList.add('hidden');
                const chevron = toggle.querySelector('.fas');
                chevron.classList.remove('fa-chevron-up');
                chevron.classList.add('fa-chevron-down');
                toggle.classList.remove('text-white');
                toggle.classList.add('text-gray-400');
            }

            function getCurrentSection() {
                // Determine which section the current page belongs to
                const pageSections = {
                    'orders.php': 'operations',
                    'customers.php': 'operations',
                    'customer_support.php': 'operations',
                    'menu.php': 'products',
                    'categories.php': 'products',
                    'reviews.php': 'products',
                    'ambassador.php': 'marketing',
                    'success_stories.php': 'marketing',
                    'blog.php': 'marketing',
                    'newsletter.php': 'marketing',
                    'create_newsletter.php': 'marketing',
                    'reports.php': 'admin',
                    'settings.php': 'admin',
                    'feedback.php': 'admin'
                };

                return pageSections[currentPage] || 'overview';
            }
        });
    </script>
</aside>
