<?php
// ========== НАСТРОЙКИ ==========
session_start();
error_reporting(0);

$data_file = 'forum_data.txt';
$admin_file = 'admin_data.txt';
$users_file = 'users_txt.txt';
$online_file = 'online_users.txt';
$upload_dir = 'uploads/';
$banner_dir = 'banners/';
$messages_file = 'private_messages.txt';

// Создаём папки
if (!file_exists($upload_dir)) mkdir($upload_dir, 0755, true);
if (!file_exists($banner_dir)) mkdir($banner_dir, 0755, true);

// Создаём файлы
if (!file_exists($data_file)) file_put_contents($data_file, json_encode([]));
if (!file_exists($admin_file)) { 
    file_put_contents($admin_file, json_encode([
        'bans' => [], 
        'mutes' => [], 
        'roles' => [], 
        'verified' => [], 
        'moderation' => [],
        'banner_image' => '',
        'banner_width' => 728,
        'banner_height' => 90
    ])); 
}
if (!file_exists($users_file)) { 
    file_put_contents($users_file, json_encode([
        'admins@greffrlend.fun' => [
            'pass' => 'Dima2D3D_YT_owner_op_01132014', 
            'avatar' => '', 
            'about' => '',
            'custom_color' => '#ff3b30',
            'custom_title' => 'Главный Администратор',
            'badge_text' => '👑'
        ]
    ])); 
}
if (!file_exists($online_file)) file_put_contents($online_file, json_encode([]));
if (!file_exists($messages_file)) file_put_contents($messages_file, json_encode([]));

// Загружаем данные
$forum_data = json_decode(file_get_contents($data_file), true);
$admin_data = json_decode(file_get_contents($admin_file), true);
$users_data = json_decode(file_get_contents($users_file), true);
$online_data = json_decode(file_get_contents($online_file), true);
$messages_data = json_decode(file_get_contents($messages_file), true);

// ========== ОНЛАЙН ==========
$current_time = time();
$user_ip = $_SERVER['REMOTE_ADDR'];
$online_data[$user_ip] = $current_time;
foreach ($online_data as $ip => $last) {
    if ($current_time - $last > 300) unset($online_data[$ip]);
}
file_put_contents($online_file, json_encode($online_data));
$online_count = count($online_data);

// ========== ФУНКЦИИ ==========
function getDisplayName($name) {
    global $users_data;
    $lower = strtolower($name);
    if (isset($users_data[$lower]['custom_title']) && !empty($users_data[$lower]['custom_title'])) {
        return $users_data[$lower]['custom_title'];
    }
    if (strcasecmp($name, 'admins@greffrlend.fun') === 0) return 'Главный Администратор';
    return $name;
}

function getUserColor($name) {
    global $users_data;
    $lower = strtolower($name);
    if (isset($users_data[$lower]['custom_color'])) return $users_data[$lower]['custom_color'];
    if (strcasecmp($name, 'admins@greffrlend.fun') === 0) return '#ff3b30';
    return '#f5f6f7';
}

function getUserBadge($name) {
    global $users_data;
    $lower = strtolower($name);
    if (isset($users_data[$lower]['badge_text']) && !empty($users_data[$lower]['badge_text'])) {
        return $users_data[$lower]['badge_text'];
    }
    return '';
}

function getBannerHTML() {
    global $admin_data;
    if (!empty($admin_data['banner_image'])) {
        return '<img src="' . $admin_data['banner_image'] . '" style="max-width:100%; height:auto; border-radius:8px;" alt="Реклама">';
    }
    return '<div style="background:linear-gradient(135deg,#1c1f26,#2a2e35); padding:20px; border-radius:8px; text-align:center; color:#768390; border:2px dashed #2a2e35; font-weight:600; font-size:14px;">📢 Здесь может быть ваша реклама!<br><span style="font-size:12px; font-weight:400;">Размещение: ' . ($admin_data['banner_width'] ?? 728) . 'x' . ($admin_data['banner_height'] ?? 90) . 'px</span></div>';
}

function handleImageUpload($file_input, $target_dir = 'uploads/') {
    global $upload_dir, $banner_dir;
    if (isset($_FILES[$file_input]) && $_FILES[$file_input]['error'] === UPLOAD_ERR_OK) {
        $file_tmp = $_FILES[$file_input]['tmp_name'];
        $file_name = basename($_FILES[$file_input]['name']);
        $ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        if (in_array($ext, $allowed)) {
            $new_name = uniqid('img_', true) . '.' . $ext;
            $dir = ($target_dir === 'banners/') ? $banner_dir : $upload_dir;
            if (move_uploaded_file($file_tmp, $dir . $new_name)) { return '/' . $dir . $new_name; }
        }
    }
    return '';
}

function isLoggedIn() {
    return isset($_SESSION['forum_user']);
}

function getLoggedUser() {
    return isset($_SESSION['forum_user']) ? $_SESSION['forum_user'] : null;
}

function isAdmin() {
    $user = getLoggedUser();
    return $user && strtolower($user) === 'admins@greffrlend.fun';
}

function canDelete($user) {
    global $admin_data;
    $lower = strtolower($user);
    if ($lower === 'admins@greffrlend.fun') return true;
    return isset($admin_data['roles'][$lower]) && in_array($admin_data['roles'][$lower], ['helper', 'mod', 'admin_role']);
}

