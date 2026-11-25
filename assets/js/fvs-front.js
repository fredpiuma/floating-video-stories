/**
 * Floating Video Stories - player no estilo stories.
 * Uma instância de FvsStories por .fvs-bubble-wrapper da página. Sem jQuery.
 */
(function () {
    'use strict';

    var i18n = window.fvsFront || {};
    var HOLD_MS = 200;

    function FvsStories(wrapper) {
        var stories;
        try {
            stories = JSON.parse(wrapper.getAttribute('data-fvs-stories') || '[]');
        } catch (e) {
            stories = [];
        }
        this.stories = stories.filter(function (s) { return s && s.url; });
        this.wrapper = wrapper;
        this.overlay = document.getElementById(wrapper.getAttribute('data-fvs-overlay'));
        if (!this.stories.length || !this.overlay) {
            return;
        }

        this.bubble = wrapper.querySelector('.fvs-bubble');
        this.bubbleClose = wrapper.querySelector('.fvs-bubble-close');
        this.player = this.overlay.querySelector('.fvs-player');
        this.stage = this.overlay.querySelector('.fvs-stage');
        this.video = this.overlay.querySelector('.fvs-video');
        this.fills = this.overlay.querySelectorAll('.fvs-bar-fill');
        this.bars = this.overlay.querySelectorAll('.fvs-bar');
        this.soundBtn = this.overlay.querySelector('.fvs-sound');
        this.prevBtn = this.overlay.querySelector('.fvs-nav--prev');
        this.nextBtn = this.overlay.querySelector('.fvs-nav--next');
        this.closeBtn = this.overlay.querySelector('.fvs-overlay-close');

        this.index = 0;
        this.isOpen = false;
        this.muted = false;
        this.raf = 0;
        this.holdTimer = 0;
        this.held = false;
        this.pointerActive = false;
        this.pausedByVisibility = false;
        this.preloader = null;

        this.video.loop = false;
        this.video.setAttribute('playsinline', '');
        this.tick = this.tick.bind(this);

        this.bindEvents();
        this.watchKeyboard();
    }

    FvsStories.prototype.bindEvents = function () {
        var self = this;

        this.bubble.addEventListener('click', function () { self.open(); });
        this.bubble.addEventListener('keydown', function (e) {
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                self.open();
            }
        });

        if (this.bubbleClose) {
            this.bubbleClose.addEventListener('click', function (e) {
                e.stopPropagation();
                self.wrapper.style.display = 'none';
            });
        }

        this.closeBtn.addEventListener('click', function (e) {
            e.stopPropagation();
            self.close();
        });

        // Clique fora do vídeo (fundo do overlay ou área vazia do player) fecha.
        this.overlay.addEventListener('click', function (e) {
            if (e.target === self.overlay || e.target === self.player) {
                self.close();
            }
        });

        this.prevBtn.addEventListener('click', function (e) {
            e.stopPropagation();
            self.prev();
        });
        this.nextBtn.addEventListener('click', function (e) {
            e.stopPropagation();
            self.next();
        });

        this.soundBtn.addEventListener('click', function (e) {
            e.stopPropagation();
            self.setMuted(!self.muted);
            if (!self.muted && self.video.paused && !self.held) {
                self.play();
            }
        });

        this.video.addEventListener('ended', function () {
            if (self.isOpen) self.next();
        });
        this.video.addEventListener('error', function () {
            if (!self.isOpen || !self.video.getAttribute('src')) return;
            console.warn('[Floating Video Stories] Não foi possível carregar o vídeo, pulando:', self.stories[self.index].url);
            self.next();
        });
        this.video.addEventListener('loadedmetadata', function () {
            var w = self.video.videoWidth, h = self.video.videoHeight;
            if (w && h) {
                self.player.style.setProperty('--fvs-ratio', String(w / h));
            }
        });
        this.video.addEventListener('playing', function () { self.preloadNext(); });

        // Toque/clique no vídeo: lado esquerdo volta, direito avança; segurar pausa.
        this.stage.addEventListener('pointerdown', function (e) {
            if (e.button !== 0 || e.target.closest('button')) return;
            self.pointerActive = true;
            self.held = false;
            clearTimeout(self.holdTimer);
            self.holdTimer = setTimeout(function () {
                self.held = true;
                self.video.pause();
            }, HOLD_MS);
        });
        this.stage.addEventListener('pointerup', function (e) {
            if (!self.pointerActive) return;
            self.pointerActive = false;
            clearTimeout(self.holdTimer);
            if (self.held) {
                self.held = false;
                self.play();
                return;
            }
            var rect = self.stage.getBoundingClientRect();
            if (e.clientX - rect.left < rect.width / 2) {
                self.prev();
            } else {
                self.next();
            }
        });
        var cancelHold = function () {
            if (!self.pointerActive) return;
            self.pointerActive = false;
            clearTimeout(self.holdTimer);
            if (self.held) {
                self.held = false;
                if (self.isOpen) self.play();
            }
        };
        this.stage.addEventListener('pointercancel', cancelHold);
        this.stage.addEventListener('pointerleave', cancelHold);
        this.stage.addEventListener('contextmenu', function (e) { e.preventDefault(); });

        document.addEventListener('keydown', function (e) {
            if (!self.isOpen) return;
            if (e.key === 'Escape') {
                e.preventDefault();
                self.close();
            } else if (e.key === 'ArrowRight') {
                e.preventDefault();
                self.next();
            } else if (e.key === 'ArrowLeft') {
                e.preventDefault();
                self.prev();
            } else if (e.key === 'Tab') {
                self.trapFocus(e);
            }
        });

        document.addEventListener('visibilitychange', function () {
            if (!self.isOpen) return;
            if (document.hidden) {
                if (!self.video.paused) {
                    self.pausedByVisibility = true;
                    self.video.pause();
                }
            } else if (self.pausedByVisibility) {
                self.pausedByVisibility = false;
                self.play();
            }
        });
    };

    // Esconde a bolha quando o teclado virtual do celular abre.
    FvsStories.prototype.watchKeyboard = function () {
        var wrapper = this.wrapper;
        if (!window.visualViewport) return;
        window.visualViewport.addEventListener('resize', function () {
            var ratio = window.visualViewport.height / window.screen.height;
            wrapper.classList.toggle('fvs-hidden-keyboard', ratio < 0.75);
        });
    };

    FvsStories.prototype.open = function () {
        if (this.isOpen) return;
        this.isOpen = true;
        this.overlay.hidden = false;
        document.documentElement.classList.add('fvs-open');
        this.go(0);
        cancelAnimationFrame(this.raf);
        this.raf = requestAnimationFrame(this.tick);
        this.closeBtn.focus({ preventScroll: true });
    };

    FvsStories.prototype.close = function () {
        if (!this.isOpen) return;
        this.isOpen = false;
        this.held = false;
        this.pointerActive = false;
        this.pausedByVisibility = false;
        clearTimeout(this.holdTimer);
        cancelAnimationFrame(this.raf);

        this.video.pause();
        // Interrompe o download do vídeo atual e do pré-carregado.
        this.video.removeAttribute('src');
        this.video.load();
        this.clearPreloader();

        this.overlay.hidden = true;
        document.documentElement.classList.remove('fvs-open');
        if (this.wrapper.style.display !== 'none') {
            this.bubble.focus({ preventScroll: true });
        }
    };

    FvsStories.prototype.go = function (index) {
        this.index = index;
        var i;
        for (i = 0; i < this.bars.length; i++) {
            this.bars[i].classList.toggle('is-done', i < index);
            this.fills[i].style.transform = i < index ? 'scaleX(1)' : 'scaleX(0)';
        }
        this.prevBtn.classList.toggle('is-disabled', index === 0);
        this.prevBtn.setAttribute('aria-disabled', index === 0 ? 'true' : 'false');

        this.video.src = this.stories[index].url;
        this.video.currentTime = 0;
        this.video.muted = this.muted;
        this.play();
    };

    FvsStories.prototype.next = function () {
        if (this.index < this.stories.length - 1) {
            this.go(this.index + 1);
        } else {
            this.close();
        }
    };

    FvsStories.prototype.prev = function () {
        if (this.index > 0) {
            this.go(this.index - 1);
        } else {
            this.video.currentTime = 0;
            this.play();
        }
    };

    // Tenta tocar com som; se o navegador bloquear, toca mudo e mostra o botão de som como mudo.
    FvsStories.prototype.play = function () {
        var self = this;
        if (!this.isOpen) return;
        this.video.muted = this.muted;
        var p = this.video.play();
        if (p && typeof p.catch === 'function') {
            p.catch(function (err) {
                if (!self.isOpen) return;
                if (err && err.name === 'NotAllowedError' && !self.video.muted) {
                    self.setMuted(true);
                    self.video.play().catch(function () {});
                } else if (err && err.name !== 'AbortError' && err.name !== 'NotSupportedError') {
                    // AbortError: troca de vídeo no meio do play(). NotSupportedError: tratado no evento 'error'.
                    console.warn('[Floating Video Stories]', err);
                }
            });
        }
    };

    FvsStories.prototype.setMuted = function (muted) {
        this.muted = muted;
        this.video.muted = muted;
        this.soundBtn.classList.toggle('is-muted', muted);
        this.soundBtn.setAttribute('aria-label', muted ? (i18n.unmute || 'Ativar som') : (i18n.mute || 'Desativar som'));
    };

    // Barra atual acompanha currentTime / duration.
    FvsStories.prototype.tick = function () {
        if (!this.isOpen) return;
        var d = this.video.duration;
        var r = d && isFinite(d) ? Math.min(this.video.currentTime / d, 1) : 0;
        var fill = this.fills[this.index];
        if (fill) {
            fill.style.transform = 'scaleX(' + r + ')';
        }
        this.raf = requestAnimationFrame(this.tick);
    };

    FvsStories.prototype.preloadNext = function () {
        var next = this.stories[this.index + 1];
        if (!next) return;
        if (!this.preloader) {
            this.preloader = document.createElement('video');
            this.preloader.className = 'fvs-preloader';
            this.preloader.muted = true;
            this.preloader.preload = 'auto';
            this.preloader.setAttribute('playsinline', '');
            this.preloader.setAttribute('aria-hidden', 'true');
            this.overlay.appendChild(this.preloader);
        }
        if (this.preloader.getAttribute('src') !== next.url) {
            this.preloader.src = next.url;
        }
    };

    FvsStories.prototype.clearPreloader = function () {
        if (!this.preloader) return;
        this.preloader.removeAttribute('src');
        this.preloader.load();
    };

    FvsStories.prototype.trapFocus = function (e) {
        var focusables = Array.prototype.filter.call(
            this.overlay.querySelectorAll('button'),
            function (el) { return el.offsetParent !== null; }
        );
        if (!focusables.length) return;
        var first = focusables[0];
        var last = focusables[focusables.length - 1];
        if (e.shiftKey && (document.activeElement === first || !this.overlay.contains(document.activeElement))) {
            e.preventDefault();
            last.focus();
        } else if (!e.shiftKey && (document.activeElement === last || !this.overlay.contains(document.activeElement))) {
            e.preventDefault();
            first.focus();
        }
    };

    function init() {
        var wrappers = document.querySelectorAll('.fvs-bubble-wrapper');
        for (var i = 0; i < wrappers.length; i++) {
            if (!wrappers[i].fvsStories) {
                wrappers[i].fvsStories = new FvsStories(wrappers[i]);
            }
        }
    }

    window.FvsStories = FvsStories;

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
