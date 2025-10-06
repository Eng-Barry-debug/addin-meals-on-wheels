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
    // Ensure we have the correct quantity field
    $quantity = isset($item['quantity']) ? $item['quantity'] : 1;

    $stmt = $pdo->prepare("SELECT name, price, image FROM menu_items WHERE id = ?");
    $stmt->execute([$product_id]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($product) {
        $itemTotal = $product['price'] * $quantity;
        $subtotal += $itemTotal;

        $cartDetails[] = [
            'id' => $product_id,
            'name' => $product['name'],
            'price' => $product['price'],
            'image' => !empty($product['image']) ? 'uploads/menu/' . $product['image'] : 'assets/img/placeholder-food.jpg',
            'quantity' => $quantity,
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
            <div class="bg-white rounded-2xl shadow-sm p-8 md:p-12 text-center border border-gray-100 relative overflow-hidden">
                <!-- Background decoration -->
                <div class="absolute inset-0 bg-gradient-to-br from-primary/5 via-transparent to-secondary/5"></div>
                <div class="absolute top-0 right-0 w-32 h-32 bg-gradient-to-br from-primary/10 to-transparent rounded-full transform translate-x-16 -translate-y-16"></div>
                <div class="absolute bottom-0 left-0 w-24 h-24 bg-gradient-to-tr from-secondary/10 to-transparent rounded-full transform -translate-x-12 translate-y-12"></div>

                <div class="relative z-10">
                    <div class="w-28 h-28 bg-gradient-to-br from-primary to-secondary rounded-full flex items-center justify-center mx-auto mb-8 shadow-lg animate-pulse">
                        <i class="fas fa-shopping-cart text-5xl text-white"></i>
                    </div>
                    <h3 class="text-2xl md:text-3xl font-bold text-gray-800 mb-3">Your cart is empty</h3>
                    <p class="text-gray-500 mb-8 max-w-md mx-auto text-lg leading-relaxed">Looks like you haven't added anything to your cart yet. Start exploring our delicious menu!</p>
                    <div class="flex flex-col sm:flex-row justify-center gap-4">
                        <a href="menu.php" class="group inline-flex items-center justify-center px-8 py-4 bg-gradient-to-r from-primary to-primary-dark text-white font-medium rounded-xl hover:opacity-90 transition-all transform hover:-translate-y-1 shadow-lg hover:shadow-xl">
                            <i class="fas fa-utensils mr-3 group-hover:scale-110 transition-transform"></i> Browse Our Menu
                        </a>
                        <a href="#specials" class="inline-flex items-center justify-center px-8 py-4 bg-white border-2 border-gray-200 text-gray-700 font-medium rounded-xl hover:border-primary hover:text-primary transition-all group">
                            <i class="fas fa-star mr-3 text-yellow-400 group-hover:scale-110 transition-transform"></i> Today's Specials
                        </a>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
                <!-- Cart Items -->
                <div class="lg:col-span-8 space-y-6">
                    <!-- Cart Items Section -->
                    <div class="bg-white rounded-2xl shadow-sm overflow-hidden border border-gray-100">
                        <!-- Enhanced Desktop Table Header -->
                        <div class="hidden md:grid grid-cols-12 gap-4 p-6 bg-gradient-to-r from-gray-50 via-gray-50 to-gray-100 border-b font-medium text-gray-600 text-sm uppercase tracking-wider">
                            <div class="col-span-5 flex items-center">
                                <i class="fas fa-utensils mr-2 text-primary"></i> Product
                            </div>
                            <div class="col-span-2 text-center">Price</div>
                            <div class="col-span-3 text-center flex items-center justify-center">
                                <i class="fas fa-calculator mr-2 text-primary"></i> Quantity
                            </div>
                            <div class="col-span-2 text-right flex items-center justify-end">
                                <i class="fas fa-money-bill-wave mr-2 text-primary"></i> Total
                            </div>
                        </div>

                        <form method="POST" action="cart.php" id="cart-form" class="divide-y divide-gray-100">
                            <?php foreach ($cartDetails as $index => $item):
                                $animationDelay = $index * 0.05;
                            ?>
                                <div class="p-5 md:p-6 hover:bg-gray-50/50 transition-all duration-300 cart-item group relative overflow-hidden"
                                     style="opacity: 0; transform: translateY(10px);"
                                     data-aos="fade-up" data-aos-delay="<?php echo $index * 50; ?>"
                                     data-aos-duration="400">
                                    <!-- Subtle background pattern -->
                                    <div class="absolute inset-0 bg-gradient-to-r from-transparent via-primary/1 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>

                                    <div class="relative z-10 flex flex-col md:flex-row md:items-center gap-4">
                                        <div class="flex items-center space-x-4 md:col-span-5">
                                            <div class="relative">
                                                <a href="menu-single.php?id=<?php echo $item['id']; ?>"
                                                   class="w-24 h-24 flex-shrink-0 rounded-xl overflow-hidden border-2 border-white shadow-md hover:shadow-lg transition-all duration-300 group-hover:scale-105 block">
                                                    <div class="relative w-full h-full">
                                                        <img src="<?php echo htmlspecialchars($item['image']); ?>"
                                                             alt="<?php echo htmlspecialchars($item['name']); ?>"
                                                             class="w-full h-full object-cover transform group-hover:scale-110 transition-transform duration-500">
                                                        <div class="absolute inset-0 bg-black/10 group-hover:bg-transparent transition-colors duration-300"></div>
                                                        <!-- Item counter badge -->
                                                        <div class="absolute -top-2 -right-2 w-6 h-6 bg-primary text-white text-xs font-bold rounded-full flex items-center justify-center shadow-md">
                                                            <?php echo $item['quantity']; ?>
                                                        </div>
                                                    </div>
                                                </a>
                                            </div>
                                            <div class="flex-1 min-w-0">
                                                <h3 class="font-semibold text-gray-800 hover:text-primary transition-colors group-hover:text-primary">
                                                    <a href="menu-single.php?id=<?php echo $item['id']; ?>">
                                                        <?php echo htmlspecialchars($item['name']); ?>
                                                    </a>
                                                </h3>
                                                <div class="flex items-center mt-1 space-x-3">
                                                    <button type="button"
                                                            onclick="removeFromCart(<?php echo $item['id']; ?>, '<?php echo htmlspecialchars($item['name']); ?>')"
                                                            class="inline-flex items-center text-sm text-red-500 hover:text-red-700 transition-colors group">
                                                        <i class="fas fa-trash-alt mr-1.5 group-hover:scale-110 transition-transform"></i> Remove
                                                    </button>
                                                    <span class="text-gray-300">•</span>
                                                    <button type="button" class="text-sm text-gray-500 hover:text-primary transition-colors group">
                                                        <i class="far fa-heart mr-1.5 group-hover:scale-110 transition-transform"></i> Save for later
                                                    </button>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="md:text-center md:col-span-2">
                                            <span class="md:hidden text-sm font-medium text-gray-500">Price: </span>
                                            <div class="flex flex-col">
                                                <span class="font-bold text-gray-800">KES <?php echo number_format($item['price'], 2); ?></span>
                                                <?php if ($item['price'] > 1000): ?>
                                                    <span class="text-xs text-green-600 font-medium bg-green-50 px-2 py-1 rounded-full inline-block w-fit mx-auto mt-1">
                                                        <i class="fas fa-tag mr-1"></i> Save 10%
                                                    </span>
                                                <?php endif; ?>
                                            </div>
                                        </div>

                                        <div class="flex items-center justify-between md:justify-center md:col-span-3">
                                            <span class="md:hidden text-sm font-medium text-gray-500">Qty: </span>
                                            <div class="flex items-center border border-gray-200 rounded-xl overflow-hidden bg-white shadow-sm hover:shadow-md transition-shadow">
                                                <button type="button"
                                                        class="decrease-qty w-10 h-10 flex items-center justify-center bg-gray-50 hover:bg-red-50 text-gray-600 hover:text-red-600 transition-all duration-200"
                                                        data-id="<?php echo $item['id']; ?>"
                                                        title="Decrease quantity">
                                                    <i class="fas fa-minus text-xs"></i>
                                                </button>
                                                <input type="number"
                                                       name="quantities[<?php echo $item['id']; ?>]"
                                                       value="<?php echo $item['quantity']; ?>"
                                                       min="1"
                                                       class="w-12 h-10 text-center border-x-0 border-gray-200 focus:ring-2 focus:ring-primary focus:border-transparent bg-transparent"
                                                       title="Quantity">
                                                <button type="button"
                                                        class="increase-qty w-10 h-10 flex items-center justify-center bg-gray-50 hover:bg-green-50 text-gray-600 hover:text-green-600 transition-all duration-200"
                                                        data-id="<?php echo $item['id']; ?>"
                                                        title="Increase quantity">
                                                    <i class="fas fa-plus text-xs"></i>
                                                </button>
                                            </div>
                                        </div>

                                        <div class="md:text-right md:col-span-2">
                                            <span class="md:hidden text-sm font-medium text-gray-500">Total: </span>
                                            <div class="flex flex-col items-end">
                                                <span class="font-bold text-lg text-gray-900 transition-colors group-hover:text-primary">
                                                    KES <?php echo number_format($item['total'], 2); ?>
                                                </span>
                                                <?php if ($item['quantity'] > 1): ?>
                                                    <span class="text-xs text-gray-500">
                                                        <?php echo $item['quantity']; ?> × KES <?php echo number_format($item['price'], 2); ?>
                                                    </span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>

                            <div class="p-5 bg-gradient-to-r from-gray-50 via-gray-50 to-gray-100 flex flex-col sm:flex-row justify-between items-center gap-4 border-t border-gray-200">
                                <div class="w-full sm:w-auto">
                                    <div class="flex items-center bg-white rounded-lg shadow-sm border border-gray-200 hover:shadow-md transition-shadow">
                                        <div class="relative">
                                            <i class="fas fa-tag absolute left-3 top-1/2 transform -translate-y-1/2 text-primary"></i>
                                            <input type="text"
                                                   placeholder="Enter promo code"
                                                   class="pl-10 pr-4 py-3 border-0 rounded-l-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent w-full sm:w-64 text-sm">
                                        </div>
                                        <button type="button"
                                                class="px-6 py-3 bg-primary text-white font-medium rounded-r-lg hover:bg-primary-dark transition-colors whitespace-nowrap text-sm">
                                            <i class="fas fa-check mr-2"></i>Apply
                                        </button>
                                    </div>
                                </div>
                                <div class="flex flex-col sm:flex-row items-center gap-3 w-full sm:w-auto">
                                    <a href="menu.php" class="w-full sm:w-auto text-center px-6 py-3 border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors text-gray-700 hover:text-primary group">
                                        <i class="fas fa-arrow-left mr-2 group-hover:-translate-x-1 transition-transform"></i> Continue Shopping
                                    </a>
                                    <button type="submit"
                                            name="update_cart"
                                            class="w-full sm:w-auto px-6 py-3 bg-gradient-to-r from-primary to-primary-dark text-white font-medium rounded-lg hover:opacity-90 transition-all transform hover:-translate-y-0.5 shadow-md hover:shadow-lg group">
                                        <i class="fas fa-sync-alt mr-2 group-hover:rotate-180 transition-transform duration-300"></i> Update Cart
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Order Summary -->
                <div class="lg:col-span-4 space-y-6">
                    <div class="bg-white rounded-2xl shadow-sm overflow-hidden border border-gray-100 sticky top-6 z-10 transform hover:scale-[1.02] transition-transform duration-300">
                        <div class="bg-gradient-to-r from-primary via-primary-dark to-secondary p-6 text-white relative overflow-hidden">
                            <!-- Background decoration -->
                            <div class="absolute inset-0 bg-gradient-to-br from-white/10 via-transparent to-white/5"></div>
                            <div class="absolute -top-4 -right-4 w-20 h-20 bg-white/10 rounded-full"></div>
                            <div class="absolute -bottom-4 -left-4 w-16 h-16 bg-white/10 rounded-full"></div>

                            <div class="relative z-10">
                                <h2 class="text-2xl font-bold flex items-center mb-2">
                                    <i class="fas fa-receipt mr-3"></i> Order Summary
                                </h2>
                                <p class="text-primary-100 text-sm">Review your order details</p>
                            </div>
                        </div>

                        <div class="p-6 space-y-5">
                            <div id="cart-summary" class="space-y-4">
                                <div class="flex justify-between items-center py-2 border-b border-gray-100">
                                    <span class="text-gray-600 flex items-center">
                                        <i class="fas fa-shopping-bag mr-2 text-primary"></i>
                                        Subtotal (<span id="item-count" class="font-semibold"><?php echo array_sum(array_column($cartDetails, 'quantity')); ?></span> items)
                                    </span>
                                    <span class="font-medium text-gray-800">KES <span id="subtotal"><?php echo number_format($subtotal, 2); ?></span></span>
                                </div>

                                <div class="flex justify-between items-center py-3 border-b border-gray-100">
                                    <div class="flex items-center">
                                        <i class="fas fa-truck text-primary mr-2"></i>
                                        <span class="text-gray-600">Delivery Fee</span>
                                        <div class="text-xs text-green-600 font-medium mt-1 ml-2 bg-green-50 px-2 py-1 rounded-full">
                                            <i class="fas fa-info-circle mr-1"></i> Free over KES 1,500
                                        </div>
                                    </div>
                                    <span class="font-medium text-gray-800">
                                        <span id="delivery-fee" class="<?php echo $subtotal >= 1500 ? 'text-green-600' : ''; ?>">
                                            <?php echo $subtotal >= 1500 ? 'Free' : 'KES ' . number_format($delivery_fee, 2); ?>
                                        </span>
                                        <span id="delivery-fee-amount" class="hidden"><?php echo $delivery_fee; ?></span>
                                    </span>
                                </div>

                                <div class="pt-2 border-t border-gray-100">
                                    <div class="flex items-center justify-between mb-3">
                                        <span class="text-gray-600 flex items-center">
                                            <i class="fas fa-tag mr-2 text-primary"></i> Promo Code
                                        </span>
                                        <span class="text-primary font-medium">-KES 0.00</span>
                                    </div>
                                    <div class="flex bg-gray-50 rounded-lg overflow-hidden">
                                        <input type="text"
                                               placeholder="Enter code"
                                               class="flex-1 px-4 py-3 bg-transparent border-0 focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent text-sm placeholder-gray-400">
                                        <button type="button"
                                                class="px-4 bg-primary text-white hover:bg-primary-dark transition-colors text-sm font-medium">
                                            <i class="fas fa-check"></i>
                                        </button>
                                    </div>
                                </div>

                                <div class="bg-gradient-to-r from-primary/5 to-secondary/5 p-4 rounded-lg border border-primary/10 relative">
                                    <div class="flex justify-between items-center mb-2">
                                        <span class="font-semibold text-gray-700 flex items-center">
                                            <i class="fas fa-calculator mr-2 text-primary"></i> Total Amount
                                        </span>
                                        <span class="text-2xl font-bold text-primary">KES <span id="total-amount"><?php echo number_format($total, 2); ?></span></span>
                                    </div>
                                    <div class="text-xs text-gray-500 flex items-center">
                                        <i class="fas fa-info-circle mr-1"></i> Includes all taxes and fees
                                    </div>
                                </div>

                                <div class="relative z-30 bg-white p-2">
                                    <?php if (isset($_SESSION['user_id'])): ?>
                                        <a href="checkout.php"
                                           class="block w-full px-6 py-4 bg-gradient-to-r from-primary via-primary-dark to-secondary text-white font-bold text-center rounded-xl hover:opacity-90 transition-all transform hover:-translate-y-0.5 shadow-lg hover:shadow-xl group">
                                            <i class="fas fa-lock mr-2 group-hover:scale-110 transition-transform"></i> Proceed to Checkout
                                        </a>
                                    <?php else: ?>
                                        <a href="auth/login.php?redirect=<?php echo urlencode('checkout.php'); ?>"
                                           class="block w-full px-6 py-4 bg-gradient-to-r from-primary via-primary-dark to-secondary text-white font-bold text-center rounded-xl hover:opacity-90 transition-all transform hover:-translate-y-0.5 shadow-lg hover:shadow-xl group">
                                            <i class="fas fa-sign-in-alt mr-2 group-hover:scale-110 transition-transform"></i> Login to Checkout
                                        </a>
                                        <p class="text-sm text-center text-gray-500 mt-2">
                                            Don't have an account?
                                            <a href="auth/register.php?redirect=<?php echo urlencode('checkout.php'); ?>" class="text-primary hover:underline transition-colors">
                                                Register here
                                            </a>
                                        </p>
                                    <?php endif; ?>
                                </div>

                                <div class="flex items-center justify-center space-x-2 pt-2 text-sm">
                                    <span class="text-gray-500">or</span>
                                    <a href="menu.php" class="font-medium text-primary hover:underline transition-colors group">
                                        <i class="fas fa-arrow-left mr-1 group-hover:-translate-x-1 transition-transform"></i> Continue Shopping
                                    </a>
                                </div>
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
// Enhanced cart functionality with better animations and UX
document.addEventListener('DOMContentLoaded', function() {
    // Initialize cart items with staggered animations
    initializeCartAnimations();

    // Enhanced quantity controls
    document.addEventListener('click', function(e) {
        // Increase quantity with enhanced animation
        if (e.target.classList.contains('increase-qty') || e.target.closest('.increase-qty')) {
            e.preventDefault();
            const button = e.target.classList.contains('increase-qty') ? e.target : e.target.closest('.increase-qty');
            const input = button.previousElementSibling;
            const newValue = parseInt(input.value) + 1;

            if (newValue <= 99) { // Max quantity limit
                updateCartQuantity(button.dataset.id, newValue, button, 'increase');
            }
        }

        // Decrease quantity with enhanced animation
        if (e.target.classList.contains('decrease-qty') || e.target.closest('.decrease-qty')) {
            e.preventDefault();
            const button = e.target.classList.contains('decrease-qty') ? e.target : e.target.closest('.decrease-qty');
            const input = button.nextElementSibling;
            const newValue = parseInt(input.value) - 1;

            if (newValue >= 1) {
                updateCartQuantity(button.dataset.id, newValue, button, 'decrease');
            }
        }
    });

    // Enhanced input validation
    document.addEventListener('input', function(e) {
        if (e.target.getAttribute('name') && e.target.getAttribute('name').startsWith('quantities')) {
            let value = parseInt(e.target.value);
            if (isNaN(value) || value < 1) {
                e.target.value = 1;
                value = 1;
            } else if (value > 99) {
                e.target.value = 99;
                value = 99;
            }
            updateCartQuantity(e.target.name.match(/\[(\d+)\]/)[1], value, e.target, 'input');
        }
    });

    // Remove item functionality
    window.removeFromCart = function(productId, productName) {
        if (confirm(`Remove "${productName}" from your cart?`)) {
            // Show loading state
            const button = event.target;
            const originalText = button.innerHTML;
            button.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Removing...';
            button.disabled = true;

            // Make request to remove item
            fetch(`cart.php?remove=${productId}`, {
                method: 'GET',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => response.text())
            .then(data => {
                // Reload page to update cart
                window.location.reload();
            })
            .catch(error => {
                console.error('Error removing item:', error);
                button.innerHTML = originalText;
                button.disabled = false;
                showNotification('error', 'Failed to remove item. Please try again.');
            });
        }
    };

    // Enhanced button animations
    function animateButton(button, type = 'default') {
        button.classList.add('animate-pulse');
        if (type === 'increase') {
            button.classList.add('bg-green-100', 'text-green-600');
        } else if (type === 'decrease') {
            button.classList.add('bg-red-100', 'text-red-600');
        }

        setTimeout(() => {
            button.classList.remove('animate-pulse', 'bg-green-100', 'text-green-600', 'bg-red-100', 'text-red-600');
        }, 300);
    }

    // Initialize cart animations
    function initializeCartAnimations() {
        const cartItems = document.querySelectorAll('.cart-item');
        cartItems.forEach((item, index) => {
            setTimeout(() => {
                item.style.opacity = '1';
                item.style.transform = 'translateY(0)';
            }, index * 100);
        });
    }

    // Enhanced cart quantity update
    async function updateCartQuantity(productId, quantity, element, type) {
        const formData = new FormData();
        formData.append('product_id', productId);
        formData.append('quantity', quantity);

        // Show loading state on buttons
        const buttons = document.querySelectorAll('.decrease-qty, .increase-qty');
        buttons.forEach(btn => {
            btn.disabled = true;
            btn.classList.add('opacity-50');
        });

        // Animate the button that was clicked
        animateButton(element, type);

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
                // Update cart summary with smooth animation
                updateCartSummary(data.subtotal, data.item_count, data);
                showNotification('success', data.message || 'Cart updated successfully');

                // Update item counter badges on product images
                updateItemBadges();
            } else {
                throw new Error(data.message || 'Failed to update cart');
            }
        } catch (error) {
            console.error('Error updating cart:', error);
            showNotification('error', error.message || 'An error occurred while updating the cart');

            // Reset quantity if update failed
            const input = element.closest('.flex').querySelector('input');
            if (input) {
                input.value = quantity - (type === 'increase' ? 1 : -1);
            }
        } finally {
            // Re-enable buttons
            buttons.forEach(btn => {
                btn.disabled = false;
                btn.classList.remove('opacity-50');
            });
        }
    }

    // Update item badges on product images
    function updateItemBadges() {
        const badges = document.querySelectorAll('.absolute.-top-2.-right-2');
        // Badges are updated dynamically based on quantity inputs
    }

    // Enhanced cart summary update
    function updateCartSummary(subtotal, itemCount, data = null) {
        // Animate item count
        const itemCountElement = document.getElementById('item-count');
        if (itemCountElement) {
            animateNumber(itemCountElement, itemCount);
        }

        // Animate subtotal
        const subtotalElement = document.getElementById('subtotal');
        if (subtotalElement) {
            animateNumber(subtotalElement, subtotal);
        }

        // Update delivery fee with animation
        const deliveryFeeElement = document.getElementById('delivery-fee');
        const deliveryFeeAmount = subtotal >= 1500 ? 0 : 200;

        if (deliveryFeeElement) {
            const wasFree = deliveryFeeElement.textContent === 'Free';
            const isFree = subtotal >= 1500;

            if (wasFree !== isFree) {
                deliveryFeeElement.classList.add('animate-pulse');
                setTimeout(() => deliveryFeeElement.classList.remove('animate-pulse'), 500);
            }

            if (isFree) {
                deliveryFeeElement.textContent = 'Free';
                deliveryFeeElement.classList.add('text-green-600');
            } else {
                deliveryFeeElement.textContent = 'KES ' + deliveryFeeAmount.toFixed(2);
                deliveryFeeElement.classList.remove('text-green-600');
            }
        }

        // Animate total with highlight effect
        const totalElement = document.getElementById('total-amount');
        if (totalElement) {
            const total = parseFloat(subtotal) + parseFloat(deliveryFeeAmount);
            animateNumber(totalElement, total);

            // Add highlight effect
            totalElement.classList.add('text-primary', 'font-bold');
            setTimeout(() => {
                totalElement.classList.remove('text-primary', 'font-bold');
                totalElement.classList.add('text-gray-900');
            }, 1000);
        }
    }

    // Animate number changes
    function animateNumber(element, newValue) {
        element.classList.add('animate-pulse');
        setTimeout(() => {
            element.textContent = typeof newValue === 'number' ? newValue.toFixed(2) : newValue;
            element.classList.remove('animate-pulse');
        }, 150);
    }

    // Enhanced notification system
    function showNotification(type, message) {
        // Remove any existing notifications
        const existingAlerts = document.querySelectorAll('.cart-notification');
        existingAlerts.forEach(alert => alert.remove());

        const notification = document.createElement('div');
        notification.className = `cart-notification fixed top-4 right-4 z-50 px-6 py-4 rounded-xl shadow-lg text-white font-medium transform translate-x-full transition-transform duration-300 ${
            type === 'success' ? 'bg-green-500' : 'bg-red-500'
        }`;

        notification.innerHTML = `
            <div class="flex items-center">
                <i class="fas ${type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'} mr-3 text-lg"></i>
                <div>
                    <div class="font-semibold">${type === 'success' ? 'Success!' : 'Error!'}</div>
                    <div class="text-sm opacity-90">${message}</div>
                </div>
                <button onclick="this.parentElement.parentElement.remove()" class="ml-4 text-white/70 hover:text-white">
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

    // Enhanced promo code functionality
    document.querySelectorAll('input[placeholder*="promo"], input[placeholder*="code"]').forEach(input => {
        const applyButton = input.nextElementSibling;
        if (applyButton) {
            input.addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    applyPromoCode(this.value, applyButton);
                }
            });

            applyButton.addEventListener('click', function() {
                applyPromoCode(input.value, this);
            });
        }
    });

    function applyPromoCode(code, button) {
        if (!code.trim()) {
            showNotification('error', 'Please enter a promo code');
            return;
        }

        button.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
        button.disabled = true;

        // Simulate promo code validation (replace with actual API call)
        setTimeout(() => {
            showNotification('success', `Promo code "${code}" applied! You saved 10%`);
            button.innerHTML = '<i class="fas fa-check"></i>';
            button.disabled = false;
        }, 1000);
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

// Add loading state to forms
document.querySelectorAll('form').forEach(form => {
    form.addEventListener('submit', function() {
        const submitButton = this.querySelector('button[type="submit"]');
        if (submitButton) {
            submitButton.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Processing...';
            submitButton.disabled = true;
        }
    });
});
</script>

<?php include 'includes/footer.php'; ?>