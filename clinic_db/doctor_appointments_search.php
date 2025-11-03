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
$sql = "SELECT a.id, a.date, a.time, a.status, u.fullname 
        FROM appointments a 
        LEFT JOIN users u ON a.patient_id = u.id 
        WHERE a.doctor_id = ? AND (u.fullname LIKE ? OR a.date LIKE ?)
        ORDER BY a.date DESC, a.time DESC";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    echo "<div class='alert alert-danger'>Query error</div>";
    exit;
}

$stmt->bind_param("iss", $doctor_id, $searchTerm, $searchTerm);
$stmt->execute();
$result = $stmt->get_result();
?>

<div class="card shadow-sm p-4">
  <div class="d-flex justify-content-between align-items-center mb-4">
    <h5 class="mb-0 text-success">
      <i class="bi bi-calendar-check me-2"></i>My Appointments
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
          <th>Date</th>
          <th>Time</th>
          <th>Status</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php if ($result->num_rows === 0): ?>
          <tr><td colspan="6" class="text-center text-muted py-4">No results found</td></tr>
        <?php else: $i=1; while($row = $result->fetch_assoc()): ?>
          <tr>
            <td><?= $i++ ?></td>
            <td><?= htmlspecialchars($row['fullname'] ?? 'N/A') ?></td>
            <td><?= date('M d, Y', strtotime($row['date'])) ?></td>
            <td><?= htmlspecialchars($row['time']) ?></td>
            <td>
              <?php 
                $status = strtolower($row['status']);
                $badgeClass = $status === 'scheduled' ? 'bg-info' : ($status === 'completed' ? 'bg-success' : 'bg-warning');
              ?>
              <span class="badge <?= $badgeClass ?>"><?= ucfirst($row['status']) ?></span>
            </td>
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