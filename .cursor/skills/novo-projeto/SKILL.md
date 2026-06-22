# Skill: Criar Novo Projeto

Use esta skill quando o usuário pedir para criar um novo projeto, iniciar um sistema do zero ou montar a estrutura base.

## Estrutura de Pastas

Criar a seguinte estrutura:

```
/projeto
├─ .vscode/
│  └─ sftp.json
├─ ajax/
├─ modules/
├─ js/
│  ├─ datatable-config.js   # NpsDataTable.create() — listagens padronizadas
│  └─ main.js
├─ css/
│  ├─ style.css
│  └─ datatable-override.css
├─ img/
├─ sql/
│  ├─ structure/
│  ├─ migrations/
│  └─ seeds/
├─ backup/
├─ logs/
├─ upload/
│  └─ imagens/
│     └─ .htaccess
├─ .env
├─ .gitignore
├─ .gitattributes
├─ backup.php
├─ bootstrap.php
├─ config.php
├─ index.php
└─ readme.md
```

## Arquivo: `.env`

```env
APP_NAME=NomeDoProjeto
APP_ENV=development
APP_URL=http://localhost/projeto

DB_HOST=127.0.0.1
DB_PORT=3306
DB_NAME=nome_banco
DB_USER=root
DB_PASS=
DB_CHARSET=utf8mb4

# Opcional: força bust global em todos os assets (ex.: após deploy)
# APP_ASSET_VERSION=20260612
```

## Arquivo: `bootstrap.php`

```php
<?php
// bootstrap.php - Inicialização do sistema
// Carrega variáveis de ambiente e configurações

// Carregar .env
$env_file = __DIR__ . '/.env';
if (file_exists($env_file)) {
    $lines = file($env_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        if (strpos($line, '=') === false) continue;
        list($key, $value) = explode('=', $line, 2);
        $_ENV[trim($key)] = trim($value);
    }
}

// Configurações de erro conforme ambiente
if (($_ENV['APP_ENV'] ?? 'production') === 'development') {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
    ini_set('log_errors', 1);
    ini_set('error_log', __DIR__ . '/logs/error.log');
}

// Timezone
date_default_timezone_set('America/Sao_Paulo');

// Charset
header('Content-Type: text/html; charset=UTF-8');

// Incluir configuração do banco
require_once __DIR__ . '/config.php';
```

## Arquivo: `config.php`

```php
<?php
// config.php - Conexão com banco de dados

$conn = mysqli_connect(
    $_ENV['DB_HOST'] ?? '127.0.0.1',
    $_ENV['DB_USER'] ?? 'root',
    $_ENV['DB_PASS'] ?? '',
    $_ENV['DB_NAME'] ?? ''
);

if (!$conn) {
    error_log('Erro de conexão MySQL: ' . mysqli_connect_error());
    die('Erro interno do sistema. Tente novamente mais tarde.');
}

mysqli_set_charset($conn, $_ENV['DB_CHARSET'] ?? 'utf8mb4');
```

## Arquivo: `.gitignore`

```gitignore
.env
.env.*
!.env.example

.vscode/*
!.vscode/settings.json
!.vscode/extensions.json

logs/
backup/
upload/imagens/*
!upload/imagens/.htaccess

vendor/
node_modules/
*.log
*.zip
```

## Arquivo: `.gitattributes`

```gitattributes
* text=auto
*.php text eol=lf
*.js text eol=lf
*.css text eol=lf
*.md text eol=lf
*.sql text eol=lf
*.json text eol=lf
*.png binary
*.jpg binary
*.zip binary
*.docx binary
```

## Arquivo: `.vscode/sftp.json`

```json
{
    "name": "Projeto - Producao",
    "host": "SEU_HOST",
    "protocol": "sftp",
    "port": 22,
    "username": "SEU_USUARIO",
    "password": "SUA_SENHA",
    "remotePath": "/caminho/remoto/projeto",
    "uploadOnSave": false,
    "useTempFile": false,
    "openSsh": false,
    "ignore": [
        "**/.git/**",
        "**/.gitignore",
        "**/.vscode/**",
        "**/node_modules/**",
        "**/vendor/**",
        "**/backup/**",
        "**/logs/**"
    ]
}
```

## Arquivo: `upload/imagens/.htaccess`

```apache
# Bloquear execução de scripts na pasta de uploads
<FilesMatch "\.(php|phtml|php3|php4|php5|pl|py|cgi|sh)$">
    Order Deny,Allow
    Deny from All
</FilesMatch>
```

## Arquivo: `backup.php`

