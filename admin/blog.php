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
    if (isset($_POST['delete_post'])) {
        $post_id = (int)$_POST['post_id'];

        try {
            $stmt = $pdo->prepare("DELETE FROM blog_posts WHERE id = ?");
            $stmt->execute([$post_id]);

            // Log activity
            $activityLogger->log('blog', 'deleted', "Deleted blog post", 'blog_post', $post_id);

            $_SESSION['message'] = [
                'type' => 'success',
                'text' => 'Blog post deleted successfully!'
            ];

            header('Location: ' . $_SERVER['PHP_SELF'] . '?' . http_build_query($_GET));
            exit();
        } catch (PDOException $e) {
            $error = 'Error deleting blog post: ' . $e->getMessage();
        }
    }

    if (isset($_POST['add_post'])) {
        $title = sanitize($_POST['title']);
        $content = sanitize($_POST['content']);
        $author = sanitize($_POST['author']);
        $status = sanitize($_POST['status'] ?? 'draft');

        if (empty($title) || empty($content) || empty($author)) {
            $error = 'Please fill in all required fields';
        } else {
            try {
                // Handle image upload
                $image_path = '';
                if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                    $upload_result = uploadFile($_FILES['image'], '../uploads/blog/');
                    if ($upload_result['success']) {
                        $image_path = $upload_result['file_name'];
                    } else {
                        $error = $upload_result['message'];
                    }
                }

                if (empty($error)) {
                    $stmt = $pdo->prepare("INSERT INTO blog_posts (title, content, author, status, image, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
                    $stmt->execute([$title, $content, $author, $status, $image_path]);

                    // Log activity
                    $activityLogger->log('blog', 'created', "Created new blog post: {$title}", 'blog_post', $pdo->lastInsertId());

                    $_SESSION['message'] = [
                        'type' => 'success',
                        'text' => 'Blog post created successfully!'
                    ];

                    header('Location: ' . $_SERVER['PHP_SELF'] . '?' . http_build_query($_GET));
                    exit();
                }
            } catch (PDOException $e) {
                $error = 'Error creating blog post: ' . $e->getMessage();
            }
        }
    }

    if (isset($_POST['update_post'])) {
        $post_id = (int)$_POST['post_id'];
        $title = sanitize($_POST['title']);
        $content = sanitize($_POST['content']);
        $author = sanitize($_POST['author']);
        $status = sanitize($_POST['status'] ?? 'draft');

        if (empty($title) || empty($content) || empty($author)) {
            $error = 'Please fill in all required fields';
        } else {
            try {
                // Get current post data to handle image update
                $stmt = $pdo->prepare("SELECT image FROM blog_posts WHERE id = ?");
                $stmt->execute([$post_id]);
                $current_post = $stmt->fetch(PDO::FETCH_ASSOC);
                $current_image = $current_post['image'] ?? '';

                // Handle image upload
                $image_path = $current_image; // Keep current image by default
                if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                    $upload_result = uploadFile($_FILES['image'], '../uploads/blog/');
                    if ($upload_result['success']) {
                        $image_path = $upload_result['file_name'];
                    } else {
                        $error = $upload_result['message'];
                    }
                }

                if (empty($error)) {
                    $stmt = $pdo->prepare("UPDATE blog_posts SET title = ?, content = ?, author = ?, status = ?, image = ? WHERE id = ?");
                    $stmt->execute([$title, $content, $author, $status, $image_path, $post_id]);

                    // Log activity
                    $activityLogger->log('blog', 'updated', "Updated blog post: {$title}", 'blog_post', $post_id);

                    $_SESSION['message'] = [
                        'type' => 'success',
                        'text' => 'Blog post updated successfully!'
                    ];

                    header('Location: ' . $_SERVER['PHP_SELF'] . '?' . http_build_query($_GET));
                    exit();
                }
            } catch (PDOException $e) {
                $error = 'Error updating blog post: ' . $e->getMessage();
            }
        }
    }
}

