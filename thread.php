<?php
require_once __DIR__ . '/config.php';

$id = (int)($_GET['id'] ?? 0);
$thread = null;
foreach (data_load('threads.json') as $item) {
    if ((int)($item['id'] ?? 0) === $id) {
        $thread = $item;
        break;
    }
}
if (!$thread) {
    http_response_code(404);
    exit('Пост не найден.');
}

$replies = data_load('replies_' . $id . '.json');
$likes = data_load('likes_thread_' . $id . '.json');
$me = current_user();
$title = ($thread['title'] ?? 'Пост') . ' — GREFFRLEND';
include __DIR__ . '/includes/header.php';

function reply_children(array $replies, int $parentId): array {
    return array_values(array_filter($replies, static function (array $reply) use ($parentId): bool {
        return (int)($reply['parent_id'] ?? 0) === $parentId;
    }));
}

function render_reply(array $reply, array $replies, ?array $me, int $threadId, int $level = 0): void {
    $replyId = (int)($reply['id'] ?? 0);
    $replyLikes = data_load('likes_reply_' . $replyId . '.json');
    $level = min($level, 4);
    ?>
    <article class="card post comment" id="reply-<?= $replyId ?>" style="margin-left:<?= $level * 24 ?>px">
        <div class="post-meta">
            <a href="profile.php?id=<?= (int)($reply['author_id'] ?? 0) ?>"><b><?= e($reply['author'] ?? 'Пользователь') ?></b></a>
            <span><?= e((string)($reply['created_at'] ?? '')) ?></span>
        </div>
        <div class="post-body"><?= nl2br(e((string)($reply['message'] ?? ''))) ?></div>
        <?php if (!empty($reply['attachments'])): ?>
            <div class="attachments">
                <?php foreach ($reply['attachments'] as $attachment): ?>
                    <a href="<?= e($attachment) ?>" target="_blank" rel="noopener">
                        <img src="<?= e($attachment) ?>" alt="Вложение" loading="lazy">
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        <div class="reactions">
            <?php if ($me): ?>
                <form method="post" action="like.php" class="inline-form">
                    <input type="hidden" name="csrf" value="<?= e(csrf()) ?>">
                    <input type="hidden" name="type" value="reply">
                    <input type="hidden" name="id" value="<?= $replyId ?>">
                    <button class="reaction" type="submit">❤️ <?= count($replyLikes) ?></button>
                </form>
                <button class="reaction" type="button" onclick="replyTo(<?= $replyId ?>, '<?= e($reply['author'] ?? '') ?>')">↩ Ответить</button>
            <?php endif; ?>
            <?php if ($me && ((int)($reply['author_id'] ?? 0) === (int)$me['id'] || is_owner($me))): ?>
                <form method="post" action="content_action.php" class="inline-form">
                    <input type="hidden" name="csrf" value="<?= e(csrf()) ?>">
                    <input type="hidden" name="type" value="reply">
                    <input type="hidden" name="thread_id" value="<?= $threadId ?>">
                    <input type="hidden" name="reply_id" value="<?= $replyId ?>">
                    <input type="hidden" name="action" value="delete">
                    <button class="reaction" type="submit">Удалить</button>
                </form>
            <?php endif; ?>
        </div>
        <?php foreach (reply_children($replies, $replyId) as $child): ?>
            <?php render_reply($child, $replies, $me, $threadId, $level + 1); ?>
        <?php endforeach; ?>
    </article>
    <?php
}
?>

