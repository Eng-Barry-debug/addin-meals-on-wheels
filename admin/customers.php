<?php
// Start session and check admin authentication
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include database connection
require_once '../includes/config.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}

// Initialize variables
$error = '';
$success = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_user'])) {
        // Update user details
        $id = (int)$_POST['user_id'];
        $name = trim($_POST['name']);
        $email = trim($_POST['email']);
        $role = $_POST['role'];
        $status = $_POST['status'];
        
        try {
            $stmt = $pdo->prepare("UPDATE users SET name = ?, email = ?, role = ?, status = ? WHERE id = ?");
            $stmt->execute([$name, $email, $role, $status, $id]);
            $success = 'User updated successfully';
        } catch (PDOException $e) {
            $error = 'Error updating user: ' . $e->getMessage();
        }
    } elseif (isset($_POST['delete_user'])) {
        // Delete user
        $id = (int)$_POST['user_id'];
        
        try {
            // Prevent deleting own account
            if ($id == $_SESSION['user_id']) {
                $error = 'You cannot delete your own account';
            } else {
                $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
                $stmt->execute([$id]);
                $success = 'User deleted successfully';
            }
        } catch (PDOException $e) {
            $error = 'Error deleting user: ' . $e->getMessage();
        }
    }
}

// Get all users
$users = [];
try {
    $stmt = $pdo->query("SELECT * FROM users ORDER BY created_at DESC");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = 'Error fetching users: ' . $e->getMessage();
}

// Get customer statistics
$totalCustomers = count($users);
$activeCustomers = 0;
$adminUsers = 0;
$newCustomersThisMonth = 0;

foreach ($users as $user) {
    if ($user['status'] === 'active') {
        $activeCustomers++;
    }
    if ($user['role'] === 'admin') {
        $adminUsers++;
    }
    // Count customers registered this month
    if (date('Y-m', strtotime($user['created_at'])) === date('Y-m')) {
        $newCustomersThisMonth++;
    }
}

// Set page title
$page_title = 'Manage Customers';

// Include header
require_once 'includes/header.php';
?>

