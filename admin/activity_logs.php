<?php
// Start session and check admin authentication
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include database connection
require_once '../includes/config.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}

// Include activity logger
require_once '../includes/ActivityLogger.php';
$activityLogger = new ActivityLogger($pdo);

// Get filter parameters
$activityType = $_GET['type'] ?? 'all';
$dateFrom = $_GET['from'] ?? '';
$dateTo = $_GET['to'] ?? '';
$search = $_GET['search'] ?? '';

// Set page title
$pageTitle = 'Activity Logs';

// Include header
require_once 'includes/header.php';
?>

<!-- Dashboard Header -->
<div class="bg-gradient-to-br from-primary via-primary-dark to-secondary text-white">
    <div class="container mx-auto px-6 py-8">
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between">
            <div>
                <h1 class="text-3xl md:text-4xl font-bold mb-2">Activity Logs</h1>
                <p class="text-lg opacity-90">Complete audit trail of all administrative activities</p>
            </div>
            <div class="mt-4 lg:mt-0">
                <a href="dashboard.php"
                   class="bg-white/10 hover:bg-white/20 text-white px-6 py-3 rounded-lg font-semibold transition-all duration-200 flex items-center backdrop-blur-sm border border-white/20">
                    <i class="fas fa-arrow-left mr-2"></i>
                    Back to Dashboard
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Main Content -->
<div class="container mx-auto px-6 py-8">
    <!-- Filters Section -->
    <div class="bg-white rounded-xl shadow-lg p-6 mb-8">
        <form method="GET" class="space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                <!-- Activity Type Filter -->
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Activity Type</label>
                    <select name="type" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent transition-colors">
                        <option value="all" <?php echo $activityType === 'all' ? 'selected' : ''; ?>>All Activities</option>
                        <option value="order" <?php echo $activityType === 'order' ? 'selected' : ''; ?>>Orders</option>
                        <option value="menu" <?php echo $activityType === 'menu' ? 'selected' : ''; ?>>Menu Items</option>
                        <option value="user" <?php echo $activityType === 'user' ? 'selected' : ''; ?>>Users</option>
                        <option value="system" <?php echo $activityType === 'system' ? 'selected' : ''; ?>>System</option>
                        <option value="login" <?php echo $activityType === 'login' ? 'selected' : ''; ?>>Logins</option>
                    </select>
                </div>

                <!-- Date From Filter -->
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">From Date</label>
                    <input type="date" name="from" value="<?php echo htmlspecialchars($dateFrom); ?>"
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent transition-colors">
                </div>

                <!-- Date To Filter -->
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">To Date</label>
                    <input type="date" name="to" value="<?php echo htmlspecialchars($dateTo); ?>"
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent transition-colors">
                </div>

                <!-- Search Filter -->
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Search</label>
                    <div class="relative">
                        <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>"
                               placeholder="Search activities..."
                               class="w-full pl-12 pr-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent transition-colors">
                        <i class="fas fa-search absolute left-4 top-1/2 transform -translate-y-1/2 text-gray-400"></i>
                    </div>
                </div>
            </div>

            <div class="flex justify-end space-x-3">
                <a href="activity_logs.php" class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition-colors duration-200">
                    <i class="fas fa-times mr-2"></i>Clear Filters
                </a>
                <button type="submit" class="bg-primary hover:bg-primary-dark text-white px-6 py-2 rounded-lg font-semibold transition-colors duration-200 flex items-center">
                    <i class="fas fa-filter mr-2"></i>
                    Apply Filters
                </button>
            </div>
        </form>
    </div>

    <!-- Activity Logs Table -->
    <div class="bg-white shadow overflow-hidden sm:rounded-lg">
        <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-medium text-gray-900">All Activities</h3>
            <p class="mt-1 text-sm text-gray-600">Complete audit trail of administrative activities</p>
        </div>

        <?php
        // Build query based on filters
        $whereConditions = [];
        $params = [];

        if ($activityType !== 'all') {
            $whereConditions[] = 'activity_type = ?';
            $params[] = $activityType;
        }

        if (!empty($dateFrom)) {
            $whereConditions[] = 'created_at >= ?';
            $params[] = $dateFrom . ' 00:00:00';
        }

        if (!empty($dateTo)) {
            $whereConditions[] = 'created_at <= ?';
            $params[] = $dateTo . ' 23:59:59';
        }

        if (!empty($search)) {
            $whereConditions[] = '(description LIKE ? OR user_name LIKE ?)';
            $searchParam = '%' . $search . '%';
            $params[] = $searchParam;
            $params[] = $searchParam;
        }

        $whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';

        // Get total count for pagination
        $countStmt = $pdo->prepare("SELECT COUNT(*) as total FROM activity_logs al LEFT JOIN users u ON al.user_id = u.id {$whereClause}");
        $countStmt->execute($params);
        $totalActivities = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];

        // Get activities with pagination
        $page = $_GET['page'] ?? 1;
        $perPage = 20;
        $offset = ($page - 1) * $perPage;

        $activitiesStmt = $pdo->prepare("
            SELECT al.*, u.name as user_name
            FROM activity_logs al
            LEFT JOIN users u ON al.user_id = u.id
            {$whereClause}
            ORDER BY al.created_at DESC
            LIMIT {$perPage} OFFSET {$offset}
        ");
        $activitiesStmt->execute($params);
        $activities = $activitiesStmt->fetchAll(PDO::FETCH_ASSOC);
        ?>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Activity</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">User</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Entity</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">IP Address</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date/Time</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php if (!empty($activities)): ?>
                        <?php foreach ($activities as $activity): ?>
                            <?php
                            $activityIcon = $activityLogger->getActivityIcon($activity['activity_type'], $activity['activity_action']);
                            $activityColor = $activityLogger->getActivityColor($activity['activity_type']);
                            ?>
                            <tr class="hover:bg-gray-50 transition-colors duration-200">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div class="w-8 h-8 bg-<?php echo $activityColor; ?>-100 rounded-full flex items-center justify-center mr-3">
                                            <i class="<?php echo $activityIcon; ?> text-<?php echo $activityColor; ?>-600 text-sm"></i>
                                        </div>
                                        <div>
                                            <div class="text-sm font-medium text-gray-900">
                                                <?php echo htmlspecialchars($activity['description']); ?>
                                            </div>
                                            <div class="text-xs text-gray-500 capitalize">
                                                <?php echo $activity['activity_type']; ?> • <?php echo $activity['activity_action']; ?>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    <?php echo htmlspecialchars($activity['user_name'] ?? 'System'); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <?php if ($activity['entity_type'] && $activity['entity_id']): ?>
                                        <span class="px-2 py-1 text-xs font-medium rounded-full bg-<?php echo $activityColor; ?>-100 text-<?php echo $activityColor; ?>-800">
                                            <?php echo ucfirst($activity['entity_type']); ?> #<?php echo $activity['entity_id']; ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="text-gray-400">-</span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <?php echo htmlspecialchars($activity['ip_address'] ?? '-'); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <?php echo date('M j, Y', strtotime($activity['created_at'])); ?>
                                    <div class="text-xs text-gray-400"><?php echo date('g:i A', strtotime($activity['created_at'])); ?></div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" class="px-6 py-4 text-center text-sm text-gray-500">
                                <i class="fas fa-history text-4xl text-gray-300 mb-3"></i>
                                <p>No activities found matching your criteria</p>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <?php if ($totalActivities > $perPage): ?>
            <div class="bg-white px-4 py-3 flex items-center justify-between border-t border-gray-200 sm:px-6">
                <div class="flex-1 flex justify-between sm:hidden">
                    <?php if ($page > 1): ?>
                        <a href="?page=<?php echo $page - 1; ?><?php echo !empty($_GET) ? '&' . http_build_query(array_filter($_GET, function($k) { return $k !== 'page'; })) : ''; ?>"
                           class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                            Previous
                        </a>
                    <?php endif; ?>

                    <?php if ($page * $perPage < $totalActivities): ?>
                        <a href="?page=<?php echo $page + 1; ?><?php echo !empty($_GET) ? '&' . http_build_query(array_filter($_GET, function($k) { return $k !== 'page'; })) : ''; ?>"
                           class="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                            Next
                        </a>
                    <?php endif; ?>
                </div>

                <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
                    <div>
                        <p class="text-sm text-gray-700">
                            Showing
                            <span class="font-medium"><?php echo $offset + 1; ?></span>
                            to
                            <span class="font-medium"><?php echo min($offset + $perPage, $totalActivities); ?></span>
                            of
                            <span class="font-medium"><?php echo $totalActivities; ?></span>
                            results
                        </p>
                    </div>

                    <div>
                        <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px" aria-label="Pagination">
                            <?php if ($page > 1): ?>
                                <a href="?page=<?php echo $page - 1; ?><?php echo !empty($_GET) ? '&' . http_build_query(array_filter($_GET, function($k) { return $k !== 'page'; })) : ''; ?>"
                                   class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
                                    <span class="sr-only">Previous</span>
                                    <i class="fas fa-chevron-left"></i>
                                </a>
                            <?php endif; ?>

                            <?php
                            $totalPages = ceil($totalActivities / $perPage);
                            $startPage = max(1, $page - 2);
                            $endPage = min($totalPages, $page + 2);

                            for ($i = $startPage; $i <= $endPage; $i++):
                            ?>
                                <a href="?page=<?php echo $i; ?><?php echo !empty($_GET) ? '&' . http_build_query(array_filter($_GET, function($k) { return $k !== 'page'; })) : ''; ?>"
                                   class="relative inline-flex items-center px-4 py-2 border <?php echo $i === $page ? 'bg-primary text-white' : 'bg-white text-gray-700'; ?> text-sm font-medium hover:bg-gray-50">
                                    <?php echo $i; ?>
                                </a>
                            <?php endfor; ?>

                            <?php if ($page * $perPage < $totalActivities): ?>
                                <a href="?page=<?php echo $page + 1; ?><?php echo !empty($_GET) ? '&' . http_build_query(array_filter($_GET, function($k) { return $k !== 'page'; })) : ''; ?>"
                                   class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
                                    <span class="sr-only">Next</span>
                                    <i class="fas fa-chevron-right"></i>
                                </a>
                            <?php endif; ?>
                        </nav>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<style>
/* Activity table enhancements */
tbody tr {
    transition: background-color 0.2s ease;
}

tbody tr:hover {
    background-color: #f9fafb;
}

/* Custom scrollbar for activity table */
.overflow-x-auto::-webkit-scrollbar {
    height: 6px;
}

.overflow-x-auto::-webkit-scrollbar-track {
    background: #f1f5f9;
    border-radius: 3px;
}

.overflow-x-auto::-webkit-scrollbar-thumb {
    background: #cbd5e1;
    border-radius: 3px;
}

.overflow-x-auto::-webkit-scrollbar-thumb:hover {
    background: #94a3b8;
}

/* Pagination styling */
.pagination .page-link {
    @apply px-3 py-2 border border-gray-300 text-sm font-medium rounded-md;
}

.pagination .page-link.active {
    @apply bg-primary text-white;
}
</style>

<?php
// Include footer
require_once 'includes/footer.php';
?>
