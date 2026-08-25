<?php
declare(strict_types=1);
session_start();

const SITE_NAME = 'GREFFRLEND Forum';
const SITE_URL = 'https://forum.greffrlend.fun';
const ADMIN_EMAIL = 'admins@greffrlend.fun';
const OWNER_EMAIL = 'admins@greffrlend.fun';
const MAIL_FROM = 'admins@greffrlend.fun';
const SERVER_IP = 'play.greffrlend.fun';
const DATA_DIR = __DIR__ . '/data/';
const UPLOAD_DIR = __DIR__ . '/uploads/';
const MAX_ATTACHMENTS = 8;
const MAX_UPLOAD_BYTES = 8 * 1024 * 1024;

if (!is_dir(DATA_DIR)) @mkdir(DATA_DIR, 0750, true);
if (!is_dir(UPLOAD_DIR)) @mkdir(UPLOAD_DIR, 0755, true);

function data_load(string $file, array $default = []): array {
    $path = DATA_DIR . basename($file);
    if (!is_file($path)) return $default;
    $value = json_decode((string)@file_get_contents($path), true);
    return is_array($value) ? $value : $default;
}
function data_save(string $file, array $data): void {
    if (!is_dir(DATA_DIR) && !@mkdir(DATA_DIR, 0750, true)) throw new RuntimeException('Storage unavailable');
    $path = DATA_DIR . basename($file);
    $fp = @fopen($path, 'c+');
    if (!$fp) throw new RuntimeException('Cannot write storage: ' . $file);
    if (!flock($fp, LOCK_EX)) { fclose($fp); throw new RuntimeException('Storage lock failed'); }
    ftruncate($fp, 0); rewind($fp);
    fwrite($fp, json_encode($data, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES));
    fflush($fp); flock($fp, LOCK_UN); fclose($fp);
}
function e(string $value): string { return htmlspecialchars($value, ENT_QUOTES, 'UTF-8'); }
function csrf(): string { if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32)); return $_SESSION['csrf']; }
function check_csrf(): void { if (!hash_equals($_SESSION['csrf'] ?? '', $_POST['csrf'] ?? '')) { http_response_code(419); exit('Недействительный запрос.'); } }
function current_user(): ?array { if (empty($_SESSION['uid'])) return null; foreach (data_load('users.json') as $u) if ((int)($u['id']??0)===(int)$_SESSION['uid']) return $u; return null; }
function user(): ?array { return current_user(); }
function is_owner(?array $u=null): bool { $u ??= current_user(); return $u && (($u['role']??'') === 'owner' || strtolower((string)($u['email']??'')) === OWNER_EMAIL); }
function is_admin(?array $u=null): bool { $u ??= current_user(); return is_owner($u) || ($u && ($u['role']??'') === 'admin'); }
function next_id(array $items): int { $max=0; foreach($items as $item) $max=max($max,(int)($item['id']??0)); return $max+1; }
function client_ip(): string { return (string)($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0'); }
function banned_user(?array $u=null): ?array { $u ??= current_user(); if ($u && !empty($u['ban']['active'])) return $u['ban']; $ip=client_ip(); foreach(data_load('bans.json') as $b){ if(($b['type']??'')==='ip' && hash_equals((string)($b['value']??''),$ip)){ $until=$b['until']??'permanent'; if($until==='permanent'||strtotime((string)$until)>time()) return $b; } } return null; }
function require_login(): array { $u=current_user(); if(!$u){ header('Location: login.php'); exit; } if(banned_user($u)){ header('Location: banned.php'); exit; } return $u; }
function require_admin(): array { $u=require_login(); if(!is_admin($u)){ http_response_code(403); exit('Доступ запрещён'); } return $u; }
function require_owner(): array { $u=require_login(); if(!is_owner($u)){ http_response_code(403); exit('Только владелец форума может выполнить это действие.'); } return $u; }
function log_action(string $action,string $target,string $reason=''): void { $logs=data_load('logs.json'); $logs[]=['id'=>next_id($logs),'action'=>$action,'target'=>$target,'reason'=>$reason,'admin'=>user()['username']??'system','time'=>date('c'),'ip'=>client_ip()]; data_save('logs.json',$logs); }
function rate_limit(string $key,int $seconds=15,int $max=1): bool { $now=time(); $bucket=$_SESSION['rate']??[]; $bucket[$key]=array_values(array_filter($bucket[$key]??[],fn($t)=>$t>$now-$seconds)); if(count($bucket[$key]) >= $max){$_SESSION['rate']=$bucket;return false;} $bucket[$key][]=$now; $_SESSION['rate']=$bucket; return true; }
function clean_text(string $text,int $max=20000): string { $text=trim($text); $text=preg_replace('/\x00/u','',$text)??''; return mb_substr($text,0,$max); }
function allowed_upload(string $tmp,string $name): bool { if(!is_uploaded_file($tmp)||filesize($tmp)>MAX_UPLOAD_BYTES)return false; $mime=(new finfo(FILEINFO_MIME_TYPE))->file($tmp); return in_array($mime,['image/jpeg','image/png','image/gif','image/webp'],true); }
function save_uploads(string $field='attachments'): array { if(empty($_FILES[$field]['name'])||!is_array($_FILES[$field]['name'])) return []; $saved=[];$count=min(count($_FILES[$field]['name']),MAX_ATTACHMENTS); for($i=0;$i<$count;$i++){ $tmp=$_FILES[$field]['tmp_name'][$i]??'';$name=$_FILES[$field]['name'][$i]??''; if(!allowed_upload($tmp,$name)) continue; $mime=(new finfo(FILEINFO_MIME_TYPE))->file($tmp); $ext=['image/jpeg'=>'jpg','image/png'=>'png','image/gif'=>'gif','image/webp'=>'webp'][$mime]??'bin'; $file=bin2hex(random_bytes(16)).'.'.$ext; if(move_uploaded_file($tmp,UPLOAD_DIR.$file)) $saved[]='uploads/'.$file; } return $saved; }
function mail_verification(string $email,string $username,string $url): bool { $subject='Подтверждение E-mail — GREFFRLEND'; $html='<!doctype html><html><body style="margin:0;background:#090909;color:#eee;font-family:Arial,sans-serif"><div style="max-width:620px;margin:30px auto;background:#111;border:1px solid #2c2c2c;border-radius:18px;overflow:hidden"><div style="padding:30px;background:linear-gradient(110deg,#111,#2a1005,#3a0808)"><div style="font-size:28px;font-weight:900;letter-spacing:4px;color:#ff6b00">GREFFRLEND</div></div><div style="padding:32px"><h1 style="margin-top:0">Подтвердите E-mail</h1><p>Привет, '.e($username).'!</p><p>Нажмите кнопку ниже, чтобы подтвердить адрес электронной почты и завершить регистрацию.</p><p><a href="'.e($url).'" style="display:inline-block;padding:14px 22px;background:#f35b12;color:#fff;text-decoration:none;border-radius:9px;font-weight:700">Подтвердить E-mail</a></p><p style="color:#999;font-size:13px">Если кнопка не работает, скопируйте ссылку:<br><a href="'.e($url).'" style="color:#ff7a2b">'.e($url).'</a></p></div><div style="padding:18px 32px;color:#777;border-top:1px solid #222">© 2025 — 2026 GREFFRLEND</div></div></body></html>'; $headers="MIME-Version: 1.0\r\nContent-Type: text/html; charset=UTF-8\r\nFrom: GREFFRLEND <".MAIL_FROM.">\r\nReply-To: ".MAIL_FROM."\r\n"; return @mail($email,$subject,$html,$headers); }

if (($ban=banned_user()) && basename($_SERVER['SCRIPT_NAME']??'')!=='banned.php') { header('Location: banned.php'); exit; }
