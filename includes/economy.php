<?php
require_once __DIR__.'/../config.php';

function subscription(array $u): ?array {
    foreach (data_load('subscriptions.json') as $s) {
        if ((int)($s['user_id']??0)===(int)($u['id']??0) && ($s['status']??'active')==='active') {
            if (($s['expires_at']??'never')==='never' || strtotime((string)$s['expires_at'])>time()) return $s;
        }
    }
    return null;
}
function premium(array $u): bool { return is_owner($u) || subscription($u)!==null; }
function wallet(array $u): int {
    foreach(data_load('wallets.json') as $w) if((int)($w['user_id']??0)===(int)$u['id']) return max(0,(int)($w['balance']??0));
    return 0;
}
function set_wallet(int $userId,int $balance): void {
    $items=data_load('wallets.json'); $found=false;
    foreach($items as &$w) if((int)($w['user_id']??0)===$userId){$w['balance']=max(0,$balance);$found=true;break;}
    unset($w); if(!$found)$items[]=['user_id'=>$userId,'balance'=>max(0,$balance)]; data_save('wallets.json',$items);
}
function change_wallet(int $userId,int $delta,string $reason,string $actor='system'): bool {
    $users=data_load('users.json'); $name='user'; foreach($users as $u)if((int)$u['id']===$userId){$name=$u['username'];break;}
    $old=wallet(['id'=>$userId]); $new=$old+$delta; if($new<0)return false; set_wallet($userId,$new);
    $logs=data_load('economy_logs.json'); $logs[]=['id'=>next_id($logs),'user_id'=>$userId,'delta'=>$delta,'balance'=>$new,'reason'=>$reason,'actor'=>$actor,'time'=>date('c'),'username'=>$name]; data_save('economy_logs.json',$logs); return true;
}
function gifts(): array { return data_load('gifts.json',[['id'=>1,'name'=>'Роза','emoji'=>'🌹','price'=>50,'enabled'=>true],['id'=>2,'name'=>'Сердце','emoji'=>'❤️','price'=>100,'enabled'=>true],['id'=>3,'name'=>'Алмаз','emoji'=>'💎','price'=>500,'enabled'=>true],['id'=>4,'name'=>'Корона','emoji'=>'👑','price'=>1000,'enabled'=>true],['id'=>5,'name'=>'Ракета','emoji'=>'🚀','price'=>2500,'enabled'=>true]]); }
function give_gift(int $from,int $to,int $giftId): bool {
    foreach(gifts() as $g) if((int)$g['id']===$giftId && !empty($g['enabled'])) {
        if($from===$to || !change_wallet($from,-(int)$g['price'],'Подарок: '.$g['name'],'user:'.$from)) return false;
        $logs=data_load('gift_logs.json'); $logs[]=['id'=>next_id($logs),'gift_id'=>$giftId,'from'=>$from,'to'=>$to,'time'=>date('c')]; data_save('gift_logs.json',$logs); return change_wallet($to,(int)floor($g['price']*.25),'Получен подарок: '.$g['name'],'gift');
    } return false;
}
