<?php
// Set page title and include header
$page_title = 'Catering Requests Management';

// Include database connection and functions
require_once '../includes/config.php';
require_once 'includes/functions.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: login.php');
    exit();
}

// Initialize variables
$search = $_GET['search'] ?? '';
$status = $_GET['status'] ?? 'all';
$date_from = $_GET['date_from'] ?? '';
$date_to = $_GET['date_to'] ?? '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 10;
$offset = ($page - 1) * $per_page;

// Build base query
$query = "FROM catering_requests cr 
          LEFT JOIN users u ON cr.user_id = u.id 
          WHERE 1=1";
$count_query = "SELECT COUNT(*) as total $query";
$params = [];

// Apply filters
if ($search) {
    $query .= " AND (cr.name LIKE :search OR cr.email LIKE :search OR cr.phone LIKE :search OR cr.event_type LIKE :search OR cr.notes LIKE :search)";
    $params[':search'] = "%$search%";
}

if ($status !== 'all') {
    $query .= " AND cr.status = :status";
    $params[':status'] = $status;
}

if ($date_from) {
    $query .= " AND DATE(cr.event_date) >= :date_from";
    $params[':date_from'] = $date_from;
}

if ($date_to) {
    $query .= " AND DATE(cr.event_date) <= :date_to";
    $params[':date_to'] = $date_to;
}

// Get total count for pagination
$stmt = $pdo->prepare($count_query);
$stmt->execute($params);
$total_items = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
$total_pages = ceil($total_items / $per_page);

// Get catering requests data with pagination
$data_query = "SELECT cr.*, u.name as customer_name, u.email as customer_email, u.phone as customer_phone 
               $query 
               ORDER BY cr.created_at DESC 
               LIMIT :offset, :per_page";
$stmt = $pdo->prepare($data_query);

// Bind parameters for the data query
foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->bindValue(':per_page', $per_page, PDO::PARAM_INT);
$stmt->execute();
$requests = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Handle status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $request_id = (int)$_POST['request_id'];
    $new_status = $_POST['status'];
    $admin_notes = trim($_POST['admin_notes'] ?? '');
    
    try {
        $stmt = $pdo->prepare("UPDATE catering_requests SET status = ?, admin_notes = ? WHERE id = ?");
        $stmt->execute([$new_status, $admin_notes, $request_id]);
        
        $_SESSION['message'] = [
            'type' => 'success',
            'text' => 'Catering request status updated successfully!'
        ];
        
        // Redirect to prevent form resubmission
        header('Location: ' . $_SERVER['PHP_SELF'] . '?' . http_build_query($_GET));
        exit();
    } catch (PDOException $e) {
        $_SESSION['message'] = [
            'type' => 'error',
            'text' => 'Error updating catering request: ' . $e->getMessage()
        ];
    }
}

// Include header
include 'includes/header.php';
?>

