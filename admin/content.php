<?php
// Set page title and include header
$page_title = 'Manage Content';
$page_description = 'Manage website content and pages';

// Include database connection and functions
require_once '../includes/config.php';
require_once 'includes/functions.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: login.php');
    exit();
}

// Define content sections
$content_sections = [
    'home_hero' => 'Homepage Hero Section',
    'about_us' => 'About Us Page',
    'contact_info' => 'Contact Information',
    'terms_conditions' => 'Terms & Conditions',
    'privacy_policy' => 'Privacy Policy',
    'shipping_policy' => 'Shipping Policy',
    'return_policy' => 'Return Policy'
];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $section = $_POST['section'];
    $content = $_POST['content'];
    
    // Validate section
    if (!array_key_exists($section, $content_sections)) {
        $_SESSION['message'] = ['type' => 'error', 'text' => 'Invalid content section'];
    } else {
        try {
            // Check if section exists
            $stmt = $pdo->prepare("SELECT id FROM content WHERE section = ?");
            $stmt->execute([$section]);
            $exists = $stmt->fetch();
            
            if ($exists) {
                // Update existing content
                $stmt = $pdo->prepare("UPDATE content SET content = ?, updated_at = NOW() WHERE section = ?");
                $stmt->execute([$content, $section]);
            } else {
                // Insert new content
                $stmt = $pdo->prepare("INSERT INTO content (section, content) VALUES (?, ?)");
                $stmt->execute([$section, $content]);
            }
            
            $_SESSION['message'] = ['type' => 'success', 'text' => 'Content updated successfully'];
            
        } catch (PDOException $e) {
            $_SESSION['message'] = ['type' => 'error', 'text' => 'Failed to update content: ' . $e->getMessage()];
        }
    }
}

// Get current section or default to home_hero
$current_section = $_GET['section'] ?? 'home_hero';
$current_content = '';

// Try to fetch current content
if (array_key_exists($current_section, $content_sections)) {
    $stmt = $pdo->prepare("SELECT content FROM content WHERE section = ?");
    $stmt->execute([$current_section]);
    $result = $stmt->fetch();
    $current_content = $result ? $result['content'] : '';
}

// Include header
include 'includes/header.php';
?>

<div class="bg-white shadow rounded-lg overflow-hidden">
    <div class="px-4 py-5 border-b border-gray-200 sm:px-6">
        <h3 class="text-lg leading-6 font-medium text-gray-900">
            Manage Content - <?= htmlspecialchars($content_sections[$current_section] ?? 'Content Editor') ?>
        </h3>
        <p class="mt-1 text-sm text-gray-500">
            Edit and manage the content of your website.
        </p>
    </div>
    
    <div class="px-4 py-5 sm:p-6">
        <div class="mb-6">
            <label for="section_selector" class="block text-sm font-medium text-gray-700 mb-2">Select Section</label>
            <select id="section_selector" onchange="window.location.href='?section=' + this.value" 
                    class="block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-primary focus:border-primary sm:text-sm rounded-md">
                <?php foreach ($content_sections as $key => $label): ?>
                    <option value="<?= $key ?>" <?= $current_section === $key ? 'selected' : '' ?>><?= htmlspecialchars($label) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <form method="POST" class="space-y-6">
            <input type="hidden" name="section" value="<?= htmlspecialchars($current_section) ?>">
            
            <div>
                <label for="content" class="block text-sm font-medium text-gray-700 mb-2">
                    Content for <?= htmlspecialchars($content_sections[$current_section] ?? 'this section') ?>
                </label>
                <textarea id="content" name="content" rows="12" 
                          class="shadow-sm focus:ring-primary focus:border-primary mt-1 block w-full sm:text-sm border border-gray-300 rounded-md"
                          placeholder="Enter your content here..."><?= htmlspecialchars($current_content) ?></textarea>
                <p class="mt-2 text-sm text-gray-500">
                    Use HTML tags for formatting. For images, use the full URL to the image.
                </p>
            </div>
            
            <div class="flex justify-end">
                <button type="submit" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-primary hover:bg-primary-dark focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary">
                    Save Changes
                </button>
            </div>
        </form>
    </div>
</div>

<script>
// Initialize rich text editor (you can use a library like TinyMCE or CKEditor here)
document.addEventListener('DOMContentLoaded', function() {
    // This is a placeholder for rich text editor initialization
    // In a production environment, you would initialize your preferred editor here
    // For example: tinymce.init({ selector: '#content' });
});
</script>

<?php include 'includes/footer.php'; ?>
