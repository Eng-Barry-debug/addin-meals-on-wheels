<?php
// help.php - Delivery Personnel Help & Support

// Start session and check delivery authentication
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include database connection and functions
require_once '../../admin/includes/config.php';
require_once 'includes/functions.php';

// Check if user is logged in and is delivery personnel
if (!isset($_SESSION['user_id']) || ($_SESSION['user_role'] !== 'delivery' && $_SESSION['user_role'] !== 'driver')) {
    header('Location: ../../auth/login.php');
    exit();
}

// Set page title
$page_title = 'Help & Support';

// Initialize variables
$user_id = $_SESSION['user_id'];
$faq_categories = [
    'getting_started' => 'Getting Started',
    'deliveries' => 'Managing Deliveries',
    'earnings' => 'Earnings & Payments',
    'account' => 'Account & Settings',
    'troubleshooting' => 'Troubleshooting'
];

$faqs = [
    'getting_started' => [
        [
            'question' => 'How do I get started with deliveries?',
            'answer' => '1. Complete your profile in Settings<br>2. Set your availability status to "Available"<br>3. Check the dashboard for available deliveries<br>4. Accept deliveries that match your preferences<br>5. Follow the delivery instructions carefully'
        ],
        [
            'question' => 'What equipment do I need?',
            'answer' => 'You need a reliable vehicle (motorcycle, car, bicycle, or van) with valid insurance and registration. A smartphone with GPS and the delivery app is also required. Protective gear and weather-appropriate clothing are recommended.'
        ],
        [
            'question' => 'How do I update my vehicle information?',
            'answer' => 'Go to Settings > Vehicle Information. You can update your vehicle type, model, license plate, and capacity. This information helps us match you with appropriate deliveries.'
        ]
    ],
    'deliveries' => [
        [
            'question' => 'How do I accept a delivery?',
            'answer' => 'When you see an available delivery on your dashboard, click "Accept" to claim it. You\'ll receive customer details and delivery instructions. Make sure to confirm pickup and delivery times accurately.'
        ],
        [
            'question' => 'What should I do if I can\'t complete a delivery?',
            'answer' => 'Contact our support team immediately at support@addinmeals.com or call +254-700-123-456. Provide the order number and reason for delay. Never abandon a delivery without notification.'
        ],
        [
            'question' => 'How do I update delivery status?',
            'answer' => 'Use the delivery management page to update status: "Picked Up" when you collect the order, "Out for Delivery" when en route, and "Delivered" when completed. Always take photos as proof of delivery when required.'
        ]
    ],
    'earnings' => [
        [
            'question' => 'How are earnings calculated?',
            'answer' => 'You earn 10% commission on each delivered order. Earnings are calculated automatically and added to your account upon successful delivery confirmation. Track your earnings in the Earnings section.'
        ],
        [
            'question' => 'When do I get paid?',
            'answer' => 'Payments are processed weekly on Fridays for all completed deliveries from the previous week. You\'ll receive an email notification when payment is processed to your registered payment method.'
        ],
        [
            'question' => 'Can I view my earnings history?',
            'answer' => 'Yes, go to Earnings > History to view detailed breakdowns of all your deliveries, commissions earned, and payment status. You can also download statements for tax purposes.'
        ]
    ],
    'account' => [
        [
            'question' => 'How do I change my notification preferences?',
            'answer' => 'Go to Settings > Notification Preferences. You can choose to receive email, SMS, or push notifications for new deliveries, updates, and important announcements.'
        ],
        [
            'question' => 'How do I update my availability?',
            'answer' => 'In Settings > Availability Settings, you can set your status (Available/Busy/Offline) and working hours. Update this regularly to receive appropriate delivery assignments.'
        ],
        [
            'question' => 'I forgot my password. How do I reset it?',
            'answer' => 'Click "Forgot Password" on the login page or contact support. We\'ll send a password reset link to your registered email address. For security, never share your password with anyone.'
        ]
    ],
    'troubleshooting' => [
        [
            'question' => 'The app is not loading properly',
            'answer' => 'Try: 1) Refresh the page, 2) Clear browser cache, 3) Check internet connection, 4) Try a different browser. If issues persist, contact support with your browser and device information.'
        ],
        [
            'question' => 'I can\'t see available deliveries',
            'answer' => 'Ensure your availability status is set to "Available" in Settings. Also check that you\'re within your preferred delivery zones and working hours. If no deliveries appear, it may be due to high demand from other drivers.'
        ],
        [
            'question' => 'GPS/location not working accurately',
            'answer' => 'Enable location services in your device settings and browser permissions. Ensure you have a clear view of the sky for GPS accuracy. If problems persist, restart your device and check for app updates.'
        ]
    ]
];

