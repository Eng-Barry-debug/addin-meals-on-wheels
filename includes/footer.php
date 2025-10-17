</main>

    <!-- Footer -->
    <footer class="bg-dark text-white pt-12 pb-8">
        <div class="container mx-auto px-4">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
                <!-- About Section -->
                <div class="space-y-4">
                    <div class="flex items-center space-x-2">
                        <img src="/uploads/menu/Addin-logo.jpeg" alt="Addins Meals" class="h-10">
                        <span class="text-xl font-bold">Addins Meals</span>
                    </div>
                    <p class="text-gray-300">Delivering delicious, home-cooked meals straight to your door. Made with love, served with care. Born from campus entrepreneurship, rooted in integrity and faith.</p>
                    <div class="flex space-x-4 pt-2">
                        <a href="https://www.instagram.com/adinns_meals_on_wheels?igsh=MXBmdnBkNjBrYnJieQ==" target="_blank" rel="noopener noreferrer" class="text-gray-300 hover:text-accent transition-colors duration-200" title="Follow us on Instagram">
                            <i class="fab fa-instagram text-lg"></i>
                        </a>
                        <a href="https://www.facebook.com/share/17Y42uWZG4/" target="_blank" rel="noopener noreferrer" class="text-gray-300 hover:text-accent transition-colors duration-200" title="Like us on Facebook">
                            <i class="fab fa-facebook-f text-lg"></i>
                        </a>
                        <a href="https://www.tiktok.com/@adinns_meals_on_wheels?_t=ZM-8zyKYPHl740&_r=1" target="_blank" rel="noopener noreferrer" class="text-gray-300 hover:text-accent transition-colors duration-200" title="Follow us on TikTok">
                            <i class="fab fa-tiktok text-lg"></i>
                        </a>
                        <a href="https://wa.me/254112855900" target="_blank" rel="noopener noreferrer" class="text-gray-300 hover:text-accent transition-colors duration-200" title="Chat with us on WhatsApp">
                            <i class="fab fa-whatsapp text-lg"></i>
                        </a>
                    </div>
                </div>

                <!-- Quick Links -->
                <div>
                    <h3 class="text-lg font-bold mb-4 text-white border-b border-gray-600 pb-2">Quick Links</h3>
                    <ul class="space-y-3">
                        <li><a href="/menu.php" class="text-gray-300 hover:text-accent transition-colors duration-200 flex items-center">
                            <i class="fas fa-chevron-right text-xs text-accent mr-2"></i> Our Menu
                        </a></li>
                        <li><a href="/about.php" class="text-gray-300 hover:text-accent transition-colors duration-200 flex items-center">
                            <i class="fas fa-chevron-right text-xs text-accent mr-2"></i> About Us
                        </a></li>
                        <li><a href="/catering.php" class="text-gray-300 hover:text-accent transition-colors duration-200 flex items-center">
                            <i class="fas fa-chevron-right text-xs text-accent mr-2"></i> Catering Services
                        </a></li>
                        <li><a href="/blog.php" class="text-gray-300 hover:text-accent transition-colors duration-200 flex items-center">
                            <i class="fas fa-chevron-right text-xs text-accent mr-2"></i> Blog
                        </a></li>
                        <li><a href="/contact.php" class="text-gray-300 hover:text-accent transition-colors duration-200 flex items-center">
                            <i class="fas fa-chevron-right text-xs text-accent mr-2"></i> Contact Us
                        </a></li>
                    </ul>
                </div>

                <!-- Contact Info -->
                <div>
                    <h3 class="text-lg font-bold mb-4 text-white border-b border-gray-600 pb-2">Contact Us</h3>
                    <ul class="space-y-3">
                        <li class="flex items-start">
                            <i class="fas fa-map-marker-alt mt-1 text-accent mr-3"></i>
                            <span class="text-gray-300">Online Delivery Service</span>
                        </li>
                        <li class="flex items-center">
                            <i class="fas fa-phone-alt text-accent mr-3"></i>
                            <a href="tel:+254112855900" class="text-gray-300 hover:text-accent transition-colors duration-200">(254) 112-855-900</a>
                        </li>
                        <li class="flex items-center">
                            <i class="fas fa-envelope text-accent mr-3"></i>
                            <a href="mailto:info@addinsmeals.com" class="text-gray-300 hover:text-accent transition-colors duration-200">info@addinsmeals.com</a>
                        </li>
                        <li class="flex items-center">
                            <i class="fas fa-clock text-accent mr-3"></i>
                            <span class="text-gray-300">Mon-Sun: 8:00 AM - 10:00 PM</span>
                        </li>
                    </ul>
                </div>

                <!-- Newsletter -->
                <div>
                    <h3 class="text-lg font-bold mb-4 text-white border-b border-gray-600 pb-2">Newsletter</h3>
                    <p class="text-gray-300 mb-4">Subscribe to our newsletter for the latest updates and exclusive offers.</p>

                    <!-- Newsletter Form -->
                    <form id="newsletter-form" class="space-y-3">
                        <div class="flex">
                            <input type="email" id="newsletter-email" placeholder="Your email address"
                                   class="px-4 py-2.5 w-full rounded-l-md focus:outline-none focus:ring-2 focus:ring-accent text-gray-800"
                                   required>
                            <button type="submit" id="newsletter-submit"
                                    class="bg-accent text-secondary px-4 rounded-r-md hover:bg-primary hover:text-white transition-colors duration-200 flex items-center disabled:opacity-50">
                                <i class="fas fa-paper-plane"></i>
                            </button>
                        </div>
                        <div id="newsletter-message" class="text-sm hidden"></div>
                    </form>

                    <p class="text-xs text-gray-400 mt-2">We respect your privacy. Unsubscribe at any time.</p>
                </div>
            </div>

            <!-- Copyright -->
            <div class="border-t border-gray-600 mt-10 pt-6 text-center">
                <p class="text-gray-400">&copy; <?php echo date('Y'); ?> Addins Meals on Wheels. All rights reserved.</p>
                <div class="mt-3 flex justify-center space-x-4">
                    <a href="/privacy.php" class="text-gray-400 hover:text-accent transition-colors duration-200 text-sm">Privacy Policy</a>
                    <a href="/terms.php" class="text-gray-400 hover:text-accent transition-colors duration-200 text-sm">Terms of Service</a>
                    <span class="text-gray-600">•</span>
                    <a href="/sitemap.php" class="text-gray-400 hover:text-accent transition-colors duration-200 text-sm">Sitemap</a>
                </div>
    <!-- Newsletter Subscription JavaScript -->
    <script>
        document.getElementById('newsletter-form').addEventListener('submit', async function(e) {
            e.preventDefault();

            const emailInput = document.getElementById('newsletter-email');
            const submitBtn = document.getElementById('newsletter-submit');
            const messageDiv = document.getElementById('newsletter-message');

            const email = emailInput.value.trim();

            if (!email) {
                showMessage('Please enter your email address.', 'error');
                return;
            }

            if (!isValidEmail(email)) {
                showMessage('Please enter a valid email address.', 'error');
                return;
            }

            // Disable form during submission
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';

            try {
                const response = await fetch('/includes/subscribe.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'email=' + encodeURIComponent(email) + '&ajax=1'
                });

                const result = await response.json();

                if (result.success) {
                    showMessage(result.message, 'success');
                    emailInput.value = '';
                } else {
                    showMessage(result.message, 'error');
                }
            } catch (error) {
                showMessage('Something went wrong. Please try again later.', 'error');
            } finally {
                // Re-enable form
                submitBtn.disabled = false;
                submitBtn.innerHTML = '<i class="fas fa-paper-plane"></i>';
            }
        });

        function showMessage(message, type) {
            const messageDiv = document.getElementById('newsletter-message');
            messageDiv.textContent = message;
            messageDiv.className = `text-sm ${type === 'success' ? 'text-green-400' : 'text-red-400'}`;
            messageDiv.classList.remove('hidden');

            // Hide message after 5 seconds
            setTimeout(() => {
                messageDiv.classList.add('hidden');
            }, 5000);
        }

        function isValidEmail(email) {
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            return emailRegex.test(email);
        }
    </script>
