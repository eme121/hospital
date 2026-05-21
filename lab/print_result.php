<?php
session_start();
require_once '../includes/db_connect.php';

if (!isset($_GET['id'])) {
    die("Result ID required.");
}

$result_id = intval($_GET['id']);

// Fetch full result data with patient and test details
$sql = "SELECT r.*, p.full_name, p.file_number, p.gender, p.age, 
               t.test_name, t.category, t.unit as test_unit, t.reference_min, t.reference_max,
               lt.name as technician_name,
               req.requested_at, req.priority,
               COALESCE(d.name, n.name) as requester_name
        FROM lab_results r
        JOIN patients p ON r.patient_id = p.id
        JOIN lab_requests req ON r.request_id = req.id
        JOIN lab_tests t ON req.test_id = t.id
        LEFT JOIN lab_technicians lt ON r.technician_id = lt.id
        LEFT JOIN doctors d ON req.doctor_id = d.id
        LEFT JOIN nurses n ON req.nurse_id = n.id
        WHERE r.id = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $result_id);
$stmt->execute();
$res = $stmt->get_result()->fetch_assoc();

if (!$res) {
    die("Result not found.");
}

$is_abnormal = $res['is_abnormal'] == 1;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Lab Report - <?php echo $res['file_number']; ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; color: #1e293b; line-height: 1.5; margin: 0; padding: 40px; background: #f8fafc; }
        .report-card { background: white; max-width: 900px; margin: 0 auto; padding: 60px; box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1); border-radius: 8px; }
        .header { display: flex; justify-content: space-between; align-items: flex-start; border-bottom: 2px solid #f1f5f9; padding-bottom: 30px; margin-bottom: 40px; }
        .hospital-info h1 { margin: 0; font-size: 24px; font-weight: 800; letter-spacing: -0.025em; color: #0f172a; }
        .hospital-info p { margin: 4px 0; font-size: 12px; color: #64748b; font-weight: 500; }
        .report-title { text-align: right; }
        .report-title h2 { margin: 0; font-size: 18px; font-weight: 700; color: #4f46e5; text-transform: uppercase; letter-spacing: 0.05em; }
        .report-title p { margin: 4px 0; font-size: 11px; font-weight: 700; color: #94a3b8; }
        
        .patient-grid { display: grid; grid-cols: 2; display: flex; gap: 40px; margin-bottom: 40px; background: #f8fafc; padding: 25px; border-radius: 12px; }
        .info-col { flex: 1; }
        .info-item { margin-bottom: 12px; }
        .info-label { font-size: 10px; font-weight: 800; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 2px; }
        .info-value { font-size: 13px; font-weight: 600; color: #1e293b; }

        .results-table { width: 100%; border-collapse: collapse; margin-bottom: 40px; }
        .results-table th { text-align: left; font-size: 11px; font-weight: 800; color: #64748b; text-transform: uppercase; letter-spacing: 0.05em; padding: 12px 15px; border-bottom: 2px solid #f1f5f9; }
        .results-table td { padding: 20px 15px; border-bottom: 1px solid #f1f5f9; font-size: 14px; }
        .test-name { font-weight: 700; color: #0f172a; }
        .test-category { font-size: 10px; color: #64748b; font-weight: 600; }
        .result-value { font-weight: 800; font-size: 16px; }
        .abnormal { color: #e11d48; }
        .normal { color: #059669; }
        
        .findings-box { background: #f8fafc; padding: 25px; border-radius: 12px; margin-bottom: 40px; border-left: 4px solid #4f46e5; }
        .findings-title { font-size: 11px; font-weight: 800; color: #64748b; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 10px; }
        .findings-content { font-size: 14px; color: #334155; font-style: italic; font-weight: 500; }

        .footer-sig { display: flex; justify-content: space-between; align-items: flex-end; margin-top: 60px; padding-top: 30px; border-top: 1px solid #f1f5f9; }
        .sig-box { text-align: center; width: 200px; }
        .sig-line { border-top: 1px solid #cbd5e1; margin-bottom: 8px; }
        .sig-name { font-size: 12px; font-weight: 700; color: #1e293b; }
        .sig-title { font-size: 10px; font-weight: 600; color: #94a3b8; text-transform: uppercase; }

        @media print {
            body { padding: 0; background: white; }
            .report-card { box-shadow: none; max-width: 100%; padding: 40px; }
            .no-print { display: none; }
        }
    </style>
</head>
<body>

    <div class="no-print" style="max-width: 900px; margin: 0 auto 20px auto; text-align: right;">
        <button onclick="window.print()" style="padding: 12px 24px; background: #4f46e5; color: white; border: none; border-radius: 8px; font-weight: 700; cursor: pointer; box-shadow: 0 4px 6px -1px rgba(79, 70, 229, 0.4);">Print Report</button>
    </div>

    <div class="report-card">
        <div class="header">
            <div class="hospital-info">
                <h1>HOPE HAVEN HOSPITAL</h1>
                <p>Clinical Laboratory Services Department</p>
                <p>128 Healthcare Ave, Victoria Island, Lagos</p>
                <p>Tel: +234 800 HOPE HAVEN | Email: lab@hopehaven.ng</p>
            </div>
            <div class="report-title">
                <h2>Laboratory Report</h2>
                <p>REPORT ID: #LR-<?php echo str_pad($res['id'], 6, '0', STR_PAD_LEFT); ?></p>
                <p>RELEASED: <?php echo date('d M Y, h:i A', strtotime($res['released_at'])); ?></p>
            </div>
        </div>

        <div class="patient-grid">
            <div class="info-col">
                <div class="info-item">
                    <div class="info-label">Patient Name</div>
                    <div class="info-value"><?php echo strtoupper($res['full_name']); ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">Patient ID / File #</div>
                    <div class="info-value"><?php echo $res['file_number']; ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">Gender / Age</div>
                    <div class="info-value"><?php echo $res['gender']; ?> / <?php echo $res['age']; ?> Yrs</div>
                </div>
            </div>
            <div class="info-col">
                <div class="info-item">
                    <div class="info-label">Requested By</div>
                    <div class="info-value">Dr. <?php echo $res['requester_name']; ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">Collection Date</div>
                    <div class="info-value"><?php echo date('d M Y, h:i A', strtotime($res['requested_at'])); ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">Priority</div>
                    <div class="info-value"><?php echo $res['priority']; ?></div>
                </div>
            </div>
        </div>

        <table class="results-table">
            <thead>
                <tr>
                    <th>Test Description</th>
                    <th>Result Value</th>
                    <th>Flag</th>
                    <th>Reference Range</th>
                    <th>Units</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>
                        <div class="test-name"><?php echo $res['test_name']; ?></div>
                        <div class="test-category"><?php echo $res['category']; ?></div>
                    </td>
                    <td class="result-value <?php echo $is_abnormal ? 'abnormal' : 'normal'; ?>">
                        <?php echo $res['numeric_value'] !== null ? number_format($res['numeric_value'], 2) : 'SEE FINDINGS'; ?>
                    </td>
                    <td style="font-weight: 800; font-size: 12px;">
                        <?php if ($is_abnormal): ?>
                            <span class="abnormal">ABNORMAL</span>
                        <?php else: ?>
                            <span class="normal">NORMAL</span>
                        <?php endif; ?>
                    </td>
                    <td class="info-value" style="color: #64748b;"><?php echo $res['reference_range'] ?: '---'; ?></td>
                    <td class="info-value" style="color: #64748b;"><?php echo $res['unit'] ?: '---'; ?></td>
                </tr>
            </tbody>
        </table>

        <div class="findings-box">
            <div class="findings-title">Technician's Clinical Findings</div>
            <div class="findings-content"><?php echo nl2br(htmlspecialchars($res['findings'])); ?></div>
            
            <?php if (!empty($res['lab_notes'])): ?>
                <div class="findings-title" style="margin-top: 20px; opacity: 0.7;">Confidential Lab Notes</div>
                <div class="findings-content" style="font-size: 12px; color: #64748b;"><?php echo nl2br(htmlspecialchars($res['lab_notes'])); ?></div>
            <?php endif; ?>
        </div>

        <div class="footer-sig">
            <div class="sig-box">
                <div class="sig-line"></div>
                <div class="sig-name"><?php echo $res['technician_name'] ?: 'System Verified'; ?></div>
                <div class="sig-title">Laboratory Technician</div>
            </div>
            <div class="sig-box">
                <div class="sig-line"></div>
                <div class="sig-name">Dr. A. B. Cole</div>
                <div class="sig-title">Consultant Pathologist</div>
            </div>
        </div>

        <div style="margin-top: 50px; text-align: center; font-size: 10px; color: #94a3b8; font-weight: 600;">
            *** END OF REPORT — This is a digitally verified medical document ***
        </div>
    </div>

</body>
</html>