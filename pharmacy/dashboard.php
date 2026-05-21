<?php
session_start();
if (!isset($_SESSION['pharmacist_id'])) {
    header('Location: login.php');
    exit;
}
require_once '../includes/db_connect.php';
require_once '../includes/clinical_helper.php';

// Advanced Stats from NEW Schema
$main_store_qty = $conn->query("SELECT IFNULL(SUM(quantity), 0) as total FROM main_store_inventory")->fetch_assoc()['total'];
$pharmacy_qty = $conn->query("SELECT IFNULL(SUM(quantity), 0) as total FROM pharmacy_stock")->fetch_assoc()['total'];
$low_stock = $conn->query("SELECT COUNT(*) as count FROM pharmacy_stock WHERE quantity <= reorder_level")->fetch_assoc()['count'];

// Revenue and Dispensations
$today_disp = $conn->query("SELECT COUNT(*) as count FROM pharmacy_dispensations WHERE DATE(dispensed_at) = CURRENT_DATE")->fetch_assoc()['count'] ?? 0;
$today_revenue = $conn->query("SELECT SUM(total_amount) as total FROM pharmacy_dispensations WHERE DATE(dispensed_at) = CURRENT_DATE AND status='Dispensed'")->fetch_assoc()['total'] ?: 0;
$safety_overrides = $conn->query("SELECT COUNT(*) FROM pharmacy_dispensation_items di JOIN pharmacy_dispensations d ON di.dispensation_id = d.id WHERE di.is_override = 1 AND DATE(d.dispensed_at) = CURDATE()")->fetch_row()[0] ?? 0;

$recent_movements = $conn->query("SELECT * FROM stock_movements ORDER BY movement_date DESC LIMIT 6");

// Pharmacy Queue (New from Smart Triage) - Centralized via ClinicalHelper
$pharmacy_queue = ClinicalHelper::getPatientQueue($conn, 'Pharmacy', ['Medication']);

include '../includes/portal_head.php';
?>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<style>
    .toast-notification {
        font-family: 'Plus Jakarta Sans', sans-serif !important;
        border-radius: 20px !important;
        box-shadow: 0 20px 40px -10px rgba(0,0,0,0.1) !important;
    }
</style>

