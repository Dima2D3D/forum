<?php
require_once __DIR__.'/config.php';
$title='Поиск — GREFFRLEND';
$q=trim($_GET['q']??'');$threads=data_load('threads.json');$results=[];
if($q!==''){foreach($threads as $t){$hay=mb_strtolower((string)($t['title']??'').' '.(string)($t['content']??'').' '.(string)($t['author']??''));if(mb_strpos($hay,mb_strtolower($q))!==false)$results[]=$t;}}
include __DIR__.'/includes/header.php';
?><section class="card"><h1>Поиск по форуму</h1><form class="search-row" method="get"><input class="searchbox" name="q" value="<?=e($q)?>" placeholder="Введите название темы, текст или автора"><button class="btn" type="submit">Найти</button></form></section>
<?php if($q!==''):?><p class="muted">Найдено: <?=count($results)?></p><?php foreach($results as $t):?><article class="card thread"><div><div class="category">ТЕМА</div><h3><a href="thread.php?id=<?=$t['id']?>"><?=e($t['title']??'Без названия')?></a></h3><p class="muted"><?=e($t['author']??'')?> · <?=e((string)($t['created_at']??''))?></p></div></article><?php endforeach;if(!$results):?><div class="card muted">Ничего не найдено.</div><?php endif;?><?php endif;?>
<?php include __DIR__.'/includes/footer.php'; ?>
