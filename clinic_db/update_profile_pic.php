<?php
require_once 'db_connect.php';
session_start();

if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit;
}

// sanitize
$id = intval($_SESSION['user']['id']);
$fullname = isset($_POST['fullname']) ? $conn->real_escape_string($_POST['fullname']) : '';
$email = isset($_POST['email']) ? $conn->real_escape_string($_POST['email']) : '';
$profile_pic = isset($_POST['profile_pic']) ? $conn->real_escape_string($_POST['profile_pic']) : 'default.png';

// optional: ensure selected filename belongs to user's role to avoid tampering
$role = $_SESSION['user']['role'] ?? '';
$allowed = [];
if ($role === 'patient') {
    $allowed = ['patient1.png','patient2.png','patient3.png','patient4.png','patient5.png'];
    $profile_pic = in_array($profile_pic, $allowed) ? $profile_pic : $_SESSION['user']['profile_pic'] ?? $allowed[0];
    $profile_pic = 'patients/' . $profile_pic;
} elseif ($role === 'doctor') {
    $allowed = ['doctor1.png','doctor2.png','doctor3.png','doctor4.png','doctor5.png'];
    $profile_pic = in_array($profile_pic, $allowed) ? $profile_pic : $_SESSION['user']['profile_pic'] ?? $allowed[0];
    $profile_pic = 'doctors/' . $profile_pic;
} elseif ($role === 'admin') {
    $allowed = ['admin1.png','admin2.png','admin3.png','admin4.png','admin5.png'];
    $profile_pic = in_array($profile_pic, $allowed) ? $profile_pic : $_SESSION['user']['profile_pic'] ?? $allowed[0];
    $profile_pic = 'admins/' . $profile_pic;
} else {
    // fallback - keep previous
    $profile_pic = $_SESSION['user']['profile_pic'] ?? 'patients/patient1.png';
}

// Update DB
$sql = "UPDATE users SET fullname='$fullname', email='$email', profile_pic='$profile_pic' WHERE id=$id";
if ($conn->query($sql) === false) {
    // On DB error, show message for debugging (remove in production)
    echo "<p class='text-danger'>Database error: " . htmlspecialchars($conn->error) . "</p>";
    exit;
}

// update session
$_SESSION['user']['fullname'] = $fullname;
$_SESSION['user']['email'] = $email;
$_SESSION['user']['profile_pic'] = $profile_pic;

// return to dashboard or wherever you want
echo "<script>alert('Profile updated successfully!'); window.location='patient_dashboard.php';</script>";
?>
