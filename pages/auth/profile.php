<?php
// profile.php
require_once "../../includes/auth.php";
$user_id = $_SESSION['user_id'];

require_once "../../config/config.php";

$user = [];
$sql  = "SELECT id, name, email, password, role, profile_photo, created_at FROM users WHERE id = ?";
if ($stmt = $mysqli->prepare($sql)) {
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}

if (!empty($user['profile_photo']) && strpos($user['profile_photo'], 'uploads/') === 0 && strpos($user['profile_photo'], 'assets/') === false) {
    $user['profile_photo'] = 'assets/' . $user['profile_photo'];
}

$total_projects = 0;
$sql_proj = "SELECT COUNT(*) as total FROM projects WHERE user_id = ?";
if ($stmt_p = $mysqli->prepare($sql_proj)) {
    $stmt_p->bind_param("i", $user_id);
    $stmt_p->execute();
    $total_projects = $stmt_p->get_result()->fetch_assoc()['total'];
    $stmt_p->close();
}

$mysqli->close();

$initials     = strtoupper(substr($user['name'] ?? 'U', 0, 2));
$joined       = !empty($user['created_at']) ? date('d M Y', strtotime($user['created_at'])) : '-';
$current_role = $user['role'] ?? '-';
$has_photo    = !empty($user['profile_photo']) && file_exists('../../' . $user['profile_photo']);
?>
<?php
$base_path = "../../";
$page_title = "Profil Saya";
$extra_head = "<style>.stat-card { background: linear-gradient(135deg, rgba(16,185,129,.08) 0%, rgba(15,23,42,0) 100%); }</style>";
require_once "../../includes/header.php";
?>
<body class="bg-slate-950 text-slate-100 min-h-screen">

<?php
$nav_back_url = "../projects/home.php";
$nav_back_label = "Dashboard";
require_once "../../includes/navbar.php";
?>

<main class="max-w-4xl mx-auto px-6 py-10">

    <div class="mb-7 flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-white tracking-tight">Profil Saya</h1>
            <p class="text-xs text-slate-500 mt-1">Informasi akun Anda.</p>
        </div>
        <a href="edit-profile.php"
           class="inline-flex items-center gap-2 bg-emerald-500 hover:bg-emerald-600 active:bg-emerald-700 text-slate-950 font-bold px-5 py-2.5 rounded-xl text-xs shadow-lg transition-all">
            <i class="fas fa-pen"></i> Edit Profile
        </a>
    </div>

    <!-- Two-column, equal height -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-5" style="align-items: stretch;">

        <!-- KOLOM KIRI -->
        <div class="flex flex-col gap-5">

            <!-- Card: Avatar + Nama -->
            <div class="bg-slate-900 border border-slate-800 rounded-2xl p-7 flex flex-col items-center text-center gap-4">
                <div class="w-24 h-24 rounded-full overflow-hidden bg-emerald-500/15 border-2 border-emerald-500/40 flex items-center justify-center flex-shrink-0"
                     style="box-shadow: 0 0 0 5px rgba(16,185,129,0.10);">
                    <?php if ($has_photo): ?>
                        <img src="<?php echo htmlspecialchars('../../' . $user['profile_photo']); ?>" class="w-full h-full object-cover" alt="Foto Profil">
                    <?php else: ?>
                        <span class="text-3xl font-black text-emerald-400"><?php echo $initials; ?></span>
                    <?php endif; ?>
                </div>
                <div>
                    <p class="text-lg font-bold text-white"><?php echo htmlspecialchars($user['name'] ?? '-'); ?></p>
                    <p class="text-xs text-slate-500 mt-0.5"><?php echo htmlspecialchars($user['email'] ?? '-'); ?></p>
                    <span class="inline-block mt-2 text-[10px] font-semibold text-emerald-400 bg-emerald-500/10 border border-emerald-500/20 px-2.5 py-1 rounded-md">
                        <?php echo htmlspecialchars($current_role); ?>
                    </span>
                </div>
            </div>

            <!-- Card: Statistik (fill remaining height) -->
            <div class="flex-1 grid grid-cols-2 gap-3">
                <div class="stat-card bg-slate-900 border border-slate-800 rounded-2xl flex flex-col items-center justify-center py-6">
                    <p class="text-3xl font-black text-emerald-400"><?php echo $total_projects; ?></p>
                    <p class="text-[10px] text-slate-500 font-medium mt-1">Total Proyek</p>
                </div>
                <div class="stat-card bg-slate-900 border border-slate-800 rounded-2xl flex flex-col items-center justify-center py-6">
                    <p class="text-sm font-bold text-slate-300"><?php echo $joined; ?></p>
                    <p class="text-[10px] text-slate-500 font-medium mt-1">Bergabung</p>
                </div>
            </div>

        </div>

        <!-- KOLOM KANAN: full height -->
        <div class="bg-slate-900 border border-slate-800 rounded-2xl p-6 flex flex-col">
            <h2 class="text-xs font-bold text-slate-400 uppercase tracking-widest mb-5 flex items-center gap-2">
                <i class="fas fa-id-card text-emerald-400"></i> Detail Akun
            </h2>

            <!-- Detail rows — distributed evenly -->
            <div class="flex-1 flex flex-col justify-between">

                <div class="py-3 border-b border-slate-800/80">
                    <p class="text-[10px] text-slate-600 font-semibold uppercase tracking-wide mb-1">Nama Lengkap</p>
                    <p class="text-sm font-semibold text-slate-200"><?php echo htmlspecialchars($user['name'] ?? '-'); ?></p>
                </div>

                <div class="py-3 border-b border-slate-800/80">
                    <p class="text-[10px] text-slate-600 font-semibold uppercase tracking-wide mb-1">Role</p>
                    <p class="text-sm font-semibold text-slate-200"><?php echo htmlspecialchars($current_role); ?></p>
                </div>

                <div class="py-3 border-b border-slate-800/80">
                    <p class="text-[10px] text-slate-600 font-semibold uppercase tracking-wide mb-1">Email</p>
                    <p class="text-sm font-semibold text-slate-200"><?php echo htmlspecialchars($user['email'] ?? '-'); ?></p>
                </div>

                <div class="py-3 border-b border-slate-800/80">
                    <p class="text-[10px] text-slate-600 font-semibold uppercase tracking-wide mb-1">Password</p>
                    <div class="flex items-center justify-between gap-2">
                        <p id="pw-display" class="text-sm font-semibold text-slate-200 tracking-widest break-all min-w-0">••••••••••</p>
                        <button type="button" onclick="togglePw()" class="text-slate-500 hover:text-emerald-400 transition-colors ml-1 flex-shrink-0">
                            <i id="pw-icon" class="fas fa-eye text-xs"></i>
                        </button>
                    </div>
                </div>

                <div class="py-3">
                    <p class="text-[10px] text-slate-600 font-semibold uppercase tracking-wide mb-1">Bergabung Sejak</p>
                    <p class="text-sm font-semibold text-slate-200"><?php echo $joined; ?></p>
                </div>

            </div>
        </div>

    </div>
</main>

<script>
const realPw = "<?php echo addslashes($_SESSION['user_password'] ?? '(Silakan login kembali untuk melihat password)'); ?>";
let shown = false;
function togglePw() {
    shown = !shown;
    document.getElementById('pw-display').textContent = shown ? realPw : '••••••••••';
    document.getElementById('pw-display').classList.toggle('tracking-widest', !shown);
    document.getElementById('pw-icon').className = shown ? 'fas fa-eye-slash text-xs' : 'fas fa-eye text-xs';
}
</script>
</body>
</html>