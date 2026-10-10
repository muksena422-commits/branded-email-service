<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../config/db.php';
requireLogin();

$id = (int)($_GET['id'] ?? 0);
$college = [
    'name' => '', 'brand_color' => '#2563eb', 'sender_name' => '',
    'sender_email' => '', 'contact_details' => '', 'logo_path' => null,
];

if ($id) {
    $stmt = $pdo->prepare("SELECT * FROM colleges WHERE id = ?");
    $stmt->execute([$id]);
    $found = $stmt->fetch();
    if (!$found) {
        header('Location: colleges.php');
        exit;
    }
    $college = $found;
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();

    $college['name']            = trim($_POST['name'] ?? '');
    $college['brand_color']     = trim($_POST['brand_color'] ?? '');
    $college['sender_name']     = trim($_POST['sender_name'] ?? '');
    $college['sender_email']    = trim($_POST['sender_email'] ?? '');
    $college['contact_details'] = trim($_POST['contact_details'] ?? '');

    if ($college['name'] === '') $errors[] = 'College name is required.';
    if (!preg_match('/^#[0-9a-fA-F]{6}$/', $college['brand_color'])) {
        $errors[] = 'Please choose a valid colour.';
        $college['brand_color'] = '#2563eb';
    }
    if ($college['sender_name'] === '') $errors[] = 'Sender name is required.';
    if (!filter_var($college['sender_email'], FILTER_VALIDATE_EMAIL)) $errors[] = 'Enter a valid sender email.';

    $newLogo = null;
    if (!empty($_FILES['logo']['name'])) {
        if ($_FILES['logo']['error'] !== UPLOAD_ERR_OK) {
            $errors[] = 'Logo upload failed. Please try again.';
        } elseif ($_FILES['logo']['size'] > 2 * 1024 * 1024) {
            $errors[] = 'Logo must be smaller than 2 MB.';
        } else {
            $finfo = new finfo(FILEINFO_MIME_TYPE);
            $mime = $finfo->file($_FILES['logo']['tmp_name']);
            $allowed = ['image/png' => 'png', 'image/jpeg' => 'jpg', 'image/webp' => 'webp'];
            if (!isset($allowed[$mime])) {
                $errors[] = 'Logo must be a PNG, JPG or WEBP image.';
            } else {
                $dir = __DIR__ . '/../assets/uploads/logos/';
                if (!is_dir($dir)) mkdir($dir, 0777, true);
                $fileName = 'logo_' . bin2hex(random_bytes(8)) . '.' . $allowed[$mime];
                if (move_uploaded_file($_FILES['logo']['tmp_name'], $dir . $fileName)) {
                    $newLogo = 'assets/uploads/logos/' . $fileName;
                } else {
                    $errors[] = 'Could not save the logo file.';
                }
            }
        }
    }

    if (!$errors) {
        $oldLogo = $college['logo_path'];
        $logoToSave = $newLogo ?? $oldLogo;

        if ($id) {
            $stmt = $pdo->prepare(
                "UPDATE colleges SET name=?, logo_path=?, brand_color=?, sender_name=?, sender_email=?, contact_details=? WHERE id=?"
            );
            $stmt->execute([$college['name'], $logoToSave, $college['brand_color'],
                $college['sender_name'], $college['sender_email'], $college['contact_details'], $id]);
        } else {
            $stmt = $pdo->prepare(
                "INSERT INTO colleges (name, logo_path, brand_color, sender_name, sender_email, contact_details) VALUES (?,?,?,?,?,?)"
            );
            $stmt->execute([$college['name'], $logoToSave, $college['brand_color'],
                $college['sender_name'], $college['sender_email'], $college['contact_details']]);
        }

        if ($newLogo && $oldLogo) {
            $oldFile = __DIR__ . '/../' . $oldLogo;
            if (is_file($oldFile)) unlink($oldFile);
        }

        setFlash($id ? 'College updated.' : 'College added.');
        header('Location: colleges.php');
        exit;
    }
}

$pageTitle = $id ? 'Edit College' : 'Add College';
$activePage = 'colleges';
require __DIR__ . '/../includes/header.php';
?>
<div class="page-head">
  <h1 class="page-title"><?= h($pageTitle) ?></h1>
  <a href="colleges.php" class="btn btn-ghost"><i class="fa-solid fa-arrow-left"></i> Back</a>
</div>

<?php if ($errors): ?>
  <div class="alert alert-error">
    <?php foreach ($errors as $e): ?><div><?= h($e) ?></div><?php endforeach; ?>
  </div>
<?php endif; ?>

<div class="form-layout">
  <div class="card">
    <form method="POST" enctype="multipart/form-data">
      <?= csrfField() ?>
      <div class="form-group">
        <label for="name">College name</label>
        <input type="text" id="name" name="name" value="<?= h($college['name']) ?>" required>
      </div>
      <div class="form-group">
        <label for="logo">Logo (PNG, JPG or WEBP, max 2 MB)</label>
        <input type="file" id="logo" name="logo" accept="image/png,image/jpeg,image/webp">
      </div>
      <div class="form-group">
        <label for="brand_color">Brand colour</label>
        <input type="color" id="brand_color" name="brand_color" value="<?= h($college['brand_color']) ?>">
      </div>
      <div class="form-group">
        <label for="sender_name">Sender name</label>
        <input type="text" id="sender_name" name="sender_name" value="<?= h($college['sender_name']) ?>" placeholder="Northfield Placements" required>
      </div>
      <div class="form-group">
        <label for="sender_email">Sender email</label>
        <input type="email" id="sender_email" name="sender_email" value="<?= h($college['sender_email']) ?>" placeholder="placements@northfield.edu" required>
      </div>
      <div class="form-group">
        <label for="contact_details">Contact details (shown in the email footer)</label>
        <input type="text" id="contact_details" name="contact_details" value="<?= h($college['contact_details']) ?>" placeholder="placements@northfield.edu">
      </div>
      <button class="btn" type="submit"><i class="fa-solid fa-floppy-disk"></i> Save College</button>
    </form>
  </div>

  <div class="card">
    <h3 style="margin-top:0">Live email preview</h3>
    <div class="email-preview">
      <div class="ep-header" id="pvHeader">
        <img id="pvLogo" alt="" style="<?= $college['logo_path'] ? '' : 'display:none' ?>"
             src="<?= $college['logo_path'] ? '../' . h($college['logo_path']) : '' ?>">
        <span id="pvName"></span>
      </div>
      <div class="ep-body">
        Hi Rahul,<br>
        Java Test result: 42 / 50<br>
        &ndash; <span id="pvSender"></span>
      </div>
      <div class="ep-footer" id="pvContact"></div>
    </div>
  </div>
</div>

<script>
const $ = id => document.getElementById(id);
function updatePreview() {
  $('pvHeader').style.background = $('brand_color').value;
  $('pvName').textContent    = $('name').value || 'College name';
  $('pvSender').textContent  = $('sender_name').value || 'Sender name';
  $('pvContact').textContent = $('contact_details').value || $('sender_email').value || 'contact@college.edu';
}
['name','brand_color','sender_name','sender_email','contact_details'].forEach(id =>
  $(id).addEventListener('input', updatePreview));
$('logo').addEventListener('change', e => {
  const file = e.target.files[0];
  if (file) {
    $('pvLogo').src = URL.createObjectURL(file);
    $('pvLogo').style.display = 'block';
  }
});
updatePreview();
</script>
<?php require __DIR__ . '/../includes/footer.php'; ?>