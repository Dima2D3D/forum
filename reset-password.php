<?php
declare(strict_types=1);
require_once __DIR__ . '/config.php';

if (user()) {
    header('Location: index.php');
    exit;
}

$id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
$token = (string)($_GET['token'] ?? $_POST['token'] ?? '');
$err = '';
$ok = '';
$valid = false;
$users = data_load('users.json');
$userIndex = null;

foreach ($users as $index => $item) {
    if ((int)($item['id'] ?? 0) !== $id) continue;
    $hash = (string)($item['password_reset_token'] ?? '');
    $expires = (int)($item['password_reset_expires'] ?? 0);
    if ($token !== '' && $hash !== '' && $expires >= time() && password_verify($token, $hash)) {
        $valid = true;
        $userIndex = $index;
    }
    break;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();
    if (!$valid || $userIndex === null) {
        $err = 'Ссылка для сброса пароля недействительна или уже истекла.';
    } else {
        $pass = (string)($_POST['password'] ?? '');
        $pass2 = (string)($_POST['password_confirm'] ?? '');
        if (strlen($pass) < 8) {
            $err = 'Пароль должен содержать минимум 8 символов.';
        } elseif ($pass !== $pass2) {
            $err = 'Пароли не совпадают.';
        } else {
            $users[$userIndex]['password_hash'] = password_hash($pass, PASSWORD_DEFAULT);
            unset($users[$userIndex]['password_reset_token'], $users[$userIndex]['password_reset_expires']);
            data_save('users.json', $users);
            $valid = false;
            $ok = 'Пароль успешно изменён. Теперь можно войти с новым паролем.';
        }
    }
}

$title = 'Новый пароль — GREFFRLEND';
include __DIR__ . '/includes/header.php';
?>
<div class="card form" style="margin:70px auto;max-width:560px">
    <a class="logo" href="index.php">GREFFRLEND</a>
    <h1>Новый пароль</h1>
    <?php if ($err): ?><div class="card danger"><?= e($err) ?></div><?php endif; ?>
    <?php if ($ok): ?><div class="card success"><?= e($ok) ?></div><?php endif; ?>
    <?php if ($valid): ?>
    <form method="post">
        <input type="hidden" name="csrf" value="<?= e(csrf()) ?>">
        <input type="hidden" name="id" value="<?= e((string)$id) ?>">
        <input type="hidden" name="token" value="<?= e($token) ?>">
        <label>Новый пароль</label>
        <input type="password" name="password" minlength="8" autocomplete="new-password" required>
        <label>Повторите пароль</label>
        <input type="password" name="password_confirm" minlength="8" autocomplete="new-password" required>
        <button class="btn" type="submit">Изменить пароль</button>
    </form>
    <?php elseif (!$ok): ?>
        <p>Ссылка недействительна или срок её действия истёк.</p>
        <p><a href="forgot-password.php">Запросить новую ссылку</a></p>
    <?php else: ?>
        <p><a href="login.php">Войти в аккаунт</a></p>
    <?php endif; ?>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>