<?php
require_once __DIR__ . '/config.php';

$title = 'GREFFRLEND Forum';
$categories = data_load('categories.json');
$threads = data_load('threads.json');
$currentUser = user();

function categoryThreadCount(array $threads, int $categoryId): int
{
    $count = 0;
    foreach ($threads as $thread) {
        if ((int)($thread['category_id'] ?? 0) === $categoryId) {
            $count++;
        }
    }
    return $count;
}

$latestThreads = array_slice(array_reverse($threads), 0, 10);
?>
<!doctype html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($title) ?></title>
    <meta name="description" content="Официальный форум GREFFRLEND — Minecraft, новости, помощь и общение.">
    <meta name="robots" content="index,follow">
    <link rel="stylesheet" href="style.css">
</head>
<body>

<header class="top">
    <div class="wrap nav">
        <a class="logo" href="index.php">GREFFRLEND</a>

        <nav>
            <a href="index.php">Главная</a>
            <a href="rules.php">Правила</a>
            <a href="offer.php">Оферта</a>

            <?php if ($currentUser): ?>
                <a href="profile.php?id=<?= (int)$currentUser['id'] ?>">
                    <?= e($currentUser['username']) ?>
                </a>

                <?php if (($currentUser['role'] ?? 'user') === 'admin'): ?>
                    <a href="admin/index.php">Админ-панель</a>
                <?php endif; ?>

                <a href="logout.php">Выйти</a>
            <?php else: ?>
                <a href="login.php">Войти</a>
                <a class="btn small" href="register.php">Регистрация</a>
            <?php endif; ?>
        </nav>
    </div>
</header>

<main class="wrap">

    <div class="projectbar">
        <span>
            Наш сервер:
            <b id="server-ip">play.greffrlend.fun</b>
        </span>

        <button
            type="button"
            onclick="copyServerIp()"
        >Копировать IP</button>

        <a href="https://forum.greffrlend.fun">Форум</a>
    </div>

    <section class="hero">
        <div class="category">ОФИЦИАЛЬНЫЙ ФОРУМ</div>

        <h1>
            GREFFRLEND<br>
            <span>COMMUNITY</span>
        </h1>

        <p class="muted">
            Minecraft, новости проекта, помощь и общение.
        </p>

        <?php if ($currentUser): ?>
            <a class="btn" href="create-thread.php">Создать тему</a>
        <?php else: ?>
            <a class="btn" href="register.php">Присоединиться</a>
        <?php endif; ?>
    </section>

    <section class="billboard">
        <strong>ПОДДОМЕНЫ GREFFRLEND</strong>

        <p>
            Получите собственный адрес для Minecraft-сервера:
            <b>ваш сервер.greffrlend.fun</b>
        </p>

        <p>
            Стоимость: <b>бесплатно</b>, но вы таким образом
            рекламируете наш бренд GREFFRLEND.
        </p>

        <p>
            Для выдачи поддомена напишите на
            <a href="mailto:admins@greffrlend.fun">
                admins@greffrlend.fun
            </a>.
        </p>

        <p>
            Поддомен сразу привязывается к Cloudflare.
        </p>
    </section>

    <section>
        <h2>Разделы форума</h2>

        <?php if (!$categories): ?>
            <div class="card muted">
                Разделов пока нет.
            </div>
        <?php else: ?>
            <?php foreach ($categories as $category): ?>
                <?php
                $categoryId = (int)($category['id'] ?? 0);
                $categoryTitle = $category['title'] ?? $category['name'] ?? 'Без названия';
                $categoryDescription = $category['description'] ?? '';
                $threadCount = categoryThreadCount($threads, $categoryId);
                ?>

                <div class="card thread">
                    <div>
                        <div class="category">РАЗДЕЛ</div>

                        <h3>
                            <a href="forum.php?id=<?= $categoryId ?>">
                                <?= e($categoryTitle) ?>
                            </a>
                        </h3>

                        <p class="muted">
                            <?= e($categoryDescription) ?>
                        </p>

                        <span class="muted">
                            Тем: <?= $threadCount ?>
                        </span>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </section>

    <section>
        <h2>Последние обсуждения</h2>

        <?php if (!$latestThreads): ?>
            <div class="card muted">
                Пока нет тем. Первую тему можно создать после регистрации.
            </div>
        <?php else: ?>
            <?php foreach ($latestThreads as $thread): ?>
                <div class="card thread">
                    <div>
                        <a href="thread.php?id=<?= (int)$thread['id'] ?>">
                            <b><?= e($thread['title'] ?? 'Без названия') ?></b>
                        </a>

                        <div class="muted">
                            <?= e($thread['author'] ?? 'Неизвестный') ?>
                            <?php if (!empty($thread['created_at'])): ?>
                                · <?= e($thread['created_at']) ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </section>

</main>

<footer class="footer">
    <div class="wrap footer-grid">
        <div>
            <div class="logo">GREFFRLEND</div>
            <p>Minecraft Community</p>
        </div>

        <div class="footer-links">
            <a href="rules.php">Правила</a>
            <a href="offer.php">Оферта</a>
            <a href="mailto:admins@greffrlend.fun">Контакты</a>
        </div>
    </div>

    <div class="copyright">
        © 2025 — 2026 GREFFRLEND. Все права защищены.
    </div>
</footer>

<script>
function copyServerIp() {
    const ip = 'play.greffrlend.fun';

    if (navigator.clipboard && window.isSecureContext) {
        navigator.clipboard.writeText(ip).then(function () {
            alert('IP скопирован: ' + ip);
        }).catch(function () {
            prompt('Скопируйте IP:', ip);
        });
        return;
    }

    prompt('Скопируйте IP:', ip);
}
</script>

</body>
</html>
