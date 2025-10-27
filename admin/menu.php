<?php
// menu.php - Admin Menu Management
// This file provides a comprehensive interface for viewing, filtering, and managing menu items.
// All CRUD operations (Add, Edit, Delete, Toggle Status/Featured) are handled via AJAX/modals.

// 1. Session Start
// IMPORTANT: session_start() must be called before any HTML output.
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// 2. Page Configuration
$page_title = 'Menu Management';
$page_description = 'Manage your restaurant\'s menu items';

// 3. Include Core Dependencies
require_once dirname(__DIR__) . '/includes/config.php'; // Contains database connection ($pdo)
require_once 'includes/functions.php'; // For getRecordById, deleteRecord (assuming these exist here)
require_once dirname(__DIR__) . '/includes/ActivityLogger.php'; // Custom activity logging class

// Initialize ActivityLogger
$activityLogger = new ActivityLogger($pdo);

// Initialize message variables to be passed to JavaScript for SweetAlert2 display
$message_type = '';
$message_text = '';

// --- 4. API Endpoint for Fetching Single Menu Item Data (CRUCIAL: Must be before any HTML output) ---
// This block handles AJAX/fetch() requests from JavaScript to get specific menu item details for editing.
// It MUST execute and terminate script execution (`exit()`) before any HTML is sent.
if (isset($_GET['fetch_item_data']) && is_numeric($_GET['fetch_item_data'])) {
    $item_id_to_fetch = (int)$_GET['fetch_item_data'];
    try {
        $stmt = $pdo->prepare("SELECT * FROM menu_items WHERE id = ?");
        $stmt->execute([$item_id_to_fetch]);
        $item_data = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($item_data) {
            // Ensure numeric values are properly cast for client-side JavaScript handling
            $item_data['price'] = (float)$item_data['price'];
            // Convert is_featured to boolean for JS
            $item_data['is_featured'] = (bool)$item_data['is_featured'];

            header('Content-Type: application/json');
            echo json_encode($item_data);
        } else {
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Menu item not found']);
        }
    } catch (PDOException $e) {
        // Log and return database error details
        error_log("DB Error fetching specific menu item (ID: {$item_id_to_fetch}): " . $e->getMessage());
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Database error', 'details' => $e->getMessage()]);
    }
    exit(); // Crucial: Stop further script execution for API responses
}
// --- END API Endpoint ---


