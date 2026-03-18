<?php
// dashboard.php — redirect alla ruota
require_once __DIR__ . '/auth.php';
requireLogin();
header('Location: wheel.php');
exit;
