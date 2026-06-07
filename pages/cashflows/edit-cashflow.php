<?php
// edit-cashflow.php
require_once "../../includes/auth.php";
$user_id = $_SESSION['user_id'];
require_once "../../config/config.php";

$cashflow_id = isset($_GET['id']) ? trim($_GET['id']) : null;
if (empty($cashflow_id) || !ctype_digit($cashflow_id)) { header("location: ../projects/home.php"); exit; }

$cf = null;
$sql_cf = "SELECT * FROM cashflows WHERE id = ?";
if ($stmt_cf = $mysqli->prepare($sql_cf)) {
    $stmt_cf->bind_param("i", $cashflow_id);
    if ($stmt_cf->execute()) { $cf = $stmt_cf->get_result()->fetch_assoc(); }
    $stmt_cf->close();
}
if (!$cf) { header("location: ../projects/home.php"); exit; }

$project_id = $cf['project_id'];
$project_details = null;
$sql_project = "SELECT name, tax, invest_capital, investment_years, depreciation_method FROM projects WHERE id = ? AND user_id = ?";
if ($stmt_project = $mysqli->prepare($sql_project)) {
    $stmt_project->bind_param("ii", $project_id, $user_id);
    if ($stmt_project->execute()) { $project_details = $stmt_project->get_result()->fetch_assoc(); }
    $stmt_project->close();
}
if (!$project_details) { header("location: ../projects/home.php"); exit; }

