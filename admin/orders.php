<?php
// Set page title and include header
$page_title = 'Manage Orders';
$page_description = 'View and manage customer orders';

// Include database connection and functions
// Ensure these paths are correct relative to orders.php in admin/
// If orders.php is in admin/, then config.php is in ../includes/
// If includes/functions.php is in admin/includes/, then it's 'includes/functions.php'.
// If ActivityLogger.php is in ../includes/, then it's 'dirname(__DIR__) . /includes/ActivityLogger.php'.
require_once dirname(__DIR__) . '/includes/config.php'; // Correct path to project_root/includes/config.php
require_once 'includes/functions.php'; // Assuming custom admin functions exist here, relative to admin/
require_once dirname(__DIR__) . '/includes/ActivityLogger.php'; // Correct path to project_root/includes/ActivityLogger.php
$activityLogger = new ActivityLogger($pdo);

// Initialize message variables
$success_message = '';
$error_message = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['_method']) && $_POST['_method'] === 'DELETE') {
        $order_id = (int)$_POST['id'];
        try {
            // Fetch order details for logging
            $orderStmt = $pdo->prepare("SELECT order_number, user_id FROM orders WHERE id = ?");
            $orderStmt->execute([$order_id]);
            $order_info = $orderStmt->fetch(PDO::FETCH_ASSOC);

            if ($order_info) {
                // To delete an order, you might need to delete its related order_items first
                // if order_items.order_id has an ON DELETE RESTRICT foreign key constraint.
                // Or ensure ON DELETE CASCADE is set up in your database schema.
                // Assuming ON DELETE CASCADE is set on order_items.order_id.
                $deleteOrderStmt = $pdo->prepare("DELETE FROM orders WHERE id = ?");
                if ($deleteOrderStmt->execute([$order_id])) {
                    $success_message = 'Order ' . htmlspecialchars($order_info['order_number']) . ' deleted successfully';
                    $activityLogger->logActivity("Order '{$order_info['order_number']}' (ID: {$order_id}) deleted.", $_SESSION['user_id'], 'order_delete');
                } else {
                    $error_message = 'Failed to delete order. Please check database constraints.';
                }
            } else {
                $error_message = 'Order not found for deletion.';
            }
        } catch (PDOException $e) {
            $error_message = 'Database error deleting order: ' . $e->getMessage();
            error_log("Error deleting order (ID: {$order_id}): {$e->getMessage()}");
        }
    } elseif (isset($_POST['update_order'])) {
        $order_id = (int)$_POST['order_id'];
        $status = $_POST['status'];
        $payment_status = $_POST['payment_status'];
        $delivery_address = trim($_POST['delivery_address']);
        $delivery_instructions = trim($_POST['delivery_instructions']);
        $subtotal = (float)$_POST['subtotal'];
        $delivery_fee = (float)$_POST['delivery_fee'];
        $total_amount = (float)$_POST['total_amount']; // Should be calculated or validated

        try {
            $stmt = $pdo->prepare("UPDATE orders SET status = ?, payment_status = ?, delivery_address = ?, delivery_instructions = ?, subtotal = ?, delivery_fee = ?, total_amount = ? WHERE id = ?");
            $stmt->execute([$status, $payment_status, $delivery_address, $delivery_instructions, $subtotal, $delivery_fee, $total_amount, $order_id]);
            $success_message = "Order #{$order_id} updated successfully.";
            $activityLogger->logActivity("Order #{$order_id} details updated.", $_SESSION['user_id'], 'order_update');
        } catch (PDOException $e) {
            $error_message = "Error updating order #{$order_id}: " . $e->getMessage();
            error_log("Error updating order #{$order_id}: {$e->getMessage()}");
        }
    } elseif (isset($_POST['add_order'])) {
        // This is a simplified "Add Order" handler. A real one might need more complex item selection logic.
        $user_id = (int)$_POST['user_id'];
        $order_number = "ORD-" . strtoupper(uniqid()); // Generate unique order number
        $status = $_POST['status'];
        $payment_method = $_POST['payment_method'];
        $payment_status = $_POST['payment_status'];
        $delivery_address = trim($_POST['delivery_address']);
        $delivery_instructions = trim($_POST['delivery_instructions']);
        $subtotal = (float)$_POST['subtotal'];
        $delivery_fee = (float)$_POST['delivery_fee'];
        $total_amount = $subtotal + $delivery_fee; // Calculate total

        try {
            $stmt = $pdo->prepare("INSERT INTO orders (user_id, order_number, status, payment_method, payment_status, delivery_address, delivery_instructions, subtotal, delivery_fee, total_amount, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
            $stmt->execute([$user_id, $order_number, $status, $payment_method, $payment_status, $delivery_address, $delivery_instructions, $subtotal, $delivery_fee, $total_amount]);
            $new_order_id = $pdo->lastInsertId();
            $success_message = "New Order #{$order_number} created successfully.";
            $activityLogger->logActivity("New Order #{$order_number} created.", $_SESSION['user_id'], 'order_add');
        } catch (PDOException $e) {
            $error_message = "Error creating order: " . $e->getMessage();
            error_log("Error creating order: {$e->getMessage()}");
        }
    }

    // Store messages in session for redirection (Post/Redirect/Get pattern)
    if ($success_message) {
        $_SESSION['message'] = ['type' => 'success', 'text' => $success_message];
    } elseif ($error_message) {
        $_SESSION['message'] = ['type' => 'error', 'text' => $error_message];
    }
    header('Location: orders.php');
    exit();
}

