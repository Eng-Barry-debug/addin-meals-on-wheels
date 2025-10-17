<?php
session_start();
require_once 'includes/config.php';
require_once 'includes/Cart.php';
require_once 'includes/Wishlist.php';

// Initialize wishlist if user is logged in
$wishlist = null;
if (isset($_SESSION['user_id'])) {
    $wishlist = new Wishlist($pdo, $_SESSION['user_id']);
}

// Handle wishlist actions
if (isset($_POST['wishlist_action']) && isset($_POST['menu_item_id']) && $wishlist) {
    $menu_item_id = (int)$_POST['menu_item_id'];
    $action = $_POST['wishlist_action'];
    
    if ($action === 'add') {
        $wishlist->addItem($menu_item_id);
    } elseif ($action === 'remove') {
        $wishlist->removeItem($menu_item_id);
    }
    
    // Return JSON response for AJAX requests
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
        header('Content-Type: application/json');
        echo json_encode(['success' => true]);
        exit();
    }
}

// Initialize cart with PDO connection
$cart = new Cart($pdo);

// Handle add to cart
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_to_cart'])) {
    $menu_item_id = $_POST['menu_item_id'] ?? 0;
    $quantity = $_POST['quantity'] ?? 1;
    
    // Get menu item details
    $stmt = $pdo->prepare("SELECT id, price, name FROM menu_items WHERE id = ?");
    $stmt->execute([$menu_item_id]);
    $menu_item = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($menu_item) {
        $cart->addItem($menu_item['id'], $quantity, $menu_item['price']);
        
        // If this is an AJAX request, return JSON response
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'message' => "{$menu_item['name']} added to cart!",
                'count' => $cart->getTotalItems()
            ]);
            exit();
        } else {
            // Regular form submission
            $_SESSION['success'] = "{$menu_item['name']} added to cart!";
            header("Location: " . $_SERVER['PHP_SELF'] . "?id=" . $menu_item_id);
            exit();
        }
    } else if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
        // Return error for AJAX request
        header('HTTP/1.1 400 Bad Request');
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => 'Menu item not found']);
        exit();
    }
}

// Get menu item details with category and related items
$menu_item_id = $_GET['id'] ?? 0;
$stmt = $pdo->prepare("
    SELECT m.*, c.name as category_name, c.id as category_id
    FROM menu_items m
    LEFT JOIN categories c ON m.category_id = c.id
    WHERE m.id = ? AND m.status = 'active'");
$stmt->execute([$menu_item_id]);
$menu_item = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$menu_item) {
    header("Location: menu.php");
    exit();
}

