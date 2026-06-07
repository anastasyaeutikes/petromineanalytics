<?php
// edit-project.php
require_once "../../includes/auth.php";
$user_id = $_SESSION['user_id'];
require_once "../../config/config.php";

$project_id = isset($_GET['id']) ? trim($_GET['id']) : null;
if (empty($project_id) || !ctype_digit($project_id)) { header("location: home.php"); exit; }

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
    if (!is_numeric($invest_capital) || $invest_capital <= 0) $validation_errors['invest_capital'] = "Nilai CAPEX harus angka positif lebih besar dari 0.";
    if (!is_numeric($invest_noncapital) || $invest_noncapital < 0) $validation_errors['invest_noncapital'] = "Nilai Non-CAPEX harus angka positif minimal 0.";
    if (!is_numeric($tax) || $tax < 0 || $tax > 100) $validation_errors['tax'] = "Pajak harus berupa angka antara 0% sampai 100%.";
    if (!is_numeric($investment_years) || $investment_years <= 0 || intval($investment_years) != $investment_years) $validation_errors['investment_years'] = "Durasi proyek harus berupa bilangan bulat positif lebih besar dari 0.";
    if (empty($depreciation_method)) $validation_errors['depreciation_method'] = "Pilih metode depresiasi.";
    if (!is_numeric($decline_rate) || $decline_rate < 0 || $decline_rate > 100) $validation_errors['decline_rate'] = "Decline rate harus berupa angka antara 0% sampai 100%.";

    if (empty($validation_errors)) {
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
    } else {
        $_SESSION["toast_error"] = "Gagal menyimpan perubahan. Harap periksa input Anda.";
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
                    <label class="block text-xs font-bold text-slate-400 mb-2">Nama Lapangan / Struktur</label>
                    <input type="text" name="name" value="<?php echo htmlspecialchars($project['name']); ?>" class="w-full p-3 bg-slate-950 border border-slate-800 rounded-xl text-sm text-white focus:outline-none focus:border-emerald-500/50 transition-colors">
                    <p class="text-rose-400 text-xs mt-1"><?php echo $validation_errors['name'] ?? ''; ?></p>
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-400 mb-2">Manajer Lapangan</label>
                    <input type="text" name="site_manager" value="<?php echo htmlspecialchars($project['site_manager']); ?>" class="w-full p-3 bg-slate-950 border border-slate-800 rounded-xl text-sm text-white focus:outline-none focus:border-emerald-500/50 transition-colors">
                    <p class="text-rose-400 text-xs mt-1"><?php echo $validation_errors['site_manager'] ?? ''; ?></p>
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-400 mb-2">Capital Investment (CAPEX - USD)</label>
                    <input type="number" id="invest_capital" name="invest_capital" min="0.01" step="any" required value="<?php echo htmlspecialchars($project['invest_capital']); ?>" class="w-full p-3 bg-slate-950 border border-slate-800 rounded-xl text-sm text-white focus:outline-none focus:border-emerald-500/50 transition-colors">
                    <p id="invest_capital_preview" class="text-[10px] text-emerald-400 mt-1 italic"></p>
                    <p class="text-rose-400 text-xs mt-1"><?php echo $validation_errors['invest_capital'] ?? ''; ?></p>
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-400 mb-2">Non-Capital Investment (USD)</label>
                    <input type="number" id="invest_noncapital" name="invest_noncapital" min="0" step="any" required value="<?php echo htmlspecialchars($project['invest_noncapital']); ?>" class="w-full p-3 bg-slate-950 border border-slate-800 rounded-xl text-sm text-white focus:outline-none focus:border-emerald-500/50 transition-colors">
                    <p id="invest_noncapital_preview" class="text-[10px] text-emerald-400 mt-1 italic"></p>
                    <p class="text-rose-400 text-xs mt-1"><?php echo $validation_errors['invest_noncapital'] ?? ''; ?></p>
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-400 mb-2">Pajak Korporasi (%)</label>
                    <input type="number" step="any" min="0" max="100" required name="tax" value="<?php echo htmlspecialchars($project['tax']); ?>" class="w-full p-3 bg-slate-950 border border-slate-800 rounded-xl text-sm text-white focus:outline-none focus:border-emerald-500/50 transition-colors">
                    <p class="text-rose-400 text-xs mt-1"><?php echo $validation_errors['tax'] ?? ''; ?></p>
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-400 mb-2">Durasi Kontrak (Tahun)</label>
                    <input type="number" min="1" step="1" required name="investment_years" value="<?php echo htmlspecialchars($project['investment_years']); ?>" class="w-full p-3 bg-slate-950 border border-slate-800 rounded-xl text-sm text-white focus:outline-none focus:border-emerald-500/50 transition-colors">
                    <p class="text-rose-400 text-xs mt-1"><?php echo $validation_errors['investment_years'] ?? ''; ?></p>
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-400 mb-2">Metode Depresiasi</label>
                    <select name="depreciation_method" class="w-full p-3 bg-slate-950 border border-slate-800 rounded-xl text-sm text-white focus:outline-none focus:border-emerald-500/50 transition-colors">
                        <option value="Straight Line" <?php echo ($project['depreciation_method'] == 'Straight Line') ? 'selected' : ''; ?>>Straight Line Method</option>
                        <option value="Double Declining" <?php echo ($project['depreciation_method'] == 'Double Declining') ? 'selected' : ''; ?>>Double Declining Balance Method</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-400 mb-2">Decline Rate (% / Tahun)</label>
                    <input type="number" step="any" min="0" max="100" required name="decline_rate" value="<?php echo htmlspecialchars($project['decline_rate']); ?>" class="w-full p-3 bg-slate-950 border border-slate-800 rounded-xl text-sm text-white focus:outline-none focus:border-emerald-500/50 transition-colors">
                    <p class="text-rose-400 text-xs mt-1"><?php echo $validation_errors['decline_rate'] ?? ''; ?></p>
                </div>
            </div>
            <div class="flex justify-between items-center pt-4 border-t border-slate-800">
                <button type="button" onclick="document.getElementById('delete-project-modal').classList.remove('hidden')" class="text-xs text-rose-400 font-bold hover:underline bg-transparent border-0 cursor-pointer flex items-center"><i class="fas fa-trash-alt mr-1"></i> Hapus Seluruh Proyek</button>
                <div class="flex gap-2">
                    <a href="project-details.php?id=<?php echo $project_id; ?>" class="bg-slate-800 text-slate-300 py-2 px-4 rounded-xl text-xs font-bold">Batal</a>
                    <button type="submit" class="bg-emerald-500 text-slate-950 py-2 px-4 rounded-xl text-xs font-bold">Simpan Perubahan</button>
                </div>
            </div>
        </form>
            </div>
        </main>
    </div>

<!-- MODAL: Konfirmasi Hapus Proyek dengan Proteksi Ganda -->
<div id="delete-project-modal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-slate-950/80 backdrop-blur-sm px-4">
    <div class="bg-slate-900 border border-slate-800 rounded-2xl p-6 max-w-sm w-full shadow-2xl">
        <div class="w-12 h-12 bg-rose-500/15 border border-rose-500/30 rounded-xl flex items-center justify-center mx-auto mb-4">
            <i class="fas fa-exclamation-triangle text-rose-400 text-lg"></i>
        </div>
        <h3 class="text-sm font-bold text-white text-center mb-1">Hapus Seluruh Proyek?</h3>
        <p class="text-xs text-slate-400 text-center mb-4">Seluruh data lapangan, parameter, dan tabel cashflow proyek <strong><?php echo htmlspecialchars($project['name'] ?? ''); ?></strong> akan dihapus secara permanen.</p>
        
        <div class="mb-5">
            <label class="block text-[10px] font-bold text-slate-400 mb-1.5 uppercase tracking-wider">Ketik nama proyek untuk konfirmasi:</label>
            <p class="text-[11px] text-slate-500 mb-2 italic bg-slate-950 p-2 rounded-lg border border-slate-800 select-all text-center"><?php echo htmlspecialchars($project['name'] ?? ''); ?></p>
            <input type="text" id="confirm-project-name" placeholder="Ketik nama proyek di sini..." class="w-full p-2.5 bg-slate-950 border border-slate-800 rounded-xl text-xs text-white focus:outline-none focus:border-rose-500 transition-colors">
        </div>

        <div class="flex gap-3">
            <button type="button" onclick="document.getElementById('delete-project-modal').classList.add('hidden')"
                class="flex-1 px-4 py-2.5 bg-slate-800 border border-slate-700 text-slate-300 hover:text-white font-semibold text-xs rounded-xl transition-all">
                Batal
            </button>
            <a id="confirm-delete-project-btn" href="delete-project.php?id=<?php echo $project_id; ?>"
                class="flex-1 text-center px-4 py-2.5 bg-rose-500/50 text-white/50 font-bold text-xs rounded-xl transition-all flex items-center justify-center pointer-events-none cursor-not-allowed">
                <i class="fas fa-trash-alt mr-1.5"></i>Ya, Hapus
            </a>
        </div>
    </div>
</div>

<script>
document.addEventListener("DOMContentLoaded", function() {
    const confirmInput = document.getElementById('confirm-project-name');
    const deleteBtn = document.getElementById('confirm-delete-project-btn');
    const projectName = <?php echo json_encode($project['name'] ?? ''); ?>;

    if (confirmInput) {
        confirmInput.addEventListener('input', function() {
            if (confirmInput.value.trim() === projectName) {
                deleteBtn.classList.remove('bg-rose-500/50', 'text-white/50', 'pointer-events-none', 'cursor-not-allowed');
                deleteBtn.classList.add('bg-rose-500', 'hover:bg-rose-600', 'text-white');
            } else {
                deleteBtn.classList.remove('bg-rose-500', 'hover:bg-rose-600', 'text-white');
                deleteBtn.classList.add('bg-rose-500/50', 'text-white/50', 'pointer-events-none', 'cursor-not-allowed');
            }
        });
    }

    const deleteModal = document.getElementById('delete-project-modal');
    if (deleteModal) {
        deleteModal.addEventListener('click', function(e) {
            if (e.target === this) {
                this.classList.add('hidden');
                confirmInput.value = '';
                deleteBtn.classList.remove('bg-rose-500', 'hover:bg-rose-600', 'text-white');
                deleteBtn.classList.add('bg-rose-500/50', 'text-white/50', 'pointer-events-none', 'cursor-not-allowed');
            }
        });
    }

    // Live currency formatting previews
    const formatCurrencyPreview = (num) => {
        if (!num || isNaN(num) || num < 0) return '';
        const parsed = parseFloat(num);
        const fullUSD = new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'USD', minimumFractionDigits: 0 }).format(parsed);
        const millionUSD = (parsed / 1000000).toFixed(2) + ' Juta USD';
        const thousandUSD = (parsed / 1000).toFixed(2) + ' M USD (Ribuan)';
        return `Interpretasi: ${fullUSD} | ${millionUSD} | ${thousandUSD}`;
    };

    const capexInput = document.getElementById('invest_capital');
    const capexPreview = document.getElementById('invest_capital_preview');
    const nonCapexInput = document.getElementById('invest_noncapital');
    const nonCapexPreview = document.getElementById('invest_noncapital_preview');

    const updatePreviews = () => {
        if (capexInput && capexPreview) capexPreview.textContent = formatCurrencyPreview(capexInput.value);
        if (nonCapexInput && nonCapexPreview) nonCapexPreview.textContent = formatCurrencyPreview(nonCapexInput.value);
    };

    if (capexInput) {
        capexInput.addEventListener('input', () => {
            capexPreview.textContent = formatCurrencyPreview(capexInput.value);
        });
    }
    if (nonCapexInput) {
        nonCapexInput.addEventListener('input', () => {
            nonCapexPreview.textContent = formatCurrencyPreview(nonCapexInput.value);
        });
    }

    updatePreviews();
});
</script>
</body>
</html>