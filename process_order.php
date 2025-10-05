<?php
session_start();
require_once 'includes/config.php';
require_once 'includes/Cart.php';

// Set content type to JSON
header('Content-Type: application/json');

// Check if the request is AJAX
$is_ajax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
            strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';

// Initialize response array
$response = [
    'success' => false,
    'message' => '',
    'order_id' => null,
    'redirect' => ''
];

try {
    // Check if user is logged in
    if (!isset($_SESSION['user_id'])) {
        throw new Exception('Please log in to complete your order.');
    }

    // Get user ID from session
    $user_id = $_SESSION['user_id'];
    
    // Get cart instance
    $cart = new Cart($pdo);
    $cartItems = $cart->getItems();
    
    // Check if cart is empty
    if (empty($cartItems)) {
        throw new Exception('Your cart is empty. Please add items before checking out.');
    }

    // Validate form data
    $required_fields = ['full_name', 'email', 'phone', 'address'];
    $missing_fields = [];
    $order_data = [];

    foreach ($required_fields as $field) {
        if (empty(trim($_POST[$field] ?? ''))) {
            $missing_fields[] = str_replace('_', ' ', ucfirst($field));
        } else {
            $order_data[$field] = trim($_POST[$field]);
        }
    }

    if (!empty($missing_fields)) {
        throw new Exception('Please fill in all required fields: ' . implode(', ', $missing_fields));
    }

    // Validate email
    if (!filter_var($order_data['email'], FILTER_VALIDATE_EMAIL)) {
        throw new Exception('Please enter a valid email address.');
    }

    // Get payment method
    $payment_method = $_POST['payment_method'] ?? 'cash_on_delivery';
    $valid_payment_methods = ['cash_on_delivery', 'mpesa'];
    
    if (!in_array($payment_method, $valid_payment_methods)) {
        $payment_method = 'cash_on_delivery';
    }

    // Get delivery instructions
    $delivery_instructions = trim($_POST['instructions'] ?? '');
    
    // Calculate totals
    $subtotal = 0;
    $order_items = [];
    
    // Get menu items details
    $menuItemIds = array_column($cartItems, 'menu_item_id');
    $placeholders = rtrim(str_repeat('?,', count($menuItemIds)), ',');
    $stmt = $pdo->prepare("SELECT * FROM menu_items WHERE id IN ($placeholders)");
    $stmt->execute($menuItemIds);
    $menuItems = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Prepare order items and calculate subtotal
    foreach ($cartItems as $item) {
        foreach ($menuItems as $menuItem) {
            if ($menuItem['id'] == $item['menu_item_id']) {
                $item_total = $menuItem['price'] * $item['quantity'];
                $subtotal += $item_total;
                
                $order_items[] = [
                    'menu_item_id' => $menuItem['id'],
                    'name' => $menuItem['name'],
                    'price' => $menuItem['price'],
                    'quantity' => $item['quantity'],
                    'total' => $item_total
                ];
                break;
            }
        }
    }

    // Calculate delivery fee (free for orders over 1500)
    $delivery_fee = $subtotal >= 1500 ? 0 : 200;
    $total = $subtotal + $delivery_fee;

    // Start transaction
    $pdo->beginTransaction();

    try {
        // Insert order
        $stmt = $pdo->prepare("
            INSERT INTO orders (
                user_id, 
                order_number, 
                customer_name, 
                customer_email, 
                customer_phone, 
                delivery_address, 
                delivery_instructions, 
                subtotal, 
                delivery_fee, 
                total, 
                payment_method, 
                status
            ) VALUES (
                :user_id, 
                :order_number, 
                :customer_name, 
                :customer_email, 
                :customer_phone, 
                :delivery_address, 
                :delivery_instructions, 
                :subtotal, 
                :delivery_fee, 
                :total, 
                :payment_method, 
                'pending'
            )
        ");

        // Generate order number (format: ORD-YYYYMMDD-XXXX)
        $order_number = 'ORD-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -4));
        
        $stmt->execute([
            ':user_id' => $user_id,
            ':order_number' => $order_number,
            ':customer_name' => $order_data['full_name'],
            ':customer_email' => $order_data['email'],
            ':customer_phone' => $order_data['phone'],
            ':delivery_address' => $order_data['address'],
            ':delivery_instructions' => $delivery_instructions,
            ':subtotal' => $subtotal,
            ':delivery_fee' => $delivery_fee,
            ':total' => $total,
            ':payment_method' => $payment_method
        ]);

        $order_id = $pdo->lastInsertId();

        // Insert order items
        $stmt = $pdo->prepare("
            INSERT INTO order_items (
                order_id, 
                menu_item_id, 
                item_name, 
                quantity, 
                price, 
                total
            ) VALUES (
                :order_id, 
                :menu_item_id, 
                :item_name, 
                :quantity, 
                :price, 
                :total
            )
        ");

        foreach ($order_items as $item) {
            $stmt->execute([
                ':order_id' => $order_id,
                ':menu_item_id' => $item['menu_item_id'],
                ':item_name' => $item['name'],
                ':quantity' => $item['quantity'],
                ':price' => $item['price'],
                ':total' => $item['total']
            ]);
        }

        // Clear the cart
        $cart->clear();

        // Commit transaction
        $pdo->commit();

        // Process payment if M-Pesa
        if ($payment_method === 'mpesa') {
            // TODO: Implement M-Pesa payment processing
            // This would involve calling the M-Pesa API
            // For now, we'll just update the order status to 'processing'
            $stmt = $pdo->prepare("UPDATE orders SET status = 'processing' WHERE id = ?");
            $stmt->execute([$order_id]);
        }

        // Set success response
        $response = [
            'success' => true,
            'message' => 'Your order has been placed successfully!',
            'order_id' => $order_id,
            'order_number' => $order_number,
            'redirect' => 'order-confirmation.php?order_id=' . $order_id
        ];

    } catch (Exception $e) {
        // Rollback transaction on error
        $pdo->rollBack();
        throw $e;
    }

} catch (Exception $e) {
    // Set error response
    $response = [
        'success' => false,
        'message' => $e->getMessage(),
        'order_id' => null,
        'redirect' => ''
    ];
    
    // Log the error
    error_log('Order processing error: ' . $e->getMessage());
}

// Return JSON response
echo json_encode($response);

// If this is not an AJAX request, redirect appropriately
if (!$is_ajax) {
    if ($response['success'] && !empty($response['redirect'])) {
        header('Location: ' . $response['redirect']);
    } else {
        // Store error message in session and redirect back to checkout
        $_SESSION['checkout_error'] = $response['message'];
        header('Location: checkout.php');
    }
    exit;
}
