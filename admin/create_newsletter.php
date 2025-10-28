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
            }
            // Default status is 'draft' for draft saves

            // Get template if specified
            $template_val = $template_id > 0 ? $template_id : null;

            if ($campaign_id > 0 && isset($_POST['update_newsletter'])) {
                // Update existing newsletter
                $stmt = $pdo->prepare("
                    UPDATE newsletter_campaigns
                    SET subject = ?, content = ?, template = ?, status = ?, updated_at = CURRENT_TIMESTAMP
                    WHERE id = ? AND status = 'draft'
                ");
                $stmt->execute([$subject, $content, $template_val, $status, $campaign_id]);
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
                    INSERT INTO newsletter_campaigns (subject, content, template, status, created_by)
                    VALUES (?, ?, ?, ?, ?)
                ");
                $stmt->execute([$subject, $content, $template_val, $status, $_SESSION['user_id']]);
                $campaign_id = $pdo->lastInsertId();

                $message = 'Newsletter ' . ($action === 'send_now' ? 'created and sending initiated' : 'saved as draft') . ' successfully!';
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
        'sending' => 'border-yellow-200 bg-yellow-50',
        'sent' => 'border-green-200 bg-green-50',
        'cancelled' => 'border-red-200 bg-red-50'
    ];
    return $colors[strtolower($status)] ?? 'border-gray-200 bg-gray-50';
}

