<?php
require_once 'db_connect.php';
session_start();

// ✅ Restrict to doctors
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'doctor') {
    echo "<p class='text-danger'>Unauthorized access.</p>";
    exit;
}

$doctor_id = $_SESSION['user']['id'];

// ✅ FIXED: Using correct column names from your schema
$sql = "SELECT DISTINCT a.patient_id, u.id, u.fullname, u.email, u.specialty, u.phone
        FROM appointments a 
        LEFT JOIN users u ON a.patient_id = u.id 
        WHERE a.doctor_id = ? AND u.role = 'patient'
        ORDER BY u.fullname ASC";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    die("SQL prepare failed: " . $conn->error);
}

$stmt->bind_param("i", $doctor_id);
$stmt->execute();
$result = $stmt->get_result();
?>

<div class="card shadow-sm p-3">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="mb-0 text-success"><i class="bi bi-people me-2"></i>My Patients</h5>
    <form class="d-flex ajax-search" method="post" action="doctor_patients_search.php">
      <input type="text" name="keyword" class="form-control form-control-sm me-2" placeholder="Search patients...">
      <button class="btn btn-success btn-sm">Search</button>
    </form>
  </div>

  <div class="table-responsive">
    <table class="table table-hover align-middle">
      <thead class="table-light">
        <tr>
          <th>#</th>
          <th>Full Name</th>
          <th>Email</th>
          <th>Specialty</th>
          <th>Phone</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php if ($result->num_rows === 0): ?>
          <tr><td colspan="6" class="text-center text-muted">No patients found.</td></tr>
        <?php else: $i=1; while($row = $result->fetch_assoc()): ?>
          <tr>
            <td><?= $i++ ?></td>
            <td><?= htmlspecialchars($row['fullname'] ?? 'N/A') ?></td>
            <td><?= htmlspecialchars($row['email'] ?? 'N/A') ?></td>
            <td><?= htmlspecialchars($row['specialty'] ?? 'N/A') ?></td>
            <td><?= htmlspecialchars($row['phone'] ?? 'N/A') ?></td>
            <td>
              <button class="btn btn-outline-success btn-sm" onclick="viewPatientDetails(<?= $row['patient_id'] ?>)">
                <i class="bi bi-eye"></i> View
              </button>
            </td>
          </tr>
        <?php endwhile; endif; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Patient Details Modal -->
<div class="modal fade" id="patientDetailsModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content" id="patientModalContent">
      <!-- loaded dynamically -->
    </div>
  </div>
</div>

<script>
function viewPatientDetails(id) {
  const modal = new bootstrap.Modal(document.getElementById('patientDetailsModal'));
  const modalBody = document.getElementById('patientModalContent');
  modalBody.innerHTML = "<div class='text-center p-4'><div class='spinner-border text-success'></div><p>Loading...</p></div>";

  fetch('patient_details.php?id=' + id)
    .then(res => res.text())
    .then(html => { modalBody.innerHTML = html; })
    .catch(() => { modalBody.innerHTML = "<div class='text-danger text-center p-4'>Failed to load patient details.</div>"; });
  modal.show();
}
</script>