<?php
$page_title = "About Us - Addins Meals on Wheels";
include 'includes/header.php';
?>

<!-- 1. Hero Section -->
<section class="relative h-screen overflow-hidden">
    <div class="absolute inset-0 bg-cover bg-center" style="background-image: url('assets/img/Addinkitchen.png');">
        <!-- Enhanced overlay for better text visibility -->
        <div class="absolute inset-0 bg-gradient-to-br from-black/90 via-black/80 to-black/85"></div>
        <div class="absolute inset-0 bg-gradient-to-t from-black/70 via-black/30 to-black/60"></div>
        <!-- Additional brand color overlay -->
        <div class="absolute inset-0 bg-primary/10"></div>
    </div>

    <div class="relative z-10 h-full flex items-center justify-center">
        <div class="container mx-auto px-4 text-center text-white">
            <!-- Background overlay for text readability -->
            <div class="max-w-5xl mx-auto">
                <div class="relative">
                    <!-- Semi-transparent background for text -->
                    <div class="absolute inset-0 bg-black/40 backdrop-blur-sm rounded-3xl -mx-6 px-8 py-16"></div>

                    <!-- Hero Content -->
                    <div class="relative z-10">
                        <!-- Main Heading -->
                        <h1 class="text-5xl md:text-7xl font-bold mb-8 leading-tight" style="text-shadow: 4px 4px 10px rgba(0, 0, 0, 0.9), 0 0 20px rgba(0, 0, 0, 0.7);">
                            Our <span class="text-accent">Story</span>
                        </h1>

                        <!-- Mission Statement -->
                        <div class="mb-8">
                            <p class="text-xl md:text-3xl font-medium mb-4" style="text-shadow: 3px 3px 8px rgba(0, 0, 0, 0.9);">
                                Passion for cooking, driven by community
                            </p>

                            <!-- Mother's Touch Motto -->
                            <div class="inline-block bg-gradient-to-r from-accent/20 to-primary/20 backdrop-blur-sm border border-accent/30 rounded-full px-6 py-3 mx-auto">
                                <p class="text-lg md:text-xl font-bold text-accent mb-2" style="text-shadow: 2px 2px 6px rgba(0, 0, 0, 0.8);">
                                    ✨ Mother's Taste, Touching Every Heart ✨
                                </p>
                                <p class="text-sm text-white/90" style="text-shadow: 1px 1px 3px rgba(0, 0, 0, 0.7);">
                                    Every meal crafted with love, served with care
                                </p>
                            </div>
                        </div>

                        <!-- Enhanced Story highlights -->
                        <div class="grid md:grid-cols-3 gap-8 mt-16 max-w-5xl mx-auto">
                            <div class="bg-white/15 backdrop-blur-sm rounded-xl p-8 border border-white/20 hover:bg-white/20 transition-all duration-300 transform hover:-translate-y-2">
                                <div class="text-4xl mb-4">
                                    <i class="fas fa-heart text-red-400" style="text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.8);"></i>
                                </div>
                                <h3 class="font-bold text-lg text-white mb-3" style="text-shadow: 1px 1px 3px rgba(0, 0, 0, 0.8);">Community First</h3>
                                <p class="text-white/90 text-sm leading-relaxed" style="text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.7);">Supporting local families with every meal we serve</p>
                            </div>
                            <div class="bg-white/15 backdrop-blur-sm rounded-xl p-8 border border-white/20 hover:bg-white/20 transition-all duration-300 transform hover:-translate-y-2">
                                <div class="text-4xl mb-4">
                                    <i class="fas fa-seedling text-green-400" style="text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.8);"></i>
                                </div>
                                <h3 class="font-bold text-lg text-white mb-3" style="text-shadow: 1px 1px 3px rgba(0, 0, 0, 0.8);">Fresh & Local</h3>
                                <p class="text-white/90 text-sm leading-relaxed" style="text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.7);">Sourcing the finest ingredients from local farmers</p>
                            </div>
                            <div class="bg-white/15 backdrop-blur-sm rounded-xl p-8 border border-white/20 hover:bg-white/20 transition-all duration-300 transform hover:-translate-y-2">
                                <div class="text-4xl mb-4">
                                    <i class="fas fa-users text-blue-400" style="text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.8);"></i>
                                </div>
                                <h3 class="font-bold text-lg text-white mb-3" style="text-shadow: 1px 1px 3px rgba(0, 0, 0, 0.8);">Youth Empowerment</h3>
                                <p class="text-white/90 text-sm leading-relaxed" style="text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.7);">Creating opportunities for young entrepreneurs</p>
                            </div>
                        </div>

                        <!-- Call to Action -->
                        <div class="mt-12">
                            <a href="#story" class="inline-flex items-center px-8 py-4 bg-gradient-to-r from-primary to-accent text-white font-bold rounded-full hover:opacity-90 transition-all transform hover:-translate-y-1 shadow-lg hover:shadow-xl">
                                <i class="fas fa-chevron-down mr-3 animate-bounce"></i>
                                Discover Our Journey
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- 2. Company Story -->
<section class="py-16 bg-white">
    <div class="container mx-auto px-4">
        <div class="flex flex-col md:flex-row items-center">
            <div class="md:w-1/2 mb-8 md:mb-0 md:pr-8">
                <img src="assets/img/story.png" alt="Our Story" class="rounded-lg shadow-lg w-full h-auto">
            </div>
            <div class="md:w-1/2">
                <h2 class="text-3xl font-bold text-[#1A1A1A] mb-6">Our Story</h2>
                <p class="text-[#212121] mb-4">
                    Addins Meals on Wheels is a food business born out of a passion for cooking, entrepreneurship and a desire to bring love and care through food. Our journey began in campus days with small ventures like chapati sales, later expanding to pizza, cookies, cupcakes, and catering services. We were officially registered in September 2024.
                </p>
                <p class="text-[#212121] mb-6">
                    We are rooted in values of integrity, excellence and glorifying Christ through business, bringing the nurturing touch of a mother in every bite.
                </p>

                <!-- The Meaning of Addins -->
                <div class="bg-[#F5E6D3] p-6 rounded-lg mb-6">
                    <h3 class="text-xl font-bold text-[#C1272D] mb-3">The Meaning of Addins</h3>
                    <p class="text-[#212121] mb-3">
                        The name Addins is inspired by Adino the Spearman, one of King David's mighty men (2 Samuel 23:8). Adino was known for his unmatched courage, strength and resilience, defeating 800 men in a single encounter.
                    </p>
                    <p class="text-[#212121]">
                        At Addins Meals on Wheels, we draw inspiration from this legacy of strength and perseverance, combining it with the nurturing heart of a mother to deliver food made with integrity, love and excellence.
                    </p>
                </div>

                <!-- Vision & Mission -->
                <div class="grid md:grid-cols-2 gap-6 mb-8">
                    <div class="bg-white p-6 rounded-lg shadow-md">
                        <h3 class="text-xl font-bold text-[#C1272D] mb-3">Our Vision</h3>
                        <p class="text-[#212121]">To build a globally recognized food brand that not only delivers quality meals but also nurtures lives, creates opportunities for young people and glorifies God through business.</p>
                    </div>
                    <div class="bg-white p-6 rounded-lg shadow-md">
                        <h3 class="text-xl font-bold text-[#C1272D] mb-3">Our Mission</h3>
                        <p class="text-[#212121]">To provide delicious, high-quality and affordable meals while creating opportunities for students and young entrepreneurs to grow, learn, and earn through the Addins Ambassadors program.</p>
                    </div>
                </div>
                <div class="flex flex-wrap gap-4
                ">
                    <div class="bg-[#F5E6D3] p-4 rounded-lg">
                        <h3 class="font-bold text-[#C1272D]">50+</h3>
                        <p class="text-sm">Menu Items</p>
                    </div>
                    <div class="bg-[#F5E6D3] p-4 rounded-lg">
                        <h3 class="font-bold text-[#C1272D]">5+</h3>
                        <p class="text-sm">Years Serving</p>
                    </div>
                    <div class="bg-[#F5E6D3] p-4 rounded-lg">
                        <h3 class="font-bold text-[#C1272D]">10K+</h3>
                        <p class="text-sm">Happy Customers</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- 3. Mission, Vision & Values -->
