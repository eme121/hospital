<?php
session_start();
if (!isset($_SESSION['pharmacist_id']) && !isset($_SESSION['admin_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized.']);
    exit;
}
require_once '../includes/db_connect.php';
require_once '../includes/pharmacy_clinical_helper.php';

$action = $_GET['action'] ?? '';
$user_id = $_SESSION['pharmacist_id'] ?? $_SESSION['admin_id'];

if ($action === 'get_clinical_configs') {
    echo json_encode(['success' => true, 'configs' => PharmacyClinicalHelper::getFormConfigs()]);
    exit;
}

// Utility to check and update pharmacy alerts
function checkPharmacyAlerts($conn) {
    // 1. Check Low Stock
    $low_p = $conn->query("SELECT drug_name, quantity, reorder_level FROM pharmacy_stock WHERE quantity <= reorder_level");
    while($row = $low_p->fetch_assoc()) {
        // Send notification
        require_once '../includes/notifications_helper.php';
        NotificationService::setConnection($conn);
        NotificationService::send('pharmacy', 0, 'low_stock', 'Low Stock Alert', "{$row['drug_name']} in Pharmacy is below reorder level ({$row['quantity']} units left).", 'pharmacy/inventory.php?tab=pharmacy-stock');
    }
}

if ($action === 'get_suppliers') {
    $res = $conn->query("SELECT * FROM suppliers ORDER BY name ASC");
    echo json_encode(['success' => true, 'suppliers' => $res->fetch_all(MYSQLI_ASSOC)]);
    exit;
}

