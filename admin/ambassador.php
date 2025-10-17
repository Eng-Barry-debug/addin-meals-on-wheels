<?php
// ambassador.php - Admin Ambassador Management

// STRICT ERROR REPORTING FOR DIAGNOSIS (REMOVE IN PRODUCTION)
// error_reporting(E_ALL);
// ini_set('display_errors', 1);

// 1. Session Start
// IMPORTANT: session_start() must be called before any HTML output.
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 2. Access Control: Redirect if not logged in or not an admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] == 'admin') { // Corrected: user_role == 'admin' rather than !== 'admin' for redirect
    // Ensure only admins can access this page
    if ($_SESSION['user_role'] !== 'admin') { // Only redirect if not an admin
         header('Location: ../login.php'); // Adjust path to your login page
         exit();
    }
} else { // Not logged in
    header('Location: ../login.php');
    exit();
}


// 3. Page Configuration
$page_title = 'Ambassador Management';
$page_description = 'Manage ambassador applications and partnerships';

// 4. Include Core Dependencies
require_once 'includes/functions.php'; // Assuming functions.php contains sanitize(), etc.
require_once '../includes/config.php'; // Contains database connection ($pdo)
require_once '../includes/ActivityLogger.php'; // Custom activity logging class

// Initialize ActivityLogger
$activityLogger = new ActivityLogger($pdo);

// Initialize message variables for SweetAlert2 display
$message_type = '';
$message_text = '';

// --- 5. API Endpoint for AJAX Requests (CRUCIAL: Must be before any HTML output) ---
// This block processes AJAX requests for fetching application data for modals.
// It MUST execute and terminate script execution (`exit()`) before any HTML is sent.


