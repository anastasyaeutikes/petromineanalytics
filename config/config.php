<?php
/*
 * config.php
 * File untuk konfigurasi dan koneksi database.
 * Simpan file ini di lokasi yang aman.
 */

// ** Pengaturan Database MySQL ** //
// Ganti nilai-nilai di bawah ini dengan informasi database Anda.

/** Nama server database (biasanya 'localhost') */
define('DB_SERVER', 'localhost');

/** Username untuk koneksi ke database */
define('DB_USERNAME', 'root');

/** Password untuk koneksi ke database */
define('DB_PASSWORD', '');

/** Nama database yang akan digunakan */
define('DB_NAME', 'databasemigas');

/* Mencoba untuk terhubung ke database MySQL */
$mysqli = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);

// Memeriksa koneksi
if($mysqli === false){
    // Jika koneksi gagal, hentikan skrip dan tampilkan pesan error.
    // Sebaiknya pesan error yang lebih umum digunakan di lingkungan produksi.
    die("ERROR: Tidak dapat terhubung ke database. " . $mysqli->connect_error);
}

// Set karakter set ke utf8mb4 untuk mendukung berbagai macam karakter
$mysqli->set_charset("utf8mb4");

?>
