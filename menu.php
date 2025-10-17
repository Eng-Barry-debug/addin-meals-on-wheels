<?php
// Ensure no output is sent before headers
// Temporarily enable error reporting for debugging, but set display_errors to 0 for production.
error_reporting(E_ALL);
ini_set('display_errors', 0); // Keep this at 0 in production to prevent exposing sensitive info.
// To debug 500 errors, you might temporarily set display_errors = 1, but remove it immediately after fixing.

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'includes/config.php';
require_once 'includes/Cart.php'; // <--- THIS IS THE CRUCIAL ADDITION

// Fetch menu items from database
try {
    // Get all active categories first
    $stmt = $pdo->query("SELECT * FROM categories WHERE is_active = 1 ORDER BY name");
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get all active menu items
    $stmt = $pdo->query("
        SELECT mi.*, c.name as category_name
        FROM menu_items mi
        LEFT JOIN categories c ON mi.category_id = c.id
        WHERE mi.status = 'active'
        ORDER BY c.name, mi.name
    ");
    $menu_items = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get featured items and specials
    $stmt = $pdo->query("
        SELECT DISTINCT mi.*, c.name as category_name
        FROM menu_items mi
        LEFT JOIN categories c ON mi.category_id = c.id
        WHERE mi.status = 'active'
        AND (mi.is_featured = 1 OR LOWER(c.name) = 'specials')
        ORDER BY mi.is_featured DESC, mi.created_at DESC
        LIMIT 12
    ");
    $featured_items = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Format the data to match the expected structure
    $format_item = function($item) {
        // Build the full image path if image exists
        $imagePath = '';
        if (!empty($item['image'])) {
            // Check if the image path already includes the uploads directory
            if (strpos($item['image'], 'uploads/') === 0) {
                $imagePath = $item['image'];
            } else {
                $imagePath = 'uploads/menu/' . $item['image'];
            }
        }

        return [
            'id' => $item['id'],
            'name' => $item['name'],
            'description' => $item['description'],
            'image' => !empty($imagePath) ? $imagePath : '/assets/img/placeholder-food.jpg',
            'price' => (float)$item['price'],
            'category' => strtolower($item['category_name']),
            'is_featured' => (bool)($item['is_featured'] ?? false)
        ];
    };

    $menu_items = array_map($format_item, $menu_items);
    $featured_items = array_map($format_item, $featured_items);
} catch (PDOException $e) {
    // Log error and show user-friendly message
    error_log("Database error: " . $e->getMessage());
    $error_message = "We're having trouble loading our menu. Please try again later.";
    $categories = []; // Initialize empty array for categories
    $menu_items = []; // Initialize empty array for menu items
    $featured_items = []; // Initialize empty array for featured items
}

// Handle add to cart action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_to_cart'])) {
    // --- Start of AJAX response setup ---
    // Ensure no output before JSON header and clear output buffer immediately
    if (ob_get_length()) {
        ob_clean();
    }
    header('Content-Type: application/json');
    // Ensure headers haven't been sent before attempting to set new ones
    if (headers_sent()) {
        error_log('Headers already sent when attempting to send JSON response for add_to_cart.');
        // Fallback or just exit, as we can't send proper JSON now
        echo json_encode(['success' => false, 'message' => 'Server error: Output started before JSON response could be sent.']);
        exit();
    }
    // --- End of AJAX response setup ---

    $response = ['success' => false, 'message' => ''];
    $product_id = (int)($_POST['product_id'] ?? 0);
    $quantity = isset($_POST['quantity']) ? (int)$_POST['quantity'] : 1;
    
    // Validate quantity
    if ($quantity < 1) {
        // Use http_response_code for clearer error status in AJAX
        http_response_code(400); 
        $response['message'] = 'Invalid quantity';
        echo json_encode($response);
        exit();
    }
    
    // Find the product in the database
    $product = null;
    try {
        $stmt = $pdo->prepare("SELECT * FROM menu_items WHERE id = ? AND status = 'active'");
        $stmt->execute([$product_id]);
        $product = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$product) {
             // Use http_response_code for clearer error status in AJAX
            http_response_code(404);
            throw new Exception('Product not found or not available');
        }
        
        // Initialize cart (now that Cart.php is included)
        $cart = new Cart($pdo);
        
        // Add item to cart using Cart class
        $cart->addItem($product_id, $quantity, $product['price']);
        
        // Get updated cart items to calculate total count
        $cartItems = $cart->getItems();
        $totalItems = array_sum(array_column($cartItems, 'quantity'));
        
        $response = [
            'success' => true,
            'message' => $product['name'] . ' added to cart!',
            'cartCount' => $totalItems
        ];
        
    } catch (Exception $e) {
        error_log("Error when adding to cart: " . $e->getMessage());
        // Set an HTTP response code for the error if not already set by specific exceptions
        if (http_response_code() === 200) {
            http_response_code(500); // Default to 500 for generic server errors
        }
        $response['message'] = 'Error adding item to cart. Please try again. (' . $e->getMessage() . ')'; // Include message temporarily for debug
    }
    
    echo json_encode($response);
    exit();
}

