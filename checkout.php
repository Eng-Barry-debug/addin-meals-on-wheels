<?php
session_start();
require_once 'includes/config.php';
require_once 'includes/Cart.php';

// Check if M-Pesa is properly configured
$mpesa_available = isMpesaConfigured();

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
                                    <h3 class="text-lg font-semibold text-gray-900 mb-6">Choose Payment Method</h3>

                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                        <!-- Cash on Delivery -->
                                        <div class="relative">
                                            <input id="cash_on_delivery" name="payment_method" type="radio" value="cash" class="sr-only peer" checked>
                                            <label for="cash_on_delivery" class="flex flex-col p-4 border-2 border-gray-200 rounded-xl cursor-pointer hover:border-primary hover:bg-primary/5 peer-checked:border-primary peer-checked:bg-primary/10 transition-all duration-200">
                                                <div class="flex items-center mb-2">
                                                    <div class="w-8 h-8 bg-green-100 rounded-full flex items-center justify-center mr-3">
                                                        <i class="fas fa-money-bill-wave text-green-600 text-sm"></i>
                                                    </div>
                                                    <div>
                                                        <h4 class="font-semibold text-gray-900">Cash on Delivery</h4>
                                                        <p class="text-xs text-gray-500">Pay with cash upon delivery</p>
                                                    </div>
                                                </div>
                                                <div class="mt-auto">
                                                    <div class="flex items-center text-green-600">
                                                        <i class="fas fa-check-circle text-sm mr-1"></i>
                                                        <span class="text-xs font-medium">Available</span>
                                                    </div>
                                                </div>
                                            </label>
                                        </div>

                                        <!-- M-Pesa -->
                                        <div class="relative">
                                            <input id="mpesa" name="payment_method" type="radio" value="mpesa" class="sr-only peer" <?php echo $mpesa_available ? '' : 'disabled'; ?>>
                                            <label for="mpesa" class="flex flex-col p-4 border-2 border-gray-200 rounded-xl cursor-pointer hover:border-primary hover:bg-primary/5 peer-checked:border-primary peer-checked:bg-primary/10 transition-all duration-200 <?php echo $mpesa_available ? '' : 'opacity-60 cursor-not-allowed'; ?>">
                                                <div class="flex items-center mb-2">
                                                    <div class="w-8 h-8 bg-green-100 rounded-full flex items-center justify-center mr-3">
                                                        <div class="px-1 py-0.5 bg-green-600 text-white text-xs font-bold rounded">M-PESA</div>
                                                    </div>
                                                    <div>
                                                        <h4 class="font-semibold text-gray-900">M-Pesa</h4>
                                                        <p class="text-xs text-gray-500">
                                                            <?php if ($mpesa_available): ?>
                                                                Pay instantly via STK Push
                                                            <?php else: ?>
                                                                M-Pesa not configured
                                                            <?php endif; ?>
                                                        </p>
                                                    </div>
                                                </div>
                                                <div class="mt-auto">
                                                    <?php if ($mpesa_available): ?>
                                                        <div class="flex items-center text-green-600">
                                                            <i class="fas fa-check-circle text-sm mr-1"></i>
                                                            <span class="text-xs font-medium">Available</span>
                                                        </div>
                                                    <?php else: ?>
                                                        <div class="flex items-center text-orange-600">
                                                            <i class="fas fa-exclamation-triangle text-sm mr-1"></i>
                                                            <span class="text-xs font-medium">Not Configured</span>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                            </label>
                                        </div>

                                        <!-- Airtel Money -->
                                        <div class="relative">
                                            <input id="airtel_money" name="payment_method" type="radio" value="airtel" class="sr-only peer">
                                            <label for="airtel_money" class="flex flex-col p-4 border-2 border-gray-200 rounded-xl cursor-pointer hover:border-primary hover:bg-primary/5 peer-checked:border-primary peer-checked:bg-primary/10 transition-all duration-200">
                                                <div class="flex items-center mb-2">
                                                    <div class="w-8 h-8 bg-red-100 rounded-full flex items-center justify-center mr-3">
                                                        <div class="px-1 py-0.5 bg-red-600 text-white text-xs font-bold rounded">AIRTEL</div>
                                                    </div>
                                                    <div>
                                                        <h4 class="font-semibold text-gray-900">Airtel Money</h4>
                                                        <p class="text-xs text-gray-500">Pay instantly via Airtel Money</p>
                                                    </div>
                                                </div>
                                                <div class="mt-auto">
                                                    <div class="flex items-center text-blue-600">
                                                        <i class="fas fa-clock text-sm mr-1"></i>
                                                        <span class="text-xs font-medium">Coming Soon</span>
                                                    </div>
                                                </div>
                                            </label>
                                        </div>

                                        <!-- Card Payments -->
                                        <div class="relative">
                                            <input id="card_payment" name="payment_method" type="radio" value="card" class="sr-only peer">
                                            <label for="card_payment" class="flex flex-col p-4 border-2 border-gray-200 rounded-xl cursor-pointer hover:border-primary hover:bg-primary/5 peer-checked:border-primary peer-checked:bg-primary/10 transition-all duration-200">
                                                <div class="flex items-center mb-2">
                                                    <div class="w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center mr-3">
                                                        <div class="flex items-center space-x-1">
                                                            <div class="w-4 h-3 bg-blue-600 rounded-sm flex items-center justify-center">
                                                                <span class="text-white text-[8px] font-bold">VISA</span>
                                                            </div>
                                                            <div class="w-4 h-3 bg-red-600 rounded-sm flex items-center justify-center">
                                                                <span class="text-white text-[8px] font-bold">MC</span>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div>
                                                        <h4 class="font-semibold text-gray-900">Credit/Debit Card</h4>
                                                        <p class="text-xs text-gray-500">Pay securely with your card</p>
                                                    </div>
                                                </div>
                                                <div class="mt-auto">
                                                    <div class="flex items-center text-blue-600">
                                                        <i class="fas fa-clock text-sm mr-1"></i>
                                                        <span class="text-xs font-medium">Coming Soon</span>
                                                    </div>
                                                </div>
                                            </label>
                                        </div>
                                    </div>

                                    <!-- Payment Details (Conditional) -->
                                    <div id="payment-details" class="mt-8 hidden">
                                        <div class="bg-gray-50 rounded-lg p-6">
                                            <h4 class="text-lg font-semibold text-gray-900 mb-4">Payment Details</h4>

                                            <!-- M-Pesa Phone Number -->
                                            <div id="mpesa-details" class="hidden mb-6">
                                                <label for="mpesa_phone" class="block text-sm font-medium text-gray-700 mb-2">M-Pesa Phone Number <span class="text-red-500">*</span></label>
                                                <div class="relative">
                                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                                        <div class="px-2 py-1 bg-green-600 text-white text-xs font-bold rounded">KE</div>
                                                    </div>
                                                    <input type="tel" id="mpesa_phone" name="mpesa_phone" placeholder="0712 345 678"
                                                        class="w-full pl-16 pr-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent transition-colors">
                                                </div>
                                                <p class="text-xs text-gray-500 mt-1">Enter your M-Pesa registered phone number</p>
                                            </div>

                                            <!-- Airtel Money Phone Number -->
                                            <div id="airtel-details" class="hidden mb-6">
                                                <label for="airtel_phone" class="block text-sm font-medium text-gray-700 mb-2">Airtel Money Phone Number <span class="text-red-500">*</span></label>
                                                <div class="relative">
                                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                                        <div class="px-2 py-1 bg-red-600 text-white text-xs font-bold rounded">KE</div>
                                                    </div>
                                                    <input type="tel" id="airtel_phone" name="airtel_phone" placeholder="0731 234 567"
                                                        class="w-full pl-16 pr-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent transition-colors">
                                                </div>
                                                <p class="text-xs text-gray-500 mt-1">Enter your Airtel Money registered phone number</p>
                                            </div>

                                            <!-- Card Details -->
                                            <div id="card-details" class="hidden space-y-4">
                                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                                    <div class="md:col-span-2">
                                                        <label for="card_number" class="block text-sm font-medium text-gray-700 mb-2">Card Number <span class="text-red-500">*</span></label>
                                                        <div class="relative">
                                                            <input type="text" id="card_number" name="card_number" placeholder="1234 5678 9012 3456"
                                                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent"
                                                                maxlength="19">
                                                            <div class="absolute right-3 top-3 text-gray-400">
                                                                <i class="fas fa-credit-card"></i>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div>
                                                        <label for="expiry_date" class="block text-sm font-medium text-gray-700 mb-2">Expiry Date <span class="text-red-500">*</span></label>
                                                        <input type="text" id="expiry_date" name="expiry_date" placeholder="MM/YY"
                                                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent"
                                                            maxlength="5">
                                                    </div>
                                                    <div>
                                                        <label for="cvv" class="block text-sm font-medium text-gray-700 mb-2">CVV <span class="text-red-500">*</span></label>
                                                        <input type="text" id="cvv" name="cvv" placeholder="123"
                                                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent"
                                                            maxlength="4">
                                                    </div>
                                                </div>
                                                <div>
                                                    <label for="card_name" class="block text-sm font-medium text-gray-700 mb-2">Cardholder Name <span class="text-red-500">*</span></label>
                                                    <input type="text" id="card_name" name="card_name" placeholder="John Doe"
                                                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                                                </div>
                                                <div class="bg-blue-50 border border-blue-200 rounded-lg p-3">
                                                    <div class="flex items-center justify-between">
                                                        <div class="flex items-center text-blue-800">
                                                            <i class="fas fa-shield-alt text-blue-600 mr-2"></i>
                                                            <span class="text-sm font-medium">Your payment information is secure and encrypted</span>
                                                        </div>
                                                        <div class="flex items-center space-x-2">
                                                            <div class="px-2 py-1 bg-blue-600 text-white text-xs font-bold rounded">VISA</div>
                                                            <div class="px-2 py-1 bg-red-600 text-white text-xs font-bold rounded">MC</div>
                                                            <div class="px-2 py-1 bg-blue-800 text-white text-xs font-bold rounded">PAYPAL</div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Right Column - Order Summary & Checkout -->
                <div class="lg:w-1/3">
                    <!-- Order Summary Toggle -->
                    <div class="bg-white p-6 rounded-lg shadow cursor-pointer mb-6" id="order-summary-toggle">
                        <div class="flex justify-between items-center">
                            <h3 class="text-lg font-bold text-neutral">Order Summary</h3>
                            <svg id="summary-arrow" class="w-5 h-5 text-gray-500 transition-transform duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                            </svg>
                        </div>
                        <div id="order-summary-content" class="hidden">
                            <div class="mt-4 space-y-4">
                                <?php foreach ($menuItems as $item): ?>
                                    <div class="flex items-center justify-between py-3 border-b border-gray-100">
                                        <div class="flex items-center space-x-4">
                                            <?php if (!empty($item['image'])): ?>
                                                <img src="<?php echo htmlspecialchars($item['image']); ?>"
                                                     alt="<?php echo htmlspecialchars($item['name']); ?>"
                                                     class="w-16 h-16 rounded-lg object-cover border border-gray-100">
                                            <?php endif; ?>
                                            <div>
                                                <p class="font-medium text-gray-900"><?php echo htmlspecialchars($item['name']); ?></p>
                                                <p class="text-sm text-gray-500">Qty: <?php echo $item['quantity']; ?></p>
                                            </div>
                                        </div>
                                        <p class="font-medium">KES <?php echo number_format($item['price'] * $item['quantity'], 2); ?></p>
                                    </div>
                                <?php endforeach; ?>
                            </div>

                            <div class="mt-6 space-y-3">
                                <div class="flex justify-between text-sm">
                                    <span class="text-gray-600">Subtotal (<?php echo array_sum(array_column($menuItems, 'quantity')); ?> items)</span>
                                    <span class="font-medium">KES <?php echo number_format($subtotal, 2); ?></span>
                                </div>
                                <div class="flex justify-between text-sm">
                                    <span class="text-gray-600">
                                        Delivery
                                        <?php if ($subtotal >= 1500): ?>
                                            <span class="text-green-600 text-xs ml-1">(Free delivery)</span>
                                        <?php endif; ?>
                                    </span>
                                    <span class="font-medium <?php echo $delivery_fee === 0 ? 'text-green-600' : ''; ?>">
                                        <?php echo $delivery_fee === 0 ? 'Free' : 'KES ' . number_format($delivery_fee, 2); ?>
                                    </span>
                                </div>
                                <?php if (isset($_SESSION['promo_discount']) && $_SESSION['promo_discount'] > 0): ?>
                                    <div class="flex justify-between text-sm">
                                        <span class="text-gray-600">Promo Code Discount</span>
                                        <span class="text-green-600 font-medium">- KES <?php echo number_format($_SESSION['promo_discount'], 2); ?></span>
                                    </div>
                                <?php endif; ?>
                                <div class="border-t border-gray-200 my-2"></div>
                                <div class="flex justify-between text-lg font-bold">
                                    <span>Total Amount</span>
                                    <span class="text-primary">KES <?php echo number_format($grand_total, 2); ?></span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Checkout Buttons -->
                    <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-100 sticky top-4">
                        <div class="flex flex-col sm:flex-row justify-between items-center gap-4">
                            <div class="text-center sm:text-left">
                                <p class="text-sm text-gray-600">Order Total: <span class="font-bold text-lg text-primary">KES <?php echo number_format($grand_total, 2); ?></span></p>
                                <?php if ($delivery_fee === 0): ?>
                                    <p class="text-xs text-green-600 mt-1"><i class="fas fa-check-circle mr-1"></i> Free delivery applied</p>
                                <?php endif; ?>
                            </div>
                            <div class="flex flex-col sm:flex-row gap-3 w-full sm:w-auto">
                                <a href="/cart.php" class="px-6 py-3 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-colors font-medium text-center">
                                    <i class="fas fa-arrow-left mr-2"></i> Back to Cart
                                </a>
                                <button type="submit" form="checkout-form" class="px-8 py-3 bg-primary text-white rounded-lg hover:opacity-90 transition-all font-medium shadow-md hover:shadow-lg">
                                    <i class="fas fa-lock mr-2"></i> Secure Checkout
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <script>
                // Toggle order summary
                const orderSummaryToggle = document.getElementById('order-summary-toggle');
                const orderSummaryContent = document.getElementById('order-summary-content');
                const summaryArrow = document.getElementById('summary-arrow');

                if (orderSummaryToggle && orderSummaryContent && summaryArrow) {
                    orderSummaryToggle.addEventListener('click', function() {
                        const isExpanded = orderSummaryContent.classList.toggle('hidden');
                        summaryArrow.style.transform = isExpanded ? 'rotate(0deg)' : 'rotate(180deg)';
                    });
                }

                // Payment method change handler
                const paymentMethodRadios = document.querySelectorAll('input[name="payment_method"]');
                const paymentDetails = document.getElementById('payment-details');
                const mpesaDetails = document.getElementById('mpesa-details');
                const airtelDetails = document.getElementById('airtel-details');
                const cardDetails = document.getElementById('card-details');

                paymentMethodRadios.forEach(radio => {
                    radio.addEventListener('change', function() {
                        // Hide all payment details first
                        mpesaDetails.classList.add('hidden');
                        airtelDetails.classList.add('hidden');
                        cardDetails.classList.add('hidden');

                        // Show relevant payment details based on selection
                        if (this.value === 'mpesa') {
                            mpesaDetails.classList.remove('hidden');
                            paymentDetails.classList.remove('hidden');
                        } else if (this.value === 'airtel') {
                            airtelDetails.classList.remove('hidden');
                            paymentDetails.classList.remove('hidden');
                        } else if (this.value === 'card') {
                            cardDetails.classList.remove('hidden');
                            paymentDetails.classList.remove('hidden');
                        } else {
                            paymentDetails.classList.add('hidden');
                        }
                    });
                });

                function saveCustomerInfo() {
                    const customerInfo = {
                        full_name: document.getElementById('full_name').value,
                        email: document.getElementById('email').value,
                        phone: document.getElementById('phone').value,
                        address: document.getElementById('address').value
                    };

                    localStorage.setItem('customerInfo', JSON.stringify(customerInfo));
                }

                // Add event listeners to save data as user types
                document.addEventListener('DOMContentLoaded', function() {
                    const formFields = ['full_name', 'email', 'phone', 'address'];
                    formFields.forEach(fieldId => {
                        const field = document.getElementById(fieldId);
                        if (field) {
                            field.addEventListener('input', saveCustomerInfo);
                        }
                    });
                });
            </script>
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
    const paymentMethodRadios = document.querySelectorAll('input[name="payment_method"]');
    const mpesaRadio = document.getElementById('mpesa');

    if (checkoutForm) {

        checkoutForm.addEventListener('submit', async function(e) {
            e.preventDefault();

            // Get form data
            const formData = new FormData(this);
            const paymentMethod = formData.get('payment_method');
            const submitButton = document.querySelector('button[form="checkout-form"][type="submit"]');

            if (!submitButton) {
                console.error('Submit button not found');
                return;
            }

            // Check if payment method is available/configured
            if (paymentMethod === 'mpesa' && !<?php echo $mpesa_available ? 'true' : 'false'; ?>) {
                showNotification('M-Pesa payment is not configured yet. Please configure your M-Pesa credentials in includes/config.php first.', 'error');
                return;
            }

            if (paymentMethod === 'mpesa') {
                const mpesaPhone = formData.get('mpesa_phone');
                if (!mpesaPhone || mpesaPhone.trim() === '') {
                    showNotification('Please enter your M-Pesa phone number.', 'error');
                    return;
                }

                // Comprehensive M-Pesa phone validation
                const cleanPhone = mpesaPhone.replace(/[\s\-\(\)\.]/g, '');

                // Check if it starts with valid M-Pesa prefixes
                const mpesaPrefixes = ['2547', '2541', '070', '071', '072', '079', '+2547', '+2541'];
                const isValidPrefix = mpesaPrefixes.some(prefix => cleanPhone.startsWith(prefix));

                if (!isValidPrefix) {
                    showNotification('Please enter a valid M-Pesa phone number. M-Pesa numbers typically start with 070, 071, 072, 079 or +2547, +2541.', 'error');
                    return;
                }

                // Check total length (including country code if present)
                if (cleanPhone.startsWith('+254')) {
                    if (cleanPhone.length !== 13) { // +254XXXXXXXXX
                        showNotification('Please enter a complete M-Pesa phone number with country code (+254XXXXXXXXX).', 'error');
                        return;
                    }
                } else if (cleanPhone.startsWith('254')) {
                    if (cleanPhone.length !== 12) { // 254XXXXXXXXX
                        showNotification('Please enter a complete M-Pesa phone number with country code (254XXXXXXXXX).', 'error');
                        return;
                    }
                } else {
                    if (cleanPhone.length !== 10) { // 07XXXXXXXX
                        showNotification('Please enter a complete M-Pesa phone number (07XXXXXXXX).', 'error');
                        return;
                    }
                }

                // Additional check for M-Pesa specific ranges
                const operatorCode = cleanPhone.substring(cleanPhone.length - 9, cleanPhone.length - 7);
                if (!['07', '71', '72', '79'].includes(operatorCode)) {
                    showNotification('This phone number is not registered with M-Pesa. Please use a valid M-Pesa number.', 'error');
                    return;
                }
            }

            if (paymentMethod === 'airtel') {
                const airtelPhone = formData.get('airtel_phone');
                if (!airtelPhone || airtelPhone.trim() === '') {
                    showNotification('Please enter your Airtel Money phone number.', 'error');
                    return;
                }

                // Comprehensive Airtel phone validation
                const cleanPhone = airtelPhone.replace(/[\s\-\(\)\.]/g, '');

                // Check if it starts with valid Airtel prefixes
                const airtelPrefixes = ['2547', '073', '078', '+2547'];
                const isValidPrefix = airtelPrefixes.some(prefix => cleanPhone.startsWith(prefix));

                if (!isValidPrefix) {
                    showNotification('Please enter a valid Airtel Money phone number. Airtel numbers typically start with 073, 078 or +2547.', 'error');
                    return;
                }

                // Check total length
                if (cleanPhone.startsWith('+254')) {
                    if (cleanPhone.length !== 13) { // +254XXXXXXXXX
                        showNotification('Please enter a complete Airtel Money phone number with country code (+254XXXXXXXXX).', 'error');
                        return;
                    }
                } else if (cleanPhone.startsWith('254')) {
                    if (cleanPhone.length !== 12) { // 254XXXXXXXXX
                        showNotification('Please enter a complete Airtel Money phone number with country code (254XXXXXXXXX).', 'error');
                        return;
                    }
                } else {
                    if (cleanPhone.length !== 10) { // 07XXXXXXXX
                        showNotification('Please enter a complete Airtel Money phone number (07XXXXXXXX).', 'error');
                        return;
                    }
                }
            }

            if (paymentMethod === 'card') {
                const cardNumber = formData.get('card_number');
                const expiryDate = formData.get('expiry_date');
                const cvv = formData.get('cvv');
                const cardName = formData.get('card_name');

                if (!cardNumber || cardNumber.trim() === '') {
                    showNotification('Please enter your card number.', 'error');
                    return;
                }

                // Enhanced card number validation with Luhn algorithm
                const cleanCardNumber = cardNumber.replace(/\s/g, '');
                if (cleanCardNumber.length < 13 || cleanCardNumber.length > 19) {
                    showNotification('Please enter a valid card number (13-19 digits).', 'error');
                    return;
                }

                // Luhn algorithm check
                if (!luhnCheck(cleanCardNumber)) {
                    showNotification('Please enter a valid card number. The card number you entered appears to be invalid.', 'error');
                    return;
                }

                // Enhanced expiry date validation
                // Add your expiry date validation here if needed
            } // Close the card validation if block

            const originalButtonText = submitButton.innerHTML;

            // Validate required fields
            const requiredFields = ['full_name', 'email', 'phone', 'address'];
            const missingFields = [];
            
            requiredFields.forEach(field => {
                if (!formData.get(field)?.trim()) {
                    missingFields.push(field);
                }
            });

            if (missingFields.length > 0) {
                showNotification(`Please fill in all required fields: ${missingFields.join(', ')}`, 'error');
                return;
            }

            if (paymentMethod === 'mpesa') {
                // Create an order first
                try {
                    const orderResponse = await fetch('process_order.php', {
                        method: 'POST',
                        body: formData
                    });

                    const orderResult = await orderResponse.json();

                    if (orderResult.success) {
                        // Initiate STK push with the real order ID
                        await initiateMpesaPayment(orderResult.order_id, formData, submitButton);
                    } else {
                        showNotification(orderResult.message || 'Failed to create order. Please try again.', 'error');
                    }
                } catch (error) {
                    console.error('Order creation error:', error);
                    showNotification('An error occurred. Please try again.', 'error');
                }
                return;
            } 
            // Handle other payment methods
            else {
                // For other payment methods, submit the form normally
                try {
                    submitButton.disabled = true;
                    submitButton.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Processing...';

                    const response = await fetch('process_order.php', {
                        method: 'POST',
                        body: formData
                    });

                    const result = await response.json();

                    if (result.success) {
                        // Redirect to thank you page
                        window.location.href = 'order_confirmation.php?order_id=' + result.order_id;
                    } else {
                        throw new Error(result.message || 'Failed to process your order');
                    }
                } catch (error) {
                    console.error('Form submission error:', error);
                    showNotification(error.message || 'An error occurred while processing your order. Please try again.', 'error');
                    submitButton.disabled = false;
                    submitButton.innerHTML = originalButtonText;
                }
            }
        });
    }
});

