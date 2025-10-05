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

// Get all categories for the dropdown
try {
    $stmt = $pdo->query("SELECT * FROM categories ORDER BY name");
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = 'Error loading categories: ' . $e->getMessage();
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
            // Handle file upload
            $image_path = '';
            if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                $upload_dir = '../uploads/menu/';
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }

                $file_extension = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
                $file_name = uniqid() . '.' . $file_extension;
                $target_file = $upload_dir . $file_name;

                // Check if image file is an actual image
                $check = getimagesize($_FILES['image']['tmp_name']);
                if ($check === false) {
                    throw new Exception('File is not an image');
                }

                // Check file size (max 5MB)
                if ($_FILES['image']['size'] > 5000000) {
                    throw new Exception('Image size should not exceed 5MB');
                }

                // Allow certain file formats
                $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
                if (!in_array($file_extension, $allowed_extensions)) {
                    throw new Exception('Only JPG, JPEG, PNG, GIF & WEBP files are allowed');
                }

                if (move_uploaded_file($_FILES['image']['tmp_name'], $target_file)) {
                    $image_path = 'uploads/menu/' . $file_name;
                } else {
                    throw new Exception('Error uploading image');
                }
            }

            // Insert into database
            $stmt = $pdo->prepare("
                INSERT INTO menu_items (name, description, price, image, category_id, is_available, is_featured, created_at, status)
                VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), 'active')
            ");

            $stmt->execute([
                $name,
                $description,
                $price,
                $image_path,
                $category_id,
                $is_available,
                $is_featured
            ]);

            $new_item_id = $pdo->lastInsertId();

            // Log activity
            $activityLogger->log('menu', 'created', "Added new menu item: {$name}", 'menu_item', $new_item_id);

            $success = "Menu item '{$name}' added successfully!";

            // Clear form
            $_POST = [];

        } catch (Exception $e) {
            $error = 'Error adding menu item: ' . $e->getMessage();

            // Delete the uploaded file if there was an error
            if (!empty($target_file) && file_exists($target_file)) {
                unlink($target_file);
            }
        }
    }
}

// Set page title
$page_title = 'Add New Menu Item';

// Include header
require_once 'includes/header.php';
?>

<!-- Add Menu Item Header -->
<div class="bg-gradient-to-br from-primary via-primary-dark to-secondary text-white">
    <div class="container mx-auto px-6 py-12">
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between">
            <div class="flex items-center space-x-6">
                <div class="h-20 w-20 rounded-full bg-white/20 backdrop-blur-sm flex items-center justify-center text-white font-bold text-3xl shadow-lg">
                    <i class="fas fa-utensils"></i>
                </div>
                <div>
                    <h1 class="text-3xl md:text-4xl font-bold mb-2">Add New Menu Item</h1>
                    <p class="text-lg opacity-90 mb-2">Create a delicious new item for your menu</p>
                    <div class="flex items-center space-x-4">
                        <span class="px-3 py-1 bg-white/20 backdrop-blur-sm rounded-full text-sm font-medium">
                            <i class="fas fa-list mr-1"></i>
                            <?php echo count($categories); ?> Categories Available
                        </span>
                        <span class="px-3 py-1 bg-white/20 backdrop-blur-sm rounded-full text-sm font-medium">
                            <i class="fas fa-image mr-1"></i>
                            Image Upload
                        </span>
                    </div>
                </div>
            </div>
            <div class="mt-6 lg:mt-0">
                <div class="flex items-center space-x-4 text-sm">
                    <div class="flex items-center space-x-2 bg-white/10 backdrop-blur-sm rounded-lg px-3 py-2">
                        <i class="fas fa-clock text-blue-300"></i>
                        <span>Created: <?php echo date('M j, g:i A'); ?></span>
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

    <!-- Add Menu Item Form -->
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
                        <p class="text-sm text-gray-600">Essential details for your menu item</p>
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
                           value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>"
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
                                <?php echo (isset($_POST['category_id']) && $_POST['category_id'] == $category['id']) ? 'selected' : ''; ?>>
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
                               value="<?php echo htmlspecialchars($_POST['price'] ?? ''); ?>"
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
                              placeholder="Describe your menu item - ingredients, preparation method, taste profile, dietary information, etc."><?php echo htmlspecialchars($_POST['description'] ?? ''); ?></textarea>
                    <div class="flex justify-between mt-1">
                        <p class="text-xs text-gray-500">Provide a detailed description to help customers make informed choices</p>
                        <span class="text-xs text-gray-400" id="char-count">0/1000</span>
                    </div>
                </div>
            </div>
        </div>

                    <div class="sm:col-span-6">
                        <label class="block text-sm font-medium text-gray-700">
                            Item Image
                        </label>
                        <div class="mt-1 flex items-center">
                            <span class="inline-block h-12 w-12 rounded-full overflow-hidden bg-gray-100">
                                <img id="image-preview" src="#" alt="Preview" class="h-full w-full object-cover hidden">
                                <svg class="h-full w-full text-gray-300" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M24 20.993V24H0v-2.996A14.977 14.977 0 0112.004 15c4.904 0 9.26 2.354 11.996 5.993zM16.002 8.999a4 4 0 11-8 0 4 4 0 018 0z" />
                                </svg>
                            </span>
                            <label for="image" class="ml-5">
                                <div class="py-2 px-3 border border-gray-300 rounded-md shadow-sm text-sm leading-4 font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary cursor-pointer">
                                    Change
                                </div>
                                <input id="image" name="image" type="file" class="sr-only" accept="image/*" onchange="previewImage(this)">
                            </label>
                        </div>
                        <p class="mt-2 text-sm text-gray-500">JPG, JPEG, PNG or GIF (Max. 2MB)</p>
                    </div>

                    <div class="sm:col-span-6">
                        <div class="flex items-start">
                            <div class="flex items-center h-5">
                                <input id="is_available" name="is_available" type="checkbox" 
                                       class="focus:ring-primary h-4 w-4 text-primary border-gray-300 rounded"
                                       <?php echo (isset($_POST['is_available']) || !isset($_POST['is_available'])) ? 'checked' : ''; ?>>
                            </div>
                            <div class="ml-3 text-sm">
                                <label for="is_available" class="font-medium text-gray-700">This item is available for ordering</label>
                                <p class="text-gray-500">Uncheck to hide this item from the menu</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="pt-5">
                    <div class="flex justify-end">
                        <a href="menu.php" class="bg-white py-2 px-4 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary">
                            Cancel
                        </a>
                        <button type="submit" class="ml-3 inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-primary hover:bg-primary-dark focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary">
                            Save Menu Item
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function previewImage(input) {
    const preview = document.getElementById('image-preview');
    const file = input.files[0];
    const reader = new FileReader();

    reader.onload = function(e) {
        preview.src = e.target.result;
        preview.classList.remove('hidden');
        preview.nextElementSibling.classList.add('hidden');
    }

    if (file) {
        reader.readAsDataURL(file);
    }
}

// Validate price input
document.getElementById('price').addEventListener('input', function(e) {
    if (this.value < 0) {
        this.value = 0;
    }
});
</script>

<?php
// Include footer
require_once 'includes/footer.php';
?>