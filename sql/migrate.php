<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');
require __DIR__ . '/../bootstrap.php';

$sql = file_get_contents(__DIR__ . '/migrations/002_migracao_relatorios.sql');
$parts = preg_split('/;\s*\n/', str_replace("\r\n", "\n", $sql));

foreach ($parts as $i => $stmt) {
    $stmt = trim($stmt);
    if ($stmt === '' || strpos(ltrim($stmt), '--') === 0) continue;
    echo "=== STMT {$i} (" . strlen($stmt) . " bytes) ===\n";
    echo substr($stmt, 0, 80) . "...\n";
    if (!mysqli_query($conn, $stmt)) {
        echo "ERROR: " . mysqli_error($conn) . "\n\n";
    } else {
        echo "OK\n\n";
    }
}
