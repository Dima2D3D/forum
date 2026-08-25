<?php
require __DIR__.'/config.php';
if(GOOGLE_CLIENT_ID===''){http_response_code(503);exit('Google OAuth ещё не настроен. Заполните GOOGLE_CLIENT_ID и GOOGLE_CLIENT_SECRET в config.php.');}
$state=bin2hex(random_bytes(24));$_SESSION['google_state']=$state;
$params=http_build_query(['client_id'=>GOOGLE_CLIENT_ID,'redirect_uri'=>GOOGLE_REDIRECT_URI,'response_type'=>'code','scope'=>'openid email profile','state'=>$state,'access_type'=>'online','prompt'=>'select_account']);
header('Location: https://accounts.google.com/o/oauth2/v2/auth?'.$params);exit;
