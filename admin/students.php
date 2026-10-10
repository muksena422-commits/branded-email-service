<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../config/db.php';
requireLogin();

$errors = [];
$old = ['name' => '', 'email' => '', 'college_id' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();

    if (isset($_POST['delete_id'])) {
        $pdo->prepare("DELETE FROM students WHERE id = ?")->execute([(int)$_POST['delete_id']]);
        setFlash('Student deleted.');
        header('Location: students.php');
        exit;
    }

    $old['name']       = trim($_POST['name'] ?? '');
    $old['email']      = trim($_POST['email'] ?? '');
    $old['college_id'] = (int)($_POST['college_id'] ?? 0);

    if ($old['name'] === '') $errors[] = 'Student name is required.';
    if (!filter_var($old['email'], FILTER_VALIDATE_EMAIL)) $errors[] = 'Enter a valid student email.';

    if ($old['college_id']) {
        $check = $pdo->prepare("SELECT id FROM colleges WHERE id = ?");
        $check->execute([$old['college_id']]);
        if (!$check->fetch()) $errors[] = 'Please choose a valid college.';
    } else {
        $errors[] = 'Please choose a college.';
    }

    if (!$errors) {
        $stmt = $pdo->prepare("INSERT INTO students (name, email, college_id) VALUES (?, ?, ?)");
        $stmt->execute([$old['name'], $old['email'], $old['college_id']]);
        setFlash('Student added.');
        header('Location: students.php');
        exit;
    }
}

$colleges = $pdo->query("SELECT id, name FROM colleges ORDER BY name")->fetchAll();
$students = $pdo->query(
    "SELECT s.id, s.name, s.email, c.name AS college_name
     FROM students s LEFT JOIN colleges c ON c.id = s.college_id
     ORDER BY s.id DESC"
)->fetchAll();

$pageTitle = 'Students';
$activePage = 'students';
require __DIR__ . '/../includes/header.php';
?>
<div class="page-head">
  <h1 class="page-title">Students</h1>
</div>

<?php if ($errors): ?>
  <div class="alert alert-error">
    <?php foreach ($errors as $e): ?><div><?= h($e) ?></div><?php endforeach; ?>
  </div>
<?php endif; ?>

<div class="form-layout">
  <div class="card">
    <h3 style="margin-top:0">Add student</h3>
    <form method="POST">
      <?= csrfField() ?>
      <div class="form-group">
        <label for="name">Student name</label>
        <input type="text" id="name" name="name" value="<?= h($old['name']) ?>" required>
      </div>
      <div class="form-group">
        <label for="email">Student email</label>
        <input type="email" id="email" name="email" value="<?= h($old['email']) ?>" required>
      </div>
      <div class="form-group">
        <label for="college_id">College</label>
        <select id="college_id" name="college_id" required>
          <option value="">Choose a college</option>
          <?php foreach ($colleges as $c): ?>
            <option value="<?= (int)$c['id'] ?>" <?= $old['college_id'] == $c['id'] ? 'selected' : '' ?>>
              <?= h($c['name']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
      <button class="btn" type="submit"><i class="fa-solid fa-plus"></i> Add Student</button>
    </form>
  </div>

  <div class="card">
    <h3 style="margin-top:0">All students</h3>
    <div class="table-wrap">
      <table>
        <thead><tr><th>Name</th><th>Email</th><th>College</th><th></th></tr></thead>
        <tbody>
        <?php if (!$students): ?>
          <tr><td colspan="4">No students yet.</td></tr>
        <?php endif; ?>
        <?php foreach ($students as $s): ?>
          <tr>
            <td><strong><?= h($s['name']) ?></strong></td>
            <td><?= h($s['email']) ?></td>
            <td>
              <?php if ($s['college_name']): ?>
                <?= h($s['college_name']) ?>
              <?php else: ?>
                <span class="badge badge-muted">No college</span>
              <?php endif; ?>
            </td>
            <td>
              <form method="POST" onsubmit="return confirm('Delete this student?');">
                <?= csrfField() ?>
                <input type="hidden" name="delete_id" value="<?= (int)$s['id'] ?>">
                <button class="btn btn-danger btn-sm" type="submit"><i class="fa-solid fa-trash"></i></button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>