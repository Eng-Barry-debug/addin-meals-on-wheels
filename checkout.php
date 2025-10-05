<?php
session_start();
require_once 'includes/config.php';
require_once 'includes/Cart.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: auth/login.php?redirect=' . urlencode('checkout.php'));
    exit();
}

// Initialize cart
$cart = new Cart($pdo);
$cartItems = $cart->getItems();
$is_cart_empty = empty($cartItems);

// Get menu items details for the cart
$menuItems = [];
$subtotal = 0;

if (!$is_cart_empty) {
    // Get menu item IDs from cart
    $menuItemIds = array_column($cartItems, 'menu_item_id');
    $placeholders = rtrim(str_repeat('?,', count($menuItemIds)), ',');
    
    // Fetch menu items details
    $stmt = $pdo->prepare("SELECT * FROM menu_items WHERE id IN ($placeholders)");
    $stmt->execute($menuItemIds);
    $menuItemsResult = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Create an associative array of menu items with their details
    foreach ($cartItems as $cartItem) {
        foreach ($menuItemsResult as $menuItem) {
            if ($menuItem['id'] == $cartItem['menu_item_id']) {
                $menuItems[] = [
                    'id' => $menuItem['id'],
                    'name' => $menuItem['name'],
                    'price' => $menuItem['price'],
                    'quantity' => $cartItem['quantity'],
                    'image' => !empty($menuItem['image']) ? 'uploads/menu/' . $menuItem['image'] : ''
                ];
                $subtotal += $menuItem['price'] * $cartItem['quantity'];
                break;
            }
        }
    }
}

// Calculate delivery fee (free for orders over 1500)
$delivery_fee = $is_cart_empty ? 0 : ($subtotal >= 1500 ? 0 : 200);
$grand_total = $subtotal + $delivery_fee;

$page_title = "Checkout - Addins Meals on Wheels";
include 'includes/header.php';
?>

<!-- Page Header -->
<section class="bg-light py-12">
    <div class="container mx-auto px-4">
        <div class="text-center">
            <h1 class="text-4xl font-bold text-neutral mb-2">Checkout</h1>
            <p class="text-gray-600">Complete your order below.</p>
        </div>
    </div>
</section>

