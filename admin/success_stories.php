<?php
// success_stories.php - Admin Success Stories Management
// This file provides a comprehensive interface for managing success stories/testimonials.
// All CRUD operations (Add, Edit, Delete, Toggle Status, Reorder) are handled via AJAX/modals.

// 1. Session Start
// IMPORTANT: session_start() must be called before any HTML output.
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// 2. Page Configuration
$page_title = 'Success Stories Management';
$page_description = 'Manage customer and ambassador success stories';

// 3. Include Core Dependencies
require_once dirname(__DIR__) . '/includes/config.php'; // Contains database connection ($pdo)
require_once 'includes/functions.php'; // For getRecordById, deleteRecord (assuming these exist here)
require_once dirname(__DIR__) . '/includes/ActivityLogger.php'; // Custom activity logging class

// Initialize ActivityLogger
$activityLogger = new ActivityLogger($pdo);

// Initialize message variables to be passed to JavaScript for SweetAlert2 display
$message_type = '';
$message_text = '';

// --- 4. API Endpoint for Fetching Single Success Story Data (CRUCIAL: Must be before any HTML output) ---
// This block handles AJAX/fetch() requests from JavaScript to get specific success story details for editing.
// It MUST execute and terminate script execution (`exit()`) before any HTML is sent.
if (isset($_GET['fetch_story_data']) && is_numeric($_GET['fetch_story_data'])) {
    $story_id_to_fetch = (int)$_GET['fetch_story_data'];
    try {
        $stmt = $pdo->prepare("SELECT * FROM success_stories WHERE id = ?");
        $stmt->execute([$story_id_to_fetch]);
        $story_data = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($story_data) {
            // Ensure numeric values are properly cast for client-side JavaScript handling
            $story_data['rating'] = (int)$story_data['rating'];
            // Convert is_active to boolean for JS
            $story_data['is_active'] = (bool)$story_data['is_active'];

            header('Content-Type: application/json');
            echo json_encode($story_data);
        } else {
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Success story not found']);
        }
    } catch (PDOException $e) {
        // Log and return database error details
        error_log("DB Error fetching specific success story (ID: {$story_id_to_fetch}): " . $e->getMessage());
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Database error', 'details' => $e->getMessage()]);
    }
    exit(); // Crucial: Stop further script execution for API responses
}
// --- END API Endpoint ---


