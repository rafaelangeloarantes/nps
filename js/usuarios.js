/** usuarios.js — Gestão de usuários */

$(function () {

    var isList = $('#tabelaUsuarios').length;

    var isForm = $('#formUsuario').length;

    var tabela;



    function togglePerfilUi() {

        var perfil = $('#perfil').val();

        var ehUsuario = perfil === 'usuario';

        $('#grupoContrato').toggle(ehUsuario);

        if (!ehUsuario) {

            $('#contrato_id').val('').trigger('change');

        }

    }



    if (isList) {

        tabela = NpsDataTable.create('#tabelaUsuarios', {

            processing: true,

            serverSide: true,

            ajax: 'ajax/usuarios_listar.php',

            order: [[0, 'desc']],

            columns: [

                { data: 'id' },

                { data: 'nome' },

                { data: 'email' },

                { data: 'perfil', orderable: false },

                { data: 'contrato' },

                { data: 'status', orderable: false },

                { data: 'ultimo_login' },

                {

                    data: 'acoes',

                    orderable: false,

                    render: function (id) {

                        return NpsCrud.btnAcoes(id, { editUrl: 'index.php?p=usuarios_form&id=' + id });

                    }

                }

            ]

        });



        $('#tabelaUsuarios').on('click', '.btn-delete', function () {

            NpsCrud.excluir('ajax/usuarios_excluir.php', $(this).data('id'), tabela);

        });

    }



    if (isForm) {

        $('#perfil').on('change', togglePerfilUi);

        togglePerfilUi();



        var id = parseInt($('#usuario_id').val(), 10);

        if (id > 0) {

            NpsCrud.carregarRegistro('ajax/usuarios_buscar.php', id, function (d) {

                $('#nome').val(d.nome);

                $('#email').val(d.email);

                $('#perfil').val(d.perfil).trigger('change');

                $('#contrato_id').val(d.contrato_id || '').trigger('change');

                $('#ativo').val(d.ativo);

                togglePerfilUi();

            });

        }



        $('#formUsuario').on('submit', function (e) {

            e.preventDefault();

            NpsCrud.salvarForm('ajax/usuarios_salvar.php', $(this).serialize(), 'index.php?p=usuarios');

        });

    }

});

