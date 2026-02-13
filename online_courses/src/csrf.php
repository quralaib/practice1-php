<?php
function csrf_token(): string {
    if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32));
    return $_SESSION['csrf'];
}
function csrf_verify(): void {
    $t = $_POST['csrf'] ?? '';
    if (!$t || empty($_SESSION['csrf']) || !hash_equals($_SESSION['csrf'], $t)) {
        http_response_code(403);
        echo "CSRF validation failed";
        exit;
    }
}
