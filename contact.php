<?php
require_once 'includes/config.php';

// Initialize variables
$success = false;
$error = '';

// Check if we should show success message from redirect
if (isset($_GET['success']) && $_GET['success'] === '1') {
    $success = true;
}

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Validate input
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $message = trim($_POST['message'] ?? '');
        
        // Basic validation
        if (empty($name) || empty($email) || empty($message)) {
            throw new Exception('Please fill in all required fields.');
        }
        
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception('Please enter a valid email address.');
        }
        
        // Insert into database
        $stmt = $pdo->prepare("
            INSERT INTO feedback (name, email, message) 
            VALUES (:name, :email, :message)
        ");
        
        $stmt->execute([
            ':name' => $name,
            ':email' => $email,
            ':message' => $message
        ]);
        
        // Clear form on success
        $_POST = [];
        
        // Redirect to contact form with success message
        header('Location: contact.php?success=1#contact-form');
        exit;
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

$page_title = "Contact Us - Addins Meals on Wheels";
?>

<?php include 'includes/header.php'; ?>

<!-- Hero Section -->
<section class="relative h-96 overflow-hidden">
    <div class="absolute inset-0 bg-cover bg-center" style="background-image: url('assets/img/delivery.png');">
        <!-- Enhanced overlay for better text visibility -->
        <div class="absolute inset-0 bg-gradient-to-br from-black/90 via-black/80 to-black/85"></div>
        <div class="absolute inset-0 bg-gradient-to-t from-black/70 via-black/30 to-black/60"></div>
        <!-- Additional brand color overlay -->
        <div class="absolute inset-0 bg-primary/10"></div>
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
                        <h1 class="text-4xl md:text-6xl font-bold mb-6" style="text-shadow: 3px 3px 8px rgba(0, 0, 0, 0.9), 0 0 15px rgba(0, 0, 0, 0.6);">Contact Us</h1>
                        <p class="text-xl md:text-2xl mb-8 max-w-2xl mx-auto" style="text-shadow: 2px 2px 6px rgba(0, 0, 0, 0.9);">We'd love to hear from you</p>

                        <!-- Contact highlights -->
                        <div class="grid md:grid-cols-3 gap-6 mt-12 max-w-4xl mx-auto">
                            <div class="bg-white/20 backdrop-blur-sm rounded-lg p-4 border border-white/30">
                                <div class="text-2xl mb-2">
                                    <i class="fas fa-clock text-blue-400" style="text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.8);"></i>
                                </div>
                                <h3 class="font-bold text-sm text-white mb-1" style="text-shadow: 1px 1px 3px rgba(0, 0, 0, 0.8);">Quick Response</h3>
                                <p class="text-white/80 text-xs" style="text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.7);">We respond within 24 hours</p>
                            </div>
                            <div class="bg-white/20 backdrop-blur-sm rounded-lg p-4 border border-white/30">
                                <div class="text-2xl mb-2">
                                    <i class="fas fa-phone text-green-400" style="text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.8);"></i>
                                </div>
                                <h3 class="font-bold text-sm text-white mb-1" style="text-shadow: 1px 1px 3px rgba(0, 0, 0, 0.8);">Multiple Channels</h3>
                                <p class="text-white/80 text-xs" style="text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.7);">Phone, email, and chat support</p>
                            </div>
                            <div class="bg-white/20 backdrop-blur-sm rounded-lg p-4 border border-white/30">
                                <div class="text-2xl mb-2">
                                    <i class="fas fa-comments text-purple-400" style="text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.8);"></i>
                                </div>
                                <h3 class="font-bold text-sm text-white mb-1" style="text-shadow: 1px 1px 3px rgba(0, 0, 0, 0.8);">Live Chat</h3>
                                <p class="text-white/80 text-xs" style="text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.7);">Get instant help online</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Contact Section -->
<section class="py-16 bg-light">
    <div class="container mx-auto px-4">
        <div class="max-w-4xl mx-auto">
            <div class="bg-white rounded-lg shadow-lg overflow-hidden">
                <div class="md:flex">
                    <!-- Contact Form -->
                    <div class="w-full md:w-1/2 p-8" id="contact-form">
                        <h2 class="text-2xl font-bold mb-6">Send us a Message</h2>
                        
                        <?php if ($success): ?>
                            <div class="mb-6 p-4 bg-green-100 text-green-700 rounded-lg">
                                <p class="font-medium">Thank you for your message!</p>
                                <p>We've received your message and will get back to you as soon as possible.</p>
                            </div>
                        <?php elseif ($error): ?>
                            <div class="mb-6 p-4 bg-red-100 text-red-700 rounded-lg">
                                <p class="font-medium">Error:</p>
                                <p><?php echo htmlspecialchars($error); ?></p>
                            </div>
                        <?php endif; ?>
                        
                        <form method="POST" action="" class="space-y-6">
                            <div class="space-y-2">
                                <label for="name" class="block text-sm font-medium text-gray-700">Full Name <span class="text-red-500">*</span></label>
                                <input type="text" id="name" name="name" required 
                                       value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>"
                                       class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent">
                            </div>
                            
                            <div class="space-y-2">
                                <label for="email" class="block text-sm font-medium text-gray-700">Email <span class="text-red-500">*</span></label>
                                <input type="email" id="email" name="email" required
                                       value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>"
                                       class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent">
                            </div>
                            
                            <div class="space-y-2">
                                <label for="message" class="block text-sm font-medium text-gray-700">Message <span class="text-red-500">*</span></label>
                                <textarea id="message" name="message" rows="5" required
                                          class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent"><?php echo htmlspecialchars($_POST['message'] ?? ''); ?></textarea>
                            </div>
                            
                            <div class="pt-2">
                                <button type="submit" class="w-full bg-primary text-white py-3 px-6 rounded-md hover:bg-red-700 transition-colors font-medium">
                                    Send Message
                                </button>
                            </div>
                        </form>
                    </div>
                    
                    <!-- Contact Info -->
                    <div class="w-full md:w-1/2 bg-gray-50 p-8">
                        <h2 class="text-2xl font-bold mb-6">Contact Information</h2>
                        
                        <div class="space-y-6">
                            <div class="flex items-start">
                                <div class="flex-shrink-0 bg-primary/10 p-3 rounded-full text-primary">
                                    <i class="fas fa-truck text-xl"></i>
                                </div>
                                <div class="ml-4">
                                    <h3 class="font-medium text-gray-900">Service Area</h3>
                                    <p class="mt-1 text-gray-600">Online delivery service covering the entire city</p>
                                </div>
                            </div>
                            
                            <div class="flex items-start">
                                <div class="flex-shrink-0 bg-primary/10 p-3 rounded-full text-primary">
                                    <i class="fas fa-phone-alt text-xl"></i>
                                </div>
                                <div class="ml-4">
                                    <h3 class="font-medium text-gray-900">Phone Number</h3>
                                    <p class="mt-1 text-gray-600">(254) 112-855-900</p>
                                    <p class="text-sm text-gray-500">Mon-Fri: 9:00 AM - 6:00 PM</p>
                                </div>
                            </div>
                            
                            <div class="flex items-start">
                                <div class="flex-shrink-0 bg-primary/10 p-3 rounded-full text-primary">
                                    <i class="fas fa-envelope text-xl"></i>
                                </div>
                                <div class="ml-4">
                                    <h3 class="font-medium text-gray-900">Email Address</h3>
                                    <p class="mt-1 text-gray-600">info@addinsmeals.com</p>
                                    <p class="text-sm text-gray-500">We'll respond within 24 hours</p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mt-8">
                            <h3 class="text-lg font-medium text-gray-900 mb-4">Follow Us</h3>
                            <div class="flex space-x-4">
                                <a href="https://www.instagram.com/adinns_meals_on_wheels?igsh=MXBmdnBkNjBrYnJieQ==" target="_blank" rel="noopener noreferrer" class="text-gray-500 hover:text-accent transition-colors" title="Follow us on Instagram">
                                    <i class="fab fa-instagram text-2xl"></i>
                                    <span class="sr-only">Instagram</span>
                                </a>
                                <a href="https://www.facebook.com/share/17Y42uWZG4/" target="_blank" rel="noopener noreferrer" class="text-gray-500 hover:text-accent transition-colors" title="Like us on Facebook">
                                    <i class="fab fa-facebook-f text-2xl"></i>
                                    <span class="sr-only">Facebook</span>
                                </a>
                                <a href="https://www.tiktok.com/@adinns_meals_on_wheels?_t=ZM-8zyKYPHl740&_r=1" target="_blank" rel="noopener noreferrer" class="text-gray-500 hover:text-accent transition-colors" title="Follow us on TikTok">
                                    <i class="fab fa-tiktok text-2xl"></i>
                                    <span class="sr-only">TikTok</span>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

<!-- Service Area Section -->
<section class="py-16 bg-light">
    <div class="container mx-auto px-4">
        <div class="max-w-4xl mx-auto text-center">
            <h2 class="text-3xl font-bold mb-6">Our Service Area</h2>
            <p class="text-xl text-gray-600 mb-8">We deliver delicious meals across the entire city with fast and reliable service</p>

            <div class="grid md:grid-cols-3 gap-8">
                <div class="bg-white p-6 rounded-lg shadow-md">
                    <div class="w-16 h-16 bg-primary rounded-full flex items-center justify-center mx-auto mb-4">
                        <i class="fas fa-map-marker-alt text-white text-2xl"></i>
                    </div>
                    <h3 class="text-xl font-bold mb-2">City-Wide Coverage</h3>
                    <p class="text-gray-600">We deliver to all neighborhoods and districts across the city</p>
                </div>

                <div class="bg-white p-6 rounded-lg shadow-md">
                    <div class="w-16 h-16 bg-secondary rounded-full flex items-center justify-center mx-auto mb-4">
                        <i class="fas fa-clock text-white text-2xl"></i>
                    </div>
                    <h3 class="text-xl font-bold mb-2">Express Delivery</h3>
                    <p class="text-gray-600">Same-day delivery available for orders placed before 4 PM</p>
                </div>

                <div class="bg-white p-6 rounded-lg shadow-md">
                    <div class="w-16 h-16 bg-accent rounded-full flex items-center justify-center mx-auto mb-4">
                        <i class="fas fa-shield-alt text-white text-2xl"></i>
                    </div>
                    <h3 class="text-xl font-bold mb-2">Quality Guarantee</h3>
                    <p class="text-gray-600">Fresh ingredients and careful packaging ensure quality delivery</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- CTA Section -->
<section class="py-16 bg-primary text-white">
    <div class="container mx-auto px-4 text-center">
        <h2 class="text-3xl font-bold mb-6">Ready to place an order?</h2>
        <p class="text-xl mb-8 max-w-2xl mx-auto">Experience the taste of home-cooked meals delivered straight to your door.</p>
        <a href="/menu.php" class="inline-block bg-secondary text-white px-8 py-3 rounded-lg hover:bg-yellow-600 transition-colors font-medium">
            View Our Menu
        </a>
    </div>
</section>

<?php include 'includes/footer.php'; ?>

<script>
// Smooth scroll to contact form when page loads with anchor
document.addEventListener('DOMContentLoaded', function() {
    if (window.location.hash === '#contact-form' || window.location.search.includes('success=1')) {
        const contactForm = document.getElementById('contact-form');
        if (contactForm) {
            contactForm.scrollIntoView({
                behavior: 'smooth',
                block: 'center'
            });
        }
    }
});
</script>