<section class="py-16 bg-[#F5E6D3]">
    <div class="container mx-auto px-4">
        <div class="text-center mb-12">
            <h2 class="text-3xl font-bold text-[#1A1A1A] mb-4">Our Core Values</h2>
            <div class="w-20 h-1 bg-[#C1272D] mx-auto"></div>
        </div>
        
        <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-6">
            <!-- Integrity -->
            <div class="bg-white p-6 rounded-lg shadow-md text-center hover:shadow-lg transition-shadow">
                <div class="bg-[#C1272D] w-16 h-16 flex items-center justify-center rounded-full mx-auto mb-4">
                    <i class="fas fa-shield-alt text-white text-2xl"></i>
                </div>
                <h3 class="text-lg font-bold text-[#1A1A1A] mb-2">Integrity</h3>
                <p class="text-[#212121] text-sm">Doing business with honesty and fairness in all our dealings.</p>
            </div>
            
            <!-- Excellence -->
            <div class="bg-white p-6 rounded-lg shadow-md text-center hover:shadow-lg transition-shadow">
                <div class="bg-[#D4AF37] w-16 h-16 flex items-center justify-center rounded-full mx-auto mb-4">
                    <i class="fas fa-star text-white text-2xl"></i>
                </div>
                <h3 class="text-lg font-bold text-[#1A1A1A] mb-2">Excellence</h3>
                <p class="text-[#212121] text-sm">Delivering quality in every product and service we provide.</p>
            </div>
            
            <!-- Nurture -->
            <div class="bg-white p-6 rounded-lg shadow-md text-center hover:shadow-lg transition-shadow">
                <div class="bg-[#2E5E3A] w-16 h-16 flex items-center justify-center rounded-full mx-auto mb-4">
                    <i class="fas fa-heart text-white text-2xl"></i>
                </div>
                <h3 class="text-lg font-bold text-[#1A1A1A] mb-2">Nurture</h3>
                <p class="text-[#212121] text-sm">Sharing motherly care and love through every meal we serve.</p>
            </div>
            
            <!-- Faith -->
            <div class="bg-white p-6 rounded-lg shadow-md text-center hover:shadow-lg transition-shadow">
                <div class="bg-[#1A1A1A] w-16 h-16 flex items-center justify-center rounded-full mx-auto mb-4">
                    <i class="fas fa-praying-hands text-white text-2xl"></i>
                </div>
                <h3 class="text-lg font-bold text-[#1A1A1A] mb-2">Faith</h3>
                <p class="text-[#212121] text-sm">Glorifying Christ in all we do and conducting business with faith.</p>
            </div>
            
            <!-- Innovation -->
            <div class="bg-white p-6 rounded-lg shadow-md text-center hover:shadow-lg transition-shadow">
                <div class="bg-[#C1272D] w-16 h-16 flex items-center justify-center rounded-full mx-auto mb-4">
                    <i class="fas fa-lightbulb text-white text-2xl"></i>
                </div>
                <h3 class="text-lg font-bold text-[#1A1A1A] mb-2">Innovation</h3>
                <p class="text-[#212121] text-sm">Adapting to market needs and embracing technology like AI for future growth.</p>
            </div>
            
            <!-- Teamwork -->
            <div class="bg-white p-6 rounded-lg shadow-md text-center hover:shadow-lg transition-shadow">
                <div class="bg-[#D4AF37] w-16 h-16 flex items-center justify-center rounded-full mx-auto mb-4">
                    <i class="fas fa-users text-white text-2xl"></i>
                </div>
                <h3 class="text-lg font-bold text-[#1A1A1A] mb-2">Teamwork</h3>
                <p class="text-[#212121] text-sm">Building strong internal and external partnerships for mutual success.</p>
            </div>
            
            <!-- Customer Delight -->
            <div class="bg-white p-6 rounded-lg shadow-md text-center hover:shadow-lg transition-shadow">
                <div class="bg-[#2E5E3A] w-16 h-16 flex items-center justify-center rounded-full mx-auto mb-4">
                    <i class="fas fa-smile text-white text-2xl"></i>
                </div>
                <h3 class="text-lg font-bold text-[#1A1A1A] mb-2">Customer Delight</h3>
                <p class="text-[#212121] text-sm">Making every bite enjoyable and every experience memorable.</p>
            </div>
            
            <!-- Growth Mindset -->
            <div class="bg-white p-6 rounded-lg shadow-md text-center hover:shadow-lg transition-shadow">
                <div class="bg-[#1A1A1A] w-16 h-16 flex items-center justify-center rounded-full mx-auto mb-4">
                    <i class="fas fa-chart-line text-white text-2xl"></i>
                </div>
                <h3 class="text-lg font-bold text-[#1A1A1A] mb-2">Growth Mindset</h3>
                <p class="text-[#212121] text-sm">Encouraging continuous learning, development and progress for all.</p>
            </div>
        </div>
    </div>
