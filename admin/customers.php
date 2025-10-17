<?php
// Include header with session start and admin authentication
// The header.php inherently contains the session_start(), config.php include,
// and the admin user check, redirecting if not an admin.
require_once 'includes/header.php'; // Adjust this path as needed to your main admin header.

// Define a helper function for status badges if not already defined in functions.php
if (!function_exists('getStatusBadgeClass')) {
    function getStatusBadgeClass($status) {
        switch ($status) {
            case 'active':
                return 'bg-green-100 text-green-800';
            case 'inactive':
                return 'bg-yellow-100 text-yellow-800';
            case 'suspended':
                return 'bg-red-100 text-red-800';
            default:
                return 'bg-gray-100 text-gray-800';
        }
    }
}

// Initialize variables
$error = '';
$success = '';

// Include ActivityLogger for activity tracking (must be before form processing)
require_once '../includes/ActivityLogger.php';
$activityLogger = new ActivityLogger($pdo);

// --- AJAX / API Endpoints ---
// These specific handlers return JSON and exit to prevent full page rendering.

// AJAX Request to Delete User
if (isset($_POST['delete_user_ajax'])) {
    $id = (int)$_POST['user_id'];
    $username_to_delete = '';

    ob_clean(); // Ensure no prior output
    header('Content-Type: application/json'); // Respond with JSON

    try {
        // Prevent deleting own account
        if ($id == $_SESSION['user_id']) {
            echo json_encode(['status' => 'error', 'message' => 'You cannot delete your own account.']);
            exit();
        }

        // Get user name before deleting for activity log
        $stmt = $pdo->prepare("SELECT name FROM users WHERE id = ?");
        $stmt->execute([$id]);
        $user_info = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($user_info) {
            $username_to_delete = $user_info['name'];
        }

        $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
        $stmt->execute([$id]);
        
        // Log activity
        $activityLogger->logActivity("User '{$username_to_delete}' (ID: {$id}) deleted.", $_SESSION['user_id'], 'user_delete');

        echo json_encode(['status' => 'success', 'message' => "User '{$username_to_delete}' (ID: {$id}) deleted successfully"]);

    } catch (PDOException $e) {
        error_log("Error deleting user (AJAX, ID: $id): " . $e->getMessage());
        echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
    }
    exit(); // Crucial: Exit after AJAX response
}


// --- Traditional Form Submissions ---
// These handlers typically redirect after processing.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_user'])) {
        // Update user details
        $id = (int)$_POST['user_id'];
        $name = trim($_POST['name']);
        $email = trim($_POST['email']);
        $role = $_POST['role'] ?? 'customer';
        $status = $_POST['status'];

        try {
            $stmt = $pdo->prepare("UPDATE users SET name = ?, email = ?, role = ?, status = ? WHERE id = ?");
            $stmt->execute([$name, $email, $role, $status, $id]);
            $success = 'User updated successfully';
            // Log activity
            $activityLogger->logActivity("User '{$name}' (ID: {$id}) updated.", $id, 'user_update');
        } catch (PDOException $e) {
            $error = 'Error updating user: ' . $e->getMessage();
            error_log("Error updating user (ID: $id): " . $e->getMessage());
        }
    } elseif (isset($_POST['delete_user'])) {
        // Delete user (This is the old, non-AJAX handler. The new AJAX handler will be used instead)
        $id = (int)$_POST['user_id'];
        $username_to_delete = '';

        try {
            // Prevent deleting own account
            if ($id == $_SESSION['user_id']) {
                $error = 'You cannot delete your own account';
            } else {
                // Get user name before deleting for activity log
                $stmt = $pdo->prepare("SELECT name FROM users WHERE id = ?");
                $stmt->execute([$id]);
                $user_info = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($user_info) {
                    $username_to_delete = $user_info['name'];
                }

                $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
                $stmt->execute([$id]);
                $success = "User '{$username_to_delete}' (ID: {$id}) deleted successfully";
                // Log activity
                $activityLogger->logActivity("User '{$username_to_delete}' (ID: {$id}) deleted.", $_SESSION['user_id'], 'user_delete');
            }
        } catch (PDOException $e) {
            $error = 'Error deleting user: ' . $e->getMessage();
            error_log("Error deleting user (ID: $id): " . $e->getMessage());
        }
    } elseif (isset($_POST['add_user'])) {
        // Add new user
        $name = trim($_POST['name']);
        $email = trim($_POST['email']);
        $password = password_hash(trim($_POST['password']), PASSWORD_DEFAULT); // Hash password
        $role = $_POST['role'] ?? 'customer';
        $status = $_POST['status'];

        try {
            $stmt = $pdo->prepare("INSERT INTO users (name, email, password, role, status) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$name, $email, $password, $role, $status]);
            $new_user_id = $pdo->lastInsertId();
            $success = "New user '{$name}' (ID: {$new_user_id}) added successfully";
            // Log activity
            $activityLogger->logActivity("New user '{$name}' (ID: {$new_user_id}) added.", $_SESSION['user_id'], 'user_add');
        } catch (PDOException $e) {
            // Check for duplicate email error
            if ($e->getCode() == '23000') { // SQLSTATE for integrity constraint violation
                $error = 'Error adding user: Email already exists.';
            } else {
                $error = 'Error adding user: ' . $e->getMessage();
            }
            error_log("Error adding user: " . $e->getMessage());
        }
    }

    // After any POST operation, re-fetch users to reflect changes
}

