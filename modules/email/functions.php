<?php
/**
 * Envio de e-mails via API SendGrid
 */

function sendgrid_configurado()
{
    return !empty(trim($_ENV['SENDGRID_API_KEY'] ?? ''));
}

/**
 * Envia e-mail HTML via SendGrid v3.
 */
function enviar_email_sendgrid($para, $assunto, $html, $texto = '')
{
    $api_key = trim($_ENV['SENDGRID_API_KEY'] ?? '');
    if ($api_key === '') {
        error_log('SendGrid: SENDGRID_API_KEY não configurada no .env');
        return false;
    }

    $de_email = trim($_ENV['SENDGRID_FROM_EMAIL'] ?? 'noreply@nps.local');
    $de_nome = trim($_ENV['SENDGRID_FROM_NAME'] ?? ($_ENV['APP_NAME'] ?? 'NPS Relatórios'));

    if ($texto === '') {
        $texto = strip_tags(str_replace(['<br>', '<br/>', '<br />'], "\n", $html));
    }

    $payload = [
        'personalizations' => [
            ['to' => [['email' => $para]]],
        ],
        'from' => ['email' => $de_email, 'name' => $de_nome],
        'subject' => $assunto,
        'content' => [
            ['type' => 'text/plain', 'value' => $texto],
            ['type' => 'text/html', 'value' => $html],
        ],
    ];

    $ch = curl_init('https://api.sendgrid.com/v3/mail/send');
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            'Authorization: Bearer ' . $api_key,
            'Content-Type: application/json',
        ],
        CURLOPT_POSTFIELDS => json_encode($payload),
        CURLOPT_TIMEOUT => 30,
    ]);

    $response = curl_exec($ch);
    $http_code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_erro = curl_error($ch);
    curl_close($ch);

    if ($curl_erro !== '') {
        error_log('SendGrid cURL: ' . $curl_erro);
        return false;
    }

    if ($http_code < 200 || $http_code >= 300) {
        error_log('SendGrid HTTP ' . $http_code . ': ' . $response);
        return false;
    }

    return true;
}

/**
 * Envia link de redefinição de senha.
 */
function enviar_email_recuperacao_senha($conn, $usuario, $token, $base_url)
{
    $link = rtrim($base_url, '/') . '/redefinir_senha.php?token=' . urlencode($token);
    $nome = h($usuario['nome']);
    $app = h($_ENV['APP_NAME'] ?? 'NPS Relatórios');

    $html = '<div style="font-family:Inter,Arial,sans-serif;max-width:520px;margin:0 auto;padding:24px">'
        . '<h2 style="color:#4F46E5;margin:0 0 16px">' . $app . '</h2>'
        . '<p>Olá, <strong>' . $nome . '</strong>,</p>'
        . '<p>Recebemos uma solicitação para redefinir sua senha. Clique no botão abaixo (válido por 1 hora):</p>'
        . '<p style="margin:24px 0"><a href="' . h($link) . '" '
        . 'style="background:#4F46E5;color:#fff;padding:12px 24px;border-radius:8px;text-decoration:none;display:inline-block">'
        . 'Redefinir senha</a></p>'
        . '<p style="font-size:13px;color:#64748b">Se você não solicitou, ignore este e-mail.</p>'
        . '<p style="font-size:12px;color:#94a3b8;word-break:break-all">' . h($link) . '</p>'
        . '</div>';

    $texto = "Olá, {$usuario['nome']}.\n\nRedefina sua senha em: {$link}\n\nVálido por 1 hora.";

    return enviar_email_sendgrid($usuario['email'], 'Recuperação de senha — ' . ($_ENV['APP_NAME'] ?? 'NPS'), $html, $texto);
}
