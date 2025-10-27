<?php
// orders.php - Admin Order Management
// This file provides a comprehensive interface for viewing, filtering, and managing customer orders.

// 1. Session Start
// IMPORTANT: session_start() must be called before any HTML output.
// It's often handled in a central config.php or header.php, but included here for completeness.
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// 2. Page Configuration
$page_title = 'Manage Orders';
$page_description = 'View and manage customer orders';

// 3. Include Core Dependencies
// Adjust paths as necessary based on your project structure.
require_once dirname(__DIR__) . '/includes/config.php'; // Contains database connection ($pdo)
require_once 'includes/functions.php'; // For any custom admin-specific PHP functions (optional)
require_once dirname(__DIR__) . '/includes/ActivityLogger.php'; // Custom activity logging class

// Initialize ActivityLogger with the PDO object
$activityLogger = new ActivityLogger($pdo);

// --- 4. API Endpoint for Fetching Single Order Data ---
// This block handles AJAX/fetch() requests from JavaScript to get specific order details.
// It MUST be placed directly after includes and before any HTML output, as it exits script execution.
if (isset($_GET['fetch_order_data']) && is_numeric($_GET['fetch_order_data'])) {
    $order_id_to_fetch = (int)$_GET['fetch_order_data'];
    try {
        // Fetch order and associated customer details
        $stmt = $pdo->prepare("SELECT o.*, u.name as customer_name, u.email as customer_email FROM orders o LEFT JOIN users u ON o.user_id = u.id WHERE o.id = ?");
        $stmt->execute([$order_id_to_fetch]);
        $order_data = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($order_data) {
            // Convert monetary values to float to ensure correct JSON and JS handling
            $order_data['subtotal'] = (float)$order_data['subtotal'];
            $order_data['delivery_fee'] = (float)$order_data['delivery_fee'];
            $order_data['total'] = (float)$order_data['total'];

            header('Content-Type: application/json');
            echo json_encode($order_data);
        } else {
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Order not found']);
        }
    } catch (PDOException $e) {
        // Log and return database error details
        error_log("DB Error fetching specific order (ID: {$order_id_to_fetch}): " . $e->getMessage());
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Database error', 'details' => $e->getMessage()]);
    }
    exit(); // Crucial: Stop further script execution for API responses
}
// --- END API Endpoint ---

// 5. Initialize Feedback Messages
$success_message = '';
$error_message = '';

// 6. Handle POST Requests for CRUD Operations
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // A. Delete Order Operation
    if (isset($_POST['_method']) && $_POST['_method'] === 'DELETE') {
        $order_id = (int)$_POST['id'];
        try {
            // Retrieve order info before deletion for logging
            $orderStmt = $pdo->prepare("SELECT order_number, user_id FROM orders WHERE id = ?");
            $orderStmt->execute([$order_id]);
            $order_info = $orderStmt->fetch(PDO::FETCH_ASSOC);

            if ($order_info) {
                // Assuming 'order_items' foreign key has ON DELETE CASCADE.
                // If not, explicitly delete from 'order_items' first:
                // $pdo->prepare("DELETE FROM order_items WHERE order_id = ?")->execute([$order_id]);

                $deleteStmt = $pdo->prepare("DELETE FROM orders WHERE id = ?");
                if ($deleteStmt->execute([$order_id])) {
                    $success_message = 'Order ' . htmlspecialchars($order_info['order_number']) . ' deleted successfully.';
                    $activityLogger->logActivity("Order '{$order_info['order_number']}' (ID: {$order_id}) deleted.", $_SESSION['user_id'] ?? null, 'order_delete');
                } else {
                    $error_message = 'Failed to delete order. Database operation failed.';
                    error_log("Failed to delete order ID {$order_id}. PDO execute returned false.");
                }
            } else {
                $error_message = 'Order not found for deletion.';
            }
        } catch (PDOException $e) {
            $error_message = 'Database error during order deletion: ' . $e->getMessage();
            error_log("Error deleting order ID {$order_id}: " . $e->getMessage());
        }
    }
    // B. Update Order Operation
    elseif (isset($_POST['update_order'])) {
        // Sanitize and validate inputs
        $order_id = (int)($_POST['order_id'] ?? 0);
        $status = trim($_POST['status'] ?? '');
        $delivery_address = trim($_POST['delivery_address'] ?? '');
        $delivery_instructions = trim($_POST['delivery_instructions'] ?? '');
        $subtotal = (float)($_POST['subtotal'] ?? 0.0);
        $delivery_fee = (float)($_POST['delivery_fee'] ?? 0.0);
        // Recalculate total_amount server-side for accuracy and security
        // $total_amount = (float)($_POST['total_amount'] ?? 0.0); // Not used directly for update query

        // Basic validation
        if ($order_id <= 0 || empty($status) || empty($delivery_address)) {
            $error_message = 'Invalid input for order update. Please fill all required fields.';
        } else {
            $calculated_total_amount = $subtotal + $delivery_fee; // Recalculate here

            try {
                $stmt = $pdo->prepare("UPDATE orders SET status = ?, delivery_address = ?, delivery_instructions = ?, subtotal = ?, delivery_fee = ?, total = ?, updated_at = NOW() WHERE id = ?");
                $stmt->execute([
                    $status, $delivery_address, $delivery_instructions,
                    $subtotal, $delivery_fee, $calculated_total_amount, $order_id
                ]);
                $success_message = "Order #{$order_id} updated successfully.";
                $activityLogger->logActivity("Order #{$order_id} details updated (status: {$status}).", $_SESSION['user_id'] ?? null, 'order_update');
            } catch (PDOException $e) {
                $error_message = 'Database error updating order: ' . $e->getMessage();
                error_log("Error updating order ID {$order_id}: " . $e->getMessage());
            }
        }
    }
    // C. Add New Order Operation
    elseif (isset($_POST['add_order'])) {
        // Sanitize and validate inputs
        $user_id = (int)($_POST['user_id'] ?? 0);
        $status = trim($_POST['status'] ?? '');
        $payment_method = trim($_POST['payment_method'] ?? '');
        $payment_status = trim($_POST['payment_status'] ?? '');
        $delivery_address = trim($_POST['delivery_address'] ?? '');
        $delivery_instructions = trim($_POST['delivery_instructions'] ?? '');
        $subtotal = (float)($_POST['subtotal'] ?? 0.0);
        $delivery_fee = (float)($_POST['delivery_fee'] ?? 0.0);
        $total_amount = $subtotal + $delivery_fee; // Calculate server-side

        $order_number = "ORD-" . strtoupper(uniqid()); // Generate a unique order number

        // Basic validation
        if ($user_id <= 0 || empty($status) || empty($payment_method) || empty($payment_status) || empty($delivery_address)) {
            $error_message = 'Invalid input for new order. Please fill all required fields and select a customer.';
        } else {
            try {
                $stmt = $pdo->prepare("INSERT INTO orders (user_id, order_number, status, payment_method, payment_status, delivery_address, delivery_instructions, subtotal, delivery_fee, total_amount, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())");
                $stmt->execute([
                    $user_id, $order_number, $status, $payment_method, $payment_status,
                    $delivery_address, $delivery_instructions, $subtotal, $delivery_fee, $total_amount
                ]);
                $new_order_id = $pdo->lastInsertId();
                $success_message = "New Order #{$order_number} (ID: {$new_order_id}) created successfully.";
                $activityLogger->logActivity("New Order #{$order_number} (ID:{$new_order_id}) created.", $_SESSION['user_id'] ?? null, 'order_add');
            } catch (PDOException $e) {
                $error_message = 'Database error creating new order: ' . $e->getMessage();
                error_log("Error creating new order: " . $e->getMessage());
            }
        }
    }

    // Redirect to prevent form re-submission on refresh (PRG pattern)
    if ($success_message) {
        $_SESSION['message'] = ['type' => 'success', 'text' => $success_message];
    } elseif ($error_message) {
        $_SESSION['message'] = ['type' => 'error', 'text' => $error_message];
    }
    header('Location: orders.php');
    exit();
}

