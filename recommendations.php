<?php
require_once __DIR__ . '/config.php';

$title = 'Рекомендации — GREFFRLEND';
$threads = data_load('threads.json');
$recommendations = data_load('recommendations.json');

$byId = [];
foreach ($threads as $thread) {
    $threadId = (int)($thread['id'] ?? 0);
    if ($threadId > 0) {
        $byId[$threadId] = $thread;
    }
}

$items = [];
foreach ($recommendations as $recommendation) {
    $threadId = (int)($recommendation['thread_id'] ?? $recommendation['post_id'] ?? 0);
    if ($threadId > 0 && isset($byId[$threadId])) {
        $thread = $byId[$threadId];
        $thread['_pinned_recommendation'] = !empty($recommendation['pinned']);
        $thread['_score'] = (int)($recommendation['score'] ?? 0);
        $items[] = $thread;
    }
}

// Если владелец ещё ничего не закрепил, страница всё равно работает:
// автоматически рассчитываем популярные посты по лайкам, ответам и закреплению.
if (!$items) {
    foreach ($threads as $thread) {
        $threadId = (int)($thread['id'] ?? 0);
        if ($threadId <= 0) {
            continue;
        }
        $likes = count(data_load('likes_thread_' . $threadId . '.json'));
        $replies = count(data_load('replies_' . $threadId . '.json'));
        $pinned = !empty($thread['pinned']) ? 20 : 0;
        $thread['_pinned_recommendation'] = false;
        $thread['_score'] = $likes * 3 + $replies * 2 + $pinned;
        $items[] = $thread;
    }
}

usort($items, static function (array $a, array $b): int {
    if (!empty($a['_pinned_recommendation']) !== !empty($b['_pinned_recommendation'])) {
        return !empty($a['_pinned_recommendation']) ? -1 : 1;
    }

    $scoreCompare = ((int)($b['_score'] ?? 0)) <=> ((int)($a['_score'] ?? 0));
    if ($scoreCompare !== 0) {
        return $scoreCompare;
    }

    return (strtotime((string)($b['created_at'] ?? '')) ?: 0)
        <=> (strtotime((string)($a['created_at'] ?? '')) ?: 0);
});

$items = array_slice($items, 0, 20);

include __DIR__ . '/includes/header.php';
?>
<section class="hero">
    <div class="eyebrow">GREFFRLEND</div>
    <h1>Рекомендации</h1>
    <p class="muted">Популярные и интересные посты сообщества.</p>
    <div class="hero-actions">
        <?php if (current_user()): ?>
            <a class="btn" href="create-thread.php">+ Создать пост</a>
        <?php endif; ?>
        <a class="btn secondary" href="search.php">Поиск</a>
    </div>
</section>

<section class="forum-section">
    <div class="section-heading">
        <div>
            <div class="category">ЛЕНТА</div>
            <h2>Для вас</h2>
        </div>
    </div>

    <?php if (!$items): ?>
        <div class="card muted">Постов пока нет.</div>
    <?php endif; ?>

    <?php foreach ($items as $thread): ?>
        <article class="card recommendation <?= !empty($thread['_pinned_recommendation']) ? 'pinned' : '' ?>">
            <?php if (!empty($thread['_pinned_recommendation'])): ?>
                <div class="category">📌 ЗАКРЕПЛЕНО В РЕКОМЕНДАЦИЯХ</div>
            <?php else: ?>
                <div class="category">⭐ РЕКОМЕНДАЦИЯ</div>
            <?php endif; ?>
            <h2>
                <a href="thread.php?id=<?= (int)$thread['id'] ?>">
                    <?= e((string)($thread['title'] ?? 'Без названия')) ?>
                </a>
            </h2>
            <p><?= e(mb_substr((string)($thread['content'] ?? ''), 0, 280)) ?></p>
            <div class="muted">
                <?= e((string)($thread['author'] ?? 'Неизвестно')) ?>
                · рейтинг <?= (int)($thread['_score'] ?? 0) ?>
            </div>
        </article>
    <?php endforeach; ?>
</section>
<?php include __DIR__ . '/includes/footer.php'; ?>