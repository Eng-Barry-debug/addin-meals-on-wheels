<?php
// Set page title and include header
$page_title = 'Edit Order';
$page_description = 'Modify order details and information';

// Include database connection and functions
require_once '../includes/config.php';
require_once 'includes/functions.php';

// Check if order ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['message'] = ['type' => 'error', 'text' => 'Order ID is required'];
    header('Location: orders.php');
    exit();
}

$order_id = (int)$_GET['id'];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate and sanitize input
    $customer_name = trim($_POST['customer_name'] ?? '');
    $customer_email = trim($_POST['customer_email'] ?? '');
    $customer_phone = trim($_POST['customer_phone'] ?? '');
    $delivery_address = trim($_POST['delivery_address'] ?? '');
    $delivery_instructions = trim($_POST['delivery_instructions'] ?? '');
    $payment_method = trim($_POST['payment_method'] ?? '');
    $payment_status = trim($_POST['payment_status'] ?? '');
    $status = trim($_POST['status'] ?? '');
    $notes = trim($_POST['notes'] ?? '');

    // Basic validation
    $errors = [];

    if (empty($customer_name)) $errors[] = 'Customer name is required';
    if (empty($customer_email)) $errors[] = 'Customer email is required';
    if (empty($customer_phone)) $errors[] = 'Customer phone is required';
    if (empty($delivery_address)) $errors[] = 'Delivery address is required';
    if (empty($payment_method)) $errors[] = 'Payment method is required';
    if (empty($status)) $errors[] = 'Order status is required';

    // Validate email format
    if (!filter_var($customer_email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email address';
    }

    // Validate phone format (basic validation)
    if (!preg_match('/^[0-9+\-\s()]{10,}$/', $customer_phone)) {
        $errors[] = 'Please enter a valid phone number';
    }

    if (empty($errors)) {
        // Initialize params array for error logging
        $params = [];

        try {
            // First check if order exists and get column information
            $check_stmt = $pdo->prepare("SELECT id FROM orders WHERE id = ?");
            $check_stmt->execute([$order_id]);
            if (!$check_stmt->fetch()) {
                $errors[] = 'Order not found';
            } else {
                // Check what columns actually exist in the orders table
                $column_stmt = $pdo->prepare("DESCRIBE orders");
                $column_stmt->execute();
                $columns = $column_stmt->fetchAll(PDO::FETCH_COLUMN);

                // Build dynamic update query based on existing columns
                $update_fields = [];
                $param_values = [];

                if (in_array('customer_name', $columns)) {
                    $update_fields[] = 'customer_name = ?';
                    $param_values[] = $customer_name;
                }

                if (in_array('customer_email', $columns)) {
                    $update_fields[] = 'customer_email = ?';
                    $param_values[] = $customer_email;
                }

                if (in_array('customer_phone', $columns)) {
                    $update_fields[] = 'customer_phone = ?';
                    $param_values[] = $customer_phone;
                }

                if (in_array('delivery_address', $columns)) {
                    $update_fields[] = 'delivery_address = ?';
                    $param_values[] = $delivery_address;
                }

                if (in_array('delivery_instructions', $columns)) {
                    $update_fields[] = 'delivery_instructions = ?';
                    $param_values[] = $delivery_instructions;
                }

                if (in_array('payment_method', $columns)) {
                    $update_fields[] = 'payment_method = ?';
                    $param_values[] = $payment_method;
                }

                if (in_array('payment_status', $columns)) {
                    $update_fields[] = 'payment_status = ?';
                    $param_values[] = $payment_status;
                }

                if (in_array('status', $columns)) {
                    $update_fields[] = 'status = ?';
                    $param_values[] = $status;
                }

                if (in_array('notes', $columns)) {
                    $update_fields[] = 'notes = ?';
                    $param_values[] = $notes;
                }

                // Add updated_at if column exists
                if (in_array('updated_at', $columns)) {
                    $update_fields[] = 'updated_at = NOW()';
                }

                if (!empty($update_fields)) {
                    $update_query = "UPDATE orders SET " . implode(', ', $update_fields) . " WHERE id = ?";
                    $param_values[] = $order_id;

                    $stmt = $pdo->prepare($update_query);

                    if ($stmt->execute($param_values)) {
                        // Log the activity (basic logging for now)
                        error_log("Order #$order_id updated by admin at " . date('Y-m-d H:i:s'));

                        $_SESSION['message'] = ['type' => 'success', 'text' => 'Order updated successfully'];
                        header('Location: order_view.php?id=' . $order_id);
                        exit();
                    } else {
                        $errors[] = 'Failed to update order. Please try again.';
                    }
                } else {
                    $errors[] = 'No valid fields to update.';
                }
            }
        } catch (PDOException $e) {
            error_log("Error updating order: " . $e->getMessage());
            error_log("SQL State: " . $e->getCode());
            error_log("Available columns: " . print_r($columns ?? [], true));
            error_log("Query params: " . print_r($param_values ?? [], true));

            // Provide more specific error messages based on error code
            $error_message = 'Database error occurred while updating order. ';
            switch ($e->getCode()) {
                case '42S22':
                    $error_message .= 'One or more columns do not exist in the database table.';
                    break;
                case '23000':
                    $error_message .= 'Data constraint violation. Please check your input values.';
                    break;
                case '22001':
                    $error_message .= 'Data too long for one or more fields.';
                    break;
                case '22007':
                    $error_message .= 'Invalid date format provided.';
                    break;
                default:
                    $error_message .= 'Please contact the administrator if this problem persists.';
            }

            $errors[] = $error_message;
        }
    }

    // Store form data for repopulation
    $form_data = $_POST;
}