// 7. Process One-Time Session Messages
// This displays success/error messages from previous redirects.
if (isset($_SESSION['message'])) {
    $message = $_SESSION['message'];
    unset($_SESSION['message']); // Clear message after display
    if ($message['type'] === 'success') {
        $success_message = $message['text'];
    } else {
        $error_message = $message['text'];
    }
}

// --- 8. Data Retrieval for Page Display (GET requests) ---

// Get filter parameters from URL
$status_filter = trim($_GET['status'] ?? '');
$date_from = trim($_GET['date_from'] ?? '');
$date_to = trim($_GET['date_to'] ?? '');
$search = trim($_GET['search'] ?? '');
$page = (int)($_GET['page'] ?? 1); // Current page for pagination

// Prepare dynamic WHERE clauses and parameters
$where_conditions = [];
$params = [];
$base_query_joins = "FROM orders o LEFT JOIN users u ON o.user_id = u.id";

if (!empty($status_filter) && $status_filter !== 'all') {
    $where_conditions[] = "o.status = :status_filter";
    $params[':status_filter'] = $status_filter;
}

if (!empty($date_from)) {
    $where_conditions[] = "DATE(o.created_at) >= :date_from";
    $params[':date_from'] = $date_from;
}

if (!empty($date_to)) {
    $where_conditions[] = "DATE(o.created_at) <= :date_to";
    $params[':date_to'] = $date_to;
}

if (!empty($search)) {
    $where_conditions[] = "(o.order_number LIKE :search_num OR u.name LIKE :search_name OR u.email LIKE :search_email)";
    $params[':search_num'] = "%{$search}%";
    $params[':search_name'] = "%{$search}%";
    $params[':search_email'] = "%{$search}%";
}

$final_where_clause = '';
if (!empty($where_conditions)) {
    $final_where_clause = " WHERE " . implode(" AND ", $where_conditions);
}

// 9. Total Record Count for Pagination
$count_query = "SELECT COUNT(o.id) AS total $base_query_joins $final_where_clause";
$count_stmt = $pdo->prepare($count_query);
foreach ($params as $key => $value) {
    $count_stmt->bindValue($key, $value, is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR);
}
$count_stmt->execute();
$total_records = $count_stmt->fetchColumn(); // Use fetchColumn for single value

// 10. Pagination Calculations
$per_page = 10; // Number of orders per page
$page = max(1, $page); // Ensure page is at least 1
$total_pages = ceil($total_records / $per_page);
$offset = ($page - 1) * $per_page;

// 11. Main Order Data Retrieval for Current Page
$query_data = "
    SELECT o.*, u.name AS customer_name, u.email AS customer_email, u.phone AS customer_phone
    $base_query_joins
    $final_where_clause
    ORDER BY o.created_at DESC
    LIMIT :limit OFFSET :offset";

$stmt_data = $pdo->prepare($query_data);
foreach ($params as $key => $value) {
    $stmt_data->bindValue($key, $value, is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR);
}
$stmt_data->bindValue(':limit', $per_page, PDO::PARAM_INT);
$stmt_data->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt_data->execute();
$orders = $stmt_data->fetchAll(PDO::FETCH_ASSOC);

// 12. Enrich Orders with Associated Items Summary
// This fetches items for each order displayed on the current page.
foreach ($orders as &$order) { // Use reference to modify array elements directly
    $items_stmt = $pdo->prepare("SELECT item_name, quantity, price, total FROM order_items WHERE order_id = ?");
    $items_stmt->execute([$order['id']]);
    $order_items = $items_stmt->fetchAll(PDO::FETCH_ASSOC);

    $order['item_count'] = count($order_items);
    $item_names_arr = array_column($order_items, 'item_name');
    $order['item_names_summary'] = implode(', ', $item_names_arr);
}
unset($order); // Unset the reference after the loop is complete (corrected: from unsetId to unset)

// 13. Calculate Status Counts for Filter Tabs (respecting other active filters)
$status_counts = [];
$all_status_options = ['pending', 'processing', 'shipped', 'delivered', 'cancelled'];
// Define status colors for filters
$status_colors = [
    'pending' => 'bg-yellow-100 text-yellow-600',
    'processing' => 'bg-blue-100 text-blue-600',
    'shipped' => 'bg-indigo-100 text-indigo-600',
    'delivered' => 'bg-green-100 text-green-600',
    'cancelled' => 'bg-red-100 text-red-600'
];

foreach ($all_status_options as $status_key) {
    $temp_conditions = $where_conditions; // Start with current filters (date, search)
    $temp_params = $params;

    // Remove any status_filter from the main parameters for this specific count
    $key_to_remove = null;
    foreach($temp_conditions as $k => $condition_str) {
        if (strpos($condition_str, 'o.status = :status_filter') !== false) {
            $key_to_remove = $k;
            break;
        }
    }
    if ($key_to_remove !== false && $key_to_remove !== null) { // Check for non-false as well
        unset($temp_conditions[$key_to_remove]);
        unset($temp_params[':status_filter']);
    }

    $temp_conditions_for_count = array_values($temp_conditions); // Re-index after potential unset
    $temp_conditions_for_count[] = "o.status = :current_status"; // Add current status for this specific count

    $temp_final_where = !empty($temp_conditions_for_count) ? " WHERE " . implode(" AND ", $temp_conditions_for_count) : "";

    $count_this_status_query = "SELECT COUNT(o.id) $base_query_joins $temp_final_where";
    $count_this_status_stmt = $pdo->prepare($count_this_status_query);

    // Bind parameters for this count query
    foreach ($temp_params as $param_key => $param_value) {
        $count_this_status_stmt->bindValue($param_key, $param_value, is_int($param_value) ? PDO::PARAM_INT : PDO::PARAM_STR);
    }
    $count_this_status_stmt->bindValue(':current_status', $status_key); // Bind the specific status for this loop iteration

    $count_this_status_stmt->execute();
    $status_counts[$status_key] = $count_this_status_stmt->fetchColumn() ?: 0; // Ensure it's 0 if no results
}

// 14. Fetch Customer List for "Add Order" Modal (if needed)
$customer_list = [];
try {
    $stmt_customers = $pdo->query("SELECT id, name, email FROM users WHERE role = 'customer' ORDER BY name ASC");
    $customer_list = $stmt_customers->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error fetching customer list for add order modal: " . $e->getMessage());
}

// 15. Include Header (Start HTML Output)
include 'includes/header.php';
?>

<!-- ============================================== HTML BEGINS HERER ============================================== -->

