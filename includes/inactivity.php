<?php
require_once __DIR__.'/../config.php';

function run_inactivity_reminders(): void {
    $runner = data_load('inactivity_runner.json');
    $now = time();
    if ((int)($runner['last_run'] ?? 0) > $now - 86400) return;
    data_save('inactivity_runner.json', ['last_run' => $now]);

    $sent = data_load('inactivity_sent.json');
    $users = data_load('users.json');
    $changed = false;

    foreach ($users as $u) {
        $id = (int)($u['id'] ?? 0);
        $email = (string)($u['email'] ?? '');
        if ($id <= 0 || $email === '' || empty($u['email_verified']) || is_owner($u)) continue;

        $last = strtotime((string)($u['last_activity'] ?? $u['last_login'] ?? $u['created_at'] ?? ''));
        if ($last <= 0 || $last > $now - 30 * 86400) continue;

        $lastSent = strtotime((string)($sent[$id] ?? ''));
        if ($lastSent > 0 && $lastSent > $now - 30 * 86400) continue;

        $profileUrl = SITE_URL . '/profile.php?id=' . $id;
        $name = (string)($u['username'] ?? 'Пользователь');
        $html = '<!doctype html><html><body style="margin:0;background:#090909;color:#eee;font-family:Arial,sans-serif"><div style="max-width:620px;margin:30px auto;background:#111;border:1px solid #2c2c2c;border-radius:18px;overflow:hidden"><div style="padding:30px;background:linear-gradient(110deg,#111,#2a1005,#3a0808)"><div style="font-size:28px;font-weight:900;letter-spacing:4px;color:#ff6b00">GREFFRLEND</div></div><div style="padding:32px"><h1>' . e($name) . ', вы давно не заходили на форум.</h1><p style="font-size:17px;line-height:1.6">Может, зайдёшь? Мы будем рады тебя видеть снова.</p><p><a href="' . e($profileUrl) . '" style="display:inline-block;padding:14px 22px;background:#f35b12;color:#fff;text-decoration:none;border-radius:9px;font-weight:700">Открыть свою страницу</a></p><p style="color:#888;font-size:13px">' . e($profileUrl) . '</p></div><div style="padding:18px 32px;color:#777;border-top:1px solid #222">© 2025 — 2026 GREFFRLEND</div></div></body></html>';
        if (send_html_mail($email, 'Мы скучаем — GREFFRLEND', $html, "$name, вы давно не заходили на форум, может зайдёшь?\n$profileUrl")) {
            $sent[$id] = date('c');
            $changed = true;
        }
    }

    if ($changed) data_save('inactivity_sent.json', $sent);
}
