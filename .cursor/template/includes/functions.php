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
