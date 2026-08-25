<?php
require_once __DIR__.'/config.php';
$u=require_login();
if($_SERVER['REQUEST_METHOD']!=='POST')redirect('index.php');
check_csrf();
if(!rate_limit('reply',10,1))exit('Слишком часто. Подождите несколько секунд.');
$id=(int)($_POST['thread_id']??0);$msg=clean_text($_POST['message']??'',20000);if($msg==='')exit('Пустой ответ.');
$r=data_load('replies_'.$id.'.json');
if(count($r)>0){$last=end($r);if(($last['author_id']??0)===$u['id']&&time()-strtotime((string)($last['created_at']??''))<10)exit('Слишком частые сообщения.');}
$attachments=save_uploads('attachments');
$r[]=['id'=>next_id($r),'author'=>$u['username'],'author_id'=>$u['id'],'message'=>$msg,'attachments'=>$attachments,'created_at'=>date('c')];
data_save('replies_'.$id.'.json',$r);redirect('thread.php?id='.$id);
