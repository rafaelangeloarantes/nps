<?php
/**
 * Auditoria centralizada — compatibilidade retroativa
 */
require_once __DIR__ . '/../log/functions.php';

function auditoria_garantir_estrutura($conn)
{
    log_garantir_estrutura($conn);
}

function auditoria_obter_ip()
{
    return log_obter_ip();
}

function auditoria_obter_user_agent()
{
    return log_obter_user_agent();
}

/**
 * Registra evento de auditoria (alias para log_acao).
 */
function auditoria_registrar($conn, $modulo, $acao, $entidade_id = null, array $detalhes = [])
{
    return log_acao($conn, $modulo, $acao, $entidade_id, $detalhes);
}
