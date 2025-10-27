<?php
require_once 'includes/config.php';

// Get catering testimonials from database
$catering_reviews = [];
try {
    $stmt = $pdo->prepare("
        SELECT customer_name, customer_title, rating, review_text, service_type, catering_event_type, occasion_details
        FROM customer_reviews
        WHERE status = 'approved' AND service_type = 'Catering'
        ORDER BY created_at DESC
        LIMIT 9
    ");
    $stmt->execute();
    $catering_reviews = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // If table doesn't exist or error occurs, use empty array
    $catering_reviews = [];
}

$success = false;
$error = '';

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Validate input
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $event_date = $_POST['event_date'] ?? '';
        $message = trim($_POST['message'] ?? '');
        
        // Basic validation
        if (empty($name) || empty($email) || empty($phone) || empty($event_date)) {
            throw new Exception('Please fill in all required fields.');
        }
        
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception('Please enter a valid email address.');
        }
        
        // Insert into database
        $stmt = $pdo->prepare("
            INSERT INTO catering_requests (name, email, phone, event_date, message) 
            VALUES (:name, :email, :phone, :event_date, :message)
        ");
        
        $stmt->execute([
            ':name' => $name,
            ':email' => $email,
            ':phone' => $phone,
            ':event_date' => $event_date,
            ':message' => $message
        ]);
        
        $success = true;
        
        // Clear form on success
        $_POST = [];
        
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

$page_title = "Catering Services - Addins Meals on Wheels";
?>

<?php include 'includes/header.php'; ?>

<!-- Hero Section -->
<section class="relative h-96 overflow-hidden">
    <div class="absolute inset-0 bg-cover bg-center" style="background-image: url('assets/img/opening.png');">
        <!-- Enhanced overlay for better text visibility -->
        <div class="absolute inset-0 bg-gradient-to-br from-black/70 via-black/60 to-black/70"></div>
        <div class="absolute inset-0 bg-gradient-to-t from-black/50 via-transparent to-black/40"></div>
    </div>

    <div class="relative z-10 h-full flex items-center">
        <div class="container mx-auto px-4">
            <div class="grid lg:grid-cols-2 gap-12 items-center">
                <!-- Content -->
                <div class="text-center">
                    <div class="inline-flex items-center bg-white/20 backdrop-blur-md rounded-full px-4 py-2 mb-6 border border-white/30 shadow-lg">
                        <i class="fas fa-star text-accent mr-2 drop-shadow-sm"></i>
                        <span class="text-sm font-medium drop-shadow-sm text-white">Premium Catering Services</span>
                    </div>
                    <h1 class="text-4xl md:text-5xl lg:text-6xl font-bold mb-6 leading-tight text-white drop-shadow-2xl text-center mx-auto">
                        Exceptional
                        <span class="text-accent drop-shadow-lg">Catering</span>
                        for Every Occasion
                    </h1>
                    <p class="text-xl mb-8 text-white leading-relaxed drop-shadow-md max-w-2xl">
                        From intimate gatherings to grand celebrations, we create unforgettable culinary experiences
                        that delight your guests and make your event truly special.
                    </p>
                    <div class="flex flex-col sm:flex-row gap-4 justify-center lg:justify-start">
                        <a href="#contact-form" class="btn bg-accent hover:bg-accent-dark text-white px-8 py-4 text-lg font-semibold shadow-lg hover:shadow-xl transform hover:-translate-y-1 transition-all duration-300">
                            <i class="fas fa-calendar-check mr-2"></i>
                            Request Quote
                        </a>
                        <a href="#services" class="btn border-2 border-white text-white hover:bg-white hover:text-primary px-8 py-4 text-lg font-semibold transition-all duration-300">
                            <i class="fas fa-utensils mr-2"></i>
                            View Services
                        </a>
                    </div>
                </div>

                <!-- Hero Visual Element -->
                <div class="relative hidden lg:block">
                    <!-- Decorative Elements -->
                    <div class="absolute -top-8 -right-8 bg-accent/20 backdrop-blur-sm rounded-2xl p-6 transform rotate-12">
                        <i class="fas fa-glass-cheers text-accent text-4xl"></i>
                    </div>
                    <div class="absolute -bottom-8 -left-8 bg-secondary/20 backdrop-blur-sm rounded-2xl p-6 transform -rotate-12">
                        <i class="fas fa-birthday-cake text-secondary text-4xl"></i>
                    </div>

                    <!-- Central Image Container with Slideshow -->
                    <div class="relative bg-white/10 backdrop-blur-sm rounded-3xl p-8 shadow-2xl border border-white/20 overflow-hidden">
                        <!-- Slideshow Container -->
                        <div class="aspect-video bg-gradient-to-br from-white/20 to-white/5 rounded-2xl relative overflow-hidden">
                            <!-- Slideshow Images -->
                            <div class="slideshow-container relative w-full h-full">
                                <div class="slide opacity-0 transition-opacity duration-500 absolute inset-0">
                                    <img src="assets/img/catering.jpeg" alt="Elegant Catering Display" class="w-full h-full object-cover rounded-2xl">
                                </div>
                                <div class="slide opacity-0 transition-opacity duration-500 absolute inset-0">
                                    <img src="assets/img/catering2.png" alt="Professional Event Setup" class="w-full h-full object-cover rounded-2xl">
                                </div>
                                <div class="slide opacity-0 transition-opacity duration-500 absolute inset-0">
                                    <img src="assets/img/Addinkitchen.png" alt="Beautiful Table Setting" class="w-full h-full object-cover rounded-2xl">
                                </div>
                                <div class="slide opacity-100 transition-opacity duration-500 absolute inset-0">
                                    <img src="assets/img/freshfoods.png" alt="Gourmet Food Presentation" class="w-full h-full object-cover rounded-2xl">
                                </div>
                            </div>

                            <!-- Slideshow Navigation Dots -->
                            <div class="absolute bottom-4 left-1/2 transform -translate-x-1/2 flex space-x-2">
                                <button class="slide-dot active w-3 h-3 rounded-full bg-white/60 hover:bg-white transition-all duration-300" onclick="currentSlide(1)"></button>
                                <button class="slide-dot w-3 h-3 rounded-full bg-white/60 hover:bg-white transition-all duration-300" onclick="currentSlide(2)"></button>
                                <button class="slide-dot w-3 h-3 rounded-full bg-white/60 hover:bg-white transition-all duration-300" onclick="currentSlide(3)"></button>
                                <button class="slide-dot w-3 h-3 rounded-full bg-white/60 hover:bg-white transition-all duration-300" onclick="currentSlide(4)"></button>
                            </div>

                            <!-- Slideshow Navigation Arrows -->
                            <button class="absolute left-4 top-1/2 transform -translate-y-1/2 bg-black/30 hover:bg-black/50 text-white p-3 rounded-full transition-all duration-300" onclick="changeSlide(-1)">
                                <i class="fas fa-chevron-left"></i>
                            </button>
                            <button class="absolute right-4 top-1/2 transform -translate-y-1/2 bg-black/30 hover:bg-black/50 text-white p-3 rounded-full transition-all duration-300" onclick="changeSlide(1)">
                                <i class="fas fa-chevron-right"></i>
                            </button>
                        </div>

                        <!-- Overlay Text -->
                        <div class="absolute inset-0 flex items-end justify-center pb-8">
                            <div class="text-center text-white drop-shadow-lg">
                                <h3 class="text-2xl md:text-3xl font-bold mb-2">Elegant Catering Display</h3>
                                <p class="text-lg opacity-90">Professional setup for your special event</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Catering Form Section -->
<section id="contact-form" class="py-20 bg-light relative overflow-hidden">
    <!-- Background Elements -->
    <div class="absolute top-0 right-0 w-96 h-96 bg-primary/5 rounded-full -translate-y-48 translate-x-48"></div>
    <div class="absolute bottom-0 left-0 w-80 h-80 bg-accent/5 rounded-full translate-y-40 -translate-x-40"></div>

    <div class="container mx-auto px-4 max-w-5xl relative">
        <div class="text-center mb-16">
            <div class="inline-flex items-center bg-primary/10 rounded-full px-4 py-2 mb-4">
                <i class="fas fa-envelope text-primary mr-2"></i>
                <span class="text-sm font-medium text-primary">Get Your Quote</span>
            </div>
            <h2 class="text-3xl md:text-4xl font-bold mb-6">Request a Catering Quote</h2>
            <p class="text-xl text-gray-600 max-w-2xl mx-auto">
                Tell us about your event and we'll create a customized catering proposal just for you
            </p>
        </div>

        <div class="grid lg:grid-cols-5 gap-12 items-start">
            <!-- Form Info -->
            <div class="lg:col-span-2">
                <div class="bg-white rounded-2xl shadow-xl p-8 border border-gray-100">
                    <h3 class="text-2xl font-bold mb-6 text-gray-800">Why Choose Us?</h3>
                    <div class="space-y-6">
                        <div class="flex items-start space-x-4">
                            <div class="flex-shrink-0 w-12 h-12 bg-primary/10 rounded-full flex items-center justify-center">
                                <i class="fas fa-leaf text-primary text-xl"></i>
                            </div>
                            <div>
                                <h4 class="font-semibold text-gray-800 mb-2">Fresh Ingredients</h4>
                                <p class="text-gray-600 text-sm">We use only the finest, locally-sourced ingredients for every dish</p>
                            </div>
                        </div>
                        <div class="flex items-start space-x-4">
                            <div class="flex-shrink-0 w-12 h-12 bg-accent/10 rounded-full flex items-center justify-center">
                                <i class="fas fa-clock text-accent text-xl"></i>
                            </div>
                            <div>
                                <h4 class="font-semibold text-gray-800 mb-2">On-Time Service</h4>
                                <p class="text-gray-600 text-sm">Punctual delivery and setup for stress-free event planning</p>
                            </div>
                        </div>
                        <div class="flex items-start space-x-4">
                            <div class="flex-shrink-0 w-12 h-12 bg-secondary/10 rounded-full flex items-center justify-center">
                                <i class="fas fa-users text-secondary text-xl"></i>
                            </div>
                            <div>
                                <h4 class="font-semibold text-gray-800 mb-2">Professional Staff</h4>
                                <p class="text-gray-600 text-sm">Experienced catering team ensures flawless execution</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Form -->
            <div class="lg:col-span-3">
                <div class="bg-white rounded-2xl shadow-2xl p-8 border-2 border-gray-200 relative overflow-hidden">
                    <!-- Form Background Enhancement -->
                    <div class="absolute inset-0 bg-gradient-to-br from-white via-gray-50 to-white opacity-50"></div>

                    <div class="relative z-10">
                    <?php if ($success): ?>
                        <div class="mb-6 p-6 bg-green-50 border border-green-200 rounded-xl">
                            <div class="flex items-center mb-4">
                                <div class="flex-shrink-0 w-12 h-12 bg-green-100 rounded-full flex items-center justify-center mr-4">
                                    <i class="fas fa-check text-green-600 text-xl"></i>
                                </div>
                                <div>
                                    <h3 class="text-lg font-semibold text-green-800">Request Submitted Successfully!</h3>
                                    <p class="text-green-700">We'll contact you within 24 hours</p>
                                </div>
                            </div>
                        </div>
                    <?php elseif ($error): ?>
                        <div class="mb-6 p-6 bg-red-50 border border-red-200 rounded-xl">
                            <div class="flex items-center mb-4">
                                <div class="flex-shrink-0 w-12 h-12 bg-red-100 rounded-full flex items-center justify-center mr-4">
                                    <i class="fas fa-exclamation-triangle text-red-600 text-xl"></i>
                                </div>
                                <div>
                                    <h3 class="text-lg font-semibold text-red-800">Error Submitting Request</h3>
                                    <p class="text-red-700"><?php echo htmlspecialchars($error); ?></p>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="" class="space-y-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- Name -->
                            <div class="space-y-2">
                                <label for="name" class="block text-sm font-semibold text-gray-700">
                                    <i class="fas fa-user mr-2 text-primary"></i>Full Name <span class="text-red-500">*</span>
                                </label>
                                <input type="text" id="name" name="name" required
                                       value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>"
                                       class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent transition-all duration-300 bg-gray-50 focus:bg-white">
                            </div>

                            <!-- Email -->
                            <div class="space-y-2">
                                <label for="email" class="block text-sm font-semibold text-gray-700">
                                    <i class="fas fa-envelope mr-2 text-primary"></i>Email <span class="text-red-500">*</span>
                                </label>
                                <input type="email" id="email" name="email" required
                                       value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>"
                                       class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent transition-all duration-300 bg-gray-50 focus:bg-white">
                            </div>

                            <!-- Phone -->
                            <div class="space-y-2">
                                <label for="phone" class="block text-sm font-semibold text-gray-700">
                                    <i class="fas fa-phone mr-2 text-primary"></i>Phone <span class="text-red-500">*</span>
                                </label>
                                <input type="tel" id="phone" name="phone" required
                                       value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>"
                                       class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent transition-all duration-300 bg-gray-50 focus:bg-white">
                            </div>

                            <!-- Event Date -->
                            <div class="space-y-2">
                                <label for="event_date" class="block text-sm font-semibold text-gray-700">
                                    <i class="fas fa-calendar mr-2 text-primary"></i>Event Date <span class="text-red-500">*</span>
                                </label>
                                <input type="date" id="event_date" name="event_date" required
                                       min="<?php echo date('Y-m-d'); ?>"
                                       value="<?php echo htmlspecialchars($_POST['event_date'] ?? ''); ?>"
                                       class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent transition-all duration-300 bg-gray-50 focus:bg-white">
                            </div>
                        </div>

                        <!-- Message -->
                        <div class="space-y-2">
                            <label for="message" class="block text-sm font-semibold text-gray-700">
                                <i class="fas fa-edit mr-2 text-primary"></i>Event Details & Special Requirements
                            </label>
                            <textarea id="message" name="message" rows="5"
                                      placeholder="Tell us about your event - number of guests, type of cuisine preferred, dietary restrictions, venue details, etc."
                                      class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent transition-all duration-300 bg-gray-50 focus:bg-white resize-none"><?php echo htmlspecialchars($_POST['message'] ?? ''); ?></textarea>
                        </div>

                        <!-- Submit Button -->
                        <div class="pt-4">
                            <button type="submit" class="w-full bg-gradient-to-r from-accent via-accent-dark to-accent text-white font-bold py-5 px-8 rounded-xl hover:from-accent-dark hover:via-accent hover:to-accent-dark transform hover:-translate-y-2 transition-all duration-300 shadow-2xl hover:shadow-3xl border-2 border-accent/30 text-lg">
                                <i class="fas fa-paper-plane mr-3 text-xl"></i>
                                Submit Catering Request
                                <span class="block text-sm font-normal mt-1 opacity-90">Get your personalized quote within 24 hours</span>
                            </button>
                        </div>
                    </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Catering Services -->
<section id="services" class="py-20 bg-white relative">
    <!-- Background Pattern -->
    <div class="absolute inset-0 opacity-5">
        <div class="absolute inset-0 bg-[url('data:image/svg+xml,%3Csvg width="40" height="40" viewBox="0 0 40 40" xmlns="http://www.w3.org/2000/svg"%3E%3Cg fill="%23000000" fill-opacity="0.05"%3E%3Cpath d="M20 20c0-5.5-4.5-10-10-10s-10 4.5-10 10 4.5 10 10 10 10-4.5 10-10zm10 0c0-5.5-4.5-10-10-10s-10 4.5-10 10 4.5 10 10 10 10-4.5 10-10z"/%3E%3C/g%3E%3C/g%3E%3C/svg%3E')] bg-repeat"></div>
    </div>

    <div class="container mx-auto px-4 relative">
        <div class="text-center mb-16">
            <div class="inline-flex items-center bg-accent/10 rounded-full px-4 py-2 mb-4">
                <i class="fas fa-concierge-bell text-accent mr-2"></i>
                <span class="text-sm font-medium text-accent">Professional Services</span>
            </div>
            <h2 class="text-3xl md:text-4xl font-bold mb-6">Our Catering Services</h2>
            <p class="text-xl text-gray-600 max-w-2xl mx-auto">
                From intimate gatherings to grand celebrations, we offer comprehensive catering solutions
            </p>
        </div>

        <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-8 max-w-6xl mx-auto">
            <!-- Corporate Events -->
            <div class="group bg-white rounded-2xl shadow-lg hover:shadow-2xl p-8 border border-gray-100 hover:border-primary/20 transition-all duration-300 transform hover:-translate-y-2">
                <div class="text-center mb-6">
                    <div class="w-20 h-20 bg-primary rounded-2xl flex items-center justify-center mx-auto mb-4 shadow-lg group-hover:scale-110 transition-transform duration-300">
                        <i class="fas fa-building text-white text-3xl"></i>
                    </div>
                    <h3 class="text-2xl font-bold mb-3 text-gray-800 group-hover:text-primary transition-colors">Corporate Events</h3>
                    <p class="text-gray-600 leading-relaxed">
                        Impress your colleagues and clients with our professional catering services for meetings, conferences, product launches, and corporate gatherings.
                    </p>
                </div>
                <div class="space-y-3">
                    <div class="flex items-center text-sm text-gray-600">
                        <i class="fas fa-check text-primary mr-3 flex-shrink-0"></i>
                        Executive lunch meetings
                    </div>
                    <div class="flex items-center text-sm text-gray-600">
                        <i class="fas fa-check text-primary mr-3 flex-shrink-0"></i>
                        Conference catering
                    </div>
                    <div class="flex items-center text-sm text-gray-600">
                        <i class="fas fa-check text-primary mr-3 flex-shrink-0"></i>
                        Product launch events
                    </div>
                    <div class="flex items-center text-sm text-gray-600">
                        <i class="fas fa-check text-primary mr-3 flex-shrink-0"></i>
                        Team building events
                    </div>
                </div>
            </div>

            <!-- Weddings & Receptions -->
            <div class="group bg-white rounded-2xl shadow-lg hover:shadow-2xl p-8 border border-gray-100 hover:border-accent/20 transition-all duration-300 transform hover:-translate-y-2">
                <div class="text-center mb-6">
                    <div class="w-20 h-20 bg-accent rounded-2xl flex items-center justify-center mx-auto mb-4 shadow-lg group-hover:scale-110 transition-transform duration-300">
                        <i class="fas fa-heart text-white text-3xl"></i>
                    </div>
                    <h3 class="text-2xl font-bold mb-3 text-gray-800 group-hover:text-accent transition-colors">Weddings & Receptions</h3>
                    <p class="text-gray-600 leading-relaxed">
                        Make your special day unforgettable with our customizable wedding catering packages, from intimate ceremonies to grand receptions.
                    </p>
                </div>
                <div class="space-y-3">
                    <div class="flex items-center text-sm text-gray-600">
                        <i class="fas fa-check text-accent mr-3 flex-shrink-0"></i>
                        Wedding ceremonies
                    </div>
                    <div class="flex items-center text-sm text-gray-600">
                        <i class="fas fa-check text-accent mr-3 flex-shrink-0"></i>
                        Reception dinners
                    </div>
                    <div class="flex items-center text-sm text-gray-600">
                        <i class="fas fa-check text-accent mr-3 flex-shrink-0"></i>
                        Bridal showers
                    </div>
                    <div class="flex items-center text-sm text-gray-600">
                        <i class="fas fa-check text-accent mr-3 flex-shrink-0"></i>
                        Engagement parties
                    </div>
                </div>
            </div>

            <!-- Private Parties -->
            <div class="group bg-white rounded-2xl shadow-lg hover:shadow-2xl p-8 border border-gray-100 hover:border-secondary/20 transition-all duration-300 transform hover:-translate-y-2">
                <div class="text-center mb-6">
                    <div class="w-20 h-20 bg-secondary rounded-2xl flex items-center justify-center mx-auto mb-4 shadow-lg group-hover:scale-110 transition-transform duration-300">
                        <i class="fas fa-birthday-cake text-white text-3xl"></i>
                    </div>
                    <h3 class="text-2xl font-bold mb-3 text-gray-800 group-hover:text-secondary transition-colors">Private Parties</h3>
                    <p class="text-gray-600 leading-relaxed">
                        From birthday parties to family reunions, anniversary celebrations to graduation parties - we'll handle the food so you can enjoy your guests.
                    </p>
                </div>
                <div class="space-y-3">
                    <div class="flex items-center text-sm text-gray-600">
                        <i class="fas fa-check text-secondary mr-3 flex-shrink-0"></i>
                        Birthday celebrations
                    </div>
                    <div class="flex items-center text-sm text-gray-600">
                        <i class="fas fa-check text-secondary mr-3 flex-shrink-0"></i>
                        Family reunions
                    </div>
                    <div class="flex items-center text-sm text-gray-600">
                        <i class="fas fa-check text-secondary mr-3 flex-shrink-0"></i>
                        Anniversary parties
                    </div>
                    <div class="flex items-center text-sm text-gray-600">
                        <i class="fas fa-check text-secondary mr-3 flex-shrink-0"></i>
                        Graduation events
                    </div>
                </div>
            </div>
        </div>

        <!-- Additional Services -->
        <div class="mt-16 text-center">
            <h3 class="text-2xl font-bold mb-8 text-gray-800">Additional Services</h3>
            <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-6 max-w-4xl mx-auto">
                <div class="bg-white rounded-xl p-6 shadow-md hover:shadow-lg transition-all duration-300 border border-gray-100">
                    <div class="w-12 h-12 bg-primary/10 rounded-full flex items-center justify-center mx-auto mb-3">
                        <i class="fas fa-utensils text-primary text-xl"></i>
                    </div>
                    <h4 class="font-semibold mb-2">Equipment Rental</h4>
                    <p class="text-sm text-gray-600">Tables, chairs, linens, and serving equipment</p>
                </div>
                <div class="bg-white rounded-xl p-6 shadow-md hover:shadow-lg transition-all duration-300 border border-gray-100">
                    <div class="w-12 h-12 bg-accent/10 rounded-full flex items-center justify-center mx-auto mb-3">
                        <i class="fas fa-users text-accent text-xl"></i>
                    </div>
                    <h4 class="font-semibold mb-2">Staff Services</h4>
                    <p class="text-sm text-gray-600">Professional servers and bartenders</p>
                </div>
                <div class="bg-white rounded-xl p-6 shadow-md hover:shadow-lg transition-all duration-300 border border-gray-100">
                    <div class="w-12 h-12 bg-secondary/10 rounded-full flex items-center justify-center mx-auto mb-3">
                        <i class="fas fa-paint-brush text-secondary text-xl"></i>
                    </div>
                    <h4 class="font-semibold mb-2">Event Styling</h4>
                    <p class="text-sm text-gray-600">Floral arrangements and decor setup</p>
                </div>
                <div class="bg-white rounded-xl p-6 shadow-md hover:shadow-lg transition-all duration-300 border border-gray-100">
                    <div class="w-12 h-12 bg-primary/10 rounded-full flex items-center justify-center mx-auto mb-3">
                        <i class="fas fa-truck text-primary text-xl"></i>
                    </div>
                    <h4 class="font-semibold mb-2">Delivery & Setup</h4>
                    <p class="text-sm text-gray-600">Complete delivery and venue setup</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Testimonial -->
<section class="py-20 bg-light relative overflow-hidden">
    <!-- Background Elements -->
    <div class="absolute inset-0 bg-gradient-to-br from-primary/5 via-transparent to-accent/5"></div>
    <div class="absolute top-0 right-0 w-96 h-96 bg-secondary/5 rounded-full -translate-y-48 translate-x-48"></div>
    <div class="absolute bottom-0 left-0 w-80 h-80 bg-accent/5 rounded-full translate-y-40 -translate-x-40"></div>

    <div class="container mx-auto px-4 max-w-6xl relative">
        <div class="text-center mb-16">
            <div class="inline-flex items-center bg-primary/10 rounded-full px-4 py-2 mb-4">
                <i class="fas fa-star text-primary mr-2"></i>
                <span class="text-sm font-medium text-primary">Client Testimonials</span>
            </div>
            <h2 class="text-3xl md:text-5xl font-bold mb-6 text-dark">
                What Our Clients Say
            </h2>
            <p class="text-xl text-gray-600 max-w-3xl mx-auto leading-relaxed">
                Real experiences from real clients who trusted us with their most important celebrations
            </p>
        </div>

        <!-- Testimonials Carousel Container -->
        <div class="relative">
            <div class="testimonials-container overflow-hidden">
                <div class="testimonials-wrapper flex transition-transform duration-500 ease-in-out" id="testimonials-wrapper">
                    <?php if (!empty($catering_reviews)): ?>
                        <?php
                        // Group testimonials in sets of 3
                        $testimonial_groups = array_chunk($catering_reviews, 3);
                        foreach ($testimonial_groups as $group_index => $group):
                        ?>
                            <div class="testimonial-slide min-w-full flex-shrink-0 px-4">
                                <div class="flex flex-col md:flex-row gap-6 justify-center items-stretch">
                                    <?php foreach ($group as $review_index => $review): ?>
                                        <div class="bg-white rounded-2xl p-8 border-2 border-primary/20 hover:border-primary/40 hover:shadow-2xl transition-all duration-300 shadow-lg w-80 h-96 flex flex-col">
                                            <div class="flex items-center mb-6">
                                                <div class="w-14 h-14 bg-primary rounded-full flex items-center justify-center mr-4 shadow-lg">
                                                    <span class="text-white font-bold text-lg"><?php echo strtoupper(substr($review['customer_name'], 0, 1)); ?></span>
                                                </div>
                                                <div>
                                                    <h4 class="font-bold text-dark text-lg"><?php echo htmlspecialchars($review['customer_name']); ?></h4>
                                                    <?php if (!empty($review['customer_title'])): ?>
                                                        <p class="text-primary text-sm font-medium"><?php echo htmlspecialchars($review['customer_title']); ?></p>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                            <div class="mb-6 flex-grow">
                                                <div class="flex text-primary mb-3 text-base">
                                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                                        <i class="fas fa-star <?php echo $i <= $review['rating'] ? '' : 'text-gray-300'; ?>"></i>
                                                    <?php endfor; ?>
                                                </div>
                                                <div class="w-16 h-1 bg-primary/40 rounded-full mb-4"></div>
                                                <p class="text-gray-700 leading-relaxed text-sm">
                                                    "<?php echo htmlspecialchars(substr($review['review_text'], 0, 180) . (strlen($review['review_text']) > 180 ? '...' : '')); ?>"
                                                </p>
                                            </div>
                                            <div class="mt-auto pt-4 border-t border-primary/20">
                                                <p class="text-primary text-sm font-medium">
                                                    <?php
                                                    echo htmlspecialchars($review['catering_event_type'] ?? 'Catering Event');
                                                    if (!empty($review['occasion_details'])) {
                                                        echo ' • ' . htmlspecialchars($review['occasion_details']);
                                                    }
                                                    ?>
                                                </p>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <!-- Single slide for no testimonials message -->
                        <div class="testimonial-slide min-w-full flex-shrink-0 px-4">
                            <div class="max-w-md mx-auto text-center py-16 h-full flex items-center justify-center">
                                <div class="bg-primary/5 rounded-2xl p-12">
                                    <i class="fas fa-star text-primary text-5xl mb-6"></i>
                                    <h3 class="text-2xl font-bold text-gray-800 mb-4">Be Our First Catering Success Story!</h3>
                                    <p class="text-gray-600 text-lg leading-relaxed mb-6">
                                        We're excited to showcase testimonials from our valued catering clients. Be among the first to share your exceptional catering experience with Addins Meals on Wheels!
                                    </p>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Carousel Navigation -->
            <?php if (!empty($catering_reviews) && count($catering_reviews) > 1): ?>
                <!-- Dots Navigation -->
                <div class="flex justify-center space-x-2 mt-8" id="testimonial-dots">
                    <?php
                    $total_groups = ceil(count($catering_reviews) / 3);
                    for ($i = 0; $i < $total_groups; $i++):
                    ?>
                        <button class="testimonial-dot w-3 h-3 rounded-full transition-all duration-300 <?php echo $i === 0 ? 'bg-primary' : 'bg-gray-300'; ?>"
                                onclick="currentTestimonialSlide(<?php echo $i + 1; ?>)"></button>
                    <?php endfor; ?>
                </div>

                <!-- Arrow Navigation -->
                <button class="absolute left-0 top-1/2 transform -translate-y-1/2 -translate-x-4 bg-white hover:bg-primary text-primary hover:text-white p-3 rounded-full shadow-lg transition-all duration-300 z-10"
                        onclick="changeTestimonialSlide(-1)">
                    <i class="fas fa-chevron-left"></i>
                </button>
                <button class="absolute right-0 top-1/2 transform -translate-y-1/2 translate-x-4 bg-white hover:bg-primary text-primary hover:text-white p-3 rounded-full shadow-lg transition-all duration-300 z-10"
                        onclick="changeTestimonialSlide(1)">
                    <i class="fas fa-chevron-right"></i>
                </button>
            <?php endif; ?>
        </div>

        <!-- Call to Action -->
        <div class="text-center mt-16">
            <div class="bg-white backdrop-blur-sm rounded-3xl p-10 border-2 border-primary/10 max-w-4xl mx-auto shadow-2xl hover:shadow-3xl transition-all duration-300">
                <div class="mb-6">
                    <i class="fas fa-calendar-plus text-primary text-4xl mb-4 animate-pulse"></i>
                </div>
                <h3 class="text-3xl font-bold mb-6 text-dark">Ready to Create an Unforgettable Event?</h3>
                <p class="text-gray-700 mb-8 text-lg leading-relaxed">Join hundreds of satisfied clients who trust us with their most important celebrations</p>
                <a href="#contact-form" class="inline-block bg-accent hover:bg-accent-dark text-white px-10 py-4 text-xl font-bold shadow-xl hover:shadow-2xl transform hover:-translate-y-2 transition-all duration-300 rounded-xl border-2 border-white/20">
                    <i class="fas fa-calendar-check mr-3"></i>
                    Book Your Event Today
                </a>
            </div>
        </div>
    </div>
</section>

<!-- CTA Section -->
<section class="py-20 bg-primary/5 relative overflow-hidden">
    <!-- Background Pattern -->
    <div class="absolute inset-0 opacity-10">
        <div class="absolute inset-0 bg-[url('data:image/svg+xml,%3Csvg width="80" height="80" viewBox="0 0 80 80" xmlns="http://www.w3.org/2000/svg"%3E%3Cg fill="none" fill-rule="evenodd"%3E%3Cg fill="%23ffffff" fill-opacity="0.1"%3E%3Cpath d="M40 40c0-8.8-7.2-16-16-16s-16 7.2-16 16 7.2 16 16 16 16-7.2 16-16z"/%3E%3C/g%3E%3C/g%3E%3C/svg%3E')] bg-repeat"></div>
    </div>

    <div class="container mx-auto px-4 max-w-4xl relative text-center">
        <!-- Floating Icons -->
        <div class="absolute top-8 left-8 text-primary/20 animate-bounce">
            <i class="fas fa-utensils text-4xl"></i>
        </div>
        <div class="absolute top-8 right-8 text-accent/20 animate-pulse">
            <i class="fas fa-glass-cheers text-4xl"></i>
        </div>
        <div class="absolute bottom-8 left-1/4 text-secondary/20 animate-bounce" style="animation-delay: 1s;">
            <i class="fas fa-birthday-cake text-3xl"></i>
        </div>
        <div class="absolute bottom-8 right-1/4 text-primary/20 animate-pulse" style="animation-delay: 2s;">
            <i class="fas fa-heart text-3xl"></i>
        </div>

        <div class="relative">
            <div class="inline-flex items-center bg-primary/10 backdrop-blur-sm rounded-full px-4 py-2 mb-6">
                <i class="fas fa-rocket text-primary mr-2"></i>
                <span class="text-sm font-medium text-primary">Get Started Today</span>
            </div>
            <h2 class="text-3xl md:text-4xl lg:text-5xl font-bold mb-6 leading-tight text-dark">
                Ready to Plan Your
                <span class="text-accent">Perfect Event?</span>
            </h2>
            <p class="text-xl mb-8 text-gray-600 max-w-2xl mx-auto leading-relaxed">
                Don't wait! Contact us today to discuss your catering needs and let us help make your event a success that your guests will never forget.
            </p>

            <div class="flex flex-col sm:flex-row gap-4 justify-center items-center mb-8">
                <a href="#contact-form" class="btn bg-primary hover:bg-primary-dark text-white px-8 py-4 text-lg font-bold shadow-lg hover:shadow-xl transform hover:-translate-y-1 transition-all duration-300">
                    <i class="fas fa-paper-plane mr-2"></i>
                    Request Quote Now
                </a>
                <a href="tel:+1234567890" class="btn border-2 border-primary text-primary hover:bg-primary hover:text-white px-8 py-4 text-lg font-semibold transition-all duration-300">
                    <i class="fas fa-phone mr-2"></i>
                    Call Us: (254) 112-855-900
                </a>
            </div>

            <!-- Trust Indicators -->
            <div class="grid grid-cols-2 md:grid-cols-4 gap-6 max-w-2xl mx-auto">
                <div class="text-center">
                    <div class="text-2xl font-bold text-primary mb-1">500+</div>
                    <div class="text-gray-600 text-sm">Events Catered</div>
                </div>
                <div class="text-center">
                    <div class="text-2xl font-bold text-accent mb-1">98%</div>
                    <div class="text-gray-600 text-sm">Client Satisfaction</div>
                </div>
                <div class="text-center">
                    <div class="text-2xl font-bold text-secondary mb-1">24/7</div>
                    <div class="text-gray-600 text-sm">Support Available</div>
                </div>
                <div class="text-center">
                    <div class="text-2xl font-bold text-primary mb-1">5★</div>
                    <div class="text-gray-600 text-sm">Average Rating</div>
                </div>
            </div>
        </div>
    </div>
</section>

<script>
// Initialize form validation
const form = document.querySelector('form');
const requiredFields = form.querySelectorAll('[required]');

form.addEventListener('submit', function(e) {
    let isValid = true;
    
    // Check required fields
    requiredFields.forEach(field => {
        if (!field.value.trim()) {
            field.classList.add('border-red-500');
            isValid = false;
        } else {
            field.classList.remove('border-red-500');
        }
    });
    
    // Check email format
    const emailField = document.getElementById('email');
    if (emailField && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(emailField.value)) {
        emailField.classList.add('border-red-500');
        isValid = false;
    }
    
    // Check date is in the future
    const dateField = document.getElementById('event_date');
    if (dateField && dateField.value) {
        const today = new Date();
        today.setHours(0, 0, 0, 0);
        const selectedDate = new Date(dateField.value);
        
        if (selectedDate < today) {
            dateField.classList.add('border-red-500');
            alert('Please select a future date for your event.');
            isValid = false;
        }
    }
    // Remove error class when user starts typing
    requiredFields.forEach(field => {
        field.addEventListener('input', function() {
            if (this.value.trim()) {
                this.classList.remove('border-red-500');
            }
        });
    });

    if (!isValid) {
        e.preventDefault(); // Stop form submission if validation fails
    }
}); // CLOSING BRACE WAS MISSING HERE!


// Slideshow functionality
let slideIndex = 1;
let slideInterval;
let slides; // Declare globally accessible variables
let dots;
let slideshowContainer;

// Initialize slideshow when page loads
document.addEventListener('DOMContentLoaded', function() {
    initializeSlideshow();

    // Initialize testimonials carousel
    showTestimonialSlide(1); // Show first slide
    startTestimonialSlideshow(); // Start auto-slideshow

    // Add hover effects for testimonials carousel
    const testimonialContainer = document.querySelector('.testimonials-container');
    if (testimonialContainer) {
        testimonialContainer.addEventListener('mouseenter', stopTestimonialSlideshow);
        testimonialContainer.addEventListener('mouseleave', startTestimonialSlideshow);
    }
});

// Fallback initialization (not strictly necessary if DOMContentLoaded works, but good for robustness)
window.addEventListener('load', function() {
    if (!slides || slides.length === 0 || !document.querySelector('.slide.opacity-100')) {
        initializeSlideshow();
    }

    // Initialize testimonials carousel as fallback
    if (!document.querySelector('.testimonial-slide') || !document.getElementById('testimonials-wrapper')) {
        showTestimonialSlide(1);
        startTestimonialSlideshow();
    }
});

function initializeSlideshow() {
    slides = document.querySelectorAll('.slide'); // Assign to global variable
    dots = document.querySelectorAll('.slide-dot');    // Assign to global variable
    slideshowContainer = document.querySelector('.slideshow-container'); // Assign to global variable

    if (slides.length === 0) {
        console.error('Slideshow elements not found');
        return;
    }

    // Set initial state
    showSlide(slideIndex); // Use initial slideIndex
    startSlideshow();

    // Add event listeners
    if (slideshowContainer) {
        slideshowContainer.addEventListener('mouseenter', stopSlideshow);
        slideshowContainer.addEventListener('mouseleave', startSlideshow);
    }

    // Add click listeners to dots and arrows
    setupControls();
}

function setupControls() {
    // Dots
    dots.forEach((dot, index) => { // Use global `dots`
        dot.addEventListener('click', () => currentSlide(index + 1));
    });

    // Arrows
    if (slideshowContainer) { // Use global `slideshowContainer`
        const prevBtn = slideshowContainer.querySelector('.absolute.left-4');
        const nextBtn = slideshowContainer.querySelector('.absolute.right-4');

        if (prevBtn) prevBtn.addEventListener('click', () => changeSlide(-1));
        if (nextBtn) nextBtn.addEventListener('click', () => changeSlide(1));
    }

    // Testimonials carousel controls
    const testimonialDots = document.querySelectorAll('.testimonial-dot');
    testimonialDots.forEach((dot, index) => {
        dot.addEventListener('click', () => currentTestimonialSlide(index + 1));
    });

    const testimonialContainer = document.querySelector('.testimonials-container');
    if (testimonialContainer) {
        const prevBtn = testimonialContainer.parentElement.querySelector('.absolute.left-0');
        const nextBtn = testimonialContainer.parentElement.querySelector('.absolute.right-0');

        if (prevBtn) prevBtn.addEventListener('click', () => changeTestimonialSlide(-1));
        if (nextBtn) nextBtn.addEventListener('click', () => changeTestimonialSlide(1));
    }
}

function showSlide(n) {
    if (!slides || slides.length === 0 || !dots || dots.length === 0) {
        console.error('Slideshow elements not initialized for showSlide.');
        return;
    }

    if (n > slides.length) slideIndex = 1;
    else if (n < 1) slideIndex = slides.length;
    else slideIndex = n; // Update global slideIndex

    // Update slides
    slides.forEach((slide, index) => {
        if (index === slideIndex - 1) {
            slide.classList.remove('opacity-0');
            slide.classList.add('opacity-100');
        } else {
            slide.classList.remove('opacity-100');
            slide.classList.add('opacity-0');
        }
    });

    // Update dots
    dots.forEach((dot, index) => {
        if (index === slideIndex - 1) {
            dot.classList.add('active', 'bg-white');
            dot.classList.remove('bg-white/60');
        } else {
            dot.classList.remove('active', 'bg-white');
            dot.classList.add('bg-white/60');
        }
    });
}

function changeSlide(n) {
    showSlide(slideIndex + n);
}

function currentSlide(n) {
    showSlide(n);
}

function startSlideshow() {
    if (slideInterval) clearInterval(slideInterval);
    slideInterval = setInterval(() => {
        slideIndex++;
        showSlide(slideIndex);
    }, 5000);
}

function stopSlideshow() {
    if (slideInterval) {
        clearInterval(slideInterval);
        slideInterval = null;
    }
}

// Testimonials Carousel functionality
let testimonialSlideIndex = 1;
let testimonialInterval;

function showTestimonialSlide(n) {
    const wrapper = document.getElementById('testimonials-wrapper');
    const slides = document.querySelectorAll('.testimonial-slide');

    if (!wrapper || slides.length === 0) return;

    if (n > slides.length) testimonialSlideIndex = 1;
    else if (n < 1) testimonialSlideIndex = slides.length;
    else testimonialSlideIndex = n;

    // Move the wrapper to show the correct slide (each slide contains 3 testimonials)
    const translateX = -(testimonialSlideIndex - 1) * 100;
    wrapper.style.transform = `translateX(${translateX}%)`;

    // Update dots
    const dots = document.querySelectorAll('.testimonial-dot');
    dots.forEach((dot, index) => {
        if (index === testimonialSlideIndex - 1) {
            dot.classList.remove('bg-gray-300');
            dot.classList.add('bg-primary');
        } else {
            dot.classList.remove('bg-primary');
            dot.classList.add('bg-gray-300');
        }
    });
}

function changeTestimonialSlide(n) {
    showTestimonialSlide(testimonialSlideIndex + n);
}

function currentTestimonialSlide(n) {
    showTestimonialSlide(n);
}

function startTestimonialSlideshow() {
    const slides = document.querySelectorAll('.testimonial-slide');
    if (slides.length <= 1) return; // Don't start auto-slideshow if only one slide (or no testimonials)

    if (testimonialInterval) clearInterval(testimonialInterval);
    testimonialInterval = setInterval(() => {
        testimonialSlideIndex++;
        showTestimonialSlide(testimonialSlideIndex);
    }, 6000); // Change slide every 6 seconds
}

function stopTestimonialSlideshow() {
    if (testimonialInterval) {
        clearInterval(testimonialInterval);
        testimonialInterval = null;
    }
}
</script>

<?php include 'includes/footer.php'; ?>