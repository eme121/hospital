<?php
session_start();
require_once '../includes/db_connect.php';

if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: index.php');
    exit;
}

// Fetch all providers
$providers = $conn->query("SELECT * FROM insurance_providers ORDER BY name ASC")->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Insurance Management | Admin</title>
    <?php include 'includes/header_scripts.php'; ?>
</head>
<body class="bg-slate-50 min-h-screen">
    <?php include 'includes/admin_sidebar.php'; ?>
    <?php include 'includes/admin_topbar.php'; ?>

    <main id="main-wrapper" class="pt-[90px] px-8 pb-10 lg:ml-[280px] transition-all duration-300">
        <div class="mb-10 flex justify-between items-end">
            <div>
                <h1 class="text-3xl font-black text-slate-900 mb-2 tracking-tight uppercase">Insurance & HMOs</h1>
                <p class="text-slate-500 font-medium">Manage partner insurance providers and discount rates.</p>
            </div>
            <button onclick="openProviderModal()" class="px-6 py-3 bg-blue-600 text-white rounded-2xl font-black text-xs uppercase tracking-widest shadow-xl shadow-blue-100 flex items-center gap-3 hover:bg-blue-700 transition-all">
                <i class="fas fa-plus-circle"></i> Add Provider
            </button>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <?php foreach ($providers as $p): ?>
                <div class="bg-white p-8 rounded-[40px] border border-slate-100 shadow-sm hover:shadow-xl transition-all group">
                    <div class="flex justify-between items-start mb-6">
                        <div class="w-14 h-14 bg-blue-50 text-blue-600 rounded-2xl flex items-center justify-center text-xl">
                            <i class="fas fa-shield-alt"></i>
                        </div>
                        <span class="px-4 py-1.5 bg-emerald-50 text-emerald-600 rounded-full text-[10px] font-black uppercase tracking-widest">
                            <?php echo $p['discount_rate']; ?>% Coverage
                        </span>
                    </div>
                    <h3 class="text-xl font-black text-slate-900 mb-1"><?php echo htmlspecialchars($p['name']); ?></h3>
                    <p class="text-sm font-bold text-slate-400 mb-6"><?php echo htmlspecialchars($p['contact_person'] ?: 'No Contact Person'); ?></p>
                    
                    <div class="space-y-3 pt-6 border-t border-slate-50">
                        <div class="flex items-center gap-3 text-slate-500">
                            <i class="fas fa-phone text-[10px]"></i>
                            <span class="text-xs font-bold"><?php echo $p['phone'] ?: 'N/A'; ?></span>
                        </div>
                        <div class="flex items-center gap-3 text-slate-500">
                            <i class="fas fa-envelope text-[10px]"></i>
                            <span class="text-xs font-bold"><?php echo $p['email'] ?: 'N/A'; ?></span>
                        </div>
                    </div>

                    <div class="mt-8 flex gap-3 opacity-0 group-hover:opacity-100 transition-opacity">
                        <button class="flex-1 py-3 bg-slate-50 text-slate-600 rounded-xl font-black text-[10px] uppercase tracking-widest hover:bg-slate-100">Edit</button>
                        <button class="flex-1 py-3 bg-rose-50 text-rose-600 rounded-xl font-black text-[10px] uppercase tracking-widest hover:bg-rose-600 hover:text-white transition-all">Archive</button>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </main>

    <!-- Modal Placeholder -->
    <div id="providerModal" class="fixed inset-0 z-[100] hidden">
        <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm"></div>
        <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-full max-w-lg p-6">
            <div class="bg-white rounded-[40px] shadow-2xl p-10">
                <h3 class="text-2xl font-black text-slate-900 mb-8">Add Insurance Partner</h3>
                <form id="providerForm" class="space-y-6">
                    <input type="text" name="name" placeholder="Provider Name (e.g. AXA Mansard)" required class="w-full bg-slate-50 border-0 rounded-2xl px-6 py-4 font-bold outline-none focus:ring-2 focus:ring-blue-600 transition-all">
                    <div class="grid grid-cols-2 gap-4">
                        <input type="text" name="contact_person" placeholder="Contact Person" class="w-full bg-slate-50 border-0 rounded-2xl px-6 py-4 font-bold">
                        <input type="number" step="0.01" name="discount_rate" placeholder="Coverage %" class="w-full bg-slate-50 border-0 rounded-2xl px-6 py-4 font-bold">
                    </div>
                    <input type="email" name="email" placeholder="Official Email" class="w-full bg-slate-50 border-0 rounded-2xl px-6 py-4 font-bold">
                    <button type="submit" class="w-full py-5 bg-blue-600 text-white rounded-2xl font-black uppercase tracking-widest shadow-xl shadow-blue-100">Register Provider</button>
                </form>
            </div>
        </div>
    </div>

    <script>
        function openProviderModal() { document.getElementById('providerModal').classList.remove('hidden'); }
        // Simple submission logic for demo
        document.getElementById('providerForm').onsubmit = (e) => {
            e.preventDefault();
            alert('Insurance provider registered successfully!');
            location.reload();
        }
    </script>
    <?php include 'includes/admin_footer.php'; ?>
</body>
</html>