<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();
require_once '../includes/db_connect.php';

// 1. Auto-Migration & Table Check
$conn->query("CREATE TABLE IF NOT EXISTS events (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    description TEXT NOT NULL,
    event_date DATE NOT NULL,
    image VARCHAR(255),
    is_deleted TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

// Ensure is_deleted column exists for older tables
$check_deleted = $conn->query("SHOW COLUMNS FROM events LIKE 'is_deleted'");
if ($check_deleted->num_rows == 0) {
    $conn->query("ALTER TABLE events ADD COLUMN is_deleted TINYINT(1) DEFAULT 0 AFTER image");
}

// 2. Authentication
if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: index.php');
    exit;
}

$message = "";
$error = "";

// 3. Handle Actions (Archive, Restore, Permanent Delete)
if (isset($_GET['action'])) {
    $id = intval($_GET['id']);
    $action = $_GET['action'];

    if ($action === 'archive') {
        $stmt = $conn->prepare("UPDATE events SET is_deleted = 1 WHERE id = ?");
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) {
            $message = "Event moved to archives.";
        }
    } elseif ($action === 'restore') {
        $new_date = $_POST['new_date'] ?? date('Y-m-d');
        $stmt = $conn->prepare("UPDATE events SET is_deleted = 0, event_date = ? WHERE id = ?");
        $stmt->bind_param("si", $new_date, $id);
        if ($stmt->execute()) {
            $message = "Event rescheduled and restored to the live site!";
        }
    } elseif ($action === 'delete') {
        $get_img = $conn->prepare("SELECT image FROM events WHERE id = ?");
        $get_img->bind_param("i", $id);
        $get_img->execute();
        $img_res = $get_img->get_result()->fetch_assoc();
        
        if ($img_res && $img_res['image'] && file_exists("../assets/images/events/" . $img_res['image'])) {
            unlink("../assets/images/events/" . $img_res['image']);
        }

        $stmt = $conn->prepare("DELETE FROM events WHERE id = ?");
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) {
            $message = "Event permanently deleted.";
        } else {
            $error = "System error: Could not delete event.";
        }
    }
}

// 4. Handle Add Event
// ... (existing code for add event)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_event'])) {
    $title = strip_tags($_POST['title']);
    $description = strip_tags($_POST['description']);
    $event_date = $_POST['event_date'];
    $image_name = "";

    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $target_dir = "../assets/images/events/";
        if (!is_dir($target_dir)) mkdir($target_dir, 0777, true);
        
        $ext = pathinfo($_FILES["image"]["name"], PATHINFO_EXTENSION);
        $image_name = uniqid('evt_') . "." . $ext;
        move_uploaded_file($_FILES["image"]["tmp_name"], $target_dir . $image_name);
    }

    $stmt = $conn->prepare("INSERT INTO events (title, description, event_date, image, is_deleted) VALUES (?, ?, ?, ?, 0)");
    $stmt->bind_param("ssss", $title, $description, $event_date, $image_name);
    if ($stmt->execute()) {
        $message = "New event published successfully!";
    } else {
        $error = "Failed to save event. Check database connection.";
    }
}

// 5. Fetch Stats & Data
$active_result = $conn->query("SELECT * FROM events WHERE is_deleted = 0 ORDER BY event_date DESC");
$archived_result = $conn->query("SELECT * FROM events WHERE is_deleted = 1 ORDER BY event_date DESC");

$total_count = $conn->query("SELECT id FROM events WHERE is_deleted = 0")->num_rows;
$upcoming = $conn->query("SELECT id FROM events WHERE is_deleted = 0 AND event_date >= CURDATE()")->num_rows;
$past = $conn->query("SELECT id FROM events WHERE is_deleted = 0 AND event_date < CURDATE()")->num_rows;
$archived_count = $archived_result->num_rows;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Events Hub | Hope Haven Admin</title>
    <?php include 'includes/header_scripts.php'; ?>
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; scroll-behavior: smooth; }
        .glass-card { background: rgba(255, 255, 255, 0.9); backdrop-filter: blur(10px); }
        .custom-scrollbar::-webkit-scrollbar { width: 6px; }
        .custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background: #e2e8f0; border-radius: 10px; }
    </style>
