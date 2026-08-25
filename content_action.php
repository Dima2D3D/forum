<?php
require_once __DIR__ . '/config.php';

$u = require_login();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}
check_csrf();

$type = $_POST['type'] ?? '';
$action = $_POST['action'] ?? '';
$id = (int)($_POST['id'] ?? 0);

if ($type === 'thread') {
    $threads = data_load('threads.json');
    $index = null;
    foreach ($threads as $key => $thread) {
        if ((int)($thread['id'] ?? 0) === $id) {
            $index = $key;
            break;
        }
    }
    if ($index === null) {
        http_response_code(404);
        exit('Пост не найден.');
    }

    // Темы/посты форума: создавать может участник, но менять описание,
    // закреплять и удалять может только владелец форума.
    if (!is_owner($u)) {
        http_response_code(403);
        exit('Только владелец форума может управлять этим постом.');
    }

    if ($action === 'pin') {
        $threads[$index]['pinned'] = empty($threads[$index]['pinned']);
        data_save('threads.json', $threads);
        log_action($threads[$index]['pinned'] ? 'Закрепление поста' : 'Открепление поста', (string)$id);
        header('Location: thread.php?id=' . $id);
        exit;
    }

    if ($action === 'delete') {
        array_splice($threads, $index, 1);
        data_save('threads.json', $threads);
        log_action('Удаление поста', (string)$id);
        header('Location: index.php');
        exit;
    }

    if ($action === 'edit') {
        $threads[$index]['title'] = clean_text($_POST['title'] ?? $threads[$index]['title'], 120);
        $threads[$index]['content'] = clean_text($_POST['content'] ?? $threads[$index]['content'], 20000);
        $threads[$index]['updated_at'] = date('c');
        data_save('threads.json', $threads);
        log_action('Изменение поста', (string)$id);
        header('Location: thread.php?id=' . $id);
        exit;
    }
}

if ($type === 'reply') {
    $threadId = (int)($_POST['thread_id'] ?? $id);
    $replyId = (int)($_POST['reply_id'] ?? 0);
    $file = 'replies_' . $threadId . '.json';
    $replies = data_load($file);
    $index = null;

    foreach ($replies as $key => $reply) {
        if ((int)($reply['id'] ?? 0) === $replyId) {
            $index = $key;
            break;
        }
    }
    if ($index === null) {
        http_response_code(404);
        exit('Комментарий не найден.');
    }

    // Свой комментарий можно изменять/удалять. Владелец форума может модерировать любой.
    $own = (int)($replies[$index]['author_id'] ?? 0) === (int)$u['id'];
    if (!$own && !is_owner($u)) {
        http_response_code(403);
        exit('Нет прав.');
    }

    if ($action === 'delete') {
        array_splice($replies, $index, 1);
        data_save($file, $replies);
        log_action('Удаление комментария', (string)$replyId);
    } elseif ($action === 'edit') {
        $replies[$index]['message'] = clean_text($_POST['message'] ?? $replies[$index]['message'], 20000);
        $replies[$index]['updated_at'] = date('c');
        data_save($file, $replies);
        log_action('Изменение комментария', (string)$replyId);
    }

    header('Location: thread.php?id=' . $threadId . '#reply-' . $replyId);
    exit;
}

http_response_code(400);
exit('Неизвестное действие.');
