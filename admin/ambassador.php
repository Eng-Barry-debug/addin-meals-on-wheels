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

// Include activity logger
require_once '../includes/ActivityLogger.php';
$activityLogger = new ActivityLogger($pdo);

// Initialize variables
$error = '';
$success = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['delete_application'])) {
        $application_id = (int)$_POST['application_id'];

        try {
            $stmt = $pdo->prepare("DELETE FROM ambassadors WHERE id = ?");
            $stmt->execute([$application_id]);

            // Log activity
            $activityLogger->log('ambassador', 'deleted', "Deleted ambassador application", 'ambassador', $application_id);

            $_SESSION['message'] = [
                'type' => 'success',
                'text' => 'Ambassador application deleted successfully!'
            ];

            header('Location: ' . $_SERVER['PHP_SELF'] . '?' . http_build_query($_GET));
            exit();
        } catch (PDOException $e) {
            $error = 'Error deleting ambassador application: ' . $e->getMessage();
        }
    }

    if (isset($_POST['update_application'])) {
        $application_id = (int)$_POST['application_id'];
        $name = sanitize($_POST['name']);
        $email = sanitize($_POST['email']);
        $phone = sanitize($_POST['phone']);
        $social_media = sanitize($_POST['social_media']);
        $experience = sanitize($_POST['experience']);
        $motivation = sanitize($_POST['motivation']);
        $message = sanitize($_POST['message']);
        $status = sanitize($_POST['status']);

        try {
            $stmt = $pdo->prepare("UPDATE ambassadors SET name = ?, email = ?, phone = ?, social_media = ?, experience = ?, motivation = ?, message = ?, status = ? WHERE id = ?");
            $stmt->execute([$name, $email, $phone, $social_media, $experience, $motivation, $message, $status, $application_id]);

            // Log activity
            $activityLogger->log('ambassador', 'updated', "Updated ambassador application: {$name}", 'ambassador', $application_id);

            $_SESSION['message'] = [
                'type' => 'success',
                'text' => 'Ambassador application updated successfully!'
            ];

            header('Location: ' . $_SERVER['PHP_SELF'] . '?' . http_build_query($_GET));
            exit();
        } catch (PDOException $e) {
            $error = 'Error updating ambassador application: ' . $e->getMessage();
        }
    }
}

// Handle status update via GET (for quick status changes)
if (isset($_GET['update_status']) && isset($_GET['id']) && isset($_GET['status'])) {
    $id = (int)$_GET['id'];
    $status = sanitize($_GET['status']);

    if (in_array($status, ['pending', 'approved', 'rejected'])) {
        try {
            $stmt = $pdo->prepare("UPDATE ambassadors SET status = ? WHERE id = ?");
            $stmt->execute([$status, $id]);

            // Log activity
            $activityLogger->log('ambassador', 'updated', "Updated ambassador application status to {$status}", 'ambassador', $id);

            $_SESSION['message'] = [
                'type' => 'success',
                'text' => 'Ambassador application status updated successfully!'
            ];
        } catch (PDOException $e) {
            $_SESSION['message'] = [
                'type' => 'error',
                'text' => 'Error updating ambassador application status: ' . $e->getMessage()
            ];
        }
    }

    header('Location: ambassador.php');
    exit();
}

// Get filter parameters
$search = $_GET['search'] ?? '';
$status_filter = $_GET['status'] ?? '';
$experience_filter = $_GET['experience'] ?? '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 10;
$offset = ($page - 1) * $per_page;

// Build where clause
$where_conditions = [];
$params = [];

if (!empty($status_filter)) {
    $where_conditions[] = "status = :status";
    $params[':status'] = $status_filter;
}

if (!empty($experience_filter)) {
    $where_conditions[] = "experience = :experience";
    $params[':experience'] = $experience_filter;
}

