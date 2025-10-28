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
    $search_condition = "WHERE (title LIKE :search OR content LIKE :search) AND status = 'published'";
    $search_params[':search'] = "%$search%";
} else {
    $search_condition = "WHERE status = 'published'";
}

// Get total number of posts for pagination
$count_sql = "SELECT COUNT(*) as total FROM blog_posts $search_condition";
$count_stmt = $pdo->prepare($count_sql);

// Bind search parameters for count query
foreach ($search_params as $key => $value) {
    $count_stmt->bindValue($key, $value);
}

$count_stmt->execute();
$total_posts = $count_stmt->fetch(PDO::FETCH_ASSOC)['total'];
$total_pages = ceil($total_posts / $posts_per_page);

// Fetch blog posts with pagination
$sql = "SELECT p.*, p.author as author_name,
        (SELECT COUNT(*) FROM blog_likes WHERE post_id = p.id) as like_count,
        (SELECT COUNT(*) FROM blog_comments WHERE post_id = p.id AND status = 'approved') as comment_count
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
            <form action="blog.php" method="GET" class="relative">
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
                <p class="text-gray-600 mb-2">Found <?php echo $total_posts; ?> result<?php echo $total_posts != 1 ? 's' : ''; ?></p>
                <a href="blog.php" class="text-primary hover:underline">← Back to all posts</a>
            </div>
        <?php endif; ?>

        <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-8">
            <?php if (count($posts) > 0): ?>
                <?php foreach ($posts as $post): ?>
                    <article class="bg-white rounded-lg shadow-md overflow-hidden hover:shadow-lg transition-shadow duration-300 group">
                        <?php if (!empty($post['image'])): ?>
                            <a href="blog-single.php?id=<?php echo $post['id']; ?>" class="block relative overflow-hidden">
                                <img src="uploads/blog/<?php echo htmlspecialchars($post['image']); ?>" 
                                     alt="<?php echo htmlspecialchars($post['title']); ?>" 
                                     class="w-full h-48 object-cover group-hover:scale-105 transition-transform duration-300">
                                <div class="absolute inset-0 bg-black/0 group-hover:bg-black/10 transition-colors duration-300"></div>
                            </a>
                        <?php else: ?>
                            <a href="blog-single.php?id=<?php echo $post['id']; ?>" class="block">
                                <div class="bg-gradient-to-br from-primary/20 to-secondary/20 h-48 flex items-center justify-center text-gray-400">
                                    <i class="fas fa-image text-4xl"></i>
                                </div>
                            </a>
                        <?php endif; ?>
                        
                        <div class="p-6">
                            <div class="flex items-center text-sm text-gray-500 mb-3 flex-wrap gap-2">
                                <span><i class="far fa-user mr-1"></i> <?php echo htmlspecialchars($post['author_name'] ?? 'Admin'); ?></span>
                                <span>•</span>
                                <span><i class="far fa-calendar-alt mr-1"></i> <?php echo date('M j, Y', strtotime($post['created_at'])); ?></span>
                            </div>

                            <!-- Stats -->
                            <div class="flex items-center gap-4 text-sm text-gray-500 mb-3">
                                <span><i class="far fa-heart mr-1"></i> <?php echo $post['like_count']; ?></span>
                                <span><i class="far fa-comment mr-1"></i> <?php echo $post['comment_count']; ?></span>
                                <span><i class="far fa-eye mr-1"></i> <?php echo number_format($post['views']); ?></span>
                            </div>
                            
                            <h2 class="text-xl font-bold mb-3 group-hover:text-primary transition-colors line-clamp-2">
                                <a href="blog-single.php?id=<?php echo $post['id']; ?>">
                                    <?php echo htmlspecialchars($post['title']); ?>
                                </a>
                            </h2>
                            
                            <p class="text-gray-600 mb-4 line-clamp-3">
                                <?php 
                                if (!empty($post['excerpt'])) {
                                    echo htmlspecialchars($post['excerpt']);
                                } else {
                                    $content = strip_tags($post['content']);
                                    echo strlen($content) > 150 ? substr($content, 0, 150) . '...' : $content;
                                }
                                ?>
                            </p>
                            
                            <a href="blog-single.php?id=<?php echo $post['id']; ?>" 
                               class="text-primary font-medium hover:text-primary-dark inline-flex items-center group-hover:gap-3 gap-2 transition-all">
                                Read More 
                                <i class="fas fa-arrow-right text-sm group-hover:translate-x-1 transition-transform"></i>
                            </a>
                        </div>
                    </article>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="col-span-full text-center py-12">
                    <div class="max-w-md mx-auto">
                        <i class="fas fa-newspaper text-5xl text-gray-300 mb-4"></i>
                        <h3 class="text-xl font-medium text-gray-700 mb-2">No blog posts found</h3>
                        <?php if (!empty($search)): ?>
                            <p class="text-gray-500 mt-2 mb-4">
                                We couldn't find any posts matching "<?php echo htmlspecialchars($search); ?>". 
                                Try a different search term or browse all posts.
                            </p>
                            <a href="blog.php" class="inline-block px-6 py-2 bg-primary text-white rounded-lg hover:bg-primary-dark transition-colors">
                                View All Posts
                            </a>
                        <?php else: ?>
                            <p class="text-gray-500 mt-2">Check back later for new posts!</p>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
            <div class="mt-12 flex justify-center">
                <nav class="flex items-center space-x-2 flex-wrap justify-center gap-2">
                    <?php if ($page > 1): ?>
                        <a href="blog.php?page=<?php echo $page - 1; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>" 
                           class="px-4 py-2 border border-gray-300 rounded hover:bg-gray-100 transition-colors">
                            <i class="fas fa-chevron-left"></i> Previous
                        </a>
                    <?php endif; ?>
                    
                    <?php 
                    // Smart pagination - show first, last, and pages around current
                    $range = 2; // Number of pages to show on each side of current page
                    
                    for ($i = 1; $i <= $total_pages; $i++): 
                        // Show first page, last page, and pages around current page
                        if ($i == 1 || $i == $total_pages || ($i >= $page - $range && $i <= $page + $range)):
                    ?>
                        <?php if ($i == $page): ?>
                            <span class="px-4 py-2 bg-primary text-white rounded font-semibold"><?php echo $i; ?></span>
                        <?php else: ?>
                            <a href="blog.php?page=<?php echo $i; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>" 
                               class="px-4 py-2 border border-gray-300 rounded hover:bg-gray-100 transition-colors">
                                <?php echo $i; ?>
                            </a>
                        <?php endif; ?>
                    <?php 
                        elseif ($i == $page - $range - 1 || $i == $page + $range + 1):
                            // Show ellipsis
                            echo '<span class="px-2 py-2 text-gray-500">...</span>';
                        endif;
                    endfor; 
                    ?>
                    
                    <?php if ($page < $total_pages): ?>
                        <a href="blog.php?page=<?php echo $page + 1; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>" 
                           class="px-4 py-2 border border-gray-300 rounded hover:bg-gray-100 transition-colors">
                            Next <i class="fas fa-chevron-right"></i>
                        </a>
                    <?php endif; ?>
                </nav>
            </div>
        <?php endif; ?>
    </div>
