<?php
// delete-cashflow.php
require_once "../../includes/auth.php";
$user_id = $_SESSION['user_id'];
require_once "../../config/config.php";

// 2. Ambil parameter dari URL
$cashflow_id = isset($_GET['id']) ? trim($_GET['id']) : null;
$project_id = isset($_GET['project_id']) ? trim($_GET['project_id']) : null;

// 3. Validasi parameter angka
if (!empty($cashflow_id) && ctype_digit($cashflow_id) && !empty($project_id) && ctype_digit($project_id)) {
    
    // Query dengan JOIN untuk memastikan cashflow yang dihapus adalah milik user yang sedang login
    $sql = "DELETE cf FROM cashflows cf 
            JOIN projects p ON cf.project_id = p.id 
            WHERE cf.id = ? AND p.user_id = ?";
            
    if ($stmt = $mysqli->prepare($sql)) {
        $stmt->bind_param("ii", $cashflow_id, $user_id);
        $stmt->execute();
        $stmt->close();
    }
}

// 4. Tutup koneksi database
$mysqli->close();

// 5. Kembalikan user ke halaman detail proyek sebelumnya (otomatis me-refresh kalkulasi)
if (!empty($project_id) && ctype_digit($project_id)) {
    header("location: ../projects/project-details.php?id=" . $project_id);
} else {
    header("location: ../projects/home.php");
}
exit;
?>