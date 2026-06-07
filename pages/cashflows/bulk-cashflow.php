<?php
// bulk-cashflow.php
require_once "../../includes/auth.php";
$user_id = $_SESSION['user_id'];
require_once "../../config/config.php";

$project_id = isset($_GET['project_id']) ? trim($_GET['project_id']) : null;
if (empty($project_id) || !ctype_digit($project_id)) { 
    header("location: ../projects/home.php"); 
    exit; 
}

// 1. Ambil Parameter Proyek
$project_details = null;
$sql_project = "SELECT name, tax, invest_capital, investment_years, depreciation_method FROM projects WHERE id = ? AND user_id = ?";
if ($stmt_project = $mysqli->prepare($sql_project)) {
    $stmt_project->bind_param("ii", $project_id, $user_id);
    if ($stmt_project->execute()) { 
        $project_details = $stmt_project->get_result()->fetch_assoc(); 
    }
    $stmt_project->close();
}
if(!$project_details) { 
    header("location: ../projects/home.php"); 
    exit; 
}

$N = intval($project_details['investment_years']);

// 2. Ambil data cashflow yang sudah terisi di database
$existing_data = [];
$sql_cf = "SELECT * FROM cashflows WHERE project_id = ? ORDER BY year ASC";
if ($stmt_cf = $mysqli->prepare($sql_cf)) {
    $stmt_cf->bind_param("i", $project_id);
    if ($stmt_cf->execute()) {
        $result_cf = $stmt_cf->get_result();
        while($row = $result_cf->fetch_assoc()) {
            $existing_data[$row['year']] = $row;
        }
    }
    $stmt_cf->close();
}

$validation_errors = [];

// 3. Proses penyimpanan bulk
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $productions = $_POST['production'] ?? [];
    $prices = $_POST['price_per_barrel'] ?? [];
    $opexs = $_POST['opex'] ?? [];

    // Validasi input
    for ($t = 1; $t <= $N; $t++) {
        $prod = trim($productions[$t] ?? '');
        $price = trim($prices[$t] ?? '');
        $op = trim($opexs[$t] ?? '');

        // Lewati jika baris benar-benar kosong semuanya
        if ($prod === '' && $price === '' && $op === '') {
            continue;
        }

        // Jika salah satu diisi, maka wajib diisi semua dan harus positif
        if ($prod === '' || $price === '' || $op === '') {
            $validation_errors[] = "Tahun ${t}: Semua kolom data harus diisi lengkap jika baris ini ingin disimpan.";
            continue;
        }

        if (!is_numeric($prod) || $prod <= 0) {
            $validation_errors[] = "Tahun ${t}: Volume produksi harus berupa angka positif lebih besar dari 0.";
        }
        if (!is_numeric($price) || $price <= 0) {
            $validation_errors[] = "Tahun ${t}: Harga jual harus berupa angka positif lebih besar dari 0.";
        }
        if (!is_numeric($op) || $op <= 0) {
            $validation_errors[] = "Tahun ${t}: OPEX harus berupa angka positif lebih besar dari 0.";
        }
    }

    if (empty($validation_errors)) {
        // Mulai simpan / perbarui ke database
        for ($t = 1; $t <= $N; $t++) {
            $prod = isset($productions[$t]) && $productions[$t] !== '' ? floatval($productions[$t]) : null;
            $price = isset($prices[$t]) && $prices[$t] !== '' ? floatval($prices[$t]) : null;
            $op = isset($opexs[$t]) && $opexs[$t] !== '' ? floatval($opexs[$t]) : null;

            if ($prod === null || $price === null || $op === null) {
                // Hapus data jika dikosongkan secara sengaja oleh pengguna
                if (isset($existing_data[$t])) {
                    $sql_del = "DELETE FROM cashflows WHERE project_id = ? AND year = ?";
                    if ($stmt_del = $mysqli->prepare($sql_del)) {
                        $stmt_del->bind_param("ii", $project_id, $t);
                        $stmt_del->execute();
                        $stmt_del->close();
                    }
                }
                continue;
            }

            $income = $prod * $price;

            // Hitung Depresiasi
            if ($project_details['depreciation_method'] == "Straight Line") {
                $dep = $project_details['invest_capital'] / $N;
            } else {
                $dep = (2 / $N) * $project_details['invest_capital'];
            }

            $taxable = $income - $op - $dep;
            if ($taxable < 0) $taxable = 0;

            $tax_paid = $taxable * ($project_details['tax'] / 100);
            $ncf = $income - $op - $tax_paid;

            if (isset($existing_data[$t])) {
                // Update
                $sql_up = "UPDATE cashflows SET production = ?, income = ?, opex = ?, taxable_income = ?, net_cashflow = ?, updated_at = NOW() WHERE id = ?";
                if ($stmt_up = $mysqli->prepare($sql_up)) {
                    $stmt_up->bind_param("dddddi", $prod, $income, $op, $taxable, $ncf, $existing_data[$t]['id']);
                    $stmt_up->execute();
                    $stmt_up->close();
                }
            } else {
                // Insert
                $sql_in = "INSERT INTO cashflows (year, production, income, opex, taxable_income, net_cashflow, project_id, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), NOW())";
                if ($stmt_in = $mysqli->prepare($sql_in)) {
                    $stmt_in->bind_param("iiddddi", $t, $prod, $income, $op, $taxable, $ncf, $project_id);
                    $stmt_in->execute();
                    $stmt_in->close();
                }
            }
        }

        $_SESSION["toast_success"] = "Seluruh data cashflow tahunan berhasil diperbarui massal!";
        header("location: ../projects/project-details.php?id=" . $project_id);
        exit;
    } else {
        $_SESSION["toast_error"] = "Gagal memperbarui cashflow massal. Terdapat kesalahan input.";
    }
}

