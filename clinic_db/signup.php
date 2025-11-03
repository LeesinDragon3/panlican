<?php
require_once 'db_connect.php';

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $fullname = trim($_POST['fullname']);
    $email = trim($_POST['username']); // Form sends 'username' which is actually email
    $phone = trim($_POST['contact']); // Form sends 'contact' but DB has 'phone'
    $password = trim($_POST['password']);
    $confirm_password = trim($_POST['confirm_password']);
    $role = trim($_POST['role']);

    // Validation
    if (empty($fullname) || empty($email) || empty($phone) || empty($password) || empty($confirm_password) || empty($role)) {
        echo "<script>alert('Please fill in all required fields!'); window.history.back();</script>";
        exit;
    }

    if ($password !== $confirm_password) {
        echo "<script>alert('Passwords do not match!'); window.history.back();</script>";
        exit;
    }

    if (strlen($password) < 6) {
        echo "<script>alert('Password must be at least 6 characters long!'); window.history.back();</script>";
        exit;
    }

    // Validate email format
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo "<script>alert('Please enter a valid email address!'); window.history.back();</script>";
        exit;
    }

    // Check if email already exists
    $check = $conn->prepare("SELECT * FROM users WHERE email = ? LIMIT 1");
    if (!$check) {
        die("Prepare failed: " . $conn->error);
    }
    $check->bind_param("s", $email);
    $check->execute();
    $res = $check->get_result();

    if ($res->num_rows > 0) {
        echo "<script>alert('Email already exists!'); window.history.back();</script>";
        exit;
    }
    $check->close();

    // Hash password
    $hashed = password_hash($password, PASSWORD_DEFAULT);

    // Handle different roles
    if ($role === 'doctor') {
        $doctor_id = trim($_POST['doctor_id'] ?? '');
        $specialty = trim($_POST['specialty'] ?? '');

        if (empty($doctor_id) || empty($specialty)) {
            echo "<script>alert('Doctor License ID and Specialization are required for doctors!'); window.history.back();</script>";
            exit;
        }

        // Insert doctor with specialty (your DB has specialty column)
        $stmt = $conn->prepare("INSERT INTO users (fullname, email, phone, password, role, specialty) VALUES (?, ?, ?, ?, ?, ?)");
        if (!$stmt) {
            die("Prepare failed: " . $conn->error);
        }
        $stmt->bind_param("ssssss", $fullname, $email, $phone, $hashed, $role, $specialty);

    } elseif ($role === 'admin') {
        $admin_code = trim($_POST['admin_code'] ?? '');
        $admin_id = trim($_POST['admin_id'] ?? '');

        if (empty($admin_code) || empty($admin_id)) {
            echo "<script>alert('Admin Access Code and Admin ID are required for admin registration!'); window.history.back();</script>";
            exit;
        }

        // Verify admin code (CHANGE THIS SECRET CODE!)
        $valid_admin_code = "ADMIN2024"; // ⚠️ Change this to your secret admin code
        if ($admin_code !== $valid_admin_code) {
            echo "<script>alert('Invalid Admin Access Code!'); window.history.back();</script>";
            exit;
        }

        // Insert admin
        $stmt = $conn->prepare("INSERT INTO users (fullname, email, phone, password, role) VALUES (?, ?, ?, ?, ?)");
        if (!$stmt) {
            die("Prepare failed: " . $conn->error);
        }
        $stmt->bind_param("sssss", $fullname, $email, $phone, $hashed, $role);

    } else {
        // Insert regular patient
        $stmt = $conn->prepare("INSERT INTO users (fullname, email, phone, password, role) VALUES (?, ?, ?, ?, ?)");
        if (!$stmt) {
            die("Prepare failed: " . $conn->error);
        }
        $stmt->bind_param("sssss", $fullname, $email, $phone, $hashed, $role);
    }

    // Execute the insert
    if ($stmt->execute()) {
        echo "<script>alert('✅ Account created successfully! You can now login.'); window.location='auth.html';</script>";
    } else {
        echo "<script>alert('Signup failed: " . $stmt->error . "'); window.history.back();</script>";
    }

    $stmt->close();
}
$conn->close();