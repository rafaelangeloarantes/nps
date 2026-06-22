/** dashboard-render.js — renderização compartilhada de widgets */
window.NpsDashboard = {
    colsClass: function (w) {
        var cols = parseInt(w.colunas_linha, 10) || 0;
        if (w.tipo_bloco === 'grade' || w.tamanho === 'inteiro') {
            return 'dashboard-cols-1';
        }
        if (cols >= 2 && cols <= 6) {
            return 'dashboard-cols-' + cols;
        }
        var map = { contador: 6, terco: 3, metade: 2, inteiro: 1 };
        var t = w.tamanho || 'metade';
        return 'dashboard-cols-' + (map[t] || 2);
    },

    renderWidget: function (w, container) {
        var colsClass = this.colsClass(w);
        var $el = $('<div class="dashboard-preview-widget ' + colsClass + '"></div>');

        if (w.tipo_bloco === 'grade') {
            $el.append(this.renderGrade(w));
        } else if (w.tipo_bloco === 'contador') {
            $el.append(this.renderContador(w));
        } else {
            $el.append(this.renderGrafico(w));
        }

        $(container).append($el);
    },

    renderContador: function (w) {
        var dados = w.dados || {};
        var valor = dados.valor != null ? dados.valor : '—';
        var $card = $('<div class="dashboard-contador-card"></div>');
        $card.append(
            '<div class="dashboard-contador-body">' +
            '<span class="dashboard-contador-label">' + $('<div>').text(w.titulo || dados.label || '').html() + '</span>' +
            '<span class="dashboard-contador-value">' + valor + '</span>' +
            (dados.subtitulo ? '<span class="dashboard-contador-sub">' + $('<div>').text(dados.subtitulo).html() + '</span>' : '') +
            '</div>'
        );
        return $card;
    },

    renderGrafico: function (w) {
        var dados = w.dados || {};
        var tipo = w.tipo_grafico || 'bar';
        var $card = $('<div class="card-chart"></div>');
        var icon = tipo === 'nps' ? 'bi-speedometer2' : 'bi-bar-chart';
        $card.append('<div class="card-chart-header"><div class="card-chart-title"><i class="bi ' + icon + '"></i> ' + $('<div>').text(w.titulo || '').html() + '</div></div>');

        var $body = $('<div class="card-chart-body"></div>');

        if (tipo === 'metric') {
            $body.append(
                '<div class="stat-card" style="margin:0;border:none;box-shadow:none;">' +
                '<div class="stat-card-body">' +
                '<span class="stat-card-label">' + $('<div>').text(dados.label || 'Valor').html() + '</span>' +
                '<span class="stat-card-value">' + (dados.valor != null ? dados.valor : '—') + '</span>' +
                '</div></div>'
            );
        } else if (tipo === 'nps') {
            var canvasId = 'chart-nps-' + (w.id || Math.random().toString(36).slice(2));
            var score = dados.score != null ? dados.score : 0;
            var total = dados.total || 0;
            $body.append(
                '<div class="dashboard-nps-wrap">' +
                '<div class="chart-donut-wrap">' +
                '<div class="chart-donut-canvas-wrap"><canvas id="' + canvasId + '"></canvas>' +
                '<div class="chart-donut-center"><span class="chart-donut-center-value">' + score + '</span>' +
                '<span class="chart-donut-center-label">NPS</span></div></div></div>' +
                '<div class="dashboard-nps-stats">' +
                '<div><strong>' + (dados.promotores || 0) + '</strong><span>Promotores</span></div>' +
                '<div><strong>' + (dados.neutros || 0) + '</strong><span>Neutros</span></div>' +
                '<div><strong>' + (dados.detratores || 0) + '</strong><span>Detratores</span></div>' +
                '<div><strong>' + total + '</strong><span>Respostas</span></div>' +
                '</div></div>'
            );
            $card.append($body);
            if (total > 0) {
                setTimeout(function () {
                    if (window.TemplateCharts && dados.labels) {
                        TemplateCharts.initDonut(canvasId, {
                            labels: dados.labels,
                            values: dados.values || [],
                            totalLabel: 'Respostas'
                        });
                    }
                }, 50);
            } else {
                $body.find('.dashboard-nps-wrap').append('<p class="text-muted dashboard-nps-empty">Nenhuma resposta NPS válida (0-10).</p>');
            }
            return $card;
        } else {
            var chartId = 'chart-' + (w.id || Math.random().toString(36).slice(2));
            var chartType = (tipo === 'donut' || tipo === 'pie') ? 'donut' : 'bar';
            if (!dados.labels || !dados.labels.length) {
                $body.append('<p class="text-muted" style="padding:1rem;">Sem dados para exibir.</p>');
            } else if (chartType === 'donut') {
                $body.append(
                    '<div class="chart-donut-wrap">' +
                    '<div class="chart-donut-canvas-wrap"><canvas id="' + chartId + '"></canvas>' +
                    '<div class="chart-donut-center"><span class="chart-donut-center-value">0</span><span class="chart-donut-center-label">Total</span></div></div>' +
                    '<ul class="chart-donut-legend"></ul></div>'
                );
            } else {
                var barMarkup = window.TemplateCharts && TemplateCharts.buildHorizontalBarMarkup
                    ? TemplateCharts.buildHorizontalBarMarkup(chartId, dados.labels.length)
                    : '<div class="chart-bar-wrap" style="height:280px"><div class="chart-bar-inner" style="height:280px"><canvas id="' + chartId + '"></canvas></div></div>';
                $body.append(barMarkup);
            }
            $card.append($body);
            setTimeout(function () {
                if (!window.TemplateCharts || !dados.labels || !dados.labels.length) return;
                if (chartType === 'donut') {
                    TemplateCharts.initDonut(chartId, { labels: dados.labels, values: dados.values || [] });
                } else {
                    TemplateCharts.initHorizontalBar(chartId, { labels: dados.labels, values: dados.values || [] });
                }
            }, 50);
            return $card;
        }

        $card.append($body);
        return $card;
    },

    renderGrade: function (w) {
        var dados = w.dados || {};
        var linhas = dados.linhas || [];
        var cols = dados.colunas || [];
        var $card = $('<div class="card-chart dashboard-grade-card"></div>');
        $card.append('<div class="card-chart-header"><div class="card-chart-title"><i class="bi bi-table"></i> ' + $('<div>').text(w.titulo || '').html() + '</div></div>');
        var $body = $('<div class="card-chart-body dashboard-grade-body"></div>');
        var $wrap = $('<div class="dashboard-grade-scroll"></div>');
        var $table = $('<table class="dashboard-grade-table"><thead><tr></tr></thead><tbody></tbody></table>');
        cols.forEach(function (c) {
            $table.find('thead tr').append('<th>' + $('<div>').text(c).html() + '</th>');
        });
        linhas.forEach(function (row) {
            var $tr = $('<tr></tr>');
            if (Array.isArray(row)) {
                row.forEach(function (cell) {
                    $tr.append('<td>' + $('<div>').text(cell != null ? cell : '').html() + '</td>');
                });
            }
            $table.find('tbody').append($tr);
        });
        $wrap.append($table);
        $body.append($wrap);
        $card.append($body);
        return $card;
    },

    carregarDashboard: function (relatorioId, container, statContainer) {
        return $.getJSON('ajax/dashboard_relatorios_dados.php', { id: relatorioId }, function (res) {
            if (res.status !== 'success' || !res.data) return;
            var dash = res.data.dashboard || {};
            $(container).empty();
            if (statContainer) {
                $(statContainer).empty();
            }
            (dash.widgets || []).forEach(function (w) {
                NpsDashboard.renderWidget(w, container);
            });
        });
    }
};