// A. AJAX Request to View Application (for modal content)
if (isset($_POST['view_application'])) {
    $application_id = (int)$_POST['view_application'];

    try {
        $stmt = $pdo->prepare("SELECT * FROM ambassadors WHERE id = ?");
        $stmt->execute([$application_id]);
        $application = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($application) {
            // Include helper display functions if not already available
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
            if (!function_exists('getStatusBadgeClass')) { // Basic definition, customize if needed
                function getStatusBadgeClass(string $status): string {
                    return match($status) {
                        'pending' => 'bg-yellow-200 text-yellow-800',
                        'approved' => 'bg-green-200 text-green-800',
                        'rejected' => 'bg-red-200 text-red-800',
                        default => 'bg-gray-200 text-gray-800',
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
            ob_clean(); // Clear any previous output before rendering modal content
            ?>
            <div class="space-y-6">
                <!-- Data from view_application AJAX will be inserted here; existing structure is fine -->
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
                
                <!-- ID Documents Section (if uploaded) -->
                <?php if (!empty($application['id_front']) || !empty($application['id_back'])): ?>
                <div class="mt-8 bg-gradient-to-br from-indigo-50 to-indigo-100 rounded-xl p-6 border-t-4 border-indigo-400 shadow-sm">
                    <div class="flex items-center mb-6">
                        <div class="w-10 h-10 bg-indigo-500 rounded-full flex items-center justify-center mr-3 shadow-lg">
                            <i class="fas fa-id-card text-white text-lg"></i>
                            </div>
                            <h4 class="font-bold text-gray-900 text-xl">ID Verification Documents</h4>
                        </div>

                        <div class="grid md:grid-cols-2 gap-6">
                        <?php if (!empty($application['id_front'])): ?>
                        <div class="bg-white rounded-lg p-4 shadow-inner border border-gray-200">
                            <div class="flex items-center mb-3">
                                <i class="fas fa-address-card text-indigo-500 mr-2"></i>
                                <h6 class="font-semibold text-gray-900">Front ID Card</h6>
                            </div>
                            <div class="aspect-w-16 aspect-h-10 bg-gray-100 rounded-lg overflow-hidden">
                                <img src="../<?php echo htmlspecialchars($application['id_front']); ?>"
                                     alt="Front ID Card"
                                     class="w-full h-full object-cover"
                                     onerror="this.parentNode.innerHTML = '<div class=\'w-full h-full bg-gray-200 flex items-center justify-center text-gray-500\'><i class=\'fas fa-image text-3xl\'></i></div>';">
                            </div>
                        </div>
                        <?php endif; ?>

                        <?php if (!empty($application['id_back'])): ?>
                        <div class="bg-white rounded-lg p-4 shadow-inner border border-gray-200">
                            <div class="flex items-center mb-3">
                                <i class="fas fa-address-card text-indigo-500 mr-2"></i>
                                <h6 class="font-semibold text-gray-900">Back ID Card</h6>
                            </div>
                            <div class="aspect-w-16 aspect-h-10 bg-gray-100 rounded-lg overflow-hidden">
                                <img src="../<?php echo htmlspecialchars($application['id_back']); ?>"
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
            <?php
            exit(); // EXIT after sending JSON/HTML for the modal
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

// B. AJAX Request to Edit Application (for modal content)
if (isset($_POST['edit_application'])) {
    $application_id = (int)$_POST['edit_application'];

    try {
        $stmt = $pdo->prepare("SELECT * FROM ambassadors WHERE id = ?");
        $stmt->execute([$application_id]);
        $application = $stmt->fetch(PDO::FETCH_ASSOC);

        // Include helper getExperienceLabel if not available
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

        if ($application) {
            ob_clean(); // Clear any previous output before rendering modal content
            ?>
            <form id="editApplicationForm" onsubmit="event.preventDefault(); submitEditForm(this);" class="space-y-6">
                <!-- Form Fields -->
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
                 <!-- Hidden inputs for form submission -->
                <input type="hidden" name="application_id" value="<?php echo $application['id']; ?>">
                <input type="hidden" name="update_application" value="1">
            </form>
            <?php
            ob_clean(); // Don't clean until after a potential function definition
            exit(); // EXIT after sending JSON/HTML for the modal
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
// C. AJAX Request to Update Application (actual submission via fetch)
if (isset($_POST['update_application_ajax'])) {
    $application_id = (int)$_POST['application_id'];
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $social_media = trim($_POST['social_media']);
    $experience = $_POST['experience'];
    $motivation = trim($_POST['motivation']);
    $message = trim($_POST['message']);
    $status = $_POST['status'];

    ob_clean(); // Ensure no prior output
    header('Content-Type: application/json'); // Respond with JSON

    try {
        $stmt = $pdo->prepare("UPDATE ambassadors SET name = ?, email = ?, phone = ?, social_media = ?, experience = ?, motivation = ?, message = ?, status = ? WHERE id = ?");
        $stmt->execute([$name, $email, $phone, $social_media, $experience, $motivation, $message, $status, $application_id]);

        $activityLogger->log('ambassador', 'updated', "Updated ambassador application: {$name} (ID: {$application_id})", 'ambassador', $application_id);

        echo json_encode(['status' => 'success', 'message' => 'Application updated successfully!', 'application_id' => $application_id]);
    } catch (PDOException $e) {
        // Check for duplicate email error
        if ($e->getCode() == '23000') { // SQLSTATE for integrity constraint violation
            echo json_encode(['status' => 'error', 'message' => 'Error: Email already exists for another application.']);
        } else {
            error_log("Error updating ambassador application (AJAX, ID: {$application_id}): " . $e->getMessage());
            echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
        }
    }
    exit(); // Exit after AJAX response
}

// D. AJAX Request to Update Status (approve/reject via fetch)
if (isset($_POST['update_status_ajax'])) {
    $application_id = (int)$_POST['application_id'];
    $new_status = $_POST['new_status_value'];

    ob_clean(); // Ensure no prior output
    header('Content-Type: application/json'); // Respond with JSON

    // Basic validation
    if (!in_array($new_status, ['pending', 'approved', 'rejected'])) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid status provided.']);
        exit();
    }

    try {
        $stmt = $pdo->prepare("UPDATE ambassadors SET status = ? WHERE id = ?");
        $stmt->execute([$new_status, $application_id]);

        // Get app name for log
        $stmt_name = $pdo->prepare("SELECT name FROM ambassadors WHERE id = ?");
        $stmt_name->execute([$application_id]);
        $app_name = $stmt_name->fetchColumn();

        $activityLogger->log('ambassador', 'updated_status', "Updated application #{$application_id} ({$app_name}) status to {$new_status}", 'ambassador', $application_id);

        echo json_encode(['status' => 'success', 'message' => "Application status updated to {$new_status}!", 'application_id' => $application_id, 'new_status' => $new_status]);
    } catch (PDOException $e) {
        error_log("Error updating ambassador status (AJAX, ID: {$application_id}): " . $e->getMessage());
        echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
    }
    exit(); // Exit after AJAX response
}

// E. AJAX Request to Delete Application (via fetch)
if (isset($_POST['delete_application_ajax'])) {
    $application_id = (int)$_POST['application_id'];

    ob_clean(); // Ensure no prior output
    header('Content-Type: application/json'); // Respond with JSON

    try {
        // Get user name before deleting for activity log
        $stmt = $pdo->prepare("SELECT name, id_front, id_back FROM ambassadors WHERE id = ?");
        $stmt->execute([$application_id]);
        $app_data = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$app_data) {
            echo json_encode(['status' => 'error', 'message' => 'Application not found.']);
            exit();
        }

        $app_name_for_log = $app_data['name'];

        // Delete associated image files (ID_front, ID_back)
        if (!empty($app_data['id_front']) && file_exists('../' . $app_data['id_front'])) {
            unlink('../' . $app_data['id_front']);
        }
        if (!empty($app_data['id_back']) && file_exists('../' . $app_data['id_back'])) {
            unlink('../' . $app_data['id_back']);
        }

        $stmt = $pdo->prepare("DELETE FROM ambassadors WHERE id = ?");
        $stmt->execute([$application_id]);

        $activityLogger->log('ambassador', 'deleted', "Deleted ambassador application: {$app_name_for_log} (ID: {$application_id})", 'ambassador', $application_id);

        echo json_encode(['status' => 'success', 'message' => "Application '{$app_name_for_log}' deleted successfully!"]);
    } catch (PDOException $e) {
        error_log("Error deleting ambassador application (AJAX, ID: {$application_id}): " . $e->getMessage());
        echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
    }
    exit(); // Exit after AJAX response
}
// --- END API Endpoints ---


// 6. Handle Traditional POST Requests for CRUD Operations (If an AJAX handled request somehow didn't exit)
// This block now serves as a fallback/redundant handler for direct form submissions if JS fails to preventDefault.
// The AJAX handled requests will exit earlier.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // This is a safety net. The AJAX versions should be handling all dynamic submits now.
    // This block is mostly for completeness or if JS is disabled.
    
    // B. Update Application Details
    if (isset($_POST['update_application'])) { // This should be triggered by full form submit.
        // The AJAX version (update_application_ajax) should handle all dynamic submits now.
        // This block is mostly for completeness or if JS is disabled.
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
            $activityLogger->log('ambassador', 'updated', "Updated ambassador application: {$name} (ID: {$application_id})", 'ambassador', $application_id);
            $_SESSION['message'] = ['type' => 'success', 'text' => 'Ambassador application updated successfully!'];
        } catch (PDOException $e) {
            if ($e->getCode() == '23000') {
                $_SESSION['message'] = ['type' => 'error', 'text' => 'Error: Email already exists.'];
            } else {
                $_SESSION['message'] = ['type' => 'error', 'text' => 'Error updating application: ' . $e->getMessage()];
            }
            error_log("Error updating application (Full POST, ID: {$application_id}): " . $e->getMessage());
        }
        header('Location: ambassador.php'); // Redirect after POST to prevent re-submission
        exit();
    }
    // ... other traditional POST handlers if they exist ...

    header('Location: ambassador.php'); // Fallback redirect
    exit();
}


// 7. Process One-Time Session Messages for JavaScript (SweetAlert2) Display
if (isset($_SESSION['message'])) {
    $message_type = $_SESSION['message']['type'];
    $message_text = $_SESSION['message']['text'];
    unset($_SESSION['message']); // Clear message after retrieval
}


// --- 8. Data Retrieval for Page Display (GET requests) ---

// Get filter parameters from URL
$search = $_GET['search'] ?? '';
$status_filter = $_GET['status'] ?? '';
$experience_filter = $_GET['experience'] ?? '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 10; // Number of applications per page
$offset = ($page - 1) * $per_page;

// Prepare dynamic WHERE clauses and parameters
$where_conditions = [];
$params = [];
$base_query = "FROM ambassadors";

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

$final_where_clause = '';
if (!empty($where_conditions)) {
    $final_where_clause = 'WHERE ' . implode(' AND ', $where_conditions);
}

// 9. Get Total Count of applications for pagination (with current filters)
$total_sql = "SELECT COUNT(*) as count $base_query $final_where_clause";
$total_stmt = $pdo->prepare($total_sql);
foreach ($params as $key => $value) {
    $total_stmt->bindValue($key, $value, is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR);
}
$total_stmt->execute();
$total_applications = (int)$total_stmt->fetch(PDO::FETCH_ASSOC)['count'];

// 10. Get ambassador applications with applied filters and pagination
$sql = "SELECT * $base_query $final_where_clause ORDER BY application_date DESC LIMIT :limit OFFSET :offset";
$stmt = $pdo->prepare($sql);
foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value, is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR);
}
$stmt->bindValue(':limit', $per_page, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$applications = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 11. Calculate total pages for pagination display
$total_pages = ceil($total_applications / $per_page);

// 12. Helper Functions for Display (if not already in includes/functions.php)
// These define how badges and labels are displayed.
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
if (!function_exists('getStatusBadgeClass')) { // Custom function based on common naming convention
    function getStatusBadgeClass(string $status): string {
        return match($status) {
            'pending' => 'bg-yellow-200 text-yellow-800',
            'approved' => 'bg-green-200 text-green-800',
            'rejected' => 'bg-red-200 text-red-800',
            default => 'bg-gray-200 text-gray-800',
        };
    }
}

// 13. Include Header (starts HTML output)
require_once 'includes/header.php';
?>


<!-- ============================================== HTML BEGINS HERE ============================================== -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"/>

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
                    <h1 class="text-3xl md:text-4xl font-bold mb-2"><?= htmlspecialchars($page_title) ?></h1>
                    <p class="text-lg opacity-90 mb-2"><?= htmlspecialchars($page_description) ?></p>
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
    <?php if ($message_text): ?>
        <div class="mb-6 p-4 <?php echo $message_type === 'success' ? 'bg-green-50 border-l-4 border-green-400 text-green-700' : 'bg-red-50 border-l-4 border-red-400 text-red-700'; ?> rounded-lg flex items-center" role="alert">
            <i class="fas fa-<?php echo $message_type === 'success' ? 'check-circle' : 'exclamation-circle'; ?> mr-3"></i>
            <div>
                <p class="font-semibold"><?php echo ucfirst($message_type); ?></p>
                <p><?php echo htmlspecialchars($message_text); ?></p>
            </div>
            <button type="button" onclick="this.parentElement.remove()" class="ml-auto text-gray-400 hover:text-gray-600" aria-label="Close message">
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
                                <button type="button" onclick="viewApplication(<?php echo $app['id']; ?>)"
                                   class="group relative bg-gradient-to-r from-blue-500 to-blue-600 hover:from-blue-600 hover:to-blue-700 text-white px-4 py-2.5 rounded-xl font-semibold transition-all duration-300 flex items-center space-x-2 shadow-lg hover:shadow-xl transform hover:-translate-y-0.5 border border-blue-400/20">
                                    <div class="absolute inset-0 bg-gradient-to-r from-white/0 via-white/10 to-white/0 opacity-0 group-hover:opacity-100 transition-opacity duration-300 rounded-xl"></div>
                                    <i class="fas fa-eye text-sm relative z-10"></i>
                                    <span class="relative z-10 font-medium">View</span>
                                </button>

                                <?php if (($app['status'] ?? 'pending') === 'pending'): ?>
                                    <!-- Approve Button (using form) -->
                                    <button type="button" onclick="updateApplicationStatus(<?php echo $app['id']; ?>, 'approved')"
                                            class="group relative bg-gradient-to-r from-emerald-500 to-emerald-600 hover:from-emerald-600 hover:to-emerald-700 text-white px-4 py-2.5 rounded-xl font-semibold transition-all duration-300 flex items-center space-x-2 shadow-lg hover:shadow-xl transform hover:-translate-y-0.5 border border-emerald-400/20">
                                        <div class="absolute inset-0 bg-gradient-to-r from-white/0 via-white/10 to-white/0 opacity-0 group-hover:opacity-100 transition-opacity duration-300 rounded-xl"></div>
                                        <i class="fas fa-check text-sm relative z-10"></i>
                                        <span class="relative z-10 font-medium">Approve</span>
                                    </button>

                                    <!-- Reject Button (using form) -->
                                    <button type="button" onclick="updateApplicationStatus(<?php echo $app['id']; ?>, 'rejected')"
                                            class="group relative bg-gradient-to-r from-red-500 to-red-600 hover:from-red-600 hover:to-red-700 text-white px-4 py-2.5 rounded-xl font-semibold transition-all duration-300 flex items-center space-x-2 shadow-lg hover:shadow-xl transform hover:-translate-y-0.5 border border-red-400/20">
                                        <div class="absolute inset-0 bg-gradient-to-r from-white/0 via-white/10 to-white/0 opacity-0 group-hover:opacity-100 transition-opacity duration-300 rounded-xl"></div>
                                        <i class="fas fa-times text-sm relative z-10"></i>
                                        <span class="relative z-10 font-medium">Reject</span>
                                    </button>
                                <?php endif; ?>

                                <!-- Edit Button -->
                                <button type="button" onclick="editApplication(<?php echo $app['id']; ?>)"
                                        class="group relative bg-gradient-to-r from-purple-500 to-purple-600 hover:from-purple-600 hover:to-purple-700 text-white px-4 py-2.5 rounded-xl font-semibold transition-all duration-300 flex items-center space-x-2 shadow-lg hover:shadow-xl transform hover:-translate-y-0.5 border border-purple-400/20">
                                    <div class="absolute inset-0 bg-gradient-to-r from-white/0 via-white/10 to-white/0 opacity-0 group-hover:opacity-100 transition-opacity duration-300 rounded-xl"></div>
                                    <i class="fas fa-edit text-sm relative z-10"></i>
                                    <span class="relative z-10 font-medium">Edit</span>
                                </button>

                                <!-- Delete Button -->
                                <button type="button" onclick="confirmDeleteApplication(<?= $app['id'] ?>, '<?= htmlspecialchars($app['name']) ?>')"
                                        class="group relative bg-gradient-to-r from-gray-600 to-gray-700 hover:from-gray-700 hover:to-gray-800 text-white px-4 py-2.5 rounded-xl font-semibold transition-all duration-300 flex items-center space-x-2 shadow-lg hover:shadow-xl transform hover:-translate-y-0.5 border border-gray-500/20">
                                    <div class="absolute inset-0 bg-gradient-to-r from-white/0 via-white/10 to-white/0 opacity-0 group-hover:opacity-100 transition-opacity duration-300 rounded-xl"></div>
                                    <i class="fas fa-trash text-sm relative z-10"></i>
                                    <span class="relative z-10 font-medium">Delete</span>
                                </button>
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
                                            <p><span class="font-medium text-gray-900">Application ID</p>
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
                            <?php
                                // Helper function to build pagination URL
                                function getAmbassadorPaginationUrl($page_num, $search_term, $status_fil, $experience_fil) {
                                    $url_params = [];
                                    if ($page_num > 1) $url_params['page'] = $page_num;
                                    if (!empty($search_term)) $url_params['search'] = urlencode($search_term);
                                    if (!empty($status_fil)) $url_params['status'] = urlencode($status_fil);
                                    if (!empty($experience_fil)) $url_params['experience'] = urlencode($experience_fil);
                                    return '?' . http_build_query($url_params);
                                }
                            ?>
                            <?php if ($page > 1): ?>
                                <a href="<?php echo getAmbassadorPaginationUrl($page - 1, $search, $status_filter, $experience_filter); ?>"
                                   class="px-3 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50" aria-label="Go to previous page">
                                    Previous
                                </a>
                            <?php endif; ?>

                            <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                                <a href="<?php echo getAmbassadorPaginationUrl($i, $search, $status_filter, $experience_filter); ?>"
                                   class="px-3 py-2 border <?php echo $i === $page ? 'bg-primary text-white' : 'border-gray-300 text-gray-700 bg-white hover:bg-gray-50'; ?> rounded-md text-sm font-medium" aria-label="Go to page <?php echo $i; ?>">
                                    <?php echo $i; ?>
                                </a>
                            <?php endfor; ?>

                            <?php if ($page < $total_pages): ?>
                                <a href="<?php echo getAmbassadorPaginationUrl($page + 1, $search, $status_filter, $experience_filter); ?>"
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

<!-- ============================================== MODALS ============================================== -->

<!-- View Application Modal Structure (Styled from orders.php) -->
<div id="viewApplicationModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4 animate__animated animate__fadeIn">
    <div class="bg-white rounded-2xl shadow-2xl max-w-5xl w-full max-h-[90vh] overflow-y-auto animate__animated animate__zoomIn">
        <!-- Modal Header -->
        <div class="bg-gradient-to-r from-orange-500 to-orange-600 p-6 text-white rounded-t-2xl">
            <div class="flex items-center justify-between">
                <h3 id="applicationModalTitle" class="text-xl font-bold">
                    <i class="fas fa-eye mr-2"></i>Application Details
                </h3>
                <button type="button" onclick="closeModal('viewApplicationModal')" class="text-white hover:text-gray-200 text-2xl">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <p class="text-sm font-medium opacity-90">Ambassador: <span id="viewApplicationName"></span></p>
        </div>

        <!-- Modal Body -->
        <div id="viewModalContent" class="p-6 text-gray-700">
            <!-- Content will be loaded here via AJAX -->
            <div class="flex flex-col items-center justify-center p-8 text-gray-700">
                <div class="animate-spin rounded-full h-12 w-12 border-b-4 border-primary mb-4"></div>
                <span class="text-lg font-semibold">Loading application details...</span>
                <p class="text-sm text-gray-500 mt-2">This may take a moment.</p>
            </div>
        </div>

        <!-- Modal Footer - Optional for view modal, but good for consistency -->
        <div class="p-6 border-t border-gray-200 flex gap-3 justify-end">
            <button type="button" onclick="closeModal('viewApplicationModal')"
                    class="group relative bg-gray-500 hover:bg-gray-600 text-white px-6 py-3 rounded-xl font-semibold transition-all duration-300 flex items-center justify-center shadow-lg hover:shadow-xl transform hover:-translate-y-0.5">
                <div class="absolute inset-0 bg-gradient-to-r from-white/0 via-white/10 to-white/0 opacity-0 group-hover:opacity-100 transition-opacity duration-300 rounded-xl"></div>
                <span class="relative z-10 font-medium">Close</span>
            </button>
        </div>
    </div>
</div>

<!-- Edit Application Modal Structure (Styled from orders.php) -->
<div id="editApplicationModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4 animate__animated animate__fadeIn">
    <div class="bg-white rounded-2xl shadow-2xl max-w-4xl w-full max-h-[90vh] overflow-hidden flex flex-col animate__animated animate__zoomIn">
        <!-- Modal Header -->
       <div class="bg-gradient-to-r from-orange-500 to-orange-600 p-6 text-white rounded-t-2xl">
            <div class="flex items-center justify-between">
                <h3 id="editModalTitle" class="text-xl font-bold">
                    <i class="fas fa-pencil-alt mr-2"></i>Edit Application <span id="editApplicationName" class="font-mono text-purple-100 italic"></span>
                </h3>
                <button type="button" onclick="closeModal('editApplicationModal')" class="text-white hover:text-gray-200 text-2xl">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        </div>

        <!-- Modal Body (this part will now scroll) -->
        <div id="editModalContent" class="p-6 text-gray-700 overflow-y-auto flex-grow">
            <!-- Edit form will be loaded here via AJAX -->
            <div class="flex flex-col items-center justify-center p-8 text-gray-700">
                <div class="animate-spin rounded-full h-12 w-12 border-b-4 border-primary mb-4"></div>
                <span class="text-lg font-semibold">Loading edit form...</span>
                <p class="text-sm text-gray-500 mt-2">This may take a moment.</p>
            </div>
        </div>

        <!-- Modal Footer (fixed at the bottom) -->
        <div class="p-6 border-t border-gray-200 flex gap-3 justify-end flex-shrink-0">
             <button type="button" onclick="closeModal('editApplicationModal')"
                    class="group relative bg-gray-500 hover:bg-gray-600 text-white px-6 py-3 rounded-xl font-semibold transition-all duration-300 flex items-center justify-center shadow-lg hover:shadow-xl transform hover:-translate-y-0.5">
                <div class="absolute inset-0 bg-gradient-to-r from-white/0 via-white/10 to-white/0 opacity-0 group-hover:opacity-100 transition-opacity duration-300 rounded-xl"></div>
                <span class="relative z-10 font-medium">Cancel</span>
            </button>
            <button type="submit" form="editApplicationForm"
                    class="group relative bg-primary hover:bg-primary-dark text-white px-6 py-3 rounded-xl font-semibold transition-all duration-300 flex items-center justify-center shadow-lg hover:shadow-xl transform hover:-translate-y-0.5">
                <div class="absolute inset-0 bg-gradient-to-r from-white/0 via-white/10 to-white/0 opacity-0 group-hover:opacity-100 transition-opacity duration-300 rounded-xl"></div>
                <i class="fas fa-save mr-2 relative z-10"></i>
                <span class="relative z-10 font-medium">Update Application</span>
            </button>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal Structure (Styled from orders.php) -->
<div id="deleteConfirmationModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4 animate__animated animate__fadeIn">
    <div class="bg-white rounded-2xl shadow-2xl max-w-md w-full max-h-[90vh] overflow-y-auto animate__animated animate__zoomIn">
        <!-- Modal Header -->
        <div class="bg-gradient-to-r from-orange-500 to-orange-600 p-6 text-white rounded-t-2xl">
            <div class="flex items-center justify-between">
                <h3 id="deleteModalTitle" class="text-xl font-bold">
                    <i class="fas fa-exclamation-triangle mr-2"></i>Confirm Deletion
                </h3>
                <button type="button" onclick="closeModal('deleteConfirmationModal')" class="text-white hover:text-gray-200 text-2xl">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        </div>

        <!-- Modal Body -->
        <div class="p-6 text-gray-700">
            <p class="text-lg font-medium mb-3">Are you sure you want to delete the application by <span id="appNameToDelete" class="font-bold text-red-600"></span>?</p>
            <p>This action cannot be undone. All associated data will be permanently removed.</p>
        </div>

        <!-- Modal Footer -->
        <div class="p-6 border-t border-gray-200 flex gap-3 justify-end">
            <button type="button" onclick="closeModal('deleteConfirmationModal')"
                    class="group relative bg-gray-500 hover:bg-gray-600 text-white px-6 py-3 rounded-xl font-semibold transition-all duration-300 flex items-center justify-center shadow-lg hover:shadow-xl transform hover:-translate-y-0.5">
                <div class="absolute inset-0 bg-gradient-to-r from-white/0 via-white/10 to-white/0 opacity-0 group-hover:opacity-100 transition-opacity duration-300 rounded-xl"></div>
                <span class="relative z-10 font-medium">Cancel</span>
            </button>
            <form method="POST" class="inline" id="deleteApplicationForm">
                <input type="hidden" name="application_id" id="deleteAppId">
                <button type="submit" name="delete_application_ajax"
                        class="group relative bg-red-600 hover:bg-red-700 text-white px-6 py-3 rounded-xl font-semibold transition-all duration-300 flex items-center justify-center shadow-lg hover:shadow-xl transform hover:-translate-y-0.5">
                    <div class="absolute inset-0 bg-gradient-to-r from-white/0 via-white/10 to-white/0 opacity-0 group-hover:opacity-100 transition-opacity duration-300 rounded-xl"></div>
                    <i class="fas fa-trash mr-2 relative z-10"></i>
                    <span class="relative z-10 font-medium">Delete</span>
                </button>
            </form>
        </div>
    </div>
</div>


<!-- ============================================== JAVASCRIPT & STYLES ============================================== -->
<!-- Animate.css for modal animations -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"/>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script> <!-- SweetAlert2 CDN -->
<script>
// --- Global Utility Functions for Modals (from orders.php) ---

// Enhanced Global utility to close modals with better error handling and animation support
function closeModal(modalId) {
    if (!modalId || typeof modalId !== 'string') {
        console.warn('closeModal: Invalid modal ID provided');
        return;
    }

    const modal = document.getElementById(modalId);
    if (!modal) {
        console.warn(`closeModal: Modal with ID "${modalId}" not found`);
        return;
    }

    // Add fade-out animation class if available
    modal.classList.add('animate__fadeOut');
    setTimeout(() => {
        modal.classList.add('hidden');
        modal.classList.remove('animate__fadeOut', 'animate__animated', 'animate__fadeIn'); // Clean up animation classes
        // Re-enable background scrolling
        document.body.classList.remove('overflow-y-hidden');
    }, 300); // Match animation duration

    // Clear content of AJAX-loaded modals when closing
    if (modalId === 'viewApplicationModal') {
        document.getElementById('viewModalContent').innerHTML = `
            <div class="flex flex-col items-center justify-center p-8 text-gray-700">
                <div class="animate-spin rounded-full h-12 w-12 border-b-4 border-primary mb-4"></div>
                <span class="text-lg font-semibold">Loading application details...</span>
                <p class="text-sm text-gray-500 mt-2">This may take a moment.</p>
            </div>`;
        document.getElementById('viewApplicationName').textContent = ''; // Clear name in header
    } else if (modalId === 'editApplicationModal') {
        document.getElementById('editModalContent').innerHTML = `
            <div class="flex flex-col items-center justify-center p-8 text-gray-700">
                <div class="animate-spin rounded-full h-12 w-12 border-b-4 border-primary mb-4"></div>
                <span class="text-lg font-semibold">Loading edit form...</span>
                <p class="text-sm text-gray-500 mt-2">This may take a moment.</p>
            </div>`;
        document.getElementById('editApplicationName').textContent = ''; // Clear name in header
    }
}

// Enhanced Global utility to open modals with animation support
function openModal(modalId) {
    if (!modalId || typeof modalId !== 'string') {
        console.warn('openModal: Invalid modal ID provided');
        return;
    }

    const modal = document.getElementById(modalId);
    if (!modal) {
        console.warn(`openModal: Modal with ID "${modalId}" not found`);
        return;
    }

    // Hide all other modals first (optional, but good for single modal display)
    const allModals = document.querySelectorAll('.fixed.inset-0.modal-backdrop');
    allModals.forEach(m => {
        if (m.id !== modalId) {
            m.classList.add('hidden');
            m.classList.remove('animate__fadeIn', 'animate__zoomIn');
        }
    });

    modal.classList.remove('hidden');
    modal.classList.add('animate__animated', 'animate__fadeIn'); // Add entrance animation
    // Disable background scrolling
    document.body.classList.add('overflow-y-hidden');
}


// Close modal when clicking outside (on the black overlay) - from orders.php
window.addEventListener('click', function(event) {
    const modalIds = ['viewApplicationModal', 'editApplicationModal', 'deleteConfirmationModal']; // All modal IDs
    modalIds.forEach(id => {
        const modal = document.getElementById(id);
        if (modal && !modal.classList.contains('hidden')) { // Check if it's open
            // Check if the click was directly on the backdrop (not inside the modal content)
            if (event.target === modal) {
                closeModal(id);
            }
        }
    });
});

// Keyboard navigation for modals (Escape key) - from orders.php
document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape') {
        const modalIds = ['viewApplicationModal', 'editApplicationModal', 'deleteConfirmationModal']; // All modal IDs
        for (const id of modalIds) {
            const modal = document.getElementById(id);
            if (modal && !modal.classList.contains('hidden')) {
                closeModal(id); // Close the topmost visible modal
                break;
            }
        }
    }
});


// --- SweetAlert2 Message on Page Load (from PHP session) ---
document.addEventListener('DOMContentLoaded', function() {
    const messageType = "<?php echo $message_type; ?>";
    const messageText = "<?php echo htmlspecialchars($message_text, ENT_QUOTES); ?>";

    if (messageType && messageText) {
        Swal.fire({
            icon: messageType === 'success' ? 'success' : 'error',
            title: messageType === 'success' ? 'Success!' : 'Error!',
            text: messageText,
            showConfirmButton: false,
            timer: 3000
        });
    }

    // --- Filtering Logic for Search ---
    const searchInput = document.getElementById('search');
    const statusFilter = document.getElementById('status');
    const experienceFilter = document.getElementById('experience');

    // Function to apply filters by reloading the page with new URL parameters
    const applyFilters = () => {
        let url = new URL(window.location.origin + window.location.pathname);
        if (searchInput.value) url.searchParams.set('search', searchInput.value);
        if (statusFilter.value) url.searchParams.set('status', statusFilter.value);
        if (experienceFilter.value) url.searchParams.set('experience', experienceFilter.value);
        // Reset page to 1 when filters change
        url.searchParams.delete('page');
        window.location.href = url.toString();
    };

    // Add event listeners to filter fields
    searchInput.addEventListener('change', applyFilters); // Changed to 'change' for less frequent reload
    statusFilter.addEventListener('change', applyFilters);
    experienceFilter.addEventListener('change', applyFilters);

    // Attach event listener for delete form submission
    const deleteApplicationForm = document.getElementById('deleteApplicationForm');
    if (deleteApplicationForm) {
        deleteApplicationForm.addEventListener('submit', async function(event) {
            event.preventDefault(); // Prevent default form submission

            const form = event.target;
            // The submit button is now outside the form but linked via form="id"
            // We need to find it by ID or class within the modal footer
            const submitButton = document.querySelector('#deleteConfirmationModal button[type="submit"][form="deleteApplicationForm"]');
            const originalButtonHtml = submitButton ? submitButton.innerHTML : '';


            if (submitButton) {
                submitButton.disabled = true;
                submitButton.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> Deleting...';
            }

            const formData = new FormData(form);
            // Add a flag to distinguish this AJAX request on the PHP side
            formData.append('delete_application_ajax', '1');

            try {
                const response = await fetch('ambassador.php', {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();

                if (result.status === 'success') {
                    Swal.fire('Deleted!', result.message, 'success').then(() => {
                        closeModal('deleteConfirmationModal');
                        location.reload(); // Reload the page to show changes
                    });
                } else {
                    Swal.fire('Error!', result.message, 'error');
                }
            } catch (error) {
                console.error('Error:', error);
                Swal.fire('Error!', 'An unexpected error occurred.', 'error');
            } finally {
                if (submitButton) {
                    submitButton.disabled = false;
                    submitButton.innerHTML = originalButtonHtml; // Restore button text
                }
            }
        });
    }
});

// --- Application CRUD Functions ---

// Function to open and load content into the View Application Modal
async function viewApplication(applicationId) {
    const viewModalContent = document.getElementById('viewModalContent');
    const viewApplicationNameSpan = document.getElementById('viewApplicationName');

    // Show loading state and open modal
    viewModalContent.innerHTML = `
        <div class="flex flex-col items-center justify-center p-8 text-gray-700">
            <div class="animate-spin rounded-full h-12 w-12 border-b-4 border-primary mb-4"></div>
            <span class="text-lg font-semibold">Loading application details...</span>
            <p class="text-sm text-gray-500 mt-2">This may take a moment.</p>
        </div>`;
    viewApplicationNameSpan.textContent = 'Loading...'; // Interim text
    openModal('viewApplicationModal'); // Use the new openModal function

    try {
        const response = await fetch('ambassador.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `view_application=${applicationId}`
        });
        if (!response.ok) {
            ob_clean(); // Ensure no prior output before error handling
            const errorText = await response.text();
            throw new Error(`HTTP error! status: ${response.status}, ${errorText}`);
        }
        const htmlContent = await response.text();
        viewModalContent.innerHTML = htmlContent;
        // After content is loaded, extract name for modal header if available
        const tempDiv = document.createElement('div');
        tempDiv.innerHTML = htmlContent;
        // The H3 inside the ajax response has the name, but might also have the first letter
        // Safest to find the actual name displayed within the content, e.g., in a p tag after "Full Name" or similar if structure changes
        // For now, let's target the existing h3 assuming it contains the full name
        const appNameElement = tempDiv.querySelector('h3.font-bold'); // Assuming the main name is in this h3
        if (appNameElement) {
            viewApplicationNameSpan.textContent = appNameElement.textContent.trim();
        } else {
             // Fallback to searching data-name on a relevant element if available
             const fallbackNameElement = document.querySelector(`[data-id="${applicationId}"]`);
             if (fallbackNameElement && fallbackNameElement.dataset.name) {
                 viewApplicationNameSpan.textContent = fallbackNameElement.dataset.name;
             } else {
                 viewApplicationNameSpan.textContent = 'Unknown';
             }
        }


    } catch (error) {
        console.error('Error fetching application for view:', error);
        viewModalContent.innerHTML = `
            <div class="p-8 text-center text-red-600">
                <i class="fas fa-exclamation-triangle text-3xl mb-4"></i>
                <p>Error loading application details. ${error.message}</p>
            </div>`;
        viewApplicationNameSpan.textContent = 'Error';
    }
}

// Function to open and load content into the Edit Application Modal
async function editApplication(applicationId) {
    const editModalContent = document.getElementById('editModalContent');
    const editApplicationNameSpan = document.getElementById('editApplicationName');

    // Show loading state and open modal
    editModalContent.innerHTML = `
        <div class="flex flex-col items-center justify-center p-8 text-gray-700">
            <div class="animate-spin rounded-full h-12 w-12 border-b-4 border-primary mb-4"></div>
            <span class="text-lg font-semibold">Loading edit form...</span>
            <p class="text-sm text-gray-500 mt-2">This may take a moment.</p>
        </div>`;
    editApplicationNameSpan.textContent = 'Loading...'; // Interim text
    openModal('editApplicationModal'); // Use the new openModal function

    try {
        const response = await fetch('ambassador.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `edit_application=${applicationId}`
        });
        if (!response.ok) {
            ob_clean(); // Ensure no prior output before error handling
            const errorText = await response.text();
            throw new Error(`HTTP error! status: ${response.status}, ${errorText}`);
        }
        const htmlContent = await response.text();
        editModalContent.innerHTML = htmlContent;

        // After content is loaded, extract name for modal header if available
        const tempDiv = document.createElement('div');
        tempDiv.innerHTML = htmlContent;
        const appNameElement = tempDiv.querySelector('#edit_name'); // Assuming edit_name input has the name
        if (appNameElement) {
            editApplicationNameSpan.textContent = appNameElement.value.trim();
        } else {
             const fallbackNameElement = document.querySelector(`[data-id="${applicationId}"]`);
             if (fallbackNameElement && fallbackNameElement.dataset.name) {
                 editApplicationNameSpan.textContent = fallbackNameElement.dataset.name;
             } else {
                editApplicationNameSpan.textContent = 'Unknown';
             }
        }
    } catch (error) {
        console.error('Error fetching application for edit:', error);
        editModalContent.innerHTML = `
            <div class="p-8 text-center text-red-600">
                <i class="fas fa-exclamation-triangle text-3xl mb-4"></i>
                <p>Error loading edit form. ${error.message}</p>
            </div>`;
        editApplicationNameSpan.textContent = 'Error';
    }
}

// Function to handle submission of the Edit Form (loaded via AJAX)
async function submitEditForm(form) {
    const submitButton = document.querySelector('#editApplicationModal button[type="submit"][form="editApplicationForm"]');
    const originalButtonHtml = submitButton ? submitButton.innerHTML : ''; // Store original HTML

    if (submitButton) {
        submitButton.disabled = true;
        submitButton.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> Updating...';
    }

    const formData = new FormData(form);
    // Add a flag to distinguish this AJAX request on the PHP side
    formData.append('update_application_ajax', '1');

    try {
        const response = await fetch('ambassador.php', {
            method: 'POST',
            body: formData
        });

        const result = await response.json();

        if (result.status === 'success') {
            Swal.fire('Updated!', result.message, 'success').then(() => {
                closeModal('editApplicationModal');
                location.reload(); // Reload the page to reflect changes
            });
        } else {
            Swal.fire('Error!', result.message, 'error');
        }
    } catch (error) {
        console.error('Error:', error);
        Swal.fire('Error!', 'An unexpected error occurred.', 'error');
    } finally {
        if (submitButton) {
            submitButton.disabled = false;
            submitButton.innerHTML = originalButtonHtml; // Restore original HTML
        }
    }
}


// Function to trigger update status (approve/reject) after confirmation
function updateApplicationStatus(applicationId, newStatus) {
    Swal.fire({
        title: 'Are you sure?',
        text: `You are about to change the application status to ${newStatus}. This action can be undone.`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: `Yes, ${newStatus} it!`
    }).then(async (result) => { // Made async to use await for fetch
        if (result.isConfirmed) {
            // Send AJAX request to update status
            const formData = new FormData();
            formData.append('application_id', applicationId);
            formData.append('new_status_value', newStatus);
            formData.append('update_status_ajax', '1'); // Flag for AJAX handler

            try {
                const response = await fetch('ambassador.php', {
                    method: 'POST',
                    body: formData
                });
                const responseData = await response.json();

                if (responseData.status === 'success') {
                    Swal.fire('Updated!', responseData.message, 'success').then(() => {
                        location.reload(); // Reload to reflect status change
                    });
                } else {
                    Swal.fire('Error!', responseData.message, 'error');
                }
            } catch (error) {
                console.error('Error updating status via AJAX:', error);
                Swal.fire('Error!', 'Failed to update status. Please try again.', 'error');
            }
        }
    });
}

// Function to open the delete confirmation modal
function confirmDeleteApplication(applicationId, appName) {
    document.getElementById('deleteAppId').value = applicationId;
    document.getElementById('appNameToDelete').textContent = appName;
    openModal('deleteConfirmationModal'); // Use the new openModal function
}


</script>

<style>
/* Filter tab styling (from orders.php - not directly used in ambassador.php but good to keep consistent) */
.filter-tab {
    @apply px-4 py-2 rounded-lg font-medium transition-all duration-200;
}

.filter-tab.active {
    @apply bg-red-600 text-white shadow-md; /* Orders uses red, ambassador purple. Will keep this red as it's not active here */
}

.filter-tab:not(.active) {
    @apply bg-gray-100 text-gray-600 hover:bg-gray-200;
}

/* Base button styling for the new gradient buttons (from orders.php) */
.group.relative.bg-gradient-to-r {
    @apply transition-transform duration-300 ease-in-out;
}

.group.relative.bg-gradient-to.r:hover {
    @apply transform -translate-y-0.5 shadow-xl;
}

.group.relative.bg-gradient-to.r .absolute.inset-0 {
    @apply opacity-0 transition-opacity duration-300;
}

.group.relative.bg-gradient-to.r:hover .absolute.inset-0 {
    @apply opacity-100;
}

/* Specific styling for the purple primary action buttons adapted to ambassador colors */
.action-button,
.group.relative.bg-gradient-to-r.from-blue-500, /* View Button */
.group.relative.bg-gradient-to-r.from-emerald-500, /* Approve Button */
.group.relative.bg-gradient-to-r.from-red-500, /* Reject Button */
.group.relative.bg-gradient-to-r.from-purple-500, /* Edit Button */
.group.relative.bg-gradient-to-r.from-gray-600 /* Delete Button */
{
    @apply inline-flex items-center space-x-2 px-4 py-2.5 rounded-xl font-semibold transition-all duration-300 shadow-lg hover:shadow-xl transform hover:-translate-y-0.5 border;
}

.group.relative.bg-gradient-to-r.from-blue-500 { @apply from-blue-500 to-blue-600 hover:from-blue-600 hover:to-blue-700 text-white border-blue-400/20; }
.group.relative.bg-gradient-to-r.from-emerald-500 { @apply from-emerald-500 to-emerald-600 hover:from-emerald-600 hover:to-emerald-700 text-white border-emerald-400/20; }
.group.relative.bg-gradient-to-r.from-red-500 { @apply from-red-500 to-red-600 hover:from-red-600 hover:to-red-700 text-white border-red-400/20; }
.group.relative.bg-gradient-to-r.from-purple-500 { @apply from-purple-500 to-purple-600 hover:from-purple-600 hover:to-purple-700 text-white border-purple-400/20; }
.group.relative.bg-gradient-to-r.from-gray-600 { @apply from-gray-600 to-gray-700 hover:from-gray-700 hover:to-gray-800 text-white border-gray-500/20; }


/* Form focus enhancements */
input:focus, textarea:focus, select:focus {
    box-shadow: 0 0 0 3px rgba(193, 39, 45, 0.1); /* Primary color shadow based on ambassador's colors */
}

/* Content preview styling */
.line-clamp-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}


