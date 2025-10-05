<?php
// Start session and check admin authentication
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include configuration and check login
require_once __DIR__ . '/../admin/includes/config.php';
require_once __DIR__ . '/includes/functions.php'; // Add this line to include the sanitize function
requireLogin();

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

// Handle form submissions for customer support actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_message_status'])) {
        $message_id = (int)$_POST['message_id'];
        $status = sanitize($_POST['status']);

        if (in_array($status, ['read', 'replied', 'resolved'])) {
            try {
                $stmt = $pdo->prepare("UPDATE customer_messages SET status = ?, updated_at = NOW() WHERE id = ?");
                $stmt->execute([$status, $message_id]);

                // Log activity
                $activityLogger->log('support', 'updated', "Updated customer message status to {$status}", 'customer_message', $message_id);

                $_SESSION['message'] = [
                    'type' => 'success',
                    'text' => 'Message status updated successfully!'
                ];

                header('Location: ' . $_SERVER['PHP_SELF']);
                exit();
            } catch (PDOException $e) {
                $error = 'Error updating message status: ' . $e->getMessage();
            }
        }
    }
}

// Get filter parameters
$status_filter = $_GET['status'] ?? '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 10;
$offset = ($page - 1) * $per_page;

// Build where clause
$where_conditions = [];
$params = [];

if (!empty($status_filter)) {
    $where_conditions[] = "status = :status";
    $params[':status'] = $status_filter;
}

$where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

// Get total count for pagination
$total_sql = "SELECT COUNT(*) as count FROM customer_messages $where_clause";
$total_stmt = $pdo->prepare($total_sql);
$total_stmt->execute($params);
$total_messages = (int)$total_stmt->fetch(PDO::FETCH_ASSOC)['count'];

// Get customer messages with pagination
$sql = "SELECT cm.*, u.name as customer_name, u.email as customer_email
        FROM customer_messages cm
        LEFT JOIN users u ON cm.customer_id = u.id
        $where_clause
        ORDER BY cm.created_at DESC
        LIMIT $offset, $per_page";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get pagination info
$total_pages = ceil($total_messages / $per_page);

// Set page title
$page_title = 'Customer Support';
$page_description = 'Manage customer communications and support requests';

// Include header
require_once 'includes/header.php';
?>

