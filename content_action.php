<?php

declare(strict_types=1);

/*
 * GREFFRLEND — действия над постами и комментариями.
 *
 * Важно:
 * - обычный автор может изменять только свой пост/комментарий;
 * - владелец форума может изменять любой пост/комментарий;
 * - удалять посты и закреплять/откреплять посты может только владелец;
 * - все действия выполняются только через POST + CSRF.
 */

require_once __DIR__ . '/config.php';

$method = strtoupper((string)($_SERVER['REQUEST_METHOD'] ?? 'GET'));

if ($method !== 'POST') {
    header('Location: index.php');
    exit;
}

$u = require_login();
check_csrf();

$type   = trim((string)($_POST['type'] ?? ''));
$action = trim((string)($_POST['action'] ?? ''));

/*
 * Для поста идентификатор приходит в поле id.
 * Для комментария форма использует reply_id, поэтому нельзя
 * проверять только $_POST['id'] до определения типа содержимого.
 */
$id = (int)($_POST['id'] ?? $_POST['reply_id'] ?? 0);

if ($id < 1) {
    http_response_code(400);
    exit('Некорректный идентификатор.');
}

/* Безопасный переход обратно на пост. */
function content_redirect(int $threadId, ?int $replyId = null): void
{
    $url = 'thread.php?id=' . $threadId;

    if ($replyId !== null && $replyId > 0) {
        $url .= '#reply-' . $replyId;
    }

    header('Location: ' . $url);
    exit;
}

/*
 * Работа с постами.
 */
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

    $thread = $threads[$index];
    $authorId = (int)($thread['author_id'] ?? 0);
    $isAuthor = $authorId === (int)$u['id'];
    $isOwner = is_owner($u);

    /* Изменение поста: автор или владелец. */
    if ($action === 'edit') {
        if (!$isAuthor && !$isOwner) {
            http_response_code(403);
            exit('Нет прав на изменение этого поста.');
        }

        $title = clean_text(
            (string)($_POST['title'] ?? $thread['title'] ?? ''),
            120
        );

        $content = clean_text(
            (string)($_POST['content'] ?? $thread['content'] ?? ''),
            20000
        );

        if ($title === '') {
            http_response_code(400);
            exit('Заголовок не может быть пустым.');
        }

        if ($content === '') {
            http_response_code(400);
            exit('Текст поста не может быть пустым.');
        }

        $threads[$index]['title'] = $title;
        $threads[$index]['content'] = $content;
        $threads[$index]['updated_at'] = date('c');
        $threads[$index]['edited_by'] = (int)$u['id'];

        data_save('threads.json', $threads);
        log_action('Изменение поста', (string)$id);

        content_redirect($id);
    }

    /* Закрепление и открепление — только владелец форума. */
    if ($action === 'pin' || $action === 'unpin') {
        if (!$isOwner) {
            http_response_code(403);
            exit('Только владелец форума может закреплять посты.');
        }

        $newState = $action === 'pin';
        $threads[$index]['pinned'] = $newState;
        $threads[$index]['pinned_at'] = $newState ? date('c') : null;
        $threads[$index]['pinned_by'] = $newState ? (int)$u['id'] : null;

        data_save('threads.json', $threads);

        log_action(
            $newState ? 'Закрепление поста' : 'Открепление поста',
            (string)$id
        );

        content_redirect($id);
    }

    /* Удаление поста — только владелец форума. */
    if ($action === 'delete') {
        if (!$isOwner) {
            http_response_code(403);
            exit('Только владелец форума может удалять посты.');
        }

        array_splice($threads, $index, 1);
        data_save('threads.json', $threads);

        /* Удаляем связанные реакции, если они существуют. */
        $relatedFiles = [
            'likes_thread_' . $id . '.json',
            'replies_' . $id . '.json',
        ];

        foreach ($relatedFiles as $relatedFile) {
            $path = DATA_DIR . basename($relatedFile);
            if (is_file($path)) {
                @unlink($path);
            }
        }

        log_action('Удаление поста', (string)$id);

        header('Location: index.php');
        exit;
    }

    http_response_code(400);
    exit('Неизвестное действие для поста.');
}

/*
 * Работа с комментариями.
 */
if ($type === 'reply') {
    $threadId = (int)($_POST['thread_id'] ?? 0);
    $replyId  = (int)($_POST['reply_id'] ?? $id);

    if ($threadId < 1 || $replyId < 1) {
        http_response_code(400);
        exit('Некорректные данные комментария.');
    }

    /* Проверяем, что родительский пост существует. */
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

    $reply = $replies[$index];
    $authorId = (int)($reply['author_id'] ?? 0);
    $isAuthor = $authorId === (int)$u['id'];
    $isOwner = is_owner($u);

    /* Изменение комментария: автор или владелец. */
    if ($action === 'edit') {
        if (!$isAuthor && !$isOwner) {
            http_response_code(403);
            exit('Нет прав на изменение этого комментария.');
        }

        $message = clean_text(
            (string)($_POST['message'] ?? $reply['message'] ?? ''),
            20000
        );

        if ($message === '') {
            http_response_code(400);
            exit('Комментарий не может быть пустым.');
        }

        $replies[$index]['message'] = $message;
        $replies[$index]['updated_at'] = date('c');
        $replies[$index]['edited_by'] = (int)$u['id'];

        data_save($file, $replies);
        log_action('Изменение комментария', (string)$replyId);

        content_redirect($threadId, $replyId);
    }

    /* Удаление комментария: автор или владелец. */
    if ($action === 'delete') {
        if (!$isAuthor && !$isOwner) {
            http_response_code(403);
            exit('Нет прав на удаление этого комментария.');
        }

        /*
         * Сначала удаляем комментарий. Ответы на него не теряем:
         * они остаются в файле и смогут быть показаны как ответы
         * на удалённый комментарий после дополнительной обработки.
         */
        array_splice($replies, $index, 1);
        data_save($file, $replies);

        /* Удаляем файл лайков именно этого комментария. */
        $likesFile = DATA_DIR . 'likes_reply_' . $replyId . '.json';
        if (is_file($likesFile)) {
            @unlink($likesFile);
        }

        log_action('Удаление комментария', (string)$replyId);

        content_redirect($threadId);
    }

    http_response_code(400);
    exit('Неизвестное действие для комментария.');
}

http_response_code(400);
exit('Неизвестный тип содержимого.');
