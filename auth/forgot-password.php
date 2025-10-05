<?php
// Include configuration
require_once __DIR__ . '/../admin/includes/config.php';

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
            $stmt = $GLOBALS['conn']->prepare("
                INSERT INTO password_resets (user_id, token, expires_at) 
                VALUES (?, ?, ?)
                ON DUPLICATE KEY UPDATE 
                    token = VALUES(token),
                    expires_at = VALUES(expires_at),
                    created_at = NOW()
            ");
            $stmt->bind_param("iss", $user['id'], $token, $expires);
            
            if ($stmt->execute()) {
                // Send email with reset link
                $reset_link = "http://" . $_SERVER['HTTP_HOST'] . "/auth/reset-password.php?token=" . $token;
                $subject = "Password Reset Request";
                $message = "Hello " . htmlspecialchars($user['name']) . ",\n\n";
                $message .= "You requested a password reset. Click the link below to set a new password:\n\n";
                $message .= $reset_link . "\n\n";
                $message .= "This link will expire in 1 hour.\n\n";
                $message .= "If you didn't request this, please ignore this email.\n";
                
                // In a real application, use a proper email sending library
                $headers = "From: no-reply@addinmeals.com";
                @mail($email, $subject, $message, $headers);
                
                $success = true;
                $success_message = "If an account exists with that email, we've sent a password reset link.";
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