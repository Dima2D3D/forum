<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/inactivity.php';

run_inactivity_reminders();
echo "OK\n";
