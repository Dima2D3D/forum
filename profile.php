<?php
require_once __DIR__ . '/config.php';

$id = (int)($_GET['id'] ?? 0);
$profile = null;
foreach (data_load('users.json') as $item) {
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
$privacy = $profile['privacy'] ?? [
    'email' => 'nobody',
    'phone' => 'nobody',
    'description' => 'everyone'
];
$canSeeDescription = ($privacy['description'] ?? 'everyone') === 'everyone'
    || ($privacy['description'] ?? '') === 'members' && $me
    || ($me && (int)$me['id'] === (int)$profile['id']);
$canSeeEmail = $me && ((int)$me['id'] === (int)$profile['id'] || ($privacy['email'] ?? 'nobody') === 'everyone');
$canSeePhone = $me && ((int)$me['id'] === (int)$profile['id'] || ($privacy['phone'] ?? 'nobody') === 'everyone');
$title = ($profile['username'] ?? 'Профиль') . ' — GREFFRLEND';
include __DIR__ . '/includes/header.php';
?>

<section class="profile-cover" style="background-image:url('<?= e($profile['cover'] ?? 'assets/profile-cover.jpg') ?>')">
    <div class="profile-head">
        <img class="avatar" src="<?= e($profile['avatar'] ?? 'banners/IMG_20260727_215431_065.jpg') ?>" alt="Аватар">
        <div>
            <h1><?= e($profile['username']) ?></h1>
            <span class="pill"><?= e($profile['role'] ?? 'user') ?></span>
            <div class="muted">С нами с <?= e((string)($profile['created_at'] ?? '')) ?></div>
        </div>
    </div>
</section>

<div class="profile-grid">
    <section class="card">
        <h2>О пользователе</h2>
        <?php if ($canSeeDescription): ?>
            <p><?= nl2br(e($profile['description'] ?? 'Пользователь пока ничего о себе не написал.')) ?></p>
        <?php else: ?>
            <p class="muted">Описание скрыто настройками конфиденциальности.</p>
        <?php endif; ?>
    </section>

    <section class="card">
        <h2>Информация</h2>
        <?php if ($canSeeEmail): ?><p>E-mail: <?= e($profile['email']) ?></p><?php endif; ?>
        <?php if ($canSeePhone && !empty($profile['phone'])): ?><p>Телефон: <?= e($profile['phone']) ?></p><?php endif; ?>
        <?php if (!$canSeeEmail && !$canSeePhone): ?><p class="muted">Личная информация скрыта.</p><?php endif; ?>
        <?php if ($me && (int)$me['id'] === (int)$profile['id']): ?>
            <a class="btn" href="settings.php">Настройки профиля</a>
        <?php endif; ?>
    </section>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
