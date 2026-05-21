<?php
session_start();
require_once '../includes/db_connect.php';

if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: index.php');
    exit;
}

// Stats for overview
$total_patients = $conn->query("SELECT COUNT(*) FROM patient_onboarding")->fetch_row()[0];
$pending_records = $conn->query("SELECT COUNT(*) FROM patient_onboarding WHERE status = 'Pending Records'")->fetch_row()[0];
$verified_count = $conn->query("SELECT COUNT(*) FROM patient_onboarding WHERE status = 'Verified'")->fetch_row()[0];
$nursing_queue = $conn->query("SELECT COUNT(*) FROM patient_onboarding WHERE status = 'Sent to Nursing'")->fetch_row()[0];

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Records Management | Hope Haven</title>
    <?php include 'includes/header_scripts.php'; ?>
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; background-color: #f8fafc; }
        .tab-btn.active { background-color: #2563eb; color: white; box-shadow: 0 10px 15px -3px rgba(37, 99, 235, 0.2); }
        .custom-scrollbar::-webkit-scrollbar { width: 4px; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background: #e2e8f0; border-radius: 10px; }
    </style>
</head>
<body class="min-h-screen">
    <?php include 'includes/admin_sidebar.php'; ?>
    <?php include 'includes/admin_topbar.php'; ?>

    <main id="main-wrapper" class="pt-[90px] px-8 pb-10 lg:ml-[280px] transition-all duration-300">
        <div class="flex justify-between items-end mb-10">
            <div>
                <h1 class="text-4xl font-black text-slate-900 mb-2 tracking-tight">Medical Records</h1>
                <p class="text-slate-500 font-medium">Manage patient onboarding, folders, and workflow transitions.</p>
            </div>
            <div class="flex gap-4">
                <div class="bg-white p-4 rounded-2xl border border-slate-100 shadow-sm flex items-center gap-4">
                    <div class="w-10 h-10 bg-blue-50 text-blue-600 rounded-xl flex items-center justify-center">
                        <i class="fas fa-users"></i>
                    </div>
                    <div>
                        <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest">In System</p>
                        <p class="text-lg font-black text-slate-900"><?php echo $total_patients; ?></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- TABS NAVIGATION -->
        <div class="flex gap-2 mb-8 bg-white p-2 rounded-2xl border border-slate-100 w-fit">
            <button onclick="switchTab('dashboard')" id="tab-dashboard" class="tab-btn px-6 py-3 rounded-xl text-xs font-black uppercase tracking-widest transition-all active">
                <i class="fas fa-inbox mr-2"></i> Registration Inbox (<?php echo $pending_records; ?>)
            </button>
            <button onclick="switchTab('all')" id="tab-all" class="tab-btn px-6 py-3 rounded-xl text-xs font-black uppercase tracking-widest transition-all text-slate-400 hover:bg-slate-50">
                <i class="fas fa-list mr-2"></i> All Patients
            </button>
            <button onclick="switchTab('archive')" id="tab-archive" class="tab-btn px-6 py-3 rounded-xl text-xs font-black uppercase tracking-widest transition-all text-slate-400 hover:bg-slate-50">
                <i class="fas fa-archive mr-2"></i> Sent to Nursing (<?php echo $nursing_queue; ?>)
            </button>
        </div>

        <!-- SEARCH BAR -->
        <div class="relative mb-6">
            <i class="fas fa-search absolute left-6 top-1/2 -translate-y-1/2 text-slate-400"></i>
            <input type="text" id="patientSearch" placeholder="Search by name, file number or phone..." class="w-full pl-14 pr-6 py-5 bg-white border border-slate-100 rounded-[24px] shadow-sm text-sm font-bold outline-none focus:ring-2 focus:ring-blue-600 transition-all">
        </div>

        <!-- CONTENT AREA -->
        <div id="tab-content" class="bg-white rounded-[40px] border border-slate-100 shadow-xl shadow-slate-200/50 overflow-hidden">
            <div class="overflow-x-auto custom-scrollbar">
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
                <p class="text-slate-400 font-bold uppercase tracking-widest text-[10px]">Loading Records...</p>
            </div>
            <div id="empty-state" class="p-20 text-center hidden">
                <div class="w-20 h-20 bg-slate-50 text-slate-300 rounded-3xl flex items-center justify-center mx-auto mb-6">
                    <i class="fas fa-folder-open text-3xl"></i>
                </div>
                <h3 class="text-xl font-black text-slate-900 mb-2">No Records Found</h3>
                <p class="text-slate-500 font-medium">There are no patients matching this criteria currently.</p>
            </div>
        </div>
    </main>

    <!-- PATIENT DETAILS MODAL -->
    <div id="detailsModal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-md z-[100] hidden flex items-center justify-center p-6">
        <div class="bg-white max-w-5xl w-full rounded-[40px] shadow-2xl overflow-hidden flex flex-col max-h-[90vh]">
            <div class="p-8 border-b border-slate-100 flex justify-between items-center bg-slate-50/50">
                <div class="flex items-center gap-4">
                    <div id="modalPatientInitial" class="w-14 h-14 bg-blue-600 text-white rounded-2xl flex items-center justify-center font-black text-xl">P</div>
                    <div>
                        <h2 class="text-2xl font-black text-slate-900 tracking-tight" id="modalPatientName">---</h2>
                        <p class="text-[10px] font-black text-blue-600 uppercase tracking-widest" id="modalFileNumber">#FILE-000</p>
                    </div>
                </div>
                <div class="flex gap-2">
                    <button onclick="closeModal()" class="p-3 text-slate-400 hover:text-slate-600 hover:bg-slate-50 rounded-2xl transition-all">
                        <i class="fas fa-times text-xl"></i>
                    </button>
                </div>
            </div>
            
            <div class="flex-1 overflow-y-auto custom-scrollbar p-10">
                <div class="grid md:grid-cols-3 gap-10">
                    <!-- Left: Basic Info & Actions -->
                    <div class="space-y-8">
                        <div>
                            <h4 class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-4">Patient Actions</h4>
                            <div class="flex flex-col gap-2" id="modalActions">
                                <!-- Actions injected here -->
                            </div>
                        </div>
                        <div class="p-6 bg-slate-50 rounded-[32px] border border-slate-100">
                            <h4 class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-4">Contact Info</h4>
                            <div class="space-y-4">
                                <div class="flex items-center gap-3">
                                    <i class="fas fa-envelope text-slate-300"></i>
                                    <p class="text-sm font-bold text-slate-700" id="modalEmail">---</p>
                                </div>
                                <div class="flex items-center gap-3">
                                    <i class="fas fa-phone text-slate-300"></i>
                                    <p class="text-sm font-bold text-slate-700" id="modalPhone">---</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Right: Form Content -->
                    <div class="md:col-span-2">
                        <div class="flex justify-between items-center mb-6">
                            <h4 class="text-lg font-black text-slate-900 uppercase tracking-tight">Form Completion Progress</h4>
                            <span id="modalProgressBadge" class="px-4 py-1.5 bg-blue-50 text-blue-600 text-[10px] font-black rounded-full">70% Complete</span>
                        </div>
                        <div class="w-full h-3 bg-slate-100 rounded-full mb-10 overflow-hidden">
                            <div id="modalProgressBar" class="h-full bg-blue-600 transition-all duration-1000" style="width: 0%"></div>
                        </div>

                        <div id="modalFormContent" class="space-y-10">
                            <!-- Sections injected here -->
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- EDIT MODAL -->
    <div id="editModal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-md z-[110] hidden flex items-center justify-center p-6">
        <div class="bg-white max-w-md w-full rounded-[40px] shadow-2xl p-10">
            <h3 class="text-2xl font-black text-slate-900 mb-6">Edit Patient Details</h3>
            <form id="editForm" class="space-y-6">
                <input type="hidden" id="edit_patient_id">
                <div class="space-y-2">
                    <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-4">Full Name</label>
                    <input type="text" id="edit_full_name" required class="w-full px-6 py-4 bg-slate-50 rounded-2xl border-0 font-bold focus:ring-2 focus:ring-blue-600 outline-none">
                </div>
                <div class="space-y-2">
                    <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-4">Email Address</label>
                    <input type="email" id="edit_email" required class="w-full px-6 py-4 bg-slate-50 rounded-2xl border-0 font-bold focus:ring-2 focus:ring-blue-600 outline-none">
                </div>
                <div class="space-y-2">
                    <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-4">Phone Number</label>
                    <input type="text" id="edit_phone" required class="w-full px-6 py-4 bg-slate-50 rounded-2xl border-0 font-bold focus:ring-2 focus:ring-blue-600 outline-none">
                </div>
                <div class="flex gap-3 pt-4">
                    <button type="button" onclick="closeEditModal()" class="flex-1 py-4 bg-slate-100 text-slate-500 rounded-2xl font-black text-xs uppercase tracking-widest hover:bg-slate-200 transition-all">Cancel</button>
                    <button type="submit" class="flex-1 py-4 bg-blue-600 text-white rounded-2xl font-black text-xs uppercase tracking-widest hover:bg-blue-700 transition-all shadow-xl shadow-blue-100">Save Changes</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        let currentTab = 'dashboard';
        let allPatients = [];

        function switchTab(tab) {
            currentTab = tab;
            document.querySelectorAll('.tab-btn').forEach(btn => {
                btn.classList.remove('active', 'bg-blue-600', 'text-white');
                btn.classList.add('text-slate-400', 'hover:bg-slate-50');
            });
            const activeBtn = document.getElementById(`tab-${tab}`);
            activeBtn.classList.add('active', 'bg-blue-600', 'text-white');
            activeBtn.classList.remove('text-slate-400', 'hover:bg-slate-50');
            
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
                const response = await fetch(`api/get_records_data.php?tab=${currentTab}`);
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
                    'Sent to Nursing': 'bg-blue-50 text-blue-600 border-blue-100',
                    'Paid': 'bg-indigo-50 text-indigo-600 border-indigo-100',
                    'Payment Pending': 'bg-rose-50 text-rose-600 border-rose-100'
                };

                const payColors = {
                    'Confirmed': 'text-emerald-500',
                    'Pending': 'text-amber-500',
                    'Failed': 'text-rose-500'
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
                        <div class="flex items-center gap-1.5">
                            <i class="fas fa-circle text-[6px] ${payColors[p.payment_status]}"></i>
                            <span class="text-[9px] font-black uppercase tracking-widest text-slate-400">Payment: ${p.payment_status}</span>
                        </div>
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
                        <span class="px-3 py-1.5 rounded-full text-[9px] font-black uppercase tracking-widest border ${statusColors[p.status] || 'bg-slate-50 text-slate-400'}">
                            ${p.status}
                        </span>
                        ${p.is_locked == 1 ? '<i class="fas fa-lock ml-2 text-rose-500 text-[10px]"></i>' : ''}
                    </td>
                    <td class="px-8 py-6 text-right">
                        <button onclick="viewDetails(${p.patient_id})" class="px-4 py-2 bg-slate-900 text-white rounded-xl text-[10px] font-black uppercase tracking-widest hover:bg-blue-600 transition-all">
                            Manage
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
            document.getElementById('modalEmail').innerText = p.email;
            document.getElementById('modalPhone').innerText = p.phone;
            document.getElementById('modalPatientInitial').innerText = p.full_name.charAt(0);
            document.getElementById('modalProgressBadge').innerText = `${p.form_progress}% Complete`;
            document.getElementById('modalProgressBar').style.width = `${p.form_progress}%`;

            // Actions
            const actionsDiv = document.getElementById('modalActions');
            actionsDiv.innerHTML = `
                <button onclick="openEditModal(${p.patient_id})" class="w-full py-3 bg-white border border-slate-200 text-slate-600 rounded-xl text-[10px] font-black uppercase tracking-widest hover:bg-slate-50 transition-all flex items-center justify-center gap-2">
                    <i class="fas fa-edit"></i> Edit Details
                </button>
                ${p.is_locked == 0 ? `
                    <button onclick="performAction('lock', ${p.patient_id})" class="w-full py-3 bg-amber-50 text-amber-600 rounded-xl text-[10px] font-black uppercase tracking-widest hover:bg-amber-100 transition-all flex items-center justify-center gap-2">
                        <i class="fas fa-lock"></i> Lock Record
                    </button>
                ` : `
                    <button onclick="performAction('unlock', ${p.patient_id})" class="w-full py-3 bg-blue-50 text-blue-600 rounded-xl text-[10px] font-black uppercase tracking-widest hover:bg-blue-100 transition-all flex items-center justify-center gap-2">
                        <i class="fas fa-unlock"></i> Unlock Record
                    </button>
                `}
                ${p.status === 'Pending Records' ? `
                    <button onclick="performAction('approve', ${p.patient_id})" class="w-full py-3 bg-emerald-500 text-white rounded-xl text-[10px] font-black uppercase tracking-widest hover:bg-emerald-600 transition-all shadow-lg shadow-emerald-100 flex items-center justify-center gap-2">
                        <i class="fas fa-check-circle"></i> Verify & Approve
                    </button>
                ` : ''}
                ${p.status === 'Verified' ? `
                    <button onclick="performAction('send_to_nursing', ${p.patient_id})" class="w-full py-3 bg-blue-600 text-white rounded-xl text-[10px] font-black uppercase tracking-widest hover:bg-blue-700 transition-all shadow-lg shadow-blue-100 flex items-center justify-center gap-2">
                        <i class="fas fa-paper-plane"></i> Send to Nursing
                    </button>
                ` : ''}
            `;

            // Load Form Content
            const formContent = document.getElementById('modalFormContent');
            formContent.innerHTML = '<div class="flex justify-center py-10"><i class="fas fa-spinner fa-spin text-3xl text-blue-600"></i></div>';
            
            try {
                const res = await fetch(`api/get_onboarding_form.php?patient_id=${p.patient_id}`);
                const formData = await res.json();
                if (formData.success) {
                    let html = '';
                    for (const section in formData.form_data) {
                        html += `
                            <div class="animate-in">
                                <h5 class="text-xs font-black text-blue-600 uppercase tracking-widest mb-6 pb-2 border-b border-blue-50 flex items-center gap-2">
                                    <i class="fas fa-chevron-right text-[8px]"></i> ${section}
                                </h5>
                                <div class="grid md:grid-cols-2 gap-x-10 gap-y-6">
                        `;
                        for (const field in formData.form_data[section]) {
                            html += `
                                <div class="space-y-1">
                                    <p class="text-[10px] font-black text-slate-400 uppercase tracking-wider">${field.replace(/_/g, ' ')}</p>
                                    <p class="text-sm font-bold text-slate-700">${formData.form_data[section][field] || '---'}</p>
                                </div>
                            `;
                        }
                        html += `</div></div>`;
                    }
                    formContent.innerHTML = html || '<p class="text-center text-slate-400 italic">No form data provided.</p>';
                }
            } catch (e) {
                formContent.innerHTML = '<p class="text-rose-500 font-bold">Error loading form data.</p>';
            }

            document.getElementById('detailsModal').classList.remove('hidden');
        }

        async function performAction(action, patientId) {
            if (!confirm(`Are you sure you want to perform this action?`)) return;

            const formData = new FormData();
            formData.append('action', action);
            formData.append('patient_id', patientId);

            try {
                const res = await fetch('api/records_actions.php', {
                    method: 'POST',
                    body: formData
                });
                const result = await res.json();
                if (result.success) {
                    showProminentAlert('Success', result.message, 'success');
                    closeModal();
                    loadData();
                } else {
                    alert(result.message);
                }
            } catch (e) {
                alert('An error occurred.');
            }
        }

        function openEditModal(patientId) {
            const p = allPatients.find(x => x.patient_id == patientId);
            if (!p) return;

            document.getElementById('edit_patient_id').value = p.patient_id;
            document.getElementById('edit_full_name').value = p.full_name;
            document.getElementById('edit_email').value = p.email;
            document.getElementById('edit_phone').value = p.phone;

            document.getElementById('editModal').classList.remove('hidden');
        }

        document.getElementById('editForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            const formData = new FormData();
            formData.append('action', 'edit_details');
            formData.append('patient_id', document.getElementById('edit_patient_id').value);
            formData.append('full_name', document.getElementById('edit_full_name').value);
            formData.append('email', document.getElementById('edit_email').value);
            formData.append('phone', document.getElementById('edit_phone').value);

            try {
                const res = await fetch('api/records_actions.php', {
                    method: 'POST',
                    body: formData
                });
                const result = await res.json();
                if (result.success) {
                    showProminentAlert('Success', result.message, 'success');
                    closeEditModal();
                    closeModal();
                    loadData();
                } else {
                    alert(result.message);
                }
            } catch (e) {
                alert('An error occurred.');
            }
        });

        function closeEditModal() {
            document.getElementById('editModal').classList.add('hidden');
        }

        function closeModal() {
            document.getElementById('detailsModal').classList.add('hidden');
        }

        // Search filtering
        document.getElementById('patientSearch').addEventListener('input', (e) => {
            const term = e.target.value.toLowerCase();
            const filtered = allPatients.filter(p => 
                p.full_name.toLowerCase().includes(term) || 
                p.file_number.toLowerCase().includes(term) ||
                p.phone.includes(term)
            );
            renderTable(filtered);
        });

        // Initialize
        switchTab('dashboard');

        function showProminentAlert(title, message, type = 'info') {
            document.getElementById('alertTitle').innerText = title;
            document.getElementById('alertMessage').innerText = message;
            document.getElementById('prominentAlert').classList.remove('hidden');
            
            const iconBox = document.getElementById('alertIconBox');
            if (type === 'success') {
                iconBox.className = "w-24 h-24 bg-emerald-50 text-emerald-600 rounded-[32px] flex items-center justify-center mx-auto mb-8 shadow-inner";
                iconBox.innerHTML = '<i class="fas fa-check-circle text-3xl"></i>';
            } else {
                iconBox.className = "w-24 h-24 bg-blue-50 text-blue-600 rounded-[32px] flex items-center justify-center mx-auto mb-8 shadow-inner";
                iconBox.innerHTML = '<i class="fas fa-bell text-3xl"></i>';
            }
        }

        function closeAlert() {
            document.getElementById('prominentAlert').classList.add('hidden');
        }
    </script>
    <?php include 'includes/admin_footer.php'; ?>
</body>
</html>