// 5. Handle POST Requests for CRUD Operations
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // A. Delete Menu Item Operation
    if (isset($_POST['_method']) && $_POST['_method'] === 'DELETE') {
        $id = (int)$_POST['id'];
        $item_name_for_log = 'Unknown Item'; // Default for logging if item not found

        // Retrieve menu item details (for image deletion and logging)
        $item_details = getRecordById('menu_items', $id, 'image,name,additional_images'); // Assume getRecordById fetches specific columns
        if ($item_details) {
            $item_name_for_log = $item_details['name'];
            // Delete associated image file if it exists
            if (!empty($item_details['image'])) {
                $imagePath = '../uploads/menu/' . $item_details['image'];
                if (file_exists($imagePath) && is_writable($imagePath)) { // Added is_writable check
                    unlink($imagePath);
                } else {
                    error_log("Failed to delete image: {$imagePath} (file not found or not writable)");
                }
            }
            // Delete additional images
            $additional_images = json_decode($item_details['additional_images'] ?? '[]', true) ?: [];
            foreach ($additional_images as $additional_image) {
                $additionalImagePath = '../uploads/menu/' . $additional_image;
                if (file_exists($additionalImagePath) && is_writable($additionalImagePath)) {
                    unlink($additionalImagePath);
                } else {
                    error_log("Failed to delete additional image: {$additionalImagePath}");
                }
            }
        }

        // Perform database deletion
        if (deleteRecord('menu_items', $id)) { // Assume deleteRecord function exists in includes/functions.php
            $_SESSION['message'] = ['type' => 'success', 'text' => "'{$item_name_for_log}' (ID: {$id}) deleted successfully."];
            $activityLogger->logActivity("Menu item '{$item_name_for_log}' (ID: {$id}) deleted.", $_SESSION['user_id'] ?? null, 'menu_delete');
        } else {
            $_SESSION['message'] = ['type' => 'error', 'text' => "Failed to delete menu item '{$item_name_for_log}' (ID: {$id})."];
            error_log("Failed to delete menu item with ID: {$id}");
        }
    }

    // B. Toggle Status or Featured Status Operation
    elseif (isset($_POST['action']) && ($_POST['action'] === 'toggle_status' || $_POST['action'] === 'toggle_featured')) {
        $id = (int)$_POST['id'];
        $column_to_update = ($_POST['action'] === 'toggle_status') ? 'status' : 'is_featured';
        $item_name_for_log = 'Unknown Item';

        // Fetch current value and name for accurate toggling and logging
        $current_item_state_stmt = $pdo->prepare("SELECT {$column_to_update}, name FROM menu_items WHERE id = ?");
        $current_item_state_stmt->execute([$id]);
        $current_item_data = $current_item_state_stmt->fetch(PDO::FETCH_ASSOC);

        if ($current_item_data) {
            $item_name_for_log = $current_item_data['name'];
            $new_column_value = '';
            $log_entry = '';

            if ($column_to_update === 'status') {
                $new_column_value = ($current_item_data[$column_to_update] === 'active') ? 'inactive' : 'active';
                $log_entry = "Menu item '{$item_name_for_log}' (ID: {$id}) status changed to '{$new_column_value}'.";
            } else { // 'is_featured'
                $new_column_value = ($current_item_data[$column_to_update] == 1) ? 0 : 1; // Toggle 1/0
                $log_entry = "Menu item '{$item_name_for_log}' (ID: {$id}) featured status changed to " . ($new_column_value ? 'true' : 'false') . ".";
            }

            $update_stmt = $pdo->prepare("UPDATE menu_items SET {$column_to_update} = :new_value, updated_at = NOW() WHERE id = :id");
            if ($update_stmt->execute([':new_value' => $new_column_value, ':id' => $id])) {
                $_SESSION['message'] = ['type' => 'success', 'text' => "Menu item '{$item_name_for_log}' updated successfully."];
                $activityLogger->logActivity($log_entry, $_SESSION['user_id'] ?? null, 'menu_update');
            } else {
                $_SESSION['message'] = ['type' => 'error', 'text' => "Failed to update {$column_to_update} for '{$item_name_for_log}' (ID: {$id})."];
                error_log("Failed to update menu item {$column_to_update} for ID: {$id}");
            }
        } else {
            $_SESSION['message'] = ['type' => 'error', 'text' => "Menu item with ID {$id} not found."];
        }
    }

    // C. Add New Menu Item Operation
    elseif (isset($_POST['add_menu_item'])) {
        $name = trim($_POST['name'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $ingredients = trim($_POST['ingredients'] ?? '');
        $allergens = trim($_POST['allergens'] ?? '');
        $nutrition_info = trim($_POST['nutrition_info'] ?? '');
        $price = (float)($_POST['price'] ?? 0);
        $category_id = (int)($_POST['category_id'] ?? 0);
        $status = trim($_POST['status'] ?? 'inactive');
        $is_featured = isset($_POST['is_featured']) ? 1 : 0;
        $image_name = null;
        $additional_images = [];

        $errors = [];
        if (empty($name)) $errors[] = "Item name is required.";
        if ($price <= 0) $errors[] = "Price must be greater than zero.";
        if ($category_id <= 0) $errors[] = "Category is required.";

        if (empty($errors)) {
            // Handle main image upload
            if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                $uploadDir = '../uploads/menu/';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0777, true);
                }
                $fileExtension = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
                $image_name = uniqid('menu_') . '.' . $fileExtension;
                $targetPath = $uploadDir . $image_name;

                if (!move_uploaded_file($_FILES['image']['tmp_name'], $targetPath)) {
                    $errors[] = "Failed to upload main image.";
                    $image_name = null; // Reset image name if upload fails
                }
            } else if ($_FILES['image']['error'] !== UPLOAD_ERR_NO_FILE) {
                $errors[] = "Error uploading main image: " . $_FILES['image']['error'];
            }

            // Handle additional images upload
            if (isset($_FILES['additional_images'])) {
                foreach ($_FILES['additional_images']['tmp_name'] as $key => $tmp_name) {
                    if ($_FILES['additional_images']['error'][$key] === UPLOAD_ERR_OK) {
                        $fileExtension = pathinfo($_FILES['additional_images']['name'][$key], PATHINFO_EXTENSION);
                        $additional_image_name = uniqid('menu_add_') . '.' . $fileExtension;
                        $targetPath = $uploadDir . $additional_image_name;

                        if (move_uploaded_file($tmp_name, $targetPath)) {
                            $additional_images[] = $additional_image_name;
                        } else {
                            $errors[] = "Failed to upload additional image: " . $_FILES['additional_images']['name'][$key];
                        }
                    } else if ($_FILES['additional_images']['error'][$key] !== UPLOAD_ERR_NO_FILE) {
                        $errors[] = "Error uploading additional image " . $_FILES['additional_images']['name'][$key] . ": " . $_FILES['additional_images']['error'][$key];
                    }
                }
            }
        }

        if (empty($errors)) {
            try {
                $stmt = $pdo->prepare("INSERT INTO menu_items (name, description, ingredients, allergens, nutrition_info, price, category_id, status, is_featured, image, additional_images, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())");
                $stmt->execute([$name, $description, $ingredients, $allergens, $nutrition_info, $price, $category_id, $status, $is_featured, $image_name, json_encode($additional_images)]);
                $new_item_id = $pdo->lastInsertId();
                $_SESSION['message'] = ['type' => 'success', 'text' => "New menu item '{$name}' added successfully."];
                $activityLogger->logActivity("New menu item '{$name}' (ID: {$new_item_id}) added.", $_SESSION['user_id'] ?? null, 'menu_add');
            } catch (PDOException $e) {
                $errors[] = "Database error: " . $e->getMessage();
                error_log("Error adding menu item: " . $e->getMessage());
            }
        }

        if (!empty($errors)) {
            $_SESSION['message'] = ['type' => 'error', 'text' => "Failed to add menu item: " . implode(" ", $errors)];
        }
    }

    // D. Edit Menu Item Operation (using menu_edit_submit as trigger)
    elseif (isset($_POST['edit_menu_item'])) {
        $id = (int)$_POST['id'];
        $name = trim($_POST['name'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $ingredients = trim($_POST['ingredients'] ?? '');
        $allergens = trim($_POST['allergens'] ?? '');
        $nutrition_info = trim($_POST['nutrition_info'] ?? '');
        $price = (float)($_POST['price'] ?? 0);
        $category_id = (int)($_POST['category_id'] ?? 0);
        $status = trim($_POST['status'] ?? 'inactive');
        $is_featured = isset($_POST['is_featured']) ? 1 : 0;
        $image_name = null; // Will be set if a new image is uploaded
        $additional_images = [];

        $errors = [];
        if ($id <= 0) $errors[] = "Invalid item ID for update.";
        if (empty($name)) $errors[] = "Item name is required.";
        if ($price <= 0) $errors[] = "Price must be greater than zero.";
        if ($category_id <= 0) $errors[] = "Category is required.";

        if (empty($errors)) {
            // Fetch current additional images to retain if no new ones are uploaded
            $current_item = getRecordById('menu_items', $id, 'image,name,additional_images');
            $original_additional_images = json_decode($current_item['additional_images'] ?? '[]', true) ?: [];
            $item_name_for_log = $current_item['name'] ?? 'Unknown Item';

            // Handle removed additional images
            $remaining_additional_images = json_decode($_POST['remaining_additional_images'] ?? '[]', true) ?: [];
            $removed_images = array_diff($original_additional_images, $remaining_additional_images);
            foreach ($removed_images as $removed_image) {
                $removedImagePath = '../uploads/menu/' . $removed_image;
                if (file_exists($removedImagePath) && is_writable($removedImagePath)) {
                    unlink($removedImagePath);
                }
            }

            // Use the remaining images as the base
            $all_additional_images = $remaining_additional_images;

            // Handle new main image upload
            if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                $uploadDir = '../uploads/menu/';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0777, true);
                }
                $fileExtension = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
                $image_name = uniqid('menu_') . '.' . $fileExtension;
                $targetPath = $uploadDir . $image_name;

                if (move_uploaded_file($_FILES['image']['tmp_name'], $targetPath)) {
                    // Delete old image if a new one is uploaded
                    if (!empty($original_image) && file_exists($uploadDir . $original_image) && is_writable($uploadDir . $original_image)) {
                        unlink($uploadDir . $original_image);
                    }
                } else {
                    $errors[] = "Failed to upload new main image.";
                    $image_name = $original_image; // Revert to original if new upload fails
                }
            } else if ($_FILES['image']['error'] !== UPLOAD_ERR_NO_FILE) {
                $errors[] = "Error uploading main image: " . $_FILES['image']['error'];
                $image_name = $original_image; // Revert to original on error
            } else {
                // No new main image uploaded, retain the original
                $image_name = $original_image;
            }

            // Handle new additional images upload
            if (isset($_FILES['additional_images'])) {
                foreach ($_FILES['additional_images']['tmp_name'] as $key => $tmp_name) {
                    if ($_FILES['additional_images']['error'][$key] === UPLOAD_ERR_OK) {
                        $fileExtension = pathinfo($_FILES['additional_images']['name'][$key], PATHINFO_EXTENSION);
                        $additional_image_name = uniqid('menu_add_') . '.' . $fileExtension;
                        $targetPath = $uploadDir . $additional_image_name;

                        if (move_uploaded_file($tmp_name, $targetPath)) {
                            $all_additional_images[] = $additional_image_name;
                        } else {
                            $errors[] = "Failed to upload additional image: " . $_FILES['additional_images']['name'][$key];
                        }
                    } else if ($_FILES['additional_images']['error'][$key] !== UPLOAD_ERR_NO_FILE) {
                        $errors[] = "Error uploading additional image " . $_FILES['additional_images']['name'][$key] . ": " . $_FILES['additional_images']['error'][$key];
                    }
                }
            }

            if (empty($errors)) {
                // Build the UPDATE query dynamically to only update changed fields
                $updateFields = [];
                $updateValues = [];

                // Always update these fields
                $updateFields[] = "name = ?";
                $updateValues[] = $name;
                $updateFields[] = "description = ?";
                $updateValues[] = $description;
                $updateFields[] = "ingredients = ?";
                $updateValues[] = $ingredients;
                $updateFields[] = "allergens = ?";
                $updateValues[] = $allergens;
                $updateFields[] = "nutrition_info = ?";
                $updateValues[] = $nutrition_info;
                $updateFields[] = "price = ?";
                $updateValues[] = $price;
                $updateFields[] = "category_id = ?";
                $updateValues[] = $category_id;
                $updateFields[] = "status = ?";
                $updateValues[] = $status;
                $updateFields[] = "is_featured = ?";
                $updateValues[] = $is_featured;
                $updateFields[] = "additional_images = ?";
                $updateValues[] = json_encode($all_additional_images);
                $updateFields[] = "updated_at = NOW()";

                // Only update the image if a new one is uploaded
                if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                    $updateFields[] = "image = ?";
                    $updateValues[] = $image_name;
                }

                $updateFieldsStr = implode(", ", $updateFields);
                $stmt = $pdo->prepare("UPDATE menu_items SET $updateFieldsStr WHERE id = ?");
                $updateValues[] = $id; // Add the ID at the end
                $stmt->execute($updateValues);

                $_SESSION['message'] = ['type' => 'success', 'text' => "Menu item '{$name}' (ID: {$id}) updated successfully."];
                $activityLogger->logActivity("Menu item '{$item_name_for_log}' (ID: {$id}) updated.", $_SESSION['user_id'] ?? null, 'menu_update');
            }
        }

        if (!empty($errors)) {
            $_SESSION['message'] = ['type' => 'error', 'text' => "Failed to update menu item: " . implode(" ", $errors)];
        }
    }


    header('Location: menu.php'); // Redirect to refresh and display message
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
$category_filter = $_GET['category'] ?? ''; // Renamed to avoid clash with category data
$status_filter   = $_GET['status'] ?? '';
$search_term     = $_GET['search'] ?? '';

// Prepare dynamic WHERE clauses and parameters
$params = [];
$where_conditions = [];
$base_query_joins = "FROM menu_items mi LEFT JOIN categories c ON mi.category_id = c.id";

if (!empty($search_term)) {
    $where_conditions[] = "(mi.name LIKE :search_term OR mi.description LIKE :search_term OR c.name LIKE :search_cat)";
    $params[':search_term'] = '%' . $search_term . '%';
    $params[':search_cat'] = '%' . $search_term . '%'; // Allow searching by category name
}

if (!empty($category_filter)) {
    $where_conditions[] = "mi.category_id = :category_filter_id";
    $params[':category_filter_id'] = $category_filter;
}

if (!empty($status_filter)) {
    if ($status_filter === 'featured') { // Special case for 'featured' filter
        $where_conditions[] = "mi.is_featured = 1 AND mi.status = 'active'";
    } else {
        $where_conditions[] = "mi.status = :status_filter_val"; // Use distinct param name
        $params[':status_filter_val'] = $status_filter;
    }
}

$final_where_clause = '';
if (!empty($where_conditions)) {
    $final_where_clause = " WHERE " . implode(" AND ", $where_conditions);
}


// 8. Total Record Count for Pagination (with current filters)
$total_records_query = "SELECT COUNT(mi.id) AS total $base_query_joins $final_where_clause";
$count_stmt = $pdo->prepare($total_records_query);
foreach ($params as $key => $value) {
    $count_stmt->bindValue($key, $value, is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR);
}
$count_stmt->execute();
$total_items = $count_stmt->fetchColumn();


