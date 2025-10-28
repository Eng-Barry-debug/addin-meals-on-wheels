<?php
// Start session at the very beginning
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'includes/config.php';

// Debug logging function
if (!function_exists('debug_log')) {
    function debug_log($message) {
        $log_file = __DIR__ . '/debug.log';
        $timestamp = date('Y-m-d H:i:s');
        file_put_contents($log_file, "[$timestamp] $message\n", FILE_APPEND);
    }
}

// Include admin config for authentication functions
if (file_exists(__DIR__ . '/admin/includes/config.php')) {
    require_once __DIR__ . '/admin/includes/config.php';
}

// Check if user is logged in
if (!function_exists('isLoggedIn')) {
    function isLoggedIn() {
        return isset($_SESSION['user_id']) && isset($_SESSION['user_status']) && $_SESSION['user_status'] === 'active';
    }
}

// Debug current session
if (isset($_GET['debug_session'])) {
    echo '<pre>';
    echo 'Session Status: ' . session_status() . "\n";
    echo 'Session ID: ' . session_id() . "\n";
    echo 'Session Data: ';
    print_r($_SESSION);
    echo '</pre>';
    exit;
}

// Ensure output buffering is on to prevent headers already sent errors
if (!ob_get_level()) {
    ob_start();
}


// Check if post ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    debug_log('No valid post ID provided in URL');
    header('Location: /blog.php');
    exit();
}

$post_id = (int)$_GET['id'];

try {
    // Fetch the blog post with like and comment counts
    $stmt = $pdo->prepare("
        SELECT 
            p.*, 
            p.author as author_name,
            (SELECT COUNT(*) FROM blog_likes WHERE post_id = p.id) as like_count,
            (SELECT COUNT(*) FROM blog_comments WHERE post_id = p.id AND status = 'approved') as comment_count
        FROM blog_posts p
        WHERE p.id = :id
    ");
    $stmt->execute([':id' => $post_id]);
    $post = $stmt->fetch(PDO::FETCH_ASSOC);

    // Debug: Log post fetch result
    debug_log('Fetched post: ' . ($post ? 'Found' : 'Not found'));
    if ($post) {
        debug_log('Post status: ' . ($post['status'] ?? 'status_not_set'));
        debug_log('Post title: ' . ($post['title'] ?? 'no_title'));
    }

    // If post not found, redirect to blog page
    if (!$post) {
        debug_log('Post not found, redirecting to blog.php');
        header('Location: /blog.php');
        exit();
    }
    
    // Check if post is published
    if (($post['status'] ?? '') !== 'published') {
        debug_log('Post is not published, status: ' . ($post['status'] ?? 'not_set'));
        // Uncomment the next line to redirect if post is not published
        // header('Location: /blog.php');
        // exit();
    }

    // Update view count
    $update_stmt = $pdo->prepare("UPDATE blog_posts SET views = views + 1 WHERE id = :id");
    $update_stmt->execute([':id' => $post_id]);

    // Fetch related posts (excluding current post)
    $related_stmt = $pdo->prepare("
        SELECT id, title, image, created_at
        FROM blog_posts
        WHERE id != :post_id
        ORDER BY created_at DESC
        LIMIT 3
    ");
    $related_stmt->execute([':post_id' => $post_id]);
    $related_posts = $related_stmt->fetchAll(PDO::FETCH_ASSOC);

    // Set page title for header
    $page_title = htmlspecialchars($post['title']) . ' - Addins Meals on Wheels';

} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    header('Location: /blog.php');
    exit();
}

// Get current URL for sharing
$current_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
?>

<?php include 'includes/header.php'; ?>

<!-- Layout Fix CSS -->
<style>
/* Fix for layout overflow and content visibility */
.container {
    overflow-x: hidden;
}

.main-content-wrapper {
    min-height: 100vh;
    display: flex;
    flex-direction: column;
}

.content-section {
    flex: 1 0 auto;
}

/* Ensure proper content flow */
#article-content {
    overflow: visible;
}

/* Fix for prose content */
.prose {
    max-width: 100% !important;
}

/* Ensure footer visibility */
.site-footer {
    position: relative;
    z-index: 10;
    flex-shrink: 0;
}

/* Fix dropdown menu positioning */
.dropdown-menu {
    z-index: 10001 !important;
}

/* Fix header dropdown positioning */
nav .relative {
    position: static !important;
}

