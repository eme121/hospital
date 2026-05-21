<?php
$current_page = basename($_SERVER['PHP_SELF']);
?>
<nav class="bg-white border-b border-slate-100 py-6 px-10 flex justify-between items-center sticky top-0 z-50 shadow-sm no-print">
    <div class="flex flex-col cursor-pointer" onclick="window.location.href='dashboard.php'">
        <span class="text-xl font-black tracking-tighter leading-none text-blue-700">HOPE HAVEN <span class="text-amber-600">HOSPITAL</span></span>
        <span class="text-[10px] font-bold text-slate-400 tracking-[0.2em] uppercase mt-1">Admin Dashboard</span>
    </div>
    <div class="flex items-center gap-6">
        <!-- Notification System -->
        <div class="relative no-print" id="notifDropdownContainer">
            <button id="notifBtn" class="p-2.5 bg-slate-50 text-slate-400 hover:text-blue-600 hover:bg-blue-50 rounded-xl transition-all relative group">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path></svg>
                <span id="notifBadge" class="absolute -top-1 -right-1 w-5 h-5 bg-rose-500 text-white text-[10px] font-black rounded-full flex items-center justify-center border-2 border-white hidden animate-bounce">0</span>
            </button>

            <!-- Dropdown Menu -->
            <div id="notifDropdown" class="absolute right-0 mt-4 w-80 bg-white rounded-3xl shadow-2xl border border-slate-100 hidden z-[60] overflow-hidden animate-zoom-in">
                <div class="p-5 border-b border-slate-50 flex justify-between items-center bg-slate-50/50">
                    <h4 class="text-xs font-black text-slate-900 uppercase tracking-widest">Recent Activity</h4>
                    <button onclick="markAllAsRead()" class="text-[10px] font-bold text-blue-600 hover:underline">Mark all read</button>
                </div>
                <div id="notifList" class="max-h-96 overflow-y-auto custom-scrollbar divide-y divide-slate-50">
                    <!-- Notifications will be injected here -->
                    <div class="p-8 text-center">
                        <p class="text-xs text-slate-400 font-medium">No recent notifications</p>
                    </div>
                </div>
                <div class="p-4 bg-slate-50/30 text-center">
                    <a href="reports.php" class="text-[10px] font-black text-slate-400 uppercase tracking-widest hover:text-blue-600 transition-colors">View All Reports</a>
                </div>
            </div>
        </div>

        <div class="h-6 w-px bg-slate-200 mx-2"></div>
        <a href="dashboard.php" class="text-[11px] font-black uppercase tracking-widest <?php echo ($current_page == 'dashboard.php') ? 'text-blue-600 border-b-2 border-blue-600 pb-1' : 'text-slate-500 hover:text-blue-600 transition-colors'; ?>">Appointments</a>
        <a href="manage_patients.php" class="text-[11px] font-black uppercase tracking-widest <?php echo ($current_page == 'manage_patients.php') ? 'text-blue-600 border-b-2 border-blue-600 pb-1' : 'text-slate-500 hover:text-blue-600 transition-colors'; ?>">Patients</a>
        <a href="manage_medical_records.php" class="text-[11px] font-black uppercase tracking-widest <?php echo ($current_page == 'manage_medical_records.php') ? 'text-blue-600 border-b-2 border-blue-600 pb-1' : 'text-slate-500 hover:text-blue-600 transition-colors'; ?>">Medical Records</a>
        <a href="manage_services.php" class="text-[11px] font-black uppercase tracking-widest <?php echo ($current_page == 'manage_services.php') ? 'text-blue-600 border-b-2 border-blue-600 pb-1' : 'text-slate-500 hover:text-blue-600 transition-colors'; ?>">Clinical Services</a>
        <a href="specialists.php" class="text-[11px] font-black uppercase tracking-widest <?php echo ($current_page == 'specialists.php') ? 'text-blue-600 border-b-2 border-blue-600 pb-1' : 'text-slate-500 hover:text-blue-600 transition-colors'; ?>">Specialists</a>
        <a href="manage_doctors.php" class="text-[11px] font-black uppercase tracking-widest <?php echo ($current_page == 'manage_doctors.php') ? 'text-blue-600 border-b-2 border-blue-600 pb-1' : 'text-slate-500 hover:text-blue-600 transition-colors'; ?>">Doctors</a>
        <a href="telemedicine.php" class="text-[11px] font-black uppercase tracking-widest <?php echo ($current_page == 'telemedicine.php') ? 'text-blue-600 border-b-2 border-blue-600 pb-1' : 'text-slate-500 hover:text-blue-600 transition-colors'; ?>">Telemedicine</a>
        <a href="manage_events.php" class="text-[11px] font-black uppercase tracking-widest <?php echo ($current_page == 'manage_events.php') ? 'text-blue-600 border-b-2 border-blue-600 pb-1' : 'text-slate-500 hover:text-blue-600 transition-colors'; ?>">Events</a>
        <a href="manage_invoices.php" class="text-[11px] font-black uppercase tracking-widest <?php echo ($current_page == 'manage_invoices.php') ? 'text-blue-600 border-b-2 border-blue-600 pb-1' : 'text-slate-500 hover:text-blue-600 transition-colors'; ?>">Revenue & Billing</a>
        <a href="manage_hr.php" class="text-[11px] font-black uppercase tracking-widest <?php echo ($current_page == 'manage_hr.php') ? 'text-blue-600 border-b-2 border-blue-600 pb-1' : 'text-slate-500 hover:text-blue-600 transition-colors'; ?>">Staff & HR</a>
        <a href="verify_payments.php" class="text-[11px] font-black uppercase tracking-widest <?php echo ($current_page == 'verify_payments.php') ? 'text-blue-600 border-b-2 border-blue-600 pb-1' : 'text-slate-500 hover:text-blue-600 transition-colors'; ?>">Verifications</a>
        <a href="reports.php" class="text-[11px] font-black uppercase tracking-widest <?php echo ($current_page == 'reports.php') ? 'text-blue-600 border-b-2 border-blue-600 pb-1' : 'text-slate-500 hover:text-blue-600 transition-colors'; ?>">Reports</a>
        <div class="h-6 w-px bg-slate-200 mx-2"></div>
        <a href="logout.php" class="text-[11px] font-black uppercase tracking-widest text-red-500 hover:text-red-700 transition-colors">Logout</a>
    </div>
