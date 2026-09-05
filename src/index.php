<?php

require_once __DIR__ . '/includes/bootstrap.php';

header('Location: ' . (!empty($_SESSION['username']) ? 'dashboard.php' : 'login.php'));
exit;