// 5. Handle POST Requests for CRUD Operations
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // A. Delete Success Story Operation
    if (isset($_POST['_method']) && $_POST['_method'] === 'DELETE') {
        $id = (int)$_POST['id'];
        $story_name_for_log = 'Unknown Story'; // Default for logging if story not found

        // Retrieve success story details (for image deletion and logging)
        $story_details = getRecordById('success_stories', $id, 'image,name,story'); // Assume getRecordById fetches specific columns
        if ($story_details) {
            $story_name_for_log = $story_details['name'];
            // Delete associated image file if it exists
            if (!empty($story_details['image'])) {
                $imagePath = '../uploads/success_stories/' . $story_details['image'];
                if (file_exists($imagePath) && is_writable($imagePath)) { // Added is_writable check
                    unlink($imagePath);
                } else {
                    error_log("Failed to delete image: {$imagePath} (file not found or not writable)");
                }
            }
        }

        // Perform database deletion
        if (deleteRecord('success_stories', $id)) { // Assume deleteRecord function exists in includes/functions.php
            $_SESSION['message'] = ['type' => 'success', 'text' => "'{$story_name_for_log}' (ID: {$id}) deleted successfully."];
            $activityLogger->logActivity("Success story '{$story_name_for_log}' (ID: {$id}) deleted.", $_SESSION['user_id'] ?? null, 'success_story_delete');
        } else {
            $_SESSION['message'] = ['type' => 'error', 'text' => "Failed to delete success story '{$story_name_for_log}' (ID: {$id})."];
            error_log("Failed to delete success story with ID: {$id}");
        }
    }

    // B. Toggle Active Status Operation
    elseif (isset($_POST['action']) && $_POST['action'] === 'toggle_status') {
        $id = (int)$_POST['id'];
        $story_name_for_log = 'Unknown Story';

        // Fetch current value and name for accurate toggling and logging
        $current_story_state_stmt = $pdo->prepare("SELECT is_active, name FROM success_stories WHERE id = ?");
        $current_story_state_stmt->execute([$id]);
        $current_story_data = $current_story_state_stmt->fetch(PDO::FETCH_ASSOC);

        if ($current_story_data) {
            $story_name_for_log = $current_story_data['name'];
            $new_status_value = ($current_story_data['is_active'] == 1) ? 0 : 1; // Toggle 1/0
            $log_entry = "Success story '{$story_name_for_log}' (ID: {$id}) status changed to " . ($new_status_value ? 'active' : 'inactive') . ".";

            $update_stmt = $pdo->prepare("UPDATE success_stories SET is_active = :new_value, updated_at = NOW() WHERE id = :id");
            if ($update_stmt->execute([':new_value' => $new_status_value, ':id' => $id])) {
                $_SESSION['message'] = ['type' => 'success', 'text' => "Success story '{$story_name_for_log}' updated successfully."];
                $activityLogger->logActivity($log_entry, $_SESSION['user_id'] ?? null, 'success_story_update');
            } else {
                $_SESSION['message'] = ['type' => 'error', 'text' => "Failed to update status for '{$story_name_for_log}' (ID: {$id})."];
                error_log("Failed to update success story status for ID: {$id}");
            }
        } else {
            $_SESSION['message'] = ['type' => 'error', 'text' => "Success story with ID {$id} not found."];
        }
    }

    // C. Add New Success Story Operation
    elseif (isset($_POST['add_success_story'])) {
        $name = trim($_POST['name'] ?? '');
        $role = trim($_POST['role'] ?? '');
        $story = trim($_POST['story'] ?? '');
        $rating = (int)($_POST['rating'] ?? 5);
        $is_active = isset($_POST['is_active']) ? 1 : 0;
        $image_name = null;

        $errors = [];
        if (empty($name)) $errors[] = "Name is required.";
        if (empty($role)) $errors[] = "Role/Title is required.";
        if (empty($story)) $errors[] = "Story content is required.";
        if ($rating < 1 || $rating > 5) $errors[] = "Rating must be between 1 and 5.";

        if (empty($errors)) {
            // Handle image upload
            if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                $uploadDir = '../uploads/success_stories/';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0777, true);
                }
                $fileExtension = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
                $image_name = uniqid('story_') . '.' . $fileExtension;
                $targetPath = $uploadDir . $image_name;

                if (!move_uploaded_file($_FILES['image']['tmp_name'], $targetPath)) {
                    $errors[] = "Failed to upload image.";
                    $image_name = null; // Reset image name if upload fails
                }
            } else if ($_FILES['image']['error'] !== UPLOAD_ERR_NO_FILE) {
                $errors[] = "Error uploading image: " . $_FILES['image']['error'];
            }
        }

        if (empty($errors)) {
            try {
                $stmt = $pdo->prepare("INSERT INTO success_stories (name, role, story, rating, image, is_active, sort_order, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, 0, NOW(), NOW())");
                $stmt->execute([$name, $role, $story, $rating, $image_name, $is_active]);
                $new_story_id = $pdo->lastInsertId();
                $_SESSION['message'] = ['type' => 'success', 'text' => "New success story '{$name}' added successfully."];
                $activityLogger->logActivity("New success story '{$name}' (ID: {$new_story_id}) added.", $_SESSION['user_id'] ?? null, 'success_story_add');
            } catch (PDOException $e) {
                $errors[] = "Database error: " . $e->getMessage();
                error_log("Error adding success story: " . $e->getMessage());
            }
        }

        if (!empty($errors)) {
            $_SESSION['message'] = ['type' => 'error', 'text' => "Failed to add success story: " . implode(" ", $errors)];
        }
    }

    // D. Edit Success Story Operation
    elseif (isset($_POST['edit_success_story'])) {
        $id = (int)$_POST['id'];
        $name = trim($_POST['name'] ?? '');
        $role = trim($_POST['role'] ?? '');
        $story = trim($_POST['story'] ?? '');
        $rating = (int)($_POST['rating'] ?? 5);
        $is_active = isset($_POST['is_active']) ? 1 : 0;
        $image_name = null; // Will be set if a new image is uploaded

        $errors = [];
        if ($id <= 0) $errors[] = "Invalid story ID for update.";
        if (empty($name)) $errors[] = "Name is required.";
        if (empty($role)) $errors[] = "Role/Title is required.";
        if (empty($story)) $errors[] = "Story content is required.";
        if ($rating < 1 || $rating > 5) $errors[] = "Rating must be between 1 and 5.";

        if (empty($errors)) {
            // Fetch current image to retain if no new one is uploaded
            $current_story = getRecordById('success_stories', $id, 'image,name');
            $original_image = $current_story['image'] ?? null;
            $story_name_for_log = $current_story['name'] ?? 'Unknown Story';

            // Handle new image upload
            if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                $uploadDir = '../uploads/success_stories/';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0777, true);
                }
                $fileExtension = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
                $image_name = uniqid('story_') . '.' . $fileExtension;
                $targetPath = $uploadDir . $image_name;

                if (move_uploaded_file($_FILES['image']['tmp_name'], $targetPath)) {
                    // Delete old image if a new one is uploaded
                    if (!empty($original_image) && file_exists($uploadDir . $original_image) && is_writable($uploadDir . $original_image)) {
                        unlink($uploadDir . $original_image);
                    }
                } else {
                    $errors[] = "Failed to upload new image.";
                    $image_name = $original_image; // Revert to original if new upload fails
                }
            } else if ($_FILES['image']['error'] !== UPLOAD_ERR_NO_FILE) {
                $errors[] = "Error uploading image: " . $_FILES['image']['error'];
                $image_name = $original_image; // Revert to original on error
            } else {
                // No new image uploaded, retain the original
                $image_name = $original_image;
            }

            if (empty($errors)) {
                // Build the UPDATE query dynamically to only update changed fields
                $updateFields = [];
                $updateValues = [];

                // Always update these fields
                $updateFields[] = "name = ?";
                $updateValues[] = $name;
                $updateFields[] = "role = ?";
                $updateValues[] = $role;
                $updateFields[] = "story = ?";
                $updateValues[] = $story;
                $updateFields[] = "rating = ?";
                $updateValues[] = $rating;
                $updateFields[] = "is_active = ?";
                $updateValues[] = $is_active;
                $updateFields[] = "updated_at = NOW()";

                // Only update the image if a new one is uploaded
                if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                    $updateFields[] = "image = ?";
                    $updateValues[] = $image_name;
                }

                $updateFieldsStr = implode(", ", $updateFields);
                $stmt = $pdo->prepare("UPDATE success_stories SET $updateFieldsStr WHERE id = ?");
                $updateValues[] = $id; // Add the ID at the end
                $stmt->execute($updateValues);

                $_SESSION['message'] = ['type' => 'success', 'text' => "Success story '{$name}' (ID: {$id}) updated successfully."];
                $activityLogger->logActivity("Success story '{$story_name_for_log}' (ID: {$id}) updated.", $_SESSION['user_id'] ?? null, 'success_story_update');
            }
        }

        if (!empty($errors)) {
            $_SESSION['message'] = ['type' => 'error', 'text' => "Failed to update success story: " . implode(" ", $errors)];
        }
    }

    // E. Reorder Success Stories
    elseif (isset($_POST['reorder_stories'])) {
        $order_data = json_decode($_POST['order_data'], true);

        if (is_array($order_data)) {
            try {
                $pdo->beginTransaction();

                foreach ($order_data as $index => $story_id) {
                    $stmt = $pdo->prepare("UPDATE success_stories SET sort_order = ?, updated_at = NOW() WHERE id = ?");
                    $stmt->execute([$index, $story_id]);
                }

                $pdo->commit();
                $_SESSION['message'] = ['type' => 'success', 'text' => "Success stories reordered successfully."];
                $activityLogger->logActivity("Success stories reordered.", $_SESSION['user_id'] ?? null, 'success_story_reorder');
            } catch (Exception $e) {
                $pdo->rollBack();
                $_SESSION['message'] = ['type' => 'error', 'text' => "Failed to reorder success stories."];
                error_log("Error reordering success stories: " . $e->getMessage());
            }
        }
    }

    header('Location: success_stories.php'); // Redirect to refresh and display message
    exit();
}