nav .relative .absolute {
    z-index: 10001 !important;
    position: fixed !important;
    top: 80px !important; /* Position below the header */
    right: 20px !important; /* Align with the right side */
    left: auto !important;
    margin-top: 0 !important;
    transform: none !important;
}

/* Clear floats in blog content */
.blog-content::after {
    content: "";
    display: table;
    clear: both;
}

/* Fix image overflow */
.blog-content img {
    max-width: 100%;
    height: auto;
}

/* Ensure proper section spacing */
.section-spacing {
    margin-bottom: 3rem;
}

/* Reading progress bar */
#progress-bar {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 3px;
    background: #f3f4f6;
    z-index: 10000;
}

#progress-fill {
    height: 100%;
    background: linear-gradient(to right, #fc7703, #D4AF37);
    width: 0%;
    transition: width 0.3s ease;
}

/* Blog content styles */
.blog-content {
    font-family: 'Merriweather', 'Georgia', serif;
    line-height: 1.75;
    color: #374151;
    overflow: visible;
}

.blog-content h1 {
    font-size: 2.5rem;
    font-weight: bold;
    color: #1f2937;
    margin-top: 3rem;
    margin-bottom: 1.5rem;
    font-family: 'Georgia', serif;
}

.blog-content h2 {
    font-size: 2rem;
    font-weight: bold;
    color: #1f2937;
    margin-top: 2.5rem;
    margin-bottom: 1rem;
    font-family: 'Georgia', serif;
}

.blog-content h3 {
    font-size: 1.5rem;
    font-weight: 600;
    color: #374151;
    margin-top: 2rem;
    margin-bottom: 1rem;
    font-family: 'Georgia', serif;
}

.blog-content p {
    font-size: 1.125rem;
    color: #374151;
    margin-bottom: 1.5rem;
    line-height: 1.75;
}

.blog-content ul, .blog-content ol {
    margin-bottom: 1.5rem;
    padding-left: 1.5rem;
}

.blog-content li {
    margin-bottom: 0.5rem;
}

.blog-content blockquote {
    border-left: 4px solid #fc7703;
    padding-left: 1.5rem;
    margin: 2rem 0;
    font-style: italic;
    background: linear-gradient(to right, rgba(252, 119, 3, 0.05), rgba(212, 175, 55, 0.05));
    padding: 1rem 1.5rem;
}

.blog-content img {
    max-width: 100%;
    height: auto;
    border-radius: 0.75rem;
    margin: 2rem 0;
}

.blog-content a {
    color: #fc7703;
    text-decoration: underline;
}

.blog-content a:hover {
    color: #e56a02;
}

.blog-content code {
    background: #f3f4f6;
    padding: 0.25rem 0.5rem;
    border-radius: 0.375rem;
    font-family: 'Monaco', 'Consolas', monospace;
}

.blog-content pre {
    background: #1f2937;
    color: white;
    padding: 1.5rem;
    border-radius: 0.75rem;
    overflow-x: auto;
    margin: 2rem 0;
}

.blog-content table {
    width: 100%;
    border-collapse: collapse;
    margin: 2rem 0;
}

.blog-content th, .blog-content td {
    padding: 0.75rem;
    border: 1px solid #e5e7eb;
    text-align: left;
}

.blog-content th {
    background: #f9fafb;
    font-weight: 600;
}
</style>