/* Responsive button adjustments */
/* @media (max-width: 640px) ranges are already in ambassador.php's existing styles */


/* Enhanced shadow effects for better depth */
.action-button, .group.relative.bg-gradient-to-r {
    filter: drop-shadow(0 2px 4px rgba(0, 0, 0, 0.1));
}

.action-button:hover, .group.relative.bg-gradient-to-r:hover {
    filter: drop-shadow(0 4px 8px rgba(0, 0, 0, 0.15));
}

/* Loading state for buttons */
.action-button:disabled, .group.relative.bg-gradient-to-r:disabled {
    opacity: 0.6;
    cursor: not-allowed;
    transform: none;
}

/* Focus states for accessibility */
.action-button:focus, .group.relative.bg-gradient-to-r:focus {
    outline: none;
    box-shadow: 0 0 0 3px rgba(193, 39, 45, 0.5); /* Use primary color */
    outline-offset: 2px;
}

/* Enhanced application card styling from ambassador.php */
.application-card {
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    border: 1px solid rgba(0, 0, 0, 0.1);
}

.application-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
    border-color: rgba(193, 39, 45, 0.2);
}

/* Modal styles from orders.php */
/* Combined and refined for ambassador.php */
.fixed.inset-0.modal-backdrop {
    /* Base for backdrop - already had these */
    background-color: rgba(0, 0, 0, 0.5); /* from orders.php's bg-black bg-opacity-50 */
    display: flex; /* from orders.php */
    align-items: center; /* from orders.php */
    justify-content: center; /* from orders.php */
    padding: 1rem; /* p-4 from orders.php */
}
@media (min-width: 640px) { /* sm:p-6 from orders.php */
    .fixed.inset-0.modal-backdrop {
        padding: 1.5rem;
    }
}

