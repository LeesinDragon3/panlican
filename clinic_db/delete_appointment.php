<?php
require_once 'db_connect.php';
session_start();

// Only allow doctors
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'doctor') {
    echo json_encode(['success' => false, 'error' => 'unauthorized']);
    exit();
}

if (!isset($_POST['id'])) {
    echo json_encode(['success' => false, 'error' => 'missing id']);
    exit();
}

$id = intval($_POST['id']);
$doctorId = $_SESSION['user']['id'];

// ✅ Delete only if the appointment belongs to this doctor
$stmt = $conn->prepare("DELETE FROM appointments WHERE id = ? AND doctor_id = ?");
$stmt->bind_param("ii", $id, $doctorId);

if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false]);
}

$stmt->close();
$conn->close();
?>
