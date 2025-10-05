<?php
// Set page title and include header
$page_title = 'Menu Management';
$page_description = 'Manage your restaurant\'s menu items';

// Include database connection and functions
require_once '../includes/config.php';
require_once 'includes/functions.php';

// Handle delete action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['_method']) && $_POST['_method'] === 'DELETE') {
    // First, delete the associated image if it exists
    $item = getRecordById('menu_items', $_POST['id'], 'image');
    if ($item && !empty($item['image'])) {
        $imagePath = '../uploads/menu/' . $item['image'];
        if (file_exists($imagePath)) {
            unlink($imagePath);
        }
    }

    if (deleteRecord('menu_items', $_POST['id'])) {
        $_SESSION['message'] = ['type' => 'success', 'text' => 'Menu item deleted successfully'];
    } else {
        $_SESSION['message'] = ['type' => 'error', 'text' => 'Failed to delete menu item'];
    }
    header('Location: menu.php');
    exit();
}

// Handle status toggle
if (isset($_GET['toggle_status']) && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $item = getRecordById('menu_items', $id, 'status');
    if ($item) {
        $newStatus = $item['status'] === 'active' ? 'inactive' : 'active';
        $stmt = $pdo->prepare("UPDATE menu_items SET status = :status WHERE id = :id");
        if ($stmt->execute([':status' => $newStatus, ':id' => $id])) {
            $_SESSION['message'] = ['type' => 'success', 'text' => 'Menu item status updated'];
        } else {
            $_SESSION['message'] = ['type' => 'error', 'text' => 'Failed to update status'];
        }
    }
    header('Location: menu.php');
    exit();
}

// Get filter parameters
$category = $_GET['category'] ?? '';
$status   = $_GET['status'] ?? '';
$search   = $_GET['search'] ?? '';

// Get categories for filter dropdown
$categories = [];
try {
    $stmt = $pdo->query("SELECT * FROM categories WHERE is_active = 1 ORDER BY name");
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $categories = [];
}

// Build query with filters
$params = [];
$query = "
    SELECT mi.*, c.name as category_name
    FROM menu_items mi
    LEFT JOIN categories c ON mi.category_id = c.id
    WHERE 1=1
";

if (!empty($search)) {
    $query .= " AND (mi.name LIKE :search OR mi.description LIKE :search OR c.name LIKE :search)";
    $params[':search'] = '%' . $search . '%';
}

if (!empty($category)) {
    $query .= " AND mi.category_id = :category";
    $params[':category'] = $category;
}

if (!empty($status)) {
    if ($status === 'featured') {
        $query .= " AND mi.is_featured = 1 AND mi.status = 'active'";
    } else {
        $query .= " AND mi.status = :status";
        $params[':status'] = $status;
    }
}

$query .= " ORDER BY mi.created_at DESC";

// Pagination setup
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 12;
$offset = ($page - 1) * $per_page;

// Get total records for pagination
$total_records = 0;
try {
    $count_query = "SELECT COUNT(*) as total FROM menu_items mi LEFT JOIN categories c ON mi.category_id = c.id WHERE 1=1";
    if (!empty($search)) {
        $count_query .= " AND (mi.name LIKE :search OR mi.description LIKE :search OR c.name LIKE :search)";
    }
    if (!empty($category)) {
        $count_query .= " AND mi.category_id = :category";
    }
    if (!empty($status)) {
        if ($status === 'featured') {
            $count_query .= " AND mi.is_featured = 1 AND mi.status = 'active'";
        } else {
            $count_query .= " AND mi.status = :status";
        }
    }

    $count_stmt = $pdo->prepare($count_query);

    // Bind search/filter parameters for count query
    foreach ($params as $key => $value) {
        $param_type = is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR;
        $count_stmt->bindValue($key, $value, $param_type);
    }

    $count_stmt->execute();
    $total_records = $count_stmt->fetch(PDO::FETCH_ASSOC)['total'];
    $total_pages = ceil($total_records / $per_page);

    // Add pagination to query
    $query .= " LIMIT :limit OFFSET :offset";

    // Execute the main query
    $stmt = $pdo->prepare($query);

    // Bind search/filter parameters
    foreach ($params as $key => $value) {
        $param_type = is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR;
        $stmt->bindValue($key, $value, $param_type);
    }

    // Bind pagination parameters
    $stmt->bindValue(':limit', (int)$per_page, PDO::PARAM_INT);
    $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    die("An error occurred while fetching menu items. Please try again later.");
}

