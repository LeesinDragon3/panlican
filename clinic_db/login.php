<?php
session_start();
require_once "db_connect.php";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $username = $_POST['username']; // used as email in DB
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($user = $result->fetch_assoc()) {
        if (password_verify($password, $user['password'])) {
            $_SESSION['user'] = $user;

            // ✅ Redirect based on user role
            switch ($user['role']) {
                case 'admin':
                    header("Location: admin_dashboard.php");
                    break;
                case 'doctor':
                    header("Location: doctor_dashboard.php");
                    break;
                case 'patient':
                default:
                    header("Location: patient_dashboard.php");
                    break;
            }
            exit();
        } else {
            echo "<script>alert('Incorrect password!'); window.location='auth.html';</script>";
        }
    } else {
        echo "<script>alert('Username not found!'); window.location='auth.html';</script>";
    }
}
?>
