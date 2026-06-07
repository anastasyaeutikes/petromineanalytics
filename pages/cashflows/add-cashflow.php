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

    if(empty($year) || !is_numeric($year) || $year <= 0 || intval($year) != $year) {
        $validation_errors['year'] = "Tahun wajib diisi dengan bilangan bulat positif lebih besar dari 0.";
    }
    if(empty($production) || !is_numeric($production) || $production <= 0) {
        $validation_errors['production'] = "Volume produksi wajib diisi dengan angka positif lebih besar dari 0.";
    }
    if(empty($price_per_barrel) || !is_numeric($price_per_barrel) || $price_per_barrel <= 0) {
        $validation_errors['price_per_barrel'] = "Harga per barel wajib diisi dengan angka positif lebih besar dari 0.";
    }
    if(empty($opex) || !is_numeric($opex) || $opex <= 0) {
        $validation_errors['opex'] = "OPEX wajib diisi dengan angka positif lebih besar dari 0.";
    }

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
                $_SESSION["toast_success"] = "Data cashflow tahun " . htmlspecialchars($year) . " berhasil ditambahkan!";
                header("location: ../projects/project-details.php?id=" . $project_id);
                exit;
            }
            $stmt->close();
        }
    } else {
        $_SESSION["toast_error"] = "Gagal menyimpan cashflow. Harap periksa kolom yang ditandai.";
    }
}
?>
<?php
$base_path = "../../";
$page_title = "Input Cashflow Tahunan - " . htmlspecialchars($project_details['name'] ?? '');
require_once "../../includes/header.php";
?>
<body class="bg-slate-950 text-slate-100 min-h-screen flex">
    <?php require_once "../../includes/sidebar.php"; ?>
    <div class="flex-1 flex flex-col min-w-0">
        <?php 
        $breadcrumb_items = [
            ['label' => $project_details['name'], 'url' => '../projects/project-details.php?id=' . $project_id],
            ['label' => 'Tambah Cashflow']
        ];
        require_once "../../includes/topbar.php"; 
        ?>
        <main class="max-w-xl w-full mx-auto px-6 py-10 flex-grow flex items-center justify-center">
            <div class="w-full max-w-xl bg-slate-900 border border-slate-800 rounded-2xl p-8 shadow-2xl">
        <h2 class="text-lg font-bold text-white mb-2">Cashflow</h2>
        <p class="text-xs text-slate-400 mb-6">Proyek: <span class="text-emerald-400 font-semibold"><?php echo htmlspecialchars($project_details['name']); ?></span></p>
        
        <form action="" method="POST" class="space-y-4">
            <div>
                <label class="block text-xs font-bold text-slate-400 mb-2">Tahun Ke-</label>
                <input type="number" min="1" step="1" required name="year" placeholder="Contoh: 1" value="<?php echo htmlspecialchars($year); ?>" class="w-full p-3 bg-slate-950 border border-slate-800 rounded-xl text-sm text-white focus:outline-none focus:border-emerald-500/50 transition-colors">
                <p class="text-rose-400 text-xs mt-1"><?php echo $validation_errors['year'] ?? ''; ?></p>
            </div>
            <div>
                <label class="block text-xs font-bold text-slate-400 mb-2">Volume Produksi Minyak Mentah (BBL atau Mbbl)</label>
                <input type="number" id="cf_production" min="0.01" step="any" required name="production" placeholder="Contoh: 150000" value="<?php echo htmlspecialchars($production); ?>" class="w-full p-3 bg-slate-950 border border-slate-800 rounded-xl text-sm text-white focus:outline-none focus:border-emerald-500/50 transition-colors">
                <p id="production_preview" class="text-[10px] text-emerald-400 mt-1 italic"></p>
                <p class="text-rose-400 text-xs mt-1"><?php echo $validation_errors['production'] ?? ''; ?></p>
            </div>
            <div>
                <label class="block text-xs font-bold text-slate-400 mb-2">Harga Minyak Mentah (USD / BBL)</label>
                <input type="number" id="cf_price" min="0.01" step="any" required name="price_per_barrel" placeholder="Contoh: 75.50" value="<?php echo htmlspecialchars($price_per_barrel); ?>" class="w-full p-3 bg-slate-950 border border-slate-800 rounded-xl text-sm text-white focus:outline-none focus:border-emerald-500/50 transition-colors">
                <p id="price_preview" class="text-[10px] text-emerald-400 mt-1 italic"></p>
                <p class="text-rose-400 text-xs mt-1"><?php echo $validation_errors['price_per_barrel'] ?? ''; ?></p>
            </div>
            <div>
                <label class="block text-xs font-bold text-slate-400 mb-2">Biaya Operasional Lapangan (OPEX - USD atau M USD)</label>
                <input type="number" id="cf_opex" min="0.01" step="any" required name="opex" placeholder="Contoh: 2000000" value="<?php echo htmlspecialchars($opex); ?>" class="w-full p-3 bg-slate-950 border border-slate-800 rounded-xl text-sm text-white focus:outline-none focus:border-emerald-500/50 transition-colors">
                <p id="opex_preview" class="text-[10px] text-emerald-400 mt-1 italic"></p>
                <p class="text-rose-400 text-xs mt-1"><?php echo $validation_errors['opex'] ?? ''; ?></p>
            </div>
            <div class="flex justify-end gap-3 pt-4 border-t border-slate-800">
                <a href="../projects/project-details.php?id=<?php echo $project_id; ?>" class="bg-slate-800 text-slate-300 py-2.5 px-5 rounded-xl text-xs font-bold">Batal</a>
                <button type="submit" class="bg-emerald-500 text-slate-950 py-2.5 px-5 rounded-xl text-xs font-bold">Simpan & Hitung</button>
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

    const formatBarrelsPreview = (num) => {
        if (!num || isNaN(num) || num < 0) return '';
        const parsed = parseFloat(num);
        const fullBBL = parsed.toLocaleString('id-ID') + ' BBL';
        const thousandBBL = (parsed / 1000).toFixed(2) + ' Mbbl (Ribuan BBL)';
        const millionBBL = (parsed / 1000000).toFixed(2) + ' MMbbl (Jutaan BBL)';
        return `Interpretasi: ${fullBBL} | ${thousandBBL} | ${millionBBL}`;
    };

    const prodInput = document.getElementById('cf_production');
    const prodPreview = document.getElementById('production_preview');
    const priceInput = document.getElementById('cf_price');
    const pricePreview = document.getElementById('price_preview');
    const opexInput = document.getElementById('cf_opex');
    const opexPreview = document.getElementById('opex_preview');

    const updatePreviews = () => {
        if (prodInput && prodPreview) prodPreview.textContent = formatBarrelsPreview(prodInput.value);
        if (priceInput && pricePreview) pricePreview.textContent = formatCurrencyPreview(priceInput.value);
        if (opexInput && opexPreview) opexPreview.textContent = formatCurrencyPreview(opexInput.value);
    };

    if (prodInput) {
        prodInput.addEventListener('input', () => {
            prodPreview.textContent = formatBarrelsPreview(prodInput.value);
        });
    }
    if (priceInput) {
        priceInput.addEventListener('input', () => {
            pricePreview.textContent = formatCurrencyPreview(priceInput.value);
        });
    }
    if (opexInput) {
        opexInput.addEventListener('input', () => {
            opexPreview.textContent = formatCurrencyPreview(opexInput.value);
        });
    }

    updatePreviews();
});
</script>
</body>
</html>