<?php
require_once 'db_connect.php';
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'doctor') {
  echo json_encode(['success'=>false,'message'=>'Unauthorized']); exit;
}
$id = intval($_POST['id'] ?? 0);
if (!$id) { echo json_encode(['success'=>false,'message'=>'Invalid id']); exit; }

// optional ownership check
$doctorId = intval($_SESSION['user']['id']);
$stmtCheck = $conn->prepare("SELECT id FROM prescriptions WHERE id = ? AND doctor_id = ?");
$stmtCheck->bind_param('ii', $id, $doctorId);
$stmtCheck->execute();
if (!$stmtCheck->get_result()->fetch_assoc()) {
  echo json_encode(['success'=>false,'message'=>'Not found or access denied.']); exit;
}

$stmt = $conn->prepare("DELETE FROM prescriptions WHERE id = ?");
$stmt->bind_param('i', $id);
if ($stmt->execute()) echo json_encode(['success'=>true]);
else echo json_encode(['success'=>false,'message'=>'DB error.']);
