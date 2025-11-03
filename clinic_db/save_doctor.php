<?php
session_start();
include('db_connect.php');

// Check if user is logged in and is admin
if(!isset($_SESSION['login_id'])){
    header('location:login.php');
    exit;
}

if($_SERVER['REQUEST_METHOD'] == 'POST'){
    // Get form data and sanitize
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $password = mysqli_real_escape_string($conn, $_POST['password']);
    $specialization = mysqli_real_escape_string($conn, $_POST['specialization']);
    $contact = mysqli_real_escape_string($conn, $_POST['contact']);
    
    // Validate required fields
    if(empty($name) || empty($email) || empty($username) || empty($password) || empty($specialization) || empty($contact)){
        $_SESSION['error'] = "All fields are required!";
        header('location:add_doctor.php');
        exit;
    }
    
    // Check if email already exists
    $check_email = mysqli_query($conn, "SELECT * FROM doctors WHERE email = '$email'");
    if(mysqli_num_rows($check_email) > 0){
        $_SESSION['error'] = "Email already exists!";
        header('location:add_doctor.php');
        exit;
    }
    
    // Check if username already exists
    $check_username = mysqli_query($conn, "SELECT * FROM doctors WHERE username = '$username'");
    if(mysqli_num_rows($check_username) > 0){
        $_SESSION['error'] = "Username already exists!";
        header('location:add_doctor.php');
        exit;
    }
    
    // Hash the password for security
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    
    // Insert doctor into database
    $sql = "INSERT INTO doctors (name, email, username, password, specialization, contact, created_at, updated_at) 
            VALUES ('$name', '$email', '$username', '$hashed_password', '$specialization', '$contact', NOW(), NOW())";
    
    if(mysqli_query($conn, $sql)){
        $_SESSION['success'] = "Doctor added successfully!";
        header('location:manage_doctors.php');
        exit;
    } else {
        $_SESSION['error'] = "Error adding doctor: " . mysqli_error($conn);
        header('location:add_doctor.php');
        exit;
    }
} else {
    header('location:add_doctor.php');
    exit;
}
?>