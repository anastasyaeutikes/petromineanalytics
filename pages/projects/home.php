<?php
// home.php
require_once "../../includes/auth.php";
$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'];
$user_photo = null;
$user_role  = "Senior Oil & Gas Analyst";

require_once "../../config/config.php";

// Ambil data user
$sql_user = "SELECT profile_photo, role FROM users WHERE id = ?";
if ($stmt_user = $mysqli->prepare($sql_user)) {
    $stmt_user->bind_param("i", $user_id);
    if ($stmt_user->execute()) {
        $result_user = $stmt_user->get_result();
        if ($row_user = $result_user->fetch_assoc()) {
            $user_photo = $row_user['profile_photo'];
            if (!empty($user_photo) && strpos($user_photo, 'uploads/') === 0 && strpos($user_photo, 'assets/') === false) {
                $user_photo = 'assets/' . $user_photo;
            }
            if (!empty($row_user['role'])) {
                $user_role = $row_user['role'];
            }
        }
    }
    $stmt_user->close();
} 

$projects = []; 
$sql = "SELECT id, name, site_manager, invest_capital, investment_years, depreciation_method, decline_rate, created_at FROM projects WHERE user_id = ? ORDER BY created_at DESC";

if ($stmt = $mysqli->prepare($sql)) {
  $stmt->bind_param("i", $user_id);
  if ($stmt->execute()) {
    $result = $stmt->get_result();
    $projects = $result->fetch_all(MYSQLI_ASSOC);
  }
  $stmt->close();
}
$mysqli->close();
$base_path = "../../";
$page_title = "Dashboard Analisis Investasi";
require_once "../../includes/header.php";
?>
<body class="bg-slate-950 text-slate-100 min-h-screen flex">
    <?php require_once "../../includes/sidebar.php"; ?>
    <div class="flex-1 flex flex-col min-w-0">
        <?php 
        $breadcrumb_items = [];
        require_once "../../includes/topbar.php"; 
        ?>
        <main class="max-w-7xl w-full mx-auto px-6 py-10 flex-grow">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-8">
            <div><h1 class="text-2xl font-bold text-white tracking-tight">Manajemen Pengelolaan Lapangan Migas</h1><p class="text-xs text-slate-400 mt-1">Kelola simulasi PSC model fiskal dan indikator ekonomi makro.</p></div>
            <a href="create-project.php" class="bg-emerald-500 hover:bg-emerald-600 text-slate-950 font-bold px-5 py-2.5 rounded-xl text-xs flex items-center gap-2 shadow-lg"><i class="fas fa-plus"></i> Tambah Proyek</a>
        </div>
        <?php if(empty($projects)): ?>
            <div class="border border-dashed border-slate-800 rounded-2xl p-12 text-center">
                <i class="fas fa-folder-open text-4xl text-slate-700 mb-3"></i>
                <p class="text-sm text-slate-400 font-medium">Belum ada proyek yang dibuat.</p>
            </div>
        <?php else: ?>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <?php foreach($projects as $project): ?>
                    <div class="bg-slate-900 border border-slate-800 rounded-2xl flex flex-col justify-between hover:border-slate-700 transition-all">
                        <div class="p-6">
                            <h3 class="text-base font-bold text-white mb-1"><?php echo htmlspecialchars($project['name']); ?></h3>
                            <p class="text-xs text-slate-500 mb-4"><i class="fas fa-user-tie mr-1"></i> PM: <?php echo htmlspecialchars($project['site_manager']); ?></p>
                            <div class="grid grid-cols-2 gap-3 text-[11px] bg-slate-950/40 p-3 rounded-xl border border-slate-800/50">
                                <div><span class="text-slate-500 block">CAPEX</span><span class="text-emerald-400 font-bold">$<?php echo number_format($project['invest_capital']); ?></span></div>
                                <div><span class="text-slate-500 block">Durasi Kontrak</span><span class="text-slate-200 font-bold"><?php echo $project['investment_years']; ?> Tahun</span></div>
                                <div><span class="text-slate-500 block">Metode Depresiasi</span><span class="text-slate-200 font-bold"><?php echo htmlspecialchars($project['depreciation_method']); ?></span></div>
                                <div><span class="text-slate-500 block">Decline Rate</span><span class="text-amber-400 font-bold"><?php echo $project['decline_rate']; ?>% / Thn</span></div>
                            </div>
                        </div>
                        <div class="px-6 py-4 bg-slate-950/20 border-t border-slate-800/60 flex justify-between items-center rounded-b-2xl">
                            <span class="text-[10px] text-slate-500"><i class="far fa-calendar mr-1"></i><?php echo date('d M Y', strtotime($project['created_at'])); ?></span>
                            <a href="project-details.php?id=<?php echo $project['id']; ?>" class="bg-slate-800 text-slate-200 px-3 py-1.5 rounded-lg text-xs font-bold hover:bg-slate-700">Analisis <i class="fas fa-arrow-right ml-1"></i></a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        </main>
    </div>
</body>
</html>