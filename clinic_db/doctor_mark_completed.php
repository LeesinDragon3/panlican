<?php
session_start();
require_once 'db_connect.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'doctor') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

if (!isset($_POST['appointment_id'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit();
}

$appointmentId = intval($_POST['appointment_id']);
$doctorId = $_SESSION['user']['id'];

// ✅ Update appointment status
$stmt = $conn->prepare("UPDATE appointments SET status='completed' WHERE id=? AND doctor_id=?");
$stmt->bind_param('ii', $appointmentId, $doctorId);

if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Database update failed']);
}
