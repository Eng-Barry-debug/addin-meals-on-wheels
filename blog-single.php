<?php
require_once 'includes/config.php';

// Check if post ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: /blog.php');
    exit();
}

$post_id = (int)$_GET['id'];

try {
    // Fetch the blog post (include both published and draft for admin access)
    $stmt = $pdo->prepare("
        SELECT p.*, p.author as author_name
        FROM blog_posts p
        WHERE p.id = :id
    ");
    $stmt->execute([':id' => $post_id]);
    $post = $stmt->fetch(PDO::FETCH_ASSOC);

    // If post not found, redirect to blog page
    if (!$post) {
        header('Location: /blog.php');
        exit();
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
    // Log error and redirect
    error_log("Database error: " . $e->getMessage());
    header('Location: /blog.php');
    exit();
}
?>

<?php include 'includes/header.php'; ?>

<!-- Hero Section -->
<section class="relative h-80 overflow-hidden">
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
                        <h1 class="text-4xl md:text-5xl font-bold mb-4" style="text-shadow: 3px 3px 8px rgba(0, 0, 0, 0.9), 0 0 15px rgba(0, 0, 0, 0.6);">Article</h1>
                        <p class="text-lg md:text-xl mb-6 max-w-2xl mx-auto" style="text-shadow: 2px 2px 6px rgba(0, 0, 0, 0.9);">Discover delicious recipes, cooking tips, and food stories</p>

                        <!-- Article highlights -->
                        <div class="grid md:grid-cols-3 gap-4 mt-8 max-w-3xl mx-auto">
                            <div class="bg-white/20 backdrop-blur-sm rounded-lg p-3 border border-white/30">
                                <div class="text-xl mb-2">
                                    <i class="fas fa-book-open text-blue-400" style="text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.8);"></i>
                                </div>
                                <h3 class="font-bold text-sm text-white mb-1" style="text-shadow: 1px 1px 3px rgba(0, 0, 0, 0.8);">In-Depth Articles</h3>
                                <p class="text-white/80 text-xs" style="text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.7);">Detailed recipes & guides</p>
                            </div>
                            <div class="bg-white/20 backdrop-blur-sm rounded-lg p-3 border border-white/30">
                                <div class="text-xl mb-2">
                                    <i class="fas fa-camera text-green-400" style="text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.8);"></i>
                                </div>
                                <h3 class="font-bold text-sm text-white mb-1" style="text-shadow: 1px 1px 3px rgba(0, 0, 0, 0.8);">Visual Stories</h3>
                                <p class="text-white/80 text-xs" style="text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.7);">Beautiful food photography</p>
                            </div>
                            <div class="bg-white/20 backdrop-blur-sm rounded-lg p-3 border border-white/30">
                                <div class="text-xl mb-2">
                                    <i class="fas fa-users text-purple-400" style="text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.8);"></i>
                                </div>
                                <h3 class="font-bold text-sm text-white mb-1" style="text-shadow: 1px 1px 3px rgba(0, 0, 0, 0.8);">Community Driven</h3>
                                <p class="text-white/80 text-xs" style="text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.7);">Stories from food lovers</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Blog Post Header -->
<article class="py-12 bg-white">
    <div class="container mx-auto px-4 max-w-4xl">
        <!-- Back to Blog -->
        <div class="mb-6">
            <a href="/blog.php" class="text-primary hover:underline inline-flex items-center">
                <i class="fas fa-arrow-left mr-2"></i> Back to Blog
            </a>
        </div>
        
        <!-- Post Header -->
        <header class="mb-8">
            <div class="flex items-center text-sm text-gray-500 mb-4">
                <span><i class="far fa-calendar-alt mr-1"></i> <?php echo date('F j, Y', strtotime($post['created_at'])); ?></span>
                <span class="mx-2">•</span>
                <span><i class="far fa-eye mr-1"></i> <?php echo number_format($post['views'] + 1); ?> views</span>
                <span class="mx-2">•</span>
                <span class="px-2 py-1 rounded-full text-xs font-medium <?php echo ($post['status'] ?? 'draft') === 'published' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'; ?>">
                    <?php echo ucfirst($post['status'] ?? 'draft'); ?>
                </span>
            </div>
            
            <h1 class="text-3xl md:text-4xl font-bold mb-4"><?php echo htmlspecialchars($post['title']); ?></h1>
            
            <!-- Author Info -->
            <div class="flex items-center mt-6">
                <div class="w-12 h-12 rounded-full bg-primary flex items-center justify-center text-white font-semibold text-lg mr-4">
                    <?php echo strtoupper(substr($post['author'] ?? 'A', 0, 1)); ?>
                </div>
                
                <div>
                    <p class="font-medium"><?php echo htmlspecialchars($post['author'] ?? 'Admin'); ?></p>
                    <p class="text-sm text-gray-500">
                        <?php 
                        $word_count = str_word_count(strip_tags($post['content']));
                        $reading_time = ceil($word_count / 200); // Average reading speed: 200 words per minute
                        echo $reading_time > 1 ? $reading_time . ' min read' : '1 min read';
                        ?>
                    </p>
                </div>
            </div>
        </header>
        
        <!-- Featured Image -->
        <?php if (!empty($post['image'])): ?>
            <div class="mb-8 rounded-lg overflow-hidden">
                <img src="/uploads/blog/<?php echo htmlspecialchars($post['image']); ?>" 
                     alt="<?php echo htmlspecialchars($post['title']); ?>" 
                     class="w-full h-auto max-h-[500px] object-cover">
            </div>
        <?php endif; ?>
        
        <!-- Post Content -->
        <div class="prose max-w-none mb-12">
            <?php echo $post['content']; ?>
        </div>
        
        <!-- Share Buttons -->
        <div class="flex items-center space-x-4 mb-12 pt-6 border-t border-gray-200">
            <span class="text-gray-600">Share:</span>
            <?php 
            $share_url = urlencode('https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']);
            $share_title = urlencode($post['title']);
            ?>
            <a href="https://www.facebook.com/share/17Y42uWZG4/" 
               target="_blank" 
               class="text-gray-500 hover:text-facebook transition-colors"
               aria-label="Share on Facebook">
                <i class="fab fa-facebook-f text-xl"></i>
            </a>
            <a href="https://twitter.com/intent/tweet?url=https%3A%2F%2Faddinsmeals.com<?php echo $_SERVER['REQUEST_URI']; ?>&text=Check%20out%20this%20delicious%20recipe%3A%20<?php echo urlencode($post['title']); ?>" 
               target="_blank" 
               class="text-gray-500 hover:text-twitter transition-colors"
               aria-label="Share on Twitter">
                <i class="fab fa-twitter text-xl"></i>
            </a>
            <a href="https://www.linkedin.com/sharing/share-offsite/?url=<?php echo $share_url; ?>" 
               target="_blank" 
               class="text-gray-500 hover:text-linkedin transition-colors"
               aria-label="Share on LinkedIn">
                <i class="fab fa-linkedin-in text-xl"></i>
            </a>
            <a href="mailto:?subject=<?php echo $share_title; ?>&body=Check out this article: <?php echo $share_url; ?>" 
               class="text-gray-500 hover:text-primary transition-colors"
               aria-label="Share via Email">
                <i class="fas fa-envelope text-xl"></i>
            </a>
        </div>
        
        <!-- Author Bio -->
        <div class="bg-gray-50 rounded-lg p-6 mb-12">
            <div class="flex items-start">
                <div class="w-16 h-16 rounded-full bg-primary flex-shrink-0 flex items-center justify-center text-white font-semibold text-xl mr-4">
                    <?php echo strtoupper(substr($post['author'] ?? 'A', 0, 1)); ?>
                </div>
                
                <div>
                    <h3 class="text-lg font-bold">About <?php echo htmlspecialchars($post['author'] ?? 'the Author'); ?></h3>
                    <p class="text-gray-600 mt-1">
                        Food enthusiast and writer with a passion for sharing delicious recipes and cooking tips. Creating memorable dining experiences one post at a time.
                    </p>
                </div>
            </div>
        </div>
        
        <!-- Related Posts -->
        <?php if (!empty($related_posts)): ?>
            <div class="mb-12">
                <h2 class="text-2xl font-bold mb-6">You Might Also Like</h2>
                <div class="grid md:grid-cols-3 gap-6">
                    <?php foreach ($related_posts as $related): ?>
                        <article class="border border-gray-100 rounded-lg overflow-hidden hover:shadow-md transition-shadow">
                            <?php if (!empty($related['image'])): ?>
                                <a href="/blog-single.php?id=<?php echo $related['id']; ?>">
                                    <img src="/uploads/blog/<?php echo htmlspecialchars($related['image']); ?>" 
                                         alt="<?php echo htmlspecialchars($related['title']); ?>" 
                                         class="w-full h-40 object-cover">
                                </a>
                            <?php endif; ?>
                            <div class="p-4">
                                <p class="text-sm text-gray-500 mb-2">
                                    <?php echo date('F j, Y', strtotime($related['created_at'])); ?>
                                </p>
                                <h3 class="font-bold mb-2 hover:text-primary transition-colors">
                                    <a href="/blog-single.php?id=<?php echo $related['id']; ?>">
                                        <?php echo htmlspecialchars($related['title']); ?>
                                    </a>
                                </h3>
                                <a href="/blog-single.php?id=<?php echo $related['id']; ?>" 
                                   class="text-primary text-sm font-medium hover:underline inline-flex items-center">
                                    Read More <i class="fas fa-arrow-right ml-1 text-xs"></i>
                                </a>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
        
        <!-- Comments Section (Optional) -->
        <!-- <div class="bg-gray-50 rounded-lg p-6">
            <h2 class="text-2xl font-bold mb-6">Comments</h2>
            
            <div class="space-y-6">
                <div class="bg-white p-4 rounded-lg shadow">
                    <div class="flex items-start">
                        <img src="/assets/img/avatar-placeholder.png" alt="User" class="w-10 h-10 rounded-full mr-3">
                        <div class="flex-1">
                            <div class="flex items-center justify-between">
                                <h4 class="font-bold">John Doe</h4>
                                <span class="text-sm text-gray-500">2 days ago</span>
                            </div>
                            <p class="text-gray-700 mt-1">Great post! I tried this recipe and it turned out amazing. Thanks for sharing!</p>
                            <button class="text-sm text-gray-500 mt-2 hover:text-primary">Reply</button>
                        </div>
                    </div>
                </div>
                
                <form class="mt-8">
                    <h3 class="text-lg font-medium mb-4">Leave a Comment</h3>
                    <div class="grid md:grid-cols-2 gap-4 mb-4">
                        <div>
                            <label for="name" class="block text-sm font-medium text-gray-700 mb-1">Name</label>
                            <input type="text" id="name" class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent">
                        </div>
                        <div>
                            <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                            <input type="email" id="email" class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent">
                        </div>
                    </div>
                    <div class="mb-4">
                        <label for="comment" class="block text-sm font-medium text-gray-700 mb-1">Comment</label>
                        <textarea id="comment" rows="4" class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent"></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary">Post Comment</button>
                </form>
            </div>
        </div> -->
    </div>
</article>

<!-- CTA Section -->
<section class="py-16 bg-primary text-white">
    <div class="container mx-auto px-4 text-center">
        <h2 class="text-3xl font-bold mb-6">Never Miss a Recipe</h2>
        <p class="text-xl mb-8 max-w-2xl mx-auto">Subscribe to our newsletter and get the latest recipes and cooking tips delivered straight to your inbox.</p>
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