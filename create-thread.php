<?php
require_once __DIR__ . '/config.php';

$u = require_login();
$categories = data_load('categories.json');
$categoryId = (int)($_GET['category'] ?? 0);
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();
    if (!rate_limit('create_post', 60, 2)) {
        $error = 'Вы создаёте посты слишком часто. Подождите минуту.';
    } else {
        $categoryId = (int)($_POST['category_id'] ?? 0);
        $title = clean_text($_POST['title'] ?? '', 120);
        $content = clean_text($_POST['content'] ?? '', 20000);
        $validCategory = false;
        foreach ($categories as $category) {
            if ((int)($category['id'] ?? 0) === $categoryId) $validCategory = true;
        }

        if (!$validCategory) $error = 'Выберите существующую тему форума.';
        elseif (mb_strlen($title) < 3 || mb_strlen($content) < 3) $error = 'Заполните заголовок и текст.';
        else {
            $threads = data_load('threads.json');
            $newId = next_id($threads);
            $attachments = save_uploads('attachments');
            $threads[] = [
                'id' => $newId,
                'category_id' => $categoryId,
                'title' => $title,
                'content' => $content,
                'author' => $u['username'],
                'author_id' => (int)$u['id'],
                'attachments' => $attachments,
                'pinned' => false,
                'created_at' => date('c')
            ];
            data_save('threads.json', $threads);
            header('Location: thread.php?id=' . $newId);
            exit;
        }
    }
}

$title = 'Новый пост — GREFFRLEND';
include __DIR__ . '/includes/header.php';
?>
<div class="card form" style="max-width:760px;margin:50px auto">
    <div class="category">НОВЫЙ ПОСТ</div>
    <h1>Создать пост</h1>
    <?php if ($error): ?><div class="card danger"><?= e($error) ?></div><?php endif; ?>
    <form method="post" enctype="multipart/form-data">
        <input type="hidden" name="csrf" value="<?= e(csrf()) ?>">
        <label>Тема форума</label>
        <select name="category_id" required>
            <?php foreach ($categories as $category): ?>
                <option value="<?= (int)$category['id'] ?>" <?= $categoryId === (int)$category['id'] ? 'selected' : '' ?>><?= e($category['title']) ?></option>
            <?php endforeach; ?>
        </select>
        <label>Заголовок поста</label>
        <input name="title" maxlength="120" required>
        <label>Текст</label>
        <div class="emoji">
            <?php foreach (['😀','😂','❤️','🔥','👍','🎮','😎','🤔','🎉','🚀','🥳','💀'] as $emoji): ?>
                <button type="button" onclick="insertEmoji('<?= e($emoji) ?>')"><?= e($emoji) ?></button>
            <?php endforeach; ?>
        </div>
        <textarea id="post-content" name="content" maxlength="20000" required></textarea>
        <label>Фотографии и GIF — до <?= MAX_ATTACHMENTS ?> файлов, каждый до <?= (int)(MAX_UPLOAD_BYTES / 1024 / 1024) ?> МБ</label>
        <input type="file" name="attachments[]" accept="image/jpeg,image/png,image/gif,image/webp" multiple>
        <button class="btn" type="submit">Опубликовать</button>
    </form>
</div>
<script>
function insertEmoji(emoji) {
    const field = document.getElementById('post-content');
    const start = field.selectionStart || field.value.length;
    const end = field.selectionEnd || field.value.length;
    field.value = field.value.slice(0, start) + emoji + field.value.slice(end);
    field.focus();
    field.selectionStart = field.selectionEnd = start + emoji.length;
}
</script>
<?php include __DIR__ . '/includes/footer.php'; ?>
