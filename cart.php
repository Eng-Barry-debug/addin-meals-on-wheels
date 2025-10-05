<?php
session_start();
require_once 'includes/config.php';
require_once 'includes/Cart.php';

// Initialize cart
$cart = new Cart($pdo);

// Handle remove item action
if (isset($_GET['remove'])) {
    $product_id = (int)$_GET['remove'];
    $cart->removeItem($product_id);
    header('Location: cart.php');
    exit;
}

// Handle AJAX update quantity action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['product_id']) && isset($_POST['quantity'])) {
    // Set JSON header first
    if (!headers_sent()) {
        header('Content-Type: application/json');
    }

    // Initialize response array
    $response = [
        'success' => false,
        'message' => 'An error occurred',
        'item_count' => 0,
        'subtotal' => 0
    ];

    try {
        // Check if it's an AJAX request
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
            throw new Exception('Invalid request');
        }

        $product_id = (int)$_POST['product_id'];
        $quantity = (int)$_POST['quantity'];

        if ($quantity > 0) {
            // Get product price first
            $stmt = $pdo->prepare("SELECT price, name FROM menu_items WHERE id = ?");
            if (!$stmt->execute([$product_id])) {
                throw new Exception('Failed to fetch product details');
            }

            $product = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($product) {
                $cart->addItem($product_id, $quantity, $product['price']);
                $response = [
                    'success' => true,
                    'item_count' => $cart->getTotalItems(),
                    'subtotal' => $cart->getSubtotal(),
                    'message' => $product['name'] . ' added to cart!'
                ];
            } else {
                throw new Exception('Product not found');
            }
        } else {
            $cart->removeItem($product_id);
            $response = [
                'success' => true,
                'item_count' => $cart->getTotalItems(),
                'subtotal' => $cart->getSubtotal(),
                'message' => 'Item removed from cart'
            ];
        }
    } catch (Exception $e) {
        http_response_code(400);
        $response = [
            'success' => false,
            'message' => $e->getMessage(),
            'item_count' => $cart->getTotalItems(),
            'subtotal' => $cart->getSubtotal()
        ];
    }

    // Ensure no output before this
    if (ob_get_level()) {
        ob_clean();
    }

    echo json_encode($response);
    exit;
}

// Handle regular form submission for non-JS users
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_cart'])) {
    foreach ($_POST['quantities'] as $product_id => $quantity) {
        $product_id = (int)$product_id;
        $quantity = (int)$quantity;

        if ($quantity > 0) {
            $cart->updateQuantity($product_id, $quantity);
        } else {
            $cart->removeItem($product_id);
        }
    }
    header('Location: cart.php');
    exit;
}

// Get cart items
$cartItems = $cart->getItems();
$subtotal = 0;

