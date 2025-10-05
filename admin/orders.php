<?php
// Set page title and include header
$page_title = 'Manage Orders';
$page_description = 'View and manage customer orders';

// Include database connection and functions
require_once '../includes/config.php';
require_once 'includes/functions.php';

// Handle delete action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['_method']) && $_POST['_method'] === 'DELETE') {
    if (deleteRecord('orders', $_POST['id'])) {
        $_SESSION['message'] = ['type' => 'success', 'text' => 'Order deleted successfully'];
    } else {
        $_SESSION['message'] = ['type' => 'error', 'text' => 'Failed to delete order'];
    }
    header('Location: orders.php');
    exit();
}

// Get filter parameters
$status = isset($_GET['status']) ? trim($_GET['status']) : '';
$date_from = isset($_GET['date_from']) ? trim($_GET['date_from']) : date('Y-m-01'); // Default to start of current month
$date_to = isset($_GET['date_to']) ? trim($_GET['date_to']) : date('Y-m-d');     // Default to today
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

// Build base query (without SELECT for count query)
$where_conditions = [];
$base_query = "FROM orders o
          LEFT JOIN users u ON o.user_id = u.id
          LEFT JOIN order_items oi ON o.id = oi.order_id
          WHERE 1=1";

if ($status) {
    $where_conditions[] = "o.status = :status";
    $params[':status'] = $status;
}

if ($date_from) {
    $where_conditions[] = "DATE(o.created_at) >= :date_from";
    $params[':date_from'] = $date_from;
}

if ($date_to) {
    $where_conditions[] = "DATE(o.created_at) <= :date_to";
    $params[':date_to'] = $date_to;
}

if ($search) {
    $where_conditions[] = "(o.id = :search_id OR u.name LIKE :search OR u.email LIKE :search OR u.phone LIKE :search_phone)";
    $params[':search'] = "%$search%";
    $params[':search_phone'] = "%$search%";
    $params[':search_id'] = is_numeric($search) ? (int)$search : -1;
}

// Add WHERE conditions to base query
if (!empty($where_conditions)) {
    $base_query .= " AND " . implode(" AND ", $where_conditions);
}

// Build query for main data retrieval
$query = "SELECT o.*, u.name as customer_name, u.email as customer_email,
                 u.phone as customer_phone,
                 (SELECT COUNT(*) FROM orders o2 WHERE o2.user_id = o.user_id) as total_orders,
                 GROUP_CONCAT(oi.item_name SEPARATOR ', ') as item_names,
                 COUNT(oi.id) as item_count,
                 SUM(oi.total) as total_amount
          $base_query";

// Get total count for pagination
$count_query = "SELECT COUNT(*) as total $base_query";
$count_stmt = $pdo->prepare($count_query);
foreach ($params as $key => $value) {
    $param_type = is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR;
    $count_stmt->bindValue($key, $value, $param_type);
}
$count_stmt->execute();
$total_records = $count_stmt->fetch(PDO::FETCH_ASSOC)['total'];

// Pagination
$per_page = 10;
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$total_pages = ceil($total_records / $per_page);
$offset = ($page - 1) * $per_page;

// Add pagination to main query
$query .= " GROUP BY o.id ORDER BY o.created_at DESC LIMIT :limit OFFSET :offset";
$params[':limit'] = $per_page;
$params[':offset'] = $offset;

// Execute the main query
$stmt = $pdo->prepare($query);
foreach ($params as $key => $value) {
    $param_type = is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR;
    $stmt->bindValue($key, $value, $param_type);
}
$stmt->execute();
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get order statuses for filter with counts
$statuses = [
    '' => ['name' => 'All Orders', 'count' => $total_records, 'color' => 'gray'],
    'pending' => ['name' => 'Pending', 'color' => 'yellow'],
    'processing' => ['name' => 'Processing', 'color' => 'blue'],
    'shipped' => ['name' => 'Shipped', 'color' => 'indigo'],
    'delivered' => ['name' => 'Delivered', 'color' => 'green'],
    'cancelled' => ['name' => 'Cancelled', 'color' => 'red']
];