// Check if there's a message from a previous redirect
if (isset($_SESSION['message'])) {
    $message = $_SESSION['message'];
    unset($_SESSION['message']);
    if ($message['type'] === 'success') {
        $success_message = $message['text'];
    } else {
        $error_message = $message['text'];
    }
}


// Get filter parameters
$status_filter = isset($_GET['status']) ? trim($_GET['status']) : ''; // Renamed to avoid clash with local variable
$date_from = isset($_GET['date_from']) ? trim($_GET['date_from']) : '';
$date_to = isset($_GET['date_to']) ? trim($_GET['date_to']) : '';
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

// Build base query
$where_conditions = [];
$params = [];
$base_query = "FROM orders o
          LEFT JOIN users u ON o.user_id = u.id"; // Adjusted as per your previous query

if ($status_filter) {
    $where_conditions[] = "o.status = :status_filter";
    $params[':status_filter'] = $status_filter;
}

if ($date_from) {
    $where_conditions[] = "DATE(o.created_at) >= :date_from";
    $params[':date_from'] = $date_from;
}

if ($date_to) {
    // Add 1 day to date_to so it includes the entire selected day
    $where_conditions[] = "DATE(o.created_at) <= :date_to";
    $params[':date_to'] = $date_to;
}

if ($search) {
    $where_conditions[] = "(o.order_number LIKE :search OR u.name LIKE :search_user)";
    $params[':search'] = "%{$search}%";
    $params[':search_user'] = "%{$search}%";
}

$final_where_clause = '';
if (!empty($where_conditions)) {
    $final_where_clause = " WHERE " . implode(" AND ", $where_conditions);
}

// Get total count for pagination (before applying GROUP BY and LIMIT)
$count_query = "SELECT COUNT(DISTINCT o.id) as total $base_query $final_where_clause";
$count_stmt = $pdo->prepare($count_query);
foreach ($params as $key => $value) {
    $count_stmt->bindValue($key, $value, is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR);
}
$count_stmt->execute();
$total_records = $count_stmt->fetch(PDO::FETCH_ASSOC)['total'];

// Pagination
$per_page = 10;
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$total_pages = ceil($total_records / $per_page);
$offset = ($page - 1) * $per_page;

// Build query for main data retrieval
$query_data = "SELECT o.*, u.name as customer_name, u.email as customer_email,
                 u.phone as customer_phone
          $base_query
          $final_where_clause
          GROUP BY o.id
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

// For order cards, we need item details. For simplicity, let's fetch them per order
// This is not ideal for performance in a large dataset but works for the current display logic.
// A more efficient approach would be to join order_items or fetch separately and map.
foreach ($orders as &$order) {
    $items_stmt = $pdo->prepare("SELECT item_name, total FROM order_items WHERE order_id = ?");
    $items_stmt->execute([$order['id']]);
    $order_items = $items_stmt->fetchAll(PDO::FETCH_ASSOC);

    $order['item_count'] = count($order_items);
    $item_names_arr = array_column($order_items, 'item_name');
    $order['item_names_summary'] = implode(', ', $item_names_arr);
    $order['items_total_amount'] = array_sum(array_column($order_items, 'total')); // Sum of individual item totals

}
unset($order); // Break the reference after loop

// Get order status counts for filters
$status_counts = [];
$status_options = ['pending', 'processing', 'shipped', 'delivered', 'cancelled'];
foreach ($status_options as $s) {
    $temp_query = "SELECT COUNT(o.id) $base_query WHERE o.status = :status";
    $temp_stmt = $pdo->prepare($temp_query);
    $temp_stmt->execute([':status' => $s]);
    $status_counts[$s] = $temp_stmt->fetchColumn();
}


// Fetch a list of users to select from in the "Add Order" modal
$customer_list = [];
try {
    $stmt_customers = $pdo->query("SELECT id, name, email FROM users WHERE role = 'customer' ORDER BY name ASC");
    $customer_list = $stmt_customers->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error fetching customer list: " . $e->getMessage());
}