</section>

<!-- 4. Meet the Team -->
<section class="py-16 bg-white">
    <div class="container mx-auto px-4">
        <div class="text-center mb-12">
            <h2 class="text-3xl font-bold text-[#1A1A1A] mb-4">Meet Our Team</h2>
            <p class="text-[#212121] max-w-2xl mx-auto">Passionate individuals dedicated to bringing you the best dining experience</p>
            <div class="w-20 h-1 bg-[#C1272D] mx-auto mt-4"></div>
        </div>
        
        <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-8">
            <!-- Team Member 1 -->
            <div class="bg-white rounded-lg overflow-hidden shadow-md hover:shadow-lg transition-shadow">
                <div class="h-64 bg-gray-200 flex items-center justify-center">
                    <i class="fas fa-user text-6xl text-gray-400"></i>
                </div>
                <div class="p-6 text-center">
                    <h3 class="text-xl font-bold text-[#1A1A1A] mb-1">Sarah Johnson</h3>
                    <p class="text-[#C1272D] font-medium mb-3">Head Chef</p>
                    <p class="text-[#212121] text-sm">With 15+ years of culinary experience, Sarah brings creativity and passion to every dish.</p>
                </div>
            </div>
            
            <!-- Team Member 2 -->
            <div class="bg-white rounded-lg overflow-hidden shadow-md hover:shadow-lg transition-shadow">
                <div class="h-64 bg-gray-200 flex items-center justify-center">
                    <i class="fas fa-user text-6xl text-gray-400"></i>
                </div>
                <div class="p-6 text-center">
                    <h3 class="text-xl font-bold text-[#1A1A1A] mb-1">Michael Chen</h3>
                    <p class="text-[#C1272D] font-medium mb-3">Operations Manager</p>
                    <p class="text-[#212121] text-sm">Ensuring smooth operations and exceptional customer service.</p>
                </div>
            </div>
            
            <!-- Team Member 3 -->
            <div class="bg-white rounded-lg overflow-hidden shadow-md hover:shadow-lg transition-shadow">
                <div class="h-64 bg-gray-200 flex items-center justify-center">
                    <i class="fas fa-user text-6xl text-gray-400"></i>
                </div>
                <div class="p-6 text-center">
                    <h3 class="text-xl font-bold text-[#1A1A1A] mb-1">Elena Rodriguez</h3>
                    <p class="text-[#C1272D] font-medium mb-3">Pastry Chef</p>
                    <p class="text-[#212121] text-sm">Creates delightful desserts that keep our customers coming back for more.</p>
                </div>
            </div>
            
            <!-- Team Member 4 -->
            <div class="bg-white rounded-lg overflow-hidden shadow-md hover:shadow-lg transition-shadow">
                <div class="h-64 bg-gray-200 flex items-center justify-center">
                    <i class="fas fa-user text-6xl text-gray-400"></i>
                </div>
                <div class="p-6 text-center">
                    <h3 class="text-xl font-bold text-[#1A1A1A] mb-1">David Kim</h3>
                    <p class="text-[#C1272D] font-medium mb-3">Youth Program Director</p>
                    <p class="text-[#212121] text-sm">Leads our initiative to empower at-risk youth through culinary training.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- 5. Why Choose Us -->
