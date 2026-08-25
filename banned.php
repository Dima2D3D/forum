<?php
require __DIR__ . '/config.php';
$u = user();
if (!$u || !is_banned($u)) { header('Location: /'); exit; }
$until = (int)$u['banned_until'];
?><!doctype html><html lang="ru"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Доступ ограничен — GREFFRLEND</title><link rel="stylesheet" href="/style.css"></head><body><main class="wrap"><section class="auth-card center" style="margin-top:14vh"><div class="big-icon">🚫</div><span class="eyebrow">GREFFRLEND MODERATION</span><h1>Доступ ограничен</h1><p>Ваш аккаунт заблокирован администрацией форума.</p><div class="notice"><b>Причина:</b> <?=e($u['ban_reason'] ?: 'Нарушение правил')?> <br><b>Срок:</b> <?=date('d.m.Y H:i',$until)?></div><p class="muted">Если вы считаете блокировку ошибочной, обратитесь в администрацию: <a href="mailto:admins@greffrlend.fun">admins@greffrlend.fun</a></p></section></main></body></html>