<?php
function audit(?int $userId, string $action, ?string $meta = null): void {
    $pdo = db();
    $ip = $_SERVER['REMOTE_ADDR'] ?? null;
    $ua = $_SERVER['HTTP_USER_AGENT'] ?? null;
    $st = $pdo->prepare("INSERT INTO audit_log (user_id, action, meta, ip, ua) VALUES (?, ?, ?, ?, ?)");
    $st->execute([$userId, $action, $meta, $ip, $ua]);
}

function auth_session_guard(): void {
    if (!empty($_SESSION['uid'])) {
        $fp = $_SESSION['fp'] ?? '';
        if (!$fp || !hash_equals($fp, session_fingerprint())) {
            audit($_SESSION['uid'] ?? null, 'session_fingerprint_mismatch', null);
            session_destroy_all();
            header("Location: index.php");
            exit;
        }
    }
}

function auth_current_user(): ?array {
    if (empty($_SESSION['uid'])) return null;
    $pdo = db();
    $st = $pdo->prepare("SELECT id,email,role FROM users WHERE id=?");
    $st->execute([$_SESSION['uid']]);
    $u = $st->fetch();
    return $u ?: null;
}

function auth_require_login(): array {
    $u = auth_current_user();
    if (!$u) { header("Location: index.php"); exit; }
    return $u;
}

function auth_require_role(array $roles): array {
    $u = auth_require_login();
    if (!in_array($u['role'], $roles, true)) {
        audit((int)$u['id'], 'access_denied', $_SERVER['REQUEST_URI'] ?? '');
        http_response_code(403);
        echo "Access denied";
        exit;
    }
    return $u;
}

function auth_password_ok(string $p): bool {
    if (mb_strlen($p) < 8) return false;
    if (!preg_match('/[A-Z]/', $p)) return false;
    if (!preg_match('/[a-z]/', $p)) return false;
    if (!preg_match('/[0-9]/', $p)) return false;
    return true;
}

function auth_register(string $email, string $password): array {
    $email = trim(mb_strtolower($email));
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) return [false, "Неверный email"];
    if (!auth_password_ok($password)) return [false, "Пароль: минимум 8, A-Z, a-z, 0-9"];

    $pdo = db();
    $st = $pdo->prepare("SELECT id FROM users WHERE email=?");
    $st->execute([$email]);
    if ($st->fetch()) return [false, "Такой email уже существует"];

    $hash = password_hash($password, PASSWORD_BCRYPT);
    $st = $pdo->prepare("INSERT INTO users (email, password_hash, role) VALUES (?, ?, 'student')");
    $st->execute([$email, $hash]);

    audit((int)$pdo->lastInsertId(), 'register', null);
    return [true, "OK"];
}

function auth_login(string $email, string $password): array {
    $email = trim(mb_strtolower($email));
    $pdo = db();
    $st = $pdo->prepare("SELECT id,password_hash,role FROM users WHERE email=?");
    $st->execute([$email]);
    $u = $st->fetch();
    if (!$u || !password_verify($password, $u['password_hash'])) {
        audit(null, 'login_failed', $email);
        return [false, "Неверный логин или пароль"];
    }

    $_SESSION['uid'] = (int)$u['id'];
    $_SESSION['fp'] = session_fingerprint();
    session_regenerate_safe();
    audit((int)$u['id'], 'login_success', $u['role']);
    return [true, "OK"];
}

function auth_logout(): void {
    $u = auth_current_user();
    audit($u['id'] ?? null, 'logout', null);
    session_destroy_all();
}

function seed_user(string $email, string $password, string $role): void {
    $pdo = db();
    $st = $pdo->prepare("SELECT id FROM users WHERE email=?");
    $st->execute([$email]);
    if ($st->fetch()) return;
    $hash = password_hash($password, PASSWORD_BCRYPT);
    $st = $pdo->prepare("INSERT INTO users (email, password_hash, role) VALUES (?, ?, ?)");
    $st->execute([$email, $hash, $role]);
}
