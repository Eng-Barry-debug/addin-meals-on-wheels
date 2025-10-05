<?php
session_start();
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/Cart.php';

header('Content-Type: application/json');

try {
    $cart = new Cart($pdo);
    echo json_encode([
        'success' => true,
        'count' => $cart->getTotalItems()
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Failed to get cart count'
    ]);
}
?>
