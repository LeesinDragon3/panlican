<?php
require_once 'db_connect.php';
session_start();

// Restrict access
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'patient') {
    header('Location: login.php');
    exit();
}

$patient_id = $_SESSION['user']['id'];

// ✅ Use prepared statement for security
$sql = "SELECT * FROM notifications WHERE patient_id = ? ORDER BY created_at DESC";
$stmt = $conn->prepare($sql);

if ($stmt === false) {
    die("Prepare failed: " . $conn->error);
}

$stmt->bind_param("i", $patient_id);
$stmt->execute();
$result = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Notifications</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #1a8a6e 0%, #2ecc71 100%);
            min-height: 100vh;
            padding: 40px 20px;
        }
        
        .notification-container {
            max-width: 900px;
            margin: 0 auto;
        }
        
        .header-section {
            background: white;
            padding: 30px;
            border-radius: 20px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
            margin-bottom: 30px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        
        .header-title {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .header-icon {
            background: linear-gradient(135deg, #1a8a6e 0%, #2ecc71 100%);
            color: white;
            width: 60px;
            height: 60px;
            border-radius: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.8rem;
        }
        
        .header-title h1 {
            font-size: 2rem;
            color: #333;
            font-weight: 700;
        }
        
        .notification-count {
            background: linear-gradient(135deg, #1a8a6e 0%, #2ecc71 100%);
            color: white;
            padding: 10px 20px;
            border-radius: 25px;
            font-weight: 600;
            font-size: 1rem;
        }
        
        .notifications-list {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }
        
        .notification {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
            border-left: 5px solid #1a8a6e;
            transition: all 0.3s ease;
            animation: slideIn 0.5s ease;
        }
        
        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .notification:hover {
            transform: translateX(5px);
            box-shadow: 0 8px 30px rgba(26, 138, 110, 0.3);
        }
        
        .notification-header {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 15px;
        }
        
        .notification-icon {
            width: 50px;
            height: 50px;
            border-radius: 12px;
            background: linear-gradient(135deg, #1a8a6e 0%, #2ecc71 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.5rem;
            flex-shrink: 0;
        }
        
        .notification-content {
            flex: 1;
        }
        
        .notification-message {
            color: #333;
            font-size: 1.05rem;
            line-height: 1.6;
            margin: 0 0 10px 0;
        }
        
        .notification-time {
            display: flex;
            align-items: center;
            gap: 8px;
            color: #999;
            font-size: 0.9rem;
        }
        
        .notification-time i {
            color: #1a8a6e;
        }
        
        .empty-state {
            background: white;
            border-radius: 20px;
            padding: 60px 40px;
            text-align: center;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);
        }
        
        .empty-state-icon {
            font-size: 5rem;
            color: #ddd;
            margin-bottom: 20px;
        }
        
        .empty-state h2 {
            color: #999;
            font-size: 1.5rem;
            margin-bottom: 10px;
        }
        
        .empty-state p {
            color: #bbb;
            font-size: 1rem;
        }
        
        .error-state {
            background: #f8d7da;
            color: #721c24;
            padding: 20px;
            border-radius: 15px;
            border-left: 5px solid #dc3545;
        }
        
        .back-button {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            background: white;
            color: #1a8a6e;
            padding: 12px 25px;
            border-radius: 10px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            margin-top: 30px;
        }
        
        .back-button:hover {
            background: #1a8a6e;
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(26, 138, 110, 0.4);
        }
        
        @media (max-width: 768px) {
            body {
                padding: 20px 10px;
            }
            
            .header-section {
                flex-direction: column;
                gap: 20px;
                text-align: center;
            }
            
            .header-title {
                flex-direction: column;
            }
            
            .header-title h1 {
                font-size: 1.5rem;
            }
            
            .notification {
                padding: 20px;
            }
            
            .notification-header {
                flex-direction: column;
                text-align: center;
            }
        }
    </style>
</head>
<body>
    <div class="notification-container">
        <div class="header-section">
            <div class="header-title">
                <div class="header-icon">
                    <i class="bi bi-bell-fill"></i>
                </div>
                <h1>My Notifications</h1>
            </div>
            <?php if ($result && $result->num_rows > 0): ?>
                <div class="notification-count">
                    <i class="bi bi-envelope-fill"></i> <?= $result->num_rows ?> Notification<?= $result->num_rows !== 1 ? 's' : '' ?>
                </div>
            <?php endif; ?>
        </div>
        
        <div class="notifications-list">
            <?php
            if (!$result) {
                echo "<div class='error-state'>
                        <i class='bi bi-exclamation-triangle-fill'></i>
                        <strong>Database error:</strong> " . htmlspecialchars($conn->error) . "
                      </div>";
            } elseif ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    // Determine icon based on message content
                    $icon = 'bi-bell-fill';
                    $message = strtolower($row['message']);
                    
                    if (strpos($message, 'appointment') !== false) {
                        $icon = 'bi-calendar-check-fill';
                    } elseif (strpos($message, 'prescription') !== false) {
                        $icon = 'bi-prescription2';
                    } elseif (strpos($message, 'reminder') !== false) {
                        $icon = 'bi-alarm-fill';
                    } elseif (strpos($message, 'cancelled') !== false) {
                        $icon = 'bi-x-circle-fill';
                    } elseif (strpos($message, 'confirmed') !== false || strpos($message, 'approved') !== false) {
                        $icon = 'bi-check-circle-fill';
                    }
                    
                    echo "<div class='notification'>
                            <div class='notification-header'>
                                <div class='notification-icon'>
                                    <i class='bi {$icon}'></i>
                                </div>
                                <div class='notification-content'>
                                    <p class='notification-message'>" . htmlspecialchars($row['message']) . "</p>
                                    <div class='notification-time'>
                                        <i class='bi bi-clock'></i>
                                        <span>" . date("F j, Y • g:i A", strtotime($row['created_at'])) . "</span>
                                    </div>
                                </div>
                            </div>
                          </div>";
                }
            } else {
                echo "<div class='empty-state'>
                        <div class='empty-state-icon'>
                            <i class='bi bi-inbox'></i>
                        </div>
                        <h2>No Notifications Yet</h2>
                        <p>You're all caught up! New notifications will appear here.</p>
                      </div>";
            }
            ?>
        </div>
        
        <div style="text-align: center;">
            <a href="patient_dashboard.php" class="back-button">
                <i class="bi bi-arrow-left"></i>
                Back to Dashboard
            </a>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>