// Helper function to send JSON response (This function is now largely redundant due to the in-place AJAX handling above)
function sendJsonResponse($data) {
    // Clear any previous output
    if (ob_get_length()) ob_clean();
    
    // Set JSON header
    header('Content-Type: application/json');
    
    // Ensure we're not sending any HTML in the response
    if (headers_sent()) {
        error_log('Headers already sent when trying to send JSON response');
        die(json_encode(['success' => false, 'message' => 'Server error: Headers already sent']));
    }
    
    echo json_encode($data);
    exit();
}

$page_title = "Our Menu - Addins Meals on Wheels";
?>

<?php include 'includes/header.php'; ?>

<!-- Hero Section -->
<section class="relative h-screen overflow-hidden">
    <div class="absolute inset-0 bg-cover bg-center" style="background-image: url('assets/img/freshfoods.png');">
        <!-- Enhanced overlay for better text visibility -->
        <div class="absolute inset-0 bg-black/85"></div>
        <div class="absolute inset-0 bg-black/60"></div>
        <!-- Additional brand color overlay -->
        <div class="absolute inset-0 bg-primary/10"></div>
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
                        <h1 class="text-4xl md:text-6xl font-bold mb-6" style="text-shadow: 3px 3px 8px rgba(0, 0, 0, 0.9), 0 0 15px rgba(0, 0, 0, 0.6);">Our Delicious Menu</h1>
                        <p class="text-xl md:text-2xl mb-8 max-w-3xl mx-auto" style="text-shadow: 2px 2px 6px rgba(0, 0, 0, 0.9);">Explore our selection of chef-prepared meals made with the finest ingredients</p>

                        <!-- Menu highlights -->
                        <div class="grid md:grid-cols-4 gap-6 mt-12 max-w-5xl mx-auto">
                            <div class="bg-white/20 backdrop-blur-sm rounded-lg p-4 border border-white/30">
                                <div class="text-2xl mb-2">
                                    <i class="fas fa-leaf text-green-400" style="text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.8);"></i>
                                </div>
                                <h3 class="font-bold text-sm text-white mb-1" style="text-shadow: 1px 1px 3px rgba(0, 0, 0, 0.8);">Fresh & Healthy</h3>
                                <p class="text-white/80 text-xs" style="text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.7);">Locally sourced ingredients</p>
                            </div>
                            <div class="bg-white/20 backdrop-blur-sm rounded-lg p-4 border border-white/30">
                                <div class="text-2xl mb-2">
                                    <i class="fas fa-clock text-blue-400" style="text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.8);"></i>
                                </div>
                                <h3 class="font-bold text-sm text-white mb-1" style="text-shadow: 1px 1px 3px rgba(0, 0, 0, 0.8);">Quick Delivery</h3>
                                <p class="text-white/80 text-xs" style="text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.7);">Hot meals in minutes</p>
                            </div>
                            <div class="bg-white/20 backdrop-blur-sm rounded-lg p-4 border border-white/30">
                                <div class="text-2xl mb-2">
                                    <i class="fas fa-utensils text-yellow-400" style="text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.8);"></i>
                                </div>
                                <h3 class="font-bold text-sm text-white mb-1" style="text-shadow: 1px 1px 3px rgba(0, 0, 0, 0.8);">Chef Prepared</h3>
                                <p class="text-white/80 text-xs" style="text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.7);">Restaurant quality meals</p>
                            </div>
                            <div class="bg-white/20 backdrop-blur-sm rounded-lg p-4 border border-white/30">
                                <div class="text-2xl mb-2">
                                    <i class="fas fa-heart text-red-400" style="text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.8);"></i>
                                </div>
                                <h3 class="font-bold text-sm text-white mb-1" style="text-shadow: 1px 1px 3px rgba(0, 0, 0, 0.8);">Made with Love</h3>
                                <p class="text-white/80 text-xs" style="text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.7);">Carefully crafted recipes</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Menu Filter -->