.fixed.inset-0.modal-backdrop > div { /* This targets the inner modal content box */
    @apply bg-white rounded-2xl shadow-2xl w-full max-h-[90vh] overflow-y-auto animate__animated;
}

/* Adjustments for specific modal headers to match ambassador colors, not order reds/purples */
#viewApplicationModal .bg-gradient-to-r,
#editApplicationModal .bg-gradient-to-r {
    @apply from-purple-600 to-purple-700; /* Primary ambassador colors */
}

#deleteConfirmationModal .bg-gradient-to-r {
    @apply from-orange-500 to-orange-600; /* Matching delete modal from orders.php */
}

/* Small adjustments for specific elements to match the general orders.php buttons look better */
/* For buttons in the fixed footer, they are not part of AJAX content, so we define here */
/* These styles were already somewhat present, slight refinement for consistency */
#editApplicationModal .modal-footer button {
    @apply px-6 py-3 rounded-xl font-semibold transition-colors duration-300 flex items-center justify-center shadow-lg hover:shadow-xl transform hover:-translate-y-0.5;
}

#editApplicationModal .modal-footer button[type="submit"] {
    @apply bg-primary hover:bg-primary-dark text-white;
}

#editApplicationModal .modal-footer button[type="button"] { /* Cancel button */
    @apply bg-gray-500 hover:bg-gray-600 text-white;
}


/* Animate.css fade-in/out for modals */
.animate__animated.animate__fadeIn {
    animation-duration: 0.3s;
}
.animate__animated.animate__fadeOut {
    animation-duration: 0.3s;
}
.animate__animated.animate__zoomIn {
    animation-duration: 0.3s;
}

</style>

<?php require_once 'includes/footer.php'; ?>