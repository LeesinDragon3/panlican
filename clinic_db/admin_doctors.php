<?php
// This file is loaded via AJAX, so no session check needed here
require_once 'db_connect.php';

// Handle doctor operations
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        $action = $_POST['action'];
        
        if ($action === 'add') {
            $name = $_POST['name'];
            $email = $_POST['email'];
            $specialization = $_POST['specialization'];
            $contact = $_POST['contact'] ?? '';
            $username = $_POST['username'];
            $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
            
            $stmt = $conn->prepare("INSERT INTO doctors (name, email, username, password, specialization, contact) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssssss", $name, $email, $username, $password, $specialization, $contact);
            
            if ($stmt->execute()) {
                echo json_encode(['success' => true, 'message' => 'Doctor added successfully!']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to add doctor.']);
            }
            exit;
        }
        
        if ($action === 'delete') {
            $id = intval($_POST['id']);
            $stmt = $conn->prepare("DELETE FROM doctors WHERE id = ?");
            $stmt->bind_param("i", $id);
            
            if ($stmt->execute()) {
                echo json_encode(['success' => true, 'message' => 'Doctor deleted successfully!']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to delete doctor.']);
            }
            exit;
        }
        
        if ($action === 'update') {
            $id = intval($_POST['id']);
            $name = $_POST['name'];
            $email = $_POST['email'];
            $specialization = $_POST['specialization'];
            $contact = $_POST['contact'] ?? '';
            $username = $_POST['username'];
            
            if (!empty($_POST['password'])) {
                $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
                $stmt = $conn->prepare("UPDATE doctors SET name=?, email=?, username=?, password=?, specialization=?, contact=? WHERE id=?");
                $stmt->bind_param("ssssssi", $name, $email, $username, $password, $specialization, $contact, $id);
            } else {
                $stmt = $conn->prepare("UPDATE doctors SET name=?, email=?, username=?, specialization=?, contact=? WHERE id=?");
                $stmt->bind_param("sssssi", $name, $email, $username, $specialization, $contact, $id);
            }
            
            if ($stmt->execute()) {
                echo json_encode(['success' => true, 'message' => 'Doctor updated successfully!']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to update doctor.']);
            }
            exit;
        }
    }
}

// Fetch all doctors
$searchTerm = '';
if (isset($_GET['search'])) {
    $searchTerm = $_GET['search'];
    $query = "SELECT * FROM doctors WHERE (name LIKE '%$searchTerm%' OR email LIKE '%$searchTerm%' OR specialization LIKE '%$searchTerm%') ORDER BY name ASC";
} else {
    $query = "SELECT * FROM doctors ORDER BY name ASC";
}
$doctors = $conn->query($query);
?>

<div class="doctors-management">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h5><i class="bi bi-person-badge me-2"></i>Manage Doctors</h5>
        <button class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#addDoctorModal">
            <i class="bi bi-plus-circle me-1"></i> Add New Doctor
        </button>
    </div>

    <!-- Search Bar -->
    <div class="card p-3 mb-3">
        <div class="row">
            <div class="col-md-6">
                <input type="text" id="searchDoctor" class="form-control" placeholder="Search by name, email, or specialty...">
            </div>
        </div>
    </div>

    <!-- Doctors Table -->
    <div class="card p-3">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead class="table-light">
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Specialty</th>
                        <th>Phone</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="doctorsTableBody">
                    <?php if($doctors && $doctors->num_rows > 0): ?>
                        <?php while($doctor = $doctors->fetch_assoc()): ?>
                            <tr>
                                <td><?= $doctor['id'] ?></td>
                                <td><strong><?= htmlspecialchars($doctor['name']) ?></strong></td>
                                <td><?= htmlspecialchars($doctor['email'] ?? 'N/A') ?></td>
                                <td><span class="badge bg-info"><?= htmlspecialchars($doctor['specialization'] ?? 'N/A') ?></span></td>
                                <td><?= htmlspecialchars($doctor['contact'] ?? 'N/A') ?></td>
                                <td>
                                    <button class="btn btn-sm btn-primary" onclick='editDoctor(<?= json_encode($doctor) ?>)'>
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    <button class="btn btn-sm btn-danger" onclick="deleteDoctor(<?= $doctor['id'] ?>, '<?= htmlspecialchars($doctor['name']) ?>')">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="text-center text-muted py-4">No doctors found</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Add Doctor Modal -->
<div class="modal fade" id="addDoctorModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-person-plus me-2"></i>Add New Doctor</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="addDoctorForm">
                <div class="modal-body">
                    <input type="hidden" name="action" value="add">
                    <div class="mb-3">
                        <label class="form-label">Full Name *</label>
                        <input type="text" name="name" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Email *</label>
                        <input type="email" name="email" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Username *</label>
                        <input type="text" name="username" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Password *</label>
                        <input type="password" name="password" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Specialization *</label>
                        <input type="text" name="specialization" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Contact Phone</label>
                        <input type="text" name="contact" class="form-control">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">Add Doctor</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Doctor Modal -->
<div class="modal fade" id="editDoctorModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-pencil me-2"></i>Edit Doctor</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="editDoctorForm">
                <div class="modal-body">
                    <input type="hidden" name="action" value="update">
                    <input type="hidden" name="id" id="editDoctorId">
                    <div class="mb-3">
                        <label class="form-label">Full Name *</label>
                        <input type="text" name="name" id="editDoctorName" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Email *</label>
                        <input type="email" name="email" id="editDoctorEmail" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Username *</label>
                        <input type="text" name="username" id="editDoctorUsername" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Password (leave blank to keep current)</label>
                        <input type="password" name="password" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Specialization *</label>
                        <input type="text" name="specialization" id="editDoctorSpecialization" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Contact Phone</label>
                        <input type="text" name="contact" id="editDoctorContact" class="form-control">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Doctor</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Search functionality
document.getElementById('searchDoctor').addEventListener('keyup', function() {
    const searchTerm = this.value;
    loadSection('admin_doctors.php?search=' + encodeURIComponent(searchTerm), document.querySelector('[onclick*="admin_doctors"]'));
});

// Add doctor form submission
document.getElementById('addDoctorForm').addEventListener('submit', function(e) {
    e.preventDefault();
    const formData = new FormData(this);
    
    fetch('admin_doctors.php', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            alert('✅ ' + data.message);
            bootstrap.Modal.getInstance(document.getElementById('addDoctorModal')).hide();
            loadSection('admin_doctors.php', document.querySelector('[onclick*="admin_doctors"]'));
        } else {
            alert('❌ ' + data.message);
        }
    })
    .catch(err => alert('❌ Failed to add doctor'));
});

