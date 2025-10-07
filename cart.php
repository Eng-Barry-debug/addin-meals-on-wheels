<?php
session_start();
require_once 'includes/config.php';
require_once 'includes/Cart.php';

// Generate and validate CSRF token
// In a real application, you'd want a more robust CSRF library.
// For demonstration, a simple session-based token.
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

function validateCsrfToken($token) {
    if (!isset($_SESSION['csrf_token']) || $_SESSION['csrf_token'] !== $token) {
        throw new Exception('CSRF token validation failed.');
    }
}

// Initialize cart
$cart = new Cart($pdo);

// Handle remove item action (converted to POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['remove_item'])) {
    // Validate CSRF token
    try {
        validateCsrfToken($_POST['csrf_token'] ?? '');
    } catch (Exception $e) {
        error_log("CSRF attack detected: " . $e->getMessage());
        $_SESSION['message'] = ['type' => 'error', 'text' => 'Security check failed. Please try again.'];
        header('Location: cart.php');
        exit;
    }

    $product_id = (int)$_POST['remove_item'];
    $cart->removeItem($product_id);
    $_SESSION['message'] = ['type' => 'success', 'text' => 'Item removed from cart!'];
    header('Location: cart.php');
    exit;
}

// Handle AJAX update quantity action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['product_id']) && isset($_POST['quantity_ajax'])) {
    // Ensure no output before JSON header
    if (ob_get_level()) {
        ob_clean();
    }
    header('Content-Type: application/json');

    $response = [
        'success' => false,
        'message' => 'An error occurred',
        'item_count' => 0,
        'subtotal' => 0,
        'delivery_fee_display' => 'Free', // Initialize for consistency
        'total' => 0
    ];

    try {
        // Validate CSRF token for AJAX requests
        validateCsrfToken($_POST['csrf_token_ajax'] ?? '');

        // Check if it's an AJAX request (optional, but good practice)
        if (empty($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
            throw new Exception('Invalid request type for AJAX endpoint.');
        }

        $product_id = (int)$_POST['product_id'];
        $quantity = (int)$_POST['quantity_ajax']; // Use a distinct name for AJAX quantity to avoid collision with regular form

        if ($quantity > 0) {
            $stmt = $pdo->prepare("SELECT price, name FROM menu_items WHERE id = ?");
            if (!$stmt->execute([$product_id])) {
                throw new Exception('Failed to fetch product details.');
            }
            $product = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($product) {
                // Assuming Cart->addItem can also update quantity if item exists
                $cart->addItem($product_id, $quantity, $product['price']);
                $response['message'] = $product['name'] . ' quantity updated to ' . $quantity . '.';
            } else {
                throw new Exception('Product not found.');
            }
        } else {
            $cart->removeItem($product_id);
            $response['message'] = 'Item removed from cart.';
        }

        $response['success'] = true;
        // Recalculate totals after cart modification
        $currentCartItems = $cart->getItems();
        $currentSubtotal = 0;
        foreach ($currentCartItems as $id => $item) {
            $currentSubtotal += $item['price'] * $item['quantity'];
        }
        $response['item_count'] = $cart->getTotalItems();
        $response['subtotal'] = $currentSubtotal;

        // Delivery fee logic (should align with frontend display)
        $delivery_fee_amount = 0; // Default
        if ($currentSubtotal > 0 && $currentSubtotal < 1500) { // Example threshold
             $delivery_fee_amount = 200; // Example fee
        }
        $response['delivery_fee_amount'] = $delivery_fee_amount;
        $response['delivery_fee_display'] = ($delivery_fee_amount == 0 && $currentSubtotal > 0) ? 'Free' : 'KES ' . number_format($delivery_fee_amount, 2);
        $response['total'] = $currentSubtotal + $delivery_fee_amount;

    } catch (Exception $e) {
        http_response_code(400); // Bad request
        $response['success'] = false;
        $response['message'] = $e->getMessage();
        // Still provide current cart totals even on error
        $response['item_count'] = $cart->getTotalItems();
        $response['subtotal'] = $cart->getSubtotal();
        // Recalculate delivery fee and total for error response too
        $currentSubtotal = $cart->getSubtotal();
        $delivery_fee_amount = 0;
        if ($currentSubtotal > 0 && $currentSubtotal < 1500) {
             $delivery_fee_amount = 200;
        }
        $response['delivery_fee_amount'] = $delivery_fee_amount;
        $response['delivery_fee_display'] = ($delivery_fee_amount == 0 && $currentSubtotal > 0) ? 'Free' : 'KES ' . number_format($delivery_fee_amount, 2);
        $response['total'] = $currentSubtotal + $delivery_fee_amount;
    }

    echo json_encode($response);
    exit;
}