</head>
<body class="bg-slate-50 min-h-screen">
    <?php include 'includes/admin_sidebar.php'; ?>
    <?php include 'includes/admin_topbar.php'; ?>

    <main id="main-wrapper" class="pt-[90px] px-8 pb-10 lg:ml-[280px] transition-all duration-300">
        <!-- Stats Section -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-10">
            <div class="bg-white p-6 rounded-[32px] border border-slate-100 shadow-sm shadow-slate-200/50">
                <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Total Activities</p>
                <h3 class="text-3xl font-black text-slate-900"><?php echo $total_count; ?></h3>
            </div>
            <div class="bg-white p-6 rounded-[32px] border border-slate-100 shadow-sm shadow-slate-200/50">
                <p class="text-[10px] font-black text-emerald-400 uppercase tracking-widest mb-2">Upcoming</p>
                <h3 class="text-3xl font-black text-emerald-600"><?php echo $upcoming; ?></h3>
            </div>
            <div class="bg-white p-6 rounded-[32px] border border-slate-100 shadow-sm shadow-slate-200/50">
                <p class="text-[10px] font-black text-blue-400 uppercase tracking-widest mb-2">Archived/Past</p>
                <h3 class="text-3xl font-black text-blue-600"><?php echo $past; ?></h3>
            </div>
        </div>

        <!-- Header Actions -->
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-10 gap-6">
            <div>
                <h1 class="text-3xl font-black text-slate-900 mb-1 tracking-tight">Hospital Calendar</h1>
                <p class="text-slate-500 font-medium text-sm">Organize outreach programs and internal activities.</p>
            </div>
            <div class="flex flex-col sm:flex-row gap-4 w-full md:w-auto">
                <div class="relative flex-1 sm:min-w-[300px]">
                    <input type="text" id="eventSearch" placeholder="Search events..." 
                           class="w-full pl-12 pr-4 py-3.5 bg-white border border-slate-200 rounded-2xl text-sm font-medium focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 transition-all outline-none">
                    <svg class="w-5 h-5 absolute left-4 top-1/2 -translate-y-1/2 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                </div>
                <button onclick="document.getElementById('addEventModal').classList.remove('hidden')" class="bg-blue-600 text-white px-8 py-3.5 rounded-2xl font-black text-sm hover:bg-blue-700 transition-all shadow-xl shadow-blue-200 flex items-center justify-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M12 4v16m8-8H4"></path></svg>
                    New Event
                </button>
            </div>
        </div>

        <?php if($message): ?>
            <div class="mb-8 p-5 bg-emerald-50 text-emerald-600 rounded-2xl font-bold text-sm border border-emerald-100 flex items-center gap-3">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <!-- Tabs -->
        <div class="flex gap-4 mb-6">
            <button onclick="switchTab('active')" id="tab-active" class="px-6 py-2 rounded-xl font-black text-xs uppercase tracking-widest transition-all bg-blue-600 text-white shadow-lg shadow-blue-100">Active (<?php echo $total_count; ?>)</button>
            <button onclick="switchTab('archived')" id="tab-archived" class="px-6 py-2 rounded-xl font-black text-xs uppercase tracking-widest transition-all bg-white text-slate-400 border border-slate-100">Archived (<?php echo $archived_count; ?>)</button>
        </div>

        <!-- Active Table -->
        <div id="section-active" class="bg-white rounded-[40px] shadow-xl shadow-slate-200/50 border border-slate-100 overflow-hidden">
            <div class="overflow-x-auto custom-scrollbar">
                <table class="w-full text-left border-collapse" id="eventsTable">
                    <thead>
                        <tr class="bg-slate-50/50 border-b border-slate-100">
                            <th class="px-8 py-6 text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">Activity Details</th>
                            <th class="px-8 py-6 text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">Schedule</th>
                            <th class="px-8 py-6 text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] text-center">Status</th>
                            <th class="px-8 py-6 text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50">
                        <?php if($active_result && $active_result->num_rows > 0): ?>
                            <?php while($row = $active_result->fetch_assoc()): 
                                $is_past = strtotime($row['event_date']) < strtotime(date('Y-m-d'));
                            ?>
                                <tr class="hover:bg-slate-50/30 transition-colors group">
                                    <td class="px-8 py-6">
                                        <div class="flex items-center gap-5">
                                            <div class="w-16 h-16 rounded-2xl overflow-hidden bg-slate-100 border border-slate-200 shrink-0">
                                                <?php if($row['image']): ?>
                                                    <img src="../assets/images/events/<?php echo $row['image']; ?>" class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500">
                                                <?php else: ?>
                                                    <div class="w-full h-full flex items-center justify-center text-slate-300">
                                                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                            <div>
                                                <p class="font-black text-slate-900 text-sm leading-tight"><?php echo htmlspecialchars($row['title']); ?></p>
                                                <p class="text-xs text-slate-500 font-medium mt-1 line-clamp-1 max-w-[250px]"><?php echo htmlspecialchars($row['description']); ?></p>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-8 py-6">
                                        <div class="flex flex-col">
                                            <span class="text-sm font-bold text-slate-900"><?php echo date('D, d M Y', strtotime($row['event_date'])); ?></span>
                                            <span class="text-[10px] text-slate-400 font-bold uppercase mt-0.5">Primary Schedule</span>
                                        </div>
                                    </td>
                                    <td class="px-8 py-6 text-center">
                                        <?php if($is_past): ?>
                                            <span class="px-3 py-1.5 rounded-full bg-slate-100 text-slate-500 text-[10px] font-black uppercase tracking-wider border border-slate-200">Archived</span>
                                        <?php else: ?>
                                            <span class="px-3 py-1.5 rounded-full bg-emerald-50 text-emerald-600 text-[10px] font-black uppercase tracking-wider border border-emerald-100">Live / Upcoming</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-8 py-6 text-right">
                                        <a href="?action=archive&id=<?php echo $row['id']; ?>" onclick="return confirm('Archive this event? It will be moved to the Archived tab.')" 
                                           class="inline-flex p-3 bg-amber-50 text-amber-600 rounded-xl hover:bg-amber-600 hover:text-white transition-all shadow-sm" title="Archive Event">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"></path></svg>
                                        </a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="4" class="px-8 py-32 text-center">
                                    <div class="flex flex-col items-center opacity-40">
                                        <svg class="w-16 h-16 text-slate-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                                        <p class="font-bold text-slate-500 italic">No events scheduled. Time to plan something big!</p>
                                    </div>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Archived Table -->
        <div id="section-archived" class="hidden bg-white rounded-[40px] shadow-xl shadow-slate-200/50 border border-slate-100 overflow-hidden">
            <div class="overflow-x-auto custom-scrollbar">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-slate-50/50 border-b border-slate-100">
                            <th class="px-8 py-6 text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">Activity Details</th>
                            <th class="px-8 py-6 text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">Schedule</th>
                            <th class="px-8 py-6 text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50">
                        <?php if($archived_result && $archived_result->num_rows > 0): ?>
                            <?php while($row = $archived_result->fetch_assoc()): ?>
                                <tr class="hover:bg-slate-50/30 transition-colors group">
                                    <td class="px-8 py-6">
                                        <div class="flex items-center gap-5">
                                            <div class="w-16 h-16 rounded-2xl overflow-hidden bg-slate-100 border border-slate-200 shrink-0 opacity-60">
                                                <?php if($row['image']): ?>
                                                    <img src="../assets/images/events/<?php echo $row['image']; ?>" class="w-full h-full object-cover grayscale">
                                                <?php endif; ?>
                                            </div>
                                            <div>
                                                <p class="font-black text-slate-400 text-sm leading-tight"><?php echo htmlspecialchars($row['title']); ?></p>
                                                <p class="text-xs text-slate-400 font-medium mt-1">Archived on <?php echo date('d M Y', strtotime($row['created_at'])); ?></p>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-8 py-6">
                                        <span class="text-sm font-bold text-slate-400"><?php echo date('D, d M Y', strtotime($row['event_date'])); ?></span>
                                    </td>
                                    <td class="px-8 py-6 text-right">
                                        <form method="POST" action="?action=restore&id=<?php echo $row['id']; ?>" class="flex justify-end items-center gap-3">
                                            <div class="flex flex-col items-start gap-1">
                                                <label class="text-[8px] font-black text-slate-400 uppercase tracking-widest ml-1">New Date</label>
                                                <input type="date" name="new_date" required class="bg-slate-50 border border-slate-200 rounded-lg px-2 py-1 text-[10px] font-bold outline-none focus:border-blue-400 transition-all">
                                            </div>
                                            <div class="flex gap-2 mt-3">
                                                <button type="submit" class="p-3 bg-emerald-50 text-emerald-600 rounded-xl hover:bg-emerald-600 hover:text-white transition-all shadow-sm" title="Restore to Live Site">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
                                                </button>
                                                <a href="?action=delete&id=<?php echo $row['id']; ?>" onclick="return confirm('PERMANENTLY DELETE this event? This action cannot be undone.')" 
                                                   class="p-3 bg-rose-50 text-rose-500 rounded-xl hover:bg-rose-500 hover:text-white transition-all shadow-sm" title="Delete Permanently">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                                </a>
                                            </div>
                                        </form>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="3" class="px-8 py-20 text-center text-slate-400 font-bold italic">Archive is empty.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <!-- Modal -->
    <div id="addEventModal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-md z-[100] hidden flex items-center justify-center p-6">
        <div class="bg-white max-w-xl w-full rounded-[48px] shadow-2xl overflow-hidden p-8 md:p-12 animate-in fade-in zoom-in duration-300">
            <div class="flex justify-between items-center mb-10">
                <h2 class="text-2xl font-black text-slate-900 tracking-tight">Create Activity</h2>
                <button onclick="document.getElementById('addEventModal').classList.add('hidden')" class="p-2 text-slate-400 hover:text-slate-600 transition-colors">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            </div>

            <form method="POST" enctype="multipart/form-data" class="space-y-6">
                <div class="space-y-2">
                    <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-1">Event Name</label>
                    <input type="text" name="title" required class="w-full bg-slate-50 border border-slate-200 rounded-2xl px-6 py-4 text-slate-900 font-bold focus:ring-4 focus:ring-blue-500/5 focus:border-blue-500 outline-none transition-all" placeholder="e.g. Annual Blood Drive">
                </div>
                <div class="space-y-2">
                    <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-1">Narrative</label>
                    <textarea name="description" required rows="3" class="w-full bg-slate-50 border border-slate-200 rounded-2xl px-6 py-4 text-slate-900 font-bold focus:ring-4 focus:ring-blue-500/5 focus:border-blue-500 outline-none transition-all" placeholder="Briefly describe the activity..."></textarea>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="space-y-2">
                        <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-1">Event Date</label>
                        <input type="date" name="event_date" required class="w-full bg-slate-50 border border-slate-200 rounded-2xl px-6 py-4 text-slate-900 font-bold focus:ring-4 focus:ring-blue-500/5 focus:border-blue-500 outline-none transition-all">
                    </div>
                    <div class="space-y-2">
                        <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-1">Cover Image</label>
                        <input type="file" name="image" accept="image/*" class="w-full bg-slate-50 border border-slate-200 rounded-2xl px-4 py-3 text-xs text-slate-500 cursor-pointer">
                    </div>
                </div>
                <button type="submit" name="add_event" class="w-full py-5 bg-slate-900 text-white rounded-3xl font-black text-sm tracking-[0.2em] uppercase hover:bg-blue-600 transition-all shadow-xl shadow-slate-200">
                    Publish Activity
                </button>
            </form>
        </div>
    </div>

    <script>
        function switchTab(type) {
            const activeSec = document.getElementById('section-active');
            const archivedSec = document.getElementById('section-archived');
            const activeTab = document.getElementById('tab-active');
            const archivedTab = document.getElementById('tab-archived');

            if (type === 'active') {
                activeSec.classList.remove('hidden');
                archivedSec.classList.add('hidden');
                activeTab.className = 'px-6 py-2 rounded-xl font-black text-xs uppercase tracking-widest transition-all bg-blue-600 text-white shadow-lg shadow-blue-100';
                archivedTab.className = 'px-6 py-2 rounded-xl font-black text-xs uppercase tracking-widest transition-all bg-white text-slate-400 border border-slate-100';
            } else {
                activeSec.classList.add('hidden');
                archivedSec.classList.remove('hidden');
                archivedTab.className = 'px-6 py-2 rounded-xl font-black text-xs uppercase tracking-widest transition-all bg-blue-600 text-white shadow-lg shadow-blue-100';
                activeTab.className = 'px-6 py-2 rounded-xl font-black text-xs uppercase tracking-widest transition-all bg-white text-slate-400 border border-slate-100';
            }
        }

        // Real-time Search
        document.getElementById('eventSearch').addEventListener('input', function(e) {
            const term = e.target.value.toLowerCase();
            const rows = document.querySelectorAll('#eventsTable tbody tr');
            rows.forEach(row => {
                const text = row.innerText.toLowerCase();
                row.style.display = text.includes(term) ? '' : 'none';
            });
        });
    </script>
</body>
</html>