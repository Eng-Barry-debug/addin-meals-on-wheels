<?php
// Ensure no output is sent before headers
error_reporting(E_ALL);
ini_set('display_errors', 0); // Don't show errors to users

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'includes/config.php';
require_once 'includes/Cart.php'; // Make sure Cart class is included

// Fetch active menu items from database
$menu_items = [];
$featured_items = [];

try {
    // Get all active menu items
    $stmt = $pdo->query("SELECT mi.*, c.name as category_name 
                        FROM menu_items mi 
                        LEFT JOIN categories c ON mi.category_id = c.id 
                        WHERE mi.status = 'active' 
                        ORDER BY mi.name");
    $menu_items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get featured menu items
    $featured_stmt = $pdo->query("SELECT mi.*, c.name as category_name 
                                FROM menu_items mi 
                                LEFT JOIN categories c ON mi.category_id = c.id 
                                WHERE mi.status = 'active' AND mi.is_featured = 1 
                                ORDER BY RAND() LIMIT 3");
    $featured_items = $featured_stmt->fetchAll(PDO::FETCH_ASSOC);
    
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
}

// Handle add to cart action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_to_cart'])) {
    $response = ['success' => false, 'message' => ''];
    $product_id = (int)($_POST['product_id'] ?? 0);
    $quantity = isset($_POST['quantity']) ? (int)$_POST['quantity'] : 1;
    
    // Validate quantity
    if ($quantity < 1) {
        $response['message'] = 'Invalid quantity';
        sendJsonResponse($response);
    }
    
    // Find the product in the database
    $product = null;
    try {
        $stmt = $pdo->prepare("SELECT * FROM menu_items WHERE id = ? AND status = 'active'");
        $stmt->execute([$product_id]);
        $product = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$product) {
            throw new Exception('Product not found or not available');
        }
        
        // Initialize cart
        $cart = new Cart($pdo);
        
        // Add item to cart using Cart class
        $cart->addItem($product_id, $product['price'], $quantity);
        
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
        $response['message'] = 'Error adding item to cart. Please try again.';
    }
    
    sendJsonResponse($response);
}

// Helper function to send JSON response
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
        <div class="absolute inset-0 bg-gradient-to-br from-black/90 via-black/80 to-black/85"></div>
        <div class="absolute inset-0 bg-gradient-to-t from-black/70 via-black/30 to-black/60"></div>
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
<section class="py-8 bg-light">
    <div class="container mx-auto px-4">
        <div class="flex flex-wrap justify-center gap-4">
            <button class="filter-btn active" data-category="all">All Items</button>
            <button class="filter-btn" data-category="main">Main Courses</button>
            <button class="filter-btn" data-category="appetizer">Appetizers</button>
            <button class="filter-btn" data-category="dessert">Desserts</button>
            <button class="filter-btn" data-category="beverage">Beverages</button>
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
                            <button class="add-to-cart-btn w-full bg-primary hover:bg-secondary text-white px-4 py-2 rounded-md font-medium transition-colors duration-200 flex items-center justify-center" 
                                    data-id="<?php echo $item['id']; ?>"
                                    data-name="<?php echo htmlspecialchars($item['name']); ?>"
                                    data-price="<?php echo $item['price']; ?>">
                                <i class="fas fa-plus mr-2"></i> Add to Cart
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
                            <div class="absolute inset-0 bg-gradient-to-t from-black/70 to-transparent"></div>
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
                                <button class="add-to-cart-btn bg-primary hover:bg-secondary text-white px-4 py-2 rounded-md text-sm font-medium transition-colors duration-200 flex items-center" 
                                        data-id="<?= $item['id'] ?>"
                                        data-name="<?= htmlspecialchars($item['name']) ?>"
                                        data-price="<?= $item['price'] ?>">
                                    <i class="fas fa-plus mr-2"></i> Add to Cart
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
<div id="cartModal" class="fixed inset-0 bg-black/50 hidden items-center justify-center z-50">
    <div class="bg-white rounded-lg p-6 max-w-md w-full mx-4">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-xl font-bold">Add to Cart</h3>
            <button id="closeModal" class="text-gray-500 hover:text-dark">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div id="modalContent">
            <!-- Dynamic content will be inserted here -->
        </div>
    </div>
</div>

<script>
// Filter menu items
const filterBtns = document.querySelectorAll('.filter-btn');
const menuItems = document.querySelectorAll('.menu-item');

filterBtns.forEach(btn => {
    btn.addEventListener('click', () => {
        // Update active button
        filterBtns.forEach(b => b.classList.remove('active'));
        btn.classList.add('active');
        
        const category = btn.dataset.category;
        
        // Filter items
        menuItems.forEach(item => {
            if (category === 'all' || item.dataset.category === category) {
                item.style.display = 'block';
            } else {
                item.style.display = 'none';
            }
        });
    });
});

