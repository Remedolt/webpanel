<?php
declare(strict_types=1);
require __DIR__ . '/site/bootstrap.php';
header('Location: ' . services_permalink(), true, 301);
exit;
