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
$discount_rate = 0.10; // NPV @10%

// --- BARIS TAHUN 0 (Inisiasi Investasi Awal) ---
$capex_t0 = $project['invest_capital'] ?? 0;
$non_capex_t0 = $project['invest_noncapital'] ?? 0;
$ncf_t0 = -($capex_t0 + $non_capex_t0);

$excel_sheet[0] = [
    'id' => null,
    'year' => 0,
    'production' => 0,
    'price_per_bbl' => 0,
    'income' => 0,
    'capex' => $capex_t0,
    'non_capex' => $non_capex_t0,
    'opex' => 0,
    'depreciation' => 0,
    'taxable_income' => 0,
    'tax' => 0,
    'net_cashflow' => $ncf_t0
];

// --- HITUNG DEPRESIASI OTOMATIS TAHUNAN ---
$N = $project['investment_years'] > 0 ? $project['investment_years'] : 7;
$depreciation_method = $project['depreciation_method'] ?? 'Straight Line';
$remaining_book_value = $capex_t0;

$db_mapped = [];
foreach($db_cashflows as $cf) {
    $db_mapped[$cf['year']] = $cf;
}

for ($t = 1; $t <= $N; $t++) {
    $cf_id = isset($db_mapped[$t]) ? $db_mapped[$t]['id'] : null;
    $prod = isset($db_mapped[$t]) ? $db_mapped[$t]['production'] : 0;
    $inc = isset($db_mapped[$t]) ? $db_mapped[$t]['income'] : 0;
    $op = isset($db_mapped[$t]) ? $db_mapped[$t]['opex'] : 0;

    $price_bbl = ($prod > 0) ? ($inc / $prod) : 0;

    $dep = 0;
    if ($capex_t0 > 0) {
        if ($depreciation_method == "Straight Line") {
            $dep = $capex_t0 / $N;
        } elseif ($depreciation_method == "Double Declining") {
            if ($t == $N) {
                $dep = $remaining_book_value;
            } else {
                $dep = $remaining_book_value * (2 / $N);
                $remaining_book_value -= $dep;
            }
        }
    }

    $taxable_inc = $inc - $op - $dep;
    if($taxable_inc < 0) $taxable_inc = 0;

    $tax_rate = ($project['tax'] ?? 44) / 100;
    $tax_paid = $taxable_inc * $tax_rate;
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
}

// ==============================================================
// KALKULASI INDIKATOR EKONOMI MIGAS (sesuai materi kuliah FM)
// ==============================================================

$total_investasi = $capex_t0 + $non_capex_t0;

// --- 1. NPV @10% ---
$total_npv = 0;
foreach ($excel_sheet as $row) {
    $total_npv += $row['net_cashflow'] / pow((1 + $discount_rate), $row['year']);
}

// --- 2. POT (Pay Out Time / Payback Period) ---
// POT = tahun di mana akumulasi NCF pertama kali >= 0
$cumulative_ncf = 0;
$payback_year = null;
$pot_decimal = null;
$prev_cumulative = 0;

foreach ($excel_sheet as $row) {
    $prev_cumulative = $cumulative_ncf;
    $cumulative_ncf += $row['net_cashflow'];
    if ($payback_year === null && $cumulative_ncf >= 0 && $row['year'] > 0) {
        $payback_year = $row['year'];
        // Interpolasi: POT = (tahun sebelumnya) + (sisa defisit / NCF tahun ini)
        if ($row['net_cashflow'] > 0) {
            $pot_decimal = ($row['year'] - 1) + (abs($prev_cumulative) / $row['net_cashflow']);
        } else {
            $pot_decimal = $row['year'];
        }
    }
}

// --- 3. ROR / IRR (Rate of Return) menggunakan iterasi bisection ---
// ROR adalah discount rate dimana NPV = 0
function calcNPV($cashflows, $rate) {
    $npv = 0;
    foreach ($cashflows as $row) {
        $npv += $row['net_cashflow'] / pow((1 + $rate), $row['year']);
    }
    return $npv;
}

$ror_value = null;
$npv_low = calcNPV($excel_sheet, 0.001);
$npv_high = calcNPV($excel_sheet, 0.999);

if ($npv_low > 0 && $npv_high < 0) {
    // Bisection method
    $lo = 0.001;
    $hi = 0.999;
    for ($i = 0; $i < 100; $i++) {
        $mid = ($lo + $hi) / 2;
        $npv_mid = calcNPV($excel_sheet, $mid);
        if (abs($npv_mid) < 0.0001) break;
        if ($npv_mid > 0) $lo = $mid;
        else $hi = $mid;
    }
    $ror_value = $mid * 100; // dalam persen
} elseif ($npv_low <= 0) {
    $ror_value = 0; // proyek tidak menguntungkan sama sekali
}
// Jika npv_high >= 0, ROR > 99.9% (sangat menguntungkan)

