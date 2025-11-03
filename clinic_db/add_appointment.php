<?php
session_start();
require_once 'db_connect.php';

// ✅ Debugging (you can remove after testing)
error_reporting(E_ALL);
ini_set('display_errors', 1);
header('Content-Type: application/json');

// ✅ Allow only logged-in patients
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'patient') {
    echo json_encode(["status" => "error", "message" => "Unauthorized access"]);
    exit();
}

$patientId = (int) $_SESSION['user']['id'];
$patientUsername = $_SESSION['user']['username'] ?? 'unknown';

// ✅ Collect POST data
$doctorId  = $_POST['doctor_id'] ?? '';
$date      = $_POST['date'] ?? '';
$time      = $_POST['time'] ?? '';
$notes     = $_POST['notes'] ?? '';
$emergency = isset($_POST['emergency']) ? (int) $_POST['emergency'] : 0;

// ✅ Validate
if (empty($doctorId) || empty($date) || empty($time)) {
    echo json_encode(["status" => "error", "message" => "All required fields must be filled."]);
    exit();
}

// ✅ Default status
$status = 'pending';

// ✅ Prepare and execute insert query
$stmt = $conn->prepare("
    INSERT INTO appointments 
    (patient_id, doctor_id, date, time, status, emergency, patient_username, notes)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?)
");
$stmt->bind_param('iisssiss', 
    $patientId, 
    $doctorId, 
    $date, 
    $time, 
    $status, 
    $emergency, 
    $patientUsername, 
    $notes
);

if ($stmt->execute()) {
    echo json_encode(["status" => "success", "message" => "✅ Appointment successfully booked!"]);
} else {
    echo json_encode(["status" => "error", "message" => "Database error: " . $conn->error]);
}
?>
