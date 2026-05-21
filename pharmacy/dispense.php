<?php
session_start();
if (!isset($_SESSION['pharmacist_id']) && !isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}
require_once '../includes/db_connect.php';
include '../includes/portal_head.php';

$prescription_id = isset($_GET['id']) ? intval($_GET['id']) : null;
$patient_id = isset($_GET['patient_id']) ? intval($_GET['patient_id']) : null;

$prescription = null;
if ($prescription_id) {
    $sql = "SELECT p.*, pat.full_name as patient_name, pat.file_number, pat.id as patient_id, 
                   COALESCE(td.name, dr.name) as doctor_name
            FROM telemedicine_prescriptions p
            JOIN patients pat ON p.patient_id = pat.id
            LEFT JOIN telemedicine_doctors td ON p.doctor_id = td.id
            LEFT JOIN doctors dr ON p.doctor_id = dr.id
            WHERE p.id = $prescription_id";
    $prescription = $conn->query($sql)->fetch_assoc();
    if ($prescription) $patient_id = $prescription['patient_id'];
}

if (!$patient_id) {
    echo "<div class='p-20 text-center'><h2 class='text-2xl font-black text-slate-900'>Patient record not specified.</h2><p class='text-slate-400 mt-2'>Please select a patient from the prescription list.</p><a href='prescriptions.php' class='mt-6 inline-block px-8 py-3 bg-emerald-600 text-white rounded-2xl font-black uppercase text-xs tracking-widest hover:bg-emerald-700 transition-all'>Back to Prescriptions</a></div>";
    include '../includes/portal_footer.php';
    exit;
}

$patient = $conn->query("SELECT * FROM patients WHERE id = $patient_id")->fetch_assoc();
$drugs = $conn->query("SELECT * FROM pharmacy_stock WHERE quantity > 0 ORDER BY drug_name ASC")->fetch_all(MYSQLI_ASSOC);

