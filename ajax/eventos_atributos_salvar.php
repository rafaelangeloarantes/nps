<?php
require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../modules/auth/middleware.php';
require_once __DIR__ . '/../modules/eventos/atributos.php';

try {
    $evento_id = (int) ($_POST['evento_id'] ?? 0);
    if ($evento_id <= 0) {
        json_response('error', 'Evento inválido.');
    }

    $campos_raw = $_POST['atributos'] ?? '[]';
    if (is_string($campos_raw)) {
        $campos = json_decode($campos_raw, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            json_response('error', 'Dados de atributos inválidos.');
        }
        $campos = is_array($campos) ? $campos : [];
    } elseif (is_array($campos_raw)) {
        $campos = $campos_raw;
    } else {
        $campos = [];
    }

    $resultado = evento_atributo_salvar_lote($conn, $evento_id, $campos);
    if (!is_array($resultado)) {
        json_response('success', 'Mapeamento salvo com sucesso.');
    }

    json_response(
        $resultado['status'] ?? 'error',
        $resultado['message'] ?? 'Erro ao salvar mapeamento.'
    );
} catch (Throwable $e) {
    error_log('eventos_atributos_salvar: ' . $e->getMessage());
    json_response('error', 'Erro ao salvar mapeamento: ' . $e->getMessage());
}
