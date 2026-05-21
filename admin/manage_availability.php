<?php
session_start();
require_once '../includes/db_connect.php';

if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: index.php');
    exit;
}

// Handle Add Availability Rule
if (isset($_POST['add_rule'])) {
    $doctor_ids = $_POST['doctor_ids'] ?? [];
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];
    $start_time = $_POST['start_time'];
    $end_time = $_POST['end_time'];
    $slot_duration = intval($_POST['slot_duration'] ?? 30);
    $days = isset($_POST['days']) ? implode(',', $_POST['days']) : '';

    if (!empty($doctor_ids)) {
        $stmt = $conn->prepare("INSERT INTO doctor_availability_rules (doctor_id, start_date, end_date, days_of_week, start_time, end_time, slot_duration) VALUES (?, ?, ?, ?, ?, ?, ?)");
        
        foreach ($doctor_ids as $doctor_id) {
            $doctor_id = intval($doctor_id);
            $stmt->bind_param("isssssi", $doctor_id, $start_date, $end_date, $days, $start_time, $end_time, $slot_duration);
            $stmt->execute();
        }
        header('Location: manage_availability.php?success=1');
        exit;
    } else {
        $error = "Please select at least one doctor.";
    }
}

// Handle Delete Rule
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $stmt = $conn->prepare("DELETE FROM doctor_availability_rules WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    header("Location: manage_availability.php?deleted=1");
    exit;
}

// Fetch Data
$doctors = $conn->query("SELECT id, name FROM doctors ORDER BY name ASC");
if (!$doctors) {
    die("Database Error: " . $conn->error . ". Please make sure the 'doctors' table exists.");
}

$rules = $conn->query("SELECT r.*, d.name as doctor_name FROM doctor_availability_rules r JOIN doctors d ON r.doctor_id = d.id ORDER BY r.start_date DESC");

if (!$rules) {
    // If rules query fails, it's likely the table is missing
    $error = "The 'doctor_availability_rules' table is missing. <a href='../db_migration_availability.php' class='underline'>Click here to run the migration script</a>.";
}

$dayNames = [0 => "Sun", 1 => "Mon", 2 => "Tue", 3 => "Wed", 4 => "Thu", 5 => "Fri", 6 => "Sat"];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Availability Scheduler | Hope Haven Admin</title>
    <?php include 'includes/header_scripts.php'; ?>
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
        
        .custom-scrollbar::-webkit-scrollbar {
            width: 6px;
        }
        .custom-scrollbar::-webkit-scrollbar-track {
            background: #f1f5f9;
            border-radius: 10px;
        }
        .custom-scrollbar::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 10px;
        }
        .custom-scrollbar::-webkit-scrollbar-thumb:hover {
            background: #94a3b8;
        }

        @keyframes zoomIn {
            from { opacity: 0; transform: scale(0.95); }
            to { opacity: 1; transform: scale(1); }
        }
        .animate-zoom-in {
            animation: zoomIn 0.3s ease-out forwards;
        }
    </style>