// Handle support ticket submission
$ticket_message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_ticket'])) {
    $subject = sanitize($_POST['ticket_subject']);
    $message = sanitize($_POST['ticket_message']);
    $priority = sanitize($_POST['ticket_priority']);

    try {
        $stmt = $pdo->prepare("INSERT INTO support_tickets (user_id, subject, message, priority, status, created_at) VALUES (?, ?, ?, ?, 'open', NOW())");
        $stmt->execute([$user_id, $subject, $message, $priority]);

        $ticket_message = 'Support ticket submitted successfully! We\'ll respond within 24 hours.';

        // Log ticket creation
        require_once '../../includes/ActivityLogger.php';
        $activityLogger = new ActivityLogger($pdo);
        $activityLogger->log('delivery', 'support_ticket', 'Delivery person created support ticket: ' . $subject, 'user', $user_id);

    } catch (PDOException $e) {
        $ticket_message = 'Error submitting ticket: ' . $e->getMessage();
    }
}

// Include activity logger
require_once '../../includes/ActivityLogger.php';
$activityLogger = new ActivityLogger($pdo);

// Log help page access
$activityLogger->log('delivery', 'help_view', 'Delivery person accessed help page', 'user', $user_id);

// Include header
require_once 'includes/header.php';
?>

<!-- Help Header -->
<div class="bg-gradient-to-br from-primary via-primary-dark to-green-700 text-white">
    <div class="px-6 py-12">
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between">
            <div>
                <h1 class="text-4xl md:text-5xl font-bold mb-3">Help & Support</h1>
                <p class="text-xl opacity-90">Find answers and get help with your deliveries</p>
            </div>
            <div class="mt-6 lg:mt-0">
                <div class="flex items-center space-x-2 bg-white/10 backdrop-blur-sm rounded-lg px-3 py-2">
                    <i class="fas fa-question-circle text-yellow-300"></i>
                    <span class="text-sm font-medium">24/7 Support Available</span>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Main Help Content -->
