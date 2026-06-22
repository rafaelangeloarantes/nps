/** dashboard_relatorios.js */
$(function () {
    var isList = $('#tabelaDashboardRelatorios').length;
    var isForm = $('#formRelatorio').length;
    var isView = $('#relatorio_preview_id').length;
    var tabela;

    if (isList) {
        tabela = NpsDataTable.create('#tabelaDashboardRelatorios', {
            processing: true,
            serverSide: true,
            ajax: {
                url: 'ajax/dashboard_relatorios_listar.php',
                data: function (d) {
                    d.evento_id = $('#filtroEvento').val() || '';
                }
            },
            order: [[0, 'desc']],
            columns: [
                { data: 'id' },
                { data: 'nome' },
                { data: 'template_nome' },
                { data: 'evento_nome' },
                { data: 'chave_prefixo' },
                { data: 'ultimo_acesso' },
                {
                    data: 'acoes',
                    orderable: false,
                    render: function (id, type, row) {
                        var html = '<a href="index.php?p=dashboard_relatorio_view&id=' + id + '" class="btn-icon btn-icon-sm" aria-label="Visualizar"><i class="bi bi-eye"></i></a> ';
                        if (window.NpsAuth && window.NpsAuth.eh_master) {
                            html += '<a href="index.php?p=dashboard_relatorios_form&id=' + id + '" class="btn-icon btn-icon-sm btn-edit" aria-label="Editar"><i class="bi bi-pencil"></i></a> ';
                            html += '<button type="button" class="btn-icon btn-icon-sm btn-copy-link" data-url="' + $('<div>').text(row.url_publica).html() + '" aria-label="Copiar link"><i class="bi bi-link-45deg"></i></button> ';
                            html += '<button type="button" class="btn-icon btn-icon-sm btn-delete" data-id="' + id + '" aria-label="Excluir"><i class="bi bi-trash"></i></button>';
                        }
                        html += ' <a href="ajax/dashboard_relatorios_extrato.php?id=' + id + '" class="btn-icon btn-icon-sm" aria-label="Exportar XLSX" title="Extrato XLSX"><i class="bi bi-file-earmark-spreadsheet"></i></a>';
                        return html;
                    }
                }
            ]
        });

        $('#filtroEvento').on('change', function () {
            tabela.ajax.reload();
        });

        $('#tabelaDashboardRelatorios').on('click', '.btn-delete', function () {
            NpsCrud.excluir('ajax/dashboard_relatorios_excluir.php', $(this).data('id'), tabela);
        }).on('click', '.btn-copy-link', function () {
            var url = $(this).data('url');
            navigator.clipboard.writeText(url).then(function () {
                if (typeof showToast === 'function') showToast('success', 'Link copiado.');
            });
        });
    }

    if (isForm) {
        var relId = parseInt($('#relatorio_id').val(), 10);

        function mostrarAcesso(data) {
            $('#cardAcessoPublico, #cardPreview').removeClass('hidden');
            $('#url_publica').val(data.url_publica || '');
            $('#btnAbrirPublico').attr('href', data.url_publica || '#');
            $('#btnExportarExtrato').attr('href', 'ajax/dashboard_relatorios_extrato.php?id=' + (data.id || relId));

            var chave = data.chave_acesso || '';
            $('#chave_acesso').val(chave);

            if (chave) {
                $('#chaveHintAdmin').text('A chave permanece a mesma até você marcar "Regenerar chave de acesso" e salvar.');
            } else if (data.id || relId) {
                $('#chaveHintAdmin').text('Chave indisponível para exibição. Marque "Regenerar chave de acesso" e salve para gerar uma nova.');
            } else {
                $('#chaveHintAdmin').text('A chave será gerada ao salvar o relatório.');
            }

            if (data.id || relId) {
                NpsDashboard.carregarDashboard(data.id || relId, '#dashboardPreview');
            }
        }

        if (relId > 0) {
            $('#wrapRegenerarChave').show();
            $.getJSON('ajax/dashboard_relatorios_buscar.php', { id: relId }, function (res) {
                if (res.status !== 'success' || !res.data) return;
                var d = res.data;
                $('#nome').val(d.nome);
                $('#template_id').val(d.template_id).trigger('change');
                $('#evento_id').val(d.evento_id).trigger('change');
                mostrarAcesso(d);
            });
        }

        $('#formRelatorio').on('submit', function (e) {
            e.preventDefault();
            $.post('ajax/dashboard_relatorios_salvar.php', $(this).serialize(), function (res) {
                if (res.status === 'success') {
                    if (typeof showToast === 'function') showToast('success', res.message);
                    var payload = res.data || {};
                    if (!relId && payload.id) {
                        $('#relatorio_id').val(payload.id);
                        relId = payload.id;
                        $('#wrapRegenerarChave').show();
                    }
                    $('#regenerar_chave').prop('checked', false);
                    mostrarAcesso(payload);
                } else if (typeof showAlert === 'function') {
                    showAlert('danger', res.message, 'alertContainer');
                }
            }, 'json');
        });

        $('#btnCopiarLink').on('click', function () {
            var url = $('#url_publica').val();
            navigator.clipboard.writeText(url).then(function () {
                if (typeof showToast === 'function') showToast('success', 'Link copiado.');
            });
        });

        $('#btnCopiarChave').on('click', function () {
            var chave = $('#chave_acesso').val().trim();
            if (!chave) {
                if (typeof showToast === 'function') showToast('warning', 'Nenhuma chave disponível para copiar.');
                return;
            }
            navigator.clipboard.writeText(chave).then(function () {
                if (typeof showToast === 'function') showToast('success', 'Chave copiada.');
            });
        });
    }

    if (isView) {
        var viewId = parseInt($('#relatorio_preview_id').val(), 10);
        NpsDashboard.carregarDashboard(viewId, '#dashboardPreview');
    }
});
