<?php
/**
 * Migration 006 — Governança de usuários (idempotente)
 * Executar: php sql/migrate_006_usuarios_governanca.php
 */
require __DIR__ . '/../bootstrap.php';

function coluna_existe($conn, $tabela, $coluna)
{
    $tabela = mysqli_real_escape_string($conn, $tabela);
    $coluna = mysqli_real_escape_string($conn, $coluna);
    $r = mysqli_query($conn, "SHOW COLUMNS FROM `{$tabela}` LIKE '{$coluna}'");
    return $r && mysqli_num_rows($r) > 0;
}

function executar($conn, $sql, $label)
{
    if (mysqli_query($conn, $sql)) {
        echo "OK: {$label}\n";
        return true;
    }
    echo "ERRO ({$label}): " . mysqli_error($conn) . "\n";
    return false;
}

// Expandir ENUM para incluir novos valores antes de migrar dados
$r = mysqli_query($conn, "SHOW COLUMNS FROM usuarios LIKE 'perfil'");
$row = $r ? mysqli_fetch_assoc($r) : null;
$tipo = $row['Type'] ?? '';
if ($tipo && strpos($tipo, 'admin_master') === false) {
    executar(
        $conn,
        "ALTER TABLE `usuarios` MODIFY COLUMN `perfil` ENUM('admin_master','usuario','admin','editor','viewer') NOT NULL DEFAULT 'admin_master'",
        'expandir ENUM perfil'
    );
    executar($conn, "UPDATE `usuarios` SET `perfil` = 'admin_master' WHERE `perfil` IN ('admin', 'editor')", 'migrar admin/editor');
    executar($conn, "UPDATE `usuarios` SET `perfil` = 'usuario' WHERE `perfil` = 'viewer'", 'migrar viewer');
    executar(
        $conn,
        "ALTER TABLE `usuarios` MODIFY COLUMN `perfil` ENUM('admin_master','usuario') NOT NULL DEFAULT 'admin_master'",
        'ENUM perfil final'
    );
}

$colunas = [
    'contrato_id' => 'ADD COLUMN `contrato_id` INT NULL AFTER `perfil`',
    'perm_editar_evento' => 'ADD COLUMN `perm_editar_evento` TINYINT(1) NOT NULL DEFAULT 0 AFTER `contrato_id`',
    'perm_sincronizar_evento' => 'ADD COLUMN `perm_sincronizar_evento` TINYINT(1) NOT NULL DEFAULT 0 AFTER `perm_editar_evento`',
    'perm_editar_participante' => 'ADD COLUMN `perm_editar_participante` TINYINT(1) NOT NULL DEFAULT 0 AFTER `perm_sincronizar_evento`',
    'perm_editar_pesquisa' => 'ADD COLUMN `perm_editar_pesquisa` TINYINT(1) NOT NULL DEFAULT 0 AFTER `perm_editar_participante`',
    'perm_sincronizar_pesquisa' => 'ADD COLUMN `perm_sincronizar_pesquisa` TINYINT(1) NOT NULL DEFAULT 0 AFTER `perm_editar_pesquisa`',
    'token_reset' => 'ADD COLUMN `token_reset` VARCHAR(64) NULL AFTER `bloqueado_ate`',
    'token_reset_expira' => 'ADD COLUMN `token_reset_expira` DATETIME NULL AFTER `token_reset`',
];

foreach ($colunas as $nome => $ddl) {
    if (!coluna_existe($conn, 'usuarios', $nome)) {
        executar($conn, "ALTER TABLE `usuarios` {$ddl}", "coluna {$nome}");
    } else {
        echo "SKIP: coluna {$nome} já existe\n";
    }
}

// Índice contrato
$r = mysqli_query($conn, "SHOW INDEX FROM usuarios WHERE Key_name = 'idx_usuarios_contrato'");
if (!$r || mysqli_num_rows($r) === 0) {
    executar($conn, 'ALTER TABLE `usuarios` ADD KEY `idx_usuarios_contrato` (`contrato_id`)', 'índice contrato');
}

// Índice token
$r = mysqli_query($conn, "SHOW INDEX FROM usuarios WHERE Key_name = 'idx_usuarios_token_reset'");
if (!$r || mysqli_num_rows($r) === 0) {
    executar($conn, 'ALTER TABLE `usuarios` ADD KEY `idx_usuarios_token_reset` (`token_reset`)', 'índice token_reset');
}

// FK contrato (se não existir)
$r = mysqli_query($conn, "SELECT CONSTRAINT_NAME FROM information_schema.TABLE_CONSTRAINTS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'usuarios' AND CONSTRAINT_NAME = 'fk_usuarios_contrato'");
if (!$r || mysqli_num_rows($r) === 0) {
    executar(
        $conn,
        'ALTER TABLE `usuarios` ADD CONSTRAINT `fk_usuarios_contrato` FOREIGN KEY (`contrato_id`) REFERENCES `contratos` (`id`) ON DELETE SET NULL',
        'FK contrato'
    );
}

echo "\nMigration 006 concluída.\n";
