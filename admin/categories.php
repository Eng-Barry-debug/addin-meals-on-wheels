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

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_category'])) {
        // Add new category
        $name = trim($_POST['name']);
        $description = trim($_POST['description']);

        if (empty($name)) {
            $error = 'Category name is required';
        } else {
            try {
                $stmt = $pdo->prepare("INSERT INTO categories (name, description) VALUES (?, ?)");
                $stmt->execute([$name, $description]);
                $success = 'Category added successfully';
            } catch (PDOException $e) {
                $error = 'Error adding category: ' . $e->getMessage();
            }
        }
    } elseif (isset($_POST['update_category'])) {
        // Update existing category
        $id = (int)$_POST['category_id'];
        $name = trim($_POST['name']);
        $description = trim($_POST['description']);

        if (empty($name)) {
            $error = 'Category name is required';
        } else {
            try {
                $stmt = $pdo->prepare("UPDATE categories SET name = ?, description = ? WHERE id = ?");
                $stmt->execute([$name, $description, $id]);
                $success = 'Category updated successfully';
            } catch (PDOException $e) {
                $error = 'Error updating category: ' . $e->getMessage();
            }
        }
    } elseif (isset($_POST['delete_category'])) {
        // Delete category
        $id = (int)$_POST['category_id'];

        try {
            // First, check if there are any menu items in this category
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM menu_items WHERE category_id = ?");
            $stmt->execute([$id]);
            $count = $stmt->fetchColumn();

            if ($count > 0) {
                $error = 'Cannot delete category: There are menu items assigned to this category';
            } else {
                $stmt = $pdo->prepare("DELETE FROM categories WHERE id = ?");
                $stmt->execute([$id]);
                $success = 'Category deleted successfully';
            }
        } catch (PDOException $e) {
            $error = 'Error deleting category: ' . $e->getMessage();
        }
    }
}

