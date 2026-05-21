<?php
session_start();
if (!isset($_SESSION['lab_tech_id'])) {
    header('Location: login.php');
    exit;
}
require_once '../includes/db_connect.php';

$tech_name = $_SESSION['lab_tech_name'] ?? 'Technician';
$portal_title = "LABPRO | Modern Laboratory Management";
include '../includes/portal_head.php';
?>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<style>
    .tab-btn.active { background-color: #4f46e5; color: white; box-shadow: 0 10px 15px -3px rgba(79, 70, 229, 0.2); }
    .animate-fade-in { animation: fadeIn 0.3s ease-out; }
    @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
</style>

<body class="flex min-h-screen bg-slate-50 text-slate-900">

    <!-- Sidebar -->
    <aside class="w-72 bg-white border-r border-slate-200 hidden lg:flex flex-col sticky top-0 h-screen">
        <div class="p-8">
            <div class="flex items-center gap-3 mb-10">
                <div class="w-10 h-10 bg-indigo-600 rounded-xl flex items-center justify-center text-white font-black shadow-lg shadow-indigo-200">L</div>
                <h1 class="text-xl font-black text-slate-900 tracking-tighter">LAB<span class="text-indigo-600">PRO</span></h1>
            </div>
            
            <nav class="space-y-1">
                <button onclick="showTab('worklist')" class="tab-btn w-full flex items-center gap-3 px-5 py-4 text-slate-500 hover:bg-slate-50 rounded-2xl font-bold transition-all active" id="btn-worklist">
                    <i data-lucide="beaker" class="w-5 h-5"></i> Worklist
                </button>
                <button onclick="showTab('patients')" class="tab-btn w-full flex items-center gap-3 px-5 py-4 text-slate-500 hover:bg-slate-50 rounded-2xl font-bold transition-all" id="btn-patients">
                    <i data-lucide="users" class="w-5 h-5"></i> Patient Directory
                </button>
                <button onclick="showTab('tests')" class="tab-btn w-full flex items-center gap-3 px-5 py-4 text-slate-500 hover:bg-slate-50 rounded-2xl font-bold transition-all" id="btn-tests">
                    <i data-lucide="list" class="w-5 h-5"></i> Test Catalog
                </button>
                <button onclick="showTab('history')" class="tab-btn w-full flex items-center gap-3 px-5 py-4 text-slate-500 hover:bg-slate-50 rounded-2xl font-bold transition-all" id="btn-history">
                    <i data-lucide="history" class="w-5 h-5"></i> Patient Trends
                </button>
            </nav>
        </div>

        <div class="mt-auto p-8 border-t border-slate-100">
            <div class="flex items-center gap-3 mb-6">
                <div class="w-10 h-10 bg-indigo-100 rounded-full flex items-center justify-center text-indigo-600 font-bold"><?php echo substr($tech_name, 0, 1); ?></div>
                <div>
                    <p class="text-xs font-black text-slate-900"><?php echo $tech_name; ?></p>
                    <p class="text-[10px] font-bold text-indigo-500 uppercase">Lab Technician</p>
                </div>
            </div>
            <a href="logout.php" class="flex items-center gap-3 text-red-500 font-bold text-sm hover:translate-x-1 transition-all">
                <i data-lucide="log-out" class="w-5 h-5"></i> Sign Out
            </a>
        </div>
    </aside>

    <!-- Main Content -->
    <main class="flex-1 flex flex-col min-w-0 h-screen overflow-hidden">
        <header class="h-20 bg-white border-b border-slate-200 flex items-center justify-between px-10 shrink-0">
            <h2 id="view-title" class="text-lg font-black text-slate-900 uppercase tracking-tight">Laboratory Worklist</h2>
            <div class="flex items-center gap-4">
                <div class="flex items-center gap-2 px-4 py-2 bg-indigo-50 text-indigo-600 rounded-xl text-[10px] font-black uppercase tracking-widest border border-indigo-100">
                    <i data-lucide="activity" class="w-3 h-3"></i> System Online
                </div>
            </div>
        </header>

        <div class="flex-1 overflow-y-auto p-10 bg-slate-50/50">
            
            <!-- VIEW: WORKLIST -->
            <section id="view-worklist" class="tab-view space-y-8 animate-fade-in">
                <!-- Stats -->
                <div class="grid grid-cols-4 gap-6">
                    <div class="bg-white p-6 rounded-[32px] border border-slate-200 shadow-sm flex items-center gap-5">
                        <div class="w-12 h-12 bg-amber-50 text-amber-600 rounded-2xl flex items-center justify-center"><i data-lucide="clock" class="w-6 h-6"></i></div>
                        <div><p class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Pending</p><h4 class="text-2xl font-black text-slate-900" id="stat-pending">0</h4></div>
                    </div>
                    <div class="bg-white p-6 rounded-[32px] border border-slate-200 shadow-sm flex items-center gap-5">
                        <div class="w-12 h-12 bg-blue-50 text-blue-600 rounded-2xl flex items-center justify-center"><i data-lucide="test-tube-2" class="w-6 h-6"></i></div>
                        <div><p class="text-[10px] font-black text-slate-400 uppercase tracking-widest">In Lab</p><h4 class="text-2xl font-black text-slate-900" id="stat-collected">0</h4></div>
                    </div>
                    <div class="bg-white p-6 rounded-[32px] border border-slate-200 shadow-sm flex items-center gap-5">
                        <div class="w-12 h-12 bg-emerald-50 text-emerald-600 rounded-2xl flex items-center justify-center"><i data-lucide="check-circle" class="w-6 h-6"></i></div>
                        <div><p class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Done (Today)</p><h4 class="text-2xl font-black text-slate-900" id="stat-done">0</h4></div>
                    </div>
                    <div class="bg-white p-6 rounded-[32px] border border-slate-200 shadow-sm flex items-center gap-5">
                        <div class="w-12 h-12 bg-rose-50 text-rose-600 rounded-2xl flex items-center justify-center"><i data-lucide="alert-circle" class="w-6 h-6"></i></div>
                        <div><p class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Urgent</p><h4 class="text-2xl font-black text-rose-600" id="stat-urgent">0</h4></div>
                    </div>
                </div>

                <!-- Tabs for Filtering -->
                <div class="flex gap-4 mb-4">
                    <button onclick="loadRequests('Pending')" class="px-6 py-2 bg-white text-slate-500 rounded-xl font-bold text-xs border border-slate-200 hover:bg-slate-50 transition-all filter-btn active-filter" id="filter-Pending">Pending Collection</button>
                    <button onclick="loadRequests('Sample Collected')" class="px-6 py-2 bg-white text-slate-500 rounded-xl font-bold text-xs border border-slate-200 hover:bg-slate-50 transition-all filter-btn" id="filter-SampleCollected">Processing Results</button>
                </div>

                <div class="bg-white rounded-[40px] border border-slate-200 shadow-sm overflow-hidden">
                    <table class="w-full text-left">
                        <thead class="bg-slate-50 border-b border-slate-200">
                            <tr>
                                <th class="px-8 py-5 text-[10px] font-black text-slate-400 uppercase tracking-widest">Patient & Priority</th>
                                <th class="px-8 py-5 text-[10px] font-black text-slate-400 uppercase tracking-widest">Lab Test</th>
                                <th class="px-8 py-5 text-[10px] font-black text-slate-400 uppercase tracking-widest">Requested By</th>
                                <th class="px-8 py-5 text-[10px] font-black text-slate-400 uppercase tracking-widest text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="worklist-body" class="divide-y divide-slate-100">
                            <!-- Injected -->
                        </tbody>
                    </table>
                </div>
            </section>

            <!-- VIEW: PATIENT DIRECTORY -->
            <section id="view-patients" class="tab-view hidden space-y-8 animate-fade-in">
                <div class="bg-white rounded-[40px] border border-slate-200 shadow-sm p-10">
                    <div class="flex justify-between items-center mb-10">
                        <h3 class="text-2xl font-black text-slate-900">Patient Directory</h3>
                        <div class="relative w-96">
                            <i data-lucide="search" class="absolute left-6 top-1/2 -translate-y-1/2 text-slate-400 w-5 h-5"></i>
                            <input type="text" id="directory-search" placeholder="Search patients..." class="w-full pl-16 pr-8 py-4 bg-slate-50 border-0 rounded-2xl outline-none focus:ring-2 focus:ring-indigo-500 font-bold text-slate-900">
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6" id="directory-list">
                        <!-- Loaded via JS -->
                        <div class="col-span-full py-20 text-center text-slate-400 italic">Loading registered patients...</div>
                    </div>
                </div>
            </section>

            <!-- VIEW: TEST CATALOG -->
            <section id="view-tests" class="tab-view hidden space-y-8 animate-fade-in">
                <div class="flex justify-between items-center">
                    <h3 class="text-2xl font-black text-slate-900">Manage Lab Tests</h3>
                    <button onclick="openTestModal()" class="px-6 py-3 bg-indigo-600 text-white rounded-2xl font-bold text-sm shadow-xl shadow-indigo-100 flex items-center gap-2 hover:bg-indigo-700 transition-all">
                        <i data-lucide="plus" class="w-4 h-4"></i> Add New Test
                    </button>
                </div>
                <div class="bg-white rounded-[40px] border border-slate-200 shadow-sm overflow-hidden">
                    <table class="w-full text-left">
                        <thead class="bg-slate-50 border-b border-slate-200">
                            <tr>
                                <th class="px-8 py-5 text-[10px] font-black text-slate-400 uppercase tracking-widest">Category</th>
                                <th class="px-8 py-5 text-[10px] font-black text-slate-400 uppercase tracking-widest">Test Name</th>
                                <th class="px-8 py-5 text-[10px] font-black text-slate-400 uppercase tracking-widest">Unit & Range</th>
                                <th class="px-8 py-5 text-[10px] font-black text-slate-400 uppercase tracking-widest">Price</th>
                                <th class="px-8 py-5 text-[10px] font-black text-slate-400 uppercase tracking-widest text-right">Action</th>
                            </tr>
                        </thead>
                        <tbody id="tests-body" class="divide-y divide-slate-100">
                            <!-- Injected -->
                        </tbody>
                    </table>
                </div>
            </section>

            <!-- VIEW: PATIENT TRENDS -->
            <section id="view-history" class="tab-view hidden space-y-8 animate-fade-in">
                <div class="bg-white rounded-[40px] border border-slate-200 shadow-sm p-10">
                    <h3 class="text-2xl font-black text-slate-900 mb-8">Intelligent Patient Trends</h3>
                    <div class="grid grid-cols-3 gap-6 mb-10">
                        <div class="space-y-2 relative">
                            <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-4">Select Patient</label>
                            <input type="text" id="patient-search" placeholder="Patient Name or File #" class="w-full px-6 py-4 bg-slate-50 rounded-2xl border-0 focus:ring-2 focus:ring-indigo-500 font-bold" autocomplete="off">
                            <div id="search-results-history" class="hidden absolute left-0 right-0 top-full mt-2 bg-white rounded-3xl border border-slate-200 shadow-2xl z-10 max-h-60 overflow-y-auto divide-y divide-slate-50"></div>
                        </div>
                        <div class="space-y-2">
                            <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-4">Select Test</label>
                            <select id="trend-test-select" class="w-full px-6 py-4 bg-slate-50 rounded-2xl border-0 focus:ring-2 focus:ring-indigo-500 font-bold">
                                <option value="">Select Numeric Test</option>
                                <!-- Loaded dynamically -->
                            </select>
                        </div>
                        <div class="flex items-end">
                            <button onclick="loadTrendChart()" class="w-full py-4 bg-indigo-600 text-white rounded-2xl font-black uppercase text-[10px] tracking-widest shadow-xl shadow-indigo-100">Generate Visualization</button>
                        </div>
                    </div>
                    <div class="h-96 w-full bg-slate-50 rounded-[32px] p-8 border border-slate-100">
                        <canvas id="trendChart"></canvas>
                    </div>
                </div>
            </section>

        </div>
    </main>

    <!-- MODAL: SAMPLE COLLECTION -->
    <div id="sampleModal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-50 hidden flex items-center justify-center p-6">
        <div class="bg-white rounded-[40px] w-full max-w-md p-10 space-y-8 shadow-2xl animate-fade-in">
            <h3 class="text-2xl font-black text-slate-900">Sample Collection</h3>
            <form id="sample-form" class="space-y-6">
                <input type="hidden" name="request_id" id="sample-request-id">
                <div class="grid grid-cols-2 gap-4">
                    <div class="space-y-2">
                        <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-4">Sample Type</label>
                        <select name="sample_type" class="w-full px-6 py-4 bg-slate-50 rounded-2xl border-0 font-bold">
                            <option value="Venous Blood">Venous Blood</option>
                            <option value="Capillary Blood">Capillary Blood</option>
                            <option value="Urine (Mid-stream)">Urine (Mid-stream)</option>
                            <option value="Stool">Stool</option>
                            <option value="Swab">Swab</option>
                        </select>
                    </div>
                    <div class="space-y-2">
                        <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-4">Volume (mL/Qty)</label>
                        <input type="text" name="sample_volume" placeholder="e.g. 5mL" class="w-full px-6 py-4 bg-slate-50 rounded-2xl border-0 font-bold">
                    </div>
                </div>
                <div class="space-y-2">
                    <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-4">Notes</label>
                    <textarea name="notes" rows="2" class="w-full px-6 py-4 bg-slate-50 rounded-2xl border-0 font-medium text-slate-600 resize-none"></textarea>
                </div>
                <div class="flex gap-4">
                    <button type="button" onclick="closeModal('sampleModal')" class="flex-1 py-4 bg-slate-100 text-slate-500 rounded-2xl font-bold">Cancel</button>
                    <button type="submit" class="flex-1 py-4 bg-blue-600 text-white rounded-2xl font-bold shadow-xl shadow-blue-100">Confirm Collection</button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL: RESULT ENTRY -->
    <div id="resultModal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-50 hidden flex items-center justify-center p-6">
        <div class="bg-white rounded-[40px] w-full max-w-xl p-10 space-y-8 shadow-2xl animate-fade-in">
            <div class="flex justify-between items-start">
                <div>
                    <h3 class="text-2xl font-black text-slate-900">Result Entry</h3>
                    <p id="result-test-name" class="text-indigo-600 font-bold text-sm uppercase tracking-widest">---</p>
                </div>
                <div class="text-right">
                    <p id="result-patient-name" class="font-black text-slate-900">---</p>
                    <p id="result-ref-range" class="text-[10px] text-slate-400 font-bold uppercase">Range: ---</p>
                </div>
            </div>
            <form id="result-form" class="space-y-6">
                <input type="hidden" name="request_id" id="result-request-id">
                <input type="hidden" name="patient_id" id="result-patient-id">
                
                <div id="numeric-input-group" class="hidden space-y-2">
                    <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-4">Numeric Value (<span id="result-unit"></span>)</label>
                    <input type="number" step="0.0001" name="numeric_value" class="w-full px-6 py-5 bg-slate-50 rounded-[24px] border-0 focus:ring-2 focus:ring-indigo-500 font-black text-2xl text-indigo-600">
                </div>

                <div class="space-y-2">
                    <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-4">Findings / Clinical Observations</label>
                    <textarea name="findings" rows="4" required class="w-full px-6 py-4 bg-slate-50 rounded-[24px] border-0 font-medium text-slate-600 resize-none" placeholder="Enter test observations..."></textarea>
                </div>

                <div class="space-y-2">
                    <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-4">Internal Technician Notes (Optional)</label>
                    <textarea name="lab_notes" rows="2" class="w-full px-6 py-4 bg-slate-50 rounded-[24px] border-0 font-medium text-slate-500 resize-none" placeholder="Enter notes for doctor..."></textarea>
                </div>

                <div class="flex gap-4">
                    <button type="button" onclick="closeModal('resultModal')" class="flex-1 py-4 bg-slate-100 text-slate-500 rounded-2xl font-bold">Close</button>
                    <button type="submit" class="flex-1 py-4 bg-indigo-600 text-white rounded-2xl font-black uppercase text-xs tracking-[0.2em] shadow-xl shadow-indigo-100">Release Results</button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL: MANAGE TEST -->
    <div id="testModal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-50 hidden flex items-center justify-center p-6">
        <div class="bg-white rounded-[40px] w-full max-w-2xl p-10 space-y-8 shadow-2xl overflow-y-auto max-h-[90vh]">
            <h3 class="text-2xl font-black text-slate-900" id="test-modal-title">Add Lab Test</h3>
            <form id="test-form" class="grid grid-cols-2 gap-6">
                <input type="hidden" name="id">
                <div class="space-y-2">
                    <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-4">Category</label>
                    <select name="category" required class="w-full px-6 py-4 bg-slate-50 rounded-2xl border-0 font-bold">
                        <option value="HEMATOLOGY">HEMATOLOGY</option>
                        <option value="CHEMISTRY">CHEMISTRY</option>
                        <option value="LIPID PROFILE">LIPID PROFILE</option>
                        <option value="LIVER FUNCTION">LIVER FUNCTION</option>
                        <option value="HORMONES">HORMONES</option>
                        <option value="SEROLOGY">SEROLOGY</option>
                        <option value="MICROBIOLOGY">MICROBIOLOGY</option>
                    </select>
                </div>
                <div class="space-y-2">
                    <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-4">Test Name</label>
                    <input type="text" name="test_name" required class="w-full px-6 py-4 bg-slate-50 rounded-2xl border-0 font-bold">
                </div>
                <div class="space-y-2">
                    <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-4">Unit (e.g. mg/dL)</label>
                    <input type="text" name="unit" class="w-full px-6 py-4 bg-slate-50 rounded-2xl border-0 font-bold">
                </div>
                <div class="space-y-2">
                    <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-4">Price (₦)</label>
                    <input type="number" name="price" required class="w-full px-6 py-4 bg-slate-50 rounded-2xl border-0 font-bold">
                </div>
                <div class="flex items-center gap-4 py-4">
                    <input type="checkbox" name="is_numeric" id="is_numeric_check" class="w-5 h-5 rounded-lg text-indigo-600 focus:ring-indigo-500 border-slate-300">
                    <label for="is_numeric_check" class="text-xs font-black text-slate-900 uppercase tracking-widest">Numeric Test (Enables Charting)</label>
                </div>
                <div class="col-span-2 grid grid-cols-2 gap-6 pt-4 border-t border-slate-100">
                    <div class="space-y-2">
                        <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-4">Ref. Min</label>
                        <input type="number" step="0.001" name="reference_min" class="w-full px-6 py-4 bg-slate-50 rounded-2xl border-0 font-bold">
                    </div>
                    <div class="space-y-2">
                        <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-4">Ref. Max</label>
                        <input type="number" step="0.001" name="reference_max" class="w-full px-6 py-4 bg-slate-50 rounded-2xl border-0 font-bold">
                    </div>
                </div>
                <div class="col-span-2 flex gap-4 pt-6">
                    <button type="button" onclick="closeModal('testModal')" class="flex-1 py-4 bg-slate-100 text-slate-500 rounded-2xl font-bold">Cancel</button>
                    <button type="submit" class="flex-1 py-4 bg-indigo-600 text-white rounded-2xl font-black uppercase text-xs tracking-widest shadow-xl shadow-indigo-100">Save Test Definition</button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL: NEW LAB ORDER -->
    <div id="orderModal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-50 hidden flex items-center justify-center p-6">
        <div class="bg-white rounded-[40px] w-full max-w-md p-10 space-y-8 shadow-2xl animate-fade-in">
            <h3 class="text-2xl font-black text-slate-900">Initiate Lab Order</h3>
            <p id="order-patient-name" class="text-indigo-600 font-bold text-sm uppercase tracking-widest">---</p>
            <form id="order-form" class="space-y-6">
                <input type="hidden" name="patient_id" id="order-patient-id">
                <div class="space-y-2">
                    <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-4">Select Lab Test</label>
                    <select name="test_id" id="order-test-select" required class="w-full px-6 py-4 bg-slate-50 rounded-2xl border-0 font-bold">
                        <!-- Loaded via JS -->
                    </select>
                </div>
                <div class="space-y-2">
                    <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-4">Priority</label>
                    <select name="priority" class="w-full px-6 py-4 bg-slate-50 rounded-2xl border-0 font-bold">
                        <option value="Normal">Normal</option>
                        <option value="Urgent">Urgent</option>
                    </select>
                </div>
                <div class="space-y-2">
                    <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-4">Clinical Notes</label>
                    <textarea name="notes" rows="2" class="w-full px-6 py-4 bg-slate-50 rounded-2xl border-0 font-medium text-slate-600 resize-none" placeholder="Reason for test..."></textarea>
                </div>
                <div class="flex gap-4">
                    <button type="button" onclick="closeModal('orderModal')" class="flex-1 py-4 bg-slate-100 text-slate-500 rounded-2xl font-bold">Cancel</button>
                    <button type="submit" class="flex-1 py-4 bg-indigo-600 text-white rounded-2xl font-bold shadow-xl shadow-indigo-100 uppercase text-[10px] tracking-widest">Create Order</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        let currentTab = 'worklist';
        let chartInstance = null;

        function showTab(tab) {
            document.querySelectorAll('.tab-view').forEach(v => v.classList.add('hidden'));
            document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
            document.getElementById('view-' + tab).classList.remove('hidden');
            document.getElementById('btn-' + tab).classList.add('active');
            
            const titles = {'worklist': 'Laboratory Worklist', 'patients': 'Patient Directory', 'tests': 'Test Catalog Management', 'history': 'Patient Longitudinal Trends'};
            document.getElementById('view-title').textContent = titles[tab];
            currentTab = tab;
            
            if (tab === 'worklist') loadRequests();
            if (tab === 'patients') loadPatients();
            if (tab === 'tests') loadTests();
            if (tab === 'history') loadNumericTestsForTrend();
            
            lucide.createIcons();
        }

        // --- Patient Directory ---
        function loadPatients(term = '') {
            const endpoint = term ? `../api/lab_v2.php?action=search_patients&term=${term}` : '../api/lab_v2.php?action=get_recent_patients';
            fetch(endpoint)
            .then(r => r.json()).then(data => {
                if (data.success) {
                    const html = data.patients.map(p => `
                        <div class="bg-white p-8 rounded-[32px] border border-slate-200 shadow-sm hover:border-indigo-300 hover:shadow-indigo-100/50 transition-all group">
                            <div class="flex justify-between items-start mb-6">
                                <div class="w-12 h-12 bg-slate-50 text-slate-400 group-hover:bg-indigo-50 group-hover:text-indigo-600 rounded-2xl flex items-center justify-center transition-colors">
                                    <i data-lucide="user" class="w-6 h-6"></i>
                                </div>
                                <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest">#${p.file_number}</span>
                            </div>
                            <h4 class="text-lg font-black text-slate-900 mb-1">${p.full_name}</h4>
                            <p class="text-xs font-bold text-slate-400 uppercase tracking-tight mb-6">${p.gender || 'N/A'}, ${p.age || 'N/A'} years</p>
                            <div class="flex flex-col gap-2">
                                <button onclick="openOrderModal(${p.id}, '${p.full_name.replace(/'/g, "\\'")}')" class="w-full py-3 bg-indigo-600 text-white rounded-xl font-black text-[10px] uppercase tracking-widest hover:bg-indigo-700 transition-all">New Lab Order</button>
                                <button onclick="selectPatientForTrend('${p.full_name.replace(/'/g, "\\'")}')" class="w-full py-3 bg-indigo-50 text-indigo-600 rounded-xl font-black text-[10px] uppercase tracking-widest hover:bg-indigo-600 hover:text-white transition-all">View Trends</button>
                            </div>
                        </div>
                    `).join('');
                    document.getElementById('directory-list').innerHTML = html || '<div class="col-span-full py-20 text-center text-slate-400 italic">No patients found.</div>';
                    lucide.createIcons();
                }
            });
        }

        function selectPatientForTrend(name) {
            showTab('history');
            document.getElementById('patient-search').value = name;
            document.getElementById('search-results-history').classList.add('hidden');
        }

        document.getElementById('directory-search').addEventListener('input', (e) => {
            loadPatients(e.target.value);
        });

        document.getElementById('patient-search').addEventListener('input', function(e) {
            const term = e.target.value;
            if (term.length < 2) {
                document.getElementById('search-results-history').classList.add('hidden');
                return;
            }

            fetch(`../api/lab_v2.php?action=search_patients&term=${term}`)
            .then(r => r.json()).then(data => {
                if (data.success && data.patients.length > 0) {
                    const html = data.patients.map(p => `
                        <div onclick="selectPatientForTrend('${p.full_name.replace(/'/g, "\\'")}')" class="p-4 hover:bg-slate-50 cursor-pointer flex justify-between items-center transition-all">
                            <div>
                                <p class="font-bold text-slate-900 text-sm">${p.full_name}</p>
                                <p class="text-[9px] font-bold text-slate-400 uppercase">#${p.file_number} • ${p.gender || 'N/A'}, ${p.age || 'N/A'}y</p>
                            </div>
                            <i data-lucide="chevron-right" class="w-3 h-3 text-slate-300"></i>
                        </div>
                    `).join('');
                    const resultsDiv = document.getElementById('search-results-history');
                    resultsDiv.innerHTML = html;
                    resultsDiv.classList.remove('hidden');
                    lucide.createIcons();
                } else {
                    document.getElementById('search-results-history').classList.add('hidden');
                }
            });
        });

        // --- Lab Orders ---
        function openOrderModal(patientId, patientName) {
            document.getElementById('order-patient-id').value = patientId;
            document.getElementById('order-patient-name').textContent = 'Order for: ' + patientName;
            
            // Load test catalog into dropdown
            fetch('../api/lab_v2.php?action=get_tests')
            .then(r => r.json()).then(data => {
                if (data.success) {
                    const html = '<option value="">-- Choose Test --</option>' + 
                                data.tests.map(t => `<option value="${t.id}">${t.category} - ${t.test_name}</option>`).join('');
                    document.getElementById('order-test-select').innerHTML = html;
                    document.getElementById('orderModal').classList.remove('hidden');
                }
            });
        }

        document.getElementById('order-form').onsubmit = (e) => {
            e.preventDefault();
            const fd = new FormData(e.target);
            fetch('../api/lab_v2.php?action=create_request', { method: 'POST', body: fd })
            .then(r => r.json()).then(data => {
                if (data.success) {
                    alert(data.message);
                    closeModal('orderModal');
                    showTab('worklist'); // Switch to worklist to see the new request
                } else alert(data.message);
            });
        };

        // --- Worklist ---
        function loadRequests(status = 'Pending') {
            document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('bg-indigo-600', 'text-white'));
            document.getElementById('filter-' + status.replace(' ', '')).classList.add('bg-indigo-600', 'text-white');

            fetch(`../api/lab_v2.php?action=get_requests&status=${status}`)
            .then(r => r.json()).then(data => {
                if (data.success) {
                    const html = data.requests.map(r => `
                        <tr class="hover:bg-slate-50 transition-colors">
                            <td class="px-8 py-6">
                                <p class="font-black text-slate-900">${r.patient_name} ${r.priority === 'Urgent' ? '<span class="ml-2 text-[8px] bg-rose-50 text-rose-600 px-2 py-0.5 rounded-full">URGENT</span>' : ''}</p>
                                <p class="text-[10px] text-slate-400 font-bold uppercase tracking-widest">#${r.file_number} • ${r.gender || 'N/A'}, ${r.age || 'N/A'}y</p>
                            </td>
                            <td class="px-8 py-6">
                                <p class="font-bold text-indigo-600">${r.test_name}</p>
                                <p class="text-[10px] text-slate-400 font-black uppercase">${r.category}</p>
                                ${r.payment_status && r.payment_status !== 'Paid' ? 
                                    `<p class="mt-2 flex items-center gap-1.5 text-[9px] font-black text-amber-600 bg-amber-50 px-2.5 py-1 rounded-lg w-fit uppercase tracking-wider border border-amber-100 shadow-sm shadow-amber-100/50 animate-pulse">
                                        <i data-lucide="alert-circle" class="w-3 h-3"></i> Payment Pending
                                    </p>` : ''
                                }
                            </td>
                            <td class="px-8 py-6">
                                <p class="text-sm font-bold text-slate-700">${r.requester_name || 'System'}</p>
                                <p class="text-[9px] text-slate-400 font-medium">${new Date(r.requested_at).toLocaleString()}</p>
                            </td>
                            <td class="px-8 py-6 text-right">
                                <?php /* Payment check removed to allow clinical flow */ ?>
                                ${r.status === 'Pending' ? 
                                    `<button onclick="openSampleModal(${r.id})" class="px-4 py-2 bg-blue-50 text-blue-600 rounded-xl font-black text-[10px] uppercase hover:bg-blue-600 hover:text-white transition-all shadow-lg shadow-blue-100/50">Collect Sample</button>` :
                                    `<button onclick="openResultModal(${JSON.stringify(r).replace(/"/g, '&quot;')})" class="px-4 py-2 bg-emerald-50 text-emerald-600 rounded-xl font-black text-[10px] uppercase hover:bg-emerald-600 hover:text-white transition-all shadow-lg shadow-emerald-100/50">Release Result</button>`
                                }
                            </td>
                        </tr>
                    `).join('');
                    document.getElementById('worklist-body').innerHTML = html || '<tr><td colspan="4" class="p-20 text-center text-slate-400 italic font-medium">No requests in this status.</td></tr>';
                    lucide.createIcons();
                }
                updateStats();
            });
        }

        function updateStats() {
            fetch('../api/lab_v2.php?action=get_stats')
            .then(r => r.json()).then(data => {
                if(data.success) {
                    document.getElementById('stat-pending').textContent = data.stats.pending;
                    document.getElementById('stat-collected').textContent = data.stats.collected;
                    document.getElementById('stat-done').textContent = data.stats.completed_today;
                    document.getElementById('stat-urgent').textContent = data.stats.urgent;
                }
            });
        }

        // --- Modals ---
        function openSampleModal(id) {
            document.getElementById('sample-form').reset();
            document.getElementById('sample-request-id').value = id;
            document.getElementById('sampleModal').classList.remove('hidden');
        }

        function openResultModal(req) {
            document.getElementById('result-form').reset();
            document.getElementById('result-request-id').value = req.id;
            document.getElementById('result-patient-id').value = req.patient_id;
            document.getElementById('result-test-name').textContent = req.test_name;
            document.getElementById('result-patient-name').textContent = req.patient_name;
            document.getElementById('result-ref-range').textContent = req.is_numeric ? `Range: ${req.reference_min} - ${req.reference_max} ${req.unit}` : 'Standard Assessment';
            
            if (req.is_numeric) {
                document.getElementById('numeric-input-group').classList.remove('hidden');
                document.getElementById('result-unit').textContent = req.unit;
            } else {
                document.getElementById('numeric-input-group').classList.add('hidden');
            }
            
            document.getElementById('resultModal').classList.remove('hidden');
        }

        function closeModal(id) {
            document.getElementById(id).classList.add('hidden');
        }

        // --- Form Submissions ---
        document.getElementById('sample-form').onsubmit = (e) => {
            e.preventDefault();
            const fd = new FormData(e.target);
            fetch('../api/lab_v2.php?action=collect_sample', { method: 'POST', body: fd })
            .then(r => r.json()).then(data => {
                if(data.success) { closeModal('sampleModal'); loadRequests('Pending'); }
                else alert(data.message);
            });
        };

        document.getElementById('result-form').onsubmit = (e) => {
            e.preventDefault();
            const fd = new FormData(e.target);
            fetch('../api/lab_v2.php?action=save_result', { method: 'POST', body: fd })
            .then(r => r.json()).then(data => {
                if(data.success) { closeModal('resultModal'); loadRequests('Sample Collected'); }
                else alert(data.message);
            });
        };

        // --- Test Catalog ---
        function loadTests() {
            fetch('../api/lab_v2.php?action=get_tests')
            .then(r => r.json()).then(data => {
                if (data.success) {
                    const html = data.tests.map(t => `
                        <tr>
                            <td class="px-8 py-6 text-[10px] font-black text-slate-400 uppercase tracking-widest">${t.category}</td>
                            <td class="px-8 py-6 font-bold text-slate-900">${t.test_name}</td>
                            <td class="px-8 py-6">
                                <p class="text-sm font-bold text-slate-700">${t.unit || '---'}</p>
                                <p class="text-[10px] text-slate-400 font-medium">${t.is_numeric ? `Range: ${t.reference_min} - ${t.reference_max}` : 'Non-numeric'}</p>
                            </td>
                            <td class="px-8 py-6 font-black text-slate-900 text-sm">₦${parseFloat(t.price).toLocaleString()}</td>
                            <td class="px-8 py-6 text-right">
                                <button onclick='editTest(${JSON.stringify(t)})' class="p-2 text-indigo-600 hover:bg-indigo-50 rounded-lg transition-all"><i data-lucide="edit-3" class="w-4 h-4"></i></button>
                            </td>
                        </tr>
                    `).join('');
                    document.getElementById('tests-body').innerHTML = html;
                    lucide.createIcons();
                }
            });
        }

        function editTest(test) {
            const form = document.getElementById('test-form');
            form.id.value = test.id;
            form.test_name.value = test.test_name;
            form.category.value = test.category;
            form.price.value = test.price;
            form.unit.value = test.unit;
            form.is_numeric.checked = test.is_numeric == 1;
            form.reference_min.value = test.reference_min;
            form.reference_max.value = test.reference_max;
            document.getElementById('test-modal-title').textContent = 'Edit Lab Test';
            document.getElementById('testModal').classList.remove('hidden');
        }

        function openTestModal() {
            document.getElementById('test-form').reset();
            document.getElementById('test-form').id.value = '';
            document.getElementById('test-modal-title').textContent = 'Add Lab Test';
            document.getElementById('testModal').classList.remove('hidden');
        }

        document.getElementById('test-form').onsubmit = (e) => {
            e.preventDefault();
            const fd = new FormData(e.target);
            fetch('../api/lab_v2.php?action=save_test', { method: 'POST', body: fd })
            .then(r => r.json()).then(data => {
                if(data.success) { closeModal('testModal'); loadTests(); }
                else alert(data.message);
            });
        };

        // --- Trends ---
        function loadNumericTestsForTrend() {
            fetch('../api/lab_v2.php?action=get_tests')
            .then(r => r.json()).then(data => {
                if(data.success) {
                    const numTests = data.tests.filter(t => t.is_numeric == 1);
                    const html = '<option value="">Select Numeric Test</option>' + numTests.map(t => `<option value="${t.id}">${t.test_name}</option>`).join('');
                    document.getElementById('trend-test-select').innerHTML = html;
                }
            });
        }

        function loadTrendChart() {
            const patientTerm = document.getElementById('patient-search').value;
            const testId = document.getElementById('trend-test-select').value;
            if(!patientTerm || !testId) return alert('Please select patient and test.');

            // First search patient ID - FIX: Use lab_v2.php instead of nursing_v2.php
            fetch(`../api/lab_v2.php?action=search_patients&term=${patientTerm}`)
            .then(r => r.json()).then(data => {
                if(data.success && data.patients.length > 0) {
                    const patientId = data.patients[0].id;
                    fetch(`../api/lab_v2.php?action=get_patient_trends&patient_id=${patientId}&test_id=${testId}`)
                    .then(r => r.json()).then(trends => {
                        if(trends.success) renderChart(trends.history);
                    });
                } else alert('Patient not found.');
            });
        }

        function renderChart(data) {
            const ctx = document.getElementById('trendChart').getContext('2d');
            if (chartInstance) chartInstance.destroy();

            const labels = data.map(d => new Date(d.date).toLocaleDateString());
            const values = data.map(d => d.numeric_value);

            chartInstance = new Chart(ctx, {
                type: 'line',
                data: {
                    labels,
                    datasets: [{
                        label: 'Clinical Value',
                        data: values,
                        borderColor: '#4f46e5',
                        backgroundColor: 'rgba(79, 70, 229, 0.1)',
                        fill: true,
                        tension: 0.4,
                        pointRadius: 6,
                        pointBackgroundColor: '#fff',
                        pointBorderWidth: 3
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { display: false } },
                    scales: {
                        y: { beginAtZero: false, grid: { color: '#f1f5f9' } },
                        x: { grid: { display: false } }
                    }
                }
            });
        }

        // Init
        loadRequests();
        lucide.createIcons();

        // Real-Time Sync Subscription
        if (window.HospitalSync) {
            window.HospitalSync.subscribe('lab_requests', (signal) => {
                console.log('📡 [Lab Dashboard] Lab Request Signal Received:', signal);
                loadRequests();
            });
        }
    </script>
    <?php include '../includes/portal_footer.php'; ?>
</body>
</html>