<?php
session_start();
require_once 'includes/db_connect.php';

// Check if patient is logged in
if (!isset($_SESSION['patient_id'])) {
    header('Location: patient_login.php');
    exit;
}

$patient_id = $_SESSION['patient_id'];
$patient_name = $_SESSION['patient_name'] ?? 'Patient';

// Fetch Virtual Appointments
$appt_sql = "
    SELECT ta.*, td.name as doctor_name, d.name as department_name, ta.is_paid 
    FROM telemedicine_appointments ta
    LEFT JOIN telemedicine_doctors td ON ta.doctor_id = td.id
    LEFT JOIN departments d ON ta.department_id = d.id
    WHERE ta.patient_id = ? AND ta.appointment_date >= CURDATE()
    ORDER BY ta.appointment_date ASC, ta.appointment_time ASC";

$appt_stmt = $conn->prepare($appt_sql);
$appt_stmt->bind_param("i", $patient_id);
$appt_stmt->execute();
$appointments = $appt_stmt->get_result();

// Fetch Cases/Consultations
$case_sql = "
    SELECT tc.*, td.name as doctor_name 
    FROM telemedicine_cases tc
    LEFT JOIN telemedicine_doctors td ON tc.created_by = td.id
    WHERE tc.patient_id = ?
    ORDER BY tc.created_at DESC";

$case_stmt = $conn->prepare($case_sql);
$case_stmt->bind_param("i", $patient_id);
$case_stmt->execute();
$cases = $case_stmt->get_result();

// --- ONBOARDING CHECK ---
$onboarding_res = $conn->query("SELECT status FROM patient_onboarding WHERE patient_id = $patient_id");
$onboarding = $onboarding_res->fetch_assoc();

// If they have only 'Paid' but NOT filled the form, we used to kick them back.
// Now we allow 'Paid' so they can join consultations, but will show a banner to complete the form.
if (!$onboarding || !in_array($onboarding['status'], ['Completed', 'Sent to Nursing', 'Verified', 'In Intake', 'Pending Records', 'Paid'])) {
    header("Location: onboarding.php");
    exit();
}
$show_completion_banner = ($onboarding['status'] === 'Paid');
// ------------------------

include 'includes/dashboard_header.php';
?>

