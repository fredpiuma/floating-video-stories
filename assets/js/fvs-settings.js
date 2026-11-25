/* Floating Video Stories - página de configurações: prévia ao vivo, limites por unidade e "Restaurar padrão". */
(function () {
    'use strict';

    var cfg = window.fvsSettings;
    if (!cfg) {
        return;
    }

    var OPTION = 'fvs_settings';

    // Tela de referência de cada prévia (px reais) e proporção do X igual à do front.
    var REF = {
        desktop: { width: 1440, close: 1 / 3, offset: 1 / 9 },
        mobile: { width: 390, close: 7 / 27, offset: 2 / 27 }
    };

    function field(name) {
        return document.querySelector('[name="' + OPTION + '[' + name + ']"]');
    }

    function radioValue(name) {
        var checked = document.querySelector('[name="' + OPTION + '[' + name + ']"]:checked');
        return checked ? checked.value : cfg.defaults[name];
    }

    function num(name) {
        var el = field(name);
        var value = el ? parseFloat(el.value) : NaN;
        return isNaN(value) ? cfg.defaults[name] : value;
    }

    // Converte um valor das configurações para px da prévia.
    function toPreview(value, unit, screen, device) {
        if (unit === '%' || unit === '%w') {
            return value / 100 * screen.width;
        }
        if (unit === '%h') {
            return value / 100 * screen.height;
        }
        return value * screen.width / REF[device].width;
    }

    function clampToUnit(input, unit) {
        var limits = cfg.limits[input.getAttribute('data-fvs-field')];
        if (!limits || !limits[unit]) {
            return;
        }
        input.min = limits[unit][0];
        input.max = limits[unit][1];
        var value = parseFloat(input.value);
        if (!isNaN(value)) {
            input.value = Math.min(limits[unit][1], Math.max(limits[unit][0], value));
        }
    }

    function updatePreview(section) {
        var device = section.getAttribute('data-fvs-device');
        var screenEl = section.querySelector('.fvs-screen');
        var bubble = section.querySelector('.fvs-preview-bubble');
        var close = section.querySelector('.fvs-preview-close');
        if (!screenEl || !bubble) {
            return;
        }

        var screen = { width: screenEl.clientWidth, height: screenEl.clientHeight };
        var p = device + '_';
        var side = radioValue(p + 'side');
        var anchor = radioValue(p + 'anchor');
        var size = toPreview(num(p + 'size'), field(p + 'size_unit').value, screen, device);
        var x = toPreview(num(p + 'offset_x'), field(p + 'offset_x_unit').value, screen, device);
        var y = toPreview(num(p + 'offset_y'), field(p + 'offset_y_unit').value, screen, device);

        bubble.style.width = bubble.style.height = size + 'px';
        bubble.style.left = side === 'left' ? x + 'px' : 'auto';
        bubble.style.right = side === 'right' ? x + 'px' : 'auto';
        bubble.style.top = anchor === 'top' ? y + 'px' : 'auto';
        bubble.style.bottom = anchor === 'bottom' ? y + 'px' : 'auto';

        // X com tamanho mínimo de 24px reais, convertido para a escala da prévia.
        var scale = screen.width / REF[device].width;
        var closeSize = Math.max(24 * scale, size * REF[device].close);
        var closeOffset = -size * REF[device].offset;
        close.style.width = close.style.height = closeSize + 'px';
        close.style.top = closeOffset + 'px';
        close.style.left = side === 'left' ? closeOffset + 'px' : 'auto';
        close.style.right = side === 'right' ? closeOffset + 'px' : 'auto';
    }

    function updateMobileNote() {
        var note = document.querySelector('.fvs-mobile-until');
        var input = field('breakpoint');
        if (note && input && cfg.mobileUntil) {
            note.textContent = cfg.mobileUntil.replace('%s', input.value || cfg.defaults.breakpoint);
        }
    }

    function resetDevice(section) {
        var device = section.getAttribute('data-fvs-device');
        Object.keys(cfg.defaults).forEach(function (key) {
            if (key.indexOf(device + '_') !== 0) {
                return;
            }
            var els = document.querySelectorAll('[name="' + OPTION + '[' + key + ']"]');
            Array.prototype.forEach.call(els, function (el) {
                if (el.type === 'radio') {
                    el.checked = el.value === String(cfg.defaults[key]);
                } else {
                    el.value = cfg.defaults[key];
                }
            });
        });
        // Ajusta os limites dos números à unidade restaurada.
        Array.prototype.forEach.call(section.querySelectorAll('[data-fvs-unit-for]'), function (select) {
            var input = select.parentNode.querySelector('input[type="number"]');
            if (input) {
                clampToUnit(input, select.value);
            }
        });
        updatePreview(section);
    }

    var sections = document.querySelectorAll('.fvs-device');

    Array.prototype.forEach.call(sections, function (section) {
        section.addEventListener('input', function () {
            updatePreview(section);
        });
        section.addEventListener('change', function (e) {
            var target = e.target;
            if (target.hasAttribute('data-fvs-unit-for')) {
                var input = target.parentNode.querySelector('input[type="number"]');
                if (input) {
                    clampToUnit(input, target.value);
                }
            }
            updatePreview(section);
        });
        var reset = section.querySelector('.fvs-reset');
        if (reset) {
            reset.addEventListener('click', function () {
                resetDevice(section);
            });
        }
        updatePreview(section);
    });

    var breakpoint = field('breakpoint');
    if (breakpoint) {
        breakpoint.addEventListener('input', updateMobileNote);
    }

    window.addEventListener('resize', function () {
        Array.prototype.forEach.call(sections, updatePreview);
    });
})();
