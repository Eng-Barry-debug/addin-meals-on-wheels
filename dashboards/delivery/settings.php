<?php
// settings.php - Delivery Personnel Settings

// Start session and check delivery authentication
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include database connection and functions
require_once '../../admin/includes/config.php';
require_once 'includes/functions.php';

// Check if user is logged in and is delivery personnel
if (!isset($_SESSION['user_id']) || ($_SESSION['user_role'] !== 'delivery' && $_SESSION['user_role'] !== 'driver')) {
    header('Location: ../../auth/login.php');
    exit();
}

// Set page title
$page_title = 'Settings';

// Initialize variables
$user_id = $_SESSION['user_id'];
$success_message = '';
$error_message = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_profile'])) {
        // Update profile information
        $name = sanitize($_POST['name']);
        $phone = sanitize($_POST['phone']);
        $email = sanitize($_POST['email']);

        try {
            $stmt = $pdo->prepare("UPDATE users SET name = ?, phone = ?, email = ? WHERE id = ? AND role = 'delivery'");
            $stmt->execute([$name, $phone, $email, $user_id]);

            $_SESSION['user_name'] = $name; // Update session
            $success_message = 'Profile updated successfully!';
        } catch (PDOException $e) {
            $error_message = 'Error updating profile: ' . $e->getMessage();
        }
    }

    elseif (isset($_POST['update_vehicle'])) {
        // Update vehicle information
        $vehicle_type = sanitize($_POST['vehicle_type']);
        $vehicle_model = sanitize($_POST['vehicle_model']);
        $license_plate = sanitize($_POST['license_plate']);
        $vehicle_capacity = sanitize($_POST['vehicle_capacity']);

        try {
            $stmt = $pdo->prepare("UPDATE delivery_personnel SET vehicle_type = ?, vehicle_model = ?, license_plate = ?, vehicle_capacity = ? WHERE user_id = ?");
            $stmt->execute([$vehicle_type, $vehicle_model, $license_plate, $vehicle_capacity, $user_id]);
            $success_message = 'Vehicle information updated successfully!';
        } catch (PDOException $e) {
            $error_message = 'Error updating vehicle information: ' . $e->getMessage();
        }
    }

    elseif (isset($_POST['update_preferences'])) {
        // Update notification preferences
        $email_notifications = isset($_POST['email_notifications']) ? 1 : 0;
        $sms_notifications = isset($_POST['sms_notifications']) ? 1 : 0;
        $push_notifications = isset($_POST['push_notifications']) ? 1 : 0;

        try {
            $stmt = $pdo->prepare("UPDATE delivery_personnel SET email_notifications = ?, sms_notifications = ?, push_notifications = ? WHERE user_id = ?");
            $stmt->execute([$email_notifications, $sms_notifications, $push_notifications, $user_id]);
            $success_message = 'Notification preferences updated successfully!';
        } catch (PDOException $e) {
            $error_message = 'Error updating preferences: ' . $e->getMessage();
        }
    }

    elseif (isset($_POST['update_availability'])) {
        // Update availability settings
        $availability_status = sanitize($_POST['availability_status']);
        $working_hours_start = sanitize($_POST['working_hours_start']);
        $working_hours_end = sanitize($_POST['working_hours_end']);
        $preferred_zones = sanitize($_POST['preferred_zones']);

        try {
            $stmt = $pdo->prepare("UPDATE delivery_personnel SET availability_status = ?, working_hours_start = ?, working_hours_end = ?, preferred_zones = ? WHERE user_id = ?");
            $stmt->execute([$availability_status, $working_hours_start, $working_hours_end, $preferred_zones, $user_id]);
            $success_message = 'Availability settings updated successfully!';
        } catch (PDOException $e) {
            $error_message = 'Error updating availability: ' . $e->getMessage();
        }
    }
}

// Get current user data
try {
    $stmt = $pdo->prepare("SELECT u.*, dp.* FROM users u LEFT JOIN delivery_personnel dp ON u.id = dp.user_id WHERE u.id = ?");
    $stmt->execute([$user_id]);
    $user_data = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error_message = 'Error loading user data: ' . $e->getMessage();
}

