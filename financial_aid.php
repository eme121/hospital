<?php
session_start();
require_once 'includes/db_connect.php';

// Smart Header Logic
if (isset($_SESSION['patient_id'])) {
    require_once 'includes/dashboard_header.php';
} else {
    require_once 'includes/header.php';
}

// Fetch active financial aid requests
$query = "SELECT f.*, p.full_name FROM financial_aid_requests f 
          JOIN patients p ON f.patient_id = p.id
          WHERE f.status = 'pending' AND f.is_approved = 1 AND f.display_on_site = 1
          ORDER BY f.created_at DESC";
$result = $conn->query($query);
?>

<section class="relative py-32 bg-slate-900 overflow-hidden">
    <div class="absolute inset-0 z-0">
        <img src="assets/img/external/financial-aid.jpg" class="w-full h-full object-cover opacity-30" alt="Support Patients">
        <div class="absolute inset-0 bg-gradient-to-b from-slate-900 via-slate-900/80 to-slate-900"></div>
    </div>
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10 text-center">
        <span class="text-sm font-bold text-red-400 uppercase tracking-[0.3em] mb-4 block">Lend a Hand</span>
        <h1 class="text-5xl md:text-7xl font-black text-white leading-tight mb-8">Patients Who <br><span class="text-red-500">Need Support</span></h1>
        <p class="text-xl text-slate-400 max-w-2xl mx-auto font-medium mb-12">Extend a helping hand to those in need of medical financial assistance. Every contribution brings hope and healing.</p>
        
        <?php if (isset($_SESSION['patient_id'])): ?>
            <div class="flex justify-center">
                <button onclick="document.getElementById('requestModal').classList.remove('hidden')" class="px-10 py-5 bg-red-600 text-white rounded-3xl font-black text-sm uppercase tracking-widest hover:bg-red-700 transition-all shadow-2xl shadow-red-500/20">
                    Request Financial Aid
                </button>
            </div>
        <?php endif; ?>
    </div>
</section>

<section class="py-24 bg-white">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid md:grid-cols-3 gap-10">
            <?php if ($result && $result->num_rows > 0): ?>
                <?php while ($row = $result->fetch_assoc()): 
                    $progress = ($row['current_amount'] / $row['amount']) * 100;
                ?>
                    <div class="bg-white rounded-[40px] border border-slate-100 shadow-xl shadow-slate-100/50 overflow-hidden group hover:-translate-y-2 transition-all duration-500">
                        <div class="relative h-64 overflow-hidden">
                            <?php if ($row['image']): ?>
                                <img src="assets/financial_aid/<?php echo $row['image']; ?>" class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-700">
                            <?php else: ?>
                                <div class="w-full h-full bg-blue-600 flex items-center justify-center text-white">
                                    <svg class="w-20 h-20 opacity-20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                </div>
                            <?php endif; ?>
                            <div class="absolute top-6 left-6">
                                <span class="px-4 py-2 bg-white/90 backdrop-blur text-slate-900 text-xs font-black rounded-full shadow-lg">₦<?php echo number_format($row['amount']); ?> Goal</span>
                            </div>
                        </div>
                        <div class="p-8">
                            <h3 class="text-2xl font-black text-slate-900 mb-1"><?php echo htmlspecialchars($row['name']); ?></h3>
                            <p class="text-[10px] font-black text-red-600 uppercase tracking-widest mb-4">Patient: <?php echo htmlspecialchars($row['full_name']); ?></p>
                            <p class="text-slate-500 text-sm font-medium mb-8 line-clamp-3"><?php echo htmlspecialchars($row['reason']); ?></p>
                            
                            <div class="mb-8">
                                <div class="flex justify-between text-xs font-black uppercase tracking-widest mb-2">
                                    <span class="text-blue-600">Raised: ₦<?php echo number_format($row['current_amount']); ?></span>
                                    <span class="text-slate-400"><?php echo round($progress); ?>%</span>
                                </div>
                                <div class="w-full h-3 bg-slate-100 rounded-full overflow-hidden">
                                    <div class="h-full bg-blue-600 transition-all duration-1000" style="width: <?php echo $progress; ?>%"></div>
                                </div>
                            </div>

                            <button onclick="openSupportModal(<?php echo htmlspecialchars(json_encode($row)); ?>)" class="w-full py-4 bg-slate-900 text-white rounded-2xl font-black text-sm hover:bg-blue-600 transition-all shadow-xl shadow-slate-200 group-hover:shadow-blue-200">Support This Patient</button>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="col-span-3 text-center py-20">
                    <p class="text-slate-400 font-bold italic text-xl">No active aid requests at the moment. Thank you for your heart to give!</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<!-- Support Modal -->
