<?php
// Include configuration
require_once __DIR__ . '/../admin/includes/config.php';

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    $role = $_SESSION['role'] ?? $_SESSION['user_role'] ?? 'customer';
    if ($role === 'admin') {
        redirect('../admin/dashboard.php');
    } else if ($role === 'driver' || $role === 'delivery') {
        redirect('../dashboards/delivery/index.php');
    } else if ($role === 'ambassador') {
        redirect('../dashboards/ambassador/index.php');
    } else {
        redirect('../account/customerdashboard.php');
    }
}

// Initialize variables
$email = '';
$error = '';
$redirect_url = $_GET['redirect'] ?? '';

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
        // Check if database connection exists
        if (!isset($GLOBALS['conn']) || !$GLOBALS['conn']) {
            $error = 'Database connection error. Please try again later.';
        } else {
            // Prepare and execute query with status check
            $stmt = $GLOBALS['conn']->prepare("SELECT id, name, email, password, role, status FROM users WHERE email = ? LIMIT 1");
            if (!$stmt) {
                $error = 'Database query error. Please try again later.';
            } else {
                $stmt->bind_param("s", $email);
                $stmt->execute();
                $result = $stmt->get_result();
                
                if ($user = $result->fetch_assoc()) {
                    // Check if user is active
                    if ($user['status'] !== 'active') {
                        $error = 'Your account has been deactivated. Please contact an administrator.';
                    } else if (password_verify($password, $user['password'])) {
                        // Set session variables
                        $_SESSION['user_id'] = $user['id'];
                        $_SESSION['username'] = $user['name'];
                        $_SESSION['email'] = $user['email'];
                        $_SESSION['role'] = $user['role'];  // Keep this for backward compatibility
                        $_SESSION['user_role'] = $user['role'];  // Add this for admin dashboard
                        $_SESSION['user_status'] = $user['status'];  // Add user status to session

                        // Force session write
                        session_write_close();

                        // Log login activity
                        require_once '../includes/ActivityLogger.php';
                        $activityLogger = new ActivityLogger($pdo);
                        $activityLogger->logActivity("{$user['name']} ({$user['role']}) logged in successfully", $user['id'], 'login');

                        // Set remember me cookie if requested (30 days)
                        if ($remember) {
                            $token = bin2hex(random_bytes(32));
                            $expires = time() + (86400 * 30); // 30 days
                            setcookie('remember_token', $token, $expires, '/', '', false, true);

                            // Store token in database (you'll need to add a remember_tokens table)
                            $stmt = $GLOBALS['conn']->prepare("INSERT INTO remember_tokens (user_id, token, expires_at) VALUES (?, ?, ?)");
                            $expires_date = date('Y-m-d H:i:s', $expires);
                            $stmt->bind_param("iss", $user['id'], $token, $expires_date);
                            $stmt->execute();
                        }

                        // Redirect based on role or redirect parameter
                        if ($user['role'] === 'admin') {
                            header('Location: /admin/dashboard.php');
                            exit();
                        } else if ($user['role'] === 'driver' || $user['role'] === 'delivery') {
                            header('Location: /dashboards/delivery/index.php');
                            exit();
                        } else if ($user['role'] === 'ambassador') {
                            header('Location: /dashboards/ambassador/index.php');
                            exit();
                        } else {
                            // Check if user came from checkout and should be redirected back there
                            if (!empty($redirect_url) && $redirect_url === 'checkout.php') {
                                header('Location: /checkout.php');
                                exit();
                            } else {
                                header('Location: /account/customerdashboard.php');
                                exit();
                            }
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
            background-color: #fc7703; /* Orange */
            color: white;
            transition: all 0.3s;
        }
        .btn-primary:hover {
            background-color: #e66b02; /* Slightly darker orange on hover */
        }
        .form-input:focus {
            border-color: #fc7703;
            box-shadow: 0 0 0 3px rgba(252, 119, 3, 0.2);
        }
        .password-toggle {
            position: relative;
            display: block;
        }
        .password-toggle input {
            padding-right: 45px !important;
            width: 100%;
        }
        .password-toggle button {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: #6B7280;
            cursor: pointer;
            padding: 6px;
            z-index: 10;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .password-toggle button:hover {
            color: #374151;
        }
        .password-toggle button i {
            font-size: 16px;
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
                           class="w-full px-4 py-2 border rounded-md form-input focus:outline-none focus:ring-2 focus:ring-[#fc7703] focus:border-transparent"
                           value="<?php echo htmlspecialchars($email); ?>"
                           placeholder="Enter your email">
                </div>

                <div class="password-toggle">
                    <label for="password" class="block text-sm font-medium text-gray-700 mb-1">Password</label>
                    <div class="relative">
                        <input type="password" id="password" name="password" required
                               class="w-full px-4 py-2 pr-12 border rounded-md form-input focus:outline-none focus:ring-2 focus:ring-[#fc7703] focus:border-transparent"
                               placeholder="Enter your password">
                        <button type="button" onclick="togglePassword('password', 'passwordToggle')" class="absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-500 hover:text-gray-700 z-10">
                            <i class="fas fa-eye text-sm" id="passwordToggle"></i>
                        </button>
                    </div>
                </div>

                <div class="flex items-center justify-between">
                    <div class="flex items-center">
                        <input id="remember" name="remember" type="checkbox" class="h-4 w-4 text-[#fc7703] focus:ring-[#fc7703] border-gray-300 rounded">
                        <label for="remember" class="ml-2 block text-sm text-gray-700">
                            Remember me
                        </label>
                    </div>

                    <div class="text-sm">
                        <a href="forgot-password.php" class="font-medium text-[#fc7703] hover:text-[#e66b02]">
                            Forgot password?
                        </a>
                    </div>
                </div>

                <div>
                    <button type="submit" class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-[#fc7703] hover:bg-[#e66b02] focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-[#fc7703]">
                        Sign in
                    </button>
                </div>
            </form>

            <div class="mt-6 text-center text-sm">
                <p class="text-gray-600">
                    Don't have an account?
                    <a href="register.php" class="font-medium text-[#fc7703] hover:text-[#e66b02]">
                        Sign up
                    </a>
                </p>
            </div>
        </div>
    </div>

    <script>
        function togglePassword(inputId, iconId) {
            const passwordInput = document.getElementById(inputId);
            const toggleIcon = document.getElementById(iconId);

            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                toggleIcon.classList.remove('fa-eye');
                toggleIcon.classList.add('fa-eye-slash');
            } else {
                passwordInput.type = 'password';
                toggleIcon.classList.remove('fa-eye-slash');
                toggleIcon.classList.add('fa-eye');
            }
        }
    </script>
</body>
</html>