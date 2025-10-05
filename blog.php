<?php
require_once 'includes/config.php';

// Pagination
$posts_per_page = 6;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $posts_per_page;

// Search functionality
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$search_condition = '';
$search_params = [];

if (!empty($search)) {
    $search_condition = "WHERE title LIKE :search OR content LIKE :search";
    $search_params[':search'] = "%$search%";
}

// Get total number of posts for pagination
if (!empty($search_condition)) {
    $count_sql = "SELECT COUNT(*) as total FROM blog_posts $search_condition";
    $count_stmt = $pdo->prepare($count_sql);

    // Bind search parameters for count query
    foreach ($search_params as $key => $value) {
        $count_stmt->bindValue($key, $value);
    }

    $count_stmt->execute();
} else {
    $count_sql = "SELECT COUNT(*) as total FROM blog_posts";
    $count_stmt = $pdo->query($count_sql);
}

$total_posts = $count_stmt->fetch(PDO::FETCH_ASSOC)['total'];
$total_pages = ceil($total_posts / $posts_per_page);

// Fetch blog posts with pagination
$sql = "SELECT p.*, p.author as author_name 
        FROM blog_posts p 
        $search_condition 
        ORDER BY p.created_at DESC 
        LIMIT :offset, :limit";

$stmt = $pdo->prepare($sql);

// Bind search parameters for main query
foreach ($search_params as $key => $value) {
    $stmt->bindValue($key, $value);
}

// Bind pagination parameters
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->bindValue(':limit', $posts_per_page, PDO::PARAM_INT);
$stmt->execute();

$posts = $stmt->fetchAll(PDO::FETCH_ASSOC);

$page_title = "Blog - Addins Meals on Wheels";
?>

<?php include 'includes/header.php'; ?>

<!-- Hero Section -->
<section class="relative h-96 overflow-hidden">
    <div class="absolute inset-0 bg-cover bg-center" style="background-image: url('https://images.unsplash.com/photo-1481627834876-b7833e8f5570?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=2048&q=80');">
        <!-- Enhanced overlay for better text visibility -->
        <div class="absolute inset-0 bg-gradient-to-br from-black/70 via-black/60 to-black/70"></div>
        <div class="absolute inset-0 bg-gradient-to-t from-black/50 via-transparent to-black/40"></div>
    </div>

    <div class="relative z-10 h-full flex items-center justify-center">
        <div class="container mx-auto px-4 text-center text-white">
            <!-- Background overlay for text readability -->
            <div class="max-w-4xl mx-auto">
                <div class="relative">
                    <!-- Semi-transparent background for text -->
                    <div class="absolute inset-0 bg-black/40 backdrop-blur-sm rounded-2xl -mx-4 px-8 py-12"></div>

                    <!-- Hero Content -->
                    <div class="relative z-10">
                        <h1 class="text-4xl md:text-6xl font-bold mb-6" style="text-shadow: 3px 3px 8px rgba(0, 0, 0, 0.9), 0 0 15px rgba(0, 0, 0, 0.6);">Our Blog</h1>
                        <p class="text-xl md:text-2xl mb-8 max-w-3xl mx-auto" style="text-shadow: 2px 2px 6px rgba(0, 0, 0, 0.9);">Discover delicious recipes, cooking tips, and food stories</p>

                        <!-- Blog highlights -->
                        <div class="grid md:grid-cols-3 gap-6 mt-12 max-w-4xl mx-auto">
                            <div class="bg-white/20 backdrop-blur-sm rounded-lg p-4 border border-white/30">
                                <div class="text-2xl mb-2">
                                    <i class="fas fa-utensils text-orange-400" style="text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.8);"></i>
                                </div>
                                <h3 class="font-bold text-sm text-white mb-1" style="text-shadow: 1px 1px 3px rgba(0, 0, 0, 0.8);">Delicious Recipes</h3>
                                <p class="text-white/80 text-xs" style="text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.7);">Step-by-step cooking guides</p>
                            </div>
                            <div class="bg-white/20 backdrop-blur-sm rounded-lg p-4 border border-white/30">
                                <div class="text-2xl mb-2">
                                    <i class="fas fa-lightbulb text-yellow-400" style="text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.8);"></i>
                                </div>
                                <h3 class="font-bold text-sm text-white mb-1" style="text-shadow: 1px 1px 3px rgba(0, 0, 0, 0.8);">Cooking Tips</h3>
                                <p class="text-white/80 text-xs" style="text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.7);">Expert advice and techniques</p>
                            </div>
                            <div class="bg-white/20 backdrop-blur-sm rounded-lg p-4 border border-white/30">
                                <div class="text-2xl mb-2">
                                    <i class="fas fa-camera text-purple-400" style="text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.8);"></i>
                                </div>
                                <h3 class="font-bold text-sm text-white mb-1" style="text-shadow: 1px 1px 3px rgba(0, 0, 0, 0.8);">Food Stories</h3>
                                <p class="text-white/80 text-xs" style="text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.7);">Behind-the-scenes content</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Blog Section -->
