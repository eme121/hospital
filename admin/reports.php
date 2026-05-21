<?php
session_start();
require_once '../includes/db_connect.php';
require_once '../includes/report_engine.php';

if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: index.php');
    exit;
}

$engine = new ReportEngine($conn);

// Filters
$start_date = $_GET['start_date'] ?? date('Y-m-d', strtotime('-30 days'));
$end_date = $_GET['end_date'] ?? date('Y-m-d');
$report_type = $_GET['type'] ?? 'financial';

// Export Logic
if (isset($_GET['export'])) {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="report_' . $report_type . '_' . date('Ymd') . '.csv"');
    $output = fopen('php://output', 'w');
    
    if ($report_type == 'financial') {
        fputcsv($output, ['Department', 'Volume', 'Revenue (₦)']);
        $data = $engine->getRevenueByDepartment($start_date, $end_date);
        foreach ($data as $row) fputcsv($output, $row);
    } elseif ($report_type == 'pharmacy') {
        fputcsv($output, ['Drug Name', 'Quantity Dispensed', 'Total Revenue (₦)']);
        $data = $engine->getPharmacyMetrics($start_date, $end_date);
        foreach ($data as $row) fputcsv($output, $row);
    }
    fclose($output);
    exit;
}

// Data for Summaries
$fin_summary = $engine->getFinancialSummary($start_date, $end_date);
$revenue_dept = $engine->getRevenueByDepartment($start_date, $end_date);
$low_stock = $engine->getLowStockAlerts();
$vitals = $engine->getVitalsSummary($start_date, $end_date);
$diagnoses = $engine->getTopDiagnoses($start_date, $end_date);
$patient_volume = $engine->getPatientVolume($start_date, $end_date);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Advanced Analytics | Hope Haven Hospital</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <?php include 'includes/header_scripts.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>body { font-family: 'Plus Jakarta Sans', sans-serif; }</style>
</head>
<body class="bg-slate-50 min-h-screen">
    <?php include 'includes/admin_sidebar.php'; ?>
    <?php include 'includes/admin_topbar.php'; ?>

    <main id="main-wrapper" class="pt-[90px] px-8 pb-10 transition-all duration-300">
        <!-- Header -->
        <div class="flex flex-col md:flex-row justify-between items-start md:items-end mb-10 gap-6">
            <div>
                <h1 class="text-3xl font-black text-slate-900 mb-2 tracking-tight uppercase">Hospital Intelligence Engine</h1>
                <p class="text-slate-500 font-medium">Production-grade analytics across all medical departments.</p>
            </div>
            
            <form class="flex flex-wrap gap-4 items-end bg-white p-6 rounded-[32px] shadow-sm border border-slate-100">
                <div>
                    <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">From</label>
                    <input type="date" name="start_date" value="<?php echo $start_date; ?>" class="bg-slate-50 border-0 rounded-xl px-4 py-2.5 text-xs font-bold text-slate-700 outline-none">
                </div>
                <div>
                    <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">To</label>
                    <input type="date" name="end_date" value="<?php echo $end_date; ?>" class="bg-slate-50 border-0 rounded-xl px-4 py-2.5 text-xs font-bold text-slate-700 outline-none">
                </div>
                <button type="submit" class="bg-slate-900 text-white px-6 py-2.5 rounded-xl font-black text-[10px] uppercase tracking-widest hover:bg-blue-600 transition-all">Filter</button>
                <a href="?export=1&type=<?php echo $report_type; ?>&start_date=<?php echo $start_date; ?>&end_date=<?php echo $end_date; ?>" 
                   class="bg-emerald-50 text-emerald-600 border border-emerald-100 px-6 py-2.5 rounded-xl font-black text-[10px] uppercase tracking-widest hover:bg-emerald-600 hover:text-white transition-all">Export CSV</a>
            </form>
        </div>

        <!-- Financial Summary Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-12">
            <div class="bg-white p-8 rounded-[40px] shadow-sm border border-slate-100">
                <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-3">Total Billed</p>
                <h3 class="text-3xl font-black text-slate-900">₦<?php echo number_format($fin_summary['total_billed'] ?? 0); ?></h3>
                <div class="mt-4 flex items-center gap-2 text-emerald-500 text-[10px] font-bold">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path></svg>
                    <span>Revenue Potential</span>
                </div>
            </div>
            <div class="bg-blue-600 p-8 rounded-[40px] shadow-blue-200 shadow-xl text-white">
                <p class="text-[10px] font-black text-blue-200 uppercase tracking-widest mb-3">Actual Collected</p>
                <h3 class="text-3xl font-black">₦<?php echo number_format($fin_summary['total_collected'] ?? 0); ?></h3>
                <div class="mt-4 flex items-center gap-2 text-blue-200 text-[10px] font-bold">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    <span>Net Liquidity</span>
                </div>
            </div>
            <div class="bg-white p-8 rounded-[40px] shadow-sm border border-slate-100">
                <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-3">Outstanding Debt</p>
                <h3 class="text-3xl font-black text-rose-600">₦<?php echo number_format($fin_summary['total_outstanding'] ?? 0); ?></h3>
                <div class="mt-4 flex items-center gap-2 text-rose-400 text-[10px] font-bold">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                    <span>Credit Exposure</span>
                </div>
            </div>
            <div class="bg-white p-8 rounded-[40px] shadow-sm border border-slate-100">
                <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-3">Clinical Activity</p>
                <h3 class="text-3xl font-black text-slate-900"><?php echo number_format($fin_summary['invoice_count'] ?? 0); ?></h3>
                <div class="mt-4 flex items-center gap-2 text-slate-400 text-[10px] font-bold">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                    <span>Unique Encounters</span>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 mb-12">
            <!-- Patient Volume Chart -->
            <div class="lg:col-span-2 bg-white p-10 rounded-[48px] border border-slate-100 shadow-sm">
                <div class="flex justify-between items-center mb-8">
                    <h3 class="text-xl font-black text-slate-900">Encounters vs Schedule</h3>
                    <span class="px-4 py-1.5 bg-blue-50 text-blue-600 rounded-full text-[10px] font-black uppercase tracking-widest">Growth Trend</span>
                </div>
                <canvas id="patientVolumeChart" height="250"></canvas>
            </div>

            <!-- Revenue by Department -->
            <div class="bg-white p-10 rounded-[48px] border border-slate-100 shadow-sm">
                <h3 class="text-xl font-black text-slate-900 mb-8">Revenue Distribution</h3>
                <div class="space-y-6">
                    <?php foreach ($revenue_dept as $dept): 
                        $percentage = ($fin_summary['total_billed'] > 0) ? ($dept['revenue'] / $fin_summary['total_billed'] * 100) : 0;
                        $color = ($dept['item_type'] == 'Medication') ? 'bg-blue-600' : (($dept['item_type'] == 'Lab') ? 'bg-emerald-500' : 'bg-purple-500');
                    ?>
                        <div>
                            <div class="flex justify-between items-center mb-2">
                                <span class="text-xs font-black text-slate-600 uppercase tracking-tighter"><?php echo $dept['item_type']; ?></span>
                                <span class="text-xs font-bold text-slate-900">₦<?php echo number_format($dept['revenue']); ?></span>
                            </div>
                            <div class="h-2 w-full bg-slate-50 rounded-full overflow-hidden">
                                <div class="<?php echo $color; ?> h-full" style="width: <?php echo $percentage; ?>%"></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-12">
            <!-- Nursing & Clinical Health -->
            <div class="bg-white p-10 rounded-[48px] border border-slate-100 shadow-sm">
                <h3 class="text-xl font-black text-slate-900 mb-8">Clinical Risk Assessment</h3>
                <div class="grid grid-cols-2 gap-6 mb-8">
                    <div class="p-6 bg-rose-50 rounded-3xl border border-rose-100">
                        <p class="text-[10px] font-black text-rose-400 uppercase tracking-widest mb-1">Fever Cases</p>
                        <h4 class="text-2xl font-black text-rose-600"><?php echo $vitals['fever_cases']; ?></h4>
                    </div>
                    <div class="p-6 bg-emerald-50 rounded-3xl border border-emerald-100">
                        <p class="text-[10px] font-black text-emerald-400 uppercase tracking-widest mb-1">Avg Patient Temp</p>
                        <h4 class="text-2xl font-black text-emerald-600"><?php echo round($vitals['avg_temp'], 1); ?>°C</h4>
                    </div>
                </div>
                <h4 class="text-xs font-black text-slate-400 uppercase tracking-widest mb-4">Top Presenting Complaints</h4>
                <div class="space-y-3">
                    <?php foreach ($diagnoses as $diag): ?>
                        <div class="flex items-center justify-between p-4 bg-slate-50 rounded-2xl">
                            <span class="text-xs font-bold text-slate-700"><?php echo $diag['diagnosis']; ?></span>
                            <span class="text-xs font-black text-slate-900"><?php echo $diag['frequency']; ?> Cases</span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Pharmacy Operational Alerts -->
            <div class="bg-white p-10 rounded-[48px] border border-slate-100 shadow-sm">
                <h3 class="text-xl font-black text-slate-900 mb-8">Pharmacy Supply Chain</h3>
                <div class="mb-8">
                    <h4 class="text-xs font-black text-rose-500 uppercase tracking-widest mb-4 flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                        Stock Depletion Warnings
                    </h4>
                    <div class="grid grid-cols-1 gap-4">
                        <?php foreach ($low_stock as $item): ?>
                            <div class="flex items-center justify-between p-5 bg-rose-50/50 border border-rose-100 rounded-3xl">
                                <div>
                                    <p class="text-sm font-black text-slate-900"><?php echo $item['name']; ?></p>
                                    <p class="text-[10px] font-bold text-rose-600 uppercase">Current Stock: <?php echo $item['stock_quantity']; ?> <?php echo $item['unit']; ?></p>
                                </div>
                                <span class="px-4 py-1.5 bg-rose-600 text-white rounded-xl text-[10px] font-black">Restock Now</span>
                            </div>
                        <?php endforeach; ?>
                        <?php if (empty($low_stock)): ?>
                            <div class="py-10 text-center text-slate-400 italic text-sm">All stock levels are optimal.</div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script>
        // Chart.js Implementation
        const ctx = document.getElementById('patientVolumeChart').getContext('2d');
        const labels = <?php echo json_encode(array_values(array_unique(array_column($patient_volume, 'date')))); ?>;
        
        // Group data by type
        const physicalData = labels.map(date => {
            const entry = <?php echo json_encode($patient_volume); ?>.find(v => v.date === date && v.appt_type === 'Physical');
            return entry ? entry.count : 0;
        });

        const virtualData = labels.map(date => {
            const entry = <?php echo json_encode($patient_volume); ?>.find(v => v.date === date && v.appt_type === 'Virtual');
            return entry ? entry.count : 0;
        });

        new Chart(ctx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [
                    {
                        label: 'Physical Consults',
                        data: physicalData,
                        borderColor: '#2563eb',
                        backgroundColor: 'rgba(37, 99, 235, 0.1)',
                        borderWidth: 4,
                        tension: 0.4,
                        fill: true
                    },
                    {
                        label: 'Virtual (Telemedicine)',
                        data: virtualData,
                        borderColor: '#8b5cf6',
                        backgroundColor: 'rgba(139, 92, 246, 0.1)',
                        borderWidth: 4,
                        tension: 0.4,
                        fill: true
                    }
                ]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: { position: 'bottom', labels: { font: { weight: '800', size: 10 } } }
                },
                scales: {
                    y: { beginAtZero: true, grid: { display: false } },
                    x: { grid: { display: false } }
                }
            }
        });
    </script>
</body>
</html>
