/** sistema_logs.js — Consulta de logs do sistema */

$(function () {
    if (!$('#tabelaLogs').length) {
        return;
    }

    var tabela;

    function filtrosAtuais() {
        return {
            tipo: $('#filtro_tipo').val() || '',
            nivel: $('#filtro_nivel').val() || '',
            modulo: $('#filtro_modulo').val() || '',
            usuario_id: $('#filtro_usuario').val() || '',
            data_inicio: $('#filtro_data_inicio').val() || '',
            data_fim: $('#filtro_data_fim').val() || ''
        };
    }

    tabela = NpsDataTable.create('#tabelaLogs', {
        processing: true,
        serverSide: true,
        ajax: {
            url: 'ajax/sistema_logs_listar.php',
            data: function (d) {
                return $.extend({}, d, filtrosAtuais());
            }
        },
        order: [[0, 'desc']],
        columns: [
            { data: 'criado_em' },
            { data: 'tipo', orderable: false },
            { data: 'nivel', orderable: false },
            { data: 'modulo' },
            { data: 'acao' },
            { data: 'usuario' },
            { data: 'mensagem', orderable: false },
            {
                data: 'detalhes',
                orderable: false,
                render: function (chave) {
                    return '<button type="button" class="btn-icon btn-icon-sm btn-log-detalhe" data-chave="' +
                        $('<div>').text(chave).html() + '" aria-label="Ver detalhes">' +
                        '<i class="bi bi-eye"></i></button>';
                }
            }
        ]
    });

    $('#formFiltrosLog').on('submit', function (e) {
        e.preventDefault();
        tabela.ajax.reload();
    });

    $('#btnLimparFiltrosLog').on('click', function () {
        $('#formFiltrosLog')[0].reset();
        tabela.ajax.reload();
    });

    $('#tabelaLogs').on('click', '.btn-log-detalhe', function () {
        var chave = $(this).data('chave');
        abrirDetalheLog(chave);
    });

    function abrirDetalheLog(chave) {
        $('#modalLogDetalheBody').html('<p class="text-muted">Carregando...</p>');
        if (typeof TemplateModal !== 'undefined') {
            TemplateModal.openById('modalLogDetalhe');
        } else {
            $('#modalLogDetalhe').addClass('is-open');
        }

        $.getJSON('ajax/sistema_logs_detalhe.php', { chave: chave }, function (res) {
            if (res.status !== 'success' || !res.data) {
                $('#modalLogDetalheBody').html('<p class="text-danger">' + (res.message || 'Erro ao carregar.') + '</p>');
                return;
            }

            var d = res.data;
            var html = '<dl class="log-detalhe-list">';
            html += itemDetalhe('Data/Hora', d.criado_em);
            html += itemDetalhe('Tipo', d.tipo);
            html += itemDetalhe('Nível', d.nivel);
            html += itemDetalhe('Módulo', d.modulo);
            html += itemDetalhe('Ação', d.acao);
            if (d.entidade_id) {
                html += itemDetalhe('ID entidade', d.entidade_id);
            }
            if (d.usuario_id) {
                html += itemDetalhe('ID usuário', d.usuario_id);
            }
            if (d.ip) {
                html += itemDetalhe('IP', d.ip);
            }
            if (d.mensagem) {
                html += itemDetalhe('Mensagem', d.mensagem);
            }
            html += '</dl>';

            if (d.detalhes && Object.keys(d.detalhes).length) {
                html += '<h3 class="log-detalhe-subtitulo">Detalhes adicionais</h3>';
                html += '<pre class="log-detalhe-json">' + $('<div>').text(JSON.stringify(d.detalhes, null, 2)).html() + '</pre>';
            }

            if (d.user_agent) {
                html += '<p class="log-detalhe-ua"><small>User-Agent: ' + $('<div>').text(d.user_agent).html() + '</small></p>';
            }

            $('#modalLogDetalheBody').html(html);
        }).fail(function () {
            $('#modalLogDetalheBody').html('<p class="text-danger">Falha ao carregar detalhes.</p>');
        });
    }

    function itemDetalhe(label, valor) {
        return '<dt>' + $('<div>').text(label).html() + '</dt>' +
            '<dd>' + $('<div>').text(valor || '—').html() + '</dd>';
    }
});
