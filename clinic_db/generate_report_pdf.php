<?php
require_once 'db_connect.php';
require_once 'vendor/autoload.php'; // if you use Composer for PDF library
session_start();

use Dompdf\Dompdf;

// ✅ Only for logged-in doctors
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'doctor') {
    die('Unauthorized access.');
}

$doctorName = htmlspecialchars($_SESSION['user']['fullname']);
$today = date('F j, Y');

// ✅ Create PDF
$dompdf = new Dompdf();
$html = '
<html>
<head>
<style>
    body {
        font-family: Arial, sans-serif;
        font-size: 12px;
    }
    .header {
        display: flex;
        align-items: center;
        border-bottom: 2px solid #4CAF50;
        padding-bottom: 8px;
        margin-bottom: 20px;
    }
    .header img {
        height: 50px;
        width: 50px;
        border-radius: 12px;
        object-fit: cover;
        margin-right: 10px;
    }
    .header .title {
        font-size: 22px;
        font-weight: bold;
        color: #4CAF50;
    }
    .doctor {
        text-align: right;
        font-size: 12px;
        color: #555;
    }
</style>
</head>
<body>

<!-- ✅ Header Section -->
<div class="header">
    <img src="c:\xampp23\htdocs\public_eclinic\ChatGPT Image Nov 1, 2025, 06_56_32 PM.png" alt="EClinic Logo">
    <div>
        <div class="title">EClinic</div>
        <div class="doctor">Dr. ' . $doctorName . '</div>
    </div>
</div>

<p><strong>Date:</strong> ' . $today . '</p>

<!-- Example Table -->
<table width="100%" border="1" cellspacing="0" cellpadding="5">
<thead>
<tr style="background-color:#d9f9e8;">
    <th>Patient</th>
    <th>Medicine</th>
    <th>Dosage</th>
    <th>Instructions</th>
</tr>
</thead>
<tbody>
<tr>
    <td>Jane Doe</td>
    <td>Paracetamol</td>
    <td>500mg</td>
    <td>Take twice daily after meals</td>
</tr>
</tbody>
</table>

</body>
</html>
';

$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();
$dompdf->stream('EClinic_Report.pdf', ['Attachment' => false]);
?>