<!-- Customer Support Header -->
<div class="bg-gradient-to-br from-primary via-primary-dark to-secondary text-white">
    <div class="container mx-auto px-6 py-12">
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between">
            <div class="flex items-center space-x-6">
                <div class="h-20 w-20 rounded-full bg-white/20 backdrop-blur-sm flex items-center justify-center text-white font-bold text-3xl shadow-lg">
                    <i class="fas fa-comments"></i>
                </div>
                <div>
                    <h1 class="text-3xl md:text-4xl font-bold mb-2">Customer Support</h1>
                    <p class="text-lg opacity-90 mb-2">Manage customer communications and support requests</p>
                    <div class="flex items-center space-x-4">
                        <span class="px-3 py-1 bg-white/20 backdrop-blur-sm rounded-full text-sm font-medium">
                            <?php echo $total_messages; ?> Total Messages
                        </span>
                        <span class="px-3 py-1 bg-white/20 backdrop-blur-sm rounded-full text-sm font-medium">
                            <?php echo count($messages); ?> This Page
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

    <!-- Quick Actions -->
    <div class="bg-white rounded-xl shadow-lg p-6 mb-8">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h3 class="text-lg font-semibold text-gray-900 mb-2">Customer Support Management</h3>
                <p class="text-gray-600">Handle customer inquiries and support requests</p>
            </div>
            <div class="mt-4 sm:mt-0 flex space-x-3">
                <a href="../chat.php"
                   class="bg-primary hover:bg-primary-dark text-white px-6 py-3 rounded-lg font-semibold transition-colors duration-200 flex items-center">
                    <i class="fas fa-comments mr-2"></i>
                    Open Chat
                </a>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-xl shadow-lg p-6 mb-8">
        <form method="GET" class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <label for="status" class="block text-sm font-semibold text-gray-700 mb-2">Status</label>
                <select name="status" id="status"
                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                    <option value="">All Status</option>
                    <option value="unread" <?php echo $status_filter === 'unread' ? 'selected' : ''; ?>>Unread</option>
                    <option value="read" <?php echo $status_filter === 'read' ? 'selected' : ''; ?>>Read</option>
                    <option value="replied" <?php echo $status_filter === 'replied' ? 'selected' : ''; ?>>Replied</option>
                    <option value="resolved" <?php echo $status_filter === 'resolved' ? 'selected' : ''; ?>>Resolved</option>
                </select>
            </div>

            <div class="flex items-end space-x-4">
                <button type="submit" class="bg-primary hover:bg-primary-dark text-white px-6 py-3 rounded-lg font-semibold transition-colors duration-200 flex items-center">
                    <i class="fas fa-filter mr-2"></i>
                    Filter
                </button>
                <a href="customer_support.php" class="bg-gray-500 hover:bg-gray-600 text-white px-6 py-3 rounded-lg font-semibold transition-colors duration-200">
                    <i class="fas fa-times mr-2"></i>
                    Clear
                </a>
            </div>
        </form>
    </div>

    <!-- Messages List -->
    <div class="bg-white rounded-xl shadow-lg overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200">
            <h2 class="text-lg font-semibold text-gray-900">Customer Messages (<?php echo $total_messages; ?>)</h2>
        </div>

        <?php if (empty($messages)): ?>
            <div class="p-12 text-center">
                <i class="fas fa-comments text-4xl text-gray-400 mb-4"></i>
                <h3 class="text-lg font-medium text-gray-900 mb-2">No messages found</h3>
                <p class="text-gray-500 mb-4">No customer messages match your current filters.</p>
                <a href="../chat.php" class="bg-primary hover:bg-primary-dark text-white px-6 py-3 rounded-lg font-semibold transition-colors duration-200 flex items-center mx-auto w-fit">
                    <i class="fas fa-comments mr-2"></i>
                    Start Chat
                </a>
            </div>
        <?php else: ?>
            <div class="divide-y divide-gray-200">
                <?php foreach ($messages as $message): ?>
                    <div class="p-6 hover:bg-gray-50 transition-colors duration-200">
                        <div class="flex items-start justify-between">
                            <div class="flex items-start space-x-4 flex-1">
                                <div class="flex-shrink-0">
                                    <div class="h-12 w-12 rounded-full bg-primary flex items-center justify-center text-white font-semibold text-lg">
                                        <?php echo strtoupper(substr($message['customer_name'] ?? 'C', 0, 1)); ?>
                                    </div>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center space-x-2 mb-2">
                                        <h3 class="text-lg font-semibold text-gray-900">
                                            <?php echo htmlspecialchars($message['customer_name'] ?? 'Anonymous Customer'); ?>
                                        </h3>
                                        <span class="px-2.5 py-0.5 rounded-full text-xs font-medium <?php echo getMessageStatusBadgeClass($message['status']); ?>">
                                            <?php echo ucfirst($message['status'] ?? 'unread'); ?>
                                        </span>
                                    </div>
                                    <div class="text-sm text-gray-600 mb-3">
                                        <span class="font-medium"><?php echo htmlspecialchars($message['customer_email'] ?? 'No email provided'); ?></span>
                                        <span class="text-gray-400 mx-2">•</span>
                                        <span class="text-gray-400">
                                            <?php echo date('M j, Y g:i A', strtotime($message['created_at'])); ?>
                                        </span>
                                    </div>
                                    <div class="bg-gray-50 rounded-lg p-4 mb-3">
                                        <p class="text-gray-700 line-clamp-3">
                                            <?php echo htmlspecialchars(substr($message['message'] ?? '', 0, 200) . (strlen($message['message'] ?? '') > 200 ? '...' : '')); ?>
                                        </p>
                                    </div>
                                    <?php if (!empty($message['response'])): ?>
                                        <div class="bg-blue-50 rounded-lg p-4 mb-3">
                                            <p class="text-sm text-gray-600 mb-1"><strong>Your Response:</strong></p>
                                            <p class="text-gray-700 line-clamp-2">
                                                <?php echo htmlspecialchars(substr($message['response'], 0, 150) . (strlen($message['response']) > 150 ? '...' : '')); ?>
                                            </p>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <div class="flex items-center space-x-2 ml-4">
                                <button onclick="viewMessage(<?php echo $message['id']; ?>)"
                                   class="bg-blue-500 hover:bg-blue-600 text-white px-3 py-2 rounded-lg font-medium transition-colors duration-200 flex items-center space-x-1"
                                   title="View Full Message">
                                    <i class="fas fa-eye"></i>
                                    <span class="hidden sm:inline">View</span>
                                </button>

                                <?php if (($message['status'] ?? 'unread') !== 'resolved'): ?>
                                    <form method="POST" class="inline">
                                        <input type="hidden" name="message_id" value="<?php echo $message['id']; ?>">
                                        <input type="hidden" name="status" value="resolved">
                                        <button type="submit" name="update_message_status"
                                                class="bg-green-500 hover:bg-green-600 text-white px-3 py-2 rounded-lg font-medium transition-colors duration-200 flex items-center space-x-1"
                                                title="Mark as Resolved">
                                            <i class="fas fa-check"></i>
                                            <span class="hidden sm:inline">Resolve</span>
                                        </button>
                                    </form>
                                <?php endif; ?>

                                <button onclick="replyToMessage(<?php echo $message['id']; ?>)"
                                        class="bg-indigo-500 hover:bg-indigo-600 text-white px-3 py-2 rounded-lg font-medium transition-colors duration-200 flex items-center space-x-1"
                                        title="Send Response">
                                    <i class="fas fa-reply"></i>
                                    <span class="hidden sm:inline">Reply</span>
                                </button>
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
                            Showing <?php echo (($page - 1) * $per_page) + 1; ?> to <?php echo min($page * $per_page, $total_messages); ?> of <?php echo $total_messages; ?> results
                        </div>
                        <div class="flex space-x-1">
                            <?php if ($page > 1): ?>
                                <a href="?page=<?php echo $page - 1; ?><?php echo !empty($status_filter) ? '&status=' . urlencode($status_filter) : ''; ?>"
                                   class="px-3 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                                    Previous
                                </a>
                            <?php endif; ?>

                            <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                                <a href="?page=<?php echo $i; ?><?php echo !empty($status_filter) ? '&status=' . urlencode($status_filter) : ''; ?>"
                                   class="px-3 py-2 border <?php echo $i === $page ? 'bg-primary text-white' : 'border-gray-300 text-gray-700 bg-white hover:bg-gray-50'; ?> rounded-md text-sm font-medium">
                                    <?php echo $i; ?>
                                </a>
                            <?php endfor; ?>

                            <?php if ($page < $total_pages): ?>
                                <a href="?page=<?php echo $page + 1; ?><?php echo !empty($status_filter) ? '&status=' . urlencode($status_filter) : ''; ?>"
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

