<?php
// settings.php - Ambassador Settings

// Start session and check ambassador authentication
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include database connection and functions
require_once '../../includes/config.php';
require_once 'includes/functions.php';

// Check if user is logged in and is ambassador
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'ambassador') {
    header('Location: ../../auth/login.php');
    exit();
}

// Set page title
$page_title = 'Ambassador Settings';

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
        $bio = sanitize($_POST['bio']);

        try {
            $stmt = $pdo->prepare("UPDATE users SET name = ?, phone = ?, email = ? WHERE id = ? AND role = 'ambassador'");
            $stmt->execute([$name, $phone, $email, $user_id]);

            // Update ambassador specific data
            $stmt = $pdo->prepare("UPDATE ambassadors SET bio = ? WHERE user_id = ?");
            $stmt->execute([$bio, $user_id]);

            $_SESSION['user_name'] = $name; // Update session
            $success_message = 'Profile updated successfully!';
        } catch (PDOException $e) {
            $error_message = 'Error updating profile: ' . $e->getMessage();
        }
    }

    elseif (isset($_POST['update_social'])) {
        // Update social media settings
        $social_media = sanitize($_POST['social_media']);
        $website = sanitize($_POST['website']);
        $preferred_content = sanitize($_POST['preferred_content']);

        try {
            $stmt = $pdo->prepare("UPDATE ambassadors SET social_media = ?, website = ?, preferred_content = ? WHERE user_id = ?");
            $stmt->execute([$social_media, $website, $preferred_content, $user_id]);
            $success_message = 'Social media settings updated successfully!';
        } catch (PDOException $e) {
            $error_message = 'Error updating social media settings: ' . $e->getMessage();
        }
    }

    elseif (isset($_POST['update_preferences'])) {
        // Update notification preferences
        $email_notifications = isset($_POST['email_notifications']) ? 1 : 0;
        $sms_notifications = isset($_POST['sms_notifications']) ? 1 : 0;
        $push_notifications = isset($_POST['push_notifications']) ? 1 : 0;

        try {
            $stmt = $pdo->prepare("UPDATE ambassadors SET email_notifications = ?, sms_notifications = ?, push_notifications = ? WHERE user_id = ?");
            $stmt->execute([$email_notifications, $sms_notifications, $push_notifications, $user_id]);
            $success_message = 'Notification preferences updated successfully!';
        } catch (PDOException $e) {
            $error_message = 'Error updating preferences: ' . $e->getMessage();
        }
    }
}

// Get current user data
try {
    $stmt = $pdo->prepare("SELECT u.*, a.* FROM users u LEFT JOIN ambassadors a ON u.id = a.user_id WHERE u.id = ?");
    $stmt->execute([$user_id]);
    $user_data = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error_message = 'Error loading user data: ' . $e->getMessage();
}

// Get referral stats
try {
    $stmt = $pdo->prepare("SELECT COUNT(*) as total_referrals, SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_referrals FROM referrals WHERE ambassador_id = ?");
    $stmt->execute([$user_id]);
    $referral_stats = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $referral_stats = ['total_referrals' => 0, 'completed_referrals' => 0];
}

// Include activity logger
require_once '../../includes/ActivityLogger.php';
$activityLogger = new ActivityLogger($pdo);

// Log settings page access
$activityLogger->log('ambassador', 'settings_view', 'Ambassador accessed settings', 'user', $user_id);

// Include header
require_once 'includes/header.php';
?>

