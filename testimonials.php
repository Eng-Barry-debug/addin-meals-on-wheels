<?php
$page_title = "Share Your Experience - Addins Meals on Wheels";
include 'includes/header.php';

// Handle form submission
$success = false;
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $customer_name = trim($_POST['customer_name'] ?? '');
        $customer_email = trim($_POST['customer_email'] ?? '');
        $customer_title = trim($_POST['customer_title'] ?? '');
        $rating = (int)($_POST['rating'] ?? 0);
        $review_text = trim($_POST['review_text'] ?? '');
        $service_type = trim($_POST['service_type'] ?? '');
        $catering_event_type = trim($_POST['catering_event_type'] ?? '');
        $occasion_details = trim($_POST['occasion_details'] ?? '');

        // Basic validation
        if (empty($customer_name) || empty($customer_email) || empty($rating) || empty($review_text) || empty($service_type)) {
            throw new Exception('Please fill in all required fields.');
        }

        if ($rating < 1 || $rating > 5) {
            throw new Exception('Please provide a rating between 1 and 5 stars.');
        }

        if (!filter_var($customer_email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception('Please enter a valid email address.');
        }

        // Validate catering fields if catering is selected
        if ($service_type === 'Catering') {
            if (empty($catering_event_type)) {
                throw new Exception('Please select an event type for catering.');
            }
        }

        // Insert review (pending approval)
        $stmt = $pdo->prepare("
            INSERT INTO customer_reviews (customer_name, customer_email, customer_title, rating, review_text, service_type, catering_event_type, occasion_details, review_date, status)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, CURDATE(), 'pending')
        ");

        $stmt->execute([$customer_name, $customer_email, $customer_title, $rating, $review_text, $service_type, $catering_event_type, $occasion_details]);

        $success = true;
        $_POST = []; // Clear form

    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}
?>

<!-- Hero Section -->
<section class="relative h-96 overflow-hidden">
    <div class="absolute inset-0 bg-cover bg-center" style="background-image: url('assets/img/Addinkitchen.png');">
        <!-- Enhanced overlay for better text visibility -->
        <div class="absolute inset-0 bg-gradient-to-br from-black/70 via-black/60 to-black/70"></div>
        <div class="absolute inset-0 bg-gradient-to-t from-black/50 via-transparent to-black/40"></div>
    </div>

    <div class="relative z-10 h-full flex items-center justify-center">
        <div class="container mx-auto px-4 text-center text-white">
            <!-- Background overlay for text readability -->
            <div class="max-w-4xl mx-auto">
                <div class="relative">
                    <!-- Semi-transparent background for text -->
                    <div class="absolute inset-0 bg-black/40 backdrop-blur-sm rounded-2xl -mx-4 px-8 py-12"></div>

                    <!-- Hero Content -->
                    <div class="relative z-10">
                        <h1 class="text-4xl md:text-6xl font-bold mb-6" style="text-shadow: 3px 3px 8px rgba(0, 0, 0, 0.9), 0 0 15px rgba(0, 0, 0, 0.6);">Share Your Experience</h1>
                        <p class="text-xl md:text-2xl mb-8 max-w-3xl mx-auto" style="text-shadow: 2px 2px 6px rgba(0, 0, 0, 0.9);">Your feedback helps us serve you better and helps other customers make informed decisions.</p>

                        <!-- Review highlights -->
                        <div class="grid md:grid-cols-3 gap-6 mt-12 max-w-4xl mx-auto">
                            <div class="bg-white/20 backdrop-blur-sm rounded-lg p-4 border border-white/30">
                                <div class="text-2xl mb-2">
                                    <i class="fas fa-users text-blue-400" style="text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.8);"></i>
                                </div>
                                <h3 class="font-bold text-sm text-white mb-1" style="text-shadow: 1px 1px 3px rgba(0, 0, 0, 0.8);">Help Others</h3>
                                <p class="text-white/80 text-xs" style="text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.7);">Guide future customers</p>
                            </div>
                            <div class="bg-white/20 backdrop-blur-sm rounded-lg p-4 border border-white/30">
                                <div class="text-2xl mb-2">
                                    <i class="fas fa-heart text-red-400" style="text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.8);"></i>
                                </div>
                                <h3 class="font-bold text-sm text-white mb-1" style="text-shadow: 1px 1px 3px rgba(0, 0, 0, 0.8);">Build Trust</h3>
                                <p class="text-white/80 text-xs" style="text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.7);">Strengthen our community</p>
                            </div>
                            <div class="bg-white/20 backdrop-blur-sm rounded-lg p-4 border border-white/30">
                                <div class="text-2xl mb-2">
                                    <i class="fas fa-chart-line text-green-400" style="text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.8);"></i>
                                </div>
                                <h3 class="font-bold text-sm text-white mb-1" style="text-shadow: 1px 1px 3px rgba(0, 0, 0, 0.8);">Drive Growth</h3>
                                <p class="text-white/80 text-xs" style="text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.7);">Help us improve</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Review Form Section -->