// Add to cart functionality
document.querySelectorAll('.add-to-cart-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        const id = this.dataset.id;
        const name = this.dataset.name;
        const price = this.dataset.price;
        
        // Update modal content
        document.getElementById('modalContent').innerHTML = `
            <p class="mb-4">Add <span class="font-semibold">${name}</span> to your cart?</p>
            <div class="flex items-center mb-4">
                <label class="mr-4">Quantity:</label>
                <div class="flex items-center border rounded">
                    <button type="button" class="px-3 py-1 border-r" onclick="updateQuantity(-1)">-</button>
                    <input type="number" id="quantity" value="1" min="1" class="w-12 text-center border-0 focus:ring-0">
                    <button type="button" class="px-3 py-1 border-l" onclick="updateQuantity(1)">+</button>
                </div>
            </div>
            <div class="flex justify-between items-center">
                <span>Total: <span id="itemTotal" class="font-bold">KES ${price}</span></span>
                <button type="button" onclick="addToCart(${id}, '${name.replace(/'/g, "\\'")}', ${price})" class="px-4 py-2 bg-primary text-white rounded hover:bg-secondary transition-colors">
                    Add to Cart
                </button>
            </div>
        `;
        document.getElementById('cartModal').classList.remove('hidden');
        document.getElementById('cartModal').classList.add('flex');
    });
});

// Close modal
document.getElementById('closeModal').addEventListener('click', () => {
    document.getElementById('cartModal').classList.add('hidden');
    document.getElementById('cartModal').classList.remove('flex');
});

// Close modal when clicking outside
window.addEventListener('click', (e) => {
    const modal = document.getElementById('cartModal');
    if (e.target === modal) {
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    }
});

// Update quantity
function updateQuantity(change) {
    const input = document.getElementById('quantity');
    let value = parseInt(input.value) + change;
    if (value < 1) value = 1;
    input.value = value;
    
    // Update total
    const price = parseFloat(document.querySelector('#modalContent button').dataset.price);
    document.getElementById('itemTotal').textContent = 'KES ' + (price * value).toFixed(2);
}

// Add to cart
function addToCart(id, name, price) {
    const quantity = parseInt(document.getElementById('quantity').value);
    const addToCartBtn = document.querySelector('#modalContent button');
    
    // Disable button to prevent multiple clicks
    addToCartBtn.disabled = true;
    addToCartBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> Adding...';
    
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
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Update cart count in the header
            const cartCount = document.querySelectorAll('.cart-count, .fa-shopping-cart + span');
            if (cartCount.length > 0) {
                cartCount.forEach(el => {
                    el.textContent = data.cartCount || 0;
                });
            }
            
            // Show success message
            showNotification('success', data.message);
            
            // Close modal
            document.getElementById('cartModal').classList.add('hidden');
            document.getElementById('cartModal').classList.remove('flex');
            
            // Reload the page to update the cart
            setTimeout(() => {
                window.location.href = 'cart.php';
            }, 1000);
        } else {
            showNotification('error', data.message || 'Failed to add item to cart');
            addToCartBtn.disabled = false;
            addToCartBtn.innerHTML = 'Add to Cart';
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('error', 'An error occurred. Please try again.');
        addToCartBtn.disabled = false;
        addToCartBtn.innerHTML = 'Add to Cart';
    });
}

// Show notification function
function showNotification(type, message) {
    // Remove any existing notifications
    const existingAlert = document.querySelector('.alert-notification');
    if (existingAlert) {
        existingAlert.remove();
    }
    
    // Create notification element
    const alert = document.createElement('div');
    alert.className = `alert-notification fixed top-4 right-4 px-6 py-3 rounded-lg shadow-lg text-white font-medium z-50 ${type === 'success' ? 'bg-green-500' : 'bg-red-500'}`;
    alert.innerHTML = `
        <div class="flex items-center">
            <i class="fas ${type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'} mr-2"></i>
            <span>${message}</span>
        </div>
    `;
    
    // Add to body and auto-remove after 3 seconds
    document.body.appendChild(alert);
    setTimeout(() => {
        alert.remove();
    }, 3000);
}
</script>

<style>
.filter-btn {
    @apply px-4 py-2 rounded-full bg-white text-dark font-medium transition-colors duration-200;
}

.filter-btn:hover, .filter-btn.active {
    @apply bg-primary text-white;
}

.menu-item {
    transition: all 0.3s ease;
}

.menu-item:hover {
    transform: translateY(-5px);
}

#quantity {
    -moz-appearance: textfield;
}

#quantity::-webkit-outer-spin-button,
#quantity::-webkit-inner-spin-button {
    -webkit-appearance: none;
    margin: 0;
}
</style>

<?php include 'includes/footer.php'; ?>