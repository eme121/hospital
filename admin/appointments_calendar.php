<?php
session_start();
date_default_timezone_set('Africa/Lagos');
require_once '../includes/db_connect.php';

if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: index.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Appointment Calendar | Hope Haven Hospital</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <?php include 'includes/header_scripts.php'; ?>
    
    <!-- FullCalendar CDN -->
    <script src='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.js'></script>
    
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
        .fc { --fc-border-color: #f1f5f9; --fc-today-bg-color: #f8fafc; }
        .fc-toolbar-title { font-weight: 800 !important; color: #0f172a !important; font-size: 1.5rem !important; }
        .fc-button-primary { background-color: #3b82f6 !important; border-color: #3b82f6 !important; font-weight: 700 !important; text-transform: uppercase !important; font-size: 0.75rem !important; letter-spacing: 0.05em !important; padding: 0.6rem 1.2rem !important; border-radius: 12px !important; }
        .fc-event { border-radius: 8px !important; padding: 4px 8px !important; font-weight: 700 !important; font-size: 0.75rem !important; border: none !important; box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1); }
        .fc-daygrid-day.fc-day-today { background-color: #eff6ff !important; }
        .fc-col-header-cell { padding: 12px 0 !important; background-color: #f8fafc; }
        .fc-col-header-cell-cushion { font-size: 0.7rem !important; font-weight: 800 !important; text-transform: uppercase !important; letter-spacing: 0.1em !important; color: #64748b !important; }
    </style>
</head>
<body class="bg-slate-50 min-h-screen">
    <?php include 'includes/admin_sidebar.php'; ?>
    <?php include 'includes/admin_topbar.php'; ?>

    <main id="main-wrapper" class="pt-[90px] px-8 pb-10 lg:ml-[280px] transition-all duration-300">
        <div class="mb-10 flex flex-col lg:flex-row justify-between items-start lg:items-end gap-6">
            <div>
                <h1 class="text-3xl font-black text-slate-900 mb-2 tracking-tight">Appointment Calendar</h1>
                <p class="text-slate-500 font-medium">Interactive schedule for physical and virtual consultations.</p>
            </div>
            <div class="flex gap-3">
                <div class="flex items-center gap-2 px-4 py-2 bg-white rounded-xl border border-slate-200 shadow-sm">
                    <div class="w-3 h-3 rounded-full bg-blue-500"></div>
                    <span class="text-[10px] font-black text-slate-600 uppercase tracking-widest">Physical</span>
                </div>
                <div class="flex items-center gap-2 px-4 py-2 bg-white rounded-xl border border-slate-200 shadow-sm">
                    <div class="w-3 h-3 rounded-full bg-indigo-500"></div>
                    <span class="text-[10px] font-black text-slate-600 uppercase tracking-widest">Virtual</span>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-[40px] shadow-xl shadow-slate-200/50 border border-slate-100 p-8">
            <div id='calendar'></div>
        </div>
    </main>

    <!-- Appointment Detail Modal -->
    <div id="eventModal" class="fixed inset-0 z-[100] hidden">
        <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm"></div>
        <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-full max-w-lg p-6">
            <div class="bg-white rounded-[40px] shadow-2xl overflow-hidden animate-zoom-in">
                <div id="modalHeader" class="p-8 text-white relative">
                    <button onclick="closeModal()" class="absolute top-6 right-6 w-10 h-10 bg-white/20 hover:bg-white/30 rounded-full flex items-center justify-center transition-all">
                        <i class="fas fa-times"></i>
                    </button>
                    <p id="modalType" class="text-[10px] font-black uppercase tracking-[0.2em] mb-2 opacity-80"></p>
                    <h3 id="modalPatient" class="text-2xl font-black"></h3>
                    <p id="modalDoctor" class="text-sm font-bold opacity-90"></p>
                </div>
                <div class="p-8 space-y-6">
                    <div class="grid grid-cols-2 gap-6">
                        <div>
                            <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">Date</p>
                            <p id="modalDate" class="font-bold text-slate-900"></p>
                        </div>
                        <div>
                            <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">Time</p>
                            <p id="modalTime" class="font-bold text-slate-900"></p>
                        </div>
                    </div>
                    <div>
                        <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">Current Status</p>
                        <span id="modalStatus" class="px-4 py-1.5 rounded-full text-[10px] font-black uppercase tracking-wider inline-block"></span>
                    </div>

                    <div id="modalActions" class="pt-6 border-t border-slate-100 flex flex-wrap gap-3">
                        <!-- Actions will be injected -->
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        let calendar;
        let currentEvent = null;

        document.addEventListener('DOMContentLoaded', function() {
            const calendarEl = document.getElementById('calendar');
            calendar = new FullCalendar.Calendar(calendarEl, {
                initialView: 'dayGridMonth',
                headerToolbar: {
                    left: 'prev,next today',
                    center: 'title',
                    right: 'dayGridMonth,timeGridWeek,listMonth'
                },
                events: '../api/get_calendar_events.php',
                eventClick: function(info) {
                    showEventDetails(info.event);
                },
                height: 'auto',
                eventTimeFormat: {
                    hour: 'numeric',
                    minute: '2-digit',
                    meridiem: 'short'
                }
            });
            calendar.render();
        });

        function showEventDetails(event) {
            currentEvent = event;
            const props = event.extendedProps;
            const modal = document.getElementById('eventModal');
            const header = document.getElementById('modalHeader');
            
            // Set Header Color
            header.style.backgroundColor = event.backgroundColor;
            
            document.getElementById('modalType').textContent = props.type + ' Appointment';
            document.getElementById('modalPatient').textContent = event.title.split(' (')[0];
            document.getElementById('modalDoctor').textContent = props.doctor ? 'Dr. ' + props.doctor : 'No Doctor Assigned';
            
            const startDate = new Date(event.start);
            document.getElementById('modalDate').textContent = startDate.toLocaleDateString('en-US', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' });
            document.getElementById('modalTime').textContent = startDate.toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit', hour12: true });
            
            const statusBadge = document.getElementById('modalStatus');
            statusBadge.textContent = props.status;
            statusBadge.className = 'px-4 py-1.5 rounded-full text-[10px] font-black uppercase tracking-wider inline-block ';
            
            if (props.status === 'Confirmed') statusBadge.classList.add('bg-emerald-50', 'text-emerald-600', 'border', 'border-emerald-100');
            else if (props.status === 'Cancelled') statusBadge.classList.add('bg-rose-50', 'text-rose-600', 'border', 'border-rose-100');
            else if (props.status === 'Completed') statusBadge.classList.add('bg-blue-50', 'text-blue-600', 'border', 'border-blue-100');
            else statusBadge.classList.add('bg-amber-50', 'text-amber-600', 'border', 'border-amber-100');

            // Actions
            const actionsDiv = document.getElementById('modalActions');
            actionsDiv.innerHTML = '';
            
            const id = event.id.split('_')[1];
            const type = props.type;

            if (props.status === 'Pending') {
                actionsDiv.innerHTML += `<button onclick="updateStatus('${id}', '${type}', 'status_update', 'Confirmed')" class="px-6 py-3 bg-emerald-500 text-white rounded-2xl text-xs font-black uppercase tracking-widest hover:bg-emerald-600 transition-all shadow-lg shadow-emerald-200">Confirm Booking</button>`;
                actionsDiv.innerHTML += `<button onclick="updateStatus('${id}', '${type}', 'status_update', 'Cancelled')" class="px-6 py-3 bg-rose-50 text-rose-600 rounded-2xl text-xs font-black uppercase tracking-widest hover:bg-rose-600 hover:text-white transition-all">Cancel</button>`;
            } else if (props.status === 'Confirmed') {
                actionsDiv.innerHTML += `<button onclick="updateStatus('${id}', '${type}', 'status_update', 'Completed')" class="px-6 py-3 bg-blue-600 text-white rounded-2xl text-xs font-black uppercase tracking-widest hover:bg-blue-700 transition-all shadow-lg shadow-blue-200">Mark Completed</button>`;
            }
            
            actionsDiv.innerHTML += `<button onclick="updateStatus('${id}', '${type}', 'archive')" class="px-6 py-3 bg-slate-100 text-slate-600 rounded-2xl text-xs font-black uppercase tracking-widest hover:bg-slate-200 transition-all">Archive</button>`;

            modal.classList.remove('hidden');
        }

        function closeModal() {
            document.getElementById('eventModal').classList.add('hidden');
        }

        async function updateStatus(id, type, action, status = null) {
            if (!confirm(`Are you sure you want to perform this action?`)) return;

            try {
                const response = await fetch('api/manage_appointments.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ 
                        action: action, 
                        items: [{ id: id, type: type }],
                        status: status 
                    })
                });
                const data = await response.json();
                if (data.success) {
                    calendar.refetchEvents();
                    closeModal();
                } else {
                    alert('Error: ' + data.message);
                }
            } catch (err) {
                console.error(err);
                alert('An error occurred.');
            }
        }
    </script>
    <?php include 'includes/admin_footer.php'; ?>
</body>
</html>