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

// Initialize variables
$error = '';
$success = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['delete_request'])) {
        $request_id = (int)$_POST['request_id'];

        try {
            $stmt = $pdo->prepare("DELETE FROM catering_requests WHERE id = ?");
            $stmt->execute([$request_id]);

            // Log activity
            $activityLogger->log('catering', 'deleted', "Deleted catering request", 'catering_request', $request_id);

            $_SESSION['message'] = [
                'type' => 'success',
                'text' => 'Catering request deleted successfully!'
            ];

            header('Location: ' . $_SERVER['PHP_SELF'] . '?' . http_build_query($_GET));
            exit();
        } catch (PDOException $e) {
            $error = 'Error deleting catering request: ' . $e->getMessage();
        }
    }
}

// Get filter parameters
$search = $_GET['search'] ?? '';
$date_from = $_GET['date_from'] ?? '';
$date_to = $_GET['date_to'] ?? '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 10;
$offset = ($page - 1) * $per_page;

// Build where clause
$where_conditions = [];
$params = [];

if (!empty($search)) {
    $where_conditions[] = "(cr.name LIKE :search OR cr.email LIKE :search OR cr.phone LIKE :search OR cr.message LIKE :search)";
    $params[':search'] = "%$search%";
}

if (!empty($date_from)) {
    $where_conditions[] = "DATE(cr.event_date) >= :date_from";
    $params[':date_from'] = $date_from;
}

if (!empty($date_to)) {
    $where_conditions[] = "DATE(cr.event_date) <= :date_to";
    $params[':date_to'] = $date_to;
}

$where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

// Get total count for pagination
$total_sql = "SELECT COUNT(*) as count FROM catering_requests cr $where_clause";
$total_stmt = $pdo->prepare($total_sql);
$total_stmt->execute($params);
$total_requests = (int)$total_stmt->fetch(PDO::FETCH_ASSOC)['count'];

// Get catering requests with pagination
$sql = "SELECT cr.* FROM catering_requests cr $where_clause ORDER BY cr.event_date ASC, cr.created_at DESC LIMIT $offset, $per_page";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$catering_requests = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get pagination info
$total_pages = ceil($total_requests / $per_page);

// Get stats for header
$stats = [
    'upcoming_7_days' => 0,
    'today' => 0,
    'this_week' => 0
];

try {
    // Upcoming events (next 7 days)
    $upcoming_date = date('Y-m-d', strtotime('+7 days'));
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM catering_requests WHERE event_date BETWEEN CURDATE() AND ?");
    $stmt->execute([$upcoming_date]);
    $stats['upcoming_7_days'] = (int)$stmt->fetchColumn();

    // Today's events
    $stmt = $pdo->query("SELECT COUNT(*) FROM catering_requests WHERE DATE(event_date) = CURDATE()");
    $stats['today'] = (int)$stmt->fetchColumn();

    // New this week
    $stmt = $pdo->query("SELECT COUNT(*) FROM catering_requests WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)");
    $stats['this_week'] = (int)$stmt->fetchColumn();
} catch (PDOException $e) {
    // Silent fail for stats
}

// Set page title
$page_title = 'Catering Requests';
$page_description = 'Manage catering requests and events';

// Include header
require_once 'includes/header.php';
?>

<!-- Catering Header -->
<div class="bg-gradient-to-br from-primary via-primary-dark to-secondary text-white">
    <div class="container mx-auto px-6 py-12">
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between">
            <div class="flex items-center space-x-6">
                <div class="h-20 w-20 rounded-full bg-white/20 backdrop-blur-sm flex items-center justify-center text-white font-bold text-3xl shadow-lg">
                    <i class="fas fa-utensils"></i>
                </div>
                <div>
                    <h1 class="text-3xl md:text-4xl font-bold mb-2">Catering Requests</h1>
                    <p class="text-lg opacity-90 mb-2">Manage catering requests and events</p>
                    <div class="flex items-center space-x-4">
                        <span class="px-3 py-1 bg-white/20 backdrop-blur-sm rounded-full text-sm font-medium">
                            <?php echo $total_requests; ?> Total Requests
                        </span>
                        <span class="px-3 py-1 bg-white/20 backdrop-blur-sm rounded-full text-sm font-medium">
                            <?php echo count($catering_requests); ?> This Page
                        </span>
                    </div>
                </div>
            </div>
            <div class="mt-6 lg:mt-0">
                <div class="flex items-center space-x-4 text-sm">
                    <div class="flex items-center space-x-2 bg-white/10 backdrop-blur-sm rounded-lg px-3 py-2">
                        <i class="fas fa-chart-line text-blue-300"></i>
                        <span>Updated: <?php echo date('M j, g:i A'); ?></span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Main Content -->