// Get all users for display and statistics
$users = [];
try {
    $stmt = $pdo->query("SELECT * FROM users ORDER BY created_at DESC");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = 'Error fetching users: ' . $e->getMessage();
    error_log("Error fetching users: " . $e->getMessage());
}

// Get customer statistics
$totalCustomers = count($users);
$activeCustomers = 0;
$adminUsers = 0;
$newCustomersThisMonth = 0;

foreach ($users as $user) {
    if (($user['status'] ?? 'inactive') === 'active') {
        $activeCustomers++;
    }
    if (($user['role'] ?? 'user') === 'admin') {
        $adminUsers++;
    }
    // Count customers registered this month
    if (isset($user['created_at']) && date('Y-m', strtotime($user['created_at'])) === date('Y-m')) {
        $newCustomersThisMonth++;
    }
}

// Set page title for header
$page_title = 'Manage Customers';
?>

<!-- Add Animate.css for modal animations -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"/>
<!-- Your HTML starts here, which will be within the <body> of header.php -->

<!-- Dashboard Header -->
<div class="bg-gradient-to-br from-primary via-primary-dark to-secondary text-white mt-0">
    <div class="container mx-auto px-6 py-8">
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between">
            <div>
                <h1 class="text-3xl md:text-4xl font-bold mb-2">Customer Management</h1>
                <p class="text-lg opacity-90">Manage user accounts and permissions efficiently</p>
            </div>
            <div class="mt-4 lg:mt-0">
                <button onclick="addUser()"
                   class="group relative bg-gradient-to-r from-emerald-500 to-emerald-600 hover:from-emerald-600 hover:to-emerald-700 text-white px-6 py-3 rounded-xl font-semibold transition-all duration-300 flex items-center shadow-lg hover:shadow-xl transform hover:-translate-y-1 border border-emerald-400/20">
                    <div class="absolute inset-0 bg-gradient-to-r from-white/0 via-white/10 to-white/0 opacity-0 group-hover:opacity-100 transition-opacity duration-300 rounded-xl"></div>
                    <i class="fas fa-plus mr-2 relative z-10"></i>
                    <span class="relative z-10 font-medium">Add Customer</span>
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Statistics Cards -->
<div class="bg-gray-50 py-8">
    <div class="container mx-auto px-6">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
            <div class="bg-white rounded-xl shadow-lg p-6 border-l-4 border-blue-500">
                <div class="flex items-center">
                    <div class="p-3 bg-blue-100 rounded-lg">
                        <i class="fas fa-users text-2xl text-blue-600"></i>
                    </div>
                    <div class="ml-4">
                        <h3 class="text-2xl font-bold text-gray-900"><?php echo $totalCustomers; ?></h3>
                        <p class="text-gray-600">Total Customers</p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-lg p-6 border-l-4 border-green-500">
                <div class="flex items-center">
                    <div class="p-3 bg-green-100 rounded-lg">
                        <i class="fas fa-user-check text-2xl text-green-600"></i>
                    </div>
                    <div class="ml-4">
                        <h3 class="text-2xl font-bold text-gray-900"><?php echo $activeCustomers; ?></h3>
                        <p class="text-gray-600">Active Users</p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-lg p-6 border-l-4 border-purple-500">
                <div class="flex items-center">
                    <div class="p-3 bg-purple-100 rounded-lg">
                        <i class="fas fa-user-shield text-2xl text-purple-600"></i>
                    </div>
                    <div class="ml-4">
                        <h3 class="text-2xl font-bold text-gray-900"><?php echo $adminUsers; ?></h3>
                        <p class="text-gray-600">Administrators</p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-lg p-6 border-l-4 border-yellow-500">
                <div class="flex items-center">
                    <div class="p-3 bg-yellow-100 rounded-lg">
                        <i class="fas fa-user-plus text-2xl text-yellow-600"></i>
                    </div>
                    <div class="ml-4">
                        <h3 class="text-2xl font-bold text-gray-900"><?php echo $newCustomersThisMonth; ?></h3>
                        <p class="text-gray-600">New This Month</p>
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
        <div class="mb-6 p-4 bg-red-50 border-l-4 border-red-400 text-red-700 rounded-lg flex items-center">
            <i class="fas fa-exclamation-circle mr-3"></i>
            <div>
                <p class="font-semibold">Error</p>
                <p><?php echo htmlspecialchars($error); ?></p>
            </div>
            <button onclick="this.parentElement.remove()" class="ml-auto text-gray-400 hover:text-gray-600">
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
            <button onclick="this.parentElement.remove()" class="ml-auto text-gray-400 hover:text-gray-600">
                <i class="fas fa-times"></i>
            </button>
        </div>
    <?php endif; ?>

    <!-- Enhanced Search & Filter Section -->
    <div class="bg-white rounded-xl shadow-lg p-6 mb-8">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Search Input -->
            <div class="lg:col-span-2">
                <label class="block text-sm font-semibold text-gray-700 mb-2">Search Customers</label>
                <div class="relative">
                    <input type="text" id="searchInput"
                           placeholder="Search by name, email, or role..."
                           class="w-full pl-12 pr-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent transition-colors">
                    <i class="fas fa-search absolute left-4 top-1/2 transform -translate-y-1/2 text-gray-400"></i>
                </div>
            </div>

            <!-- Role Filter -->
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">Filter by Role</label>
                <select id="roleFilter" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent transition-colors">
                    <option value="">All Roles</option>
                    <option value="customer">Customers</option>
                    <option value="admin">Administrators</option>
                    <option value="driver">Drivers</option>
                    <option value="ambassador">Ambassadors</option>
                </select>
            </div>
        </div>

        <!-- Quick Filter Tabs -->
        <div class="mt-6 pt-6 border-t border-gray-200">
            <div class="flex flex-wrap gap-2">
                <button class="filter-tab active" data-filter="all">
                    All <span class="ml-1 bg-gray-100 text-gray-600 px-2 py-1 rounded-full text-xs"><?php echo $totalCustomers; ?></span>
                </button>
                <button class="filter-tab" data-filter="active">
                    Active <span class="ml-1 bg-green-100 text-green-600 px-2 py-1 rounded-full text-xs"><?php echo $activeCustomers; ?></span>
                </button>
                <button class="filter-tab" data-filter="admin">
                    Admins <span class="ml-1 bg-purple-100 text-purple-600 px-2 py-1 rounded-full text-xs"><?php echo $adminUsers; ?></span>
                </button>
                <button onclick="clearFilters()" class="ml-4 text-primary hover:text-primary-dark font-medium">
                    <i class="fas fa-times mr-1"></i>Clear Filters
                </button>
            </div>
        </div>
    </div>

    <!-- Customers Table -->
    <div class="bg-white shadow overflow-hidden sm:rounded-lg">
        <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-medium text-gray-900">All Customers</h3>
            <p class="mt-1 text-sm text-gray-600">Manage customer accounts and permissions</p>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Customer</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Contact</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Role</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Joined</th>
                        <th scope="col" class="relative px-6 py-3">
                            <span class="sr-only">Actions</span>
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php if (empty($users)): ?>
                        <tr>
                            <td colspan="6" class="px-6 py-4 text-center text-sm text-gray-500">
                                <i class="fas fa-users text-4xl text-gray-300 mb-3"></i>
                                <p>No customers found</p>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($users as $user): ?>
                            <tr class="hover:bg-gray-50 transition-colors duration-200"
                                data-id="<?php echo $user['id']; ?>"
                                data-name="<?php echo htmlspecialchars($user['name']); ?>"
                                data-email="<?php echo htmlspecialchars($user['email']); ?>"
                                data-role="<?php echo htmlspecialchars($user['role']); ?>"
                                data-status="<?php echo htmlspecialchars($user['status']); ?>"
                                >
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div class="flex-shrink-0 h-10 w-10 rounded-full bg-gradient-to-br from-blue-500 to-indigo-600 flex items-center justify-center text-white font-semibold text-lg">
                                            <?php echo strtoupper(substr($user['name'], 0, 1)); ?>
                                        </div>
                                        <div class="ml-4">
                                            <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($user['name']); ?></div>
                                            <div class="text-sm text-gray-500">ID: #<?php echo $user['id']; ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900"><?php echo htmlspecialchars($user['email']); ?></div>
                                    <?php // Assuming 'phone' might not always exist ?>
                                    <?php if (!empty($user['phone'])): ?>
                                        <div class="text-sm text-gray-500"><?php echo htmlspecialchars($user['phone']); ?></div>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full
                                        <?php
                                        // Dynamic role badge
                                        switch ($user['role'] ?? 'customer') {
                                            case 'admin': echo 'bg-purple-100 text-purple-800'; break;
                                            case 'driver': echo 'bg-orange-100 text-orange-800'; break;
                                            case 'ambassador': echo 'bg-pink-100 text-pink-800'; break;
                                            default: echo 'bg-blue-100 text-blue-800'; break; // 'customer' role
                                        }
                                        ?>">
                                        <i class="fas fa-<?php
                                        switch ($user['role'] ?? 'customer') {
                                            case 'admin': echo 'shield-alt'; break;
                                            case 'driver': echo 'truck'; break;
                                            case 'ambassador': echo 'hands-helping'; break;
                                            default: echo 'user'; break;
                                        }
                                        ?> mr-1"></i>
                                        <?php echo ucfirst(htmlspecialchars($user['role'] ?? 'customer')); ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full
                                        <?php echo getStatusBadgeClass($user['status']); ?>">
                                        <i class="fas fa-<?php echo ($user['status'] ?? 'active') === 'active' ? 'check-circle' : 'circle'; ?> mr-1"></i>
                                        <?php echo ucfirst(htmlspecialchars($user['status'] ?? 'active')); ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <?php echo date('M j, Y', strtotime($user['created_at'])); ?>
                                    <div class="text-xs text-gray-400"><?php echo date('g:i A', strtotime($user['created_at'])); ?></div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <div class="flex justify-end space-x-2">
                                        <button onclick="editUser(<?php echo htmlspecialchars(json_encode($user)); ?>)"
                                                class="group relative bg-gradient-to-r from-blue-500 to-blue-600 hover:from-blue-600 hover:to-blue-700 text-white px-4 py-2 rounded-xl font-semibold transition-all duration-300 flex items-center space-x-2 shadow-lg hover:shadow-xl transform hover:-translate-y-0.5 border border-blue-400/20"
                                                type="button">
                                            <div class="absolute inset-0 bg-gradient-to-r from-white/0 via-white/10 to-white/0 opacity-0 group-hover:opacity-100 transition-opacity duration-300 rounded-xl"></div>
                                            <i class="fas fa-edit text-sm relative z-10"></i>
                                            <span class="relative z-10 font-medium">Edit</span>
                                        </button>
                                        <?php if ($user['id'] != $_SESSION['user_id']): // Prevent admin from deleting their own account ?>
                                            <form action="" method="POST" class="inline"
                                                onsubmit="return confirm('Are you sure you want to delete this customer? This action cannot be undone.');">
                                                <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                <button type="submit" name="delete_user"
                                                        class="group relative bg-gradient-to-r from-red-500 to-red-600 hover:from-red-600 hover:to-red-700 text-white px-4 py-2 rounded-xl font-semibold transition-all duration-300 flex items-center space-x-2 shadow-lg hover:shadow-xl transform hover:-translate-y-0.5 border border-red-400/20">
                                                    <div class="absolute inset-0 bg-gradient-to-r from-white/0 via-white/10 to-white/0 opacity-0 group-hover:opacity-100 transition-opacity duration-300 rounded-xl"></div>
                                                    <i class="fas fa-trash text-sm relative z-10"></i>
                                                    <span class="relative z-10 font-medium">Delete</span>
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Edit User Modal -->
<div id="editUserModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
    <div class="bg-white rounded-2xl shadow-2xl max-w-md w-full max-h-[90vh] overflow-y-auto">
        <!-- Modal Header -->
        <div class="bg-gradient-to-r from-primary to-secondary p-6 text-white rounded-t-2xl">
            <div class="flex items-center justify-between">
                <h3 class="text-xl font-bold">
                    <i class="fas fa-user-edit mr-2"></i>Edit Customer
                </h3>
                <button onclick="closeModal('editUserModal')" class="text-white hover:text-gray-200 text-2xl">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        </div>

        <!-- Modal Body -->
        <div class="p-6">
            <form id="editUserForm" action="" method="POST" class="space-y-6">
                <input type="hidden" name="user_id" id="edit_userId">
                <input type="hidden" name="update_user" value="1">

                <div>
                    <label for="edit_name" class="block text-sm font-semibold text-gray-700 mb-2">Full Name <span class="text-red-500">*</span></label>
                    <input type="text" name="name" id="edit_name" required
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent transition-colors"
                           placeholder="Enter full name">
                </div>

                <div>
                    <label for="edit_email" class="block text-sm font-semibold text-gray-700 mb-2">Email Address <span class="text-red-500">*</span></label>
                    <input type="email" name="email" id="edit_email" required
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent transition-colors"
                           placeholder="Enter email address">
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label for="edit_role" class="block text-sm font-semibold text-gray-700 mb-2">Role</label>
                        <select id="edit_role" name="role"
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent transition-colors">
                            <option value="customer">Customer</option>
                            <option value="admin">Administrator</option>
                            <option value="driver">Driver</option>
                            <option value="ambassador">Ambassador</option>
                        </select>
                    </div>
                    <div>
                        <label for="edit_status" class="block text-sm font-semibold text-gray-700 mb-2">Status</label>
                        <select id="edit_status" name="status"
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent transition-colors">
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                            <option value="suspended">Suspended</option>
                        </select>
                    </div>
                </div>

                <!-- Modal Footer -->
                <div class="flex gap-3 pt-4">
                    <button type="submit"
                            class="group relative bg-gradient-to-r from-primary to-primary-dark hover:from-primary-dark hover:to-primary text-white px-6 py-3 rounded-xl font-semibold transition-all duration-300 flex items-center justify-center shadow-lg hover:shadow-xl transform hover:-translate-y-0.5 border border-primary/20 flex-1">
                        <div class="absolute inset-0 bg-gradient-to-r from-white/0 via-white/10 to-white/0 opacity-0 group-hover:opacity-100 transition-opacity duration-300 rounded-xl"></div>
                        <i class="fas fa-save mr-2 relative z-10"></i>
                        <span class="relative z-10 font-medium">Update Customer</span>
                    </button>
                    <button type="button" onclick="closeModal('editUserModal')"
                            class="group relative bg-gradient-to-r from-gray-500 to-gray-600 hover:from-gray-600 hover:to-gray-700 text-white px-6 py-3 rounded-xl font-semibold transition-all duration-300 flex items-center justify-center shadow-lg hover:shadow-xl transform hover:-translate-y-0.5 border border-gray-400/20">
                        <div class="absolute inset-0 bg-gradient-to-r from-white/0 via-white/10 to-white/0 opacity-0 group-hover:opacity-100 transition-opacity duration-300 rounded-xl"></div>
                        <span class="relative z-10 font-medium">Cancel</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Add User Modal -->