<article class="card post" id="post-<?= $id ?>">
    <div class="category">ПОСТ</div>
    <h1><?= e($thread['title'] ?? '') ?></h1>
    <div class="post-meta">
        <a href="profile.php?id=<?= (int)($thread['author_id'] ?? 0) ?>"><b><?= e($thread['author'] ?? '') ?></b></a>
        <span><?= e((string)($thread['created_at'] ?? '')) ?></span>
        <?php if (!empty($thread['pinned'])): ?><span class="pin">📌 Закреплено</span><?php endif; ?>
    </div>
    <div class="post-body"><?= nl2br(e((string)($thread['content'] ?? ''))) ?></div>

    <?php if (!empty($thread['attachments'])): ?>
        <div class="attachments">
            <?php foreach ($thread['attachments'] as $attachment): ?>
                <a href="<?= e($attachment) ?>" target="_blank" rel="noopener">
                    <img src="<?= e($attachment) ?>" alt="Вложение" loading="lazy">
                </a>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <div class="reactions">
        <?php if ($me): ?>
            <form method="post" action="like.php" class="inline-form">
                <input type="hidden" name="csrf" value="<?= e(csrf()) ?>">
                <input type="hidden" name="type" value="thread">
                <input type="hidden" name="id" value="<?= $id ?>">
                <button class="reaction" type="submit">❤️ <?= count($likes) ?></button>
            </form>
        <?php endif; ?>
        <?php if ($me && ((int)($thread['author_id'] ?? 0) === (int)$me['id'] || is_owner($me))): ?>
            <form method="post" action="content_action.php" class="inline-form">
                <input type="hidden" name="csrf" value="<?= e(csrf()) ?>">
                <input type="hidden" name="type" value="thread">
                <input type="hidden" name="id" value="<?= $id ?>">
                <input type="hidden" name="action" value="delete">
                <button class="reaction" type="submit">Удалить</button>
            </form>
        <?php endif; ?>
        <?php if ($me && is_owner($me)): ?>
            <form method="post" action="content_action.php" class="inline-form">
                <input type="hidden" name="csrf" value="<?= e(csrf()) ?>">
                <input type="hidden" name="type" value="thread">
                <input type="hidden" name="id" value="<?= $id ?>">
                <input type="hidden" name="action" value="pin">
                <button class="reaction" type="submit">📌 <?= !empty($thread['pinned']) ? 'Открепить' : 'Закрепить' ?></button>
            </form>
        <?php endif; ?>
    </div>
</article>

<h2>Комментарии</h2>
<?php foreach (reply_children($replies, 0) as $reply): ?>
    <?php render_reply($reply, $replies, $me, $id); ?>
<?php endforeach; ?>

<?php if ($me): ?>
<div class="card form" id="reply-form">
    <h2>Ответить</h2>
    <div id="replying-to" class="muted"></div>
    <div class="emoji">
        <?php foreach (['😀','😂','❤️','🔥','👍','👎','🎮','😎','🤔','🎉','💀','🚀','🥳','😢','😡'] as $emoji): ?>
            <button type="button" onclick="insertEmoji('<?= e($emoji) ?>')"><?= e($emoji) ?></button>
        <?php endforeach; ?>
    </div>
    <form method="post" action="reply.php" enctype="multipart/form-data">
        <input type="hidden" name="csrf" value="<?= e(csrf()) ?>">
        <input type="hidden" name="thread_id" value="<?= $id ?>">
        <input type="hidden" name="parent_id" id="parent-id" value="0">
        <textarea id="reply-message" name="message" required maxlength="20000" placeholder="Напишите комментарий..."></textarea>
        <label>Фото/GIF — до <?= MAX_ATTACHMENTS ?> файлов</label>
        <input type="file" name="attachments[]" accept="image/jpeg,image/png,image/gif,image/webp" multiple>
        <button class="btn" type="submit">Отправить</button>
    </form>
</div>
<?php else: ?>
<div class="card">Чтобы отвечать, <a href="login.php">войдите</a> или <a href="register.php">зарегистрируйтесь</a>.</div>
<?php endif; ?>

<script>
function insertEmoji(emoji) {
    const field = document.getElementById('reply-message');
    if (!field) return;
    const start = field.selectionStart || field.value.length;
    const end = field.selectionEnd || field.value.length;
    field.value = field.value.slice(0, start) + emoji + field.value.slice(end);
    field.focus();
    field.selectionStart = field.selectionEnd = start + emoji.length;
}
function replyTo(id, name) {
    const parent = document.getElementById('parent-id');
    const label = document.getElementById('replying-to');
    const form = document.getElementById('reply-form');
    if (parent) parent.value = id;
    if (label) label.textContent = 'Ответ пользователю: ' + name + ' (Комментарий #' + id + ')';
    if (form) form.scrollIntoView({behavior:'smooth', block:'center'});
}
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
