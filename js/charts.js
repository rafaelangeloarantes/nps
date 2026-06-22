/**
 * Template — Charts (Chart.js)
 * Donut, barras horizontais; cores via variáveis CSS quando disponíveis.
 * Uso: incluir Chart.js antes deste script; inicialização automática por data-* ou manual.
 */
(function ($) {
    'use strict';

    if (typeof Chart === 'undefined') {
        console.warn('Charts: Chart.js não carregado. Inclua o script do Chart.js antes de charts.js.');
        return;
    }

    /**
     * Obtém cor CSS variable ou fallback hex
     */
    function getThemeColor(name, fallback) {
        try {
            var val = getComputedStyle(document.documentElement).getPropertyValue('--' + name).trim();
            if (val) return val;
        } catch (e) {}
        return fallback || '#94A3B8';
    }

    /**
     * Paleta padrão para gráficos (compatível com tema claro/escuro)
     */
    function getChartColors() {
        return [
            getThemeColor('primary', '#4F46E5'),
            getThemeColor('accent-blue', '#60A5FA'),
            getThemeColor('accent-orange', '#FB923C'),
            getThemeColor('accent-green', '#4ADE80'),
            getThemeColor('accent-purple', '#A78BFA'),
            getThemeColor('secondary', '#8B5CF6'),
            getThemeColor('warning', '#F59E0B'),
            getThemeColor('info', '#3B82F6'),
            getThemeColor('text-muted', '#94A3B8')
        ];
    }

    /**
     * Cria gráfico Donut com total no centro e legenda.
     * @param {string} canvasId - ID do elemento canvas
     * @param {Object} opts - { labels: [], values: [], totalLabel: 'Total', colors: [] }
     */
    function initDonut(canvasId, opts) {
        var el = document.getElementById(canvasId);
        if (!el || !opts || !opts.labels || !opts.values) return null;

        var colors = opts.colors || getChartColors();
        var total = opts.values.reduce(function (a, b) { return a + b; }, 0);
        var totalLabel = opts.totalLabel != null ? opts.totalLabel : 'Total';

        var centerValue = el.closest('.chart-donut-canvas-wrap');
        if (centerValue) {
            var centerEl = centerValue.querySelector('.chart-donut-center');
            if (centerEl) {
                var vEl = centerEl.querySelector('.chart-donut-center-value');
                var lEl = centerEl.querySelector('.chart-donut-center-label');
                if (vEl) vEl.textContent = total.toLocaleString('pt-BR');
                if (lEl) lEl.textContent = totalLabel;
            }
        }

        var legendWrap = el.closest('.chart-donut-wrap');
        if (legendWrap) {
            var legendEl = legendWrap.querySelector('.chart-donut-legend');
            if (legendEl) {
                legendEl.innerHTML = '';
                opts.labels.forEach(function (label, i) {
                    var val = opts.values[i] || 0;
                    var pct = total ? ((val / total) * 100).toFixed(0) : 0;
                    var color = colors[i % colors.length];
                    var li = document.createElement('li');
                    li.innerHTML = '<span class="chart-donut-legend-swatch" style="background:' + color + '"></span>' +
                        '<span class="chart-donut-legend-label">' + label + '</span>' +
                        ' <span class="chart-donut-legend-value">' + Number(val).toLocaleString('pt-BR') + ' (' + pct + '%)</span>';
                    legendEl.appendChild(li);
                });
            }
        }

        var data = {
            labels: opts.labels,
            datasets: [{
                data: opts.values,
                backgroundColor: colors.slice(0, opts.values.length),
                borderWidth: 0,
                hoverOffset: 4
            }]
        };

        var config = {
            type: 'doughnut',
            data: data,
            options: {
                responsive: true,
                maintainAspectRatio: true,
                cutout: '65%',
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label: function (ctx) {
                                var pct = total ? ((ctx.raw / total) * 100).toFixed(1) : 0;
                                return ctx.label + ': ' + ctx.raw.toLocaleString('pt-BR') + ' (' + pct + '%)';
                            }
                        }
                    }
                }
            }
        };

        return new Chart(el, config);
    }

    /**
     * Altura recomendada para barras horizontais conforme quantidade de itens.
     */
    function getHorizontalBarLayout(labelCount) {
        var count = Math.max(1, labelCount || 1);
        var rowHeight = 38;
        var padding = 44;
        var innerHeight = count * rowHeight + padding;
        var maxVisible = 440;
        return {
            innerHeight: innerHeight,
            wrapHeight: Math.min(maxVisible, Math.max(240, innerHeight)),
            scrollable: innerHeight > maxVisible
        };
    }

    /**
     * Monta o container do gráfico de barras com altura proporcional aos itens.
     */
    function buildHorizontalBarMarkup(canvasId, labelCount) {
        var layout = getHorizontalBarLayout(labelCount);
        var scrollAttr = layout.scrollable ? ' data-scroll="1"' : '';
        return (
            '<div class="chart-bar-wrap" style="height:' + layout.wrapHeight + 'px"' + scrollAttr + '>' +
            '<div class="chart-bar-inner" style="height:' + layout.innerHeight + 'px">' +
            '<canvas id="' + canvasId + '"></canvas></div></div>'
        );
    }

    /**
     * Cria gráfico de barras horizontais (bar com indexAxis: 'y').
     * @param {string} canvasId - ID do canvas
     * @param {Object} opts - { labels: [], values: [], colors: [] }
     */
    function initHorizontalBar(canvasId, opts) {
        var el = document.getElementById(canvasId);
        if (!el || !opts || !opts.labels || !opts.values) return null;

        var colors = opts.colors || getChartColors();
        var maxVal = Math.max.apply(null, opts.values);
        var labelCount = opts.labels.length;

        // Ajusta container legado (altura fixa) para o layout dinâmico
        var inner = el.closest('.chart-bar-inner');
        var wrap = el.closest('.chart-bar-wrap');
        if (!inner || !wrap) {
            var layout = getHorizontalBarLayout(labelCount);
            var parent = el.parentElement;
            if (parent) {
                parent.classList.add('chart-bar-wrap');
                parent.style.height = layout.wrapHeight + 'px';
                if (layout.scrollable) {
                    parent.setAttribute('data-scroll', '1');
                }
                inner = document.createElement('div');
                inner.className = 'chart-bar-inner';
                inner.style.height = layout.innerHeight + 'px';
                parent.insertBefore(inner, el);
                inner.appendChild(el);
            }
        }

        var data = {
            labels: opts.labels,
            datasets: [{
                data: opts.values,
                backgroundColor: opts.values.map(function (_, i) { return colors[i % colors.length]; }),
                borderWidth: 0,
                borderRadius: 4,
                maxBarThickness: 28,
                barPercentage: 0.82,
                categoryPercentage: 0.88
            }]
        };

        var config = {
            type: 'bar',
            data: data,
            options: {
                indexAxis: 'y',
                responsive: true,
                maintainAspectRatio: false,
                layout: {
                    padding: { top: 4, right: 8, bottom: 4, left: 4 }
                },
                scales: {
                    x: {
                        max: maxVal ? Math.ceil(maxVal * 1.1) : 100,
                        grid: { color: getThemeColor('border', '#E2E8F0') },
                        ticks: {
                            color: getThemeColor('text-muted', '#94A3B8'),
                            font: { size: 11 }
                        }
                    },
                    y: {
                        grid: { display: false },
                        ticks: {
                            color: getThemeColor('text-primary', '#0F172A'),
                            font: { size: 12 },
                            autoSkip: false
                        }
                    }
                },
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            afterLabel: function (ctx) {
                                var total = opts.values.reduce(function (a, b) { return a + b; }, 0);
                                var pct = total ? ((ctx.raw / total) * 100).toFixed(1) : 0;
                                return ' (' + pct + '%)';
                            }
                        }
                    }
                }
            }
        };

        return new Chart(el, config);
    }

    /**
     * Atualiza lista de barras horizontais (HTML, sem canvas) a partir de dados.
     * Cada .bar-list deve ter data-chart-bars com JSON: { labels: [], values: [], colors?: [] }
     */
    function initBarListFromData($container) {
        $container = $container || $('.bar-list[data-chart-bars]');
        $container.each(function () {
            var $list = $(this);
            var json = $list.attr('data-chart-bars');
            if (!json) return;
            try {
                var data = typeof json === 'string' ? JSON.parse(json) : json;
                var labels = data.labels || [];
                var values = data.values || [];
                var colors = data.colors || getChartColors();
                var max = Math.max.apply(null, values) || 1;
                $list.empty();
                labels.forEach(function (label, i) {
                    var val = values[i] != null ? values[i] : 0;
                    var pct = Math.round((val / max) * 100);
                    var color = colors[i % colors.length];
                    var item = '<li class="bar-list-item">' +
                        '<span class="bar-list-label">' + $('<div>').text(label).html() + '</span>' +
                        '<div class="bar-list-bar-wrap">' +
                        '<div class="bar-list-bar" style="width:' + pct + '%;background:' + color + '"></div>' +
                        '</div>' +
                        '<span class="bar-list-value">' + Number(val).toLocaleString('pt-BR') + ' <span class="bar-list-pct">(' + pct + '%)</span></span>' +
                        '</li>';
                    $list.append(item);
                });
            } catch (e) {
                console.warn('Charts: data-chart-bars inválido', e);
            }
        });
    }

    /**
     * Inicialização automática: elementos com data-chart-donut ou data-chart-bar
     */
    function autoInit() {
        document.querySelectorAll('[data-chart-donut]').forEach(function (wrap) {
            var canvas = wrap.querySelector('canvas');
            if (!canvas) return;
            if (!canvas.id) canvas.id = 'chart-donut-' + Math.random().toString(36).slice(2);
            try {
                var opts = JSON.parse(wrap.getAttribute('data-chart-donut'));
                initDonut(canvas.id, opts);
            } catch (e) {
                console.warn('Charts: data-chart-donut inválido', e);
            }
        });

        document.querySelectorAll('[data-chart-bar]').forEach(function (wrap) {
            var canvas = wrap.querySelector('canvas');
            var opts;
            try {
                opts = JSON.parse(wrap.getAttribute('data-chart-bar'));
            } catch (e) {
                console.warn('Charts: data-chart-bar inválido', e);
                return;
            }
            if (!opts || !opts.labels) return;

            if (!canvas) {
                if (!wrap.id) wrap.id = 'chart-bar-' + Math.random().toString(36).slice(2);
                var canvasId = 'chart-bar-canvas-' + Math.random().toString(36).slice(2);
                wrap.innerHTML = buildHorizontalBarMarkup(canvasId, opts.labels.length);
                canvas = document.getElementById(canvasId);
            } else {
                if (!canvas.id) canvas.id = 'chart-bar-' + Math.random().toString(36).slice(2);
            }
            if (!canvas) return;
            initHorizontalBar(canvas.id, opts);
        });

        initBarListFromData();
    }

    // API global para uso em outros projetos
    window.TemplateCharts = {
        initDonut: initDonut,
        initHorizontalBar: initHorizontalBar,
        getHorizontalBarLayout: getHorizontalBarLayout,
        buildHorizontalBarMarkup: buildHorizontalBarMarkup,
        initBarListFromData: initBarListFromData,
        getChartColors: getChartColors
    };

    $(function () {
        autoInit();
    });

})(jQuery);