<div class="max-w-7xl mx-auto">
    <!-- Page Header -->
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold text-gray-900">Catering Requests</h1>
        <a href="#" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-primary hover:bg-secondary focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary">
            <i class="fas fa-download mr-2"></i> Export Requests
        </a>
    </div>

    <!-- Filters -->
    <div class="bg-white shadow overflow-hidden sm:rounded-lg mb-6">
        <div class="px-4 py-5 sm:p-6">
            <form method="GET" class="space-y-4">
                <div class="grid grid-cols-1 gap-y-6 gap-x-4 sm:grid-cols-12">
                    <!-- Search -->
                    <div class="sm:col-span-4">
                        <label for="search" class="block text-sm font-medium text-gray-700">Search</label>
                        <div class="mt-1 relative rounded-md shadow-sm">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i class="fas fa-search text-gray-400"></i>
                            </div>
                            <input type="text" name="search" id="search" value="<?= htmlspecialchars($search) ?>" 
                                   class="focus:ring-primary focus:border-primary block w-full pl-10 sm:text-sm border-gray-300 rounded-md" 
                                   placeholder="Search requests...">
                        </div>
                    </div>

                    <!-- Status Filter -->
                    <div class="sm:col-span-2">
                        <label for="status" class="block text-sm font-medium text-gray-700">Status</label>
                        <select id="status" name="status" class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-primary focus:border-primary sm:text-sm rounded-md">
                            <option value="all" <?= $status === 'all' ? 'selected' : '' ?>>All Statuses</option>
                            <option value="pending" <?= $status === 'pending' ? 'selected' : '' ?>>Pending</option>
                            <option value="confirmed" <?= $status === 'confirmed' ? 'selected' : '' ?>>Confirmed</option>
                            <option value="completed" <?= $status === 'completed' ? 'selected' : '' ?>>Completed</option>
                            <option value="cancelled" <?= $status === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                        </select>
                    </div>

                    <!-- Date Range -->
                    <div class="sm:col-span-3">
                        <label for="date_from" class="block text-sm font-medium text-gray-700">Event Date Range</label>
                        <div class="mt-1 flex rounded-md shadow-sm">
                            <input type="date" name="date_from" id="date_from" value="<?= htmlspecialchars($date_from) ?>" 
                                   class="focus:ring-primary focus:border-primary flex-1 block w-full rounded-none rounded-l-md sm:text-sm border-gray-300">
                            <span class="inline-flex items-center px-3 rounded-r-md border border-l-0 border-gray-300 bg-gray-50 text-gray-500 sm:text-sm">
                                to
                            </span>
                            <input type="date" name="date_to" id="date_to" value="<?= htmlspecialchars($date_to) ?>" 
                                   class="focus:ring-primary focus:border-primary flex-1 block w-full rounded-none rounded-r-md sm:text-sm border-gray-300">
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="sm:col-span-3 flex items-end space-x-3">
                        <button type="submit" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-primary hover:bg-secondary focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary">
                            <i class="fas fa-filter mr-2"></i> Apply Filters
                        </button>
                        <a href="?" class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary">
                            <i class="fas fa-sync-alt mr-2"></i> Reset
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Requests List -->
    <div class="bg-white shadow overflow-hidden sm:rounded-lg">
        <div class="px-4 py-5 sm:p-0">
            <?php if (empty($requests)): ?>
                <div class="text-center py-12">
                    <i class="fas fa-utensils text-4xl text-gray-400 mb-4"></i>
                    <h3 class="text-lg font-medium text-gray-900">No catering requests found</h3>
                    <p class="mt-1 text-sm text-gray-500">There are no catering requests matching your criteria.</p>
                </div>
            <?php else: ?>
                <ul class="divide-y divide-gray-200">
                    <?php foreach ($requests as $request): ?>
                        <li class="px-4 py-5 sm:px-6 hover:bg-gray-50">
                            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between">
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center space-x-3">
                                        <h3 class="text-lg font-medium text-gray-900">
                                            <?= htmlspecialchars($request['event_type']) ?: 'Catering Request' ?>
                                            <?php if ($request['customer_name']): ?>
                                                <span class="text-sm text-gray-500">for <?= htmlspecialchars($request['customer_name']) ?></span>
                                            <?php endif; ?>
                                        </h3>
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium 
                                            <?= $request['status'] === 'confirmed' ? 'bg-green-100 text-green-800' : 
                                               ($request['status'] === 'pending' ? 'bg-yellow-100 text-yellow-800' : 
                                               ($request['status'] === 'cancelled' ? 'bg-red-100 text-red-800' : 'bg-gray-100 text-gray-800')) ?>">
                                            <?= ucfirst(htmlspecialchars($request['status'])) ?>
                                        </span>
                                    </div>
                                    <div class="mt-1 flex flex-col sm:flex-row sm:flex-wrap sm:mt-0 sm:space-x-6">
                                        <?php if ($request['event_date']): ?>
                                            <div class="mt-2 flex items-center text-sm text-gray-500">
                                                <i class="far fa-calendar-alt mr-1.5"></i>
                                                <?= date('M j, Y', strtotime($request['event_date'])) ?>
                                                <?php if ($request['event_time']): ?>
                                                    at <?= date('g:i A', strtotime($request['event_time'])) ?>
                                                <?php endif; ?>
                                            </div>
                                        <?php endif; ?>
                                        <?php if ($request['guests']): ?>
                                            <div class="mt-2 flex items-center text-sm text-gray-500">
                                                <i class="fas fa-users mr-1.5"></i>
                                                <?= $request['guests'] ?> guests
                                            </div>
                                        <?php endif; ?>
                                        <?php if ($request['phone']): ?>
                                            <div class="mt-2 flex items-center text-sm text-gray-500">
                                                <i class="fas fa-phone-alt mr-1.5"></i>
                                                <?= htmlspecialchars($request['phone']) ?>
                                            </div>
                                        <?php endif; ?>
                                        <?php if ($request['email']): ?>
                                            <div class="mt-2 flex items-center text-sm text-gray-500">
                                                <i class="far fa-envelope mr-1.5"></i>
                                                <?= htmlspecialchars($request['email']) ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="mt-4 flex-shrink-0 sm:mt-0 sm:ml-5">
                                    <div class="flex space-x-2">
                                        <button onclick="openRequestModal(<?= htmlspecialchars(json_encode($request)) ?>)" 
                                                class="inline-flex items-center px-3 py-1.5 border border-transparent text-xs font-medium rounded-md text-primary bg-primary/10 hover:bg-primary/20 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary">
                                            <i class="fas fa-eye mr-1"></i> View
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </li>
                    <?php endforeach; ?>
                </ul>

                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                    <div class="bg-white px-4 py-3 flex items-center justify-between border-t border-gray-200 sm:px-6">
                        <div class="flex-1 flex justify-between sm:hidden">
                            <?php if ($page > 1): ?>
                                <a href="?page=<?= $page - 1 ?><?= $status ? '&status=' . urlencode($status) : '' ?><?= $search ? '&search=' . urlencode($search) : '' ?>" 
                                   class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                                    Previous
                                </a>
                            <?php endif; ?>
                            <?php if ($page < $total_pages): ?>
                                <a href="?page=<?= $page + 1 ?><?= $status ? '&status=' . urlencode($status) : '' ?><?= $search ? '&search=' . urlencode($search) : '' ?>" 
                                   class="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                                    Next
                                </a>
                            <?php endif; ?>
                        </div>
                        <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
                            <div>
                                <p class="text-sm text-gray-700">
                                    Showing <span class="font-medium"><?= $offset + 1 ?></span> to 
                                    <span class="font-medium"><?= min($offset + $per_page, $total_items) ?></span> of 
                                    <span class="font-medium"><?= $total_items ?></span> results
                                </p>
                            </div>
                            <div>
                                <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px" aria-label="Pagination">
                                    <?php if ($page > 1): ?>
                                        <a href="?page=<?= $page - 1 ?><?= $status ? '&status=' . urlencode($status) : '' ?><?= $search ? '&search=' . urlencode($search) : '' ?>" 
                                           class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
                                            <span class="sr-only">Previous</span>
                                            <i class="fas fa-chevron-left h-5 w-5"></i>
                                        </a>
                                    <?php endif; ?>
                                    
                                    <?php
                                    $start_page = max(1, $page - 2);
                                    $end_page = min($total_pages, $page + 2);
                                    
                                    if ($start_page > 1) {
                                        echo '<a href="?page=1" class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700 hover:bg-gray-50">1</a>';
                                        if ($start_page > 2) {
                                            echo '<span class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700">...</span>';
                                        }
                                    }
                                    
                                    for ($i = $start_page; $i <= $end_page; $i++): ?>
                                        <a href="?page=<?= $i ?><?= $status ? '&status=' . urlencode($status) : '' ?><?= $search ? '&search=' . urlencode($search) : '' ?>" 
                                           class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium <?= $i === $page ? 'bg-primary text-white' : 'bg-white text-gray-700 hover:bg-gray-50' ?>">
                                            <?= $i ?>
                                        </a>
                                    <?php endfor; ?>
                                    
                                    if ($end_page < $total_pages) {
                                        if ($end_page < $total_pages - 1) {
                                            echo '<span class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700">...</span>';
                                        }
                                        echo '<a href="?page=' . $total_pages . '" class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700 hover:bg-gray-50">' . $total_pages . '</a>';
                                    }
                                    ?>
                                    
                                    <?php if ($page < $total_pages): ?>
                                        <a href="?page=<?= $page + 1 ?><?= $status ? '&status=' . urlencode($status) : '' ?><?= $search ? '&search=' . urlencode($search) : '' ?>" 
                                           class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
                                            <span class="sr-only">Next</span>
                                            <i class="fas fa-chevron-right h-5 w-5"></i>
                                        </a>
                                    <?php endif; ?>
                                </nav>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Request Details Modal -->
