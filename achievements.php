<?php
require_once __DIR__.'/config.php';require_once __DIR__.'/includes/social.php';
$me=require_login();$items=user_achievements((int)$me['id']);$title='Достижения — GREFFRLEND';include __DIR__.'/includes/header.php';
?>
<section class="card"><h1>🏆 Достижения</h1><div class="achievement-grid"><?php foreach($items as $a):?><div class="achievement"><b>🏆 <?=e($a['name'])?></b><span><?=e($a['created_at']??'')?></span><?php if((int)($a['reward']??0)>0):?><small>+<?=e((string)$a['reward'])?> 🪙</small><?php endif;?></div><?php endforeach;if(!$items):?><p class="muted">Достижений пока нет. Участвуйте в жизни форума!</p><?php endif;?></div></section>
<?php include __DIR__.'/includes/footer.php'; ?>