// ========== ОБРАБОТКА POST ==========
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    // === ВХОД ===
    if ($action === 'login') {
        $user = trim($_POST['username']);
        $pass = trim($_POST['password']);
        $lower = strtolower($user);
        
        $pass_valid = false;
        if ($lower === 'admins@greffrlend.fun' && $pass === 'admin123') {
            $pass_valid = true;
        } elseif (isset($users_data[$lower]) && $users_data[$lower]['pass'] === $pass) {
            $pass_valid = true;
        }
        
        if (!$pass_valid) {
            $login_error = '❌ Неверный логин или пароль!';
        } else {
            $_SESSION['forum_user'] = $user;
            header('Location: ?page=home');
            exit;
        }
    }
    
    // === РЕГИСТРАЦИЯ ===
    if ($action === 'register') {
        $user = trim($_POST['username']);
        $pass = trim($_POST['password']);
        $agree = isset($_POST['agree_policy']);
        $lower = strtolower($user);
        
        if (!$agree) {
            $reg_error = '❌ Примите Политику конфиденциальности!';
        } elseif ($lower === 'admins@greffrlend.fun') {
            $reg_error = '❌ Этот ник зарезервирован!';
        } elseif (isset($users_data[$lower])) {
            $reg_error = '❌ Ник уже занят!';
        } elseif (empty($user) || empty($pass)) {
            $reg_error = '❌ Заполните все поля!';
        } else {
            $users_data[$lower] = [
                'pass' => $pass,
                'avatar' => '',
                'about' => '',
                'custom_color' => '#f5f6f7',
                'custom_title' => '',
                'badge_text' => ''
            ];
            file_put_contents($users_file, json_encode($users_data));
            $_SESSION['forum_user'] = $user;
            header('Location: ?page=home');
            exit;
        }
    }
    
    // === ВЫХОД ===
    if ($action === 'logout') {
        session_destroy();
        header('Location: index.php');
        exit;
    }
    
    // === ДЕЙСТВИЯ ДЛЯ АВТОРИЗОВАННЫХ ===
    if (isLoggedIn()) {
        $username = getLoggedUser();
        $user_lower = strtolower($username);
        
        // Проверка бана
        if (in_array($user_lower, $admin_data['bans'])) {
            session_destroy();
            die("<div style='background:#0b0c0f; color:#ff3b30; font-family:Arial; height:100vh; display:flex; flex-direction:column; justify-content:center; align-items:center; font-size:24px; font-weight:bold;'><div>Ошибка 140, вас забанили</div><a href='/' style='color:#0a84ff; font-size:16px; margin-top:20px; text-decoration:none;'>На главную</a></div>");
        }
        
        // Проверка мута
        if (in_array($user_lower, $admin_data['mutes']) && !in_array($action, ['update_profile', 'admin_action', 'logout', 'send_pm'])) {
            die("<script>alert('⛔ Вы не можете писать, у вас МУТ!'); window.history.back();</script>");
        }
        
        // === ОБНОВЛЕНИЕ ПРОФИЛЯ ===
        if ($action === 'update_profile') {
            if (!isset($users_data[$user_lower])) $users_data[$user_lower] = [];
            $users_data[$user_lower]['about'] = htmlspecialchars(trim($_POST['about_text'] ?? ''));
            $users_data[$user_lower]['custom_color'] = htmlspecialchars(trim($_POST['custom_color'] ?? '#f5f6f7'));
            $users_data[$user_lower]['custom_title'] = htmlspecialchars(trim($_POST['custom_title'] ?? ''));
            $users_data[$user_lower]['badge_text'] = htmlspecialchars(trim($_POST['badge_text'] ?? ''));
            $uploaded_avatar = handleImageUpload('avatar_file');
            if (!empty($uploaded_avatar)) { $users_data[$user_lower]['avatar'] = $uploaded_avatar; }
            file_put_contents($users_file, json_encode($users_data));
            header("Location: ?page=settings");
            exit;
        }
        
        // === СОЗДАНИЕ ТЕМЫ ===
        if ($action === 'create_thread') {
            if (in_array($user_lower, $admin_data['mutes'])) {
                die("<script>alert('⛔ У вас МУТ!'); history.back();</script>");
            }
            $title = htmlspecialchars(trim($_POST['title']));
            $content = htmlspecialchars(trim($_POST['content']));
            if (!empty($title) && !empty($content)) {
                $new_id = uniqid();
                $post_image = handleImageUpload('post_file');
                $thread_payload = [
                    'id' => $new_id,
                    'title' => $title,
                    'author' => $username,
                    'content' => str_replace("\n", "<br>", $content),
                    'image' => $post_image,
                    'date' => date('d.m.Y H:i'),
                    'replies' => [],
                    'pinned' => false
                ];
                if (isAdmin()) {
                    $forum_data[$new_id] = $thread_payload;
                    file_put_contents($data_file, json_encode($forum_data));
                } else {
                    $admin_data['moderation'][$new_id] = $thread_payload;
                    file_put_contents($admin_file, json_encode($admin_data));
                    echo "<script>alert('✅ Тема отправлена на проверку администрации!'); window.location.href='?page=home';</script>";
                    exit;
                }
            }
            header("Location: ?page=home");
            exit;
        }
        
        // === ОТВЕТ В ТЕМЕ ===
        if ($action === 'add_reply') {
            if (in_array($user_lower, $admin_data['mutes'])) {
                die("<script>alert('⛔ У вас МУТ!'); history.back();</script>");
            }
            $t_id = $_POST['thread_id'] ?? '';
            $content = htmlspecialchars(trim($_POST['reply_content']));
            if (!empty($content) && isset($forum_data[$t_id])) {
                $forum_data[$t_id]['replies'][] = [
                    'author' => $username,
                    'content' => str_replace("\n", "<br>", $content),
                    'date' => date('d.m.Y H:i')
                ];
                file_put_contents($data_file, json_encode($forum_data));
            }
            header("Location: ?page=thread&id=" . $t_id);
            exit;
        }
        
        // === УДАЛЕНИЕ ТЕМЫ ===
        if ($action === 'delete_thread') {
            $t_id = $_POST['thread_id'] ?? '';
            if (!empty($t_id) && isset($forum_data[$t_id]) && canDelete($username)) {
                unset($forum_data[$t_id]);
                file_put_contents($data_file, json_encode($forum_data));
            }
            header("Location: ?page=home");
            exit;
        }
        
        // === УДАЛЕНИЕ КОММЕНТАРИЯ ===
        if ($action === 'delete_reply') {
            $t_id = $_POST['thread_id'] ?? '';
            $reply_index = intval($_POST['reply_index'] ?? -1);
            if (!empty($t_id) && isset($forum_data[$t_id]) && $reply_index >= 0 && canDelete($username)) {
                if (isset($forum_data[$t_id]['replies'][$reply_index])) {
                    array_splice($forum_data[$t_id]['replies'], $reply_index, 1);
                    file_put_contents($data_file, json_encode($forum_data));
                }
            }
            header("Location: ?page=thread&id=" . $t_id);
            exit;
        }
        
        // === ЛИЧНЫЕ СООБЩЕНИЯ ===
        if ($action === 'send_pm') {
            if (in_array($user_lower, $admin_data['mutes'])) {
                die("<script>alert('⛔ У вас МУТ!'); history.back();</script>");
            }
            $recipient = htmlspecialchars(trim($_POST['recipient']));
            $content = htmlspecialchars(trim($_POST['pm_content']));
            if (!empty($recipient) && !empty($content)) {
                $messages_data[] = [
                    'id' => uniqid(),
                    'from' => $username,
                    'to' => $recipient,
                    'content' => str_replace("\n", "<br>", $content),
                    'date' => date('d.m.Y H:i'),
                    'read' => false
                ];
                file_put_contents($messages_file, json_encode($messages_data));
            }
            header("Location: ?page=messages");
            exit;
        }
        
        // === АДМИН ДЕЙСТВИЯ ===
        if ($action === 'admin_action') {
            $admin_action = $_POST['admin_action'];
            $target = trim($_POST['target_user'] ?? '');
            $target_lower = strtolower($target);
            $redirect = $_POST['redirect'] ?? '?page=home';
            
            // Защита от бана/мута админа
            if ($target_lower === 'admins@greffrlend.fun' && in_array($admin_action, ['ban', 'mute', 'delete_account'])) {
                echo "<script>alert('❌ Нельзя банить/мутить/удалять главного админа!'); history.back();</script>";
                exit;
            }
            
            // === БАН ===
            if ($admin_action === 'ban' && !empty($target)) {
                if (!in_array($target_lower, $admin_data['bans'])) {
                    $admin_data['bans'][] = $target_lower;
                    file_put_contents($admin_file, json_encode($admin_data));
                    echo "<script>alert('✅ Игрок $target забанен!'); location.href='$redirect';</script>";
                    exit;
                }
            }
            
            // === РАЗБАН ===
            if ($admin_action === 'unban' && !empty($target)) {
                if (in_array($target_lower, $admin_data['bans'])) {
                    $admin_data['bans'] = array_diff($admin_data['bans'], [$target_lower]);
                    file_put_contents($admin_file, json_encode($admin_data));
                    echo "<script>alert('✅ Игрок $target разбанен!'); location.href='$redirect';</script>";
                    exit;
                }
            }
            
            // === МУТ ===
            if ($admin_action === 'mute' && !empty($target)) {
                if (!in_array($target_lower, $admin_data['mutes'])) {
                    $admin_data['mutes'][] = $target_lower;
                    file_put_contents($admin_file, json_encode($admin_data));
                    echo "<script>alert('✅ Игрок $target замучен!'); location.href='$redirect';</script>";
                    exit;
                }
            }
            
            // === РАЗМУТ ===
            if ($admin_action === 'unmute' && !empty($target)) {
                if (in_array($target_lower, $admin_data['mutes'])) {
                    $admin_data['mutes'] = array_diff($admin_data['mutes'], [$target_lower]);
                    file_put_contents($admin_file, json_encode($admin_data));
                    echo "<script>alert('✅ Игрок $target размучен!'); location.href='$redirect';</script>";
                    exit;
                }
            }
            
            // === ВЫДАЧА РОЛИ ===
            if ($admin_action === 'set_role' && !empty($target)) {
                $role = $_POST['role_val'] ?? 'user';
                if ($role === 'user') {
                    unset($admin_data['roles'][$target_lower]);
                } else {
                    $admin_data['roles'][$target_lower] = $role;
                }
                file_put_contents($admin_file, json_encode($admin_data));
                echo "<script>alert('✅ Роль выдана игроку $target!'); location.href='$redirect';</script>";
                exit;
            }
            
            // === ГАЛОЧКА ===
            if ($admin_action === 'toggle_verify' && !empty($target)) {
                if (in_array($target_lower, $admin_data['verified'])) {
                    $admin_data['verified'] = array_diff($admin_data['verified'], [$target_lower]);
                } else {
                    $admin_data['verified'][] = $target_lower;
                }
                file_put_contents($admin_file, json_encode($admin_data));
                echo "<script>alert('✅ Галочка обновлена для $target!'); location.href='$redirect';</script>";
                exit;
            }
            
            // === ЗАКРЕПЛЕНИЕ ТЕМЫ ===
            if ($admin_action === 'pin_thread' && isset($_POST['thread_id'])) {
                $id = $_POST['thread_id'];
                if (isset($forum_data[$id])) {
                    $forum_data[$id]['pinned'] = !$forum_data[$id]['pinned'];
                    file_put_contents($data_file, json_encode($forum_data));
                }
                header('Location: ?page=thread&id=' . $id);
                exit;
            }
            
            // === БАННЕР ===
            if ($admin_action === 'upload_banner') {
                $banner_path = handleImageUpload('banner_file', 'banners/');
                if (!empty($banner_path)) {
                    $admin_data['banner_image'] = $banner_path;
                    $admin_data['banner_width'] = intval($_POST['banner_width'] ?? 728);
                    $admin_data['banner_height'] = intval($_POST['banner_height'] ?? 90);
                    file_put_contents($admin_file, json_encode($admin_data));
                }
                header('Location: ?page=admin');
                exit;
            }
            
            if ($admin_action === 'remove_banner') {
                $admin_data['banner_image'] = '';
                file_put_contents($admin_file, json_encode($admin_data));
                header('Location: ?page=admin');
                exit;
            }
            
            // === УДАЛЕНИЕ АККАУНТА ===
            if ($admin_action === 'delete_account' && !empty($target) && $target_lower !== 'admins@greffrlend.fun') {
                unset($users_data[$target_lower]);
                file_put_contents($users_file, json_encode($users_data));
                $admin_data['bans'] = array_diff($admin_data['bans'], [$target_lower]);
                $admin_data['mutes'] = array_diff($admin_data['mutes'], [$target_lower]);
                unset($admin_data['roles'][$target_lower]);
                $admin_data['verified'] = array_diff($admin_data['verified'], [$target_lower]);
                file_put_contents($admin_file, json_encode($admin_data));
                
                $messages_data = array_filter($messages_data, function($m) use ($target_lower) {
                    return strtolower($m['from']) !== $target_lower && strtolower($m['to']) !== $target_lower;
                });
                file_put_contents($messages_file, json_encode(array_values($messages_data)));
                
                echo "<script>alert('✅ Аккаунт $target удалён!'); location.href='?page=admin';</script>";
                exit;
            }
            
            // === ПРОСМОТР ПЕРЕПИСКИ ===
            if ($admin_action === 'view_messages' && !empty($target)) {
                $msgs = array_filter($messages_data, function($m) use ($target_lower) {
                    return strtolower($m['from']) === $target_lower || strtolower($m['to']) === $target_lower;
                });
                echo "<div style='background:#0b0c0f; color:#f5f6f7; padding:20px; font-family:Arial; max-width:800px; margin:0 auto;'>";
                echo "<h2 style='font-size:28px;'>💬 Переписка " . htmlspecialchars($target) . "</h2>";
                echo "<a href='?page=admin' style='color:#0a84ff; font-size:18px;'>← Назад</a>";
                echo "<div style='margin-top:20px;'>";
                foreach ($msgs as $m) {
                    $c = strtolower($m['from']) === $target_lower ? '#ff3b30' : '#0a84ff';
                    echo "<div style='border-bottom:1px solid #2a2e35; padding:15px;'>";
                    echo "<div style='color:#768390; font-size:16px;'>" . htmlspecialchars($m['from']) . " → " . htmlspecialchars($m['to']) . " | " . $m['date'] . "</div>";
                    echo "<div style='color:" . $c . "; font-size:18px; margin-top:8px;'>" . $m['content'] . "</div>";
                    echo "</div>";
                }
                echo "</div></div>";
                exit;
            }
            
            // === МОДЕРАЦИЯ ПОСТОВ ===
            if ($admin_action === 'approve_post' && isset($_POST['post_id'])) {
                $id = $_POST['post_id'];
                if (isset($admin_data['moderation'][$id])) {
                    $forum_data[$id] = $admin_data['moderation'][$id];
                    unset($admin_data['moderation'][$id]);
                    file_put_contents($data_file, json_encode($forum_data));
                    file_put_contents($admin_file, json_encode($admin_data));
                }
                header('Location: ?page=home');
                exit;
            }
            
            if ($admin_action === 'reject_post' && isset($_POST['post_id'])) {
                unset($admin_data['moderation'][$_POST['post_id']]);
                file_put_contents($admin_file, json_encode($admin_data));
                header('Location: ?page=home');
                exit;
            }
            
            header('Location: ?page=home');
            exit;
        }
    }
}

