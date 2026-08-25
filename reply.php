<?php
require_once __DIR__ . '/config.php';

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

if ($threadId < 1 || $message === '') {
    http_response_code(400);
    exit('Пустой или некорректный ответ.');
}

$threads = data_load('threads.json');
$threadExists = false;
foreach ($threads as $thread) {
    if ((int)($thread['id'] ?? 0) === $threadId) {
        $threadExists = true;
        break;
    }
}
if (!$threadExists) {
    http_response_code(404);
    exit('Пост не найден.');
}

$replies = data_load('replies_' . $threadId . '.json');
if ($parentId > 0) {
    $parentExists = false;
    foreach ($replies as $reply) {
        if ((int)($reply['id'] ?? 0) === $parentId) {
            $parentExists = true;
            break;
        }
    }
    if (!$parentExists) {
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
    'created_at' => date('c')
];
data_save('replies_' . $threadId . '.json', $replies);

header('Location: thread.php?id=' . $threadId . '#reply-' . $newId);
exit;