// Function to initiate M-Pesa payment after order creation
async function initiateMpesaPayment(orderId, formData, submitButton) {
    const originalButtonText = submitButton.innerHTML;

    try {
        // Get the phone number and format it
        const mpesaPhone = formData.get('mpesa_phone');
        const phoneNumber = formatMpesaPhoneNumber(mpesaPhone);

        // Get the order total (we'll use the grand_total from PHP)
        const orderAmount = '<?php echo $grand_total; ?>';

        // Prepare payment data
        const paymentData = {
            phone: phoneNumber,
            amount: orderAmount,
            order_id: orderId
        };

        // Initiate M-Pesa payment
        const paymentResponse = await fetch('initiate_stk_push.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(paymentData)
        });

        const paymentResult = await paymentResponse.json();

        if (paymentResult.success) {
            // Show success message
            showNotification('Payment request sent to your phone. Please complete the payment on your phone.', 'success');

            // Start polling for payment status
            await pollPaymentStatus(paymentResult.data.checkout_request_id, submitButton, originalButtonText);
        } else {
            throw new Error(paymentResult.message || 'Failed to initiate M-Pesa payment');
        }
    } catch (error) {
        console.error('M-Pesa payment error:', error);
        showNotification(error.message || 'An error occurred while processing your payment. Please try again.', 'error');
        submitButton.disabled = false;
        submitButton.innerHTML = originalButtonText;
    }
}

