<?php
// Start session and check admin authentication
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include database connection
require_once '../includes/config.php';

// Include activity logger
require_once '../includes/ActivityLogger.php';
$activityLogger = new ActivityLogger($pdo);

// Initialize variables
$error = '';
$success = '';
$categories = [];
$menu_item = null;

// Get all categories for the dropdown
try {
    $stmt = $pdo->query("SELECT * FROM categories ORDER BY name");
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = 'Error loading categories: ' . $e->getMessage();
}

// Get menu item data if editing
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id > 0) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM menu_items WHERE id = ?");
        $stmt->execute([$id]);
        $menu_item = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$menu_item) {
            $_SESSION['message'] = ['type' => 'error', 'text' => 'Menu item not found'];
            header('Location: menu.php');
            exit();
        }
    } catch (PDOException $e) {
        $error = 'Error loading menu item: ' . $e->getMessage();
    }
} else {
    header('Location: menu.php');
    exit();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $price = trim($_POST['price'] ?? '');
    $category_id = (int)($_POST['category_id'] ?? 0);
    $is_available = isset($_POST['is_available']) ? 1 : 0;
    $is_featured = isset($_POST['is_featured']) ? 1 : 0;

    // Validate input
    if (empty($name)) {
        $error = 'Menu item name is required';
    } elseif (!is_numeric($price) || $price <= 0) {
        $error = 'Please enter a valid price greater than 0';
    } elseif ($category_id <= 0) {
        $error = 'Please select a category';
    } elseif (strlen($description) > 1000) {
        $error = 'Description must be less than 1000 characters';
    } else {
        try {
            // Start transaction
            $pdo->beginTransaction();

            // Handle file upload if a new image is provided
            $image_path = $menu_item['image'] ?? '';
            if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                $upload_dir = '../uploads/menu/';
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }

                $file_extension = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
                $filename = uniqid() . '.' . $file_extension;
                $target_path = $upload_dir . $filename;

                if (move_uploaded_file($_FILES['image']['tmp_name'], $target_path)) {
                    // Delete old image if it exists
                    if (!empty($menu_item['image']) && file_exists($upload_dir . $menu_item['image'])) {
                        unlink($upload_dir . $menu_item['image']);
                    }
                    $image_path = $filename;
                } else {
                    throw new Exception('Failed to upload image');
                }
            }

            // Update menu item in database
            $stmt = $pdo->prepare("
                UPDATE menu_items
                SET name = ?, description = ?, price = ?, category_id = ?, is_available = ?, is_featured = ?,
                    image = COALESCE(?, image), updated_at = NOW()
                WHERE id = ?
            ");

            $stmt->execute([
                $name,
                $description,
                $price,
                $category_id,
                $is_available,
                $is_featured,
                $image_path === $menu_item['image'] ? null : $image_path,
                $id
            ]);

            $pdo->commit();

            // Log activity
            $activityLogger->log('menu', 'updated', "Updated menu item: {$name}", 'menu_item', $id);

            $_SESSION['message'] = ['type' => 'success', 'text' => 'Menu item updated successfully'];
            header('Location: menu.php');
            exit();

        } catch (Exception $e) {
            $pdo->rollBack();
            $error = 'Error updating menu item: ' . $e->getMessage();
        }
    }
}

// Set page title and include header
$page_title = 'Edit Menu Item';
$page_description = 'Edit an existing menu item';
include 'includes/header.php';
?>

