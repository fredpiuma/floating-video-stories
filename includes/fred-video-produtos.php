<?php

/**
 * Fred Video Produto - Funcionalidade para Produtos
 * Aba "Vídeos (stories)" nos dados do produto WooCommerce
 */

if (!defined('ABSPATH')) {
    exit;
}

// === Aba própria no painel "Dados do produto" ===
add_filter('woocommerce_product_data_tabs', 'fvs_add_product_data_tab');
function fvs_add_product_data_tab($tabs)
{
    $tabs['fvs_stories'] = array(
        'label'    => __('Vídeos (stories)', 'floating-video-stories'),
        'target'   => 'fvs_stories_panel',
        'class'    => array(),
        'priority' => 75,
    );
    return $tabs;
}

add_action('woocommerce_product_data_panels', 'fvs_render_product_data_panel');
function fvs_render_product_data_panel()
{
    global $post;
    echo '<div id="fvs_stories_panel" class="panel woocommerce_options_panel hidden">';
    fvs_render_stories_field($post->ID);
    echo '</div>';
}

// === Salvar metadados do produto ===
add_action('woocommerce_admin_process_product_object', 'fvs_save_product_stories');
function fvs_save_product_stories($product)
{
    $data = fvs_read_stories_request($product->get_id());
    if (null === $data) {
        return;
    }
    fvs_save_stories_meta($product->get_id(), $data, $product);
}

// === FRONT: Bolinha de vídeo + lightbox para Produtos ===
add_action('wp_footer', 'fvp_display_product_video_bubble');
function fvp_display_product_video_bubble()
{
    if (!is_product()) return;

    global $product;
    if (!$product || !is_a($product, 'WC_Product')) return;

    $stories = fvs_get_stories($product->get_id(), 'product');
    if (empty($stories)) return;

    fvs_render_stories_bubble('product', fvs_get_bubble_thumb($product->get_id()), $stories, $product->get_id());
}
