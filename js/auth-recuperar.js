/**
 * auth-recuperar.js — Esqueci senha e redefinição
 */
$(document).ready(function () {
    $('#formEsqueciSenha').on('submit', function (e) {
        e.preventDefault();
        var email = $('#email_recuperar').val().trim();
        if (!email) {
            showAlert('danger', 'Informe seu e-mail.', 'alertRecuperar');
            return;
        }
        $.post('ajax/auth_esqueci_senha.php', { email: email }, function (res) {
            var tipo = res.status === 'success' ? 'success' : 'danger';
            showAlert(tipo, res.message, 'alertRecuperar');
            if (res.status === 'success') {
                $('#formEsqueciSenha')[0].reset();
            }
        }, 'json').fail(function () {
            showAlert('danger', 'Erro ao comunicar com o servidor.', 'alertRecuperar');
        });
    });

    $('#formRedefinirSenha').on('submit', function (e) {
        e.preventDefault();
        var token = $('#token').val();
        var senha = $('#senha').val();
        var confirmacao = $('#senha_confirmacao').val();

        if (!senha || !confirmacao) {
            showAlert('danger', 'Preencha todos os campos.', 'alertRedefinir');
            return;
        }

        $.post('ajax/auth_redefinir_senha.php', {
            token: token,
            senha: senha,
            senha_confirmacao: confirmacao
        }, function (res) {
            if (res.status === 'success') {
                showAlert('success', res.message, 'alertRedefinir');
                setTimeout(function () {
                    window.location.href = 'login.php';
                }, 2000);
            } else {
                showAlert('danger', res.message, 'alertRedefinir');
            }
        }, 'json').fail(function () {
            showAlert('danger', 'Erro ao comunicar com o servidor.', 'alertRedefinir');
        });
    });
});