$stmt->execute();
$menu_items = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get statistics
$totalItems = $total_records;
$activeItems = getCount($pdo, 'menu_items', "status = 'active'");
$featuredItems = getCount($pdo, 'menu_items', "is_featured = 1 AND status = 'active'");
$inactiveItems = getCount($pdo, 'menu_items', "status = 'inactive'");

// Include header
include 'includes/header.php';
?>

<!-- Dashboard Header -->
<div class="bg-gradient-to-br from-primary via-primary-dark to-secondary text-white">
    <div class="container mx-auto px-6 py-8">
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between">
            <div>
                <h1 class="text-3xl md:text-4xl font-bold mb-2">Menu Management</h1>
                <p class="text-lg opacity-90">Manage your restaurant's menu items, pricing, and availability</p>
            </div>
            <div class="mt-4 lg:mt-0">
                <a href="menu_add.php"
                   class="bg-accent hover:bg-accent-dark text-white px-6 py-3 rounded-lg font-semibold transition-colors duration-200 flex items-center shadow-lg hover:shadow-xl transform hover:-translate-y-1">
                    <i class="fas fa-plus mr-2"></i>
                    Add New Menu Item
                </a>
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
                        <h3 class="text-2xl font-bold text-gray-900"><?php echo $totalItems; ?></h3>
                        <p class="text-gray-600">Total Items</p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-lg p-6 border-l-4 border-green-500">
                <div class="flex items-center">
                    <div class="p-3 bg-green-100 rounded-lg">
                        <i class="fas fa-check-circle text-2xl text-green-600"></i>
                    </div>
                    <div class="ml-4">
                        <h3 class="text-2xl font-bold text-gray-900"><?php echo $activeItems; ?></h3>
                        <p class="text-gray-600">Active Items</p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-lg p-6 border-l-4 border-yellow-500">
                <div class="flex items-center">
                    <div class="p-3 bg-yellow-100 rounded-lg">
                        <i class="fas fa-star text-2xl text-yellow-600"></i>
                    </div>
                    <div class="ml-4">
                        <h3 class="text-2xl font-bold text-gray-900"><?php echo $featuredItems; ?></h3>
                        <p class="text-gray-600">Featured Items</p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-lg p-6 border-l-4 border-gray-500">
                <div class="flex items-center">
                    <div class="p-3 bg-gray-100 rounded-lg">
                        <i class="fas fa-eye-slash text-2xl text-gray-600"></i>
                    </div>
                    <div class="ml-4">
                        <h3 class="text-2xl font-bold text-gray-900"><?php echo $inactiveItems; ?></h3>
                        <p class="text-gray-600">Inactive Items</p>
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
                <label class="block text-sm font-semibold text-gray-700 mb-2">Search Menu Items</label>
                <div class="relative">
                    <input type="text" id="searchInput" value="<?php echo htmlspecialchars($search); ?>"
                           placeholder="Search by name, description, or category..."
                           class="w-full pl-12 pr-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent transition-colors">
                    <i class="fas fa-search absolute left-4 top-1/2 transform -translate-y-1/2 text-gray-400"></i>
                </div>
            </div>

            <!-- Category Filter -->
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">Category</label>
                <select id="categoryFilter" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent transition-colors">
                    <option value="">All Categories</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?php echo $cat['id']; ?>" <?php echo $category == $cat['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($cat['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Status Filter -->
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">Status</label>
                <select id="statusFilter" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent transition-colors">
                    <option value="">All Status</option>
                    <option value="active" <?php echo $status === 'active' ? 'selected' : ''; ?>>Active</option>
                    <option value="inactive" <?php echo $status === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                    <option value="featured" <?php echo $status === 'featured' ? 'selected' : ''; ?>>Featured</option>
                </select>
            </div>
        </div>

        <!-- Quick Filter Tabs -->
        <div class="mt-6 pt-6 border-t border-gray-200">
            <div class="flex flex-wrap gap-2">
                <button class="filter-tab <?php echo empty($status) ? 'active' : ''; ?>" data-filter="all">
                    All Items <span class="ml-1 bg-gray-100 text-gray-600 px-2 py-1 rounded-full text-xs"><?php echo $totalItems; ?></span>
                </button>
                <button class="filter-tab <?php echo $status === 'active' ? 'active' : ''; ?>" data-filter="active">
                    Active <span class="ml-1 bg-green-100 text-green-600 px-2 py-1 rounded-full text-xs"><?php echo $activeItems; ?></span>
                </button>
                <button class="filter-tab <?php echo $status === 'featured' ? 'active' : ''; ?>" data-filter="featured">
                    Featured <span class="ml-1 bg-yellow-100 text-yellow-600 px-2 py-1 rounded-full text-xs"><?php echo $featuredItems; ?></span>
                </button>
                <button class="filter-tab <?php echo $status === 'inactive' ? 'active' : ''; ?>" data-filter="inactive">
                    Inactive <span class="ml-1 bg-gray-100 text-gray-600 px-2 py-1 rounded-full text-xs"><?php echo $inactiveItems; ?></span>
                </button>
                <?php if ($category || $status || $search): ?>
                    <button onclick="clearFilters()" class="ml-4 text-primary hover:text-primary-dark font-medium">
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
                $status = $item['status'] ?? 'active';
                $isActive = $status === 'active';
            ?>
                <div class="menu-item-card bg-white rounded-xl shadow-lg hover:shadow-xl transition-all duration-300 overflow-hidden border border-gray-100 group"
                     data-item-id="<?php echo $item['id']; ?>"
                     data-category="<?php echo $item['category_id']; ?>"
                     data-status="<?php echo $status; ?>"
                     data-featured="<?php echo $isFeatured ? '1' : '0'; ?>"
                     data-name="<?php echo strtolower($item['name']); ?>">

                    <!-- Image Section -->
                    <div class="relative h-48 overflow-hidden">
                        <?php if (!empty($item['image'])): ?>
                            <img src="../uploads/menu/<?php echo htmlspecialchars($item['image']); ?>"
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
                                <?php echo ucfirst($status); ?>
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
                                <a href="menu_edit.php?id=<?php echo $item['id']; ?>"
                                   class="bg-primary hover:bg-primary-dark text-white px-3 py-2.5 rounded-lg font-medium transition-all duration-200 text-center shadow-sm hover:shadow-md transform hover:-translate-y-0.5">
                                    <i class="fas fa-edit mr-2"></i>
                                    Edit
                                </a>
                                <button onclick="toggleStatus(<?php echo $item['id']; ?>, '<?php echo $isActive ? 'inactive' : 'active'; ?>')"
                                        class="px-3 py-2.5 rounded-lg font-medium transition-all duration-200 shadow-sm hover:shadow-md transform hover:-translate-y-0.5 <?php echo $isActive ? 'bg-yellow-100 text-yellow-700 hover:bg-yellow-200 border border-yellow-200' : 'bg-green-100 text-green-700 hover:bg-green-200 border border-green-200'; ?>">
                                    <i class="fas fa-<?php echo $isActive ? 'eye-slash' : 'eye'; ?> mr-2"></i>
                                    <?php echo $isActive ? 'Deactivate' : 'Activate'; ?>
                                </button>
                            </div>

                            <!-- Bottom Row - Secondary Actions -->
                            <div class="primary-actions-bottom grid grid-cols-2 gap-2">
                                <button onclick="deleteItem(<?php echo $item['id']; ?>, '<?php echo htmlspecialchars($item['name']); ?>')"
                                        class="px-3 py-2.5 rounded-lg font-medium transition-all duration-200 bg-red-100 text-red-700 hover:bg-red-200 border border-red-200 shadow-sm hover:shadow-md transform hover:-translate-y-0.5">
                                    <i class="fas fa-trash mr-2"></i>
                                    Delete
                                </button>
                                <button onclick="toggleFeatured(<?php echo $item['id']; ?>, <?php echo $isFeatured ? 'false' : 'true'; ?>)"
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
                        <?php if ($page > 1): ?>
                            <a href="?page=<?php echo $page - 1; ?><?php echo $category ? '&category=' . urlencode($category) : ''; ?><?php echo $status ? '&status=' . urlencode($status) : ''; ?>"
                               class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                                Previous
                            </a>
                        <?php endif; ?>
                        <?php if ($page < $total_pages): ?>
                            <a href="?page=<?php echo $page + 1; ?><?php echo $category ? '&category=' . urlencode($category) : ''; ?><?php echo $status ? '&status=' . urlencode($status) : ''; ?>"
                               class="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                                Next
                            </a>
                        <?php endif; ?>
                    </div>
                    <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
                        <div>
                            <p class="text-sm text-gray-700">
                                Showing <span class="font-medium"><?php echo $offset + 1; ?></span> to
                                <span class="font-medium"><?php echo min($offset + $per_page, $total_records); ?></span> of
                                <span class="font-medium"><?php echo $total_records; ?></span> results
                            </p>
                        </div>
                        <div>
                            <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px" aria-label="Pagination">
                                <?php if ($page > 1): ?>
                                    <a href="?page=<?php echo $page - 1; ?><?php echo $category ? '&category=' . urlencode($category) : ''; ?><?php echo $status ? '&status=' . urlencode($status) : ''; ?>"
                                       class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
                                        <span class="sr-only">Previous</span>
                                        <i class="fas fa-chevron-left h-5 w-5"></i>
                                    </a>
                                <?php endif; ?>

                                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                    <?php if ($i == 1 || $i == $total_pages || ($i >= $page - 2 && $i <= $page + 2)): ?>
                                        <a href="?page=<?php echo $i; ?><?php echo $category ? '&category=' . urlencode($category) : ''; ?><?php echo $status ? '&status=' . urlencode($status) : ''; ?>"
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
                                    <a href="?page=<?php echo $page + 1; ?><?php echo $category ? '&category=' . urlencode($category) : ''; ?><?php echo $status ? '&status=' . urlencode($status) : ''; ?>"
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
                    <?php if ($search || $category || $status): ?>
                        No items match your filters
                    <?php else: ?>
                        No menu items found
                    <?php endif; ?>
                </h3>
                <p class="text-gray-500 mb-6">
                    <?php if ($search || $category || $status): ?>
                        Try adjusting your search or filter criteria.
                    <?php else: ?>
                        Get started by creating your first menu item.
                    <?php endif; ?>
                </p>
                <div class="flex flex-col sm:flex-row gap-3 justify-center">
                    <a href="menu_add.php"
                       class="bg-primary hover:bg-primary-dark text-white px-6 py-3 rounded-lg font-semibold transition-colors duration-200 flex items-center justify-center">
                        <i class="fas fa-plus mr-2"></i>
                        Add Menu Item
                    </a>
                    <?php if ($search || $category || $status): ?>
                        <button onclick="clearFilters()"
                                class="bg-gray-100 hover:bg-gray-200 text-gray-700 px-6 py-3 rounded-lg font-semibold transition-colors duration-200">
                            Clear Filters
                        </button>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<script>