// Fetch order details
$query = "SELECT o.*,
                u.name as customer_name,
                u.email as customer_email,
                u.phone as customer_phone
         FROM orders o
         LEFT JOIN users u ON o.user_id = u.id
         WHERE o.id = :order_id";

$params = [':order_id' => $order_id];

try {
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$order) {
        $_SESSION['message'] = ['type' => 'error', 'text' => 'Order not found'];
        header('Location: orders.php');
        exit();
    }
} catch (PDOException $e) {
    error_log("Error fetching order: " . $e->getMessage());
    $_SESSION['message'] = ['type' => 'error', 'text' => 'Database error occurred'];
    header('Location: orders.php');
    exit();
}

// Use form data if available (for validation errors)
$form_data = $form_data ?? $order;

// Include header
include 'includes/header.php';
?>

<!-- Dashboard Header -->
<div class="bg-gradient-to-br from-red-600 via-red-700 to-red-800 text-white">
    <div class="container mx-auto px-6 py-8">
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between">
            <div>
                <nav class="flex items-center space-x-2 text-sm mb-4">
                    <a href="orders.php" class="hover:text-red-200 transition-colors">
                        <i class="fas fa-arrow-left mr-1"></i> Back to Orders
                    </a>
                    <span class="text-red-200">/</span>
                    <a href="order_view.php?id=<?= $order['id'] ?>" class="hover:text-red-200 transition-colors">
                        Order #<?= htmlspecialchars($order['order_number']) ?>
                    </a>
                    <span class="text-red-200">/</span>
                    <span>Edit Order</span>
                </nav>
                <h1 class="text-3xl md:text-4xl font-bold mb-2">Edit Order</h1>
                <p class="text-lg opacity-90">Modify order details and update information</p>
            </div>
        </div>
    </div>
</div>

