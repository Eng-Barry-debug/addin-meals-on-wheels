<?php
// Start session and check admin authentication
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include database connection
require_once '../includes/config.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}

// Check if ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['message'] = ['type' => 'error', 'text' => 'Invalid menu item ID'];
    header('Location: menu.php');
    exit();
}

$id = (int)$_GET['id'];

try {
    // Get the menu item to delete its image
    $stmt = $pdo->prepare("SELECT image FROM menu_items WHERE id = ?");
    $stmt->execute([$id]);
    $menu_item = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$menu_item) {
        $_SESSION['message'] = ['type' => 'error', 'text' => 'Menu item not found'];
        header('Location: menu.php');
        exit();
    }
    
    // Start transaction
    $pdo->beginTransaction();
    
    // Delete the menu item
    $stmt = $pdo->prepare("DELETE FROM menu_items WHERE id = ?");
    $stmt->execute([$id]);
    
    // If the menu item had an image, delete it
    if (!empty($menu_item['image'])) {
        $image_path = '../uploads/menu/' . $menu_item['image'];
        if (file_exists($image_path)) {
            unlink($image_path);
        }
    }
    
    $pdo->commit();
    
    $_SESSION['message'] = ['type' => 'success', 'text' => 'Menu item deleted successfully'];
    
} catch (Exception $e) {
    $pdo->rollBack();
    $_SESSION['message'] = ['type' => 'error', 'text' => 'Error deleting menu item: ' . $e->getMessage()];
}

header('Location: menu.php');
exit();
?>
