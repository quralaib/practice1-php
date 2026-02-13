<?php
ini_set('display_errors', '0');
error_reporting(0);

require __DIR__ . '/src/connect_db.php';
require __DIR__ . '/src/session.php';
require __DIR__ . '/src/csrf.php';
require __DIR__ . '/src/auth_service.php';

session_start_secure();
auth_session_guard();