</nav>

<!-- Notification Sound -->
<audio id="adminNotificationSound" preload="auto">
    <source src="https://www.soundjay.com/buttons/sounds/button-3.mp3" type="audio/mpeg">
    <source src="https://assets.mixkit.co/active_storage/sfx/2869/2869-preview.mp3" type="audio/mpeg">
</audio>

<!-- Prominent Alert Modal -->
<div id="prominentAlert" class="fixed inset-0 z-[200] flex items-center justify-center p-4 bg-slate-900/80 backdrop-blur-md hidden" style="backdrop-filter: blur(8px); -webkit-backdrop-filter: blur(8px);">
    <div class="bg-white rounded-[40px] w-full max-w-md p-10 shadow-2xl animate-zoom-in text-center relative overflow-hidden">
        <div class="absolute top-0 left-0 w-full h-2 bg-blue-600"></div>
        <div id="alertIconBox" class="w-24 h-24 bg-blue-50 text-blue-600 rounded-[32px] flex items-center justify-center mx-auto mb-8 animate-pulse shadow-inner">
            <svg class="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path></svg>
        </div>
        <div class="space-y-3 mb-10">
            <span class="px-4 py-1.5 bg-blue-50 text-blue-600 text-[10px] font-black uppercase tracking-[0.2em] rounded-full">New Update</span>
            <h3 id="alertTitle" class="text-2xl font-black text-slate-900 leading-tight">New Notification</h3>
            <p id="alertMessage" class="text-slate-500 font-medium leading-relaxed px-4">You have a new update in the system.</p>
        </div>
        <div class="flex flex-col gap-3">
            <a id="alertAction" href="#" class="w-full py-5 bg-blue-600 text-white rounded-[24px] font-black text-sm tracking-widest uppercase hover:bg-blue-700 transition-all shadow-xl shadow-blue-200 flex items-center justify-center gap-3">
                View Details
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M13 5l7 7m0 0l-7 7m7-7H3"></path></svg>
            </a>
            <button onclick="closeAlert()" class="w-full py-4 text-slate-400 font-bold text-xs uppercase tracking-widest hover:text-slate-600 transition-all">Dismiss Now</button>
        </div>
    </div>
</div>

