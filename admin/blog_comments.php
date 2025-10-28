<?php
require_once 'includes/config.php';
requireAdmin(); // Only admins can access this page

$pdo = $GLOBALS['pdo'];

// Handle comment status updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && isset($_POST['comment_id'])) {
    $commentId = (int)$_POST['comment_id'];
    $action = $_POST['action'];
    
    try {
        if ($action === 'approve') {
            $stmt = $pdo->prepare("UPDATE blog_comments SET status = 'approved' WHERE id = ?");
            $message = 'Comment approved successfully';
        } elseif ($action === 'reject') {
            $stmt = $pdo->prepare("UPDATE blog_comments SET status = 'rejected' WHERE id = ?");
            $message = 'Comment rejected successfully';
        } elseif ($action === 'delete') {
            $stmt = $pdo->prepare("DELETE FROM blog_comments WHERE id = ?");
            $message = 'Comment deleted successfully';
        }
        
        if (isset($stmt)) {
            $stmt->execute([$commentId]);
            $_SESSION['success_message'] = $message;
        }
    } catch (PDOException $e) {
        $_SESSION['error_message'] = 'Error updating comment: ' . $e->getMessage();
    }
    
    // Redirect to prevent form resubmission
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit();
}

// Get filter parameters
$statusFilter = $_GET['status'] ?? 'all';
$searchQuery = $_GET['search'] ?? '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 10;
$offset = ($page - 1) * $per_page;

// Build where clause
$where_conditions = [];
$params = [];

// Apply status filter
if ($statusFilter !== 'all') {
    $where_conditions[] = "c.status = :status";
    $params[':status'] = $statusFilter;
}

// Apply search filter
if (!empty($searchQuery)) {
    $where_conditions[] = "(
        c.content LIKE :search
        OR u.name LIKE :search
        OR u.email LIKE :search
        OR p.title LIKE :search
    )";
    $params[':search'] = "%$searchQuery%";
}

$where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

// Build the base query
$query = "
    SELECT
        c.*,
        p.title as post_title,
        u.name as user_name,
        u.email as user_email,
        u.profile_image,
        CONCAT('blog-single.php?id=', p.id) as post_slug
    FROM blog_comments c
    LEFT JOIN blog_posts p ON c.post_id = p.id
    LEFT JOIN users u ON c.user_id = u.id
    $where_clause
    ORDER BY c.created_at DESC
    LIMIT $offset, $per_page
";

$params = [];

// Apply status filter
if ($statusFilter !== 'all') {
    $query .= " AND c.status = :status";
    $params[':status'] = $statusFilter;
}

// Apply search filter
if (!empty($searchQuery)) {
    $query .= " AND (
        c.content LIKE :search 
        OR u.name LIKE :search 
        OR u.email LIKE :search
        OR p.title LIKE :search
    )";
    $params[':search'] = "%$searchQuery%";
}

// Get total count for pagination
$total_sql = "SELECT COUNT(*) as count FROM blog_comments c
              LEFT JOIN blog_posts p ON c.post_id = p.id
              LEFT JOIN users u ON c.user_id = u.id
              $where_clause";
$total_stmt = $pdo->prepare($total_sql);
$total_stmt->execute($params);
$total_comments = (int)$total_stmt->fetch(PDO::FETCH_ASSOC)['count'];

// Get comments with pagination
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$comments = $stmt->fetchAll();

// Get pagination info
$total_pages = ceil($total_comments / $per_page);

// Function to get counts
function getCount($pdo, $table, $condition = '') {
    $query = "SELECT COUNT(*) as count FROM $table";
    if (!empty($condition)) {
        $query .= " WHERE $condition";
    }
    $stmt = $pdo->prepare($query);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    return $result['count'];
}

// Get counts for status filter
$statusCounts = [
    'all' => getCount($pdo, 'blog_comments'),
    'pending' => getCount($pdo, 'blog_comments', "status = 'pending'"),
    'approved' => getCount($pdo, 'blog_comments', "status = 'approved'"),
    'rejected' => getCount($pdo, 'blog_comments', "status = 'rejected'")
];

