<?php
// admin/logout.php
require_once __DIR__ . '/../config/app.php';
logoutUser();
header('Location: ' . BASE_URL . '/admin/login.php');
exit;
