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

/**
 * Post (produto ou página) exibido agora, se ele tiver stories.
 *
 * @return int 0 quando não há bolha para exibir.
 */
function fvs_current_post_with_stories()
{
    $is_product = function_exists('is_product') && is_product();
    if (!$is_product && !is_page()) {
        return 0;
    }
    $post_id = get_queried_object_id();
    return ($post_id && fvs_get_stories($post_id, $is_product ? 'product' : 'page')) ? $post_id : 0;
}

// === Assets do front ===
add_action('wp_enqueue_scripts', 'fvs_register_front_assets');
function fvs_register_front_assets()
{
    wp_register_style('fvs-front', FVS_URL . 'assets/css/fvs-front.css', array(), FVS_VERSION);
    wp_register_script('fvs-front', FVS_URL . 'assets/js/fvs-front.js', array(), FVS_VERSION, array(
        'in_footer' => true,
        'strategy'  => 'defer',
    ));
    wp_localize_script('fvs-front', 'fvsFront', array(
        'mute'   => __('Desativar som', 'floating-video-stories'),
        'unmute' => __('Ativar som', 'floating-video-stories'),
    ));

    // Carrega no <head> quando já se sabe que haverá bolha; evita o carregamento tardio do CSS.
    if (fvs_current_post_with_stories()) {
        fvs_enqueue_front_assets();
    }
}

function fvs_enqueue_front_assets()
{
    if (!wp_style_is('fvs-front', 'registered')) {
        fvs_register_front_assets();
    }
    wp_enqueue_style('fvs-front');
    wp_enqueue_script('fvs-front');
}

/**
 * Renderiza a bolha flutuante e o player stories.
 *
 * @param string $type      Contexto ('product', 'page' ou outro).
 * @param string $thumb_url URL do MP4 da bolha. Vazia: usa o primeiro story.
 * @param array  $stories   Lista de ['id' => int, 'url' => string].
 */
