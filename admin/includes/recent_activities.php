<?php
/**
 * Recent Activities Component
 * Displays comprehensive activity feed for admin dashboard
 */

// Include activity logger
require_once '../includes/ActivityLogger.php';

$activityLogger = new ActivityLogger($pdo);

// Get recent activities
$recentActivities = $activityLogger->getRecentActivities(8);

// Create sample activities if none exist
if (empty($recentActivities)) {
    try {
        // Log some sample activities for demonstration
        $activityLogger->log('system', 'login', 'Admin user logged in', 'user', $_SESSION['user_id'] ?? null);
        $activityLogger->log('menu', 'created', 'Added new menu item: Jollof Rice', 'menu_item', 1);
        $activityLogger->log('order', 'created', 'New order placed for Ugali & Sukuma', 'order', 1);
        $activityLogger->log('user', 'created', 'New customer registered: Barrack Oluoch', 'user', 3);

        // Get the activities we just created
        $recentActivities = $activityLogger->getRecentActivities(8);
    } catch (Exception $e) {
        error_log("Error creating sample activities: " . $e->getMessage());
    }
}
?>

<!-- Recent Activities Section -->
<div class="bg-white rounded-xl shadow-lg p-6">
    <div class="flex items-center justify-between mb-6">
        <h3 class="text-xl font-bold text-gray-900 flex items-center">
            <i class="fas fa-history mr-3 text-primary"></i>
            Recent Activities
        </h3>
        <div class="flex space-x-2">
            <select id="activityFilter" class="text-sm border border-gray-300 rounded-lg px-3 py-1 focus:ring-2 focus:ring-primary focus:border-transparent">
                <option value="all">All Activities</option>
                <option value="order">Orders</option>
                <option value="menu">Menu Items</option>
                <option value="user">Users</option>
                <option value="system">System</option>
            </select>
        </div>
    </div>

    <?php if (!empty($recentActivities)): ?>
        <div class="space-y-4 max-h-96 overflow-y-auto">
            <?php foreach ($recentActivities as $activity): ?>
                <?php
                $activityIcon = $activityLogger->getActivityIcon($activity['activity_type'], $activity['activity_action']);
                $activityColor = $activityLogger->getActivityColor($activity['activity_type']);
                $timeAgo = strtotime($activity['created_at']);
                $currentTime = time();
                $diff = $currentTime - $timeAgo;

                if ($diff < 60) {
                    $timeDisplay = 'Just now';
                } elseif ($diff < 3600) {
                    $timeDisplay = floor($diff / 60) . ' minutes ago';
                } elseif ($diff < 86400) {
                    $timeDisplay = floor($diff / 3600) . ' hours ago';
                } else {
                    $timeDisplay = floor($diff / 86400) . ' days ago';
                }
                ?>
                <div class="flex items-start space-x-4 p-3 rounded-lg hover:bg-gray-50 transition-colors duration-200 border-l-4 border-<?php echo $activityColor; ?>-400 activity-item"
                     data-type="<?php echo $activity['activity_type']; ?>"
                     data-action="<?php echo $activity['activity_action']; ?>">
                    <div class="flex-shrink-0">
                        <div class="w-10 h-10 bg-<?php echo $activityColor; ?>-100 rounded-full flex items-center justify-center">
                            <i class="<?php echo $activityIcon; ?> text-<?php echo $activityColor; ?>-600"></i>
                        </div>
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center justify-between">
                            <p class="text-sm font-medium text-gray-900">
                                <?php echo htmlspecialchars($activity['description']); ?>
                            </p>
                            <span class="text-xs text-gray-500"><?php echo $timeDisplay; ?></span>
                        </div>
                        <?php if ($activity['user_name']): ?>
                            <p class="text-xs text-gray-500 mt-1">
                                by <?php echo htmlspecialchars($activity['user_name']); ?>
                            </p>
                        <?php endif; ?>
                        <?php if ($activity['entity_type'] && $activity['entity_id']): ?>
                            <div class="flex items-center mt-2">
                                <span class="px-2 py-1 text-xs font-medium rounded-full bg-<?php echo $activityColor; ?>-100 text-<?php echo $activityColor; ?>-800">
                                    <?php echo ucfirst($activity['entity_type']); ?> #<?php echo $activity['entity_id']; ?>
                                </span>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="mt-6 pt-4 border-t border-gray-200">
            <a href="activity_logs.php" class="text-primary hover:text-primary-dark font-medium text-sm flex items-center justify-center">
                <i class="fas fa-list mr-2"></i>
                View All Activities
            </a>
        </div>
    <?php else: ?>
        <div class="text-center py-8">
            <i class="fas fa-history text-4xl text-gray-300 mb-3"></i>
            <p class="text-gray-500">No recent activities found.</p>
            <p class="text-sm text-gray-400 mt-2">Activities will appear here as you use the system.</p>
        </div>
    <?php endif; ?>
</div>

<script>
// Activity filtering functionality
document.addEventListener('DOMContentLoaded', function() {
    const filterSelect = document.getElementById('activityFilter');
    const activityItems = document.querySelectorAll('.activity-item');

    if (filterSelect) {
        filterSelect.addEventListener('change', function() {
            const filterValue = this.value;

            activityItems.forEach(item => {
                const activityType = item.dataset.type;

                if (filterValue === 'all' || activityType === filterValue) {
                    item.style.display = 'flex';
                } else {
                    item.style.display = 'none';
                }
            });
        });
    }
});
</script>
