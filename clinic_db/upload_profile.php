<?php
session_start();
require_once 'db_connect.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'doctor') {
    echo json_encode(['success'=>false,'error'=>'Unauthorized']);
    exit;
}

$doctorId = intval($_SESSION['user']['id']);
$uploadDir = __DIR__ . '/uploads/';
$webDir = 'uploads/';

if (!is_dir($uploadDir)) {
    if (!mkdir($uploadDir, 0755, true)) {
        echo json_encode(['success'=>false,'error'=>'Unable to create uploads directory']);
        exit;
    }
}

if (empty($_FILES['profile_pic']) || $_FILES['profile_pic']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['success'=>false,'error'=>'No file uploaded']);
    exit;
}

$file = $_FILES['profile_pic'];
$maxSize = 4 * 1024 * 1024;
if ($file['size'] > $maxSize) {
    echo json_encode(['success'=>false,'error'=>'File too large (max 4MB)']);
    exit;
}

$allowed = ['jpg','jpeg','png','gif'];
$ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
if (!in_array($ext, $allowed)) {
    echo json_encode(['success'=>false,'error'=>'Invalid file type']);
    exit;
}

$fname = time() . '_' . bin2hex(random_bytes(6)) . '.' . $ext;
$target = $uploadDir . $fname;
if (!move_uploaded_file($file['tmp_name'], $target)) {
    echo json_encode(['success'=>false,'error'=>'Failed to move uploaded file']);
    exit;
}

$relativePath = $webDir . $fname;
$updated = false;

// Update doctors.profile_pic if exists
$check = $conn->query("SHOW COLUMNS FROM doctors LIKE 'profile_pic'");
if ($check && $check->num_rows > 0) {
    $stmt = $conn->prepare("UPDATE doctors SET profile_pic = ? WHERE id = ?");
    $stmt->bind_param('si', $relativePath, $doctorId);
    if ($stmt->execute()) $updated = true;
    $stmt->close();
} else {
    // Try user table
    $check2 = $conn->query("SHOW COLUMNS FROM `user` LIKE 'profile_pic'");
    if ($check2 && $check2->num_rows > 0) {
        $stmt = $conn->prepare("UPDATE `user` SET profile_pic = ? WHERE id = ?");
        $stmt->bind_param('si', $relativePath, $doctorId);
        if ($stmt->execute()) $updated = true;
        $stmt->close();
    }
}

$_SESSION['user']['profile_pic'] = $relativePath;
echo json_encode(['success'=>true,'newPath'=>$relativePath,'db_updated'=>$updated]);
exit;
