<?php
// includes/topbar.php
$bp = isset($base_path) ? $base_path : '';
?>
<header class="bg-slate-900/40 border-b border-slate-800/60 px-6 py-4 flex justify-between items-center sticky top-0 z-30 backdrop-blur w-full">
    <!-- Left: Breadcrumbs -->
    <div class="flex items-center gap-2 text-xs">
        <a href="<?php echo $bp; ?>pages/projects/home.php" class="text-slate-500 hover:text-emerald-400 font-medium transition-colors">Dashboard</a>
        <?php if (isset($breadcrumb_items) && is_array($breadcrumb_items)): ?>
            <?php foreach ($breadcrumb_items as $item): ?>
                <span class="text-slate-700">/</span>
                <?php if (!empty($item['url'])): ?>
                    <a href="<?php echo $item['url']; ?>" class="text-slate-500 hover:text-emerald-400 font-medium transition-colors"><?php echo htmlspecialchars($item['label']); ?></a>
                <?php else: ?>
                    <span class="text-slate-300 font-bold"><?php echo htmlspecialchars($item['label']); ?></span>
                <?php endif; ?>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- Right: Mobile Nav Trigger & User Info -->
    <div class="flex items-center gap-4">
        <!-- Desktop Back Button if set -->
        <?php if (!empty($topbar_back_url)): ?>
            <a href="<?php echo $topbar_back_url; ?>" class="hidden sm:inline-flex items-center gap-1.5 px-3 py-1.5 bg-slate-800 border border-slate-700 rounded-lg text-slate-400 hover:text-emerald-400 transition-all text-[11px] font-bold">
                <i class="fas fa-arrow-left"></i> <?php echo htmlspecialchars($topbar_back_label ?? 'Kembali'); ?>
            </a>
        <?php endif; ?>

        <!-- Mobile Menu Link/Icons directly visible for easy access -->
        <div class="flex md:hidden items-center gap-2">
            <a href="<?php echo $bp; ?>pages/projects/home.php" class="text-slate-400 hover:text-emerald-400 p-2" title="Dashboard">
                <i class="fas fa-home text-xs"></i>
            </a>
            <a href="<?php echo $bp; ?>pages/projects/create-project.php" class="text-slate-400 hover:text-emerald-400 p-2" title="Tambah Proyek">
                <i class="fas fa-plus-circle text-xs"></i>
            </a>
            <a href="<?php echo $bp; ?>pages/auth/profile.php" class="text-slate-400 hover:text-emerald-400 p-2" title="Profil Saya">
                <i class="fas fa-user text-xs"></i>
            </a>
            <a href="<?php echo $bp; ?>pages/auth/logout.php" class="text-slate-400 hover:text-rose-400 p-2" title="Keluar">
                <i class="fas fa-power-off text-xs"></i>
            </a>
        </div>
    </div>
</header>
