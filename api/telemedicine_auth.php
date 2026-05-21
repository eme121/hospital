<?php
session_start();
require_once '../includes/db_connect.php';

header('Content-Type: application/json');

$action = $_GET['action'] ?? '';

if ($action === 'register') {
    $name = $_POST['name'] ?? '';
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $dept_id = intval($_POST['department'] ?? 0);
    
    if (empty($name) || empty($email) || empty($password) || $dept_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Please fill all fields.']);
        exit;
    }

    // Check if email exists
    $check = $conn->prepare("SELECT id FROM telemedicine_doctors WHERE email = ?");
    $check->bind_param("s", $email);
    $check->execute();
    if ($check->get_result()->num_rows > 0) {
        echo json_encode(['success' => false, 'message' => 'Email already registered.']);
        exit;
    }

    // Handle Profile Pic Upload
    $profile_pix = 'https://images.unsplash.com/photo-1612349317150-e413f6a5b16d?auto=format&fit=crop&q=80&w=200';
    if (isset($_FILES['profile_pix']) && $_FILES['profile_pix']['error'] === 0) {
        $target_dir = "../assets/images/doctors/";
        if (!is_dir($target_dir)) mkdir($target_dir, 0777, true);
        
        $file_ext = pathinfo($_FILES['profile_pix']['name'], PATHINFO_EXTENSION);
        $file_name = uniqid() . "." . $file_ext;
        $target_file = $target_dir . $file_name;
        
        if (move_uploaded_file($_FILES['profile_pix']['tmp_name'], $target_file)) {
            $profile_pix = "assets/images/doctors/" . $file_name;
        }
    }

    $hashed_pass = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $conn->prepare("INSERT INTO telemedicine_doctors (name, email, password, department_id, profile_pix) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("sssis", $name, $email, $hashed_pass, $dept_id, $profile_pix);

    if ($stmt->execute()) {
        // Create Admin Notification
        create_notification('doctor_approval', 'New Specialist Registration', "Dr. $name applied for telemedicine access.", 'specialists.php');

        // Notify Admin of new registration
        require_once '../includes/config.php';
        require_once '../includes/SimpleSMTP.php';
        $smtp = new SimpleSMTP(SMTP_HOST, SMTP_PORT, SMTP_USER, SMTP_PASS);
        $admin_subject = "New Specialist Registration: $name";
        $admin_message = "A new medical specialist has registered on the platform.\n\n" .
                        "Name: $name\n" .
                        "Email: $email\n" .
                        "Department ID: $dept_id\n\n" .
                        "Please log in to the admin dashboard to review and approve.";
        @$smtp->send(SMTP_USER, $admin_subject, $admin_message, FROM_EMAIL, FROM_NAME);

        echo json_encode(['success' => true, 'message' => 'Registration successful! Please wait for admin approval before logging in.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $conn->error]);
    }
}

if ($action === 'login') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';

    // Check telemedicine_doctors first
    $stmt = $conn->prepare("SELECT * FROM telemedicine_doctors WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $doctor = $result->fetch_assoc();
        if (password_verify($password, $doctor['password'])) {
            if ($doctor['status'] === 'Approved') {
                $_SESSION['doctor_id'] = $doctor['id'];
                $_SESSION['doctor_name'] = $doctor['name'];
                $_SESSION['doctor_pix'] = $doctor['profile_pix'];
                $_SESSION['doctor_type'] = 'telemedicine';
                echo json_encode(['success' => true]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Your account is pending admin approval.']);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Invalid password.']);
        }
        exit;
    }

    // Check regular doctors second
    $stmt2 = $conn->prepare("SELECT * FROM doctors WHERE email = ?");
    $stmt2->bind_param("s", $email);
    $stmt2->execute();
    $result2 = $stmt2->get_result();

    if ($result2->num_rows === 1) {
        $doctor = $result2->fetch_assoc();
        if (password_verify($password, $doctor['password'])) {
            // SYNC to telemedicine_doctors if not exists
            $check_tele = $conn->prepare("SELECT id, name, profile_pix FROM telemedicine_doctors WHERE email = ?");
            $check_tele->bind_param("s", $email);
            $check_tele->execute();
            $tele_res = $check_tele->get_result();
            
            if ($tele_res->num_rows === 0) {
                // Auto-sync regular doctor to telemedicine_doctors
                $ins_stmt = $conn->prepare("INSERT INTO telemedicine_doctors (name, email, password, department_id, profile_pix, status) VALUES (?, ?, ?, ?, ?, 'Approved')");
                $ins_stmt->bind_param("sssis", $doctor['name'], $email, $doctor['password'], $doctor['department_id'], $doctor['image_url']);
                $ins_stmt->execute();
                $tele_doctor_id = $ins_stmt->insert_id;
                $tele_doctor_name = $doctor['name'];
                $tele_doctor_pix = $doctor['image_url'];
            } else {
                $tele_doctor = $tele_res->fetch_assoc();
                $tele_doctor_id = $tele_doctor['id'];
                $tele_doctor_name = $tele_doctor['name'];
                $tele_doctor_pix = $tele_doctor['profile_pix'];
            }

            $_SESSION['doctor_id'] = $tele_doctor_id;
            $_SESSION['doctor_name'] = $tele_doctor_name;
            $_SESSION['doctor_pix'] = $tele_doctor_pix;
            $_SESSION['doctor_type'] = 'telemedicine_synced';
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Invalid password.']);
        }
        exit;
    }

    echo json_encode(['success' => false, 'message' => 'Email not found in our medical staff records.']);
}

if ($action === 'logout') {
    session_destroy();
    header('Location: ../telemedicine.php');
}
?>