<?php
session_start();
require_once 'includes/db_connect.php';

// Check if patient is logged in
if (!isset($_SESSION['patient_id'])) {
    header('Location: patient_login.php');
    exit;
}

$patient_id = $_SESSION['patient_id'];
$message = "";
$error = "";

// Handle Profile Update
if (isset($_POST['update_profile'])) {
    $full_name = $_POST['full_name'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];

    $stmt = $conn->prepare("UPDATE patients SET full_name = ?, email = ?, phone = ? WHERE id = ?");
    $stmt->bind_param("sssi", $full_name, $email, $phone, $patient_id);
    if ($stmt->execute()) {
        $_SESSION['patient_name'] = $full_name;
        $message = "Profile updated successfully!";
    } else {
        $error = "Error updating profile: " . $conn->error;
    }
}

// Handle Password Update
if (isset($_POST['update_password'])) {
    $current_pass = $_POST['current_password'];
    $new_pass = $_POST['new_password'];
    $confirm_pass = $_POST['confirm_password'];

    if ($new_pass !== $confirm_pass) {
        $error = "New passwords do not match!";
    } else {
        $stmt = $conn->prepare("SELECT password FROM patients WHERE id = ?");
        $stmt->bind_param("i", $patient_id);
        $stmt->execute();
        $res = $stmt->get_result()->fetch_assoc();

        if (password_verify($current_pass, $res['password'])) {
            $hashed_pass = password_hash($new_pass, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE patients SET password = ? WHERE id = ?");
            $stmt->bind_param("si", $hashed_pass, $patient_id);
            $stmt->execute();
            $message = "Password updated successfully!";
        } else {
            $error = "Current password is incorrect!";
        }
    }
}

// Fetch current data
$stmt = $conn->prepare("SELECT * FROM patients WHERE id = ?");
$stmt->bind_param("i", $patient_id);
$stmt->execute();
$patient = $stmt->get_result()->fetch_assoc();

include 'includes/dashboard_header.php';
?>

<main class="bg-slate-50 min-h-screen py-12">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="mb-10 flex items-center justify-between">
            <h1 class="text-3xl font-black text-slate-900">Profile Settings</h1>
            <a href="patient_dashboard.php" class="text-sm font-bold text-blue-600 hover:underline flex items-center">
                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                Back to Dashboard
            </a>
        </div>

        <?php if($message): ?>
            <div class="mb-6 p-4 bg-green-100 text-green-700 rounded-2xl font-bold border border-green-200"><?php echo $message; ?></div>
        <?php endif; ?>
        <?php if($error): ?>
            <div class="mb-6 p-4 bg-red-100 text-red-700 rounded-2xl font-bold border border-red-200"><?php echo $error; ?></div>
        <?php endif; ?>

        <div class="grid md:grid-cols-2 gap-8">
            <!-- Edit Profile -->
            <div class="bg-white rounded-[32px] p-8 shadow-sm border border-slate-100">
                <h2 class="text-xl font-black text-slate-800 mb-6">Personal Information</h2>
                <form method="POST" class="space-y-6">
                    <div class="space-y-2">
                        <label class="text-xs font-bold text-slate-500 uppercase ml-2 tracking-widest">Full Name</label>
                        <input type="text" name="full_name" value="<?php echo htmlspecialchars($patient['full_name']); ?>" required class="w-full bg-slate-50 border-0 rounded-2xl px-6 py-4 text-slate-900 focus:ring-2 focus:ring-blue-500 outline-none transition-all">
                    </div>
                    <div class="space-y-2">
                        <label class="text-xs font-bold text-slate-500 uppercase ml-2 tracking-widest">Email Address</label>
                        <input type="email" name="email" value="<?php echo htmlspecialchars($patient['email']); ?>" required class="w-full bg-slate-50 border-0 rounded-2xl px-6 py-4 text-slate-900 focus:ring-2 focus:ring-blue-500 outline-none transition-all">
                    </div>
                    <div class="space-y-2">
                        <label class="text-xs font-bold text-slate-500 uppercase ml-2 tracking-widest">Phone Number</label>
                        <input type="text" name="phone" value="<?php echo htmlspecialchars($patient['phone']); ?>" required class="w-full bg-slate-50 border-0 rounded-2xl px-6 py-4 text-slate-900 focus:ring-2 focus:ring-blue-500 outline-none transition-all">
                    </div>
                    <button type="submit" name="update_profile" class="w-full py-4 bg-blue-600 text-white rounded-2xl font-black hover:bg-blue-700 shadow-lg shadow-blue-100 transition-all">Save Changes</button>
                </form>
            </div>

            <!-- Security -->
            <div class="bg-white rounded-[32px] p-8 shadow-sm border border-slate-100">
                <h2 class="text-xl font-black text-slate-800 mb-6">Security & Password</h2>
                <form method="POST" class="space-y-6">
                    <div class="space-y-2">
                        <label class="text-xs font-bold text-slate-500 uppercase ml-2 tracking-widest">Current Password</label>
                        <input type="password" name="current_password" required class="w-full bg-slate-50 border-0 rounded-2xl px-6 py-4 text-slate-900 focus:ring-2 focus:ring-blue-500 outline-none transition-all">
                    </div>
                    <div class="space-y-2">
                        <label class="text-xs font-bold text-slate-500 uppercase ml-2 tracking-widest">New Password</label>
                        <input type="password" name="new_password" required class="w-full bg-slate-50 border-0 rounded-2xl px-6 py-4 text-slate-900 focus:ring-2 focus:ring-blue-500 outline-none transition-all">
                    </div>
                    <div class="space-y-2">
                        <label class="text-xs font-bold text-slate-500 uppercase ml-2 tracking-widest">Confirm New Password</label>
                        <input type="password" name="confirm_password" required class="w-full bg-slate-50 border-0 rounded-2xl px-6 py-4 text-slate-900 focus:ring-2 focus:ring-blue-500 outline-none transition-all">
                    </div>
                    <button type="submit" name="update_password" class="w-full py-4 bg-slate-900 text-white rounded-2xl font-black hover:bg-slate-800 shadow-lg shadow-slate-100 transition-all">Update Password</button>
                </form>
            </div>
        </div>
    </div>
</main>

<?php include 'includes/footer.php'; ?>