<div class="container mx-auto px-6 py-8">
    <!-- Alerts -->
    <?php if ($error): ?>
        <div class="mb-6 p-4 bg-red-50 border-l-4 border-red-400 text-red-700 rounded-lg flex items-center">
            <i class="fas fa-exclamation-circle mr-3"></i>
            <div>
                <p class="font-semibold">Error</p>
                <p><?php echo htmlspecialchars($error); ?></p>
            </div>
            <button onclick="this.parentElement.remove()" class="ml-auto text-gray-400 hover:text-gray-600">
                <i class="fas fa-times"></i>
            </button>
        </div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="mb-6 p-4 bg-green-50 border-l-4 border-green-400 text-green-700 rounded-lg flex items-center">
            <i class="fas fa-check-circle mr-3"></i>
            <div>
                <p class="font-semibold">Success</p>
                <p><?php echo htmlspecialchars($success); ?></p>
            </div>
            <button onclick="this.parentElement.remove()" class="ml-auto text-gray-400 hover:text-gray-600">
                <i class="fas fa-times"></i>
            </button>
        </div>
    <?php endif; ?>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <!-- Total Requests -->
        <div class="bg-white rounded-xl shadow-lg p-6">
            <div class="flex items-center">
                <div class="flex-shrink-0 bg-primary rounded-lg p-3">
                    <i class="fas fa-utensils text-white text-xl"></i>
                </div>
                <div class="ml-4 flex-1">
                    <div class="text-sm font-medium text-gray-500">Total Requests</div>
                    <div class="text-2xl font-bold text-gray-900"><?php echo number_format($total_requests); ?></div>
                </div>
            </div>
        </div>

        <!-- Today's Events -->
        <div class="bg-white rounded-xl shadow-lg p-6">
            <div class="flex items-center">
                <div class="flex-shrink-0 bg-green-500 rounded-lg p-3">
                    <i class="fas fa-calendar-day text-white text-xl"></i>
                </div>
                <div class="ml-4 flex-1">
                    <div class="text-sm font-medium text-gray-500">Today's Events</div>
                    <div class="text-2xl font-bold text-gray-900"><?php echo number_format($stats['today']); ?></div>
                </div>
            </div>
        </div>

        <!-- Upcoming Events -->
        <div class="bg-white rounded-xl shadow-lg p-6">
            <div class="flex items-center">
                <div class="flex-shrink-0 bg-blue-500 rounded-lg p-3">
                    <i class="fas fa-calendar-alt text-white text-xl"></i>
                </div>
                <div class="ml-4 flex-1">
                    <div class="text-sm font-medium text-gray-500">Upcoming (7 days)</div>
                    <div class="text-2xl font-bold text-gray-900"><?php echo number_format($stats['upcoming_7_days']); ?></div>
                </div>
            </div>
        </div>

        <!-- New This Week -->
        <div class="bg-white rounded-xl shadow-lg p-6">
            <div class="flex items-center">
                <div class="flex-shrink-0 bg-yellow-500 rounded-lg p-3">
                    <i class="fas fa-star text-white text-xl"></i>
                </div>
                <div class="ml-4 flex-1">
                    <div class="text-sm font-medium text-gray-500">New This Week</div>
                    <div class="text-2xl font-bold text-gray-900"><?php echo number_format($stats['this_week']); ?></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters and Search -->
    <div class="bg-white rounded-xl shadow-lg p-6 mb-8">
        <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-6">
            <div>
                <label for="search" class="block text-sm font-semibold text-gray-700 mb-2">Search</label>
                <input type="text" name="search" id="search"
                       value="<?php echo htmlspecialchars($search); ?>"
                       class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent"
                       placeholder="Search by name, email, or message...">
            </div>

            <div>
                <label for="date_from" class="block text-sm font-semibold text-gray-700 mb-2">From Date</label>
                <input type="date" name="date_from" id="date_from"
                       value="<?php echo htmlspecialchars($date_from); ?>"
                       class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
            </div>

            <div>
                <label for="date_to" class="block text-sm font-semibold text-gray-700 mb-2">To Date</label>
                <input type="date" name="date_to" id="date_to"
                       value="<?php echo htmlspecialchars($date_to); ?>"
                       class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
            </div>

            <div class="flex items-end space-x-4">
                <button type="submit" class="bg-primary hover:bg-primary-dark text-white px-6 py-3 rounded-lg font-semibold transition-colors duration-200 flex items-center">
                    <i class="fas fa-search mr-2"></i>
                    Search
                </button>
                <a href="catering.php" class="bg-gray-500 hover:bg-gray-600 text-white px-6 py-3 rounded-lg font-semibold transition-colors duration-200">
                    <i class="fas fa-times mr-2"></i>
                    Clear
                </a>
            </div>
        </form>
    </div>

    <!-- Catering Requests List -->
    <div class="bg-white rounded-xl shadow-lg overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200">
            <h2 class="text-lg font-semibold text-gray-900">Catering Requests (<?php echo $total_requests; ?>)</h2>
        </div>

        <?php if (empty($catering_requests)): ?>
            <div class="p-12 text-center">
                <i class="fas fa-utensils text-4xl text-gray-400 mb-4"></i>
                <h3 class="text-lg font-medium text-gray-900 mb-2">No catering requests found</h3>
                <p class="text-gray-500 mb-4">No catering requests match your current filters.</p>
                <a href="../catering-request.php" target="_blank" class="bg-primary hover:bg-primary-dark text-white px-6 py-3 rounded-lg font-semibold transition-colors duration-200 flex items-center mx-auto w-fit">
                    <i class="fas fa-external-link-alt mr-2"></i>
                    View Request Form
                </a>
            </div>
        <?php else: ?>
            <div class="divide-y divide-gray-200">
                <?php foreach ($catering_requests as $request): ?>
                    <div class="p-6 hover:bg-gray-50 transition-colors duration-200">
                        <div class="flex items-start justify-between">
                            <div class="flex items-start space-x-4 flex-1">
                                <div class="flex-shrink-0">
                                    <div class="h-12 w-12 rounded-full bg-primary flex items-center justify-center text-white font-semibold text-lg">
                                        <?php echo strtoupper(substr($request['name'], 0, 1)); ?>
                                    </div>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center space-x-2 mb-2">
                                        <h3 class="text-lg font-semibold text-gray-900">
                                            <?php echo htmlspecialchars($request['name']); ?>
                                        </h3>
                                        <?php
                                        $event_date = new DateTime($request['event_date']);
                                        $today = new DateTime();
                                        $interval = $today->diff($event_date);
                                        $days = $interval->format('%a');

                                        if ($event_date < $today) {
                                            $status_class = 'bg-red-100 text-red-800';
                                            $status_text = $days . ' days ago';
                                        } elseif ($days == 0) {
                                            $status_class = 'bg-green-100 text-green-800';
                                            $status_text = 'Today';
                                        } elseif ($days == 1) {
                                            $status_class = 'bg-green-100 text-green-800';
                                            $status_text = 'Tomorrow';
                                        } else {
                                            $status_class = 'bg-blue-100 text-blue-800';
                                            $status_text = 'In ' . $days . ' days';
                                        }
                                        ?>
                                        <span class="px-2.5 py-0.5 rounded-full text-xs font-medium <?php echo $status_class; ?>">
                                            <?php echo $status_text; ?>
                                        </span>
                                    </div>
                                    <div class="text-sm text-gray-600 mb-3">
                                        <span class="font-medium"><?php echo htmlspecialchars($request['email']); ?></span>
                                        <?php if (!empty($request['phone'])): ?>
                                            <span class="text-gray-400 mx-2">•</span>
                                            <span><?php echo htmlspecialchars($request['phone']); ?></span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="text-sm text-gray-500 mb-3">
                                        Event: <?php echo date('M j, Y', strtotime($request['event_date'])); ?> • Requested: <?php echo date('M j, Y g:i A', strtotime($request['created_at'])); ?>
                                    </div>
                                    <?php if (!empty($request['message'])): ?>
                                        <div class="bg-gray-50 rounded-lg p-4">
                                            <p class="text-gray-700 line-clamp-3">
                                                <?php echo htmlspecialchars(substr($request['message'], 0, 200) . '...'); ?>
                                            </p>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <div class="flex items-center space-x-2 ml-4">
                                <button onclick="viewRequest(<?php echo $request['id']; ?>)"
                                        class="text-indigo-600 hover:text-indigo-900 p-2 rounded-lg transition-colors duration-200"
                                        title="View Details">
                                    <i class="fas fa-eye"></i>
                                </button>

                                <a href="mailto:<?php echo htmlspecialchars($request['email']); ?>"
                                   class="text-blue-600 hover:text-blue-900 p-2 rounded-lg transition-colors duration-200"
                                   title="Send Email">
                                    <i class="fas fa-envelope"></i>
                                </a>

                                <?php if (!empty($request['phone'])): ?>
                                    <a href="tel:<?php echo preg_replace('/[^0-9+]/', '', $request['phone']); ?>"
                                       class="text-green-600 hover:text-green-900 p-2 rounded-lg transition-colors duration-200"
                                       title="Call">
                                        <i class="fas fa-phone"></i>
                                    </a>
                                <?php endif; ?>

                                <form method="POST" class="inline" onsubmit="return confirm('Are you sure you want to delete this request? This action cannot be undone.');">
                                    <input type="hidden" name="request_id" value="<?php echo $request['id']; ?>">
                                    <button type="submit" name="delete_request"
                                            class="text-red-600 hover:text-red-900 p-2 rounded-lg transition-colors duration-200"
                                            title="Delete Request">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
                <div class="px-6 py-4 border-t border-gray-200 bg-gray-50">
                    <div class="flex items-center justify-between">
                        <div class="text-sm text-gray-700">
                            Showing <?php echo (($page - 1) * $per_page) + 1; ?> to <?php echo min($page * $per_page, $total_requests); ?> of <?php echo $total_requests; ?> results
                        </div>
                        <div class="flex space-x-1">
                            <?php if ($page > 1): ?>
                                <a href="?page=<?php echo $page - 1; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo !empty($date_from) ? '&date_from=' . urlencode($date_from) : ''; ?><?php echo !empty($date_to) ? '&date_to=' . urlencode($date_to) : ''; ?>"
                                   class="px-3 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                                    Previous
                                </a>
                            <?php endif; ?>

                            <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                                <a href="?page=<?php echo $i; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo !empty($date_from) ? '&date_from=' . urlencode($date_from) : ''; ?><?php echo !empty($date_to) ? '&date_to=' . urlencode($date_to) : ''; ?>"
                                   class="px-3 py-2 border <?php echo $i === $page ? 'bg-primary text-white' : 'border-gray-300 text-gray-700 bg-white hover:bg-gray-50'; ?> rounded-md text-sm font-medium">
                                    <?php echo $i; ?>
                                </a>
                            <?php endfor; ?>

                            <?php if ($page < $total_pages): ?>
                                <a href="?page=<?php echo $page + 1; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo !empty($date_from) ? '&date_from=' . urlencode($date_from) : ''; ?><?php echo !empty($date_to) ? '&date_to=' . urlencode($date_to) : ''; ?>"
                                   class="px-3 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                                    Next
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<!-- View Request Modal -->
<div id="requestModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden z-50">
    <div class="flex items-center justify-center min-h-screen p-4">
        <div class="bg-white rounded-lg shadow-xl max-w-4xl w-full max-h-screen overflow-y-auto">
            <div class="px-6 py-4 border-b border-gray-200">
                <div class="flex items-center justify-between">
                    <h3 class="text-lg font-semibold text-gray-900">Catering Request Details</h3>
                    <button onclick="closeRequestModal()" class="text-gray-400 hover:text-gray-600">
                        <i class="fas fa-times text-xl"></i>
                    </button>
                </div>
            </div>
            <div id="modalContent" class="p-6">
                <!-- Content will be loaded here -->
            </div>
        </div>
    </div>
