<?php
// register.php
session_start();
if (isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true) {
    header("location: home.php");
    exit;
}
require_once "config.php";

$name = $email = $password = "";
$name_err = $email_err = $password_err = "";
$error_message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (empty(trim($_POST["name"]))) { $name_err = "Silakan masukkan nama Anda."; } else { $name = trim($_POST["name"]); }

    if (empty(trim($_POST["email"]))) {
        $email_err = "Silakan masukkan email Anda.";
    } else {
        $sql_check = "SELECT id FROM users WHERE email = ?";
        if ($stmt_check = $mysqli->prepare($sql_check)) {
            $stmt_check->bind_param("s", $param_email_check);
            $param_email_check = trim($_POST["email"]);
            if ($stmt_check->execute()) {
                $stmt_check->store_result();
                if ($stmt_check->num_rows == 1) { $email_err = "Email ini sudah terdaftar."; } else { $email = trim($_POST["email"]); }
            } else { $error_message = "Terjadi kesalahan sistem."; }
            $stmt_check->close();
        }
    }

    if (empty(trim($_POST["password"]))) { $password_err = "Silakan masukkan password."; } elseif (strlen(trim($_POST["password"])) < 6) { $password_err = "Password minimal 6 karakter."; } else { $password = trim($_POST["password"]); }

    if (empty($name_err) && empty($email_err) && empty($password_err)) {
        $sql = "INSERT INTO users (name, email, password, created_at, updated_at) VALUES (?, ?, ?, NOW(), NOW())";
        if ($stmt = $mysqli->prepare($sql)) {
            $stmt->bind_param("sss", $param_name, $param_email, $param_password);
            $param_name = $name;
            $param_email = $email;
            $param_password = password_hash($password, PASSWORD_DEFAULT);
            if ($stmt->execute()) {
                header("location: login.php?registration=success");
                exit();
            } else { $error_message = "Registrasi gagal, silakan coba lagi."; }
            $stmt->close();
        }
    }
    $mysqli->close();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Registrasi Akun Baru - Petromine Analytics</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>body { font-family: 'Plus Jakarta Sans', sans-serif; }</style>
</head>
<body class="bg-slate-950 text-slate-100 flex items-center justify-center min-h-screen p-4">
    <div class="w-full max-w-md bg-slate-900 border border-slate-800 rounded-2xl shadow-2xl p-8">
        <div class="text-center mb-6">
            <div class="w-12 h-12 bg-emerald-500/10 text-emerald-400 rounded-xl flex items-center justify-center text-xl mx-auto mb-4 border border-emerald-500/20"><i class="fas fa-user-plus"></i></div>
            <h1 class="text-2xl font-bold text-white">Register</h1>
            <p class="text-xs text-slate-400 mt-1">Platform Manajemen Lapangan Migas</p>
        </div>
        <?php if(!empty($error_message)): ?>
            <div class="mb-4 p-3 bg-rose-500/10 border border-rose-500/20 rounded-xl text-rose-400 text-xs"><i class="fas fa-exclamation-circle mr-2"></i><?php echo $error_message; ?></div>
        <?php endif; ?>
        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="POST" class="space-y-4">
            <div>
                <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Nama Lengkap</label>
                <input type="text" name="name" value="<?php echo htmlspecialchars($name); ?>" class="w-full px-4 py-3 bg-slate-950 border <?php echo (!empty($name_err)) ? 'border-rose-500' : 'border-slate-800'; ?> rounded-xl text-sm text-white focus:outline-none focus:border-emerald-500" placeholder="Nama Lengkap Anda">
                <p class="text-rose-400 text-xs mt-1"><?php echo $name_err; ?></p>
            </div>
            <div>
                <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Alamat Email</label>
                <input type="email" name="email" value="<?php echo htmlspecialchars($email); ?>" class="w-full px-4 py-3 bg-slate-950 border <?php echo (!empty($email_err)) ? 'border-rose-500' : 'border-slate-800'; ?> rounded-xl text-sm text-white focus:outline-none focus:border-emerald-500" placeholder="nama@perusahaan.com">
                <p class="text-rose-400 text-xs mt-1"><?php echo $email_err; ?></p>
            </div>
            <div>
                <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Password</label>
                <input type="password" name="password" class="w-full px-4 py-3 bg-slate-950 border <?php echo (!empty($password_err)) ? 'border-rose-500' : 'border-slate-800'; ?> rounded-xl text-sm text-white focus:outline-none focus:border-emerald-500" placeholder="Password Anda">
                <p class="text-rose-400 text-xs mt-1"><?php echo $password_err; ?></p>
            </div>
            <button type="submit" class="w-full bg-emerald-500 hover:bg-emerald-600 text-slate-950 font-bold py-3 rounded-xl shadow-lg text-sm transition-all"><i class="fas fa-check-circle mr-1"></i>Registrasi</button>
        </form>
        <div class="mt-6 pt-4 border-t border-slate-800 text-center">
            <p class="text-xs text-slate-400">Sudah memiliki akun? <a href="login.php" class="font-bold text-emerald-400 hover:text-emerald-300">Masuk Di Sini</a></p>
        </div>
    </div>
</body>
</html>