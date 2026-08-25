<?php

declare(strict_types=1);

require_once __DIR__ . '/config.php';

$title = 'GREFFRLEND Forum';
$description = 'Официальный форум GREFFRLEND — Minecraft, новости, помощь и общение.';

$categories = data_load('categories.json');
$threads = data_load('threads.json');
$banner = data_load('banner.json', [
    'enabled' => false,
    'title' => '',
    'text' => '',
    'image' => '',
    'url' => ''
]);

$currentUser = current_user();

/**
 * Возвращает количество тем в категории.
 */
function categoryThreadCount(array $threads, int $categoryId): int
{
    $count = 0;

    foreach ($threads as $thread) {
        $threadCategoryId = (int)($thread['category_id'] ?? 0);

        if ($threadCategoryId === $categoryId) {
            $count++;
        }
    }

    return $count;
}

/**
 * Возвращает количество комментариев темы.
 * Комментарии хранятся в отдельных JSON-файлах.
 */
function threadReplyCount(int $threadId): int
{
    $replies = data_load('replies_' . $threadId . '.json');

    return count($replies);
}

/**
 * Возвращает количество лайков темы.
 */
function threadLikeCount(int $threadId): int
{
    $likes = data_load('likes_thread_' . $threadId . '.json');

    return count($likes);
}

/**
 * Проверяет, закреплена ли тема.
 */
function isThreadPinned(array $thread): bool
{
    return !empty($thread['pinned']);
}

/**
 * Безопасно возвращает дату публикации.
 */
function threadDate(array $thread): string
{
    $date = trim((string)($thread['created_at'] ?? ''));

    if ($date === '') {
        return 'Дата неизвестна';
    }

    return $date;
}

/**
 * Сортируем темы так, чтобы закреплённые были выше остальных.
 * Внутри каждой группы сохраняется порядок по времени.
 */
usort($threads, static function (array $a, array $b): int {
    $aPinned = !empty($a['pinned']);
    $bPinned = !empty($b['pinned']);

    if ($aPinned !== $bPinned) {
        return $aPinned ? -1 : 1;
    }

    $aDate = strtotime((string)($a['created_at'] ?? '')) ?: 0;
    $bDate = strtotime((string)($b['created_at'] ?? '')) ?: 0;

    return $bDate <=> $aDate;
});

$latestThreads = array_slice($threads, 0, 10);

$recommendations = data_load('recommendations.json');

usort($recommendations, static function (array $a, array $b): int {
    $aPinned = !empty($a['pinned']);
    $bPinned = !empty($b['pinned']);

    if ($aPinned !== $bPinned) {
        return $aPinned ? -1 : 1;
    }

    $aScore = (int)($a['score'] ?? 0);
    $bScore = (int)($b['score'] ?? 0);

    return $bScore <=> $aScore;
});

$recommendations = array_slice($recommendations, 0, 5);

include __DIR__ . '/includes/header.php';
?>

<section class="hero">
    <div class="eyebrow">ОФИЦИАЛЬНЫЙ ФОРУМ</div>

    <h1>
        GREFFRLEND<br>
        <span>COMMUNITY</span>
    </h1>

    <p class="muted">
        Minecraft, новости проекта, помощь и общение.
    </p>

    <div class="hero-actions">
        <a class="btn" href="recommendations.php">
            Смотреть рекомендации
        </a>

        <a class="btn secondary" href="search.php">
            Поиск по форуму
        </a>
    </div>
</section>

<?php if (!empty($banner['enabled'])): ?>
    <section class="billboard">
        <div class="category">РЕКЛАМА</div>

        <?php if (!empty($banner['image'])): ?>
            <div class="advert-image">
                <img
                    src="<?= e((string)$banner['image']) ?>"
                    alt="Рекламный баннер"
                >
            </div>
        <?php endif; ?>

        <?php if (!empty($banner['title'])): ?>
            <h2>
                <?= e((string)$banner['title']) ?>
            </h2>
        <?php endif; ?>

        <?php if (!empty($banner['text'])): ?>
            <p>
                <?= nl2br(e((string)$banner['text'])) ?>
            </p>
        <?php endif; ?>

        <?php if (!empty($banner['url'])): ?>
            <a
                class="btn"
                href="<?= e((string)$banner['url']) ?>"
                rel="nofollow sponsored"
                target="_blank"
            >
                Подробнее
            </a>
        <?php endif; ?>
    </section>
<?php endif; ?>

<section class="forum-section">
    <div class="section-heading">
        <div>
            <div class="category">FORUM</div>
            <h2>Разделы форума</h2>
        </div>

        <a href="search.php" class="section-link">
            Найти тему →
        </a>
    </div>

    <?php if (!$categories): ?>
        <div class="card muted">
            Разделов пока нет.
        </div>
    <?php else: ?>
        <div class="category-list">
            <?php foreach ($categories as $category): ?>
                <?php
                $categoryId = (int)($category['id'] ?? 0);
                $categoryTitle = (string)(
                    $category['title']
                    ?? $category['name']
                    ?? 'Без названия'
                );
                $categoryDescription = (string)(
                    $category['description'] ?? ''
                );
                $categoryCount = categoryThreadCount(
                    $threads,
                    $categoryId
                );
                ?>

                <article class="card category-card">
                    <div class="category-icon">
                        💬
                    </div>

                    <div class="category-content">
                        <div class="category">
                            РАЗДЕЛ
                        </div>

                        <h3>
                            <a href="forum.php?id=<?= $categoryId ?>">
                                <?= e($categoryTitle) ?>
                            </a>
                        </h3>

                        <?php if ($categoryDescription !== ''): ?>
                            <p class="muted">
                                <?= e($categoryDescription) ?>
                            </p>
                        <?php endif; ?>
                    </div>

                    <div class="category-stats">
                        <strong>
                            <?= $categoryCount ?>
                        </strong>
                        <span>тем</span>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>

