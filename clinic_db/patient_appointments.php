<?php
session_start();
require_once 'db_connect.php';

// ✅ Allow only patients
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'patient') {
    header('Location: login.php');
    exit();
}

$patientId = $_SESSION['user']['id'];
$search = $_POST['search'] ?? '';

// ✅ Query: join users table to get doctor name
$sql = "SELECT a.*, u.fullname AS doctor_name, u.specialty 
        FROM appointments a
        LEFT JOIN users u ON a.doctor_id = u.id
        WHERE a.patient_id = ?";

if (!empty($search)) {
    $search = "%{$search}%";
    $sql .= " AND (u.fullname LIKE ? OR u.specialty LIKE ? OR a.status LIKE ? OR a.date LIKE ?)";
}

$sql .= " ORDER BY a.date DESC, a.time DESC";

$stmt = $conn->prepare($sql);

if ($stmt === false) {
    die("Prepare failed: " . $conn->error);
}

if (!empty($_POST['search'])) {
    $stmt->bind_param("issss", $patientId, $search, $search, $search, $search);
} else {
    $stmt->bind_param("i", $patientId);
}
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Appointments</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 40px 20px;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .appointments-card {
            background: white;
            border-radius: 20px;
            padding: 30px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
            max-width: 1400px;
            margin: 0 auto;
        }
        
        .appointments-header {
            display: flex;
            align-items: center;
            margin-bottom: 25px;
            padding-bottom: 20px;
            border-bottom: 3px solid #f0f0f0;
        }
        
        .appointments-header i {
            font-size: 2.5rem;
            margin-right: 15px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 15px;
            border-radius: 15px;
        }
        
        .appointments-header h2 {
            margin: 0;
            font-size: 2rem;
            font-weight: 700;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        .search-box {
            background: #f8f9ff;
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 25px;
        }
        
        .search-input-group {
            display: flex;
            gap: 10px;
        }
        
        .search-input-group input {
            flex: 1;
            padding: 12px 20px;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            font-size: 1rem;
            transition: all 0.3s ease;
        }
        
        .search-input-group input:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        
        .search-btn {
            padding: 12px 30px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 10px;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.2s ease;
        }
        
        .search-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }
        
        .appointments-table-container {
            background: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.05);
        }
        
        .appointments-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .appointments-table thead {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        
        .appointments-table th {
            padding: 18px 15px;
            text-align: left;
            font-weight: 600;
            font-size: 0.95rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .appointments-table td {
            padding: 15px;
            border-bottom: 1px solid #f0f0f0;
            color: #333;
        }
        
        .appointments-table tbody tr {
            transition: all 0.3s ease;
        }
        
        .appointments-table tbody tr:hover {
            background: #f8f9ff;
            transform: scale(1.005);
        }
        
        .status-badge {
            display: inline-block;
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
            text-transform: capitalize;
        }
        
        .status-pending {
            background: #fff3cd;
            color: #856404;
        }
        
        .status-completed {
            background: #d4edda;
            color: #155724;
        }
        
        .status-cancelled {
            background: #f8d7da;
            color: #721c24;
        }
        
        .emergency-badge {
            background: linear-gradient(135deg, #ff6b6b 0%, #ee5a6f 100%);
            color: white;
            padding: 5px 12px;
            border-radius: 15px;
            font-size: 0.85rem;
            font-weight: 600;
        }
        
        .empty-state {
            text-align: center;
            padding: 60px 20px;
        }
        
        .empty-state i {
            font-size: 4rem;
            color: #ddd;
            margin-bottom: 20px;
        }
        
        .empty-state p {
            color: #999;
            font-size: 1.1rem;
        }

        .back-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 20px;
            background: #6c757d;
            color: white;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 600;
            transition: all 0.3s ease;
            margin-top: 20px;
        }

        .back-btn:hover {
            background: #5a6268;
            transform: translateY(-2px);
            color: white;
        }
        
        @media (max-width: 768px) {
            .appointments-table {
                font-size: 0.85rem;
            }
            
            .appointments-table th,
            .appointments-table td {
                padding: 10px 8px;
            }

            .search-input-group {
                flex-direction: column;
            }

            .search-btn {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <div class="appointments-card">
        <div class="appointments-header">
            <i class="bi bi-calendar-event"></i>
            <h2>My Appointments</h2>
        </div>

        <div class="search-box">
            <form method="POST" action="patient_appointments.php">
                <div class="search-input-group">
                    <input 
                        type="text" 
                        name="search" 
                        value="<?= htmlspecialchars($_POST['search'] ?? '') ?>" 
                        placeholder="🔍 Search by doctor, specialty, status, or date..."
                    >
                    <button type="submit" class="search-btn">
                        <i class="bi bi-search"></i> Search
                    </button>
                </div>
            </form>
        </div>

        <?php if ($result && $result->num_rows > 0): ?>
            <div class="appointments-table-container">
                <table class="appointments-table">
                    <thead>
                        <tr>
                            <th><i class="bi bi-calendar3"></i> Date</th>
                            <th><i class="bi bi-clock"></i> Time</th>
                            <th><i class="bi bi-person-badge"></i> Doctor</th>
                            <th><i class="bi bi-heart-pulse"></i> Specialty</th>
                            <th><i class="bi bi-info-circle"></i> Status</th>
                            <th><i class="bi bi-exclamation-triangle"></i> Emergency</th>
                            <th><i class="bi bi-chat-left-text"></i> Notes</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = $result->fetch_assoc()): ?>
                            <tr>
                                <td>
                                    <strong><?= date('M d, Y', strtotime($row['date'])) ?></strong>
                                </td>
                                <td><?= date('h:i A', strtotime($row['time'])) ?></td>
                                <td>
                                    <strong><?= htmlspecialchars($row['doctor_name'] ?? 'Unknown') ?></strong>
                                </td>
                                <td><?= htmlspecialchars($row['specialty'] ?? 'N/A') ?></td>
                                <td>
                                    <?php
                                        $status = strtolower($row['status']);
                                        $statusClass = match($status) {
                                            'pending' => 'status-pending',
                                            'completed' => 'status-completed',
                                            'cancelled' => 'status-cancelled',
                                            default => 'status-pending'
                                        };
                                    ?>
                                    <span class="status-badge <?= $statusClass ?>">
                                        <?= ucfirst($status) ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($row['emergency']): ?>
                                        <span class="emergency-badge">
                                            <i class="bi bi-exclamation-circle"></i> Yes
                                        </span>
                                    <?php else: ?>
                                        <span style="color: #999;">No</span>
                                    <?php endif; ?>
                                </td>
                                <td><?= htmlspecialchars($row['notes'] ?: '-') ?></td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <i class="bi bi-calendar-x"></i>
                <p>No appointments found.</p>
            </div>
        <?php endif; ?>

        <a href="patient_dashboard.php" class="back-btn">
            <i class="bi bi-arrow-left"></i> Back to Dashboard
        </a>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>