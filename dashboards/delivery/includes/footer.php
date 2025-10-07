        </main>
    </div>

    <script>
        // Auto-refresh delivery status every 30 seconds
        setInterval(function() {
            if (document.querySelector('.pending-deliveries-count')) {
                fetch('api/get_delivery_counts.php')
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            document.querySelector('.pending-deliveries-count').textContent = data.pending;
                            document.querySelector('.completed-today-count').textContent = data.completed_today;
                        }
                    })
                    .catch(error => console.error('Error updating counts:', error));
            }
        }, 30000);

        // Mobile sidebar toggle
        document.addEventListener('DOMContentLoaded', function() {
            const sidebarToggle = document.getElementById('sidebar-toggle');
            const sidebar = document.getElementById('sidebar');
            const backdrop = document.getElementById('sidebar-backdrop');

            if (sidebarToggle && sidebar && backdrop) {
                sidebarToggle.addEventListener('click', function() {
                    sidebar.classList.toggle('-translate-x-full');
                    backdrop.classList.toggle('hidden');
                });

                backdrop.addEventListener('click', function() {
                    sidebar.classList.add('-translate-x-full');
                    backdrop.classList.add('hidden');
                });
            }
        });
    </script>
</body>
</html>