<div class="px-6 py-8">
    <?php if ($ticket_message): ?>
        <div class="mb-6 p-4 bg-blue-50 border-l-4 border-blue-400 text-blue-700 rounded-lg flex items-center">
            <i class="fas fa-info-circle mr-3"></i>
            <span><?php echo htmlspecialchars($ticket_message); ?></span>
        </div>
    <?php endif; ?>

    <!-- Quick Help Options -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        <a href="tel:+254700123456" class="bg-white rounded-xl shadow-lg p-6 hover:shadow-xl transition-shadow duration-200 text-center group">
            <div class="w-16 h-16 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-4 group-hover:bg-red-200 transition-colors">
                <i class="fas fa-phone text-2xl text-red-600"></i>
            </div>
            <h3 class="font-bold text-gray-900 mb-2">Call Support</h3>
            <p class="text-gray-600 text-sm mb-3">Speak directly with our support team</p>
            <p class="font-semibold text-red-600">+254 700 123 456</p>
        </a>

        <a href="mailto:support@addinmeals.com" class="bg-white rounded-xl shadow-lg p-6 hover:shadow-xl transition-shadow duration-200 text-center group">
            <div class="w-16 h-16 bg-blue-100 rounded-full flex items-center justify-center mx-auto mb-4 group-hover:bg-blue-200 transition-colors">
                <i class="fas fa-envelope text-2xl text-blue-600"></i>
            </div>
            <h3 class="font-bold text-gray-900 mb-2">Email Support</h3>
            <p class="text-gray-600 text-sm mb-3">Get help via email within 24 hours</p>
            <p class="font-semibold text-blue-600">support@addinmeals.com</p>
        </a>

        <div onclick="document.getElementById('contact-form').scrollIntoView({behavior: 'smooth'})"
             class="bg-white rounded-xl shadow-lg p-6 hover:shadow-xl transition-shadow duration-200 text-center group cursor-pointer">
            <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4 group-hover:bg-green-200 transition-colors">
                <i class="fas fa-ticket-alt text-2xl text-green-600"></i>
            </div>
            <h3 class="font-bold text-gray-900 mb-2">Submit Ticket</h3>
            <p class="text-gray-600 text-sm mb-3">Create a support ticket for detailed help</p>
            <p class="font-semibold text-green-600">Get Help Below ↓</p>
        </div>
    </div>

    <!-- FAQ Section -->
    <div class="bg-white rounded-xl shadow-lg p-6 mb-8">
        <h2 class="text-2xl font-bold text-gray-900 mb-6 flex items-center">
            <i class="fas fa-question-circle mr-3 text-primary"></i>
            Frequently Asked Questions
        </h2>

        <div class="space-y-6">
            <?php foreach ($faq_categories as $category_key => $category_name): ?>
                <div>
                    <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center">
                        <i class="fas fa-folder mr-2 text-primary"></i>
                        <?php echo htmlspecialchars($category_name); ?>
                    </h3>

                    <div class="space-y-4">
                        <?php foreach ($faqs[$category_key] as $faq): ?>
                            <div class="border border-gray-200 rounded-lg p-4 hover:border-primary/50 transition-colors">
                                <h4 class="font-semibold text-gray-900 mb-2"><?php echo htmlspecialchars($faq['question']); ?></h4>
                                <div class="text-gray-600 text-sm leading-relaxed">
                                    <?php echo $faq['answer']; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Contact Form -->
    <div id="contact-form" class="bg-white rounded-xl shadow-lg p-6">
        <h2 class="text-2xl font-bold text-gray-900 mb-6 flex items-center">
            <i class="fas fa-headset mr-3 text-primary"></i>
            Contact Support
        </h2>

        <form method="POST" class="space-y-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label for="ticket_subject" class="block text-sm font-semibold text-gray-700 mb-2">Subject</label>
                    <input type="text" name="ticket_subject" id="ticket_subject"
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent"
                           placeholder="Brief description of your issue" required>
                </div>

                <div>
                    <label for="ticket_priority" class="block text-sm font-semibold text-gray-700 mb-2">Priority</label>
                    <select name="ticket_priority" id="ticket_priority"
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                        <option value="low">Low - General question</option>
                        <option value="medium" selected>Medium - Issue affecting work</option>
                        <option value="high">High - Urgent problem</option>
                        <option value="critical">Critical - Cannot work</option>
                    </select>
                </div>
            </div>

            <div>
                <label for="ticket_message" class="block text-sm font-semibold text-gray-700 mb-2">Message</label>
                <textarea name="ticket_message" id="ticket_message" rows="6"
                          class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent"
                          placeholder="Please describe your issue in detail. Include any error messages, steps to reproduce, and what you expected to happen." required></textarea>
            </div>

            <div class="bg-gray-50 rounded-lg p-4">
                <h4 class="font-semibold text-gray-800 mb-2">Before submitting, check:</h4>
                <ul class="text-sm text-gray-600 space-y-1">
                    <li>• Have you checked the FAQ section above?</li>
                    <li>• Is your app/browser up to date?</li>
                    <li>• Have you tried restarting the app/device?</li>
                    <li>• Include your order number if applicable</li>
                </ul>
            </div>

            <div class="flex items-center justify-between">
                <p class="text-sm text-gray-600">
                    Response time: <span class="font-semibold">Within 24 hours</span>
                </p>
                <button type="submit" name="submit_ticket"
                        class="bg-primary hover:bg-primary-dark text-white px-6 py-3 rounded-lg font-semibold transition-colors duration-200 flex items-center">
                    <i class="fas fa-paper-plane mr-2"></i>
                    Submit Ticket
                </button>
            </div>
        </form>
    </div>

    <!-- Additional Resources -->
    <div class="mt-8">
        <div class="bg-gradient-to-r from-gray-50 to-gray-100 rounded-xl p-6">
            <h3 class="text-xl font-bold text-gray-900 mb-4 flex items-center">
                <i class="fas fa-book mr-3 text-primary"></i>
                Additional Resources
            </h3>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                <a href="#" class="bg-white rounded-lg p-4 hover:shadow-md transition-shadow text-center">
                    <i class="fas fa-video text-2xl text-red-600 mb-2"></i>
                    <h4 class="font-semibold text-gray-800 mb-1">Video Tutorials</h4>
                    <p class="text-xs text-gray-600">Step-by-step guides</p>
                </a>

                <a href="#" class="bg-white rounded-lg p-4 hover:shadow-md transition-shadow text-center">
                    <i class="fas fa-file-pdf text-2xl text-blue-600 mb-2"></i>
                    <h4 class="font-semibold text-gray-800 mb-1">Delivery Handbook</h4>
                    <p class="text-xs text-gray-600">Complete guide</p>
                </a>

                <a href="#" class="bg-white rounded-lg p-4 hover:shadow-md transition-shadow text-center">
                    <i class="fas fa-users text-2xl text-green-600 mb-2"></i>
                    <h4 class="font-semibold text-gray-800 mb-1">Community Forum</h4>
                    <p class="text-xs text-gray-600">Connect with drivers</p>
                </a>

                <a href="#" class="bg-white rounded-lg p-4 hover:shadow-md transition-shadow text-center">
                    <i class="fas fa-graduation-cap text-2xl text-purple-600 mb-2"></i>
                    <h4 class="font-semibold text-gray-800 mb-1">Training Center</h4>
                    <p class="text-xs text-gray-600">Skill development</p>
                </a>
            </div>
        </div>
    </div>

    <!-- Emergency Contact -->
    <div class="mt-8">
        <div class="bg-red-50 border border-red-200 rounded-xl p-6">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-lg font-bold text-red-900 mb-2 flex items-center">
                        <i class="fas fa-exclamation-triangle mr-2 text-red-600"></i>
                        Emergency Contact
                    </h3>
                    <p class="text-red-700 mb-1">For urgent delivery issues or safety concerns</p>
                    <p class="font-semibold text-red-800">Call: +254 700 123 456 (24/7 Emergency Line)</p>
                </div>
                <a href="tel:+254700123456" class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg font-medium transition-colors">
                    Call Now
                </a>
            </div>
        </div>
    </div>
</div>

<script>
// Auto-expand FAQ sections
document.addEventListener('DOMContentLoaded', function() {
    const faqItems = document.querySelectorAll('.faq-item');

    faqItems.forEach(item => {
        const question = item.querySelector('.faq-question');
        const answer = item.querySelector('.faq-answer');

        question.addEventListener('click', function() {
            answer.classList.toggle('hidden');
            const icon = question.querySelector('i');
            icon.classList.toggle('fa-chevron-down');
            icon.classList.toggle('fa-chevron-up');
        });
    });
});
</script>

<?php require_once 'includes/footer.php'; ?>
