        </div> <!-- End Content -->
    </div> <!-- End Wrapper -->

    <!-- Bootstrap JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Dark Mode Manager -->
    <script src="/EXAMs/assets/js/dark-mode.js"></script>
    
    <script>
        // Initialize Lucide Icons
        lucide.createIcons();

        // Sidebar Toggle Logic
        document.addEventListener("DOMContentLoaded", function() {
            const sidebarCollapse = document.getElementById('sidebarCollapse');
            const sidebar = document.getElementById('sidebar');
            if (sidebarCollapse && sidebar) {
                sidebarCollapse.addEventListener('click', () => sidebar.classList.toggle('active'));
            }
            
            // Initialize User Dropdown with Bootstrap
            const userDropdownElement = document.getElementById('userDropdown');
            if (userDropdownElement) {
                // Create new dropdown instance
                new bootstrap.Dropdown(userDropdownElement);
                
                // Reinitialize Lucide icons when dropdown is shown
                userDropdownElement.addEventListener('shown.bs.dropdown', function() {
                    setTimeout(() => lucide.createIcons(), 100);
                });
                
                // Also reinitialize when hidden
                userDropdownElement.addEventListener('hidden.bs.dropdown', function() {
                    setTimeout(() => lucide.createIcons(), 100);
                });
            }
        });

        // Notification AJAX Handlers
        function markRead(e, id, url) {
            e.preventDefault();
            let fd = new FormData(); fd.append('id', id);
            fetch('<?= BASE_URL ?>/ajax/mark_notification_read.php', { method: 'POST', body: fd })
            .then(res => res.json()).then(data => {
                if (url && url !== '#') window.location.href = url;
                else e.target.closest('a').classList.remove('bg-primary', 'bg-opacity-10');
            });
        }
        function markAllRead(e) {
            e.preventDefault();
            fetch('<?= BASE_URL ?>/ajax/mark_all_notifications_read.php', { method: 'POST' })
            .then(res => res.json()).then(data => {
                document.querySelectorAll('#notifContainer a').forEach(el => el.classList.remove('bg-primary', 'bg-opacity-10'));
                if (document.getElementById('notifBadge')) document.getElementById('notifBadge').remove();
            });
        }
    </script>
</body>
</html>