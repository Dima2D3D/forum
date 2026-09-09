<?php
require_once __DIR__.'/config.php';
require_once __DIR__.'/includes/economy.php';
$me=require_login();
$users=data_load('users.json');
$targetId=(int)($_GET['gift']??$_POST['target_id']??$me['id']);
$target=null;foreach($users as $u)if((int)$u['id']===$targetId){$target=$u;break;}
if(!$target)$target=$me;
$error='';$success='';
if($_SERVER['REQUEST_METHOD']==='POST'){
 check_csrf();
 if(!rate_limit('premium_purchase',5,3))$error='Подождите немного перед следующей покупкой.';
 else{$days=(int)($_POST['days']??0);$targetId=(int)($_POST['target_id']??$me['id']);$target=null;foreach($users as $u)if((int)$u['id']===$targetId){$target=$u;break;}
  if(!$target)$error='Получатель не найден.';
  elseif($targetId===(int)$me['id']&&premium($me))$error='У вас уже есть Premium. Новый срок можно будет купить после окончания текущего.';
  elseif($targetId!==(int)$me['id']&&$targetId===0)$error='Некорректный получатель.';
  elseif(buy_premium((int)$me['id'],$targetId,$days)){$success=$targetId===(int)$me['id'?'1':'0']?'':' ';$success=$targetId===(int)$me['id']?'⭐ Premium успешно активирован!':'🎁 Premium подарен пользователю '.($target['username']??'').'.';$me=current_user();$target=$target;}
  else $error='Не хватает Гриферок или выбранный тариф недоступен.';
 }
}
$plans=premium_plans();$title='Premium — GREFFRLEND';include __DIR__.'/includes/header.php';
?>
<style>
.premium-shop{border:1px solid #6b3518!important;background:radial-gradient(circle at 90% 0%,rgba(255,105,25,.22),transparent 35%),linear-gradient(135deg,#17100c,#0d0d0d)}
.premium-plan{transition:.2s transform,.2s border-color;position:relative;overflow:hidden}.premium-plan:hover{transform:translateY(-4px);border-color:#ff7620}.premium-plan.featured{box-shadow:0 0 35px rgba(255,100,20,.12)}
.premium-price{font-size:28px;font-weight:900;color:#ff7620}.premium-icon{font-size:45px}.premium-shop input,.premium-shop select{background:#090909}
</style>
<section class="card premium-shop">
 <div class="category">GREFFRLEND PREMIUM</div><h1>⭐ Premium</h1>
 <p>Больше оформления, больше возможностей и заметный профиль. Premium можно купить себе или подарить другому пользователю за 🪙 Гриферки.</p>
 <?php if($error):?><div class="card danger"><?=e($error)?></div><?php endif;?><?php if($success):?><div class="card success"><?=e($success)?></div><?php endif;?>
 <div class="grid" style="margin-top:18px">
 <?php $icons=[30=>'⭐',90=>'💎',365=>'👑'];foreach($plans as $days=>$price):?><div class="card premium-plan <?=($days===90?'featured':'')?>" style="margin:0"><div class="premium-icon"><?=$icons[$days]?></div><h2><?=$days===365?'1 год':$days.' дней'?></h2><div class="premium-price">🪙 <?=number_format($price,0,'.',' ')?></div><p class="muted"><?=number_format((int)floor($price/$days),0,'.',' ')?> Гриферок в день</p><form method="post"><input type="hidden" name="csrf" value="<?=e(csrf())?>"><input type="hidden" name="days" value="<?=$days?>"><input type="hidden" name="target_id" value="<?=($targetId===(int)$me['id']?(int)$me['id']:$targetId)?>"><button class="btn" type="submit">Купить / подарить</button></form></div><?php endforeach;?></div>
 <div class="card" style="margin-top:18px"><h2>🎁 Подарить Premium</h2><p class="muted">Выберите пользователя, если хотите купить Premium именно ему. Деньги списываются с вашего баланса.</p><form method="get"><label>Получатель</label><select name="gift" required><option value="<?=e((string)$me['id'])?>">Мне — <?=e($me['username'])?></option><?php foreach($users as $u):if((int)$u['id']===(int)$me['id'])continue;?><option value="<?=e((string)$u['id'])?>" <?=($targetId===(int)$u['id'])?'selected':''?>><?=e((string)$u['username'])?> @<?=e((string)($u['handle']??''))?></option><?php endforeach;?></select><button class="btn" type="submit">Выбрать получателя</button></form><?php if($targetId!==(int)$me['id']&&$target):?><p style="margin-bottom:0">Сейчас подарок будет отправлен: <b><?=e($target['username'])?></b>.</p><?php endif;?></div>
 <div class="card"><h2>✨ Что получает Premium</h2><div class="grid"><div>🎨 Свой цвет профиля</div><div>🖼 Форма аватара</div><div>🌌 Стиль обложки</div><div>🪟 Темы форума</div><div>💬 Расширенный статус</div><div>🕐 Время активности</div><div>✨ Эффекты имени</div><div>📌 Premium-оформление постов</div></div></div>
 <p class="muted">Ваш баланс: 🪙 <?=number_format(wallet($me),0,'.',' ')?></p>
</section>
<?php include __DIR__.'/includes/footer.php'; ?>