// Handle status toggle via GET (for quick status changes)
if (isset($_GET['toggle_status']) && isset($_GET['id'])) {
    $id = (int)$_GET['id'];

    try {
        // Get current status
        $stmt = $pdo->prepare("SELECT status FROM blog_posts WHERE id = ?");
        $stmt->execute([$id]);
        $post = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($post) {
            $new_status = ($post['status'] ?? 'draft') === 'published' ? 'draft' : 'published';
            $stmt = $pdo->prepare("UPDATE blog_posts SET status = ? WHERE id = ?");
            $stmt->execute([$new_status, $id]);

            // Log activity
            $activityLogger->log('blog', 'updated', "Changed blog post status to {$new_status}", 'blog_post', $id);

            $_SESSION['message'] = [
                'type' => 'success',
                'text' => 'Blog post status updated successfully!'
            ];
        }
    } catch (PDOException $e) {
        $_SESSION['message'] = [
            'type' => 'error',
            'text' => 'Error updating blog post status: ' . $e->getMessage()
        ];
    }

    header('Location: blog.php');
    exit();
}

// Get filter parameters
$search = $_GET['search'] ?? '';
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

if (!empty($search)) {
    $where_conditions[] = "(title LIKE :search OR content LIKE :search OR author LIKE :search)";
    $params[':search'] = "%$search%";
}

$where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

// Get total count for pagination
$total_sql = "SELECT COUNT(*) as count FROM blog_posts $where_clause";
$total_stmt = $pdo->prepare($total_sql);
$total_stmt->execute($params);
$total_posts = (int)$total_stmt->fetch(PDO::FETCH_ASSOC)['count'];

// Get blog posts with pagination
$sql = "SELECT * FROM blog_posts $where_clause ORDER BY created_at DESC LIMIT $offset, $per_page";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$blog_posts = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get pagination info
$total_pages = ceil($total_posts / $per_page);

// Set page title
$page_title = 'Blog Management';
$page_description = 'Manage your blog posts and content';

// Include header
require_once 'includes/header.php';
?>

