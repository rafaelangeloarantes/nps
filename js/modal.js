/**
 * Componente Modal — System Designer (template base)
 * Abrir/fechar por id, por API (conteúdo dinâmico), ESC e clique no backdrop.
 * Uso: data-modal-open="idDoModal" no botão; data-modal-close no botão fechar; ou TemplateModal.open({ title, body, footer }).
 */
(function ($) {
    'use strict';

    var bodyScrollLock = false;

    function lockBody() {
        if (bodyScrollLock) return;
        bodyScrollLock = true;
        $('body').css('overflow', 'hidden');
    }

    function unlockBody() {
        if (!bodyScrollLock) return;
        bodyScrollLock = false;
        $('body').css('overflow', '');
    }

    function setAria($overlay, open) {
        $overlay.attr('aria-hidden', open ? 'false' : 'true');
        $overlay.find('.modal').attr('role', 'dialog');
        if (open) {
            var id = $overlay.attr('id');
            if (id) $overlay.find('.modal').attr('aria-labelledby', id + '-title');
        }
    }

    /**
     * Abre um modal existente no DOM pelo id do overlay.
     * @param {string} id - ID do elemento .modal-overlay
     */
    function openById(id) {
        var $overlay = $('#' + id).closest('.modal-overlay');
        if (!$overlay.length) return;
        lockBody();
        $overlay.addClass('is-open');
        setAria($overlay, true);
        $overlay.find('.modal-close, .modal').first().focus();
    }

    /**
     * Fecha um modal.
     * @param {string|jQuery} idOrEl - ID do .modal-overlay ou jQuery do overlay/close button
     */
    function close(idOrEl) {
        var $overlay;
        if (typeof idOrEl === 'string') {
            $overlay = $('#' + idOrEl).closest('.modal-overlay');
        } else {
            $overlay = $(idOrEl).closest('.modal-overlay');
        }
        if (!$overlay.length) return;
        $overlay.removeClass('is-open');
        setAria($overlay, false);
        if ($('.modal-overlay.is-open').length === 0) {
            unlockBody();
        }
    }

    /**
     * Abre um modal criado dinamicamente (conteúdo por options).
     * @param {Object} options - { title: string, body: string|jQuery, footer: string|jQuery, size: 'sm'|'lg'|'xl', onClose: function }
     * @returns {string} id do overlay criado (para fechar programaticamente)
     */
    function open(options) {
        var opts = options || {};
        var title = opts.title || '';
        var body = '';
        if (opts.body != null) {
            if (typeof opts.body === 'string') {
                body = $('<div>').text(opts.body).html();
            } else if (opts.body && opts.body.jquery) {
                body = $('<div>').append(opts.body.clone()).html();
            } else {
                body = $('<div>').append(opts.body).html();
            }
        }
        var footer = '';
        if (opts.footer != null) {
            if (typeof opts.footer === 'string') {
                footer = $('<div>').text(opts.footer).html();
            } else if (opts.footer && opts.footer.jquery) {
                footer = $('<div>').append(opts.footer.clone()).html();
            } else {
                footer = $('<div>').append(opts.footer).html();
            }
        }
        var size = opts.size ? ' modal-' + opts.size : '';
        var id = 'modal-dyn-' + Date.now();

        var html = '<div class="modal-overlay" id="' + id + '" aria-hidden="false" data-modal-dynamic>' +
            '<div class="modal' + size + '" role="dialog" aria-labelledby="' + id + '-title" aria-modal="true">' +
            '<div class="modal-header">' +
            '<h2 class="modal-title" id="' + id + '-title">' + $('<div>').text(title).html() + '</h2>' +
            '<button type="button" class="modal-close" data-modal-close aria-label="Fechar"><i class="bi bi-x-lg"></i></button>' +
            '</div>' +
            '<div class="modal-body">' + body + '</div>' +
            (footer ? '<div class="modal-footer">' + footer + '</div>' : '') +
            '</div></div>';

        var $overlay = $(html).appendTo('body');
        lockBody();
        $overlay.addClass('is-open');

        $overlay.on('click', function (e) {
            if (e.target === this) {
                close($overlay);
                if (opts.onClose) opts.onClose();
                $overlay.remove();
            }
        });

        $overlay.find('[data-modal-close]').on('click', function (e) {
            e.preventDefault();
            close($overlay);
            if (opts.onClose) opts.onClose();
            $overlay.remove();
        });

        $(document).on('keydown.modal-' + id, function (e) {
            if (e.key === 'Escape') {
                close($overlay);
                if (opts.onClose) opts.onClose();
                $overlay.remove();
                $(document).off('keydown.modal-' + id);
            }
        });

        $overlay.find('.modal-close').first().focus();
        return id;
    }

    function init() {
        $(document).on('click', '[data-modal-open]', function (e) {
            e.preventDefault();
            var id = $(this).attr('data-modal-open');
            if (id) openById(id);
        });

        $(document).on('click', '[data-modal-close]', function (e) {
            e.preventDefault();
            close($(this));
        });

        $(document).on('click', '.modal-overlay', function (e) {
            if (e.target === this && !$(this).attr('data-modal-dynamic')) {
                close($(this));
            }
        });

        $(document).on('keydown', function (e) {
            if (e.key === 'Escape') {
                var $open = $('.modal-overlay.is-open').last();
                if ($open.length) {
                    close($open);
                    if ($open.attr('data-modal-dynamic')) $open.remove();
                }
            }
        });
    }

    $(function () {
        init();
    });

    window.TemplateModal = {
        open: open,
        openById: openById,
        close: close
    };

})(jQuery);
