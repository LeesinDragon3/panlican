<?php
require_once 'db_connect.php';
session_start();

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'patient') {
    exit('Unauthorized');
}

$patient_id = $_SESSION['user']['id'];
$q = $_GET['q'] ?? '';

$sql = "SELECT a.id, d.fullname AS doctor_name, a.date, a.time, a.status, a.emergency, a.notes
        FROM appointments a
        JOIN users d ON a.doctor_id = d.id
        WHERE a.patient_id = ?
        AND (d.fullname LIKE ? OR a.status LIKE ? OR a.notes LIKE ?)
        ORDER BY a.date DESC, a.time DESC";

$stmt = $conn->prepare($sql);
$like = "%$q%";
$stmt->bind_param("isss", $patient_id, $like, $like, $like);
$stmt->execute();
$result = $stmt->get_result();
?>

<table class="table table-bordered table-striped">
  <thead>
    <tr>
      <th>Doctor</th>
      <th>Date</th>
      <th>Time</th>
      <th>Status</th>
      <th>Emergency</th>
      <th>Notes</th>
    </tr>
  </thead>
  <tbody>
    <?php if ($result->num_rows > 0): ?>
      <?php while ($row = $result->fetch_assoc()): ?>
        <tr>
          <td><?= htmlspecialchars($row['doctor_name']) ?></td>
          <td><?= htmlspecialchars($row['date']) ?></td>
          <td><?= htmlspecialchars($row['time']) ?></td>
          <td><?= htmlspecialchars($row['status']) ?></td>
          <td><?= $row['emergency'] ? '🚨 Yes' : 'No' ?></td>
          <td><?= htmlspecialchars($row['notes']) ?></td>
        </tr>
      <?php endwhile; ?>
    <?php else: ?>
      <tr><td colspan="6" class="text-center">No appointments found.</td></tr>
    <?php endif; ?>
  </tbody>
</table>