<!-- Dashboard Header -->
<div class="bg-gradient-to-br from-primary via-primary-dark to-secondary text-white">
    <div class="container mx-auto px-6 py-8">
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between">
            <div>
                <h1 class="text-3xl md:text-4xl font-bold mb-2">Customer Management</h1>
                <p class="text-lg opacity-90">Manage user accounts and permissions efficiently</p>
            </div>
            <div class="mt-4 lg:mt-0">
                <a href="customer_add.php"
                   class="bg-accent hover:bg-accent-dark text-white px-6 py-3 rounded-lg font-semibold transition-colors duration-200 flex items-center shadow-lg hover:shadow-xl transform hover:-translate-y-1">
                    <i class="fas fa-plus mr-2"></i>
                    Add Customer
                </a>
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
                    <input type="text" id="searchInput" value=""
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
                    <option value="user">Customers</option>
                    <option value="admin">Administrators</option>
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
                            <tr class="hover:bg-gray-50 transition-colors duration-200" data-role="<?php echo $user['role']; ?>" data-status="<?php echo $user['status']; ?>" data-name="<?php echo strtolower($user['name']); ?>">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div class="flex-shrink-0 h-12 w-12 rounded-full bg-gradient-to-br from-primary to-secondary flex items-center justify-center text-white font-semibold text-lg">
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
                                    <?php if (isset($user['phone'])): ?>
                                        <div class="text-sm text-gray-500"><?php echo htmlspecialchars($user['phone']); ?></div>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full
                                        <?php echo $user['role'] === 'admin' ? 'bg-purple-100 text-purple-800' : 'bg-blue-100 text-blue-800'; ?>">
                                        <i class="fas fa-<?php echo $user['role'] === 'admin' ? 'shield-alt' : 'user'; ?> mr-1"></i>
                                        <?php echo ucfirst(htmlspecialchars($user['role'])); ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full
                                        <?php echo getStatusBadgeClass($user['status']); ?>">
                                        <i class="fas fa-<?php echo $user['status'] === 'active' ? 'check-circle' : 'clock'; ?> mr-1"></i>
                                        <?php echo ucfirst(htmlspecialchars($user['status'])); ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <?php echo date('M j, Y', strtotime($user['created_at'])); ?>
                                    <div class="text-xs text-gray-400"><?php echo date('g:i A', strtotime($user['created_at'])); ?></div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <div class="flex justify-end space-x-2">
                                        <button onclick="editUser(<?php echo htmlspecialchars(json_encode($user)); ?>)"
                                                class="inline-flex items-center px-3 py-1.5 border border-transparent text-xs font-medium rounded-md text-white bg-primary hover:bg-primary-dark transition-colors duration-200">
                                            <i class="fas fa-edit mr-1"></i>
                                            Edit
                                        </button>
                                        <?php if ($user['id'] != $_SESSION['user_id']): ?>
                                            <form action="" method="POST" class="inline"
                                                  onsubmit="return confirm('Are you sure you want to delete this customer? This action cannot be undone.');">
                                                <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                <button type="submit" name="delete_user"
                                                        class="inline-flex items-center px-3 py-1.5 border border-transparent text-xs font-medium rounded-md text-white bg-red-600 hover:bg-red-700 transition-colors duration-200">
                                                    <i class="fas fa-trash mr-1"></i>
                                                    Delete
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
<div id="editUserModal" class="hidden fixed z-10 inset-0 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true" onclick="closeModal()"></div>
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
        <div class="inline-block align-bottom bg-white rounded-lg px-6 pt-5 pb-4 text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
            <div class="sm:flex sm:items-start">
                <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left w-full">
                    <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4" id="modal-title">
                        <i class="fas fa-user-edit mr-2"></i>Edit Customer
                    </h3>
                    <form id="userForm" action="" method="POST" class="space-y-4">
                        <input type="hidden" name="user_id" id="userId">
                        <div>
                            <label for="name" class="block text-sm font-medium text-gray-700 mb-1">Full Name</label>
                            <input type="text" name="name" id="name" required
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-primary focus:border-transparent transition-colors">
                        </div>
                        <div>
                            <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email Address</label>
                            <input type="email" name="email" id="email" required
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-primary focus:border-transparent transition-colors">
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label for="role" class="block text-sm font-medium text-gray-700 mb-1">Role</label>
                                <select id="role" name="role"
                                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-primary focus:border-transparent transition-colors">
                                    <option value="user">Customer</option>
                                    <option value="admin">Administrator</option>
                                </select>
                            </div>
                            <div>
                                <label for="status" class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                                <select id="status" name="status"
                                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-primary focus:border-transparent transition-colors">
                                    <option value="active">Active</option>
                                    <option value="inactive">Inactive</option>
                                    <option value="suspended">Suspended</option>
                                </select>
                            </div>
                        </div>
                        <div class="flex justify-end space-x-3 pt-4 border-t border-gray-200">
                            <button type="button" onclick="closeModal()"
                                    class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50 transition-colors duration-200">
                                Cancel
                            </button>
                            <button type="submit" name="update_user"
                                    class="px-4 py-2 bg-primary text-white rounded-md text-sm font-medium hover:bg-primary-dark transition-colors duration-200 flex items-center">
                                <i class="fas fa-save mr-2"></i>
                                Update Customer
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Enhanced filtering functionality for customers page
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('searchInput');
    const roleFilter = document.getElementById('roleFilter');
    const filterTabs = document.querySelectorAll('.filter-tab');

    // Search functionality
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase();
            filterCustomers();

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

    // Role filter
    if (roleFilter) {
        roleFilter.addEventListener('change', function() {
            filterCustomers();

            // Update URL
            const url = new URL(window.location);
            if (this.value) {
                url.searchParams.set('role', this.value);
            } else {
                url.searchParams.delete('role');
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

            // Update role filter based on tab
            if (roleFilter) {
                switch(filter) {
                    case 'admin':
                        roleFilter.value = 'admin';
                        break;
                    case 'active':
                        // For active filter, we'll handle this in the filterCustomers function
                        break;
                    default:
                        roleFilter.value = '';
                }
            }

            filterCustomers();

            // Update URL
            const url = new URL(window.location);
            if (filter === 'all') {
                url.searchParams.delete('role');
            } else if (filter === 'admin') {
                url.searchParams.set('role', 'admin');
            }
    });

// Global functions for customer management
function filterCustomers() {
    const searchTerm = document.getElementById('searchInput')?.value.toLowerCase() || '';
    const roleValue = document.getElementById('roleFilter')?.value || '';

    const customers = document.querySelectorAll('tbody tr');

    customers.forEach(customer => {
        const name = customer.dataset.name || '';
        const role = customer.dataset.role || '';
        const status = customer.dataset.status || '';

        let shouldShow = true;

        // Search filter
        if (searchTerm && !name.includes(searchTerm)) {
            shouldShow = false;
        }

        // Role filter
        if (roleValue && role !== roleValue) {
            shouldShow = false;
        }

        // Status filter (for active tab)
        if (document.querySelector('.filter-tab.active')?.dataset.filter === 'active' && status !== 'active') {
            shouldShow = false;
        }

        if (shouldShow) {
            customer.style.display = 'table-row';
        } else {
            customer.style.display = 'none';
        }
    });

    // Show/hide empty state
    const visibleCustomers = document.querySelectorAll('tbody tr[style*="table-row"]');
    const noResultsRow = document.querySelector('tbody tr td[colspan="6"]');

    if (visibleCustomers.length === 0 && !noResultsRow) {
        const tbody = document.querySelector('tbody');
        const emptyRow = document.createElement('tr');
        emptyRow.innerHTML = '<td colspan="6" class="px-6 py-4 text-center text-sm text-gray-500"><i class="fas fa-search text-2xl text-gray-300 mb-2"></i><p>No customers match your filters</p></td>';
        tbody.appendChild(emptyRow);
    } else if (visibleCustomers.length > 0 && noResultsRow) {
        noResultsRow.closest('tr').remove();
    }
}

function clearFilters() {
    document.getElementById('searchInput').value = '';
    document.getElementById('roleFilter').value = '';

    // Reset active tab
    document.querySelectorAll('.filter-tab').forEach(tab => {
        tab.classList.toggle('active', tab.dataset.filter === 'all');
    });

    // Show all customers
    document.querySelectorAll('tbody tr').forEach(customer => {
        customer.style.display = 'table-row';
    });

    // Remove empty state
    const noResultsRow = document.querySelector('tbody tr td[colspan="6"]');
    if (noResultsRow) {
        noResultsRow.closest('tr').remove();
    }

    // Update URL
    const url = new URL(window.location);
    url.searchParams.delete('search');
    url.searchParams.delete('role');
    window.history.pushState({}, '', url);
}

function editUser(user) {
    document.getElementById('userId').value = user.id;
    document.getElementById('name').value = user.name;
    document.getElementById('email').value = user.email;
    document.getElementById('role').value = user.role;
    document.getElementById('status').value = user.status;

    // Show modal
    document.getElementById('editUserModal').classList.remove('hidden');
}

function closeModal() {
    document.getElementById('editUserModal').classList.add('hidden');
}

// Close modal when clicking outside
window.onclick = function(event) {
    const modal = document.getElementById('editUserModal');
    if (event.target === modal) {
        closeModal();
    }
}
</script>

<style>
/* Filter tabs styling */
.filter-tab {
    @apply px-4 py-2 rounded-full bg-gray-100 text-gray-700 font-medium transition-colors duration-200;
}

.filter-tab:hover {
    @apply bg-primary text-white;
}

.filter-tab.active {
    @apply bg-primary text-white;
}

/* Table enhancements */
tbody tr {
    transition: background-color 0.2s ease;
}

tbody tr:hover {
    background-color: #f9fafb;
}

/* Avatar styling */
.avatar-badge {
    background: linear-gradient(135deg, #C1272D 0%, #B01E24 100%);
}

/* Status badge improvements */
.status-badge {
    @apply px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full;
}

/* Button enhancements */
button:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}
</style>

<?php
// Include footer
require_once 'includes/footer.php';
?>