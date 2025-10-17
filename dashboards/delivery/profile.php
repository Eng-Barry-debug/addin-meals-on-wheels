<?php
// Start session and check delivery authentication
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include database connection and functions
require_once '../../admin/includes/config.php';

// Check if user is logged in and is delivery personnel
if (!isset($_SESSION['user_id']) || ($_SESSION['user_role'] !== 'delivery' && $_SESSION['user_role'] !== 'driver')) {
    header('Location: ../../auth/login.php');
    exit();
}

// Set page title
$page_title = 'My Profile';

// Initialize variables
$userProfile = [];
$deliveryStats = [];
$error = null;
$success = null;

// Get user profile data
try {
    global $pdo;

    // Get user profile information
    $profileStmt = $pdo->prepare("
        SELECT u.*, COUNT(o.id) as total_deliveries,
               AVG(CASE WHEN o.status = 'delivered' THEN TIMESTAMPDIFF(MINUTE, o.created_at, o.updated_at) END) as avg_delivery_time
        FROM users u
        LEFT JOIN orders o ON u.id = ? AND o.status = 'delivered'
        WHERE u.id = ?
        GROUP BY u.id
    ");
    $profileStmt->execute([$_SESSION['user_id'], $_SESSION['user_id']]);
    $userProfile = $profileStmt->fetch(PDO::FETCH_ASSOC);

    // Get delivery statistics
    $statsStmt = $pdo->prepare("
        SELECT
            COUNT(*) as today_deliveries,
            COALESCE(SUM(total * 0.1), 0) as today_earnings,
            (SELECT COUNT(*) FROM orders WHERE status = 'delivered' AND updated_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)) as weekly_deliveries,
            (SELECT COALESCE(SUM(total * 0.1), 0) FROM orders WHERE status = 'delivered' AND updated_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)) as weekly_earnings
        FROM orders
        WHERE status = 'delivered' AND DATE(updated_at) = CURDATE()
    ");
    $statsStmt->execute();
    $deliveryStats = $statsStmt->fetch(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $error = "Database error: " . $e->getMessage();
    error_log("Delivery profile page error: " . $e->getMessage());
}

// Handle profile updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $name = trim($_POST['name']);
    $phone = trim($_POST['phone']);
    $vehicle_type = $_POST['vehicle_type'];
    $license_plate = trim($_POST['license_plate']);

    if (empty($name) || empty($phone)) {
        $error = "Name and phone are required.";
    } else {
        try {
            global $pdo;

            $stmt = $pdo->prepare("
                UPDATE users
                SET name = ?, phone = ?, updated_at = NOW()
                WHERE id = ?
            ");
            $stmt->execute([$name, $phone, $_SESSION['user_id']]);

            // Update session
            $_SESSION['username'] = $name;

            $success = "Profile updated successfully!";
            $userProfile['name'] = $name;
            $userProfile['phone'] = $phone;

        } catch (PDOException $e) {
            $error = "Database error: " . $e->getMessage();
        }
    }
}

// Handle password change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
        $error = "All password fields are required.";
    } elseif ($new_password !== $confirm_password) {
        $error = "New passwords do not match.";
    } elseif (strlen($new_password) < 6) {
        $error = "New password must be at least 6 characters long.";
    } else {
        try {
            global $pdo;

            // Verify current password
            $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
            $stmt->execute([$_SESSION['user_id']]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if (password_verify($current_password, $user['password'])) {
                // Update password
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
                $stmt->execute([$hashed_password, $_SESSION['user_id']]);

                $success = "Password changed successfully!";
            } else {
                $error = "Current password is incorrect.";
            }

        } catch (PDOException $e) {
            $error = "Database error: " . $e->getMessage();
        }
    }
}

// Include header
require_once 'includes/header.php';
?>

<!-- Page Header -->
<div class="bg-gradient-to-br from-primary via-primary-dark to-green-700 text-white">
    <div class="px-6 py-8">
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between">
            <div>
                <h1 class="text-3xl md:text-4xl font-bold mb-2">My Profile</h1>
                <p class="text-lg opacity-90">Manage your delivery profile and account settings</p>
            </div>
            <div class="mt-4 lg:mt-0">
                <div class="bg-white/10 backdrop-blur-sm rounded-lg px-4 py-2">
                    <span class="text-sm">Member since <?php echo date('M Y', strtotime($userProfile['created_at'] ?? 'now')); ?></span>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Main Content -->