$base_path = "../../";
$page_title = "Input Cashflow Massal - " . htmlspecialchars($project_details['name']);
$extra_head = "
    <!-- Load SheetJS untuk import & export template excel -->
    <script src=\"https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js\"></script>
";
require_once "../../includes/header.php";
?>
<body class="bg-slate-950 text-slate-100 min-h-screen flex">
    <?php require_once "../../includes/sidebar.php"; ?>
    <div class="flex-1 flex flex-col min-w-0">
        <?php 
        $breadcrumb_items = [
            ['label' => $project_details['name'], 'url' => '../projects/project-details.php?id=' . $project_id],
            ['label' => 'Input Massal']
        ];
        require_once "../../includes/topbar.php"; 
        ?>
        <main class="max-w-6xl w-full mx-auto px-6 py-8 flex-grow space-y-6">
            
            <!-- Header Panel -->
            <div class="bg-slate-900 border border-slate-800 rounded-2xl p-6 flex flex-col md:flex-row justify-between items-start md:items-center gap-4 shadow-xl">
                <div>
                    <h2 class="text-lg font-bold text-white">Input Data Cashflow Massal (Multi-Row / Excel)</h2>
                    <p class="text-xs text-slate-400 mt-1">Proyek: <span class="text-emerald-400 font-semibold"><?php echo htmlspecialchars($project_details['name']); ?></span> &nbsp;|&nbsp; Durasi Kontrak: <span class="text-slate-200"><?php echo $N; ?> Tahun</span></p>
                </div>
                
                <!-- Aksi Excel -->
                <div class="flex flex-wrap gap-2.5">
                    <button type="button" onclick="downloadExcelTemplate()" class="bg-slate-800 hover:bg-slate-700 text-slate-200 border border-slate-700 py-2 px-4 rounded-xl text-xs font-bold transition-colors flex items-center gap-2">
                        <i class="fas fa-file-excel text-green-500"></i> Unduh Template Excel
                    </button>
                    <label class="bg-green-700 hover:bg-green-600 text-white py-2 px-4 rounded-xl text-xs font-bold transition-colors flex items-center gap-2 cursor-pointer">
                        <i class="fas fa-upload"></i> Unggah & Impor Excel
                        <input type="file" id="excel_file_input" accept=".xlsx, .xls" class="hidden" onchange="importFromExcel(event)">
                    </label>
                </div>
            </div>

            <!-- Pesan Kesalahan PHP -->
            <?php if (!empty($validation_errors)): ?>
                <div class="bg-rose-500/10 border border-rose-500/30 rounded-2xl p-4">
                    <h4 class="text-xs font-bold text-rose-400 mb-2">Terdapat Kesalahan Pengisian Data:</h4>
                    <ul class="list-disc pl-4 text-[11px] text-rose-300 space-y-1">
                        <?php foreach($validation_errors as $err): ?>
                            <li><?php echo htmlspecialchars($err); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <!-- Formulir Grid -->
            <form action="" method="POST" id="bulk-form" class="space-y-6">
                <div class="bg-slate-900 border border-slate-800 rounded-2xl overflow-hidden shadow-2xl">
                    <div class="px-6 py-4 border-b border-slate-800 flex justify-between items-center bg-slate-900/60">
                        <h3 class="text-xs font-bold text-slate-400 uppercase tracking-wider">Tabel Data Cashflow (Tahun 1 – <?php echo $N; ?>)</h3>
                        <span class="text-[10px] text-slate-500 italic">Kosongkan baris untuk menghapus/melewati data tahun tersebut</span>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse text-xs">
                            <thead>
                                <tr class="bg-slate-950 text-slate-400 border-b border-slate-800 font-bold text-[11px]">
                                    <th class="p-4 w-28">Tahun</th>
                                    <th class="p-4">Volume Produksi (BBL / Tahun)</th>
                                    <th class="p-4">Harga Jual (USD / BBL)</th>
                                    <th class="p-4">Biaya Operasional (OPEX - USD / Tahun)</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-800/60 text-slate-300">
                                <?php for ($t = 1; $t <= $N; $t++): 
                                    $curr_prod = isset($existing_data[$t]) ? $existing_data[$t]['production'] : '';
                                    $curr_price = isset($existing_data[$t]) ? ($existing_data[$t]['income'] / $existing_data[$t]['production']) : '';
                                    $curr_opex = isset($existing_data[$t]) ? $existing_data[$t]['opex'] : '';
                                ?>
                                    <tr class="hover:bg-slate-800/20 transition-colors">
                                        <td class="p-4 font-bold text-slate-400">Tahun <?php echo $t; ?></td>
                                        <td class="p-3">
                                            <input type="number" step="any" min="0.01" name="production[<?php echo $t; ?>]" id="production_<?php echo $t; ?>" value="<?php echo htmlspecialchars($curr_prod); ?>" placeholder="Kosong" class="w-full p-2.5 bg-slate-950 border border-slate-800 rounded-xl text-xs text-white focus:outline-none focus:border-indigo-500/50 transition-colors table-input" data-year="<?php echo $t; ?>" data-type="production">
                                        </td>
                                        <td class="p-3">
                                            <input type="number" step="any" min="0.01" name="price_per_barrel[<?php echo $t; ?>]" id="price_<?php echo $t; ?>" value="<?php echo htmlspecialchars($curr_price ? number_format($curr_price, 2, '.', '') : ''); ?>" placeholder="Kosong" class="w-full p-2.5 bg-slate-950 border border-slate-800 rounded-xl text-xs text-white focus:outline-none focus:border-indigo-500/50 transition-colors table-input" data-year="<?php echo $t; ?>" data-type="price">
                                        </td>
                                        <td class="p-3">
                                            <input type="number" step="any" min="0.01" name="opex[<?php echo $t; ?>]" id="opex_<?php echo $t; ?>" value="<?php echo htmlspecialchars($curr_opex); ?>" placeholder="Kosong" class="w-full p-2.5 bg-slate-950 border border-slate-800 rounded-xl text-xs text-white focus:outline-none focus:border-indigo-500/50 transition-colors table-input" data-year="<?php echo $t; ?>" data-type="opex">
                                        </td>
                                    </tr>
                                <?php endfor; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Live Preview & Action Buttons -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    
                    <!-- Panel Panduan Live Preview Dinamis -->
                    <div class="md:col-span-2 bg-slate-900 border border-slate-800 rounded-2xl p-5 shadow-xl">
                        <div class="flex items-center gap-3 mb-3">
                            <div class="w-7 h-7 rounded-lg bg-indigo-500/10 flex items-center justify-center text-indigo-400">
                                <i class="fas fa-eye text-xs"></i>
                            </div>
                            <h4 class="text-xs font-bold text-white uppercase tracking-wider">Detektor & Preview Satuan Input</h4>
                        </div>
                        <div id="live-preview-box" class="text-xs text-slate-400 bg-slate-950 p-4 rounded-xl border border-slate-800 min-h-[64px] flex items-center">
                            Klik atau ketik di dalam kolom input untuk melihat interpretasi format/satuan secara langsung.
                        </div>
                    </div>

                    <!-- Tombol Aksi Form -->
                    <div class="bg-slate-900 border border-slate-800 rounded-2xl p-5 shadow-xl flex flex-col justify-center gap-3">
                        <button type="submit" class="w-full bg-emerald-500 hover:bg-emerald-600 text-slate-950 py-3 px-5 rounded-xl text-xs font-bold transition-all shadow-lg shadow-emerald-500/10 flex items-center justify-center gap-2 cursor-pointer border-0">
                            <i class="fas fa-save text-sm"></i> Simpan Semua Cashflow
                        </button>
                        <a href="../projects/project-details.php?id=<?php echo $project_id; ?>" class="w-full bg-slate-800 hover:bg-slate-700 text-slate-300 py-3 px-5 rounded-xl text-xs font-bold transition-all flex items-center justify-center gap-2 border border-slate-700">
                            Kembali ke Detail Proyek
                        </a>
                    </div>
                </div>
            </form>
        </main>
    </div>

