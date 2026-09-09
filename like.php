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
$threadId = 0;

if ($type === 'thread') {
    $threads = data_load('threads.json');
    foreach ($threads as $thread) {
        if ((int)($thread['id'] ?? 0) === $id) {
            $targetUserId = (int)($thread['author_id'] ?? 0);
            $redirect = 'thread.php?id=' . $id;
            $threadId = $id;
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
    foreach (glob(DATA_DIR . 'replies_*.json') ?: [] as $path) {
        $rows = json_decode((string)@file_get_contents($path), true);
        if (!is_array($rows)) continue;

        $name = basename($path);
        if (!preg_match('/^replies_(\d+)\.json$/', $name, $m)) continue;
        $candidateThreadId = (int)$m[1];

        foreach ($rows as $reply) {
            if ((int)($reply['id'] ?? 0) === $id) {
                $replyFile = $name;
                $threadId = $candidateThreadId;
                $targetUserId = (int)($reply['author_id'] ?? 0);
                break 2;
            }
        }
    }

    if (!$replyFile || $threadId <= 0 || $targetUserId <= 0) {
        http_response_code(404);
        exit('Комментарий не найден.');
    }

    // ID комментариев начинаются заново в каждой теме, поэтому thread_id
    // обязательно входит в имя файла лайков. Иначе комментарий #1 одной
    // темы наследовал лайки комментария #1 другой темы.
    $file = 'likes_reply_' . $threadId . '_' . $id . '.json';
    $redirect = 'thread.php?id=' . $threadId;
}

$likes = data_load($file);
$normalized = [];
foreach ($likes as $like) {
    $normalized[] = is_array($like) ? (int)($like['user_id'] ?? 0) : (int)$like;
}
$normalized = array_values(array_unique(array_filter($normalized)));

$position = array_search((int)$u['id'], $normalized, true);
if ($position === false) {
    $normalized[] = (int)$u['id'];
    if ($targetUserId !== (int)$u['id']) {
        $actorName = (string)($u['username'] ?? 'Пользователь');
        notifications_add(
            $targetUserId,
            'like',
            $actorName . ' поставил(а) лайк на ' . ($type === 'thread' ? 'ваш пост.' : 'ваш комментарий.'),
            $redirect . '#post-' . $id
        );
    }
} else {
    unset($normalized[$position]);
    $normalized = array_values($normalized);
}

data_save($file, $normalized);
check_achievements($targetUserId);
header('Location: ' . $redirect . '#post-' . $id);
exit;
