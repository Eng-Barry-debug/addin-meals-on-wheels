<?php
// Set page title and include header
$page_title = 'Add New User';

// Include database connection and functions
require_once '../includes/config.php';
require_once 'includes/functions.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: login.php');
    exit();
}

// Initialize variables
$errors = [];
$user = [
    'name' => '',
    'email' => '',
    'phone' => '',
    'role' => 'user',
    'status' => 'active'
];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize and validate input
    $user['name'] = trim($_POST['name'] ?? '');
    $user['email'] = trim($_POST['email'] ?? '');
    $user['phone'] = trim($_POST['phone'] ?? '');
    $user['role'] = $_POST['role'] ?? 'user';
    $user['status'] = $_POST['status'] ?? 'active';
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    // Validate name
    if (empty($user['name'])) {
        $errors[] = 'Name is required';
    }
    
    // Validate email
    if (empty($user['email']) || !filter_var($user['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Valid email is required';
    } else {
        // Check if email already exists
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$user['email']]);
        if ($stmt->fetch()) {
            $errors[] = 'Email is already registered';
        }
    }
    
    // Validate password
    if (empty($password)) {
        $errors[] = 'Password is required';
    } elseif (strlen($password) < 8) {
        $errors[] = 'Password must be at least 8 characters long';
    } elseif ($password !== $confirm_password) {
        $errors[] = 'Passwords do not match';
    }
    
    // If no errors, insert the new user
    if (empty($errors)) {
        try {
            $pdo->beginTransaction();
            
            // Hash the password
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            // Insert the new user
            $stmt = $pdo->prepare("INSERT INTO users (name, email, phone, password, role, status) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $user['name'],
                $user['email'],
                $user['phone'],
                $hashed_password,
                $user['role'],
                $user['status']
            ]);
            
            $pdo->commit();
            
            // Set success message and redirect
            $_SESSION['message'] = ['type' => 'success', 'text' => 'User added successfully'];
            header('Location: users.php');
            exit();
            
        } catch (PDOException $e) {
            $pdo->rollBack();
            $errors[] = 'Failed to add user: ' . $e->getMessage();
        }
    }
}

// Include header
include 'includes/header.php';
?>

<div class="max-w-3xl mx-auto">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold text-gray-900">Add New User</h1>
        <a href="users.php" class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary">
            <i class="fas fa-arrow-left mr-2"></i> Back to Users
        </a>
    </div>

    <div class="bg-white shadow overflow-hidden sm:rounded-lg">
        <div class="px-4 py-5 sm:p-6">
            <?php if (!empty($errors)): ?>
                <div class="bg-red-50 border-l-4 border-red-400 p-4 mb-6">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-red-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        <div class="ml-3">
                            <h3 class="text-sm font-medium text-red-800">
                                Please fix the following errors:
                            </h3>
                            <div class="mt-2 text-sm text-red-700">
                                <ul class="list-disc pl-5 space-y-1">
                                    <?php foreach ($errors as $error): ?>
                                        <li><?= htmlspecialchars($error) ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <form method="POST" class="space-y-6">
                <div class="grid grid-cols-1 gap-y-6 gap-x-4 sm:grid-cols-6">
                    <div class="sm:col-span-6">
                        <label for="name" class="block text-sm font-medium text-gray-700">Full Name <span class="text-red-500">*</span></label>
                        <div class="mt-1">
                            <input type="text" name="name" id="name" value="<?= htmlspecialchars($user['name']) ?>" 
                                   class="shadow-sm focus:ring-primary focus:border-primary block w-full sm:text-sm border-gray-300 rounded-md" required>
                        </div>
                    </div>

                    <div class="sm:col-span-6">
                        <label for="email" class="block text-sm font-medium text-gray-700">Email Address <span class="text-red-500">*</span></label>
                        <div class="mt-1">
                            <input type="email" name="email" id="email" value="<?= htmlspecialchars($user['email']) ?>" 
                                   class="shadow-sm focus:ring-primary focus:border-primary block w-full sm:text-sm border-gray-300 rounded-md" required>
                        </div>
                    </div>

                    <div class="sm:col-span-6">
                        <label for="phone" class="block text-sm font-medium text-gray-700">Phone Number</label>
                        <div class="mt-1">
                            <input type="tel" name="phone" id="phone" value="<?= htmlspecialchars($user['phone']) ?>" 
                                   class="shadow-sm focus:ring-primary focus:border-primary block w-full sm:text-sm border-gray-300 rounded-md">
                        </div>
                    </div>

                    <div class="sm:col-span-3">
                        <label for="role" class="block text-sm font-medium text-gray-700">Role <span class="text-red-500">*</span></label>
                        <div class="mt-1">
                            <select id="role" name="role" class="shadow-sm focus:ring-primary focus:border-primary block w-full sm:text-sm border-gray-300 rounded-md">
                                <option value="user" <?= $user['role'] === 'user' ? 'selected' : '' ?>>User</option>
                                <option value="admin" <?= $user['role'] === 'admin' ? 'selected' : '' ?>>Administrator</option>
                            </select>
                        </div>
                    </div>

                    <div class="sm:col-span-3">
                        <label for="status" class="block text-sm font-medium text-gray-700">Status <span class="text-red-500">*</span></label>
                        <div class="mt-1">
                            <select id="status" name="status" class="shadow-sm focus:ring-primary focus:border-primary block w-full sm:text-sm border-gray-300 rounded-md">
                                <option value="active" <?= $user['status'] === 'active' ? 'selected' : '' ?>>Active</option>
                                <option value="inactive" <?= $user['status'] === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                            </select>
                        </div>
                    </div>

                    <div class="sm:col-span-6">
                        <label for="password" class="block text-sm font-medium text-gray-700">Password <span class="text-red-500">*</span></label>
                        <div class="mt-1">
                            <input type="password" name="password" id="password" 
                                   class="shadow-sm focus:ring-primary focus:border-primary block w-full sm:text-sm border-gray-300 rounded-md" 
                                   required minlength="8">
                            <p class="mt-1 text-xs text-gray-500">Password must be at least 8 characters long</p>
                        </div>
                    </div>

                    <div class="sm:col-span-6">
                        <label for="confirm_password" class="block text-sm font-medium text-gray-700">Confirm Password <span class="text-red-500">*</span></label>
                        <div class="mt-1">
                            <input type="password" name="confirm_password" id="confirm_password" 
                                   class="shadow-sm focus:ring-primary focus:border-primary block w-full sm:text-sm border-gray-300 rounded-md" 
                                   required minlength="8">
                        </div>
                    </div>
                </div>

                <div class="pt-5">
                    <div class="flex justify-end">
                        <a href="users.php" class="bg-white py-2 px-4 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary">
                            Cancel
                        </a>
                        <button type="submit" class="ml-3 inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-primary hover:bg-secondary focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary">
                            Save User
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
