<?php
// help.php - Ambassador Help & Support

// Start session and check ambassador authentication
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include database connection and functions
require_once '../../includes/config.php';
require_once 'includes/functions.php';

// Check if user is logged in and is ambassador
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'ambassador') {
    header('Location: ../../auth/login.php');
    exit();
}

// Set page title
$page_title = 'Ambassador Help & Support';

// Initialize variables
$user_id = $_SESSION['user_id'];
$faq_categories = [
    'getting_started' => 'Getting Started as Ambassador',
    'referrals' => 'Referral Program',
    'commissions' => 'Commissions & Earnings',
    'content' => 'Content Creation',
    'account' => 'Account Management',
    'troubleshooting' => 'Troubleshooting'
];

$faqs = [
    'getting_started' => [
        [
            'question' => 'How do I become a successful ambassador?',
            'answer' => '1. Complete your profile with accurate information<br>2. Share your unique referral code with friends and family<br>3. Create engaging content about our restaurant experiences<br>4. Be active on social media and engage with your audience<br>5. Track your referrals and performance regularly'
        ],
        [
            'question' => 'What are the ambassador requirements?',
            'answer' => '• Must be 18+ years old<br>• Have a valid ID document<br>• Be active on social media<br>• Share genuine experiences about our restaurants<br>• Follow our brand guidelines and terms of service<br>• Maintain good standing with no violations'
        ],
        [
            'question' => 'How do I get my referral code?',
            'answer' => 'Your unique referral code is automatically generated when your ambassador application is approved. You can find it on your dashboard and in your profile settings. Share this code with others to earn commissions.'
        ]
    ],
    'referrals' => [
        [
            'question' => 'How does the referral program work?',
            'answer' => 'When someone uses your referral code to place an order and completes their purchase, you earn a commission. The more successful referrals you generate, the more you earn. Track all your referrals in your dashboard.'
        ],
        [
            'question' => 'How do I share my referral code?',
            'answer' => 'Share your code through: • Social media posts<br>• Personal messages<br>• Email signatures<br>• Word of mouth<br>• QR codes<br>• Link sharing<br>Always disclose that you\'re an ambassador when sharing.'
        ],
        [
            'question' => 'Can I refer myself or create fake accounts?',
            'answer' => 'No, self-referrals and fake accounts violate our terms of service and will result in account suspension. All referrals must be genuine customers who make real purchases.'
        ]
    ],
    'commissions' => [
        [
            'question' => 'How are commissions calculated?',
            'answer' => 'You earn a 15% commission on all successful referrals. For example, if your referral spends KES 1,000, you earn KES 150. Commissions are calculated automatically when orders are completed and delivered.'
        ],
        [
            'question' => 'When do I receive my commissions?',
            'answer' => 'Commissions are processed monthly on the 15th of each month for all successful referrals from the previous month. You\'ll receive an email notification when payment is processed to your registered payment method.'
        ],
        [
            'question' => 'How can I track my earnings?',
            'answer' => 'Monitor your earnings in the Reports section of your dashboard. View detailed breakdowns by month, referral source, and individual transactions. Download statements for your records.'
        ]
    ],
    'content' => [
        [
            'question' => 'What type of content should I create?',
            'answer' => 'Focus on authentic experiences: restaurant reviews, food photos, behind-the-scenes content, special offers, and honest feedback. Always tag our restaurant and use relevant hashtags. Content should be original and engaging.'
        ],
        [
            'question' => 'Do I need to disclose that I\'m an ambassador?',
            'answer' => 'Yes, always disclose your ambassador status when posting sponsored content. Use #Ad, #Ambassador, or #Sponsored in your posts. Transparency builds trust with your audience.'
        ],
        [
            'question' => 'Can I use restaurant photos and logos?',
            'answer' => 'Yes, you can use our approved photos and logos in your content. However, always follow our brand guidelines and don\'t modify logos or use them in ways that could damage our brand reputation.'
        ]
    ],
    'account' => [
        [
            'question' => 'How do I update my profile information?',
            'answer' => 'Go to Settings > Profile Information. You can update your name, contact details, bio, and social media information. Keep your profile current to maintain good ambassador standing.'
        ],
        [
            'question' => 'How do I change my notification preferences?',
            'answer' => 'Visit Settings > Notification Preferences. Choose how you want to receive updates about new referrals, commission payments, and ambassador announcements via email, SMS, or push notifications.'
        ],
        [
            'question' => 'I forgot my password. How do I reset it?',
            'answer' => 'Click "Forgot Password" on the login page or contact our ambassador support team. We\'ll send a password reset link to your registered email address. Never share your password with anyone.'
        ]
    ],
    'troubleshooting' => [
        [
            'question' => 'My referral code isn\'t working',
            'answer' => 'Check: 1) Code is entered exactly as shown, 2) Customer is a new user, 3) No spaces or typos. If issues persist, contact support with the specific code and error message received.'
        ],
        [
            'question' => 'I\'m not receiving referral notifications',
            'answer' => 'Verify your notification settings in your profile. Also check your email spam folder and ensure your contact information is current. Test notifications by making a small referral if possible.'
        ],
        [
            'question' => 'My commissions aren\'t showing up',
            'answer' => 'Commissions appear after order completion and delivery confirmation, which can take 24-48 hours. Check your Reports section for detailed status. If still missing after 3 days, contact support.'
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
        $activityLogger->log('ambassador', 'support_ticket', 'Ambassador created support ticket: ' . $subject, 'user', $user_id);

    } catch (PDOException $e) {
        $ticket_message = 'Error submitting ticket: ' . $e->getMessage();
    }
}

// Include activity logger
require_once '../../includes/ActivityLogger.php';
$activityLogger = new ActivityLogger($pdo);

// Log help page access
$activityLogger->log('ambassador', 'help_view', 'Ambassador accessed help page', 'user', $user_id);

// Include header
require_once 'includes/header.php';
?>

<!-- Help Header -->
<div class="bg-gradient-to-br from-purple-600 via-purple-700 to-pink-600 text-white">
    <div class="px-6 py-12">
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between">
            <div>
                <h1 class="text-4xl md:text-5xl font-bold mb-3">Help & Support</h1>
                <p class="text-xl opacity-90">Get help with your ambassador journey</p>
            </div>
            <div class="mt-6 lg:mt-0">
                <div class="flex items-center space-x-2 bg-white/10 backdrop-blur-sm rounded-lg px-3 py-2">
                    <i class="fas fa-question-circle text-yellow-300"></i>
                    <span class="text-sm font-medium">24/7 Ambassador Support</span>
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
            <h3 class="font-bold text-gray-900 mb-2">Call Ambassador Support</h3>
            <p class="text-gray-600 text-sm mb-3">Speak directly with our ambassador team</p>
            <p class="font-semibold text-red-600">+254 700 123 456</p>
        </a>

        <a href="mailto:ambassadors@addinmeals.com" class="bg-white rounded-xl shadow-lg p-6 hover:shadow-xl transition-shadow duration-200 text-center group">
            <div class="w-16 h-16 bg-blue-100 rounded-full flex items-center justify-center mx-auto mb-4 group-hover:bg-blue-200 transition-colors">
                <i class="fas fa-envelope text-2xl text-blue-600"></i>
            </div>
            <h3 class="font-bold text-gray-900 mb-2">Email Ambassador Support</h3>
            <p class="text-gray-600 text-sm mb-3">Get help via email within 24 hours</p>
            <p class="font-semibold text-blue-600">ambassadors@addinmeals.com</p>
        </a>

        <div onclick="document.getElementById('contact-form').scrollIntoView({behavior: 'smooth'})"
             class="bg-white rounded-xl shadow-lg p-6 hover:shadow-xl transition-shadow duration-200 text-center group cursor-pointer">
            <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4 group-hover:bg-green-200 transition-colors">
                <i class="fas fa-ticket-alt text-2xl text-green-600"></i>
            </div>
            <h3 class="font-bold text-gray-900 mb-2">Submit Support Ticket</h3>
            <p class="text-gray-600 text-sm mb-3">Get detailed help for complex issues</p>
            <p class="font-semibold text-green-600">Get Help Below ↓</p>
        </div>
    </div>

    <!-- Ambassador Resources -->
    <div class="bg-white rounded-xl shadow-lg p-6 mb-8">
        <h2 class="text-2xl font-bold text-gray-900 mb-6 flex items-center">
            <i class="fas fa-star mr-3 text-purple-600"></i>
            Ambassador Resources
        </h2>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
            <div class="bg-purple-50 rounded-lg p-4 text-center border border-purple-200">
                <i class="fas fa-gift text-2xl text-purple-600 mb-2"></i>
                <h4 class="font-semibold text-purple-800 mb-1">Commission Rates</h4>
                <p class="text-sm text-purple-600">15% on all referrals</p>
            </div>

            <div class="bg-green-50 rounded-lg p-4 text-center border border-green-200">
                <i class="fas fa-calendar-check text-2xl text-green-600 mb-2"></i>
                <h4 class="font-semibold text-green-800 mb-1">Monthly Payout</h4>
                <p class="text-sm text-green-600">15th of each month</p>
            </div>

            <div class="bg-blue-50 rounded-lg p-4 text-center border border-blue-200">
                <i class="fas fa-users text-2xl text-blue-600 mb-2"></i>
                <h4 class="font-semibold text-blue-800 mb-1">Min. Referrals</h4>
                <p class="text-sm text-blue-600">No minimum required</p>
            </div>

            <div class="bg-yellow-50 rounded-lg p-4 text-center border border-yellow-200">
                <i class="fas fa-infinity text-2xl text-yellow-600 mb-2"></i>
                <h4 class="font-semibold text-yellow-800 mb-1">Duration</h4>
                <p class="text-sm text-yellow-600">Ongoing partnership</p>
            </div>
        </div>
    </div>

    <!-- FAQ Section -->
    <div class="bg-white rounded-xl shadow-lg p-6 mb-8">
        <h2 class="text-2xl font-bold text-gray-900 mb-6 flex items-center">
            <i class="fas fa-question-circle mr-3 text-purple-600"></i>
            Frequently Asked Questions
        </h2>

        <div class="space-y-6">
            <?php foreach ($faq_categories as $category_key => $category_name): ?>
                <div>
                    <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center">
                        <i class="fas fa-folder mr-2 text-purple-600"></i>
                        <?php echo htmlspecialchars($category_name); ?>
                    </h3>

                    <div class="space-y-4">
                        <?php foreach ($faqs[$category_key] as $faq): ?>
                            <div class="border border-gray-200 rounded-lg p-4 hover:border-purple-200 transition-colors">
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
            <i class="fas fa-headset mr-3 text-purple-600"></i>
            Contact Ambassador Support
        </h2>

        <form method="POST" class="space-y-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label for="ticket_subject" class="block text-sm font-semibold text-gray-700 mb-2">Subject</label>
                    <input type="text" name="ticket_subject" id="ticket_subject"
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                           placeholder="Brief description of your ambassador issue" required>
                </div>

                <div>
                    <label for="ticket_priority" class="block text-sm font-semibold text-gray-700 mb-2">Priority</label>
                    <select name="ticket_priority" id="ticket_priority"
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                        <option value="low">Low - General ambassador question</option>
                        <option value="medium" selected>Medium - Referral or commission issue</option>
                        <option value="high">High - Account or payment problem</option>
                        <option value="critical">Critical - Ambassador program issue</option>
                    </select>
                </div>
            </div>

            <div>
                <label for="ticket_message" class="block text-sm font-semibold text-gray-700 mb-2">Message</label>
                <textarea name="ticket_message" id="ticket_message" rows="6"
                          class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                          placeholder="Please describe your ambassador issue in detail. Include your referral code, relevant dates, and what you expected to happen." required></textarea>
            </div>

            <div class="bg-purple-50 rounded-lg p-4">
                <h4 class="font-semibold text-purple-800 mb-2">Before submitting, check:</h4>
                <ul class="text-sm text-purple-700 space-y-1">
                    <li>• Have you checked the FAQ section above?</li>
                    <li>• Is your referral code active and correct?</li>
                    <li>• Have you waited 24-48 hours for commission processing?</li>
                    <li>• Include your referral code and relevant order numbers</li>
                </ul>
            </div>

            <div class="flex items-center justify-between">
                <p class="text-sm text-gray-600">
                    Response time: <span class="font-semibold">Within 24 hours</span>
                </p>
                <button type="submit" name="submit_ticket"
                        class="bg-purple-600 hover:bg-purple-700 text-white px-6 py-3 rounded-lg font-semibold transition-colors duration-200 flex items-center">
                    <i class="fas fa-paper-plane mr-2"></i>
                    Submit Ambassador Ticket
                </button>
            </div>
        </form>
    </div>

    <!-- Success Tips -->
    <div class="mt-8">
        <div class="bg-gradient-to-r from-green-50 to-emerald-50 rounded-xl p-6 border border-green-200">
            <h3 class="text-xl font-bold text-gray-900 mb-4 flex items-center">
                <i class="fas fa-lightbulb mr-3 text-green-600"></i>
                Ambassador Success Tips
            </h3>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <h4 class="font-semibold text-gray-800 mb-2">📈 Maximize Your Earnings</h4>
                    <ul class="text-sm text-gray-600 space-y-1">
                        <li>• Share your code regularly on social media</li>
                        <li>• Create engaging content about our restaurants</li>
                        <li>• Target food lovers and local communities</li>
                        <li>• Track your best-performing referral sources</li>
                    </ul>
                </div>

                <div>
                    <h4 class="font-semibold text-gray-800 mb-2">🤝 Build Strong Relationships</h4>
                    <ul class="text-sm text-gray-600 space-y-1">
                        <li>• Always disclose your ambassador status</li>
                        <li>• Provide genuine, honest reviews</li>
                        <li>• Engage with your audience authentically</li>
                        <li>• Follow our brand guidelines</li>
                    </ul>
                </div>
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
                        Ambassador Emergency Contact
                    </h3>
                    <p class="text-red-700 mb-1">For urgent ambassador program issues</p>
                    <p class="font-semibold text-red-800">Call: +254 700 123 456 (24/7 Ambassador Line)</p>
                </div>
                <a href="tel:+254700123456" class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg font-medium transition-colors">
                    Call Now
                </a>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
