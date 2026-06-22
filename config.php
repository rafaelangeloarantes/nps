<?php
/**
 * config.php — Conexão com banco de dados MySQL
 */

mysqli_report(MYSQLI_REPORT_OFF);

$conn = null;
$db_host = $_ENV['DB_HOST'] ?? '127.0.0.1';
$db_user = $_ENV['DB_USER'] ?? 'root';
$db_pass = $_ENV['DB_PASS'] ?? '';
$db_name = $_ENV['DB_NAME'] ?? '';
$db_port = (int) ($_ENV['DB_PORT'] ?? 3306);

try {
    $conn = mysqli_connect($db_host, $db_user, $db_pass, $db_name, $db_port);
} catch (mysqli_sql_exception $e) {
    error_log('Erro de conexão MySQL: ' . $e->getMessage());
    http_response_code(500);
    die('Erro interno do sistema. Tente novamente mais tarde.');
}

if (!$conn) {
    error_log('Erro de conexão MySQL: ' . mysqli_connect_error());
    http_response_code(500);
    die('Erro interno do sistema. Tente novamente mais tarde.');
}

mysqli_set_charset($conn, $_ENV['DB_CHARSET'] ?? 'utf8mb4');