<section class="py-16 bg-white">
    <div class="container mx-auto px-4">
        <div class="max-w-2xl mx-auto">
            <?php if ($success): ?>
                <div class="bg-green-50 border border-green-200 text-green-700 px-6 py-4 rounded-lg mb-8">
                    <div class="flex items-center">
                        <i class="fas fa-check-circle text-green-600 mr-3"></i>
                        <div>
                            <h3 class="font-semibold">Thank You!</h3>
                            <p>Your review has been submitted and is pending approval. We'll review it and publish it soon!</p>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="bg-red-50 border border-red-200 text-red-700 px-6 py-4 rounded-lg mb-8">
                    <div class="flex items-center">
                        <i class="fas fa-exclamation-circle text-red-600 mr-3"></i>
                        <div>
                            <h3 class="font-semibold">Error</h3>
                            <p><?php echo htmlspecialchars($error); ?></p>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <div class="bg-white rounded-xl shadow-lg p-8 border">
                <h2 class="text-2xl font-bold text-gray-800 mb-6">Write a Review</h2>

                <form method="POST" class="space-y-6">
                    <!-- Name -->
                    <div>
                        <label for="customer_name" class="block text-sm font-medium text-gray-700 mb-2">Full Name *</label>
                        <input type="text" id="customer_name" name="customer_name"
                               value="<?php echo htmlspecialchars($_POST['customer_name'] ?? ''); ?>"
                               required class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-primary focus:border-primary">
                    </div>

                    <!-- Email -->
                    <div>
                        <label for="customer_email" class="block text-sm font-medium text-gray-700 mb-2">Email Address *</label>
                        <input type="email" id="customer_email" name="customer_email"
                               value="<?php echo htmlspecialchars($_POST['customer_email'] ?? ''); ?>"
                               required class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-primary focus:border-primary">
                    </div>

                    <!-- Title/Position (Optional) -->
                    <div>
                        <label for="customer_title" class="block text-sm font-medium text-gray-700 mb-2">Title/Position <span class="text-gray-500">(Optional)</span></label>
                        <input type="text" id="customer_title" name="customer_title"
                               value="<?php echo htmlspecialchars($_POST['customer_title'] ?? ''); ?>"
                               placeholder="e.g., Event Coordinator, Wedding Planner, Private Event Host"
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-primary focus:border-primary">
                    </div>

                    <!-- Rating -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Rating *</label>
                        <div class="flex items-center space-x-1">
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                <button type="button"
                                        class="star-rating text-3xl transition-all duration-200 hover:scale-110 focus:outline-none focus:ring-2 focus:ring-primary/50 rounded"
                                        data-rating="<?php echo $i; ?>"
                                        onclick="setRating(<?php echo $i; ?>)">
                                    <i class="far fa-star text-gray-300 hover:text-yellow-300 transition-colors duration-200"
                                       id="star-<?php echo $i; ?>"></i>
                                </button>
                            <?php endfor; ?>
                            <input type="hidden" name="rating" id="rating-value" value="<?php echo $_POST['rating'] ?? ''; ?>" required>
                        </div>
                        <p class="text-xs text-gray-500 mt-1">Click on the stars to rate</p>
                    </div>

                    <!-- Service Type -->
                    <div>
                        <label for="service_type" class="block text-sm font-medium text-gray-700 mb-2">Service Used</label>
                        <select id="service_type" name="service_type" required
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-primary focus:border-primary"
                                onchange="toggleCateringFields()">
                            <option value="">Select a service</option>
                            <option value="Food Delivery" <?php echo ($_POST['service_type'] ?? '') === 'Food Delivery' ? 'selected' : ''; ?>>Food Delivery</option>
                            <option value="Catering" <?php echo ($_POST['service_type'] ?? '') === 'Catering' ? 'selected' : ''; ?>>Catering</option>
                            <option value="Pizza" <?php echo ($_POST['service_type'] ?? '') === 'Pizza' ? 'selected' : ''; ?>>Pizza</option>
                            <option value="Cookies & Cupcakes" <?php echo ($_POST['service_type'] ?? '') === 'Cookies & Cupcakes' ? 'selected' : ''; ?>>Cookies & Cupcakes</option>
                            <option value="General" <?php echo ($_POST['service_type'] ?? '') === 'General' ? 'selected' : ''; ?>>General</option>
                        </select>
                    </div>

                    <!-- Catering Event Type (Hidden by default, shows when Catering is selected) -->
                    <div id="catering_event_type_div" class="space-y-4" style="display: <?php echo ($_POST['service_type'] ?? '') === 'Catering' ? 'block' : 'none'; ?>;">
                        <div>
                            <label for="catering_event_type" class="block text-sm font-medium text-gray-700 mb-2">Event Type</label>
                            <select id="catering_event_type" name="catering_event_type"
                                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-primary focus:border-primary">
                                <option value="">Select event type</option>
                                <option value="Corporate Event" <?php echo ($_POST['catering_event_type'] ?? '') === 'Corporate Event' ? 'selected' : ''; ?>>Corporate Event</option>
                                <option value="Wedding Catering" <?php echo ($_POST['catering_event_type'] ?? '') === 'Wedding Catering' ? 'selected' : ''; ?>>Wedding Catering</option>
                                <option value="Family Events" <?php echo ($_POST['catering_event_type'] ?? '') === 'Family Events' ? 'selected' : ''; ?>>Family Events</option>
                            </select>
                        </div>

                        <div>
                            <label for="occasion_details" class="block text-sm font-medium text-gray-700 mb-2">Occasion Details</label>
                            <input type="text" id="occasion_details" name="occasion_details"
                                   value="<?php echo htmlspecialchars($_POST['occasion_details'] ?? ''); ?>"
                                   placeholder="e.g., 150 Guests, Annual Gala, Birthday Party, etc."
                                   class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-primary focus:border-primary">
                        </div>
                    </div>

                    <!-- Review Text -->
                    <div>
                        <label for="review_text" class="block text-sm font-medium text-gray-700 mb-2">Your Review *</label>
                        <textarea id="review_text" name="review_text" rows="5"
                                  required
                                  placeholder="Tell us about your experience with Addins Meals on Wheels..."
                                  class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-primary focus:border-primary"><?php echo htmlspecialchars($_POST['review_text'] ?? ''); ?></textarea>
                    </div>

                    <!-- Submit Button -->
                    <div>
                        <button type="submit" class="w-full bg-primary hover:bg-primary-dark text-white py-3 px-6 rounded-lg font-semibold transition-colors">
                            Submit Review
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</section>

