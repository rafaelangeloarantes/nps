<?php
/**
 * install.php — Instalação inicial do banco de dados
 * Acesso: install.php?token=INSTALL_TOKEN do .env
 */

require_once __DIR__ . '/bootstrap.php';

$token_esperado = $_ENV['INSTALL_TOKEN'] ?? '';
$token_recebido = $_GET['token'] ?? '';

if ($token_esperado === '' || !hash_equals($token_esperado, $token_recebido)) {
    http_response_code(403);
    die('Acesso negado.');
}

header('Content-Type: text/html; charset=utf-8');

$sql_file = __DIR__ . '/sql/structure/001_schema_completo.sql';
if (!is_file($sql_file)) {
    die('Arquivo SQL não encontrado.');
}

$sql_content = file_get_contents($sql_file);
$statements = array_filter(array_map('trim', preg_split('/;\s*\n/', $sql_content)));

$erros = [];
$ok = 0;

foreach ($statements as $stmt) {
    if ($stmt === '' || stripos($stmt, '--') === 0) {
        continue;
    }
    if (!mysqli_query($conn, $stmt)) {
        $erros[] = mysqli_error($conn) . ' — ' . substr($stmt, 0, 80);
    } else {
        $ok++;
    }
}

// Usuário admin padrão
$admin_email = 'admin@nps.local';
$admin_nome = 'Administrador';
$admin_senha = password_hash('admin123', PASSWORD_BCRYPT, ['cost' => 12]);

$check = mysqli_prepare($conn, 'SELECT id FROM usuarios WHERE email = ? LIMIT 1');
mysqli_stmt_bind_param($check, 's', $admin_email);
mysqli_stmt_execute($check);
$res = mysqli_stmt_get_result($check);
$existe = mysqli_fetch_assoc($res);
mysqli_stmt_close($check);

if (!$existe) {
    $ins = mysqli_prepare($conn, 'INSERT INTO usuarios (nome, email, senha_hash, perfil) VALUES (?, ?, ?, ?)');
    $perfil = 'admin_master';
    mysqli_stmt_bind_param($ins, 'ssss', $admin_nome, $admin_email, $admin_senha, $perfil);
    if (mysqli_stmt_execute($ins)) {
        $ok++;
    } else {
        $erros[] = 'Erro ao criar admin: ' . mysqli_error($conn);
    }
    mysqli_stmt_close($ins);
}

echo '<h1>Instalação NPS Relatórios</h1>';
echo '<p>Statements executados: ' . $ok . '</p>';
if ($erros) {
    echo '<h2>Avisos/Erros</h2><ul>';
    foreach ($erros as $e) {
        echo '<li>' . htmlspecialchars($e) . '</li>';
    }
    echo '</ul>';
} else {
    echo '<p style="color:green">Instalação concluída com sucesso.</p>';
}
echo '<p><strong>Login padrão:</strong> admin@nps.local / admin123</p>';
echo '<p><a href="login.php">Ir para login</a></p>';
