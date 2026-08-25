<?php
require_once __DIR__ . '/config.php';
$u = require_login();
$users = data_load('users.json');
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();
    if (!rate_limit('settings', 5, 3)) exit('Слишком много изменений.');
    $action = $_POST['action'] ?? '';

    foreach ($users as &$item) {
        if ((int)$item['id'] !== (int)$u['id']) continue;

        if ($action === 'profile') {
            $item['description'] = clean_text($_POST['description'] ?? '', 1000);
            $item['avatar'] = $item['avatar'] ?? 'banners/IMG_20260727_215431_065.jpg';
            $item['cover'] = $item['cover'] ?? 'assets/profile-cover.jpg';
            $avatar = save_uploads('avatar');
            $cover = save_uploads('cover');
            if ($avatar) $item['avatar'] = $avatar[0];
            if ($cover) $item['cover'] = $cover[0];
            $success = 'Профиль сохранён.';
        }

        if ($action === 'privacy') {
            $item['privacy'] = [
                'email' => in_array($_POST['email_visibility'] ?? '', ['everyone','members','nobody'], true) ? $_POST['email_visibility'] : 'nobody',
                'phone' => in_array($_POST['phone_visibility'] ?? '', ['everyone','members','nobody'], true) ? $_POST['phone_visibility'] : 'nobody',
                'description' => in_array($_POST['description_visibility'] ?? '', ['everyone','members','nobody'], true) ? $_POST['description_visibility'] : 'everyone'
            ];
            $success = 'Настройки конфиденциальности сохранены.';
        }

        if ($action === 'contact') {
            $email = strtolower(trim($_POST['email'] ?? ''));
            $phone = trim($_POST['phone'] ?? '');
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $error = 'Введите корректный E-mail.';
            } else {
                $emailUsed = false;
                foreach ($users as $other) {
                    if ((int)($other['id'] ?? 0) !== (int)$u['id'] && strtolower((string)($other['email'] ?? '')) === $email) $emailUsed = true;
                }
                if ($emailUsed) $error = 'Этот E-mail уже используется.';
                else {
                    $item['email'] = $email;
                    $item['phone'] = mb_substr($phone, 0, 30);
                    $item['email_verified'] = false;
                    $success = 'Контактные данные сохранены. Новый E-mail нужно подтвердить.';
                }
            }
        }

        if ($action === 'password') {
            $old = $_POST['old_password'] ?? '';
            $new = $_POST['new_password'] ?? '';
            if (!password_verify($old, (string)$item['password_hash'])) $error = 'Старый пароль неверен.';
            elseif (strlen($new) < 8) $error = 'Новый пароль должен содержать минимум 8 символов.';
            else {
                $item['password_hash'] = password_hash($new, PASSWORD_DEFAULT);
                $success = 'Пароль изменён.';
            }
        }

        if ($action === '2fa') {
            $item['two_factor_enabled'] = !empty($_POST['two_factor_enabled']);
            $success = $item['two_factor_enabled'] ? 'Подтверждение входа по коду включено.' : 'Подтверждение входа отключено.';
        }
        unset($item);
        break;
    }
    data_save('users.json', $users);
    $u = current_user();
}

$privacy = $u['privacy'] ?? ['email'=>'nobody','phone'=>'nobody','description'=>'everyone'];
$title = 'Настройки профиля — GREFFRLEND';
include __DIR__ . '/includes/header.php';
?>
<div class="card form">
    <h1>Настройки профиля</h1>
    <?php if ($error): ?><div class="card danger"><?= e($error) ?></div><?php endif; ?>
    <?php if ($success): ?><div class="card success"><?= e($success) ?></div><?php endif; ?>

    <h2>Профиль</h2>
    <form method="post" enctype="multipart/form-data">
        <input type="hidden" name="csrf" value="<?= e(csrf()) ?>">
        <input type="hidden" name="action" value="profile">
        <label>Описание</label>
        <textarea name="description" maxlength="1000"><?= e($u['description'] ?? '') ?></textarea>
        <label>Аватар</label>
        <input type="file" name="avatar" accept="image/jpeg,image/png,image/gif,image/webp">
        <label>Фон профиля</label>
        <input type="file" name="cover" accept="image/jpeg,image/png,image/gif,image/webp">
        <button class="btn">Сохранить профиль</button>
    </form>

    <h2>Конфиденциальность</h2>
    <form method="post">
        <input type="hidden" name="csrf" value="<?= e(csrf()) ?>">
        <input type="hidden" name="action" value="privacy">
        <label>E-mail виден</label>
        <select name="email_visibility"><option value="everyone" <?=($privacy['email']??'nobody')==='everyone'?'selected':''?>>Всем</option><option value="members" <?=($privacy['email']??'')==='members'?'selected':''?>>Участникам</option><option value="nobody" <?=($privacy['email']??'')==='nobody'?'selected':''?>>Никому</option></select>
        <label>Телефон виден</label>
        <select name="phone_visibility"><option value="everyone">Всем</option><option value="members">Участникам</option><option value="nobody" selected>Никому</option></select>
        <label>Описание видят</label>
        <select name="description_visibility"><option value="everyone">Всем</option><option value="members">Участникам</option><option value="nobody">Никому</option></select>
        <button class="btn">Сохранить приватность</button>
    </form>

    <h2>Контактные данные</h2>
    <form method="post">
        <input type="hidden" name="csrf" value="<?= e(csrf()) ?>"><input type="hidden" name="action" value="contact">
        <label>E-mail</label><input type="email" name="email" value="<?= e($u['email']) ?>" required>
        <label>Номер телефона</label><input type="tel" name="phone" value="<?= e($u['phone'] ?? '') ?>" maxlength="30">
        <button class="btn">Изменить контакты</button>
    </form>

    <h2>Пароль</h2>
    <form method="post">
        <input type="hidden" name="csrf" value="<?= e(csrf()) ?>"><input type="hidden" name="action" value="password">
        <label>Старый пароль</label><input type="password" name="old_password" required>
        <label>Новый пароль</label><input type="password" name="new_password" minlength="8" required>
        <button class="btn">Изменить пароль</button>
    </form>

    <h2>Двухфакторная защита</h2>
    <p class="muted">При каждом новом входе форум отправит одноразовый код на подтверждённый E-mail.</p>
    <form method="post">
        <input type="hidden" name="csrf" value="<?= e(csrf()) ?>"><input type="hidden" name="action" value="2fa">
        <label><input type="checkbox" name="two_factor_enabled" value="1" <?= !empty($u['two_factor_enabled']) ? 'checked' : '' ?>> Включить 2FA по E-mail</label>
        <button class="btn">Сохранить 2FA</button>
    </form>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