<div id="addUserModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
    <div class="bg-white rounded-2xl shadow-2xl max-w-md w-full max-h-[90vh] overflow-y-auto">
        <!-- Modal Header -->
        <div class="bg-gradient-to-r from-emerald-500 to-emerald-600 p-6 text-white rounded-t-2xl">
            <div class="flex items-center justify-between">
                <h3 class="text-xl font-bold">
                    <i class="fas fa-user-plus mr-2"></i>Add New Customer
                </h3>
                <button onclick="closeModal('addUserModal')" class="text-white hover:text-gray-200 text-2xl">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        </div>

        <!-- Modal Body -->
        <div class="p-6">
            <form id="addUserForm" action="" method="POST" class="space-y-6">
                <input type="hidden" name="add_user" value="1">

                <div>
                    <label for="add_name" class="block text-sm font-semibold text-gray-700 mb-2">Full Name <span class="text-red-500">*</span></label>
                    <input type="text" name="name" id="add_name" required
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent transition-colors"
                           placeholder="Enter full name">
                </div>

                <div>
                    <label for="add_email" class="block text-sm font-semibold text-gray-700 mb-2">Email Address <span class="text-red-500">*</span></label>
                    <input type="email" name="email" id="add_email" required
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent transition-colors"
                           placeholder="Enter email address">
                </div>

                <div>
                    <label for="add_password" class="block text-sm font-semibold text-gray-700 mb-2">Password <span class="text-red-500">*</span></label>
                    <input type="password" name="password" id="add_password" required
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent transition-colors"
                           placeholder="Enter password">
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label for="add_role" class="block text-sm font-semibold text-gray-700 mb-2">Role</label>
                        <select id="add_role" name="role"
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent transition-colors">
                            <option value="customer">Customer</option>
                            <option value="admin">Administrator</option>
                            <option value="driver">Driver</option>
                            <option value="ambassador">Ambassador</option>
                        </select>
                    </div>
                    <div>
                        <label for="add_status" class="block text-sm font-semibold text-gray-700 mb-2">Status</label>
                        <select id="add_status" name="status"
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent transition-colors">
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                            <option value="suspended">Suspended</option>
                        </select>
                    </div>
                </div>

                <!-- Modal Footer -->
                <div class="flex gap-3 pt-4">
                    <button type="submit"
                            class="group relative bg-gradient-to-r from-emerald-500 to-emerald-600 hover:from-emerald-600 hover:to-emerald-700 text-white px-6 py-3 rounded-xl font-semibold transition-all duration-300 flex items-center justify-center shadow-lg hover:shadow-xl transform hover:-translate-y-0.5 border border-emerald-400/20 flex-1">
                        <div class="absolute inset-0 bg-gradient-to-r from-white/0 via-white/10 to-white/0 opacity-0 group-hover:opacity-100 transition-opacity duration-300 rounded-xl"></div>
                        <i class="fas fa-plus mr-2 relative z-10"></i>
                        <span class="relative z-10 font-medium">Add Customer</span>
                    </button>
                    <button type="button" onclick="closeModal('addUserModal')"
                            class="group relative bg-gradient-to-r from-gray-500 to-gray-600 hover:from-gray-600 hover:to-gray-700 text-white px-6 py-3 rounded-xl font-semibold transition-all duration-300 flex items-center justify-center shadow-lg hover:shadow-xl transform hover:-translate-y-0.5 border border-gray-400/20">
                        <div class="absolute inset-0 bg-gradient-to-r from-white/0 via-white/10 to-white/0 opacity-0 group-hover:opacity-100 transition-opacity duration-300 rounded-xl"></div>
                        <span class="relative z-10 font-medium">Cancel</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
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
            <p class="text-lg font-medium mb-3">Are you sure you want to delete customer <span id="customerNameToDelete" class="font-bold text-red-600"></span>?</p>
            <p>This action cannot be undone. All associated data will be permanently removed.</p>
        </div>

        <!-- Modal Footer -->
        <div class="p-6 border-t border-gray-200 flex gap-3 justify-end">
            <button type="button" onclick="closeModal('deleteConfirmationModal')"
                    class="group relative bg-gray-500 hover:bg-gray-600 text-white px-6 py-3 rounded-xl font-semibold transition-all duration-300 flex items-center justify-center shadow-lg hover:shadow-xl transform hover:-translate-y-0.5">
                <div class="absolute inset-0 bg-gradient-to-r from-white/0 via-white/10 to-white/0 opacity-0 group-hover:opacity-100 transition-opacity duration-300 rounded-xl"></div>
                <span class="relative z-10 font-medium">Cancel</span>
            </button>
            <form method="POST" class="inline" id="deleteCustomerForm">
                <input type="hidden" name="user_id" id="deleteCustomerId">
                <button type="submit" name="delete_user_ajax"
                        class="group relative bg-red-600 hover:bg-red-700 text-white px-6 py-3 rounded-xl font-semibold transition-all duration-300 flex items-center justify-center shadow-lg hover:shadow-xl transform hover:-translate-y-0.5">
                    <div class="absolute inset-0 bg-gradient-to-r from-white/0 via-white/10 to-white/0 opacity-0 group-hover:opacity-100 transition-opacity duration-300 rounded-xl"></div>
                    <i class="fas fa-trash mr-2 relative z-10"></i>
                    <span class="relative z-10 font-medium">Delete</span>
                </button>
            </form>
        </div>
    </div>
