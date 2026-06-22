/**

 * eventos_mapeamento.js — mapeamento de atributos Guests + sync

 */

$(function () {

    var eventoId = parseInt($('#evento_id').val(), 10);

    var categoriasPadrao = ['participante', 'evento'];



    function renderAtributos(lista) {

        var $tbody = $('#tbodyAtributos').empty();

        if (!lista || !lista.length) {

            $tbody.append('<tr><td colspan="4" class="text-muted">Nenhum atributo encontrado.</td></tr>');

            return;

        }



        lista.forEach(function (item, i) {

            var importar = item.importar == 1 || item.importar === true;

            var exemplo = item.exemplo_valor || '—';

            var campoPadraoId = item.campo_padrao_id || item.campo_padrao_id_sugerido || '';



            var $tr = $('<tr>').attr('data-index', i);



            $tr.append(

                $('<td>').append(

                    $('<input>', { type: 'checkbox', class: 'attr-importar' }).prop('checked', importar)

                )

            );



            var $tdNome = $('<td>');

            $tdNome.append($('<strong>').text(item.atributo_nome || ''));

            $tdNome.append($('<input>', { type: 'hidden', class: 'attr-nome' }).val(item.atributo_nome || ''));

            $tdNome.append($('<input>', { type: 'hidden', class: 'attr-id-api' }).val(item.atributo_id_api || ''));

            $tr.append($tdNome);



            $tr.append($('<td>').append($('<small>', { class: 'text-muted' }).text(exemplo)));



            var $tdPadrao = $('<td class="td-campo-padrao"></td>');

            NpsCampoPadraoSelect.criarSelect($tdPadrao, categoriasPadrao, campoPadraoId);

            $tr.append($tdPadrao);



            $tbody.append($tr);

        });

    }



    function coletarAtributos() {

        var lista = [];

        $('#tbodyAtributos tr').each(function (i) {

            var $tr = $(this);

            var nome = $tr.find('.attr-nome').val();

            if (!nome) return;

            lista.push({

                atributo_nome: nome,

                atributo_id_api: $tr.find('.attr-id-api').val() || null,

                importar: $tr.find('.attr-importar').is(':checked') ? 1 : 0,

                campo_padrao_id: $tr.find('.campo-padrao-id').val() || null,

                ordem: i

            });

        });

        return lista;

    }



    function atualizarBotaoSync(exibir) {

        $('#btnSyncParticipantes').toggleClass('hidden', !exibir);

    }



    function carregarSalvos() {

        $.getJSON('ajax/eventos_atributos_listar.php', { evento_id: eventoId }, function (res) {

            if (res.status === 'success' && res.data.atributos && res.data.atributos.length) {

                renderAtributos(res.data.atributos);

                atualizarBotaoSync(true);

            }

        });

    }



    $('#btnDescobrirAtributos').on('click', function () {

        $.getJSON('ajax/eventos_atributos_descobrir.php', { evento_id: eventoId }, function (res) {

            if (res.status === 'success' || res.status === 'warning') {

                renderAtributos(res.data.atributos || []);

                if (res.status === 'warning') {

                    Swal.fire('Atenção', res.message, 'warning');

                } else if (typeof showToast === 'function') {

                    showToast('success', res.message);

                }

            } else if (typeof showAlert === 'function') {

                showAlert('danger', res.message, 'alertContainer');

            }

        }).fail(function () {

            showAlert('danger', 'Erro ao buscar atributos na API.', 'alertContainer');

        });

    });



    $('#btnSalvarMapeamento').on('click', function () {

        var atributos = coletarAtributos();

        $.post('ajax/eventos_atributos_salvar.php', {

            evento_id: eventoId,

            atributos: JSON.stringify(atributos)

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



    $('#btnSyncParticipantes').on('click', function () {

        Swal.fire({

            title: 'Sincronizar participantes?',

            text: 'Importa guests da API (participantes + credenciamento SHOW/NOSHOW).',

            icon: 'question',

            showCancelButton: true,

            confirmButtonText: 'Sim, sincronizar',

            cancelButtonText: 'Cancelar'

        }).then(function (result) {

            if (!result.isConfirmed) return;

            $.post('ajax/participantes_sincronizar_evento.php', { evento_id: eventoId }, function (res) {

                if (res.status === 'success') {

                    Swal.fire('Concluído', res.message, 'success');

                } else {

                    Swal.fire('Erro', res.message, 'error');

                }

            }, 'json');

        });

    });



    $('#btnLimparDadosEvento').on('click', function () {

        NpsCrud.limparDadosEvento($(this).data('evento-id'), function () {

            carregarSalvos();

        });

    });



    carregarSalvos();

});


