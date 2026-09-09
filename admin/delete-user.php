<?php
require_once __DIR__.'/../config.php';
$me = require_owner();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: users.php');
    exit;
}
check_csrf();
$targetId = (int)($_POST['user_id'] ?? 0);
if ($targetId <= 0 || $targetId === (int)$me['id']) {
    exit('Нельзя удалить текущий аккаунт владельца.');
}

$users = data_load('users.json');
$target = null;
foreach ($users as $u) if ((int)($u['id'] ?? 0) === $targetId) { $target = $u; break; }
if (!$target) exit('Пользователь не найден.');
if (is_owner($target)) exit('Аккаунт владельца защищён.');

$users = array_values(array_filter($users, fn($u) => (int)($u['id'] ?? 0) !== $targetId));
data_save('users.json', $users);

foreach (['follows.json', 'notifications.json', 'achievements.json', 'wallets.json', 'subscriptions.json'] as $file) {
    $rows = data_load($file);
    if ($file === 'follows.json') {
        $rows = array_values(array_filter($rows, fn($r) => (int)($r['follower_id'] ?? 0) !== $targetId && (int)($r['following_id'] ?? 0) !== $targetId));
    } else {
        $key = $file === 'notifications.json' ? 'user_id' : ($file === 'achievements.json' ? 'user_id' : ($file === 'wallets.json' ? 'user_id' : 'user_id'));
        $rows = array_values(array_filter($rows, fn($r) => (int)($r[$key] ?? 0) !== $targetId));
    }
    data_save($file, $rows);
}

$threads = data_load('threads.json');
$threads = array_values(array_filter($threads, fn($t) => (int)($t['author_id'] ?? 0) !== $targetId));
data_save('threads.json', $threads);

foreach (glob(DATA_DIR . 'replies_*.json') ?: [] as $path) {
    $rows = json_decode((string)@file_get_contents($path), true);
    if (!is_array($rows)) continue;
    $newRows = array_values(array_filter($rows, fn($r) => (int)($r['author_id'] ?? 0) !== $targetId));
    data_save(basename($path), $newRows);
}

log_action('Удаление аккаунта', (string)($target['username'] ?? $targetId), 'Аккаунт удалён владельцем');
header('Location: users.php?deleted=1');
exit;
