<?php
require_once 'db_connect.php';
session_start();

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'doctor') {
    echo "<p class='text-danger'>Unauthorized access.</p>";
    exit;
}

$doctor_id = $_SESSION['user']['id'];
$keyword = $_POST['keyword'] ?? '';

$searchTerm = '%' . $keyword . '%';
$sql = "SELECT p.id, p.medicine, p.dosage, p.instructions, p.date_issued, u.fullname
        FROM prescriptions p 
        LEFT JOIN appointments a ON p.appointment_id = a.id
        LEFT JOIN users u ON a.patient_id = u.id
        WHERE p.doctor_id = $doctor_id AND (u.fullname LIKE '$searchTerm' OR p.medicine LIKE '$searchTerm')
        ORDER BY p.date_issued DESC";

$result = $conn->query($sql);

if (!$result) {
    echo "<div class='alert alert-danger'>Query error: " . $conn->error . "</div>";
    exit;
}
?>

<div class="card shadow-sm p-4">
  <div class="d-flex justify-content-between align-items-center mb-4">
    <h5 class="mb-0 text-success">
      <i class="bi bi-prescription2 me-2"></i>Prescriptions
      <small class="text-muted"><?= $result->num_rows ?> result(s)</small>
    </h5>
    <a href="#" onclick="location.reload()" class="btn btn-outline-secondary btn-sm">Clear</a>
  </div>

  <div class="table-responsive">
    <table class="table table-hover align-middle">
      <thead class="table-light">
        <tr>
          <th>#</th>
          <th>Patient Name</th>
          <th>Medicine</th>
          <th>Dosage</th>
          <th>Date Issued</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php if ($result->num_rows === 0): ?>
          <tr><td colspan="6" class="text-center text-muted py-4">No prescriptions found for "<?= htmlspecialchars($keyword) ?>"</td></tr>
        <?php else: $i=1; while($row = $result->fetch_assoc()): ?>
          <tr>
            <td><?= $i++ ?></td>
            <td><?= htmlspecialchars($row['fullname'] ?? 'N/A') ?></td>
            <td><strong><?= htmlspecialchars($row['medicine'] ?? 'N/A') ?></strong></td>
            <td><?= htmlspecialchars($row['dosage'] ?? 'N/A') ?></td>
            <td><?= date('M d, Y', strtotime($row['date_issued'])) ?></td>
            <td>
              <button class="btn btn-outline-primary btn-sm" title="View">
                <i class="bi bi-eye"></i>
              </button>
            </td>
          </tr>
        <?php endwhile; endif; ?>
      </tbody>
    </table>
  </div>
</div>