// Get counts for each status
foreach ($statuses as $key => &$status) {
    if ($key === '') continue;
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM orders WHERE status = ?");
    $stmt->execute([$key]);
    $status['count'] = $stmt->fetchColumn();
}
unset($status); // Break reference

// Include header
include 'includes/header.php';
?>

<!-- Status Filter Tabs -->
<div class="mb-6 border-b border-gray-200">
    <nav class="-mb-px flex space-x-8" aria-label="Order status">
        <a href="?status=" class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm <?= !$status ? 'border-primary text-primary' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' ?>">
            All Orders
            <span class="bg-gray-100 text-gray-900 ml-2 py-0.5 px-2 rounded-full text-xs font-medium"><?= $total_records ?></span>
        </a>
        <?php foreach ($statuses as $key => $status_info): 
            if ($key === '') continue; // Skip the 'all' status since we handle it separately
            $count = $status_info['count'] ?? 0;
        ?>
            <a href="?status=<?= $key ?>" class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm <?= $status === $key ? 'border-primary text-primary' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' ?>">
                <?= $status_info['name'] ?>
                <span class="bg-gray-100 text-gray-900 ml-2 py-0.5 px-2 rounded-full text-xs font-medium"><?= $count ?></span>
            </a>
        <?php endforeach; ?>
    </nav>
</div>

<!-- Search and Filter Form -->
<div class="bg-white shadow-lg rounded-xl p-6 mb-8">
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div>
            <label for="search" class="block text-sm font-semibold text-gray-700 mb-2">Search Orders</label>
            <div class="relative">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <i class="fas fa-search text-gray-400"></i>
                </div>
                <input type="text" name="search" id="search" value="<?= htmlspecialchars($search) ?>"
                       class="block w-full pl-10 pr-3 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-red-500 sm:text-sm"
                       placeholder="Order #, Customer, Email...">
            </div>
        </div>
        <div>
            <label for="date_from" class="block text-sm font-semibold text-gray-700 mb-2">From Date</label>
            <input type="date" name="date_from" id="date_from" value="<?= htmlspecialchars($date_from) ?>"
                   class="block w-full px-3 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-red-500 sm:text-sm">
        </div>
        <div>
            <label for="date_to" class="block text-sm font-semibold text-gray-700 mb-2">To Date</label>
            <input type="date" name="date_to" id="date_to" value="<?= htmlspecialchars($date_to) ?>"
                   class="block w-full px-3 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-red-500 sm:text-sm">
        </div>
        <div class="flex items-end space-x-3">
            <button type="submit" class="flex-1 bg-red-600 hover:bg-red-700 text-white px-4 py-3 rounded-lg font-semibold transition-colors duration-200 flex items-center justify-center">
                <i class="fas fa-filter mr-2"></i> Apply Filters
            </button>
            <?php if (!empty($status) || !empty($date_from) || !empty($date_to) || !empty($search)): ?>
                <a href="orders.php" class="px-4 py-3 border-2 border-gray-300 text-gray-600 rounded-lg hover:bg-gray-50 transition-colors duration-200 flex items-center justify-center">
                    <i class="fas fa-times"></i>
                </a>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Dashboard Header -->