// 9. Pagination Setup
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1; // Ensure page is at least 1
$per_page = 8; // Number of menu items per page
$offset = ($page - 1) * $per_page;
$total_pages = ceil($total_items / $per_page);


// 10. Main Menu Items Data Retrieval for Current Page
$query_menu_items = "
    SELECT mi.*, c.name AS category_name
    $base_query_joins
    $final_where_clause
    ORDER BY mi.created_at DESC
    LIMIT :limit OFFSET :offset";

$stmt_menu_items = $pdo->prepare($query_menu_items);
foreach ($params as $key => $value) {
    $stmt_menu_items->bindValue($key, $value, is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR);
}
$stmt_menu_items->bindValue(':limit', (int)$per_page, PDO::PARAM_INT);
$stmt_menu_items->bindValue(':offset', (int)$offset, PDO::PARAM_INT);

try {
    $stmt_menu_items->execute();
    $menu_items = $stmt_menu_items->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Database error fetching menu items for display: " . $e->getMessage());
    $menu_items = []; // Fallback empty array on error
    $error_message .= "An error occurred while fetching menu items. Please try again later.";
}


// 11. Get Categories for Filter Dropdown and Modals
// This is fetched independently to ensure all categories are available for selection, regardless of filters.
$categories = [];
try {
    $cat_stmt = $pdo->query("SELECT id, name FROM categories WHERE is_active = 1 ORDER BY name");
    $categories = $cat_stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Database error fetching categories: " . $e->getMessage());
    // $categories remains empty, UI will show no categories in dropdowns.
}

// 12. Get Menu Item Statistics (for dashboard cards and filter tabs)
// Counts are based on the current filters applied to the main item list.
function getMenuItemCounts($pdo, $category_filter_id, $search_term) {
    $counts = [
        'total' => 0,
        'active' => 0,
        'featured' => 0,
        'inactive' => 0,
    ];

    $base_sql_count = "SELECT COUNT(mi.id) FROM menu_items mi LEFT JOIN categories c ON mi.category_id = c.id";
    $count_conditions = []; // Conditions specific to counting, based on current filters
    $count_params = [];

    if (!empty($category_filter_id)) {
        $count_conditions[] = "mi.category_id = :category_filter_id";
        $count_params[':category_filter_id'] = $category_filter_id;
    }
    if (!empty($search_term)) {
        $count_conditions[] = "(mi.name LIKE :search_term OR mi.description LIKE :search_term OR c.name LIKE :search_cat)";
        $count_params[':search_term'] = '%' . $search_term . '%';
        $count_params[':search_cat'] = '%' . $search_term . '%';
    }

    $count_where_clause = '';
    if (!empty($count_conditions)) {
        $count_where_clause = " WHERE " . implode(" AND ", $count_conditions);
    }

    // --- Calculate All Counts based on current filters ---
    // Total Items
    $stmt_total = $pdo->prepare($base_sql_count . $count_where_clause);
    $stmt_total->execute($count_params);
    $counts['total'] = $stmt_total->fetchColumn();

    // Active Items
    $active_conditions = $count_conditions;
    $active_conditions[] = "mi.status = 'active'";
    $stmt_active = $pdo->prepare($base_sql_count . " WHERE " . implode(" AND ", $active_conditions));
    $stmt_active->execute($count_params);
    $counts['active'] = $stmt_active->fetchColumn();

    // Featured Items (must also be active)
    $featured_conditions = $count_conditions;
    $featured_conditions[] = "mi.is_featured = 1";
    $featured_conditions[] = "mi.status = 'active'"; // Featured implies active
    $stmt_featured = $pdo->prepare($base_sql_count . " WHERE " . implode(" AND ", $featured_conditions));
    $stmt_featured->execute($count_params);
    $counts['featured'] = $stmt_featured->fetchColumn();

    // Inactive Items
    $inactive_conditions = $count_conditions;
    $inactive_conditions[] = "mi.status = 'inactive'";
    $stmt_inactive = $pdo->prepare($base_sql_count . " WHERE " . implode(" AND ", $inactive_conditions));
    $stmt_inactive->execute($count_params);
    $counts['inactive'] = $stmt_inactive->fetchColumn();

    return $counts;
}

$item_counts = getMenuItemCounts($pdo, $category_filter, $search_term);


// 13. Include Header (starts HTML output)
include 'includes/header.php';
?>

<!-- ============================================== HTML BEGINS HERER ============================================== -->

<!-- Dashboard Header -->
<div class="bg-gradient-to-br from-primary via-primary-dark to-secondary text-white">
    <div class="container mx-auto px-6 py-8">
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between">
            <div>
                <h1 class="text-3xl md:text-4xl font-bold mb-2"><?= htmlspecialchars($page_title) ?></h1>
                <p class="text-lg opacity-90"><?= htmlspecialchars($page_description) ?></p>
            </div>
            <div class="mt-4 lg:mt-0">
                <button type="button" onclick="openAddItemModal()"
                   class="bg-accent hover:bg-accent-dark text-white px-6 py-3 rounded-lg font-semibold transition-colors duration-200 flex items-center shadow-lg hover:shadow-xl transform hover:-translate-y-1">
                    <i class="fas fa-plus mr-2"></i>
                    Add New Menu Item
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Statistics Cards -->
<div class="bg-gray-50 py-8">
    <div class="container mx-auto px-6">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
            <div class="bg-white rounded-xl shadow-lg p-6 border-l-4 border-primary">
                <div class="flex items-center">
                    <div class="p-3 bg-primary/10 rounded-lg">
                        <i class="fas fa-utensils text-2xl text-primary"></i>
                    </div>
                    <div class="ml-4">
                        <h3 class="text-2xl font-bold text-gray-900"><?php echo $item_counts['total'] ?? 0; ?></h3>
                        <p class="text-gray-600">Total Items (Filtered)</p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-lg p-6 border-l-4 border-green-500">
                <div class="flex items-center">
                    <div class="p-3 bg-green-100 rounded-lg">
                        <i class="fas fa-check-circle text-2xl text-green-600"></i>
                    </div>
                    <div class="ml-4">
                        <h3 class="text-2xl font-bold text-gray-900"><?php echo $item_counts['active'] ?? 0; ?></h3>
                        <p class="text-gray-600">Active Items (Filtered)</p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-lg p-6 border-l-4 border-yellow-500">
                <div class="flex items-center">
                    <div class="p-3 bg-yellow-100 rounded-lg">
                        <i class="fas fa-star text-2xl text-yellow-600"></i>
                    </div>
                    <div class="ml-4">
                        <h3 class="text-2xl font-bold text-gray-900"><?php echo $item_counts['featured'] ?? 0; ?></h3>
                        <p class="text-gray-600">Featured Items (Filtered)</p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-lg p-6 border-l-4 border-gray-500">
                <div class="flex items-center">
                    <div class="p-3 bg-gray-100 rounded-lg">
                        <i class="fas fa-eye-slash text-2xl text-gray-600"></i>
                    </div>
                    <div class="ml-4">
                        <h3 class="text-2xl font-bold text-gray-900"><?php echo $item_counts['inactive'] ?? 0; ?></h3>
                        <p class="text-gray-600">Inactive Items (Filtered)</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Main Content -->
