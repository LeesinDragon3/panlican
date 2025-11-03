<?php
// ajax_load.php
session_start();
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'patient') {
    http_response_code(403);
    echo "Access denied";
    exit();
}

$input = json_decode(file_get_contents('php://input'), true);
$page = $input['page'] ?? '';

$allowed = [
  'dashboard_content.php',
  'appointments_content.php',
  'prescriptions_content.php',
  'notifications_content.php',
  'profile_content.php'
];

if (!in_array($page, $allowed)) {
    http_response_code(400);
    echo "<div class='alert alert-danger'>Invalid page request.</div>";
    exit();
}

// include the requested page (it should echo its HTML fragment)
require $page;
