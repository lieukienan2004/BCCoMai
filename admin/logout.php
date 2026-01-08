<?php
require_once __DIR__ . '/../backend/config/config.php';

$user = new User();
$user->logout();

header('Location: login.php');
exit;
