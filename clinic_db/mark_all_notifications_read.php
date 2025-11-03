<?php
require_once 'db_connect.php';
session_start();
header('Content-Type: application/json');
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'doctor') { echo json_encode(['success'=>false]); exit; }
$doctorId = intval($_SESSION['user']['id']);
$stmt = $conn->prepare("UPDATE notifications SET is_read = 1 WHERE doctor_id = ?");
$stmt->bind_param('i', $doctorId);
if ($stmt->execute()) echo json_encode(['success'=>true]); else echo json_encode(['success'=>false]);