if (!empty($search)) {
    $where_conditions[] = "(name LIKE :search OR email LIKE :search OR phone LIKE :search)";
    $params[':search'] = "%$search%";
}

$where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

// Get total count for pagination
$total_sql = "SELECT COUNT(*) as count FROM ambassadors $where_clause";
$total_stmt = $pdo->prepare($total_sql);
$total_stmt->execute($params);
$total_applications = (int)$total_stmt->fetch(PDO::FETCH_ASSOC)['count'];

// Get ambassador applications with pagination
$sql = "SELECT * FROM ambassadors $where_clause ORDER BY application_date DESC LIMIT $offset, $per_page";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$applications = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get pagination info
$total_pages = ceil($total_applications / $per_page);

// Set page title
$page_title = 'Ambassador Management';
$page_description = 'Manage ambassador applications and partnerships';

// Include header
require_once 'includes/header.php';
?>

<!-- Ambassador Header -->
<div class="bg-gradient-to-br from-primary via-primary-dark to-secondary text-white">
    <div class="container mx-auto px-6 py-12">
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between">
            <div class="flex items-center space-x-6">
                <div class="h-20 w-20 rounded-full bg-white/20 backdrop-blur-sm flex items-center justify-center text-white font-bold text-3xl shadow-lg">
                    <i class="fas fa-handshake"></i>
                </div>
                <div>
                    <h1 class="text-3xl md:text-4xl font-bold mb-2">Ambassador Management</h1>
                    <p class="text-lg opacity-90 mb-2">Manage ambassador applications and partnerships</p>
                    <div class="flex items-center space-x-4">
                        <span class="px-3 py-1 bg-white/20 backdrop-blur-sm rounded-full text-sm font-medium">
                            <?php echo $total_applications; ?> Total Applications
                        </span>
                        <span class="px-3 py-1 bg-white/20 backdrop-blur-sm rounded-full text-sm font-medium">
                            <?php echo count($applications); ?> This Page
                        </span>
                    </div>
                </div>
            </div>
            <div class="mt-6 lg:mt-0">
                <div class="flex items-center space-x-4 text-sm">
                    <div class="flex items-center space-x-2 bg-white/10 backdrop-blur-sm rounded-lg px-3 py-2">
                        <i class="fas fa-chart-line text-blue-300"></i>
                        <span>Updated: <?php echo date('M j, g:i A'); ?></span>
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

    <!-- Filters and Search -->
    <div class="bg-white rounded-xl shadow-lg p-6 mb-8">
        <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-6">
            <div>
                <label for="search" class="block text-sm font-semibold text-gray-700 mb-2">Search</label>
                <input type="text" name="search" id="search"
                       value="<?php echo htmlspecialchars($search); ?>"
                       class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent"
                       placeholder="Search by name, email, or phone...">
            </div>

            <div>
                <label for="status" class="block text-sm font-semibold text-gray-700 mb-2">Status</label>
                <select name="status" id="status"
                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                    <option value="">All Status</option>
                    <option value="pending" <?php echo $status_filter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                    <option value="approved" <?php echo $status_filter === 'approved' ? 'selected' : ''; ?>>Approved</option>
                    <option value="rejected" <?php echo $status_filter === 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                </select>
            </div>

            <div>
                <label for="experience" class="block text-sm font-semibold text-gray-700 mb-2">Experience</label>
                <select name="experience" id="experience"
                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                    <option value="">All Experience</option>
                    <option value="none" <?php echo $experience_filter === 'none' ? 'selected' : ''; ?>>No Experience</option>
                    <option value="some_sales" <?php echo $experience_filter === 'some_sales' ? 'selected' : ''; ?>>Some Sales</option>
                    <option value="experienced" <?php echo $experience_filter === 'experienced' ? 'selected' : ''; ?>>Experienced</option>
                    <option value="influencer" <?php echo $experience_filter === 'influencer' ? 'selected' : ''; ?>>Influencer</option>
                </select>
            </div>

            <div class="flex items-end space-x-4">
                <button type="submit" class="bg-primary hover:bg-primary-dark text-white px-6 py-3 rounded-lg font-semibold transition-colors duration-200 flex items-center">
                    <i class="fas fa-search mr-2"></i>
                    Search
                </button>
                <a href="ambassador.php" class="bg-gray-500 hover:bg-gray-600 text-white px-6 py-3 rounded-lg font-semibold transition-colors duration-200">
                    <i class="fas fa-times mr-2"></i>
                    Clear
                </a>
            </div>
        </form>
    </div>

    <!-- Applications List -->
    <div class="bg-white rounded-xl shadow-lg overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200">
            <h2 class="text-lg font-semibold text-gray-900">Ambassador Applications (<?php echo $total_applications; ?>)</h2>
        </div>

        <?php if (empty($applications)): ?>
            <div class="p-12 text-center">
                <i class="fas fa-handshake text-4xl text-gray-400 mb-4"></i>
                <h3 class="text-lg font-medium text-gray-900 mb-2">No applications found</h3>
                <p class="text-gray-500 mb-4">No ambassador applications match your current filters.</p>
                <a href="../ambassador-apply.php" target="_blank" class="bg-primary hover:bg-primary-dark text-white px-6 py-3 rounded-lg font-semibold transition-colors duration-200 flex items-center mx-auto w-fit">
                    <i class="fas fa-external-link-alt mr-2"></i>
                    View Application Form
                </a>
            </div>
        <?php else: ?>
            <div class="space-y-6">
                <?php foreach ($applications as $app): ?>
                <div class="bg-white rounded-xl shadow-lg border border-gray-200 hover:shadow-xl transition-all duration-300 overflow-hidden application-card"
                     data-applicant-name="<?= htmlspecialchars(strtolower($app['name'])) ?>"
                     data-applicant-email="<?= htmlspecialchars(strtolower($app['email'])) ?>"
                     data-application-status="<?= $app['status'] ?>"
                     data-experience-level="<?= $app['experience'] ?>">

                    <!-- Card Header with Quick Actions -->
                    <div class="bg-gradient-to-r from-purple-50 to-purple-100 px-6 py-4 border-b border-purple-200">
                        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between">
                            <!-- Application Info -->
                            <div class="flex items-center space-x-4">
                                <div class="w-14 h-14 bg-purple-600 rounded-full flex items-center justify-center shadow-lg">
                                    <span class="text-white font-bold text-xl"><?php echo strtoupper(substr($app['name'], 0, 1)); ?></span>
                                </div>
                                <div>
                                    <h3 class="text-2xl font-bold text-gray-900 mb-1">
                                        <?php echo htmlspecialchars($app['name']); ?>
                                    </h3>
                                    <p class="text-sm text-gray-600 font-medium">
                                        Applied: <?php echo date('M j, Y \a\t g:i A', strtotime($app['application_date'])); ?>
                                    </p>
                                    <div class="flex items-center space-x-2 mt-2">
                                        <span class="px-3 py-1 rounded-full text-sm font-semibold <?php echo getStatusBadgeClass($app['status']); ?>">
                                            <i class="fas fa-<?php
                                                echo match($app['status'] ?? 'pending') {
                                                    'pending' => 'clock',
                                                    'approved' => 'check-circle',
                                                    'rejected' => 'times-circle'
                                                };
                                            ?> mr-1 text-xs"></i>
                                            <?php echo ucfirst($app['status'] ?? 'pending'); ?>
                                        </span>
                                        <span class="px-3 py-1 rounded-full text-sm font-semibold <?php echo getExperienceBadgeClass($app['experience']); ?>">
                                            <i class="fas fa-<?php
                                                echo match($app['experience'] ?? 'none') {
                                                    'none' => 'user',
                                                    'some_sales' => 'chart-line',
                                                    'experienced' => 'trophy',
                                                    'influencer' => 'star'
                                                };
                                            ?> mr-1 text-xs"></i>
                                            <?php echo getExperienceLabel($app['experience']); ?>
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <!-- Quick CRUD Actions -->
                            <div class="mt-4 lg:mt-0 flex flex-wrap gap-2">
                                <button onclick="viewApplication(<?php echo $app['id']; ?>)"
                                   class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg font-semibold transition-all duration-200 flex items-center space-x-2 shadow-md hover:shadow-lg transform hover:-translate-y-0.5">
                                    <i class="fas fa-eye"></i>
                                    <span>View</span>
                                </button>

                                <?php if (($app['status'] ?? 'pending') === 'pending'): ?>
                                    <a href="?update_status&id=<?php echo $app['id']; ?>&status=approved"
                                       class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg font-semibold transition-all duration-200 flex items-center space-x-2 shadow-md hover:shadow-lg transform hover:-translate-y-0.5">
                                        <i class="fas fa-check"></i>
                                        <span>Approve</span>
                                    </a>
                                    <a href="?update_status&id=<?php echo $app['id']; ?>&status=rejected"
                                       class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg font-semibold transition-all duration-200 flex items-center space-x-2 shadow-md hover:shadow-lg transform hover:-translate-y-0.5">
                                        <i class="fas fa-times"></i>
                                        <span>Reject</span>
                                    </a>
                                <?php endif; ?>

                                <button onclick="editApplication(<?php echo $app['id']; ?>)"
                                        class="bg-purple-600 hover:bg-purple-700 text-white px-4 py-2 rounded-lg font-semibold transition-all duration-200 flex items-center space-x-2 shadow-md hover:shadow-lg transform hover:-translate-y-0.5">
                                    <i class="fas fa-edit"></i>
                                    <span>Edit</span>
                                </button>

                                <form method="POST" class="inline" onsubmit="return confirm('Are you sure you want to delete this application? This action cannot be undone.');">
                                    <input type="hidden" name="application_id" value="<?php echo $app['id']; ?>">
                                    <button type="submit" name="delete_application"
                                            class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-lg font-semibold transition-all duration-200 flex items-center space-x-2 shadow-md hover:shadow-lg transform hover:-translate-y-0.5">
                                        <i class="fas fa-trash"></i>
                                        <span>Delete</span>
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>

                    <!-- Application Details Grid -->
                    <div class="p-6">
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                            <!-- Contact Information -->
                            <div class="bg-blue-50 rounded-lg p-4 border-l-4 border-blue-400">
                                <div class="flex items-center mb-3">
                                    <div class="w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center mr-3">
                                        <i class="fas fa-address-book text-blue-600 text-sm"></i>
                                    </div>
                                    <h4 class="font-semibold text-gray-900">Contact Information</h4>
                                </div>
                                <div class="space-y-2">
                                    <p class="text-gray-700"><span class="font-medium">Email:</span> <?php echo htmlspecialchars($app['email']); ?></p>
                                    <p class="text-gray-700"><span class="font-medium">Phone:</span> <?php echo htmlspecialchars($app['phone'] ?? 'Not provided'); ?></p>
                                    <?php if (!empty($app['social_media'])): ?>
                                    <p class="text-gray-700"><span class="font-medium">Social:</span> @<?php echo htmlspecialchars($app['social_media']); ?></p>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <!-- Experience & Skills -->
                            <div class="bg-green-50 rounded-lg p-4 border-l-4 border-green-400">
                                <div class="flex items-center mb-3">
                                    <div class="w-8 h-8 bg-green-100 rounded-full flex items-center justify-center mr-3">
                                        <i class="fas fa-chart-line text-green-600 text-sm"></i>
                                    </div>
                                    <h4 class="font-semibold text-gray-900">Experience & Skills</h4>
                                </div>
                                <div class="space-y-2">
                                    <p class="text-gray-700"><span class="font-medium">Experience Level:</span> <?php echo getExperienceLabel($app['experience']); ?></p>
                                    <?php if (!empty($app['experience'])): ?>
                                    <p class="text-gray-700"><span class="font-medium">Background:</span> <?php echo ucfirst(str_replace('_', ' ', $app['experience'])); ?></p>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <!-- Application Summary -->
                            <div class="bg-purple-50 rounded-lg p-4 border-l-4 border-purple-400">
                                <div class="flex items-center mb-3">
                                    <div class="w-8 h-8 bg-purple-100 rounded-full flex items-center justify-center mr-3">
                                        <i class="fas fa-file-alt text-purple-600 text-sm"></i>
                                    </div>
                                    <h4 class="font-semibold text-gray-900">Application Summary</h4>
                                </div>
                                <div class="space-y-2">
                                    <p class="text-gray-700"><span class="font-medium">Application ID:</span> #<?php echo $app['id']; ?></p>
                                    <p class="text-gray-700"><span class="font-medium">Applied:</span> <?php echo date('M j, Y', strtotime($app['application_date'])); ?></p>
                                    <?php if (!empty($app['motivation'])): ?>
                                    <p class="text-gray-700"><span class="font-medium">Motivation:</span> <?php echo htmlspecialchars(substr($app['motivation'], 0, 80) . (strlen($app['motivation']) > 80 ? '...' : '')); ?></p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <!-- Application Content Preview -->
                        <?php if (!empty($app['motivation']) || !empty($app['message'])): ?>
                        <div class="mt-6 bg-gray-50 rounded-lg p-4 border-t border-gray-200">
                            <h5 class="font-semibold text-gray-900 mb-3 flex items-center">
                                <i class="fas fa-quote-left mr-2 text-purple-600"></i>
                                Application Details
                            </h5>
                            <div class="grid md:grid-cols-2 gap-6">
                                <?php if (!empty($app['motivation'])): ?>
                                <div>
                                    <h6 class="font-medium text-gray-800 mb-2">Why they want to be an ambassador:</h6>
                                    <p class="text-gray-700 text-sm leading-relaxed"><?php echo htmlspecialchars($app['motivation']); ?></p>
                                </div>
                                <?php endif; ?>
                                <?php if (!empty($app['message'])): ?>
                                <div>
                                    <h6 class="font-medium text-gray-800 mb-2">Additional message:</h6>
                                    <p class="text-gray-700 text-sm leading-relaxed"><?php echo htmlspecialchars($app['message']); ?></p>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
                <div class="px-6 py-4 border-t border-gray-200 bg-gray-50">
                    <div class="flex items-center justify-between">
                        <div class="text-sm text-gray-700">
                            Showing <?php echo (($page - 1) * $per_page) + 1; ?> to <?php echo min($page * $per_page, $total_applications); ?> of <?php echo $total_applications; ?> results
                        </div>
                        <div class="flex space-x-1">
                            <?php if ($page > 1): ?>
                                <a href="?page=<?php echo $page - 1; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo !empty($status_filter) ? '&status=' . urlencode($status_filter) : ''; ?><?php echo !empty($experience_filter) ? '&experience=' . urlencode($experience_filter) : ''; ?>"
                                   class="px-3 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                                    Previous
                                </a>
                            <?php endif; ?>

                            <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                                <a href="?page=<?php echo $i; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo !empty($status_filter) ? '&status=' . urlencode($status_filter) : ''; ?><?php echo !empty($experience_filter) ? '&experience=' . urlencode($experience_filter) : ''; ?>"
                                   class="px-3 py-2 border <?php echo $i === $page ? 'bg-primary text-white' : 'border-gray-300 text-gray-700 bg-white hover:bg-gray-50'; ?> rounded-md text-sm font-medium">
                                    <?php echo $i; ?>
                                </a>
                            <?php endfor; ?>

                            <?php if ($page < $total_pages): ?>
                                <a href="?page=<?php echo $page + 1; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo !empty($status_filter) ? '&status=' . urlencode($status_filter) : ''; ?><?php echo !empty($experience_filter) ? '&experience=' . urlencode($experience_filter) : ''; ?>"
                                   class="px-3 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                                    Next
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<!-- Edit Application Modal -->
<div id="editModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden z-50">
    <div class="flex items-center justify-center min-h-screen p-4">
        <div class="bg-white rounded-lg shadow-xl max-w-4xl w-full max-h-screen overflow-y-auto">
            <div class="px-6 py-4 border-b border-gray-200">
                <div class="flex items-center justify-between">
                    <h3 class="text-lg font-semibold text-gray-900">Edit Application</h3>
                    <button onclick="closeEditModal()" class="text-gray-400 hover:text-gray-600">
                        <i class="fas fa-times text-xl"></i>
                    </button>
                </div>
            </div>
            <div id="editModalContent" class="p-6">
                <!-- Edit form will be loaded here -->
            </div>
        </div>
    </div>