$existing_order = null;
if ($prescription_id) {
    $existing_order = $conn->query("SELECT d.*, i.status as payment_status 
                                    FROM pharmacy_dispensations d 
                                    LEFT JOIN invoices i ON d.invoice_id = i.id 
                                    WHERE d.prescription_id = $prescription_id AND d.status = 'Awaiting Payment' 
                                    LIMIT 1")->fetch_assoc();
} else {
    $existing_order = $conn->query("SELECT d.*, i.status as payment_status 
                                    FROM pharmacy_dispensations d 
                                    LEFT JOIN invoices i ON d.invoice_id = i.id 
                                    WHERE d.patient_id = $patient_id AND d.status = 'Awaiting Payment' 
                                    ORDER BY d.id DESC LIMIT 1")->fetch_assoc();
}
?>

<style>
    .clinical-engine-root { background: #f0f4f8; min-height: 100vh; font-family: 'Inter', sans-serif; }
    
    .clinical-row { 
        background: white; 
        border: 1px solid #e2e8f0; 
        border-radius: 2rem; 
        padding: 2rem; 
        margin-bottom: 2rem;
        transition: all 0.3s ease;
        box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.05);
    }
    .clinical-row:hover { border-color: #10b981; box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1); }
    
    .input-group { display: flex; flex-direction: column; gap: 0.5rem; }
    .input-label { font-size: 0.65rem; font-weight: 800; color: #64748b; text-transform: uppercase; letter-spacing: 0.1em; margin-left: 0.5rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    
    .clinical-input { 
        background: #f8fafc; 
        border: 2px solid #e2e8f0; 
        border-radius: 1rem; 
        padding: 0.75rem 1rem; 
        font-weight: 700; 
        color: #1e293b;
        transition: all 0.2s;
        width: 100%;
    }
    .clinical-input:focus { border-color: #10b981; background: white; outline: none; box-shadow: 0 0 0 4px rgba(16, 185, 129, 0.1); }

    .result-card {
        background: #0f172a;
        color: white;
        border-radius: 1.5rem;
        padding: 2rem;
        display: flex;
        flex-direction: column;
        justify-content: center;
        height: 100%;
        position: relative;
        overflow: hidden;
    }
    .result-card::before {
        content: '';
        position: absolute;
        top: 0; right: 0;
        width: 100px; height: 100px;
        background: linear-gradient(135deg, rgba(16, 185, 129, 0.1) 0%, transparent 100%);
        border-radius: 0 0 0 100%;
    }

    .badge-clinical { padding: 0.3rem 0.6rem; border-radius: 0.5rem; font-size: 0.6rem; font-weight: 800; text-transform: uppercase; }
    .badge-stock { background: #d1fae5; color: #065f46; }

    .custom-scrollbar::-webkit-scrollbar { width: 6px; }
    .custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
    .custom-scrollbar::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }

    @media (max-width: 1024px) {
        .clinical-row { padding: 1.5rem; }
        .result-card { margin-top: 1.5rem; padding: 1.5rem; }
    }
</style>

<div class="flex h-screen overflow-hidden clinical-engine-root">
    <?php include 'includes/sidebar.php'; ?>

    <main class="flex-1 flex flex-col min-w-0 overflow-hidden">
        <header class="h-20 bg-white border-b border-slate-200 flex items-center justify-between px-10 shrink-0 shadow-sm z-10">
            <div class="flex items-center gap-4">
                <div class="w-10 h-10 bg-emerald-600 rounded-xl flex items-center justify-center text-white">
                    <i data-lucide="pill" class="w-6 h-6"></i>
                </div>
                <div>
                    <h1 class="text-xl font-black text-slate-900 tracking-tight uppercase">Pharmacy <span class="text-emerald-600">Dispense</span></h1>
                    <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest"><?php echo $prescription ? "Case #$prescription_id" : "Walk-in Order"; ?></p>
                </div>
            </div>
            <div class="flex items-center gap-4">
                <a href="prescriptions.php" class="px-5 py-2.5 bg-slate-100 text-slate-600 rounded-xl font-bold hover:bg-slate-200 transition-all text-xs uppercase tracking-widest">
                    Back
                </a>
            </div>
        </header>

        <div class="flex-1 overflow-y-auto p-6 lg:p-10 custom-scrollbar">
            <div class="max-w-7xl mx-auto grid lg:grid-cols-12 gap-8">
                
                <!-- Left: Context & Info -->
                <div class="lg:col-span-4 space-y-6">
                    <div class="bg-white rounded-3xl border border-slate-200 shadow-sm p-8">
                        <div class="flex items-center gap-5 mb-8">
                            <div class="w-16 h-16 bg-emerald-100 text-emerald-700 rounded-2xl flex items-center justify-center text-2xl font-black">
                                <?php echo strtoupper(substr($patient['full_name'] ?? 'P', 0, 1)); ?>
                            </div>
                            <div>
                                <p class="text-xl font-black text-slate-900"><?php echo htmlspecialchars($patient['full_name'] ?? 'Unknown'); ?></p>
                                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-[0.2em]"><?php echo $patient['file_number'] ?? 'N/A'; ?></p>
                            </div>
                        </div>
                        <div class="space-y-3 pt-6 border-t border-slate-100">
                            <div class="flex justify-between text-[10px] font-black uppercase tracking-widest">
                                <span class="text-slate-400">Age / Sex</span>
                                <span class="text-slate-700"><?php echo date_diff(date_create($patient['dob'] ?? 'today'), date_create('today'))->y; ?>Y / <?php echo $patient['gender'] ?? 'N/A'; ?></span>
                            </div>
                        </div>
                    </div>

                    <?php if($prescription): ?>
                    <div class="bg-emerald-700 rounded-3xl p-8 text-white shadow-lg">
                        <h4 class="text-[10px] font-black text-emerald-200 uppercase tracking-[0.3em] mb-4">Doctor's Prescription</h4>
                        <div class="space-y-3">
                            <?php 
                                $meds = json_decode($prescription['medications_json'] ?? '[]', true);
                                foreach($meds as $m) echo "<div class='bg-emerald-600/50 p-4 rounded-2xl border border-emerald-500/30'><p class='font-bold text-sm'>".htmlspecialchars($m['drug'])."</p><p class='text-[10px] opacity-80 mt-1'>".htmlspecialchars($m['dosage'])." • ".htmlspecialchars($m['duration'])."</p></div>";
                            ?>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Right: Dispensing Interface -->
                <div class="lg:col-span-8">
                    <form id="dispenseForm" class="<?php echo $existing_order ? 'opacity-50 pointer-events-none' : ''; ?>">
                        <input type="hidden" name="patient_id" value="<?php echo $patient_id; ?>">
                        <input type="hidden" name="prescription_id" value="<?php echo $prescription_id; ?>">

                        <div id="item-rows" class="space-y-6">
                            <!-- Injected by JS -->
                        </div>

                        <div class="mt-8 flex flex-col md:flex-row justify-between items-center bg-white p-6 rounded-3xl border border-slate-200 gap-4">
                            <button type="button" onclick="addRow()" class="w-full md:w-auto px-8 py-4 bg-emerald-50 text-emerald-600 rounded-2xl font-black text-xs uppercase tracking-widest hover:bg-emerald-600 hover:text-white transition-all flex items-center justify-center gap-3">
                                <i data-lucide="plus" class="w-5 h-5"></i> Add Drug
                            </button>
                            <div class="text-center md:text-right">
                                <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">Total Payable</p>
                                <p id="grand-total" class="text-3xl font-black text-slate-900">₦0.00</p>
                            </div>
                        </div>

                        <button type="submit" id="mainSubmitBtn" class="mt-8 w-full py-6 bg-slate-900 text-white rounded-[2rem] font-black text-lg uppercase tracking-[0.2em] shadow-xl hover:bg-emerald-600 transition-all flex items-center justify-center gap-3">
                            <i data-lucide="check-circle" class="w-6 h-6"></i>
                            Generate Billing
                        </button>
                    </form>

                    <?php if($existing_order): ?>
                        <div class="mt-10 p-10 bg-white rounded-[2.5rem] border-2 border-dashed border-emerald-300 text-center">
                            <?php if($existing_order['payment_status'] == 'Paid'): ?>
                                <i data-lucide="badge-check" class="w-16 h-16 text-emerald-500 mx-auto mb-4"></i>
                                <h4 class="text-2xl font-black text-slate-900">Payment Confirmed</h4>
                                <p class="text-slate-400 font-bold mb-8">Click below to finalize and deduct stock.</p>
                                <button onclick="finalizeHandover(<?php echo $existing_order['id']; ?>)" class="w-full py-6 bg-emerald-600 text-white rounded-2xl font-black text-lg uppercase tracking-widest hover:bg-emerald-700 transition-all">
                                    Final Handover
                                </button>
                            <?php else: ?>
                                <i data-lucide="clock" class="w-16 h-16 text-amber-500 mx-auto mb-4"></i>
                                <h4 class="text-2xl font-black text-slate-900">Awaiting Payment</h4>
                                <p class="text-slate-400 font-bold mb-8">Invoice #<?php echo $existing_order['invoice_id']; ?> is pending.</p>
                                <button onclick="location.reload()" class="px-8 py-3 bg-slate-100 text-slate-600 rounded-xl font-black text-xs uppercase tracking-widest hover:bg-slate-200 transition-all">
                                    Check Status
                                </button>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>
</div>

<template id="itemRowTemplate">
    <div class="clinical-row group">
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
            <!-- Left: Inputs (Col 7) -->
            <div class="lg:col-span-7 space-y-6">
                <div class="input-group">
                    <label class="input-label">Drug Selection</label>
                    <select name="drug_id[]" required onchange="updateRowCore(this)" class="clinical-input drug-select-engine">
                        <option value="">-- Select Medication --</option>
                        <?php foreach($drugs as $d): ?>
                            <option value="<?php echo $d['id']; ?>" 
                                    data-stock="<?php echo $d['quantity']; ?>"
                                    data-price="<?php echo $d['selling_price']; ?>"
                                    data-form="<?php echo $d['form_type']; ?>"
                                    data-strength="<?php echo $d['strength']; ?>"
                                    data-unit="<?php echo $d['base_unit']; ?>"
                                    data-std-dose="<?php echo $d['standard_dose']; ?>">
                                <?php echo htmlspecialchars($d['drug_name']); ?><?php echo !empty($d['strength']) ? " ({$d['strength']})" : ""; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <div class="flex gap-2 mt-2 ml-1 clinical-meta hidden">
                        <span class="badge-clinical bg-slate-100 text-slate-600 strength-val"></span>
                        <span class="badge-clinical bg-blue-50 text-blue-600 form-val"></span>
                        <span class="badge-clinical badge-stock stock-val"></span>
                    </div>
                </div>

                <div class="grid grid-cols-3 gap-4 prescription-inputs">
                    <div class="input-group">
                        <label class="input-label">Dose</label>
                        <input type="number" step="0.01" name="dose[]" value="1" oninput="recalculate(this)" class="clinical-input dose-field">
                    </div>
                    <div class="input-group">
                        <label class="input-label">Frequency</label>
                        <input type="number" name="frequency[]" value="3" oninput="recalculate(this)" class="clinical-input freq-field">
                    </div>
                    <div class="input-group">
                        <label class="input-label">Duration</label>
                        <input type="number" name="duration[]" value="5" oninput="recalculate(this)" class="clinical-input dur-field">
                    </div>
                </div>

                <div class="input-group manual-qty-input hidden">
                    <label class="input-label">Direct Quantity</label>
                    <input type="number" step="0.01" name="manual_qty[]" value="1" oninput="recalculate(this)" class="clinical-input manual-qty-field">
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div class="input-group">
                        <label class="input-label">Mode</label>
                        <select name="mode[]" onchange="updateUIMode(this)" class="clinical-input mode-select">
                            <option value="Prescription">Prescription</option>
                            <option value="Manual">Manual</option>
                        </select>
                    </div>
                    <div class="input-group">
                        <label class="input-label">Unit</label>
                        <select name="dispense_unit_type[]" onchange="recalculate(this)" class="clinical-input unit-type-select">
                            <option value="Tablet">Tablet</option>
                            <option value="ml">ml</option>
                            <option value="Vial">Vial</option>
                            <option value="Capsule">Capsule</option>
                        </select>
                    </div>
                </div>

                <div class="input-group">
                    <label class="input-label">Unit Price (₦)</label>
                    <input type="number" step="0.01" class="clinical-input price-display-input" oninput="recalculate(this)">
                    <input type="hidden" name="unit_price[]" class="price-val">
                </div>
            </div>

            <!-- Right: Results (Col 5) -->
            <div class="lg:col-span-5">
                <div class="result-card">
                    <div class="mb-6">
                        <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Total Quantity</p>
                        <div class="flex items-baseline gap-2 overflow-hidden">
                            <span class="text-4xl font-black qty-display">0</span>
                            <span class="text-sm font-bold text-emerald-400 uppercase unit-display">Tablets</span>
                        </div>
                        <input type="hidden" name="calculated_qty[]" class="qty-val">
                    </div>
                    
                    <div class="pt-6 border-t border-slate-700 overflow-hidden">
                        <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">Subtotal</p>
                        <p class="text-3xl font-black text-emerald-400 subtotal-text break-words">₦0.00</p>
                    </div>

                    <div class="validation-dashboard mt-6 empty:hidden">
                        <div class="bg-rose-500/20 text-rose-400 p-3 rounded-xl text-[10px] font-bold hidden stock-error flex items-center gap-2">
                            <i data-lucide="alert-triangle" class="w-4 h-4"></i>
                            Stock Low: Only <span class="avail-val">0</span> left.
                        </div>
                    </div>

                    <button type="button" onclick="removeRow(this)" class="mt-8 text-slate-500 hover:text-rose-500 text-[10px] font-black uppercase tracking-widest transition-all flex items-center gap-2 self-start">
                        <i data-lucide="trash-2" class="w-4 h-4"></i> Remove
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
function roundToTwo(num) {
    return +(Math.round(num + "e+2")  + "e-2");
}

function addRow() {
    const template = document.getElementById('itemRowTemplate');
    const clone = template.content.cloneNode(true);
    document.getElementById('item-rows').appendChild(clone);
    lucide.createIcons();
    updateGlobalTotals();
}

function removeRow(btn) {
    btn.closest('.clinical-row').remove();
    updateGlobalTotals();
}

function updateRowCore(select) {
    const row = select.closest('.clinical-row');
    const option = select.options[select.selectedIndex];
    const meta = row.querySelector('.clinical-meta');
    
    if(!option.value) { meta.classList.add('hidden'); return; }
    
    meta.classList.remove('hidden');
    if(row.querySelector('.strength-val')) row.querySelector('.strength-val').textContent = option.dataset.strength;
    if(row.querySelector('.form-val')) row.querySelector('.form-val').textContent = option.dataset.form;
    if(row.querySelector('.stock-val')) row.querySelector('.stock-val').textContent = 'Stock: ' + option.dataset.stock;
    
    // Auto-Set defaults
    row.querySelector('.dose-field').value = option.dataset.stdDose || 1;
    
    // Try to auto-select unit type based on form
    const unitSelect = row.querySelector('.unit-type-select');
    const drugUnit = (option.dataset.unit || '').toLowerCase();
    const drugForm = (option.dataset.form || '').toLowerCase();
    
    if (drugUnit.includes('tab') || drugForm.includes('tab')) unitSelect.value = 'Tablet';
    else if (drugUnit.includes('ml') || drugForm.includes('inj') || drugForm.includes('syr')) unitSelect.value = 'ml';
    else if (drugUnit.includes('vial') || drugForm.includes('vial')) unitSelect.value = 'Vial';

    // Set price
    row.querySelector('.price-display-input').value = parseFloat(option.dataset.price || 0).toFixed(2);

    updateUIMode(row.querySelector('.mode-select'));
}

function updateUIMode(select) {
    const row = select.closest('.clinical-row');
    const mode = select.value;
    const prescInputs = row.querySelector('.prescription-inputs');
    const manualInput = row.querySelector('.manual-qty-input');
    
    if(mode === 'Prescription') {
        prescInputs.classList.remove('hidden');
        manualInput.classList.add('hidden');
    } else {
        prescInputs.classList.add('hidden');
        manualInput.classList.remove('hidden');
    }
    recalculate(select);
}

function recalculate(input) {
    const row = input.closest('.clinical-row');
    const select = row.querySelector('.drug-select-engine');
    const option = select.options[select.selectedIndex];
    if(!option.value) return;

    const mode = row.querySelector('.mode-select').value;
    const unitType = row.querySelector('.unit-type-select').value;
    const priceInput = row.querySelector('.price-display-input');
    
    let qty = 0;
    if(mode === 'Prescription') {
        const dose = parseFloat(row.querySelector('.dose-field').value) || 0;
        const freq = parseFloat(row.querySelector('.freq-field').value) || 0;
        const dur = parseFloat(row.querySelector('.dur-field').value) || 0;
        qty = roundToTwo(dose * freq * dur);
    } else {
        qty = parseFloat(row.querySelector('.manual-qty-field').value) || 0;
    }

    const unitPrice = parseFloat(priceInput.value) || 0;
    const subtotal = roundToTwo(qty * unitPrice);

    // Update UI
    row.querySelector('.qty-display').textContent = qty;
    row.querySelector('.unit-display').textContent = unitType + (qty !== 1 ? 's' : '');
    row.querySelector('.qty-val').value = qty;
    row.querySelector('.price-val').value = unitPrice;
    row.querySelector('.subtotal-text').textContent = '₦' + subtotal.toLocaleString(undefined, {minimumFractionDigits:2});

    // Stock Check
    const stock = parseFloat(option.dataset.stock) || 0;
    if(qty > stock) {
        row.querySelector('.stock-error').classList.remove('hidden');
        row.querySelector('.avail-val').textContent = stock + ' Units';
        row.classList.add('has-error');
    } else {
        row.querySelector('.stock-error').classList.add('hidden');
        row.classList.remove('has-error');
    }

    updateGlobalTotals();
}

function updateGlobalTotals() {
    const rows = document.querySelectorAll('.clinical-row');
    let grandTotal = 0;
    let totalItems = 0;
    let anyError = false;

    rows.forEach(row => {
        const sub = parseFloat(row.querySelector('.subtotal-text').textContent.replace('₦','').replace(/,/g,'')) || 0;
        grandTotal += sub;
        totalItems++;
        if(row.classList.contains('has-error')) anyError = true;
    });

    document.getElementById('grand-total').textContent = '₦' + grandTotal.toLocaleString(undefined, {minimumFractionDigits:2});

    const btn = document.getElementById('mainSubmitBtn');
    if(anyError || totalItems === 0) {
        btn.disabled = true;
        btn.classList.add('opacity-50', 'cursor-not-allowed');
    } else {
        btn.disabled = false;
        btn.classList.remove('opacity-50', 'cursor-not-allowed');
    }
}

document.getElementById('dispenseForm').onsubmit = function(e) {
    e.preventDefault();
    if(!confirm('CONFIRM DISPENSING: Proceed to generate billing for these items?')) return;
    
    const items = [];
    document.querySelectorAll('.clinical-row').forEach(row => {
        items.push({
            drug_id: row.querySelector('.drug-select-engine').value,
            mode: row.querySelector('.mode-select').value,
            unit_type: row.querySelector('.unit-type-select').value,
            dose: row.querySelector('.dose-field').value,
            frequency: row.querySelector('.freq-field').value,
            duration: row.querySelector('.dur-field').value,
            manual_qty: row.querySelector('.manual-qty-field').value,
            quantity: row.querySelector('.qty-val').value,
            unit_price: row.querySelector('.price-val').value
        });
    });

    const fd = new FormData(this);
    fd.append('items', JSON.stringify(items));
    fd.append('total_amount', document.getElementById('grand-total').textContent.replace('₦','').replace(/,/g,''));

    fetch('../api/pharmacy_v2.php?action=prepare_bill', { method: 'POST', body: fd })
    .then(r => r.json()).then(data => {
        if(data.success) { alert('Billing Success. Redirecting...'); location.reload(); }
        else alert('Error: ' + data.message);
    });
};

function finalizeHandover(id) {
    if(!confirm('VERIFY HANDOVER: Have you physically verified the medications?')) return;
    const fd = new FormData();
    fd.append('dispensation_id', id);
    fetch('../api/pharmacy_v2.php?action=finalize_dispense', { method: 'POST', body: fd })
    .then(r => r.json()).then(data => {
        if(data.success) { alert('Medication Handed Over.'); location.href = 'dashboard.php'; }
        else alert(data.message);
    });
}

// Start with 1 row
addRow();
</script>

<?php include '../includes/portal_footer.php'; ?>
</body>
</html>