```php
<?php
// backup.php - Backup local automatizado
// Executar via CLI: php backup.php

$PROJECT_NAME = 'nome-do-projeto';
$DB = [
    'host'     => '127.0.0.1',
    'port'     => 3306,
    'dbname'   => 'banco',
    'user'     => 'usuario',
    'password' => 'senha',
    'charset'  => 'utf8mb4',
];
$WEB_TOKEN       = ''; // vazio = bloquear via web
$AUTO_GIT_COMMIT = true;
$AUTO_GIT_PUSH   = false;

// Bloquear acesso via web se token vazio
if (php_sapi_name() !== 'cli') {
    if (empty($WEB_TOKEN) || ($_GET['token'] ?? '') !== $WEB_TOKEN) {
        http_response_code(403);
        die('Acesso negado.');
    }
}

$timestamp  = date('Y-m-d_H-i-s');
$backup_dir = __DIR__ . '/backup';
if (!is_dir($backup_dir)) mkdir($backup_dir, 0755, true);

// Dump SQL
$sql_file = "{$backup_dir}/{$PROJECT_NAME}_{$timestamp}.sql";
$cmd = sprintf(
    'mysqldump -h%s -P%d -u%s -p%s %s --default-character-set=%s > %s 2>&1',
    $DB['host'], $DB['port'], $DB['user'], $DB['password'],
    $DB['dbname'], $DB['charset'], $sql_file
);
exec($cmd, $output, $return);
echo $return === 0 ? "SQL OK: {$sql_file}\n" : "SQL ERRO\n";

// ZIP do projeto
$zip_file = "{$backup_dir}/{$PROJECT_NAME}_{$timestamp}.zip";
$exclude  = '--exclude=./backup/* --exclude=./.git/*';
exec("cd " . __DIR__ . " && zip -r {$zip_file} . {$exclude} 2>&1", $out2, $ret2);
echo $ret2 === 0 ? "ZIP OK: {$zip_file}\n" : "ZIP ERRO\n";

// Git commit automático
if ($AUTO_GIT_COMMIT) {
    exec('git add . && git commit -m "backup: ' . $timestamp . '"', $out3, $ret3);
    echo $ret3 === 0 ? "GIT COMMIT OK\n" : "GIT COMMIT ERRO\n";
    if ($AUTO_GIT_PUSH) {
        exec('git push', $out4, $ret4);
        echo $ret4 === 0 ? "GIT PUSH OK\n" : "GIT PUSH ERRO\n";
    }
}

echo "\nBackup finalizado: {$timestamp}\n";
```

## Arquivo: `index.php` (template base)

```php
<?php
require_once __DIR__ . '/bootstrap.php';
$page_title = $_ENV['APP_NAME'] ?? 'Sistema';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($page_title) ?></title>
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <!-- CSS do projeto -->
    <link href="css/style.css" rel="stylesheet">
</head>
<body>

    <header>
        <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
            <div class="container">
                <a class="navbar-brand" href="index.php">
                    <?= htmlspecialchars($page_title) ?>
                </a>
            </div>
        </nav>
    </header>

    <main class="container py-4">
        <h1>Bem-vindo ao sistema</h1>
        <p>Estrutura base criada com sucesso.</p>
    </main>

    <footer class="bg-light text-center py-3 mt-auto">
        <small class="text-muted">&copy; <?= date('Y') ?> - <?= htmlspecialchars($page_title) ?></small>
    </footer>

    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <!-- JS do projeto -->
    <script src="js/main.js"></script>
</body>
</html>
```

## Arquivo: `css/style.css`

```css
/* ===========================================
   VARIÁVEIS E DESIGN TOKENS
   =========================================== */
:root {
    --primary: #2563eb;
    --primary-hover: #1d4ed8;
    --secondary: #64748b;
    --success: #16a34a;
    --danger: #dc2626;
    --warning: #f59e0b;
    --info: #0ea5e9;
    --bg-light: #f8fafc;
    --bg-dark: #0f172a;
    --text-light: #1e293b;
    --text-dark: #f1f5f9;
    --border: #e2e8f0;
    --radius: 8px;
    --shadow: 0 1px 3px rgba(0,0,0,0.1);
    --font-family: 'Inter', 'Roboto', sans-serif;
}

/* ===========================================
   RESET E BASE
   =========================================== */
body {
    font-family: var(--font-family);
    color: var(--text-light);
    background-color: var(--bg-light);
    line-height: 1.5;
}

/* ===========================================
   DARK MODE
   =========================================== */
body.dark-mode {
    background-color: var(--bg-dark);
    color: var(--text-dark);
}
```

## Arquivo: `js/main.js`

```javascript
/**
 * main.js - Scripts principais do sistema
 */
$(document).ready(function() {

    // Toggle Dark Mode
    $('#btn-dark-mode').on('click', function() {
        $('body').toggleClass('dark-mode');
        var isDark = $('body').hasClass('dark-mode');
        localStorage.setItem('darkMode', isDark ? '1' : '0');
    });

    // Restaurar Dark Mode ao carregar
    if (localStorage.getItem('darkMode') === '1') {
        $('body').addClass('dark-mode');
    }

});
```

## Checklist Pós-Criação

- [ ] `.env` preenchido com dados do banco
- [ ] `config.php` testando conexão com sucesso
- [ ] `backup.php` funcionando via CLI (`php backup.php`)
- [ ] `.gitignore` e `.gitattributes` no lugar
- [ ] Primeiro commit: `git add . && git commit -m "chore: bootstrap inicial"`
- [ ] Remote configurado: `git remote add origin URL`
- [ ] `readme.md` com descrição básica do projeto
