<?php
// edit_profile.php
session_start();
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: login.php");
    exit;
}
$user_id = $_SESSION['user_id'];

require_once "config.php";

$error_msg = "";

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

$user = [];
$sql  = "SELECT id, name, email, role, profile_photo FROM users WHERE id = ?";
if ($stmt = $mysqli->prepare($sql)) {
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    $stmt->close();
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
    $_SESSION['success_msg'] = "Foto profil berhasil dihapus.";
    $mysqli->close();
    header("location: profile.php");
    exit;
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
        if (!empty($_FILES["profile_photo"]["name"])) {
            $allowed_types = ["image/jpeg", "image/png", "image/gif", "image/webp"];
            $file_type     = mime_content_type($_FILES["profile_photo"]["tmp_name"]);
            if (!in_array($file_type, $allowed_types)) {
                $error_msg = "Format foto tidak didukung. Gunakan JPG, PNG, GIF, atau WEBP.";
            } elseif ($_FILES["profile_photo"]["size"] > 2 * 1024 * 1024) {
                $error_msg = "Ukuran foto maksimal 2MB.";
            } else {
                $upload_dir = "uploads/profile/";
                if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);
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
                    $_SESSION['user_name']   = $new_name;
                    $_SESSION['user_role']   = $new_role;
                    $_SESSION['success_msg'] = "Profil berhasil diperbarui!";
                    $mysqli->close();
                    header("location: profile.php");
                    exit;
                } else {
                    $error_msg = "Gagal memperbarui profil ke database.";
                }
                $stmt_upd->close();
            }
        }
    }
}

$mysqli->close();

$initials     = strtoupper(substr($user['name'] ?? 'U', 0, 2));
$current_role = $user['role'] ?? $available_roles[0];
$has_photo    = !empty($user['profile_photo']) && file_exists($user['profile_photo']);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Edit Profil - Petromine Analytics</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
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

<nav class="border-b border-slate-800 bg-slate-900/50 backdrop-blur sticky top-0 z-50 px-6 py-4 flex justify-between items-center">
    <div class="flex items-center gap-3">
        <div class="w-9 h-9 bg-emerald-500 rounded-xl flex items-center justify-center text-slate-950 font-black">
            <i class="fas fa-oil-well"></i>
        </div>
        <span class="text-md font-bold text-white tracking-tight">Petromine <span class="text-emerald-400 font-normal">Analytics</span></span>
    </div>
    <div class="flex items-center gap-3">
        <a href="profile.php" class="px-3 py-2 bg-slate-800 border border-slate-700 rounded-lg text-slate-400 hover:text-emerald-400 transition-all text-xs font-semibold">
            <i class="fas fa-arrow-left mr-1.5"></i>Profil
        </a>
        <a href="logout.php" class="px-3 py-2 bg-slate-800 border border-slate-700 rounded-lg text-slate-400 hover:text-rose-400 transition-all text-xs">
            <i class="fas fa-power-off"></i>
        </a>
    </div>
</nav>

