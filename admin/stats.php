<?php
require_once __DIR__.'/../config.php';
require_once __DIR__.'/../includes/analytics.php';
$me = require_owner();
$days = 30;
$summary = analytics_summary($days);
$online = analytics_online_count();
$today = $summary[date('Y-m-d')] ?? ['views'=>0,'unique'=>0,'pages'=>[]];
$views30 = array_sum(array_column($summary, 'views'));
$unique30 = array_sum(array_column($summary, 'unique'));
$topPages = analytics_top_pages($days, 12);

$title='Статистика — GREFFRLEND';
include __DIR__.'/../includes/header.php';
$maxViews = max(1, max(array_column($summary, 'views')));
?>
<style>
.stats-head{display:flex;justify-content:space-between;align-items:center;gap:15px;flex-wrap:wrap}.stats-live{display:flex;align-items:center;gap:9px;background:#111;border:1px solid #303030;border-radius:12px;padding:10px 14px;font-weight:800}.live-dot{width:10px;height:10px;border-radius:50%;background:#45d483;box-shadow:0 0 14px #45d483;display:inline-block}.stats-grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:15px}.stats-card{background:linear-gradient(145deg,#17120f,#0d0d0d);border:1px solid #38271e;border-radius:16px;padding:20px;box-shadow:0 12px 35px rgba(0,0,0,.3)}.stats-label{color:#ff7620;font-size:11px;font-weight:900;letter-spacing:1.7px}.stats-number{font-size:32px;font-weight:900;color:#fff;margin-top:5px}.stats-muted{color:#888;font-size:13px}.chart{display:flex;align-items:flex-end;gap:7px;height:230px;padding:20px 4px 4px;overflow-x:auto}.bar-wrap{min-width:26px;height:100%;display:flex;align-items:flex-end;justify-content:center;position:relative}.bar{width:100%;min-height:3px;border-radius:7px 7px 2px 2px;background:linear-gradient(180deg,#ff7b20,#df2d29);position:relative}.bar:hover{filter:brightness(1.2)}.bar-label{position:absolute;bottom:-25px;font-size:10px;color:#777;white-space:nowrap;transform:rotate(-45deg);transform-origin:top left}.bar-value{position:absolute;top:-17px;left:50%;transform:translateX(-50%);font-size:10px;color:#aaa}.table{width:100%;border-collapse:collapse}.table th,.table td{text-align:left;padding:11px 8px;border-bottom:1px solid #242424}.table th{color:#ff7620;font-size:11px;text-transform:uppercase;letter-spacing:1px}.online-list{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:10px}.online-item{background:#111;border:1px solid #292929;border-radius:10px;padding:11px}.back{display:inline-block;margin-bottom:15px}.stats-note{padding:14px;border-radius:11px;background:#12100f;border:1px solid #30251f;color:#999;font-size:13px}.stats-note b{color:#eee}@media(max-width:760px){.stats-grid{grid-template-columns:1fr 1fr}.online-list{grid-template-columns:1fr}.chart{gap:5px}}@media(max-width:430px){.stats-grid{grid-template-columns:1fr}}
</style>
<a class="btn back" href="index.php">← В админ-панель</a>
<section class="admin-card admin-hero">
    <div class="stats-head">
        <div><div class="admin-label">LIVE ANALYTICS</div><h1>📊 Статистика форума</h1><p class="admin-muted">Реальные запросы к форуму за последние 30 дней.</p></div>
        <div class="stats-live"><span class="live-dot"></span> Сейчас онлайн: <?=e((string)$online)?></div>
    </div>
</section>
<div class="stats-grid">
    <div class="stats-card"><div class="stats-label">ОНЛАЙН СЕЙЧАС</div><div class="stats-number"><?=e((string)$online)?></div><div class="stats-muted">активность за последние 5 минут</div></div>
    <div class="stats-card"><div class="stats-label">ПРОСМОТРЫ СЕГОДНЯ</div><div class="stats-number"><?=e((string)$today['views'])?></div><div class="stats-muted">все реальные просмотры страниц</div></div>
    <div class="stats-card"><div class="stats-label">УНИКАЛЬНЫЕ СЕГОДНЯ</div><div class="stats-number"><?=e((string)$today['unique'])?></div><div class="stats-muted">уникальные посетители</div></div>
    <div class="stats-card"><div class="stats-label">ПРОСМОТРЫ ЗА 30 ДНЕЙ</div><div class="stats-number"><?=e((string)$views30)?></div><div class="stats-muted">суммарные просмотры</div></div>
</div>
<section class="admin-card"><h2>📈 Посещаемость за 30 дней</h2><div class="chart">
<?php foreach($summary as $date=>$row): $height=round(((int)$row['views']/$maxViews)*100); ?>
    <div class="bar-wrap" title="<?=e($date)?>: <?=e((string)$row['views'])?> просмотров / <?=e((string)$row['unique'])?> уникальных"><div class="bar" style="height:<?=max(2,$height)?>%"><span class="bar-value"><?=e((string)$row['views'])?></span></div><span class="bar-label"><?=e(date('d.m',strtotime($date)))?></span></div>
<?php endforeach; ?>
</div></section>
<section class="admin-card"><h2>👥 Уникальные посетители</h2><p class="stats-muted">Сумма уникальных посетителей по дням за период: <b><?=e((string)$unique30)?></b>. Один человек, посетивший форум в разные дни, учитывается отдельно в каждом дне.</p></section>
<section class="admin-card"><h2>🔥 Популярные страницы</h2><table class="table"><thead><tr><th>Страница</th><th>Просмотры</th></tr></thead><tbody><?php if(!$topPages): ?><tr><td colspan="2" class="stats-muted">Данных пока нет.</td></tr><?php else: foreach($topPages as $path=>$count): ?><tr><td><?=e($path)?></td><td><b><?=e((string)$count)?></b></td></tr><?php endforeach; endif; ?></tbody></table></section>
<section class="admin-card"><h2>🟢 Кто сейчас онлайн</h2><div class="online-list"><?php $rows=data_load('analytics_online.json'); $now=time(); $shown=0; foreach($rows as $row): if((int)($row['last_seen']??0)<$now-300) continue; $shown++; ?><div class="online-item"><b><?=e(($row['username']??'')!==''?'@'.$row['username']:'Гость')?></b><div class="stats-muted"><?=e((string)($row['path']??'/'))?> · <?=e((string)max(0,$now-(int)($row['last_seen']??$now)))?> сек. назад</div></div><?php endforeach; if(!$shown): ?><div class="stats-muted">Сейчас активных посетителей не зафиксировано.</div><?php endif; ?></div></section>
<div class="stats-note"><b>Как считается:</b> форум фиксирует реальные HTTP-запросы пользователей, отбрасывает типичных ботов, считает просмотры страниц и уникальных посетителей по анонимному идентификатору сессии. «Онлайн» — активность за последние 5 минут.</div>
<?php include __DIR__.'/../includes/footer.php'; ?>