<div class="main-content-wrapper">
    <!-- Reading Progress Bar -->
    <div id="progress-bar">
        <div id="progress-fill"></div>
    </div>

    <!-- Hero Section -->
    <section class="relative py-20 md:py-32 flex items-center justify-center overflow-hidden content-section">
        <!-- Dynamic background based on post image or fallback -->
        <div class="absolute inset-0 bg-cover bg-center" style="background-image: url('<?php echo !empty($post['image']) ? "uploads/blog/" . htmlspecialchars($post['image']) : "https://images.unsplash.com/photo-1481627834876-b7833e8f5570?ixlib=rb-4.0.3&auto=format&fit=crop&w=2048&q=80"; ?>');">
            <!-- Enhanced overlay for better text visibility -->
            <div class="absolute inset-0 bg-gradient-to-br from-black/80 via-black/70 to-black/75"></div>
            <div class="absolute inset-0 bg-gradient-to-t from-black/60 via-black/20 to-black/50"></div>
        </div>

        <div class="relative z-10 w-full">
            <div class="container mx-auto px-4">
                <div class="max-w-6xl mx-auto">
                    <!-- Back to Blog Link -->
                    <div class="max-w-5xl mx-auto">
                        <a href="blog.php" class="inline-flex items-center text-white/80 hover:text-white transition-colors group mb-6">
                            <i class="fas fa-arrow-left mr-2 group-hover:-translate-x-1 transition-transform"></i>
                            Back to Blog
                        </a>
                    </div>

                    <!-- Main Content Card -->
                    <div class="bg-white/95 backdrop-blur-sm rounded-3xl shadow-2xl p-8 md:p-12 max-w-4xl mx-auto">
                        <!-- Article Meta -->
                        <div class="flex flex-wrap items-center gap-4 text-sm text-gray-600 mb-6">
                            <div class="flex items-center">
                                <i class="far fa-calendar-alt mr-2 text-primary"></i>
                                <span><?php echo date('M j, Y', strtotime($post['created_at'])); ?></span>
                            </div>
                            <div class="flex items-center">
                                <i class="far fa-clock mr-2 text-primary"></i>
                                <span>
                                    <?php
                                    $word_count = str_word_count(strip_tags($post['content']));
                                    $reading_time = ceil($word_count / 200);
                                    echo $reading_time > 1 ? $reading_time . ' min read' : '1 min read';
                                    ?>
                                </span>
                            </div>
                            <div class="flex items-center">
                                <i class="far fa-eye mr-2 text-primary"></i>
                                <span><?php echo number_format($post['views'] + 1); ?> views</span>
                            </div>
                        </div>

                        <!-- Article Title -->
                        <h1 class="text-4xl md:text-5xl lg:text-6xl font-bold mb-6 text-gray-900 leading-tight">
                            <?php echo htmlspecialchars($post['title']); ?>
                        </h1>

                        <!-- Article Excerpt -->
                        <?php if (!empty($post['excerpt'])): ?>
                        <p class="text-xl md:text-2xl text-gray-700 mb-8 leading-relaxed max-w-3xl">
                            <?php echo htmlspecialchars($post['excerpt']); ?>
                        </p>
                        <?php endif; ?>

                        <!-- Author Info -->
                        <div class="flex items-center justify-between flex-wrap gap-4">
                            <div class="flex items-center">
                                <div class="w-16 h-16 rounded-full bg-gradient-to-br from-primary to-secondary flex items-center justify-center text-white font-bold text-xl mr-4 shadow-lg">
                                    <?php echo strtoupper(substr($post['author'] ?? 'A', 0, 1)); ?>
                                </div>
                                <div>
                                    <p class="font-semibold text-lg text-gray-900"><?php echo htmlspecialchars($post['author'] ?? 'Admin'); ?></p>
                                    <p class="text-gray-600">Food Writer & Recipe Developer</p>
                                </div>
                            </div>

                            <!-- Share Buttons -->
                            <div class="hidden md:flex items-center space-x-3">
                                <?php
                                $share_url = urlencode($current_url);
                                $share_title = urlencode($post['title']);
                                ?>
                                <span class="text-gray-600 text-sm mr-2">Share:</span>
                                <a href="https://www.facebook.com/sharer/sharer.php?u=<?php echo $share_url; ?>"
                                   target="_blank"
                                   rel="noopener noreferrer"
                                   class="w-10 h-10 bg-blue-600 text-white rounded-full flex items-center justify-center hover:bg-blue-700 transition-colors"
                                   aria-label="Share on Facebook">
                                    <i class="fab fa-facebook-f text-sm"></i>
                                </a>
                                <a href="https://twitter.com/intent/tweet?url=<?php echo $share_url; ?>&text=<?php echo $share_title; ?>"
                                   target="_blank"
                                   rel="noopener noreferrer"
                                   class="w-10 h-10 bg-blue-400 text-white rounded-full flex items-center justify-center hover:bg-blue-500 transition-colors"
                                   aria-label="Share on Twitter">
                                    <i class="fab fa-twitter text-sm"></i>
                                </a>
                                <a href="https://www.linkedin.com/sharing/share-offsite/?url=<?php echo $share_url; ?>"
                                   target="_blank"
                                   rel="noopener noreferrer"
                                   class="w-10 h-10 bg-blue-700 text-white rounded-full flex items-center justify-center hover:bg-blue-800 transition-colors"
                                   aria-label="Share on LinkedIn">
                                    <i class="fab fa-linkedin-in text-sm"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Article Content Section -->
    <section class="py-12 md:py-16 bg-gradient-to-br from-gray-50 via-white to-gray-100 relative content-section">
        <!-- Subtle background pattern -->
        <div class="absolute inset-0 opacity-5">
            <div class="absolute inset-0" style="background-image: radial-gradient(circle at 2px 2px, rgba(252, 119, 3, 0.3) 1px, transparent 0); background-size: 40px 40px;"></div>
        </div>
        
        <div class="container mx-auto px-4 relative z-10">
            <div class="max-w-6xl mx-auto">
                <div class="max-w-5xl mx-auto">
                    <!-- Main Article Content -->
                    <article class="bg-white rounded-2xl shadow-xl overflow-hidden section-spacing">
                        <!-- Featured Image -->
                        <?php if (!empty($post['image'])): ?>
                            <div class="relative h-96 overflow-hidden">
                                <img src="uploads/blog/<?php echo htmlspecialchars($post['image']); ?>"
                                     alt="<?php echo htmlspecialchars($post['title']); ?>"
                                     class="w-full h-full object-cover">
                                <div class="absolute inset-0 bg-gradient-to-t from-black/30 to-transparent"></div>
                            </div>
                        <?php endif; ?>

                        <!-- Article Content -->
                        <div id="article-content" class="p-8 lg:p-12">
                            <div class="prose prose-lg max-w-none">
                                <div class="blog-content">
                                    <?php echo $post['content']; ?>
                                </div>

                                <!-- Like and Share Buttons -->
                                <div class="flex items-center justify-between mt-12 pt-6 border-t border-gray-200 flex-wrap gap-4">
                                    <div class="flex items-center space-x-4">
                                        <?php if (function_exists('isLoggedIn') && isLoggedIn()): ?>
                                            <?php
                                            $isLiked = false;
                                            $stmt = $pdo->prepare("SELECT id FROM blog_likes WHERE post_id = ? AND user_id = ?");
                                            $stmt->execute([$post['id'], $_SESSION['user_id']]);
                                            $isLiked = $stmt->rowCount() > 0;
                                            ?>
                                            <button id="like-button"
                                                    class="flex items-center space-x-2 px-4 py-2 rounded-full transition-colors duration-200 <?php echo $isLiked ? 'bg-red-100 text-red-600 hover:bg-red-200' : 'bg-gray-100 hover:bg-gray-200'; ?>"
                                                    data-post-id="<?php echo $post['id']; ?>">
                                                <i class="fas fa-heart"></i>
                                                <span class="like-count"><?php echo (int)$post['like_count']; ?></span>
                                            </button>
                                        <?php else: ?>
                                            <button onclick="showLoginAlert()" 
                                                    class="flex items-center space-x-2 px-4 py-2 rounded-full bg-gray-100 hover:bg-gray-200 cursor-pointer">
                                                <i class="far fa-heart"></i>
                                                <span class="like-count"><?php echo (int)$post['like_count']; ?></span>
                                            </button>
                                        <?php endif; ?>
                                        <span class="text-gray-500">•</span>
                                        <div class="flex items-center text-gray-600">
                                            <i class="far fa-comment mr-2"></i>
                                            <span id="comment-display-count"><?php echo (int)$post['comment_count']; ?> comments</span>
                                        </div>
                                    </div>
                                    <div class="flex space-x-2">
                                        <button onclick="shareOnTwitter()" class="p-2 rounded-full bg-blue-50 text-blue-500 hover:bg-blue-100 transition-colors" aria-label="Share on Twitter">
                                            <i class="fab fa-twitter"></i>
                                        </button>
                                        <button onclick="shareOnFacebook()" class="p-2 rounded-full bg-blue-600 text-white hover:bg-blue-700 transition-colors" aria-label="Share on Facebook">
                                            <i class="fab fa-facebook-f"></i>
                                        </button>
                                        <button onclick="shareOnLinkedIn()" class="p-2 rounded-full bg-blue-700 text-white hover:bg-blue-800 transition-colors" aria-label="Share on LinkedIn">
                                            <i class="fab fa-linkedin-in"></i>
                                        </button>
                                        <button onclick="copyLink()" class="p-2 rounded-full bg-gray-100 text-gray-600 hover:bg-gray-200 transition-colors" aria-label="Copy link">
                                            <i class="fas fa-link"></i>
                                        </button>
                                    </div>
                                </div>

                                <!-- Comments Section -->
                                <div id="comments" class="mt-12 pt-8 border-t border-gray-200">
                                    <h3 class="text-2xl font-bold text-gray-800 mb-6">
                                        Comments <span id="comment-count" class="text-gray-500">(<?php echo (int)$post['comment_count']; ?>)</span>
                                    </h3>

                                    <!-- Comment Form -->
                                    <div class="mb-8 bg-gray-50 p-6 rounded-lg">
                                        <h4 class="text-lg font-medium text-gray-800 mb-4">Leave a comment</h4>
                                        <?php if (function_exists('isLoggedIn') && isLoggedIn()): ?>
                                            <form id="comment-form" class="space-y-4">
                                                <input type="hidden" name="post_id" value="<?php echo $post['id']; ?>">
                                                <div>
                                                    <label for="comment" class="block text-sm font-medium text-gray-700 mb-1">Your Comment</label>
                                                    <textarea id="comment" name="content" rows="4" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent" placeholder="Write your comment here..." required></textarea>
                                                </div>
                                                <button type="submit" class="px-6 py-2 bg-primary text-white rounded-lg hover:bg-primary-dark transition-colors">
                                                    Post Comment
                                                </button>
                                            </form>
                                        <?php else: ?>
                                            <div class="bg-gray-50 p-6 rounded-lg text-center">
                                                <p class="text-gray-600 mb-4">You must be logged in to post a comment.</p>
                                                <a href="/auth/login.php?redirect=<?php echo urlencode($current_url); ?>" class="inline-block px-6 py-2 bg-primary text-white rounded-lg hover:bg-primary-dark transition-colors">
                                                    Login to Comment
                                                </a>
                                            </div>
                                        <?php endif; ?>
                                    </div>

                                    <!-- Comments List -->
                                    <div id="comments-list" class="space-y-6">
                                        <!-- Comments will be loaded dynamically via JavaScript -->
                                        <p class="text-gray-500 text-center py-8">Loading comments...</p>
                                    </div>
                                </div>
                            </div>

                            <!-- Article Footer -->
                            <div class="mt-12 pt-8 border-t border-gray-200">
                                <!-- Tags -->
                                <div class="mb-8">
                                    <h4 class="font-semibold text-gray-900 mb-3">Tags:</h4>
                                    <div class="flex flex-wrap gap-2">
                                        <span class="px-3 py-1 bg-primary/10 text-primary rounded-full text-sm">Recipes</span>
                                        <span class="px-3 py-1 bg-secondary/10 text-secondary rounded-full text-sm">Cooking Tips</span>
                                        <span class="px-3 py-1 bg-accent/10 text-accent rounded-full text-sm">Food Blog</span>
                                    </div>
                                </div>

                                <!-- Social Share (Mobile) -->
                                <div class="md:hidden mb-8">
                                    <h4 class="font-semibold text-gray-900 mb-3">Share this article:</h4>
                                    <div class="flex flex-wrap gap-3">
                                        <?php
                                        $share_url = urlencode($current_url);
                                        $share_title = urlencode($post['title']);
                                        ?>
                                        <a href="https://www.facebook.com/sharer/sharer.php?u=<?php echo $share_url; ?>"
                                           target="_blank"
                                           rel="noopener noreferrer"
                                           class="w-10 h-10 bg-blue-600 text-white rounded-full flex items-center justify-center hover:bg-blue-700 transition-colors">
                                            <i class="fab fa-facebook-f text-sm"></i>
                                        </a>
                                        <a href="https://twitter.com/intent/tweet?url=<?php echo $share_url; ?>&text=<?php echo $share_title; ?>"
                                           target="_blank"
                                           rel="noopener noreferrer"
                                           class="w-10 h-10 bg-blue-400 text-white rounded-full flex items-center justify-center hover:bg-blue-500 transition-colors">
                                            <i class="fab fa-twitter text-sm"></i>
                                        </a>
                                        <a href="https://www.linkedin.com/sharing/share-offsite/?url=<?php echo $share_url; ?>"
                                           target="_blank"
                                           rel="noopener noreferrer"
                                           class="w-10 h-10 bg-blue-700 text-white rounded-full flex items-center justify-center hover:bg-blue-800 transition-colors">
                                            <i class="fab fa-linkedin-in text-sm"></i>
                                        </a>
                                        <button onclick="copyLink()"
                                           class="w-10 h-10 bg-gray-600 text-white rounded-full flex items-center justify-center hover:bg-gray-700 transition-colors">
                                            <i class="fas fa-link text-sm"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </article>

                    <!-- Author Bio Section -->
                    <div id="author-bio" class="mt-12 bg-gradient-to-br from-primary/10 via-secondary/5 to-accent/10 rounded-2xl p-8 relative overflow-hidden section-spacing">
                        <!-- Subtle background pattern -->
                        <div class="absolute inset-0 opacity-10">
                            <div class="absolute inset-0" style="background-image: radial-gradient(circle at 1px 1px, rgba(252, 119, 3, 0.2) 1px, transparent 0); background-size: 30px 30px;"></div>
                        </div>
                        <div class="flex items-start space-x-6 relative z-10">
                            <div class="w-20 h-20 rounded-full bg-gradient-to-br from-primary to-secondary flex items-center justify-center text-white font-bold text-2xl shadow-lg flex-shrink-0">
                                <?php echo strtoupper(substr($post['author'] ?? 'A', 0, 1)); ?>
                            </div>
                            <div class="flex-1">
                                <h3 class="text-2xl font-bold text-gray-900 mb-2">
                                    <meta name="description" content="<?php echo htmlspecialchars($post['meta_description'] ?? 'Read our latest blog post'); ?>">
                                    <?php if (function_exists('isLoggedIn') && isLoggedIn()): ?>
                                    <meta name="user-logged-in" content="1">
                                    <?php endif; ?>
                                    About <?php echo htmlspecialchars($post['author'] ?? 'the Author'); ?>
                                </h3>
                                <p class="text-gray-700 mb-4 leading-relaxed">
                                    Food enthusiast and writer with a passion for sharing delicious recipes and cooking tips. Creating memorable dining experiences one post at a time.
                                </p>
                                <div class="flex items-center space-x-4">
                                    <a href="blog.php" class="text-primary hover:text-primary-dark font-medium transition-colors">More Articles</a>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Related Posts Section -->
                    <?php if (!empty($related_posts)): ?>
                        <div id="related-posts" class="mt-16 section-spacing">
                            <h2 class="text-3xl font-bold text-gray-900 mb-8 text-center">You Might Also Like</h2>
                            <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-8">
                                <?php foreach ($related_posts as $related): ?>
                                    <article class="bg-white rounded-xl shadow-lg overflow-hidden hover:shadow-xl transition-all duration-300 transform hover:-translate-y-1">
                                        <?php if (!empty($related['image'])): ?>
                                            <div class="relative h-48 overflow-hidden">
                                                <a href="blog-single.php?id=<?php echo $related['id']; ?>">
                                                    <img src="uploads/blog/<?php echo htmlspecialchars($related['image']); ?>"
                                                         alt="<?php echo htmlspecialchars($related['title']); ?>"
                                                         class="w-full h-full object-cover transition-transform duration-300 hover:scale-105">
                                                    <div class="absolute inset-0 bg-gradient-to-t from-black/20 to-transparent"></div>
                                                </a>
                                            </div>
                                        <?php endif; ?>
                                        <div class="p-6">
                                            <p class="text-sm text-gray-500 mb-3">
                                                <?php echo date('M j, Y', strtotime($related['created_at'])); ?>
                                            </p>
                                            <h3 class="font-bold text-lg mb-3 text-gray-900 hover:text-primary transition-colors line-clamp-2">
                                                <a href="blog-single.php?id=<?php echo $related['id']; ?>">
                                                    <?php echo htmlspecialchars($related['title']); ?>
                                                </a>
                                            </h3>
                                            <?php if (!empty($related['excerpt'])): ?>
                                                <p class="text-gray-600 text-sm mb-4 line-clamp-2">
                                                    <?php echo htmlspecialchars($related['excerpt']); ?>
                                                </p>
                                            <?php endif; ?>
                                            <a href="blog-single.php?id=<?php echo $related['id']; ?>"
                                               class="inline-flex items-center text-primary font-semibold hover:text-primary-dark transition-colors">
                                                Read More <i class="fas fa-arrow-right ml-2 text-sm"></i>
                                            </a>
                                        </div>
                                    </article>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </section>

    <!-- Enhanced CTA Section -->
    <section class="py-20 bg-gradient-to-br from-primary via-primary-dark to-secondary text-white relative overflow-hidden section-spacing">
        <!-- Background Pattern -->
        <div class="absolute inset-0 opacity-10">
            <div class="absolute inset-0" style="background-image: radial-gradient(circle at 1px 1px, rgba(255,255,255,0.3) 1px, transparent 0); background-size: 20px 20px;"></div>
        </div>

        <div class="container mx-auto px-4 text-center relative z-10">
            <div class="max-w-4xl mx-auto">
                <!-- CTA Icon -->
                <div class="w-20 h-20 bg-white/20 backdrop-blur-sm rounded-full flex items-center justify-center mx-auto mb-8">
                    <i class="fas fa-envelope-open text-3xl text-white"></i>
                </div>

                <h2 class="text-4xl md:text-5xl font-bold mb-6">Never Miss a Recipe</h2>
                <p class="text-xl md:text-2xl mb-12 max-w-3xl mx-auto text-white/90 leading-relaxed">
                    Subscribe to our newsletter and get the latest recipes, cooking tips, and culinary inspiration delivered straight to your inbox every week.
                </p>

                <!-- Subscription Form -->
                <div class="max-w-2xl mx-auto">
                    <form id="cta-newsletter-form" class="space-y-4">
                        <div class="flex flex-col sm:flex-row gap-4">
                            <input type="email" 
                                   id="cta-newsletter-email" 
                                   placeholder="Enter your email address" 
                                   required
                                   class="flex-1 px-6 py-4 rounded-full text-gray-900 text-lg focus:outline-none focus:ring-4 focus:ring-white/30 transition-all duration-300">
                            <button type="submit" 
                                    id="cta-newsletter-submit"
                                    class="bg-white text-primary font-bold py-4 px-8 rounded-full hover:bg-gray-100 transition-all duration-300 text-lg shadow-lg hover:shadow-xl transform hover:-translate-y-1 flex items-center justify-center min-w-[180px]">
                                <span class="flex items-center">
                                    <i class="fas fa-paper-plane mr-2"></i>Subscribe Now
                                </span>
                            </button>
                        </div>
                        <div id="cta-newsletter-message" class="text-sm font-medium hidden"></div>
                        <p class="text-white/80 text-sm">Join 10,000+ food lovers • Unsubscribe anytime</p>
                    </form>
                </div>

                <!-- Social Proof -->
                <div class="mt-16 grid grid-cols-1 md:grid-cols-3 gap-8 max-w-3xl mx-auto">
                    <div class="text-center">
                        <div class="text-3xl font-bold text-white mb-2">10K+</div>
                        <div class="text-white/80">Newsletter Subscribers</div>
                    </div>
                    <div class="text-center">
                        <div class="text-3xl font-bold text-white mb-2">500+</div>
                        <div class="text-white/80">Recipes Shared</div>
                    </div>
                    <div class="text-center">
                        <div class="text-3xl font-bold text-white mb-2">Weekly</div>
                        <div class="text-white/80">Fresh Content</div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>

