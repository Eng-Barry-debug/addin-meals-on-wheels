<?php
// Start session and check admin authentication
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include database connection
require_once '../includes/config.php';

// Debug: Check if PDO is available
if (!isset($pdo)) {
    die('PDO connection not available. Please check database configuration.');
}

// Check if users table exists
try {
    $checkTableStmt = $pdo->query("SHOW TABLES LIKE 'users'");
    if ($checkTableStmt->rowCount() == 0) {
        die('Users table does not exist. Please run database migrations.');
    }
} catch (PDOException $e) {
    die('Database error: ' . $e->getMessage());
}

// Check if user exists in session
if (!isset($_SESSION['user_id'])) {
    die('User ID not found in session. Please log in again.');
}

// Include activity logger
require_once '../includes/ActivityLogger.php';
$activityLogger = new ActivityLogger($pdo);

// Get current user information
try {
    $userStmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $userStmt->execute([$_SESSION['user_id']]);
    $user = $userStmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        header('Location: dashboard.php');
        exit();
    }
} catch (PDOException $e) {
    $error = "Error fetching user data: " . $e->getMessage();
}

// Handle form submissions
$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_profile'])) {
        $name = trim($_POST['name']);
        $email = trim($_POST['email']);
        $phone = trim($_POST['phone']);

        if (empty($name) || empty($email)) {
            $error = 'Name and email are required';
        } else {
            try {
                $stmt = $pdo->prepare("UPDATE users SET name = ?, email = ?, phone = ? WHERE id = ?");
                $stmt->execute([$name, $email, $phone, $_SESSION['user_id']]);

                // Update session
                $_SESSION['username'] = $name;
                $_SESSION['user_email'] = $email;

                // Log activity
                $activityLogger->log('user', 'updated', "Updated profile information", 'user', $_SESSION['user_id']);

                $success = 'Profile updated successfully';
            } catch (PDOException $e) {
                $error = 'Error updating profile: ' . $e->getMessage();
            }
        }
    }

    if (isset($_POST['upload_image'])) {
        // Debug output
        error_log("Upload attempt - Files: " . print_r($_FILES, true));
        error_log("Upload attempt - POST: " . print_r($_POST, true));

        if (isset($_FILES['profile_image'])) {
            error_log("File upload error code: " . $_FILES['profile_image']['error']);

            if ($_FILES['profile_image']['error'] === UPLOAD_ERR_OK) {
                $file = $_FILES['profile_image'];
                $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
                $maxSize = 2 * 1024 * 1024; // 2MB (reduced from 5MB to fit within PHP limits)

                error_log("File details - Type: {$file['type']}, Size: {$file['size']}, Name: {$file['name']}");

                if (!in_array($file['type'], $allowedTypes)) {
                    $error = 'Only JPG, PNG, and GIF images are allowed';
                } elseif ($file['size'] > $maxSize) {
                    $error = 'Image size must be less than 5MB';
                } else {
                    // Create uploads directory if it doesn't exist
                    $uploadDir = '../uploads/profile_images/';
                    if (!is_dir($uploadDir)) {
                        mkdir($uploadDir, 0755, true);
                        error_log("Created upload directory: $uploadDir");
                    }

                    // Generate unique filename
                    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                    $filename = 'profile_' . $_SESSION['user_id'] . '_' . time() . '.' . $extension;
                    $filepath = $uploadDir . $filename;

                    error_log("Attempting to move file to: $filepath");

                    if (move_uploaded_file($file['tmp_name'], $filepath)) {
                        error_log("File moved successfully");
                        try {
                            // Update database with new image path
                            $stmt = $pdo->prepare("UPDATE users SET profile_image = ? WHERE id = ?");
                            $result = $stmt->execute([$filename, $_SESSION['user_id']]);

                            error_log("Database update result: " . ($result ? 'SUCCESS' : 'FAILED'));

                            // Log activity
                            $activityLogger->log('user', 'updated', "Updated profile image", 'user', $_SESSION['user_id']);

                            $success = 'Profile image updated successfully';

                            // Refresh user data to show new image immediately
                            $userStmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
                            $userStmt->execute([$_SESSION['user_id']]);
                            $user = $userStmt->fetch(PDO::FETCH_ASSOC);

                        } catch (PDOException $e) {
                            $error = 'Error updating profile image: ' . $e->getMessage();
                            error_log("PDO Error: " . $e->getMessage());
                        }
                    } else {
                        $error = 'Error uploading image - could not move file';
                        error_log("move_uploaded_file failed for: $filepath");
                    }
                }
            } elseif ($_FILES['profile_image']['error'] === UPLOAD_ERR_NO_FILE) {
                $error = 'Please select an image to upload';
                error_log("No file was uploaded");
            } elseif ($_FILES['profile_image']['error'] === UPLOAD_ERR_INI_SIZE) {
                $error = 'File size exceeds server limit';
                error_log("File size exceeds upload_max_filesize");
            } elseif ($_FILES['profile_image']['error'] === UPLOAD_ERR_FORM_SIZE) {
                $error = 'File size exceeds form limit';
                error_log("File size exceeds MAX_FILE_SIZE");
            } elseif ($_FILES['profile_image']['error'] === UPLOAD_ERR_PARTIAL) {
                $error = 'File was only partially uploaded';
                error_log("File upload was partial");
            } elseif ($_FILES['profile_image']['error'] === UPLOAD_ERR_NO_TMP_DIR) {
                $error = 'Temporary upload directory missing';
                error_log("No temporary directory");
            } elseif ($_FILES['profile_image']['error'] === UPLOAD_ERR_CANT_WRITE) {
                $error = 'Failed to write file to disk';
                error_log("Cannot write to disk");
            } elseif ($_FILES['profile_image']['error'] === UPLOAD_ERR_EXTENSION) {
                $error = 'File upload stopped by extension';
                error_log("Upload stopped by PHP extension");
            } else {
                $error = 'Upload error: ' . $_FILES['profile_image']['error'];
                error_log("Upload error code: " . $_FILES['profile_image']['error']);
            }
        } else {
            $error = 'No file data received';
            error_log("No profile_image in FILES array");
        }
    }

    if (isset($_POST['remove_image'])) {
        try {
            // Get current image
            $stmt = $pdo->prepare("SELECT profile_image FROM users WHERE id = ?");
            $stmt->execute([$_SESSION['user_id']]);
            $currentImage = $stmt->fetchColumn();

            if ($currentImage) {
                // Delete file
                $filepath = '../uploads/profile_images/' . $currentImage;
                if (file_exists($filepath)) {
                    unlink($filepath);
                }

                // Update database
                $stmt = $pdo->prepare("UPDATE users SET profile_image = NULL WHERE id = ?");
                $stmt->execute([$_SESSION['user_id']]);

                // Log activity
                $activityLogger->log('user', 'updated', "Removed profile image", 'user', $_SESSION['user_id']);

                $success = 'Profile image removed successfully';
            }
        } catch (PDOException $e) {
            $error = 'Error removing profile image: ' . $e->getMessage();
        }
    }

    if (isset($_POST['change_password'])) {
        $current_password = $_POST['current_password'];
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];

        if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
            $error = 'All password fields are required';
        } elseif ($new_password !== $confirm_password) {
            $error = 'New passwords do not match';
        } elseif (strlen($new_password) < 6) {
            $error = 'Password must be at least 6 characters long';
        } else {
            try {
                // Verify current password
                $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
                $stmt->execute([$_SESSION['user_id']]);
                $user_data = $stmt->fetch(PDO::FETCH_ASSOC);

                if (!password_verify($current_password, $user_data['password'])) {
                    $error = 'Current password is incorrect';
                } else {
                    // Update password
                    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                    $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
                    $stmt->execute([$hashed_password, $_SESSION['user_id']]);

                    // Log activity
                    $activityLogger->log('user', 'updated', "Changed account password", 'user', $_SESSION['user_id']);

                    $success = 'Password changed successfully';
                }
            } catch (PDOException $e) {
                $error = 'Error changing password: ' . $e->getMessage();
            }
        }
    }
}

