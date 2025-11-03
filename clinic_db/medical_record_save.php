<?php
require_once 'db_connect.php';
session_start();

header('Content-Type: application/json');

// ✅ Restrict to doctors
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'doctor') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

$doctor_id = $_SESSION['user']['id'];
$record_id = $_POST['record_id'] ?? '';
$patient_id = $_POST['patient_id'] ?? '';
$visit_date = $_POST['visit_date'] ?? '';
$visit_time = $_POST['visit_time'] ?? '';
$chief_complaint = $_POST['chief_complaint'] ?? '';
$diagnosis = $_POST['diagnosis'] ?? '';
$treatment = $_POST['treatment'] ?? '';

// ✅ Validate input
if (empty($patient_id) || empty($visit_date) || empty($visit_time) || empty($diagnosis)) {
    echo json_encode(['success' => false, 'message' => 'Please fill in all required fields']);
    exit;
}

// ✅ Check if patient belongs to this doctor
$checkQuery = "SELECT id FROM appointments WHERE doctor_id = ? AND patient_id = ? LIMIT 1";
$checkStmt = $conn->prepare($checkQuery);
$checkStmt->bind_param("ii", $doctor_id, $patient_id);
$checkStmt->execute();
$checkResult = $checkStmt->get_result();

if ($checkResult->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Patient not found or unauthorized']);
    exit;
}

// ✅ If editing, update the appointment notes
if (!empty($record_id)) {
    $updateQuery = "UPDATE appointments 
                    SET notes = ?, 
                        date = ?,
                        time = ?
                    WHERE id = ? AND doctor_id = ?";
    
    $updateStmt = $conn->prepare($updateQuery);
    if (!$updateStmt) {
        echo json_encode(['success' => false, 'message' => 'Update prepare failed: ' . $conn->error]);
        exit;
    }
    
    $fullNotes = "Chief Complaint: $chief_complaint\n\nDiagnosis: $diagnosis\n\nTreatment: $treatment";
    $updateStmt->bind_param("sssii", $fullNotes, $visit_date, $visit_time, $record_id, $doctor_id);
    
    if ($updateStmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Medical record updated successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update record: ' . $conn->error]);
    }
} 
// ✅ If new record, create appointment if not exists
else {
    // Check if appointment already exists for this patient and date
    $existQuery = "SELECT id FROM appointments WHERE patient_id = ? AND doctor_id = ? AND date = ? LIMIT 1";
    $existStmt = $conn->prepare($existQuery);
    $existStmt->bind_param("iis", $patient_id, $doctor_id, $visit_date);
    $existStmt->execute();
    $existResult = $existStmt->get_result();
    
    if ($existResult->num_rows > 0) {
        // Update existing appointment
        $apptRow = $existResult->fetch_assoc();
        $appt_id = $apptRow['id'];
        
        $updateQuery = "UPDATE appointments 
                        SET notes = ?, time = ?, status = 'completed'
                        WHERE id = ?";
        $updateStmt = $conn->prepare($updateQuery);
        $fullNotes = "Chief Complaint: $chief_complaint\n\nDiagnosis: $diagnosis\n\nTreatment: $treatment";
        $updateStmt->bind_param("ssi", $fullNotes, $visit_time, $appt_id);
        
        if ($updateStmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Medical record created successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to create record']);
        }
    } else {
        // Create new appointment
        $insertQuery = "INSERT INTO appointments (patient_id, doctor_id, date, time, status, notes) 
                        VALUES (?, ?, ?, ?, 'completed', ?)";
        $insertStmt = $conn->prepare($insertQuery);
        
        if (!$insertStmt) {
            echo json_encode(['success' => false, 'message' => 'Insert prepare failed: ' . $conn->error]);
            exit;
        }
        
        $fullNotes = "Chief Complaint: $chief_complaint\n\nDiagnosis: $diagnosis\n\nTreatment: $treatment";
        $status = 'completed';
        $insertStmt->bind_param("iisss", $patient_id, $doctor_id, $visit_date, $visit_time, $fullNotes);
        
        if ($insertStmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Medical record created successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to create record: ' . $conn->error]);
        }
    }
}
?>