<?php
session_start();
require_once 'includes/db_connect.php';

if (!isset($_SESSION['doctor_id'])) {
    header('Location: telemedicine_login.php');
    exit;
}

$doctor_id = $_SESSION['doctor_id'];
$doctor_name = $_SESSION['doctor_name'];
$doctor_pix = $_SESSION['doctor_pix'];

// Fetch latest data
$stmt = $conn->prepare("SELECT * FROM telemedicine_doctors WHERE id = ?");
$stmt->bind_param("i", $doctor_id);
$stmt->execute();
$doctor = $stmt->get_result()->fetch_assoc();

$message = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'];
    $email = $_POST['email'];
    $password = $_POST['password'];

    if (!empty($password)) {
        $hashed = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("UPDATE telemedicine_doctors SET name = ?, email = ?, password = ? WHERE id = ?");
        $stmt->bind_param("sssi", $name, $email, $hashed, $doctor_id);
    } else {
        $stmt = $conn->prepare("UPDATE telemedicine_doctors SET name = ?, email = ? WHERE id = ?");
        $stmt->bind_param("ssi", $name, $email, $doctor_id);
    }

    if ($stmt->execute()) {
        $_SESSION['doctor_name'] = $name;
        $message = "Profile updated successfully!";
        // Refresh local data
        $doctor['name'] = $name;
        $doctor['email'] = $email;
    } else {
        $message = "Error updating profile: " . $conn->error;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Doctor Settings | Hope Haven Hospital</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; background-color: #f8fafc; }
        .sidebar-item.active { background-color: #eff6ff; color: #2563eb; border-right: 4px solid #2563eb; }
    </style>
</head>
<body class="flex min-h-screen">

    <!-- Sidebar -->
    <aside id="sidebar" class="fixed inset-y-0 left-0 z-50 w-64 bg-white border-r border-slate-200 transition-transform duration-300 md:relative md:transform-none">
        <div class="p-6 flex flex-col h-full">
            <div class="flex items-center gap-3 mb-10">
                <div class="w-10 h-10 bg-blue-600 rounded-xl flex items-center justify-center text-white font-black shadow-lg shadow-blue-200">D</div>
                <h1 class="text-xl font-black text-slate-900 tracking-tighter">DOCTOR<span class="text-blue-600">HUB</span></h1>
            </div>

            <nav class="space-y-1 flex-1">
                <a href="telemedicine_dashboard.php?view=dashboard" class="sidebar-item flex items-center gap-3 px-4 py-3 rounded-lg font-bold text-sm text-slate-600 hover:bg-slate-50 transition-all">
                    <i data-lucide="layout-dashboard" class="w-5 h-5"></i> Dashboard
                </a>
                <a href="telemedicine_dashboard.php?view=visits" class="sidebar-item flex items-center gap-3 px-4 py-3 rounded-lg font-bold text-sm text-slate-600 hover:bg-slate-50 transition-all">
                    <i data-lucide="video" class="w-5 h-5"></i> Virtual Visits
                </a>
                <a href="telemedicine_dashboard.php?view=cases" class="sidebar-item flex items-center gap-3 px-4 py-3 rounded-lg font-bold text-sm text-slate-600 hover:bg-slate-50 transition-all">
                    <i data-lucide="folder-kanban" class="w-5 h-5"></i> Case Discussions
                </a>
                <a href="doctor_settings.php" class="sidebar-item active flex items-center gap-3 px-4 py-3 rounded-lg font-bold text-sm text-slate-600 hover:bg-slate-50 transition-all">
                    <i data-lucide="settings" class="w-5 h-5"></i> My Settings
                </a>
            </nav>

            <div class="mt-auto pt-6 border-t border-slate-100">
                <a href="logout.php" class="flex items-center gap-3 px-4 py-3 rounded-lg font-bold text-sm text-red-600 hover:bg-red-50 transition-all">
                    <i data-lucide="log-out" class="w-5 h-5"></i> Logout
                </a>
            </div>
        </div>
    </aside>

    <!-- Main Content -->
    <main class="flex-1 flex flex-col min-w-0 overflow-hidden">
        <header class="bg-white border-b border-slate-200 h-16 flex items-center justify-between px-6 shrink-0">
            <div class="flex items-center gap-4">
                <button id="mobile-toggle" class="md:hidden p-2 text-slate-600"><i data-lucide="menu"></i></button>
                <h2 class="text-lg font-black text-slate-900 uppercase tracking-tight">Account Settings</h2>
            </div>
        </header>

        <div class="flex-1 overflow-y-auto p-6 space-y-8">
            <div class="max-w-2xl bg-white rounded-[40px] border border-slate-200 shadow-sm p-10 mx-auto">
                <div class="flex items-center gap-6 mb-10">
                    <img src="<?php echo $doctor_pix; ?>" class="w-20 h-20 rounded-3xl object-cover border-4 border-slate-50 shadow-xl">
                    <div>
                        <h3 class="text-2xl font-black text-slate-900"><?php echo htmlspecialchars($doctor['name']); ?></h3>
                        <p class="text-xs font-bold text-blue-600 uppercase tracking-widest">Update your professional profile</p>
                    </div>
                </div>

                <?php if ($message): ?>
                    <div class="p-4 bg-emerald-50 text-emerald-600 rounded-2xl text-xs font-bold mb-8 flex items-center gap-3">
                        <i data-lucide="check-circle" class="w-4 h-4"></i>
                        <?php echo $message; ?>
                    </div>
                <?php endif; ?>

                <form method="POST" class="space-y-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="space-y-2">
                            <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-1">Full Name</label>
                            <input type="text" name="name" value="<?php echo htmlspecialchars($doctor['name']); ?>" required class="w-full bg-slate-50 border-0 rounded-2xl px-6 py-4 font-bold outline-none focus:ring-2 focus:ring-blue-500 transition-all">
                        </div>
                        <div class="space-y-2">
                            <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-1">Email Address</label>
                            <input type="email" name="email" value="<?php echo htmlspecialchars($doctor['email']); ?>" required class="w-full bg-slate-50 border-0 rounded-2xl px-6 py-4 font-bold outline-none focus:ring-2 focus:ring-blue-500 transition-all">
                        </div>
                    </div>

                    <div class="space-y-2">
                        <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-1">New Password (leave blank to keep current)</label>
                        <input type="password" name="password" placeholder="••••••••" class="w-full bg-slate-50 border-0 rounded-2xl px-6 py-4 font-bold outline-none focus:ring-2 focus:ring-blue-500 transition-all">
                    </div>

                    <div class="pt-4">
                        <button type="submit" class="w-full py-5 bg-slate-900 text-white rounded-2xl font-black uppercase tracking-widest shadow-xl shadow-slate-200 hover:bg-slate-800 transition-all">Save Profile Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </main>

    <script>
        document.getElementById('mobile-toggle').addEventListener('click', () => {
            document.getElementById('sidebar').classList.toggle('open');
        });
        lucide.createIcons();
    </script>
</body>
</html>