<div class="container mx-auto px-6 py-8">
    <!-- Enhanced Filter & Search Section -->
    <div class="bg-white rounded-xl shadow-lg p-6 mb-8">
        <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
            <!-- Search Input -->
            <div class="lg:col-span-2">
                <label class="block text-sm font-semibold text-gray-700 mb-2" for="searchInput">Search Menu Items</label>
                <div class="relative">
                    <input type="text" id="searchInput" value="<?php echo htmlspecialchars($search_term); ?>"
                           placeholder="Search by name, description, or category..."
                           class="w-full pl-12 pr-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent transition-colors">
                    <i class="fas fa-search absolute left-4 top-1/2 transform -translate-y-1/2 text-gray-400"></i>
                </div>
            </div>

            <!-- Category Filter -->
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2" for="categoryFilter">Category</label>
                <select id="categoryFilter" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent transition-colors">
                    <option value="">All Categories</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?php echo $cat['id']; ?>" <?php echo $category_filter == $cat['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($cat['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Status Filter -->
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2" for="statusFilter">Status</label>
                <select id="statusFilter" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent transition-colors">
                    <option value="">All Status</option>
                    <option value="active" <?php echo $status_filter === 'active' ? 'selected' : ''; ?>>Active</option>
                    <option value="inactive" <?php echo $status_filter === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                    <option value="featured" <?php echo $status_filter === 'featured' ? 'selected' : ''; ?>>Featured</option>
                </select>
            </div>
        </div>

        <!-- Quick Filter Tabs -->
        <div class="mt-6 pt-6 border-t border-gray-200">
            <div class="flex flex-wrap gap-2">
                <button type="button" class="filter-tab <?php echo empty($status_filter) ? 'active' : ''; ?>" data-filter="all">
                    All Items <span class="ml-1 bg-gray-100 text-gray-600 px-2 py-1 rounded-full text-xs"><?php echo $item_counts['total'] ?? 0; ?></span>
                </button>
                <button type="button" class="filter-tab <?php echo $status_filter === 'active' ? 'active' : ''; ?>" data-filter="active">
                    Active <span class="ml-1 bg-green-100 text-green-600 px-2 py-1 rounded-full text-xs"><?php echo $item_counts['active'] ?? 0; ?></span>
                </button>
                <button type="button" class="filter-tab <?php echo $status_filter === 'featured' ? 'active' : ''; ?>" data-filter="featured">
                    Featured <span class="ml-1 bg-yellow-100 text-yellow-600 px-2 py-1 rounded-full text-xs"><?php echo $item_counts['featured'] ?? 0; ?></span>
                </button>
                <button type="button" class="filter-tab <?php echo $status_filter === 'inactive' ? 'active' : ''; ?>" data-filter="inactive">
                    Inactive <span class="ml-1 bg-gray-100 text-gray-600 px-2 py-1 rounded-full text-xs"><?php echo $item_counts['inactive'] ?? 0; ?></span>
                </button>
                <?php if ($category_filter || $status_filter || $search_term): ?>
                    <button type="button" onclick="clearFilters()" class="ml-4 text-primary hover:text-primary-dark font-medium">
                        <i class="fas fa-times mr-1"></i>Clear Filters
                    </button>
                <?php endif; ?>
            </div>
        </div>
    </div>


    <!-- Menu Items Grid -->
    <?php if (count($menu_items) > 0): ?>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6" id="menuItemsGrid">
            <?php foreach ($menu_items as $item):
                $isFeatured = $item['is_featured'] ?? false;
                $item_status = $item['status'] ?? 'active'; // Renamed from $status
                $isActive = $item_status === 'active';
            ?>
                <div class="menu-item-card bg-white rounded-xl shadow-lg hover:shadow-xl transition-all duration-300 overflow-hidden border border-gray-100 group"
                     data-item-id="<?php echo $item['id']; ?>"
                     data-category="<?php echo $item['category_id']; ?>"
                     data-status="<?php echo $item_status; ?>"
                     data-featured="<?php echo $isFeatured ? '1' : '0'; ?>"
                     data-name="<?php echo strtolower($item['name']); ?>">

                    <!-- Image Section -->
                    <div class="relative h-48 overflow-hidden">
                        <?php
                        $displayImage = $item['image'];
                        if (empty($displayImage)) {
                            $additionalImages = json_decode($item['additional_images'] ?? '[]', true) ?: [];
                            $displayImage = !empty($additionalImages) ? $additionalImages[0] : null;
                        }
                        ?>
                        <?php if (!empty($displayImage)): ?>
                            <img src="../uploads/menu/<?php echo htmlspecialchars($displayImage); ?>"
                                 alt="<?php echo htmlspecialchars($item['name']); ?>"
                                 class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500">
                        <?php else: ?>
                            <div class="w-full h-full bg-gradient-to-br from-gray-100 to-gray-200 flex items-center justify-center">
                                <i class="fas fa-utensils text-5xl text-gray-300"></i>
                            </div>
                        <?php endif; ?>

                        <!-- Status Badges -->
                        <div class="absolute top-3 left-3 flex flex-col gap-2">
                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-semibold <?php echo $isActive ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'; ?>">
                                <span class="w-2 h-2 rounded-full mr-1.5 <?php echo $isActive ? 'bg-green-500' : 'bg-gray-500'; ?>"></span>
                                <?php echo ucfirst($item_status); // Used item_status ?>
                            </span>
                            <?php if ($isFeatured): ?>
                                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-semibold bg-yellow-100 text-yellow-800">
                                    <i class="fas fa-star text-yellow-500 mr-1.5"></i>
                                    Featured
                                </span>
                            <?php endif; ?>
                        </div>

                        <!-- Price Badge -->
                        <div class="absolute top-3 right-3 bg-black bg-opacity-70 text-white px-2 py-1 rounded">
                            <span class="font-bold">KES <?php echo number_format($item['price'], 0); ?></span>
                        </div>
                    </div>

                    <!-- Card Content -->
                    <div class="p-5">
                        <div class="mb-3">
                            <h3 class="text-lg font-bold text-gray-900 mb-2 group-hover:text-primary transition-colors duration-200">
                                <?php echo htmlspecialchars($item['name']); ?>
                            </h3>
                            <?php if (isset($item['category_name']) && !empty($item['category_name'])): ?>
                                <span class="inline-block bg-primary/10 text-primary text-xs font-medium px-2 py-1 rounded-full mb-2">
                                    <?php echo htmlspecialchars($item['category_name']); ?>
                                </span>
                            <?php endif; ?>
                        </div>

                        <?php if (!empty($item['description'])): ?>
                            <p class="text-gray-600 text-sm mb-4 line-clamp-2">
                                <?php echo htmlspecialchars($item['description']); ?>
                            </p>
                        <?php endif; ?>

                        <!-- Item Meta -->
                        <div class="flex items-center justify-between text-xs text-gray-500 mb-4">
                            <span>ID: #<?php echo $item['id']; ?></span>
                            <span>Added: <?php echo date('M j, Y', strtotime($item['created_at'])); ?></span>
                        </div>

                        <!-- Action Buttons -->
                        <div class="action-buttons flex flex-col space-y-3">
                            <!-- Top Row - Primary Actions -->
                            <div class="primary-actions-top grid grid-cols-2 gap-2">
                                <button type="button" onclick="openEditItemModal(<?php echo $item['id']; ?>)"
                                   class="bg-primary hover:bg-primary-dark text-white px-3 py-2.5 rounded-lg font-medium transition-all duration-200 text-center shadow-sm hover:shadow-md transform hover:-translate-y-0.5">
                                    <i class="fas fa-edit mr-2"></i>
                                    Edit
                                </button>
                                <button type="button" onclick="toggleItemProperty(<?php echo $item['id']; ?>, 'toggle_status')"
                                        class="px-3 py-2.5 rounded-lg font-medium transition-all duration-200 shadow-sm hover:shadow-md transform hover:-translate-y-0.5 <?php echo $isActive ? 'bg-yellow-100 text-yellow-700 hover:bg-yellow-200 border border-yellow-200' : 'bg-green-100 text-green-700 hover:bg-green-200 border border-green-200'; ?>">
                                    <i class="fas fa-<?php echo $isActive ? 'eye-slash' : 'eye'; ?> mr-2"></i>
                                    <?php echo $isActive ? 'Deactivate' : 'Activate'; ?>
                                </button>
                            </div>

                            <!-- Bottom Row - Secondary Actions -->
                            <div class="primary-actions-bottom grid grid-cols-2 gap-2">
                                <button type="button" onclick="confirmDeleteItem(<?php echo $item['id']; ?>, '<?php echo htmlspecialchars($item['name']); ?>')"
                                        class="px-3 py-2.5 rounded-lg font-medium transition-all duration-200 bg-red-100 text-red-700 hover:bg-red-200 border border-red-200 shadow-sm hover:shadow-md transform hover:-translate-y-0.5">
                                    <i class="fas fa-trash mr-2"></i>
                                    Delete
                                </button>
                                <button type="button" onclick="toggleItemProperty(<?php echo $item['id']; ?>, 'toggle_featured')"
                                        class="inline-flex items-center justify-center px-3 py-2.5 rounded-lg font-medium transition-all duration-200 text-sm shadow-sm hover:shadow-md transform hover:-translate-y-0.5 <?php echo $isFeatured ? 'bg-yellow-100 text-yellow-700 hover:bg-yellow-200 border border-yellow-200' : 'bg-gray-100 text-gray-700 hover:bg-gray-200 border border-gray-200'; ?>">
                                    <i class="fas fa-star mr-2"></i>
                                    <?php echo $isFeatured ? 'Featured' : 'Feature'; ?>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
            <div class="mt-8">
                <div class="bg-white px-4 py-3 flex items-center justify-between border-t border-gray-200 sm:px-6 rounded-lg shadow-sm">
                    <div class="flex-1 flex justify-between sm:hidden">
                        <?php
                            // Helper function to build pagination URL
                            function getMenuItemPaginationUrl($page_num, $cat_filter, $stat_filter, $srch_term) {
                                $url_parts = [];
                                if ($page_num > 1) $url_parts['page'] = $page_num;
                                if (!empty($cat_filter)) $url_parts['category'] = urlencode($cat_filter);
                                if (!empty($stat_filter)) $url_parts['status'] = urlencode($stat_filter);
                                if (!empty($srch_term)) $url_parts['search'] = urlencode($srch_term);
                                return '?' . http_build_query($url_parts);
                            }
                        ?>
                        <?php if ($page > 1): ?>
                            <a href="<?php echo getMenuItemPaginationUrl($page - 1, $category_filter, $status_filter, $search_term); ?>"
                               class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                                Previous
                            </a>
                        <?php endif; ?>
                        <?php if ($page < $total_pages): ?>
                            <a href="<?php echo getMenuItemPaginationUrl($page + 1, $category_filter, $status_filter, $search_term); ?>"
                               class="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                                Next
                            </a>
                        <?php endif; ?>
                    </div>
                    <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
                        <div>
                            <p class="text-sm text-gray-700">
                                Showing <span class="font-medium"><?php echo ($offset + 1); ?></span> to
                                <span class="font-medium"><?php echo min($offset + $per_page, $total_items); ?></span> of
                                <span class="font-medium"><?php echo $total_items; ?></span> results
                            </p>
                        </div>
                        <div>
                            <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px" aria-label="Pagination">
                                <?php if ($page > 1): ?>
                                    <a href="<?php echo getMenuItemPaginationUrl($page - 1, $category_filter, $status_filter, $search_term); ?>"
                                       class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
                                        <span class="sr-only">Previous</span>
                                        <i class="fas fa-chevron-left h-5 w-5"></i>
                                    </a>
                                <?php endif; ?>

                                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                    <?php if ($i == 1 || $i == $total_pages || ($i >= $page - 2 && $i <= $page + 2)): ?>
                                        <a href="<?php echo getMenuItemPaginationUrl($i, $category_filter, $status_filter, $search_term); ?>"
                                           class="relative inline-flex items-center px-4 py-2 border text-sm font-medium <?php echo $i == $page ? 'z-10 bg-primary border-primary text-white' : 'bg-white border-gray-300 text-gray-700 hover:bg-gray-50'; ?>">
                                            <?php echo $i; ?>
                                        </a>
                                    <?php elseif ($i == $page - 3 || $i == $page + 3): ?>
                                        <span class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700">
                                            ...
                                        </span>
                                    <?php endif; ?>
                                <?php endfor; ?>

                                <?php if ($page < $total_pages): ?>
                                    <a href="<?php echo getMenuItemPaginationUrl($page + 1, $category_filter, $status_filter, $search_term); ?>"
                                       class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
                                        <span class="sr-only">Next</span>
                                        <i class="fas fa-chevron-right h-5 w-5"></i>
                                    </a>
                                <?php endif; ?>
                            </nav>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>

    <?php else: ?>
        <!-- Empty State -->
        <div class="text-center py-16">
            <div class="max-w-md mx-auto">
                <i class="fas fa-utensils text-6xl text-gray-300 mb-4"></i>
                <h3 class="text-2xl font-semibold text-gray-700 mb-2">
                    <?php if ($search_term || $category_filter || $status_filter): ?>
                        No items match your filters
                    <?php else: ?>
                        No menu items found
                    <?php endif; ?>
                </h3>
                <p class="text-gray-500 mb-6">
                    <?php if ($search_term || $category_filter || $status_filter): ?>
                        Try adjusting your search or filter criteria.
                    <?php else: ?>
                        Get started by creating your first menu item.
                    <?php endif; ?>
                </p>
                <div class="flex flex-col sm:flex-row gap-3 justify-center">
                    <button type="button" onclick="openAddItemModal()"
                       class="bg-primary hover:bg-primary-dark text-white px-6 py-3 rounded-lg font-semibold transition-colors duration-200 flex items-center justify-center">
                        <i class="fas fa-plus mr-2"></i>
                        Add Menu Item
                    </button>
                    <?php if ($search_term || $category_filter || $status_filter): ?>
                        <button type="button" onclick="clearFilters()"
                                class="bg-gray-100 hover:bg-gray-200 text-gray-700 px-6 py-3 rounded-lg font-semibold transition-colors duration-200">
                            Clear Filters
                        </button>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<!-- ============================================== MODALS ============================================== -->