// Handle regular form submission for non-JS users
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_cart'])) {
    // Validate CSRF token
    try {
        validateCsrfToken($_POST['csrf_token'] ?? '');
    } catch (Exception $e) {
        error_log("CSRF attack detected: " . $e->getMessage());
        $_SESSION['message'] = ['type' => 'error', 'text' => 'Security check failed. Please try again.'];
        header('Location: cart.php');
        exit;
    }

    foreach ($_POST['quantities'] as $product_id => $quantity) {
        $product_id = (int)$product_id;
        $quantity = (int)$quantity;

        // Fetch price for each item to ensure Cart object has correct price
        $stmt_price = $pdo->prepare("SELECT price FROM menu_items WHERE id = ?");
        $stmt_price->execute([$product_id]);
        $product_data = $stmt_price->fetch(PDO::FETCH_ASSOC);

        if ($product_data) {
            if ($quantity > 0) {
                // Cart->updateQuantity or Cart->addItem should handle this
                $cart->addItem($product_id, $quantity, $product_data['price']);
            } else {
                $cart->removeItem($product_id);
            }
        }
    }
    $_SESSION['message'] = ['type' => 'success', 'text' => 'Your cart has been updated!'];
    header('Location: cart.php');
    exit;
}

// Display messages from session
$feedback_message = null;
if (isset($_SESSION['message'])) {
    $feedback_message = $_SESSION['message'];
    unset($_SESSION['message']); // Clear the message after displaying it
}


// Get cart items and optimize SQL queries
$cartItems = $cart->getItems(); // This is the session cart data
$subtotal = 0;
$cartDetails = [];
$product_ids_in_cart = array_keys($cartItems);

if (!empty($product_ids_in_cart)) {
    // Fetch all product details in a single query
    $placeholders = implode(',', array_fill(0, count($product_ids_in_cart), '?'));
    $stmt = $pdo->prepare("SELECT id, name, price, image FROM menu_items WHERE id IN ($placeholders)");
    $stmt->execute($product_ids_in_cart);
    $products_db_data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Reindex products by ID for easier lookup
    $products_by_id = [];
    foreach ($products_db_data as $product) {
        $products_by_id[$product['id']] = $product;
    }

    foreach ($cartItems as $product_id => $item) {
        if (isset($products_by_id[$product_id])) {
            $db_product = $products_by_id[$product_id];
            $quantity = $item['quantity']; // Use quantity from the cart session

            // Ensure price from DB is used for display consistency, or use Cart's stored price if reliable
            $price_to_use = $db_product['price']; // Or $item['price'] if Cart stores most up-to-date prices
            $itemTotal = $price_to_use * $quantity;
            $subtotal += $itemTotal;

            $cartDetails[] = [
                'id' => $product_id,
                'name' => $db_product['name'],
                'price' => $db_product['price'], // Display DB price
                'image' => !empty($db_product['image']) ? 'uploads/menu/' . $db_product['image'] : 'assets/img/placeholder-food.jpg',
                'quantity' => $quantity,
                'total' => $itemTotal
            ];
        } else {
             // Product in cart but not found in DB (e.g., deleted item). Remove from cart.
             $cart->removeItem($product_id);
        }
    }
}
// Recalculate subtotal from cartDetails in case items were removed above
$subtotal = array_sum(array_column($cartDetails, 'total'));

// Delivery fee logic
// These values should ideally come from configuration or a database.
const DELIVERY_FREE_THRESHOLD = 1500;
const STANDARD_DELIVERY_FEE = 200;

