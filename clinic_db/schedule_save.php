<?php
require_once 'db_connect.php';
session_start();

header('Content-Type: application/json');

// Get POST data
$day = isset($_POST['day']) ? trim($_POST['day']) : '';
$start_time = isset($_POST['start_time']) ? trim($_POST['start_time']) : '09:00';
$end_time = isset($_POST['end_time']) ? trim($_POST['end_time']) : '17:00';
$is_available = isset($_POST['is_available']) ? 1 : 0;
$doctor_id = isset($_SESSION['user']['id']) ? intval($_SESSION['user']['id']) : 0;

// Validate
if (!$doctor_id || empty($day)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid doctor or day']);
    exit;
}

// First, try to delete any TestDay records (cleanup from testing)
$conn->query("DELETE FROM doctor_schedules WHERE day_of_week = 'TestDay'");

// Check if record exists
$checkQuery = "SELECT id FROM doctor_schedules WHERE doctor_id = ? AND day_of_week = ? LIMIT 1";
$checkStmt = $conn->prepare($checkQuery);

if (!$checkStmt) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Check prepare error: ' . $conn->error]);
    exit;
}

$checkStmt->bind_param('is', $doctor_id, $day);
$checkStmt->execute();
$checkResult = $checkStmt->get_result();
$recordExists = ($checkResult->num_rows > 0);
$checkStmt->close();

if ($recordExists) {
    // UPDATE existing record
    $updateQuery = "UPDATE doctor_schedules SET start_time = ?, end_time = ?, is_available = ? WHERE doctor_id = ? AND day_of_week = ?";
    $updateStmt = $conn->prepare($updateQuery);
    
    if (!$updateStmt) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Update prepare error: ' . $conn->error]);
        exit;
    }
    
    $updateStmt->bind_param('sssii', $start_time, $end_time, $is_available, $doctor_id, $day);
    
    if ($updateStmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Schedule updated']);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Update error: ' . $updateStmt->error]);
    }
    $updateStmt->close();
} else {
    // INSERT new record
    $insertQuery = "INSERT INTO doctor_schedules (doctor_id, day_of_week, start_time, end_time, is_available) VALUES (?, ?, ?, ?, ?)";
    $insertStmt = $conn->prepare($insertQuery);
    
    if (!$insertStmt) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Insert prepare error: ' . $conn->error]);
        exit;
    }
    
    $insertStmt->bind_param('isssi', $doctor_id, $day, $start_time, $end_time, $is_available);
    
    if ($insertStmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Schedule created']);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Insert error: ' . $insertStmt->error]);
    }
    $insertStmt->close();
}
?>