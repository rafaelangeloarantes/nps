/**

 * pesquisas_mapeamento.js — mapeamento de campos + sync

 */

$(function () {

    var pesquisaId = parseInt($('#pesquisa_id').val(), 10);

    var categoriasPadrao = ['pesquisa', 'participante'];



    function renderCampos(lista) {

        var $tbody = $('#tbodyCampos').empty();

        if (!lista || !lista.length) {

            $tbody.append('<tr><td colspan="5" class="text-muted">Nenhum campo encontrado.</td></tr>');

            return;

        }



        lista.forEach(function (item, i) {

            var importar = item.importar == 1 || item.importar === true;

            var label = item.campo_label || item.campo_origem || '';

            var exemplo = item.exemplo_valor || '—';

            var campoPadraoId = item.campo_padrao_id || item.campo_padrao_id_sugerido || '';



            var $tr = $('<tr>').attr('data-index', i);



            $tr.append(

                $('<td>').append(

                    $('<input>', { type: 'checkbox', class: 'campo-importar' }).prop('checked', importar)

                )

            );



            var $tdCampo = $('<td>');

            $tdCampo.append($('<strong>').text(item.campo_origem || ''));

            $tdCampo.append($('<input>', { type: 'hidden', class: 'campo-origem' }).val(item.campo_origem || ''));

            $tr.append($tdCampo);



            $tr.append($('<td>').append($('<small>', { class: 'text-muted' }).text(exemplo)));



            $tr.append(

                $('<td>').append(

                    $('<input>', { type: 'text', class: 'campo-label form-control' }).val(label)

                )

            );



            var $tdPadrao = $('<td class="td-campo-padrao"></td>');

            NpsCampoPadraoSelect.criarSelect($tdPadrao, categoriasPadrao, campoPadraoId);

            $tr.append($tdPadrao);



            $tbody.append($tr);

        });

    }



    function coletarCampos() {

        var lista = [];

        $('#tbodyCampos tr').each(function (i) {

            var $tr = $(this);

            var origem = $tr.find('.campo-origem').val();

            if (!origem) return;

            lista.push({

                campo_origem: origem,

                campo_label: $tr.find('.campo-label').val(),

                campo_padrao_id: $tr.find('.campo-padrao-id').val() || null,

                importar: $tr.find('.campo-importar').is(':checked') ? 1 : 0,

                ordem: i

            });

        });

        return lista;

    }



    function atualizarBotaoSync(exibir) {

        $('#btnSyncPesquisa').toggleClass('hidden', !exibir);

    }



    function carregarSalvos() {

        $.getJSON('ajax/pesquisas_campos_listar.php', { pesquisa_id: pesquisaId }, function (res) {

            if (res.status === 'success' && res.data.campos && res.data.campos.length) {

                renderCampos(res.data.campos);

                atualizarBotaoSync(true);

            }

        });

    }



    $('#btnDescobrirCampos').on('click', function () {

        $.getJSON('ajax/pesquisas_campos_descobrir.php', { pesquisa_id: pesquisaId }, function (res) {

            if (res.status === 'success') {

                renderCampos(res.data.campos || []);

                if (typeof showToast === 'function') {

                    showToast('success', res.message);

                }

            } else if (typeof showAlert === 'function') {

                showAlert('danger', res.message, 'alertContainer');

            }

        }).fail(function () {

            showAlert('danger', 'Erro ao buscar campos na API.', 'alertContainer');

        });

    });



    $('#btnSalvarMapeamento').on('click', function () {

        var campos = coletarCampos();

        $.post('ajax/pesquisas_campos_salvar.php', {

            pesquisa_id: pesquisaId,

            campos: JSON.stringify(campos)

        }, function (res) {

            if (res.status === 'success') {

                atualizarBotaoSync(true);

                if (typeof showToast === 'function') showToast('success', res.message);

            } else if (typeof showAlert === 'function') {

                showAlert('danger', res.message, 'alertContainer');

            }

        }, 'json').fail(function (xhr) {

            var msg = 'Erro ao salvar mapeamento.';

            if (xhr.responseJSON && xhr.responseJSON.message) {

                msg = xhr.responseJSON.message;

            }

            if (typeof showAlert === 'function') {

                showAlert('danger', msg, 'alertContainer');

            }

        });

    });



    $('#btnSyncPesquisa').on('click', function () {

        Swal.fire({

            title: 'Sincronizar respostas?',

            text: 'Importa as respostas da API conforme o mapeamento salvo.',

            icon: 'question',

            showCancelButton: true,

            confirmButtonText: 'Sim, sincronizar',

            cancelButtonText: 'Cancelar'

        }).then(function (result) {

            if (!result.isConfirmed) return;

            $.post('ajax/pesquisas_sincronizar.php', { id: pesquisaId }, function (res) {

                if (res.status === 'success') {

                    Swal.fire('Concluído', res.message, 'success');

                } else {

                    Swal.fire('Erro', res.message, 'error');

                }

            }, 'json');

        });

    });



    carregarSalvos();

});


