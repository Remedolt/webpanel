<?php
header('Content-Type: text/plain; charset=UTF-8');
echo 'PHP OK ' . PHP_VERSION . "\n";
echo 'PDO MySQL: ' . (in_array('mysql', PDO::getAvailableDrivers(), true) ? 'var' : 'YOK') . "\n";
echo 'install.php: ' . (is_file(__DIR__ . '/install.php') ? 'var' : 'YOK') . "\n";
echo 'db.php: ' . (is_file(__DIR__ . '/includes/db.php') ? 'var' : 'YOK') . "\n";
