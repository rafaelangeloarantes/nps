<?php
require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../modules/auth/middleware.php';
require_once __DIR__ . '/../modules/auth/permissoes.php';
require_once __DIR__ . '/../modules/dashboard/relatorios.php';

$draw = (int) ($_GET['draw'] ?? 1);
[$start, $length] = datatable_paginacao($_GET['start'] ?? 0, $_GET['length'] ?? 10);
$search = trim($_GET['search']['value'] ?? '');
$evento_id = (int) ($_GET['evento_id'] ?? 0);

$where = 'r.ativo = 1';
if ($evento_id > 0) {
    exigir_acesso_evento($conn, $evento_id);
    $where .= ' AND r.evento_id = ' . $evento_id;
}
$where .= sql_filtro_contrato('e.contrato_id');

$total = datatable_count(
    $conn,
    "SELECT COUNT(*) AS t FROM dashboard_relatorios r
     INNER JOIN eventos e ON e.id = r.evento_id WHERE {$where}"
);

$sql = "SELECT r.id, r.nome, r.token, r.chave_prefixo, r.criado_em, r.ultimo_acesso_externo,
               t.nome AS template_nome, e.nome AS evento_nome, e.id AS evento_id
        FROM dashboard_relatorios r
        INNER JOIN dashboard_templates t ON t.id = r.template_id AND t.ativo = 1
        INNER JOIN eventos e ON e.id = r.evento_id AND e.ativo = 1
        WHERE {$where}";

if ($search !== '') {
    $s = '%' . mysqli_real_escape_string($conn, $search) . '%';
    $sql .= " AND (r.nome LIKE '{$s}' OR t.nome LIKE '{$s}' OR e.nome LIKE '{$s}')";
}

$count_sql = "SELECT COUNT(*) AS t FROM dashboard_relatorios r
        INNER JOIN dashboard_templates t ON t.id = r.template_id AND t.ativo = 1
        INNER JOIN eventos e ON e.id = r.evento_id AND e.ativo = 1
        WHERE {$where}";
if ($search !== '') {
    $count_sql .= " AND (r.nome LIKE '{$s}' OR t.nome LIKE '{$s}' OR e.nome LIKE '{$s}')";
}
$total_filtrado = datatable_count($conn, $count_sql);
$sql .= " ORDER BY r.id DESC LIMIT {$start}, {$length}";
$result = mysqli_query($conn, $sql);

$data = [];
while ($row = mysqli_fetch_assoc($result)) {
    $data[] = [
        'id' => (int) $row['id'],
        'nome' => $row['nome'],
        'template_nome' => $row['template_nome'],
        'evento_nome' => $row['evento_nome'],
        'evento_id' => (int) $row['evento_id'],
        'chave_prefixo' => $row['chave_prefixo'] ?: '—',
        'ultimo_acesso' => formatar_data_hora($row['ultimo_acesso_externo']),
        'criado_em' => formatar_data_hora($row['criado_em']),
        'url_publica' => dashboard_relatorio_url_publica($row['token']),
        'acoes' => (int) $row['id'],
    ];
}

header('Content-Type: application/json; charset=utf-8');
echo json_encode([
    'draw' => $draw,
    'recordsTotal' => $total,
    'recordsFiltered' => $total_filtrado,
    'data' => $data,
]);
