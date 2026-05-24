<?php
// edit-cashflow.php
session_start();
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) { header("location: login.php"); exit; }
$user_id = $_SESSION['user_id'];
require_once "config.php";

$cashflow_id = isset($_GET['id']) ? trim($_GET['id']) : null;
if (empty($cashflow_id) || !ctype_digit($cashflow_id)) { header("location: home.php"); exit; }

$cf = null;
$sql_cf = "SELECT * FROM cashflows WHERE id = ?";
if ($stmt_cf = $mysqli->prepare($sql_cf)) {
    $stmt_cf->bind_param("i", $cashflow_id);
    if ($stmt_cf->execute()) { $cf = $stmt_cf->get_result()->fetch_assoc(); }
    $stmt_cf->close();
}
if(!$cf) { header("location: home.php"); exit; }

$project_id = $cf['project_id'];
$project_details = null;
$sql_project = "SELECT name, tax, invest_capital, investment_years, depreciation_method FROM projects WHERE id = ? AND user_id = ?";
if ($stmt_project = $mysqli->prepare($sql_project)) {
    $stmt_project->bind_param("ii", $project_id, $user_id);
    if ($stmt_project->execute()) { $project_details = $stmt_project->get_result()->fetch_assoc(); }
    $stmt_project->close();
}
if(!$project_details) { header("location: home.php"); exit; }

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $production = trim($_POST['production']);
    $price_per_barrel = trim($_POST['price_per_barrel']);
    $opex = trim($_POST['opex']);

    $income = $production * $price_per_barrel;
    if($project_details['depreciation_method'] == "Straight Line") {
        $depreciation = $project_details['invest_capital'] / $project_details['investment_years'];
    } else {
        $depreciation = (2 / $project_details['investment_years']) * $project_details['invest_capital'];
    }

    $taxable_income = $income - $opex - $depreciation;
    if($taxable_income < 0) $taxable_income = 0;
    $tax_paid = $taxable_income * ($project_details['tax'] / 100);
    $net_cashflow = $income - $opex - $tax_paid;

    $sql_update = "UPDATE cashflows SET production = ?, income = ?, opex = ?, taxable_income = ?, net_cashflow = ?, updated_at = NOW() WHERE id = ?";
    if ($stmt_up = $mysqli->prepare($sql_update)) {
        $stmt_up->bind_param("iddddi", $production, $income, $opex, $taxable_income, $net_cashflow, $cashflow_id);
        if ($stmt_up->execute()) {
            header("location: project-details.php?id=" . $project_id);
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Edit Cashflow - Petromine Analytics</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>
<body class="bg-slate-950 text-slate-100 min-h-screen p-6">
    <div class="max-w-xl mx-auto bg-slate-900 border border-slate-800 rounded-2xl p-8">
        <h2 class="text-base font-bold mb-4">Edit Data Cashflow Tahun Ke-<?php echo $cf['year']; ?></h2>
        <form action="" method="POST" class="space-y-4">
            <div>
                <label class="block text-xs font-bold text-slate-400 mb-2">Volume Produksi (Mbbl)</label>
                <input type="number" name="production" value="<?php echo htmlspecialchars($cf['production']); ?>" class="w-full p-3 bg-slate-950 border border-slate-800 rounded-xl text-sm text-white focus:outline-none">
            </div>
            <div>
                <label class="block text-xs font-bold text-slate-400 mb-2"> Harga jual per Barrel (USD)</label>
                <input type="number" step="any" name="price_per_barrel" value="<?php echo htmlspecialchars($cf['income'] / ($cf['production'] ?: 1)); ?>" class="w-full p-3 bg-slate-950 border border-slate-800 rounded-xl text-sm text-white focus:outline-none">
            </div>
            <div>
                <label class="block text-xs font-bold text-slate-400 mb-2">OPEX (USD)</label>
                <input type="number" name="opex" value="<?php echo htmlspecialchars($cf['opex']); ?>" class="w-full p-3 bg-slate-950 border border-slate-800 rounded-xl text-sm text-white focus:outline-none">
            </div>
            <div class="flex justify-end gap-3">
                <a href="project-details.php?id=<?php echo $project_id; ?>" class="bg-slate-800 text-slate-300 py-2 px-4 rounded-xl text-xs font-bold">Batal</a>
                <button type="submit" class="bg-emerald-500 text-slate-950 py-2 px-4 rounded-xl text-xs font-bold">Terapkan</button>
            </div>
        </form>
    </div>
</body>
</html>