<div class="bg-gradient-to-br from-red-600 via-red-700 to-red-800 text-white">
    <div class="container mx-auto px-6 py-8">
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between">
            <div>
                <h1 class="text-3xl md:text-4xl font-bold mb-2">Order Management</h1>
                <p class="text-lg opacity-90">Manage customer orders, track deliveries, and monitor business performance</p>
            </div>
            <div class="mt-4 lg:mt-0">
                <a href="orders.php"
                   class="bg-green-600 hover:bg-green-700 text-white px-6 py-3 rounded-lg font-semibold transition-colors duration-200 flex items-center shadow-lg hover:shadow-xl transform hover:-translate-y-1">
                    <i class="fas fa-plus mr-2"></i>
                    Add New Order
                </a>
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
                            <?php
                            $delivered_count = $pdo->prepare("SELECT COUNT(*) FROM orders WHERE status = 'delivered'");
                            $delivered_count->execute();
                            echo number_format($delivered_count->fetchColumn());
                            ?>
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
                            <?php
                            $pending_count = $pdo->prepare("SELECT COUNT(*) FROM orders WHERE status = 'pending'");
                            $pending_count->execute();
                            echo number_format($pending_count->fetchColumn());
                            ?>
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
                            $total_revenue = $pdo->prepare("SELECT SUM(total) FROM orders WHERE status = 'delivered'");
                            $total_revenue->execute();
                            echo number_format($total_revenue->fetchColumn() ?: 0, 0);
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
    <?php if (isset($_SESSION['message'])): ?>
        <?php $message = $_SESSION['message']; ?>
        <div class="mb-6 p-4 bg-<?php echo $message['type'] === 'success' ? 'green' : 'red'; ?>-50 border-l-4 border-<?php echo $message['type'] === 'success' ? 'green' : 'red'; ?>-400 text-<?php echo $message['type'] === 'success' ? 'green' : 'red'; ?>-700 rounded-lg flex items-center">
            <i class="fas fa-<?php echo $message['type'] === 'success' ? 'check' : 'exclamation'; ?>-circle mr-3"></i>
            <div>
                <p class="font-semibold"><?php echo ucfirst($message['type']); ?></p>
                <p><?php echo htmlspecialchars($message['text']); ?></p>
            </div>
            <button onclick="this.parentElement.remove()" class="ml-auto text-gray-400 hover:text-gray-600">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <?php unset($_SESSION['message']); ?>
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
                <button class="filter-tab <?php echo empty($status) ? 'active' : ''; ?>" data-filter="all">
                    All Orders <span class="ml-1 bg-gray-100 text-gray-600 px-2 py-1 rounded-full text-xs"><?php echo number_format($total_records); ?></span>
                </button>
                <button class="filter-tab <?php echo $status === 'pending' ? 'active' : ''; ?>" data-filter="pending">
                    Pending <span class="ml-1 bg-yellow-100 text-yellow-600 px-2 py-1 rounded-full text-xs">
                        <?php
                        $pending_count = $pdo->prepare("SELECT COUNT(*) FROM orders WHERE status = 'pending'");
                        $pending_count->execute();
                        echo number_format($pending_count->fetchColumn());
                        ?>
                    </span>
                </button>
                <button class="filter-tab <?php echo $status === 'processing' ? 'active' : ''; ?>" data-filter="processing">
                    Processing <span class="ml-1 bg-blue-100 text-blue-600 px-2 py-1 rounded-full text-xs">
                        <?php
                        $processing_count = $pdo->prepare("SELECT COUNT(*) FROM orders WHERE status = 'processing'");
                        $processing_count->execute();
                        echo number_format($processing_count->fetchColumn());
                        ?>
                    </span>
                </button>
                <button class="filter-tab <?php echo $status === 'delivered' ? 'active' : ''; ?>" data-filter="delivered">
                    Delivered <span class="ml-1 bg-green-100 text-green-600 px-2 py-1 rounded-full text-xs">
                        <?php
                        $delivered_count = $pdo->prepare("SELECT COUNT(*) FROM orders WHERE status = 'delivered'");
                        $delivered_count->execute();
                        echo number_format($delivered_count->fetchColumn());
                        ?>
                    </span>
                </button>
                <?php if ($search || $date_from || $date_to): ?>
                    <button onclick="clearFilters()" class="ml-4 text-red-600 hover:text-red-700 font-medium">
                        <i class="fas fa-times mr-1"></i>Clear Filters
                    </button>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php if (count($orders) > 0): ?>
        <?php foreach ($orders as $order): ?>
        <div class="bg-white rounded-xl shadow-lg border border-gray-200 hover:shadow-xl transition-all duration-300 overflow-hidden order-card"
             data-order-number="<?= htmlspecialchars($order['order_number']) ?>"
             data-customer-name="<?= htmlspecialchars(strtolower($order['customer_name'])) ?>"
             data-customer-email="<?= htmlspecialchars(strtolower($order['customer_email'])) ?>"
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
                                        'confirmed' => 'bg-green-200 text-green-800',
                                        'delivered' => 'bg-purple-200 text-purple-800',
                                        'cancelled' => 'bg-red-200 text-red-800',
                                        default => 'bg-gray-200 text-gray-800'
                                    };
                                ?>">
                                    <i class="fas fa-<?php
                                        echo match($order['status']) {
                                            'pending' => 'clock',
                                            'processing' => 'cog',
                                            'confirmed' => 'check-circle',
                                            'delivered' => 'truck',
                                            'cancelled' => 'times-circle'
                                        };
                                    ?> mr-1 text-xs"></i>
                                    <?php echo ucfirst($order['status']); ?>
                                </span>
                                <span class="text-lg font-bold text-red-600">
                                    KES <?= number_format($order['total_amount'], 2) ?>
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Quick CRUD Actions -->
                    <div class="mt-4 lg:mt-0 flex flex-wrap gap-2">
                        <a href="order_view.php?id=<?= $order['id'] ?>"
                           class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg font-semibold transition-all duration-200 flex items-center space-x-2 shadow-md hover:shadow-lg transform hover:-translate-y-0.5">
                            <i class="fas fa-eye"></i>
                            <span>View</span>
                        </a>

                        <?php if ($order['status'] === 'pending'): ?>
                        <a href="update_status.php?id=<?= $order['id'] ?>&status=processing"
                           class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg font-semibold transition-all duration-200 flex items-center space-x-2 shadow-md hover:shadow-lg transform hover:-translate-y-0.5">
                            <i class="fas fa-play"></i>
                            <span>Process</span>
                        </a>
                        <?php endif; ?>

                        <?php if (in_array($order['status'], ['processing', 'confirmed'])): ?>
                        <a href="update_status.php?id=<?= $order['id'] ?>&status=delivered"
                           class="bg-emerald-600 hover:bg-emerald-700 text-white px-4 py-2 rounded-lg font-semibold transition-all duration-200 flex items-center space-x-2 shadow-md hover:shadow-lg transform hover:-translate-y-0.5">
                            <i class="fas fa-check"></i>
                            <span>Deliver</span>
                        </a>
                        <?php endif; ?>

                        <a href="order_edit.php?id=<?= $order['id'] ?>"
                           class="bg-purple-600 hover:bg-purple-700 text-white px-4 py-2 rounded-lg font-semibold transition-all duration-200 flex items-center space-x-2 shadow-md hover:shadow-lg transform hover:-translate-y-0.5">
                            <i class="fas fa-edit"></i>
                            <span>Edit</span>
                        </a>

                        <button onclick="confirmDelete(<?= $order['id'] ?>, '<?= htmlspecialchars($order['order_number']) ?>')"
                                class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg font-semibold transition-all duration-200 flex items-center space-x-2 shadow-md hover:shadow-lg transform hover:-translate-y-0.5">
                            <i class="fas fa-trash"></i>
                            <span>Delete</span>
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
                            <p class="text-gray-700"><span class="font-medium">Name:</span> <?php echo htmlspecialchars($order['customer_name']); ?></p>
                            <p class="text-gray-700"><span class="font-medium">Email:</span> <?php echo htmlspecialchars($order['customer_email']); ?></p>
                            <p class="text-gray-700"><span class="font-medium">Phone:</span> <?php echo htmlspecialchars($order['customer_phone']); ?></p>
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
                            <p class="text-gray-700"><span class="font-medium">Items:</span> <?php echo htmlspecialchars(substr($order['item_names'] ?: 'No items', 0, 50) . (strlen($order['item_names'] ?: '') > 50 ? '...' : '')); ?></p>
                            <p class="text-gray-700"><span class="font-medium">Payment:</span> <?php echo ucfirst(str_replace('_', ' ', $order['payment_method'])); ?></p>
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
                            <p class="text-xl font-bold text-red-600">KES <?php echo number_format($order['total_amount'], 2); ?></p>
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
                    <?php if (!empty($search) || !empty($status) || !empty($date_from) || !empty($date_to)): ?>
                        No orders match your current filters. Try adjusting your search criteria.
                    <?php else: ?>
                        No orders have been placed yet. Orders will appear here once customers start ordering.
                    <?php endif; ?>
                </p>
                <?php if (!empty($search) || !empty($status) || !empty($date_from) || !empty($date_to)): ?>
                    <a href="orders.php" class="inline-flex items-center justify-center px-6 py-3 bg-red-600 text-white font-bold rounded-lg hover:bg-red-700 transition-colors">
                        <i class="fas fa-times mr-2"></i>
                        Clear Filters
                    </a>
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
        <a href="?page=<?php echo $page - 1; ?><?php echo $status ? '&status=' . urlencode($status) : ''; ?><?php echo $date_from ? '&date_from=' . urlencode($date_from) : ''; ?><?php echo $date_to ? '&date_to=' . urlencode($date_to) : ''; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?>"
           class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors font-medium">
            <i class="fas fa-chevron-left mr-1"></i> Previous
        </a>
        <?php endif; ?>

        <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
        <a href="?page=<?php echo $i; ?><?php echo $status ? '&status=' . urlencode($status) : ''; ?><?php echo $date_from ? '&date_from=' . urlencode($date_from) : ''; ?><?php echo $date_to ? '&date_to=' . urlencode($date_to) : ''; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?>"
           class="px-4 py-2 border rounded-lg transition-colors font-medium <?php echo $i === $page ? 'bg-red-600 text-white border-red-600' : 'border-gray-300 hover:bg-gray-50'; ?>">
            <?php echo $i; ?>
        </a>
        <?php endfor; ?>

        <?php if ($page < $total_pages): ?>
        <a href="?page=<?php echo $page + 1; ?><?php echo $status ? '&status=' . urlencode($status) : ''; ?><?php echo $date_from ? '&date_from=' . urlencode($date_from) : ''; ?><?php echo $date_to ? '&date_to=' . urlencode($date_to) : ''; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?>"
           class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors font-medium">
            Next <i class="fas fa-chevron-right ml-1"></i>
        </a>
        <?php endif; ?>
    </nav>
