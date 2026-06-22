/**
 * System Designer — Modelo
 * Sidebar (mobile + recolhível), modo escuro, alertas/toasts padronizados.
 * Depende de jQuery.
 */
(function ($) {
    'use strict';

    var STORAGE_SIDEBAR = 'systemDesigner_sidebarCollapsed';
    var STORAGE_DARK    = 'systemDesigner_darkMode';

    /**
     * Sidebar mobile: abrir/fechar
     */
    function initSidebarMobile() {
        $('#btnMenu').on('click', function () {
            $('#sidebar').toggleClass('open');
            $('#sidebarOverlay').toggleClass('open');
        });
        $('#sidebarOverlay').on('click', function () {
            $('#sidebar').removeClass('open');
            $('#sidebarOverlay').removeClass('open');
        });
    }

    /**
     * Sidebar recolhível (desktop): contrair/expandir por clique, persistir em localStorage
     */
    function initSidebarCollapse() {
        var $body = $('body');
        var $icon = $('#sidebarToggleIcon');

        function applyCollapsed(collapsed) {
            if (collapsed) {
                $body.addClass('sidebar-collapsed');
                $icon.removeClass('bi-chevron-double-left').addClass('bi-chevron-double-right');
                try { localStorage.setItem(STORAGE_SIDEBAR, '1'); } catch (e) {}
            } else {
                $body.removeClass('sidebar-collapsed');
                $icon.removeClass('bi-chevron-double-right').addClass('bi-chevron-double-left');
                try { localStorage.setItem(STORAGE_SIDEBAR, '0'); } catch (e) {}
            }
        }

        $('#btnSidebarToggle').on('click', function () {
            applyCollapsed(!$body.hasClass('sidebar-collapsed'));
        });

        try {
            if (localStorage.getItem(STORAGE_SIDEBAR) === '1') {
                applyCollapsed(true);
            }
        } catch (e) {}
    }

    /**
     * Modo escuro: toggle e persistência
     */
    function initDarkMode() {
        var $body = $('body');
        var $icon = $('#darkModeIcon');

        function applyDark(dark) {
            if (dark) {
                $body.addClass('dark-mode');
                $icon.removeClass('bi-moon').addClass('bi-sun');
                try { localStorage.setItem(STORAGE_DARK, '1'); } catch (e) {}
            } else {
                $body.removeClass('dark-mode');
                $icon.removeClass('bi-sun').addClass('bi-moon');
                try { localStorage.setItem(STORAGE_DARK, '0'); } catch (e) {}
            }
        }

        $('#btnDarkMode').on('click', function () {
            applyDark(!$body.hasClass('dark-mode'));
        });

        try {
            if (localStorage.getItem(STORAGE_DARK) === '1') {
                applyDark(true);
            }
        } catch (e) {}
    }

    /**
     * Toast padronizado: exibe notificação no canto da tela.
     * @param {string} type - 'success' | 'error' | 'warning' | 'info'
     * @param {string} message - Texto da mensagem
     * @param {number} [duration] - ms para auto-fechar (0 = não fecha)
     */
    function showToast(type, message, duration) {
        var icons = {
            success: 'bi-check-circle-fill',
            error:   'bi-exclamation-triangle-fill',
            warning: 'bi-exclamation-triangle-fill',
            info:    'bi-info-circle-fill'
        };
        var icon = icons[type] || icons.info;
        var $container = $('#toastContainer');
        if (!$container.length) {
            $container = $('<div class="toast-container" id="toastContainer" role="region" aria-label="Notificações"></div>').appendTo('body');
        }
        var $toast = $('<div class="toast ' + type + '" role="alert">' +
            '<i class="bi ' + icon + '"></i>' +
            '<span>' + $('<div>').text(message).html() + '</span>' +
            '</div>');
        $container.append($toast);
        if (duration !== 0 && (duration || duration === undefined)) {
            duration = duration || 5000;
            setTimeout(function () {
                $toast.fadeOut(200, function () { $(this).remove(); });
            }, duration);
        }
    }

    /**
     * Alert inline padronizado: insere um alerta no container (ex.: #alertContainer).
     * @param {string} type - 'success' | 'danger' | 'warning' | 'info'
     * @param {string} message - Texto
     * @param {string} [containerId] - ID do elemento container (default: alertContainer)
     */
    function showAlert(type, message, containerId) {
        var icons = {
            success: 'bi-check-circle-fill',
            danger:  'bi-exclamation-triangle-fill',
            error:  'bi-exclamation-triangle-fill',
            warning: 'bi-exclamation-triangle-fill',
            info:    'bi-info-circle-fill'
        };
        var cls = type === 'error' ? 'danger' : type;
        var icon = icons[type] || icons[cls] || icons.info;
        var id = containerId || 'alertContainer';
        var $container = $('#' + id);
        if (!$container.length) return;
        var $alert = $('<div class="alert alert-' + cls + '" role="alert">' +
            '<i class="bi ' + icon + '"></i>' +
            '<span>' + $('<div>').text(message).html() + '</span>' +
            '</div>');
        $container.append($alert);
        $container[0].scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }

    // Expor para uso global (ex.: após AJAX)
    window.showToast = showToast;
    window.showAlert = showAlert;

    /**
     * Botões de exemplo: toasts (notificação no canto da tela)
     */
    function initDemoToasts() {
        $('#btnToastSuccess').on('click', function () {
            showToast('success', 'Operação realizada com sucesso.');
        });
        $('#btnToastError').on('click', function () {
            showToast('error', 'Ocorreu um erro. Tente novamente.');
        });
        $('#btnToastInfo').on('click', function () {
            showToast('info', 'Esta é uma mensagem informativa.');
        });
    }

    /**
     * Botões de exemplo: alerts inline (no #alertContainer da página).
     * Delegação em document para garantir que os botões respondam após carregamento do conteúdo.
     */
    function initDemoAlerts() {
        $(document).on('click', '#btnAlertSuccess', function () {
            showAlert('success', 'Mensagem de sucesso exibida no alert inline.');
        });
        $(document).on('click', '#btnAlertDanger', function () {
            showAlert('danger', 'Mensagem de erro exibida no alert inline.');
        });
        $(document).on('click', '#btnAlertWarning', function () {
            showAlert('warning', 'Mensagem de aviso exibida no alert inline.');
        });
        $(document).on('click', '#btnAlertInfo', function () {
            showAlert('info', 'Mensagem informativa exibida no alert inline.');
        });
    }

    /**
     * DataTable: ordenação, paginação, busca. Mantém o layout do modelo.
     * Para grande volume (server-side): use serverSide: true e ajax apontando para
     * endpoint que retorna { data: [], recordsTotal, recordsFiltered }; envie
     * start, length, search[value], order[i][column], order[i][dir] no request.
     */
    function initDataTable() {
        var $table = $('#dataTableExemplo');
        if (!$table.length) return;

        var dtApi = NpsDataTable.create('#dataTableExemplo', {
            lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, 'Todos']],
            order: [[0, 'asc']],
            columnDefs: [
                { orderable: false, targets: 3 }
            ]
        });

        if (!dtApi) return;

        // Delegação para ações (funciona em todas as páginas do DataTable)
        $(document).on('click', '#dataTableExemplo .btn-delete', function (e) {
            e.preventDefault();
            var txt = $(this).closest('tr').find('td:first').text();
            showToast('info', 'Excluir: ' + txt);
        });
        $(document).on('click', '#dataTableExemplo .btn-edit', function (e) {
            e.preventDefault();
            var txt = $(this).closest('tr').find('td:first').text();
            showToast('info', 'Editar: ' + txt);
        });
    }

    $(function () {
        initSidebarMobile();
        initSidebarCollapse();
        initDarkMode();
        initDemoToasts();
        initDemoAlerts();
        initDataTable();
    });

})(jQuery);