// 6. Process One-Time Session Messages for JavaScript (SweetAlert2) Display
if (isset($_SESSION['message'])) {
    $message_type = $_SESSION['message']['type'];
    $message_text = $_SESSION['message']['text'];
    unset($_SESSION['message']); // Clear message after retrieval
}


// --- 7. Data Retrieval for Page Display (GET requests) ---

// Get filter parameters from URL
$status_filter = $_GET['status'] ?? '';
$search_term = $_GET['search'] ?? '';

// Prepare dynamic WHERE clauses and parameters
$params = [];
$where_conditions = [];

if (!empty($search_term)) {
    $where_conditions[] = "(name LIKE :search_term OR role LIKE :search_role OR story LIKE :search_story)";
    $params[':search_term'] = '%' . $search_term . '%';
    $params[':search_role'] = '%' . $search_term . '%';
    $params[':search_story'] = '%' . $search_term . '%';
}

if (!empty($status_filter)) {
    if ($status_filter === 'active') {
        $where_conditions[] = "is_active = 1";
    } elseif ($status_filter === 'inactive') {
        $where_conditions[] = "is_active = 0";
    }
}

$final_where_clause = '';
if (!empty($where_conditions)) {
    $final_where_clause = " WHERE " . implode(" AND ", $where_conditions);
}

// 8. Total Record Count for Pagination (with current filters)
$total_records_query = "SELECT COUNT(*) FROM success_stories $final_where_clause";
$count_stmt = $pdo->prepare($total_records_query);
foreach ($params as $key => $value) {
    $count_stmt->bindValue($key, $value, PDO::PARAM_STR);
}
$count_stmt->execute();
$total_items = $count_stmt->fetchColumn();

// 9. Pagination Setup
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1; // Ensure page is at least 1
$per_page = 8; // Number of success stories per page
$offset = ($page - 1) * $per_page;
$total_pages = ceil($total_items / $per_page);

// 10. Main Success Stories Data Retrieval for Current Page
$query_success_stories = "
    SELECT *
    FROM success_stories
    $final_where_clause
    ORDER BY sort_order ASC, created_at DESC
    LIMIT :limit OFFSET :offset";

$stmt_success_stories = $pdo->prepare($query_success_stories);
foreach ($params as $key => $value) {
    $stmt_success_stories->bindValue($key, $value, PDO::PARAM_STR);
}
$stmt_success_stories->bindValue(':limit', (int)$per_page, PDO::PARAM_INT);
$stmt_success_stories->bindValue(':offset', (int)$offset, PDO::PARAM_INT);

try {
    $stmt_success_stories->execute();
    $success_stories = $stmt_success_stories->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Database error fetching success stories for display: " . $e->getMessage());
    $success_stories = []; // Fallback empty array on error
    $error_message = "An error occurred while fetching success stories. Please try again later.";
}

// 11. Get Success Stories Statistics (for dashboard cards and filter tabs)
function getSuccessStoriesCounts($pdo, $search_term, $status_filter) {
    $counts = [
        'total' => 0,
        'active' => 0,
        'inactive' => 0,
    ];

    $base_sql_count = "SELECT COUNT(*) FROM success_stories";
    $count_conditions = [];
    $count_params = [];

    if (!empty($search_term)) {
        $count_conditions[] = "(name LIKE :search_term OR role LIKE :search_role OR story LIKE :search_story)";
        $count_params[':search_term'] = '%' . $search_term . '%';
        $count_params[':search_role'] = '%' . $search_term . '%';
        $count_params[':search_story'] = '%' . $search_term . '%';
    }

    if (!empty($status_filter)) {
        if ($status_filter === 'active') {
            $count_conditions[] = "is_active = 1";
        } elseif ($status_filter === 'inactive') {
            $count_conditions[] = "is_active = 0";
        }
    }

    $count_where_clause = '';
    if (!empty($count_conditions)) {
        $count_where_clause = " WHERE " . implode(" AND ", $count_conditions);
    }

    // Total Stories
    $stmt_total = $pdo->prepare($base_sql_count . $count_where_clause);
    $stmt_total->execute($count_params);
    $counts['total'] = $stmt_total->fetchColumn();

    // Active Stories
    $active_conditions = $count_conditions;
    $active_conditions[] = "is_active = 1";
    $stmt_active = $pdo->prepare($base_sql_count . " WHERE " . implode(" AND ", $active_conditions));
    $stmt_active->execute($count_params);
    $counts['active'] = $stmt_active->fetchColumn();

    // Inactive Stories
    $inactive_conditions = $count_conditions;
    $inactive_conditions[] = "is_active = 0";
    $stmt_inactive = $pdo->prepare($base_sql_count . " WHERE " . implode(" AND ", $inactive_conditions));
    $stmt_inactive->execute($count_params);
    $counts['inactive'] = $stmt_inactive->fetchColumn();

    return $counts;
}

