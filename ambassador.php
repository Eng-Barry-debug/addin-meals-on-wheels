<?php
require_once 'includes/config.php';

$success = false;
$error = '';

// Fetch success stories from database
$success_stories = [];
try {
    $stmt = $pdo->prepare("
        SELECT name, role, story, image, rating
        FROM success_stories
        WHERE is_active = 1
        ORDER BY sort_order ASC, id ASC
    ");
    $stmt->execute();
    $success_stories = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // If table doesn't exist or error occurs, use empty array
    $success_stories = [];
}

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $social_media = trim($_POST['social_media'] ?? '');
        $experience = trim($_POST['experience'] ?? '');
        $motivation = trim($_POST['motivation'] ?? '');
        $message = trim($_POST['message'] ?? '');

        // Handle file uploads
        $id_front = '';
        $id_back = '';

        // Create uploads/ambassadors directory if it doesn't exist
        $upload_dir = 'uploads/ambassadors/';
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0755, true); // 0755 permissions for directory, true for recursive
        }

        // Handle ID front image upload
        if (isset($_FILES['id_front']) && $_FILES['id_front']['error'] === UPLOAD_ERR_OK) {
            $file_tmp = $_FILES['id_front']['tmp_name'];
            // Sanitize file name for security, prevent directory traversal, etc.
            $original_file_name = basename($_FILES['id_front']['name']);
            $extension = pathinfo($original_file_name, PATHINFO_EXTENSION);
            $file_name = time() . '_front_' . uniqid() . '.' . $extension; // Add uniqid to prevent name collisions
            $file_path = $upload_dir . $file_name;

            if (move_uploaded_file($file_tmp, $file_path)) {
                $id_front = $file_name;
            } else {
                throw new Exception('Failed to upload front ID card image.');
            }
        }

        // Handle ID back image upload
        if (isset($_FILES['id_back']) && $_FILES['id_back']['error'] === UPLOAD_ERR_OK) {
            $file_tmp = $_FILES['id_back']['tmp_name'];
            // Sanitize file name
            $original_file_name = basename($_FILES['id_back']['name']);
            $extension = pathinfo($original_file_name, PATHINFO_EXTENSION);
            $file_name = time() . '_back_' . uniqid() . '.' . $extension; // Add uniqid
            $file_path = $upload_dir . $file_name;

            if (move_uploaded_file($file_tmp, $file_path)) {
                $id_back = $file_name;
            } else {
                throw new Exception('Failed to upload back ID card image.');
            }
        }

        // Basic validation
        if (empty($name) || empty($email) || empty($phone)) {
            // Unlink uploaded files if validation fails later
            if (!empty($id_front)) @unlink($upload_dir . $id_front);
            if (!empty($id_back)) @unlink($upload_dir . $id_back);
            throw new Exception('Name, email, and phone are required.');
        }

        if (empty($id_front) || empty($id_back)) {
            // Unlink uploaded files if validation fails
            if (!empty($id_front)) @unlink($upload_dir . $id_front);
            if (!empty($id_back)) @unlink($upload_dir . $id_back);
            throw new Exception('Both front and back ID card images are required.');
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            // Unlink uploaded files if validation fails
            if (!empty($id_front)) @unlink($upload_dir . $id_front);
            if (!empty($id_back)) @unlink($upload_dir . $id_back);
            throw new Exception('Please enter a valid email address.');
        }
        
        // Ensure terms are accepted
        if (!isset($_POST['terms'])) {
            // Unlink uploaded files if validation fails
            if (!empty($id_front)) @unlink($upload_dir . $id_front);
            if (!empty($id_back)) @unlink($upload_dir . $id_back);
            throw new Exception('You must agree to the Terms and Conditions and Privacy Policy.');
        }


        // Insert into database
        $stmt = $pdo->prepare("
            INSERT INTO ambassadors (name, email, phone, social_media, experience, motivation, message, id_front, id_back, application_date)
            VALUES (:name, :email, :phone, :social_media, :experience, :motivation, :message, :id_front, :id_back, NOW())
        ");

        $stmt->execute([
            ':name' => $name,
            ':email' => $email,
            ':phone' => $phone,
            ':social_media' => $social_media,
            ':experience' => $experience,
            ':motivation' => $motivation,
            ':message' => $message,
            ':id_front' => 'uploads/ambassadors/' . $id_front, // Store full path relative to project root
            ':id_back' => 'uploads/ambassadors/' . $id_back       // Store full path relative to project root
        ]);

        $success = true;
        $_POST = []; // Clear form on success
        // Redirect to self to prevent form re-submission on refresh
        header('Location: ' . $_SERVER['PHP_SELF'] . '?success=1');
        exit();

    } catch (Exception $e) {
        $error = $e->getMessage();
        // Here, if `id_front` or `id_back` were uploaded but a later validation failed,
        // we should delete them to prevent orphaned files. Check if they were uploaded
        // successfully before trying to unlink.
        if (isset($file_path_front) && file_exists($file_path_front) && empty($id_front)) {
            @unlink($file_path_front);
        }
        if (isset($file_path_back) && file_exists($file_path_back) && empty($id_back)) {
            @unlink($file_path_back);
        }
    }
} elseif (isset($_GET['success']) && $_GET['success'] == 1) {
    // This block runs after a successful redirect
    $success = true;
}


