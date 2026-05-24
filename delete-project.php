<?php
// delete-project.php
session_start();
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) { header("location: login.php"); exit; }
$user_id = $_SESSION['user_id'];
require_once "config.php";

$project_id = isset($_GET['id']) ? trim($_GET['id']) : null;
if (!empty($project_id)) {
    $sql = "DELETE FROM projects WHERE id = ? AND user_id = ?";
    if ($stmt = $mysqli->prepare($sql)) {
        $stmt->bind_param("ii", $project_id, $user_id);
        $stmt->execute();
        $stmt->close();
    }
}
header("location: home.php");
exit;
?>