// Get all categories with item counts
$categories = [];
try {
    $stmt = $pdo->query("
        SELECT c.*, COUNT(mi.id) as item_count
        FROM categories c
        LEFT JOIN menu_items mi ON c.id = mi.category_id AND mi.status = 'active'
        GROUP BY c.id, c.name, c.description, c.image, c.is_active, c.created_at, c.updated_at
        ORDER BY c.name
    ");
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = 'Error fetching categories: ' . $e->getMessage();
}

// Get statistics
$totalCategories = count($categories);
$activeCategories = count(array_filter($categories, fn($cat) => $cat['is_active']));
$totalItems = array_sum(array_column($categories, 'item_count'));

// Set page title
$page_title = 'Category Management - Admin Dashboard';

// Include header
require_once 'includes/header.php';
?>

<!-- Dashboard Header -->
<div class="bg-gradient-to-br from-primary via-primary-dark to-secondary text-white">
    <div class="container mx-auto px-6 py-8">
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between">
            <div>
                <h1 class="text-3xl md:text-4xl font-bold mb-2">Category Management</h1>
                <p class="text-lg opacity-90">Manage your food categories and organize your menu effectively</p>
            </div>
            <div class="mt-4 lg:mt-0">
                <button onclick="showAddCategoryModal()"
                        class="bg-accent hover:bg-accent-dark text-white px-6 py-3 rounded-lg font-semibold transition-colors duration-200 flex items-center shadow-lg hover:shadow-xl transform hover:-translate-y-1">
                    <i class="fas fa-plus mr-2"></i>
                    Add New Category
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
                        <i class="fas fa-layer-group text-2xl text-primary"></i>
                    </div>
                    <div class="ml-4">
                        <h3 class="text-2xl font-bold text-gray-900"><?php echo $totalCategories; ?></h3>
                        <p class="text-gray-600">Total Categories</p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-lg p-6 border-l-4 border-green-500">
                <div class="flex items-center">
                    <div class="p-3 bg-green-100 rounded-lg">
                        <i class="fas fa-check-circle text-2xl text-green-600"></i>
                    </div>
                    <div class="ml-4">
                        <h3 class="text-2xl font-bold text-gray-900"><?php echo $activeCategories; ?></h3>
                        <p class="text-gray-600">Active Categories</p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-lg p-6 border-l-4 border-secondary">
                <div class="flex items-center">
                    <div class="p-3 bg-secondary/10 rounded-lg">
                        <i class="fas fa-utensils text-2xl text-secondary"></i>
                    </div>
                    <div class="ml-4">
                        <h3 class="text-2xl font-bold text-gray-900"><?php echo $totalItems; ?></h3>
                        <p class="text-gray-600">Menu Items</p>
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
            <i class="fas fa-exclamation-triangle mr-3"></i>
            <div>
                <p class="font-semibold">Error</p>
                <p><?php echo htmlspecialchars($error); ?></p>
            </div>
        </div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="mb-6 p-4 bg-green-50 border-l-4 border-green-400 text-green-700 rounded-lg flex items-center">
            <i class="fas fa-check-circle mr-3"></i>
            <div>
                <p class="font-semibold">Success</p>
                <p><?php echo htmlspecialchars($success); ?></p>
            </div>
        </div>
    <?php endif; ?>

    <!-- Categories Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <?php if (empty($categories)): ?>
            <div class="col-span-full text-center py-16">
                <div class="max-w-md mx-auto">
                    <i class="fas fa-layer-group text-6xl text-gray-300 mb-4"></i>
                    <h3 class="text-2xl font-semibold text-gray-700 mb-2">No categories found</h3>
                    <p class="text-gray-500 mb-6">Get started by creating your first category.</p>
                    <button onclick="showAddCategoryModal()"
                            class="bg-primary hover:bg-primary-dark text-white px-6 py-3 rounded-lg font-semibold transition-colors duration-200">
                        <i class="fas fa-plus mr-2"></i>
                        Add Your First Category
                    </button>
                </div>
            </div>
        <?php else: ?>
            <?php foreach ($categories as $category): ?>
                <div class="bg-white rounded-xl shadow-lg hover:shadow-xl transition-all duration-300 overflow-hidden border border-gray-100">
                    <!-- Category Header -->
                    <div class="relative h-32 bg-gradient-to-br from-primary/10 to-secondary/10">
                        <?php if (!empty($category['image'])): ?>
                            <img src="../uploads/menu/<?php echo htmlspecialchars($category['image']); ?>"
                                 alt="<?php echo htmlspecialchars($category['name']); ?>"
                                 class="w-full h-full object-cover">
                        <?php endif; ?>
                        <div class="absolute inset-0 bg-gradient-to-t from-black/60 via-transparent to-transparent"></div>
                        <div class="absolute bottom-0 left-0 right-0 p-4 text-white">
                            <h3 class="text-lg font-bold mb-1"><?php echo htmlspecialchars($category['name']); ?></h3>
                            <div class="flex items-center text-sm">
                                <i class="fas fa-utensils mr-1"></i>
                                <span><?php echo $category['item_count']; ?> items</span>
                            </div>
                        </div>
                        <?php if (!$category['is_active']): ?>
                            <div class="absolute top-3 right-3">
                                <span class="bg-gray-500 text-white text-xs px-2 py-1 rounded-full">
                                    Inactive
                                </span>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Category Content -->
                    <div class="p-6">
                        <p class="text-gray-600 text-sm mb-4 line-clamp-3">
                            <?php echo htmlspecialchars($category['description'] ?? 'No description available'); ?>
                        </p>

                        <!-- Category Stats -->
                        <div class="grid grid-cols-2 gap-4 mb-4 text-sm">
                            <div class="text-center p-3 bg-gray-50 rounded-lg">
                                <div class="font-bold text-primary"><?php echo $category['item_count']; ?></div>
                                <div class="text-gray-600">Items</div>
                            </div>
                            <div class="text-center p-3 bg-gray-50 rounded-lg">
                                <div class="font-bold text-green-600"><?php echo $category['is_active'] ? 'Active' : 'Inactive'; ?></div>
                                <div class="text-gray-600">Status</div>
                            </div>
                        </div>

                        <!-- Action Buttons -->
                        <div class="flex gap-2">
                            <button onclick="editCategory(<?php echo htmlspecialchars(json_encode($category)); ?>)"
                                    class="flex-1 bg-primary hover:bg-primary-dark text-white px-4 py-2 rounded-lg font-medium transition-colors duration-200 flex items-center justify-center">
                                <i class="fas fa-edit mr-2"></i>
                                Edit
                            </button>
                            <form action="" method="POST" class="flex-1" onsubmit="return confirmDelete('<?php echo htmlspecialchars($category['name']); ?>')">
                                <input type="hidden" name="category_id" value="<?php echo $category['id']; ?>">
                                <button type="submit" name="delete_category"
                                        class="w-full bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded-lg font-medium transition-colors duration-200 flex items-center justify-center">
                                    <i class="fas fa-trash mr-2"></i>
                                    Delete
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<!-- Enhanced Add/Edit Category Modal -->
<div id="categoryModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
    <div class="bg-white rounded-2xl shadow-2xl max-w-md w-full max-h-[90vh] overflow-y-auto">
        <!-- Modal Header -->
        <div class="bg-gradient-to-r from-primary to-secondary p-6 text-white rounded-t-2xl">
            <div class="flex items-center justify-between">
                <h3 class="text-xl font-bold">
                    <span id="modalTitle">Add New Category</span>
                </h3>
                <button onclick="closeCategoryModal()" class="text-white hover:text-gray-200 text-2xl">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        </div>

        <!-- Modal Body -->
        <div class="p-6">
            <form id="categoryForm" action="" method="POST">
                <input type="hidden" name="category_id" id="categoryId">

                <div class="mb-6">
                    <label for="name" class="block text-sm font-semibold text-gray-700 mb-2">
                        Category Name <span class="text-red-500">*</span>
                    </label>
                    <input type="text" name="name" id="name" required
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent transition-colors"
                           placeholder="Enter category name">
                </div>

                <div class="mb-6">
                    <label for="description" class="block text-sm font-semibold text-gray-700 mb-2">
                        Description
                    </label>
                    <textarea name="description" id="description" rows="4"
                              class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent transition-colors"
                              placeholder="Describe this category..."></textarea>
                </div>

                <!-- Modal Footer -->
                <div class="flex gap-3 pt-4">
                    <button type="submit" name="add_category" id="submitBtn"
                            class="flex-1 bg-primary hover:bg-primary-dark text-white px-6 py-3 rounded-lg font-semibold transition-colors duration-200 flex items-center justify-center">
                        <i class="fas fa-save mr-2"></i>
                        Add Category
                    </button>
                    <button type="button" onclick="closeCategoryModal()"
                            class="px-6 py-3 border border-gray-300 rounded-lg font-medium hover:bg-gray-50 transition-colors duration-200">
                        Cancel
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function showAddCategoryModal() {
    document.getElementById('modalTitle').textContent = 'Add New Category';
    document.getElementById('categoryForm').reset();
    document.getElementById('categoryId').value = '';
    document.getElementById('submitBtn').name = 'add_category';
    document.getElementById('submitBtn').innerHTML = '<i class="fas fa-plus mr-2"></i>Add Category';
    document.getElementById('categoryModal').classList.remove('hidden');
}

function editCategory(category) {
    document.getElementById('modalTitle').textContent = 'Edit Category';
    document.getElementById('categoryId').value = category.id;
    document.getElementById('name').value = category.name;
    document.getElementById('description').value = category.description || '';

    // Update form action
    document.getElementById('submitBtn').name = 'update_category';
    document.getElementById('submitBtn').innerHTML = '<i class="fas fa-save mr-2"></i>Update Category';

    document.getElementById('categoryModal').classList.remove('hidden');
}

function closeCategoryModal() {
    document.getElementById('categoryModal').classList.add('hidden');
    document.getElementById('categoryForm').reset();
}

function confirmDelete(categoryName) {
    return confirm(`Are you sure you want to delete "${categoryName}"?\n\nThis action cannot be undone. Make sure no menu items are assigned to this category first.`);
}

// Close modal when clicking outside
document.getElementById('categoryModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeCategoryModal();
    }
});

