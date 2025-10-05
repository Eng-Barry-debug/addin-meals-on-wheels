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
require_once 'includes/functions.php';
$activityLogger = new ActivityLogger($pdo);

// Initialize variables
$error = '';
$success = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_status'])) {
        $feedback_id = (int)$_POST['feedback_id'];
        $new_status = sanitize($_POST['status']);

        if (in_array($new_status, ['new', 'in_review', 'resolved', 'closed'])) {
            try {
                $stmt = $pdo->prepare("UPDATE feedback SET status = ? WHERE id = ?");
                $stmt->execute([$new_status, $feedback_id]);

                // Log activity
                $activityLogger->log('feedback', 'updated', "Updated feedback status to {$new_status}", 'feedback', $feedback_id);

                $_SESSION['message'] = [
                    'type' => 'success',
                    'text' => 'Feedback status updated successfully!'
                ];

                header('Location: ' . $_SERVER['PHP_SELF'] . '?' . http_build_query($_GET));
                exit();
            } catch (PDOException $e) {
                $error = 'Error updating feedback status: ' . $e->getMessage();
            }
        }
    }

    if (isset($_POST['add_response'])) {
        $feedback_id = (int)$_POST['feedback_id'];
        $response = sanitize($_POST['response']);

        if (empty($response)) {
            $error = 'Response cannot be empty';
        } else {
            try {
                $stmt = $pdo->prepare("UPDATE feedback SET response = ?, response_date = NOW(), status = 'resolved' WHERE id = ?");
                $stmt->execute([$response, $feedback_id]);

                // Log activity
                $activityLogger->log('feedback', 'updated', "Added response to feedback", 'feedback', $feedback_id);

                $_SESSION['message'] = [
                    'type' => 'success',
                    'text' => 'Response added successfully!'
                ];

                header('Location: ' . $_SERVER['PHP_SELF'] . '?' . http_build_query($_GET));
                exit();
            } catch (PDOException $e) {
                $error = 'Error adding response: ' . $e->getMessage();
            }
        }
    }

    if (isset($_POST['delete_feedback'])) {
        $feedback_id = (int)$_POST['feedback_id'];

        try {
            $stmt = $pdo->prepare("DELETE FROM feedback WHERE id = ?");
            $stmt->execute([$feedback_id]);

            // Log activity
            $activityLogger->log('feedback', 'deleted', "Deleted feedback entry", 'feedback', $feedback_id);

            $_SESSION['message'] = [
                'type' => 'success',
                'text' => 'Feedback deleted successfully!'
            ];

            header('Location: ' . $_SERVER['PHP_SELF'] . '?' . http_build_query($_GET));
            exit();
        } catch (PDOException $e) {
            $error = 'Error deleting feedback: ' . $e->getMessage();
        }
    }
}

// Get filter parameters
$search = $_GET['search'] ?? '';
$status_filter = $_GET['status'] ?? 'all';
$date_from = $_GET['date_from'] ?? '';
$date_to = $_GET['date_to'] ?? '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 10;
$offset = ($page - 1) * $per_page;

// Build where clause
$where_conditions = [];
$params = [];

if (!empty($search)) {
    $where_conditions[] = "(f.name LIKE :search OR f.email LIKE :search OR f.message LIKE :search)";
    $params[':search'] = "%$search%";
}

if ($status_filter !== 'all') {
    $where_conditions[] = "f.status = :status";
    $params[':status'] = $status_filter;
}

if (!empty($date_from)) {
    $where_conditions[] = "DATE(f.created_at) >= :date_from";
    $params[':date_from'] = $date_from;
}

if (!empty($date_to)) {
    $where_conditions[] = "DATE(f.created_at) <= :date_to";
    $params[':date_to'] = $date_to;
}

$where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

// Get total count for pagination
$total_sql = "SELECT COUNT(*) as count FROM feedback f $where_clause";
$total_stmt = $pdo->prepare($total_sql);
$total_stmt->execute($params);
$total_feedback = (int)$total_stmt->fetch(PDO::FETCH_ASSOC)['count'];