<style>
@keyframes zoom-in {
    from { opacity: 0; transform: scale(0.9) translateY(20px); }
    to { opacity: 1; transform: scale(1) translateY(0); }
}
.animate-zoom-in { animation: zoom-in 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275) forwards; }
.custom-scrollbar::-webkit-scrollbar { width: 4px; }
.custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
.custom-scrollbar::-webkit-scrollbar-thumb { background: #e2e8f0; border-radius: 10px; }
</style>

<script>
let lastAlertedId = parseInt(sessionStorage.getItem('lastAlertedId') || 0);
let lastNotifId = 0;
const notifBtn = document.getElementById('notifBtn');
const notifDropdown = document.getElementById('notifDropdown');
const notifBadge = document.getElementById('notifBadge');
const notifList = document.getElementById('notifList');
const adminSound = document.getElementById('adminNotificationSound');

if (notifBtn) {
    notifBtn.onclick = (e) => {
        e.stopPropagation();
        notifDropdown.classList.toggle('hidden');
    };
}
document.addEventListener('click', () => {
    if (notifDropdown) notifDropdown.classList.add('hidden');
});
if (notifDropdown) {
    notifDropdown.onclick = (e) => e.stopPropagation();
}

function fetchNotifications() {
    fetch(`api/check_notifications.php?last_id=${lastNotifId}`)
        .then(res => res.json())
        .then(data => {
            if (data.new && data.new.length > 0) {
                const latest = data.new[0];
                if (latest.id > lastAlertedId) {
                    showProminentAlert(latest);
                }
                lastNotifId = Math.max(lastNotifId, ...data.new.map(n => n.id));
            } else if (lastNotifId === 0 && data.history && data.history.length > 0) {
                lastNotifId = Math.max(...data.history.map(n => n.id));
                const latestUnread = data.history.find(n => n.status === 'unread');
                if (latestUnread && latestUnread.id > lastAlertedId) {
                    showProminentAlert(latestUnread);
                }
            }

            if (data.unread_count > 0) {
                notifBadge.textContent = data.unread_count;
                notifBadge.classList.remove('hidden');
            } else {
                notifBadge.classList.add('hidden');
            }

            updateNotifList(data.history);
        })
        .catch(err => console.error('Notif Error:', err));
}

function updateNotifList(history) {
    if (!history || history.length === 0) {
        notifList.innerHTML = '<div class="p-8 text-center"><p class="text-xs text-slate-400 font-medium">No recent notifications</p></div>';
        return;
    }

    notifList.innerHTML = history.map(n => `
        <div class="p-5 hover:bg-slate-50 transition-colors cursor-pointer ${n.status === 'unread' ? 'bg-blue-50/30' : ''}" onclick="markAsRead(${n.id}, '${n.action_url}')">
            <div class="flex gap-4">
                <div class="w-10 h-10 rounded-xl ${getNotifBg(n.type)} flex-shrink-0 flex items-center justify-center">
                    ${getNotifIcon(n.type)}
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-[11px] font-black text-slate-900 truncate">${n.title}</p>
                    <p class="text-[10px] text-slate-500 font-medium mt-0.5 line-clamp-2">${n.message}</p>
                    <p class="text-[9px] text-slate-400 font-bold uppercase mt-2 tracking-wider">${formatTime(n.created_at)}</p>
                </div>
                ${n.status === 'unread' ? '<div class="w-2 h-2 bg-blue-600 rounded-full mt-1"></div>' : ''}
            </div>
        </div>
    `).join('');
}

function getNotifBg(type) {
    switch(type) {
        case 'appointment': return 'bg-blue-100 text-blue-600';
        case 'support': return 'bg-emerald-100 text-emerald-600';
        case 'doctor_approval': return 'bg-purple-100 text-purple-600';
        default: return 'bg-slate-100 text-slate-600';
    }
}

function getNotifIcon(type) {
    return '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>';
}

function formatTime(ts) {
    const date = new Date(ts);
    return date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
}

function showProminentAlert(n) {
    document.getElementById('alertTitle').textContent = n.title;
    document.getElementById('alertMessage').textContent = n.message;
    const actionBtn = document.getElementById('alertAction');
    actionBtn.href = n.action_url || '#';
    actionBtn.onclick = (e) => {
        e.preventDefault();
        markAsRead(n.id, n.action_url);
    };
    
    document.getElementById('prominentAlert').classList.remove('hidden');
    lastAlertedId = n.id;
    sessionStorage.setItem('lastAlertedId', lastAlertedId);
    
    if (adminSound) {
        adminSound.currentTime = 0;
        adminSound.play().catch(e => {
            console.log("Autoplay blocked");
            const playOnce = () => {
                adminSound.play();
                document.removeEventListener('click', playOnce);
            };
            document.addEventListener('click', playOnce);
        });
    }
}

function closeAlert() {
    document.getElementById('prominentAlert').classList.add('hidden');
}

function markAsRead(id, url) {
    fetch(`api/check_notifications.php?action=mark_read&id=${id}`)
        .then(() => {
            if (url && url !== '#' && url !== 'undefined' && url !== 'null') window.location.href = url;
            else {
                closeAlert();
                fetchNotifications();
            }
        });
}

function markAllAsRead() {
    fetch('api/check_notifications.php?action=mark_all_read')
        .then(() => fetchNotifications());
}

fetchNotifications();
setInterval(fetchNotifications, 10000);
</script>