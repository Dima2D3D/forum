<?php
require_once __DIR__ . '/config.php';

if (user()) {
    header('Location: index.php');
    exit;
}

$err = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();
    if (!rate_limit('login', 30, 5)) {
        $err = 'Слишком много попыток входа. Подождите 30 секунд.';
    } else {
        $email = strtolower(trim($_POST['email'] ?? ''));
        $pass = $_POST['password'] ?? '';
        $found = null;
        foreach (data_load('users.json') as $x) {
            if (strtolower((string)($x['email'] ?? '')) === $email) {
                $found = $x;
                break;
            }
        }

        if (!$found || !password_verify($pass, (string)($found['password_hash'] ?? ''))) {
            $err = 'Неверный E-mail или пароль.';
        } elseif (empty($found['email_verified'])) {
            $err = 'Сначала подтвердите E-mail.';
        } elseif (!empty($found['ban']['active'])) {
            $_SESSION['uid'] = (int)$found['id'];
            header('Location: banned.php');
            exit;
        } elseif (!empty($found['two_factor_enabled'])) {
            $code = (string)random_int(100000, 999999);
            $_SESSION['2fa_uid'] = (int)$found['id'];
            $_SESSION['2fa_code'] = $code;
            $_SESSION['2fa_expires'] = time() + 600;
            $subject = 'Код входа — GREFFRLEND';
            $html = '<!doctype html><html><body style="margin:0;background:#090909;color:#eee;font-family:Arial,sans-serif"><div style="max-width:600px;margin:30px auto;background:#111;border:1px solid #292929;border-radius:18px;overflow:hidden"><div style="padding:28px;background:linear-gradient(110deg,#111,#2b1005,#390808);font-size:28px;font-weight:900;letter-spacing:4px;color:#ff6b00">GREFFRLEND</div><div style="padding:32px"><h1>Код подтверждения входа</h1><p>Ваш одноразовый код:</p><div style="font-size:38px;letter-spacing:10px;font-weight:900;color:#ff6b00">' . e($code) . '</div><p style="color:#999">Код действует 10 минут. Если это были не вы, просто проигнорируйте письмо.</p></div><div style="padding:18px 32px;border-top:1px solid #222;color:#777">© 2025 — 2026 GREFFRLEND</div></div></body></html>';
            $headers = "MIME-Version: 1.0\r\nContent-Type: text/html; charset=UTF-8\r\nFrom: GREFFRLEND <" . MAIL_FROM . ">\r\nReply-To: " . MAIL_FROM . "\r\n";
            if (!@mail($found['email'], $subject, $html, $headers)) {
                unset($_SESSION['2fa_uid'], $_SESSION['2fa_code'], $_SESSION['2fa_expires']);
                $err = 'Не удалось отправить код. Проверьте настройки почты на хостинге.';
            } else {
                header('Location: 2fa.php');
                exit;
            }
        } else {
            $_SESSION['uid'] = (int)$found['id'];
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
        <label>E-mail</label>
        <input type="email" name="email" autocomplete="email" required>
        <label>Пароль</label>
        <input type="password" name="password" autocomplete="current-password" required>
        <button class="btn" type="submit">Войти</button>
    </form>
    <p><a href="register.php">Создать аккаунт</a></p>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
