/** dashboard-publico.js */
$(function () {
    var token = $('#publicToken').val();
    if (!token) return;

    var ultimaRequisicao = 0;
    var $form = $('#formChavePublica');
    var $btnSubmit = $form.find('button[type="submit"]');

    function renderMetaEvento(relatorio) {
        var $meta = $('#publicEventoMeta').empty();
        if (!$meta.length || !relatorio) return;

        var itens = [
            { icone: 'bi-calendar3', label: 'Período', valor: relatorio.periodo || '—' },
            { icone: 'bi-geo-alt', label: 'Local', valor: relatorio.local || '—' },
            { icone: 'bi-cloud-sun', label: 'Clima', valor: relatorio.clima || '—' }
        ];

        itens.forEach(function (item) {
            $meta.append(
                '<span class="dashboard-relatorio-meta-item">' +
                '<i class="bi ' + item.icone + '" aria-hidden="true"></i>' +
                '<span class="dashboard-relatorio-meta-label">' + item.label + '</span>' +
                '<span class="dashboard-relatorio-meta-value"></span>' +
                '</span>'
            );
            $meta.find('.dashboard-relatorio-meta-value').last().text(item.valor);
        });
    }

    function renderDashboard(data) {
        $('#publicAuth').addClass('hidden');
        $('#publicDashboard').removeClass('hidden');
        $('#bodyPanel').addClass('is-dashboard-view');
        var rel = data.relatorio || {};
        $('#publicTitulo').text(rel.nome || 'Relatório');
        renderMetaEvento(rel);

        var dash = data.dashboard || {};
        $('#publicStats').empty();
        var $grid = $('#publicGrid').empty();
        (dash.widgets || []).forEach(function (w) {
            NpsDashboard.renderWidget(w, $grid);
        });
    }

    function mostrarAuth(res) {
        $('#publicAuth').removeClass('hidden');
        $('#publicDashboard').addClass('hidden');
        $('#bodyPanel').removeClass('is-dashboard-view');
        if (res && res.data && res.data.chave_prefixo) {
            $('#chaveHint').text('A chave começa com: ' + res.data.chave_prefixo);
        }
    }

    function postPublico(dados) {
        return $.ajax({
            url: 'ajax/dashboard_publico_dados.php',
            method: 'POST',
            data: $.extend({ token: token }, dados),
            dataType: 'json'
        });
    }

    function tratarErroAjax(jqXHR, fallback) {
        var msg = fallback;
        if (jqXHR.responseJSON && jqXHR.responseJSON.message) {
            msg = jqXHR.responseJSON.message;
        }
        Swal.fire('Erro', msg, 'error');
    }

    function carregarDadosRelatorio() {
        var id = ++ultimaRequisicao;
        $btnSubmit.prop('disabled', true);

        postPublico({})
            .done(function (res) {
                if (id !== ultimaRequisicao) return;

                if (res.status === 'success' && res.data && res.data.dashboard) {
                    renderDashboard(res.data);
                    return;
                }

                if (res.status === 'auth_required') {
                    mostrarAuth(res);
                    $('#chave_publica').val('').focus();
                    return;
                }

                Swal.fire('Erro', res.message || 'Não foi possível carregar o relatório.', 'error');
            })
            .fail(function (jqXHR, textStatus) {
                if (id !== ultimaRequisicao || textStatus === 'abort') return;
                tratarErroAjax(jqXHR, 'Não foi possível carregar o relatório. Tente novamente.');
            })
            .always(function () {
                if (id === ultimaRequisicao) {
                    $btnSubmit.prop('disabled', false);
                }
            });
    }

    function autenticarChave(chave) {
        var id = ++ultimaRequisicao;
        $btnSubmit.prop('disabled', true);

        postPublico({ chave: chave, apenas_status: 1 })
            .done(function (res) {
                if (id !== ultimaRequisicao) return;

                if (res.status === 'success' && res.data && res.data.autenticado) {
                    carregarDadosRelatorio();
                    return;
                }

                if (res.status === 'auth_required') {
                    mostrarAuth(res);
                    $btnSubmit.prop('disabled', false);
                    return;
                }

                Swal.fire('Erro', res.message || 'Chave de acesso inválida.', 'error');
                $btnSubmit.prop('disabled', false);
            })
            .fail(function (jqXHR, textStatus) {
                if (id !== ultimaRequisicao || textStatus === 'abort') return;
                tratarErroAjax(jqXHR, 'Não foi possível validar a chave. Tente novamente.');
                $btnSubmit.prop('disabled', false);
            });
    }

    $form.on('submit', function (e) {
        e.preventDefault();

        var chave = $('#chave_publica').val().trim().toUpperCase();
        $('#chave_publica').val(chave);
        if (!chave) {
            Swal.fire('Atenção', 'Informe a chave de acesso.', 'warning');
            return;
        }

        autenticarChave(chave);
    });

    $('#chave_publica').focus();
});
