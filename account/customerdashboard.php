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
                <a href="/account/activity.php" class="text-primary hover:underline text-sm">View All</a>
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

        <!-- Quick Actions -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
            <a href="/menu.php" class="bg-white rounded-lg shadow p-4 text-center hover:shadow-md transition-shadow">
                <div class="text-primary text-2xl mb-2"><i class="fas fa-utensils"></i></div>
                <p class="text-sm font-medium">Order Food</p>
            </a>
            <a href="/account/orders.php" class="bg-white rounded-lg shadow p-4 text-center hover:shadow-md transition-shadow">
                <div class="text-primary text-2xl mb-2"><i class="fas fa-history"></i></div>
                <p class="text-sm font-medium">Order History</p>
            </a>
            <a href="/account/addresses.php" class="bg-white rounded-lg shadow p-4 text-center hover:shadow-md transition-shadow">
                <div class="text-primary text-2xl mb-2"><i class="fas fa-map-marker-alt"></i></div>
                <p class="text-sm font-medium">My Addresses</p>
            </a>
            <a href="/chat.php" class="bg-white rounded-lg shadow p-4 text-center hover:shadow-md transition-shadow">
                <div class="text-primary text-2xl mb-2"><i class="fas fa-headset"></i></div>
                <p class="text-sm font-medium">Support</p>
            </a>
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
