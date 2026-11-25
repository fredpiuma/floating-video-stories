<?php

/**
 * Floating Video Stories - Componente de administração (lista de stories)
 * Usado na aba do produto e na meta box da página.
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Tipos de post que têm o campo de stories no admin.
 *
 * @return string[]
 */
function fvs_admin_post_types()
{
    return array('product', 'page');
}

// === Assets do admin ===
add_action('admin_enqueue_scripts', 'fvs_admin_enqueue_assets');
function fvs_admin_enqueue_assets($hook)
{
    if ('post.php' !== $hook && 'post-new.php' !== $hook) {
        return;
    }
    $screen = get_current_screen();
    if (!$screen || !in_array($screen->post_type, fvs_admin_post_types(), true)) {
        return;
    }

    wp_enqueue_media();
    wp_enqueue_style('fvs-admin', FVS_URL . 'assets/css/fvs-admin.css', array(), FVS_VERSION);
    wp_enqueue_script('fvs-admin', FVS_URL . 'assets/js/fvs-admin.js', array('jquery', 'jquery-ui-sortable'), FVS_VERSION, true);
    wp_localize_script('fvs-admin', 'fvsAdmin', array(
        'max'          => FVS_MAX_STORIES,
        'frameTitle'   => __('Selecionar vídeos (somente MP4)', 'floating-video-stories'),
        'frameButton'  => __('Adicionar à lista', 'floating-video-stories'),
        'thumbTitle'   => __('Selecionar miniatura da bolha (MP4)', 'floating-video-stories'),
        'thumbButton'  => __('Usar este vídeo', 'floating-video-stories'),
        'remove'       => __('Remover', 'floating-video-stories'),
        'dragLabel'    => __('Arrastar para reordenar', 'floating-video-stories'),
        /* translators: %s: lista de nomes de arquivo */
        'notMp4'       => __('Somente arquivos MP4 são aceitos. Ignorados: %s', 'floating-video-stories'),
        /* translators: %d: número máximo de vídeos */
        'tooMany'      => __('Limite de %d vídeos atingido. Os vídeos excedentes foram ignorados.', 'floating-video-stories'),
    ));
}

/**
 * Itens para o admin: inclui o nome do arquivo e tenta achar o id do anexo
 * nos itens migrados só com URL.
 *
 * @param int $post_id
 * @return array<int,array{id:int,url:string,name:string}>
 */
function fvs_admin_items($post_id)
{
    $items = array();
    foreach (fvs_get_saved_stories($post_id) as $story) {
        $id = $story['id'] ? $story['id'] : attachment_url_to_postid($story['url']);
        $file = $id ? get_attached_file($id) : '';
        $items[] = array(
            'id'   => (int) $id,
            'url'  => $story['url'],
            'name' => wp_basename($file ? $file : (string) wp_parse_url($story['url'], PHP_URL_PATH)),
        );
    }
    return $items;
}

/**
 * Renderiza o campo de stories (miniatura da bolha + lista ordenável).
 *
 * @param int $post_id
 */
