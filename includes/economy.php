<?php
require_once __DIR__.'/../config.php';
require_once __DIR__.'/social.php';

function subscription(array $u): ?array {
    foreach (data_load('subscriptions.json') as $s) {
        if ((int)($s['user_id'] ?? 0) === (int)($u['id'] ?? 0) && ($s['status'] ?? 'active') === 'active') {
            if (($s['expires_at'] ?? 'never') === 'never' || strtotime((string)$s['expires_at']) > time()) return $s;
        }
    }
    return null;
}
function premium(array $u): bool { return is_owner($u) || subscription($u) !== null; }
function wallet(array $u): int { foreach(data_load('wallets.json') as $w) if((int)($w['user_id']??0)===(int)$u['id']) return max(0,(int)($w['balance']??0)); return 0; }
function set_wallet(int $userId,int $balance): void { $items=data_load('wallets.json');$found=false;foreach($items as &$w)if((int)($w['user_id']??0)===$userId){$w['balance']=max(0,$balance);$found=true;break;}unset($w);if(!$found)$items[]=['user_id'=>$userId,'balance'=>max(0,$balance)];data_save('wallets.json',$items); }
function change_wallet(int $userId,int $delta,string $reason,string $actor='system'): bool { $users=data_load('users.json');$name='user';foreach($users as $u)if((int)$u['id']===$userId){$name=$u['username'];break;}$old=wallet(['id'=>$userId]);$new=$old+$delta;if($new<0)return false;set_wallet($userId,$new);$logs=data_load('economy_logs.json');$logs[]=['id'=>next_id($logs),'user_id'=>$userId,'delta'=>$delta,'balance'=>$new,'reason'=>$reason,'actor'=>$actor,'time'=>date('c'),'username'=>$name];data_save('economy_logs.json',$logs);return true; }
function gifts(): array { return data_load('gifts.json',[['id'=>1,'name'=>'Роза','emoji'=>'🌹','price'=>50,'enabled'=>true],['id'=>2,'name'=>'Сердце','emoji'=>'❤️','price'=>100,'enabled'=>true],['id'=>3,'name'=>'Алмаз','emoji'=>'💎','price'=>500,'enabled'=>true],['id'=>4,'name'=>'Корона','emoji'=>'👑','price'=>1000,'enabled'=>true],['id'=>5,'name'=>'Ракета','emoji'=>'🚀','price'=>2500,'enabled'=>true]]); }
function give_gift(int $from,int $to,int $giftId,string $description=''): bool { foreach(gifts() as $g){if((int)$g['id']!==$giftId||empty($g['enabled']))continue;if($from===$to)return false;if(!change_wallet($from,-(int)$g['price'],'Подарок: '.$g['name'],'user:'.$from))return false;$description=clean_text($description,500);$logs=data_load('gift_logs.json');$logs[]=['id'=>next_id($logs),'gift_id'=>$giftId,'from'=>$from,'to'=>$to,'description'=>$description,'time'=>date('c')];data_save('gift_logs.json',$logs);change_wallet($to,(int)floor($g['price']*.25),'Получен подарок: '.$g['name'],'gift');$fromName='Пользователь';foreach(data_load('users.json') as $sender)if((int)($sender['id']??0)===$from){$fromName=(string)$sender['username'];break;}$giftText=$fromName.' подарил(а) вам '.$g['name'].'.';if($description!=='')$giftText.=' Сообщение: '.$description;notifications_add($to,'gift',$giftText,'profile.php?id='.$from);check_achievements($to);return true;}return false; }

/* Цена Premium специально высокая: купить можно, но это долгосрочная цель. */
function premium_plans(): array { return [30=>100000,90=>250000,365=>800000]; }
function premium_plan_price(int $days): int { $plans=premium_plans();return $plans[$days]??0; }
function premium_expires_for(array $u,int $days): string {
    $current=subscription($u);$base=$current&&($current['expires_at']??'')&&strtotime((string)$current['expires_at'])>time()?strtotime((string)$current['expires_at']):time();
    return date('c',$base+($days*86400));
}
function grant_premium(int $userId,int $days,int $giverId=0,string $source='purchase'): bool {
    if(!in_array($days,array_keys(premium_plans()),true))return false;
    $users=data_load('users.json');$target=null;foreach($users as $u)if((int)$u['id']===$userId){$target=$u;break;}if(!$target)return false;
    $subs=data_load('subscriptions.json');$found=false;$expires=premium_expires_for($target,$days);
    foreach($subs as &$s){if((int)($s['user_id']??0)!==$userId)continue;$s['status']='active';$s['expires_at']=$expires;$s['days_added']=($s['days_added']??0)+$days;$s['updated_at']=date('c');$s['source']=$source;$found=true;break;}unset($s);
    if(!$found)$subs[]=['id'=>next_id($subs),'user_id'=>$userId,'status'=>'active','started_at'=>date('c'),'expires_at'=>$expires,'days_added'=>$days,'source'=>$source,'giver_id'=>$giverId];
    data_save('subscriptions.json',$subs);
    notifications_add($userId,'premium','⭐ Вам выдан Premium до '.date('d.m.Y H:i',strtotime($expires)).'.','premium.php');
    return true;
}
function buy_premium(int $buyerId,int $targetId,int $days): bool {
    $price=premium_plan_price($days);if($price<=0||$buyerId===$targetId&&premium(['id'=>$buyerId]))return false;
    if(!change_wallet($buyerId,-$price,'Premium '.$days.' дней','premium'))return false;
    if(!grant_premium($targetId,$days,$buyerId,$buyerId===$targetId?'self_purchase':'gift')){change_wallet($buyerId,$price,'Возврат за Premium','premium-refund');return false;}
    if($buyerId!==$targetId)notifications_add($buyerId,'premium','🎁 Вы подарили Premium пользователю.','premium.php?gift='.$targetId);
    return true;
}
