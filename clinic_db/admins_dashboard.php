<?php
session_start();
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header('Location: admins_dashboard.php');
    exit();
}
require_once 'db_connect.php';
$username = $_SESSION['user']['username'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Admin Dashboard - E-Clinic</title>
<link rel="stylesheet" href="dashboard.css">
</head>
<body>
<aside class="sidebar">
<h2 class="logo">E-Clinic</h2>
<ul>
    <li class="active"><a href="admins_dashboard.php">🏠 Dashboard</a></li>
    <li><a href="manage_doctors.php">👨‍⚕️ Doctors</a></li>
    <li><a href="manage_patients.php">🧍 Patients</a></li>
    <li><a href="reports.php">📊 Reports</a></li>
    <li><a href="logout.php" onclick="return confirm('Are you sure you want to logout?')">🚪 Logout</a></li>
</ul>
</aside>

<main class="main">
<header class="topbar">
<h1>Admin Dashboard</h1>
<p>Welcome back, <?php echo htmlspecialchars($username); ?> 👋</p>
</header>
</main>
</body>
</html>
