<?php
/**
 * Migration — confirmation_status_api em participante_eventos
 */
require __DIR__ . '/../bootstrap.php';

$r = mysqli_query($conn, "SHOW COLUMNS FROM participante_eventos LIKE 'confirmation_status_api'");
if (!mysqli_num_rows($r)) {
    $sql = "ALTER TABLE `participante_eventos`
            ADD COLUMN `confirmation_status_api` VARCHAR(20) NULL
            COMMENT 'ConfirmationStatus da API Inteegra (CN, NE, etc.)'
            AFTER `guest_id_api`";
    echo mysqli_query($conn, $sql) ? "Coluna confirmation_status_api criada.\n" : mysqli_error($conn) . "\n";
} else {
    echo "Coluna confirmation_status_api já existe.\n";
}
