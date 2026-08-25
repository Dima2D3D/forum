<?php
require_once __DIR__.'/config.php'; require_once __DIR__.'/includes/economy.php';
$me=require_login(); $title='Гриферки — '.SITE_NAME;
if($_SERVER['REQUEST_METHOD']==='POST'){check_csrf(); if(!rate_limit('economy',5,1))exit('Слишком часто.'); if(isset($_POST['daily'])){ $key=date('Y-m-d'); $claims=data_load('daily_claims.json'); foreach($claims as $c)if((int)$c['user_id']===$me['id']&&$c['day']===$key)exit('Сегодняшний бонус уже получен.'); $claims[]=['user_id'=>$me['id'],'day'=>$key,'time'=>date('c')];data_save('daily_claims.json',$claims);change_wallet($me['id'],100,'Ежедневный бонус',$me['username']); header('Location: economy.php?ok=1');exit;} }
include __DIR__.'/includes/header.php'; ?>
<section class="card"><div class="category">GREFFRLEND ECONOMY</div><h1>🪙 <?=wallet($me)?> Гриферок</h1><p class="muted">Внутренняя валюта форума. Не имеет денежной стоимости.</p><form method="post"><input type="hidden" name="csrf" value="<?=e(csrf())?>"><button class="btn" name="daily" value="1">🎁 Получить ежедневный бонус</button></form></section>
<section class="card"><h2>⭐ Подписка</h2><?php if(premium($me)): ?><p>У вас активен <b>GREFFRLEND PREMIUM</b>.</p><?php else: ?><p>Premium даёт дополнительные возможности профиля и повышенные лимиты.</p><?php endif; ?></section>
<section class="card"><h2>🎁 Подарки</h2><p>Отправляйте подарки другим участникам за Гриферки.</p><a class="btn" href="gifts.php">Открыть магазин подарков</a></section>
<?php include __DIR__.'/includes/footer.php'; ?>