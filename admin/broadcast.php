<?php
require_once __DIR__.'/../config.php';
$me = require_owner();

$error = '';
$success = '';
$sent = 0;
$failed = 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();
    if (!rate_limit('admin_broadcast', 60, 1)) {
        $error = 'Рассылку можно запускать не чаще одного раза в минуту.';
    } else {
        $subject = clean_text($_POST['subject'] ?? '', 160);
        $heading = clean_text($_POST['heading'] ?? '', 120);
        $message = clean_text($_POST['message'] ?? '', 10000);
        if ($subject === '' || $heading === '' || $message === '') {
            $error = 'Заполните тему, заголовок и текст письма.';
        } else {
            $htmlMessage = nl2br(e($message));
            $html = '<!doctype html><html><body style="margin:0;background:#090909;color:#eee;font-family:Arial,sans-serif"><div style="max-width:620px;margin:30px auto;background:#111;border:1px solid #2c2c2c;border-radius:18px;overflow:hidden"><div style="padding:30px;background:linear-gradient(110deg,#111,#2a1005,#3a0808)"><div style="font-size:28px;font-weight:900;letter-spacing:4px;color:#ff6b00">GREFFRLEND</div></div><div style="padding:32px"><h1 style="margin-top:0">' . e($heading) . '</h1><div style="font-size:16px;line-height:1.65">' . $htmlMessage . '</div><p style="margin-top:28px"><a href="' . e(SITE_URL) . '" style="display:inline-block;padding:13px 20px;background:#f35b12;color:#fff;text-decoration:none;border-radius:9px;font-weight:700">Открыть форум</a></p></div><div style="padding:18px 32px;color:#777;border-top:1px solid #222">© 2025 — 2026 GREFFRLEND</div></div></body></html>';
            foreach (data_load('users.json') as $user) {
                if (empty($user['email_verified']) || empty($user['email']) || is_owner($user)) continue;
                if (send_html_mail((string)$user['email'], $subject, $html, $heading . "\n\n" . $message . "\n\n" . SITE_URL)) $sent++;
                else $failed++;
            }
            log_action('Рассылка E-mail', 'Все пользователи', 'Отправлено: ' . $sent . ', ошибок: ' . $failed);
            $success = 'Рассылка завершена. Отправлено: ' . $sent . '. Ошибок: ' . $failed . '.';
        }
    }
}

$title = 'Рассылка — GREFFRLEND';
include __DIR__.'/../includes/header.php';
?>
<section class="card form" style="max-width:820px;margin:30px auto">
    <div class="category">OWNER TOOL</div>
    <h1>📨 Рассылка всем пользователям</h1>
    <p class="muted">Письма получают только подтверждённые E-mail. Письмо оформлено в том же стиле, что письма подтверждения и код входа.</p>
    <?php if ($error): ?><div class="card danger"><?=e($error)?></div><?php endif; ?>
    <?php if ($success): ?><div class="card success"><?=e($success)?></div><?php endif; ?>
    <form method="post">
        <input type="hidden" name="csrf" value="<?=e(csrf())?>">
        <label>Тема письма</label>
        <input name="subject" maxlength="160" placeholder="Новости GREFFRLEND" required>
        <label>Заголовок</label>
        <input name="heading" maxlength="120" placeholder="Важная новость" required>
        <label>Текст</label>
        <textarea name="message" maxlength="10000" rows="12" placeholder="Текст рассылки..." required></textarea>
        <button class="btn" type="submit" onclick="return confirm('Отправить письмо всем подтверждённым пользователям?')">📨 Отправить всем</button>
    </form>
</section>
<?php include __DIR__.'/../includes/footer.php'; ?>