// Enhanced form validation
document.getElementById('categoryForm').addEventListener('submit', function(e) {
    const name = document.getElementById('name').value.trim();

    if (name.length < 2) {
        e.preventDefault();
        alert('Category name must be at least 2 characters long');
        return false;
    }

    // Show loading state
    const submitBtn = document.getElementById('submitBtn');
    const originalText = submitBtn.innerHTML;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Processing...';
    submitBtn.disabled = true;

    // Re-enable after 3 seconds as fallback
    setTimeout(() => {
        submitBtn.innerHTML = originalText;
        submitBtn.disabled = false;
    }, 3000);
});
</script>

<style>
/* Enhanced styling for professional look */
.line-clamp-3 {
    display: -webkit-box;
    -webkit-line-clamp: 3;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

/* Smooth hover transitions */
.category-card {
    transition: all 0.3s ease;
}

.category-card:hover {
    transform: translateY(-4px);
}

/* Custom scrollbar for webkit browsers */
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

::-webkit-scrollbar-thumb:hover {
    background: #B01E24;
}

/* Modal animations */
#categoryModal {
    animation: fadeIn 0.3s ease-out;
}

@keyframes fadeIn {
    from {
        opacity: 0;
        transform: scale(0.9);
    }
    to {
        opacity: 1;
        transform: scale(1);
    }
}

/* Form focus states */
input:focus, textarea:focus {
    box-shadow: 0 0 0 3px rgba(193, 39, 45, 0.1);
}

/* Button hover effects */
button {
    transition: all 0.2s ease;
}

button:hover {
    transform: translateY(-1px);
}

/* Loading state */
#submitBtn:disabled {
    opacity: 0.7;
    cursor: not-allowed;
    transform: none;
}
</style>

<?php
// Include footer
require_once 'includes/footer.php';
?>
