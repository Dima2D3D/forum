<?php
require_once __DIR__.'/config.php';
$title='GREFFRLEND Forum';
$categories=data_load('categories.json');$threads=data_load('threads.json');$banner=data_load('banner.json',['enabled'=>false]);$me=current_user();
function categoryThreadCount(array $threads,int $id):int{$n=0;foreach($threads as $t)if((int)($t['category_id']??0)===$id)$n++;return $n;}
$latest=array_slice(array_reverse($threads),0,10);
include __DIR__.'/includes/header.php';
?>
<section class="hero"><div class="eyebrow">ОФИЦИАЛЬНЫЙ ФОРУМ</div><h1>GREFFRLEND<br><span>COMMUNITY</span></h1><p class="muted">Minecraft, новости проекта, помощь и общение.</p><a class="btn" href="recommendations.php">Смотреть рекомендации</a></section>
<?php if(!empty($banner['enabled'])):?><section class="billboard"><div class="category">РЕКЛАМА</div><?php if(!empty($banner['image'])):?><img src="<?=e($banner['image'])?>" alt="Реклама" style="width:100%;max-height:360px;object-fit:cover;border-radius:10px;margin:8px 0 15px"><?php endif;?><h2><?=e($banner['title']??'')?></h2><p><?=nl2br(e($banner['text']??''))?></p><?php if(!empty($banner['url'])):?><a class="btn" href="<?=e($banner['url'])?>" rel="nofollow sponsored">Подробнее</a><?php endif;?></section><?php endif;?>
<section><h2>Разделы форума</h2><?php if(!$categories):?><div class="card muted">Разделов пока нет.</div><?php else:foreach($categories as $c):$id=(int)($c['id']??0);?><div class="card thread"><div><div class="category">РАЗДЕЛ</div><h3><a href="forum.php?id=<?=$id?>"><?=e($c['title']??$c['name']??'Без названия')?></a></h3><p class="muted"><?=e($c['description']??'')?></p><span class="muted">Тем: <?=categoryThreadCount($threads,$id)?></span></div></div><?php endforeach;endif;?></section>
<section><h2>Последние обсуждения</h2><?php if(!$latest):?><div class="card muted">Пока нет тем.</div><?php else:foreach($latest as $t):?><article class="card thread"><div><a href="thread.php?id=<?=(int)$t['id']?>"><b><?=e($t['title']??'Без названия')?></b></a><div class="muted"><?=e($t['author']??'Неизвестный')?> · <?=e((string)($t['created_at']??''))?></div></div></article><?php endforeach;endif;?></section>
<?php include __DIR__.'/includes/footer.php'; ?>