function getStatusBadgeColor($status) {
    $colors = [
        'draft' => 'bg-gray-100 text-gray-800',
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
                <div class="flex flex-col sm:flex-row gap-2">
                    <button type="button" id="createNewsletterBtn" onclick="openCreateModal()"
                            class="bg-white/20 hover:bg-white/30 text-white px-4 py-2 rounded-lg transition-colors">
                        <i class="fas fa-plus mr-2"></i>Create Newsletter
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="container mx-auto px-6 py-8">
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
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
                            </div>

                            <div class="flex items-center space-x-2 mt-4 lg:mt-0">
                                <?php if ($campaign['status'] === 'draft'): ?>
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
                                    <i class="fas fa-eye mr-1"></i>Review
                                </button>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Send Newsletter Confirmation Modal -->
<div id="sendNewsletterModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-[60] p-4 animate__animated animate__fadeIn">
    <div class="bg-white rounded-2xl shadow-2xl max-w-md w-full max-h-[90vh] overflow-y-auto animate__animated animate__zoomIn">
        <!-- Modal Header -->
        <div class="bg-gradient-to-r from-green-600 to-green-700 p-6 text-white rounded-t-2xl">
            <div class="flex items-center justify-between">
                <h3 class="text-xl font-bold flex items-center">
                    <i class="fas fa-paper-plane mr-2"></i>Send Newsletter Now
                </h3>
                <button type="button" onclick="closeSendNewsletterModal()" class="text-white hover:text-gray-200 text-2xl">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        </div>

        <!-- Modal Body -->
        <div class="p-6 text-gray-700">
            <div class="text-center mb-4">
                <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-paper-plane text-2xl text-green-600"></i>
                </div>
                <h4 class="text-lg font-semibold text-gray-900 mb-2">Send Newsletter to All Subscribers</h4>
                <p class="text-sm text-gray-600 mb-4">
                    Are you sure you want to send this newsletter immediately to all active subscribers?
                </p>

                <div class="bg-gray-50 rounded-lg p-4 mb-4 text-left">
                    <div class="space-y-2 text-sm">
                        <div class="flex justify-between">
                            <span class="text-gray-600">Subject:</span>
                            <span class="font-medium text-gray-900" id="confirmSubject">Newsletter Subject</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600">Recipients:</span>
                            <span class="font-bold text-green-600" id="confirmRecipients"><?php echo number_format($subscriber_count); ?> subscribers</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600">Action:</span>
                            <span class="font-medium text-red-600">Send immediately</span>
                        </div>
                    </div>
                </div>

                <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-3 mb-4">
                    <div class="flex items-start">
                        <i class="fas fa-exclamation-triangle text-yellow-600 mt-0.5 mr-2"></i>
                        <div class="text-sm text-yellow-800">
                            <strong>Important:</strong> This will send the newsletter to <strong id="confirmRecipients2"><?php echo number_format($subscriber_count); ?></strong> active subscribers immediately. This action cannot be undone.
                        </div>
                    </div>
                </div>

                <p class="text-xs text-gray-500">The newsletter will be queued for sending and you will receive a notification when complete.</p>
            </div>
        </div>

        <!-- Modal Footer -->
        <div class="p-6 border-t border-gray-200 flex gap-3 justify-end">
            <button type="button" onclick="closeSendNewsletterModal()"
                    class="group relative bg-gray-500 hover:bg-gray-600 text-white px-6 py-3 rounded-xl font-semibold transition-all duration-300 flex items-center justify-center shadow-lg hover:shadow-xl transform hover:-translate-y-0.5">
                <div class="absolute inset-0 bg-gradient-to-r from-white/0 via-white/10 to-white/0 opacity-0 group-hover:opacity-100 transition-opacity duration-300 rounded-xl"></div>
                <span class="relative z-10 font-medium">Cancel</span>
            </button>
            <button type="button" onclick="confirmSendNewsletter()"
                    class="group relative bg-gradient-to-r from-green-600 to-green-700 hover:from-green-700 hover:to-green-800 text-white px-6 py-3 rounded-xl font-semibold transition-all duration-300 flex items-center justify-center shadow-lg hover:shadow-xl transform hover:-translate-y-0.5">
                <div class="absolute inset-0 bg-gradient-to-r from-white/0 via-white/10 to-white/0 opacity-0 group-hover:opacity-100 transition-opacity duration-300 rounded-xl"></div>
                <i class="fas fa-paper-plane mr-2 relative z-10"></i>
                <span class="relative z-10 font-medium">Send Now</span>
            </button>
        </div>
    </div>
</div>

<!-- Success Message Modal -->
<div id="newsletterSuccessModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-[80] p-4 animate__animated animate__fadeIn">
    <div class="bg-white rounded-2xl shadow-2xl max-w-md w-full max-h-[90vh] overflow-y-auto animate__animated animate__zoomIn">
        <!-- Modal Header -->
        <div class="bg-gradient-to-r from-green-600 to-green-700 p-6 text-white rounded-t-2xl">
            <div class="flex items-center justify-between">
                <h3 class="text-xl font-bold flex items-center">
                    <i class="fas fa-check-circle mr-2"></i>Newsletter Sent!
                </h3>
                <button type="button" onclick="closeNewsletterSuccessModal()" class="text-white hover:text-gray-200 text-2xl">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        </div>

        <!-- Modal Body -->
        <div class="p-6 text-gray-700">
            <div class="text-center mb-4">
                <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-check-circle text-2xl text-green-600"></i>
                </div>
                <h4 class="text-lg font-semibold text-gray-900 mb-2" id="newsletterSuccessTitle">Newsletter Sent Successfully!</h4>
                <p class="text-sm text-gray-600" id="newsletterSuccessMessage">
                    Your newsletter has been queued for sending and will be delivered to all active subscribers shortly.
                </p>
            </div>
        </div>

        <!-- Modal Footer -->
        <div class="p-6 border-t border-gray-200 flex gap-3 justify-center">
            <button type="button" onclick="closeNewsletterSuccessModal()"
                    class="group relative bg-gradient-to-r from-green-600 to-green-700 hover:from-green-700 hover:to-green-800 text-white px-8 py-3 rounded-xl font-semibold transition-all duration-300 flex items-center justify-center shadow-lg hover:shadow-xl transform hover:-translate-y-0.5">
                <div class="absolute inset-0 bg-gradient-to-r from-white/0 via-white/10 to-white/0 opacity-0 group-hover:opacity-100 transition-opacity duration-300 rounded-xl"></div>
                <i class="fas fa-check mr-2 relative z-10"></i>
                <span class="relative z-10 font-medium">Okay</span>
            </button>
        </div>
    </div>
</div>

<div id="createEditModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-[60] flex items-center justify-center p-4" style="backdrop-filter: blur(2px);">
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

                    <!-- Text Formatting Toolbar -->
                    <div class="mb-3 p-2 bg-gray-50 border border-gray-200 rounded-lg">
                        <div class="flex flex-wrap gap-1">
                            <button type="button" onclick="formatText('bold')" title="Bold (Ctrl+B)" class="format-btn bg-white hover:bg-gray-100 border border-gray-300 px-3 py-1 rounded text-sm font-medium transition-colors">
                                <i class="fas fa-bold"></i>
                            </button>
                            <button type="button" onclick="formatText('italic')" title="Italic (Ctrl+I)" class="format-btn bg-white hover:bg-gray-100 border border-gray-300 px-3 py-1 rounded text-sm font-medium transition-colors">
                                <i class="fas fa-italic"></i>
                            </button>
                            <button type="button" onclick="formatText('underline')" title="Underline (Ctrl+U)" class="format-btn bg-white hover:bg-gray-100 border border-gray-300 px-3 py-1 rounded text-sm font-medium transition-colors">
                                <i class="fas fa-underline"></i>
                            </button>
                            <div class="w-px h-6 bg-gray-300 mx-1"></div>
                            <button type="button" onclick="formatText('h1')" title="Heading 1" class="format-btn bg-white hover:bg-gray-100 border border-gray-300 px-3 py-1 rounded text-sm font-medium transition-colors">
                                H1
                            </button>
                            <button type="button" onclick="formatText('h2')" title="Heading 2" class="format-btn bg-white hover:bg-gray-100 border border-gray-300 px-3 py-1 rounded text-sm font-medium transition-colors">
                                H2
                            </button>
                            <button type="button" onclick="formatText('h3')" title="Heading 3" class="format-btn bg-white hover:bg-gray-100 border border-gray-300 px-3 py-1 rounded text-sm font-medium transition-colors">
                                H3
                            </button>
                            <button type="button" onclick="formatText('p')" title="Paragraph" class="format-btn bg-white hover:bg-gray-100 border border-gray-300 px-3 py-1 rounded text-sm font-medium transition-colors">
                                P
                            </button>
                            <div class="w-px h-6 bg-gray-300 mx-1"></div>
                            <button type="button" onclick="formatText('ul')" title="Bullet List" class="format-btn bg-white hover:bg-gray-100 border border-gray-300 px-3 py-1 rounded text-sm font-medium transition-colors">
                                <i class="fas fa-list-ul"></i>
                            </button>
                            <button type="button" onclick="formatText('ol')" title="Numbered List" class="format-btn bg-white hover:bg-gray-100 border border-gray-300 px-3 py-1 rounded text-sm font-medium transition-colors">
                                <i class="fas fa-list-ol"></i>
                            </button>
                            <button type="button" onclick="formatText('blockquote')" title="Blockquote" class="format-btn bg-white hover:bg-gray-100 border border-gray-300 px-3 py-1 rounded text-sm font-medium transition-colors">
                                <i class="fas fa-quote-right"></i>
                            </button>
                            <button type="button" onclick="formatText('link')" title="Link" class="format-btn bg-white hover:bg-gray-100 border border-gray-300 px-3 py-1 rounded text-sm font-medium transition-colors">
                                <i class="fas fa-link"></i>
                            </button>
                            <div class="w-px h-6 bg-gray-300 mx-1"></div>
                            <button type="button" onclick="formatText('undo')" title="Undo" class="format-btn bg-white hover:bg-gray-100 border border-gray-300 px-3 py-1 rounded text-sm font-medium transition-colors">
                                <i class="fas fa-undo"></i>
                            </button>
                            <button type="button" onclick="formatText('redo')" title="Redo" class="format-btn bg-white hover:bg-gray-100 border border-gray-300 px-3 py-1 rounded text-sm font-medium transition-colors">
                                <i class="fas fa-redo"></i>
                            </button>
                        </div>
                    </div>

                    <!-- Content Editable Area -->
                    <div id="content" contenteditable="true"
                         class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-primary focus:border-primary min-h-[300px] prose prose-gray max-w-none"
                         style="line-height: 1.6; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;"
                         data-placeholder="Write your newsletter content here... You can use the formatting toolbar above or keyboard shortcuts."></div>

                    <!-- Hidden textarea for form submission -->
                    <textarea name="content" id="content-hidden" style="display: none;"></textarea>

                    <p class="text-sm text-gray-500 mt-1">Use the formatting toolbar above or keyboard shortcuts for styling. Available: Bold (Ctrl+B), Italic (Ctrl+I), Underline (Ctrl+U), Headings (Ctrl+1/2/3)</p>
                </div>

                <div class="flex flex-col sm:flex-row gap-4 justify-end">
                    <button type="button" onclick="submitNewsletterForm('draft')" id="saveDraftBtn" class="bg-gray-500 hover:bg-gray-600 text-white px-6 py-2 rounded-lg">
                        <i class="fas fa-save mr-2"></i>Save as Draft
                    </button>

                    <button type="button" onclick="submitNewsletterForm('send_now')" id="sendNowBtn"
                            class="bg-green-500 hover:bg-green-600 text-white px-6 py-2 rounded-lg"
                            <?php echo $subscriber_count == 0 ? 'disabled' : ''; ?>>
                        <i class="fas fa-paper-plane mr-2"></i>Send Now
                    </button>

                    <button type="button" onclick="previewCurrentNewsletter()" id="previewBtn"
                            class="bg-blue-500 hover:bg-blue-600 text-white px-6 py-2 rounded-lg">
                        <i class="fas fa-eye mr-2"></i>Review
                    </button>

                    <button type="button" onclick="closeCreateEditModal()" class="bg-red-500 hover:bg-red-600 text-white px-6 py-2 rounded-lg">
                        Close
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<div id="previewModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-[70] flex items-center justify-center p-4" style="backdrop-filter: blur(2px);">
    <div class="bg-white rounded-2xl max-w-5xl w-full max-h-[90vh] overflow-hidden shadow-2xl relative animate__animated animate__zoomIn flex flex-col">
        <!-- Enhanced Modal Header -->
        <div class="bg-gradient-to-r from-blue-600 to-blue-700 p-6 text-white border-b border-blue-500 flex-shrink-0">
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-3">
                    <div class="w-10 h-10 bg-white/20 rounded-full flex items-center justify-center">
                        <i class="fas fa-eye text-lg"></i>
                    </div>
                    <div>
                        <h3 class="text-xl font-bold">Newsletter Review</h3>
                        <p class="text-sm opacity-90">Review how your newsletter will appear to subscribers</p>
                        <p class="text-xs opacity-75 mt-1" id="selectedTemplateInfo">Template: Default</p>
                    </div>
                </div>
                <div class="flex items-center space-x-2">
                    <button onclick="closePreviewModal()"
                            class="w-8 h-8 bg-white/20 hover:bg-white/30 rounded-full flex items-center justify-center transition-colors">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </div>
        </div>

        <!-- Preview Content Area -->
        <div class="bg-gray-100 p-4 overflow-y-auto flex-1" style="max-height: calc(90vh - 140px);">
            <div class="bg-white rounded-lg shadow-lg border border-gray-200 overflow-hidden">
                <!-- Email Header Simulation -->
                <div class="bg-gray-50 px-4 py-3 border-b border-gray-200 flex items-center justify-between">
                    <div class="flex items-center space-x-2">
                        <div class="w-6 h-6 bg-blue-600 rounded-full flex items-center justify-center">
                            <i class="fas fa-envelope text-white text-xs"></i>
                        </div>
                        <span class="text-sm font-medium text-gray-700">Email Review</span>
                        <span id="currentViewMode" class="text-xs bg-blue-100 text-blue-800 px-2 py-1 rounded-full">Desktop</span>
                    </div>
                    <div class="text-xs text-gray-500">
                        <i class="fas fa-clock mr-1"></i>
                        <span id="previewTimestamp">Just now</span>
                    </div>
                </div>

                <!-- Email Content Container -->
                <div class="relative">
                    <!-- Desktop View -->
                    <div id="desktopPreview" class="p-6">
                        <div id="previewContent" class="prose prose-lg max-w-none"></div>
                    </div>
                </div>

                <!-- Email Footer Simulation -->
                <div class="bg-gray-50 px-4 py-3 border-t border-gray-200">
                    <div class="text-xs text-gray-500 text-center">
                        <p>This is how your newsletter will appear in email clients.</p>
                        <p class="mt-1">Review generated on <span id="previewDate"><?php echo date('M j, Y \a\t g:i A'); ?></span></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal Footer with Actions - Sticky -->
        <div class="bg-gray-50 px-6 py-4 border-t border-gray-200 flex-shrink-0 flex items-center justify-center">
            <div class="flex items-center space-x-3">
                <button onclick="closePreviewModal()"
                        class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg font-medium transition-colors">
                    Close Review
                </button>
            </div>
        </div>
    </div>
</div>

<style>
/* Text Formatting Toolbar Styles */
.format-btn {
    transition: all 0.2s ease;
}

.format-btn:hover {
    transform: translateY(-1px);
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.format-btn:active {
    transform: translateY(0);
}

.format-btn:focus {
    outline: none;
    box-shadow: 0 0 0 2px rgba(193, 39, 45, 0.5);
}

/* Content Editable Styles */
#content {
    outline: none;
    padding: 12px;
    min-height: 300px;
    line-height: 1.6;
}

#content:empty::before {
    content: attr(data-placeholder);
    color: #9ca3af;
    pointer-events: none;
}

#content:focus {
    border-color: #C1272D;
    box-shadow: 0 0 0 3px rgba(193, 39, 45, 0.1);
}

