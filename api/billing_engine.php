<?php
// Core Billing & Invoicing Engine
require_once __DIR__ . '/../includes/db_connect.php';
require_once __DIR__ . '/../includes/sync_helper.php';

class BillingEngine {
    private $conn;

    public function __construct($db_connection) {
        $this->conn = $db_connection;
    }

    /**
     * Create or update an invoice for a patient.
     * If an unpaid invoice exists from today, it will add items to it.
     * Otherwise, it creates a new one.
     */
    public function automateInvoice($patient_id, $items, $appointment_id = null, $appointment_type = null, $type = 'General') {
        // 1. Check for a pending invoice from today
        $today = date('Y-m-d');
        $invoice_id = null;

        try {
            $stmt = $this->conn->prepare("SELECT id FROM invoices WHERE patient_id = ? AND status = 'Pending' AND DATE(created_at) = ? AND type = ? LIMIT 1");
            if (!$stmt) throw new Exception("Prepare failed for invoice check: " . $this->conn->error);
            
            $stmt->bind_param("iss", $patient_id, $today, $type);
            $stmt->execute();
            $invoice_res = $stmt->get_result();
            
            if ($invoice_res && $invoice_res->num_rows > 0) {
                $invoice_id = $invoice_res->fetch_assoc()['id'];
                // Update appointment link if provided
                if ($appointment_id) {
                    $upd = $this->conn->prepare("UPDATE invoices SET appointment_id = ?, appointment_type = ? WHERE id = ?");
                    if ($upd) {
                        $upd->bind_param("isi", $appointment_id, $appointment_type, $invoice_id);
                        $upd->execute();
                    }
                }
            } else {
                // Create a new invoice
                $invoice_no = "INV-" . date('Ymd') . "-" . rand(100, 999);
                $due_date = date('Y-m-d', strtotime('+7 days'));
                $new_invoice = $this->conn->prepare("INSERT INTO invoices (patient_id, invoice_no, due_date, appointment_id, appointment_type, type) VALUES (?, ?, ?, ?, ?, ?)");
                if (!$new_invoice) throw new Exception("Prepare failed for new invoice: " . $this->conn->error);
                
                $new_invoice->bind_param("ississ", $patient_id, $invoice_no, $due_date, $appointment_id, $appointment_type, $type);
                $new_invoice->execute();
                $invoice_id = $this->conn->insert_id;
            }

            if (!$invoice_id) return null;

            // 2. Add items to the invoice
            foreach ($items as $item) {
                $desc = $item['description'];
                $item_type = $item['type'];
                $qty = $item['quantity'] ?? 1;
                $price = $item['price'];

                $item_stmt = $this->conn->prepare("INSERT INTO invoice_items (invoice_id, item_description, item_type, quantity, unit_price) VALUES (?, ?, ?, ?, ?)");
                if ($item_stmt) {
                    $item_stmt->bind_param("isssd", $invoice_id, $desc, $item_type, $qty, $price);
                    $item_stmt->execute();
                }
            }

            // 3. Recalculate total for the invoice
            $this->updateInvoiceTotal($invoice_id);
            
            SyncManager::signal('billing', 'INSERT', $invoice_id);

            return $invoice_id;
        } catch (Throwable $e) {
            error_log("automateInvoice failed: " . $e->getMessage());
            return null;
        }
    }

    public function updateInvoiceTotal($invoice_id) {
        try {
            $total_res = $this->conn->query("SELECT SUM(subtotal) as total FROM invoice_items WHERE invoice_id = $invoice_id");
            $total = ($total_res && $row = $total_res->fetch_assoc()) ? ($row['total'] ?? 0) : 0;
            
            $stmt = $this->conn->prepare("UPDATE invoices SET total_amount = ? WHERE id = ?");
            if ($stmt) {
                $stmt->bind_param("di", $total, $invoice_id);
                if ($stmt->execute()) {
                    SyncManager::signal('billing', 'UPDATE', $invoice_id);
                }
            }
        } catch (Throwable $e) {
            error_log("updateInvoiceTotal failed: " . $e->getMessage());
        }
    }