// Enhanced filtering functionality
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('searchInput');
    const categoryFilter = document.getElementById('categoryFilter');
    const statusFilter = document.getElementById('statusFilter');
    const filterTabs = document.querySelectorAll('.filter-tab');

    // Search functionality
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase();
            filterItems();

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

    // Category filter
    if (categoryFilter) {
        categoryFilter.addEventListener('change', function() {
            filterItems();

            // Update URL
            const url = new URL(window.location);
            if (this.value) {
                url.searchParams.set('category', this.value);
            } else {
                url.searchParams.delete('category');
            }
            window.history.pushState({}, '', url);
        });
    }

    // Status filter
    if (statusFilter) {
        statusFilter.addEventListener('change', function() {
            filterItems();

            // Update URL
            const url = new URL(window.location);
            if (this.value) {
                url.searchParams.set('status', this.value);
            } else {
                url.searchParams.delete('status');
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

            // Update status filter
            if (statusFilter) {
                if (filter === 'all') {
                    statusFilter.value = '';
                } else {
                    statusFilter.value = filter;
                }
            }

            filterItems();

            // Update URL
            const url = new URL(window.location);
            if (filter === 'all') {
                url.searchParams.delete('status');
            } else {
                url.searchParams.set('status', filter);
            }
            window.history.pushState({}, '', url);
        });
    });
});