// Get reviews for this menu item
$reviews_stmt = $pdo->prepare("
    SELECT r.*, u.name as user_name
    FROM reviews r
    LEFT JOIN users u ON r.user_id = u.id
    WHERE r.menu_item_id = ? AND r.status = 'approved'
    ORDER BY r.created_at DESC
    LIMIT 10
");
$reviews_stmt->execute([$menu_item_id]);
$reviews = $reviews_stmt->fetchAll(PDO::FETCH_ASSOC);

// Calculate average rating
$avg_rating_stmt = $pdo->prepare("
    SELECT AVG(rating) as avg_rating, COUNT(*) as review_count
    FROM reviews
    WHERE menu_item_id = ? AND status = 'approved'
");
$avg_rating_stmt->execute([$menu_item_id]);
$rating_data = $avg_rating_stmt->fetch(PDO::FETCH_ASSOC);

// Include header after all redirects are handled
require_once 'includes/header.php';

// Get related items from the same category
$related_stmt = $pdo->prepare("
    SELECT m.id, m.name, m.price, m.image
    FROM menu_items m
    WHERE m.category_id = ? AND m.id != ? AND m.status = 'active'
    ORDER BY m.created_at DESC
    LIMIT 8
");
$related_stmt->execute([$menu_item['category_id'], $menu_item_id]);
$related_items = $related_stmt->fetchAll(PDO::FETCH_ASSOC);

// Get popular items (items with most reviews or featured items)
$popular_stmt = $pdo->prepare("
    SELECT m.id, m.name, m.price, m.image,
           COUNT(r.id) as review_count,
           AVG(r.rating) as avg_rating
    FROM menu_items m
    LEFT JOIN reviews r ON m.id = r.menu_item_id AND r.status = 'approved'
    WHERE m.status = 'active' AND m.id != ?
    GROUP BY m.id
    ORDER BY (COUNT(r.id) + (m.is_featured * 3)) DESC, m.created_at DESC
    LIMIT 8
");
$popular_stmt->execute([$menu_item_id]);
$popular_items = $popular_stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="bg-gray-50 min-h-screen">
    <!-- Top Navigation -->
    <div class="bg-white border-b border-gray-200">
        <div class="container mx-auto px-4 py-3">
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-4">
                    <a href="index.php" class="text-gray-700 hover:text-primary">
                        <i class="fas fa-home"></i>
                    </a>
                    <a href="menu.php" class="text-gray-700 hover:text-primary">Menu</a>
                    <span class="text-gray-400">/</span>
                    <span class="text-gray-500"><?= htmlspecialchars($menu_item['name']) ?></span>
                </div>
                <div class="flex items-center space-x-4">
                            <?php if (isset($_SESSION['user_id'])): ?>
                                <form class="wishlist-form" method="POST" onsubmit="return toggleWishlist(event, <?= $menu_item['id'] ?>)">
                                    <input type="hidden" name="wishlist_action" value="<?= $wishlist && $wishlist->isInWishlist($menu_item['id']) ? 'remove' : 'add' ?>">
                                    <input type="hidden" name="menu_item_id" value="<?= $menu_item['id'] ?>">
                                    <button type="submit" class="text-gray-600 hover:text-red-500 focus:outline-none">
                                        <i class="<?= $wishlist && $wishlist->isInWishlist($menu_item['id']) ? 'fas' : 'far' ?> fa-heart text-xl text-red-500"></i>
                                    </button>
                                </form>
                            <?php else: ?>
                                <a href="login.php?redirect=<?= urlencode($_SERVER['REQUEST_URI']) ?>" class="text-gray-600 hover:text-red-500">
                                    <i class="far fa-heart text-xl"></i>
                                </a>
                            <?php endif; ?>
                    <a href="cart.php" class="text-gray-600 hover:text-primary relative">
                        <i class="fas fa-shopping-cart text-xl"></i>
                        <span class="absolute -top-2 -right-2 bg-primary text-white text-xs rounded-full h-5 w-5 flex items-center justify-center">
                            <?= count($cart->getItems()) ?: '0' ?>
                        </span>
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="container mx-auto px-4 py-6">
        <!-- Success Message -->
        <?php if (isset($_SESSION['success'])): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-6">
                <div class="flex justify-between items-center">
                    <span><?= htmlspecialchars($_SESSION['success']) ?></span>
                    <button type="button" class="text-green-700" onclick="this.parentElement.parentElement.remove()">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </div>
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>

        <!-- Product Section -->
        <div class="bg-white rounded-lg shadow-sm overflow-hidden">
            <div class="md:flex">
                <!-- Product Images -->
                <div class="md:w-1/2 p-4">
                    <div class="relative">
                        <div class="aspect-w-1 aspect-h-1 w-full overflow-hidden">
                            <?php if (!empty($menu_item['image'])): ?>
                                <img src="uploads/menu/<?= htmlspecialchars($menu_item['image']) ?>" 
                                     alt="<?= htmlspecialchars($menu_item['name']) ?>"
                                     class="w-full h-96 object-contain">
                            <?php else: ?>
                                <div class="bg-gray-100 h-96 flex items-center justify-center">
                                    <i class="fas fa-utensils text-6xl text-gray-300"></i>
                                </div>
                            <?php endif; ?>
                        </div>
                        <?php if (!empty($menu_item['original_price']) && $menu_item['original_price'] > $menu_item['price']): ?>
                            <div class="absolute top-4 left-4 bg-red-600 text-white text-sm font-bold px-2 py-1 rounded">
                                -<?= number_format((($menu_item['original_price'] - $menu_item['price']) / $menu_item['original_price']) * 100, 0) ?>%
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Image Gallery (Placeholder for multiple images) -->
                    <div class="flex space-x-2 mt-4">
                        <div class="w-16 h-16 border border-gray-200 rounded overflow-hidden">
                            <?php if (!empty($menu_item['image'])): ?>
                                <img src="uploads/menu/<?= htmlspecialchars($menu_item['image']) ?>" 
                                     alt="Thumbnail 1" 
                                     class="w-full h-full object-cover cursor-pointer">
                            <?php else: ?>
                                <div class="w-full h-full bg-gray-100 flex items-center justify-center">
                                    <i class="fas fa-utensils text-gray-300"></i>
                                </div>
                            <?php endif; ?>
                        </div>
                        <!-- Add more thumbnails here if available -->
                    </div>
                </div>

                <!-- Product Info -->
                <div class="md:w-1/2 p-6 border-l border-gray-100">
                    <!-- Category & Rating -->
                    <div class="flex justify-between items-start mb-4">
                        <?php if (!empty($menu_item['category_name'])): ?>
                            <span class="text-sm text-gray-500"><?= htmlspecialchars($menu_item['category_name']) ?></span>
                        <?php endif; ?>
                        <div class="flex items-center">
                            <div class="flex text-yellow-400">
                                <?php
                                $avg_rating = $rating_data['avg_rating'] ?? 0;
                                $review_count = $rating_data['review_count'] ?? 0;
                                for ($i = 1; $i <= 5; $i++):
                                    if ($i <= $avg_rating) {
                                        echo '<i class="fas fa-star"></i>';
                                    } elseif ($i - 0.5 <= $avg_rating) {
                                        echo '<i class="fas fa-star-half-alt"></i>';
                                    } else {
                                        echo '<i class="far fa-star"></i>';
                                    }
                                endfor;
                                ?>
                            </div>
                            <span class="text-sm text-gray-500 ml-1">(<?php echo $review_count; ?>)</span>
                        </div>
                    </div>

                    <!-- Title -->
                    
                    <!-- Price -->
                    <div class="mb-4">
                        <div class="flex items-baseline">
                            <span class="text-3xl font-bold text-gray-900">
                                KSh <?= number_format($menu_item['price'], 2) ?>
                            </span>
                        </div>
                    </div>

                    <!-- Delivery Info -->
                    <div class="bg-gray-50 p-4 rounded-lg mb-6">

                        <div class="flex items-center text-sm text-gray-600 mb-2">
                            <i class="fas fa-truck text-green-500 mr-2"></i>
                            <span>Free delivery on orders over KSh 1,000</span>
                        </div>
                        <div class="text-sm text-gray-600">
                            <span class="font-medium">Delivery:</span> 
                            <span>8AM - 9PM, 7 days a week</span>
                        </div>
                    </div>

                    <!-- Quantity & Add to Cart -->
                    <form method="POST" class="mb-6">
                        <input type="hidden" name="menu_item_id" value="<?= $menu_item['id'] ?>">
                        <div class="flex items-center space-x-4">
                            <div class="flex items-center border border-gray-300 rounded-md">
                                <button type="button" 
                                        class="px-3 py-2 text-gray-600 hover:bg-gray-100"
                                        onclick="decrementQuantity()">
                                    <i class="fas fa-minus"></i>
                                </button>
                                <input type="number" 
                                       id="quantity" 
                                       name="quantity" 
                                       value="1" 
                                       min="1" 
                                       max="20"
                                       class="w-16 text-center border-0 focus:ring-0"
                                       onchange="validateQuantity(this)">
                                <button type="button" 
                                        class="px-3 py-2 text-gray-600 hover:bg-gray-100"
                                        onclick="incrementQuantity()">
                                    <i class="fas fa-plus"></i>
                                </button>
                            </div>
                            <button type="submit" 
                                    name="add_to_cart" 
                                    class="flex-1 bg-primary hover:bg-primary-dark text-white font-medium py-2 px-6 rounded-md transition duration-200 flex items-center justify-center">
                                <i class="fas fa-shopping-cart mr-2"></i>
                                Add to Cart - KSh <span id="total-price"><?= number_format($menu_item['price'], 2) ?></span>
                            </button>
                        </div>
                    </form>

                    <!-- Product Meta -->
                    <div class="border-t border-gray-200 pt-4">
                        <div class="grid grid-cols-2 gap-4 text-sm text-gray-600">
                            <div class="flex items-center">
                                <i class="fas fa-shield-alt text-gray-400 mr-2"></i>
                                <span>Secure Payment</span>
                            </div>
                            <div class="flex items-center">
                                <i class="fas fa-undo text-gray-400 mr-2"></i>
                                <span>Easy Returns</span>
                            </div>
                            <div class="flex items-center">
                                <i class="fas fa-tag text-gray-400 mr-2"></i>
                                <span>Best Price</span>
                            </div>
                            <div class="flex items-center">
                                <i class="fas fa-headset text-gray-400 mr-2"></i>
                                <span>24/7 Support</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Product Details Tabs -->
        <div class="mt-8 bg-white rounded-lg shadow-sm overflow-hidden">
            <div class="border-b border-gray-200">
                <nav class="flex -mb-px">
                    <button onclick="showTab('description')" class="px-6 py-4 font-medium text-sm border-b-2 border-primary text-primary bg-primary/5">
                        Description
                    </button>
                    <button onclick="showTab('ingredients')" class="px-6 py-4 font-medium text-sm text-gray-700 hover:text-primary cursor-pointer">
                        Ingredients
                    </button>
                    <button onclick="showTab('allergens')" class="px-6 py-4 font-medium text-sm text-gray-700 hover:text-primary cursor-pointer">
                        Allergens
                    </button>
                    <button onclick="showTab('nutrition')" class="px-6 py-4 font-medium text-sm text-gray-700 hover:text-primary cursor-pointer">
                        Nutrition
                    </button>
                    <button onclick="showTab('reviews')" class="px-6 py-4 font-medium text-sm text-gray-700 hover:text-primary cursor-pointer">
                        Reviews <?php echo !empty($reviews) ? '<span class="text-xs">(' . count($reviews) . ')</span>' : ''; ?>
                    </button>
                </nav>
            </div>
            <div class="p-6">
                <!-- Description Tab Content -->
                <div id="description-content" class="tab-content">
                    <?php if (!empty($menu_item['description'])): ?>
                        <div class="prose max-w-none">
                            <h3 class="text-lg font-medium text-gray-900 mb-3">Product Description</h3>
                            <p class="text-gray-600 leading-relaxed"><?php echo nl2br(htmlspecialchars($menu_item['description'])); ?></p>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Ingredients Tab Content -->
                <div id="ingredients-content" class="tab-content hidden">
                    <?php if (!empty($menu_item['ingredients'])): ?>
                        <div class="mt-6">
                            <h3 class="text-lg font-medium text-gray-900 mb-3">Ingredients</h3>
                            <p class="text-gray-600 leading-relaxed"><?php echo nl2br(htmlspecialchars($menu_item['ingredients'])); ?></p>
                        </div>
                    <?php else: ?>
                        <div class="mt-6 p-8 text-center bg-gray-50 rounded-lg">
                            <i class="fas fa-clipboard-list text-4xl text-gray-300 mb-3"></i>
                            <h3 class="text-lg font-medium text-gray-600 mb-2">Ingredients Coming Soon</h3>
                            <p class="text-gray-500">Detailed ingredient information will be available soon.</p>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Allergens Tab Content -->
                <div id="allergens-content" class="tab-content hidden">
                    <?php if (!empty($menu_item['allergens'])): ?>
                        <div class="mt-6">
                            <h3 class="text-lg font-medium text-gray-900 mb-3">Allergen Information</h3>
                            <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                                <div class="flex items-start">
                                    <i class="fas fa-exclamation-triangle text-yellow-600 mt-0.5 mr-3"></i>
                                    <p class="text-yellow-800 text-sm leading-relaxed"><?php echo nl2br(htmlspecialchars($menu_item['allergens'])); ?></p>
                                </div>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="mt-6 p-8 text-center bg-gray-50 rounded-lg">
                            <i class="fas fa-exclamation-triangle text-4xl text-gray-300 mb-3"></i>
                            <h3 class="text-lg font-medium text-gray-600 mb-2">Allergen Information Coming Soon</h3>
                            <p class="text-gray-500">Allergen details will be available soon for your safety.</p>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Nutrition Tab Content -->
                <div id="nutrition-content" class="tab-content hidden">
                    <?php if (!empty($menu_item['nutrition_info'])): ?>
                        <div class="mt-6">
                            <h3 class="text-lg font-medium text-gray-900 mb-3">Nutritional Information</h3>
                            <div class="bg-green-50 border border-green-200 rounded-lg p-4">
                                <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
                                    <?php
                                    // Simple nutrition info parsing (you might want to make this more sophisticated)
                                    $nutrition_lines = explode("\n", $menu_item['nutrition_info']);
                                    foreach ($nutrition_lines as $line):
                                        if (!empty(trim($line))):
                                    ?>
                                        <div class="text-green-800">
                                            <?php echo htmlspecialchars(trim($line)); ?>
                                        </div>
                                    <?php
                                        endif;
                                    endforeach;
                                    ?>
                                </div>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="mt-6 p-8 text-center bg-gray-50 rounded-lg">
                            <i class="fas fa-chart-line text-4xl text-gray-300 mb-3"></i>
                            <h3 class="text-lg font-medium text-gray-600 mb-2">Nutrition Information Coming Soon</h3>
                            <p class="text-gray-500">Detailed nutritional data will be available soon.</p>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Reviews Tab Content -->
                <div id="reviews-content" class="tab-content hidden">
                    <?php if (!empty($reviews)): ?>
                        <div class="mt-6">
                            <h3 class="text-lg font-medium text-gray-900 mb-4">Customer Reviews (<?php echo count($reviews); ?>)</h3>
                            <div class="space-y-4">
                                <?php foreach ($reviews as $review): ?>
                                    <div class="border border-gray-200 rounded-lg p-4">
                                        <div class="flex items-start justify-between mb-2">
                                            <div class="flex items-center">
                                                <div class="flex text-yellow-400 mr-2">
                                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                                        <i class="<?php echo $i <= $review['rating'] ? 'fas' : 'far'; ?> fa-star text-sm"></i>
                                                    <?php endfor; ?>
                                                </div>
                                                <span class="font-medium text-gray-900">
                                                    <?php echo htmlspecialchars($review['customer_name'] ?? $review['user_name']); ?>
                                                </span>
                                            </div>
                                            <span class="text-sm text-gray-500">
                                                <?php echo date('M j, Y', strtotime($review['created_at'])); ?>
                                            </span>
                                        </div>
                                        <?php if (!empty($review['review_text'])): ?>
                                            <p class="text-gray-600 text-sm leading-relaxed">
                                                <?php echo nl2br(htmlspecialchars($review['review_text'])); ?>
                                            </p>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="mt-6 p-8 text-center bg-gray-50 rounded-lg">
                            <i class="fas fa-comments text-4xl text-gray-300 mb-3"></i>
                            <h3 class="text-lg font-medium text-gray-600 mb-2">No Reviews Yet</h3>
                            <p class="text-gray-500 mb-4">Be the first to review this product!</p>

                            <?php if (!isset($_SESSION['user_id'])): ?>
                                <p class="text-sm text-gray-400 mb-4">Please <a href="login.php?redirect=<?= urlencode($_SERVER['REQUEST_URI']) ?>" class="text-primary hover:underline">login</a> to leave a review.</p>
                            <?php else: ?>
                                <!-- Review Form for Logged-in Users -->
                                <div class="max-w-md mx-auto mt-6 p-6 bg-white rounded-lg border border-gray-200">
                                    <h4 class="text-md font-medium text-gray-800 mb-4">Share Your Experience</h4>

                                    <?php
                                    // Handle review submission
                                    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_review'])) {
                                        try {
                                            $rating = (int)($_POST['rating'] ?? 0);
                                            $review_text = trim($_POST['review_text'] ?? '');

                                            if (empty($rating) || empty($review_text)) {
                                                throw new Exception('Please provide both rating and review text.');
                                            }

                                            if ($rating < 1 || $rating > 5) {
                                                throw new Exception('Please provide a rating between 1 and 5 stars.');
                                            }

                                            // Insert review into database
                                            $stmt = $pdo->prepare("
                                                INSERT INTO reviews (user_id, menu_item_id, rating, review_text, status, created_at)
                                                VALUES (?, ?, ?, ?, 'pending', NOW())
                                            ");

                                            $stmt->execute([$_SESSION['user_id'], $menu_item_id, $rating, $review_text]);

                                            echo '<div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded mb-4">
                                                    <div class="flex items-center">
                                                        <i class="fas fa-check-circle text-green-600 mr-2"></i>
                                                        <span>Thank you! Your review has been submitted and is pending approval.</span>
                                                    </div>
                                                  </div>';

                                            // Refresh reviews
                                            $reviews_stmt->execute([$menu_item_id]);
                                            $reviews = $reviews_stmt->fetchAll(PDO::FETCH_ASSOC);

                                        } catch (Exception $e) {
                                            echo '<div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded mb-4">
                                                    <div class="flex items-center">
                                                        <i class="fas fa-exclamation-circle text-red-600 mr-2"></i>
                                                        <span>' . htmlspecialchars($e->getMessage()) . '</span>
                                                    </div>
                                                  </div>';
                                        }
                                    }
                                    ?>

                                    <form method="POST" class="space-y-4">
                                        <!-- Rating -->
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-2">Your Rating *</label>
                                            <div class="flex items-center space-x-1">
                                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                                    <button type="button"
                                                            class="star-rating text-2xl transition-all duration-200 hover:scale-110 focus:outline-none focus:ring-2 focus:ring-primary/50 rounded"
                                                            data-rating="<?php echo $i; ?>"
                                                            onclick="setRating(<?php echo $i; ?>)">
                                                        <i class="far fa-star text-gray-300 hover:text-yellow-300 transition-colors duration-200"
                                                           id="star-<?php echo $i; ?>"></i>
                                                    </button>
                                                <?php endfor; ?>
                                                <input type="hidden" name="rating" id="rating-value" value="<?php echo $_POST['rating'] ?? ''; ?>" required>
                                            </div>
                                            <p class="text-xs text-gray-500 mt-1">Click on the stars to rate</p>
                                        </div>

                                        <!-- Review Text -->
                                        <div>
                                            <label for="review_text" class="block text-sm font-medium text-gray-700 mb-2">Your Review *</label>
                                            <textarea id="review_text" name="review_text" rows="3"
                                                      required
                                                      placeholder="Tell others about your experience with this dish..."
                                                      class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-primary focus:border-primary"><?php echo htmlspecialchars($_POST['review_text'] ?? ''); ?></textarea>
                                        </div>

                                        <!-- Submit Button -->
                                        <button type="submit" name="submit_review"
                                                class="w-full bg-primary hover:bg-primary-dark text-white py-2 px-4 rounded-lg font-medium transition-colors">
                                            Submit Review
                                        </button>
                                    </form>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <?php if (empty($menu_item['ingredients']) && empty($menu_item['allergens']) && empty($menu_item['nutrition_info']) && empty($reviews)): ?>
                    <!-- Note about additional information -->
                    <div class="mt-6 p-4 bg-blue-50 rounded-lg border border-blue-200">
                        <div class="flex items-start">
                            <i class="fas fa-info-circle text-blue-500 mt-0.5 mr-3"></i>
                            <div>
                                <h4 class="text-sm font-medium text-blue-800 mb-1">Additional Information</h4>
                                <p class="text-sm text-blue-700">
                                    Detailed ingredients, nutritional information, and allergen details will be available soon.
                                    For now, please refer to the product description above or contact us for specific dietary requirements.
                                </p>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Related Products -->
        <?php if (!empty($related_items)): ?>
                <h2 class="text-xl font-bold text-gray-900 mb-6">You may also like</h2>
                <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-6 gap-4">
                    <?php foreach ($related_items as $item): ?>
                        <div class="bg-white rounded-lg shadow-sm overflow-hidden hover:shadow-md transition-shadow duration-200">
                            <a href="menu-single.php?id=<?= $item['id'] ?>" class="block">
                                <div class="aspect-w-1 aspect-h-1 w-full overflow-hidden">
                                    <?php if (!empty($item['image'])): ?>
                                        <img src="uploads/menu/<?= htmlspecialchars($item['image']) ?>"
                                             alt="<?= htmlspecialchars($item['name']) ?>"
                                             class="w-full h-40 object-cover">
                                    <?php else: ?>
                                        <div class="bg-gray-100 h-40 flex items-center justify-center">
                                            <i class="fas fa-utensils text-4xl text-gray-300"></i>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div class="p-3">
                                    <h3 class="text-sm font-medium text-gray-900 mb-1 line-clamp-2">
                                        <?= htmlspecialchars($item['name']) ?>
                                    </h3>
                                    <div class="flex items-center justify-between mt-2">
                                        <span class="text-sm font-medium text-gray-900">
                                            KSh <?= number_format($item['price'], 2) ?>
                                        </span>
                                    </div>
                                </div>
                            </a>
                        </div>
                    <?php endforeach; ?>
                </div>
        <?php endif; ?>

        <!-- Popular & Frequently Searched Items -->
        <div class="mt-12 space-y-8">
            <?php if (!empty($popular_items)): ?>
                <section>
                    <h2 class="text-xl font-bold text-gray-900 mb-6">Popular Items</h2>
                    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-6 gap-4">
                        <?php foreach ($popular_items as $item): ?>
                            <div class="bg-white rounded-lg shadow-sm overflow-hidden hover:shadow-md transition-shadow duration-200">
                                <a href="menu-single.php?id=<?= $item['id'] ?>" class="block">
                                    <div class="aspect-w-1 aspect-h-1 w-full overflow-hidden">
                                        <?php if (!empty($item['image'])): ?>
                                            <img src="uploads/menu/<?= htmlspecialchars($item['image']) ?>"
                                                 alt="<?= htmlspecialchars($item['name']) ?>"
                                                 class="w-full h-40 object-cover">
                                        <?php else: ?>
                                            <div class="bg-gray-100 h-40 flex items-center justify-center">
                                                <i class="fas fa-utensils text-4xl text-gray-300"></i>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="p-3">
                                        <h3 class="text-sm font-medium text-gray-900 mb-1 line-clamp-2">
                                            <?= htmlspecialchars($item['name']) ?>
                                        </h3>
                                        <div class="flex items-center justify-between mt-2">
                                            <span class="text-sm font-medium text-gray-900">
                                                KSh <?= number_format($item['price'], 2) ?>
                                            </span>
                                            <?php if ($item['review_count'] > 0): ?>
                                                <div class="flex items-center text-xs text-gray-500">
                                                    <i class="fas fa-star text-yellow-400 mr-1"></i>
                                                    <?= number_format($item['avg_rating'] ?? 0, 1) ?>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </a>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </section>
            <?php endif; ?>

            <!-- Recently Viewed Items (JavaScript powered) -->
            <section id="recently-viewed-section" class="hidden">
                <h2 class="text-xl font-bold text-gray-900 mb-6">Recently Viewed</h2>
                <div id="recently-viewed-grid" class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-6 gap-4">
                    <!-- Items will be populated by JavaScript -->
                </div>
                <div id="recently-viewed-fallback" class="hidden text-center py-8 text-gray-500">
                    <i class="fas fa-clock text-3xl mb-2"></i>
                    <p>Your recently viewed items will appear here</p>
                </div>
            </section>
        </div>
    </div>
</div>

<script>
// Toggle wishlist item
function toggleWishlist(event, menuItemId) {
    event.preventDefault();
    
    const form = event.target;
    const formData = new FormData(form);
    
    // Toggle the action for next time
    const actionInput = form.querySelector('input[name="wishlist_action"]');
    actionInput.value = actionInput.value === 'add' ? 'remove' : 'add';
    
    // Toggle heart icon
    const heartIcon = form.querySelector('i.fa-heart');
    heartIcon.classList.toggle('far');
    heartIcon.classList.toggle('fas');
    
    // Send AJAX request
    fetch('', {
        method: 'POST',
        body: formData
    });
    
    return false;
}

// Quantity Controls
function incrementQuantity() {
    const quantityInput = document.getElementById('quantity');
    const currentValue = parseInt(quantityInput.value);
    if (currentValue < parseInt(quantityInput.max)) {
        quantityInput.value = currentValue + 1;
        updateTotalPrice();
    }
}

function decrementQuantity() {
    const quantityInput = document.getElementById('quantity');
    const currentValue = parseInt(quantityInput.value);
    if (currentValue > parseInt(quantityInput.min)) {
        quantityInput.value = currentValue - 1;
        updateTotalPrice();
    }
}

function validateQuantity(input) {
    const value = parseInt(input.value);
    const max = parseInt(input.max) || 20;
    const min = parseInt(input.min) || 1;
    
    if (isNaN(value) || value < min) {
        input.value = min;
    } else if (value > max) {
        input.value = max;
    }
    updateTotalPrice();
}

function updateTotalPrice() {
    const quantity = parseInt(document.getElementById('quantity').value);
    const price = <?= $menu_item['price'] ?>;
    const total = (quantity * price).toFixed(2);
    document.getElementById('total-price').textContent = total;
}

// Tab functionality
function showTab(tabName) {
    // Hide all tab contents
    const tabContents = document.querySelectorAll('.tab-content');
    tabContents.forEach(content => {
        content.classList.add('hidden');
    });

    // Remove active state from all tabs
    const tabButtons = document.querySelectorAll('[onclick*="showTab"]');
    tabButtons.forEach(button => {
        button.classList.remove('border-primary', 'text-primary', 'bg-primary/5');
        button.classList.add('border-transparent');
    });

    // Show selected tab content
    const selectedContent = document.getElementById(tabName + '-content');
    if (selectedContent) {
        selectedContent.classList.remove('hidden');
    }

    // Add active state to clicked tab
    event.target.classList.remove('border-transparent');
    event.target.classList.add('border-primary', 'text-primary', 'bg-primary/5');
}

// Show description tab by default
document.addEventListener('DOMContentLoaded', function() {
    showTab('description');

    // Initialize star rating display if there's a pre-selected value
    const initialRating = document.getElementById('rating-value')?.value;
    if (initialRating) {
        setRating(parseInt(initialRating));
    }

    // Initialize recently viewed items
    initializeRecentlyViewed();
});

function initializeRecentlyViewed() {
    const currentItemId = <?= $menu_item_id ?>;
    const recentlyViewedSection = document.getElementById('recently-viewed-section');
    const recentlyViewedGrid = document.getElementById('recently-viewed-grid');

    // Get recently viewed items from localStorage
    let recentlyViewed = JSON.parse(localStorage.getItem('recentlyViewed') || '[]');

    // Add current item to recently viewed if not already there
    if (!recentlyViewed.includes(currentItemId)) {
        recentlyViewed.unshift(currentItemId);
        // Keep only last 10 items
        recentlyViewed = recentlyViewed.slice(0, 10);
        localStorage.setItem('recentlyViewed', JSON.stringify(recentlyViewed));
    }

    // If we have recently viewed items (excluding current), show the section
    if (recentlyViewed.length > 1) {
        loadRecentlyViewedItems(recentlyViewed.filter(id => id !== currentItemId).slice(0, 6));
        recentlyViewedSection.classList.remove('hidden');
    }
}

function loadRecentlyViewedItems(itemIds) {
    if (itemIds.length === 0) return;

    const recentlyViewedGrid = document.getElementById('recently-viewed-grid');
    const fallbackElement = document.getElementById('recently-viewed-fallback');

    // Show loading state
    recentlyViewedGrid.innerHTML = '<div class="col-span-full text-center py-8"><i class="fas fa-spinner fa-spin text-2xl text-gray-400"></i></div>';

    // Fetch recently viewed items data via AJAX
    fetch('api/get_menu_items.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({ ids: itemIds })
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('Network response was not ok');
        }
        return response.json();
    })
    .then(data => {
        // Clear loading state
        recentlyViewedGrid.innerHTML = '';

        if (data.success && data.items.length > 0) {
            data.items.forEach(item => {
                const itemElement = document.createElement('div');
                itemElement.className = 'bg-white rounded-lg shadow-sm overflow-hidden hover:shadow-md transition-shadow duration-200';
                itemElement.innerHTML = `
                    <a href="menu-single.php?id=${item.id}" class="block">
                        <div class="aspect-w-1 aspect-h-1 w-full overflow-hidden">
                            ${item.image ?
                                `<img src="uploads/menu/${item.image}" alt="${item.name}" class="w-full h-40 object-cover">` :
                                `<div class="bg-gray-100 h-40 flex items-center justify-center">
                                    <i class="fas fa-utensils text-4xl text-gray-300"></i>
                                 </div>`
                            }
                        </div>
                        <div class="p-3">
                            <h3 class="text-sm font-medium text-gray-900 mb-1 line-clamp-2">
                                ${item.name}
                            </h3>
                            <div class="flex items-center justify-between mt-2">
                                <span class="text-sm font-medium text-gray-900">
                                    KSh ${parseFloat(item.price).toFixed(2)}
                                </span>
                            </div>
                        </div>
                    </a>
                `;
                recentlyViewedGrid.appendChild(itemElement);
            });
        } else {
            // Show fallback message if no items or error
            fallbackElement.classList.remove('hidden');
        }
    })
    .catch(error => {
        console.error('Error loading recently viewed items:', error);
        // Show fallback message on error
        recentlyViewedGrid.innerHTML = '';
        fallbackElement.classList.remove('hidden');
    });
}

// Star rating functionality
function setRating(rating) {
    // Update hidden input value
    document.getElementById('rating-value').value = rating;

    // Update star display
    for (let i = 1; i <= 5; i++) {
        const star = document.getElementById('star-' + i);
        if (i <= rating) {
            // Fill and glow selected stars
            star.className = 'fas fa-star text-yellow-400 drop-shadow-lg';
        } else {
            // Empty unselected stars
            star.className = 'far fa-star text-gray-300 hover:text-yellow-300 transition-colors duration-200';
        }
    }
}

// Initialize star rating on page load
document.addEventListener('DOMContentLoaded', function() {
    showTab('description');

    // Initialize star rating display if there's a pre-selected value
    const initialRating = document.getElementById('rating-value')?.value;
    if (initialRating) {
        setRating(parseInt(initialRating));
    }

    // Initialize recently viewed items
    initializeRecentlyViewed();
});
</script>

<?php include 'includes/footer.php'; ?>