<?php
// admin/reviews.php - Admin interface for managing customer reviews

// 1. Session Start and Authentication
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}

// 2. Page Configuration
$page_title = 'Reviews Management';
$page_description = 'Manage customer reviews and testimonials';

// 3. Include Core Dependencies
require_once dirname(__DIR__) . '/includes/config.php';
require_once 'includes/functions.php';
require_once dirname(__DIR__) . '/includes/ActivityLogger.php';

// Initialize ActivityLogger
$activityLogger = new ActivityLogger($pdo);

// 4. Handle POST Requests
$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (isset($_POST['approve_review'])) {
            $review_id = (int)$_POST['review_id'];
            $stmt = $pdo->prepare("UPDATE reviews SET status = 'approved' WHERE id = ?");
            $stmt->execute([$review_id]);
            $message = 'Review approved successfully!';
            $message_type = 'success';
            $activityLogger->logActivity("Customer review approved (ID: {$review_id}).", $_SESSION['user_id'] ?? null, 'review_approve');
        }

        if (isset($_POST['reject_review'])) {
            $review_id = (int)$_POST['review_id'];
            $stmt = $pdo->prepare("UPDATE reviews SET status = 'rejected' WHERE id = ?");
            $stmt->execute([$review_id]);
            $message = 'Review rejected successfully!';
            $message_type = 'success';
            $activityLogger->logActivity("Customer review rejected (ID: {$review_id}).", $_SESSION['user_id'] ?? null, 'review_reject');
        }

        if (isset($_POST['delete_review'])) {
            // Check if it's a testimonial or product review
            if (isset($_POST['testimonial_id']) && !empty($_POST['testimonial_id'])) {
                // Handle testimonial deletion
                $testimonial_id = (int)$_POST['testimonial_id'];
                $stmt = $pdo->prepare("DELETE FROM customer_reviews WHERE id = ?");
                $stmt->execute([$testimonial_id]);
                $message = 'Testimonial deleted successfully!';
                $message_type = 'success';
                $activityLogger->logActivity("Customer testimonial deleted (ID: {$testimonial_id}).", $_SESSION['user_id'] ?? null, 'testimonial_delete');
            } elseif (isset($_POST['review_id']) && !empty($_POST['review_id'])) {
                // Handle product review deletion
                $review_id = (int)$_POST['review_id'];
                $stmt = $pdo->prepare("DELETE FROM reviews WHERE id = ?");
                $stmt->execute([$review_id]);
                $message = 'Review deleted successfully!';
                $message_type = 'success';
                $activityLogger->logActivity("Customer review deleted (ID: {$review_id}).", $_SESSION['user_id'] ?? null, 'review_delete');
            }
        }

        if (isset($_POST['testimonial_action'])) {
            $action = $_POST['testimonial_action'];
            $testimonial_id = (int)$_POST['testimonial_id'];

            try {
                if ($action === 'approve') {
                    $stmt = $pdo->prepare("UPDATE customer_reviews SET status = 'approved' WHERE id = ?");
                    $stmt->execute([$testimonial_id]);
                    $message = 'Testimonial approved successfully!';
                    $message_type = 'success';
                    $activityLogger->logActivity("Customer testimonial approved (ID: {$testimonial_id}).", $_SESSION['user_id'] ?? null, 'testimonial_approve');
                } elseif ($action === 'reject') {
                    $stmt = $pdo->prepare("UPDATE customer_reviews SET status = 'rejected' WHERE id = ?");
                    $stmt->execute([$testimonial_id]);
                    $message = 'Testimonial rejected successfully!';
                    $message_type = 'success';
                    $activityLogger->logActivity("Customer testimonial rejected (ID: {$testimonial_id}).", $_SESSION['user_id'] ?? null, 'testimonial_reject');
                } elseif ($action === 'delete') {
                    $stmt = $pdo->prepare("DELETE FROM customer_reviews WHERE id = ?");
                    $stmt->execute([$testimonial_id]);
                    $message = 'Testimonial deleted successfully!';
                    $message_type = 'success';
                    $activityLogger->logActivity("Customer testimonial deleted (ID: {$testimonial_id}).", $_SESSION['user_id'] ?? null, 'testimonial_delete');
                }
            } catch (Exception $e) {
                $message = $e->getMessage();
                $message_type = 'error';
            }
        }

        if (isset($_POST['testimonial_bulk_action'])) {
            $action = $_POST['testimonial_bulk_action'];
            $testimonial_ids = $_POST['testimonial_ids'] ?? [];

            if (!empty($testimonial_ids)) {
                $placeholders = str_repeat('?,', count($testimonial_ids) - 1) . '?';

                if ($action === 'approve') {
                    $stmt = $pdo->prepare("UPDATE customer_reviews SET status = 'approved' WHERE id IN ($placeholders)");
                    $status = 'approved';
                } elseif ($action === 'reject') {
                    $stmt = $pdo->prepare("UPDATE customer_reviews SET status = 'rejected' WHERE id IN ($placeholders)");
                    $status = 'rejected';
                } elseif ($action === 'delete') {
                    $stmt = $pdo->prepare("DELETE FROM customer_reviews WHERE id IN ($placeholders)");
                    $status = null;
                }

                if (isset($status)) {
                    $params = array_merge([$status], $testimonial_ids);
                } else {
                    $params = $testimonial_ids;
                }

                $stmt->execute($params);
                $message = ucfirst($action) . ' action completed successfully!';
                $message_type = 'success';

                // Log activity for each testimonial
                foreach ($testimonial_ids as $testimonial_id) {
                    $activityLogger->logActivity("Bulk {$action} performed on testimonial (ID: {$testimonial_id}).", $_SESSION['user_id'] ?? null, 'testimonial_bulk_' . $action);
                }

                // Refresh customer testimonials data
                $stmt = $pdo->prepare("SELECT * FROM customer_reviews ORDER BY created_at DESC");
                $stmt->execute();
                $customer_testimonials = $stmt->fetchAll(PDO::FETCH_ASSOC);
            }
        }

    } catch (Exception $e) {
        $message = $e->getMessage();
        $message_type = 'error';
    }

    // Redirect to prevent form resubmission
    if ($message) {
        $_SESSION['message'] = ['type' => $message_type, 'text' => $message];
    }
    header('Location: reviews.php');
    exit();
}

