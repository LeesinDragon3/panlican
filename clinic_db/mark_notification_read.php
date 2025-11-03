<?php
require_once 'db_connect.php';
session_start();

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'doctor') {
    echo json_encode(['success' => false]);
    exit;
}

$doctor_id = $_SESSION['user']['id'];
$conn->query("UPDATE notification SET is_read=1 WHERE doctor_id=$doctor_id");
echo json_encode(['success' => true]);
?>
