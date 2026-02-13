<?php
require __DIR__ . '/../bootstrap.php';
$u = auth_require_login();
function h($s){ return htmlspecialchars((string)$s); }
?>
<!doctype html>
<html lang="ru">
<head>
<meta charset="utf-8">
<title>Dashboard</title>
<link rel="stylesheet" href="style.css">
</head>
<body>
<div class="container">
  <div class="card">
    <h2>Dashboard</h2>
    <p class="small">Вы вошли как:</p>
    <h3><?php echo h($u['email']); ?> <span class="badge"><?php echo h($u['role']); ?></span></h3>

    <div class="nav">
      <a href="courses.php">📚 Курсы</a>
      <a href="audit.php">🧾 Аудит</a>
      <a href="logout.php">🚪 Выйти</a>
    </div>
  </div>
</div>
</body>
</html>
