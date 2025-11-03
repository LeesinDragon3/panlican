<?php
session_start();
require_once "db_connect.php";

// 🧠 Signup logic
if (isset($_POST['action']) && $_POST['action'] === 'signup') {
    $fullname = $_POST['fullname'];
    $email = $_POST['email'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $role = $_POST['role'] ?? 'patient';

    $check = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $check->bind_param("s", $email);
    $check->execute();
    $check->store_result();

    if ($check->num_rows > 0) {
        echo "<script>alert('Email already registered!'); window.location='auth.php';</script>";
        exit();
    }

    $stmt = $conn->prepare("INSERT INTO users (fullname, email, password, role) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssss", $fullname, $email, $password, $role);

    if ($stmt->execute()) {
        echo "<script>alert('Signup successful! You can now log in.'); window.location='auth.php';</script>";
    } else {
        echo "<script>alert('Signup failed. Please try again.');</script>";
    }

    $stmt->close();
}

// 🧠 Login logic
if (isset($_POST['action']) && $_POST['action'] === 'login') {
    $email = $_POST['email'];
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($user = $result->fetch_assoc()) {
        if (password_verify($password, $user['password'])) {
            $_SESSION['user'] = $user;
            echo "<script>alert('Login successful!'); window.location='dashboard.php';</script>";
        } else {
            echo "<script>alert('Incorrect password.'); window.location='auth.php';</script>";
        }
    } else {
        echo "<script>alert('Email not found.'); window.location='auth.php';</script>";
    }
}
?>
