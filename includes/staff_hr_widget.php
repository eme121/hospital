<?php
// Shared HR Widget for Staff Dashboards
// Assumes $staff_type and $staff_id are set in the including file.

$roster_query = $conn->prepare("SELECT * FROM staff_roster WHERE staff_type = ? AND staff_id = ? AND shift_date >= CURDATE() ORDER BY shift_date ASC LIMIT 5");
$roster_query->bind_param("si", $staff_type, $staff_id);
$roster_query->execute();
$my_roster = $roster_query->get_result();

$leave_query = $conn->prepare("SELECT * FROM leave_requests WHERE staff_type = ? AND staff_id = ? ORDER BY applied_at DESC LIMIT 3");
$leave_query->bind_param("si", $staff_type, $staff_id);
$leave_query->execute();
$my_leaves = $leave_query->get_result();
?>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mt-12">
    <!-- My Roster -->
    <div class="bg-white rounded-[40px] border border-slate-100 shadow-sm overflow-hidden">
        <div class="p-8 border-b border-slate-50 bg-blue-50/30 flex justify-between items-center">
            <h3 class="text-xl font-black text-slate-900">My Duty Roster</h3>
            <span class="px-3 py-1 bg-white text-blue-600 text-[10px] font-black uppercase rounded-full border border-blue-100">Upcoming</span>
        </div>
        <div class="p-8">
            <?php if($my_roster->num_rows == 0): ?>
                <p class="text-slate-400 font-medium italic text-center py-8">No upcoming shifts assigned.</p>
            <?php else: ?>
            <div class="space-y-6">
                <?php while($shift = $my_roster->fetch_assoc()): ?>
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-4">
                        <div class="w-10 h-10 bg-slate-50 text-slate-400 rounded-xl flex items-center justify-center">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        </div>
                        <div>
                            <p class="text-sm font-bold text-slate-900"><?php echo date('l, d M', strtotime($shift['shift_date'])); ?></p>
                            <p class="text-[10px] font-bold text-blue-600 uppercase tracking-widest"><?php echo $shift['shift_time']; ?></p>
                        </div>
                    </div>
                    <span class="text-[9px] font-black text-slate-400 uppercase"><?php echo $shift['status']; ?></span>
                </div>
                <?php endwhile; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Leave Management -->
    <div class="bg-white rounded-[40px] border border-slate-100 shadow-sm overflow-hidden">
        <div class="p-8 border-b border-slate-50 bg-rose-50/30 flex justify-between items-center">
            <h3 class="text-xl font-black text-slate-900">Leave Management</h3>
            <button onclick="document.getElementById('leaveModal').classList.remove('hidden')" class="text-[10px] font-black text-rose-600 uppercase hover:underline">Request Leave</button>
        </div>
        <div class="p-8">
            <?php if($my_leaves->num_rows == 0): ?>
                <p class="text-slate-400 font-medium italic text-center py-8">No leave requests found.</p>
            <?php else: ?>
            <div class="space-y-6">
                <?php while($leave = $my_leaves->fetch_assoc()): ?>
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-bold text-slate-900"><?php echo $leave['leave_type']; ?> Leave</p>
                        <p class="text-[10px] font-bold text-slate-400 uppercase"><?php echo date('d M', strtotime($leave['start_date'])); ?> - <?php echo date('d M Y', strtotime($leave['end_date'])); ?></p>
                    </div>
                    <span class="px-3 py-1 rounded-full text-[9px] font-black uppercase tracking-widest <?php echo $leave['status'] == 'Approved' ? 'bg-emerald-50 text-emerald-600' : ($leave['status'] == 'Rejected' ? 'bg-rose-50 text-rose-600' : 'bg-amber-50 text-amber-600'); ?>">
                        <?php echo $leave['status']; ?>
                    </span>
                </div>
                <?php endwhile; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Leave Modal -->
<div id="leaveModal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-[100] hidden items-center justify-center p-4">
    <div class="bg-white rounded-[40px] w-full max-w-lg p-10 shadow-2xl">
        <h3 class="text-2xl font-black text-slate-900 mb-8">Request Leave</h3>
        <form id="leaveForm" class="space-y-6">
            <input type="hidden" name="staff_type" value="<?php echo $staff_type; ?>">
            <input type="hidden" name="staff_id" value="<?php echo $staff_id; ?>">
            
            <div class="space-y-2">
                <label class="text-xs font-bold text-slate-400 uppercase">Leave Type</label>
                <select name="leave_type" required class="w-full bg-slate-50 border-0 rounded-2xl px-6 py-4 outline-none focus:ring-2 focus:ring-rose-500 font-bold">
                    <option value="Sick">Sick Leave</option>
                    <option value="Vacation">Vacation</option>
                    <option value="Personal">Personal Day</option>
                    <option value="Maternity/Paternity">Maternity/Paternity</option>
                </select>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div class="space-y-2">
                    <label class="text-xs font-bold text-slate-400 uppercase">Start Date</label>
                    <input type="date" name="start_date" required class="w-full bg-slate-50 border-0 rounded-2xl px-6 py-4 outline-none focus:ring-2 focus:ring-rose-500 font-bold">
                </div>
                <div class="space-y-2">
                    <label class="text-xs font-bold text-slate-400 uppercase">End Date</label>
                    <input type="date" name="end_date" required class="w-full bg-slate-50 border-0 rounded-2xl px-6 py-4 outline-none focus:ring-2 focus:ring-rose-500 font-bold">
                </div>
            </div>

            <div class="space-y-2">
                <label class="text-xs font-bold text-slate-400 uppercase">Reason</label>
                <textarea name="reason" rows="3" required class="w-full bg-slate-50 border-0 rounded-2xl px-6 py-4 outline-none focus:ring-2 focus:ring-rose-500 font-medium"></textarea>
            </div>

            <div class="flex gap-4 pt-4">
                <button type="button" onclick="document.getElementById('leaveModal').classList.add('hidden')" class="flex-1 py-4 bg-slate-100 text-slate-500 rounded-2xl font-black uppercase text-xs">Cancel</button>
                <button type="submit" class="flex-2 px-10 py-4 bg-rose-600 text-white rounded-2xl font-black uppercase text-xs shadow-lg shadow-rose-200">Submit Request</button>
            </div>
        </form>
    </div>
</div>

<script>
document.getElementById('leaveForm').addEventListener('submit', function(e) {
    e.preventDefault();
    const formData = new FormData(this);
    fetch('../api/hr_api.php?action=request_leave', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if(data.success) {
            alert("Leave request submitted!");
            location.reload();
        } else {
            alert(data.message);
        }
    });
});
</script>