<section class="py-12 bg-gradient-to-br from-gray-50 to-gray-100 border-b border-gray-200">
    <div class="container mx-auto px-4">
        <div class="max-w-4xl mx-auto">
            <!-- Filter Header -->
            <div class="text-center mb-8">
                <h3 class="text-2xl font-bold text-gray-800 mb-2">Browse Our Menu</h3>
                <p class="text-gray-600">Choose a category to find exactly what you're craving</p>
                <div class="w-16 h-1 bg-primary mx-auto mt-3"></div>
            </div>

            <!-- Filter Buttons Container -->
            <div class="flex flex-wrap justify-center gap-3 md:gap-4">
                <!-- All Items Button -->
                <button class="filter-btn active group" data-category="all">
                    <div class="flex items-center space-x-2">
                        <i class="fas fa-th-large text-sm"></i>
                        <span>All Items</span>
                        <span class="bg-white text-primary text-xs font-bold px-2 py-0.5 rounded-full opacity-0 group-hover:opacity-100 transition-opacity duration-200">
                            <?php echo count($menu_items); ?>
                        </span>
                    </div>
                </button>

                <!-- Category Buttons -->
                <?php foreach ($categories as $category): ?>
                    <?php
                    $categoryLower = strtolower($category['name']);
                    $categoryCount = 0;
                    foreach ($menu_items as $item) {
                        if (strtolower($item['category']) === $categoryLower) {
                            $categoryCount++;
                        }
                    }
                    ?>
                    <button class="filter-btn group" data-category="<?php echo htmlspecialchars($categoryLower); ?>">
                        <div class="flex items-center space-x-2">
                            <i class="fas <?php
                                echo match($categoryLower) {
                                    'breakfast' => 'fa-coffee',
                                    'lunch' => 'fa-utensils',
                                    'dinner' => 'fa-moon',
                                    'appetizers', 'appetizers 2.1' => 'fa-seedling',
                                    'desserts' => 'fa-ice-cream',
                                    'beverages', 'beverages 1' => 'fa-mug-hot',
                                    'specials' => 'fa-star',
                                    default => 'fa-utensils'
                                };
                            ?> text-sm"></i>
                            <span><?php echo htmlspecialchars($category['name']); ?></span>
                            <?php if ($categoryCount > 0): ?>
                                <span class="bg-white text-primary text-xs font-bold px-2 py-0.5 rounded-full opacity-0 group-hover:opacity-100 transition-opacity duration-200">
                                    <?php echo $categoryCount; ?>
                                </span>
                            <?php endif; ?>
                        </div>
                    </button>
                <?php endforeach; ?>
            </div>

            <!-- Active Filter Info -->
            <div class="text-center mt-6">
                <div class="inline-flex items-center bg-white rounded-full px-4 py-2 shadow-sm border border-gray-200">
                    <span class="text-sm text-gray-600">Showing:</span>
                    <span class="text-sm font-semibold text-primary ml-2" id="active-filter-text">All Items</span>
                    <span class="text-sm text-gray-600 ml-2" id="active-filter-count">• <?php echo count($menu_items); ?> items</span>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Menu Items Grid -->
