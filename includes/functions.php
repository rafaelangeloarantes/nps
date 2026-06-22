<?php
/**
 * Funções globais — template System Designer
 */

function h($str)
{
    return htmlspecialchars((string) $str, ENT_QUOTES, 'UTF-8');
}

function asset_version($relative_path)
{
    static $global_version = null;
    static $cache = [];

    $clean = ltrim(str_replace('\\', '/', (string) strtok($relative_path, '?')), '/');
    if ($clean === '') {
        return '0';
    }

    if (isset($cache[$clean])) {
        return $cache[$clean];
    }

    if ($global_version === null) {
        $env_file = dirname(__DIR__) . '/.env';
        $global_version = '';
        if (is_file($env_file)) {
            foreach (file($env_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
                $line = trim($line);
                if ($line === '' || $line[0] === '#') continue;
                if (strpos($line, 'APP_ASSET_VERSION=') === 0) {
                    $global_version = trim(substr($line, strlen('APP_ASSET_VERSION=')));
                    break;
                }
            }
        }
    }

    $full = dirname(__DIR__) . '/' . $clean;
    $mtime = is_file($full) ? (string) filemtime($full) : '0';
    $cache[$clean] = $global_version !== '' ? $global_version . '.' . $mtime : $mtime;

    return $cache[$clean];
}

function asset($relative_path)
{
    $relative_path = ltrim(str_replace('\\', '/', $relative_path), '/');
    $version = asset_version($relative_path);

    if (strpos($relative_path, '?') !== false) {
        return $relative_path . '&v=' . rawurlencode($version);
    }

    return $relative_path . '?v=' . rawurlencode($version);
}

function asset_url($relative_path)
{
    return asset($relative_path);
}

/**
 * Executa query de contagem para DataTables server-side.
 */
function datatable_count($conn, $sql)
{
    $result = mysqli_query($conn, $sql);
    if (!$result) {
        error_log('datatable_count: ' . mysqli_error($conn) . ' | SQL: ' . $sql);
        return 0;
    }

    $row = mysqli_fetch_assoc($result);
    return (int) ($row['t'] ?? $row['total'] ?? 0);
}

/**
 * Normaliza parâmetros de paginação enviados pelo DataTables.
 */
function datatable_paginacao($start, $length)
{
    $start = max(0, (int) $start);
    $length = (int) $length;

    if ($length <= 0) {
        $length = 10;
    }

    return [$start, $length];
}

/**
 * URL base da aplicação (APP_URL no .env ou detecção automática via request).
 */
function app_base_url()
{
    static $cached = null;
    if ($cached !== null) {
        return $cached;
    }

    $from_env = rtrim($_ENV['APP_URL'] ?? '', '/');
    if ($from_env !== '') {
        $cached = $from_env;
        return $cached;
    }

    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $base = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? ''), '/\\');
    if (basename(str_replace('\\', '/', $base)) === 'ajax') {
        $base = dirname($base);
    }
    $path = ($base === '/' || $base === '\\' || $base === '.') ? '' : str_replace('\\', '/', $base);
    $cached = $scheme . '://' . $host . $path;

    return $cached;
}