<section class="py-16 bg-light">
    <div class="container mx-auto px-4">
        <!-- Search Bar -->
        <div class="mb-12 max-w-2xl mx-auto">
            <form action="/blog.php" method="GET" class="relative">
                <input type="text" 
                       name="search" 
                       placeholder="Search blog posts..." 
                       value="<?php echo htmlspecialchars($search); ?>"
                       class="w-full px-6 py-3 pr-12 rounded-full border border-gray-300 focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent">
                <button type="submit" class="absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-500 hover:text-primary">
                    <i class="fas fa-search text-xl"></i>
                </button>
            </form>
        </div>

        <?php if (!empty($search)): ?>
            <div class="mb-8 text-center">
                <h2 class="text-2xl font-bold mb-2">Search Results for "<?php echo htmlspecialchars($search); ?>"</h2>
                <a href="/blog.php" class="text-primary hover:underline">← Back to all posts</a>
            </div>
        <?php endif; ?>

        <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-8">
            <?php if (count($posts) > 0): ?>
                <?php foreach ($posts as $post): ?>
                    <article class="bg-white rounded-lg shadow-md overflow-hidden hover:shadow-lg transition-shadow duration-300">
                        <?php if (!empty($post['image'])): ?>
                            <a href="/blog-single.php?id=<?php echo $post['id']; ?>">
                                <img src="/uploads/blog/<?php echo htmlspecialchars($post['image']); ?>" 
                                     alt="<?php echo htmlspecialchars($post['title']); ?>" 
                                     class="w-full h-48 object-cover hover:opacity-90 transition-opacity duration-300">
                            </a>
                        <?php else: ?>
                            <div class="bg-gray-200 h-48 flex items-center justify-center text-gray-400">
                                <i class="fas fa-image text-4xl"></i>
                            </div>
                        <?php endif; ?>
                        
                        <div class="p-6">
                            <div class="flex items-center text-sm text-gray-500 mb-3">
                                <span><i class="far fa-user mr-1"></i> <?php echo htmlspecialchars($post['author_name'] ?? 'Admin'); ?></span>
                                <span class="mx-2">•</span>
                                <span><i class="far fa-calendar-alt mr-1"></i> <?php echo date('F j, Y', strtotime($post['created_at'])); ?></span>
                            </div>
                            
                            <h2 class="text-xl font-bold mb-3 hover:text-primary transition-colors">
                                <a href="/blog-single.php?id=<?php echo $post['id']; ?>">
                                    <?php echo htmlspecialchars($post['title']); ?>
                                </a>
                            </h2>
                            
                            <p class="text-gray-600 mb-4">
                                <?php 
                                $content = strip_tags($post['content']);
                                echo strlen($content) > 150 ? substr($content, 0, 150) . '...' : $content;
                                ?>
                            </p>
                            
                            <a href="/blog-single.php?id=<?php echo $post['id']; ?>" class="text-primary font-medium hover:underline inline-flex items-center">
                                Read More <i class="fas fa-arrow-right ml-2"></i>
                            </a>
                        </div>
                    </article>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="col-span-full text-center py-12">
                    <i class="fas fa-newspaper text-5xl text-gray-300 mb-4"></i>
                    <h3 class="text-xl font-medium text-gray-700">No blog posts found</h3>
                    <?php if (!empty($search)): ?>
                        <p class="text-gray-500 mt-2">Try a different search term or <a href="/blog.php" class="text-primary hover:underline">browse all posts</a>.</p>
                    <?php else: ?>
                        <p class="text-gray-500 mt-2">Check back later for new posts!</p>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
            <div class="mt-12 flex justify-center">
                <nav class="flex items-center space-x-2">
                    <?php if ($page > 1): ?>
                        <a href="?page=<?php echo $page - 1; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>" 
                           class="px-4 py-2 border rounded hover:bg-gray-100">
                            <i class="fas fa-chevron-left"></i> Previous
                        </a>
                    <?php endif; ?>
                    
                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <?php if ($i == $page): ?>
                            <span class="px-4 py-2 bg-primary text-white rounded"><?php echo $i; ?></span>
                        <?php else: ?>
                            <a href="?page=<?php echo $i; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>" 
                               class="px-4 py-2 border rounded hover:bg-gray-100">
                                <?php echo $i; ?>
                            </a>
                        <?php endif; ?>
                    <?php endfor; ?>
                    
                    <?php if ($page < $total_pages): ?>
                        <a href="?page=<?php echo $page + 1; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>" 
                           class="px-4 py-2 border rounded hover:bg-gray-100">
                            Next <i class="fas fa-chevron-right"></i>
                        </a>
                    <?php endif; ?>
                </nav>
            </div>
        <?php endif; ?>
    </div>
</section>

<!-- CTA Section -->
<section class="py-16 bg-primary text-white">
    <div class="container mx-auto px-4 text-center">
        <h2 class="text-3xl font-bold mb-6">Subscribe to Our Newsletter</h2>
        <p class="text-xl mb-8 max-w-2xl mx-auto">Get the latest recipes, cooking tips, and special offers delivered to your inbox.</p>
        <form action="/subscribe.php" method="POST" class="max-w-md mx-auto flex">
            <input type="email" name="email" placeholder="Your email address" required 
                   class="flex-1 px-4 py-3 rounded-l-lg focus:outline-none text-gray-900">
            <button type="submit" class="bg-secondary text-white px-6 py-3 rounded-r-lg hover:bg-yellow-600 transition-colors">
                Subscribe
            </button>
        </form>
    </div>
</section>

<?php include 'includes/footer.php'; ?>