<?php
session_start();
require_once '../includes/db_connect.php';

header('Content-Type: application/json');

if (!isset($_SESSION['admin_logged_in'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$action = $_GET['action'] ?? '';

if ($action === 'assign_shift') {
    $type = $_POST['staff_type'];
    $id = intval($_POST['staff_id']);
    $date = $_POST['shift_date'];
    $time = $_POST['shift_time'];

    $stmt = $conn->prepare("INSERT INTO staff_roster (staff_type, staff_id, shift_date, shift_time) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("siss", $type, $id, $date, $time);
    
    if ($stmt->execute()) {
        header('Location: ../admin/manage_hr.php');
    } else {
        echo "Error: " . $conn->error;
    }
}

if ($action === 'save_staff') {
    $id = $_POST['id'] ?? '';
    $role = $_POST['role'];
    $name = $_POST['name'];
    $email = $_POST['email'];
    $password = $_POST['password'];
    $dept_id = $_POST['department_id'] ?? null;

    $table = '';
    if ($role === 'Nurse') $table = 'nurses';
    elseif ($role === 'Pharmacist') $table = 'pharmacists';
    elseif ($role === 'Lab Technician') $table = 'lab_technicians';
    elseif ($role === 'Doctor') $table = 'telemedicine_doctors';

    if (!$table) {
        echo json_encode(['success' => false, 'message' => 'Invalid role']);
        exit;
    }

    if ($id) {
        // Update
        if ($role === 'Doctor' || $role === 'Nurse') {
            if ($password) {
                $hashed = password_hash($password, PASSWORD_DEFAULT);
                $sql = "UPDATE $table SET name = ?, email = ?, password = ?, department_id = ? WHERE id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("sssii", $name, $email, $hashed, $dept_id, $id);
            } else {
                $sql = "UPDATE $table SET name = ?, email = ?, department_id = ? WHERE id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("ssii", $name, $email, $dept_id, $id);
            }
        } else {
            if ($password) {
                $hashed = password_hash($password, PASSWORD_DEFAULT);
                $sql = "UPDATE $table SET name = ?, email = ?, password = ? WHERE id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("sssi", $name, $email, $hashed, $id);
            } else {
                $sql = "UPDATE $table SET name = ?, email = ? WHERE id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("ssi", $name, $email, $id);
            }
        }
    } else {
        // Create
        $hashed = password_hash($password, PASSWORD_DEFAULT);
        if ($role === 'Doctor' || $role === 'Nurse') {
            $sql = "INSERT INTO $table (name, email, password, department_id) VALUES (?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sssi", $name, $email, $hashed, $dept_id);
        } else {
            $sql = "INSERT INTO $table (name, email, password) VALUES (?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sss", $name, $email, $hashed);
        }
    }

    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => $conn->error]);
    }
}

if ($action === 'delete_staff') {
    $id = intval($_GET['id']);
    $role = $_GET['role'];

    $table = '';
    if ($role === 'Nurse') $table = 'nurses';
    elseif ($role === 'Pharmacist') $table = 'pharmacists';
    elseif ($role === 'Lab Technician') $table = 'lab_technicians';
    elseif ($role === 'Doctor') $table = 'telemedicine_doctors';

    if ($table) {
        $conn->begin_transaction();
        try {
            if ($role === 'Doctor') {
                // Nullify references in telemedicine_cases
                $conn->query("UPDATE telemedicine_cases SET created_by = NULL WHERE created_by = $id");
                
                // Nullify references in telemedicine_messages
                $conn->query("UPDATE telemedicine_messages SET doctor_id = NULL WHERE doctor_id = $id");
                
                // References in staff_roster
                $conn->query("UPDATE staff_roster SET staff_id = NULL WHERE staff_id = $id AND staff_type = 'Doctor'");
                
                // References in leave_requests
                $conn->query("UPDATE leave_requests SET staff_id = NULL WHERE staff_id = $id AND staff_type = 'Doctor'");
            } elseif ($role === 'Nurse') {
                $conn->query("UPDATE staff_roster SET staff_id = NULL WHERE staff_id = $id AND staff_type = 'Nurse'");
                $conn->query("UPDATE leave_requests SET staff_id = NULL WHERE staff_id = $id AND staff_type = 'Nurse'");
            } elseif ($role === 'Pharmacist') {
                $conn->query("UPDATE staff_roster SET staff_id = NULL WHERE staff_id = $id AND staff_type = 'Pharmacist'");
                $conn->query("UPDATE leave_requests SET staff_id = NULL WHERE staff_id = $id AND staff_type = 'Pharmacist'");
            } elseif ($role === 'Lab Technician') {
                $conn->query("UPDATE staff_roster SET staff_id = NULL WHERE staff_id = $id AND staff_type = 'Lab Technician'");
                $conn->query("UPDATE leave_requests SET staff_id = NULL WHERE staff_id = $id AND staff_type = 'Lab Technician'");
            }

            if ($conn->query("DELETE FROM $table WHERE id = $id")) {
                $conn->commit();
                echo json_encode(['success' => true]);
            } else {
                throw new Exception($conn->error);
            }
        } catch (Exception $e) {
            $conn->rollback();
            echo json_encode(['success' => false, 'message' => "Database Error: " . $e->getMessage()]);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid role or table']);
    }
}

if ($action === 'update_leave') {
    $id = intval($_GET['leave_id']);
    $status = $_GET['status'];
    
    $stmt = $conn->prepare("UPDATE leave_requests SET status = ? WHERE id = ?");
    $stmt->bind_param("si", $status, $id);
    
    if ($stmt->execute()) {
        header('Location: ../admin/manage_hr.php');
    } else {
        echo "Error: " . $conn->error;
    }
}

if ($action === 'request_leave') {
    $type = $_POST['staff_type'];
    $id = intval($_POST['staff_id']);
    $leave_type = $_POST['leave_type'];
    $start = $_POST['start_date'];
    $end = $_POST['end_date'];
    $reason = $_POST['reason'];

    $stmt = $conn->prepare("INSERT INTO leave_requests (staff_type, staff_id, leave_type, start_date, end_date, reason) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sissss", $type, $id, $leave_type, $start, $end, $reason);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => $conn->error]);
    }
}
?>