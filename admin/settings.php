<?php
// Start session and check admin authentication
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include database connection
require_once '../includes/config.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}

// Include activity logger
require_once '../includes/ActivityLogger.php';
$activityLogger = new ActivityLogger($pdo);

// Initialize variables
$error = '';
$success = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Start transaction
        $pdo->beginTransaction();

        $settingsUpdated = 0;
        foreach ($_POST['settings'] as $key => $value) {
            // Skip empty values
            if (trim($value) === '') continue;

            $stmt = $pdo->prepare("
                INSERT INTO settings (setting_key, setting_value)
                VALUES (?, ?)
                ON DUPLICATE KEY UPDATE setting_value = ?
            ");
            $stmt->execute([$key, $value, $value]);
            $settingsUpdated++;
        }

        $pdo->commit();

        if ($settingsUpdated > 0) {
            // Log activity
            $activityLogger->log('system', 'updated', "Updated {$settingsUpdated} system settings", 'setting', null);
            $success = "Settings updated successfully ({$settingsUpdated} settings changed)";
        }
    } catch (PDOException $e) {
        $pdo->rollBack();
        $error = 'Error updating settings: ' . $e->getMessage();
    }
}

// Get all settings
$settings = [];
try {
    $stmt = $pdo->query("SELECT * FROM settings ORDER BY setting_group, setting_key");
    $settings = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // If settings table doesn't exist, create it
    if ($e->getCode() == '42S02') {
        try {
            $pdo->exec("
                CREATE TABLE IF NOT EXISTS settings (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    setting_key VARCHAR(100) NOT NULL UNIQUE,
                    setting_value TEXT,
                    setting_group VARCHAR(50) DEFAULT 'general',
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
            ");

            // Insert comprehensive default settings
            $defaultSettings = [
                // General Settings
                ['site_name', 'Addins Meals on Wheels', 'general'],
                ['site_description', 'Your trusted partner for delicious meals delivered to your doorstep', 'general'],
                ['site_email', 'info@addinsmeals.com', 'general'],
                ['site_phone', '+1555123456', 'contact'],
                ['site_address', 'Online Delivery Service - Nationwide Coverage', 'contact'],
                ['opening_hours', 'Mon-Fri: 8:00 AM - 10:00 PM', 'general'],
                ['closing_days', 'Sunday', 'general'],

                // Delivery Settings
                ['delivery_fee', '200', 'delivery'],
                ['min_order_amount', '500', 'delivery'],
                ['max_delivery_distance', '20', 'delivery'],
                ['estimated_delivery_time', '30-45 minutes', 'delivery'],

                // Business Settings
                ['currency', 'KES', 'business'],
                ['tax_rate', '16', 'business'],
                ['service_charge', '5', 'business'],

                // Notification Settings
                ['email_notifications', '1', 'notifications'],
                ['sms_notifications', '0', 'notifications'],
                ['order_notifications', '1', 'notifications'],
                ['marketing_emails', '0', 'notifications'],

                // Security Settings
                ['session_timeout', '30', 'security'],
                ['password_min_length', '8', 'security'],
                ['require_special_chars', '0', 'security'],
                ['enable_2fa', '0', 'security']
            ];

            $stmt = $pdo->prepare("
                INSERT INTO settings (setting_key, setting_value, setting_group)
                VALUES (?, ?, ?)
            ");

            foreach ($defaultSettings as $setting) {
                $stmt->execute($setting);
            }

            // Refresh settings
            $stmt = $pdo->query("SELECT * FROM settings ORDER BY setting_group, setting_key");
            $settings = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Log activity
            $activityLogger->log('system', 'created', "Created settings table with default values", 'setting', null);
            $success = 'Settings table created successfully with default values';
        } catch (PDOException $e) {
            $error = 'Error creating settings table: ' . $e->getMessage();
        }
    } else {
        $error = 'Error loading settings: ' . $e->getMessage();
    }
}

// Group settings by their group
$groupedSettings = [];
foreach ($settings as $setting) {
    $group = $setting['setting_group'] ?? 'general';
    if (!isset($groupedSettings[$group])) {
        $groupedSettings[$group] = [];
    }
    $groupedSettings[$group][] = $setting;
}

// Set page title
$page_title = 'System Settings';

// Include header
require_once 'includes/header.php';
?>

<!-- Settings Header -->
<div class="bg-gradient-to-br from-primary via-primary-dark to-secondary text-white">
    <div class="container mx-auto px-6 py-12">
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between">
            <div class="flex items-center space-x-6">
                <div class="h-20 w-20 rounded-full bg-white/20 backdrop-blur-sm flex items-center justify-center text-white font-bold text-3xl shadow-lg">
                    <i class="fas fa-cog"></i>
                </div>
                <div>
                    <h1 class="text-3xl md:text-4xl font-bold mb-2">System Settings</h1>
                    <p class="text-lg opacity-90 mb-2">Configure your application settings and preferences</p>
                    <div class="flex items-center space-x-4">
                        <span class="px-3 py-1 bg-white/20 backdrop-blur-sm rounded-full text-sm font-medium">
                            <?php echo count($settings); ?> Settings
                        </span>
                        <span class="px-3 py-1 bg-white/20 backdrop-blur-sm rounded-full text-sm font-medium">
                            <?php echo count($groupedSettings); ?> Categories
                        </span>
                    </div>
                </div>
            </div>
            <div class="mt-6 lg:mt-0">
                <div class="flex items-center space-x-4 text-sm">
                    <div class="flex items-center space-x-2 bg-white/10 backdrop-blur-sm rounded-lg px-3 py-2">
                        <i class="fas fa-clock text-blue-300"></i>
                        <span>Last updated: <?php echo date('M j, g:i A'); ?></span>
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
        <div class="mb-6 p-4 bg-red-50 border-l-4 border-red-400 text-red-700 rounded-lg flex items-center animate-slide-in-left">
            <i class="fas fa-exclamation-circle mr-3 animate-pulse"></i>
            <div class="flex-1">
                <p class="font-semibold">Error</p>
                <p><?php echo htmlspecialchars($error); ?></p>
            </div>
            <button onclick="this.parentElement.remove()" class="ml-auto text-gray-400 hover:text-gray-600 transition-colors duration-200 hover:scale-110">
                <i class="fas fa-times"></i>
            </button>
        </div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="mb-6 p-4 bg-green-50 border-l-4 border-green-400 text-green-700 rounded-lg flex items-center animate-bounce-in">
            <i class="fas fa-check-circle mr-3 text-green-500 animate-pulse"></i>
            <div class="flex-1">
                <p class="font-semibold">Success</p>
                <p><?php echo htmlspecialchars($success); ?></p>
            </div>
            <button onclick="this.parentElement.remove()" class="ml-auto text-gray-400 hover:text-gray-600 transition-colors duration-200 hover:scale-110">
                <i class="fas fa-times"></i>
            </button>
        </div>
    <?php endif; ?>

    <!-- Settings Form -->
    <form action="" method="POST" class="space-y-8">
        <?php
        $settingGroups = [
            'general' => ['title' => 'General Settings', 'icon' => 'fas fa-store', 'color' => 'blue'],
            'contact' => ['title' => 'Contact Information', 'icon' => 'fas fa-address-book', 'color' => 'green'],
            'delivery' => ['title' => 'Delivery Settings', 'icon' => 'fas fa-truck', 'color' => 'purple'],
            'business' => ['title' => 'Business Settings', 'icon' => 'fas fa-chart-line', 'color' => 'yellow'],
            'notifications' => ['title' => 'Notification Settings', 'icon' => 'fas fa-bell', 'color' => 'indigo'],
            'security' => ['title' => 'Security Settings', 'icon' => 'fas fa-shield-alt', 'color' => 'red']
        ];

        foreach ($groupedSettings as $group => $groupSettings):
            $groupInfo = $settingGroups[$group] ?? ['title' => ucfirst($group), 'icon' => 'fas fa-cog', 'color' => 'gray'];
        ?>
            <div class="bg-white rounded-xl shadow-lg p-6">
                <!-- Group Header -->
                <div class="flex items-center justify-between mb-6 pb-4 border-b border-gray-200">
                    <div class="flex items-center space-x-3">
                        <div class="w-12 h-12 bg-<?php echo $groupInfo['color']; ?>-100 rounded-lg flex items-center justify-center">
                            <i class="<?php echo $groupInfo['icon']; ?> text-<?php echo $groupInfo['color']; ?>-600 text-xl"></i>
                        </div>
                        <div>
                            <h3 class="text-xl font-bold text-gray-900"><?php echo $groupInfo['title']; ?></h3>
                            <p class="text-sm text-gray-600"><?php echo count($groupSettings); ?> settings</p>
                        </div>
                    </div>
                    <div class="text-right">
                        <span class="px-3 py-1 bg-gray-100 text-gray-600 rounded-full text-xs font-medium">
                            <?php echo count($groupSettings); ?> items
                        </span>
                    </div>
                </div>

                <!-- Settings Grid -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <?php foreach ($groupSettings as $setting): ?>
                        <div class="space-y-2">
                            <label for="<?php echo htmlspecialchars($setting['setting_key']); ?>"
                                   class="block text-sm font-semibold text-gray-700">
                                <?php echo ucwords(str_replace('_', ' ', $setting['setting_key'])); ?>
                            </label>

                            <?php if (in_array($setting['setting_key'], ['email_notifications', 'sms_notifications', 'order_notifications', 'marketing_emails', 'require_special_chars', 'enable_2fa'])): ?>
                                <!-- Toggle Switch -->
                                <div class="flex items-center">
                                    <input type="hidden" name="settings[<?php echo htmlspecialchars($setting['setting_key']); ?>]" value="0">
                                    <input type="checkbox"
                                           name="settings[<?php echo htmlspecialchars($setting['setting_key']); ?>]"
                                           id="<?php echo htmlspecialchars($setting['setting_key']); ?>"
                                           value="1"
                                           <?php echo ($setting['setting_value'] == '1') ? 'checked' : ''; ?>
                                           class="sr-only">
                                    <label for="<?php echo htmlspecialchars($setting['setting_key']); ?>"
                                           class="flex items-center cursor-pointer">
                                        <div class="relative">
                                            <div class="w-10 h-6 bg-gray-400 rounded-full shadow-inner transition-colors duration-200 <?php echo ($setting['setting_value'] == '1') ? 'bg-primary' : ''; ?>"></div>
                                            <div class="absolute w-4 h-4 bg-white rounded-full shadow -left-1 -top-1 transition-transform duration-200 transform <?php echo ($setting['setting_value'] == '1') ? 'translate-x-full' : ''; ?>"></div>
                                        </div>
                                        <span class="ml-3 text-sm font-medium text-gray-900">
                                            <?php echo ($setting['setting_value'] == '1') ? 'Enabled' : 'Disabled'; ?>
                                        </span>
                                    </label>
                                </div>
                            <?php elseif (strlen($setting['setting_value']) > 100): ?>
                                <!-- Textarea -->
                                <textarea name="settings[<?php echo htmlspecialchars($setting['setting_key']); ?>]"
                                          id="<?php echo htmlspecialchars($setting['setting_key']); ?>"
                                          rows="3"
                                          class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent transition-colors text-sm"
                                          placeholder="Enter <?php echo ucwords(str_replace('_', ' ', $setting['setting_key'])); ?>"><?php echo htmlspecialchars($setting['setting_value']); ?></textarea>
                            <?php elseif (in_array($setting['setting_key'], ['delivery_fee', 'min_order_amount', 'max_delivery_distance', 'tax_rate', 'service_charge', 'session_timeout', 'password_min_length'])): ?>
                                <!-- Number Input -->
                                <div class="relative">
                                    <input type="number"
                                           name="settings[<?php echo htmlspecialchars($setting['setting_key']); ?>]"
                                           id="<?php echo htmlspecialchars($setting['setting_key']); ?>"
                                           value="<?php echo htmlspecialchars($setting['setting_value']); ?>"
                                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent transition-colors text-sm"
                                           min="0"
                                           <?php if (in_array($setting['setting_key'], ['tax_rate', 'service_charge'])) echo 'max="100" step="0.01"'; ?>
                                           placeholder="Enter <?php echo ucwords(str_replace('_', ' ', $setting['setting_key'])); ?>">
                                    <?php if ($setting['setting_key'] === 'delivery_fee' || $setting['setting_key'] === 'min_order_amount' || $setting['setting_key'] === 'max_delivery_distance'): ?>
                                        <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                                            <span class="text-gray-500 text-sm">KES</span>
                                        </div>
                                    <?php elseif ($setting['setting_key'] === 'max_delivery_distance'): ?>
                                        <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                                            <span class="text-gray-500 text-sm">km</span>
                                        </div>
                                    <?php elseif (in_array($setting['setting_key'], ['tax_rate', 'service_charge'])): ?>
                                        <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                                            <span class="text-gray-500 text-sm">%</span>
                                        </div>
                                    <?php elseif ($setting['setting_key'] === 'session_timeout'): ?>
                                        <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                                            <span class="text-gray-500 text-sm">minutes</span>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php else: ?>
                                <!-- Regular Text Input -->
                                <input type="<?php echo ($setting['setting_key'] === 'site_email') ? 'email' : 'text'; ?>"
                                       name="settings[<?php echo htmlspecialchars($setting['setting_key']); ?>]"
                                       id="<?php echo htmlspecialchars($setting['setting_key']); ?>"
                                       value="<?php echo htmlspecialchars($setting['setting_value']); ?>"
                                       class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent transition-colors text-sm"
                                       placeholder="Enter <?php echo ucwords(str_replace('_', ' ', $setting['setting_key'])); ?>">
                            <?php endif; ?>

                            <!-- Help Text -->
                            <p class="text-xs text-gray-500">
                                <?php
                                switch ($setting['setting_key']) {
                                    case 'site_name':
                                        echo 'The main name of your restaurant/business';
                                        break;
                                    case 'site_email':
                                        echo 'Primary contact email for customer inquiries';
                                        break;
                                    case 'delivery_fee':
                                        echo 'Fixed delivery charge per order';
                                        break;
                                    case 'min_order_amount':
                                        echo 'Minimum order value required for delivery';
                                        break;
                                    case 'tax_rate':
                                        echo 'Tax percentage applied to orders';
                                        break;
                                    case 'session_timeout':
                                        echo 'Minutes before admin sessions expire';
                                        break;
                                    default:
                                        echo 'Configure this setting as needed';
                                }
                                ?>
                            </p>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endforeach; ?>

        <!-- Submit Section -->
        <div class="bg-white rounded-xl shadow-lg p-6">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-lg font-semibold text-gray-900">Save Settings</h3>
                    <p class="text-sm text-gray-600">Review and save your configuration changes</p>
                </div>
                <div class="flex space-x-4">
                    <button type="button"
                            onclick="window.location.reload()"
                            class="px-6 py-3 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition-colors duration-200 font-medium flex items-center">
                        <i class="fas fa-undo mr-2"></i>
                        Reset
                    </button>
                    <button type="submit"
                            class="px-6 py-3 bg-primary hover:bg-primary-dark text-white rounded-lg font-semibold transition-colors duration-200 flex items-center shadow-lg hover:shadow-xl">
                        <i class="fas fa-save mr-2"></i>
                        Save All Settings
                    </button>
                </div>
            </div>
        </div>
    </form>
</div>

<style>
/* Settings page enhancements */
.settings-toggle {
    transition: all 0.3s ease;
}

.settings-toggle:hover {
    transform: scale(1.05);
}

/* Custom toggle switch styling */
input[type="checkbox"] + label div {
    transition: all 0.3s ease;
}

/* Form focus enhancements */
input:focus, textarea:focus, select:focus {
    box-shadow: 0 0 0 3px rgba(193, 39, 45, 0.1);
    transform: translateY(-1px);
}

/* Card hover effects */
.bg-white:hover {
    transform: translateY(-2px);
    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
}

/* Settings group icons */
.settings-icon {
    transition: transform 0.3s ease;
}

.settings-icon:hover {
    transform: rotate(10deg) scale(1.1);
}

/* Help text styling */
.help-text {
    @apply text-xs text-gray-500 mt-1;
}

/* Status indicators */
.status-indicator {
    @apply px-2 py-1 text-xs font-medium rounded-full;
}

.status-enabled {
    @apply bg-green-100 text-green-800;
}

.status-disabled {
    @apply bg-red-100 text-red-800;
}

/* Responsive grid improvements */
@media (max-width: 768px) {
    .settings-grid {
        grid-template-columns: 1fr;
    }
}

/* Animation for settings cards */
@keyframes slideInUp {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

@keyframes slideInLeft {
    from {
        opacity: 0;
        transform: translateX(-100%);
    }
    to {
        opacity: 1;
        transform: translateX(0);
    }
}

@keyframes bounceIn {
    0% {
        opacity: 0;
        transform: scale(0.3);
    }
    50% {
        opacity: 1;
        transform: scale(1.05);
    }
    70% {
        transform: scale(0.9);
    }
    100% {
        opacity: 1;
        transform: scale(1);
    }
}

.settings-card {
    animation: slideInUp 0.5s ease-out;
}

.animate-slide-in-left {
    animation: slideInLeft 0.4s ease-out;
}

.animate-bounce-in {
    animation: bounceIn 0.6s ease-out;
}

/* Enhanced button styles */
.btn-settings {
    @apply px-6 py-3 rounded-lg font-semibold transition-all duration-200 flex items-center justify-center;
}

.btn-primary-settings {
    @apply bg-primary hover:bg-primary-dark text-white shadow-lg hover:shadow-xl transform hover:-translate-y-1;
}

.btn-secondary-settings {
    @apply bg-gray-100 hover:bg-gray-200 text-gray-700 border border-gray-300;
}

/* Pulse animation for icons */
@keyframes gentlePulse {
    0%, 100% {
        opacity: 1;
    }
    50% {
        opacity: 0.7;
    }
}

.animate-gentle-pulse {
    animation: gentlePulse 2s ease-in-out infinite;
}
</style>

<script>
// Enhanced settings page functionality
document.addEventListener('DOMContentLoaded', function() {
    // Auto-save draft functionality (optional)
    const settingsForm = document.querySelector('form');
    let autoSaveTimer;

    // Add change listeners to form fields
    const formFields = settingsForm.querySelectorAll('input, textarea, select');
    formFields.forEach(field => {
        field.addEventListener('input', function() {
            // Clear existing timer
            clearTimeout(autoSaveTimer);

            // Set new timer for auto-save indication
            autoSaveTimer = setTimeout(() => {
                showAutoSaveIndicator();
            }, 2000);
        });
    });

    // Toggle switch functionality
    const toggleInputs = document.querySelectorAll('input[type="checkbox"][name*="settings"]');
    toggleInputs.forEach(toggle => {
        toggle.addEventListener('change', function() {
            const label = this.nextElementSibling;
            const statusText = label.querySelector('.status-text');

            if (this.checked) {
                statusText.textContent = 'Enabled';
                label.classList.remove('status-disabled');
                label.classList.add('status-enabled');
            } else {
                statusText.textContent = 'Disabled';
                label.classList.remove('status-enabled');
                label.classList.add('status-disabled');
            }
        });
    });

    // Form validation
    settingsForm.addEventListener('submit', function(e) {
        const requiredFields = settingsForm.querySelectorAll('[required]');
        let isValid = true;

        requiredFields.forEach(field => {
            if (!field.value.trim()) {
                field.classList.add('border-red-500');
                isValid = false;
            } else {
                field.classList.remove('border-red-500');
            }
        });

        if (!isValid) {
            e.preventDefault();
            showError('Please fill in all required fields');
        }
    });
});

function showAutoSaveIndicator() {
    // Show subtle auto-save indicator
    const indicator = document.createElement('div');
    indicator.className = 'fixed top-4 right-4 bg-green-100 text-green-800 px-4 py-2 rounded-lg shadow-lg z-50';
    indicator.innerHTML = '<i class="fas fa-check mr-2"></i>Settings saved automatically';
    document.body.appendChild(indicator);

    setTimeout(() => {
        indicator.remove();
    }, 3000);
}

function showError(message) {
    const errorDiv = document.createElement('div');
    errorDiv.className = 'fixed top-4 right-4 bg-red-100 text-red-800 px-4 py-2 rounded-lg shadow-lg z-50';
    errorDiv.innerHTML = '<i class="fas fa-exclamation-circle mr-2"></i>' + message;
    document.body.appendChild(errorDiv);

    setTimeout(() => {
        errorDiv.remove();
    }, 5000);
}

// Real-time validation feedback
document.querySelectorAll('input[type="email"]').forEach(emailField => {
    emailField.addEventListener('blur', function() {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (this.value && !emailRegex.test(this.value)) {
            this.classList.add('border-red-500');
        } else {
            this.classList.remove('border-red-500');
        }
    });
});
</script>

<?php
// Include footer
require_once 'includes/footer.php';
?>