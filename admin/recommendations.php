<?php
require_once __DIR__ . '/../config.php';
$me = require_owner();
$threads = data_load('threads.json');
$recommendations = data_load('recommendations.json');
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();
    $action = $_POST['action'] ?? '';
    $threadId = (int)($_POST['thread_id'] ?? 0);
    if ($action === 'add' && $threadId > 0) {
        $exists = false;
        foreach ($recommendations as $r) if ((int)($r['thread_id'] ?? 0) === $threadId) $exists = true;
        if (!$exists) $recommendations[] = ['id'=>next_id($recommendations),'thread_id'=>$threadId,'pinned'=>false,'score'=>100,'created_at'=>date('c')];
    } elseif ($action === 'remove') {
        $recommendations = array_values(array_filter($recommendations, fn($r)=>(int)($r['thread_id']??0)!==$threadId));
    } elseif ($action === 'pin') {
        foreach ($recommendations as &$r) if ((int)($r['thread_id']??0)===$threadId) $r['pinned']=empty($r['pinned']);
        unset($r);
    }
    data_save('recommendations.json',$recommendations);
    header('Location: recommendations.php'); exit;
}
$map=[]; foreach($recommendations as $r) $map[(int)($r['thread_id']??0)]=$r;
$title='Рекомендации — GREFFRLEND'; include __DIR__.'/../includes/header.php';
?>
<section class="card"><div class="category">OWNER</div><h1>⭐ Управление рекомендациями</h1><p class="muted">Добавлять и закреплять рекомендации может только владелец форума.</p></section>
<?php foreach(array_reverse($threads) as $thread): $id=(int)($thread['id']??0); $r=$map[$id]??null; ?>
<section class="card thread"><div><h3>#<?=$id?> <?=e($thread['title']??'Без названия')?></h3><p class="muted"><?=e($thread['author']??'')?> · <?=e($thread['created_at']??'')?></p></div><div class="button-row">
<form method="post"><input type="hidden" name="csrf" value="<?=e(csrf())?>"><input type="hidden" name="thread_id" value="<?=$id?>"><button class="btn" name="action" value="<?=$r?'remove':'add'?>"><?=$r?'Убрать':'Добавить'?></button></form>
<?php if($r): ?><form method="post"><input type="hidden" name="csrf" value="<?=e(csrf())?>"><input type="hidden" name="thread_id" value="<?=$id?>"><button class="btn" name="action" value="pin"><?=!empty($r['pinned'])?'Открепить':'Закрепить'?></button></form><?php endif; ?>
</div></section>
<?php endforeach; ?>
<?php include __DIR__.'/../includes/footer.php'; ?>