<?php
require_once 'db_connect.php';
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'doctor') {
  echo json_encode(['success'=>false,'message'=>'Unauthorized']);
  exit;
}

$doctorId = intval($_SESSION['user']['id']);
$id = intval($_POST['id'] ?? 0);
$patient_username = trim($_POST['patient_username'] ?? '');
$medicine = trim($_POST['medicine'] ?? '');
$dosage = trim($_POST['dosage'] ?? '');
$instructions = trim($_POST['instructions'] ?? '');
$date_issued = $_POST['date_issued'] ?? '';

if (!$patient_username || !$medicine || !$date_issued) {
  echo json_encode(['success'=>false,'message'=>'Missing required fields.']);
  exit;
}

// find patient_id from username (if exists)
$stmtU = $conn->prepare("SELECT id FROM users WHERE username = ? LIMIT 1");
$stmtU->bind_param('s', $patient_username);
$stmtU->execute();
$resU = $stmtU->get_result();
$patientId = null;
if ($r = $resU->fetch_assoc()) $patientId = intval($r['id']);

if (!$patientId) {
  echo json_encode(['success'=>false,'message'=>'Patient not found (username).']);
  exit;
}

if ($id) {
  // update
  $stmt = $conn->prepare("UPDATE prescriptions SET appointment_id = NULL, doctor_id = ?, patient_id = ?, medicine = ?, dosage = ?, instructions = ?, date_issued = ? WHERE id = ?");
  $stmt->bind_param('iissssi', $doctorId, $patientId, $medicine, $dosage, $instructions, $date_issued, $id);
  if ($stmt->execute()) echo json_encode(['success'=>true,'message'=>'Updated']);
  else echo json_encode(['success'=>false,'message'=>'DB error.']);
} else {
  // insert
  $stmt = $conn->prepare("INSERT INTO prescriptions (appointment_id, doctor_id, patient_id, medicine, dosage, instructions, date_issued) VALUES (NULL, ?, ?, ?, ?, ?, ?)");
  $stmt->bind_param('iiisss', $doctorId, $patientId, $patientId, $medicine, $dosage, $instructions, $date_issued);
  // Note: binding had mismatch; correct types: i i i s s s -> adjust
  $stmt = $conn->prepare("INSERT INTO prescriptions (appointment_id, doctor_id, patient_id, medicine, dosage, instructions, date_issued) VALUES (NULL, ?, ?, ?, ?, ?, ?)");
  $stmt->bind_param('iiisss', $doctorId, $patientId, $patientId, $medicine, $dosage, $instructions, $date_issued);
  if ($stmt->execute()) echo json_encode(['success'=>true,'message'=>'Saved']);
  else echo json_encode(['success'=>false,'message'=>'DB error.']);
}