// Include the admin dashboard header. This includes the HTML <body> tag and top section.
include 'includes/admin_header.php'; // Make sure this path is correct for your admin header.
?>
```

---

**Part 2: Main HTML Content (Dashboard Header, Statistics, Alerts, Filter/Search, Order Cards, Pagination)**

```html
<!-- Dashboard Header -->
<div class="bg-gradient-to-br from-red-600 via-red-700 to-red-800 text-white mt-0">
    <div class="container mx-auto px-6 py-8">
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between">
            <div>
                <h1 class="text-3xl md:text-4xl font-bold mb-2">Order Management</h1>
                <p class="text-lg opacity-90">View and manage customer orders</p>
            </div>
            <div class="mt-4 lg:mt-0">
                <button onclick="addOrder()"
                   class="group relative bg-gradient-to-r from-green-600 to-green-700 hover:from-green-700 hover:to-green-800 text-white px-6 py-3 rounded-xl font-semibold transition-all duration-300 flex items-center shadow-lg hover:shadow-xl transform hover:-translate-y-1 border border-green-400/20">
                    <div class="absolute inset-0 bg-gradient-to-r from-white/0 via-white/10 to-white/0 opacity-0 group-hover:opacity-100 transition-opacity duration-300 rounded-xl"></div>
                    <i class="fas fa-plus mr-2 relative z-10"></i>
                    <span class="relative z-10 font-medium">Add New Order</span>
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Statistics Cards -->
<div class="bg-gray-50 py-8">
    <div class="container mx-auto px-6">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
            <div class="bg-white rounded-xl shadow-lg p-6 border-l-4 border-red-500">
                <div class="flex items-center">
                    <div class="p-3 bg-red-100 rounded-lg">
                        <i class="fas fa-shopping-cart text-2xl text-red-600"></i>
                    </div>
                    <div class="ml-4">
                        <h3 class="text-2xl font-bold text-gray-900"><?php echo number_format($total_records); ?></h3>
                        <p class="text-gray-600">Total Orders</p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-lg p-6 border-l-4 border-green-500">
                <div class="flex items-center">
                    <div class="p-3 bg-green-100 rounded-lg">
                        <i class="fas fa-check-circle text-2xl text-green-600"></i>
                    </div>
                    <div class="ml-4">
                        <h3 class="text-2xl font-bold text-gray-900">
                            <?php echo number_format($status_counts['delivered'] ?? 0); ?>
                        </h3>
                        <p class="text-gray-600">Delivered</p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-lg p-6 border-l-4 border-yellow-500">
                <div class="flex items-center">
                    <div class="p-3 bg-yellow-100 rounded-lg">
                        <i class="fas fa-clock text-2xl text-yellow-600"></i>
                    </div>
                    <div class="ml-4">
                        <h3 class="text-2xl font-bold text-gray-900">
                            <?php echo number_format($status_counts['pending'] ?? 0); ?>
                        </h3>
                        <p class="text-gray-600">Pending</p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-lg p-6 border-l-4 border-purple-500">
                <div class="flex items-center">
                    <div class="p-3 bg-purple-100 rounded-lg">
                        <i class="fas fa-dollar-sign text-2xl text-purple-600"></i>
                    </div>
                    <div class="ml-4">
                        <h3 class="text-2xl font-bold text-gray-900">
                            KES <?php
                            try {
                                $total_revenue_stmt = $pdo->prepare("SELECT SUM(total_amount) FROM orders WHERE status = 'delivered'");
                                $total_revenue_stmt->execute();
                                echo number_format($total_revenue_stmt->fetchColumn() ?: 0, 0);
                            } catch (PDOException $e) {
                                echo "0";
                                error_log("Error calculating revenue: " . $e->getMessage());
                            }
                            ?>
                        </h3>
                        <p class="text-gray-600">Revenue</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Main Content -->
