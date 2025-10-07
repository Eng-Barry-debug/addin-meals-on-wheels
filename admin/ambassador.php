<?php
// Start session and check admin authentication
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Global functions for sanitization and display logic
// Make sure this sanitize function is robust against XSS and other injection attacks.
// It should preferably be in includes/functions.php or similar and included once.

require_once 'includes/functions.php';

// Display utility functions (ensure these are available, e.g., in includes/functions.php or included here)
if (!function_exists('getExperienceLabel')) {
    function getExperienceLabel(string $experience): string {
        return match($experience) {
            'none' => 'No Experience',
            'some_sales' => 'Some Sales Experience',
            'experienced' => 'Experienced',
            'influencer' => 'Social Media Influencer',
            default => 'Unknown',
        };
    }
}

if (!function_exists('getExperienceBadgeClass')) {
    function getExperienceBadgeClass(string $experience): string {
        return match($experience) {
            'none' => 'bg-gray-100 text-gray-800',
            'some_sales' => 'bg-blue-100 text-blue-800',
            'experienced' => 'bg-purple-100 text-purple-800',
            'influencer' => 'bg-pink-100 text-pink-800',
            default => 'bg-gray-100 text-gray-800',
        };
    }
}

// Include database connection
require_once '../includes/config.php';

// Access control: Redirect if not logged in or not an admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}

// Initialize variables for messages
$error = '';
$success = '';

// Retrieve and clear session messages if any
if (isset($_SESSION['message'])) {
    if ($_SESSION['message']['type'] === 'success') {
        $success = $_SESSION['message']['text'];
    } elseif ($_SESSION['message']['type'] === 'error') {
        $error = $_SESSION['message']['text'];
    }
    unset($_SESSION['message']); // Clear the message after displaying
}

// Include activity logger
require_once '../includes/ActivityLogger.php';
$activityLogger = new ActivityLogger($pdo);

