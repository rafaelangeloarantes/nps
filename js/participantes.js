/** participantes.js */
$(function () {
    var isList = $('#tabelaParticipantes').length;
    var isForm = $('#formParticipante').length;
    var tabela;

    function renderPesquisasRespostasHtml(lista) {
        if (window.NpsPesquisasRespostas) {
            return NpsPesquisasRespostas.render(lista);
        }
        return '<p class="text-muted mb-0">Nenhuma resposta de pesquisa vinculada.</p>';
    }

    function renderPesquisasRespostasEl(lista) {
        return $('<div>').html(renderPesquisasRespostasHtml(lista));
    }

    function abrirModalPesquisas(participanteId, nomeParticipante) {
        var eventoId = $('#filtroEvento').val() || '';
        $.getJSON('ajax/participantes_pesquisas_respostas.php', {
            participante_id: participanteId,
            evento_id: eventoId
        }, function (res) {
            if (res.status !== 'success') {
                Swal.fire('Erro', res.message, 'error');
                return;
            }

            var titulo = nomeParticipante ? 'Pesquisas — ' + nomeParticipante : 'Respostas de pesquisas';
            var conteudo = renderPesquisasRespostasEl(res.data.respostas || []);

            if (window.TemplateModal) {
                TemplateModal.open({
                    title: titulo,
                    body: conteudo,
                    size: 'lg'
                });
            } else {
                Swal.fire({
                    title: titulo,
                    html: conteudo.html(),
                    width: 720
                });
            }
        }).fail(function () {
            Swal.fire('Erro', 'Não foi possível carregar as pesquisas.', 'error');
        });
    }

    if (isList) {
        tabela = NpsDataTable.create('#tabelaParticipantes', {
            processing: true,
            serverSide: true,
            ajax: {
                url: 'ajax/participantes_listar.php',
                data: function (d) {
                    d.evento_id = $('#filtroEvento').val();
                    d.com_pesquisa = $('#filtroComPesquisa').is(':checked') ? 1 : 0;
                }
            },
            order: [[0, 'desc']],
            columnDefs: [
                { targets: 1, className: 'dt-col-text' },
                { targets: 2, className: 'dt-col-email' },
                { targets: 3, className: 'dt-col-text' },
                { targets: 6, className: 'dt-col-acoes dt-no-sort' }
            ],
            columns: [
                { data: 'id' },
                {
                    data: 'nome_completo',
                    render: function (data, type) {
                        if (type !== 'display') return data;
                        return NpsCrud.truncateCell(data, 48);
                    }
                },
                {
                    data: 'email',
                    render: function (data, type) {
                        if (type !== 'display') return data;
                        return NpsCrud.truncateCell(data, 42, 'dt-text-truncate--email');
                    }
                },
                {
                    data: 'empresa',
                    render: function (data, type) {
                        if (type !== 'display') return data;
                        return NpsCrud.truncateCell(data, 40);
                    }
                },
                { data: 'integridade', orderable: false },
                { data: 'credenciamento', orderable: false },
                {
                    data: null,
                    orderable: false,
                    className: 'dt-col-acoes',
                    render: function (data, type, row) {
                        if (type !== 'display') return '';
                        var html = '<div class="dt-cell-actions">';
                        if ((row.total_pesquisas || 0) > 0) {
                            html += '<button type="button" class="btn-icon btn-icon-sm btn-pesquisas" data-id="' + row.id +
                                '" title="Ver pesquisas"><i class="bi bi-clipboard-check"></i></button>';
                        }
                        html += NpsCrud.btnAcoes(row.id, {
                            editUrl: 'index.php?p=participantes_form&id=' + row.id
                        });
                        html += '</div>';
                        return html;
                    }
                }
            ]
        });

        $('#filtroEvento').on('change', function () {
            tabela.ajax.reload();
            var evId = $(this).val();
            $('#btnSyncEventoParticipantes').prop('disabled', !evId);
            if (evId) {
                $('#btnMapearEvento').attr('href', 'index.php?p=eventos_mapeamento&id=' + evId).show();
            } else {
                $('#btnMapearEvento').hide();
            }
        });

        $('#filtroComPesquisa').on('change', function () {
            tabela.ajax.reload();
        });

        if ($('#filtroEvento').val()) {
            $('#filtroEvento').trigger('change');
        }

        $('#btnSyncEventoParticipantes').on('click', function () {
            var eventoId = $('#filtroEvento').val();
            if (!eventoId) return;
            Swal.fire({
                title: 'Sincronizar participantes?',
                text: 'Importa guests da API (participantes + credenciamento SHOW/NOSHOW).',
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Sincronizar',
                cancelButtonText: 'Cancelar'
            }).then(function (result) {
                if (!result.isConfirmed) return;
                $.post('ajax/participantes_sincronizar_evento.php', { evento_id: eventoId }, function (res) {
                    if (res.status === 'success') {
                        Swal.fire('Concluído', res.message, 'success');
                        tabela.ajax.reload();
                    } else {
                        Swal.fire('Erro', res.message, 'error');
                    }
                }, 'json');
            });
        });

        $('#tabelaParticipantes').on('click', '.btn-pesquisas', function () {
            var $tr = $(this).closest('tr');
            var row = tabela.row($tr).data() || {};
            abrirModalPesquisas($(this).data('id'), row.nome_completo || '');
        });

        $('#tabelaParticipantes').on('click', '.btn-delete', function () {
            NpsCrud.excluir('ajax/participantes_excluir.php', $(this).data('id'), tabela);
        });
    }

    if (isForm) {
        var id = parseInt($('#participante_id').val(), 10);

        function atualizarSecaoPesquisasRespostas(lista) {
            var $box = $('#listaPesquisasRespostas');
            if (!$box.length) return;
            $box.html(renderPesquisasRespostasHtml(lista));
        }

        if (id > 0) {
            NpsCrud.carregarRegistro('ajax/participantes_buscar.php', id, function (d) {
                $('#nome_completo').val(d.nome_completo);
                $('#email').val(d.email);
                $('#telefone').val(d.telefone);
                $('#cargo').val(d.cargo);
                $('#empresa').val(d.empresa);
                $('#estado').val(d.estado);
                $('#cidade').val(d.cidade);
                $('#data_nascimento').val(d.data_nascimento);
                $('#linkedin').val(d.linkedin);
                if (d.eventos_ids) $('#eventos_ids').val(d.eventos_ids).trigger('change');
                atualizarSecaoPesquisasRespostas(d.pesquisas_respostas || []);
            });
        }

        $('#formParticipante').on('submit', function (e) {
            e.preventDefault();
            var data = $(this).serializeArray();
            var eventos = $('#eventos_ids').val() || [];
            data.push({ name: 'eventos_ids', value: JSON.stringify(eventos) });
            NpsCrud.salvarForm('ajax/participantes_salvar.php', $.param(data), 'index.php?p=participantes');
        });
    }
});
