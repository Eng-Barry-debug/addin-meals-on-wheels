<?php
// Prevent any output before headers
while (ob_get_level() > 0) {
    ob_end_clean();
}
ob_start();

// Set error handling
ini_set('display_errors', 0);
ini_set('log_errors', 1);
error_reporting(E_ALL);

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Function to log errors to a file
function logError($message) {
    $logFile = __DIR__ . '/../debug.log';
    $logMessage = '[' . date('Y-m-d H:i:s') . '] ' . $message . PHP_EOL;
    @file_put_contents($logFile, $logMessage, FILE_APPEND);
}

// Set error handler to catch all errors
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    logError("Error [$errno] $errstr in $errfile on line $errline");
    return true; // Don't execute PHP internal error handler
}, E_ALL);

// Set exception handler
set_exception_handler(function($e) {
    logError("Uncaught exception: " . $e->getMessage() . " in " . $e->getFile() . " on line " . $e->getLine());
    sendJsonResponse([
        'success' => false,
        'message' => 'An error occurred while processing your request',
        'error' => (defined('ENVIRONMENT') && ENVIRONMENT === 'development') ? $e->getMessage() : null
    ], 500);
});

// Set JSON content type header
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache, must-revalidate');
header('X-Content-Type-Options: nosniff');

// Function to send JSON response and exit
function sendJsonResponse($data, $statusCode = 200) {
    try {
        // Clear any previous output
        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        // Set status code and headers
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');

        // Convert data to JSON with error handling
        $json = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        // Check for JSON encoding errors
        if ($json === false) {
            throw new Exception('JSON Encode Error: ' . json_last_error_msg());
        }

        // Output the JSON
        echo $json;
    } catch (Exception $e) {
        // Log the error
        logError("sendJsonResponse Error: " . $e->getMessage());
        
        // Fallback to simple error response
        header('Content-Type: application/json');
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'An error occurred while processing your request',
            'error' => (defined('ENVIRONMENT') && ENVIRONMENT === 'development') ? $e->getMessage() : null
        ]);
    }
    exit;
}

