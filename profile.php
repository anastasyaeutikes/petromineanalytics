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

// Pilihan role yang tersedia
$available_roles = [
    "Senior Oil & Gas Analyst",
    "Junior Oil & Gas Analyst",
    "Petroleum Engineer",
    "Reservoir Engineer",
    "Production Engineer",
    "Drilling Engineer",
    "Geologist",
    "Geophysicist",
    "HSE Engineer",
    "Project Manager",
    "Financial Analyst",
    "Business Development Manager",
];

// ─── Ambil data user dari database ───────────────────────────────────────────
$user = [];
$sql  = "SELECT id, name, email, password, role, profile_photo, created_at FROM users WHERE id = ?";
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

// ─── Handle: Hapus Foto ───────────────────────────────────────────────────────
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['action']) && $_POST['action'] === 'delete_photo') {
    if (!empty($user['profile_photo']) && file_exists($user['profile_photo'])) {
        unlink($user['profile_photo']);
    }
    $sql_del = "UPDATE users SET profile_photo = NULL, updated_at = NOW() WHERE id = ?";
    if ($stmt_del = $mysqli->prepare($sql_del)) {
        $stmt_del->bind_param("i", $user_id);
        $stmt_del->execute();
        $stmt_del->close();
    }
    $user['profile_photo'] = null;
    $success_msg = "Foto profil berhasil dihapus.";
}

// ─── Handle: Simpan Perubahan ─────────────────────────────────────────────────
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['action']) && $_POST['action'] === 'update_profile') {
    $new_name   = trim($_POST["name"] ?? "");
    $new_role   = trim($_POST["role"] ?? "");
    $photo_path = $user['profile_photo'];

    if (empty($new_name)) {
        $error_msg = "Nama tidak boleh kosong.";
    } elseif (!in_array($new_role, $available_roles)) {
        $error_msg = "Role tidak valid.";
    } else {
        // Upload foto baru jika ada
        if (!empty($_FILES["profile_photo"]["name"])) {
            $allowed_types = ["image/jpeg", "image/png", "image/gif", "image/webp"];
            $file_type     = mime_content_type($_FILES["profile_photo"]["tmp_name"]);

            if (!in_array($file_type, $allowed_types)) {
                $error_msg = "Format foto tidak didukung. Gunakan JPG, PNG, GIF, atau WEBP.";
            } elseif ($_FILES["profile_photo"]["size"] > 2 * 1024 * 1024) {
                $error_msg = "Ukuran foto maksimal 2MB.";
            } else {
                $upload_dir = "uploads/profile/";
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0755, true);
                }
                // Hapus foto lama jika ada
                if (!empty($user['profile_photo']) && file_exists($user['profile_photo'])) {
                    unlink($user['profile_photo']);
                }
                $ext       = strtolower(pathinfo($_FILES["profile_photo"]["name"], PATHINFO_EXTENSION));
                $filename  = "user_" . $user_id . "_" . time() . "." . $ext;
                $dest_path = $upload_dir . $filename;

                if (move_uploaded_file($_FILES["profile_photo"]["tmp_name"], $dest_path)) {
                    $photo_path = $dest_path;
                } else {
                    $error_msg = "Gagal mengupload foto. Periksa permission folder uploads/profile/.";
                }
            }
        }

        if (empty($error_msg)) {
            $sql_upd = "UPDATE users SET name = ?, role = ?, profile_photo = ?, updated_at = NOW() WHERE id = ?";
            if ($stmt_upd = $mysqli->prepare($sql_upd)) {
                $stmt_upd->bind_param("sssi", $new_name, $new_role, $photo_path, $user_id);
                if ($stmt_upd->execute()) {
                    $_SESSION['user_name']  = $new_name;
                    $_SESSION['user_role']  = $new_role;
                    $user['name']           = $new_name;
                    $user['role']           = $new_role;
                    $user['profile_photo']  = $photo_path;
                    $success_msg = "Profil berhasil diperbarui!";
                } else {
                    $error_msg = "Gagal memperbarui profil ke database.";
                }
                $stmt_upd->close();
            }
        }
    }
}

$mysqli->close();