<!-- Settings Header -->
<div class="bg-gradient-to-br from-purple-600 via-purple-700 to-pink-600 text-white">
    <div class="px-6 py-12">
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between">
            <div>
                <h1 class="text-4xl md:text-5xl font-bold mb-3">Ambassador Settings</h1>
                <p class="text-xl opacity-90">Manage your ambassador profile and preferences</p>
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

    <!-- Referral Stats Overview -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        <div class="bg-white rounded-xl shadow-lg p-6 border-l-4 border-purple-500">
            <div class="flex items-center">
                <div class="p-3 bg-purple-100 rounded-lg">
                    <i class="fas fa-users text-2xl text-purple-600"></i>
                </div>
                <div class="ml-4">
                    <h3 class="text-2xl font-bold text-gray-900"><?php echo number_format($referral_stats['total_referrals'] ?? 0); ?></h3>
                    <p class="text-gray-600 font-medium">Total Referrals</p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-lg p-6 border-l-4 border-green-500">
            <div class="flex items-center">
                <div class="p-3 bg-green-100 rounded-lg">
                    <i class="fas fa-check-circle text-2xl text-green-600"></i>
                </div>
                <div class="ml-4">
                    <h3 class="text-2xl font-bold text-gray-900"><?php echo number_format($referral_stats['completed_referrals'] ?? 0); ?></h3>
                    <p class="text-gray-600 font-medium">Completed</p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-lg p-6 border-l-4 border-yellow-500">
            <div class="flex items-center">
                <div class="p-3 bg-yellow-100 rounded-lg">
                    <i class="fas fa-percentage text-2xl text-yellow-600"></i>
                </div>
                <div class="ml-4">
                    <h3 class="text-2xl font-bold text-gray-900">
                        <?php
                        $completion_rate = $referral_stats['total_referrals'] > 0 ?
                            round(($referral_stats['completed_referrals'] / $referral_stats['total_referrals']) * 100, 1) : 0;
                        echo $completion_rate . '%';
                        ?>
                    </h3>
                    <p class="text-gray-600 font-medium">Success Rate</p>
                </div>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
        <!-- Profile Settings -->
        <div class="bg-white rounded-xl shadow-lg p-6">
            <h3 class="text-xl font-bold text-gray-900 mb-6 flex items-center">
                <i class="fas fa-user mr-3 text-purple-600"></i>
                Profile Information
            </h3>

            <form method="POST" class="space-y-4">
                <div>
                    <label for="name" class="block text-sm font-semibold text-gray-700 mb-2">Full Name</label>
                    <input type="text" name="name" id="name" value="<?php echo htmlspecialchars($user_data['name'] ?? ''); ?>"
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent" required>
                </div>

                <div>
                    <label for="email" class="block text-sm font-semibold text-gray-700 mb-2">Email Address</label>
                    <input type="email" name="email" id="email" value="<?php echo htmlspecialchars($user_data['email'] ?? ''); ?>"
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent" required>
                </div>

                <div>
                    <label for="phone" class="block text-sm font-semibold text-gray-700 mb-2">Phone Number</label>
                    <input type="tel" name="phone" id="phone" value="<?php echo htmlspecialchars($user_data['phone'] ?? ''); ?>"
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent" required>
                </div>

                <div>
                    <label for="bio" class="block text-sm font-semibold text-gray-700 mb-2">Bio</label>
                    <textarea name="bio" id="bio" rows="4"
                              class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                              placeholder="Tell us about yourself and your ambassador journey..."><?php echo htmlspecialchars($user_data['bio'] ?? ''); ?></textarea>
                </div>

                <div class="pt-4">
                    <button type="submit" name="update_profile"
                            class="w-full bg-purple-600 hover:bg-purple-700 text-white px-6 py-3 rounded-lg font-semibold transition-colors duration-200 flex items-center justify-center">
                        <i class="fas fa-save mr-2"></i>
                        Update Profile
                    </button>
                </div>
            </form>
        </div>

        <!-- Social Media Settings -->
        <div class="bg-white rounded-xl shadow-lg p-6">
            <h3 class="text-xl font-bold text-gray-900 mb-6 flex items-center">
                <i class="fas fa-share-alt mr-3 text-purple-600"></i>
                Social Media & Content
            </h3>

            <form method="POST" class="space-y-4">
                <div>
                    <label for="social_media" class="block text-sm font-semibold text-gray-700 mb-2">Social Media Handle</label>
                    <input type="text" name="social_media" id="social_media" value="<?php echo htmlspecialchars($user_data['social_media'] ?? ''); ?>"
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                           placeholder="@yourhandle">
                </div>

                <div>
                    <label for="website" class="block text-sm font-semibold text-gray-700 mb-2">Website/Blog (Optional)</label>
                    <input type="url" name="website" id="website" value="<?php echo htmlspecialchars($user_data['website'] ?? ''); ?>"
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                           placeholder="https://yourwebsite.com">
                </div>

                <div>
                    <label for="preferred_content" class="block text-sm font-semibold text-gray-700 mb-2">Preferred Content Type</label>
                    <select name="preferred_content" id="preferred_content"
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                        <option value="food_reviews" <?php echo ($user_data['preferred_content'] ?? '') === 'food_reviews' ? 'selected' : ''; ?>>Food Reviews</option>
                        <option value="restaurant_visits" <?php echo ($user_data['preferred_content'] ?? '') === 'restaurant_visits' ? 'selected' : ''; ?>>Restaurant Visits</option>
                        <option value="cooking_tips" <?php echo ($user_data['preferred_content'] ?? '') === 'cooking_tips' ? 'selected' : ''; ?>>Cooking Tips</option>
                        <option value="lifestyle" <?php echo ($user_data['preferred_content'] ?? '') === 'lifestyle' ? 'selected' : ''; ?>>Lifestyle</option>
                        <option value="all" <?php echo ($user_data['preferred_content'] ?? '') === 'all' ? 'selected' : ''; ?>>All Types</option>
                    </select>
                </div>

                <div class="pt-4">
                    <button type="submit" name="update_social"
                            class="w-full bg-purple-600 hover:bg-purple-700 text-white px-6 py-3 rounded-lg font-semibold transition-colors duration-200 flex items-center justify-center">
                        <i class="fas fa-save mr-2"></i>
                        Update Social Settings
                    </button>
                </div>
            </form>
        </div>

        <!-- Notification Preferences -->
        <div class="bg-white rounded-xl shadow-lg p-6">
            <h3 class="text-xl font-bold text-gray-900 mb-6 flex items-center">
                <i class="fas fa-bell mr-3 text-purple-600"></i>
                Notification Preferences
            </h3>

            <form method="POST" class="space-y-4">
                <div class="space-y-3">
                    <label class="flex items-center">
                        <input type="checkbox" name="email_notifications" value="1" <?php echo ($user_data['email_notifications'] ?? 0) ? 'checked' : ''; ?>
                               class="rounded border-gray-300 text-purple-600 focus:ring-purple-500">
                        <span class="ml-3 text-sm font-medium text-gray-700">Email Notifications</span>
                    </label>

                    <label class="flex items-center">
                        <input type="checkbox" name="sms_notifications" value="1" <?php echo ($user_data['sms_notifications'] ?? 0) ? 'checked' : ''; ?>
                               class="rounded border-gray-300 text-purple-600 focus:ring-purple-500">
                        <span class="ml-3 text-sm font-medium text-gray-700">SMS Notifications</span>
                    </label>

                    <label class="flex items-center">
                        <input type="checkbox" name="push_notifications" value="1" <?php echo ($user_data['push_notifications'] ?? 0) ? 'checked' : ''; ?>
                               class="rounded border-gray-300 text-purple-600 focus:ring-purple-500">
                        <span class="ml-3 text-sm font-medium text-gray-700">Push Notifications</span>
                    </label>
                </div>

                <div class="pt-4">
                    <button type="submit" name="update_preferences"
                            class="w-full bg-purple-600 hover:bg-purple-700 text-white px-6 py-3 rounded-lg font-semibold transition-colors duration-200 flex items-center justify-center">
                        <i class="fas fa-save mr-2"></i>
                        Update Preferences
                    </button>
                </div>
            </form>
        </div>

        <!-- Ambassador Status -->
        <div class="bg-white rounded-xl shadow-lg p-6">
            <h3 class="text-xl font-bold text-gray-900 mb-6 flex items-center">
                <i class="fas fa-star mr-3 text-purple-600"></i>
                Ambassador Status
            </h3>

            <div class="space-y-4">
                <div class="flex items-center justify-between p-3 bg-green-50 rounded-lg">
                    <div class="flex items-center">
                        <div class="w-3 h-3 bg-green-500 rounded-full mr-3"></div>
                        <span class="font-medium text-green-800">Status: <?php echo ucfirst($user_data['status'] ?? 'pending'); ?></span>
                    </div>
                    <span class="px-3 py-1 bg-green-100 text-green-800 text-sm font-medium rounded-full">
                        <?php echo ucfirst($user_data['status'] ?? 'pending'); ?>
                    </span>
                </div>

                <div class="flex items-center justify-between p-3 bg-blue-50 rounded-lg">
                    <span class="font-medium text-blue-800">Experience Level</span>
                    <span class="text-sm text-blue-600 capitalize"><?php echo str_replace('_', ' ', $user_data['experience'] ?? 'none'); ?></span>
                </div>

                <div class="flex items-center justify-between p-3 bg-purple-50 rounded-lg">
                    <span class="font-medium text-purple-800">Member Since</span>
                    <span class="text-sm text-purple-600"><?php echo date('M Y', strtotime($user_data['application_date'] ?? 'now')); ?></span>
                </div>

                <div class="pt-4 border-t border-gray-200">
                    <a href="index.php" class="text-purple-600 hover:text-purple-700 font-medium text-sm flex items-center">
                        <i class="fas fa-arrow-left mr-2"></i>
                        Back to Dashboard
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Account Security -->
    <div class="mt-8">
        <div class="bg-white rounded-xl shadow-lg p-6">
            <h3 class="text-xl font-bold text-gray-900 mb-6 flex items-center">
                <i class="fas fa-shield-alt mr-3 text-purple-600"></i>
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
                    <button class="bg-purple-600 hover:bg-purple-700 text-white px-4 py-2 rounded-lg font-medium transition-colors">
                        Enable 2FA
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
