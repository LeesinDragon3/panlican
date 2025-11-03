<?php
session_start();
include('db_connect.php');

// Check if user is logged in and is admin
if(!isset($_SESSION['login_id'])){
    header('location:login.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add New Doctor - MED+ Clinic</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: Arial, sans-serif;
            background-color: #f5f5f5;
            padding: 20px;
        }
        
        .container {
            max-width: 600px;
            margin: 0 auto;
            background-color: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        h2 {
            color: #2d8659;
            margin-bottom: 25px;
            text-align: center;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        label {
            display: block;
            margin-bottom: 5px;
            color: #333;
            font-weight: 500;
        }
        
        input[type="text"],
        input[type="email"],
        input[type="password"],
        input[type="tel"],
        select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
        }
        
        input:focus,
        select:focus {
            outline: none;
            border-color: #2d8659;
        }
        
        .btn-container {
            display: flex;
            gap: 10px;
            margin-top: 25px;
        }
        
        .btn {
            flex: 1;
            padding: 12px;
            border: none;
            border-radius: 4px;
            font-size: 16px;
            cursor: pointer;
            transition: background-color 0.3s;
        }
        
        .btn-primary {
            background-color: #2d8659;
            color: white;
        }
        
        .btn-primary:hover {
            background-color: #246b47;
        }
        
        .btn-secondary {
            background-color: #6c757d;
            color: white;
        }
        
        .btn-secondary:hover {
            background-color: #5a6268;
        }
        
        .alert {
            padding: 12px;
            border-radius: 4px;
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
        
        .required {
            color: red;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Add New Doctor</h2>
        
        <form id="addDoctorForm" method="POST" action="save_doctor.php">
            <div class="form-group">
                <label for="name">Full Name <span class="required">*</span></label>
                <input type="text" id="name" name="name" required>
            </div>
            
            <div class="form-group">
                <label for="email">Email <span class="required">*</span></label>
                <input type="email" id="email" name="email" required>
            </div>
            
            <div class="form-group">
                <label for="username">Username <span class="required">*</span></label>
                <input type="text" id="username" name="username" required>
            </div>
            
            <div class="form-group">
                <label for="password">Password <span class="required">*</span></label>
                <input type="password" id="password" name="password" required>
            </div>
            
            <div class="form-group">
                <label for="specialization">Specialization <span class="required">*</span></label>
                <select id="specialization" name="specialization" required>
                    <option value="">Select Specialization</option>
                    <option value="General Practitioner">General Practitioner</option>
                    <option value="Pediatrician">Pediatrician</option>
                    <option value="Cardiologist">Cardiologist</option>
                    <option value="Dermatologist">Dermatologist</option>
                    <option value="Neurologist">Neurologist</option>
                    <option value="Orthopedic">Orthopedic</option>
                    <option value="Psychiatrist">Psychiatrist</option>
                    <option value="Surgeon">Surgeon</option>
                    <option value="Gynecologist">Gynecologist</option>
                    <option value="Ophthalmologist">Ophthalmologist</option>
                    <option value="ENT Specialist">ENT Specialist</option>
                    <option value="Other">Other</option>
                </select>
            </div>
            
            <div class="form-group">
                <label for="contact">Contact Number <span class="required">*</span></label>
                <input type="tel" id="contact" name="contact" placeholder="09XXXXXXXXX" required>
            </div>
            
            <div class="btn-container">
                <button type="submit" class="btn btn-primary">Add Doctor</button>
                <button type="button" class="btn btn-secondary" onclick="window.location.href='manage_doctors.php'">Cancel</button>
            </div>
        </form>
    </div>
    
    <script>
        document.getElementById('addDoctorForm').addEventListener('submit', function(e) {
            const contact = document.getElementById('contact').value;
            
            // Validate Philippine phone number format
            if (!/^09\d{9}$/.test(contact)) {
                e.preventDefault();
                alert('Please enter a valid Philippine mobile number (e.g., 09171234567)');
                return false;
            }
        });
    </script>
</body>
</html>