// --- 4. PIR (Profit to Investment Ratio) ---
// PIR = Σ NCF undiscounted / total investasi
$sum_ncf_undiscounted = 0;
foreach ($excel_sheet as $row) {
    $sum_ncf_undiscounted += $row['net_cashflow'];
}
$pir_value = ($total_investasi > 0) ? ($sum_ncf_undiscounted / $total_investasi) : null;

// --- 5. DPR (Discounted Profit to Investment Ratio) ---
// DPR = NPV / total investasi
$dpr_value = ($total_investasi > 0) ? ($total_npv / $total_investasi) : null;

// --- Konversi POT ke Tahun dan Bulan ---
$pot_tahun = null;
$pot_bulan = null;
if ($pot_decimal !== null) {
    $pot_tahun = floor($pot_decimal);
    $pot_bulan = round(($pot_decimal - $pot_tahun) * 12);
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

        <!-- Header -->
        <div class="flex justify-between items-center bg-slate-900 border border-slate-800 p-6 rounded-2xl">
            <div>
                <a href="home.php" class="text-xs text-emerald-400 hover:underline"><i class="fas fa-arrow-left mr-1"></i> Kembali ke Dashboard</a>
                <h1 class="text-xl font-bold text-white mt-2"><?php echo htmlspecialchars($project['name']); ?></h1>
                <p class="text-xs text-slate-400 mt-1">Metode Depresiasi: <span class="text-slate-200 font-semibold"><?php echo htmlspecialchars($depreciation_method); ?></span> &nbsp;|&nbsp; Durasi Kontrak: <span class="text-slate-200 font-semibold"><?php echo $N; ?> Tahun</span></p>
            </div>
            <div class="flex gap-3">
                <a href="edit-project.php?id=<?php echo $project['id']; ?>" class="bg-slate-800 hover:bg-slate-700 px-4 py-2 text-xs font-bold rounded-xl border border-slate-700">Edit Parameter</a>
                <a href="add-cashflow.php?project_id=<?php echo $project['id']; ?>" class="bg-emerald-500 hover:bg-emerald-600 text-slate-950 px-4 py-2 text-xs font-bold rounded-xl shadow-md">Input Data Tahunan</a>
            </div>
        </div>

        <!-- Kartu Ringkasan Parameter -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
            <div class="bg-slate-900 border border-slate-800 p-5 rounded-2xl">
                <span class="text-[10px] uppercase font-bold text-slate-500 tracking-wider">Capital Invest (CAPEX)</span>
                <p class="text-xl font-bold text-white mt-2">$<?php echo number_format($project['invest_capital']); ?></p>
            </div>
            <div class="bg-slate-900 border border-slate-800 p-5 rounded-2xl">
                <span class="text-[10px] uppercase font-bold text-slate-500 tracking-wider">Non-Capital Invest</span>
                <p class="text-xl font-bold text-white mt-2">$<?php echo number_format($project['invest_noncapital']); ?></p>
            </div>
            <div class="bg-slate-900 border border-slate-800 p-5 rounded-2xl">
                <span class="text-[10px] uppercase font-bold text-slate-500 tracking-wider">Total Investasi (Tahun-0)</span>
                <p class="text-xl font-bold text-amber-400 mt-2">$<?php echo number_format($total_investasi); ?></p>
            </div>
            <div class="bg-slate-900 border border-slate-800 p-5 rounded-2xl">
                <span class="text-[10px] uppercase font-bold text-slate-500 tracking-wider">Pajak (Tax Rate)</span>
                <p class="text-xl font-bold text-amber-400 mt-2"><?php echo $project['tax']; ?>%</p>
            </div>
        </div>

        <!-- Grafik NCF -->
        <div class="bg-slate-900 border border-slate-800 p-6 rounded-2xl">
            <h3 class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-4">Grafik Net Cashflow Proyek (Tahun 0 – <?php echo $N; ?>)</h3>
            <div class="h-64"><canvas id="ncfChart"></canvas></div>
        </div>

        <!-- Tabel Cashflow -->
        <div class="bg-slate-900 border border-slate-800 rounded-2xl overflow-hidden shadow-2xl">
            <div class="px-6 py-4 border-b border-slate-800 bg-slate-900/60">
                <h3 class="text-xs font-bold text-slate-400 uppercase tracking-wider">Tabel Cashflow Tahunan</h3>
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

        <!-- ================================================================== -->
        <!-- LAPORAN INDIKATOR EKONOMI MIGAS (sesuai kaidah akademik FM)        -->
        <!-- ================================================================== -->
        <?php
        // Tentukan kelayakan berdasarkan NPV sebagai indikator utama
        $is_feasible = $total_npv > 0;

        // Kriteria tambahan untuk kesimpulan komprehensif
        $npv_ok  = $total_npv > 0;
        $pot_ok  = ($pot_decimal !== null) && ($pot_decimal <= $N * 0.5); // POT <= 50% umur proyek = baik
        $ror_ok  = ($ror_value !== null) && ($ror_value > ($discount_rate * 100));
        $dpr_ok  = ($dpr_value !== null) && ($dpr_value > 0);
        $pir_ok  = ($pir_value !== null) && ($pir_value > 0);

        $positif_count = (int)$npv_ok + (int)$pot_ok + (int)$ror_ok + (int)$dpr_ok + (int)$pir_ok;
        ?>

        <div class="bg-slate-900 border border-slate-800 rounded-2xl overflow-hidden shadow-2xl">

            <!-- Judul Panel -->
            <div class="px-6 py-4 border-b border-slate-800 flex items-center gap-3">
                <div class="w-8 h-8 rounded-xl bg-emerald-500/10 flex items-center justify-center text-emerald-400">
                    <i class="fas fa-chart-pie text-sm"></i>
                </div>
                <div>
                    <h3 class="text-sm font-bold text-white">Laporan Analisis Kelayakan Investasi Migas</h3>
                    <p class="text-[10px] text-slate-500">Berdasarkan 5 Indikator Ekonomi Sektor Hulu (E&P)</p>
                </div>
            </div>

            <div class="p-6 space-y-6">

                <!-- ===== KARTU 5 INDIKATOR EKONOMI ===== -->
                <div>
                    <h4 class="text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-3">Indikator Ekonomi</h4>
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3">

                        <!-- NPV -->
                        <div class="bg-slate-950 border <?php echo $npv_ok ? 'border-emerald-600/40' : 'border-rose-600/40'; ?> rounded-xl p-4 flex flex-col gap-2">
                            <div class="flex items-center justify-between">
                                <span class="text-[9px] font-bold uppercase tracking-widest text-slate-500">NPV @<?php echo ($discount_rate*100); ?>%</span>
                                <span class="text-[9px] font-bold px-1.5 py-0.5 rounded <?php echo $npv_ok ? 'bg-emerald-500/10 text-emerald-400' : 'bg-rose-500/10 text-rose-400'; ?>">
                                    <?php echo $npv_ok ? '✓ POSITIF' : '✗ NEGATIF'; ?>
                                </span>
                            </div>
                            <p class="text-lg font-black <?php echo $npv_ok ? 'text-emerald-400' : 'text-rose-400'; ?>">
                                $<?php echo number_format($total_npv, 2); ?>
                            </p>
                            <p class="text-[10px] text-slate-500 leading-snug">Jumlah aljabar seluruh NCF yang didiskontokan ke nilai sekarang.</p>
                            <div class="mt-auto pt-2 border-t border-slate-800 text-[9px] text-slate-600">Kriteria: NPV > 0 → Layak</div>
                        </div>

                        <!-- POT -->
                        <div class="bg-slate-950 border <?php echo $pot_ok ? 'border-emerald-600/40' : 'border-amber-600/40'; ?> rounded-xl p-4 flex flex-col gap-2">
                            <div class="flex items-center justify-between">
                                <span class="text-[9px] font-bold uppercase tracking-widest text-slate-500">POT / Payback</span>
                                <span class="text-[9px] font-bold px-1.5 py-0.5 rounded <?php echo $pot_ok ? 'bg-emerald-500/10 text-emerald-400' : 'bg-amber-500/10 text-amber-400'; ?>">
                                    <?php echo $pot_ok ? '✓ CEPAT' : '△ LAMBAT'; ?>
                                </span>
                            </div>
                            <p class="text-lg font-black text-white">
                                <?php if ($payback_year !== null): ?>
                                    <?php echo $pot_tahun; ?> Thn <?php echo $pot_bulan; ?> Bln
                                <?php else: ?>
                                    <span class="text-rose-400 text-sm">Tidak Tercapai</span>
                                <?php endif; ?>
                            </p>
                            <p class="text-[10px] text-slate-500 leading-snug">Lama waktu hingga akumulasi NCF menutup total investasi awal.</p>
                            <div class="mt-auto pt-2 border-t border-slate-800 text-[9px] text-slate-600">Kriteria: Makin cepat makin baik</div>
                        </div>

                        <!-- ROR / IRR -->
                        <div class="bg-slate-950 border <?php echo $ror_ok ? 'border-emerald-600/40' : 'border-rose-600/40'; ?> rounded-xl p-4 flex flex-col gap-2">
                            <div class="flex items-center justify-between">
                                <span class="text-[9px] font-bold uppercase tracking-widest text-slate-500">ROR / IRR</span>
                                <span class="text-[9px] font-bold px-1.5 py-0.5 rounded <?php echo $ror_ok ? 'bg-emerald-500/10 text-emerald-400' : 'bg-rose-500/10 text-rose-400'; ?>">
                                    <?php echo $ror_ok ? '✓ DI ATAS HURDLE' : '✗ DI BAWAH HURDLE'; ?>
                                </span>
                            </div>
                            <p class="text-lg font-black <?php echo $ror_ok ? 'text-emerald-400' : 'text-rose-400'; ?>">
                                <?php if ($ror_value !== null): ?>
                                    <?php echo number_format($ror_value, 2); ?>%
                                <?php else: ?>
                                    <span class="text-slate-500 text-sm">N/A</span>
                                <?php endif; ?>
                            </p>
                            <p class="text-[10px] text-slate-500 leading-snug">Discount rate dimana NPV = 0 (titik impas bunga).</p>
                            <div class="mt-auto pt-2 border-t border-slate-800 text-[9px] text-slate-600">Kriteria: ROR > <?php echo ($discount_rate*100); ?>% (hurdle rate)</div>
                        </div>

                        <!-- DPR -->
                        <div class="bg-slate-950 border <?php echo $dpr_ok ? 'border-emerald-600/40' : 'border-rose-600/40'; ?> rounded-xl p-4 flex flex-col gap-2">
                            <div class="flex items-center justify-between">
                                <span class="text-[9px] font-bold uppercase tracking-widest text-slate-500">DPR</span>
                                <span class="text-[9px] font-bold px-1.5 py-0.5 rounded <?php echo $dpr_ok ? 'bg-emerald-500/10 text-emerald-400' : 'bg-rose-500/10 text-rose-400'; ?>">
                                    <?php echo $dpr_ok ? '✓ > 0' : '✗ ≤ 0'; ?>
                                </span>
                            </div>
                            <p class="text-lg font-black <?php echo $dpr_ok ? 'text-emerald-400' : 'text-rose-400'; ?>">
                                <?php echo ($dpr_value !== null) ? number_format($dpr_value, 3) : 'N/A'; ?>
                            </p>
                            <p class="text-[10px] text-slate-500 leading-snug">Discounted Profit to Investment Ratio: NPV dibagi total investasi.</p>
                            <div class="mt-auto pt-2 border-t border-slate-800 text-[9px] text-slate-600">Kriteria: DPR > 0 → Layak</div>
                        </div>

                        <!-- PIR -->
                        <div class="bg-slate-950 border <?php echo $pir_ok ? 'border-emerald-600/40' : 'border-rose-600/40'; ?> rounded-xl p-4 flex flex-col gap-2">
                            <div class="flex items-center justify-between">
                                <span class="text-[9px] font-bold uppercase tracking-widest text-slate-500">PIR</span>
                                <span class="text-[9px] font-bold px-1.5 py-0.5 rounded <?php echo $pir_ok ? 'bg-emerald-500/10 text-emerald-400' : 'bg-rose-500/10 text-rose-400'; ?>">
                                    <?php echo $pir_ok ? '✓ > 0' : '✗ ≤ 0'; ?>
                                </span>
                            </div>
                            <p class="text-lg font-black <?php echo $pir_ok ? 'text-emerald-400' : 'text-rose-400'; ?>">
                                <?php echo ($pir_value !== null) ? number_format($pir_value, 3) : 'N/A'; ?>
                            </p>
                            <p class="text-[10px] text-slate-500 leading-snug">Profit to Investment Ratio: Total NCF undiscounted dibagi investasi.</p>
                            <div class="mt-auto pt-2 border-t border-slate-800 text-[9px] text-slate-600">Kriteria: PIR > 0 → Layak</div>
                        </div>

                    </div>
                </div>

                <!-- ==<!-- ===== RINGKASAN DETAIL TIAP INDIKATOR (Accordion) ===== -->
<div>
    <h4 class="text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-3">Evaluasi Teknis Finansial per Indikator</h4>
    <div class="space-y-1.5" id="accordion-indikator">

        <?php
        $indicators_detail = [
            [
                'label' => 'NPV @' . ($discount_rate*100) . '%',
                'icon' => 'fa-chart-line',
                'ok' => $npv_ok,
                'badge_ok' => '✓ POSITIF',
                'badge_fail' => '✗ NEGATIF',
                'title_text' => 'Net Present Value (NPV @' . ($discount_rate*100) . '%)',
                'body' => $npv_ok
                    ? 'Proyek menghasilkan NPV sebesar <strong class="text-emerald-400">$' . number_format($total_npv, 2) . '</strong>. NPV &gt; 0 menunjukkan bahwa arus pendapatan masa depan — setelah dipotong pajak dan didiskontokan — mampu menutup seluruh pengeluaran kapital awal <em>dan</em> menghasilkan keuntungan bersih di atas hurdle rate ' . ($discount_rate*100) . '%. Proyek <strong class="text-emerald-400">direkomendasikan dari perspektif NPV</strong>.'
                    : 'NPV bernilai <strong class="text-rose-400">$' . number_format($total_npv, 2) . '</strong>. NPV &le; 0 berarti pendapatan yang diproyeksikan <strong>tidak cukup</strong> untuk menutup modal awal pada suku bunga ' . ($discount_rate*100) . '%. Proyek <strong class="text-rose-400">tidak direkomendasikan dari perspektif NPV</strong>.',
                'criteria' => 'Kriteria: NPV &gt; 0 → Layak',
            ],
            [
                'label' => 'POT / Payback Period',
                'icon' => 'fa-clock',
                'ok' => $pot_ok,
                'warn' => !$pot_ok && $payback_year !== null,
                'badge_ok' => '✓ CEPAT',
                'badge_fail' => $payback_year !== null ? '△ LAMBAT' : '✗ TIDAK TERCAPAI',
                'title_text' => 'Pay Out Time / Payback Period (POT)',
                'body' => $payback_year !== null
                    ? 'Investasi awal diproyeksikan kembali dalam <strong class="text-white">' . $pot_tahun . ' tahun ' . $pot_bulan . ' bulan</strong> (akumulasi NCF pertama kali positif). ' . ($pot_ok ? 'POT kurang dari 50% umur kontrak (' . floor($N*0.5) . ' tahun) — <strong class="text-emerald-400">tergolong baik</strong>, risiko tidak kembalinya modal relatif rendah.' : 'POT melampaui 50% umur kontrak — <strong class="text-amber-400">perlu perhatian</strong>, sebagian besar periode kontrak habis sebelum balik modal.')
                    : '<strong class="text-rose-400">Proyek tidak mencapai Payback Period</strong> dalam ' . $N . ' tahun umur kontrak. Akumulasi NCF tidak pernah bernilai positif.',
                'criteria' => 'Kriteria: Makin cepat makin baik',
            ],
            [
                'label' => 'ROR / IRR',
                'icon' => 'fa-percent',
                'ok' => $ror_ok,
                'badge_ok' => '✓ DI ATAS HURDLE',
                'badge_fail' => '✗ DI BAWAH HURDLE',
                'title_text' => 'Rate of Return / IRR (ROR)',
                'body' => $ror_value !== null
                    ? 'ROR proyek ini sebesar <strong class="' . ($ror_ok ? 'text-emerald-400' : 'text-rose-400') . '">' . number_format($ror_value, 2) . '%</strong> (titik di mana NPV = 0). ' . ($ror_ok ? 'Karena ROR <strong>&gt; hurdle rate ' . ($discount_rate*100) . '%</strong>, proyek ini <strong class="text-emerald-400">mampu memberikan imbal hasil di atas biaya modal</strong>.' : 'ROR di bawah hurdle rate ' . ($discount_rate*100) . '%, proyek tidak mampu menghasilkan imbal hasil minimal yang disyaratkan. <strong class="text-rose-400">Proyek tidak efisien secara kapital</strong>.')
                    : 'ROR tidak dapat dihitung — seluruh NCF bernilai positif atau proyek tidak menghasilkan arus kas.',
                'criteria' => 'Kriteria: ROR &gt; ' . ($discount_rate*100) . '% (hurdle rate)',
            ],
            [
                'label' => 'DPR',
                'icon' => 'fa-chart-pie',
                'ok' => $dpr_ok,
                'badge_ok' => '✓ &gt; 0',
                'badge_fail' => '✗ ≤ 0',
                'title_text' => 'Discounted Profit to Investment Ratio (DPR)',
                'body' => $dpr_value !== null
                    ? 'DPR = NPV / Investasi = <strong class="' . ($dpr_ok ? 'text-emerald-400' : 'text-rose-400') . '">' . number_format($dpr_value, 3) . '</strong>. ' . ($dpr_ok ? 'Setiap $1 yang diinvestasikan menghasilkan nilai bersih terdiskon sebesar <strong class="text-emerald-400">$' . number_format($dpr_value, 3) . '</strong>. Efisiensi kapital <strong class="text-emerald-400">positif</strong>.' : 'DPR &le; 0 — setiap dollar yang diinvestasikan <strong class="text-rose-400">tidak menghasilkan nilai bersih memadai</strong> setelah diperhitungkan faktor waktu.')
                    : 'DPR tidak dapat dihitung (investasi = 0).',
                'criteria' => 'Kriteria: DPR &gt; 0 → Layak',
            ],
            [
                'label' => 'PIR',
                'icon' => 'fa-coins',
                'ok' => $pir_ok,
                'badge_ok' => '✓ &gt; 0',
                'badge_fail' => '✗ ≤ 0',
                'title_text' => 'Profit to Investment Ratio (PIR)',
                'body' => $pir_value !== null
                    ? 'PIR = Σ NCF Undiscounted / Investasi = <strong class="' . ($pir_ok ? 'text-emerald-400' : 'text-rose-400') . '">' . number_format($pir_value, 3) . '</strong>. ' . ($pir_ok ? 'Total pendapatan bersih proyek mencapai <strong class="text-emerald-400">' . number_format($pir_value, 1) . '× lipat investasi awal</strong>. PIR &gt; 0 mengkonfirmasi proyek menguntungkan secara nominal.' : 'PIR &le; 0 — secara nominal pun total pendapatan proyek <strong class="text-rose-400">belum mampu menutup investasi awal</strong>.')
                    : 'PIR tidak dapat dihitung (investasi = 0).',
                'criteria' => 'Kriteria: PIR &gt; 0 → Layak',
            ],
        ];

        foreach ($indicators_detail as $idx => $ind):
            $is_warn = isset($ind['warn']) && $ind['warn'];
            $icon_bg   = $ind['ok'] ? 'bg-emerald-500/20 text-emerald-400' : ($is_warn ? 'bg-amber-500/20 text-amber-400' : 'bg-rose-500/20 text-rose-400');
            $badge_cls = $ind['ok'] ? 'bg-emerald-500/10 text-emerald-400' : ($is_warn ? 'bg-amber-500/10 text-amber-400' : 'bg-rose-500/10 text-rose-400');
            $badge_txt = $ind['ok'] ? $ind['badge_ok'] : $ind['badge_fail'];
            $acc_id    = 'acc-body-' . $idx;
            $chev_id   = 'chev-' . $idx;
        ?>
        <div class="bg-slate-950/60 border border-slate-800 rounded-xl overflow-hidden">
            <button
                type="button"
                onclick="toggleAcc('<?php echo $acc_id; ?>','<?php echo $chev_id; ?>')"
                class="w-full flex items-center gap-3 px-4 py-3 text-left hover:bg-slate-800/40 transition-colors"
                aria-expanded="false"
            >
                <div class="w-6 h-6 rounded-lg flex-shrink-0 flex items-center justify-center <?php echo $icon_bg; ?>">
                    <i class="fas <?php echo $ind['icon']; ?> text-[10px]"></i>
                </div>
                <span class="flex-1 text-xs font-semibold text-slate-200"><?php echo $ind['label']; ?></span>
                <span class="text-[9px] font-bold px-2 py-0.5 rounded-full <?php echo $badge_cls; ?>"><?php echo $badge_txt; ?></span>
                <i id="<?php echo $chev_id; ?>" class="fas fa-chevron-down text-slate-500 text-[10px] transition-transform duration-200"></i>
            </button>
            <div id="<?php echo $acc_id; ?>" class="hidden px-4 pb-4 pt-1 border-t border-slate-800/60">
                <p class="text-xs text-slate-300 leading-relaxed">
                    <span class="font-bold text-white"><?php echo $ind['title_text']; ?>: </span>
                    <?php echo $ind['body']; ?>
                </p>
                <p class="mt-2 text-[10px] text-slate-600 bg-slate-900 px-3 py-1.5 rounded-lg inline-block"><?php echo $ind['criteria']; ?></p>
            </div>
        </div>
        <?php endforeach; ?>

    </div>
</div>
                <!-- ===== KESIMPULAN AKHIR ===== -->
                <div class="rounded-2xl border-2 <?php echo $is_feasible ? 'border-emerald-500/30 bg-emerald-500/5' : 'border-rose-500/30 bg-rose-500/5'; ?> p-5">
                    <div class="flex flex-col md:flex-row md:items-start gap-4">

                        <!-- Badge Keputusan -->
                        <div class="flex-shrink-0 text-center md:text-left">
                            <div class="inline-flex items-center gap-2 px-4 py-3 rounded-xl <?php echo $is_feasible ? 'bg-emerald-500/15 border border-emerald-500/30' : 'bg-rose-500/15 border border-rose-500/30'; ?>">
                                <i class="fas <?php echo $is_feasible ? 'fa-thumbs-up text-emerald-400' : 'fa-thumbs-down text-rose-400'; ?> text-2xl"></i>
                                <div>
                                    <div class="text-[9px] font-bold uppercase tracking-widest <?php echo $is_feasible ? 'text-emerald-500' : 'text-rose-500'; ?>">Keputusan Investasi</div>
                                    <div class="text-lg font-black <?php echo $is_feasible ? 'text-emerald-400' : 'text-rose-400'; ?>">
                                        <?php echo $is_feasible ? 'LAYAK (FEASIBLE)' : 'TIDAK LAYAK'; ?>
                                    </div>
                                    <div class="text-[10px] text-slate-400 mt-0.5"><?php echo $positif_count; ?>/5 indikator terpenuhi</div>
                                </div>
                            </div>
                        </div>

                        <!-- Narasi Kesimpulan -->
                        <div class="flex-1 text-xs text-slate-300 space-y-2 leading-relaxed">
                            <p class="font-bold text-sm <?php echo $is_feasible ? 'text-emerald-300' : 'text-rose-300'; ?>">
                                Kesimpulan Analisis Kelayakan — <?php echo htmlspecialchars($project['name']); ?>
                            </p>
                            <?php if ($is_feasible): ?>
                                <p>
                                    Berdasarkan analisis keekonomian hulu migas terhadap <?php echo $positif_count; ?> dari 5 indikator standar,
                                    proyek ini <strong class="text-emerald-400">dinyatakan LAYAK untuk dikembangkan</strong>.
                                    Indikator utama NPV @<?php echo ($discount_rate*100); ?>% bernilai positif sebesar <strong>$<?php echo number_format($total_npv, 2); ?></strong>,
                                    yang berarti arus pendapatan bersih dari lapangan ini — setelah diperhitungkan depresiasi,
                                    pajak, dan nilai waktu uang — mampu mengembalikan seluruh investasi awal senilai
                                    <strong>$<?php echo number_format($total_investasi); ?></strong> dan masih menghasilkan surplus bagi investor.
                                </p>
                                <?php if ($payback_year !== null): ?>
                                <p>
                                    Modal diproyeksikan kembali dalam <strong><?php echo $pot_tahun; ?> tahun <?php echo $pot_bulan; ?> bulan</strong>,
                                    <?php if ($ror_value !== null): ?>
                                    dengan ROR sebesar <strong><?php echo number_format($ror_value, 2); ?>%</strong>
                                    yang <?php echo $ror_ok ? 'melampaui' : 'berada di bawah'; ?> hurdle rate <?php echo ($discount_rate*100); ?>%.
                                    <?php endif; ?>
                                    Efisiensi kapital terkonfirmasi melalui DPR = <strong><?php echo ($dpr_value !== null) ? number_format($dpr_value, 3) : 'N/A'; ?></strong>
                                    dan PIR = <strong><?php echo ($pir_value !== null) ? number_format($pir_value, 3) : 'N/A'; ?></strong>.
                                </p>
                                <?php endif; ?>
                                <p class="text-slate-400">
                                    <em>Catatan: Analisis ini didasarkan pada proyeksi deterministik. Disarankan untuk melengkapi dengan analisis sensitivitas (variasi harga minyak dan opex) serta analisis risiko probabilistik sebelum pengambilan keputusan investasi final.</em>
                                </p>
                            <?php else: ?>
                                <p>
                                    Berdasarkan analisis keekonomian hulu migas, hanya <?php echo $positif_count; ?> dari 5 indikator yang memenuhi kriteria kelayakan.
                                    Indikator utama NPV @<?php echo ($discount_rate*100); ?>% bernilai <strong class="text-rose-400">negatif ($<?php echo number_format($total_npv, 2); ?>)</strong>,
                                    yang berarti arus pendapatan yang diproyeksikan tidak cukup untuk menutup investasi awal
                                    <strong>$<?php echo number_format($total_investasi); ?></strong> pada tingkat diskonto <?php echo ($discount_rate*100); ?>%.
                                    Proyek ini <strong class="text-rose-400">tidak direkomendasikan untuk dikembangkan</strong> pada kondisi saat ini.
                                </p>
                                <?php if ($payback_year === null): ?>
                                <p class="text-slate-400">Payback Period tidak tercapai dalam <?php echo $N; ?> tahun umur kontrak — modal tidak kembali.</p>
                                <?php endif; ?>
                                <p class="text-slate-400">
                                    <em>Rekomendasi: Tinjau ulang asumsi harga minyak, opex, dan struktur investasi. Pertimbangkan negosiasi ulang fiscal terms atau penundaan pengembangan hingga kondisi pasar membaik.</em>
                                </p>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Scorecard mini -->
                    <div class="mt-4 pt-4 border-t <?php echo $is_feasible ? 'border-emerald-500/20' : 'border-rose-500/20'; ?> grid grid-cols-5 gap-2 text-center">
                        <?php
                        $indicators = [
                            ['label' => 'NPV', 'ok' => $npv_ok],
                            ['label' => 'POT', 'ok' => $pot_ok],
                            ['label' => 'ROR', 'ok' => $ror_ok],
                            ['label' => 'DPR', 'ok' => $dpr_ok],
                            ['label' => 'PIR', 'ok' => $pir_ok],
                        ];
                        foreach ($indicators as $ind): ?>
                        <div class="<?php echo $ind['ok'] ? 'text-emerald-400' : 'text-rose-500'; ?>">
                            <div class="text-lg font-black"><?php echo $ind['ok'] ? '✓' : '✗'; ?></div>
                            <div class="text-[9px] font-bold uppercase tracking-wider"><?php echo $ind['label']; ?></div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

            </div>
        </div>

    </div><!-- /max-w-7xl -->

    <script>
        document.addEventListener("DOMContentLoaded", function () {
            const ctx = document.getElementById('ncfChart').getContext('2d');
            const sheetData = <?php echo json_encode(array_values($excel_sheet)); ?>;

            const labels = sheetData.map(item => 'Th-' + item.year);
            const ncfValues = sheetData.map(item => item.net_cashflow);

            // Hitung NCF kumulatif untuk garis trend
            let cumulative = 0;
            const cumulativeValues = ncfValues.map(v => { cumulative += v; return cumulative; });

            new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [
                        {
                            label: 'Net Cashflow ($M)',
                            data: ncfValues,
                            backgroundColor: ncfValues.map(v => v >= 0 ? 'rgba(16, 185, 129, 0.4)' : 'rgba(244, 63, 94, 0.4)'),
                            borderColor: ncfValues.map(v => v >= 0 ? '#10b981' : '#f43f5e'),
                            borderWidth: 1.5,
                            borderRadius: 4,
                            yAxisID: 'y'
                        },
                        {
                            label: 'Kumulatif NCF ($M)',
                            data: cumulativeValues,
                            type: 'line',
                            borderColor: '#f59e0b',
                            backgroundColor: 'transparent',
                            borderWidth: 2,
                            pointRadius: 3,
                            pointBackgroundColor: '#f59e0b',
                            tension: 0.3,
                            yAxisID: 'y'
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: { grid: { color: '#1e293b' }, ticks: { color: '#94a3b8' } },
                        x: { grid: { display: false }, ticks: { color: '#94a3b8' } }
                    },
                    plugins: {
                        legend: { display: true, labels: { color: '#94a3b8', font: { size: 10 } } }
                    }
                }
            });
        });
        function toggleAcc(bodyId, chevId) {
    const body = document.getElementById(bodyId);
    const chev = document.getElementById(chevId);
    const isHidden = body.classList.contains('hidden');
    // Tutup semua
    document.querySelectorAll('[id^="acc-body-"]').forEach(el => el.classList.add('hidden'));
    document.querySelectorAll('[id^="chev-"]').forEach(el => el.style.transform = '');
    // Buka yang diklik (toggle)
    if (isHidden) {
        body.classList.remove('hidden');
        chev.style.transform = 'rotate(180deg)';
    }
}
    </script>
</body>
</html>