<!-- Blog Header -->
<div class="bg-gradient-to-br from-primary via-primary-dark to-secondary text-white">
    <div class="container mx-auto px-6 py-12">
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between">
            <div class="flex items-center space-x-6">
                <div class="h-20 w-20 rounded-full bg-white/20 backdrop-blur-sm flex items-center justify-center text-white font-bold text-3xl shadow-lg">
                    <i class="fas fa-blog"></i>
                </div>
                <div>
                    <h1 class="text-3xl md:text-4xl font-bold mb-2">Blog Management</h1>
                    <p class="text-lg opacity-90 mb-2">Create and manage your blog content</p>
                    <div class="flex items-center space-x-4">
                        <span class="px-3 py-1 bg-white/20 backdrop-blur-sm rounded-full text-sm font-medium">
                            <?php echo $total_posts; ?> Total Posts
                        </span>
                        <span class="px-3 py-1 bg-white/20 backdrop-blur-sm rounded-full text-sm font-medium">
                            <?php echo count($blog_posts); ?> This Page
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
                <h3 class="text-lg font-semibold text-gray-900 mb-2">Blog Management</h3>
                <p class="text-gray-600">Create, edit, and manage your blog posts</p>
            </div>
            <div class="mt-4 sm:mt-0 flex space-x-3">
                <button onclick="openAddModal()"
                   class="bg-primary hover:bg-primary-dark text-white px-6 py-3 rounded-lg font-semibold transition-colors duration-200 flex items-center">
                    <i class="fas fa-plus mr-2"></i>
                    Add New Post
                </button>
            </div>
        </div>
    </div>

    <!-- Filters and Search -->
    <div class="bg-white rounded-xl shadow-lg p-6 mb-8">
        <form method="GET" class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div>
                <label for="search" class="block text-sm font-semibold text-gray-700 mb-2">Search</label>
                <input type="text" name="search" id="search"
                       value="<?php echo htmlspecialchars($search); ?>"
                       class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent"
                       placeholder="Search by title, content, or author...">
            </div>

            <div>
                <label for="status" class="block text-sm font-semibold text-gray-700 mb-2">Status</label>
                <select name="status" id="status"
                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                    <option value="">All Status</option>
                    <option value="published" <?php echo $status_filter === 'published' ? 'selected' : ''; ?>>Published</option>
                    <option value="draft" <?php echo $status_filter === 'draft' ? 'selected' : ''; ?>>Draft</option>
                </select>
            </div>

            <div class="flex items-end space-x-4">
                <button type="submit" class="bg-primary hover:bg-primary-dark text-white px-6 py-3 rounded-lg font-semibold transition-colors duration-200 flex items-center">
                    <i class="fas fa-search mr-2"></i>
                    Search
                </button>
                <a href="blog.php" class="bg-gray-500 hover:bg-gray-600 text-white px-6 py-3 rounded-lg font-semibold transition-colors duration-200">
                    <i class="fas fa-times mr-2"></i>
                    Clear
                </a>
            </div>
        </form>
    </div>

    <!-- Blog Posts List -->
    <div class="bg-white rounded-xl shadow-lg overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200">
            <h2 class="text-lg font-semibold text-gray-900">Blog Posts (<?php echo $total_posts; ?>)</h2>
        </div>

        <?php if (empty($blog_posts)): ?>
            <div class="p-12 text-center">
                <i class="fas fa-blog text-4xl text-gray-400 mb-4"></i>
                <h3 class="text-lg font-medium text-gray-900 mb-2">No blog posts found</h3>
                <p class="text-gray-500 mb-4">Get started by creating your first blog post.</p>
                <button onclick="openAddModal()" class="bg-primary hover:bg-primary-dark text-white px-6 py-3 rounded-lg font-semibold transition-colors duration-200 flex items-center mx-auto w-fit">
                    <i class="fas fa-plus mr-2"></i>
                    Create First Post
                </button>
            </div>
        <?php else: ?>
            <div class="divide-y divide-gray-200">
                <?php foreach ($blog_posts as $post): ?>
                    <div class="p-6 hover:bg-gray-50 transition-colors duration-200">
                        <div class="flex items-start justify-between">
                            <div class="flex items-start space-x-4 flex-1">
                                <div class="flex-shrink-0">
                                    <div class="h-12 w-12 rounded-full bg-primary flex items-center justify-center text-white font-semibold text-lg">
                                        <?php echo strtoupper(substr($post['title'], 0, 1)); ?>
                                    </div>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center space-x-2 mb-2">
                                        <h3 class="text-lg font-semibold text-gray-900">
                                            <?php echo htmlspecialchars($post['title']); ?>
                                        </h3>
                                        <span class="px-2.5 py-0.5 rounded-full text-xs font-medium <?php echo getStatusBadgeClass($post['status']); ?>">
                                            <?php echo ucfirst($post['status'] ?? 'draft'); ?>
                                        </span>
                                    </div>
                                    <div class="text-sm text-gray-600 mb-3">
                                        <span class="font-medium">By <?php echo htmlspecialchars($post['author'] ?? 'Unknown'); ?></span>
                                        <span class="text-gray-400 mx-2">•</span>
                                        <span class="text-gray-400">
                                            <?php echo date('M j, Y g:i A', strtotime($post['created_at'])); ?>
                                        </span>
                                    </div>
                                    <?php if (!empty($post['image'])): ?>
                                        <div class="mb-3">
                                            <img src="../uploads/blog/<?php echo htmlspecialchars($post['image']); ?>"
                                                 alt="<?php echo htmlspecialchars($post['title']); ?>"
                                                 class="w-full max-w-sm h-32 object-cover rounded-lg border">
                                        </div>
                                    <?php endif; ?>
                                    <div class="bg-gray-50 rounded-lg p-4">
                                        <p class="text-gray-700 line-clamp-3">
                                            <?php echo htmlspecialchars(substr(strip_tags($post['content'] ?? ''), 0, 200) . '...'); ?>
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <div class="flex items-center space-x-2 ml-4">
                                <button onclick="viewBlogPost(<?php echo $post['id']; ?>)"
                                   class="action-btn bg-blue-500 hover:bg-blue-600 text-white px-3 py-2 rounded-lg font-medium transition-colors duration-200 flex items-center space-x-1"
                                   title="View full blog post details">
                                    <i class="fas fa-eye"></i>
                                    <span class="hidden sm:inline">View</span>
                                </button>
                                <button onclick="openEditModal(<?php echo $post['id']; ?>)"
                                   class="action-btn bg-indigo-500 hover:bg-indigo-600 text-white px-3 py-2 rounded-lg font-medium transition-colors duration-200 flex items-center space-x-1"
                                   title="Edit this blog post">
                                    <i class="fas fa-edit"></i>
                                    <span class="hidden sm:inline">Edit</span>
                                </button>
                                <a href="?toggle_status&id=<?php echo $post['id']; ?>"
                                   class="action-btn bg-<?php echo (($post['status'] ?? 'draft') === 'published' ? 'yellow' : 'green'); ?>-500 hover:bg-<?php echo (($post['status'] ?? 'draft') === 'published' ? 'yellow' : 'green'); ?>-600 text-white px-3 py-2 rounded-lg font-medium transition-colors duration-200 flex items-center space-x-1"
                                   title="<?php echo (($post['status'] ?? 'draft') === 'published' ? 'Make this post a draft' : 'Publish this post'); ?>">
                                    <i class="fas fa-<?php echo (($post['status'] ?? 'draft') === 'published' ? 'eye-slash' : 'eye'); ?>"></i>
                                    <span class="hidden sm:inline"><?php echo (($post['status'] ?? 'draft') === 'published' ? 'Draft' : 'Publish'); ?></span>
                                </a>
                                <button onclick="confirmDeletePost(<?php echo $post['id']; ?>, '<?php echo htmlspecialchars(addslashes($post['title'])); ?>')"
                                        class="action-btn bg-red-500 hover:bg-red-600 text-white px-3 py-2 rounded-lg font-medium transition-colors duration-200 flex items-center space-x-1"
                                        title="Permanently delete this blog post">
                                    <i class="fas fa-trash"></i>
                                    <span class="hidden sm:inline">Delete</span>
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
                            Showing <?php echo (($page - 1) * $per_page) + 1; ?> to <?php echo min($page * $per_page, $total_posts); ?> of <?php echo $total_posts; ?> results
                        </div>
                        <div class="flex space-x-1">
                            <?php if ($page > 1): ?>
                                <a href="?page=<?php echo $page - 1; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo !empty($status_filter) ? '&status=' . urlencode($status_filter) : ''; ?>"
                                   class="px-3 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                                    Previous
                                </a>
                            <?php endif; ?>

                            <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                                <a href="?page=<?php echo $i; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo !empty($status_filter) ? '&status=' . urlencode($status_filter) : ''; ?>"
                                   class="px-3 py-2 border <?php echo $i === $page ? 'bg-primary text-white' : 'border-gray-300 text-gray-700 bg-white hover:bg-gray-50'; ?> rounded-md text-sm font-medium">
                                    <?php echo $i; ?>
                                </a>
                            <?php endfor; ?>

                            <?php if ($page < $total_pages): ?>
                                <a href="?page=<?php echo $page + 1; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo !empty($status_filter) ? '&status=' . urlencode($status_filter) : ''; ?>"
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