</section>

<!-- CTA Section -->
<section class="py-16 bg-gradient-to-br from-primary to-primary-dark text-white relative overflow-hidden">
    <!-- Background Pattern -->
    <div class="absolute inset-0 opacity-10">
        <div class="absolute inset-0" style="background-image: radial-gradient(circle at 1px 1px, rgba(255,255,255,0.3) 1px, transparent 0); background-size: 20px 20px;"></div>
    </div>

    <div class="container mx-auto px-4 text-center relative z-10">
        <div class="max-w-3xl mx-auto">
            <div class="w-16 h-16 bg-white/20 backdrop-blur-sm rounded-full flex items-center justify-center mx-auto mb-6">
                <i class="fas fa-envelope-open text-2xl text-white"></i>
            </div>
            
            <h2 class="text-3xl md:text-4xl font-bold mb-4">Subscribe to Our Newsletter</h2>
            <p class="text-xl mb-8 text-white/90">Get the latest recipes, cooking tips, and special offers delivered to your inbox.</p>
            
            <!-- Newsletter Form -->
            <div class="max-w-md mx-auto">
                <form id="blog-newsletter-form" class="space-y-3">
                    <div class="flex flex-col sm:flex-row gap-3">
                        <input type="email" 
                               id="blog-newsletter-email" 
                               placeholder="Your email address"
                               class="px-4 py-3 flex-1 rounded-lg focus:outline-none focus:ring-2 focus:ring-white/50 text-gray-800"
                               required>
                        <button type="submit" 
                                id="blog-newsletter-submit"
                                class="bg-secondary text-white px-6 py-3 rounded-lg hover:bg-yellow-600 transition-colors duration-200 font-semibold flex items-center justify-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed min-w-[140px]">
                            <span class="flex items-center gap-2">
                                <i class="fas fa-paper-plane"></i>
                                <span>Subscribe</span>
                            </span>
                        </button>
                    </div>
                    <div id="blog-newsletter-message" class="text-sm font-medium min-h-[20px]"></div>
                    <p class="text-sm text-white/80">We respect your privacy. Unsubscribe at any time.</p>
                </form>
            </div>

            <!-- Stats -->
            <div class="mt-12 grid grid-cols-3 gap-6 max-w-2xl mx-auto">
                <div>
                    <div class="text-3xl font-bold">5K+</div>
                    <div class="text-white/80 text-sm">Subscribers</div>
                </div>
                <div>
                    <div class="text-3xl font-bold">100+</div>
                    <div class="text-white/80 text-sm">Recipes</div>
                </div>
                <div>
                    <div class="text-3xl font-bold">Weekly</div>
                    <div class="text-white/80 text-sm">Updates</div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Newsletter Subscription JavaScript -->
