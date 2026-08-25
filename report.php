<?php
require_once __DIR__.'/config.php';require_once __DIR__.'/includes/social.php';
$me=require_login();check_csrf();$type=$_POST['type']??'post';$target=(int)($_POST['target_id']??0);$reason=trim($_POST['reason']??'');if($target<1||$reason===''){http_response_code(422);exit('Укажите причину жалобы.');}if(!rate_limit('report',60,3)){http_response_code(429);exit('Слишком много жалоб. Попробуйте позже.');}report_content((int)$me['id'],$type,$target,$reason);header('Location: '.($_POST['return']??'index.php'));exit;
