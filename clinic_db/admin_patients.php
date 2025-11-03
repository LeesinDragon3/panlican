<?php
// This file is loaded via AJAX
require_once 'db_connect.php';

// Handle patient operations
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        $action = $_POST['action'];
        
        if ($action === 'add') {
            $fullname = $_POST['fullname'];
            $email = $_POST['email'];
            $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
            $phone = $_POST['phone'] ?? '';
            $address = $_POST['address'] ?? '';
            
            $stmt = $conn->prepare("INSERT INTO users (fullname, email, password, role, phone, address) VALUES (?, ?, ?, 'patient', ?, ?)");
            $stmt->bind_param("sssss", $fullname, $email, $password, $phone, $address);
            
            if ($stmt->execute()) {
                echo json_encode(['success' => true, 'message' => 'Patient added successfully!']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to add patient.']);
            }
            exit;
        }
        
        if ($action === 'delete') {
            $id = intval($_POST['id']);
            $stmt = $conn->prepare("DELETE FROM users WHERE id = ? AND role = 'patient'");
            $stmt->bind_param("i", $id);
            
            if ($stmt->execute()) {
                echo json_encode(['success' => true, 'message' => 'Patient deleted successfully!']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to delete patient.']);
            }
            exit;
        }
        
        if ($action === 'update') {
            $id = intval($_POST['id']);
            $fullname = $_POST['fullname'];
            $email = $_POST['email'];
            $phone = $_POST['phone'] ?? '';
            $address = $_POST['address'] ?? '';
            
            if (!empty($_POST['password'])) {
                $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
                $stmt = $conn->prepare("UPDATE users SET fullname=?, email=?, password=?, phone=?, address=? WHERE id=? AND role='patient'");
                $stmt->bind_param("sssssi", $fullname, $email, $password, $phone, $address, $id);
            } else {
                $stmt = $conn->prepare("UPDATE users SET fullname=?, email=?, phone=?, address=? WHERE id=? AND role='patient'");
                $stmt->bind_param("ssssi", $fullname, $email, $phone, $address, $id);
            }
            
            if ($stmt->execute()) {
                echo json_encode(['success' => true, 'message' => 'Patient updated successfully!']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to update patient.']);
            }
            exit;
        }
    }
}

// Fetch all patients
$searchTerm = '';
if (isset($_GET['search'])) {
    $searchTerm = $_GET['search'];
    $query = "SELECT u.*, COUNT(a.id) as appointment_count FROM users u 
              LEFT JOIN appointments a ON u.id = a.patient_id 
              WHERE u.role='patient' AND (u.fullname LIKE '%$searchTerm%' OR u.email LIKE '%$searchTerm%') 
              GROUP BY u.id ORDER BY u.fullname ASC";
} else {
    $query = "SELECT u.*, COUNT(a.id) as appointment_count FROM users u 
              LEFT JOIN appointments a ON u.id = a.patient_id 
              WHERE u.role='patient' GROUP BY u.id ORDER BY u.fullname ASC";
}
$patients = $conn->query($query);
?>

<div class="patients-management">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h5><i class="bi bi-people me-2"></i>Manage Patients</h5>
        <button class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#addPatientModal">
            <i class="bi bi-plus-circle me-1"></i> Add New Patient
        </button>
    </div>

    <!-- Search Bar -->
    <div class="card p-3 mb-3">
        <div class="row">
            <div class="col-md-6">
                <input type="text" id="searchPatient" class="form-control" placeholder="Search by name or email...">
            </div>
        </div>
    </div>

    <!-- Patients Table -->
    <div class="card p-3">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead class="table-light">
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Appointments</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if($patients && $patients->num_rows > 0): ?>
                        <?php while($patient = $patients->fetch_assoc()): ?>
                            <tr>
                                <td><?= $patient['id'] ?></td>
                                <td><strong><?= htmlspecialchars($patient['fullname']) ?></strong></td>
                                <td><?= htmlspecialchars($patient['email']) ?></td>
                                <td><?= htmlspecialchars($patient['phone'] ?? 'N/A') ?></td>
                                <td><span class="badge bg-primary"><?= $patient['appointment_count'] ?></span></td>
                                <td>
                                    <button class="btn btn-sm btn-primary" onclick='editPatient(<?= json_encode($patient) ?>)'>
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    <button class="btn btn-sm btn-danger" onclick="deletePatient(<?= $patient['id'] ?>, '<?= htmlspecialchars($patient['fullname']) ?>')">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="text-center text-muted py-4">No patients found</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Add Patient Modal -->
<div class="modal fade" id="addPatientModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-person-plus me-2"></i>Add New Patient</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="addPatientForm">
                <div class="modal-body">
                    <input type="hidden" name="action" value="add">
                    <div class="mb-3">
                        <label class="form-label">Full Name *</label>
                        <input type="text" name="fullname" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Email *</label>
                        <input type="email" name="email" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Password *</label>
                        <input type="password" name="password" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Phone</label>
                        <input type="text" name="phone" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Address</label>
                        <textarea name="address" class="form-control" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">Add Patient</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Patient Modal -->
<div class="modal fade" id="editPatientModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-pencil me-2"></i>Edit Patient</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="editPatientForm">
                <div class="modal-body">
                    <input type="hidden" name="action" value="update">
                    <input type="hidden" name="id" id="editPatientId">
                    <div class="mb-3">
                        <label class="form-label">Full Name *</label>
                        <input type="text" name="fullname" id="editPatientName" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Email *</label>
                        <input type="email" name="email" id="editPatientEmail" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Password (leave blank to keep current)</label>
                        <input type="password" name="password" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Phone</label>
                        <input type="text" name="phone" id="editPatientPhone" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Address</label>
                        <textarea name="address" id="editPatientAddress" class="form-control" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Patient</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Search functionality
document.getElementById('searchPatient').addEventListener('keyup', function() {
    const searchTerm = this.value;
    loadSection('admin_patients.php?search=' + encodeURIComponent(searchTerm), document.querySelector('[onclick*="admin_patients"]'));
});

// Add patient form
document.getElementById('addPatientForm').addEventListener('submit', function(e) {
    e.preventDefault();
    const formData = new FormData(this);
    
    fetch('admin_patients.php', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            alert('✅ ' + data.message);
            bootstrap.Modal.getInstance(document.getElementById('addPatientModal')).hide();
            loadSection('admin_patients.php', document.querySelector('[onclick*="admin_patients"]'));
        } else {
            alert('❌ ' + data.message);
        }
    });
});

