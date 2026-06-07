<?php
// delete-project.php
require_once "../../includes/auth.php";
$user_id = $_SESSION['user_id'];
require_once "../../config/config.php";

$project_id = isset($_GET['id']) ? trim($_GET['id']) : null;
if (!empty($project_id)) {
    $sql = "DELETE FROM projects WHERE id = ? AND user_id = ?";
    if ($stmt = $mysqli->prepare($sql)) {
        $stmt->bind_param("ii", $project_id, $user_id);
        if ($stmt->execute()) {
            $_SESSION["toast_success"] = "Proyek berhasil dihapus secara permanen.";
        } else {
            $_SESSION["toast_error"] = "Gagal menghapus proyek.";
        }
        $stmt->close();
    }
}
header("location: home.php");
exit;
?>