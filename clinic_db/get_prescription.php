<?php
require_once 'db_connect.php';
session_start();
header('Content-Type: application/json');
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'doctor') { echo json_encode(['success'=>false]); exit; }
$id = intval($_GET['id'] ?? 0);
if ($id <= 0) { echo json_encode(['success'=>false]); exit; }
$stmt = $conn->prepare("SELECT p.*, u.fullname AS patient_name, u.username AS patient_username FROM prescriptions p LEFT JOIN `user` u ON p.patient_id = u.id WHERE p.id = ? AND p.doctor_id = ?");
$stmt->bind_param('ii', $id, $_SESSION['user']['id']);
$stmt->execute();
$res = $stmt->get_result();
if ($res->num_rows === 0) { echo json_encode(['success'=>false]); exit; }
$row = $res->fetch_assoc();
echo json_encode(['success'=>true,'data'=>$row]);
