<?php
declare(strict_types=1);
session_start();
const SITE_NAME='GREFFRLEND Forum'; const SITE_URL='https://forum.greffrlend.fun'; const ADMIN_EMAIL='admins@greffrlend.fun'; const MAIL_FROM='admins@greffrlend.fun'; const DATA_DIR=__DIR__.'/data/';
function data_load(string $f,array $d=[]):array{$p=DATA_DIR.$f;if(!is_file($p))return $d;$v=json_decode((string)file_get_contents($p),true);return is_array($v)?$v:$d;}
function data_save(string $f,array $d):void{$p=DATA_DIR.$f;if(!is_dir(DATA_DIR))mkdir(DATA_DIR,0750,true);$fp=fopen($p,'c+');if(!$fp)throw new RuntimeException('Storage unavailable');flock($fp,LOCK_EX);ftruncate($fp,0);rewind($fp);fwrite($fp,json_encode($d,JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES));fflush($fp);flock($fp,LOCK_UN);fclose($fp);}
function e(string $s):string{return htmlspecialchars($s,ENT_QUOTES,'UTF-8');}
function csrf():string{if(empty($_SESSION['csrf']))$_SESSION['csrf']=bin2hex(random_bytes(32));return $_SESSION['csrf'];}
function check_csrf():void{if(!hash_equals($_SESSION['csrf']??'',$_POST['csrf']??'')){http_response_code(419);exit('Недействительный запрос.');}}
function user():?array{if(empty($_SESSION['uid']))return null;foreach(data_load('users.json') as $u)if((int)$u['id']===(int)$_SESSION['uid'])return $u;return null;}
function banned_user(?array $u):bool{return $u&&!empty($u['banned_until'])&&($u['banned_until']==='permanent'||strtotime($u['banned_until'])>time());}
function ip_ban():?array{$ip=$_SERVER['REMOTE_ADDR']??'';foreach(data_load('bans.json') as $b)if(($b['type']??'')==='ip'&&hash_equals((string)$b['value'],(string)$ip)&&(($b['until']??'permanent')==='permanent'||strtotime($b['until'])>time()))return $b;return null;}
function require_login():array{$u=user();if(!$u){header('Location: login.php');exit;}if(banned_user($u)||ip_ban()){header('Location: banned.php');exit;}return $u;}
function require_admin():array{$u=require_login();if(($u['role']??'user')!=='admin'){http_response_code(403);exit('Доступ запрещён');}return $u;}
function log_action(string $a,string $t,string $r=''):void{$l=data_load('logs.json');$l[]=['id'=>count($l)+1,'action'=>$a,'target'=>$t,'reason'=>$r,'admin'=>user()['username']??'system','time'=>date('c')];data_save('logs.json',$l);}
if(ip_ban()&&basename($_SERVER['SCRIPT_NAME'])!=='banned.php'){header('Location: banned.php');exit;}