// Edit patient form
document.getElementById('editPatientForm').addEventListener('submit', function(e) {
    e.preventDefault();
    const formData = new FormData(this);
    
    fetch('admin_patients.php', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            alert('✅ ' + data.message);
            bootstrap.Modal.getInstance(document.getElementById('editPatientModal')).hide();
            loadSection('admin_patients.php', document.querySelector('[onclick*="admin_patients"]'));
        } else {
            alert('❌ ' + data.message);
        }
    });
});

function editPatient(patient) {
    document.getElementById('editPatientId').value = patient.id;
    document.getElementById('editPatientName').value = patient.fullname;
    document.getElementById('editPatientEmail').value = patient.email;
    document.getElementById('editPatientPhone').value = patient.phone || '';
    document.getElementById('editPatientAddress').value = patient.address || '';
    
    const modal = new bootstrap.Modal(document.getElementById('editPatientModal'));
    modal.show();
}

function deletePatient(id, name) {
    if (confirm(`Are you sure you want to delete ${name}?`)) {
        const formData = new FormData();
        formData.append('action', 'delete');
        formData.append('id', id);
        
        fetch('admin_patients.php', {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                alert('✅ ' + data.message);
                loadSection('admin_patients.php', document.querySelector('[onclick*="admin_patients"]'));
            } else {
                alert('❌ ' + data.message);
            }
        });
    }
}
</script>