// ─── Helper ───────────────────────────────────────────────────────────────────
$initials     = strtoupper(substr($user['name'] ?? 'U', 0, 2));
$joined       = !empty($user['created_at']) ? date('d M Y', strtotime($user['created_at'])) : '-';
$current_role = $user['role'] ?? $available_roles[0];
$has_photo    = !empty($user['profile_photo']) && file_exists($user['profile_photo']);
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
        select.custom-select {
            appearance: none;
            -webkit-appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='%2364748b' stroke-width='2.5' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 14px center;
        }
        select.custom-select option { background-color: #0f172a; color: #e2e8f0; }
        input[type="file"] { display: none; }
        .photo-overlay { opacity: 0; transition: opacity .2s ease; }
        .photo-wrapper:hover .photo-overlay { opacity: 1; }
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
   <div class="bg-slate-900 border border-slate-800 rounded-2xl p-5 mb-5 flex justify-between items-center">
        <div class="flex-1 min-w-0">
            <p class="text-sm font-bold text-white truncate"><?php echo htmlspecialchars($user['name'] ?? '-'); ?></p>
            <p class="text-xs text-slate-500 truncate"><?php echo htmlspecialchars($user['email'] ?? '-'); ?></p>
            <span class="inline-block mt-1 text-[10px] font-semibold text-emerald-400 bg-emerald-500/10 border border-emerald-500/20 px-2 py-0.5 rounded-md">
                <?php echo htmlspecialchars($current_role); ?>
            </span>
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
    <form method="POST" enctype="multipart/form-data">
        <input type="hidden" name="action" value="update_profile">

        <!-- ── CARD: Foto Profil ─────────────────────────────────────────── -->
        <div class="bg-slate-900 border border-slate-800 rounded-2xl p-6 mb-5">
            <h2 class="text-xs font-bold text-slate-400 uppercase tracking-widest mb-5 flex items-center gap-2">
                <i class="fas fa-camera text-emerald-400"></i> Foto Profil
            </h2>
            <div class="flex items-center gap-5">
                <!-- Avatar klik untuk ganti -->
                <label for="profile_photo_input" class="photo-wrapper relative cursor-pointer flex-shrink-0">
                    <div class="w-20 h-20 rounded-full overflow-hidden bg-emerald-500/15 border-2 border-emerald-500/40 flex items-center justify-center"
                         style="box-shadow: 0 0 0 4px rgba(16,185,129,0.10);">
                        <?php if ($has_photo): ?>
                            <img id="photo-preview" src="<?php echo htmlspecialchars($user['profile_photo']); ?>" class="w-full h-full object-cover" alt="Foto Profil">
                        <?php else: ?>
                            <img id="photo-preview" src="" alt="" class="w-full h-full object-cover hidden">
                            <span id="photo-initials" class="text-2xl font-black text-emerald-400"><?php echo $initials; ?></span>
                        <?php endif; ?>
                    </div>
                    <div class="photo-overlay absolute inset-0 bg-slate-950/75 rounded-full flex flex-col items-center justify-center gap-0.5">
                        <i class="fas fa-camera text-white text-sm"></i>
                        <span class="text-[9px] text-white font-bold tracking-wide">edit</span>
                    </div>
                </label>
                <input type="file" id="profile_photo_input" name="profile_photo"
                    accept="image/jpeg,image/png,image/gif,image/webp"
                    onchange="previewPhoto(this)">

                <div class="flex-1">
                    <div class="flex flex-wrap gap-2 mb-2.5">
                        <label for="profile_photo_input"
                            class="cursor-pointer inline-flex items-center gap-1.5 bg-slate-800 hover:bg-slate-700 border border-slate-700 text-slate-300 text-xs font-semibold px-3 py-2 rounded-lg transition-all">
                            <i class="fas fa-upload text-emerald-400 text-[11px]"></i>
                            <?php echo $has_photo ? 'Edit Photo' : 'Upload Foto'; ?>
                        </label>
                        <?php if ($has_photo): ?>
                        <button type="button" onclick="confirmDeletePhoto()"
                            class="inline-flex items-center gap-1.5 bg-rose-500/10 hover:bg-rose-500/20 border border-rose-500/30 text-rose-400 text-xs font-semibold px-3 py-2 rounded-lg transition-all">
                            <i class="fas fa-trash-alt text-[11px]"></i> Delete
                        </button>
                        <?php endif; ?>
                    </div>
                    <p class="text-[10px] text-slate-600">
                        <i class="fas fa-info-circle mr-1"></i>Format: JPG, PNG, WEBP &bull; Maks. 2MB
                    </p>
                </div>
            </div>
        </div>

        <!-- ── CARD: Informasi Akun ──────────────────────────────────────── -->
        <div class="bg-slate-900 border border-slate-800 rounded-2xl p-6 mb-5">
            <h2 class="text-xs font-bold text-slate-400 uppercase tracking-widest mb-5 flex items-center gap-2">
                <i class="fas fa-id-card text-emerald-400"></i> Account Information
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

                <!-- Role (editable dropdown) -->
                <div>
                    <label class="block text-xs font-semibold text-slate-400 mb-1.5">
                        Role<span class="text-emerald-500">*</span>
                    </label>
                    <select name="role"
                        class="custom-select w-full bg-slate-950/60 border border-slate-700 text-slate-100 text-sm rounded-xl px-4 py-2.5 pr-10 focus:outline-none focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500/30 transition-all cursor-pointer">
                        <?php foreach ($available_roles as $role): ?>
                            <option value="<?php echo htmlspecialchars($role); ?>"
                                <?php echo ($current_role === $role) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($role); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <p class="text-[10px] text-slate-600 mt-1.5">
                        <i class="fas fa-info-circle mr-1"></i>Role ditampilkan di halaman dashboard dan profil.
                    </p>
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
                Cancel 
            </a>
            <button type="submit" class="bg-emerald-500 hover:bg-emerald-600 active:bg-emerald-700 text-slate-950 font-bold px-6 py-2.5 rounded-xl text-xs flex items-center gap-2 shadow-lg transition-all">
                <i class="fas fa-save"></i> Save Changes
            </button>
        </div>
    </form>

</main>

<!-- ═══ MODAL KONFIRMASI HAPUS FOTO ══════════════════════════════════════════ -->
<div id="delete-modal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-slate-950/80 backdrop-blur-sm px-4">
    <div class="bg-slate-900 border border-slate-700 rounded-2xl p-6 max-w-sm w-full shadow-2xl">
        <div class="w-12 h-12 bg-rose-500/15 border border-rose-500/30 rounded-xl flex items-center justify-center mx-auto mb-4">
            <i class="fas fa-trash-alt text-rose-400 text-lg"></i>
        </div>
        <h3 class="text-sm font-bold text-white text-center mb-1">Hapus Foto Profil?</h3>
        <p class="text-xs text-slate-500 text-center mb-6">Foto Anda akan dihapus secara permanen dan tidak dapat dikembalikan.</p>
        <div class="flex gap-3">
            <button type="button" onclick="closeDeleteModal()"
                class="flex-1 px-4 py-2.5 bg-slate-800 border border-slate-700 text-slate-300 hover:text-white font-semibold text-xs rounded-xl transition-all">
                Batal
            </button>
            <form method="POST" class="flex-1">
                <input type="hidden" name="action" value="delete_photo">
                <button type="submit"
                    class="w-full px-4 py-2.5 bg-rose-500 hover:bg-rose-600 text-white font-bold text-xs rounded-xl transition-all">
                    <i class="fas fa-trash-alt mr-1.5"></i>Ya, Hapus
                </button>
            </form>
        </div>
    </div>
</div>

<script>
    // Preview foto sebelum upload
    function previewPhoto(input) {
        if (input.files && input.files[0]) {
            const reader = new FileReader();
            reader.onload = function(e) {
                const preview  = document.getElementById('photo-preview');
                const initials = document.getElementById('photo-initials');
                preview.src = e.target.result;
                preview.classList.remove('hidden');
                if (initials) initials.classList.add('hidden');
            };
            reader.readAsDataURL(input.files[0]);
        }
    }

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

    // Modal hapus foto
    function confirmDeletePhoto() {
        document.getElementById('delete-modal').classList.remove('hidden');
    }
    function closeDeleteModal() {
        document.getElementById('delete-modal').classList.add('hidden');
    }
    document.getElementById('delete-modal').addEventListener('click', function(e) {
        if (e.target === this) closeDeleteModal();
    });
</script>
</body>
</html>