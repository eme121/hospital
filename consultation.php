<?php
session_start();
require_once 'includes/db_connect.php';

if (!isset($_SESSION['doctor_id'])) {
    header('Location: telemedicine_login.php');
    exit;
}

$patient_id = intval($_GET['patient_id'] ?? 0);
if ($patient_id <= 0) {
    header('Location: telemedicine_dashboard.php');
    exit;
}

// Fetch Patient Info
$p_stmt = $conn->prepare("SELECT * FROM patients WHERE id = ?");
$p_stmt->bind_param("i", $patient_id);
$p_stmt->execute();
$patient = $p_stmt->get_result()->fetch_assoc();

// Update Queue Status to 'In Consultation' and assign to THIS doctor
$conn->query("UPDATE patient_queue_status 
              SET status = 'In Consultation', 
                  doctor_id = $doctor_id,
                  notes = CONCAT('Attending by Dr. ', '" . $conn->real_escape_string($_SESSION['doctor_name']) . "')
              WHERE patient_id = $patient_id AND current_stage = 'Doctor'");

// Fetch Latest Visit
$v_stmt = $conn->prepare("SELECT * FROM patient_visits WHERE patient_id = ? ORDER BY visit_date DESC LIMIT 1");
$v_stmt->bind_param("i", $patient_id);
$v_stmt->execute();
$visit = $v_stmt->get_result()->fetch_assoc();
$visit_id = $visit['id'] ?? 0;

// Fetch Latest Vitals
$vi_stmt = $conn->prepare("SELECT * FROM vital_signs WHERE patient_id = ? ORDER BY recorded_at DESC LIMIT 1");
$vi_stmt->bind_param("i", $patient_id);
$vi_stmt->execute();
$vitals = $vi_stmt->get_result()->fetch_assoc();

// Fetch Lab Results
$l_sql = "SELECT r.*, COALESCE(NULLIF(r.custom_test_name, ''), t.test_name) as test_name, t.category, res.findings, res.numeric_value, res.is_abnormal, res.status as res_status
          FROM lab_requests r 
          JOIN lab_tests t ON r.test_id = t.id 
          LEFT JOIN lab_results res ON r.id = res.request_id
          WHERE r.patient_id = ?
          ORDER BY r.requested_at DESC";
$l_stmt = $conn->prepare($l_sql);
$l_stmt->bind_param("i", $patient_id);
$l_stmt->execute();
$labs = $l_stmt->get_result();

// Fetch Historical Vitals for Trends
$vh_sql = "SELECT recorded_at, blood_pressure_sys, blood_pressure_dia FROM vital_signs WHERE patient_id = ? ORDER BY recorded_at ASC LIMIT 10";
$vh_stmt = $conn->prepare($vh_sql);
$vh_stmt->bind_param("i", $patient_id);
$vh_stmt->execute();
$v_history = $vh_stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Fetch Past Medical Records
$ph_sql = "SELECT id, visit_date, diagnosis, presenting_complaints, status 
           FROM patient_visits WHERE patient_id = ? AND id != ? ORDER BY visit_date DESC";
$ph_stmt = $conn->prepare($ph_sql);
$ph_stmt->bind_param("ii", $patient_id, $visit_id);
$ph_stmt->execute();
$past_visits = $ph_stmt->get_result();

$doctor_name = $_SESSION['doctor_name'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Clinical Console: <?php echo $patient['full_name']; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; background-color: #f8fafc; height: 100vh; overflow: hidden; }
        .pane-scroll::-webkit-scrollbar { width: 4px; }
        .pane-scroll::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }
        
        :root { --blue-glow: rgba(59, 130, 246, 0.4); }
        .step-link { position: relative; padding: 10px 16px; border-radius: 16px; transition: all 0.3s ease; }
        .step-link.active { background: #eff6ff; }
        .step-link.active .step-dot { background-color: #2563eb; transform: scale(1.3); box-shadow: 0 0 10px var(--blue-glow); }
        .step-link.active span:last-child { color: #1e3a8a; }
        
        .step-link.completed .step-dot { background-color: #10b981; }
        
        .nav-next-btn { 
            display: inline-flex; align-items: center; gap: 12px; padding: 14px 28px; 
            background: #2563eb; color: white; border-radius: 20px; font-weight: 800; 
            text-transform: uppercase; font-size: 10px; letter-spacing: 0.15em;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: 0 10px 15px -3px rgba(37, 99, 235, 0.2);
        }
        .nav-next-btn:hover { transform: translateX(6px); background: #1d4ed8; box-shadow: 0 20px 25px -5px rgba(37, 99, 235, 0.25); }

        section { scroll-margin-top: 100px; }
    </style>
</head>
<body class="flex">

    <!-- Sidebar -->
    <aside class="w-20 bg-white border-r border-slate-200 flex flex-col items-center py-8 shrink-0">
        <div class="w-10 h-10 bg-blue-600 rounded-xl flex items-center justify-center text-white font-black mb-10 shadow-lg shadow-blue-100">D</div>
        <nav class="flex-1 space-y-8">
            <a href="telemedicine_dashboard.php" class="p-3 text-slate-400 hover:text-blue-600 block"><i data-lucide="layout-dashboard"></i></a>
            <a href="#" class="p-3 text-blue-600 bg-blue-50 rounded-xl block"><i data-lucide="clipboard-list"></i></a>
        </nav>
        <a href="logout.php" class="p-3 text-rose-400 hover:text-rose-600 mt-auto"><i data-lucide="log-out"></i></a>
    </aside>

    <div class="flex-1 flex flex-col min-w-0">
        
        <!-- Header: Identity & Interactive Stepper -->
        <header class="h-20 bg-white border-b border-slate-200 flex items-center justify-between px-10 shrink-0 sticky top-0 z-50">
            <div class="flex items-center gap-6">
                <div class="w-10 h-10 bg-slate-900 rounded-xl flex items-center justify-center text-white font-black text-lg">
                    <?php echo substr($patient['full_name'], 0, 1); ?>
                </div>
                <div>
                    <h2 class="text-sm font-black text-slate-900 leading-tight"><?php echo htmlspecialchars($patient['full_name']); ?></h2>
                    <p class="text-[9px] font-black text-blue-600 uppercase tracking-widest mt-0.5">ID: #<?php echo $patient['file_number']; ?></p>
                </div>
            </div>

            <!-- The Clinical Stepper (Interactive) -->
            <div class="flex items-center gap-12">
                <button onclick="scrollToSection('subjective-section')" class="step-link active flex items-center gap-3 group transition-all">
                    <span class="step-dot w-2 h-2 rounded-full bg-slate-200 transition-all"></span>
                    <span class="text-[10px] font-black uppercase tracking-widest text-slate-400 group-hover:text-blue-600">1. Complaints</span>
                </button>
                <div class="w-8 h-[1px] bg-slate-100"></div>
                <button onclick="scrollToSection('objective-section')" class="step-link flex items-center gap-3 group transition-all">
                    <span class="step-dot w-2 h-2 rounded-full bg-slate-200 transition-all"></span>
                    <span class="text-[10px] font-black uppercase tracking-widest text-slate-400 group-hover:text-blue-600">2. Objective Data</span>
                </button>
                <div class="w-8 h-[1px] bg-slate-100"></div>
                <button onclick="scrollToSection('assessment-section')" class="step-link flex items-center gap-3 group transition-all">
                    <span class="step-dot w-2 h-2 rounded-full bg-slate-200 transition-all"></span>
                    <span class="text-[10px] font-black uppercase tracking-widest text-slate-400 group-hover:text-blue-600">3. Assessment & Plan</span>
                </button>
            </div>

            <div class="flex items-center gap-4">
                <?php 
                $abnormal_count = 0;
                $labs->data_seek(0);
                while($l = $labs->fetch_assoc()) if($l['is_abnormal']) $abnormal_count++;
                $labs->data_seek(0);
                if($abnormal_count > 0): ?>
                    <div class="px-4 py-2 bg-rose-50 text-rose-600 rounded-xl text-[9px] font-black uppercase tracking-widest border border-rose-100 flex items-center gap-2 animate-pulse">
                        <i data-lucide="alert-triangle" class="w-3 h-3"></i> <?php echo $abnormal_count; ?> Abnormal Results
                    </div>
                <?php endif; ?>
                <button onclick="finalizeConsultation(event)" class="px-8 py-2.5 bg-slate-900 text-white rounded-xl font-black uppercase text-[10px] tracking-widest shadow-xl shadow-slate-200">Discharge</button>
            </div>
        </header>

        <!-- Scrollable Main Content -->
        <main id="clinical-workspace" class="flex-1 overflow-y-auto pane-scroll p-10 space-y-20 pb-40">
            
            <!-- SECTION 1: SUBJECTIVE (Complaints & History) -->
            <section id="subjective-section" class="max-w-5xl mx-auto">
                <div class="flex items-center gap-4 mb-8">
                    <span class="w-8 h-8 bg-blue-600 text-white rounded-lg flex items-center justify-center text-xs font-black">S</span>
                    <h3 class="text-xs font-black text-slate-900 uppercase tracking-[0.2em]">Subjective: Patient Narrative</h3>
                </div>
                
                <div class="grid grid-cols-12 gap-8">
                    <div class="col-span-8">
                        <div class="p-10 bg-white rounded-[40px] border border-slate-200 shadow-sm relative overflow-hidden">
                            <i data-lucide="quote" class="absolute top-6 right-8 w-12 h-12 text-slate-50"></i>
                            <h4 class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-6">Presenting Complaints</h4>
                            <p class="text-xl font-bold text-slate-700 leading-relaxed italic relative z-10">
                                "<?php echo htmlspecialchars($visit['presenting_complaints'] ?? 'No patient notes captured.'); ?>"
                            </p>
                        </div>
                    </div>
                    <div class="col-span-4">
                        <div class="p-8 bg-slate-900 rounded-[40px] text-white h-full">
                            <h4 class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-6">Past History</h4>
                            <div class="space-y-4">
                                <?php if ($past_visits->num_rows > 0): while($pv = $past_visits->fetch_assoc()): ?>
                                    <div class="pb-4 border-b border-white/10 last:border-0">
                                        <p class="text-[9px] font-black text-blue-400 uppercase"><?php echo date('M Y', strtotime($pv['visit_date'])); ?></p>
                                        <p class="text-xs font-bold mt-1 truncate"><?php echo htmlspecialchars($pv['diagnosis'] ?? 'Clinical Review'); ?></p>
                                    </div>
                                <?php endwhile; else: ?>
                                    <p class="text-xs italic opacity-40">No records found.</p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Navigation Guidance -->
                <div class="mt-16 flex justify-end">
                    <button onclick="scrollToSection('objective-section')" class="nav-next-btn shadow-blue-100">
                        2. Review Vitals & Labs <i data-lucide="arrow-right" class="w-4 h-4 ml-2"></i>
                    </button>
                </div>
            </section>

            <!-- SECTION 2: OBJECTIVE (Vitals & Labs) -->
            <section id="objective-section" class="max-w-5xl mx-auto">
                <div class="flex items-center gap-4 mb-8">
                    <span class="w-8 h-8 bg-indigo-600 text-white rounded-lg flex items-center justify-center text-xs font-black">O</span>
                    <h3 class="text-xs font-black text-slate-900 uppercase tracking-[0.2em]">Objective: Diagnostic Evidence</h3>
                </div>

                <div class="grid grid-cols-12 gap-8">
                    <!-- Vitals Column -->
                    <div class="col-span-4 space-y-6">
                        <div class="p-8 bg-white rounded-[40px] border border-slate-200 shadow-sm">
                            <h4 class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-6">Latest Vitals</h4>
                            <div class="grid grid-cols-2 gap-4">
                                <div class="p-4 bg-slate-50 rounded-2xl border border-slate-100 text-center">
                                    <p class="text-[8px] font-black text-slate-400 uppercase mb-1">BP</p>
                                    <p class="text-xs font-black text-slate-900"><?php echo ($vitals['blood_pressure_sys'] ?? '--').'/'.($vitals['blood_pressure_dia'] ?? '--'); ?></p>
                                </div>
                                <div class="p-4 bg-slate-50 rounded-2xl border border-slate-100 text-center">
                                    <p class="text-[8px] font-black text-slate-400 uppercase mb-1">Temp</p>
                                    <p class="text-xs font-black text-slate-900"><?php echo ($vitals['temperature'] ?? '--'); ?>°C</p>
                                </div>
                            </div>
                        </div>
                        <div class="p-6 bg-white rounded-[40px] border border-slate-200 shadow-sm">
                            <h4 class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-4">Pressure Curve</h4>
                            <div class="h-32"><canvas id="vitalsTrendChart"></canvas></div>
                        </div>
                    </div>

                    <!-- Labs Column -->
                    <div class="col-span-8">
                        <div class="p-10 bg-white rounded-[40px] border border-slate-200 shadow-sm h-full flex flex-col">
                            <div class="flex justify-between items-center mb-8">
                                <h4 class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Lab Intelligence</h4>
                                <button onclick="openLabModal()" class="text-[9px] font-black text-blue-600 uppercase hover:underline">+ Request Lab</button>
                            </div>
                            <div class="flex-1 space-y-3">
                                <?php if ($labs->num_rows > 0): while($l = $labs->fetch_assoc()): 
                                    $is_done = ($l['findings'] !== null);
                                    $is_bad = ($l['is_abnormal']);
                                ?>
                                    <div class="flex items-center justify-between p-4 bg-slate-50 rounded-2xl border <?php echo $is_bad ? 'border-rose-100' : 'border-slate-100'; ?>">
                                        <div class="flex items-center gap-4">
                                            <div class="w-8 h-8 rounded-full <?php echo $is_done ? 'bg-emerald-100 text-emerald-600' : 'bg-amber-100 text-amber-600'; ?> flex items-center justify-center">
                                                <i data-lucide="<?php echo $is_done ? 'check' : 'clock'; ?>" class="w-4 h-4"></i>
                                            </div>
                                            <div>
                                                <p class="text-xs font-black text-slate-900"><?php echo htmlspecialchars($l['test_name']); ?></p>
                                                <?php if($is_done): ?>
                                                    <p class="text-[10px] font-bold text-slate-500 italic mt-0.5"><?php echo htmlspecialchars($l['findings']); ?></p>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        <?php if($l['numeric_value']): ?>
                                            <p class="text-xs font-black <?php echo $is_bad ? 'text-rose-600' : 'text-slate-900'; ?>"><?php echo $l['numeric_value']; ?></p>
                                        <?php endif; ?>
                                    </div>
                                <?php endwhile; else: ?>
                                    <div class="py-12 text-center opacity-20"><i data-lucide="beaker" class="w-10 h-10 mx-auto mb-4"></i><p class="text-[10px] font-black uppercase">No diagnostics</p></div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Navigation Guidance -->
                <div class="mt-16 flex justify-end">
                    <button onclick="scrollToSection('assessment-section')" class="nav-next-btn shadow-indigo-100">
                        3. Formulate Assessment & Plan <i data-lucide="arrow-right" class="w-4 h-4 ml-2"></i>
                    </button>
                </div>
            </section>

            <!-- SECTION 3: ASSESSMENT & PLAN (Diagnosis & Rx) -->
            <section id="assessment-section" class="max-w-5xl mx-auto">
                <div class="flex items-center gap-4 mb-8">
                    <span class="w-8 h-8 bg-emerald-600 text-white rounded-lg flex items-center justify-center text-xs font-black">A</span>
                    <h3 class="text-xs font-black text-slate-900 uppercase tracking-[0.2em]">Assessment & Plan: Clinical Decision</h3>
                </div>

                <div class="p-12 bg-white rounded-[60px] border-2 border-emerald-50 shadow-2xl shadow-emerald-100/50">
                    <form id="clinical-plan-form" onsubmit="finalizeConsultation(event)" class="grid grid-cols-12 gap-12">
                        <!-- Left: Diagnosis & Notes -->
                        <div class="col-span-7 space-y-8">
                            <div class="space-y-4 relative">
                                <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-6">Clinical Diagnosis (Coded)</label>
                                <input type="text" id="diagnosis-search" autocomplete="off" oninput="searchDiagnosis(this.value)" class="w-full px-8 py-4 bg-slate-50 rounded-2xl border-0 font-bold text-slate-700 text-sm focus:ring-4 focus:ring-emerald-100 transition-all" placeholder="Search ICD-10 or common terms...">
                                <div id="diagnosis-suggestions" class="absolute left-0 right-0 top-[85px] bg-white rounded-2xl shadow-2xl border border-slate-100 z-50 hidden"></div>
                                <textarea name="diagnosis" id="diagnosis-field" required class="w-full px-8 py-6 bg-slate-50 rounded-[32px] border-0 font-bold text-slate-700 text-sm resize-none h-32 focus:ring-4 focus:ring-emerald-100 transition-all" placeholder="Final assessment will appear here..."></textarea>
                            </div>

                            <div class="space-y-4">
                                <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-6">Clinical Observations / Notes</label>
                                <textarea name="clinical_notes" class="w-full px-8 py-6 bg-slate-50 rounded-[32px] border-0 font-medium text-slate-600 text-sm resize-none h-48 focus:ring-4 focus:ring-blue-100 transition-all" placeholder="Enter detailed clinical findings, SOAP notes, or patient advice..."></textarea>
                            </div>
                        </div>

                        <!-- Right: Rx & Follow-up -->
                        <div class="col-span-5 flex flex-col gap-8">
                            <div class="space-y-4">
                                <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-6">Follow-up Schedule</label>
                                <div class="relative">
                                    <i data-lucide="calendar" class="absolute left-6 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400"></i>
                                    <input type="date" name="follow_up_date" class="w-full pl-14 pr-8 py-4 bg-slate-50 rounded-2xl border-0 font-bold text-slate-700 text-sm focus:ring-4 focus:ring-emerald-100 transition-all">
                                </div>
                            </div>

                            <div class="flex-1 flex flex-col">
                                <div class="flex justify-between items-center px-4 mb-4">
                                    <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Medical Script (Rx)</label>
                                    <button type="button" onclick="openMedModal()" class="px-4 py-2 bg-blue-600 text-white rounded-xl text-[9px] font-black uppercase shadow-lg shadow-blue-100">+ Add Medication</button>
                                </div>
                                <div id="prescription-preview" class="flex-1 bg-slate-50 rounded-[40px] p-8 border border-slate-100 flex flex-col gap-4">
                                    <p class="text-[10px] text-slate-300 italic font-bold text-center py-20">No medications added.</p>
                                </div>
                                <input type="hidden" name="medications_json" id="meds-input">
                            </div>
                            
                            <button type="submit" id="submit-btn" class="w-full py-6 bg-slate-900 text-white rounded-[32px] font-black uppercase text-xs tracking-[0.3em] shadow-2xl shadow-slate-200 hover:scale-[1.02] active:scale-[0.98] transition-all">
                                Finalize & Release Case
                            </button>
                        </div>
                    </form>
                </div>
            </section>

        </main>
    </div>

    <!-- Modals -->
    <div id="lab-test-modal" class="hidden fixed inset-0 bg-slate-900/60 backdrop-blur-md z-[100] flex items-center justify-center p-6">
        <div class="bg-white rounded-[40px] w-full max-w-2xl shadow-2xl overflow-hidden flex flex-col max-h-[85vh]">
            <div class="p-8 border-b border-slate-100 flex justify-between items-center"><h3 class="text-xl font-black text-slate-900 uppercase">Diagnostic Order</h3><button onclick="closeLabModal()"><i data-lucide="x"></i></button></div>
            <form id="lab-request-form" class="flex-1 overflow-y-auto p-8">
                <div id="available-tests-list" class="grid grid-cols-2 gap-3"></div>
                
                <div class="mt-8 pt-8 border-t border-slate-100">
                    <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-4 mb-4 block">Other / Manual Test Request</label>
                    <input type="text" name="manual_test" class="w-full px-6 py-4 bg-slate-50 rounded-2xl border-0 text-xs font-bold" placeholder="Type test name here (e.g. Specialized Hormonal Panel)">
                </div>

                <textarea name="notes" rows="3" class="w-full px-6 py-4 bg-slate-50 rounded-2xl border-0 text-xs font-bold mt-6" placeholder="Lab notes..."></textarea>
            </form>
            <div class="p-8 bg-slate-50 border-t border-slate-100 flex gap-4"><button onclick="closeLabModal()" class="flex-1 py-4 bg-white text-slate-500 rounded-2xl font-bold uppercase text-[10px]">Cancel</button><button onclick="submitLabRequest()" class="flex-1 py-4 bg-blue-600 text-white rounded-2xl font-black uppercase text-[10px] tracking-widest">Post Request</button></div>
        </div>
    </div>

    <div id="med-modal" class="hidden fixed inset-0 bg-slate-900/60 backdrop-blur-md z-[100] flex items-center justify-center p-6">
        <div class="bg-white rounded-[40px] w-full max-w-md shadow-2xl p-10"><h3 class="text-xl font-black text-slate-900 uppercase mb-8">Add Medication</h3><form id="med-form" class="space-y-4">
        <input type="text" name="drug" required class="w-full px-6 py-4 bg-slate-50 rounded-2xl border-0 text-xs font-bold" placeholder="Drug (e.g. Paracetamol)">
        <input type="text" name="dosage" required class="w-full px-6 py-4 bg-slate-50 rounded-2xl border-0 text-xs font-bold" placeholder="Dosage (e.g. 500mg)">
        <input type="text" name="duration" required class="w-full px-6 py-4 bg-slate-50 rounded-2xl border-0 text-xs font-bold" placeholder="Instruction (e.g. 1x3 Daily for 5 days)">
        <div class="pt-6 flex gap-4"><button type="button" onclick="closeMedModal()" class="flex-1 py-4 text-slate-400 font-bold uppercase text-[10px]">Back</button><button type="submit" class="flex-1 py-4 bg-blue-600 text-white rounded-2xl font-black uppercase text-[10px]">Add</button></div></form></div>
    </div>

    <script>
        const patientId = <?php echo $patient_id; ?>;
        const visitId = <?php echo $visit_id; ?>;
        let medications = [];

        function scrollToSection(id) {
            const container = document.getElementById('clinical-workspace');
            const element = document.getElementById(id);
            const offset = 20; 
            container.scrollTo({ top: element.offsetTop - offset, behavior: 'smooth' });
            updateActiveStep(id);
        }

        function updateActiveStep(sectionId) {
            document.querySelectorAll('.step-link').forEach(link => {
                link.classList.remove('active');
                if (link.getAttribute('onclick').includes(sectionId)) link.classList.add('active');
            });
        }

        document.getElementById('clinical-workspace').addEventListener('scroll', () => {
            const sections = ['subjective-section', 'objective-section', 'assessment-section'];
            sections.forEach(id => {
                const el = document.getElementById(id);
                const rect = el.getBoundingClientRect();
                if (rect.top >= 0 && rect.top <= 300) updateActiveStep(id);
            });
        });

        function markStepComplete(sectionId) {
            document.querySelectorAll('.step-link').forEach(link => {
                if (link.getAttribute('onclick').includes(sectionId)) link.classList.add('completed');
            });
        }

        function searchDiagnosis(query) {
            const suggestions = document.getElementById('diagnosis-suggestions');
            if (query.length < 2) { suggestions.classList.add('hidden'); return; }
            
            fetch(`api/search_diagnosis.php?q=${encodeURIComponent(query)}`)
                .then(r => r.json()).then(data => {
                    if (data.length > 0) {
                        suggestions.innerHTML = data.map(d => `<div onclick="selectDiagnosis('${d}')" class="px-6 py-3 hover:bg-slate-50 cursor-pointer font-bold text-slate-700 border-b border-slate-50 last:border-0">${d}</div>`).join('');
                        suggestions.classList.remove('hidden');
                    } else suggestions.classList.add('hidden');
                });
        }

        function selectDiagnosis(val) {
            const field = document.getElementById('diagnosis-field');
            field.value = (field.value + "\n" + val).trim();
            document.getElementById('diagnosis-suggestions').classList.add('hidden');
            document.getElementById('diagnosis-search').value = '';
            markStepComplete('assessment-section');
        }

        // Mini Chart
        const vHistory = <?php echo json_encode($v_history); ?>;
        if (vHistory.length > 0) {
            new Chart(document.getElementById('vitalsTrendChart'), {
                type: 'line',
                data: {
                    labels: vHistory.map(v => new Date(v.recorded_at).toLocaleDateString('en-US', {month:'short', day:'numeric'})),
                    datasets: [{
                        label: 'SYS',
                        data: vHistory.map(v => v.blood_pressure_sys),
                        borderColor: '#f43f5e',
                        borderWidth: 2,
                        pointRadius: 2,
                        tension: 0.4,
                        fill: false
                    }, {
                        label: 'DIA',
                        data: vHistory.map(v => v.blood_pressure_dia),
                        borderColor: '#3b82f6',
                        borderWidth: 2,
                        pointRadius: 2,
                        tension: 0.4,
                        fill: false
                    }]
                },
                options: { 
                    maintainAspectRatio: false, 
                    plugins: { legend: { display: false } }, 
                    scales: { 
                        x: { display: false }, 
                        y: { 
                            ticks: { font: { size: 8 } },
                            grid: { color: '#f1f5f9' }
                        } 
                    } 
                }
            });
        }

        function openLabModal() {
            document.getElementById('lab-test-modal').classList.remove('hidden');
            fetch('api/nursing_v2.php?action=get_available_tests').then(r => r.json()).then(data => {
                if (data.success) {
                    document.getElementById('available-tests-list').innerHTML = data.tests.map(t => `
                        <label class="cursor-pointer group">
                            <input type="checkbox" name="test_ids[]" value="${t.id}" class="hidden peer">
                            <div class="p-4 rounded-2xl border-2 border-slate-50 bg-slate-50 peer-checked:border-blue-600 peer-checked:bg-blue-50 transition-all">
                                <p class="font-bold text-slate-900 text-[10px]">${t.test_name}</p>
                            </div>
                        </label>
                    `).join('');
                }
            });
        }

        function closeLabModal() { document.getElementById('lab-test-modal').classList.add('hidden'); }
        function openMedModal() { document.getElementById('med-modal').classList.remove('hidden'); }
        function closeMedModal() { document.getElementById('med-modal').classList.add('hidden'); }

        function submitLabRequest() {
            const fd = new FormData(document.getElementById('lab-request-form'));
            fd.append('patient_id', patientId); fd.append('visit_id', visitId);
            fetch('api/lab_requests.php?action=create', { method: 'POST', body: fd })
            .then(r => r.json()).then(data => data.success ? location.reload() : alert(data.message));
        }

        document.getElementById('med-form').onsubmit = function(e) {
            e.preventDefault();
            medications.push({ drug: this.drug.value, dosage: this.dosage.value, duration: this.duration.value });
            updateMedPreview(); this.reset(); closeMedModal();
            markStepComplete('assessment-section');
        };

        function updateMedPreview() {
            const container = document.getElementById('prescription-preview');
            const input = document.getElementById('meds-input');
            if (medications.length === 0) {
                container.innerHTML = '<p class="text-[10px] text-slate-300 italic font-bold text-center py-20">Add medications to complete the plan.</p>';
                input.value = ''; return;
            }
            container.innerHTML = medications.map((m, i) => `
                <div class="p-4 bg-white rounded-2xl border border-blue-50 flex justify-between items-center shadow-sm">
                    <div class="min-w-0"><p class="text-[10px] font-black text-slate-900 truncate">${m.drug}</p><p class="text-[8px] font-bold text-slate-400 uppercase">${m.dosage} • ${m.duration}</p></div>
                    <button onclick="removeMed(${i})" class="text-rose-400 hover:text-rose-600"><i data-lucide="x-circle" class="w-4 h-4"></i></button>
                </div>
            `).join('');
            input.value = JSON.stringify(medications); lucide.createIcons();
        }

        function removeMed(i) { medications.splice(i, 1); updateMedPreview(); }

        function finalizeConsultation(e) {
            if (e) e.preventDefault();
            if (!confirm("Finalize case and discharge to Pharmacy?")) return;
            const fd = new FormData(document.getElementById('clinical-plan-form'));
            fd.append('patient_id', patientId); fd.append('visit_id', visitId); fd.append('action', 'finalize');
            fetch('api/doctor_clinical.php', { method: 'POST', body: fd })
            .then(r => r.json()).then(data => data.success ? window.location.href = 'telemedicine_dashboard.php' : alert(data.message));
        }

        lucide.createIcons();
    </script>
</body>
</html>