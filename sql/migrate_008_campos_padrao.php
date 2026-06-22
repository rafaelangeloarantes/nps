<?php
/**
 * Migration 008 — Campos padrão NPS
 */
require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../modules/campos_padrao/functions.php';

$sql = file_get_contents(__DIR__ . '/migrations/008_campos_padrao.sql');
$statements = array_filter(array_map('trim', explode(';', $sql)));
foreach ($statements as $stmt) {
    if ($stmt === '') {
        continue;
    }
    mysqli_query($conn, $stmt);
}

campo_padrao_garantir_estrutura($conn);
$seeds = campo_padrao_instalar_seeds($conn);
$migrados = campo_padrao_migrar_mapeamentos_existentes($conn);

echo "Migration 008 OK\n";
echo "Seeds inseridos/verificados: {$seeds}\n";
echo "Mapeamentos migrados: {$migrados}\n";
