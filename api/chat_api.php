<?php
session_start(); // Start session for user authentication
header('Content-Type: application/json');
require_once '../includes/config.php';

$action = $_GET['action'] ?? '';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode([
        'success' => false,
        'error' => 'User not logged in'
    ]);
    exit;
}

switch ($action) {
    case 'get_conversations':
        try {
            $isAdmin = isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
            $userId = $_SESSION['user_id'] ?? null;

            if ($isAdmin) {
                // Admin can see all conversations
                $stmt = $pdo->query("
                    SELECT
                        customer_email as id,
                        customer_name,
                        customer_email,
                        status,
                        created_at as last_message_at,
                        (SELECT COUNT(*) FROM customer_messages cm2 WHERE cm2.customer_email = customer_messages.customer_email AND cm2.status IN ('unread', 'read')) as unread_count
                    FROM customer_messages
                    GROUP BY customer_email, customer_name, status, created_at
                    ORDER BY MAX(created_at) DESC
                ");
            } else {
                // Customer can only see conversations where they are the customer
                $expectedConversationId = $_SESSION['user_email'] ??
                    ($_SESSION['username'] ?? $_SESSION['user_name'] ?? 'customer') . '@example.com';

                $stmt = $pdo->prepare("
                    SELECT
                        customer_email as id,
                        customer_name,
                        customer_email,
                        status,
                        created_at as last_message_at,
                        0 as unread_count
                    FROM customer_messages
                    WHERE customer_email = ?
                    GROUP BY customer_email, customer_name, status, created_at
                    ORDER BY MAX(created_at) DESC
                ");
                $stmt->execute([$expectedConversationId]);
            }

            $conversations = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // If no conversations exist for customer, return sample data for demo
            if (!$isAdmin && empty($conversations)) {
                // Use the same logic as chat.php for determining conversation ID
                $expectedConversationId = $_SESSION['user_email'] ??
                    ($_SESSION['username'] ?? $_SESSION['user_name'] ?? 'customer') . '@example.com';

                $conversations = [
                    [
                        'id' => $expectedConversationId,
                        'customer_name' => $_SESSION['username'] ?? $_SESSION['user_name'] ?? 'Customer',
                        'customer_email' => $expectedConversationId,
                        'status' => 'active',
                        'last_message_at' => date('Y-m-d H:i:s'),
                        'unread_count' => 0
                    ]
                ];
            }

            echo json_encode([
                'success' => true,
                'conversations' => $conversations
            ]);
        } catch (PDOException $e) {
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
        break;

    case 'get_messages':
        $conversation_id = $_GET['conversation_id'] ?? '';
        $since = $_GET['since'] ?? '';

        if (empty($conversation_id)) {
            echo json_encode([
                'success' => false,
                'error' => 'Conversation ID is required'
            ]);
            exit;
        }

        try {
            $isAdmin = isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
            $userId = $_SESSION['user_id'] ?? null;

            // Verify user has access to this conversation
            if (!$isAdmin) {
                // Use the same logic as chat.php for determining conversation ID
                $expectedConversationId = $_SESSION['user_email'] ??
                    ($_SESSION['username'] ?? $_SESSION['user_name'] ?? 'customer') . '@example.com';

                if ($conversation_id !== $expectedConversationId) {
                    echo json_encode([
                        'success' => false,
                        'error' => 'Access denied to this conversation'
                    ]);
                    exit;
                }
            }

            // Build query for messages
            $sql = "SELECT * FROM customer_messages WHERE customer_email = ?";
            $params = [$conversation_id];

            if (!empty($since)) {
                $sql .= " AND created_at > ?";
                $params[] = $since;
            }

            $sql .= " ORDER BY created_at ASC";

            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Format messages for frontend
            $formattedMessages = [];
            foreach ($messages as $msg) {
                $formattedMessages[] = [
                    'id' => $msg['id'],
                    'conversation_id' => $msg['customer_email'],
                    'sender_name' => $msg['customer_name'],
                    'sender_type' => 'customer',
                    'message' => $msg['message'],
                    'is_read' => in_array($msg['status'], ['read', 'replied', 'resolved']),
                    'created_at' => $msg['created_at'],
                    'updated_at' => $msg['updated_at'],
                    'subject' => $msg['subject'],
                    'status' => $msg['status']
                ];

                // Add admin response as separate message if exists
                if (!empty($msg['response'])) {
                    $formattedMessages[] = [
                        'id' => $msg['id'] . '_response',
                        'conversation_id' => $msg['customer_email'],
                        'sender_name' => 'Support Team',
                        'sender_type' => 'admin',
                        'message' => $msg['response'],
                        'is_read' => true,
                        'created_at' => $msg['updated_at'],
                        'response_to' => $msg['id']
                    ];
                }
            }

            echo json_encode([
                'success' => true,
                'messages' => $formattedMessages
            ]);
        } catch (PDOException $e) {
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
        break;

    case 'send_message':
        $conversation_id = $_POST['conversation_id'] ?? '';
        $message = trim($_POST['message'] ?? '');
        $sender_name = $_POST['sender_name'] ?? 'Anonymous';
        $sender_type = $_POST['sender_type'] ?? 'customer';
        $subject = $_POST['subject'] ?? 'Chat Message';

        if (empty($message) || empty($conversation_id)) {
            echo json_encode([
                'success' => false,
                'error' => 'Message and conversation ID are required'
            ]);
            exit;
        }

        try {
            $isAdmin = isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
            $userId = $_SESSION['user_id'] ?? null;

            // Verify user has access to this conversation
            if (!$isAdmin) {
                // Use the same logic as chat.php for determining conversation ID
                $expectedConversationId = $_SESSION['user_email'] ??
                    ($_SESSION['username'] ?? $_SESSION['user_name'] ?? 'customer') . '@example.com';

                if ($conversation_id !== $expectedConversationId) {
                    echo json_encode([
                        'success' => false,
                        'error' => 'Access denied to this conversation'
                    ]);
                    exit;
                }
            }

            // Insert message
            $stmt = $pdo->prepare("
                INSERT INTO customer_messages (customer_name, customer_email, subject, message, status, created_at, updated_at)
                VALUES (?, ?, ?, ?, 'unread', NOW(), NOW())
            ");
            $stmt->execute([$sender_name, $conversation_id, $subject, $message]);

            echo json_encode([
                'success' => true,
                'message' => 'Message sent successfully',
                'conversation_id' => $conversation_id
            ]);
        } catch (PDOException $e) {
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
        break;

    case 'mark_as_read':
        $message_id = (int)$_POST['message_id'];

        try {
            $stmt = $pdo->prepare("UPDATE customer_messages SET status = 'read' WHERE id = ?");
            $stmt->execute([$message_id]);

            echo json_encode([
                'success' => true,
                'message' => 'Message marked as read'
            ]);
        } catch (PDOException $e) {
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
        break;

    default:
        echo json_encode([
            'success' => false,
            'error' => 'Invalid action'
        ]);
}
?>
