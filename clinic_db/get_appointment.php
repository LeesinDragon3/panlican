<?php
require_once 'db_connect.php';
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'doctor') {
    echo json_encode(['success'=>false,'message'=>'Unauthorized']);
    exit;
}

$id = intval($_GET['id'] ?? 0);
if (!$id) { echo json_encode(['success'=>false,'message'=>'Invalid id']); exit; }

$stmt = $conn->prepare("SELECT * FROM appointments WHERE id = ? LIMIT 1");
$stmt->bind_param('i', $id);
$stmt->execute();
$res = $stmt->get_result();
if ($row = $res->fetch_assoc()) {
    echo json_encode(['success'=>true,'appointment'=>$row]);
} else {
    echo json_encode(['success'=>false,'message'=>'Not found']);
}