// Include header
$pageTitle = 'Manage Blog Comments';
include 'includes/header.php';
?>

<!-- Dashboard Header -->
<div class="bg-gradient-to-br from-primary via-primary-dark to-secondary text-white mt-0">
    <div class="container mx-auto px-6 py-8">
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between">
            <div>
                <h1 class="text-3xl md:text-4xl font-bold mb-2">Blog Comments Management</h1>
                <p class="text-lg opacity-90">Manage and moderate blog post comments</p>
            </div>
            <div class="mt-4 lg:mt-0">
                <button type="button" onclick="window.location.reload()"
                       class="bg-white/20 hover:bg-white/30 text-white px-4 py-2 rounded-lg transition-colors">
                    <i class="fas fa-sync-alt mr-2"></i>Refresh
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Main Content Area -->
<div class="container mx-auto px-6 py-8">
    <!-- Feedback Messages -->
    <?php if (isset($_SESSION['success_message'])): ?>
        <div class="mb-6 p-4 bg-green-50 border-l-4 border-green-400 text-green-700 rounded-lg flex items-center">
            <i class="fas fa-check-circle mr-3"></i>
            <div>
                <p class="font-semibold">Success</p>
                <p><?php echo htmlspecialchars($_SESSION['success_message']); ?></p>
            </div>
            <button type="button" onclick="this.parentElement.remove()" class="ml-auto text-gray-400 hover:text-gray-600">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <?php unset($_SESSION['success_message']); ?>
    <?php endif; ?>

    <?php if (isset($_SESSION['error_message'])): ?>
        <div class="mb-6 p-4 bg-red-50 border-l-4 border-red-400 text-red-700 rounded-lg flex items-center">
            <i class="fas fa-exclamation-triangle mr-3"></i>
            <div>
                <p class="font-semibold">Error</p>
                <p><?php echo htmlspecialchars($_SESSION['error_message']); ?></p>
            </div>
            <button type="button" onclick="this.parentElement.remove()" class="ml-auto text-gray-400 hover:text-gray-600">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <?php unset($_SESSION['error_message']); ?>
    <?php endif; ?>

    <!-- Statistics Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
        <div class="bg-white rounded-xl shadow-lg p-6 border-l-4 border-blue-500">
            <div class="flex items-center">
                <div class="p-3 bg-blue-100 rounded-lg">
                    <i class="fas fa-comment text-2xl text-blue-600"></i>
                </div>
                <div class="ml-4">
                    <h3 class="text-2xl font-bold text-gray-900"><?php echo number_format($statusCounts['all']); ?></h3>
                    <p class="text-gray-600">Total Comments</p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-lg p-6 border-l-4 border-yellow-500">
            <div class="flex items-center">
                <div class="p-3 bg-yellow-100 rounded-lg">
                    <i class="fas fa-clock text-2xl text-yellow-600"></i>
                </div>
                <div class="ml-4">
                    <h3 class="text-2xl font-bold text-gray-900"><?php echo number_format($statusCounts['pending']); ?></h3>
                    <p class="text-gray-600">Pending Review</p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-lg p-6 border-l-4 border-green-500">
            <div class="flex items-center">
                <div class="p-3 bg-green-100 rounded-lg">
                    <i class="fas fa-check-circle text-2xl text-green-600"></i>
                </div>
                <div class="ml-4">
                    <h3 class="text-2xl font-bold text-gray-900"><?php echo number_format($statusCounts['approved']); ?></h3>
                    <p class="text-gray-600">Approved</p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-lg p-6 border-l-4 border-red-500">
            <div class="flex items-center">
                <div class="p-3 bg-red-100 rounded-lg">
                    <i class="fas fa-times-circle text-2xl text-red-600"></i>
                </div>
                <div class="ml-4">
                    <h3 class="text-2xl font-bold text-gray-900"><?php echo number_format($statusCounts['rejected']); ?></h3>
                    <p class="text-gray-600">Rejected</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Comments Table -->
    <div class="bg-white rounded-xl shadow-lg overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200">
            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between">
                <h3 class="text-lg font-semibold text-gray-800 mb-4 lg:mb-0">
                    <i class="fas fa-list-ul text-primary mr-2"></i>
                    Comments List
                </h3>
                <div class="flex flex-col sm:flex-row gap-3">
                    <form class="flex" method="GET" action="">
                        <input type="hidden" name="status" value="<?php echo htmlspecialchars($statusFilter); ?>">
                        <div class="flex">
                            <input type="text" class="form-control form-control-sm border-r-0 rounded-r-none" name="search"
                                   placeholder="Search comments..." value="<?php echo htmlspecialchars($searchQuery); ?>">
                            <button class="btn btn-outline-secondary btn-sm rounded-l-none" type="submit">
                                <i class="fas fa-search"></i>
                            </button>
                            <?php if (!empty($searchQuery)): ?>
                                <a href="?status=<?php echo urlencode($statusFilter); ?>" class="btn btn-outline-danger btn-sm">
                                    <i class="fas fa-times"></i>
                                </a>
                            <?php endif; ?>
                        </div>
                    </form>
                    <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-toggle="modal" data-bs-target="#filterModal">
                        <i class="fas fa-filter mr-1"></i> Filter
                    </button>
                </div>
            </div>
        </div>
        <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Comment</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Post</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">User</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php if (empty($comments)): ?>
                                    <tr>
                                        <td colspan="7" class="text-center py-4">
                                            <div class="text-muted">
                                                <i class="far fa-comment-slash fa-2x mb-3"></i>
                                                <p class="mb-0">No comments found.</p>
                                                <?php if (!empty($searchQuery) || $statusFilter !== 'all'): ?>
                                                    <a href="?" class="btn btn-sm btn-outline-primary mt-2">
                                                        Clear filters
                                                    </a>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($comments as $comment): 
                                        $statusClass = [
                                            'pending' => 'warning',
                                            'approved' => 'success',
                                            'rejected' => 'danger'
                                        ][$comment['status']] ?? 'secondary';
                                    ?>
                                        <tr>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                                #<?php echo $comment['id']; ?>
                                            </td>
                                            <td class="px-6 py-4">
                                                <div class="text-sm">
                                                    <?php echo nl2br(htmlspecialchars(substr($comment['content'], 0, 150))); ?>
                                                    <?php if (strlen($comment['content']) > 150): ?>...<?php endif; ?>
                                                </div>
                                            </td>
                                            <td class="px-6 py-4">
                                                <a href="../<?php echo htmlspecialchars($comment['post_slug']); ?>"
                                                   target="_blank"
                                                   class="text-blue-600 hover:text-blue-800 text-sm font-medium flex items-center">
                                                    <i class="fas fa-external-link-alt mr-1"></i>
                                                    <?php echo htmlspecialchars(substr($comment['post_title'] ?? 'Unknown Post', 0, 30)); ?>
                                                    <?php if (strlen($comment['post_title'] ?? '') > 30): ?>...<?php endif; ?>
                                                </a>
                                            </td>
                                            <td class="px-6 py-4">
                                                <?php if ($comment['user_id']): ?>
                                                    <div class="flex items-center">
                                                        <div class="flex-shrink-0 mr-3">
                                                            <?php if (!empty($comment['profile_image'])): ?>
                                                                <img src="../uploads/profiles/<?php echo htmlspecialchars($comment['profile_image']); ?>"
                                                                     class="h-8 w-8 rounded-full object-cover" alt="User">
                                                            <?php else: ?>
                                                                <div class="h-8 w-8 rounded-full bg-gradient-to-br from-primary to-secondary flex items-center justify-center text-white font-bold text-xs">
                                                                    <?php echo strtoupper(substr($comment['user_name'] ?? 'U', 0, 1)); ?>
                                                                </div>
                                                            <?php endif; ?>
                                                        </div>
                                                        <div>
                                                            <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($comment['user_name'] ?? 'Unknown User'); ?></div>
                                                            <div class="text-sm text-gray-500"><?php echo htmlspecialchars($comment['user_email'] ?? ''); ?></div>
                                                        </div>
                                                    </div>
                                                <?php else: ?>
                                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                                        <i class="fas fa-user-secret mr-1"></i> Guest
                                                    </span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="px-6 py-4">
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?php
                                                    echo $comment['status'] === 'approved' ? 'bg-green-100 text-green-800' :
                                                         ($comment['status'] === 'pending' ? 'bg-yellow-100 text-yellow-800' : 'bg-red-100 text-red-800');
                                                ?>">
                                                    <i class="fas fa-<?php
                                                        echo $comment['status'] === 'approved' ? 'check-circle' :
                                                             ($comment['status'] === 'pending' ? 'clock' : 'times-circle');
                                                    ?> mr-1"></i>
                                                    <?php echo ucfirst(htmlspecialchars($comment['status'])); ?>
                                                </span>
                                            </td>
                                            <td class="px-6 py-4 text-sm text-gray-500">
                                                <?php echo date('M j, Y', strtotime($comment['created_at'])); ?>
                                            </td>
                                            <td class="px-6 py-4 text-right text-sm font-medium">
                                                <div class="flex justify-end space-x-1">
                                                    <?php if ($comment['status'] !== 'approved'): ?>
                                                        <button onclick="confirmAction(<?php echo $comment['id']; ?>, 'approve', '<?php echo htmlspecialchars(addslashes($comment['content'])); ?>')"
                                                                class="text-green-600 hover:text-green-900 p-1 rounded"
                                                                title="Approve">
                                                            <i class="fas fa-check"></i>
                                                        </button>
                                                    <?php endif; ?>

                                                    <?php if ($comment['status'] !== 'rejected'): ?>
                                                        <button onclick="confirmAction(<?php echo $comment['id']; ?>, 'reject', '<?php echo htmlspecialchars(addslashes($comment['content'])); ?>')"
                                                                class="text-yellow-600 hover:text-yellow-900 p-1 rounded"
                                                                title="Reject">
                                                            <i class="fas fa-times"></i>
                                                        </button>
                                                    <?php endif; ?>

                                                    <button onclick="confirmDelete(<?php echo $comment['id']; ?>, '<?php echo htmlspecialchars(addslashes($comment['content'])); ?>')"
                                                            class="text-red-600 hover:text-red-900 p-1 rounded"
                                                            title="Delete">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <?php if (!empty($comments)): ?>
                    <div class="bg-gray-50 px-6 py-4 border-t border-gray-200">
                        <div class="flex justify-between items-center">
                            <div class="text-sm text-gray-700">
                                Showing <strong><?php echo (($page - 1) * $per_page) + 1; ?> to <?php echo min($page * $per_page, $total_comments); ?></strong> of <strong><?php echo number_format($total_comments); ?></strong> comments
                            </div>
                            <div class="flex items-center space-x-2">
                                <?php if ($page > 1): ?>
                                    <a href="?page=<?php echo $page - 1; ?><?php echo !empty($searchQuery) ? '&search=' . urlencode($searchQuery) : ''; ?><?php echo $statusFilter !== 'all' ? '&status=' . urlencode($statusFilter) : ''; ?>"
                                       class="px-3 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                                        Previous
                                    </a>
                                <?php endif; ?>

                                <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                                    <a href="?page=<?php echo $i; ?><?php echo !empty($searchQuery) ? '&search=' . urlencode($searchQuery) : ''; ?><?php echo $statusFilter !== 'all' ? '&status=' . urlencode($statusFilter) : ''; ?>"
                                       class="px-3 py-2 border <?php echo $i === $page ? 'bg-primary text-white' : 'border-gray-300 text-gray-700 bg-white hover:bg-gray-50'; ?> rounded-md text-sm font-medium">
                                        <?php echo $i; ?>
                                    </a>
                                <?php endfor; ?>

                                <?php if ($page < $total_pages): ?>
                                    <a href="?page=<?php echo $page + 1; ?><?php echo !empty($searchQuery) ? '&search=' . urlencode($searchQuery) : ''; ?><?php echo $statusFilter !== 'all' ? '&status=' . urlencode($statusFilter) : ''; ?>"
                                       class="px-3 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                                        Next
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
    </div>
