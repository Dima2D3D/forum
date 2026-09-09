<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');
require_once __DIR__ . '/config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();
    $login = strtolower(trim((string)($_POST['email'] ?? '')));
    $pass = (string)($_POST['password'] ?? '');
    $users = data_load('users.json');
    $found = null;

    foreach ($users as $x) {
        if (strtolower((string)($x['email'] ?? '')) === $login || strtolower((string)($x['username'] ?? '')) === $login || strtolower((string)($x['handle'] ?? '')) === ltrim($login, '@')) {
            $found = $x;
            break;
        }
    }

    if (!$found || !password_verify($pass, (string)($found['password_hash'] ?? ''))) {
        exit('LOGIN TEST: неверный логин или пароль');
    }

    if (empty($found['email_verified'])) {
        exit('LOGIN TEST: email не подтверждён');
    }

    if (empty($found['two_factor_enabled'])) {
        exit('LOGIN TEST: у этого аккаунта 2FA выключена');
    }

    $code = (string)random_int(100000, 999999);
    $_SESSION['2fa_pending'] = true;
    $_SESSION['2fa_uid'] = (int)$found['id'];
    $_SESSION['2fa_code_hash'] = password_hash($code, PASSWORD_DEFAULT);
    $_SESSION['2fa_expires'] = time() + 600;

    if (!send_html_mail((string)$found['email'], 'Код входа — GREFFRLEND', '<h1>Код подтверждения входа</h1><p>Ваш код: <b>' . e($code) . '</b></p>', 'Ваш код входа: ' . $code)) {
        exit('LOGIN TEST: письмо не отправилось');
    }

    session_write_close();
    header('Location: 2fa-test (1).php', true, 303);
    exit;
}

?>
<!doctype html><html><body>
<h2>LOGIN TEST (1)</h2>
<p>Тестируем только авторизацию и сохранение 2FA-сессии.</p>
<form method="post">
<input type="hidden" name="csrf" value="<?= e(csrf()) ?>">
<input name="email" placeholder="E-mail / username" required>
<input type="password" name="password" placeholder="Пароль" required>
<button type="submit">Войти — TEST</button>
</form>
</body></html>
