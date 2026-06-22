<?php
require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../modules/auth/middleware.php';
require_once __DIR__ . '/../modules/auth/permissoes.php';
require_once __DIR__ . '/../modules/log/functions.php';

exigir_admin_master();

log_garantir_estrutura($conn);

$draw = (int) ($_GET['draw'] ?? 1);
[$start, $length] = datatable_paginacao($_GET['start'] ?? 0, $_GET['length'] ?? 10);
$search = trim($_GET['search']['value'] ?? '');

$filtros = [
    'tipo' => $_GET['tipo'] ?? '',
    'nivel' => $_GET['nivel'] ?? '',
    'modulo' => $_GET['modulo'] ?? '',
    'usuario_id' => $_GET['usuario_id'] ?? '',
    'data_inicio' => $_GET['data_inicio'] ?? '',
    'data_fim' => $_GET['data_fim'] ?? '',
];

$where = log_montar_filtros($conn, $filtros);
$sub = log_sql_unificado();

$total = datatable_count($conn, "SELECT COUNT(*) AS t FROM {$sub}");

if ($search !== '') {
    $s = '%' . mysqli_real_escape_string($conn, $search) . '%';
    $where .= " AND (logs.modulo LIKE '{$s}' OR logs.acao LIKE '{$s}' OR logs.mensagem LIKE '{$s}' OR logs.ip LIKE '{$s}')";
}

$total_filtrado = datatable_count($conn, "SELECT COUNT(*) AS t FROM {$sub} WHERE {$where}");

$order_col = (int) ($_GET['order'][0]['column'] ?? 0);
$order_dir = strtolower($_GET['order'][0]['dir'] ?? 'desc') === 'asc' ? 'ASC' : 'DESC';
$colunas_ordem = [
    'logs.criado_em',
    'logs.tipo',
    'logs.nivel',
    'logs.modulo',
    'logs.acao',
    'u.nome',
    'logs.mensagem',
    'logs.id',
];
$order_by = $colunas_ordem[$order_col] ?? 'logs.criado_em';

$sql = "SELECT logs.chave, logs.tipo, logs.nivel, logs.modulo, logs.acao, logs.mensagem,
               logs.entidade_id, logs.usuario_id, logs.ip, logs.criado_em,
               u.nome AS usuario_nome
        FROM {$sub}
        LEFT JOIN usuarios u ON u.id = logs.usuario_id
        WHERE {$where}
        ORDER BY {$order_by} {$order_dir}
        LIMIT {$start}, {$length}";

$result = mysqli_query($conn, $sql);
$data = [];

while ($row = mysqli_fetch_assoc($result)) {
    $mensagem = $row['mensagem'] ?? '';
    if ($mensagem === '' || $mensagem === null) {
        $mensagem = '—';
    }

    $data[] = [
        'criado_em' => formatar_data_hora($row['criado_em']),
        'tipo' => log_badge_tipo($row['tipo']),
        'nivel' => log_badge_nivel($row['nivel']),
        'modulo' => h($row['modulo']),
        'acao' => h($row['acao']),
        'usuario' => $row['usuario_nome'] ? h($row['usuario_nome']) : '—',
        'mensagem' => h(mb_substr((string) $mensagem, 0, 80, 'UTF-8')),
        'detalhes' => h($row['chave']),
    ];
}

header('Content-Type: application/json; charset=utf-8');
echo json_encode([
    'draw' => $draw,
    'recordsTotal' => $total,
    'recordsFiltered' => $total_filtrado,
    'data' => $data,
]);