// ========== ГЛОБАЛЬНАЯ ПРОВЕРКА БАНА ==========
$logged_user = getLoggedUser();
if ($logged_user && in_array(strtolower($logged_user), $admin_data['bans'])) {
    session_destroy();
    die("<div style='background:#0b0c0f; color:#ff3b30; font-family:Arial; height:100vh; display:flex; flex-direction:column; justify-content:center; align-items:center; font-size:32px; font-weight:bold; padding:20px; text-align:center;'>
        <div style='font-size:60px; margin-bottom:20px;'>⛔</div>
        <div>Ошибка 140 - Вас забанили!</div>
        <div style='color:#768390; font-size:20px; font-weight:400; margin-top:10px;'>Вы не можете просматривать форум</div>
        <a href='index.php' style='color:#0a84ff; font-size:18px; margin-top:30px; text-decoration:none; border:2px solid #0a84ff; padding:12px 30px; border-radius:10px;'>На главную</a>
    </div>");
}

$page = isset($_GET['page']) ? $_GET['page'] : 'home';
$thread_id = isset($_GET['id']) ? $_GET['id'] : null;
$profile_user = isset($_GET['user']) ? $_GET['user'] : null;
$msg = isset($_GET['msg']) ? $_GET['msg'] : '';
?>
<!DOCTYPE html>
<html lang="ru">
<head>
<meta charset="UTF-8">
<meta name="GREFFRLEND" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
<title>Форум | greffrlend</title>
<link rel="icon" href="/uploads/favicon.png" type="image/png" />
<link rel="shortcut icon" href="/uploads/favicon.png" />
<meta property="og:title" content="GREFFRLEND - Уютненький сервер" />
    <meta property="og:description" content="Форум сообщества Minecraft сервера GREFFRLEND. Анархия, читерам бан, вайпы, уютное комьюнити. Заходи на play.greffrlend.fun!" />
    <meta property="og:image" content="https://forum.greffrlend.fun/uploads/forum-banner.png" />
    <meta property="og:image:width" content="1200" />
    <meta property="og:image:height" content="630" />
    <meta property="og:url" content="https://forum.greffrlend.fun" />
    <meta property="og:type" content="website" />
    <meta property="og:site_name" content="GREFFFRLEND" />
    <link rel="image_src" href="https://forum.greffrlend.fun/uploads/forum-banner.png" />
<style>
* { margin:0; padding:0; box-sizing:border-box; }
body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Arial, sans-serif; background: #0b0c0f; color: #f5f6f7; padding-top: 65px; font-size: 18px; line-height: 1.6; }
header { background: #16181d; border-bottom: 1px solid #2a2e35; position: fixed; top: 0; left: 0; width: 100%; height: 60px; z-index: 1000; }
.header-container { max-width: 1200px; margin: 0 auto; height: 100%; display: flex; justify-content: space-between; align-items: center; padding: 0 15px; }
.logo { font-size: 22px; font-weight: 800; text-transform: uppercase; color: #fff; text-decoration: none; }
.logo span { color: #ff3b30; }
.nav-links { display: flex; gap: 8px; align-items: center; flex-wrap: nowrap; overflow-x: auto; -webkit-overflow-scrolling: touch; }
.nav-links a, .nav-links button { color: #adbac7; text-decoration: none; font-size: 14px; padding: 6px 12px; border-radius: 6px; background: none; border: none; cursor: pointer; white-space: nowrap; }
.nav-links a:hover, .nav-links a.active { color: #fff; background: #22272e; }
.ip-badge { background: #ff3b30; color: #fff; padding: 6px 12px; border-radius: 20px; cursor: pointer; font-weight: 600; font-size: 13px; border: none; }
.container { max-width: 100%; margin: 0 auto; padding: 0 12px; display: flex; gap: 16px; flex-direction: column; }
.main { flex: 1; min-width: 0; }
.sidebar { width: 100%; flex-shrink: 0; }
.card { background: #16181d; border: 1px solid #2a2e35; border-radius: 12px; padding: 16px; margin-bottom: 14px; }
.card-title { font-size: 16px; font-weight: 700; color: #768390; text-transform: uppercase; margin-bottom: 12px; border-bottom: 1px solid #2a2e35; padding-bottom: 8px; }
input, textarea, select { width: 100%; padding: 14px; margin-bottom: 12px; border-radius: 8px; border: 1px solid #373e47; background: #22272e; color: #f5f6f7; box-sizing: border-box; font-size: 16px; }
input:focus, textarea:focus, select:focus { border-color: #ff3b30; outline: none; }
.btn { background: #ff3b30; color: #fff; border: none; padding: 14px 20px; border-radius: 8px; cursor: pointer; font-weight: 700; width: 100%; text-transform: uppercase; font-size: 16px; transition: 0.2s; }
.btn:hover { background: #e03126; }
.btn-green { background: #34c759; }
.btn-green:hover { background: #28a745; }
.btn-blue { background: #0a84ff; }
.btn-blue:hover { background: #0066cc; }
.btn-purple { background: #bf5af2; }
.btn-purple:hover { background: #a855e0; }
.avatar-img { width: 40px; height: 40px; border-radius: 50%; object-fit: cover; background: #373e47; }
.avatar-big { width: 80px; height: 80px; border-radius: 50%; object-fit: cover; border: 2px solid #ff3b30; }
.thread-row { border-bottom: 1px solid #22272e; padding: 14px 0; display: flex; justify-content: space-between; align-items: center; gap: 10px; }
.thread-row.pinned { background: rgba(255,59,48,0.05); border-left: 3px solid #ff3b30; padding-left: 12px; border-radius: 4px; }
.thread-link { color: #f5f6f7; text-decoration: none; font-weight: 700; font-size: 18px; display: block; margin-bottom: 4px; word-break: break-word; }
.thread-link:hover { color: #ff3b30; }
.pin-icon { color: #ffcc00; font-size: 14px; margin-right: 4px; }
.badge { font-size: 11px; padding: 3px 8px; border-radius: 4px; font-weight: 700; text-transform: uppercase; margin-left: 4px; display: inline-block; }
.badge-user { background: rgba(173,186,199,0.15); color: #adbac7; }
.badge-helper { background: rgba(52,199,89,0.2); color: #30d158; border: 1px solid #30d158; }
.badge-mod { background: rgba(0,122,255,0.2); color: #0a84ff; border: 1px solid #0a84ff; }
.badge-admin_role { background: rgba(191,90,242,0.2); color: #bf5af2; border: 1px solid #bf5af2; }
.badge-admin { background: rgba(255,59,48,0.15); color: #ff3b30; border: 1px solid rgba(255,59,48,0.3); }
.verify-tick { color: #0a84ff; font-weight: bold; margin-left: 4px; font-size: 14px; }
.replies-box { margin-top: 16px; display: flex; flex-direction: column; gap: 12px; padding-left: 12px; border-left: 2px solid #2a2e35; }
.reply-card { background: #1c1f26; padding: 12px; border-radius: 8px; border: 1px solid #2a2e35; position: relative; }
.widget-stat { display: flex; justify-content: space-between; font-size: 16px; color: #adbac7; margin-bottom: 8px; }
.online-dot { color: #34c759; font-weight: bold; }
.locked-alert { background: rgba(255,59,48,0.08); border: 1px dashed #ff3b30; color: #ff3b30; padding: 15px; border-radius: 8px; text-align: center; font-weight: 600; font-size: 14px; margin-bottom: 14px; }
.auth-only { display: none; }
.admin-only { display: none; }
.banner-container { margin-bottom: 14px; }
.color-picker { width: 60px; height: 40px; padding: 2px; border-radius: 6px; border: 1px solid #373e47; background: #22272e; cursor: pointer; }
.pm-container { max-height: 500px; overflow-y: auto; }
.pm-item { border-bottom: 1px solid #2a2e35; padding: 12px 0; }
.pm-item.unread { background: rgba(10,132,255,0.05); border-left: 3px solid #0a84ff; padding-left: 8px; }
.agreement-checkbox { display: flex; align-items: center; gap: 8px; margin-bottom: 12px; font-size: 15px; color: #adbac7; }
.agreement-checkbox input[type="checkbox"] { width: auto; margin: 0; }
.error-msg { background: rgba(255,59,48,0.1); border: 1px solid #ff3b30; color: #ff3b30; padding: 12px; border-radius: 6px; margin-bottom: 12px; font-size: 16px; }
.success-msg { background: rgba(52,199,89,0.1); border: 1px solid #34c759; color: #34c759; padding: 12px; border-radius: 6px; margin-bottom: 12px; font-size: 16px; }
.post-attached-img { max-width: 100%; max-height: 400px; border-radius: 8px; margin-top: 12px; border: 1px solid #2a2e35; }
.file-upload-label { display: block; font-size: 14px; color: #768390; margin-bottom: 8px; cursor: pointer; }
.meta-info { font-size: 14px; color: #768390; }
.thread-actions { display: flex; gap: 8px; flex-wrap: wrap; margin-top: 8px; }
.thread-actions button { padding: 6px 14px; font-size: 14px; width: auto; }
.back-btn { color: #ff3b30; text-decoration: none; font-weight: 600; }
.back-btn:hover { text-decoration: underline; }
.delete-btn { background: #ff3b30; color: #fff; border: none; padding: 4px 12px; border-radius: 4px; cursor: pointer; font-size: 12px; font-weight: 700; }
.delete-btn:hover { background: #e03126; }
.reply-actions { display: flex; gap: 8px; margin-top: 8px; flex-wrap: wrap; }

@media (min-width: 768px) {
    .container { max-width: 1200px; padding: 0 20px; flex-direction: row; }
    .sidebar { width: 320px; flex-shrink: 0; }
    body { font-size: 16px; }
    .thread-link { font-size: 18px; }
}
@media (max-width: 480px) {
    .nav-links a, .nav-links button { font-size: 13px; padding: 4px 8px; }
    .logo { font-size: 18px; }
    .card { padding: 12px; }
    .card-title { font-size: 14px; }
    input, textarea, select { font-size: 15px; padding: 12px; }
    .btn { font-size: 14px; padding: 12px 16px; }
    .thread-link { font-size: 16px; }
}
</style>
</head>
<body>
<header>
    <div class="header-container">
        <a href="?page=home" class="logo">GREFF<span>RLEND</span></a>
        <div class="nav-links">
            <?php if (isLoggedIn()): ?>
                <a href="?page=home" class="<?= $page=='home'?'active':'' ?>">🏠</a>
                <a href="?page=settings" class="<?= $page=='settings'?'active':'' ?>">⚙️</a>
                <a href="?page=messages" class="<?= $page=='messages'?'active':'' ?>">💬</a>
                <?php if (isAdmin()): ?>
                    <a href="?page=admin" class="<?= $page=='admin'?'active':'' ?>">🔧</a>
                <?php endif; ?>
                <button class="ip-badge" onclick="copyIP()">🌐</button>
                <form action="index.php" method="POST" style="display:inline;">
                    <input type="hidden" name="action" value="logout">
                    <button type="submit" style="background:none;border:none;color:#adbac7;cursor:pointer;font-size:14px;padding:6px 10px;">🚪</button>
                </form>
            <?php else: ?>
                <button class="ip-badge" onclick="copyIP()">🌐</button>
            <?php endif; ?>
        </div>
    </div>
</header>

<div class="container">
    <div class="main">
        <?php if (isLoggedIn()): ?>
            <div class="banner-container"><?= getBannerHTML() ?></div>
        <?php endif; ?>
        
        <?php if (!isLoggedIn()): ?>
            <div id="guest-alert" class="locked-alert">🔒 Чтобы просматривать контент и общаться на форуме, необходимо выполнить вход.</div>
            <div style="display:flex; gap:16px; flex-direction:column;">
                <div class="card">
                    <div class="card-title">🔐 Авторизация</div>
                    <?php if (isset($login_error)): ?><div class="error-msg"><?= $login_error ?></div><?php endif; ?>
                    <form action="index.php" method="POST">
                        <input type="hidden" name="action" value="login">
                        <input type="text" name="username" placeholder="Никнейм" required>
                        <input type="password" name="password" placeholder="Пароль" required>
                        <button type="submit" class="btn">Войти</button>
                    </form>
                </div>
                <div class="card">
                    <div class="card-title">📝 Регистрация</div>
                    <?php if (isset($reg_error)): ?><div class="error-msg"><?= $reg_error ?></div><?php endif; ?>
                    <form action="index.php" method="POST">
                        <input type="hidden" name="action" value="register">
                        <input type="text" name="username" placeholder="Никнейм" required>
                        <input type="password" name="password" placeholder="Пароль" required>
                        <div class="agreement-checkbox">
                            <input type="checkbox" id="agree" name="agree_policy" required>
                            <label for="agree">Я принимаю <a href="#" onclick="showPolicy();return false;" style="color:#0a84ff;">Политику конфиденциальности</a></label>
                        </div>
                        <button type="submit" class="btn btn-green">Создать профиль</button>
                    </form>
                </div>
            </div>
            
        <?php else: ?>
            <!-- АДМИН-ПАНЕЛЬ -->
            <?php if ($page === 'admin' && isAdmin()): ?>
                <div class="card">
                    <div class="card-title" style="color:#ff3b30;">🔧 Админ-панель</div>
                    
                    <h3 style="color:#768390; margin:12px 0 8px; font-size:16px;">📊 Статистика</h3>
                    <div style="display:grid; grid-template-columns:1fr 1fr 1fr; gap:8px; margin-bottom:16px;">
                        <div style="background:#22272e; padding:12px; border-radius:6px; text-align:center;">
                            <div style="font-size:24px; font-weight:700; color:#0a84ff;"><?= count($users_data) ?></div>
                            <div style="font-size:12px; color:#768390;">Аккаунтов</div>
                        </div>
                        <div style="background:#22272e; padding:12px; border-radius:6px; text-align:center;">
                            <div style="font-size:24px; font-weight:700; color:#34c759;"><?= count($forum_data) ?></div>
                            <div style="font-size:12px; color:#768390;">Тем</div>
                        </div>
                        <div style="background:#22272e; padding:12px; border-radius:6px; text-align:center;">
                            <div style="font-size:24px; font-weight:700; color:#ffcc00;"><?= count($messages_data) ?></div>
                            <div style="font-size:12px; color:#768390;">Сообщений</div>
                        </div>
                    </div>
                    
                    <h3 style="color:#768390; margin:12px 0 8px; font-size:16px;">👥 Управление игроками</h3>
                    <form action="index.php" method="POST" style="margin-bottom:16px;">
                        <input type="hidden" name="action" value="admin_action">
                        <input type="hidden" name="redirect" value="?page=admin">
                        <input type="text" name="target_user" placeholder="Никнейм игрока" required>
                        <div style="display:grid; grid-template-columns:1fr 1fr; gap:8px; margin-bottom:8px;">
                            <button type="submit" name="admin_action" value="ban" class="btn" style="background:#ff3b30;">⛔ БАН</button>
                            <button type="submit" name="admin_action" value="unban" class="btn btn-green">✅ РАЗБАН</button>
                        </div>
                        <div style="display:grid; grid-template-columns:1fr 1fr; gap:8px; margin-bottom:8px;">
                            <button type="submit" name="admin_action" value="mute" class="btn" style="background:#ffcc00; color:#000;">🔇 МУТ</button>
                            <button type="submit" name="admin_action" value="unmute" class="btn btn-blue">🔊 РАЗМУТ</button>
                        </div>
                        <select name="role_val" style="margin-bottom:8px;">
                            <option value="user">🎮 Игрок</option>
                            <option value="helper">🆘 Хелпер</option>
                            <option value="mod">🛡️ Модератор</option>
                            <option value="admin_role">⚡ Администратор</option>
                        </select>
                        <div style="display:grid; grid-template-columns:1fr 1fr; gap:8px;">
                            <button type="submit" name="admin_action" value="set_role" class="btn btn-purple">🎭 РОЛЬ</button>
                            <button type="submit" name="admin_action" value="toggle_verify" class="btn btn-blue">✅ ГАЛОЧКА</button>
                        </div>
                    </form>
                    
                    <h3 style="color:#768390; margin:12px 0 8px; font-size:16px;">🗑️ Удалить аккаунт</h3>
                    <form action="index.php" method="POST" style="display:flex; gap:8px; flex-wrap:wrap; margin-bottom:16px;">
                        <input type="hidden" name="action" value="admin_action">
                        <input type="hidden" name="admin_action" value="delete_account">
                        <input type="text" name="target_user" placeholder="Никнейм" required style="flex:1; min-width:150px;">
                        <button type="submit" class="btn" style="background:#ff3b30; width:auto; padding:10px 20px;" onclick="return confirm('Удалить аккаунт?')">🗑️ Удалить</button>
                    </form>
                    
                    <h3 style="color:#768390; margin:12px 0 8px; font-size:16px;">👁️ Просмотр переписки</h3>
                    <form action="index.php" method="POST" style="display:flex; gap:8px; flex-wrap:wrap; margin-bottom:16px;">
                        <input type="hidden" name="action" value="admin_action">
                        <input type="hidden" name="admin_action" value="view_messages">
                        <input type="text" name="target_user" placeholder="Никнейм" required style="flex:1; min-width:150px;">
                        <button type="submit" class="btn btn-blue" style="width:auto; padding:10px 20px;">👁️ Показать</button>
                    </form>
                    
                    <h3 style="color:#768390; margin:12px 0 8px; font-size:16px;">📢 Баннер</h3>
                    <form action="index.php" method="POST" enctype="multipart/form-data" style="margin-bottom:16px;">
                        <input type="hidden" name="action" value="admin_action">
                        <input type="hidden" name="admin_action" value="upload_banner">
                        <input type="file" name="banner_file" accept="image/*" required>
                        <div style="display:flex; gap:8px; margin-bottom:8px;">
                            <input type="number" name="banner_width" placeholder="Ширина" value="<?= $admin_data['banner_width'] ?? 728 ?>" style="flex:1;">
                            <input type="number" name="banner_height" placeholder="Высота" value="<?= $admin_data['banner_height'] ?? 90 ?>" style="flex:1;">
                        </div>
                        <div style="display:flex; gap:8px;">
                            <button type="submit" class="btn btn-green" style="width:auto; padding:10px 20px;">📤 Загрузить</button>
                            <?php if(!empty($admin_data['banner_image'])): ?>
                                <button type="submit" name="admin_action" value="remove_banner" class="btn" style="background:#ff3b30; width:auto; padding:10px 20px;" onclick="return confirm('Удалить баннер?')">🗑️ Удалить</button>
                            <?php endif; ?>
                        </div>
                    </form>
                    <?php if(!empty($admin_data['banner_image'])): ?>
                        <div style="background:#22272e; padding:10px; border-radius:6px;">
                            <img src="<?= $admin_data['banner_image'] ?>" style="max-width:100%; max-height:200px; border-radius:4px;">
                            <div style="font-size:12px; color:#768390; margin-top:4px;">Размер: <?= $admin_data['banner_width'] ?? 728 ?>x<?= $admin_data['banner_height'] ?? 90 ?>px</div>
                        </div>
                    <?php endif; ?>
                </div>
            
            <!-- ЛИЧНЫЕ СООБЩЕНИЯ -->
            <?php elseif ($page === 'messages'): ?>
                <div class="card">
                    <div class="card-title">💬 Личные сообщения</div>
                    <form action="index.php" method="POST" style="margin-bottom:16px;">
                        <input type="hidden" name="action" value="send_pm">
                        <input type="text" name="recipient" placeholder="Кому (никнейм)" required>
                        <textarea name="pm_content" rows="4" placeholder="Текст сообщения..." required></textarea>
                        <button type="submit" class="btn btn-blue">📨 Отправить</button>
                    </form>
                    <div class="card-title" style="margin-top:12px;">📥 Входящие</div>
                    <div class="pm-container">
                        <?php 
                        $lower = strtolower(getLoggedUser());
                        $msgs = array_filter($messages_data, function($m) use ($lower) {
                            return strtolower($m['to']) === $lower || strtolower($m['from']) === $lower;
                        });
                        $msgs = array_reverse($msgs);
                        if(empty($msgs)): ?>
                            <p style="color:#768390; text-align:center; padding:20px 0;">📭 У вас нет сообщений</p>
                        <?php else: foreach($msgs as $m): 
                            $from_me = strtolower($m['from']) === $lower;
                        ?>
                            <div class="pm-item <?= (!$from_me && !$m['read']) ? 'unread' : '' ?>">
                                <div style="display:flex; justify-content:space-between; align-items:center;">
                                    <div style="font-size:14px; color:#768390;">
                                        <?php if($from_me): ?>
                                            📤 Вы → <a href="?page=user&user=<?= urlencode($m['to']) ?>" style="color:#f5f6f7; text-decoration:none;"><?= getDisplayName($m['to']) ?></a>
                                        <?php else: ?>
                                            📩 <a href="?page=user&user=<?= urlencode($m['from']) ?>" style="color:#f5f6f7; text-decoration:none;"><?= getDisplayName($m['from']) ?></a>
                                        <?php endif; ?>
                                    </div>
                                    <div style="font-size:12px; color:#768390;"><?= $m['date'] ?></div>
                                </div>
                                <div style="margin-top:4px; color:#eceff2;"><?= $m['content'] ?></div>
                            </div>
                        <?php endforeach; endif; ?>
                    </div>
                </div>
            
            <!-- ПРОФИЛЬ ПОЛЬЗОВАТЕЛЯ -->
            <?php elseif ($page === 'user' && $profile_user): ?>
                <?php $u = strtolower($profile_user); $info = $users_data[$u] ?? []; $av = !empty($info['avatar']) ? $info['avatar'] : 'https://gravatar.com/avatar/000?s=80'; $about = !empty($info['about']) ? $info['about'] : 'Этот игрок еще ничего не рассказал о себе.'; ?>
                <div class="card" style="display:flex; gap:16px; align-items:center; flex-wrap:wrap;">
                    <img src="<?= $av ?>" class="avatar-big" alt="Avatar">
                    <div>
                        <div style="font-size:24px; font-weight:700; display:flex; align-items:center; gap:8px; flex-wrap:wrap;">
                            <span style="color:<?= getUserColor($profile_user) ?>;"><?= getDisplayName($profile_user) ?></span>
                            <?php if(!empty(getUserBadge($profile_user))): ?><span style="font-size:20px;"><?= getUserBadge($profile_user) ?></span><?php endif; ?>
                            <?php if(in_array($u, $admin_data['verified'])) echo '<span class="verify-tick">☑</span>'; ?>
                        </div>
                        <div style="display:flex; flex-wrap:wrap; gap:4px; margin:4px 0;">
                            <?php if(strcasecmp($profile_user, 'admins@greffrlend.fun') === 0): ?>
                                <span class="badge badge-admin">👑 Гл. Админ</span>
                            <?php elseif(!empty($admin_data['roles'][$u])): 
                                $role = $admin_data['roles'][$u];
                                $class = $role === 'helper' ? 'badge-helper' : ($role === 'mod' ? 'badge-mod' : ($role === 'admin_role' ? 'badge-admin_role' : 'badge-user'));
                            ?>
                                <span class="badge <?= $class ?>"><?= $role ?></span>
                            <?php else: ?>
                                <span class="badge badge-user">🎮 Игрок</span>
                            <?php endif; ?>
                        </div>
                        <p style="color:#adbac7; font-size:15px; margin-top:8px; max-width:500px;"><?= $about ?></p>
                    </div>
                </div>
                <div style="display:flex; gap:10px; flex-wrap:wrap; margin-top:10px;">
                    <a href="?page=messages" class="back-btn" style="margin-top:0;">💬 Написать сообщение</a>
                    <a href="?page=home" class="back-btn" style="margin-top:0;">← На главную</a>
                </div>
            
            <!-- НАСТРОЙКИ -->
            <?php elseif ($page === 'settings'): ?>
                <div class="card">
                    <div class="card-title">⚙️ Настройки профиля</div>
                    <?php if ($msg): ?><div class="<?= strpos($msg, '✅') !== false ? 'success-msg' : 'error-msg' ?>"><?= $msg ?></div><?php endif; ?>
                    <form action="index.php" method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="action" value="update_profile">
                        <label class="file-upload-label">📸 Загрузить фото профиля:</label>
                        <input type="file" name="avatar_file" accept="image/*">
                        
                        <div style="display:flex; gap:12px; flex-wrap:wrap;">
                            <div style="flex:1; min-width:140px;">
                                <label style="font-size:13px; color:#768390;">🎨 Цвет ника:</label>
                                <input type="color" name="custom_color" class="color-picker" value="<?= getUserColor(getLoggedUser()) ?>">
                            </div>
                            <div style="flex:1; min-width:140px;">
                                <label style="font-size:13px; color:#768390;">🏷️ Кастомное имя:</label>
                                <input type="text" name="custom_title" placeholder="Отображаемое имя" value="<?= htmlspecialchars($users_data[strtolower(getLoggedUser())]['custom_title'] ?? '') ?>">
                            </div>
                            <div style="flex:0 0 80px;">
                                <label style="font-size:13px; color:#768390;">✨ Значок:</label>
                                <input type="text" name="badge_text" placeholder="👑" value="<?= htmlspecialchars($users_data[strtolower(getLoggedUser())]['badge_text'] ?? '') ?>" maxlength="5" style="text-align:center; font-size:20px;">
                            </div>
                        </div>
                        
                        <label style="font-size:13px; color:#768390; margin-top:8px; display:block;">📝 О себе:</label>
                        <textarea name="about_text" rows="3" placeholder="Расскажите игрокам что-нибудь о себе..."><?= htmlspecialchars($users_data[strtolower(getLoggedUser())]['about'] ?? '') ?></textarea>
                        
                        <button type="submit" class="btn btn-green">💾 Сохранить изменения</button>
                    </form>
                </div>
            
            <!-- ПРОСМОТР ТЕМЫ -->
            <?php elseif ($page === 'thread' && $thread_id && isset($forum_data[$thread_id])): ?>
                <?php $th = $forum_data[$thread_id]; $thAdmin = (strcasecmp($th['author'], 'admins@greffrlend.fun') === 0); $thAuthorLower = strtolower($th['author']); $customRole = $admin_data['roles'][$thAuthorLower] ?? ''; ?>
                <div class="card">
                    <div style="font-size:14px; color:#768390; margin-bottom:8px;">
                        <?php if($th['pinned']): ?><span style="color:#ffcc00;">📌 </span><?php endif; ?>
                        Автор: <a href="?page=user&user=<?= urlencode($th['author']) ?>" style="color:<?= getUserColor($th['author']) ?>; font-weight:600; text-decoration:none;"><?= getDisplayName($th['author']) ?></a>
                        <?php if(!empty(getUserBadge($th['author']))): ?><span style="font-size:14px;"><?= getUserBadge($th['author']) ?></span><?php endif; ?>
                        <?php if(in_array($thAuthorLower, $admin_data['verified'])) echo '<span class="verify-tick">☑</span>'; ?>
                        <?php if ($thAdmin) echo '<span class="badge badge-admin">👑 Гл. Админ</span>'; elseif (!empty($customRole)) echo '<span class="badge badge-'.$customRole.'">'.$customRole.'</span>'; else echo '<span class="badge badge-user">🎮 Игрок</span>'; ?>
                        | <?= $th['date'] ?>
                    </div>
                    <h2 style="margin:0 0 12px 0; font-size:22px;"><?= $th['title'] ?></h2>
                    <div style="line-height:1.6; color:#eceff2; font-size:16px;"><?= $th['content'] ?></div>
                    <?php if(!empty($th['image'])): ?><img src="<?= $th['image'] ?>" class="post-attached-img" alt="Post img"><?php endif; ?>
                    
                    <div class="thread-actions">
                        <?php if(canDelete(getLoggedUser())): ?>
                            <form action="index.php" method="POST" onsubmit="return confirm('Удалить тему?')">
                                <input type="hidden" name="action" value="delete_thread">
                                <input type="hidden" name="thread_id" value="<?= $thread_id ?>">
                                <button type="submit" class="btn" style="background:#ff3b30; width:auto; padding:6px 14px; font-size:14px;">🗑️ Удалить тему</button>
                            </form>
                        <?php endif; ?>
                        <?php if(isAdmin()): ?>
                            <form action="index.php" method="POST">
                                <input type="hidden" name="action" value="admin_action">
                                <input type="hidden" name="admin_action" value="pin_thread">
                                <input type="hidden" name="thread_id" value="<?= $thread_id ?>">
                                <button type="submit" class="btn" style="background:#ffcc00; color:#000; width:auto; padding:6px 14px; font-size:14px;">
                                    <?= $th['pinned'] ? '📌 Открепить' : '📌 Закрепить' ?>
                                </button>
                            </form>
                        <?php endif; ?>
                    </div>
                    
                    <a href="?page=home" class="back-btn" style="display:inline-block; margin-top:12px;">← Список тем</a>
                </div>
                
                <div class="card-title" style="font-size:16px;">💬 Ответы игроков (<?= count($th['replies']) ?>)</div>
                <div class="replies-box">
                    <?php foreach ($th['replies'] as $index => $rep): ?>
                        <?php $rAdmin = (strcasecmp($rep['author'], 'admins@greffrlend.fun') === 0); $rAuthorLower = strtolower($rep['author']); $rRole = $admin_data['roles'][$rAuthorLower] ?? ''; ?>
                        <div class="reply-card">
                            <div style="font-size:13px; color:#768390; margin-bottom:6px;">
                                <a href="?page=user&user=<?= urlencode($rep['author']) ?>" style="color:<?= getUserColor($rep['author']) ?>; font-weight:600; text-decoration:none;"><?= getDisplayName($rep['author']) ?></a>
                                <?php if(!empty(getUserBadge($rep['author']))): ?><span style="font-size:14px;"><?= getUserBadge($rep['author']) ?></span><?php endif; ?>
                                <?php if(in_array($rAuthorLower, $admin_data['verified'])) echo '<span class="verify-tick">☑</span>'; ?>
                                <?php if ($rAdmin) echo '<span class="badge badge-admin">👑 Гл. Admin</span>'; elseif (!empty($rRole)) echo '<span class="badge badge-'.$rRole.'">'.$rRole.'</span>'; else echo '<span class="badge badge-user">🎮 Игрок</span>'; ?>
                                • <?= $rep['date'] ?>
                            </div>
                            <div style="line-height:1.6; color:#eceff2; font-size:15px;"><?= $rep['content'] ?></div>
                            <?php if(canDelete(getLoggedUser())): ?>
                                <div class="reply-actions">
                                    <form action="index.php" method="POST" onsubmit="return confirm('Удалить этот ответ?')">
                                        <input type="hidden" name="action" value="delete_reply">
                                        <input type="hidden" name="thread_id" value="<?= $thread_id ?>">
                                        <input type="hidden" name="reply_index" value="<?= $index ?>">
                                        <button type="submit" class="delete-btn">🗑️ Удалить</button>
                                    </form>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
                <div class="card" style="margin-top:16px;">
                    <div class="card-title">✍️ Написать ответ</div>
                    <form action="index.php" method="POST">
                        <input type="hidden" name="action" value="add_reply">
                        <input type="hidden" name="thread_id" value="<?= $thread_id ?>">
                        <textarea name="reply_content" rows="3" placeholder="Ваш ответ..." required></textarea>
                        <button type="submit" class="btn">📨 Отправить</button>
                    </form>
                </div>
            
            <!-- ГЛАВНАЯ -->
            <?php else: ?>
                <div class="card">
                    <div class="card-title">📝 Создать новое обсуждение</div>
                    <form action="index.php" method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="action" value="create_thread">
                        <input type="text" name="title" placeholder="Заголовок темы" required>
                        <textarea name="content" rows="3" placeholder="Суть вопроса..." required></textarea>
                        <label class="file-upload-label">📎 Прикрепить фотографию:</label>
                        <input type="file" name="post_file" accept="image/*">
                        <button type="submit" class="btn" style="margin-top:8px;">🚀 Отправить публикацию</button>
                    </form>
                </div>
                
                <?php if(isAdmin() && !empty($admin_data['moderation'])): ?>
                    <div class="card" style="border: 1px dashed #ff3b30;">
                        <div class="card-title" style="color:#ff3b30;">📋 Очередь постов на проверку</div>
                        <?php foreach($admin_data['moderation'] as $p_id => $p_data): ?>
                            <div style="padding:10px; border-bottom:1px solid #2a2e35;">
                                <div style="font-size:13px; color:#adbac7;">Автор: <b><?= $p_data['author'] ?></b> | Тема: <b><?= $p_data['title'] ?></b></div>
                                <p style="font-size:14px; color:#eceff2; margin:5px 0;"><?= $p_data['content'] ?></p>
                                <?php if(!empty($p_data['image'])): ?><img src="<?= $p_data['image'] ?>" class="post-attached-img" alt="Attached img"><?php endif; ?>
                                <form action="index.php" method="POST" style="display:flex; gap:8px; margin-top:8px;">
                                    <input type="hidden" name="action" value="admin_action">
                                    <input type="hidden" name="post_id" value="<?= $p_id ?>">
                                    <button type="submit" name="admin_action" value="approve_post" class="btn btn-green" style="width:auto; padding:4px 12px; font-size:13px;">✅ Одобрить</button>
                                    <button type="submit" name="admin_action" value="reject_post" class="btn" style="width:auto; padding:4px 12px; font-size:13px;">❌ Отклонить</button>
                                </form>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                
                <div class="card-title" style="font-size:16px;">📚 Все темы сообщества</div>
                <div class="card" style="padding:5px 14px;">
                    <?php if (empty($forum_data)): ?>
                        <p style="text-align:center; color:#768390; padding:20px 0;">📭 На форуме пока нет тем.</p>
                    <?php else: 
                        $sorted = $forum_data;
                        uasort($sorted, function($a, $b) {
                            return ($b['pinned'] ?? false) <=> ($a['pinned'] ?? false);
                        });
                        foreach ($sorted as $id => $th): 
                            $thAdmin = (strcasecmp($th['author'], 'admins@greffrlend.fun') === 0); 
                            $thAuthorLower = strtolower($th['author']); 
                            $thRole = $admin_data['roles'][$thAuthorLower] ?? ''; 
                            $th_av = !empty($users_data[$thAuthorLower]['avatar']) ? $users_data[$thAuthorLower]['avatar'] : 'https://gravatar.com/avatar/000?s=40';
                            $is_pinned = $th['pinned'] ?? false;
                    ?>
                        <div class="thread-row <?= $is_pinned ? 'pinned' : '' ?>">
                            <div style="display:flex; gap:12px; align-items:center; flex:1; min-width:0;">
                                <img src="<?= $th_av ?>" class="avatar-img" alt="Avatar">
                                <div style="flex:1; min-width:0;">
                                    <a href="?page=thread&id=<?= $id ?>" class="thread-link">
                                        <?= $is_pinned ? '<span class="pin-icon">📌</span>' : '' ?>
                                        <?= $th['title'] ?>
                                    </a>
                                    <div class="meta-info">
                                        <a href="?page=user&user=<?= urlencode($th['author']) ?>" style="color:<?= getUserColor($th['author']) ?>; text-decoration:none; font-weight:600;"><?= getDisplayName($th['author']) ?></a>
                                        <?php if(!empty(getUserBadge($th['author']))): ?><span style="font-size:14px;"><?= getUserBadge($th['author']) ?></span><?php endif; ?>
                                        <?php if(in_array($thAuthorLower, $admin_data['verified'])) echo '<span class="verify-tick">☑</span>'; ?>
                                        <?php if ($thAdmin) echo '<span class="badge badge-admin">👑 Гл. Админ</span>'; elseif (!empty($thRole)) echo '<span class="badge badge-'.$thRole.'">'.$thRole.'</span>'; else echo '<span class="badge badge-user">🎮 Игрок</span>'; ?>
                                        • <?= $th['date'] ?>
                                    </div>
                                </div>
                            </div>
                            <div style="font-size:13px; color:#768390; background:#22272e; padding:4px 12px; border-radius:12px; white-space:nowrap;">💬 <?= count($th['replies']) ?></div>
                        </div>
                    <?php endforeach; endif; ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
    
    <!-- САЙДБАР -->
    <?php if (isLoggedIn()): ?>
    <div class="sidebar" id="mainSidebar">
        <div class="card">
            <div class="card-title">📊 Статистика</div>
            <div class="widget-stat"><span>🟢 Онлайн:</span><span class="online-dot">● <?= $online_count ?></span></div>
            <div class="widget-stat"><span>📚 Тем:</span><span><?= count($forum_data) ?></span></div>
            <div class="widget-stat"><span>🔇 В муте:</span><span style="color:#ffcc00; font-weight:600;"><?= count($admin_data['mutes']) ?></span></div>
            <div class="widget-stat"><span>⛔ В бане:</span><span style="color:#ff3b30; font-weight:600;"><?= count($admin_data['bans']) ?></span></div>
            <div class="widget-stat"><span>👥 Аккаунтов:</span><span style="color:#0a84ff;"><?= count($users_data) ?></span></div>
        </div>
        <!-- ====== ГОЛОСОВОЙ КАНАЛ (КОМПАКТНЫЙ) ====== -->
<div class="card" style="border:2px solid #34c759; padding:10px 14px; margin-top:4px;">
    <div style="display:flex; align-items:center; gap:8px; flex-wrap:wrap;">
        <span style="font-weight:700; font-size:15px; color:#34c759;">🎙️</span>
        <span id="voice-status" style="font-size:13px; color:#768390;">🔴 Офлайн</span>
        <button class="btn btn-green" onclick="joinVoiceRoom()" id="joinVoiceBtn" style="padding:4px 12px; font-size:12px; width:auto; background:#34c759; border:none; border-radius:6px; color:#fff; cursor:pointer;">Войти</button>
        <button class="btn" onclick="leaveVoiceRoom()" id="leaveVoiceBtn" style="padding:4px 12px; font-size:12px; width:auto; background:#ff3b30; border:none; border-radius:6px; color:#fff; cursor:pointer; display:none;">Выйти</button>
        <button class="btn btn-blue" onclick="toggleMute()" id="muteBtn" style="padding:4px 12px; font-size:12px; width:auto; background:#0a84ff; border:none; border-radius:6px; color:#fff; cursor:pointer; display:none;">🔇</button>
    </div>
    <div id="voice-users" style="margin-top:4px; font-size:12px; display:flex; flex-wrap:wrap; gap:4px;">
        <span style="color:#768390; font-size:12px;" id="users-placeholder">👥 Никого нет</span>
    </div>
    <div id="voice-messages" style="margin-top:6px; max-height:60px; overflow-y:auto; background:#1c1f26; border-radius:4px; padding:4px 8px; font-size:11px; color:#768390; display:none;">
    </div>
</div>

<script src="https://unpkg.com/peerjs@1.5.1/dist/peerjs.min.js"></script>

<script>
// ============================================
// ГОЛОСОВОЙ ЧАТ НА PEERJS (КОМПАКТНАЯ ВЕРСИЯ)
// ============================================

let peer = null;
let localStream = null;
let connections = [];
let isMuted = false;
let isInRoom = false;
let myName = '';

function getUsername() {
    const user = localStorage.getItem('forum_logged_in') || 'Гость';
    return user;
}

// ========== ВХОД ==========
async function joinVoiceRoom() {
    try {
        const roomName = 'greffrlend';
        myName = getUsername();
        
        localStream = await navigator.mediaDevices.getUserMedia({ 
            audio: {
                echoCancellation: true,
                noiseSuppression: true,
                autoGainControl: true
            }
        });
        
        const peerId = roomName + '_' + myName + '_' + Date.now();
        peer = new Peer(peerId, {
            host: '0.peerjs.com',
            port: 443,
            secure: true
        });
        
        peer.on('open', () => {
            isInRoom = true;
            document.getElementById('voice-status').innerHTML = '🟢 В эфире';
            document.getElementById('voice-status').style.color = '#34c759';
            document.getElementById('joinVoiceBtn').style.display = 'none';
            document.getElementById('leaveVoiceBtn').style.display = 'inline-block';
            document.getElementById('muteBtn').style.display = 'inline-block';
            document.getElementById('users-placeholder').style.display = 'none';
            
            addUser('Вы (' + myName + ')', true);
            
            const audio = new Audio();
            audio.srcObject = localStream;
            audio.play().catch(() => {});
            
            addMessage('✅ Вы вошли в голосовой канал');
        });
        
        peer.on('connection', (conn) => {
            connections.push(conn);
            const userName = conn.metadata?.name || 'Участник';
            addUser(userName, false);
            addMessage('🔊 ' + userName + ' присоединился');
            
            conn.on('close', () => {
                removeUser(conn.metadata?.name || 'Участник');
                addMessage('🔇 Участник покинул канал');
            });
        });
        
        peer.on('error', (err) => {
            console.error('Peer error:', err);
            if (err.type === 'unavailable-id') {
                addMessage('⚠️ Это имя уже занято');
            }
        });
        
    } catch(e) {
        console.error('Ошибка:', e);
        alert('❌ Нет доступа к микрофону!\nРазрешите доступ в настройках браузера.');
        document.getElementById('voice-status').innerHTML = '❌ Ошибка';
        document.getElementById('voice-status').style.color = '#ff3b30';
    }
}

// ========== ВЫХОД ==========
function leaveVoiceRoom() {
    if (localStream) {
        localStream.getTracks().forEach(track => track.stop());
        localStream = null;
    }
    
    if (peer) {
        connections.forEach(conn => conn.close());
        connections = [];
        peer.destroy();
        peer = null;
    }
    
    isInRoom = false;
    isMuted = false;
    
    document.getElementById('voice-status').innerHTML = '🔴 Офлайн';
    document.getElementById('voice-status').style.color = '#768390';
    document.getElementById('joinVoiceBtn').style.display = 'inline-block';
    document.getElementById('leaveVoiceBtn').style.display = 'none';
    document.getElementById('muteBtn').style.display = 'none';
    document.getElementById('muteBtn').textContent = '🔇';
    document.getElementById('voice-users').innerHTML = '<span style="color:#768390; font-size:12px;" id="users-placeholder">👥 Никого нет</span>';
    document.getElementById('users-placeholder').style.display = 'inline';
    
    addMessage('👋 Вы вышли из канала');
}

// ========== ЗАГЛУШИТЬ ==========
function toggleMute() {
    if (!localStream) return;
    
    const audioTrack = localStream.getAudioTracks()[0];
    if (audioTrack) {
        isMuted = !isMuted;
        audioTrack.enabled = !isMuted;
        document.getElementById('muteBtn').textContent = isMuted ? '🔊' : '🔇';
        addMessage(isMuted ? '🔇 Микрофон выключен' : '🎤 Микрофон включён');
    }
}

// ========== ДОБАВИТЬ УЧАСТНИКА ==========
function addUser(name, isSelf = false) {
    const container = document.getElementById('voice-users');
    const userEl = document.createElement('span');
    userEl.className = 'voice-user';
    userEl.dataset.name = name;
    userEl.style.cssText = 'background:#22272e; padding:3px 10px; border-radius:12px; font-size:12px; border:1px solid #34c759; display:inline-block;';
    userEl.textContent = (isSelf ? '🟢 ' : '🟢 ') + name;
    if (isSelf) {
        userEl.style.borderColor = '#ffcc00';
        userEl.style.background = 'rgba(255,204,0,0.1)';
    }
    container.appendChild(userEl);
    document.getElementById('users-placeholder')?.remove();
}

// ========== УДАЛИТЬ УЧАСТНИКА ==========
function removeUser(name) {
    const container = document.getElementById('voice-users');
    const users = container.querySelectorAll('.voice-user');
    users.forEach(el => {
        if (el.dataset.name === name) {
            el.remove();
        }
    });
    if (container.children.length === 0) {
        container.innerHTML = '<span style="color:#768390; font-size:12px;" id="users-placeholder">👥 Никого нет</span>';
    }
}

// ========== ДОБАВИТЬ СООБЩЕНИЕ ==========
function addMessage(text) {
    const container = document.getElementById('voice-messages');
    container.style.display = 'block';
    const msgEl = document.createElement('div');
    msgEl.style.cssText = 'padding:2px 0; border-bottom:1px solid #2a2e35; font-size:11px;';
    msgEl.textContent = '• ' + text;
    container.appendChild(msgEl);
    container.scrollTop = container.scrollHeight;
    
    while (container.children.length > 15) {
        container.removeChild(container.firstChild);
    }
}

// ========== ДЕМО-УЧАСТНИКИ ==========
setTimeout(() => {
    if (!isInRoom) {
        const container = document.getElementById('voice-users');
        container.innerHTML = `
            <span style="color:#768390; font-size:12px; padding:3px 10px; background:#1c1f26; border-radius:12px; border:1px solid #2a2e35; display:inline-block;">🟢 Dima2D3D</span>
            <span style="color:#768390; font-size:12px; padding:3px 10px; background:#1c1f26; border-radius:12px; border:1px solid #2a2e35; display:inline-block;">🟢 Top_GameR</span>
            <span style="color:#768390; font-size:12px; padding:3px 10px; background:#1c1f26; border-radius:12px; border:1px solid #2a2e35; display:inline-block;">🟢 mama2d3d</span>
        `;
        document.getElementById('users-placeholder')?.remove();
    }
}, 1000);

// ========== ПРОВЕРКА ПОДДЕРЖКИ ==========
if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
    document.getElementById('voice-status').innerHTML = '❌ Не поддерживается';
    document.getElementById('voice-status').style.color = '#ff3b30';
    document.getElementById('joinVoiceBtn').disabled = true;
    document.getElementById('joinVoiceBtn').style.opacity = '0.5';
}
</script>
        <?php if(isAdmin()): ?>
        <div class="card">
            <div class="card-title" style="color:#ff3b30;">🛠️ Управление</div>
            <form action="index.php" method="POST">
                <input type="hidden" name="action" value="admin_action">
                <input type="hidden" name="redirect" value="?page=home">
                <input type="text" name="target_user" placeholder="Никнейм" required>
                <select name="role_val" style="margin-bottom:8px;">
                    <option value="user">🎮 Игрок</option>
                    <option value="helper">🆘 Хелпер</option>
                    <option value="mod">🛡️ Модератор</option>
                    <option value="admin_role">⚡ Администратор</option>
                </select>
                <button type="submit" name="admin_action" value="set_role" class="btn btn-purple" style="margin-bottom:8px;">🎭 Выдать роль</button>
                <button type="submit" name="admin_action" value="toggle_verify" class="btn btn-blue" style="margin-bottom:12px;">✅ Галочка</button>
                <div style="display:grid; grid-template-columns:1fr 1fr; gap:8px; margin-bottom:8px;">
                    <button type="submit" name="admin_action" value="ban" class="btn" style="background:#ff3b30;">⛔ БАН</button>
                    <button type="submit" name="admin_action" value="unban" class="btn btn-green">✅ РАЗБАН</button>
                </div>
                <div style="display:grid; grid-template-columns:1fr 1fr; gap:8px;">
                    <button type="submit" name="admin_action" value="mute" class="btn" style="background:#ffcc00; color:#000;">🔇 МУТ</button>
                    <button type="submit" name="admin_action" value="unmute" class="btn btn-blue">🔊 РАЗМУТ</button>
                </div>
            </form>
        </div>
        <?php endif; ?>
    </div>
    <?php endif; ?>
</div>

<script>
function toggleSidebar() { 
    document.getElementById('mainSidebar').classList.toggle('active'); 
}

function showPolicy() {
    alert('📋 ПОЛИТИКА КОНФИДЕНЦИАЛЬНОСТИ\n\n1. Регистрируясь на нашем форуме, вы соглашаетесь с тем, что:\n   - Ваши личные сообщения могут быть предоставлены правоохранительным органам по запросу суда.\n   - Администрация оставляет за собой право просматривать переписки для обеспечения безопасности.\n2. Политика конфиденциальности может быть изменена в любой момент.\n3. Изменения вступают в силу с момента публикации на сайте.\n4. Продолжая использовать сайт, вы автоматически принимаете новые условия.\n\n✅ Я принимаю условия политики конфиденциальности.');
}

function copyIP() {
    const ip = "play.greffrlend.fun";
    if (navigator.clipboard) {
        navigator.clipboard.writeText(ip).then(() => {
            const btn = document.querySelector('.ip-badge');
            btn.textContent = "✅";
            btn.style.background = "#34c759";
            setTimeout(() => { btn.textContent = "🌐"; btn.style.background = "#ff3b30"; }, 2000);
        });
    }
}
</script>
</body>
</html>