</div>
<?php endif; ?>

<!-- Summary Stats -->
<?php if (count($orders) > 0): ?>
<div class="mt-8 grid grid-cols-2 md:grid-cols-4 gap-6">
    <div class="bg-white rounded-lg shadow-md p-6 text-center border-l-4 border-blue-500">
        <div class="text-2xl font-bold text-blue-600 mb-2"><?php echo $total_records; ?></div>
        <div class="text-gray-600">Total Orders</div>
    </div>
    <div class="bg-white rounded-lg shadow-md p-6 text-center border-l-4 border-green-500">
        <div class="text-2xl font-bold text-green-600 mb-2">
            <?php
            $delivered_count = $pdo->prepare("SELECT COUNT(*) FROM orders WHERE status = 'delivered'");
            $delivered_count->execute();
            echo $delivered_count->fetchColumn();
            ?>
        </div>
        <div class="text-gray-600">Delivered</div>
    </div>
    <div class="bg-white rounded-lg shadow-md p-6 text-center border-l-4 border-yellow-500">
        <div class="text-2xl font-bold text-yellow-600 mb-2">
            <?php
            $pending_count = $pdo->prepare("SELECT COUNT(*) FROM orders WHERE status = 'pending'");
            $pending_count->execute();
            echo $pending_count->fetchColumn();
            ?>
        </div>
        <div class="text-gray-600">Pending</div>
    </div>
    <div class="bg-white rounded-lg shadow-md p-6 text-center border-l-4 border-purple-500">
        <div class="text-2xl font-bold text-purple-600 mb-2">
            KES <?php
            $total_revenue = $pdo->prepare("SELECT SUM(total) FROM orders WHERE status = 'delivered'");
            $total_revenue->execute();
            echo number_format($total_revenue->fetchColumn() ?: 0, 2);
            ?>
        </div>
        <div class="text-gray-600">Revenue</div>
    </div>