// 5. Get reviews from database
try {
    // Get all reviews with menu item and user details
    $stmt = $pdo->prepare("
        SELECT r.*, mi.name as menu_item_name, u.name as user_name, u.email as user_email
        FROM reviews r
        LEFT JOIN menu_items mi ON r.menu_item_id = mi.id
        LEFT JOIN users u ON r.user_id = u.id
        ORDER BY r.created_at DESC
    ");
    $stmt->execute();
    $all_reviews = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Separate testimonials (no menu_item_id) from product reviews (with menu_item_id)
    $testimonials = array_filter($all_reviews, function($review) {
        return is_null($review['menu_item_id']);
    });

    $product_reviews = array_filter($all_reviews, function($review) {
        return !is_null($review['menu_item_id']);
    });

    // Get testimonials from customer_reviews table
    try {
        $stmt = $pdo->prepare("
            SELECT * FROM customer_reviews
            ORDER BY created_at DESC
        ");
        $stmt->execute();
        $customer_testimonials = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $customer_testimonials = [];
    }

    // Get counts by status for all reviews
    $stmt = $pdo->prepare("SELECT status, COUNT(*) as count FROM reviews GROUP BY status");
    $stmt->execute();
    $status_counts = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

    // Get counts by type and status
    $stmt = $pdo->prepare("SELECT
        CASE WHEN menu_item_id IS NULL THEN 'testimonials' ELSE 'product_reviews' END as type,
        status,
        COUNT(*) as count
        FROM reviews
        GROUP BY type, status");
    $stmt->execute();
    $type_status_counts = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Organize counts by type
    $testimonial_counts = ['pending' => 0, 'approved' => 0, 'rejected' => 0];
    $product_review_counts = ['pending' => 0, 'approved' => 0, 'rejected' => 0];

    foreach ($type_status_counts as $count) {
        if ($count['type'] === 'testimonials') {
            $testimonial_counts[$count['status']] = $count['count'];
        } elseif ($count['type'] === 'product_reviews') {
            $product_review_counts[$count['status']] = $count['count'];
        }
    }

    // Get customer testimonials counts
    $stmt = $pdo->prepare("SELECT status, COUNT(*) as count FROM customer_reviews GROUP BY status");
    $stmt->execute();
    $customer_testimonial_counts = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

    // Update testimonial counts with customer testimonials
    $testimonial_counts['pending'] += $customer_testimonial_counts['pending'] ?? 0;
    $testimonial_counts['approved'] += $customer_testimonial_counts['approved'] ?? 0;
    $testimonial_counts['rejected'] += $customer_testimonial_counts['rejected'] ?? 0;

    // Get approved reviews for homepage display (limit to 6 for display)
    $stmt = $pdo->prepare("
        SELECT r.*, mi.name as menu_item_name, u.name as user_name
        FROM reviews r
        LEFT JOIN menu_items mi ON r.menu_item_id = mi.id
        LEFT JOIN users u ON r.user_id = u.id
        WHERE r.status = 'approved'
        ORDER BY r.created_at DESC
        LIMIT 6
    ");
    $stmt->execute();
    $approved_reviews = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get testimonials and product reviews separately for display
    $approved_testimonials = array_filter($approved_reviews, function($review) {
        return is_null($review['menu_item_id']);
    });

    $approved_product_reviews = array_filter($approved_reviews, function($review) {
        return !is_null($review['menu_item_id']);
    });

} catch (PDOException $e) {
    $all_reviews = [];
    $testimonials = [];
    $product_reviews = [];
    $status_counts = [];
    $testimonial_counts = ['pending' => 0, 'approved' => 0, 'rejected' => 0];
    $product_review_counts = ['pending' => 0, 'approved' => 0, 'rejected' => 0];
    $approved_reviews = [];
    $approved_testimonials = [];
    $approved_product_reviews = [];
    $customer_testimonials = [];
}

// 6. Include Header
include 'includes/header.php';
?>

<!-- Dashboard Header -->
<div class="bg-gradient-to-br from-primary via-primary-dark to-secondary text-white mt-0">
    <div class="container mx-auto px-6 py-8">
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between">
            <div>
                <h1 class="text-3xl md:text-4xl font-bold mb-2"><?php echo htmlspecialchars($page_title); ?></h1>
                <p class="text-lg opacity-90"><?php echo htmlspecialchars($page_description); ?></p>
            </div>
            <div class="mt-4 lg:mt-0">
                <a href="../index.php#testimonials" class="bg-white/20 hover:bg-white/30 text-white px-4 py-2 rounded-lg transition-colors">
                    <i class="fas fa-eye mr-2"></i>View on Homepage
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Main Content Area -->
<div class="container mx-auto px-6 py-8">
    <!-- Feedback Messages -->
    <?php if (isset($_SESSION['message'])): ?>
        <div class="mb-6 p-4 <?php echo $_SESSION['message']['type'] === 'success' ? 'bg-green-50 border-green-200 text-green-700' : 'bg-red-50 border-red-200 text-red-700'; ?> border rounded-lg">
            <p><?php echo htmlspecialchars($_SESSION['message']['text']); ?></p>
        </div>
        <?php unset($_SESSION['message']); ?>
    <?php endif; ?>

    <!-- Statistics Cards - Top Row (3 cards) -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
        <div class="bg-white rounded-xl shadow-lg p-6 border">
            <div class="flex items-center">
                <div class="p-3 bg-blue-100 rounded-full">
                    <i class="fas fa-star text-blue-600 text-xl"></i>
                </div>
                <div class="ml-4">
                    <h3 class="text-lg font-semibold text-gray-800">All Testimonials</h3>
                    <p class="text-2xl font-bold text-blue-600"><?php echo array_sum($testimonial_counts); ?></p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-lg p-6 border">
            <div class="flex items-center">
                <div class="p-3 bg-green-100 rounded-full">
                    <i class="fas fa-check-circle text-green-600 text-xl"></i>
                </div>
                <div class="ml-4">
                    <h3 class="text-lg font-semibold text-gray-800">Approved Testimonials</h3>
                    <p class="text-2xl font-bold text-green-600"><?php echo $testimonial_counts['approved'] ?? 0; ?></p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-lg p-6 border">
            <div class="flex items-center">
                <div class="p-3 bg-yellow-100 rounded-full">
                    <i class="fas fa-clock text-yellow-600 text-xl"></i>
                </div>
                <div class="ml-4">
                    <h3 class="text-lg font-semibold text-gray-800">Pending Testimonials</h3>
                    <p class="text-2xl font-bold text-yellow-600"><?php echo $testimonial_counts['pending'] ?? 0; ?></p>
                </div>
            </div>
        </div>
    </div>

    <!-- Statistics Cards - Bottom Row (3 cards) -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        <div class="bg-white rounded-xl shadow-lg p-6 border">
            <div class="flex items-center">
                <div class="p-3 bg-orange-100 rounded-full">
                    <i class="fas fa-utensils text-orange-600 text-xl"></i>
                </div>
                <div class="ml-4">
                    <h3 class="text-lg font-semibold text-gray-800">All Product Reviews</h3>
                    <p class="text-2xl font-bold text-orange-600"><?php echo array_sum($product_review_counts); ?></p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-lg p-6 border">
            <div class="flex items-center">
                <div class="p-3 bg-green-100 rounded-full">
                    <i class="fas fa-check-circle text-green-600 text-xl"></i>
                </div>
                <div class="ml-4">
                    <h3 class="text-lg font-semibold text-gray-800">Approved Product Reviews</h3>
                    <p class="text-2xl font-bold text-green-600"><?php echo $product_review_counts['approved'] ?? 0; ?></p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-lg p-6 border">
            <div class="flex items-center">
                <div class="p-3 bg-red-100 rounded-full">
                    <i class="fas fa-times-circle text-red-600 text-xl"></i>
                </div>
                <div class="ml-4">
                    <h3 class="text-lg font-semibold text-gray-800">Total Reviews</h3>
                    <p class="text-2xl font-bold text-red-600"><?php echo array_sum($status_counts); ?></p>
                </div>
            </div>
        </div>
    </div>

    <!-- Reviews Management Tabs -->
    <div class="bg-white rounded-xl shadow-lg p-6 mb-8">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between mb-6">
            <h3 class="text-lg font-semibold text-gray-800 mb-4 md:mb-0">Reviews Management</h3>

            <!-- Tab Navigation -->
            <div class="flex space-x-1 bg-gray-100 rounded-lg p-1">
                <button id="testimonials-tab" class="tab-button active px-4 py-2 rounded-md text-sm font-medium transition-colors"
                        data-tab="testimonials">
                    <i class="fas fa-star mr-2"></i>Testimonials (<?php echo array_sum($testimonial_counts); ?>)
                </button>
                <button id="products-tab" class="tab-button px-4 py-2 rounded-md text-sm font-medium transition-colors"
                        data-tab="products">
                    <i class="fas fa-utensils mr-2"></i>Product Reviews (<?php echo array_sum($product_review_counts); ?>)
                </button>
            </div>
        </div>

        <!-- Testimonials Section -->
        <div id="testimonials-section" class="tab-content">
            <?php if (empty($customer_testimonials)): ?>
                <p class="text-gray-500 text-center py-8">No testimonials found. General testimonials will appear here once submitted.</p>
            <?php else: ?>
                <div class="space-y-4">
                    <?php foreach ($customer_testimonials as $testimonial): ?>
                        <div class="border rounded-lg p-6 <?php echo $testimonial['status'] === 'approved' ? 'border-green-200 bg-green-50' : ($testimonial['status'] === 'rejected' ? 'border-red-200 bg-red-50' : 'border-gray-200'); ?>">
                            <div class="flex flex-col lg:flex-row lg:items-start lg:justify-between">
                                <div class="flex-1">
                                    <!-- Review Header -->
                                    <div class="flex items-center justify-between mb-3">
                                        <div class="flex items-center">
                                            <div class="w-10 h-10 bg-primary/10 rounded-full mr-3 flex items-center justify-center">
                                                <span class="text-primary font-bold"><?php echo strtoupper(substr($testimonial['customer_name'], 0, 1)); ?></span>
                                            </div>
                                            <div>
                                                <h4 class="font-semibold text-gray-800"><?php echo htmlspecialchars($testimonial['customer_name']); ?></h4>
                                                <?php if (!empty($testimonial['customer_title'])): ?>
                                                    <p class="text-primary text-sm font-medium"><?php echo htmlspecialchars($testimonial['customer_title']); ?></p>
                                                <?php endif; ?>
                                                <p class="text-sm text-gray-500">
                                                    General Testimonial •
                                                    <?php echo htmlspecialchars($testimonial['customer_email']); ?>
                                                    <?php if (!empty($testimonial['service_type'])): ?>
                                                        • <?php echo htmlspecialchars($testimonial['service_type']); ?>
                                                    <?php endif; ?>
                                                    <?php if (!empty($testimonial['catering_event_type'])): ?>
                                                        • <?php echo htmlspecialchars($testimonial['catering_event_type']); ?>
                                                    <?php endif; ?>
                                                    <?php if (!empty($testimonial['occasion_details'])): ?>
                                                        • <?php echo htmlspecialchars($testimonial['occasion_details']); ?>
                                                    <?php endif; ?>
                                                </p>
                                            </div>
                                        </div>
                                        <div class="flex items-center">
                                            <!-- Rating Stars -->
                                            <div class="flex text-yellow-400 mr-3">
                                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                                    <i class="fas fa-star <?php echo $i <= $testimonial['rating'] ? '' : 'text-gray-300'; ?>"></i>
                                                <?php endfor; ?>
                                            </div>
                                            <!-- Status Badge -->
                                            <span class="px-2 py-1 text-xs font-medium rounded-full <?php
                                                echo $testimonial['status'] === 'approved' ? 'bg-green-100 text-green-800' :
                                                     ($testimonial['status'] === 'rejected' ? 'bg-red-100 text-red-800' : 'bg-yellow-100 text-yellow-800'); ?>">
                                                <?php echo ucfirst($testimonial['status']); ?>
                                            </span>
                                        </div>
                                    </div>

                                    <!-- Review Content -->
                                    <div class="mb-4">
                                        <p class="text-gray-700 mb-2"><?php echo htmlspecialchars($testimonial['review_text']); ?></p>
                                        <p class="text-sm text-gray-500">Type: General Testimonial</p>
                                    </div>

                                    <!-- Review Meta -->
                                    <div class="text-sm text-gray-500">
                                        <span>Submitted: <?php echo date('M j, Y', strtotime($testimonial['created_at'])); ?></span>
                                        <?php if ($testimonial['created_at'] !== $testimonial['updated_at']): ?>
                                            <span class="ml-4">Updated: <?php echo date('M j, Y', strtotime($testimonial['updated_at'])); ?></span>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <!-- Actions -->
                                <div class="flex items-center space-x-2 mt-4 lg:mt-0">
                                    <input type="checkbox" name="testimonial_ids[]" value="<?php echo $testimonial['id']; ?>" form="testimonials-bulk-form" class="rounded">

                                    <?php if ($testimonial['status'] === 'pending'): ?>
                                        <form method="POST" class="inline">
                                            <input type="hidden" name="testimonial_id" value="<?php echo $testimonial['id']; ?>">
                                            <input type="hidden" name="testimonial_action" value="approve">
                                            <button type="submit" class="bg-green-500 hover:bg-green-600 text-white px-3 py-1 rounded text-sm">
                                                Approve
                                            </button>
                                        </form>

                                        <form method="POST" class="inline">
                                            <input type="hidden" name="testimonial_id" value="<?php echo $testimonial['id']; ?>">
                                            <input type="hidden" name="testimonial_action" value="reject">
                                            <button type="submit" class="bg-red-500 hover:bg-red-600 text-white px-3 py-1 rounded text-sm">
                                                Reject
                                            </button>
                                        </form>
                                    <?php endif; ?>

                                    <form method="POST" class="inline" onsubmit="return false;">
                                        <input type="hidden" name="testimonial_id" value="<?php echo $testimonial['id']; ?>">
                                        <input type="hidden" name="testimonial_action" value="delete">
                                        <button type="button" onclick="event.preventDefault(); confirmDeleteReview(<?php echo $testimonial['id']; ?>, '<?php echo htmlspecialchars(addslashes($testimonial['customer_name'] ?? 'Unknown Customer')); ?>', 'testimonial');"
                                                class="bg-gray-500 hover:bg-gray-600 text-white px-3 py-1 rounded text-sm">
                                            Delete
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Product Reviews Section -->
        <div id="products-section" class="tab-content hidden">
            <?php if (empty($product_reviews)): ?>
                <p class="text-gray-500 text-center py-8">No product reviews found. Menu item reviews will appear here once submitted.</p>
            <?php else: ?>
                <div class="space-y-4">
                    <?php foreach ($product_reviews as $review): ?>
                        <div class="border rounded-lg p-6 <?php echo $review['status'] === 'approved' ? 'border-green-200 bg-green-50' : ($review['status'] === 'rejected' ? 'border-red-200 bg-red-50' : 'border-gray-200'); ?>">
                            <div class="flex flex-col lg:flex-row lg:items-start lg:justify-between">
                                <div class="flex-1">
                                    <!-- Review Header -->
                                    <div class="flex items-center justify-between mb-3">
                                        <div class="flex items-center">
                                            <div class="w-10 h-10 bg-primary/10 rounded-full mr-3 flex items-center justify-center">
                                                <span class="text-primary font-bold"><?php echo strtoupper(substr($review['customer_name'], 0, 1)); ?></span>
                                            </div>
                                            <div>
                                                <h4 class="font-semibold text-gray-800"><?php echo htmlspecialchars($review['customer_name']); ?></h4>
                                                <p class="text-sm text-gray-500">
                                                    <?php echo htmlspecialchars($review['menu_item_name'] ?? 'Unknown Item'); ?> •
                                                    <?php echo htmlspecialchars($review['customer_email'] ?? 'No email provided'); ?>
                                                </p>
                                            </div>
                                        </div>
                                        <div class="flex items-center">
                                            <!-- Rating Stars -->
                                            <div class="flex text-yellow-400 mr-3">
                                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                                    <i class="fas fa-star <?php echo $i <= $review['rating'] ? '' : 'text-gray-300'; ?>"></i>
                                                <?php endfor; ?>
                                            </div>
                                            <!-- Status Badge -->
                                            <span class="px-2 py-1 text-xs font-medium rounded-full <?php
                                                echo $review['status'] === 'approved' ? 'bg-green-100 text-green-800' :
                                                     ($review['status'] === 'rejected' ? 'bg-red-100 text-red-800' : 'bg-yellow-100 text-yellow-800'); ?>">
                                                <?php echo ucfirst($review['status']); ?>
                                            </span>
                                        </div>
                                    </div>

                                    <!-- Review Content -->
                                    <div class="mb-4">
                                        <p class="text-gray-700 mb-2"><?php echo htmlspecialchars($review['review_text']); ?></p>
                                        <p class="text-sm text-gray-500">Menu Item: <?php echo htmlspecialchars($review['menu_item_name'] ?? 'Unknown Item'); ?></p>
                                    </div>

                                    <!-- Review Meta -->
                                    <div class="text-sm text-gray-500">
                                        <span>Submitted: <?php echo date('M j, Y', strtotime($review['created_at'])); ?></span>
                                        <?php if ($review['created_at'] !== $review['updated_at']): ?>
                                            <span class="ml-4">Updated: <?php echo date('M j, Y', strtotime($review['updated_at'])); ?></span>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <!-- Actions -->
                                <div class="flex items-center space-x-2 mt-4 lg:mt-0">
                                    <input type="checkbox" name="review_ids[]" value="<?php echo $review['id']; ?>" form="products-bulk-form" class="rounded">

                                    <?php if ($review['status'] === 'pending'): ?>
                                        <form method="POST" class="inline">
                                            <input type="hidden" name="review_id" value="<?php echo $review['id']; ?>">
                                            <button type="submit" name="approve_review" class="bg-green-500 hover:bg-green-600 text-white px-3 py-1 rounded text-sm">
                                                Approve
                                            </button>
                                        </form>

                                        <form method="POST" class="inline">
                                            <input type="hidden" name="review_id" value="<?php echo $review['id']; ?>">
                                            <button type="submit" name="reject_review" class="bg-red-500 hover:bg-red-600 text-white px-3 py-1 rounded text-sm">
                                                Reject
                                            </button>
                                        </form>
                                    <?php endif; ?>

                                    <form method="POST" class="inline" onsubmit="return false;">
                                        <input type="hidden" name="review_id" value="<?php echo $review['id']; ?>">
                                        <button type="button" onclick="event.preventDefault(); confirmDeleteReview(<?php echo $review['id']; ?>, '<?php echo htmlspecialchars(addslashes($review['customer_name'] ?? 'Unknown Customer')); ?>');"
                                                class="bg-gray-500 hover:bg-gray-600 text-white px-3 py-1 rounded text-sm">
                                            Delete
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Bulk Actions Forms (hidden) -->
    <form id="testimonials-bulk-form" method="POST" class="hidden">
        <input type="hidden" name="testimonial_bulk_action" value="">
        <input type="hidden" name="testimonial_type" value="testimonials">
    </form>
    <form id="products-bulk-form" method="POST" class="hidden">
        <input type="hidden" name="review_type" value="products">
    </form>

    <!-- JavaScript for Tab Functionality -->
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const tabButtons = document.querySelectorAll('.tab-button');
        const tabContents = document.querySelectorAll('.tab-content');

        tabButtons.forEach(button => {
            button.addEventListener('click', function() {
                const tabType = this.getAttribute('data-tab');

                // Update active button
                tabButtons.forEach(btn => btn.classList.remove('active', 'bg-primary', 'text-white'));
                this.classList.add('active', 'bg-primary', 'text-white');

                // Show/hide content
                tabContents.forEach(content => {
                    if (content.id === tabType + '-section') {
                        content.classList.remove('hidden');
                    } else {
                        content.classList.add('hidden');
                    }
                });
            });
        });

        // Handle bulk actions for testimonials
        const testimonialsForm = document.getElementById('testimonials-bulk-form');
        const testimonialsSection = document.getElementById('testimonials-section');

        if (testimonialsSection) {
            // Add bulk action buttons for testimonials
            const bulkActionsDiv = document.createElement('div');
            bulkActionsDiv.className = 'flex flex-col sm:flex-row gap-2 mb-4';
            bulkActionsDiv.innerHTML = `
                <select id="testimonial-bulk-select" class="px-3 py-2 border border-gray-300 rounded-lg">
                    <option value="">Bulk Actions</option>
                    <option value="approve">Approve Selected</option>
                    <option value="reject">Reject Selected</option>
                    <option value="delete">Delete Selected</option>
                </select>
                <button type="button" id="testimonial-bulk-apply" class="bg-primary hover:bg-primary-dark text-white px-4 py-2 rounded-lg">
                    Apply
                </button>
            `;

            const existingHeader = testimonialsSection.querySelector('.flex.flex-col.md\\:flex-row');
            if (existingHeader) {
                existingHeader.appendChild(bulkActionsDiv);
            }

            // Handle bulk apply button
            document.getElementById('testimonial-bulk-apply').addEventListener('click', function() {
                const action = document.getElementById('testimonial-bulk-select').value;
                const checkboxes = testimonialsSection.querySelectorAll('input[name="testimonial_ids[]"]:checked');

                if (action && checkboxes.length > 0) {
                    if (confirm(`Are you sure you want to ${action} ${checkboxes.length} testimonial(s)?`)) {
                        const testimonialIds = Array.from(checkboxes).map(cb => cb.value);

                        // Set form values
                        testimonialsForm.querySelector('input[name="testimonial_bulk_action"]').value = action;
                        testimonialsForm.querySelector('input[name="testimonial_ids[]"]').remove();

                        testimonialIds.forEach(id => {
                            const input = document.createElement('input');
                            input.type = 'hidden';
                            input.name = 'testimonial_ids[]';
                            input.value = id;
                            testimonialsForm.appendChild(input);
                        });

                        testimonialsForm.submit();
                    }
                } else {
                    alert('Please select an action and at least one testimonial.');
                }
            });
        }
    });
    </script>

    <!-- Delete Confirmation Modal -->
    <div id="deleteModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
        <div class="bg-white rounded-2xl shadow-2xl max-w-md w-full max-h-[90vh] overflow-y-auto">
            <!-- Modal Header -->
            <div class="bg-gradient-to-r from-red-600 to-red-700 p-6 text-white rounded-t-2xl">
                <div class="flex items-center justify-between">
                    <h3 class="text-xl font-bold">
                        <i class="fas fa-exclamation-triangle mr-2"></i>Confirm Deletion
                    </h3>
                    <button type="button" onclick="document.getElementById('deleteModal').classList.add('hidden'); document.getElementById('deleteModal').classList.remove('animate__fadeIn', 'animate__zoomIn');" class="text-white hover:text-gray-200 text-2xl">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </div>

            <!-- Modal Body -->
            <div class="p-6 text-gray-700">
                <p class="text-lg font-medium mb-3">Are you sure you want to delete this review?</p>
                <p class="text-sm text-gray-600">This action cannot be undone. The review will be permanently removed.</p>
            </div>

            <!-- Modal Footer -->
            <div class="p-6 border-t border-gray-200 flex gap-3 justify-end">
                <button type="button" onclick="document.getElementById('deleteModal').classList.add('hidden'); document.getElementById('deleteModal').classList.remove('animate__fadeIn', 'animate__zoomIn');"
                        class="group relative bg-gray-500 hover:bg-gray-600 text-white px-6 py-3 rounded-xl font-semibold transition-all duration-300 flex items-center justify-center shadow-lg hover:shadow-xl transform hover:-translate-y-0.5">
                    <div class="absolute inset-0 bg-gradient-to-r from-white/0 via-white/10 to-white/0 opacity-0 group-hover:opacity-100 transition-opacity duration-300 rounded-xl"></div>
                    <span class="relative z-10 font-medium">Cancel</span>
                </button>
                <form method="POST" class="inline-block" id="deleteReviewForm">
                    <input type="hidden" name="testimonial_id" id="deleteTestimonialId">
                    <input type="hidden" name="review_id" id="deleteReviewId">
                    <input type="hidden" name="delete_type" id="deleteType" value="review">
                    <button type="submit" name="delete_review" id="deleteSubmitButton"
                            class="group relative bg-red-600 hover:bg-red-700 text-white px-6 py-3 rounded-xl font-semibold transition-all duration-300 flex items-center justify-center shadow-lg hover:shadow-xl transform hover:-translate-y-0.5">
                        <div class="absolute inset-0 bg-gradient-to-r from-white/0 via-white/10 to-white/0 opacity-0 group-hover:opacity-100 transition-opacity duration-300 rounded-xl"></div>
                        <i class="fas fa-trash mr-2 relative z-10"></i>
                        <span class="relative z-10 font-medium" id="deleteButtonText">Delete Review</span>
                    </button>
                </form>
            </div>
        </div>
    </div>

    <?php include 'includes/footer.php'; ?>

    <script>
    // Delete confirmation modal
    function confirmDeleteReview(id, title, type = 'review') {
        // Set the review ID in the modal form
        if (type === 'testimonial') {
            document.getElementById('deleteTestimonialId').value = id;
            document.getElementById('deleteReviewId').value = '';
            document.getElementById('deleteType').value = 'testimonial';
            document.getElementById('deleteButtonText').textContent = 'Delete Testimonial';
            document.getElementById('deleteSubmitButton').name = 'testimonial_action';
            document.getElementById('deleteSubmitButton').value = 'delete';
        } else {
            document.getElementById('deleteReviewId').value = id;
            document.getElementById('deleteTestimonialId').value = '';
            document.getElementById('deleteType').value = 'review';
            document.getElementById('deleteButtonText').textContent = 'Delete Review';
            document.getElementById('deleteSubmitButton').name = 'delete_review';
            document.getElementById('deleteSubmitButton').value = '1';
        }

        // Update the modal content based on type
        const modalBody = document.querySelector('#deleteModal .p-6.text-gray-700');
        if (modalBody) {
            if (type === 'testimonial') {
                modalBody.innerHTML = `
                    <p class="text-lg font-medium mb-3">Are you sure you want to delete this testimonial?</p>
                    <p class="text-sm text-gray-600">This action cannot be undone. The testimonial will be permanently removed.</p>
                `;
            } else {
                modalBody.innerHTML = `
                    <p class="text-lg font-medium mb-3">Are you sure you want to delete this review?</p>
                    <p class="text-sm text-gray-600">This action cannot be undone. The review will be permanently removed.</p>
                `;
            }
        }

        // Show the modal
        document.getElementById('deleteModal').classList.remove('hidden');
    }

    // Close modal when clicking outside
    window.addEventListener('click', function(event) {
        const modalIds = ['deleteModal'];
        modalIds.forEach(id => {
            const modal = document.getElementById(id);
            if (modal && !modal.classList.contains('hidden') && event.target === modal) {
                modal.classList.add('hidden');
                modal.classList.remove('animate__fadeIn', 'animate__zoomIn');
            }
        });
    });

    // Keyboard navigation for modals (Escape key)
    document.addEventListener('keydown', function(event) {
        if (event.key === 'Escape') {
            const modalIds = ['deleteModal'];
            modalIds.forEach(id => {
                const modal = document.getElementById(id);
                if (modal && !modal.classList.contains('hidden')) {
                    modal.classList.add('hidden');
                    modal.classList.remove('animate__fadeIn', 'animate__zoomIn');
                    event.preventDefault(); // Prevent default ESC behavior
                }
            });
        }
    });
    </script>
</body>
</html>
