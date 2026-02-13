<?php
require __DIR__ . '/../bootstrap.php';
$u = auth_require_login();
$pdo = db();

$action = $_GET['action'] ?? 'list';
function h($s){ return htmlspecialchars((string)$s); }

if ($_SERVER['REQUEST_METHOD'] === 'POST') csrf_verify();

if ($u['role'] === 'teacher' && $action === 'create' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $desc = trim($_POST['description'] ?? '');
    if ($title !== '') {
        $st = $pdo->prepare("INSERT INTO courses (title, description, status, teacher_id) VALUES (?, ?, 'draft', ?)");
        $st->execute([$title, $desc, (int)$u['id']]);
        audit((int)$u['id'], 'course_create', $title);
    }
    header("Location: courses.php"); exit;
}

if ($u['role'] === 'teacher' && $action === 'update' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int)($_POST['id'] ?? 0);
    $title = trim($_POST['title'] ?? '');
    $desc = trim($_POST['description'] ?? '');
    $status = $_POST['status'] ?? 'draft';

    $st = $pdo->prepare("SELECT id FROM courses WHERE id=? AND teacher_id=?");
    $st->execute([$id, (int)$u['id']]);
    if ($st->fetch() && $title !== '' && in_array($status, ['draft','published'], true)) {
        $st = $pdo->prepare("UPDATE courses SET title=?, description=?, status=? WHERE id=? AND teacher_id=?");
        $st->execute([$title, $desc, $status, $id, (int)$u['id']]);
        audit((int)$u['id'], 'course_update', (string)$id);
    }
    header("Location: courses.php"); exit;
}

if ($u['role'] === 'teacher' && $action === 'delete' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int)($_POST['id'] ?? 0);
    $st = $pdo->prepare("DELETE FROM courses WHERE id=? AND teacher_id=?");
    $st->execute([$id, (int)$u['id']]);
    audit((int)$u['id'], 'course_delete', (string)$id);
    header("Location: courses.php"); exit;
}

if ($u['role'] === 'admin' && $action === 'admin_status' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int)($_POST['id'] ?? 0);
    $status = $_POST['status'] ?? 'draft';
    if (in_array($status, ['draft','published'], true)) {
        $st = $pdo->prepare("UPDATE courses SET status=? WHERE id=?");
        $st->execute([$status, $id]);
        audit((int)$u['id'], 'admin_course_status', (string)$id . ':' . $status);
    }
    header("Location: courses.php"); exit;
}

if ($u['role'] === 'student' && $action === 'enroll' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $courseId = (int)($_POST['course_id'] ?? 0);
    $st = $pdo->prepare("SELECT id FROM courses WHERE id=? AND status='published'");
    $st->execute([$courseId]);
    if ($st->fetch()) {
        $st = $pdo->prepare("INSERT IGNORE INTO enrollments (course_id, student_id) VALUES (?, ?)");
        $st->execute([$courseId, (int)$u['id']]);
        audit((int)$u['id'], 'enroll', (string)$courseId);
    }
    header("Location: courses.php"); exit;
}

if ($u['role'] === 'student' && $action === 'unenroll' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $courseId = (int)($_POST['course_id'] ?? 0);
    $st = $pdo->prepare("DELETE FROM enrollments WHERE course_id=? AND student_id=?");
    $st->execute([$courseId, (int)$u['id']]);
    audit((int)$u['id'], 'unenroll', (string)$courseId);
    header("Location: courses.php"); exit;
}

