<?php
// Include database connection and functions
require_once '../includes/config.php';
require_once 'includes/functions.php';

// Check authentication
checkAuth();

// Get application ID
$id = (int)($_GET['id'] ?? 0);
if (!$id) {
    http_response_code(400);
    echo 'Invalid application ID';
    exit();
}

// Get application data
$app = getRecordById('ambassadors', $id);
if (!$app) {
    http_response_code(404);
    echo 'Application not found';
    exit();
}

// Experience labels
$experience_labels = [
    'none' => 'No Experience',
    'some_sales' => 'Some Sales Experience',
    'experienced' => 'Experienced',
    'influencer' => 'Social Media Influencer'
];

// Status labels and colors
$status_info = [
    'pending' => ['label' => 'Pending Review', 'color' => 'yellow'],
    'approved' => ['label' => 'Approved', 'color' => 'green'],
    'rejected' => ['label' => 'Rejected', 'color' => 'red']
];

$status = $app['status'] ?? 'pending';
$status_label = $status_info[$status]['label'];
$status_color = $status_info[$status]['color'];
?>

<div class="space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
        <div class="flex items-center space-x-4">
            <div class="h-16 w-16 rounded-full bg-primary flex items-center justify-center text-white font-bold text-xl">
                <?php echo strtoupper(substr($app['name'], 0, 1)); ?>
            </div>
            <div>
                <h2 class="text-xl font-bold text-gray-900"><?php echo htmlspecialchars($app['name']); ?></h2>
                <p class="text-gray-600">Applied on <?php echo date('F j, Y \a\t g:i A', strtotime($app['application_date'])); ?></p>
            </div>
        </div>
        <div class="text-right">
            <span class="status-badge bg-<?php echo $status_color; ?>-100 text-<?php echo $status_color; ?>-800">
                <?php echo $status_label; ?>
            </span>
        </div>
    </div>

    <!-- Contact Information -->
    <div class="bg-gray-50 rounded-lg p-4">
        <h3 class="text-lg font-semibold text-gray-900 mb-3">Contact Information</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700">Email</label>
                <p class="text-gray-900"><?php echo htmlspecialchars($app['email']); ?></p>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">Phone</label>
                <p class="text-gray-900"><?php echo htmlspecialchars($app['phone'] ?? 'Not provided'); ?></p>
            </div>
            <?php if (!empty($app['social_media'])): ?>
            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700">Social Media</label>
                <p class="text-gray-900">@<?php echo htmlspecialchars($app['social_media']); ?></p>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Experience & Background -->
    <div class="bg-gray-50 rounded-lg p-4">
        <h3 class="text-lg font-semibold text-gray-900 mb-3">Experience & Background</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700">Experience Level</label>
                <p class="text-gray-900"><?php echo $experience_labels[$app['experience'] ?? 'none']; ?></p>
            </div>
        </div>

        <?php if (!empty($app['motivation'])): ?>
        <div class="mt-4">
            <label class="block text-sm font-medium text-gray-700">Motivation</label>
            <div class="mt-1 p-3 bg-white rounded border">
                <p class="text-gray-900 whitespace-pre-wrap"><?php echo htmlspecialchars($app['motivation']); ?></p>
            </div>
        </div>
        <?php endif; ?>

        <?php if (!empty($app['message'])): ?>
        <div class="mt-4">
            <label class="block text-sm font-medium text-gray-700">Additional Message</label>
            <div class="mt-1 p-3 bg-white rounded border">
                <p class="text-gray-900 whitespace-pre-wrap"><?php echo htmlspecialchars($app['message']); ?></p>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <!-- Quick Actions (only show for pending applications) -->
    <?php if ($status === 'pending'): ?>
    <div class="bg-blue-50 rounded-lg p-4">
        <h3 class="text-lg font-semibold text-blue-900 mb-3">Quick Actions</h3>
        <div class="flex flex-wrap gap-3">
            <a href="ambassador.php?update_status&id=<?php echo $app['id']; ?>&status=approved"
               class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg font-medium transition-colors"
               onclick="return confirm('Are you sure you want to approve this application?')">
                <i class="fas fa-check mr-2"></i>Approve Application
            </a>
            <a href="ambassador.php?update_status&id=<?php echo $app['id']; ?>&status=rejected"
               class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg font-medium transition-colors"
               onclick="return confirm('Are you sure you want to reject this application?')">
                <i class="fas fa-times mr-2"></i>Reject Application
            </a>
        </div>
    </div>
    <?php endif; ?>

    <!-- Application Timeline -->
    <div class="bg-gray-50 rounded-lg p-4">
        <h3 class="text-lg font-semibold text-gray-900 mb-3">Application Timeline</h3>
        <div class="space-y-3">
            <div class="flex items-center space-x-3">
                <div class="flex-shrink-0 w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center">
                    <i class="fas fa-file-alt text-blue-600"></i>
                </div>
                <div>
                    <p class="text-sm font-medium text-gray-900">Application Submitted</p>
                    <p class="text-sm text-gray-600"><?php echo date('F j, Y \a\t g:i A', strtotime($app['application_date'])); ?></p>
                </div>
            </div>
            <?php if ($status !== 'pending'): ?>
            <div class="flex items-center space-x-3">
                <div class="flex-shrink-0 w-8 h-8 bg-<?php echo $status_color; ?>-100 rounded-full flex items-center justify-center">
                    <i class="fas fa-<?php echo $status === 'approved' ? 'check' : 'times'; ?> text-<?php echo $status_color; ?>-600"></i>
                </div>
                <div>
                    <p class="text-sm font-medium text-gray-900">Status Updated to <?php echo ucfirst($status); ?></p>
                    <p class="text-sm text-gray-600"><?php echo date('F j, Y \a\t g:i A', strtotime($app['created_at'])); ?></p>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
.status-badge {
    padding: 0.25rem 0.75rem;
    border-radius: 9999px;
    font-size: 0.75rem;
    font-weight: 600;
    display: inline-block;
}
</style>