<script>
const investmentYears = <?php echo $N; ?>;
const projectName = <?php echo json_encode($project_details['name']); ?>;
// Data existing untuk dinomori di template excel
const existingData = <?php echo json_encode($existing_data); ?>;

// Fungsi format angka mata uang
const formatCurrency = (num) => {
    if (!num || isNaN(num) || num < 0) return '';
    const parsed = parseFloat(num);
    const fullUSD = new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'USD', minimumFractionDigits: 0 }).format(parsed);
    const millionUSD = (parsed / 1000000).toFixed(2) + ' Juta USD';
    const thousandUSD = (parsed / 1000).toFixed(2) + ' M USD (Ribuan)';
    return `${fullUSD} | ${millionUSD} | ${thousandUSD}`;
};

// Fungsi format barrel
const formatBarrels = (num) => {
    if (!num || isNaN(num) || num < 0) return '';
    const parsed = parseFloat(num);
    const fullBBL = parsed.toLocaleString('id-ID') + ' BBL';
    const thousandBBL = (parsed / 1000).toFixed(2) + ' Mbbl (Ribuan BBL)';
    const millionBBL = (parsed / 1000000).toFixed(2) + ' MMbbl (Jutaan BBL)';
    return `${fullBBL} | ${thousandBBL} | ${millionBBL}`;
};

