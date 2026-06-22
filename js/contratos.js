/** contratos.js */
$(function () {
    var isList = $('#tabelaContratos').length;
    var isForm = $('#formContrato').length;
    var tabela;

    if (isList) {
        tabela = NpsDataTable.create('#tabelaContratos', {
            processing: true,
            serverSide: true,
            ajax: 'ajax/contratos_listar.php',
            order: [[0, 'desc']],
            columns: [
                { data: 'id' },
                { data: 'nome' },
                { data: 'status', orderable: false },
                { data: 'criado_em' },
                {
                    data: 'acoes',
                    orderable: false,
                    render: function (id) {
                        return NpsCrud.btnAcoes(id, { editUrl: 'index.php?p=contratos_form&id=' + id });
                    }
                }
            ]
        });

        $('#tabelaContratos').on('click', '.btn-delete', function () {
            NpsCrud.excluir('ajax/contratos_excluir.php', $(this).data('id'), tabela);
        });
    }

    if (isForm) {
        var id = parseInt($('#contrato_id').val(), 10);
        if (id > 0) {
            NpsCrud.carregarRegistro('ajax/contratos_buscar.php', id, function (d) {
                $('#nome').val(d.nome);
                $('#ativo').val(d.ativo);
            });
        }

        $('#formContrato').on('submit', function (e) {
            e.preventDefault();
            NpsCrud.salvarForm('ajax/contratos_salvar.php', $(this).serialize(), 'index.php?p=contratos');
        });
    }
});