<!-- Dashboard Header -->
<div class="bg-gradient-to-br from-primary via-primary-dark to-secondary text-white mt-0">
    <div class="container mx-auto px-6 py-8">
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between">
            <div>
                <h1 class="text-3xl md:text-4xl font-bold mb-2"><?= htmlspecialchars($page_title) ?></h1>
                <p class="text-lg opacity-90"><?= htmlspecialchars($page_description) ?></p>
            </div>
            <div class="mt-4 lg:mt-0">
                <button type="button" onclick="addOrder()"
                   class="group relative bg-gradient-to-r from-green-600 to-green-700 hover:from-green-700 hover:to-green-800 text-white px-6 py-3 rounded-xl font-semibold transition-all duration-300 flex items-center shadow-lg hover:shadow-xl transform hover:-translate-y-1 border border-green-400/20">
                    <div class="absolute inset-0 bg-gradient-to-r from-white/0 via-white/10 to-white/0 opacity-0 group-hover:opacity-100 transition-opacity duration-300 rounded-xl"></div>
                    <i class="fas fa-plus mr-2 relative z-10"></i>
                    <span class="relative z-10 font-medium">Add New Order</span>
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Statistics Cards Section -->
<div class="bg-gray-50 py-8">
    <div class="container mx-auto px-6">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
            <!-- Total Orders Card -->
            <div class="bg-white rounded-xl shadow-lg p-6 border-l-4 border-red-500">
                <div class="flex items-center">
                    <div class="p-3 bg-red-100 rounded-lg">
                        <i class="fas fa-shopping-cart text-2xl text-red-600"></i>
                    </div>
                    <div class="ml-4">
                        <h3 class="text-2xl font-bold text-gray-900"><?php echo number_format($total_records); ?></h3>
                        <p class="text-gray-600">Total Orders (Filtered)</p>
                    </div>
                </div>
            </div>

            <!-- Delivered Orders Card -->
            <div class="bg-white rounded-xl shadow-lg p-6 border-l-4 border-green-500">
                <div class="flex items-center">
                    <div class="p-3 bg-green-100 rounded-lg">
                        <i class="fas fa-check-circle text-2xl text-green-600"></i>
                    </div>
                    <div class="ml-4">
                        <h3 class="text-2xl font-bold text-gray-900">
                            <?php echo number_format($status_counts['delivered'] ?? 0); ?>
                        </h3>
                        <p class="text-gray-600">Delivered (Filtered)</p>
                    </div>
                </div>
            </div>

            <!-- Pending Orders Card -->
            <div class="bg-white rounded-xl shadow-lg p-6 border-l-4 border-yellow-500">
                <div class="flex items-center">
                    <div class="p-3 bg-yellow-100 rounded-lg">
                        <i class="fas fa-clock text-2xl text-yellow-600"></i>
                    </div>
                    <div class="ml-4">
                        <h3 class="text-2xl font-bold text-gray-900">
                            <?php echo number_format($status_counts['pending'] ?? 0); ?>
                        </h3>
                        <p class="text-gray-600">Pending (Filtered)</p>
                    </div>
                </div>
            </div>

            <!-- Total Revenue Card -->
            <div class="bg-white rounded-xl shadow-lg p-6 border-l-4 border-purple-500">
                <div class="flex items-center">
                    <div class="p-3 bg-purple-100 rounded-lg">
                        <i class="fas fa-dollar-sign text-2xl text-purple-600"></i>
                    </div>
                    <div class="ml-4">
                        <h3 class="text-2xl font-bold text-gray-900">
                            KES <?php
                            try {
                                $revenue_conditions = $where_conditions;
                                $revenue_params = $params;

                                // Remove any previous general status filter from the main query's conditions/params
                                $key_to_remove = null;
                                foreach($revenue_conditions as $k => $condition_str) {
                                    if (strpos($condition_str, 'o.status = :status_filter') !== false) {
                                        $key_to_remove = $k;
                                        break;
                                    }
                                }
                                if ($key_to_remove !== false && $key_to_remove !== null) {
                                    unset($revenue_conditions[$key_to_remove]);
                                    unset($revenue_params[':status_filter']);
                                }

                                $revenue_conditions_final = array_values($revenue_conditions);
                                $revenue_conditions_final[] = "o.status = 'delivered'"; // Only count delivered for revenue
                                $revenue_final_where = !empty($revenue_conditions_final) ? " WHERE " . implode(" AND ", $revenue_conditions_final) : "";

                                $total_revenue_stmt = $pdo->prepare("SELECT SUM(o.total_amount) $base_query_joins $revenue_final_where");
                                foreach ($revenue_params as $key => $value) {
                                    $total_revenue_stmt->bindValue($key, $value, is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR);
                                }
                                $total_revenue_stmt->execute();
                                echo number_format($total_revenue_stmt->fetchColumn() ?: 0, 0); // Display as integer, or with decimals if needed
                            } catch (PDOException $e) {
                                echo "0"; // Display 0 on error
                                error_log("Error calculating main revenue stat: " . $e->getMessage());
                            }
                            ?>
                        </h3>
                        <p class="text-gray-600">Revenue (Filtered Delivered)</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Main Content Area -->
