<?php
session_start();
if (!isset($_SESSION['pharmacist_id'])) {
    header('Location: login.php');
    exit;
}
require_once '../includes/db_connect.php';
include '../includes/portal_head.php';

// Fetch all prescriptions, joining with patient and checking if already dispensed
$sql = "SELECT p.*, pat.full_name as patient_name, pat.file_number, 
               COALESCE(td.name, dr.name) as doctor_name, 
               disp.id as dispensation_id
        FROM telemedicine_prescriptions p
        JOIN patients pat ON p.patient_id = pat.id
        LEFT JOIN telemedicine_doctors td ON p.doctor_id = td.id
        LEFT JOIN doctors dr ON p.doctor_id = dr.id
        LEFT JOIN pharmacy_dispensations disp ON p.id = disp.prescription_id AND disp.status != 'Retrieved'
        ORDER BY p.created_at DESC";
$result = $conn->query($sql);
?>

<div class="flex h-screen overflow-hidden bg-slate-50">
    <?php include 'includes/sidebar.php'; ?>

    <main class="flex-1 flex flex-col min-w-0 h-screen overflow-hidden">
        <header class="h-20 bg-white border-b border-slate-200 flex items-center justify-between px-8 lg:px-12 shrink-0 shadow-sm z-10">
            <div>
                <h1 class="text-xl font-black text-slate-900 tracking-tight uppercase">Clinical <span class="text-emerald-600">Prescriptions</span></h1>
                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mt-0.5">Active medication orders</p>
            </div>
            <div class="flex items-center gap-4">
                <button onclick="openPatientSearch()" class="px-6 py-3 bg-slate-900 text-white rounded-2xl font-black text-xs uppercase tracking-widest shadow-xl shadow-slate-200 hover:bg-emerald-600 transition-all flex items-center gap-2">
                    <i data-lucide="plus" class="w-4 h-4"></i> New Walk-in Order
                </button>
            </div>
        </header>

        <div class="flex-1 overflow-y-auto p-8 lg:p-12 custom-scrollbar">
            <div class="grid grid-cols-1 gap-6 max-w-6xl mx-auto">
                <?php if($result->num_rows == 0): ?>
                    <div class="bg-white p-24 rounded-[40px] text-center border border-dashed border-slate-200 shadow-sm">
                        <div class="w-20 h-20 bg-slate-50 rounded-full flex items-center justify-center mx-auto mb-6">
                            <i data-lucide="clipboard-list" class="w-10 h-10 text-slate-300"></i>
                        </div>
                        <h3 class="text-xl font-black text-slate-900 uppercase">Clear Inbox</h3>
                        <p class="text-slate-400 font-bold mt-2 tracking-wide">No pending prescriptions found in the system.</p>
                    </div>
                <?php else: ?>
                    <?php while($row = $result->fetch_assoc()): ?>
                    <div class="bg-white rounded-[40px] border border-slate-100 shadow-sm p-8 flex flex-col md:flex-row gap-8 items-center justify-between hover:shadow-2xl hover:shadow-emerald-100/50 transition-all group">
                        <div class="flex items-center gap-6 flex-1">
                            <div class="w-20 h-20 bg-slate-50 text-slate-400 rounded-3xl flex items-center justify-center transition-colors group-hover:bg-emerald-50 group-hover:text-emerald-600">
                                <i data-lucide="user" class="w-10 h-10"></i>
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center gap-3 mb-1">
                                    <h3 class="text-2xl font-black text-slate-900 truncate"><?php echo htmlspecialchars($row['patient_name']); ?></h3>
                                    <span class="shrink-0 px-4 py-1.5 bg-slate-100 text-slate-500 text-[10px] font-black uppercase rounded-xl tracking-widest"><?php echo $row['file_number']; ?></span>
                                </div>
                                <div class="flex items-center gap-2 mt-2">
                                    <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                                    <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Prescribed by Dr. <?php echo htmlspecialchars($row['doctor_name']); ?> &bull; <?php echo date('d M Y', strtotime($row['created_at'])); ?></p>
                                </div>
                            </div>
                        </div>

                        <div class="flex-1 w-full md:w-auto px-8 py-6 bg-slate-50 rounded-[2.5rem] border border-slate-100">
                            <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-3 flex items-center gap-2">
                                <i data-lucide="pill" class="w-3 h-3 text-emerald-500"></i>
                                Medications
                            </p>
                            <div class="text-xs font-bold text-slate-700 leading-relaxed space-y-2">
                                <?php 
                                if (!empty($row['medications'])) {
                                    echo nl2br(htmlspecialchars($row['medications']));
                                } elseif (!empty($row['medications_json'])) {
                                    $meds = json_decode($row['medications_json'], true);
                                    if ($meds) {
                                        foreach ($meds as $m) {
                                            echo "<div class='flex justify-between border-b border-slate-200/50 pb-1'><span>• " . htmlspecialchars($m['drug']) . "</span> <span class='text-[10px] text-slate-400'>" . htmlspecialchars($m['dosage'] . " | " . $m['duration']) . "</span></div>";
                                        }
                                    } else {
                                        echo "<span class='text-rose-500'>Invalid Prescription Data</span>";
                                    }
                                } else {
                                    echo "<span class='text-slate-400 italic'>No medications listed</span>";
                                }
                                ?>
                            </div>
                        </div>

                        <div class="flex items-center gap-4 shrink-0">
                            <?php if($row['dispensation_id']): ?>
                                <div class="text-right">
                                    <span class="px-6 py-3 bg-emerald-50 text-emerald-600 rounded-2xl text-[10px] font-black uppercase tracking-[0.2em] border border-emerald-100">Fulfilled</span>
                                    <p class="text-[10px] font-bold text-slate-400 mt-3 uppercase tracking-widest">Handover Ready</p>
                                </div>
                            <?php else: ?>
                                <a href="dispense.php?id=<?php echo $row['id']; ?>" class="px-10 py-5 bg-emerald-600 text-white rounded-[2rem] font-black text-xs uppercase tracking-widest shadow-xl shadow-emerald-100 hover:bg-slate-900 transition-all flex items-center gap-3 hover:scale-105 active:scale-95">
                                    Process Order
                                    <i data-lucide="arrow-right" class="w-4 h-4"></i>
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endwhile; ?>
                <?php endif; ?>
            </div>
        </div>
    </main>
