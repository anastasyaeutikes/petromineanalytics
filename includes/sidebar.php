<?php
// includes/sidebar.php
$bp = isset($base_path) ? $base_path : '';
$current_page = basename($_SERVER['PHP_SELF']);
?>
<aside class="w-64 bg-slate-900 border-r border-slate-800 flex flex-col h-screen sticky top-0 flex-shrink-0 z-40 hidden md:flex">
    <!-- Logo -->
    <div class="px-6 py-5 border-b border-slate-800/60 bg-slate-900">
        <a href="<?php echo $bp; ?>pages/projects/home.php" class="flex items-center gap-3">
            <div class="w-8 h-8 bg-emerald-500 rounded-lg flex items-center justify-center text-slate-950 font-black">
                <i class="fas fa-oil-well text-sm"></i>
            </div>
            <span class="text-sm font-bold text-white tracking-tight">Petromine <span class="text-emerald-400 font-normal">Analytics</span></span>
        </a>
    </div>

    <!-- Navigation Menu -->
    <nav class="flex-1 px-4 py-6 space-y-1.5 overflow-y-auto">
        <a href="<?php echo $bp; ?>pages/projects/home.php" 
           class="flex items-center gap-3 px-4 py-3 rounded-xl text-xs font-bold transition-all <?php echo ($current_page == 'home.php' || $current_page == 'project-details.php' || $current_page == 'edit-project.php') ? 'bg-emerald-500/10 text-emerald-400 border-l-4 border-emerald-500' : 'text-slate-400 hover:bg-slate-800/40 hover:text-slate-200'; ?>">
            <i class="fas fa-chart-line text-sm w-5"></i>
            Dashboard Proyek
        </a>
        <a href="<?php echo $bp; ?>pages/projects/create-project.php" 
           class="flex items-center gap-3 px-4 py-3 rounded-xl text-xs font-bold transition-all <?php echo ($current_page == 'create-project.php') ? 'bg-emerald-500/10 text-emerald-400 border-l-4 border-emerald-500' : 'text-slate-400 hover:bg-slate-800/40 hover:text-slate-200'; ?>">
            <i class="fas fa-plus-circle text-sm w-5"></i>
            Tambah Proyek
        </a>
        <a href="<?php echo $bp; ?>pages/auth/profile.php" 
           class="flex items-center gap-3 px-4 py-3 rounded-xl text-xs font-bold transition-all <?php echo ($current_page == 'profile.php' || $current_page == 'edit-profile.php') ? 'bg-emerald-500/10 text-emerald-400 border-l-4 border-emerald-500' : 'text-slate-400 hover:bg-slate-800/40 hover:text-slate-200'; ?>">
            <i class="fas fa-user text-sm w-5"></i>
            Profil Saya
        </a>
    </nav>

    <!-- Footer / User Widget inside Sidebar -->
    <div class="p-4 border-t border-slate-800/60 bg-slate-950/20">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-2">
                <div class="w-8 h-8 rounded-full bg-emerald-500 flex items-center justify-center text-slate-950 font-bold text-xs">
                    <?php echo strtoupper(substr($_SESSION['user_name'] ?? 'U', 0, 1)); ?>
                </div>
                <div class="leading-tight">
                    <p class="text-[11px] font-bold text-slate-200 max-w-[120px] truncate"><?php echo htmlspecialchars($_SESSION['user_name'] ?? 'User'); ?></p>
                    <p class="text-[9px] text-slate-500">Online</p>
                </div>
            </div>
            <a href="<?php echo $bp; ?>pages/auth/logout.php" class="text-slate-500 hover:text-rose-400 p-2 transition-colors" title="Log Out">
                <i class="fas fa-power-off text-xs"></i>
            </a>
        </div>
    </div>
</aside>
