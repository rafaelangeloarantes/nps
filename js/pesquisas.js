/** pesquisas.js */
$(function () {
    var isList = $('#tabelaPesquisas').length;
    var isForm = $('#formPesquisa').length;
    var tabela;

    if (isList) {
        tabela = NpsDataTable.create('#tabelaPesquisas', {
            processing: true,
            serverSide: true,
            ajax: {
                url: 'ajax/pesquisas_listar.php',
                data: function (d) {
                    d.evento_id = $('#filtroEvento').val();
                }
            },
            order: [[0, 'desc']],
            columns: [
                { data: 'id' },
                { data: 'nome' },
                { data: 'evento_nome' },
                { data: 'identificador_integracao' },
                { data: 'status', orderable: false },
                { data: 'total_respostas', className: 'text-center' },
                { data: 'ultima_sync' },
                {
                    data: 'acoes',
                    orderable: false,
                    render: function (id) {
                        var html = '<a href="index.php?p=pesquisas_mapeamento&id=' + id + '" class="btn-icon btn-icon-sm" title="Mapear campos"><i class="bi bi-diagram-3"></i></a> ';
                        html += NpsCrud.btnAcoes(id, {
                            editUrl: 'index.php?p=pesquisas_form&id=' + id,
                            sync: true
                        });
                        return html;
                    }
                }
            ]
        });

        $('#filtroEvento').on('change', function () {
            tabela.ajax.reload();
        });

        if ($('#filtroEvento').val()) {
            tabela.ajax.reload();
        }

        $('#tabelaPesquisas').on('click', '.btn-delete', function () {
            NpsCrud.excluir('ajax/pesquisas_excluir.php', $(this).data('id'), tabela);
        });

        $('#tabelaPesquisas').on('click', '.btn-sync', function () {
            var id = $(this).data('id');
            Swal.fire({
                title: 'Sincronizar respostas?',
                text: 'Importa as respostas da API conforme o mapeamento salvo.',
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Sincronizar',
                cancelButtonText: 'Cancelar'
            }).then(function (result) {
                if (!result.isConfirmed) return;
                $.post('ajax/pesquisas_sincronizar.php', { id: id }, function (res) {
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
        var id = parseInt($('#pesquisa_id').val(), 10);

        if (id > 0) {
            NpsCrud.carregarRegistro('ajax/pesquisas_buscar.php', id, function (d) {
                $('#evento_id').val(d.evento_id).trigger('change');
                $('#nome').val(d.nome);
                $('#identificador_integracao').val(d.identificador_integracao);
                $('#ativo').val(d.ativo);
            });
        }

        $('#formPesquisa').on('submit', function (e) {
            e.preventDefault();
            $.post('ajax/pesquisas_salvar.php', $(this).serialize(), function (res) {
                if (res.status === 'success') {
                    if (typeof showToast === 'function') showToast('success', res.message);
                    var destinoId = (res.data && res.data.id) ? res.data.id : id;
                    window.location.href = 'index.php?p=pesquisas_mapeamento&id=' + destinoId;
                } else if (typeof showAlert === 'function') {
                    showAlert('danger', res.message, 'alertContainer');
                }
            }, 'json');
        });
    }
});
