<?php
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('CLI only');
}
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/inactivity.php';
run_inactivity_reminders();
echo "OK\n";
