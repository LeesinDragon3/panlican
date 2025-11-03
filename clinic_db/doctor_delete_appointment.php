<?php
require_once 'db_connect.php';
session_start();

// ✅ Only allow doctors
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'doctor') {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$doctor_id = $_SESSION['user']['id'];
$doctor_name = $_SESSION['user']['fullname'] ?? 'Doctor';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'])) {
    $appointment_id = intval($_POST['id']);

    // ✅ Get the patient ID before deleting
    $stmt = $conn->prepare("SELECT patient_id FROM appointments WHERE id = ?");
    $stmt->bind_param("i", $appointment_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        echo json_encode(['success' => false, 'error' => 'Appointment not found']);
        exit;
    }

    $patient = $result->fetch_assoc();
    $patient_id = $patient['patient_id'];

    // ✅ Delete the appointment
    $delete = $conn->prepare("DELETE FROM appointments WHERE id = ?");
    $delete->bind_param("i", $appointment_id);
    $delete->execute();

    if ($delete->affected_rows > 0) {
        // ✅ Send notification to patient
        $message = "Dr. $doctor_name has deleted your appointment.";
        $notif = $conn->prepare("
            INSERT INTO notifications (patient_id, doctor_id, message)
            VALUES (?, ?, ?)
        ");
        $notif->bind_param("iis", $patient_id, $doctor_id, $message);
        $notif->execute();

        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Failed to delete appointment.']);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Invalid request.']);
}
