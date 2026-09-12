<?php
declare(strict_types=1);
require __DIR__ . '/site/bootstrap.php';
header('Content-Type: text/plain; charset=UTF-8');
echo "User-agent: *\n";
echo "Allow: /\n";
echo 'Sitemap: ' . rtrim(PUBLIC_URL, '/') . "/sitemap.xml\n";
