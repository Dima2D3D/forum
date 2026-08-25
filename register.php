<?php
require_once __DIR__.'/config.php';
if(user()){header('Location: index.php');exit;}
$err='';$ok='';
if($_SERVER['REQUEST_METHOD']==='POST'){
 check_csrf();
 $name=trim($_POST['username']??'');$email=strtolower(trim($_POST['email']??''));$pass=$_POST['password']??'';$users=data_load('users.json');
 foreach($users as $x)if(strtolower($x['email']??'')===$email||strtolower($x['username']??'')===strtolower($name)){$err='Ник или E-mail уже занят.';break;}
 if(!$err&&!preg_match('/^[A-Za-z0-9_]{3,24}$/',$name))$err='Некорректный ник. Используйте 3–24 символа: латиница, цифры и _.';
 if(!$err&&!filter_var($email,FILTER_VALIDATE_EMAIL))$err='Некорректный E-mail.';
 if(!$err&&strlen($pass)<8)$err='Пароль минимум 8 символов.';
 if(!$err){$id=next_id($users);$token=bin2hex(random_bytes(32));$role=strtolower($email)===strtolower(OWNER_EMAIL)?'owner':'user';$users[]=['id'=>$id,'username'=>$name,'email'=>$email,'password_hash'=>password_hash($pass,PASSWORD_DEFAULT),'email_verified'=>false,'verify_token'=>$token,'role'=>$role,'ban'=>null,'banned_until'=>null,'ban_reason'=>'','ip'=>client_ip(),'last_ip'=>client_ip(),'created_at'=>date('c')];data_save('users.json',$users);$link=SITE_URL.'/verify.php?id='.rawurlencode((string)$id).'&token='.rawurlencode($token);if(mail_verification($email,$name,$link))$ok='Аккаунт создан. Письмо с кнопкой подтверждения отправлено на E-mail.';else$ok='Аккаунт создан, но хостинг не смог отправить письмо. Проверьте настройки почты.';}
}
$title='Регистрация — GREFFRLEND';include __DIR__.'/includes/header.php';
?><div class="card form"><div class="logo">GREFFRLEND</div><h1>Регистрация</h1><?php if($err):?><div class="card danger"><?=e($err)?></div><?php endif;?><?php if($ok):?><div class="card success"><?=e($ok)?></div><?php endif;?><form method="post"><input type="hidden" name="csrf" value="<?=e(csrf())?>"><label>Ник</label><input name="username" maxlength="24" required><label>E-mail</label><input type="email" name="email" required><label>Пароль</label><input type="password" name="password" minlength="8" required><button class="btn" type="submit">Создать аккаунт</button></form></div><?php include __DIR__.'/includes/footer.php'; ?>