// Get feedback with pagination
$sql = "SELECT f.* FROM feedback f $where_clause ORDER BY f.created_at DESC LIMIT $offset, $per_page";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$feedback_list = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get pagination info
$total_pages = ceil($total_feedback / $per_page);

// Set page title
$page_title = 'Feedback Management';
$page_description = 'Manage customer feedback and responses';

// Include header
require_once 'includes/header.php';
?>

<!-- Feedback Header -->
<div class="bg-gradient-to-br from-primary via-primary-dark to-secondary text-white">
    <div class="container mx-auto px-6 py-12">
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between">
            <div class="flex items-center space-x-6">
                <div class="h-20 w-20 rounded-full bg-white/20 backdrop-blur-sm flex items-center justify-center text-white font-bold text-3xl shadow-lg">
                    <i class="fas fa-comments"></i>
                </div>
                <div>
                    <h1 class="text-3xl md:text-4xl font-bold mb-2">Feedback Management</h1>
                    <p class="text-lg opacity-90 mb-2">View and respond to customer feedback</p>
                    <div class="flex items-center space-x-4">
                        <span class="px-3 py-1 bg-white/20 backdrop-blur-sm rounded-full text-sm font-medium">
                            <?php echo $total_feedback; ?> Total Feedback
                        </span>
                        <span class="px-3 py-1 bg-white/20 backdrop-blur-sm rounded-full text-sm font-medium">
                            <?php echo count($feedback_list); ?> This Page
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
                <label for="status" class="block text-sm font-semibold text-gray-700 mb-2">Status</label>
                <select name="status" id="status"
                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                    <option value="all" <?php echo $status_filter === 'all' ? 'selected' : ''; ?>>All Status</option>
                    <option value="new" <?php echo $status_filter === 'new' ? 'selected' : ''; ?>>New</option>
                    <option value="in_review" <?php echo $status_filter === 'in_review' ? 'selected' : ''; ?>>In Review</option>
                    <option value="resolved" <?php echo $status_filter === 'resolved' ? 'selected' : ''; ?>>Resolved</option>
                    <option value="closed" <?php echo $status_filter === 'closed' ? 'selected' : ''; ?>>Closed</option>
                </select>
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

            <div class="md:col-span-4 flex items-end space-x-4">
                <button type="submit" class="bg-primary hover:bg-primary-dark text-white px-6 py-3 rounded-lg font-semibold transition-colors duration-200 flex items-center">
                    <i class="fas fa-search mr-2"></i>
                    Search
                </button>
                <a href="feedback.php" class="bg-gray-500 hover:bg-gray-600 text-white px-6 py-3 rounded-lg font-semibold transition-colors duration-200">
                    <i class="fas fa-times mr-2"></i>
                    Clear
                </a>
            </div>
        </form>
    </div>

    <!-- Feedback List -->
    <div class="bg-white rounded-xl shadow-lg overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200">
            <h2 class="text-lg font-semibold text-gray-900">Customer Feedback (<?php echo $total_feedback; ?>)</h2>
        </div>

        <?php if (empty($feedback_list)): ?>
            <div class="p-12 text-center">
                <i class="fas fa-comments text-4xl text-gray-400 mb-4"></i>
                <h3 class="text-lg font-medium text-gray-900 mb-2">No feedback found</h3>
                <p class="text-gray-500">No feedback entries match your current filters.</p>
            </div>
        <?php else: ?>
            <div class="divide-y divide-gray-200">
                <?php foreach ($feedback_list as $item): ?>
                    <div class="p-6 hover:bg-gray-50 transition-colors duration-200">
                        <div class="flex items-start justify-between">
                            <div class="flex items-start space-x-4">
                                <div class="flex-shrink-0">
                                    <div class="h-12 w-12 rounded-full bg-primary flex items-center justify-center text-white font-semibold text-lg">
                                        <?php echo strtoupper(substr($item['name'], 0, 1)); ?>
                                    </div>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center space-x-2 mb-2">
                                        <h3 class="text-lg font-semibold text-gray-900">
                                            <?php echo htmlspecialchars($item['name']); ?>
                                        </h3>
                                        <span class="px-2.5 py-0.5 rounded-full text-xs font-medium <?php echo getStatusBadgeClass($item['status'] ?? 'new'); ?>">
                                            <?php echo ucfirst(str_replace('_', ' ', $item['status'] ?? 'new')); ?>
                                        </span>
                                    </div>
                                    <div class="text-sm text-gray-600 mb-3">
                                        <?php echo htmlspecialchars($item['email']); ?>
                                        <?php if (!empty($item['phone'])): ?>
                                            • <?php echo htmlspecialchars($item['phone']); ?>
                                        <?php endif; ?>
                                        <span class="text-gray-400">•</span>
                                        <span class="text-gray-400">
                                            <?php echo date('M j, Y g:i A', strtotime($item['created_at'])); ?>
                                        </span>
                                    </div>
                                    <div class="bg-gray-50 rounded-lg p-4 mb-4">
                                        <p class="text-gray-700 whitespace-pre-wrap">
                                            <?php echo htmlspecialchars($item['message']); ?>
                                        </p>
                                    </div>

                                    <?php if (!empty($item['response'])): ?>
                                        <div class="bg-green-50 border border-green-200 rounded-lg p-4">
                                            <div class="flex justify-between items-start mb-2">
                                                <div class="flex items-center space-x-2">
                                                    <i class="fas fa-reply text-green-600"></i>
                                                    <span class="text-sm font-medium text-green-800">Your Response</span>
                                                </div>
                                                <span class="text-xs text-green-600">
                                                    <?php echo date('M j, Y g:i A', strtotime($item['response_date'])); ?>
                                                </span>
                                            </div>
                                            <p class="text-green-700 whitespace-pre-wrap">
                                                <?php echo htmlspecialchars($item['response']); ?>
                                            </p>
                                        </div>
                                    <?php else: ?>
                                        <div class="flex items-center space-x-3">
                                            <button onclick="openResponseModal(<?php echo $item['id']; ?>)"
                                                    class="bg-primary hover:bg-primary-dark text-white px-4 py-2 rounded-lg font-medium transition-colors duration-200 flex items-center">
                                                <i class="fas fa-reply mr-2"></i>
                                                Add Response
                                            </button>
                                            <form method="POST" class="inline" onsubmit="return confirm('Mark this feedback as in review?');">
                                                <input type="hidden" name="feedback_id" value="<?php echo $item['id']; ?>">
                                                <input type="hidden" name="status" value="in_review">
                                                <button type="submit" name="update_status"
                                                        class="bg-yellow-500 hover:bg-yellow-600 text-white px-4 py-2 rounded-lg font-medium transition-colors duration-200">
                                                    <i class="fas fa-eye mr-2"></i>
                                                    Mark In Review
                                                </button>
                                            </form>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <div class="flex items-center space-x-2">
                                <form method="POST" class="inline" onsubmit="return confirm('Are you sure you want to delete this feedback?');">
                                    <input type="hidden" name="feedback_id" value="<?php echo $item['id']; ?>">
                                    <button type="submit" name="delete_feedback"
                                            class="text-red-600 hover:text-red-900 p-2 rounded-lg transition-colors duration-200"
                                            title="Delete Feedback">
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
                            Showing <?php echo (($page - 1) * $per_page) + 1; ?> to <?php echo min($page * $per_page, $total_feedback); ?> of <?php echo $total_feedback; ?> results
                        </div>
                        <div class="flex space-x-1">
                            <?php if ($page > 1): ?>
                                <a href="?page=<?php echo $page - 1; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo $status_filter !== 'all' ? '&status=' . urlencode($status_filter) : ''; ?><?php echo !empty($date_from) ? '&date_from=' . urlencode($date_from) : ''; ?><?php echo !empty($date_to) ? '&date_to=' . urlencode($date_to) : ''; ?>"
                                   class="px-3 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                                    Previous
                                </a>
                            <?php endif; ?>

                            <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                                <a href="?page=<?php echo $i; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo $status_filter !== 'all' ? '&status=' . urlencode($status_filter) : ''; ?><?php echo !empty($date_from) ? '&date_from=' . urlencode($date_from) : ''; ?><?php echo !empty($date_to) ? '&date_to=' . urlencode($date_to) : ''; ?>"
                                   class="px-3 py-2 border <?php echo $i === $page ? 'bg-primary text-white' : 'border-gray-300 text-gray-700 bg-white hover:bg-gray-50'; ?> rounded-md text-sm font-medium">
                                    <?php echo $i; ?>
                                </a>
                            <?php endfor; ?>

                            <?php if ($page < $total_pages): ?>
                                <a href="?page=<?php echo $page + 1; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo $status_filter !== 'all' ? '&status=' . urlencode($status_filter) : ''; ?><?php echo !empty($date_from) ? '&date_from=' . urlencode($date_from) : ''; ?><?php echo !empty($date_to) ? '&date_to=' . urlencode($date_to) : ''; ?>"
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