<div id="requestModal" class="hidden fixed z-10 inset-0 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true" onclick="closeRequestModal()"></div>
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
        <div class="inline-block align-bottom bg-white rounded-lg px-4 pt-5 pb-4 text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-2xl sm:w-full sm:p-6">
            <div class="absolute top-0 right-0 pt-4 pr-4">
                <button type="button" onclick="closeRequestModal()" class="bg-white rounded-md text-gray-400 hover:text-gray-500 focus:outline-none">
                    <span class="sr-only">Close</span>
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div>
                <div class="mt-3 text-center sm:mt-0 sm:text-left">
                    <h3 class="text-lg leading-6 font-medium text-gray-900" id="modal-title">
                        <span id="modalEventType"></span>
                        <span id="modalStatus" class="ml-2 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"></span>
                    </h3>
                    <div class="mt-4">
                        <div class="grid grid-cols-1 gap-y-4 gap-x-4 sm:grid-cols-2">
                            <div class="sm:col-span-2">
                                <h4 class="text-sm font-medium text-gray-500">Event Details</h4>
                                <div class="mt-1 text-sm text-gray-900" id="modalEventDetails"></div>
                            </div>
                            <div class="sm:col-span-2">
                                <h4 class="text-sm font-medium text-gray-500">Contact Information</h4>
                                <div class="mt-1 text-sm text-gray-900" id="modalContactInfo"></div>
                            </div>
                            <div class="sm:col-span-2">
                                <h4 class="text-sm font-medium text-gray-500">Additional Notes</h4>
                                <div class="mt-1 text-sm text-gray-900 whitespace-pre-line" id="modalNotes"></div>
                            </div>
                            <div class="sm:col-span-2">
                                <h4 class="text-sm font-medium text-gray-500">Admin Notes</h4>
                                <div class="mt-1">
                                    <form id="statusForm" method="POST" class="space-y-4">
                                        <input type="hidden" name="request_id" id="requestId">
                                        <div class="mt-1">
                                            <select id="status" name="status" class="block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-primary focus:border-primary sm:text-sm rounded-md">
                                                <option value="pending">Pending</option>
                                                <option value="confirmed">Confirmed</option>
                                                <option value="completed">Completed</option>
                                                <option value="cancelled">Cancelled</option>
                                            </select>
                                        </div>
                                        <div>
                                            <label for="admin_notes" class="sr-only">Admin Notes</label>
                                            <textarea id="admin_notes" name="admin_notes" rows="3" class="shadow-sm focus:ring-primary focus:border-primary block w-full sm:text-sm border border-gray-300 rounded-md" placeholder="Add any notes or updates here..."></textarea>
                                        </div>
                                        <div class="flex justify-end">
                                            <button type="button" onclick="closeRequestModal()" class="bg-white py-2 px-4 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary">
                                                Cancel
                                            </button>
                                            <button type="submit" name="update_status" class="ml-3 inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-primary hover:bg-secondary focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary">
                                                Update Status
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Function to open the request modal with data
function openRequestModal(request) {
    const modal = document.getElementById('requestModal');
    const statusMap = {
        'pending': { class: 'bg-yellow-100 text-yellow-800', text: 'Pending' },
        'confirmed': { class: 'bg-green-100 text-green-800', text: 'Confirmed' },
        'completed': { class: 'bg-blue-100 text-blue-800', text: 'Completed' },
        'cancelled': { class: 'bg-red-100 text-red-800', text: 'Cancelled' }
    };

    // Set the modal title and status
    document.getElementById('modalEventType').textContent = request.event_type || 'Catering Request';
    
    const statusSpan = document.getElementById('modalStatus');
    const status = statusMap[request.status] || { class: 'bg-gray-100 text-gray-800', text: 'Unknown' };
    statusSpan.className = `ml-2 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${status.class}`;
    statusSpan.textContent = status.text;

    // Set the event details
    let eventDetails = [];
    if (request.event_date) {
        const eventDate = new Date(request.event_date);
        let dateStr = eventDate.toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' });
        if (request.event_time) {
            const timeStr = new Date('1970-01-01T' + request.event_time).toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' });
            dateStr += ` at ${timeStr}`;
        }
        eventDetails.push(`<p><span class="font-medium">When:</span> ${dateStr}</p>`);
    }
    if (request.guests) {
        eventDetails.push(`<p><span class="font-medium">Number of Guests:</span> ${request.guests}</p>`);
    }
    if (request.location) {
        eventDetails.push(`<p><span class="font-medium">Location:</span> ${request.location}</p>`);
    }
    if (request.budget) {
        eventDetails.push(`<p><span class="font-medium">Budget:</span> $${parseFloat(request.budget).toFixed(2)}</p>`);
    }
    document.getElementById('modalEventDetails').innerHTML = eventDetails.join('');

    // Set the contact information
    let contactInfo = [];
    if (request.customer_name) {
        contactInfo.push(`<p><span class="font-medium">Name:</span> ${request.customer_name}</p>`);
    }
    if (request.email) {
        contactInfo.push(`<p><span class="font-medium">Email:</span> <a href="mailto:${request.email}" class="text-primary hover:text-secondary">${request.email}</a></p>`);
    }
    if (request.phone) {
        contactInfo.push(`<p><span class="font-medium">Phone:</span> <a href="tel:${request.phone}" class="text-primary hover:text-secondary">${request.phone}</a></p>`);
    }
    document.getElementById('modalContactInfo').innerHTML = contactInfo.join('');

    // Set the notes and admin notes
    document.getElementById('modalNotes').textContent = request.notes || 'No additional notes provided.';
    document.getElementById('admin_notes').value = request.admin_notes || '';
    
    // Set the form action and request ID
    document.getElementById('requestId').value = request.id;
    document.getElementById('status').value = request.status || 'pending';
    
    // Show the modal
    modal.classList.remove('hidden');
    document.body.classList.add('overflow-hidden');
}

// Function to close the request modal
function closeRequestModal() {
    const modal = document.getElementById('requestModal');
    modal.classList.add('hidden');
    document.body.classList.remove('overflow-hidden');
}

// Close modal when clicking outside the modal content
document.getElementById('requestModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeRequestModal();
    }
});

// Close modal with Escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeRequestModal();
    }
});
</script>

<?php
// Include footer
include 'includes/footer.php';
?>
