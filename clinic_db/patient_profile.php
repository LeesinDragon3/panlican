<?php
require_once 'db_connect.php';
session_start();

if (!isset($_SESSION['user'])) {
    echo "<p class='text-danger'>Login required.</p>";
    exit;
}

$user = $_SESSION['user'];
$fullname = htmlspecialchars($user['fullname'] ?? '');
$email = htmlspecialchars($user['email'] ?? '');
$currentPic = htmlspecialchars($user['profile_pic'] ?? 'default.png');

// ✅ Cartoon avatars (make sure these files exist in "avatars/" folder)
$avatars = [
  'avatar1.png',
  'avatar2.png',
  'avatar3.png',
  'avatar4.png',
  'avatar5.png'
];
?>

<h5 class="mb-3">👤 My Profile</h5>

<form id="profileForm" method="POST" action="update_profile.php" class="p-3 border rounded bg-white">
  <div class="text-center mb-3">
    <img id="previewPic" src="avatars/<?= $currentPic ?>" 
         alt="Profile Picture" 
         style="width:100px; height:100px; border-radius:50%; object-fit:cover; border:3px solid #1a7c4a;">
  </div>

  <div class="mb-3">
    <label class="form-label">Full Name</label>
    <input type="text" name="fullname" value="<?= $fullname ?>" class="form-control" required>
  </div>

  <div class="mb-3">
    <label class="form-label">Email</label>
    <input type="email" name="email" value="<?= $email ?>" class="form-control" required>
  </div>

  <div class="mb-3">
    <label class="form-label">Choose a Cartoon Avatar</label>
    <div class="d-flex flex-wrap gap-3">
      <?php foreach ($avatars as $avatar): ?>
        <label style="cursor:pointer;">
          <input type="radio" name="profile_pic" value="<?= $avatar ?>" 
                 <?= $avatar === $currentPic ? 'checked' : '' ?> hidden>
          <img src="avatars/<?= $avatar ?>" 
               onclick="selectAvatar('<?= $avatar ?>')" 
               style="width:70px; height:70px; border-radius:50%; border:3px solid <?= $avatar === $currentPic ? '#1a7c4a' : '#ccc' ?>; object-fit:cover;">
        </label>
      <?php endforeach; ?>
    </div>
  </div>

  <button class="btn btn-success mt-3">Save Changes</button>
</form>

<script>
// JS: highlight selected avatar and update preview
function selectAvatar(filename) {
  document.getElementById('previewPic').src = 'avatars/' + filename;
  document.querySelectorAll('input[name="profile_pic"]').forEach(el => {
    const img = el.nextElementSibling;
    img.style.border = (el.value === filename) ? '3px solid #1a7c4a' : '3px solid #ccc';
  });
}
</script>