<!-- Add Blog Post Modal -->
<div id="addModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden z-50">
    <div class="flex items-center justify-center min-h-screen p-4">
        <div class="bg-white rounded-lg shadow-xl max-w-4xl w-full max-h-screen overflow-y-auto">
            <div class="px-6 py-4 border-b border-gray-200">
                <div class="flex items-center justify-between">
                    <h3 class="text-lg font-semibold text-gray-900">Add New Blog Post</h3>
                    <button onclick="closeAddModal()" class="text-gray-400 hover:text-gray-600">
                        <i class="fas fa-times text-xl"></i>
                    </button>
                </div>
            </div>
            <form method="POST" enctype="multipart/form-data" class="p-6" id="addForm">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                    <div>
                        <label for="add_title" class="block text-sm font-semibold text-gray-700 mb-2">Post Title *</label>
                        <input type="text" name="title" id="add_title" required
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent"
                               placeholder="Enter an engaging title for your blog post">
                    </div>

                    <div>
                        <label for="add_author" class="block text-sm font-semibold text-gray-700 mb-2">Author *</label>
                        <input type="text" name="author" id="add_author" required
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent"
                               placeholder="Enter the author's name">
                    </div>
                </div>

                <div class="mb-6">
                    <label for="add_status" class="block text-sm font-semibold text-gray-700 mb-2">Status</label>
                    <select name="status" id="add_status"
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                        <option value="draft">Draft</option>
                        <option value="published">Published</option>
                    </select>
                </div>

                <div class="mb-6">
                    <label for="add_image" class="block text-sm font-semibold text-gray-700 mb-2">Featured Image</label>
                    <input type="file" name="image" id="add_image" accept="image/*"
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                    <p class="text-xs text-gray-500 mt-1">Upload a featured image for your blog post (optional)</p>
                    <div id="add_image_preview" class="mt-3 hidden">
                        <img id="add_preview_img" src="" alt="Preview" class="max-w-full h-48 object-cover rounded-lg border">
                    </div>
                </div>

                <div class="mb-6">
                    <label for="add_content" class="block text-sm font-semibold text-gray-700 mb-2">Content *</label>
                    <textarea name="content" id="add_content" rows="8" required
                              class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent"
                              placeholder="Write your blog post content here..."></textarea>
                </div>

                <div class="flex justify-end space-x-4">
                    <button type="button" onclick="closeAddModal()"
                            class="px-6 py-3 border border-gray-300 rounded-lg font-semibold text-gray-700 hover:bg-gray-50 transition-colors duration-200">
                        Cancel
                    </button>
                    <button type="submit" name="add_post"
                            class="bg-primary hover:bg-primary-dark text-white px-6 py-3 rounded-lg font-semibold transition-colors duration-200">
                        <i class="fas fa-save mr-2"></i>
                        Create Blog Post
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- View Blog Post Modal -->
<div id="viewModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden z-50">
    <div class="flex items-center justify-center min-h-screen p-4">
        <div class="bg-white rounded-lg shadow-xl max-w-5xl w-full max-h-screen overflow-y-auto">
            <div class="px-6 py-4 border-b border-gray-200">
                <div class="flex items-center justify-between">
                    <h3 class="text-lg font-semibold text-gray-900">Blog Post Details</h3>
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