// Format phone number to M-Pesa format (2547XXXXXXXX)
function formatMpesaPhoneNumber(phone) {
    // Remove all non-digit characters
    let cleanPhone = phone.replace(/\D/g, '');
    
    // Convert to 254 format if it's a local number
    if (cleanPhone.startsWith('0')) {
        cleanPhone = '254' + cleanPhone.substring(1);
    } else if (cleanPhone.startsWith('+')) {
        cleanPhone = cleanPhone.substring(1);
    }
    
    return cleanPhone;
}

// Poll payment status
async function pollPaymentStatus(checkoutRequestId, submitButton, originalButtonText, attempts = 0) {
    const maxAttempts = 20; // Max 20 attempts (about 1 minute with 3-second intervals)
    
    if (attempts >= maxAttempts) {
        showNotification('Payment verification timed out. Please check your M-Pesa messages and refresh the page.', 'warning');
        submitButton.disabled = false;
        submitButton.innerHTML = originalButtonText;
        return;
    }

    try {
        const response = await fetch(`check_payment_status.php?checkout_request_id=${checkoutRequestId}`);
        const data = await response.json();

        if (data.success) {
            if (data.status === 'completed') {
                // Payment successful
                showNotification('Payment received! Your order is being processed.', 'success');
                // Redirect to thank you page or order confirmation
                window.location.href = 'order_confirmation.php?order_id=' + data.data.order_id;
                return;
            } else if (data.status === 'failed' || data.status === 'cancelled') {
                // Payment failed or was cancelled
                throw new Error(data.message || 'Payment was not completed');
            } else {
                // Payment still pending, poll again after delay
                setTimeout(() => {
                    pollPaymentStatus(checkoutRequestId, submitButton, originalButtonText, attempts + 1);
                }, 3000); // Poll every 3 seconds
            }
        } else {
            throw new Error(data.message || 'Error checking payment status');
        }
    } catch (error) {
        console.error('Error polling payment status:', error);
        showNotification(error.message || 'Error verifying payment status. Please refresh the page and check your order status.', 'error');
        submitButton.disabled = false;
        submitButton.innerHTML = originalButtonText;
    }
}