// Handle form submissions for CRUD operations
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // --- Delete Application ---
    if (isset($_POST['delete_application'])) {
        $application_id = (int)$_POST['application_id'];

        try {
            $stmt = $pdo->prepare("DELETE FROM ambassadors WHERE id = ?");
            $stmt->execute([$application_id]);

            $activityLogger->log('ambassador', 'deleted', "Deleted ambassador application", 'ambassador', $application_id);

            $_SESSION['message'] = [
                'type' => 'success',
                'text' => 'Ambassador application deleted successfully!'
            ];

            header('Location: ' . $_SERVER['PHP_SELF'] . '?' . http_build_query($_GET));
            exit();
        } catch (PDOException $e) {
            $_SESSION['message'] = [
                'type' => 'error',
                'text' => 'Error deleting ambassador application: ' . $e->getMessage()
            ];
            header('Location: ' . $_SERVER['PHP_SELF'] . '?' . http_build_query($_GET));
            exit();
        }
    }

    // --- Update Application Details (from edit modal) ---
    if (isset($_POST['update_application'])) {
        $application_id = (int)$_POST['application_id'];
        $name = sanitize($_POST['name']);
        $email = sanitize($_POST['email']);
        $phone = sanitize($_POST['phone']);
        $social_media = sanitize($_POST['social_media']);
        $experience = sanitize($_POST['experience']);
        $motivation = sanitize($_POST['motivation']);
        $message = sanitize($_POST['message']);
        $status = sanitize($_POST['status']);

        try {
            $stmt = $pdo->prepare("UPDATE ambassadors SET name = ?, email = ?, phone = ?, social_media = ?, experience = ?, motivation = ?, message = ?, status = ? WHERE id = ?");
            $stmt->execute([$name, $email, $phone, $social_media, $experience, $motivation, $message, $status, $application_id]);

            $activityLogger->log('ambassador', 'updated', "Updated ambassador application: {$name}", 'ambassador', $application_id);

            $_SESSION['message'] = [
                'type' => 'success',
                'text' => 'Ambassador application updated successfully!'
            ];

            header('Location: ' . $_SERVER['PHP_SELF'] . '?' . http_build_query($_GET));
            exit();
        } catch (PDOException $e) {
             $_SESSION['message'] = [
                'type' => 'error',
                'text' => 'Error updating ambassador application: ' . $e->getMessage()
            ];
            header('Location: ' . $_SERVER['PHP_SELF'] . '?' . http_build_query($_GET));
            exit();
        }
    }

    // --- Update Application Status (Approve/Reject actions) ---
    if (isset($_POST['update_status'])) {
        $application_id = (int)$_POST['application_id'];
        $new_status = sanitize($_POST['status_value']);

        // Basic validation for new_status
        if (!in_array($new_status, ['pending', 'approved', 'rejected'])) {
            $_SESSION['message'] = [
                'type' => 'error',
                'text' => 'Invalid status provided for application.'
            ];
            header('Location: ' . $_SERVER['PHP_SELF'] . '?' . http_build_query($_GET));
            exit();
        }

        try {
            $stmt = $pdo->prepare("UPDATE ambassadors SET status = ? WHERE id = ?");
            $stmt->execute([$new_status, $application_id]);

            $activityLogger->log('ambassador', 'updated_status', "Updated application #{$application_id} status to {$new_status}", 'ambassador', $application_id);

            $_SESSION['message'] = [
                'type' => 'success',
                'text' => "Application status updated to {$new_status} successfully!"
            ];
            header('Location: ' . $_SERVER['PHP_SELF'] . '?' . http_build_query($_GET));
            exit();
        } catch (PDOException $e) {
            $_SESSION['message'] = [
                'type' => 'error',
                'text' => 'Error updating application status: ' . $e->getMessage()
            ];
            header('Location: ' . $_SERVER['PHP_SELF'] . '?' . http_build_query($_GET));
            exit();
        }
    }

    // --- AJAX Request to View Application (for modal content) ---
    if (isset($_POST['view_application'])) {
        $application_id = (int)$_POST['view_application'];

        try {
            $stmt = $pdo->prepare("SELECT * FROM ambassadors WHERE id = ?");
            $stmt->execute([$application_id]);
            $application = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($application) {
                ob_clean(); // Clear any previous output before rendering modal content
                ?>
                <div class="space-y-6">
                    <!-- Application Header -->
                    <div class="bg-gradient-to-r from-purple-50 to-purple-100 p-6 rounded-lg border-l-4 border-purple-400">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center space-x-4">
                                <div class="w-16 h-16 bg-purple-600 rounded-full flex items-center justify-center shadow-lg">
                                    <span class="text-white font-bold text-2xl"><?php echo strtoupper(substr($application['name'], 0, 1)); ?></span>
                                </div>
                                <div>
                                    <h3 class="text-2xl font-bold text-gray-900"><?php echo htmlspecialchars($application['name']); ?></h3>
                                    <p class="text-gray-600">Applied: <?php echo date('F j, Y \a\t g:i A', strtotime($application['application_date'])); ?></p>
                                </div>
                            </div>
                            <div class="text-right">
                                <span class="px-4 py-2 rounded-full text-sm font-semibold <?php echo getStatusBadgeClass($application['status']); ?>">
                                    <i class="fas fa-<?php
                                        echo match($application['status'] ?? 'pending') {
                                            'pending' => 'clock',
                                            'approved' => 'check-circle',
                                            'rejected' => 'times-circle'
                                        };
                                    ?> mr-1"></i>
                                    <?php echo ucfirst($application['status'] ?? 'pending'); ?>
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Contact Information -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="bg-blue-50 rounded-lg p-6 border-l-4 border-blue-400">
                            <h4 class="font-semibold text-gray-900 mb-4 flex items-center">
                                <i class="fas fa-address-book text-blue-600 mr-2"></i>
                                Contact Information
                            </h4>
                            <div class="space-y-3">
                                <p><span class="font-medium">Email:</span> <?php echo htmlspecialchars($application['email']); ?></p>
                                <p><span class="font-medium">Phone:</span> <?php echo htmlspecialchars($application['phone'] ?? 'Not provided'); ?></p>
                                <?php if (!empty($application['social_media'])): ?>
                                <p><span class="font-medium">Social Media:</span> @<?php echo htmlspecialchars($application['social_media']); ?></p>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Experience & Background -->
                        <div class="bg-green-50 rounded-lg p-6 border-l-4 border-green-400">
                            <h4 class="font-semibold text-gray-900 mb-4 flex items-center">
                                <i class="fas fa-chart-line text-green-600 mr-2"></i>
                                Experience & Background
                            </h4>
                            <div class="space-y-3">
                                <p><span class="font-medium">Experience Level:</span> <?php echo getExperienceLabel($application['experience']); ?></p>
                                <p><span class="font-medium">Application ID:</span> #<?php echo $application['id']; ?></p>
                            </div>
                        </div>
                    </div>

                    <!-- Application Content -->
                    <?php if (!empty($application['motivation']) || !empty($application['message'])): ?>
                    <div class="bg-gray-50 rounded-lg p-6">
                        <h4 class="font-semibold text-gray-900 mb-4 flex items-center">
                            <i class="fas fa-quote-left text-purple-600 mr-2"></i>
                            Application Details
                        </h4>
                        <div class="grid md:grid-cols-2 gap-6">
                            <?php if (!empty($application['motivation'])): ?>
                            <div>
                                <h5 class="font-medium text-gray-800 mb-2">Why they want to be an ambassador:</h5>
                                <p class="text-gray-700 leading-relaxed"><?php echo nl2br(htmlspecialchars($application['motivation'])); ?></p>
                            </div>
                            <?php endif; ?>
                            <?php if (!empty($application['message'])): ?>
                            <div>
                                <h5 class="font-medium text-gray-800 mb-2">Additional message:</h5>
                                <p class="text-gray-700 leading-relaxed"><?php echo nl2br(htmlspecialchars($application['message'])); ?></p>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
                <?php
                exit();
            } else {
                ob_clean();
                echo '<div class="p-8 text-center"><p class="text-red-600">Application not found.</p></div>';
                exit();
            }
        } catch (PDOException $e) {
            ob_clean();
            echo '<div class="p-8 text-center"><p class="text-red-600">Error loading application: ' . htmlspecialchars($e->getMessage()) . '</p></div>';
            exit();
        }
    }

    // --- AJAX Request to Edit Application (for modal content) ---
    if (isset($_POST['edit_application'])) {
        $application_id = (int)$_POST['edit_application'];

        try {
            $stmt = $pdo->prepare("SELECT * FROM ambassadors WHERE id = ?");
            $stmt->execute([$application_id]);
            $application = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($application) {
                ob_clean(); // Clear any previous output before rendering modal content
                ?>
                <form id="editApplicationForm" onsubmit="event.preventDefault(); submitEditForm(this);" class="space-y-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label for="edit_name" class="block text-sm font-semibold text-gray-700 mb-2">Full Name</label>
                            <input type="text" name="name" id="edit_name" value="<?php echo htmlspecialchars($application['name']); ?>"
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent" required>
                        </div>
                        <div>
                            <label for="edit_email" class="block text-sm font-semibold text-gray-700 mb-2">Email Address</label>
                            <input type="email" name="email" id="edit_email" value="<?php echo htmlspecialchars($application['email']); ?>"
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent" required>
                        </div>
                        <div>
                            <label for="edit_phone" class="block text-sm font-semibold text-gray-700 mb-2">Phone Number</label>
                            <input type="tel" name="phone" id="edit_phone" value="<?php echo htmlspecialchars($application['phone'] ?? ''); ?>"
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                        </div>
                        <div>
                            <label for="edit_social_media" class="block text-sm font-semibold text-gray-700 mb-2">Social Media Handle</label>
                            <input type="text" name="social_media" id="edit_social_media" value="<?php echo htmlspecialchars($application['social_media'] ?? ''); ?>"
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent" placeholder="@username">
                        </div>
                    </div>

                    <div>
                        <label for="edit_status" class="block text-sm font-semibold text-gray-700 mb-2">Status</label>
                        <select name="status" id="edit_status"
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                            <option value="pending" <?php echo ($application['status'] ?? 'pending') === 'pending' ? 'selected' : ''; ?>>Pending</option>
                            <option value="approved" <?php echo ($application['status'] ?? 'pending') === 'approved' ? 'selected' : ''; ?>>Approved</option>
                            <option value="rejected" <?php echo ($application['status'] ?? 'pending') === 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                        </select>
                    </div>

                    <div>
                        <label for="edit_experience" class="block text-sm font-semibold text-gray-700 mb-2">Experience Level</label>
                        <select name="experience" id="edit_experience"
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                            <option value="none" <?php echo ($application['experience'] ?? 'none') === 'none' ? 'selected' : ''; ?>>No Experience</option>
                            <option value="some_sales" <?php echo ($application['experience'] ?? 'none') === 'some_sales' ? 'selected' : ''; ?>>Some Sales Experience</option>
                            <option value="experienced" <?php echo ($application['experience'] ?? 'none') === 'experienced' ? 'selected' : ''; ?>>Experienced</option>
                            <option value="influencer" <?php echo ($application['experience'] ?? 'none') === 'influencer' ? 'selected' : ''; ?>>Social Media Influencer</option>
                        </select>
                    </div>

                    <div>
                        <label for="edit_motivation" class="block text-sm font-semibold text-gray-700 mb-2">Motivation (Why they want to be an ambassador)</label>
                        <textarea name="motivation" id="edit_motivation" rows="4"
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent"
                                placeholder="Explain why they want to become an ambassador..."><?php echo htmlspecialchars($application['motivation'] ?? ''); ?></textarea>
                    </div>

                    <div>
                        <label for="edit_message" class="block text-sm font-semibold text-gray-700 mb-2">Additional Message</label>
                        <textarea name="message" id="edit_message" rows="4"
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent"
                                placeholder="Any additional information..."><?php echo htmlspecialchars($application['message'] ?? ''); ?></textarea>
                    </div>

                    <div class="flex items-center justify-end space-x-4 pt-6 border-t border-gray-200">
                        <button type="button" onclick="closeEditModal()" class="px-6 py-3 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 font-semibold transition-colors duration-200">
                            Cancel
                        </button>
                        <input type="hidden" name="application_id" value="<?php echo $application['id']; ?>">
                        <input type="hidden" name="update_application" value="1">
                        <button type="submit" class="bg-primary hover:bg-primary-dark text-white px-6 py-3 rounded-lg font-semibold transition-colors duration-200 flex items-center">
                            <i class="fas fa-save mr-2"></i>
                            Update Application
                        </button>
                    </div>
                </form>
                <?php
                exit();
            } else {
                ob_clean();
                echo '<div class="p-8 text-center"><p class="text-red-600">Application not found.</p></div>';
                exit();
            }
        } catch (PDOException $e) {
            ob_clean();
            echo '<div class="p-8 text-center"><p class="text-red-600">Error loading application: ' . htmlspecialchars($e->getMessage()) . '</p></div>';
            exit();
        }
    }
}

// Get filter parameters from GET request
$search = $_GET['search'] ?? '';
$status_filter = $_GET['status'] ?? '';
$experience_filter = $_GET['experience'] ?? '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 10; // Number of applications per page
$offset = ($page - 1) * $per_page; // Offset for SQL query

// Build where clause for filtering and searching
$where_conditions = [];
$params = [];

