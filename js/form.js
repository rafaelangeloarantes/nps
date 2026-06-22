/**
 * Template — Formulários
 * Select2, moeda BRL, máscaras (data, hora, CPF, CNPJ, telefone, celular).
 * Textarea: back-end deve tratar HTML e caracteres especiais ao gravar/exibir.
 */
(function ($) {
    'use strict';

    var OPTS_SELECT2 = {
        theme: 'default',
        width: '100%',
        language: {
            noResults: function () { return 'Nenhum resultado encontrado'; },
            searching: function () { return 'Buscando…'; },
            inputTooShort: function (args) { return 'Digite ' + args.minimum + ' ou mais caracteres'; },
            errorLoading: function () { return 'Erro ao carregar.'; }
        }
    };

    /**
     * Inicializa Select2 em todos os selects com classe .form-select2.
     * Usar apenas 'select.form-select2' para não atingir o container do Select2 (que herda a classe).
     */
    function initSelect2() {
        var $sel = $('select.form-select2');
        if (!$sel.length) return;
        if (typeof $.fn.select2 === 'undefined') {
            console.warn('Form: Select2 não carregado. Inclua CSS e JS do Select2.');
            return;
        }
        try {
            $sel.each(function () {
                var $el = $(this);
                if ($el.attr('data-estados-cidades') || $el.attr('data-cidade-uf') || $el.attr('data-paises')) return;
                try {
                    if ($el.hasClass('select2-hidden-accessible')) {
                        $el.select2('destroy');
                    }
                } catch (e) { /* ignora */ }
                $el.removeData();
                var opts = $.extend({}, OPTS_SELECT2);
                var placeholder = $el.attr('data-placeholder');
                if (placeholder) {
                    opts.placeholder = placeholder;
                    opts.allowClear = !$el.prop('multiple');
                }
                $el.select2(opts);
            });
        } catch (e) {
            console.warn('Form: Select2 falhou.', e);
        }
    }

    /**
     * Opções padrão para moeda Real (Brasil): R$, ponto milhar, vírgula decimal.
     */
    var OPTS_MONEY_BRL = {
        prefix: 'R$ ',
        thousands: '.',
        decimal: ',',
        allowNegative: true,
        affixesStay: true
    };

    /**
     * Aplica máscara de moeda BRL em campos .currency-brl.
     */
    function initCurrencyBrl() {
        var $inputs = $('.currency-brl');
        if (!$inputs.length) return;
        if (typeof $.fn.maskMoney === 'undefined') {
            console.warn('Form: maskMoney não carregado. Inclua jquery.maskMoney para campos em R$.');
            return;
        }
        $inputs.each(function () {
            var $el = $(this);
            if ($el.data('maskMoney')) return;
            $el.maskMoney(OPTS_MONEY_BRL);
        });
    }

    /**
     * Máscaras (jQuery Mask Plugin): data, hora, CPF, CNPJ, telefone, celular.
     * Classes: .mask-data, .mask-hora, .mask-cpf, .mask-cnpj, .mask-telefone, .mask-celular
     */
    function initMasks() {
        if (typeof $.fn.mask === 'undefined') {
            console.warn('Form: jQuery Mask Plugin não carregado. Inclua jquery.mask para máscaras de data, CPF, etc.');
            return;
        }
        // Data: dd/mm/aaaa
        $('.mask-data').mask('00/00/0000', { placeholder: '__/__/____' });
        // Hora: HH:mm
        $('.mask-hora').mask('00:00', { placeholder: '__:__' });
        // CPF: 000.000.000-00
        $('.mask-cpf').mask('000.000.000-00', { reverse: true });
        // CNPJ: 00.000.000/0000-00
        $('.mask-cnpj').mask('00.000.000/0000-00', { reverse: true });
        // Telefone fixo: (00) 0000-0000
        $('.mask-telefone').mask('(00) 0000-0000');
        // Celular: (00) 00000-0000
        $('.mask-celular').mask('(00) 00000-0000');
    }

    /**
     * No submit: converte apenas moeda para número; CPF/CNPJ/telefone/celular seguem mascarados
     * (back-end remove não-dígitos ao gravar). Data e hora seguem no formato dd/mm/aaaa e HH:mm.
     */
    function onFormSubmit($form) {
        $form.find('.currency-brl').each(function () {
            var $input = $(this);
            if (!$input.attr('name')) return;
            var unmasked = $input.maskMoney('unmasked')[0];
            if (unmasked == null || isNaN(unmasked)) unmasked = '';
            else unmasked = String(unmasked).replace(',', '.');
            $input.attr('data-masked-value', $input.val());
            $input.val(unmasked);
        });
    }

    function bindFormSubmit() {
        $(document).on('submit', 'form.form-model', function () {
            onFormSubmit($(this));
        });
    }

    /**
     * Redes sociais: normaliza URL colada para só o perfil/slug (LinkedIn/Instagram).
     * Mantém apenas prefixo + input; validação no back-end.
     */
    var SOCIAL = {
        linkedin: {
            urlRegex: /linkedin\.com\/in\/([a-zA-Z0-9\-]+)/i
        },
        instagram: {
            urlRegex: /instagram\.com\/([a-zA-Z0-9._]+)/i
        }
    };

    function getSocialSlug(social, val) {
        if (!val || !SOCIAL[social]) return '';
        var v = val.trim();
        if (!v) return '';
        var m = v.match(SOCIAL[social].urlRegex);
        if (m) return m[1];
        return v.replace(/^https?:\/\//i, '').replace(/^www\./i, '').replace(/\/$/, '').split('/').pop() || v;
    }

    function initSocialFields() {
        $('.input-social-wrap').each(function () {
            var $wrap = $(this);
            var social = $wrap.attr('data-social');
            if (!social || !SOCIAL[social]) return;
            var $input = $wrap.find('.input-social-input');

            $input.off('paste.social').on('paste.social', function () {
                var el = this;
                setTimeout(function () {
                    var val = $(el).val();
                    var slug = getSocialSlug(social, val);
                    if (slug !== val) $(el).val(slug);
                }, 10);
            });

            $input.off('blur.social').on('blur.social', function () {
                var val = $(this).val().trim();
                if (!val) return;
                var slug = getSocialSlug(social, val);
                if (slug !== val) $input.val(slug);
            });
        });
    }

    /**
     * Localidade (data/all.json): País + Estado/Província + Cidade.
     * API única: api/localidade.php → países | ?pais=BR estados | ?pais=BR&estado=AC cidades.
     * País com estados no JSON: selects. País sem estados: inputs de texto.
     */
    var API_LOCALIDADE = 'api/localidade.php';

    function reinitSelect2($el) {
        if (!$el || !$el.length) return;
        try {
            if ($el.hasClass('select2-hidden-accessible')) $el.select2('destroy');
        } catch (e) { /* ignora */ }
        $el.removeData();
        $el.select2(OPTS_SELECT2);
    }

    function setLocalidadeNames(usarSelects) {
        var $estadoSel = $('#estado');
        var $estadoInput = $('#estado_text');
        var $cidadeSel = $('#cidade');
        var $cidadeInput = $('#cidade_text');
        if (usarSelects) {
            $estadoSel.attr('name', 'estado');
            $estadoInput.removeAttr('name').val('');
            $cidadeSel.attr('name', 'cidade');
            $cidadeInput.removeAttr('name').val('');
        } else {
            $estadoSel.removeAttr('name');
            $estadoInput.attr('name', 'estado');
            $cidadeSel.removeAttr('name');
            $cidadeInput.attr('name', 'cidade');
        }
    }

    function toggleLocalidadeModo() {
        var paisVal = ($('#pais').val() || '').trim();
        var $form = $('#pais').closest('form');
        if (!paisVal) {
            $('.estado-brasil-wrap').removeClass('hidden').show();
            $('.estado-outros-wrap').addClass('hidden').hide();
            $('.cidade-brasil-wrap').removeClass('hidden').show();
            $('.cidade-outros-wrap').addClass('hidden').hide();
            $('#estado').find('option:not([value=""])').remove();
            $('#estado').find('option[value=""]').text('Selecione o país primeiro');
            $('#cidade').find('option').remove();
            $('#cidade').append($('<option value="">Selecione o país primeiro</option>'));
            $('#estado').removeAttr('name');
            $('#estado_text').removeAttr('name');
            $('#cidade').removeAttr('name');
            $('#cidade_text').removeAttr('name');
            reinitSelect2($('#estado'));
            reinitSelect2($('#cidade'));
            return;
        }
        loadEstados(paisVal).then(function (estados) {
            var usarSelects = estados && estados.length > 0;
            if (usarSelects) {
                $('.estado-brasil-wrap').removeClass('hidden').show();
                $('.estado-outros-wrap').addClass('hidden').hide();
                $('.cidade-brasil-wrap').removeClass('hidden').show();
                $('.cidade-outros-wrap').addClass('hidden').hide();
                var initialUf = $form.length ? ($form.attr('data-initial-estado') || '').trim() : '';
                if (initialUf) $('#estado').val(initialUf);
                var uf = $('#estado').val();
                loadCidades(paisVal, uf, $form.length && ($form.attr('data-initial-estado') || '').trim() !== '');
            } else {
                $('.estado-brasil-wrap').addClass('hidden').hide();
                $('.estado-outros-wrap').removeClass('hidden').show();
                $('.cidade-brasil-wrap').addClass('hidden').hide();
                $('.cidade-outros-wrap').removeClass('hidden').show();
                if ($form.length) {
                    $('#estado_text').val(($form.attr('data-initial-estado') || '').trim());
                    $('#cidade_text').val(($form.attr('data-initial-cidade') || '').trim());
                }
                $('#estado').find('option:not([value=""])').remove();
                $('#estado').find('option[value=""]').text('Selecione o estado primeiro');
                $('#cidade').find('option').remove();
                $('#cidade').append($('<option value="">Selecione o estado primeiro</option>'));
            }
            setLocalidadeNames(usarSelects);
        });
    }

    function loadPaises() {
        return $.getJSON(API_LOCALIDADE).then(function (paises) {
            var $pais = $('#pais');
            if (!$pais.length) return;
            var $form = $pais.closest('form');
            var initialPais = $form.length ? ($form.attr('data-initial-pais') || '').trim().toUpperCase() : '';
            $pais.find('option:not([value=""])').remove();
            $.each(paises, function (i, p) {
                $pais.append($('<option></option>').attr('value', p.sigla).text(p.nome));
            });
            if (initialPais) $pais.val(initialPais);
            reinitSelect2($pais);
            return paises;
        });
    }

    function loadEstados(pais) {
        if (!pais) return $.when([]);
        return $.getJSON(API_LOCALIDADE + '?pais=' + encodeURIComponent(pais)).then(function (estados) {
            var $estado = $('#estado');
            if (!$estado.length) return [];
            var $form = $estado.closest('form');
            var initialUf = $form.length ? ($form.attr('data-initial-estado') || '').trim() : '';
            $estado.find('option:not([value=""])').remove();
            $.each(estados, function (i, e) {
                $estado.append($('<option></option>').attr('value', e.sigla).text(e.nome));
            });
            if (initialUf) $estado.val(initialUf);
            reinitSelect2($estado);
            return estados;
        });
    }

    function loadCidades(pais, estadoCode, setInitial) {
        var $cidade = $('#cidade');
        if (!$cidade.length || !pais || !estadoCode) {
            if ($('#cidade').length) {
                $cidade.find('option:not([value=""])').remove();
                $cidade.find('option[value=""]').text('Selecione o estado primeiro');
                reinitSelect2($cidade);
            }
            return $.when();
        }
        var url = API_LOCALIDADE + '?pais=' + encodeURIComponent(pais) + '&estado=' + encodeURIComponent(estadoCode);
        return $.getJSON(url).then(function (cidades) {
            var $form = $cidade.closest('form');
            var initialCidade = (setInitial && $form.length) ? ($form.attr('data-initial-cidade') || '').trim() : '';
            $cidade.find('option').remove();
            $cidade.append($('<option value="">Selecione...</option>'));
            $.each(cidades, function (i, nome) {
                $cidade.append($('<option></option>').attr('value', nome).text(nome));
            });
            if (initialCidade) $cidade.val(initialCidade);
            reinitSelect2($cidade);
        });
    }

    function initEstadosCidades() {
        var $pais = $('#pais');
        var $estado = $('#estado');
        var $cidade = $('#cidade');
        if (!$pais.length) return;

        loadPaises().then(function () {
            toggleLocalidadeModo();
        });

        $pais.off('change.localidade').on('change.localidade', function () {
            toggleLocalidadeModo();
        });

        $estado.off('change.ec').on('change.ec', function () {
            var pais = $('#pais').val();
            if (!pais) return;
            loadCidades(pais, $(this).val(), false);
        });
    }

    function init() {
        initSocialFields();
        initCurrencyBrl();
        initMasks();
        bindFormSubmit();
        initEstadosCidades();
        initSelect2();
    }

    $(function () {
        init();
        // Reaplica máscaras após load (garante que inputs já existam e plugins carregados)
        $(window).on('load', function () {
            if (typeof $.fn.mask !== 'undefined') initMasks();
            if (typeof $.fn.maskMoney !== 'undefined') initCurrencyBrl();
        });
    });

    window.TemplateForm = {
        initSelect2: initSelect2,
        initCurrencyBrl: initCurrencyBrl,
        initMasks: initMasks,
        initSocialFields: initSocialFields
    };

})(jQuery);