<!-- Edit Menu Item Header -->
<div class="bg-gradient-to-br from-primary via-primary-dark to-secondary text-white">
    <div class="container mx-auto px-6 py-12">
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between">
            <div class="flex items-center space-x-6">
                <div class="h-20 w-20 rounded-full bg-white/20 backdrop-blur-sm flex items-center justify-center text-white font-bold text-3xl shadow-lg">
                    <i class="fas fa-edit"></i>
                </div>
                <div>
                    <h1 class="text-3xl md:text-4xl font-bold mb-2">Edit Menu Item</h1>
                    <p class="text-lg opacity-90 mb-2">Modify <?php echo htmlspecialchars($menu_item['name'] ?? ''); ?> details and settings</p>
                    <div class="flex items-center space-x-4">
                        <span class="px-3 py-1 bg-white/20 backdrop-blur-sm rounded-full text-sm font-medium">
                            <i class="fas fa-tag mr-1"></i>
                            <?php echo htmlspecialchars($menu_item['name'] ?? ''); ?>
                        </span>
                        <span class="px-3 py-1 bg-white/20 backdrop-blur-sm rounded-full text-sm font-medium">
                            <i class="fas fa-dollar-sign mr-1"></i>
                            KES <?php echo number_format($menu_item['price'] ?? 0, 2); ?>
                        </span>
                    </div>
                </div>
            </div>
            <div class="mt-6 lg:mt-0">
                <div class="flex items-center space-x-4 text-sm">
                    <div class="flex items-center space-x-2 bg-white/10 backdrop-blur-sm rounded-lg px-3 py-2">
                        <i class="fas fa-clock text-blue-300"></i>
                        <span>Last updated: <?php echo date('M j, g:i A'); ?></span>
                    </div>
                    <a href="menu.php"
                       class="bg-white/20 hover:bg-white/30 text-white px-4 py-2 rounded-lg font-medium transition-all duration-200 flex items-center backdrop-blur-sm">
                        <i class="fas fa-arrow-left mr-2"></i>
                        Back to Menu
                    </a>
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

    <!-- Edit Menu Item Form -->
    <form action="" method="POST" enctype="multipart/form-data" class="space-y-8">
        <!-- Basic Information Card -->
        <div class="bg-white rounded-xl shadow-lg p-6">
            <div class="flex items-center justify-between mb-6 pb-4 border-b border-gray-200">
                <div class="flex items-center space-x-3">
                    <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
                        <i class="fas fa-info-circle text-blue-600 text-xl"></i>
                    </div>
                    <div>
                        <h3 class="text-xl font-bold text-gray-900">Basic Information</h3>
                        <p class="text-sm text-gray-600">Update the essential details for your menu item</p>
                    </div>
                </div>
                <div class="text-right">
                    <span class="px-3 py-1 bg-blue-100 text-blue-600 rounded-full text-xs font-medium">
                        Required
                    </span>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Item Name -->
                <div class="md:col-span-2">
                    <label for="name" class="block text-sm font-semibold text-gray-700 mb-2">
                        <i class="fas fa-tag mr-2 text-primary"></i>
                        Item Name <span class="text-red-500">*</span>
                    </label>
                    <input type="text"
                           name="name"
                           id="name"
                           required
                           value="<?php echo htmlspecialchars($menu_item['name'] ?? ''); ?>"
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent transition-all duration-200 text-sm"
                           placeholder="e.g., Jollof Rice, Ugali & Sukuma, Grilled Chicken">
                    <p class="text-xs text-gray-500 mt-1">Enter a catchy, descriptive name for your menu item</p>
                </div>

                <!-- Category Selection -->
                <div>
                    <label for="category_id" class="block text-sm font-semibold text-gray-700 mb-2">
                        <i class="fas fa-folder mr-2 text-primary"></i>
                        Category <span class="text-red-500">*</span>
                    </label>
                    <select id="category_id"
                            name="category_id"
                            required
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent transition-all duration-200 text-sm">
                        <option value="">Select a category</option>
                        <?php foreach ($categories as $category): ?>
                            <option value="<?php echo $category['id']; ?>"
                                <?php echo (isset($menu_item['category_id']) && $menu_item['category_id'] == $category['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($category['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <p class="text-xs text-gray-500 mt-1">Choose the most appropriate category for this item</p>
                </div>

                <!-- Price -->
                <div>
                    <label for="price" class="block text-sm font-semibold text-gray-700 mb-2">
                        <i class="fas fa-dollar-sign mr-2 text-primary"></i>
                        Price (KES) <span class="text-red-500">*</span>
                    </label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <span class="text-gray-500 text-sm">KES</span>
                        </div>
                        <input type="number"
                               name="price"
                               id="price"
                               required
                               step="0.01"
                               min="0"
                               value="<?php echo htmlspecialchars($menu_item['price'] ?? ''); ?>"
                               class="w-full pl-12 pr-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent transition-all duration-200 text-sm"
                               placeholder="0.00">
                    </div>
                    <p class="text-xs text-gray-500 mt-1">Enter the price in Kenyan Shillings</p>
                </div>

                <!-- Description -->
                <div class="md:col-span-2">
                    <label for="description" class="block text-sm font-semibold text-gray-700 mb-2">
                        <i class="fas fa-align-left mr-2 text-primary"></i>
                        Description
                    </label>
                    <textarea id="description"
                              name="description"
                              rows="4"
                              maxlength="1000"
                              class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent transition-all duration-200 text-sm resize-none"
                              placeholder="Describe your menu item - ingredients, preparation method, taste profile, dietary information, etc."><?php echo htmlspecialchars($menu_item['description'] ?? ''); ?></textarea>
                    <div class="flex justify-between mt-1">
                        <p class="text-xs text-gray-500">Provide a detailed description to help customers make informed choices</p>
                        <span class="text-xs text-gray-400" id="char-count">0/1000</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Image Upload Card -->
        <div class="bg-white rounded-xl shadow-lg p-6">
            <div class="flex items-center justify-between mb-6 pb-4 border-b border-gray-200">
                <div class="flex items-center space-x-3">
                    <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center">
                        <i class="fas fa-camera text-green-600 text-xl"></i>
                    </div>
                    <div>
                        <h3 class="text-xl font-bold text-gray-900">Item Image</h3>
                        <p class="text-sm text-gray-600">Update the photo for your menu item</p>
                    </div>
                </div>
                <div class="text-right">
                    <span class="px-3 py-1 bg-green-100 text-green-600 rounded-full text-xs font-medium">
                        Optional
                    </span>
                </div>
            </div>

            <!-- Current Image Display -->
            <?php if (!empty($menu_item['image'])): ?>
                <div class="mb-6 p-4 bg-gray-50 rounded-lg">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center space-x-4">
                            <img src="../uploads/menu/<?php echo htmlspecialchars($menu_item['image']); ?>"
                                 alt="Current image"
                                 class="h-16 w-16 object-cover rounded-lg">
                            <div>
                                <p class="text-sm font-medium text-gray-900">Current Image</p>
                                <p class="text-xs text-gray-500"><?php echo htmlspecialchars($menu_item['image']); ?></p>
                            </div>
                        </div>
                        <div class="flex items-center">
                            <input type="hidden" name="remove_image" value="0">
                            <input type="checkbox" name="remove_image" value="1" id="remove_image" class="rounded text-primary">
                            <label for="remove_image" class="ml-2 text-sm text-gray-700">Remove image</label>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Image Upload Area -->
            <div class="border-2 border-dashed border-gray-300 rounded-lg p-8 text-center hover:border-primary transition-colors duration-200">
                <div id="upload-area" class="space-y-4">
                    <div class="mx-auto h-24 w-24 text-gray-400">
                        <i class="fas fa-cloud-upload-alt text-6xl"></i>
                    </div>
                    <div>
                        <label for="image" class="cursor-pointer">
                            <span class="mt-2 block text-sm font-medium text-gray-900">
                                Drop new image here or click to browse
                            </span>
                            <span class="mt-1 block text-xs text-gray-500">
                                PNG, JPG, GIF, WEBP up to 5MB
                            </span>
                        </label>
                        <input id="image"
                               name="image"
                               type="file"
                               class="sr-only"
                               accept="image/*"
                               onchange="handleImageUpload(this)">
                    </div>
                </div>

                <!-- Image Preview -->
                <div id="image-preview-container" class="hidden mt-6">
                    <div class="relative inline-block">
                        <img id="image-preview" src="#" alt="Preview" class="max-h-48 max-w-xs rounded-lg shadow-lg">
                        <button type="button"
                                onclick="removeImage()"
                                class="absolute -top-2 -right-2 bg-red-500 text-white rounded-full w-6 h-6 flex items-center justify-center hover:bg-red-600 transition-colors">
                            <i class="fas fa-times text-xs"></i>
                        </button>
                    </div>
                    <p class="mt-2 text-sm text-gray-600">New image preview - will replace current image</p>
                </div>
            </div>
        </div>

        <!-- Settings Card -->
        <div class="bg-white rounded-xl shadow-lg p-6">
            <div class="flex items-center justify-between mb-6 pb-4 border-b border-gray-200">
                <div class="flex items-center space-x-3">
                    <div class="w-12 h-12 bg-purple-100 rounded-lg flex items-center justify-center">
                        <i class="fas fa-cog text-purple-600 text-xl"></i>
                    </div>
                    <div>
                        <h3 class="text-xl font-bold text-gray-900">Item Settings</h3>
                        <p class="text-sm text-gray-600">Configure availability and display options</p>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Availability Toggle -->
                <div class="space-y-4">
                    <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg">
                        <div class="flex items-center space-x-3">
                            <div class="w-10 h-10 bg-green-100 rounded-full flex items-center justify-center">
                                <i class="fas fa-check-circle text-green-600"></i>
                            </div>
                            <div>
                                <label for="is_available" class="font-medium text-gray-900">Available for Ordering</label>
                                <p class="text-sm text-gray-600">Customers can order this item</p>
                            </div>
                        </div>
                        <div class="flex items-center">
                            <input type="hidden" name="is_available" value="0">
                            <input type="checkbox"
                                   name="is_available"
                                   id="is_available"
                                   value="1"
                                   <?php echo (isset($menu_item['is_available']) && $menu_item['is_available']) ? 'checked' : ''; ?>
                                   class="sr-only">
                            <label for="is_available" class="flex items-center cursor-pointer">
                                <div class="relative">
                                    <div class="w-12 h-6 bg-gray-400 rounded-full shadow-inner transition-colors duration-200 <?php echo (isset($menu_item['is_available']) && $menu_item['is_available']) ? 'bg-primary' : ''; ?>"></div>
                                    <div class="absolute w-4 h-4 bg-white rounded-full shadow -left-1 -top-1 transition-transform duration-200 transform <?php echo (isset($menu_item['is_available']) && $menu_item['is_available']) ? 'translate-x-6' : ''; ?>"></div>
                                </div>
                            </label>
                        </div>
                    </div>
                </div>

                <!-- Featured Toggle -->
                <div class="space-y-4">
                    <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg">
                        <div class="flex items-center space-x-3">
                            <div class="w-10 h-10 bg-yellow-100 rounded-full flex items-center justify-center">
                                <i class="fas fa-star text-yellow-600"></i>
                            </div>
                            <div>
                                <label for="is_featured" class="font-medium text-gray-900">Featured Item</label>
                                <p class="text-sm text-gray-600">Highlight as a special recommendation</p>
                            </div>
                        </div>
                        <div class="flex items-center">
                            <input type="hidden" name="is_featured" value="0">
                            <input type="checkbox"
                                   name="is_featured"
                                   id="is_featured"
                                   value="1"
                                   <?php echo (isset($menu_item['is_featured']) && $menu_item['is_featured']) ? 'checked' : ''; ?>
                                   class="sr-only">
                            <label for="is_featured" class="flex items-center cursor-pointer">
                                <div class="relative">
                                    <div class="w-12 h-6 bg-gray-400 rounded-full shadow-inner transition-colors duration-200 <?php echo (isset($menu_item['is_featured']) && $menu_item['is_featured']) ? 'bg-primary' : ''; ?>"></div>
                                    <div class="absolute w-4 h-4 bg-white rounded-full shadow -left-1 -top-1 transition-transform duration-200 transform <?php echo (isset($menu_item['is_featured']) && $menu_item['is_featured']) ? 'translate-x-6' : ''; ?>"></div>
                                </div>
                            </label>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Submit Section -->
        <div class="bg-white rounded-xl shadow-lg p-6">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-lg font-semibold text-gray-900">Ready to Update Item?</h3>
                    <p class="text-sm text-gray-600">Review your changes and save the updated menu item</p>
                </div>
                <div class="flex space-x-4">
                    <a href="menu.php"
                       class="px-6 py-3 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition-colors duration-200 font-medium flex items-center">
                        <i class="fas fa-times mr-2"></i>
                        Cancel
                    </a>
                    <button type="submit"
                            class="px-8 py-3 bg-primary hover:bg-primary-dark text-white rounded-lg font-semibold transition-all duration-200 flex items-center shadow-lg hover:shadow-xl transform hover:-translate-y-1">
                        <i class="fas fa-save mr-2"></i>
                        Update Menu Item
                    </button>
                </div>
            </div>
        </div>
    </form>
</div>

<style>
/* Enhanced form styling */
input:focus, textarea:focus, select:focus {
    box-shadow: 0 0 0 3px rgba(193, 39, 45, 0.1);
    transform: translateY(-1px);
}

/* Image upload area styling */
#upload-area.dragover {
    border-color: #C1272D;
    background-color: rgba(193, 39, 45, 0.05);
}

#upload-area.dragover i {
    color: #C1272D;
}

