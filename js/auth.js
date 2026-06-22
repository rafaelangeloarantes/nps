/**
 * auth.js — Login administrativo
 */
$(document).ready(function () {
    $('#formLogin').on('submit', function (e) {
        e.preventDefault();
        var email = $('#email').val().trim();
        var senha = $('#senha').val();

        if (!email || !senha) {
            if (typeof showAlert === 'function') {
                showAlert('danger', 'Preencha e-mail e senha.', 'alertLogin');
            }
            return;
        }

        $.post('ajax/auth_login.php', { email: email, senha: senha }, function (res) {
            if (res.status === 'success') {
                window.location.href = 'index.php';
            } else if (typeof showAlert === 'function') {
                showAlert('danger', res.message, 'alertLogin');
            }
        }, 'json').fail(function () {
            if (typeof showAlert === 'function') {
                showAlert('danger', 'Erro ao comunicar com o servidor.', 'alertLogin');
            }
        });
    });
});
