<?php
require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../modules/auth/middleware.php';
require_once __DIR__ . '/../modules/auth/permissoes.php';
require_once __DIR__ . '/../modules/campos_padrao/functions.php';

exigir_admin_master();

$draw = (int) ($_GET['draw'] ?? 1);
[$start, $length] = datatable_paginacao($_GET['start'] ?? 0, $_GET['length'] ?? 10);
$search = trim($_GET['search']['value'] ?? '');
$categoria = trim($_GET['categoria'] ?? '');

$where = 'ativo = 1';
if ($categoria !== '') {
    $cats = array_keys(campo_padrao_categorias());
    if (in_array($categoria, $cats, true)) {
        $where .= " AND categoria = '" . mysqli_real_escape_string($conn, $categoria) . "'";
    }
}

$total = datatable_count($conn, "SELECT COUNT(*) AS t FROM nps_campos_padrao WHERE {$where}");
$sql = "SELECT id, nome, slug, categoria, tipo_dado, tipo_grafico_sugerido, sistema, ordem, criado_em
        FROM nps_campos_padrao WHERE {$where}";

if ($search !== '') {
    $s = '%' . mysqli_real_escape_string($conn, $search) . '%';
    $sql .= " AND (nome LIKE '{$s}' OR slug LIKE '{$s}')";
}

$count_sql = "SELECT COUNT(*) AS t FROM nps_campos_padrao WHERE {$where}";
if ($search !== '') {
    $count_sql .= " AND (nome LIKE '{$s}' OR slug LIKE '{$s}')";
}
$total_filtrado = datatable_count($conn, $count_sql);
$sql .= " ORDER BY ordem ASC, nome ASC LIMIT {$start}, {$length}";
$result = mysqli_query($conn, $sql);

$cats = campo_padrao_categorias();
$data = [];
while ($row = mysqli_fetch_assoc($result)) {
    $data[] = [
        'id' => (int) $row['id'],
        'nome' => $row['nome'],
        'slug' => $row['slug'],
        'categoria' => $cats[$row['categoria']] ?? $row['categoria'],
        'tipo_dado' => $row['tipo_dado'],
        'tipo_grafico' => $row['tipo_grafico_sugerido'],
        'sistema' => (int) $row['sistema'] ? 'Sim' : 'Não',
        'ordem' => (int) $row['ordem'],
        'criado_em' => formatar_data_hora($row['criado_em']),
        'acoes' => (int) $row['id'],
        'eh_sistema' => (int) $row['sistema'],
    ];
}

header('Content-Type: application/json; charset=utf-8');
echo json_encode([
    'draw' => $draw,
    'recordsTotal' => $total,
    'recordsFiltered' => $total_filtrado,
    'data' => $data,
]);