function fvs_render_stories_bubble($type, $thumb_url, array $stories)
{
    static $instance = 0;

    $stories = array_values(array_filter(array_map(function ($story) {
        $url = is_array($story) && !empty($story['url']) ? esc_url_raw($story['url']) : '';
        return $url ? array('id' => isset($story['id']) ? absint($story['id']) : 0, 'url' => $url) : null;
    }, $stories)));
    if (empty($stories)) {
        return;
    }
    if (empty($thumb_url)) {
        $thumb_url = $stories[0]['url'];
    }

    fvs_enqueue_front_assets();

    $instance++;
    $overlay_id = 'fvs-overlay-' . $instance;
    $single     = count($stories) < 2;
    ?>
    <div class="fvs-ui fvs-bubble-wrapper fvs-bubble-wrapper--<?php echo esc_attr(sanitize_html_class($type)); ?>" data-fvs-stories="<?php echo esc_attr(wp_json_encode($stories)); ?>" data-fvs-overlay="<?php echo esc_attr($overlay_id); ?>">
        <div class="fvs-bubble" role="button" tabindex="0" aria-haspopup="dialog" aria-controls="<?php echo esc_attr($overlay_id); ?>" aria-label="<?php esc_attr_e('Abrir vídeos', 'floating-video-stories'); ?>">
            <video autoplay muted loop playsinline preload="metadata" src="<?php echo esc_url($thumb_url); ?>"></video>
        </div>
        <button type="button" class="fvs-btn fvs-bubble-close" aria-label="<?php esc_attr_e('Fechar bolha de vídeo', 'floating-video-stories'); ?>">
            <svg viewBox="0 0 16.3 16.3" aria-hidden="true">
                <path d="M1.2,16.3c-.3,0-.6-.1-.8-.3-.5-.5-.5-1.2,0-1.7L14.3.3c.5-.5,1.2-.5,1.7,0,.5.5.5,1.2,0,1.7L2,16c-.2.2-.5.3-.8.3Z" />
                <path d="M15.2,16.3c-.3,0-.6-.1-.8-.3L.3,2C-.1,1.5-.1.8.3.3.8-.1,1.5-.1,2,.3l14,14c.5.5.5,1.2,0,1.7-.2.2-.5.3-.8.3Z" />
            </svg>
        </button>
    </div>

    <div class="fvs-ui fvs-overlay<?php echo $single ? ' fvs-single' : ''; ?>" id="<?php echo esc_attr($overlay_id); ?>" role="dialog" aria-modal="true" aria-label="<?php esc_attr_e('Vídeos', 'floating-video-stories'); ?>" hidden>
        <button type="button" class="fvs-btn fvs-overlay-close" aria-label="<?php esc_attr_e('Fechar vídeo', 'floating-video-stories'); ?>">
            <svg viewBox="0 0 24 24" aria-hidden="true">
                <path d="M18.3 5.71a1 1 0 0 0-1.41 0L12 10.59 7.11 5.7A1 1 0 0 0 5.7 7.11L10.59 12l-4.89 4.89a1 1 0 1 0 1.41 1.41L12 13.41l4.89 4.89a1 1 0 0 0 1.41-1.41L13.41 12l4.89-4.89a1 1 0 0 0 0-1.4z" />
            </svg>
        </button>
        <div class="fvs-player">
            <button type="button" class="fvs-btn fvs-nav fvs-nav--prev" aria-label="<?php esc_attr_e('Vídeo anterior', 'floating-video-stories'); ?>">
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M15.4 4.6a1.2 1.2 0 0 1 0 1.7L9.7 12l5.7 5.7a1.2 1.2 0 1 1-1.7 1.7l-6.5-6.5a1.2 1.2 0 0 1 0-1.7l6.5-6.5a1.2 1.2 0 0 1 1.7 0z" /></svg>
            </button>
            <div class="fvs-stage">
                <video class="fvs-video" playsinline preload="metadata"></video>
                <div class="fvs-top">
                    <div class="fvs-bars" aria-hidden="true">
                        <?php foreach ($stories as $story) : ?>
                            <span class="fvs-bar"><span class="fvs-bar-fill"></span></span>
                        <?php endforeach; ?>
                    </div>
                    <button type="button" class="fvs-btn fvs-sound" aria-label="<?php esc_attr_e('Desativar som', 'floating-video-stories'); ?>">
                        <svg class="fvs-icon-sound" viewBox="0 0 24 24" aria-hidden="true"><path d="M3 9v6h4l5 5V4L7 9H3zm13.5 3A4.5 4.5 0 0 0 14 8v8a4.5 4.5 0 0 0 2.5-4zM14 3.2v2.1a7 7 0 0 1 0 13.4v2.1a9 9 0 0 0 0-17.6z" /></svg>
                        <svg class="fvs-icon-muted" viewBox="0 0 24 24" aria-hidden="true"><path d="M16.5 12A4.5 4.5 0 0 0 14 8v2.2l2.5 2.5V12zM19 12a7 7 0 0 1-.6 2.8l1.5 1.5A9 9 0 0 0 14 3.2v2.1A7 7 0 0 1 19 12zM4.3 3 3 4.3 7.7 9H3v6h4l5 5v-6.7l4.3 4.3a7 7 0 0 1-2.3 1.2v2.1a9 9 0 0 0 3.7-1.8l2 2 1.3-1.3L4.3 3zM12 4 9.9 6.1 12 8.2V4z" /></svg>
                    </button>
                </div>
            </div>
            <button type="button" class="fvs-btn fvs-nav fvs-nav--next" aria-label="<?php esc_attr_e('Próximo vídeo', 'floating-video-stories'); ?>">
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M8.6 4.6a1.2 1.2 0 0 0 0 1.7l5.7 5.7-5.7 5.7a1.2 1.2 0 1 0 1.7 1.7l6.5-6.5a1.2 1.2 0 0 0 0-1.7l-6.5-6.5a1.2 1.2 0 0 0-1.7 0z" /></svg>
            </button>
        </div>
    </div>
    <?php
}

/**
 * Wrapper legado (1.3.x), mantido para extensões: um vídeo principal vira um story.
 *
 * @param string $type
 * @param string $thumb_url
 * @param string $video_url
 */
function fvp_render_video_bubble($type, $thumb_url, $video_url)
{
    fvs_render_stories_bubble($type, $thumb_url, array(array('id' => 0, 'url' => $video_url)));
}