</div>


<style>
/* Custom styles for the comments page */
.avatar-circle {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.25rem;
}

.icon-circle {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    background-color: rgba(255, 255, 255, 0.2);
    font-size: 1.25rem;
}

.comment-actions .btn {
    opacity: 0;
    transition: opacity 0.2s;
}

tr:hover .comment-actions .btn {
    opacity: 1;
}

.table > :not(caption) > * > * {
    padding: 1rem 1.25rem;
}

.table th {
    font-weight: 600;
    text-transform: uppercase;
    font-size: 0.75rem;
    letter-spacing: 0.5px;
    color: #6c757d;
    border-bottom-width: 1px;
}

.badge {
    font-weight: 500;
    padding: 0.35em 0.65em;
}

.card {
    border: none;
    box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
    margin-bottom: 1.5rem;
    border-radius: 0.5rem;
    overflow: hidden;
}

.card-header {
    background-color: #f8f9fa;
    border-bottom: 1px solid rgba(0, 0, 0, 0.05);
    padding: 1rem 1.5rem;
}

.card-header h5 {
    font-weight: 600;
    color: #344767;
    margin: 0;
}

.btn-sm {
    padding: 0.25rem 0.5rem;
    font-size: 0.75rem;
    border-radius: 0.25rem;
}

.alert {
    border: none;
    border-left: 4px solid;
    border-radius: 0.25rem;
}

