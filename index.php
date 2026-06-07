<?php
// index.php (Halaman Landing Utama - Petromine Analytics)
session_start();

// Jika pengguna sudah login, langsung arahkan ke dashboard utama
if (isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true) {
    header("location: pages/projects/home.php");
    exit;
}

$base_path = "./";

$page_title = "Petromine Analytics - Platform Keekonomian Migas";
require_once "includes/header.php";
?>
<body class="bg-slate-950 text-slate-100 min-h-screen flex flex-col justify-between">

    <?php
    $nav_full_width = false;
    require_once "includes/navbar.php";
    ?>

    <main class="max-w-7xl mx-auto px-6 py-8 w-full flex-grow space-y-8">
        
        <div class="relative rounded-3xl overflow-hidden bg-slate-900 border border-slate-800 h-[450px] flex flex-col items-center justify-center text-center px-4 shadow-2xl">
            <div class="absolute inset-0 bg-gradient-to-t from-slate-950 via-slate-900/40 to-slate-900/20 z-0"></div>
            <div class="absolute inset-0 opacity-10 bg-[linear-gradient(to_right,#0f172a_1px,transparent_1px),linear-gradient(to_bottom,#0f172a_1px,transparent_1px)] bg-[size:4rem_4rem]"></div>

            <div class="relative z-10 max-w-2xl space-y-6">
                <span class="px-3 py-1 bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 rounded-full text-[10px] font-bold uppercase tracking-widest">
                    PSC Economic Modeling Engineering
                </span>
                <h1 class="text-4xl md:text-5xl font-extrabold text-white tracking-tight leading-tight">
                    Analyze & Find Your <span class="text-emerald-400">Project's Prosperity</span>
                </h1>
                <p class="text-sm text-slate-400 max-w-lg mx-auto leading-relaxed">
                    Simulasikan model fiskal terintegrasi, kelola data parameter keekonomian, serta kalkulasi nilai PSC Net Present Value (NPV) secara akurat dalam platform digital modern.
                </p>
                <div class="pt-4 flex flex-col sm:flex-row justify-center items-center gap-4">
                    <a href="pages/auth/register.php" class="w-full sm:w-auto bg-emerald-500 hover:bg-emerald-600 text-slate-950 font-bold px-6 py-3.5 rounded-xl text-xs flex items-center justify-center gap-2 shadow-lg transition-all">
                        Mulai Analisis Sekarang <i class="fas fa-arrow-right"></i>
                    </a>
                    <a href="pages/auth/login.php" class="w-full sm:w-auto bg-slate-800 hover:bg-slate-700 text-slate-200 border border-slate-700 font-bold px-6 py-3.5 rounded-xl text-xs flex items-center justify-center gap-2 transition-all">
                        <i class="fas fa-right-to-bracket"></i> Akses Console
                    </a>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
            <div class="bg-slate-900 border border-slate-800 p-6 rounded-2xl space-y-3 hover:border-slate-700 transition-all group">
                <div class="w-10 h-10 bg-emerald-500/10 text-emerald-400 rounded-xl flex items-center justify-center text-md group-hover:bg-emerald-500 group-hover:text-slate-950 transition-all">
                    <i class="fas fa-chart-line"></i>
                </div>
                <h3 class="text-sm font-bold text-white">Evaluasi Keekonomian</h3>
                <p class="text-xs text-slate-400 leading-relaxed">Kalkulasi parameter finansial, cashflow tahunan, pendapatan kotor, hingga perhitungan pajak secara komprehensif.</p>
            </div>

            <div class="bg-slate-900 border border-slate-800 p-6 rounded-2xl space-y-3 hover:border-slate-700 transition-all group">
                <div class="w-10 h-10 bg-emerald-500/10 text-emerald-400 rounded-xl flex items-center justify-center text-md group-hover:bg-emerald-500 group-hover:text-slate-950 transition-all">
                    <i class="fas fa-calculator"></i>
                </div>
                <h3 class="text-sm font-bold text-white">Automated Depreciation</h3>
                <p class="text-xs text-slate-400 leading-relaxed">Sistem depresiasi otomatis terintegrasi menggunakan metode Straight Line maupun Double Declining Balance.</p>
            </div>

            <div class="bg-slate-900 border border-slate-800 p-6 rounded-2xl space-y-3 hover:border-slate-700 transition-all group">
                <div class="w-10 h-10 bg-emerald-500/10 text-emerald-400 rounded-xl flex items-center justify-center text-md group-hover:bg-emerald-500 group-hover:text-slate-950 transition-all">
                    <i class="fas fa-chart-bar"></i>
                </div>
                <h3 class="text-sm font-bold text-white">Interaktif Charting</h3>
                <p class="text-xs text-slate-400 leading-relaxed">Visualisasi visual profil net cashflow dari tahun investasi awal (Tahun 0) hingga akhir masa kontrak kerja proyek.</p>
            </div>

            <div class="bg-slate-900 border border-slate-800 p-6 rounded-2xl space-y-3 hover:border-slate-700 transition-all group">
                <div class="w-10 h-10 bg-emerald-500/10 text-emerald-400 rounded-xl flex items-center justify-center text-md group-hover:bg-emerald-500 group-hover:text-slate-950 transition-all">
                    <i class="fas fa-file-invoice-dollar"></i>
                </div>
                <h3 class="text-sm font-bold text-white">Excel Synchronization</h3>
                <p class="text-xs text-slate-400 leading-relaxed">Struktur pemodelan finansial laporan disesuaikan secara matematis dengan format komersial spreadsheet Excel.</p>
            </div>
        </div>

    </main>

    <?php require_once "includes/footer.php"; ?>

</body>
</html>