<div id="supportModal" class="fixed inset-0 bg-slate-900/40 backdrop-blur-sm z-[100] hidden flex items-center justify-center p-6">
    <div class="bg-white max-w-2xl w-full rounded-[40px] shadow-2xl overflow-hidden animate-in fade-in zoom-in duration-300 relative">
        
        <!-- Paystack Simulation Overlay (Hidden by default) -->
        <div id="paystack-overlay" class="absolute inset-0 bg-white z-[110] hidden flex flex-col items-center justify-center p-10 text-center">
            <div class="w-20 h-20 bg-emerald-500 text-white rounded-full flex items-center justify-center mb-6 animate-pulse">
                <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path></svg>
            </div>
            <h3 class="text-2xl font-black text-slate-900 mb-2">Secure Checkout</h3>
            <p class="text-slate-500 font-medium mb-8 uppercase tracking-widest text-xs">Powered by Paystack</p>
            
            <div class="w-full max-w-xs space-y-4 mb-8">
                <div class="h-12 bg-slate-100 rounded-xl animate-pulse"></div>
                <div class="h-12 bg-slate-100 rounded-xl animate-pulse"></div>
            </div>

            <p class="text-sm font-bold text-slate-400">Processing secure transaction...</p>
        </div>

        <div class="p-10">
            <div class="flex justify-between items-center mb-8">
                <h2 class="text-2xl font-black text-slate-900">Support Patient</h2>
                <button onclick="document.getElementById('supportModal').classList.add('hidden')" class="text-slate-400 hover:text-slate-600 transition-colors">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            </div>

            <div id="modalContent" class="space-y-6">
                <!-- Content injected via JS -->
            </div>
            
            <form id="supportForm" class="mt-8 space-y-6">
                <input type="hidden" name="request_id" id="request_id">
                <div class="space-y-2">
                    <label class="text-xs font-bold text-slate-500 uppercase tracking-widest ml-1">Support Amount (₦)</label>
                    <input type="number" name="support_amount" id="support_amount" required class="w-full bg-slate-50 border-0 rounded-2xl px-6 py-4 text-slate-900 focus:ring-2 focus:ring-blue-500 transition-all outline-none font-bold" placeholder="How much would you like to give?">
                </div>
                <button type="submit" class="w-full py-4 bg-emerald-600 text-white rounded-2xl font-black text-lg transition-all shadow-xl shadow-emerald-200 hover:bg-emerald-700 flex items-center justify-center gap-3">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                    Pay with Paystack
                </button>
            </form>
        </div>
    </div>
</div>

<script>
function openSupportModal(data) {
    const modal = document.getElementById('supportModal');
    const content = document.getElementById('modalContent');
    document.getElementById('request_id').value = data.id;
    document.getElementById('paystack-overlay').classList.add('hidden');
    
    content.innerHTML = `
        <div class="flex items-center gap-6 p-6 bg-slate-50 rounded-3xl border border-slate-100">
            <div class="w-20 h-20 rounded-2xl overflow-hidden shrink-0">
                ${data.image ? `<img src="assets/financial_aid/${data.image}" class="w-full h-full object-cover">` : `<div class="w-full h-full bg-blue-600 flex items-center justify-center text-white text-xs font-black">AID</div>`}
            </div>
            <div>
                <h4 class="text-xl font-black text-slate-900">${data.full_name}</h4>
                <p class="text-xs font-bold text-red-600 uppercase tracking-widest mb-2">${data.name}</p>
                <p class="text-sm text-slate-500 font-medium">Needs assistance for medical treatment.</p>
            </div>
        </div>
        <div>
            <h5 class="text-xs font-black text-slate-400 uppercase tracking-widest mb-2">Request Details</h5>
            <p class="text-slate-600 font-medium leading-relaxed">${data.reason}</p>
        </div>
        <div class="flex justify-between p-4 bg-blue-50 rounded-2xl">
            <span class="text-sm font-bold text-blue-600">Balance Needed:</span>
            <span class="text-sm font-black text-blue-700">₦${(data.amount - data.current_amount).toLocaleString()}</span>
        </div>
    `;
    
    modal.classList.remove('hidden');
}