// Show notification function
function showNotification(message, type = 'info') {
    const notification = document.getElementById('notification');
    const notificationContent = document.getElementById('notification-content');
    const notificationMessage = document.getElementById('notification-message');
    const notificationIcon = document.getElementById('notification-icon');

    if (!notification || !notificationContent || !notificationMessage || !notificationIcon) {
        console.error('Notification elements not found');
        return;
    }

    // Set message and styles based on type
    notificationMessage.textContent = message;
    notificationContent.className = `p-4 rounded-md shadow-lg ${type === 'success' ? 'bg-green-50' : type === 'error' ? 'bg-red-50' : 'bg-blue-50'}`;
    notificationIcon.className = `flex-shrink-0 ${type === 'success' ? 'text-green-400' : type === 'error' ? 'text-red-400' : 'text-blue-400'}`;
    notificationIcon.innerHTML = type === 'success' ? '<i class="fas fa-check-circle h-5 w-5"></i>' : 
                                     type === 'error' ? '<i class="fas fa-exclamation-circle h-5 w-5"></i>' : 
                                     '<i class="fas fa-info-circle h-5 w-5"></i>';

    // Show notification
    notification.classList.remove('hidden');
    notification.classList.add('block');

    // Auto-hide after 5 seconds
    setTimeout(() => {
        notification.classList.remove('block');
        notification.classList.add('hidden');
    }, 5000);
}