    public function applyPayment($invoice_id, $amount) {
        $this->conn->begin_transaction();
        try {
            $stmt = $this->conn->prepare("UPDATE invoices SET paid_amount = paid_amount + ? WHERE id = ?");
            $stmt->bind_param("di", $amount, $invoice_id);
            $stmt->execute();

            // Update status
            $check = $this->conn->query("SELECT * FROM invoices WHERE id = $invoice_id")->fetch_assoc();
            $status = ($check['paid_amount'] >= $check['total_amount']) ? 'Paid' : 'Partial';
            
            $status_stmt = $this->conn->prepare("UPDATE invoices SET status = ? WHERE id = ?");
            $status_stmt->bind_param("si", $status, $invoice_id);
            $status_stmt->execute();

            // 1. Virtual Appointment Payment Linking
            if ($status === 'Paid' && $check['appointment_id']) {
                $table = ($check['appointment_type'] === 'Virtual') ? 'telemedicine_appointments' : 'appointments';
                $pay_update = $this->conn->prepare("UPDATE $table SET is_paid = 1 WHERE id = ?");
                $pay_update->bind_param("i", $check['appointment_id']);
                $pay_update->execute();

                // Send WhatsApp & System Notifications
                require_once __DIR__ . '/../includes/notifications_helper.php';
                NotificationService::setConnection($this->conn);

                // Fetch patient phone and appointment details
                $appt_res = $this->conn->query("SELECT doctor_id, patient_name, phone, appointment_date, appointment_time, status FROM $table WHERE id = " . $check['appointment_id']);
                $appt = $appt_res->fetch_assoc();

                if ($appt) {
                    // Notify Patient
                    if ($appt['phone']) {
                        $msg = "Dear {$appt['patient_name']}, your payment has been confirmed for your {$check['appointment_type']} appointment on {$appt['appointment_date']} at {$appt['appointment_time']}. " . 
                               (($appt['status'] === 'Accepted' || $appt['status'] === 'Confirmed') ? "You can now join the session from your dashboard." : "Your booking is awaiting final confirmation from the doctor.");
                        NotificationService::sendWhatsApp($appt['phone'], $msg);
                    }

                    // Notify Doctor
                    if ($appt['doctor_id']) {
                        $doc_res = $this->conn->query("SELECT name, email, phone FROM doctors WHERE id = " . $appt['doctor_id']);
                        if($doc_res->num_rows == 0) $doc_res = $this->conn->query("SELECT name, email, phone FROM telemedicine_doctors WHERE id = " . $appt['doctor_id']);
                        $doc = $doc_res->fetch_assoc();

                        $doc_msg = "Payment confirmed for appointment with {$appt['patient_name']} on {$appt['appointment_date']} at {$appt['appointment_time']}. " . 
                                   (($appt['status'] === 'Pending') ? "Please ACCEPT the appointment in your dashboard to enable the consultation link." : "Appointment is already accepted.");
                        
                        NotificationService::send('doctor', $appt['doctor_id'], 'payment_confirmed', 'Payment Received', $doc_msg, 'telemedicine_dashboard.php', [
                            'email' => $doc['email'] ?? null,
                            'phone' => $doc['phone'] ?? null
                        ]);
                    }
                }
            }

            // 2. Specialized Workflow Handling (Lab, Pharmacy, Folder)
            if ($status === 'Paid') {
                $items_res = $this->conn->query("SELECT item_type, item_description FROM invoice_items WHERE invoice_id = $invoice_id");
                $has_lab = false;
                $has_folder = false;
                $has_pharma = false;

                while ($itm = $items_res->fetch_assoc()) {
                    if ($itm['item_type'] === 'Lab') $has_lab = true;
                    if ($itm['item_type'] === 'Medication') $has_pharma = true;
                    if (strpos($itm['item_description'], 'Folder') !== false || strpos($itm['item_description'], 'Onboarding') !== false) $has_folder = true;
                }

                $patient_id = $check['patient_id'];

                if ($has_folder) {
                    $this->conn->query("UPDATE patient_onboarding SET payment_status = 'Confirmed', status = 'Paid' WHERE patient_id = $patient_id AND status IN ('Not Started', 'Payment Pending', 'Awaiting Confirmation')");
                    SyncManager::signal('patient_queue', 'UPDATE', $patient_id);
                }

                if ($has_lab) {
                    // Update Queue to move to Lab stage if they were waiting for payment
                    $this->conn->query("UPDATE patient_queue_status SET status = 'Payment Confirmed', updated_at = CURRENT_TIMESTAMP WHERE patient_id = $patient_id AND current_stage = 'Lab'");
                    SyncManager::signal('patient_queue', 'UPDATE', $patient_id);
                    SyncManager::signal('lab_requests', 'UPDATE', $patient_id);
                }

                if ($has_pharma) {
                    // Update Queue to move to Pharmacy stage
                    $this->conn->query("UPDATE patient_queue_status SET status = 'Paid & Ready', updated_at = CURRENT_TIMESTAMP WHERE patient_id = $patient_id AND current_stage = 'Pharmacy'");
                    SyncManager::signal('patient_queue', 'UPDATE', $patient_id);
                    SyncManager::signal('prescriptions', 'UPDATE', $patient_id);
                }
            }

            $this->conn->commit();
            SyncManager::signal('billing', 'UPDATE', $invoice_id);
            return true;
        } catch (Exception $e) {
            $this->conn->rollback();
            return false;
        }
    }
}
?>