.alert-success {
    border-left-color: #198754;
}

.alert-danger {
    border-left-color: #dc3545;
}

/* Status badges */
.badge.bg-success {
    background-color: rgba(25, 135, 84, 0.1) !important;
    color: #198754 !important;
    border: 1px solid rgba(25, 135, 84, 0.2);
}

.badge.bg-warning {
    background-color: rgba(255, 193, 7, 0.1) !important;
    color: #ffc107 !important;
    border: 1px solid rgba(255, 193, 7, 0.2);
}

.badge.bg-danger {
    background-color: rgba(220, 53, 69, 0.1) !important;
    color: #dc3545 !important;
    border: 1px solid rgba(220, 53, 69, 0.2);
}

/* Stats cards */
.card[class*="bg-"] {
    color: #fff;
    border: none;
    transition: transform 0.2s;
}

.card[class*="bg-"]:hover {
    transform: translateY(-5px);
}

.card[class*="bg-"] .icon-circle {
    background-color: rgba(255, 255, 255, 0.2);
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .table-responsive {
        border: 0;
    }
    
    .table thead {
        display: none;
    }
    
    .table, .table tbody, .table tr, .table td {
        display: block;
        width: 100%;
    }
    
    .table tr {
        margin-bottom: 1rem;
        border: 1px solid #e9ecef;
        border-radius: 0.5rem;
        overflow: hidden;
    }
    
    .table td {
        text-align: right;
        padding-left: 50%;
        position: relative;
        border-bottom: 1px solid #e9ecef;
    }
    
    .table td::before {
        content: attr(data-label);
        position: absolute;
        left: 1rem;
        width: 50%;
        padding-right: 1rem;
        text-align: left;
        font-weight: 600;
        color: #6c757d;
    }
    
    .table td:last-child {
        border-bottom: 0;
    }
    
    .table td .d-flex {
        justify-content: flex-end;
    }
    
    .table td .btn-group {
        width: 100%;
        justify-content: flex-end;
    }
}
</style>