if (!empty($status_filter)) {
    $where_conditions[] = "status = :status";
    $params[':status'] = $status_filter;
}

if (!empty($experience_filter)) {
    $where_conditions[] = "experience = :experience";
    $params[':experience'] = $experience_filter;
}

// Search functionality across name, email, and phone
if (!empty($search)) {
    $where_conditions[] = "(name LIKE :search OR email LIKE :search OR phone LIKE :search)";
    $params[':search'] = "%$search%"; // Wildcard search
}

$where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

// Get total count of applications for pagination
$total_sql = "SELECT COUNT(*) as count FROM ambassadors $where_clause";
$total_stmt = $pdo->prepare($total_sql);
$total_stmt->execute($where_clause ? $params : []);
$total_applications = (int)$total_stmt->fetch(PDO::FETCH_ASSOC)['count'];

// Get ambassador applications with applied filters and pagination
$sql = "SELECT * FROM ambassadors $where_clause ORDER BY application_date DESC LIMIT $offset, $per_page";
$stmt = $pdo->prepare($sql);
$stmt->execute($where_clause ? $params : []);
$applications = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Calculate total pages for pagination display
$total_pages = ceil($total_applications / $per_page);

// Set page specific metadata
$page_title = 'Ambassador Management';
$page_description = 'Manage ambassador applications and partnerships';

// Include header HTML
require_once 'includes/header.php';
?>
<!-- Page Header Section -->
<div class="bg-gradient-to-br from-primary via-primary-dark to-secondary text-white">
    <div class="container mx-auto px-6 py-12">
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between">
            <div class="flex items-center space-x-6">
                <!-- Icon and Title -->
                <div class="h-20 w-20 rounded-full bg-white/20 backdrop-blur-sm flex items-center justify-center text-white font-bold text-3xl shadow-lg">
                    <i class="fas fa-handshake"></i>
                </div>
                <div>
                    <h1 class="text-3xl md:text-4xl font-bold mb-2">Ambassador Management</h1>
                    <p class="text-lg opacity-90 mb-2">Manage ambassador applications and partnerships</p>
                    <!-- Stats Badges -->
                    <div class="flex items-center space-x-4">
                        <span class="px-3 py-1 bg-white/20 backdrop-blur-sm rounded-full text-sm font-medium">
                            <?php echo $total_applications; ?> Total Applications
                        </span>
                        <span class="px-3 py-1 bg-white/20 backdrop-blur-sm rounded-full text-sm font-medium">
                            <?php echo count($applications); ?> This Page
                        </span>
                    </div>
                </div>
            </div>
            <!-- Last Updated Info -->
            <div class="mt-6 lg:mt-0">
                <div class="flex items-center space-x-4 text-sm">
                    <div class="flex items-center space-x-2 bg-white/10 backdrop-blur-sm rounded-lg px-3 py-2">
                        <i class="fas fa-chart-line text-blue-300"></i>
                        <span>Updated: <?php echo date('M j, g:i A'); ?></span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Main Content Area -->
