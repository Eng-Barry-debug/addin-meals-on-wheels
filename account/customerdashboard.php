<?php
// Include configuration and check login
require_once __DIR__ . '/../admin/includes/config.php';
requireLogin();

$pageTitle = 'Customer Dashboard';
include '../includes/header.php';
?>

<div class="container mx-auto px-4 py-8">
    <div class="max-w-6xl mx-auto">
        <!-- Welcome Section -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-8">
            <h1 class="text-3xl font-bold text-dark mb-2">Welcome, <?php echo htmlspecialchars($_SESSION['username'] ?? 'Customer'); ?>!</h1>
            <p class="text-gray-600">Here's what's happening with your account today.</p>
        </div>

        <!-- Quick Stats -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <!-- Orders Card -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-blue-100 text-blue-600 mr-4">
                        <i class="fas fa-shopping-bag text-xl"></i>
                    </div>
                    <div>
                        <p class="text-gray-500 text-sm">Total Orders</p>
                        <h3 class="text-2xl font-bold" id="totalOrders">0</h3>
                    </div>
                </div>
                <a href="/account/orders.php" class="mt-4 inline-block text-primary hover:underline text-sm">View All Orders →</a>
            </div>

            <!-- Messages Card -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-green-100 text-green-600 mr-4">
                        <i class="fas fa-envelope text-xl"></i>
                    </div>
                    <div class="flex-1">
                        <p class="text-gray-500 text-sm">Unread Messages</p>
                        <h3 class="text-2xl font-bold" id="unreadMessages">0</h3>
                    </div>
                </div>
                <div class="mt-4 flex space-x-2">
                    <a href="/chat.php" class="inline-block text-primary hover:underline text-sm">View Messages →</a>
                    <button onclick="markAllMessagesRead()" id="markReadBtn" class="text-gray-400 hover:text-green-600 text-sm transition-colors" style="display: none;">
                        <i class="fas fa-check-double mr-1"></i>Mark all read
                    </button>
                </div>
            </div>

            <!-- Favorites Card -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-yellow-100 text-yellow-600 mr-4">
                        <i class="fas fa-heart text-xl"></i>
                    </div>
                    <div>
                        <p class="text-gray-500 text-sm">Saved Items</p>
                        <h3 class="text-2xl font-bold" id="savedItems">0</h3>
                    </div>
                </div>
                <a href="/account/wishlist.php" class="mt-4 inline-block text-primary hover:underline text-sm">View Favorites →</a>
            </div>
        </div>

        <!-- Recent Activity -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-8">
            <div class="flex justify-between items-center mb-6">
                <h2 class="text-xl font-semibold text-dark">Recent Activity</h2>
                <div class="flex space-x-4">
                    <a href="/account/orders.php" class="text-primary hover:underline text-sm">View All Orders</a>
                    <a href="/account/addresses.php" class="text-primary hover:underline text-sm">Manage Addresses</a>
                </div>
            </div>

            <div id="recentActivity" class="space-y-4">
                <!-- Loading indicator -->
                <div id="activityLoading" class="text-center py-8">
                    <div class="inline-flex items-center">
                        <i class="fas fa-spinner fa-spin text-primary mr-2"></i>
                        <span class="text-gray-600">Loading recent activity...</span>
                    </div>
                </div>

                <!-- Activity items will be loaded here -->
            </div>
        </div>

        <!-- Quick Actions & Shortcuts -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
            <!-- Primary Actions -->
            <div class="bg-gradient-to-br from-primary to-primary-dark rounded-xl shadow-lg p-6 text-white">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold">Quick Order</h3>
                    <i class="fas fa-utensils text-2xl opacity-80"></i>
                </div>
                <p class="text-primary-foreground/80 mb-4">Ready to order? Browse our menu and place your order in minutes.</p>
                <a href="/menu.php" class="inline-flex items-center px-4 py-2 bg-white text-primary font-semibold rounded-lg hover:bg-gray-50 transition-colors">
                    <i class="fas fa-arrow-right mr-2"></i>Browse Menu
                </a>
            </div>

            <!-- Account Management -->
            <div class="bg-gradient-to-br from-secondary to-secondary-dark rounded-xl shadow-lg p-6 text-white">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold">Account Center</h3>
                    <i class="fas fa-user-cog text-2xl opacity-80"></i>
                </div>
                <p class="text-secondary-foreground/80 mb-4">Manage your profile, addresses, and account settings.</p>
                <div class="space-y-2">
                    <a href="/account/addresses.php" class="block text-sm hover:underline">
                        <i class="fas fa-map-marker-alt mr-2"></i>My Addresses
                    </a>
                    <a href="/account/orders.php" class="block text-sm hover:underline">
                        <i class="fas fa-history mr-2"></i>Order History
                    </a>
                    <a href="/account/wishlist.php" class="block text-sm hover:underline">
                        <i class="fas fa-heart mr-2"></i>Wishlist
                    </a>
                </div>
            </div>

            <!-- Support & Help -->
            <div class="bg-gradient-to-br from-accent to-accent-dark rounded-xl shadow-lg p-6 text-white">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold">Help & Support</h3>
                    <i class="fas fa-headset text-2xl opacity-80"></i>
                </div>
                <p class="text-accent-foreground/80 mb-4">Need help? Get support or contact our team.</p>
                <div class="space-y-2">
                    <a href="/chat.php" class="block text-sm hover:underline">
                        <i class="fas fa-comments mr-2"></i>Live Chat
                    </a>
                    <a href="/faq.php" class="block text-sm hover:underline">
                        <i class="fas fa-question-circle mr-2"></i>FAQ
                    </a>
                    <a href="/contact.php" class="block text-sm hover:underline">
                        <i class="fas fa-envelope mr-2"></i>Contact Us
                    </a>
                </div>
            </div>
        </div>

        <!-- Featured & Recommendations -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            <!-- Popular Items -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">
                    <i class="fas fa-fire text-orange-500 mr-2"></i>Popular This Week
                </h3>
                <div id="popularItemsContainer" class="space-y-3">
                    <!-- Loading indicator -->
                    <div id="popularItemsLoading" class="text-center py-8">
                        <div class="inline-flex items-center">
                            <i class="fas fa-spinner fa-spin text-primary mr-2"></i>
                            <span class="text-gray-600">Loading popular items...</span>
                        </div>
                    </div>
                </div>
                <a href="/menu.php" class="mt-4 inline-block text-primary hover:underline text-sm">
                    View Full Menu →
                </a>
            </div>

            <!-- Order Tips -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">
                    <i class="fas fa-lightbulb text-yellow-500 mr-2"></i>Ordering Tips
                </h3>
                <div class="space-y-4 text-sm text-gray-600">
                    <div class="flex items-start">
                        <i class="fas fa-check-circle text-green-500 mr-3 mt-0.5"></i>
                        <div>
                            <p class="font-medium text-gray-900">Save addresses for faster checkout</p>
                            <p>Add your delivery addresses to skip address entry next time</p>
                        </div>
                    </div>
                    <div class="flex items-start">
                        <i class="fas fa-check-circle text-green-500 mr-3 mt-0.5"></i>
                        <div>
                            <p class="font-medium text-gray-900">Track your orders in real-time</p>
                            <p>Use the order tracking page to see your delivery status</p>
                        </div>
                    </div>
                    <div class="flex items-start">
                        <i class="fas fa-check-circle text-green-500 mr-3 mt-0.5"></i>
                        <div>
                            <p class="font-medium text-gray-900">Save favorites for quick reordering</p>
                            <p>Add items to your wishlist for easy access later</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Real-time Dashboard JavaScript -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Load initial dashboard data
    loadDashboardData();

    // Set up auto-refresh every 30 seconds
    setInterval(loadDashboardData, 30000);
});