$story_counts = getSuccessStoriesCounts($pdo, $search_term, $status_filter);

// 12. Include Header (starts HTML output)
include 'includes/header.php';
?>

<!-- ============================================== HTML BEGINS HERE ============================================== -->

<!-- Dashboard Header -->
<div class="bg-gradient-to-br from-primary via-primary-dark to-secondary text-white">
    <div class="container mx-auto px-6 py-8">
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between">
            <div>
                <h1 class="text-3xl md:text-4xl font-bold mb-2">Success Stories Management</h1>
                <p class="text-lg opacity-90">Manage customer and ambassador testimonials</p>
            </div>
            <div class="mt-4 lg:mt-0">
                <button type="button" onclick="openAddStoryModal()"
                   class="bg-accent hover:bg-accent-dark text-white px-6 py-3 rounded-lg font-semibold transition-colors duration-200 flex items-center shadow-lg hover:shadow-xl transform hover:-translate-y-1">
                    <i class="fas fa-plus mr-2"></i>
                    Add New Story
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Statistics Cards -->
<div class="bg-gray-50 py-8">
    <div class="container mx-auto px-6">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div class="bg-white rounded-xl shadow-lg p-6 border-l-4 border-primary">
                <div class="flex items-center">
                    <div class="p-3 bg-primary/10 rounded-lg">
                        <i class="fas fa-star text-2xl text-primary"></i>
                    </div>
                    <div class="ml-4">
                        <h3 class="text-2xl font-bold text-gray-900"><?php echo $story_counts['total'] ?? 0; ?></h3>
                        <p class="text-gray-600">Total Stories (Filtered)</p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-lg p-6 border-l-4 border-green-500">
                <div class="flex items-center">
                    <div class="p-3 bg-green-100 rounded-lg">
                        <i class="fas fa-check-circle text-2xl text-green-600"></i>
                    </div>
                    <div class="ml-4">
                        <h3 class="text-2xl font-bold text-gray-900"><?php echo $story_counts['active'] ?? 0; ?></h3>
                        <p class="text-gray-600">Active Stories (Filtered)</p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-lg p-6 border-l-4 border-gray-500">
                <div class="flex items-center">
                    <div class="p-3 bg-gray-100 rounded-lg">
                        <i class="fas fa-eye-slash text-2xl text-gray-600"></i>
                    </div>
                    <div class="ml-4">
                        <h3 class="text-2xl font-bold text-gray-900"><?php echo $story_counts['inactive'] ?? 0; ?></h3>
                        <p class="text-gray-600">Inactive Stories (Filtered)</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Main Content -->