<section class="py-12 bg-white">
    <div class="container mx-auto px-4">
        <?php if (empty($menu_items)): ?>
            <div class="text-center py-12">
                <h3 class="text-2xl font-semibold text-gray-700">No menu items available at the moment.</h3>
                <p class="text-gray-500 mt-2">Please check back later or contact us for more information.</p>
            </div>
        <?php else: ?>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-8">
                <?php foreach ($menu_items as $item): ?>
                    <div class="menu-item bg-white rounded-lg overflow-hidden shadow-md hover:shadow-xl transition-shadow duration-300" data-category="<?php echo htmlspecialchars($item['category'] ?? 'main'); ?>">
                        <a href="menu-single.php?id=<?php echo $item['id']; ?>" class="block">
                            <div class="relative h-48 overflow-hidden">
                                <?php if (!empty($item['image'])): ?>
                                    <img src="<?php echo htmlspecialchars($item['image']); ?>" 
                                         alt="<?php echo htmlspecialchars($item['name']); ?>" 
                                         class="w-full h-full object-cover transition-transform duration-500 hover:scale-110">
                                <?php else: ?>
                                    <div class="w-full h-full bg-gray-200 flex items-center justify-center">
                                        <i class="fas fa-utensils text-4xl text-gray-400"></i>
                                    </div>
                                <?php endif; ?>
                                
                                <?php if (isset($item['is_vegetarian']) && $item['is_vegetarian']): ?>
                                    <span class="absolute top-2 right-2 bg-green-500 text-white text-xs font-bold px-2 py-1 rounded-full">
                                        <i class="fas fa-leaf"></i> Veg
                                    </span>
                                <?php endif; ?>
                                
                                <?php if (isset($item['is_spicy']) && $item['is_spicy']): ?>
                                    <span class="absolute top-2 left-2 bg-red-500 text-white text-xs font-bold px-2 py-1 rounded-full">
                                        <i class="fas fa-pepper-hot"></i> Spicy
                                    </span>
                                <?php endif; ?>
                            </div>
                            
                            <div class="p-4">
                                <div class="flex justify-between items-start mb-2">
                                    <h3 class="text-xl font-bold text-dark"><?php echo htmlspecialchars($item['name']); ?></h3>
                                    <span class="text-lg font-bold text-primary">KES <?php echo number_format($item['price'], 2); ?></span>
                                </div>
                                
                                <?php 
                                // Show only first 100 characters of description
                                $short_desc = strlen($item['description']) > 100 
                                    ? substr($item['description'], 0, 100) . '...' 
                                    : $item['description'];
                                ?>
                                <p class="text-gray-600 mb-4"><?php echo htmlspecialchars($short_desc); ?></p>
                                <span class="text-primary text-sm font-medium">View details →</span>
                            </div>
                        </a>
                        
                        <div class="px-4 pb-4">
                            <button class="add-to-cart-btn w-full bg-primary hover:bg-secondary text-white px-4 py-3 rounded-lg font-medium transition-all duration-200 flex items-center justify-center group hover:shadow-lg transform hover:-translate-y-0.5" 
                                    data-id="<?php echo $item['id']; ?>"
                                    data-name="<?php echo htmlspecialchars($item['name']); ?>"
                                    data-price="<?php echo $item['price']; ?>">
                                <i class="fas fa-plus mr-2 group-hover:scale-110 transition-transform"></i>
                                <span>Add to Cart</span>
                                <div class="ml-2 opacity-0 group-hover:opacity-100 transition-opacity">
                                    <i class="fas fa-shopping-cart text-sm"></i>
                                </div>
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>