<!-- Edit Blog Post Modal -->
<div id="editModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden z-50">
    <div class="flex items-center justify-center min-h-screen p-4">
        <div class="bg-white rounded-lg shadow-xl max-w-4xl w-full max-h-screen overflow-y-auto">
            <div class="px-6 py-4 border-b border-gray-200">
                <div class="flex items-center justify-between">
                    <h3 class="text-lg font-semibold text-gray-900">Edit Blog Post</h3>
                    <button onclick="closeEditModal()" class="text-gray-400 hover:text-gray-600">
                        <i class="fas fa-times text-xl"></i>
                    </button>
                </div>
            </div>
            <form method="POST" enctype="multipart/form-data" class="p-6" id="editForm">
                <input type="hidden" name="post_id" id="edit_post_id">

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                    <div>
                        <label for="edit_title" class="block text-sm font-semibold text-gray-700 mb-2">Post Title *</label>
                        <input type="text" name="title" id="edit_title" required
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                    </div>

                    <div>
                        <label for="edit_author" class="block text-sm font-semibold text-gray-700 mb-2">Author *</label>
                        <input type="text" name="author" id="edit_author" required
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                    </div>
                </div>

                <div class="mb-6">
                    <label for="edit_status" class="block text-sm font-semibold text-gray-700 mb-2">Status</label>
                    <select name="status" id="edit_status"
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                        <option value="draft">Draft</option>
                        <option value="published">Published</option>
                    </select>
                </div>

                <div class="mb-6">
                    <label for="edit_image" class="block text-sm font-semibold text-gray-700 mb-2">Featured Image</label>
                    <input type="file" name="image" id="edit_image" accept="image/*"
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                    <p class="text-xs text-gray-500 mt-1">Upload a new featured image or keep current image</p>
                    <div id="edit_image_preview" class="mt-3">
                        <img id="edit_preview_img" src="" alt="Current Image" class="max-w-full h-48 object-cover rounded-lg border hidden">
                        <p id="edit_current_image_text" class="text-sm text-gray-600 mt-2"></p>
                    </div>
                </div>

                <div class="mb-6">
                    <label for="edit_content" class="block text-sm font-semibold text-gray-700 mb-2">Content *</label>
                    <textarea name="content" id="edit_content" rows="8" required
                              class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent"></textarea>
                </div>

                <div class="flex justify-end space-x-4">
                    <button type="button" onclick="closeEditModal()"
                            class="px-6 py-3 border border-gray-300 rounded-lg font-semibold text-gray-700 hover:bg-gray-50 transition-colors duration-200">
                        Cancel
                    </button>
                    <button type="submit" name="update_post"
                            class="bg-primary hover:bg-primary-dark text-white px-6 py-3 rounded-lg font-semibold transition-colors duration-200">
                        <i class="fas fa-save mr-2"></i>
                        Update Blog Post
                    </button>
                </div>
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
                <button type="button" onclick="document.getElementById('deleteModal').classList.add('hidden'); document.getElementById('deleteModal').classList.remove('animate__fadeIn', 'animate__zoomIn');" class="text-white hover:text-gray-200 text-2xl">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        </div>

        <!-- Modal Body -->
        <div class="p-6 text-gray-700">
            <p class="text-lg font-medium mb-3">Are you sure you want to delete blog post "<span id="deletePostName" class="font-bold text-red-600"></span>"?</p>
            <p>This action cannot be undone. All associated data will be permanently removed.</p>
        </div>

        <!-- Modal Footer -->
        <div class="p-6 border-t border-gray-200 flex gap-3 justify-end">
            <button type="button" onclick="document.getElementById('deleteModal').classList.add('hidden'); document.getElementById('deleteModal').classList.remove('animate__fadeIn', 'animate__zoomIn');"
                    class="group relative bg-gray-500 hover:bg-gray-600 text-white px-6 py-3 rounded-xl font-semibold transition-all duration-300 flex items-center justify-center shadow-lg hover:shadow-xl transform hover:-translate-y-0.5">
                <div class="absolute inset-0 bg-gradient-to-r from-white/0 via-white/10 to-white/0 opacity-0 group-hover:opacity-100 transition-opacity duration-300 rounded-xl"></div>
                <span class="relative z-10 font-medium">Cancel</span>
            </button>
            <form action="" method="POST" class="inline-block" id="deletePostForm">
                <input type="hidden" name="post_id" id="deletePostId">
                <button type="submit" name="delete_post"
                        class="group relative bg-red-600 hover:bg-red-700 text-white px-6 py-3 rounded-xl font-semibold transition-all duration-300 flex items-center justify-center shadow-lg hover:shadow-xl transform hover:-translate-y-0.5">
                    <div class="absolute inset-0 bg-gradient-to-r from-white/0 via-white/10 to-white/0 opacity-0 group-hover:opacity-100 transition-opacity duration-300 rounded-xl"></div>
                    <i class="fas fa-trash mr-2 relative z-10"></i>
                    <span class="relative z-10 font-medium">Delete Post</span>
                </button>
            </form>
        </div>
    </div>
