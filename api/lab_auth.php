<?php
session_start();
require_once '../includes/db_connect.php';

header('Content-Type: application/json');

$action = $_GET['action'] ?? '';

if ($action === 'login') {
    $email = $_POST['email'];
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT * FROM lab_technicians WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        if (password_verify($password, $user['password'])) {
            $_SESSION['lab_tech_id'] = $user['id'];
            $_SESSION['lab_tech_name'] = $user['name'];
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Invalid credentials.']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Account not found.']);
    }
}

if ($action === 'logout') {
    session_destroy();
    header('Location: ../lab/login.php');
}
?>