<!-- Delete Confirmation Modal -->
<div id="deleteModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
    <div class="bg-white rounded-2xl shadow-2xl max-w-md w-full max-h-[90vh] overflow-y-auto">
        <!-- Modal Header -->
        <div class="bg-gradient-to-r from-primary via-primary-dark to-secondary p-6 text-white rounded-t-2xl">
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
        <div class="p-6 text-gray-700">
            <p class="text-lg font-medium mb-3">Are you sure you want to delete menu item "<span id="deleteItemName" class="font-bold text-red-600"></span>" (ID: <span id="deleteItemIdDisplay" class="font-bold text-red-600"></span>)?</p>
            <p>This action cannot be undone. It will be permanently removed.</p>
        </div>

        <!-- Modal Footer -->
        <div class="p-6 border-t border-gray-200 flex gap-3 justify-end">
            <button type="button" onclick="document.getElementById('deleteModal').classList.add('hidden'); document.getElementById('deleteModal').classList.remove('animate__fadeIn', 'animate__zoomIn');"
                    class="group relative bg-gray-500 hover:bg-gray-600 text-white px-6 py-3 rounded-xl font-semibold transition-all duration-300 flex items-center justify-center shadow-lg hover:shadow-xl transform hover:-translate-y-0.5">
                <div class="absolute inset-0 bg-gradient-to-r from-white/0 via-white/10 to-white/0 opacity-0 group-hover:opacity-100 transition-opacity duration-300 rounded-xl"></div>
                <span class="relative z-10 font-medium">Cancel</span>
            </button>
            <form action="" method="POST" class="inline-block" id="deleteItemForm">
                <input type="hidden" name="_method" value="DELETE">
                <input type="hidden" name="id" id="deleteItemId">
                <button type="submit"
                        class="group relative bg-red-600 hover:bg-red-700 text-white px-6 py-3 rounded-xl font-semibold transition-all duration-300 flex items-center justify-center shadow-lg hover:shadow-xl transform hover:-translate-y-0.5">
                    <div class="absolute inset-0 bg-gradient-to-r from-white/0 via-white/10 to-white/0 opacity-0 group-hover:opacity-100 transition-opacity duration-300 rounded-xl"></div>
                    <i class="fas fa-trash mr-2 relative z-10"></i>
                    <span class="relative z-10 font-medium">Delete Item</span>
                </button>
            </form>
        </div>
    </div>
</div>