<script>
// Wait for the DOM to be fully loaded
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('blog-newsletter-form');
    if (!form) return;

    form.addEventListener('submit', function(e) {
        e.preventDefault();
        e.stopPropagation();

        const emailInput = document.getElementById('blog-newsletter-email');
        const submitBtn = document.getElementById('blog-newsletter-submit');
        const messageDiv = document.getElementById('blog-newsletter-message');
        
        if (!emailInput || !submitBtn || !messageDiv) return;

        const email = emailInput.value.trim();

        // Basic validation
        if (!email) {
            showMessage('Please enter your email address.', 'error', messageDiv);
            return;
        }

        if (!isValidEmail(email)) {
            showMessage('Please enter a valid email address.', 'error', messageDiv);
            return;
        }

        // Show loading state
        const submitText = submitBtn.querySelector('span');
        const originalHTML = submitText ? submitText.innerHTML : '';
        if (submitText) submitText.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
        submitBtn.disabled = true;
        submitBtn.classList.add('opacity-75');

        // Use XMLHttpRequest for better browser compatibility
        const xhr = new XMLHttpRequest();
        const formData = new FormData();
        formData.append('email', email);
        formData.append('ajax', '1');

        xhr.open('POST', 'includes/subscribe.php', true);
        xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
        
        xhr.onload = function() {
            try {
                const result = JSON.parse(xhr.responseText);
                if (result.success) {
                    showMessage(result.message || 'Successfully subscribed!', 'success', messageDiv);
                    emailInput.value = '';
                } else {
                    showMessage(result.message || 'Subscription failed.', 'error', messageDiv);
                }
            } catch (e) {
                console.error('Error parsing response:', e);
                showMessage('Something went wrong. Please try again later.', 'error', messageDiv);
            } finally {
                // Reset button state
                if (submitText) submitText.innerHTML = originalHTML;
                submitBtn.disabled = false;
                submitBtn.classList.remove('opacity-75');
            }
        };

        xhr.onerror = function() {
            showMessage('Network error. Please check your connection and try again.', 'error', messageDiv);
            if (submitText) submitText.innerHTML = originalHTML;
            submitBtn.disabled = false;
            submitBtn.classList.remove('opacity-75');
        };

        xhr.send(formData);
    });
});

function showMessage(message, type, messageDiv) {
    if (!messageDiv) return;
    
    messageDiv.textContent = message;
    messageDiv.className = 'text-sm font-medium ' + (type === 'success' ? 'text-green-300' : 'text-red-300');
    messageDiv.style.display = 'block';
    messageDiv.style.opacity = '1';
    messageDiv.style.transition = 'opacity 0.3s ease';

    // Hide message after 5 seconds with fade out
    setTimeout(function() {
        messageDiv.style.opacity = '0';
        setTimeout(function() {
            messageDiv.style.display = 'none';
            messageDiv.textContent = '';
        }, 300);
    }, 5000);
}

function isValidEmail(email) {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return emailRegex.test(email);
}
</script>

<?php include 'includes/footer.php'; ?>