<div class="px-6 py-8">
    <?php if ($success): ?>
        <div class="mb-6 p-4 bg-green-50 border-l-4 border-green-400 text-green-700 rounded-lg">
            <?php echo htmlspecialchars($success); ?>
        </div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="mb-6 p-4 bg-red-50 border-l-4 border-red-400 text-red-700 rounded-lg">
            <?php echo htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Profile Overview -->
        <div class="lg:col-span-1">
            <div class="bg-white rounded-xl shadow-lg p-6">
                <div class="text-center">
                    <div class="w-24 h-24 bg-primary rounded-full flex items-center justify-center mx-auto mb-4">
                        <span class="text-white text-2xl font-bold">
                            <?php echo strtoupper(substr($userProfile['name'] ?? 'D', 0, 1)); ?>
                        </span>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 mb-1"><?php echo htmlspecialchars($userProfile['name'] ?? 'Delivery Person'); ?></h3>
                    <p class="text-gray-600 mb-4">Delivery Personnel</p>

                    <!-- Quick Stats -->
                    <div class="grid grid-cols-2 gap-4 mb-6">
                        <div class="text-center p-3 bg-green-50 rounded-lg">
                            <div class="text-lg font-bold text-green-600"><?php echo $deliveryStats['today_deliveries'] ?? 0; ?></div>
                            <div class="text-xs text-green-700">Today</div>
                        </div>
                        <div class="text-center p-3 bg-blue-50 rounded-lg">
                            <div class="text-lg font-bold text-blue-600"><?php echo $userProfile['total_deliveries'] ?? 0; ?></div>
                            <div class="text-xs text-blue-700">Total</div>
                        </div>
                    </div>

                    <!-- Status Badge -->
                    <div class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-green-100 text-green-800 mb-4">
                        <div class="w-2 h-2 bg-green-400 rounded-full mr-2 animate-pulse"></div>
                        Active
                    </div>
                </div>

                <div class="space-y-3 text-sm">
                    <div class="flex justify-between">
                        <span class="text-gray-600">Email:</span>
                        <span class="font-medium"><?php echo htmlspecialchars($userProfile['email'] ?? ''); ?></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">Phone:</span>
                        <span class="font-medium"><?php echo htmlspecialchars($userProfile['phone'] ?? 'Not set'); ?></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">Avg. Time:</span>
                        <span class="font-medium"><?php echo isset($userProfile['avg_delivery_time']) ? round($userProfile['avg_delivery_time']) . 'm' : 'N/A'; ?></span>
                    </div>
                </div>
            </div>

            <!-- Performance Summary -->
            <div class="bg-white rounded-xl shadow-lg p-6 mt-6">
                <h4 class="text-lg font-bold text-gray-900 mb-4 flex items-center">
                    <i class="fas fa-trophy mr-2 text-secondary"></i>
                    Performance Summary
                </h4>

                <div class="space-y-3">
                    <div class="flex items-center justify-between">
                        <span class="text-gray-600">Today's Earnings</span>
                        <span class="font-semibold text-green-600">KES <?php echo number_format($deliveryStats['today_earnings'] ?? 0, 2); ?></span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-gray-600">Weekly Deliveries</span>
                        <span class="font-semibold"><?php echo $deliveryStats['weekly_deliveries'] ?? 0; ?> deliveries</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-gray-600">Rating</span>
                        <div class="flex items-center">
                            <span class="font-semibold mr-2">4.8</span>
                            <div class="flex">
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                    <i class="fas fa-star text-yellow-400 text-sm"></i>
                                <?php endfor; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Profile Forms -->
        <div class="lg:col-span-2 space-y-8">
            <!-- Personal Information -->
            <div class="bg-white rounded-xl shadow-lg p-6">
                <h3 class="text-xl font-bold text-gray-900 mb-6 flex items-center">
                    <i class="fas fa-user mr-3 text-primary"></i>
                    Personal Information
                </h3>

                <form method="POST" class="space-y-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label for="name" class="block text-sm font-medium text-gray-700 mb-2">Full Name</label>
                            <input type="text"
                                   id="name"
                                   name="name"
                                   value="<?php echo htmlspecialchars($userProfile['name'] ?? ''); ?>"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent"
                                   required>
                        </div>

                        <div>
                            <label for="email" class="block text-sm font-medium text-gray-700 mb-2">Email Address</label>
                            <input type="email"
                                   id="email"
                                   value="<?php echo htmlspecialchars($userProfile['email'] ?? ''); ?>"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg bg-gray-50"
                                   readonly>
                            <p class="text-xs text-gray-500 mt-1">Email cannot be changed</p>
                        </div>

                        <div>
                            <label for="phone" class="block text-sm font-medium text-gray-700 mb-2">Phone Number</label>
                            <input type="tel"
                                   id="phone"
                                   name="phone"
                                   value="<?php echo htmlspecialchars($userProfile['phone'] ?? ''); ?>"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent"
                                   required>
                        </div>

                        <div>
                            <label for="vehicle_type" class="block text-sm font-medium text-gray-700 mb-2">Vehicle Type</label>
                            <select id="vehicle_type" name="vehicle_type" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                                <option value="motorcycle" <?php echo ($userProfile['vehicle_type'] ?? '') === 'motorcycle' ? 'selected' : ''; ?>>Motorcycle</option>
                                <option value="car" <?php echo ($userProfile['vehicle_type'] ?? '') === 'car' ? 'selected' : ''; ?>>Car</option>
                                <option value="bicycle" <?php echo ($userProfile['vehicle_type'] ?? '') === 'bicycle' ? 'selected' : ''; ?>>Bicycle</option>
                                <option value="walking" <?php echo ($userProfile['vehicle_type'] ?? '') === 'walking' ? 'selected' : ''; ?>>Walking</option>
                            </select>
                        </div>
                    </div>

                    <div>
                        <label for="license_plate" class="block text-sm font-medium text-gray-700 mb-2">License Plate (Optional)</label>
                        <input type="text"
                               id="license_plate"
                               name="license_plate"
                               value="<?php echo htmlspecialchars($userProfile['license_plate'] ?? ''); ?>"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent"
                               placeholder="e.g., KCB 123D">
                    </div>

                    <div class="flex justify-end">
                        <button type="submit"
                                name="update_profile"
                                class="bg-primary hover:bg-primary-dark text-white px-6 py-2 rounded-lg font-medium transition-colors flex items-center">
                            <i class="fas fa-save mr-2"></i>
                            Update Profile
                        </button>
                    </div>
                </form>
            </div>

            <!-- Change Password -->
            <div class="bg-white rounded-xl shadow-lg p-6">
                <h3 class="text-xl font-bold text-gray-900 mb-6 flex items-center">
                    <i class="fas fa-lock mr-3 text-primary"></i>
                    Change Password
                </h3>

                <form method="POST" class="space-y-6">
                    <div>
                        <label for="current_password" class="block text-sm font-medium text-gray-700 mb-2">Current Password</label>
                        <input type="password"
                               id="current_password"
                               name="current_password"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent"
                               required>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label for="new_password" class="block text-sm font-medium text-gray-700 mb-2">New Password</label>
                            <input type="password"
                                   id="new_password"
                                   name="new_password"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent"
                                   required>
                        </div>

                        <div>
                            <label for="confirm_password" class="block text-sm font-medium text-gray-700 mb-2">Confirm New Password</label>
                            <input type="password"
                                   id="confirm_password"
                                   name="confirm_password"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent"
                                   required>
                        </div>
                    </div>

                    <div class="flex justify-end">
                        <button type="submit"
                                name="change_password"
                                class="bg-primary hover:bg-primary-dark text-white px-6 py-2 rounded-lg font-medium transition-colors flex items-center">
                            <i class="fas fa-key mr-2"></i>
                            Change Password
                        </button>
                    </div>
                </form>
            </div>

            <!-- Account Settings -->
            <div class="bg-white rounded-xl shadow-lg p-6">
                <h3 class="text-xl font-bold text-gray-900 mb-6 flex items-center">
                    <i class="fas fa-cog mr-3 text-primary"></i>
                    Account Settings
                </h3>

                <div class="space-y-4">
                    <div class="flex items-center justify-between p-4 border border-gray-200 rounded-lg">
                        <div class="flex items-center">
                            <i class="fas fa-bell text-gray-400 mr-3"></i>
                            <div>
                                <p class="font-medium text-gray-900">Push Notifications</p>
                                <p class="text-sm text-gray-600">Receive delivery notifications</p>
                            </div>
                        </div>
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" class="sr-only peer" checked>
                            <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-primary/25 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-primary"></div>
                        </label>
                    </div>

                    <div class="flex items-center justify-between p-4 border border-gray-200 rounded-lg">
                        <div class="flex items-center">
                            <i class="fas fa-map text-gray-400 mr-3"></i>
                            <div>
                                <p class="font-medium text-gray-900">Location Tracking</p>
                                <p class="text-sm text-gray-600">Track location during deliveries</p>
                            </div>
                        </div>
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" class="sr-only peer" checked>
                            <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-primary/25 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-primary"></div>
                        </label>
                    </div>

                    <div class="flex items-center justify-between p-4 border border-gray-200 rounded-lg">
                        <div class="flex items-center">
                            <i class="fas fa-moon text-gray-400 mr-3"></i>
                            <div>
                                <p class="font-medium text-gray-900">Dark Mode</p>
                                <p class="text-sm text-gray-600">Enable dark theme</p>
                            </div>
                        </div>
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" class="sr-only peer">
                            <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-primary/25 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-primary"></div>
                        </label>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Password confirmation validation
document.getElementById('confirm_password')?.addEventListener('input', function() {
    const newPassword = document.getElementById('new_password').value;
    const confirmPassword = this.value;

    if (newPassword !== confirmPassword) {
        this.setCustomValidity('Passwords do not match');
    } else {
        this.setCustomValidity('');
    }
});

// Form validation
document.querySelectorAll('form').forEach(form => {
    form.addEventListener('submit', function(e) {
        const submitButton = form.querySelector('button[type="submit"]');
        const originalText = submitButton.innerHTML;

        submitButton.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Processing...';
        submitButton.disabled = true;

        // Re-enable after 3 seconds as fallback
        setTimeout(() => {
            submitButton.innerHTML = originalText;
            submitButton.disabled = false;
        }, 3000);
    });
});
</script>

<?php require_once 'includes/footer.php'; ?>