<main class="bg-slate-50 min-h-screen py-12">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

        <?php if ($show_completion_banner): ?>
            <div class="mb-8 bg-indigo-600 rounded-[32px] p-6 text-white flex flex-col md:flex-row items-center justify-between gap-6 shadow-xl shadow-indigo-200">
                <div class="flex items-center gap-4">
                    <div class="w-12 h-12 bg-white/20 rounded-2xl flex items-center justify-center backdrop-blur-md">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                    </div>
                    <div>
                        <h4 class="text-lg font-black uppercase tracking-tighter">Complete Your Medical Profile</h4>
                        <p class="text-sm font-medium text-indigo-100">You've successfully paid for your folder. Please complete the medical history form to fully activate your records.</p>
                    </div>
                </div>
                <a href="onboarding.php" class="px-8 py-4 bg-white text-indigo-600 rounded-2xl font-black text-xs uppercase tracking-widest hover:bg-indigo-50 transition-all shadow-lg whitespace-nowrap">Complete Form Now</a>
            </div>
        <?php endif; ?>
        
        <!-- Header -->
        <div class="mb-12 flex flex-col md:flex-row justify-between items-start md:items-end gap-4">
            <div>
                <h1 class="text-3xl md:text-4xl font-black text-slate-900 mb-2">Virtual Care Center</h1>
                <p class="text-slate-500 font-medium tracking-wide">Join your video consultations and track your medical cases.</p>
            </div>
            <a href="patient_dashboard.php" class="text-sm font-bold text-blue-600 hover:text-blue-700 flex items-center shrink-0">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                Back to Dashboard
            </a>
        </div>

        <!-- Active Video Calls -->
        <div class="mb-16">
            <h2 class="text-xl font-black text-slate-800 mb-6 flex items-center">
                <span class="w-2 h-2 bg-red-500 rounded-full mr-3 animate-pulse"></span>
                Upcoming Video Consultations
            </h2>
            
            <div class="grid gap-6">
                <?php if ($appointments->num_rows > 0): ?>
                    <?php while ($appt = $appointments->fetch_assoc()): ?>
                        <div class="bg-white rounded-[32px] p-8 border border-slate-100 shadow-sm flex flex-col md:flex-row md:items-center justify-between gap-6 hover:shadow-xl transition-all">
                            <div class="flex items-center gap-6">
                                <div class="w-16 h-16 bg-blue-50 rounded-2xl flex items-center justify-center">
                                    <svg class="w-8 h-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"></path></svg>
                                </div>
                                <div>
                                    <h3 class="text-lg font-black text-slate-900"><?php echo htmlspecialchars($appt['doctor_name'] ?? 'Doctor to be assigned'); ?></h3>
                                    <p class="text-xs font-bold text-blue-600 uppercase tracking-widest mt-1"><?php echo htmlspecialchars($appt['department_name']); ?> Specialization</p>
                                    <div class="flex items-center gap-4 mt-3">
                                        <span class="text-sm font-bold text-slate-500 flex items-center">
                                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                                            <?php echo date('F j, Y', strtotime($appt['appointment_date'])); ?>
                                        </span>
                                        <span class="text-sm font-bold text-slate-500 flex items-center">
                                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                            <?php echo date('h:i A', strtotime($appt['appointment_time'])); ?>
                                        </span>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="flex flex-col items-end gap-3">
                                <?php if (in_array($appt['status'], ['Accepted', 'Confirmed'])): ?>
                                    <?php 
                                        $is_paid = isset($appt['is_paid']) ? $appt['is_paid'] : 0;
                                        if (!$is_paid): 
                                    ?>
                                        <button disabled class="px-10 py-4 bg-amber-50 text-amber-600 border border-amber-100 rounded-2xl font-black cursor-not-allowed">
                                            Waiting for Payment
                                        </button>
                                        <p class="text-[10px] font-bold text-amber-500 uppercase tracking-widest">Please settle your invoice</p>
                                    <?php else: ?>
                                        <a href="https://meet.jit.si/HopeHaven-Huddle-Case-<?php echo $appt['id']; ?>-<?php echo substr(md5($appt['appointment_date']), 0, 8); ?>" target="_blank" class="px-10 py-4 bg-blue-600 text-white rounded-2xl font-black shadow-lg shadow-blue-200 hover:bg-blue-700 transition-all flex items-center group">
                                            Join Video Call
                                            <svg class="w-5 h-5 ml-2 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"></path></svg>
                                        </a>
                                        <p class="text-[10px] font-bold text-emerald-500 uppercase tracking-widest">Consultation is ready</p>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <button disabled class="px-10 py-4 bg-slate-100 text-slate-400 rounded-2xl font-black cursor-not-allowed">
                                        Waiting for Confirmation
                                    </button>
                                    <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Status: <?php echo $appt['status']; ?></p>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="bg-white rounded-[32px] p-12 text-center border border-dashed border-slate-200">
                        <p class="text-slate-400 font-bold italic">No upcoming virtual consultations found.</p>
                        <a href="appointment.php" class="text-blue-600 font-bold mt-4 inline-block hover:underline">Book a virtual session →</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Medical Cases -->
        <div>
            <h2 class="text-xl font-black text-slate-800 mb-6">Your Consultation Cases</h2>
            <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-8">
                <?php if ($cases->num_rows > 0): ?>
                    <?php while ($case = $cases->fetch_assoc()): ?>
                        <div class="bg-white rounded-[32px] p-8 border border-slate-100 shadow-sm hover:shadow-xl hover:-translate-y-1 transition-all">
                            <div class="flex justify-between items-start mb-6">
                                <span class="px-3 py-1 bg-purple-50 text-purple-600 rounded-lg text-[10px] font-black uppercase tracking-wider">
                                    <?php echo $case['status']; ?>
                                </span>
                                <span class="text-[10px] font-bold text-slate-400 uppercase"><?php echo date('M d, Y', strtotime($case['created_at'])); ?></span>
                            </div>
                            <h3 class="text-lg font-black text-slate-900 mb-2"><?php echo htmlspecialchars($case['symptoms']); ?></h3>
                            <p class="text-xs text-slate-500 font-medium mb-6 line-clamp-2">Diagnosis: <?php echo htmlspecialchars($case['diagnosis'] ?: 'Awaiting review'); ?></p>
                            
                            <!-- Action Buttons -->
                            <div class="grid grid-cols-2 gap-2 mb-6">
                                <button onclick="viewPrescriptions(<?php echo $case['id']; ?>)" class="py-3 bg-emerald-50 text-emerald-600 rounded-xl text-[10px] font-black uppercase tracking-wider hover:bg-emerald-600 hover:text-white transition-all">
                                    Prescriptions
                                </button>
                                <button onclick="viewLabResults(<?php echo $case['id']; ?>)" class="py-3 bg-blue-50 text-blue-600 rounded-xl text-[10px] font-black uppercase tracking-wider hover:bg-blue-600 hover:text-white transition-all">
                                    Lab Results
                                </button>
                                <a href="telemedicine_private_chat.php?case_id=<?php echo $case['id']; ?>" class="py-3 bg-slate-100 text-slate-600 rounded-xl text-[10px] font-black uppercase tracking-wider hover:bg-slate-200 transition-all text-center">
                                    Message
                                </a>
                                <?php if ($case['status'] === 'Closed'): ?>
                                <a href="telemedicine_report.php?id=<?php echo $case['id']; ?>" target="_blank" class="py-3 bg-purple-600 text-white rounded-xl text-[10px] font-black uppercase tracking-wider hover:bg-purple-700 transition-all text-center">
                                    Full Report
                                </a>
                                <?php endif; ?>
                            </div>

                            <div class="flex items-center gap-3 pt-6 border-t border-slate-50">
                                <div class="w-8 h-8 bg-slate-100 rounded-full flex items-center justify-center text-[10px] font-black text-slate-400">
                                    DR
                                </div>
                                <span class="text-xs font-bold text-slate-700"><?php echo htmlspecialchars($case['doctor_name'] ?? 'To be assigned'); ?></span>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="col-span-full py-12 text-center text-slate-400 font-bold italic">No active consultation cases.</div>
                <?php endif; ?>
            </div>
        </div>

    </div>