<div class="container mx-auto px-6 py-8">
    <!-- Alerts for Success/Error Messages -->
    <?php if ($error): ?>
        <div class="mb-6 p-4 bg-red-50 border-l-4 border-red-400 text-red-700 rounded-lg flex items-center">
            <i class="fas fa-exclamation-circle mr-3"></i>
            <div>
                <p class="font-semibold">Error</p>
                <p><?php echo htmlspecialchars($error); ?></p>
            </div>
            <button type="button" onclick="this.parentElement.remove()" class="ml-auto text-gray-400 hover:text-gray-600" aria-label="Close error message">
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
            <button type="button" onclick="this.parentElement.remove()" class="ml-auto text-gray-400 hover:text-gray-600" aria-label="Close success message">
                <i class="fas fa-times"></i>
            </button>
        </div>
    <?php endif; ?>

    <!-- Filters and Search Form -->
    <div class="bg-white rounded-xl shadow-lg p-6 mb-8">
        <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-6">
            <div>
                <label for="search" class="block text-sm font-semibold text-gray-700 mb-2">Search</label>
                <input type="text" name="search" id="search"
                       value="<?php echo htmlspecialchars($search); ?>"
                       class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent"
                       placeholder="Search by name, email, or phone...">
            </div>

            <div>
                <label for="status" class="block text-sm font-semibold text-gray-700 mb-2">Status</label>
                <select name="status" id="status"
                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                    <option value="">All Status</option>
                    <option value="pending" <?php echo $status_filter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                    <option value="approved" <?php echo $status_filter === 'approved' ? 'selected' : ''; ?>>Approved</option>
                    <option value="rejected" <?php echo $status_filter === 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                </select>
            </div>

            <div>
                <label for="experience" class="block text-sm font-semibold text-gray-700 mb-2">Experience</label>
                <select name="experience" id="experience"
                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                    <option value="">All Experience</option>
                    <option value="none" <?php echo $experience_filter === 'none' ? 'selected' : ''; ?>>No Experience</option>
                    <option value="some_sales" <?php echo $experience_filter === 'some_sales' ? 'selected' : ''; ?>>Some Sales</option>
                    <option value="experienced" <?php echo $experience_filter === 'experienced' ? 'selected' : ''; ?>>Experienced</option>
                    <option value="influencer" <?php echo $experience_filter === 'influencer' ? 'selected' : ''; ?>>Influencer</option>
                </select>
            </div>

            <div class="flex items-end space-x-4">
                <button type="submit" class="bg-primary hover:bg-primary-dark text-white px-6 py-3 rounded-lg font-semibold transition-colors duration-200 flex items-center">
                    <i class="fas fa-search mr-2"></i>
                    Search
                </button>
                <a href="ambassador.php" class="bg-gray-500 hover:bg-gray-600 text-white px-6 py-3 rounded-lg font-semibold transition-colors duration-200">
                    <i class="fas fa-times mr-2"></i>
                    Clear
                </a>
            </div>
        </form>
    </div>

    <!-- Applications List Section -->
    <div class="bg-white rounded-xl shadow-lg overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200">
            <h2 class="text-lg font-semibold text-gray-900">Ambassador Applications (<?php echo $total_applications; ?>)</h2>
        </div>

        <?php if (empty($applications)): ?>
            <!-- Empty state message -->
            <div class="p-12 text-center">
                <i class="fas fa-handshake text-4xl text-gray-400 mb-4"></i>
                <h3 class="text-lg font-medium text-gray-900 mb-2">No applications found</h3>
                <p class="text-gray-500 mb-4">No ambassador applications match your current filters.</p>
                <a href="../ambassador-apply.php" target="_blank" class="bg-primary hover:bg-primary-dark text-white px-6 py-3 rounded-lg font-semibold transition-colors duration-200 flex items-center mx-auto w-fit">
                    <i class="fas fa-external-link-alt mr-2"></i>
                    View Application Form
                </a>
            </div>
        <?php else: ?>
            <!-- Loop through each application and display its card -->
            <div class="space-y-6">
                <?php foreach ($applications as $app): ?>
                <div class="bg-white rounded-xl shadow-lg border border-gray-200 hover:shadow-xl transition-all duration-300 overflow-hidden application-card"
                     data-applicant-name="<?= htmlspecialchars(strtolower($app['name'])) ?>"
                     data-applicant-email="<?= htmlspecialchars(strtolower($app['email'])) ?>"
                     data-application-status="<?= $app['status'] ?>"
                     data-experience-level="<?= $app['experience'] ?>">

                    <!-- Card Header with Quick Actions -->
                    <div class="bg-gradient-to-r from-purple-50 to-purple-100 px-6 py-4 border-b border-purple-200">
                        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between">
                            <!-- Application Info -->
                            <div class="flex items-center space-x-4">
                                <div class="w-14 h-14 bg-purple-600 rounded-full flex items-center justify-center shadow-lg">
                                    <span class="text-white font-bold text-xl"><?php echo strtoupper(substr($app['name'], 0, 1)); ?></span>
                                </div>
                                <div>
                                    <h3 class="text-2xl font-bold text-gray-900 mb-1">
                                        <?php echo htmlspecialchars($app['name']); ?>
                                    </h3>
                                    <p class="text-sm text-gray-600 font-medium">
                                        Applied: <?php echo date('M j, Y \a\t g:i A', strtotime($app['application_date'])); ?>
                                    </p>
                                    <div class="flex items-center space-x-2 mt-2">
                                        <span class="px-3 py-1 rounded-full text-sm font-semibold <?php echo getStatusBadgeClass($app['status']); ?>">
                                            <i class="fas fa-<?php
                                                echo match($app['status'] ?? 'pending') {
                                                    'pending' => 'clock',
                                                    'approved' => 'check-circle',
                                                    'rejected' => 'times-circle'
                                                };
                                            ?> mr-1 text-xs"></i>
                                            <?php echo ucfirst($app['status'] ?? 'pending'); ?>
                                        </span>
                                        <span class="px-3 py-1 rounded-full text-sm font-semibold <?php echo getExperienceBadgeClass($app['experience']); ?>">
                                            <i class="fas fa-<?php
                                                echo match($app['experience'] ?? 'none') {
                                                    'none' => 'user',
                                                    'some_sales' => 'chart-line',
                                                    'experienced' => 'trophy',
                                                    'influencer' => 'star'
                                                };
                                            ?> mr-1 text-xs"></i>
                                            <?php echo getExperienceLabel($app['experience']); ?>
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <!-- Quick CRUD Actions (using POST for status updates) -->
                            <div class="mt-4 lg:mt-0 flex flex-wrap gap-3">
                                <!-- View Button -->
                                <button onclick="viewApplication(<?php echo $app['id']; ?>)"
                                   class="group relative bg-gradient-to-r from-blue-500 to-blue-600 hover:from-blue-600 hover:to-blue-700 text-white px-4 py-2.5 rounded-xl font-semibold transition-all duration-300 flex items-center space-x-2 shadow-lg hover:shadow-xl transform hover:-translate-y-0.5 border border-blue-400/20"
                                   type="button" aria-label="View application by <?php echo htmlspecialchars($app['name']); ?>">
                                    <div class="absolute inset-0 bg-gradient-to-r from-white/0 via-white/10 to-white/0 opacity-0 group-hover:opacity-100 transition-opacity duration-300 rounded-xl"></div>
                                    <i class="fas fa-eye text-sm relative z-10"></i>
                                    <span class="relative z-10 font-medium">View</span>
                                </button>

                                <?php if (($app['status'] ?? 'pending') === 'pending'): ?>
                                    <!-- Approve Button (using form) -->
                                    <form method="POST" class="inline" action="">
                                        <input type="hidden" name="application_id" value="<?php echo $app['id']; ?>">
                                        <input type="hidden" name="update_status" value="1">
                                        <input type="hidden" name="status_value" value="approved">
                                        <button type="submit" class="group relative bg-gradient-to-r from-emerald-500 to-emerald-600 hover:from-emerald-600 hover:to-emerald-700 text-white px-4 py-2.5 rounded-xl font-semibold transition-all duration-300 flex items-center space-x-2 shadow-lg hover:shadow-xl transform hover:-translate-y-0.5 border border-emerald-400/20" aria-label="Approve application by <?php echo htmlspecialchars($app['name']); ?>">
                                            <div class="absolute inset-0 bg-gradient-to-r from-white/0 via-white/10 to-white/0 opacity-0 group-hover:opacity-100 transition-opacity duration-300 rounded-xl"></div>
                                            <i class="fas fa-check text-sm relative z-10"></i>
                                            <span class="relative z-10 font-medium">Approve</span>
                                        </button>
                                    </form>

                                    <!-- Reject Button (using form) -->
                                    <form method="POST" class="inline" action="">
                                        <input type="hidden" name="application_id" value="<?php echo $app['id']; ?>">
                                        <input type="hidden" name="update_status" value="1">
                                        <input type="hidden" name="status_value" value="rejected">
                                        <button type="submit" class="group relative bg-gradient-to-r from-red-500 to-red-600 hover:from-red-600 hover:to-red-700 text-white px-4 py-2.5 rounded-xl font-semibold transition-all duration-300 flex items-center space-x-2 shadow-lg hover:shadow-xl transform hover:-translate-y-0.5 border border-red-400/20" aria-label="Reject application by <?php echo htmlspecialchars($app['name']); ?>">
                                            <div class="absolute inset-0 bg-gradient-to-r from-white/0 via-white/10 to-white/0 opacity-0 group-hover:opacity-100 transition-opacity duration-300 rounded-xl"></div>
                                            <i class="fas fa-times text-sm relative z-10"></i>
                                            <span class="relative z-10 font-medium">Reject</span>
                                        </button>
                                    </form>
                                <?php endif; ?>

                                <!-- Edit Button -->
                                <button onclick="editApplication(<?php echo $app['id']; ?>)"
                                        class="group relative bg-gradient-to-r from-purple-500 to-purple-600 hover:from-purple-600 hover:to-purple-700 text-white px-4 py-2.5 rounded-xl font-semibold transition-all duration-300 flex items-center space-x-2 shadow-lg hover:shadow-xl transform hover:-translate-y-0.5 border border-purple-400/20"
                                        type="button" aria-label="Edit application by <?php echo htmlspecialchars($app['name']); ?>">
                                    <div class="absolute inset-0 bg-gradient-to-r from-white/0 via-white/10 to-white/0 opacity-0 group-hover:opacity-100 transition-opacity duration-300 rounded-xl"></div>
                                    <i class="fas fa-edit text-sm relative z-10"></i>
                                    <span class="relative z-10 font-medium">Edit</span>
                                </button>

                                <!-- Delete Button -->
                                <form method="POST" class="inline" onsubmit="return confirm('Are you sure you want to delete this application? This action cannot be undone.');">
                                    <input type="hidden" name="application_id" value="<?php echo $app['id']; ?>">
                                    <button type="submit" name="delete_application"
                                            class="group relative bg-gradient-to-r from-gray-600 to-gray-700 hover:from-gray-700 hover:to-gray-800 text-white px-4 py-2.5 rounded-xl font-semibold transition-all duration-300 flex items-center space-x-2 shadow-lg hover:shadow-xl transform hover:-translate-y-0.5 border border-gray-500/20" aria-label="Delete application by <?php echo htmlspecialchars($app['name']); ?>">
                                        <div class="absolute inset-0 bg-gradient-to-r from-white/0 via-white/10 to-white/0 opacity-0 group-hover:opacity-100 transition-opacity duration-300 rounded-xl"></div>
                                        <i class="fas fa-trash text-sm relative z-10"></i>
                                        <span class="relative z-10 font-medium">Delete</span>
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>

                    <!-- Application Details Grid -->
                    <div class="p-6">
                        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                            <!-- Contact Information -->
                            <div class="bg-gradient-to-br from-blue-50 to-blue-100 rounded-xl p-6 border-l-4 border-blue-400 shadow-sm">
                                <div class="flex items-center mb-4">
                                    <div class="w-10 h-10 bg-blue-500 rounded-full flex items-center justify-center mr-3 shadow-lg">
                                        <i class="fas fa-address-book text-white text-lg"></i>
                                    </div>
                                    <h4 class="font-bold text-gray-900 text-lg">Contact Information</h4>
                                </div>
                                <div class="space-y-3">
                                    <div class="flex items-center">
                                        <i class="fas fa-envelope text-blue-500 mr-3 w-5"></i>
                                        <div>
                                            <p class="font-medium text-gray-900">Email</p>
                                            <p class="text-gray-700"><?php echo htmlspecialchars($app['email']); ?></p>
                                        </div>
                                    </div>
                                    <div class="flex items-center">
                                        <i class="fas fa-phone text-blue-500 mr-3 w-5"></i>
                                        <div>
                                            <p class="font-medium text-gray-900">Phone</p>
                                            <p class="text-gray-700"><?php echo htmlspecialchars($app['phone'] ?? 'Not provided'); ?></p>
                                        </div>
                                    </div>
                                    <?php if (!empty($app['social_media'])): ?>
                                    <div class="flex items-center">
                                        <i class="fab fa-instagram text-blue-500 mr-3 w-5"></i>
                                        <div>
                                            <p class="font-medium text-gray-900">Social Media</p>
                                            <p class="text-gray-700">@<?php echo htmlspecialchars($app['social_media']); ?></p>
                                        </div>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <!-- Experience & Background -->
                            <div class="bg-gradient-to-br from-green-50 to-green-100 rounded-xl p-6 border-l-4 border-green-400 shadow-sm">
                                <div class="flex items-center mb-4">
                                    <div class="w-10 h-10 bg-green-500 rounded-full flex items-center justify-center mr-3 shadow-lg">
                                        <i class="fas fa-chart-line text-white text-lg"></i>
                                    </div>
                                    <h4 class="font-bold text-gray-900 text-lg">Experience & Background</h4>
                                </div>
                                <div class="space-y-3">
                                    <div class="flex items-center">
                                        <i class="fas fa-graduation-cap text-green-500 mr-3 w-5"></i>
                                        <div>
                                            <p class="font-medium text-gray-900">Experience Level</p>
                                            <p class="text-gray-700"><?php echo getExperienceLabel($app['experience']); ?></p>
                                        </div>
                                    </div>
                                    <?php if (!empty($app['experience'])): ?>
                                    <div class="flex items-center">
                                        <i class="fas fa-briefcase text-green-500 mr-3 w-5"></i>
                                        <div>
                                            <p class="font-medium text-gray-900">Background</p>
                                            <p class="text-gray-700"><?php echo ucfirst(str_replace('_', ' ', $app['experience'])); ?></p>
                                        </div>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <!-- Application Details -->
                            <div class="bg-gradient-to-br from-purple-50 to-purple-100 rounded-xl p-6 border-l-4 border-purple-400 shadow-sm">
                                <div class="flex items-center mb-4">
                                    <div class="w-10 h-10 bg-purple-500 rounded-full flex items-center justify-center mr-3 shadow-lg">
                                        <i class="fas fa-file-alt text-white text-lg"></i>
                                    </div>
                                    <h4 class="font-bold text-gray-900 text-lg">Application Details</h4>
                                </div>
                                <div class="space-y-3">
                                    <div class="flex items-center">
                                        <i class="fas fa-hashtag text-purple-500 mr-3 w-5"></i>
                                        <div>
                                            <p class="font-medium text-gray-900">Application ID</p>
                                            <p class="text-gray-700">#<?php echo $app['id']; ?></p>
                                        </div>
                                    </div>
                                    <div class="flex items-center">
                                        <i class="fas fa-calendar text-purple-500 mr-3 w-5"></i>
                                        <div>
                                            <p class="font-medium text-gray-900">Applied Date</p>
                                            <p class="text-gray-700"><?php echo date('M j, Y \a\t g:i A', strtotime($app['application_date'])); ?></p>
                                        </div>
                                    </div>
                                    <div class="flex items-center">
                                        <i class="fas fa-clock text-purple-500 mr-3 w-5"></i>
                                        <div>
                                            <p class="font-medium text-gray-900">Created</p>
                                            <p class="text-gray-700"><?php echo date('M j, Y \a\t g:i A', strtotime($app['created_at'] ?? $app['application_date'])); ?></p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- ID Document Status -->
                            <div class="bg-gradient-to-br from-amber-50 to-amber-100 rounded-xl p-6 border-l-4 border-amber-400 shadow-sm">
                                <div class="flex items-center mb-4">
                                    <div class="w-10 h-10 bg-amber-500 rounded-full flex items-center justify-center mr-3 shadow-lg">
                                        <i class="fas fa-id-card text-white text-lg"></i>
                                    </div>
                                    <h4 class="font-bold text-gray-900 text-lg">ID Verification</h4>
                                </div>
                                <div class="space-y-3">
                                    <?php if (!empty($app['id_front']) || !empty($app['id_back'])): ?>
                                        <div class="flex items-center">
                                            <i class="fas fa-check-circle text-green-500 mr-3 w-5"></i>
                                            <div>
                                                <p class="font-medium text-gray-900">Documents Uploaded</p>
                                                <p class="text-gray-700">ID verification documents provided</p>
                                            </div>
                                        </div>
                                    <?php else: ?>
                                        <div class="flex items-center">
                                            <i class="fas fa-exclamation-triangle text-amber-500 mr-3 w-5"></i>
                                            <div>
                                                <p class="font-medium text-gray-900">Documents Pending</p>
                                                <p class="text-gray-700">ID verification documents not uploaded</p>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <!-- Application Content Section -->
                        <?php if (!empty($app['motivation']) || !empty($app['message'])): ?>
                        <div class="mt-8 bg-gradient-to-br from-gray-50 to-gray-100 rounded-xl p-6 border-t-4 border-gray-300 shadow-sm">
                            <div class="flex items-center mb-6">
                                <div class="w-10 h-10 bg-gray-500 rounded-full flex items-center justify-center mr-3 shadow-lg">
                                    <i class="fas fa-quote-left text-white text-lg"></i>
                                </div>
                                <h4 class="font-bold text-gray-900 text-xl">Application Content</h4>
                            </div>

                            <div class="grid md:grid-cols-1 gap-8">
                                <?php if (!empty($app['motivation'])): ?>
                                <div class="bg-white rounded-lg p-6 shadow-inner border-l-4 border-purple-400">
                                    <div class="flex items-center mb-4">
                                        <i class="fas fa-lightbulb text-purple-500 mr-3 text-lg"></i>
                                        <h5 class="font-bold text-gray-900 text-lg">Why they want to be an ambassador:</h5>
                                    </div>
                                    <div class="prose prose-gray max-w-none">
                                        <p class="text-gray-700 leading-relaxed text-base"><?php echo nl2br(htmlspecialchars($app['motivation'])); ?></p>
                                    </div>
                                </div>
                                <?php endif; ?>

                                <?php if (!empty($app['message'])): ?>
                                <div class="bg-white rounded-lg p-6 shadow-inner border-l-4 border-blue-400">
                                    <div class="flex items-center mb-4">
                                        <i class="fas fa-comment text-blue-500 mr-3 text-lg"></i>
                                        <h5 class="font-bold text-gray-900 text-lg">Additional message:</h5>
                                    </div>
                                    <div class="prose prose-gray max-w-none">
                                        <p class="text-gray-700 leading-relaxed text-base"><?php echo nl2br(htmlspecialchars($app['message'])); ?></p>
                                    </div>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endif; ?>

                        <!-- ID Documents Section (if uploaded) -->
                        <?php if (!empty($app['id_front']) || !empty($app['id_back'])): ?>
                        <div class="mt-8 bg-gradient-to-br from-indigo-50 to-indigo-100 rounded-xl p-6 border-t-4 border-indigo-400 shadow-sm">
                            <div class="flex items-center mb-6">
                                <div class="w-10 h-10 bg-indigo-500 rounded-full flex items-center justify-center mr-3 shadow-lg">
                                    <i class="fas fa-id-card text-white text-lg"></i>
                                    </div>
                                    <h4 class="font-bold text-gray-900 text-xl">ID Verification Documents</h4>
                                </div>

                                <div class="grid md:grid-cols-2 gap-6">
                                <?php if (!empty($app['id_front'])): ?>
                                <div class="bg-white rounded-lg p-4 shadow-inner border border-gray-200">
                                    <div class="flex items-center mb-3">
                                        <i class="fas fa-address-card text-indigo-500 mr-2"></i>
                                        <h6 class="font-semibold text-gray-900">Front ID Card</h6>
                                    </div>
                                    <div class="aspect-w-16 aspect-h-10 bg-gray-100 rounded-lg overflow-hidden">
                                        <img src="../<?php echo htmlspecialchars($app['id_front']); ?>"
                                             alt="Front ID Card"
                                             class="w-full h-full object-cover"
                                             onerror="this.parentNode.innerHTML = '<div class=\'w-full h-full bg-gray-200 flex items-center justify-center text-gray-500\'><i class=\'fas fa-image text-3xl\'></i></div>';">
                                    </div>
                                </div>
                                <?php endif; ?>

                                <?php if (!empty($app['id_back'])): ?>
                                <div class="bg-white rounded-lg p-4 shadow-inner border border-gray-200">
                                    <div class="flex items-center mb-3">
                                        <i class="fas fa-address-card text-indigo-500 mr-2"></i>
                                        <h6 class="font-semibold text-gray-900">Back ID Card</h6>
                                    </div>
                                    <div class="aspect-w-16 aspect-h-10 bg-gray-100 rounded-lg overflow-hidden">
                                        <img src="../<?php echo htmlspecialchars($app['id_back']); ?>"
                                             alt="Back ID Card"
                                             class="w-full h-full object-cover"
                                             onerror="this.parentNode.innerHTML = '<div class=\'w-full h-full bg-gray-200 flex items-center justify-center text-gray-500\'><i class=\'fas fa-image text-3xl\'></i></div>';">
                                    </div>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
                <div class="px-6 py-4 border-t border-gray-200 bg-gray-50">
                    <div class="flex items-center justify-between">
                        <div class="text-sm text-gray-700">
                            Showing <?php echo (($page - 1) * $per_page) + 1; ?> to <?php echo min($page * $per_page, $total_applications); ?> of <?php echo $total_applications; ?> results
                        </div>
                        <div class="flex space-x-1">
                            <?php if ($page > 1): ?>
                                <a href="?page=<?php echo $page - 1; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo !empty($status_filter) ? '&status=' . urlencode($status_filter) : ''; ?><?php echo !empty($experience_filter) ? '&experience=' . urlencode($experience_filter) : ''; ?>"
                                   class="px-3 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50" aria-label="Go to previous page">
                                    Previous
                                </a>
                            <?php endif; ?>

                            <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                                <a href="?page=<?php echo $i; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo !empty($status_filter) ? '&status=' . urlencode($status_filter) : ''; ?><?php echo !empty($experience_filter) ? '&experience=' . urlencode($experience_filter) : ''; ?>"
                                   class="px-3 py-2 border <?php echo $i === $page ? 'bg-primary text-white' : 'border-gray-300 text-gray-700 bg-white hover:bg-gray-50'; ?> rounded-md text-sm font-medium" aria-label="Go to page <?php echo $i; ?>">
                                    <?php echo $i; ?>
                                </a>
                            <?php endfor; ?>

                            <?php if ($page < $total_pages): ?>
                                <a href="?page=<?php echo $page + 1; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo !empty($status_filter) ? '&status=' . urlencode($status_filter) : ''; ?><?php echo !empty($experience_filter) ? '&experience=' . urlencode($experience_filter) : ''; ?>"
                                   class="px-3 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50" aria-label="Go to next page">
                                    Next
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<!-- Edit Application Modal -->
<div id="editModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden z-50 modal-backdrop" role="dialog" aria-modal="true" aria-labelledby="editModalTitle">
    <div class="bg-white rounded-lg shadow-xl max-w-4xl w-full max-h-screen overflow-y-auto mx-auto my-auto">
        <div class="px-6 py-4 border-b border-gray-200">
            <div class="flex items-center justify-between">
                <h3 id="editModalTitle" class="text-lg font-semibold text-gray-900">Edit Application</h3>
                <button type="button" onclick="closeEditModal()" class="text-gray-400 hover:text-gray-600" aria-label="Close edit application modal">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
        </div>
        <div id="editModalContent" class="p-6">
            <!-- Edit form will be loaded here -->
        </div>
    </div>
