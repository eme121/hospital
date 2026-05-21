<?php
session_start();
require_once '../includes/db_connect.php';

if (!isset($_SESSION['accountant_id'])) {
    header('Location: login.php');
    exit;
}

$range = $_GET['range'] ?? 'month';
$current_date = date('Y-m-d');

// SQL for Revenue by Type
$revenue_by_type_sql = "SELECT ii.item_type, SUM(ii.subtotal) as total 
                        FROM invoice_items ii 
                        JOIN invoices i ON ii.invoice_id = i.id 
                        WHERE i.status = 'Paid' ";

// SQL for Trend Data
$trend_sql = "SELECT DATE(i.created_at) as date, SUM(i.paid_amount) as total 
              FROM invoices i 
              WHERE i.status = 'Paid' ";

if ($range == 'day') {
    $revenue_by_type_sql .= " AND DATE(i.created_at) = '$current_date'";
    $trend_sql .= " AND DATE(i.created_at) = '$current_date'";
} elseif ($range == 'week') {
    $revenue_by_type_sql .= " AND i.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
    $trend_sql .= " AND i.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
} else {
    $revenue_by_type_sql .= " AND i.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
    $trend_sql .= " AND i.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
}

$revenue_by_type_sql .= " GROUP BY ii.item_type";
$trend_sql .= " GROUP BY DATE(i.created_at) ORDER BY date ASC";

$type_data = $conn->query($revenue_by_type_sql)->fetch_all(MYSQLI_ASSOC);
$trend_data = $conn->query($trend_sql)->fetch_all(MYSQLI_ASSOC);

// CSV Logic
if (isset($_GET['download'])) {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="financial_report_' . $range . '_' . date('Y-m-d') . '.csv"');
    $output = fopen('php://output', 'w');
    fputcsv($output, ['Date', 'Invoice No', 'Patient', 'Item', 'Category', 'Amount']);
    $csv_sql = "SELECT i.created_at, i.invoice_no, p.full_name, ii.item_description, ii.item_type, ii.subtotal FROM invoices i JOIN patients p ON i.patient_id = p.id JOIN invoice_items ii ON i.id = ii.invoice_id WHERE i.status = 'Paid'";
    $csv_res = $conn->query($csv_sql);
    while($row = $csv_res->fetch_assoc()) fputcsv($output, $row);
    fclose($output);
    exit;
}

$page_title = "Financial Reports";
include 'includes/portal_layout_header.php';
?>

