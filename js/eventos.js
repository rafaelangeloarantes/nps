/** eventos.js */
$(function () {
    var isList = $('#tabelaEventos').length;
    var isForm = $('#formEvento').length;
    var tabela;

    if (isList) {
        tabela = NpsDataTable.create('#tabelaEventos', {
            processing: true,
            serverSide: true,
            ajax: {
                url: 'ajax/eventos_listar.php',
                data: function (d) {
                    d.contrato_id = $('#filtroContrato').val();
                }
            },
            order: [[0, 'desc']],
            columns: [
                { data: 'id_integracao' },
                { data: 'nome' },
                { data: 'convidados', className: 'text-center' },
                { data: 'confirmados', className: 'text-center' },
                { data: 'show', className: 'text-center' },
                { data: 'noshow', className: 'text-center' },
                { data: 'ultima_sync' },
                {
                    data: 'acoes',
                    orderable: false,
                    render: function (id) {
                        var html = '<a href="index.php?p=participantes&evento_id=' + id + '" class="btn-icon btn-icon-sm" title="Participantes"><i class="bi bi-people"></i></a> ';
                        html += '<a href="index.php?p=pesquisas&evento_id=' + id + '" class="btn-icon btn-icon-sm" title="Pesquisas"><i class="bi bi-clipboard-data"></i></a> ';
                        html += '<a href="index.php?p=eventos_mapeamento&id=' + id + '" class="btn-icon btn-icon-sm" title="Mapear atributos"><i class="bi bi-diagram-3"></i></a> ';
                        html += NpsCrud.btnAcoes(id, {
                            editUrl: 'index.php?p=eventos_form&id=' + id,
                            sync: true
                        });
                        return html;
                    }
                }
            ]
        });

        $('#filtroContrato').on('change', function () {
            tabela.ajax.reload();
        });

        $('#tabelaEventos').on('click', '.btn-delete', function () {
            NpsCrud.excluir('ajax/eventos_excluir.php', $(this).data('id'), tabela);
        });

        $('#tabelaEventos').on('click', '.btn-sync', function () {
            var id = $(this).data('id');
            Swal.fire({
                title: 'Sincronizar participantes?',
                text: 'Importa guests da API (participantes + credenciamento SHOW/NOSHOW).',
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Sincronizar',
                cancelButtonText: 'Cancelar'
            }).then(function (result) {
                if (!result.isConfirmed) return;
                $.post('ajax/participantes_sincronizar_evento.php', { evento_id: id }, function (res) {
                    if (res.status === 'success') {
                        Swal.fire('Concluído', res.message, 'success');
                        tabela.ajax.reload(null, false);
                    } else {
                        Swal.fire('Erro', res.message, 'error');
                    }
                }, 'json');
            });
        });
    }

    if (isForm) {
        var id = parseInt($('#evento_id').val(), 10);
        if (id > 0) {
            NpsCrud.carregarRegistro('ajax/eventos_buscar.php', id, function (d) {
                $('#contrato_id').val(d.contrato_id).trigger('change');
                $('#nome').val(d.nome);
                $('#link').val(d.link);
                $('#id_integracao').val(d.id_integracao);
                $('#endereco').val(d.endereco);
                $('#clima').val(d.clima);
                $('#ativo').val(d.ativo);
                if (d.data_inicio) $('#data_inicio').val(d.data_inicio.replace(' ', 'T').substring(0, 16));
                if (d.data_fim) $('#data_fim').val(d.data_fim.replace(' ', 'T').substring(0, 16));
            });
        }

        $('#formEvento').on('submit', function (e) {
            e.preventDefault();
            NpsCrud.salvarForm('ajax/eventos_salvar.php', $(this).serialize(), 'index.php?p=eventos');
        });

        $('#btnLimparDadosEvento').on('click', function () {
            NpsCrud.limparDadosEvento($(this).data('evento-id'));
        });
    }
});