<!-- JavaScript -->
<script>
// Newsletter subscription handling
document.getElementById('cta-newsletter-form').addEventListener('submit', async function(e) {
    e.preventDefault();

    const emailInput = document.getElementById('cta-newsletter-email');
    const submitBtn = document.getElementById('cta-newsletter-submit');
    const messageDiv = document.getElementById('cta-newsletter-message');
    const submitText = submitBtn.querySelector('span');

    const email = emailInput.value.trim();

    if (!email) {
        showMessage('Please enter your email address.', 'error');
        return;
    }

    if (!isValidEmail(email)) {
        showMessage('Please enter a valid email address.', 'error');
        return;
    }

    // Disable form during submission
    submitBtn.disabled = true;
    submitBtn.classList.add('opacity-75');
    submitText.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Subscribing...';

    try {
        const response = await fetch('includes/subscribe.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'email=' + encodeURIComponent(email) + '&ajax=1'
        });

        const result = await response.json();

        if (result.success) {
            showMessage(result.message || 'Successfully subscribed!', 'success');
            emailInput.value = '';
        } else {
            showMessage(result.message || 'Subscription failed. Please try again.', 'error');
        }
    } catch (error) {
        console.error('Subscription error:', error);
        showMessage('Something went wrong. Please try again later.', 'error');
    } finally {
        // Re-enable form
        submitBtn.disabled = false;
        submitBtn.classList.remove('opacity-75');
        submitText.innerHTML = '<i class="fas fa-paper-plane mr-2"></i>Subscribe Now';
    }
});