function filterItems() {
    const searchTerm = document.getElementById('searchInput')?.value.toLowerCase() || '';
    const categoryId = document.getElementById('categoryFilter')?.value || '';
    const statusValue = document.getElementById('statusFilter')?.value || '';

    const items = document.querySelectorAll('.menu-item-card');
    let visibleCount = 0;

    items.forEach(item => {
        const name = item.dataset.name || '';
        const itemCategory = item.dataset.category || '';
        const itemStatus = item.dataset.status || '';
        const isFeatured = item.dataset.featured === '1';

        let shouldShow = true;

        // Search filter
        if (searchTerm && !name.includes(searchTerm)) {
            shouldShow = false;
        }

        // Category filter
        if (categoryId && itemCategory !== categoryId) {
            shouldShow = false;
        }

        // Status filter
        if (statusValue) {
            if (statusValue === 'featured' && !isFeatured) {
                shouldShow = false;
            } else if (statusValue !== 'featured' && itemStatus !== statusValue) {
                shouldShow = false;
            }
        }

        if (shouldShow) {
            item.style.display = 'block';
            visibleCount++;
        } else {
            item.style.display = 'none';
        }
    });

    // Show/hide empty state
    const grid = document.getElementById('menuItemsGrid');
    if (grid && visibleCount === 0) {
        if (!document.querySelector('.empty-state')) {
            const emptyState = document.createElement('div');
            emptyState.className = 'empty-state col-span-full text-center py-16';
            emptyState.innerHTML = `
                <i class="fas fa-search text-6xl text-gray-300 mb-4"></i>
                <h3 class="text-2xl font-semibold text-gray-700 mb-2">No items found</h3>
                <p class="text-gray-500">Try adjusting your search or filter criteria.</p>
            `;
            grid.parentNode.insertBefore(emptyState, grid.nextSibling);
        }
    } else {
        const emptyState = document.querySelector('.empty-state');
        if (emptyState) {
            emptyState.remove();
        }
    }
}