#content p {
    margin-bottom: 1rem;
}

#content h1 {
    font-size: 2em;
    font-weight: 600;
    color: #C1272D;
    margin: 1.5rem 0 0.5rem 0;
    line-height: 1.2;
}

#content h2 {
    font-size: 1.5em;
    font-weight: 600;
    color: #C1272D;
    margin: 1.25rem 0 0.5rem 0;
    line-height: 1.3;
}

#content h3 {
    font-size: 1.25em;
    font-weight: 600;
    color: #C1272D;
    margin: 1rem 0 0.5rem 0;
    line-height: 1.4;
}

#content strong {
    font-weight: 600;
}

#content em {
    font-style: italic;
}

#content u {
    text-decoration: underline;
}

#content blockquote {
    border-left: 4px solid #C1272D;
    padding: 10px 15px;
    margin: 15px 0;
    background: #f8f9fa;
    font-style: italic;
}

#content ul, #content ol {
    margin: 15px 0;
    padding-left: 25px;
}

#content li {
    margin-bottom: 5px;
}

#content a {
    color: #C1272D;
    text-decoration: none;
    font-weight: 500;
}

#content a:hover {
    text-decoration: underline;
}

/* Desktop preview styles */
#desktopPreview {
    display: block !important;
}

/* Format toolbar separator styling */
.format-separator {
    background-color: #d1d5db;
    margin: 0 4px;
}
</style>

