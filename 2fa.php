<?php
require_once __DIR__ . '/config.php';

if (empty($_SESSION['2fa_uid']) || empty($_SESSION['2fa_code_hash']) || empty($_SESSION['2fa_expires'])) {
    header('Location: login.php');
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();
    $code = preg_replace('/\D/', '', (string)($_POST['code'] ?? ''));
    if (time() > (int)$_SESSION['2fa_expires']) {
        $error = 'Код истёк. Войдите заново.';
        unset($_SESSION['2fa_uid'], $_SESSION['2fa_code_hash'], $_SESSION['2fa_expires']);
    } elseif (!password_verify($code, (string)$_SESSION['2fa_code_hash'])) {
        $error = 'Неверный код подтверждения.';
    } else {
        $uid = (int)$_SESSION['2fa_uid'];
        $users = data_load('users.json');
        foreach ($users as &$item) {
            if ((int)($item['id'] ?? 0) === $uid) {
                $item['last_login'] = date('c');
                $item['last_activity'] = date('c');
                break;
            }
        }
        unset($item);
        data_save('users.json', $users);
        $_SESSION['uid'] = $uid;
        $_SESSION['activity_touch'] = time();
        unset($_SESSION['2fa_uid'], $_SESSION['2fa_code_hash'], $_SESSION['2fa_expires']);
        header('Location: index.php');
        exit;
    }
}

$title = 'Подтверждение входа — GREFFRLEND';
include __DIR__ . '/includes/header.php';
?>
<div class="card form" style="max-width:520px;margin:60px auto">
    <h1>Подтверждение входа</h1>
    <p class="muted">Мы отправили шестизначный код на ваш подтверждённый E-mail.</p>
    <?php if ($error): ?><div class="card danger"><?= e($error) ?></div><?php endif; ?>
    <form method="post">
        <input type="hidden" name="csrf" value="<?= e(csrf()) ?>">
        <label>Код</label>
        <input name="code" inputmode="numeric" autocomplete="one-time-code" maxlength="6" pattern="[0-9]{6}" required autofocus>
        <button class="btn">Подтвердить вход</button>
    </form>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>