</main>

<!-- Video Call Modal -->
<div id="videoModal" class="fixed inset-0 bg-slate-900/90 backdrop-blur-md z-[200] hidden flex-col">
    <div class="p-6 flex justify-between items-center text-white">
        <div class="flex items-center gap-4">
            <div class="w-3 h-3 bg-red-500 rounded-full animate-pulse"></div>
            <span class="font-black uppercase tracking-widest text-xs">Live Consultation</span>
        </div>
        <button onclick="closeVideoCall()" class="bg-white/10 hover:bg-white/20 px-6 py-2 rounded-xl font-black transition-all">End Session</button>
    </div>
    <div id="jitsi-container" class="flex-1"></div>
</div>

<!-- Prescription Modal -->
<div id="prescriptionModal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-[200] hidden items-center justify-center p-4">
    <div class="bg-white rounded-[40px] w-full max-w-xl p-10 shadow-2xl relative">
        <button onclick="togglePrescriptionModal()" class="absolute top-6 right-8 text-slate-400 hover:text-slate-600 text-3xl font-light">&times;</button>
        <h3 class="text-2xl font-black text-slate-900 mb-8">Medical Prescriptions</h3>
        <div id="prescriptionsList" class="space-y-6 max-h-[60vh] overflow-y-auto pr-4">
            <!-- Loaded via JS -->
        </div>
    </div>
</div>

<!-- Lab Results Modal -->
<div id="labModal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-[200] hidden items-center justify-center p-4">
    <div class="bg-white rounded-[40px] w-full max-w-xl p-10 shadow-2xl relative">
        <button onclick="toggleLabModal()" class="absolute top-6 right-8 text-slate-400 hover:text-slate-600 text-3xl font-light">&times;</button>
        <h3 class="text-2xl font-black text-slate-900 mb-8">Lab Investigation Results</h3>
        <div id="labsList" class="space-y-6 max-h-[60vh] overflow-y-auto pr-4">
            <!-- Loaded via JS -->
        </div>
    </div>
</div>

