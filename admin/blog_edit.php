<?php
// Set page title and include header
$page_title = 'Edit Blog Post';
$page_description = 'Edit an existing blog post';

// Include database connection and functions
require_once '../includes/config.php';
require_once 'includes/functions.php';

// Check authentication
checkAuth();

// Get blog post ID
$id = (int)($_GET['id'] ?? 0);
if (!$id) {
    $_SESSION['message'] = ['type' => 'error', 'text' => 'Invalid blog post ID'];
    header('Location: blog.php');
    exit();
}

// Get blog post data
$post = getRecordById('blog_posts', $id);
if (!$post) {
    $_SESSION['message'] = ['type' => 'error', 'text' => 'Blog post not found'];
    header('Location: blog.php');
    exit();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = sanitize($_POST['title'] ?? '');
    $content = sanitize($_POST['content'] ?? '');
    $author = sanitize($_POST['author'] ?? '');
    $status = sanitize($_POST['status'] ?? 'draft');

    // Validate input
    $errors = [];
    if (empty($title)) {
        $errors[] = 'Title is required';
    }
    if (empty($content)) {
        $errors[] = 'Content is required';
    }
    if (empty($author)) {
        $errors[] = 'Author is required';
    }

    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("UPDATE blog_posts SET title = :title, content = :content, author = :author, status = :status WHERE id = :id");
            $result = $stmt->execute([
                ':title' => $title,
                ':content' => $content,
                ':author' => $author,
                ':status' => $status,
                ':id' => $id
            ]);

            if ($result) {
                $_SESSION['message'] = ['type' => 'success', 'text' => 'Blog post updated successfully'];
                header('Location: blog.php');
                exit();
            } else {
                $errors[] = 'Failed to update blog post';
            }
        } catch (PDOException $e) {
            $errors[] = 'Database error: ' . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?> - Admin Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.ckeditor.com/4.20.1/standard/ckeditor.js"></script>
    <style>
        .sidebar {
            transform: translateX(0);
        }
        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
            }
        }
    </style>
</head>
<body class="bg-gray-100">
    <!-- Sidebar -->
    <?php include 'includes/sidebar.php'; ?>

    <!-- Main Content -->
    <div class="lg:ml-64 min-h-screen">
        <!-- Top Navigation -->
        <header class="bg-white shadow-sm border-b border-gray-200">
            <div class="flex items-center justify-between h-16 px-6">
                <div class="flex items-center">
                    <button id="sidebar-toggle" class="lg:hidden mr-4 text-gray-600 hover:text-gray-900">
                        <i class="fas fa-bars text-xl"></i>
                    </button>
                    <nav class="flex items-center space-x-2 text-sm text-gray-600">
                        <a href="blog.php" class="hover:text-gray-900">Blog Management</a>
                        <i class="fas fa-chevron-right text-xs"></i>
                        <span class="text-gray-900 font-medium"><?php echo htmlspecialchars($page_title); ?></span>
                    </nav>
                </div>
                <div class="flex items-center space-x-4">
                    <a href="blog.php" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-lg font-medium transition-colors">
                        <i class="fas fa-times mr-2"></i>Cancel
                    </a>
                </div>
            </div>
        </header>

        <!-- Page Content -->
        <main class="p-6">
            <div class="max-w-4xl mx-auto">
                <div class="bg-white rounded-lg shadow-sm p-6">
                    <h1 class="text-2xl font-bold text-gray-900 mb-6"><?php echo htmlspecialchars($page_title); ?></h1>

                    <?php if (!empty($errors)): ?>
                        <div class="mb-6 bg-red-50 border border-red-200 rounded-lg p-4">
                            <div class="flex items-center">
                                <i class="fas fa-exclamation-circle text-red-400 mr-2"></i>
                                <h3 class="text-sm font-medium text-red-800">Please fix the following errors:</h3>
                            </div>
                            <ul class="mt-2 text-sm text-red-700 list-disc list-inside">
                                <?php foreach ($errors as $error): ?>
                                    <li><?php echo htmlspecialchars($error); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>

                    <form method="POST" class="space-y-6">
                        <div>
                            <label for="title" class="block text-sm font-medium text-gray-700 mb-2">
                                Post Title <span class="text-red-500">*</span>
                            </label>
                            <input type="text"
                                   id="title"
                                   name="title"
                                   value="<?php echo htmlspecialchars($_POST['title'] ?? $post['title']); ?>"
                                   required
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent"
                                   placeholder="Enter an engaging title for your blog post">
                        </div>

                        <div>
                            <label for="author" class="block text-sm font-medium text-gray-700 mb-2">
                                Author <span class="text-red-500">*</span>
                            </label>
                            <input type="text"
                                   id="author"
                                   name="author"
                                   value="<?php echo htmlspecialchars($_POST['author'] ?? $post['author']); ?>"
                                   required
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent"
                                   placeholder="Enter the author's name">
                        </div>

                        <div>
                            <label for="status" class="block text-sm font-medium text-gray-700 mb-2">
                                Status
                            </label>
                            <select id="status"
                                    name="status"
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                                <option value="draft" <?php echo ($_POST['status'] ?? $post['status']) === 'draft' ? 'selected' : ''; ?>>Draft</option>
                                <option value="published" <?php echo ($_POST['status'] ?? $post['status']) === 'published' ? 'selected' : ''; ?>>Published</option>
                            </select>
                        </div>

                        <div>
                            <label for="content" class="block text-sm font-medium text-gray-700 mb-2">
                                Content <span class="text-red-500">*</span>
                            </label>
                            <textarea id="content"
                                      name="content"
                                      rows="12"
                                      required
                                      class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent"
                                      placeholder="Write your blog post content here..."><?php echo htmlspecialchars($_POST['content'] ?? $post['content']); ?></textarea>
                        </div>

                        <div class="flex items-center justify-end space-x-4 pt-6 border-t border-gray-200">
                            <a href="blog.php" class="bg-gray-500 hover:bg-gray-600 text-white px-6 py-2 rounded-lg font-medium transition-colors">
                                Cancel
                            </a>
                            <button type="submit" class="bg-primary hover:bg-primary-hover text-white px-6 py-2 rounded-lg font-medium transition-colors">
                                <i class="fas fa-save mr-2"></i>Update Blog Post
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </main>
    </div>

    <script>
        // Mobile sidebar toggle
        document.getElementById('sidebar-toggle')?.addEventListener('click', function() {
            const sidebar = document.querySelector('.sidebar');
            sidebar.classList.toggle('-translate-x-full');
        });

        // Initialize CKEditor
        CKEDITOR.replace('content', {
            height: 400,
            toolbar: [
                { name: 'basicstyles', items: ['Bold', 'Italic', 'Underline', 'Strike'] },
                { name: 'paragraph', items: ['NumberedList', 'BulletedList', 'Blockquote'] },
                { name: 'links', items: ['Link', 'Unlink'] },
                { name: 'tools', items: ['Maximize'] },
                { name: 'document', items: ['Source'] }
            ]
        });
    </script>
</body>
</html>