<div class="container mx-auto px-6 py-8">
    <!-- Feedback Alerts -->
    <?php if ($error_message): ?>
        <div class="mb-6 p-4 bg-red-50 border-l-4 border-red-400 text-red-700 rounded-lg flex items-center">
            <i class="fas fa-exclamation-circle mr-3"></i>
            <div>
                <p class="font-semibold">Error</p>
                <p><?php echo htmlspecialchars($error_message); ?></p>
            </div>
            <button type="button" onclick="this.parentElement.remove()" class="ml-auto text-gray-400 hover:text-gray-600">
                <i class="fas fa-times"></i>
            </button>
        </div>
    <?php endif; ?>

    <?php if ($success_message): ?>
        <div class="mb-6 p-4 bg-green-50 border-l-4 border-green-400 text-green-700 rounded-lg flex items-center">
            <i class="fas fa-check-circle mr-3"></i>
            <div>
                <p class="font-semibold">Success</p>
                <p><?php echo htmlspecialchars($success_message); ?></p>
            </div>
            <button type="button" onclick="this.parentElement.remove()" class="ml-auto text-gray-400 hover:text-gray-600">
                <i class="fas fa-times"></i>
            </button>
        </div>
    <?php endif; ?>

    <!-- Filter & Search Section -->
    <div class="bg-white rounded-xl shadow-lg p-6 mb-8">
        <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
            <!-- Search Input -->
            <div class="lg:col-span-2">
                <label for="searchInput" class="block text-sm font-semibold text-gray-700 mb-2">Search Orders</label>
                <div class="relative">
                    <input type="text" id="searchInput" value="<?php echo htmlspecialchars($search); ?>"
                           placeholder="Order #, Customer Name, Email..."
                           class="w-full pl-12 pr-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-red-500 transition-colors">
                    <i class="fas fa-search absolute left-4 top-1/2 transform -translate-y-1/2 text-gray-400"></i>
                </div>
            </div>

            <!-- Date From Filter -->
            <div>
                <label for="dateFromFilter" class="block text-sm font-semibold text-gray-700 mb-2">From Date</label>
                <input type="date" id="dateFromFilter" value="<?php echo htmlspecialchars($date_from); ?>"
                       class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-red-500 transition-colors">
            </div>

            <!-- Date To Filter -->
            <div>
                <label for="dateToFilter" class="block text-sm font-semibold text-gray-700 mb-2">To Date</label>
                <input type="date" id="dateToFilter" value="<?php echo htmlspecialchars($date_to); ?>"
                       class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-red-500 transition-colors">
            </div>
        </div>

        <!-- Quick Filter Tabs for Status -->
        <div class="mt-6 pt-6 border-t border-gray-200">
            <div class="flex flex-wrap gap-2">
                <button type="button" class="filter-tab <?php echo (empty($status_filter) || $status_filter === 'all') ? 'active' : ''; ?>" data-filter="all">
                    All Orders <span class="ml-1 bg-gray-100 text-gray-600 px-2 py-1 rounded-full text-xs"><?php echo number_format($total_records); ?></span>
                </button>
                <?php foreach ($all_status_options as $key): ?>
                    <button type="button" class="filter-tab <?php echo ($status_filter === $key) ? 'active' : ''; ?>" data-filter="<?php echo $key; ?>">
                        <?php echo ucfirst($key); ?> <span class="ml-1 <?php echo htmlspecialchars($status_colors[$key] ?? 'bg-gray-100 text-gray-600'); ?> px-2 py-1 rounded-full text-xs"><?php echo number_format($status_counts[$key] ?? 0); ?></span>
                    </button>
                <?php endforeach; ?>
                <?php if (!empty($search) || !empty($date_from) || !empty($date_to) || (!empty($status_filter) && $status_filter !== 'all')): ?>
                    <button type="button" onclick="clearFilterInputs()" class="ml-4 text-red-600 hover:text-red-700 font-medium">
                        <i class="fas fa-times mr-1"></i>Clear Filters
                    </button>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Order Cards Display -->
    <?php if (count($orders) > 0): ?>
        <?php foreach ($orders as $order): ?>
        <div class="bg-white rounded-xl shadow-lg border border-gray-200 hover:shadow-xl transition-all duration-300 overflow-hidden mb-6 order-card">

            <!-- Card Header with Quick Actions -->
            <div class="bg-gradient-to-r from-red-50 to-red-100 px-6 py-4 border-b border-red-200">
                <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between">
                    <!-- Order Info -->
                    <div class="flex items-center space-x-4">
                        <div class="w-14 h-14 bg-red-600 rounded-full flex items-center justify-center shadow-lg">
                            <i class="fas fa-shopping-cart text-white text-2xl"></i>
                        </div>
                        <div>
                            <h3 class="text-2xl font-bold text-gray-900 mb-1">
                                #<?= htmlspecialchars($order['order_number']) ?>
                            </h3>
                            <p class="text-sm text-gray-600 font-medium">
                                <?= date('M j, Y \a\t g:i A', strtotime($order['created_at'])) ?>
                            </p>
                            <div class="flex items-center space-x-2 mt-2">
                                <?php
                                $status_icons_map = [ // Define a map for status icons
                                    'pending' => 'clock',
                                    'processing' => 'cog',
                                    'shipped' => 'shipping-fast',
                                    'delivered' => 'truck',
                                    'cancelled' => 'times-circle'
                                ];
                                ?>
                                <span class="px-3 py-1 rounded-full text-sm font-semibold <?= $status_colors[$order['status']] ?? 'bg-gray-200 text-gray-800'; ?>">
                                    <i class="fas fa-<?= $status_icons_map[$order['status']] ?? 'info-circle'; ?> mr-1 text-xs"></i>
                                    <?php echo ucfirst($order['status']); ?>
                                </span>
                                <span class="text-lg font-bold text-red-600">
                                    KES <?= number_format($order['total_amount'] ?? 0, 2) ?>
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Quick CRUD Action Buttons -->
                    <div class="mt-4 lg:mt-0 flex flex-wrap gap-2">
                        <a href="order_view.php?id=<?= $order['id'] ?>"
                           class="group relative bg-gradient-to-r from-blue-500 to-blue-600 hover:from-blue-600 hover:to-blue-700 text-white px-4 py-2 rounded-xl font-semibold transition-all duration-300 flex items-center space-x-2 shadow-lg hover:shadow-xl transform hover:-translate-y-0.5 border border-blue-400/20">
                            <div class="absolute inset-0 bg-gradient-to-r from-white/0 via-white/10 to-white/0 opacity-0 group-hover:opacity-100 transition-opacity duration-300 rounded-xl"></div>
                            <i class="fas fa-eye relative z-10"></i>
                            <span class="relative z-10 font-medium">View</span>
                        </a>

                        <?php if (in_array($order['status'], ['pending', 'processing'])): ?>
                            <button type="button" onclick="updateOrderStatus(<?php echo $order['id']; ?>, 'processing')"
                               class="group relative bg-gradient-to-r from-teal-500 to-teal-600 hover:from-teal-600 hover:to-teal-700 text-white px-4 py-2 rounded-xl font-semibold transition-all duration-300 flex items-center space-x-2 shadow-lg hover:shadow-xl transform hover:-translate-y-0.5 border border-teal-400/20">
                                <div class="absolute inset-0 bg-gradient-to-r from-white/0 via-white/10 to-white/0 opacity-0 group-hover:opacity-100 transition-opacity duration-300 rounded-xl"></div>
                                <i class="fas fa-play-circle relative z-10"></i>
                                <span class="relative z-10 font-medium">Process</span>
                            </button>
                        <?php endif; ?>

                        <?php if (in_array($order['status'], ['processing'])): ?>
                            <button type="button" onclick="updateOrderStatus(<?php echo $order['id']; ?>, 'shipped')"
                               class="group relative bg-gradient-to-r from-indigo-500 to-indigo-600 hover:from-indigo-600 hover:to-indigo-700 text-white px-4 py-2 rounded-xl font-semibold transition-all duration-300 flex items-center space-x-2 shadow-lg hover:shadow-xl transform hover:-translate-y-0.5 border border-indigo-400/20">
                                <div class="absolute inset-0 bg-gradient-to-r from-white/0 via-white/10 to-white/0 opacity-0 group-hover:opacity-100 transition-opacity duration-300 rounded-xl"></div>
                                <i class="fas fa-shipping-fast relative z-10"></i>
                                <span class="relative z-10 font-medium">Ship</span>
                            </button>
                        <?php endif; ?>

                        <?php if (in_array($order['status'], ['shipped'])): ?>
                            <button type="button" onclick="updateOrderStatus(<?php echo $order['id']; ?>, 'delivered')"
                               class="group relative bg-gradient-to-r from-green-600 to-green-700 hover:from-green-700 hover:to-green-800 text-white px-4 py-2 rounded-xl font-semibold transition-all duration-300 flex items-center space-x-2 shadow-lg hover:shadow-xl transform hover:-translate-y-0.5 border border-green-400/20">
                                <div class="absolute inset-0 bg-gradient-to-r from-white/0 via-white/10 to-white/0 opacity-0 group-hover:opacity-100 transition-opacity duration-300 rounded-xl"></div>
                                <i class="fas fa-check relative z-10"></i>
                                <span class="relative z-10 font-medium">Deliver</span>
                            </button>
                        <?php endif; ?>

                        <button type="button" onclick="editOrder(<?php echo htmlspecialchars(json_encode($order)); ?>, '<?php echo htmlspecialchars($order['customer_name'] ?? ''); ?>', '<?php echo htmlspecialchars($order['customer_email'] ?? ''); ?>')"
                           class="group relative bg-gradient-to-r from-purple-500 to-purple-600 hover:from-purple-600 hover:to-purple-700 text-white px-4 py-2 rounded-xl font-semibold transition-all duration-300 flex items-center space-x-2 shadow-lg hover:shadow-xl transform hover:-translate-y-0.5 border border-purple-400/20">
                            <div class="absolute inset-0 bg-gradient-to-r from-white/0 via-white/10 to-white/0 opacity-0 group-hover:opacity-100 transition-opacity duration-300 rounded-xl"></div>
                            <i class="fas fa-edit relative z-10"></i>
                            <span class="relative z-10 font-medium">Edit</span>
                        </button>

                        <button type="button" onclick="confirmDelete(<?= $order['id'] ?>, '<?= htmlspecialchars($order['order_number']) ?>')"
                                class="group relative bg-gradient-to-r from-red-600 to-red-700 hover:from-red-700 hover:to-red-800 text-white px-4 py-2 rounded-xl font-semibold transition-all duration-300 flex items-center space-x-2 shadow-lg hover:shadow-xl transform hover:-translate-y-0.5 border border-red-400/20">
                            <div class="absolute inset-0 bg-gradient-to-r from-white/0 via-white/10 to-white/0 opacity-0 group-hover:opacity-100 transition-opacity duration-300 rounded-xl"></div>
                            <i class="fas fa-trash relative z-10"></i>
                            <span class="relative z-10 font-medium">Delete</span>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Order Details Grid -->
            <div class="p-6">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <!-- Customer Information -->
                    <div class="bg-blue-50 rounded-lg p-4 border-l-4 border-blue-400">
                        <div class="flex items-center mb-3">
                            <div class="w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center mr-3">
                                <i class="fas fa-user text-blue-600 text-sm"></i>
                            </div>
                            <h4 class="font-semibold text-gray-900">Customer Details</h4>
                        </div>
                        <div class="space-y-2">
                            <p class="text-gray-700"><span class="font-medium">Name:</span> <?php echo htmlspecialchars($order['customer_name'] ?? 'N/A'); ?></p>
                            <p class="text-gray-700"><span class="font-medium">Email:</span> <?php echo htmlspecialchars($order['customer_email'] ?? 'N/A'); ?></p>
                            <p class="text-gray-700"><span class="font-medium">Phone:</span> <?php echo htmlspecialchars($order['customer_phone'] ?? 'N/A'); ?></p>
                        </div>
                    </div>

                    <!-- Order Information -->
                    <div class="bg-green-50 rounded-lg p-4 border-l-4 border-green-400">
                        <div class="flex items-center mb-3">
                            <div class="w-8 h-8 bg-green-100 rounded-full flex items-center justify-center mr-3">
                                <i class="fas fa-box text-green-600 text-sm"></i>
                            </div>
                            <h4 class="font-semibold text-gray-900">Order Details</h4>
                        </div>
                        <div class="space-y-2">
                            <p class="text-gray-700"><span class="font-medium">Items:</span> <?php echo $order['item_count']; ?> items</p>
                            <p class="text-gray-700"><span class="font-medium">Summary:</span> <?php echo htmlspecialchars(substr($order['item_names_summary'] ?: 'No items', 0, 50) . (strlen($order['item_names_summary'] ?: '') > 50 ? '...' : '')); ?></p>
                            <p class="text-gray-700"><span class="font-medium">Payment:</span> <?php echo ucfirst(str_replace('_', ' ', $order['payment_method'] ?? 'N/A')); ?> (<?php echo isset($order['payment_status']) ? ucfirst($order['payment_status']) : 'Unknown'; ?>)</p>
                        </div>
                    </div>

                    <!-- Delivery Information -->
                    <div class="bg-purple-50 rounded-lg p-4 border-l-4 border-purple-400">
                        <div class="flex items-center mb-3">
                            <div class="w-8 h-8 bg-purple-100 rounded-full flex items-center justify-center mr-3">
                                <i class="fas fa-map-marker-alt text-purple-600 text-sm"></i>
                            </div>
                            <h4 class="font-semibold text-gray-900">Delivery Info</h4>
                        </div>
                        <div class="space-y-2">
                            <p class="text-gray-700 text-wrap"><span class="font-medium">Address:</span> <?php echo htmlspecialchars($order['delivery_address']); ?></p>
                            <?php if (!empty($order['delivery_instructions'])): ?>
                            <p class="text-gray-700 text-wrap"><span class="font-medium">Instructions:</span> <?php echo htmlspecialchars($order['delivery_instructions']); ?></p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Financial Summary -->
                <div class="mt-6 bg-gray-50 rounded-lg p-4 border-t border-gray-200">
                    <div class="grid grid-cols-3 gap-4 text-center">
                        <div>
                            <p class="text-sm text-gray-600">Subtotal</p>
                            <p class="text-lg font-semibold text-gray-900">KES <?php echo number_format($order['subtotal'], 2); ?></p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-600">Delivery Fee</p>
                            <p class="text-lg font-semibold <?php echo ($order['delivery_fee'] ?? 0.0) === 0.0 ? 'text-green-600' : 'text-gray-900'; ?>">
                                <?php echo ($order['delivery_fee'] ?? 0.0) === 0.0 ? 'Free' : 'KES ' . number_format($order['delivery_fee'], 2); ?>
                            </p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-600">Total</p>
                            <p class="text-xl font-bold text-red-600">KES <?php echo number_format($order['total_amount'] ?? 0, 2); ?></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    <?php else: ?>
        <!-- No Orders State Display -->
        <div class="bg-white rounded-xl shadow-lg p-12 text-center">
            <div class="max-w-md mx-auto">
                <i class="fas fa-shopping-cart text-6xl text-gray-300 mb-6"></i>
                <h2 class="text-2xl font-bold text-gray-700 mb-4">No Orders Found</h2>
                <p class="text-gray-600 mb-8">
                    <?php if (!empty($search) || !empty($date_from) || !empty($date_to) || (!empty($status_filter) && $status_filter !== 'all')): ?>
                        No orders match your current filters. Try adjusting your search criteria.
                    <?php else: ?>
                        No orders have been placed yet. Orders will appear here once customers start ordering.
                    <?php endif; ?>
                </p>
                <?php if (!empty($search) || !empty($date_from) || !empty($date_to) || (!empty($status_filter) && $status_filter !== 'all')): ?>
                    <button type="button" onclick="clearFilterInputs()" class="inline-flex items-center justify-center px-6 py-3 bg-red-600 text-white font-bold rounded-lg hover:bg-red-700 transition-colors">
                        <i class="fas fa-times mr-2"></i>
                        Clear Filters
                    </button>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>
