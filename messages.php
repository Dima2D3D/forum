<?php
declare(strict_types=1);
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/messages.php';

$me = require_login();
$users = data_load('users.json');

// Показываем только существующих пользователей, кроме самого себя.
$others = [];
foreach ($users as $u) {
    $id = (int)($u['id'] ?? 0);
    if ($id > 0 && $id !== (int)$me['id']) {
        $others[] = $u;
    }
}

$title = 'Личные сообщения — GREFFRLEND';
include __DIR__ . '/includes/header.php';
?>
<div class="card">
    <h1>💬 Личные сообщения</h1>
    <p class="muted">Переписки хранятся на сервере в зашифрованном виде.</p>

    <form method="get" action="message.php">
        <label>Написать пользователю</label>
        <input name="to" placeholder="@юзернейм" autocomplete="off" required>
        <button class="btn" type="submit">Открыть переписку</button>
    </form>
</div>

<?php if (!$others): ?>
    <div class="card">
        <p class="muted">Пока нет других пользователей для переписки.</p>
    </div>
<?php else: ?>
    <?php foreach ($others as $u):
        $uid = (int)$u['id'];
        $handle = (string)($u['handle'] ?? $u['username'] ?? '');
        $username = (string)($u['username'] ?? 'Пользователь');
        $rows = [];
        $lastText = '';
        $unread = 0;

        try {
            $rows = conversation_rows((int)$me['id'], $uid);
            if ($rows) {
                $last = $rows[count($rows) - 1];
                try {
                    $lastText = mb_substr(message_decrypt((string)($last['body'] ?? '')), 0, 90);
                } catch (Throwable $e) {
                    $lastText = '[Сообщение недоступно]';
                }
            }
            foreach ($rows as $m) {
                if ((int)($m['to'] ?? 0) === (int)$me['id'] && !empty($m['unread'])) {
                    $unread++;
                }
            }
        } catch (Throwable $e) {
            // Одна повреждённая переписка не должна ломать всю страницу.
            $rows = [];
            $lastText = '[Переписка недоступна]';
        }
    ?>
        <div class="card" style="display:flex;align-items:center;justify-content:space-between;gap:15px;flex-wrap:wrap">
            <div>
                <b><?= e($username) ?></b>
                <span class="username">@<?= e($handle) ?></span>
                <?php if ($lastText !== ''): ?>
                    <div class="muted"><?= e($lastText) ?></div>
                <?php endif; ?>
            </div>
            <a class="btn" href="message.php?to=<?= rawurlencode($handle) ?>">
                Открыть<?php if ($unread): ?> · <?= $unread ?> новых<?php endif; ?>
            </a>
        </div>
    <?php endforeach; ?>
<?php endif; ?>

<?php include __DIR__ . '/includes/footer.php'; ?>