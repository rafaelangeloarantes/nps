/**
 * Select de Campo Padrão NPS — reutilizado nos mapeamentos
 */
window.NpsCampoPadraoSelect = {
    _cache: {},

    carregarOpcoes: function (categorias, callback) {
        var key = (categorias || []).join(',');
        if (this._cache[key]) {
            callback(this._cache[key]);
            return;
        }
        $.getJSON('ajax/opcoes_campos_padrao.php', { categoria: key }, function (res) {
            var lista = (res && res.data) ? res.data : [];
            NpsCampoPadraoSelect._cache[key] = lista;
            callback(lista);
        });
    },

    htmlOptions: function (lista, selecionado) {
        var cats = { participante: 'Participante', evento: 'Evento', pesquisa: 'Pesquisa', credenciamento: 'Credenciamento' };
        var html = '<option value="">— Não vincular —</option>';
        var ultimaCat = '';
        lista.forEach(function (op) {
            if (op.categoria !== ultimaCat) {
                if (ultimaCat !== '') {
                    html += '</optgroup>';
                }
                var catLabel = cats[op.categoria] || op.categoria;
                html += '<optgroup label="' + $('<div>').text(catLabel).html() + '">';
                ultimaCat = op.categoria;
            }
            var sel = String(selecionado) === String(op.id) ? ' selected' : '';
            html += '<option value="' + op.id + '"' + sel + '>' + $('<div>').text(op.nome).html() + '</option>';
        });
        if (ultimaCat !== '') {
            html += '</optgroup>';
        }
        return html;
    },

    criarSelect: function ($container, categorias, valorInicial, onChange) {
        var self = this;
        var $wrap = $('<div class="campo-padrao-select-wrap"></div>');
        var $sel = $('<select class="campo-padrao-id form-control"></select>');
        var $btn = $('<button type="button" class="btn-icon btn-icon-sm btn-add-padrao" title="Cadastrar campo padrão" aria-label="Novo campo padrão"><i class="bi bi-plus-lg"></i></button>');
        $wrap.append($sel).append($btn);
        $container.empty().append($wrap);

        self.carregarOpcoes(categorias, function (lista) {
            $sel.html(self.htmlOptions(lista, valorInicial));
            if (onChange) {
                $sel.off('change.campoPadrao').on('change.campoPadrao', onChange);
            }
        });

        $btn.on('click', function (e) {
            e.preventDefault();
            e.stopPropagation();
            self.abrirModalRapido(categorias[0] || 'evento', function (novo) {
                delete self._cache[(categorias || []).join(',')];
                self.carregarOpcoes(categorias, function (lista) {
                    $sel.html(self.htmlOptions(lista, novo.id));
                    if (onChange) {
                        $sel.trigger('change');
                    }
                });
            });
        });

        return $sel;
    },

    abrirModalRapido: function (categoriaDefault, onSalvo) {
        if (typeof TemplateModal === 'undefined' || !TemplateModal.open) {
            Swal.fire('Erro', 'Componente de modal não carregado. Recarregue a página.', 'error');
            return;
        }

        var $body = $(
            '<div class="form-group"><label class="form-label">Nome</label><input type="text" class="rapido-nome form-control" autocomplete="off"></div>' +
            '<div class="form-group"><label class="form-label">Categoria</label><select class="rapido-categoria form-control">' +
            '<option value="participante">Participante</option>' +
            '<option value="evento">Evento</option>' +
            '<option value="pesquisa">Pesquisa</option>' +
            '<option value="credenciamento">Credenciamento</option>' +
            '</select></div>'
        );

        var $footer = $('<div></div>')
            .append('<button type="button" class="btn-secondary" data-rapido-cancel>Cancelar</button>')
            .append('<button type="button" class="btn-primary" data-rapido-salvar>Salvar</button>');

        var modalId = TemplateModal.open({
            title: 'Novo campo padrão',
            body: $body,
            footer: $footer
        });

        var $overlay = $('#' + modalId);
        var $inputNome = $overlay.find('.rapido-nome');
        var $selectCategoria = $overlay.find('.rapido-categoria');

        $selectCategoria.val(categoriaDefault || 'evento');
        $inputNome.focus();

        function fecharModal() {
            TemplateModal.close(modalId);
            $overlay.remove();
        }

        // Eventos no overlay (botões são clonados pelo TemplateModal — handlers no jQuery original não funcionam)
        $overlay.on('click', '[data-rapido-cancel]', function (e) {
            e.preventDefault();
            fecharModal();
        });

        $overlay.on('click', '[data-rapido-salvar]', function (e) {
            e.preventDefault();
            var $btn = $(this);
            var nome = $inputNome.val().trim();

            if (!nome) {
                Swal.fire('Atenção', 'Informe o nome do campo.', 'warning');
                return;
            }

            $btn.prop('disabled', true);
            $.post('ajax/campos_padrao_salvar_rapido.php', {
                nome: nome,
                categoria: $selectCategoria.val()
            }, function (res) {
                $btn.prop('disabled', false);
                if (res.status === 'success') {
                    fecharModal();
                    if (typeof showToast === 'function') {
                        showToast('success', res.message);
                    }
                    if (onSalvo && res.data) {
                        onSalvo(res.data);
                    }
                } else {
                    Swal.fire('Erro', res.message || 'Não foi possível salvar.', 'error');
                }
            }, 'json').fail(function (xhr) {
                $btn.prop('disabled', false);
                var msg = 'Erro ao salvar campo padrão.';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    msg = xhr.responseJSON.message;
                }
                Swal.fire('Erro', msg, 'error');
            });
        });

        $inputNome.on('keydown', function (e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                $overlay.find('[data-rapido-salvar]').trigger('click');
            }
        });
    }
};
