<?php
session_start();
require_once 'db_connect.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'patient') {
    echo json_encode(['status'=>'error','message'=>'Access denied']);
    exit;
}

$patientId = $_POST['patient_id'] ?? $_SESSION['user']['id'];
$doctorId = $_POST['doctor_id'] ?? null;
$date = $_POST['date'] ?? null;
$time = $_POST['time'] ?? null;
$emergency = isset($_POST['emergency']) ? 1 : 0;

if (!$doctorId || !$date || !$time) {
    echo json_encode(['status'=>'error','message'=>'All fields are required.']);
    exit;
}

$stmt = $conn->prepare("INSERT INTO appointments (patient_id, doctor_id, date, time, status, emergency) VALUES (?, ?, ?, ?, 'Pending', ?)");
$stmt->bind_param("iissi", $patientId, $doctorId, $date, $time, $emergency);

if ($stmt->execute()) {
    echo json_encode(['status'=>'success','message'=>'Appointment booked successfully!']);
} else {
    echo json_encode(['status'=>'error','message'=>'Failed to book appointment.']);
}
?>
