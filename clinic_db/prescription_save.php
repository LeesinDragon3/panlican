<?php
require_once 'db_connect.php';
session_start();

header('Content-Type: application/json');

// Restrict to doctors
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'doctor') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$doctor_id = $_SESSION['user']['id'];
$patient_id = $_POST['patient_id'] ?? 0;
$medicine = $_POST['medicine'] ?? '';
$dosage = $_POST['dosage'] ?? '';
$instructions = $_POST['instructions'] ?? '';
$prescription_id = $_POST['prescription_id'] ?? 0;

// Validate
if (!$patient_id || !$medicine || !$dosage || !$instructions) {
    echo json_encode(['success' => false, 'message' => 'All fields are required']);
    exit;
}

// Check if patient exists in users table
$patientCheck = $conn->query("SELECT id FROM users WHERE id = $patient_id AND role = 'patient'");
if ($patientCheck->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Patient not found']);
    exit;
}

// Check if doctor has appointment with this patient
$appointmentCheck = $conn->query("SELECT id FROM appointments WHERE doctor_id = $doctor_id AND patient_id = $patient_id LIMIT 1");
if ($appointmentCheck->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'No appointment found with this patient']);
    exit;
}

// Get latest appointment ID
$apptResult = $conn->query("SELECT id FROM appointments WHERE doctor_id = $doctor_id AND patient_id = $patient_id ORDER BY date DESC LIMIT 1");
$appt = $apptResult->fetch_assoc();
$appointment_id = $appt['id'];

// Escape strings
$medicine = $conn->real_escape_string($medicine);
$dosage = $conn->real_escape_string($dosage);
$instructions = $conn->real_escape_string($instructions);

if ($prescription_id) {
    // UPDATE existing prescription
    $sql = "UPDATE prescriptions 
            SET medicine = '$medicine', dosage = '$dosage', instructions = '$instructions'
            WHERE id = $prescription_id AND doctor_id = $doctor_id";
} else {
    // INSERT new prescription
    $sql = "INSERT INTO prescriptions (doctor_id, appointment_id, patient_id, medicine, dosage, instructions, date_issued) 
            VALUES ($doctor_id, $appointment_id, $patient_id, '$medicine', '$dosage', '$instructions', NOW())";
}

if ($conn->query($sql)) {
    echo json_encode(['success' => true, 'message' => 'Prescription saved successfully']);
} else {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $conn->error]);
}
?>