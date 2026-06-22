<?php
require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../modules/auth/middleware.php';
require_once __DIR__ . '/../modules/auth/permissoes.php';
require_once __DIR__ . '/../modules/credenciamentos/functions.php';

$draw = (int) ($_GET['draw'] ?? 1);
[$start, $length] = datatable_paginacao($_GET['start'] ?? 0, $_GET['length'] ?? 10);
$search = trim($_GET['search']['value'] ?? '');
$evento_id = (int) ($_GET['evento_id'] ?? 0);

$where = 'cr.ativo = 1';
if ($evento_id > 0) {
    exigir_acesso_evento($conn, $evento_id);
    $where .= ' AND cr.evento_id = ' . $evento_id;
}

$join = ' INNER JOIN eventos e ON e.id = cr.evento_id INNER JOIN participantes p ON p.id = cr.participante_id';
$where .= sql_filtro_contrato('e.contrato_id');

$total = datatable_count($conn, "SELECT COUNT(*) AS t FROM credenciamentos cr{$join} WHERE {$where}");
$count_sql = "SELECT COUNT(*) AS t FROM credenciamentos cr{$join} WHERE {$where}";
if ($search !== '') {
    $s = '%' . mysqli_real_escape_string($conn, $search) . '%';
    $count_sql .= " AND (p.nome_completo LIKE '{$s}' OR p.email LIKE '{$s}' OR e.nome LIKE '{$s}')";
}
$total_filtrado = datatable_count($conn, $count_sql);

$sql = "SELECT cr.id, e.nome AS evento_nome, p.nome_completo, p.email, cr.status, cr.ultima_sincronizacao
        FROM credenciamentos cr{$join}
        WHERE {$where}";

if ($search !== '') {
    $s = '%' . mysqli_real_escape_string($conn, $search) . '%';
    $sql .= " AND (p.nome_completo LIKE '{$s}' OR p.email LIKE '{$s}' OR e.nome LIKE '{$s}')";
}
$sql .= " ORDER BY cr.id DESC LIMIT {$start}, {$length}";
$result = mysqli_query($conn, $sql);

$data = [];
while ($row = mysqli_fetch_assoc($result)) {
    $status_badge = credenciamento_badge_html($row['status']);
    $data[] = [
        'id' => $row['id'],
        'evento_nome' => $row['evento_nome'],
        'participante' => $row['nome_completo'],
        'email' => $row['email'],
        'status' => $status_badge,
        'ultima_sync' => formatar_data_hora($row['ultima_sincronizacao']),
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
