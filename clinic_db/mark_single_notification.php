<?php
session_start();
require_once 'db_connect.php';
header('Content-Type: application/json');
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'doctor') { echo json_encode(['success'=>false]); exit; }
$data = json_decode(file_get_contents('php://input'), true);
$id = intval($data['id'] ?? 0);
if ($id <= 0) { echo json_encode(['success'=>false]); exit; }
$stmt = $conn->prepare("UPDATE notification SET is_read = 1 WHERE id = ? AND doctor_id = ?");
$stmt->bind_param('ii', $id, $_SESSION['user']['id']);
$stmt->execute();
echo json_encode(['success'=> $stmt->affected_rows>0]);
