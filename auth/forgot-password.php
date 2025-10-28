<?php
// Include configuration and EmailService
require_once __DIR__ . '/../admin/includes/config.php';
require_once __DIR__ . '/../includes/EmailService.php';

// Redirect if already logged in
if (isLoggedIn()) {
    redirect(isAdmin() ? '../admin/dashboard.php' : '../index.php');
}

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $success = false;
    
    if (empty($email)) {
        $error = 'Please enter your email address';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address';
    } else {
        // Check if email exists
        $stmt = $GLOBALS['conn']->prepare("SELECT id, name FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($user = $result->fetch_assoc()) {
            // Generate token (valid for 1 hour)
            $token = bin2hex(random_bytes(32));
            $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));
            
            // Store token in database
            $used = 0; // Token starts as unused
            $stmt = $GLOBALS['conn']->prepare("
                INSERT INTO password_resets (user_id, token, expires_at, used) 
                VALUES (?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE 
                    token = VALUES(token),
                    expires_at = VALUES(expires_at),
                    used = VALUES(used),
                    created_at = NOW()
            ");
            $stmt->bind_param("issi", $user['id'], $token, $expires, $used);
            
            if ($stmt->execute()) {
                // Send email with reset link using PHPMailer
                $reset_link = "http://" . $_SERVER['HTTP_HOST'] . "/auth/reset-password.php?token=" . $token;
                $subject = "Password Reset Request";
                $message = "
                <!DOCTYPE html>
                <html>
                <head>
                    <meta charset='utf-8'>
                    <title>Password Reset</title>
                    <style>
                        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px; }
                        .header { background: #C1272D; color: white; padding: 20px; text-align: center; border-radius: 5px 5px 0 0; }
                        .content { background: #f8f9fa; padding: 20px; border-radius: 0 0 5px 5px; }
                        .button { display: inline-block; background: #C1272D; color: white; padding: 12px 24px; text-decoration: none; border-radius: 5px; margin: 20px 0; }
                        .footer { text-align: center; margin-top: 20px; color: #666; font-size: 12px; }
                    </style>
                </head>
                <body>
                    <div class='header'>
                        <h1>Password Reset Request</h1>
                    </div>
                    <div class='content'>
                        <p>Hello " . htmlspecialchars($user['name']) . ",</p>
                        <p>You requested a password reset. Click the button below to set a new password:</p>
                        <p><a href='{$reset_link}' class='button'>Reset Password</a></p>
                        <p><strong>Or copy and paste this link:</strong></p>
                        <p><a href='{$reset_link}'>{$reset_link}</a></p>
                        <p>This link will expire in 1 hour.</p>
                        <p>If you didn't request this, please ignore this email.</p>
                        <p>Best regards,<br>Addins Meals on Wheels Team</p>
                    </div>
                    <div class='footer'>
                        <p>This is an automated message. Please do not reply to this email.</p>
                    </div>
                </body>
                </html>";

                // Try to send email using EmailService
                $emailSent = false;
                
                try {
                    $emailService = new EmailService();
                    $emailSent = $emailService->sendEmail($email, $subject, $message);
                    
                    if ($emailSent) {
                        // Clear any previous email content from session
                        if (isset($_SESSION['email_content'])) {
                            unset($_SESSION['email_content']);
                        }
                    } else {
                        throw new Exception('Failed to send email via EmailService');
                    }
                } catch (Exception $e) {
                    error_log("Password reset email failed: " . $e->getMessage());
                    // For development, show the email content in case of failure
                    if (isset($_SERVER['HTTP_HOST']) && strpos($_SERVER['HTTP_HOST'], 'localhost') !== false) {
                        $_SESSION['email_content'] = [
                            'to' => $email,
                            'subject' => $subject,
                            'message' => $message,
                            'reset_link' => $reset_link,
                            'error' => $e->getMessage()
                        ];
                    }
                    $error = "Error sending email. Please try again later or contact support if the problem persists.";
                }

                if ($emailSent) {
                    $success = true;
                    $success_message = "If an account exists with that email, we've sent a password reset link.";
                }
            } else {
                $error = "Error processing your request. Please try again.";
            }
        } else {
            // For security, don't reveal if email exists
            $success = true;
            $success_message = "If an account exists with that email, we've sent a password reset link.";
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - Addin Meals on Wheels</title>
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <style>
        body { background-color: #F5E6D3; }
        .form-container { max-width: 400px; margin: 100px auto; }
        .btn-primary { 
            background-color: #C1272D;
            transition: all 0.3s;
        }
        .btn-primary:hover { background-color: #A02025; }
        .form-input:focus {
            border-color: #C1272D;
            box-shadow: 0 0 0 3px rgba(193, 39, 45, 0.2);
        }
    </style>
</head>
<body class="bg-gray-100">
    <div class="min-h-screen flex items-center justify-center p-4">
        <div class="form-container bg-white p-8 rounded-lg shadow-lg w-full">
            <div class="text-center mb-8">
                <h1 class="text-3xl font-bold text-[#1A1A1A] mb-2">Forgot Password</h1>
                <p class="text-gray-600">Enter your email to reset your password</p>
            </div>

            <?php if (isset($error)): ?>
                <div class="mb-4 p-3 bg-red-100 text-red-800 rounded">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <?php if (isset($success) && $success): ?>
                <div class="mb-4 p-3 bg-green-100 text-green-800 rounded">
                    <?php echo htmlspecialchars($success_message); ?>
                    <p class="mt-2">Didn't receive the email? <a href="forgot-password.php" class="font-medium text-[#C1272D] hover:underline">Try again</a></p>
                </div>

                <?php if (isset($_SESSION['email_content'])): ?>
                    <!-- Development Mode: Show Email Content -->
                    <div class="mt-6 p-4 bg-blue-50 border border-blue-200 rounded-lg">
                        <h3 class="font-semibold text-blue-800 mb-2">📧 Development Mode - Email Preview</h3>
                        <div class="text-sm text-blue-700 space-y-1">
                            <p><strong>To:</strong> <?php echo htmlspecialchars($_SESSION['email_content']['to']); ?></p>
                            <p><strong>Subject:</strong> <?php echo htmlspecialchars($_SESSION['email_content']['subject']); ?></p>
                            <p><strong>Reset Link:</strong> <a href="<?php echo htmlspecialchars($_SESSION['email_content']['reset_link']); ?>" class="text-blue-600 underline" target="_blank">Click here</a></p>
                        </div>
                        <div class="mt-3 p-3 bg-gray-50 rounded text-xs font-mono text-gray-700">
                            <?php echo nl2br(htmlspecialchars($_SESSION['email_content']['message'])); ?>
                        </div>
                        <p class="mt-2 text-xs text-blue-600">In production, this email would be sent to the user.</p>
                    </div>
                <?php endif; ?>
            <?php else: ?>
                <form method="POST" action="" class="space-y-6">
                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email Address *</label>
                        <input type="email" id="email" name="email" required
                               class="w-full px-4 py-2 border rounded-md form-input focus:outline-none focus:ring-2 focus:ring-[#C1272D] focus:border-transparent"
                               placeholder="your@email.com"
                               value="<?php echo isset($email) ? htmlspecialchars($email) : ''; ?>">
                    </div>
                    
                    <div>
                        <button type="submit" 
                                class="w-full flex justify-center py-3 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-[#C1272D] hover:bg-[#A02025] focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-[#C1272D]">
                            Send Reset Link
                        </button>
                    </div>
                </form>
            <?php endif; ?>

            <div class="mt-6 text-center text-sm">
                <p class="text-gray-600">
                    Remember your password? 
                    <a href="login.php" class="font-medium text-[#C1272D] hover:text-[#A02025]">
                        Sign in
                    </a>
                </p>
            </div>
        </div>
    </div>
</body>
</html>