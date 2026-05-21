
/* Admin Notification System */
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
    return '<i class="fas fa-info-circle"></i>';
}

function formatTime(ts) {
    const date = new Date(ts);
    return date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
}

function showProminentAlert(n) {
    const alertModal = document.getElementById('prominentAlert');
    if (!alertModal) return;

    document.getElementById('alertTitle').textContent = n.title;
    document.getElementById('alertMessage').textContent = n.message;
    const actionBtn = document.getElementById('alertAction');
    actionBtn.href = n.action_url || '#';
    actionBtn.onclick = (e) => {
        e.preventDefault();
        markAsRead(n.id, n.action_url);
    };
    
    alertModal.classList.remove('hidden');
    lastAlertedId = n.id;
    sessionStorage.setItem('lastAlertedId', lastAlertedId);
    
    if (adminSound) {
        adminSound.currentTime = 0;
        adminSound.play().catch(e => console.log("Sound play blocked"));
    }
}

function closeAlert() {
    const alertModal = document.getElementById('prominentAlert');
    if (alertModal) alertModal.classList.add('hidden');
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
setInterval(fetchNotifications, 15000);
