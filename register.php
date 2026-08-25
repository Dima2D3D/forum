<?php
require_once __DIR__ . '/config.php';

if (user()) {
    header('Location: index.php');
    exit;
}

$err = '';
$ok = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();

    $name = trim($_POST['username'] ?? '');
    $email = strtolower(trim($_POST['email'] ?? ''));
    $pass = $_POST['password'] ?? '';
    $users = data_load('users.json');

    foreach ($users as $x) {
        if (strtolower($x['email'] ?? '') === $email || strtolower($x['username'] ?? '') === strtolower($name)) {
            $err = 'Ник или E-mail уже занят.';
            break;
        }
    }

    if (!$err && !preg_match('/^[A-Za-z0-9_]{3,24}$/', $name)) {
        $err = 'Некорректный ник. Используйте 3–24 символа: латиница, цифры и _. ';
    }

    if (!$err && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $err = 'Некорректный E-mail.';
    }

    if (!$err && strlen($pass) < 8) {
        $err = 'Пароль минимум 8 символов.';
    }

    if (!$err) {
        $id = count($users) + 1;
        $token = bin2hex(random_bytes(32));
        $users[] = [
            'id' => $id,
            'username' => $name,
            'email' => $email,
            'password_hash' => password_hash($pass, PASSWORD_DEFAULT),
            'email_verified' => false,
            'verify_token' => $token,
            'role' => strtolower($email) === strtolower(ADMIN_EMAIL) ? 'admin' : 'user',
            'banned_until' => null,
            'ban_reason' => '',
            'last_ip' => $_SERVER['REMOTE_ADDR'] ?? '',
            'created_at' => date('c')
        ];

        data_save('users.json', $users);

        $link = SITE_URL . '/verify.php?id=' . rawurlencode((string)$id) . '&token=' . rawurlencode($token);
        $safeLink = htmlspecialchars($link, ENT_QUOTES, 'UTF-8');
        $safeName = htmlspecialchars($name, ENT_QUOTES, 'UTF-8');

        $subject = 'GREFFRLEND — подтверждение E-mail';
        $headers = [
            'MIME-Version: 1.0',
            'Content-Type: text/html; charset=UTF-8',
            'From: GREFFRLEND <' . MAIL_FROM . '>',
            'Reply-To: ' . MAIL_FROM,
            'X-Mailer: GREFFRLEND Forum'
        ];

        $html = '<!doctype html>
<html lang="ru">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"></head>
<body style="margin:0;padding:0;background:#080808;color:#eeeeee;font-family:Arial,Helvetica,sans-serif;">
<table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background:#080808;padding:35px 12px;">
<tr><td align="center">
<table role="presentation" width="600" cellspacing="0" cellpadding="0" style="max-width:600px;background:#111111;border:1px solid #2b2b2b;border-radius:18px;overflow:hidden;">
<tr><td style="padding:30px 35px;text-align:center;background:linear-gradient(135deg,#111111,#241006,#170707);border-bottom:1px solid #3b1d0e;">
<div style="font-size:30px;font-weight:900;letter-spacing:5px;color:#ff6a00;">GREFFRLEND</div>
<div style="margin-top:8px;color:#999999;font-size:13px;letter-spacing:2px;">OFFICIAL FORUM</div>
</td></tr>
<tr><td style="padding:38px 35px;">
<h1 style="margin:0 0 18px;color:#ffffff;font-size:25px;">Подтвердите E-mail</h1>
<p style="margin:0 0 12px;color:#cccccc;font-size:16px;line-height:1.6;">Привет, <strong style="color:#ff7417;">' . $safeName . '</strong>!</p>
<p style="margin:0 0 28px;color:#aaaaaa;font-size:15px;line-height:1.7;">Вы зарегистрировали аккаунт на форуме GREFFRLEND. Нажмите кнопку ниже, чтобы подтвердить адрес электронной почты.</p>
<table role="presentation" cellspacing="0" cellpadding="0" style="margin:0 auto 28px;"><tr><td style="border-radius:9px;background:linear-gradient(90deg,#ff6500,#e51b23);">
<a href="' . $safeLink . '" style="display:inline-block;padding:14px 28px;color:#ffffff;text-decoration:none;font-weight:bold;font-size:16px;">Подтвердить E-mail</a>
</td></tr></table>
<p style="margin:0 0 8px;color:#888888;font-size:13px;line-height:1.6;">Если кнопка не работает, скопируйте эту ссылку в браузер:</p>
<p style="margin:0;padding:12px;background:#0a0a0a;border:1px solid #292929;border-radius:8px;word-break:break-all;font-size:12px;"><a href="' . $safeLink . '" style="color:#ff7417;text-decoration:underline;">' . $safeLink . '</a></p>
<p style="margin:28px 0 0;color:#666666;font-size:12px;line-height:1.6;">Если вы не регистрировались на GREFFRLEND, просто проигнорируйте это письмо.</p>
</td></tr>
<tr><td style="padding:20px 35px;text-align:center;border-top:1px solid #242424;color:#666666;font-size:12px;">© 2025 — 2026 GREFFRLEND. Все права защищены.</td></tr>
</table>
</td></tr></table>
</body></html>';

        $sent = @mail($email, '=?UTF-8?B?' . base64_encode($subject) . '?=', $html, implode("\r\n", $headers));

        if ($sent) {
            $ok = 'Аккаунт создан. Мы отправили красивое письмо с кнопкой подтверждения на ваш E-mail.';
        } else {
            $ok = 'Аккаунт создан, но хостинг не смог отправить письмо. Проверьте настройки почты на хостинге.';
        }
    }
}
?>
<!doctype html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Регистрация — GREFFRLEND</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<main class="wrap">
    <div class="card form" style="margin:70px auto">
        <a class="logo" href="index.php">GREFFRLEND</a>
        <h1>Регистрация</h1>

        <?php if ($err): ?>
            <div class="card danger"><?= e($err) ?></div>
        <?php endif; ?>

        <?php if ($ok): ?>
            <div class="card success"><?= e($ok) ?></div>
        <?php endif; ?>

        <form method="post">
            <input type="hidden" name="csrf" value="<?= e(csrf()) ?>">
            <label>Ник</label>
            <input name="username" maxlength="24" required>

            <label>E-mail</label>
            <input type="email" name="email" required>

            <label>Пароль</label>
            <input type="password" name="password" minlength="8" required>

            <button class="btn" type="submit">Создать аккаунт</button>
        </form>

        <p class="muted">После подтверждения адреса <?= e(ADMIN_EMAIL) ?> получает роль admin.</p>
    </div>
</main>
</body>
</html>