// Deteksi fokus input untuk live preview
document.addEventListener("DOMContentLoaded", function() {
    const inputs = document.querySelectorAll('.table-input');
    const previewBox = document.getElementById('live-preview-box');

    inputs.forEach(input => {
        const updatePreview = () => {
            const val = input.value;
            const year = input.getAttribute('data-year');
            const type = input.getAttribute('data-type');
            
            if (!val || val <= 0) {
                previewBox.innerHTML = `<span class="text-slate-500">Tahun ${year} - Input masih kosong atau tidak valid. Silakan masukkan nilai positif.</span>`;
                return;
            }

            if (type === 'production') {
                previewBox.innerHTML = `<div><strong class="text-indigo-400">Tahun ${year} - Volume Produksi:</strong><br><p class="mt-1 text-slate-200">${formatBarrels(val)}</p></div>`;
            } else if (type === 'price') {
                previewBox.innerHTML = `<div><strong class="text-indigo-400">Tahun ${year} - Harga Jual:</strong><br><p class="mt-1 text-slate-200">${formatCurrency(val)} / BBL</p></div>`;
            } else if (type === 'opex') {
                previewBox.innerHTML = `<div><strong class="text-indigo-400">Tahun ${year} - OPEX:</strong><br><p class="mt-1 text-slate-200">${formatCurrency(val)}</p></div>`;
            }
        };

        input.addEventListener('focus', updatePreview);
        input.addEventListener('input', updatePreview);
    });
});