async function loadDashboardData() {
    try {
        // Load stats
        const statsResponse = await fetch('/api/customerdashboard_api.php?action=get_dashboard_stats');
        const statsData = await statsResponse.json();

        if (statsData.success) {
            document.getElementById('totalOrders').textContent = statsData.stats.total_orders;
            document.getElementById('unreadMessages').textContent = statsData.stats.unread_messages;
            document.getElementById('savedItems').textContent = statsData.stats.saved_items;

            // Show/hide "Mark all read" button based on unread messages count
            const markReadBtn = document.getElementById('markReadBtn');
            if (statsData.stats.unread_messages > 0) {
                markReadBtn.style.display = 'inline-block';
            } else {
                markReadBtn.style.display = 'none';
            }
        }

        // Load recent activity
        const activityResponse = await fetch('/api/customerdashboard_api.php?action=get_recent_activity');
        const activityData = await activityResponse.json();

        if (activityData.success) {
            displayRecentActivity(activityData.activities);
        }

        // Load popular items
        const popularResponse = await fetch('/api/customerdashboard_api.php?action=get_popular_items');
        const popularData = await popularResponse.json();

        if (popularData.success) {
            displayPopularItems(popularData.items);
        }

    } catch (error) {
        console.error('Error loading dashboard data:', error);
    }
}

