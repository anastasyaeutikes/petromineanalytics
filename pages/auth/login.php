<?php
// login.php (Versi Modern Enterprise Dark-Mode - Gerbang Otentikasi Terenkripsi)

// Mulai session
session_start();

// Jika pengguna sudah login, arahkan langsung ke halaman dashboard utama
if(isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true){
    header("location: ../projects/home.php");
    exit;
}

// Sisipkan file config.php
require_once "../../config/config.php";

// Definisikan variabel dan inisialisasi dengan string kosong
$email = $password = $id = $hashed_password = "";
$email_err = $password_err = $login_err = "";

// Memproses data form ketika form disubmit
if($_SERVER["REQUEST_METHOD"] == "POST"){

    // Periksa apakah email kosong
    if(empty(trim($_POST["email"]))){
        $email_err = "Silakan masukkan alamat email Anda.";
    } else{
        $email = trim($_POST["email"]);
    }

    // Periksa apakah password kosong
    if(empty(trim($_POST["password"]))){
        $password_err = "Silakan masukkan kata sandi keamanan Anda.";
    } else{
        $password = trim($_POST["password"]);
    }

    // Validasi kredensial
    if(empty($email_err) && empty($password_err)){
        // Siapkan statement select aman
        $sql = "SELECT id, name, email, password FROM users WHERE email = ?";

        if($stmt = $mysqli->prepare($sql)){
            // Ikat variabel ke statement sebagai parameter
            $stmt->bind_param("s", $param_email);
            $param_email = $email;
            
            // Eksekusi statement
            if($stmt->execute()){
                // Simpan hasil untuk memeriksa apakah email terdaftar
                $stmt->store_result();
                
                if($stmt->num_rows == 1){                    
                    // Ikat variabel hasil komponen (password hasil hash dari database diikat ke $hashed_password)
                    $stmt->bind_result($id, $name, $email, $hashed_password);
                    
                    if($stmt->fetch()){
                        // KUNCI PERBAIKAN: Menggunakan password_verify untuk mencocokkan password input dengan hash
                        if(password_verify($password, $hashed_password)){
                            // Password benar, mulai session baru
                            session_start();
                            
                            // Simpan data ke variabel session
                            $_SESSION["loggedin"] = true;
                            $_SESSION["user_id"] = $id;
                            $_SESSION["user_name"] = $name;                            
                            $_SESSION["user_password"] = $password;
                            
                            // Alihkan pengguna ke halaman beranda
                            header("location: ../projects/home.php");
                            exit;
                        } else{
                            // Password tidak cocok
                            $password_err = "Kata sandi yang Anda masukkan tidak valid.";
                        }
                    }
                } else{
                    // Email tidak ditemukan
                    $email_err = "Tidak ada akun yang ditemukan dengan email tersebut.";
                }
            } else{
                $login_err = "Oops! Terjadi kesalahan internal sistem. Silakan coba lagi nanti.";
            }

            // Tutup statement
            $stmt->close();
        }
    }
    
    // Tutup koneksi database
    $mysqli->close();
}
?>
<?php
$base_path = "../../";
$page_title = "Otentikasi Masuk";
require_once "../../includes/header.php";
?>
<body class="bg-slate-950 text-slate-100 flex items-center justify-center min-h-screen p-4">
    <div class="w-full max-w-md bg-slate-900 border border-slate-800 rounded-2xl shadow-2xl p-8">
        <div class="text-center mb-6">
            <div class="w-12 h-12 bg-emerald-500/10 text-emerald-400 rounded-xl flex items-center justify-center text-xl mx-auto mb-4 border border-emerald-500/20">
                <i class="fas fa-shield-halved"></i>
            </div>
            <h1 class="text-2xl font-bold text-white">Login</h1>
            <p class="text-xs text-slate-400 mt-1">Platform Manajemen Lapangan Migas.</p>
        </div>

        <?php 
        if(!empty($login_err)){
            echo '<div class="mb-4 p-3 bg-rose-500/10 border border-rose-500/20 rounded-xl text-rose-400 text-xs"><i class="fas fa-exclamation-circle mr-2"></i>' . $login_err . '</div>';
        }        
        ?>

        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="POST" class="space-y-4">
            <div>
                <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Alamat Email</label>
                <div class="relative">
                    <span class="absolute left-4 top-3.5 text-slate-500"><i class="fas fa-envelope"></i></span>
                    <input 
                    type="email" 
                    name="email" 
                    value="<?php echo htmlspecialchars($email); ?>" 
                           class="w-full pl-11 pr-4 py-3 bg-slate-950 border <?php echo (!empty($email_err)) ? 'border-rose-500' : 'border-slate-800'; ?> rounded-xl text-sm text-white focus:outline-none focus:border-emerald-500 transition-all placeholder-slate-600" placeholder="nama@perusahaan.com">
                </div>
                <?php if(!empty($email_err)): ?>
                    <p class="text-[11px] text-rose-400 mt-1.5 font-medium"><i class=\"fas fa-circle-info mr-1\"></i> <?php echo $email_err; ?></p>
                <?php endif; ?>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Kata Sandi Keamanan</label>
                <div class="relative">
                    <span class="absolute left-4 top-3.5 text-slate-500"><i class="fas fa-lock"></i></span>
                    <input type="password" name="password"
                           class="w-full pl-11 pr-4 py-3 bg-slate-950 border <?php echo (!empty($password_err)) ? 'border-rose-500' : 'border-slate-800'; ?> rounded-xl text-sm text-white focus:outline-none focus:border-emerald-500 transition-all placeholder-slate-600" placeholder="••••••••">
                </div>
                <?php if(!empty($password_err)): ?>
                    <p class="text-[11px] text-rose-400 mt-1.5 font-medium"><i class=\"fas fa-circle-info mr-1\"></i> <?php echo $password_err; ?></p>
                <?php endif; ?>
            </div>

            <div class="pt-2">
                <button type="submit"
                        class="w-full bg-emerald-500 text-slate-950 font-bold py-3 px-4 rounded-xl hover:bg-emerald-600 focus:outline-none transition-all shadow-lg shadow-emerald-500/10 flex items-center justify-center gap-2 text-sm">
                    <i class="fas fa-right-to-bracket"></i> Masuk
                </button>
            </div>
        </form>

        <div class="mt-6 pt-4 border-t border-slate-800 text-center">
            <p class="text-xs text-slate-400">Belum memiliki akses? <a href="register.php" class="font-bold text-emerald-400 hover:text-emerald-300">Daftar Akun Baru</a></p>
        </div>
    </div>
</body>
</html>