<div class="container mx-auto px-6 py-8">
    <!-- Filter & Search Section -->
    <div class="bg-white rounded-xl shadow-lg p-6 mb-8">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Search Input -->
            <div class="lg:col-span-2">
                <label class="block text-sm font-semibold text-gray-700 mb-2" for="searchInput">Search Stories</label>
                <div class="relative">
                    <input type="text" id="searchInput" value="<?php echo htmlspecialchars($search_term); ?>"
                           placeholder="Search by name, role, or story content..."
                           class="w-full pl-12 pr-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent transition-colors">
                    <i class="fas fa-search absolute left-4 top-1/2 transform -translate-y-1/2 text-gray-400"></i>
                </div>
            </div>

            <!-- Status Filter -->
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2" for="statusFilter">Status</label>
                <select id="statusFilter" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent transition-colors">
                    <option value="">All Status</option>
                    <option value="active" <?php echo $status_filter === 'active' ? 'selected' : ''; ?>>Active</option>
                    <option value="inactive" <?php echo $status_filter === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                </select>
            </div>
        </div>

        <!-- Quick Filter Tabs -->
        <div class="mt-6 pt-6 border-t border-gray-200">
            <div class="flex flex-wrap gap-2">
                <button type="button" class="filter-tab <?php echo empty($status_filter) ? 'active' : ''; ?>" data-filter="all">
                    All Stories <span class="ml-1 bg-gray-100 text-gray-600 px-2 py-1 rounded-full text-xs"><?php echo $story_counts['total'] ?? 0; ?></span>
                </button>
                <button type="button" class="filter-tab <?php echo $status_filter === 'active' ? 'active' : ''; ?>" data-filter="active">
                    Active <span class="ml-1 bg-green-100 text-green-600 px-2 py-1 rounded-full text-xs"><?php echo $story_counts['active'] ?? 0; ?></span>
                </button>
                <button type="button" class="filter-tab <?php echo $status_filter === 'inactive' ? 'active' : ''; ?>" data-filter="inactive">
                    Inactive <span class="ml-1 bg-gray-100 text-gray-600 px-2 py-1 rounded-full text-xs"><?php echo $story_counts['inactive'] ?? 0; ?></span>
                </button>
                <?php if ($status_filter || $search_term): ?>
                    <button type="button" onclick="clearFilters()" class="ml-4 text-primary hover:text-primary-dark font-medium">
                        <i class="fas fa-times mr-1"></i>Clear Filters
                    </button>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Success Stories Grid -->
    <?php if (count($success_stories) > 0): ?>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6" id="storiesGrid">
            <?php foreach ($success_stories as $story):
                $isActive = $story['is_active'] ?? true;
            ?>
                <div class="bg-white rounded-xl shadow-lg overflow-hidden story-card" data-story-id="<?php echo $story['id']; ?>">
                    <div class="p-6">
                        <div class="flex items-center mb-4">
                            <?php if (!empty($story['image']) && file_exists('../uploads/success_stories/' . $story['image'])): ?>
                                <img src="../uploads/success_stories/<?php echo htmlspecialchars($story['image']); ?>"
                                     alt="<?php echo htmlspecialchars($story['name']); ?>"
                                     class="w-16 h-16 rounded-full object-cover mr-4">
                            <?php else: ?>
                                <div class="w-16 h-16 rounded-full bg-gray-200 flex items-center justify-center mr-4">
                                    <i class="fas fa-user text-2xl text-gray-400"></i>
                                </div>
                            <?php endif; ?>
                            <div class="flex-1">
                                <h4 class="font-bold text-lg text-gray-900"><?php echo htmlspecialchars($story['name']); ?></h4>
                                <p class="text-gray-600 text-sm"><?php echo htmlspecialchars($story['role']); ?></p>
                            </div>
                            <div class="flex items-center">
                                <span class="px-2 py-1 text-xs rounded-full <?php echo $isActive ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'; ?>">
                                    <?php echo $isActive ? 'Active' : 'Inactive'; ?>
                                </span>
                            </div>
                        </div>

                        <p class="text-gray-700 text-sm mb-4 line-clamp-3"><?php echo htmlspecialchars(substr($story['story'], 0, 150)) . (strlen($story['story']) > 150 ? '...' : ''); ?></p>

                        <div class="flex items-center justify-between">
                            <div class="flex text-yellow-400">
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                    <i class="fas fa-star<?php echo $i <= ($story['rating'] ?? 5) ? '' : '-o'; ?> text-sm"></i>
                                <?php endfor; ?>
                                <span class="ml-1 text-sm text-gray-600">(<?php echo $story['rating'] ?? 5; ?>)</span>
                            </div>
                        </div>
                    </div>

                    <div class="px-6 py-4 bg-gray-50 border-t border-gray-100">
                        <div class="flex justify-between items-center">
                            <div class="flex space-x-2">
                                <button type="button" onclick="openEditStoryModal(<?php echo $story['id']; ?>)"
                                        class="text-primary hover:text-primary-dark transition-colors" title="Edit Story">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button type="button" onclick="toggleStoryStatus(<?php echo $story['id']; ?>)"
                                        class="transition-colors <?php echo $isActive ? 'text-green-600 hover:text-green-700' : 'text-gray-600 hover:text-gray-700'; ?>"
                                        title="<?php echo $isActive ? 'Deactivate' : 'Activate'; ?> Story">
                                    <i class="fas fa-toggle-<?php echo $isActive ? 'on' : 'off'; ?>"></i>
                                </button>
                                <button type="button" onclick="confirmDeleteStory(<?php echo $story['id']; ?>, '<?php echo htmlspecialchars(addslashes($story['name'])); ?>')"
                                        class="text-red-600 hover:text-red-700 transition-colors" title="Delete Story">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                            <div class="text-xs text-gray-500">
                                ID: <?php echo $story['id']; ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
            <div class="mt-8 flex justify-center">
                <nav class="flex items-center space-x-2">
                    <?php if ($page > 1): ?>
                        <a href="?page=<?php echo $page - 1; ?><?php echo !empty($search_term) ? '&search=' . urlencode($search_term) : ''; ?><?php echo !empty($status_filter) ? '&status=' . urlencode($status_filter) : ''; ?>"
                           class="px-3 py-2 text-sm font-medium text-gray-500 bg-white border border-gray-300 rounded-md hover:bg-gray-50">
                            Previous
                        </a>
                    <?php endif; ?>

                    <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                        <a href="?page=<?php echo $i; ?><?php echo !empty($search_term) ? '&search=' . urlencode($search_term) : ''; ?><?php echo !empty($status_filter) ? '&status=' . urlencode($status_filter) : ''; ?>"
                           class="px-3 py-2 text-sm font-medium <?php echo $i === $page ? 'text-white bg-primary border border-primary' : 'text-gray-500 bg-white border border-gray-300 hover:bg-gray-50'; ?> rounded-md">
                            <?php echo $i; ?>
                        </a>
                    <?php endfor; ?>

                    <?php if ($page < $total_pages): ?>
                        <a href="?page=<?php echo $page + 1; ?><?php echo !empty($search_term) ? '&search=' . urlencode($search_term) : ''; ?><?php echo !empty($status_filter) ? '&status=' . urlencode($status_filter) : ''; ?>"
                           class="px-3 py-2 text-sm font-medium text-gray-500 bg-white border border-gray-300 rounded-md hover:bg-gray-50">
                            Next
                        </a>
                    <?php endif; ?>
                </nav>
            </div>
        <?php endif; ?>

    <?php else: ?>
        <!-- Empty State -->
        <div class="text-center py-16">
            <div class="mx-auto w-24 h-24 bg-gray-100 rounded-full flex items-center justify-center mb-6">
                <i class="fas fa-star text-4xl text-gray-400"></i>
            </div>
            <h3 class="text-xl font-semibold text-gray-900 mb-2">No success stories found</h3>
            <p class="text-gray-600 mb-8">
                <?php if ($search_term || $status_filter): ?>
                    Try adjusting your filters or search terms.
                <?php else: ?>
                    Get started by adding your first success story.
                <?php endif; ?>
            </p>
            <?php if (!$search_term && !$status_filter): ?>
                <button type="button" onclick="openAddStoryModal()"
                        class="bg-primary hover:bg-primary-dark text-white px-6 py-3 rounded-lg font-semibold transition-colors duration-200 inline-flex items-center">
                    <i class="fas fa-plus mr-2"></i>
                    Add First Story
                </button>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