function displayRecentActivity(activities) {
    const container = document.getElementById('recentActivity');
    const loadingIndicator = document.getElementById('activityLoading');

    // Remove loading indicator
    if (loadingIndicator) {
        loadingIndicator.remove();
    }

    if (activities.length === 0) {
        container.innerHTML = `
            <div class="text-center py-8 text-gray-500">
                <i class="fas fa-inbox text-4xl mb-4"></i>
                <p>No recent activity</p>
                <p class="text-sm">Your recent orders and messages will appear here</p>
            </div>
        `;
        return;
    }

    const activitiesHtml = activities.map(activity => `
        <div class="flex items-start pb-4 border-b border-gray-100 last:border-b-0">
            <div class="p-2 rounded-full bg-${getActivityColor(activity.color)}-100 text-${getActivityColor(activity.color)}-600 mr-4">
                <i class="${activity.icon}"></i>
            </div>
            <div class="flex-1">
                <p class="text-gray-800">${activity.title}</p>
                <p class="text-sm text-gray-500">${activity.description} • ${formatTime(activity.created_at)}</p>
            </div>
        </div>
    `).join('');

    container.innerHTML = activitiesHtml;
}

function displayPopularItems(items) {
    const container = document.getElementById('popularItemsContainer');
    const loadingIndicator = document.getElementById('popularItemsLoading');

    // Remove loading indicator
    if (loadingIndicator) {
        loadingIndicator.remove();
    }

    if (items.length === 0) {
        container.innerHTML = `
            <div class="text-center py-8 text-gray-500">
                <i class="fas fa-chart-line text-4xl mb-4"></i>
                <p>No popular items yet</p>
                <p class="text-sm">Popular items will appear here once orders start coming in</p>
            </div>
        `;
        return;
    }

    const itemsHtml = items.map(item => `
        <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors cursor-pointer"
             onclick="window.location.href='/menu-single.php?id=${item.id}'">
            <div class="flex items-center">
                <div class="w-12 h-12 ${item.image ? 'bg-gray-200' : 'bg-primary/10'} rounded-lg flex items-center justify-center mr-3 overflow-hidden">
                    ${item.image ?
                        `<img src="../uploads/menu/${item.image}" alt="${item.name}" class="w-full h-full object-cover">` :
                        `<i class="fas fa-utensils text-primary"></i>`
                    }
                </div>
                <div>
                    <h4 class="font-medium text-gray-900">${item.name}</h4>
                    <div class="flex items-center text-sm text-gray-600">
                        ${item.avg_rating ? `
                            <div class="flex items-center mr-3">
                                <i class="fas fa-star text-yellow-400 text-xs mr-1"></i>
                                <span>${parseFloat(item.avg_rating).toFixed(1)}</span>
                            </div>
                        ` : ''}
                        ${item.order_count ? `
                            <span>${item.order_count} orders</span>
                        ` : ''}
                    </div>
                </div>
            </div>
            <span class="font-bold text-primary">KSh ${parseFloat(item.price).toFixed(2)}</span>
        </div>
    `).join('');

    container.innerHTML = itemsHtml;
}

