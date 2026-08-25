<?php require_once __DIR__.'/../config.php'; $me=current_user(); $title=$title??SITE_NAME; ?>
<!doctype html><html lang="ru"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title><?=e($title)?></title><meta name="description" content="Официальный форум GREFFRLEND"><meta name="robots" content="index,follow"><link rel="stylesheet" href="style.css"></head><body>
<header class="site-header"><div class="header-inner">
<a class="logo" href="index.php">GREFFRLEND</a>
<nav class="main-nav"><a href="index.php">Форум</a><a href="search.php">Поиск</a><a href="recommendations.php">Рекомендации</a><?php if($me):?><a href="profile.php?id=<?=$me['id']?>"><?=e($me['username'])?></a><?php if(is_admin($me)):?><a href="admin/index.php">Админка</a><?php endif;?><?php else:?><a href="login.php">Войти</a><?php endif;?></nav>
</div></header>
<div class="server-strip"><span>Сервер: <b>play.greffrlend.fun</b></span><button type="button" onclick="copyIP()">Копировать IP</button></div>
<main class="page">