</div>

<!-- View Application Modal -->
<div id="applicationModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden z-50 modal-backdrop" role="dialog" aria-modal="true" aria-labelledby="applicationModalTitle">
    <div class="bg-white rounded-lg shadow-xl max-w-5xl w-full max-h-screen overflow-y-auto mx-auto my-auto">
        <div class="px-6 py-4 border-b border-gray-200">
            <div class="flex items-center justify-between">
                <h3 id="applicationModalTitle" class="text-lg font-semibold text-gray-900">Application Details</h3>
                <button type="button" onclick="closeViewModal()" class="text-gray-400 hover:text-gray-600" aria-label="Close application details modal">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
        </div>
        <div id="modalContent" class="p-6">
            <!-- Content will be loaded here -->
        </div>
    </div>
</div>

<style>
/* Ambassador page enhancements */
.application-card {
    transition: all 0.2s ease;
}

.application-card:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

/* Status badge styling - Direct CSS instead of @apply */
.status-badge {
    padding: 0.625rem 0.5rem; /* px-2.5 py-0.5 */
    border-radius: 9999px; /* rounded-full */
    font-size: 0.75rem; /* text-xs */
    font-weight: 500; /* font-medium */
    display: inline-block;
}

.status-pending {
    background-color: rgb(254 240 138); /* bg-yellow-100 */
    color: rgb(133 77 14); /* text-yellow-800 */
}