<!-- View Message Modal -->
<div id="viewModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden z-50">
    <div class="flex items-center justify-center min-h-screen p-4">
        <div class="bg-white rounded-lg shadow-xl max-w-4xl w-full max-h-screen overflow-y-auto">
            <div class="px-6 py-4 border-b border-gray-200">
                <div class="flex items-center justify-between">
                    <h3 class="text-lg font-semibold text-gray-900">Message Details</h3>
                    <button onclick="closeViewModal()" class="text-gray-400 hover:text-gray-600">
                        <i class="fas fa-times text-xl"></i>
                    </button>
                </div>
            </div>
            <div id="viewModalContent" class="p-6">
                <!-- Content will be loaded here -->
            </div>
        </div>
    </div>
</div>

<!-- Reply Modal -->
<div id="replyModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden z-50">
    <div class="flex items-center justify-center min-h-screen p-4">
        <div class="bg-white rounded-lg shadow-xl max-w-4xl w-full max-h-screen overflow-y-auto">
            <div class="px-6 py-4 border-b border-gray-200">
                <div class="flex items-center justify-between">
                    <h3 class="text-lg font-semibold text-gray-900">Send Response</h3>
                    <button onclick="closeReplyModal()" class="text-gray-400 hover:text-gray-600">
                        <i class="fas fa-times text-xl"></i>
                    </button>
                </div>
            </div>
            <form method="POST" class="p-6" id="replyForm">
                <input type="hidden" name="message_id" id="reply_message_id">

                <div class="mb-6">
                    <label for="response" class="block text-sm font-semibold text-gray-700 mb-2">Response Message *</label>
                    <textarea name="response" id="response" rows="6" required
                              class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent"
                              placeholder="Type your response to the customer..."></textarea>
                </div>

                <div class="flex justify-end space-x-4">
                    <button type="button" onclick="closeReplyModal()"
                            class="px-6 py-3 border border-gray-300 rounded-lg font-semibold text-gray-700 hover:bg-gray-50 transition-colors duration-200">
                        Cancel
                    </button>
                    <button type="submit" name="send_response"
                            class="bg-primary hover:bg-primary-dark text-white px-6 py-3 rounded-lg font-semibold transition-colors duration-200">
                        <i class="fas fa-paper-plane mr-2"></i>
                        Send Response
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
/* Customer support page enhancements */
.message-card {
    transition: all 0.2s ease;
}