function showMessage(message, type) {
    const messageDiv = document.getElementById('cta-newsletter-message');
    messageDiv.textContent = message;
    messageDiv.className = `text-sm font-medium ${type === 'success' ? 'text-green-300' : 'text-red-300'}`;
    messageDiv.classList.remove('hidden');

    // Hide message after 5 seconds
    setTimeout(() => {
        messageDiv.classList.add('hidden');
    }, 5000);
}

function isValidEmail(email) {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return emailRegex.test(email);
}

// Reading progress bar
document.addEventListener('DOMContentLoaded', function() {
    const progressBar = document.getElementById('progress-fill');
    
    function updateProgressBar() {
        const windowHeight = window.innerHeight;
        const documentHeight = document.documentElement.scrollHeight - windowHeight;
        const scrollPosition = window.scrollY;
        const progress = (scrollPosition / documentHeight) * 100;
        
        if (progressBar) {
            progressBar.style.width = Math.min(progress, 100) + '%';
        }
    }
    
    window.addEventListener('scroll', updateProgressBar);
    window.addEventListener('resize', updateProgressBar);
    updateProgressBar();

    // Lazy loading for images
    if ('IntersectionObserver' in window) {
        const images = document.querySelectorAll('img:not(.no-lazy)');
        const imageObserver = new IntersectionObserver((entries, observer) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const img = entry.target;
                    img.classList.add('opacity-100');
                    observer.unobserve(img);
                }
            });
        });

        images.forEach(img => {
            img.classList.add('opacity-0', 'transition-opacity', 'duration-300');
            imageObserver.observe(img);
        });
    }

    // Smooth scroll for anchor links
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            e.preventDefault();
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                target.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        });
    });
});

