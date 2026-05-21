<?php
session_start();
if (!isset($_SESSION['patient_id'])) {
    header('Location: patient_login.php');
    exit;
}
require_once 'includes/db_connect.php';

$patient_id = $_SESSION['patient_id'];
$sql = "SELECT r.*, t.test_name, t.category, lt.name as tech_name
        FROM lab_results r
        JOIN lab_requests req ON r.request_id = req.id
        JOIN lab_tests t ON req.test_id = t.id
        JOIN lab_technicians lt ON r.technician_id = lt.id
        WHERE r.patient_id = $patient_id AND r.status = 'Released'
        ORDER BY r.released_at DESC";
$results = $conn->query($sql);

include 'includes/dashboard_header.php';
?>

<main class="min-h-screen bg-slate-50 py-24">
    <div class="max-w-6xl mx-auto px-4">
        <header class="mb-12 flex justify-between items-center">
            <div>
                <h1 class="text-4xl font-black text-slate-900 tracking-tight">Laboratory Reports</h1>
                <p class="text-slate-500 font-medium">Access your diagnostic test results and medical findings.</p>
            </div>
            <a href="patient_dashboard.php" class="px-8 py-3 bg-white border border-slate-100 rounded-xl font-bold text-xs uppercase text-slate-500 hover:text-slate-900 transition-all shadow-sm">Dashboard</a>
        </header>

        <div class="grid grid-cols-1 gap-6">
            <?php if ($results->num_rows == 0): ?>
                <div class="bg-white rounded-[40px] p-20 text-center border border-dashed border-slate-200">
                    <p class="text-slate-400 font-bold italic">No laboratory results released yet.</p>
                </div>
            <?php else: ?>
                <?php while($row = $results->fetch_assoc()): ?>
                <div class="bg-white rounded-[40px] border border-slate-100 shadow-sm p-8 flex flex-col md:flex-row gap-8 items-center justify-between hover:shadow-xl transition-all" data-aos="fade-up">
                    <div class="flex items-center gap-6">
                        <div class="w-16 h-16 bg-blue-50 text-blue-600 rounded-2xl flex items-center justify-center">
                            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"></path></svg>
                        </div>
                        <div>
                            <h3 class="text-xl font-black text-slate-900"><?php echo $row['test_name']; ?></h3>
                            <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest"><?php echo $row['category']; ?> &bull; Released <?php echo date('d M Y', strtotime($row['released_at'])); ?></p>
                        </div>
                    </div>

                    <div class="flex-1 px-8">
                        <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Technician's Summary</p>
                        <p class="text-sm font-bold text-slate-700 line-clamp-2"><?php echo htmlspecialchars($row['findings']); ?></p>
                    </div>

                    <div class="flex items-center gap-4">
                        <button onclick="viewFindings('<?php echo addslashes(htmlspecialchars($row['test_name'])); ?>', '<?php echo addslashes(htmlspecialchars($row['findings'])); ?>')" class="px-6 py-3 bg-slate-50 text-slate-600 rounded-xl font-black text-[10px] uppercase tracking-widest hover:bg-slate-100 transition-all">View Details</button>
                        <?php if($row['result_file']): ?>
                            <a href="assets/lab_results/<?php echo $row['result_file']; ?>" target="_blank" class="p-4 bg-blue-600 text-white rounded-2xl hover:bg-blue-700 transition-all shadow-lg shadow-blue-100">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endwhile; ?>
            <?php endif; ?>
        </div>
    </div>
</main>

<!-- Findings Modal -->
<div id="findingsModal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-50 hidden flex items-center justify-center p-4">
    <div class="bg-white rounded-[40px] w-full max-w-2xl overflow-hidden shadow-2xl animate-zoom-in">
        <div class="p-10 border-b border-slate-50 bg-slate-50/50 flex justify-between items-center">
            <h3 id="modalTestName" class="text-2xl font-black text-slate-900">Lab Result</h3>
            <button onclick="closeModal()" class="text-slate-400 hover:text-slate-900 transition-colors">&times;</button>
        </div>
        <div class="p-10">
            <p class="text-[10px] font-black text-blue-600 uppercase tracking-[0.3em] mb-6">Detailed Findings</p>
            <div id="modalFindings" class="text-slate-700 font-medium leading-relaxed bg-slate-50 p-8 rounded-[32px] border border-slate-100 whitespace-pre-wrap"></div>
            <div class="mt-10 flex justify-center">
                <button onclick="closeModal()" class="px-10 py-4 bg-slate-900 text-white rounded-2xl font-black text-xs uppercase tracking-widest hover:bg-blue-600 transition-all">Dismiss Report</button>
            </div>
        </div>
    </div>
</div>

<script>
function viewFindings(name, findings) {
    document.getElementById('modalTestName').textContent = name;
    document.getElementById('modalFindings').textContent = findings;
    document.getElementById('findingsModal').classList.remove('hidden');
    document.getElementById('findingsModal').classList.add('flex');
}
function closeModal() {
    document.getElementById('findingsModal').classList.add('hidden');
    document.getElementById('findingsModal').classList.remove('flex');
}
</script>

<?php include 'includes/footer.php'; ?>