<!-- Edit Menu Item Modal -->
<div id="editItemModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
    <div class="bg-white rounded-2xl shadow-2xl max-w-2xl w-full max-h-[90vh] overflow-y-auto">
        <!-- Modal Header -->
        <div class="bg-gradient-to-r from-primary via-primary-dark to-secondary p-6 text-white rounded-t-2xl">
            <div class="flex items-center justify-between">
                <h3 class="text-xl font-bold">
                    <i class="fas fa-pencil-alt mr-2"></i>Edit Menu Item <span id="editItemNameDisplay" class="font-mono text-purple-100 italic"></span>
                </h3>
                <button type="button" onclick="document.getElementById('editItemModal').classList.add('hidden'); document.getElementById('editItemModal').classList.remove('animate__fadeIn', 'animate__zoomIn');" class="text-white hover:text-gray-200 text-2xl">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        </div>

        <!-- Modal Body -->
        <div class="p-6">
            <form action="menu.php" method="POST" enctype="multipart/form-data" id="editItemForm" class="space-y-6">
                <!-- Hidden input for item ID -->
                <input type="hidden" name="id" id="edit_item_id">
                <input type="hidden" name="edit_menu_item" value="1"> <!-- Marker for PHP POST handler -->
                <!-- Hidden input for remaining additional images -->
                <input type="hidden" name="remaining_additional_images" id="remaining_additional_images" value="">

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <!-- Item Name -->
                    <div>
                        <label for="edit_name" class="block text-sm font-semibold text-gray-700 mb-2">Item Name <span class="text-red-500">*</span></label>
                        <input type="text" name="name" id="edit_name" required
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-600 focus:border-transparent transition-colors">
                    </div>
                    <!-- Item Price -->
                    <div>
                        <label for="edit_price" class="block text-sm font-semibold text-gray-700 mb-2">Price (KES) <span class="text-red-500">*</span></label>
                        <input type="number" step="0.01" min="0" name="price" id="edit_price" required
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-600 focus:border-transparent transition-colors">
                    </div>
                </div>

                <!-- Item Description -->
                <div>
                    <label for="edit_description" class="block text-sm font-semibold text-gray-700 mb-2">Description</label>
                    <textarea name="description" id="edit_description" rows="3"
                              class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-600 focus:border-transparent transition-colors"
                              placeholder="Describe the menu item"></textarea>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <!-- Category -->
                    <div>
                        <label for="edit_category_id" class="block text-sm font-semibold text-gray-700 mb-2">Category <span class="text-red-500">*</span></label>
                        <select name="category_id" id="edit_category_id" required
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-600 focus:border-transparent transition-colors">
                            <option value="">Select a category</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?php echo $cat['id']; ?>"><?php echo htmlspecialchars($cat['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <!-- Status -->
                    <div>
                        <label for="edit_status" class="block text-sm font-semibold text-gray-700 mb-2">Status <span class="text-red-500">*</span></label>
                        <select name="status" id="edit_status" required
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-600 focus:border-transparent transition-colors">
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                </div>

                <!-- Image Upload -->
                <div>
                    <label for="edit_image" class="block text-sm font-semibold text-gray-700 mb-2">Item Image</label>
                    <input type="file" name="image" id="edit_image" accept="image/*"
                           class="w-full text-gray-700 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-purple-50 file:text-purple-700 hover:file:bg-purple-100">
                    <p class="text-xs text-gray-500 mt-1">Leave blank to keep current image. Max 2MB, JPG/PNG.</p>
                    <div id="edit_current_image_preview" class="mt-2" style="display:none;">
                        <span class="text-sm text-gray-600">Current Image:</span>
                        <img id="edit_image_thumb" src="" alt="Current Image" class="block w-24 h-24 object-cover rounded-lg border border-gray-200 mt-1">
                    </div>
                </div>

                <!-- Additional Images Upload -->
                <div>
                    <label for="edit_additional_images" class="block text-sm font-semibold text-gray-700 mb-2">Additional Images</label>
                    <input type="file" name="additional_images[]" id="edit_additional_images" accept="image/*" multiple
                           class="w-full text-gray-700 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-purple-50 file:text-purple-700 hover:file:bg-purple-100">
                    <p class="text-xs text-gray-500 mt-1">Select multiple images. Max 2MB each, JPG/PNG. Leave blank to keep current additional images.</p>
                    <div id="edit_current_additional_images_preview" class="mt-2">
                        <span class="text-sm text-gray-600">Current Additional Images:</span>
                        <div id="edit_additional_images_thumbs" class="flex flex-wrap gap-2 mt-1"></div>
                    </div>
                </div>

                <!-- Ingredients -->
                <div>
                    <label for="edit_ingredients" class="block text-sm font-semibold text-gray-700 mb-2">
                        <i class="fas fa-list mr-2 text-green-600"></i>
                        Ingredients
                    </label>
                    <textarea name="ingredients" id="edit_ingredients" rows="3"
                              class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-600 focus:border-transparent transition-colors"
                              placeholder="List all ingredients used in this menu item"><?php echo htmlspecialchars($itemData['ingredients'] ?? ''); ?></textarea>
                    <p class="text-xs text-gray-500 mt-1">Detailed list of ingredients for dietary information</p>
                </div>

                <!-- Allergens -->
                <div>
                    <label for="edit_allergens" class="block text-sm font-semibold text-gray-700 mb-2">
                        <i class="fas fa-exclamation-triangle mr-2 text-orange-600"></i>
                        Allergen Information
                    </label>
                    <textarea name="allergens" id="edit_allergens" rows="3"
                              class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-600 focus:border-transparent transition-colors"
                              placeholder="List potential allergens (nuts, dairy, gluten, etc.)"><?php echo htmlspecialchars($itemData['allergens'] ?? ''); ?></textarea>
                    <p class="text-xs text-gray-500 mt-1">Important allergen information for customer safety</p>
                </div>

                <!-- Nutrition Information -->
                <div>
                    <label for="edit_nutrition_info" class="block text-sm font-semibold text-gray-700 mb-2">
                        <i class="fas fa-chart-line mr-2 text-blue-600"></i>
                        Nutrition Information
                    </label>
                    <textarea name="nutrition_info" id="edit_nutrition_info" rows="4"
                              class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-600 focus:border-transparent transition-colors"
                              placeholder="Calories, protein, carbs, fat content, etc."><?php echo htmlspecialchars($itemData['nutrition_info'] ?? ''); ?></textarea>
                    <p class="text-xs text-gray-500 mt-1">Nutritional data (calories, macros, vitamins, etc.)</p>
                </div>

                <!-- Is Featured Toggle -->
                <div class="flex items-center space-x-2">
                    <input type="checkbox" name="is_featured" id="edit_is_featured" class="h-4 w-4 text-purple-600 focus:ring-purple-500 border-gray-300 rounded">
                    <label for="edit_is_featured" class="text-sm font-medium text-gray-700">Mark as Featured</label>
                </div>
            </form>
        </div>

        <!-- Modal Footer -->
        <div class="p-6 border-t border-gray-200 flex gap-3 justify-end">
            <button type="button" onclick="document.getElementById('editItemModal').classList.add('hidden'); document.getElementById('editItemModal').classList.remove('animate__fadeIn', 'animate__zoomIn');"
                    class="group relative bg-gray-500 hover:bg-gray-600 text-white px-6 py-3 rounded-xl font-semibold transition-all duration-300 flex items-center justify-center shadow-lg hover:shadow-xl transform hover:-translate-y-0.5">
                <div class="absolute inset-0 bg-gradient-to-r from-white/0 via-white/10 to-white/0 opacity-0 group-hover:opacity-100 transition-opacity duration-300 rounded-xl"></div>
                <span class="relative z-10 font-medium">Cancel</span>
            </button>
             <button type="submit" form="editItemForm"
                    class="group relative bg-primary hover:bg-primary-dark text-white px-6 py-3 rounded-xl font-semibold transition-all duration-300 flex items-center justify-center shadow-lg hover:shadow-xl transform hover:-translate-y-0.5">
                <div class="absolute inset-0 bg-gradient-to-r from-white/0 via-white/10 to-white/0 opacity-0 group-hover:opacity-100 transition-opacity duration-300 rounded-xl"></div>
                <i class="fas fa-save mr-2 relative z-10"></i>
                <span class="relative z-10 font-medium">Save Changes</span>
            </button>
        </div>
    </div>
</div>

