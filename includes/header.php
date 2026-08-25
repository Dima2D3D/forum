<?php
require_once __DIR__ . '/../config.php';
if (is_file(__DIR__ . '/social.php')) {
    require_once __DIR__ . '/social.php';
}
$me = current_user();
$pageTitle = isset($title) && $title !== '' ? $title : SITE_NAME;
$requestUri = isset($_SERVER['REQUEST_URI']) ? (string)$_SERVER['REQUEST_URI'] : '';
$adminPage = strpos($requestUri, '/admin/') !== false;
?>
<!doctype html>
<html lang="ru">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($pageTitle) ?></title>
<meta name="description" content="Официальный форум GREFFRLEND — Minecraft Community">
<meta name="robots" content="index,follow">
<style>
*{box-sizing:border-box}
html{background:#080808;color-scheme:dark}
body{margin:0;background:#080808;color:#eee;font:15px/1.65 Arial,Helvetica,sans-serif}
a{color:#ff7620;text-decoration:none}
a:hover{color:#ff3d2d}
.site-header{background:#0c0c0c;border-bottom:1px solid #292929;box-shadow:0 10px 35px rgba(0,0,0,.35);position:sticky;top:0;z-index:50}
.header-inner,.page,.server-strip,.footer-inner{width:min(1160px,94%);margin:auto}
.header-inner{min-height:72px;display:flex;align-items:center;justify-content:space-between;gap:20px}
.logo{font-size:28px;font-weight:900;letter-spacing:5px;text-decoration:none!important;background:linear-gradient(100deg,#ff941c,#ff4b18,#df202b,#ff941c);background-size:220% 100%;color:transparent!important;background-clip:text;-webkit-background-clip:text}
.main-nav{display:flex;gap:6px;align-items:center;flex-wrap:wrap}
.main-nav a{color:#bbb!important;padding:8px 11px;border-radius:9px;font-weight:700}
.main-nav a:hover{background:#191919;color:#ff7820!important}
.notif-badge{background:#e52d25;color:#fff;border-radius:20px;padding:1px 6px;font-size:11px}
.server-strip{padding:13px 0;display:flex;align-items:center;gap:12px;color:#888;border-bottom:1px solid #181818}
.server-strip b{color:#eee}
.server-strip button{background:#15110e;color:#ff7920;border:1px solid #3c2a20;border-radius:9px;padding:9px 13px;cursor:pointer}
.page{padding:28px 0;min-height:70vh}
.hero{padding:50px 34px;margin-bottom:24px;border:1px solid #34251d;border-radius:18px;background:radial-gradient(circle at 90% 10%,#431407,transparent 35%),linear-gradient(135deg,#101010,#160b06)}
.hero h1{font-size:54px;line-height:1;margin:8px 0 15px}
.hero h1 span{color:#ff6616}
.eyebrow,.category{font-size:11px;text-transform:uppercase;letter-spacing:1.8px;color:#ff7620!important;font-weight:900}
.card{background:linear-gradient(145deg,#141414,#0e0e0e);border:1px solid #292929;border-radius:15px;padding:21px;margin:14px 0;box-shadow:0 12px 35px rgba(0,0,0,.24)}
.card:hover{border-color:#4a3020}
h1,h2,h3,h4,b,strong{color:#fff}
.muted{color:#888!important}
.stat{font-size:30px;font-weight:900;color:#ff711c}
.grid{display:grid;grid-template-columns:repeat(2,1fr);gap:15px}
.admin-grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:15px}
.button-row{display:flex;gap:10px;flex-wrap:wrap}
.btn{display:inline-block;background:linear-gradient(105deg,#ff6a12,#e72d27);color:#fff!important;border:0;border-radius:10px;padding:11px 17px;font-weight:800;text-decoration:none!important;cursor:pointer}
.thread{display:flex;align-items:center;justify-content:space-between;gap:20px}
.billboard{padding:22px;margin:20px 0;border:1px solid #3d2113;border-radius:13px;background:linear-gradient(110deg,#170b05,#241008,#0d0707)}
.post-body{margin-top:15px;white-space:pre-wrap;overflow-wrap:anywhere}
.attachments{display:flex;flex-wrap:wrap;gap:10px;margin-top:15px}
.attachments img{width:min(240px,100%);height:180px;max-width:240px;max-height:180px;border-radius:9px;object-fit:cover;border:1px solid #333}
.reactions{display:flex;gap:8px;margin-top:15px;flex-wrap:wrap}
.reaction{border:1px solid #333;background:#171717;color:#ddd;padding:6px 11px;border-radius:8px;cursor:pointer}
.form{max-width:720px}
.form input,.form textarea,.form select,input,textarea,select{font:inherit;width:100%;background:#0a0a0a;color:#eee;border:1px solid #343434;border-radius:9px;padding:12px;margin:7px 0 14px;outline:none}
.form textarea{min-height:170px;resize:vertical}
.emoji{display:flex;gap:5px;flex-wrap:wrap;margin-bottom:8px}
.emoji button{background:#171717;color:#eee;border:1px solid #303030;border-radius:6px;padding:5px;cursor:pointer}
.site-footer{margin-top:60px;background:#0a0a0a;border-top:1px solid #292929;color:#777}
.footer-inner{padding:30px 0;display:flex;justify-content:space-between;gap:25px}
.footer-brand{display:flex;align-items:center;gap:10px}
.footer-brand strong{font-size:20px}
.footer-brand span{display:block;color:#777;font-size:14px}
.footer-links{display:flex;gap:16px;flex-wrap:wrap;align-items:center}
.footer-links a{color:#aaa!important;font-weight:700}
.footer-links a:hover{color:#ff7820!important}
.site-icon{width:42px!important;height:42px!important;max-width:42px!important;max-height:42px!important;object-fit:cover;border-radius:10px}
.copyright{text-align:center;padding:16px;border-top:1px solid #202020;color:#666}
.admin-page .card{border-color:#302820}
.admin-page .btn{box-shadow:0 6px 18px rgba(255,75,10,.16)}
@media(max-width:760px){.header-inner{align-items:flex-start;flex-direction:column;padding:13px 0}.main-nav{gap:5px}.hero{padding:35px 22px}.hero h1{font-size:39px}.grid,.admin-grid{grid-template-columns:1fr}.thread{align-items:flex-start;flex-direction:column}.footer-inner{flex-direction:column}}
</style>
</head>
<body class="<?= $adminPage ? 'admin-page' : '' ?>">
<header class="site-header">
<div class="header-inner">
<a class="logo" href="<?= e(SITE_URL) ?>/index.php">GREFFRLEND</a>
<nav class="main-nav">
<a href="<?= e(SITE_URL) ?>/index.php">Форум</a>
<a href="<?= e(SITE_URL) ?>/search.php">Поиск</a>
<a href="<?= e(SITE_URL) ?>/recommendations.php">Рекомендации</a>
<?php if ($me): ?>
<a href="<?= e(SITE_URL) ?>/notifications.php">🔔</a>
<a href="<?= e(SITE_URL) ?>/profile.php?id=<?= (int)$me['id'] ?>"><?= e((string)$me['username']) ?></a>
<?php if (is_admin($me)): ?><a href="<?= e(SITE_URL) ?>/admin/index.php">Админка</a><?php endif; ?>
<?php else: ?>
<a href="<?= e(SITE_URL) ?>/login.php">Войти</a>
<?php endif; ?>
</nav>
</div>
</header>
<div class="server-strip">
<span>Сервер: <b><?= e(SERVER_IP) ?></b></span>
<button type="button" onclick="copyIP()">Копировать IP</button>
</div>
<main class="page">