</div>

<style>
.blog-card {
    transition: all 0.2s ease;
}

.blog-card:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

/* Status badge styling - Direct CSS instead of @apply */
.status-badge {
    padding: 0.625rem 0.5rem; /* px-2.5 py-0.5 */
    border-radius: 9999px; /* rounded-full */
    font-size: 0.75rem; /* text-xs */
    font-weight: 500; /* font-medium */
    display: inline-block;
}

.status-published {
    background-color: rgb(220 252 231); /* bg-green-100 */
    color: rgb(22 163 74); /* text-green-800 */
}

.status-draft {
    background-color: rgb(243 244 246); /* bg-gray-100 */
    color: rgb(31 41 55); /* text-gray-800 */
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

/* Enhanced button styling - Compatible with Tailwind */
.action-btn {
    transition: transform 0.2s ease, box-shadow 0.2s ease;
    min-width: 5rem; /* 80px */
    font-size: 0.875rem; /* text-sm */
    font-weight: 500; /* font-medium */
}

/* Only apply custom hover effects if not already handled by Tailwind */
.action-btn:not([class*="hover:"]):hover {
    transform: translateY(-0.25rem);
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
        min-width: 2.5rem; /* 40px */
        padding: 0.5rem;
    }
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
// Blog posts data for JavaScript modals
const blogPosts = <?= json_encode($blog_posts) ?>;

// Blog management modals
function openAddModal() {
    document.getElementById('addModal').classList.remove('hidden');
    document.getElementById('add_title').focus();

    // Setup image preview for add modal
    document.getElementById('add_image').addEventListener('change', function(e) {
        const file = e.target.files[0];
        const preview = document.getElementById('add_image_preview');
        const img = document.getElementById('add_preview_img');

        if (file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                img.src = e.target.result;
                preview.classList.remove('hidden');
            };
            reader.readAsDataURL(file);
        } else {
            preview.classList.add('hidden');
        }
    });
}

