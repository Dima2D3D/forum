<?php
declare(strict_types=1);
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/messages.php';

$owner = require_owner();
$selectedA = max(0, (int)($_GET['a'] ?? 0));
$selectedB = max(0, (int)($_GET['b'] ?? 0));
$rows = [];
$ua = null;
$ub = null;
$error = '';

try {
    if ($selectedA > 0 && $selectedB > 0 && $selectedA !== $selectedB) {
        $ua = find_user_by_id($selectedA);
        $ub = find_user_by_id($selectedB);
        if (!$ua || !$ub) {
            $error = 'Один из пользователей не найден.';
        } else {
            $rows = conversation_rows($selectedA, $selectedB);
            log_action('Просмотр личной переписки', $ua['username'] . ' ↔ ' . $ub['username'], 'Только для передачи по требованию суда/уполномоченного органа.');
        }
    } elseif ($selectedA > 0 || $selectedB > 0) {
        $error = 'Некорректные параметры переписки.';
    }
    $conversations = conversation_participants_for_admin();
} catch (Throwable $e) {
    $conversations = [];
    $error = 'Не удалось загрузить личные переписки. Проверьте права на папку data и ключ шифрования сообщений.';
}

$title = 'Личные переписки — GREFFRLEND';
include __DIR__ . '/../includes/header.php';
?>
<div class="card">
    <h1>👑 Личные переписки</h1>
    <p class="muted">Доступ только владельцу. Просмотр предназначен исключительно для законного запроса/передачи материалов в рамках суда или иного уполномоченного производства.</p>
</div>
<?php if ($error): ?><div class="card danger" style="margin-top:15px"><?= e($error) ?></div><?php endif; ?>

<?php if ($ua && $ub && !$error): ?>
<div class="card">
    <h2><?= e((string)$ua['username']) ?> ↔ <?= e((string)$ub['username']) ?></h2>
    <?php if (!$rows): ?>
        <p class="muted">Переписка пуста.</p>
    <?php else: ?>
        <?php foreach ($rows as $m):
            $from = find_user_by_id((int)($m['from'] ?? 0));
            try { $body = message_decrypt((string)($m['body'] ?? '')); }
            catch (Throwable $e) { $body = '[Не удалось расшифровать сообщение]'; }
        ?>
        <div style="padding:12px;margin:9px 0;border:1px solid #302820;border-radius:10px">
            <b><?= e((string)($from['username'] ?? 'Пользователь')) ?></b>
            <span class="muted"> · <?= e((string)($m['created_at'] ?? '')) ?></span>
            <div style="white-space:pre-wrap;margin-top:6px"><?= e($body) ?></div>
        </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>
<?php endif; ?>

<h2>Переписки</h2>
<?php if (!$conversations): ?>
<div class="card"><p class="muted">Личных переписок пока нет.</p></div>
<?php else: ?>
<?php $seen = [];
foreach ($conversations as $pair):
    $aId = (int)($pair[0] ?? 0); $bId = (int)($pair[1] ?? 0);
    $key = $aId . '_' . $bId;
    if ($aId <= 0 || $bId <= 0 || $aId === $bId || isset($seen[$key])) continue;
    $seen[$key] = true;
    $a = find_user_by_id($aId); $b = find_user_by_id($bId);
    if (!$a || !$b) continue;
?>
<div class="card" style="display:flex;justify-content:space-between;gap:15px;align-items:center;flex-wrap:wrap">
    <b><?= e((string)$a['username']) ?> ↔ <?= e((string)$b['username']) ?></b>
    <a class="btn" href="messages.php?a=<?= $aId ?>&amp;b=<?= $bId ?>">Просмотреть</a>
</div>
<?php endforeach; ?>
<?php endif; ?>
<?php include __DIR__ . '/../includes/footer.php'; ?>