<!-- Response Modal -->
<div id="responseModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden z-50">
    <div class="flex items-center justify-center min-h-screen p-4">
        <div class="bg-white rounded-lg shadow-xl max-w-lg w-full">
            <div class="px-6 py-4 border-b border-gray-200">
                <div class="flex items-center justify-between">
                    <h3 class="text-lg font-semibold text-gray-900">Add Response</h3>
                    <button onclick="closeResponseModal()" class="text-gray-400 hover:text-gray-600">
                        <i class="fas fa-times text-xl"></i>
                    </button>
                </div>
            </div>
            <form method="POST" class="p-6">
                <input type="hidden" name="feedback_id" id="responseFeedbackId">

                <div class="mb-4">
                    <label for="response" class="block text-sm font-medium text-gray-700 mb-2">Your Response</label>
                    <textarea id="response" name="response" rows="4" required
                              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent"
                              placeholder="Type your response to the customer..."></textarea>
                </div>

                <div class="flex justify-end space-x-3">
                    <button type="button" onclick="closeResponseModal()"
                            class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition-colors duration-200">
                        Cancel
                    </button>
                    <button type="submit" name="add_response"
                            class="bg-primary hover:bg-primary-dark text-white px-4 py-2 rounded-lg font-medium transition-colors duration-200">
                        <i class="fas fa-paper-plane mr-2"></i>
                        Send Response
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
/* Feedback page enhancements */
.feedback-card {
    transition: all 0.2s ease;
}

