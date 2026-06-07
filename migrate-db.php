<?php
// migrate-db.php
require_once "config.php";

$query = "ALTER TABLE projects MODIFY COLUMN depreciation INT NOT NULL DEFAULT 0";
if ($mysqli->query($query)) {
    echo "<div style='font-family: sans-serif; max-width: 600px; margin: 50px auto; padding: 30px; border-radius: 12px; background: #e6f4ea; border: 1px solid #34a853; color: #137333;'>";
    echo "<h2 style='margin-top: 0;'>Migrasi Database Berhasil!</h2>";
    echo "<p>Kolom <code>depreciation</code> pada tabel <code>projects</code> telah berhasil diubah untuk memiliki nilai default <strong>0</strong>.</p>";
    echo "<p>Sekarang Anda dapat menutup halaman ini, menghapus file <code>migrate-db.php</code>, dan mencoba kembali untuk membuat proyek baru.</p>";
    echo "<a href='home.php' style='display: inline-block; background: #137333; color: white; padding: 10px 20px; border-radius: 6px; text-decoration: none; margin-top: 15px; font-weight: bold;'>Kembali ke Dashboard</a>";
    echo "</div>";
} else {
    echo "<div style='font-family: sans-serif; max-width: 600px; margin: 50px auto; padding: 30px; border-radius: 12px; background: #fce8e6; border: 1px solid #ea4335; color: #c5221f;'>";
    echo "<h2 style='margin-top: 0;'>Migrasi Gagal!</h2>";
    echo "<p>Error: " . htmlspecialchars($mysqli->error) . "</p>";
    echo "</div>";
}
?>