<script>
        // Global function definitions (outside DOMContentLoaded)
        function openCreateModal() {
            const newsletterForm = document.getElementById('newsletterForm');
            const campaignIdInput = document.getElementById('campaign_id');
            const actionTypeInput = document.getElementById('action_type');
            const modalTitle = document.getElementById('modalTitle');
            const createEditModal = document.getElementById('createEditModal');
            const saveDraftBtn = document.getElementById('saveDraftBtn');
            const sendNowBtn = document.getElementById('sendNowBtn');

            if (!newsletterForm || !campaignIdInput || !actionTypeInput || !modalTitle || !createEditModal) {
                console.error('Modal elements not found');
                alert('Create modal not available. Please refresh the page.');
                return;
            }

            newsletterForm.reset();
            campaignIdInput.value = '';
            actionTypeInput.value = 'create_newsletter';
            modalTitle.textContent = 'Create New Newsletter';

            if (saveDraftBtn) {
                saveDraftBtn.innerHTML = '<i class="fas fa-save mr-2"></i>Save as Draft';
            }
            if (sendNowBtn) {
                sendNowBtn.innerHTML = '<i class="fas fa-paper-plane mr-2"></i>Send Now';
            }

            createEditModal.classList.remove('hidden');
            document.body.style.overflow = 'hidden';
        }

        function closeCreateEditModal() {
            const createEditModal = document.getElementById('createEditModal');
            const newsletterForm = document.getElementById('newsletterForm');
            if (createEditModal && newsletterForm) {
                createEditModal.classList.add('hidden');
                document.body.style.overflow = '';
                newsletterForm.querySelectorAll('.border-red-500').forEach(el => el.classList.remove('border-red-500'));
            }
        }

        function editNewsletter(campaignId) {
            openCreateModal();

            const modalTitle = document.getElementById('modalTitle');
            const actionTypeInput = document.getElementById('action_type');
            const campaignIdInput = document.getElementById('campaign_id');
            const saveDraftBtn = document.getElementById('saveDraftBtn');
            const sendNowBtn = document.getElementById('sendNowBtn');
            const subjectInput = document.getElementById('subject');
            const contentInput = document.getElementById('content');
            const templateIdSelect = document.getElementById('template_id');

            if (!modalTitle || !actionTypeInput || !campaignIdInput) {
                console.error('Modal elements not found for editing');
                alert('Edit functionality not available. Please refresh the page.');
                return;
            }

            modalTitle.textContent = 'Edit Newsletter';
            actionTypeInput.value = 'update_newsletter';
            campaignIdInput.value = campaignId;

            if (saveDraftBtn) {
                saveDraftBtn.innerHTML = '<i class="fas fa-save mr-2"></i>Update Draft';
            }
            if (sendNowBtn) {
                sendNowBtn.innerHTML = '<i class="fas fa-paper-plane mr-2"></i>Send Now';
            }

            // Load newsletter data
            fetch(`api/get_newsletter.php?id=${campaignId}`)
                .then(response => {
                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.success) {
                        if (subjectInput && contentInput && templateIdSelect) {
                            subjectInput.value = data.newsletter.subject;
                            contentInput.innerHTML = data.newsletter.content;
                            templateIdSelect.value = data.newsletter.template || '';
                            // Sync hidden textarea
                            syncContentToHidden();
                        }

                        if (sendNowBtn) {
                            if (<?php echo $subscriber_count; ?> === 0) {
                                sendNowBtn.disabled = true;
                                sendNowBtn.title = 'No active subscribers to send to.';
                            } else {
                                sendNowBtn.disabled = false;
                                sendNowBtn.title = '';
                            }
                        }
                    } else {
                        alert('Error loading newsletter data: ' + data.message);
                        closeCreateEditModal();
                    }
                })
                .catch(error => {
                    console.error('Fetch error:', error);
                    alert('Failed to load newsletter data.');
                    closeCreateEditModal();
                });
        }

        function submitNewsletterForm(action) {
            const subjectInput = document.getElementById('subject');
            const contentEditor = document.getElementById('content');
            const hiddenContent = document.getElementById('content-hidden');
            const campaignIdInput = document.getElementById('campaign_id');
            const actionTypeInput = document.getElementById('action_type');
            const newsletterForm = document.getElementById('newsletterForm');
            const saveDraftBtn = document.getElementById('saveDraftBtn');
            const sendNowBtn = document.getElementById('sendNowBtn');
            const templateIdSelect = document.getElementById('template_id');

            console.log('submitNewsletterForm called with action:', action);

            // Validate required fields
            const subjectValue = subjectInput ? subjectInput.value.trim() : '';
            const contentValue = hiddenContent ? hiddenContent.value.trim() : '';

            if (!subjectValue || !contentValue) {
                alert('Please fill in all required fields (Subject and Content).');
                return;
            }

            if (action === 'send_now') {
                if (<?php echo $subscriber_count; ?> === 0) {
                    showNewsletterSuccessModal('Cannot Send Newsletter', 'No active subscribers to send to. Please add subscribers first.');
                    return;
                }
                showSendNewsletterModal();
                return;
            }
            // No confirmation needed for draft saves - direct save

            console.log('Validation passed, proceeding with action:', action);

            // Show loading state based on action
            let loadingBtn = null;
            let originalText = '';
            let loadingText = '';

            if (action === 'draft' && saveDraftBtn) {
                loadingBtn = saveDraftBtn;
                originalText = saveDraftBtn.innerHTML;
                loadingText = '<i class="fas fa-spinner fa-spin mr-2"></i>Saving Draft...';
                console.log('Setting up draft loading state');
            }

            if (loadingBtn) {
                loadingBtn.innerHTML = loadingText;
                loadingBtn.disabled = true;
                console.log('Loading state set for button:', action);
            }

            try {
                console.log('Creating hidden action input');
                if (newsletterForm) {
                    const hiddenActionInput = document.createElement('input');
                    hiddenActionInput.type = 'hidden';
                    hiddenActionInput.name = 'action';
                    hiddenActionInput.value = action;
                    newsletterForm.appendChild(hiddenActionInput);
                }

                console.log('Setting action type:', campaignIdInput && campaignIdInput.value ? 'update_newsletter' : 'create_newsletter');
                if (actionTypeInput && campaignIdInput) {
                    actionTypeInput.name = campaignIdInput.value ? 'update_newsletter' : 'create_newsletter';
                }

                console.log('Form data:', {
                    subject: subjectInput.value,
                    content: hiddenContent ? hiddenContent.value : '',
                    template_id: templateIdSelect ? templateIdSelect.value : '',
                    action: action,
                    campaign_id: campaignIdInput ? campaignIdInput.value : '',
                    action_type: actionTypeInput ? actionTypeInput.name : ''
                });

                // Add success callback
                if (newsletterForm) {
                    newsletterForm.addEventListener('submit', function successHandler() {
                        newsletterForm.removeEventListener('submit', successHandler);
                        console.log('Form submitted successfully');
                        if (loadingBtn) {
                            loadingBtn.innerHTML = originalText;
                            loadingBtn.disabled = false;
                        }
                    });
                }

                console.log('Submitting form...');
                if (newsletterForm) {
                    newsletterForm.submit();
                }
            } catch (error) {
                console.error('Submit error:', error);
                alert('An error occurred while submitting the newsletter.');
                // Restore button state
                if (loadingBtn) {
                    loadingBtn.innerHTML = originalText;
                    loadingBtn.disabled = false;
                }
            }
        }

        function htmlspecialchars(str) {
            if (typeof str !== 'string') {
                return str;
            }
            const htmlEntities = {
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#39;'
            };
            return str.replace(/[&<>"']/g, function(match) {
                return htmlEntities[match];
            });
        }

        function replaceTemplatePlaceholders(template, subject, content) {
            // Simple string replacement approach
            const replacements = {
                '{{SUBJECT}}': htmlspecialchars(subject),
                '{{CONTENT}}': content,
                '{{UNSUBSCRIBE_URL}}': 'https://' + window.location.hostname + '/unsubscribe.php?token={{UNSUBSCRIBE_TOKEN}}',
                '{{WEBSITE_URL}}': 'https://' + window.location.hostname,
                '{{YEAR}}': new Date().getFullYear(),
                '{{UNSUBSCRIBE_TOKEN}}': 'preview_token'
            };

            let processedTemplate = template;
            for (const [placeholder, value] of Object.entries(replacements)) {
                processedTemplate = processedTemplate.replace(new RegExp(placeholder, 'g'), value);
            }

            // Return the full processed template for desktop view only
            return processedTemplate;
        }

        async function previewCurrentNewsletter() {
            const subjectInput = document.getElementById('subject');
            const contentEditor = document.getElementById('content');
            const hiddenContent = document.getElementById('content-hidden');
            const templateIdSelect = document.getElementById('template_id');
            const previewModal = document.getElementById('previewModal');
            const previewTimestamp = document.getElementById('previewTimestamp');
            const previewContent = document.getElementById('previewContent');

            const subject = subjectInput ? subjectInput.value || 'Newsletter Subject' : 'Newsletter Subject';
            const content = hiddenContent ? hiddenContent.value || '<p>Newsletter content will appear here...</p>' : '<p>Newsletter content will appear here...</p>';
            const templateId = templateIdSelect ? templateIdSelect.value : '';

            // Check if preview modal elements exist
            if (!previewContent) {
                console.error('Preview content elements not found');
                alert('Preview elements not available. Please refresh the page.');
                return;
            }

            try {
                // Fetch the template HTML based on selection
                let templateHtml = '';
                if (templateId && templateId !== '') {
                    console.log('Loading template:', templateId);
                    // Fetch template from API
                    const response = await fetch(`api/get_template.php?id=${templateId}`);
                    const data = await response.json();

                    if (data.success && data.template) {
                        templateHtml = data.template.html_template;
                        console.log('Loaded template:', data.template.name);

                        // Update template info display
                        const selectedTemplateInfo = document.getElementById('selectedTemplateInfo');
                        if (selectedTemplateInfo) {
                            selectedTemplateInfo.textContent = `Template: ${data.template.name}`;
                        }
                    } else {
                        // Fallback to default template
                        console.warn('Template not found, using default');
                        const defaultTemplate = `<!DOCTYPE html>
<html>
<head>
    <meta charset='utf-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1'>
    <title>{{SUBJECT}}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif; line-height: 1.6; color: #1f2937; background-color: #ffffff; }
        .desktop-container { max-width: 600px; margin: 0 auto; background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%); border-radius: 16px; box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04); overflow: hidden; }
        .desktop-header { background: linear-gradient(135deg, #C1272D 0%, #991b1b 100%); color: white; text-align: center; padding: 40px 30px; position: relative; }
        .desktop-header::before { content: ''; position: absolute; top: 0; left: 0; right: 0; bottom: 0; background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><circle cx="20" cy="20" r="2" fill="rgba(255,255,255,0.1)"/><circle cx="80" cy="40" r="1.5" fill="rgba(255,255,255,0.08)"/><circle cx="40" cy="80" r="1" fill="rgba(255,255,255,0.06)"/></svg>'); opacity: 0.3; }
        .desktop-logo { font-size: 28px; font-weight: 700; margin-bottom: 8px; text-shadow: 0 2px 4px rgba(0,0,0,0.1); position: relative; z-index: 1; }
        .desktop-tagline { font-size: 16px; opacity: 0.9; font-weight: 300; position: relative; z-index: 1; }
        .desktop-subject { font-size: 32px; font-weight: 600; margin: 20px 0 10px 0; text-shadow: 0 2px 4px rgba(0,0,0,0.1); position: relative; z-index: 1; }
        .desktop-content { padding: 40px 30px; background: white; font-size: 16px; line-height: 1.7; }
        .desktop-content h1, .desktop-content h2, .desktop-content h3 { color: #C1272D; margin: 30px 0 15px 0; font-weight: 600; }
        .desktop-content h1:first-child, .desktop-content h2:first-child, .desktop-content h3:first-child { margin-top: 0; }
        .desktop-content p { margin-bottom: 20px; color: #374151; }
        .desktop-content ul, .desktop-content ol { margin: 20px 0; padding-left: 25px; }
        .desktop-content li { margin-bottom: 8px; color: #374151; }
        .desktop-content blockquote { background: #f3f4f6; border-left: 4px solid #C1272D; padding: 20px 25px; margin: 25px 0; font-style: italic; border-radius: 0 8px 8px 0; }
        .desktop-content a { color: #C1272D; text-decoration: none; font-weight: 500; border-bottom: 1px solid transparent; transition: border-color 0.2s; }
        .desktop-content a:hover { border-bottom-color: #C1272D; }
        .desktop-footer { background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%); padding: 30px; text-align: center; border-top: 1px solid #e2e8f0; }
        .desktop-footer-content { font-size: 14px; color: #6b7280; line-height: 1.6; }
        .desktop-unsubscribe { margin-top: 20px; padding-top: 20px; border-top: 1px solid #d1d5db; }
        .desktop-social-links { margin: 20px 0; }
        .desktop-social-links a { display: inline-block; margin: 0 10px; color: #C1272D; text-decoration: none; font-size: 20px; }
        .email-client-info { background: #f3f4f6; border: 1px solid #d1d5db; border-radius: 8px; padding: 15px; margin: 20px 0; font-size: 13px; color: #6b7280; text-align: center; }
        .email-client-info strong { color: #374151; }
        .cta-button { display: inline-block; background: linear-gradient(135deg, #C1272D 0%, #991b1b 100%); color: white; text-decoration: none; padding: 12px 24px; border-radius: 8px; font-weight: 600; margin: 15px 0; box-shadow: 0 4px 6px -1px rgba(193, 39, 45, 0.3); transition: all 0.3s ease; }
        .cta-button:hover { transform: translateY(-2px); box-shadow: 0 8px 15px -3px rgba(193, 39, 45, 0.4); }
        .newsletter-image { max-width: 100%; height: auto; border-radius: 8px; margin: 15px 0; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1); }
        @media (max-width: 768px) { .desktop-container { margin: 0; border-radius: 0; box-shadow: none; } .desktop-header { padding: 30px 20px; } .desktop-logo { font-size: 24px; } .desktop-subject { font-size: 24px; } .desktop-content { padding: 25px 20px; font-size: 15px; } .desktop-footer { padding: 25px 20px; } }
    </style>
</head>
<body>
    <div class='desktop-container'>
        <div class='desktop-header'>
            <div class='desktop-logo'>🍽️ Addins Meals on Wheels</div>
            <div class='desktop-tagline'>Delicious Food Delivered Fresh</div>
            <h1 class='desktop-subject'>{{SUBJECT}}</h1>
        </div>
        <div class='desktop-content'>{{CONTENT}}</div>
        <div class='desktop-footer'>
            <div class='desktop-footer-content'>
                <p><strong>Thank you for choosing Addins Meals on Wheels!</strong></p>
                <p>We appreciate your business and look forward to serving you again.</p>
                <div class='desktop-social-links'>
                    <a href='#' title='Facebook'><i class='fab fa-facebook'></i></a>
                    <a href='#' title='Instagram'><i class='fab fa-instagram'></i></a>
                    <a href='#' title='Twitter'><i class='fab fa-twitter'></i></a>
                </div>
                <div class='desktop-unsubscribe'>
                    <p><a href='{{UNSUBSCRIBE_URL}}' style='color: #C1272D;'>Unsubscribe</a> | <a href='{{WEBSITE_URL}}'>Visit Website</a></p>
                    <p>&copy; {{YEAR}} Addins Meals on Wheels. All rights reserved.</p>
                    <p>📞 +254 112 855 900 | 📧 info@addinsmeals.com</p>
                </div>
            </div>
        </div>
    </div>
    <div class='email-client-info'>
        <p><strong>Newsletter Review Mode</strong></p>
        <p>This preview shows how your newsletter will appear in email clients like Gmail, Outlook, and mobile email apps.</p>
    </div>
</body>
</html>`;
                        templateHtml = defaultTemplate;

                        // Update template info display
                        const selectedTemplateInfo = document.getElementById('selectedTemplateInfo');
                        if (selectedTemplateInfo) {
                            selectedTemplateInfo.textContent = 'Template: Default (Fallback)';
                        }
                    }
                } else {
                    console.log('No template selected, using default');
                    const defaultTemplate = `<!DOCTYPE html>
<html>
<head>
    <meta charset='utf-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1'>
    <title>{{SUBJECT}}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif; line-height: 1.6; color: #1f2937; background-color: #ffffff; }
        .desktop-container { max-width: 600px; margin: 0 auto; background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%); border-radius: 16px; box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04); overflow: hidden; }
        .desktop-header { background: linear-gradient(135deg, #C1272D 0%, #991b1b 100%); color: white; text-align: center; padding: 40px 30px; position: relative; }
        .desktop-header::before { content: ''; position: absolute; top: 0; left: 0; right: 0; bottom: 0; background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><circle cx="20" cy="20" r="2" fill="rgba(255,255,255,0.1)"/><circle cx="80" cy="40" r="1.5" fill="rgba(255,255,255,0.08)"/><circle cx="40" cy="80" r="1" fill="rgba(255,255,255,0.06)"/></svg>'); opacity: 0.3; }
        .desktop-logo { font-size: 28px; font-weight: 700; margin-bottom: 8px; text-shadow: 0 2px 4px rgba(0,0,0,0.1); position: relative; z-index: 1; }
        .desktop-tagline { font-size: 16px; opacity: 0.9; font-weight: 300; position: relative; z-index: 1; }
        .desktop-subject { font-size: 32px; font-weight: 600; margin: 20px 0 10px 0; text-shadow: 0 2px 4px rgba(0,0,0,0.1); position: relative; z-index: 1; }
        .desktop-content { padding: 40px 30px; background: white; font-size: 16px; line-height: 1.7; }
        .desktop-content h1, .desktop-content h2, .desktop-content h3 { color: #C1272D; margin: 30px 0 15px 0; font-weight: 600; }
        .desktop-content h1:first-child, .desktop-content h2:first-child, .desktop-content h3:first-child { margin-top: 0; }
        .desktop-content p { margin-bottom: 20px; color: #374151; }
        .desktop-content ul, .desktop-content ol { margin: 20px 0; padding-left: 25px; }
        .desktop-content li { margin-bottom: 8px; color: #374151; }
        .desktop-content blockquote { background: #f3f4f6; border-left: 4px solid #C1272D; padding: 20px 25px; margin: 25px 0; font-style: italic; border-radius: 0 8px 8px 0; }
        .desktop-content a { color: #C1272D; text-decoration: none; font-weight: 500; border-bottom: 1px solid transparent; transition: border-color 0.2s; }
        .desktop-content a:hover { border-bottom-color: #C1272D; }
        .desktop-footer { background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%); padding: 30px; text-align: center; border-top: 1px solid #e2e8f0; }
        .desktop-footer-content { font-size: 14px; color: #6b7280; line-height: 1.6; }
        .desktop-unsubscribe { margin-top: 20px; padding-top: 20px; border-top: 1px solid #d1d5db; }
        .desktop-social-links { margin: 20px 0; }
        .desktop-social-links a { display: inline-block; margin: 0 10px; color: #C1272D; text-decoration: none; font-size: 20px; }
        .email-client-info { background: #f3f4f6; border: 1px solid #d1d5db; border-radius: 8px; padding: 15px; margin: 20px 0; font-size: 13px; color: #6b7280; text-align: center; }
        .email-client-info strong { color: #374151; }
        .cta-button { display: inline-block; background: linear-gradient(135deg, #C1272D 0%, #991b1b 100%); color: white; text-decoration: none; padding: 12px 24px; border-radius: 8px; font-weight: 600; margin: 15px 0; box-shadow: 0 4px 6px -1px rgba(193, 39, 45, 0.3); transition: all 0.3s ease; }
        .cta-button:hover { transform: translateY(-2px); box-shadow: 0 8px 15px -3px rgba(193, 39, 45, 0.4); }
        .newsletter-image { max-width: 100%; height: auto; border-radius: 8px; margin: 15px 0; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1); }
        @media (max-width: 768px) { .desktop-container { margin: 0; border-radius: 0; box-shadow: none; } .desktop-header { padding: 30px 20px; } .desktop-logo { font-size: 24px; } .desktop-subject { font-size: 24px; } .desktop-content { padding: 25px 20px; font-size: 15px; } .desktop-footer { padding: 25px 20px; } }
    </style>
</head>
<body>
    <div class='desktop-container'>
        <div class='desktop-header'>
            <div class='desktop-logo'>🍽️ Addins Meals on Wheels</div>
            <div class='desktop-tagline'>Delicious Food Delivered Fresh</div>
            <h1 class='desktop-subject'>{{SUBJECT}}</h1>
        </div>
        <div class='desktop-content'>{{CONTENT}}</div>
        <div class='desktop-footer'>
            <div class='desktop-footer-content'>
                <p><strong>Thank you for choosing Addins Meals on Wheels!</strong></p>
                <p>We appreciate your business and look forward to serving you again.</p>
                <div class='desktop-social-links'>
                    <a href='#' title='Facebook'><i class='fab fa-facebook'></i></a>
                    <a href='#' title='Instagram'><i class='fab fa-instagram'></i></a>
                    <a href='#' title='Twitter'><i class='fab fa-twitter'></i></a>
                </div>
                <div class='desktop-unsubscribe'>
                    <p><a href='{{UNSUBSCRIBE_URL}}' style='color: #C1272D;'>Unsubscribe</a> | <a href='{{WEBSITE_URL}}'>Visit Website</a></p>
                    <p>&copy; {{YEAR}} Addins Meals on Wheels. All rights reserved.</p>
                    <p>📞 +254 112 855 900 | 📧 info@addinsmeals.com</p>
                </div>
            </div>
        </div>
    </div>
    <div class='email-client-info'>
        <p><strong>Newsletter Review Mode</strong></p>
        <p>This preview shows how your newsletter will appear in email clients like Gmail, Outlook, and mobile email apps.</p>
    </div>
</body>
</html>`;
                    templateHtml = defaultTemplate;

                    // Update template info display
                    const selectedTemplateInfo = document.getElementById('selectedTemplateInfo');
                    if (selectedTemplateInfo) {
                        selectedTemplateInfo.textContent = 'Template: Default';
                    }
                }

                // Replace placeholders in the template
                const previewHtml = replaceTemplatePlaceholders(templateHtml, subject, content);

                if (previewTimestamp) {
                    previewTimestamp.textContent = new Date().toLocaleTimeString();
                }

                if (!previewModal) {
                    console.error('Preview modal element not found');
                    alert('Preview modal not available. Please refresh the page.');
                    return;
                }

                // Update preview content
                previewContent.innerHTML = previewHtml;

                previewModal.classList.remove('hidden');
                document.body.style.overflow = 'hidden';

            } catch (error) {
                console.error('Error loading template:', error);
                // Fallback to default template from API
                try {
                    const fallbackResponse = await fetch('api/get_template.php');
                    const fallbackData = await fallbackResponse.json();

                    if (fallbackData.success && fallbackData.template) {
                        const templateHtml = fallbackData.template.html_template;
                        const previewHtml = replaceTemplatePlaceholders(templateHtml, subject, content);

                        previewContent.innerHTML = previewHtml;

                        // Update template info display for fallback
                        const selectedTemplateInfo = document.getElementById('selectedTemplateInfo');
                        if (selectedTemplateInfo) {
                            selectedTemplateInfo.textContent = `Template: ${fallbackData.template.name} (Fallback)`;
                        }

                        previewModal.classList.remove('hidden');
                        document.body.style.overflow = 'hidden';
                    }
                } catch (fallbackError) {
                    console.error('Fallback template loading failed:', fallbackError);
                    alert('Unable to load template. Please refresh the page.');
                }
            }
        }

        function previewNewsletter(campaignId) {
            fetch('preview_newsletter.php?id=' + campaignId)
                .then(response => {
                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }
                    return response.text();
                })
                .then(html => {
                    // Check if preview elements exist before using them
                    const previewTimestamp = document.getElementById('previewTimestamp');
                    const previewContent = document.getElementById('previewContent');
                    const currentViewMode = document.getElementById('currentViewMode');
                    const previewModal = document.getElementById('previewModal');

                    if (!previewContent) {
                        console.error('Preview content elements not found');
                        alert('Preview elements not available. Please refresh the page.');
                        return;
                    }

                    if (previewTimestamp) {
                        previewTimestamp.textContent = new Date().toLocaleTimeString();
                    }

                    // For fetched newsletter, use the full HTML for desktop view only
                    const parser = new DOMParser();
                    const doc = parser.parseFromString(html, 'text/html');
                    const desktopContainer = doc.querySelector('.desktop-container');

                    if (desktopContainer) {
                        previewContent.innerHTML = desktopContainer.outerHTML;
                    } else {
                        previewContent.innerHTML = html;
                    }

                    if (currentViewMode) {
                        currentViewMode.textContent = 'Desktop';
                    }

                    if (!previewModal) {
                        console.error('Preview modal element not found');
                        alert('Preview modal not available. Please refresh the page.');
                        return;
                    }

                    previewModal.classList.remove('hidden');
                    document.body.style.overflow = 'hidden';
                })
                .catch(error => {
                    alert('Error loading preview: ' + error.message);
                    console.error('Preview load error:', error);
                });
        }

        function closePreviewModal() {
            const previewModal = document.getElementById('previewModal');
            if (previewModal) {
                previewModal.classList.add('hidden');
                document.body.style.overflow = '';
            }
        }

        function showSendNewsletterModal() {
            const confirmSubject = document.getElementById('confirmSubject');
            const confirmRecipients = document.getElementById('confirmRecipients');
            const confirmRecipients2 = document.getElementById('confirmRecipients2');
            const sendNewsletterModal = document.getElementById('sendNewsletterModal');
            const subjectInput = document.getElementById('subject');

            if (!sendNewsletterModal || !confirmSubject || !confirmRecipients || !confirmRecipients2) {
                console.error('Send newsletter modal elements not found');
                alert('Send newsletter modal not available. Please refresh the page.');
                return;
            }

            confirmSubject.textContent = subjectInput ? subjectInput.value || 'Newsletter Subject' : 'Newsletter Subject';
            confirmRecipients.textContent = '<?php echo number_format($subscriber_count); ?> subscribers';
            confirmRecipients2.textContent = '<?php echo number_format($subscriber_count); ?>';

            sendNewsletterModal.classList.remove('hidden');
            sendNewsletterModal.classList.add('animate__fadeIn', 'animate__zoomIn');
        }

        function closeSendNewsletterModal() {
            const sendNewsletterModal = document.getElementById('sendNewsletterModal');
            if (sendNewsletterModal) {
                sendNewsletterModal.classList.add('hidden');
                sendNewsletterModal.classList.remove('animate__fadeIn', 'animate__zoomIn');
            }
        }

        function confirmSendNewsletter() {
            closeSendNewsletterModal();

            const sendNowBtn = document.getElementById('sendNowBtn');
            const newsletterForm = document.getElementById('newsletterForm');
            const actionTypeInput = document.getElementById('action_type');
            const campaignIdInput = document.getElementById('campaign_id');

            if (!sendNowBtn) {
                console.error('Send Now button not found');
                return;
            }

            const originalText = sendNowBtn.innerHTML;
            sendNowBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> Sending...';
            sendNowBtn.disabled = true;

            if (newsletterForm) {
                const hiddenActionInput = document.createElement('input');
                hiddenActionInput.type = 'hidden';
                hiddenActionInput.name = 'action';
                hiddenActionInput.value = 'send_now';
                newsletterForm.appendChild(hiddenActionInput);
            }

            if (actionTypeInput && campaignIdInput) {
                actionTypeInput.name = campaignIdInput.value ? 'update_newsletter' : 'create_newsletter';
            }

            if (newsletterForm) {
                newsletterForm.submit();
            }
        }

        function showNewsletterSuccessModal(title, message) {
            const newsletterSuccessTitle = document.getElementById('newsletterSuccessTitle');
            const newsletterSuccessMessage = document.getElementById('newsletterSuccessMessage');
            const modal = document.getElementById('newsletterSuccessModal');

            if (!newsletterSuccessTitle || !newsletterSuccessMessage || !modal) {
                console.error('Newsletter success modal elements not found');
                alert('Success modal not available. Please refresh the page.');
                return;
            }

            newsletterSuccessTitle.textContent = title;
            newsletterSuccessMessage.textContent = message;

            const header = modal.querySelector('.bg-gradient-to-r');
            const icon = modal.querySelector('.fas');
            const button = modal.querySelector('button');

            if (title.toLowerCase().includes('error') || title.toLowerCase().includes('cannot') || title.toLowerCase().includes('failed')) {
                header.className = 'bg-gradient-to-r from-red-600 to-red-700 p-6 text-white rounded-t-2xl';
                icon.className = 'fas fa-exclamation-triangle text-2xl text-red-600';
                button.className = 'group relative bg-gradient-to-r from-red-600 to-red-700 hover:from-red-700 hover:to-red-800 text-white px-8 py-3 rounded-xl font-semibold transition-all duration-300 flex items-center justify-center shadow-lg hover:shadow-xl transform hover:-translate-y-0.5';
                button.innerHTML = '<div class="absolute inset-0 bg-gradient-to-r from-white/0 via-white/10 to-white/0 opacity-0 group-hover:opacity-100 transition-opacity duration-300 rounded-xl"></div><i class="fas fa-times mr-2 relative z-10"></i><span class="relative z-10 font-medium">Close</span>';
            } else {
                header.className = 'bg-gradient-to-r from-green-600 to-green-700 p-6 text-white rounded-t-2xl';
                icon.className = 'fas fa-check-circle text-2xl text-green-600';
                button.className = 'group relative bg-gradient-to-r from-green-600 to-green-700 hover:from-green-700 hover:to-green-800 text-white px-8 py-3 rounded-xl font-semibold transition-all duration-300 flex items-center justify-center shadow-lg hover:shadow-xl transform hover:-translate-y-0.5';
                button.innerHTML = '<div class="absolute inset-0 bg-gradient-to-r from-white/0 via-white/10 to-white/0 opacity-0 group-hover:opacity-100 transition-opacity duration-300 rounded-xl"></div><i class="fas fa-check mr-2 relative z-10"></i><span class="relative z-10 font-medium">Okay</span>';
            }

            modal.classList.remove('hidden');
            modal.classList.add('animate__fadeIn', 'animate__zoomIn');
        }

        function closeNewsletterSuccessModal() {
            const newsletterSuccessModal = document.getElementById('newsletterSuccessModal');
            if (newsletterSuccessModal) {
                newsletterSuccessModal.classList.add('hidden');
                newsletterSuccessModal.classList.remove('animate__fadeIn', 'animate__zoomIn');
            }
        }

        function formatText(formatType) {
            const contentInput = document.getElementById('content');
            if (!contentInput) {
                console.error('Content editor not found');
                return;
            }

            // Ensure we have a selection or insert at cursor
            const selection = window.getSelection();
            let selectedText = '';

            if (selection.rangeCount > 0) {
                selectedText = selection.toString();
            }

            let formattedText = '';
            let cursorOffset = 0;

            switch(formatType) {
                case 'bold':
                    formattedText = `<strong>${selectedText || 'bold text'}</strong>`;
                    cursorOffset = selectedText ? `</strong>`.length : `bold text</strong>`.length - `bold text`.length;
                    break;
                case 'italic':
                    formattedText = `<em>${selectedText || 'italic text'}</em>`;
                    cursorOffset = selectedText ? `</em>`.length : `italic text</em>`.length - `italic text`.length;
                    break;
                case 'underline':
                    formattedText = `<u>${selectedText || 'underlined text'}</u>`;
                    cursorOffset = selectedText ? `</u>`.length : `underlined text</u>`.length - `underlined text`.length;
                    break;
                case 'h1':
                    formattedText = `<h1>${selectedText || 'Heading 1'}</h1>`;
                    cursorOffset = selectedText ? `</h1>`.length : `Heading 1</h1>`.length - `Heading 1`.length;
                    break;
                case 'h2':
                    formattedText = `<h2>${selectedText || 'Heading 2'}</h2>`;
                    cursorOffset = selectedText ? `</h2>`.length : `Heading 2</h2>`.length - `Heading 2`.length;
                    break;
                case 'h3':
                    formattedText = `<h3>${selectedText || 'Heading 3'}</h3>`;
                    cursorOffset = selectedText ? `</h3>`.length : `Heading 3</h3>`.length - `Heading 3`.length;
                    break;
                case 'p':
                    formattedText = `<p>${selectedText || 'Paragraph text'}</p>`;
                    cursorOffset = selectedText ? `</p>`.length : `Paragraph text</p>`.length - `Paragraph text`.length;
                    break;
                case 'ul':
                    formattedText = `<ul>\n    <li>${selectedText || 'List item 1'}</li>\n    <li>List item 2</li>\n</ul>`;
                    cursorOffset = selectedText ? `</ul>`.length : `<ul>\n    <li>List item 1</li>\n    <li>List item 2</li>\n</ul>`.length - `<ul>\n    <li>List item 1</li>\n    <li>List item 2</li>\n`.length;
                    break;
                case 'ol':
                    formattedText = `<ol>\n    <li>${selectedText || 'List item 1'}</li>\n    <li>List item 2</li>\n</ol>`;
                    cursorOffset = selectedText ? `</ol>`.length : `<ol>\n    <li>List item 1</li>\n    <li>List item 2</li>\n</ol>`.length - `<ol>\n    <li>List item 1</li>\n    <li>List item 2</li>\n`.length;
                    break;
                case 'link':
                    const url = prompt('Enter the URL:', selectedText || 'https://');
                    if (url) {
                        const linkText = selectedText || 'Link Text';
                        formattedText = `<a href="${url}" style="color: #C1272D; text-decoration: none; font-weight: 500;">${linkText}</a>`;
                        cursorOffset = selectedText ? `</a>`.length : `${linkText}</a>`.length - linkText.length;
                    } else {
                        return;
                    }
                    break;
                case 'blockquote':
                    formattedText = `<blockquote style="border-left: 4px solid #C1272D; padding: 10px 15px; margin: 15px 0; background: #f8f9fa; font-style: italic;">\n    ${selectedText || 'Blockquote text'}\n</blockquote>`;
                    cursorOffset = selectedText ? `</blockquote>`.length : `<blockquote style="border-left: 4px solid #C1272D; padding: 10px 15px; margin: 15px 0; background: #f8f9fa; font-style: italic;">\n    Blockquote text\n</blockquote>`.length - `<blockquote style="border-left: 4px solid #C1272D; padding: 10px 15px; margin: 15px 0; background: #f8f9fa; font-style: italic;">\n    Blockquote text\n`.length;
                    break;
                case 'undo':
                    document.execCommand('undo');
                    return;
                case 'redo':
                    document.execCommand('redo');
                    return;
                default:
                    return;
            }

            // Insert formatted text at cursor position or replace selection
            if (selectedText) {
                // Replace selected text
                const range = selection.getRangeAt(0);
                range.deleteContents();
                const tempDiv = document.createElement('div');
                tempDiv.innerHTML = formattedText;
                const fragment = document.createDocumentFragment();
                while (tempDiv.firstChild) {
                    fragment.appendChild(tempDiv.firstChild);
                }
                range.insertNode(fragment);
                range.setStartAfter(fragment.lastChild);
                range.setEndAfter(fragment.lastChild);
                selection.removeAllRanges();
                selection.addRange(range);
            } else {
                // Insert at cursor position
                const range = selection.getRangeAt(0);
                const tempDiv = document.createElement('div');
                tempDiv.innerHTML = formattedText;
                const fragment = document.createDocumentFragment();
                while (tempDiv.firstChild) {
                    fragment.appendChild(tempDiv.firstChild);
                }
                range.insertNode(fragment);
                range.setStartAfter(fragment.lastChild);
                range.setEndAfter(fragment.lastChild);
                selection.removeAllRanges();
                selection.addRange(range);
            }

            // Sync hidden textarea
            syncContentToHidden();
        }

        function syncContentToHidden() {
            const contentDiv = document.getElementById('content');
            const hiddenTextarea = document.getElementById('content-hidden');

            if (contentDiv && hiddenTextarea) {
                hiddenTextarea.value = contentDiv.innerHTML;
            }
        }

        function syncHiddenToContent() {
            const contentDiv = document.getElementById('content');
            const hiddenTextarea = document.getElementById('content-hidden');

            if (contentDiv && hiddenTextarea) {
                contentDiv.innerHTML = hiddenTextarea.value || '';
            }
        }

    document.addEventListener('DOMContentLoaded', function() {
        // Initialize all modal elements
        const createEditModal = document.getElementById('createEditModal');
        const previewModal = document.getElementById('previewModal');
        const newsletterForm = document.getElementById('newsletterForm');
        const modalTitle = document.getElementById('modalTitle');
        const campaignIdInput = document.getElementById('campaign_id');
        const subjectInput = document.getElementById('subject');
        const contentInput = document.getElementById('content');
        const templateIdSelect = document.getElementById('template_id');
        const actionTypeInput = document.getElementById('action_type');
        const sendNowBtn = document.getElementById('sendNowBtn');
        const saveDraftBtn = document.getElementById('saveDraftBtn');

        if (!createEditModal || !newsletterForm || !subjectInput || !contentInput) {
            console.error('Newsletter modal elements not found. Please check HTML structure.');
            return;
        }

        // Keep modal hidden by default - only show when user clicks button
        // createEditModal.classList.remove('hidden');
        // document.body.style.overflow = 'hidden';

        campaignIdInput.value = '';
        actionTypeInput.value = 'create_newsletter';
        modalTitle.textContent = 'Create New Newsletter';

        saveDraftBtn.innerHTML = '<i class="fas fa-save mr-2"></i>Save as Draft';
        sendNowBtn.innerHTML = '<i class="fas fa-paper-plane mr-2"></i>Send Now';

        createEditModal.addEventListener('click', function(e) {
            if (e.target === this) closeCreateEditModal();
        });

        if (previewModal) {
            previewModal.addEventListener('click', function(e) {
                if (e.target === this) closePreviewModal();
            });
        }

        const sendNewsletterModal = document.getElementById('sendNewsletterModal');
        if (sendNewsletterModal) {
            sendNewsletterModal.addEventListener('click', function(e) {
                if (e.target === this) closeSendNewsletterModal();
            });
        }

        const newsletterSuccessModal = document.getElementById('newsletterSuccessModal');
        if (newsletterSuccessModal) {
            newsletterSuccessModal.addEventListener('click', function(e) {
                if (e.target === this) closeNewsletterSuccessModal();
            });
        }

        <?php if (isset($_SESSION['message'])): ?>
            showNewsletterSuccessModal(
                '<?php echo $_SESSION['message']['type'] === 'success' ? 'Success!' : 'Notice'; ?>',
                '<?php echo htmlspecialchars($_SESSION['message']['text']); ?>'
            );
            <?php unset($_SESSION['message']); ?>
        <?php endif; ?>

        // Add event listeners for modal action buttons
        saveDraftBtn.addEventListener('click', function(e) {
            console.log('Save as Draft button clicked!');
            e.preventDefault();
            submitNewsletterForm('draft');
        });

        const previewBtn = document.getElementById('previewBtn');
        if (previewBtn) {
            previewBtn.addEventListener('click', function(e) {
                e.preventDefault();
                previewCurrentNewsletter();
            });
        }

        // Add event listeners for Create, Review, and Edit buttons
        document.addEventListener('click', function(e) {
            // Handle Create Newsletter button
            if (e.target.closest('#createNewsletterBtn')) {
                e.preventDefault();
                openCreateModal();
            }

            // Handle Review button
            const reviewBtn = e.target.closest('[onclick^=\"previewNewsletter(\"]');
            if (reviewBtn) {
                e.preventDefault();
                const onclickAttr = reviewBtn.getAttribute('onclick');
                if (onclickAttr) {
                    const match = onclickAttr.match(/\\(([^)]+)\\)/);
                    if (match && match[1]) {
                        const campaignId = match[1];
                        previewNewsletter(campaignId);
                    } else {
                        console.error('Could not extract campaign ID from onclick attribute:', onclickAttr);
                    }
                }
            }

            // Handle Edit button
            const editBtn = e.target.closest('[onclick^=\"editNewsletter(\"]');
            if (editBtn) {
                e.preventDefault();
                const onclickAttr = editBtn.getAttribute('onclick');
                if (onclickAttr) {
                    const match = onclickAttr.match(/\\(([^)]+)\\)/);
                    if (match && match[1]) {
                        const campaignId = match[1];
                        editNewsletter(campaignId);
                    } else {
                        console.error('Could not extract campaign ID from onclick attribute:', onclickAttr);
                    }
                }
            }
        });

        // Add keyboard shortcuts
        document.addEventListener('keydown', function(e) {
            // Only apply shortcuts when content editor is focused
            const contentInput = document.getElementById('content');
            if (e.target !== contentInput && !contentInput.contains(e.target)) return;

            // Ctrl+B for bold
            if (e.ctrlKey && e.key === 'b') {
                e.preventDefault();
                formatText('bold');
            }
            // Ctrl+I for italic
            else if (e.ctrlKey && e.key === 'i') {
                e.preventDefault();
                formatText('italic');
            }
            // Ctrl+U for underline
            else if (e.ctrlKey && e.key === 'u') {
                e.preventDefault();
                formatText('underline');
            }
            // Ctrl+1 for H1
            else if (e.ctrlKey && e.key === '1') {
                e.preventDefault();
                formatText('h1');
            }
            // Ctrl+2 for H2
            else if (e.ctrlKey && e.key === '2') {
                e.preventDefault();
                formatText('h2');
            }
            // Ctrl+3 for H3
            else if (e.ctrlKey && e.key === '3') {
                e.preventDefault();
                formatText('h3');
            }
            // Ctrl+K for link
            else if (e.ctrlKey && e.key === 'k') {
                e.preventDefault();
                formatText('link');
            }
            // Ctrl+Shift+8 for bullet list
            else if (e.ctrlKey && e.shiftKey && e.key === '8') {
                e.preventDefault();
                formatText('ul');
            }
            // Ctrl+Shift+7 for numbered list
            else if (e.ctrlKey && e.shiftKey && e.key === '7') {
                e.preventDefault();
                formatText('ol');
            }
            // Ctrl+Shift+B for blockquote
            else if (e.ctrlKey && e.shiftKey && e.key === 'B') {
                e.preventDefault();
                formatText('blockquote');
            }
            // Ctrl+Shift+P for paragraph
            else if (e.ctrlKey && e.shiftKey && e.key === 'P') {
                e.preventDefault();
                formatText('p');
            }
        });

        const contentEditor = document.getElementById('content');
        const hiddenTextarea = document.getElementById('content-hidden');

        if (contentEditor && hiddenTextarea) {
            // Sync content on input
            contentEditor.addEventListener('input', function() {
                syncContentToHidden();
            });

            // Sync content on paste
            contentEditor.addEventListener('paste', function() {
                setTimeout(syncContentToHidden, 10);
            });

            // Initialize content from hidden textarea if editing
            syncHiddenToContent();
        }

        // Update form submission to sync content before submitting
        if (newsletterForm) {
            newsletterForm.addEventListener('submit', function() {
                syncContentToHidden();
            });
        }
    });
</script>

<?php
// Include footer
require_once 'includes/footer.php';
?>

<!-- Close HTML structure as required by header.php -->
</div>
</div>
</div>
</body>
</html>