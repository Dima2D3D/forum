<?php
require __DIR__ . '/config.php';

$pdo = db();
$me = user();
$page = $_GET['page'] ?? 'home';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_ok()) { http_response_code(403); exit('Invalid CSRF token'); }
    $action = $_POST['action'] ?? '';

    if ($action === 'logout') {
        $_SESSION = [];
        session_destroy();
        header('Location: /'); exit;
    }

    if ($action === 'register') {
        $username = trim($_POST['username'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        if (!preg_match('/^[A-Za-z0-9_\-]{3,24}$/', $username)) $error = 'Ник: 3–24 символа, только латиница, цифры, _ и -.';
        elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) $error = 'Укажите корректный E-mail.';
        elseif (strlen($password) < 8) $error = 'Пароль должен содержать минимум 8 символов.';
        else {
            try {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $pdo->prepare('INSERT INTO users(username,email,password_hash,created_at) VALUES(?,?,?,?)')->execute([$username,$email,$hash,time()]);
                $id = (int)$pdo->lastInsertId();
                $u = $pdo->query('SELECT * FROM users WHERE id=' . $id)->fetch();
                send_verify_email($u);
                $_SESSION['user_id'] = $id;
                header('Location: /?page=verify-needed'); exit;
            } catch (PDOException $ex) { $error = 'Такой ник или E-mail уже используется.'; }
        }
        $page = 'register';
    }

    if ($action === 'login') {
        $login = trim($_POST['login'] ?? '');
        $password = $_POST['password'] ?? '';
        $st = $pdo->prepare('SELECT * FROM users WHERE username = ? OR email = ? LIMIT 1');
        $st->execute([$login,$login]); $u = $st->fetch();
        if (!$u || !$u['password_hash'] || !password_verify($password,$u['password_hash'])) $error = 'Неверный логин или пароль.';
        elseif (is_banned($u)) { $_SESSION['user_id']=$u['id']; header('Location: /banned.php'); exit; }
        else { $_SESSION['user_id']=$u['id']; header('Location: /'); exit; }
        $page = 'login';
    }

    if ($action === 'create_thread') {
        $me = require_login(); require_verified($me);
        $title = trim($_POST['title'] ?? ''); $body = trim($_POST['body'] ?? '');
        if ($title === '' || $body === '') $error = 'Заполните заголовок и текст.';
        elseif (mb_strlen($title) > 120 || mb_strlen($body) > 20000) $error = 'Текст слишком большой.';
        else { $pdo->prepare('INSERT INTO threads(title,body,user_id,created_at) VALUES(?,?,?,?)')->execute([$title,$body,$me['id'],time()]); header('Location: /'); exit; }
        $page = 'new-thread';
    }

    if ($action === 'reply') {
        $me = require_login(); require_verified($me);
        $tid=(int)($_POST['thread_id']??0); $body=trim($_POST['body']??'');
        $st=$pdo->prepare('SELECT * FROM threads WHERE id=?'); $st->execute([$tid]); $thread=$st->fetch();
        if (!$thread || $thread['locked']) $error='Тема закрыта или не найдена.';
        elseif ($body==='') $error='Введите сообщение.';
        else { $pdo->prepare('INSERT INTO replies(thread_id,user_id,body,created_at) VALUES(?,?,?,?)')->execute([$tid,$me['id'],$body,time()]); header('Location: /?page=thread&id='.$tid); exit; }
        $page='thread';
    }
}

function layout_start(string $title, ?array $me): void { ?>
<!doctype html><html lang="ru"><head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title><?=e($title)?> — GREFFRLEND Forum</title>
<meta name="description" content="Официальный форум GREFFRLEND: Minecraft, новости, обсуждения и помощь игрокам.">
<link rel="canonical" href="<?=e(SITE_URL . ($_SERVER['REQUEST_URI']==='/'?'':$_SERVER['REQUEST_URI']))?>">
<meta property="og:title" content="<?=e($title)?> — GREFFRLEND Forum"><meta property="og:site_name" content="GREFFRLEND">
<link rel="stylesheet" href="/style.css">
</head><body>
<header class="site-header"><div class="wrap nav"><a class="logo" href="/">GREFFRLEND</a><nav><a href="/">Форум</a><a href="/?page=rules">Правила</a><a href="/?page=about">О проекте</a><a href="/?page=offer">Оферта</a></nav><div class="account"><?php if($me): ?><a href="/?page=profile">@<?=e($me['username'])?></a><form method="post" class="inline"><?=csrf_field()?><input type="hidden" name="action" value="logout"><button>Выйти</button></form><?php else: ?><a href="/?page=login">Войти</a><a class="button mini" href="/?page=register">Регистрация</a><?php endif; ?></div></div></header>
<main class="wrap">
<?php }
function layout_end(): void { ?>
</main><footer class="footer"><div class="wrap footer-inner"><div class="footer-brand"><div class="footer-logo">GREFFRLEND</div><p>Официальное сообщество Minecraft-проекта.</p></div><div class="footer-links"><a href="/">Форум</a><a href="/?page=rules">Правила</a><a href="/?page=offer">Оферта</a><a href="/?page=about">О проекте</a><a href="mailto:admins@greffrlend.fun">Контакты</a></div><div class="copyright">© 2025 — 2026 GREFFRLEND<br>Все права защищены.</div></div></footer></body></html>
<?php }

