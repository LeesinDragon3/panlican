<?php
session_start();
require_once 'db_connect.php';
header('Content-Type: application/json; charset=utf-8');

// Ensure patient is logged in
if(!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'patient'){
    echo json_encode(['status' => 'error', 'message' => 'Access denied']);
    exit;
}

$patient_id = intval($_SESSION['user']['id'] ?? 0);
$patient_username = $_SESSION['user']['username'] ?? null;

$doctor_id = intval($_POST['doctor_id'] ?? 0);
$date = trim($_POST['date'] ?? '');
$time = trim($_POST['time'] ?? '');
$notes = trim($_POST['notes'] ?? '');
$emergency = isset($_POST['emergency']) && ($_POST['emergency'] === '1' || $_POST['emergency'] === 'on' || $_POST['emergency'] === 1) ? 1 : 0;

if (!$doctor_id || !$date || !$time) {
    echo json_encode(['status' => 'error', 'message' => 'All fields are required']);
    exit;
}

// Validate date and time formats (basic)
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) || !preg_match('/^\d{2}:\d{2}(:\d{2})?$/', $time)) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid date or time format']);
    exit;
}

// Determine whether appointments table has patient_id and/or patient_username columns
function column_exists($conn, $table, $column) {
    $stmt = $conn->prepare("SELECT COUNT(*) as c FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?");
    if (!$stmt) return false;
    $stmt->bind_param("ss", $table, $column);
    $stmt->execute();
    $res = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return intval($res['c']) > 0;
}

$has_patient_id = column_exists($conn, 'appointments', 'patient_id');
$has_patient_username = column_exists($conn, 'appointments', 'patient_username');

// Build insertion statement according to available columns
if ($has_patient_id) {
    $sql = "INSERT INTO appointments (patient_id, doctor_id, date, time, status, emergency, notes) VALUES (?, ?, ?, ?, 'pending', ?, ?)";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        echo json_encode(['status' => 'error', 'message' => 'Prepare failed: ' . $conn->error]);
        exit;
    }
    $stmt->bind_param("iissis", $patient_id, $doctor_id, $date, $time, $emergency, $notes);
} elseif ($has_patient_username) {
    $sql = "INSERT INTO appointments (patient_username, doctor_id, date, time, status, emergency, notes) VALUES (?, ?, ?, ?, 'pending', ?, ?)";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        echo json_encode(['status' => 'error', 'message' => 'Prepare failed: ' . $conn->error]);
        exit;
    }
    $stmt->bind_param("sissis", $patient_username, $doctor_id, $date, $time, $emergency, $notes);
} else {
    // Neither patient_id nor patient_username exists: fallback to older schema (unsafe)
    // Best to require migration; return an informative error.
    echo json_encode(['status' => 'error', 'message' => 'Database schema missing patient identifier. Please add patient_id or patient_username column to appointments.']);
    exit;
}

if ($stmt->execute()) {
    echo json_encode(['status' => 'success']);
} else {
    // Provide a general message but include statement error for debugging on dev (remove in production)
    echo json_encode(['status' => 'error', 'message' => $stmt->error]);
}

$stmt->close();
$conn->close();
