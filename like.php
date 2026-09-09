<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/social.php';

$u = require_login();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}
check_csrf();

if (!rate_limit('like', 2, 6)) {
    http_response_code(429);
    exit('Слишком много действий. Попробуйте через несколько секунд.');
}

$type = $_POST['type'] ?? '';
$id = (int)($_POST['id'] ?? 0);
if (!in_array($type, ['thread', 'reply'], true) || $id < 1) {
    http_response_code(400);
    exit('Некорректный запрос.');
}

$targetUserId = 0;
$redirect = 'index.php';

if ($type === 'thread') {
    $threads = data_load('threads.json');
    foreach ($threads as $thread) {
        if ((int)($thread['id'] ?? 0) === $id) {
            $targetUserId = (int)($thread['author_id'] ?? 0);
            $redirect = 'thread.php?id=' . $id;
            break;
        }
    }
    if ($targetUserId <= 0) {
        http_response_code(404);
        exit('Пост не найден.');
    }
    $file = 'likes_thread_' . $id . '.json';
} else {
    $replyFile = null;
    $threadId = 0;
    foreach (glob(DATA_DIR . 'replies_*.json') ?: [] as $path) {
        $rows = json_decode((string)@file_get_contents($path), true);
        if (!is_array($rows)) continue;
        foreach ($rows as $reply) {
            if ((int)($reply['id'] ?? 0) === $id) {
                $replyFile = basename($path);
                $threadId = (int)preg_replace('/\D/', '', str_replace(['replies_', '.json'], '', basename($path)));
                $targetUserId = (int)($reply['author_id'] ?? 0);
                break 2;
            }
        }
    }
    if (!$replyFile || $targetUserId <= 0) {
        http_response_code(404);
        exit('Комментарий не найден.');
    }
    $file = 'likes_reply_' . $id . '.json';
    $redirect = 'thread.php?id=' . $threadId;
}

$likes = data_load($file);
$normalized = [];
foreach ($likes as $like) $normalized[] = is_array($like) ? (int)($like['user_id'] ?? 0) : (int)$like;
$normalized = array_values(array_unique(array_filter($normalized)));

$position = array_search((int)$u['id'], $normalized, true);
if ($position === false) {
    $normalized[] = (int)$u['id'];
    if ($targetUserId !== (int)$u['id']) {
        $actorName = (string)($u['username'] ?? 'Пользователь');
        notifications_add($targetUserId, 'like', $actorName . ' поставил(а) лайк на ' . ($type === 'thread' ? 'ваш пост.' : 'ваш комментарий.'), $redirect . '#post-' . $id);
    }
} else {
    unset($normalized[$position]);
    $normalized = array_values($normalized);
}

data_save($file, $normalized);
check_achievements($targetUserId);
header('Location: ' . $redirect . '#post-' . $id);
exit;
