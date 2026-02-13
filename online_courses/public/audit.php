<?php
require __DIR__ . '/../bootstrap.php';
$u = auth_require_login();
$pdo = db();

$st = $pdo->prepare("SELECT action, meta, created_at FROM audit_log WHERE user_id=? ORDER BY id DESC LIMIT 10");
$st->execute([(int)$u['id']]);
$rows = $st->fetchAll();

function h($s){ return htmlspecialchars((string)$s); }
?>
<!doctype html>
<html lang="ru">
<head>
<meta charset="utf-8">
<title>Аудит</title>
<link rel="stylesheet" href="style.css">
</head>
<body>
<div class="container">
  <div class="card">
    <h2>Аудит (последние 10)</h2>
    <p><a href="dashboard.php">← Назад</a></p>

    <table class="table">
      <tr><th>Время</th><th>Действие</th><th>Meta</th></tr>
      <?php foreach ($rows as $r): ?>
      <tr>
        <td><?php echo h($r['created_at']); ?></td>
        <td><?php echo h($r['action']); ?></td>
        <td><?php echo h($r['meta']); ?></td>
      </tr>
      <?php endforeach; ?>
    </table>
  </div>
</div>
</body>
</html>