// Get user's recent activities
try {
    $userActivitiesStmt = $pdo->prepare("
        SELECT al.*, u.name as user_name
        FROM activity_logs al
        LEFT JOIN users u ON al.user_id = u.id
        WHERE al.user_id = ?
        ORDER BY al.created_at DESC
        LIMIT 10
    ");
    $userActivitiesStmt->execute([$_SESSION['user_id']]);
    $userActivities = $userActivitiesStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $userActivities = [];
}

// Set page title
$page_title = 'My Profile';

// Include header
require_once 'includes/header.php';
?>

<!-- Profile Header -->
<div class="bg-gradient-to-br from-primary via-primary-dark to-secondary text-white">
    <div class="container mx-auto px-6 py-12">
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between">
            <div class="flex items-center space-x-6">
                <div class="h-20 w-20 rounded-full bg-white/20 backdrop-blur-sm flex items-center justify-center text-white font-bold text-3xl shadow-lg overflow-hidden">
                    <?php if (!empty($user['profile_image'])): ?>
                        <img src="../uploads/profile_images/<?php echo htmlspecialchars($user['profile_image']); ?>"
                             alt="Profile Image"
                             class="w-full h-full object-cover">
                    <?php else: ?>
                        <?php echo strtoupper(substr($user['name'], 0, 1)); ?>
                    <?php endif; ?>
                </div>
                <div>
                    <h1 class="text-3xl md:text-4xl font-bold mb-2"><?php echo htmlspecialchars($user['name']); ?></h1>
                    <p class="text-lg opacity-90 mb-2"><?php echo htmlspecialchars($user['email']); ?></p>
                    <div class="flex items-center space-x-4">
                        <span class="px-3 py-1 bg-white/20 backdrop-blur-sm rounded-full text-sm font-medium">
                            <?php echo ucfirst($user['role']); ?>
                        </span>
                        <span class="px-3 py-1 bg-white/20 backdrop-blur-sm rounded-full text-sm font-medium">
                            <?php echo ucfirst($user['status']); ?>
                        </span>
                    </div>
                </div>
            </div>
            <div class="mt-6 lg:mt-0">
                <div class="flex items-center space-x-4 text-sm">
                    <div class="flex items-center space-x-2 bg-white/10 backdrop-blur-sm rounded-lg px-3 py-2">
                        <i class="fas fa-calendar-alt text-yellow-300"></i>
                        <span>Joined <?php echo date('M Y', strtotime($user['created_at'])); ?></span>
                    </div>
                    <div class="flex items-center space-x-2 bg-white/10 backdrop-blur-sm rounded-lg px-3 py-2">
                        <i class="fas fa-clock text-blue-300"></i>
                        <span>Last seen: <?php echo date('M j, g:i A'); ?></span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Main Content -->
<div class="container mx-auto px-6 py-8">
    <!-- Alerts -->
    <?php if ($error): ?>
        <div class="mb-6 p-4 bg-red-50 border-l-4 border-red-400 text-red-700 rounded-lg flex items-center">
            <i class="fas fa-exclamation-circle mr-3"></i>
            <div>
                <p class="font-semibold">Error</p>
                <p><?php echo htmlspecialchars($error); ?></p>
            </div>
            <button onclick="this.parentElement.remove()" class="ml-auto text-gray-400 hover:text-gray-600">
                <i class="fas fa-times"></i>
            </button>
        </div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="mb-6 p-4 bg-green-50 border-l-4 border-green-400 text-green-700 rounded-lg flex items-center">
            <i class="fas fa-check-circle mr-3"></i>
            <div>
                <p class="font-semibold">Success</p>
                <p><?php echo htmlspecialchars($success); ?></p>
            </div>
            <button onclick="this.parentElement.remove()" class="ml-auto text-gray-400 hover:text-gray-600">
                <i class="fas fa-times"></i>
            </button>
        </div>
    <?php endif; ?>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Left Column - Profile Information -->
        <div class="lg:col-span-2 space-y-8">
            <!-- Profile Image Card -->
            <div class="bg-white rounded-xl shadow-lg p-6">
                <h3 class="text-xl font-bold text-gray-900 mb-6 flex items-center">
                    <i class="fas fa-camera mr-3 text-primary"></i>
                    Profile Image
                </h3>

                <div class="flex items-center space-x-6 mb-6">
                    <div class="h-24 w-24 rounded-full overflow-hidden bg-gray-200 flex items-center justify-center">
                        <?php if (!empty($user['profile_image'])): ?>
                            <img src="../uploads/profile_images/<?php echo htmlspecialchars($user['profile_image']); ?>"
                                 alt="Profile Image"
                                 class="w-full h-full object-cover">
                        <?php else: ?>
                            <div class="text-gray-400 text-2xl font-bold">
                                <?php echo strtoupper(substr($user['name'], 0, 1)); ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="flex-1">
                        <form method="POST" enctype="multipart/form-data" class="space-y-4">
                            <div>
                                <label for="profile_image" class="block text-sm font-semibold text-gray-700 mb-2">Upload New Image</label>
                                <input type="file" id="profile_image" name="profile_image" accept="image/jpeg,image/png,image/gif"
                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent transition-colors" required>
                                <p class="text-xs text-gray-500 mt-1">Supported formats: JPG, PNG, GIF. Max size: 2MB</p>
                            </div>
                            <div class="flex space-x-3">
                                <button type="submit" name="upload_image"
                                        class="bg-primary hover:bg-primary-dark text-white px-4 py-2 rounded-lg font-medium transition-colors duration-200 flex items-center">
                                    <i class="fas fa-upload mr-2"></i>
                                    Upload Image
                                </button>
                                <?php if (!empty($user['profile_image'])): ?>
                                    <button type="submit" name="remove_image"
                                            class="bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded-lg font-medium transition-colors duration-200 flex items-center"
                                            onclick="return confirm('Are you sure you want to remove your profile image?')">
                                        <i class="fas fa-trash mr-2"></i>
                                        Remove Image
                                    </button>
                                <?php endif; ?>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Profile Information Card -->
            <div class="bg-white rounded-xl shadow-lg p-6">
                <h3 class="text-xl font-bold text-gray-900 mb-6 flex items-center">
                    <i class="fas fa-user-circle mr-3 text-primary"></i>
                    Profile Information
                </h3>

                <form method="POST" class="space-y-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label for="name" class="block text-sm font-semibold text-gray-700 mb-2">Full Name</label>
                            <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($user['name']); ?>" required
                                   class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent transition-colors">
                        </div>

                        <div>
                            <label for="email" class="block text-sm font-semibold text-gray-700 mb-2">Email Address</label>
                            <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required
                                   class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent transition-colors">
                        </div>

                        <div>
                            <label for="phone" class="block text-sm font-semibold text-gray-700 mb-2">Phone Number</label>
                            <input type="tel" id="phone" name="phone" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>"
                                   class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent transition-colors">
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Account Role</label>
                            <div class="px-4 py-3 bg-gray-50 border border-gray-300 rounded-lg">
                                <span class="text-gray-900 font-medium"><?php echo ucfirst($user['role']); ?></span>
                            </div>
                        </div>
                    </div>

                    <div class="flex justify-end pt-4">
                        <button type="submit" name="update_profile"
                                class="bg-primary hover:bg-primary-dark text-white px-6 py-3 rounded-lg font-semibold transition-colors duration-200 flex items-center">
                            <i class="fas fa-save mr-2"></i>
                            Update Profile
                        </button>
                    </div>
                </form>
            </div>

            <!-- Change Password Card -->
            <div class="bg-white rounded-xl shadow-lg p-6">
                <h3 class="text-xl font-bold text-gray-900 mb-6 flex items-center">
                    <i class="fas fa-lock mr-3 text-primary"></i>
                    Change Password
                </h3>

                <form method="POST" class="space-y-6">
                    <div class="space-y-4">
                        <div>
                            <label for="current_password" class="block text-sm font-semibold text-gray-700 mb-2">Current Password</label>
                            <input type="password" id="current_password" name="current_password" required
                                   class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent transition-colors">
                        </div>

                        <div>
                            <label for="new_password" class="block text-sm font-semibold text-gray-700 mb-2">New Password</label>
                            <input type="password" id="new_password" name="new_password" required minlength="6"
                                   class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent transition-colors">
                            <p class="text-xs text-gray-500 mt-1">Minimum 6 characters</p>
                        </div>

                        <div>
                            <label for="confirm_password" class="block text-sm font-semibold text-gray-700 mb-2">Confirm New Password</label>
                            <input type="password" id="confirm_password" name="confirm_password" required minlength="6"
                                   class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent transition-colors">
                        </div>
                    </div>

                    <div class="flex justify-end pt-4">
                        <button type="submit" name="change_password"
                                class="bg-primary hover:bg-primary-dark text-white px-6 py-3 rounded-lg font-semibold transition-colors duration-200 flex items-center">
                            <i class="fas fa-key mr-2"></i>
                            Change Password
                        </button>
                    </div>
                </form>
            </div>

            <!-- Recent Activity Card -->
            <div class="bg-white rounded-xl shadow-lg p-6">
                <h3 class="text-xl font-bold text-gray-900 mb-6 flex items-center">
                    <i class="fas fa-history mr-3 text-primary"></i>
                    Recent Activity
                </h3>

                <?php if (!empty($userActivities)): ?>
                    <div class="space-y-4">
                        <?php foreach ($userActivities as $activity): ?>
                            <?php
                            $activityIcon = $activityLogger->getActivityIcon($activity['activity_type'], $activity['activity_action']);
                            $activityColor = $activityLogger->getActivityColor($activity['activity_type']);
                            ?>
                            <div class="flex items-center space-x-4 p-4 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors duration-200">
                                <div class="w-10 h-10 bg-<?php echo $activityColor; ?>-100 rounded-full flex items-center justify-center">
                                    <i class="<?php echo $activityIcon; ?> text-<?php echo $activityColor; ?>-600"></i>
                                </div>
                                <div class="flex-1">
                                    <p class="font-medium text-gray-900"><?php echo htmlspecialchars($activity['description']); ?></p>
                                    <p class="text-sm text-gray-600"><?php echo date('M j, Y g:i A', strtotime($activity['created_at'])); ?></p>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="text-center py-8">
                        <i class="fas fa-history text-4xl text-gray-300 mb-3"></i>
                        <p class="text-gray-500">No recent activity found.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Right Column - Account Overview & Quick Stats -->
        <div class="space-y-6">
            <!-- Account Overview -->
            <div class="bg-white rounded-xl shadow-lg p-6">
                <h3 class="text-lg font-bold text-gray-900 mb-4 flex items-center">
                    <i class="fas fa-info-circle mr-2 text-blue-500"></i>
                    Account Overview
                </h3>

                <div class="space-y-4">
                    <div class="flex items-center justify-between">
                        <span class="text-gray-600">Account Status</span>
                        <span class="px-3 py-1 text-sm font-medium rounded-full <?php echo $user['status'] === 'active' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                            <?php echo ucfirst($user['status']); ?>
                        </span>
                    </div>

                    <div class="flex items-center justify-between">
                        <span class="text-gray-600">Member Since</span>
                        <span class="font-medium text-gray-900"><?php echo date('M Y', strtotime($user['created_at'])); ?></span>
                    </div>

                    <div class="flex items-center justify-between">
                        <span class="text-gray-600">Last Updated</span>
                        <span class="font-medium text-gray-900"><?php echo date('M j, Y', strtotime($user['updated_at'] ?? $user['created_at'])); ?></span>
                    </div>

                    <div class="flex items-center justify-between">
                        <span class="text-gray-600">Role</span>
                        <span class="font-medium text-gray-900"><?php echo ucfirst($user['role']); ?></span>
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="bg-white rounded-xl shadow-lg p-6">
                <h3 class="text-lg font-bold text-gray-900 mb-4 flex items-center">
                    <i class="fas fa-bolt mr-2 text-primary"></i>
                    Quick Actions
                </h3>

                <div class="space-y-3">
                    <a href="dashboard.php"
                       class="w-full bg-primary hover:bg-primary-dark text-white px-4 py-3 rounded-lg font-medium transition-colors duration-200 flex items-center justify-center">
                        <i class="fas fa-tachometer-alt mr-2"></i>
                        Dashboard
                    </a>

                    <a href="settings.php"
                       class="w-full bg-secondary hover:bg-secondary-dark text-white px-4 py-3 rounded-lg font-medium transition-colors duration-200 flex items-center justify-center">
                        <i class="fas fa-cog mr-2"></i>
                        Settings
                    </a>

                    <a href="activity_logs.php"
                       class="w-full bg-accent hover:bg-accent-dark text-white px-4 py-3 rounded-lg font-medium transition-colors duration-200 flex items-center justify-center">
                        <i class="fas fa-history mr-2"></i>
                        All Activities
                    </a>
                </div>
            </div>

            <!-- Account Security -->
            <div class="bg-white rounded-xl shadow-lg p-6">
                <h3 class="text-lg font-bold text-gray-900 mb-4 flex items-center">
                    <i class="fas fa-shield-alt mr-2 text-green-500"></i>
                    Security Status
                </h3>

                <div class="space-y-3">
                    <div class="flex items-center justify-between">
                        <span class="text-gray-600">Password</span>
                        <span class="flex items-center text-green-600">
                            <i class="fas fa-check-circle mr-1"></i>
                            Secure
                        </span>
                    </div>

                    <div class="flex items-center justify-between">
                        <span class="text-gray-600">Two-Factor Auth</span>
                        <span class="flex items-center text-yellow-600">
                            <i class="fas fa-exclamation-triangle mr-1"></i>
                            Disabled
                        </span>
                    </div>

                    <div class="flex items-center justify-between">
                        <span class="text-gray-600">Login Sessions</span>
                        <span class="font-medium text-gray-900">1 Active</span>
                    </div>
                </div>

                <div class="mt-4 pt-4 border-t border-gray-200">
                    <button class="w-full bg-gray-100 hover:bg-gray-200 text-gray-700 px-4 py-2 rounded-lg font-medium transition-colors duration-200 text-sm">
                        <i class="fas fa-shield-alt mr-2"></i>
                        Enable Two-Factor Auth
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
/* Profile page enhancements */
.profile-avatar {
    transition: transform 0.3s ease;
}

.profile-avatar:hover {
    transform: scale(1.05);
}

/* Form enhancements */
input:focus, select:focus, textarea:focus {
    box-shadow: 0 0 0 3px rgba(193, 39, 45, 0.1);
}

/* Activity item hover effects */
.activity-item {
    transition: all 0.2s ease;
}

.activity-item:hover {
    transform: translateX(4px);
}

/* Status badge styling */
.status-badge {
    @apply px-3 py-1 text-sm font-medium rounded-full;
}
</style>

<?php
// Include footer
require_once 'includes/footer.php';
?>