<!-- Why Reviews Matter Section -->
<section class="py-16 bg-light">
    <div class="container mx-auto px-4">
        <div class="text-center mb-12">
            <h2 class="text-3xl font-bold mb-4">Why Your Review Matters</h2>
            <div class="w-20 h-1 bg-primary mx-auto"></div>
        </div>

        <div class="grid md:grid-cols-3 gap-8 max-w-4xl mx-auto">
            <div class="text-center p-6">
                <div class="bg-primary text-white w-16 h-16 rounded-full flex items-center justify-center text-2xl font-bold mx-auto mb-4">
                    <i class="fas fa-users"></i>
                </div>
                <h3 class="text-xl font-bold mb-2">Help Other Customers</h3>
                <p class="text-gray-600">Your experience helps others make informed decisions about our services.</p>
            </div>

            <div class="text-center p-6">
                <div class="bg-secondary text-white w-16 h-16 rounded-full flex items-center justify-center text-2xl font-bold mx-auto mb-4">
                    <i class="fas fa-thumbs-up"></i>
                </div>
                <h3 class="text-xl font-bold mb-2">Improve Our Service</h3>
                <p class="text-gray-600">Your feedback helps us understand what we're doing well and where we can improve.</p>
            </div>

            <div class="text-center p-6">
                <div class="bg-accent text-white w-16 h-16 rounded-full flex items-center justify-center text-2xl font-bold mx-auto mb-4">
                    <i class="fas fa-heart"></i>
                </div>
                <h3 class="text-xl font-bold mb-2">Build Community Trust</h3>
                <p class="text-gray-600">Authentic reviews build trust and help grow our community of satisfied customers.</p>
            </div>
        </div>
    </div>