<!-- Main Content -->
<div class="container mx-auto px-6 py-8">
    <!-- Alerts -->
    <?php if (!empty($errors)): ?>
        <div class="mb-6 p-4 bg-red-50 border-l-4 border-red-400 text-red-700 rounded-lg">
            <div class="flex items-center">
                <i class="fas fa-exclamation-circle mr-3"></i>
                <div>
                    <p class="font-semibold">Please fix the following errors:</p>
                    <ul class="mt-2 list-disc list-inside">
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo htmlspecialchars($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- Edit Form -->
    <div class="bg-white rounded-xl shadow-lg p-8">
        <form method="POST" action="">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                <!-- Customer Information -->
                <div>
                    <h3 class="text-xl font-bold text-gray-900 mb-6 flex items-center">
                        <i class="fas fa-user mr-3 text-blue-600"></i>
                        Customer Information
                    </h3>

                    <div class="space-y-6">
                        <!-- Customer Name -->
                        <div>
                            <label for="customer_name" class="block text-sm font-semibold text-gray-700 mb-2">
                                Customer Name *
                            </label>
                            <input type="text"
                                   id="customer_name"
                                   name="customer_name"
                                   value="<?php echo htmlspecialchars($form_data['customer_name'] ?? ''); ?>"
                                   class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-red-500 transition-colors"
                                   required>
                        </div>

                        <!-- Customer Email -->
                        <div>
                            <label for="customer_email" class="block text-sm font-semibold text-gray-700 mb-2">
                                Customer Email *
                            </label>
                            <input type="email"
                                   id="customer_email"
                                   name="customer_email"
                                   value="<?php echo htmlspecialchars($form_data['customer_email'] ?? ''); ?>"
                                   class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-red-500 transition-colors"
                                   required>
                        </div>

                        <!-- Customer Phone -->
                        <div>
                            <label for="customer_phone" class="block text-sm font-semibold text-gray-700 mb-2">
                                Customer Phone *
                            </label>
                            <input type="tel"
                                   id="customer_phone"
                                   name="customer_phone"
                                   value="<?php echo htmlspecialchars($form_data['customer_phone'] ?? ''); ?>"
                                   class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-red-500 transition-colors"
                                   required>
                        </div>
                    </div>
                </div>

                <!-- Delivery Information -->
                <div>
                    <h3 class="text-xl font-bold text-gray-900 mb-6 flex items-center">
                        <i class="fas fa-map-marker-alt mr-3 text-purple-600"></i>
                        Delivery Information
                    </h3>

                    <div class="space-y-6">
                        <!-- Delivery Address -->
                        <div>
                            <label for="delivery_address" class="block text-sm font-semibold text-gray-700 mb-2">
                                Delivery Address *
                            </label>
                            <textarea id="delivery_address"
                                      name="delivery_address"
                                      rows="3"
                                      class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-red-500 transition-colors"
                                      required><?php echo htmlspecialchars($form_data['delivery_address'] ?? ''); ?></textarea>
                        </div>

                        <!-- Delivery Instructions -->
                        <div>
                            <label for="delivery_instructions" class="block text-sm font-semibold text-gray-700 mb-2">
                                Delivery Instructions
                            </label>
                            <textarea id="delivery_instructions"
                                      name="delivery_instructions"
                                      rows="3"
                                      class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-red-500 transition-colors"
                                      placeholder="Any special delivery instructions..."><?php echo htmlspecialchars($form_data['delivery_instructions'] ?? ''); ?></textarea>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Order Details -->
            <div class="mt-8 pt-8 border-t border-gray-200">
                <h3 class="text-xl font-bold text-gray-900 mb-6 flex items-center">
                    <i class="fas fa-shopping-cart mr-3 text-green-600"></i>
                    Order Details
                </h3>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <!-- Payment Method -->
                    <div>
                        <label for="payment_method" class="block text-sm font-semibold text-gray-700 mb-2">
                            Payment Method *
                        </label>
                        <select id="payment_method"
                                name="payment_method"
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-red-500 transition-colors"
                                required>
                            <option value="">Select Payment Method</option>
                            <option value="cash" <?php echo ($form_data['payment_method'] ?? '') === 'cash' ? 'selected' : ''; ?>>Cash on Delivery</option>
                            <option value="card" <?php echo ($form_data['payment_method'] ?? '') === 'card' ? 'selected' : ''; ?>>Card Payment</option>
                            <option value="mpesa" <?php echo ($form_data['payment_method'] ?? '') === 'mpesa' ? 'selected' : ''; ?>>M-Pesa</option>
                            <option value="bank_transfer" <?php echo ($form_data['payment_method'] ?? '') === 'bank_transfer' ? 'selected' : ''; ?>>Bank Transfer</option>
                        </select>
                    </div>

                    <!-- Payment Status -->
                    <div>
                        <label for="payment_status" class="block text-sm font-semibold text-gray-700 mb-2">
                            Payment Status
                        </label>
                        <select id="payment_status"
                                name="payment_status"
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-red-500 transition-colors">
                            <option value="pending" <?php echo ($form_data['payment_status'] ?? '') === 'pending' ? 'selected' : ''; ?>>Pending</option>
                            <option value="paid" <?php echo ($form_data['payment_status'] ?? '') === 'paid' ? 'selected' : ''; ?>>Paid</option>
                            <option value="failed" <?php echo ($form_data['payment_status'] ?? '') === 'failed' ? 'selected' : ''; ?>>Failed</option>
                        </select>
                    </div>

                    <!-- Order Status -->
                    <div>
                        <label for="status" class="block text-sm font-semibold text-gray-700 mb-2">
                            Order Status *
                        </label>
                        <select id="status"
                                name="status"
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-red-500 transition-colors"
                                required>
                            <option value="">Select Order Status</option>
                            <option value="pending" <?php echo ($form_data['status'] ?? '') === 'pending' ? 'selected' : ''; ?>>Pending</option>
                            <option value="processing" <?php echo ($form_data['status'] ?? '') === 'processing' ? 'selected' : ''; ?>>Processing</option>
                            <option value="confirmed" <?php echo ($form_data['status'] ?? '') === 'confirmed' ? 'selected' : ''; ?>>Confirmed</option>
                            <option value="delivered" <?php echo ($form_data['status'] ?? '') === 'delivered' ? 'selected' : ''; ?>>Delivered</option>
                            <option value="cancelled" <?php echo ($form_data['status'] ?? '') === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                        </select>
                    </div>
                </div>

                <!-- Order Notes -->
                <div class="mt-6">
                    <label for="notes" class="block text-sm font-semibold text-gray-700 mb-2">
                        Order Notes
                    </label>
                    <textarea id="notes"
                              name="notes"
                              rows="4"
                              class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-red-500 transition-colors"
                              placeholder="Internal notes about this order..."><?php echo htmlspecialchars($form_data['notes'] ?? ''); ?></textarea>
                </div>
            </div>

            <!-- Form Actions -->
            <div class="mt-8 pt-6 border-t border-gray-200 flex flex-col sm:flex-row gap-4">
                <button type="submit" class="flex-1 bg-red-600 hover:bg-red-700 text-white px-6 py-3 rounded-lg font-semibold transition-colors duration-200 flex items-center justify-center">
                    <i class="fas fa-save mr-2"></i>
                    Update Order
                </button>

                <a href="order_view.php?id=<?= $order['id'] ?>"
                   class="px-6 py-3 border-2 border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors duration-200 flex items-center justify-center">
                    <i class="fas fa-times mr-2"></i>
                    Cancel
                </a>
            </div>
        </form>
    </div>
</div>

<style>
/* Form styling enhancements */
.form-section {
    @apply bg-gray-50 rounded-lg p-6 border border-gray-200;
}

.form-section h4 {
    @apply text-lg font-semibold text-gray-900 mb-4 flex items-center;
}

/* Custom focus states */
input:focus, select:focus, textarea:focus {
    @apply ring-2 ring-red-500 border-red-500;
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .container {
        @apply px-4;
    }
}
</style>

<?php include 'includes/footer.php'; ?>
