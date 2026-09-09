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

    $name = trim((string)($_POST['username'] ?? ''));
    $email = strtolower(trim((string)($_POST['email'] ?? '')));
    $pass = (string)($_POST['password'] ?? '');
    $users = data_load('users.json');

    $reserved = ['admin','login','register','settings','messages','message','notifications','search','index','profile','rules','privacy','offer','gifts','achievements','banned'];

    foreach ($users as $x) {
        $xh = strtolower((string)($x['handle'] ?? ''));
        $xn = strtolower((string)($x['username'] ?? ''));
        if (strtolower($name) === $xn || strtolower($name) === $xh || $email === strtolower((string)($x['email'] ?? ''))) {
            $err = 'Ник, юзернейм или E-mail уже занят.';
            break;
        }
    }

    if (!$err && !preg_match('/^[A-Za-z0-9_]{3,24}$/', $name)) {
        $err = 'Некорректный ник. Используйте 3–24 символа: латиница, цифры и _.';
    }
    if (!$err && in_array(strtolower($name), $reserved, true)) {
        $err = 'Этот юзернейм зарезервирован системой.';
    }
    if (!$err && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $err = 'Некорректный E-mail.';
    }
    if (!$err && strlen($pass) < 8) {
        $err = 'Пароль минимум 8 символов.';
    }

    if (!$err) {
        $id = next_id($users);
        $token = bin2hex(random_bytes(32));
        $now = date('c');
        $role = $email === strtolower(OWNER_EMAIL) ? 'owner' : 'user';

        $users[] = [
            'id' => $id,
            'username' => $name,
            'handle' => $name,
            'email' => $email,
            'password_hash' => password_hash($pass, PASSWORD_DEFAULT),
            'email_verified' => false,
            'verify_token' => $token,
            'role' => $role,
            'ban' => null,
            'banned_until' => null,
            'ban_reason' => '',
            'ip' => client_ip(),
            'last_ip' => client_ip(),
            'last_login' => null,
            'last_activity' => $now,
            'created_at' => $now,
            'privacy' => ['email' => 'nobody', 'phone' => 'nobody', 'description' => 'everyone'],
            'description' => '',
            'avatar' => 'banners/IMG_20260727_215431_065.jpg',
            'cover' => 'banners/IMG_20260727_215431_065.jpg'
        ];

        $link = SITE_URL . '/verify.php?id=' . rawurlencode((string)$id) . '&token=' . rawurlencode($token);

        try {
            data_save('users.json', $users);

            // Основной способ — готовая почтовая функция форума.
            $sent = mail_verification($email, $name, $link);

            // Простой fallback для хостингов, где multipart/alternative
            // или параметр -f блокируется почтовым сервером.
            if (!$sent) {
                $subject = 'Подтверждение E-mail — GREFFRLEND';
                $safeName = e($name);
                $safeLink = e($link);
                $html = '<!doctype html><html lang="ru"><body style="font-family:Arial,sans-serif;background:#090909;color:#eee;padding:30px">'
                    . '<div style="max-width:600px;margin:auto;background:#111;padding:30px;border-radius:15px">'
                    . '<h1 style="color:#ff6b00">GREFFRLEND</h1>'
                    . '<p>Привет, ' . $safeName . '!</p>'
                    . '<p>Подтвердите E-mail, чтобы завершить регистрацию.</p>'
                    . '<p><a href="' . $safeLink . '" style="display:inline-block;padding:14px 22px;background:#f35b12;color:#fff;text-decoration:none;border-radius:9px;font-weight:bold">Подтвердить E-mail</a></p>'
                    . '<p>Если кнопка не работает, откройте ссылку:</p><p>' . $safeLink . '</p>'
                    . '</div></body></html>';
                $plain = "Привет, $name!\n\nПодтвердите E-mail: $link";
                $headers = "MIME-Version: 1.0\r\n"
                    . "Content-Type: multipart/alternative; boundary=greffrlend_register\r\n"
                    . "From: GREFFRLEND <" . MAIL_FROM . ">\r\n"
                    . "Reply-To: " . MAIL_FROM . "\r\n"
                    . "X-Mailer: GREFFRLEND\r\n";
                $body = "--greffrlend_register\r\n"
                    . "Content-Type: text/plain; charset=UTF-8\r\n"
                    . "Content-Transfer-Encoding: 8bit\r\n\r\n"
                    . $plain . "\r\n\r\n"
                    . "--greffrlend_register\r\n"
                    . "Content-Type: text/html; charset=UTF-8\r\n"
                    . "Content-Transfer-Encoding: 8bit\r\n\r\n"
                    . $html . "\r\n\r\n"
                    . "--greffrlend_register--\r\n";

                $sent = @mail($email, $subject, $body, $headers);
            }

            if ($sent) {
                $ok = 'Аккаунт создан. Письмо с кнопкой подтверждения отправлено на E-mail.';
            } else {
                $ok = 'Аккаунт создан, но хостинг не смог отправить письмо. Проверьте настройки почты хостинга.';
            }
        } catch (Throwable $e) {
            $err = 'Не удалось завершить регистрацию. Попробуйте ещё раз.';
        }
    }
}

$title = 'Регистрация — GREFFRLEND';
include __DIR__ . '/includes/header.php';
?>
<div class="card form">
    <div class="logo">GREFFRLEND</div>
    <h1>Регистрация</h1>
    <?php if ($err): ?><div class="card danger"><?= e($err) ?></div><?php endif; ?>
    <?php if ($ok): ?><div class="card success"><?= e($ok) ?></div><?php endif; ?>
    <form method="post">
        <input type="hidden" name="csrf" value="<?= e(csrf()) ?>">
        <label>Ник / юзернейм</label>
        <input name="username" maxlength="24" pattern="[A-Za-z0-9_]{3,24}" placeholder="например, Dima_123" required>
        <small class="muted">Уникальный адрес профиля. Его можно изменить в настройках.</small>
        <label>E-mail</label>
        <input type="email" name="email" required>
        <label>Пароль</label>
        <input type="password" name="password" minlength="8" required>
        <button class="btn" type="submit">Создать аккаунт</button>
    </form>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>