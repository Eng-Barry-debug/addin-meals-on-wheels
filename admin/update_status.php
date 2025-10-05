<?php
session_start();
require_once '../includes/config.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

// Validate required fields
if (!isset($_POST['id'], $_POST['type'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit();
}

$id = (int)$_POST['id'];
$type = $_POST['type'];

try {
    // Update status in the appropriate table
    $table = $type === 'menu_item' ? 'menu_items' : ($type === 'category' ? 'categories' : '');
    
    if (empty($table)) {
        throw new Exception('Invalid type');
    }

    // Handle status update
    if (isset($_POST['status'])) {
        $status = $_POST['status'] === 'active' ? 'active' : 'inactive';
        $stmt = $pdo->prepare("UPDATE $table SET status = ?, updated_at = NOW() WHERE id = ?");
        $stmt->execute([$status, $id]);
    }
    // Handle featured status update
    elseif (isset($_POST['is_featured'])) {
        $is_featured = $_POST['is_featured'] === '1' ? 1 : 0;
        $stmt = $pdo->prepare("UPDATE $table SET is_featured = ?, updated_at = NOW() WHERE id = ?");
        $stmt->execute([$is_featured, $id]);
    }
    else {
        throw new Exception('No update action specified');
    }
    
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}