/* Toggle switch enhancements */
input[type="checkbox"] + label div {
    transition: all 0.3s ease;
}

/* Card hover effects */
.bg-white:hover {
    transform: translateY(-2px);
    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
}

/* Form field animations */
.form-field {
    transition: all 0.3s ease;
}

.form-field:hover {
    transform: translateY(-1px);
}

/* Status indicators */
.status-indicator {
    @apply px-3 py-1 text-xs font-medium rounded-full;
}

.status-available {
    @apply bg-green-100 text-green-800;
}

.status-unavailable {
    @apply bg-red-100 text-red-800;
}

.status-featured {
    @apply bg-yellow-100 text-yellow-800;
}

/* Image preview styling */
#image-preview-container {
    animation: fadeIn 0.3s ease-in;
}

@keyframes fadeIn {
    from { opacity: 0; transform: scale(0.9); }
    to { opacity: 1; transform: scale(1); }
}

/* Character counter styling */
#char-count.warning {
    color: #f59e0b;
}

#char-count.error {
    color: #ef4444;
}

/* Current image styling */
.current-image-container {
    @apply p-4 bg-gray-50 rounded-lg;
}

.current-image-container:hover {
    @apply bg-gray-100;
}

/* Enhanced button styles */
.btn-primary-enhanced {
    @apply px-8 py-3 bg-primary hover:bg-primary-dark text-white rounded-lg font-semibold transition-all duration-200 flex items-center shadow-lg hover:shadow-xl transform hover:-translate-y-1;
}

