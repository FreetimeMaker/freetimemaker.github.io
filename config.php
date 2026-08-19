<?php
$dbHost = getenv('DB_HOST') ?: 'db.us-losa1.bengt.wasmernet.com';
$dbPort = getenv('DB_PORT') ?: '16751';
$dbName = getenv('DB_NAME') ?: 'db_f332bb08';
$dbUser = getenv('DB_USER') ?: 'user_ead4230b';
$dbPass = getenv('DB_PASS') ?: 'pw_vGenSvgsdkU3hrSGpOpyfqilrBbGugKv';

$pdoDrivers = extension_loaded('pdo_mysql') ? ['mysql'] : [];
$mysqlSupported = in_array('mysql', PDO::getAvailableDrivers(), true);

if (getenv('DB_DSN')) {
    $dbDsn = getenv('DB_DSN');
} elseif ($mysqlSupported) {
    $dbDsn = sprintf(
        'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
        $dbHost,
        $dbPort,
        $dbName
    );
} else {
    $dbDsn = 'sqlite:' . __DIR__ . '/db/contact.sqlite';
}