<!-- Delete Confirmation Modal -->
<div id="deleteModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
    <div class="bg-white rounded-2xl shadow-2xl max-w-md w-full">
        <!-- Modal Header -->
        <div class="bg-gradient-to-r from-red-600 to-red-700 p-6 text-white rounded-t-2xl">
            <div class="flex items-center justify-between">
                <h3 class="text-xl font-bold">
                    <i class="fas fa-exclamation-triangle mr-2"></i>Confirm Deletion
                </h3>
                <button type="button" onclick="document.getElementById('deleteModal').classList.add('hidden'); document.getElementById('deleteModal').classList.remove('animate__fadeIn', 'animate__zoomIn');" class="text-white hover:text-gray-200 text-2xl">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        </div>

        <!-- Modal Body -->
        <div class="p-6">
            <p class="text-gray-700 mb-4">
                Are you sure you want to delete the success story for <strong id="deleteStoryName"></strong>?
                This action cannot be undone.
            </p>
            <p class="text-sm text-gray-500 mb-6">
                Story ID: <span id="deleteStoryId"></span>
            </p>

            <form action="success_stories.php" method="POST">
                <input type="hidden" name="_method" value="DELETE">
                <input type="hidden" name="id" id="deleteStoryIdInput">
            </form>
        </div>

        <!-- Modal Footer -->
        <div class="p-6 border-t border-gray-200 flex gap-3 justify-end">
            <button type="button" onclick="document.getElementById('deleteModal').classList.add('hidden'); document.getElementById('deleteModal').classList.remove('animate__fadeIn', 'animate__zoomIn');"
                    class="bg-gray-500 hover:bg-gray-600 text-white px-6 py-3 rounded-lg font-semibold transition-colors">
                Cancel
            </button>
            <button type="submit" form="deleteModal form"
                    class="bg-red-600 hover:bg-red-700 text-white px-6 py-3 rounded-lg font-semibold transition-colors">
                <i class="fas fa-trash mr-2"></i>
                Delete Story
            </button>
        </div>
    </div>
</div>

<!-- Edit Success Story Modal -->
<div id="editStoryModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
    <div class="bg-white rounded-2xl shadow-2xl max-w-2xl w-full max-h-[90vh] overflow-y-auto">
        <!-- Modal Header -->
        <div class="bg-gradient-to-r from-blue-600 to-blue-700 p-6 text-white rounded-t-2xl">
            <div class="flex items-center justify-between">
                <h3 class="text-xl font-bold">
                    <i class="fas fa-edit mr-2"></i>Edit Success Story
                </h3>
                <button type="button" onclick="document.getElementById('editStoryModal').classList.add('hidden'); document.getElementById('editStoryModal').classList.remove('animate__fadeIn', 'animate__zoomIn');" class="text-white hover:text-gray-200 text-2xl">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        </div>

        <!-- Modal Body -->
        <div class="p-6">
            <form action="success_stories.php" method="POST" enctype="multipart/form-data" id="editStoryForm" class="space-y-6">
                <input type="hidden" name="edit_success_story" value="1">
                <input type="hidden" name="id" id="edit_story_id">

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <!-- Name -->
                    <div>
                        <label for="edit_name" class="block text-sm font-semibold text-gray-700 mb-2">Name <span class="text-red-500">*</span></label>
                        <input type="text" name="name" id="edit_name" required
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-600 focus:border-transparent transition-colors">
                    </div>
                    <!-- Role -->
                    <div>
                        <label for="edit_role" class="block text-sm font-semibold text-gray-700 mb-2">Role/Title <span class="text-red-500">*</span></label>
                        <input type="text" name="role" id="edit_role" required
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-600 focus:border-transparent transition-colors">
                    </div>
                </div>

                <!-- Story Content -->
                <div>
                    <label for="edit_story" class="block text-sm font-semibold text-gray-700 mb-2">Story Content <span class="text-red-500">*</span></label>
                    <textarea name="story" id="edit_story" rows="4" required
                              class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-600 focus:border-transparent transition-colors"
                              placeholder="Share the success story or testimonial..."></textarea>
                </div>

                <!-- Rating -->
                <div>
                    <label for="edit_rating" class="block text-sm font-semibold text-gray-700 mb-2">Rating (1-5 stars)</label>
                    <select name="rating" id="edit_rating"
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-600 focus:border-transparent transition-colors">
                        <option value="5">5 Stars - Excellent</option>
                        <option value="4">4 Stars - Very Good</option>
                        <option value="3">3 Stars - Good</option>
                        <option value="2">2 Stars - Fair</option>
                        <option value="1">1 Star - Poor</option>
                    </select>
                </div>

                <!-- Image Upload -->
                <div>
                    <label for="edit_image" class="block text-sm font-semibold text-gray-700 mb-2">Profile Image</label>
                    <input type="file" name="image" id="edit_image" accept="image/*"
                           class="w-full text-gray-700 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                    <p class="text-xs text-gray-500 mt-1">Leave blank to keep current image. Max 2MB, JPG/PNG.</p>
                    <div id="edit_current_image_preview" class="mt-2" style="display:none;">
                        <span class="text-sm text-gray-600">Current Image:</span>
                        <img id="edit_image_thumb" src="" alt="Current Image" class="block w-24 h-24 object-cover rounded-lg border border-gray-200 mt-1">
                    </div>
                </div>

                <!-- Active Status Toggle -->
                <div class="flex items-center space-x-2">
                    <input type="checkbox" name="is_active" id="edit_is_active" class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                    <label for="edit_is_active" class="text-sm font-medium text-gray-700">Story is Active</label>
                </div>
            </form>
        </div>

        <!-- Modal Footer -->
        <div class="p-6 border-t border-gray-200 flex gap-3 justify-end">
            <button type="button" onclick="document.getElementById('editStoryModal').classList.add('hidden'); document.getElementById('editStoryModal').classList.remove('animate__fadeIn', 'animate__zoomIn');"
                    class="bg-gray-500 hover:bg-gray-600 text-white px-6 py-3 rounded-lg font-semibold transition-colors">
                Cancel
            </button>
            <button type="submit" form="editStoryForm"
                    class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-lg font-semibold transition-colors">
                <i class="fas fa-save mr-2"></i>
                Save Changes
            </button>
        </div>
    </div>
