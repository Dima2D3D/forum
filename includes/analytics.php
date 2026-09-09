<?php
require_once __DIR__ . '/../config.php';

function analytics_is_bot(): bool {
    $ua = strtolower((string)($_SERVER['HTTP_USER_AGENT'] ?? ''));
    if ($ua === '') return true;
    foreach (['bot','crawler','spider','slurp','bingpreview','facebookexternalhit','monitor','uptimerobot'] as $needle) {
        if (strpos($ua, $needle) !== false) return true;
    }
    return false;
}

function analytics_path(): string {
    $path = parse_url((string)($_SERVER['REQUEST_URI'] ?? '/'), PHP_URL_PATH);
    return is_string($path) && $path !== '' ? $path : '/';
}

function analytics_lock_update(string $file, callable $callback, array $default = []): array {
    $path = DATA_DIR . basename($file);
    $fp = @fopen($path, 'c+');
    if (!$fp || !@flock($fp, LOCK_EX)) {
        if ($fp) @fclose($fp);
        return $default;
    }
    rewind($fp);
    $raw = @stream_get_contents($fp);
    $data = json_decode((string)$raw, true);
    if (!is_array($data)) $data = $default;
    try { $data = $callback($data); } catch (Throwable $e) { $data = $default; }
    @ftruncate($fp, 0);
    @rewind($fp);
    @fwrite($fp, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    @fflush($fp);
    @flock($fp, LOCK_UN);
    @fclose($fp);
    return $data;
}

function analytics_track(): void {
    if (analytics_is_bot()) return;
    $path = analytics_path();
    if (strpos($path, '/admin/') !== false) return;

    $today = date('Y-m-d');
    $visitor = hash('sha256', session_id() . '|' . ($_SERVER['HTTP_USER_AGENT'] ?? ''));
    $now = time();
    $username = '';
    $user = current_user();
    if (is_array($user)) $username = (string)($user['username'] ?? '');

    analytics_lock_update('analytics_daily.json', function(array $days) use ($today,$visitor,$path): array {
        if (!isset($days[$today]) || !is_array($days[$today])) $days[$today] = ['views'=>0,'unique'=>[],'pages'=>[]];
        $days[$today]['views'] = (int)($days[$today]['views'] ?? 0) + 1;
        $unique = (array)($days[$today]['unique'] ?? []);
        if (!in_array($visitor, $unique, true)) $unique[] = $visitor;
        $days[$today]['unique'] = $unique;
        $pages = (array)($days[$today]['pages'] ?? []);
        $pages[$path] = (int)($pages[$path] ?? 0) + 1;
        $days[$today]['pages'] = $pages;
        $cutoff = strtotime('-120 days');
        foreach (array_keys($days) as $date) {
            $stamp = strtotime($date);
            if ($stamp !== false && $stamp < $cutoff) unset($days[$date]);
        }
        return $days;
    });

    analytics_lock_update('analytics_online.json', function(array $online) use ($visitor,$now,$path,$username): array {
        $online[$visitor] = ['last_seen'=>$now,'path'=>$path,'username'=>$username];
        foreach ($online as $id=>$row) if ((int)($row['last_seen'] ?? 0) < $now-300) unset($online[$id]);
        return $online;
    });
}

function analytics_summary(int $days = 30): array {
    $all = data_load('analytics_daily.json');
    $result = [];
    $days = max(1, min(120, $days));
    $start = strtotime('-'.($days-1).' days');
    for ($i=0; $i<$days; $i++) {
        $date = date('Y-m-d',$start+$i*86400);
        $row = isset($all[$date]) && is_array($all[$date]) ? $all[$date] : [];
        $result[$date] = ['views'=>(int)($row['views'] ?? 0),'unique'=>count(array_unique((array)($row['unique'] ?? []))),'pages'=>(array)($row['pages'] ?? [])];
    }
    return $result;
}

function analytics_online_count(): int {
    $online = data_load('analytics_online.json');
    $now = time(); $count = 0;
    foreach ($online as $row) if (is_array($row) && (int)($row['last_seen'] ?? 0) >= $now-300) $count++;
    return $count;
}

function analytics_top_pages(int $days = 30, int $limit = 10): array {
    $pages = [];
    foreach (analytics_summary($days) as $row) foreach ($row['pages'] as $path=>$count) $pages[$path] = (int)($pages[$path] ?? 0) + (int)$count;
    arsort($pages);
    return array_slice($pages,0,$limit,true);
}
