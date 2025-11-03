<?php
// This file is loaded via AJAX
require_once 'db_connect.php';

// Handle appointment operations
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        $action = $_POST['action'];
        
        if ($action === 'update_status') {
            $id = intval($_POST['id']);
            $status = $_POST['status'];
            
            $stmt = $conn->prepare("UPDATE appointments SET status = ? WHERE id = ?");
            $stmt->bind_param("si", $status, $id);
            
            if ($stmt->execute()) {
                echo json_encode(['success' => true, 'message' => 'Status updated successfully!']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to update status.']);
            }
            exit;
        }
        
        if ($action === 'delete') {
            $id = intval($_POST['id']);
            $stmt = $conn->prepare("DELETE FROM appointments WHERE id = ?");
            $stmt->bind_param("i", $id);
            
            if ($stmt->execute()) {
                echo json_encode(['success' => true, 'message' => 'Appointment deleted successfully!']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to delete appointment.']);
            }
            exit;
        }
    }
}

// Fetch appointments with filters
$whereConditions = [];
$searchTerm = '';

if (isset($_GET['status']) && $_GET['status'] !== '') {
    $status = $_GET['status'];
    $whereConditions[] = "a.status = '$status'";
}

if (isset($_GET['search']) && $_GET['search'] !== '') {
    $searchTerm = $_GET['search'];
    $whereConditions[] = "(p.fullname LIKE '%$searchTerm%' OR d.fullname LIKE '%$searchTerm%')";
}

$whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';

$query = "SELECT a.*, 
          p.fullname as patient_name, p.email as patient_email,
          d.fullname as doctor_name, d.specialty
          FROM appointments a
          JOIN users p ON a.patient_id = p.id
          JOIN users d ON a.doctor_id = d.id
          $whereClause
          ORDER BY a.date DESC, a.time DESC";

$appointments = $conn->query($query);
?>

<div class="appointments-management">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h5><i class="bi bi-calendar-check me-2"></i>Manage Appointments</h5>
    </div>

    <!-- Filters -->
    <div class="card p-3 mb-3">
        <div class="row g-3">
            <div class="col-md-4">
                <input type="text" id="searchAppointment" class="form-control" placeholder="Search patient or doctor name..." value="<?= htmlspecialchars($searchTerm) ?>">
            </div>
            <div class="col-md-3">
                <select id="filterStatus" class="form-select">
                    <option value="">All Status</option>
                    <option value="pending" <?= isset($_GET['status']) && $_GET['status'] === 'pending' ? 'selected' : '' ?>>Pending</option>
                    <option value="scheduled" <?= isset($_GET['status']) && $_GET['status'] === 'scheduled' ? 'selected' : '' ?>>Scheduled</option>
                    <option value="completed" <?= isset($_GET['status']) && $_GET['status'] === 'completed' ? 'selected' : '' ?>>Completed</option>
                    <option value="cancelled" <?= isset($_GET['status']) && $_GET['status'] === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                </select>
            </div>
            <div class="col-md-2">
                <button class="btn btn-secondary w-100" onclick="clearFilters()">
                    <i class="bi bi-x-circle me-1"></i> Clear
                </button>
            </div>
        </div>
    </div>

    <!-- Appointments Table -->
    <div class="card p-3">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead class="table-light">
                    <tr>
                        <th>ID</th>
                        <th>Date & Time</th>
                        <th>Patient</th>
                        <th>Doctor</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if($appointments && $appointments->num_rows > 0): ?>
                        <?php while($appt = $appointments->fetch_assoc()): ?>
                            <tr>
                                <td><?= $appt['id'] ?></td>
                                <td>
                                    <strong><?= date('M d, Y', strtotime($appt['date'])) ?></strong><br>
                                    <small class="text-muted"><?= date('g:i A', strtotime($appt['time'])) ?></small>
                                </td>
                                <td>
                                    <strong><?= htmlspecialchars($appt['patient_name']) ?></strong><br>
                                    <small class="text-muted"><?= htmlspecialchars($appt['patient_email']) ?></small>
                                </td>
                                <td>
                                    <strong>Dr. <?= htmlspecialchars($appt['doctor_name']) ?></strong><br>
                                    <small class="text-muted"><?= htmlspecialchars($appt['specialty']) ?></small>
                                </td>
                                <td>
                                    <select class="form-select form-select-sm status-select" onchange="updateStatus(<?= $appt['id'] ?>, this.value)">
                                        <option value="pending" <?= $appt['status'] === 'pending' ? 'selected' : '' ?>>Pending</option>
                                        <option value="scheduled" <?= $appt['status'] === 'scheduled' ? 'selected' : '' ?>>Scheduled</option>
                                        <option value="completed" <?= $appt['status'] === 'completed' ? 'selected' : '' ?>>Completed</option>
                                        <option value="cancelled" <?= $appt['status'] === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                                    </select>
                                </td>
                                <td>
                                    <button class="btn btn-sm btn-danger" onclick="deleteAppointment(<?= $appt['id'] ?>)">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="text-center text-muted py-4">No appointments found</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<style>
.status-select {
    width: 130px;
}
</style>

<script>
// Search functionality
document.getElementById('searchAppointment').addEventListener('keyup', function() {
    applyFilters();
});

document.getElementById('filterStatus').addEventListener('change', function() {
    applyFilters();
});

function applyFilters() {
    const search = document.getElementById('searchAppointment').value;
    const status = document.getElementById('filterStatus').value;
    
    let url = 'admin_appointments.php?';
    if (search) url += 'search=' + encodeURIComponent(search) + '&';
    if (status) url += 'status=' + status;
    
    loadSection(url, document.querySelector('[onclick*="admin_appointments"]'));
}

function clearFilters() {
    document.getElementById('searchAppointment').value = '';
    document.getElementById('filterStatus').value = '';
    loadSection('admin_appointments.php', document.querySelector('[onclick*="admin_appointments"]'));
}

function updateStatus(id, status) {
    const formData = new FormData();
    formData.append('action', 'update_status');
    formData.append('id', id);
    formData.append('status', status);
    
    fetch('admin_appointments.php', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            // Silent update - no alert needed
        } else {
            alert('❌ ' + data.message);
        }
    });
}

function deleteAppointment(id) {
    if (confirm('Are you sure you want to delete this appointment?')) {
        const formData = new FormData();
        formData.append('action', 'delete');
        formData.append('id', id);
        
        fetch('admin_appointments.php', {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                alert('✅ ' + data.message);
                loadSection('admin_appointments.php', document.querySelector('[onclick*="admin_appointments"]'));
            } else {
                alert('❌ ' + data.message);
            }
        });
    }
}
</script>