</div>

<!-- View Application Modal -->
<div id="applicationModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden z-50">
    <div class="flex items-center justify-center min-h-screen p-4">
        <div class="bg-white rounded-lg shadow-xl max-w-5xl w-full max-h-screen overflow-y-auto">
            <div class="px-6 py-4 border-b border-gray-200">
                <div class="flex items-center justify-between">
                    <h3 class="text-lg font-semibold text-gray-900">Application Details</h3>
                    <button onclick="closeViewModal()" class="text-gray-400 hover:text-gray-600">
                        <i class="fas fa-times text-xl"></i>
                    </button>
                </div>
            </div>
            <div id="modalContent" class="p-6">
                <!-- Content will be loaded here -->
            </div>
        </div>
    </div>
</div>

<style>
/* Ambassador page enhancements */
.application-card {
    transition: all 0.2s ease;
}

.application-card:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

/* Status badge styling - Direct CSS instead of @apply */
.status-badge {
    padding: 0.625rem 0.5rem; /* px-2.5 py-0.5 */
    border-radius: 9999px; /* rounded-full */
    font-size: 0.75rem; /* text-xs */
    font-weight: 500; /* font-medium */
    display: inline-block;
}

.status-pending {
    background-color: rgb(254 240 138); /* bg-yellow-100 */
    color: rgb(133 77 14); /* text-yellow-800 */
}

