<?php
declare(strict_types=1);
require_once __DIR__ . '/config.php';

if (empty($_SESSION['2fa_pending']) || empty($_SESSION['2fa_uid']) || empty($_SESSION['2fa_code_hash']) || empty($_SESSION['2fa_expires'])) {
    header('Location: login.php');
    exit;
}

// Гарантируем наличие CSRF-токена именно в текущей сессии.
$csrfToken = csrf();
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postedCsrf = (string)($_POST['csrf'] ?? '');

    // Не вызываем check_csrf(), чтобы тестовая страница не падала с
    // «Недействительный запрос», если токен был потерян при переходе.
    // При наличии токена всё равно проверяем его стандартным способом.
    if ($postedCsrf === '' || !hash_equals($csrfToken, $postedCsrf)) {
        // Пересоздаём токен и оставляем пользователя на странице 2FA.
        // Сам код 2FA дополнительно защищает незавершённую авторизацию.
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
        $csrfToken = $_SESSION['csrf'];
        $error = 'Сессия обновлена. Введите код ещё раз.';
    } else {
        $code = preg_replace('/\D+/', '', (string)($_POST['code'] ?? ''));

        if (time() > (int)$_SESSION['2fa_expires']) {
            $error = 'Код истёк. Войдите заново.';
            unset($_SESSION['2fa_pending'], $_SESSION['2fa_uid'], $_SESSION['2fa_code_hash'], $_SESSION['2fa_expires']);
        } elseif (strlen($code) !== 6 || !password_verify($code, (string)$_SESSION['2fa_code_hash'])) {
            $error = 'Неверный код подтверждения.';
        } else {
            $uid = (int)$_SESSION['2fa_uid'];
            $users = data_load('users.json');
            $found = false;

            foreach ($users as &$item) {
                if ((int)($item['id'] ?? 0) === $uid) {
                    $item['last_login'] = date('c');
                    $item['last_activity'] = date('c');
                    $item['last_ip'] = client_ip();
                    $found = true;
                    break;
                }
            }
            unset($item);

            if (!$found) {
                $error = 'Пользователь не найден. Войдите заново.';
                unset($_SESSION['2fa_pending'], $_SESSION['2fa_uid'], $_SESSION['2fa_code_hash'], $_SESSION['2fa_expires']);
            } else {
                data_save('users.json', $users);
                session_regenerate_id(true);
                $_SESSION['uid'] = $uid;
                $_SESSION['activity_touch'] = time();
                unset($_SESSION['2fa_pending'], $_SESSION['2fa_uid'], $_SESSION['2fa_code_hash'], $_SESSION['2fa_expires']);
                session_write_close();
                header('Location: index.php', true, 303);
                exit;
            }
        }
    }
}

$title = 'Подтверждение входа — GREFFRLEND';
include __DIR__ . '/includes/header.php';
?>
<div class="card form" style="max-width:520px;margin:60px auto">
    <h1>Подтверждение входа</h1>
    <p class="muted">Введите шестизначный код из письма.</p>
    <?php if ($error): ?><div class="card danger"><?= e($error) ?></div><?php endif; ?>
    <form method="post">
        <input type="hidden" name="csrf" value="<?= e($csrfToken) ?>">
        <label>Код</label>
        <input name="code" inputmode="numeric" autocomplete="one-time-code" maxlength="6" pattern="[0-9]{6}" required autofocus>
        <button class="btn" type="submit">Подтвердить вход</button>
    </form>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>