<?php
require_once __DIR__.'/../config.php';
$owner=require_owner();
$bannerFile=DATA_DIR.'banner.json';
$banner=data_load('banner.json',['enabled'=>false,'title'=>'','text'=>'','url'=>'','image'=>'']);
$error='';$ok='';
if($_SERVER['REQUEST_METHOD']==='POST'){
 check_csrf();
 $action=$_POST['action']??'save';
 if($action==='delete'){$banner=['enabled'=>false,'title'=>'','text'=>'','url'=>'','image'=>''];data_save('banner.json',$banner);log_action('Удаление баннера','banner');$ok='Баннер удалён.';}
 else{
  $banner['enabled']=isset($_POST['enabled']);$banner['title']=clean_text($_POST['title']??'',120);$banner['text']=clean_text($_POST['text']??'',500);$banner['url']=filter_var(trim($_POST['url']??''),FILTER_VALIDATE_URL)?trim($_POST['url']??''):'';
  if(!empty($_FILES['image']['tmp_name'])){if(allowed_upload($_FILES['image']['tmp_name'],$_FILES['image']['name'])){$old=$banner['image']??'';$name='banner_'.bin2hex(random_bytes(10)).'.'.pathinfo($_FILES['image']['name'],PATHINFO_EXTENSION);if(move_uploaded_file($_FILES['image']['tmp_name'],__DIR__.'/../banners/'.$name)){$banner['image']='banners/'.$name;if($old&&str_starts_with($old,'banners/')&&is_file(__DIR__.'/../'.$old))@unlink(__DIR__.'/../'.$old);}}else{$error='Изображение должно быть JPG, PNG, GIF или WEBP и не больше 8 МБ.';}}
  if(!$error){data_save('banner.json',$banner);log_action('Изменение баннера','banner');$ok='Баннер сохранён.';}
 }
}
$title='Реклама — GREFFRLEND';include __DIR__.'/../includes/header.php';
?><section class="card"><div class="category">ТОЛЬКО OWNER</div><h1>Рекламный баннер</h1><p class="muted">Только владелец форума может загружать, изменять, включать и удалять рекламный баннер.</p></section><?php if($error):?><div class="card danger"><?=e($error)?></div><?php endif;if($ok):?><div class="card success"><?=e($ok)?></div><?php endif;?><div class="card form"><form method="post" enctype="multipart/form-data"><input type="hidden" name="csrf" value="<?=e(csrf())?>"><input type="hidden" name="action" value="save"><label><input type="checkbox" name="enabled" <?=$banner['enabled']?'checked':''?>> Показывать баннер</label><label>Заголовок</label><input name="title" maxlength="120" value="<?=e($banner['title'])?>"><label>Текст</label><textarea name="text" maxlength="500"><?=e($banner['text'])?></textarea><label>Ссылка</label><input name="url" type="url" value="<?=e($banner['url'])?>"><label>Изображение</label><input name="image" type="file" accept="image/jpeg,image/png,image/gif,image/webp"><button class="btn">Сохранить</button></form><form method="post" style="margin-top:12px"><input type="hidden" name="csrf" value="<?=e(csrf())?>"><input type="hidden" name="action" value="delete"><button class="btn" type="submit">Удалить баннер</button></form></div><?php include __DIR__.'/../includes/footer.php'; ?>
