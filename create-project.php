<?php
// create-project.php
session_start();
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: login.php");
    exit;
}
$user_id = $_SESSION['user_id'];
require_once "config.php";

$name = $site_manager = $invest_capital = $invest_noncapital = $tax = $investment_years = $depreciation_method = $decline_rate = "";
$validation_errors = [];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = trim($_POST['name']);
    $site_manager = trim($_POST['site_manager']);
    $invest_capital = trim($_POST['invest_capital']);
    $invest_noncapital = trim($_POST['invest_noncapital']);
    $tax = trim($_POST['tax']);
    $investment_years = trim($_POST['investment_years']);
    $depreciation_method = trim($_POST['depreciation_method']);
    $decline_rate = trim($_POST['decline_rate']);

    if (empty($name)) $validation_errors['name'] = "Nama proyek wajib diisi.";
    if (empty($site_manager)) $validation_errors['site_manager'] = "Nama manajer wajib diisi.";
    if (!is_numeric($invest_capital)) $validation_errors['invest_capital'] = "Nilai CAPEX harus angka.";
    if (!is_numeric($invest_noncapital)) $validation_errors['invest_noncapital'] = "Nilai Non-CAPEX harus angka.";
    if (!is_numeric($tax)) $validation_errors['tax'] = "Pajak harus angka.";
    if (!is_numeric($investment_years)) $validation_errors['investment_years'] = "Jumlah tahun investasi harus angka.";
    if (empty($depreciation_method)) $validation_errors['depreciation_method'] = "Pilih metode depresiasi.";
    if (!is_numeric($decline_rate)) $validation_errors['decline_rate'] = "Decline rate harus angka.";

    if (empty($validation_errors)) {
        $sql = "INSERT INTO projects (name, site_manager, invest_capital, invest_noncapital, tax, investment_years, depreciation, depreciation_method, decline_rate, user_id, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, 0, ?, ?, ?, NOW(), NOW())";
        if ($stmt = $mysqli->prepare($sql)) {
            $stmt->bind_param("ssddddssi", $name, $site_manager, $invest_capital, $invest_noncapital, $tax, $investment_years, $depreciation_method, $decline_rate, $user_id);
            if ($stmt->execute()) {
                header("location: home.php");
                exit;
            }
            $stmt->close();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Inisiasi Lapangan Baru - Petromine Analytics</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>body { font-family: 'Plus Jakarta Sans', sans-serif; }</style>
</head>
<body class="bg-slate-950 text-slate-100 min-h-screen p-6">
    <div class="max-w-2xl mx-auto bg-slate-900 border border-slate-800 rounded-2xl p-8 shadow-2xl">
        <h2 class="text-xl font-bold text-white mb-6">Inisiasi Parameter Lapangan Proyek</h2>
        <form action="create-project.php" method="POST" class="space-y-4">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-bold text-slate-400 mb-2">Nama Lapangan / Struktur</label>
                    <input type="text" name="name" value="<?php echo htmlspecialchars($name); ?>" class="w-full p-3 bg-slate-950 border border-slate-800 rounded-xl text-sm text-white focus:outline-none">
                    <p class="text-rose-400 text-xs mt-1"><?php echo $validation_errors['name'] ?? ''; ?></p>
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-400 mb-2">Manajer Lapangan</label>
                    <input type="text" name="site_manager" value="<?php echo htmlspecialchars($site_manager); ?>" class="w-full p-3 bg-slate-950 border border-slate-800 rounded-xl text-sm text-white focus:outline-none">
                    <p class="text-rose-400 text-xs mt-1"><?php echo $validation_errors['site_manager'] ?? ''; ?></p>
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-400 mb-2">Capital Investment (CAPEX - USD)</label>
                    <input type="number" name="invest_capital" value="<?php echo htmlspecialchars($invest_capital); ?>" class="w-full p-3 bg-slate-950 border border-slate-800 rounded-xl text-sm text-white focus:outline-none">
                    <p class="text-rose-400 text-xs mt-1"><?php echo $validation_errors['invest_capital'] ?? ''; ?></p>
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-400 mb-2">Non-Capital Investment (USD)</label>
                    <input type="number" name="invest_noncapital" value="<?php echo htmlspecialchars($invest_noncapital); ?>" class="w-full p-3 bg-slate-950 border border-slate-800 rounded-xl text-sm text-white focus:outline-none">
                    <p class="text-rose-400 text-xs mt-1"><?php echo $validation_errors['invest_noncapital'] ?? ''; ?></p>
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-400 mb-2">Pajak Korporasi (%)</label>
                    <input type="number" step="any" name="tax" value="<?php echo htmlspecialchars($tax); ?>" class="w-full p-3 bg-slate-950 border border-slate-800 rounded-xl text-sm text-white focus:outline-none">
                    <p class="text-rose-400 text-xs mt-1"><?php echo $validation_errors['tax'] ?? ''; ?></p>
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-400 mb-2">Durasi Proyek (Tahun)</label>
                    <input type="number" name="investment_years" value="<?php echo htmlspecialchars($investment_years); ?>" class="w-full p-3 bg-slate-950 border border-slate-800 rounded-xl text-sm text-white focus:outline-none">
                    <p class="text-rose-400 text-xs mt-1"><?php echo $validation_errors['investment_years'] ?? ''; ?></p>
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-400 mb-2">Metode Depresiasi</label>
                    <select name="depreciation_method" class="w-full p-3 bg-slate-950 border border-slate-800 rounded-xl text-sm text-white focus:outline-none">
                        <option value="Straight Line">Straight Line Method</option>
                        <option value="Double Declining">Double Declining Balance Method</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-400 mb-2">Decline Rate (% / Tahun)</label>
                    <input type="number" step="any" name="decline_rate" value="<?php echo htmlspecialchars($decline_rate); ?>" class="w-full p-3 bg-slate-950 border border-slate-800 rounded-xl text-sm text-white focus:outline-none">
                    <p class="text-rose-400 text-xs mt-1"><?php echo $validation_errors['decline_rate'] ?? ''; ?></p>
                </div>
            </div>
            <div class="flex justify-end gap-3 pt-4 border-t border-slate-800">
                <a href="home.php" class="bg-slate-800 text-slate-300 py-2.5 px-5 rounded-xl text-xs font-bold">Batal</a>
                <button type="submit" class="bg-emerald-500 text-slate-950 py-2.5 px-5 rounded-xl text-xs font-bold shadow-md shadow-emerald-500/10">Inisiasi Proyek</button>
            </div>
        </form>
    </div>
</body>
</html>