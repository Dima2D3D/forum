<?php
require_once __DIR__.'/../config.php';
$me = require_admin();
$users = data_load('users.json');
$title = 'Пользователи — GREFFRLEND';
include __DIR__.'/../includes/header.php';
?>
<section class="card">
    <h1>👥 Пользователи</h1>
    <?php if (isset($_GET['deleted'])): ?><div class="card success">Аккаунт удалён.</div><?php endif; ?>
    <?php foreach ($users as $u): ?>
        <div class="card thread" style="display:flex;align-items:center;justify-content:space-between;gap:15px;flex-wrap:wrap">
            <div>
                <b><?=e($u['username'] ?? 'Пользователь')?></b>
                <div class="muted"><?=e($u['email'] ?? '')?> · <?=e($u['role'] ?? 'user')?></div>
                <div class="muted">Последняя активность: <?=e((string)($u['last_activity'] ?? 'нет данных'))?></div>
            </div>
            <?php if (is_owner($me) && !is_owner($u)): ?>
                <form method="post" action="delete-user.php" onsubmit="return confirm('Удалить аккаунт <?=e($u['username'] ?? '')?> без возможности восстановления?')">
                    <input type="hidden" name="csrf" value="<?=e(csrf())?>">
                    <input type="hidden" name="user_id" value="<?=e((string)$u['id'])?>">
                    <button class="admin-btn" style="background:linear-gradient(105deg,#8d1717,#d92b28)!important" type="submit">🗑️ Удалить аккаунт</button>
                </form>
            <?php else: ?>
                <span class="pill">👑 OWNER</span>
            <?php endif; ?>
        </div>
    <?php endforeach; ?>
</section>
<?php include __DIR__.'/../includes/footer.php'; ?>