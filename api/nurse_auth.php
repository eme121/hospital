<?php
session_start();
require_once '../includes/db_connect.php';

header('Content-Type: application/json');

$action = $_GET['action'] ?? '';

if ($action === 'login') {
    $email = $_POST['email'];
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT * FROM nurses WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        if (password_verify($password, $user['password'])) {
            $_SESSION['nurse_id'] = $user['id'];
            $_SESSION['nurse_name'] = $user['name'];
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Invalid password.']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Nurse account not found.']);
    }
}

if ($action === 'register_patient') {
    if (!isset($_SESSION['nurse_id'])) {
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        exit;
    }

    $name = $_POST['name'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];
    $gender = $_POST['gender'];
    $age = $_POST['age'];
    $password = password_hash($_POST['password'] ?: '123456', PASSWORD_DEFAULT); // Default password if none provided

    // Check if email exists if provided
    if ($email) {
        $check = $conn->prepare("SELECT id FROM patients WHERE email = ?");
        $check->bind_param("s", $email);
        $check->execute();
        if ($check->get_result()->num_rows > 0) {
            echo json_encode(['success' => false, 'message' => 'An account with this email already exists.']);
            exit;
        }
    }

    $stmt = $conn->prepare("INSERT INTO patients (file_number, full_name, email, phone, gender, age, password) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $placeholder_file = "PENDING";
    $stmt->bind_param("sssssis", $placeholder_file, $name, $email, $phone, $gender, $age, $password);
    
    if ($stmt->execute()) {
        $new_id = $stmt->insert_id;
        $year = date('Y');
        $file_number = "HH-" . $year . "-" . str_pad($new_id, 4, '0', STR_PAD_LEFT);
        
        $update = $conn->prepare("UPDATE patients SET file_number = ? WHERE id = ?");
        $update->bind_param("si", $file_number, $new_id);
        $update->execute();

        echo json_encode(['success' => true, 'message' => 'Patient registered successfully. File Number: ' . $file_number]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Registration failed: ' . $conn->error]);
    }
    exit;
}

if ($action === 'logout') {
    session_destroy();
    header('Location: ../nurse/login.php');
}
?>