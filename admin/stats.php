<?php
require_once __DIR__.'/../config.php';
$me = require_owner();
require_once __DIR__.'/../includes/analytics.php';

$days = 30;
$summary = analytics_summary($days);
$online = analytics_online_count();
$todayKey = date('Y-m-d');
$today = isset($summary[$todayKey]) && is_array($summary[$todayKey]) ? $summary[$todayKey] : ['views'=>0,'unique'=>0,'pages'=>[]];
$views30 = 0;
$unique30 = 0;
$maxViews = 1;
foreach ($summary as $row) {
    $views30 += (int)($row['views'] ?? 0);
    $unique30 += (int)($row['unique'] ?? 0);
    $maxViews = max($maxViews, (int)($row['views'] ?? 0));
}
$topPages = analytics_top_pages($days, 12);
$onlineRows = data_load('analytics_online.json');
$now = time();

$title='Статистика — GREFFRLEND';
include __DIR__.'/../includes/header.php';
?>
<style>
.stats-head{display:flex;justify-content:space-between;align-items:center;gap:15px;flex-wrap:wrap}.stats-live{display:flex;align-items:center;gap:9px;background:#111;border:1px solid #303030;border-radius:12px;padding:10px 14px;font-weight:800}.live-dot{width:10px;height:10px;border-radius:50%;background:#45d483;box-shadow:0 0 14px #45d483;display:inline-block}.stats-grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:15px}.stats-card{background:linear-gradient(145deg,#17120f,#0d0d0d);border:1px solid #38271e;border-radius:16px;padding:20px;box-shadow:0 12px 35px rgba(0,0,0,.3)}.stats-label{color:#ff7620;font-size:11px;font-weight:900;letter-spacing:1.7px}.stats-number{font-size:32px;font-weight:900;color:#fff;margin-top:5px}.stats-muted{color:#888;font-size:13px}.chart{display:flex;align-items:flex-end;gap:7px;height:230px;padding:20px 4px 35px;overflow-x:auto}.bar-wrap{min-width:26px;height:100%;display:flex;align-items:flex-end;justify-content:center;position:relative}.bar{width:100%;min-height:3px;border-radius:7px 7px 2px 2px;background:linear-gradient(180deg,#ff7b20,#df2d29);position:relative}.bar-label{position:absolute;bottom:-29px;font-size:10px;color:#777;white-space:nowrap;transform:rotate(-45deg);transform-origin:top left}.bar-value{position:absolute;top:-17px;left:50%;transform:translateX(-50%);font-size:10px;color:#aaa}.stats-table{width:100%;border-collapse:collapse}.stats-table th,.stats-table td{text-align:left;padding:11px 8px;border-bottom:1px solid #242424}.stats-table th{color:#ff7620;font-size:11px;text-transform:uppercase;letter-spacing:1px}.online-list{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:10px}.online-item{background:#111;border:1px solid #292929;border-radius:10px;padding:11px}.stats-note{padding:14px;border-radius:11px;background:#12100f;border:1px solid #30251f;color:#999;font-size:13px}.stats-note b{color:#eee}.stats-back{display:inline-block;margin-bottom:15px;background:#171717;border:1px solid #333;color:#ddd!important;border-radius:9px;padding:9px 13px}.stats-back:hover{background:#222;color:#ff7620!important}@media(max-width:760px){.stats-grid{grid-template-columns:1fr 1fr}.online-list{grid-template-columns:1fr}.chart{gap:5px}}@media(max-width:430px){.stats-grid{grid-template-columns:1fr}}
</style>
<a class="stats-back" href="index.php">← В админ-панель</a>
<section class="admin-card admin-hero">
    <div class="stats-head">
        <div><div class="admin-label">LIVE ANALYTICS</div><h1>📊 Статистика форума</h1><p class="admin-muted">Реальные посещения форума за последние 30 дней.</p></div>
        <div class="stats-live"><span class="live-dot"></span> Сейчас онлайн: <?=e((string)$online)?></div>
    </div>
</section>
<div class="stats-grid">
    <div class="stats-card"><div class="stats-label">ОНЛАЙН СЕЙЧАС</div><div class="stats-number"><?=e((string)$online)?></div><div class="stats-muted">активность за последние 5 минут</div></div>
    <div class="stats-card"><div class="stats-label">ПРОСМОТРЫ СЕГОДНЯ</div><div class="stats-number"><?=e((string)($today['views'] ?? 0))?></div><div class="stats-muted">запросы страниц форума</div></div>
    <div class="stats-card"><div class="stats-label">УНИКАЛЬНЫЕ СЕГОДНЯ</div><div class="stats-number"><?=e((string)($today['unique'] ?? 0))?></div><div class="stats-muted">анонимные посетители</div></div>
    <div class="stats-card"><div class="stats-label">ПРОСМОТРЫ ЗА 30 ДНЕЙ</div><div class="stats-number"><?=e((string)$views30)?></div><div class="stats-muted">суммарно</div></div>
</div>
<section class="admin-card"><h2>📈 Посещаемость за 30 дней</h2><div class="chart">
<?php foreach($summary as $date=>$row): $views=(int)($row['views']??0); $height=round(($views/$maxViews)*100); ?>
    <div class="bar-wrap" title="<?=e((string)$date)?>: <?=e((string)$views)?> просмотров / <?=e((string)($row['unique']??0))?> уникальных"><div class="bar" style="height:<?=max(2,$height)?>%"><span class="bar-value"><?=e((string)$views)?></span></div><span class="bar-label"><?=e(date('d.m',strtotime((string)$date)))?></span></div>
<?php endforeach; ?>
</div></section>
<section class="admin-card"><h2>🔥 Популярные страницы</h2><table class="stats-table"><thead><tr><th>Страница</th><th>Просмотры</th></tr></thead><tbody><?php if(!$topPages): ?><tr><td colspan="2" class="stats-muted">Данных пока нет. Откройте несколько страниц форума.</td></tr><?php else: foreach($topPages as $path=>$count): ?><tr><td><?=e((string)$path)?></td><td><b><?=e((string)$count)?></b></td></tr><?php endforeach; endif; ?></tbody></table></section>
<section class="admin-card"><h2>🟢 Кто сейчас онлайн</h2><div class="online-list"><?php $shown=0; foreach($onlineRows as $row): $last=(int)($row['last_seen']??0); if($last<$now-300) continue; $shown++; $name=(string)($row['username']??''); ?><div class="online-item"><b><?=e($name!==''?'@'.$name:'Гость')?></b><div class="stats-muted"><?=e((string)($row['path']??'/'))?> · <?=e((string)max(0,$now-$last))?> сек. назад</div></div><?php endforeach; if(!$shown): ?><div class="stats-muted">Сейчас активных посетителей не зафиксировано.</div><?php endif; ?></div></section>
<section class="admin-card"><h2>📊 Уникальные посетители</h2><p class="stats-muted">За 30 дней накоплено <?=e((string)$unique30)?> дневных уникальных посещений. Один и тот же человек в разные дни считается отдельным дневным посетителем.</p></section>
<div class="stats-note"><b>Важно:</b> статистика собирается самим форумом, без Google Analytics. Боты отбрасываются, IP-адреса отдельно для аналитики не сохраняются. Онлайн означает активность посетителя за последние 5 минут.</div>
<?php include __DIR__.'/../includes/footer.php'; ?>
