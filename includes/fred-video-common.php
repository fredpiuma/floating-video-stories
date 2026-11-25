<?php

/**
 * Fred Video Produto - Funcionalidades Comuns
 * Funções compartilhadas entre produtos e páginas
 */

if (!defined('ABSPATH')) {
    exit;
}

/** Número máximo de stories por post. */
const FVS_MAX_STORIES = 20;

/**
 * Metas legadas (1.3.x) por tipo de post.
 *
 * @param string $post_type
 * @return array{video:string,thumb:string}|null
 */
function fvs_legacy_meta_keys($post_type)
{
    $keys = array(
        'product' => array('video' => '_fred_product_video', 'thumb' => '_fred_video_thumbnail'),
        'page'    => array('video' => '_fred_page_video', 'thumb' => '_fred_page_video_thumbnail'),
    );
    return isset($keys[$post_type]) ? $keys[$post_type] : null;
}

/**
 * Indica se a URL aponta para um arquivo .mp4 (ignora query string e fragmento).
 *
 * @param string $url
 * @return bool
 */
function fvs_is_mp4_url($url)
{
    $path = wp_parse_url($url, PHP_URL_PATH);
    return is_string($path) && '.mp4' === strtolower(substr($path, -4));
}

/**
 * Normaliza uma lista de stories: aceita array ou JSON, limita a FVS_MAX_STORIES,
 * aplica absint no id, esc_url_raw na url e descarta URLs que não sejam .mp4.
 *
 * @param mixed $raw
 * @return array<int,array{id:int,url:string}>
 */
function fvs_sanitize_stories($raw)
{
    if (is_string($raw)) {
        $raw = json_decode($raw, true);
    }
    if (!is_array($raw)) {
        return array();
    }

    $stories = array();
    foreach ($raw as $item) {
        if (!is_array($item) || empty($item['url']) || !is_string($item['url'])) {
            continue;
        }
        $url = esc_url_raw(trim($item['url']));
        if ('' === $url || !fvs_is_mp4_url($url)) {
            continue;
        }
        $stories[] = array(
            'id'  => isset($item['id']) ? absint($item['id']) : 0,
            'url' => $url,
        );
        if (count($stories) >= FVS_MAX_STORIES) {
            break;
        }
    }
    return $stories;
}

/**
 * O post já foi salvo no formato de stories (1.4.0+)?
 *
 * @param int $post_id
 * @return bool
 */
function fvs_uses_new_format($post_id)
{
    return metadata_exists('post', $post_id, '_fvs_stories')
        || metadata_exists('post', $post_id, '_fvs_stories_cleared');
}

/**
 * Lista de stories de um post, em ordem de exibição.
 * Sem a meta nova, cai nas metas legadas (migração preguiçosa).
 *
 * @param int    $post_id
 * @param string $context Contexto de exibição (padrão: tipo do post).
 * @return array<int,array{id:int,url:string}>
 */
function fvs_get_stories($post_id, $context = '')
{
    $post_id = absint($post_id);
    if (!$post_id) {
        return array();
    }
    if ('' === $context) {
        $context = (string) get_post_type($post_id);
    }

    return (array) apply_filters('fvs_stories', fvs_get_saved_stories($post_id), $post_id, $context);
}

/**
 * Stories gravados no post (com fallback legado), sem passar pelo filtro `fvs_stories`.
 * É o que o admin mostra e salva.
 *
 * @param int $post_id
 * @return array<int,array{id:int,url:string}>
 */
function fvs_get_saved_stories($post_id)
{
    $post_id   = absint($post_id);
    $post_type = get_post_type($post_id);

    $stories = fvs_sanitize_stories(get_post_meta($post_id, '_fvs_stories', true));

    if (empty($stories) && !fvs_uses_new_format($post_id)) {
        $legacy = fvs_legacy_meta_keys($post_type);
        if ($legacy) {
            $url = esc_url_raw((string) get_post_meta($post_id, $legacy['video'], true));
            if ('' !== $url) {
                $stories = array(array('id' => 0, 'url' => $url));
            }
        }
    }

    return $stories;
}

/**
 * URL configurada para a miniatura da bolha (pode ser vazia).
 * Sem o formato novo, usa a miniatura legada.
 *
 * @param int $post_id
 * @return string
 */
function fvs_get_bubble_thumb($post_id)
{
    $post_id = absint($post_id);
    if (!$post_id) {
        return '';
    }
    if (fvs_uses_new_format($post_id)) {
        return esc_url_raw((string) get_post_meta($post_id, '_fvs_bubble_thumb', true));
    }
    $legacy = fvs_legacy_meta_keys(get_post_type($post_id));
    return $legacy ? esc_url_raw((string) get_post_meta($post_id, $legacy['thumb'], true)) : '';
}