</div>

<!-- Add New Success Story Modal -->
<div id="addStoryModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
    <div class="bg-white rounded-2xl shadow-2xl max-w-2xl w-full max-h-[90vh] overflow-y-auto">
        <!-- Modal Header -->
        <div class="bg-gradient-to-r from-green-600 to-green-700 p-6 text-white rounded-t-2xl">
            <div class="flex items-center justify-between">
                <h3 class="text-xl font-bold">
                    <i class="fas fa-plus-circle mr-2"></i>Add New Success Story
                </h3>
                <button type="button" onclick="document.getElementById('addStoryModal').classList.add('hidden'); document.getElementById('addStoryModal').classList.remove('animate__fadeIn', 'animate__zoomIn'); document.getElementById('addStoryForm')?.reset();" class="text-white hover:text-gray-200 text-2xl">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        </div>

        <!-- Modal Body -->
        <div class="p-6">
            <form action="success_stories.php" method="POST" enctype="multipart/form-data" id="addStoryForm" class="space-y-6">
                <input type="hidden" name="add_success_story" value="1">

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <!-- Name -->
                    <div>
                        <label for="add_name" class="block text-sm font-semibold text-gray-700 mb-2">Name <span class="text-red-500">*</span></label>
                        <input type="text" name="name" id="add_name" required
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-600 focus:border-transparent transition-colors">
                    </div>
                    <!-- Role -->
                    <div>
                        <label for="add_role" class="block text-sm font-semibold text-gray-700 mb-2">Role/Title <span class="text-red-500">*</span></label>
                        <input type="text" name="role" id="add_role" required
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-600 focus:border-transparent transition-colors"
                               placeholder="e.g. University Student, Young Entrepreneur">
                    </div>
                </div>

                <!-- Story Content -->
                <div>
                    <label for="add_story" class="block text-sm font-semibold text-gray-700 mb-2">Story Content <span class="text-red-500">*</span></label>
                    <textarea name="story" id="add_story" rows="4" required
                              class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-600 focus:border-transparent transition-colors"
                              placeholder="Share the success story or testimonial..."></textarea>
                </div>

                <!-- Rating -->
                <div>
                    <label for="add_rating" class="block text-sm font-semibold text-gray-700 mb-2">Rating (1-5 stars)</label>
                    <select name="rating" id="add_rating"
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-600 focus:border-transparent transition-colors">
                        <option value="5">5 Stars - Excellent</option>
                        <option value="4">4 Stars - Very Good</option>
                        <option value="3">3 Stars - Good</option>
                        <option value="2">2 Stars - Fair</option>
                        <option value="1">1 Star - Poor</option>
                    </select>
                </div>

                <!-- Image Upload -->
                <div>
                    <label for="add_image" class="block text-sm font-semibold text-gray-700 mb-2">Profile Image</label>
                    <input type="file" name="image" id="add_image" accept="image/*"
                           class="w-full text-gray-700 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-green-50 file:text-green-700 hover:file:bg-green-100">
                    <p class="text-xs text-gray-500 mt-1">Upload a profile image for this story. Max 2MB, JPG/PNG.</p>
                </div>

                <!-- Active Status Toggle -->
                <div class="flex items-center space-x-2">
                    <input type="checkbox" name="is_active" id="add_is_active" class="h-4 w-4 text-green-600 focus:ring-green-500 border-gray-300 rounded" checked>
                    <label for="add_is_active" class="text-sm font-medium text-gray-700">Story is Active</label>
                </div>
            </form>
        </div>

        <!-- Modal Footer -->
        <div class="p-6 border-t border-gray-200 flex gap-3 justify-end">
            <button type="button" onclick="document.getElementById('addStoryModal').classList.add('hidden'); document.getElementById('addStoryModal').classList.remove('animate__fadeIn', 'animate__zoomIn'); document.getElementById('addStoryForm')?.reset();"
                    class="bg-gray-500 hover:bg-gray-600 text-white px-6 py-3 rounded-lg font-semibold transition-colors">
                Cancel
            </button>
            <button type="submit" form="addStoryForm"
                    class="bg-green-600 hover:bg-green-700 text-white px-6 py-3 rounded-lg font-semibold transition-colors">
                <i class="fas fa-plus mr-2"></i>
                Add Story
            </button>
        </div>
    </div>
</div>

<!-- ============================================== JAVASCRIPT ============================================== -->
<script>
// Global utility to close modals by adding 'hidden' class
function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.add('hidden');
        modal.classList.remove('animate__fadeIn', 'animate__zoomIn');

        // Reset forms for Add/Edit modals
        if (modalId === 'addStoryModal') {
            document.getElementById('addStoryForm')?.reset();
        }
        if (modalId === 'editStoryModal') {
            // Reset form when closing Edit modal
            const editForm = document.getElementById('editStoryForm');
            if (editForm) {
                editForm.reset();
            }
        }
    }
}

// Keyboard navigation for modals (Escape key)
document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape') {
        const modalIds = ['deleteModal', 'editStoryModal', 'addStoryModal'];
        modalIds.forEach(id => {
            const modal = document.getElementById(id);
            if (modal && !modal.classList.contains('hidden')) {
                modal.classList.add('hidden');
                modal.classList.remove('animate__fadeIn', 'animate__zoomIn');
                event.preventDefault(); // Prevent default ESC behavior
            }
        });
    }
});

