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

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: login.php');
    exit();
}

// Initialize variables
$error = '';
$success = '';

// Get all images from assets/img/
$images_dir = '../assets/img/';
$images = []; // Initialize as empty array to avoid undefined variable warnings
if (is_dir($images_dir)) {
    $images = array_filter(scandir($images_dir), function($file) use ($images_dir) {
        return !is_dir($images_dir . $file) && in_array(pathinfo($file, PATHINFO_EXTENSION), ['jpg', 'jpeg', 'png', 'gif']);
    });
}

// Handle file upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload'])) {
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $file_name = basename($_FILES['image']['name']);
        $target_file = $images_dir . $file_name;

        // Check if file already exists
        if (file_exists($target_file)) {
            $error = 'File already exists. Please rename your file or delete the existing one.';
        } else {
            // Move uploaded file
            if (move_uploaded_file($_FILES['image']['tmp_name'], $target_file)) {
                $success = 'Image uploaded successfully!';
                $activityLogger->logActivity($_SESSION['user_id'], 'Uploaded image: ' . $file_name);
                // Refresh images list
                $images = array_filter(scandir($images_dir), function($file) use ($images_dir) {
                    return !is_dir($images_dir . $file) && in_array(pathinfo($file, PATHINFO_EXTENSION), ['jpg', 'jpeg', 'png', 'gif']);
                });
            } else {
                $error = 'Error uploading file. Please try again.';
            }
        }
    } else {
        $error = 'Please select a valid image file.';
    }
}

// Handle file deletion
if (isset($_GET['delete'])) {
    $file_to_delete = $images_dir . basename($_GET['delete']);
    if (file_exists($file_to_delete)) {
        if (unlink($file_to_delete)) {
            $success = 'Image deleted successfully!';
            $activityLogger->logActivity($_SESSION['user_id'], 'Deleted image: ' . basename($_GET['delete']));
            // Refresh images list
            $images = array_filter(scandir($images_dir), function($file) use ($images_dir) {
                return !is_dir($images_dir . $file) && in_array(pathinfo($file, PATHINFO_EXTENSION), ['jpg', 'jpeg', 'png', 'gif']);
            });
        } else {
            $error = 'Error deleting file.';
        }
    }
}

include 'includes/header.php';
?>

<div class="container mx-auto px-4 py-8">
    <h1 class="text-3xl font-bold mb-6">Manage Homepage Images</h1>

    <?php if ($error): ?>
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
            <?php echo htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
            <?php echo htmlspecialchars($success); ?>
        </div>
    <?php endif; ?>

    <!-- Upload Form -->
    <div class="bg-white p-6 rounded-lg shadow-md mb-8">
        <h2 class="text-xl font-semibold mb-4">Upload New Image</h2>
        <form method="post" enctype="multipart/form-data">
            <div class="mb-4">
                <label for="image" class="block text-gray-700">Select Image (JPG, PNG, GIF):</label>
                <input type="file" id="image" name="image" accept="image/*" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm" required>
            </div>
            <button type="submit" name="upload" class="bg-primary text-white px-4 py-2 rounded hover:bg-primary-dark">Upload Image</button>
        </form>
    </div>

    <!-- Images List -->
    <div class="bg-white p-6 rounded-lg shadow-md">
        <h2 class="text-xl font-semibold mb-4">Existing Images</h2>
        <?php if (empty($images)): ?>
            <p class="text-gray-600">No images found in assets/img/.</p>
        <?php else: ?>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <?php foreach ($images as $image): ?>
                    <div class="relative group">
                        <img src="../assets/img/<?php echo htmlspecialchars($image); ?>" alt="<?php echo htmlspecialchars($image); ?>" class="w-full h-32 object-cover rounded">
                        <div class="absolute inset-0 bg-black bg-opacity-50 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center rounded">
                            <a href="?delete=<?php echo urlencode($image); ?>" onclick="return confirm('Are you sure you want to delete this image?')" class="text-white bg-red-600 px-2 py-1 rounded text-sm">Delete</a>
                        </div>
                        <p class="mt-2 text-sm text-gray-600"><?php echo htmlspecialchars($image); ?></p>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Note about Hero Section -->
    <div class="mt-8 bg-blue-100 border border-blue-400 text-blue-700 px-4 py-3 rounded">
        <h3 class="font-semibold">Note:</h3>
        <p>For managing hero slideshow content (titles, descriptions, buttons), use the <strong>Hero Slides</strong> management page in the admin panel. The images above are used as backgrounds for the slides.</p>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
