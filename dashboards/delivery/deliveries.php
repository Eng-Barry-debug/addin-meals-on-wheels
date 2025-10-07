<?php
// Start session and check delivery authentication
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include database connection and functions
require_once '../../includes/config.php';

// Check if user is logged in and is delivery personnel
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'delivery') {
    header('Location: ../../auth/login.php');
    exit();
}

// Set page title
$page_title = 'My Deliveries';

// Initialize variables
$pendingDeliveries = [];
$outForDelivery = [];
$completedDeliveries = [];
$error = null;

// Get delivery data
try {
    global $pdo;

    // Get pending deliveries (ready for pickup)
    $pendingStmt = $pdo->prepare("
        SELECT o.*, u.name as customer_name, u.phone as customer_phone,
               CONCAT(o.delivery_address, ', ', COALESCE(o.delivery_instructions, '')) as full_address
        FROM orders o
        JOIN users u ON o.user_id = u.id
        WHERE o.status = 'ready_for_delivery'
        ORDER BY o.created_at ASC
        LIMIT 20
    ");
    $pendingStmt->execute();
    $pendingDeliveries = $pendingStmt->fetchAll(PDO::FETCH_ASSOC);

    // Get deliveries out for delivery
    $outForDeliveryStmt = $pdo->prepare("
        SELECT o.*, u.name as customer_name, u.phone as customer_phone,
               CONCAT(o.delivery_address, ', ', COALESCE(o.delivery_instructions, '')) as full_address
        FROM orders o
        JOIN users u ON o.user_id = u.id
        WHERE o.status = 'out_for_delivery'
        ORDER BY o.updated_at DESC
        LIMIT 20
    ");
    $outForDeliveryStmt->execute();
    $outForDelivery = $outForDeliveryStmt->fetchAll(PDO::FETCH_ASSOC);

    // Get completed deliveries today
    $completedStmt = $pdo->prepare("
        SELECT o.*, u.name as customer_name, u.phone as customer_phone,
               CONCAT(o.delivery_address, ', ', COALESCE(o.delivery_instructions, '')) as full_address
        FROM orders o
        JOIN users u ON o.user_id = u.id
        WHERE o.status = 'delivered' AND DATE(o.updated_at) = CURDATE()
        ORDER BY o.updated_at DESC
        LIMIT 10
    ");
    $completedStmt->execute();
    $completedDeliveries = $completedStmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $error = "Database error: " . $e->getMessage();
    error_log("Delivery deliveries page error: " . $e->getMessage());
}

// Handle delivery actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && isset($_POST['order_id'])) {
        $orderId = (int)$_POST['order_id'];
        $action = $_POST['action'];

        try {
            global $pdo;

            switch ($action) {
                case 'accept_delivery':
                    // Update order status to out_for_delivery
                    $stmt = $pdo->prepare("UPDATE orders SET status = 'out_for_delivery', updated_at = NOW() WHERE id = ?");
                    $stmt->execute([$orderId]);
                    $_SESSION['message'] = ['type' => 'success', 'text' => 'Delivery accepted successfully'];
                    break;

                case 'mark_delivered':
                    // Update order status to delivered
                    $stmt = $pdo->prepare("UPDATE orders SET status = 'delivered', updated_at = NOW() WHERE id = ?");
                    $stmt->execute([$orderId]);
                    $_SESSION['message'] = ['type' => 'success', 'text' => 'Delivery marked as completed'];
                    break;

                case 'mark_failed':
                    // Update order status to delivery_failed
                    $reason = $_POST['failure_reason'] ?? 'Delivery failed';
                    $stmt = $pdo->prepare("UPDATE orders SET status = 'delivery_failed', updated_at = NOW() WHERE id = ?");
                    $stmt->execute([$orderId]);
                    $_SESSION['message'] = ['type' => 'warning', 'text' => 'Delivery marked as failed'];
                    break;
            }

            // Log the action
            require_once '../../includes/ActivityLogger.php';
            $activityLogger = new ActivityLogger($pdo);
            $activityLogger->log('delivery', $action, "Order {$orderId} {$action}", 'order', $orderId);

            header('Location: deliveries.php');
            exit();

        } catch (PDOException $e) {
            $_SESSION['message'] = ['type' => 'error', 'text' => 'Database error: ' . $e->getMessage()];
        }
    }
}

// Include header
require_once 'includes/header.php';
?>

<!-- Page Header -->
<div class="bg-gradient-to-br from-primary via-primary-dark to-green-700 text-white">
    <div class="px-6 py-8">
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between">
            <div>
                <h1 class="text-3xl md:text-4xl font-bold mb-2">My Deliveries</h1>
                <p class="text-lg opacity-90">Manage your delivery assignments and track progress</p>
            </div>
            <div class="mt-4 lg:mt-0">
                <div class="flex items-center space-x-4">
                    <div class="bg-white/10 backdrop-blur-sm rounded-lg px-4 py-2">
                        <span class="text-sm">Status: <span class="font-semibold text-green-300">Active</span></span>
                    </div>
                    <div class="bg-white/10 backdrop-blur-sm rounded-lg px-4 py-2">
                        <span class="text-sm">Available: <span class="font-semibold"><?php echo count($pendingDeliveries); ?></span></span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Main Content -->
<div class="px-6 py-8">
    <?php if (isset($_SESSION['message'])): ?>
        <div class="mb-6 p-4 rounded-lg <?php echo $_SESSION['message']['type'] === 'success' ? 'bg-green-50 text-green-800 border-l-4 border-green-400' : ($_SESSION['message']['type'] === 'warning' ? 'bg-yellow-50 text-yellow-800 border-l-4 border-yellow-400' : 'bg-red-50 text-red-800 border-l-4 border-red-400'); ?>">
            <?php echo htmlspecialchars($_SESSION['message']['text']); ?>
            <?php unset($_SESSION['message']); ?>
        </div>
    <?php endif; ?>

    <?php if (isset($error)): ?>
        <div class="mb-6 p-4 bg-red-50 border-l-4 border-red-400 text-red-700 rounded-lg">
            <p class="font-semibold">Error</p>
            <p><?php echo htmlspecialchars($error); ?></p>
        </div>
    <?php endif; ?>

    <!-- Delivery Tabs -->
    <div class="mb-8">
        <div class="border-b border-gray-200">
            <nav class="-mb-px flex space-x-8">
                <button onclick="showTab('pending')" class="tab-button active whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm border-primary text-primary">
                    Available (<?php echo count($pendingDeliveries); ?>)
                </button>
                <button onclick="showTab('active')" class="tab-button whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300">
                    Out for Delivery (<?php echo count($outForDelivery); ?>)
                </button>
                <button onclick="showTab('completed')" class="tab-button whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300">
                    Completed Today (<?php echo count($completedDeliveries); ?>)
                </button>
            </nav>
        </div>
    </div>

    <!-- Pending Deliveries Tab -->
    <div id="pending-tab" class="tab-content">
        <?php if (!empty($pendingDeliveries)): ?>
            <div class="grid gap-6">
                <?php foreach ($pendingDeliveries as $delivery): ?>
                    <div class="bg-white rounded-lg shadow-md p-6 border-l-4 border-yellow-400">
                        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between">
                            <div class="flex-1">
                                <div class="flex items-center space-x-3 mb-3">
                                    <div class="w-12 h-12 bg-yellow-100 rounded-full flex items-center justify-center">
                                        <i class="fas fa-clock text-yellow-600 text-lg"></i>
                                    </div>
                                    <div>
                                        <h3 class="text-lg font-semibold text-gray-900">Order #<?php echo $delivery['id']; ?></h3>
                                        <p class="text-sm text-gray-600">Ready for pickup</p>
                                    </div>
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                                    <div>
                                        <p class="text-sm text-gray-600 mb-1">Customer</p>
                                        <p class="font-medium text-gray-900"><?php echo htmlspecialchars($delivery['customer_name']); ?></p>
                                        <p class="text-sm text-gray-600"><?php echo htmlspecialchars($delivery['customer_phone']); ?></p>
                                    </div>
                                    <div>
                                        <p class="text-sm text-gray-600 mb-1">Delivery Address</p>
                                        <p class="font-medium text-gray-900"><?php echo htmlspecialchars($delivery['full_address']); ?></p>
                                    </div>
                                </div>

                                <div class="flex items-center space-x-4 text-sm text-gray-600">
                                    <span><i class="fas fa-calendar mr-1"></i><?php echo date('M j, Y g:i A', strtotime($delivery['created_at'])); ?></span>
                                    <span><i class="fas fa-dollar-sign mr-1"></i>KES <?php echo number_format($delivery['total'], 2); ?></span>
                                </div>
                            </div>

                            <div class="mt-4 lg:mt-0 lg:ml-6">
                                <form method="POST" class="flex space-x-2">
                                    <input type="hidden" name="order_id" value="<?php echo $delivery['id']; ?>">
                                    <input type="hidden" name="action" value="accept_delivery">
                                    <button type="submit"
                                            class="bg-primary hover:bg-primary-dark text-white px-4 py-2 rounded-lg font-medium transition-colors flex items-center">
                                        <i class="fas fa-check mr-2"></i>
                                        Accept Delivery
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="text-center py-12">
                <i class="fas fa-box-open text-6xl text-gray-300 mb-4"></i>
                <h3 class="text-xl font-medium text-gray-900 mb-2">No deliveries available</h3>
                <p class="text-gray-600">Check back later for new delivery assignments.</p>
            </div>
        <?php endif; ?>
    </div>

    <!-- Active Deliveries Tab -->
    <div id="active-tab" class="tab-content hidden">
        <?php if (!empty($outForDelivery)): ?>
            <div class="grid gap-6">
                <?php foreach ($outForDelivery as $delivery): ?>
                    <div class="bg-white rounded-lg shadow-md p-6 border-l-4 border-blue-400">
                        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between">
                            <div class="flex-1">
                                <div class="flex items-center space-x-3 mb-3">
                                    <div class="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center">
                                        <i class="fas fa-truck text-blue-600 text-lg"></i>
                                    </div>
                                    <div>
                                        <h3 class="text-lg font-semibold text-gray-900">Order #<?php echo $delivery['id']; ?></h3>
                                        <p class="text-sm text-gray-600">Out for delivery</p>
                                    </div>
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                                    <div>
                                        <p class="text-sm text-gray-600 mb-1">Customer</p>
                                        <p class="font-medium text-gray-900"><?php echo htmlspecialchars($delivery['customer_name']); ?></p>
                                        <p class="text-sm text-gray-600"><?php echo htmlspecialchars($delivery['customer_phone']); ?></p>
                                    </div>
                                    <div>
                                        <p class="text-sm text-gray-600 mb-1">Delivery Address</p>
                                        <p class="font-medium text-gray-900"><?php echo htmlspecialchars($delivery['full_address']); ?></p>
                                    </div>
                                </div>

                                <div class="flex items-center space-x-4 text-sm text-gray-600">
                                    <span><i class="fas fa-clock mr-1"></i>Started: <?php echo date('M j, g:i A', strtotime($delivery['updated_at'])); ?></span>
                                    <span><i class="fas fa-dollar-sign mr-1"></i>KES <?php echo number_format($delivery['total'], 2); ?></span>
                                </div>
                            </div>

                            <div class="mt-4 lg:mt-0 lg:ml-6">
                                <div class="flex space-x-2">
                                    <button onclick="openMap('<?php echo urlencode($delivery['delivery_address']); ?>')"
                                            class="bg-accent hover:bg-blue-600 text-white px-3 py-2 rounded-lg font-medium transition-colors flex items-center text-sm">
                                        <i class="fas fa-map-marker-alt mr-1"></i>
                                        Map
                                    </button>
                                    <button onclick="callCustomer('<?php echo $delivery['customer_phone']; ?>')"
                                            class="bg-green-600 hover:bg-green-700 text-white px-3 py-2 rounded-lg font-medium transition-colors flex items-center text-sm">
                                        <i class="fas fa-phone mr-1"></i>
                                        Call
                                    </button>
                                    <form method="POST" class="inline">
                                        <input type="hidden" name="order_id" value="<?php echo $delivery['id']; ?>">
                                        <input type="hidden" name="action" value="mark_delivered">
                                        <button type="submit"
                                                class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg font-medium transition-colors flex items-center">
                                            <i class="fas fa-check mr-2"></i>
                                            Delivered
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="text-center py-12">
                <i class="fas fa-truck text-6xl text-gray-300 mb-4"></i>
                <h3 class="text-xl font-medium text-gray-900 mb-2">No active deliveries</h3>
                <p class="text-gray-600">Accept some deliveries to get started.</p>
            </div>
        <?php endif; ?>
    </div>

    <!-- Completed Deliveries Tab -->
    <div id="completed-tab" class="tab-content hidden">
        <?php if (!empty($completedDeliveries)): ?>
            <div class="grid gap-6">
                <?php foreach ($completedDeliveries as $delivery): ?>
                    <div class="bg-white rounded-lg shadow-md p-6 border-l-4 border-green-400">
                        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between">
                            <div class="flex-1">
                                <div class="flex items-center space-x-3 mb-3">
                                    <div class="w-12 h-12 bg-green-100 rounded-full flex items-center justify-center">
                                        <i class="fas fa-check-circle text-green-600 text-lg"></i>
                                    </div>
                                    <div>
                                        <h3 class="text-lg font-semibold text-gray-900">Order #<?php echo $delivery['id']; ?></h3>
                                        <p class="text-sm text-gray-600">Successfully delivered</p>
                                    </div>
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                                    <div>
                                        <p class="text-sm text-gray-600 mb-1">Customer</p>
                                        <p class="font-medium text-gray-900"><?php echo htmlspecialchars($delivery['customer_name']); ?></p>
                                        <p class="text-sm text-gray-600"><?php echo htmlspecialchars($delivery['customer_phone']); ?></p>
                                    </div>
                                    <div>
                                        <p class="text-sm text-gray-600 mb-1">Delivery Address</p>
                                        <p class="font-medium text-gray-900"><?php echo htmlspecialchars($delivery['full_address']); ?></p>
                                    </div>
                                </div>

                                <div class="flex items-center space-x-4 text-sm text-gray-600">
                                    <span><i class="fas fa-clock mr-1"></i>Delivered: <?php echo date('M j, g:i A', strtotime($delivery['updated_at'])); ?></span>
                                    <span><i class="fas fa-dollar-sign mr-1"></i>KES <?php echo number_format($delivery['total'], 2); ?></span>
                                </div>
                            </div>

                            <div class="mt-4 lg:mt-0 lg:ml-6">
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-green-100 text-green-800">
                                    <i class="fas fa-check mr-1"></i>
                                    Completed
                                </span>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="text-center py-12">
                <i class="fas fa-check-circle text-6xl text-gray-300 mb-4"></i>
                <h3 class="text-xl font-medium text-gray-900 mb-2">No completed deliveries today</h3>
                <p class="text-gray-600">Your completed deliveries will appear here.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
function showTab(tabName) {
    // Hide all tabs
    document.querySelectorAll('.tab-content').forEach(tab => {
        tab.classList.add('hidden');
    });

    // Remove active state from all buttons
    document.querySelectorAll('.tab-button').forEach(button => {
        button.classList.remove('border-primary', 'text-primary');
        button.classList.add('border-transparent', 'text-gray-500');
    });

    // Show selected tab
    document.getElementById(tabName + '-tab').classList.remove('hidden');

    // Add active state to clicked button
    event.target.classList.remove('border-transparent', 'text-gray-500');
    event.target.classList.add('border-primary', 'text-primary');
}

function openMap(address) {
    // Open Google Maps or preferred mapping service
    const encodedAddress = encodeURIComponent(address);
    window.open(`https://www.google.com/maps/search/?api=1&query=${encodedAddress}`, '_blank');
}

function callCustomer(phone) {
    // Initiate phone call
    window.location.href = `tel:${phone}`;
}

// Auto-refresh every 30 seconds
setInterval(function() {
    location.reload();
}, 30000);
</script>

<?php require_once 'includes/footer.php'; ?>
