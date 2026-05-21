<?php
// Get current page for active state
$current_page = basename($_SERVER['PHP_SELF']);
$current_tab = $_GET['tab'] ?? '';

// Count pending prescriptions for indicator
if (isset($conn)) {
    $pending_orders = $conn->query("SELECT COUNT(*) as count FROM telemedicine_prescriptions p LEFT JOIN pharmacy_dispensations d ON p.id = d.prescription_id WHERE d.id IS NULL")->fetch_assoc()['count'];
} else {
    $pending_orders = 0;
}

// Sidebar items definition
$menu_items = [
    ['label' => 'Dashboard', 'icon' => 'layout-dashboard', 'url' => 'dashboard.php', 'notif_id' => 'notif-badge'],
    ['label' => 'Transfer Stock', 'icon' => 'truck', 'url' => 'inventory.php?tab=main-store', 'tab' => 'main-store'],
    ['label' => 'Pharmacy Stock', 'icon' => 'pill', 'url' => 'inventory.php?tab=pharmacy-stock', 'tab' => 'pharmacy-stock'],
    ['label' => 'Audit Logs', 'icon' => 'history', 'url' => 'inventory.php?tab=movements', 'tab' => 'movements'],
    ['label' => 'Dispensations', 'icon' => 'clipboard-list', 'url' => 'dispensations.php'],
    ['label' => 'Order / Prescription', 'icon' => 'file-text', 'url' => 'prescriptions.php', 'badge' => $pending_orders]
];
?>

<aside id="sidebar" class="w-72 bg-white border-r border-slate-200 hidden lg:flex flex-col sticky top-0 h-screen shrink-0 z-40">
    <div class="p-8 flex flex-col h-full">
        <!-- Logo Section -->
        <div class="flex items-center gap-3 mb-10 shrink-0">
            <div class="w-10 h-10 bg-emerald-600 rounded-xl flex items-center justify-center text-white font-black shadow-lg shadow-emerald-200">P</div>
            <h1 class="text-xl font-black text-slate-900 tracking-tighter uppercase">PHARMA<span class="text-emerald-600 font-black">CORE</span></h1>
        </div>

        <!-- Navigation Section -->
        <nav class="space-y-1">
            <?php foreach ($menu_items as $item): ?>
                <?php 
                    $url_parts = parse_url($item['url']);
                    $url_base = basename($url_parts['path']);
                    
                    $isActive = ($current_page == $url_base);
                    
                    // Special handling for inventory tabs
                    if ($url_base == 'inventory.php') {
                        if (isset($item['tab'])) {
                            $isActive = ($current_page == 'inventory.php' && $current_tab == $item['tab']);
                        }
                    }

                    $activeClasses = $isActive 
                        ? 'bg-emerald-600 text-white shadow-lg shadow-emerald-200' 
                        : 'text-slate-500 hover:bg-slate-50 hover:text-emerald-600';
                ?>
                <a href="<?php echo $item['url']; ?>" 
                   class="flex items-center justify-between gap-4 px-5 py-4 rounded-2xl font-bold text-sm transition-all group <?php echo $activeClasses; ?>">
                    <div class="flex items-center gap-4">
                        <i data-lucide="<?php echo $item['icon']; ?>" class="w-5 h-5 shrink-0 transition-transform group-hover:scale-110"></i>
                        <span class="truncate"><?php echo $item['label']; ?></span>
                    </div>
                    <?php if (isset($item['badge']) || isset($item['notif_id'])): ?>
                        <span id="<?php echo $item['notif_id'] ?? ''; ?>" class="flex items-center justify-center min-w-[20px] h-5 px-1.5 bg-rose-500 text-white text-[10px] font-black rounded-full <?php echo (isset($item['badge']) && $item['badge'] > 0) ? ($isActive ? '' : 'animate-pulse') : 'hidden'; ?>">
                            <?php echo $item['badge'] ?? '0'; ?>
                        </span>
                    <?php endif; ?>
                </a>
            <?php endforeach; ?>
        </nav>

        <!-- Footer Section -->
        <div class="mt-auto pt-8 border-t border-slate-100 shrink-0">
            <div class="bg-slate-50 rounded-[32px] p-5 mb-6 border border-slate-100">
                <div class="flex items-center gap-3 mb-4">
                    <div class="w-10 h-10 bg-emerald-100 rounded-full flex items-center justify-center text-emerald-600 font-black">
                        <?php echo strtoupper(substr($_SESSION['pharmacist_name'] ?? 'P', 0, 1)); ?>
                    </div>
                    <div class="overflow-hidden">
                        <p class="text-xs font-black text-slate-900 truncate"><?php echo htmlspecialchars($_SESSION['pharmacist_name'] ?? 'Pharmacist'); ?></p>
                        <p class="text-[10px] font-bold text-emerald-600 uppercase">On Duty</p>
                    </div>
                </div>
                <a href="logout.php" 
                   class="flex items-center justify-center gap-3 w-full py-3 bg-white text-rose-500 border border-rose-100 rounded-xl font-bold text-xs hover:bg-rose-500 hover:text-white transition-all shadow-sm">
                    <i data-lucide="log-out" class="w-4 h-4"></i> Sign Out
                </a>
            </div>
            
            <p class="text-center text-[10px] font-bold text-slate-300 uppercase tracking-widest">© 2026 HOPE HAVEN HOSPITAL v2.0</p>
        </div>
    </div>
</aside>
