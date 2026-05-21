<?php
session_start();
require_once 'includes/db_connect.php';

if (!isset($_SESSION['doctor_id']) && !isset($_SESSION['patient_id'])) { exit("Unauthorized"); }

$case_id = intval($_GET['id'] ?? 0);
$query = "SELECT c.*, d.name as doctor_name, dep.name as specialty 
          FROM telemedicine_cases c 
          JOIN telemedicine_doctors d ON c.created_by = d.id 
          LEFT JOIN departments dep ON d.department_id = dep.id
          WHERE c.id = ?";
$stmt = $conn->prepare($query);
if (!$stmt) {
    exit("Database error: " . $conn->error);
}
$stmt->bind_param("i", $case_id);
$stmt->execute();
$case = $stmt->get_result()->fetch_assoc();

if (!$case) { exit("Case not found"); }

// If a patient is logged in, ensure they can only see their own report
if (isset($_SESSION['patient_id']) && !isset($_SESSION['doctor_id'])) {
    if ($case['patient_id'] != $_SESSION['patient_id']) {
        exit("Unauthorized: You can only view your own reports.");
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Clinical Summary - Case #<?php echo $case_id; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
        @media print {
            .no-print { display: none; }
            body { background: white; }
        }
    </style>
</head>
<body class="bg-slate-50 p-4 md:p-8">
    <div class="max-w-4xl mx-auto bg-white shadow-xl rounded-[32px] md:rounded-[40px] overflow-hidden border border-slate-100">
        <!-- Letterhead Header -->
        <div class="bg-slate-900 p-8 md:p-12 text-white flex flex-col md:flex-row justify-between items-center text-center md:text-left gap-6">
            <div>
                <h1 class="text-2xl md:text-3xl font-black tracking-tighter uppercase mb-2">Hope Haven <span class="text-cyan-400">Hospital</span></h1>
                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-[0.3em]">Specialist Consultation Division</p>
            </div>
            <div class="md:text-right">
                <p class="text-[10px] font-black uppercase tracking-widest text-cyan-400 mb-1">Report Generated</p>
                <p class="text-sm font-bold"><?php echo date('F d, Y'); ?></p>
            </div>
        </div>

        <div class="p-8 md:p-16 space-y-12">
            <!-- Patient & Doctor Metadata -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-8 md:gap-16 border-b border-slate-100 pb-12">
                <div>
                    <h4 class="text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] mb-4">Patient Information</h4>
                    <p class="text-xl font-black text-slate-900 mb-1"><?php echo htmlspecialchars($case['patient_name_or_id']); ?></p>
                    <p class="text-xs font-bold text-slate-500 uppercase tracking-tight">Case ID: #D2D-<?php echo str_pad($case['id'], 5, '0', STR_PAD_LEFT); ?></p>
                </div>
                <div>
                    <h4 class="text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] mb-4">Referring Specialist</h4>
                    <p class="text-xl font-black text-slate-900 mb-1">Dr. <?php echo htmlspecialchars($case['doctor_name']); ?></p>
                    <p class="text-xs font-bold text-cyan-600 uppercase tracking-tight"><?php echo htmlspecialchars($case['specialty'] ?? 'Clinical Medicine'); ?></p>
                </div>
            </div>

            <!-- Clinical Content -->
            <div class="space-y-10">
                <div>
                    <h4 class="text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] mb-6 flex items-center gap-3">
                        <span class="w-8 h-[1px] bg-slate-200"></span> Clinical Presentation
                    </h4>
                    <div class="bg-slate-50 p-8 rounded-3xl border border-slate-100">
                        <p class="text-sm leading-relaxed text-slate-700 italic">"<?php echo nl2br(htmlspecialchars($case['symptoms'])); ?>"</p>
                    </div>
                </div>

                <div>
                    <h4 class="text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] mb-6 flex items-center gap-3">
                        <span class="w-8 h-[1px] bg-slate-200"></span> Specialist Assessment (SOAP)
                    </h4>
                    <div class="prose prose-slate max-w-none">
                        <div class="bg-white p-8 rounded-3xl border-2 border-slate-50 space-y-6">
                            <?php if ($case['status'] === 'Closed'): ?>
                                <pre class="font-sans whitespace-pre-wrap text-sm leading-relaxed text-slate-800"><?php echo htmlspecialchars($case['diagnosis']); ?></pre>
                            <?php else: ?>
                                <p class="text-sm font-bold text-rose-500 uppercase tracking-widest text-center py-8 border-2 border-dashed border-rose-100 rounded-2xl">Case Pending Finalization</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Footer / Signature -->
            <div class="pt-12 mt-12 border-t border-slate-100 flex justify-between items-end">
                <div class="space-y-4">
                    <?php if ($case['specialist_signature']): ?>
                        <img src="<?php echo $case['specialist_signature']; ?>" class="w-48 h-20 object-contain border-b border-slate-900 mb-2" alt="Specialist Signature">
                    <?php else: ?>
                        <div class="w-48 h-1 bg-slate-900"></div>
                    <?php endif; ?>
                    <p class="text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">Authorized Specialist Signature</p>
                </div>
                <div class="no-print">
                    <button onclick="window.print()" class="px-8 py-4 bg-slate-900 text-white rounded-2xl font-black text-xs uppercase tracking-widest hover:bg-slate-800 transition-all shadow-xl">Print Report</button>
                </div>
            </div>
        </div>
        
        <div class="bg-slate-50 p-8 text-center border-t border-slate-100">
            <p class="text-[9px] font-bold text-slate-400 uppercase tracking-[0.3em]">This is a secure electronic medical record. Confidentiality required.</p>
        </div>
    </div>
</body>
</html>