if ($u['role'] === 'teacher') {
    $st = $pdo->prepare("SELECT c.*, u.email AS teacher_email
                         FROM courses c JOIN users u ON u.id=c.teacher_id
                         WHERE c.teacher_id=? ORDER BY c.id DESC");
    $st->execute([(int)$u['id']]);
    $courses = $st->fetchAll();
} else {
    $st = $pdo->query("SELECT c.*, u.email AS teacher_email
                       FROM courses c JOIN users u ON u.id=c.teacher_id
                       ORDER BY c.id DESC");
    $courses = $st->fetchAll();
}

$enrolled = [];
if ($u['role'] === 'student') {
    $st = $pdo->prepare("SELECT course_id FROM enrollments WHERE student_id=?");
    $st->execute([(int)$u['id']]);
    foreach ($st->fetchAll() as $r) $enrolled[(int)$r['course_id']] = true;
}
?>
<!doctype html>
<html lang="ru">
<head>
<meta charset="utf-8">
<title>Курсы</title>
<link rel="stylesheet" href="style.css">
</head>
<body>
<div class="container">
  <div class="card">
    <h2>Курсы</h2>
    <p><a href="dashboard.php">← Назад</a></p>

    <?php if ($u['role'] === 'teacher'): ?>
      <h3>Создать курс</h3>
      <form method="post" action="courses.php?action=create">
        <input type="hidden" name="csrf" value="<?php echo h(csrf_token()); ?>">
        <div class="form-group">
          <label class="form-label">Название</label>
          <input name="title" required>
        </div>
        <div class="form-group">
          <label class="form-label">Описание</label>
          <textarea name="description"></textarea>
        </div>
        <button class="btn" type="submit">Создать</button>
      </form>
      <hr style="border:none;border-top:1px solid var(--border);margin:18px 0;">
    <?php endif; ?>

    <h3>Список</h3>
    <table class="table">
      <tr>
        <th>ID</th><th>Название</th><th>Статус</th><th>Teacher</th><th>Действия</th>
      </tr>

      <?php foreach ($courses as $c): ?>
      <tr>
        <td><?php echo h($c['id']); ?></td>
        <td><?php echo h($c['title']); ?></td>
        <td><?php echo h($c['status']); ?></td>
        <td><?php echo h($c['teacher_email']); ?></td>
        <td>
          <?php if ($u['role'] === 'teacher'): ?>
            <form method="post" action="courses.php?action=update" style="margin-bottom:10px;">
              <input type="hidden" name="csrf" value="<?php echo h(csrf_token()); ?>">
              <input type="hidden" name="id" value="<?php echo h($c['id']); ?>">
              <div class="form-group" style="margin:0 0 10px;">
                <label class="form-label">Название</label>
                <input name="title" value="<?php echo h($c['title']); ?>" required>
              </div>
              <div class="form-group" style="margin:0 0 10px;">
                <label class="form-label">Описание</label>
                <input name="description" value="<?php echo h($c['description']); ?>">
              </div>
              <div class="form-group" style="margin:0 0 10px;">
                <label class="form-label">Статус</label>
                <select name="status">
                  <option value="draft" <?php echo $c['status']==='draft'?'selected':''; ?>>draft</option>
                  <option value="published" <?php echo $c['status']==='published'?'selected':''; ?>>published</option>
                </select>
              </div>
              <button class="btn" type="submit">Сохранить</button>
            </form>

            <form method="post" action="courses.php?action=delete">
              <input type="hidden" name="csrf" value="<?php echo h(csrf_token()); ?>">
              <input type="hidden" name="id" value="<?php echo h($c['id']); ?>">
              <button class="btn btn-danger" type="submit">Удалить</button>
            </form>

          <?php elseif ($u['role'] === 'admin'): ?>
            <form method="post" action="courses.php?action=admin_status">
              <input type="hidden" name="csrf" value="<?php echo h(csrf_token()); ?>">
              <input type="hidden" name="id" value="<?php echo h($c['id']); ?>">
              <select name="status">
                <option value="draft" <?php echo $c['status']==='draft'?'selected':''; ?>>draft</option>
                <option value="published" <?php echo $c['status']==='published'?'selected':''; ?>>published</option>
              </select>
              <button class="btn" type="submit">Поменять</button>
            </form>

          <?php else: ?>
            <?php if ($c['status'] === 'published'): ?>
              <?php if (!empty($enrolled[(int)$c['id']])): ?>
                <form method="post" action="courses.php?action=unenroll">
                  <input type="hidden" name="csrf" value="<?php echo h(csrf_token()); ?>">
                  <input type="hidden" name="course_id" value="<?php echo h($c['id']); ?>">
                  <button class="btn btn-danger" type="submit">Отписаться</button>
                </form>
              <?php else: ?>
                <form method="post" action="courses.php?action=enroll">
                  <input type="hidden" name="csrf" value="<?php echo h(csrf_token()); ?>">
                  <input type="hidden" name="course_id" value="<?php echo h($c['id']); ?>">
                  <button class="btn" type="submit">Записаться</button>
                </form>
              <?php endif; ?>
            <?php else: ?>
              <span class="small">недоступно</span>
            <?php endif; ?>
          <?php endif; ?>
        </td>
      </tr>
      <?php endforeach; ?>
    </table>
  </div>
</div>
</body>
</html>
