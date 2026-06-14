        </div> <!-- End Content -->
    </div> <!-- End Wrapper -->

    <!-- Bootstrap JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
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