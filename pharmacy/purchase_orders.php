<?php
session_start();
if (!isset($_SESSION['pharmacist_id']) && !isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}
require_once '../includes/db_connect.php';
include '../includes/portal_head.php';

$suppliers = $conn->query("SELECT * FROM suppliers ORDER BY name ASC")->fetch_all(MYSQLI_ASSOC);
?>

<div class="flex h-screen overflow-hidden bg-slate-50">
    <?php include 'includes/sidebar.php'; ?>

    <main class="flex-1 flex flex-col min-w-0 h-screen overflow-hidden">
        <header class="h-20 bg-white border-b border-slate-200 flex items-center justify-between px-10 shrink-0">
            <div>
                <h1 class="text-xl font-black text-slate-900 tracking-tight uppercase">Procurement & Purchase Orders</h1>
                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Manage stock replenishment and suppliers</p>
            </div>
            <button onclick="openPOModal()" class="px-6 py-3 bg-indigo-600 text-white rounded-2xl font-black text-xs uppercase tracking-widest shadow-xl shadow-indigo-100 flex items-center gap-3 hover:bg-indigo-700 transition-all">
                <i data-lucide="plus-circle" class="w-4 h-4"></i> Create New PO
            </button>
        </header>

        <div class="flex-1 overflow-y-auto p-10 custom-scrollbar">
            <div class="bg-white rounded-[40px] border border-slate-100 shadow-sm overflow-hidden">
                <table class="w-full text-left">
                    <thead class="bg-slate-50 border-b border-slate-100">
                        <tr>
                            <th class="px-8 py-5 text-[10px] font-black text-slate-400 uppercase tracking-widest">Order Details</th>
                            <th class="px-8 py-5 text-[10px] font-black text-slate-400 uppercase tracking-widest">Supplier</th>
                            <th class="px-8 py-5 text-[10px] font-black text-slate-400 uppercase tracking-widest">Amount</th>
                            <th class="px-8 py-5 text-[10px] font-black text-slate-400 uppercase tracking-widest">Status</th>
                            <th class="px-8 py-5 text-[10px] font-black text-slate-400 uppercase tracking-widest text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="po-list" class="divide-y divide-slate-50">
                        <!-- Loaded via JS -->
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</div>

