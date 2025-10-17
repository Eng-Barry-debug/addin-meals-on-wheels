<?php
session_start();
header('Content-Type: application/json');
require_once '../includes/config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode([
        'success' => false,
        'error' => 'User not logged in'
    ]);
    exit;
}

$action = $_GET['action'] ?? '';

switch ($action) {
    case 'get_dashboard_stats':
        try {
            $userId = $_SESSION['user_id'];
            $userEmail = $_SESSION['user_email'] ?? ($_SESSION['username'] ?? $_SESSION['user_name'] ?? 'customer') . '@example.com';

            // Get total orders count (assuming there's an orders table)
            $ordersCount = 0;
            try {
                $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM orders WHERE customer_id = ? OR customer_email = ?");
                $stmt->execute([$userId, $userEmail]);
                $ordersCount = (int)$stmt->fetch(PDO::FETCH_ASSOC)['count'];
            } catch (PDOException $e) {
                // If orders table doesn't exist, keep count as 0
                $ordersCount = 0;
            }

            // Get unread messages count
            $unreadMessagesCount = 0;
            try {
                $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM customer_messages WHERE customer_email = ? AND status = 'unread'");
                $stmt->execute([$userEmail]);
                $unreadMessagesCount = (int)$stmt->fetch(PDO::FETCH_ASSOC)['count'];
            } catch (PDOException $e) {
                $unreadMessagesCount = 0;
            }

            // Get wishlist/saved items count (assuming there's a wishlist table)
            $wishlistCount = 0;
            try {
                $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM wishlist WHERE user_id = ?");
                $stmt->execute([$userId]);
                $wishlistCount = (int)$stmt->fetch(PDO::FETCH_ASSOC)['count'];
            } catch (PDOException $e) {
                // If wishlist table doesn't exist, keep count as 0
                $wishlistCount = 0;
            }

            echo json_encode([
                'success' => true,
                'stats' => [
                    'total_orders' => $ordersCount,
                    'unread_messages' => $unreadMessagesCount,
                    'saved_items' => $wishlistCount
                ]
            ]);

        } catch (PDOException $e) {
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
        break;

    case 'get_recent_activity':
        try {
            $userId = $_SESSION['user_id'];
            $userEmail = $_SESSION['user_email'] ?? ($_SESSION['username'] ?? $_SESSION['user_name'] ?? 'customer') . '@example.com';

            // Get recent orders (last 5)
            $recentOrders = [];
            try {
                $stmt = $pdo->prepare("
                    SELECT id, order_number, status, total_amount, created_at
                    FROM orders
                    WHERE customer_id = ? OR customer_email = ?
                    ORDER BY created_at DESC
                    LIMIT 5
                ");
                $stmt->execute([$userId, $userEmail]);
                $recentOrders = $stmt->fetchAll(PDO::FETCH_ASSOC);
            } catch (PDOException $e) {
                $recentOrders = [];
            }

            // Get recent messages (last 5)
            $recentMessages = [];
            try {
                $stmt = $pdo->prepare("
                    SELECT id, subject, status, created_at
                    FROM customer_messages
                    WHERE customer_email = ?
                    ORDER BY created_at DESC
                    LIMIT 5
                ");
                $stmt->execute([$userEmail]);
                $recentMessages = $stmt->fetchAll(PDO::FETCH_ASSOC);
            } catch (PDOException $e) {
                $recentMessages = [];
            }

            // Get recent wishlist activity (last 5)
            $recentWishlist = [];
            try {
                $stmt = $pdo->prepare("
                    SELECT id, product_name, created_at
                    FROM wishlist
                    WHERE user_id = ?
                    ORDER BY created_at DESC
                    LIMIT 5
                ");
                $stmt->execute([$userId]);
                $recentWishlist = $stmt->fetchAll(PDO::FETCH_ASSOC);
            } catch (PDOException $e) {
                $recentWishlist = [];
            }

            // Combine and sort all activities by date
            $allActivities = [];

            foreach ($recentOrders as $order) {
                $allActivities[] = [
                    'type' => 'order',
                    'id' => $order['id'],
                    'title' => 'Order #' . ($order['order_number'] ?? $order['id']) . ' - ' . ucfirst($order['status'] ?? 'pending'),
                    'description' => 'Order placed for $' . number_format($order['total_amount'] ?? 0, 2),
                    'icon' => 'fas fa-shopping-cart',
                    'color' => 'blue',
                    'created_at' => $order['created_at']
                ];
            }

            foreach ($recentMessages as $message) {
                $allActivities[] = [
                    'type' => 'message',
                    'id' => $message['id'],
                    'title' => 'New message: ' . $message['subject'],
                    'description' => ucfirst($message['status'] ?? 'unread') . ' message',
                    'icon' => 'fas fa-envelope',
                    'color' => 'green',
                    'created_at' => $message['created_at']
                ];
            }

            foreach ($recentWishlist as $item) {
                $allActivities[] = [
                    'type' => 'wishlist',
                    'id' => $item['id'],
                    'title' => 'Added to favorites',
                    'description' => $item['product_name'] ?? 'Item added to wishlist',
                    'icon' => 'fas fa-heart',
                    'color' => 'red',
                    'created_at' => $item['created_at']
                ];
            }

            // Sort by creation date (newest first)
            usort($allActivities, function($a, $b) {
                return strtotime($b['created_at']) - strtotime($a['created_at']);
            });

            // Return only the latest 5 activities
            $recentActivities = array_slice($allActivities, 0, 5);

            echo json_encode([
                'success' => true,
                'activities' => $recentActivities
            ]);

        } catch (PDOException $e) {
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
        break;

    case 'mark_messages_read':
        try {
            $userEmail = $_SESSION['user_email'] ?? ($_SESSION['username'] ?? $_SESSION['user_name'] ?? 'customer') . '@example.com';

            // Mark all unread messages as read for this user
            $stmt = $pdo->prepare("UPDATE customer_messages SET status = 'read' WHERE customer_email = ? AND status = 'unread'");
            $stmt->execute([$userEmail]);

            $markedCount = $stmt->rowCount();

            echo json_encode([
                'success' => true,
                'message' => "Marked $markedCount messages as read",
                'marked_count' => $markedCount
            ]);

        } catch (PDOException $e) {
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    case 'get_popular_items':
        try {
            // Get popular items based on multiple factors:
            // 1. Items with most orders in the last 7 days
            // 2. Items with highest average ratings
            // 3. Featured items get bonus points

            $seven_days_ago = date('Y-m-d H:i:s', strtotime('-7 days'));

            $stmt = $pdo->prepare("
                SELECT
                    m.id,
                    m.name,
                    m.price,
                    m.image,
                    m.description,
                    COUNT(DISTINCT o.id) as order_count,
                    AVG(r.rating) as avg_rating,
                    (m.is_featured * 2) as featured_bonus
                FROM menu_items m
                LEFT JOIN orders o ON (
                    (o.customer_id = ? OR o.customer_email LIKE ?)
                    AND o.created_at > ?
                    AND o.status IN ('delivered', 'completed')
                )
                LEFT JOIN reviews r ON m.id = r.menu_item_id AND r.status = 'approved'
                WHERE m.status = 'active'
                GROUP BY m.id
                ORDER BY (
                    (COUNT(DISTINCT o.id) * 3) +
                    (AVG(r.rating) * 2) +
                    (m.is_featured * 5) +
                    featured_bonus
                ) DESC, m.created_at DESC
                LIMIT 6
            ");

            $user_email_pattern = $_SESSION['user_email'] ?? ($_SESSION['username'] ?? $_SESSION['user_name'] ?? 'customer') . '@%';
            $stmt->execute([$_SESSION['user_id'], $user_email_pattern, $seven_days_ago]);
            $popular_items = $stmt->fetchAll(PDO::FETCH_ASSOC);

            echo json_encode([
                'success' => true,
                'items' => $popular_items
            ]);

        } catch (PDOException $e) {
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
        break;
}
