<?php
// This file is loaded via AJAX
require_once 'db_connect.php';

// Fetch prescriptions with filters
$whereConditions = [];
$searchTerm = '';

if (isset($_GET['search']) && $_GET['search'] !== '') {
    $searchTerm = $_GET['search'];
    $whereConditions[] = "(p.medication LIKE '%$searchTerm%' OR pat.fullname LIKE '%$searchTerm%' OR doc.fullname LIKE '%$searchTerm%')";
}

$whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';

$query = "SELECT p.*, 
          pat.fullname as patient_name, pat.email as patient_email,
          doc.fullname as doctor_name, doc.specialty
          FROM prescriptions p
          JOIN users pat ON p.patient_id = pat.id
          JOIN users doc ON p.doctor_id = doc.id
          $whereClause
          ORDER BY p.created_at DESC";

$prescriptions = $conn->query($query);
?>

<div class="prescriptions-management">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h5><i class="bi bi-prescription2 me-2"></i>View All Prescriptions</h5>
    </div>

    <!-- Search Bar -->
    <div class="card p-3 mb-3">
        <div class="row g-3">
            <div class="col-md-6">
                <input type="text" id="searchPrescription" class="form-control" placeholder="Search by medication, patient, or doctor..." value="<?= htmlspecialchars($searchTerm) ?>">
            </div>
            <div class="col-md-2">
                <button class="btn btn-secondary w-100" onclick="clearSearch()">
                    <i class="bi bi-x-circle me-1"></i> Clear
                </button>
            </div>
        </div>
    </div>

    <!-- Prescriptions Table -->
    <div class="card p-3">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead class="table-light">
                    <tr>
                        <th>ID</th>
                        <th>Date</th>
                        <th>Patient</th>
                        <th>Doctor</th>
                        <th>Medication</th>
                        <th>Dosage</th>
                        <th>Instructions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if($prescriptions && $prescriptions->num_rows > 0): ?>
                        <?php while($rx = $prescriptions->fetch_assoc()): ?>
                            <tr>
                                <td><?= $rx['id'] ?></td>
                                <td><?= date('M d, Y', strtotime($rx['created_at'])) ?></td>
                                <td>
                                    <strong><?= htmlspecialchars($rx['patient_name']) ?></strong><br>
                                    <small class="text-muted"><?= htmlspecialchars($rx['patient_email']) ?></small>
                                </td>
                                <td>
                                    <strong>Dr. <?= htmlspecialchars($rx['doctor_name']) ?></strong><br>
                                    <small class="text-muted"><?= htmlspecialchars($rx['specialty']) ?></small>
                                </td>
                                <td><span class="badge bg-primary"><?= htmlspecialchars($rx['medication'] ?? 'N/A') ?></span></td>
                                <td><?= htmlspecialchars($rx['dosage'] ?? 'N/A') ?></td>
                                <td>
                                    <small><?= htmlspecialchars($rx['instructions'] ?? 'No instructions') ?></small>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" class="text-center text-muted py-4">No prescriptions found</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
// Search functionality
document.getElementById('searchPrescription').addEventListener('keyup', function() {
    const search = this.value;
    let url = 'admin_prescriptions.php';
    if (search) url += '?search=' + encodeURIComponent(search);
    loadSection(url, document.querySelector('[onclick*="admin_prescriptions"]'));
});

function clearSearch() {
    document.getElementById('searchPrescription').value = '';
    loadSection('admin_prescriptions.php', document.querySelector('[onclick*="admin_prescriptions"]'));
}
</script>