<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/social.php';
require_once __DIR__ . '/includes/economy.php';

$id = (int)($_GET['id'] ?? 0);
$profile = null;
$users = data_load('users.json');
foreach ($users as $item) {
    if ((int)($item['id'] ?? 0) === $id) {
        $profile = $item;
        break;
    }
}
if (!$profile) {
    http_response_code(404);
    exit('Пользователь не найден');
}

$me = current_user();
$privacy = $profile['privacy'] ?? ['email' => 'nobody', 'phone' => 'nobody', 'description' => 'everyone'];
$canSeeDescription = ($privacy['description'] ?? 'everyone') === 'everyone'
    || (($privacy['description'] ?? '') === 'members' && $me)
    || ($me && (int)$me['id'] === $id);
$canSeeEmail = $me && ((int)$me['id'] === $id || ($privacy['email'] ?? 'nobody') === 'everyone');
$canSeePhone = $me && ((int)$me['id'] === $id || ($privacy['phone'] ?? 'nobody') === 'everyone');
$premium = subscription($profile) !== null || is_owner($profile);
$title = ($profile['username'] ?? 'Профиль') . ' — GREFFRLEND';

$giftMap = [];
foreach (gifts() as $gift) $giftMap[(int)$gift['id']] = $gift;
$receivedGifts = [];
foreach (data_load('gift_logs.json') as $giftLog) {
    if ((int)($giftLog['to'] ?? 0) !== $id) continue;
    $giftId = (int)($giftLog['gift_id'] ?? 0);
    if (!isset($giftMap[$giftId])) continue;
    $fromName = 'Пользователь';
    $fromId = (int)($giftLog['from'] ?? 0);
    foreach ($users as $giftUser) {
        if ((int)($giftUser['id'] ?? 0) === $fromId) {
            $fromName = (string)$giftUser['username'];
            break;
        }
    }
    $receivedGifts[] = [
        'gift' => $giftMap[$giftId],
        'from' => $fromName,
        'description' => (string)($giftLog['description'] ?? ''),
        'time' => (string)($giftLog['time'] ?? '')
    ];
}
$receivedGifts = array_reverse($receivedGifts);

include __DIR__ . '/includes/header.php';
?>

<section class="profile-cover" style="background-image:url('<?=e($profile['cover'] ?? 'banners/IMG_20260727_215431_065.jpg')?>');background-size:cover;background-position:center;height:220px;max-height:42vw;border-radius:18px;border:1px solid #34251d;overflow:hidden">
    <div style="height:100%;padding:22px;display:flex;align-items:flex-end;background:linear-gradient(180deg,transparent 25%,rgba(0,0,0,.9))">
        <div class="profile-head" style="display:flex;align-items:center;gap:16px">
            <img class="avatar" src="<?=e($profile['avatar'] ?? 'banners/IMG_20260727_215431_065.jpg')?>" alt="Аватар" style="width:82px;height:82px;max-width:82px;max-height:82px;object-fit:cover;border-radius:50%;border:3px solid #ff6817;transform:translateY(10px)">
            <div>
                <h1 style="margin:0"><?=e($profile['username'])?><?php if ($premium): ?> <span class="premium-badge">⭐ PREMIUM</span><?php endif; ?></h1>
                <div class="username">@<?=e((string)($profile['handle'] ?? $profile['username']))?></div>
                <span class="pill"><?=e($profile['role'] ?? 'user')?></span>
                <div class="muted">С нами с <?=e((string)($profile['created_at'] ?? ''))?></div>
            </div>
        </div>
    </div>
</section>

<div class="profile-stats">
    <span><b><?=follower_count($id)?></b> подписчиков</span>
    <span><b><?=following_count($id)?></b> подписок</span>
    <span><b><?=count(user_achievements($id))?></b> достижений</span>
    <span><b><?=wallet($profile)?></b> 🪙</span>
</div>

<?php if ($me && (int)$me['id'] !== $id): ?>
<div style="display:flex;gap:10px;flex-wrap:wrap;margin:14px 0 20px">
    <form method="post" action="follow.php" class="follow-form" style="margin:0">
        <input type="hidden" name="csrf" value="<?=e(csrf())?>">
        <input type="hidden" name="id" value="<?=$id?>">
        <input type="hidden" name="return" value="profile.php?id=<?=$id?>">
        <button class="btn" type="submit"><?=is_following((int)$me['id'], $id) ? 'Отписаться' : 'Подписаться'?></button>
    </form>
    <a class="btn" href="gifts.php?to=<?=$id?>">🎁 Подарить подарок</a>
</div>
<?php endif; ?>

<div class="profile-grid">
    <section class="card">
        <h2>О пользователе</h2>
        <?php if ($canSeeDescription): ?>
            <p><?=nl2br(e($profile['description'] ?? 'Пользователь пока ничего о себе не написал.'))?></p>
        <?php else: ?>
            <p class="muted">Описание скрыто настройками конфиденциальности.</p>
        <?php endif; ?>
    </section>

    <section class="card">
        <h2>Информация</h2>
        <?php if ($canSeeEmail): ?><p>E-mail: <?=e($profile['email'])?></p><?php endif; ?>
        <?php if ($canSeePhone && !empty($profile['phone'])): ?><p>Телефон: <?=e($profile['phone'])?></p><?php endif; ?>
        <?php if (!$canSeeEmail && !$canSeePhone): ?><p class="muted">Личная информация скрыта.</p><?php endif; ?>
        <?php if ($me && (int)$me['id'] === $id): ?>
            <a class="btn" href="settings.php">Настройки профиля</a>
            <a class="btn" href="achievements.php">Достижения</a>
        <?php endif; ?>
    </section>
</div>

<section class="card" style="margin-top:18px">
    <div style="display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap">
        <h2 style="margin:0">🎁 Подарки</h2>
        <?php if ($me && (int)$me['id'] !== $id): ?><a class="btn" href="gifts.php?to=<?=$id?>">🎁 Подарить подарок</a><?php endif; ?>
    </div>
    <?php if (!$receivedGifts): ?>
        <p class="muted">Пока никто не подарил этому пользователю подарок.</p>
    <?php else: ?>
        <div class="gift-grid" style="margin-top:14px">
            <?php foreach ($receivedGifts as $received): ?>
                <div class="card" style="text-align:center;margin:0;padding:18px">
                    <div style="font-size:42px"><?=e($received['gift']['emoji'] ?? '🎁')?></div>
                    <b><?=e($received['gift']['name'] ?? 'Подарок')?></b>
                    <div class="muted" style="margin-top:6px">От <?=e($received['from'])?></div>
                    <?php if ($received['description'] !== ''): ?><p style="margin:10px 0 0"><?=nl2br(e($received['description']))?></p><?php endif; ?>
                    <?php if ($received['time'] !== ''): ?><div class="muted" style="font-size:12px;margin-top:4px"><?=e($received['time'])?></div><?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>

<?php include __DIR__ . '/includes/footer.php'; ?>