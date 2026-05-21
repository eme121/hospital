<?php
session_start();
require_once '../includes/db_connect.php';

if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: index.php');
    exit;
}

$message = "";

// --- SELF-HEALING DATABASE LOGIC ---
$check_cols = $conn->query("SHOW COLUMNS FROM financial_aid_requests LIKE 'is_approved'");
if ($check_cols->num_rows == 0) {
    $conn->query("ALTER TABLE financial_aid_requests ADD COLUMN is_approved INT(1) DEFAULT 0");
    $conn->query("ALTER TABLE financial_aid_requests ADD COLUMN display_on_site INT(1) DEFAULT 0");
    $conn->query("ALTER TABLE financial_aid_requests ADD COLUMN display_until DATETIME DEFAULT NULL");
}

// Handle status updates (Approval & Site Display)
if (isset($_GET['action']) && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $action = $_GET['action'];
    
    if ($action === 'approve') {
        $stmt = $conn->prepare("UPDATE financial_aid_requests SET is_approved = 1 WHERE id = ?");
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) $message = "Request approved!";
    }
    elseif ($action === 'decline') {
        $stmt = $conn->prepare("UPDATE financial_aid_requests SET is_approved = -1, display_on_site = 0 WHERE id = ?");
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) $message = "Request declined.";
    }
    elseif ($action === 'toggle_site') {
        $stmt = $conn->prepare("UPDATE financial_aid_requests SET display_on_site = NOT display_on_site WHERE id = ? AND is_approved = 1");
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) $message = "Site visibility toggled!";
    }
    elseif ($action === 'complete') {
        $conn->query("UPDATE financial_aid_requests SET status = 'completed', display_on_site = 0 WHERE id = $id");
        $message = "Case marked as completed.";
    }
}

// Handle Duration Update
if (isset($_POST['set_duration'])) {
    $id = intval($_POST['id']);
    $until = $_POST['display_until'];
    $stmt = $conn->prepare("UPDATE financial_aid_requests SET display_until = ? WHERE id = ?");
    $stmt->bind_param("si", $until, $id);
    if ($stmt->execute()) $message = "Display duration updated!";
}

// Handle deletion
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $stmt = $conn->prepare("DELETE FROM financial_aid_requests WHERE id = ?");
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) $message = "Request deleted successfully!";
}

