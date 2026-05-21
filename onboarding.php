<?php
session_start();
require_once 'includes/db_connect.php';

// Enable error reporting for debugging blank screen
ini_set('display_errors', 1);
error_reporting(E_ALL);

if (!isset($_SESSION['patient_id'])) {
    header('Location: patient_login.php');
    exit;
}

$patient_id = $_SESSION['patient_id'];

// Fetch patient's current onboarding status
$stmt = $conn->prepare("SELECT po.*, ft.name as folder_name, ft.price as folder_price, ft.theme_color, ft.description as folder_desc 
                        FROM patient_onboarding po 
                        LEFT JOIN folder_types ft ON po.folder_type_id = ft.id 
                        WHERE po.patient_id = ?");
$stmt->bind_param("i", $patient_id);
$stmt->execute();
$onboarding = $stmt->get_result()->fetch_assoc();

// If not initialized in database, do so now
if (!$onboarding) {
    $conn->query("INSERT INTO patient_onboarding (patient_id, status) VALUES ($patient_id, 'Not Started')");
    header("Location: onboarding.php");
    exit;
}

// AUTO-CORRECT STUCK STATUS (Fix for blank screen)
if ($onboarding && (empty($onboarding['status']) || $onboarding['status'] == '')) {
    $new_status = 'Not Started';
    if (!empty($onboarding['folder_type_id'])) {
        $new_status = 'Payment Pending';
    }
    $conn->query("UPDATE patient_onboarding SET status = '$new_status' WHERE patient_id = $patient_id");
    header("Location: onboarding.php?recovered=1");
    exit;
}

// FORCE RESET if status is accidentally stuck (Optional safety)
if (isset($_GET['reset_onboarding'])) {
    $conn->query("UPDATE patient_onboarding SET status = 'Not Started', folder_type_id = NULL, payment_status = 'Pending' WHERE patient_id = $patient_id");
    header("Location: onboarding.php");
    exit;
}

// REDIRECTION LOGIC (STATE-BASED)
if ($onboarding['status'] === 'Completed') {
    header("Location: patient_dashboard.php");
    exit;
}

// 1. Handle Selection
if (isset($_POST['select_folder'])) {
    $fid = (int)$_POST['folder_id'];
    if ($fid > 0) {
        $stmt = $conn->prepare("UPDATE patient_onboarding SET folder_type_id = ?, status = 'Payment Pending', payment_status = 'Pending' WHERE patient_id = ?");
        $stmt->bind_param("ii", $fid, $patient_id);
        if ($stmt->execute()) {
            header("Location: onboarding.php?selected=1");
            exit;
        }
    }
    header("Location: onboarding.php?error=invalid_folder");
    exit;
}

// 2. Handle Payment Confirmation
if (isset($_POST['reset_to_payment'])) {
    $conn->query("UPDATE patient_onboarding SET status = 'Payment Pending', payment_status = 'Pending' WHERE patient_id = $patient_id");
    header("Location: onboarding.php?payment_reset=1");
    exit;
}

if (isset($_POST['confirm_payment'])) {
    if ($_POST['payment_method'] === 'transfer') {
        require_once 'api/billing_engine.php';
        $billing = new BillingEngine($conn);
        $price = (float)($onboarding['folder_price'] ?? 0);
        $folder_name = $onboarding['folder_name'] ?? 'Medical Folder';

        $billing_items = [['description' => "Medical Folder: " . $folder_name, 'type' => 'Other', 'quantity' => 1, 'price' => $price]];
        $invoice_id = $billing->automateInvoice($patient_id, $billing_items);

        $stmt = $conn->prepare("UPDATE patient_onboarding SET status = 'Awaiting Confirmation', payment_status = 'Awaiting Confirmation' WHERE patient_id = ?");
        $stmt->bind_param("i", $patient_id);
        $stmt->execute();

        $desc = "Manual Transfer for Folder: " . $folder_name;
        $stmt_pay = $conn->prepare("INSERT INTO payment_history (patient_id, amount, reference, method, description, invoice_id, status) VALUES (?, ?, 'TRANSFER', 'transfer', ?, ?, 'pending')");
        $stmt_pay->bind_param("issi", $patient_id, $price, $desc, $invoice_id);
        $stmt_pay->execute();

        header("Location: onboarding.php?transfer_submitted=1");
        exit;
    }
    // Gateway handled via JS
}

// 3. Handle Form Submission
if (isset($_POST['submit_registration_form'])) {
    if (isset($onboarding['is_locked']) && $onboarding['is_locked']) {
        die("This record is locked and cannot be edited.");
    }
    $sections = ['Personal Details', 'Contact/NOK', 'Medical History', 'Present Complaints'];
    $total_fields = 0;
    $filled_fields = 0;

    foreach ($_POST['form_data'] as $section => $fields) {
        foreach ($fields as $key => $value) {
            $total_fields++;
            if (!empty($value)) $filled_fields++;

            $stmt = $conn->prepare("INSERT INTO patient_form_data (patient_id, section_name, field_name, field_value) 
                                    VALUES (?, ?, ?, ?) 
                                    ON DUPLICATE KEY UPDATE field_value = VALUES(field_value)");
            $stmt->bind_param("isss", $patient_id, $section, $key, $value);
            $stmt->execute();
        }
    }
    
    // Calculate progress
    $progress = ($total_fields > 0) ? round(($filled_fields / $total_fields) * 100) : 0;

    // Set to Pending Records for Records Office Approval
    $stmt = $conn->prepare("UPDATE patient_onboarding SET status = 'Pending Records', form_progress = ? WHERE patient_id = ?");
    $stmt->bind_param("ii", $progress, $patient_id);
    $stmt->execute();

    require_once 'includes/sync_helper.php';
    SyncManager::signal('patient_queue', 'INSERT', $patient_id);

    header("Location: onboarding.php");
    exit;
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Onboarding | Hope Haven Hospital</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; background-color: #f8fafc; overflow-x: hidden; }
        .full-modal { position: fixed; inset: 0; background: white; z-index: 9999; overflow-y: auto; padding: 2rem; }
        .animate-in { animation: fadeIn 0.4s ease-out; }
        @keyframes fadeIn { from { opacity: 0; transform: scale(0.98); } to { opacity: 1; transform: scale(1); } }
        @keyframes pulseGlow { 0% { box-shadow: 0 0 0 0 rgba(59, 130, 246, 0.2); } 70% { box-shadow: 0 0 0 10px rgba(59, 130, 246, 0); } 100% { box-shadow: 0 0 0 0 rgba(59, 130, 246, 0); } }
        .animate-pulse-glow { animation: pulseGlow 2s infinite; }
        .locked-overlay { position: absolute; inset: 0; background: rgba(255,255,255,0.7); backdrop-filter: blur(2px); z-index: 50; display: flex; flex-direction: column; align-items: center; justify-content: center; border-radius: 40px; }
    </style>
</head>
<body class="min-h-screen">
    <!-- DEBUG INFO (REMOVE AFTER FIX) -->
    <?php if(isset($_GET['debug'])): ?>
    <div class="fixed bottom-0 right-0 bg-black text-white p-4 text-[10px] z-[99999] opacity-75">
        STATUS: <?php echo $onboarding['status'] ?? 'NULL'; ?><br>
        PAYMENT: <?php echo $onboarding['payment_status'] ?? 'NULL'; ?><br>
        FOLDER: <?php echo $onboarding['folder_name'] ?? 'NULL'; ?>
    </div>
    <?php endif; ?>

    <!-- 1. FOLDER SELECTION (FULL SCREEN MODAL) -->
    <?php if ($onboarding['status'] === 'Not Started' || empty($onboarding['status'])): ?>
        <div class="full-modal flex flex-col items-center">
            <div class="max-w-6xl w-full text-center py-10 animate-in">
                <div class="w-16 h-16 bg-blue-600 rounded-2xl flex items-center justify-center text-white font-black text-2xl mx-auto mb-6 shadow-xl shadow-blue-200">H</div>
                <h1 class="text-4xl font-extrabold text-slate-900 tracking-tight mb-4">Initialize Your Medical Profile</h1>
                <p class="text-slate-500 font-medium mb-16 text-lg">Select a folder type to begin your medical journey at Hope Haven Hospital.</p>

                <div class="grid md:grid-cols-3 gap-8">
                    <?php 
                        $res = $conn->query("SELECT * FROM folder_types");
                        while($f = $res->fetch_assoc()):
                            $colors = [
                                'blue' => ['bg' => 'bg-blue-600', 'light' => 'bg-blue-50', 'text' => 'text-blue-600', 'shadow' => 'shadow-blue-200/50'],
                                'emerald' => ['bg' => 'bg-emerald-500', 'light' => 'bg-emerald-50', 'text' => 'text-emerald-500', 'shadow' => 'shadow-emerald-200/50'],
                                'amber' => ['bg' => 'bg-amber-500', 'light' => 'bg-amber-50', 'text' => 'text-amber-500', 'shadow' => 'shadow-amber-200/50']
                            ];
                            $c = $colors[$f['theme_color']] ?? $colors['blue'];
                    ?>
                        <div class="bg-white rounded-[40px] p-10 border border-slate-100 shadow-xl shadow-slate-100 hover:shadow-2xl hover:-translate-y-2 transition-all group relative overflow-hidden text-center flex flex-col">
                            <div class="absolute -top-10 -right-10 w-32 h-32 <?php echo $c['light']; ?> rounded-full opacity-30 group-hover:scale-150 transition-transform"></div>
                            
                            <!-- UNIQUE CAPTION (TOP CENTER) -->
                            <div class="absolute top-2 left-1/2 -translate-x-1/2 z-10 w-max">
                                <span class="px-3 py-1.5 bg-blue-50/90 backdrop-blur-sm border border-blue-100 rounded-full text-[9px] font-black text-blue-600 uppercase tracking-widest shadow-sm animate-pulse-glow flex items-center gap-1">
                                    <i data-lucide="info" class="w-3 h-3"></i>
                                    Other Charges may apply
                                </span>
                            </div>

                            <div class="w-20 h-20 <?php echo $c['light']; ?> <?php echo $c['text']; ?> rounded-3xl flex items-center justify-center mb-10 mx-auto group-hover:scale-110 transition-transform">
                                <i data-lucide="folder-key" class="w-10 h-10"></i>
                            </div>

                            <h3 class="text-2xl font-black text-slate-900 mb-4"><?php echo $f['name']; ?></h3>
                            <p class="text-sm text-slate-500 font-medium leading-relaxed mb-8 flex-1"><?php echo $f['description']; ?></p>
                            
                            <div class="mb-10 p-4 rounded-3xl <?php echo $c['light']; ?>">
                                <span class="text-[10px] font-black <?php echo $c['text']; ?> uppercase tracking-[0.2em] block mb-1">One-Time Activation</span>
                                <span class="text-3xl font-black text-slate-900">₦<?php echo number_format($f['price']); ?></span>
                            </div>

                            <form method="POST">
                                <input type="hidden" name="folder_id" value="<?php echo $f['id']; ?>">
                                <button type="submit" name="select_folder" class="w-full py-5 <?php echo $c['bg']; ?> text-white rounded-2xl font-black uppercase tracking-widest text-sm shadow-xl <?php echo $c['shadow']; ?> hover:brightness-110 transition-all">
                                    Select Folder
                                </button>
                            </form>
                        </div>
                    <?php endwhile; ?>
                </div>
                <p class="mt-20 text-[10px] font-bold text-slate-400 uppercase tracking-widest px-10">Note: Clinical services and file initialization require an active medical folder.</p>
            </div>
        </div>
    <?php endif; ?>

    <!-- 2. PAYMENT GATEWAY (STRICT REDIRECT) -->
    <?php if ($onboarding['status'] === 'Payment Pending'): ?>
        <div class="full-modal flex flex-col items-center bg-slate-50">
            <div class="max-w-md w-full animate-in py-10">
                <div class="bg-white rounded-[40px] p-10 shadow-2xl border border-slate-100 text-center">
                    <div class="w-20 h-20 bg-blue-50 text-blue-600 rounded-full flex items-center justify-center mb-8 mx-auto shadow-inner">
                        <i data-lucide="credit-card" class="w-10 h-10"></i>
                    </div>
                    <h2 class="text-3xl font-black text-slate-900 mb-2">Folder Activation</h2>
                    <p class="text-slate-500 font-medium mb-10">Complete your folder payment to unlock your medical registration form.</p>
                    
                    <div class="p-8 bg-slate-50 rounded-3xl border border-slate-100 mb-8 text-left space-y-4">
                        <div class="flex justify-between items-center">
                            <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Selected Item</span>
                            <span class="text-xs font-black text-blue-600"><?php echo $onboarding['folder_name'] ?? 'Medical Folder'; ?></span>
                        </div>
                        <div class="h-px bg-slate-200"></div>
                        <div class="flex justify-between items-center">
                            <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Amount Due</span>
                            <span class="text-2xl font-black text-slate-900">₦<?php echo number_format($onboarding['folder_price'] ?? 0); ?></span>
                        </div>
                    </div>

                    <form method="POST" class="space-y-4">
                        <input type="hidden" name="payment_method" id="payment_method" value="gateway">
                        
                        <div class="grid grid-cols-2 gap-4 mb-6">
                            <button type="button" onclick="setPaymentMethod('gateway')" id="btn-gateway" class="p-4 rounded-2xl border-2 border-blue-600 bg-blue-50 text-blue-600 font-bold text-xs transition-all">
                                <i data-lucide="credit-card" class="w-4 h-4 mx-auto mb-2"></i>
                                Online Card
                            </button>
                            <button type="button" onclick="setPaymentMethod('transfer')" id="btn-transfer" class="p-4 rounded-2xl border-2 border-slate-100 text-slate-400 font-bold text-xs transition-all">
                                <i data-lucide="banknote" class="w-4 h-4 mx-auto mb-2"></i>
                                Bank Transfer
                            </button>
                        </div>

                        <div id="transfer-instructions" class="hidden p-6 bg-slate-900 rounded-3xl text-white text-left mb-6 animate-in">
                            <p class="text-[10px] font-black text-blue-400 uppercase tracking-widest mb-4">Transfer Details</p>
                            <div class="space-y-3">
                                <div>
                                    <p class="text-[9px] text-slate-400 uppercase">Bank Name</p>
                                    <p class="text-sm font-bold">Zenith Bank</p>
                                </div>
                                <div>
                                    <p class="text-[9px] text-slate-400 uppercase">Account Number</p>
                                    <p class="text-sm font-bold tracking-widest">1012345678</p>
                                </div>
                                <div>
                                    <p class="text-[9px] text-slate-400 uppercase">Account Name</p>
                                    <p class="text-sm font-bold">Hope Haven Hospital</p>
                                </div>
                            </div>
                            <p class="text-[9px] text-slate-500 mt-6 italic">* Click confirm after making your transfer.</p>
                        </div>

                        <button type="submit" name="confirm_payment" id="confirm-payment-btn" class="w-full py-5 bg-blue-600 text-white rounded-2xl font-black uppercase tracking-widest text-sm shadow-xl shadow-blue-100 hover:bg-blue-700 transition-all">
                            Confirm Payment (₦<?php echo number_format($onboarding['folder_price'] ?? 0); ?>)
                        </button>
                        <?php if($onboarding['payment_status'] === 'Failed'): ?>
                            <p class="text-rose-500 text-xs font-bold mt-2 text-center">Last payment attempt failed. Please try again.</p>
                        <?php endif; ?>
                        <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mt-6">Secure Hospital Payment Gateway</p>
                    </form>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- 2.5 AWAITING CONFIRMATION HUB -->
    <?php if ($onboarding['status'] === 'Awaiting Confirmation'): ?>
        <div class="full-modal flex flex-col items-center justify-center bg-slate-50">
            <div class="max-w-md w-full animate-in py-10">
                <div class="bg-white rounded-[40px] p-10 shadow-2xl border border-slate-100 text-center">
                    <div class="w-24 h-24 bg-amber-50 text-amber-500 rounded-full flex items-center justify-center mb-8 mx-auto animate-pulse">
                        <i data-lucide="clock" class="w-12 h-12"></i>
                    </div>
                    <h2 class="text-3xl font-black text-slate-900 mb-2">Verifying Payment</h2>
                    <p class="text-slate-500 font-medium mb-10 leading-relaxed">Your transfer is being verified by our finance department. This usually takes 5-15 minutes.</p>
                    
                    <div class="p-8 bg-amber-50 rounded-3xl border border-amber-100 mb-10 text-left">
                        <div class="flex items-center gap-4 mb-4">
                            <div class="w-2 h-2 bg-amber-500 rounded-full"></div>
                            <span class="text-[10px] font-black text-amber-700 uppercase tracking-widest">Accountant Review Pending</span>
                        </div>
                        <p class="text-xs font-bold text-slate-600">Once confirmed, your medical registration form will be automatically unlocked.</p>
                    </div>

                    <div class="flex flex-col gap-4">
                        <button onclick="location.reload()" class="w-full py-5 bg-slate-900 text-white rounded-2xl font-black uppercase tracking-widest text-sm shadow-xl shadow-slate-200">
                            Check Status
                        </button>
                        <form method="POST">
                            <button type="submit" name="reset_to_payment" class="w-full py-4 bg-white text-blue-600 border-2 border-blue-600 rounded-2xl font-black uppercase tracking-widest text-[10px] hover:bg-blue-50 transition-all">
                                Change Payment Method / Pay with Card
                            </button>
                        </form>
                        <a href="logout.php" class="text-xs font-bold text-slate-400 hover:text-rose-500 underline underline-offset-8 transition-all">
                            Log out and check back later
                        </a>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- 3. MULTI-STEP FORM (UNLOCK AFTER PAYMENT) -->
    <?php if ($onboarding['status'] === 'Paid' && $onboarding['payment_status'] === 'Confirmed'): ?>
        <div class="max-w-4xl mx-auto py-20 px-6 animate-in">
            <div class="bg-white rounded-[40px] shadow-2xl border border-slate-100 overflow-hidden">
                <!-- Header & Progress -->
                <div class="p-10 border-b border-slate-100 bg-slate-50/50 flex justify-between items-center">
                    <div>
                        <h2 class="text-2xl font-black text-slate-900 tracking-tight">Medical Registration</h2>
                        <p class="text-xs font-bold text-slate-400 uppercase tracking-widest mt-1">NEW PATIENT FORM: <span class="text-blue-600"><?php echo $onboarding['folder_name']; ?></span></p>
                    </div>
                    <div class="text-right">
                        <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Form Progress</p>
                        <div class="w-40 h-2 bg-slate-200 rounded-full overflow-hidden">
                            <div id="form-progress-bar" class="h-full bg-blue-600 transition-all duration-500" style="width: 20%"></div>
                        </div>
                    </div>
                </div>

                <form id="multiStepForm" method="POST" class="p-10">
                    <!-- Step 1: Personal Details -->
                    <div class="form-step space-y-10" data-step="1">
                        <div class="flex items-center gap-4">
                            <div class="w-12 h-12 bg-blue-600 text-white rounded-2xl flex items-center justify-center font-black text-xl shadow-lg shadow-blue-100">1</div>
                            <h3 class="text-2xl font-black text-slate-900">Personal Details</h3>
                        </div>
                        <div class="grid md:grid-cols-2 gap-8">
                            <div class="space-y-2">
                                <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-4">Gender</label>
                                <select name="form_data[Personal Details][gender]" required class="w-full px-6 py-4 bg-slate-50 rounded-2xl border-0 font-bold focus:ring-2 focus:ring-blue-600 outline-none">
                                    <option value="">Select Gender</option>
                                    <option value="Male">Male</option>
                                    <option value="Female">Female</option>
                                </select>
                            </div>
                            <div class="space-y-2">
                                <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-4">Date of Birth</label>
                                <input type="date" name="form_data[Personal Details][dob]" required class="w-full px-6 py-4 bg-slate-50 rounded-2xl border-0 font-bold focus:ring-2 focus:ring-blue-600 outline-none">
                            </div>
                            <div class="space-y-2">
                                <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-4">Marital Status</label>
                                <select name="form_data[Personal Details][marital_status]" class="w-full px-6 py-4 bg-slate-50 rounded-2xl border-0 font-bold focus:ring-2 focus:ring-blue-600 outline-none">
                                    <option value="Single">Single</option>
                                    <option value="Married">Married</option>
                                    <option value="Divorced">Divorced</option>
                                </select>
                            </div>
                            <div class="space-y-2">
                                <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-4">Occupation</label>
                                <input type="text" name="form_data[Personal Details][occupation]" required class="w-full px-6 py-4 bg-slate-50 rounded-2xl border-0 font-bold focus:ring-2 focus:ring-blue-600 outline-none">
                            </div>
                        </div>
                    </div>

                    <!-- Step 2: Contact / NOK -->
                    <div class="form-step space-y-10 hidden" data-step="2">
                        <div class="flex items-center gap-4">
                            <div class="w-12 h-12 bg-blue-600 text-white rounded-2xl flex items-center justify-center font-black text-xl shadow-lg shadow-blue-100">2</div>
                            <h3 class="text-2xl font-black text-slate-900">Contact / Next of Kin</h3>
                        </div>
                        <div class="grid md:grid-cols-2 gap-8">
                            <div class="md:col-span-2 space-y-2">
                                <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-4">Residential Address</label>
                                <textarea name="form_data[Contact/NOK][address]" required class="w-full px-6 py-4 bg-slate-50 rounded-2xl border-0 font-bold focus:ring-2 focus:ring-blue-600 outline-none h-24 resize-none"></textarea>
                            </div>
                            <div class="space-y-2">
                                <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-4">Next of Kin Name</label>
                                <input type="text" name="form_data[Contact/NOK][nok_name]" required class="w-full px-6 py-4 bg-slate-50 rounded-2xl border-0 font-bold focus:ring-2 focus:ring-blue-600 outline-none">
                            </div>
                            <div class="space-y-2">
                                <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-4">Next of Kin Phone</label>
                                <input type="text" name="form_data[Contact/NOK][nok_phone]" required class="w-full px-6 py-4 bg-slate-50 rounded-2xl border-0 font-bold focus:ring-2 focus:ring-blue-600 outline-none">
                            </div>
                        </div>
                    </div>

                    <!-- Step 3: Medical History -->
                    <div class="form-step space-y-10 hidden" data-step="3">
                        <div class="flex items-center gap-4">
                            <div class="w-12 h-12 bg-blue-600 text-white rounded-2xl flex items-center justify-center font-black text-xl shadow-lg shadow-blue-100">3</div>
                            <h3 class="text-2xl font-black text-slate-900">Medical History</h3>
                        </div>
                        <div class="space-y-6">
                            <p class="text-sm font-bold text-slate-500 ml-4">Have you ever had any of the following? (Select all that apply)</p>
                            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                                <?php $conds = ['Hypertension', 'Diabetes', 'Asthma', 'Heart Condition', 'Sickle Cell', 'Tuberculosis', 'Ulcer', 'Epilepsy']; 
                                foreach($conds as $c): ?>
                                    <label class="flex items-center gap-3 p-4 bg-slate-50 rounded-2xl border border-slate-100 cursor-pointer hover:bg-blue-50 transition-all">
                                        <input type="checkbox" name="form_data[Medical History][<?php echo $c; ?>]" value="Yes" class="w-5 h-5 rounded-lg border-slate-200 text-blue-600 focus:ring-blue-600">
                                        <span class="text-xs font-bold text-slate-700"><?php echo $c; ?></span>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                            <div class="space-y-2">
                                <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-4">Drug Allergies</label>
                                <input type="text" name="form_data[Medical History][allergies]" class="w-full px-6 py-4 bg-slate-50 rounded-2xl border-0 font-bold focus:ring-2 focus:ring-blue-600 outline-none" placeholder="e.g. Penicillin, Sulfa drugs">
                            </div>
                        </div>
                    </div>

                    <!-- Step 4: Present Complaints -->
                    <div class="form-step space-y-10 hidden" data-step="4">
                        <div class="flex items-center gap-4">
                            <div class="w-12 h-12 bg-blue-600 text-white rounded-2xl flex items-center justify-center font-black text-xl shadow-lg shadow-blue-100">4</div>
                            <h3 class="text-2xl font-black text-slate-900">Present Complaints</h3>
                        </div>
                        <div class="space-y-6">
                            <div class="space-y-2">
                                <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-4">Primary Complaint</label>
                                <textarea name="form_data[Present Complaints][primary_complaint]" required class="w-full px-6 py-4 bg-slate-50 rounded-3xl border-0 font-bold focus:ring-2 focus:ring-blue-600 outline-none h-32 resize-none" placeholder="What brings you to the hospital today?"></textarea>
                            </div>
                            <div class="space-y-2">
                                <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-4">Duration of Symptoms</label>
                                <input type="text" name="form_data[Present Complaints][duration]" required class="w-full px-6 py-4 bg-slate-50 rounded-2xl border-0 font-bold focus:ring-2 focus:ring-blue-600 outline-none" placeholder="e.g. 3 Days, 1 Month">
                            </div>
                        </div>
                    </div>

                    <!-- Step 5: Review & Submit -->
                    <div class="form-step space-y-10 hidden text-center py-10" data-step="5">
                        <div class="w-24 h-24 bg-emerald-50 text-emerald-500 rounded-full flex items-center justify-center mx-auto mb-8 animate-bounce">
                            <i data-lucide="check-circle-2" class="w-12 h-12"></i>
                        </div>
                        <h3 class="text-3xl font-black text-slate-900 mb-4">Form Complete</h3>
                        <p class="text-slate-500 font-medium px-20">Please review your entries. Once submitted, your file will be sent to the Records Department for final activation.</p>
                    </div>

                    <!-- Navigation Footer -->
                    <div class="mt-16 flex justify-between items-center border-t border-slate-100 pt-10">
                        <button type="button" id="prevBtn" class="px-8 py-4 bg-slate-100 text-slate-400 rounded-2xl font-bold hover:bg-slate-200 transition-all hidden">Previous Step</button>
                        <div class="flex-1"></div>
                        <button type="button" id="nextBtn" class="px-10 py-4 bg-slate-900 text-white rounded-2xl font-black uppercase tracking-widest text-xs shadow-xl shadow-slate-100 hover:bg-blue-600 transition-all">Next Step</button>
                        <button type="submit" name="submit_registration_form" id="submitBtn" class="px-10 py-4 bg-emerald-500 text-white rounded-2xl font-black uppercase tracking-widest text-xs shadow-xl shadow-emerald-100 hover:bg-emerald-600 transition-all hidden">Finalize Submission</button>
                    </div>
                </form>
            </div>
        </div>
    <?php endif; ?>

    <!-- 4. STATUS HUB / VERIFICATION TRACKER -->
    <?php if (in_array($onboarding['status'], ['Pending Records', 'Verified', 'Sent to Nursing', 'In Intake'])): ?>
        <div class="full-modal flex flex-col items-center justify-center bg-slate-50">
            <div class="max-w-2xl w-full animate-in px-6">
                <!-- Welcome Card -->
                <div class="bg-white rounded-[40px] shadow-2xl border border-slate-100 overflow-hidden mb-8">
                    <div class="p-10 text-center border-b border-slate-50 bg-slate-50/30">
                        <div class="w-20 h-20 bg-blue-600 text-white rounded-3xl flex items-center justify-center mx-auto mb-6 shadow-xl shadow-blue-200">
                            <i data-lucide="shield-check" class="w-10 h-10"></i>
                        </div>
                        <h2 class="text-3xl font-black text-slate-900 mb-2 tracking-tight">Registration in Progress</h2>
                        <p class="text-slate-500 font-medium">We are preparing your medical file for the clinical team.</p>
                    </div>

                    <!-- Step Tracker -->
                    <div class="p-10 space-y-8">
                        <!-- Step 1: Payment -->
                        <div class="flex items-center gap-6">
                            <div class="w-10 h-10 bg-emerald-500 text-white rounded-full flex items-center justify-center shrink-0">
                                <i data-lucide="check" class="w-5 h-5"></i>
                            </div>
                            <div class="flex-1">
                                <h4 class="text-sm font-black text-slate-900">Folder Activation & Payment</h4>
                                <p class="text-xs font-bold text-emerald-500 uppercase tracking-widest">Confirmed</p>
                            </div>
                        </div>

                        <!-- Step 2: Form Submission -->
                        <div class="flex items-center gap-6">
                            <div class="w-10 h-10 bg-emerald-500 text-white rounded-full flex items-center justify-center shrink-0">
                                <i data-lucide="check" class="w-5 h-5"></i>
                            </div>
                            <div class="flex-1">
                                <h4 class="text-sm font-black text-slate-900">Medical History & Form Entry</h4>
                                <p class="text-xs font-bold text-emerald-500 uppercase tracking-widest">Submitted (<?php echo $onboarding['form_progress']; ?>%)</p>
                            </div>
                        </div>

                        <!-- Step 3: Records Review -->
                        <?php 
                            $is_reviewing = ($onboarding['status'] === 'Pending Records');
                            $is_done = in_array($onboarding['status'], ['Verified', 'Sent to Nursing', 'In Intake']);
                        ?>
                        <div class="flex items-center gap-6">
                            <div class="w-10 h-10 <?php echo $is_done ? 'bg-emerald-500 text-white' : ($is_reviewing ? 'bg-amber-100 text-amber-600 animate-pulse' : 'bg-slate-100 text-slate-300'); ?> rounded-full flex items-center justify-center shrink-0">
                                <i data-lucide="<?php echo $is_done ? 'check' : 'search'; ?>" class="w-5 h-5"></i>
                            </div>
                            <div class="flex-1">
                                <h4 class="text-sm font-black <?php echo $is_reviewing || $is_done ? 'text-slate-900' : 'text-slate-400'; ?>">Records Officer Verification</h4>
                                <p class="text-xs font-bold <?php echo $is_done ? 'text-emerald-500' : ($is_reviewing ? 'text-amber-500' : 'text-slate-300'); ?> uppercase tracking-widest">
                                    <?php echo $is_done ? 'Verified ✓' : ($is_reviewing ? 'Under Review...' : 'Awaiting Review'); ?>
                                </p>
                            </div>
                        </div>

                        <!-- Step 4: Nursing Station -->
                        <?php 
                            $is_sent = ($onboarding['status'] === 'Sent to Nursing'); 
                            $is_intake = ($onboarding['status'] === 'In Intake');
                        ?>
                        <div class="flex items-center gap-6">
                            <div class="w-10 h-10 <?php echo ($is_sent || $is_intake) ? 'bg-blue-600 text-white' : 'bg-slate-100 text-slate-300'; ?> rounded-full flex items-center justify-center shrink-0">
                                <i data-lucide="thermometer" class="w-5 h-5"></i>
                            </div>
                            <div class="flex-1">
                                <h4 class="text-sm font-black <?php echo ($is_sent || $is_intake) ? 'text-slate-900' : 'text-slate-400'; ?>">Proceed to Nursing Triage</h4>
                                <p class="text-xs font-bold <?php echo ($is_sent || $is_intake) ? 'text-blue-600' : 'text-slate-300'; ?> uppercase tracking-widest">
                                    <?php echo $is_intake ? 'Currently with Nurse' : ($is_sent ? 'Ready for Vitals' : 'Next Step'); ?>
                                </p>
                            </div>
                        </div>
                    </div>

                    <?php if($is_sent || $is_intake): ?>
                        <div class="p-8 bg-blue-50 border-t border-blue-100 text-center">
                            <p class="text-sm font-bold text-blue-700 mb-6"><?php echo $is_intake ? 'Your intake is in progress. You can now access your health dashboard.' : 'Your file is now active. Please proceed to the vitals station at the hospital.'; ?></p>
                            <a href="patient_dashboard.php" class="inline-block px-10 py-4 bg-blue-600 text-white rounded-2xl font-black uppercase tracking-widest text-xs shadow-xl shadow-blue-200 hover:bg-blue-700 transition-all">
                                Enter My Dashboard
                            </a>
                        </div>
                    <?php else: ?>
                        <div class="p-8 bg-slate-50 border-t border-slate-100 text-center">
                            <a href="logout.php" class="text-xs font-bold text-slate-400 hover:text-rose-500 underline underline-offset-8 transition-all">Log out and check back later</a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- 5. EMERGENCY FALLBACK (Ensures no blank screen) -->
    <?php
    $matched = false;
    $statuses = ['Not Started', 'Payment Pending', 'Awaiting Confirmation', 'Paid', 'Pending Records', 'Verified', 'Sent to Nursing', 'In Intake', 'In Progress'];
    foreach($statuses as $s) {
        if ($onboarding['status'] === $s) { $matched = true; break; }
    }
    if (!$matched && $onboarding['status'] !== 'Completed'): 
    ?>
        <div class="full-modal flex flex-col items-center justify-center">
            <div class="text-center p-10 bg-white rounded-[40px] shadow-2xl border border-slate-100 max-w-md">
                <div class="w-20 h-20 bg-rose-50 text-rose-500 rounded-3xl flex items-center justify-center mx-auto mb-6">
                    <i data-lucide="alert-triangle" class="w-10 h-10"></i>
                </div>
                <h2 class="text-2xl font-black text-slate-900 mb-2">Session Sync Issue</h2>
                <p class="text-slate-500 font-medium mb-8">We found a slight mismatch in your registration progress. Click below to refresh and fix it.</p>
                <a href="onboarding.php?reset_onboarding=1" class="block w-full py-5 bg-blue-600 text-white rounded-2xl font-black uppercase tracking-widest text-xs shadow-xl shadow-blue-100">
                    Repair & Restart Onboarding
                </a>
            </div>
        </div>
    <?php endif; ?>

    <script>
        lucide.createIcons();

        // Check for payment success from redirect
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.get('status') === 'success') {
            Swal.fire({
                title: 'Payment Completed!',
                text: 'Your medical folder has been activated. Please proceed to complete your registration form.',
                icon: 'success',
                confirmButtonText: 'Start Registration',
                confirmButtonColor: '#2563eb',
                allowOutsideClick: false,
                customClass: {
                    container: 'z-[10000]',
                    popup: 'rounded-[40px] p-10',
                    confirmButton: 'px-10 py-4 rounded-2xl font-black uppercase tracking-widest text-xs'
                }
            }).then(() => {
                // Clear URL params without reload
                window.history.replaceState({}, document.title, window.location.pathname);
            });
        }

        function setPaymentMethod(method) {
            document.getElementById('payment_method').value = method;
            const btnGateway = document.getElementById('btn-gateway');
            const btnTransfer = document.getElementById('btn-transfer');
            const instructions = document.getElementById('transfer-instructions');

            if (method === 'transfer') {
                btnTransfer.classList.add('border-blue-600', 'bg-blue-50', 'text-blue-600');
                btnTransfer.classList.remove('border-slate-100', 'text-slate-400');
                btnGateway.classList.remove('border-blue-600', 'bg-blue-50', 'text-blue-600');
                btnGateway.classList.add('border-slate-100', 'text-slate-400');
                instructions.classList.remove('hidden');
            } else {
                btnGateway.classList.add('border-blue-600', 'bg-blue-50', 'text-blue-600');
                btnGateway.classList.remove('border-slate-100', 'text-slate-400');
                btnTransfer.classList.remove('border-blue-600', 'bg-blue-50', 'text-blue-600');
                btnTransfer.classList.add('border-slate-100', 'text-slate-400');
                instructions.classList.add('hidden');
            }
        }

        // Real-time Sync for Onboarding Progress
        let reloadTimeout = null;
        function throttledReload() {
            if (reloadTimeout) return;
            reloadTimeout = setTimeout(() => {
                location.reload();
            }, 2000);
        }

        if (window.HospitalSync) {
            window.HospitalSync.subscribe('billing', (signal) => {
                console.log('📡 [Onboarding] Payment Signal Received');
                throttledReload();
            });
            window.HospitalSync.subscribe('patient_queue', (signal) => {
                console.log('📡 [Onboarding] Status Update Received');
                throttledReload();
            });
        }

        // Handle Gateway Payment
        const confirmBtn = document.getElementById('confirm-payment-btn');
        if (confirmBtn) {
            confirmBtn.addEventListener('click', function(e) {
                const method = document.getElementById('payment_method').value;
                if (method === 'gateway') {
                    e.preventDefault();
                    confirmBtn.innerText = 'Initializing Gateway...';
                    confirmBtn.disabled = true;

                    fetch('api/init_onboarding_payment.php', {
                        method: 'POST'
                    })
                    .then(r => r.json())
                    .then(data => {
                        if (data.status === 'success') {
                            window.location.href = data.authorization_url;
                        } else {
                            alert(data.message || 'Initialization failed');
                            confirmBtn.innerText = 'Confirm Payment';
                            confirmBtn.disabled = false;
                        }
                    })
                    .catch(err => {
                        console.error(err);
                        alert('System Error. Please try again.');
                        confirmBtn.innerText = 'Confirm Payment';
                        confirmBtn.disabled = false;
                    });
                }
            });
        }

        // Multi-Step Form Logic
        const steps = document.querySelectorAll('.form-step');
        const nextBtn = document.getElementById('nextBtn');
        const prevBtn = document.getElementById('prevBtn');
        const submitBtn = document.getElementById('submitBtn');
        const progressBar = document.getElementById('form-progress-bar');
        let currentStep = 1;

        if (nextBtn) {
            nextBtn.addEventListener('click', () => {
                // Validation for required fields in current step
                const currentInputs = steps[currentStep - 1].querySelectorAll('input[required], select[required], textarea[required]');
                let valid = true;
                currentInputs.forEach(input => {
                    if (!input.value) {
                        input.classList.add('ring-2', 'ring-rose-500');
                        valid = false;
                    } else {
                        input.classList.remove('ring-2', 'ring-rose-500');
                    }
                });

                if (!valid) return;

                if (currentStep < steps.length) {
                    steps[currentStep - 1].classList.add('hidden');
                    currentStep++;
                    steps[currentStep - 1].classList.remove('hidden');
                    updateUI();
                }
            });

            prevBtn.addEventListener('click', () => {
                if (currentStep > 1) {
                    steps[currentStep - 1].classList.add('hidden');
                    currentStep--;
                    steps[currentStep - 1].classList.remove('hidden');
                    updateUI();
                }
            });

            function updateUI() {
                // Navigation buttons
                if (currentStep === 1) prevBtn.classList.add('hidden');
                else prevBtn.classList.remove('hidden');

                if (currentStep === steps.length) {
                    nextBtn.classList.add('hidden');
                    submitBtn.classList.remove('hidden');
                } else {
                    nextBtn.classList.remove('hidden');
                    submitBtn.classList.add('hidden');
                }

                // Progress Bar
                const progress = (currentStep / steps.length) * 100;
                progressBar.style.width = `${progress}%`;
                
                window.scrollTo({ top: 0, behavior: 'smooth' });
            }
        }
    </script>
</body>
</html>