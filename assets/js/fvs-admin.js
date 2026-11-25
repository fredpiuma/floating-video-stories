/**
 * Floating Video Stories - campo de stories no admin (produto e página).
 * wp.media + jquery-ui-sortable; a lista vai em JSON num input hidden.
 */
(function ($) {
    'use strict';

    var i18n = window.fvsAdmin || {};
    var MAX = parseInt(i18n.max, 10) || 20;

    function isMp4(att) {
        return att && att.mime === 'video/mp4';
    }

    function format(str, value) {
        return String(str || '').replace(/%[sd]/, value);
    }

    function initField($field) {
        var $list = $field.find('.fvs-stories-list');
        var $input = $field.find('.fvs-stories-input');
        var $empty = $field.find('.fvs-empty');
        var $notice = $field.find('.fvs-notice');
        var frame = null;
        var thumbFrame = null;

        function showNotice(messages) {
            if (!messages.length) {
                $notice.prop('hidden', true);
                return;
            }
            $notice.find('p').text(messages.join(' '));
            $notice.prop('hidden', false);
        }

        function sync() {
            var items = $list.children('.fvs-story').map(function () {
                var $li = $(this);
                return { id: parseInt($li.attr('data-id'), 10) || 0, url: $li.attr('data-url') };
            }).get();
            $input.val(JSON.stringify(items));
            $empty.prop('hidden', items.length > 0);
        }

        function buildItem(item) {
            var $li = $('<li class="fvs-story"></li>')
                .attr('data-id', item.id || 0)
                .attr('data-url', item.url);

            $('<span class="fvs-handle dashicons dashicons-menu" aria-hidden="true"></span>')
                .attr('title', i18n.dragLabel || '')
                .appendTo($li);

            var video = document.createElement('video');
            video.className = 'fvs-story-preview';
            video.muted = true;
            video.preload = 'metadata';
            video.setAttribute('playsinline', '');
            video.src = item.url;
            $li.append(video);

            $('<span class="fvs-story-name"></span>')
                .text(item.name || item.url.split('/').pop().split('?')[0])
                .attr('title', item.url)
                .appendTo($li);

            $('<button type="button" class="button-link button-link-delete fvs-remove"></button>')
                .text(i18n.remove || 'Remover')
                .appendTo($li);

            return $li;
        }

        // Itens iniciais (salvos ou vindos do fallback legado).
        var initial = $field.data('items') || [];
        $.each(initial, function (_, item) {
            if (item && item.url) {
                $list.append(buildItem(item));
            }
        });
        sync();

        $list.sortable({
            handle: '.fvs-handle',
            axis: 'y',
            items: '> .fvs-story',
            placeholder: 'fvs-story-placeholder',
            update: sync
        });

        $list.on('click', '.fvs-remove', function (e) {
            e.preventDefault();
            $(this).closest('.fvs-story').remove();
            showNotice([]);
            sync();
        });

        $field.on('click', '.fvs-add', function (e) {
            e.preventDefault();
            if (!frame) {
                frame = wp.media({
                    title: i18n.frameTitle,
                    button: { text: i18n.frameButton },
                    library: { type: 'video/mp4' },
                    multiple: 'add'
                });
                frame.on('select', function () {
                    var rejected = [];
                    var overflow = false;
                    frame.state().get('selection').each(function (model) {
                        var att = model.toJSON();
                        if (!isMp4(att)) {
                            rejected.push(att.filename || att.title || att.url);
                            return;
                        }
                        if ($list.children('.fvs-story').length >= MAX) {
                            overflow = true;
                            return;
                        }
                        $list.append(buildItem({ id: att.id, url: att.url, name: att.filename }));
                    });
                    // Limpa a seleção para a próxima abertura não repetir os mesmos itens.
                    frame.state().get('selection').reset();

                    var messages = [];
                    if (rejected.length) {
                        messages.push(format(i18n.notMp4, rejected.join(', ')));
                    }
                    if (overflow) {
                        messages.push(format(i18n.tooMany, MAX));
                    }
                    showNotice(messages);
                    $list.sortable('refresh');
                    sync();
                });
            }
            frame.open();
        });

        // Miniatura da bolha.
        var $thumb = $field.find('.fvs-thumb-input');
        $field.on('click', '.fvs-thumb-select', function (e) {
            e.preventDefault();
            if (!thumbFrame) {
                thumbFrame = wp.media({
                    title: i18n.thumbTitle,
                    button: { text: i18n.thumbButton },
                    library: { type: 'video/mp4' },
                    multiple: false
                });
                thumbFrame.on('select', function () {
                    var att = thumbFrame.state().get('selection').first().toJSON();
                    if (!isMp4(att)) {
                        showNotice([format(i18n.notMp4, att.filename || att.url)]);
                        return;
                    }
                    showNotice([]);
                    $thumb.val(att.url);
                });
            }
            thumbFrame.open();
        });
        $field.on('click', '.fvs-thumb-clear', function (e) {
            e.preventDefault();
            $thumb.val('');
        });
    }

    $(function () {
        $('.fvs-field').each(function () {
            initField($(this));
        });
    });
})(jQuery);