// Fungsi mengunduh template Excel dinamis
function downloadExcelTemplate() {
    const wb = XLSX.utils.book_new();
    
    // Header sheet
    const content = [
        ['TEMPLATE INPUT CASHFLOW TAHUNAN - PETROMINE ANALYTICS'],
        ['Proyek:', projectName],
        ['Durasi Kontrak:', investmentYears + ' Tahun'],
        ['Catatan:', 'Jangan mengubah kolom Tahun. Kosongkan baris untuk melewati/menghapus data.'],
        [],
        ['Tahun', 'Volume Produksi (BBL atau Mbbl)', 'Harga Jual ($/bbl)', 'Biaya Operasional / OPEX (USD atau M USD)']
    ];

    // Isi baris data dengan data lama yang sudah ada di database jika ada
    for (let t = 1; t <= investmentYears; t++) {
        const dataYear = existingData[t] || {};
        const prod = dataYear.production !== undefined ? dataYear.production : '';
        const price = dataYear.production > 0 ? (dataYear.income / dataYear.production) : '';
        const opex = dataYear.opex !== undefined ? dataYear.opex : '';
        
        content.push([t, prod, price, opex]);
    }

    const ws = XLSX.utils.aoa_to_sheet(content);
    
    // Lebar kolom
    ws['!cols'] = [
        { wch: 10 }, { wch: 32 }, { wch: 20 }, { wch: 36 }
    ];

    XLSX.utils.book_append_sheet(wb, ws, 'Template Cashflow');
    
    const safeName = projectName.replace(/[^a-zA-Z0-9_\- ]/g, '').trim().replace(/\s+/g, '_');
    XLSX.writeFile(wb, 'Template_Cashflow_' + safeName + '.xlsx');
}

// Fungsi impor data dari berkas Excel
function importFromExcel(event) {
    const file = event.target.files[0];
    if (!file) return;

    const reader = new FileReader();
    reader.onload = function(e) {
        try {
            const data = new Uint8Array(e.target.result);
            const workbook = XLSX.read(data, { type: 'array' });
            const firstSheet = workbook.Sheets[workbook.SheetNames[0]];
            
            // Konversi ke format array data raw
            const rows = XLSX.utils.sheet_to_json(firstSheet, { header: 1 });
            
            // Validasi format baris headers
            // Baris ke-5 (indeks 5) adalah baris data Tahun 1
            if (rows.length < 6) {
                if (window.showToast) window.showToast('Gagal impor: File template tidak berisi baris data.', 'error');
                else alert('File template tidak berisi baris data.');
                return;
            }

            let importedRows = 0;
            let errorRows = 0;

            for (let i = 6; i < rows.length; i++) {
                const row = rows[i];
                if (row.length === 0) continue;

                const t = parseInt(row[0]);
                const prod = row[1] !== undefined && row[1] !== '' ? parseFloat(row[1]) : '';
                const price = row[2] !== undefined && row[2] !== '' ? parseFloat(row[2]) : '';
                const opex = row[3] !== undefined && row[3] !== '' ? parseFloat(row[3]) : '';

                if (!isNaN(t) && t >= 1 && t <= investmentYears) {
                    const prodInput = document.getElementById(`production_${t}`);
                    const priceInput = document.getElementById(`price_${t}`);
                    const opexInput = document.getElementById(`opex_${t}`);

                    if (prodInput && priceInput && opexInput) {
                        // Jika baris data kosong semuanya, kosongkan juga di form
                        if (prod === '' && price === '' && opex === '') {
                            prodInput.value = '';
                            priceInput.value = '';
                            opexInput.value = '';
                        } else {
                            // Validasi angka positif
                            if (prod > 0 && price > 0 && opex > 0) {
                                prodInput.value = prod;
                                priceInput.value = price;
                                opexInput.value = opex;
                                importedRows++;
                            } else {
                                errorRows++;
                            }
                        }
                    }
                }
            }

            // Notifikasi hasil
            if (importedRows > 0) {
                const msg = `Berhasil memetakan ${importedRows} tahun data dari Excel ke form. Silakan tinjau kembali lalu simpan.`;
                if (window.showToast) window.showToast(msg, 'success');
                else alert(msg);
            }
            if (errorRows > 0) {
                const errMsg = `Terdapat ${errorRows} baris diabaikan karena berisi nilai negatif atau nol.`;
                if (window.showToast) window.showToast(errMsg, 'error');
                else alert(errMsg);
            }
        } catch (err) {
            console.error(err);
            if (window.showToast) window.showToast('Gagal membaca file. Pastikan menggunakan file template Excel yang benar.', 'error');
            else alert('Gagal membaca file.');
        }
    };
    reader.readAsArrayBuffer(file);
    // Reset file input agar bisa di-upload ulang file yang sama
    event.target.value = '';
}
</script>
</body>
</html>
