<?php
require_once __DIR__.'/../config.php';

function notifications_add(int $userId, string $type, string $text, string $url = ''): void {
    if ($userId <= 0) return;
    $items = data_load('notifications.json');
    $items[] = [
        'id' => next_id($items),
        'user_id' => $userId,
        'type' => $type,
        'text' => clean_text($text, 500),
        'url' => $url,
        'read' => false,
        'created_at' => date('c')
    ];
    data_save('notifications.json', $items);
}

function notify_mentions(string $text, int $actorId, string $url = ''): void {
    if (!preg_match_all('/@([A-Za-z0-9_]{3,24})\b/u', $text, $matches)) return;
    $wanted = array_values(array_unique(array_map('strtolower', $matches[1])));
    if (!$wanted) return;
    foreach (data_load('users.json') as $u) {
        $uid = (int)($u['id'] ?? 0);
        if ($uid <= 0 || $uid === $actorId) continue;
        $handle = strtolower((string)($u['handle'] ?? $u['username'] ?? ''));
        $username = strtolower((string)($u['username'] ?? ''));
        if (in_array($handle, $wanted, true) || in_array($username, $wanted, true)) {
            notifications_add($uid, 'mention', 'Вас упомянули в сообщении.', $url);
        }
    }
}

function following_ids(int $userId): array {
    $out = [];
    foreach (data_load('follows.json') as $r) {
        if ((int)($r['follower_id'] ?? 0) === $userId) $out[] = (int)$r['following_id'];
    }
    return array_values(array_unique($out));
}

function is_following(int $from, int $to): bool {
    foreach (data_load('follows.json') as $r) {
        if ((int)($r['follower_id'] ?? 0) === $from && (int)($r['following_id'] ?? 0) === $to) return true;
    }
    return false;
}

function follow_user(int $from, int $to): void {
    if ($from <= 0 || $to <= 0 || $from === $to || is_following($from, $to)) return;
    $rows = data_load('follows.json');
    $rows[] = ['id' => next_id($rows), 'follower_id' => $from, 'following_id' => $to, 'created_at' => date('c')];
    data_save('follows.json', $rows);
    notifications_add($to, 'follow', 'На вас подписался новый пользователь.', 'profile.php?id=' . $from);
    check_achievements($to);
    check_achievements($from);
}

function unfollow_user(int $from, int $to): void {
    $rows = data_load('follows.json');
    $rows = array_values(array_filter($rows, fn($r) => !((int)($r['follower_id'] ?? 0) === $from && (int)($r['following_id'] ?? 0) === $to)));
    data_save('follows.json', $rows);
}

function follower_count(int $id): int {
    $n = 0;
    foreach (data_load('follows.json') as $r) $n += (int)($r['following_id'] ?? 0) === $id ? 1 : 0;
    return $n;
}

function following_count(int $id): int {
    return count(following_ids($id));
}

function unread_notifications(int $id): int {
    $n = 0;
    foreach (data_load('notifications.json') as $r) $n += (int)($r['user_id'] ?? 0) === $id && empty($r['read']) ? 1 : 0;
    return $n;
}

function grant_achievement(int $userId, string $code, string $name, int $reward = 0): void {
    $rows = data_load('achievements.json');
    foreach ($rows as $r) {
        if ((int)($r['user_id'] ?? 0) === $userId && ($r['code'] ?? '') === $code) return;
    }
    $rows[] = ['id' => next_id($rows), 'user_id' => $userId, 'code' => $code, 'name' => $name, 'reward' => $reward, 'created_at' => date('c')];
    data_save('achievements.json', $rows);
    if ($reward > 0) {
        require_once __DIR__.'/economy.php';
        change_wallet($userId, $reward, 'Достижение: ' . $name, 'system');
    }
    notifications_add($userId, 'achievement', 'Получено достижение: ' . $name, 'achievements.php');
}

function user_achievements(int $id): array {
    return array_values(array_filter(data_load('achievements.json'), fn($r) => (int)($r['user_id'] ?? 0) === $id));
}

function user_thread_count(int $id): int {
    $n = 0;
    foreach (data_load('threads.json') as $t) $n += (int)($t['author_id'] ?? 0) === $id ? 1 : 0;
    return $n;
}

function user_reply_count(int $id): int {
    $n = 0;
    foreach (glob(DATA_DIR . 'replies_*.json') ?: [] as $path) {
        $rows = json_decode((string)@file_get_contents($path), true);
        if (!is_array($rows)) continue;
        foreach ($rows as $r) $n += (int)($r['author_id'] ?? 0) === $id ? 1 : 0;
    }
    return $n;
}

function user_received_like_count(int $id): int {
    $n = 0;

    foreach (glob(DATA_DIR . 'likes_thread_*.json') ?: [] as $path) {
        $name = basename($path);
        if (!preg_match('/^likes_thread_(\d+)\.json$/', $name, $m)) continue;
        $threadId = (int)$m[1];

        foreach (data_load('threads.json') as $t) {
            if ((int)($t['id'] ?? 0) === $threadId && (int)($t['author_id'] ?? 0) === $id) {
                $n += count(data_load($name));
                break;
            }
        }
    }

    foreach (glob(DATA_DIR . 'likes_reply_*.json') ?: [] as $path) {
        $name = basename($path);
        if (!preg_match('/^likes_reply_(\d+)_(\d+)\.json$/', $name, $m)) continue;
        $threadId = (int)$m[1];
        $replyId = (int)$m[2];

        $rows = data_load('replies_' . $threadId . '.json');
        foreach ($rows as $r) {
            if ((int)($r['id'] ?? 0) === $replyId && (int)($r['author_id'] ?? 0) === $id) {
                $n += count(data_load($name));
                break;
            }
        }
    }

    return $n;
}

function user_gift_count(int $id): int {
    $n = 0;
    foreach (data_load('gift_logs.json') as $g) $n += (int)($g['to'] ?? 0) === $id ? 1 : 0;
    return $n;
}

function check_achievements(int $userId): void {
    if ($userId <= 0) return;
    $threads = user_thread_count($userId);
    $replies = user_reply_count($userId);
    $followers = follower_count($userId);
    $likes = user_received_like_count($userId);
    $gifts = user_gift_count($userId);
    $defs = [
        [$threads >= 1, 'first_post', 'Первый пост', 25],
        [$threads >= 10, 'posts_10', '10 постов', 100],
        [$replies >= 1, 'first_comment', 'Первый комментарий', 25],
        [$replies >= 10, 'comments_10', '10 комментариев', 100],
        [$followers >= 1, 'first_follower', 'Первый подписчик', 25],
        [$followers >= 10, 'followers_10', '10 подписчиков', 250],
        [$likes >= 1, 'first_like', 'Получен первый лайк', 25],
        [$likes >= 10, 'likes_10', 'Получено 10 лайков', 100],
        [$gifts >= 1, 'first_gift', 'Получен первый подарок', 50],
        [$gifts >= 10, 'gifts_10', 'Получено 10 подарков', 250]
    ];
    foreach ($defs as [$condition, $code, $name, $reward]) if ($condition) grant_achievement($userId, $code, $name, $reward);
}

function report_content(int $userId, string $type, int $target, string $reason): void {
    $rows = data_load('reports.json');
    $rows[] = ['id' => next_id($rows), 'user_id' => $userId, 'type' => $type, 'target_id' => $target, 'reason' => clean_text($reason, 1000), 'status' => 'new', 'created_at' => date('c')];
    data_save('reports.json', $rows);
}
