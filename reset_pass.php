<?php
session_start();
require_once 'includes/db_connect.php';
require_once 'includes/config.php';
require_once 'includes/SimpleSMTP.php';

$error = "";
$success = "";
$step = 1; // 1: Request, 2: Reset

// Check for token in URL
if (isset($_GET['token'])) {
    $token = $_GET['token'];
    $stmt = $conn->prepare("SELECT id FROM patients WHERE reset_token = ? AND token_expiry > NOW()");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    if ($stmt->get_result()->num_rows > 0) {
        $step = 2;
    } else {
        $error = "Invalid or expired reset token. Please request a new one.";
    }
}

// Handle Step 1: Request Reset
if (isset($_POST['request_reset'])) {
    $email = $_POST['email'];
    $stmt = $conn->prepare("SELECT id, full_name FROM patients WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $res = $stmt->get_result();

    if ($res->num_rows > 0) {
        $user = $res->fetch_assoc();
        $token = bin2hex(random_bytes(32));
        $expiry = date('Y-m-d H:i:s', strtotime('+1 hour'));

        $update = $conn->prepare("UPDATE patients SET reset_token = ?, token_expiry = ? WHERE id = ?");
        $update->bind_param("ssi", $token, $expiry, $user['id']);
        
        if ($update->execute()) {
            $reset_link = BASE_URL . "/reset_pass.php?token=" . $token;
            
            $subject = "Password Reset - Hope Haven Hospital";
            $message = "Dear " . $user['full_name'] . ",\n\nYou requested a password reset. Click the link below to set a new password:\n\n" . $reset_link . "\n\nThis link will expire in 1 hour.\n\nIf you did not request this, please ignore this email.";
            
            $smtp = new SimpleSMTP(SMTP_HOST, SMTP_PORT, SMTP_USER, SMTP_PASS);
            if ($smtp->send($email, $subject, $message, FROM_EMAIL, FROM_NAME)) {
                $success = "A reset link has been sent to your email address.";
            } else {
                $error = "Error sending email. Please try again later.";
            }
        }
    } else {
        $error = "No account found with that email address.";
    }
}

// Handle Step 2: Set New Password
if (isset($_POST['reset_password'])) {
    $token = $_POST['token'];
    $password = $_POST['password'];
    $confirm = $_POST['confirm_password'];

    if ($password !== $confirm) {
        $error = "Passwords do not match.";
    } else {
        $hashed = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("UPDATE patients SET password = ?, reset_token = NULL, token_expiry = NULL WHERE reset_token = ?");
        $stmt->bind_param("ss", $hashed, $token);
        
        if ($stmt->execute()) {
            $success = "Password updated successfully! You can now <a href='patient_login.php' class='underline'>login</a>.";
            $step = 1; // Reset back to start
        } else {
            $error = "Failed to update password. Link may have expired.";
        }
    }
}
?>
<?php include 'includes/header.php'; ?>

<section class="min-h-screen bg-slate-50 py-24 flex items-center">
    <div class="max-w-md mx-auto px-4 w-full">
        <div class="bg-white rounded-[48px] p-10 lg:p-12 shadow-2xl border border-slate-50">
            <div class="text-center mb-10">
                <div class="w-16 h-16 bg-blue-50 text-blue-600 rounded-3xl flex items-center justify-center mx-auto mb-6">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path></svg>
                </div>
                <h1 class="text-3xl font-black text-slate-900 mb-2">Password Recovery</h1>
                <p class="text-slate-500 font-medium text-sm">Securely restore access to your account.</p>
            </div>

            <?php if($error): ?>
                <div class="mb-6 p-4 bg-red-50 text-red-600 rounded-2xl text-xs font-bold border border-red-100"><?php echo $error; ?></div>
            <?php endif; ?>

            <?php if($success): ?>
                <div class="mb-6 p-4 bg-emerald-50 text-emerald-600 rounded-2xl text-xs font-bold border border-emerald-100"><?php echo $success; ?></div>
            <?php endif; ?>

            <?php if ($step == 1): ?>
                <form method="POST" class="space-y-6">
                    <div class="space-y-2">
                        <label class="text-xs font-bold text-slate-400 uppercase tracking-widest ml-2">Email Address</label>
                        <input type="email" name="email" required class="w-full bg-slate-50 border-0 rounded-2xl px-6 py-4 text-slate-900 focus:ring-2 focus:ring-blue-500 transition-all outline-none" placeholder="Enter your registered email">
                    </div>
                    <button type="submit" name="request_reset" class="w-full bg-slate-900 text-white py-5 rounded-2xl font-black text-sm tracking-widest uppercase hover:bg-blue-600 transition-all shadow-xl shadow-slate-200">
                        Send Recovery Link
                    </button>
                    <div class="text-center">
                        <a href="patient_login.php" class="text-xs font-bold text-blue-600 hover:underline">Back to Login</a>
                    </div>
                </form>
            <?php else: ?>
                <form method="POST" class="space-y-6">
                    <input type="hidden" name="token" value="<?php echo htmlspecialchars($_GET['token']); ?>">
                    <div class="space-y-2">
                        <label class="text-xs font-bold text-slate-400 uppercase tracking-widest ml-2">New Password</label>
                        <input type="password" name="password" required class="w-full bg-slate-50 border-0 rounded-2xl px-6 py-4 text-slate-900 focus:ring-2 focus:ring-blue-500 transition-all outline-none" placeholder="••••••••">
                    </div>
                    <div class="space-y-2">
                        <label class="text-xs font-bold text-slate-400 uppercase tracking-widest ml-2">Confirm Password</label>
                        <input type="password" name="confirm_password" required class="w-full bg-slate-50 border-0 rounded-2xl px-6 py-4 text-slate-900 focus:ring-2 focus:ring-blue-500 transition-all outline-none" placeholder="••••••••">
                    </div>
                    <button type="submit" name="reset_password" class="w-full bg-blue-600 text-white py-5 rounded-2xl font-black text-sm tracking-widest uppercase hover:bg-blue-700 transition-all shadow-xl shadow-blue-200">
                        Update Password
                    </button>
                </form>
            <?php endif; ?>
        </div>
    </div>
</section>

<?php include 'includes/footer.php'; ?>