<!-- Modal: Create Purchase Order -->
<div id="poModal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-50 hidden flex items-center justify-center p-6">
    <div class="bg-white rounded-[40px] w-full max-w-4xl p-10 space-y-8 shadow-2xl overflow-y-auto max-h-[90vh] animate-fade-in">
        <div class="flex justify-between items-center">
            <h3 class="text-2xl font-black text-slate-900">Generate Purchase Order</h3>
            <button onclick="closeModal('poModal')" class="text-slate-400 hover:text-slate-600"><i data-lucide="x"></i></button>
        </div>

        <form id="po-form" class="space-y-8">
            <div class="grid grid-cols-2 gap-6">
                <div class="space-y-2">
                    <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-4">Select Supplier</label>
                    <select name="supplier_id" required class="w-full px-6 py-4 bg-slate-50 rounded-2xl border-0 font-bold">
                        <option value="">-- Choose Supplier --</option>
                        <?php foreach($suppliers as $s): ?>
                            <option value="<?php echo $s['id']; ?>"><?php echo htmlspecialchars($s['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="space-y-4">
                <div class="flex justify-between items-center">
                    <h4 class="text-xs font-black text-slate-900 uppercase tracking-widest">Order Items</h4>
                    <button type="button" onclick="addPOItem()" class="text-indigo-600 font-bold text-xs uppercase tracking-widest hover:underline">+ Add Item</button>
                </div>
                <div id="po-items-container" class="space-y-3">
                    <!-- Items go here -->
                </div>
            </div>

            <div class="pt-6 border-t border-slate-100 flex justify-between items-center">
                <div>
                    <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Estimated Total</p>
                    <h4 id="po-total-display" class="text-2xl font-black text-indigo-600">₦0.00</h4>
                </div>
                <div class="flex gap-4">
                    <button type="button" onclick="closeModal('poModal')" class="px-8 py-4 bg-slate-100 text-slate-500 rounded-2xl font-bold uppercase text-xs">Cancel</button>
                    <button type="submit" class="px-10 py-4 bg-indigo-600 text-white rounded-2xl font-black uppercase text-xs tracking-widest shadow-xl shadow-indigo-100">Send Order</button>
                </div>
            </div>
        </form>
    </div>
</div>

<template id="poItemTemplate">
    <div class="grid grid-cols-12 gap-4 items-end bg-slate-50 p-4 rounded-2xl group">
        <div class="col-span-6 space-y-1">
            <label class="text-[9px] font-bold text-slate-400 uppercase ml-2">Drug Name</label>
            <input type="text" name="drug_name[]" required class="w-full px-4 py-3 bg-white rounded-xl border-0 font-bold text-sm shadow-sm" placeholder="e.g. Paracetamol 500mg">
        </div>
        <div class="col-span-2 space-y-1">
            <label class="text-[9px] font-bold text-slate-400 uppercase ml-2">Qty (Packs)</label>
            <input type="number" name="qty[]" required oninput="calcPOTotal()" class="w-full px-4 py-3 bg-white rounded-xl border-0 font-bold text-sm text-center shadow-sm">
        </div>
        <div class="col-span-3 space-y-1">
            <label class="text-[9px] font-bold text-slate-400 uppercase ml-2">Cost / Pack (₦)</label>
            <input type="number" step="0.01" name="price[]" required oninput="calcPOTotal()" class="w-full px-4 py-3 bg-white rounded-xl border-0 font-bold text-sm text-center shadow-sm">
        </div>
        <div class="col-span-1 flex justify-center pb-2">
            <button type="button" onclick="this.closest('.grid').remove(); calcPOTotal();" class="p-2 text-rose-300 hover:text-rose-600 transition-all"><i data-lucide="trash-2" class="w-4 h-4"></i></button>
        </div>
    </div>
</template>

<script>
    function loadPOs() {
        fetch('../api/pharmacy_v2.php?action=get_pos')
        .then(r => r.json()).then(data => {
            if(data.success) {
                const html = data.pos.map(po => `
                    <tr class="hover:bg-slate-50/50 transition-colors">
                        <td class="px-8 py-6">
                            <p class="font-black text-slate-900">${po.order_no}</p>
                            <p class="text-[10px] text-slate-400 font-bold uppercase tracking-widest">${new Date(po.created_at).toLocaleDateString()}</p>
                        </td>
                        <td class="px-8 py-6 font-bold text-slate-700">${po.supplier_name}</td>
                        <td class="px-8 py-6 font-black text-slate-900 text-sm">₦${parseFloat(po.total_amount).toLocaleString()}</td>
                        <td class="px-8 py-6">
                            <span class="px-3 py-1 rounded-full text-[9px] font-black uppercase tracking-widest ${
                                po.status === 'Received' ? 'bg-emerald-50 text-emerald-600' : 
                                (po.status === 'Pending' ? 'bg-amber-50 text-amber-600' : 'bg-slate-50 text-slate-400')
                            }">${po.status}</span>
                        </td>
                        <td class="px-8 py-6 text-right">
                            ${po.status === 'Pending' ? `
                                <button onclick="receivePO(${po.id})" class="px-4 py-2 bg-indigo-50 text-indigo-600 rounded-xl font-black text-[10px] uppercase hover:bg-indigo-600 hover:text-white transition-all">Mark Received</button>
                            ` : `
                                <span class="text-[10px] font-black text-slate-300 uppercase italic">Stock Updated</span>
                            `}
                        </td>
                    </tr>
                `).join('');
                document.getElementById('po-list').innerHTML = html || '<tr><td colspan="5" class="py-20 text-center text-slate-400 italic font-medium">No purchase orders found.</td></tr>';
                lucide.createIcons();
            }
        });
    }

    function openPOModal() {
        document.getElementById('po-form').reset();
        document.getElementById('po-items-container').innerHTML = '';
        addPOItem();
        document.getElementById('poModal').classList.remove('hidden');
    }

    function addPOItem() {
        const template = document.getElementById('poItemTemplate');
        const clone = template.content.cloneNode(true);
        document.getElementById('po-items-container').appendChild(clone);
        lucide.createIcons();
    }

    function calcPOTotal() {
        const container = document.getElementById('po-items-container');
        const qtys = container.querySelectorAll('input[name="qty[]"]');
        const prices = container.querySelectorAll('input[name="price[]"]');
        let total = 0;
        qtys.forEach((q, i) => {
            const val = (parseInt(q.value) || 0) * (parseFloat(prices[i].value) || 0);
            total += val;
        });
        document.getElementById('po-total-display').textContent = '₦' + total.toLocaleString(undefined, {minimumFractionDigits: 2});
    }

    document.getElementById('po-form').onsubmit = function(e) {
        e.preventDefault();
        const fd = new FormData(this);
        const drugs = fd.getAll('drug_name[]');
        const qtys = fd.getAll('qty[]');
        const prices = fd.getAll('price[]');
        
        const items = drugs.map((name, i) => ({ name, qty: qtys[i], price: prices[i] }));
        
        const payload = new FormData();
        payload.append('supplier_id', fd.get('supplier_id'));
        payload.append('items', JSON.stringify(items));

        fetch('../api/pharmacy_v2.php?action=create_po', { method: 'POST', body: payload })
        .then(r => r.json()).then(data => {
            if(data.success) {
                alert(data.message);
                closeModal('poModal');
                loadPOs();
            } else alert(data.message);
        });
    };

    function receivePO(id) {
        if(!confirm('Has this order been delivered? Clicking OK will automatically update your Main Store inventory with the quantities in this PO.')) return;
        
        const fd = new FormData();
        fd.append('po_id', id);
        fetch('../api/pharmacy_v2.php?action=receive_po', { method: 'POST', body: fd })
        .then(r => r.json()).then(data => {
            alert(data.message);
            loadPOs();
        });
    }

    function closeModal(id) { document.getElementById(id).classList.add('hidden'); }

    // Init
    loadPOs();
    lucide.createIcons();
</script>

<?php include '../includes/portal_footer.php'; ?>
</body>
</html>