<main class="max-w-xl mx-auto px-6 py-10">

    <div class="mb-7">
        <h1 class="text-2xl font-bold text-white tracking-tight">Edit Profil</h1>
        <p class="text-xs text-slate-500 mt-1">Perbarui nama, role, dan foto profil Anda.</p>
    </div>

    <?php if (!empty($error_msg)): ?>
    <div class="mb-5 flex items-center gap-3 bg-rose-500/10 border border-rose-500/30 text-rose-400 text-xs font-semibold px-4 py-3 rounded-xl">
        <i class="fas fa-exclamation-circle text-base"></i> <?php echo htmlspecialchars($error_msg); ?>
    </div>
    <?php endif; ?>

    <!-- ── CARD: Foto Profil ─────────────────────────────────────────────── -->
    <div class="bg-slate-900 border border-slate-800 rounded-2xl p-6 mb-5">
        <h2 class="text-xs font-bold text-slate-400 uppercase tracking-widest mb-5 flex items-center gap-2">
            <i class="fas fa-camera text-emerald-400"></i> Foto Profil
        </h2>
        <div class="flex items-center gap-5">
            <!-- Avatar preview -->
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
                    <span class="text-[9px] text-white font-bold tracking-wide">ganti</span>
                </div>
            </label>
            <input type="file" id="profile_photo_input" name="profile_photo_temp"
                accept="image/jpeg,image/png,image/gif,image/webp"
                onchange="previewPhoto(this)">

            <div class="flex-1">
                <div class="flex flex-wrap gap-2 mb-2.5">
                    <label for="profile_photo_input"
                        class="cursor-pointer inline-flex items-center gap-1.5 bg-slate-800 hover:bg-slate-700 border border-slate-700 text-slate-300 text-xs font-semibold px-3 py-2 rounded-lg transition-all">
                        <i class="fas fa-upload text-emerald-400 text-[11px]"></i>
                        <?php echo $has_photo ? 'Ganti Foto' : 'Upload Foto'; ?>
                    </label>
                    <?php if ($has_photo): ?>
                    <button type="button" onclick="document.getElementById('delete-modal').classList.remove('hidden')"
                        class="inline-flex items-center gap-1.5 bg-rose-500/10 hover:bg-rose-500/20 border border-rose-500/30 text-rose-400 text-xs font-semibold px-3 py-2 rounded-lg transition-all">
                        <i class="fas fa-trash-alt text-[11px]"></i> Hapus Foto
                    </button>
                    <?php endif; ?>
                </div>
                <p class="text-[10px] text-slate-600">
                    <i class="fas fa-info-circle mr-1"></i>JPG, PNG, WEBP &bull; Maks. 2MB
                </p>
            </div>
        </div>
    </div>

    <!-- ── FORM: Data Diri ────────────────────────────────────────────────── -->
    <form method="POST" enctype="multipart/form-data" id="main-form">
        <input type="hidden" name="action" value="update_profile">
        <!-- File dikirim via hidden input yang diisi JS -->
        <input type="file" id="hidden-file-input" name="profile_photo" style="display:none" accept="image/*">

        <div class="bg-slate-900 border border-slate-800 rounded-2xl p-6 mb-6">
            <h2 class="text-xs font-bold text-slate-400 uppercase tracking-widest mb-5 flex items-center gap-2">
                <i class="fas fa-id-card text-emerald-400"></i> Informasi Pribadi 
            </h2>
            <div class="space-y-4">

                <div>
                    <label class="block text-xs font-semibold text-slate-400 mb-1.5">
                        Nama Lengkap <span class="text-emerald-500">*</span>
                    </label>
                    <input type="text" name="name"
                        value="<?php echo htmlspecialchars($user['name'] ?? ''); ?>"
                        class="w-full bg-slate-950/60 border border-slate-700 text-slate-100 text-sm rounded-xl px-4 py-2.5 focus:outline-none focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500/30 transition-all placeholder-slate-600"
                        placeholder="Masukkan nama lengkap" required>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-400 mb-1.5">
                        Role <span class="text-emerald-500">*</span>
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
                </div>

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

            </div>
        </div>

        <div class="flex justify-end gap-3">
            <a href="profile.php"
               class="px-5 py-2.5 bg-slate-800 border border-slate-700 text-slate-300 hover:text-white font-semibold text-xs rounded-xl transition-all">
                <i class="fas fa-times mr-1.5"></i>Cancel
            </a>
            <button type="submit"
                class="bg-emerald-500 hover:bg-emerald-600 active:bg-emerald-700 text-slate-950 font-bold px-6 py-2.5 rounded-xl text-xs flex items-center gap-2 shadow-lg transition-all">
                <i class="fas fa-save"></i> Save Changes
            </button>
        </div>
    </form>
</main>

<!-- MODAL: Konfirmasi Hapus Foto -->
<div id="delete-modal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-slate-950/80 backdrop-blur-sm px-4">
    <div class="bg-slate-900 border border-slate-700 rounded-2xl p-6 max-w-sm w-full shadow-2xl">
        <div class="w-12 h-12 bg-rose-500/15 border border-rose-500/30 rounded-xl flex items-center justify-center mx-auto mb-4">
            <i class="fas fa-trash-alt text-rose-400 text-lg"></i>
        </div>
        <h3 class="text-sm font-bold text-white text-center mb-1">Hapus Foto Profil?</h3>
        <p class="text-xs text-slate-500 text-center mb-6">Foto Anda akan dihapus secara permanen dan tidak dapat dikembalikan.</p>
        <div class="flex gap-3">
            <button type="button" onclick="document.getElementById('delete-modal').classList.add('hidden')"
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
// Preview foto & sync ke hidden input di main-form
function previewPhoto(input) {
    if (!input.files || !input.files[0]) return;
    const file = input.files[0];
    const reader = new FileReader();
    reader.onload = function(e) {
        const preview  = document.getElementById('photo-preview');
        const initials = document.getElementById('photo-initials');
        preview.src = e.target.result;
        preview.classList.remove('hidden');
        if (initials) initials.classList.add('hidden');
    };
    reader.readAsDataURL(file);

    // Transfer file ke hidden input yang ada di dalam main-form
    const dt = new DataTransfer();
    dt.items.add(file);
    document.getElementById('hidden-file-input').files = dt.files;
}

// Tutup modal hapus jika klik backdrop
document.getElementById('delete-modal').addEventListener('click', function(e) {
    if (e.target === this) this.classList.add('hidden');
});
</script>
</body>
</html>