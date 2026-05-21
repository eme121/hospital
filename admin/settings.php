<?php
session_start();
require_once '../includes/db_connect.php';

if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: index.php');
    exit;
}

// Fetch current settings
$res = $conn->query("SELECT * FROM system_settings");
$settings = [];
while($row = $res->fetch_assoc()) {
    $settings[$row['key']] = $row['value'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Settings | Hope Haven Hospital</title>
    <?php include 'includes/header_scripts.php'; ?>
</head>
<body class="bg-slate-50 min-h-screen">
    <?php include 'includes/admin_sidebar.php'; ?>
    <?php include 'includes/admin_topbar.php'; ?>

    <main id="main-wrapper" class="pt-[90px] px-8 pb-10 lg:ml-[280px] transition-all duration-300">
        <div class="mb-10">
            <h1 class="text-3xl font-black text-slate-900 mb-2 tracking-tight">Configuration</h1>
            <p class="text-slate-500 font-medium">Manage API integrations and notification channels.</p>
        </div>

        <div class="max-w-4xl space-y-8">
            <!-- Financial Settings -->
            <div class="bg-white rounded-[32px] shadow-xl shadow-slate-200/50 border border-slate-100 overflow-hidden">
                <div class="p-8 border-b border-slate-100 bg-slate-50/50 flex items-center gap-4">
                    <div class="w-12 h-12 bg-blue-100 text-blue-600 rounded-2xl flex items-center justify-center">
                        <i class="fas fa-hand-holding-usd text-2xl"></i>
                    </div>
                    <div>
                        <h3 class="text-lg font-black text-slate-900">Financial Settings</h3>
                        <p class="text-xs font-bold text-slate-400 uppercase tracking-widest">Manage Service Pricing</p>
                    </div>
                </div>
                <div class="p-8 space-y-8">
                    <div class="max-w-xs space-y-2">
                        <label class="text-[11px] font-black text-slate-400 uppercase tracking-widest ml-1">Virtual Consultation Fee (₦)</label>
                        <input type="number" form="settingsForm" name="virtual_consultation_fee" value="<?php echo htmlspecialchars($settings['virtual_consultation_fee'] ?? '5000'); ?>" 
                               class="w-full bg-slate-50 border border-slate-200 rounded-2xl px-5 py-4 font-bold text-slate-700 outline-none focus:ring-4 focus:ring-blue-500/10 transition-all" placeholder="5000">
                    </div>

                    <div class="pt-6 border-t border-slate-100">
                        <h4 class="text-sm font-black text-slate-900 mb-4 uppercase tracking-widest">Onboarding Folder Pricing</h4>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                            <?php 
                                $folders = $conn->query("SELECT * FROM folder_types");
                                while($f = $folders->fetch_assoc()):
                            ?>
                                <div class="p-6 bg-slate-50 rounded-[24px] border border-slate-100">
                                    <p class="text-[10px] font-black text-blue-600 uppercase tracking-widest mb-3"><?php echo $f['name']; ?></p>
                                    <div class="space-y-4">
                                        <div class="space-y-1">
                                            <label class="text-[9px] font-bold text-slate-400 uppercase ml-1">Price (₦)</label>
                                            <input type="number" form="settingsForm" name="folder_price_<?php echo $f['id']; ?>" value="<?php echo $f['price']; ?>" 
                                                   class="w-full bg-white border border-slate-200 rounded-xl px-4 py-2 text-sm font-bold text-slate-700 outline-none focus:ring-2 focus:ring-blue-500 transition-all">
                                        </div>
                                        <div class="space-y-1">
                                            <label class="text-[9px] font-bold text-slate-400 uppercase ml-1">Description</label>
                                            <textarea form="settingsForm" name="folder_desc_<?php echo $f['id']; ?>" 
                                                      class="w-full bg-white border border-slate-200 rounded-xl px-4 py-2 text-[10px] font-medium text-slate-600 outline-none focus:ring-2 focus:ring-blue-500 transition-all h-20 resize-none"><?php echo $f['description']; ?></textarea>
                                        </div>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-[32px] shadow-xl shadow-slate-200/50 border border-slate-100 overflow-hidden">
                <div class="p-8 border-b border-slate-100 bg-slate-50/50 flex items-center gap-4">
                    <div class="w-12 h-12 bg-emerald-100 text-emerald-600 rounded-2xl flex items-center justify-center">
                        <i class="fab fa-whatsapp text-2xl"></i>
                    </div>
                    <div>
                        <h3 class="text-lg font-black text-slate-900">WhatsApp API (Twilio)</h3>
                        <p class="text-xs font-bold text-slate-400 uppercase tracking-widest">Global Notification Channel</p>
                    </div>
                </div>

                <form id="settingsForm" class="p-8 space-y-8">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                        <div class="space-y-2">
                            <label class="text-[11px] font-black text-slate-400 uppercase tracking-widest ml-1">Enable WhatsApp Service</label>
                            <select name="enable_whatsapp" class="w-full bg-slate-50 border border-slate-200 rounded-2xl px-5 py-4 font-bold text-slate-700 outline-none focus:ring-4 focus:ring-blue-500/10 transition-all">
                                <option value="0" <?php echo ($settings['enable_whatsapp'] ?? '0') == '0' ? 'selected' : ''; ?>>Disabled (Offline)</option>
                                <option value="1" <?php echo ($settings['enable_whatsapp'] ?? '0') == '1' ? 'selected' : ''; ?>>Enabled (Live)</option>
                            </select>
                        </div>

                        <div class="space-y-2">
                            <label class="text-[11px] font-black text-slate-400 uppercase tracking-widest ml-1">Admin Alert Number</label>
                            <input type="text" name="admin_whatsapp" value="<?php echo htmlspecialchars($settings['admin_whatsapp'] ?? ''); ?>" 
                                   class="w-full bg-slate-50 border border-slate-200 rounded-2xl px-5 py-4 font-bold text-slate-700 outline-none focus:ring-4 focus:ring-blue-500/10 transition-all" placeholder="e.g. 2348000000000">
                        </div>

                        <div class="md:col-span-2 space-y-2">
                            <label class="text-[11px] font-black text-slate-400 uppercase tracking-widest ml-1">Twilio API URL</label>
                            <input type="text" name="whatsapp_api_url" value="<?php echo htmlspecialchars($settings['whatsapp_api_url'] ?? ''); ?>" 
                                   class="w-full bg-slate-50 border border-slate-200 rounded-2xl px-5 py-4 font-bold text-slate-700 outline-none focus:ring-4 focus:ring-blue-500/10 transition-all" placeholder="https://api.twilio.com/...">
                        </div>

                        <div class="space-y-2">
                            <label class="text-[11px] font-black text-slate-400 uppercase tracking-widest ml-1">Twilio Auth Token</label>
                            <input type="password" name="whatsapp_token" value="<?php echo htmlspecialchars($settings['whatsapp_token'] ?? ''); ?>" 
                                   class="w-full bg-slate-50 border border-slate-200 rounded-2xl px-5 py-4 font-bold text-slate-700 outline-none focus:ring-4 focus:ring-blue-500/10 transition-all" placeholder="••••••••••••••••">
                        </div>

                        <div class="space-y-2">
                            <label class="text-[11px] font-black text-slate-400 uppercase tracking-widest ml-1">Twilio WhatsApp Number</label>
                            <input type="text" name="whatsapp_from" value="<?php echo htmlspecialchars($settings['whatsapp_from'] ?? ''); ?>" 
                                   class="w-full bg-slate-50 border border-slate-200 rounded-2xl px-5 py-4 font-bold text-slate-700 outline-none focus:ring-4 focus:ring-blue-500/10 transition-all" placeholder="whatsapp:+14155238886">
                        </div>
                    </div>

                    <div class="pt-6 border-t border-slate-100 flex justify-end">
                        <button type="submit" class="bg-slate-900 text-white px-10 py-4 rounded-2xl font-black text-sm uppercase tracking-widest hover:bg-blue-600 transition-all shadow-xl shadow-blue-200 flex items-center gap-3">
                            <i class="fas fa-save"></i> Save Configuration
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </main>

    <script>
        document.getElementById('settingsForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            const btn = e.target.querySelector('button');
            const originalText = btn.innerHTML;
            
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';

            const formData = new FormData(e.target);
            const params = new URLSearchParams(formData);

            try {
                const res = await fetch('api/update_settings.php', {
                    method: 'POST',
                    body: params
                });
                const result = await res.json();
                if (result.success) {
                    alert('Settings updated successfully!');
                } else {
                    alert('Error: ' + result.message);
                }
            } catch (err) {
                console.error(err);
                alert('Connection failed.');
            } finally {
                btn.disabled = false;
                btn.innerHTML = originalText;
            }
        });
    </script>
</body>
</html>