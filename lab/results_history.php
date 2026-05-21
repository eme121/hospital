<?php
session_start();
if (!isset($_SESSION['lab_tech_id'])) {
    header('Location: login.php');
    exit;
}
require_once '../includes/db_connect.php';
include '../includes/portal_head.php';

$sql = "SELECT r.*, p.full_name as patient_name, p.file_number, t.test_name, res.released_at
        FROM lab_requests r
        JOIN patients p ON r.patient_id = p.id
        JOIN lab_tests t ON r.test_id = t.id
        JOIN lab_results res ON r.id = res.request_id
        WHERE r.status = 'Completed'
        ORDER BY res.released_at DESC";
$history = $conn->query($sql);
?>

<div class="min-h-screen bg-slate-50 flex">
    <!-- Sidebar -->
    <aside class="w-80 bg-white border-r border-slate-100 hidden lg:block sticky top-0 h-screen overflow-y-auto">
        <div class="p-8">
            <div class="flex items-center gap-3 mb-12">
                <div class="w-10 h-10 bg-blue-600 rounded-xl flex items-center justify-center text-white font-black shadow-lg shadow-blue-100">L</div>
                <span class="text-xl font-black text-slate-900 tracking-tight">Hope Haven Hospital</span>
            </div>
            
            <nav class="space-y-2">
                <a href="dashboard.php" class="flex items-center gap-4 px-6 py-4 text-slate-500 hover:bg-blue-50 hover:text-blue-600 rounded-2xl font-bold transition-all">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path></svg>
                    Dashboard
                </a>
                <a href="results_history.php" class="flex items-center gap-4 px-6 py-4 bg-blue-50 text-blue-600 rounded-2xl font-bold transition-all">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                    Result History
                </a>
            </nav>
        </div>
    </aside>

    <!-- Main Content -->
    <main class="flex-1 p-8 lg:p-12 h-screen overflow-y-auto">
        <header class="mb-12">
            <h1 class="text-3xl font-black text-slate-900 tracking-tight">Released Results</h1>
            <p class="text-slate-500 font-medium">Historical record of all completed laboratory tests.</p>
        </header>

        <div class="bg-white rounded-[40px] border border-slate-100 shadow-sm overflow-hidden">
            <table class="w-full text-left">
                <thead class="bg-slate-50 border-b border-slate-100">
                    <tr>
                        <th class="px-8 py-5 text-[10px] font-black text-slate-400 uppercase tracking-widest">Patient</th>
                        <th class="px-8 py-5 text-[10px] font-black text-slate-400 uppercase tracking-widest">Test Name</th>
                        <th class="px-8 py-5 text-[10px] font-black text-slate-400 uppercase tracking-widest">Released Date</th>
                        <th class="px-8 py-5 text-[10px] font-black text-slate-400 uppercase tracking-widest text-right">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                    <?php if($history->num_rows == 0): ?>
                        <tr><td colspan="4" class="px-8 py-20 text-center text-slate-400 font-bold italic">No completed results in history.</td></tr>
                    <?php else: ?>
                        <?php while($row = $history->fetch_assoc()): ?>
                        <tr class="hover:bg-slate-50/50 transition-colors">
                            <td class="px-8 py-6">
                                <p class="font-bold text-slate-900"><?php echo htmlspecialchars($row['patient_name']); ?></p>
                                <p class="text-[10px] text-blue-600 font-black uppercase"><?php echo $row['file_number']; ?></p>
                            </td>
                            <td class="px-8 py-6">
                                <p class="font-bold text-slate-900"><?php echo $row['test_name']; ?></p>
                            </td>
                            <td class="px-8 py-6 font-medium text-slate-500 text-sm">
                                <?php echo date('d M Y, H:i', strtotime($row['released_at'])); ?>
                            </td>
                            <td class="px-8 py-6 text-right">
                                <a href="process.php?id=<?php echo $row['id']; ?>" class="text-blue-600 font-black text-[10px] uppercase hover:underline">View/Edit</a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </main>
</div>

<?php include '../includes/portal_footer.php'; ?>
</body>
</html>
?php include '../includes/portal_footer.php'; ?>