<!-- Main Content -->
<section class="py-8 md:py-12">
    <div class="container mx-auto px-4">
        <?php if ($is_cart_empty): ?>
            <!-- Empty Cart State -->
            <div class="max-w-md mx-auto text-center py-12">
                <div class="bg-white p-8 rounded-lg shadow-md">
                    <div class="w-24 h-24 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-6">
                        <i class="fas fa-shopping-cart text-4xl text-gray-400"></i>
                    </div>
                    <h2 class="text-2xl font-bold text-neutral mb-2">Your cart is empty</h2>
                    <p class="text-gray-600 mb-6">Please add items before checking out.</p>
                    <a href="/menu.php" class="inline-block bg-primary text-white px-6 py-3 rounded-md hover:bg-opacity-90 transition-colors">
                        Browse Menu
                    </a>
                </div>
            </div>
        <?php else: ?>
            <div class="flex flex-col lg:flex-row gap-8">
                <!-- Left Column - Customer Information -->
                <div class="lg:w-2/3">
                    <div class="bg-white rounded-lg shadow overflow-hidden">
                        <div class="p-6 border-b">
                            <h2 class="text-xl font-bold text-neutral">Customer Information</h2>
                        </div>
                        
                        <form id="checkout-form" class="p-6">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <!-- Full Name -->
                                <div class="md:col-span-2">
                                    <label for="full_name" class="block text-sm font-medium text-gray-700 mb-1">Full Name <span class="text-red-500">*</span></label>
                                    <input type="text" id="full_name" name="full_name" required
                                        class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-primary focus:border-transparent">
                                </div>

                                <!-- Email -->
                                <div>
                                    <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email <span class="text-red-500">*</span></label>
                                    <input type="email" id="email" name="email" required
                                        class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-primary focus:border-transparent">
                                </div>

                                <!-- Phone -->
                                <div>
                                    <label for="phone" class="block text-sm font-medium text-gray-700 mb-1">Phone Number <span class="text-red-500">*</span></label>
                                    <input type="tel" id="phone" name="phone" required
                                        class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-primary focus:border-transparent">
                                </div>

                                <!-- Delivery Address -->
                                <div class="md:col-span-2">
                                    <label for="address" class="block text-sm font-medium text-gray-700 mb-1">Delivery Address <span class="text-red-500">*</span></label>
                                    <input type="text" id="address" name="address" required
                                        class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-primary focus:border-transparent">
                                </div>

                                <!-- Delivery Instructions -->
                                <div class="md:col-span-2">
                                    <label for="instructions" class="block text-sm font-medium text-gray-700 mb-1">Delivery Instructions (Optional)</label>
                                    <textarea id="instructions" name="instructions" rows="3"
                                        class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-primary focus:border-transparent"></textarea>
                                    <p class="mt-1 text-xs text-gray-500">Any special instructions for delivery?</p>
                                </div>

                                <!-- Payment Method -->
                                <div class="md:col-span-2 pt-4 border-t">
                                    <h3 class="text-lg font-medium text-neutral mb-4">Payment Method</h3>
                                    
                                    <div class="space-y-3">
                                        <!-- Cash on Delivery -->
                                        <div class="flex items-center">
                                            <input id="cash_on_delivery" name="payment_method" type="radio" value="cash" class="h-4 w-4 text-primary focus:ring-primary" checked>
                                            <label for="cash_on_delivery" class="ml-3 block text-sm font-medium text-gray-700">
                                                <span>Cash on Delivery</span>
                                                <p class="text-xs text-gray-500 mt-1">Pay with cash upon delivery</p>
                                            </label>
                                        </div>
                                        
                                        <!-- M-Pesa -->
                                        <div class="flex items-center">
                                            <input id="mpesa" name="payment_method" type="radio" value="mpesa" class="h-4 w-4 text-primary focus:ring-primary">
                                            <label for="mpesa" class="ml-3 block text-sm font-medium text-gray-700">
                                                <div class="flex items-center">
                                                    <span>M-Pesa</span>
                                                    <!-- Removed missing logo image -->
                                                </div>
                                                <p class="text-xs text-gray-500 mt-1">You'll be redirected to M-Pesa to complete your payment</p>
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Right Column - Order Summary -->
                <div class="lg:w-1/3">
                    <div class="bg-[#F5E6D3] rounded-lg shadow p-6 sticky top-4">
                        <h2 class="text-xl font-bold text-neutral mb-4">Order Summary</h2>
                        
                        <!-- Order Items -->
                        <div class="space-y-4 mb-6 max-h-64 overflow-y-auto pr-2">
                            <?php foreach ($menuItems as $item): ?>
                                <div class="flex justify-between items-center py-2 border-b border-gray-200 last:border-0">
                                    <div class="flex items-center space-x-3">
                                        <?php if (!empty($item['image'])): ?>
                                            <img src="<?php echo htmlspecialchars($item['image']); ?>" 
                                                 alt="<?php echo htmlspecialchars($item['name']); ?>" 
                                                 class="w-12 h-12 rounded-md object-cover">
                                        <?php endif; ?>
                                        <div>
                                            <p class="text-sm font-medium text-neutral"><?php echo htmlspecialchars($item['name']); ?></p>
                                            <p class="text-xs text-gray-600">Qty: <?php echo $item['quantity']; ?> × KES <?php echo number_format($item['price'], 2); ?></p>
                                        </div>
                                    </div>
                                    <p class="text-sm font-medium">KES <?php echo number_format($item['price'] * $item['quantity'], 2); ?></p>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <!-- Order Totals -->
                        <div class="border-t border-gray-300 pt-4 space-y-3">
                            <div class="flex justify-between">
                                <span class="text-gray-600">Subtotal (<?php echo array_sum(array_column($menuItems, 'quantity')); ?> items)</span>
                                <span class="font-medium">KES <?php echo number_format($subtotal, 2); ?></span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600">
                                    Delivery
                                    <?php if ($subtotal >= 1500): ?>
                                        <span class="text-green-600 text-xs ml-1">(Free delivery for orders over KES 1,500)</span>
                                    <?php endif; ?>
                                </span>
                                <span class="font-medium <?php echo $delivery_fee === 0 ? 'text-green-600' : ''; ?>">
                                    <?php echo $delivery_fee === 0 ? 'Free' : 'KES ' . number_format($delivery_fee, 2); ?>
                                </span>
                            </div>
                            <div class="border-t border-gray-300 my-2"></div>
                            <div class="flex justify-between text-lg font-bold">
                                <span>Total</span>
                                <span>KES <?php echo number_format($grand_total, 2); ?></span>
                            </div>
                        </div>
                        
                        <!-- CTA Buttons -->
                        <div class="mt-6 space-y-3
                        ">
                            <button type="submit" form="checkout-form" class="w-full bg-primary text-white py-3 rounded-md hover:bg-opacity-90 transition-colors font-medium">
                                Place Order
                            </button>
                            <a href="/cart.php" class="block w-full bg-secondary text-white text-center py-3 rounded-md hover:bg-opacity-90 transition-colors font-medium">
                                Back to Cart
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</section>