// Share functions
function shareOnTwitter() {
    const url = encodeURIComponent(window.location.href);
    const text = encodeURIComponent('<?php echo addslashes($post['title']); ?>');
    window.open(`https://twitter.com/intent/tweet?text=${text}&url=${url}`, '_blank', 'width=550,height=420');
}

function shareOnFacebook() {
    const url = encodeURIComponent(window.location.href);
    window.open(`https://www.facebook.com/sharer/sharer.php?u=${url}`, '_blank', 'width=550,height=420');
}

function shareOnLinkedIn() {
    const url = encodeURIComponent(window.location.href);
    window.open(`https://www.linkedin.com/sharing/share-offsite/?url=${url}`, '_blank', 'width=550,height=420');
}

function copyLink() {
    const url = window.location.href;
    
    if (navigator.clipboard && navigator.clipboard.writeText) {
        navigator.clipboard.writeText(url).then(() => {
            showCopyNotification('Link copied to clipboard!');
        }).catch(err => {
            fallbackCopyTextToClipboard(url);
        });
    } else {
        fallbackCopyTextToClipboard(url);
    }
}

function fallbackCopyTextToClipboard(text) {
    const textArea = document.createElement("textarea");
    textArea.value = text;
    textArea.style.position = "fixed";
    textArea.style.left = "-999999px";
    document.body.appendChild(textArea);
    textArea.focus();
    textArea.select();
    
    try {
        document.execCommand('copy');
        showCopyNotification('Link copied to clipboard!');
    } catch (err) {
        showCopyNotification('Failed to copy link');
    }
    
    document.body.removeChild(textArea);
}