$page_title = "Addins Ambassador Program - Join Our Community";
?>

<?php include 'includes/header.php'; ?>

<!-- Hero Section -->
<section class="relative h-screen overflow-hidden">
    <div class="absolute inset-0 bg-cover bg-center" style="background-image: url('assets/img/Addinhotel.png');">
        <!-- Enhanced overlay for better text visibility -->
        <div class="absolute inset-0 bg-gradient-to-br from-black/70 via-black/60 to-black/70"></div>
        <div class="absolute inset-0 bg-gradient-to-t from-black/50 via-transparent to-black/40"></div>
    </div>

    <div class="relative z-10 h-full flex items-center">
        <div class="container mx-auto px-4">
            <div class="max-w-4xl mx-auto text-center">
                <div class="relative">
                    <!-- Semi-transparent background for text -->
                    <div class="absolute inset-0 bg-black/40 backdrop-blur-sm rounded-2xl -mx-4 px-8 py-12"></div>

                    <!-- Hero Content -->
                    <div class="relative z-10">
                        <h1 class="text-4xl md:text-6xl font-bold mb-6 leading-tight" style="text-shadow: 3px 3px 8px rgba(0, 0, 0, 0.9), 0 0 15px rgba(0, 0, 0, 0.6);">
                            Join the <span class="text-accent">Addins Ambassador</span> Program
                        </h1>
                        <p class="text-xl md:text-2xl mb-8 text-white" style="text-shadow: 2px 2px 6px rgba(0, 0, 0, 0.9);">
                            Empower your community while building your personal brand. Become a voice for exceptional culinary experiences and earn rewards for your passion.
                        </p>

                        <!-- Ambassador highlights -->
                        <div class="grid md:grid-cols-2 gap-6 mt-12 max-w-4xl mx-auto">
                            <div class="bg-white/20 backdrop-blur-sm rounded-lg p-6 border border-white/30">
                                <div class="text-3xl mb-3">
                                    <i class="fas fa-users text-blue-400" style="text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.8);"></i>
                                </div>
                                <h3 class="font-bold text-lg text-white mb-2" style="text-shadow: 1px 1px 3px rgba(0, 0, 0, 0.8);">500+ Active Ambassadors</h3>
                                <p class="text-white/80 text-sm" style="text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.7);">Join a growing community of passionate individuals</p>
                            </div>
                            <div class="bg-white/20 backdrop-blur-sm rounded-lg p-6 border border-white/30">
                                <div class="text-3xl mb-3">
                                    <i class="fas fa-star text-yellow-400" style="text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.8);"></i>
                                </div>
                                <h3 class="font-bold text-lg text-white mb-2" style="text-shadow: 1px 1px 3px rgba(0, 0, 0, 0.8);">4.9/5 Satisfaction Rate</h3>
                                <p class="text-white/80 text-sm" style="text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.7);">Our ambassadors love the program and opportunities</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Program Overview -->
