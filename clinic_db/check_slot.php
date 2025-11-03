<?php
session_start();
require_once 'db_connect.php';
header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true);
$doctor_id = intval($input['doctor_id'] ?? 0);
$date = $conn->real_escape_string($input['date'] ?? '');
$time = $conn->real_escape_string($input['time'] ?? '');

if(!$doctor_id || !$date || !$time){
    echo json_encode(['available'=>false]);
    exit;
}

$sql = "SELECT COUNT(*) AS c FROM appointments WHERE doctor_id=$doctor_id AND date='$date' AND time='$time' AND status='pending'";
$res = $conn->query($sql);
$row = $res->fetch_assoc();
echo json_encode(['available'=>$row['c']==0]);
