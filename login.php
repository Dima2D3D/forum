<?php
require_once __DIR__ . '/config.php';

if (user()) {
    header('Location: index.php');
    exit;
}

$err = '';
$loginValue = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();
    $loginValue = trim((string)($_POST['email'] ?? ''));

    if (!rate_limit('login', 30, 5)) {
        $err = 'Слишком много попыток входа. Подождите 30 секунд.';
    } else {
        $login = strtolower($loginValue);
        $pass = (string)($_POST['password'] ?? '');
        $found = null;
        $users = data_load('users.json');

        foreach ($users as $x) {
            $email = strtolower((string)($x['email'] ?? ''));
            $username = strtolower((string)($x['username'] ?? ''));
            $handle = strtolower((string)($x['handle'] ?? ''));
            if ($email === $login || $username === $login || $handle === ltrim($login, '@')) {
                $found = $x;
                break;
            }
        }

        if (!$found || !password_verify($pass, (string)($found['password_hash'] ?? ''))) {
            $err = 'Неверный E-mail/юзернейм или пароль.';
        } elseif (empty($found['email_verified'])) {
            $err = 'Сначала подтвердите E-mail. Проверьте почту, включая папку «Спам».';
        } elseif (!empty($found['ban']['active'])) {
            session_regenerate_id(true);
            $_SESSION['uid'] = (int)$found['id'];
            header('Location: banned.php');
            exit;
        } elseif (!empty($found['two_factor_enabled'])) {
            try {
                $code = (string)random_int(100000, 999999);
                $_SESSION['2fa_uid'] = (int)$found['id'];
                $_SESSION['2fa_code_hash'] = password_hash($code, PASSWORD_DEFAULT);
                $_SESSION['2fa_expires'] = time() + 600;

                $html = '<!doctype html><html><body style="margin:0;background:#090909;color:#eee;font-family:Arial,sans-serif"><div style="max-width:600px;margin:30px auto;background:#111;border:1px solid #292929;border-radius:18px;overflow:hidden"><div style="padding:28px;background:linear-gradient(110deg,#111,#2b1005,#390808);font-size:28px;font-weight:900;letter-spacing:4px;color:#ff6b00">GREFFRLEND</div><div style="padding:32px"><h1>Код подтверждения входа</h1><p>Ваш одноразовый код:</p><div style="font-size:38px;letter-spacing:10px;font-weight:900;color:#ff6b00">' . e($code) . '</div><p style="color:#999">Код действует 10 минут. Если это были не вы, просто проигнорируйте письмо.</p></div><div style="padding:18px 32px;border-top:1px solid #222;color:#777">© 2025 — 2026 GREFFRLEND</div></div></body></html>';

                if (!send_html_mail((string)$found['email'], 'Код входа — GREFFRLEND', $html, "Ваш код входа: $code. Код действует 10 минут.")) {
                    unset($_SESSION['2fa_uid'], $_SESSION['2fa_code_hash'], $_SESSION['2fa_expires']);
                    $err = 'Не удалось отправить код 2FA. Попробуйте позже или отключите 2FA в настройках профиля.';
                } else {
                    header('Location: 2fa.php');
                    exit;
                }
            } catch (Throwable $e) {
                unset($_SESSION['2fa_uid'], $_SESSION['2fa_code_hash'], $_SESSION['2fa_expires']);
                $err = 'Не удалось подготовить код входа. Попробуйте ещё раз.';
            }
        } else {
            try {
                foreach ($users as &$item) {
                    if ((int)($item['id'] ?? 0) === (int)$found['id']) {
                        $item['last_login'] = date('c');
                        $item['last_activity'] = date('c');
                        $item['last_ip'] = client_ip();
                        break;
                    }
                }
                unset($item);
                data_save('users.json', $users);
            } catch (Throwable $e) {
                // Ошибка записи статистики входа не должна блокировать авторизацию.
            }

            session_regenerate_id(true);
            $_SESSION['uid'] = (int)$found['id'];
            $_SESSION['activity_touch'] = time();
            header('Location: index.php');
            exit;
        }
    }
}

$title = 'Вход — GREFFRLEND';
include __DIR__ . '/includes/header.php';
?>
<div class="card form" style="margin:70px auto;max-width:560px">
    <a class="logo" href="index.php">GREFFRLEND</a>
    <h1>Вход</h1>
    <?php if ($err): ?><div class="card danger"><?= e($err) ?></div><?php endif; ?>
    <form method="post">
        <input type="hidden" name="csrf" value="<?= e(csrf()) ?>">
        <label>E-mail или юзернейм</label>
        <input type="text" name="email" autocomplete="username" value="<?= e($loginValue) ?>" placeholder="E-mail или @юзернейм" required>
        <label>Пароль</label>
        <input type="password" name="password" autocomplete="current-password" required>
        <button class="btn" type="submit">Войти</button>
    </form>
    <p><a href="register.php">Создать аккаунт</a></p>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>