// Calculate subtotal and get product details
$cartDetails = [];
foreach ($cartItems as $product_id => $item) {
    $stmt = $pdo->prepare("SELECT name, price, image FROM menu_items WHERE id = ?");
    $stmt->execute([$product_id]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($product) {
        $itemTotal = $product['price'] * $item['quantity'];
        $subtotal += $itemTotal;

        $cartDetails[] = [
            'id' => $product_id,
            'name' => $product['name'],
            'price' => $product['price'],
            'image' => !empty($product['image']) ? 'uploads/menu/' . $product['image'] : 'assets/img/placeholder-food.jpg',
            'quantity' => $item['quantity'],
            'total' => $itemTotal
        ];
    }
}

// Calculate totals
$delivery_fee = $subtotal > 0 ? 200 : 0; // Example delivery fee
$total = $subtotal + $delivery_fee;

$page_title = "Your Cart - Addins Meals on Wheels";
include 'includes/header.php';
?>

<!-- Cart Section -->
<section class="py-8 md:py-12 bg-gradient-to-b from-light via-white to-light">
    <div class="container mx-auto px-4 max-w-7xl">
        <!-- Breadcrumb -->
        <nav class="mb-6">
            <ol class="flex items-center space-x-2 text-sm text-gray-500">
                <li><a href="index.php" class="hover:text-primary transition-colors">Home</a></li>
                <li class="text-gray-300">/</li>
                <li class="text-primary font-medium">Your Cart</li>
            </ol>
        </nav>

        <div class="flex flex-col md:flex-row md:items-center md:justify-between mb-8">
            <div class="mb-4 md:mb-0">
                <h1 class="text-3xl md:text-4xl font-bold text-dark">Your Cart</h1>
                <p class="text-gray-600 mt-1">Review and manage your delicious selections</p>
            </div>
            <a href="menu.php" class="inline-flex items-center px-5 py-2.5 bg-white border border-gray-200 rounded-lg shadow-sm hover:shadow-md transition-all duration-300 hover:-translate-y-0.5 text-gray-700 hover:bg-gray-50">
                <i class="fas fa-arrow-left mr-2 text-primary"></i> Continue Shopping
            </a>
        </div>

        <?php if (empty($cartDetails)): ?>
            <div class="bg-white rounded-2xl shadow-sm p-8 md:p-12 text-center border border-gray-100">
                <div class="w-28 h-28 bg-gradient-to-br from-primary-50 to-primary-100 rounded-full flex items-center justify-center mx-auto mb-8">
                    <i class="fas fa-shopping-cart text-5xl text-primary"></i>
                </div>
                <h3 class="text-2xl md:text-3xl font-bold text-gray-800 mb-3">Your cart is empty</h3>
                <p class="text-gray-500 mb-8 max-w-md mx-auto text-lg">Looks like you haven't added anything to your cart yet. Start exploring our delicious menu!</p>
                <div class="flex flex-col sm:flex-row justify-center gap-4">
                    <a href="menu.php" class="inline-flex items-center justify-center px-8 py-4 bg-gradient-to-r from-primary to-primary-dark text-white font-medium rounded-xl hover:opacity-90 transition-all transform hover:-translate-y-0.5 shadow-lg hover:shadow-xl">
                        <i class="fas fa-utensils mr-3"></i> Browse Our Menu
                    </a>
                    <a href="#specials" class="inline-flex items-center justify-center px-8 py-4 bg-white border-2 border-gray-200 text-gray-700 font-medium rounded-xl hover:border-primary hover:text-primary transition-all">
                        <i class="fas fa-star mr-3 text-yellow-400"></i> Today's Specials
                    </a>
                </div>
            </div>
        <?php else: ?>
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
                <!-- Cart Items -->
                <div class="lg:col-span-8 space-y-6">
                    <!-- Cart Items Section -->
                    <div class="bg-white rounded-2xl shadow-sm overflow-hidden border border-gray-100">
                        <!-- Desktop Table Header -->
                        <div class="hidden md:grid grid-cols-12 gap-4 p-6 bg-gradient-to-r from-gray-50 to-gray-50 border-b font-medium text-gray-600 text-sm uppercase tracking-wider">
                            <div class="col-span-5">Product</div>
                            <div class="col-span-2 text-center">Price</div>
                            <div class="col-span-3 text-center">Quantity</div>
                            <div class="col-span-2 text-right">Total</div>
                        </div>

                        <form method="POST" action="cart.php" id="cart-form" class="divide-y divide-gray-100">
                            <?php foreach ($cartDetails as $index => $item):
                                $animationDelay = $index * 0.05;
                            ?>
                                <div class="p-5 md:p-6 hover:bg-gray-50/50 transition-colors duration-300 cart-item"
                                     style="opacity: 0; transform: translateY(10px);"
                                     data-aos="fade-up" data-aos-delay="<?php echo $index * 50; ?>"
                                     data-aos-duration="400">
                                    <div class="flex flex-col md:flex-row md:items-center gap-4">
                                        <div class="flex items-center space-x-4 md:col-span-5">
                                            <a href="menu-single.php?id=<?php echo $item['id']; ?>"
                                               class="w-24 h-24 flex-shrink-0 rounded-xl overflow-hidden border-2 border-white shadow-md hover:shadow-lg transition-all duration-300 group">
                                                <div class="relative w-full h-full">
                                                    <img src="<?php echo htmlspecialchars($item['image']); ?>"
                                                         alt="<?php echo htmlspecialchars($item['name']); ?>"
                                                         class="w-full h-full object-cover transform group-hover:scale-110 transition-transform duration-500">
                                                    <div class="absolute inset-0 bg-black/10 group-hover:bg-transparent transition-colors duration-300"></div>
                                                </div>
                                            </a>
                                            <div class="flex-1 min-w-0">
                                                <h3 class="font-semibold text-gray-800 hover:text-primary transition-colors">
                                                    <a href="menu-single.php?id=<?php echo $item['id']; ?>">
                                                        <?php echo htmlspecialchars($item['name']); ?>
                                                    </a>
                                                </h3>
                                                <div class="flex items-center mt-1 space-x-3">
                                                    <a href="cart.php?remove=<?php echo $item['id']; ?>"
                                                       class="inline-flex items-center text-sm text-red-500 hover:text-red-700 transition-colors"
                                                       onclick="return confirm('Remove this item from your cart?')">
                                                        <i class="fas fa-trash-alt mr-1.5"></i> Remove
                                                    </a>
                                                    <span class="text-gray-300">•</span>
                                                    <button type="button" class="text-sm text-gray-500 hover:text-primary transition-colors">
                                                        <i class="far fa-heart mr-1.5"></i> Save for later
                                                    </button>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="md:text-center md:col-span-2">
                                            <span class="md:hidden text-sm font-medium text-gray-500">Price: </span>
                                            <div class="flex flex-col">
                                                <span class="font-bold text-gray-800">KES <?php echo number_format($item['price'], 2); ?></span>
                                                <?php if ($item['price'] > 1000): ?>
                                                    <span class="text-xs text-green-600 font-medium">You save 10%</span>
                                                <?php endif; ?>
                                            </div>
                                        </div>

                                        <div class="flex items-center justify-between md:justify-center md:col-span-3">
                                            <span class="md:hidden text-sm font-medium text-gray-500">Qty: </span>
                                            <div class="flex items-center border border-gray-200 rounded-xl overflow-hidden bg-white">
                                                <button type="button"
                                                        class="decrease-qty w-10 h-10 flex items-center justify-center bg-gray-50 hover:bg-gray-100 text-gray-600 hover:text-primary transition-colors"
                                                        data-id="<?php echo $item['id']; ?>">
                                                    <i class="fas fa-minus text-xs"></i>
                                                </button>
                                                <input type="number"
                                                       name="quantities[<?php echo $item['id']; ?>]"
                                                       value="<?php echo $item['quantity']; ?>"
                                                       min="1"
                                                       class="w-12 h-10 text-center border-x-0 border-gray-200 focus:ring-1 focus:ring-primary focus:border-transparent">
                                                <button type="button"
                                                        class="increase-qty w-10 h-10 flex items-center justify-center bg-gray-50 hover:bg-gray-100 text-gray-600 hover:text-primary transition-colors"
                                                        data-id="<?php echo $item['id']; ?>">
                                                    <i class="fas fa-plus text-xs"></i>
                                                </button>
                                            </div>
                                        </div>

                                        <div class="md:text-right md:col-span-2">
                                            <span class="md:hidden text-sm font-medium text-gray-500">Total: </span>
                                            <div class="flex flex-col items-end">
                                                <span class="font-bold text-lg text-gray-900">KES <?php echo number_format($item['total'], 2); ?></span>
                                                <?php if ($item['quantity'] > 1): ?>
                                                    <span class="text-xs text-gray-500"><?php echo $item['quantity']; ?> × KES <?php echo number_format($item['price'], 2); ?></span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>

                            <div class="p-5 bg-gray-50/80 flex flex-col sm:flex-row justify-between items-center gap-4 border-t border-gray-100">
                                <div class="w-full sm:w-auto">
                                    <div class="flex items-center">
                                        <i class="fas fa-tag text-primary mr-3 text-xl"></i>
                                        <input type="text"
                                               placeholder="Enter promo code"
                                               class="px-4 py-2.5 border border-gray-200 rounded-l-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent w-full sm:w-64">
                                        <button type="button"
                                                class="px-6 py-2.5 bg-primary text-white font-medium rounded-r-lg hover:bg-primary-dark transition-colors whitespace-nowrap">
                                            Apply
                                        </button>
                                    </div>
                                </div>
                                <div class="flex flex-col sm:flex-row items-center gap-3 w-full sm:w-auto">
                                    <a href="menu.php" class="w-full sm:w-auto text-center px-6 py-2.5 border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors text-gray-700 hover:text-primary">
                                        <i class="fas fa-arrow-left mr-2"></i> Continue Shopping
                                    </a>
                                    <button type="submit"
                                            name="update_cart"
                                            class="w-full sm:w-auto px-6 py-2.5 bg-gradient-to-r from-primary to-primary-dark text-white font-medium rounded-lg hover:opacity-90 transition-all transform hover:-translate-y-0.5 shadow-md">
                                        <i class="fas fa-sync-alt mr-2"></i> Update Cart
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Order Summary -->
                <div class="lg:col-span-4 space-y-6">
                    <div class="bg-white rounded-2xl shadow-sm overflow-hidden border border-gray-100 sticky top-6 z-10">
                        <div class="bg-gradient-to-r from-primary to-primary-dark p-6 text-white">
                            <h2 class="text-2xl font-bold flex items-center">
                                <i class="fas fa-receipt mr-3"></i> Order Summary
                            </h2>
                            <p class="text-primary-100 text-sm mt-1">Review your order details</p>
                        </div>

                        <div class="p-6 space-y-5">
                            <div id="cart-summary">
                                <div class="flex justify-between items-center">
                                    <span class="text-gray-600">Subtotal (<span id="item-count"><?php echo array_sum(array_column($cartDetails, 'quantity')); ?></span> items)</span>
                                    <span class="font-medium text-gray-800">KES <span id="subtotal"><?php echo number_format($subtotal, 2); ?></span></span>
                                </div>

                                <div class="flex justify-between items-center py-3 border-t border-b border-gray-100">
                                    <div>
                                        <span class="text-gray-600">Delivery Fee</span>
                                        <div class="text-xs text-green-600 font-medium mt-1">
                                            <i class="fas fa-info-circle mr-1"></i> Free delivery on orders over KES 1,500
                                        </div>
                                    </div>
                                    <span class="font-medium text-gray-800">
                                        <span id="delivery-fee" class="<?php echo $subtotal >= 1500 ? 'text-green-600' : ''; ?>">
                                            <?php echo $subtotal >= 1500 ? 'Free' : 'KES ' . number_format($delivery_fee, 2); ?>
                                        </span>
                                        <span id="delivery-fee-amount" class="hidden"><?php echo $delivery_fee; ?></span>
                                    </span>
                                </div>

                            <div class="pt-2">
                                <div class="flex items-center justify-between mb-2">
                                    <span class="text-gray-600">Promo Code</span>
                                    <span class="text-primary font-medium">-KES 0.00</span>
                                </div>
                                <div class="flex">
                                    <input type="text"
                                           placeholder="Enter code"
                                           class="flex-1 px-4 py-2 border border-gray-200 rounded-l-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent text-sm">
                                    <button type="button"
                                            class="px-4 bg-gray-100 text-gray-600 hover:bg-gray-200 transition-colors rounded-r-lg text-sm font-medium">
                                        Apply
                                    </button>
                                </div>
                            </div>

                            <div class="bg-gray-50 p-4 rounded-lg border border-gray-100 relative z-20">
                                <div class="flex justify-between items-center mb-2">
                                    <span class="font-semibold text-gray-700">Total Amount</span>
                                    <span class="text-2xl font-bold text-gray-900">KES <span id="total-amount"><?php echo number_format($total, 2); ?></span></span>
                                </div>
                                <div class="text-xs text-gray-500">
                                    <i class="fas fa-info-circle mr-1"></i> Includes all taxes and fees
                                </div>
                            </div>

                            <div class="relative z-30 bg-white p-2">
                                <?php if (isset($_SESSION['user_id'])): ?>
                                    <a href="checkout.php"
                                       class="block w-full px-6 py-4 bg-gradient-to-r from-primary to-primary-dark text-white font-bold text-center rounded-xl hover:opacity-90 transition-all transform hover:-translate-y-0.5 shadow-lg hover:shadow-xl"
                                       id="checkout-button">
                                        <i class="fas fa-lock mr-2"></i> Proceed to Checkout
                                    </a>
                                <?php else: ?>
                                    <a href="auth/login.php?redirect=<?php echo urlencode('checkout.php'); ?>"
                                       class="block w-full px-6 py-4 bg-gradient-to-r from-primary to-primary-dark text-white font-bold text-center rounded-xl hover:opacity-90 transition-all transform hover:-translate-y-0.5 shadow-lg hover:shadow-xl"
                                       id="login-button">
                                        <i class="fas fa-sign-in-alt mr-2"></i> Login to Checkout
                                    </a>
                                    <p class="text-sm text-center text-gray-500 mt-2">
                                        Don't have an account?
                                        <a href="auth/register.php?redirect=<?php echo urlencode('checkout.php'); ?>" class="text-primary hover:underline">
                                            Register here
                                        </a>
                                    </p>
                                <?php endif; ?>
                            </div>

                            <div class="flex items-center justify-center space-x-2 pt-2">
                                <span class="text-sm text-gray-500">or</span>
                                <a href="menu.php" class="text-sm font-medium text-primary hover:underline">
                                    Continue Shopping
                                </a>
                            </div>
                        </div>

                        <!-- Payment Methods -->
                        <div class="border-t border-gray-100 p-6">
                            <h4 class="text-sm font-semibold text-gray-500 uppercase tracking-wider mb-4">We Accept</h4>
                            <div class="grid grid-cols-4 gap-3">
                                <div class="h-12 flex items-center justify-center border border-gray-200 rounded-lg p-2">
                                    <i class="fab fa-cc-visa text-3xl text-blue-600"></i>
                                </div>
                                <div class="h-12 flex items-center justify-center border border-gray-200 rounded-lg p-2">
                                    <i class="fab fa-cc-mastercard text-3xl text-red-500"></i>
                                </div>
                                <div class="h-12 flex items-center justify-center border border-gray-200 rounded-lg p-2">
                                    <i class="fab fa-cc-paypal text-3xl text-blue-400"></i>
                                </div>
                                <div class="h-12 flex items-center justify-center border border-gray-200 rounded-lg p-2">
                                    <i class="fas fa-mobile-alt text-2xl text-green-600"></i>
                                </div>
                            </div>
                        </div>

                        <!-- Secure Checkout -->
                        <div class="bg-gray-50 p-6 border-t border-gray-100">
                            <div class="flex items-center justify-center space-x-2 text-sm text-gray-500">
                                <i class="fas fa-lock text-green-500"></i>
                                <span>Secure SSL Checkout</span>
                            </div>
                        </div>
                    </div>

                    <!-- Delivery Info -->
                    <div class="bg-white rounded-2xl shadow-sm overflow-hidden border border-gray-100">
                        <div class="p-6">
                            <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                                <i class="fas fa-truck text-primary mr-3"></i> Delivery Information
                            </h3>
                            <div class="space-y-3">
                                <div class="flex items-start">
                                    <div class="flex-shrink-0 mt-1">
                                        <div class="w-8 h-8 rounded-full bg-green-50 flex items-center justify-center text-green-500">
                                            <i class="fas fa-check text-xs"></i>
                                        </div>
                                    </div>
                                    <div class="ml-3">
                                        <p class="text-sm font-medium text-gray-900">Free Delivery</p>
                                        <p class="text-sm text-gray-500">On orders over KES 1,500</p>
                                    </div>
                                </div>
                                <div class="flex items-start">
                                    <div class="flex-shrink-0 mt-1">
                                        <div class="w-8 h-8 rounded-full bg-blue-50 flex items-center justify-center text-blue-500">
                                            <i class="fas fa-clock text-xs"></i>
                                        </div>
                                    </div>
                                    <div class="ml-3">
                                        <p class="text-sm font-medium text-gray-900">Fast Delivery</p>
                                        <p class="text-sm text-gray-500">Within 45-60 minutes</p>
                                    </div>
                                </div>
                                <div class="flex items-start">
                                    <div class="flex-shrink-0 mt-1">
                                        <div class="w-8 h-8 rounded-full bg-purple-50 flex items-center justify-center text-purple-500">
                                            <i class="fas fa-headset text-xs"></i>
                                        </div>
                                    </div>
                                    <div class="ml-3">
                                        <p class="text-sm font-medium text-gray-900">24/7 Support</p>
                                        <p class="text-sm text-gray-500">Dedicated support</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</section>

<script>
// Quantity controls and AJAX functionality
document.addEventListener('DOMContentLoaded', function() {
    // Quantity controls
    document.addEventListener('click', function(e) {
        // Increase quantity
        if (e.target.classList.contains('increase-qty') || e.target.closest('.increase-qty')) {
            const button = e.target.classList.contains('increase-qty') ? e.target : e.target.closest('.increase-qty');
            const input = button.previousElementSibling;
            input.value = parseInt(input.value) + 1;
            updateCartItem(button.dataset.id, input.value);
            animateButton(button);
        }

        // Decrease quantity
        if (e.target.classList.contains('decrease-qty') || e.target.closest('.decrease-qty')) {
            const button = e.target.classList.contains('decrease-qty') ? e.target : e.target.closest('.decrease-qty');
            const input = button.nextElementSibling;
            if (parseInt(input.value) > 1) {
                input.value = parseInt(input.value) - 1;
                updateCartItem(button.dataset.id, input.value);
                animateButton(button);
            }
        }
    });

    // Input validation for quantity
    document.addEventListener('change', function(e) {
        if (e.target.getAttribute('name') && e.target.getAttribute('name').startsWith('quantities')) {
            const value = parseInt(e.target.value);
            if (isNaN(value) || value < 1) {
                e.target.value = 1;
            }
            updateCartItem(e.target.name.match(/\[(\d+)\]/)[1], e.target.value);
        }
    });

    // Button animation
    function animateButton(button) {
        button.classList.add('animate-pulse');
        setTimeout(() => {
            button.classList.remove('animate-pulse');
        }, 300);
    }

    // Function to update cart item quantity via AJAX
    async function updateCartItem(productId, quantity) {
        const formData = new FormData();
        formData.append('product_id', productId);
        formData.append('quantity', quantity);

        // Disable buttons during request
        const buttons = document.querySelectorAll('.decrease-qty, .increase-qty');
        buttons.forEach(btn => {
            btn.disabled = true;
        });

        try {
            const response = await fetch('cart.php', {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                },
                credentials: 'same-origin'
            });

            const data = await response.json();

            if (data.success) {
                // Update the cart summary
                updateCartSummary(data.subtotal, data.item_count);
                showNotification('success', data.message || 'Cart updated successfully');
            } else {
                throw new Error(data.message || 'Failed to update cart');
            }
        } catch (error) {
            console.error('Error updating cart:', error);
            showNotification('error', error.message || 'An error occurred while updating the cart');
        } finally {
            // Re-enable buttons
            buttons.forEach(btn => {
                btn.disabled = false;
            });
        }
    }

    // Function to update cart summary
    function updateCartSummary(subtotal, itemCount) {
        // Update item count
        const itemCountElement = document.getElementById('item-count');
        if (itemCountElement) itemCountElement.textContent = itemCount;

        // Update subtotal
        const subtotalElement = document.getElementById('subtotal');
        if (subtotalElement) subtotalElement.textContent = subtotal.toFixed(2);

        // Update delivery fee
        const deliveryFeeElement = document.getElementById('delivery-fee');
        const deliveryFeeAmount = subtotal >= 1500 ? 0 : 200;

        if (deliveryFeeElement) {
            if (subtotal >= 1500) {
                deliveryFeeElement.textContent = 'Free';
                deliveryFeeElement.classList.add('text-green-600');
            } else {
                deliveryFeeElement.textContent = 'KES ' + deliveryFeeAmount.toFixed(2);
                deliveryFeeElement.classList.remove('text-green-600');
            }
        }

        // Update total
        const totalElement = document.getElementById('total-amount');
        if (totalElement) {
            const total = parseFloat(subtotal) + parseFloat(deliveryFeeAmount);
            totalElement.textContent = total.toFixed(2);
        }
    }

    // Show notification
    function showNotification(type, message) {
        const notification = document.createElement('div');
        notification.className = `fixed top-4 right-4 z-50 px-6 py-3 rounded-lg shadow-lg text-white font-medium ${
            type === 'success' ? 'bg-green-500' : 'bg-red-500'
        }`;
        notification.innerHTML = `
            <div class="flex items-center">
                <i class="fas ${type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'} mr-2"></i>
                <span>${message}</span>
            </div>
        `;

        document.body.appendChild(notification);

        // Remove after delay
        setTimeout(() => {
            notification.remove();
        }, 3000);
    }
});

// Add smooth scrolling for anchor links
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
</script>

<?php include 'includes/footer.php'; ?>