<section class="py-16 bg-[#F5E6D3]">
    <div class="container mx-auto px-4">
        <div class="text-center mb-12">
            <h2 class="text-3xl font-bold text-[#1A1A1A] mb-4">Our Products & Services</h2>
            <div class="w-20 h-1 bg-[#C1272D] mx-auto"></div>
        </div>
        
        <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-8">
            <!-- Pizza -->
            <div class="bg-white p-6 rounded-lg shadow-md text-center hover:shadow-lg transition-transform hover:-translate-y-1">
                <div class="w-16 h-16 bg-[#C1272D] rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-pizza-slice text-white text-2xl"></i>
                </div>
                <h3 class="text-xl font-bold text-[#1A1A1A] mb-2">Pizza</h3>
                <p class="text-[#212121]">Unique recipes made with homemade cheese and fresh ingredients.</p>
            </div>
            
            <!-- Cookies & Cupcakes -->
            <div class="bg-white p-6 rounded-lg shadow-md text-center hover:shadow-lg transition-transform hover:-translate-y-1">
                <div class="w-16 h-16 bg-[#D4AF37] rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-cookie-bite text-white text-2xl"></i>
                </div>
                <h3 class="text-xl font-bold text-[#1A1A1A] mb-2">Cookies & Cupcakes</h3>
                <p class="text-[#212121]">Our current best-sellers in the local market, baked with love.</p>
            </div>
            
            <!-- Catering -->
            <div class="bg-white p-6 rounded-lg shadow-md text-center hover:shadow-lg transition-transform hover:-translate-y-1">
                <div class="w-16 h-16 bg-[#2E5E3A] rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-utensils text-white text-2xl"></i>
                </div>
                <h3 class="text-xl font-bold text-[#1A1A1A] mb-2">Catering Services</h3>
                <p class="text-[#212121]">Full-service catering for events, parties, and corporate functions.</p>
            </div>
            
            <!-- Future Plans -->
            <div class="bg-white p-6 rounded-lg shadow-md text-center hover:shadow-lg transition-transform hover:-translate-y-1">
                <div class="w-16 h-16 bg-[#1A1A1A] rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-robot text-white text-2xl"></i>
                </div>
                <h3 class="text-xl font-bold text-[#1A1A1A] mb-2">AI-Powered Future</h3>
                <p class="text-[#212121]">Training schools, children's homes, and AI-driven kitchen innovations.</p>
            </div>
        </div>
    </div>
