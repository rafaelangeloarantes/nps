<?php
/**
 * Preenche confirmation_status_api a partir da API Inteegra (eventos já sincronizados)
 */
require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../modules/integracao/inteegra_client.php';

$r = mysqli_query($conn, "SHOW COLUMNS FROM participante_eventos LIKE 'confirmation_status_api'");
if (!mysqli_num_rows($r)) {
    echo "Execute sql/migrate_006_confirmation_status.php antes.\n";
    exit(1);
}

$eventos = mysqli_query($conn, "
    SELECT id, nome, id_integracao
    FROM eventos
    WHERE ativo = 1 AND id_integracao IS NOT NULL AND id_integracao != ''
    ORDER BY id ASC
");

$totalAtualizados = 0;

while ($evento = mysqli_fetch_assoc($eventos)) {
    $evento_id = (int) $evento['id'];
    echo "Evento #{$evento_id} ({$evento['id_integracao']}) — {$evento['nome']}\n";

    $auth = inteegra_autenticar_evento($conn, $evento_id);
    if (!$auth['ok']) {
        echo "  API indisponível: {$auth['error']}\n";
        continue;
    }

    $fetch = inteegra_buscar_todos_guests(
        $auth['token'],
        $auth['event_id_api'],
        50,
        $auth['guests_base'] ?? null
    );
    if (!$fetch['ok']) {
        echo "  Erro guests: {$fetch['error']}\n";
        continue;
    }

    $stmt = mysqli_prepare(
        $conn,
        'UPDATE participante_eventos
         SET confirmation_status_api = ?
         WHERE evento_id = ? AND guest_id_api = ?'
    );

    $atualizados = 0;
    foreach ($fetch['guests'] as $guest) {
        if (!is_array($guest)) {
            continue;
        }
        $guest_id = (int) ($guest['Id'] ?? 0);
        if ($guest_id <= 0) {
            continue;
        }
        $status = strtoupper(trim((string) ($guest['ConfirmationStatus'] ?? '')));
        mysqli_stmt_bind_param($stmt, 'sii', $status, $evento_id, $guest_id);
        mysqli_stmt_execute($stmt);
        if (mysqli_stmt_affected_rows($stmt) > 0) {
            $atualizados++;
        }
    }
    mysqli_stmt_close($stmt);

    echo "  Atualizados: {$atualizados}\n";
    $totalAtualizados += $atualizados;
}

echo "\nTotal de vínculos atualizados: {$totalAtualizados}\n";
