<?php
// Include configuration
require_once __DIR__ . '/../admin/includes/config.php';

// Redirect if already logged in
if (isLoggedIn()) {
    redirect(isAdmin() ? '../admin/dashboard.php' : '../index.php');
}

// Initialize variables
$email = '';
$error = '';

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $remember = isset($_POST['remember']);
    
    // Validate inputs
    if (empty($email) || empty($password)) {
        $error = 'Please enter both email and password.';
    } else {
        // Prepare and execute query
        $stmt = $GLOBALS['conn']->prepare("SELECT id, name, email, password, role FROM users WHERE email = ? LIMIT 1");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($user = $result->fetch_assoc()) {
            // Verify password
            if (password_verify($password, $user['password'])) {
                // Set session variables
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['name'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['role'] = $user['role'];  // Keep this for backward compatibility
                $_SESSION['user_role'] = $user['role'];  // Add this for admin dashboard
                
                // Set remember me cookie if requested (30 days)
                if ($remember) {
                    $token = bin2hex(random_bytes(32));
                    $expires = time() + (86400 * 30); // 30 days
                    setcookie('remember_token', $token, $expires, '/');
                    
                    // Store token in database (you'll need to add a remember_tokens table)
                    $stmt = $GLOBALS['conn']->prepare("INSERT INTO remember_tokens (user_id, token, expires_at) VALUES (?, ?, ?)");
                    $expires_date = date('Y-m-d H:i:s', $expires);
                    $stmt->bind_param("iss", $user['id'], $token, $expires_date);
                    $stmt->execute();
                }
                
                // Redirect based on role
                if ($user['role'] === 'admin') {
                    header('Location: /admin/dashboard.php');
                    exit();
                } else {
                    header('Location: /account/customerdashboard.php');
                    exit();
                }
            } else {
                $error = 'Invalid email or password.';
            }
        } else {
            $error = 'Invalid email or password.';
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
    <title>Login - Addin Meals on Wheels</title>
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #F5E6D3; /* Warm Cream background */
        }
        .login-container {
            max-width: 400px;
            margin: 100px auto;
        }
        .btn-primary {
            background-color: #C1272D; /* Deep Red */
            color: white;
            transition: all 0.3s;
        }
        .btn-primary:hover {
            background-color: #A02025; /* Slightly darker red on hover */
        }
        .form-input:focus {
            border-color: #C1272D;
            box-shadow: 0 0 0 3px rgba(193, 39, 45, 0.2);
        }
    </style>
</head>
<body class="bg-gray-100">
    <div class="min-h-screen flex items-center justify-center">
        <div class="login-container bg-white p-8 rounded-lg shadow-lg w-full">
            <div class="text-center mb-8">
                <h1 class="text-3xl font-bold text-[#1A1A1A] mb-2">Welcome Back</h1>
                <p class="text-gray-600">Sign in to your account</p>
            </div>
            
            <?php if (isset($_SESSION['message'])): ?>
                <div class="mb-4 p-3 rounded <?php echo $_SESSION['message']['type'] === 'success' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                    <?php 
                    echo htmlspecialchars($_SESSION['message']['text']);
                    unset($_SESSION['message']);
                    ?>
                </div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="mb-4 p-3 bg-red-100 text-red-800 rounded">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="" class="space-y-6">
                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                    <input type="email" id="email" name="email" required 
                           class="w-full px-4 py-2 border rounded-md form-input focus:outline-none focus:ring-2 focus:ring-[#C1272D] focus:border-transparent"
                           value="<?php echo htmlspecialchars($email); ?>"
                           placeholder="Enter your email">
                </div>
                
                <div>
                    <label for="password" class="block text-sm font-medium text-gray-700 mb-1">Password</label>
                    <input type="password" id="password" name="password" required 
                           class="w-full px-4 py-2 border rounded-md form-input focus:outline-none focus:ring-2 focus:ring-[#C1272D] focus:border-transparent"
                           placeholder="Enter your password">
                </div>
                
                <div class="flex items-center justify-between">
                    <div class="flex items-center">
                        <input id="remember" name="remember" type="checkbox" class="h-4 w-4 text-[#C1272D] focus:ring-[#C1272D] border-gray-300 rounded">
                        <label for="remember" class="ml-2 block text-sm text-gray-700">
                            Remember me
                        </label>
                    </div>
                    
                    <div class="text-sm">
                        <a href="forgot-password.php" class="font-medium text-[#C1272D] hover:text-[#A02025]">
                            Forgot password?
                        </a>
                    </div>
                </div>
                
                <div>
                    <button type="submit" class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-[#C1272D] hover:bg-[#A02025] focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-[#C1272D]">
                        Sign in
                    </button>
                </div>
            </form>
            
            <div class="mt-6 text-center text-sm">
                <p class="text-gray-600">
                    Don't have an account? 
                    <a href="register.php" class="font-medium text-[#C1272D] hover:text-[#A02025]">
                        Sign up
                    </a>
                </p>
            </div>
        </div>
    </div>
</body>
</html>