<section class="py-16 bg-white">
    <div class="container mx-auto px-4">
        <div class="max-w-6xl mx-auto">
            <div class="text-center mb-16">
                <h2 class="text-3xl md:text-4xl font-bold text-dark mb-4">Why Become an Ambassador?</h2>
                <p class="text-xl text-gray-600 max-w-3xl mx-auto">
                    Our ambassador program is designed for passionate individuals who love food, community, and entrepreneurship.
                    Join a network of like-minded people making a difference in their communities.
                </p>
            </div>

            <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-8 mb-16">
                <div class="text-center group">
                    <div class="w-20 h-20 bg-primary rounded-full flex items-center justify-center mx-auto mb-4 group-hover:scale-110 transition-transform duration-300">
                        <i class="fas fa-handshake text-2xl text-white"></i>
                    </div>
                    <h3 class="text-xl font-bold text-dark mb-3">Partnership Opportunities</h3>
                    <p class="text-gray-600">Work directly with Addins Meals on Wheels to promote our services and earn competitive commissions.</p>
                </div>

                <div class="text-center group">
                    <div class="w-20 h-20 bg-secondary rounded-full flex items-center justify-center mx-auto mb-4 group-hover:scale-110 transition-transform duration-300">
                        <i class="fas fa-gift text-2xl text-white"></i>
                    </div>
                    <h3 class="text-xl font-bold text-dark mb-3">Exclusive Perks</h3>
                    <p class="text-gray-600">Enjoy complimentary meals, priority service, and special discounts on all our products and services.</p>
                </div>

                <div class="text-center group">
                    <div class="w-20 h-20 bg-accent rounded-full flex items-center justify-center mx-auto mb-4 group-hover:scale-110 transition-transform duration-300">
                        <i class="fas fa-chart-line text-2xl text-white"></i>
                    </div>
                    <h3 class="text-xl font-bold text-dark mb-3">Career Development</h3>
                    <p class="text-gray-600">Gain valuable marketing, sales, and networking skills that enhance your professional profile.</p>
                </div>

                <div class="text-center group">
                    <div class="w-20 h-20 bg-primary rounded-full flex items-center justify-center mx-auto mb-4 group-hover:scale-110 transition-transform duration-300">
                        <i class="fas fa-heart text-2xl text-white"></i>
                    </div>
                    <h3 class="text-xl font-bold text-dark mb-3">Community Impact</h3>
                    <p class="text-gray-600">Be part of something bigger - help bring quality food services to more people in your community.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Statistics Section -->
<section class="py-16 bg-light">
    <div class="container mx-auto px-4">
        <div class="max-w-6xl mx-auto">
            <div class="grid grid-cols-2 md:grid-cols-4 gap-8 text-center">
                <div class="p-6">
                    <div class="text-4xl md:text-5xl font-bold text-primary mb-2">500+</div>
                    <div class="text-gray-600 font-medium">Active Ambassadors</div>
                </div>
                <div class="p-6">
                    <div class="text-4xl md:text-5xl font-bold text-secondary mb-2">50K+</div>
                    <div class="text-gray-600 font-medium">Meals Delivered</div>
                </div>
                <div class="p-6">
                    <div class="text-4xl md:text-5xl font-bold text-accent mb-2">98%</div>
                    <div class="text-gray-600 font-medium">Satisfaction Rate</div>
                </div>
                <div class="p-6">
                    <div class="text-4xl md:text-5xl font-bold text-primary mb-2">KSh 2M+</div>
                    <div class="text-gray-600 font-medium">Ambassador Earnings</div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Application Form -->
