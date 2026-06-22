<?php
/**
 * Migration 007 — Dashboard templates, relatórios e auditoria
 */
require_once __DIR__ . '/../bootstrap.php';

$sql = file_get_contents(__DIR__ . '/migrations/007_dashboard.sql');
if ($sql === false) {
    die("Arquivo SQL não encontrado.\n");
}

$statements = array_filter(array_map('trim', explode(';', $sql)));
$ok = 0;
$erros = [];

foreach ($statements as $stmt) {
    if ($stmt === '' || stripos($stmt, 'SET ') === 0) {
        continue;
    }
    if (!mysqli_query($conn, $stmt)) {
        $erros[] = mysqli_error($conn) . ' | ' . substr($stmt, 0, 80);
    } else {
        $ok++;
    }
}

echo "Migration 007: {$ok} statement(s) executado(s).\n";
if ($erros) {
    echo "Erros:\n" . implode("\n", $erros) . "\n";
}
