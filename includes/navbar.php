<?php
// includes/navbar.php
$nav_full_width_class = (isset($nav_full_width) && !$nav_full_width) ? 'max-w-7xl mx-auto w-full rounded-b-xl' : '';
$bp = isset($base_path) ? $base_path : '';
?>
<nav class="border-b border-slate-800 bg-slate-900/50 backdrop-blur sticky top-0 z-50 px-6 py-4 flex justify-between items-center <?php echo $nav_full_width_class; ?>">
    <div class="flex items-center gap-3">
        <a href="<?php echo $bp; ?>index.php" class="flex items-center gap-3 hover:opacity-95 transition-opacity">
            <div class="w-9 h-9 bg-emerald-500 rounded-xl flex items-center justify-center text-slate-950 font-black">
                <i class="fas fa-oil-well"></i>
            </div>
            <span class="text-md font-bold text-white tracking-tight">Petromine <span class="text-emerald-400 font-normal">Analytics</span></span>
        </a>
    </div>
    <div class="flex items-center gap-4">
        <?php if (!empty($nav_back_url)): ?>
            <a href="<?php echo htmlspecialchars($nav_back_url); ?>" class="px-3 py-2 bg-slate-800 border border-slate-700 rounded-lg text-slate-400 hover:text-emerald-400 transition-all text-xs font-semibold">
                <i class="fas fa-arrow-left mr-1.5"></i><?php echo htmlspecialchars($nav_back_label ?? 'Kembali'); ?>
            </a>
        <?php elseif (isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true && !empty($_SESSION["user_name"])): ?>
            <?php
            $disp_name = $_SESSION["user_name"];
            $disp_role = isset($user_role) ? $user_role : (isset($_SESSION["user_role"]) ? $_SESSION["user_role"] : "Senior Oil & Gas Analyst");
            $disp_photo = isset($user_photo) ? $user_photo : null;
            $disp_photo_full = !empty($disp_photo) ? $bp . $disp_photo : null;
            ?>
            <a href="<?php echo $bp; ?>pages/auth/profile.php" class="hidden sm:flex items-center gap-3 hover:opacity-90 transition-all group">
                <?php if (!empty($disp_photo) && file_exists($disp_photo_full)): ?>
                    <img src="<?php echo htmlspecialchars($disp_photo_full); ?>" alt="Profile" class="w-10 h-10 rounded-full object-cover border-2 border-emerald-500">
                <?php else: ?>
                    <div class="w-10 h-10 rounded-full bg-emerald-500 flex items-center justify-center text-slate-950 font-bold text-sm">
                        <?php echo strtoupper(substr($disp_name, 0, 1)); ?>
                    </div>
                <?php endif; ?>
                <div class="text-left leading-tight">
                    <p class="text-xs font-bold text-slate-200 group-hover:text-emerald-400 transition-colors">
                        <?php echo htmlspecialchars($disp_name); ?>
                    </p>
                    <p class="text-[10px] text-slate-500 font-medium">
                        <?php echo htmlspecialchars($disp_role); ?>
                    </p>
                </div>
            </a>
        <?php endif; ?>

        <?php if (isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true): ?>
            <a href="<?php echo $bp; ?>pages/auth/logout.php" class="px-3 py-2 bg-slate-800 border border-slate-700 rounded-lg text-slate-400 hover:text-rose-400 transition-all text-xs">
                <i class="fas fa-power-off"></i>
            </a>
        <?php else: ?>
            <a href="<?php echo $bp; ?>pages/auth/login.php" class="px-4 py-2 text-xs font-bold text-slate-300 hover:text-white transition-all">Masuk</a>
            <a href="<?php echo $bp; ?>pages/auth/register.php" class="px-4 py-2 bg-emerald-500 hover:bg-emerald-600 text-slate-950 font-bold rounded-xl text-xs transition-all shadow-md shadow-emerald-500/10">Daftar Akun</a>
        <?php endif; ?>
    </div>
</nav>