<!-- Weekly Specials -->
<section class="py-12 bg-light" id="specials">
    <div class="container mx-auto px-4">
        <div class="text-center mb-12">
            <h2 class="text-3xl font-bold mb-4">This Week's Specials</h2>
            <div class="w-20 h-1 bg-primary mx-auto mb-8"></div>
        
        <?php if (!empty($featured_items)): ?>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                <?php foreach ($featured_items as $item): ?>
                    <div class="special-item bg-white rounded-lg overflow-hidden shadow-lg transform transition-transform hover:scale-105 h-full flex flex-col">
                        <div class="relative h-48 overflow-hidden">
                            <img src="<?= htmlspecialchars($item['image']) ?>" 
                                 alt="<?= htmlspecialchars($item['name']) ?>" 
                                 class="w-full h-full object-cover">
                            <div class="absolute inset-0 bg-black/70"></div>
                            <div class="absolute bottom-0 left-0 p-4 text-white">
                                <span class="bg-primary text-xs font-semibold px-2 py-1 rounded">Featured</span>
                                <h3 class="text-xl font-bold mt-2"><?= htmlspecialchars($item['name']) ?></h3>
                            </div>
                        </div>
                        <div class="p-4 flex flex-col flex-grow">
                            <p class="text-gray-600 mb-3 flex-grow"><?php 
                                $short_desc = strlen($item['description']) > 80 
                                    ? substr($item['description'], 0, 80) . '...' 
                                    : $item['description'];
                                echo htmlspecialchars($short_desc); 
                            ?></p>
                            <div class="mb-3">
                                <a href="menu-single.php?id=<?= $item['id'] ?>" class="text-primary text-sm font-medium hover:underline">View details →</a>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="font-bold">KES <?= number_format($item['price'], 2, '.', ',') ?></span>
                                <button class="add-to-cart-btn bg-primary hover:bg-secondary text-white px-4 py-3 rounded-lg text-sm font-medium transition-all duration-200 flex items-center group hover:shadow-lg transform hover:-translate-y-0.5" 
                                        data-id="<?= $item['id'] ?>"
                                        data-name="<?= htmlspecialchars($item['name']) ?>"
                                        data-price="<?= $item['price'] ?>">
                                    <i class="fas fa-plus mr-2 group-hover:scale-110 transition-transform"></i> Add to Cart
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="text-center py-8">
                <div class="inline-block p-4 bg-white rounded-lg shadow-md">
                    <i class="fas fa-star text-yellow-400 text-4xl mb-3"></i>
                    <h3 class="text-lg font-medium text-gray-900">No Featured Items</h3>
                    <p class="text-gray-500 mt-1">Check back soon for our weekly specials!</p>
                </div>
            </div>
        <?php endif; ?>
        </div>
    </div>
</section>

<!-- Add to Cart Modal -->
<div id="cartModal" class="fixed inset-0 bg-black/50 hidden items-center justify-center z-50 backdrop-blur-sm p-4">
    <div class="bg-white rounded-2xl p-8 max-w-2xl w-full mx-auto shadow-2xl transform scale-95 opacity-0 transition-all duration-300 max-h-[90vh] overflow-y-auto">
        <div class="flex justify-between items-center mb-6">
            <h3 class="text-2xl font-bold text-gray-800 flex items-center">
                <i class="fas fa-shopping-cart text-primary mr-3"></i> Add to Cart
            </h3>
            <button id="closeModal" class="text-gray-400 hover:text-gray-600 transition-colors p-2 hover:bg-gray-100 rounded-full">
                <i class="fas fa-times text-xl"></i>
            </button>
        </div>

        <div id="modalContent" class="space-y-6">
            <!-- Product info will be inserted here -->
        </div>

        <!-- Enhanced close button -->
        <div class="mt-8 pt-6 border-t border-gray-200">
            <button id="closeModalBottom" class="w-full px-4 py-3 text-gray-600 hover:text-gray-800 transition-colors">
                <i class="fas fa-arrow-left mr-2"></i> Continue Shopping
            </button>
        </div>
    </div>
</div>

<script>
// Filter menu items
const filterBtns = document.querySelectorAll('.filter-btn');
const menuItems = document.querySelectorAll('.menu-item');
const activeFilterText = document.getElementById('active-filter-text');
const activeFilterCount = document.getElementById('active-filter-count');

