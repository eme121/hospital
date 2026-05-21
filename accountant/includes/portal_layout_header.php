<?php
// Master Layout Header
$current_page = basename($_SERVER['PHP_SELF']);
$accountant_name = $_SESSION['accountant_name'] ?? 'Accountant';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title><?php echo $page_title ?? 'Finance Dept'; ?> | Hope Haven</title>
    
    <!-- Standardized Assets -->
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <!-- Real-Time Sync Engine -->
    <script> window.APP_BASE_URL = '<?php echo BASE_URL; ?>'; </script>
    <script src="<?php echo BASE_URL; ?>/assets/js/sync_engine.js?v=<?php echo time(); ?>"></script>

    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; background-color: #f8fafc; overflow-x: hidden; }
        .sidebar-link-active { background-color: #10b981 !important; color: white !important; box-shadow: 0 10px 15px -3px rgba(16, 185, 129, 0.2); }
        /* Prevent layout shift during loading */
        .main-content { min-height: 100vh; }
    </style>
</head>
<body class="min-h-screen flex bg-[#f8fafc]">

    <!-- PERSISTENT SIDEBAR: Locked w-72 and shrink-0 -->
    <aside class="w-72 bg-[#0f172a] text-slate-400 p-8 flex flex-col shrink-0 h-screen sticky top-0 z-50">
        <div class="flex items-center gap-3 mb-12">
            <div class="w-10 h-10 bg-emerald-600 rounded-xl flex items-center justify-center text-white font-black">F</div>
            <h1 class="text-xl font-black text-white tracking-tighter uppercase">Finance<span class="text-emerald-500">Dept</span></h1>
        </div>

        <nav class="flex-1 space-y-2">
            <a href="dashboard.php" class="flex items-center gap-4 px-4 py-3 rounded-xl font-bold text-sm transition-all hover:bg-slate-800 <?php echo $current_page == 'dashboard.php' ? 'sidebar-link-active' : ''; ?>">
                <i class="fas fa-chart-line w-5"></i> Dashboard
            </a>
            <a href="verify_payments.php" class="flex items-center gap-4 px-4 py-3 rounded-xl font-bold text-sm transition-all hover:bg-slate-800 relative <?php echo $current_page == 'verify_payments.php' ? 'sidebar-link-active' : ''; ?>">
                <i class="fas fa-check-double w-5"></i> Verify Payments
                <span id="notif-badge" class="hidden absolute top-3 right-4 w-4 h-4 bg-rose-500 text-white text-[8px] flex items-center justify-center rounded-full font-black">0</span>
            </a>
            <a href="reports.php" class="flex items-center gap-4 px-4 py-3 rounded-xl font-bold text-sm transition-all hover:bg-slate-800 <?php echo $current_page == 'reports.php' ? 'sidebar-link-active' : ''; ?>">
                <i class="fas fa-file-contract w-5"></i> Financial Reports
            </a>
            <a href="manage_invoices.php" class="flex items-center gap-4 px-4 py-3 rounded-xl font-bold text-sm transition-all hover:bg-slate-800 <?php echo $current_page == 'manage_invoices.php' ? 'sidebar-link-active' : ''; ?>">
                <i class="fas fa-file-invoice-dollar w-5"></i> All Invoices
            </a>
        </nav>

        <!-- PERSISTENT LOGOUT: Locked at bottom -->
        <div class="mt-auto pt-8 border-t border-slate-800">
            <div class="mb-6">
                <p class="text-[10px] font-black uppercase tracking-widest text-slate-500">Logged in as</p>
                <p class="text-sm font-bold text-white truncate"><?php echo htmlspecialchars($accountant_name); ?></p>
            </div>
            <a href="logout.php" class="flex items-center gap-4 px-4 py-3 text-rose-500 hover:bg-rose-500/10 rounded-xl font-bold text-sm transition-all">
                <i class="fas fa-sign-out-alt w-5"></i> Sign Out
            </a>
        </div>
    </aside>

    <!-- CONTENT WRAPPER: Standardsied p-10 and flex structure -->
    <main class="flex-1 flex flex-col min-w-0 main-content">
