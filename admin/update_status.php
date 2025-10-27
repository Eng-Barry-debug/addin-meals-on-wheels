<?php
session_start();
require_once '../includes/config.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

// Handle GET requests for order status updates (from Process/Deliver buttons)
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['id'], $_GET['status'])) {
    $id = (int)$_GET['id'];
    $status = $_GET['status'];

    // Validate status
    $valid_statuses = ['pending', 'processing', 'shipped', 'delivered', 'cancelled'];
    if (!in_array($status, $valid_statuses)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid status']);
        exit();
    }

    try {
        // Update order status
        $stmt = $pdo->prepare("UPDATE orders SET status = ?, updated_at = NOW() WHERE id = ?");
        $result = $stmt->execute([$status, $id]);

        if ($result) {
            // Log activity
            error_log("Order #$id status changed to $status by admin");

            // Check if this is an AJAX request
            $isAjax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest';

            if ($isAjax) {
                // Return JSON for AJAX requests
                header('Content-Type: application/json');
                echo json_encode(['success' => true, 'message' => "Order status updated to " . ucfirst($status)]);
                exit();
            } else {
                // Redirect back to orders page with success message for direct browser requests
                $_SESSION['message'] = ['type' => 'success', 'text' => "Order status updated to " . ucfirst($status)];
                header('Location: orders.php');
                exit();
            }
        } else {
            // Check if this is an AJAX request
            $isAjax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest';

            if ($isAjax) {
                // Return JSON error for AJAX requests
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'Failed to update order status']);
                exit();
            } else {
                // Redirect with error for direct browser requests
                $_SESSION['message'] = ['type' => 'error', 'text' => 'Failed to update order status'];
                header('Location: orders.php');
                exit();
            }
        }
    } catch (PDOException $e) {
        error_log("Error updating order status: " . $e->getMessage());

        // Check if this is an AJAX request
        $isAjax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest';

        if ($isAjax) {
            // Return JSON error for AJAX requests
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Database error occurred']);
            exit();
        } else {
            // Redirect with error for direct browser requests
            $_SESSION['message'] = ['type' => 'error', 'text' => 'Database error occurred'];
            header('Location: orders.php');
            exit();
        }
    }
}

// Handle POST requests for other status updates (existing functionality)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
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
}
?>