<!-- Add New Menu Item Modal -->
<div id="addItemModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
    <div class="bg-white rounded-2xl shadow-2xl max-w-2xl w-full max-h-[90vh] overflow-y-auto">
        <!-- Modal Header -->
        <div class="bg-gradient-to-r from-green-600 to-green-700 p-6 text-white rounded-t-2xl">
            <div class="flex items-center justify-between">
                <h3 class="text-xl font-bold">
                    <i class="fas fa-plus-circle mr-2"></i>Add New Menu Item
                </h3>
                <button type="button" onclick="document.getElementById('addItemModal').classList.add('hidden'); document.getElementById('addItemModal').classList.remove('animate__fadeIn', 'animate__zoomIn'); document.getElementById('addItemForm')?.reset();" class="text-white hover:text-gray-200 text-2xl">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        </div>

        <!-- Modal Body -->
        <div class="p-6">
            <form action="menu.php" method="POST" enctype="multipart/form-data" id="addItemForm" class="space-y-6">
                <input type="hidden" name="add_menu_item" value="1"> <!-- Marker for PHP POST handler -->

                 <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <!-- Item Name -->
                    <div>
                        <label for="add_name" class="block text-sm font-semibold text-gray-700 mb-2">Item Name <span class="text-red-500">*</span></label>
                        <input type="text" name="name" id="add_name" required
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-600 focus:border-transparent transition-colors">
                    </div>
                    <!-- Item Price -->
                    <div>
                        <label for="add_price" class="block text-sm font-semibold text-gray-700 mb-2">Price (KES) <span class="text-red-500">*</span></label>
                        <input type="number" step="0.01" min="0" name="price" id="add_price" value="0.00" required
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-600 focus:border-transparent transition-colors">
                    </div>
                </div>

                <!-- Item Description -->
                <div>
                    <label for="add_description" class="block text-sm font-semibold text-gray-700 mb-2">Description</label>
                    <textarea name="description" id="add_description" rows="3"
                              class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-600 focus:border-transparent transition-colors"
                              placeholder="Describe the menu item"></textarea>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <!-- Category -->
                    <div>
                        <label for="add_category_id" class="block text-sm font-semibold text-gray-700 mb-2">Category <span class="text-red-500">*</span></label>
                        <select name="category_id" id="add_category_id" required
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-600 focus:border-transparent transition-colors">
                            <option value="">Select a category</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?php echo $cat['id']; ?>"><?php echo htmlspecialchars($cat['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <!-- Status -->
                    <div>
                        <label for="add_status" class="block text-sm font-semibold text-gray-700 mb-2">Status <span class="text-red-500">*</span></label>
                        <select name="status" id="add_status" required
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-600 focus:border-transparent transition-colors">
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                </div>

                <!-- Image Upload -->
                <div>
                    <label for="add_image" class="block text-sm font-semibold text-gray-700 mb-2">Item Image</label>
                    <input type="file" name="image" id="add_image" accept="image/*"
                           class="w-full text-gray-700 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-green-50 file:text-green-700 hover:file:bg-green-100">
                    <p class="text-xs text-gray-500 mt-1">Max 2MB, JPG/PNG. Required for new items.</p>
                </div>

                <!-- Additional Images Upload -->
                <div>
                    <label for="add_additional_images" class="block text-sm font-semibold text-gray-700 mb-2">Additional Images</label>
                    <input type="file" name="additional_images[]" id="add_additional_images" accept="image/*" multiple
                           class="w-full text-gray-700 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-green-50 file:text-green-700 hover:file:bg-green-100">
                    <p class="text-xs text-gray-500 mt-1">Select multiple images. Max 2MB each, JPG/PNG.</p>
                </div>

                <!-- Ingredients -->
                <div>
                    <label for="add_ingredients" class="block text-sm font-semibold text-gray-700 mb-2">
                        <i class="fas fa-list mr-2 text-green-600"></i>
                        Ingredients
                    </label>
                    <textarea name="ingredients" id="add_ingredients" rows="3"
                              class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-600 focus:border-transparent transition-colors"
                              placeholder="List all ingredients used in this menu item"></textarea>
                    <p class="text-xs text-gray-500 mt-1">Detailed list of ingredients for dietary information</p>
                </div>

                <!-- Allergens -->
                <div>
                    <label for="add_allergens" class="block text-sm font-semibold text-gray-700 mb-2">
                        <i class="fas fa-exclamation-triangle mr-2 text-orange-600"></i>
                        Allergen Information
                    </label>
                    <textarea name="allergens" id="add_allergens" rows="3"
                              class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-600 focus:border-transparent transition-colors"
                              placeholder="List potential allergens (nuts, dairy, gluten, etc.)"></textarea>
                    <p class="text-xs text-gray-500 mt-1">Important allergen information for customer safety</p>
                </div>

                <!-- Nutrition Information -->
                <div>
                    <label for="add_nutrition_info" class="block text-sm font-semibold text-gray-700 mb-2">
                        <i class="fas fa-chart-line mr-2 text-blue-600"></i>
                        Nutrition Information
                    </label>
                    <textarea name="nutrition_info" id="add_nutrition_info" rows="4"
                              class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-600 focus:border-transparent transition-colors"
                              placeholder="Calories, protein, carbs, fat content, etc."></textarea>
                    <p class="text-xs text-gray-500 mt-1">Nutritional data (calories, macros, vitamins, etc.)</p>
                </div>

                <!-- Is Featured Toggle -->
                <div class="flex items-center space-x-2">
                    <input type="checkbox" name="is_featured" id="add_is_featured" class="h-4 w-4 text-green-600 focus:ring-green-500 border-gray-300 rounded">
                    <label for="add_is_featured" class="text-sm font-medium text-gray-700">Mark as Featured</label>
                </div>
            </form>
        </div>

        <!-- Modal Footer -->
        <div class="p-6 border-t border-gray-200 flex gap-3 justify-end">
            <button type="button" onclick="document.getElementById('addItemModal').classList.add('hidden'); document.getElementById('addItemModal').classList.remove('animate__fadeIn', 'animate__zoomIn'); document.getElementById('addItemForm')?.reset();"
                    class="group relative bg-gray-500 hover:bg-gray-600 text-white px-6 py-3 rounded-xl font-semibold transition-all duration-300 flex items-center justify-center shadow-lg hover:shadow-xl transform hover:-translate-y-0.5">
                <div class="absolute inset-0 bg-gradient-to-r from-white/0 via-white/10 to-white/0 opacity-0 group-hover:opacity-100 transition-opacity duration-300 rounded-xl"></div>
                <span class="relative z-10 font-medium">Cancel</span>
            </button>
             <button type="submit" form="addItemForm"
                    class="group relative bg-green-600 hover:bg-green-700 text-white px-6 py-3 rounded-xl font-semibold transition-all duration-300 flex items-center justify-center shadow-lg hover:shadow-xl transform hover:-translate-y-0.5">
                <div class="absolute inset-0 bg-gradient-to-r from-white/0 via-white/10 to-white/0 opacity-0 group-hover:opacity-100 transition-opacity duration-300 rounded-xl"></div>
                <i class="fas fa-plus mr-2 relative z-10"></i>
                <span class="relative z-10 font-medium">Add Item</span>
            </button>
        </div>
    </div>
</div>


<!-- ============================================== JAVASCRIPT ============================================== -->
<script>
// IMPORTANT: Ensure SweetAlert2 is loaded. It is assumed to be included in your includes/header.php
// or includes/admin_footer.php. If not, add this line in your header/footer:
// <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
// Global utility to close modals by adding 'hidden' class
function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.add('hidden');
        modal.classList.remove('animate__fadeIn', 'animate__zoomIn');

        // Reset forms for Add/Edit modals
        if (modalId === 'addItemModal') {
            document.getElementById('addItemForm')?.reset();
        }
        if (modalId === 'editItemModal') {
            // Reset form when closing Edit modal
            const editForm = document.getElementById('editItemForm');
            if (editForm) {
                editForm.reset();
            }
        }
    }
}

// Keyboard navigation for modals (Escape key)
document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape') {
        const modalIds = ['deleteModal', 'editItemModal', 'addItemModal'];
        modalIds.forEach(id => {
            const modal = document.getElementById(id);
            if (modal && !modal.classList.contains('hidden')) {
                modal.classList.add('hidden');
                modal.classList.remove('animate__fadeIn', 'animate__zoomIn');

                // Reset forms for Add/Edit modals
                if (id === 'addItemModal') {
                    document.getElementById('addItemForm')?.reset();
                }
                if (id === 'editItemModal') {
                    document.getElementById('editItemForm')?.reset();
                }

                event.preventDefault(); // Prevent default ESC behavior
            }
        });
    }
});

document.addEventListener('DOMContentLoaded', function() {
    // --- Message Display on Page Load (from PHP session for SweetAlert2) ---
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

    // --- FILTERING LOGIC ---
    // These listeners trigger a full page reload with new URL parameters (server-side filtering)
    const searchInput = document.getElementById('searchInput');
    const categoryFilter = document.getElementById('categoryFilter');
    const statusFilter = document.getElementById('statusFilter');
    const filterTabs = document.querySelectorAll('.filter-tab[data-filter]');

    const applyFiltersToUrl = () => {
        let url = new URL(window.location.origin + window.location.pathname);

        const searchInputVal = searchInput?.value;
        const categoryFilterVal = categoryFilter?.value;
        const statusFilterVal = statusFilter?.value;

        if (searchInputVal) url.searchParams.set('search', searchInputVal);
        if (categoryFilterVal) url.searchParams.set('category', categoryFilterVal);

        if (statusFilterVal && statusFilterVal !== 'all') { // Avoid sending 'all' status
            url.searchParams.set('status', statusFilterVal);
        } else {
            url.searchParams.delete('status'); // Remove if 'all' or empty
        }

        url.searchParams.delete('page'); // Reset page to 1 when filters change
        window.location.href = url.toString();
    };

    let searchTimeout;
    searchInput?.addEventListener('input', () => {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(applyFiltersToUrl, 500); // Debounce search
    });
    categoryFilter?.addEventListener('change', applyFiltersToUrl);
    statusFilter?.addEventListener('change', applyFiltersToUrl);

    filterTabs.forEach(tab => {
        tab.addEventListener('click', function() {
            const newStatus = this.dataset.filter === 'all' ? '' : this.dataset.filter;
            if (statusFilter) statusFilter.value = newStatus; // Update select element before applying
            applyFiltersToUrl(); // Apply filters
        });
    });
});

// Function to clear all filter inputs and reload the page
function clearFilters() {
    // Clear the values of the filter inputs
    document.getElementById('searchInput').value = '';
    document.getElementById('categoryFilter').value = '';
    document.getElementById('statusFilter').value = '';
    // Reload page without any GET parameters to show all items
    window.location.href = window.location.origin + window.location.pathname;
}


// --- Delete Confirmation Modal Logic ---
function confirmDeleteItem(id, name) {
    document.getElementById('deleteItemId').value = id;
    document.getElementById('deleteItemIdDisplay').textContent = id;
    document.getElementById('deleteItemName').textContent = name;
    document.getElementById('deleteModal').classList.remove('hidden');
}