<body class="bg-slate-50 text-slate-900">
<div class="flex h-screen overflow-hidden bg-slate-50">
    <?php include 'includes/sidebar.php'; ?>

    <!-- Main Content -->
    <main class="flex-1 flex flex-col min-w-0 h-screen overflow-hidden">

        <header class="bg-white border-b border-slate-200 h-20 flex items-center justify-between px-8 shrink-0">
            <h2 class="text-xl font-black text-slate-900 uppercase tracking-tight">Pharmacy Command Center</h2>
            <div class="flex items-center gap-6">
                <div class="text-right hidden sm:block">
                    <p class="text-xs font-black text-slate-900"><?php echo htmlspecialchars($_SESSION['pharmacist_name']); ?></p>
                    <p class="text-[10px] font-bold text-emerald-600 uppercase">Head Pharmacist</p>
                </div>
                <div class="w-12 h-12 bg-emerald-100 rounded-2xl border border-emerald-200 flex items-center justify-center text-emerald-600 font-black text-lg">
                    <?php echo strtoupper(substr($_SESSION['pharmacist_name'], 0, 1)); ?>
                </div>
            </div>
        </header>

        <div class="flex-1 overflow-y-auto p-8 space-y-8 custom-scrollbar">
            <!-- Summary Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-6">
                <div class="bg-white p-6 rounded-[32px] border border-slate-100 shadow-sm card-hover transition-all">
                    <div class="w-12 h-12 bg-emerald-50 rounded-2xl flex items-center justify-center text-emerald-600 mb-4"><i data-lucide="warehouse"></i></div>
                    <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">Main Store Stock</p>
                    <h4 class="text-2xl font-black text-slate-900"><?php echo number_format($main_store_qty); ?></h4>
                </div>
                <div class="bg-white p-6 rounded-[32px] border border-slate-100 shadow-sm card-hover transition-all">
                    <div class="w-12 h-12 bg-emerald-50 rounded-2xl flex items-center justify-center text-emerald-600 mb-4"><i data-lucide="pill"></i></div>
                    <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">Pharmacy Units</p>
                    <h4 class="text-2xl font-black text-emerald-600"><?php echo number_format($pharmacy_qty); ?></h4>
                </div>
                <div class="bg-white p-6 rounded-[32px] border border-slate-100 shadow-sm card-hover transition-all">
                    <div class="w-12 h-12 bg-rose-50 rounded-2xl flex items-center justify-center text-rose-600 mb-4"><i data-lucide="alert-triangle"></i></div>
                    <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">Low Stock Alerts</p>
                    <h4 class="text-2xl font-black text-rose-600"><?php echo $low_stock; ?></h4>
                </div>
                <div class="bg-white p-6 rounded-[32px] border border-slate-100 shadow-sm card-hover transition-all">
                    <div class="w-12 h-12 bg-amber-50 rounded-2xl flex items-center justify-center text-amber-600 mb-4"><i data-lucide="shield-alert"></i></div>
                    <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">Safety Overrides</p>
                    <h4 class="text-2xl font-black text-amber-600"><?php echo $safety_overrides; ?></h4>
                </div>
                <div class="bg-white p-6 rounded-[32px] border border-slate-100 shadow-sm card-hover transition-all">
                    <div class="w-12 h-12 bg-blue-50 rounded-2xl flex items-center justify-center text-blue-600 mb-4"><i data-lucide="banknote"></i></div>
                    <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">Today's Revenue</p>
                    <h4 class="text-2xl font-black text-slate-900">₦<?php echo number_format($today_revenue, 0); ?></h4>
                </div>
            </div>

            <!-- Pharmacy Queue (New) -->
            <div class="bg-white rounded-[40px] border border-slate-200 shadow-sm overflow-hidden">
                <div class="p-8 border-b border-slate-100 flex justify-between items-center bg-emerald-50/30">
                    <div>
                        <h4 class="font-black text-slate-900 uppercase tracking-tight">Active Prescription Queue</h4>
                        <p class="text-[10px] font-bold text-emerald-600 uppercase tracking-widest mt-0.5">Patients Waiting for Medication</p>
                    </div>
                    <div class="flex gap-2">
                         <span class="px-3 py-1 bg-white border border-emerald-100 rounded-lg text-[10px] font-black text-emerald-600 shadow-sm"><?php echo $pharmacy_queue->num_rows; ?> Waiting</span>
                    </div>
                </div>
                <div class="divide-y divide-slate-50">
                    <?php if($pharmacy_queue->num_rows == 0): ?>
                        <div class="p-12 text-center text-slate-400 font-bold italic">The pharmacy queue is currently empty.</div>
                    <?php else: while($pq = $pharmacy_queue->fetch_assoc()): ?>
                        <div class="p-6 flex items-center justify-between hover:bg-slate-50 transition-colors">
                            <div class="flex items-center gap-6">
                                <div class="w-14 h-14 bg-white rounded-2xl flex items-center justify-center text-slate-400 border border-slate-100 font-black text-xl shadow-sm">
                                    <?php echo substr($pq['patient_name'], 0, 1); ?>
                                </div>
                                <div>
                                    <div class="flex items-center gap-3">
                                        <p class="text-base font-black text-slate-900"><?php echo htmlspecialchars($pq['patient_name']); ?></p>
                                        <span class="px-2 py-0.5 bg-blue-50 text-blue-600 border border-blue-100 rounded text-[8px] font-black uppercase tracking-tighter"><?php echo $pq['status']; ?></span>
                                    </div>
                                    <p class="text-[10px] font-bold text-slate-400 uppercase mt-1">#<?php echo $pq['file_number']; ?> • <?php echo $pq['gender']; ?>, <?php echo $pq['age']; ?>y • Since <?php echo date('H:i', strtotime($pq['updated_at'])); ?></p>
                                </div>
                            </div>
                            <div class="flex items-center gap-3">
                                <?php if ($pq['payment_status'] !== 'Paid'): ?>
                                    <div class="flex flex-col items-end gap-1">
                                        <button disabled class="px-6 py-3 bg-slate-100 text-slate-400 rounded-xl font-black text-[10px] uppercase cursor-not-allowed border border-slate-200 opacity-60">Locked</button>
                                        <span class="text-[8px] font-bold text-amber-600 uppercase tracking-tighter flex items-center gap-1">
                                            <i data-lucide="alert-circle" class="w-3 h-3 text-amber-600"></i> Awaiting Payment
                                        </span>
                                    </div>
                                <?php else: ?>
                                    <a href="dispense.php?patient_id=<?php echo $pq['patient_id']; ?>" class="px-6 py-3 bg-emerald-600 text-white text-[10px] font-black rounded-xl hover:bg-emerald-700 transition-all shadow-xl shadow-emerald-50 uppercase tracking-widest">Open Order</a>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endwhile; endif; ?>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <!-- Inventory Warnings (New) -->
                <div class="lg:col-span-1 bg-white rounded-[40px] border border-slate-200 shadow-sm overflow-hidden border-l-rose-500 border-l-4">
                    <div class="p-8 border-b border-slate-100 bg-rose-50/30">
                        <h4 class="font-black text-rose-900 uppercase tracking-tight">Critical Warnings</h4>
                        <p class="text-[10px] font-bold text-rose-600 uppercase tracking-widest mt-0.5">Expiring or Low Stock</p>
                    </div>
                    <div class="p-4 space-y-4 max-h-[400px] overflow-y-auto">
                        <?php
                        $warnings = [];
                        // Expiry Warnings (Next 90 days)
                        $exp_res = $conn->query("SELECT drug_name, expiry_date, 'Pharmacy' as location FROM pharmacy_stock WHERE expiry_date <= DATE_ADD(CURDATE(), INTERVAL 90 DAY) AND expiry_date != '0000-00-00' UNION SELECT drug_name, expiry_date, 'Main Store' as location FROM main_store_inventory WHERE expiry_date <= DATE_ADD(CURDATE(), INTERVAL 90 DAY) AND expiry_date != '0000-00-00' ORDER BY expiry_date ASC LIMIT 10");
                        while($w = $exp_res->fetch_assoc()) {
                            $days = floor((strtotime($w['expiry_date']) - time()) / 86400);
                            $warnings[] = [
                                'type' => 'expiry',
                                'title' => $w['drug_name'],
                                'meta' => $w['location'] . " • Expires " . date('d M', strtotime($w['expiry_date'])),
                                'badge' => $days . " days left",
                                'color' => $days <= 30 ? 'rose' : 'amber'
                            ];
                        }
                        
                        // Low Stock Warnings
                        $low_res = $conn->query("SELECT drug_name, quantity, reorder_level, 'Pharmacy' as location FROM pharmacy_stock WHERE quantity <= reorder_level UNION SELECT drug_name, quantity, reorder_level, 'Main Store' as location FROM main_store_inventory WHERE quantity <= reorder_level LIMIT 10");
                        while($w = $low_res->fetch_assoc()) {
                            $warnings[] = [
                                'type' => 'stock',
                                'title' => $w['drug_name'],
                                'meta' => $w['location'] . " • Currently " . $w['quantity'],
                                'badge' => "LOW STOCK",
                                'color' => 'rose'
                            ];
                        }

                        if(empty($warnings)): ?>
                            <div class="p-8 text-center text-slate-400 font-bold italic">No critical inventory warnings.</div>
                        <?php else: foreach($warnings as $warn): ?>
                            <div class="flex items-center gap-4 p-4 rounded-3xl bg-<?php echo $warn['color']; ?>-50/50 border border-<?php echo $warn['color']; ?>-100 group">
                                <div class="w-10 h-10 rounded-2xl bg-white flex items-center justify-center text-<?php echo $warn['color']; ?>-600 shadow-sm">
                                    <i data-lucide="<?php echo $warn['type'] == 'expiry' ? 'calendar-off' : 'package-x'; ?>" class="w-5 h-5"></i>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="text-xs font-black text-slate-900 truncate"><?php echo $warn['title']; ?></p>
                                    <p class="text-[9px] font-bold text-slate-400 uppercase tracking-widest truncate"><?php echo $warn['meta']; ?></p>
                                </div>
                                <span class="px-2 py-1 bg-white rounded-lg text-[8px] font-black text-<?php echo $warn['color']; ?>-600 uppercase tracking-tighter border border-<?php echo $warn['color']; ?>-100"><?php echo $warn['badge']; ?></span>
                            </div>
                        <?php endforeach; endif; ?>
                    </div>
                </div>

                <!-- Recent Stock Movements -->
                <div class="lg:col-span-2 bg-white rounded-[40px] border border-slate-200 shadow-sm overflow-hidden">
                    <div class="p-8 border-b border-slate-100 flex justify-between items-center">
                        <h4 class="font-black text-slate-900 text-lg uppercase tracking-tight">Recent Stock Activity</h4>
                        <a href="inventory.php" class="text-xs font-black text-emerald-600 uppercase tracking-widest hover:underline">View All</a>
                    </div>
                    <div class="p-2">
                        <table class="w-full text-left">
                            <thead class="bg-slate-50/50">
                                <tr>
                                    <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest">Drug</th>
                                    <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest">Route</th>
                                    <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest text-center">Qty</th>
                                    <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest">Time</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-50">
                                <?php if($recent_movements): while($move = $recent_movements->fetch_assoc()): ?>
                                <tr class="hover:bg-slate-50/50 transition-colors">
                                    <td class="px-6 py-4 font-bold text-slate-900"><?php echo htmlspecialchars($move['drug_name']); ?></td>
                                    <td class="px-6 py-4"><span class="px-2 py-1 bg-slate-100 text-slate-600 text-[9px] font-black uppercase rounded-full"><?php echo $move['from_location']; ?> → <?php echo $move['to_location']; ?></span></td>
                                    <td class="px-6 py-4 text-center font-black text-emerald-600">
                                        <?php echo $move['quantity']; ?>
                                    </td>
                                    <td class="px-6 py-4 text-[10px] font-bold text-slate-400 uppercase"><?php echo date('h:i A', strtotime($move['movement_date'])); ?></td>
                                </tr>
                                <?php endwhile; endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Quick Actions & Critical Alerts -->
                <div class="space-y-8">
                    <div class="bg-slate-900 rounded-[40px] p-8 text-white shadow-2xl shadow-slate-200">
                        <h4 class="font-black text-lg mb-6 uppercase tracking-tight">Quick Actions</h4>
                        <div class="grid grid-cols-2 gap-4">
                            <a href="inventory.php" class="p-4 bg-slate-800 rounded-3xl hover:bg-emerald-600 transition-all text-center group">
                                <i data-lucide="warehouse" class="w-6 h-6 mx-auto mb-2 text-emerald-400 group-hover:text-white"></i>
                                <p class="text-[10px] font-black uppercase tracking-widest">Main Store</p>
                            </a>
                            <a href="inventory.php" class="p-4 bg-slate-800 rounded-3xl hover:bg-emerald-600 transition-all text-center group">
                                <i data-lucide="pill" class="w-6 h-6 mx-auto mb-2 text-emerald-400 group-hover:text-white"></i>
                                <p class="text-[10px] font-black uppercase tracking-widest">Dispensing</p>
                            </a>
                        </div>
                    </div>

                    <!-- Inventory Health & Alerts -->
                    <div class="bg-white rounded-[40px] border border-slate-200 shadow-sm overflow-hidden">
                        <div class="p-6 border-b border-slate-100 bg-amber-50/30">
                            <h4 class="font-black text-slate-900 flex items-center gap-2 uppercase tracking-tight"><i data-lucide="calendar-off" class="w-5 h-5 text-amber-600"></i> Expiry Watch (Next 90 Days)</h4>
                        </div>
                        <div class="divide-y divide-slate-50" id="expiry-alerts-list">
                            <div class="p-8 text-center text-slate-400 font-bold italic text-xs">Loading health checks...</div>
                        </div>
                    </div>

                    <div class="bg-white rounded-[40px] border border-slate-200 shadow-sm overflow-hidden">
                        <div class="p-6 border-b border-slate-100 bg-rose-50/30">
                            <h4 class="font-black text-slate-900 flex items-center gap-2 uppercase tracking-tight"><i data-lucide="alert-circle" class="w-5 h-5 text-rose-600"></i> Critical Stock (Pharmacy)</h4>
                        </div>
                        <div class="divide-y divide-slate-50" id="low-stock-list">
                            <?php 
                            $low_items = $conn->query("SELECT * FROM pharmacy_stock WHERE quantity <= reorder_level ORDER BY quantity ASC LIMIT 4");
                            if($low_items && $low_items->num_rows == 0): ?>
                                <div class="p-8 text-center text-slate-400 font-bold italic text-xs">All levels optimal.</div>
                            <?php elseif($low_items): while($item = $low_items->fetch_assoc()): ?>
                                <div class="p-4 flex items-center justify-between hover:bg-slate-50 transition-colors">
                                    <div>
                                        <p class="text-sm font-black text-slate-900"><?php echo $item['drug_name']; ?></p>
                                        <p class="text-[10px] font-bold text-slate-400 uppercase">Only <?php echo $item['quantity']; ?> left</p>
                                    </div>
                                    <span class="px-2 py-1 <?php echo $item['quantity'] <= 0 ? 'bg-rose-100 text-rose-600' : 'bg-amber-100 text-amber-600'; ?> rounded-lg text-[8px] font-black uppercase">
                                        <?php echo $item['quantity'] <= 0 ? 'Out of Stock' : 'Low Stock'; ?>
                                    </span>
                                </div>
                            <?php endwhile; endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <?php include '../includes/portal_footer.php'; ?>
    <script>
        function fetchInventoryAlerts() {
            fetch('../api/inventory_alerts.php?action=get_alerts')
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        const expiryList = document.getElementById('expiry-alerts-list');
                        const expiringItems = data.alerts.filter(a => a.type === 'expiring_soon' || a.type === 'expired');
                        
                        if (expiringItems.length === 0) {
                            expiryList.innerHTML = '<div class="p-8 text-center text-slate-400 font-bold italic text-xs">No upcoming expiries.</div>';
                        } else {
                            expiryList.innerHTML = expiringItems.map(a => `
                                <div class="p-4 flex items-center justify-between hover:bg-slate-50 transition-colors">
                                    <div>
                                        <p class="text-sm font-black text-slate-900">${a.item}</p>
                                        <p class="text-[10px] font-bold ${a.severity === 'critical' ? 'text-rose-600' : 'text-amber-600'} uppercase">
                                            ${a.type === 'expired' ? 'EXPIRED' : 'Expiring: ' + a.date}
                                        </p>
                                    </div>
                                    <div class="w-2 h-2 rounded-full ${a.severity === 'critical' ? 'bg-rose-500 animate-pulse' : 'bg-amber-500'}"></div>
                                </div>
                            `).join('');
                        }
                    }
                });
        }

        // Init alerts
        fetchInventoryAlerts();
        setInterval(fetchInventoryAlerts, 60000); // Check every minute

        // Real-Time Sync Subscription
        if (window.HospitalSync) {
            window.HospitalSync.subscribe('patient_queue', (signal) => {
                console.log('📡 [Pharmacy] Patient Queue Updated');
                location.reload(); 
            });
            window.HospitalSync.subscribe('prescriptions', (signal) => {
                console.log('📡 [Pharmacy] New Prescription Received');
                location.reload();
            });
            window.HospitalSync.subscribe('billing', (signal) => {
                console.log('📡 [Pharmacy] Payment Status Changed');
                location.reload();
            });
        }
    </script>