// Function to update post like count
function updatePostLikeCount($pdo, $postId, $increment) {
    try {
        $pdo->beginTransaction();

        // Update like count
        $stmt = $pdo->prepare("UPDATE blog_posts SET like_count = like_count + ? WHERE id = ?");
        $stmt->execute([$increment, $postId]);

        // Get updated like count
        $stmt = $pdo->prepare("SELECT like_count FROM blog_posts WHERE id = ?");
        $stmt->execute([$postId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        $pdo->commit();

        return $result ? (int)$result['like_count'] : 0;
    } catch (Exception $e) {
        $pdo->rollBack();
        throw $e;
    }
}

try {
    // Include config file
    $configPath = __DIR__ . '/../includes/config.php';
    if (!file_exists($configPath)) {
        throw new Exception('Configuration file not found');
    }
    require_once $configPath;

    // Check if PDO is available
    if (!isset($pdo) || !($pdo instanceof PDO)) {
        throw new Exception('Database connection not available');
    }

    // Get request method and action
    $requestMethod = $_SERVER['REQUEST_METHOD'];
    $action = $_GET['action'] ?? $_POST['action'] ?? '';
    $userId = $_SESSION['user_id'] ?? null;
    $isLoggedIn = !empty($userId);

    // Handle different actions
    switch ($action) {
        case 'like':
            if ($requestMethod !== 'POST') {
                sendJsonResponse(['success' => false, 'message' => 'Invalid request method'], 405);
            }

            if (!$isLoggedIn) {
                sendJsonResponse([
                    'success' => false,
                    'message' => 'Please login to like posts',
                    'redirect' => '/auth/login.php?redirect=' . urlencode($_SERVER['HTTP_REFERER'] ?? '/')
                ], 401);
            }

            $postId = filter_input(INPUT_POST, 'post_id', FILTER_VALIDATE_INT);

            if (!$postId) {
                sendJsonResponse(['success' => false, 'message' => 'Invalid post ID'], 400);
            }

            // Check if user already liked the post
            $stmt = $pdo->prepare("SELECT id FROM blog_likes WHERE post_id = ? AND user_id = ?");
            $stmt->execute([$postId, $userId]);
            $alreadyLiked = $stmt->rowCount() > 0;

            if (!$alreadyLiked) {
                // Add like
                $stmt = $pdo->prepare("INSERT INTO blog_likes (post_id, user_id) VALUES (?, ?)");
                $stmt->execute([$postId, $userId]);
                $likeCount = updatePostLikeCount($pdo, $postId, 1);

                sendJsonResponse([
                    'success' => true,
                    'liked' => true,
                    'like_count' => $likeCount,
                    'message' => 'Post liked successfully'
                ]);
            } else {
                // Remove like
                $stmt = $pdo->prepare("DELETE FROM blog_likes WHERE post_id = ? AND user_id = ?");
                $stmt->execute([$postId, $userId]);
                $likeCount = updatePostLikeCount($pdo, $postId, -1);

                sendJsonResponse([
                    'success' => true,
                    'liked' => false,
                    'like_count' => $likeCount,
                    'message' => 'Like removed successfully'
                ]);
            }
            break;

        case 'comment':
            if ($requestMethod !== 'POST') {
                sendJsonResponse(['success' => false, 'message' => 'Invalid request method'], 405);
            }

            if (!$isLoggedIn) {
                sendJsonResponse([
                    'success' => false,
                    'message' => 'Please login to post comments',
                    'redirect' => '/auth/login.php?redirect=' . urlencode($_SERVER['HTTP_REFERER'] ?? '/')
                ], 401);
            }

            $postId = filter_input(INPUT_POST, 'post_id', FILTER_VALIDATE_INT);
            $comment = trim($_POST['content'] ?? '');
            
            // Sanitize the comment
            $comment = htmlspecialchars($comment, ENT_QUOTES, 'UTF-8');

            if (!$postId) {
                sendJsonResponse(['success' => false, 'message' => 'Invalid post ID'], 400);
            }

            if (empty($comment)) {
                sendJsonResponse(['success' => false, 'message' => 'Comment cannot be empty'], 400);
            }

            try {
                // Start transaction
                $pdo->beginTransaction();

                // Insert comment - using 'content' instead of 'comment' as the column name
                $stmt = $pdo->prepare("INSERT INTO blog_comments (post_id, user_id, content, created_at) VALUES (?, ?, ?, NOW())");
                $stmt->execute([$postId, $userId, $comment]);
                $commentId = $pdo->lastInsertId();

                // Update comment count
                $stmt = $pdo->prepare("UPDATE blog_posts SET comment_count = COALESCE(comment_count, 0) + 1 WHERE id = ?");
                $stmt->execute([$postId]);

                // Get updated comment count and user info
                $stmt = $pdo->prepare("
                    SELECT p.comment_count, u.name as username
                    FROM blog_posts p
                    LEFT JOIN users u ON u.id = ?
                    WHERE p.id = ?
                ");
                $stmt->execute([$userId, $postId]);
                $result = $stmt->fetch(PDO::FETCH_ASSOC);
                
                // Commit the transaction
                $pdo->commit();

                // Generate HTML for the new comment
                $username = htmlspecialchars($result['username'] ?? 'User');
                $initial = strtoupper(substr($username, 0, 1));
                $date = date('M j, Y');
                $content = htmlspecialchars($comment);
                
                $commentHtml = "
                    <div class='bg-gray-50 p-4 rounded-lg' id='comment-{$commentId}'>
                        <div class='flex items-start space-x-3'>
                            <div class='w-10 h-10 rounded-full bg-gradient-to-br from-primary to-secondary flex items-center justify-center text-white font-bold flex-shrink-0'>
                                {$initial}
                            </div>
                            <div class='flex-1'>
                                <div class='flex items-center justify-between'>
                                    <h5 class='font-semibold text-gray-900'>{$username}</h5>
                                    <span class='text-sm text-gray-500'>{$date}</span>
                                </div>
                                <p class='text-gray-700 mt-2'>{$content}</p>
                            </div>
                        </div>
                    </div>
                ";

                sendJsonResponse([
                    'success' => true,
                    'comment_id' => $commentId,
                    'comment_html' => $commentHtml,
                    'comment_count' => $result ? (int)$result['comment_count'] : 1,
                    'message' => 'Comment posted successfully'
                ]);
            } catch (Exception $e) {
                // Rollback transaction on error
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                throw $e; // Re-throw to be caught by the global handler
            }
            break;

        case 'get_comments':
            if ($requestMethod !== 'GET') {
                sendJsonResponse(['success' => false, 'message' => 'Invalid request method'], 405);
            }

            $postId = filter_input(INPUT_GET, 'post_id', FILTER_VALIDATE_INT);
            if (!$postId) {
                sendJsonResponse(['success' => false, 'message' => 'Invalid post ID'], 400);
            }

            // Get comments with user info
            $stmt = $pdo->prepare("
                SELECT c.*, u.name as username
                FROM blog_comments c
                LEFT JOIN users u ON c.user_id = u.id
                WHERE c.post_id = ?
                ORDER BY c.created_at DESC");

            $stmt->execute([$postId]);
            $comments = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Log the comments for debugging
            logError('Fetched comments: ' . print_r($comments, true));

            // Generate HTML for comments
            $html = '';
            if (empty($comments)) {
                $html = '<p class="text-gray-500 text-center py-8">No comments yet. Be the first to comment!</p>';
            } else {
                foreach ($comments as $comment) {
                    $username = htmlspecialchars($comment['username'] ?? 'User');
                    $content = htmlspecialchars($comment['content']);
                    $date = date('M j, Y', strtotime($comment['created_at']));
                    $initial = strtoupper(substr($username, 0, 1));

                    $html .= "
                        <div class='bg-gray-50 p-4 rounded-lg'>
                            <div class='flex items-start space-x-3'>
                                <div class='w-10 h-10 rounded-full bg-gradient-to-br from-primary to-secondary flex items-center justify-center text-white font-bold flex-shrink-0'>
                                    {$initial}
                                </div>
                                <div class='flex-1'>
                                    <div class='flex items-center justify-between'>
                                        <h5 class='font-semibold text-gray-900'>{$username}</h5>
                                        <span class='text-sm text-gray-500'>{$date}</span>
                                    </div>
                                    <p class='text-gray-700 mt-2'>{$content}</p>
                                </div>
                            </div>
                        </div>
                    ";
                }
            }

            sendJsonResponse([
                'success' => true,
                'html' => $html
            ]);
            break;

        case 'like_comment':
            if ($requestMethod !== 'POST') {
                sendJsonResponse(['success' => false, 'message' => 'Invalid request method'], 405);
            }

            if (!$isLoggedIn) {
                sendJsonResponse([
                    'success' => false,
                    'message' => 'Please login to like comments',
                    'redirect' => '/auth/login.php?redirect=' . urlencode($_SERVER['HTTP_REFERER'] ?? '/')
                ], 401);
            }

            $commentId = filter_input(INPUT_POST, 'comment_id', FILTER_VALIDATE_INT);

            if (!$commentId) {
                sendJsonResponse(['success' => false, 'message' => 'Invalid comment ID'], 400);
            }

            // Check if user already liked the comment
            $stmt = $pdo->prepare("SELECT id FROM blog_comment_likes WHERE comment_id = ? AND user_id = ?");
            $stmt->execute([$commentId, $userId]);
            $alreadyLiked = $stmt->rowCount() > 0;

            if (!$alreadyLiked) {
                // Add like
                $stmt = $pdo->prepare("INSERT INTO blog_comment_likes (comment_id, user_id) VALUES (?, ?)");
                $stmt->execute([$commentId, $userId]);
                $likeCount = 1;
            } else {
                // Remove like
                $stmt = $pdo->prepare("DELETE FROM blog_comment_likes WHERE comment_id = ? AND user_id = ?");
                $stmt->execute([$commentId, $userId]);
                $likeCount = 0;
            }

            // Get updated like count
            $stmt = $pdo->prepare("SELECT COUNT(*) as like_count FROM blog_comment_likes WHERE comment_id = ?");
            $stmt->execute([$commentId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $likeCount = (int)$result['like_count'];

            sendJsonResponse([
                'success' => true,
                'liked' => !$alreadyLiked,
                'like_count' => $likeCount,
                'message' => !$alreadyLiked ? 'Comment liked successfully' : 'Like removed successfully'
            ]);
            break;

        default:
            sendJsonResponse(['success' => false, 'message' => 'Invalid action'], 400);
    }

} catch (Exception $e) {
    // Log the error with backtrace
    $errorMessage = 'Blog interaction error: ' . $e->getMessage() .
                   ' in ' . $e->getFile() . ' on line ' . $e->getLine() .
                   '\nStack trace:\n' . $e->getTraceAsString();

    logError($errorMessage);

    // Prepare error response
    $errorResponse = [
        'success' => false,
        'message' => 'An error occurred while processing your request',
    ];

    // Add debug info in development
    if (defined('ENVIRONMENT') && ENVIRONMENT === 'development') {
        $errorResponse['error'] = $e->getMessage();
        $errorResponse['file'] = $e->getFile();
        $errorResponse['line'] = $e->getLine();
    }

    // Send error response
    sendJsonResponse($errorResponse, 500);
}

// Make sure no output is sent after this point
exit;