</div>

<!-- Pagination Section -->
<?php if ($total_pages > 1): ?>
<div class="mt-8 flex justify-center">
    <nav class="flex items-center space-x-2">
        <?php
            // Helper function for building pagination URLs
            function generatePaginationUrl($page_num, $current_status_filter, $current_date_from, $current_date_to, $current_search) {
                $url_params = [];
                if ($page_num > 1) $url_params['page'] = $page_num;
                if (!empty($current_status_filter) && $current_status_filter !== 'all') $url_params['status'] = urlencode($current_status_filter);
                if (!empty($current_date_from)) $url_params['date_from'] = urlencode($current_date_from);
                if (!empty($current_date_to)) $url_params['date_to'] = urlencode($current_date_to);
                if (!empty($current_search)) $url_params['search'] = urlencode($current_search);
                return '?' . http_build_query($url_params);
            }
        ?>
        <?php if ($page > 1): ?>
        <a href="<?php echo generatePaginationUrl($page - 1, $status_filter, $date_from, $date_to, $search); ?>"
           class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors font-medium">
            <i class="fas fa-chevron-left mr-1"></i> Previous
        </a>
        <?php endif; ?>

        <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
        <a href="<?php echo generatePaginationUrl($i, $status_filter, $date_from, $date_to, $search); ?>"
           class="px-4 py-2 border rounded-lg transition-colors font-medium <?php echo $i === $page ? 'bg-red-600 text-white border-red-600' : 'border-gray-300 hover:bg-gray-50'; ?>">
            <?php echo $i; ?>
        </a>
        <?php endfor; ?>

        <?php if ($page < $total_pages): ?>
        <a href="<?php echo generatePaginationUrl($page + 1, $status_filter, $date_from, $date_to, $search); ?>"
           class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors font-medium">
            Next <i class="fas fa-chevron-right ml-1"></i>
        </a>
        <?php endif; ?>
    </nav>
</div>
<?php endif; ?>

<!-- ============================================== MODALS ============================================== -->

<!-- Delete Confirmation Modal -->
<div id="deleteModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4 animate__animated animate__fadeIn">
    <div class="bg-white rounded-2xl shadow-2xl max-w-md w-full max-h-[90vh] overflow-y-auto animate__animated animate__zoomIn">
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
            <p class="text-lg font-medium mb-3">Are you sure you want to delete order <span id="deleteOrderNumber" class="font-bold text-red-600"></span>?</p>
            <p>This action cannot be undone. All associated data, including order items, will be permanently removed.</p>
        </div>

        <!-- Modal Footer -->
        <div class="p-6 border-t border-gray-200 flex gap-3 justify-end">
                <button type="button" onclick="document.getElementById('deleteModal').classList.add('hidden'); document.getElementById('deleteModal').classList.remove('animate__fadeIn', 'animate__zoomIn');"
                        class="group relative bg-gray-500 hover:bg-gray-600 text-white px-6 py-3 rounded-xl font-semibold transition-all duration-300 flex items-center justify-center shadow-lg hover:shadow-xl transform hover:-translate-y-0.5">
                    <div class="absolute inset-0 bg-gradient-to-r from-white/0 via-white/10 to-white/0 opacity-0 group-hover:opacity-100 transition-opacity duration-300 rounded-xl"></div>
                    <span class="relative z-10 font-medium">Cancel</span>
                </button>
            <form action="" method="POST" class="inline-block" id="deleteOrderForm">
                <input type="hidden" name="_method" value="DELETE">
                <input type="hidden" name="id" id="deleteOrderId">
                <button type="submit"
                        class="group relative bg-red-600 hover:bg-red-700 text-white px-6 py-3 rounded-xl font-semibold transition-all duration-300 flex items-center justify-center shadow-lg hover:shadow-xl transform hover:-translate-y-0.5">
                    <div class="absolute inset-0 bg-gradient-to-r from-white/0 via-white/10 to-white/0 opacity-0 group-hover:opacity-100 transition-opacity duration-300 rounded-xl"></div>
                    <i class="fas fa-trash mr-2 relative z-10"></i>
                    <span class="relative z-10 font-medium">Delete Order</span>
                </button>
            </form>
        </div>
    </div>
</div>