// --- Open Edit Item Modal ---
// Asynchronously fetches item data and populates the edit form
async function openEditItemModal(itemId) {
    try {
        // Fetch item data using the API endpoint defined at the top of menu.php
        const response = await fetch(`menu.php?fetch_item_data=${itemId}`);
        if (!response.ok) {
            // Attempt to parse JSON error message if provided by the PHP endpoint
            const errorData = await response.json();
            throw new Error(errorData.error || `HTTP error! status: ${response.status}`);
        }
        const itemData = await response.json();

        // Populate the form fields in the edit modal
        document.getElementById('edit_item_id').value = itemData.id;
        document.getElementById('editItemNameDisplay').textContent = itemData.name;
        document.getElementById('edit_name').value = itemData.name;
        document.getElementById('edit_price').value = parseFloat(itemData.price).toFixed(2);
        document.getElementById('edit_description').value = itemData.description || '';
        document.getElementById('edit_ingredients').value = itemData.ingredients || '';
        document.getElementById('edit_allergens').value = itemData.allergens || '';
        document.getElementById('edit_nutrition_info').value = itemData.nutrition_info || '';
        document.getElementById('edit_category_id').value = itemData.category_id;
        document.getElementById('edit_status').value = itemData.status;
        document.getElementById('edit_is_featured').checked = itemData.is_featured; // Boolean from API

        // Image preview logic
        const currentImagePreview = document.getElementById('edit_current_image_preview');
        const imageThumb = document.getElementById('edit_image_thumb');
        if (itemData.image) {
            imageThumb.src = `../uploads/menu/${itemData.image}`; // Adjust path if needed
            currentImagePreview.style.display = 'block';
        } else {
            currentImagePreview.style.display = 'none';
            imageThumb.src = ''; // Clear src if no image
        }

        // Additional images preview logic
        const currentAdditionalImagesPreview = document.getElementById('edit_current_additional_images_preview');
        const additionalImagesThumbs = document.getElementById('edit_additional_images_thumbs');
        const additionalImages = JSON.parse(itemData.additional_images || '[]');
        if (additionalImages.length > 0) {
            additionalImagesThumbs.innerHTML = additionalImages.map((img, index) => {
                const imgSrc = `../uploads/menu/${img}`;
                return `<div class="relative w-16 h-16 group" data-index="${index}">
                    <img src="${imgSrc}" alt="Additional Image" class="w-16 h-16 object-cover rounded border" onerror="this.onerror=null; this.src='data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNjQiIGhlaWdodD0iNjQiIHZpZXdCb3g9IjAgMCA2NCA2NCIgZmlsbD0ibm9uZSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj4KPHJlY3Qgd2lkdGg9IjY0IiBoZWlnaHQ9IjY0IiBmaWxsPSIjRjNGNEY2Ii8+CjxwYXRoIGQ9Ik00MiAzMkM0MiAyNi4wOTU0IDM3LjkwNTQgMjIgMzIgMjJDMjYuMDk1NCAyMiAyMiAyNi4wOTU0IDIyIDMyQzIyIDM3LjkwNTQgMjYuMDk1NCA0MiAzMiA0MkM0MiA0MiA0MiAzNy45MDU0IDQyIDMyWiIgZmlsbD0iI0Q5RDlEOSIvPgo8L3N2Zz4K'; this.alt='Image not found';">
                    <button type="button" class="absolute top-0 right-0 bg-red-500 text-white rounded-full w-4 h-4 text-xs opacity-0 group-hover:opacity-100 transition-opacity" onclick="removeAdditionalImage(${index})">×</button>
                </div>`;
            }).join('');
            currentAdditionalImagesPreview.style.display = 'block';
        } else {
            currentAdditionalImagesPreview.style.display = 'none';
            additionalImagesThumbs.innerHTML = '';
        }

        // Show the edit modal
        document.getElementById('editItemModal').classList.remove('hidden');
    } catch (error) {
        console.error('Error opening edit modal:', error);
        Swal.fire({
            icon: 'error',
            title: 'Oops...',
            text: `Could not load menu item details: ${error.message}. Please check console for more details.`,
        });
    }
}

// --- Open Add Item Modal ---
function openAddItemModal() {
    // Reset the form to clear any previous data
    document.getElementById('addItemForm').reset();
    // Set default values for new item
    document.getElementById('add_name').value = '';
    document.getElementById('add_price').value = '0.00';
    document.getElementById('add_description').value = '';
    document.getElementById('add_ingredients').value = '';
    document.getElementById('add_allergens').value = '';
    document.getElementById('add_nutrition_info').value = '';
    document.getElementById('add_category_id').value = ''; // Ensure no category pre-selected
    document.getElementById('add_status').value = 'active';
    document.getElementById('add_is_featured').checked = false; // Default to not featured
    document.getElementById('add_image').value = ''; // Clear file input value

    // Show the add modal
    document.getElementById('addItemModal').classList.remove('hidden');
}


// --- Toggle Item Status / Featured Status (using SweetAlert2 for confirmation and form submission) ---
async function toggleItemProperty(itemId, actionType) {
    let confirmTitle = 'Confirm Action';
    let confirmText = '';
    let successMsg = ''; // Although PHP handles message, good to have for consistency

    // Determine the confirmation message based on actionType
    if (actionType === 'toggle_status') {
        const itemElement = document.querySelector(`.menu-item-card[data-item-id="${itemId}"]`);
        const currentStatus = itemElement?.dataset.status;
        const newStatusText = currentStatus === 'active' ? 'inactive' : 'active';
        confirmText = `Are you sure you want to change the status to "${newStatusText}"?`;
        successMsg = `Status updated to ${newStatusText}.`;
    } else if (actionType === 'toggle_featured') {
        const itemElement = document.querySelector(`.menu-item-card[data-item-id="${itemId}"]`);
        const isCurrentlyFeatured = itemElement?.dataset.featured === '1';
        const newFeaturedText = isCurrentlyFeatured ? 'not featured' : 'featured';
        confirmText = `Are you sure you want to mark this item as ${newFeaturedText}?`;
        successMsg = `Item marked as ${newFeaturedText}.`;
    }

    Swal.fire({
        title: confirmTitle,
        text: confirmText,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Yes, do it!'
    }).then(async (result) => {
        if (result.isConfirmed) {
            try {
                // Create a temporary form to submit the action via POST
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = 'menu.php'; // Submit to the current page

                // Hidden fields for ID and action type
                const idInput = document.createElement('input');
                idInput.type = 'hidden';
                idInput.name = 'id';
                idInput.value = itemId;

                const actionInput = document.createElement('input');
                actionInput.type = 'hidden';
                actionInput.name = 'action';
                actionInput.value = actionType;

                form.appendChild(idInput);
                form.appendChild(actionInput);
                document.body.appendChild(form); // Temporarily append form to DOM
                form.submit(); // Programmatically submit the form (this will cause a page reload)

            } catch (error) {
                console.error('Error in toggleItemProperty:', error);
                Swal.fire('Error', `Failed to update item: ${error.message}. Please try again.`, 'error');
            }
        }
    });
}

// Function to remove additional image
function removeAdditionalImage(index) {
    const thumbsContainer = document.getElementById('edit_additional_images_thumbs');
    const images = Array.from(thumbsContainer.children);
    if (images[index]) {
        images[index].remove();
    }
    // Update the hidden input with remaining images
    const remainingImages = images.map(div => {
        const img = div.querySelector('img');
        const src = img.src.split('/').pop();
        return src && !src.includes('data:') ? src : null;
    }).filter(Boolean);
    document.getElementById('remaining_additional_images').value = JSON.stringify(remainingImages);
}
</script>

<style>
/* Enhanced styling specific to menu.php */
.line-clamp-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
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
    /* Tailwind 'primary' color should be defined in tailwind.config.js */
    @apply bg-primary text-white;
}

/* Menu item cards */
.menu-item-card {
    transition: all 0.3s ease;
}

.menu-item-card:hover {
    transform: translateY(-4px);
}

/* Loading states */
/* Used for buttons during AJAX actions */
button:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}

/* Custom scrollbar (Global styles) */
::-webkit-scrollbar {
    width: 8px;
}

::-webkit-scrollbar-track {
    background: #f1f1f1;
}

::-webkit-scrollbar-thumb {
    background: #C1272D; /* Example: Matches a 'primary' color */
    border-radius: 4px;
}

/* Action buttons styling (using Tailwind for classes like group, relative, bg-gradient-to, etc.) */
.action-buttons {
    width: 100%;
}

.action-buttons .primary-actions-top,
.action-buttons .primary-actions-bottom {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 0.5rem;
}

.action-buttons .primary-actions-top button,
.action-buttons .primary-actions-top a,
.action-buttons .primary-actions-bottom button {
    font-size: 0.875rem; /* text-sm */
    font-weight: 500;    /* font-medium */
    padding: 0.625rem 0.75rem; /* py-2.5 px-3 */
    border-radius: 0.5rem; /* rounded-lg */
    transition: all 0.2s ease;
    white-space: nowrap;
    text-align: center;
    border: 1px solid transparent; /* default border for consistency */
    position: relative;
    overflow: hidden;
}

/* Hover/Focus states for all action buttons */
.action-buttons button:hover,
.action-buttons a:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15); /* strong hover shadow */
}

.action-buttons button:focus,
.action-buttons a:focus {
    outline: none;
    box-shadow: 0 0 0 3px rgba(193, 39, 45, 0.3); /* ring-3 with primary color */
}

/* Specific Gradient Styles (Tailwind's bg-gradient-to-r utilities are directly in HTML) */
/* The general hover/focus styles above apply to these buttons as well. */

/* Modals styles - controlled by 'hidden' class with JS */
#deleteModal, #editItemModal, #addItemModal {
    /* Default is hidden, JS removes/adds 'hidden' */
    background-color: rgba(0, 0, 0, 0.5); /* Semi-transparent backdrop */
    z-index: 9999; /* Ensure modals are on top */
}
</style>

<?php include 'includes/footer.php'; // Adjust this path as needed ?>