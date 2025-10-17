<?php
// admin/create_newsletter.php - Newsletter creation and management interface

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}

// 2. Page Configuration
$page_title = 'Newsletter Management';
$page_description = 'Create and manage newsletter campaigns';

// 3. Include Core Dependencies
require_once dirname(__DIR__) . '/includes/config.php';
require_once dirname(__DIR__) . '/includes/newsletter_sender.php';

// 4. Handle POST Requests
$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (isset($_POST['create_newsletter']) || isset($_POST['update_newsletter'])) {
            $campaign_id = (int)($_POST['campaign_id'] ?? 0);
            $subject = trim($_POST['subject'] ?? '');
            $content = $_POST['content'] ?? '';
            $template_id = (int)($_POST['template_id'] ?? 0);
            $action = $_POST['action'] ?? 'draft'; // 'draft', 'send_now', 'schedule'
            $scheduled_at_input = trim($_POST['scheduled_at'] ?? '');

            // Validate input
            if (empty($subject) || empty($content)) {
                throw new Exception('Subject and content are required.');
            }

            // Determine status and scheduled_at
            $status = 'draft';
            $scheduled_at = null;

            if ($action === 'send_now') {
                $status = 'sending';
            } elseif ($action === 'schedule' && !empty($scheduled_at_input)) {
                $status = 'scheduled';
                // Validate datetime format
                $dt = new DateTime($scheduled_at_input);
                $scheduled_at = $dt->format('Y-m-d H:i:s');
                if ($dt < new DateTime()) {
                    throw new Exception('Scheduled time cannot be in the past.');
                }
            }

            // Get template if specified
            $template_val = $template_id > 0 ? $template_id : null;

            if ($campaign_id > 0 && isset($_POST['update_newsletter'])) {
                // Update existing newsletter
                $stmt = $pdo->prepare("
                    UPDATE newsletter_campaigns
                    SET subject = ?, content = ?, template = ?, status = ?, scheduled_at = ?, updated_at = CURRENT_TIMESTAMP
                    WHERE id = ? AND status IN ('draft', 'scheduled')
                ");
                $stmt->execute([$subject, $content, $template_val, $status, $scheduled_at, $campaign_id]);
                $message = 'Newsletter updated successfully!';
                $message_type = 'success';

                // If updated to send_now, initiate sending
                if ($action === 'send_now') {
                    sendNewsletterCampaign($campaign_id);
                    $message = 'Newsletter update complete, sending initiated!';
                }

            } else {
                // Insert new newsletter campaign
                $stmt = $pdo->prepare("
                    INSERT INTO newsletter_campaigns (subject, content, template, status, scheduled_at, created_by)
                    VALUES (?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([$subject, $content, $template_val, $status, $scheduled_at, $_SESSION['user_id']]);
                $campaign_id = $pdo->lastInsertId();

                $message = 'Newsletter ' . ($action === 'schedule' ? 'scheduled' : 'saved as draft') . ' successfully!';
                $message_type = 'success';

                // If sent now, initiate sending
                if ($action === 'send_now') {
                    sendNewsletterCampaign($campaign_id);
                    $message = 'Newsletter created and sending initiated! You will be notified when complete.';
                }
            }
        }

        if (isset($_POST['delete_newsletter'])) {
            $campaign_id = (int)$_POST['campaign_id'];
            $stmt = $pdo->prepare("UPDATE newsletter_campaigns SET status = 'cancelled', updated_at = CURRENT_TIMESTAMP WHERE id = ?");
            $stmt->execute([$campaign_id]);
            $message = 'Newsletter cancelled successfully!';
            $message_type = 'success';
        }

    } catch (Exception $e) {
        $message = $e->getMessage();
        $message_type = 'error';
    }

    // Redirect to prevent form resubmission
    if ($message) {
        $_SESSION['message'] = ['type' => $message_type, 'text' => $message];
    }
    header('Location: create_newsletter.php');
    exit();
}

// 5. Get data for display
try {
    // Get newsletter templates
    $stmt = $pdo->prepare("SELECT * FROM newsletter_templates WHERE is_active = TRUE ORDER BY name");
    $stmt->execute();
    $templates = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get newsletter campaigns
    $stmt = $pdo->prepare("
        SELECT nc.*, COALESCE(u.name, 'Unknown') as created_by_name
        FROM newsletter_campaigns nc
        LEFT JOIN users u ON nc.created_by = u.id
        ORDER BY nc.created_at DESC
    ");
    $stmt->execute();
    $campaigns = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get subscriber count for sending
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM newsletter_subscriptions WHERE is_active = TRUE");
    $stmt->execute();
    $subscriber_count = $stmt->fetchColumn();

} catch (PDOException $e) {
    // Log the error for debugging
    error_log("Database error: " . $e->getMessage());
    $templates = [];
    $campaigns = [];
    $subscriber_count = 0;
}

// Helper functions for newsletter status display
function getStatusColor($status) {
    $colors = [
        'draft' => 'border-gray-200 bg-gray-50',
        'scheduled' => 'border-blue-200 bg-blue-50',
        'sending' => 'border-yellow-200 bg-yellow-50',
        'sent' => 'border-green-200 bg-green-50',
        'cancelled' => 'border-red-200 bg-red-50'
    ];
    return $colors[strtolower($status)] ?? 'border-gray-200 bg-gray-50';
}

function getStatusBadgeColor($status) {
    $colors = [
        'draft' => 'bg-gray-100 text-gray-800',
        'scheduled' => 'bg-blue-100 text-blue-800',
        'sending' => 'bg-yellow-100 text-yellow-800',
        'sent' => 'bg-green-100 text-green-800',
        'cancelled' => 'bg-red-100 text-red-800'
    ];
    return $colors[strtolower($status)] ?? 'bg-gray-100 text-gray-800';
}

// 6. Include Header (Uses the reusable header.php)
include 'includes/header.php';

// Newsletter Management Content
?>

<div class="bg-gradient-to-br from-primary via-primary-dark to-secondary text-white mt-0">
    <div class="container mx-auto px-6 py-8">
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between">
            <div>
                <h1 class="text-3xl md:text-4xl font-bold mb-2"><?php echo htmlspecialchars($page_title); ?></h1>
                <p class="text-lg opacity-90"><?php echo htmlspecialchars($page_description); ?></p>
            </div>
            <div class="mt-4 lg:mt-0">
                <button type="button" onclick="openCreateModal()"
                        class="bg-white/20 hover:bg-white/30 text-white px-4 py-2 rounded-lg transition-colors">
                    <i class="fas fa-plus mr-2"></i>Create Newsletter
                </button>
            </div>
        </div>
    </div>
</div>

<div class="container mx-auto px-6 py-8">
    <?php if (isset($_SESSION['message'])): ?>
        <div class="mb-6 p-4 <?php echo $_SESSION['message']['type'] === 'success' ? 'bg-green-50 border-green-200 text-green-700' : 'bg-red-50 border-red-200 text-red-700'; ?> border rounded-lg">
            <p><?php echo htmlspecialchars($_SESSION['message']['text']); ?></p>
        </div>
        <?php unset($_SESSION['message']); ?>
    <?php endif; ?>

    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
        <div class="bg-white rounded-xl shadow-lg p-6 border">
            <div class="flex items-center">
                <div class="p-3 bg-blue-100 rounded-full">
                    <i class="fas fa-users text-blue-600 text-xl"></i>
                </div>
                <div class="ml-4">
                    <h3 class="text-lg font-semibold text-gray-800">Active Subscribers</h3>
                    <p class="text-2xl font-bold text-blue-600"><?php echo number_format($subscriber_count); ?></p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-lg p-6 border">
            <div class="flex items-center">
                <div class="p-3 bg-green-100 rounded-full">
                    <i class="fas fa-paper-plane text-green-600 text-xl"></i>
                </div>
                <div class="ml-4">
                    <h3 class="text-lg font-semibold text-gray-800">Campaigns Sent</h3>
                    <p class="text-2xl font-bold text-green-600"><?php echo count(array_filter($campaigns, fn($c) => $c['status'] === 'sent')); ?></p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-lg p-6 border">
            <div class="flex items-center">
                <div class="p-3 bg-yellow-100 rounded-full">
                    <i class="fas fa-clock text-yellow-600 text-xl"></i>
                </div>
                <div class="ml-4">
                    <h3 class="text-lg font-semibold text-gray-800">Scheduled</h3>
                    <p class="text-2xl font-bold text-yellow-600"><?php echo count(array_filter($campaigns, fn($c) => $c['status'] === 'scheduled')); ?></p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-lg p-6 border">
            <div class="flex items-center">
                <div class="p-3 bg-purple-100 rounded-full">
                    <i class="fas fa-edit text-purple-600 text-xl"></i>
                </div>
                <div class="ml-4">
                    <h3 class="text-lg font-semibold text-gray-800">Drafts</h3>
                    <p class="text-2xl font-bold text-purple-600"><?php echo count(array_filter($campaigns, fn($c) => $c['status'] === 'draft')); ?></p>
                </div>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-lg p-6">
        <h3 class="text-lg font-semibold text-gray-800 mb-4">Newsletter Campaigns</h3>

        <?php if (empty($campaigns)): ?>
            <p class="text-gray-500 text-center py-8">No newsletters created yet. Create your first newsletter above.</p>
        <?php else: ?>
            <div class="space-y-4">
                <?php foreach ($campaigns as $campaign): ?>
                    <div class="border rounded-lg p-6 <?php echo getStatusColor($campaign['status']); ?>">
                        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between">
                            <div class="flex-1">
                                <div class="flex items-center justify-between mb-3">
                                    <h4 class="font-semibold text-gray-800 text-lg"><?php echo htmlspecialchars($campaign['subject']); ?></h4>
                                    <span class="px-3 py-1 text-xs font-medium rounded-full <?php echo getStatusBadgeColor($campaign['status']); ?>">
                                        <?php echo ucfirst($campaign['status']); ?>
                                    </span>
                                </div>

                                <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-4 text-sm">
                                    <div>
                                        <span class="text-gray-500">Recipients:</span>
                                        <span class="font-medium"><?php echo number_format($campaign['total_recipients']); ?></span>
                                    </div>
                                    <div>
                                        <span class="text-gray-500">Sent:</span>
                                        <span class="font-medium"><?php echo number_format($campaign['sent_count']); ?></span>
                                    </div>
                                    <div>
                                        <span class="text-gray-500">Opened:</span>
                                        <span class="font-medium"><?php echo number_format($campaign['opened_count']); ?></span>
                                    </div>
                                    <div>
                                        <span class="text-gray-500">Created:</span>
                                        <span class="font-medium"><?php echo date('M j, Y', strtotime($campaign['created_at'])); ?></span>
                                    </div>
                                </div>

                                <?php if ($campaign['scheduled_at']): ?>
                                    <div class="text-sm text-gray-600">
                                        <i class="fas fa-clock mr-1"></i>
                                        Scheduled for: <?php echo date('M j, Y g:i A', strtotime($campaign['scheduled_at'])); ?>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <div class="flex items-center space-x-2 mt-4 lg:mt-0">
                                <?php if (in_array($campaign['status'], ['draft', 'scheduled'])): ?>
                                    <button onclick="editNewsletter(<?php echo $campaign['id']; ?>)"
                                            class="bg-blue-500 hover:bg-blue-600 text-white px-3 py-1 rounded text-sm">
                                        <i class="fas fa-edit mr-1"></i>Edit
                                    </button>

                                    <form method="POST" class="inline" onsubmit="return confirm('Are you sure you want to cancel this newsletter?');">
                                        <input type="hidden" name="campaign_id" value="<?php echo $campaign['id']; ?>">
                                        <button type="submit" name="delete_newsletter"
                                                class="bg-red-500 hover:bg-red-600 text-white px-3 py-1 rounded text-sm">
                                            <i class="fas fa-times mr-1"></i>Cancel
                                        </button>
                                    </form>
                                <?php endif; ?>

                                <?php if ($campaign['status'] === 'sent'): ?>
                                    <span class="text-gray-500 text-sm">Sent by <?php echo htmlspecialchars($campaign['created_by_name']); ?></span>
                                <?php endif; ?>

                                <button onclick="previewNewsletter(<?php echo $campaign['id']; ?>)"
                                        class="bg-gray-500 hover:bg-gray-600 text-white px-3 py-1 rounded text-sm">
                                    <i class="fas fa-eye mr-1"></i>Preview
                                </button>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<div id="createEditModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50 flex items-center justify-center p-4" style="backdrop-filter: blur(2px);">
    <div class="bg-white rounded-xl max-w-4xl w-full max-h-[90vh] overflow-y-auto shadow-2xl relative">
        <div class="p-6">
            <h3 id="modalTitle" class="text-lg font-semibold text-gray-800 mb-4">Create New Newsletter</h3>
            <button type="button" onclick="closeCreateEditModal()" class="absolute top-4 right-4 text-gray-400 hover:text-gray-600">
                <i class="fas fa-times text-xl"></i>
            </button>

            <form method="POST" id="newsletterForm" class="space-y-6">
                <input type="hidden" name="campaign_id" id="campaign_id" value="">
                <input type="hidden" name="action_type" id="action_type" value="create_newsletter">

                <div>
                    <label for="subject" class="block text-sm font-medium text-gray-700 mb-2">Subject Line *</label>
                    <input type="text" id="subject" name="subject"
                            required maxlength="255"
                            placeholder="Enter compelling subject line..."
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-primary focus:border-primary">
                    <p class="text-sm text-gray-500 mt-1">Keep it under 60 characters for best open rates</p>
                </div>

                <div>
                    <label for="template_id" class="block text-sm font-medium text-gray-700 mb-2">Email Template</label>
                    <select id="template_id" name="template_id" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-primary focus:border-primary">
                        <option value="">No template (plain text)</option>
                        <?php foreach ($templates as $template): ?>
                            <option value="<?php echo $template['id']; ?>"><?php echo htmlspecialchars($template['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label for="content" class="block text-sm font-medium text-gray-700 mb-2">Newsletter Content *</label>
                    <textarea id="content" name="content" rows="15" required
                                placeholder="Write your newsletter content here... You can use HTML formatting."
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-primary focus:border-primary font-mono text-sm"></textarea>
                    <p class="text-sm text-gray-500 mt-1">Use HTML tags for formatting. Basic tags like &lt;p&gt;, &lt;strong&gt;, &lt;em&gt;, &lt;h1&gt;-&lt;h3&gt; are supported.</p>
                </div>

                <div id="scheduleOptions" class="hidden">
                    <label for="scheduled_at" class="block text-sm font-medium text-gray-700 mb-2">Schedule For</label>
                    <input type="datetime-local" id="scheduled_at" name="scheduled_at"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-primary focus:border-primary">
                    <p class="text-sm text-gray-500 mt-1">Set a future date and time to send this newsletter.</p>
                </div>

                <div class="flex flex-col sm:flex-row gap-4 justify-end">
                    <button type="button" onclick="submitNewsletterForm('draft')" id="saveDraftBtn" class="bg-gray-500 hover:bg-gray-600 text-white px-6 py-2 rounded-lg">
                        <i class="fas fa-save mr-2"></i>Save as Draft
                    </button>

                    <button type="button" onclick="toggleScheduleInput()" id="scheduleBtn" class="bg-yellow-500 hover:bg-yellow-600 text-white px-6 py-2 rounded-lg">
                        <i class="fas fa-clock mr-2"></i>Schedule
                    </button>

                    <button type="button" onclick="submitNewsletterForm('send_now')" id="sendNowBtn"
                            class="bg-green-500 hover:bg-green-600 text-white px-6 py-2 rounded-lg"
                            <?php echo $subscriber_count == 0 ? 'disabled' : ''; ?>>
                        <i class="fas fa-paper-plane mr-2"></i>Send Now
                    </button>

                    <button type="button" onclick="closeCreateEditModal()" class="bg-red-500 hover:bg-red-600 text-white px-6 py-2 rounded-lg">
                        Close
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<div id="previewModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50 flex items-center justify-center p-4" style="backdrop-filter: blur(2px);">
    <div class="bg-white rounded-lg max-w-4xl w-full max-h-[90vh] overflow-y-auto shadow-2xl relative">
        <div class="p-6 border-b">
            <div class="flex items-center justify-between">
                <h3 class="text-lg font-semibold">Newsletter Preview</h3>
                <button onclick="closePreviewModal()" class="text-gray-400 hover:text-gray-600">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
        </div>
        <div class="p-6">
            <div id="previewContent"></div>
        </div>
    </div>
</div>

<script>
    const createEditModal = document.getElementById('createEditModal');
    const previewModal = document.getElementById('previewModal');
    const newsletterForm = document.getElementById('newsletterForm');
    const modalTitle = document.getElementById('modalTitle');
    const campaignIdInput = document.getElementById('campaign_id');
    const subjectInput = document.getElementById('subject');
    const contentInput = document.getElementById('content');
    const templateIdSelect = document.getElementById('template_id');
    const actionTypeInput = document.getElementById('action_type');
    const scheduleOptionsDiv = document.getElementById('scheduleOptions');
    const scheduledAtInput = document.getElementById('scheduled_at');
    const sendNowBtn = document.getElementById('sendNowBtn');
    const saveDraftBtn = document.getElementById('saveDraftBtn');
    const scheduleBtn = document.getElementById('scheduleBtn');

    // Function to open the Create/Edit Modal
    function openCreateModal() {
        // Reset form for creation
        newsletterForm.reset();
        campaignIdInput.value = '';
        actionTypeInput.value = 'create_newsletter';
        modalTitle.textContent = 'Create New Newsletter';
        scheduleOptionsDiv.classList.add('hidden');
        scheduledAtInput.removeAttribute('required');
        scheduledAtInput.value = '';

        // Update button texts
        saveDraftBtn.innerHTML = '<i class="fas fa-save mr-2"></i>Save as Draft';
        scheduleBtn.innerHTML = '<i class="fas fa-clock mr-2"></i>Schedule';
        sendNowBtn.innerHTML = '<i class="fas fa-paper-plane mr-2"></i>Send Now';

        createEditModal.classList.remove('hidden');
        document.body.style.overflow = 'hidden';
    }

    // Function to close the Create/Edit Modal
    function closeCreateEditModal() {
        createEditModal.classList.add('hidden');
        document.body.style.overflow = '';
        // Reset validaiton styles
        newsletterForm.querySelectorAll('.border-red-500').forEach(el => el.classList.remove('border-red-500'));
    }

    // Function to load data and open modal for editing
    async function editNewsletter(campaignId) {
        openCreateModal(); // Open and reset the form first

        modalTitle.textContent = 'Edit Newsletter';
        actionTypeInput.value = 'update_newsletter';
        campaignIdInput.value = campaignId;

        saveDraftBtn.innerHTML = '<i class="fas fa-save mr-2"></i>Update Draft';
        scheduleBtn.innerHTML = '<i class="fas fa-clock mr-2"></i>Update Schedule';
        sendNowBtn.innerHTML = '<i class="fas fa-paper-plane mr-2"></i>Send Now';


        try {
            const response = await fetch(`api/get_newsletter.php?id=${campaignId}`); // You'll need to create this API endpoint
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            const data = await response.json();

            if (data.success) {
                subjectInput.value = data.newsletter.subject;
                contentInput.value = data.newsletter.content;
                templateIdSelect.value = data.newsletter.template || '';

                if (data.newsletter.status === 'scheduled' && data.newsletter.scheduled_at) {
                    toggleScheduleInput(true); // Show schedule input
                    // Format datetime for input[type="datetime-local"]
                    scheduledAtInput.value = data.newsletter.scheduled_at.substring(0, 16);
                } else {
                    toggleScheduleInput(false); // Hide schedule input
                }

                // Disable send now if no subscribers
                if (<?php echo $subscriber_count; ?> === 0) {
                    sendNowBtn.disabled = true;
                    sendNowBtn.title = 'No active subscribers to send to.';
                } else {
                    sendNowBtn.disabled = false;
                    sendNowBtn.title = '';
                }

            } else {
                alert('Error loading newsletter data: ' + data.message);
                closeCreateEditModal();
            }
        } catch (error) {
            console.error('Fetch error:', error);
            alert('Failed to load newsletter data.');
            closeCreateEditModal();
        }
    }

    // Toggle schedule input visibility
    function toggleScheduleInput(show = null) {
        if (show === null) {
            scheduleOptionsDiv.classList.toggle('hidden');
        } else if (show) {
            scheduleOptionsDiv.classList.remove('hidden');
        } else {
            scheduleOptionsDiv.classList.add('hidden');
        }

        if (!scheduleOptionsDiv.classList.contains('hidden')) {
            scheduledAtInput.setAttribute('required', 'required');
            scheduledAtInput.min = new Date().toISOString().slice(0, 16); // Set minimum to current datetime
        } else {
            scheduledAtInput.removeAttribute('required');
            scheduledAtInput.value = ''; // Clear value if hidden
        }
    }

    // Function to submit the form with a specific action
    async function submitNewsletterForm(action) {
        const confirmMessage = {
            'draft': 'Are you sure you want to save this as a draft?',
            'schedule': 'Are you sure you want to schedule this newsletter?',
            'send_now': `Are you sure you want to send this newsletter to <?php echo $subscriber_count; ?> subscribers now?`
        };

        let finalAction = action;

        // If scheduling, and input is visible, ensure datetime is present
        if (action === 'schedule') {
            if (scheduleOptionsDiv.classList.contains('hidden')) {
                toggleScheduleInput(true); // Show it if not already
                return; // Wait for user to input date/time or click again
            }
            if (!scheduledAtInput.value) {
                alert('Please select a date and time to schedule the newsletter.');
                scheduledAtInput.focus();
                scheduledAtInput.classList.add('border-red-500'); // Highlight missing input
                return;
            }
        }

        if (action === 'send_now' && <?php echo $subscriber_count; ?> === 0) {
            alert('Cannot send: No active subscribers.');
            return;
        }


        if (!confirm(confirmMessage[finalAction])) {
            return; // User cancelled
        }

        // Set the action for the PHP script
        const hiddenActionInput = document.createElement('input');
        hiddenActionInput.type = 'hidden';
        hiddenActionInput.name = 'action';
        hiddenActionInput.value = finalAction;
        newsletterForm.appendChild(hiddenActionInput);

        // Set action_type for update/create
        actionTypeInput.name = finalAction === 'send_now' && campaignIdInput.value ? 'update_newsletter' : (campaignIdInput.value ? 'update_newsletter' : 'create_newsletter');


        newsletterForm.submit();
    }


    async function previewNewsletter(campaignId) {
        try {
            const response = await fetch('preview_newsletter.php?id=' + campaignId);
            const html = await response.text();

            document.getElementById('previewContent').innerHTML = html;
            previewModal.classList.remove('hidden');
            document.body.style.overflow = 'hidden';
        } catch (error) {
            alert('Error loading preview: ' + error.message);
            console.error('Preview load error:', error);
        }
    }

    function closePreviewModal() {
        previewModal.classList.add('hidden');
        document.body.style.overflow = '';
    }

    // Close modals when clicking outside
    createEditModal.addEventListener('click', function(e) {
        if (e.target === this) {
            closeCreateEditModal();
        }
    });

    previewModal.addEventListener('click', function(e) {
        if (e.target === this) {
            closePreviewModal();
        }
    });

</script>

<?php
// Include footer
require_once 'includes/footer.php';

// Close the HTML structure that header.php expects
?>
</div>
</div>
</main>
</body>
</html>