function clearFilters() {
    document.getElementById('searchInput').value = '';
    document.getElementById('categoryFilter').value = '';
    document.getElementById('statusFilter').value = '';

    // Reset active tab
    document.querySelectorAll('.filter-tab').forEach(tab => {
        tab.classList.toggle('active', tab.dataset.filter === 'all');
    });

    filterItems();

    // Update URL
    const url = new URL(window.location);
    url.searchParams.delete('search');
    url.searchParams.delete('category');
    url.searchParams.delete('status');
    window.history.pushState({}, '', url);
}

// Enhanced toggle functions
function toggleStatus(id, newStatus) {
    if (confirm('Are you sure you want to ' + (newStatus === 'active' ? 'activate' : 'deactivate') + ' this item?')) {
        const button = document.querySelector(`button[onclick*="toggleStatus(${id}"]`);
        if (button) {
            const originalText = button.innerHTML;
            button.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> Updating...';
            button.disabled = true;

            fetch('update_status.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: 'id=' + id + '&status=' + newStatus + '&type=menu_item'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert('Error updating status: ' + (data.message || 'Unknown error'));
                    button.innerHTML = originalText;
                    button.disabled = false;
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error updating status');
                button.innerHTML = originalText;
                button.disabled = false;
            });
        }
    }
}

function toggleFeatured(id, isFeatured) {
    const button = document.querySelector(`button[onclick*="toggleFeatured(${id}"]`);
    if (button) {
        const originalText = button.innerHTML;
        button.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> Updating...';
        button.disabled = true;

        fetch('update_status.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'id=' + id + '&is_featured=' + (isFeatured ? '1' : '0') + '&type=menu_item'
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Error updating featured status: ' + (data.message || 'Unknown error'));
                button.innerHTML = originalText;
                button.disabled = false;
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error updating featured status');
            button.innerHTML = originalText;
            button.disabled = false;
        });
    }
}

function deleteItem(id, name) {
    if (confirm(`Are you sure you want to delete "${name}"?\n\nThis action cannot be undone.`)) {
        const button = document.querySelector(`button[onclick*="deleteItem(${id}"]`);
        if (button) {
            const originalText = button.innerHTML;
            button.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> Deleting...';
            button.disabled = true;

            const formData = new FormData();
            formData.append('_method', 'DELETE');
            formData.append('id', id);

            fetch(window.location.href, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Show success message and remove item from DOM
                    const itemElement = document.querySelector(`[data-item-id="${id}"]`);
                    if (itemElement) {
                        itemElement.style.opacity = '0';
                        setTimeout(() => {
                            itemElement.remove();
                            // If no items left, show empty state
                            if (document.querySelectorAll('[data-item-id]').length === 0) {
                                location.reload();
                            }
                        }, 300);
                    }
                    showToast('success', data.message || 'Menu item deleted successfully');
                } else {
                    showToast('error', data.message || 'Failed to delete menu item');
                    button.innerHTML = originalText;
                    button.disabled = false;
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showToast('error', 'Error deleting menu item');
                button.innerHTML = originalText;
                button.disabled = false;
            });
        }
    }
}

