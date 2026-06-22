<?php
require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../modules/auth/middleware.php';
require_once __DIR__ . '/../modules/auth/permissoes.php';
require_once __DIR__ . '/../modules/dashboard/templates.php';

$draw = (int) ($_GET['draw'] ?? 1);
[$start, $length] = datatable_paginacao($_GET['start'] ?? 0, $_GET['length'] ?? 10);
$search = trim($_GET['search']['value'] ?? '');

$where = 't.ativo = 1';
$cid = obter_contrato_usuario();
if ($cid) {
    $where .= ' AND (t.contrato_id IS NULL OR t.contrato_id = ' . (int) $cid . ')';
}

$total = datatable_count($conn, "SELECT COUNT(*) AS t FROM dashboard_templates t WHERE {$where}");

$sql = "SELECT t.id, t.nome, t.descricao, t.criado_em,
               (SELECT COUNT(*) FROM dashboard_relatorios r WHERE r.template_id = t.id AND r.ativo = 1) AS total_relatorios
        FROM dashboard_templates t WHERE {$where}";

if ($search !== '') {
    $s = '%' . mysqli_real_escape_string($conn, $search) . '%';
    $sql .= " AND (t.nome LIKE '{$s}' OR t.descricao LIKE '{$s}')";
}

$count_sql = "SELECT COUNT(*) AS t FROM dashboard_templates t WHERE {$where}";
if ($search !== '') {
    $count_sql .= " AND (t.nome LIKE '{$s}' OR t.descricao LIKE '{$s}')";
}
$total_filtrado = datatable_count($conn, $count_sql);
$sql .= " ORDER BY t.id DESC LIMIT {$start}, {$length}";
$result = mysqli_query($conn, $sql);

$data = [];
while ($row = mysqli_fetch_assoc($result)) {
    $data[] = [
        'id' => (int) $row['id'],
        'nome' => $row['nome'],
        'descricao' => $row['descricao'] ?: '—',
        'total_relatorios' => (int) $row['total_relatorios'],
        'criado_em' => formatar_data_hora($row['criado_em']),
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
