<?php
require_once 'db_connect.php';
session_start();
header('Content-Type: application/json');
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'doctor') { echo json_encode(['success'=>false]); exit; }
$id = intval($_POST['id'] ?? 0);
if (!$id) { echo json_encode(['success'=>false]); exit; }
$doctorId = intval($_SESSION['user']['id']);
$stmt = $conn->prepare("DELETE FROM notifications WHERE id = ? AND doctor_id = ?");
$stmt->bind_param('ii', $id, $doctorId);
if ($stmt->execute()) echo json_encode(['success'=>true]); else echo json_encode(['success'=>false,'message'=>'DB error']);
