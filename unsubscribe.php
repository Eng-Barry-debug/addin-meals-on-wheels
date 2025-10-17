<?php
// unsubscribe.php - Handle newsletter unsubscribe requests

require_once 'includes/config.php';

$token = $_GET['token'] ?? '';
$email = $_GET['email'] ?? '';

if (empty($token) && empty($email)) {
    die('Invalid unsubscribe request');
}

$message = '';
$message_type = '';

try {
    if (!empty($token)) {
        // Unsubscribe by token
        $stmt = $pdo->prepare("UPDATE newsletter_subscriptions SET is_active = FALSE, unsubscribed_at = CURRENT_TIMESTAMP WHERE unsubscribe_token = ? AND is_active = TRUE");
        $stmt->execute([$token]);

        if ($stmt->rowCount() > 0) {
            $message = 'You have been successfully unsubscribed from our newsletter.';
            $message_type = 'success';
        } else {
            $message = 'Invalid unsubscribe token or you are already unsubscribed.';
            $message_type = 'error';
        }
    } elseif (!empty($email)) {
        // Unsubscribe by email (for email clients that don't support links)
        $stmt = $pdo->prepare("UPDATE newsletter_subscriptions SET is_active = FALSE, unsubscribed_at = CURRENT_TIMESTAMP WHERE email = ? AND is_active = TRUE");
        $stmt->execute([$email]);

        if ($stmt->rowCount() > 0) {
            $message = 'You have been successfully unsubscribed from our newsletter.';
            $message_type = 'success';
        } else {
            $message = 'Email address not found in our subscription list.';
            $message_type = 'error';
        }
    }

} catch (Exception $e) {
    $message = 'An error occurred while processing your unsubscribe request.';
    $message_type = 'error';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Unsubscribe - Addins Meals on Wheels</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="bg-gray-100 min-h-screen flex items-center justify-center">
    <div class="max-w-md w-full bg-white rounded-lg shadow-lg p-8 text-center">
        <!-- Logo -->
        <div class="mb-6">
            <img src="/uploads/menu/Addin-logo.jpeg" alt="Addins Meals" class="h-12 mx-auto">
        </div>

        <!-- Icon -->
        <div class="mb-6">
            <div class="mx-auto w-16 h-16 <?php echo $message_type === 'success' ? 'bg-green-100' : 'bg-red-100'; ?> rounded-full flex items-center justify-center">
                <i class="fas <?php echo $message_type === 'success' ? 'fa-check text-green-600' : 'fa-exclamation-triangle text-red-600'; ?> text-2xl"></i>
            </div>
        </div>

        <!-- Message -->
        <h2 class="text-xl font-semibold mb-4 <?php echo $message_type === 'success' ? 'text-green-700' : 'text-red-700'; ?>">
            <?php echo $message_type === 'success' ? 'Unsubscribed Successfully' : 'Unsubscribe Error'; ?>
        </h2>

        <p class="text-gray-600 mb-6">
            <?php echo htmlspecialchars($message); ?>
        </p>

        <!-- Actions -->
        <div class="space-y-3">
            <?php if ($message_type === 'success'): ?>
                <p class="text-sm text-gray-500">
                    We're sorry to see you go! If you change your mind, you can always resubscribe from our website.
                </p>
            <?php else: ?>
                <p class="text-sm text-gray-500">
                    If you continue to receive unwanted emails, please contact our support team.
                </p>
            <?php endif; ?>

            <a href="/" class="inline-block bg-primary text-white px-6 py-2 rounded-lg hover:bg-primary-dark transition-colors">
                Return to Website
            </a>
        </div>
    </div>
</body>
</html>
