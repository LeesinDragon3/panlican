<?php
require_once 'db_connect.php';
session_start();

// ✅ Only allow logged-in doctors
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'doctor') {
    echo "Access denied.";
    exit();
}

if (isset($_POST['id'], $_POST['date'], $_POST['time'], $_POST['status'], $_POST['notes'])) {
    $id = intval($_POST['id']);
    $date = $_POST['date'];
    $time = $_POST['time'];
    $status = $_POST['status'];
    $notes = $_POST['notes'];
    $doctorId = $_SESSION['user']['id'];

    // ✅ Update only if the appointment belongs to this doctor
    $stmt = $conn->prepare("UPDATE appointments SET date = ?, time = ?, status = ?, notes = ? WHERE id = ? AND doctor_id = ?");
    $stmt->bind_param("ssssii", $date, $time, $status, $notes, $id, $doctorId);

    if ($stmt->execute()) {
        echo "Appointment updated successfully.";
    } else {
        echo "Error updating appointment.";
    }

    $stmt->close();
} else {
    echo "Missing required fields.";
}
?>