<div class="container mx-auto px-6 py-8">
    <!-- Alerts -->
    <?php if ($error_message): ?>
        <div class="mb-6 p-4 bg-red-50 border-l-4 border-red-400 text-red-700 rounded-lg flex items-center">
            <i class="fas fa-exclamation-circle mr-3"></i>
            <div>
                <p class="font-semibold">Error</p>
                <p><?php echo htmlspecialchars($error_message); ?></p>
            </div>
            <button onclick="this.parentElement.remove()" class="ml-auto text-gray-400 hover:text-gray-600">
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
            <button onclick="this.parentElement.remove()" class="ml-auto text-gray-400 hover:text-gray-600">
                <i class="fas fa-times"></i>
            </button>
        </div>
    <?php endif; ?>

    <!-- Enhanced Filter & Search Section -->
    <div class="bg-white rounded-xl shadow-lg p-6 mb-8">
        <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
            <!-- Search Input -->
            <div class="lg:col-span-2">
                <label class="block text-sm font-semibold text-gray-700 mb-2">Search Orders</label>
                <div class="relative">
                    <input type="text" id="searchInput" value="<?php echo htmlspecialchars($search); ?>"
                           placeholder="Search by order number, customer name, email..."
                           class="w-full pl-12 pr-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-red-500 transition-colors">
                    <i class="fas fa-search absolute left-4 top-1/2 transform -translate-y-1/2 text-gray-400"></i>
                </div>
            </div>

            <!-- Date From Filter -->
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">From Date</label>
                <input type="date" id="dateFromFilter" value="<?php echo htmlspecialchars($date_from); ?>"
                       class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-red-500 transition-colors">
            </div>

            <!-- Date To Filter -->
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">To Date</label>
                <input type="date" id="dateToFilter" value="<?php echo htmlspecialchars($date_to); ?>"
                       class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-red-500 transition-colors">
            </div>
        </div>

        <!-- Quick Filter Tabs -->
        <div class="mt-6 pt-6 border-t border-gray-200">
            <div class="flex flex-wrap gap-2">
                <button class="filter-tab <?php echo empty($status_filter) ? 'active' : ''; ?>" data-filter="all">
                    All Orders <span class="ml-1 bg-gray-100 text-gray-600 px-2 py-1 rounded-full text-xs"><?php echo number_format($total_records); ?></span>
                </button>
                <?php
                $order_statuses = [
                    'pending' => ['name' => 'Pending', 'color_class' => 'bg-yellow-100 text-yellow-600'],
                    'processing' => ['name' => 'Processing', 'color_class' => 'bg-blue-100 text-blue-600'],
                    'shipped' => ['name' => 'Shipped', 'color_class' => 'bg-indigo-100 text-indigo-600'],
                    'delivered' => ['name' => 'Delivered', 'color_class' => 'bg-green-100 text-green-600'],
                    'cancelled' => ['name' => 'Cancelled', 'color_class' => 'bg-red-100 text-red-600']
                ];
                foreach ($order_statuses as $status_key => $details):
                ?>
                    <button class="filter-tab <?php echo ($status_filter === $status_key) ? 'active' : ''; ?>" data-filter="<?php echo $status_key; ?>">
                        <?php echo $details['name']; ?> <span class="ml-1 <?php echo $details['color_class']; ?> px-2 py-1 rounded-full text-xs"><?php echo number_format($status_counts[$status_key] ?? 0); ?></span>
                    </button>
                <?php endforeach; ?>
                <?php if ($search || $date_from || $date_to || $status_filter): ?>
                    <button onclick="clearFilterInputs()" class="ml-4 text-red-600 hover:text-red-700 font-medium">
                        <i class="fas fa-times mr-1"></i>Clear Filters
                    </button>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php if (count($orders) > 0): ?>
        <?php foreach ($orders as $order): ?>
        <div class="bg-white rounded-xl shadow-lg border border-gray-200 hover:shadow-xl transition-all duration-300 overflow-hidden mb-6 order-card"
             data-order-number="<?= htmlspecialchars($order['order_number']) ?>"
             data-customer-name="<?= htmlspecialchars(strtolower($order['customer_name'] ?? '')) ?>"
             data-customer-email="<?= htmlspecialchars(strtolower($order['customer_email'] ?? '')) ?>"
             data-order-date="<?= date('Y-m-d', strtotime($order['created_at'])) ?>"
             data-order-status="<?= $order['status'] ?>">

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
                                <span class="px-3 py-1 rounded-full text-sm font-semibold <?php
                                    echo match($order['status']) {
                                        'pending' => 'bg-yellow-200 text-yellow-800',
                                        'processing' => 'bg-blue-200 text-blue-800',
                                        'shipped' => 'bg-indigo-200 text-indigo-800',
                                        'delivered' => 'bg-green-200 text-green-800',
                                        'cancelled' => 'bg-red-200 text-red-800',
                                        default => 'bg-gray-200 text-gray-800'
                                    };
                                ?>">
                                    <i class="fas fa-<?php
                                        echo match($order['status']) {
                                            'pending' => 'clock',
                                            'processing' => 'cog',
                                            'shipped' => 'shipping-fast',
                                            'delivered' => 'truck',
                                            'cancelled' => 'times-circle'
                                        };
                                    ?> mr-1 text-xs"></i>
                                    <?php echo ucfirst($order['status']); ?>
                                </span>
                                <span class="text-lg font-bold text-red-600">
                                    KES <?= number_format($order['total_amount'] ?? 0, 2) ?>
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Quick CRUD Actions -->
                    <div class="mt-4 lg:mt-0 flex flex-wrap gap-2">
                        <a href="order_view.php?id=<?= $order['id'] ?>"
                           class="group relative bg-gradient-to-r from-blue-500 to-blue-600 hover:from-blue-600 hover:to-blue-700 text-white px-4 py-2 rounded-xl font-semibold transition-all duration-300 flex items-center space-x-2 shadow-lg hover:shadow-xl transform hover:-translate-y-0.5 border border-blue-400/20">
                            <div class="absolute inset-0 bg-gradient-to-r from-white/0 via-white/10 to-white/0 opacity-0 group-hover:opacity-100 transition-opacity duration-300 rounded-xl"></div>
                            <i class="fas fa-eye relative z-10"></i>
                            <span class="relative z-10 font-medium">View</span>
                        </a>

                        <?php if (in_array($order['status'], ['pending', 'processing'])): ?>
                            <button onclick="updateOrderStatus(<?php echo $order['id']; ?>, 'processing')"
                               class="group relative bg-gradient-to-r from-teal-500 to-teal-600 hover:from-teal-600 hover:to-teal-700 text-white px-4 py-2 rounded-xl font-semibold transition-all duration-300 flex items-center space-x-2 shadow-lg hover:shadow-xl transform hover:-translate-y-0.5 border border-teal-400/20">
                                <div class="absolute inset-0 bg-gradient-to-r from-white/0 via-white/10 to-white/0 opacity-0 group-hover:opacity-100 transition-opacity duration-300 rounded-xl"></div>
                                <i class="fas fa-play-circle relative z-10"></i>
                                <span class="relative z-10 font-medium">Process</span>
                            </button>
                        <?php endif; ?>

                        <?php if (in_array($order['status'], ['processing'])): ?>
                            <button onclick="updateOrderStatus(<?php echo $order['id']; ?>, 'shipped')"
                               class="group relative bg-gradient-to-r from-indigo-500 to-indigo-600 hover:from-indigo-600 hover:to-indigo-700 text-white px-4 py-2 rounded-xl font-semibold transition-all duration-300 flex items-center space-x-2 shadow-lg hover:shadow-xl transform hover:-translate-y-0.5 border border-indigo-400/20">
                                <div class="absolute inset-0 bg-gradient-to-r from-white/0 via-white/10 to-white/0 opacity-0 group-hover:opacity-100 transition-opacity duration-300 rounded-xl"></div>
                                <i class="fas fa-shipping-fast relative z-10"></i>
                                <span class="relative z-10 font-medium">Ship</span>
                            </button>
                        <?php endif; ?>

                        <?php if (in_array($order['status'], ['shipped'])): ?>
                            <button onclick="updateOrderStatus(<?php echo $order['id']; ?>, 'delivered')"
                               class="group relative bg-gradient-to-r from-green-600 to-green-700 hover:from-green-700 hover:to-green-800 text-white px-4 py-2 rounded-xl font-semibold transition-all duration-300 flex items-center space-x-2 shadow-lg hover:shadow-xl transform hover:-translate-y-0.5 border border-green-400/20">
                                <div class="absolute inset-0 bg-gradient-to-r from-white/0 via-white/10 to-white/0 opacity-0 group-hover:opacity-100 transition-opacity duration-300 rounded-xl"></div>
                                <i class="fas fa-check relative z-10"></i>
                                <span class="relative z-10 font-medium">Deliver</span>
                            </button>
                        <?php endif; ?>

                        <button onclick="editOrder(<?php echo htmlspecialchars(json_encode($order)); ?>, '<?php echo htmlspecialchars($order['customer_name'] ?? ''); ?>', '<?php echo htmlspecialchars($order['customer_email'] ?? ''); ?>')"
                           class="group relative bg-gradient-to-r from-purple-500 to-purple-600 hover:from-purple-600 hover:to-purple-700 text-white px-4 py-2 rounded-xl font-semibold transition-all duration-300 flex items-center space-x-2 shadow-lg hover:shadow-xl transform hover:-translate-y-0.5 border border-purple-400/20">
                            <div class="absolute inset-0 bg-gradient-to-r from-white/0 via-white/10 to-white/0 opacity-0 group-hover:opacity-100 transition-opacity duration-300 rounded-xl"></div>
                            <i class="fas fa-edit relative z-10"></i>
                            <span class="relative z-10 font-medium">Edit</span>
                        </button>

                        <button onclick="confirmDelete(<?= $order['id'] ?>, '<?= htmlspecialchars($order['order_number']) ?>')"
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
                            <p class="text-gray-700"><span class="font-medium">Payment:</span> <?php echo ucfirst(str_replace('_', ' ', $order['payment_method'])); ?> (<?php echo ucfirst($order['payment_status']); ?>)</p>
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
                            <p class="text-gray-700"><span class="font-medium">Address:</span> <?php echo htmlspecialchars(substr($order['delivery_address'], 0, 60) . (strlen($order['delivery_address']) > 60 ? '...' : '')); ?></p>
                            <?php if (!empty($order['delivery_instructions'])): ?>
                            <p class="text-gray-700"><span class="font-medium">Instructions:</span> <?php echo htmlspecialchars(substr($order['delivery_instructions'], 0, 60) . (strlen($order['delivery_instructions']) > 60 ? '...' : '')); ?></p>
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
                            <p class="text-lg font-semibold <?php echo $order['delivery_fee'] === 0 ? 'text-green-600' : 'text-gray-900'; ?>">
                                <?php echo $order['delivery_fee'] === 0 ? 'Free' : 'KES ' . number_format($order['delivery_fee'], 2); ?>
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
        <!-- No Orders State -->
        <div class="bg-white rounded-xl shadow-lg p-12 text-center">
            <div class="max-w-md mx-auto">
                <i class="fas fa-shopping-cart text-6xl text-gray-300 mb-6"></i>
                <h2 class="text-2xl font-bold text-gray-700 mb-4">No Orders Found</h2>
                <p class="text-gray-600 mb-8">
                    <?php if (!empty($search) || !empty($status_filter) || !empty($date_from) || !empty($date_to)): ?>
                        No orders match your current filters. Try adjusting your search criteria.
                    <?php else: ?>
                        No orders have been placed yet. Orders will appear here once customers start ordering.
                    <?php endif; ?>
                </p>
                <?php if (!empty($search) || !empty($status_filter) || !empty($date_from) || !empty($date_to)): ?>
                    <button onclick="clearFilterInputs()" class="inline-flex items-center justify-center px-6 py-3 bg-red-600 text-white font-bold rounded-lg hover:bg-red-700 transition-colors">
                        <i class="fas fa-times mr-2"></i>
                        Clear Filters
                    </button>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>