<!-- Action Confirmation Modal -->
<div id="actionModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
    <div class="bg-white rounded-2xl shadow-2xl max-w-md w-full max-h-[90vh] overflow-y-auto">
        <!-- Modal Header -->
        <div class="bg-gradient-to-r from-blue-600 to-blue-700 p-6 text-white rounded-t-2xl" id="actionModalHeader">
            <div class="flex items-center justify-between">
                <h3 class="text-xl font-bold" id="actionModalTitle">
                    <i class="fas fa-question-circle mr-2"></i>Confirm Action
                </h3>
                <button type="button" onclick="closeActionModal()" class="text-white hover:text-gray-200 text-2xl">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        </div>

        <!-- Modal Body -->
        <div class="p-6 text-gray-700">
            <p class="text-lg font-medium mb-3" id="actionModalMessage">Are you sure you want to perform this action?</p>
            <div class="bg-gray-50 rounded-lg p-4 mb-4">
                <p class="text-sm text-gray-600 mb-2"><strong>Comment:</strong></p>
                <p class="text-sm italic" id="actionModalComment">Comment content here...</p>
            </div>
        </div>

        <!-- Modal Footer -->
        <div class="p-6 border-t border-gray-200 flex gap-3 justify-end">
            <button type="button" onclick="closeActionModal()"
                    class="group relative bg-gray-500 hover:bg-gray-600 text-white px-6 py-3 rounded-xl font-semibold transition-all duration-300 flex items-center justify-center shadow-lg hover:shadow-xl transform hover:-translate-y-0.5">
                <div class="absolute inset-0 bg-gradient-to-r from-white/0 via-white/10 to-white/0 opacity-0 group-hover:opacity-100 transition-opacity duration-300 rounded-xl"></div>
                <span class="relative z-10 font-medium">Cancel</span>
            </button>
            <form action="" method="POST" class="inline-block" id="actionForm">
                <input type="hidden" name="comment_id" id="actionCommentId">
                <input type="hidden" name="action" id="actionType">
                <button type="submit" id="actionButton"
                        class="group relative bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-xl font-semibold transition-all duration-300 flex items-center justify-center shadow-lg hover:shadow-xl transform hover:-translate-y-0.5">
                    <div class="absolute inset-0 bg-gradient-to-r from-white/0 via-white/10 to-white/0 opacity-0 group-hover:opacity-100 transition-opacity duration-300 rounded-xl"></div>
                    <i class="fas fa-check mr-2 relative z-10" id="actionIcon"></i>
                    <span class="relative z-10 font-medium" id="actionButtonText">Confirm</span>
                </button>
            </form>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div id="deleteModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
    <div class="bg-white rounded-2xl shadow-2xl max-w-md w-full max-h-[90vh] overflow-y-auto">
        <!-- Modal Header -->
        <div class="bg-gradient-to-r from-red-600 to-red-700 p-6 text-white rounded-t-2xl">
            <div class="flex items-center justify-between">
                <h3 class="text-xl font-bold">
                    <i class="fas fa-exclamation-triangle mr-2"></i>Confirm Deletion
                </h3>
                <button type="button" onclick="closeDeleteModal()" class="text-white hover:text-gray-200 text-2xl">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        </div>

        <!-- Modal Body -->
        <div class="p-6 text-gray-700">
            <p class="text-lg font-medium mb-3">Are you sure you want to delete this comment?</p>
            <div class="bg-red-50 border border-red-200 rounded-lg p-4 mb-4">
                <p class="text-sm text-red-800 mb-2"><strong>Comment to be deleted:</strong></p>
                <p class="text-sm italic" id="deleteModalComment">Comment content here...</p>
            </div>
            <p class="text-sm text-gray-600">This action cannot be undone. The comment will be permanently removed from the system.</p>
        </div>

        <!-- Modal Footer -->
        <div class="p-6 border-t border-gray-200 flex gap-3 justify-end">
            <button type="button" onclick="closeDeleteModal()"
                    class="group relative bg-gray-500 hover:bg-gray-600 text-white px-6 py-3 rounded-xl font-semibold transition-all duration-300 flex items-center justify-center shadow-lg hover:shadow-xl transform hover:-translate-y-0.5">
                <div class="absolute inset-0 bg-gradient-to-r from-white/0 via-white/10 to-white/0 opacity-0 group-hover:opacity-100 transition-opacity duration-300 rounded-xl"></div>
                <span class="relative z-10 font-medium">Cancel</span>
            </button>
            <form action="" method="POST" class="inline-block" id="deleteForm">
                <input type="hidden" name="comment_id" id="deleteCommentId">
                <input type="hidden" name="action" value="delete">
                <button type="submit"
                        class="group relative bg-red-600 hover:bg-red-700 text-white px-6 py-3 rounded-xl font-semibold transition-all duration-300 flex items-center justify-center shadow-lg hover:shadow-xl transform hover:-translate-y-0.5">
                    <div class="absolute inset-0 bg-gradient-to-r from-white/0 via-white/10 to-white/0 opacity-0 group-hover:opacity-100 transition-opacity duration-300 rounded-xl"></div>
                    <i class="fas fa-trash mr-2 relative z-10"></i>
                    <span class="relative z-10 font-medium">Delete Comment</span>
                </button>
            </form>
        </div>
    </div>
