<?php
// edit-project.php
require_once "../../includes/auth.php";
$user_id = $_SESSION['user_id'];
require_once "../../config/config.php";

$project_id = isset($_GET['id']) ? trim($_GET['id']) : null;
if (empty($project_id) || !ctype_digit($project_id)) { header("location: home.php"); exit; }

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = trim($_POST['name']);
    $site_manager = trim($_POST['site_manager']);
    $invest_capital = trim($_POST['invest_capital']);
    $invest_noncapital = trim($_POST['invest_noncapital']);
    $tax = trim($_POST['tax']);
    $investment_years = trim($_POST['investment_years']);
    $depreciation_method = trim($_POST['depreciation_method']);
    $decline_rate = trim($_POST['decline_rate']);

    $sql = "UPDATE projects SET name=?, site_manager=?, invest_capital=?, invest_noncapital=?, tax=?, investment_years=?, depreciation_method=?, decline_rate=?, updated_at=NOW() WHERE id=? AND user_id=?";
    if ($stmt = $mysqli->prepare($sql)) {
        $stmt->bind_param("ssddddssii", $name, $site_manager, $invest_capital, $invest_noncapital, $tax, $investment_years, $depreciation_method, $decline_rate, $project_id, $user_id);
        if ($stmt->execute()) { 
            $_SESSION["toast_success"] = "Parameter proyek '" . htmlspecialchars($name) . "' berhasil diperbarui!";
            header("location: project-details.php?id=" . $project_id); 
            exit; 
        } else {
            $_SESSION["toast_error"] = "Gagal memperbarui parameter proyek.";
        }
    }
}

$project = null;
$sql = "SELECT * FROM projects WHERE id = ? AND user_id = ?";
if ($stmt = $mysqli->prepare($sql)) {
    $stmt->bind_param("ii", $project_id, $user_id);
    if ($stmt->execute()) { $project = $stmt->get_result()->fetch_assoc(); }
    $stmt->close();
}
if(!$project) { header("location: home.php"); exit; }
?>
<?php
$base_path = "../../";
$page_title = "Konfigurasi Lapangan - " . htmlspecialchars($project['name'] ?? '');
require_once "../../includes/header.php";
?>
<body class="bg-slate-950 text-slate-100 min-h-screen flex">
    <?php require_once "../../includes/sidebar.php"; ?>
    <div class="flex-1 flex flex-col min-w-0">
        <?php 
        $breadcrumb_items = [
            ['label' => $project['name'], 'url' => 'project-details.php?id=' . $project_id],
            ['label' => 'Edit Parameter']
        ];
        require_once "../../includes/topbar.php"; 
        ?>
        <main class="max-w-4xl w-full mx-auto px-6 py-10 flex-grow flex items-center justify-center">
            <div class="w-full max-w-2xl bg-slate-900 border border-slate-800 rounded-2xl p-8">
        <h2 class="text-lg font-bold mb-6">Ubah Parameter Lapangan Proyek</h2>
        <form action="" method="POST" class="space-y-4">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-bold text-slate-400 mb-2">Nama Lapangan</label>
                    <input type="text" name="name" value="<?php echo htmlspecialchars($project['name']); ?>" class="w-full p-3 bg-slate-950 border border-slate-800 rounded-xl text-sm text-white focus:outline-none">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-400 mb-2">Manajer</label>
                    <input type="text" name="site_manager" value="<?php echo htmlspecialchars($project['site_manager']); ?>" class="w-full p-3 bg-slate-950 border border-slate-800 rounded-xl text-sm text-white focus:outline-none">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-400 mb-2">CAPEX (USD)</label>
                    <input type="number" name="invest_capital" value="<?php echo htmlspecialchars($project['invest_capital']); ?>" class="w-full p-3 bg-slate-950 border border-slate-800 rounded-xl text-sm text-white focus:outline-none">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-400 mb-2">Non-CAPEX (USD)</label>
                    <input type="number" name="invest_noncapital" value="<?php echo htmlspecialchars($project['invest_noncapital']); ?>" class="w-full p-3 bg-slate-950 border border-slate-800 rounded-xl text-sm text-white focus:outline-none">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-400 mb-2">Pajak Korporasi (%)</label>
                    <input type="number" step="any" name="tax" value="<?php echo htmlspecialchars($project['tax']); ?>" class="w-full p-3 bg-slate-950 border border-slate-800 rounded-xl text-sm text-white focus:outline-none">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-400 mb-2">Durasi Kontrak (Tahun)</label>
                    <input type="number" name="investment_years" value="<?php echo htmlspecialchars($project['investment_years']); ?>" class="w-full p-3 bg-slate-950 border border-slate-800 rounded-xl text-sm text-white focus:outline-none">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-400 mb-2">Metode Depresiasi</label>
                    <select name="depreciation_method" class="w-full p-3 bg-slate-950 border border-slate-800 rounded-xl text-sm text-white focus:outline-none">
                        <option value="Straight Line" <?php echo ($project['depreciation_method'] == 'Straight Line') ? 'selected' : ''; ?>>Straight Line Method</option>
                        <option value="Double Declining" <?php echo ($project['depreciation_method'] == 'Double Declining') ? 'selected' : ''; ?>>Double Declining Balance Method</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-400 mb-2">Decline Rate (%)</label>
                    <input type="number" step="any" name="decline_rate" value="<?php echo htmlspecialchars($project['decline_rate']); ?>" class="w-full p-3 bg-slate-950 border border-slate-800 rounded-xl text-sm text-white focus:outline-none">
                </div>
            </div>
            <div class="flex justify-between items-center pt-4 border-t border-slate-800">
                <a href="delete-project.php?id=<?php echo $project_id; ?>" onclick="return confirm('Hapus seluruh berkas proyek secara permanen?')" class="text-xs text-rose-400 font-bold hover:underline"><i class="fas fa-trash-alt mr-1"></i> Hapus Seluruh Proyek</a>
                <div class="flex gap-2">
                    <a href="project-details.php?id=<?php echo $project_id; ?>" class="bg-slate-800 text-slate-300 py-2 px-4 rounded-xl text-xs font-bold">Batal</a>
                    <button type="submit" class="bg-emerald-500 text-slate-950 py-2 px-4 rounded-xl text-xs font-bold">Simpan Perubahan</button>
                </div>
            </div>
        </form>
            </div>
        </main>
    </div>
</body>
</html>