</div>

<!-- Pagination -->
<?php if ($total_pages > 1): ?>
<div class="mt-8 flex justify-center">
    <nav class="flex items-center space-x-2">
        <?php if ($page > 1): ?>
        <a href="?page=<?php echo $page - 1; ?><?php echo $status_filter ? '&status=' . urlencode($status_filter) : ''; ?><?php echo $date_from ? '&date_from=' . urlencode($date_from) : ''; ?><?php echo $date_to ? '&date_to=' . urlencode($date_to) : ''; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?>"
           class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors font-medium">
            <i class="fas fa-chevron-left mr-1"></i> Previous
        </a>
        <?php endif; ?>

        <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
        <a href="?page=<?php echo $i; ?><?php echo $status_filter ? '&status=' . urlencode($status_filter) : ''; ?><?php echo $date_from ? '&date_from=' . urlencode($date_from) : ''; ?><?php echo $date_to ? '&date_to=' . urlencode($date_to) : ''; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?>"
           class="px-4 py-2 border rounded-lg transition-colors font-medium <?php echo $i === $page ? 'bg-red-600 text-white border-red-600' : 'border-gray-300 hover:bg-gray-50'; ?>">
            <?php echo $i; ?>
        </a>
        <?php endfor; ?>

        <?php if ($page < $total_pages): ?>
        <a href="?page=<?php echo $page + 1; ?><?php echo $status_filter ? '&status=' . urlencode($status_filter) : ''; ?><?php echo $date_from ? '&date_from=' . urlencode($date_from) : ''; ?><?php echo $date_to ? '&date_to=' . urlencode($date_to) : ''; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?>"
           class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors font-medium">
            Next <i class="fas fa-chevron-right ml-1"></i>
        </a>
        <?php endif; ?>
    </nav>
