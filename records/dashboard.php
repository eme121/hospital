<?php
session_start();
require_once '../includes/db_connect.php';

if (!isset($_SESSION['records_id'])) {
    header('Location: login.php');
    exit;
}

$officer_name = $_SESSION['records_name'];

// Stats for overview
$pending_records = $conn->query("SELECT COUNT(*) FROM patient_onboarding WHERE status = 'Pending Records'")->fetch_row()[0];
$verified_count = $conn->query("SELECT COUNT(*) FROM patient_onboarding WHERE status = 'Verified'")->fetch_row()[0];
$nursing_queue = $conn->query("SELECT COUNT(*) FROM patient_onboarding WHERE status = 'Sent to Nursing'")->fetch_row()[0];

$portal_title = "Records Portal | Hope Haven";
include '../includes/portal_head.php';
?>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
    .tab-btn.active { background-color: #2563eb; color: white; box-shadow: 0 10px 15px -3px rgba(37, 99, 235, 0.2); }
    .custom-scrollbar::-webkit-scrollbar { width: 4px; }
    .custom-scrollbar::-webkit-scrollbar-thumb { background: #e2e8f0; border-radius: 10px; }
    .animate-in { animation: fadeIn 0.3s ease-out; }
    @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
</style>
<body class="min-h-screen flex bg-slate-50">

    <!-- Sidebar -->
    <aside class="w-72 bg-[#0f172a] text-slate-400 p-8 flex flex-col shrink-0">
        <div class="flex items-center gap-3 mb-12">
            <div class="w-10 h-10 bg-blue-600 rounded-xl flex items-center justify-center text-white font-black shadow-lg shadow-blue-500/20">R</div>
            <h1 class="text-xl font-black text-white tracking-tighter">RECORDS<span class="text-blue-500">PORTAL</span></h1>
        </div>

        <nav class="flex-1 space-y-2">
            <button onclick="switchTab('dashboard')" id="tab-dashboard" class="tab-btn w-full flex items-center gap-4 px-4 py-3 rounded-xl font-bold text-sm transition-all active">
                <i class="fas fa-inbox w-5"></i> Inbox (<?php echo $pending_records; ?>)
            </button>
            <button onclick="switchTab('all')" id="tab-all" class="tab-btn w-full flex items-center gap-4 px-4 py-3 rounded-xl font-bold text-sm transition-all text-slate-400 hover:bg-slate-800">
                <i class="fas fa-users w-5"></i> All Patients
            </button>
            <button onclick="switchTab('archive')" id="tab-archive" class="tab-btn w-full flex items-center gap-4 px-4 py-3 rounded-xl font-bold text-sm transition-all text-slate-400 hover:bg-slate-800">
                <i class="fas fa-paper-plane w-5"></i> Nursing Queue
            </button>
        </nav>

        <div class="mt-auto pt-8 border-t border-slate-800">
            <div class="mb-6">
                <p class="text-[10px] font-black uppercase tracking-widest text-slate-500">Logged in as</p>
                <p class="text-sm font-bold text-white"><?php echo htmlspecialchars($officer_name); ?></p>
            </div>
            <a href="logout.php" class="flex items-center gap-4 px-4 py-3 text-rose-500 hover:bg-rose-500/10 rounded-xl font-bold text-sm transition-all">
                <i class="fas fa-sign-out-alt"></i> Sign Out
            </a>
        </div>
    </aside>

    <!-- Main Content -->
    <main class="flex-1 flex flex-col min-w-0 h-screen overflow-hidden">
        <header class="h-24 bg-white border-b border-slate-200 flex items-center justify-between px-10 shrink-0">
            <h2 class="text-2xl font-black text-slate-900 uppercase tracking-tight" id="header-title">Registration Inbox</h2>
            <div class="flex items-center gap-6">
                <div class="relative">
                    <i class="fas fa-search absolute left-4 top-1/2 -translate-y-1/2 text-slate-400"></i>
                    <input type="text" id="patientSearch" placeholder="Search records..." class="pl-12 pr-6 py-3 bg-slate-50 border-0 rounded-2xl text-sm font-bold outline-none focus:ring-2 focus:ring-blue-600 transition-all w-64">
                </div>
            </div>
        </header>

        <div class="flex-1 overflow-y-auto custom-scrollbar p-10">
            <!-- CONTENT AREA -->
            <div id="tab-content" class="bg-white rounded-[40px] border border-slate-100 shadow-xl shadow-slate-200/50 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-slate-50/50 border-b border-slate-100">
                                <th class="px-8 py-6 text-[10px] font-black text-slate-400 uppercase tracking-widest">Patient Details</th>
                                <th class="px-8 py-6 text-[10px] font-black text-slate-400 uppercase tracking-widest">Folder & Payment</th>
                                <th class="px-8 py-6 text-[10px] font-black text-slate-400 uppercase tracking-widest">Form Progress</th>
                                <th class="px-8 py-6 text-[10px] font-black text-slate-400 uppercase tracking-widest">Current Status</th>
                                <th class="px-8 py-6 text-[10px] font-black text-slate-400 uppercase tracking-widest text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="records-tbody" class="divide-y divide-slate-50">
                            <!-- Loaded via JS -->
                        </tbody>
                    </table>
                </div>
                <div id="loader" class="p-20 text-center hidden">
                    <i class="fas fa-spinner fa-spin text-4xl text-blue-600 mb-4"></i>
                    <p class="text-slate-400 font-bold uppercase tracking-widest text-[10px]">Syncing Data...</p>
                </div>
                <div id="empty-state" class="p-20 text-center hidden">
                    <div class="w-20 h-20 bg-slate-50 text-slate-300 rounded-3xl flex items-center justify-center mx-auto mb-6">
                        <i class="fas fa-folder-open text-3xl"></i>
                    </div>
                    <h3 class="text-xl font-black text-slate-900 mb-2">Queue Clear</h3>
                    <p class="text-slate-500 font-medium">No files are currently waiting for your attention.</p>
                </div>
            </div>
        </div>
    </main>

    <!-- PATIENT DETAILS MODAL (COPIED FROM ADMIN FOR UNIFIED EXPERIENCE) -->
    <div id="detailsModal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-md z-[100] hidden flex items-center justify-center p-6">
        <div class="bg-white max-w-5xl w-full rounded-[40px] shadow-2xl overflow-hidden flex flex-col max-h-[90vh] animate-in">
            <div class="p-8 border-b border-slate-100 flex justify-between items-center bg-slate-50/50">
                <div class="flex items-center gap-4">
                    <div id="modalPatientInitial" class="w-14 h-14 bg-blue-600 text-white rounded-2xl flex items-center justify-center font-black text-xl">P</div>
                    <div>
                        <h2 class="text-2xl font-black text-slate-900 tracking-tight" id="modalPatientName">---</h2>
                        <p class="text-[10px] font-black text-blue-600 uppercase tracking-widest" id="modalFileNumber">#FILE-000</p>
                    </div>
                </div>
                <button onclick="closeModal()" class="p-3 text-slate-400 hover:text-slate-600 hover:bg-slate-50 rounded-2xl transition-all">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
            
            <div class="flex-1 overflow-y-auto custom-scrollbar p-10">
                <div class="grid md:grid-cols-3 gap-10">
                    <div class="space-y-8">
                        <div>
                            <h4 class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-4">Officer Actions</h4>
                            <div class="flex flex-col gap-2" id="modalActions"></div>
                        </div>
                    </div>
                    <div class="md:col-span-2">
                        <div class="flex justify-between items-center mb-6">
                            <h4 class="text-lg font-black text-slate-900 uppercase tracking-tight">Form Completion Progress</h4>
                            <span id="modalProgressBadge" class="px-4 py-1.5 bg-blue-50 text-blue-600 text-[10px] font-black rounded-full">0%</span>
                        </div>
                        <div class="w-full h-3 bg-slate-100 rounded-full mb-10 overflow-hidden">
                            <div id="modalProgressBar" class="h-full bg-blue-600 transition-all duration-1000" style="width: 0%"></div>
                        </div>
                        <div id="modalFormContent" class="space-y-10"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        let currentTab = 'dashboard';
        let allPatients = [];

        function switchTab(tab) {
            currentTab = tab;
            document.querySelectorAll('.tab-btn').forEach(btn => {
                btn.classList.remove('active', 'bg-blue-600', 'text-white');
                btn.classList.add('text-slate-400', 'hover:bg-slate-800');
            });
            const activeBtn = document.getElementById(`tab-${tab}`);
            activeBtn.classList.add('active', 'bg-blue-600', 'text-white');
            activeBtn.classList.remove('text-slate-400', 'hover:bg-slate-800');
            
            const titles = { 'dashboard': 'Registration Inbox', 'all': 'Full Patient Registry', 'archive': 'Sent to Nursing' };
            document.getElementById('header-title').innerText = titles[tab];

            loadData();
        }

        async function loadData() {
            const tbody = document.getElementById('records-tbody');
            const loader = document.getElementById('loader');
            const empty = document.getElementById('empty-state');
            
            tbody.innerHTML = '';
            loader.classList.remove('hidden');
            empty.classList.add('hidden');

            try {
                const response = await fetch(`../admin/api/get_records_data.php?tab=${currentTab}`);
                const data = await response.json();
                allPatients = data.patients || [];
                renderTable(allPatients);
            } catch (error) {
                console.error('Error loading data:', error);
            } finally {
                loader.classList.add('hidden');
            }
        }

        function renderTable(patients) {
            const tbody = document.getElementById('records-tbody');
            const empty = document.getElementById('empty-state');
            tbody.innerHTML = '';

            if (patients.length === 0) {
                empty.classList.remove('hidden');
                return;
            }

            patients.forEach(p => {
                const statusColors = {
                    'Pending Records': 'bg-amber-50 text-amber-600 border-amber-100',
                    'Verified': 'bg-emerald-50 text-emerald-600 border-emerald-100',
                    'Sent to Nursing': 'bg-blue-50 text-blue-600 border-blue-100'
                };

                const row = document.createElement('tr');
                row.className = 'hover:bg-slate-50/50 transition-all record-row group';
                row.innerHTML = `
                    <td class="px-8 py-6">
                        <div class="flex items-center gap-4">
                            <div class="w-10 h-10 bg-slate-100 text-slate-400 rounded-xl flex items-center justify-center font-black text-sm group-hover:bg-blue-600 group-hover:text-white transition-all">
                                ${p.full_name.charAt(0)}
                            </div>
                            <div>
                                <p class="font-black text-slate-900 text-sm patient-name">${p.full_name}</p>
                                <p class="text-[10px] text-slate-400 font-bold uppercase tracking-widest">#${p.file_number}</p>
                            </div>
                        </div>
                    </td>
                    <td class="px-8 py-6">
                        <p class="text-xs font-black text-slate-700 mb-1">${p.folder_name || 'No Folder'}</p>
                        <span class="text-[9px] font-black uppercase text-emerald-500 tracking-widest">${p.payment_status}</span>
                    </td>
                    <td class="px-8 py-6">
                        <div class="flex items-center gap-3">
                            <div class="flex-1 h-1.5 bg-slate-100 rounded-full overflow-hidden">
                                <div class="h-full bg-blue-600" style="width: ${p.form_progress}%"></div>
                            </div>
                            <span class="text-[10px] font-black text-slate-900">${p.form_progress}%</span>
                        </div>
                    </td>
                    <td class="px-8 py-6">
                        <span class="px-3 py-1.5 rounded-full text-[9px] font-black uppercase border ${statusColors[p.status] || 'bg-slate-50 text-slate-400'}">
                            ${p.status}
                        </span>
                    </td>
                    <td class="px-8 py-6 text-right">
                        <button onclick="viewDetails(${p.patient_id})" class="px-4 py-2 bg-slate-900 text-white rounded-xl text-[10px] font-black uppercase tracking-widest hover:bg-blue-600 transition-all">
                            Review & Verify
                        </button>
                    </td>
                `;
                tbody.appendChild(row);
            });
        }

        async function viewDetails(patientId) {
            const p = allPatients.find(x => x.patient_id == patientId);
            if (!p) return;

            document.getElementById('modalPatientName').innerText = p.full_name;
            document.getElementById('modalFileNumber').innerText = '#' + p.file_number;
            document.getElementById('modalPatientInitial').innerText = p.full_name.charAt(0);
            document.getElementById('modalProgressBadge').innerText = `${p.form_progress}% Complete`;
            document.getElementById('modalProgressBar').style.width = `${p.form_progress}%`;

            const actionsDiv = document.getElementById('modalActions');
            actionsDiv.innerHTML = `
                ${p.status === 'Pending Records' ? `
                    <button onclick="performAction('approve', ${p.patient_id})" class="w-full py-4 bg-emerald-500 text-white rounded-xl text-[10px] font-black uppercase tracking-widest hover:bg-emerald-600 transition-all shadow-lg flex items-center justify-center gap-2">
                        <i class="fas fa-check-circle"></i> Verify & Approve
                    </button>
                ` : ''}
                ${p.status === 'Verified' ? `
                    <button onclick="performAction('send_to_nursing', ${p.patient_id})" class="w-full py-4 bg-blue-600 text-white rounded-xl text-[10px] font-black uppercase tracking-widest hover:bg-blue-700 transition-all shadow-lg flex items-center justify-center gap-2">
                        <i class="fas fa-paper-plane"></i> Send to Nursing
                    </button>
                ` : ''}
                <button onclick="performAction('${p.is_locked == 0 ? 'lock' : 'unlock'}', ${p.patient_id})" class="w-full py-3 ${p.is_locked == 0 ? 'bg-amber-50 text-amber-600' : 'bg-blue-50 text-blue-600'} rounded-xl text-[10px] font-black uppercase tracking-widest flex items-center justify-center gap-2 mt-4">
                    <i class="fas fa-${p.is_locked == 0 ? 'lock' : 'unlock'}"></i> ${p.is_locked == 0 ? 'Lock' : 'Unlock'} Record
                </button>
            `;

            const formContent = document.getElementById('modalFormContent');
            formContent.innerHTML = '<div class="text-center py-10"><i class="fas fa-spinner fa-spin text-3xl text-blue-600"></i></div>';
            
            try {
                const res = await fetch(`../admin/api/get_onboarding_form.php?patient_id=${p.patient_id}`);
                const formData = await res.json();
                if (formData.success) {
                    let html = '';
                    for (const section in formData.form_data) {
                        html += `<div class="animate-in">
                            <h5 class="text-xs font-black text-blue-600 uppercase tracking-widest mb-6 border-b border-blue-50 pb-2">${section}</h5>
                            <div class="grid md:grid-cols-2 gap-6">`;
                        for (const field in formData.form_data[section]) {
                            html += `<div>
                                <p class="text-[10px] font-black text-slate-400 uppercase tracking-wider">${field.replace(/_/g, ' ')}</p>
                                <p class="text-sm font-bold text-slate-700">${formData.form_data[section][field] || '---'}</p>
                            </div>`;
                        }
                        html += `</div></div>`;
                    }
                    formContent.innerHTML = html || '<p class="text-center text-slate-400 italic">No data.</p>';
                }
            } catch (e) { formContent.innerHTML = 'Error.'; }

            document.getElementById('detailsModal').classList.remove('hidden');
        }

        async function performAction(action, patientId) {
            const formData = new FormData();
            formData.append('action', action);
            formData.append('patient_id', patientId);

            try {
                const res = await fetch('../admin/api/records_actions.php', { method: 'POST', body: formData });
                const result = await res.json();
                if (result.success) {
                    closeModal();
                    loadData();
                } else alert(result.message);
            } catch (e) { alert('Error.'); }
        }

        function closeModal() { document.getElementById('detailsModal').classList.add('hidden'); }
        
        switchTab('dashboard');

        // Real-Time Sync Subscription
        if (window.HospitalSync) {
            window.HospitalSync.subscribe('patient_queue', (signal) => {
                console.log('📡 [Records Dashboard] Patient Queue Signal Received:', signal);
                loadData();
            });
            window.HospitalSync.subscribe('notifications', (signal) => {
                console.log('📡 [Records Dashboard] Notification Signal Received:', signal);
                // Potential notification fetch logic here
            });
        }
    </script>
    <?php include '../includes/portal_footer.php'; ?>
</body>
</html>