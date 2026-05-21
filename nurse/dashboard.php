<?php
session_start();
if (!isset($_SESSION['nurse_id'])) {
    header('Location: login.php');
    exit;
}
require_once '../includes/db_connect.php';

$nurse_name = $_SESSION['nurse_name'] ?? 'Nurse';
$portal_title = "Nursing Dashboard | Hope Haven Hospital";
include '../includes/portal_head.php';
?>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<style>
    .step-inactive { opacity: 0.5; pointer-events: none; }
    .tab-btn.active { background-color: #e11d48; color: white; }
    .toast-notification {
        font-family: 'Plus Jakarta Sans', sans-serif !important;
        border-radius: 20px !important;
        box-shadow: 0 20px 40px -10px rgba(0,0,0,0.1) !important;
    }
</style>
<body class="flex min-h-screen bg-slate-50">

    <!-- Sidebar -->
    <aside class="w-72 bg-white border-r border-slate-200 hidden lg:flex flex-col sticky top-0 h-screen">
        <div class="p-8">
            <div class="flex items-center gap-3 mb-10">
                <div class="w-10 h-10 bg-rose-600 rounded-xl flex items-center justify-center text-white font-black shadow-lg shadow-rose-200">N</div>
                <h1 class="text-xl font-black text-slate-900 tracking-tighter">NURSE<span class="text-rose-600">PRO</span></h1>
            </div>
            
            <nav class="space-y-1">
                <a href="dashboard.php" class="flex items-center gap-3 px-5 py-4 bg-rose-50 text-rose-600 rounded-2xl font-bold transition-all relative">
                    <i data-lucide="layout-dashboard" class="w-5 h-5"></i> Dashboard
                    <span id="notif-badge" class="hidden absolute top-4 right-5 w-4 h-4 bg-rose-500 text-white text-[8px] flex items-center justify-center rounded-full font-black">0</span>
                </a>
                <a href="#" onclick="resetWorkflow(); return false;" class="flex items-center gap-3 px-5 py-4 text-slate-500 hover:bg-slate-50 rounded-2xl font-bold transition-all">
                    <i data-lucide="plus-circle" class="w-5 h-5"></i> New Intake
                </a>
                <a href="#" onclick="viewAllVisits(); return false;" class="flex items-center gap-3 px-5 py-4 text-slate-500 hover:bg-slate-50 rounded-2xl font-bold transition-all">
                    <i data-lucide="history" class="w-5 h-5"></i> Visit History
                </a>
            </nav>
        </div>

        <div class="mt-auto p-8 border-t border-slate-100">
            <div class="flex items-center gap-3 mb-6">
                <div class="w-10 h-10 bg-rose-100 rounded-full flex items-center justify-center text-rose-600 font-bold"><?php echo substr($nurse_name, 0, 1); ?></div>
                <div>
                    <p class="text-xs font-black text-slate-900"><?php echo $nurse_name; ?></p>
                    <p class="text-[10px] font-bold text-rose-500 uppercase">On Duty</p>
                </div>
            </div>
            <a href="logout.php" class="flex items-center gap-3 text-red-500 font-bold text-sm hover:translate-x-1 transition-all">
                <i data-lucide="log-out" class="w-5 h-5"></i> Sign Out
            </a>
        </div>
    </aside>

    <!-- Main Content -->
    <main class="flex-1 flex flex-col min-w-0">
        <header class="h-20 bg-white border-b border-slate-200 flex items-center justify-between px-10 shrink-0">
            <h2 id="workflow-title" class="text-lg font-black text-slate-900 uppercase tracking-tight">Clinical Workflow: Start</h2>
            <div class="flex items-center gap-4">
                <div id="current-visit-badge" class="hidden px-4 py-2 bg-emerald-50 text-emerald-600 rounded-xl text-[10px] font-black uppercase tracking-widest border border-emerald-100">
                    Active Visit: #<span id="active-visit-id">0</span>
                </div>
            </div>
        </header>

        <div class="flex-1 overflow-y-auto p-10">
            
            <!-- Workflow Steps Indicator -->
            <div class="flex items-center justify-between mb-12 max-w-5xl mx-auto">
                <div class="step-node active" data-step="1">
                    <div class="w-10 h-10 rounded-full bg-rose-600 text-white flex items-center justify-center font-black mb-2 shadow-lg shadow-rose-100">1</div>
                    <p class="text-[10px] font-black text-slate-900 uppercase text-center">Intake</p>
                </div>
                <div class="flex-1 h-0.5 bg-slate-200 mx-2 -mt-6"></div>
                <div class="step-node" data-step="2">
                    <div class="w-10 h-10 rounded-full bg-slate-200 text-slate-400 flex items-center justify-center font-black mb-2">2</div>
                    <p class="text-[10px] font-black text-slate-400 uppercase text-center">Vitals</p>
                </div>
                <div class="flex-1 h-0.5 bg-slate-200 mx-2 -mt-6"></div>
                <div class="step-node" data-step="3">
                    <div class="w-10 h-10 rounded-full bg-slate-200 text-slate-400 flex items-center justify-center font-black mb-2">3</div>
                    <p class="text-[10px] font-black text-slate-400 uppercase text-center">Clerking</p>
                </div>
                <div class="flex-1 h-0.5 bg-slate-200 mx-2 -mt-6"></div>
                <div class="step-node" data-step="4">
                    <div class="w-10 h-10 rounded-full bg-slate-200 text-slate-400 flex items-center justify-center font-black mb-2">4</div>
                    <p class="text-[10px] font-black text-slate-400 uppercase text-center">Labs</p>
                </div>
                <div class="flex-1 h-0.5 bg-slate-200 mx-2 -mt-6"></div>
                <div class="step-node" data-step="5">
                    <div class="w-10 h-10 rounded-full bg-slate-200 text-slate-400 flex items-center justify-center font-black mb-2">5</div>
                    <p class="text-[10px] font-black text-slate-400 uppercase text-center">Referral</p>
                </div>
            </div>

            <div id="workflow-container" class="max-w-5xl mx-auto">
                
                <!-- STEP 1: PATIENT INTAKE -->
                <section id="step-1" class="workflow-step space-y-8">
                    <div class="bg-white rounded-[40px] border border-slate-200 shadow-sm p-10">
                        <div class="flex justify-between items-center mb-10">
                            <h3 class="text-2xl font-black text-slate-900">Patient Selection & Intake</h3>
                            <div class="flex gap-4">
                                <button onclick="viewVisitHistory()" id="history-btn" class="hidden px-6 py-3 bg-slate-100 text-slate-600 rounded-2xl font-bold text-sm hover:bg-slate-200 transition-all">Visit History</button>
                                <button onclick="toggleRegMode()" id="reg-toggle-btn" class="px-6 py-3 bg-blue-600 text-white rounded-2xl font-bold text-sm hover:bg-blue-700 transition-all">New Patient Registration</button>
                            </div>
                        </div>

                        <!-- Search/Selection -->
                        <div id="selection-mode" class="space-y-6">
                            <div class="relative">
                                <i data-lucide="search" class="absolute left-6 top-1/2 -translate-y-1/2 text-slate-400 w-5 h-5"></i>
                                <input type="text" id="patient-search" placeholder="Search by name or file number..." class="w-full pl-16 pr-8 py-5 bg-slate-50 border-0 rounded-[24px] outline-none focus:ring-2 focus:ring-rose-500 font-bold text-slate-900 shadow-inner">
                                <div id="search-results" class="hidden absolute left-0 right-0 top-full mt-2 bg-white rounded-3xl border border-slate-200 shadow-2xl z-10 max-h-60 overflow-y-auto divide-y divide-slate-50"></div>
                            </div>

                            <div id="triage-queue-container" class="space-y-4 mb-10 pb-10 border-b border-slate-50">
                                <div class="flex justify-between items-center ml-4">
                                    <h4 class="text-[10px] font-black text-rose-600 uppercase tracking-widest flex items-center gap-2">
                                        <span class="relative flex h-2 w-2">
                                          <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-rose-400 opacity-75"></span>
                                          <span class="relative inline-flex rounded-full h-2 w-2 bg-rose-500"></span>
                                        </span>
                                        Live Triage Queue (Incoming from Records)
                                    </h4>
                                    <span id="queue-count" class="px-3 py-1 bg-rose-50 text-rose-600 rounded-lg text-[10px] font-black uppercase tracking-tight border border-rose-100 shadow-sm">0 Waiting</span>
                                </div>
                                <div id="triage-queue-list" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div class="p-8 bg-slate-50 border border-dashed border-slate-200 rounded-[32px] col-span-2 flex flex-col items-center justify-center text-center">
                                        <div class="w-12 h-12 bg-white rounded-full flex items-center justify-center text-slate-200 mb-4">
                                            <i data-lucide="users-2" class="w-6 h-6"></i>
                                        </div>
                                        <p class="text-sm text-slate-400 font-bold italic">Checking for incoming patients...</p>
                                    </div>
                                </div>
                            </div>

                            <div id="recent-patients-container" class="space-y-4">
                                <h4 class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-4">Recent Patients</h4>
                                <div id="recent-patients-list" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <!-- Loaded via JS -->
                                    <p class="text-sm text-slate-400 italic col-span-2 ml-4">Loading patients...</p>
                                </div>
                            </div>
                            
                            <div id="selected-patient-card" class="hidden p-8 bg-emerald-50 rounded-[32px] border border-emerald-100 flex justify-between items-center">
                                <div class="flex items-center gap-6">
                                    <div class="w-16 h-16 bg-white rounded-2xl flex items-center justify-center text-emerald-600 font-black text-2xl shadow-sm">
                                        <span id="pat-initial">?</span>
                                    </div>
                                    <div>
                                        <h4 id="pat-name" class="text-xl font-black text-slate-900">---</h4>
                                        <p id="pat-file" class="text-xs font-bold text-emerald-600 uppercase tracking-widest mt-1">#---</p>
                                    </div>
                                </div>
                                <button onclick="resetSelection()" class="text-rose-600 font-black text-[10px] uppercase tracking-widest hover:underline">Change Patient</button>
                            </div>
                        </div>

                        <!-- Registration (Hidden by default) -->
                        <div id="registration-mode" class="hidden space-y-6">
                            <form id="patient-reg-form" class="grid grid-cols-2 gap-6">
                                <div class="space-y-2">
                                    <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-4">Full Name</label>
                                    <input type="text" name="name" required class="w-full px-6 py-4 bg-slate-50 rounded-2xl border-0 outline-none focus:ring-2 focus:ring-blue-500 font-bold">
                                </div>
                                <div class="space-y-2">
                                    <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-4">Email Address</label>
                                    <input type="email" name="email" required class="w-full px-6 py-4 bg-slate-50 rounded-2xl border-0 outline-none focus:ring-2 focus:ring-blue-500 font-bold">
                                </div>
                                <div class="space-y-2">
                                    <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-4">Phone Number</label>
                                    <input type="text" name="phone" required class="w-full px-6 py-4 bg-slate-50 rounded-2xl border-0 outline-none focus:ring-2 focus:ring-blue-500 font-bold">
                                </div>
                                <div class="space-y-2">
                                    <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-4">Gender</label>
                                    <select name="gender" required class="w-full px-6 py-4 bg-slate-50 rounded-2xl border-0 outline-none focus:ring-2 focus:ring-blue-500 font-bold">
                                        <option value="Male">Male</option>
                                        <option value="Female">Female</option>
                                    </select>
                                </div>
                                <div class="space-y-2">
                                    <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-4">Age / DOB</label>
                                    <input type="number" name="age" required class="w-full px-6 py-4 bg-slate-50 rounded-2xl border-0 outline-none focus:ring-2 focus:ring-blue-500 font-bold">
                                </div>
                                <button type="submit" class="col-span-2 py-5 bg-blue-600 text-white rounded-3xl font-black uppercase text-xs tracking-widest shadow-xl shadow-blue-100 mt-4">Complete Registration</button>
                            </form>
                        </div>

                        <!-- Complaints Form (Visit Initialization) -->
                        <div id="complaints-section" class="hidden mt-12 pt-12 border-t border-slate-100 space-y-8 animate-in fade-in duration-500">
                            <h3 class="text-xl font-black text-slate-900">Visit Information</h3>
                            <form id="visit-init-form" class="space-y-6">
                                <div class="space-y-2">
                                    <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-4">Presenting Complaints</label>
                                    <textarea name="complaints" required rows="3" class="w-full px-6 py-4 bg-slate-50 rounded-[24px] border-0 outline-none focus:ring-2 focus:ring-rose-500 font-medium text-slate-600 resize-none" placeholder="e.g. Headache for 3 days, general body pain..."></textarea>
                                </div>
                                <div class="space-y-2">
                                    <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-4">Medical History (Brief)</label>
                                    <textarea name="history" rows="2" class="w-full px-6 py-4 bg-slate-50 rounded-[24px] border-0 outline-none focus:ring-2 focus:ring-rose-500 font-medium text-slate-600 resize-none" placeholder="Past conditions, allergies..."></textarea>
                                </div>
                                <button type="submit" class="w-full py-6 bg-slate-900 text-white rounded-[32px] font-black uppercase text-xs tracking-[0.2em] shadow-2xl shadow-slate-200 transition-all hover:bg-black">Initialize Clinical Visit</button>
                            </form>
                        </div>
                    </div>
                </section>

                <!-- STEP 2: VITALS & CHARTS -->
                <section id="step-2" class="workflow-step hidden space-y-8">
                    <div class="grid lg:grid-cols-2 gap-8">
                        <!-- Record Form -->
                        <div class="bg-white rounded-[40px] border border-slate-200 shadow-sm p-10">
                            <h3 class="text-xl font-black text-slate-900 mb-8">Record Vital Signs</h3>
                            <form id="vitals-form" class="space-y-6">
                                <div class="grid grid-cols-2 gap-6">
                                    <div class="space-y-1">
                                        <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-4">BP (Sys/Dia)</label>
                                        <div class="flex items-center gap-2">
                                            <input type="number" name="blood_pressure_sys" placeholder="120" required class="w-full px-5 py-4 bg-slate-50 rounded-2xl border-0 font-bold focus:ring-2 focus:ring-rose-500">
                                            <span class="text-slate-300">/</span>
                                            <input type="number" name="blood_pressure_dia" placeholder="80" required class="w-full px-5 py-4 bg-slate-50 rounded-2xl border-0 font-bold focus:ring-2 focus:ring-rose-500">
                                        </div>
                                    </div>
                                    <div class="space-y-1">
                                        <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-4">Heart Rate / Pulse</label>
                                        <div class="flex items-center gap-2">
                                            <input type="number" name="heart_rate" placeholder="HR" class="w-full px-5 py-4 bg-slate-50 rounded-2xl border-0 font-bold focus:ring-2 focus:ring-rose-500">
                                            <input type="number" name="pulse" placeholder="Pulse" class="w-full px-5 py-4 bg-slate-50 rounded-2xl border-0 font-bold focus:ring-2 focus:ring-rose-500">
                                        </div>
                                    </div>
                                </div>
                                <div class="grid grid-cols-2 gap-6">
                                    <div class="space-y-1">
                                        <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-4">Temperature (°C)</label>
                                        <input type="number" step="0.1" name="temperature" placeholder="36.5" required class="w-full px-5 py-4 bg-slate-50 rounded-2xl border-0 font-bold focus:ring-2 focus:ring-rose-500">
                                    </div>
                                    <div class="space-y-1">
                                        <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-4">FBS (mg/dL)</label>
                                        <input type="number" step="0.1" name="fasting_blood_sugar" placeholder="95" class="w-full px-5 py-4 bg-slate-50 rounded-2xl border-0 font-bold focus:ring-2 focus:ring-rose-500">
                                    </div>
                                </div>
                                <div class="grid grid-cols-3 gap-6">
                                    <div class="space-y-1">
                                        <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-4">Weight (kg)</label>
                                        <input type="number" step="0.1" name="weight" id="v-weight" required class="w-full px-5 py-4 bg-slate-50 rounded-2xl border-0 font-bold focus:ring-2 focus:ring-rose-500">
                                    </div>
                                    <div class="space-y-1">
                                        <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-4">Height (cm)</label>
                                        <input type="number" step="0.1" name="height" id="v-height" required class="w-full px-5 py-4 bg-slate-50 rounded-2xl border-0 font-bold focus:ring-2 focus:ring-rose-500">
                                    </div>
                                    <div class="space-y-1">
                                        <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-4">BMI (Auto)</label>
                                        <input type="text" name="bmi" id="v-bmi" readonly class="w-full px-5 py-4 bg-slate-100 rounded-2xl border-0 font-black text-slate-900 cursor-not-allowed">
                                    </div>
                                </div>
                                <button type="submit" class="w-full py-5 bg-emerald-600 text-white rounded-[32px] font-black uppercase text-xs tracking-widest shadow-xl shadow-emerald-100">Save Vitals & Continue</button>
                            </form>
                        </div>

                        <!-- Visual Charts -->
                        <div class="space-y-8">
                            <div class="bg-white rounded-[40px] border border-slate-200 shadow-sm p-10">
                                <h3 class="text-xl font-black text-slate-900 mb-6 flex items-center justify-between">
                                    Clinical Trends
                                    <span class="text-[10px] text-slate-400 font-black uppercase tracking-widest">History Visualization</span>
                                </h3>
                                <div class="h-64">
                                    <canvas id="vitalsChart"></canvas>
                                </div>
                                <div class="flex gap-4 mt-6">
                                    <button onclick="updateChart('bp')" class="px-4 py-2 bg-slate-100 rounded-xl text-[10px] font-black uppercase tracking-widest hover:bg-rose-50 hover:text-rose-600 transition-all">Blood Pressure</button>
                                    <button onclick="updateChart('glucose')" class="px-4 py-2 bg-slate-100 rounded-xl text-[10px] font-black uppercase tracking-widest hover:bg-amber-50 hover:text-amber-600 transition-all">Blood Sugar</button>
                                    <button onclick="updateChart('bmi')" class="px-4 py-2 bg-slate-100 rounded-xl text-[10px] font-black uppercase tracking-widest hover:bg-blue-50 hover:text-blue-600 transition-all">Weight/BMI</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- STEP 3: CLERKING SHEETS -->
                <section id="step-3" class="workflow-step hidden space-y-8">
                    <div class="bg-white rounded-[40px] border border-slate-200 shadow-sm p-10">
                        <div class="flex justify-between items-center mb-10">
                            <div>
                                <h3 class="text-2xl font-black text-slate-900">Clinical Clerking</h3>
                                <p class="text-slate-500 font-medium">Select a template to start assessment.</p>
                            </div>
                            <select id="template-selector" class="px-6 py-3 bg-slate-50 border-0 rounded-2xl font-bold text-sm outline-none ring-2 ring-rose-500/20 focus:ring-rose-500 transition-all">
                                <!-- Loaded via API -->
                            </select>
                        </div>

                        <form id="clerking-form" class="space-y-8 p-6 bg-slate-50 rounded-[32px] border border-slate-100">
                            <div id="dynamic-fields" class="grid grid-cols-1 gap-8">
                                <!-- Injected based on template -->
                                <div class="text-center py-20 text-slate-400 italic">Please select a clerking template to begin.</div>
                            </div>
                            <div id="form-actions" class="hidden pt-8 border-t border-slate-200">
                                <button type="submit" class="w-full py-6 bg-rose-600 text-white rounded-[32px] font-black uppercase text-xs tracking-widest shadow-xl shadow-rose-100">Finalize Assessment</button>
                            </div>
                        </form>
                    </div>
                </section>

                <!-- STEP 4: DECISION & LAB -->
                <section id="step-4" class="workflow-step hidden space-y-8">
                    <div class="bg-white rounded-[40px] border border-slate-200 shadow-sm p-10">
                        <div class="flex justify-between items-center mb-10">
                            <div>
                                <h3 class="text-2xl font-black text-slate-900">Lab & Triage Decision</h3>
                                <p class="text-[10px] font-black text-rose-600 uppercase tracking-widest mt-1">Smart Routing System</p>
                            </div>
                            <div class="flex gap-4">
                                <button onclick="openLabModal()" class="px-6 py-3 bg-blue-600 text-white rounded-2xl font-black uppercase text-[10px] tracking-widest shadow-xl shadow-blue-100 flex items-center gap-2">
                                    <i data-lucide="plus" class="w-4 h-4"></i> Request Lab Test
                                </button>
                                <button onclick="loadLabs()" class="p-3 bg-slate-50 text-slate-400 rounded-xl hover:text-rose-600 transition-all"><i data-lucide="refresh-cw" class="w-5 h-5"></i></button>
                            </div>
                        </div>

                        <div id="labs-container" class="space-y-4">
                            <!-- Injected via API -->
                            <div class="text-center py-20 text-slate-400 italic">Checking for recent lab activity...</div>
                        </div>

                        <div class="mt-12 pt-8 border-t border-slate-100 flex justify-between items-center">
                            <p class="text-xs font-bold text-slate-400 italic">If no tests are needed, you can skip directly to the doctor.</p>
                            <button onclick="showStep(5)" class="px-10 py-5 bg-slate-900 text-white rounded-[32px] font-black uppercase text-xs tracking-widest shadow-2xl shadow-slate-200">Skip to Referral</button>
                        </div>
                    </div>
                </section>

                <!-- STEP 5: REFERRAL -->
                <section id="step-5" class="workflow-step hidden space-y-8">
                    <div class="max-w-2xl mx-auto space-y-8">
                        <div class="bg-white rounded-[40px] border border-slate-200 shadow-sm p-10">
                            <h3 class="text-2xl font-black text-slate-900 mb-8">Referral to Doctor</h3>
                            <form id="referral-form" class="space-y-6">
                                <div class="space-y-2">
                                    <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-4">Assign to Doctor</label>
                                    <select name="doctor_id" id="doctor-list" required class="w-full px-6 py-4 bg-slate-50 rounded-2xl border-0 font-bold focus:ring-2 focus:ring-rose-500">
                                        <!-- Loaded via API -->
                                    </select>
                                </div>
                                <div class="space-y-2">
                                    <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-4">Priority Level</label>
                                    <div class="grid grid-cols-3 gap-4">
                                        <label class="cursor-pointer">
                                            <input type="radio" name="priority" value="Normal" checked class="hidden peer">
                                            <div class="p-4 rounded-2xl border-2 border-slate-100 text-center font-bold peer-checked:border-emerald-500 peer-checked:bg-emerald-50 peer-checked:text-emerald-600 transition-all">Normal</div>
                                        </label>
                                        <label class="cursor-pointer">
                                            <input type="radio" name="priority" value="Urgent" class="hidden peer">
                                            <div class="p-4 rounded-2xl border-2 border-slate-100 text-center font-bold peer-checked:border-amber-500 peer-checked:bg-amber-50 peer-checked:text-amber-600 transition-all">Urgent</div>
                                        </label>
                                        <label class="cursor-pointer">
                                            <input type="radio" name="priority" value="Emergency" class="hidden peer">
                                            <div class="p-4 rounded-2xl border-2 border-slate-100 text-center font-bold peer-checked:border-rose-500 peer-checked:bg-rose-50 peer-checked:text-rose-600 transition-all">Emergency</div>
                                        </label>
                                    </div>
                                </div>
                                <div class="space-y-2">
                                    <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-4">Handover Notes</label>
                                    <textarea name="notes" rows="4" class="w-full px-6 py-4 bg-slate-50 rounded-3xl border-0 font-medium text-slate-600 resize-none" placeholder="Summary for the doctor..."></textarea>
                                </div>
                                <button type="submit" class="w-full py-6 bg-slate-900 text-white rounded-[32px] font-black uppercase text-xs tracking-widest shadow-2xl shadow-slate-200">Submit Referral</button>
                            </form>
                        </div>
                    </div>
                </section>

            </div>
        </div>
    </main>

    <!-- Patient History Modal -->
    <div id="history-modal" class="hidden fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-50 flex items-center justify-center p-6">
        <div class="bg-white rounded-[40px] w-full max-w-4xl max-h-[90vh] overflow-hidden flex flex-col">
            <div class="p-8 border-b border-slate-100 flex justify-between items-center">
                <h3 class="text-2xl font-black text-slate-900 uppercase tracking-tighter">Clinical History: <span id="history-patient-name" class="text-rose-600">---</span></h3>
                <button onclick="closeHistory()" class="w-12 h-12 rounded-2xl bg-slate-50 text-slate-400 flex items-center justify-center hover:bg-rose-50 hover:text-rose-600 transition-all">
                    <i data-lucide="x" class="w-6 h-6"></i>
                </button>
            </div>
            <div id="history-content" class="flex-1 overflow-y-auto p-8 space-y-6">
                <!-- Injected via API -->
            </div>
        </div>
    </div>

    <!-- Lab Test Selection Modal -->
    <div id="lab-test-modal" class="hidden fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-[60] flex items-center justify-center p-6">
        <div class="bg-white rounded-[40px] w-full max-w-2xl shadow-2xl overflow-hidden flex flex-col max-h-[90vh]">
            <div class="p-8 border-b border-slate-100 flex justify-between items-center bg-slate-50/50">
                <div>
                    <h3 class="text-2xl font-black text-slate-900 uppercase tracking-tighter">Request Lab Tests</h3>
                    <p class="text-[10px] font-black text-blue-600 uppercase tracking-widest mt-1">Select multiple tests for this patient</p>
                </div>
                <button onclick="closeLabModal()" class="w-12 h-12 rounded-2xl bg-white text-slate-400 flex items-center justify-center hover:text-rose-600 transition-all shadow-sm">
                    <i data-lucide="x" class="w-6 h-6"></i>
                </button>
            </div>
            
            <form id="lab-request-form" class="flex-1 overflow-y-auto p-8 space-y-6">
                <div id="available-tests-list" class="grid grid-cols-2 gap-4">
                    <!-- Loaded via API -->
                    <div class="col-span-2 text-center py-10 text-slate-400">Loading available tests...</div>
                </div>
                
                <div class="pt-6 border-t border-slate-100">
                    <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-4">Clinical Indication / Notes</label>
                    <textarea name="notes" rows="3" class="w-full px-6 py-4 bg-slate-50 rounded-3xl border-0 outline-none focus:ring-2 focus:ring-blue-600 font-medium text-slate-600 resize-none mt-2" placeholder="Why are these tests being requested?"></textarea>
                </div>
            </form>

            <div class="p-8 bg-slate-50 border-t border-slate-100 flex gap-4">
                <button onclick="closeLabModal()" class="flex-1 py-4 bg-white text-slate-500 rounded-2xl font-bold border border-slate-200">Cancel</button>
                <button onclick="submitLabRequest(event)" class="flex-1 py-4 bg-blue-600 text-white rounded-2xl font-black uppercase text-xs tracking-widest shadow-xl shadow-blue-100">Send to Lab Queue</button>
            </div>
        </div>
    </div>

    <!-- Visit Detail Modal -->
    <div id="visit-detail-modal" class="hidden fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-[70] flex items-center justify-center p-6">
        <div class="bg-white rounded-[40px] w-full max-w-3xl max-h-[90vh] overflow-hidden flex flex-col shadow-2xl">
            <div class="p-8 border-b border-slate-100 flex justify-between items-center bg-slate-50/50">
                <div>
                    <h3 class="text-2xl font-black text-slate-900 uppercase tracking-tighter">Full Visit Record</h3>
                    <p class="text-[10px] font-black text-rose-600 uppercase tracking-widest mt-1">Visit ID: #<span id="detail-visit-id">0</span></p>
                </div>
                <div class="flex gap-2">
                    <button id="edit-record-btn" class="px-6 py-3 bg-emerald-600 text-white rounded-2xl font-black uppercase text-[10px] tracking-widest hover:bg-emerald-700 transition-all shadow-lg shadow-emerald-100 flex items-center gap-2">
                        <i data-lucide="edit-3" class="w-4 h-4"></i> Edit Record
                    </button>
                    <button onclick="closeVisitDetail()" class="w-12 h-12 rounded-2xl bg-white text-slate-400 flex items-center justify-center hover:text-rose-600 transition-all shadow-sm">
                        <i data-lucide="x" class="w-6 h-6"></i>
                    </button>
                </div>
            </div>
            
            <div class="flex-1 overflow-y-auto p-8 space-y-8" id="visit-detail-body">
                <!-- Data Injected Here -->
            </div>
        </div>
    </div>

    <script>
        // Global workflow state
        let currentStep = 1;
        let activePatientId = null;
        let activeVisitId = null;
        let chartInstance = null;
        let vitalsHistory = [];
        let lastNurseNotifCount = 0;

        function saveWorkflowState() {
            const state = {
                currentStep,
                activePatientId,
                activeVisitId,
                patientName: document.getElementById('pat-name').textContent,
                patientFile: document.getElementById('pat-file').textContent,
                timestamp: new Date().getTime()
            };
            localStorage.setItem('nurse_workflow_state', JSON.stringify(state));
        }

        function restoreWorkflowState() {
            const saved = localStorage.getItem('nurse_workflow_state');
            if (!saved) return;

            const state = JSON.parse(saved);
            // Expire state after 2 hours
            if (new Date().getTime() - state.timestamp > 7200000) {
                localStorage.removeItem('nurse_workflow_state');
                return;
            }

            if (state.activePatientId) {
                activePatientId = state.activePatientId;
                activeVisitId = state.activeVisitId;
                
                document.getElementById('pat-name').textContent = state.patientName;
                document.getElementById('pat-file').textContent = state.patientFile;
                document.getElementById('pat-initial').textContent = state.patientName.charAt(0);
                
                document.getElementById('selection-mode').classList.add('hidden');
                document.getElementById('selected-patient-card').classList.remove('hidden');
                document.getElementById('complaints-section').classList.remove('hidden');
                document.getElementById('history-btn').classList.remove('hidden');

                if (activeVisitId) {
                    document.getElementById('active-visit-id').textContent = activeVisitId;
                    document.getElementById('current-visit-badge').classList.remove('hidden');
                }

                showStep(state.currentStep);
                
                if (state.currentStep >= 2) loadVitalsHistory();
                if (state.currentStep >= 3) loadTemplates();
                if (state.currentStep >= 4) loadLabs();
            }
        }

        function fetchNurseNotifications() {
            fetch('../api/notifications.php?action=get&role=nurse')
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        if (data.unread_count > lastNurseNotifCount) {
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
                                    customClass: { popup: 'toast-notification' }
                                });
                                new Audio('https://www.soundjay.com/buttons/beep-07a.mp3').play().catch(e => {});
                            }
                        }
                        lastNurseNotifCount = data.unread_count;
                        const badge = document.getElementById('notif-badge');
                        if(data.unread_count > 0) {
                            badge.textContent = data.unread_count;
                            badge.classList.remove('hidden');
                        } else {
                            badge.classList.add('hidden');
                        }
                    }
                });
        }

        setInterval(fetchNurseNotifications, 30000);
        fetchNurseNotifications();

        // UI Helpers
        function showStep(step) {
            document.querySelectorAll('.workflow-step').forEach(s => s.classList.add('hidden'));
            const target = document.getElementById('step-' + step);
            if (target) target.classList.remove('hidden');
            
            document.querySelectorAll('.step-node').forEach(n => {
                const s = n.dataset.step;
                const circle = n.querySelector('div');
                const text = n.querySelector('p');
                if (s < step) {
                    circle.className = 'w-10 h-10 rounded-full bg-emerald-500 text-white flex items-center justify-center font-black mb-2';
                    circle.innerHTML = '<i data-lucide="check" class="w-5 h-5"></i>';
                } else if (s == step) {
                    circle.className = 'w-10 h-10 rounded-full bg-rose-600 text-white flex items-center justify-center font-black mb-2 shadow-lg shadow-rose-100';
                    circle.innerHTML = s;
                } else {
                    circle.className = 'w-10 h-10 rounded-full bg-slate-200 text-slate-400 flex items-center justify-center font-black mb-2';
                    circle.innerHTML = s;
                }
            });
            lucide.createIcons();
            
            const titles = ['Selection', 'Vital Signs', 'Clinical Clerking', 'Lab Review', 'Doctor Referral'];
            document.getElementById('workflow-title').textContent = 'Clinical Workflow: ' + (titles[step-1] || 'Unknown');
            currentStep = step;
            saveWorkflowState();
        }

        function resetWorkflow() {
            activePatientId = null;
            activeVisitId = null;
            localStorage.removeItem('nurse_workflow_state');
            document.getElementById('current-visit-badge').classList.add('hidden');
            document.getElementById('selected-patient-card').classList.add('hidden');
            document.getElementById('selection-mode').classList.remove('hidden');
            document.getElementById('complaints-section').classList.add('hidden');
            document.getElementById('history-btn').classList.add('hidden');
            document.getElementById('patient-search').value = '';
            showStep(1);
        }

        // Search Patients
        document.getElementById('patient-search').addEventListener('input', function(e) {
            const term = e.target.value;
            if (term.length < 2) {
                document.getElementById('search-results').classList.add('hidden');
                return;
            }

            fetch(`../api/nursing_v2.php?action=search_patients&term=${term}`)
            .then(r => r.json()).then(data => {
                if (data.success && data.patients.length > 0) {
                    const html = data.patients.map(p => `
                        <div onclick="selectPatient(${p.id}, '${p.full_name.replace(/'/g, "\\'")}', '${p.file_number}')" class="p-6 hover:bg-slate-50 cursor-pointer flex justify-between items-center transition-all">
                            <div>
                                <p class="font-black text-slate-900">${p.full_name}</p>
                                <p class="text-[10px] font-bold text-slate-400 uppercase">#${p.file_number} • ${p.gender || 'N/A'}, ${p.age || 'N/A'}y • ${p.phone}</p>
                            </div>
                            <i data-lucide="chevron-right" class="w-4 h-4 text-slate-300"></i>
                        </div>
                    `).join('');
                    document.getElementById('search-results').innerHTML = html;
                    document.getElementById('search-results').classList.remove('hidden');
                    lucide.createIcons();
                } else {
                    document.getElementById('search-results').classList.add('hidden');
                }
            });
        });

        function selectPatient(id, name, file) {
            activePatientId = id;
            document.getElementById('pat-name').textContent = name;
            document.getElementById('pat-file').textContent = '#' + file;
            document.getElementById('pat-initial').textContent = name.charAt(0);
            document.getElementById('search-results').classList.add('hidden');
            document.getElementById('selection-mode').classList.add('hidden');
            document.getElementById('selected-patient-card').classList.remove('hidden');
            document.getElementById('complaints-section').classList.remove('hidden');
            document.getElementById('history-btn').classList.remove('hidden');

            saveWorkflowState();

            // Fetch Onboarding Data to auto-fill
            fetch(`../api/nursing_v2.php?action=get_onboarding_data&patient_id=${id}`)
            .then(r => r.json()).then(data => {
                if (data.success) {
                    const form = document.getElementById('visit-init-form');
                    form.querySelector('textarea[name="complaints"]').value = data.complaints || '';
                    form.querySelector('textarea[name="history"]').value = data.history || '';
                    
                    // Show Badge
                    document.getElementById('pat-file').innerHTML = `#${file} <span class="ml-2 px-2 py-0.5 bg-blue-100 text-blue-600 rounded text-[8px] font-black uppercase tracking-tighter">Verified by Records</span>`;
                }
            });
        }

        function resetSelection() {
            activePatientId = null;
            document.getElementById('selected-patient-card').classList.add('hidden');
            document.getElementById('selection-mode').classList.remove('hidden');
            document.getElementById('complaints-section').classList.add('hidden');
            document.getElementById('history-btn').classList.add('hidden');
        }

        // Intake Form
        document.getElementById('visit-init-form').addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            formData.append('patient_id', activePatientId);

            fetch('../api/nursing_v2.php?action=start_visit', { method: 'POST', body: formData })
            .then(r => r.json()).then(data => {
                if (data.success) {
                    activeVisitId = data.visit_id;
                    document.getElementById('active-visit-id').textContent = activeVisitId;
                    document.getElementById('current-visit-badge').classList.remove('hidden');
                    loadVitalsHistory();
                    showStep(2);
                } else alert(data.message);
            });
        });

        // Vitals Logic
        document.getElementById('v-weight').addEventListener('input', calculateBMI);
        document.getElementById('v-height').addEventListener('input', calculateBMI);

        function calculateBMI() {
            const w = parseFloat(document.getElementById('v-weight').value);
            const h = parseFloat(document.getElementById('v-height').value) / 100;
            if (w && h) {
                const bmi = (w / (h * h)).toFixed(1);
                document.getElementById('v-bmi').value = bmi;
            }
        }

        document.getElementById('vitals-form').addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            formData.append('visit_id', activeVisitId);
            formData.append('patient_id', activePatientId);

            fetch('../api/nursing_v2.php?action=save_vitals', { method: 'POST', body: formData })
            .then(r => r.json()).then(data => {
                if (data.success) {
                    loadTemplates();
                    showStep(3);
                } else alert(data.message);
            });
        });

        function loadVitalsHistory() {
            fetch(`../api/nursing_v2.php?action=get_vitals_history&patient_id=${activePatientId}`)
            .then(r => r.json()).then(data => {
                if (data.success) {
                    vitalsHistory = data.history;
                    updateChart('bp');
                }
            });
        }

        function updateChart(type) {
            const ctx = document.getElementById('vitalsChart').getContext('2d');
            if (chartInstance) chartInstance.destroy();

            const labels = vitalsHistory.map(v => new Date(v.recorded_at).toLocaleDateString());
            let datasets = [];

            if (type === 'bp') {
                datasets = [
                    {
                        label: 'Systolic',
                        data: vitalsHistory.map(v => v.blood_pressure_sys),
                        borderColor: '#e11d48',
                        backgroundColor: 'rgba(225, 29, 72, 0.1)',
                        tension: 0.4,
                        fill: true
                    },
                    {
                        label: 'Diastolic',
                        data: vitalsHistory.map(v => v.blood_pressure_dia),
                        borderColor: '#fb7185',
                        backgroundColor: 'rgba(251, 113, 133, 0.1)',
                        tension: 0.4,
                        fill: true
                    }
                ];
            } else if (type === 'glucose') {
                datasets = [{
                    label: 'Fasting Blood Sugar',
                    data: vitalsHistory.map(v => v.fasting_blood_sugar),
                    borderColor: '#f59e0b',
                    backgroundColor: 'rgba(245, 158, 11, 0.1)',
                    tension: 0.4,
                    fill: true
                }];
            } else if (type === 'bmi') {
                datasets = [{
                    label: 'BMI Trend',
                    data: vitalsHistory.map(v => v.bmi),
                    borderColor: '#3b82f6',
                    backgroundColor: 'rgba(59, 130, 246, 0.1)',
                    tension: 0.4,
                    fill: true
                }];
            }

            chartInstance = new Chart(ctx, {
                type: 'line',
                data: { labels, datasets },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { 
                        legend: { display: true, position: 'top', labels: { font: { weight: 'bold', family: 'Plus Jakarta Sans' } } } 
                    },
                    scales: { 
                        y: { beginAtZero: false, grid: { color: '#f1f5f9' } },
                        x: { grid: { display: false } }
                    }
                }
            });
        }

        // Clerking Logic
        let templates = [];
        function loadTemplates() {
            fetch('../api/nursing_v2.php?action=get_templates')
            .then(r => r.json()).then(data => {
                if (data.success) {
                    templates = data.templates;
                    const html = '<option value="">Select Template</option>' + templates.map(t => `<option value="${t.id}">${t.name}</option>`).join('');
                    document.getElementById('template-selector').innerHTML = html;
                }
            });
        }

        document.getElementById('template-selector').addEventListener('change', function(e) {
            const tid = e.target.value;
            if (!tid) {
                document.getElementById('dynamic-fields').innerHTML = '<div class="text-center py-20 text-slate-400 italic">Please select a clerking template to begin.</div>';
                document.getElementById('form-actions').classList.add('hidden');
                return;
            }

            const template = templates.find(t => t.id == tid);
            const fields = JSON.parse(template.fields_json);
            
            const html = fields.map(f => `
                <div class="space-y-2">
                    <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-4">${f.label}</label>
                    ${f.type === 'textarea' 
                        ? `<textarea name="${f.name}" required rows="4" class="w-full px-6 py-4 bg-white rounded-3xl border-0 outline-none focus:ring-2 focus:ring-rose-500 font-medium text-slate-600 resize-none"></textarea>` 
                        : `<input type="${f.type}" name="${f.name}" required class="w-full px-6 py-4 bg-white rounded-2xl border-0 outline-none focus:ring-2 focus:ring-rose-500 font-bold">`
                    }
                </div>
            `).join('');

            document.getElementById('dynamic-fields').innerHTML = html;
            document.getElementById('form-actions').classList.remove('hidden');
        });

        document.getElementById('clerking-form').addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            const data = {};
            formData.forEach((v, k) => data[k] = v);

            const payload = new FormData();
            payload.append('visit_id', activeVisitId);
            payload.append('template_id', document.getElementById('template-selector').value);
            payload.append('data_json', JSON.stringify(data));

            fetch('../api/nursing_v2.php?action=save_clerking', { method: 'POST', body: payload })
            .then(r => r.json()).then(data => {
                if (data.success) {
                    loadLabs();
                    showStep(4);
                } else alert(data.message);
            });
        });

        // Labs Logic
        function loadLabs() {
            fetch(`../api/nursing_v2.php?action=get_lab_results&patient_id=${activePatientId}`)
            .then(r => r.json()).then(data => {
                if (data.success) {
                    if (data.results.length === 0) {
                        document.getElementById('labs-container').innerHTML = `
                            <div class="p-10 bg-slate-50 rounded-[32px] text-center">
                                <p class="text-slate-400 font-bold">No lab records found for this patient.</p>
                            </div>
                        `;
                        return;
                    }

                    const html = data.results.map(l => {
                        let findingsHtml = '';
                        if (l.findings) {
                            findingsHtml = `
                                <div class="bg-emerald-50 p-3 rounded-2xl border border-emerald-100 mb-2">
                                    <p class="text-xs font-bold text-emerald-700 leading-tight">${l.findings}</p>
                                </div>
                            `;
                        } else if (l.status === 'Sample Collected') {
                            findingsHtml = `
                                <div class="bg-blue-50 p-3 rounded-2xl border border-blue-100 mb-2 flex items-center gap-3">
                                    <div class="w-8 h-8 bg-blue-100 text-blue-600 rounded-full flex items-center justify-center shrink-0"><i data-lucide="droplet" class="w-4 h-4"></i></div>
                                    <div class="text-[10px] font-black text-blue-600 uppercase tracking-tighter">
                                        <p>Sample: ${l.sample_type || 'Collected'}</p>
                                        <p class="opacity-70">Taken: ${new Date(l.sample_time).toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'})}</p>
                                    </div>
                                </div>
                            `;
                        } else {
                            findingsHtml = `
                                <div class="bg-slate-50 p-3 rounded-2xl border border-slate-100 mb-2">
                                    <p class="text-[10px] font-bold text-slate-400 italic">Awaiting lab technician action...</p>
                                </div>
                            `;
                        }

                        return `
                            <div class="p-6 bg-white rounded-3xl border border-slate-100 hover:border-blue-200 transition-all shadow-sm">
                                <div class="flex justify-between items-start mb-4">
                                    <div>
                                        <h4 class="font-black text-slate-900">${l.test_name}</h4>
                                        <p class="text-[9px] font-black uppercase ${l.status === 'Completed' ? 'text-emerald-500' : 'text-amber-500'} tracking-widest mt-1">${l.status}</p>
                                    </div>
                                    <span class="text-[9px] font-bold text-slate-400 uppercase bg-slate-50 px-2 py-1 rounded-lg">${new Date(l.requested_at).toLocaleDateString()}</span>
                                </div>
                                ${findingsHtml}
                                ${l.notes ? `<p class="text-[10px] text-slate-400 italic mt-2 pl-2 border-l-2 border-slate-100">Nurse Note: ${l.notes}</p>` : ''}
                            </div>
                        `;
                    }).join('');
                    document.getElementById('labs-container').innerHTML = html;
                    lucide.createIcons();
                }
            });
        }

        // Lab Modal Logic
        function openLabModal() {
            document.getElementById('lab-test-modal').classList.remove('hidden');
            loadAvailableTests();
        }

        function closeLabModal() {
            document.getElementById('lab-test-modal').classList.add('hidden');
        }

        function loadAvailableTests() {
            fetch('../api/nursing_v2.php?action=get_available_tests')
            .then(r => r.json()).then(data => {
                if (data.success) {
                    const html = data.tests.map(t => `
                        <label class="cursor-pointer group">
                            <input type="checkbox" name="test_ids[]" value="${t.id}" class="hidden peer">
                            <div class="p-6 rounded-[24px] border-2 border-slate-100 peer-checked:border-blue-600 peer-checked:bg-blue-50 transition-all">
                                <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1 group-hover:text-blue-400">${t.category}</p>
                                <p class="font-bold text-slate-900">${t.test_name}</p>
                                <p class="text-xs font-black text-blue-600 mt-2">₦${parseFloat(t.price).toLocaleString()}</p>
                            </div>
                        </label>
                    `).join('');
                    document.getElementById('available-tests-list').innerHTML = html;
                }
            });
        }

        function submitLabRequest(event) {
            if(!activePatientId || !activeVisitId) {
                Swal.fire('Error', 'No active patient or visit found. Please complete intake first.', 'error');
                return;
            }

            const form = document.getElementById('lab-request-form');
            const formData = new FormData(form);
            
            // Validate that at least one test is selected
            const selectedTests = formData.getAll('test_ids[]');
            if (selectedTests.length === 0) {
                Swal.fire('Required', 'Please select at least one lab test.', 'warning');
                return;
            }

            formData.append('patient_id', activePatientId);
            formData.append('visit_id', activeVisitId);

            const btn = event.currentTarget || event.target;
            const originalText = btn.innerText;
            btn.disabled = true;
            btn.innerText = 'Sending...';

            fetch('../api/nursing_v2.php?action=request_lab_test', { method: 'POST', body: formData })
            .then(r => r.json()).then(data => {
                if (data.success) {
                    Swal.fire({
                        title: 'Success!',
                        text: 'Lab tests requested and invoice generated.',
                        icon: 'success',
                        timer: 2000
                    });
                    closeLabModal();
                    loadLabs();
                    // Optional: auto-proceed to referral or stay to add more
                } else {
                    Swal.fire('Error', data.message || 'Failed to send lab request', 'error');
                }
            }).finally(() => {
                btn.disabled = false;
                btn.innerText = originalText;
            });
        }

        // Referral Logic
        function loadDoctors() {
            fetch('../api/nursing_v2.php?action=get_doctors')
            .then(r => r.json()).then(data => {
                if (data.success) {
                    const html = '<option value="">Select Doctor</option>' + data.doctors.map(d => `<option value="${d.id}">${d.name}</option>`).join('');
                    document.getElementById('doctor-list').innerHTML = html;
                }
            });
        }

        document.getElementById('referral-form').addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            formData.append('visit_id', activeVisitId);
            formData.append('patient_id', activePatientId);

            fetch('../api/nursing_v2.php?action=refer_to_doctor', { method: 'POST', body: formData })
            .then(r => r.json()).then(data => {
                if (data.success) {
                    alert('Patient referred to doctor successfully!');
                    resetWorkflow();
                } else alert(data.message);
            });
        });

        // History Logic
        function viewVisitHistory() {
            document.getElementById('history-patient-name').textContent = document.getElementById('pat-name').textContent;
            document.getElementById('history-modal').classList.remove('hidden');
            
            fetch(`../api/nursing_v2.php?action=get_visit_history&patient_id=${activePatientId}`)
            .then(r => r.json()).then(data => {
                if (data.success) {
                    const html = data.history.map(v => `
                        <div class="p-8 bg-slate-50 rounded-[32px] border border-slate-100 space-y-4">
                            <div class="flex justify-between items-start">
                                <div>
                                    <h4 class="text-lg font-black text-slate-900">Visit: ${new Date(v.visit_date).toLocaleDateString()}</h4>
                                    <p class="text-xs font-bold text-rose-600 uppercase tracking-widest mt-1">Status: ${v.status}</p>
                                </div>
                                <div class="text-right text-[10px] font-black text-slate-400 uppercase tracking-widest">
                                    Nurse: ${v.nurse_name}
                                </div>
                            </div>
                            <div class="grid grid-cols-2 gap-6 pt-4 border-t border-slate-200/60">
                                <div>
                                    <p class="text-[10px] font-black text-slate-400 uppercase mb-2">Complaints</p>
                                    <p class="text-sm font-bold text-slate-700">${v.presenting_complaints}</p>
                                </div>
                                <div>
                                    <p class="text-[10px] font-black text-slate-400 uppercase mb-2">History</p>
                                    <p class="text-sm font-bold text-slate-700">${v.medical_history || 'N/A'}</p>
                                </div>
                            </div>
                        </div>
                    `).join('');
                    document.getElementById('history-content').innerHTML = html || '<p class="text-center py-20 text-slate-400 italic">No previous visits found.</p>';
                }
            });
        }

        function closeHistory() {
            document.getElementById('history-modal').classList.add('hidden');
        }

        function toggleRegMode() {
            const isReg = !document.getElementById('registration-mode').classList.contains('hidden');
            if (isReg) {
                document.getElementById('registration-mode').classList.add('hidden');
                document.getElementById('selection-mode').classList.remove('hidden');
                document.getElementById('reg-toggle-btn').textContent = 'New Patient Registration';
            } else {
                document.getElementById('registration-mode').classList.remove('hidden');
                document.getElementById('selection-mode').classList.add('hidden');
                document.getElementById('reg-toggle-btn').textContent = 'Back to Selection';
            }
        }

        document.getElementById('patient-reg-form').addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            fetch('../api/nurse_auth.php?action=register_patient', { method: 'POST', body: formData })
            .then(r => r.json()).then(data => {
                if (data.success) {
                    alert(data.message);
                    toggleRegMode();
                    loadRecentPatients();
                    // Extract ID from message if possible or just use the search
                    document.getElementById('patient-search').value = formData.get('name');
                    document.getElementById('patient-search').dispatchEvent(new Event('input'));
                } else alert(data.message);
            });
        });

        function loadRecentPatients() {
            fetch('../api/nursing_v2.php?action=get_recent_patients')
            .then(r => r.json()).then(data => {
                if (data.success && data.patients.length > 0) {
                    const html = data.patients.map(p => `
                        <div onclick="selectPatient(${p.id}, '${p.full_name.replace(/'/g, "\\'")}', '${p.file_number}')" class="p-4 bg-slate-50 border border-slate-100 rounded-2xl hover:border-rose-300 hover:bg-rose-50 cursor-pointer transition-all group">
                            <p class="font-black text-slate-900 group-hover:text-rose-600">${p.full_name}</p>
                            <p class="text-[9px] font-bold text-slate-400 uppercase">#${p.file_number} • ${p.gender || 'N/A'}, ${p.age || 'N/A'}y</p>
                        </div>
                    `).join('');
                    document.getElementById('recent-patients-list').innerHTML = html;
                } else {
                    document.getElementById('recent-patients-list').innerHTML = '<p class="text-sm text-slate-400 italic col-span-2 ml-4">No recent patients.</p>';
                }
            });
        }

        function viewAllVisits() {
            document.getElementById('history-patient-name').textContent = "All Recent Visits";
            document.getElementById('history-modal').classList.remove('hidden');
            document.getElementById('history-content').innerHTML = '<p class="text-center py-20 text-slate-400 italic">Loading visit history...</p>';

            fetch(`../api/nursing_v2.php?action=get_all_visits`)
            .then(r => r.json()).then(data => {
                if (data.success) {
                    const html = data.visits.map(v => `
                        <div class="p-8 bg-slate-50 rounded-[32px] border border-slate-100 space-y-4">
                            <div class="flex justify-between items-start">
                                <div>
                                    <h4 class="text-lg font-black text-slate-900">${v.full_name} <span class="text-slate-400 text-sm">#${v.file_number}</span></h4>
                                    <p class="text-xs font-bold text-rose-600 uppercase tracking-widest mt-1">Visit: ${new Date(v.visit_date).toLocaleDateString()} • ${v.status}</p>
                                </div>
                                <div class="text-right text-[10px] font-black text-slate-400 uppercase tracking-widest">
                                    Nurse: ${v.nurse_name}
                                </div>
                            </div>
                            <div class="grid grid-cols-2 gap-6 pt-4 border-t border-slate-200/60">
                                <div>
                                    <p class="text-[10px] font-black text-slate-400 uppercase mb-2">Complaints</p>
                                    <p class="text-sm font-bold text-slate-700">${v.presenting_complaints}</p>
                                </div>
                                <div>
                                    <p class="text-[10px] font-black text-slate-400 uppercase mb-2">Details</p>
                                    <button onclick="viewVisitDetails(${v.id})" class="text-rose-600 text-[10px] font-black uppercase tracking-widest hover:underline">View Full Record</button>
                                </div>
                            </div>
                        </div>
                    `).join('');
                    document.getElementById('history-content').innerHTML = html || '<p class="text-center py-20 text-slate-400 italic">No visits recorded in the system.</p>';
                }
            });
        }

        function viewVisitDetails(visitId) {
            Swal.fire({ title: 'Loading...', didOpen: () => { Swal.showLoading() } });
            
            fetch(`../api/nursing_v2.php?action=get_visit_details&visit_id=${visitId}`)
            .then(r => r.json()).then(data => {
                Swal.close();
                if (data.success) {
                    const v = data.visit;
                    const vit = data.vitals;
                    const clk = data.clerking;

                    document.getElementById('detail-visit-id').textContent = visitId;
                    
                    let html = `
                        <div class="grid grid-cols-2 gap-8">
                            <div class="space-y-4">
                                <h4 class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Patient Information</h4>
                                <p class="text-xl font-black text-slate-900">${v.full_name}</p>
                                <p class="text-sm font-bold text-slate-500">File: #${v.file_number} • ${v.phone}</p>
                            </div>
                            <div class="text-right space-y-2">
                                <h4 class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Date & Time</h4>
                                <p class="text-sm font-black text-slate-900">${new Date(v.visit_date).toLocaleString()}</p>
                                <span class="inline-block px-3 py-1 bg-rose-50 text-rose-600 rounded-full text-[9px] font-black uppercase">${v.status}</span>
                            </div>
                        </div>

                        <div class="p-8 bg-slate-50 rounded-[32px] border border-slate-100 space-y-6">
                            <h4 class="text-[10px] font-black text-slate-900 uppercase tracking-widest">Intake Summary</h4>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <p class="text-[9px] font-black text-slate-400 uppercase mb-1">Presenting Complaints</p>
                                    <p class="text-sm font-medium text-slate-700">${v.presenting_complaints || 'None'}</p>
                                </div>
                                <div>
                                    <p class="text-[9px] font-black text-slate-400 uppercase mb-1">Medical History</p>
                                    <p class="text-sm font-medium text-slate-700">${v.medical_history || 'None'}</p>
                                </div>
                            </div>
                        </div>
                    `;

                    if (vit) {
                        html += `
                            <div class="grid grid-cols-4 gap-4">
                                <div class="bg-white p-4 rounded-2xl border border-slate-100 text-center">
                                    <p class="text-[8px] font-black text-slate-400 uppercase mb-1">BP</p>
                                    <p class="text-base font-black text-slate-900">${vit.blood_pressure_sys}/${vit.blood_pressure_dia}</p>
                                </div>
                                <div class="bg-white p-4 rounded-2xl border border-slate-100 text-center">
                                    <p class="text-[8px] font-black text-slate-400 uppercase mb-1">HR/Pulse</p>
                                    <p class="text-base font-black text-slate-900">${vit.heart_rate}/${vit.pulse}</p>
                                </div>
                                <div class="bg-white p-4 rounded-2xl border border-slate-100 text-center">
                                    <p class="text-[8px] font-black text-slate-400 uppercase mb-1">Temp</p>
                                    <p class="text-base font-black text-slate-900">${vit.temperature}°C</p>
                                </div>
                                <div class="bg-white p-4 rounded-2xl border border-slate-100 text-center">
                                    <p class="text-[8px] font-black text-slate-400 uppercase mb-1">BMI</p>
                                    <p class="text-base font-black text-slate-900">${vit.bmi}</p>
                                </div>
                            </div>
                        `;
                    }

                    if (clk && clk.length > 0) {
                        html += `
                            <div class="space-y-4">
                                <h4 class="text-[10px] font-black text-slate-900 uppercase tracking-widest">Clerking Records</h4>
                                ${clk.map(c => {
                                    const data = JSON.parse(c.data_json);
                                    return `
                                        <div class="p-6 bg-slate-50 rounded-2xl border border-slate-100">
                                            <p class="text-[9px] font-black text-rose-600 uppercase mb-3">${c.template_name}</p>
                                            <div class="grid grid-cols-1 gap-4">
                                                ${Object.entries(data).map(([k, val]) => `
                                                    <div class="flex justify-between border-b border-slate-200/50 pb-2">
                                                        <span class="text-[10px] font-bold text-slate-400 uppercase">${k.replace(/_/g, ' ')}</span>
                                                        <span class="text-xs font-bold text-slate-700">${val}</span>
                                                    </div>
                                                `).join('')}
                                            </div>
                                        </div>
                                    `;
                                }).join('')}
                            </div>
                        `;
                    }

                    document.getElementById('visit-detail-body').innerHTML = html;
                    document.getElementById('edit-record-btn').onclick = () => {
                        closeVisitDetail();
                        closeHistory();
                        startEditMode(data);
                    };
                    document.getElementById('visit-detail-modal').classList.remove('hidden');
                    lucide.createIcons();
                } else {
                    Swal.fire('Error', data.message, 'error');
                }
            });
        }

        function startEditMode(data) {
            const v = data.visit;
            activePatientId = v.patient_id;
            activeVisitId = v.id;

            // Pre-fill Intake
            document.getElementById('pat-name').textContent = v.full_name;
            document.getElementById('pat-file').textContent = '#' + v.file_number;
            document.getElementById('pat-initial').textContent = v.full_name.charAt(0);
            
            document.getElementById('selection-mode').classList.add('hidden');
            document.getElementById('selected-patient-card').classList.remove('hidden');
            document.getElementById('complaints-section').classList.remove('hidden');
            document.getElementById('history-btn').classList.remove('hidden');

            const initForm = document.getElementById('visit-init-form');
            initForm.querySelector('textarea[name="complaints"]').value = v.presenting_complaints;
            initForm.querySelector('textarea[name="history"]').value = v.medical_history;

            document.getElementById('active-visit-id').textContent = activeVisitId;
            document.getElementById('current-visit-badge').classList.remove('hidden');

            // Pre-fill Vitals if exist
            if (data.vitals) {
                const f = document.getElementById('vitals-form');
                const vit = data.vitals;
                if(f.querySelector('[name="blood_pressure_sys"]')) f.querySelector('[name="blood_pressure_sys"]').value = vit.blood_pressure_sys;
                if(f.querySelector('[name="blood_pressure_dia"]')) f.querySelector('[name="blood_pressure_dia"]').value = vit.blood_pressure_dia;
                if(f.querySelector('[name="heart_rate"]')) f.querySelector('[name="heart_rate"]').value = vit.heart_rate;
                if(f.querySelector('[name="pulse"]')) f.querySelector('[name="pulse"]').value = vit.pulse;
                if(f.querySelector('[name="temperature"]')) f.querySelector('[name="temperature"]').value = vit.temperature;
                if(f.querySelector('[name="fasting_blood_sugar"]')) f.querySelector('[name="fasting_blood_sugar"]').value = vit.fasting_blood_sugar;
                if(f.querySelector('[name="weight"]')) f.querySelector('[name="weight"]').value = vit.weight;
                if(f.querySelector('[name="height"]')) f.querySelector('[name="height"]').value = vit.height;
                if(f.querySelector('[name="bmi"]')) f.querySelector('[name="bmi"]').value = vit.bmi;
            }

            saveWorkflowState();
            showStep(1);
            
            Swal.fire({
                title: 'Edit Mode Active',
                text: 'You can now update the records for this visit.',
                icon: 'info',
                toast: true,
                position: 'top-end',
                timer: 3000
            });
        }

        function closeVisitDetail() {
            document.getElementById('visit-detail-modal').classList.add('hidden');
        }

        function loadTriageQueue() {
            fetch('../api/nursing_v2.php?action=get_nursing_queue')
            .then(r => r.json()).then(data => {
                if (data.success) {
                    const count = data.queue.length;
                    document.getElementById('queue-count').textContent = `${count} Waiting`;
                    
                    if (count > 0) {
                        const html = data.queue.map(p => `
                            <div class="p-6 bg-white border border-rose-100 rounded-[32px] shadow-sm hover:shadow-md hover:border-rose-300 transition-all group flex justify-between items-center">
                                <div class="flex items-center gap-4">
                                    <div class="w-12 h-12 bg-rose-50 text-rose-600 rounded-2xl flex items-center justify-center font-black text-lg group-hover:bg-rose-600 group-hover:text-white transition-all">
                                        ${p.full_name.charAt(0)}
                                    </div>
                                    <div>
                                        <p class="font-black text-slate-900">${p.full_name}</p>
                                        <p class="text-[10px] font-bold text-slate-400 uppercase tracking-tight">#${p.file_number} • ${p.gender || 'N/A'}, ${p.age || 'N/A'}y</p>
                                        <div class="mt-2 flex items-center gap-2">
                                            <span class="w-1.5 h-1.5 bg-emerald-500 rounded-full animate-pulse"></span>
                                            <span class="text-[8px] font-black text-emerald-600 uppercase tracking-widest">Ready for Intake</span>
                                        </div>
                                    </div>
                                </div>
                                <button onclick="selectPatient(${p.id}, '${p.full_name.replace(/'/g, "\\'")}', '${p.file_number}')" class="px-5 py-3 bg-rose-600 text-white rounded-xl text-[10px] font-black uppercase tracking-widest shadow-lg shadow-rose-100 hover:scale-105 transition-all">Receive</button>
                            </div>
                        `).join('');
                        document.getElementById('triage-queue-list').innerHTML = html;
                    } else {
                        document.getElementById('triage-queue-list').innerHTML = `
                            <div class="p-8 bg-slate-50 border border-dashed border-slate-200 rounded-[32px] col-span-2 flex flex-col items-center justify-center text-center">
                                <div class="w-12 h-12 bg-white rounded-full flex items-center justify-center text-slate-200 mb-4">
                                    <i data-lucide="users-2" class="w-6 h-6"></i>
                                </div>
                                <p class="text-sm text-slate-400 font-bold italic">Queue is currently empty.</p>
                            </div>
                        `;
                    }
                    lucide.createIcons();
                }
            });
        }

        // Initialize Doctors on load
        loadDoctors();
        loadRecentPatients();
        loadTriageQueue();
        restoreWorkflowState();
        setInterval(loadTriageQueue, 30000); // Refresh queue every 30s
        lucide.createIcons();

        // Real-Time Sync Subscription
        if (window.HospitalSync) {
            window.HospitalSync.subscribe('patient_queue', (signal) => {
                console.log('📡 [Nurse Dashboard] Patient Queue Signal Received:', signal);
                loadTriageQueue();
            });
            window.HospitalSync.subscribe('lab_requests', (signal) => {
                console.log('📡 [Nurse Dashboard] Lab Request Signal Received:', signal);
                if (activePatientId) loadLabs();
            });
            window.HospitalSync.subscribe('notifications', (signal) => {
                console.log('📡 [Nurse Dashboard] Notification Signal Received:', signal);
                fetchNurseNotifications();
            });
        }
    </script>
    <?php include '../includes/portal_footer.php'; ?>
</body>
</html>
