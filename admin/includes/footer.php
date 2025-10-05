    </main>

    <!-- Footer -->
    <footer class="bg-white border-t border-gray-200 mt-auto py-4">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex flex-col md:flex-row justify-between items-center">
                <p class="text-xs text-gray-500">
                    &copy; <?php echo date('Y'); ?> Addins Meals on Wheels. All rights reserved.
                </p>
                <div class="mt-2 md:mt-0 flex space-x-4">
                    <a href="#" class="text-gray-400 hover:text-gray-500 text-sm">
                        <span class="sr-only">Help Center</span>
                        <i class="far fa-question-circle"></i>
                    </a>
                    <a href="#" class="text-gray-400 hover:text-gray-500 text-sm">
                        <span class="sr-only">Documentation</span>
                        <i class="far fa-file-alt"></i>
                    </a>
                    <a href="#" class="text-gray-400 hover:text-gray-500 text-sm">
                        <span class="sr-only">Settings</span>
                        <i class="fas fa-cog"></i>
                    </a>
                </div>
            </div>
        </div>
    </footer>

    <!-- Common Modals -->
    <div id="deleteModal" class="hidden fixed z-10 inset-0 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true"></div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
                <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <div class="sm:flex sm:items-start">
                        <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-red-100 sm:mx-0 sm:h-10 sm:w-10">
                            <i class="fas fa-exclamation text-red-600"></i>
                        </div>
                        <div class="mt-2 sm:mt-0 sm:ml-4 sm:text-left">
                            <h3 class="text-lg leading-6 font-medium text-gray-900" id="modal-title">Delete Record</h3>
                            <div class="mt-2">
                                <p class="text-sm text-gray-500">Are you sure you want to delete this record? This action cannot be undone.</p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                    <form id="deleteForm" method="POST" action="">
                        <input type="hidden" name="_method" value="DELETE">
                        <button type="submit" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-red-600 text-base font-medium text-white hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 sm:ml-3 sm:w-auto sm:text-sm">
                            Delete
                        </button>
                    </form>
                    <button type="button" onclick="closeModal()" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                        Cancel
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Toggle mobile menu
        function toggleMobileMenu() {
            const menu = document.getElementById('mobile-menu');
            menu.classList.toggle('hidden');
        }

        // Open delete confirmation modal
        function openDeleteModal(url) {
            document.getElementById('deleteForm').action = url;
            document.getElementById('deleteModal').classList.remove('hidden');
            document.body.classList.add('overflow-hidden');
        }

        // Close modal
        function closeModal() {
            document.getElementById('deleteModal').classList.add('hidden');
            document.body.classList.remove('overflow-hidden');
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('deleteModal');
            if (event.target === modal) {
                closeModal();
            }
        }

        // Initialize tooltips
        document.addEventListener('DOMContentLoaded', function() {
            const tooltipTriggers = document.querySelectorAll('[data-tooltip]');
            
            tooltipTriggers.forEach(trigger => {
                let tooltip = document.createElement('div');
                tooltip.className = 'hidden bg-gray-800 text-white text-xs rounded py-1 px-2 absolute z-50 whitespace-nowrap';
                tooltip.textContent = trigger.getAttribute('data-tooltip');
                document.body.appendChild(tooltip);
                
                trigger.addEventListener('mouseenter', (e) => {
                    const rect = trigger.getBoundingClientRect();
                    tooltip.style.top = `${rect.top + window.scrollY - 30}px`;
                    tooltip.style.left = `${rect.left + window.scrollX}px`;
                    tooltip.classList.remove('hidden');
                });
                
                trigger.addEventListener('mouseleave', () => {
                    tooltip.classList.add('hidden');
                });
                
                // Clean up on page unload
                window.addEventListener('beforeunload', () => {
                    tooltip.remove();
                });
            });
        });
    </script>
</body>
</html>
