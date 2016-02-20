<?php
include_once __DIR__.'/vendor/autoload.php';

$config = new \Doctrine\DBAL\Configuration();
$connectionParams = array(
    'driver' => 'pdo_sqlite',
    'path' => __DIR__.'/var/app.db',
    'charset' => 'utf8',
);

$conn = \Doctrine\DBAL\DriverManager::getConnection($connectionParams, $config);
