/** dashboard_templates.js */
$(function () {
    var isList = $('#tabelaDashboardTemplates').length;
    var isForm = $('#widgetsCanvas').length;
    var tabela;
    var widgets = [];
    var catalogo = null;
    var dragIdx = null;
    var editWidgetId = null;

    var tipoBlocoLabels = { contador: 'Contador', grafico: 'Gráfico', grade: 'Grade' };
    var fonteLabels = {
        participante: 'Participante',
        evento_extra: 'Atributo do evento',
        pesquisa: 'Pesquisa',
        credenciamento: 'Credenciamento'
    };

    if (isList) {
        tabela = NpsDataTable.create('#tabelaDashboardTemplates', {
            processing: true,
            serverSide: true,
            ajax: 'ajax/dashboard_templates_listar.php',
            order: [[0, 'desc']],
            columns: [
                { data: 'id' },
                { data: 'nome' },
                { data: 'descricao', render: function (v) { return NpsCrud.truncateCell(v, 50); } },
                { data: 'total_relatorios' },
                { data: 'criado_em' },
                {
                    data: 'acoes',
                    orderable: false,
                    render: function (id, type, row) {
                        var totalRel = parseInt(row.total_relatorios, 10) || 0;
                        var opts = { editUrl: 'index.php?p=dashboard_templates_form&id=' + id };
                        if (totalRel > 0) {
                            opts.deleteDisabled = true;
                            opts.deleteDisabledTitle = totalRel === 1
                                ? 'Vinculado a 1 relatório — não é possível excluir.'
                                : 'Vinculado a ' + totalRel + ' relatórios — não é possível excluir.';
                        }
                        return NpsCrud.btnAcoes(id, opts);
                    }
                }
            ]
        });

        $('#tabelaDashboardTemplates').on('click', '.btn-delete', function () {
            NpsCrud.excluir(
                'ajax/dashboard_templates_excluir.php',
                $(this).attr('data-id'),
                tabela || $('#tabelaDashboardTemplates').DataTable()
            );
        });
    }

    if (!isForm) return;

    function uid() {
        return 'w' + Date.now() + Math.random().toString(36).slice(2, 6);
    }

    function tipoBlocoAtual() {
        return $('#bloco_tipo_bloco').val() || 'contador';
    }

    function atualizarFormularioBloco(preservarValores) {
        var tipo = tipoBlocoAtual();
        var isGrade = tipo === 'grade';
        var isContador = tipo === 'contador';
        var isGrafico = tipo === 'grafico';
        var slugAtual = preservarValores ? $('#bloco_campo').val() : '';

        $('#wrapCampo').toggleClass('hidden', isGrade);
        $('#wrapTipoGrafico').toggleClass('hidden', !isGrafico);
        $('#wrapGraficoOpcoes').toggleClass('hidden', !isGrafico);
        $('#wrapColunasLinha').toggleClass('hidden', isGrade);
        $('#wrapTipoMetrica').toggleClass('hidden', !isContador);

        if (isGrade) {
            $('#hintFonte').text('A grade exibirá todos os campos mapeados desta fonte no evento.');
        } else if (isContador) {
            $('#hintFonte').text('Escolha o indicador e quantas colunas por linha.');
            if (!preservarValores) {
                $('#bloco_colunas_linha').val('6');
            }
        } else {
            $('#hintFonte').text('Escolha o campo e o layout na linha.');
            if (!preservarValores && $('#bloco_colunas_linha').val() === '6') {
                $('#bloco_colunas_linha').val('2');
            }
        }

        popularCampos(preservarValores ? slugAtual : '');
    }

    function atualizarBotaoBloco() {
        if (editWidgetId) {
            $('#sidebarBlocoTitulo').text('Editar bloco');
            $('#btnAdicionarBloco').html('<i class="bi bi-check-lg"></i> Salvar alterações');
            $('#btnCancelarEdicaoBloco').removeClass('hidden');
        } else {
            $('#sidebarBlocoTitulo').text('Adicionar bloco');
            $('#btnAdicionarBloco').html('<i class="bi bi-plus-circle"></i> Adicionar ao layout');
            $('#btnCancelarEdicaoBloco').addClass('hidden');
        }
    }

    function limparFormularioBloco() {
        editWidgetId = null;
        $('#tipoBlocoPills .dashboard-pill').removeClass('active');
        $('#tipoBlocoPills .dashboard-pill[data-tipo="contador"]').addClass('active');
        $('#bloco_tipo_bloco').val('contador');
        $('#bloco_fonte').val($('#bloco_fonte option:first').val());
        $('#bloco_titulo').val('');
        $('#bloco_grafico_limite').val('0');
        $('#bloco_grafico_separador').val('nenhum');
        atualizarFormularioBloco(false);
        atualizarBotaoBloco();
    }

    function carregarFormularioBloco(w) {
        if (!w) return;

        editWidgetId = w.id;
        var tipo = w.tipo_bloco || 'grafico';

        $('#tipoBlocoPills .dashboard-pill').removeClass('active');
        $('#tipoBlocoPills .dashboard-pill[data-tipo="' + tipo + '"]').addClass('active');
        $('#bloco_tipo_bloco').val(tipo);

        var isGrade = tipo === 'grade';
        var isContador = tipo === 'contador';
        var isGrafico = tipo === 'grafico';

        $('#wrapCampo').toggleClass('hidden', isGrade);
        $('#wrapTipoGrafico').toggleClass('hidden', !isGrafico);
        $('#wrapGraficoOpcoes').toggleClass('hidden', !isGrafico);
        $('#wrapColunasLinha').toggleClass('hidden', isGrade);
        $('#wrapTipoMetrica').toggleClass('hidden', !isContador);

        if (isGrade) {
            $('#hintFonte').text('A grade exibirá todos os campos mapeados desta fonte no evento.');
        } else if (isContador) {
            $('#hintFonte').text('Escolha o indicador e quantas colunas por linha.');
        } else {
            $('#hintFonte').text('Escolha o campo e o layout na linha.');
        }

        $('#bloco_fonte').val(w.fonte || 'participante');
        popularCampos(w.campo_padrao_slug || '');

        if (isGrafico) {
            $('#bloco_tipo_grafico').val(w.tipo_grafico || 'bar');
            $('#bloco_grafico_limite').val(w.grafico_limite_itens != null ? w.grafico_limite_itens : 0);
            $('#bloco_grafico_separador').val(w.grafico_separador || 'nenhum');
        }
        if (isContador) {
            $('#bloco_tipo_metrica').val(w.tipo_metrica || 'total');
        }

        $('#bloco_colunas_linha').val(
            w.colunas_linha || (isGrade ? 1 : (isContador ? 6 : 2))
        );
        $('#bloco_titulo').val(w.titulo || '');

        atualizarBotaoBloco();
        renderCanvas();
    }

    function coletarBlocoDoForm() {
        var tipoBloco = tipoBlocoAtual();
        var fonte = $('#bloco_fonte').val();
        var slug = $('#bloco_campo').val();
        var $opt = $('#bloco_campo').find(':selected');
        var colunasLinha = tipoBloco === 'grade' ? 1 : parseInt($('#bloco_colunas_linha').val(), 10) || 2;
        var titulo = $('#bloco_titulo').val().trim();

        if (!titulo) {
            titulo = tipoBloco === 'grade'
                ? 'Grade — ' + (fonteLabels[fonte] || fonte)
                : ($opt.data('nome') || $opt.text() || fonteLabels[fonte]);
        }

        return {
            tipo_bloco: tipoBloco,
            fonte: fonte,
            campo_padrao_slug: tipoBloco === 'grade' ? '' : slug,
            tipo_grafico: tipoBloco === 'grafico' ? $('#bloco_tipo_grafico').val() : (tipoBloco === 'contador' ? 'metric' : 'grade'),
            tipo_metrica: tipoBloco === 'contador' ? ($opt.data('metrica') || $('#bloco_tipo_metrica').val()) : 'total',
            grafico_limite_itens: tipoBloco === 'grafico' ? (parseInt($('#bloco_grafico_limite').val(), 10) || 0) : 0,
            grafico_separador: tipoBloco === 'grafico' ? ($('#bloco_grafico_separador').val() || 'nenhum') : 'nenhum',
            titulo: titulo,
            colunas_linha: colunasLinha
        };
    }

    function popularCampos(slugPreservar) {
        var fonte = $('#bloco_fonte').val();
        var $campo = $('#bloco_campo').empty();
        var lista = (catalogo && catalogo[fonte]) ? catalogo[fonte] : [];
        var slugAtual = slugPreservar !== undefined && slugPreservar !== null ? String(slugPreservar) : '';

        if (!lista.length && !slugAtual) {
            $campo.append('<option value="">Nenhum campo padrão nesta fonte</option>');
            return;
        }

        lista.forEach(function (item) {
            var opt = $('<option></option>').val(item.slug).text(item.nome);
            opt.attr('data-grafico', item.tipo_grafico || 'bar');
            opt.attr('data-metrica', item.tipo_metrica || 'total');
            opt.attr('data-nome', item.nome);
            $campo.append(opt);
        });

        if (slugAtual && !$campo.find('option[value="' + slugAtual.replace(/\\/g, '\\\\').replace(/"/g, '\\"') + '"]').length) {
            $campo.append($('<option></option>').val(slugAtual).text(slugAtual));
        }

        if (slugAtual) {
            $campo.val(slugAtual);
        } else {
            aplicarDefaultsCampo();
        }
    }

    function aplicarDefaultsCampo() {
        var $opt = $('#bloco_campo').find(':selected');
        if (!$opt.length || !$opt.val()) return;

        if ($opt.data('grafico')) $('#bloco_tipo_grafico').val($opt.data('grafico'));
        if ($opt.data('metrica')) $('#bloco_tipo_metrica').val($opt.data('metrica'));
        $('#bloco_titulo').val($opt.data('nome') || $opt.text());
    }

    function iconeBloco(tipo) {
        if (tipo === 'contador') return 'bi-123';
        if (tipo === 'grade') return 'bi-table';
        return 'bi-bar-chart';
    }

    var separadorLabels = {
        nenhum: '',
        virgula: 'sep. vírgula',
        ponto_virgula: 'sep. ;'
    };

    function resumoBloco(w) {
        var tipo = w.tipo_bloco || 'grafico';
        var cols = w.colunas_linha || (tipo === 'grade' ? 1 : 2);
        var partes = [fonteLabels[w.fonte] || w.fonte];
        if (tipo === 'grade') {
            partes.push('linha inteira');
        } else {
            partes.push(cols + '/linha');
            if (tipo === 'grafico') {
                var lim = parseInt(w.grafico_limite_itens, 10) || 0;
                if (lim > 0) partes.push('top ' + lim);
                var sep = w.grafico_separador || 'nenhum';
                if (sep !== 'nenhum' && separadorLabels[sep]) partes.push(separadorLabels[sep]);
            }
        }
        return partes.join(' · ');
    }

    function initDragDrop() {
        var $canvas = $('#widgetsCanvas');
        $canvas.off('.dtDrag');

        $canvas.on('dragstart.dtDrag', '.dashboard-widget-item', function (e) {
            dragIdx = parseInt($(this).attr('data-idx'), 10);
            $(this).addClass('is-dragging');
            e.originalEvent.dataTransfer.effectAllowed = 'move';
            e.originalEvent.dataTransfer.setData('text/plain', String(dragIdx));
        });

        $canvas.on('dragover.dtDrag', '.dashboard-widget-item', function (e) {
            e.preventDefault();
            e.originalEvent.dataTransfer.dropEffect = 'move';
            $('.dashboard-widget-item').removeClass('is-drag-over');
            $(this).addClass('is-drag-over');
        });

        $canvas.on('dragleave.dtDrag', '.dashboard-widget-item', function () {
            $(this).removeClass('is-drag-over');
        });

        $canvas.on('drop.dtDrag', '.dashboard-widget-item', function (e) {
            e.preventDefault();
            var dropIdx = parseInt($(this).attr('data-idx'), 10);
            if (dragIdx === null || isNaN(dropIdx) || dragIdx === dropIdx) return;
            var item = widgets.splice(dragIdx, 1)[0];
            widgets.splice(dropIdx, 0, item);
            dragIdx = null;
            renderCanvas();
        });

        $canvas.on('dragend.dtDrag', '.dashboard-widget-item', function () {
            dragIdx = null;
            $('.dashboard-widget-item').removeClass('is-dragging is-drag-over');
        });
    }

    function renderCanvas() {
        var $c = $('#widgetsCanvas').empty();

        if (!widgets.length) {
            $c.append('<p class="text-muted" id="widgetsEmpty">Nenhum bloco adicionado.</p>');
            return;
        }

        widgets.forEach(function (w, idx) {
            var tipo = w.tipo_bloco || 'grafico';
            var cols = parseInt(w.colunas_linha, 10) || (tipo === 'grade' ? 1 : 2);
            var titulo = w.titulo || fonteLabels[w.fonte] || 'Bloco';

            var $item = $('<div class="dashboard-widget-item dashboard-cols-' + cols + '"></div>');
            $item.attr({
                'data-idx': idx,
                'data-id': w.id || '',
                draggable: 'true'
            });
            if (editWidgetId && w.id === editWidgetId) {
                $item.addClass('is-selected');
            }

            $item.append(
                '<div class="dashboard-widget-drag" title="Arrastar para reordenar"><i class="bi bi-grip-vertical"></i></div>' +
                '<div class="dashboard-widget-icon"><i class="bi ' + iconeBloco(tipo) + '"></i></div>' +
                '<div class="dashboard-widget-body">' +
                '<span class="dashboard-widget-type-badge">' + $('<div>').text(tipoBlocoLabels[tipo] || tipo).html() + '</span>' +
                '<p class="dashboard-widget-title">' + $('<div>').text(titulo).html() + '</p>' +
                '<p class="dashboard-widget-sub">' + $('<div>').text(resumoBloco(w)).html() + '</p>' +
                '</div>' +
                '<button type="button" class="btn-icon btn-icon-sm btn-remove" data-idx="' + idx + '" aria-label="Remover"><i class="bi bi-trash"></i></button>'
            );

            $c.append($item);
        });
    }

    initDragDrop();

    $('#tipoBlocoPills').on('click', '.dashboard-pill', function () {
        $('#tipoBlocoPills .dashboard-pill').removeClass('active');
        $(this).addClass('active');
        $('#bloco_tipo_bloco').val($(this).data('tipo'));
        atualizarFormularioBloco(!!editWidgetId);
    });

    $('#bloco_fonte').on('change', function () {
        if (!editWidgetId) {
            $('#bloco_titulo').val('');
        }
        popularCampos('');
    });

    $('#bloco_campo').on('change', function () {
        if (!editWidgetId) {
            $('#bloco_titulo').val('');
            aplicarDefaultsCampo();
        }
    });

    $('#btnCancelarEdicaoBloco').on('click', function () {
        limparFormularioBloco();
        renderCanvas();
    });

    $('#widgetsCanvas').on('click', '.dashboard-widget-item', function (e) {
        if ($(e.target).closest('.btn-remove, .dashboard-widget-drag').length) {
            return;
        }
        var idx = parseInt($(this).attr('data-idx'), 10);
        if (isNaN(idx) || !widgets[idx]) {
            return;
        }
        carregarFormularioBloco(widgets[idx]);
    });

    $('#btnAdicionarBloco').on('click', function () {
        var tipoBloco = tipoBlocoAtual();
        var slug = $('#bloco_campo').val();

        if (tipoBloco !== 'grade' && !slug) {
            showAlert('warning', 'Selecione um campo padrão.', 'alertContainer');
            return;
        }

        var dados = coletarBlocoDoForm();

        if (editWidgetId) {
            var idx = widgets.findIndex(function (w) { return w.id === editWidgetId; });
            if (idx >= 0) {
                widgets[idx] = $.extend({}, widgets[idx], dados);
            }
            limparFormularioBloco();
        } else {
            widgets.push($.extend({
                id: uid(),
                ordem: widgets.length
            }, dados));
            $('#bloco_titulo').val('');
            if (tipoBloco === 'grafico') {
                $('#bloco_grafico_limite').val('0');
                $('#bloco_grafico_separador').val('nenhum');
            }
        }

        renderCanvas();
    });

    $('#widgetsCanvas').on('click', '.btn-remove', function (e) {
        e.stopPropagation();
        var idx = parseInt($(this).data('idx'), 10);
        var removido = widgets[idx];
        if (removido && removido.id === editWidgetId) {
            limparFormularioBloco();
        }
        widgets.splice(idx, 1);
        renderCanvas();
    });

    $('#btnSalvarTemplate').on('click', function () {
        var nome = $('#nome').val().trim();
        if (!nome) {
            showAlert('danger', 'Informe o nome do template.', 'alertContainer');
            return;
        }
        if (!widgets.length) {
            showAlert('danger', 'Adicione ao menos um bloco.', 'alertContainer');
            return;
        }

        widgets.forEach(function (w, i) { w.ordem = i; });

        $.post('ajax/dashboard_templates_salvar.php', {
            id: $('#template_id').val(),
            nome: nome,
            descricao: $('#descricao').val(),
            contrato_id: $('#contrato_id').val(),
            widgets: JSON.stringify(widgets)
        }, function (res) {
            if (res.status === 'success') {
                if (typeof showToast === 'function') showToast('success', res.message);
                window.location.href = 'index.php?p=dashboard_templates';
            } else if (typeof showAlert === 'function') {
                showAlert('danger', res.message, 'alertContainer');
            }
        }, 'json');
    });

    function carregarCatalogo(callback) {
        $.getJSON('ajax/dashboard_catalogo_template.php', function (res) {
            if (res.status === 'success' && res.data) {
                catalogo = res.data.catalogo || {};
            }
            if (callback) callback();
        });
    }

    var id = parseInt($('#template_id').val(), 10);
    carregarCatalogo(function () {
        atualizarFormularioBloco();

        if (id > 0) {
            $.getJSON('ajax/dashboard_templates_buscar.php', { id: id }, function (res) {
                if (res.status !== 'success' || !res.data) return;
                var d = res.data;
                $('#nome').val(d.nome);
                $('#descricao').val(d.descricao || '');
                if (d.contrato_id) $('#contrato_id').val(d.contrato_id).trigger('change');
                widgets = d.widgets || [];
                renderCanvas();
            });
        }
    });
});
