<?php
session_start();
if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: index.php');
    exit;
}
require_once '../includes/db_connect.php';

// Fetch Staff (Unified View)
$doctors = $conn->query("SELECT id, name, email, 'Doctor' as role, department_id FROM telemedicine_doctors");
$pharmacists = $conn->query("SELECT id, name, email, 'Pharmacist' as role, NULL as department_id FROM pharmacists");
$lab_techs = $conn->query("SELECT id, name, email, 'Lab Technician' as role, NULL as department_id FROM lab_technicians");
$nurses = $conn->query("SELECT id, name, email, 'Nurse' as role, department_id FROM nurses");

$all_staff = [];
while($r = $doctors->fetch_assoc()) $all_staff[] = $r;
while($r = $pharmacists->fetch_assoc()) $all_staff[] = $r;
while($r = $lab_techs->fetch_assoc()) $all_staff[] = $r;
while($r = $nurses->fetch_assoc()) $all_staff[] = $r;

// Fetch Leave Requests
$leave_requests = $conn->query("SELECT l.*, 
    CASE 
        WHEN l.staff_type = 'Doctor' THEN (SELECT name FROM telemedicine_doctors WHERE id = l.staff_id)
        WHEN l.staff_type = 'Pharmacist' THEN (SELECT name FROM pharmacists WHERE id = l.staff_id)
        WHEN l.staff_type = 'Lab Technician' THEN (SELECT name FROM lab_technicians WHERE id = l.staff_id)
        WHEN l.staff_type = 'Nurse' THEN (SELECT name FROM nurses WHERE id = l.staff_id)
    END as staff_name
    FROM leave_requests l ORDER BY applied_at DESC");

// Fetch Today's Roster
$roster = $conn->query("SELECT r.*, 
    CASE 
        WHEN r.staff_type = 'Doctor' THEN (SELECT name FROM telemedicine_doctors WHERE id = r.staff_id)
        WHEN r.staff_type = 'Pharmacist' THEN (SELECT name FROM pharmacists WHERE id = r.staff_id)
        WHEN r.staff_type = 'Lab Technician' THEN (SELECT name FROM lab_technicians WHERE id = r.staff_id)
        WHEN r.staff_type = 'Nurse' THEN (SELECT name FROM nurses WHERE id = r.staff_id)
    END as staff_name
    FROM staff_roster r WHERE shift_date = CURDATE()");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Staff & HR Management | Hope Haven Admin</title>
    <?php include 'includes/header_scripts.php'; ?>
    <style>body { font-family: 'Plus Jakarta Sans', sans-serif; }</style>
</head>
<body class="bg-slate-50 min-h-screen">
    <?php include 'includes/admin_sidebar.php'; ?>
    <?php include 'includes/admin_topbar.php'; ?>

    <main id="main-wrapper" class="pt-[90px] px-8 pb-10 lg:ml-[280px] transition-all duration-300">
        <header class="mb-12 flex justify-between items-center">
            <div>
                <h1 class="text-3xl font-black text-slate-900 tracking-tight">Staff & HR Management</h1>
                <p class="text-slate-500 font-medium">Oversee personnel, rosters, and leave approvals.</p>
            </div>
            <div class="flex gap-4">
                <button onclick="openStaffModal()" class="bg-blue-600 text-white px-8 py-4 rounded-2xl font-black text-sm hover:bg-blue-700 transition-all shadow-xl shadow-blue-200">
                    Add Staff Member
                </button>
                <button onclick="document.getElementById('shiftModal').classList.remove('hidden'); document.getElementById('shiftModal').classList.add('flex');" class="bg-white border border-slate-200 text-slate-600 px-8 py-4 rounded-2xl font-black text-sm hover:bg-slate-50 transition-all">
                    Assign New Shift
                </button>
            </div>
        </header>

        <div class="grid lg:grid-cols-3 gap-8">
            <div class="lg:col-span-2 space-y-8">
                <!-- Personnel Directory -->
                <div class="bg-white rounded-[40px] shadow-xl border border-slate-100 overflow-hidden">
                    <div class="p-8 border-b border-slate-50 bg-slate-50/50">
                        <h3 class="text-xl font-black text-slate-900">Personnel Directory</h3>
                    </div>
                    <table class="w-full text-left">
                        <thead class="bg-slate-50/30">
                            <tr>
                                <th class="px-8 py-5 text-[10px] font-black text-slate-400 uppercase tracking-widest">Name</th>
                                <th class="px-8 py-5 text-[10px] font-black text-slate-400 uppercase tracking-widest">Role</th>
                                <th class="px-8 py-5 text-[10px] font-black text-slate-400 uppercase tracking-widest text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-50">
                            <?php foreach($all_staff as $staff): ?>
                            <tr class="hover:bg-slate-50/50 transition-colors">
                                <td class="px-8 py-6">
                                    <p class="font-bold text-slate-900"><?php echo htmlspecialchars($staff['name']); ?></p>
                                    <p class="text-[10px] text-slate-400 font-medium"><?php echo $staff['email']; ?></p>
                                </td>
                                <td class="px-8 py-6">
                                    <span class="px-3 py-1 bg-blue-50 text-blue-600 text-[10px] font-black uppercase rounded-full"><?php echo $staff['role']; ?></span>
                                </td>
                                <td class="px-8 py-6 text-right">
                                    <div class="flex justify-end gap-2">
                                        <button onclick='editStaff(<?php echo json_encode($staff); ?>)' class="p-2 bg-slate-100 text-slate-600 rounded-lg hover:bg-blue-600 hover:text-white transition-all">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path></svg>
                                        </button>
                                        <button onclick="deleteStaff(<?php echo $staff['id']; ?>, '<?php echo $staff['role']; ?>')" class="p-2 bg-slate-100 text-rose-500 rounded-lg hover:bg-rose-500 hover:text-white transition-all">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Roster -->
                <div class="bg-white rounded-[40px] shadow-xl border border-slate-100 overflow-hidden">
                    <div class="p-8 border-b border-slate-50 bg-blue-50/30">
                        <h3 class="text-xl font-black text-slate-900">Today's Duty Roster</h3>
                    </div>
                    <div class="p-8 grid md:grid-cols-2 gap-6">
                        <?php if($roster->num_rows == 0): ?>
                            <p class="col-span-2 text-center py-10 text-slate-400 font-bold italic">No shifts assigned for today.</p>
                        <?php else: ?>
                            <?php while($shift = $roster->fetch_assoc()): ?>
                            <div class="p-6 bg-slate-50 rounded-3xl border border-slate-100 flex items-center gap-4">
                                <div class="w-12 h-12 bg-white rounded-2xl flex items-center justify-center text-blue-600 shadow-sm font-black">
                                    <?php echo substr($shift['staff_type'], 0, 1); ?>
                                </div>
                                <div>
                                    <p class="font-black text-slate-900"><?php echo htmlspecialchars($shift['staff_name']); ?></p>
                                    <p class="text-[10px] font-black text-blue-600 uppercase tracking-widest"><?php echo $shift['shift_time']; ?></p>
                                </div>
                            </div>
                            <?php endwhile; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Leave Requests -->
            <div class="space-y-8">
                <div class="bg-white rounded-[40px] shadow-xl border border-slate-100 overflow-hidden">
                    <div class="p-8 border-b border-slate-50 bg-rose-50/30">
                        <h3 class="text-xl font-black text-slate-900">Leave Applications</h3>
                    </div>
                    <div class="p-8 space-y-6">
                        <?php if($leave_requests->num_rows == 0): ?>
                            <p class="text-center py-10 text-slate-400 font-bold italic">No pending requests.</p>
                        <?php else: ?>
                            <?php while($leave = $leave_requests->fetch_assoc()): ?>
                            <div class="p-6 bg-slate-50 rounded-[32px] border border-slate-100 relative group">
                                <div class="flex justify-between items-start mb-4">
                                    <div>
                                        <p class="font-black text-slate-900"><?php echo htmlspecialchars($leave['staff_name']); ?></p>
                                        <p class="text-[9px] font-black text-slate-400 uppercase"><?php echo $leave['staff_type']; ?> &bull; <?php echo $leave['leave_type']; ?></p>
                                    </div>
                                    <span class="px-3 py-1 rounded-full text-[8px] font-black uppercase tracking-widest <?php echo $leave['status'] == 'Approved' ? 'bg-emerald-50 text-emerald-600' : ($leave['status'] == 'Rejected' ? 'bg-rose-50 text-rose-600' : 'bg-amber-50 text-amber-600'); ?>">
                                        <?php echo $leave['status']; ?>
                                    </span>
                                </div>
                                <div class="text-[10px] font-bold text-slate-500 mb-4">
                                    <?php echo date('d M', strtotime($leave['start_date'])); ?> - <?php echo date('d M Y', strtotime($leave['end_date'])); ?>
                                </div>
                                <?php if($leave['status'] == 'Pending'): ?>
                                <div class="flex gap-2">
                                    <a href="../api/hr_api.php?action=update_leave&leave_id=<?php echo $leave['id']; ?>&status=Approved" class="flex-1 py-2 bg-emerald-600 text-white text-[9px] font-black uppercase rounded-xl text-center">Approve</a>
                                    <a href="../api/hr_api.php?action=update_leave&leave_id=<?php echo $leave['id']; ?>&status=Rejected" class="flex-1 py-2 bg-slate-200 text-slate-600 text-[9px] font-black uppercase rounded-xl text-center">Reject</a>
                                </div>
                                <?php endif; ?>
                            </div>
                            <?php endwhile; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Staff Modal (COMPACT DESIGN) -->
    <div id="staffModal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-[100] hidden items-center justify-center p-4">
        <div class="bg-white rounded-[32px] w-full max-w-2xl overflow-hidden shadow-2xl">
            <div class="p-6 border-b border-slate-50 bg-slate-50/50 flex justify-between items-center">
                <h3 id="staffModalTitle" class="text-xl font-black text-slate-900 uppercase tracking-tight">Add Staff Member</h3>
                <button onclick="closeStaffModal()" class="text-slate-400 hover:text-slate-900 transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            </div>
            <form id="staffForm" class="p-6 space-y-4">
                <input type="hidden" name="id" id="staff_id">
                
                <div class="space-y-1">
                    <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Account Type</label>
                    <select name="role" id="staff_role" required onchange="toggleDeptField()" class="w-full bg-slate-50 border-0 rounded-xl px-4 py-3 outline-none focus:ring-2 focus:ring-blue-500 font-bold text-sm">
                        <option value="Nurse">Nurse</option>
                        <option value="Pharmacist">Pharmacist</option>
                        <option value="Lab Technician">Lab Technician</option>
                        <option value="Doctor">Doctor</option>
                    </select>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div class="space-y-1">
                        <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Full Name</label>
                        <input type="text" name="name" id="staff_name" required class="w-full bg-slate-50 border-0 rounded-xl px-4 py-3 outline-none focus:ring-2 focus:ring-blue-500 font-bold text-sm">
                    </div>
                    <div class="space-y-1">
                        <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Email Address</label>
                        <input type="email" name="email" id="staff_email" required class="w-full bg-slate-50 border-0 rounded-xl px-4 py-3 outline-none focus:ring-2 focus:ring-blue-500 font-bold text-sm">
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div class="space-y-1">
                        <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Password</label>
                        <input type="password" name="password" id="staff_password" class="w-full bg-slate-50 border-0 rounded-xl px-4 py-3 outline-none focus:ring-2 focus:ring-blue-500 font-bold text-sm">
                    </div>
                    <div id="dept_field" class="space-y-1 hidden">
                        <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Department</label>
                        <select name="department_id" id="staff_dept" class="w-full bg-slate-50 border-0 rounded-xl px-4 py-3 outline-none focus:ring-2 focus:ring-blue-500 font-bold text-sm">
                            <?php 
                            $depts = $conn->query("SELECT * FROM departments");
                            while($d = $depts->fetch_assoc()) echo "<option value='{$d['id']}'>{$d['name']}</option>";
                            ?>
                        </select>
                    </div>
                </div>

                <div class="flex gap-3 pt-2">
                    <button type="button" onclick="closeStaffModal()" class="flex-1 py-3 bg-slate-100 text-slate-500 rounded-xl font-black uppercase text-[10px] tracking-widest transition-all">Cancel</button>
                    <button type="submit" class="flex-1 py-3 bg-blue-600 text-white rounded-xl font-black uppercase text-[10px] tracking-widest shadow-lg shadow-blue-100 hover:bg-blue-700 transition-all">Save Account</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Shift Modal (COMPACT DESIGN) -->
    <div id="shiftModal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-[100] hidden items-center justify-center p-4">
        <div class="bg-white rounded-[32px] w-full max-w-lg p-8 shadow-2xl">
            <h3 class="text-xl font-black text-slate-900 mb-6 uppercase tracking-tight">Assign Staff Shift</h3>
            <form action="../api/hr_api.php?action=assign_shift" method="POST" class="space-y-4">
                <div class="grid grid-cols-2 gap-4">
                    <div class="space-y-1">
                        <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Staff Type</label>
                        <select name="staff_type" id="staffTypeSelect" required class="w-full bg-slate-50 border-0 rounded-xl px-4 py-3 outline-none focus:ring-2 focus:ring-blue-500 font-bold text-sm">
                            <option value="Doctor">Doctor</option>
                            <option value="Nurse">Nurse</option>
                            <option value="Pharmacist">Pharmacist</option>
                            <option value="Lab Technician">Lab Technician</option>
                        </select>
                    </div>
                    <div class="space-y-1">
                        <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Staff Member</label>
                        <select name="staff_id" id="staffMemberSelect" required class="w-full bg-slate-50 border-0 rounded-xl px-4 py-3 outline-none focus:ring-2 focus:ring-blue-500 font-bold text-sm">
                        </select>
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div class="space-y-1">
                        <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Shift Date</label>
                        <input type="date" name="shift_date" required class="w-full bg-slate-50 border-0 rounded-xl px-4 py-3 outline-none focus:ring-2 focus:ring-blue-500 font-bold text-sm">
                    </div>
                    <div class="space-y-1">
                        <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Shift Timing</label>
                        <select name="shift_time" required class="w-full bg-slate-50 border-0 rounded-xl px-4 py-3 outline-none focus:ring-2 focus:ring-blue-500 font-bold text-sm">
                            <option value="Morning (08:00-16:00)">Morning (08:00-16:00)</option>
                            <option value="Afternoon (16:00-00:00)">Afternoon (16:00-00:00)</option>
                            <option value="Night (00:00-08:00)">Night (00:00-08:00)</option>
                        </select>
                    </div>
                </div>
                <div class="flex gap-3 pt-4">
                    <button type="button" onclick="document.getElementById('shiftModal').classList.add('hidden'); document.getElementById('shiftModal').classList.remove('flex');" class="flex-1 py-3 bg-slate-100 text-slate-500 rounded-xl font-black uppercase text-[10px] tracking-widest transition-all">Cancel</button>
                    <button type="submit" class="flex-1 py-3 bg-blue-600 text-white rounded-xl font-black uppercase text-[10px] tracking-widest shadow-lg shadow-blue-100 hover:bg-blue-700 transition-all">Assign Shift</button>
                </div>
            </form>
        </div>
    </div>

    <script>
    function openStaffModal() {
        document.getElementById('staffForm').reset();
        document.getElementById('staff_id').value = '';
        document.getElementById('staffModalTitle').textContent = 'Add Staff Member';
        document.getElementById('staff_password').placeholder = 'Enter password';
        document.getElementById('staff_password').required = true;
        document.getElementById('staff_role').disabled = false;
        document.getElementById('staffModal').classList.remove('hidden');
        document.getElementById('staffModal').classList.add('flex');
        toggleDeptField();
    }

    function closeStaffModal() {
        document.getElementById('staffModal').classList.add('hidden');
        document.getElementById('staffModal').classList.remove('flex');
    }

    function toggleDeptField() {
        const role = document.getElementById('staff_role').value;
        const deptField = document.getElementById('dept_field');
        if (role === 'Doctor' || role === 'Nurse') deptField.classList.remove('hidden');
        else deptField.classList.add('hidden');
    }

    function editStaff(staff) {
        document.getElementById('staffForm').reset();
        document.getElementById('staff_id').value = staff.id;
        document.getElementById('staff_name').value = staff.name;
        document.getElementById('staff_email').value = staff.email;
        document.getElementById('staff_role').value = staff.role;
        document.getElementById('staff_password').placeholder = 'Leave blank to keep current';
        document.getElementById('staff_password').required = false;
        document.getElementById('staff_role').disabled = true;
        if (staff.department_id) document.getElementById('staff_dept').value = staff.department_id;
        
        document.getElementById('staffModalTitle').textContent = 'Edit Staff Member';
        document.getElementById('staffModal').classList.remove('hidden');
        document.getElementById('staffModal').classList.add('flex');
        toggleDeptField();
    }

    function deleteStaff(id, role) {
        if (confirm('Are you sure you want to delete this staff member?')) {
            fetch(`../api/hr_api.php?action=delete_staff&id=${id}&role=${role}`)
            .then(r => r.json())
            .then(data => {
                if (data.success) location.reload();
                else alert(data.message);
            });
        }
    }

    document.getElementById('staffForm').addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(this);
        if (document.getElementById('staff_role').disabled) {
            formData.append('role', document.getElementById('staff_role').value);
        }
        
        fetch('../api/hr_api.php?action=save_staff', {
            method: 'POST',
            body: formData
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) location.reload();
            else alert(data.message);
        });
    });

    const staffData = {
        'Doctor': <?php 
            $d = $conn->query("SELECT id, name FROM telemedicine_doctors");
            $arr = []; while($row = $d->fetch_assoc()) $arr[] = $row; echo json_encode($arr); 
        ?>,
        'Pharmacist': <?php 
            $p = $conn->query("SELECT id, name FROM pharmacists");
            $arr = []; while($row = $p->fetch_assoc()) $arr[] = $row; echo json_encode($arr); 
        ?>,
        'Lab Technician': <?php 
            $l = $conn->query("SELECT id, name FROM lab_technicians");
            $arr = []; while($row = $l->fetch_assoc()) $arr[] = $row; echo json_encode($arr); 
        ?>,
        'Nurse': <?php 
            $n = $conn->query("SELECT id, name FROM nurses");
            $arr = []; while($row = $n->fetch_assoc()) $arr[] = $row; echo json_encode($arr); 
        ?>
    };

    const typeSelect = document.getElementById('staffTypeSelect');
    const memberSelect = document.getElementById('staffMemberSelect');

    function updateMembers() {
        const type = typeSelect.value;
        const members = staffData[type];
        memberSelect.innerHTML = members.map(m => `<option value="${m.id}">${m.name}</option>`).join('');
    }

    typeSelect.addEventListener('change', updateMembers);
    updateMembers();
    </script>
</body>
</html>
