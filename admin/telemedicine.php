<?php
session_start();
require_once '../includes/db_connect.php';

if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: index.php');
    exit;
}

// Update Status Logic for Telemedicine
if (isset($_GET['action']) && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $action = $_GET['action'];
    $status = ($action == 'confirm') ? 'Accepted' : (($action == 'cancel') ? 'Cancelled' : 'Pending');

    $sql = "UPDATE telemedicine_appointments SET status = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("si", $status, $id);
    
    if ($stmt->execute()) {
        // Trigger Real-Time Sync Signal
        require_once '../includes/sync_helper.php';
        SyncManager::signal('patient_queue', 'UPDATE', $id);

        if ($status == 'Confirmed' || $status == 'Cancelled') {
            // Fetch patient details for notifications
            $get_patient = $conn->prepare("SELECT patient_id, patient_name, email, phone, appointment_date, appointment_time FROM telemedicine_appointments WHERE id = ?");
            $get_patient->bind_param("i", $id);
            $get_patient->execute();
            $patient = $get_patient->get_result()->fetch_assoc();

            if ($patient) {
                require_once '../includes/notifications_helper.php';
                NotificationService::setConnection($conn);

                $name = $patient['patient_name'];
                $date = $patient['appointment_date'];
                $time = $patient['appointment_time'];

                if ($status == 'Confirmed') {
                    $room_id = md5($id . 'hopehaven');
                    // Switched to meet.ffmuc.net to avoid meet.jit.si 5-minute guest limit
                    $meeting_link = "https://meet.ffmuc.net/" . $room_id;

                    $subject = "Telemedicine Confirmed - Hope Haven Hospital";                    $message = "Dear $name,\n\nYour virtual consultation scheduled for $date at $time has been CONFIRMED. Your meeting room is ready.\n\nYou can join the video call from your dashboard or using this link: $meeting_link";
                } else {
                    $subject = "Telemedicine Update - Hope Haven Hospital";
                    $message = "Dear $name,\n\nWe regret to inform you that your virtual consultation scheduled for $date at $time has been DECLINED/CANCELLED.\n\nBest regards,\nHope Haven Team";
                }

                NotificationService::send('patient', $patient['patient_id'], 'appointment_update', $subject, $message, 'telemedicine_dashboard_patient.php', [
                    'email' => $patient['email'],
                    'phone' => $patient['phone']
                ]);
            }
        }
    }
    header('Location: telemedicine.php?updated=1');
    exit;
}

// Fetch Telemedicine Appointments
$sql = "SELECT a.*, COALESCE(ts.name, dr.name) as doctor_name, de.name as department_name 
        FROM telemedicine_appointments a 
        LEFT JOIN telemedicine_doctors ts ON a.doctor_id = ts.id 
        LEFT JOIN doctors dr ON a.doctor_id = dr.id
        LEFT JOIN departments de ON a.department_id = de.id 
        ORDER BY a.appointment_date ASC, a.appointment_time ASC";
$result = $conn->query($sql);
$num_rows = ($result) ? $result->num_rows : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Telemedicine Admin | Hope Haven Hospital</title>
    <?php include 'includes/header_scripts.php'; ?>
    <style>body { font-family: 'Plus Jakarta Sans', sans-serif; }</style>