// Save customer info to localStorage
function saveCustomerInfo() {
    const customerInfo = {
        full_name: document.getElementById('full_name')?.value || '',
        email: document.getElementById('email')?.value || '',
        phone: document.getElementById('phone')?.value || '',
        address: document.getElementById('address')?.value || ''
    };
    localStorage.setItem('customerInfo', JSON.stringify(customerInfo));
}

// Load saved customer info when page loads
function loadCustomerInfo() {
    // Check if we have saved customer information in localStorage
    const savedInfo = localStorage.getItem('customerInfo');
    if (savedInfo) {
        try {
            const customerInfo = JSON.parse(savedInfo);

            // Pre-populate form fields
            const fullNameField = document.getElementById('full_name');
            const emailField = document.getElementById('email');
            const phoneField = document.getElementById('phone');
            const addressField = document.getElementById('address');

            if (fullNameField && customerInfo.full_name) {
                fullNameField.value = customerInfo.full_name;
            }
            if (emailField && customerInfo.email) {
                emailField.value = customerInfo.email;
            }
            if (phoneField && customerInfo.phone) {
                phoneField.value = customerInfo.phone;
            }
            if (addressField && customerInfo.address) {
                addressField.value = customerInfo.address;
            }
        } catch (e) {
            console.error('Error loading customer info:', e);
        }
    }
}