.status-approved {
    background-color: rgb(220 252 231); /* bg-green-100 */
    color: rgb(22 163 74); /* text-green-800 */
}

.status-rejected {
    background-color: rgb(254 226 226); /* bg-red-100 */
    color: rgb(153 27 27); /* text-red-800 */
}

/* Experience badge styling - Direct CSS instead of @apply */
.experience-badge {
    padding: 0.625rem 0.5rem; /* px-2.5 py-0.5 */
    border-radius: 9999px; /* rounded-full */
    font-size: 0.75rem; /* text-xs */
    font-weight: 500; /* font-medium */
    display: inline-block;
}

.experience-none {
    background-color: rgb(243 244 246); /* bg-gray-100 */
    color: rgb(31 41 55); /* text-gray-800 */
}

.experience-some_sales {
    background-color: rgb(219 234 254); /* bg-blue-100 */
    color: rgb(30 64 175); /* text-blue-800 */
}

.experience-experienced {
    background-color: rgb(243 232 255); /* bg-purple-100 */
    color: rgb(124 58 237); /* text-purple-800 */
}

.experience-influencer {
    background-color: rgb(253 244 255); /* bg-pink-100 */
    color: rgb(157 23 77); /* text-pink-800 */
}

/* Form focus enhancements */
input:focus, textarea:focus, select:focus {
    box-shadow: 0 0 0 3px rgba(193, 39, 45, 0.1);
}

/* Content preview styling */
.line-clamp-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

/* Modern Gradient Button Styling */
.action-button {
    position: relative !important;
    overflow: hidden !important;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1) !important;
    border-radius: 0.75rem !important;
    font-weight: 600 !important;
    text-decoration: none !important;
    cursor: pointer !important;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1) !important;
    backdrop-filter: blur(10px) !important;
    -webkit-backdrop-filter: blur(10px) !important;
}

