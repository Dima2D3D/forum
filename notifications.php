<?php
require_once __DIR__.'/config.php';require_once __DIR__.'/includes/social.php';
$me=require_login();$rows=data_load('notifications.json');$changed=false;
foreach($rows as &$r)if((int)($r['user_id']??0)===(int)$me['id']){$r['read']=true;$changed=true;}unset($r);if($changed)data_save('notifications.json',$rows);
$items=array_reverse(array_values(array_filter($rows,fn($r)=>(int)($r['user_id']??0)===(int)$me['id'])));$title='Уведомления — GREFFRLEND';include __DIR__.'/includes/header.php';
?>
<section class="card"><h1>🔔 Уведомления</h1><?php if(!$items):?><p class="muted">Пока уведомлений нет.</p><?php else:foreach($items as $n):?><article class="notification"><div><?=e($n['text'])?></div><small><?=e(date('d.m.Y H:i',strtotime($n['created_at']??'now')))?></small><?php if(!empty($n['url'])):?><a href="<?=e($n['url'])?>">Открыть</a><?php endif;?></article><?php endforeach;endif;?></section>
<?php include __DIR__.'/includes/footer.php'; ?>
