<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/social.php';
require_once __DIR__ . '/includes/economy.php';

$u = require_login();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}
check_csrf();

if (!rate_limit('reply', 10, 1)) {
    http_response_code(429);
    exit('Слишком часто. Подождите несколько секунд.');
}

$threadId = (int)($_POST['thread_id'] ?? 0);
$message = clean_text($_POST['message'] ?? '', 20000);
$parentId = (int)($_POST['parent_id'] ?? 0);
$premiumStyle = (string)($_POST['premium_style'] ?? 'default');
$allowedPremiumStyles = ['default','glow','glass','accent'];
$premiumUser = premium($u) || is_owner($u);
if (!$premiumUser || !in_array($premiumStyle, $allowedPremiumStyles, true)) $premiumStyle = 'default';

if ($threadId < 1 || $message === '') {
    http_response_code(400);
    exit('Пустой или некорректный ответ.');
}

$threads = data_load('threads.json');
$thread = null;
foreach ($threads as $item) {
    if ((int)($item['id'] ?? 0) === $threadId) {
        $thread = $item;
        break;
    }
}
if (!$thread) {
    http_response_code(404);
    exit('Пост не найден.');
}

$replies = data_load('replies_' . $threadId . '.json');
$parent = null;
if ($parentId > 0) {
    foreach ($replies as $reply) {
        if ((int)($reply['id'] ?? 0) === $parentId) {
            $parent = $reply;
            break;
        }
    }
    if (!$parent) {
        http_response_code(404);
        exit('Комментарий для ответа не найден.');
    }
}

$attachments = save_uploads('attachments');
$newId = next_id($replies);
$replies[] = [
    'id' => $newId,
    'author' => $u['username'],
    'author_id' => (int)$u['id'],
    'message' => $message,
    'attachments' => $attachments,
    'parent_id' => $parentId,
    'premium_style' => $premiumStyle,
    'created_at' => date('c')
];
data_save('replies_' . $threadId . '.json', $replies);

$url = 'thread.php?id=' . $threadId . '#reply-' . $newId;
$actorName = (string)($u['username'] ?? 'Пользователь');
$threadAuthorId = (int)($thread['author_id'] ?? 0);

if ($threadAuthorId > 0 && $threadAuthorId !== (int)$u['id']) {
    notifications_add($threadAuthorId, 'reply', $actorName . ' ответил(а) на ваш пост.', $url);
}
if ($parent) {
    $parentAuthorId = (int)($parent['author_id'] ?? 0);
    if ($parentAuthorId > 0 && $parentAuthorId !== (int)$u['id'] && $parentAuthorId !== $threadAuthorId) {
        notifications_add($parentAuthorId, 'reply', $actorName . ' ответил(а) на ваш комментарий.', $url);
    }
}
notify_mentions($message, (int)$u['id'], $url);
check_achievements((int)$u['id']);

header('Location: ' . $url);
exit;