</div>
<?php endif; ?>

<!-- Delete Confirmation Modal -->
<div id="deleteModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
    <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
        <div class="mt-3 text-center">
            <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-red-100 mb-4">
                <i class="fas fa-exclamation-triangle text-red-600 text-xl"></i>
            </div>
            <h3 class="text-lg font-medium text-gray-900 mb-2">Delete Order</h3>
            <div class="mt-2 px-7 py-3">
                <p class="text-sm text-gray-500">
                    Are you sure you want to delete order <span id="deleteOrderNumber" class="font-semibold"></span>?
                    This action cannot be undone.
                </p>
            </div>
            <div class="items-center px-4 py-3 flex justify-center space-x-3">
                <button id="confirmDeleteBtn" class="px-4 py-2 bg-red-600 text-white text-base font-medium rounded-md shadow-sm hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-300">
                    Delete Order
                </button>
                <button onclick="closeDeleteModal()" class="px-4 py-2 bg-gray-300 text-gray-700 text-base font-medium rounded-md shadow-sm hover:bg-gray-400 focus:outline-none focus:ring-2 focus:ring-gray-300">
                    Cancel
                </button>
            </div>
        </div>
    </div>
</div>

<script>
// Enhanced filtering functionality
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('searchInput');
    const dateFromFilter = document.getElementById('dateFromFilter');
    const dateToFilter = document.getElementById('dateToFilter');
    const filterTabs = document.querySelectorAll('.filter-tab');

    // Search functionality
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase();
            filterOrders();

            // Update URL without page reload
            const url = new URL(window.location);
            if (searchTerm) {
                url.searchParams.set('search', searchTerm);
            } else {
                url.searchParams.delete('search');
            }
            window.history.pushState({}, '', url);
        });
    }

    // Date filters
    if (dateFromFilter) {
        dateFromFilter.addEventListener('change', function() {
            filterOrders();
            const url = new URL(window.location);
            if (this.value) {
                url.searchParams.set('date_from', this.value);
            } else {
                url.searchParams.delete('date_from');
            }
            window.history.pushState({}, '', url);
        });
    }

    if (dateToFilter) {
        dateToFilter.addEventListener('change', function() {
            filterOrders();
            const url = new URL(window.location);
            if (this.value) {
                url.searchParams.set('date_to', this.value);
            } else {
                url.searchParams.delete('date_to');
            }
            window.history.pushState({}, '', url);
        });
    }

    // Filter tabs
    filterTabs.forEach(tab => {
        tab.addEventListener('click', function() {
            const filter = this.dataset.filter;

            // Update active tab
            filterTabs.forEach(t => t.classList.remove('active'));
            this.classList.add('active');

            // Update status in URL
            const url = new URL(window.location);
            if (filter === 'all') {
                url.searchParams.delete('status');
            } else {
                url.searchParams.set('status', filter);
            }
            window.history.pushState({}, '', url);

            // Trigger filtering
            filterOrders();
        });
    });
});

