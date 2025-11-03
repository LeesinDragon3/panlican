<?php
session_start();
require_once 'db_connect.php';

$message = '';
$messageType = '';
$validToken = false;
$token = '';

// Check if token is provided
if (isset($_GET['token'])) {
    $token = $_GET['token'];
    
    // Validate token
    $stmt = $conn->prepare("SELECT id, email, fullname, reset_token_expiry FROM users WHERE reset_token = ?");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        
        // Check if token has expired
        if (strtotime($user['reset_token_expiry']) > time()) {
            $validToken = true;
        } else {
            $message = 'This password reset link has expired. Please request a new one.';
            $messageType = 'error';
        }
    } else {
        $message = 'Invalid password reset link.';
        $messageType = 'error';
    }
    
    $stmt->close();
} else {
    $message = 'No reset token provided.';
    $messageType = 'error';
}

// Handle password reset form submission
if ($_SERVER["REQUEST_METHOD"] === "POST" && $validToken) {
    $newPassword = trim($_POST['password']);
    $confirmPassword = trim($_POST['confirm_password']);
    $tokenPost = trim($_POST['token']);
    
    // Validate passwords
    if (empty($newPassword) || empty($confirmPassword)) {
        $message = 'Please fill in all fields.';
        $messageType = 'error';
    } elseif (strlen($newPassword) < 6) {
        $message = 'Password must be at least 6 characters long.';
        $messageType = 'error';
    } elseif ($newPassword !== $confirmPassword) {
        $message = 'Passwords do not match.';
        $messageType = 'error';
    } else {
        // Hash the new password
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
        
        // Update password and clear reset token
        $updateStmt = $conn->prepare("UPDATE users SET password = ?, reset_token = NULL, reset_token_expiry = NULL WHERE reset_token = ?");
        $updateStmt->bind_param("ss", $hashedPassword, $tokenPost);
        
        if ($updateStmt->execute()) {
            $message = 'Password successfully reset! You can now login with your new password.';
            $messageType = 'success';
            $validToken = false; // Hide the form after successful reset
        } else {
            $message = 'An error occurred. Please try again.';
            $messageType = 'error';
        }
        
        $updateStmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Reset Password - E-Clinic</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
<style>
* {
  margin: 0;
  padding: 0;
  box-sizing: border-box;
}

body {
  font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
  background: linear-gradient(135deg, #1a7c4a 0%, #2ea169 100%);
  min-height: 100vh;
  display: flex;
  justify-content: center;
  align-items: center;
  padding: 20px;
}

.container {
  background: white;
  border-radius: 20px;
  box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
  padding: 50px 40px;
  width: 100%;
  max-width: 500px;
}

.header {
  text-align: center;
  margin-bottom: 30px;
}

.logo {
  font-size: 2rem;
  font-weight: 700;
  color: #1a7c4a;
  margin-bottom: 10px;
}

.logo span {
  color: #2ea169;
}

.header h2 {
  color: #333;
  font-size: 1.5rem;
  margin-bottom: 10px;
}

.header p {
  color: #666;
  font-size: 0.9rem;
  line-height: 1.5;
}

.icon-wrapper {
  text-align: center;
  margin-bottom: 25px;
}

.icon-wrapper i {
  font-size: 4rem;
  color: #1a7c4a;
  opacity: 0.8;
}

.alert {
  padding: 15px 20px;
  border-radius: 10px;
  margin-bottom: 25px;
  font-size: 0.9rem;
  display: flex;
  align-items: center;
  gap: 10px;
}

.alert i {
  font-size: 1.2rem;
}

.alert-success {
  background: #d4edda;
  color: #155724;
  border: 1px solid #c3e6cb;
}

.alert-error {
  background: #f8d7da;
  color: #721c24;
  border: 1px solid #f5c6cb;
}

.input-group {
  position: relative;
  margin-bottom: 25px;
}

.input-group i {
  position: absolute;
  left: 15px;
  top: 50%;
  transform: translateY(-50%);
  color: #999;
  font-size: 1.1rem;
}

.input-group input {
  width: 100%;
  padding: 14px 15px 14px 45px;
  border: 2px solid #e0e0e0;
  border-radius: 10px;
  font-size: 0.95rem;
  transition: all 0.3s;
  outline: none;
}

.input-group input:focus {
  border-color: #1a7c4a;
  box-shadow: 0 0 0 4px rgba(26, 124, 74, 0.1);
}

.password-strength {
  font-size: 0.8rem;
  margin-top: 5px;
  padding-left: 45px;
}

.password-strength.weak { color: #dc3545; }
.password-strength.medium { color: #ffc107; }
.password-strength.strong { color: #28a745; }

.submit-btn {
  width: 100%;
  padding: 14px;
  background: linear-gradient(135deg, #1a7c4a 0%, #2ea169 100%);
  color: white;
  border: none;
  border-radius: 10px;
  font-size: 1rem;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.3s;
  box-shadow: 0 4px 15px rgba(26, 124, 74, 0.3);
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
}

.submit-btn:hover {
  transform: translateY(-2px);
  box-shadow: 0 6px 20px rgba(26, 124, 74, 0.4);
}

.submit-btn:active {
  transform: translateY(0);
}

.back-link {
  text-align: center;
  margin-top: 20px;
}

.back-link a {
  color: #1a7c4a;
  text-decoration: none;
  font-weight: 500;
  font-size: 0.9rem;
  transition: color 0.3s;
  display: inline-flex;
  align-items: center;
  gap: 5px;
}

.back-link a:hover {
  color: #145c37;
}

.password-requirements {
  background: #f8f9fa;
  padding: 15px;
  border-radius: 10px;
  margin-bottom: 20px;
  font-size: 0.85rem;
}

.password-requirements h4 {
  color: #333;
  font-size: 0.9rem;
  margin-bottom: 10px;
}

.password-requirements ul {
  list-style: none;
  padding-left: 0;
}

.password-requirements li {
  padding: 3px 0;
  color: #666;
}

.password-requirements li i {
  margin-right: 8px;
  color: #1a7c4a;
}

@media (max-width: 768px) {
  .container {
    padding: 40px 30px;
  }
  
  .header h2 {
    font-size: 1.3rem;
  }
}
</style>
</head>
<body>

<div class="container">
  <div class="header">
    <div class="logo">MED<span>+</span> Clinic</div>
    <h2>Reset Your Password</h2>
    <p>Enter your new password below to reset your account password.</p>
  </div>

  <div class="icon-wrapper">
    <i class="bi bi-shield-lock-fill"></i>
  </div>

  <?php if ($message): ?>
    <div class="alert alert-<?= $messageType ?>">
      <i class="bi bi-<?= $messageType === 'success' ? 'check-circle-fill' : 'exclamation-triangle-fill' ?>"></i>
      <span><?= htmlspecialchars($message) ?></span>
    </div>
  <?php endif; ?>

  <?php if ($validToken): ?>
    <div class="password-requirements">
      <h4><i class="bi bi-info-circle"></i> Password Requirements:</h4>
      <ul>
        <li><i class="bi bi-check2"></i> At least 6 characters long</li>
        <li><i class="bi bi-check2"></i> Use a mix of letters and numbers</li>
        <li><i class="bi bi-check2"></i> Avoid common passwords</li>
      </ul>
    </div>

    <form method="POST" action="" id="reset-form">
      <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">
      
      <div class="input-group">
        <i class="bi bi-lock"></i>
        <input type="password" name="password" id="password" placeholder="New Password" required minlength="6">
      </div>
      <div class="password-strength" id="strength-indicator"></div>

      <div class="input-group">
        <i class="bi bi-lock-fill"></i>
        <input type="password" name="confirm_password" id="confirm-password" placeholder="Confirm New Password" required minlength="6">
      </div>

      <button type="submit" class="submit-btn">
        <i class="bi bi-check-circle"></i>
        Reset Password
      </button>
    </form>
  <?php elseif ($messageType === 'success'): ?>
    <div class="back-link">
      <a href="auth.html">
        <i class="bi bi-box-arrow-in-right"></i>
        Go to Login
      </a>
    </div>
  <?php else: ?>
    <div class="back-link">
      <a href="forgot_password.php">
        <i class="bi bi-arrow-left"></i>
        Request New Reset Link
      </a>
    </div>
  <?php endif; ?>
</div>

<script>
// Password strength indicator
const passwordInput = document.getElementById('password');
const strengthIndicator = document.getElementById('strength-indicator');

if (passwordInput && strengthIndicator) {
  passwordInput.addEventListener('input', function() {
    const password = this.value;
    let strength = 0;
    
    if (password.length >= 6) strength++;
    if (password.length >= 10) strength++;
    if (/[a-z]/.test(password) && /[A-Z]/.test(password)) strength++;
    if (/\d/.test(password)) strength++;
    if (/[^a-zA-Z\d]/.test(password)) strength++;
    
    if (password.length === 0) {
      strengthIndicator.textContent = '';
      strengthIndicator.className = 'password-strength';
    } else if (strength <= 2) {
      strengthIndicator.textContent = 'Weak password';
      strengthIndicator.className = 'password-strength weak';
    } else if (strength <= 3) {
      strengthIndicator.textContent = 'Medium strength';
      strengthIndicator.className = 'password-strength medium';
    } else {
      strengthIndicator.textContent = 'Strong password';
      strengthIndicator.className = 'password-strength strong';
    }
  });
}

// Confirm password validation
const form = document.getElementById('reset-form');
const confirmPassword = document.getElementById('confirm-password');

if (form) {
  form.addEventListener('submit', function(e) {
    if (passwordInput.value !== confirmPassword.value) {
      e.preventDefault();
      alert('Passwords do not match!');
      confirmPassword.focus();
    }
  });
}
</script>

</body>
</html>