if ($page === 'thread') {
    $id=(int)($_GET['id']??0); $st=$pdo->prepare('SELECT t.*,u.username FROM threads t JOIN users u ON u.id=t.user_id WHERE t.id=?'); $st->execute([$id]); $thread=$st->fetch();
    if (!$thread) { http_response_code(404); layout_start('Тема не найдена',$me); echo '<div class="empty"><h1>Тема не найдена</h1><a href="/">Вернуться на форум</a></div>'; layout_end(); exit; }
    $st=$pdo->prepare('SELECT r.*,u.username,u.role FROM replies r JOIN users u ON u.id=r.user_id WHERE r.thread_id=? ORDER BY r.id'); $st->execute([$id]); $replies=$st->fetchAll();
    layout_start($thread['title'],$me); ?>
    <div class="breadcrumbs"><a href="/">Форум</a> / <?=e($thread['title'])?></div>
    <article class="thread-card"><div class="thread-head"><div><span class="eyebrow">ОБСУЖДЕНИЕ</span><h1><?=e($thread['title'])?></h1><div class="muted">@<?=e($thread['username'])?> · <?=date('d.m.Y H:i',$thread['created_at'])?></div></div><?php if($thread['locked']): ?><span class="pill red">Закрыта</span><?php endif;?></div><div class="post-body"><?=nl2br(e($thread['body']))?></div></article>
    <?php foreach($replies as $r): ?><article class="reply"><div class="avatar"><?=e(mb_strtoupper(mb_substr($r['username'],0,1)))?></div><div><div class="reply-meta"><b><?=e($r['username'])?></b><span><?=date('d.m.Y H:i',$r['created_at'])?></span></div><div class="post-body"><?=nl2br(e($r['body']))?></div></div></article><?php endforeach; ?>
    <?php if($me && $me['email_verified'] && !$thread['locked'] && !is_banned($me)): ?><form method="post" class="composer"><?=csrf_field()?><input type="hidden" name="action" value="reply"><input type="hidden" name="thread_id" value="<?=$id?>"><textarea name="body" placeholder="Написать ответ..."></textarea><button class="button">Ответить</button></form><?php elseif(!$me): ?><div class="notice">Чтобы отвечать, <a href="/?page=login">войдите</a> или <a href="/?page=register">зарегистрируйтесь</a>.</div><?php endif; ?>
    <?php layout_end(); exit;
}

if ($page === 'login' || $page === 'register') {
    layout_start($page==='login'?'Вход':'Регистрация',$me); ?><section class="auth-card"><div class="eyebrow">GREFFRLEND ACCOUNT</div><h1><?=$page==='login'?'Добро пожаловать':'Создать аккаунт'?></h1><?php if($error): ?><div class="alert"><?=e($error)?></div><?php endif; ?>
    <?php if($page==='login'): ?><form method="post"><?=csrf_field()?><input type="hidden" name="action" value="login"><label>Логин или E-mail<input name="login" required></label><label>Пароль<input type="password" name="password" required></label><button class="button full">Войти</button><a class="google-button" href="/google-login.php">G&nbsp;&nbsp; Продолжить через Google</a><p class="muted center">Нет аккаунта? <a href="/?page=register">Регистрация</a></p></form><?php else: ?><form method="post"><?=csrf_field()?><input type="hidden" name="action" value="register"><label>Ник<input name="username" required minlength="3" maxlength="24"></label><label>E-mail<input type="email" name="email" required></label><label>Пароль<input type="password" name="password" required minlength="8"></label><label class="check"><input type="checkbox" required> Я принимаю <a href="/?page=offer">Пользовательское соглашение</a> и <a href="/?page=rules">Правила форума</a>.</label><button class="button full">Создать аккаунт</button><a class="google-button" href="/google-login.php">G&nbsp;&nbsp; Зарегистрироваться через Google</a></form><?php endif; ?></section><?php layout_end(); exit; }

if ($page === 'verify-needed') { layout_start('Подтверждение E-mail',$me); ?><div class="auth-card center"><div class="big-icon">✉</div><h1>Проверьте E-mail</h1><p>Мы отправили письмо на адрес вашего аккаунта. Подтвердите его, чтобы создавать темы и отвечать.</p><p class="muted">Письма отправляются от <b>admins@greffrlend.fun</b>.</p></div><?php layout_end(); exit; }