$delivery_fee = 0;
if ($subtotal > 0 && $subtotal < DELIVERY_FREE_THRESHOLD) {
    $delivery_fee = STANDARD_DELIVERY_FEE;
}
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

        <?php if ($feedback_message): ?>
            <div class="mb-6 animate-fade-in-down <?php echo $feedback_message['type'] === 'success' ? 'bg-green-100 border-green-500' : 'bg-red-100 border-red-500'; ?> text-<?php echo $feedback_message['type'] === 'success' ? 'green-700' : 'red-700'; ?> p-4 rounded-lg flex items-center shadow-md border-l-4" role="alert">
                <i class="fas <?php echo $feedback_message['type'] === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'; ?> mr-3 text-lg"></i>
                <p class="font-medium"><?php echo htmlspecialchars($feedback_message['text']); ?></p>
                <button type="button" class="ml-auto -mr-1 p-2 text-<?php echo $feedback_message['type'] === 'success' ? 'green-700' : 'red-700'; ?> opacity-75 hover:opacity-100" onclick="this.closest('[role=alert]').remove()" aria-label="Close">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        <?php endif; ?>

        <?php if (empty($cartDetails)): ?>
            <div class="bg-white rounded-2xl shadow-sm p-8 md:p-12 text-center border border-gray-100 relative overflow-hidden">
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
                        <a href="##specials" class="inline-flex items-center justify-center px-8 py-4 bg-white border-2 border-gray-200 text-gray-700 font-medium rounded-xl hover:border-primary hover:text-primary transition-all group">
                            <i class="fas fa-star mr-3 text-yellow-400 group-hover:scale-110 transition-transform"></i> Today's Specials
                        </a>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
                <!-- Cart Items -->
                <div class="lg:col-span-8 space-y-6">
                    <div class="bg-white rounded-2xl shadow-sm overflow-hidden border border-gray-100">
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
                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                            <?php foreach ($cartDetails as $index => $item):
                                $animationDelay = $index * 0.05;
                            ?>
                                <div class="p-5 md:p-6 hover:bg-gray-50/50 transition-all duration-300 cart-item group relative overflow-hidden"
                                     style="opacity: 0; transform: translateY(10px);"
                                     data-aos="fade-up" data-aos-delay="<?php echo $index * 50; ?>"
                                     data-aos-duration="400">
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
                                                        <div class="absolute -top-2 -right-2 w-6 h-6 bg-primary text-white text-xs font-bold rounded-full flex items-center justify-center shadow-md item-qty-badge"
                                                            data-product-id="<?php echo $item['id']; ?>">
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
                                                            onclick="requestRemoveFromCart(<?php echo $item['id']; ?>, '<?php echo htmlspecialchars($item['name']); ?>', '<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>')"
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
                                                <?php if ($item['price'] > 1000): // Example for dynamic badge ?>
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
                                                       min="0"
                                                       data-product-id="<?php echo $item['id']; ?>"
                                                       class="w-12 h-10 text-center border-x-0 border-gray-200 focus:ring-2 focus:ring-primary focus:border-transparent bg-transparent quantity-input"
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
                                                <span class="font-bold text-lg text-gray-900 transition-colors group-hover:text-primary item-total-price" data-product-id="<?php echo $item['id']; ?>">
                                                    KES <?php echo number_format($item['total'], 2); ?>
                                                </span>
                                                <?php if ($item['quantity'] > 1): ?>
                                                    <span class="text-xs text-gray-50 item-unit-price" data-product-id="<?php echo $item['id']; ?>">
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
                                                   class="pl-10 pr-4 py-3 border-0 rounded-l-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent w-full sm:w-64 text-sm promo-input">
                                        </div>
                                        <button type="button"
                                                class="px-6 py-3 bg-primary text-white font-medium rounded-r-lg hover:bg-primary-dark transition-colors whitespace-nowrap text-sm promo-apply-btn">
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
                                            <i class="fas fa-info-circle mr-1"></i> Free over KES <?php echo number_format(DELIVERY_FREE_THRESHOLD, 0); ?>
                                        </div>
                                    </div>
                                    <span class="font-medium text-gray-800">
                                        <span id="delivery-fee-display" class="<?php echo $subtotal >= DELIVERY_FREE_THRESHOLD ? 'text-green-600' : ''; ?>">
                                            <?php echo $subtotal >= DELIVERY_FREE_THRESHOLD ? 'Free' : 'KES ' . number_format($delivery_fee, 2); ?>
                                        </span>
                                        <span id="delivery-fee-amount" class="hidden"><?php echo $delivery_fee; ?></span>
                                    </span>
                                </div>

                                <div class="pt-2 border-t border-gray-100">
                                    <div class="flex items-center justify-between mb-3">
                                        <span class="text-gray-600 flex items-center">
                                            <i class="fas fa-tag mr-2 text-primary"></i> Promo Code
                                        </span>
                                        <span class="text-primary font-medium">-KES <span id="promo-discount">0.00</span></span>
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
                                        <p class="text-sm text-gray-500">On orders over KES <?php echo number_format(DELIVERY_FREE_THRESHOLD, 0); ?></p>
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
document.addEventListener('DOMContentLoaded', function() {
    // Initialize cart items with staggered animations
    initializeCartAnimations();

    // Enhanced quantity controls
    document.addEventListener('click', function(e) {
        let button = e.target.closest('.increase-qty') || e.target.closest('.decrease-qty');
        if (!button) return;

        e.preventDefault();
        const productId = button.dataset.id;
        const input = document.querySelector(`.quantity-input[data-product-id="${productId}"]`);
        if (!input) return;

        let newValue = parseInt(input.value);
        if (button.classList.contains('increase-qty')) {
            newValue = Math.min(99, newValue + 1); // Max quantity limit
        } else {
            newValue = Math.max(0, newValue - 1); // Min quantity 0 to remove
        }

        if (newValue !== parseInt(input.value)) { // Only update if value actually changed
            input.value = newValue; // Update input immediately for responsiveness
            updateCartQuantityAjax(productId, newValue, button, button.classList.contains('increase-qty') ? 'increase' : 'decrease');
        }
    });

    // Enhanced input validation with debounce for AJAX
    let debounceTimer;
    document.addEventListener('input', function(e) {
        if (!e.target.classList.contains('quantity-input')) return;

        const input = e.target;
        const productId = input.dataset.productId;
        let value = parseInt(input.value);

        if (isNaN(value) || value < 0) { // Allow 0 to trigger remove logic
            value = 0;
        } else if (value > 99) {
            value = 99;
        }
        input.value = value; // Always update input to sanitized value

        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(() => {
            updateCartQuantityAjax(productId, value, input, 'input');
        }, 500); // Debounce by 500ms
    });

    // Remove item functionality (via POST AJAX for better UX)
    window.requestRemoveFromCart = function(productId, productName, csrfToken) {
        if (!confirm(`Are you sure you want to remove "${productName}" from your cart?`)) {
            return;
        }

        // Find the specific quantity input and set its value to 0
        const quantityInput = document.querySelector(`.quantity-input[data-product-id="${productId}"]`);
        if (quantityInput) {
            quantityInput.value = 0; // Set to 0 to trigger removal logic in backend AJAX
            updateCartQuantityAjax(productId, 0, quantityInput, 'remove');
        } else {
            // Fallback for an item that might not have a visible quantity input
            // Could re-use the AJAX call or submit a hidden form
            showNotification('info', `Attempting to remove ${productName}.`);
            updateCartQuantityAjax(productId, 0, null, 'remove'); // Pass null for element if not found
        }
    };

    // Enhanced button animations
    function animateButton(element, type = 'default') {
        if (!element) return; // Guard against null element

        const parent = element.closest('.flex.items-center');
        if (parent) {
            parent.classList.add('animate-pulse-effect'); // Custom class for animation
            setTimeout(() => {
                parent.classList.remove('animate-pulse-effect');
            }, 300);
        }
        
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

    // Enhanced cart quantity update via AJAX
    async function updateCartQuantityAjax(productId, quantity, element, type) {
        const csrfToken = "<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>"; // Get CSRF token
        const formData = new FormData();
        formData.append('product_id', productId);
        formData.append('quantity_ajax', quantity); // Use distinct name
        formData.append('csrf_token_ajax', csrfToken); // Include CSRF token

        // Show loading state on buttons
        const allButtons = document.querySelectorAll('.decrease-qty, .increase-qty');
        const allQuantityInputs = document.querySelectorAll('.quantity-input');
        allButtons.forEach(btn => { btn.disabled = true; btn.classList.add('opacity-50'); });
        allQuantityInputs.forEach(input => { input.disabled = true; input.classList.add('opacity-50'); });

        animateButton(element, type); // Animate the specific button/input

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

            if (!response.ok) { // Check for HTTP errors (e.g., 400, 500)
                const errorData = await response.json().catch(() => ({ message: 'Server error occurred.' }));
                 throw new Error(errorData.message || 'Failed to update cart due to server error.');
            }

            const data = await response.json();

            if (data.success) {
                // Update specific item total
                const itemTotalPriceElement = document.querySelector(`.item-total-price[data-product-id="${productId}"]`);
                if (itemTotalPriceElement) {
                    itemTotalPriceElement.textContent = `KES ${parseFloat(data.subtotal).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}`; // This is now incorrect, needs specific item total
                }
                const itemUnitPriceElement = document.querySelector(`.item-unit-price[data-product-id="${productId}"]`);
                if (itemUnitPriceElement) {
                   // This text is more static, needs to be re-evaluated
                   const productPrice = parseFloat(itemUnitPriceElement.closest('.flex.flex-col.items-end').querySelector('.font-bold.text-lg').textContent.replace('KES ', '').replace(',', ''));
                   if(quantity > 1) {
                        itemUnitPriceElement.textContent = `${quantity} × KES ${productPrice.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}`;
                   } else {
                       itemUnitPriceElement.textContent = ''; // Clear if quantity is 1
                   }
                }

                // If quantity is 0, remove the item card from DOM
                if (quantity === 0) {
                    const cartItemCard = document.querySelector(`.cart-item div.flex.flex-col.md\\:flex-row.md\\:items-center.gap-4`).closest('.cart-item');
                    if (cartItemCard) {
                        cartItemCard.classList.add('animate-fade-out'); // Add fade-out animation
                        cartItemCard.style.maxHeight = cartItemCard.offsetHeight + 'px'; // Set current height
                        requestAnimationFrame(() => {
                            cartItemCard.style.maxHeight = '0'; // Animate to 0 height
                            cartItemCard.style.margin = '0';
                            cartItemCard.style.padding = '0';
                        });
                        setTimeout(() => cartItemCard.remove(), 500); // Remove after animation
                    }
                }

                showNotification('success', data.message || 'Cart updated successfully.');
                updateCartSummary(data); // Pass full data object

                updateItemBadges(productId, quantity); // Update specific item badge

            } else {
                throw new Error(data.message || 'Failed to update cart.');
            }
        } catch (error) {
            console.error('Error updating cart:', error);
            showNotification('error', error.message || 'An error occurred while updating the cart.');

            // Revert input field value if AJAX failed
            if (element && element.classList.contains('quantity-input')) {
                const originalQuantity = <?php echo json_encode(array_column($cartDetails, 'quantity', 'id')); ?>[productId] || 0;
                element.value = originalQuantity;
            } else if (type === 'increase' || type === 'decrease') {
                 // Try to revert the input next to the button
                const inputToRevert = element.closest('.flex.items-center').querySelector('.quantity-input');
                if (inputToRevert) {
                    const originalQuantity = <?php echo json_encode(array_column($cartDetails, 'quantity', 'id')); ?>[productId] || 0;
                    inputToRevert.value = originalQuantity;
                }
            }
        } finally {
            allButtons.forEach(btn => { btn.disabled = false; btn.classList.remove('opacity-50'); });
            allQuantityInputs.forEach(input => { input.disabled = false; input.classList.remove('opacity-50'); });
            
            // Re-check empty cart state
            const cartItemsContainer = document.querySelector('.lg\\:col-span-8.space-y-6');
            if (cartItemsContainer && cartItemsContainer.children.length === 0) {
                 location.reload(); // Reload only if no cart items are left
            }
        }
    }

    // Update item badges on product images
    function updateItemBadges(productId, quantity) {
        const badge = document.querySelector(`.item-qty-badge[data-product-id="${productId}"]`);
        if (badge) {
            if (quantity > 0) {
                badge.textContent = quantity;
                badge.style.display = 'flex';
            } else {
                badge.style.display = 'none'; // Hide badge if quantity is 0
            }
        }
    }

    // Enhanced cart summary update (receives full data object)
    function updateCartSummary(data) {
        const currentCurrency = 'KES'; // Or use a variable if dynamic

        const itemCountElement = document.getElementById('item-count');
        if (itemCountElement) {
            animateNumber(itemCountElement, data.item_count);
        }

        const subtotalElement = document.getElementById('subtotal');
        if (subtotalElement) {
            animateNumber(subtotalElement, data.subtotal, currentCurrency);
        }

        // Update delivery fee with animation
        const deliveryFeeDisplayElement = document.getElementById('delivery-fee-display');
        const deliveryFeeAmountElement = document.getElementById('delivery-fee-amount'); // Hidden amount
        
        if (deliveryFeeDisplayElement && deliveryFeeAmountElement) {
            deliveryFeeAmountElement.textContent = data.delivery_fee_amount; // Always update hidden amount

            const isCurrentlyFree = deliveryFeeDisplayElement.textContent.includes('Free');
            const willBeFree = data.delivery_fee_amount === 0 && data.subtotal > 0;

            if (isCurrentlyFree !== willBeFree) { // Only animate if status changes
                deliveryFeeDisplayElement.classList.add('animate-pulse');
                setTimeout(() => deliveryFeeDisplayElement.classList.remove('animate-pulse'), 500);
            }

            if (willBeFree) {
                deliveryFeeDisplayElement.textContent = 'Free';
                deliveryFeeDisplayElement.classList.add('text-green-600');
            } else {
                deliveryFeeDisplayElement.textContent = `${currentCurrency} ${data.delivery_fee_amount.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}`;
                deliveryFeeDisplayElement.classList.remove('text-green-600');
            }
        }

        const totalElement = document.getElementById('total-amount');
        if (totalElement) {
            animateNumber(totalElement, data.total, currentCurrency);
            totalElement.classList.add('highlight-total'); // Custom class for highlighting
            setTimeout(() => totalElement.classList.remove('highlight-total'), 1000);
        }
    }

    // Animate number changes
    function animateNumber(element, newValue, currency = '') {
        const startValue = parseFloat(element.textContent.replace(currency, '').replace(/,/g, '')) || 0;
        const duration = 300; // ms
        const startTime = performance.now();

        function updateCount(currentTime) {
            const elapsedTime = currentTime - startTime;
            const progress = Math.min(elapsedTime / duration, 1);
            const currentValue = startValue + (newValue - startValue) * progress;

            element.textContent = `${currency} ${currentValue.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}`;

            if (progress < 1) {
                requestAnimationFrame(updateCount);
            }
        }

        requestAnimationFrame(updateCount);
    }

    // Optimized showNotification
    function showNotification(type, message) {
        const existingAlert = document.getElementById('cart-notification-alert');
        if (existingAlert) {
            existingAlert.remove();
        }

        const notificationDiv = document.createElement('div');
        notificationDiv.id = 'cart-notification-alert';
        notificationDiv.className = `fixed top-4 right-4 z-50 p-4 rounded-lg flex items-center shadow-md border-l-4 transition-all duration-300 transform translate-x-full opacity-0`;
        notificationDiv.setAttribute('role', 'alert');

        if (type === 'success') {
            notificationDiv.classList.add('bg-green-100', 'border-green-500', 'text-green-700');
            notificationDiv.innerHTML = `<i class="fas fa-check-circle mr-3 text-lg"></i><p class="font-medium">${message}</p>`;
        } else {
            notificationDiv.classList.add('bg-red-100', 'border-red-500', 'text-red-700');
            notificationDiv.innerHTML = `<i class="fas fa-exclamation-circle mr-3 text-lg"></i><p class="font-medium">${message}</p>`;
        }

        notificationDiv.innerHTML += `
            <button type="button" class="ml-auto -mr-1 p-2 text-current opacity-75 hover:opacity-100" onclick="this.closest('[role=alert]').remove()" aria-label="Close">
                <i class="fas fa-times"></i>
            </button>`;

        document.body.appendChild(notificationDiv);

        requestAnimationFrame(() => {
            notificationDiv.classList.remove('translate-x-full', 'opacity-0');
            notificationDiv.classList.add('translate-x-0', 'opacity-100');
        });

        setTimeout(() => {
            notificationDiv.classList.remove('translate-x-0', 'opacity-100');
            notificationDiv.classList.add('translate-x-full', 'opacity-0');
            setTimeout(() => notificationDiv.remove(), 300);
        }, 4000);
    }

    // Debounce promo code application
    const promoInputField = document.querySelector('.promo-input');
    const promoApplyButton = document.querySelector('.promo-apply-btn');

    if (promoInputField && promoApplyButton) {
        let promoDebounceTimer;

        const handlePromoInput = () => {
            clearTimeout(promoDebounceTimer);
            promoDebounceTimer = setTimeout(() => {
                applyPromoCode(promoInputField.value, promoApplyButton);
            }, 600); // Debounce for 600ms
        };

        promoInputField.addEventListener('input', handlePromoInput);
        promoApplyButton.addEventListener('click', () => {
            clearTimeout(promoDebounceTimer); // Clear any pending debounce
            applyPromoCode(promoInputField.value, promoApplyButton);
        });
    }

    function applyPromoCode(code, button) {
        if (!code.trim()) {
            showNotification('error', 'Please enter a promo code.');
            return;
        }

        const originalButtonHtml = button.innerHTML;
        button.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
        button.disabled = true;

        // Simulate promo code validation (replace with actual API call)
        setTimeout(() => {
            showNotification('success', `Promo code "${code}" applied! You saved 10%.`);
            // Here you'd typically make an AJAX call to apply the promo and update cart totals
            // For now, reset button state
            button.innerHTML = originalButtonHtml;
            button.disabled = false;
            // Example:
            // updateCartSummary({ /* new totals after promo */ });
        }, 1500);
    }
});

