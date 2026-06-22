/** campos_padrao.js */
$(function () {
    var isList = $('#tabelaCamposPadrao').length;
    var isForm = $('#formCampoPadrao').length;
    var tabela;

    if (isList) {
        tabela = NpsDataTable.create('#tabelaCamposPadrao', {
            processing: true,
            serverSide: true,
            ajax: {
                url: 'ajax/campos_padrao_listar.php',
                data: function (d) {
                    d.categoria = $('#filtroCategoria').val() || '';
                }
            },
            order: [[7, 'asc']],
            columns: [
                { data: 'id' },
                { data: 'nome' },
                { data: 'slug' },
                { data: 'categoria' },
                { data: 'tipo_dado' },
                { data: 'tipo_grafico' },
                { data: 'sistema' },
                { data: 'ordem' },
                {
                    data: 'acoes',
                    orderable: false,
                    render: function (id, type, row) {
                        var html = '<a href="index.php?p=campos_padrao_form&id=' + id + '" class="btn-icon btn-icon-sm btn-edit" aria-label="Editar"><i class="bi bi-pencil"></i></a> ';
                        if (!row.eh_sistema) {
                            html += '<button type="button" class="btn-icon btn-icon-sm btn-delete" data-id="' + id + '" aria-label="Excluir"><i class="bi bi-trash"></i></button>';
                        }
                        return html;
                    }
                }
            ]
        });

        $('#filtroCategoria').on('change', function () {
            tabela.ajax.reload();
        });

        $('#tabelaCamposPadrao').on('click', '.btn-delete', function () {
            NpsCrud.excluir('ajax/campos_padrao_excluir.php', $(this).data('id'), tabela);
        });
    }

    if (isForm) {
        var id = parseInt($('#campo_padrao_id').val(), 10);
        if (id > 0) {
            NpsCrud.carregarRegistro('ajax/campos_padrao_buscar.php', id, function (d) {
                $('#nome').val(d.nome);
                $('#slug').val(d.slug);
                $('#categoria').val(d.categoria).trigger('change');
                $('#tipo_dado').val(d.tipo_dado).trigger('change');
                $('#tipo_grafico_sugerido').val(d.tipo_grafico_sugerido).trigger('change');
                $('#mapeia_participante').val(d.mapeia_participante || '').trigger('change');
                $('#ordem').val(d.ordem);
                if (parseInt(d.sistema, 10) === 1) {
                    $('#slug').prop('readonly', true);
                }
            });
        }

        $('#formCampoPadrao').on('submit', function (e) {
            e.preventDefault();
            NpsCrud.salvarForm('ajax/campos_padrao_salvar.php', $(this).serialize(), 'index.php?p=campos_padrao');
        });
    }
});
