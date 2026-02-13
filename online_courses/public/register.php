<?php
require __DIR__ . '/../bootstrap.php';

$user = auth_current_user();
if ($user) { header("Location: dashboard.php"); exit; }

$error = '';
$okmsg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    $email = $_POST['email'] ?? '';
    $pass1 = $_POST['password'] ?? '';
    $pass2 = $_POST['password2'] ?? '';

    if ($pass1 !== $pass2) {
        $error = "Пароли не совпадают";
    } else {
        [$ok, $msg] = auth_register($email, $pass1);
        if ($ok) $okmsg = "Аккаунт создан. Теперь можно войти.";
        else $error = $msg;
    }
}

function h($s){ return htmlspecialchars((string)$s); }
?>
<!doctype html>
<html lang="ru">
<head>
<meta charset="utf-8">
<title>Регистрация</title>
<link rel="stylesheet" href="style.css">
</head>
<body>
<div class="container">
  <div class="card auth">
    <h2>РЕГИСТРАЦИЯ</h2>

    <?php if ($error): ?><div class="msg-error"><?php echo h($error); ?></div><?php endif; ?>
    <?php if ($okmsg): ?><div class="msg-ok"><?php echo h($okmsg); ?></div><?php endif; ?>

    <form method="post">
      <input type="hidden" name="csrf" value="<?php echo h(csrf_token()); ?>">

      <div class="form-group">
        <label class="form-label">Email</label>
        <input name="email" placeholder="Введите email" required>
      </div>

      <div class="form-group">
        <label class="form-label">Пароль</label>
        <div class="pw-wrap">
          <input id="pw1" name="password" type="password" placeholder="Напр: Test1234A" required>
          <button class="pw-toggle" type="button" onclick="togglePw('pw1', this)">👁</button>
        </div>
        <p class="small">Пароль: минимум 8 символов и должен содержать A-Z, a-z и 0-9.</p>
      </div>

      <div class="form-group">
        <label class="form-label">Повторите пароль</label>
        <div class="pw-wrap">
          <input id="pw2" name="password2" type="password" placeholder="Повторите пароль" required>
          <button class="pw-toggle" type="button" onclick="togglePw('pw2', this)">👁</button>
        </div>
      </div>

      <div class="auth-actions">
        <a href="index.php">Назад ко входу</a>
        <button class="btn btn-wide" type="submit">Создать</button>
      </div>
    </form>
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
