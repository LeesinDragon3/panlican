<?php
require_once 'db_connect.php';
session_start();

if (!isset($_SESSION['user']) || ($_SESSION['user']['role'] ?? '') !== 'doctor') {
    echo "<p class='text-danger'>Access denied.</p>";
    exit;
}

$user = $_SESSION['user'];
$fullname = htmlspecialchars($user['fullname'] ?? '');
$email = htmlspecialchars($user['email'] ?? '');
$currentPic = htmlspecialchars(basename($user['profile_pic'] ?? 'doctor1.png'));
?>

<h5 class="mb-3">👩‍⚕️ My Profile</h5>

<form id="profileForm" method="POST" action="update_profile.php" class="p-3 border rounded bg-white">
  <div class="text-center mb-3">
    <img id="previewPic" src="avatars/<?= 'doctors/' . $currentPic ?>" alt="Profile Picture" style="width:100px; height:100px; border-radius:50%; object-fit:cover; border:3px solid #1a7c4a;">
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
    <label class="form-label">Choose a Doctor Avatar</label>
    <div class="d-flex flex-wrap gap-3">
      <?php for ($i=1;$i<=5;$i++): 
          $fn = "doctor{$i}.png";
          $checked = ($fn === $currentPic) ? 'checked' : '';
      ?>
        <label style="cursor:pointer;">
          <input type="radio" name="profile_pic" value="<?= $fn ?>" <?= $checked ?> hidden>
          <img src="avatars/doctors/<?= $fn ?>" onclick="selectAvatar('doctors/<?= $fn ?>','<?= $fn ?>')" style="width:70px; height:70px; border-radius:50%; border:3px solid <?= $checked ? '#1a7c4a' : '#ccc' ?>; object-fit:cover;">
        </label>
      <?php endfor; ?>
    </div>
  </div>

  <button class="btn btn-success mt-3">Save Changes</button>
</form>

<script>
function selectAvatar(relativePath, filename){
  document.getElementById('previewPic').src = 'avatars/' + relativePath;
  document.querySelectorAll('input[name="profile_pic"]').forEach(el => {
    const img = el.nextElementSibling;
    img.style.border = (el.value === filename) ? '3px solid #1a7c4a' : '3px solid #ccc';
    if(el.value === filename) el.checked = true;
  });
}
</script>