<!-- Success/Error Notifications (Hidden by default) -->
<div id="notification" class="fixed top-4 right-4 max-w-sm z-50 hidden">
    <div class="p-4 rounded-md shadow-lg" id="notification-content">
        <div class="flex items-center">
            <div class="flex-shrink-0" id="notification-icon"></div>
            <div class="ml-3">
                <p class="text-sm font-medium" id="notification-message"></p>
            </div>
            <div class="ml-4 flex-shrink-0 flex">
                <button onclick="document.getElementById('notification').classList.add('hidden')" class="inline-flex text-gray-400 hover:text-gray-500 focus:outline-none">
                    <span class="sr-only">Close</span>
                    <i class="fas fa-times"></i>
                </button>
            </div>
        </div>
    </div>
</div>

<script>
// Form submission handler
document.addEventListener('DOMContentLoaded', function() {
    const checkoutForm = document.getElementById('checkout-form');
    if (checkoutForm) {
        checkoutForm.addEventListener('submit', function(e) {
            e.preventDefault();

            // Get form data
            const formData = new FormData(this);
            const submitButton = document.querySelector('button[form="checkout-form"][type="submit"]');

            if (!submitButton) {
                console.error('Submit button not found');
                return;
            }

            const originalButtonText = submitButton.innerHTML;

            // Show loading state
            submitButton.disabled = true;
            submitButton.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Processing...';

            // Add CSRF token if available
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
            if (csrfToken) {
                formData.append('csrf_token', csrfToken);
            }

            // Submit form via AJAX
            fetch('process_order.php', {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json();
            })
            .then(data => {
                // Reset loading state
                submitButton.disabled = false;
                submitButton.innerHTML = originalButtonText;

                if (data && data.success) {
                    showNotification(data.message || 'Your order has been placed successfully!', 'success');
                    // Redirect to order confirmation page after a short delay
                    setTimeout(() => {
                        window.location.href = 'order-confirmation.php?order_id=' + data.order_id;
                    }, 2000);
                } else {
                    showNotification(data.message || 'Failed to place order. Please try again.', 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                // Reset loading state
                if (submitButton) {
                    submitButton.disabled = false;
                    submitButton.innerHTML = originalButtonText;
                }
                showNotification('An error occurred. Please try again.', 'error');
            });
        });
    }
});

// Show notification function
function showNotification(message, type = 'info') {
    const notification = document.getElementById('notification');
    const content = document.getElementById('notification-content');
    const icon = document.getElementById('notification-icon');
    const messageEl = document.getElementById('notification-message');
    
    // Reset classes
    content.className = 'p-4 rounded-md shadow-lg flex items-start';
    
    // Set content and styles based on type
    messageEl.textContent = message;
    
    if (type === 'success') {
        content.classList.add('bg-green-50', 'border-l-4', 'border-[#2E5E3A]');
        icon.innerHTML = '<i class="fas fa-check-circle text-[#2E5E3A] text-xl mt-0.5"></i>';
    } else if (type === 'error') {
        content.classList.add('bg-red-50', 'border-l-4', 'border-[#C1272D]');
        icon.innerHTML = '<i class="fas fa-exclamation-circle text-[#C1272D] text-xl mt-0.5"></i>';
    } else {
        content.classList.add('bg-blue-50', 'border-l-4', 'border-blue-400');
        icon.innerHTML = '<i class="fas fa-info-circle text-blue-400 text-xl mt-0.5"></i>';
    }
    
    // Show notification
    notification.classList.remove('hidden');
    
    // Auto-hide after 5 seconds (only for non-error messages)
    if (type !== 'error') {
        setTimeout(() => {
            notification.classList.add('hidden');
        }, 5000);
    }
}

// Example of showing an error (for demo purposes)
// showNotification('Please fill in all required fields.', 'error');
</script>

<?php include 'includes/footer.php'; ?>