document.addEventListener('DOMContentLoaded', function() {
    // Message Display on Page Load (from PHP session for SweetAlert2)
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

    // FILTERING LOGIC
    const searchInput = document.getElementById('searchInput');
    const statusFilter = document.getElementById('statusFilter');
    const filterTabs = document.querySelectorAll('.filter-tab[data-filter]');

    const applyFiltersToUrl = () => {
        let url = new URL(window.location.origin + window.location.pathname);

        const searchInputVal = searchInput?.value;
        const statusFilterVal = statusFilter?.value;

        if (searchInputVal) url.searchParams.set('search', searchInputVal);

        if (statusFilterVal && statusFilterVal !== 'all') {
            url.searchParams.set('status', statusFilterVal);
        } else {
            url.searchParams.delete('status');
        }

        url.searchParams.delete('page'); // Reset page to 1 when filters change
        window.location.href = url.toString();
    };

    let searchTimeout;
    searchInput?.addEventListener('input', () => {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(applyFiltersToUrl, 500);
    });
    statusFilter?.addEventListener('change', applyFiltersToUrl);

    filterTabs.forEach(tab => {
        tab.addEventListener('click', function() {
            const newStatus = this.dataset.filter === 'all' ? '' : this.dataset.filter;
            if (statusFilter) statusFilter.value = newStatus;
            applyFiltersToUrl();
        });
    });
});

// Function to clear all filter inputs and reload the page
function clearFilters() {
    document.getElementById('searchInput').value = '';
    document.getElementById('statusFilter').value = '';
    window.location.href = window.location.origin + window.location.pathname;
}

// Delete Confirmation Modal Logic
function confirmDeleteStory(id, name) {
    document.getElementById('deleteStoryIdInput').value = id;
    document.getElementById('deleteStoryId').textContent = id;
    document.getElementById('deleteStoryName').textContent = name;
    document.getElementById('deleteModal').classList.remove('hidden');
}

// Toggle Story Status
async function toggleStoryStatus(storyId) {
    const storyCard = document.querySelector(`.story-card[data-story-id="${storyId}"]`);
    const currentStatus = storyCard?.querySelector('.bg-green-100, .bg-gray-100')?.textContent.trim() === 'Active';

    Swal.fire({
        title: 'Confirm Status Change',
        text: `Are you sure you want to ${currentStatus ? 'deactivate' : 'activate'} this success story?`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Yes, do it!'
    }).then(async (result) => {
        if (result.isConfirmed) {
            try {
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = 'success_stories.php';

                const idInput = document.createElement('input');
                idInput.type = 'hidden';
                idInput.name = 'id';
                idInput.value = storyId;

                const actionInput = document.createElement('input');
                actionInput.type = 'hidden';
                actionInput.name = 'action';
                actionInput.value = 'toggle_status';

                form.appendChild(idInput);
                form.appendChild(actionInput);
                document.body.appendChild(form);
                form.submit();

            } catch (error) {
                console.error('Error in toggleStoryStatus:', error);
                Swal.fire('Error', `Failed to update story status: ${error.message}`, 'error');
            }
        }
    });
}

// Open Edit Story Modal
async function openEditStoryModal(storyId) {
    try {
        const response = await fetch(`success_stories.php?fetch_story_data=${storyId}`);
        if (!response.ok) {
            const errorData = await response.json();
            throw new Error(errorData.error || `HTTP error! status: ${response.status}`);
        }
        const storyData = await response.json();

        // Populate the form fields in the edit modal
        document.getElementById('edit_story_id').value = storyData.id;
        document.getElementById('edit_name').value = storyData.name;
        document.getElementById('edit_role').value = storyData.role;
        document.getElementById('edit_story').value = storyData.story;
        document.getElementById('edit_rating').value = storyData.rating;
        document.getElementById('edit_is_active').checked = storyData.is_active;

        // Image preview logic
        const currentImagePreview = document.getElementById('edit_current_image_preview');
        const imageThumb = document.getElementById('edit_image_thumb');
        if (storyData.image) {
            imageThumb.src = `../uploads/success_stories/${storyData.image}`;
            currentImagePreview.style.display = 'block';
        } else {
            currentImagePreview.style.display = 'none';
            imageThumb.src = '';
        }

        // Show the edit modal
        document.getElementById('editStoryModal').classList.remove('hidden');
    } catch (error) {
        console.error('Error opening edit modal:', error);
        Swal.fire({
            icon: 'error',
            title: 'Oops...',
            text: `Could not load story details: ${error.message}. Please check console for more details.`,
        });
    }
}

// Open Add Story Modal
function openAddStoryModal() {
    // Reset the form to clear any previous data
    document.getElementById('addStoryForm').reset();
    document.getElementById('addStoryModal').classList.remove('hidden');
}
</script>

<style>
/* Enhanced styling specific to success_stories.php */
.line-clamp-3 {
    display: -webkit-box;
    -webkit-line-clamp: 3;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

/* Filter tabs using Tailwind */
.filter-tab {
    @apply px-4 py-2 rounded-full bg-gray-100 text-gray-700 font-medium transition-colors duration-200;
}

.filter-tab:hover {
    @apply bg-primary text-white;
}

.filter-tab.active {
    @apply bg-primary text-white;
}

/* Story cards */
.story-card {
    transition: all 0.3s ease;
}

.story-card:hover {
    transform: translateY(-4px);
}

/* Loading states */
button:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}

/* Custom scrollbar */
::-webkit-scrollbar {
    width: 8px;
}

::-webkit-scrollbar-track {
    background: #f1f1f1;
}

::-webkit-scrollbar-thumb {
    background: #C1272D;
    border-radius: 4px;
}

/* Modals styles */
#deleteModal, #editStoryModal, #addStoryModal {
    background-color: rgba(0, 0, 0, 0.5);
    z-index: 9999;
}
</style>

<?php include 'includes/footer.php'; ?>
</div>