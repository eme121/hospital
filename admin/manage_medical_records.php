<?php
session_start();
require_once '../includes/db_connect.php';

if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: index.php');
    exit;
}

$message = "";
$error = "";

// Handle Record Upload
if (isset($_POST['upload_record'])) {
    $patient_id = intval($_POST['patient_id']);
    $title = strip_tags($_POST['title']);
    $type = $_POST['record_type'];
    $notes = strip_tags($_POST['notes']);
    $file_name = "";

    if (isset($_FILES['record_file']) && $_FILES['record_file']['error'] == 0) {
        $target_dir = "../assets/medical_records/";
        if (!is_dir($target_dir)) mkdir($target_dir, 0777, true);
        
        $ext = pathinfo($_FILES["record_file"]["name"], PATHINFO_EXTENSION);
        $file_name = "REC_" . uniqid() . "." . $ext;
        if (move_uploaded_file($_FILES["record_file"]["tmp_name"], $target_dir . $file_name)) {
            $stmt = $conn->prepare("INSERT INTO medical_records (patient_id, title, record_type, file_path, notes) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("issss", $patient_id, $title, $type, $file_name, $notes);
            if ($stmt->execute()) {
                $message = "Medical record uploaded successfully!";
            } else {
                $error = "Database error: " . $conn->error;
            }
        } else {
            $error = "Failed to move uploaded file.";
        }
    } else {
        $error = "Please select a valid file.";
    }
}

// Handle Delete
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $get_file = $conn->prepare("SELECT file_path FROM medical_records WHERE id = ?");
    $get_file->bind_param("i", $id);
    $get_file->execute();
    $res = $get_file->get_result()->fetch_assoc();
    
    if ($res && $res['file_path'] && file_exists("../assets/medical_records/" . $res['file_path'])) {
        unlink("../assets/medical_records/" . $res['file_path']);
    }
    
    $stmt = $conn->prepare("DELETE FROM medical_records WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $message = "Record deleted permanently.";
}

// Fetch Records
$records = $conn->query("SELECT r.*, p.full_name as patient_name, p.file_number FROM medical_records r JOIN patients p ON r.patient_id = p.id ORDER BY r.created_at DESC");
if (!$records) {
    die("Database Error (medical_records): " . $conn->error . ". Please ensure 'medical_records' and 'patients' tables exist.");
}

$patients = $conn->query("SELECT id, full_name, file_number FROM patients WHERE is_deleted = 0 ORDER BY full_name ASC");
if (!$patients) {
    die("Database Error (patients): " . $conn->error . ". Please ensure 'patients' table exists.");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Medical Records | Hope Haven Admin</title>
    <?php include 'includes/header_scripts.php'; ?>
    <style>body { font-family: 'Plus Jakarta Sans', sans-serif; }</style>
</head>
<body class="bg-slate-50 min-h-screen">
    <?php include 'includes/admin_sidebar.php'; ?>
    <?php include 'includes/admin_topbar.php'; ?>

    <main id="main-wrapper" class="pt-[90px] px-8 pb-10 lg:ml-[280px] transition-all duration-300">
        <div class="flex justify-between items-center mb-10">
            <div>
                <h1 class="text-3xl font-black text-slate-900 tracking-tight">Medical Records</h1>
                <p class="text-slate-500 font-medium">Upload and manage patient clinical reports.</p>
            </div>
            <button onclick="document.getElementById('uploadModal').classList.remove('hidden')" class="bg-blue-600 text-white px-8 py-3.5 rounded-2xl font-black text-sm hover:bg-blue-700 transition-all shadow-xl shadow-blue-200">
                Upload New Report
            </button>
        </div>

        <?php if($message): ?>
            <div class="mb-8 p-5 bg-emerald-50 text-emerald-600 rounded-2xl font-bold text-sm border border-emerald-100"><?php echo $message; ?></div>
        <?php endif; ?>
        <?php if($error): ?>
            <div class="mb-8 p-5 bg-rose-50 text-rose-600 rounded-2xl font-bold text-sm border border-rose-100"><?php echo $error; ?></div>
        <?php endif; ?>

        <div class="bg-white rounded-[32px] shadow-xl border border-slate-100 overflow-hidden">
            <table class="w-full text-left">
                <thead class="bg-slate-50 border-b border-slate-100">
                    <tr>
                        <th class="px-8 py-5 text-[10px] font-black text-slate-400 uppercase tracking-widest">Patient</th>
                        <th class="px-8 py-5 text-[10px] font-black text-slate-400 uppercase tracking-widest">Report Title</th>
                        <th class="px-8 py-5 text-[10px] font-black text-slate-400 uppercase tracking-widest">Type</th>
                        <th class="px-8 py-5 text-[10px] font-black text-slate-400 uppercase tracking-widest">Date</th>
                        <th class="px-8 py-5 text-[10px] font-black text-slate-400 uppercase tracking-widest text-right">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                    <?php while($row = $records->fetch_assoc()): ?>
                        <tr class="hover:bg-slate-50/50 transition-colors">
                            <td class="px-8 py-6">
                                <p class="font-bold text-slate-900 text-sm"><?php echo htmlspecialchars($row['patient_name']); ?></p>
                                <p class="text-[10px] text-blue-600 font-black uppercase"><?php echo $row['file_number']; ?></p>
                            </td>
                            <td class="px-8 py-6 text-sm font-medium text-slate-700"><?php echo htmlspecialchars($row['title']); ?></td>
                            <td class="px-8 py-6">
                                <span class="px-3 py-1 rounded-full bg-blue-50 text-blue-600 text-[10px] font-black uppercase"><?php echo $row['record_type']; ?></span>
                            </td>
                            <td class="px-8 py-6 text-sm text-slate-500"><?php echo date('d M Y', strtotime($row['created_at'])); ?></td>
                            <td class="px-8 py-6 text-right">
                                <div class="flex justify-end gap-2">
                                    <a href="../assets/medical_records/<?php echo $row['file_path']; ?>" target="_blank" class="p-2 bg-slate-100 text-slate-600 rounded-lg hover:bg-blue-600 hover:text-white transition-all">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                    </a>
                                    <a href="?delete=<?php echo $row['id']; ?>" onclick="return confirm('Delete this record?')" class="p-2 bg-rose-50 text-rose-600 rounded-lg hover:bg-rose-600 hover:text-white transition-all">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </main>

    <!-- Upload Modal -->
    <div id="uploadModal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-md z-50 hidden flex items-center justify-center p-6">
        <div class="bg-white max-w-lg w-full rounded-[40px] p-10 shadow-2xl">
            <h2 class="text-2xl font-black text-slate-900 mb-8">Upload Medical Report</h2>
            <form method="POST" enctype="multipart/form-data" class="space-y-6">
                <div class="space-y-2">
                    <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Select Patient</label>
                    <select name="patient_id" required class="w-full bg-slate-50 border border-slate-200 rounded-2xl px-4 py-3 font-bold text-sm outline-none">
                        <?php while($p = $patients->fetch_assoc()): ?>
                            <option value="<?php echo $p['id']; ?>"><?php echo htmlspecialchars($p['full_name']); ?> (<?php echo $p['file_number']; ?>)</option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="space-y-2">
                    <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Report Title</label>
                    <input type="text" name="title" required class="w-full bg-slate-50 border border-slate-200 rounded-2xl px-4 py-3 font-bold text-sm outline-none" placeholder="e.g. Full Blood Count Result">
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div class="space-y-2">
                        <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Report Type</label>
                        <select name="record_type" class="w-full bg-slate-50 border border-slate-200 rounded-2xl px-4 py-3 font-bold text-sm outline-none">
                            <option>Lab Result</option>
                            <option>Diagnostic</option>
                            <option>Prescription</option>
                            <option>Other</option>
                        </select>
                    </div>
                    <div class="space-y-2">
                        <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest">File (PDF/Image)</label>
                        <input type="file" name="record_file" required class="text-xs text-slate-500">
                    </div>
                </div>
                <div class="space-y-2">
                    <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Notes (Internal)</label>
                    <textarea name="notes" rows="3" class="w-full bg-slate-50 border border-slate-200 rounded-2xl px-4 py-3 font-bold text-sm outline-none" placeholder="Add any specific observations..."></textarea>
                </div>
                <div class="flex gap-4 pt-4">
                    <button type="button" onclick="document.getElementById('uploadModal').classList.add('hidden')" class="flex-1 py-4 bg-slate-100 text-slate-600 rounded-2xl font-black text-xs uppercase tracking-widest">Cancel</button>
                    <button type="submit" name="upload_record" class="flex-1 py-4 bg-blue-600 text-white rounded-2xl font-black text-xs uppercase tracking-widest shadow-lg shadow-blue-200">Upload Record</button>
                </div>
            </form>
        </div>
    </div>
</body>
</html>