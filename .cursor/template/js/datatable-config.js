/**
 * Configuração padrão dos DataTables do painel NPS.
 * Garante toolbar, footer, tipografia e layout consistentes em todos os módulos.
 */
(function ($, window) {
    'use strict';

    var defaults = {
        pageLength: 10,
        lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
        stripeClasses: [],
        dom: '<"dt-toolbar"lf>rt<"dt-footer"ip>',
        language: {
            url: 'https://cdn.datatables.net/plug-ins/1.13.8/i18n/pt-BR.json'
        }
    };

    function mergeOptions(custom) {
        return $.extend(true, {}, defaults, custom || {});
    }

    function bindResize(api) {
        function adjust() {
            try {
                api.columns.adjust();
            } catch (e) {}
        }

        $(window).on('resize.npsDt', adjust);
        $('#btnSidebarToggle').on('click.npsDt', function () {
            setTimeout(adjust, 400);
        });

        if (typeof ResizeObserver !== 'undefined') {
            var target = document.querySelector('.main-content') || document.querySelector('.card-body');
            if (target) {
                new ResizeObserver(function () {
                    setTimeout(adjust, 50);
                }).observe(target);
            }
        }
    }

    function create(selector, options) {
        var $el = $(selector);
        if (!$el.length || typeof $.fn.DataTable !== 'function') {
            return null;
        }

        var api = $el.DataTable(mergeOptions(options));
        bindResize(api);
        return api;
    }

    window.NpsDataTable = {
        defaults: defaults,
        merge: mergeOptions,
        create: create
    };
}(jQuery, window));
