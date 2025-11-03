<?php
session_start();
require_once 'db_connect.php';

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'patient') {
    echo json_encode(['success'=>false]);
    exit();
}

$patientId = (int)$_SESSION['user']['id'];
$today = date('Y-m-d');

function safeCount($conn, $sql){
    $result = $conn->query($sql);
    if (!$result) return 0;
    $row = $result->fetch_assoc();
    return $row ? intval($row['c']) : 0;
}

echo json_encode([
    'success'=>true,
    'upcoming'=>safeCount($conn,"SELECT COUNT(*) AS c FROM appointments WHERE patient_id=$patientId AND date>='$today'"),
    'today'=>safeCount($conn,"SELECT COUNT(*) AS c FROM appointments WHERE patient_id=$patientId AND date='$today'"),
    'completed'=>safeCount($conn,"SELECT COUNT(*) AS c FROM appointments WHERE patient_id=$patientId AND status='completed'")
]);
