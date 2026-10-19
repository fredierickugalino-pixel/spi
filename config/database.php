<?php
declare(strict_types=1);

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$dbHost = getenv('SPI_DB_HOST') ?: '127.0.0.1';
$dbUser = getenv('SPI_DB_USER') ?: 'root';
$dbPass = getenv('SPI_DB_PASSWORD') ?: '';
$dbName = getenv('SPI_DB_NAME') ?: 'drick';
try {
    $conn = new mysqli($dbHost, $dbUser, $dbPass, $dbName);
    $conn->set_charset('utf8mb4');
} catch (mysqli_sql_exception $exception) {
    http_response_code(503);
    if ($exception->getCode() === 1049) {
        $message = 'The Drick database has not been created yet.';
        $steps = 'Open phpMyAdmin, choose Import, select database/drick.sql from the SPI project folder, and click Import. For an existing SPI installation, import database/migrate_spi_to_drick.sql after creating the new database.';
    } else {
        $message = 'The SPI database is unavailable.';
        $steps = 'Start MySQL in the XAMPP Control Panel and check the database settings in config/database.php.';
    }
    echo '<!doctype html><html lang="en"><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>SPI setup required</title><style>body{font:16px/1.5 system-ui;background:#f4f7f4;color:#19251d;margin:8vh auto;max-width:700px;padding:24px}.box{background:#fff;border:1px solid #dce6de;border-radius:12px;padding:24px}h1{color:#145c34}</style><div class="box"><h1>'.htmlspecialchars($message, ENT_QUOTES, 'UTF-8').'</h1><p>'.htmlspecialchars($steps, ENT_QUOTES, 'UTF-8').'</p><p>SQL file: <code>'.htmlspecialchars(__DIR__ . '/../database/drick.sql', ENT_QUOTES, 'UTF-8').'</code></p><p>After importing, reload this page.</p></div></html>';
    exit;
}
