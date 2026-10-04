<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth612.php';
rafiq612_logout();
header('Location: index612.html', true, 302);
exit;
