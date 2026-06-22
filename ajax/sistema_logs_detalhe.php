<?php
require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../modules/auth/middleware.php';
require_once __DIR__ . '/../modules/auth/permissoes.php';
require_once __DIR__ . '/../modules/log/functions.php';

exigir_admin_master();

$chave = trim($_GET['chave'] ?? '');
if ($chave === '') {
    json_response('error', 'Chave do log inválida.');
}

$row = log_buscar_por_chave($conn, $chave);
if (!$row) {
    json_response('error', 'Registro não encontrado.');
}

$detalhes = [];
if (!empty($row['detalhes_json'])) {
    $decoded = json_decode($row['detalhes_json'], true);
    if (is_array($decoded)) {
        $detalhes = $decoded;
    }
}

json_response('success', 'OK', [
    'chave' => $row['chave'] ?? $chave,
    'origem' => $row['origem'] ?? 'auditoria',
    'tipo' => log_tipo_label($row['tipo'] ?? 'acao'),
    'nivel' => log_nivel_label($row['nivel'] ?? 'info'),
    'modulo' => $row['modulo'] ?? '',
    'acao' => $row['acao'] ?? '',
    'mensagem' => $row['mensagem'] ?? '',
    'entidade_id' => $row['entidade_id'] ?? null,
    'usuario_id' => $row['usuario_id'] ?? null,
    'ip' => $row['ip'] ?? '',
    'user_agent' => $row['user_agent'] ?? '',
    'criado_em' => formatar_data_hora($row['criado_em'] ?? ''),
    'detalhes' => $detalhes,
]);