.feedback-card:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

/* Status badge styling */
.status-badge {
    @apply px-2.5 py-0.5 rounded-full text-xs font-medium;
}

.status-new {
    @apply bg-blue-100 text-blue-800;
}

.status-in_review {
    @apply bg-yellow-100 text-yellow-800;
}

.status-resolved {
    @apply bg-green-100 text-green-800;
}

.status-closed {
    @apply bg-gray-100 text-gray-800;
}

/* Form focus enhancements */
input:focus, textarea:focus, select:focus {
    box-shadow: 0 0 0 3px rgba(193, 39, 45, 0.1);
}

/* Modal animations */
.modal-enter {
    animation: modalFadeIn 0.3s ease-out;
}

@keyframes modalFadeIn {
    from {
        opacity: 0;
        transform: scale(0.95) translateY(-20px);
    }
    to {
        opacity: 1;
        transform: scale(1) translateY(0);
    }
}
</style>

<script>
// Modal functionality
function openResponseModal(feedbackId) {
    document.getElementById('responseFeedbackId').value = feedbackId;
    document.getElementById('responseModal').classList.remove('hidden');
    document.getElementById('response').focus();
}

function closeResponseModal() {
    document.getElementById('responseModal').classList.add('hidden');
    document.getElementById('response').value = '';
}

// Close modal when clicking outside
document.getElementById('responseModal')?.addEventListener('click', function(e) {
    if (e.target === this) {
        closeResponseModal();
    }
});

// Close modal with Escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeResponseModal();
    }
});
</script>

<?php
// Include footer
require_once 'includes/footer.php';
?>