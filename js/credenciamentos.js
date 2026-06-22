/** credenciamentos.js */
$(function () {
    var isList = $('#tabelaCredenciamentos').length;
    var isForm = $('#formCredenciamento').length;
    var tabela;

    function carregarParticipantes(eventoId) {
        var params = {};
        if (eventoId) {
            params.evento_id = eventoId;
        }
        $.getJSON('ajax/opcoes_participantes.php', params, function (res) {
            var $sel = $('#participante_id');
            var atual = $sel.val();
            $sel.empty().append('<option value="">Selecione...</option>');
            (res.data || []).forEach(function (p) {
                $sel.append('<option value="' + p.id + '">' + p.nome_completo + ' (' + p.email + ')</option>');
            });
            if (atual) {
                $sel.val(atual);
            }
            if ($sel.hasClass('select2-hidden-accessible')) {
                $sel.trigger('change');
            } else if (window.TemplateForm && typeof window.TemplateForm.initSelect2 === 'function') {
                window.TemplateForm.initSelect2();
            }
        });
    }

    if (isList) {
        tabela = NpsDataTable.create('#tabelaCredenciamentos', {
            processing: true,
            serverSide: true,
            ajax: {
                url: 'ajax/credenciamentos_listar.php',
                data: function (d) {
                    d.evento_id = $('#filtroEvento').val();
                }
            },
            order: [[0, 'desc']],
            columns: [
                { data: 'id' },
                { data: 'evento_nome' },
                { data: 'participante' },
                { data: 'email' },
                { data: 'status', orderable: false },
                { data: 'ultima_sync' },
                {
                    data: 'acoes',
                    orderable: false,
                    render: function (id) {
                        return NpsCrud.btnAcoes(id, { editUrl: 'index.php?p=credenciamentos_form&id=' + id });
                    }
                }
            ]
        });

        $('#filtroEvento').on('change', function () {
            tabela.ajax.reload();
        });

        $('#tabelaCredenciamentos').on('click', '.btn-delete', function () {
            NpsCrud.excluir('ajax/credenciamentos_excluir.php', $(this).data('id'), tabela);
        });
    }

    if (isForm) {
        carregarParticipantes();

        $('#evento_id').on('change', function () {
            carregarParticipantes($(this).val());
        });

        var id = parseInt($('#credenciamento_id').val(), 10);
        if (id > 0) {
            NpsCrud.carregarRegistro('ajax/credenciamentos_buscar.php', id, function (d) {
                $('#evento_id').val(d.evento_id).trigger('change');
                setTimeout(function () {
                    $('#participante_id').val(d.participante_id).trigger('change');
                }, 300);
                $('#status').val(d.status).trigger('change');
            });
        }

        $('#formCredenciamento').on('submit', function (e) {
            e.preventDefault();
            NpsCrud.salvarForm('ajax/credenciamentos_salvar.php', $(this).serialize(), 'index.php?p=credenciamentos');
        });
    }
});
