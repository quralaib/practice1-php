<?php
require __DIR__ . '/../bootstrap.php';
auth_logout();
header("Location: index.php");
exit;