<div class="p-10">
    <header class="flex justify-between items-end mb-12">
        <div>
            <h2 class="text-4xl font-black text-slate-900 mb-2 tracking-tight">Financial Reports</h2>
            <p class="text-slate-500 font-medium">Visualized revenue performance and exports.</p>
        </div>
        <div class="flex gap-4">
            <div class="bg-white p-1 rounded-2xl border border-slate-100 flex gap-2">
                <a href="?range=day" class="px-4 py-2 rounded-xl text-xs font-black uppercase <?php echo $range=='day'?'bg-slate-900 text-white':'text-slate-400'; ?>">Day</a>
                <a href="?range=week" class="px-4 py-2 rounded-xl text-xs font-black uppercase <?php echo $range=='week'?'bg-slate-900 text-white':'text-slate-400'; ?>">Week</a>
                <a href="?range=month" class="px-4 py-2 rounded-xl text-xs font-black uppercase <?php echo $range=='month'?'bg-slate-900 text-white':'text-slate-400'; ?>">Month</a>
            </div>
            <a href="?download=1&range=<?php echo $range; ?>" class="px-6 py-4 bg-emerald-600 text-white rounded-2xl font-black text-xs uppercase tracking-widest shadow-xl shadow-emerald-100 flex items-center gap-2">
                <i class="fas fa-download"></i> Export CSV
            </a>
        </div>
    </header>

    <div class="grid md:grid-cols-4 gap-6 mb-12">
        <?php
        $total_rev = array_sum(array_column($type_data, 'total'));
        $tx_count = count($trend_data);
        $avg_val = $tx_count > 0 ? $total_rev / $tx_count : 0;
        $top_cat = 'N/A';
        if (!empty($type_data)) {
            usort($type_data, function($a, $b) { return $b['total'] <=> $a['total']; });
            $top_cat = $type_data[0]['item_type'];
        }
        ?>
        <div class="bg-white p-6 rounded-[32px] border border-slate-100 shadow-sm">
            <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1">Total Revenue</p>
            <h4 class="text-xl font-black text-emerald-600">₦<?php echo number_format($total_rev, 2); ?></h4>
        </div>
        <div class="bg-white p-6 rounded-[32px] border border-slate-100 shadow-sm">
            <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1">Invoices Paid</p>
            <h4 class="text-xl font-black text-slate-900"><?php echo $tx_count; ?></h4>
        </div>
        <div class="bg-white p-6 rounded-[32px] border border-slate-100 shadow-sm">
            <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1">Avg. Per Day</p>
            <h4 class="text-xl font-black text-blue-600">₦<?php echo number_format($avg_val, 2); ?></h4>
        </div>
        <div class="bg-white p-6 rounded-[32px] border border-slate-100 shadow-sm">
            <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1">Top Category</p>
            <h4 class="text-xl font-black text-amber-600"><?php echo $top_cat; ?></h4>
        </div>
    </div>

    <!-- Executive Summary -->
    <div class="bg-white p-10 rounded-[40px] border border-slate-100 shadow-sm mb-12">
        <h3 class="text-lg font-black text-slate-900 uppercase tracking-tight mb-6 flex items-center gap-3">
            <i class="fas fa-file-alt text-emerald-600"></i>
            Executive Summary
        </h3>
        <div class="prose prose-slate max-w-none text-slate-600 font-medium leading-relaxed">
            <p>
                Based on the financial records for the selected period (Last <?php echo ucfirst($range); ?>), Hope Haven Hospital has generated a total confirmed revenue of 
                <span class="font-black text-slate-900">₦<?php echo number_format($total_rev, 2); ?></span>. 
                This income was collected across <span class="font-black text-slate-900"><?php echo $tx_count; ?></span> successful transactions.
            </p>
            <p class="mt-4">
                The <span class="font-black text-emerald-600"><?php echo $top_cat; ?></span> department is currently the primary revenue driver, 
                contributing significantly to the overall hospital performance. 
                <?php if($tx_count > 0): ?>
                    On average, the hospital is processing approximately <span class="font-black text-blue-600">₦<?php echo number_format($avg_val, 2); ?></span> in daily paid invoices.
                <?php endif; ?>
            </p>
            <div class="mt-6 p-6 bg-slate-50 rounded-2xl border border-slate-100 text-sm italic">
                <p>
                    <strong>Explanation:</strong> This report analyzes "Paid" invoices only. 
                    The <span class="font-bold">Revenue Trend</span> chart visualizes daily growth patterns, while the 
                    <span class="font-bold">Revenue by Category</span> breakdown identifies which hospital services (Consultations, Pharmacy, Lab, or Records) are most utilized by patients. 
                    High performance in a specific category suggests higher patient volume or optimized billing in that department.
                </p>
            </div>
        </div>
    </div>

    <div class="grid lg:grid-cols-2 gap-8">
        <div class="bg-white p-10 rounded-[40px] border border-slate-100 shadow-sm">
            <h3 class="text-lg font-black text-slate-900 uppercase tracking-tight mb-8">Revenue Trend (₦)</h3>
            <canvas id="trendChart" height="200"></canvas>
        </div>
        <div class="bg-white p-10 rounded-[40px] border border-slate-100 shadow-sm">
            <h3 class="text-lg font-black text-slate-900 uppercase tracking-tight mb-8">Revenue by Category</h3>
            <canvas id="typeChart" height="200"></canvas>
        </div>
    </div>
</div>

<script>
    new Chart(document.getElementById('trendChart'), {
        type: 'line',
        data: {
            labels: <?php echo json_encode(array_column($trend_data, 'date')); ?>,
            datasets: [{
                label: 'Daily Revenue',
                data: <?php echo json_encode(array_column($trend_data, 'total')); ?>,
                borderColor: '#10b981',
                backgroundColor: 'rgba(16,185,129,0.1)',
                fill: true,
                tension: 0.4,
                borderWidth: 4
            }]
        },
        options: { 
            responsive: true, 
            plugins: { legend: { display: false } },
            scales: { y: { beginAtZero: true, grid: { display: false } }, x: { grid: { display: false } } }
        }
    });

    new Chart(document.getElementById('typeChart'), {
        type: 'doughnut',
        data: {
            labels: <?php echo json_encode(array_column($type_data, 'item_type')); ?>,
            datasets: [{
                data: <?php echo json_encode(array_column($type_data, 'total')); ?>,
                backgroundColor: ['#10b981', '#3b82f6', '#f59e0b', '#ef4444', '#8b5cf6'],
                borderWidth: 0
            }]
        },
        options: { responsive: true, cutout: '70%' }
    });
</script>

<?php include 'includes/portal_layout_footer.php'; ?>
