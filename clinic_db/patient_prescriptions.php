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

// ✅ Query: join with users to get doctor name
$sql = "SELECT p.*, u.fullname AS doctor_name 
        FROM prescriptions p
        LEFT JOIN users u ON p.doctor_id = u.id
        WHERE p.patient_id = ?";

if (!empty($search)) {
    $search = "%{$search}%";
    $sql .= " AND (u.fullname LIKE ? OR p.medication LIKE ? OR p.created_at LIKE ?)";
}

$sql .= " ORDER BY p.created_at DESC";

$stmt = $conn->prepare($sql);

if ($stmt === false) {
    die("Prepare failed: " . $conn->error);
}

if (!empty($_POST['search'])) {
    $stmt->bind_param("isss", $patientId, $search, $search, $search);
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
    <title>My Prescriptions</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #1a8a6e 0%, #2ecc71 100%);
            min-height: 100vh;
            padding: 40px 20px;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .prescriptions-card {
            background: white;
            border-radius: 20px;
            padding: 30px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
            max-width: 1400px;
            margin: 0 auto;
        }
        
        .prescriptions-header {
            display: flex;
            align-items: center;
            margin-bottom: 25px;
            padding-bottom: 20px;
            border-bottom: 3px solid #f0f0f0;
        }
        
        .prescriptions-header i {
            font-size: 2.5rem;
            margin-right: 15px;
            background: linear-gradient(135deg, #1a8a6e 0%, #2ecc71 100%);
            color: white;
            padding: 15px;
            border-radius: 15px;
        }
        
        .prescriptions-header h2 {
            margin: 0;
            font-size: 2rem;
            font-weight: 700;
            background: linear-gradient(135deg, #1a8a6e 0%, #2ecc71 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        .rx-search-box {
            background: #e8f5f1;
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 25px;
        }
        
        .rx-search-input-group {
            display: flex;
            gap: 10px;
        }
        
        .rx-search-input-group input {
            flex: 1;
            padding: 12px 20px;
            border: 2px solid #d0e9e1;
            border-radius: 10px;
            font-size: 1rem;
            transition: all 0.3s ease;
        }
        
        .rx-search-input-group input:focus {
            outline: none;
            border-color: #1a8a6e;
            box-shadow: 0 0 0 3px rgba(26, 138, 110, 0.1);
        }
        
        .rx-search-btn {
            padding: 12px 30px;
            background: linear-gradient(135deg, #1a8a6e 0%, #2ecc71 100%);
            color: white;
            border: none;
            border-radius: 10px;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.2s ease;
        }
        
        .rx-search-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(26, 138, 110, 0.4);
        }
        
        .prescriptions-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 20px;
        }
        
        .prescription-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.08);
            transition: all 0.3s ease;
            border-left: 5px solid #1a8a6e;
        }
        
        .prescription-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 30px rgba(26, 138, 110, 0.3);
        }
        
        .prescription-header {
            display: flex;
            justify-content: space-between;
            align-items: start;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #f0f0f0;
        }
        
        .medication-name {
            font-size: 1.3rem;
            font-weight: 700;
            color: #1a8a6e;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .prescription-date {
            background: #e8f5f1;
            padding: 6px 12px;
            border-radius: 8px;
            font-size: 0.85rem;
            color: #1a8a6e;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .prescription-info {
            margin-bottom: 15px;
        }
        
        .info-label {
            font-size: 0.85rem;
            color: #999;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 5px;
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .info-value {
            font-size: 1rem;
            color: #333;
            font-weight: 500;
        }
        
        .dosage-badge {
            display: inline-block;
            background: linear-gradient(135deg, #1a8a6e 0%, #2ecc71 100%);
            color: white;
            padding: 8px 16px;
            border-radius: 20px;
            font-weight: 600;
            font-size: 0.95rem;
        }
        
        .duration-badge {
            display: inline-block;
            background: #fff3cd;
            color: #856404;
            padding: 6px 14px;
            border-radius: 15px;
            font-weight: 600;
            font-size: 0.85rem;
        }
        
        .instructions-box {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 10px;
            margin-top: 10px;
            border-left: 3px solid #1a8a6e;
        }
        
        .instructions-box p {
            margin: 0;
            color: #555;
            line-height: 1.6;
        }
        
        .rx-empty-state {
            text-align: center;
            padding: 60px 20px;
        }
        
        .rx-empty-state i {
            font-size: 4rem;
            color: #ddd;
            margin-bottom: 20px;
        }
        
        .rx-empty-state p {
            color: #999;
            font-size: 1.1rem;
        }

        .back-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 20px;
            background: #1a8a6e;
            color: white;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 600;
            transition: all 0.3s ease;
            margin-top: 20px;
        }

        .back-btn:hover {
            background: #156f5a;
            transform: translateY(-2px);
            color: white;
        }
        
        @media (max-width: 768px) {
            .prescriptions-grid {
                grid-template-columns: 1fr;
            }

            .rx-search-input-group {
                flex-direction: column;
            }

            .rx-search-btn {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <div class="prescriptions-card">
        <div class="prescriptions-header">
            <i class="bi bi-capsule"></i>
            <h2>My Prescriptions</h2>
        </div>

        <div class="rx-search-box">
            <form method="POST" action="patient_prescriptions.php">
                <div class="rx-search-input-group">
                    <input 
                        type="text" 
                        name="search" 
                        value="<?= htmlspecialchars($_POST['search'] ?? '') ?>" 
                        placeholder="🔍 Search by doctor, medication, or date..."
                    >
                    <button type="submit" class="rx-search-btn">
                        <i class="bi bi-search"></i> Search
                    </button>
                </div>
            </form>
        </div>

        <?php if ($result && $result->num_rows > 0): ?>
            <div class="prescriptions-grid">
                <?php while ($row = $result->fetch_assoc()): ?>
                    <div class="prescription-card">
                        <div class="prescription-header">
                            <h3 class="medication-name">
                                <i class="bi bi-prescription2"></i>
                                <?= htmlspecialchars($row['medication']) ?>
                            </h3>
                            <span class="prescription-date">
                                <i class="bi bi-calendar3"></i>
                                <?= date('M d, Y', strtotime($row['created_at'])) ?>
                            </span>
                        </div>

                        <div class="prescription-info">
                            <div class="info-label">
                                <i class="bi bi-person-badge"></i> Doctor
                            </div>
                            <div class="info-value">
                                <?= htmlspecialchars($row['doctor_name'] ?? 'Unknown') ?>
                            </div>
                        </div>

                        <div class="prescription-info">
                            <div class="info-label">
                                <i class="bi bi-droplet"></i> Dosage
                            </div>
                            <div>
                                <span class="dosage-badge">
                                    <?= htmlspecialchars($row['dosage']) ?>
                                </span>
                            </div>
                        </div>

                        <div class="prescription-info">
                            <div class="info-label">
                                <i class="bi bi-clock-history"></i> Duration
                            </div>
                            <div>
                                <span class="duration-badge">
                                    <?= htmlspecialchars($row['duration']) ?>
                                </span>
                            </div>
                        </div>

                        <?php if (!empty($row['instructions'])): ?>
                            <div class="prescription-info">
                                <div class="info-label">
                                    <i class="bi bi-info-circle"></i> Instructions
                                </div>
                                <div class="instructions-box">
                                    <p><?= htmlspecialchars($row['instructions']) ?></p>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <div class="rx-empty-state">
                <i class="bi bi-prescription2"></i>
                <p>No prescriptions found.</p>
            </div>
        <?php endif; ?>

        <a href="patient_dashboard.php" class="back-btn">
            <i class="bi bi-arrow-left"></i> Back to Dashboard
        </a>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>