</div>

<script>
// Action confirmation functions
function confirmAction(commentId, action, commentContent) {
    const modal = document.getElementById('actionModal');
    const header = document.getElementById('actionModalHeader');
    const title = document.getElementById('actionModalTitle');
    const message = document.getElementById('actionModalMessage');
    const comment = document.getElementById('actionModalComment');
    const button = document.getElementById('actionButton');
    const icon = document.getElementById('actionIcon');
    const buttonText = document.getElementById('actionButtonText');

    // Set modal content based on action
    if (action === 'approve') {
        header.className = 'bg-gradient-to-r from-green-600 to-green-700 p-6 text-white rounded-t-2xl';
        title.innerHTML = '<i class="fas fa-check-circle mr-2"></i>Approve Comment';
        message.textContent = 'Are you sure you want to approve this comment?';
        button.className = 'group relative bg-green-600 hover:bg-green-700 text-white px-6 py-3 rounded-xl font-semibold transition-all duration-300 flex items-center justify-center shadow-lg hover:shadow-xl transform hover:-translate-y-0.5';
        icon.className = 'fas fa-check mr-2 relative z-10';
        buttonText.textContent = 'Approve Comment';
    } else if (action === 'reject') {
        header.className = 'bg-gradient-to-r from-yellow-600 to-yellow-700 p-6 text-white rounded-t-2xl';
        title.innerHTML = '<i class="fas fa-times-circle mr-2"></i>Reject Comment';
        message.textContent = 'Are you sure you want to reject this comment?';
        button.className = 'group relative bg-yellow-600 hover:bg-yellow-700 text-white px-6 py-3 rounded-xl font-semibold transition-all duration-300 flex items-center justify-center shadow-lg hover:shadow-xl transform hover:-translate-y-0.5';
        icon.className = 'fas fa-times mr-2 relative z-10';
        buttonText.textContent = 'Reject Comment';
    }

    comment.textContent = commentContent.length > 100 ? commentContent.substring(0, 100) + '...' : commentContent;

    // Set form values
    document.getElementById('actionCommentId').value = commentId;
    document.getElementById('actionType').value = action;

    // Show modal
    modal.classList.remove('hidden');
    modal.classList.add('animate__fadeIn', 'animate__zoomIn');
}

function confirmDelete(commentId, commentContent) {
    const modal = document.getElementById('deleteModal');
    const comment = document.getElementById('deleteModalComment');

    comment.textContent = commentContent.length > 100 ? commentContent.substring(0, 100) + '...' : commentContent;
    document.getElementById('deleteCommentId').value = commentId;

    // Show modal
    modal.classList.remove('hidden');
    modal.classList.add('animate__fadeIn', 'animate__zoomIn');
}

function closeActionModal() {
    const modal = document.getElementById('actionModal');
    modal.classList.add('hidden');
    modal.classList.remove('animate__fadeIn', 'animate__zoomIn');
}

function closeDeleteModal() {
    const modal = document.getElementById('deleteModal');
    modal.classList.add('hidden');
    modal.classList.remove('animate__fadeIn', 'animate__zoomIn');
}

// Close modals when clicking outside
document.getElementById('actionModal')?.addEventListener('click', function(e) {
    if (e.target === this) {
        closeActionModal();
    }
});

document.getElementById('deleteModal')?.addEventListener('click', function(e) {
    if (e.target === this) {
        closeDeleteModal();
    }
});

// Close modals with Escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeActionModal();
        closeDeleteModal();
    }
});
</script>

<?php include 'includes/footer.php'; ?>