.status-approved {
    background-color: rgb(220 252 231); /* bg-green-100 */
    color: rgb(22 163 74); /* text-green-800 */
}

.status-rejected {
    background-color: rgb(254 226 226); /* bg-red-100 */
    color: rgb(153 27 27); /* text-red-800 */
}

/* Experience badge styling - Direct CSS instead of @apply */
.experience-badge {
    padding: 0.625rem 0.5rem; /* px-2.5 py-0.5 */
    border-radius: 9999px; /* rounded-full */
    font-size: 0.75rem; /* text-xs */
    font-weight: 500; /* font-medium */
    display: inline-block;
}

.experience-none {
    background-color: rgb(243 244 246); /* bg-gray-100 */
    color: rgb(31 41 55); /* text-gray-800 */
}

.experience-some_sales {
    background-color: rgb(219 234 254); /* bg-blue-100 */
    color: rgb(30 64 175); /* text-blue-800 */
}

.experience-experienced {
    background-color: rgb(243 232 255); /* bg-purple-100 */
    color: rgb(124 58 237); /* text-purple-800 */
}

.experience-influencer {
    background-color: rgb(253 244 255); /* bg-pink-100 */
    color: rgb(157 23 77); /* text-pink-800 */
}

/* Form focus enhancements */
input:focus, textarea:focus, select:focus {
    box-shadow: 0 0 0 3px rgba(193, 39, 45, 0.1);
}