<section class="py-16 bg-light">
    <div class="container mx-auto px-4">
        <div class="max-w-4xl mx-auto">
            <div class="bg-white rounded-2xl shadow-xl overflow-hidden">
                <div class="bg-gradient-to-r from-primary to-secondary p-8 text-white text-center">
                    <h2 class="text-3xl font-bold mb-4">Ready to Join Our Ambassador Program?</h2>
                    <p class="text-lg">Take the first step towards an exciting opportunity. Apply now and start your journey with Addins Meals on Wheels.</p>
                </div>

                <div class="p-8">
                    <?php if ($success): ?>
                        <div id="success-message" class="mb-8 p-6 bg-green-50 border border-green-200 rounded-lg">
                            <div class="flex items-center">
                                <i class="fas fa-check-circle text-green-500 text-2xl mr-4"></i>
                                <div>
                                    <h3 class="text-lg font-semibold text-green-800">Application Submitted Successfully!</h3>
                                    <p class="text-green-700">Thank you for your interest in becoming an Addins ambassador. Our team will review your application and contact you within 2-3 business days.</p>
                                </div>
                            </div>
                        </div>
                    <?php elseif ($error): ?>
                        <div id="error-message" class="mb-8 p-6 bg-red-50 border border-red-200 rounded-lg">
                            <div class="flex items-center">
                                <i class="fas fa-exclamation-circle text-red-500 text-2xl mr-4"></i>
                                <div>
                                    <h3 class="text-lg font-semibold text-red-800">Application Error</h3>
                                    <p class="text-red-700"><?php echo htmlspecialchars($error); ?></p>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>

                    <form method="POST" class="space-y-6" enctype="multipart/form-data">
                        <div class="grid md:grid-cols-2 gap-6">
                            <div>
                                <label for="name" class="block text-sm font-semibold text-gray-700 mb-2">Full Name <span class="text-red-500">*</span></label>
                                <input type="text" id="name" name="name" required
                                       value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>"
                                       class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent transition-colors">
                            </div>

                            <div>
                                <label for="email" class="block text-sm font-semibold text-gray-700 mb-2">Email Address <span class="text-red-500">*</span></label>
                                <input type="email" id="email" name="email" required
                                       value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>"
                                       class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent transition-colors">
                            </div>
                        </div>

                        <div class="grid md:grid-cols-2 gap-6">
                            <div>
                                <label for="phone" class="block text-sm font-semibold text-gray-700 mb-2">Phone Number <span class="text-red-500">*</span></label>
                                <input type="tel" id="phone" name="phone" required
                                       value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>"
                                       class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent transition-colors">
                            </div>

                            <div>
                                <label for="social_media" class="block text-sm font-semibold text-gray-700 mb-2">Social Media Handle</label>
                                <input type="text" id="social_media" name="social_media"
                                       value="<?php echo htmlspecialchars($_POST['social_media'] ?? ''); ?>"
                                       placeholder="@yourhandle or your Facebook page"
                                       class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent transition-colors">
                            </div>
                        </div>

                        <div>
                            <label for="experience" class="block text-sm font-semibold text-gray-700 mb-2">Relevant Experience</label>
                            <select id="experience" name="experience"
                                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent transition-colors">
                                <option value="">Select your experience level</option>
                                <option value="none" <?php echo ($_POST['experience'] ?? '') === 'none' ? 'selected' : ''; ?>>No prior experience</option>
                                <option value="some_sales" <?php echo ($_POST['experience'] ?? '') === 'some_sales' ? 'selected' : ''; ?>>Some sales/marketing experience</option>
                                <option value="experienced" <?php echo ($_POST['experience'] ?? '') === 'experienced' ? 'selected' : ''; ?>>Experienced in sales/marketing</option>
                                <option value="influencer" <?php echo ($_POST['experience'] ?? '') === 'influencer' ? 'selected' : ''; ?>>Social media influencer/blogger</option>
                            </select>
                        </div>

                        <div>
                            <label for="motivation" class="block text-sm font-semibold text-gray-700 mb-2">Why do you want to become an ambassador? <span class="text-red-500">*</span></label>
                            <textarea id="motivation" name="motivation" rows="4" required
                                      class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent transition-colors"
                                      placeholder="Tell us about your motivation, goals, and what you hope to achieve as an ambassador..."><?php echo htmlspecialchars($_POST['motivation'] ?? ''); ?></textarea>
                        </div>

                        <div>
                            <label for="message" class="block text-sm font-semibold text-gray-700 mb-2">Additional Information</label>
                            <textarea id="message" name="message" rows="3"
                                      class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent transition-colors"
                                      placeholder="Any additional information you'd like to share..."><?php echo htmlspecialchars($_POST['message'] ?? ''); ?></textarea>
                        </div>

                        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
                            <div class="flex items-start">
                                <i class="fas fa-info-circle text-blue-500 mt-1 mr-3"></i>
                                <div>
                                    <h4 class="font-semibold text-blue-800 mb-1">ID Card Upload Requirements</h4>
                                    <ul class="text-sm text-blue-700 space-y-1">
                                        <li>• Upload clear, high-quality photos of both sides of your ID card</li>
                                        <li>• Ensure all text and details are clearly visible and readable</li>
                                        <li>• Accepted formats: JPG, PNG, GIF (max 5MB each)</li>
                                        <li>• Make sure the photo shows the entire card without cropping important details</li>
                                    </ul>
                                </div>
                            </div>
                        </div>

                        <div class="grid md:grid-cols-2 gap-6">
                            <div>
                                <label for="id_front" class="block text-sm font-semibold text-gray-700 mb-2">ID Card Front <span class="text-red-500">*</span></label>
                                <div class="relative">
                                    <input type="file" id="id_front" name="id_front" accept="image/jpeg,image/png,image/gif" required
                                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent transition-colors file:mr-4 file:py-2 file:px-4 file:rounded-l-lg file:border-0 file:text-sm file:font-semibold file:bg-primary file:text-white hover:file:bg-primary-dark">
                                    <p class="text-xs text-gray-500 mt-1">Upload a clear photo of the front of your ID card</p>
                                </div>
                            </div>

                            <div>
                                <label for="id_back" class="block text-sm font-semibold text-gray-700 mb-2">ID Card Back <span class="text-red-500">*</span></label>
                                <div class="relative">
                                    <input type="file" id="id_back" name="id_back" accept="image/jpeg,image/png,image/gif" required
                                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent transition-colors file:mr-4 file:py-2 file:px-4 file:rounded-l-lg file:border-0 file:text-sm file:font-semibold file:bg-primary file:text-white hover:file:bg-primary-dark">
                                    <p class="text-xs text-gray-500 mt-1">Upload a clear photo of the back of your ID card</p>
                                </div>
                            </div>
                        </div>

                        <div class="bg-gray-50 rounded-lg p-4">
                            <div class="flex items-start">
                                <input type="checkbox" id="terms" name="terms" required class="mt-1 mr-3">
                                <label for="terms" class="text-sm text-gray-700">
                                    I agree to the <a href="#" class="text-primary hover:underline">Terms and Conditions</a> and
                                    <a href="#" class="text-primary hover:underline">Privacy Policy</a> of the Addins Ambassador Program.
                                </label>
                            </div>
                        </div>

                        <button type="submit" class="w-full bg-gradient-to-r from-primary to-secondary text-white font-bold py-4 px-8 rounded-lg hover:shadow-lg transform hover:-translate-y-1 transition-all duration-300 text-lg">
                            <i class="fas fa-paper-plane mr-2"></i>
                            Submit Application
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Ambassador Benefits -->
<section class="py-16 bg-white">
    <div class="container mx-auto px-4">
        <div class="max-w-6xl mx-auto">
            <div class="text-center mb-12">
                <h2 class="text-3xl md:text-4xl font-bold text-dark mb-4">Ambassador Benefits & Rewards</h2>
                <p class="text-xl text-gray-600">Comprehensive perks designed to support your success and growth</p>
            </div>

            <div class="grid md:grid-cols-2 gap-8">
                <div class="bg-light rounded-xl p-8">
                    <h3 class="text-2xl font-bold text-primary mb-6 flex items-center">
                        <i class="fas fa-money-bill-wave mr-3"></i>
                        Financial Rewards
                    </h3>
                    <ul class="space-y-4">
                        <li class="flex items-start">
                            <i class="fas fa-check text-green-500 mt-1 mr-3"></i>
                            <div>
                                <span class="font-semibold">Commission Structure:</span> Earn up to 15% commission on referred orders
                            </div>
                        </li>
                        <li class="flex items-start">
                            <i class="fas fa-check text-green-500 mt-1 mr-3"></i>
                            <div>
                                <span class="font-semibold">Monthly Bonuses:</span> Performance-based incentives for top performers
                            </div>
                        </li>
                        <li class="flex items-start">
                            <i class="fas fa-check text-green-500 mt-1 mr-3"></i>
                            <div>
                                <span class="font-semibold">Referral Program:</span> Earn bonuses for recruiting new ambassadors
                            </div>
                        </li>
                    </ul>
                </div>

                <div class="bg-light rounded-xl p-8">
                    <h3 class="text-2xl font-bold text-secondary mb-6 flex items-center">
                        <i class="fas fa-utensils mr-3"></i>
                        Culinary Perks
                    </h3>
                    <ul class="space-y-4">
                        <li class="flex items-start">
                            <i class="fas fa-check text-green-500 mt-1 mr-3"></i>
                            <div>
                                <span class="font-semibold">Free Monthly Credits:</span> KSh 5,000 worth of meals monthly
                            </div>
                        </li>
                        <li class="flex items-start">
                            <i class="fas fa-check text-green-500 mt-1 mr-3"></i>
                            <div>
                                <span class="font-semibold">Exclusive Access:</span> Try new menu items before public release
                            </div>
                        </li>
                        <li class="flex items-start">
                            <i class="fas fa-check text-green-500 mt-1 mr-3"></i>
                            <div>
                                <span class="font-semibold">Catering Discounts:</span> 50% off catering services for personal events
                            </div>
                        </li>
                    </ul>
                </div>
            </div>

            <div class="mt-8 bg-gradient-to-r from-primary to-secondary rounded-xl p-8 text-white text-center">
                <h3 class="text-2xl font-bold mb-4">Professional Development Opportunities</h3>
                <p class="text-lg mb-6">Gain real-world experience in marketing, sales, and entrepreneurship while building your personal brand.</p>
                <div class="grid md:grid-cols-3 gap-6">
                    <div>
                        <i class="fas fa-bullhorn text-3xl mb-3"></i>
                        <h4 class="font-bold mb-2">Marketing Skills</h4>
                        <p>Learn digital marketing and social media strategies</p>
                    </div>
                    <div>
                        <i class="fas fa-handshake text-3xl mb-3"></i>
                        <h4 class="font-bold mb-2">Sales Training</h4>
                        <p>Develop negotiation and customer service expertise</p>
                    </div>
                    <div>
                        <i class="fas fa-network-wired text-3xl mb-3"></i>
                        <h4 class="font-bold mb-2">Networking</h4>
                        <p>Connect with industry professionals and entrepreneurs</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Success Stories -->