</head>
<body class="bg-slate-50 min-h-screen">
    <?php include 'includes/admin_sidebar.php'; ?>
    <?php include 'includes/admin_topbar.php'; ?>

    <main id="main-wrapper" class="pt-[90px] px-8 pb-10 lg:ml-[280px] transition-all duration-300">
        <div class="flex justify-between items-end mb-10">
            <div>
                <h1 class="text-4xl font-black text-slate-900 tracking-tight mb-2">Advanced Scheduler</h1>
                <p class="text-slate-500 font-medium">Define custom date ranges and time blocks for your medical staff.</p>
            </div>
            <button onclick="openModal()" class="px-8 py-4 bg-blue-600 text-white rounded-3xl font-black text-sm hover:bg-blue-700 transition-all shadow-xl shadow-blue-200">
                + New Schedule Rule
            </button>
        </div>

        <?php if(isset($error)): ?>
            <div class="mb-8 p-6 bg-rose-50 border border-rose-100 text-rose-600 rounded-3xl font-bold"><?php echo $error; ?></div>
        <?php endif; ?>

        <!-- Active Rules Grid -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            <?php if ($rules && $rules->num_rows > 0): ?>
                <?php while($row = $rules->fetch_assoc()): ?>
                    <div class="bg-white p-8 rounded-[40px] shadow-sm border border-slate-100 relative group overflow-hidden">
                        <div class="absolute top-0 right-0 p-6">
                            <a href="?delete=<?php echo $row['id']; ?>" onclick="return confirm('Delete this rule?')" class="p-3 bg-rose-50 text-rose-400 hover:bg-rose-600 hover:text-white rounded-2xl transition-all">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                            </a>
                        </div>
                        
                        <div class="flex items-center gap-6 mb-8">
                            <div class="w-16 h-16 bg-blue-50 text-blue-600 rounded-3xl flex items-center justify-center font-black text-2xl">
                                <?php echo substr($row['doctor_name'], 0, 1); ?>
                            </div>
                            <div>
                                <h3 class="text-xl font-black text-slate-900"><?php echo htmlspecialchars($row['doctor_name']); ?></h3>
                                <p class="text-xs font-bold text-slate-400 uppercase tracking-widest mt-1">Rule ID: #<?php echo $row['id']; ?></p>
                            </div>
                        </div>

                        <div class="space-y-6">
                            <div class="flex gap-4">
                                <div class="flex-1 bg-slate-50 p-4 rounded-3xl border border-slate-100">
                                    <p class="text-[10px] font-black text-slate-400 uppercase mb-2">Date Range</p>
                                    <p class="text-sm font-bold text-slate-700"><?php echo date('M d', strtotime($row['start_date'])); ?> &mdash; <?php echo date('M d, Y', strtotime($row['end_date'])); ?></p>
                                </div>
                                <div class="flex-1 bg-slate-50 p-4 rounded-3xl border border-slate-100">
                                    <p class="text-[10px] font-black text-slate-400 uppercase mb-2">Time Block</p>
                                    <p class="text-sm font-bold text-blue-600"><?php echo date('h:i A', strtotime($row['start_time'])); ?> - <?php echo date('h:i A', strtotime($row['end_time'])); ?></p>
                                </div>
                            </div>

                            <div class="bg-slate-900 p-6 rounded-3xl text-white">
                                <p class="text-[10px] font-black text-blue-400 uppercase mb-3 tracking-widest">Active Days</p>
                                <div class="flex gap-2">
                                    <?php 
                                    $activeDays = explode(',', $row['days_of_week']);
                                    foreach($dayNames as $id => $name): 
                                        $isActive = in_array($id, $activeDays);
                                    ?>
                                        <span class="w-10 h-10 flex items-center justify-center rounded-xl text-[10px] font-black <?php echo $isActive ? 'bg-blue-600 text-white' : 'bg-slate-800 text-slate-600'; ?>">
                                            <?php echo $name; ?>
                                        </span>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="col-span-full py-20 text-center bg-white rounded-[60px] border-2 border-dashed border-slate-200">
                    <div class="w-20 h-20 bg-slate-50 text-slate-300 rounded-full flex items-center justify-center mx-auto mb-6">
                        <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                    </div>
                    <p class="text-slate-400 font-bold uppercase tracking-widest">No active scheduling rules found</p>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <!-- Range Selector Modal -->
    <div id="ruleModal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-md z-[100] hidden flex items-center justify-center p-4">
        <div class="bg-white rounded-[40px] md:rounded-[50px] w-full max-w-2xl shadow-2xl animate-zoom-in flex flex-col max-h-[90vh] overflow-hidden">
            <!-- Modal Header -->
            <div class="p-8 md:p-12 pb-4 md:pb-6">
                <h3 class="text-2xl md:text-3xl font-black text-slate-900 tracking-tight">Set Availability Rule</h3>
            </div>

            <!-- Modal Body (Scrollable) -->
            <div class="flex-1 overflow-y-auto px-8 md:px-12 py-4 custom-scrollbar">
                <form id="availabilityForm" method="POST" class="space-y-8">
                    <div class="space-y-4">
                        <div class="flex justify-between items-center">
                            <label class="text-[11px] font-black text-slate-400 uppercase tracking-widest ml-1">Doctor Selection</label>
                            <button type="button" onclick="selectAllDoctors()" class="text-[10px] font-bold text-blue-600 uppercase hover:underline">Select All</button>
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 max-h-48 overflow-y-auto p-4 bg-slate-50 rounded-3xl custom-scrollbar" id="doctorList">
                            <?php $doctors->data_seek(0); while($doc = $doctors->fetch_assoc()): ?>
                                <label class="flex items-center gap-3 p-3 bg-white rounded-2xl border border-slate-100 cursor-pointer hover:border-blue-300 transition-all">
                                    <input type="checkbox" name="doctor_ids[]" value="<?php echo $doc['id']; ?>" class="w-5 h-5 rounded-lg border-slate-200 text-blue-600 focus:ring-blue-500">
                                    <span class="text-sm font-bold text-slate-700"><?php echo htmlspecialchars($doc['name']); ?></span>
                                </label>
                            <?php endwhile; ?>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 md:gap-8">
                        <div class="space-y-2">
                            <label class="text-[11px] font-black text-slate-400 uppercase tracking-widest ml-1">Slot Duration</label>
                            <select name="slot_duration" class="w-full bg-slate-50 border-0 rounded-3xl px-6 py-5 text-slate-900 font-bold focus:ring-2 focus:ring-blue-600 outline-none">
                                <option value="15">15 Minutes</option>
                                <option value="30" selected>30 Minutes</option>
                                <option value="60">1 Hour</option>
                            </select>
                        </div>
                        <div class="space-y-2">
                            <label class="text-[11px] font-black text-slate-400 uppercase tracking-widest ml-1">Start Date</label>
                            <input type="date" name="start_date" min="<?php echo date('Y-m-d'); ?>" required class="w-full bg-slate-50 border-0 rounded-3xl px-6 py-5 font-bold">
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 md:gap-8">
                        <div class="space-y-2">
                            <label class="text-[11px] font-black text-slate-400 uppercase tracking-widest ml-1">End Date</label>
                            <input type="date" name="end_date" required class="w-full bg-slate-50 border-0 rounded-3xl px-6 py-5 font-bold">
                        </div>
                        <div class="space-y-2">
                            <label class="text-[11px] font-black text-slate-400 uppercase tracking-widest ml-1">Start Time</label>
                            <input type="time" name="start_time" required class="w-full bg-slate-50 border-0 rounded-3xl px-6 py-5 font-bold">
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 md:gap-8">
                        <div class="space-y-2">
                            <label class="text-[11px] font-black text-slate-400 uppercase tracking-widest ml-1">End Time</label>
                            <input type="time" name="end_time" required class="w-full bg-slate-50 border-0 rounded-3xl px-6 py-5 font-bold">
                        </div>
                    </div>

                    <div class="space-y-4">
                        <label class="text-[11px] font-black text-slate-400 uppercase tracking-widest ml-1">Select Days Active</label>
                        <div class="grid grid-cols-4 md:flex md:flex-wrap gap-2 md:gap-3">
                            <?php foreach($dayNames as $id => $name): ?>
                                <label class="cursor-pointer">
                                    <input type="checkbox" name="days[]" value="<?php echo $id; ?>" checked class="hidden peer">
                                    <div class="p-3 md:p-4 rounded-xl md:rounded-2xl bg-slate-50 border-2 border-transparent text-center font-black text-[10px] md:text-xs text-slate-400 peer-checked:bg-blue-600 peer-checked:text-white peer-checked:border-blue-400 transition-all">
                                        <?php echo $name; ?>
                                    </div>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    
                    <input type="hidden" name="add_rule" value="1">
                </form>
            </div>

            <!-- Modal Footer -->
            <div class="p-8 md:p-12 pt-4 md:pt-6 border-t border-slate-50 flex flex-col md:flex-row gap-4">
                <button type="submit" form="availabilityForm" class="flex-1 py-5 md:py-6 bg-slate-900 text-white rounded-[24px] md:rounded-[32px] font-black text-sm uppercase tracking-widest hover:bg-blue-600 transition-all shadow-xl shadow-blue-200">
                    Activate Rule
                </button>
                <button type="button" onclick="closeModal()" class="px-10 py-5 md:py-6 bg-slate-100 text-slate-400 rounded-[24px] md:rounded-[32px] font-black text-xs uppercase hover:text-slate-600 transition-all">
                    Cancel
                </button>
            </div>
        </div>
    </div>

    <script>
        const modal = document.getElementById('ruleModal');
        
        function openModal() {
            modal.classList.remove('hidden');
            document.body.style.overflow = 'hidden';
        }

        function closeModal() {
            modal.classList.add('hidden');
            document.body.style.overflow = '';
        }

        function selectAllDoctors() {
            const checkboxes = document.querySelectorAll('#doctorList input[type="checkbox"]');
            const allChecked = Array.from(checkboxes).every(cb => cb.checked);
            checkboxes.forEach(cb => cb.checked = !allChecked);
        }

        // Close on backdrop click
        modal.addEventListener('click', (e) => {
            if (e.target === modal) closeModal();
        });

        // Close on ESC key
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && !modal.classList.contains('hidden')) {
                closeModal();
            }
        });

        // Prevention of form resubmission on refresh
        if (window.history.replaceState) {
            window.history.replaceState(null, null, window.location.href);
        }
    </script>
</body>
</html>