if ($action === 'add_supplier') {
    $name = $_POST['name'];
    $contact = $_POST['contact'];
    $phone = $_POST['phone'];
    $stmt = $conn->prepare("INSERT INTO suppliers (name, contact_person, phone_number) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $name, $contact, $phone);
    if ($stmt->execute()) echo json_encode(['success' => true, 'id' => $conn->insert_id]);
    else echo json_encode(['success' => false, 'message' => $conn->error]);
    exit;
}

if ($action === 'get_main_store') {
    $res = $conn->query("SELECT m.*, s.name as supplier_name 
                         FROM main_store_inventory m 
                         LEFT JOIN suppliers s ON m.supplier_id = s.id 
                         ORDER BY m.drug_name ASC");
    echo json_encode(['success' => true, 'inventory' => $res->fetch_all(MYSQLI_ASSOC)]);
    exit;
}

if ($action === 'add_to_main_store') {
    $name = $_POST['drug_name'];
    $strength = $_POST['strength'] ?? '';
    $form_type = $_POST['form_type'] ?? 'Tablet';
    $standard_dose = floatval($_POST['standard_dose'] ?? 1.00);
    $max_dose = floatval($_POST['max_dose_per_day'] ?? 4.00);
    $supplier_id = $_POST['supplier_id'];
    $batch = $_POST['batch_number'];
    $expiry = $_POST['expiry_date'];
    $qty = (int)$_POST['quantity'];
    $total_cost = (float)$_POST['total_cost_price'];
    $price_per_pack = (float)($_POST['price_per_pack'] ?? 0);
    $category = $_POST['category'];
    $pkg = $_POST['packaging_type'];
    $unit = $_POST['unit'];
    $units_per_pack = (int)($_POST['units_per_pack'] ?? 1);
    $reorder_level = isset($_POST['reorder_level']) ? (int)$_POST['reorder_level'] : 20;
    
    // Auto-calculate unit cost (per pack)
    $unit_cost = ($qty > 0) ? ($total_cost / $qty) : 0;
    $total_base_units = $qty * $units_per_pack;

    $conn->begin_transaction();
    try {
        $stmt = $conn->prepare("INSERT INTO main_store_inventory (drug_name, form_type, standard_dose, max_dose_per_day, strength, supplier_id, batch_number, expiry_date, quantity, unit_cost_price, total_cost_price, price_per_pack, category, packaging_type, unit, units_per_pack, total_base_units, reorder_level) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssddsissidddsssiii", $name, $form_type, $standard_dose, $max_dose, $strength, $supplier_id, $batch, $expiry, $qty, $unit_cost, $total_cost, $price_per_pack, $category, $pkg, $unit, $units_per_pack, $total_base_units, $reorder_level);
        $stmt->execute();
        $item_id = $conn->insert_id;

        // Log purchase
        $stmt_p = $conn->prepare("INSERT INTO drug_purchases (main_store_item_id, supplier_id, quantity_received, total_cost, batch_number) VALUES (?, ?, ?, ?, ?)");
        $stmt_p->bind_param("iiids", $item_id, $supplier_id, $qty, $total_cost, $batch);
        $stmt_p->execute();

        $conn->commit();
        echo json_encode(['success' => true, 'message' => 'Stock added to Main Store.']);
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

if ($action === 'get_pharmacy_stock') {
    $res = $conn->query("SELECT * FROM pharmacy_stock ORDER BY drug_name ASC");
    echo json_encode(['success' => true, 'stock' => $res->fetch_all(MYSQLI_ASSOC)]);
    exit;
}

if ($action === 'move_to_pharmacy') {
    $m_id = (int)$_POST['main_store_item_id'];
    $qty_packs = (int)$_POST['quantity'];
    $selling_price = (float)$_POST['selling_price'];

    $conn->begin_transaction();
    try {
        $res = $conn->query("SELECT * FROM main_store_inventory WHERE id = $m_id");
        $item = $res->fetch_assoc();

        if ($item['quantity'] < $qty_packs) throw new Exception("Insufficient stock in Main Store.");

        $units_per_pack = $item['units_per_pack'] ?: 1;
        $base_units_to_add = $qty_packs * $units_per_pack;

        // Update Main Store
        $conn->query("UPDATE main_store_inventory SET quantity = quantity - $qty_packs, total_base_units = total_base_units - $base_units_to_add WHERE id = $m_id");

        // Update/Insert Pharmacy
        $p_res = $conn->query("SELECT id FROM pharmacy_stock WHERE drug_name = '" . $conn->real_escape_string($item['drug_name']) . "' AND strength = '" . $conn->real_escape_string($item['strength']) . "'");
        $p_item = $p_res->fetch_assoc();

        if ($p_item) {
            $stmt_upd = $conn->prepare("UPDATE pharmacy_stock SET 
                quantity = quantity + ?, 
                selling_price = ?, 
                price_per_pack = ?,
                expiry_date = ?,
                form_type = ?,
                standard_dose = ?,
                max_dose_per_day = ?,
                units_per_pack = ?
                WHERE id = ?");
            $stmt_upd->bind_param("id dssddii", 
                $base_units_to_add, 
                $selling_price, 
                $item['price_per_pack'],
                $item['expiry_date'],
                $item['form_type'],
                $item['standard_dose'],
                $item['max_dose_per_day'],
                $units_per_pack,
                $p_item['id']
            );
            $stmt_upd->execute();
        } else {
            $stmt_ins = $conn->prepare("INSERT INTO pharmacy_stock (drug_name, strength, category, base_unit, quantity, selling_price, price_per_pack, expiry_date, form_type, standard_dose, max_dose_per_day, units_per_pack) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt_ins->bind_param("ssssid dssddi", 
                $item['drug_name'], 
                $item['strength'], 
                $item['category'], 
                $item['unit'], 
                $base_units_to_add, 
                $selling_price, 
                $item['price_per_pack'],
                $item['expiry_date'],
                $item['form_type'],
                $item['standard_dose'],
                $item['max_dose_per_day'],
                $units_per_pack
            );
            $stmt_ins->execute();
        }

        // Log Movement
        $stmt_m = $conn->prepare("INSERT INTO stock_movements (drug_name, quantity, from_location, to_location, performed_by) VALUES (?, ?, 'Main Store', 'Pharmacy', ?)");
        $stmt_m->bind_param("sii", $item['drug_name'], $base_units_to_add, $user_id);
        $stmt_m->execute();

        $conn->commit();
        echo json_encode(['success' => true, 'message' => 'Stock transferred to Pharmacy.']);
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

if ($action === 'prepare_bill') {
    $patient_id = intval($_POST['patient_id']);
    $prescription_id = isset($_POST['prescription_id']) && !empty($_POST['prescription_id']) ? intval($_POST['prescription_id']) : null;
    $items = json_decode($_POST['items'], true);
    $total_amount = floatval($_POST['total_amount']);
    $notes = $conn->real_escape_string($_POST['notes'] ?? '');

    if (empty($items)) {
        echo json_encode(['success' => false, 'message' => 'No items provided.']);
        exit;
    }

    $conn->begin_transaction();
    try {
        // 1. Create a "Pending" dispensation record
        $stmt = $conn->prepare("INSERT INTO pharmacy_dispensations (patient_id, pharmacist_id, prescription_id, total_amount, notes, status) VALUES (?, ?, ?, ?, ?, 'Awaiting Payment')");
        $stmt->bind_param("iiids", $patient_id, $user_id, $prescription_id, $total_amount, $notes);
        $stmt->execute();
        $dispensation_id = $conn->insert_id;

        $invoice_items = [];

        // 2. Process each item (but don't deduct stock yet)
        foreach ($items as $item) {
            $drug_id = intval($item['drug_id']);
            $qty = round(floatval($item['quantity']), 2); // Unit-driven final qty
            $price = round(floatval($item['unit_price']), 2); // Price per unit
            $mode = $conn->real_escape_string($item['mode'] ?? 'Prescription');
            $dispense_unit = $conn->real_escape_string($item['unit_type'] ?? 'Tablet');
            
            $dose = floatval($item['dose'] ?? 1);
            $freq = intval($item['frequency'] ?? 1);
            $dur = intval($item['duration'] ?? 1);
            $dose_number = $conn->real_escape_string($item['dose_number'] ?? '');
            $schedule_date = !empty($item['schedule_date']) ? $conn->real_escape_string($item['schedule_date']) : null;
            $is_override = intval($item['is_override'] ?? 0);

            // Backend Validation
            $drug_res = $conn->query("SELECT drug_name, quantity, max_dose_per_day, form_type, admin_unit, dispense_unit, pack_size, strength FROM pharmacy_stock WHERE id = $drug_id");
            $drug_data = $drug_res->fetch_assoc();
            if (!$drug_data) throw new Exception("Drug ID $drug_id not found.");

            // 1. Centralized Clinical Calculation (for validation)
            $calc = PharmacyClinicalHelper::calculate($drug_data['form_type'], $dose, $freq, $dur, 1);
            $daily_dose = $calc['daily_dose'];

            $description = "{$drug_data['drug_name']} ($qty $dispense_unit)";

            // Stock Check
            if ($qty > $drug_data['quantity']) {
                throw new Exception("Insufficient stock for " . $drug_data['drug_name'] . ". Required: $qty units, Available: " . $drug_data['quantity']);
            }

            // Dosage Check
            if ($mode === 'Prescription' && $is_override === 0) {
                if ($daily_dose > $drug_data['max_dose_per_day'] && $drug_data['max_dose_per_day'] > 0) {
                    throw new Exception("Dosage for " . $drug_data['drug_name'] . " exceeds safety limits ($daily_dose > " . $drug_data['max_dose_per_day'] . "). Override required.");
                }
            }

            $subtotal = round($qty * $price, 2);

            $stmt_item = $conn->prepare("INSERT INTO pharmacy_dispensation_items (dispensation_id, drug_id, dispense_unit, dispensing_mode, dose, frequency, duration, dose_number, schedule_date, is_override, quantity, unit_price, subtotal) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt_item->bind_param("iisdiissidddd", $dispensation_id, $drug_id, $dispense_unit, $mode, $dose, $freq, $dur, $dose_number, $schedule_date, $is_override, $qty, $price, $subtotal);
            $stmt_item->execute();

            $invoice_items[] = [
                'description' => $description,
                'type' => 'Medication',
                'quantity' => 1,
                'price' => $subtotal
            ];
        }

        // 3. Generate Invoice
        require_once 'billing_engine.php';
        $billing = new BillingEngine($conn);
        $invoice_id = $billing->automateInvoice($patient_id, $invoice_items, null, null, 'Pharmacy');

        // 4. Link Invoice to Dispensation
        $conn->query("UPDATE pharmacy_dispensations SET invoice_id = $invoice_id WHERE id = $dispensation_id");

        $conn->commit();
        echo json_encode(['success' => true, 'message' => 'Billing invoice generated.', 'invoice_id' => $invoice_id]);
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

if ($action === 'finalize_dispense') {
    $dispensation_id = intval($_POST['dispensation_id']);
    
    $res = $conn->query("SELECT d.*, i.status as payment_status 
                         FROM pharmacy_dispensations d 
                         LEFT JOIN invoices i ON d.invoice_id = i.id 
                         WHERE d.id = $dispensation_id");
    $disp = $res->fetch_assoc();

    if (!$disp) {
        echo json_encode(['success' => false, 'message' => 'Order not found.']);
        exit;
    }

    if ($disp['payment_status'] !== 'Paid' && $disp['status'] !== 'Dispensed') {
        echo json_encode(['success' => false, 'message' => 'Payment has not been confirmed.']);
        exit;
    }

    $conn->begin_transaction();
    try {
        $items_res = $conn->query("SELECT * FROM pharmacy_dispensation_items WHERE dispensation_id = $dispensation_id");
        while ($item = $items_res->fetch_assoc()) {
            $drug_id = $item['drug_id'];
            $qty = $item['quantity'];

            $stock = $conn->query("SELECT quantity, drug_name FROM pharmacy_stock WHERE id = $drug_id")->fetch_assoc();
            if ($stock['quantity'] < $qty) {
                throw new Exception("CRITICAL: Stock became insufficient for " . $stock['drug_name']);
            }

            $conn->query("UPDATE pharmacy_stock SET quantity = quantity - $qty WHERE id = $drug_id");
            
            $stmt_log = $conn->prepare("INSERT INTO dispensations (medication_id, quantity_dispensed, status, performed_by) VALUES (?, ?, 'Fulfilled', ?)");
            $stmt_log->bind_param("iii", $drug_id, $qty, $user_id);
            $stmt_log->execute();
        }

        $conn->query("UPDATE pharmacy_dispensations SET status = 'Dispensed', dispensed_at = NOW() WHERE id = $dispensation_id");
        
        $conn->query("INSERT INTO patient_queue_status (patient_id, current_stage, status, notes) 
                      VALUES ({$disp['patient_id']}, 'Discharged', 'Medication Handed Over', 'Patient finished pharmacy checkout')
                      ON DUPLICATE KEY UPDATE current_stage='Discharged', status='Medication Handed Over'");

        $conn->commit();
        checkPharmacyAlerts($conn);
        echo json_encode(['success' => true, 'message' => 'Medication successfully handed over.']);
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

if ($action === 'check_interactions') {
    $patient_id = intval($_GET['patient_id']);
    $new_drugs = json_decode($_GET['drugs'], true) ?: [];

    $current_meds_res = $conn->query("SELECT DISTINCT medications_json FROM telemedicine_prescriptions WHERE patient_id = $patient_id AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)");
    $existing_drug_names = [];
    while($row = $current_meds_res->fetch_assoc()) {
        $json = json_decode($row['medications_json'], true);
        if (is_array($json)) foreach($json as $m) if (isset($m['drug'])) $existing_drug_names[] = $m['drug'];
    }
    $existing_drug_names = array_unique($existing_drug_names);

    $found_interactions = [];
    $all_to_check = array_unique(array_merge($new_drugs, $existing_drug_names));

    if (count($all_to_check) >= 2) {
        for ($i = 0; $i < count($all_to_check); $i++) {
            for ($j = $i + 1; $j < count($all_to_check); $j++) {
                $drugA = $conn->real_escape_string($all_to_check[$i]);
                $drugB = $conn->real_escape_string($all_to_check[$j]);
                
                $int_res = $conn->query("SELECT * FROM drug_interactions 
                                        WHERE (drug_a_name LIKE '%$drugA%' AND drug_b_name LIKE '%$drugB%')
                                        OR (drug_a_name LIKE '%$drugB%' AND drug_b_name LIKE '%$drugA%')");
                
                while($interaction = $int_res->fetch_assoc()) {
                    $found_interactions[] = [
                        'drug_a' => $all_to_check[$i],
                        'drug_b' => $all_to_check[$j],
                        'description' => $interaction['description'],
                        'severity' => $interaction['severity'] ?? 'High'
                    ];
                }
            }
        }
    }

    echo json_encode(['success' => true, 'interactions' => $found_interactions]);
    exit;
}

if ($action === 'get_stats') {
    $revenue_res = $conn->query("SELECT SUM(d.quantity * d.unit_price) as total FROM pharmacy_dispensation_items d JOIN pharmacy_dispensations pd ON d.dispensation_id = pd.id WHERE DATE(pd.dispensed_at) = CURDATE() AND pd.status = 'Dispensed'");
    $revenue = $revenue_res->fetch_assoc()['total'] ?? 0;
    
    $low_items = [];
    $res_p = $conn->query("SELECT drug_name, quantity, reorder_level FROM pharmacy_stock WHERE quantity <= reorder_level");
    while($row = $res_p->fetch_assoc()) { $row['location'] = 'Pharmacy'; $low_items[] = $row; }
    
    $res_m = $conn->query("SELECT drug_name, quantity, reorder_level FROM main_store_inventory WHERE quantity <= reorder_level");
    while($row = $res_m->fetch_assoc()) { $row['location'] = 'Main Store'; $low_items[] = $row; }

    $stats = [
        'main_store_count' => $conn->query("SELECT IFNULL(SUM(quantity), 0) FROM main_store_inventory")->fetch_row()[0],
        'pharmacy_count' => $conn->query("SELECT IFNULL(SUM(quantity), 0) FROM pharmacy_stock")->fetch_row()[0],
        'low_stock_count' => count($low_items),
        'low_stock_items' => $low_items,
        'today_revenue' => (float)$revenue,
        'safety_overrides_today' => $conn->query("SELECT COUNT(*) FROM pharmacy_dispensation_items di JOIN pharmacy_dispensations d ON di.dispensation_id = d.id WHERE di.is_override = 1 AND DATE(d.dispensed_at) = CURDATE()")->fetch_row()[0] ?? 0
    ];
    echo json_encode(['success' => true, 'stats' => $stats]);
    exit;
}

if ($action === 'get_dispensation_details') {
    $id = (int)$_GET['id'];
    $disp = $conn->query("SELECT d.*, p.full_name as patient_name, u.name as pharmacist_name FROM pharmacy_dispensations d JOIN patients p ON d.patient_id = p.id LEFT JOIN pharmacists u ON d.pharmacist_id = u.id WHERE d.id = $id")->fetch_assoc();
    if (!$disp) { echo json_encode(['success' => false, 'message' => 'Dispensation not found.']); exit; }
    $items = $conn->query("SELECT di.*, s.drug_name, s.form_type, s.strength FROM pharmacy_dispensation_items di JOIN pharmacy_stock s ON di.drug_id = s.id WHERE di.dispensation_id = $id")->fetch_all(MYSQLI_ASSOC);
    echo json_encode(['success' => true, 'disp' => $disp, 'items' => $items]);
    exit;
}

if ($action === 'reverse_dispensation') {
    $id = (int)$_POST['dispensation_id'];
    $reason = $_POST['reason'] ?? '';
    $conn->begin_transaction();
    try {
        $disp = $conn->query("SELECT * FROM pharmacy_dispensations WHERE id = $id")->fetch_assoc();
        if (!$disp) throw new Exception("Order not found.");
        if ($disp['status'] !== 'Dispensed') throw new Exception("Only dispensed orders can be reversed.");

        $items_res = $conn->query("SELECT * FROM pharmacy_dispensation_items WHERE dispensation_id = $id");
        while ($item = $items_res->fetch_assoc()) {
            $conn->query("UPDATE pharmacy_stock SET quantity = quantity + " . $item['quantity'] . " WHERE id = " . $item['drug_id']);
        }
        $conn->query("UPDATE pharmacy_dispensations SET status = 'Reversed', notes = CONCAT(IFNULL(notes, ''), '\nREVERSED: ', '" . $conn->real_escape_string($reason) . "') WHERE id = $id");
        if ($disp['invoice_id']) $conn->query("UPDATE invoices SET status = 'Cancelled' WHERE id = " . $disp['invoice_id']);
        $conn->commit();
        echo json_encode(['success' => true, 'message' => 'Dispensation reversed.']);
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

if ($action === 'adjust_stock') {
    $id = (int)$_POST['item_id'];
    $type = $_POST['type']; 
    $new_qty = (int)$_POST['quantity'];
    $new_price = (float)$_POST['price'];
    $new_expiry = $_POST['expiry_date'] ?? null;
    $form_type = $_POST['form_type'] ?? 'Tablet';
    $std_dose = floatval($_POST['standard_dose'] ?? 1.0);
    $max_dose = floatval($_POST['max_dose_per_day'] ?? 4.0);
    $reason = $_POST['reason'] ?? '';

    if (!$reason) { echo json_encode(['success' => false, 'message' => 'Reason required.']); exit; }

    $conn->begin_transaction();
    try {
        if ($type === 'main') {
            $old = $conn->query("SELECT drug_name, quantity, unit_cost_price FROM main_store_inventory WHERE id = $id")->fetch_assoc();
            $stmt = $conn->prepare("UPDATE main_store_inventory SET quantity = ?, unit_cost_price = ?, expiry_date = ?, form_type = ?, standard_dose = ?, max_dose_per_day = ?, total_cost_price = (quantity * unit_cost_price) WHERE id = ?");
            $stmt->bind_param("idssdii", $new_qty, $new_price, $new_expiry, $form_type, $std_dose, $max_dose, $id);
            $stmt->execute();
        } else {
            $old = $conn->query("SELECT drug_name, quantity, selling_price FROM pharmacy_stock WHERE id = $id")->fetch_assoc();
            $stmt = $conn->prepare("UPDATE pharmacy_stock SET quantity = ?, selling_price = ?, expiry_date = ?, form_type = ?, standard_dose = ?, max_dose_per_day = ? WHERE id = ?");
            $stmt->bind_param("idssdii", $new_qty, $new_price, $new_expiry, $form_type, $std_dose, $max_dose, $id);
            $stmt->execute();
        }
        $conn->commit();
        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

if ($action === 'add_pharmacy_stock') {
    $name = $_POST['drug_name'];
    $strength = $_POST['strength'] ?? '';
    $form_type = $_POST['form_type'] ?? 'Tablet';
    $std_dose = floatval($_POST['standard_dose'] ?? 1.0);
    $max_dose = floatval($_POST['max_dose_per_day'] ?? 4.0);
    $qty = (int)$_POST['quantity'];
    $price = (float)$_POST['selling_price'];
    $expiry = $_POST['expiry_date'];
    $category = $_POST['category'];
    $unit = $_POST['unit'];
    $level = (int)$_POST['reorder_level'];

    $stmt = $conn->prepare("INSERT INTO pharmacy_stock (drug_name, strength, form_type, standard_dose, max_dose_per_day, category, base_unit, quantity, selling_price, expiry_date, reorder_level) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE quantity = quantity + VALUES(quantity), selling_price = VALUES(selling_price), expiry_date = VALUES(expiry_date), reorder_level = VALUES(reorder_level), form_type = VALUES(form_type), standard_dose = VALUES(standard_dose), max_dose_per_day = VALUES(max_dose_per_day)");
    $stmt->bind_param("sssd dssidsi", $name, $strength, $form_type, $std_dose, $max_dose, $category, $unit, $qty, $price, $expiry, $level);

    if ($stmt->execute()) { checkPharmacyAlerts($conn); echo json_encode(['success' => true]); }
    else echo json_encode(['success' => false, 'message' => $conn->error]);
    exit;
}

if ($action === 'get_movements') {
    $res = $conn->query("SELECT * FROM stock_movements ORDER BY movement_date DESC LIMIT 50");
    echo json_encode(['success' => true, 'movements' => $res->fetch_all(MYSQLI_ASSOC)]);
    exit;
}

if ($action === 'search_patients') {
    $query = $conn->real_escape_string($_GET['query'] ?? '');
    $res = $conn->query("SELECT id, full_name, file_number FROM patients 
                         WHERE full_name LIKE '%$query%' OR file_number LIKE '%$query%' 
                         LIMIT 10");
    echo json_encode(['success' => true, 'patients' => $res->fetch_all(MYSQLI_ASSOC)]);
    exit;
}

if ($action === 'update_reorder_level') {
    $id = (int)$_POST['id'];
    $type = $_POST['type'];
    $level = (int)$_POST['level'];
    $table = ($type === 'main') ? 'main_store_inventory' : 'pharmacy_stock';
    if($conn->query("UPDATE $table SET reorder_level = $level WHERE id = $id")) echo json_encode(['success' => true]);
    else echo json_encode(['success' => false]);
    exit;
}
?>