</section>

<!-- 6. Growth Outlook -->
<section class="py-16 bg-white">
    <div class="container mx-auto px-4">
        <div class="max-w-6xl mx-auto">
            <div class="text-center mb-12">
                <h2 class="text-3xl md:text-4xl font-bold text-dark mb-4">Our Growth Outlook</h2>
                <p class="text-xl text-gray-600 max-w-3xl mx-auto">
                    We are building a brand that begins with students and young people while expanding into larger markets through innovation, strong branding and mentorship.
                </p>
                <div class="w-20 h-1 bg-[#C1272D] mx-auto mt-4"></div>
            </div>

            <div class="grid md:grid-cols-2 gap-12 items-center mb-16">
                <div>
                    <h3 class="text-2xl font-bold text-primary mb-6">Building for the Future</h3>
                    <div class="space-y-4">
                        <div class="flex items-start">
                            <div class="flex-shrink-0 w-8 h-8 bg-primary rounded-full flex items-center justify-center mr-4 mt-1">
                                <i class="fas fa-graduation-cap text-white text-sm"></i>
                            </div>
                            <div>
                                <h4 class="font-semibold text-gray-800">Student-Centered Growth</h4>
                                <p class="text-gray-600">Starting with campus communities and expanding outward</p>
                            </div>
                        </div>
                        <div class="flex items-start">
                            <div class="flex-shrink-0 w-8 h-8 bg-secondary rounded-full flex items-center justify-center mr-4 mt-1">
                                <i class="fas fa-lightbulb text-white text-sm"></i>
                            </div>
                            <div>
                                <h4 class="font-semibold text-gray-800">Innovation-Driven Expansion</h4>
                                <p class="text-gray-600">Embracing AI and technology for scalable growth</p>
                            </div>
                        </div>
                        <div class="flex items-start">
                            <div class="flex-shrink-0 w-8 h-8 bg-accent rounded-full flex items-center justify-center mr-4 mt-1">
                                <i class="fas fa-users text-white text-sm"></i>
                            </div>
                            <div>
                                <h4 class="font-semibold text-gray-800">Community Impact</h4>
                                <p class="text-gray-600">Creating opportunities while serving delicious food</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="bg-gradient-to-br from-primary/5 to-accent/5 rounded-2xl p-8">
                    <h3 class="text-2xl font-bold text-primary mb-6">Addins Ambassadors Program</h3>
                    <p class="text-gray-700 mb-6">
                        Our ambassador program empowers students to earn through commission sales while learning entrepreneurship, ensuring growth for both the business and the community.
                    </p>
                    <div class="grid grid-cols-2 gap-4">
                        <div class="text-center p-4 bg-white rounded-lg">
                            <div class="text-2xl font-bold text-primary">500+</div>
                            <div class="text-sm text-gray-600">Active Ambassadors</div>
                        </div>
                        <div class="text-center p-4 bg-white rounded-lg">
                            <div class="text-2xl font-bold text-secondary">KSh 2M+</div>
                            <div class="text-sm text-gray-600">Ambassador Earnings</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-gradient-to-r from-primary to-secondary rounded-2xl p-8 text-white text-center">
                <h3 class="text-2xl font-bold mb-4">Our Commitment to Excellence</h3>
                <p class="text-lg mb-6 text-white">Addins Meals on Wheels is more than a food business; it is a vision of strength, nurture, and impact. We stand on integrity, faith and innovation to serve not just meals but love and opportunities.</p>
                <a href="/ambassador.php" class="inline-block bg-white text-primary font-bold py-3 px-8 rounded-lg hover:bg-gray-100 transition-colors">
                    Join Our Ambassador Program
                </a>
            </div>
        </div>
    </div>
</section>

<!-- 7. Call to Action -->
<section class="py-16 bg-[#2E5E3A] text-white">
    <div class="container mx-auto px-4 text-center">
        <h2 class="text-3xl md:text-4xl font-bold mb-6">Ready to taste the difference?</h2>
        <p class="text-xl mb-8 max-w-2xl mx-auto">Experience the perfect blend of flavor, quality, and community impact with every bite.</p>
        <a href="/menu.php" class="inline-block bg-[#C1272D] hover:bg-[#D4AF37] text-white font-bold py-3 px-8 rounded-full transition-colors">
            View Our Menu
        </a>
    </div>
</section>

<?php include 'includes/footer.php'; ?>