function filterOrders() {
    const searchTerm = document.getElementById('searchInput')?.value.toLowerCase() || '';
    const dateFrom = document.getElementById('dateFromFilter')?.value || '';
    const dateTo = document.getElementById('dateToFilter')?.value || '';

    const orders = document.querySelectorAll('.order-card');
    let visibleCount = 0;

    orders.forEach(order => {
        const orderNumber = order.dataset.orderNumber?.toLowerCase() || '';
        const customerName = order.dataset.customerName?.toLowerCase() || '';
        const customerEmail = order.dataset.customerEmail?.toLowerCase() || '';
        const orderDate = order.dataset.orderDate || '';
        const orderStatus = order.dataset.orderStatus || '';

        let shouldShow = true;

        // Search filter
        if (searchTerm && !orderNumber.includes(searchTerm) &&
            !customerName.includes(searchTerm) &&
            !customerEmail.includes(searchTerm)) {
            shouldShow = false;
        }

        // Date range filter
        if (shouldShow && (dateFrom || dateTo)) {
            if (dateFrom && orderDate < dateFrom) {
                shouldShow = false;
            }
            if (dateTo && orderDate > dateTo) {
                shouldShow = false;
            }
        }

        if (shouldShow) {
            order.style.display = 'block';
            visibleCount++;
        } else {
            order.style.display = 'none';
        }
    });

    // Show/hide empty state
    updateEmptyState(visibleCount);
}

