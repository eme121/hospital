<?php
session_start();
require_once '../includes/db_connect.php';

if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: index.php');
    exit;
}

// Fetch all active/recent board room cases
$cases_sql = "SELECT c.*, p.full_name as patient_name, d.name as lead_doctor 
              FROM telemedicine_cases c
              LEFT JOIN patients p ON c.patient_id = p.id
              LEFT JOIN telemedicine_doctors d ON c.created_by = d.id
              ORDER BY c.created_at DESC LIMIT 50";
$cases = $conn->query($cases_sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>War Room Oversight | Admin</title>
    <?php include 'includes/header_scripts.php'; ?>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
</head>
<body class="bg-[#020617] text-slate-400 min-h-screen font-['Plus_Jakarta_Sans']">
    <?php include 'includes/admin_sidebar.php'; ?>
    
    <main class="lg:ml-[280px] p-8 pt-[90px]">
        <header class="flex justify-between items-center mb-12">
            <div>
                <h1 class="text-3xl font-black text-white tracking-tighter uppercase">Board Room <span class="text-cyan-500">Oversight</span></h1>
                <p class="text-[10px] font-bold text-slate-500 uppercase tracking-[0.3em] mt-2">Administrative Command & Control</p>
            </div>
            <div class="flex items-center gap-4 bg-slate-900/50 p-2 rounded-2xl border border-slate-800">
                <span class="w-3 h-3 rounded-full bg-emerald-500 animate-pulse"></span>
                <span class="text-[10px] font-black text-emerald-500 uppercase">Live Network Monitor</span>
            </div>
        </header>

        <div class="grid grid-cols-1 gap-8">
            <?php while($c = $cases->fetch_assoc()): 
                // Get board members for this case
                $members_res = $conn->query("SELECT d.name, d.profile_pix, m.role FROM telemedicine_case_members m JOIN telemedicine_doctors d ON m.doctor_id = d.id WHERE m.case_id = " . $c['id']);
                
                // Get last 3 ledger actions
                $ledger_res = $conn->query("SELECT * FROM telemedicine_ledger WHERE case_id = " . $c['id'] . " ORDER BY created_at DESC LIMIT 3");
            ?>
            <div class="bg-slate-900/40 border border-slate-800 rounded-[32px] p-8 hover:border-cyan-500/30 transition-all group">
                <div class="flex flex-col lg:flex-row justify-between gap-8">
                    <div class="flex-1">
                        <div class="flex items-center gap-4 mb-6">
                            <span class="px-3 py-1 bg-cyan-500/10 text-cyan-400 text-[10px] font-black rounded-lg border border-cyan-500/20 uppercase tracking-widest">Case #<?php echo $c['id']; ?></span>
                            <span class="text-[10px] font-bold text-slate-600 uppercase"><?php echo date('M d, H:i', strtotime($c['created_at'])); ?></span>
                            <?php if($c['status'] == 'Closed'): ?>
                                <span class="px-3 py-1 bg-slate-800 text-slate-400 text-[10px] font-black rounded-lg uppercase tracking-widest">Finalized</span>
                            <?php else: ?>
                                <span class="px-3 py-1 bg-emerald-500/10 text-emerald-400 text-[10px] font-black rounded-lg border border-emerald-500/20 uppercase tracking-widest animate-pulse">Active</span>
                            <?php endif; ?>
                        </div>
                        <h2 class="text-2xl font-black text-white mb-2"><?php echo htmlspecialchars($c['patient_name'] ?? $c['patient_name_or_id']); ?></h2>
                        <p class="text-sm text-slate-500 font-medium mb-8 leading-relaxed italic">"<?php echo htmlspecialchars($c['symptoms']); ?>"</p>
                        
                        <div class="flex items-center gap-6">
                            <div>
                                <p class="text-[9px] font-black text-slate-600 uppercase tracking-widest mb-3">Medical Board</p>
                                <div class="flex -space-x-3">
                                    <?php while($m = $members_res->fetch_assoc()): ?>
                                        <div class="w-10 h-10 rounded-full bg-slate-800 border-2 border-[#020617] flex items-center justify-center text-xs font-black text-white uppercase group/mem relative" title="<?php echo $m['name']; ?> (<?php echo $m['role']; ?>)">
                                            <?php echo substr($m['name'], 0, 1); ?>
                                        </div>
                                    <?php endwhile; ?>
                                </div>
                            </div>
                            <div class="h-10 w-px bg-slate-800"></div>
                            <a href="../telemedicine_report.php?id=<?php echo $c['id']; ?>" target="_blank" class="px-6 py-3 bg-slate-800 text-slate-400 rounded-xl text-[10px] font-black uppercase tracking-widest hover:bg-white hover:text-slate-900 transition-all">Audit Report</a>
                        </div>
                    </div>

                    <div class="lg:w-96">
                        <p class="text-[9px] font-black text-slate-600 uppercase tracking-widest mb-4">Live Ledger Stream</p>
                        <div class="space-y-3">
                            <?php if($ledger_res->num_rows > 0): ?>
                                <?php while($l = $ledger_res->fetch_assoc()): ?>
                                    <div class="p-4 bg-slate-950/50 rounded-2xl border border-slate-800/50">
                                        <div class="flex justify-between items-center mb-1">
                                            <span class="text-[9px] font-black text-cyan-500 uppercase"><?php echo str_replace('_', ' ', $l['action_type']); ?></span>
                                            <span class="text-[8px] font-bold text-slate-700"><?php echo date('H:i', strtotime($l['created_at'])); ?></span>
                                        </div>
                                        <p class="text-[10px] text-slate-400 font-medium truncate"><?php echo $l['description']; ?></p>
                                    </div>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <p class="text-[10px] text-slate-700 font-bold italic py-4">Waiting for clinical actions...</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            <?php endwhile; ?>
        </div>
    </main>

    <script>lucide.createIcons();</script>
</body>
</html>