<?php
session_start();
if (!isset($_SESSION['pharmacist_id'])) {
    header('Location: login.php');
    exit;
}
require_once '../includes/db_connect.php';
include '../includes/portal_head.php';

$movements = $conn->query("SELECT m.*, d.name as drug_name, u.name as staff_name 
                           FROM pharmacy_stock_movements m 
                           JOIN medications d ON m.drug_id = d.id 
                           LEFT JOIN pharmacists u ON m.performed_by = u.id 
                           ORDER BY m.created_at DESC");

$drugs = $conn->query("SELECT id, name, stock_quantity FROM medications ORDER BY name ASC")->fetch_all(MYSQLI_ASSOC);
?>

<body class="flex h-screen overflow-hidden bg-slate-50 text-slate-900">
<div class="flex h-screen overflow-hidden bg-slate-50 w-full">
    <?php include 'includes/sidebar.php'; ?>

    <main class="flex-1 flex flex-col min-w-0 h-screen overflow-hidden">
        <header class="h-20 bg-white border-b border-slate-200 flex items-center justify-between px-8 lg:px-12 shrink-0">
            <div>
                <h1 class="text-xl font-black text-slate-900 tracking-tight uppercase">Stock Movement Logs</h1>
            </div>
            <div class="flex items-center gap-4">
                <button onclick="openModal('movementModal')" class="bg-slate-900 text-white px-6 py-3 rounded-xl font-black text-[10px] uppercase tracking-widest shadow-lg shadow-slate-200 flex items-center gap-2">
                    <i data-lucide="plus" class="w-4 h-4"></i> Record Manual
                </button>
            </div>
        </header>

        <div class="flex-1 overflow-y-auto p-8 lg:p-12 custom-scrollbar">
            <div class="bg-white rounded-[40px] border border-slate-100 shadow-sm overflow-hidden">
                <table class="w-full text-left">
                    <thead class="bg-slate-50 border-b border-slate-100">
                        <tr>
                            <th class="px-8 py-5 text-[10px] font-black text-slate-400 uppercase tracking-widest">Date</th>
                            <th class="px-8 py-5 text-[10px] font-black text-slate-400 uppercase tracking-widest">Drug</th>
                            <th class="px-8 py-5 text-[10px] font-black text-slate-400 uppercase tracking-widest">Type</th>
                            <th class="px-8 py-5 text-[10px] font-black text-slate-400 uppercase tracking-widest text-center">Qty</th>
                            <th class="px-8 py-5 text-[10px] font-black text-slate-400 uppercase tracking-widest">Performed By</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50">
                        <?php while($row = $movements->fetch_assoc()): ?>
                        <tr class="hover:bg-slate-50/50 transition-colors">
                            <td class="px-8 py-6 text-xs font-bold text-slate-500">
                                <?php echo date('M d, Y', strtotime($row['created_at'])); ?><br>
                                <span class="text-[10px] opacity-50 uppercase"><?php echo date('h:i A', strtotime($row['created_at'])); ?></span>
                            </td>
                            <td class="px-8 py-6 font-bold text-slate-900">
                                <?php echo htmlspecialchars($row['drug_name']); ?>
                            </td>
                            <td class="px-8 py-6">
                                <span class="px-3 py-1 bg-slate-100 text-slate-600 text-[10px] font-black uppercase rounded-full">
                                    <?php echo $row['type']; ?>
                                </span>
                            </td>
                            <td class="px-8 py-6 text-center">
                                <span class="font-black <?php echo $row['quantity'] > 0 ? 'text-emerald-600' : 'text-rose-600'; ?>">
                                    <?php echo ($row['quantity'] > 0 ? '+' : '') . $row['quantity']; ?>
                                </span>
                            </td>
                            <td class="px-8 py-6 text-sm font-bold text-slate-600">
                                <?php echo htmlspecialchars($row['staff_name'] ?: 'System'); ?>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</div>

<!-- Record Movement Modal -->
<div id="movementModal" class="fixed inset-0 z-50 hidden flex items-center justify-center p-4">
    <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm transition-opacity" onclick="closeModal('movementModal')"></div>
    <div class="relative bg-white rounded-[40px] w-full max-w-md shadow-2xl overflow-hidden transform transition-all max-h-[90vh] flex flex-col">
        <div class="p-8 border-b border-slate-50 bg-slate-50 flex justify-between items-start shrink-0">
            <div>
                <h3 class="text-2xl font-black text-slate-900">Record Stock Movement</h3>
                <p class="text-emerald-600 font-black text-xs uppercase tracking-widest mt-1">Manual adjustment</p>
            </div>
            <button onclick="closeModal('movementModal')" class="p-2 text-slate-400 hover:text-slate-900 transition-colors">
                <i data-lucide="x" class="w-6 h-6"></i>
            </button>
        </div>
        <div class="overflow-y-auto custom-scrollbar flex-1">
            <form id="movementForm" class="p-8 space-y-6">
                <div class="space-y-2">
                    <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-4 block">Select Drug</label>
                    <select name="drug_id" required class="w-full bg-slate-50 border-0 rounded-2xl px-6 py-4 outline-none focus:ring-2 focus:ring-emerald-500 font-bold">
                        <option value="">-- Select Drug --</option>
                        <?php foreach($drugs as $d): ?>
                            <option value="<?php echo $d['id']; ?>"><?php echo htmlspecialchars($d['name']); ?> (Current: <?php echo $d['stock_quantity']; ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="grid grid-cols-2 gap-4">
                    <div class="space-y-2">
                        <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-4 block">Quantity</label>
                        <input type="number" name="quantity" required class="w-full bg-slate-50 border-0 rounded-2xl px-6 py-4 outline-none focus:ring-2 focus:ring-emerald-500 font-black" placeholder="Use negative for OUT">
                    </div>
                    <div class="space-y-2">
                        <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-4 block">Movement Type</label>
                        <select name="type" required class="w-full bg-slate-50 border-0 rounded-2xl px-6 py-4 outline-none focus:ring-2 focus:ring-emerald-500 font-bold">
                            <option value="Outreach">Outreach</option>
                            <option value="Adjustment">Adjustment</option>
                            <option value="Loss">Loss / Expired</option>
                            <option value="Return">Return</option>
                        </select>
                    </div>
                </div>

                <div class="space-y-2">
                    <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-4 block">Reason / Notes</label>
                    <textarea name="notes" rows="3" class="w-full bg-slate-50 border-0 rounded-2xl px-6 py-4 outline-none focus:ring-2 focus:ring-emerald-500 font-medium text-slate-600 resize-none" placeholder="Explain the reason for this movement..."></textarea>
                </div>

                <div class="pt-4">
                    <button type="submit" class="w-full py-5 bg-slate-900 text-white rounded-3xl font-black uppercase text-xs tracking-[0.2em] shadow-2xl shadow-slate-100 hover:bg-black transition-all duration-300">
                        Record Movement
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function closeModal(id) {
    document.getElementById(id).classList.add('hidden');
}

function openModal(id) {
    document.getElementById(id).classList.remove('hidden');
    lucide.createIcons();
}

document.getElementById('movementForm').addEventListener('submit', function(e) {
    e.preventDefault();
    const formData = new FormData(this);
    fetch('../api/pharmacy_v2.php?action=record_movement', { method: 'POST', body: formData })
    .then(r => r.json()).then(data => {
        if(data.success) {
            alert('Movement recorded successfully.');
            location.reload();
        } else {
            alert(data.message);
        }
    });
});
</script>

<?php include '../includes/portal_footer.php'; ?>
