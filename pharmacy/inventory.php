<?php
session_start();
if (!isset($_SESSION['pharmacist_id']) && !isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}
require_once '../includes/db_connect.php';
include '../includes/portal_head.php';
?>

<body class="flex h-screen overflow-hidden bg-slate-50 text-slate-900">
<div class="flex h-screen overflow-hidden bg-slate-50 w-full">

    <?php include 'includes/sidebar.php'; ?>

    <!-- Main Content -->
    <main class="flex-1 flex flex-col min-w-0 h-screen overflow-hidden">

        <header class="h-20 bg-white border-b border-slate-200 flex items-center justify-between px-10 shrink-0">
            <h2 id="view-title" class="text-lg font-black text-slate-900 uppercase tracking-tight">Pharmacy Dashboard</h2>
            <div class="flex items-center gap-4">
                <button id="header-new-purchase-btn" onclick="openPurchaseModal()" class="px-5 py-2.5 bg-emerald-600 text-white rounded-xl text-xs font-black uppercase tracking-widest shadow-lg shadow-emerald-100 hover:bg-emerald-700 transition-all flex items-center gap-2">
                    <i data-lucide="plus" class="w-4 h-4"></i> New Purchase
                </button>
            </div>
        </header>

        <div class="flex-1 overflow-y-auto p-10 bg-slate-50/50">
            
            <!-- VIEW: DASHBOARD -->
            <section id="view-dashboard" class="tab-view space-y-8 animate-fade-in">
                <div class="grid grid-cols-4 gap-6">
                    <div class="bg-white p-8 rounded-[32px] border border-slate-200 shadow-sm flex items-center gap-6">
                        <div class="w-14 h-14 bg-emerald-50 text-emerald-600 rounded-2xl flex items-center justify-center"><i data-lucide="warehouse" class="w-7 h-7"></i></div>
                        <div><p class="text-xs font-black text-slate-400 uppercase tracking-widest mb-1">Main Store Stock</p><h4 class="text-3xl font-black text-slate-900" id="stat-main">0</h4></div>
                    </div>
                    <div class="bg-white p-8 rounded-[32px] border border-slate-200 shadow-sm flex items-center gap-6">
                        <div class="w-14 h-14 bg-emerald-50 text-emerald-600 rounded-2xl flex items-center justify-center"><i data-lucide="pill" class="w-7 h-7"></i></div>
                        <div><p class="text-xs font-black text-slate-400 uppercase tracking-widest mb-1">Pharmacy Units</p><h4 class="text-3xl font-black text-slate-900" id="stat-pharmacy">0</h4></div>
                    </div>
                    <div class="bg-white p-8 rounded-[32px] border border-slate-200 shadow-sm flex items-center gap-6">
                        <div class="w-14 h-14 bg-rose-50 text-rose-600 rounded-2xl flex items-center justify-center"><i data-lucide="alert-triangle" class="w-7 h-7"></i></div>
                        <div><p class="text-xs font-black text-slate-400 uppercase tracking-widest mb-1">Low Stock Alerts</p><h4 class="text-3xl font-black text-rose-600" id="stat-low">0</h4></div>
                    </div>
                    <div class="bg-white p-8 rounded-[32px] border border-slate-200 shadow-sm flex items-center gap-6">
                        <div class="w-14 h-14 bg-amber-50 text-amber-600 rounded-2xl flex items-center justify-center"><i data-lucide="shield-alert" class="w-7 h-7"></i></div>
                        <div><p class="text-xs font-black text-slate-400 uppercase tracking-widest mb-1">Safety Overrides</p><h4 class="text-3xl font-black text-amber-600" id="stat-overrides">0</h4></div>
                    </div>
                    <div class="bg-white p-8 rounded-[32px] border border-slate-200 shadow-sm flex items-center gap-6">
                        <div class="w-14 h-14 bg-blue-50 text-blue-600 rounded-2xl flex items-center justify-center"><i data-lucide="banknote" class="w-7 h-7"></i></div>
                        <div><p class="text-xs font-black text-slate-400 uppercase tracking-widest mb-1">Today's Revenue</p><h4 class="text-3xl font-black text-slate-900">₦<span id="stat-revenue">0</span></h4></div>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-8">
                    <div class="bg-white rounded-[40px] border border-slate-200 shadow-sm p-10">
                        <h3 class="text-xl font-black text-slate-900 mb-6 flex items-center gap-3">
                            <i data-lucide="alert-circle" class="text-rose-500 w-6 h-6"></i>
                            Critical Stock Alerts
                        </h3>
                        <div class="space-y-4" id="low-stock-list">
                            <!-- Injected -->
                        </div>
                    </div>
                    <div class="bg-white rounded-[40px] border border-slate-200 shadow-sm p-10">
                        <h3 class="text-xl font-black text-slate-900 mb-6">Recent Transfers</h3>
                        <div class="space-y-4" id="recent-movements">
                            <!-- Injected -->
                        </div>
                    </div>
                </div>
            </section>

            <!-- VIEW: MAIN STORE -->
            <section id="view-main-store" class="tab-view hidden space-y-8 animate-fade-in">
                <div class="bg-white rounded-[40px] border border-slate-200 shadow-sm overflow-hidden">
                    <div class="p-8 border-b border-slate-100 flex justify-between items-center">
                        <h3 class="text-xl font-black text-slate-900">Central Inventory (Main Store)</h3>
                        <div class="relative w-64">
                            <i data-lucide="search" class="absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 w-4 h-4"></i>
                            <input type="text" placeholder="Search store..." class="w-full pl-10 pr-4 py-2 bg-slate-50 rounded-xl border-0 focus:ring-2 focus:ring-emerald-500 text-sm font-bold">
                        </div>
                    </div>
                    <table class="w-full text-left">
                        <thead class="bg-slate-50 text-[10px] font-black text-slate-400 uppercase tracking-widest">
                            <tr>
                                <th class="px-8 py-5">Drug Name</th>
                                <th class="px-8 py-5">Supplier & Batch</th>
                                <th class="px-8 py-5">Quantity</th>
                                <th class="px-8 py-5">Cost (Unit/Total)</th>
                                <th class="px-8 py-5">Expiry</th>
                                <th class="px-8 py-5 text-right">Action</th>
                            </tr>
                        </thead>
                        <tbody id="main-store-body" class="divide-y divide-slate-100">
                            <!-- Injected -->
                        </tbody>
                    </table>
                </div>
            </section>

            <!-- VIEW: PHARMACY STOCK -->
            <section id="view-pharmacy-stock" class="tab-view hidden space-y-8 animate-fade-in">
                <div class="bg-white rounded-[40px] border border-slate-200 shadow-sm overflow-hidden">
                    <div class="p-8 border-b border-slate-100 flex justify-between items-center">
                        <h3 class="text-xl font-black text-slate-900">Pharmacy Dispensing Unit</h3>
                        <div class="flex gap-4">
                            <button onclick="openPharmacyStockModal()" class="px-5 py-2.5 bg-slate-900 text-white rounded-xl text-xs font-black uppercase tracking-widest hover:bg-emerald-600 transition-all flex items-center gap-2">
                                <i data-lucide="plus" class="w-4 h-4"></i> Manual Stock Entry
                            </button>
                            <button onclick="syncAlerts()" class="px-4 py-2 bg-white border border-slate-200 text-slate-600 rounded-xl text-[10px] font-black uppercase tracking-widest hover:bg-slate-50 transition-all">Sync Alerts</button>
                        </div>
                    </div>
                    <table class="w-full text-left">
                        <thead class="bg-slate-50 text-[10px] font-black text-slate-400 uppercase tracking-widest">
                            <tr>
                                <th class="px-8 py-5">Drug Name</th>
                                <th class="px-8 py-5">Available Qty</th>
                                <th class="px-8 py-5">Expiry Date</th>
                                <th class="px-8 py-5">Selling Price</th>
                                <th class="px-8 py-5">Status</th>
                                <th class="px-8 py-5 text-right">Action</th>
                            </tr>
                        </thead>
                        <tbody id="pharmacy-stock-body" class="divide-y divide-slate-100">
                            <!-- Injected -->
                        </tbody>
                    </table>
                </div>
            </section>

            <!-- VIEW: MOVEMENTS -->
            <section id="view-movements" class="tab-view hidden space-y-8 animate-fade-in">
                <div class="bg-white rounded-[40px] border border-slate-200 shadow-sm overflow-hidden">
                    <div class="p-8 border-b border-slate-100">
                        <h3 class="text-xl font-black text-slate-900">Movement & Transfer Logs</h3>
                    </div>
                    <table class="w-full text-left">
                        <thead class="bg-slate-50 text-[10px] font-black text-slate-400 uppercase tracking-widest">
                            <tr>
                                <th class="px-8 py-5">Date/Time</th>
                                <th class="px-8 py-5">Drug Item</th>
                                <th class="px-8 py-5">Quantity Moved</th>
                                <th class="px-8 py-5">Route</th>
                                <th class="px-8 py-5">User</th>
                            </tr>
                        </thead>
                        <tbody id="movements-body" class="divide-y divide-slate-100">
                            <!-- Injected -->
                        </tbody>
                    </table>
                </div>
            </section>

        </div>
    </main>

    <!-- MODAL: NEW PURCHASE (MAIN STORE) -->
    <div id="purchaseModal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-50 hidden flex items-center justify-center p-4 sm:p-6">
        <div class="bg-white rounded-[40px] w-full max-w-2xl p-6 sm:p-10 shadow-2xl animate-fade-in max-h-[90vh] overflow-y-auto custom-scrollbar">
            <div class="flex justify-between items-start mb-8">
                <div>
                    <h3 class="text-2xl font-black text-slate-900">New Supplier Purchase</h3>
                    <p class="text-xs font-bold text-slate-400 uppercase tracking-widest">Add stock to Main Store</p>
                </div>
                <button onclick="closeModal('purchaseModal')" class="p-2 text-slate-400 hover:text-slate-900 transition-colors">
                    <i data-lucide="x" class="w-6 h-6"></i>
                </button>
            </div>
            <form id="purchase-form" class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="space-y-2">
                    <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-4">Drug Name</label>
                    <input type="text" name="drug_name" required class="w-full px-6 py-4 bg-slate-50 rounded-2xl border-0 font-bold focus:ring-2 focus:ring-emerald-500">
                </div>
                <div class="space-y-2">
                    <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-4">Strength (e.g. 500mg)</label>
                    <input type="text" name="strength" placeholder="e.g. 500mg" required class="w-full px-6 py-4 bg-slate-50 rounded-2xl border-0 font-bold focus:ring-2 focus:ring-emerald-500">
                </div>
                <div class="space-y-2">
                    <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-4">Drug Type (Form)</label>
                    <select name="form_type" required class="w-full px-6 py-4 bg-slate-50 rounded-2xl border-0 font-bold focus:ring-2 focus:ring-emerald-500">
                        <option value="Tablet">Tablet</option>
                        <option value="Syrup">Syrup</option>
                        <option value="Injection">Injection</option>
                        <option value="Vaccine">Vaccine</option>
                        <option value="Cream">Cream</option>
                        <option value="IV Fluid">IV Fluid</option>
                    </select>
                </div>
                <div class="space-y-2">
                    <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-4">Category</label>
                    <select name="category" required class="w-full px-6 py-4 bg-slate-50 rounded-2xl border-0 font-bold focus:ring-2 focus:ring-emerald-500">
                        <option value="Analgesic">Analgesic</option>
                        <option value="Antibiotic">Antibiotic</option>
                        <option value="Antimalarial">Antimalarial</option>
                        <option value="Antihypertensive">Antihypertensive</option>
                        <option value="Supplement">Supplement</option>
                        <option value="Vaccine">Vaccine</option>
                    </select>
                </div>
                <div class="space-y-2">
                    <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-4">Standard Dose (Units)</label>
                    <input type="number" step="0.1" name="standard_dose" value="1" required class="w-full px-6 py-4 bg-slate-50 rounded-2xl border-0 font-bold focus:ring-2 focus:ring-emerald-500">
                </div>
                <div class="space-y-2">
                    <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-4">Max Dose per Day (Units)</label>
                    <input type="number" step="0.1" name="max_dose_per_day" value="4" required class="w-full px-6 py-4 bg-slate-50 rounded-2xl border-0 font-bold focus:ring-2 focus:ring-emerald-500">
                </div>
                <div class="space-y-2">
                    <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-4">Supplier</label>
                    <div class="flex gap-2">
                        <select name="supplier_id" id="supplier-select" required class="flex-1 px-6 py-4 bg-slate-50 rounded-2xl border-0 font-bold focus:ring-2 focus:ring-emerald-500">
                            <!-- Loaded via JS -->
                        </select>
                        <button type="button" onclick="openSupplierModal()" class="px-4 bg-slate-100 text-slate-600 rounded-2xl hover:bg-slate-200 transition-colors">
                            <i data-lucide="plus" class="w-5 h-5"></i>
                        </button>
                    </div>
                </div>
                <div class="space-y-2">
                    <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-4">Batch Number</label>
                    <input type="text" name="batch_number" required class="w-full px-6 py-4 bg-slate-50 rounded-2xl border-0 font-bold focus:ring-2 focus:ring-emerald-500">
                </div>
                <div class="space-y-2">
                    <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-4">Expiry Date</label>
                    <input type="date" name="expiry_date" required class="w-full px-6 py-4 bg-slate-50 rounded-2xl border-0 font-bold focus:ring-2 focus:ring-emerald-500">
                </div>
                <div class="space-y-2">
                    <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-4">Packaging (e.g. Box)</label>
                    <input type="text" name="packaging_type" placeholder="e.g. Box, Bottle" required class="w-full px-6 py-4 bg-slate-50 rounded-2xl border-0 font-bold focus:ring-2 focus:ring-emerald-500">
                </div>
                <div class="space-y-2">
                    <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-4">Base Unit (e.g. tab)</label>
                    <input type="text" name="unit" placeholder="e.g. tab, ml" required class="w-full px-6 py-4 bg-slate-50 rounded-2xl border-0 font-bold focus:ring-2 focus:ring-emerald-500">
                </div>
                <div class="space-y-2">
                    <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-4">Packs Received</label>
                    <input type="number" name="quantity" id="p-qty" required class="w-full px-6 py-4 bg-slate-50 rounded-2xl border-0 font-bold focus:ring-2 focus:ring-emerald-500">
                </div>
                <div class="space-y-2">
                    <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-4">Units Per Pack</label>
                    <input type="number" name="units_per_pack" value="1" id="p-upp" required class="w-full px-6 py-4 bg-slate-50 rounded-2xl border-0 font-bold focus:ring-2 focus:ring-emerald-500">
                </div>
                <div class="space-y-2">
                    <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-4">Selling Price per Pack (₦)</label>
                    <input type="number" step="0.01" name="price_per_pack" required class="w-full px-6 py-4 bg-slate-50 rounded-2xl border-0 font-bold focus:ring-2 focus:ring-emerald-500">
                </div>
                <div class="space-y-2">
                    <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-4">Critical Alert Level (Packs)</label>
                    <input type="number" name="reorder_level" value="10" required class="w-full px-6 py-4 bg-slate-50 rounded-2xl border-0 font-bold focus:ring-2 focus:ring-emerald-500">
                </div>
                <div class="space-y-2">
                    <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-4">Total Purchase Cost (₦)</label>
                    <input type="number" step="0.01" name="total_cost_price" id="p-total" required class="w-full px-6 py-4 bg-slate-50 rounded-2xl border-0 font-bold focus:ring-2 focus:ring-emerald-500">
                </div>
                <div class="md:col-span-2 p-4 bg-emerald-50 rounded-2xl border border-emerald-100 flex justify-between items-center">
                    <div>
                        <p class="text-[10px] font-black text-emerald-600 uppercase tracking-widest mb-1">Unit Cost Calculation</p>
                        <p class="text-lg font-black text-slate-900">₦ <span id="p-unit-cost">0.00</span> per pack</p>
                    </div>
                    <div class="text-right">
                        <p class="text-[10px] font-black text-emerald-600 uppercase tracking-widest mb-1">Total Base Units</p>
                        <p class="text-lg font-black text-slate-900"><span id="p-total-base">0</span> units</p>
                    </div>
                </div>
                <div class="md:col-span-2 flex gap-4 pt-4">
                    <button type="button" onclick="closeModal('purchaseModal')" class="flex-1 py-4 bg-slate-100 text-slate-500 rounded-2xl font-bold hover:bg-slate-200 transition-colors">Cancel</button>
                    <button type="submit" class="flex-1 py-4 bg-emerald-600 text-white rounded-2xl font-black uppercase text-xs tracking-widest shadow-lg shadow-emerald-100 hover:bg-emerald-700 transition-all">Save to Main Store</button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL: MOVE STOCK -->
    <div id="moveModal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-50 hidden flex items-center justify-center p-4 sm:p-6">
        <div class="bg-white rounded-[40px] w-full max-w-md p-6 sm:p-10 shadow-2xl animate-fade-in max-h-[90vh] overflow-y-auto custom-scrollbar">
            <div class="flex justify-between items-start mb-8">
                <div>
                    <h3 class="text-2xl font-black text-slate-900">Transfer Stock</h3>
                    <p class="text-xs font-bold text-slate-400 uppercase tracking-widest">Main Store → Pharmacy</p>
                </div>
                <button onclick="closeModal('moveModal')" class="p-2 text-slate-400 hover:text-slate-900 transition-colors">
                    <i data-lucide="x" class="w-6 h-6"></i>
                </button>
            </div>
            <div class="p-6 bg-slate-50 rounded-3xl border border-slate-100 mb-8 flex justify-between items-center">
                <div>
                    <p id="move-drug-name" class="font-black text-slate-900 mb-1">---</p>
                    <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Available: <span id="move-available" class="text-emerald-600">0</span> Packs</p>
                </div>
                <div class="text-right">
                    <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Pack Size</p>
                    <p id="move-pack-size" class="font-black text-slate-900">---</p>
                </div>
            </div>
            <form id="move-form" class="space-y-6">
                <input type="hidden" name="main_store_item_id" id="move-item-id">
                <div class="space-y-2">
                    <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-4">Number of Packs to Move</label>
                    <input type="number" name="quantity" id="move-qty" required class="w-full px-6 py-4 bg-slate-50 rounded-2xl border-0 font-bold focus:ring-2 focus:ring-emerald-500">
                </div>
                <div class="p-4 bg-emerald-50 rounded-2xl border border-emerald-100">
                    <p class="text-[10px] font-black text-emerald-600 uppercase tracking-widest mb-1">Conversion Result</p>
                    <p class="text-lg font-black text-slate-900"><span id="move-total-units">0</span> <span id="move-base-unit-label">units</span></p>
                    <p class="text-[9px] text-emerald-500 italic">This many base units will be added to Pharmacy shelf.</p>
                </div>
                <div class="space-y-2">
                    <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-4">Selling Price per Base Unit (₦)</label>
                    <input type="number" step="0.01" name="selling_price" required class="w-full px-6 py-4 bg-slate-50 rounded-2xl border-0 font-bold focus:ring-2 focus:ring-emerald-500">
                </div>
                <div class="flex gap-4 pt-4">
                    <button type="button" onclick="closeModal('moveModal')" class="flex-1 py-4 bg-slate-100 text-slate-500 rounded-2xl font-bold hover:bg-slate-200 transition-colors">Cancel</button>
                    <button type="submit" class="flex-1 py-4 bg-emerald-600 text-white rounded-2xl font-black uppercase text-xs tracking-widest shadow-lg shadow-emerald-100 hover:bg-emerald-700 transition-all">Confirm Transfer</button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL: ADD SUPPLIER -->
    <div id="supplierModal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-[60] hidden flex items-center justify-center p-4 sm:p-6">
        <div class="bg-white rounded-[40px] w-full max-w-md p-6 sm:p-10 shadow-2xl animate-fade-in">
            <div class="flex justify-between items-start mb-8">
                <div>
                    <h3 class="text-2xl font-black text-slate-900">New Supplier</h3>
                    <p class="text-xs font-bold text-slate-400 uppercase tracking-widest">Add to directory</p>
                </div>
                <button onclick="closeModal('supplierModal')" class="p-2 text-slate-400 hover:text-slate-900 transition-colors">
                    <i data-lucide="x" class="w-6 h-6"></i>
                </button>
            </div>
            <form id="supplier-form" class="space-y-6">
                <div class="space-y-2">
                    <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-4">Supplier Name</label>
                    <input type="text" name="name" required class="w-full px-6 py-4 bg-slate-50 rounded-2xl border-0 font-bold focus:ring-2 focus:ring-emerald-500">
                </div>
                <div class="space-y-2">
                    <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-4">Contact Person</label>
                    <input type="text" name="contact" class="w-full px-6 py-4 bg-slate-50 rounded-2xl border-0 font-bold focus:ring-2 focus:ring-emerald-500">
                </div>
                <div class="space-y-2">
                    <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-4">Phone Number</label>
                    <input type="text" name="phone" class="w-full px-6 py-4 bg-slate-50 rounded-2xl border-0 font-bold focus:ring-2 focus:ring-emerald-500">
                </div>
                <div class="flex gap-4 pt-4">
                    <button type="button" onclick="closeModal('supplierModal')" class="flex-1 py-4 bg-slate-100 text-slate-500 rounded-2xl font-bold hover:bg-slate-200 transition-colors">Cancel</button>
                    <button type="submit" class="flex-1 py-4 bg-emerald-600 text-white rounded-2xl font-black uppercase text-xs tracking-widest shadow-lg shadow-emerald-100 hover:bg-emerald-700 transition-all">Save Supplier</button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL: MANUAL PHARMACY ENTRY -->
    <div id="pharmacyStockModal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-50 hidden flex items-center justify-center p-4 sm:p-6">
        <div class="bg-white rounded-[40px] w-full max-w-2xl p-6 sm:p-10 shadow-2xl animate-fade-in max-h-[90vh] overflow-y-auto custom-scrollbar">
            <div class="flex justify-between items-start mb-8">
                <div>
                    <h3 class="text-2xl font-black text-slate-900">Manual Stock Entry</h3>
                    <p class="text-xs font-bold text-slate-400 uppercase tracking-widest">Add directly to Pharmacy Shelf</p>
                </div>
                <button onclick="closeModal('pharmacyStockModal')" class="p-2 text-slate-400 hover:text-slate-900 transition-colors">
                    <i data-lucide="x" class="w-6 h-6"></i>
                </button>
            </div>
            <form id="pharmacy-stock-form" class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="space-y-2">
                    <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-4">Drug Name</label>
                    <input type="text" name="drug_name" required class="w-full px-6 py-4 bg-slate-50 rounded-2xl border-0 font-bold focus:ring-2 focus:ring-emerald-500">
                </div>
                <div class="space-y-2">
                    <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-4">Strength (e.g. 500mg)</label>
                    <input type="text" name="strength" placeholder="e.g. 500mg" required class="w-full px-6 py-4 bg-slate-50 rounded-2xl border-0 font-bold focus:ring-2 focus:ring-emerald-500">
                </div>
                <div class="space-y-2">
                    <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-4">Drug Type (Form)</label>
                    <select name="form_type" required class="w-full px-6 py-4 bg-slate-50 rounded-2xl border-0 font-bold focus:ring-2 focus:ring-emerald-500">
                        <option value="Tablet">Tablet</option>
                        <option value="Syrup">Syrup</option>
                        <option value="Injection">Injection</option>
                        <option value="Vaccine">Vaccine</option>
                        <option value="Cream">Cream</option>
                        <option value="IV Fluid">IV Fluid</option>
                    </select>
                </div>
                <div class="space-y-2">
                    <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-4">Category</label>
                    <select name="category" required class="w-full px-6 py-4 bg-slate-50 rounded-2xl border-0 font-bold focus:ring-2 focus:ring-emerald-500">
                        <option value="Analgesic">Analgesic</option>
                        <option value="Antibiotic">Antibiotic</option>
                        <option value="Antimalarial">Antimalarial</option>
                        <option value="Antihypertensive">Antihypertensive</option>
                        <option value="Supplement">Supplement</option>
                        <option value="Vaccine">Vaccine</option>
                    </select>
                </div>
                <div class="space-y-2">
                    <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-4">Standard Dose (Units)</label>
                    <input type="number" step="0.1" name="standard_dose" value="1" required class="w-full px-6 py-4 bg-slate-50 rounded-2xl border-0 font-bold focus:ring-2 focus:ring-emerald-500">
                </div>
                <div class="space-y-2">
                    <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-4">Max Dose per Day (Units)</label>
                    <input type="number" step="0.1" name="max_dose_per_day" value="4" required class="w-full px-6 py-4 bg-slate-50 rounded-2xl border-0 font-bold focus:ring-2 focus:ring-emerald-500">
                </div>
                <div class="space-y-2">
                    <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-4">Unit (e.g. tablet)</label>
                    <input type="text" name="unit" placeholder="e.g. tablet" required class="w-full px-6 py-4 bg-slate-50 rounded-2xl border-0 font-bold focus:ring-2 focus:ring-emerald-500">
                </div>
                <div class="space-y-2">
                    <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-4">Quantity</label>
                    <input type="number" name="quantity" required class="w-full px-6 py-4 bg-slate-50 rounded-2xl border-0 font-bold focus:ring-2 focus:ring-emerald-500">
                </div>
                <div class="space-y-2">
                    <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-4">Selling Price (₦)</label>
                    <input type="number" step="0.01" name="selling_price" required class="w-full px-6 py-4 bg-slate-50 rounded-2xl border-0 font-bold focus:ring-2 focus:ring-emerald-500">
                </div>
                <div class="space-y-2">
                    <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-4">Expiry Date</label>
                    <input type="date" name="expiry_date" required class="w-full px-6 py-4 bg-slate-50 rounded-2xl border-0 font-bold focus:ring-2 focus:ring-emerald-500">
                </div>
                <div class="space-y-2">
                    <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-4">Critical Alert Level</label>
                    <input type="number" name="reorder_level" value="10" required class="w-full px-6 py-4 bg-slate-50 rounded-2xl border-0 font-bold focus:ring-2 focus:ring-emerald-500">
                </div>
                <div class="md:col-span-2 flex gap-4 pt-4">
                    <button type="button" onclick="closeModal('pharmacyStockModal')" class="flex-1 py-4 bg-slate-100 text-slate-500 rounded-2xl font-bold hover:bg-slate-200 transition-colors">Cancel</button>
                    <button type="submit" class="flex-1 py-4 bg-emerald-600 text-white rounded-2xl font-black uppercase text-xs tracking-widest shadow-lg shadow-emerald-100 hover:bg-emerald-700 transition-all">Save to Pharmacy</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openSupplierModal() {
            document.getElementById('supplierModal').classList.remove('hidden');
        }

        function openPurchaseModal() {
            console.log('Opening Purchase Modal...');
            const modal = document.getElementById('purchaseModal');
            if (!modal) {
                console.error('purchaseModal not found');
                return;
            }
            
            fetch('../api/pharmacy_v2.php?action=get_suppliers')
            .then(r => r.json()).then(data => {
                if(data.success) {
                    const select = document.getElementById('supplier-select');
                    if (select) {
                        select.innerHTML = data.suppliers.map(s => `<option value="${s.id}">${s.name}</option>`).join('');
                    }
                }
                modal.classList.remove('hidden');
            }).catch(err => {
                console.error('Fetch error:', err);
                modal.classList.remove('hidden');
            });
        }

        function openPharmacyStockModal() {
            document.getElementById('pharmacyStockModal').classList.remove('hidden');
        }

        function closeModal(id) {
            const modal = document.getElementById(id);
            if (modal) {
                modal.classList.add('hidden');
            }
        }

        let currentTab = 'dashboard';

        function showTab(tab) {
            document.querySelectorAll('.tab-view').forEach(v => v.classList.add('hidden'));
            document.getElementById('view-' + tab).classList.remove('hidden');
            
            const titles = {
                'dashboard': 'Pharmacy Dashboard', 
                'main-store': 'Transfer Stock to Pharmacy', 
                'pharmacy-stock': 'Pharmacy Stock (Dispensing)', 
                'movements': 'Movement Logs & Audit'
            };
            document.getElementById('view-title').textContent = titles[tab];
            currentTab = tab;

            // Toggle "New Purchase" button visibility
            const purchaseBtn = document.getElementById('header-new-purchase-btn');
            if (tab === 'main-store' || tab === 'dashboard') {
                purchaseBtn.classList.remove('hidden');
            } else {
                purchaseBtn.classList.add('hidden');
            }
            
            if (tab === 'dashboard') loadStats();
            if (tab === 'main-store') loadMainStore();
            if (tab === 'pharmacy-stock') loadPharmacyStock();
            if (tab === 'movements') loadMovements();
            
            if (typeof lucide !== 'undefined') lucide.createIcons();
        }
    </script>

    <!-- MODAL: EDIT ALERT LEVEL -->
    <div id="alertModal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-50 hidden flex items-center justify-center p-4 sm:p-6">
        <div class="bg-white rounded-[40px] w-full max-w-md p-6 sm:p-10 shadow-2xl animate-fade-in">
            <div class="flex justify-between items-start mb-8">
                <div>
                    <h3 class="text-2xl font-black text-slate-900">Set Alert Level</h3>
                    <p class="text-xs font-bold text-slate-400 uppercase tracking-widest">When to show "Low Stock"</p>
                </div>
                <button onclick="closeModal('alertModal')" class="p-2 text-slate-400 hover:text-slate-900 transition-colors">
                    <i data-lucide="x" class="w-6 h-6"></i>
                </button>
            </div>
            <form id="alert-form" class="space-y-6">
                <input type="hidden" name="id" id="alert-item-id">
                <input type="hidden" name="type" id="alert-item-type">
                <div class="p-4 bg-slate-50 rounded-2xl border border-slate-100">
                    <p id="alert-drug-name" class="font-black text-slate-900 mb-1">---</p>
                    <p id="alert-location" class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">---</p>
                </div>
                <div class="space-y-2">
                    <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-4">Critical Threshold (Units)</label>
                    <input type="number" name="level" id="alert-level-input" required class="w-full px-6 py-4 bg-slate-50 rounded-2xl border-0 font-bold focus:ring-2 focus:ring-emerald-500">
                    <p class="text-[10px] text-slate-400 italic px-4">An alert will trigger when stock falls below this number.</p>
                </div>
                <div class="flex gap-4 pt-4">
                    <button type="button" onclick="closeModal('alertModal')" class="flex-1 py-4 bg-slate-100 text-slate-500 rounded-2xl font-bold hover:bg-slate-200 transition-colors">Cancel</button>
                    <button type="submit" class="flex-1 py-4 bg-emerald-600 text-white rounded-2xl font-black uppercase text-xs tracking-widest shadow-lg shadow-emerald-100 hover:bg-emerald-700 transition-all">Save Threshold</button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL: STOCK ADJUSTMENT (CORRECT ERRORS) -->
    <div id="adjustmentModal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-50 hidden flex items-center justify-center p-4 sm:p-6">
        <div class="bg-white rounded-[40px] w-full max-w-md p-6 sm:p-10 shadow-2xl animate-fade-in max-h-[90vh] overflow-y-auto custom-scrollbar">
            <div class="flex justify-between items-start mb-8">
                <div>
                    <h3 class="text-2xl font-black text-slate-900">Stock Adjustment</h3>
                    <p class="text-xs font-bold text-slate-400 uppercase tracking-widest">Correct entry errors</p>
                </div>
                <button onclick="closeModal('adjustmentModal')" class="p-2 text-slate-400 hover:text-slate-900 transition-colors">
                    <i data-lucide="x" class="w-6 h-6"></i>
                </button>
            </div>
            <form id="adjustment-form" class="space-y-6">
                <input type="hidden" name="item_id" id="adj-item-id">
                <input type="hidden" name="type" id="adj-item-type">
                
                <div class="p-4 bg-amber-50 rounded-2xl border border-amber-100 mb-4">
                    <p id="adj-drug-name" class="font-black text-slate-900 mb-1">---</p>
                    <p id="adj-location" class="text-[10px] font-bold text-amber-600 uppercase tracking-widest">AUDITED CHANGE</p>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div class="space-y-2">
                        <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-4">Correct Quantity</label>
                        <input type="number" name="quantity" id="adj-qty" required class="w-full px-6 py-4 bg-slate-50 rounded-2xl border-0 font-bold focus:ring-2 focus:ring-amber-500">
                    </div>
                    <div class="space-y-2">
                        <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-4">Correct Price (₦)</label>
                        <input type="number" step="0.01" name="price" id="adj-price" required class="w-full px-6 py-4 bg-slate-50 rounded-2xl border-0 font-bold focus:ring-2 focus:ring-amber-500">
                    </div>
                </div>

                <div class="space-y-2">
                    <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-4">Drug Type (Form)</label>
                    <select name="form_type" id="adj-form-type" required class="w-full px-6 py-4 bg-slate-50 rounded-2xl border-0 font-bold focus:ring-2 focus:ring-amber-500">
                        <option value="Tablet">Tablet</option>
                        <option value="Syrup">Syrup</option>
                        <option value="Injection">Injection</option>
                        <option value="Vaccine">Vaccine</option>
                        <option value="Cream">Cream</option>
                        <option value="IV Fluid">IV Fluid</option>
                    </select>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div class="space-y-2">
                        <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-4">Standard Dose</label>
                        <input type="number" step="0.1" name="standard_dose" id="adj-std-dose" required class="w-full px-6 py-4 bg-slate-50 rounded-2xl border-0 font-bold focus:ring-2 focus:ring-amber-500">
                    </div>
                    <div class="space-y-2">
                        <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-4">Max Dose / Day</label>
                        <input type="number" step="0.1" name="max_dose_per_day" id="adj-max-dose" required class="w-full px-6 py-4 bg-slate-50 rounded-2xl border-0 font-bold focus:ring-2 focus:ring-amber-500">
                    </div>
                </div>

                <div class="space-y-2">
                    <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-4">Correct Expiry Date</label>
                    <input type="date" name="expiry_date" id="adj-expiry" required class="w-full px-6 py-4 bg-slate-50 rounded-2xl border-0 font-bold focus:ring-2 focus:ring-amber-500">
                </div>

                <div class="space-y-2">
                    <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-4">Reason for Adjustment</label>
                    <textarea name="reason" required placeholder="e.g., Wrong quantity entered during purchase..." class="w-full px-6 py-4 bg-slate-50 rounded-2xl border-0 font-bold focus:ring-2 focus:ring-amber-500 h-24 resize-none"></textarea>
                </div>

                <div class="flex gap-4 pt-4">
                    <button type="button" onclick="closeModal('adjustmentModal')" class="flex-1 py-4 bg-slate-100 text-slate-500 rounded-2xl font-bold hover:bg-slate-200 transition-colors">Cancel</button>
                    <button type="submit" class="flex-1 py-4 bg-amber-600 text-white rounded-2xl font-black uppercase text-xs tracking-widest shadow-lg shadow-amber-100 hover:bg-amber-700 transition-all">Apply Correction</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openAdjustmentModal(id, type, name, currentQty, currentPrice, currentExpiry, formType, stdDose, maxDose) {
            document.getElementById('adj-item-id').value = id;
            document.getElementById('adj-item-type').value = type;
            document.getElementById('adj-drug-name').textContent = name;
            document.getElementById('adj-qty').value = currentQty;
            document.getElementById('adj-price').value = currentPrice;
            document.getElementById('adj-expiry').value = currentExpiry;
            document.getElementById('adj-form-type').value = formType || 'Tablet';
            document.getElementById('adj-std-dose').value = stdDose || 1.0;
            document.getElementById('adj-max-dose').value = maxDose || 4.0;
            document.getElementById('adj-location').textContent = type === 'main' ? 'MAIN STORE ADJUSTMENT' : 'PHARMACY STOCK ADJUSTMENT';
            document.getElementById('adjustmentModal').classList.remove('hidden');
        }

        document.getElementById('adjustment-form').onsubmit = (e) => {
            e.preventDefault();
            const fd = new FormData(e.target);
            fetch('../api/pharmacy_v2.php?action=adjust_stock', { method: 'POST', body: fd })
            .then(r => r.json()).then(data => {
                if(data.success) { 
                    closeModal('adjustmentModal'); 
                    loadStats(); 
                    if(currentTab === 'main-store') loadMainStore();
                    if(currentTab === 'pharmacy-stock') loadPharmacyStock();
                    alert('Adjustment recorded and audit log updated.');
                }
                else alert(data.message);
            });
        };

        function openAlertModal(id, name, location, currentLevel) {
            document.getElementById('alert-item-id').value = id;
            document.getElementById('alert-item-type').value = location === 'Main Store' ? 'main' : 'pharmacy';
            document.getElementById('alert-drug-name').textContent = name;
            document.getElementById('alert-location').textContent = location;
            document.getElementById('alert-level-input').value = currentLevel;
            document.getElementById('alertModal').classList.remove('hidden');
        }

        document.getElementById('alert-form').onsubmit = (e) => {
            e.preventDefault();
            const fd = new FormData(e.target);
            fetch('../api/pharmacy_v2.php?action=update_reorder_level', { method: 'POST', body: fd })
            .then(r => r.json()).then(data => {
                if(data.success) { 
                    closeModal('alertModal'); 
                    loadStats(); 
                    if(currentTab === 'main-store') loadMainStore();
                    if(currentTab === 'pharmacy-stock') loadPharmacyStock();
                }
                else alert(data.message);
            });
        };

        function loadStats() {
            fetch('../api/pharmacy_v2.php?action=get_stats')
            .then(r => r.json()).then(data => {
                if(data.success) {
                    document.getElementById('stat-main').textContent = data.stats.main_store_count;
                    document.getElementById('stat-pharmacy').textContent = data.stats.pharmacy_count;
                    document.getElementById('stat-low').textContent = data.stats.low_stock_count;
                    document.getElementById('stat-overrides').textContent = data.stats.safety_overrides_today || 0;
                    document.getElementById('stat-revenue').textContent = data.stats.today_revenue.toLocaleString();

                    const alertList = document.getElementById('low-stock-list');
                    if(data.stats.low_stock_items.length > 0) {
                        alertList.innerHTML = data.stats.low_stock_items.map(item => `
                            <div class="flex items-center justify-between p-4 bg-rose-50 rounded-2xl border border-rose-100 animate-pulse">
                                <div class="flex items-center gap-4">
                                    <div class="w-10 h-10 bg-white rounded-xl flex items-center justify-center text-rose-500 shadow-sm"><i data-lucide="alert-triangle" class="w-4 h-4"></i></div>
                                    <div>
                                        <p class="font-black text-slate-900 text-sm">${item.drug_name}</p>
                                        <p class="text-[9px] font-bold text-slate-400 uppercase tracking-widest">${item.location} • Level: ${item.reorder_level}</p>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <p class="font-black text-rose-600">${item.quantity} units</p>
                                    <p class="text-[9px] font-bold text-rose-400 uppercase">Left</p>
                                </div>
                            </div>
                        `).join('');
                    } else {
                        alertList.innerHTML = `
                            <div class="p-10 text-center bg-slate-50 rounded-[32px] border border-dashed border-slate-200">
                                <i data-lucide="check-circle" class="w-8 h-8 text-emerald-500 mx-auto mb-3"></i>
                                <p class="text-sm font-bold text-slate-400">All stock levels are healthy.</p>
                            </div>
                        `;
                    }
                    if (typeof lucide !== 'undefined') lucide.createIcons();
                }
            });
            
            fetch('../api/pharmacy_v2.php?action=get_movements')
            .then(r => r.json()).then(data => {
                if(data.success) {
                    const html = data.movements.slice(0, 5).map(m => `
                        <div class="flex items-center justify-between p-4 bg-slate-50 rounded-2xl border border-slate-100">
                            <div class="flex items-center gap-4">
                                <div class="w-10 h-10 bg-white rounded-xl flex items-center justify-center text-emerald-500 shadow-sm"><i data-lucide="arrow-right" class="w-4 h-4"></i></div>
                                <div><p class="font-black text-slate-900 text-sm">${m.drug_name}</p><p class="text-[9px] font-bold text-slate-400 uppercase tracking-widest">${new Date(m.movement_date).toLocaleString()}</p></div>
                            </div>
                            <div class="text-right"><p class="font-black text-emerald-600">${m.quantity} units</p><p class="text-[9px] font-bold text-slate-400 uppercase">To Pharmacy</p></div>
                        </div>
                    `).join('');
                    document.getElementById('recent-movements').innerHTML = html || '<p class="text-center text-slate-400 italic">No movements recorded yet.</p>';
                    if (typeof lucide !== 'undefined') lucide.createIcons();
                }
            });
        }

        function loadMainStore() {
            fetch('../api/pharmacy_v2.php?action=get_main_store')
            .then(r => r.json()).then(data => {
                if(data.success) {
                    const html = data.inventory.map(i => `
                        <tr class="hover:bg-slate-50 transition-colors">
                            <td class="px-8 py-6">
                                <p class="font-black text-slate-900 uppercase tracking-tight">${i.drug_name}</p>
                                <div class="flex gap-2 mt-1">
                                    <span class="px-2 py-0.5 bg-slate-100 text-slate-500 rounded-lg text-[9px] font-black uppercase tracking-widest">${i.strength || 'No Strength'}</span>
                                    <span class="px-2 py-0.5 bg-blue-50 text-blue-600 rounded-lg text-[9px] font-black uppercase tracking-widest">${i.form_type || 'Tablet'}</span>
                                </div>
                            </td>
                            <td class="px-8 py-6">
                                <p class="text-sm font-bold text-slate-700">${i.supplier_name || 'N/A'}</p>
                                <p class="text-[9px] text-slate-400 font-black uppercase tracking-widest">BATCH: ${i.batch_number}</p>
                            </td>
                            <td class="px-8 py-6">
                                <div class="mb-1">
                                    <span class="px-3 py-1 ${i.quantity <= i.reorder_level ? 'bg-rose-50 text-rose-600 border-rose-100' : 'bg-emerald-50 text-emerald-600 border-emerald-100'} rounded-full font-black text-xs border">
                                        ${i.quantity} Packs
                                    </span>
                                </div>
                                <p class="text-[10px] text-slate-400 font-bold uppercase tracking-widest ml-1">Total: ${i.total_base_units} ${i.unit}s</p>
                            </td>
                            <td class="px-8 py-6">
                                <p class="text-sm font-black text-slate-900">₦${parseFloat(i.unit_cost_price).toFixed(2)}</p>
                                <p class="text-[9px] text-slate-400 font-bold uppercase">Total: ₦${parseFloat(i.total_cost_price).toLocaleString()}</p>
                            </td>
                            <td class="px-8 py-6">
                                <p class="text-sm font-bold ${new Date(i.expiry_date) < new Date() ? 'text-rose-500' : 'text-slate-600'}">${new Date(i.expiry_date).toLocaleDateString()}</p>
                                <p class="text-[9px] font-bold text-slate-400 uppercase tracking-widest">EXPIRY</p>
                            </td>
                            <td class="px-8 py-6 text-right space-x-2">
                                <button onclick="openAdjustmentModal(${i.id}, 'main', '${i.drug_name}', ${i.quantity}, ${i.unit_cost_price}, '${i.expiry_date}', '${i.form_type}', ${i.standard_dose}, ${i.max_dose_per_day})" class="p-2 text-slate-400 hover:text-amber-500 rounded-lg transition-all" title="Correct/Adjust Stock"><i data-lucide="edit-3" class="w-4 h-4"></i></button>
                                <button onclick="openAlertModal(${i.id}, '${i.drug_name}', 'Main Store', ${i.reorder_level})" class="p-2 text-slate-400 hover:text-emerald-500 rounded-lg transition-all" title="Set Alert Level"><i data-lucide="settings" class="w-4 h-4"></i></button>
                                <button onclick="openMoveModal(${i.id}, '${i.drug_name} ${i.strength || ''}', ${i.quantity}, ${i.units_per_pack}, '${i.unit}')" class="p-2 text-emerald-500 hover:bg-emerald-50 rounded-lg transition-all" title="Move to Pharmacy"><i data-lucide="share-2" class="w-5 h-5"></i></button>
                            </td>
                        </tr>
                    `).join('');
                    document.getElementById('main-store-body').innerHTML = html || '<tr><td colspan="6" class="p-20 text-center text-slate-400 italic">Main store is empty.</td></tr>';
                    if (typeof lucide !== 'undefined') lucide.createIcons();
                }
            });
        }

        function loadPharmacyStock() {
            fetch('../api/pharmacy_v2.php?action=get_pharmacy_stock')
            .then(r => r.json()).then(data => {
                if(data.success) {
                    const html = data.stock.map(s => `
                        <tr class="hover:bg-slate-50 transition-colors">
                            <td class="px-8 py-6">
                                <p class="font-black text-slate-900 uppercase tracking-tight">${s.drug_name}</p>
                                <div class="flex gap-2 mt-1">
                                    <span class="px-2 py-0.5 bg-slate-100 text-slate-500 rounded-lg text-[9px] font-black uppercase tracking-widest">${s.strength || 'No Strength'}</span>
                                    <span class="px-2 py-0.5 bg-blue-50 text-blue-600 rounded-lg text-[9px] font-black uppercase tracking-widest">${s.form_type || 'Tablet'}</span>
                                </div>
                            </td>
                            <td class="px-8 py-6"><span class="font-black text-slate-900">${s.quantity}</span> <span class="text-[10px] text-slate-400 font-bold uppercase">${s.unit || s.base_unit}</span></td>
                            <td class="px-8 py-6">
                                <p class="text-sm font-bold ${new Date(s.expiry_date) < new Date() ? 'text-rose-500' : 'text-slate-600'}">${s.expiry_date || 'N/A'}</p>
                            </td>
                            <td class="px-8 py-6 font-black text-emerald-600">₦${parseFloat(s.selling_price).toLocaleString()}</td>
                            <td class="px-8 py-6">
                                ${s.quantity <= s.reorder_level ? 
                                    '<span class="px-3 py-1 bg-rose-50 text-rose-600 rounded-full text-[9px] font-black uppercase tracking-widest border border-rose-100">Low Stock</span>' : 
                                    '<span class="px-3 py-1 bg-emerald-50 text-emerald-600 rounded-full text-[9px] font-black uppercase tracking-widest border border-emerald-100">Healthy</span>'
                                }
                            </td>
                            <td class="px-8 py-6 text-right space-x-2">
                                <button onclick="openAdjustmentModal(${s.id}, 'pharmacy', '${s.drug_name}', ${s.quantity}, ${s.selling_price}, '${s.expiry_date}', '${s.form_type}', ${s.standard_dose}, ${s.max_dose_per_day})" class="p-2 text-slate-400 hover:text-amber-500 rounded-lg transition-all" title="Correct/Adjust Stock"><i data-lucide="edit-3" class="w-4 h-4"></i></button>
                                <button onclick="openAlertModal(${s.id}, '${s.drug_name}', 'Pharmacy', ${s.reorder_level})" class="p-2 text-slate-400 hover:text-emerald-500 rounded-lg transition-all" title="Set Alert Level"><i data-lucide="settings" class="w-4 h-4"></i></button>
                                <button onclick="restockAlert('${s.drug_name}')" class="px-4 py-2 bg-slate-50 text-slate-600 rounded-xl font-black text-[10px] uppercase tracking-widest hover:bg-emerald-600 hover:text-white transition-all">Refill</button>
                            </td>
                        </tr>
                    `).join('');
                    document.getElementById('pharmacy-stock-body').innerHTML = html || '<tr><td colspan="6" class="p-20 text-center text-slate-400 italic">No pharmacy stock available.</td></tr>';
                    if (typeof lucide !== 'undefined') lucide.createIcons();
                }
            });
        }

        function syncAlerts() {
            fetch('../api/pharmacy_v2.php?action=check_sync_alerts')
            .then(r => r.json()).then(data => {
                alert(data.message);
                loadStats();
            });
        }

        function loadMovements() {
            fetch('../api/pharmacy_v2.php?action=get_movements')
            .then(r => r.json()).then(data => {
                if(data.success) {
                    const html = data.movements.map(m => `
                        <tr>
                            <td class="px-8 py-6 text-sm font-medium text-slate-600">${new Date(m.movement_date).toLocaleString()}</td>
                            <td class="px-8 py-6 font-black text-slate-900">${m.drug_name}</td>
                            <td class="px-8 py-6 font-black text-emerald-600">${m.quantity} units</td>
                            <td class="px-8 py-6 text-[10px] font-black text-slate-400 uppercase tracking-widest">${m.from_location} → ${m.to_location}</td>
                            <td class="px-8 py-6 text-sm font-bold text-slate-700">User #${m.performed_by}</td>
                        </tr>
                    `).join('');
                    document.getElementById('movements-body').innerHTML = html;
                }
            });
        }

        let currentMoveUPP = 1;
        function openMoveModal(id, name, available, upp, baseUnit) {
            currentMoveUPP = upp || 1;
            document.getElementById('move-item-id').value = id;
            document.getElementById('move-drug-name').textContent = name;
            document.getElementById('move-available').textContent = available;
            document.getElementById('move-pack-size').textContent = currentMoveUPP + ' ' + (baseUnit || 'units') + '/pack';
            document.getElementById('move-base-unit-label').textContent = (baseUnit || 'units');
            document.getElementById('move-qty').value = '';
            document.getElementById('move-total-units').textContent = '0';
            document.getElementById('moveModal').classList.remove('hidden');
        }

        document.getElementById('move-qty').oninput = (e) => {
            const qty = parseFloat(e.target.value) || 0;
            document.getElementById('move-total-units').textContent = (qty * currentMoveUPP);
        };

        function restockAlert(name) {
            showTab('main-store');
        }

        document.getElementById('p-qty').oninput = calcUnitCost;
        document.getElementById('p-total').oninput = calcUnitCost;
        document.getElementById('p-upp').oninput = calcUnitCost;
        
        function calcUnitCost() {
            const qty = parseFloat(document.getElementById('p-qty').value) || 0;
            const upp = parseFloat(document.getElementById('p-upp').value) || 1;
            const total = parseFloat(document.getElementById('p-total').value) || 0;
            const packCost = qty > 0 ? (total / qty).toFixed(2) : '0.00';
            document.getElementById('p-unit-cost').textContent = packCost;
            document.getElementById('p-total-base').textContent = (qty * upp);
        }

        document.getElementById('purchase-form').onsubmit = (e) => {
            e.preventDefault();
            const fd = new FormData(e.target);
            fetch('../api/pharmacy_v2.php?action=add_to_main_store', { method: 'POST', body: fd })
            .then(r => r.json()).then(data => {
                if(data.success) { closeModal('purchaseModal'); loadMainStore(); loadStats(); }
                else alert(data.message);
            });
        };

        document.getElementById('supplier-form').onsubmit = (e) => {
            e.preventDefault();
            const fd = new FormData(e.target);
            fetch('../api/pharmacy_v2.php?action=add_supplier', { method: 'POST', body: fd })
            .then(r => r.json()).then(data => {
                if(data.success) { 
                    alert('Supplier added successfully.');
                    closeModal('supplierModal');
                    // Refresh suppliers list in purchase modal
                    fetch('../api/pharmacy_v2.php?action=get_suppliers')
                    .then(r => r.json()).then(sData => {
                        if(sData.success) {
                            const select = document.getElementById('supplier-select');
                            select.innerHTML = sData.suppliers.map(s => `<option value="${s.id}" ${s.id == data.id ? 'selected' : ''}>${s.name}</option>`).join('');
                        }
                    });
                } else {
                    alert(data.message);
                }
            });
        };

        document.getElementById('pharmacy-stock-form').onsubmit = (e) => {
            e.preventDefault();
            const fd = new FormData(e.target);
            fetch('../api/pharmacy_v2.php?action=add_pharmacy_stock', { method: 'POST', body: fd })
            .then(r => r.json()).then(data => {
                if(data.success) { closeModal('pharmacyStockModal'); loadPharmacyStock(); loadStats(); }
                else alert(data.message);
            });
        };

        document.getElementById('move-form').onsubmit = (e) => {
            e.preventDefault();
            const fd = new FormData(e.target);
            fetch('../api/pharmacy_v2.php?action=move_to_pharmacy', { method: 'POST', body: fd })
            .then(r => r.json()).then(data => {
                if(data.success) { closeModal('moveModal'); loadMainStore(); loadStats(); }
                else alert(data.message);
            });
        };

        const urlParams = new URLSearchParams(window.location.search);
        const tabParam = urlParams.get('tab');
        if (tabParam && ['dashboard', 'main-store', 'pharmacy-stock', 'movements'].includes(tabParam)) {
            showTab(tabParam);
        } else {
            showTab('dashboard');
        }
    </script>

    <?php include '../includes/portal_footer.php'; ?>