function closeAddModal() {
    document.getElementById('addModal').classList.add('hidden');
    document.getElementById('addForm').reset();
    document.getElementById('add_image_preview').classList.add('hidden');
}

function viewBlogPost(postId) {
    // Get post data from the blogPosts array
    const post = blogPosts.find(p => p.id == postId);
    if (!post) return;

    // Create view modal content
    const modalContent = document.getElementById('viewModalContent');
    modalContent.innerHTML = `
        <div class="space-y-6">
            <div class="text-center">
                <h2 class="text-2xl font-bold text-gray-900 mb-2">${post.title}</h2>
                <div class="flex items-center justify-center space-x-4 text-sm text-gray-600">
                    <span>By ${post.author}</span>
                    <span>•</span>
                    <span>${new Date(post.created_at).toLocaleDateString('en-US', {
                        year: 'numeric',
                        month: 'long',
                        day: 'numeric',
                        hour: '2-digit',
                        minute: '2-digit'
                    })}</span>
                    <span>•</span>
                    <span class="px-2 py-1 rounded-full text-xs font-medium ${post.status === 'published' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'}">
                        ${post.status === 'published' ? 'Published' : 'Draft'}
                    </span>
                </div>
            </div>

            ${post.image ? `
            <div class="text-center">
                <img src="../uploads/blog/${post.image}" alt="${post.title}" class="max-w-full h-64 object-cover rounded-lg mx-auto">
            </div>
            ` : ''}

            <div class="bg-gray-50 rounded-lg p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Content</h3>
                <div class="prose prose-gray max-w-none">
                    ${post.content}
                </div>
            </div>

            <div class="flex justify-center space-x-4">
                <button onclick="openEditModal(${post.id})"
                        class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg font-medium transition-colors">
                    <i class="fas fa-edit mr-2"></i>Edit Post
                </button>
                <a href="?toggle_status&id=${post.id}"
                   class="bg-${post.status === 'published' ? 'yellow' : 'green'}-600 hover:bg-${post.status === 'published' ? 'yellow' : 'green'}-700 text-white px-6 py-2 rounded-lg font-medium transition-colors"
                   onclick="return confirm('Are you sure you want to ${post.status === 'published' ? 'unpublish' : 'publish'} this post?')">
                    <i class="fas fa-${post.status === 'published' ? 'eye-slash' : 'eye'} mr-2"></i>
                    ${post.status === 'published' ? 'Unpublish' : 'Publish'}
                </a>
                <button onclick="closeViewModal()"
                        class="bg-gray-500 hover:bg-gray-600 text-white px-6 py-2 rounded-lg font-medium transition-colors">
                    <i class="fas fa-times mr-2"></i>Close
                </button>
            </div>
        </div>
    `;

    // Show the modal
    document.getElementById('viewModal').classList.remove('hidden');
}

