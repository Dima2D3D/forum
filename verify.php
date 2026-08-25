<?php
require __DIR__ . '/config.php';
$token = $_GET['token'] ?? '';
$ok = false;
if (preg_match('/^[a-f0-9]{64}$/', $token)) {
    $hash = hash('sha256',$token);
    $st=db()->prepare('SELECT * FROM email_tokens WHERE token_hash=? AND expires_at>? LIMIT 1');
    $st->execute([$hash,time()]); $row=$st->fetch();
    if ($row) {
        db()->prepare('UPDATE users SET email_verified=1 WHERE id=?')->execute([$row['user_id']]);
        db()->prepare('DELETE FROM email_tokens WHERE user_id=?')->execute([$row['user_id']]);
        $_SESSION['user_id']=$row['user_id']; $ok=true;
    }
}
?><!doctype html><html lang="ru"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Подтверждение E-mail — GREFFRLEND</title><link rel="stylesheet" href="/style.css"></head><body><main class="wrap"><section class="auth-card center" style="margin-top:14vh"><div class="big-icon"><?= $ok ? '✓' : '✕' ?></div><span class="eyebrow">GREFFRLEND ACCOUNT</span><h1><?= $ok ? 'E-mail подтверждён' : 'Ссылка недействительна' ?></h1><p><?= $ok ? 'Теперь вы можете создавать темы и отвечать на сообщения.' : 'Ссылка могла истечь или уже быть использована.' ?></p><a class="button" href="/">Перейти на форум</a></section></main></body></html>