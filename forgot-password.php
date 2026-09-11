<?php
declare(strict_types=1);
require_once __DIR__ . '/config.php';

if (user()) {
    header('Location: index.php');
    exit;
}

$err = '';
$ok = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();

    if (!rate_limit('forgot_password', 60, 3)) {
        $err = 'Слишком много запросов. Попробуйте через минуту.';
    } else {
        $email = strtolower(trim((string)($_POST['email'] ?? '')));
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $err = 'Введите корректный E-mail.';
        } else {
            $users = data_load('users.json');
            $foundIndex = null;
            foreach ($users as $index => $item) {
                if (strtolower((string)($item['email'] ?? '')) === $email) {
                    $foundIndex = $index;
                    break;
                }
            }

            // Не раскрываем, существует ли такой аккаунт.
            $ok = 'Если аккаунт с таким E-mail существует, письмо для сброса пароля уже отправлено. Проверьте входящие и папку «Спам».';

            if ($foundIndex !== null) {
                $token = bin2hex(random_bytes(32));
                $users[$foundIndex]['password_reset_token'] = password_hash($token, PASSWORD_DEFAULT);
                $users[$foundIndex]['password_reset_expires'] = time() + 3600;
                data_save('users.json', $users);

                $url = SITE_URL . '/reset-password.php?id=' . rawurlencode((string)($users[$foundIndex]['id'] ?? 0)) . '&token=' . rawurlencode($token);
                $username = (string)($users[$foundIndex]['username'] ?? 'Пользователь');
                $html = '<!doctype html><html><body style="margin:0;background:#090909;color:#eee;font-family:Arial,sans-serif"><div style="max-width:620px;margin:30px auto;background:#111;border:1px solid #2c2c2c;border-radius:18px;overflow:hidden"><div style="padding:30px;background:linear-gradient(110deg,#111,#2a1005,#3a0808)"><div style="font-size:28px;font-weight:900;letter-spacing:4px;color:#ff6b00">GREFFRLEND</div></div><div style="padding:32px"><h1>Сброс пароля</h1><p>Привет, ' . e($username) . '!</p><p>Нажмите кнопку ниже, чтобы задать новый пароль.</p><p><a href="' . e($url) . '" style="display:inline-block;padding:14px 22px;background:#f35b12;color:#fff;text-decoration:none;border-radius:9px;font-weight:700">Сбросить пароль</a></p><p style="color:#999;font-size:13px">Ссылка действует 1 час. Если вы не запрашивали сброс пароля, просто проигнорируйте это письмо.</p><p style="color:#999;font-size:13px">Если кнопка не работает, скопируйте ссылку:<br><a href="' . e($url) . '" style="color:#ff7a2b">' . e($url) . '</a></p></div><div style="padding:18px 32px;color:#777;border-top:1px solid #222">© 2025 — 2026 GREFFRLEND</div></div></body></html>';
                if (!send_html_mail($email, 'Сброс пароля — GREFFRLEND', $html, "Привет, $username! Сбросьте пароль: $url")) {
                    $ok = 'Не удалось отправить письмо. Попробуйте позже.';
                }
            }
        }
    }
}

$title = 'Восстановление пароля — GREFFRLEND';
include __DIR__ . '/includes/header.php';
?>
<div class="card form" style="margin:70px auto;max-width:560px">
    <a class="logo" href="index.php">GREFFRLEND</a>
    <h1>Забыли пароль?</h1>
    <?php if ($err): ?><div class="card danger"><?= e($err) ?></div><?php endif; ?>
    <?php if ($ok): ?><div class="card success"><?= e($ok) ?></div><?php endif; ?>
    <form method="post">
        <input type="hidden" name="csrf" value="<?= e(csrf()) ?>">
        <label>E-mail</label>
        <input type="email" name="email" autocomplete="email" required>
        <button class="btn" type="submit">Отправить ссылку</button>
    </form>
    <p><a href="login.php">Вернуться ко входу</a></p>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>