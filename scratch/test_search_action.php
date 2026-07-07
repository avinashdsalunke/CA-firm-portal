<?php
session_start();
$_SESSION['logged_in'] = true;
$_SESSION['user_id'] = 1;
$_SESSION['user_name'] = 'Super Admin';
$_SESSION['user_email'] = 'test';
$_SESSION['user_role'] = 'super_admin';
$_SESSION['last_activity'] = time();

$_GET['action'] = 'mega_search';
$_GET['q'] = 'kiran';

ob_start();
require_once __DIR__ . '/../public/index.php';
$output = ob_get_clean();
echo "Response:\n";
echo $output . "\n";