</div>

<script>
// --- Global Functions for Modals (consistent with ambassador.php/orders.php) ---
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

    modal.classList.add('animate__fadeOut');
    setTimeout(() => {
        modal.classList.add('hidden');
        modal.classList.remove('animate__fadeOut', 'animate__animated', 'animate__fadeIn');
        document.body.classList.remove('overflow-y-hidden');
    }, 300);

    // Clear content of AJAX-loaded modals if any, or reset form fields
    if (modalId === 'editUserModal') {
        document.getElementById('editUserForm').reset(); // Reset edit form
    } else if (modalId === 'addUserModal') {
        document.getElementById('addUserForm').reset(); // Reset add form
    } else if (modalId === 'deleteConfirmationModal') {
        document.getElementById('customerNameToDelete').textContent = ''; // Clear name in delete modal
    }
}

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
    modal.classList.add('animate__animated', 'animate__fadeIn');
    document.body.classList.add('overflow-y-hidden');
}


// Close modal when clicking outside (on the black overlay)
window.addEventListener('click', function(event) {
    const modalIds = ['editUserModal', 'addUserModal', 'deleteConfirmationModal'];
    modalIds.forEach(id => {
        const modal = document.getElementById(id);
        if (modal && !modal.classList.contains('hidden')) {
            if (event.target === modal) {
                closeModal(id);
            }
        }
    });
});