function fvs_render_stories_field($post_id)
{
    $items = fvs_admin_items($post_id);
    $thumb = fvs_get_bubble_thumb($post_id);
    $json  = wp_json_encode(array_map(function ($item) {
        return array('id' => $item['id'], 'url' => $item['url']);
    }, $items));

    wp_nonce_field('fvs_save_stories', 'fvs_stories_nonce');
    ?>
    <div class="fvs-field" data-items="<?php echo esc_attr(wp_json_encode($items)); ?>">
        <div class="fvs-field-row">
            <label for="fvs_bubble_thumb"><strong><?php esc_html_e('Miniatura da bolha (MP4, opcional)', 'floating-video-stories'); ?></strong></label>
            <div class="fvs-thumb-controls">
                <input type="text" name="fvs_bubble_thumb" id="fvs_bubble_thumb" class="fvs-thumb-input" value="<?php echo esc_attr($thumb); ?>" placeholder="<?php esc_attr_e('Selecione ou envie um vídeo MP4', 'floating-video-stories'); ?>" />
                <button type="button" class="button fvs-thumb-select"><?php esc_html_e('Enviar/Selecionar', 'floating-video-stories'); ?></button>
                <button type="button" class="button-link fvs-thumb-clear"><?php esc_html_e('Limpar', 'floating-video-stories'); ?></button>
            </div>
            <p class="description"><?php esc_html_e('Se vazio, a bolha usa o primeiro vídeo.', 'floating-video-stories'); ?></p>
        </div>

        <div class="fvs-field-row">
            <strong><?php esc_html_e('Vídeos (stories)', 'floating-video-stories'); ?></strong>
            <p class="description">
                <?php
                /* translators: %d: número máximo de vídeos */
                printf(esc_html__('Arraste para definir a ordem de exibição. Até %d vídeos MP4.', 'floating-video-stories'), (int) FVS_MAX_STORIES);
                ?>
            </p>
            <div class="fvs-notice notice notice-warning inline" hidden><p></p></div>
            <ul class="fvs-stories-list"></ul>
            <p class="fvs-empty"<?php echo $items ? ' hidden' : ''; ?>><?php esc_html_e('Nenhum vídeo adicionado.', 'floating-video-stories'); ?></p>
            <button type="button" class="button button-secondary fvs-add"><?php esc_html_e('Adicionar vídeos', 'floating-video-stories'); ?></button>
            <input type="hidden" name="fvs_stories" class="fvs-stories-input" value="<?php echo esc_attr($json); ?>" />
        </div>
    </div>
    <?php
}

/**
 * Lê e valida os dados enviados pelo formulário.
 * Retorna null quando o formulário não veio, o nonce é inválido ou o usuário não pode editar o post.
 *
 * @param int $post_id
 * @return array{stories:array,thumb:string}|null
 */
function fvs_read_stories_request($post_id)
{
    if ((defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) || wp_is_post_revision($post_id) || wp_is_post_autosave($post_id)) {
        return null;
    }
    if (!isset($_POST['fvs_stories_nonce'], $_POST['fvs_stories'])) {
        return null;
    }
    if (!wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['fvs_stories_nonce'])), 'fvs_save_stories')) {
        return null;
    }
    if (!current_user_can('edit_post', $post_id)) {
        return null;
    }

    $stories = fvs_sanitize_stories(wp_unslash($_POST['fvs_stories'])); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput -- sanitizado em fvs_sanitize_stories().

    $thumb = isset($_POST['fvs_bubble_thumb']) ? esc_url_raw(trim(wp_unslash($_POST['fvs_bubble_thumb']))) : '';
    if ('' !== $thumb && !fvs_is_mp4_url($thumb)) {
        $thumb = '';
    }

    return array('stories' => $stories, 'thumb' => $thumb);
}

/**
 * Grava as metas novas. Não toca nas metas legadas.
 *
 * @param int             $post_id
 * @param array           $data    Retorno de fvs_read_stories_request().
 * @param WC_Product|null $product Quando informado, grava pelo objeto do produto.
 */
function fvs_save_stories_meta($post_id, array $data, $product = null)
{
    $set = function ($key, $value) use ($post_id, $product) {
        if ($product) {
            $product->update_meta_data($key, $value);
        } else {
            update_post_meta($post_id, $key, $value);
        }
    };
    $del = function ($key) use ($post_id, $product) {
        if ($product) {
            $product->delete_meta_data($key);
        } else {
            delete_post_meta($post_id, $key);
        }
    };

    if ($data['stories']) {
        $set('_fvs_stories', $data['stories']);
        $del('_fvs_stories_cleared');
    } else {
        // O marcador impede que o fallback legado traga o vídeo antigo de volta.
        $del('_fvs_stories');
        $set('_fvs_stories_cleared', 1);
    }

    if ('' !== $data['thumb']) {
        $set('_fvs_bubble_thumb', $data['thumb']);
    } else {
        $del('_fvs_bubble_thumb');
    }
}
