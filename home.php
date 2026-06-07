<?php
// home.php
session_start();
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
  header("location: login.php");
  exit;
}
$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'];

require_once "config.php"; 

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
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Dashboard Analisis Investasi - Petromine Analytics</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>body { font-family: 'Plus Jakarta Sans', sans-serif; }</style>
</head>
<body class="bg-slate-950 text-slate-100 min-h-screen">
    <nav class="border-b border-slate-800 bg-slate-900/50 backdrop-blur sticky top-0 z-50 px-6 py-4 flex justify-between items-center">
        <div class="flex items-center gap-3">
            <div class="w-9 h-9 bg-emerald-500 rounded-xl flex items-center justify-center text-slate-950 font-black"><i class="fas fa-oil-well"></i></div>
            <span class="text-md font-bold text-white tracking-tight">Petromine <span class="text-emerald-400 font-normal">Analytics</span></span>
        </div>
        <div class="flex items-center gap-4">
            <!-- USERNAME DIBUAT CLICKABLE MENUJU PROFILE PAGE -->
            <a href="profile.php" class="text-right hidden sm:block hover:opacity-80 transition-opacity group">
                <p class="text-xs font-bold text-slate-200 group-hover:text-emerald-400 transition-colors"><?php echo htmlspecialchars($user_name); ?></p>
                <p class="text-[10px] text-slate-500 font-medium">Senior Oil & Gas Analyst</p>
            </a>
            <a href="logout.php" class="px-3 py-2 bg-slate-800 border border-slate-700 rounded-lg text-slate-400 hover:text-rose-400 transition-all text-xs"><i class="fas fa-power-off"></i></a>
        </div>
    </nav>
    <main class="max-w-7xl mx-auto px-6 py-10">
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
</body>
</html>