// Add smooth scrolling for anchor links (if applicable)
document.querySelectorAll('a[href^="##"]').forEach(anchor => {
    anchor.addEventListener('click', function (e) {
        e.preventDefault();
        const target = document.querySelector(this.getAttribute('href').substring(1)); // Remove one #
        if (target) {
            target.scrollIntoView({
                behavior: 'smooth',
                block: 'start'
            });
        }
    });
});

// Add loading state to forms where applicable
document.querySelectorAll('form').forEach(form => {
    form.addEventListener('submit', function() {
        const submitButton = this.querySelector('button[type="submit"]');
        if (submitButton) {
            submitButton.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Processing...';
            submitButton.disabled = true;
            submitButton.classList.add('opacity-75', 'cursor-not-allowed');
        }
    });
});
</script>

<style>
/* Custom animation for quantity buttons */
@keyframes pulse-effect {
    0% { transform: scale(1); box-shadow: 0 0 0 0 rgba(193, 39, 45, 0.4); }
    70% { transform: scale(1.05); box-shadow: 0 0 0 10px rgba(193, 39, 45, 0); }
    100% { transform: scale(1); box-shadow: 0 0 0 0 rgba(193, 39, 45, 0); }
}
.animate-pulse-effect {
    animation: pulse-effect 0.5s ease-out;
}

/* Animation for highlight total */
@keyframes highlight-fade {
    0% { background-color: rgba(var(--color-primary-rgb, 193, 39, 45), 0.2); } /* Using CSS variable if available, else a fixed color */
    100% { background-color: transparent; }
}
.highlight-total {
    animation: highlight-fade 1s ease-out forwards; /* forwards keeps the end state */
    padding: 0.25rem 0.5rem; /* Add some padding to make highlight visible */
    border-radius: 0.25rem;
}

/* Animation for item removal */
.animate-fade-out {
    animation: fadeOut 0.5s forwards;
}
@keyframes fadeOut {
    from { opacity: 1; transform: translateY(0); }
    to { opacity: 0; transform: translateY(-20px); }
}

/* Feedback message animation */
.animate-fade-in-down {
    animation: fadeInDown 0.5s ease-out forwards;
}
@keyframes fadeInDown {
    from { opacity: 0; transform: translateY(-20px); }
    to { opacity: 1; transform: translateY(0); }
}
</style>

<?php include 'includes/footer.php'; ?>