// Hitung harga per barel dari data yang tersimpan
$price_per_barrel_value = ($cf['production'] > 0) ? ($cf['income'] / $cf['production']) : 0;

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $production       = trim($_POST['production']);
    $price_per_barrel = trim($_POST['price_per_barrel']);
    $opex             = trim($_POST['opex']);
    // Tahun diambil langsung dari database, bukan dari POST
    $year_fixed       = $cf['year'];

    $income = $production * $price_per_barrel;

    if ($project_details['depreciation_method'] == "Straight Line") {
        $depreciation = $project_details['invest_capital'] / $project_details['investment_years'];
    } else {
        $depreciation = (2 / $project_details['investment_years']) * $project_details['invest_capital'];
    }

    $taxable_income = $income - $opex - $depreciation;
    if ($taxable_income < 0) $taxable_income = 0;

    $tax_paid     = $taxable_income * ($project_details['tax'] / 100);
    $net_cashflow = $income - $opex - $tax_paid;

    $sql_update = "UPDATE cashflows SET production = ?, income = ?, opex = ?, taxable_income = ?, net_cashflow = ?, updated_at = NOW() WHERE id = ?";
    if ($stmt_up = $mysqli->prepare($sql_update)) {
        $stmt_up->bind_param("iddddi", $production, $income, $opex, $taxable_income, $net_cashflow, $cashflow_id);
        if ($stmt_up->execute()) {
            $_SESSION["toast_success"] = "Data cashflow tahun " . htmlspecialchars($year_fixed) . " berhasil diperbarui!";
            header("location: ../projects/project-details.php?id=" . $project_id);
            exit;
        } else {
            $_SESSION["toast_error"] = "Gagal memperbarui data cashflow.";
        }
        $stmt_up->close();
    }
}
?>
<?php
$base_path = "../../";
$page_title = "Edit Cashflow - " . htmlspecialchars($project_details['name'] ?? '');
require_once "../../includes/header.php";
?>
<body class="bg-slate-950 text-slate-100 min-h-screen flex">
    <?php require_once "../../includes/sidebar.php"; ?>
    <div class="flex-1 flex flex-col min-w-0">
        <?php 
        $breadcrumb_items = [
            ['label' => $project_details['name'], 'url' => '../projects/project-details.php?id=' . $project_id],
            ['label' => 'Edit Cashflow']
        ];
        require_once "../../includes/topbar.php"; 
        ?>
        <main class="max-w-xl w-full mx-auto px-6 py-10 flex-grow flex items-center justify-center">
            <div class="w-full max-w-xl bg-slate-900 border border-slate-800 rounded-2xl p-8 shadow-2xl">

        <!-- Header -->
        <div class="mb-6">
            <h2 class="text-lg font-bold text-white">Edit Cashflow</h2>
            <p class="text-xs text-slate-400 mt-1">
                Proyek: <span class="text-emerald-400 font-semibold"><?php echo htmlspecialchars($project_details['name']); ?></span>
            </p>
        </div>

        <!-- Info Tahun (read-only, tidak bisa diubah) -->
        <div class="flex items-center gap-3 bg-slate-950/60 border border-slate-700 rounded-xl px-4 py-3 mb-6">
            <div class="w-8 h-8 rounded-lg bg-emerald-500/10 flex items-center justify-center flex-shrink-0">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                </svg>
            </div>
            <div>
                <p class="text-[10px] text-slate-500 uppercase tracking-wider font-bold">Periode Data</p>
                <p class="text-sm font-bold text-white">Tahun Ke-<?php echo $cf['year']; ?></p>
            </div>
        </div>

        <!-- Form -->
        <form action="" method="POST" class="space-y-4">

            <div>
                <label class="block text-xs font-bold text-slate-400 mb-2">
                    Volume Produksi Minyak Mentah <span class="text-slate-600 font-normal">(BBL / Tahun)</span>
                </label>
                <input
                    type="number"
                    name="production"
                    value="<?php echo htmlspecialchars($cf['production']); ?>"
                    placeholder="Contoh: 150000"
                    class="w-full p-3 bg-slate-950 border border-slate-800 rounded-xl text-sm text-white focus:outline-none focus:border-emerald-500/50 transition-colors"
                    required
                >
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-400 mb-2">
                    Harga Minyak Mentah <span class="text-slate-600 font-normal">(USD / BBL)</span>
                </label>
                <input
                    type="number"
                    step="any"
                    name="price_per_barrel"
                    value="<?php echo htmlspecialchars(number_format($price_per_barrel_value, 2, '.', '')); ?>"
                    placeholder="Contoh: 75.50"
                    class="w-full p-3 bg-slate-950 border border-slate-800 rounded-xl text-sm text-white focus:outline-none focus:border-emerald-500/50 transition-colors"
                    required
                >
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-400 mb-2">
                    Biaya Operasional Lapangan <span class="text-slate-600 font-normal">(OPEX – USD)</span>
                </label>
                <input
                    type="number"
                    name="opex"
                    value="<?php echo htmlspecialchars($cf['opex']); ?>"
                    placeholder="Contoh: 2000000"
                    class="w-full p-3 bg-slate-950 border border-slate-800 rounded-xl text-sm text-white focus:outline-none focus:border-emerald-500/50 transition-colors"
                    required
                >
            </div>

            <!-- Info kalkulasi otomatis -->
            <div class="bg-slate-950/40 border border-slate-800/60 rounded-xl px-4 py-3 text-[11px] text-slate-500 leading-relaxed">
                Depresiasi, taxable income, pajak, dan NCF akan dihitung ulang otomatis sesuai parameter proyek
                (<span class="text-slate-400"><?php echo htmlspecialchars($project_details['depreciation_method']); ?></span>,
                pajak <?php echo $project_details['tax']; ?>%).
            </div>

            <div class="flex justify-end gap-3 pt-4 border-t border-slate-800">
                <a href="../projects/project-details.php?id=<?php echo $project_id; ?>" class="bg-slate-800 hover:bg-slate-700 text-slate-300 py-2.5 px-5 rounded-xl text-xs font-bold transition-colors">
                    Batal
                </a>
                <button type="submit" class="bg-emerald-500 hover:bg-emerald-600 text-slate-950 py-2.5 px-5 rounded-xl text-xs font-bold transition-colors">
                    Terapkan Perubahan
                </button>
            </div>
        </form>

            </div>
        </main>
    </div>
</body>
</html>