.message-card:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

/* Status badge styling */
.status-badge {
    @apply px-2.5 py-0.5 rounded-full text-xs font-medium;
}

.status-unread {
    @apply bg-red-100 text-red-800;
}

.status-read {
    @apply bg-blue-100 text-blue-800;
}

.status-replied {
    @apply bg-yellow-100 text-yellow-800;
}

.status-resolved {
    @apply bg-green-100 text-green-800;
}

/* Content preview styling */
.line-clamp-3 {
    display: -webkit-box;
    -webkit-line-clamp: 3;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

/* Enhanced button styling */
.action-btn {
    transition: all 0.2s ease;
    min-width: 80px;
    font-size: 0.875rem;
    font-weight: 500;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.action-btn:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
}

.action-btn i {
    margin-right: 0.25rem;
}

/* Mobile responsive button text */
@media (max-width: 640px) {
    .action-btn span {
        display: none;
    }

    .action-btn {
        min-width: 40px;
        padding: 0.5rem;
    }
}
</style>

<script>
// Customer support modals
function viewMessage(messageId) {
    // For now, show a placeholder - in a real implementation, this would fetch message details
    const modalContent = document.getElementById('viewModalContent');
    modalContent.innerHTML = `
        <div class="space-y-6">
            <div class="text-center">
                <h2 class="text-2xl font-bold text-gray-900 mb-2">Message Details</h2>
                <p class="text-gray-600">Full message content would be displayed here.</p>
            </div>
            <div class="bg-gray-50 rounded-lg p-6">
                <p class="text-gray-700">This is where the complete customer message would be shown, along with any previous responses and conversation history.</p>
            </div>
        </div>
    `;

    document.getElementById('viewModal').classList.remove('hidden');
}

function replyToMessage(messageId) {
    document.getElementById('reply_message_id').value = messageId;
    document.getElementById('replyModal').classList.remove('hidden');
    document.getElementById('response').focus();
}

function closeViewModal() {
    document.getElementById('viewModal').classList.add('hidden');
    document.getElementById('viewModalContent').innerHTML = '';
}

function closeReplyModal() {
    document.getElementById('replyModal').classList.add('hidden');
    document.getElementById('replyForm').reset();
}

// Close modals when clicking outside
document.getElementById('viewModal')?.addEventListener('click', function(e) {
    if (e.target === this) {
        closeViewModal();
    }
});

document.getElementById('replyModal')?.addEventListener('click', function(e) {
    if (e.target === this) {
        closeReplyModal();
    }
});

// Close modals with Escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeViewModal();
        closeReplyModal();
    }
});
</script>

<?php
// Include footer
require_once 'includes/footer.php';
?>

<?php
// Helper function for message status badges
function getMessageStatusBadgeClass($status) {
    $classes = [
        'unread' => 'bg-red-100 text-red-800',
        'read' => 'bg-blue-100 text-blue-800',
        'replied' => 'bg-yellow-100 text-yellow-800',
        'resolved' => 'bg-green-100 text-green-800',
    ];

    return $classes[strtolower($status)] ?? 'bg-gray-100 text-gray-800';
}
?>
