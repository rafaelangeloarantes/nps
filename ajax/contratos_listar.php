<?php
require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../modules/auth/middleware.php';
require_once __DIR__ . '/../modules/auth/permissoes.php';

exigir_admin_master();

$draw = (int) ($_GET['draw'] ?? 1);
[$start, $length] = datatable_paginacao($_GET['start'] ?? 0, $_GET['length'] ?? 10);
$search = trim($_GET['search']['value'] ?? '');

$total = datatable_count($conn, 'SELECT COUNT(*) AS t FROM contratos WHERE ativo = 1');

$where = 'ativo = 1';
if ($search !== '') {
    $s = '%' . mysqli_real_escape_string($conn, $search) . '%';
    $where .= " AND nome LIKE '{$s}'";
}

$total_filtrado = datatable_count($conn, "SELECT COUNT(*) AS t FROM contratos WHERE {$where}");
$sql = "SELECT id, nome, ativo, criado_em FROM contratos WHERE {$where}";
$sql .= " ORDER BY id DESC LIMIT {$start}, {$length}";
$result = mysqli_query($conn, $sql);

$data = [];
while ($row = mysqli_fetch_assoc($result)) {
    $data[] = [
        'id' => $row['id'],
        'nome' => $row['nome'],
        'status' => badge_ativo($row['ativo']),
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