</div>

<!-- Patient Search Modal -->
<div id="patientSearchModal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-50 hidden flex items-center justify-center p-4">
    <div class="bg-white rounded-[40px] w-full max-w-2xl p-8 lg:p-12 shadow-2xl animate-fade-in relative overflow-hidden">
        <div class="absolute top-0 right-0 w-32 h-32 bg-emerald-50 rounded-full -mr-16 -mt-16 opacity-50"></div>
        <div class="flex justify-between items-start mb-10 relative z-10">
            <div>
                <h3 class="text-3xl font-black text-slate-900 uppercase tracking-tight">Patient Search</h3>
                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-[0.3em] mt-2">Walk-in Dispensing</p>
            </div>
            <button onclick="closePatientSearch()" class="p-3 bg-slate-50 text-slate-400 hover:text-rose-500 rounded-2xl transition-all">
                <i data-lucide="x" class="w-6 h-6"></i>
            </button>
        </div>

        <div class="relative mb-10 z-10">
            <i data-lucide="search" class="absolute left-6 top-1/2 -translate-y-1/2 text-emerald-500 w-6 h-6"></i>
            <input type="text" id="patientQuery" oninput="searchPatients(this.value)" placeholder="Search by name or file number..." class="w-full pl-16 pr-6 py-6 bg-slate-50 rounded-3xl border-2 border-transparent focus:border-emerald-500 focus:bg-white transition-all font-bold text-slate-900 text-lg shadow-inner">
        </div>

        <div id="searchResults" class="max-h-[400px] overflow-y-auto custom-scrollbar space-y-4 relative z-10 min-h-[100px]">
            <div class="p-10 text-center text-slate-400 font-bold italic tracking-wide">Enter criteria to begin search...</div>
        </div>
    </div>
</div>

<script>
function openPatientSearch() {
    document.getElementById('patientSearchModal').classList.remove('hidden');
    document.getElementById('patientQuery').focus();
}

function closePatientSearch() {
    document.getElementById('patientSearchModal').classList.add('hidden');
}

function searchPatients(query) {
    if(query.length < 2) {
        document.getElementById('searchResults').innerHTML = '<div class="p-10 text-center text-slate-400 font-bold italic tracking-wide">Enter criteria to begin search...</div>';
        return;
    }

    fetch(`../api/pharmacy_v2.php?action=search_patients&query=${encodeURIComponent(query)}`)
    .then(r => r.json()).then(data => {
        if(data.success) {
            if(data.patients.length === 0) {
                document.getElementById('searchResults').innerHTML = '<div class="p-10 text-center text-slate-400 font-bold italic tracking-wide">No patients found matches.</div>';
            } else {
                const html = data.patients.map(p => `
                    <a href="dispense.php?patient_id=${p.id}" class="flex items-center justify-between p-6 bg-white rounded-3xl border border-slate-100 shadow-sm hover:border-emerald-500 hover:shadow-xl hover:shadow-emerald-50 transition-all group">
                        <div class="flex items-center gap-5">
                            <div class="w-14 h-14 bg-slate-50 rounded-2xl flex items-center justify-center text-slate-400 group-hover:bg-emerald-50 group-hover:text-emerald-500 transition-all">
                                <i data-lucide="user" class="w-7 h-7"></i>
                            </div>
                            <div>
                                <p class="font-black text-slate-900 text-lg group-hover:text-emerald-600 transition-colors">${p.full_name}</p>
                                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mt-1">${p.file_number}</p>
                            </div>
                        </div>
                        <div class="w-10 h-10 bg-slate-50 rounded-xl flex items-center justify-center text-slate-300 group-hover:bg-emerald-500 group-hover:text-white transition-all transform group-hover:translate-x-1">
                            <i data-lucide="chevron-right" class="w-5 h-5"></i>
                        </div>
                    </a>
                `).join('');
                document.getElementById('searchResults').innerHTML = html;
                lucide.createIcons();
            }
        }
    });
}
</script>

<?php include '../includes/portal_footer.php'; ?>
</body>
</html>