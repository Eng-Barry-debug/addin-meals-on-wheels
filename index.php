<?php include 'includes/header.php';

// Get approved reviews from database
$reviews = [];
try {
    $stmt = $pdo->prepare("
        SELECT customer_name, customer_title, rating, review_text, service_type, catering_event_type, occasion_details, review_date, created_at
        FROM customer_reviews
        WHERE status = 'approved' AND service_type != 'Catering'
        ORDER BY created_at DESC
        LIMIT 6
    ");
    $stmt->execute();
    $reviews = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // If table doesn't exist or error occurs, use empty array
    $reviews = [];
}
?>

    <!-- Additional Slideshow Styles -->
    <style>
        /* Hero Slideshow Custom Styles */
        .hero-slideshow .slide-indicator {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            background: rgba(0, 0, 0, 0.3);
            color: white;
            border: none;
            padding: 12px;
            border-radius: 50%;
            cursor: pointer;
            transition: all 0.3s ease;
            z-index: 20;
        }

        .hero-slideshow .slide-indicator:hover {
            background: rgba(0, 0, 0, 0.6);
        }

        .hero-slideshow .service-feature {
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .hero-slideshow .cta-button {
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
        }

        .hero-slideshow .cta-button:hover {
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.3);
        }

        /* Responsive adjustments for slideshow */
        @media (max-width: 768px) {
            .hero-slideshow .slide-content h1 {
                font-size: 2.5rem !important;
            }

            .hero-slideshow .slide-content p {
                font-size: 1rem !important;
            }

            .hero-slideshow .service-features {
                grid-template-columns: 1fr !important;
                gap: 1rem !important;
            }
        }

        /* Ensure slideshow takes full height */
        .hero-slideshow {
            height: 100vh;
            position: relative;
            overflow: hidden;
        }
    </style>

    <!-- Hero Slideshow Section -->
    <section class="relative h-screen overflow-hidden">
        <!-- Slideshow Container -->
        <div x-data="{
            currentSlide: 0,
            totalSlides: 5,
            slides: [
                {
                    title: 'Delicious Meals Delivered',
                    subtitle: 'Experience the taste of home-cooked meals',
                    description: 'Fresh ingredients, chef-prepared meals delivered hot and fresh to your doorstep. From our kitchen to your table in minutes.',
                    buttonText: 'Order Now',
                    buttonLink: '/menu.php',
                    bgImage: 'assets/img/freshfoods.png'
                },
                {
                    title: 'Premium Catering Services',
                    subtitle: 'Perfect for events, parties, and gatherings',
                    description: 'Professional catering for weddings, corporate events, birthdays, and celebrations. Custom menus tailored to your needs.',
                    buttonText: 'Book Catering',
                    buttonLink: '/catering.php',
                    bgImage: 'assets/img/catering2.png'
                },
                {
                    title: 'Addins Ambassador Program',
                    subtitle: 'Empowering students and entrepreneurs',
                    description: 'Join our ambassador program and become part of a community that supports growth, learning, and business development.',
                    buttonText: 'Join Program',
                    buttonLink: '/ambassador.php',
                    bgImage: 'assets/img/Addinhotel.png'
                },
                {
                    title: 'Recipes & Blog Content',
                    subtitle: 'Discover culinary inspiration and tips',
                    description: 'Explore our collection of recipes, cooking tips, and food stories. Learn from our chefs and community members.',
                    buttonText: 'Read Blog',
                    buttonLink: '/blog.php',
                    bgImage: 'assets/img/cookies.png'
                },
                {
                    title: '24/7 Customer Support',
                    subtitle: 'Always here to help you succeed',
                    description: 'Get instant assistance with orders, catering bookings, and any questions. Our support team is available round the clock.',
                    buttonText: 'Get Support',
                    buttonLink: '/contact.php',
                    bgImage: 'assets/img/delivery.png'
                }
            ],
            nextSlide() {
                this.currentSlide = (this.currentSlide + 1) % this.totalSlides;
            },
            prevSlide() {
                this.currentSlide = (this.currentSlide - 1 + this.totalSlides) % this.totalSlides;
            },
            goToSlide(index) {
                this.currentSlide = index;
            }
        }"
        x-init="
            // Auto-advance slides every 6 seconds
            setInterval(() => { $data.nextSlide(); }, 6000);
        "
        class="relative h-full hero-slideshow">

            <!-- Slides -->
            <template x-for="(slide, index) in slides" :key="index">
                <div x-show="currentSlide === index"
                     x-transition:enter="transition-opacity duration-1000"
                     x-transition:enter-start="opacity-0"
                     x-transition:enter-end="opacity-100"
                     class="absolute inset-0 bg-cover bg-center flex items-center"
                     :style="`background-image: linear-gradient(rgba(26, 26, 26, 0.85), rgba(26, 26, 26, 0.75)), url('${slide.bgImage}')`">

                    <div class="container mx-auto px-4 text-center text-white">
                        <div class="max-w-4xl mx-auto">
                            <!-- Slide Indicator -->
                            <div class="mb-6 flex justify-center space-x-2">
                                <template x-for="(s, i) in slides" :key="i">
                                    <button @click="goToSlide(i)"
                                            :class="currentSlide === i ? 'bg-white' : 'bg-white/50 hover:bg-white/75'"
                                            class="w-3 h-3 rounded-full transition-all duration-300"></button>
                                </template>
                            </div>

                            <!-- Slide Content with Enhanced Background -->
                            <div class="relative">
                                <!-- Background overlay for better text readability -->
                                <div class="absolute inset-0 bg-black/60 backdrop-blur-md rounded-2xl -mx-4 px-8 py-12"></div>

                                <!-- Slide Content -->
                                <div class="relative z-10">
                                    <h1 class="text-4xl md:text-6xl font-bold mb-4 text-white" x-text="slide.title" style="text-shadow: 3px 3px 8px rgba(0, 0, 0, 0.9), 0 0 15px rgba(0, 0, 0, 0.6);"></h1>
                                    <p class="text-xl md:text-2xl mb-6 font-medium text-white" x-text="slide.subtitle" style="text-shadow: 2px 2px 6px rgba(0, 0, 0, 0.9);"></p>
                                    <p class="text-lg md:text-xl mb-8 max-w-3xl mx-auto leading-relaxed text-white/90" x-text="slide.description" style="text-shadow: 1px 1px 4px rgba(0, 0, 0, 0.8);"></p>
                                </div>
                            </div>

                            <!-- CTA Buttons -->
                            <div class="flex flex-col sm:flex-row justify-center gap-4 mb-8">
                                <a :href="slide.buttonLink" class="bg-primary hover:bg-primary-dark text-white px-8 py-4 rounded-lg text-lg font-semibold transition-all duration-300 transform hover:scale-105 shadow-lg">
                                    <span x-text="slide.buttonText"></span>
                                </a>
                                <button @click="nextSlide()" class="border-2 border-white text-white hover:bg-white hover:text-dark px-8 py-4 rounded-lg text-lg font-semibold transition-all duration-300">
                                    Explore More
                                </button>
                            </div>

                            <!-- Service Features -->
                            <div class="grid md:grid-cols-3 gap-6 mt-12 max-w-4xl mx-auto service-features">
                                <div class="bg-black/70 backdrop-blur-md rounded-lg p-6 service-feature border border-white/30">
                                    <div class="text-3xl mb-3 text-white">
                                        <i :class="index === 0 ? 'fas fa-utensils' : index === 1 ? 'fas fa-birthday-cake' : index === 2 ? 'fas fa-handshake' : index === 3 ? 'fas fa-book-open' : 'fas fa-headset'" style="text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.8);"></i>
                                    </div>
                                    <h3 class="font-bold text-base text-white leading-tight" x-text="index === 0 ? 'Fresh Ingredients' : index === 1 ? 'Custom Menus' : index === 2 ? 'Growth Opportunities' : index === 3 ? 'Expert Tips' : '24/7 Support'" style="text-shadow: 1px 1px 3px rgba(0, 0, 0, 0.8);"></h3>
                                </div>
                                <div class="bg-black/70 backdrop-blur-md rounded-lg p-6 service-feature border border-white/30">
                                    <div class="text-3xl mb-3 text-white">
                                        <i :class="index === 0 ? 'fas fa-clock' : index === 1 ? 'fas fa-map-marker-alt' : index === 2 ? 'fas fa-users' : index === 3 ? 'fas fa-camera' : 'fas fa-comments'" style="text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.8);"></i>
                                    </div>
                                    <h3 class="font-bold text-base text-white leading-tight" x-text="index === 0 ? 'Quick Delivery' : index === 1 ? 'Event Planning' : index === 2 ? 'Community' : index === 3 ? 'Visual Content' : 'Instant Help'" style="text-shadow: 1px 1px 3px rgba(0, 0, 0, 0.8);"></h3>
                                </div>
                                <div class="bg-black/70 backdrop-blur-md rounded-lg p-6 service-feature border border-white/30">
                                    <div class="text-3xl mb-3 text-white">
                                        <i :class="index === 0 ? 'fas fa-star' : index === 1 ? 'fas fa-award' : index === 2 ? 'fas fa-graduation-cap' : index === 3 ? 'fas fa-lightbulb' : 'fas fa-shield-alt'" style="text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.8);"></i>
                                    </div>
                                    <h3 class="font-bold text-base text-white leading-tight" x-text="index === 0 ? 'Premium Quality' : index === 1 ? 'Professional Service' : index === 2 ? 'Learning Resources' : index === 3 ? 'Inspiration' : 'Secure Platform'" style="text-shadow: 1px 1px 3px rgba(0, 0, 0, 0.8);"></h3>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </template>

            <!-- Navigation Arrows -->
            <button @click="prevSlide()"
                    class="absolute left-4 top-1/2 transform -translate-y-1/2 bg-black/30 hover:bg-black/50 text-white p-3 rounded-full transition-all duration-300 z-10">
                <i class="fas fa-chevron-left text-xl"></i>
            </button>
            <button @click="nextSlide()"
                    class="absolute right-4 top-1/2 transform -translate-y-1/2 bg-black/30 hover:bg-black/50 text-white p-3 rounded-full transition-all duration-300 z-10">
                <i class="fas fa-chevron-right text-xl"></i>
            </button>

            <!-- Scroll Indicator -->
            <div class="absolute bottom-8 left-0 right-0 text-center">
                <a href="#featured" class="text-white hover:text-secondary transition-colors duration-300 animate-bounce">
                    <i class="fas fa-chevron-down text-2xl"></i>
                </a>
            </div>
        </div>
    </section>

    <!-- Featured Section -->
    <section id="featured" class="py-16 bg-light">
        <div class="container mx-auto px-4">
            <div class="text-center mb-12">
                <h2 class="text-3xl font-bold mb-4">Our Specialties</h2>
                <div class="w-20 h-1 bg-primary mx-auto"></div>
            </div>

            <div class="grid md:grid-cols-3 gap-8">
                <!-- Featured Item 1 -->
                <div class="card hover:shadow-xl transition-shadow">
                    <img src="assets/img/freshfoods.png" alt="Gourmet Meals" class="w-full h-64 object-cover rounded-t-lg" onerror="this.src='/assets/img/placeholder-food.jpg'; this.alt='Gourmet Meals Placeholder';">
                    <div class="p-6">
                        <h3 class="text-xl font-bold mb-2">Gourmet Meals</h3>
                        <p class="text-gray-600 mb-4">Chef-prepared meals made with locally-sourced ingredients.</p>
                        <a href="/menu.php" class="text-primary font-medium hover:text-secondary">View Menu →</a>
                    </div>
                </div>

                <!-- Featured Item 2 -->
                <div class="card hover:shadow-xl transition-shadow">
                    <img src="assets/img/catering2.png" alt="Catering Service" class="w-full h-64 object-cover rounded-t-lg" onerror="this.src='/assets/img/placeholder-catering.jpg'; this.alt='Catering Service Placeholder';">
                    <div class="p-6">
                        <h3 class="text-xl font-bold mb-2">Catering Services</h3>
                        <p class="text-gray-600 mb-4">Perfect for events, parties, and corporate gatherings.</p>
                        <a href="/catering.php" class="text-primary font-medium hover:text-secondary">Learn More →</a>
                    </div>
                </div>

                <!-- Featured Item 3 -->
                <div class="card hover:shadow-xl transition-shadow">
                    <img src="assets/img/pizza.png" alt="Meal Plans" class="w-full h-64 object-cover rounded-t-lg" onerror="this.src='/assets/img/placeholder-meal-plans.jpg'; this.alt='Meal Plans Placeholder';">
                    <div class="p-6">
                        <h3 class="text-xl font-bold mb-2">Weekly Plans</h3>
                        <p class="text-gray-600 mb-4">Subscribe and save with our weekly meal plans.</p>
                        <a href="/menu.php#weekly-plans" class="text-primary font-medium hover:text-secondary">View Plans →</a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- How It Works -->
    <section class="py-16 bg-white">
        <div class="container mx-auto px-4">
            <div class="text-center mb-12">
                <h2 class="text-3xl font-bold mb-4">How It Works</h2>
                <div class="w-20 h-1 bg-primary mx-auto"></div>
            </div>

            <div class="grid md:grid-cols-3 gap-8 max-w-5xl mx-auto">
                <!-- Step 1 -->
                <div class="text-center p-6">
                    <div class="bg-primary text-white w-16 h-16 rounded-full flex items-center justify-center text-2xl font-bold mx-auto mb-4">1</div>
                    <h3 class="text-xl font-bold mb-2">Choose Your Meals</h3>
                    <p class="text-gray-600">Browse our delicious menu and select your favorite dishes.</p>
                </div>

                <!-- Step 2 -->
                <div class="text-center p-6">
                    <div class="bg-primary text-white w-16 h-16 rounded-full flex items-center justify-center text-2xl font-bold mx-auto mb-4">2</div>
                    <h3 class="text-xl font-bold mb-2">We Prepare with Care</h3>
                    <p class="text-gray-600">Our chefs prepare your meals using fresh, high-quality ingredients.</p>
                </div>

                <!-- Step 3 -->
                <div class="text-center p-6">
                    <div class="bg-primary text-white w-16 h-16 rounded-full flex items-center justify-center text-2xl font-bold mx-auto mb-4">3</div>
                    <h3 class="text-xl font-bold mb-2">Fast Delivery</h3>
                    <p class="text-gray-600">Enjoy your meal delivered hot and fresh to your doorstep.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Testimonials -->
    <section class="py-16 bg-light">
        <div class="container mx-auto px-4">
            <div class="text-center mb-12">
                <h2 class="text-3xl font-bold mb-4">What Our Customers Say</h2>
                <div class="w-20 h-1 bg-primary mx-auto"></div>
            </div>

            <div class="grid md:grid-cols-3 gap-8">
                <?php if (!empty($reviews)): ?>
                    <?php foreach (array_slice($reviews, 0, 3) as $review): ?>
                        <div class="bg-white p-6 rounded-lg shadow-md">
                            <div class="flex items-start mb-4">
                                <div class="text-2xl text-secondary mr-3">"</div>
                                <p class="text-gray-600 italic flex-1"><?php echo htmlspecialchars(substr($review['review_text'], 0, 120) . (strlen($review['review_text']) > 120 ? '...' : '')); ?></p>
                            </div>
                            <div class="flex items-center">
                                <div class="w-12 h-12 bg-primary/10 rounded-full mr-4 flex items-center justify-center">
                                    <span class="text-primary font-bold text-lg"><?php echo strtoupper(substr($review['customer_name'], 0, 1)); ?></span>
                                </div>
                                <div>
                                    <h4 class="font-bold"><?php echo htmlspecialchars($review['customer_name']); ?></h4>
                                    <?php if (!empty($review['customer_title'])): ?>
                                        <p class="text-primary text-sm font-medium"><?php echo htmlspecialchars($review['customer_title']); ?></p>
                                    <?php endif; ?>
                                    <div class="flex text-yellow-400">
                                        <?php for ($i = 1; $i <= 5; $i++): ?>
                                            <i class="fas fa-star <?php echo $i <= $review['rating'] ? '' : 'text-gray-300'; ?>"></i>
                                        <?php endfor; ?>
                                    </div>
                                    <?php if (!empty($review['service_type']) && $review['service_type'] !== 'Catering'): ?>
                                        <p class="text-xs text-gray-500 mt-1">
                                            <?php echo htmlspecialchars($review['service_type']); ?>
                                            <?php if (!empty($review['catering_event_type'])): ?>
                                                • <?php echo htmlspecialchars($review['catering_event_type']); ?>
                                            <?php endif; ?>
                                            <?php if (!empty($review['occasion_details'])): ?>
                                                • <?php echo htmlspecialchars($review['occasion_details']); ?>
                                            <?php endif; ?>
                                        </p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <!-- Fallback testimonials if no reviews in database -->
                    <!-- Testimonial 1 -->
                    <div class="bg-white p-6 rounded-lg shadow-md">
                        <div class="flex items-center mb-4">
                            <div class="text-2xl text-secondary mr-2">"</div>
                            <p class="text-gray-600 italic">The food is absolutely delicious! I order every week and I'm never disappointed.</p>
                        </div>
                        <div class="flex items-center">
                            <div class="w-12 h-12 bg-gray-300 rounded-full mr-4"></div>
                            <div>
                                <h4 class="font-bold">Sarah Johnson</h4>
                                <div class="flex text-yellow-400">
                                    <i class="fas fa-star"></i>
                                    <i class="fas fa-star"></i>
                                    <i class="fas fa-star"></i>
                                    <i class="fas fa-star"></i>
                                    <i class="fas fa-star"></i>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Testimonial 2 -->
                    <div class="bg-white p-6 rounded-lg shadow-md">
                        <div class="flex items-center mb-4">
                            <div class="text-2xl text-secondary mr-2">"</div>
                            <p class="text-gray-600 italic">Perfect solution for our busy family. The kids love the meals too!</p>
                        </div>
                        <div class="flex items-center">
                            <div class="w-12 h-12 bg-gray-300 rounded-full mr-4"></div>
                            <div>
                                <h4 class="font-bold">Michael Chen</h4>
                                <div class="flex text-yellow-400">
                                    <i class="fas fa-star"></i>
                                    <i class="fas fa-star"></i>
                                    <i class="fas fa-star"></i>
                                    <i class="fas fa-star"></i>
                                    <i class="fas fa-star-half-alt"></i>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Testimonial 3 -->
                    <div class="bg-white p-6 rounded-lg shadow-md">
                        <div class="flex items-center mb-4">
                            <div class="text-2xl text-secondary mr-2">"</div>
                            <p class="text-gray-600 italic">Used their catering service for my daughter's birthday. The food was a hit with all the guests!</p>
                        </div>
                        <div class="flex items-center">
                            <div class="w-12 h-12 bg-gray-300 rounded-full mr-4"></div>
                            <div>
                                <h4 class="font-bold">Emily Rodriguez</h4>
                                <div class="flex text-yellow-400">
                                    <i class="fas fa-star"></i>
                                    <i class="fas fa-star"></i>
                                    <i class="fas fa-star"></i>
                                    <i class="fas fa-star"></i>
                                    <i class="fas fa-star"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="py-16 bg-primary text-white">
        <div class="container mx-auto px-4 text-center">
            <h2 class="text-3xl font-bold mb-6">Ready to Try Our Delicious Meals?</h2>
            <p class="text-xl mb-8 max-w-2xl mx-auto">Join hundreds of satisfied customers enjoying our meals today!</p>
            <a href="/menu.php" class="btn btn-secondary px-8 py-3 text-lg">Order Now</a>
        </div>
    </section>

<?php include 'includes/footer.php'; ?>