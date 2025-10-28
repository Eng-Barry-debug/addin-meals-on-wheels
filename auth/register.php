<?php
// Include configuration
require_once __DIR__ . '/../admin/includes/config.php';
// Redirect if already logged in
if (isLoggedIn()) {
    redirect(isAdmin() ? '../admin/dashboard.php' : '../index.php');
}

// Initialize variables
$name = $email = $phone = '';
$errors = [];

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get and validate form data
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    // Validate inputs
    if (empty($name)) {
        $errors['name'] = 'Name is required';
    }
    
    if (empty($email)) {
        $errors['email'] = 'Email is required';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Please enter a valid email address';
    } else {
        // Check if email already exists
        $stmt = $GLOBALS['conn']->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            $errors['email'] = 'This email is already registered';
        }
        $stmt->close();
    }
    
    if (empty($password)) {
        $errors['password'] = 'Password is required';
    } elseif (strlen($password) < 8) {
        $errors['password'] = 'Password must be at least 8 characters long';
    }
    
    if ($password !== $confirm_password) {
        $errors['confirm_password'] = 'Passwords do not match';
    }
    
    // If no errors, insert new user
    if (empty($errors)) {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $role = 'user'; // Default role for customers
        
        $stmt = $GLOBALS['conn']->prepare("INSERT INTO users (name, email, password, role, phone) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("sssss", $name, $email, $hashed_password, $role, $phone);
        
        if ($stmt->execute()) {
            // Registration successful
            $_SESSION['message'] = array(
                'type' => 'success',
                'text' => 'Registration successful! You can now log in.'
            );
            redirect('login.php');
        } else {
            $errors['general'] = 'Registration failed. Please try again.';
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
    <title>Create Account - Addin Meals on Wheels</title>
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <style>
        body { background-color: #F5E6D3; }
        .register-container { max-width: 500px; margin: 50px auto; }
        .btn-primary { 
            background-color: #fc7703;
            transition: all 0.3s;
        }
        .btn-primary:hover { background-color: #e66b02; }
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
    <div class="min-h-screen flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
        <div class="register-container bg-white p-8 rounded-lg shadow-lg w-full">
            <div class="text-center mb-8">
                <h1 class="text-3xl font-bold text-[#1A1A1A] mb-2">Create an Account</h1>
                <p class="text-gray-600">Join our community today</p>
            </div>

            <?php if (!empty($errors['general'])): ?>
                <div class="mb-4 p-3 bg-red-100 text-red-800 rounded">
                    <?php echo htmlspecialchars($errors['general']); ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="" class="space-y-6">
                <div>
                    <label for="name" class="block text-sm font-medium text-gray-700 mb-1">Full Name *</label>
                    <input type="text" id="name" name="name" required
                           class="w-full px-4 py-2 border rounded-md form-input focus:outline-none focus:ring-2 focus:ring-[#fc7703] focus:border-transparent"
                           value="<?php echo htmlspecialchars($name); ?>"
                           placeholder="John Doe">
                    <?php if (!empty($errors['name'])): ?>
                        <p class="mt-1 text-sm text-red-600"><?php echo htmlspecialchars($errors['name']); ?></p>
                    <?php endif; ?>
                </div>

                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email Address *</label>
                    <input type="email" id="email" name="email" required
                           class="w-full px-4 py-2 border rounded-md form-input focus:outline-none focus:ring-2 focus:ring-[#fc7703] focus:border-transparent"
                           value="<?php echo htmlspecialchars($email); ?>"
                           placeholder="your@email.com">
                    <?php if (!empty($errors['email'])): ?>
                        <p class="mt-1 text-sm text-red-600"><?php echo htmlspecialchars($errors['email']); ?></p>
                    <?php endif; ?>
                </div>

                <div>
                    <label for="phone" class="block text-sm font-medium text-gray-700 mb-1">Phone Number</label>
                    <input type="tel" id="phone" name="phone"
                           class="w-full px-4 py-2 border rounded-md form-input focus:outline-none focus:ring-2 focus:ring-[#fc7703] focus:border-transparent"
                           value="<?php echo htmlspecialchars($phone); ?>"
                           placeholder="+1 (555) 123-4567">
                </div>

                <div class="password-toggle">
                    <label for="password" class="block text-sm font-medium text-gray-700 mb-1">Password *</label>
                    <div class="relative">
                        <input type="password" id="password" name="password" required
                               class="w-full px-4 py-2 pr-12 border rounded-md form-input focus:outline-none focus:ring-2 focus:ring-[#fc7703] focus:border-transparent"
                               placeholder="At least 8 characters">
                        <button type="button" onclick="togglePassword('password', 'passwordToggle')" class="absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-500 hover:text-gray-700 z-10">
                            <i class="fas fa-eye text-sm" id="passwordToggle"></i>
                        </button>
                    </div>
                    <?php if (!empty($errors['password'])): ?>
                        <p class="mt-1 text-sm text-red-600"><?php echo htmlspecialchars($errors['password']); ?></p>
                    <?php endif; ?>
                </div>

                <div class="password-toggle">
                    <label for="confirm_password" class="block text-sm font-medium text-gray-700 mb-1">Confirm Password *</label>
                    <div class="relative">
                        <input type="password" id="confirm_password" name="confirm_password" required
                               class="w-full px-4 py-2 pr-12 border rounded-md form-input focus:outline-none focus:ring-2 focus:ring-[#fc7703] focus:border-transparent"
                               placeholder="Confirm your password">
                        <button type="button" onclick="togglePassword('confirm_password', 'confirmPasswordToggle')" class="absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-500 hover:text-gray-700 z-10">
                            <i class="fas fa-eye text-sm" id="confirmPasswordToggle"></i>
                        </button>
                    </div>
                    <?php if (!empty($errors['confirm_password'])): ?>
                        <p class="mt-1 text-sm text-red-600"><?php echo htmlspecialchars($errors['confirm_password']); ?></p>
                    <?php endif; ?>
                </div>

                <div class="flex items-center">
                    <input id="terms" name="terms" type="checkbox" required
                           class="h-4 w-4 text-[#fc7703] focus:ring-[#fc7703] border-gray-300 rounded">
                    <label for="terms" class="ml-2 block text-sm text-gray-700">
                        I agree to the <a href="/terms.php" class="text-[#fc7703] hover:underline">Terms of Service</a> and 
                        <a href="/privacy.php" class="text-[#fc7703] hover:underline">Privacy Policy</a> *
                    </label>
                </div>

                <div>
                    <button type="submit" 
                            class="w-full flex justify-center py-3 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-[#fc7703] hover:bg-[#e66b02] focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-[#fc7703]">
                        Create Account
                    </button>
                </div>
            </form>

            <div class="mt-6 text-center text-sm">
                <p class="text-gray-600">
                    Already have an account? 
                    <a href="login.php" class="font-medium text-[#fc7703] hover:text-[#e66b02]">
                        Sign in
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