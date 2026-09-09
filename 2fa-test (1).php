<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');
require_once __DIR__ . '/config.php';

header('Content-Type: text/html; charset=UTF-8');

echo '<h2>2FA SESSION TEST (1)</h2>';
echo '<p>Session ID: <code>' . e(session_id()) . '</code></p>';
echo '<p>2fa_pending: <b>' . (empty($_SESSION['2fa_pending']) ? 'NO' : 'YES') . '</b></p>';
echo '<p>2fa_uid: <b>' . (isset($_SESSION['2fa_uid']) ? e((string)$_SESSION['2fa_uid']) : 'MISSING') . '</b></p>';
echo '<p>2fa_code_hash: <b>' . (empty($_SESSION['2fa_code_hash']) ? 'MISSING' : 'PRESENT') . '</b></p>';
echo '<p>2fa_expires: <b>' . (isset($_SESSION['2fa_expires']) ? e((string)$_SESSION['2fa_expires']) : 'MISSING') . '</b></p>';
echo '<p>PHP session status: <b>' . e((string)session_status()) . '</b></p>';

echo '<hr><p><b>Если здесь 2fa_pending = YES, значит сессия сохраняется, а проблема уже в обычном 2fa.php.</b></p>';
echo '<p><a href="login-test%20(1).php">Назад к тестовому входу</a></p>';
