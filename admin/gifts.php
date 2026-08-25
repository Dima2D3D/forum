<?php
require_once __DIR__ . '/../config.php';
$me = require_owner();
$gifts = data_load('gifts.json');
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();
    $action = $_POST['action'] ?? '';
    if ($action === 'add') {
        $name = clean_text($_POST['name'] ?? '', 80);
        $emoji = clean_text($_POST['emoji'] ?? '🎁', 8);
        $price = max(1, (int)($_POST['price'] ?? 100));
        if ($name === '') $error = 'Введите название подарка.';
        else {
            $gifts[] = ['id'=>next_id($gifts),'name'=>$name,'emoji'=>$emoji,'price'=>$price,'enabled'=>true];
            data_save('gifts.json',$gifts);
            header('Location: gifts.php'); exit;
        }
    }
    if ($action === 'toggle') {
        $id = (int)($_POST['id'] ?? 0);
        foreach ($gifts as &$gift) if ((int)$gift['id'] === $id) $gift['enabled'] = !empty($gift['enabled']) ? false : true;
        unset($gift); data_save('gifts.json',$gifts); header('Location: gifts.php'); exit;
    }
}
$title='Подарки — GREFFRLEND'; include __DIR__.'/../includes/header.php';
?>
<section class="card"><div class="category">OWNER</div><h1>🎁 Подарки</h1><p class="muted">Только владелец может создавать и отключать подарки.</p></section>
<section class="card"><h2>Добавить подарок</h2><?php if($error):?><div class="card danger"><?=e($error)?></div><?php endif;?><form method="post" class="form"><input type="hidden" name="csrf" value="<?=e(csrf())?>"><input type="hidden" name="action" value="add"><input name="emoji" placeholder="🎁" value="🎁" maxlength="8"><input name="name" placeholder="Название" required maxlength="80"><input name="price" type="number" min="1" value="100" required><button class="btn">Добавить</button></form></section>
<?php foreach($gifts as $gift):?><section class="card thread"><div><h3><?=e($gift['emoji']??'🎁')?> <?=e($gift['name']??'Подарок')?></h3><p class="muted"><?=number_format((int)($gift['price']??0),0,'',' ')?> 🪙 · <?=!empty($gift['enabled'])?'активен':'отключён'?></p></div><form method="post"><input type="hidden" name="csrf" value="<?=e(csrf())?>"><input type="hidden" name="action" value="toggle"><input type="hidden" name="id" value="<?=e((string)$gift['id'])?>"><button class="btn small"><?=!empty($gift['enabled'])?'Отключить':'Включить'?></button></form></section><?php endforeach;?>
<?php include __DIR__.'/../includes/footer.php'; ?>