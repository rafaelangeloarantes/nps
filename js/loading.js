/**
 * Componente Loading — System Designer (template base)
 * Overlay bloqueante para todo o sistema: submit de formulários e todas as requisições AJAX.
 * Garante que nada seja clicável até o back-end concluir. Uso: automático + TemplateLoading.show()/hide().
 */
(function ($) {
    'use strict';

    var ID_OVERLAY = 'loadingOverlay';
    var refCount = 0;
    var formSubmitPending = false;

    function getOverlay() {
        var $el = $('#' + ID_OVERLAY);
        if ($el.length) return $el;
        var html = '<div class="loading-overlay" id="' + ID_OVERLAY + '" aria-busy="false" aria-live="polite" role="status">' +
            '<div class="loading-box">' +
            '<div class="loading-spinner" aria-hidden="true"></div>' +
            '<span class="loading-message" id="' + ID_OVERLAY + '-message">Processando...</span>' +
            '</div></div>';
        return $(html).appendTo('body');
    }

    function setOverlayVisible(visible) {
        var $overlay = $('#' + ID_OVERLAY);
        if (!$overlay.length) return;
        if (visible) {
            $overlay.attr('aria-busy', 'true');
            $overlay.addClass('is-visible');
            document.body.style.pointerEvents = 'none';
            document.body.style.userSelect = 'none';
        } else {
            $overlay.attr('aria-busy', 'false');
            $overlay.removeClass('is-visible');
            document.body.style.pointerEvents = '';
            document.body.style.userSelect = '';
        }
    }

    /**
     * Exibe o overlay (uso interno + manual).
     * @param {string} [message] - Mensagem exibida (default: "Processando...")
     */
    function showOverlay(message) {
        var $overlay = getOverlay();
        $overlay.find('.loading-message').text(message || 'Processando...');
        setOverlayVisible(true);
    }

    /**
     * Esconde o overlay.
     */
    function hideOverlay() {
        refCount = 0;
        formSubmitPending = false;
        setOverlayVisible(false);
    }

    /**
     * Exibe o loading (bloqueia a tela). Para uso manual: chame hide() quando terminar.
     * @param {string} [message] - Mensagem exibida (default: "Processando...")
     */
    function show(message) {
        refCount++;
        showOverlay(message);
    }

    /**
     * Esconde o loading. Reduz o contador; só remove quando refCount <= 0.
     */
    function hide() {
        refCount--;
        if (refCount < 0) refCount = 0;
        if (refCount > 0) return;
        hideOverlay();
    }

    function shouldSkipGlobalLoading(settings) {
        var url = settings && settings.url ? String(settings.url) : '';
        if (!url) return false;

        // DataTables: listagens server-side e arquivo de idioma — usam processing próprio
        return url.indexOf('_listar.php') !== -1 || url.indexOf('datatables.net') !== -1;
    }

    function init() {
        getOverlay();

        // Todo submit de formulário exibe loading até a página recarregar ou até o AJAX terminar
        $(document).on('submit', 'form', function () {
            formSubmitPending = true;
            showOverlay('Processando...');
        });

        // Requisições AJAX: contador por requisição (exceto DataTables)
        $(document).ajaxSend(function (e, jqXHR, settings) {
            if (shouldSkipGlobalLoading(settings)) return;
            if (formSubmitPending) return;
            refCount++;
            if (refCount === 1) showOverlay('Processando...');
        });

        $(document).ajaxComplete(function (e, jqXHR, settings) {
            if (shouldSkipGlobalLoading(settings)) return;

            if (formSubmitPending) {
                formSubmitPending = false;
                hideOverlay();
                return;
            }

            refCount--;
            if (refCount < 0) refCount = 0;
            if (refCount === 0) hideOverlay();
        });
    }

    $(function () {
        init();
    });

    window.TemplateLoading = {
        show: show,
        hide: hide
    };

})(jQuery);
