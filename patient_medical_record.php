<?php
session_start();
require_once 'includes/db_connect.php';

// Authorization: Patient, Doctor, or Nurse
if (!isset($_SESSION['patient_id']) && !isset($_SESSION['doctor_id']) && !isset($_SESSION['nurse_id'])) {
    die("Unauthorized access.");
}

$patient_id = isset($_GET['id']) ? intval($_GET['id']) : ($_SESSION['patient_id'] ?? 0);

if (!$patient_id) {
    die("Patient ID required.");
}

// Fetch Patient Info (Use Cache if available)
if (isset($_SESSION['cached_patient_profile']) && $_SESSION['cached_patient_id'] == $patient_id) {
    $patient = $_SESSION['cached_patient_profile'];
} else {
    $stmt = $conn->prepare("SELECT * FROM patients WHERE id = ?");
    $stmt->bind_param("i", $patient_id);
    $stmt->execute();
    $patient = $stmt->get_result()->fetch_assoc();
}

if (!$patient) {
    die("Patient not found.");
}

// Fetch Vitals History
$v_res = $conn->query("SELECT * FROM vital_signs WHERE patient_id = $patient_id ORDER BY recorded_at DESC");
$vitals = $v_res->fetch_all(MYSQLI_ASSOC);

// Fetch Consultation History
$c_res = $conn->query("SELECT v.*, d.name as doctor_name 
                       FROM patient_visits v 
                       LEFT JOIN telemedicine_doctors d ON v.doctor_id = d.id 
                       WHERE v.patient_id = $patient_id 
                       ORDER BY v.visit_date DESC");
$consultations = $c_res->fetch_all(MYSQLI_ASSOC);

// Fetch Lab Results
$l_res = $conn->query("SELECT r.*, t.test_name, t.category 
                       FROM lab_results r 
                       JOIN lab_requests req ON r.request_id = req.id 
                       JOIN lab_tests t ON req.test_id = t.id 
                       WHERE r.patient_id = $patient_id AND r.status = 'Released' 
                       ORDER BY r.released_at DESC");
$labs = $l_res->fetch_all(MYSQLI_ASSOC);

// Fetch Prescriptions
$p_res = $conn->query("SELECT p.*, d.name as doctor_name 
                       FROM telemedicine_prescriptions p 
                       LEFT JOIN telemedicine_doctors d ON p.doctor_id = d.id 
                       WHERE p.patient_id = $patient_id 
                       ORDER BY p.created_at DESC");
$prescriptions = $p_res->fetch_all(MYSQLI_ASSOC);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Medical Record - <?php echo $patient['full_name']; ?></title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; color: #333; line-height: 1.6; padding: 40px; }
        .header { display: flex; justify-content: space-between; border-bottom: 3px solid #2563eb; padding-bottom: 20px; margin-bottom: 30px; }
        .hospital-info h1 { margin: 0; color: #2563eb; font-size: 24px; font-weight: 900; }
        .hospital-info p { margin: 5px 0 0; font-size: 12px; color: #666; font-weight: bold; text-transform: uppercase; }
        .record-title { text-align: right; }
        .record-title h2 { margin: 0; font-size: 20px; font-weight: 900; text-transform: uppercase; }
        .record-title p { margin: 5px 0 0; font-size: 12px; color: #999; }
        
        .patient-strip { background: #f8fafc; border-radius: 15px; padding: 20px; display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; margin-bottom: 40px; border: 1px solid #e2e8f0; }
        .strip-item label { display: block; font-size: 10px; font-weight: 900; color: #94a3b8; text-transform: uppercase; margin-bottom: 5px; }
        .strip-item span { font-size: 14px; font-weight: bold; color: #1e293b; }

        .section { margin-bottom: 40px; }
        .section-header { border-bottom: 1px solid #e2e8f0; padding-bottom: 10px; margin-bottom: 20px; }
        .section-header h3 { margin: 0; font-size: 16px; font-weight: 900; text-transform: uppercase; color: #2563eb; }

        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        th { text-align: left; font-size: 11px; font-weight: 900; color: #64748b; text-transform: uppercase; padding: 12px 15px; background: #f8fafc; }
        td { padding: 12px 15px; border-bottom: 1px solid #f1f5f9; font-size: 13px; }
        
        .diagnosis-box { background: #eff6ff; border-left: 4px solid #2563eb; padding: 15px; border-radius: 0 10px 10px 0; margin-top: 10px; }
        .diagnosis-box p { margin: 0; font-weight: bold; color: #1e40af; }
        
        @media print {
            body { padding: 0; }
            .no-print { display: none; }
            .page-break { page-break-before: always; }
        }

        .print-btn { position: fixed; bottom: 30px; right: 30px; background: #2563eb; color: white; border: none; padding: 15px 30px; border-radius: 50px; font-weight: bold; cursor: pointer; box-shadow: 0 10px 20px rgba(37,99,235,0.3); }
    </style>
</head>
<body>

    <button class="print-btn no-print" onclick="window.print()">Print Record</button>

    <div class="header">
        <div class="hospital-info">
            <h1>HOPE HAVEN HOSPITAL</h1>
            <p>Comprehensive Electronic Medical Record</p>
        </div>
        <div class="record-title">
            <h2>Patient Medical History</h2>
            <p>Generated on <?php echo date('d M Y, H:i'); ?></p>
        </div>
    </div>

    <div class="patient-strip">
        <div class="strip-item">
            <label>Patient Name</label>
            <span><?php echo strtoupper($patient['full_name']); ?></span>
        </div>
        <div class="strip-item">
            <label>File Number</label>
            <span><?php echo $patient['file_number']; ?></span>
        </div>
        <div class="strip-item">
            <label>Gender / Age</label>
            <span><?php echo $patient['gender']; ?> / <?php echo $patient['age']; ?> YRS</span>
        </div>
        <div class="strip-item">
            <label>Blood Group</label>
            <span><?php echo $patient['blood_group'] ?: 'NOT SPECIFIED'; ?></span>
        </div>
    </div>

    <!-- CLINICAL VISITS -->
    <div class="section">
        <div class="section-header">
            <h3>Consultation History</h3>
        </div>
        <?php if (empty($consultations)): ?>
            <p style="font-style: italic; color: #999;">No consultation records found.</p>
        <?php else: ?>
            <?php foreach($consultations as $c): ?>
                <div style="margin-bottom: 25px; border-bottom: 1px dashed #e2e8f0; padding-bottom: 20px;">
                    <div style="display: flex; justify-content: space-between; margin-bottom: 10px;">
                        <span style="font-weight: 900; font-size: 12px;"><?php echo date('d M Y', strtotime($c['visit_date'])); ?></span>
                        <span style="color: #666; font-size: 12px;">Dr. <?php echo $c['doctor_name'] ?: 'General Practitioner'; ?></span>
                    </div>
                    <div class="diagnosis-box">
                        <label style="font-size: 9px; font-weight: 900; color: #2563eb; text-transform: uppercase;">Diagnosis</label>
                        <p><?php echo $c['diagnosis'] ?: 'Observation Phase'; ?></p>
                    </div>
                    <?php if($c['clinical_notes']): ?>
                        <div style="margin-top: 15px;">
                            <label style="font-size: 9px; font-weight: 900; color: #64748b; text-transform: uppercase;">Clinical Notes</label>
                            <p style="margin: 5px 0 0; font-size: 13px;"><?php echo nl2br(htmlspecialchars($c['clinical_notes'])); ?></p>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- LAB RESULTS -->
    <div class="section">
        <div class="section-header">
            <h3>Laboratory Investigations</h3>
        </div>
        <?php if (empty($labs)): ?>
            <p style="font-style: italic; color: #999;">No laboratory results released.</p>
        <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Test Name</th>
                        <th>Findings / Result</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($labs as $l): ?>
                        <tr>
                            <td><?php echo date('d M Y', strtotime($l['released_at'])); ?></td>
                            <td>
                                <div style="font-weight: bold;"><?php echo $l['test_name']; ?></div>
                                <div style="font-size: 10px; color: #666;"><?php echo $l['category']; ?></div>
                            </td>
                            <td>
                                <?php if($l['numeric_value']): ?>
                                    <span style="font-weight: bold; font-size: 15px;"><?php echo $l['numeric_value']; ?></span>
                                    <span style="font-size: 10px; color: #666;"><?php echo $l['unit']; ?></span>
                                <?php endif; ?>
                                <div style="margin-top: 5px;"><?php echo htmlspecialchars($l['findings']); ?></div>
                                <?php if($l['is_abnormal']): ?>
                                    <span style="display: inline-block; background: #fee2e2; color: #dc2626; font-size: 9px; padding: 2px 6px; border-radius: 4px; font-weight: 900; margin-top: 5px;">ABNORMAL</span>
                                <?php endif; ?>
                            </td>
                            <td style="font-weight: bold; color: #059669;">RELEASED</td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>

    <!-- PRESCRIPTIONS -->
    <div class="section">
        <div class="section-header">
            <h3>Prescription History</h3>
        </div>
        <?php if (empty($prescriptions)): ?>
            <p style="font-style: italic; color: #999;">No prescription history.</p>
        <?php else: ?>
            <?php foreach($prescriptions as $p): 
                $meds = json_decode($p['medications_json'], true);
            ?>
                <div style="margin-bottom: 20px; background: #fff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 15px;">
                    <div style="margin-bottom: 10px; font-size: 12px; color: #666; border-bottom: 1px solid #f1f5f9; padding-bottom: 8px;">
                        Ordered on <?php echo date('d M Y', strtotime($p['created_at'])); ?> by Dr. <?php echo $p['doctor_name']; ?>
                    </div>
                    <?php if(is_array($meds)): ?>
                        <?php foreach($meds as $m): ?>
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                                <div style="font-weight: bold; color: #1e293b;"><?php echo $m['drug']; ?></div>
                                <div style="font-size: 11px; font-weight: 900; color: #64748b;"><?php echo $m['dosage']; ?> &bull; <?php echo $m['duration']; ?></div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

</body>
</html>