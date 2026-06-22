<?php
require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../modules/auth/middleware.php';
require_once __DIR__ . '/../modules/pesquisas/campos.php';

try {
    $pesquisa_id = (int) ($_POST['pesquisa_id'] ?? 0);
    if ($pesquisa_id <= 0) {
        json_response('error', 'Pesquisa inválida.');
    }

    $campos_raw = $_POST['campos'] ?? '[]';
    if (is_string($campos_raw)) {
        $campos = json_decode($campos_raw, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            json_response('error', 'Dados de campos inválidos.');
        }
        $campos = is_array($campos) ? $campos : [];
    } elseif (is_array($campos_raw)) {
        $campos = $campos_raw;
    } else {
        $campos = [];
    }

    $resultado = pesquisa_campo_salvar_lote($conn, $pesquisa_id, $campos);
    json_response($resultado['status'] ?? 'error', $resultado['message'] ?? 'Erro ao salvar mapeamento.');
} catch (Throwable $e) {
    error_log('pesquisas_campos_salvar: ' . $e->getMessage());
    json_response('error', 'Erro ao salvar mapeamento: ' . $e->getMessage());
}
