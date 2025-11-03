<?php
session_start();
require_once 'db_connect.php';

// Ensure only patients can access
if(!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'patient'){
    echo "<p class='text-danger'>Access denied.</p>";
    exit();
}

$patientId = $_SESSION['user']['id'];

// Fetch patient data
$stmt = $conn->prepare("SELECT name, age, address, contact, username FROM patients WHERE id=?");
$stmt->bind_param("i", $patientId);
$stmt->execute();
$result = $stmt->get_result();
$patient = $result->fetch_assoc();
$stmt->close();
?>

<div class="card p-4 shadow-sm" style="background-color: #f7fff9; border-left: 6px solid #28a745;">
    <h4 class="mb-4 text-success">Patient Profile</h4>

    <form id="profileForm">
        <div class="row mb-3">
            <div class="col-md-6">
                <label class="form-label">Full Name</label>
                <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($patient['name']) ?>" required>
            </div>
            <div class="col-md-6">
                <label class="form-label">Username</label>
                <input type="text" name="username" class="form-control" value="<?= htmlspecialchars($patient['username']) ?>" readonly>
            </div>
        </div>

        <div class="row mb-3">
            <div class="col-md-4">
                <label class="form-label">Age</label>
                <input type="number" name="age" class="form-control" value="<?= htmlspecialchars($patient['age']) ?>" required>
            </div>
            <div class="col-md-4">
                <label class="form-label">Contact</label>
                <input type="text" name="contact" class="form-control" value="<?= htmlspecialchars($patient['contact']) ?>" required>
            </div>
            <div class="col-md-4">
                <label class="form-label">Address</label>
                <input type="text" name="address" class="form-control" value="<?= htmlspecialchars($patient['address']) ?>" required>
            </div>
        </div>

        <button type="submit" class="btn btn-success px-4">Save Changes</button>
    </form>
</div>

<script>
document.getElementById('profileForm').addEventListener('submit', function(e){
    e.preventDefault();
    const formData = new FormData(this);

    fetch('update_patient_profile.php', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if(data.status === 'success'){
            alert('Profile updated successfully!');
        } else {
            alert('Error: ' + data.message);
        }
    })
    .catch(err => console.error(err));
});
</script>
