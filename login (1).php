<?php
require_once __DIR__ . '/config.php';
if (user()) { header('Location: index.php'); exit; }
$err = '';
$loginValue = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();
    $loginValue = trim((string)($_POST['email'] ?? ''));
    $pass = (string)($_POST['password'] ?? '');
    $login = strtolower($loginValue);
    $found = null;
    $users = data_load('users.json');
    foreach ($users as $x) {
        if (strtolower((string)($x['email'] ?? '')) === $login || strtolower((string)($x['username'] ?? '')) === $login || strtolower((string)($x['handle'] ?? '')) === ltrim($login, '@')) { $found = $x; break; }
    }
    if (!$found || !password_verify($pass, (string)($found['password_hash'] ?? ''))) {
        $err = 'Неверный E-mail/юзернейм или пароль.';
    } elseif (empty($found['email_verified'])) {
        $err = 'Сначала подтвердите E-mail.';
    } elseif (!empty($found['ban']['active'])) {
        session_regenerate_id(true); $_SESSION['uid'] = (int)$found['id']; header('Location: banned.php'); exit;
    } elseif (!empty($found['two_factor_enabled'])) {
        try {
            $code = (string)random_int(100000, 999999);
            $_SESSION['2fa_pending'] = true;
            $_SESSION['2fa_uid'] = (int)$found['id'];
            $_SESSION['2fa_code_hash'] = password_hash($code, PASSWORD_DEFAULT);
            $_SESSION['2fa_expires'] = time() + 600;
            $html = '<h1>Код подтверждения входа</h1><p>Ваш код: <b style="font-size:30px">' . e($code) . '</b></p><p>Код действует 10 минут.</p>';
            if (send_html_mail((string)$found['email'], 'Код входа — GREFFRLEND', $html, 'Ваш код входа: ' . $code)) {
                session_write_close();
                header('Location: /2fa.php');
                exit;
            }
            unset($_SESSION['2fa_pending'], $_SESSION['2fa_uid'], $_SESSION['2fa_code_hash'], $_SESSION['2fa_expires']);
            $err = 'Не удалось отправить код 2FA.';
        } catch (Throwable $e) { $err = 'Ошибка подготовки 2FA.'; }
    } else {
        session_regenerate_id(true); $_SESSION['uid'] = (int)$found['id']; $_SESSION['activity_touch'] = time(); session_write_close(); header('Location: index.php'); exit;
    }
}
$title = 'Вход — GREFFRLEND';
include __DIR__ . '/includes/header.php';
?>
<div class="card form" style="margin:70px auto;max-width:560px">
<a class="logo" href="index.php">GREFFRLEND</a><h1>Вход</h1>
<?php if ($err): ?><div class="card danger"><?= e($err) ?></div><?php endif; ?>
<form method="post"><input type="hidden" name="csrf" value="<?= e(csrf()) ?>"><label>E-mail или юзернейм</label><input type="text" name="email" autocomplete="username" value="<?= e($loginValue) ?>" placeholder="E-mail или @юзернейм" required><label>Пароль</label><input type="password" name="password" autocomplete="current-password" required><button class="btn" type="submit">Войти</button></form>
<p><a href="register.php">Создать аккаунт</a></p></div>
<?php include __DIR__ . '/includes/footer.php'; ?>
