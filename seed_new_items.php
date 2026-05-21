<?php
require_once 'includes/db_connect.php';

// 1. Seed Vaccine
$stmt1 = $conn->prepare("INSERT INTO main_store_inventory (drug_name, category, packaging_type, unit, batch_number, expiry_date, quantity, unit_cost_price, total_cost_price, supplier_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
$name1 = "AstraZeneca Vaccine";
$cat1 = "Vaccine";
$pkg1 = "Vial";
$unit1 = "0.5ml";
$batch1 = "VAC-99221";
$exp1 = "2027-12-31";
$qty1 = 100;
$ucp1 = 2500.00;
$tcp1 = 250000.00;
$sid1 = 1; // Emzor
$stmt1->bind_param("ssssssiddi", $name1, $cat1, $pkg1, $unit1, $batch1, $exp1, $qty1, $ucp1, $tcp1, $sid1);

// 2. Seed Bottle item
$stmt2 = $conn->prepare("INSERT INTO main_store_inventory (drug_name, category, packaging_type, unit, batch_number, expiry_date, quantity, unit_cost_price, total_cost_price, supplier_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
$name2 = "Benylin Cough Syrup";
$cat2 = "Syrup";
$pkg2 = "Bottle";
$unit2 = "100ml";
$batch2 = "BOT-88332";
$exp2 = "2028-05-20";
$qty2 = 50;
$ucp2 = 1200.00;
$tcp2 = 60000.00;
$sid2 = 2; // GSK
$stmt2->bind_param("ssssssiddi", $name2, $cat2, $pkg2, $unit2, $batch2, $exp2, $qty2, $ucp2, $tcp2, $sid2);

echo "<h1>Seeding New Items...</h1>";

if ($stmt1->execute()) {
    echo "<p>✅ Added 100 units of $name1 (Vaccine).</p>";
} else {
    echo "<p>❌ Error adding vaccine: " . $conn->error . "</p>";
}

if ($stmt2->execute()) {
    echo "<p>✅ Added 50 units of $name2 (Bottle).</p>";
} else {
    echo "<p>❌ Error adding bottle item: " . $conn->error . "</p>";
}

echo "<br><a href='pharmacy/inventory.php?tab=main-store'>View in Main Store</a>";
?>