<!-- Edit Order Modal -->
<div id="editOrderModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4 animate__animated animate__fadeIn">
    <div class="bg-white rounded-2xl shadow-2xl max-w-2xl w-full max-h-[90vh] overflow-y-auto animate__animated animate__zoomIn">
        <!-- Modal Header -->
        <div class="bg-gradient-to-r from-primary via-primary-dark to-secondary p-6 text-white rounded-t-2xl">
            <div class="flex items-center justify-between">
                <h3 class="text-xl font-bold">
                    <i class="fas fa-pencil-alt mr-2"></i>Edit Order <span id="editOrderNumber" class="font-mono text-purple-100 italic"></span>
                </h3>
                <button type="button" onclick="document.getElementById('editOrderModal').classList.add('hidden'); document.getElementById('editOrderModal').classList.remove('animate__fadeIn', 'animate__zoomIn'); document.getElementById('editOrderForm')?.reset();" class="text-white hover:text-gray-200 text-2xl">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <p class="text-sm font-medium opacity-90">Customer: <span id="editCustomerName"></span> (<span id="editCustomerEmail"></span>)</p>
        </div>

        <!-- Modal Body -->
        <div class="p-6">
            <form id="editOrderForm" action="" method="POST" class="space-y-6">
                <input type="hidden" name="order_id" id="edit_order_id">
                <input type="hidden" name="update_order" value="1">

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label for="edit_status" class="block text-sm font-semibold text-gray-700 mb-2">Order Status</label>
                        <select id="edit_status" name="status" required
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-600 focus:border-transparent transition-colors">
                            <option value="pending">Pending</option>
                            <option value="processing">Processing</option>
                            <option value="shipped">Shipped</option>
                            <option value="delivered">Delivered</option>
                            <option value="cancelled">Cancelled</option>
                        </select>
                    </div>
                    <div>
                        <label for="edit_payment_status" class="block text-sm font-semibold text-gray-700 mb-2">Payment Status</label>
                        <select id="edit_payment_status" name="payment_status" required
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-600 focus:border-transparent transition-colors">
                            <option value="pending">Pending</option>
                            <option value="confirmed">Confirmed</option>
                            <option value="failed">Failed</option>
                        </select>
                    </div>
                </div>

                <div>
                    <label for="edit_delivery_address" class="block text-sm font-semibold text-gray-700 mb-2">Delivery Address <span class="text-red-500">*</span></label>
                    <textarea name="delivery_address" id="edit_delivery_address" rows="3" required
                              class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-600 focus:border-transparent transition-colors"
                              placeholder="Enter full delivery address"></textarea>
                </div>

                <div>
                    <label for="edit_delivery_instructions" class="block text-sm font-semibold text-gray-700 mb-2">Delivery Instructions</label>
                    <textarea name="delivery_instructions" id="edit_delivery_instructions" rows="2"
                              class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-600 focus:border-transparent transition-colors"
                              placeholder="e.g., Leave package at front door, call on arrival"></textarea>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label for="edit_subtotal" class="block text-sm font-semibold text-gray-700 mb-2">Subtotal (KES) <span class="text-red-500">*</span></label>
                        <input type="number" step="0.01" min="0" name="subtotal" id="edit_subtotal" required
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-600 focus:border-transparent transition-colors"
                               placeholder="0.00">
                    </div>
                    <div>
                        <label for="edit_delivery_fee" class="block text-sm font-semibold text-gray-700 mb-2">Delivery Fee (KES) <span class="text-red-500">*</span></label>
                        <input type="number" step="0.01" min="0" name="delivery_fee" id="edit_delivery_fee" required
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-600 focus:border-transparent transition-colors"
                               placeholder="0.00">
                    </div>
                     <div>
                        <label for="edit_total_amount" class="block text-sm font-semibold text-gray-700 mb-2">Total Amount (KES)</label>
                        <input type="number" step="0.01" min="0" name="total" id="edit_total_amount" readonly
                               class="w-full px-4 py-3 border bg-gray-100 border-gray-300 rounded-lg cursor-not-allowed"
                               placeholder="0.00">
                        <p class="text-xs text-gray-500 mt-1">Automatically calculated</p>
                    </div>
                </div>
            </form>
        </div>

        <!-- Modal Footer -->
        <div class="p-6 border-t border-gray-200 flex gap-3 justify-end">
                <button type="button" onclick="document.getElementById('editOrderModal').classList.add('hidden'); document.getElementById('editOrderModal').classList.remove('animate__fadeIn', 'animate__zoomIn'); document.getElementById('editOrderForm')?.reset();"
                        class="group relative bg-gray-500 hover:bg-gray-600 text-white px-6 py-3 rounded-xl font-semibold transition-all duration-300 flex items-center justify-center shadow-lg hover:shadow-xl transform hover:-translate-y-0.5">
                    <div class="absolute inset-0 bg-gradient-to-r from-white/0 via-white/10 to-white/0 opacity-0 group-hover:opacity-100 transition-opacity duration-300 rounded-xl"></div>
                    <span class="relative z-10 font-medium">Cancel</span>
                </button>
             <button type="submit" form="editOrderForm"
                    class="group relative bg-purple-600 hover:bg-purple-700 text-white px-6 py-3 rounded-xl font-semibold transition-all duration-300 flex items-center justify-center shadow-lg hover:shadow-xl transform hover:-translate-y-0.5">
                <div class="absolute inset-0 bg-gradient-to-r from-white/0 via-white/10 to-white/0 opacity-0 group-hover:opacity-100 transition-opacity duration-300 rounded-xl"></div>
                <i class="fas fa-save mr-2 relative z-10"></i>
                <span class="relative z-10 font-medium">Save Changes</span>
            </button>
        </div>
    </div>
</div>

