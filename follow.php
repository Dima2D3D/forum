<?php
require_once __DIR__.'/config.php';require_once __DIR__.'/includes/social.php';
$me=require_login();check_csrf();$id=(int)($_POST['id']??0);if($id===(int)$me['id']){header('Location: profile.php?id='.$id);exit;}
if(is_following((int)$me['id'],$id))unfollow_user((int)$me['id'],$id);else follow_user((int)$me['id'],$id);
header('Location: '.($_POST['return']??('profile.php?id='.$id)));exit;