// Keyboard navigation for modals (Escape key)
document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape') {
        const modalIds = ['editUserModal', 'addUserModal', 'deleteConfirmationModal'];
        for (const id of modalIds) {
            const modal = document.getElementById(id);
            if (modal && !modal.classList.contains('hidden')) {
                closeModal(id);
                break;
            }
        }
    }
});


// Global functions for customer management
function editUser(user) {
    document.getElementById('edit_userId').value = user.id;
    document.getElementById('edit_name').value = user.name;
    document.getElementById('edit_email').value = user.email;
    document.getElementById('edit_role').value = user.role;
    document.getElementById('edit_status').value = user.status;

    // Show modal using the new openModal function
    openModal('editUserModal');
}

function addUser() {
    // Clear form fields before opening for add
    document.getElementById('addUserForm').reset();
    // Set default values if desired (e.g., role to 'customer', status to 'active')
    document.getElementById('add_role').value = 'customer';
    document.getElementById('add_status').value = 'active';
    // Ensure password field is visible/active (if it was hidden for edit)
    document.getElementById('add_password').required = true;

    // Show modal using the new openModal function
    openModal('addUserModal');
}

// Function to open the delete confirmation modal
function confirmDeleteUser(userId, userName) {
    document.getElementById('deleteCustomerId').value = userId;
    document.getElementById('customerNameToDelete').textContent = userName;
    openModal('deleteConfirmationModal');
}