// Include activity logger
require_once '../../includes/ActivityLogger.php';
$activityLogger = new ActivityLogger($pdo);

// Log settings page access
$activityLogger->log('delivery', 'settings_view', 'Delivery person accessed settings', 'user', $user_id);

// Include header
require_once 'includes/header.php';
?>

<!-- Settings Header -->
<div class="bg-gradient-to-br from-primary via-primary-dark to-green-700 text-white">
    <div class="px-6 py-12">
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between">
            <div>
                <h1 class="text-4xl md:text-5xl font-bold mb-3">Settings</h1>
                <p class="text-xl opacity-90">Manage your account and preferences</p>
            </div>
            <div class="mt-6 lg:mt-0">
                <div class="flex items-center space-x-2 bg-white/10 backdrop-blur-sm rounded-lg px-3 py-2">
                    <i class="fas fa-cog text-yellow-300"></i>
                    <span class="text-sm font-medium">Account Settings</span>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Main Settings Content -->
<div class="px-6 py-8">
    <?php if ($success_message): ?>
        <div class="mb-6 p-4 bg-green-50 border-l-4 border-green-400 text-green-700 rounded-lg flex items-center">
            <i class="fas fa-check-circle mr-3"></i>
            <span><?php echo htmlspecialchars($success_message); ?></span>
        </div>
    <?php endif; ?>

    <?php if ($error_message): ?>
        <div class="mb-6 p-4 bg-red-50 border-l-4 border-red-400 text-red-700 rounded-lg flex items-center">
            <i class="fas fa-exclamation-circle mr-3"></i>
            <span><?php echo htmlspecialchars($error_message); ?></span>
        </div>
    <?php endif; ?>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
        <!-- Profile Settings -->
        <div class="bg-white rounded-xl shadow-lg p-6">
            <h3 class="text-xl font-bold text-gray-900 mb-6 flex items-center">
                <i class="fas fa-user mr-3 text-primary"></i>
                Profile Information
            </h3>

            <form method="POST" class="space-y-4">
                <div>
                    <label for="name" class="block text-sm font-semibold text-gray-700 mb-2">Full Name</label>
                    <input type="text" name="name" id="name" value="<?php echo htmlspecialchars($user_data['name'] ?? ''); ?>"
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent" required>
                </div>

                <div>
                    <label for="email" class="block text-sm font-semibold text-gray-700 mb-2">Email Address</label>
                    <input type="email" name="email" id="email" value="<?php echo htmlspecialchars($user_data['email'] ?? ''); ?>"
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent" required>
                </div>

                <div>
                    <label for="phone" class="block text-sm font-semibold text-gray-700 mb-2">Phone Number</label>
                    <input type="tel" name="phone" id="phone" value="<?php echo htmlspecialchars($user_data['phone'] ?? ''); ?>"
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent" required>
                </div>

                <div class="pt-4">
                    <button type="submit" name="update_profile"
                            class="w-full bg-primary hover:bg-primary-dark text-white px-6 py-3 rounded-lg font-semibold transition-colors duration-200 flex items-center justify-center">
                        <i class="fas fa-save mr-2"></i>
                        Update Profile
                    </button>
                </div>
            </form>
        </div>

        <!-- Vehicle Information -->
        <div class="bg-white rounded-xl shadow-lg p-6">
            <h3 class="text-xl font-bold text-gray-900 mb-6 flex items-center">
                <i class="fas fa-car mr-3 text-primary"></i>
                Vehicle Information
            </h3>

            <form method="POST" class="space-y-4">
                <div>
                    <label for="vehicle_type" class="block text-sm font-semibold text-gray-700 mb-2">Vehicle Type</label>
                    <select name="vehicle_type" id="vehicle_type"
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                        <option value="motorcycle" <?php echo ($user_data['vehicle_type'] ?? '') === 'motorcycle' ? 'selected' : ''; ?>>Motorcycle</option>
                        <option value="car" <?php echo ($user_data['vehicle_type'] ?? '') === 'car' ? 'selected' : ''; ?>>Car</option>
                        <option value="bicycle" <?php echo ($user_data['vehicle_type'] ?? '') === 'bicycle' ? 'selected' : ''; ?>>Bicycle</option>
                        <option value="van" <?php echo ($user_data['vehicle_type'] ?? '') === 'van' ? 'selected' : ''; ?>>Van</option>
                    </select>
                </div>

                <div>
                    <label for="vehicle_model" class="block text-sm font-semibold text-gray-700 mb-2">Vehicle Model</label>
                    <input type="text" name="vehicle_model" id="vehicle_model" value="<?php echo htmlspecialchars($user_data['vehicle_model'] ?? ''); ?>"
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent"
                           placeholder="e.g., Honda CB125">
                </div>

                <div>
                    <label for="license_plate" class="block text-sm font-semibold text-gray-700 mb-2">License Plate</label>
                    <input type="text" name="license_plate" id="license_plate" value="<?php echo htmlspecialchars($user_data['license_plate'] ?? ''); ?>"
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent"
                           placeholder="e.g., KCB 123D">
                </div>

                <div>
                    <label for="vehicle_capacity" class="block text-sm font-semibold text-gray-700 mb-2">Vehicle Capacity (kg)</label>
                    <input type="number" name="vehicle_capacity" id="vehicle_capacity" value="<?php echo htmlspecialchars($user_data['vehicle_capacity'] ?? ''); ?>"
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent"
                           placeholder="e.g., 50">
                </div>

                <div class="pt-4">
                    <button type="submit" name="update_vehicle"
                            class="w-full bg-primary hover:bg-primary-dark text-white px-6 py-3 rounded-lg font-semibold transition-colors duration-200 flex items-center justify-center">
                        <i class="fas fa-save mr-2"></i>
                        Update Vehicle Info
                    </button>
                </div>
            </form>
        </div>

        <!-- Notification Preferences -->
        <div class="bg-white rounded-xl shadow-lg p-6">
            <h3 class="text-xl font-bold text-gray-900 mb-6 flex items-center">
                <i class="fas fa-bell mr-3 text-primary"></i>
                Notification Preferences
            </h3>

            <form method="POST" class="space-y-4">
                <div class="space-y-3">
                    <label class="flex items-center">
                        <input type="checkbox" name="email_notifications" value="1" <?php echo ($user_data['email_notifications'] ?? 0) ? 'checked' : ''; ?>
                               class="rounded border-gray-300 text-primary focus:ring-primary">
                        <span class="ml-3 text-sm font-medium text-gray-700">Email Notifications</span>
                    </label>

                    <label class="flex items-center">
                        <input type="checkbox" name="sms_notifications" value="1" <?php echo ($user_data['sms_notifications'] ?? 0) ? 'checked' : ''; ?>
                               class="rounded border-gray-300 text-primary focus:ring-primary">
                        <span class="ml-3 text-sm font-medium text-gray-700">SMS Notifications</span>
                    </label>

                    <label class="flex items-center">
                        <input type="checkbox" name="push_notifications" value="1" <?php echo ($user_data['push_notifications'] ?? 0) ? 'checked' : ''; ?>
                               class="rounded border-gray-300 text-primary focus:ring-primary">
                        <span class="ml-3 text-sm font-medium text-gray-700">Push Notifications</span>
                    </label>
                </div>

                <div class="pt-4">
                    <button type="submit" name="update_preferences"
                            class="w-full bg-primary hover:bg-primary-dark text-white px-6 py-3 rounded-lg font-semibold transition-colors duration-200 flex items-center justify-center">
                        <i class="fas fa-save mr-2"></i>
                        Update Preferences
                    </button>
                </div>
            </form>
        </div>

        <!-- Availability Settings -->
        <div class="bg-white rounded-xl shadow-lg p-6">
            <h3 class="text-xl font-bold text-gray-900 mb-6 flex items-center">
                <i class="fas fa-clock mr-3 text-primary"></i>
                Availability Settings
            </h3>

            <form method="POST" class="space-y-4">
                <div>
                    <label for="availability_status" class="block text-sm font-semibold text-gray-700 mb-2">Availability Status</label>
                    <select name="availability_status" id="availability_status"
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                        <option value="available" <?php echo ($user_data['availability_status'] ?? 'available') === 'available' ? 'selected' : ''; ?>>Available</option>
                        <option value="busy" <?php echo ($user_data['availability_status'] ?? 'available') === 'busy' ? 'selected' : ''; ?>>Busy</option>
                        <option value="offline" <?php echo ($user_data['availability_status'] ?? 'available') === 'offline' ? 'selected' : ''; ?>>Offline</option>
                    </select>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label for="working_hours_start" class="block text-sm font-semibold text-gray-700 mb-2">Working Hours Start</label>
                        <input type="time" name="working_hours_start" id="working_hours_start" value="<?php echo htmlspecialchars($user_data['working_hours_start'] ?? '08:00'); ?>"
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                    </div>

                    <div>
                        <label for="working_hours_end" class="block text-sm font-semibold text-gray-700 mb-2">Working Hours End</label>
                        <input type="time" name="working_hours_end" id="working_hours_end" value="<?php echo htmlspecialchars($user_data['working_hours_end'] ?? '20:00'); ?>"
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                    </div>
                </div>

                <div>
                    <label for="preferred_zones" class="block text-sm font-semibold text-gray-700 mb-2">Preferred Delivery Zones</label>
                    <input type="text" name="preferred_zones" id="preferred_zones" value="<?php echo htmlspecialchars($user_data['preferred_zones'] ?? ''); ?>"
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent"
                           placeholder="e.g., Nairobi CBD, Westlands, Kilimani">
                </div>

                <div class="pt-4">
                    <button type="submit" name="update_availability"
                            class="w-full bg-primary hover:bg-primary-dark text-white px-6 py-3 rounded-lg font-semibold transition-colors duration-200 flex items-center justify-center">
                        <i class="fas fa-save mr-2"></i>
                        Update Availability
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Account Security -->
    <div class="mt-8">
        <div class="bg-white rounded-xl shadow-lg p-6">
            <h3 class="text-xl font-bold text-gray-900 mb-6 flex items-center">
                <i class="fas fa-shield-alt mr-3 text-primary"></i>
                Account Security
            </h3>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <h4 class="font-semibold text-gray-800 mb-2">Password</h4>
                    <p class="text-sm text-gray-600 mb-4">Last updated: <?php echo date('F j, Y'); ?></p>
                    <a href="#" class="bg-gray-100 hover:bg-gray-200 text-gray-700 px-4 py-2 rounded-lg font-medium transition-colors">
                        Change Password
                    </a>
                </div>

                <div>
                    <h4 class="font-semibold text-gray-800 mb-2">Two-Factor Authentication</h4>
                    <p class="text-sm text-gray-600 mb-4">Add an extra layer of security to your account</p>
                    <button class="bg-primary hover:bg-primary-dark text-white px-4 py-2 rounded-lg font-medium transition-colors">
                        Enable 2FA
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Danger Zone -->
    <div class="mt-8">
        <div class="bg-red-50 border border-red-200 rounded-xl p-6">
            <h3 class="text-xl font-bold text-red-900 mb-4 flex items-center">
                <i class="fas fa-exclamation-triangle mr-3 text-red-600"></i>
                Danger Zone
            </h3>

            <div class="space-y-4">
                <div>
                    <h4 class="font-semibold text-red-800 mb-2">Deactivate Account</h4>
                    <p class="text-sm text-red-700 mb-4">Temporarily disable your account. You can reactivate it anytime.</p>
                    <button class="bg-red-100 hover:bg-red-200 text-red-700 px-4 py-2 rounded-lg font-medium transition-colors border border-red-300">
                        Deactivate Account
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
