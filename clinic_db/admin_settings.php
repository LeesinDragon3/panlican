<?php
// This file is loaded via AJAX
require_once 'db_connect.php';

// Get current admin info
session_start();
$adminId = $_SESSION['user']['id'];
$adminQuery = "SELECT * FROM users WHERE id = $adminId AND role = 'admin'";
$adminResult = $conn->query($adminQuery);
$admin = $adminResult->fetch_assoc();

// Handle settings update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        $action = $_POST['action'];
        
        if ($action === 'update_profile') {
            $fullname = $_POST['fullname'];
            $email = $_POST['email'];
            
            if (!empty($_POST['password'])) {
                $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
                $stmt = $conn->prepare("UPDATE users SET fullname=?, email=?, password=? WHERE id=? AND role='admin'");
                $stmt->bind_param("sssi", $fullname, $email, $password, $adminId);
            } else {
                $stmt = $conn->prepare("UPDATE users SET fullname=?, email=? WHERE id=? AND role='admin'");
                $stmt->bind_param("ssi", $fullname, $email, $adminId);
            }
            
            if ($stmt->execute()) {
                // Update session
                $_SESSION['user']['fullname'] = $fullname;
                $_SESSION['user']['email'] = $email;
                echo json_encode(['success' => true, 'message' => 'Profile updated successfully!']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to update profile.']);
            }
            exit;
        }
    }
}
?>

<div class="settings-management">
    <div class="mb-3">
        <h5><i class="bi bi-gear me-2"></i>System Settings</h5>
    </div>

    <!-- Admin Profile Settings -->
    <div class="card p-3 mb-3">
        <h6 class="text-muted mb-3"><i class="bi bi-person-circle me-2"></i>Admin Profile</h6>
        <form id="updateProfileForm">
            <input type="hidden" name="action" value="update_profile">
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Full Name</label>
                    <input type="text" name="fullname" class="form-control" value="<?= htmlspecialchars($admin['fullname']) ?>" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Email</label>
                    <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($admin['email']) ?>" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">New Password (leave blank to keep current)</label>
                    <input type="password" name="password" class="form-control" placeholder="Enter new password">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Confirm Password</label>
                    <input type="password" id="confirmPassword" class="form-control" placeholder="Confirm new password">
                </div>
                <div class="col-12">
                    <button type="submit" class="btn btn-success">
                        <i class="bi bi-check-circle me-1"></i> Save Changes
                    </button>
                </div>
            </div>
        </form>
    </div>

    <!-- System Information -->
    <div class="card p-3 mb-3">
        <h6 class="text-muted mb-3"><i class="bi bi-info-circle me-2"></i>System Information</h6>
        <div class="row g-3">
            <div class="col-md-6">
                <div class="p-3" style="background:#f8f9fa; border-radius:8px; border-left:4px solid #1a7c4a;">
                    <strong>System Name</strong>
                    <p class="mb-0 text-muted">MED+ Clinic E-Clinic System</p>
                </div>
            </div>
            <div class="col-md-6">
                <div class="p-3" style="background:#f8f9fa; border-radius:8px; border-left:4px solid #1a7c4a;">
                    <strong>Version</strong>
                    <p class="mb-0 text-muted">v1.0.0</p>
                </div>
            </div>
            <div class="col-md-6">
                <div class="p-3" style="background:#f8f9fa; border-radius:8px; border-left:4px solid #1a7c4a;">
                    <strong>Database Status</strong>
                    <p class="mb-0 text-success"><i class="bi bi-check-circle me-1"></i>Connected</p>
                </div>
            </div>
            <div class="col-md-6">
                <div class="p-3" style="background:#f8f9fa; border-radius:8px; border-left:4px solid #1a7c4a;">
                    <strong>Last Login</strong>
                    <p class="mb-0 text-muted"><?= date('M d, Y g:i A') ?></p>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="card p-3 mb-3">
        <h6 class="text-muted mb-3"><i class="bi bi-lightning me-2"></i>Quick Actions</h6>
        <div class="row g-2">
            <div class="col-auto">
                <button class="btn btn-outline-danger" onclick="if(confirm('Clear all cached data?')) alert('Cache cleared!')">
                    <i class="bi bi-trash me-1"></i> Clear Cache
                </button>
            </div>
            <div class="col-auto">
                <button class="btn btn-outline-primary" onclick="alert('Backup feature coming soon!')">
                    <i class="bi bi-cloud-download me-1"></i> Backup Database
                </button>
            </div>
            <div class="col-auto">
                <button class="btn btn-outline-warning" onclick="window.location.reload()">
                    <i class="bi bi-arrow-clockwise me-1"></i> Reload System
                </button>
            </div>
        </div>
    </div>

    <!-- Appearance Settings -->
    <div class="card p-3 mb-3">
        <h6 class="text-muted mb-3"><i class="bi bi-palette me-2"></i>Appearance</h6>
        <div class="form-check form-switch">
            <input class="form-check-input" type="checkbox" id="darkModeSwitch" disabled>
            <label class="form-check-label" for="darkModeSwitch">
                Dark Mode (Coming Soon)
            </label>
        </div>
    </div>

    <!-- Notification Settings -->
    <div class="card p-3">
        <h6 class="text-muted mb-3"><i class="bi bi-bell me-2"></i>Notification Settings</h6>
        <div class="form-check form-switch mb-2">
            <input class="form-check-input" type="checkbox" id="emailNotif" checked disabled>
            <label class="form-check-label" for="emailNotif">
                Email Notifications (Coming Soon)
            </label>
        </div>
        <div class="form-check form-switch">
            <input class="form-check-input" type="checkbox" id="smsNotif" disabled>
            <label class="form-check-label" for="smsNotif">
                SMS Notifications (Coming Soon)
            </label>
        </div>
    </div>
</div>

<script>
// Update profile form
document.getElementById('updateProfileForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const password = document.querySelector('[name="password"]').value;
    const confirmPassword = document.getElementById('confirmPassword').value;
    
    if (password && password !== confirmPassword) {
        alert('❌ Passwords do not match!');
        return;
    }
    
    const formData = new FormData(this);
    
    fetch('admin_settings.php', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            alert('✅ ' + data.message);
            // Reload dashboard to update name in navbar
            location.reload();
        } else {
            alert('❌ ' + data.message);
        }
    })
    .catch(err => alert('❌ Failed to update profile'));
});
</script>