function updateEmptyState(visibleCount) {
    let emptyState = document.querySelector('.empty-state');
    const ordersContainer = document.querySelector('.orders-container');

    if (visibleCount === 0) {
        if (!emptyState) {
            emptyState = document.createElement('div');
            emptyState.className = 'empty-state col-span-full text-center py-16';
            emptyState.innerHTML = `
                <i class="fas fa-search text-6xl text-gray-300 mb-4"></i>
                <h3 class="text-2xl font-semibold text-gray-700 mb-2">No orders found</h3>
                <p class="text-gray-500">Try adjusting your search or filter criteria.</p>
            `;
            ordersContainer.parentNode.insertBefore(emptyState, ordersContainer.nextSibling);
        }
    } else {
        if (emptyState) {
            emptyState.remove();
        }
    }
}

function clearFilters() {
    document.getElementById('searchInput').value = '';
    document.getElementById('dateFromFilter').value = '';
    document.getElementById('dateToFilter').value = '';

    // Reset active tab
    document.querySelectorAll('.filter-tab').forEach(tab => {
        tab.classList.toggle('active', tab.dataset.filter === 'all');
    });

    filterOrders();

    // Update URL
    const url = new URL(window.location);
    url.searchParams.delete('search');
    url.searchParams.delete('date_from');
    url.searchParams.delete('date_to');
    url.searchParams.delete('status');
    window.history.pushState({}, '', url);
}

// Status update functions
function updateOrderStatus(orderId, newStatus) {
    if (confirm(`Are you sure you want to mark this order as ${newStatus}?`)) {
        window.location.href = `update_status.php?id=${orderId}&status=${newStatus}`;
    }
}

// Enhanced delete confirmation
function confirmDelete(orderId, orderNumber) {
    document.getElementById('deleteOrderNumber').textContent = orderNumber;
    document.getElementById('confirmDeleteBtn').onclick = function() {
        window.location.href = 'orders.php?_method=DELETE&id=' + orderId;
    };
    document.getElementById('deleteModal').classList.remove('hidden');
}

function closeDeleteModal() {
    document.getElementById('deleteModal').classList.add('hidden');
}

// Close modal when clicking outside
document.getElementById('deleteModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeDeleteModal();
    }
});
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

/* Enhanced card hover effects */
.order-card {
    transition: all 0.3s ease;
}

.order-card:hover {
    transform: translateY(-2px);
}

/* Status badge styling */
.status-badge {
    @apply inline-flex items-center px-3 py-1 rounded-full text-sm font-semibold;
}

/* Custom scrollbar for order cards */
.orders-container {
    max-height: calc(100vh - 400px);
    overflow-y: auto;
}

.orders-container::-webkit-scrollbar {
    width: 6px;
}

.orders-container::-webkit-scrollbar-track {
    background: #f1f5f9;
    border-radius: 3px;
}

.orders-container::-webkit-scrollbar-thumb {
    background: #cbd5e1;
    border-radius: 3px;
}

.orders-container::-webkit-scrollbar-thumb:hover {
    background: #94a3b8;
}

/* Enhanced button styling */
.btn-action {
    @apply px-4 py-2 rounded-lg font-medium transition-all duration-200 transform hover:-translate-y-0.5;
}

.btn-primary {
    @apply bg-red-600 text-white hover:bg-red-700;
}

.btn-secondary {
    @apply bg-gray-100 text-gray-700 hover:bg-gray-200;
}

.btn-success {
    @apply bg-green-600 text-white hover:bg-green-700;
}

.btn-warning {
    @apply bg-yellow-600 text-white hover:bg-yellow-700;
}

.btn-danger {
    @apply bg-red-100 text-red-700 hover:bg-red-200 border border-red-200;
}
</style>

<?php include 'includes/footer.php'; ?>