<?php
require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../modules/auth/middleware.php';
require_once __DIR__ . '/../modules/auth/permissoes.php';

exigir_admin_master();

$draw = (int) ($_GET['draw'] ?? 1);
[$start, $length] = datatable_paginacao($_GET['start'] ?? 0, $_GET['length'] ?? 10);
$search = trim($_GET['search']['value'] ?? '');

$total = datatable_count($conn, 'SELECT COUNT(*) AS t FROM usuarios WHERE ativo = 1');

$where = 'u.ativo = 1';
if ($search !== '') {
    $s = '%' . mysqli_real_escape_string($conn, $search) . '%';
    $where .= " AND (u.nome LIKE '{$s}' OR u.email LIKE '{$s}' OR c.nome LIKE '{$s}')";
}

$join = ' LEFT JOIN contratos c ON c.id = u.contrato_id';
$total_filtrado = datatable_count($conn, "SELECT COUNT(*) AS t FROM usuarios u{$join} WHERE {$where}");

$sql = "SELECT u.id, u.nome, u.email, u.perfil, c.nome AS contrato_nome,
               u.ultimo_login, u.ativo
        FROM usuarios u{$join}
        WHERE {$where}
        ORDER BY u.id DESC
        LIMIT {$start}, {$length}";
$result = mysqli_query($conn, $sql);

$data = [];
while ($row = mysqli_fetch_assoc($result)) {
    $data[] = [
        'id' => $row['id'],
        'nome' => $row['nome'],
        'email' => $row['email'],
        'perfil' => badge_perfil($row['perfil']),
        'contrato' => $row['contrato_nome'] ? h($row['contrato_nome']) : '—',
        'status' => badge_ativo($row['ativo']),
        'ultimo_login' => formatar_data_hora($row['ultimo_login']),
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
