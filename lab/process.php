<?php
session_start();
if (!isset($_SESSION['lab_tech_id'])) {
    header('Location: login.php');
    exit;
}
require_once '../includes/db_connect.php';
include '../includes/portal_head.php';

$request_id = intval($_GET['id']);
$sql = "SELECT r.*, p.full_name as patient_name, p.file_number, t.test_name, t.category, t.normal_range
        FROM lab_requests r
        JOIN patients p ON r.patient_id = p.id
        JOIN lab_tests t ON r.test_id = t.id
        WHERE r.id = $request_id";
$request = $conn->query($sql)->fetch_assoc();

if (!$request) die("Request not found.");
?>

<div class="min-h-screen bg-slate-50 flex">
    <!-- Sidebar (Same as dashboard) -->
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
            </nav>
        </div>
    </aside>

    <!-- Main Content -->
    <main class="flex-1 p-8 lg:p-12 h-screen overflow-y-auto">
        <header class="mb-12 flex justify-between items-center">
            <div>
                <h1 class="text-3xl font-black text-slate-900 tracking-tight">Release Result</h1>
                <p class="text-slate-500 font-medium">Entering findings for <?php echo $request['test_name']; ?></p>
            </div>
            <a href="dashboard.php" class="text-slate-400 font-bold hover:text-slate-900 flex items-center gap-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                Back to Queue
            </a>
        </header>

        <div class="grid lg:grid-cols-3 gap-12">
            <!-- Patient Info -->
            <div class="lg:col-span-1 space-y-8">
                <div class="bg-white rounded-[40px] border border-slate-100 shadow-sm p-10">
                    <h3 class="text-[10px] font-black text-blue-600 uppercase tracking-[0.3em] mb-8">Patient Profile</h3>
                    <div class="flex items-center gap-4 mb-8">
                        <div class="w-12 h-12 bg-slate-50 rounded-2xl flex items-center justify-center text-slate-400 font-black">
                            <?php echo substr($request['patient_name'], 0, 1); ?>
                        </div>
                        <div>
                            <p class="text-lg font-black text-slate-900"><?php echo htmlspecialchars($request['patient_name']); ?></p>
                            <p class="text-xs font-bold text-slate-400 uppercase tracking-widest"><?php echo $request['file_number']; ?></p>
                        </div>
                    </div>
                    <div class="pt-8 border-t border-slate-50">
                        <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-4">Reference Range</p>
                        <div class="p-6 bg-blue-50/50 rounded-3xl border border-blue-100 italic text-blue-900 font-medium text-sm leading-relaxed">
                            <?php echo nl2br(htmlspecialchars($request['normal_range'])); ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Result Form -->
            <div class="lg:col-span-2">
                <div class="bg-white rounded-[40px] border border-slate-100 shadow-sm p-10">
                    <h3 class="text-xl font-black text-slate-900 mb-8">Laboratory Findings</h3>
                    
                    <form id="resultForm" class="space-y-8">
                        <input type="hidden" name="request_id" value="<?php echo $request_id; ?>">
                        <input type="hidden" name="patient_id" value="<?php echo $request['patient_id']; ?>">
                        
                        <div class="space-y-2">
                            <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-4 mb-2 block">Detailed Observations</label>
                            <textarea name="findings" required rows="8" class="w-full bg-slate-50 border-0 rounded-[32px] px-8 py-6 outline-none focus:ring-2 focus:ring-blue-500 font-medium text-slate-700" placeholder="Enter test results, observations, and values..."></textarea>
                        </div>

                        <div class="space-y-2">
                            <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-4 mb-2 block">Upload Report (Optional PDF/Image)</label>
                            <div class="relative group">
                                <input type="file" name="result_file" class="hidden" id="fileInput" onchange="updateFileName(this)">
                                <label for="fileInput" class="flex flex-col items-center justify-center p-10 bg-slate-50 border-2 border-dashed border-slate-200 rounded-[32px] cursor-pointer group-hover:border-blue-400 transition-all">
                                    <svg class="w-10 h-10 text-slate-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path></svg>
                                    <span id="fileName" class="text-sm font-bold text-slate-400">Click to upload scanned report</span>
                                </label>
                            </div>
                        </div>

                        <div class="pt-8 border-t border-slate-50">
                            <button type="submit" class="w-full py-6 bg-blue-600 text-white rounded-3xl font-black text-lg uppercase tracking-widest shadow-2xl shadow-blue-200 hover:bg-blue-700 transition-all">
                                Finalize & Release Result
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </main>
</div>

<script>
function updateFileName(input) {
    if (input.files && input.files[0]) {
        document.getElementById('fileName').textContent = input.files[0].name;
        document.getElementById('fileName').classList.remove('text-slate-400');
        document.getElementById('fileName').classList.add('text-blue-600');
    }
}

document.getElementById('resultForm').addEventListener('submit', function(e) {
    e.preventDefault();
    if(!confirm('Are you sure you want to release these results? This action is permanent and will notify the patient.')) return;
    
    const formData = new FormData(this);
    fetch('../api/lab_results.php?action=release', { method: 'POST', body: formData })
    .then(r => r.json()).then(data => {
        if(data.success) {
            alert('Results released successfully!');
            window.location.href = 'dashboard.php';
        } else {
            alert(data.message);
        }
    });
});
</script>

<?php include '../includes/portal_footer.php'; ?>
</body>
</html>
?php include '../includes/portal_footer.php'; ?>