<section class="py-16 bg-light">
    <div class="container mx-auto px-4">
        <div class="max-w-6xl mx-auto">
            <div class="text-center mb-12">
                <h2 class="text-3xl md:text-4xl font-bold text-dark mb-4">Success Stories</h2>
                <p class="text-xl text-gray-600">Hear from our successful ambassadors who are making an impact</p>
            </div>

            <div class="grid md:grid-cols-3 gap-8">
                <?php if (!empty($success_stories)): ?>
                    <?php foreach ($success_stories as $story): ?>
                        <div class="bg-white rounded-xl shadow-lg p-8">
                            <div class="flex items-center mb-4">
                                <?php if (!empty($story['image']) && file_exists('uploads/success_stories/' . $story['image'])): ?>
                                    <img src="uploads/success_stories/<?php echo htmlspecialchars($story['image']); ?>"
                                         alt="<?php echo htmlspecialchars($story['name']); ?>"
                                         class="w-16 h-16 rounded-full object-cover mr-4">
                                <?php else: ?>
                                    <div class="w-16 h-16 rounded-full bg-gray-200 flex items-center justify-center mr-4">
                                        <i class="fas fa-user text-2xl text-gray-400"></i>
                                    </div>
                                <?php endif; ?>
                                <div>
                                    <h4 class="font-bold text-lg"><?php echo htmlspecialchars($story['name']); ?></h4>
                                    <p class="text-gray-600"><?php echo htmlspecialchars($story['role']); ?></p>
                                </div>
                            </div>
                            <p class="text-gray-700 mb-4">"<?php echo htmlspecialchars($story['story']); ?>"</p>
                            <div class="flex text-yellow-400">
                                <?php for ($i = 0; $i < 5; $i++): ?>
                                    <i class="fas fa-star<?php echo $i < ($story['rating'] ?? 5) ? '' : '-o'; ?>"></i>
                                <?php endfor; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <!-- Fallback stories if no data in database -->
                    <div class="bg-white rounded-xl shadow-lg p-8">
                        <div class="flex items-center mb-4">
                            <div class="w-16 h-16 rounded-full bg-gray-200 flex items-center justify-center mr-4">
                                <i class="fas fa-user text-2xl text-gray-400"></i>
                            </div>
                            <div>
                                <h4 class="font-bold text-lg">Sarah Johnson</h4>
                                <p class="text-gray-600">University Student</p>
                            </div>
                        </div>
                        <p class="text-gray-700 mb-4">"Being an Addins ambassador helped me develop my communication skills and earn extra income during my studies. I've made KSh 45,000 in commissions this semester!"</p>
                        <div class="flex text-yellow-400">
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                        </div>
                    </div>

                    <div class="bg-white rounded-xl shadow-lg p-8">
                        <div class="flex items-center mb-4">
                            <div class="w-16 h-16 rounded-full bg-gray-200 flex items-center justify-center mr-4">
                                <i class="fas fa-user text-2xl text-gray-400"></i>
                            </div>
                            <div>
                                <h4 class="font-bold text-lg">Michael Chen</h4>
                                <p class="text-gray-600">Young Entrepreneur</p>
                            </div>
                        </div>
                        <p class="text-gray-700 mb-4">"The ambassador program gave me the confidence to start my own food delivery service. The training and network I gained were invaluable."</p>
                        <div class="flex text-yellow-400">
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                        </div>
                    </div>

                    <div class="bg-white rounded-xl shadow-lg p-8">
                        <div class="flex items-center mb-4">
                            <div class="w-16 h-16 rounded-full bg-gray-200 flex items-center justify-center mr-4">
                                <i class="fas fa-user text-2xl text-gray-400"></i>
                            </div>
                            <div>
                                <h4 class="font-bold text-lg">Grace Wanjiku</h4>
                                <p class="text-gray-600">Community Leader</p>
                            </div>
                        </div>
                        <p class="text-gray-700 mb-4">"Through this program, I've been able to support local events with catering while building lasting relationships in my community. Truly rewarding!"</p>
                        <div class="flex text-yellow-400">
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<!-- CTA Section -->
<section class="py-16 bg-gradient-to-br from-primary via-primary-dark to-secondary text-white">
    <div class="container mx-auto px-4 text-center">
        <div class="max-w-4xl mx-auto">
            <h2 class="text-3xl md:text-4xl font-bold mb-6">Questions About the Program?</h2>
            <p class="text-xl mb-8 text-white/90">Our ambassador support team is here to help you succeed. Get in touch for more information or assistance.</p>

            <div class="grid md:grid-cols-3 gap-8 mb-12">
                <div class="flex flex-col items-center">
                    <div class="w-16 h-16 bg-primary rounded-full flex items-center justify-center mb-4">
                        <i class="fas fa-envelope text-2xl text-white"></i>
                    </div>
                    <h3 class="font-bold mb-2">Email Support</h3>
                    <p class="text-white/80 mb-2">ambassadors@addinsmeals.com</p>
                    <p class="text-sm text-white/60">Response within 24 hours</p>
                </div>

                <div class="flex flex-col items-center">
                    <div class="w-16 h-16 bg-secondary rounded-full flex items-center justify-center mb-4">
                        <i class="fas fa-phone text-2xl text-white"></i>
                    </div>
                    <h3 class="font-bold mb-2">Phone Support</h3>
                    <p class="text-white/80 mb-2">+254 700 123 456</p>
                    <p class="text-sm text-white/60">Mon-Fri, 9AM-6PM</p>
                </div>

                <div class="flex flex-col items-center">
                    <div class="w-16 h-16 bg-accent rounded-full flex items-center justify-center mb-4">
                        <i class="fas fa-comments text-2xl text-white"></i>
                    </div>
                    <h3 class="font-bold mb-2">Live Chat</h3>
                    <p class="text-white/80 mb-2">Available on website</p>
                    <p class="text-sm text-white/60">Instant response</p>
                </div>
            </div>

            <a href="/contact.php" class="inline-flex items-center bg-primary hover:bg-primary-dark text-white font-bold py-4 px-8 rounded-lg transition-colors duration-300 text-lg shadow-lg hover:shadow-xl transform hover:-translate-y-1">
                <i class="fas fa-comments mr-2"></i>
                Contact Ambassador Support
            </a>
        </div>
    </div>