// Save customer info to localStorage
function saveCustomerInfo() {
    const customerInfo = {
        full_name: document.getElementById('full_name')?.value || '',
        email: document.getElementById('email')?.value || '',
        phone: document.getElementById('phone')?.value || '',
        address: document.getElementById('address')?.value || ''
    };
    localStorage.setItem('customerInfo', JSON.stringify(customerInfo));
}

// Load saved customer info when page loads
loadCustomerInfo();

// Save customer info when input changes
const formFields = ['full_name', 'email', 'phone', 'address'];
formFields.forEach(fieldId => {
    const field = document.getElementById(fieldId);
    if (field) {
        field.addEventListener('input', saveCustomerInfo);
    }
});

// Card validation helper functions
function luhnCheck(cardNumber) {
    let sum = 0;
    let shouldDouble = false;

    // Loop through digits from right to left
    for (let i = cardNumber.length - 1; i >= 0; i--) {
        let digit = parseInt(cardNumber.charAt(i));

        if (shouldDouble) {
            digit *= 2;
            if (digit > 9) {
                digit -= 9;
            }
        }

        sum += digit;
        shouldDouble = !shouldDouble;
    }

    return sum % 10 === 0;
}

function getCardType(cardNumber) {
    // Remove spaces and validate length
    const cleanNumber = cardNumber.replace(/\s/g, '');

    // Visa
    if (/^4/.test(cleanNumber)) {
        return 'visa';
    }

    // Mastercard
    if (/^5[1-5]/.test(cleanNumber) || /^2[2-7]/.test(cleanNumber)) {
        return 'mastercard';
    }

    // American Express
    if (/^3[47]/.test(cleanNumber)) {
        return 'amex';
    }

    // Discover
    if (/^6(?:011|5)/.test(cleanNumber)) {
        return 'discover';
    }

    // Default/unknown
    return 'unknown';
}
</script>

<?php include 'includes/footer.php'; ?>