// Edit doctor form submission
document.getElementById('editDoctorForm').addEventListener('submit', function(e) {
    e.preventDefault();
    const formData = new FormData(this);
    
    fetch('admin_doctors.php', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            alert('✅ ' + data.message);
            bootstrap.Modal.getInstance(document.getElementById('editDoctorModal')).hide();
            loadSection('admin_doctors.php', document.querySelector('[onclick*="admin_doctors"]'));
        } else {
            alert('❌ ' + data.message);
        }
    })
    .catch(err => alert('❌ Failed to update doctor'));
});

// Edit doctor function
function editDoctor(doctor) {
    document.getElementById('editDoctorId').value = doctor.id;
    document.getElementById('editDoctorName').value = doctor.name;
    document.getElementById('editDoctorEmail').value = doctor.email || '';
    document.getElementById('editDoctorUsername').value = doctor.username || '';
    document.getElementById('editDoctorSpecialization').value = doctor.specialization || '';
    document.getElementById('editDoctorContact').value = doctor.contact || '';
    
    const modal = new bootstrap.Modal(document.getElementById('editDoctorModal'));
    modal.show();
}

// Delete doctor function
function deleteDoctor(id, name) {
    if (confirm(`Are you sure you want to delete Dr. ${name}?`)) {
        const formData = new FormData();
        formData.append('action', 'delete');
        formData.append('id', id);
        
        fetch('admin_doctors.php', {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                alert('✅ ' + data.message);
                loadSection('admin_doctors.php', document.querySelector('[onclick*="admin_doctors"]'));
            } else {
                alert('❌ ' + data.message);
            }
        })
        .catch(err => alert('❌ Failed to delete doctor'));
    }
}
</script>