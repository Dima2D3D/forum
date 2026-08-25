<?php
require_once __DIR__.'/config.php';
$u=require_login();if($_SERVER['REQUEST_METHOD']!=='POST')redirect('index.php');check_csrf();
$type=$_POST['type']??'';$action=$_POST['action']??'';$id=(int)($_POST['id']??0);
$threads=data_load('threads.json');$replies=[];$found=null;
if($type==='thread'){foreach($threads as &$t)if((int)$t['id']===$id){$found=&$t;break;}unset($t);if(!$found)exit('Не найдено');$allowed=is_owner($u)||((int)($found['author_id']??0)===(int)$u['id']);if(!$allowed)exit('Нет прав.');if($action==='pin'){require_owner($u);$found['pinned']=!empty($found['pinned'])?false:true;data_save('threads.json',$threads);log_action($found['pinned']?'Закрепление темы':'Открепление темы',(string)$id);}
elseif($action==='delete'){if(!is_owner($u)&&(int)($found['author_id']??0)!==(int)$u['id'])exit('Нет прав.');$threads=array_values(array_filter($threads,fn($t)=>(int)$t['id']!==$id));data_save('threads.json',$threads);log_action('Удаление темы',(string)$id);redirect('index.php');}
elseif($action==='edit'){if(!is_owner($u)&&(int)($found['author_id']??0)!==(int)$u['id'])exit('Нет прав.');$found['title']=clean_text($_POST['title']??$found['title'],120);$found['content']=clean_text($_POST['content']??$found['content'],20000);$found['updated_at']=date('c');data_save('threads.json',$threads);log_action('Изменение темы',(string)$id);}
redirect('thread.php?id='.$id);}
if($type==='reply'){ $file='replies_'.$id.'.json';$replies=data_load($file);$rid=(int)($_POST['reply_id']??0);$idx=null;foreach($replies as $k=>$r)if((int)($r['id']??0)===$rid){$idx=$k;$found=$r;break;}if($idx===null)exit('Не найдено');if(!is_owner($u)&&(int)($found['author_id']??0)!==(int)$u['id'])exit('Нет прав.');if($action==='delete'){array_splice($replies,$idx,1);data_save($file,$replies);log_action('Удаление ответа',(string)$rid);}elseif($action==='edit'){$replies[$idx]['message']=clean_text($_POST['message']??$found['message'],20000);$replies[$idx]['updated_at']=date('c');data_save($file,$replies);log_action('Изменение ответа',(string)$rid);}redirect('thread.php?id='.$id);}
exit('Неизвестное действие.');