if ($page === 'rules' || $page === 'offer' || $page === 'about') {
    $titles=['rules'=>'Правила форума','offer'=>'Пользовательское соглашение','about'=>'О проекте']; layout_start($titles[$page],$me); ?><article class="legal"><span class="eyebrow">GREFFRLEND</span><h1><?=e($titles[$page])?></h1><?php if($page==='rules'): ?><h2>1. Общие правила</h2><p>Правила распространяются на всех пользователей. Уважайте участников и соблюдайте законодательство.</p><h2>2. Общение</h2><p>Запрещены оскорбления, травля, спам, флуд и провокации.</p><h2>3. Контент</h2><p>Запрещены вредоносные файлы, мошенничество, публикация чужих персональных данных и материалы, нарушающие права третьих лиц.</p><h2>4. Реклама</h2><p>Реклама сторонних проектов разрешена только там, где это допускается администрацией.</p><h2>5. Модерация</h2><p>За нарушения могут применяться предупреждение, удаление контента, мут, ограничение функций или бан. Мера зависит от обстоятельств и повторности нарушения.</p><?php elseif($page==='offer'): ?><h2>1. Использование форума</h2><p>Форум предоставляется для общения и обсуждения GREFFRLEND, Minecraft и связанных тем.</p><h2>2. Аккаунт</h2><p>Пользователь отвечает за сохранность своего аккаунта и достоверность предоставленного E-mail.</p><h2>3. Модерация</h2><p>Администрация вправе удалять нарушающий правила контент и ограничивать доступ к сервису в соответствии с правилами форума.</p><h2>4. Персональные данные</h2><p>Мы обрабатываем данные, необходимые для работы аккаунта и форума. Контакт администрации: admins@greffrlend.fun.</p><h2>5. Изменения</h2><p>Условия могут обновляться. Актуальная версия всегда публикуется на этой странице.</p><?php else: ?><h2>GREFFRLEND Forum</h2><p>Сообщество игроков и пользователей проекта GREFFRLEND. Гости могут свободно читать темы и ответы. Для публикации сообщений нужна регистрация и подтверждение E-mail.</p><p>Наш сервер: <b>play.greffrlend.fun</b><br>Форум: <b>forum.greffrlend.fun</b></p><?php endif; ?></article><?php layout_end(); exit; }

if ($page === 'new-thread') { $me=require_login(); require_verified($me); layout_start('Новая тема',$me); ?><section class="composer-card"><span class="eyebrow">НОВАЯ ТЕМА</span><h1>Создать обсуждение</h1><?php if($error): ?><div class="alert"><?=e($error)?></div><?php endif; ?><form method="post"><?=csrf_field()?><input type="hidden" name="action" value="create_thread"><label>Заголовок<input name="title" maxlength="120" required></label><label>Сообщение<textarea name="body" maxlength="20000" required></textarea></label><button class="button">Опубликовать</button></form></section><?php layout_end(); exit; }

layout_start('Главная',$me);
$threads=$pdo->query('SELECT t.*,u.username,(SELECT COUNT(*) FROM replies r WHERE r.thread_id=t.id) replies FROM threads t JOIN users u ON u.id=t.user_id ORDER BY t.pinned DESC,t.id DESC')->fetchAll();
$online=0;
?>
<section class="hero"><div><span class="eyebrow">ОФИЦИАЛЬНЫЙ ФОРУМ</span><h1>Сообщество <span>GREFFRLEND</span></h1><p>Обсуждения Minecraft, новости проекта, помощь игрокам и общение.</p><div class="hero-actions"><?php if($me && $me['email_verified']): ?><a class="button" href="/?page=new-thread">+ Новая тема</a><?php else: ?><a class="button" href="/?page=register">Присоединиться</a><?php endif; ?><a class="button ghost" href="/?page=rules">Правила</a></div></div></section>
<div class="billboard"><div class="ad-label">GREFFRLEND</div><div><b>Бесплатный поддомен для Minecraft-сервера</b><br><span>вашсервер.greffrlend.fun</span></div><a href="https://greffrlend.fun" target="_blank" rel="noopener">Узнать больше →</a></div>
<div class="section-head"><div><span class="eyebrow">ОБСУЖДЕНИЯ</span><h2>Последние темы</h2></div><span class="muted">Открыто для чтения</span></div>
<div class="forum-list"><?php if(!$threads): ?><div class="empty"><h3>Пока нет тем</h3><p>Станьте первым, кто начнёт обсуждение.</p></div><?php endif; foreach($threads as $t): ?><a class="thread-row" href="/?page=thread&id=<?=$t['id']?>"><div class="thread-icon">▰</div><div class="thread-info"><h3><?=e($t['title'])?></h3><p>@<?=e($t['username'])?> · <?=date('d.m.Y H:i',$t['created_at'])?></p></div><div class="thread-stats"><b><?=$t['replies']?></b><span>ответов</span></div></a><?php endforeach; ?></div>
<div class="section-grid"><div class="info-card"><span>🎮</span><h3>Наш сервер</h3><p>play.greffrlend.fun</p></div><div class="info-card"><span>💬</span><h3>Форум</h3><p>forum.greffrlend.fun</p></div><div class="info-card"><span>📧</span><h3>Администрация</h3><p>admins@greffrlend.fun</p></div></div>
<?php layout_end();