</div>
<?php endif; ?>
```

---

**Part 3: Modals, JavaScript, CSS, and Footer Include**

```html
<!-- Delete Confirmation Modal (Redesigned) -->
<div id="deleteModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
    <div class="bg-white rounded-2xl shadow-2xl max-w-md w-full max-h-[90vh] overflow-y-auto">
        <!-- Modal Header -->
        <div class="bg-gradient-to-r from-red-600 to-red-700 p-6 text-white rounded-t-2xl">
            <div class="flex items-center justify-between">
                <h3 class="text-xl font-bold">
                    <i class="fas fa-exclamation-triangle mr-2"></i>Confirm Deletion
                </h3>
                <button onclick="closeModal('deleteModal')" class="text-white hover:text-gray-200 text-2xl">
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
            <button type="button" onclick="closeModal('deleteModal')"
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
<div id="editOrderModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
    <div class="bg-white rounded-2xl shadow-2xl max-w-2xl w-full max-h-[90vh] overflow-y-auto">
        <!-- Modal Header -->
        <div class="bg-gradient-to-r from-purple-600 to-purple-700 p-6 text-white rounded-t-2xl">
            <div class="flex items-center justify-between">
                <h3 class="text-xl font-bold">
                    <i class="fas fa-pencil-alt mr-2"></i>Edit Order <span id="editOrderNumber" class="font-mono text-purple-100 italic"></span>
                </h3>
                <button onclick="closeModal('editOrderModal')" class="text-white hover:text-gray-200 text-2xl">
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
                            <option value="paid">Paid</option>
                            <option value="refunded">Refunded</option>
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
                        <input type="number" step="0.01" min="0" name="total_amount" id="edit_total_amount" readonly
                               class="w-full px-4 py-3 border bg-gray-100 border-gray-300 rounded-lg cursor-not-allowed"
                               placeholder="0.00">
                        <p class="text-xs text-gray-500 mt-1">Automatically calculated</p>
                    </div>
                </div>
            </form>
        </div>

        <!-- Modal Footer -->
        <div class="p-6 border-t border-gray-200 flex gap-3 justify-end">
            <button type="button" onclick="closeModal('editOrderModal')"
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
<div id="addOrderModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
    <div class="bg-white rounded-2xl shadow-2xl max-w-2xl w-full max-h-[90vh] overflow-y-auto">
        <!-- Modal Header -->
        <div class="bg-gradient-to-r from-green-600 to-green-700 p-6 text-white rounded-t-2xl">
            <div class="flex items-center justify-between">
                <h3 class="text-xl font-bold">
                    <i class="fas fa-cart-plus mr-2"></i>Add New Order
                </h3>
                <button onclick="closeModal('addOrderModal')" class="text-white hover:text-gray-200 text-2xl">
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
                            <option value="paid">Paid</option>
                            <option value="refunded">Refunded</option>
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
                        <input type="number" step="0.01" min="0" name="total_amount" id="add_total_amount" readonly
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
            <button type="button" onclick="closeModal('addOrderModal')"
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

<script>
// Filter tab styling (CSS for filter-tab.active is defined in previous answers' <style> block implicitly)
// CSS for this should be added to your main stylesheet or a custom <style> block for customers.php
const filterTabs = document.querySelectorAll('.filter-tab');
filterTabs.forEach(tab => {
    if (tab.dataset.filter === '<?php echo $status_filter ?: 'all'; ?>') {
        tab.classList.add('active');
    } else {
        tab.classList.remove('active');
    }
    tab.addEventListener('click', function() {
        // Update URL and reload for full filter application
        let url = new URL(window.location);
        const filter = this.dataset.filter;
        if (filter === 'all') {
            url.searchParams.delete('status');
        } else {
            url.searchParams.set('status', filter);
        }
        window.location.href = url.toString();
    });
});

