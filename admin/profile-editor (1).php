<?php
require_once __DIR__.'/../config.php';
$owner=require_owner();
$users=data_load('users.json');
$selectedId=(int)($_GET['id']??$_POST['user_id']??0);
$error='';$success='';$selected=null;

foreach($users as $u){if((int)($u['id']??0)===$selectedId){$selected=$u;break;}}

if($_SERVER['REQUEST_METHOD']==='POST' && $selected){
    check_csrf();
    foreach($users as &$item){
        if((int)($item['id']??0)!==$selectedId) continue;

        $item['username']=clean_text((string)($_POST['username']??$item['username']??'Пользователь'),64);
        $item['handle']=ltrim(clean_text((string)($_POST['handle']??$item['handle']??$item['username']),24),'@');
        $item['email']=strtolower(trim((string)($_POST['email']??$item['email']??'')));
        $item['phone']=clean_text((string)($_POST['phone']??''),30);
        $item['description']=profile_description((string)($_POST['description']??''),true);
        $item['role']=in_array($_POST['role']??'', ['user','admin','owner'],true)?$_POST['role']:'user';

        if((int)$item['id']===(int)$owner['id']) $item['role']='owner';

        $item['privacy']=[
            'email'=>in_array($_POST['email_visibility']??'', ['everyone','members','nobody'],true)?$_POST['email_visibility']:'nobody',
            'phone'=>in_array($_POST['phone_visibility']??'', ['everyone','members','nobody'],true)?$_POST['phone_visibility']:'nobody',
            'description'=>in_array($_POST['description_visibility']??'', ['everyone','members','nobody'],true)?$_POST['description_visibility']:'everyone'
        ];

        $item['premium_accent']=preg_match('/^#[0-9a-fA-F]{6}$/',(string)($_POST['premium_accent']??''))?$_POST['premium_accent']:'#ff6817';
        $item['premium_frame']=in_array($_POST['premium_frame']??'', ['glow','soft'],true)?$_POST['premium_frame']:'glow';
        $item['premium_status']=mb_substr(trim((string)($_POST['premium_status']??'')),0,80);
        $item['premium_avatar_shape']=in_array($_POST['premium_avatar_shape']??'', ['circle','rounded','square'],true)?$_POST['premium_avatar_shape']:'circle';
        $item['premium_card_style']=in_array($_POST['premium_card_style']??'', ['default','glass','accent'],true)?$_POST['premium_card_style']:'default';
        $item['premium_name_style']=in_array($_POST['premium_name_style']??'', ['accent','glow','plain'],true)?$_POST['premium_name_style']:'accent';
        $item['premium_cover_style']=in_array($_POST['premium_cover_style']??'', ['default','glow','dark'],true)?$_POST['premium_cover_style']:'default';
        $item['active_from']=preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/',(string)($_POST['active_from']??''))?$_POST['active_from']:'';
        $item['active_to']=preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/',(string)($_POST['active_to']??''))?$_POST['active_to']:'';
        $item['two_factor_enabled']=!empty($_POST['two_factor_enabled']);

        if(!empty($_POST['email_verified'])) $item['email_verified']=true; else $item['email_verified']=false;

        $avatar=save_single_upload('avatar');
        $cover=save_single_upload('cover');
        if($avatar)$item['avatar']=$avatar;
        if($cover)$item['cover']=$cover;

        if(!empty($_POST['remove_avatar'])) $item['avatar']='banners/IMG_20260727_215431_065.jpg';
        if(!empty($_POST['remove_cover'])) $item['cover']='banners/IMG_20260727_215431_065.jpg';

        if(!empty($_POST['new_password'])){
            $new=trim((string)$_POST['new_password']);
            if(strlen($new)<8){$error='Новый пароль должен содержать минимум 8 символов.';break;}
            $item['password_hash']=password_hash($new,PASSWORD_DEFAULT);
        }

        if(!empty($_POST['clear_ban'])){
            $item['ban']=null;$item['banned_until']=null;$item['ban_reason']='';
        } elseif(!empty($_POST['ban_active'])){
            $days=max(0,(int)($_POST['ban_days']??7));
            $until=$days===0?'permanent':date('c',time()+$days*86400);
            $reason=clean_text((string)($_POST['ban_reason']??'Нарушение правил'),300);
            $item['ban']=['active'=>true,'until'=>$until,'reason'=>$reason];
            $item['banned_until']=$until;$item['ban_reason']=$reason;
        }

        $success='Профиль пользователя сохранён.';
        log_action('Редактирование профиля',$item['username'],'Администратор изменил данные профиля через редактор.');
        $selected=$item;
        break;
    }
    unset($item);
    if($error==='') data_save('users.json',$users);
}

