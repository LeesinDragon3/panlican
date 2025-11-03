<?php
session_start();
require_once 'db_connect.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'patient') {
    echo json_encode(["status" => "error", "message" => "Unauthorized"]);
    exit();
}

$patientId = (int) $_SESSION['user']['id'];
$id = (int) ($_POST['id'] ?? 0);

if (!$id) {
    echo json_encode(["status" => "error", "message" => "Invalid appointment ID"]);
    exit();
}

$stmt = $conn->prepare("UPDATE appointments SET status='cancelled' WHERE id=? AND patient_id=? AND status='pending'");
$stmt->bind_param('ii', $id, $patientId);

if ($stmt->execute() && $stmt->affected_rows > 0) {
    echo json_encode(["status" => "success", "message" => "❌ Appointment cancelled"]);
} else {
    echo json_encode(["status" => "error", "message" => "Unable to cancel appointment"]);
}
?>