</section>

<script>
    // Scroll to success or error message on page load
    document.addEventListener('DOMContentLoaded', function() {
        <?php if ($success): ?>
            // Scroll to success message
            const successMessage = document.getElementById('success-message');
            if (successMessage) {
                successMessage.scrollIntoView({
                    behavior: 'smooth',
                    block: 'center'
                });

                // Add a subtle highlight animation
                successMessage.style.transform = 'scale(1.02)';
                setTimeout(() => {
                    successMessage.style.transform = 'scale(1)';
                }, 200);
            }
        <?php elseif ($error): ?>
            // Scroll to error message
            const errorMessage = document.getElementById('error-message');
            if (errorMessage) {
                errorMessage.scrollIntoView({
                    behavior: 'smooth',
                    block: 'center'
                });
            }
        <?php endif; ?>
    });

    // Form validation enhancement (client-side visually)
    document.addEventListener('DOMContentLoaded', function() {
        const form = document.querySelector('form');
        const submitButton = form.querySelector('button[type="submit"]');

        form.addEventListener('submit', function(e) {
            // Add loading state to button
            submitButton.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Submitting...';
            submitButton.disabled = true;

            // Show visual feedback
            const formContainer = document.querySelector('.bg-white.rounded-2xl.shadow-xl');
            if (formContainer) {
                formContainer.style.opacity = '0.7';
                formContainer.style.pointerEvents = 'none'; // Prevent interaction while submitting
            }
        });
    });
</script>

<?php include 'includes/footer.php'; ?>