<?php
session_start();
require_once 'db_connect.php';

$message = '';
$messageType = '';

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = trim($_POST['email']);
    
    // Validate email
    if (empty($email)) {
        $message = 'Please enter your email address.';
        $messageType = 'error';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = 'Please enter a valid email address.';
        $messageType = 'error';
    } else {
        // Check if email exists in database
        $stmt = $conn->prepare("SELECT id, fullname, email FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $user = $result->fetch_assoc();
            
            // Generate a unique reset token
            $token = bin2hex(random_bytes(32));
            $expiry = date('Y-m-d H:i:s', strtotime('+1 hour')); // Token expires in 1 hour
            
            // Store token in database
            $updateStmt = $conn->prepare("UPDATE users SET reset_token = ?, reset_token_expiry = ? WHERE email = ?");
            $updateStmt->bind_param("sss", $token, $expiry, $email);
            
            if ($updateStmt->execute()) {
                // Create reset link
                $resetLink = "http://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . "/reset_password.php?token=" . $token;
                
                // In a production environment, you would send this via email
                // For now, we'll display it (you can integrate PHPMailer or similar)
                
                // EMAIL SENDING CODE (commented out - uncomment and configure for production)
                /*
                $to = $email;
                $subject = "Password Reset Request - E-Clinic";
                $emailMessage = "
                <html>
                <head>
                    <title>Password Reset</title>
                </head>
                <body>
                    <h2>Password Reset Request</h2>
                    <p>Hello " . htmlspecialchars($user['fullname']) . ",</p>
                    <p>We received a request to reset your password. Click the link below to reset it:</p>
                    <p><a href='" . $resetLink . "'>Reset Password</a></p>
                    <p>This link will expire in 1 hour.</p>
                    <p>If you didn't request this, please ignore this email.</p>
                    <p>Best regards,<br>MED+ Clinic Team</p>
                </body>
                </html>
                ";
                
                $headers = "MIME-Version: 1.0" . "\r\n";
                $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
                $headers .= "From: noreply@eclinic.com" . "\r\n";
                
                mail($to, $subject, $emailMessage, $headers);
                */
                
                // For development/testing purposes, show the link
                $_SESSION['reset_link'] = $resetLink;
                $_SESSION['reset_email'] = $email;
                
                $message = 'Password reset instructions have been sent! (Check below for the reset link)';
                $messageType = 'success';
            } else {
                $message = 'An error occurred. Please try again later.';
                $messageType = 'error';
            }
            
            $updateStmt->close();
        } else {
            // Don't reveal if email exists or not (security best practice)
            $message = 'If an account exists with this email, you will receive password reset instructions.';
            $messageType = 'success';
        }
        
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Forgot Password - E-Clinic</title>
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

.reset-link-box {
  background: #f8f9fa;
  padding: 20px;
  border-radius: 10px;
  margin-top: 20px;
  border: 2px dashed #1a7c4a;
}

.reset-link-box h4 {
  color: #1a7c4a;
  font-size: 1rem;
  margin-bottom: 10px;
}

.reset-link-box p {
  font-size: 0.85rem;
  color: #666;
  margin-bottom: 10px;
}

.reset-link-box a {
  word-break: break-all;
  color: #1a7c4a;
  text-decoration: none;
  font-weight: 500;
}

.reset-link-box a:hover {
  text-decoration: underline;
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
    <h2>Forgot Password?</h2>
    <p>No worries! Enter your email address and we'll send you instructions to reset your password.</p>
  </div>

  <div class="icon-wrapper">
    <i class="bi bi-lock-fill"></i>
  </div>

  <?php if ($message): ?>
    <div class="alert alert-<?= $messageType ?>">
      <i class="bi bi-<?= $messageType === 'success' ? 'check-circle-fill' : 'exclamation-triangle-fill' ?>"></i>
      <span><?= htmlspecialchars($message) ?></span>
    </div>
  <?php endif; ?>

  <?php if (isset($_SESSION['reset_link']) && $messageType === 'success'): ?>
    <div class="reset-link-box">
      <h4><i class="bi bi-info-circle"></i> Development Mode - Reset Link</h4>
      <p>In production, this link would be sent to: <strong><?= htmlspecialchars($_SESSION['reset_email']) ?></strong></p>
      <p>For testing purposes, use this link to reset your password:</p>
      <a href="<?= htmlspecialchars($_SESSION['reset_link']) ?>" target="_blank">
        <?= htmlspecialchars($_SESSION['reset_link']) ?>
      </a>
    </div>
    <?php 
    unset($_SESSION['reset_link']); 
    unset($_SESSION['reset_email']);
    ?>
  <?php endif; ?>

  <form method="POST" action="">
    <div class="input-group">
      <i class="bi bi-envelope"></i>
      <input type="email" name="email" placeholder="Enter your email address" required>
    </div>

    <button type="submit" class="submit-btn">
      <i class="bi bi-send"></i>
      Send Reset Instructions
    </button>
  </form>

  <div class="back-link">
    <a href="auth.html">
      <i class="bi bi-arrow-left"></i>
      Back to Login
    </a>
  </div>
</div>

</body>
</html>