function openEditModal(postId) {
    // Get post data from the blogPosts array
    const post = blogPosts.find(p => p.id == postId);
    if (!post) return;

    // Populate edit form
    document.getElementById('edit_post_id').value = post.id;
    document.getElementById('edit_title').value = post.title;
    document.getElementById('edit_author').value = post.author;
    document.getElementById('edit_status').value = post.status || 'draft';
    document.getElementById('edit_content').value = post.content;

    // Handle current image display
    const editPreview = document.getElementById('edit_image_preview');
    const editImg = document.getElementById('edit_preview_img');
    const editText = document.getElementById('edit_current_image_text');

    if (post.image) {
        editImg.src = '../uploads/blog/' + post.image;
        editImg.classList.remove('hidden');
        editText.textContent = 'Current: ' + post.image;
    } else {
        editImg.classList.add('hidden');
        editText.textContent = 'No image uploaded';
    }

    // Setup image preview for edit modal
    document.getElementById('edit_image').addEventListener('change', function(e) {
        const file = e.target.files[0];
        const preview = document.getElementById('edit_image_preview');
        const img = document.getElementById('edit_preview_img');
        const text = document.getElementById('edit_current_image_text');

        if (file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                img.src = e.target.result;
                img.classList.remove('hidden');
                text.textContent = 'New image: ' + file.name;
            };
            reader.readAsDataURL(file);
        } else {
            // Reset to current image if no new file selected
            if (post.image) {
                img.src = '../uploads/blog/' + post.image;
                img.classList.remove('hidden');
                text.textContent = 'Current: ' + post.image;
            } else {
                img.classList.add('hidden');
                text.textContent = 'No image uploaded';
            }
        }
    });

    document.getElementById('editModal').classList.remove('hidden');
}

function closeEditModal() {
    document.getElementById('editModal').classList.add('hidden');
    document.getElementById('editForm').reset();

    // Remove event listeners
    document.getElementById('edit_image').replaceWith(document.getElementById('edit_image').cloneNode(true));
}

function confirmDeletePost(id, title) {
    document.getElementById('deletePostId').value = id;
    document.getElementById('deletePostName').textContent = title;
    document.getElementById('deleteModal').classList.remove('hidden');
}

// Close modal when clicking outside
document.getElementById('addModal')?.addEventListener('click', function(e) {
    if (e.target === this) {
        closeAddModal();
    }
});

document.getElementById('editModal')?.addEventListener('click', function(e) {
    if (e.target === this) {
        closeEditModal();
    }
});

document.getElementById('viewModal')?.addEventListener('click', function(e) {
    if (e.target === this) {
        closeViewModal();
    }
});

document.getElementById('deleteModal')?.addEventListener('click', function(e) {
    if (e.target === this) {
        this.classList.add('hidden');
    }
});

// Close modals with Escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeAddModal();
        closeEditModal();
        closeViewModal();
        document.getElementById('deleteModal')?.classList.add('hidden');
    }
});
</script>

<?php
// Include footer
require_once 'includes/footer.php';
?>