filterBtns.forEach(btn => {
    btn.addEventListener('click', () => {
        // Update active button
        filterBtns.forEach(b => {
            b.classList.remove('active');
            // Remove the ::after pseudo-element indicator
            b.style.setProperty('--active-indicator', 'none');
        });
        btn.classList.add('active');

        const category = btn.dataset.category;
        const categoryName = btn.querySelector('span').textContent;

        // Update active filter display
        if (activeFilterText) {
            activeFilterText.textContent = categoryName;
        }

        // Filter items
        let visibleCount = 0;
        menuItems.forEach(item => {
            if (category === 'all' || item.dataset.category === category) {
                item.style.display = 'block';
                visibleCount++;
            } else {
                item.style.display = 'none';
            }
        });

        // Update count display
        if (activeFilterCount) {
            activeFilterCount.textContent = `• ${visibleCount} item${visibleCount !== 1 ? 's' : ''}`;
        }

        // Add smooth transition effect
        menuItems.forEach(item => {
            item.style.opacity = '0';
            item.style.transform = 'translateY(20px)';

            setTimeout(() => {
                if (category === 'all' || item.dataset.category === category) {
                    item.style.opacity = '1';
                    item.style.transform = 'translateY(0)';
                }
            }, 100);
        });
    });
});

// Enhanced Add to cart functionality
document.querySelectorAll('.add-to-cart-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        const id = this.dataset.id;
        const name = this.dataset.name;
        const price = this.dataset.price;

        // Show loading state on button
        this.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> Adding...';
        this.disabled = true;

        // Update modal content with enhanced design
        document.getElementById('modalContent').innerHTML = `
            <div class="text-center mb-8">
                <div class="w-24 h-24 bg-primary rounded-full flex items-center justify-center mx-auto mb-6 shadow-lg">
                    <i class="fas fa-utensils text-4xl text-white"></i>
                </div>
                <h4 class="text-2xl font-bold text-gray-800 mb-3">Add to Cart</h4>
                <p class="text-gray-600 text-lg">How many <span class="font-semibold text-primary">${name}</span> would you like?</p>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
                <div class="bg-gray-50 rounded-xl p-8">
                    <div class="flex items-center justify-between mb-6">
                        <label class="text-lg font-medium text-gray-700">Quantity:</label>
                        <span class="text-sm text-gray-500 bg-white px-3 py-1 rounded-full">Max 99 items</span>
                    </div>
                    <div class="flex items-center justify-center bg-white rounded-lg border-2 border-gray-200 hover:border-primary transition-colors shadow-sm">
                        <button type="button" class="quantity-btn px-6 py-4 hover:bg-red-50 text-gray-600 hover:text-red-600 transition-all duration-200 rounded-l-lg text-lg" onclick="updateQuantity(-1, ${price})" title="Decrease quantity">
                            <i class="fas fa-minus text-xl"></i>
                        </button>
                        <input type="number" id="quantity" value="1" min="1" max="99" class="w-20 text-center py-4 border-0 focus:ring-0 focus:outline-none text-2xl font-semibold">
                        <button type="button" class="quantity-btn px-6 py-4 hover:bg-green-50 text-gray-600 hover:text-green-600 transition-all duration-200 rounded-r-lg text-lg" onclick="updateQuantity(1, ${price})" title="Increase quantity">
                            <i class="fas fa-plus text-xl"></i>
                        </button>
                    </div>
                </div>

                <div class="bg-primary/5 rounded-xl p-8 border border-primary/20">
                    <div class="text-center mb-6">
                        <h5 class="text-lg font-semibold text-gray-800 mb-2">Order Summary</h5>
                        <div class="w-16 h-1 bg-primary mx-auto"></div>
                    </div>
                    <div class="flex justify-between items-center text-2xl mb-4">
                        <span class="font-medium">Total:</span>
                        <span id="itemTotal" class="font-bold text-primary">KES ${parseFloat(price).toFixed(2)}</span>
                    </div>
                    <div class="text-sm text-gray-600 bg-white/50 rounded-lg p-3">
                        <i class="fas fa-info-circle mr-2 text-primary"></i> Price may vary based on customizations
                    </div>
                </div>
            </div>

            <div class="flex gap-3">
                <button type="button" onclick="addToCart(${id}, '${name.replace(/'/g, "\\'")}', ${price})"
                        class="flex-1 bg-primary text-white py-4 px-6 rounded-xl font-bold hover:opacity-90 transition-all transform hover:-translate-y-0.5 shadow-lg hover:shadow-xl group">
                    <i class="fas fa-shopping-cart mr-2 group-hover:scale-110 transition-transform"></i>
                    Add to Cart
                </button>
                <button type="button" onclick="closeModal()"
                        class="px-6 py-4 border-2 border-gray-200 text-gray-600 rounded-xl font-medium hover:bg-gray-50 transition-all">
                    Cancel
                </button>
            </div>
        `;

        // Show modal with animation
        const modal = document.getElementById('cartModal');
        modal.classList.remove('hidden');
        modal.classList.add('flex');

        // Animate modal in
        setTimeout(() => {
            const modalContent = modal.querySelector('.bg-white');
            modalContent.classList.remove('scale-95', 'opacity-0');
            modalContent.classList.add('scale-100', 'opacity-100');
        }, 10);

        // Re-enable button
        this.innerHTML = '<i class="fas fa-plus mr-2 group-hover:scale-110 transition-transform"></i> <span>Add to Cart</span> <div class="ml-2 opacity-0 group-hover:opacity-100 transition-opacity"> <i class="fas fa-shopping-cart text-sm"></i> </div>';
        this.disabled = false;
    });
});

