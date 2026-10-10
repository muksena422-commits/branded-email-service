<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../config/db.php';
requireLogin();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    verifyCsrf();
    $id = (int)$_POST['delete_id'];

    $stmt = $pdo->prepare("SELECT logo_path FROM colleges WHERE id = ?");
    $stmt->execute([$id]);
    $college = $stmt->fetch();

    if ($college) {
        $pdo->prepare("DELETE FROM colleges WHERE id = ?")->execute([$id]);
        if ($college['logo_path']) {
            $file = __DIR__ . '/../' . $college['logo_path'];
            if (is_file($file)) unlink($file);
        }
        setFlash('College deleted. Its students were kept.');
    }
    header('Location: colleges.php');
    exit;
}

$colleges = $pdo->query(
    "SELECT c.*, (SELECT COUNT(*) FROM students s WHERE s.college_id = c.id) AS student_count
     FROM colleges c ORDER BY c.id DESC"
)->fetchAll();

$pageTitle = 'Colleges';
$activePage = 'colleges';
require __DIR__ . '/../includes/header.php';
?>
<div class="page-head">
  <h1 class="page-title">Colleges</h1>
  <a href="college_form.php" class="btn btn-primary"><i class="fa-solid fa-plus"></i> Add College</a>
</div>

<div class="card">
  <div class="table-wrap">
    <table>
      <thead>
        <tr><th>Logo</th><th>Name</th><th>Colour</th><th>Sender</th><th>Students</th><th>Actions</th></tr>
      </thead>
      <tbody>
      <?php if (!$colleges): ?>
        <tr><td colspan="6">No colleges yet. Click "Add College" to create one.</td></tr>
      <?php endif; ?>
      <?php foreach ($colleges as $c): ?>
        <tr>
          <td>
            <?php if ($c['logo_path']): ?>
             <img src="../<?= h($c['logo_path']) ?>" alt="" style="width:40px;height:40px;object-fit:contain;border-radius:8px;border:1px solid #e5e7eb;background:#fff">
            <?php else: ?>
              <span class="badge badge-muted">None</span>
            <?php endif; ?>
          </td>
          <td><strong><?= h($c['name']) ?></strong></td>
          <td><span class="swatch" style="background:<?= h($c['brand_color']) ?>"></span><?= h($c['brand_color']) ?></td>
          <td><?= h($c['sender_name']) ?><br><small><?= h($c['sender_email']) ?></small></td>
          <td><?= (int)$c['student_count'] ?></td>
          <td>
            <div class="actions">
              <a class="btn btn-ghost btn-sm" href="college_form.php?id=<?= (int)$c['id'] ?>"><i class="fa-solid fa-pen"></i> Edit</a>
              <form method="POST" onsubmit="return confirm('Delete this college? Its students will be kept.');">
                <?= csrfField() ?>
                <input type="hidden" name="delete_id" value="<?= (int)$c['id'] ?>">
                <button class="btn btn-danger btn-sm" type="submit"><i class="fa-solid fa-trash"></i> Delete</button>
              </form>
            </div>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>