<?php
require_once 'db_connect.php';
session_start();

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'doctor') {
    echo json_encode(['count' => 0]);
    exit;
}

$doctor_id = $_SESSION['user']['id'];
$q = $conn->query("SELECT COUNT(*) AS c FROM notification WHERE doctor_id=$doctor_id AND is_read=0");
$row = $q->fetch_assoc();
echo json_encode(['count' => $row['c']]);
?>
