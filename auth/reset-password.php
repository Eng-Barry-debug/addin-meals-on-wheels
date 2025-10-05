<?php
// Include configuration
require_once __DIR__ . '/../admin/includes/config.php';

// Redirect if already logged in
if (isLoggedIn()) {
    redirect(isAdmin() ? '../admin/dashboard.php' : '../index.php');
}

$token = $_GET['token'] ?? '';
$error = '';
$valid_token = false;
$user_id = null;

// Validate token
if (!empty($token)) {
    $stmt = $GLOBALS['conn']->prepare("
        SELECT user_id 
        FROM password_resets 
        WHERE token = ? 
        AND used = 0 
        AND expires_at > NOW()
    ");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        $valid_token = true;
        $user_id = $row['user_id'];
    } else {
        $error = 'Invalid or expired token. Please request a new password reset.';
    }
    $stmt->close();
}

// Process password reset
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $valid_token) {
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    // Validate inputs
    if (empty($password)) {
        $error = 'Password is required';
    } elseif (strlen($password) < 8) {
        $error = 'Password must be at least 8 characters long';
    } elseif ($password !== $confirm_password) {
        $error = 'Passwords do not match';
    } else {
        // Update password
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $GLOBALS['conn']->prepare("UPDATE users SET password = ? WHERE id = ?");
        $stmt->bind_param("si", $hashed_password, $user_id);
        
        if ($stmt->execute()) {
            // Mark token as used
            $stmt = $GLOBALS['conn']->prepare("UPDATE password_resets SET used = 1 WHERE token = ?");
            $stmt->bind_param("s", $token);
            $stmt->execute();
            
            $_SESSION['message'] = [
                'type' => 'success',
                'text' => 'Your password has been reset successfully. You can now log in with your new password.'
            ];
            redirect('login.php');
        } else {
            $error = 'Error updating password. Please try again.';
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
    <title>Reset Password - Addin Meals on Wheels</title>
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
                <h1 class="text-3xl font-bold text-[#1A1A1A] mb-2">Reset Password</h1>
                <p class="text-gray-600">Enter your new password below</p>
            </div>

            <?php if (!empty($error)): ?>
                <div class="mb-4 p-3 bg-red-100 text-red-800 rounded">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <?php if ($valid_token): ?>
                <form method="POST" action="" class="space-y-6">
                    <div>
                        <label for="password" class="block text-sm font-medium text-gray-700 mb-1">New Password *</label>
                        <input type="password" id="password" name="password" required
                               class="w-full px-4 py-2 border rounded-md form-input focus:outline-none focus:ring-2 focus:ring-[#C1272D] focus:border-transparent"
                               placeholder="At least 8 characters">
                    </div>
                    
                    <div>
                        <label for="confirm_password" class="block text-sm font-medium text-gray-700 mb-1">Confirm New Password *</label>
                        <input type="password" id="confirm_password" name="confirm_password" required
                               class="w-full px-4 py-2 border rounded-md form-input focus:outline-none focus:ring-2 focus:ring-[#C1272D] focus:border-transparent"
                               placeholder="Confirm your password">
                    </div>
                    
                    <div>
                        <button type="submit" 
                                class="w-full flex justify-center py-3 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-[#C1272D] hover:bg-[#A02025] focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-[#C1272D]">
                            Reset Password
                        </button>
                    </div>
                </form>
            <?php else: ?>
                <div class="text-center">
                    <p class="text-gray-600 mb-4">The password reset link is invalid or has expired.</p>
                    <a href="forgot-password.php" class="font-medium text-[#C1272D] hover:text-[#A02025]">
                        Request a new password reset
                    </a>
                </div>
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