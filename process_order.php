<?php
// Set content type to JSON first thing
header('Content-Type: application/json');

// Start output buffering to catch any unexpected output
ob_start();

try {
    session_start();
    require_once 'includes/config.php';
    require_once 'includes/functions.php';
    
    // Check if user is logged in
    if (!isset($_SESSION['user_id'])) {
        throw new Exception('Please log in to place an order');
    }
    
    // Validate request method
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method');
    }
    
    // Get and validate form data
    $required_fields = ['full_name', 'email', 'phone', 'address', 'payment_method'];
    foreach ($required_fields as $field) {
        if (empty($_POST[$field])) {
            throw new Exception("Please fill in all required fields: " . implode(', ', $required_fields));
        }
    }
    
    $full_name = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $address = trim($_POST['address']);
    $instructions = isset($_POST['instructions']) ? trim($_POST['instructions']) : '';
    $payment_method = $_POST['payment_method'];
    
    // Validate email
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new Exception('Please enter a valid email address');
    }
    
    // Initialize cart and get items
    require_once 'includes/Cart.php';
    $cart = new Cart($pdo);
    $cartItems = $cart->getItems();
    
    if (empty($cartItems)) {
        throw new Exception('Your cart is empty');
    }
    
    // Calculate totals
    $subtotal = 0;
    $menuItemIds = array_column($cartItems, 'menu_item_id');
    $placeholders = rtrim(str_repeat('?,', count($menuItemIds)), ',');
    
    $stmt = $pdo->prepare("SELECT id, price FROM menu_items WHERE id IN ($placeholders)");
    $stmt->execute($menuItemIds);
    $menuItems = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($cartItems as $cartItem) {
        foreach ($menuItems as $menuItem) {
            if ($menuItem['id'] == $cartItem['menu_item_id']) {
                $subtotal += $menuItem['price'] * $cartItem['quantity'];
                break;
            }
        }
    }
    
    $delivery_fee = $subtotal >= 1500 ? 0 : 200;
    $grand_total = $subtotal + $delivery_fee;
    
    // Apply promo discount if available
    $promo_discount = isset($_SESSION['promo_discount']) ? $_SESSION['promo_discount'] : 0;
    $grand_total = max(0, $grand_total - $promo_discount);
    
    // Start transaction
    $pdo->beginTransaction();
    
    try {
        // Insert order
        $stmt = $pdo->prepare("
            INSERT INTO orders (user_id, full_name, email, phone, address, instructions, subtotal, delivery_fee, discount, total_amount, payment_method, status) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending')
        ");
        $stmt->execute([
            $_SESSION['user_id'],
            $full_name,
            $email,
            $phone,
            $address,
            $instructions,
            $subtotal,
            $delivery_fee,
            $promo_discount,
            $grand_total,
            $payment_method
        ]);
        
        $order_id = $pdo->lastInsertId();
        
        // Insert order items
        $stmt = $pdo->prepare("
            INSERT INTO order_items (order_id, menu_item_id, quantity, price) 
            VALUES (?, ?, ?, ?)
        ");
        
        foreach ($cartItems as $cartItem) {
            foreach ($menuItems as $menuItem) {
                if ($menuItem['id'] == $cartItem['menu_item_id']) {
                    $stmt->execute([
                        $order_id,
                        $menuItem['id'],
                        $cartItem['quantity'],
                        $menuItem['price']
                    ]);
                    break;
                }
            }
        }
        
        // Clear cart
        $cart->clear();
        
        // Clear promo discount if used
        if (isset($_SESSION['promo_discount'])) {
            unset($_SESSION['promo_discount']);
        }
        
        // Commit transaction
        $pdo->commit();
        
        // Clear any output buffer and return success
        ob_clean();
        echo json_encode([
            'success' => true,
            'order_id' => $order_id,
            'message' => 'Order placed successfully'
        ]);
        exit;
        
    } catch (Exception $e) {
        $pdo->rollBack();
        throw new Exception('Failed to create order: ' . $e->getMessage());
    }
    
} catch (Exception $e) {
    // Clear output buffer and return error
    ob_clean();
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
    exit;
}
?>