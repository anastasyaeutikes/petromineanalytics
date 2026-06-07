<?php
// create-project.php
require_once "../../includes/auth.php";
$user_id = $_SESSION['user_id'];
require_once "../../config/config.php";

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
    if (!is_numeric($invest_capital) || $invest_capital <= 0) $validation_errors['invest_capital'] = "Nilai CAPEX harus angka positif lebih besar dari 0.";
    if (!is_numeric($invest_noncapital) || $invest_noncapital < 0) $validation_errors['invest_noncapital'] = "Nilai Non-CAPEX harus angka positif minimal 0.";
    if (!is_numeric($tax) || $tax < 0 || $tax > 100) $validation_errors['tax'] = "Pajak harus berupa angka antara 0% sampai 100%.";
    if (!is_numeric($investment_years) || $investment_years <= 0 || intval($investment_years) != $investment_years) $validation_errors['investment_years'] = "Durasi proyek harus berupa bilangan bulat positif lebih besar dari 0.";
    if (empty($depreciation_method)) $validation_errors['depreciation_method'] = "Pilih metode depresiasi.";
    if (!is_numeric($decline_rate) || $decline_rate < 0 || $decline_rate > 100) $validation_errors['decline_rate'] = "Decline rate harus berupa angka antara 0% sampai 100%.";

    if (empty($validation_errors)) {
        $sql = "INSERT INTO projects (name, site_manager, invest_capital, invest_noncapital, tax, investment_years, depreciation, depreciation_method, decline_rate, user_id, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, 0, ?, ?, ?, NOW(), NOW())";
        if ($stmt = $mysqli->prepare($sql)) {
            $stmt->bind_param("ssddddssi", $name, $site_manager, $invest_capital, $invest_noncapital, $tax, $investment_years, $depreciation_method, $decline_rate, $user_id);
            if ($stmt->execute()) {
                $_SESSION["toast_success"] = "Proyek baru '" . htmlspecialchars($name) . "' berhasil diinisiasi!";
                header("location: home.php");
                exit;
            }
            $stmt->close();
        }
    } else {
        $_SESSION["toast_error"] = "Gagal membuat proyek. Harap periksa input Anda.";
    }
}
?>
<?php
$base_path = "../../";
$page_title = "Inisiasi Lapangan Baru";
require_once "../../includes/header.php";
?>
<body class="bg-slate-950 text-slate-100 min-h-screen flex">
    <?php require_once "../../includes/sidebar.php"; ?>
    <div class="flex-1 flex flex-col min-w-0">
        <?php 
        $breadcrumb_items = [
            ['label' => 'Tambah Proyek']
        ];
        require_once "../../includes/topbar.php"; 
        ?>
        <main class="max-w-4xl w-full mx-auto px-6 py-10 flex-grow flex items-center justify-center">
            <div class="w-full max-w-2xl bg-slate-900 border border-slate-800 rounded-2xl p-8 shadow-2xl">
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
                    <input type="number" id="invest_capital" name="invest_capital" min="0.01" step="any" required value="<?php echo htmlspecialchars($invest_capital); ?>" class="w-full p-3 bg-slate-950 border border-slate-800 rounded-xl text-sm text-white focus:outline-none focus:border-emerald-500/50 transition-colors">
                    <p id="invest_capital_preview" class="text-[10px] text-emerald-400 mt-1 italic"></p>
                    <p class="text-rose-400 text-xs mt-1"><?php echo $validation_errors['invest_capital'] ?? ''; ?></p>
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-400 mb-2">Non-Capital Investment (USD)</label>
                    <input type="number" id="invest_noncapital" name="invest_noncapital" min="0" step="any" required value="<?php echo htmlspecialchars($invest_noncapital); ?>" class="w-full p-3 bg-slate-950 border border-slate-800 rounded-xl text-sm text-white focus:outline-none focus:border-emerald-500/50 transition-colors">
                    <p id="invest_noncapital_preview" class="text-[10px] text-emerald-400 mt-1 italic"></p>
                    <p class="text-rose-400 text-xs mt-1"><?php echo $validation_errors['invest_noncapital'] ?? ''; ?></p>
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-400 mb-2">Pajak Korporasi (%)</label>
                    <input type="number" step="any" min="0" max="100" required name="tax" value="<?php echo htmlspecialchars($tax); ?>" class="w-full p-3 bg-slate-950 border border-slate-800 rounded-xl text-sm text-white focus:outline-none focus:border-emerald-500/50 transition-colors">
                    <p class="text-rose-400 text-xs mt-1"><?php echo $validation_errors['tax'] ?? ''; ?></p>
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-400 mb-2">Durasi Proyek (Tahun)</label>
                    <input type="number" min="1" step="1" required name="investment_years" value="<?php echo htmlspecialchars($investment_years); ?>" class="w-full p-3 bg-slate-950 border border-slate-800 rounded-xl text-sm text-white focus:outline-none focus:border-emerald-500/50 transition-colors">
                    <p class="text-rose-400 text-xs mt-1"><?php echo $validation_errors['investment_years'] ?? ''; ?></p>
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-400 mb-2">Metode Depresiasi</label>
                    <select name="depreciation_method" class="w-full p-3 bg-slate-950 border border-slate-800 rounded-xl text-sm text-white focus:outline-none focus:border-emerald-500/50 transition-colors">
                        <option value="Straight Line">Straight Line Method</option>
                        <option value="Double Declining">Double Declining Balance Method</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-400 mb-2">Decline Rate (% / Tahun)</label>
                    <input type="number" step="any" min="0" max="100" required name="decline_rate" value="<?php echo htmlspecialchars($decline_rate); ?>" class="w-full p-3 bg-slate-950 border border-slate-800 rounded-xl text-sm text-white focus:outline-none focus:border-emerald-500/50 transition-colors">
                    <p class="text-rose-400 text-xs mt-1"><?php echo $validation_errors['decline_rate'] ?? ''; ?></p>
                </div>
            </div>
            <div class="flex justify-end gap-3 pt-4 border-t border-slate-800">
                <a href="home.php" class="bg-slate-800 text-slate-300 py-2.5 px-5 rounded-xl text-xs font-bold">Batal</a>
                <button type="submit" class="bg-emerald-500 text-slate-950 py-2.5 px-5 rounded-xl text-xs font-bold shadow-md shadow-emerald-500/10">Inisiasi Proyek</button>
            </div>
        </form>
            </div>
        </main>
    </div>

<script>
document.addEventListener("DOMContentLoaded", function() {
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
        capexPreview.textContent = formatCurrencyPreview(capexInput.value);
        nonCapexPreview.textContent = formatCurrencyPreview(nonCapexInput.value);
    };

    capexInput.addEventListener('input', () => {
        capexPreview.textContent = formatCurrencyPreview(capexInput.value);
    });
    nonCapexInput.addEventListener('input', () => {
        nonCapexPreview.textContent = formatCurrencyPreview(nonCapexInput.value);
    });

    updatePreviews();
});
</script>
</body>
</html>