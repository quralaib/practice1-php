<?php
require __DIR__ . '/../bootstrap.php';

$user = auth_current_user();
if ($user) { header("Location: dashboard.php"); exit; }

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    [$ok, $msg] = auth_login($_POST['email'] ?? '', $_POST['password'] ?? '');
    if ($ok) { header("Location: dashboard.php"); exit; }
    $error = $msg;
}

function h($s){ return htmlspecialchars((string)$s); }
?>
<!doctype html>
<html lang="ru">
<head>
<meta charset="utf-8">
<title>Вход</title>
<link rel="stylesheet" href="style.css">
</head>
<body>
<div class="container">
  <div class="card auth">
    <h2>ВХОД</h2>

    <?php if ($error): ?>
      <div class="msg-error"><?php echo h($error); ?></div>
    <?php endif; ?>

    <form method="post">
      <input type="hidden" name="csrf" value="<?php echo h(csrf_token()); ?>">

      <div class="form-group">
        <label class="form-label">Email</label>
        <input name="email" placeholder="Введите email" required>
      </div>

      <div class="form-group">
        <label class="form-label">Пароль</label>
        <div class="pw-wrap">
          <input id="pw" name="password" type="password" placeholder="Введите пароль" required>
          <button class="pw-toggle" type="button" onclick="togglePw('pw', this)">👁</button>
        </div>
      </div>

      <div class="auth-actions">
        <a href="register.php">Создать аккаунт</a>
        <button class="btn btn-wide" type="submit">Войти</button>
      </div>
    </form>

    <p class="small" style="margin-top:14px;">
      <a href="seed.php">Создать тест-аккаунты</a>
    </p>
  </div>
</div>

<script>
function togglePw(id, btn){
  const el = document.getElementById(id);
  const isPw = el.type === 'password';
  el.type = isPw ? 'text' : 'password';
  btn.textContent = isPw ? '🙈' : '👁';
}
</script>
</body>
</html>
