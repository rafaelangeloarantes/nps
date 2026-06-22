<?php
require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../modules/auth/middleware.php';
require_once __DIR__ . '/../modules/auth/permissoes.php';
require_once __DIR__ . '/../modules/pesquisas/respostas.php';

pesquisa_resposta_garantir_estrutura($conn);

$draw = (int) ($_GET['draw'] ?? 1);
[$start, $length] = datatable_paginacao($_GET['start'] ?? 0, $_GET['length'] ?? 10);
$search = trim($_GET['search']['value'] ?? '');
$evento_id = (int) ($_GET['evento_id'] ?? 0);

$where = 'p.ativo = 1';
if ($evento_id > 0) {
    exigir_acesso_evento($conn, $evento_id);
    $where .= ' AND p.evento_id = ' . $evento_id;
}

$join = ' INNER JOIN eventos e ON e.id = p.evento_id';
$where .= sql_filtro_contrato('e.contrato_id');

$total = datatable_count($conn, "SELECT COUNT(*) AS t FROM pesquisas p{$join} WHERE {$where}");

$count_sql = "SELECT COUNT(*) AS t FROM pesquisas p{$join} WHERE {$where}";
if ($search !== '') {
    $s = '%' . mysqli_real_escape_string($conn, $search) . '%';
    $count_sql .= " AND (p.titulo LIKE '{$s}' OR e.nome LIKE '{$s}' OR p.identificador_integracao LIKE '{$s}')";
}
$total_filtrado = datatable_count($conn, $count_sql);

$sql = "SELECT p.id, p.titulo AS nome, e.nome AS evento_nome, p.identificador_integracao,
               p.ultima_sincronizacao, p.ativo,
               (SELECT COUNT(*) FROM relatorio_pesquisa_respostas r WHERE r.pesquisa_id = p.id) AS total_respostas
        FROM pesquisas p{$join}
        WHERE {$where}";

if ($search !== '') {
    $s = '%' . mysqli_real_escape_string($conn, $search) . '%';
    $sql .= " AND (p.titulo LIKE '{$s}' OR e.nome LIKE '{$s}' OR p.identificador_integracao LIKE '{$s}')";
}
$sql .= " ORDER BY p.id DESC LIMIT {$start}, {$length}";
$result = mysqli_query($conn, $sql);

$data = [];
while ($row = mysqli_fetch_assoc($result)) {
    $data[] = [
        'id' => $row['id'],
        'nome' => $row['nome'],
        'evento_nome' => $row['evento_nome'],
        'identificador_integracao' => $row['identificador_integracao'],
        'status' => badge_ativo($row['ativo']),
        'total_respostas' => (int) ($row['total_respostas'] ?? 0),
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