</div>

<style>
/* Catering page enhancements */
.request-card {
    transition: all 0.2s ease;
}

.request-card:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

/* Status badge styling */
.status-badge {
    @apply px-2.5 py-0.5 rounded-full text-xs font-medium;
}

.status-today {
    @apply bg-green-100 text-green-800;
}

.status-tomorrow {
    @apply bg-green-100 text-green-800;
}

.status-future {
    @apply bg-blue-100 text-blue-800;
}

.status-past {
    @apply bg-red-100 text-red-800;
}

/* Stats card hover effects */
.stats-card {
    transition: all 0.2s ease;
}

.stats-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
}

/* Form focus enhancements */
input:focus, textarea:focus, select:focus {
    box-shadow: 0 0 0 3px rgba(193, 39, 45, 0.1);
}

/* Content preview styling */
.line-clamp-3 {
    display: -webkit-box;
    -webkit-line-clamp: 3;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

/* Action button hover effects */
.action-btn {
    transition: all 0.2s ease;
}

.action-btn:hover {
    transform: scale(1.1);
}
</style>

<script>
// Store all catering requests data for the modal
const cateringRequests = <?= json_encode($catering_requests) ?>;

function viewRequest(requestId) {
    const request = cateringRequests.find(r => r.id == requestId);
    if (!request) return;

    // Create modal content
    const modalContent = document.getElementById('modalContent');
    modalContent.innerHTML = `
        <div class="space-y-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="bg-gray-50 rounded-lg p-4">
                    <h4 class="text-lg font-semibold text-gray-900 mb-4">Contact Information</h4>
                    <div class="space-y-3">
                        <div>
                            <label class="block text-sm font-medium text-gray-500">Name</label>
                            <p class="text-gray-900 font-medium">${request.name}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-500">Email</label>
                            <a href="mailto:${request.email}" class="text-primary hover:text-primary-dark">${request.email}</a>
                        </div>
                        ${request.phone ? `
                        <div>
                            <label class="block text-sm font-medium text-gray-500">Phone</label>
                            <a href="tel:${request.phone.replace(/[^0-9+]/g, '')}" class="text-primary hover:text-primary-dark">${request.phone}</a>
                        </div>
                        ` : ''}
                    </div>
                </div>

                <div class="bg-gray-50 rounded-lg p-4">
                    <h4 class="text-lg font-semibold text-gray-900 mb-4">Event Details</h4>
                    <div class="space-y-3">
                        <div>
                            <label class="block text-sm font-medium text-gray-500">Event Date</label>
                            <p class="text-gray-900 font-medium">${new Date(request.event_date).toLocaleDateString('en-US', {
                                weekday: 'long',
                                year: 'numeric',
                                month: 'long',
                                day: 'numeric'
                            })}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-500">Requested On</label>
                            <p class="text-gray-900">${new Date(request.created_at).toLocaleString('en-US', {
                                year: 'numeric',
                                month: 'long',
                                day: 'numeric',
                                hour: '2-digit',
                                minute: '2-digit'
                            })}</p>
                        </div>
                    </div>
                </div>
            </div>

            ${request.message ? `
            <div class="bg-gray-50 rounded-lg p-4">
                <h4 class="text-lg font-semibold text-gray-900 mb-4">Message</h4>
                <p class="text-gray-700 whitespace-pre-wrap">${request.message}</p>
            </div>
            ` : ''}
        </div>
    `;

    // Show the modal
    document.getElementById('requestModal').classList.remove('hidden');
}

function closeRequestModal() {
    document.getElementById('requestModal').classList.add('hidden');
    document.getElementById('modalContent').innerHTML = '';
}

// Close modal when clicking outside
document.getElementById('requestModal')?.addEventListener('click', function(e) {
    if (e.target === this) {
        closeRequestModal();
    }
});

// Close modal with Escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeRequestModal();
    }
});
</script>

<?php
// Include footer
require_once 'includes/footer.php';
?>