// Calculate total amount for Add/Edit order modals
function calculateTotal(prefix) {
    const subtotalInput = document.getElementById(prefix + '_subtotal');
    const deliveryFeeInput = document.getElementById(prefix + '_delivery_fee');
    const totalAmountInput = document.getElementById(prefix + '_total_amount');

    const subtotal = parseFloat(subtotalInput.value) || 0;
    const deliveryFee = parseFloat(deliveryFeeInput.value) || 0;
    totalAmountInput.value = (subtotal + deliveryFee).toFixed(2);
}

// Event listeners for calculating total in modals
document.addEventListener('DOMContentLoaded', function() {
    // For Edit Order Modal
    const editSubtotal = document.getElementById('edit_subtotal');
    const editDeliveryFee = document.getElementById('edit_delivery_fee');
    if (editSubtotal) {
        editSubtotal.addEventListener('input', () => calculateTotal('edit'));
        editSubtotal.addEventListener('change', () => calculateTotal('edit')); // Also on change to ensure calculation if value is pasted
    }
    if (editDeliveryFee) {
        editDeliveryFee.addEventListener('input', () => calculateTotal('edit'));
        editDeliveryFee.addEventListener('change', () => calculateTotal('edit'));
    }


    // For Add Order Modal
    const addSubtotal = document.getElementById('add_subtotal');
    const addDeliveryFee = document.getElementById('add_delivery_fee');
    if (addSubtotal) {
        addSubtotal.addEventListener('input', () => calculateTotal('add'));
        addSubtotal.addEventListener('change', () => calculateTotal('add'));
    }
    if (addDeliveryFee) {
        addDeliveryFee.addEventListener('input', () => calculateTotal('add'));
        addDeliveryFee.addEventListener('change', () => calculateTotal('add'));
    }


    // Client-side quick filter (if desired, currently not server-side filterable via JS)
    const searchInput = document.getElementById('searchInput');
    const dateFromFilter = document.getElementById('dateFromFilter');
    const dateToFilter = document.getElementById('dateToFilter');

    function applyFiltersToUrl() {
        let url = new URL(window.location.origin + window.location.pathname); // Base URL without current query params
        if (searchInput.value) url.searchParams.set('search', searchInput.value);
        if (dateFromFilter.value) url.searchParams.set('date_from', dateFromFilter.value);
        if (dateToFilter.value) url.searchParams.set('date_to', dateToFilter.value);
        const activeStatusFilter = document.querySelector('.filter-tab.active')?.dataset.filter;
        if (activeStatusFilter && activeStatusFilter !== 'all') {
            url.searchParams.set('status', activeStatusFilter);
        }
        window.location.href = url.toString(); // Redirect to apply server-side filters
    }

    // Debounce to improve performance for search input
    let searchTimeout;
    if (searchInput) {
        searchInput.addEventListener('input', () => {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(applyFiltersToUrl, 500); // Wait 500ms after last input
        });
    }
    if (dateFromFilter) dateFromFilter.addEventListener('change', applyFiltersToUrl);
    if (dateToFilter) dateToFilter.addEventListener('change', applyFiltersToUrl);
});


// Global functions for modals
function closeModal(modalId) {
    document.getElementById(modalId).classList.add('hidden');
}

// Close modal when clicking outside
window.onclick = function(event) {
    // List all modal element IDs
    const modalIds = ['deleteModal', 'editOrderModal', 'addOrderModal'];
    modalIds.forEach(id => {
        const modal = document.getElementById(id);
        if (modal && event.target === modal) { // Check if modal exists and click is on the modal backdrop
            closeModal(id);
        }
    });
}

// Delete Confirmation Modal Logic
function confirmDelete(orderId, orderNumber) {
    document.getElementById('deleteOrderNumber').textContent = orderNumber;
    document.getElementById('deleteOrderId').value = orderId;
    document.getElementById('deleteModal').classList.remove('hidden');
}

// Edit Order Modal Logic
function editOrder(orderData, customerName, customerEmail) { // Added customerEmail parameter
    document.getElementById('edit_order_id').value = orderData.id;
    document.getElementById('editOrderNumber').textContent = `#${orderData.order_number}`;
    document.getElementById('editCustomerName').textContent = customerName;
    document.getElementById('editCustomerEmail').textContent = customerEmail || 'N/A'; // Use passed email

    document.getElementById('edit_status').value = orderData.status;
    document.getElementById('edit_payment_status').value = orderData.payment_status;
    document.getElementById('edit_delivery_address').value = orderData.delivery_address;
    document.getElementById('edit_delivery_instructions').value = orderData.delivery_instructions;
    document.getElementById('edit_subtotal').value = parseFloat(orderData.subtotal).toFixed(2);
    document.getElementById('edit_delivery_fee').value = parseFloat(orderData.delivery_fee).toFixed(2);
    // Ensure total is calculated after subtotal and delivery fee are set
    calculateTotal('edit');

    document.getElementById('editOrderModal').classList.remove('hidden');
}

// Add Order Modal Logic
function addOrder() {
    // Reset form fields
    document.getElementById('addOrderForm').reset();
    // Default values
    document.getElementById('add_status').value = 'pending';
    document.getElementById('add_payment_method').value = 'M-Pesa'; // Default to a common method
    document.getElementById('add_payment_status').value = 'pending';
    document.getElementById('add_subtotal').value = '0.00';
    document.getElementById('add_delivery_fee').value = '0.00';
    document.getElementById('add_total_amount').value = '0.00';
    // Ensure customer select is at default (first option is usually "Select a customer")
    document.getElementById('add_user_id').value = '';

    document.getElementById('addOrderModal').classList.remove('hidden');
}