<script src="https://meet.ffmuc.net/external_api.js"></script>
<script>
    let jitsiApi = null;

    function viewLabResults(caseId) {
        const list = document.getElementById('labsList');
        list.innerHTML = '<p class="text-slate-400 font-bold italic animate-pulse">Fetching lab results...</p>';
        toggleLabModal();

        fetch(`api/lab_requests.php?action=get_by_case&case_id=${caseId}`)
            .then(res => res.json())
            .then(data => {
                if (data.success && data.results.length > 0) {
                    list.innerHTML = '';
                    data.results.forEach(r => {
                        const div = document.createElement('div');
                        div.className = "bg-slate-50 p-6 rounded-3xl border border-slate-100";
                        div.innerHTML = `
                            <div class="flex justify-between items-start mb-4">
                                <span class="text-[10px] font-black text-blue-600 uppercase tracking-widest bg-blue-50 px-3 py-1 rounded-lg">Clinical Finding</span>
                                <span class="text-[10px] font-bold text-slate-400 uppercase">${new Date(r.released_at).toLocaleDateString()}</span>
                            </div>
                            <h4 class="font-black text-slate-900 text-lg mb-2">${r.test_name}</h4>
                            <div class="p-4 bg-white rounded-2xl mb-4 border border-slate-100">
                                <p class="text-sm text-slate-600 font-medium leading-relaxed">${r.findings}</p>
                            </div>
                            ${r.result_file ? `<a href="${r.result_file}" target="_blank" class="text-[10px] font-black text-blue-600 uppercase tracking-widest hover:underline flex items-center gap-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a2 2 0 002 2h12a2 2 0 002-2v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                                Download Official Report
                            </a>` : ''}
                        `;
                        list.appendChild(div);
                    });
                } else {
                    list.innerHTML = '<p class="text-slate-400 font-bold italic text-center py-10">No lab results released for this case yet.</p>';
                }
            });
    }

    function toggleLabModal() {
        const m = document.getElementById('labModal');
        m.classList.toggle('hidden');
        m.classList.toggle('flex');
    }

    function viewPrescriptions(caseId) {
        const list = document.getElementById('prescriptionsList');
        list.innerHTML = '<p class="text-slate-400 font-bold italic animate-pulse">Fetching your prescriptions...</p>';
        togglePrescriptionModal();

        fetch(`api/telemedicine_prescriptions.php?action=get&case_id=${caseId}`)
            .then(res => res.json())
            .then(data => {
                if (data.success && data.prescriptions.length > 0) {
                    list.innerHTML = '';
                    data.prescriptions.forEach(p => {
                        const div = document.createElement('div');
                        div.className = "bg-slate-50 p-6 rounded-3xl border border-slate-100";
                        div.innerHTML = `
                            <div class="flex justify-between items-start mb-4">
                                <span class="text-[10px] font-black text-emerald-600 uppercase tracking-widest bg-emerald-50 px-3 py-1 rounded-lg">Official Prescription</span>
                                <span class="text-[10px] font-bold text-slate-400 uppercase">${new Date(p.created_at).toLocaleDateString()}</span>
                            </div>
                            <h4 class="font-black text-slate-900 text-lg mb-2">${p.medications}</h4>
                            <p class="text-sm font-bold text-blue-600 mb-2">Dosage: ${p.dosage}</p>
                            
                            <div class="p-4 bg-emerald-50 rounded-2xl mb-4 space-y-3">
                                <div class="flex items-center gap-3">
                                    <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                    <span class="text-[10px] font-black text-emerald-700 uppercase tracking-widest">Reminders: ${p.dosage_times}</span>
                                </div>
                                <div class="flex items-center gap-3">
                                    <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                                    <span class="text-[10px] font-black text-emerald-700 uppercase tracking-widest">Active: ${new Date(p.start_date).toLocaleDateString()} - ${new Date(p.end_date).toLocaleDateString()}</span>
                                </div>
                            </div>

                            ${p.notes ? `<div class="bg-white p-4 rounded-2xl text-xs text-slate-500 font-medium italic border border-slate-50">Note: ${p.notes}</div>` : ''}
                            <div class="mt-4 pt-4 border-t border-slate-100 flex items-center gap-3">
                                <div class="w-6 h-6 bg-slate-200 rounded-full flex items-center justify-center text-[8px] font-black text-slate-500">DR</div>
                                <span class="text-[10px] font-bold text-slate-400">Prescribed by <span class="text-slate-700">${p.doctor_name}</span></span>
                            </div>
                        `;
                        list.appendChild(div);
                    });
                } else {
                    list.innerHTML = '<p class="text-slate-400 font-bold italic text-center py-10">No prescriptions found for this case.</p>';
                }
            });
    }

    function togglePrescriptionModal() {
        const m = document.getElementById('prescriptionModal');
        m.classList.toggle('hidden');
        m.classList.toggle('flex');
    }

    function startVideoCall(roomName) {
        // We now use window.open to ensure the browser grants WebRTC permissions correctly
        // especially when the site is hosted on non-HTTPS or inside an iframe environment.
        const url = `https://meet.jit.si/${roomName}`;
        window.open(url, '_blank', 'width=1000,height=800');
    }

    function closeVideoCall() {
        if (jitsiApi) {
            jitsiApi.dispose();
            jitsiApi = null;
        }
        document.getElementById('jitsi-container').innerHTML = '';
        document.getElementById('videoModal').classList.add('hidden');
        document.getElementById('videoModal').classList.remove('flex');
    }
</script>

<?php include 'includes/footer.php'; ?>
