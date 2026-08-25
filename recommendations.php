<?php
require_once __DIR__.'/config.php';
$title='Рекомендации — GREFFRLEND';
$threads=data_load('threads.json');
$recommendations=data_load('recommendations.json');
$byId=[];foreach($threads as $t)$byId[(int)$t['id']=$t;
$items=[];foreach($recommendations as $r){$id=(int)($r['thread_id']??0);if(isset($byId[$id])){$r['thread']=$byId[$id];$items[]=$r;}}
usort($items,function($a,$b){$p=(int)($b['pinned']??0)<=> (int)($a['pinned']??0);return $p?:strcmp((string)($b['updated_at']??''),(string)($a['updated_at']??''));});
include __DIR__.'/includes/header.php';
?>
<section class="card"><div class="category">ЛЕНТА</div><h1>Рекомендации</h1><p class="muted">Интересные обсуждения сообщества. Закреплять материалы здесь может только владелец форума.</p></section>
<?php if(!$items):?><div class="card muted">Рекомендаций пока нет.</div><?php endif;?>
<?php foreach($items as $r):$t=$r['thread'];?><article class="card recommendation <?=!empty($r['pinned'])?'pinned':''?>"><div class="category"><?=!empty($r['pinned'])?'📌 ЗАКРЕПЛЕНО':'⭐ РЕКОМЕНДАЦИЯ'?></div><h2><a href="thread.php?id=<?=$t['id']?>"><?=e($t['title']??'Без названия')?></a></h2><p class="muted"><?=e($t['author']??'')?></p><p><?=e(mb_substr((string)($t['content']??''),0,240))?></p></article><?php endforeach;?>
<?php include __DIR__.'/includes/footer.php'; ?>
