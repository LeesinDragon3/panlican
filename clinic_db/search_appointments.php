<?php
require_once 'db_connect.php';
session_start();

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'doctor') {
    exit('unauthorized');
}

$doctorId = $_SESSION['user']['id'];
$keyword = "%" . ($_POST['keyword'] ?? '') . "%";

$sql = "SELECT a.*, u.fullname AS patient_name 
        FROM appointments a
        JOIN users u ON a.patient_id = u.id
        WHERE a.doctor_id = ? 
        AND (u.fullname LIKE ? OR a.notes LIKE ?)
        ORDER BY a.date DESC, a.time DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("iss", $doctorId, $keyword, $keyword);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo "<tr><td colspan='8' class='text-center text-muted'>No results found.</td></tr>";
} else {
    $i=1;
    while($row = $result->fetch_assoc()){
        echo "<tr id='row-{$row['id']}'>
            <td>{$i}</td>
            <td>".htmlspecialchars($row['patient_name'])."</td>
            <td>{$row['date']}</td>
            <td>{$row['time']}</td>
            <td><span class='badge bg-".($row['status']=='completed'?'success':($row['status']=='pending'?'warning':'secondary'))."'>".htmlspecialchars($row['status'])."</span></td>
            <td>".($row['emergency']?'🚨 Yes':'No')."</td>
            <td>".htmlspecialchars($row['notes'])."</td>
            <td>
              <button class='btn btn-sm btn-primary me-1' onclick='editAppointment({$row['id']})'><i class=\"bi bi-pencil-square\"></i></button>
              <button class='btn btn-sm btn-danger' onclick='deleteAppointment({$row['id']})'><i class=\"bi bi-trash\"></i></button>
            </td>
          </tr>";
        $i++;
    }
}
$stmt->close();