function getActivityColor(color) {
    const colors = {
        'blue': 'blue',
        'green': 'green',
        'red': 'red',
        'yellow': 'yellow',
        'purple': 'purple',
        'gray': 'gray'
    };
    return colors[color] || 'gray';
}

function formatTime(timestamp) {
    const date = new Date(timestamp);
    const now = new Date();
    const diff = now - date;

    if (diff < 60000) { // Less than 1 minute
        return 'Just now';
    } else if (diff < 3600000) { // Less than 1 hour
        return Math.floor(diff / 60000) + 'm ago';
    } else if (diff < 86400000) { // Less than 1 day
        return Math.floor(diff / 3600000) + 'h ago';
    } else if (diff < 604800000) { // Less than 1 week
        return Math.floor(diff / 86400000) + 'd ago';
    } else {
        return date.toLocaleDateString();
    }
}

// Handle page visibility changes
document.addEventListener('visibilitychange', function() {
    if (!document.hidden) {
        // Page became visible, refresh data
        loadDashboardData();
    }
});

async function markAllMessagesRead() {
    const markReadBtn = document.getElementById('markReadBtn');

    // Show loading state
    markReadBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i>Marking...';
    markReadBtn.disabled = true;

    try {
        const response = await fetch('/api/customerdashboard_api.php?action=mark_messages_read');
        const data = await response.json();

        if (data.success) {
            // Update the unread messages count to 0
            document.getElementById('unreadMessages').textContent = '0';

            // Hide the button since there are no more unread messages
            markReadBtn.style.display = 'none';

            // Trigger notification update event for header
            document.dispatchEvent(new CustomEvent('notificationsUpdated'));

            // Show success message
            showNotification('All messages marked as read!', 'success');
        } else {
            showNotification('Error marking messages as read: ' + data.error, 'error');
        }
    } catch (error) {
        console.error('Error marking messages as read:', error);
        showNotification('Error marking messages as read. Please try again.', 'error');
    } finally {
        // Reset button state
        markReadBtn.innerHTML = '<i class="fas fa-check-double mr-1"></i>Mark all read';
        markReadBtn.disabled = false;
    }
}

function showNotification(message, type = 'info') {
    // Create notification element
    const notification = document.createElement('div');
    notification.className = `fixed top-4 right-4 z-50 px-6 py-3 rounded-lg shadow-lg transition-all duration-300 ${
        type === 'success' ? 'bg-green-500 text-white' :
        type === 'error' ? 'bg-red-500 text-white' :
        'bg-blue-500 text-white'
    }`;

    notification.innerHTML = `
        <div class="flex items-center">
            <i class="fas ${type === 'success' ? 'fa-check-circle' : type === 'error' ? 'fa-exclamation-circle' : 'fa-info-circle'} mr-2"></i>
            <span>${message}</span>
        </div>
    `;

    // Add to page
    document.body.appendChild(notification);

    // Auto remove after 3 seconds
    setTimeout(() => {
        notification.style.opacity = '0';
        notification.style.transform = 'translateX(100%)';
        setTimeout(() => {
            if (notification.parentNode) {
                notification.parentNode.removeChild(notification);
            }
        }, 300);
    }, 3000);
}
</script>

<?php include '../includes/footer.php'; ?>
