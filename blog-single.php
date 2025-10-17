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
<section class="relative min-h-screen flex items-center justify-center overflow-hidden">
    <!-- Dynamic background based on post image or fallback -->
    <div class="absolute inset-0 bg-cover bg-center" style="background-image: url('<?php echo !empty($post['image']) ? "/uploads/blog/" . htmlspecialchars($post['image']) : "https://images.unsplash.com/photo-1481627834876-b7833e8f5570?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=2048&q=80"; ?>');">
        <!-- Enhanced overlay for better text visibility -->
        <div class="absolute inset-0 bg-gradient-to-br from-black/80 via-black/70 to-black/75"></div>
        <div class="absolute inset-0 bg-gradient-to-t from-black/60 via-black/20 to-black/50"></div>
    </div>

    <div class="relative z-10 w-full">
        <div class="container mx-auto px-4">
            <div class="max-w-6xl mx-auto">
                <!-- Main Article Content -->
                <div class="max-w-5xl mx-auto">
                    <a href="/blog.php" class="inline-flex items-center text-white/80 hover:text-white transition-colors group">
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
                        <div class="px-3 py-1 rounded-full text-xs font-medium <?php echo ($post['status'] ?? 'draft') === 'published' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'; ?>">
                            <?php echo ucfirst($post['status'] ?? 'draft'); ?>
                        </div>
                    </div>

                    <!-- Article Title -->
                    <h1 class="text-4xl md:text-5xl lg:text-6xl font-bold mb-6 text-gray-900 leading-tight">
                        <?php echo htmlspecialchars($post['title']); ?>
                    </h1>

                    <!-- Article Excerpt -->
                    <p class="text-xl md:text-2xl text-gray-700 mb-8 leading-relaxed max-w-3xl">
                        Discover culinary insights, cooking techniques, and delicious recipes that will elevate your kitchen game.
                    </p>

                    <!-- Author Info -->
                    <div class="flex items-center justify-between">
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
                            $share_url = urlencode('https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']);
                            $share_title = urlencode($post['title']);
                            ?>
                            <span class="text-gray-600 text-sm mr-2">Share:</span>
                            <a href="https://www.facebook.com/sharer/sharer.php?u=<?php echo $share_url; ?>"
                               target="_blank"
                               class="w-10 h-10 bg-blue-600 text-white rounded-full flex items-center justify-center hover:bg-blue-700 transition-colors"
                               aria-label="Share on Facebook">
                                <i class="fab fa-facebook-f text-sm"></i>
                            </a>
                            <a href="https://twitter.com/intent/tweet?url=<?php echo $share_url; ?>&text=<?php echo $share_title; ?>"
                               target="_blank"
                               class="w-10 h-10 bg-blue-400 text-white rounded-full flex items-center justify-center hover:bg-blue-500 transition-colors"
                               aria-label="Share on Twitter">
                                <i class="fab fa-twitter text-sm"></i>
                            </a>
                            <a href="https://www.linkedin.com/sharing/share-offsite/?url=<?php echo $share_url; ?>"
                               target="_blank"
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
<section class="py-16 bg-gradient-to-br from-gray-50 via-white to-gray-100 relative">
    <!-- Subtle background pattern -->
    <div class="absolute inset-0 opacity-5">
        <div class="absolute inset-0" style="background-image: radial-gradient(circle at 2px 2px, rgba(252, 119, 3, 0.3) 1px, transparent 0); background-size: 40px 40px;"></div>
    </div>
    <div class="container mx-auto px-4">
        <div class="max-w-4xl mx-auto">
            <!-- Reading Progress Bar -->
            <div class="fixed top-0 left-0 w-full h-1 bg-gray-200 z-50" id="progress-bar">
                <div class="h-full bg-gradient-to-r from-primary to-secondary transition-all duration-300 ease-out" id="progress-fill"></div>
            </div>

            <div class="max-w-6xl mx-auto">
                <!-- Main Article Content -->
                <div class="max-w-5xl mx-auto">
                    <article class="bg-white rounded-2xl shadow-xl overflow-hidden">
                        <!-- Featured Image -->
                        <?php if (!empty($post['image'])): ?>
                            <div class="relative h-96 overflow-hidden">
                                <img src="/uploads/blog/<?php echo htmlspecialchars($post['image']); ?>"
                                     alt="<?php echo htmlspecialchars($post['title']); ?>"
                                     class="w-full h-full object-cover">
                                <div class="absolute inset-0 bg-gradient-to-t from-black/30 to-transparent"></div>
                            </div>
                        <?php endif; ?>

                        <!-- Article Content -->
                        <div id="article-content" class="p-8 lg:p-12">
                            <!-- Enhanced Typography -->
                            <div class="prose prose-lg max-w-none">
                                <style>
                                    /* Enhanced typography for blog content */
                                    .blog-content h1 {
                                        @apply text-4xl md:text-5xl font-bold text-gray-900 mt-16 mb-8 leading-tight tracking-tight;
                                        font-family: 'Georgia', serif;
                                        position: relative;
                                    }
                                    .blog-content h1::after {
                                        content: '';
                                        position: absolute;
                                        bottom: -8px;
                                        left: 0;
                                        width: 60px;
                                        height: 4px;
                                        background: linear-gradient(to right, #fc7703, #D4AF37);
                                        border-radius: 2px;
                                    }
                                    .blog-content h2 {
                                        @apply text-3xl md:text-4xl font-bold text-gray-900 mt-14 mb-6 leading-tight tracking-tight;
                                        font-family: 'Georgia', serif;
                                        color: #1f2937;
                                        position: relative;
                                        padding-left: 1rem;
                                    }
                                    .blog-content h2::before {
                                        content: '';
                                        position: absolute;
                                        left: 0;
                                        top: 50%;
                                        transform: translateY(-50%);
                                        width: 4px;
                                        height: 70%;
                                        background: linear-gradient(to bottom, #fc7703, #D4AF37);
                                        border-radius: 2px;
                                    }
                                    .blog-content h3 {
                                        @apply text-2xl md:text-3xl font-semibold text-gray-800 mt-10 mb-4 leading-tight;
                                        font-family: 'Georgia', serif;
                                        color: #374151;
                                        letter-spacing: -0.025em;
                                    }
                                    .blog-content h4 {
                                        @apply text-xl md:text-2xl font-semibold text-gray-800 mt-8 mb-3 leading-tight;
                                        font-family: 'Georgia', serif;
                                        color: #4b5563;
                                    }
                                    .blog-content h5 {
                                        @apply text-lg md:text-xl font-medium text-gray-800 mt-6 mb-3 leading-tight;
                                        font-family: 'Georgia', serif;
                                        color: #6b7280;
                                    }
                                    .blog-content h6 {
                                        @apply text-base md:text-lg font-medium text-gray-700 mt-4 mb-2 leading-tight;
                                        font-family: 'Georgia', serif;
                                        color: #9ca3af;
                                    }
                                    .blog-content p {
                                        @apply text-lg text-gray-700 leading-relaxed mb-6;
                                        font-family: 'Merriweather', 'Georgia', serif;
                                        line-height: 1.75;
                                        letter-spacing: 0.01em;
                                        color: #374151;
                                    }
                                    .blog-content ul, .blog-content ol {
                                        @apply text-lg text-gray-700 mb-6 pl-6;
                                        font-family: 'Merriweather', 'Georgia', serif;
                                        line-height: 1.75;
                                    }
                                    .blog-content li {
                                        @apply mb-3 pl-2;
                                        position: relative;
                                    }
                                    .blog-content ul li::marker {
                                        color: #fc7703;
                                        font-weight: bold;
                                    }
                                    .blog-content ol li::marker {
                                        color: #6b7280;
                                        font-weight: bold;
                                    }
                                    .blog-content blockquote {
                                        @apply border-l-4 border-primary pl-8 pr-6 py-6 my-10 bg-gradient-to-r from-primary/5 to-secondary/5 italic text-xl text-gray-700;
                                        font-family: 'Merriweather', 'Georgia', serif;
                                        font-style: italic;
                                        position: relative;
                                        border-radius: 0 8px 8px 0;
                                    }
                                    .blog-content blockquote::before {
                                        content: '"';
                                        position: absolute;
                                        top: -10px;
                                        left: 20px;
                                        font-size: 4rem;
                                        color: #fc7703;
                                        opacity: 0.3;
                                        font-family: 'Georgia', serif;
                                    }
                                    .blog-content blockquote p {
                                        @apply text-xl italic text-gray-800 mb-4;
                                        font-family: 'Merriweather', 'Georgia', serif;
                                        position: relative;
                                        z-index: 1;
                                    }
                                    .blog-content blockquote footer {
                                        @apply text-base not-italic text-gray-600 mt-4;
                                        font-family: 'Inter', sans-serif;
                                    }
                                    .blog-content img {
                                        @apply rounded-xl shadow-xl my-10 w-full h-auto;
                                        transition: transform 0.3s ease, box-shadow 0.3s ease;
                                    }
                                    .blog-content img:hover {
                                        transform: scale(1.02);
                                        box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
                                    }
                                    .blog-content code {
                                        @apply bg-gray-100 px-3 py-1 rounded-md text-sm font-mono;
                                        font-family: 'Fira Code', 'Monaco', 'Consolas', monospace;
                                        color: #e11d48;
                                        border: 1px solid #e5e7eb;
                                    }
                                    .blog-content pre {
                                        @apply bg-gray-900 text-gray-100 p-6 rounded-xl overflow-x-auto my-8;
                                        font-family: 'Fira Code', 'Monaco', 'Consolas', monospace;
                                        line-height: 1.6;
                                        position: relative;
                                    }
                                    .blog-content pre::before {
                                        content: '';
                                        position: absolute;
                                        top: 1rem;
                                        left: 1rem;
                                        width: 12px;
                                        height: 12px;
                                        background: #ef4444;
                                        border-radius: 50%;
                                        box-shadow: 20px 0 0 #f59e0b, 40px 0 0 #10b981;
                                    }
                                    .blog-content pre code {
                                        @apply bg-transparent p-0 border-0 text-gray-100;
                                        color: inherit;
                                    }
                                    .blog-content a {
                                        @apply text-primary hover:text-primary-dark underline decoration-2 underline-offset-2;
                                        font-weight: 500;
                                        transition: all 0.2s ease;
                                    }
                                    .blog-content a:hover {
                                        @apply text-secondary;
                                        text-decoration-thickness: 3px;
                                        text-underline-offset: 4px;
                                    }
                                    .blog-content strong {
                                        @apply font-semibold text-gray-900;
                                        font-weight: 600;
                                    }
                                    .blog-content em {
                                        @apply italic text-gray-700;
                                        font-style: italic;
                                    }
                                    .blog-content table {
                                        @apply w-full border-collapse border border-gray-300 my-8;
                                    }
                                    .blog-content th {
                                        @apply bg-gray-50 px-4 py-3 text-left font-semibold text-gray-900 border-b border-gray-300;
                                        font-family: 'Inter', sans-serif;
                                    }
                                    .blog-content td {
                                        @apply px-4 py-3 border-b border-gray-200 text-gray-700;
                                        font-family: 'Merriweather', 'Georgia', serif;
                                    }
                                    .blog-content tr:hover {
                                        @apply bg-gray-50/50;
                                    }
                                    .blog-content hr {
                                        @apply border-0 h-px bg-gradient-to-r from-transparent via-gray-300 to-transparent my-12;
                                    }
                                </style>

                                <div class="blog-content">
                                    <?php echo $post['content']; ?>
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
                                    <div class="flex space-x-3">
                                        <?php
                                        $share_url = urlencode('https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']);
                                        $share_title = urlencode($post['title']);
                                        ?>
                                        <a href="https://www.facebook.com/sharer/sharer.php?u=<?php echo $share_url; ?>"
                                           target="_blank"
                                           class="w-10 h-10 bg-blue-600 text-white rounded-full flex items-center justify-center hover:bg-blue-700 transition-colors">
                                            <i class="fab fa-facebook-f text-sm"></i>
                                        </a>
                                        <a href="https://twitter.com/intent/tweet?url=<?php echo $share_url; ?>&text=<?php echo $share_title; ?>"
                                           target="_blank"
                                           class="w-10 h-10 bg-blue-400 text-white rounded-full flex items-center justify-center hover:bg-blue-500 transition-colors">
                                            <i class="fab fa-twitter text-sm"></i>
                                        </a>
                                        <a href="https://www.linkedin.com/sharing/share-offsite/?url=<?php echo $share_url; ?>"
                                           target="_blank"
                                           class="w-10 h-10 bg-blue-700 text-white rounded-full flex items-center justify-center hover:bg-blue-800 transition-colors">
                                            <i class="fab fa-linkedin-in text-sm"></i>
                                        </a>
                                        <a href="mailto:?subject=<?php echo $share_title; ?>&body=Check out this article: <?php echo $share_url; ?>"
                                           class="w-10 h-10 bg-gray-600 text-white rounded-full flex items-center justify-center hover:bg-gray-700 transition-colors">
                                            <i class="fas fa-envelope text-sm"></i>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </article>

                    <!-- Author Bio Section -->
                    <div id="author-bio" class="mt-12 bg-gradient-to-br from-primary/10 via-secondary/5 to-accent/10 rounded-2xl p-8 relative overflow-hidden">
                        <!-- Subtle background pattern -->
                        <div class="absolute inset-0 opacity-10">
                            <div class="absolute inset-0" style="background-image: radial-gradient(circle at 1px 1px, rgba(252, 119, 3, 0.2) 1px, transparent 0); background-size: 30px 30px;"></div>
                        </div>
                        <div class="flex items-start space-x-6">
                            <div class="w-20 h-20 rounded-full bg-gradient-to-br from-primary to-secondary flex items-center justify-center text-white font-bold text-2xl shadow-lg flex-shrink-0">
                                <?php echo strtoupper(substr($post['author'] ?? 'A', 0, 1)); ?>
                            </div>
                            <div class="flex-1">
                                <h3 class="text-2xl font-bold text-gray-900 mb-2">About <?php echo htmlspecialchars($post['author'] ?? 'the Author'); ?></h3>
                                <p class="text-gray-700 mb-4 leading-relaxed">
                                    Food enthusiast and writer with a passion for sharing delicious recipes and cooking tips. Creating memorable dining experiences one post at a time.
                                </p>
                                <div class="flex items-center space-x-4">
                                    <a href="#" class="text-primary hover:text-primary-dark font-medium">View Profile</a>
                                    <a href="#" class="text-gray-600 hover:text-gray-800 font-medium">More Articles</a>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Related Posts Section -->
                    <?php if (!empty($related_posts)): ?>
                        <div id="related-posts" class="mt-16">
                            <h2 class="text-3xl font-bold text-gray-900 mb-8 text-center">You Might Also Like</h2>
                            <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-8">
                                <?php foreach ($related_posts as $related): ?>
                                    <article class="bg-white rounded-xl shadow-lg overflow-hidden hover:shadow-xl transition-all duration-300 transform hover:-translate-y-1">
                                        <?php if (!empty($related['image'])): ?>
                                            <div class="relative h-48 overflow-hidden">
                                                <img src="/uploads/blog/<?php echo htmlspecialchars($related['image']); ?>"
                                                     alt="<?php echo htmlspecialchars($related['title']); ?>"
                                                     class="w-full h-full object-cover transition-transform duration-300 hover:scale-105">
                                                <div class="absolute inset-0 bg-gradient-to-t from-black/20 to-transparent"></div>
                                            </div>
                                        <?php endif; ?>
                                        <div class="p-6">
                                            <p class="text-sm text-gray-500 mb-3">
                                                <?php echo date('M j, Y', strtotime($related['created_at'])); ?>
                                            </p>
                                            <h3 class="font-bold text-lg mb-3 text-gray-900 hover:text-primary transition-colors line-clamp-2">
                                                <a href="/blog-single.php?id=<?php echo $related['id']; ?>">
                                                    <?php echo htmlspecialchars($related['title']); ?>
                                                </a>
                                            </h3>
                                            <a href="/blog-single.php?id=<?php echo $related['id']; ?>"
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
    </div>
</section>

<!-- Enhanced CTA Section -->
<section class="py-20 bg-gradient-to-br from-primary via-primary-dark to-secondary text-white relative overflow-hidden">
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
            <form action="/subscribe.php" method="POST" class="max-w-2xl mx-auto">
                <div class="flex flex-col sm:flex-row gap-4 mb-6">
                    <input type="email" name="email" placeholder="Enter your email address" required
                           class="flex-1 px-6 py-4 rounded-full text-gray-900 text-lg focus:outline-none focus:ring-4 focus:ring-white/30 transition-all duration-300">
                    <button type="submit" class="bg-white text-primary font-bold py-4 px-8 rounded-full hover:bg-gray-100 transition-all duration-300 text-lg shadow-lg hover:shadow-xl transform hover:-translate-y-1">
                        <i class="fas fa-paper-plane mr-2"></i>Subscribe Now
                    </button>
                </div>
                <p class="text-white/80 text-sm">Join 10,000+ food lovers • Unsubscribe anytime</p>
            </form>

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

<!-- JavaScript for Enhanced UX -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Reading Progress Bar
    const progressBar = document.getElementById('progress-bar');
    const progressFill = document.getElementById('progress-fill');

    if (progressBar && progressFill) {
        function updateProgressBar() {
            const scrollTop = window.pageYOffset;
            const docHeight = document.documentElement.scrollHeight - window.innerHeight;
            const scrollPercent = (scrollTop / docHeight) * 100;

            progressFill.style.width = scrollPercent + '%';
        }

        window.addEventListener('scroll', updateProgressBar);
        window.addEventListener('resize', updateProgressBar);
        updateProgressBar(); // Initial call
    }

    // Lazy loading for images
    const images = document.querySelectorAll('img');
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
});
</script>

<?php include 'includes/footer.php'; ?>