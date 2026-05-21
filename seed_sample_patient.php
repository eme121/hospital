<?php
require_once 'includes/db_connect.php';

echo "<style>body{font-family:sans-serif; line-height:1.6; padding:40px; background:#f8fafc; color:#1e293b;} .success{color: #059669; font-weight:bold;} hr{border:0; border-top:1px solid #e2e8f0; margin:20px 0;}</style>";
echo "<h2>🚀 Correcting & Seeding Clinical Data...</h2>";

try {
    // 1. Create Patient
    $full_name = "John Test Patient";
    $file_number = "HH-" . rand(1000, 9999);
    $age = 35;
    $gender = "Male";
    $phone = "08012345678";
    $email = "john.test@example.com";
    $password = password_hash('password123', PASSWORD_DEFAULT);

    $stmt = $conn->prepare("INSERT INTO patients (full_name, file_number, age, gender, phone, email, password) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssissss", $full_name, $file_number, $age, $gender, $phone, $email, $password);
    $stmt->execute();
    $patient_id = $conn->insert_id;
    echo "✅ Patient Created: <span class='success'>$full_name</span> (File: $file_number)<br>";

    // 2. Add Historical Vitals (10 records for the Pressure Curve)
    $base_sys = 120;
    $base_dia = 80;
    for ($i = 9; $i >= 0; $i--) {
        $sys = $base_sys + rand(-10, 15);
        $dia = $base_dia + rand(-5, 10);
        $temp = 36.5 + (rand(0, 10) / 10);
        $pulse = 70 + rand(-5, 10);
        $weight = 75 + rand(-2, 2);
        $recorded_at = date('Y-m-d H:i:s', strtotime("-$i days"));
        
        $v_stmt = $conn->prepare("INSERT INTO vital_signs (patient_id, blood_pressure_sys, blood_pressure_dia, temperature, heart_rate, weight, recorded_at) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $v_stmt->bind_param("iiiisds", $patient_id, $sys, $dia, $temp, $pulse, $weight, $recorded_at);
        $v_stmt->execute();
    }
    echo "✅ Seeded 10 Historical Vitals (Pressure Curve is now active)<br>";

    // 3. Create Current Visit
    $visit_date = date('Y-m-d H:i:s');
    $complaints = "Persistent dry cough and chest tightness for 2 weeks.";
    $history = "Family history of hypertension.";
    $vi_stmt = $conn->prepare("INSERT INTO patient_visits (patient_id, nurse_id, visit_date, status, presenting_complaints, medical_history) VALUES (?, 1, ?, 'Active', ?, ?)");
    $vi_stmt->bind_param("isss", $patient_id, $visit_date, $complaints, $history);
    $vi_stmt->execute();
    $visit_id = $conn->insert_id;
    echo "✅ Current Visit Initialized (ID: $visit_id)<br>";

    // 4. Create Paid Invoice
    $inv_stmt = $conn->prepare("INSERT INTO invoices (patient_id, total_amount, status, created_at) VALUES (?, 5000.00, 'Paid', ?)");
    $inv_stmt->bind_param("is", $patient_id, $visit_date);
    $inv_stmt->execute();
    $invoice_id = $inv_stmt->id ?? $conn->insert_id;
    $conn->query("INSERT INTO invoice_items (invoice_id, item_description, item_type, unit_price, quantity, total_price) VALUES ($invoice_id, 'Consultation Fee', 'Consultation', 5000.00, 1, 5000.00)");
    echo "✅ Payment Status: <span class='success'>PAID</span> (Bypassed accountant)<br>";

    // 5. Create Lab Request & Result (FIXED SCHEMA)
    // First, find a valid test_id from lab_tests
    $test_res = $conn->query("SELECT id FROM lab_tests LIMIT 1");
    $test_id = ($test_res->num_rows > 0) ? $test_res->fetch_assoc()['id'] : 1;

    $lab_stmt = $conn->prepare("INSERT INTO lab_requests (patient_id, doctor_id, test_id, priority, status, requested_at) VALUES (?, 1, ?, 'Urgent', 'Completed', ?)");
    $lab_stmt->bind_param("iis", $patient_id, $test_id, $visit_date);
    $lab_stmt->execute();
    $request_id = $lab_stmt->insert_id;

    $findings = "Bilateral lung markings slightly increased. No consolidation.";
    $tech_id = 1; // Default technician
    // Corrected query matching your SHOW CREATE TABLE: request_id, patient_id, technician_id, findings, status, released_at
    $res_stmt = $conn->prepare("INSERT INTO lab_results (request_id, patient_id, technician_id, findings, status, released_at, is_abnormal) VALUES (?, ?, ?, ?, 'Released', ?, 0)");
    $res_stmt->bind_param("iiiss", $request_id, $patient_id, $tech_id, $findings, $visit_date);
    
    if (!$res_stmt) {
        throw new Exception("Lab Results Prepare Failed: " . $conn->error);
    }
    
    $res_stmt->execute();
    echo "✅ Lab Evidence Seeded: <span class='success'>Released</span><br>";

    // 6. Set Queue
    $conn->query("INSERT INTO patient_queue_status (patient_id, current_stage, status, notes) VALUES ($patient_id, 'Doctor', 'Lab Results Ready', 'Ready for Review') ON DUPLICATE KEY UPDATE current_stage='Doctor', status='Lab Results Ready'");
    echo "✅ Queue Status: <span class='success'>In Doctor's Office</span><br>";

    // 7. Ensure a Test Doctor exists
    $doc_email = "clinical.test@example.com";
    $doc_pass = password_hash('doctor123', PASSWORD_DEFAULT);
    $doc_check = $conn->query("SELECT id FROM doctors WHERE email = '$doc_email'");
    if ($doc_check->num_rows == 0) {
        $conn->query("INSERT INTO doctors (name, email, password, department_id, allow_virtual) VALUES ('Dr. Test Clinical', '$doc_email', '$doc_pass', 1, 1)");
        $doctor_id = $conn->insert_id;
    } else {
        $doctor_id = $doc_check->fetch_assoc()['id'];
    }
    echo "✅ Test Doctor Ready: <span class='success'>$doc_email</span><br>";

    echo "<hr>";
    echo "<h3>🎉 Seeding Complete!</h3>";
    echo "<b>Doctor Login (For Dashboard):</b> $doc_email / doctor123<br>";
    echo "<b>Patient File Number:</b> $file_number<br>";
    echo "<b>Direct Access:</b> <a href='consultation.php?patient_id=$patient_id' style='display:inline-block; padding:10px 20px; background:#2563eb; color:white; border-radius:10px; text-decoration:none; margin-top:10px;'>Open Clinical Console</a>";

} catch (Exception $e) {
    echo "<div style='color:red; padding:20px; border:1px solid red; background:#fff1f2; border-radius:10px;'><b>Seeding Failed:</b> " . $e->getMessage() . "</div>";
}
?>