<?php
// add-cashflow.php
require_once "../../includes/auth.php";
$user_id = $_SESSION['user_id'];
require_once "../../config/config.php";

$project_id = isset($_GET['project_id']) ? trim($_GET['project_id']) : null;
if (empty($project_id) || !ctype_digit($project_id)) { header("location: ../projects/home.php"); exit; }

$project_details = null;
$sql_project = "SELECT name, tax, invest_capital, investment_years, depreciation_method FROM projects WHERE id = ? AND user_id = ?";
if ($stmt_project = $mysqli->prepare($sql_project)) {
    $stmt_project->bind_param("ii", $project_id, $user_id);
    if ($stmt_project->execute()) { $project_details = $stmt_project->get_result()->fetch_assoc(); }
    $stmt_project->close();
}
if(!$project_details) { header("location: ../projects/home.php"); exit; }

$year = isset($_GET['year']) ? trim($_GET['year']) : "";
$production = $price_per_barrel = $opex = "";
$validation_errors = [];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $year = trim($_POST['year']);
    $production = trim($_POST['production']);
    $price_per_barrel = trim($_POST['price_per_barrel']);
    $opex = trim($_POST['opex']);

    if(empty($year)) $validation_errors['year'] = "Tahun wajib diisi.";
    if(empty($production)) $validation_errors['production'] = "Volume produksi wajib diisi.";
    if(empty($price_per_barrel)) $validation_errors['price_per_barrel'] = "Harga per barel wajib diisi.";
    if(empty($opex)) $validation_errors['opex'] = "OPEX wajib diisi.";

    if (empty($validation_errors)) {
        // Rumus Keekonomian Migas Terintegrasi
        $income = $production * $price_per_barrel;
        
        // Kalkulasi Depresiasi Otomatis Berdasarkan Pilihan Konfigurasi Proyek
        if($project_details['depreciation_method'] == "Straight Line") {
            $depreciation = $project_details['invest_capital'] / $project_details['investment_years'];
        } else {
            // Double Declining Balance Method (2 / N * Nilai Buku Sisa)
            $depreciation = (2 / $project_details['investment_years']) * $project_details['invest_capital'];
        }

        $taxable_income = $income - $opex - $depreciation;
        if($taxable_income < 0) $taxable_income = 0; // Loss forward protection

        $tax_paid = $taxable_income * ($project_details['tax'] / 100);
        $net_cashflow = $income - $opex - $tax_paid;

        $sql = "INSERT INTO cashflows (year, production, income, opex, taxable_income, net_cashflow, project_id, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), NOW())";
        if ($stmt = $mysqli->prepare($sql)) {
            $stmt->bind_param("iiddddi", $year, $production, $income, $opex, $taxable_income, $net_cashflow, $project_id);
            if ($stmt->execute()) {
                header("location: ../projects/project-details.php?id=" . $project_id);
                exit;
            }
            $stmt->close();
        }
    }
}
?>
<?php
$base_path = "../../";
$page_title = "Input Cashflow Tahunan - " . htmlspecialchars($project_details['name'] ?? '');
require_once "../../includes/header.php";
?>
<body class="bg-slate-950 text-slate-100 min-h-screen flex items-center justify-center p-6">
    <div class="w-full max-w-xl bg-slate-900 border border-slate-800 rounded-2xl p-8 shadow-2xl">
        <h2 class="text-lg font-bold text-white mb-2">Cashflow</h2>
        <p class="text-xs text-slate-400 mb-6">Proyek: <span class="text-emerald-400 font-semibold"><?php echo htmlspecialchars($project_details['name']); ?></span></p>
        
        <form action="" method="POST" class="space-y-4">
            <div>
                <label class="block text-xs font-bold text-slate-400 mb-2">Tahun Ke-</label>
                <input type="number" name="year" placeholder="Contoh: 1" value="<?php echo htmlspecialchars($year); ?>" class="w-full p-3 bg-slate-950 border border-slate-800 rounded-xl text-sm text-white focus:outline-none">
            </div>
            <div>
                <label class="block text-xs font-bold text-slate-400 mb-2">Volume Produksi Minyak Mentah (BBL / Tahun)</label>
                <input type="number" name="production" placeholder="Contoh: 150000" value="<?php echo htmlspecialchars($production); ?>" class="w-full p-3 bg-slate-950 border border-slate-800 rounded-xl text-sm text-white focus:outline-none">
            </div>
            <div>
                <label class="block text-xs font-bold text-slate-400 mb-2">Harga Minyak Mentah (USD / BBL)</label>
                <input type="number" step="any" name="price_per_barrel" placeholder="Contoh: 75.50" value="<?php echo htmlspecialchars($price_per_barrel); ?>" class="w-full p-3 bg-slate-950 border border-slate-800 rounded-xl text-sm text-white focus:outline-none">
            </div>
            <div>
                <label class="block text-xs font-bold text-slate-400 mb-2">Biaya Operasional Lapangan (OPEX - USD)</label>
                <input type="number" name="opex" placeholder="Contoh: 2000000" value="<?php echo htmlspecialchars($opex); ?>" class="w-full p-3 bg-slate-950 border border-slate-800 rounded-xl text-sm text-white focus:outline-none">
            </div>
            <div class="flex justify-end gap-3 pt-4 border-t border-slate-800">
                <a href="../projects/project-details.php?id=<?php echo $project_id; ?>" class="bg-slate-800 text-slate-300 py-2.5 px-5 rounded-xl text-xs font-bold">Batal</a>
                <button type="submit" class="bg-emerald-500 text-slate-950 py-2.5 px-5 rounded-xl text-xs font-bold">Simpan & Hitung</button>
            </div>
        </form>
    </div>
</body>
</html>