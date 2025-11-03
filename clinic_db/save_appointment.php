<?php
session_start();
require_once 'db_connect.php';

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'patient') {
    echo json_encode(['success'=>false,'message'=>'Unauthorized']);
    exit();
}

$patientId = (int)$_SESSION['user']['id'];
$id = $_POST['id'] ?? null;
$date = $_POST['date'] ?? '';
$time = $_POST['time'] ?? '';
$doctor_id = $_POST['doctor_id'] ?? '';
$status = $_POST['status'] ?? 'upcoming';
$notes = $_POST['notes'] ?? '';

if (!$date || !$time || !$doctor_id) {
    echo json_encode(['success'=>false,'message'=>'Fill required fields']);
    exit();
}

if ($id) {
    $stmt = $conn->prepare("UPDATE appointments SET date=?, time=?, doctor_id=?, status=?, notes=? WHERE id=? AND patient_id=?");
    $stmt->bind_param("ssissii",$date,$time,$doctor_id,$status,$notes,$id,$patientId);
    $stmt->execute();
} else {
    $stmt = $conn->prepare("INSERT INTO appointments (patient_id, doctor_id, date, time, status, notes) VALUES (?,?,?,?,?,?)");
    $stmt->bind_param("iissss",$patientId,$doctor_id,$date,$time,$status,$notes);
    $stmt->execute();
    $id = $stmt->insert_id;
}

// Return the updated table row HTML
$row_html = "<tr data-id='$id'>
    <td>".htmlspecialchars($date)."</td>
    <td>".htmlspecialchars($time)."</td>
    <td>".htmlspecialchars($doctor_id)."</td>
    <td>".htmlspecialchars($status)."</td>
    <td>".htmlspecialchars($notes)."</td>
    <td>
        <button class='btn btn-primary btn-sm' onclick='editAppointment($id)'>Edit</button>
        <button class='btn btn-danger btn-sm' onclick='deleteAppointment($id)'>Delete</button>
    </td>
</tr>";

echo json_encode(['success'=>true,'id'=>$id,'html'=>$row_html]);