</section>

<!-- CTA Section -->
<section class="py-16 bg-primary text-white">
    <div class="container mx-auto px-4 text-center">
        <h2 class="text-3xl font-bold mb-6">Ready to Try Our Delicious Meals?</h2>
        <p class="text-xl mb-8 max-w-2xl mx-auto">Join hundreds of satisfied customers enjoying our meals today!</p>
        <div class="flex flex-col sm:flex-row justify-center gap-4">
            <a href="/menu.php" class="bg-white text-primary hover:bg-gray-100 px-8 py-3 rounded-lg font-semibold transition-colors">
                View Menu
            </a>
            <a href="/contact.php" class="border-2 border-white text-white hover:bg-white hover:text-primary px-8 py-3 rounded-lg font-semibold transition-colors">
                Contact Us
            </a>
        </div>
    </div>
</section>

            </div>
        </div>
    </div>
</section>

<!-- JavaScript for form functionality -->
<script>
function toggleCateringFields() {
    const serviceType = document.getElementById('service_type').value;
    const cateringFields = document.getElementById('catering_event_type_div');

    if (serviceType === 'Catering') {
        cateringFields.style.display = 'block';
        // Make catering fields required when catering is selected
        document.getElementById('catering_event_type').required = true;
    } else {
        cateringFields.style.display = 'none';
        // Remove required attribute when catering is not selected
        document.getElementById('catering_event_type').required = false;
        // Clear catering fields when switching away from catering
        document.getElementById('catering_event_type').value = '';
        document.getElementById('occasion_details').value = '';
    }
}

// Star rating functionality
function setRating(rating) {
    // Update hidden input value
    document.getElementById('rating-value').value = rating;

    // Update star display
    for (let i = 1; i <= 5; i++) {
        const star = document.getElementById('star-' + i);
        if (i <= rating) {
            // Fill and glow selected stars
            star.className = 'fas fa-star text-yellow-400 drop-shadow-lg';
        } else {
            // Empty unselected stars
            star.className = 'far fa-star text-gray-300 hover:text-yellow-300 transition-colors duration-200';
        }
    }
}

// Initialize star rating on page load
document.addEventListener('DOMContentLoaded', function() {
    toggleCateringFields(); // Set initial state based on pre-selected value

    // Initialize star rating display if there's a pre-selected value
    const initialRating = document.getElementById('rating-value').value;
    if (initialRating) {
        setRating(parseInt(initialRating));
    }
});
</script>

<?php include 'includes/footer.php'; ?>