.btn-secondary-enhanced {
    @apply px-6 py-3 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition-colors duration-200 font-medium flex items-center;
}
</style>

<script>
// Enhanced form functionality
document.addEventListener('DOMContentLoaded', function() {
    // Character counter for description
    const descriptionField = document.getElementById('description');
    const charCount = document.getElementById('char-count');

    if (descriptionField && charCount) {
        // Set initial count
        const initialLength = descriptionField.value.length;
        charCount.textContent = initialLength + '/1000';

        if (initialLength > 900) {
            charCount.className = 'text-xs error';
        } else if (initialLength > 800) {
            charCount.className = 'text-xs warning';
        } else {
            charCount.className = 'text-xs text-gray-400';
        }

        descriptionField.addEventListener('input', function() {
            const length = this.value.length;
            charCount.textContent = length + '/1000';

            if (length > 900) {
                charCount.className = 'text-xs error';
            } else if (length > 800) {
                charCount.className = 'text-xs warning';
            } else {
                charCount.className = 'text-xs text-gray-400';
            }
        });
    }

    // Drag and drop for image upload
    const uploadArea = document.getElementById('upload-area');
    const imageInput = document.getElementById('image');

    if (uploadArea && imageInput) {
        // Drag and drop events
        ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
            uploadArea.addEventListener(eventName, preventDefaults, false);
        });

        function preventDefaults(e) {
            e.preventDefault();
            e.stopPropagation();
        }

        ['dragenter', 'dragover'].forEach(eventName => {
            uploadArea.addEventListener(eventName, highlight, false);
        });

        ['dragleave', 'drop'].forEach(eventName => {
            uploadArea.addEventListener(eventName, unhighlight, false);
        });

        function highlight(e) {
            uploadArea.classList.add('dragover');
        }

        function unhighlight(e) {
            uploadArea.classList.remove('dragover');
        }

        uploadArea.addEventListener('drop', handleDrop, false);
        uploadArea.addEventListener('click', () => imageInput.click());

        function handleDrop(e) {
            const dt = e.dataTransfer;
            const files = dt.files;

            if (files.length > 0) {
                imageInput.files = files;
                handleImageUpload(imageInput);
            }
        }
    }

    // Form validation
    const form = document.querySelector('form');
    if (form) {
        form.addEventListener('submit', function(e) {
            const name = document.getElementById('name').value.trim();
            const category = document.getElementById('category_id').value;
            const price = document.getElementById('price').value;

            if (!name || !category || !price) {
                e.preventDefault();
                showError('Please fill in all required fields');
                return;
            }

            if (parseFloat(price) <= 0) {
                e.preventDefault();
                showError('Please enter a valid price greater than 0');
                return;
            }
        });
    }

    // Price validation
    const priceField = document.getElementById('price');
    if (priceField) {
        priceField.addEventListener('input', function() {
            if (this.value < 0) {
                this.value = 0;
            }
        });
    }

    // Show current image preview if exists
    <?php if (!empty($menu_item['image'])): ?>
    const currentPreview = document.getElementById('image-preview');
    const currentPreviewContainer = document.getElementById('image-preview-container');
    if (currentPreview && currentPreviewContainer) {
        currentPreview.src = '../uploads/menu/<?php echo htmlspecialchars($menu_item['image']); ?>';
        currentPreviewContainer.classList.remove('hidden');
    }
    <?php endif; ?>
});