<section class="forum-section">
    <div class="section-heading">
        <div>
            <div class="category">ACTIVITY</div>
            <h2>Последние обсуждения</h2>
        </div>

        <?php if ($currentUser): ?>
            <a href="create-thread.php" class="btn small">
                Новая тема
            </a>
        <?php endif; ?>
    </div>

    <?php if (!$latestThreads): ?>
        <div class="card empty-state">
            <div class="empty-icon">💬</div>
            <h3>Пока нет тем</h3>
            <p class="muted">
                Станьте первым участником, который создаст обсуждение.
            </p>

            <?php if ($currentUser): ?>
                <a class="btn" href="create-thread.php">
                    Создать тему
                </a>
            <?php else: ?>
                <a class="btn" href="register.php">
                    Зарегистрироваться
                </a>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <div class="thread-list">
            <?php foreach ($latestThreads as $thread): ?>
                <?php
                $threadId = (int)($thread['id'] ?? 0);
                $threadTitle = (string)(
                    $thread['title'] ?? 'Без названия'
                );
                $threadAuthor = (string)(
                    $thread['author'] ?? 'Неизвестный'
                );
                $replyCount = threadReplyCount($threadId);
                $likeCount = threadLikeCount($threadId);
                $pinned = isThreadPinned($thread);
                ?>

                <article class="card thread-card">
                    <div class="thread-main">
                        <?php if ($pinned): ?>
                            <div class="pin">
                                📌 Закреплено владельцем
                            </div>
                        <?php endif; ?>

                        <h3>
                            <a href="thread.php?id=<?= $threadId ?>">
                                <?= e($threadTitle) ?>
                            </a>
                        </h3>

                        <div class="thread-meta">
                            <span>
                                <?= e($threadAuthor) ?>
                            </span>

                            <span>•</span>

                            <span>
                                <?= e(threadDate($thread)) ?>
                            </span>
                        </div>
                    </div>

                    <div class="thread-stats">
                        <div>
                            💬 <?= $replyCount ?>
                        </div>

                        <div>
                            ❤️ <?= $likeCount ?>
                        </div>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>

<section class="forum-section recommendations-preview">
    <div class="section-heading">
        <div>
            <div class="category">DISCOVER</div>
            <h2>Рекомендации</h2>
        </div>

        <a href="recommendations.php" class="section-link">
            Все рекомендации →
        </a>
    </div>

    <?php if (!$recommendations): ?>
        <div class="card muted">
            Рекомендаций пока нет.
        </div>
    <?php else: ?>
        <div class="recommendation-list">
            <?php foreach ($recommendations as $recommendation): ?>
                <?php
                $recommendationTitle = (string)(
                    $recommendation['title'] ?? 'Рекомендация'
                );
                $recommendationText = (string)(
                    $recommendation['text'] ?? ''
                );
                $recommendationUrl = (string)(
                    $recommendation['url'] ?? '#'
                );
                $recommendationPinned = !empty(
                    $recommendation['pinned']
                );
                $recommendationScore = (int)(
                    $recommendation['score'] ?? 0
                );
                ?>

                <article class="card recommendation-card">
                    <div class="recommendation-icon">
                        <?= $recommendationPinned ? '📌' : '⭐' ?>
                    </div>

                    <div class="recommendation-body">
                        <?php if ($recommendationPinned): ?>
                            <span class="pin-label">
                                Закреплено владельцем
                            </span>
                        <?php endif; ?>

                        <h3>
                            <a href="<?= e($recommendationUrl) ?>">
                                <?= e($recommendationTitle) ?>
                            </a>
                        </h3>

                        <?php if ($recommendationText !== ''): ?>
                            <p class="muted">
                                <?= e($recommendationText) ?>
                            </p>
                        <?php endif; ?>
                    </div>

                    <div class="recommendation-score">
                        ❤️ <?= $recommendationScore ?>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>

<section class="community-info">
    <div class="card info-card">
        <div class="info-icon">🎮</div>
        <h3>Minecraft</h3>
        <p class="muted">
            Обсуждайте сервер, игровые события, постройки,
            модификации и технические вопросы.
        </p>
    </div>

    <div class="card info-card">
        <div class="info-icon">🤝</div>
        <h3>Сообщество</h3>
        <p class="muted">
            Общайтесь с другими участниками и делитесь
            интересными материалами.
        </p>
    </div>

    <div class="card info-card">
        <div class="info-icon">🛡️</div>
        <h3>Безопасность</h3>
        <p class="muted">
            На форуме действуют правила сообщества,
            антиспам и система модерации.
        </p>
    </div>
</section>

<?php include __DIR__ . '/includes/footer.php'; ?>
