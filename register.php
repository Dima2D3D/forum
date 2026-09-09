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

            // Старый дизайн письма сохранён, меняется только транспорт:
            // обе MIME-части кодируются base64 и разбиваются по 76 символов,
            // чтобы Exim не отклонял письмо из-за слишком длинных строк.
            $subject = 'Подтверждение E-mail — GREFFRLEND';
            $html = '<!doctype html><html><body style="margin:0;background:#090909;color:#eee;font-family:Arial,sans-serif"><div style="max-width:620px;margin:30px auto;background:#111;border:1px solid #2c2c2c;border-radius:18px;overflow:hidden"><div style="padding:30px;background:linear-gradient(110deg,#111,#2a1005,#3a0808)"><div style="font-size:28px;font-weight:900;letter-spacing:4px;color:#ff6b00">GREFFRLEND</div></div><div style="padding:32px"><h1>Подтвердите E-mail</h1><p>Привет, ' . e($name) . '!</p><p>Нажмите кнопку ниже, чтобы подтвердить адрес электронной почты и завершить регистрацию.</p><p><a href="' . e($link) . '" style="display:inline-block;padding:14px 22px;background:#f35b12;color:#fff;text-decoration:none;border-radius:9px;font-weight:700">Подтвердить E-mail</a></p><p style="color:#999;font-size:13px">Если кнопка не работает, скопируйте ссылку:<br><a href="' . e($link) . '" style="color:#ff7a2b">' . e($link) . '</a></p></div><div style="padding:18px 32px;color:#777;border-top:1px solid #222">© 2025 — 2026 GREFFRLEND</div></div></body></html>';
            $plain = "Привет, $name!\n\nПодтвердите E-mail: $link";
            $boundary = '=_greffrlend_register_' . bin2hex(random_bytes(8));

            $body = '--' . $boundary . "\r\n"
                . "Content-Type: text/plain; charset=UTF-8\r\n"
                . "Content-Transfer-Encoding: base64\r\n\r\n"
                . chunk_split(base64_encode($plain), 76, "\r\n")
                . "\r\n--" . $boundary . "\r\n"
                . "Content-Type: text/html; charset=UTF-8\r\n"
                . "Content-Transfer-Encoding: base64\r\n\r\n"
                . chunk_split(base64_encode($html), 76, "\r\n")
                . '--' . $boundary . "--\r\n";

            $headers = "MIME-Version: 1.0\r\n"
                . 'Content-Type: multipart/alternative; boundary="' . $boundary . "\"\r\n"
                . 'From: GREFFRLEND <' . MAIL_FROM . ">\r\n"
                . 'Reply-To: ' . MAIL_FROM . "\r\n"
                . 'X-Mailer: GREFFRLEND PHP/' . PHP_VERSION . "\r\n";

            $sent = @mail($email, $subject, $body, $headers, '-f' . MAIL_FROM);
            if (!$sent) {
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