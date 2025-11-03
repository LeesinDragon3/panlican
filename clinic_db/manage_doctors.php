<?php
session_start();
include('db_connect.php');

// Check if user is logged in
if(!isset($_SESSION['login_id'])){
    header('location:login.php');
    exit;
}

// Handle delete request
if(isset($_GET['delete_id'])){
    $delete_id = $_GET['delete_id'];
    $delete_sql = "DELETE FROM doctors WHERE id = $delete_id";
    if(mysqli_query($conn, $delete_sql)){
        $_SESSION['success'] = "Doctor deleted successfully!";
    } else {
        $_SESSION['error'] = "Error deleting doctor!";
    }
    header('location:manage_doctors.php');
    exit;
}

// Fetch all doctors
$doctors = mysqli_query($conn, "SELECT * FROM doctors ORDER BY created_at DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Doctors - MED+ Clinic</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: Arial, sans-serif;
            background-color: #f5f5f5;
        }
        
        .container {
            display: flex;
            min-height: 100vh;
        }
        
        /* Sidebar */
        .sidebar {
            width: 250px;
            background-color: #e8f5e9;
            padding: 20px;
            position: fixed;
            height: 100vh;
            overflow-y: auto;
        }
        
        .sidebar-header {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .sidebar-header h2 {
            color: #2d8659;
        }
        
        .profile {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .profile img {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            margin-bottom: 10px;
        }
        
        .menu {
            list-style: none;
        }
        
        .menu li {
            margin-bottom: 5px;
        }
        
        .menu a {
            display: block;
            padding: 12px 15px;
            color: #333;
            text-decoration: none;
            border-radius: 5px;
            transition: background-color 0.3s;
        }
        
        .menu a:hover,
        .menu a.active {
            background-color: #2d8659;
            color: white;
        }
        
        /* Main Content */
        .main-content {
            margin-left: 250px;
            flex: 1;
            padding: 20px;
        }
        
        .header {
            background-color: white;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        
        .header h1 {
            color: #333;
            font-size: 24px;
        }
        
        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            font-size: 14px;
            transition: background-color 0.3s;
        }
        
        .btn-primary {
            background-color: #2d8659;
            color: white;
        }
        
        .btn-primary:hover {
            background-color: #246b47;
        }
        
        .btn-edit {
            background-color: #007bff;
            color: white;
            padding: 5px 10px;
            font-size: 12px;
        }
        
        .btn-delete {
            background-color: #dc3545;
            color: white;
            padding: 5px 10px;
            font-size: 12px;
        }
        
        .content-box {
            background-color: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        
        .search-box {
            margin-bottom: 20px;
        }
        
        .search-box input {
            width: 300px;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        
        th {
            background-color: #f8f9fa;
            font-weight: 600;
            color: #333;
        }
        
        tr:hover {
            background-color: #f8f9fa;
        }
        
        .actions {
            display: flex;
            gap: 5px;
        }
        
        .alert {
            padding: 12px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        
        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .alert-error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .no-data {
            text-align: center;
            padding: 40px;
            color: #666;
        }
        
        .logout-btn {
            background-color: #dc3545;
            color: white;
            padding: 8px 15px;
            border-radius: 5px;
            text-decoration: none;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Sidebar -->
        <div class="sidebar">
            <div class="sidebar-header">
                <h2>MED+ Clinic</h2>
            </div>
            
            <div class="profile">
                <img src="profile.png" alt="Profile" onerror="this.src='data:image/svg+xml,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 width=%22100%22 height=%22100%22%3E%3Ccircle cx=%2250%22 cy=%2250%22 r=%2240%22 fill=%22%23ccc%22/%3E%3C/svg%3E'">
                <p><strong><?php echo $_SESSION['login_name'] ?? 'Administrator'; ?></strong></p>
                <p>Administrator</p>
            </div>
            
            <ul class="menu">
                <li><a href="admin_dashboard.php">📊 Dashboard</a></li>
                <li><a href="manage_doctors.php" class="active">👨‍⚕️ Manage Doctors</a></li>
                <li><a href="manage_patients.php">👥 Manage Patients</a></li>
                <li><a href="appointments.php">📅 Appointments</a></li>
                <li><a href="prescriptions.php">💊 Prescriptions</a></li>
                <li><a href="reports.php">📈 Reports</a></li>
                <li><a href="settings.php">⚙️ Settings</a></li>
                <li><a href="profile.php">👤 Profile</a></li>
            </ul>
        </div>
        
        <!-- Main Content -->
        <div class="main-content">
            <div class="header">
                <h1>Manage Doctors</h1>
                <a href="logout.php" class="logout-btn">Logout</a>
            </div>
            
            <?php if(isset($_SESSION['success'])): ?>
                <div class="alert alert-success">
                    <?php 
                    echo $_SESSION['success']; 
                    unset($_SESSION['success']);
                    ?>
                </div>
            <?php endif; ?>
            
            <?php if(isset($_SESSION['error'])): ?>
                <div class="alert alert-error">
                    <?php 
                    echo $_SESSION['error']; 
                    unset($_SESSION['error']);
                    ?>
                </div>
            <?php endif; ?>
            
            <div class="content-box">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                    <div class="search-box">
                        <input type="text" id="searchInput" placeholder="Search by name, email, or specialization..." onkeyup="searchTable()">
                    </div>
                    <a href="add_doctor.php" class="btn btn-primary">➕ Add New Doctor</a>
                </div>
                
                <table id="doctorsTable">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Specialization</th>
                            <th>Contact</th>
                            <th>Created At</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(mysqli_num_rows($doctors) > 0): ?>
                            <?php while($row = mysqli_fetch_assoc($doctors)): ?>
                                <tr>
                                    <td><?php echo $row['id']; ?></td>
                                    <td><?php echo htmlspecialchars($row['name']); ?></td>
                                    <td><?php echo htmlspecialchars($row['email']); ?></td>
                                    <td><?php echo htmlspecialchars($row['specialization']); ?></td>
                                    <td><?php echo htmlspecialchars($row['contact']); ?></td>
                                    <td><?php echo date('M d, Y', strtotime($row['created_at'])); ?></td>
                                    <td>
                                        <div class="actions">
                                            <a href="edit_doctor.php?id=<?php echo $row['id']; ?>" class="btn btn-edit">✏️ Edit</a>
                                            <a href="manage_doctors.php?delete_id=<?php echo $row['id']; ?>" class="btn btn-delete" onclick="return confirm('Are you sure you want to delete this doctor?')">🗑️ Delete</a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" class="no-data">No doctors found. Click "Add New Doctor" to add one.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <script>
        function searchTable() {
            const input = document.getElementById('searchInput');
            const filter = input.value.toLowerCase();
            const table = document.getElementById('doctorsTable');
            const tr = table.getElementsByTagName('tr');
            
            for (let i = 1; i < tr.length; i++) {
                const td = tr[i].getElementsByTagName('td');
                let found = false;
                
                for (let j = 0; j < td.length - 1; j++) {
                    if (td[j]) {
                        const txtValue = td[j].textContent || td[j].innerText;
                        if (txtValue.toLowerCase().indexOf(filter) > -1) {
                            found = true;
                            break;
                        }
                    }
                }
                
                tr[i].style.display = found ? '' : 'none';
            }
        }
    </script>
</body>
</html>