document.getElementById('supportForm').addEventListener('submit', function(e) {
    e.preventDefault();
    const requestId = document.getElementById('request_id').value;
    const amount = document.getElementById('support_amount').value;
    
    // Show Paystack Simulation
    document.getElementById('paystack-overlay').classList.remove('hidden');
    
    // Wait 2 seconds then complete
    setTimeout(() => {
        fetch('api/support_request.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `request_id=${requestId}&amount=${amount}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                alert('Payment Confirmed! Thank you for your generous support.');
                location.reload();
            } else {
                alert('Error: ' + data.message);
                document.getElementById('paystack-overlay').classList.add('hidden');
            }
        });
    }, 2500);
});
</script>

<?php if (isset($_SESSION['patient_id'])): ?>
<!-- Request Aid Modal -->
<div id="requestModal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-md z-[120] hidden flex items-center justify-center p-6">
    <div class="bg-white max-w-xl w-full rounded-[40px] p-10 shadow-2xl relative">
        <div class="flex justify-between items-center mb-8">
            <h2 class="text-2xl font-black text-slate-900">Request Financial Aid</h2>
            <button onclick="document.getElementById('requestModal').classList.add('hidden')" class="text-slate-400 hover:text-slate-600 transition-colors">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
            </button>
        </div>

        <form id="aidRequestForm" class="space-y-6" enctype="multipart/form-data">
            <div class="space-y-2">
                <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-1">Title of Request</label>
                <input type="text" name="name" required class="w-full bg-slate-50 border-0 rounded-2xl px-6 py-4 font-bold outline-none focus:ring-2 focus:ring-red-500 transition-all" placeholder="e.g. Surgery Assistance">
            </div>

            <div class="space-y-2">
                <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-1">Amount Needed (₦)</label>
                <input type="number" name="amount" required class="w-full bg-slate-50 border-0 rounded-2xl px-6 py-4 font-bold outline-none focus:ring-2 focus:ring-red-500 transition-all" placeholder="50000">
            </div>

            <div class="space-y-2">
                <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-1">Reason for Request</label>
                <textarea name="reason" rows="4" required class="w-full bg-slate-50 border-0 rounded-2xl px-6 py-4 font-medium outline-none focus:ring-2 focus:ring-red-500 transition-all" placeholder="Explain your situation..."></textarea>
            </div>

            <div class="space-y-2">
                <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-1">Supporting Image (Optional)</label>
                <input type="file" name="image" accept="image/*" class="w-full text-xs font-bold text-slate-400">
            </div>

            <button type="submit" class="w-full py-5 bg-red-600 text-white rounded-2xl font-black uppercase tracking-widest shadow-xl shadow-red-200 hover:bg-red-700 transition-all">Submit Request</button>
        </form>
    </div>
</div>

<script>
document.getElementById('aidRequestForm').addEventListener('submit', function(e) {
    e.preventDefault();
    const formData = new FormData(this);
    const btn = this.querySelector('button[type="submit"]');
    btn.disabled = true;
    btn.innerHTML = 'Submitting...';

    fetch('api/submit_financial_aid.php', {
        method: 'POST',
        body: formData
    })
    .then(r => r.json())
    .then(data => {
        if (data.status === 'success') {
            alert('Your request has been submitted to the administrator for review.');
            location.reload();
        } else {
            alert('Error: ' + data.message);
            btn.disabled = false;
            btn.innerHTML = 'Submit Request';
        }
    })
    .catch(err => {
        console.error(err);
        alert('An unexpected error occurred.');
        btn.disabled = false;
        btn.innerHTML = 'Submit Request';
    });
});
</script>
<?php endif; ?>

<?php require_once 'includes/footer.php'; ?>
; ?>

<?php require_once 'includes/footer.php'; ?>