<!-- Add Order Modal -->
<div id="addOrderModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4 animate__animated animate__fadeIn">
    <div class="bg-white rounded-2xl shadow-2xl max-w-2xl w-full max-h-[90vh] overflow-y-auto animate__animated animate__zoomIn">
        <!-- Modal Header -->
        <div class="bg-gradient-to-r from-primary via-primary-dark to-secondary p-6 text-white rounded-t-2xl">
            <div class="flex items-center justify-between">
                <h3 class="text-xl font-bold">
                    <i class="fas fa-cart-plus mr-2"></i>Add New Order
                </h3>
                <button type="button" onclick="document.getElementById('addOrderModal').classList.add('hidden'); document.getElementById('addOrderModal').classList.remove('animate__fadeIn', 'animate__zoomIn'); document.getElementById('addOrderForm')?.reset();" class="text-white hover:text-gray-200 text-2xl">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <p class="text-sm font-medium opacity-90">Create a new order for an existing customer.</p>
        </div>

        <!-- Modal Body -->
        <div class="p-6">
            <form id="addOrderForm" action="" method="POST" class="space-y-6">
                <input type="hidden" name="add_order" value="1">

                <div>
                    <label for="add_user_id" class="block text-sm font-semibold text-gray-700 mb-2">Customer <span class="text-red-500">*</span></label>
                    <select id="add_user_id" name="user_id" required
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-600 focus:border-transparent transition-colors">
                        <option value="">Select a customer</option>
                        <?php foreach($customer_list as $customer): ?>
                            <option value="<?php echo htmlspecialchars($customer['id']); ?>">
                                <?php echo htmlspecialchars($customer['name']); ?> (<?php echo htmlspecialchars($customer['email']); ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label for="add_status" class="block text-sm font-semibold text-gray-700 mb-2">Order Status</label>
                        <select id="add_status" name="status" required
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-600 focus:border-transparent transition-colors">
                            <option value="pending">Pending</option>
                            <option value="processing">Processing</option>
                            <option value="shipped">Shipped</option>
                            <option value="delivered">Delivered</option>
                            <option value="cancelled">Cancelled</option>
                        </select>
                    </div>
                    <div>
                        <label for="add_payment_method" class="block text-sm font-semibold text-gray-700 mb-2">Payment Method <span class="text-red-500">*</span></label>
                        <select id="add_payment_method" name="payment_method" required
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-600 focus:border-transparent transition-colors">
                            <option value="M-Pesa">M-Pesa</option>
                            <option value="card">Card</option>
                            <option value="cash">Cash on Delivery</option>
                        </select>
                    </div>
                    <div>
                        <label for="add_payment_status" class="block text-sm font-semibold text-gray-700 mb-2">Payment Status</label>
                        <select id="add_payment_status" name="payment_status" required
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-600 focus:border-transparent transition-colors">
                            <option value="pending">Pending</option>
                            <option value="confirmed">Confirmed</option>
                            <option value="failed">Failed</option>
                        </select>
                    </div>
                </div>

                 <div>
                    <label for="add_delivery_address" class="block text-sm font-semibold text-gray-700 mb-2">Delivery Address <span class="text-red-500">*</span></label>
                    <textarea name="delivery_address" id="add_delivery_address" rows="3" required
                              class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-600 focus:border-transparent transition-colors"
                              placeholder="Enter full delivery address"></textarea>
                </div>

                <div>
                    <label for="add_delivery_instructions" class="block text-sm font-semibold text-gray-700 mb-2">Delivery Instructions</label>
                    <textarea name="delivery_instructions" id="add_delivery_instructions" rows="2"
                              class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-600 focus:border-transparent transition-colors"
                              placeholder="e.g., Leave package at front door, call on arrival"></textarea>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label for="add_subtotal" class="block text-sm font-semibold text-gray-700 mb-2">Subtotal (KES) <span class="text-red-500">*</span></label>
                        <input type="number" step="0.01" min="0" name="subtotal" id="add_subtotal" value="0.00" required
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-600 focus:border-transparent transition-colors"
                               placeholder="0.00">
                    </div>
                    <div>
                        <label for="add_delivery_fee" class="block text-sm font-semibold text-gray-700 mb-2">Delivery Fee (KES) <span class="text-red-500">*</span></label>
                        <input type="number" step="0.01" min="0" name="delivery_fee" id="add_delivery_fee" value="0.00" required
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-600 focus:border-transparent transition-colors"
                               placeholder="0.00">
                    </div>
                     <div>
                        <label for="add_total_amount" class="block text-sm font-semibold text-gray-700 mb-2">Total Amount (KES)</label>
                        <input type="number" step="0.01" min="0" name="total" id="add_total_amount" readonly
                               class="w-full px-4 py-3 border bg-gray-100 border-gray-300 rounded-lg cursor-not-allowed"
                               placeholder="0.00">
                        <p class="text-xs text-gray-500 mt-1">Automatically calculated</p>
                    </div>
                </div>
                <p class="text-sm text-gray-600">Note: Order items cannot be added from this modal. Please edit the order after creation to add items.</p>
            </form>
        </div>

        <!-- Modal Footer -->
        <div class="p-6 border-t border-gray-200 flex gap-3 justify-end">
            <button type="button" onclick="document.getElementById('addOrderModal').classList.add('hidden'); document.getElementById('addOrderModal').classList.remove('animate__fadeIn', 'animate__zoomIn'); document.getElementById('addOrderForm')?.reset();"
                    class="group relative bg-gray-500 hover:bg-gray-600 text-white px-6 py-3 rounded-xl font-semibold transition-all duration-300 flex items-center justify-center shadow-lg hover:shadow-xl transform hover:-translate-y-0.5">
                <div class="absolute inset-0 bg-gradient-to-r from-white/0 via-white/10 to-white/0 opacity-0 group-hover:opacity-100 transition-opacity duration-300 rounded-xl"></div>
                <span class="relative z-10 font-medium">Cancel</span>
            </button>
             <button type="submit" form="addOrderForm"
                    class="group relative bg-green-600 hover:bg-green-700 text-white px-6 py-3 rounded-xl font-semibold transition-all duration-300 flex items-center justify-center shadow-lg hover:shadow-xl transform hover:-translate-y-0.5">
                <div class="absolute inset-0 bg-gradient-to-r from-white/0 via-white/10 to-white/0 opacity-0 group-hover:opacity-100 transition-opacity duration-300 rounded-xl"></div>
                <i class="fas fa-plus mr-2 relative z-10"></i>
                <span class="relative z-10 font-medium">Create Order</span>
            </button>
        </div>
    </div>
</div>

<!-- ============================================== JAVASCRIPT ============================================== -->
<script>
// IMPORTANT: SweetAlert2 is already loaded in includes/header.php
// No need to uncomment or add it here as it's included globally



// Close modal when clicking outside (on the black overlay)
window.addEventListener('click', function(event) {
    const modalIds = ['deleteModal', 'editOrderModal', 'addOrderModal'];
    modalIds.forEach(id => {
        const modal = document.getElementById(id);
        if (modal && !modal.classList.contains('hidden') && event.target === modal) {
            modal.classList.add('hidden');
            modal.classList.remove('animate__fadeIn', 'animate__zoomIn');
        }
    });
});

// Close modal when pressing ESC key
document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape') {
        const modalIds = ['deleteModal', 'editOrderModal', 'addOrderModal'];
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

// Calculate total amount in modals based on subtotal and delivery fee
function calculateTotal(prefix) {
    const subtotalInput = document.getElementById(prefix + '_subtotal');
    const deliveryFeeInput = document.getElementById(prefix + '_delivery_fee');
    const totalAmountInput = document.getElementById(prefix + '_total_amount');

    const subtotal = parseFloat(subtotalInput?.value || '0');
    const deliveryFee = parseFloat(deliveryFeeInput?.value || '0');

    if (totalAmountInput) {
        totalAmountInput.value = (subtotal + deliveryFee).toFixed(2);
    }
}

// Function to handle filter tab clicks
const filterTabs = document.querySelectorAll('.filter-tab[data-filter]');
filterTabs.forEach(tab => {
    tab.addEventListener('click', function() {
        const filterValue = this.dataset.filter;
        let url = new URL(window.location.origin + window.location.pathname);

        // Preserve existing search and date filters
        const currentSearch = document.getElementById('searchInput')?.value;
        const currentFromDate = document.getElementById('dateFromFilter')?.value;
        const currentToDate = document.getElementById('dateToFilter')?.value;

        if (currentSearch) url.searchParams.set('search', currentSearch);
        if (currentFromDate) url.searchParams.set('date_from', currentFromDate);
        if (currentToDate) url.searchParams.set('date_to', currentToDate);

        // Apply the new status filter or remove it if 'all' is selected
        if (filterValue && filterValue !== 'all') {
            url.searchParams.set('status', filterValue);
        } else {
            url.searchParams.delete('status');
        }

        url.searchParams.delete('page'); // Always reset to page 1 when filtering
        window.location.href = url.toString();
    });
});


document.addEventListener('DOMContentLoaded', function() {
    // Event listeners for total amount calculation in modals
    document.getElementById('edit_subtotal')?.addEventListener('input', () => calculateTotal('edit'));
    document.getElementById('edit_delivery_fee')?.addEventListener('input', () => calculateTotal('edit'));
    document.getElementById('add_subtotal')?.addEventListener('input', () => calculateTotal('add'));
    document.getElementById('add_delivery_fee')?.addEventListener('input', () => calculateTotal('add'));

    // Event listeners for filter inputs (search, dates) with debouncing for search
    const searchInput = document.getElementById('searchInput');
    const dateFromFilter = document.getElementById('dateFromFilter');
    const dateToFilter = document.getElementById('dateToFilter');

    let searchTimeout;
    const applyFilters = () => {
        let url = new URL(window.location.origin + window.location.pathname);
        if (searchInput?.value) url.searchParams.set('search', searchInput.value);
        if (dateFromFilter?.value) url.searchParams.set('date_from', dateFromFilter.value);
        if (dateToFilter?.value) url.searchParams.set('date_to', dateToFilter.value);

        // Preserve current active status filter from tabs
        const activeStatusTab = document.querySelector('.filter-tab.active');
        if (activeStatusTab && activeStatusTab.dataset.filter && activeStatusTab.dataset.filter !== 'all') {
            url.searchParams.set('status', activeStatusTab.dataset.filter);
        }

        url.searchParams.delete('page'); // Reset page to 1
        window.location.href = url.toString();
    };

    searchInput?.addEventListener('input', () => {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(applyFilters, 500); // Debounce search for 500ms
    });
    dateFromFilter?.addEventListener('change', applyFilters);
    dateToFilter?.addEventListener('change', applyFilters);
});

// Function to reset all filter inputs and reload the page
function clearFilterInputs() {
    window.location.href = 'orders.php'; // Simply reload to clear all GET parameters
}

// Function to send invoice email to customer
function sendInvoice(orderId, customerEmail) {
    if (!customerEmail || customerEmail === '') {
        Swal.fire('Error', 'Customer email not available for this order.', 'error');
        return;
    }

    Swal.fire({
        title: 'Send Invoice?',
        text: `Are you sure you want to send an invoice email to ${customerEmail}?`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#10b981',
        cancelButtonColor: '#6b7280',
        confirmButtonText: 'Yes, send invoice!'
    }).then((result) => {
        if (result.isConfirmed) {
            // Show loading state
            const button = event.target.closest('button');
            const originalHTML = button.innerHTML;
            button.innerHTML = '<i class="fas fa-spinner fa-spin relative z-10"></i><span class="relative z-10 font-medium">Sending...</span>';
            button.disabled = true;

            // Send email via AJAX to send_email.php
            fetch('send_email.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    'action': 'send_invoice',
                    'order_id': orderId,
                    'customer_email': customerEmail
                })
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('HTTP error! status: ' + response.status);
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    Swal.fire('Success!', 'Invoice sent successfully to customer!', 'success');
                } else {
                    Swal.fire('Error', 'Error sending invoice: ' + (data.message || 'Unknown error'), 'error');
                }
            })
            .catch(error => {
                console.error('Email sending error:', error);
                Swal.fire('Error', 'Error sending invoice: ' + error.message, 'error');
            })
            .finally(() => {
                // Restore button state
                button.innerHTML = originalHTML;
                button.disabled = false;
            });
        }
    });
}