</head>
<body class="bg-slate-50 min-h-screen">
    <?php include 'includes/admin_sidebar.php'; ?>
    <?php include 'includes/admin_topbar.php'; ?>

    <main id="main-wrapper" class="pt-[90px] px-8 pb-10 lg:ml-[280px] transition-all duration-300">
        <div class="flex flex-col md:flex-row justify-between items-start md:items-end mb-10 gap-6">
            <div>
                <h1 class="text-3xl font-black text-slate-900 mb-2 tracking-tight">Telemedicine Consultations</h1>
                <div class="flex items-center gap-4">
                    <p class="text-slate-500 font-medium">Manage virtual patient bookings and video call schedules.</p>
                    <span class="w-1.5 h-1.5 rounded-full bg-slate-300"></span>
                    <a href="telemedicine_oversight.php" class="text-xs font-black text-blue-600 uppercase tracking-widest hover:underline flex items-center gap-2">
                        <i data-lucide="eye" class="w-4 h-4"></i> Live Board Room Oversight
                    </a>
                </div>
            </div>
            <div class="flex flex-wrap gap-4 items-center w-full md:w-auto">
                <div class="relative flex-1 md:flex-none min-w-[300px]">
                    <input type="text" id="teleSearch" placeholder="Search by patient, doctor or dept..." 
                           class="w-full pl-12 pr-4 py-3 bg-white border border-slate-200 rounded-2xl text-sm font-medium focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 transition-all outline-none">
                    <svg class="w-5 h-5 absolute left-4 top-1/2 -translate-y-1/2 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                </div>
                <div class="px-6 py-3 bg-blue-50 rounded-xl border border-blue-100 flex items-center gap-3">
                    <span class="text-[10px] font-bold text-blue-400 uppercase tracking-widest">Total Consults</span>
                    <span class="text-xl font-black text-blue-600"><?php echo $num_rows; ?></span>
                </div>
            </div>
        </div>

        <!-- Table Container -->
        <div class="bg-white rounded-[32px] shadow-xl shadow-slate-200/50 border border-slate-100 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse" id="teleTable">
                    <thead>
                        <tr class="bg-slate-50/50 border-b border-slate-100">
                            <th class="px-8 py-5 text-[11px] font-bold text-slate-400 uppercase tracking-[0.2em]">Patient Details</th>
                            <th class="px-8 py-5 text-[11px] font-bold text-slate-400 uppercase tracking-[0.2em]">Specialist</th>
                            <th class="px-8 py-5 text-[11px] font-bold text-slate-400 uppercase tracking-[0.2em]">Schedule</th>
                            <th class="px-8 py-5 text-[11px] font-bold text-slate-400 uppercase tracking-[0.2em]">Status</th>
                            <th class="px-8 py-5 text-[11px] font-bold text-slate-400 uppercase tracking-[0.2em] text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50">
                        <?php if($num_rows > 0): ?>
                            <?php while($row = $result->fetch_assoc()): ?>
                                <tr class="hover:bg-slate-50/50 transition-colors group">
                                    <td class="px-8 py-6">
                                        <p class="font-bold text-slate-900 text-sm leading-tight"><?php echo htmlspecialchars($row['patient_name']); ?></p>
                                        <p class="text-[10px] text-slate-500 font-bold uppercase mt-1 tracking-widest"><?php echo htmlspecialchars($row['email']); ?></p>
                                        <p class="text-[10px] text-slate-400 font-medium"><?php echo htmlspecialchars($row['phone']); ?></p>
                                    </td>
                                    <td class="px-8 py-6">
                                        <p class="text-xs font-black text-blue-600 uppercase tracking-wider"><?php echo htmlspecialchars($row['department_name']); ?></p>
                                        <p class="text-xs text-slate-500 font-bold mt-1"><?php echo htmlspecialchars($row['doctor_name']); ?></p>
                                    </td>
                                    <td class="px-8 py-6">
                                        <p class="text-sm font-bold text-slate-900"><?php echo date('M d, Y', strtotime($row['appointment_date'])); ?></p>
                                        <p class="text-xs text-slate-500 font-bold uppercase mt-1 tracking-widest"><?php echo date('h:i A', strtotime($row['appointment_time'])); ?></p>
                                    </td>
                                    <td class="px-8 py-6">
                                        <?php 
                                            $color = "bg-amber-100 text-amber-700 border-amber-200";
                                            if($row['status'] == 'Confirmed') $color = "bg-emerald-100 text-emerald-700 border-emerald-200";
                                            if($row['status'] == 'Cancelled') $color = "bg-rose-100 text-rose-700 border-rose-200";
                                        ?>
                                        <span class="px-4 py-1.5 rounded-full text-[10px] font-black uppercase tracking-wider border <?php echo $color; ?>">
                                            <?php echo $row['status']; ?>
                                        </span>
                                    </td>
                                    <td class="px-8 py-6 text-center">
                                        <div class="flex justify-center gap-3">
                                            <?php if($row['status'] == 'Pending'): ?>
                                                <a href="?action=confirm&id=<?php echo $row['id']; ?>" class="p-2.5 bg-emerald-50 text-emerald-600 rounded-xl hover:bg-emerald-600 hover:text-white transition-all shadow-sm group/conf" title="Confirm Appointment">
                                                    <svg class="w-5 h-5 group-hover/conf:scale-110 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"></path></svg>
                                                </a>
                                                <a href="?action=cancel&id=<?php echo $row['id']; ?>" class="p-2.5 bg-rose-50 text-rose-600 rounded-xl hover:bg-rose-600 hover:text-white transition-all shadow-sm group/can" title="Cancel Appointment">
                                                    <svg class="w-5 h-5 group-hover/can:scale-110 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"></path></svg>
                                                </a>
                                            <?php else: ?>
                                                <span class="text-[10px] font-black text-slate-300 uppercase tracking-[0.2em] italic">Processed</span>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="px-8 py-20 text-center text-slate-400 font-bold italic">No telemedicine consultations found.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <!-- Toast Notification Container -->
    <div id="toastContainer" class="fixed bottom-8 right-8 z-[100] flex flex-col gap-3 max-w-sm w-full"></div>

    <!-- Notification Sound -->
    <audio id="notificationSound" src="https://assets.mixkit.co/active_storage/sfx/2869/2869-preview.mp3" preload="auto"></audio>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            let lastTimestamp = '<?php echo date('Y-m-d H:i:s'); ?>';
            const toastContainer = document.getElementById('toastContainer');
            const sound = document.getElementById('notificationSound');
            const teleSearch = document.getElementById('teleSearch');

            function createToast(event) {
                if (!toastContainer) return;
                const toast = document.createElement('div');
                toast.className = 'bg-white border-l-4 border-blue-600 p-5 rounded-2xl shadow-2xl shadow-blue-900/10 flex gap-4 items-start transform translate-x-full transition-all duration-500 hover:scale-102';
                
                let iconColor = 'text-blue-600 bg-blue-50';
                let icon = '<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>';
                
                if (event.type === 'success') {
                    iconColor = 'text-emerald-600 bg-emerald-50';
                    toast.className = toast.className.replace('border-blue-600', 'border-emerald-600');
                    icon = '<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>';
                }

                toast.innerHTML = `
                    <div class="p-2 ${iconColor} rounded-xl">
                        ${icon}
                    </div>
                    <div class="flex-1">
                        <p class="text-sm font-black text-slate-900">${event.title}</p>
                        <p class="text-xs text-slate-500 font-medium mt-1 leading-relaxed">${event.message}</p>
                        <button onclick="location.reload()" class="text-[10px] font-bold text-blue-600 uppercase tracking-widest mt-3 hover:underline">View Update</button>
                    </div>
                    <button onclick="this.parentElement.remove()" class="text-slate-300 hover:text-slate-500 transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                    </button>
                `;

                toastContainer.prepend(toast);
                setTimeout(() => toast.classList.remove('translate-x-full'), 100);
                if (sound) sound.play().catch(e => console.log("Audio play failed"));
                setTimeout(() => {
                    toast.classList.add('translate-x-full');
                    setTimeout(() => toast.remove(), 500);
                }, 10000);
            }

            function checkNotifications() {
                fetch(`api/check_notifications.php?last_check=${encodeURIComponent(lastTimestamp)}`)
                    .then(response => response.json())
                    .then(data => {
                        if (data.events && data.events.length > 0) {
                            data.events.forEach(event => createToast(event));
                        }
                        lastTimestamp = data.timestamp;
                    })
                    .catch(error => console.error('Error checking notifications:', error));
            }

            // Search Functionality
            if (teleSearch) {
                teleSearch.addEventListener('input', function(e) {
                    const term = e.target.value.toLowerCase();
                    const rows = document.querySelectorAll('#teleTable tbody tr');
                    rows.forEach(row => {
                        const text = row.innerText.toLowerCase();
                        row.style.display = text.includes(term) ? '' : 'none';
                    });
                });
            }

            const params = new URLSearchParams(window.location.search);
            if (params.has('updated')) {
                createToast({type: 'success', title: 'Action Successful', message: 'Telemedicine appointment status updated and email sent.'});
            }
            
            setInterval(checkNotifications, 10000);
            setTimeout(checkNotifications, 2000);
        });
    </script>
</body>
</html>