// Image handling functions
function handleImageUpload(input) {
    const file = input.files[0];
    const previewContainer = document.getElementById('image-preview-container');
    const preview = document.getElementById('image-preview');
    const uploadArea = document.getElementById('upload-area');

    if (file) {
        // Validate file size (5MB)
        if (file.size > 5000000) {
            showError('Image size should not exceed 5MB');
            input.value = '';
            return;
        }

        // Validate file type
        const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
        if (!allowedTypes.includes(file.type)) {
            showError('Please select a valid image file (JPG, PNG, GIF, WEBP)');
            input.value = '';
            return;
        }

        const reader = new FileReader();
        reader.onload = function(e) {
            preview.src = e.target.result;
            previewContainer.classList.remove('hidden');
            uploadArea.style.display = 'none';
        };
        reader.readAsDataURL(file);
    }
}

function removeImage() {
    const previewContainer = document.getElementById('image-preview-container');
    const uploadArea = document.getElementById('upload-area');
    const imageInput = document.getElementById('image');

    previewContainer.classList.add('hidden');
    uploadArea.style.display = 'block';
    imageInput.value = '';
}

function showError(message) {
    const errorDiv = document.createElement('div');
    errorDiv.className = 'fixed top-4 right-4 bg-red-100 text-red-800 px-4 py-2 rounded-lg shadow-lg z-50';
    errorDiv.innerHTML = '<i class="fas fa-exclamation-circle mr-2"></i>' + message;
    document.body.appendChild(errorDiv);

    setTimeout(() => {
        errorDiv.remove();
    }, 5000);
}

// Category quick-add functionality (placeholder for future enhancement)
function showAddCategoryModal() {
    // This would open a modal to quickly add a new category
    // For now, just show a message
    showError('Quick category creation coming soon! Please add categories from the Categories page.');
}
</script>

<?php
// Include footer
require_once 'includes/footer.php';
?>
