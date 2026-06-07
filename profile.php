<?php
// profile.php
session_start();
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: login.php");
    exit;
}
$user_id = $_SESSION['user_id'];

require_once "config.php";

$success_msg = "";
$error_msg   = "";

// ─── Ambil data user dari database ───────────────────────────────────────────
$user = [];
$sql  = "SELECT id, name, email, password, created_at FROM users WHERE id = ?";
if ($stmt = $mysqli->prepare($sql)) {
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $user   = $result->fetch_assoc();
    $stmt->close();
}

// ─── Ambil jumlah proyek milik user ──────────────────────────────────────────
$total_projects = 0;
$sql_proj = "SELECT COUNT(*) as total FROM projects WHERE user_id = ?";
if ($stmt_p = $mysqli->prepare($sql_proj)) {
    $stmt_p->bind_param("i", $user_id);
    $stmt_p->execute();
    $total_projects = $stmt_p->get_result()->fetch_assoc()['total'];
    $stmt_p->close();
}

// ─── Handle: Simpan Perubahan ─────────────────────────────────────────────────
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $new_name = trim($_POST["name"] ?? "");

    if (empty($new_name)) {
        $error_msg = "Nama tidak boleh kosong.";
    } else {
        $sql_upd = "UPDATE users SET name = ?, updated_at = NOW() WHERE id = ?";
        if ($stmt_upd = $mysqli->prepare($sql_upd)) {
            $stmt_upd->bind_param("si", $new_name, $user_id);
            if ($stmt_upd->execute()) {
                $_SESSION['user_name'] = $new_name;
                $user['name']          = $new_name;
                $success_msg = "Profil berhasil diperbarui!";
            } else {
                $error_msg = "Gagal memperbarui profil ke database.";
            }
            $stmt_upd->close();
        }
    }
}

$mysqli->close();

// ─── Helper ───────────────────────────────────────────────────────────────────
$initials = strtoupper(substr($user['name'] ?? 'U', 0, 2));
$joined   = !empty($user['created_at']) ? date('d M Y', strtotime($user['created_at'])) : '-';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Profil Saya - Petromine Analytics</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
        .stat-card { background: linear-gradient(135deg, rgba(16,185,129,.08) 0%, rgba(15,23,42,0) 100%); }
    </style>
</head>
<body class="bg-slate-950 text-slate-100 min-h-screen">

<!-- ═══ NAVBAR ═══════════════════════════════════════════════════════════════ -->
<nav class="border-b border-slate-800 bg-slate-900/50 backdrop-blur sticky top-0 z-50 px-6 py-4 flex justify-between items-center">
    <div class="flex items-center gap-3">
        <div class="w-9 h-9 bg-emerald-500 rounded-xl flex items-center justify-center text-slate-950 font-black">
            <i class="fas fa-oil-well"></i>
        </div>
        <span class="text-md font-bold text-white tracking-tight">Petromine <span class="text-emerald-400 font-normal">Analytics</span></span>
    </div>
    <div class="flex items-center gap-3">
        <a href="home.php" class="px-3 py-2 bg-slate-800 border border-slate-700 rounded-lg text-slate-400 hover:text-emerald-400 transition-all text-xs font-semibold">
            <i class="fas fa-arrow-left mr-1.5"></i>Dashboard
        </a>
        <a href="logout.php" class="px-3 py-2 bg-slate-800 border border-slate-700 rounded-lg text-slate-400 hover:text-rose-400 transition-all text-xs">
            <i class="fas fa-power-off"></i>
        </a>
    </div>
</nav>