function showToast(type, message) {
    // Remove existing toasts
    document.querySelectorAll('.toast').forEach(toast => toast.remove());

    const toast = document.createElement('div');
    toast.className = `toast fixed top-4 right-4 px-6 py-3 rounded-lg shadow-lg text-white font-medium z-50 ${type === 'success' ? 'bg-green-500' : 'bg-red-500'}`;
    toast.innerHTML = `
        <div class="flex items-center">
            <i class="fas ${type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'} mr-2"></i>
            <span>${message}</span>
        </div>
    `;

    document.body.appendChild(toast);
    setTimeout(() => toast.remove(), 3000);
}
</script>

<style>
/* Enhanced styling */
.line-clamp-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

/* Filter tabs */
.filter-tab {
    @apply px-4 py-2 rounded-full bg-gray-100 text-gray-700 font-medium transition-colors duration-200;
}

.filter-tab:hover {
    @apply bg-primary text-white;
}

.filter-tab.active {
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
button:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}

/* Toast notifications */
.toast {
    animation: slideIn 0.3s ease-out;
}

@keyframes slideIn {
    from {
        transform: translateX(100%);
        opacity: 0;
    }
    to {
        transform: translateX(0);
        opacity: 1;
    }
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

/* Action buttons styling */
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
    font-size: 0.875rem;
    font-weight: 500;
    padding: 0.625rem 0.75rem;
    border-radius: 0.5rem;
    transition: all 0.2s ease;
    white-space: nowrap;
    text-align: center;
    border: 1px solid transparent;
    position: relative;
    overflow: hidden;
}

.action-buttons .primary-actions-top button:hover,
.action-buttons .primary-actions-top a:hover,
.action-buttons .primary-actions-bottom button:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
}

.action-buttons .primary-actions-top button:focus,
.action-buttons .primary-actions-top a:focus,
.action-buttons .primary-actions-bottom button:focus {
    outline: none;
    ring: 2px solid rgba(193, 39, 45, 0.3);
}

/* Specific button color improvements */
.action-buttons .primary-actions-top a {
    background: linear-gradient(135deg, #C1272D 0%, #B01E24 100%);
}

.action-buttons .primary-actions-top a:hover {
    background: linear-gradient(135deg, #B01E24 0%, #A01720 100%);
}

/* Status button styling improvements */
.action-buttons .primary-actions-top button[class*="bg-green"] {
    background: linear-gradient(135deg, #10B981 0%, #059669 100%);
    color: white;
}

.action-buttons .primary-actions-top button[class*="bg-green"]:hover {
    background: linear-gradient(135deg, #059669 0%, #047857 100%);
}

.action-buttons .primary-actions-top button[class*="bg-yellow"] {
    background: linear-gradient(135deg, #F59E0B 0%, #D97706 100%);
    color: white;
}

.action-buttons .primary-actions-top button[class*="bg-yellow"]:hover {
    background: linear-gradient(135deg, #D97706 0%, #B45309 100%);
}

/* Delete button styling */
.action-buttons .primary-actions-bottom button[class*="bg-red"] {
    background: linear-gradient(135deg, #EF4444 0%, #DC2626 100%);
    color: white;
}

.action-buttons .primary-actions-bottom button[class*="bg-red"]:hover {
    background: linear-gradient(135deg, #DC2626 0%, #B91C1C 100%);
}

/* Featured button styling */
.action-buttons .primary-actions-bottom button[class*="bg-yellow"] {
    background: linear-gradient(135deg, #FCD34D 0%, #F59E0B 100%);
    color: #92400E;
}

.action-buttons .primary-actions-bottom button[class*="bg-yellow"]:hover {
    background: linear-gradient(135deg, #F59E0B 0%, #D97706 100%);
}

.action-buttons .primary-actions-bottom button[class*="bg-gray"] {
    background: linear-gradient(135deg, #F3F4F6 0%, #E5E7EB 100%);
    color: #374151;
}

.action-buttons .primary-actions-bottom button[class*="bg-gray"]:hover {
    background: linear-gradient(135deg, #E5E7EB 0%, #D1D5DB 100%);
}

<?php include 'includes/footer.php'; ?>