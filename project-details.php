<?php
// project-details.php (Versi Sinkronisasi Format Excel Keekonomian Migas dengan Fitur Aksi & Kolom Harga/Barel)
session_start();
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: login.php");
    exit;
}
$user_id = $_SESSION['user_id'];
require_once "config.php";

$project_id = isset($_GET['id']) ? trim($_GET['id']) : null;
if (empty($project_id) || !ctype_digit($project_id)) {
    header("location: home.php");
    exit;
}

// 1. Ambil Data Proyek Utama
$project = null;
$sql_project = "SELECT * FROM projects WHERE id = ? AND user_id = ?";
if ($stmt_project = $mysqli->prepare($sql_project)) {
    $stmt_project->bind_param("ii", $project_id, $user_id);
    if ($stmt_project->execute()) {
        $result_project = $stmt_project->get_result();
        if ($result_project->num_rows == 1) {
            $project = $result_project->fetch_assoc();
        } else { header("location: home.php"); exit; }
    }
    $stmt_project->close();
}

// 2. Ambil Data Cashflow dari Database
$db_cashflows = [];
$sql_cf = "SELECT * FROM cashflows WHERE project_id = ? ORDER BY year ASC";
if ($stmt_cf = $mysqli->prepare($sql_cf)) {
    $stmt_cf->bind_param("i", $project_id);
    if ($stmt_cf->execute()) {
        $result_cf = $stmt_cf->get_result();
        $db_cashflows = $result_cf->fetch_all(MYSQLI_ASSOC);
    }
    $stmt_cf->close();
}

// 3. RE-KALKULASI MENGIKUTI FORMAT EXCEL (Termasuk Depresiasi Otomatis & Tahun 0)
$excel_sheet = [];
$total_npv = 0;
$discount_rate = 0.10; // NPV @10%

// --- BARIS TAHUN 0 (Inisiasi Investasi Awal) ---
$capex_t0 = $project['invest_capital'] ?? 0;
$non_capex_t0 = $project['invest_noncapital'] ?? 0;
$ncf_t0 = -($capex_t0 + $non_capex_t0);

$excel_sheet[0] = [
    'id' => null, 
    'year' => 0,
    'production' => 0,
    'price_per_bbl' => 0, // Tahun 0 belum ada penjualan
    'income' => 0,
    'capex' => $capex_t0,
    'non_capex' => $non_capex_t0,
    'opex' => 0,
    'depreciation' => 0,
    'taxable_income' => 0,
    'tax' => 0,
    'net_cashflow' => $ncf_t0
];
$total_npv += $ncf_t0 / pow((1 + $discount_rate), 0);

// --- HITUNG DEPRESIASI OTOMATIS TAHUNAN ---
$N = $project['investment_years'] > 0 ? $project['investment_years'] : 7;
$depreciation_method = $project['depreciation_method'] ?? 'Straight Line';
$remaining_book_value = $capex_t0;

// Mapping cashflow db berdasarkan tahun biar gampang di-loop
$db_mapped = [];
foreach($db_cashflows as $cf) {
    $db_mapped[$cf['year']] = $cf;
}

// Loop untuk Tahun 1 sampai N
for ($t = 1; $t <= $N; $t++) {
    // Ambil data input dari user (jika ada), jika tidak default 0
    $cf_id = isset($db_mapped[$t]) ? $db_mapped[$t]['id'] : null;
    $prod = isset($db_mapped[$t]) ? $db_mapped[$t]['production'] : 0;
    $inc = isset($db_mapped[$t]) ? $db_mapped[$t]['income'] : 0;
    $op = isset($db_mapped[$t]) ? $db_mapped[$t]['opex'] : 0;

    // Hitung secara matematis Harga/Barel jika kolom di DB belum ada
    // Income ($M) * 1000 / Production (Mbbl) = $/bbl
    $price_bbl = ($prod > 0) ? ($inc / $prod) : 0;

    // Hitung Depresiasi Kontrak (Di)
    $dep = 0;
    if ($capex_t0 > 0) {
        if ($depreciation_method == "Straight Line") {
            $dep = $capex_t0 / $N;
        } elseif ($depreciation_method == "Double Declining") {
            if ($t == $N) {
                $dep = $remaining_book_value; // Sisa di tahun terakhir dihabiskan
            } else {
                $dep = $remaining_book_value * (2 / $N);
                $remaining_book_value -= $dep;
            }
        }
    }

    // Hitung Parameter Keekonomian Sesuai Rule Sheet Excel
    $taxable_inc = $inc - $op - $dep;
    if($taxable_inc < 0) $taxable_inc = 0; // Taxable tidak minus jika rugi komersial

    $tax_rate = ($project['tax'] ?? 44) / 100;
    $tax_paid = $taxable_inc * $tax_rate;
    
    // Net Cashflow = Income - Opex - Tax - Investasi (di tahun berjalan jika ada)
    $ncf = $inc - $op - $tax_paid; 

    $excel_sheet[$t] = [
        'id' => $cf_id, 
        'year' => $t,
        'production' => $prod,
        'price_per_bbl' => $price_bbl,
        'income' => $inc,
        'capex' => 0,
        'non_capex' => 0,
        'opex' => $op,
        'depreciation' => $dep,
        'taxable_income' => $taxable_inc,
        'tax' => $tax_paid,
        'net_cashflow' => $ncf
    ];

    // Akumulasi NPV
    $total_npv += $ncf / pow((1 + $discount_rate), $t);
}

