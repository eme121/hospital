<?php
session_start();
require_once '../includes/db_connect.php';

if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: index.php');
    exit;
}

$message = "";
$error = "";

// Handle Add/Edit Service
if (isset($_POST['save_service'])) {
    $id = isset($_POST['service_id']) ? intval($_POST['service_id']) : 0;
    $name = strip_tags($_POST['name']);
    $desc = strip_tags($_POST['description']);
    $dept_id = intval($_POST['department_id']);
    $icon = $_POST['icon'];

    if ($id > 0) {
        $stmt = $conn->prepare("UPDATE services SET name=?, description=?, department_id=?, icon=? WHERE id=?");
        $stmt->bind_param("ssisi", $name, $desc, $dept_id, $icon, $id);
    } else {
        $stmt = $conn->prepare("INSERT INTO services (name, description, department_id, icon) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssis", $name, $desc, $dept_id, $icon);
    }

    if ($stmt->execute()) {
        $message = "Service configuration saved successfully!";
    } else {
        $error = "Database error.";
    }
}

// Handle Status Toggle
if (isset($_GET['toggle'])) {
    $id = intval($_GET['toggle']);
    $stmt = $conn->prepare("UPDATE services SET is_active = NOT is_active WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    header("Location: manage_services.php?updated=1");
    exit;
}

// Fetch Services
$services = $conn->query("SELECT s.*, d.name as department_name FROM services s LEFT JOIN departments d ON s.department_id = d.id ORDER BY d.name ASC, s.name ASC");
if (!$services) {
    die("Database Error (services): " . $conn->error . ". Please ensure 'services' table exists.");
}

$depts = $conn->query("SELECT * FROM departments ORDER BY name ASC");
if (!$depts) {
    die("Database Error (departments): " . $conn->error . ". Please ensure 'departments' table exists.");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Services | Hope Haven Admin</title>
    <?php include 'includes/header_scripts.php'; ?>
    <style>body { font-family: 'Plus Jakarta Sans', sans-serif; }</style>
</head>
<body class="bg-slate-50 min-h-screen">
    <?php include 'includes/admin_sidebar.php'; ?>
    <?php include 'includes/admin_topbar.php'; ?>

    <main id="main-wrapper" class="pt-[90px] px-8 pb-10 lg:ml-[280px] transition-all duration-300">
        <div class="flex justify-between items-center mb-10">
            <div>
                <h1 class="text-3xl font-black text-slate-900 tracking-tight">Clinical Services</h1>
                <p class="text-slate-500 font-medium text-sm">Configure the medical departments and offerings shown on the website.</p>
            </div>
            <button onclick="openModal()" class="bg-blue-600 text-white px-8 py-3.5 rounded-2xl font-black text-sm shadow-xl shadow-blue-200">
                Create New Service
            </button>
        </div>

        <div class="bg-white rounded-[32px] shadow-xl border border-slate-100 overflow-hidden">
            <table class="w-full text-left">
                <thead class="bg-slate-50 border-b border-slate-100">
                    <tr>
                        <th class="px-8 py-5 text-[10px] font-black text-slate-400 uppercase tracking-widest">Service Name</th>
                        <th class="px-8 py-5 text-[10px] font-black text-slate-400 uppercase tracking-widest">Department</th>
                        <th class="px-8 py-5 text-[10px] font-black text-slate-400 uppercase tracking-widest">Visibility</th>
                        <th class="px-8 py-5 text-[10px] font-black text-slate-400 uppercase tracking-widest text-right">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                    <?php while($row = $services->fetch_assoc()): ?>
                        <tr class="hover:bg-slate-50/50 transition-colors">
                            <td class="px-8 py-6">
                                <div class="flex items-center gap-4">
                                    <div class="w-10 h-10 bg-blue-50 text-blue-600 rounded-xl flex items-center justify-center font-bold text-lg"><?php echo $row['icon']; ?></div>
                                    <span class="font-bold text-slate-900"><?php echo htmlspecialchars($row['name']); ?></span>
                                </div>
                            </td>
                            <td class="px-8 py-6 text-sm font-bold text-slate-500"><?php echo htmlspecialchars($row['department_name'] ?? 'General'); ?></td>
                            <td class="px-8 py-6">
                                <a href="?toggle=<?php echo $row['id']; ?>" class="px-3 py-1.5 rounded-full text-[10px] font-black uppercase tracking-wider <?php echo $row['is_active'] ? 'bg-emerald-50 text-emerald-600' : 'bg-slate-100 text-slate-400'; ?>">
                                    <?php echo $row['is_active'] ? 'Published' : 'Hidden'; ?>
                                </a>
                            </td>
                            <td class="px-8 py-6 text-right">
                                <button onclick='openModal(<?php echo json_encode($row); ?>)' class="p-2 text-blue-600 hover:bg-blue-50 rounded-lg">Edit</button>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </main>

    <!-- Department Fees Section -->
    <section class="px-8 pb-10 lg:ml-[280px] transition-all duration-300">
        <div class="mb-6">
            <h2 class="text-2xl font-black text-slate-800 tracking-tight">Department Consultation Fees</h2>
            <p class="text-slate-500 font-medium text-sm">Update the physical visit fees for each medical department.</p>
        </div>
        
        <div class="bg-white rounded-[32px] shadow-xl border border-slate-100 overflow-hidden max-w-4xl">
            <table class="w-full text-left">
                <thead class="bg-slate-50 border-b border-slate-100">
                    <tr>
                        <th class="px-8 py-5 text-[10px] font-black text-slate-400 uppercase tracking-widest">Department</th>
                        <th class="px-8 py-5 text-[10px] font-black text-slate-400 uppercase tracking-widest">Current Fee (₦)</th>
                        <th class="px-8 py-5 text-[10px] font-black text-slate-400 uppercase tracking-widest text-right">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                    <?php 
                    $depts->data_seek(0);
                    while($d = $depts->fetch_assoc()): 
                    ?>
                        <tr class="hover:bg-slate-50/50 transition-colors">
                            <td class="px-8 py-6 font-bold text-slate-900"><?php echo htmlspecialchars($d['name']); ?></td>
                            <td class="px-8 py-6">
                                <input type="number" id="fee_<?php echo $d['id']; ?>" value="<?php echo number_format($d['consultation_fee'], 0, '.', ''); ?>" 
                                       class="bg-slate-50 border border-slate-200 rounded-xl px-4 py-2 font-bold text-sm w-32 outline-none focus:ring-2 focus:ring-blue-500/20 transition-all">
                            </td>
                            <td class="px-8 py-6 text-right">
                                <button onclick="updateDeptFee(<?php echo $d['id']; ?>)" class="bg-slate-900 text-white px-6 py-2 rounded-xl font-black text-[10px] uppercase tracking-widest hover:bg-blue-600 transition-all shadow-lg shadow-slate-200">
                                    Update Fee
                                </button>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </section>

    <!-- Service Modal -->
    <div id="serviceModal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-md z-50 hidden flex items-center justify-center p-6">
        <div class="bg-white max-w-xl w-full rounded-[40px] p-10 shadow-2xl">
            <h2 id="modalTitle" class="text-2xl font-black text-slate-900 mb-8">Clinical Service Configuration</h2>
            <form method="POST" class="space-y-6">
                <input type="hidden" name="service_id" id="service_id">
                <div class="grid grid-cols-2 gap-4">
                    <div class="space-y-2">
                        <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Service Name</label>
                        <input type="text" name="name" id="service_name" required class="w-full bg-slate-50 border border-slate-200 rounded-2xl px-4 py-3 font-bold text-sm">
                    </div>
                    <div class="space-y-2">
                        <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Icon Emoji</label>
                        <input type="text" name="icon" id="service_icon" placeholder="e.g. 🩺" class="w-full bg-slate-50 border border-slate-200 rounded-2xl px-4 py-3 text-center font-bold text-lg">
                    </div>
                </div>
                <div class="space-y-2">
                    <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Department</label>
                    <select name="department_id" id="service_dept" class="w-full bg-slate-50 border border-slate-200 rounded-2xl px-4 py-3 font-bold text-sm">
                        <?php 
                        $depts->data_seek(0);
                        while($d = $depts->fetch_assoc()): 
                        ?>
                            <option value="<?php echo $d['id']; ?>"><?php echo htmlspecialchars($d['name']); ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="space-y-2">
                    <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Marketing Description</label>
                    <textarea name="description" id="service_desc" rows="4" required class="w-full bg-slate-50 border border-slate-200 rounded-2xl px-4 py-3 font-medium text-sm"></textarea>
                </div>
                <div class="flex gap-4 pt-4">
                    <button type="button" onclick="closeModal()" class="flex-1 py-4 bg-slate-100 text-slate-600 rounded-2xl font-black text-xs uppercase tracking-widest">Cancel</button>
                    <button type="submit" name="save_service" class="flex-1 py-4 bg-blue-600 text-white rounded-2xl font-black text-xs uppercase tracking-widest shadow-lg shadow-blue-200">Save Configuration</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openModal(data = null) {
            const modal = document.getElementById('serviceModal');
            if (data) {
                document.getElementById('service_id').value = data.id;
                document.getElementById('service_name').value = data.name;
                document.getElementById('service_icon').value = data.icon;
                document.getElementById('service_dept').value = data.department_id;
                document.getElementById('service_desc').value = data.description;
                document.getElementById('modalTitle').innerText = "Edit Service";
            } else {
                document.getElementById('service_id').value = "";
                document.getElementById('service_name').value = "";
                document.getElementById('service_icon').value = "🩺";
                document.getElementById('service_desc').value = "";
                document.getElementById('modalTitle').innerText = "Create Service";
            }
            modal.classList.remove('hidden');
        }
        function closeModal() { document.getElementById('serviceModal').classList.add('hidden'); }

        async function updateDeptFee(deptId) {
            const feeInput = document.getElementById(`fee_${deptId}`);
            const fee = feeInput.value;
            const btn = event.currentTarget;
            const originalText = btn.innerText;

            btn.disabled = true;
            btn.innerText = 'UPDATING...';

            try {
                const response = await fetch('api/update_department_fees.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `department_id=${deptId}&consultation_fee=${fee}`
                });
                const data = await response.json();
                if (data.success) {
                    alert('Department fee updated successfully!');
                } else {
                    alert('Error: ' + data.message);
                }
            } catch (err) {
                console.error(err);
                alert('Connection failed.');
            } finally {
                btn.disabled = false;
                btn.innerText = originalText;
            }
        }
    </script>
</body>
</html>