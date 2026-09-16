<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/core/Auth.php';

Auth::logout();
header('Location: connexion.php?logout=1');
exit;