// Enhanced close modal functionality
function closeModal() {
    const modal = document.getElementById('cartModal');
    const modalContent = modal.querySelector('.bg-white');

    // Animate modal out
    modalContent.classList.remove('scale-100', 'opacity-100');
    modalContent.classList.add('scale-95', 'opacity-0');

    setTimeout(() => {
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    }, 200);
}

// Close modal events
document.getElementById('closeModal').addEventListener('click', closeModal);
document.getElementById('closeModalBottom').addEventListener('click', closeModal);

// Close modal when clicking outside
window.addEventListener('click', (e) => {
    const modal = document.getElementById('cartModal');
    if (e.target === modal) {
        closeModal();
    }
});

// Enhanced quantity update
function updateQuantity(change, price) {
    const input = document.getElementById('quantity');
    let value = parseInt(input.value) + change;

    if (value < 1) value = 1;
    if (value > 99) value = 99;

    input.value = value;

    // Update total with animation
    const totalElement = document.getElementById('itemTotal');

    if (totalElement) {
        totalElement.classList.add('animate-pulse');
        setTimeout(() => {
            totalElement.textContent = 'KES ' + (price * value).toFixed(2);
            totalElement.classList.remove('animate-pulse');
        }, 150);
    }
}

// Add to cart
function addToCart(id, name, price) {
    const quantity = parseInt(document.getElementById('quantity').value);
    const addToCartBtn = document.querySelector('button[onclick*="addToCart"]');

    // Disable button to prevent multiple clicks
    addToCartBtn.disabled = true;
    addToCartBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> Adding to Cart...';

    // Create form data
    const formData = new FormData();
    formData.append('add_to_cart', '1');
    formData.append('product_id', id);
    formData.append('quantity', quantity);

    // Make AJAX request
    fetch(window.location.href, {
        method: 'POST',
        body: formData,
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => {
        // Check if the response was NOT ok (e.g., HTTP 4xx or 5xx status)
        if (!response.ok) {
            // Attempt to read the JSON error message, if any
            return response.json().then(errorData => {
                throw new Error(errorData.message || 'Server error occurred.');
            }).catch(() => {
                // If it's not JSON, just throw a generic error
                throw new Error('Server error: Could not parse response.');
            });
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            // Update cart count in the header
            updateCartCount(data.cartCount || quantity);

            // Show enhanced success message
            showNotification('success', `${name} (${quantity}x) added to cart!`);

            // Close modal with animation
            closeModal();

            // Optional: Show cart preview or redirect to cart
            setTimeout(() => {
                // Optionally reload cart to reflect changes immediately
                // if (confirm('Item added to cart! Would you like to view your cart?')) {
                //     window.location.href = 'cart.php';
                // }
                // For this example, we just show notification and close modal,
                // actual cart state reflected on cart page visit.
            }, 500); // Give notification time to show
        } else {
            showNotification('error', data.message || 'Failed to add item to cart');
            addToCartBtn.disabled = false;
            addToCartBtn.innerHTML = '<i class="fas fa-shopping-cart mr-2 group-hover:scale-110 transition-transform"></i> Add to Cart';
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('error', 'An error occurred: ' + error.message); // Display the actual error message
        addToCartBtn.disabled = false;
        addToCartBtn.innerHTML = '<i class="fas fa-shopping-cart mr-2 group-hover:scale-110 transition-transform"></i> Add to Cart';
    });
}

// Update cart count in header
function updateCartCount(newCount) {
    const cartCountElements = document.querySelectorAll('.cart-count, .fa-shopping-cart + span');
    if (cartCountElements.length > 0) {
        cartCountElements.forEach(el => {
            el.textContent = newCount > 99 ? '99+' : newCount;
            el.classList.add('animate-pulse');
            setTimeout(() => el.classList.remove('animate-pulse'), 1000);
        });
    }
}

// Enhanced notification function
function showNotification(type, message) {
    // Remove any existing notifications
    const existingAlerts = document.querySelectorAll('.menu-notification');
    existingAlerts.forEach(alert => alert.remove());

    const notification = document.createElement('div');
    notification.className = `menu-notification fixed top-4 right-4 z-50 px-6 py-4 rounded-xl shadow-lg text-white font-medium transform translate-x-full transition-transform duration-300 ${
        type === 'success' ? 'bg-green-500' : 'bg-red-500'
    }`;

    notification.innerHTML = `
        <div class="flex items-center">
            <i class="fas ${type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'} mr-3 text-lg"></i>
            <div class="flex-1">
                <div class="font-semibold">${type === 'success' ? 'Success!' : 'Error!'}</div>
                <div class="text-sm opacity-90">${message}</div>
            </div>
            <button onclick="this.parentElement.parentElement.remove()" class="ml-4 text-white/70 hover:text-white transition-colors">
                <i class="fas fa-times"></i>
            </button>
        </div>
    `;

    document.body.appendChild(notification);

    // Animate in
    setTimeout(() => {
        notification.classList.remove('translate-x-full');
    }, 100);

    // Auto-remove after delay
    setTimeout(() => {
        notification.classList.add('translate-x-full');
        setTimeout(() => notification.remove(), 300);
    }, 4000);
}
</script>

<style>
/* Professional Filter Button Styles */
.filter-btn {
    @apply relative px-5 py-3 rounded-full font-medium text-sm transition-all duration-300 ease-out;
    @apply bg-white text-gray-700 border-2 border-gray-200 shadow-sm;
    @apply hover:bg-primary hover:text-white hover:border-primary hover:shadow-lg;
    @apply focus:outline-none focus:ring-2 focus:ring-primary focus:ring-opacity-50;
    @apply transform hover:scale-105 active:scale-95;
}

.filter-btn:hover {
    @apply shadow-xl;
}

.filter-btn.active {
    @apply bg-primary text-white border-primary shadow-lg;
    @apply transform scale-105;
}

.filter-btn.active::after {
    content: '';
    @apply absolute -bottom-1 left-1/2 transform -translate-x-1/2;
    @apply w-2 h-2 bg-primary rounded-full;
}

/* Enhanced hover effects for filter buttons */
.filter-btn .bg-white {
    @apply transition-opacity duration-200;
}

/* Responsive filter buttons */
@media (max-width: 640px) {
    .filter-btn {
        @apply px-4 py-2 text-sm;
    }

    .filter-btn span:not(.bg-white) {
        @apply text-xs;
    }
}

/* Animation for filter count badges */
@keyframes pulse-count {
    0%, 100% { transform: scale(1); }
    50% { transform: scale(1.1); }
}

.filter-btn:hover .bg-white {
    animation: pulse-count 0.6s ease-in-out;
}

/* Enhanced active filter info styling */
#active-filter-text {
    @apply transition-colors duration-300;
}

.filter-btn.active ~ #active-filter-text {
    @apply text-primary;
}
</style>

<?php include 'includes/footer.php'; ?>