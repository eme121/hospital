    </main>

    <script>
        // Persistent Notification System for all views
        let lastNotifCount = 0;
        function fetchGlobalNotifications() {
            fetch('../api/notifications.php?action=get&role=admin')
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        const badge = document.getElementById('notif-badge');
                        if(data.unread_count > 0) {
                            badge.textContent = data.unread_count;
                            badge.classList.remove('hidden');
                        } else {
                            badge.classList.add('hidden');
                        }
                    }
                }).catch(e => console.log('Notif error:', e));
        }
        setInterval(fetchGlobalNotifications, 30000);
        fetchGlobalNotifications();
    </script>
</body>
</html>
