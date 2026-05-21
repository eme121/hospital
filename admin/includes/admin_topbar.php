
<?php
// Get admin name from session if available
$admin_name = $_SESSION['admin_name'] ?? 'System Admin';
?>
<!-- Include SweetAlert2 -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<style>
    .toast-notification {
        font-family: 'Plus Jakarta Sans', sans-serif !important;
        border-radius: 20px !important;
        background: #fff !important;
        box-shadow: 0 20px 40px -10px rgba(0,0,0,0.1) !important;
    }
</style>
<header id="topbar" class="fixed top-0 right-0 left-0 flex items-center justify-between px-8 bg-white/80 backdrop-blur-md border-b border-slate-200 z-50 transition-all duration-300 no-print">
    <div class="flex items-center gap-4">
        <!-- Desktop Toggle -->
        <button id="sidebarToggle" class="hidden lg:flex p-2.5 text-slate-500 hover:bg-slate-100 rounded-xl transition-all">
            <i class="fas fa-bars-staggered text-xl"></i>
        </button>
        <!-- Mobile Toggle -->
        <button id="mobileToggle" class="lg:hidden p-2.5 text-slate-500 hover:bg-slate-100 rounded-xl transition-all">
            <i class="fas fa-bars text-xl"></i>
        </button>
        
        <h2 class="text-lg font-black text-slate-800 tracking-tight hidden sm:block">
            Dashboard <span class="text-blue-600">Overview</span>
        </h2>
    </div>

    <div class="flex items-center gap-6">
        <!-- Notification Center (Reusing existing logic) -->
        <div class="relative no-print" id="notifDropdownContainer">
            <button id="notifBtn" class="p-2.5 bg-slate-50 text-slate-400 hover:text-blue-600 hover:bg-blue-50 rounded-xl transition-all relative group">
                <i class="fas fa-bell text-xl"></i>
                <span id="notifBadge" class="absolute -top-1 -right-1 w-5 h-5 bg-rose-500 text-white text-[10px] font-black rounded-full flex items-center justify-center border-2 border-white hidden animate-bounce">0</span>
            </button>

            <!-- Dropdown Menu -->
            <div id="notifDropdown" class="absolute right-0 mt-4 w-80 bg-white rounded-3xl shadow-2xl border border-slate-100 hidden z-[60] overflow-hidden animate-zoom-in">
                <div class="p-5 border-b border-slate-50 flex justify-between items-center bg-slate-50/50">
                    <h4 class="text-xs font-black text-slate-900 uppercase tracking-widest">Activity Feed</h4>
                    <button onclick="markAllAsRead()" class="text-[10px] font-bold text-blue-600 hover:underline">Clear All</button>
                </div>
                <div id="notifList" class="max-h-96 overflow-y-auto custom-scrollbar divide-y divide-slate-50">
                    <div class="p-8 text-center">
                        <p class="text-xs text-slate-400 font-medium">Listening for updates...</p>
                    </div>
                </div>
                <div class="p-4 bg-slate-50/30 text-center">
                    <a href="reports.php" class="text-[10px] font-black text-slate-400 uppercase tracking-widest hover:text-blue-600 transition-colors">Audit Logs</a>
                </div>
            </div>
        </div>

        <!-- Divider -->
        <div class="h-8 w-px bg-slate-200"></div>

        <!-- User Profile -->
        <div class="flex items-center gap-4 pl-2 cursor-pointer group">
            <div class="text-right hidden sm:block">
                <p class="text-xs font-black text-slate-900 leading-none mb-1"><?php echo htmlspecialchars($admin_name); ?></p>
                <p class="text-[10px] font-bold text-blue-500 uppercase tracking-widest">Administrator</p>
            </div>
            <div class="w-10 h-10 bg-gradient-to-tr from-blue-600 to-indigo-600 rounded-xl flex items-center justify-center text-white font-black text-sm shadow-lg shadow-blue-200 group-hover:scale-105 transition-transform">
                <?php echo substr($admin_name, 0, 1); ?>
            </div>
        </div>
    </div>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const notifBtn = document.getElementById('notifBtn');
    const notifDropdown = document.getElementById('notifDropdown');
    const notifList = document.getElementById('notifList');
    const notifBadge = document.getElementById('notifBadge');
    let lastAdminNotifCount = 0;

    if (notifBtn) {
        notifBtn.addEventListener('click', (e) => {
            e.stopPropagation();
            notifDropdown.classList.toggle('hidden');
            if (!notifDropdown.classList.contains('hidden')) {
                fetchAdminNotifications();
            }
        });
    }

    // Close dropdown on click outside
    document.addEventListener('click', (e) => {
        if (notifDropdown && !notifDropdown.contains(e.target) && !notifBtn.contains(e.target)) {
            notifDropdown.classList.add('hidden');
        }
    });

    function fetchAdminNotifications() {
        fetch('../api/notifications.php?action=get&role=admin')
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    if (data.unread_count > lastAdminNotifCount) {
                        if (data.notifications.length > 0) {
                            const latest = data.notifications[0];
                            Swal.fire({
                                title: latest.title,
                                text: latest.message,
                                icon: 'info',
                                toast: true,
                                position: 'top-end',
                                showConfirmButton: false,
                                timer: 5000,
                                timerProgressBar: true,
                                customClass: { popup: 'toast-notification' },
                                didOpen: (toast) => {
                                    toast.addEventListener('mouseenter', Swal.stopTimer);
                                    toast.addEventListener('mouseleave', Swal.resumeTimer);
                                    toast.addEventListener('click', () => {
                                        if(latest.action_url) window.location.href = latest.action_url;
                                    });
                                }
                            });
                            // Play subtle notification sound
                            const audio = new Audio('https://www.soundjay.com/buttons/beep-07a.mp3');
                            audio.play().catch(e => console.log("Sound blocked"));
                        }
                    }
                    lastAdminNotifCount = data.unread_count;

                    if (data.unread_count > 0) {
                        notifBadge.textContent = data.unread_count;
                        notifBadge.classList.remove('hidden');
                    } else {
                        notifBadge.classList.add('hidden');
                    }

                    if (data.notifications.length > 0) {
                        notifList.innerHTML = data.notifications.map(n => `
                            <div class="p-4 ${n.status === 'unread' ? 'bg-blue-50/50' : ''} hover:bg-slate-50 transition-colors">
                                <div class="flex justify-between items-start mb-1">
                                    <h5 class="text-[11px] font-black text-slate-900 uppercase tracking-tight">${n.title}</h5>
                                    <span class="text-[9px] font-bold text-slate-400">${new Date(n.created_at).toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'})}</span>
                                </div>
                                <p class="text-[10px] font-medium text-slate-600 leading-relaxed">${n.message}</p>
                                ${n.action_url ? `<a href="${n.action_url}" class="text-[9px] font-black text-blue-600 uppercase mt-2 block hover:underline">Process Now</a>` : ''}
                            </div>
                        `).join('');
                    } else {
                        notifList.innerHTML = '<div class="p-8 text-center text-slate-400 font-bold italic text-xs">No active alerts.</div>';
                    }
                }
            });
    }

    window.markAllAsRead = function() {
        fetch('../api/notifications.php?action=mark_read&role=admin', { method: 'POST' })
            .then(() => {
                if(notifBadge) notifBadge.classList.add('hidden');
                fetchAdminNotifications();
            });
    }

    // Initial fetch and poll
    fetchAdminNotifications();
    setInterval(fetchAdminNotifications, 30000);
});
</script>
</header>