$title='Редактор профилей — GREFFRLEND';
include __DIR__.'/../includes/header.php';
?>
<style>
.admin-editor{max-width:1050px;margin:25px auto}.admin-card{background:linear-gradient(145deg,#17120f,#0c0c0c);border:1px solid #38271e;border-radius:16px;padding:22px;margin:15px 0;box-shadow:0 14px 40px rgba(0,0,0,.35)}.admin-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:14px}.admin-form label{display:block;color:#bbb;font-weight:700;margin-top:10px}.admin-form input,.admin-form select,.admin-form textarea{width:100%;box-sizing:border-box;background:#090909;color:#eee;border:1px solid #39312d;border-radius:9px;padding:11px;margin-top:6px}.admin-form textarea{min-height:130px;resize:vertical}.admin-btn{display:inline-block;background:linear-gradient(105deg,#ff6a12,#e52d28);color:#fff;border:0;border-radius:10px;padding:11px 17px;font-weight:800;text-decoration:none;cursor:pointer}.admin-muted{color:#888}.danger{color:#ff7676}.success{color:#64df91}.profile-preview{display:flex;align-items:center;gap:15px}.profile-preview img{width:78px;height:78px;object-fit:cover;border-radius:50%;border:2px solid #ff6a12}@media(max-width:700px){.admin-grid{grid-template-columns:1fr}}
</style>
<div class="admin-editor">
<section class="admin-card"><h1>👤 Редактор профилей</h1><p class="admin-muted">Владелец может полностью редактировать профиль пользователя: имя, @username, контакты, описание, аватар, баннер, роль, Premium, приватность, 2FA, пароль и бан.</p></section>

<section class="admin-card">
<h2>Выберите пользователя</h2>
<form method="get" class="admin-form">
<select name="id" required>
<option value="">— Выберите пользователя —</option>
<?php foreach($users as $u): ?><option value="<?=e((string)$u['id'])?>" <?=$selectedId===(int)$u['id']?'selected':''?>><?=e($u['username']??'Пользователь')?> · @<?=e($u['handle']??$u['username']??'')?></option><?php endforeach;?>
</select>
<button class="admin-btn">Открыть профиль</button>
</form>
</section>

<?php if($selected): ?>
<?php if($error): ?><div class="admin-card danger"><?=e($error)?></div><?php endif;?>
<?php if($success): ?><div class="admin-card success"><?=e($success)?></div><?php endif;?>

<form method="post" enctype="multipart/form-data" class="admin-form">
<input type="hidden" name="csrf" value="<?=e(csrf())?>">
<input type="hidden" name="user_id" value="<?=e((string)$selected['id'])?>">

<section class="admin-card">
<div class="profile-preview">
<img src="<?=e($selected['avatar']??'banners/IMG_20260727_215431_065.jpg')?>" alt="">
<div><h2 style="margin:0"><?=e($selected['username']??'Пользователь')?></h2><div class="admin-muted">@<?=e($selected['handle']??$selected['username']??'')?></div></div>
</div>
</section>

<section class="admin-card">
<h2>Основные данные</h2>
<div class="admin-grid">
<div><label>Имя пользователя</label><input name="username" value="<?=e($selected['username']??'')?>" maxlength="64" required></div>
<div><label>@Юзернейм</label><input name="handle" value="<?=e($selected['handle']??$selected['username']??'')?>" maxlength="24"></div>
<div><label>E-mail</label><input type="email" name="email" value="<?=e($selected['email']??'')?>" required></div>
<div><label>Телефон</label><input name="phone" value="<?=e($selected['phone']??'')?>" maxlength="30"></div>
<div><label>Роль</label><select name="role"><?php foreach(['user','admin','owner'] as $role):?><option value="<?=$role?>" <?=($selected['role']??'user')===$role?'selected':''?>><?=$role?></option><?php endforeach;?></select></div>
<div><label><input type="checkbox" name="email_verified" value="1" <?=!empty($selected['email_verified'])?'checked':''?>> E-mail подтверждён</label></div>
</div>
<label>О себе</label><textarea name="description" maxlength="20000"><?=e($selected['description']??'')?></textarea>
</section>

<section class="admin-card">
<h2>🖼 Аватар и баннер</h2>
<div class="admin-grid">
<div><label>Новый аватар</label><input type="file" name="avatar" accept="image/jpeg,image/png,image/gif,image/webp"><label><input type="checkbox" name="remove_avatar" value="1"> Сбросить аватар</label></div>
<div><label>Новый баннер профиля</label><input type="file" name="cover" accept="image/jpeg,image/png,image/gif,image/webp"><label><input type="checkbox" name="remove_cover" value="1"> Сбросить баннер</label></div>
</div>
</section>

<section class="admin-card">
<h2>🔐 Безопасность</h2>
<div class="admin-grid">
<div><label>Новый пароль</label><input type="password" name="new_password" minlength="8" placeholder="Оставьте пустым, чтобы не менять"></div>
<div><label><input type="checkbox" name="two_factor_enabled" value="1" <?=!empty($selected['two_factor_enabled'])?'checked':''?>> 2FA по E-mail включена</label></div>
</div>
</section>

<section class="admin-card">
<h2>👁 Приватность</h2>
<div class="admin-grid"><?php $privacy=$selected['privacy']??[];?>
<div><label>E-mail виден</label><select name="email_visibility"><?php foreach(['everyone'=>'Всем','members'=>'Участникам','nobody'=>'Никому'] as $v=>$n):?><option value="<?=$v?>" <?=($privacy['email']??'nobody')===$v?'selected':''?>><?=$n?></option><?php endforeach;?></select></div>
<div><label>Телефон виден</label><select name="phone_visibility"><?php foreach(['everyone'=>'Всем','members'=>'Участникам','nobody'=>'Никому'] as $v=>$n):?><option value="<?=$v?>" <?=($privacy['phone']??'nobody')===$v?'selected':''?>><?=$n?></option><?php endforeach;?></select></div>
<div><label>Описание видят</label><select name="description_visibility"><?php foreach(['everyone'=>'Всем','members'=>'Участникам','nobody'=>'Никому'] as $v=>$n):?><option value="<?=$v?>" <?=($privacy['description']??'everyone')===$v?'selected':''?>><?=$n?></option><?php endforeach;?></select></div>
</div>
</section>

<section class="admin-card">
<h2>⭐ Premium</h2>
<div class="admin-grid">
<div><label>Цвет</label><input type="text" name="premium_accent" value="<?=e($selected['premium_accent']??'#ff6817')?>" pattern="#[0-9a-fA-F]{6}"></div>
<div><label>Рамка</label><select name="premium_frame"><option value="glow" <?=($selected['premium_frame']??'glow')==='glow'?'selected':''?>>Сильное свечение</option><option value="soft" <?=($selected['premium_frame']??'')==='soft'?'selected':''?>>Мягкое свечение</option></select></div>
<div><label>Статус</label><input name="premium_status" value="<?=e($selected['premium_status']??'')?>" maxlength="80"></div>
<div><label>Форма аватара</label><select name="premium_avatar_shape"><?php foreach(['circle'=>'Круг','rounded'=>'Скруглённый','square'=>'Квадрат'] as $v=>$n):?><option value="<?=$v?>" <?=($selected['premium_avatar_shape']??'circle')===$v?'selected':''?>><?=$n?></option><?php endforeach;?></select></div>
<div><label>Карточки</label><select name="premium_card_style"><?php foreach(['default'=>'Обычные','glass'=>'Стекло','accent'=>'Акцентные'] as $v=>$n):?><option value="<?=$v?>" <?=($selected['premium_card_style']??'default')===$v?'selected':''?>><?=$n?></option><?php endforeach;?></select></div>
<div><label>Имя</label><select name="premium_name_style"><?php foreach(['accent'=>'Акцентное','glow'=>'Сияющее','plain'=>'Обычное'] as $v=>$n):?><option value="<?=$v?>" <?=($selected['premium_name_style']??'accent')===$v?'selected':''?>><?=$n?></option><?php endforeach;?></select></div>
<div><label>Обложка</label><select name="premium_cover_style"><?php foreach(['default'=>'Обычная','glow'=>'Свечение','dark'=>'Затемнённая'] as $v=>$n):?><option value="<?=$v?>" <?=($selected['premium_cover_style']??'default')===$v?'selected':''?>><?=$n?></option><?php endforeach;?></select></div>
<div><label>Обычно активен с</label><input type="time" name="active_from" value="<?=e($selected['active_from']??'')?>"></div>
<div><label>Обычно активен до</label><input type="time" name="active_to" value="<?=e($selected['active_to']??'')?>"></div>
</div>
</section>

<section class="admin-card">
<h2>🚫 Бан</h2>
<p class="admin-muted">Текущий бан: <?=!empty($selected['ban']['active'])?e((string)($selected['ban']['reason']??'активен')):'нет'?></p>
<label><input type="checkbox" name="ban_active" value="1"> Установить/обновить бан</label>
<select name="ban_days"><option value="1">1 день</option><option value="7" selected>7 дней</option><option value="30">30 дней</option><option value="0">Навсегда</option></select>
<input name="ban_reason" value="<?=e($selected['ban_reason']??$selected['ban']['reason']??'Нарушение правил')?>" placeholder="Причина">
<label><input type="checkbox" name="clear_ban" value="1"> Полностью снять бан</label>
</section>

<section class="admin-card">
<button class="admin-btn" type="submit">💾 Сохранить все изменения</button>
</section>
</form>
<?php endif;?>
</div>
<?php include __DIR__.'/../includes/footer.php'; ?>