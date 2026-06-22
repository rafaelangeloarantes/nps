/**
 * Utilitários CRUD compartilhados
 */
window.NpsCrud = {
    /**
     * Texto truncado para células de DataTable (com title no hover).
     */
    truncateCell: function (texto, maxLen, extraClass) {
        maxLen = maxLen || 40;
        var full = texto == null ? '' : String(texto);
        if (full === '' || full === '—') {
            return '<span class="dt-text-truncate' + (extraClass ? ' ' + extraClass : '') + '">—</span>';
        }
        var curto = full.length > maxLen ? full.substring(0, maxLen - 1).trim() + '…' : full;
        var cls = 'dt-text-truncate' + (extraClass ? ' ' + extraClass : '');
        var title = full.length > maxLen ? ' title="' + $('<div>').text(full).html() + '"' : '';
        return '<span class="' + cls + '"' + title + '>' + $('<div>').text(curto).html() + '</span>';
    },

    btnAcoes: function (id, opts) {
        opts = opts || {};
        var html = '<a href="' + (opts.editUrl || '#') + '" class="btn-icon btn-icon-sm btn-edit" data-id="' + id + '" aria-label="Editar"><i class="bi bi-pencil"></i></a> ';
        if (opts.sync) {
            html += '<button type="button" class="btn-icon btn-icon-sm btn-sync" data-id="' + id + '" aria-label="Sincronizar"><i class="bi bi-arrow-repeat"></i></button> ';
        }
        if (opts.deleteDisabled) {
            html += '<button type="button" class="btn-icon btn-icon-sm" disabled title="' + $('<div>').text(opts.deleteDisabledTitle || 'Não é possível excluir este registro.').html() + '" aria-label="Excluir indisponível"><i class="bi bi-trash"></i></button>';
        } else {
            html += '<button type="button" class="btn-icon btn-icon-sm btn-delete" data-id="' + id + '" aria-label="Excluir"><i class="bi bi-trash"></i></button>';
        }
        return html;
    },

    recarregarTabela: function (tabela) {
        if (!tabela) {
            return;
        }
        if (tabela.ajax && typeof tabela.ajax.reload === 'function') {
            tabela.ajax.reload(null, false);
        }
    },

    excluir: function (url, id, tabela, msg) {
        Swal.fire({
            title: 'Tem certeza?',
            text: msg || 'Esta ação não poderá ser desfeita.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc2626',
            cancelButtonColor: '#64748b',
            confirmButtonText: 'Sim, excluir',
            cancelButtonText: 'Cancelar'
        }).then(function (result) {
            if (!result.isConfirmed) return;
            $.post(url, { id: id }, function (res) {
                if (res.status === 'success') {
                    if (typeof showToast === 'function') showToast('success', res.message);
                    NpsCrud.recarregarTabela(tabela);
                } else {
                    Swal.fire('Erro', res.message || 'Não foi possível excluir.', 'error');
                }
            }, 'json').fail(function (jqXHR) {
                var mensagem = 'Falha ao excluir o registro.';
                if (jqXHR.responseJSON && jqXHR.responseJSON.message) {
                    mensagem = jqXHR.responseJSON.message;
                }
                Swal.fire('Erro', mensagem, 'error');
            });
        });
    },

    salvarForm: function (url, data, redirectUrl) {
        return $.post(url, data, function (res) {
            if (res.status === 'success') {
                if (typeof showToast === 'function') showToast('success', res.message);
                if (redirectUrl) window.location.href = redirectUrl;
            } else if (typeof showAlert === 'function') {
                showAlert('danger', res.message, 'alertContainer');
            }
        }, 'json');
    },

    carregarRegistro: function (url, id, callback) {
        $.getJSON(url, { id: id }, function (res) {
            if (res.status === 'success' && res.data) callback(res.data);
        });
    },

    /**
     * Remove participantes, credenciamentos e pesquisas vinculados a um evento.
     */
    limparDadosEvento: function (eventoId, onSuccess) {
        eventoId = parseInt(eventoId, 10);
        if (!eventoId) return;

        $.getJSON('ajax/eventos_dados_resumo.php', { evento_id: eventoId }, function (res) {
            if (res.status !== 'success' || !res.data) {
                Swal.fire('Erro', res.message || 'Não foi possível carregar o resumo.', 'error');
                return;
            }

            var d = res.data;
            if (d.total_registros === 0) {
                Swal.fire('Nada a remover', 'Este evento não possui dados de participantes, credenciamento ou pesquisas.', 'info');
                return;
            }

            var html = '<p><strong>' + $('<div>').text(d.evento_nome).html() + '</strong></p>';
            html += '<ul style="text-align:left;margin:1rem 0;padding-left:1.25rem">';
            html += '<li><strong>' + d.participantes_vinculados + '</strong> vínculo(s) de participante</li>';
            html += '<li><strong>' + d.credenciamentos + '</strong> credenciamento(s)</li>';
            html += '<li><strong>' + d.pesquisas + '</strong> pesquisa(s)</li>';
            if (d.dados_extras > 0) {
                html += '<li><strong>' + d.dados_extras + '</strong> registro(s) de dados extras (API)</li>';
            }
            html += '</ul>';

            if (d.participantes_orfaos > 0) {
                html += '<p class="text-muted" style="font-size:.875rem">'
                    + d.participantes_orfaos + ' participante(s) existem apenas neste evento e serão inativados. '
                    + (d.participantes_mantidos > 0
                        ? d.participantes_mantidos + ' permanecem no cadastro (outros eventos).'
                        : '')
                    + '</p>';
            } else if (d.participantes_mantidos > 0) {
                html += '<p class="text-muted" style="font-size:.875rem">'
                    + 'Os participantes globais serão mantidos; apenas o vínculo com este evento será removido.</p>';
            }

            html += '<p style="color:#dc2626;font-size:.875rem"><strong>Esta ação não pode ser desfeita.</strong></p>';

            Swal.fire({
                title: 'Limpar dados do evento?',
                html: html,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc2626',
                cancelButtonColor: '#64748b',
                confirmButtonText: 'Sim, limpar tudo',
                cancelButtonText: 'Cancelar',
                focusCancel: true
            }).then(function (result) {
                if (!result.isConfirmed) return;

                $.post('ajax/eventos_limpar_dados.php', {
                    evento_id: eventoId,
                    remover_orfaos: 1
                }, function (postRes) {
                    if (postRes.status === 'success') {
                        Swal.fire('Concluído', postRes.message, 'success');
                        if (typeof onSuccess === 'function') onSuccess(postRes);
                    } else {
                        Swal.fire('Erro', postRes.message, 'error');
                    }
                }, 'json').fail(function () {
                    Swal.fire('Erro', 'Falha na comunicação com o servidor.', 'error');
                });
            });
        }).fail(function () {
            Swal.fire('Erro', 'Não foi possível carregar o resumo do evento.', 'error');
        });
    }
};