// Function to send receipt email to customer
function sendReceipt(orderId, customerEmail) {
    if (!customerEmail || customerEmail === '') {
        Swal.fire('Error', 'Customer email not available for this order.', 'error');
        return;
    }

    Swal.fire({
        title: 'Send Receipt?',
        text: `Are you sure you want to send a receipt email to ${customerEmail}?`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#06b6d4',
        cancelButtonColor: '#6b7280',
        confirmButtonText: 'Yes, send receipt!'
    }).then((result) => {
        if (result.isConfirmed) {
            // Show loading state
            const button = event.target.closest('button');
            const originalHTML = button.innerHTML;
            button.innerHTML = '<i class="fas fa-spinner fa-spin relative z-10"></i><span class="relative z-10 font-medium">Sending...</span>';
            button.disabled = true;

            // Send email via AJAX to send_email.php
            fetch('send_email.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    'action': 'send_receipt',
                    'order_id': orderId,
                    'customer_email': customerEmail
                })
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('HTTP error! status: ' + response.status);
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    Swal.fire('Success!', 'Receipt sent successfully to customer!', 'success');
                } else {
                    Swal.fire('Error', 'Error sending receipt: ' + (data.message || 'Unknown error'), 'error');
                }
            })
            .catch(error => {
                console.error('Email sending error:', error);
                Swal.fire('Error', 'Error sending receipt: ' + error.message, 'error');
            })
            .finally(() => {
                // Restore button state
                button.innerHTML = originalHTML;
                button.disabled = false;
            });
        }
    });
}

// Populates and displays the delete confirmation modal
function confirmDelete(orderId, orderNumber) {
    document.getElementById('deleteOrderNumber').textContent = orderNumber;
    document.getElementById('deleteOrderId').value = orderId;
    document.getElementById('deleteModal').classList.remove('hidden');
}

// Populates and displays the edit order modal
function editOrder(orderData, customerName, customerEmail) {
    // Fetch complete order data to ensure we have all fields
    fetch(`orders.php?fetch_order_data=${orderData.id}`)
        .then(response => {
            if (!response.ok) {
                throw new Error('Failed to fetch order details');
            }
            return response.json();
        })
        .then(fullOrderData => {
            if (fullOrderData.error) {
                Swal.fire('Error', 'Failed to load order details for editing.', 'error');
                return;
            }

            // Populate modal with complete order data
            document.getElementById('edit_order_id').value = fullOrderData.id;
            document.getElementById('editOrderNumber').textContent = `#${fullOrderData.order_number}`;
            document.getElementById('editCustomerName').textContent = customerName || 'N/A';
            document.getElementById('editCustomerEmail').textContent = customerEmail || 'N/A';

            document.getElementById('edit_status').value = fullOrderData.status;
            document.getElementById('edit_payment_status').value = fullOrderData.payment_status || 'pending';
            document.getElementById('edit_delivery_address').value = fullOrderData.delivery_address || '';
            document.getElementById('edit_delivery_instructions').value = fullOrderData.delivery_instructions || '';

            document.getElementById('edit_subtotal').value = parseFloat(fullOrderData.subtotal || 0).toFixed(2);
            document.getElementById('edit_delivery_fee').value = parseFloat(fullOrderData.delivery_fee || 0).toFixed(2);
            calculateTotal('edit'); // Compute total based on populated subtotal/delivery_fee

            // Show modal
            document.getElementById('editOrderModal').classList.remove('hidden');
        })
        .catch(error => {
            console.error('Error fetching order data for edit:', error);
            Swal.fire('Error', 'Failed to load order details for editing.', 'error');
        });
}

// Resets and displays the add new order modal
function addOrder() {
    document.getElementById('addOrderForm').reset(); // Clear all fields
    // Set default values for convenience
    document.getElementById('add_user_id').value = ''; // Ensure "Select a customer" is chosen
    document.getElementById('add_status').value = 'pending';
    document.getElementById('add_payment_method').value = 'M-Pesa'; // Example default
    document.getElementById('add_payment_status').value = 'pending';
    document.getElementById('add_subtotal').value = '0.00';
    document.getElementById('add_delivery_fee').value = '0.00';
    calculateTotal('add'); // Initialize total to 0.00
    document.getElementById('addOrderModal').classList.remove('hidden'); // Show modal
}

// Function to update order status directly (e.g., from Process, Ship, Deliver buttons)
function updateOrderStatus(orderId, newStatus) {
    // Show confirmation dialog
    Swal.fire({
        title: 'Update Order Status?',
        text: `Are you sure you want to change this order status to "${newStatus.charAt(0).toUpperCase() + newStatus.slice(1)}"?`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#10b981',
        cancelButtonColor: '#6b7280',
        confirmButtonText: 'Yes, update status!'
    }).then((result) => {
        if (result.isConfirmed) {
            // Show loading state
            const button = event.target.closest('button');
            const originalHTML = button.innerHTML;
            button.innerHTML = '<i class="fas fa-spinner fa-spin relative z-10"></i><span class="relative z-10 font-medium">Updating...</span>';
            button.disabled = true;

            // Update status via AJAX to update_status.php
            fetch(`update_status.php?id=${orderId}&status=${newStatus}`, {
                method: 'GET',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('HTTP error! status: ' + response.status);
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    Swal.fire('Success!', 'Order status updated successfully!', 'success');
                    // Reload page to reflect changes
                    setTimeout(() => {
                        window.location.reload();
                    }, 1500);
                } else {
                    Swal.fire('Error', 'Error updating order status: ' + (data.message || 'Unknown error'), 'error');
                }
            })
            .catch(error => {
                console.error('Status update error:', error);
                Swal.fire('Error', 'Error updating order status: ' + error.message, 'error');
            })
            .finally(() => {
                // Restore button state
                button.innerHTML = originalHTML;
                button.disabled = false;
            });
        }
    });
}
</script>

<!-- ============================================== CSS STYLES ============================================== -->
<style>
/* Filter tab styling */
.filter-tab {
    @apply px-4 py-2 rounded-lg font-medium transition-all duration-200;
}

.filter-tab.active {
    @apply bg-red-600 text-white shadow-md;
}

.filter-tab:not(.active) {
    @apply bg-gray-100 text-gray-600 hover:bg-gray-200;
}

/* Base button styling for the new gradient buttons */
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
</style>

<?php
// 17. Include Admin Footer
include 'includes/footer.php';
?>