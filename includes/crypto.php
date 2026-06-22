<?php
/**
 * Criptografia para credenciais de acesso externo (contratos/eventos)
 */

function crypto_key()
{
    $key = $_ENV['APP_KEY'] ?? $_ENV['INSTALL_TOKEN'] ?? 'nps_default_key_change_me';
    return hash('sha256', $key, true);
}

function criptografar_texto($texto)
{
    if ($texto === '' || $texto === null) {
        return '';
    }
    $iv = random_bytes(16);
    $encrypted = openssl_encrypt($texto, 'AES-256-CBC', crypto_key(), OPENSSL_RAW_DATA, $iv);
    return base64_encode($iv . $encrypted);
}

function descriptografar_texto($texto_criptografado)
{
    if ($texto_criptografado === '' || $texto_criptografado === null) {
        return '';
    }
    $raw = base64_decode($texto_criptografado, true);
    if ($raw === false || strlen($raw) < 17) {
        return '';
    }
    $iv = substr($raw, 0, 16);
    $encrypted = substr($raw, 16);
    $decrypted = openssl_decrypt($encrypted, 'AES-256-CBC', crypto_key(), OPENSSL_RAW_DATA, $iv);
    return $decrypted !== false ? $decrypted : '';
}
