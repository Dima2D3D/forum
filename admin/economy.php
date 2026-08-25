<?php
require_once __DIR__.'/../config.php'; require_once __DIR__.'/../includes/economy.php';
$me=require_owner(); $users=data_load('users.json');
if($_SERVER['REQUEST_METHOD']==='POST'){check_csrf();$id=(int)$_POST['user_id'];$delta=(int)$_POST['amount'];if(abs($delta)>1000000)exit('Слишком большое значение.');if(change_wallet($id,$delta,'Изменение владельцем',$me['username'])){log_action('economy','user:'.$id,'delta='.$delta);}$url='economy.php';header('Location: '.$url);exit;}
include __DIR__.'/../includes/header.php'; ?>
<section class="card"><h1>🪙 Экономика</h1><p class="muted">Только владелец форума может выдавать и снимать Гриферки.</p></section>
<?php foreach($users as $u): ?><form method="post" class="card" style="display:flex;gap:12px;align-items:center;flex-wrap:wrap"><input type="hidden" name="csrf" value="<?=e(csrf())?>"><input type="hidden" name="user_id" value="<?=$u['id']?>"><b><?=e($u['username'])?></b><span>🪙 <?=wallet($u)?></span><input type="number" name="amount" placeholder="+ / -" required><button class="btn">Изменить баланс</button></form><?php endforeach; ?>
<?php include __DIR__.'/../includes/footer.php'; ?>