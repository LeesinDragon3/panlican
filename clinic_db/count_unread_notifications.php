<?php
session_start();
require_once 'db_connect.php';

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'patient') {
    echo json_encode(['unread' => 0]);
    exit();
}

$patientId = $_SESSION['user']['id'];

$stmt = $conn->prepare("SELECT COUNT(*) AS unread FROM notifications WHERE patient_id = ? AND is_read = 0");
$stmt->bind_param("i", $patientId);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
$stmt->close();

echo json_encode(['unread' => intval($row['unread'])]);
?>
