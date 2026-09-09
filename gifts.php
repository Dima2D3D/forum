<?php
require_once __DIR__.'/config.php';
require_once __DIR__.'/includes/economy.php';

$me = require_login();
$users = data_load('users.json');
$target = (int)($_GET['to'] ?? $_POST['to'] ?? 0);
$recipient = null;
foreach ($users as $u) if ((int)$u['id'] === $target) { $recipient = $u; break; }

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();
    if (!$recipient) $error = 'Пользователь не найден.';
    elseif (!rate_limit('gift', 10, 1)) $error = 'Подождите перед следующим подарком.';
    else {
        $description = clean_text($_POST['description'] ?? '', 500);
        if (!give_gift((int)$me['id'], $target, (int)$_POST['gift_id'], $description)) {
            $error = 'Недостаточно Гриферок или подарок недоступен.';
        } else {
            header('Location: gifts.php?to=' . $target . '&sent=1');
            exit;
        }
    }
}

include __DIR__.'/includes/header.php';
?>
<section class="card">
    <h1>🎁 Подарки</h1>
    <?php if ($recipient): ?>
        <p>Подарок для <b><?=e($recipient['username'])?></b>. Баланс: 🪙 <?=wallet($me)?></p>
        <?php if ($error): ?><div class="card danger"><?=e($error)?></div><?php endif; ?>
        <?php if (isset($_GET['sent'])): ?><div class="notice">Подарок отправлен!</div><?php endif; ?>

        <form method="post" class="form">
            <input type="hidden" name="csrf" value="<?=e(csrf())?>">
            <input type="hidden" name="to" value="<?=$target?>">
            <label>Сообщение к подарку</label>
            <textarea name="description" maxlength="500" rows="4" placeholder="Напишите что-нибудь получателю... (необязательно)"></textarea>
            <small class="muted">До 500 символов.</small>

            <div class="gift-grid">
                <?php foreach (gifts() as $g): if (empty($g['enabled'])) continue; ?>
                    <button class="card" type="submit" name="gift_id" value="<?=$g['id']?>" style="text-align:center;cursor:pointer;border:1px solid #34251d">
                        <div style="font-size:42px"><?=e($g['emoji'])?></div>
                        <b><?=e($g['name'])?></b>
                        <div>🪙 <?=e((string)$g['price'])?></div>
                        <div class="muted">Подарить</div>
                    </button>
                <?php endforeach; ?>
            </div>
        </form>
    <?php else: ?>
        <p>Откройте профиль пользователя и выберите «Подарить подарок».</p>
    <?php endif; ?>
</section>
<?php include __DIR__.'/includes/footer.php'; ?>