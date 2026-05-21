<?php
require_once 'includes/db_connect.php';
require_once 'includes/sync_helper.php';

echo "<h2>Hope Haven Signaling Diagnostic</h2>";

// 1. Check Tables
$tables = ['sync_registry', 'sync_signals', 'telemedicine_presence'];
foreach ($tables as $t) {
    $res = $conn->query("SHOW TABLES LIKE '$t'");
    if ($res->num_rows > 0) {
        echo "✓ Table <b>$t</b> exists.<br>";
        
        // Check Columns for sync_signals
        if ($t === 'sync_signals') {
            $cols = ['sender_id', 'sender_name', 'payload'];
            foreach ($cols as $c) {
                $cres = $conn->query("SHOW COLUMNS FROM sync_signals LIKE '$c'");
                if ($cres->num_rows > 0) echo "&nbsp;&nbsp;✓ Column <b>$c</b> found.<br>";
                else echo "&nbsp;&nbsp;✗ <b>MISSING COLUMN: $c</b> (Please run setup_sync_hub.php again)<br>";
            }
        }
    } else {
        echo "✗ <b>MISSING TABLE: $t</b> (Please run setup_sync_hub.php)<br>";
    }
}

// 2. Check Registry
$res = $conn->query("SELECT * FROM sync_registry WHERE module_name = 'telemedicine_chat'");
if ($res->num_rows > 0) {
    echo "✓ Module <b>telemedicine_chat</b> is registered.<br>";
} else {
    echo "✗ <b>telemedicine_chat is NOT registered.</b> (Running setup_sync_hub.php should fix this)<br>";
}

// 3. Test a Signal
echo "<h3>Testing Signal Dispatch...</h3>";
$test_token = SyncManager::signal('telemedicine_chat', 'DIAGNOSTIC_TEST', 999, 'Test Payload');
if ($test_token) {
    echo "✓ Signal dispatched successfully (Token: $test_token).<br>";
    
    // Verify it's in the DB
    $res = $conn->query("SELECT * FROM sync_signals WHERE signal_type = 'DIAGNOSTIC_TEST' ORDER BY id DESC LIMIT 1");
    if ($row = $res->fetch_assoc()) {
        echo "✓ Signal verified in database (ID: " . $row['id'] . ").<br>";
    } else {
        echo "✗ <b>Signal was NOT found in database after dispatch.</b><br>";
    }
} else {
    echo "✗ <b>Signal dispatch failed.</b><br>";
}

echo "<br><a href='telemedicine_case.php?id=17'>Go back to War Room</a>";
?>