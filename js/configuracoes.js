/** configuracoes.js — integrações e limpeza de dados */
$(function () {
    $('.form-integracao').on('submit', function (e) {
        e.preventDefault();
        var $form = $(this);
        NpsCrud.salvarForm('ajax/integracoes_salvar.php', $form.serialize());
    });

    $('.btn-testar-integracao').on('click', function () {
        var codigo = $(this).data('codigo');
        var $btn = $(this);
        $btn.prop('disabled', true);

        $.post('ajax/integracoes_testar.php', { codigo: codigo }, function (res) {
            if (res.status === 'success') {
                Swal.fire('Conexão OK', res.message, 'success');
            } else {
                Swal.fire('Erro', res.message, 'error');
            }
        }, 'json').always(function () {
            $btn.prop('disabled', false);
        });
    });

    if (!$('#limpezaOpcoes').length) {
        return;
    }

    var $container = $('#limpezaOpcoes');
    var sugeridas = [];
    var absorvidasPorEventos = [];
    try {
        sugeridas = JSON.parse($container.attr('data-sugeridas') || '[]');
        absorvidasPorEventos = JSON.parse($container.attr('data-absorvidas') || '[]');
    } catch (e) {
        sugeridas = [];
        absorvidasPorEventos = [];
    }

    function aplicarRegrasExclusaoEventos() {
        var $eventos = $('input[name="limpeza_opcao[]"][value="eventos_inativos"]');
        if (!$eventos.is(':checked')) {
            return;
        }
        absorvidasPorEventos.forEach(function (chave) {
            $('input[name="limpeza_opcao[]"][value="' + chave + '"]').prop('checked', false);
        });
    }

    function opcoesSelecionadas() {
        return $('input[name="limpeza_opcao[]"]:checked').map(function () {
            return $(this).val();
        }).get();
    }

    function atualizarBotaoExecutar() {
        var selecionadas = opcoesSelecionadas();
        var $btn = $('#btnLimpezaExecutar');
        $btn.prop('disabled', selecionadas.length === 0);
        $btn.html(
            '<i class="bi bi-trash3"></i> Executar limpeza selecionada'
            + (selecionadas.length ? ' (' + selecionadas.length + ')' : '')
        );
    }

    function renderContagens(opcoes) {
        $.each(opcoes || {}, function (chave, item) {
            var $badge = $('[data-contagem="' + chave + '"]');
            var total = parseInt(item.total, 10) || 0;
            $badge.text(total + ' registro' + (total === 1 ? '' : 's'));
            $badge.toggleClass('limpeza-opcao-badge-zero', total === 0);
        });
    }

    function carregarResumoLimpeza() {
        $.getJSON('ajax/manutencao_limpar_dados.php', { acao: 'resumo' }, function (res) {
            if (res.status !== 'success' || !res.data || !res.data.opcoes) {
                Swal.fire('Aviso', 'Não foi possível carregar as contagens de limpeza.', 'warning');
                return;
            }
            renderContagens(res.data.opcoes);
        });
    }

    function montarHtmlConfirmacao(selecionadas, opcoes) {
        var html = '<p>As seguintes ações serão executadas:</p><ul class="limpeza-confirm-list">';
        selecionadas.forEach(function (chave) {
            var item = opcoes[chave] || {};
            var total = parseInt(item.total, 10) || 0;
            html += '<li><strong>' + (item.label || chave) + '</strong> — ' + total + ' registro(s)</li>';
        });
        html += '</ul><p class="text-danger mb-0">Esta ação é <strong>irreversível</strong>. Digite <strong>LIMPAR</strong> para confirmar.</p>';
        return html;
    }

    function executarLimpeza(selecionadas) {
        $.post('ajax/manutencao_limpar_dados.php', {
            acao: 'executar',
            confirmacao: 'LIMPAR',
            opcoes: selecionadas
        }, function (res) {
            if (res.status === 'success') {
                Swal.fire('Concluído', res.message, 'success');
                $('input[name="limpeza_opcao[]"]').prop('checked', false);
                atualizarBotaoExecutar();
                carregarResumoLimpeza();
            } else {
                Swal.fire('Erro', res.message, 'error');
            }
        }, 'json');
    }

    $('input[name="limpeza_opcao[]"]').on('change', function () {
        var $chk = $(this);
        if ($chk.is(':checked') && $chk.val() === 'tudo_operacional') {
            $('input[name="limpeza_opcao[]"]').not($chk).prop('checked', false);
        } else if ($chk.is(':checked')) {
            $('input[value="tudo_operacional"]').prop('checked', false);
        }
        if ($chk.is(':checked') && absorvidasPorEventos.indexOf($chk.val()) !== -1) {
            $('input[name="limpeza_opcao[]"][value="eventos_inativos"]').prop('checked', false);
        }
        aplicarRegrasExclusaoEventos();
        atualizarBotaoExecutar();
    });

    $('#btnLimpezaSugeridas').on('click', function () {
        $('input[name="limpeza_opcao[]"]').prop('checked', false);
        sugeridas.forEach(function (chave) {
            $('input[name="limpeza_opcao[]"][value="' + chave + '"]').prop('checked', true);
        });
        atualizarBotaoExecutar();
    });

    $('#btnLimpezaLimparSelecao').on('click', function () {
        $('input[name="limpeza_opcao[]"]').prop('checked', false);
        atualizarBotaoExecutar();
    });

    $('#btnLimpezaAtualizar').on('click', function () {
        carregarResumoLimpeza();
    });

    $('#btnLimpezaExecutar').on('click', function () {
        var selecionadas = opcoesSelecionadas();
        if (!selecionadas.length) {
            return;
        }

        $.getJSON('ajax/manutencao_limpar_dados.php', { acao: 'resumo' }, function (res) {
            if (res.status !== 'success' || !res.data || !res.data.opcoes) {
                Swal.fire('Erro', 'Não foi possível validar as opções selecionadas.', 'error');
                return;
            }

            var opcoes = res.data.opcoes;
            var totalGeral = 0;
            selecionadas.forEach(function (chave) {
                totalGeral += parseInt((opcoes[chave] || {}).total, 10) || 0;
            });

            if (totalGeral === 0) {
                Swal.fire('Nada a remover', 'Não há registros para as opções selecionadas.', 'info');
                return;
            }

            Swal.fire({
                title: 'Confirmar limpeza?',
                html: montarHtmlConfirmacao(selecionadas, opcoes),
                input: 'text',
                inputPlaceholder: 'LIMPAR',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc2626',
                confirmButtonText: 'Executar limpeza',
                cancelButtonText: 'Cancelar',
                preConfirm: function (valor) {
                    if ((valor || '').trim() !== 'LIMPAR') {
                        Swal.showValidationMessage('Digite LIMPAR para confirmar');
                    }
                }
            }).then(function (result) {
                if (result.isConfirmed) {
                    executarLimpeza(selecionadas);
                }
            });
        });
    });

    carregarResumoLimpeza();
    atualizarBotaoExecutar();
});