<!-- ═══ MAIN ══════════════════════════════════════════════════════════════════ -->
<main class="max-w-2xl mx-auto px-6 py-10">

    <!-- Header -->
    <div class="mb-7">
        <h1 class="text-2xl font-bold text-white tracking-tight">Profil Saya</h1>
        <p class="text-xs text-slate-500 mt-1">Kelola informasi akun Anda.</p>
    </div>

    <!-- Alert Success -->
    <?php if (!empty($success_msg)): ?>
    <div class="mb-5 flex items-center gap-3 bg-emerald-500/10 border border-emerald-500/30 text-emerald-400 text-xs font-semibold px-4 py-3 rounded-xl">
        <i class="fas fa-check-circle text-base"></i> <?php echo htmlspecialchars($success_msg); ?>
    </div>
    <?php endif; ?>

    <!-- Alert Error -->
    <?php if (!empty($error_msg)): ?>
    <div class="mb-5 flex items-center gap-3 bg-rose-500/10 border border-rose-500/30 text-rose-400 text-xs font-semibold px-4 py-3 rounded-xl">
        <i class="fas fa-exclamation-circle text-base"></i> <?php echo htmlspecialchars($error_msg); ?>
    </div>
    <?php endif; ?>

    <!-- ── CARD: Ringkasan Akun ─────────────────────────────────────────────── -->
    <div class="bg-slate-900 border border-slate-800 rounded-2xl p-5 mb-5 flex items-center gap-5">
        <!-- Avatar inisial -->
        <div class="w-14 h-14 rounded-2xl bg-emerald-500/15 border border-slate-700 flex items-center justify-center flex-shrink-0">
            <span class="text-lg font-black text-emerald-400"><?php echo $initials; ?></span>
        </div>
        <div class="flex-1 min-w-0">
            <p class="text-sm font-bold text-white truncate"><?php echo htmlspecialchars($user['name'] ?? '-'); ?></p>
            <p class="text-xs text-slate-500 truncate"><?php echo htmlspecialchars($user['email'] ?? '-'); ?></p>
            <p class="text-[10px] text-slate-600 mt-0.5">Senior Oil &amp; Gas Analyst</p>
        </div>
        <div class="flex gap-3 text-center flex-shrink-0">
            <div class="stat-card border border-slate-800 rounded-xl px-4 py-2.5">
                <p class="text-xl font-black text-emerald-400"><?php echo $total_projects; ?></p>
                <p class="text-[10px] text-slate-500 font-medium">Proyek</p>
            </div>
            <div class="stat-card border border-slate-800 rounded-xl px-4 py-2.5">
                <p class="text-xs font-bold text-slate-300"><?php echo $joined; ?></p>
                <p class="text-[10px] text-slate-500 font-medium">Bergabung</p>
            </div>
        </div>
    </div>

    <!-- ══ FORM UPDATE PROFIL ════════════════════════════════════════════════ -->
    <form method="POST">

        <!-- ── CARD: Informasi Akun ──────────────────────────────────────── -->
        <div class="bg-slate-900 border border-slate-800 rounded-2xl p-6 mb-5">
            <h2 class="text-xs font-bold text-slate-400 uppercase tracking-widest mb-5 flex items-center gap-2">
                <i class="fas fa-id-card text-emerald-400"></i> Informasi Akun
            </h2>
            <div class="space-y-4">

                <!-- Nama (editable) -->
                <div>
                    <label class="block text-xs font-semibold text-slate-400 mb-1.5">
                        Nama Lengkap <span class="text-emerald-500">*</span>
                    </label>
                    <input type="text" name="name"
                        value="<?php echo htmlspecialchars($user['name'] ?? ''); ?>"
                        class="w-full bg-slate-950/60 border border-slate-700 text-slate-100 text-sm rounded-xl px-4 py-2.5 focus:outline-none focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500/30 transition-all placeholder-slate-600"
                        placeholder="Masukkan nama lengkap" required>
                </div>

                <!-- Email (tidak bisa diubah) -->
                <div>
                    <label class="block text-xs font-semibold text-slate-400 mb-1.5">Email</label>
                    <div class="relative">
                        <input type="email"
                            value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>"
                            class="w-full bg-slate-950/30 border border-slate-800 text-slate-500 text-sm rounded-xl px-4 py-2.5 pr-36 cursor-not-allowed"
                            readonly tabindex="-1">
                        <span class="absolute right-3 top-1/2 -translate-y-1/2 text-[9px] text-slate-600 font-bold bg-slate-800 px-2 py-1 rounded tracking-wide whitespace-nowrap">TIDAK DAPAT DIUBAH</span>
                    </div>
                </div>

                <!-- Password (read-only + see/hide) -->
                <div>
                    <label class="block text-xs font-semibold text-slate-400 mb-1.5">Password</label>
                    <div class="relative">
                        <input type="password" id="password-field"
                            value="<?php echo htmlspecialchars($user['password'] ?? ''); ?>"
                            class="w-full bg-slate-950/30 border border-slate-800 text-slate-400 text-sm rounded-xl px-4 py-2.5 pr-28 cursor-not-allowed tracking-widest"
                            readonly tabindex="-1">
                        <div class="absolute right-3 top-1/2 -translate-y-1/2 flex items-center gap-2">
                            <button type="button" onclick="togglePassword()"
                                class="text-slate-500 hover:text-emerald-400 transition-colors w-6 h-6 flex items-center justify-center"
                                title="Tampilkan / Sembunyikan password">
                                <i id="toggle-pw-icon" class="fas fa-eye text-sm"></i>
                            </button>
                            <span class="text-[9px] text-slate-600 font-bold bg-slate-800 px-2 py-1 rounded tracking-wide">READ ONLY</span>
                        </div>
                    </div>
                    <p class="text-[10px] text-slate-600 mt-1.5">
                        <i class="fas fa-lock mr-1"></i>Password tidak dapat diubah di halaman ini.
                    </p>
                </div>

                <!-- Meta info -->
                <div class="grid grid-cols-2 gap-3 pt-1">
                    <div class="bg-slate-950/40 border border-slate-800/60 rounded-xl px-4 py-3">
                        <p class="text-[10px] text-slate-600 font-semibold mb-0.5 uppercase tracking-wide">Bergabung Sejak</p>
                        <p class="text-xs font-bold text-slate-300"><?php echo $joined; ?></p>
                    </div>
                    <div class="bg-slate-950/40 border border-slate-800/60 rounded-xl px-4 py-3">
                        <p class="text-[10px] text-slate-600 font-semibold mb-0.5 uppercase tracking-wide">Total Proyek</p>
                        <p class="text-xs font-bold text-emerald-400"><?php echo $total_projects; ?> Proyek Aktif</p>
                    </div>
                </div>

            </div>
        </div>

        <!-- Tombol Simpan -->
        <div class="flex justify-end gap-3">
            <a href="home.php" class="px-5 py-2.5 bg-slate-800 border border-slate-700 text-slate-300 hover:text-white font-semibold text-xs rounded-xl transition-all">
                Batal
            </a>
            <button type="submit" class="bg-emerald-500 hover:bg-emerald-600 active:bg-emerald-700 text-slate-950 font-bold px-6 py-2.5 rounded-xl text-xs flex items-center gap-2 shadow-lg transition-all">
                <i class="fas fa-save"></i> Simpan Perubahan
            </button>
        </div>
    </form>

</main>

<script>
    // Toggle lihat/sembunyikan password
    function togglePassword() {
        const field = document.getElementById('password-field');
        const icon  = document.getElementById('toggle-pw-icon');
        if (field.type === 'password') {
            field.type = 'text';
            field.classList.remove('tracking-widest');
            icon.classList.replace('fa-eye', 'fa-eye-slash');
        } else {
            field.type = 'password';
            field.classList.add('tracking-widest');
            icon.classList.replace('fa-eye-slash', 'fa-eye');
        }
    }
</script>
</body>
</html>