// Variável para controlar se o CSS já foi renderizado
$fvp_styles_rendered = false;

/**
 * Renderiza a bolha de vídeo com lightbox
 *
 * @param string $type Tipo de conteúdo: 'product' ou 'page'
 * @param string $thumb_url URL do vídeo de thumbnail
 * @param string $video_url URL do vídeo principal
 */
function fvp_render_video_bubble($type, $thumb_url, $video_url)
{
    global $fvp_styles_rendered;

    // Gerar IDs únicos baseados no tipo
    $wrapper_id = 'fvp-video-bubble-wrapper-' . $type;
    $bubble_id = 'fvp-video-bubble-' . $type;
    $close_btn_id = 'fvp-video-close-btn-' . $type;
    $overlay_id = 'fvp-video-overlay-' . $type;
    $main_vid_id = 'fvp-main-video-' . $type;
    $overlay_close_btn_id = 'fvp-overlay-close-btn-' . $type;
    ?>

    <?php if (!$fvp_styles_rendered) : ?>
        <style>
            .fvp-video-bubble-wrapper {
                position: fixed;
                bottom: 30vh;
                left: 20px;
                z-index: 9999;
                transition: transform .4s ease;
            }

            .fvp-video-bubble-wrapper.fvp-hidden-keyboard {
                transform: translateX(calc(-100% - 30px));
            }

            .fvp-video-bubble {
                position: relative;
                width: 90px;
                height: 90px;
                border-radius: 50%;
                overflow: hidden;
                cursor: pointer;
                box-shadow: 0 0 10px rgba(0, 0, 0, .3);
                transition: transform .3s ease;
            }

            .fvp-video-bubble:hover {
                transform: scale(1.08);
            }

            .fvp-video-bubble video {
                width: 100%;
                height: 100%;
                object-fit: cover;
            }

            .fvp-video-close-btn {
                position: absolute !important;
                top: 0 !important;
                left: 0 !important;
                margin-left: -10px !important;
                margin-top: -10px !important;
                width: 30px !important;
                height: 30px !important;
                background-color: white !important;
                color: black !important;
                border-radius: 50% !important;
                border: none !important;
                cursor: pointer !important;
                font-size: 18px !important;
                font-weight: bold !important;
                line-height: 1 !important;
                z-index: 10000 !important;
                box-shadow: 0 2px 5px rgba(0, 0, 0, 0.3) !important;
                transition: background-color 0.2s !important;
                display: block !important;
                padding: 0 !important;
                min-height: auto !important;
                min-width: auto !important;
            }

            .fvp-video-close-btn>* {
                width: 12px;
                height: 12px;
            }

            .fvp-video-close-btn:hover {
                background-color: #eee;
            }

            .fvp-video-overlay .fvp-overlay-close-btn {
                position: absolute;
                top: 3vh;
                right: 3vh;
                width: 44px;
                height: 44px;
                background-color: white;
                color: black;
                border-radius: 50%;
                border: none;
                cursor: pointer;
                display: flex;
                justify-content: center;
                align-items: center;
                font-size: 20px;
                font-weight: bold;
                z-index: 90001;
                box-shadow: 0 2px 6px rgba(0, 0, 0, 0.4);
                padding: 0;
            }

            .fvp-video-overlay .fvp-overlay-close-btn:hover {
                background-color: #eee;
            }

            .fvp-video-overlay {
                display: none;
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: rgba(0, 0, 0, 0.85);
                justify-content: center;
                align-items: center;
                z-index: 100000;
            }

            .fvp-video-overlay.active {
                display: flex;
            }

            .fvp-video-overlay video {
                width: auto;
                height: auto;
                max-width: 90vw;
                max-height: 90vh;
                border-radius: 12px;
                display: block;
                box-shadow: 0 0 25px rgba(0, 0, 0, 0.6);
            }

            @media (max-width: 640px) {
                .fvp-video-bubble-wrapper {
                    bottom: 30vw;
                    left: 3vw;
                    top: auto;
                }

                .fvp-video-bubble {
                    width: 27vw;
                    height: 27vw;
                }

                .fvp-video-close-btn {
                    width: 7vw;
                    height: 7vw;
                    font-size: 5vw;
                    margin-left: -2vw;
                    margin-top: -2vw;
                }

                .fvp-video-overlay video {
                    max-width: 95vw;
                    max-height: 80vh;
                }

                .fvp-video-close-btn>* {
                    width: 3vw;
                    height: 3vw;
                }
            }
        </style>
        <?php $fvp_styles_rendered = true; ?>
    <?php endif; ?>

    <div class="fvp-video-bubble-wrapper" id="<?php echo esc_attr($wrapper_id); ?>">
        <div class="fvp-video-bubble" id="<?php echo esc_attr($bubble_id); ?>">
            <video autoplay muted loop playsinline>
                <source src="<?php echo esc_url($thumb_url); ?>" type="video/mp4">
            </video>
        </div>
        <button class="fvp-video-close-btn" id="<?php echo esc_attr($close_btn_id); ?>" aria-label="Fechar bolha de vídeo">
            <svg xmlns="http://www.w3.org/2000/svg" version="1.1" viewBox="0 0 16.3 16.3">
                <path d="M1.2,16.3c-.3,0-.6-.1-.8-.3-.5-.5-.5-1.2,0-1.7L14.3.3c.5-.5,1.2-.5,1.7,0,.5.5.5,1.2,0,1.7L2,16c-.2.2-.5.3-.8.3Z" />
                <path d="M15.2,16.3c-.3,0-.6-.1-.8-.3L.3,2C-.1,1.5-.1.8.3.3.8-.1,1.5-.1,2,.3l14,14c.5.5.5,1.2,0,1.7-.2.2-.5.3-.8.3Z" />
            </svg>
        </button>
    </div>

    <div class="fvp-video-overlay" id="<?php echo esc_attr($overlay_id); ?>" aria-hidden="true">
        <button class="fvp-overlay-close-btn" id="<?php echo esc_attr($overlay_close_btn_id); ?>" aria-label="Fechar vídeo">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="16" height="16" fill="currentColor" aria-hidden="true">
                <path d="M18.3 5.71a1 1 0 0 0-1.41 0L12 10.59 7.11 5.7A1 1 0 0 0 5.7 7.11L10.59 12l-4.89 4.89a1 1 0 1 0 1.41 1.41L12 13.41l4.89 4.89a1 1 0 0 0 1.41-1.41L13.41 12l4.89-4.89a1 1 0 0 0 0-1.4z" />
            </svg>
        </button>
        <video id="<?php echo esc_attr($main_vid_id); ?>" autoplay muted playsinline loop>
            <source src="<?php echo esc_url($video_url); ?>" type="video/mp4">
        </video>
    </div>

    <script>
        (function() {
            const wrapper = document.getElementById('<?php echo esc_js($wrapper_id); ?>');
            const bubble = document.getElementById('<?php echo esc_js($bubble_id); ?>');
            const closeBtn = document.getElementById('<?php echo esc_js($close_btn_id); ?>');
            const overlay = document.getElementById('<?php echo esc_js($overlay_id); ?>');
            const mainVid = document.getElementById('<?php echo esc_js($main_vid_id); ?>');
            const overlayCloseBtn = document.getElementById('<?php echo esc_js($overlay_close_btn_id); ?>');

            if (!wrapper || !bubble || !closeBtn || !overlay || !mainVid) return;

            // Função para fechar a bolha
            const closeBubble = () => {
                wrapper.style.display = 'none';
            };

            // Ao clicar na bolinha, abre o lightbox e inicia o vídeo automaticamente
            bubble.addEventListener('click', () => {
                overlay.classList.add('active');
                overlay.setAttribute('aria-hidden', 'false');
                mainVid.muted = true;
                mainVid.currentTime = 0;
                mainVid.play().catch(err => console.warn('AutoPlay bloqueado:', err));
            });

            // Ao clicar no botão de fechar, esconde o wrapper
            closeBtn.addEventListener('click', (e) => {
                e.stopPropagation();
                closeBubble();
            });

            // Fechar o overlay com o botão de fechar interno
            if (overlayCloseBtn) {
                overlayCloseBtn.addEventListener('click', (e) => {
                    e.stopPropagation();
                    overlay.classList.remove('active');
                    overlay.setAttribute('aria-hidden', 'true');
                    try {
                        mainVid.pause();
                        mainVid.currentTime = 0;
                    } catch (err) {}
                });
            }

            // Fecha o lightbox ao clicar fora do vídeo
            overlay.addEventListener('click', (e) => {
                if (e.target === overlay) {
                    overlay.classList.remove('active');
                    overlay.setAttribute('aria-hidden', 'true');
                    mainVid.pause();
                }
            });

            // Fecha com tecla ESC
            document.addEventListener('keydown', (e) => {
                if (e.key === 'Escape') {
                    if (overlay.classList.contains('active')) {
                        overlay.classList.remove('active');
                        overlay.setAttribute('aria-hidden', 'true');
                        mainVid.pause();
                    }
                }
            });

            // Detecta teclado virtual via visualViewport
            if (window.visualViewport) {
                const threshold = 0.75;
                window.visualViewport.addEventListener('resize', () => {
                    const ratio = window.visualViewport.height / window.screen.height;
                    if (ratio < threshold) {
                        wrapper.classList.add('fvp-hidden-keyboard');
                    } else {
                        wrapper.classList.remove('fvp-hidden-keyboard');
                    }
                });
            }
        })();
    </script>
    <?php
}