// Enhanced filtering functionality for customers page
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('searchInput');
    const roleFilter = document.getElementById('roleFilter');
    const filterTabs = document.querySelectorAll('.filter-tab');
    const customerTableBody = document.querySelector('tbody');

    // Function to apply all filters
    function filterCustomers() {
        const searchTerm = searchInput.value.toLowerCase();
        const roleValue = roleFilter.value; // No toLowerCase needed for direct match
        const activeTabFilter = document.querySelector('.filter-tab.active')?.dataset.filter;

        let visibleCustomerCount = 0;
        let hasNoResultsRow = false; // Track if we added the no results row

        document.querySelectorAll('tbody tr').forEach(customerRow => {
            // Skip the no-results row if it exists
            if (customerRow.id === 'no-results-row') {
                customerRow.style.display = 'none'; // Hide it initially
                hasNoResultsRow = true;
                return;
            }

            const name = customerRow.dataset.name.toLowerCase();
            const email = customerRow.dataset.email.toLowerCase();
            const role = customerRow.dataset.role;
            const status = customerRow.dataset.status;

            let shouldShow = true;

            // Search filter
            if (searchTerm && !(name.includes(searchTerm) || email.includes(searchTerm) || role.includes(searchTerm))) {
                shouldShow = false;
            }

            // Role filter
            if (roleValue && roleValue !== '' && role !== roleValue) {
                shouldShow = false;
            }

            // Quick filter tabs
            if (activeTabFilter) {
                if (activeTabFilter === 'active' && status !== 'active') { // Filter by status for 'active' tab
                    shouldShow = false;
                } else if (activeTabFilter === 'admin' && role !== 'admin') { // Filter by role for 'admin' tab
                    shouldShow = false;
                }
                // 'all' filter doesn't restrict, so no specific `shouldShow = false`
            }

            if (shouldShow) {
                customerRow.style.display = 'table-row';
                visibleCustomerCount++;
            } else {
                customerRow.style.display = 'none';
            }
        });

        // Handle empty search/filter results
        const noResultsRowId = 'no-results-row';
        let existingNoResultsRow = document.getElementById(noResultsRowId);

        if (visibleCustomerCount === 0) {
            if (!existingNoResultsRow) { // If no results AND no row exists, create one
                const newRow = customerTableBody.insertRow();
                newRow.id = noResultsRowId;
                const cell = newRow.insertCell();
                cell.colSpan = 6; // Match the number of columns in your table
                cell.className = 'px-6 py-4 text-center text-sm text-gray-500';
                cell.innerHTML = '<i class="fas fa-search text-2xl text-gray-300 mb-2"></i><p>No customers match your filters</p>';
                customerTableBody.appendChild(newRow);
            } else {
                existingNoResultsRow.style.display = 'table-row'; // Show existing if it was hidden
            }
        } else {
            if (existingNoResultsRow) { // If results are found, hide or remove the no-results row
                existingNoResultsRow.remove(); // Safely remove it
            }
        }
    }

    // Event listeners for filters
    searchInput.addEventListener('input', filterCustomers);
    roleFilter.addEventListener('change', filterCustomers);

    filterTabs.forEach(tab => {
        tab.addEventListener('click', function() {
            // Update active tab style
            filterTabs.forEach(t => t.classList.remove('active'));
            this.classList.add('active');

            // Set role filter input and then re-filter based on clicked tab
            const filter = this.dataset.filter;
            if (filter === 'admin') {
                roleFilter.value = 'admin';
            } else if (filter === 'all' || filter === 'active') {
                roleFilter.value = ''; // Clear role filter for 'all' and 'active' special cases
            }
            filterCustomers();
        });
    });

    window.clearFilters = function() {
        searchInput.value = '';
        roleFilter.value = '';

        // Reset active tab to 'all'
        filterTabs.forEach(tab => {
            tab.classList.toggle('active', tab.dataset.filter === 'all');
        });

        filterCustomers(); // Re-apply filters to show all
    }

    // Initial filter application on page load to set up "All" correctly
    filterCustomers();

    // Attach event listener for delete form submission
    const deleteCustomerForm = document.getElementById('deleteCustomerForm');
    if (deleteCustomerForm) {
        deleteCustomerForm.addEventListener('submit', async function(event) {
            event.preventDefault(); // Prevent default form submission

            const form = event.target;
            const submitButton = form.querySelector('button[type="submit"][name="delete_user_ajax"]');
            const originalButtonHtml = submitButton ? submitButton.innerHTML : '';

            if (submitButton) {
                submitButton.disabled = true;
                submitButton.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> Deleting...';
            }

            const formData = new FormData(form);
            // The PHP handler will look for 'delete_user_ajax'

            try {
                const response = await fetch('customer.php', { // Ensure this URL is correct for customer.php
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
                Swal.fire('Error!', 'An unexpected error occurred during deletion.', 'error');
            } finally {
                if (submitButton) {
                    submitButton.disabled = false;
                    submitButton.innerHTML = originalButtonHtml; // Restore button text
                }
            }
        });
    }

});
</script>

<?php
// Include footer (closing </body> and </html> tags)
require_once 'includes/footer.php'; // Adjust this path as needed
?>