$mysqli->close();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Analisis Keekonomian Proyek - Petromine Analytics</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>body { font-family: 'Plus Jakarta Sans', sans-serif; }</style>
</head>
<body class="bg-slate-950 text-slate-100 min-h-screen p-6">
    <div class="max-w-7xl mx-auto space-y-6">
        
        <div class="flex justify-between items-center bg-slate-900 border border-slate-800 p-6 rounded-2xl">
            <div>
                <a href="home.php" class="text-xs text-emerald-400 hover:underline"><i class="fas fa-arrow-left mr-1"></i> Kembali ke Dashboard</a>
                <h1 class="text-xl font-bold text-white mt-2"><?php echo htmlspecialchars($project['name']); ?></h1>
                <p class="text-xs text-slate-400 mt-1">Metode Depresiasi: <span class="text-slate-200 font-semibold"><?php echo htmlspecialchars($depreciation_method); ?></span></p>
            </div>
            <div class="flex gap-3">
                <a href="edit-project.php?id=<?php echo $project['id']; ?>" class="bg-slate-800 hover:bg-slate-700 px-4 py-2 text-xs font-bold rounded-xl border border-slate-700">Edit Parameter</a>
                <a href="add-cashflow.php?project_id=<?php echo $project['id']; ?>" class="bg-emerald-500 hover:bg-emerald-600 text-slate-950 px-4 py-2 text-xs font-bold rounded-xl shadow-md">Input Data Tahunan</a>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
            <div class="bg-slate-900 border border-slate-800 p-5 rounded-2xl">
                <span class="text-[10px] uppercase font-bold text-slate-500 tracking-wider">Net Present Value (NPV @10%)</span>
                <p class="text-xl font-bold text-emerald-400 mt-2">$<?php echo number_format($total_npv, 2); ?></p>
            </div>
            <div class="bg-slate-900 border border-slate-800 p-5 rounded-2xl">
                <span class="text-[10px] uppercase font-bold text-slate-500 tracking-wider">Capital Invest (CAPEX)</span>
                <p class="text-xl font-bold text-white mt-2">$<?php echo number_format($project['invest_capital']); ?></p>
            </div>
            <div class="bg-slate-900 border border-slate-800 p-5 rounded-2xl">
                <span class="text-[10px] uppercase font-bold text-slate-500 tracking-wider">Non-Capital Invest</span>
                <p class="text-xl font-bold text-white mt-2">$<?php echo number_format($project['invest_noncapital']); ?></p>
            </div>
            <div class="bg-slate-900 border border-slate-800 p-5 rounded-2xl">
                <span class="text-[10px] uppercase font-bold text-slate-500 tracking-wider">Pajak (Tax Rate)</span>
                <p class="text-xl font-bold text-amber-400 mt-2"><?php echo $project['tax']; ?>%</p>
            </div>
        </div>

        <div class="bg-slate-900 border border-slate-800 p-6 rounded-2xl">
            <h3 class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-4">Grafik Net Cashflow Proyek (Tahun 0 - <?php echo $N; ?>)</h3>
            <div class="h-64"><canvas id="ncfChart"></canvas></div>
        </div>

        <div class="bg-slate-900 border border-slate-800 rounded-2xl overflow-hidden shadow-2xl">
            <div class="px-6 py-4 border-b border-slate-800 bg-slate-900/60">
                <h3 class="text-xs font-bold text-slate-400 uppercase tracking-wider">Cashflow Table</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-xs">
                    <thead>
                        <tr class="bg-slate-950 text-slate-400 border-b border-slate-800 font-bold text-[11px]">
                            <th class="p-4">Tahun</th>
                            <th class="p-4">Produksi</th>
                            <th class="p-4">Harga Jual</th>
                            <th class="p-4">Income</th>
                            <th class="p-4">Opex</th>
                            <th class="p-4">Di (Depresiasi)</th>
                            <th class="p-4">Taxable Income</th>
                            <th class="p-4">Tax (Pajak)</th>
                            <th class="p-4">NCF Undiscounted</th>
                            <th class="p-4 text-center">Aksi</th>
                        </tr>
                        <tr class="bg-slate-950/80 text-slate-500 border-b border-slate-800 text-[10px] font-medium">
                            <th class="px-4 pb-2"></th>
                            <th class="px-4 pb-2">(Mbbl)</th>
                            <th class="px-4 pb-2">($/bbl)</th>
                            <th class="px-4 pb-2">($M)</th>
                            <th class="px-4 pb-2">($M)</th>
                            <th class="px-4 pb-2">($M)</th>
                            <th class="px-4 pb-2">($M)</th>
                            <th class="px-4 pb-2">($M)</th>
                            <th class="px-4 pb-2">($M)</th>
                            <th class="px-4 pb-2"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800/60 text-slate-300">
                        <?php foreach($excel_sheet as $row): ?>
                            <tr class="<?php echo $row['year'] == 0 ? 'bg-slate-950/40 font-medium' : 'hover:bg-slate-800/30'; ?> transition-colors">
                                <td class="p-4 font-bold text-slate-400">Tahun <?php echo $row['year']; ?></td>
                                <td class="p-4"><?php echo $row['year'] == 0 ? '-' : number_format($row['production']); ?></td>
                                <td class="p-4 text-amber-400"><?php echo ($row['year'] == 0 || $row['price_per_bbl'] == 0) ? '-' : '$' . number_format($row['price_per_bbl'], 2); ?></td>
                                <td class="p-4 text-emerald-400">$<?php echo number_format($row['income']); ?></td>
                                <td class="p-4 text-rose-400">$<?php echo number_format($row['opex']); ?></td>
                                <td class="p-4 text-slate-400">$<?php echo number_format($row['depreciation'], 1); ?></td>
                                <td class="p-4">$<?php echo number_format($row['taxable_income'], 1); ?></td>
                                <td class="p-4 text-amber-500">$<?php echo number_format($row['tax'], 1); ?></td>
                                <td class="p-4 font-bold <?php echo $row['net_cashflow'] >= 0 ? 'text-emerald-400' : 'text-rose-500'; ?>">
                                    $<?php echo number_format($row['net_cashflow'], 1); ?>
                                </td>
                                <td class="p-4 text-center">
                                    <?php if ($row['year'] > 0 && !empty($row['id'])): ?>
                                        <div class="flex items-center justify-center gap-2">
                                            <a href="edit-cashflow.php?id=<?php echo $row['id']; ?>" class="text-blue-400 hover:text-blue-300 p-1" title="Edit Data Tahun <?php echo $row['year']; ?>">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <a href="delete-cashflow.php?id=<?php echo $row['id']; ?>&project_id=<?php echo $project_id; ?>" 
                                               onclick="return confirm('Apakah Anda yakin ingin menghapus data cashflow Tahun <?php echo $row['year']; ?>?');" 
                                               class="text-rose-400 hover:text-rose-300 p-1" title="Hapus Data Tahun <?php echo $row['year']; ?>">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                        </div>
                                    <?php elseif ($row['year'] > 0 && empty($row['id'])): ?>
                                        <a href="add-cashflow.php?project_id=<?php echo $project_id; ?>&year=<?php echo $row['year']; ?>" class="text-emerald-500 hover:underline text-[10px]">
                                            + Isi Data
                                        </a>
                                    <?php else: ?>
                                        <span class="text-slate-600">-</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener("DOMContentLoaded", function () {
            const ctx = document.getElementById('ncfChart').getContext('2d');
            const sheetData = <?php echo json_encode(array_values($excel_sheet)); ?>;
            
            const labels = sheetData.map(item => 'Th-' + item.year);
            const ncfValues = sheetData.map(item => item.net_cashflow);

            new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Net Cashflow ($M)',
                        data: ncfValues,
                        backgroundColor: ncfValues.map(v => v >= 0 ? 'rgba(16, 185, 129, 0.4)' : 'rgba(244, 63, 94, 0.4)'),
                        borderColor: ncfValues.map(v => v >= 0 ? '#10b981' : '#f43f5e'),
                        borderWidth: 1.5,
                        borderRadius: 4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: { grid: { color: '#1e293b' }, ticks: { color: '#94a3b8' } },
                        x: { grid: { display: false }, ticks: { color: '#94a3b8' } }
                    },
                    plugins: { legend: { display: false } }
                }
            });
        });
    </script>
</body>
</html>