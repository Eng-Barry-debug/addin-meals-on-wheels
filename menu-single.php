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

// Include header after all redirects are handled
require_once 'includes/header.php';

// Get related items from the same category
$related_stmt = $pdo->prepare("
    SELECT m.id, m.name, m.price, m.image
    FROM menu_items m
    WHERE m.category_id = ? AND m.id != ? AND m.status = 'active'
    LIMIT 5
");
$related_stmt->execute([$menu_item['category_id'], $menu_item_id]);
$related_items = $related_stmt->fetchAll(PDO::FETCH_ASSOC);
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
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                    <i class="fas fa-star"></i>
                                <?php endfor; ?>
                            </div>
                            <span class="text-sm text-gray-500 ml-1">(0)</span>
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
                    <button class="px-6 py-4 font-medium text-sm border-b-2 border-primary text-primary">
                        Description
                    </button>
                    <button class="px-6 py-4 font-medium text-sm text-gray-500 hover:text-gray-700">
                        Ingredients
                    </button>
                    <button class="px-6 py-4 font-medium text-sm text-gray-500 hover:text-gray-700">
                        Nutrition
                    </button>
                    <button class="px-6 py-4 font-medium text-sm text-gray-500 hover:text-gray-700">
                        Reviews (0)
                    </button>
                </nav>
            </div>
            <div class="p-6">
                <?php if (!empty($menu_item['description'])): ?>
                    <div class="prose max-w-none">
                        <h3 class="text-lg font-medium text-gray-900 mb-3">Product Description</h3>
                        <p class="text-gray-600"><?= nl2br(htmlspecialchars($menu_item['description'])) ?></p>
                    </div>
                <?php endif; ?>

                <?php if (!empty($menu_item['ingredients'])): ?>
                    <div class="mt-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-3">Ingredients</h3>
                        <p class="text-gray-600"><?= nl2br(htmlspecialchars($menu_item['ingredients'])) ?></p>
                    </div>
                <?php endif; ?>

                <?php if (!empty($menu_item['allergens'])): ?>
                    <div class="mt-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-3">Allergens</h3>
                        <p class="text-gray-600"><?= htmlspecialchars($menu_item['allergens']) ?></p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Related Products -->
        <?php if (!empty($related_items)): ?>
            <div class="mt-10">
                <h2 class="text-xl font-bold text-gray-900 mb-6">You may also like</h2>
                <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-4">
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
                                            $<?= number_format($item['price'], 2) ?>
                                        </span>
                                    </div>
                                </div>
                            </a>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
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

// Initialize tooltips
document.addEventListener('DOMContentLoaded', function() {
    const tooltipTriggers = document.querySelectorAll('[data-tooltip]');
    tooltipTriggers.forEach(trigger => {
        new bootstrap.Tooltip(trigger);
    });
});
</script>

<?php include 'includes/footer.php'; ?>