.action-button:hover {
    transform: translateY(-2px) scale(1.02) !important;
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15) !important;
}

.action-button:active {
    transform: translateY(0) scale(0.98) !important;
}

/* Button container styling */
.mt-4.lg\:mt-0.flex.flex-wrap.gap-3 {
    display: flex !important;
    flex-wrap: wrap !important;
    gap: 0.75rem !important;
    margin-top: 1rem !important;
    align-items: center !important;
}

@media (min-width: 1024px) {
    .mt-4.lg\:mt-0.flex.flex-wrap.gap-3 {
        margin-top: 0 !important;
    }
}

/* Individual button hover animations */
.action-button::before {
    content: '' !important;
    position: absolute !important;
    top: 0 !important;
    left: -100% !important;
    width: 100% !important;
    height: 100% !important;
    background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent) !important;
    transition: left 0.5s ease !important;
    border-radius: inherit !important;
}

.action-button:hover::before {
    left: 100% !important;
}

/* Icon styling within buttons */
.action-button i {
    font-size: 0.875rem !important;
    margin-right: 0.5rem !important;
    transition: transform 0.2s ease !important;
}

.action-button:hover i {
    transform: scale(1.1) !important;
}

/* Text styling within buttons */
.action-button span {
    font-size: 0.875rem !important;
    font-weight: 500 !important;
    letter-spacing: 0.025em !important;
    position: relative !important;
    z-index: 10 !important;
}

/* Responsive button adjustments */
@media (max-width: 640px) {
    .action-button {
        padding: 0.5rem 0.75rem !important;
        font-size: 0.75rem !important;
    }

    .action-button span {
        display: none !important;
    }

    .action-button i {
        margin-right: 0 !important;
        font-size: 1rem !important;
    }

    .mt-4.lg\:mt-0.flex.flex-wrap.gap-3 {
        gap: 0.5rem !important;
        justify-content: center !important;
    }
}

/* Enhanced shadow effects for better depth */
.action-button {
    filter: drop-shadow(0 2px 4px rgba(0, 0, 0, 0.1)) !important;
}

.action-button:hover {
    filter: drop-shadow(0 4px 8px rgba(0, 0, 0, 0.15)) !important;
}

/* Loading state for buttons */
.action-button:disabled {
    opacity: 0.6 !important;
    cursor: not-allowed !important;
    transform: none !important;
}

/* Focus states for accessibility */
.action-button:focus {
    outline: none !important;
    box-shadow: 0 0 0 3px rgba(193, 39, 45, 0.5) !important; /* Use primary color */
    outline-offset: 2px !important;
}

/* Enhanced application card styling */
.application-card {
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1) !important;
    border: 1px solid rgba(0, 0, 0, 0.1) !important;
}

.application-card:hover {
    transform: translateY(-4px) !important;
    box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1) !important;
}

/* Purple theme for ambassador section */
.bg-gradient-to-r.from-purple-50.to-purple-100 {
    background: linear-gradient(to right, #faf5ff, #f3e8ff);
}

.border-purple-200 {
    border-color: #e879f9;
}

.bg-purple-600 {
    background-color: #9333ea;
}

.bg-purple-700 {
    background-color: #7c3aed;
}

.text-purple-600 {
    color: #9333ea;
}

/* Enhanced card styling for detailed ambassador information */
.application-card {
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1) !important;
    border: 1px solid rgba(0, 0, 0, 0.1) !important;
}

.application-card:hover {
    transform: translateY(-4px) !important;
    box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1) !important;
    border-color: rgba(193, 39, 45, 0.2) !important;
}

/* Enhanced section backgrounds with gradients */
.bg-gradient-to-br.from-blue-50.to-blue-100 {
    background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%) !important;
}

.bg-gradient-to-br.from-green-50.to-green-100 {
    background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%) !important;
}

