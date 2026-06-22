/**
 * Renderização estruturada das respostas de pesquisas (participante / modal).
 */
(function (window, $) {
    'use strict';

    var META_CAMPOS = ['nome', 'e-mail', 'email', 'codigo interno', 'código interno'];

    function esc(texto) {
        return $('<div>').text(texto == null ? '' : String(texto)).html();
    }

    function formatarData(valor) {
        if (!valor) return '';
        var d = new Date(String(valor).replace(' ', 'T'));
        if (isNaN(d.getTime())) return esc(valor);
        var pad = function (n) { return n < 10 ? '0' + n : n; };
        return pad(d.getDate()) + '/' + pad(d.getMonth() + 1) + '/' + d.getFullYear() +
            ' ' + pad(d.getHours()) + ':' + pad(d.getMinutes());
    }

    function tipoCampo(label) {
        var k = String(label || '').toLowerCase().trim();
        if (k === 'nps') return { grupo: 0, ordem: 0 };
        var idxMeta = META_CAMPOS.indexOf(k);
        if (idxMeta !== -1) return { grupo: 1, ordem: idxMeta };
        var m = String(label).match(/^(\d+)\./);
        if (m) return { grupo: 2, ordem: parseInt(m[1], 10) };
        return { grupo: 3, ordem: 0 };
    }

    function ordenarCampos(campos) {
        return Object.keys(campos || {}).sort(function (a, b) {
            var ta = tipoCampo(a);
            var tb = tipoCampo(b);
            if (ta.grupo !== tb.grupo) return ta.grupo - tb.grupo;
            if (ta.grupo === 2) return ta.ordem - tb.ordem;
            if (ta.grupo === 3) return a.localeCompare(b, 'pt-BR');
            return ta.ordem - tb.ordem;
        });
    }

    function isNps(label, valor) {
        return String(label || '').toLowerCase().trim() === 'nps' &&
            /^-?\d+(\.\d+)?$/.test(String(valor).trim());
    }

    function classeNps(valor) {
        var n = parseFloat(valor);
        if (n >= 9) return 'pesquisa-nps--promotor';
        if (n >= 7) return 'pesquisa-nps--neutro';
        return 'pesquisa-nps--detrator';
    }

    function isTextoLongo(valor) {
        return String(valor == null ? '' : valor).length > 72;
    }

    function montarCampo(label, valor, opcoes) {
        var html = '';
        if (isNps(label, valor) && opcoes.destacarNps) {
            html += '<div class="pesquisa-nps ' + classeNps(valor) + '">';
            html += '<span class="pesquisa-nps__label">NPS</span>';
            html += '<span class="pesquisa-nps__valor">' + esc(valor) + '</span>';
            html += '</div>';
            return html;
        }

        var wide = isTextoLongo(valor) ? ' pesquisa-campo--wide' : '';
        html += '<div class="pesquisa-campo' + wide + '">';
        html += '<dt class="pesquisa-campo__label">' + esc(label) + '</dt>';
        html += '<dd class="pesquisa-campo__valor">' + esc(valor) + '</dd>';
        html += '</div>';
        return html;
    }

    function montarPesquisa(item, opcoes) {
        var campos = item.campos && typeof item.campos === 'object' ? item.campos : {};
        var chaves = ordenarCampos(campos);
        var html = '<article class="pesquisa-resposta">';

        html += '<header class="pesquisa-resposta__header">';
        html += '<div class="pesquisa-resposta__titulo">';
        html += '<i class="bi bi-clipboard-data" aria-hidden="true"></i>';
        html += '<h5>' + esc(item.pesquisa_nome || 'Pesquisa') + '</h5>';
        html += '</div>';
        if (item.atualizado_em) {
            html += '<time class="pesquisa-resposta__data" datetime="' + esc(item.atualizado_em) + '">';
            html += '<i class="bi bi-clock" aria-hidden="true"></i> ' + formatarData(item.atualizado_em);
            html += '</time>';
        }
        html += '</header>';

        if (!chaves.length) {
            html += '<p class="pesquisa-resposta__vazio">Sem campos registrados nesta resposta.</p>';
        } else {
            html += '<div class="pesquisa-resposta__corpo">';
            chaves.forEach(function (label) {
                html += montarCampo(label, campos[label], opcoes);
            });
            html += '</div>';
        }

        html += '</article>';
        return html;
    }

    function agruparPorEvento(lista) {
        var grupos = [];
        var mapa = {};

        (lista || []).forEach(function (item) {
            var chave = String(item.evento_id) + '|' + String(item.evento_nome);
            if (!mapa[chave]) {
                mapa[chave] = {
                    evento_id: item.evento_id,
                    evento_nome: item.evento_nome,
                    itens: []
                };
                grupos.push(mapa[chave]);
            }
            mapa[chave].itens.push(item);
        });

        return grupos;
    }

    function render(lista, opcoes) {
        opcoes = opcoes || {};

        if (!lista || !lista.length) {
            return '<div class="pesquisas-respostas pesquisas-respostas--vazio">' +
                '<p class="pesquisas-respostas__empty">Nenhuma resposta de pesquisa vinculada.</p></div>';
        }

        var html = '<div class="pesquisas-respostas">';
        var grupos = agruparPorEvento(lista);

        grupos.forEach(function (grupo) {
            html += '<section class="pesquisa-evento">';
            html += '<header class="pesquisa-evento__header">';
            html += '<i class="bi bi-calendar-event" aria-hidden="true"></i>';
            html += '<h4 class="pesquisa-evento__nome">' + esc(grupo.evento_nome) + '</h4>';
            html += '<span class="pesquisa-evento__badge">' + grupo.itens.length +
                (grupo.itens.length === 1 ? ' pesquisa' : ' pesquisas') + '</span>';
            html += '</header>';
            html += '<div class="pesquisa-evento__lista">';

            grupo.itens.forEach(function (item) {
                html += montarPesquisa(item, { destacarNps: true });
            });

            html += '</div></section>';
        });

        html += '</div>';
        return html;
    }

    window.NpsPesquisasRespostas = {
        render: render,
        ordenarCampos: ordenarCampos
    };
}(window, jQuery));