// Function to update order status (e.g., Ship, Deliver buttons directly)
// This submits a form to change status without a full edit modal
function updateOrderStatus(orderId, newStatus) {
    // Fetch full order data for ALL fields needed by PHP's update_order
    fetch(`orders.php?fetch_order_data=${orderId}`)
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(orderData => {
            if (orderData && orderData.id) {
                Swal.fire({
                    title: `Change order status to ${newStatus}?`,
                    text: `Are you sure you want to mark order #${orderData.order_number} as ${newStatus}?`,
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'Yes, update it!'
                }).then((result) => {
                    if (result.isConfirmed) {
                        // Create a temporary form to submit the status update
                        const form = document.createElement('form');
                        form.method = 'POST';
                        form.action = 'orders.php'; // Submit to the current page

                        // Populate all required fields for `update_order` handler
                        const orderIdInput = document.createElement('input');
                        orderIdInput.type = 'hidden';
                        orderIdInput.name = 'order_id';
                        orderIdInput.value = orderData.id;
                        form.appendChild(orderIdInput);

                        const statusInput = document.createElement('input');
                        statusInput.type = 'hidden';
                        statusInput.name = 'status';
                        statusInput.value = newStatus; // The new status
                        form.appendChild(statusInput);

                        const paymentStatusInput = document.createElement('input');
                        paymentStatusInput.type = 'hidden';
                        paymentStatusInput.name = 'payment_status';
                        paymentStatusInput.value = orderData.payment_status; // Keep original payment status
                        form.appendChild(paymentStatusInput);

                        const deliveryAddressInput = document.createElement('input');
                        deliveryAddressInput.type = 'hidden';
                        deliveryAddressInput.name = 'delivery_address';
                        deliveryAddressInput.value = orderData.delivery_address; // Keep original address
                        form.appendChild(deliveryAddressInput);

                        const deliveryInstructionsInput = document.createElement('input');
                        deliveryInstructionsInput.type = 'hidden';
                        deliveryInstructionsInput.name = 'delivery_instructions';
                        deliveryInstructionsInput.value = orderData.delivery_instructions; // Keep original instructions
                        form.appendChild(deliveryInstructionsInput);

                        const subtotalInput = document.createElement('input');
                        subtotalInput.type = 'hidden';
                        subtotalInput.name = 'subtotal';
                        subtotalInput.value = orderData.subtotal; // Keep original subtotal
                        form.appendChild(subtotalInput);

                        const deliveryFeeInput = document.createElement('input');
                        deliveryFeeInput.type = 'hidden';
                        deliveryFeeInput.name = 'delivery_fee';
                        deliveryFeeInput.value = orderData.delivery_fee; // Keep original delivery fee
                        form.appendChild(deliveryFeeInput);

                        const totalAmountInput = document.createElement('input');
                        totalAmountInput.type = 'hidden';
                        totalAmountInput.name = 'total_amount';
                        totalAmountInput.value = orderData.total_amount; // Keep original total amount
                        form.appendChild(totalAmountInput);

                        const updateTriggerInput = document.createElement('input');
                        updateTriggerInput.type = 'hidden';
                        updateTriggerInput.name = 'update_order';
                        updateTriggerInput.value = '1';
                        form.appendChild(updateTriggerInput);

                        document.body.appendChild(form); // Append to the document
                        form.submit(); // Submit the form
                    }
                });
            } else {
                Swal.fire('Error', 'Could not fetch order data for update.', 'error');
            }
        })
        .catch(error => {
            console.error('Error fetching order data:', error);
            Swal.fire('Error', 'Failed to fetch order details for status update.', 'error');
        });
}

// Function to clear filter inputs and reload the page
function clearFilterInputs() {
    window.location.href = 'orders.php'; // Simply reload to clear all filters
}

// Additional PHP for fetching single order data for AJAX (needed by updateOrderStatus)
<?php
// This block should be placed at the very top of the PHP file, before any HTML output,
// but AFTER database connection and session start.
// This is an API endpoint for fetching a single order's details.
if (isset($_GET['fetch_order_data']) && is_numeric($_GET['fetch_order_data'])) {
    $order_id_to_fetch = (int)$_GET['fetch_order_data'];
    try {
        $stmt = $pdo->prepare("SELECT o.*, u.name as customer_name, u.email as customer_email FROM orders o LEFT JOIN users u ON o.user_id = u.id WHERE o.id = ?");
        $stmt->execute([$order_id_to_fetch]);
        $order_data = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($order_data) {
            header('Content-Type: application/json');
            echo json_encode($order_data);
        } else {
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Order not found']);
        }
    } catch (PDOException $e) {
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
    }
    exit(); // IMPORTANT: Exit after sending JSON response
}
?>
</script>

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
/* These classes are already applied directly in HTML.
   This style block ensures the transitions are smooth. */
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

<?php include 'includes/admin_footer.php'; // Adjust this path as needed ?>
```
