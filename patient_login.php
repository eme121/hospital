<?php
session_start();
require_once 'includes/db_connect.php';

if (isset($_SESSION['patient_id'])) {
    header('Location: patient_dashboard.php');
    exit;
}

$error = "";
$success = "";

// Handle Login
if (isset($_POST['login'])) {
    $identifier = $_POST['identifier']; // Email or File Number
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT id, full_name, password FROM patients WHERE email = ? OR file_number = ?");
    $stmt->bind_param("ss", $identifier, $identifier);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        if (password_verify($password, $user['password'])) {
            $_SESSION['patient_id'] = $user['id'];
            $_SESSION['patient_name'] = $user['full_name'];
            header('Location: patient_dashboard.php');
            exit;
        } else {
            $error = "Invalid password. Please try again.";
        }
    } else {
        $error = "No account found with that email or file number.";
    }
}

// Handle Registration (New Patient)
if (isset($_POST['register'])) {
    $name = $_POST['name'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

    // Check if email exists
    $check = $conn->prepare("SELECT id FROM patients WHERE email = ?");
    $check->bind_param("s", $email);
    $check->execute();
    if ($check->get_result()->num_rows > 0) {
        $error = "An account with this email already exists.";
    } else {
        $stmt = $conn->prepare("INSERT INTO patients (file_number, full_name, email, phone, password) VALUES (?, ?, ?, ?, ?)");
        $placeholder_file = "PENDING";
        $stmt->bind_param("sssss", $placeholder_file, $name, $email, $phone, $password);
        
        if ($stmt->execute()) {
            $new_id = $stmt->insert_id;
            $year = date('Y');
            $file_number = "HH-" . $year . "-" . str_pad($new_id, 4, '0', STR_PAD_LEFT);
            
            // Update the record with the actual file number
            $update = $conn->prepare("UPDATE patients SET file_number = ? WHERE id = ?");
            $update->bind_param("si", $file_number, $new_id);
            $update->execute();

            // Initialize a fresh onboarding record so they start at folder selection
            $conn->query("DELETE FROM patient_onboarding WHERE patient_id = $new_id");
            $conn->query("INSERT INTO patient_onboarding (patient_id, status) VALUES ($new_id, 'Not Started')");

            $success = "Registration successful! Your File Number is: <strong>$file_number</strong>. You can now login.";
        } else {
            $error = "Registration failed. Please try again.";
        }
    }
}
?>
<?php include 'includes/header.php'; ?>

    <section class="relative py-24 lg:py-32 bg-slate-50 min-h-screen flex items-center">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 w-full">
            <div class="grid lg:grid-cols-2 gap-16 items-center">
                
                <!-- Left: Visual/Info -->
                <div data-aos="fade-right">
                    <div class="mb-8 relative">
                        <div class="absolute -top-10 -left-10 w-32 h-32 bg-blue-100 rounded-full mix-blend-multiply filter blur-2xl opacity-70 animate-pulse"></div>
                        <img src="assets/external/doc.png" alt="Medical Professional" class="relative z-10 w-full max-w-md mx-auto rounded-[40px] shadow-2xl border-8 border-white">
                        <div class="absolute -bottom-6 -right-6 w-24 h-24 bg-emerald-100 rounded-full mix-blend-multiply filter blur-2xl opacity-70 animate-pulse"></div>
                    </div>

                    <span class="text-sm font-bold text-blue-600 uppercase tracking-[0.3em] mb-4 block">Patient Experience</span>
                    <h1 class="text-5xl lg:text-6xl font-black text-slate-900 leading-tight mb-8">Your Health Journey, <span class="text-blue-600">Digitized</span>.</h1>
                    <p class="text-lg text-slate-500 font-medium leading-relaxed mb-10">Access your medical history, manage appointments, and connect with specialists through our secure patient portal.</p>
                    
                    <div class="grid sm:grid-cols-2 gap-6">
                        <div class="bg-white p-6 rounded-3xl shadow-sm border border-slate-100 group hover:border-blue-200 transition-all">
                            <div class="w-12 h-12 bg-blue-600 text-white rounded-2xl flex items-center justify-center mb-4 shadow-lg shadow-blue-200">
                                <i data-lucide="calendar" class="w-6 h-6"></i>
                            </div>
                            <h4 class="font-bold text-slate-900 mb-1">Easy Booking</h4>
                            <p class="text-xs text-slate-400 font-medium">Schedule visits in seconds.</p>
                        </div>
                        <div class="bg-white p-6 rounded-3xl shadow-sm border border-slate-100 group hover:border-emerald-200 transition-all">
                            <div class="w-12 h-12 bg-emerald-500 text-white rounded-2xl flex items-center justify-center mb-4 shadow-lg shadow-emerald-200">
                                <i data-lucide="shield-check" class="w-6 h-6"></i>
                            </div>
                            <h4 class="font-bold text-slate-900 mb-1">Secure Records</h4>
                            <p class="text-xs text-slate-400 font-medium">Your data is always private.</p>
                        </div>
                    </div>
                </div>

                <!-- Right: Form -->
                <div class="bg-white rounded-[48px] p-10 lg:p-16 shadow-2xl border border-slate-50 relative overflow-hidden" data-aos="zoom-in">
                    <div class="absolute top-0 right-0 w-32 h-32 bg-blue-50 rounded-full -mr-16 -mt-16 opacity-50"></div>
                    
                    <div class="flex flex-col items-center mb-10 relative z-10">
                        <div class="w-20 h-20 bg-blue-50 rounded-3xl flex items-center justify-center mb-4 shadow-sm border border-slate-100 overflow-hidden">
                            <img src="logo.png" alt="Hospital Logo" class="w-14 h-14 object-contain">
                        </div>
                        <h2 class="text-2xl font-black text-slate-900">Welcome Back</h2>
                        <p class="text-slate-400 text-sm font-bold uppercase tracking-widest mt-1">Patient Portal Access</p>
                    </div>

                    <!-- Tabs -->
                    <div class="flex bg-slate-50 p-1.5 rounded-2xl mb-10 relative z-10">
                        <button onclick="switchTab('login')" id="tab-login" class="flex-1 py-3 rounded-xl font-bold text-sm transition-all bg-white text-blue-600 shadow-sm">Existing Patient</button>
                        <button onclick="switchTab('register')" id="tab-register" class="flex-1 py-3 rounded-xl font-bold text-sm transition-all text-slate-500 hover:text-slate-700">New Patient</button>
                    </div>

                    <?php if($error): ?>
                        <div class="mb-6 p-4 bg-red-50 text-red-600 rounded-2xl text-xs font-bold border border-red-100"><?php echo $error; ?></div>
                    <?php endif; ?>

                    <?php if($success): ?>
                        <div class="mb-6 p-4 bg-emerald-50 text-emerald-600 rounded-2xl text-xs font-bold border border-emerald-100"><?php echo $success; ?></div>
                    <?php endif; ?>

                    <!-- Login Form -->
                    <form id="loginForm" method="POST" class="space-y-6">
                        <div class="space-y-2">
                            <label class="text-sm font-bold text-slate-700 ml-2">Email or File Number</label>
                            <input type="text" name="identifier" required class="w-full bg-slate-50 border-0 rounded-2xl px-6 py-4 text-slate-900 focus:ring-2 focus:ring-blue-500 transition-all outline-none" placeholder="e.g. HH-2026-0001">
                        </div>
                        <div class="space-y-2">
                            <label class="text-sm font-bold text-slate-700 ml-2">Password</label>
                            <input type="password" name="password" required class="w-full bg-slate-50 border-0 rounded-2xl px-6 py-4 text-slate-900 focus:ring-2 focus:ring-blue-500 transition-all outline-none" placeholder="••••••••">
                        </div>
                        <button type="submit" name="login" class="w-full bg-blue-600 text-white py-5 rounded-2xl font-black text-lg hover:bg-blue-700 transition-all shadow-xl shadow-blue-200">
                            Access Portal
                        </button>
                        <p class="text-center text-xs font-bold text-slate-400">Forgot your password? <a href="reset_pass.php" class="text-blue-600">Reset here</a></p>
                    </form>

                    <!-- Register Form -->
                    <form id="registerForm" method="POST" class="space-y-6 hidden">
                        <div class="space-y-2">
                            <label class="text-sm font-bold text-slate-700 ml-2">Full Name</label>
                            <input type="text" name="name" required class="w-full bg-slate-50 border-0 rounded-2xl px-6 py-4 text-slate-900 focus:ring-2 focus:ring-blue-500 transition-all outline-none" placeholder="John Doe">
                        </div>
                        <div class="grid md:grid-cols-2 gap-6">
                            <div class="space-y-2">
                                <label class="text-sm font-bold text-slate-700 ml-2">Email</label>
                                <input type="email" name="email" required class="w-full bg-slate-50 border-0 rounded-2xl px-6 py-4 text-slate-900 focus:ring-2 focus:ring-blue-500 transition-all outline-none" placeholder="john@example.com">
                            </div>
                            <div class="space-y-2">
                                <label class="text-sm font-bold text-slate-700 ml-2">Phone</label>
                                <input type="tel" name="phone" required class="w-full bg-slate-50 border-0 rounded-2xl px-6 py-4 text-slate-900 focus:ring-2 focus:ring-blue-500 transition-all outline-none" placeholder="+234 ...">
                            </div>
                        </div>
                        <div class="space-y-2">
                            <label class="text-sm font-bold text-slate-700 ml-2">Create Password</label>
                            <input type="password" name="password" required class="w-full bg-slate-50 border-0 rounded-2xl px-6 py-4 text-slate-900 focus:ring-2 focus:ring-blue-500 transition-all outline-none" placeholder="••••••••">
                        </div>
                        <button type="submit" name="register" class="w-full bg-emerald-500 text-white py-5 rounded-2xl font-black text-lg hover:bg-emerald-600 transition-all shadow-xl shadow-emerald-200">
                            Create Account
                        </button>
                    </form>

                </div>
            </div>
        </div>
    </section>

    <script>
        function switchTab(tab) {
            const loginForm = document.getElementById('loginForm');
            const registerForm = document.getElementById('registerForm');
            const tabLogin = document.getElementById('tab-login');
            const tabRegister = document.getElementById('tab-register');

            if (tab === 'login') {
                loginForm.classList.remove('hidden');
                registerForm.classList.add('hidden');
                tabLogin.classList.add('bg-white', 'text-blue-600', 'shadow-sm');
                tabLogin.classList.remove('text-slate-500');
                tabRegister.classList.remove('bg-white', 'text-blue-600', 'shadow-sm');
                tabRegister.classList.add('text-slate-500');
            } else {
                loginForm.classList.add('hidden');
                registerForm.classList.remove('hidden');
                tabRegister.classList.add('bg-white', 'text-blue-600', 'shadow-sm');
                tabRegister.classList.remove('text-slate-500');
                tabLogin.classList.remove('bg-white', 'text-blue-600', 'shadow-sm');
                tabLogin.classList.add('text-slate-500');
            }
        }
    </script>

<?php include 'includes/footer.php'; ?>