function showCopyNotification(message) {
    const notification = document.createElement('div');
    notification.className = 'fixed bottom-4 right-4 bg-gray-900 text-white px-6 py-3 rounded-lg shadow-lg z-50 transition-opacity duration-300';
    notification.textContent = message;
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.style.opacity = '0';
        setTimeout(() => {
            document.body.removeChild(notification);
        }, 300);
    }, 2000);
}

function showLoginAlert() {
    const alert = document.createElement('div');
    alert.className = 'fixed top-4 left-1/2 transform -translate-x-1/2 bg-yellow-100 border-l-4 border-yellow-500 text-yellow-700 p-4 rounded shadow-lg z-50 max-w-md';
    alert.innerHTML = `
        <div class="flex items-center">
            <i class="fas fa-exclamation-triangle mr-3"></i>
            <div>
                <p class="font-semibold">Login Required</p>
                <p class="text-sm">Please <a href="login.php?redirect=${encodeURIComponent(window.location.href)}" class="underline font-semibold">login</a> to like or comment on this post.</p>
            </div>
        </div>
    `;
    document.body.appendChild(alert);
    
    setTimeout(() => {
        alert.style.opacity = '0';
        alert.style.transition = 'opacity 300ms';
        setTimeout(() => {
            document.body.removeChild(alert);
        }, 300);
    }, 5000);
}





function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}
</script>

<!-- Include blog interactions if exists -->
<script src="assets/js/blog-interactions.js" defer></script>


<?php include 'includes/footer.php'; ?>