.bg-gradient-to-br.from-purple-50.to-purple-100 {
    background: linear-gradient(135deg, #faf5ff 0%, #f3e8ff 100%) !important;
}

.bg-gradient-to-br.from-amber-50.to-amber-100 {
    background: linear-gradient(135deg, #fffbeb 0%, #fef3c7 100%) !important;
}

.bg-gradient-to-br.from-gray-50.to-gray-100 {
    background: linear-gradient(135deg, #f9fafb 0%, #f3f4f6 100%) !important;
}

.bg-gradient-to-br.from-indigo-50.to-indigo-100 {
    background: linear-gradient(135deg, #eef2ff 0%, #e0e7ff 100%) !important;
}

/* Enhanced icon styling */
.application-card i {
    transition: transform 0.2s ease !important;
}

.application-card:hover i {
    transform: scale(1.1) !important;
}

/* Improved text formatting */
.prose.prose-gray {
    color: #374151 !important;
}

.prose.prose-gray p {
    margin-bottom: 1rem !important;
    line-height: 1.6 !important;
}

/* Enhanced aspect ratio containers */
.aspect-w-16.aspect-h-10 {
    position: relative !important;
    padding-bottom: 62.5% !important; /* 16:10 aspect ratio */
}

.aspect-w-16.aspect-h-10 > * {
    position: absolute !important;
    height: 100% !important;
    width: 100% !important;
    top: 0 !important;
    right: 0 !important;
    bottom: 0 !important;
    left: 0 !important;
}

/* ID document image styling */
.aspect-w-16.aspect-h-10 img {
    border-radius: 0.5rem !important;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1) !important;
    transition: transform 0.2s ease !important;
}

.aspect-w-16.aspect-h-10 img:hover {
    transform: scale(1.02) !important;
    box-shadow: 0 8px 15px rgba(0, 0, 0, 0.15) !important;
}

/* Enhanced border styling */
.border-l-4 {
    border-left-width: 4px !important;
}

/* Using linear gradient for border-image to create a more modern look (requires prefixing for full support) */
.border-l-4.border-blue-400 {
    border-image: linear-gradient(to bottom, #60a5fa, #bfdbfe) 1;
}
.border-l-4.border-green-400 {
    border-image: linear-gradient(to bottom, #4ade80, #86efac) 1;
}
.border-l-4.border-purple-400 {
    border-image: linear-gradient(to bottom, #c084fc, #e879f9) 1;
}
.border-l-4.border-amber-400 {
    border-image: linear-gradient(to bottom, #fbbf24, #fcd34d) 1;
}


/* Improved spacing for detailed content */
.space-y-3 > * + * {
    margin-top: 0.75rem !important;
}

/* Enhanced section headers */
.application-card h4, .application-card h5, .application-card h6 {
    font-weight: 700 !important;
    color: #1f2937 !important;
}

/* Better responsive layout */
@media (max-width: 1024px) {
    .application-card .grid-cols-1.lg\\:grid-cols-2 {
        grid-template-columns: 1fr !important;
    }
}

@media (max-width: 768px) {
    .application-card .p-6 {
        padding: 1.5rem !important;
    }

    .application-card h4 {
        font-size: 1.125rem !important;
    }

    .application-card h5 {
        font-size: 1rem !important;
    }
}

/* Enhanced modal backdrop */
.modal-backdrop {
    backdrop-filter: blur(4px);
    -webkit-backdrop-filter: blur(4px);
}

/* Enhanced button hover effects */
.action-button {
    position: relative;
    overflow: hidden;
    transition: all 0.3s ease;
}

.action-button::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
    transition: left 0.5s;
}

.action-button:hover::before {
    left: 100%;
}

/* Enhanced card animations */
.application-card {
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.application-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
}

/* Loading animation */
@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

.animate-spin {
    animation: spin 1s linear infinite;
}

/* Enhanced form styling */
.form-input:focus {
    transform: scale(1.02);
    transition: transform 0.2s ease;
}

/* Mobile responsive improvements */
@media (max-width: 768px) {
    .action-button {
        padding: 0.5rem 0.75rem;
        font-size: 0.875rem;
    }

    .application-card .flex-wrap {
        gap: 0.5rem;
    }

    @media (max-width: 480px) {
        .action-button span {
            display: none;
        }

        .action-button {
            padding: 0.5rem;
            min-width: 2.5rem;
        }
        .action-button i {
            margin-right: 0 !important;
        }
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('search');
    const statusFilter = document.getElementById('status');
    const experienceFilter = document.getElementById('experience');

    // Filter functionality for cards on the current page (optional, but good for UX)
    function applyCardFilters() {
        const searchTerm = searchInput ? searchInput.value.toLowerCase() : '';
        const statusValue = statusFilter ? statusFilter.value : '';
        const experienceValue = experienceFilter ? experienceFilter.value : '';

        document.querySelectorAll('.application-card').forEach(app => {
            const applicantName = app.dataset.applicantName || '';
            const applicantEmail = app.dataset.applicantEmail || '';
            const applicationStatus = app.dataset.applicationStatus || '';
            const experienceLevel = app.dataset.experienceLevel || '';

            let shouldShow = true;

            // Search filter
            if (searchTerm && !applicantName.includes(searchTerm) && !applicantEmail.includes(searchTerm)) {
                shouldShow = false;
            }

            // Status filter
            if (statusValue && applicationStatus !== statusValue) {
                shouldShow = false;
            }

            // Experience filter
            if (experienceValue && experienceLevel !== experienceValue) {
                shouldShow = false;
            }

            app.style.display = shouldShow ? 'block' : 'none';
        });
    }

    if (searchInput) searchInput.addEventListener('input', applyCardFilters);
    if (statusFilter) statusFilter.addEventListener('change', applyCardFilters);
    if (experienceFilter) experienceFilter.addEventListener('change', applyCardFilters);

    // Initial filter application when page loads (useful if filters are in URL)
    applyCardFilters();
});

function viewApplication(applicationId) {
    console.log('View button clicked for application ID:', applicationId);

    // Set loading state for modal content
    document.getElementById('modalContent').innerHTML = `
        <div class="flex flex-col items-center justify-center p-8 text-gray-700">
            <div class="animate-spin rounded-full h-12 w-12 border-b-4 border-primary mb-4"></div>
            <span class="text-lg font-semibold">Loading application details...</span>
            <p class="text-sm text-gray-500 mt-2">This may take a moment.</p>
        </div>`;

    // Show modal
    const modal = document.getElementById('applicationModal');
    modal.classList.remove('hidden');
    modal.style.display = 'flex';
    modal.setAttribute('aria-hidden', 'false');

    // Fetch application details via AJAX
    fetch(window.location.href, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'view_application=' + applicationId
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('Network response was not ok ' + response.statusText);
        }
        return response.text();
    })
    .then(data => {
        console.log('AJAX response received.');
        document.getElementById('modalContent').innerHTML = data;
    })
    .catch(error => {
        console.error('AJAX Error:', error);
        document.getElementById('modalContent').innerHTML = `
            <div class="p-8 text-center">
                <i class="fas fa-exclamation-triangle text-red-500 text-3xl mb-4"></i>
                <h4 class="text-lg font-semibold text-gray-900 mb-2">Error Loading Application</h4>
                <p class="text-gray-600">There was an error loading the application details. Please try again.</p>
                <button type="button" onclick="closeViewModal()" class="mt-4 bg-primary hover:bg-primary-dark text-white px-4 py-2 rounded-lg font-semibold">
                    Close
                </button>
            </div>`;
    });
}

function editApplication(applicationId) {
    console.log('Edit button clicked for application ID:', applicationId);

    // Set loading state for modal content
    document.getElementById('editModalContent').innerHTML = `
        <div class="flex flex-col items-center justify-center p-8 text-gray-700">
            <div class="animate-spin rounded-full h-12 w-12 border-b-4 border-primary mb-4"></div>
            <span class="text-lg font-semibold">Loading edit form...</span>
            <p class="text-sm text-gray-500 mt-2">This may take a moment.</p>
        </div>`;

    // Show modal
    const modal = document.getElementById('editModal');
    modal.classList.remove('hidden');
    modal.style.display = 'flex';
    modal.setAttribute('aria-hidden', 'false');

    // Fetch edit form via AJAX
    fetch(window.location.href, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'edit_application=' + applicationId
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('Network response was not ok ' + response.statusText);
        }
        return response.text();
    })
    .then(data => {
        console.log('Edit AJAX response received.');
        document.getElementById('editModalContent').innerHTML = data;
        // Optionally re-initialize any JS for dynamic form elements here
    })
    .catch(error => {
        console.error('Edit AJAX Error:', error);
        document.getElementById('editModalContent').innerHTML = `
            <div class="p-8 text-center">
                <i class="fas fa-exclamation-triangle text-red-500 text-3xl mb-4"></i>
                <h4 class="text-lg font-semibold text-gray-900 mb-2">Error Loading Edit Form</h4>
                <p class="text-gray-600">There was an error loading the edit form. Please try again.</p>
                <button type="button" onclick="closeEditModal()" class="mt-4 bg-primary hover:bg-primary-dark text-white px-4 py-2 rounded-lg font-semibold">
                    Close
                </button>
            </div>`;
    });
}

function closeViewModal() {
    const modal = document.getElementById('applicationModal');
    modal.classList.add('hidden');
    modal.style.display = 'none';
    modal.setAttribute('aria-hidden', 'true');
    document.getElementById('modalContent').innerHTML = '<!-- Content will be loaded here -->'; // Clear content for next use
}

function closeEditModal() {
    const modal = document.getElementById('editModal');
    modal.classList.add('hidden');
    modal.style.display = 'none';
    modal.setAttribute('aria-hidden', 'true');
    document.getElementById('editModalContent').innerHTML = '<!-- Edit form will be loaded here -->'; // Clear content for next use
}

function submitEditForm(form) {
    const formData = new FormData(form);

    // Disable submit button and add loading indicator
    const submitButton = form.querySelector('button[type="submit"]');
    const originalButtonHtml = submitButton.innerHTML;
    submitButton.disabled = true;
    submitButton.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> Updating...';

    fetch(window.location.href, {
        method: 'POST',
        body: formData
    })
    .then(response => {
        if (response.ok) {
            // Check if the response is an HTML redirect or something else
            return response.text().then(text => {
                // If it's a redirect, the browser will handle it.
                // If not, it means the update was handled AJAX-style, or it returned a message.
                // For simplicity, we just reload the page if successful post-update.
                location.reload();
            });
        } else {
            return response.text().then(text => { // Read response for error details
                throw new Error('Failed to update application: ' + text);
            });
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error updating application. Please try again. Details: ' + error.message);
        // Re-enable button and restore original HTML on error
        submitButton.disabled = false;
        submitButton.innerHTML = originalButtonHtml;
    });

    return false; // Prevent default form submission as we're handling it via fetch
}

// Close modal when clicking outside
window.onclick = function(event) {
    const viewModal = document.getElementById('applicationModal');
    const editModal = document.getElementById('editModal');

    if (event.target === viewModal) {
        closeViewModal();
    }
    if (event.target === editModal) {
        closeEditModal();
    }
}

// Keyboard navigation for modals (Escape key)
document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape') {
        if (!document.getElementById('applicationModal').classList.contains('hidden')) {
            closeViewModal();
        }
        if (!document.getElementById('editModal').classList.contains('hidden')) {
            closeEditModal();
        }
    }
});

</script>

<?php
// Include footer HTML
require_once 'includes/footer.php';
?>