$result = $conn->query("SELECT * FROM financial_aid_requests ORDER BY created_at DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Financial Aid | Admin</title>
    <?php include 'includes/header_scripts.php'; ?>
    <style>body { font-family: 'Plus Jakarta Sans', sans-serif; }</style>
</head>
<body class="bg-slate-50 min-h-screen">
    <?php include 'includes/admin_sidebar.php'; ?>
    <?php include 'includes/admin_topbar.php'; ?>

    <main id="main-wrapper" class="pt-[90px] px-8 pb-10 lg:ml-[280px] transition-all duration-300">
        <div class="flex flex-col md:flex-row justify-between items-start md:items-end mb-10 gap-6 no-print">
            <div>
                <h1 class="text-3xl font-black text-slate-900 mb-2 tracking-tight">Financial Aid Requests</h1>
                <p class="text-slate-500 font-medium">Review and manage patient requests for financial assistance.</p>
            </div>
        </div>

        <?php if($message): ?>
            <div class="mb-8 p-5 bg-emerald-50 text-emerald-600 rounded-2xl font-bold text-sm border border-emerald-100 flex items-center gap-3 animate-bounce">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <div class="bg-white rounded-[32px] shadow-xl shadow-slate-200/50 border border-slate-100 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse" id="aidTable">
                    <thead>
                        <tr class="bg-slate-50/50 border-b border-slate-100">
                            <th class="px-8 py-5 text-[11px] font-bold text-slate-400 uppercase tracking-[0.2em]">Patient & Reason</th>
                            <th class="px-8 py-5 text-[11px] font-bold text-slate-400 uppercase tracking-[0.2em]">Funding</th>
                            <th class="px-8 py-5 text-[11px] font-bold text-slate-400 uppercase tracking-[0.2em]">Approval</th>
                            <th class="px-8 py-5 text-[11px] font-bold text-slate-400 uppercase tracking-[0.2em]">Site Visibility</th>
                            <th class="px-8 py-5 text-[11px] font-bold text-slate-400 uppercase tracking-[0.2em] text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50">
                        <?php while($row = $result->fetch_assoc()): ?>
                            <tr class="hover:bg-slate-50/50 transition-colors">
                                <td class="px-8 py-6">
                                    <p class="font-bold text-slate-900 text-sm"><?php echo htmlspecialchars($row['name']); ?></p>
                                    <p class="text-[10px] text-slate-400 font-medium line-clamp-1 italic">"<?php echo htmlspecialchars($row['reason']); ?>"</p>
                                </td>
                                <td class="px-8 py-6">
                                    <span class="text-xs font-bold text-slate-700">₦<?php echo number_format($row['amount']); ?></span>
                                </td>
                                <td class="px-8 py-6">
                                    <?php if($row['is_approved'] == 0): ?>
                                        <div class="flex gap-2">
                                            <a href="?action=approve&id=<?php echo $row['id']; ?>" class="text-[10px] font-black bg-emerald-100 text-emerald-700 px-3 py-1 rounded-lg uppercase">Approve</a>
                                            <a href="?action=decline&id=<?php echo $row['id']; ?>" class="text-[10px] font-black bg-rose-100 text-rose-700 px-3 py-1 rounded-lg uppercase">Decline</a>
                                        </div>
                                    <?php elseif($row['is_approved'] == 1): ?>
                                        <span class="text-[10px] font-black text-emerald-600 uppercase flex items-center gap-1">
                                            <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"></path></svg>
                                            Approved
                                        </span>
                                    <?php else: ?>
                                        <span class="text-[10px] font-black text-rose-600 uppercase">Declined</span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-8 py-6">
                                    <?php if($row['is_approved'] == 1): ?>
                                        <div class="flex flex-col gap-2">
                                            <a href="?action=toggle_site&id=<?php echo $row['id']; ?>" class="text-[10px] font-black <?php echo $row['display_on_site'] ? 'bg-blue-600 text-white' : 'bg-slate-100 text-slate-400'; ?> px-3 py-1.5 rounded-lg uppercase text-center">
                                                <?php echo $row['display_on_site'] ? 'Live on Site' : 'Hidden'; ?>
                                            </a>
                                            <?php if($row['display_on_site']): ?>
                                                <form method="POST" class="flex gap-1">
                                                    <input type="hidden" name="id" value="<?php echo $row['id']; ?>">
                                                    <input type="datetime-local" name="display_until" value="<?php echo $row['display_until'] ? date('Y-m-d\TH:i', strtotime($row['display_until'])) : ''; ?>" class="text-[9px] border rounded px-1 outline-none">
                                                    <button type="submit" name="set_duration" class="bg-slate-900 text-white p-1 rounded"><svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path></svg></button>
                                                </form>
                                            <?php endif; ?>
                                        </div>
                                    <?php else: ?>
                                        <span class="text-[10px] text-slate-300 font-bold uppercase italic">Requires Approval</span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-8 py-6 text-center">
                                    <div class="flex justify-center gap-2">
                                        <?php if($row['status'] == 'pending'): ?>
                                            <a href="?action=complete&id=<?php echo $row['id']; ?>" class="p-2 bg-emerald-50 text-emerald-600 rounded-lg hover:bg-emerald-600 hover:text-white transition-all"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path></svg></a>
                                        <?php endif; ?>
                                        <a href="?delete=<?php echo $row['id']; ?>" onclick="return confirm('Delete permanently?')" class="p-2 bg-rose-50 text-rose-600 rounded-lg hover:bg-rose-600 hover:text-white transition-all"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg></a>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</body>
</html>