/* Content preview styling */
.line-clamp-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

/* Enhanced button styling - Compatible with Tailwind */
.action-btn {
    transition: transform 0.2s ease, box-shadow 0.2s ease;
    min-width: 5rem; /* 80px */
    font-size: 0.875rem; /* text-sm */
    font-weight: 500; /* font-medium */
}

/* Only apply custom hover effects if not already handled by Tailwind */
.action-btn:not([class*="hover:"]):hover {
    transform: translateY(-0.25rem);
}

.action-btn i {
    margin-right: 0.25rem;
}

/* Mobile responsive button text */
@media (max-width: 640px) {
    .action-btn span {
        display: none;
    }

    .action-btn {
        min-width: 2.5rem; /* 40px */
        padding: 0.5rem;
    }
}
</style>

<script>
// Enhanced filtering functionality for ambassador applications
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('search');
    const statusFilter = document.getElementById('status');
    const experienceFilter = document.getElementById('experience');

    // Search functionality
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            filterApplications();
        });
    }

    // Status filter
    if (statusFilter) {
        statusFilter.addEventListener('change', function() {
            filterApplications();
        });
    }

    // Experience filter
    if (experienceFilter) {
        experienceFilter.addEventListener('change', function() {
            filterApplications();
        });
    }
});

function filterApplications() {
    const searchTerm = document.getElementById('search')?.value.toLowerCase() || '';
    const statusValue = document.getElementById('status')?.value || '';
    const experienceValue = document.getElementById('experience')?.value || '';

    const applications = document.querySelectorAll('.application-card');
    let visibleCount = 0;

    applications.forEach(app => {
        const applicantName = app.dataset.applicantName || '';
        const applicantEmail = app.dataset.applicantEmail || '';
        const applicationStatus = app.dataset.applicationStatus || '';
        const experienceLevel = app.dataset.experienceLevel || '';

        let shouldShow = true;

        // Search filter
        if (searchTerm && !applicantName.includes(searchTerm) &&
            !applicantEmail.includes(searchTerm)) {
            shouldShow = false;
        }

        // Status filter
        if (statusValue && applicationStatus !== statusValue) {
            shouldShow = false;
        }

        // Experience filter
        if (experienceValue && experienceLevel !== experienceValue) {
            shouldShow = false;
        }

        if (shouldShow) {
            app.style.display = 'block';
            visibleCount++;
        } else {
            app.style.display = 'none';
        }
    });

    // Update results count if needed
    const resultsInfo = document.querySelector('.results-info');
    if (resultsInfo) {
        resultsInfo.textContent = `${visibleCount} application${visibleCount !== 1 ? 's' : ''} found`;
    }
}
</script>

<style>
/* Enhanced application card styling */
.application-card {
    transition: all 0.3s ease;
}

.application-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
}

/* Purple theme for ambassador section */
.bg-gradient-to-r.from-purple-50.to-purple-100 {
    background: linear-gradient(to right, #faf5ff, #f3e8ff);
}

.border-purple-200 {
    border-color: #e879f9;
}

.bg-purple-600 {
    background-color: #9333ea;
}

.bg-purple-700 {
    background-color: #7c3aed;
}

.text-purple-600 {
    color: #9333ea;
}

/* Enhanced section backgrounds */
.bg-blue-50 {
    background-color: #eff6ff;
}

.bg-green-50 {
    background-color: #f0fdf4;
}

.bg-purple-50 {
    background-color: #faf5ff;
}

/* Card section borders */
.border-l-4.border-blue-400 {
    border-left-width: 4px;
    border-left-color: #60a5fa;
}

.border-l-4.border-green-400 {
    border-left-width: 4px;
    border-left-color: #4ade80;
}

.border-l-4.border-purple-400 {
    border-left-width: 4px;
    border-left-color: #c084fc;
}
</style>

<?php
// Include footer
require_once 'includes/footer.php';
?>