<?php

/**
 * Fred Video Produto - Funcionalidade para Páginas
 * Meta box "Vídeos da Página" nas páginas estáticas
 */

if (!defined('ABSPATH')) {
    exit;
}

// === Meta box de vídeos nas páginas ===
add_action('add_meta_boxes_page', 'fvp_add_page_video_meta_box');
function fvp_add_page_video_meta_box()
{
    add_meta_box(
        'fvp_page_video_meta_box',
        __('Vídeos da Página', 'floating-video-stories'),
        'fvp_page_video_meta_box_callback',
        'page',
        'normal',
        'high'
    );
}

function fvp_page_video_meta_box_callback($post)
{
    fvs_render_stories_field($post->ID);
}

// === Salvar metadados da página ===
add_action('save_post_page', 'fvp_save_video_upload_fields_page');
function fvp_save_video_upload_fields_page($post_id)
{
    $data = fvs_read_stories_request($post_id);
    if (null === $data) {
        return;
    }
    fvs_save_stories_meta($post_id, $data);
}

// === FRONT: Bolinha de vídeo + lightbox para Páginas ===
add_action('wp_footer', 'fvp_display_page_video_bubble');
function fvp_display_page_video_bubble()
{
    if (!is_page()) return;

    global $post;
    if (!$post) return;

    $stories = fvs_get_stories($post->ID, 'page');
    if (empty($stories)) return;

    fvs_render_stories_bubble('page', fvs_get_bubble_thumb($post->ID), $stories);
}
