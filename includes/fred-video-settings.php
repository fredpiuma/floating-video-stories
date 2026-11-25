<?php

/**
 * Floating Video Stories - Configurações
 * Posição, tamanho e camada da bolha (desktop e mobile).
 */

if (!defined('ABSPATH')) {
    exit;
}

/** Nome da option com as configurações. */
const FVS_SETTINGS_OPTION = 'fvs_settings';

/**
 * Valores padrão: reproduzem o visual da 1.4.x.
 *
 * @return array<string,mixed>
 */
function fvs_default_settings()
{
    return array(
        'desktop_side'          => 'left',
        'desktop_offset_x'      => 20,
        'desktop_offset_x_unit' => 'px',
        'desktop_anchor'        => 'bottom',
        'desktop_offset_y'      => 30,
        'desktop_offset_y_unit' => '%h',
        'desktop_size'          => 90,
        'desktop_size_unit'     => 'px',
        'mobile_side'           => 'left',
        'mobile_offset_x'       => 3,
        'mobile_offset_x_unit'  => '%',
        'mobile_anchor'         => 'bottom',
        'mobile_offset_y'       => 30,
        'mobile_offset_y_unit'  => '%w',
        'mobile_size'           => 27,
        'mobile_size_unit'      => '%',
        'breakpoint'            => 640,
        'z_index'               => 9999,
    );
}

/** @return string[] */
function fvs_settings_devices()
{
    return array('desktop', 'mobile');
}

/**
 * Regras de validação dos campos por dispositivo.
 * `units` mapeia cada unidade aceita para os limites [mín, máx].
 *
 * @return array<string,array>
 */
function fvs_settings_schema()
{
    return array(
        'side'     => array('choices' => array('left', 'right')),
        'anchor'   => array('choices' => array('top', 'bottom')),
        'offset_x' => array('units' => array('px' => array(0, 2000), '%' => array(0, 50))),
        'offset_y' => array('units' => array('px' => array(0, 2000), '%h' => array(0, 90), '%w' => array(0, 90))),
        'size'     => array('units' => array('px' => array(40, 240), '%' => array(10, 50))),
    );
}

/** Limites dos campos globais. */
function fvs_settings_global_limits()
{
    return array(
        'breakpoint' => array(320, 1200),
        'z_index'    => array(1, 2147483000),
    );
}

/**
 * Valida e completa um array de configurações.
 *
 * @param mixed $input
 * @param bool  $changed Recebe true se algum valor foi corrigido ou descartado.
 * @return array<string,mixed> Sempre com todas as chaves de fvs_default_settings().
 */
function fvs_normalize_settings($input, &$changed = false)
{
    $changed  = false;
    $defaults = fvs_default_settings();
    $input    = is_array($input) ? $input : array();
    $out      = array();

    foreach (fvs_settings_devices() as $device) {
        foreach (fvs_settings_schema() as $field => $rule) {
            $key = $device . '_' . $field;

            if (isset($rule['choices'])) {
                $value = isset($input[$key]) ? $input[$key] : $defaults[$key];
                if (!is_string($value) || !in_array($value, $rule['choices'], true)) {
                    $changed = $changed || isset($input[$key]);
                    $value   = $defaults[$key];
                }
                $out[$key] = $value;
                continue;
            }

            $unit_key = $key . '_unit';
            $unit     = isset($input[$unit_key]) ? $input[$unit_key] : $defaults[$unit_key];
            if (!is_string($unit) || !isset($rule['units'][$unit])) {
                $changed = $changed || isset($input[$unit_key]);
                $unit    = $defaults[$unit_key];
            }
            $out[$unit_key] = $unit;

            list($min, $max) = $rule['units'][$unit];
            if (isset($input[$key]) && is_numeric($input[$key])) {
                $value = round((float) $input[$key], 1);
            } else {
                $changed = $changed || isset($input[$key]);
                $value   = ($unit === $defaults[$unit_key]) ? $defaults[$key] : $min;
            }
            $clamped = min($max, max($min, $value));
            if ($clamped != $value) {
                $changed = true;
            }
            $out[$key] = $clamped;
        }
    }

    foreach (fvs_settings_global_limits() as $key => $limits) {
        if (isset($input[$key]) && is_numeric($input[$key]) && (float) $input[$key] >= 0) {
            $value = absint($input[$key]);
        } else {
            $changed = $changed || isset($input[$key]);
            $value   = $defaults[$key];
        }
        $clamped = min($limits[1], max($limits[0], $value));
        if ($clamped !== $value) {
            $changed = true;
        }
        $out[$key] = $clamped;
    }

    if (array_diff_key($input, $out)) {
        $changed = true; // chaves desconhecidas são descartadas
    }

    // Mantém a ordem das chaves dos padrões.
    return array_merge($defaults, $out);
}

/**
 * sanitize_callback da option `fvs_settings`.
 *
 * @param mixed $input
 * @return array<string,mixed>
 */
function fvs_sanitize_settings($input)
{
    $settings = fvs_normalize_settings($input, $changed);

    // O WordPress pode chamar o callback duas vezes no primeiro salvamento; avisa uma vez só.
    static $warned = false;
    if ($changed && !$warned && function_exists('add_settings_error')) {
        $warned = true;
        add_settings_error(
            FVS_SETTINGS_OPTION,
            'fvs_settings_adjusted',
            __('Alguns valores estavam fora dos limites permitidos e foram ajustados automaticamente. Confira os campos abaixo.', 'floating-video-stories'),
            'warning'
        );
    }

    return $settings;
}

/**
 * Configurações salvas, completadas com os padrões.
 *
 * @return array<string,mixed>
 */
function fvs_get_settings()
{
    return fvs_normalize_settings(get_option(FVS_SETTINGS_OPTION, array()));
}

/**
 * Configurações para exibir a bolha num contexto, depois do filtro `fvs_bubble_settings`.
 *
 * @param string $context 'product', 'page' ou outro.
 * @param int    $post_id
 * @return array<string,mixed>
 */
function fvs_get_bubble_settings($context = '', $post_id = 0)
{
    $settings = apply_filters('fvs_bubble_settings', fvs_get_settings(), (string) $context, absint($post_id));
    // O filtro pode devolver qualquer coisa: valida de novo antes de virar CSS.
    return fvs_normalize_settings($settings);
}

add_action('admin_init', 'fvs_register_settings');
function fvs_register_settings()
{
    register_setting('fvs_settings_group', FVS_SETTINGS_OPTION, array(
        'type'              => 'array',
        'sanitize_callback' => 'fvs_sanitize_settings',
        'default'           => fvs_default_settings(),
    ));
}

// === Página de configurações (Configurações › Floating Video Stories) ===

/** Slug da página de configurações. */
const FVS_SETTINGS_PAGE = 'floating-video-stories';

add_action('admin_menu', 'fvs_add_settings_page');
function fvs_add_settings_page()
{
    $hook = add_options_page(
        __('Floating Video Stories', 'floating-video-stories'),
        __('Floating Video Stories', 'floating-video-stories'),
        'manage_options',
        FVS_SETTINGS_PAGE,
        'fvs_render_settings_page'
    );
    if ($hook) {
        add_action('admin_enqueue_scripts', function ($current) use ($hook) {
            if ($current === $hook) {
                fvs_enqueue_settings_assets();
            }
        });
    }
}

add_filter('plugin_action_links_' . plugin_basename(FVS_PATH . 'fred-video-produto.php'), 'fvs_plugin_action_links');
function fvs_plugin_action_links($links)
{
    if (current_user_can('manage_options')) {
        $url = admin_url('options-general.php?page=' . FVS_SETTINGS_PAGE);
        array_unshift($links, '<a href="' . esc_url($url) . '">' . esc_html__('Configurações', 'floating-video-stories') . '</a>');
    }
    return $links;
}

function fvs_enqueue_settings_assets()
{
    wp_enqueue_style('fvs-settings', FVS_URL . 'assets/css/fvs-settings.css', array(), FVS_VERSION);
    wp_enqueue_script('fvs-settings', FVS_URL . 'assets/js/fvs-settings.js', array(), FVS_VERSION, true);

    $limits = array();
    foreach (fvs_settings_schema() as $field => $rule) {
        if (isset($rule['units'])) {
            $limits[$field] = $rule['units'];
        }
    }
    wp_localize_script('fvs-settings', 'fvsSettings', array(
        'defaults'    => fvs_default_settings(),
        'limits'      => $limits,
        'mobileUntil' => __('Vale para telas de até %spx de largura.', 'floating-video-stories'),
    ));
}

/**
 * Opções de unidade de cada campo numérico.
 *
 * @param string $field offset_x, offset_y ou size.
 * @return array<string,string>
 */
function fvs_settings_unit_labels($field)
{
    $px     = __('px', 'floating-video-stories');
    $width  = __('% da largura da tela', 'floating-video-stories');
    $height = __('% da altura da tela', 'floating-video-stories');

    switch ($field) {
        case 'offset_y':
            return array('px' => $px, '%h' => $height, '%w' => $width);
        default:
            return array('px' => $px, '%' => $width);
    }
}

/**
 * Campo numérico com seletor de unidade.
 *
 * @param array  $s
 * @param string $device
 * @param string $field
 */
function fvs_render_number_with_unit(array $s, $device, $field)
{
    $key    = $device . '_' . $field;
    $unit   = $s[$key . '_unit'];
    $schema = fvs_settings_schema();
    list($min, $max) = $schema[$field]['units'][$unit];
    ?>
    <span class="fvs-number-unit">
        <input type="number" id="fvs-<?php echo esc_attr($key); ?>" name="<?php echo esc_attr(FVS_SETTINGS_OPTION . '[' . $key . ']'); ?>" value="<?php echo esc_attr($s[$key]); ?>" min="<?php echo esc_attr($min); ?>" max="<?php echo esc_attr($max); ?>" step="0.1" class="small-text" data-fvs-field="<?php echo esc_attr($field); ?>" required>
        <select name="<?php echo esc_attr(FVS_SETTINGS_OPTION . '[' . $key . '_unit]'); ?>" data-fvs-unit-for="<?php echo esc_attr($field); ?>" aria-label="<?php esc_attr_e('Unidade', 'floating-video-stories'); ?>">
            <?php foreach (fvs_settings_unit_labels($field) as $value => $label) : ?>
                <option value="<?php echo esc_attr($value); ?>" <?php selected($unit, $value); ?>><?php echo esc_html($label); ?></option>
            <?php endforeach; ?>
        </select>
    </span>
    <?php
}

/**
 * Grupo de botões de rádio.
 *
 * @param array  $s
 * @param string $key
 * @param array  $choices valor => rótulo (HTML já escapado).
 */
function fvs_render_radio_group(array $s, $key, array $choices)
{
    foreach ($choices as $value => $label) {
        printf(
            '<label class="fvs-choice"><input type="radio" name="%1$s" value="%2$s" %3$s><span>%4$s</span></label>',
            esc_attr(FVS_SETTINGS_OPTION . '[' . $key . ']'),
            esc_attr($value),
            checked($s[$key], $value, false),
            $label // phpcs:ignore WordPress.Security.EscapeOutput -- montado com esc_html abaixo
        );
    }
}

function fvs_render_settings_page()
{
    if (!current_user_can('manage_options')) {
        return;
    }

    $s       = fvs_get_settings();
    $devices = array(
        'desktop' => array('label' => __('Desktop', 'floating-video-stories'), 'icon' => 'dashicons-desktop'),
        'mobile'  => array('label' => __('Mobile', 'floating-video-stories'), 'icon' => 'dashicons-smartphone'),
    );
    ?>
    <div class="wrap fvs-settings">
        <h1><?php esc_html_e('Floating Video Stories', 'floating-video-stories'); ?></h1>
        <p class="fvs-intro"><?php esc_html_e('Escolha onde a bolha de vídeo aparece e o tamanho dela, separadamente no computador e no celular. Útil quando o site já tem outros botões flutuantes, como WhatsApp, carrinho ou chat.', 'floating-video-stories'); ?></p>

        <form method="post" action="options.php">
            <?php settings_fields('fvs_settings_group'); ?>

            <div class="fvs-devices">
                <?php foreach ($devices as $device => $info) : ?>
                    <section class="fvs-device" data-fvs-device="<?php echo esc_attr($device); ?>">
                        <header class="fvs-device-header">
                            <h2><span class="dashicons <?php echo esc_attr($info['icon']); ?>" aria-hidden="true"></span> <?php echo esc_html($info['label']); ?></h2>
                            <button type="button" class="button button-link fvs-reset"><?php esc_html_e('Restaurar padrão', 'floating-video-stories'); ?></button>
                        </header>

                        <figure class="fvs-preview">
                            <div class="fvs-screen fvs-screen--<?php echo esc_attr($device); ?>" aria-hidden="true">
                                <span class="fvs-preview-bubble"><span class="fvs-preview-close"></span></span>
                            </div>
                            <figcaption>
                                <?php esc_html_e('Prévia aproximada', 'floating-video-stories'); ?>
                                <?php if ('mobile' === $device) : ?>
                                    · <span class="fvs-mobile-until"><?php echo esc_html(sprintf(__('Vale para telas de até %spx de largura.', 'floating-video-stories'), $s['breakpoint'])); ?></span>
                                <?php endif; ?>
                            </figcaption>
                        </figure>

                        <h3><?php esc_html_e('Posição', 'floating-video-stories'); ?></h3>
                        <div class="fvs-row">
                            <span class="fvs-label" id="fvs-<?php echo esc_attr($device); ?>-side-label"><?php esc_html_e('Lado', 'floating-video-stories'); ?></span>
                            <div class="fvs-choices" role="radiogroup" aria-labelledby="fvs-<?php echo esc_attr($device); ?>-side-label">
                                <?php
                                fvs_render_radio_group($s, $device . '_side', array(
                                    'left'  => '<span class="dashicons dashicons-arrow-left-alt" aria-hidden="true"></span> ' . esc_html__('Esquerda', 'floating-video-stories'),
                                    'right' => esc_html__('Direita', 'floating-video-stories') . ' <span class="dashicons dashicons-arrow-right-alt" aria-hidden="true"></span>',
                                ));
                                ?>
                            </div>
                        </div>
                        <div class="fvs-row">
                            <label class="fvs-label" for="fvs-<?php echo esc_attr($device); ?>_offset_x"><?php esc_html_e('Distância da lateral', 'floating-video-stories'); ?></label>
                            <?php fvs_render_number_with_unit($s, $device, 'offset_x'); ?>
                        </div>
                        <div class="fvs-row">
                            <span class="fvs-label" id="fvs-<?php echo esc_attr($device); ?>-anchor-label"><?php esc_html_e('Medir a partir de', 'floating-video-stories'); ?></span>
                            <div class="fvs-choices" role="radiogroup" aria-labelledby="fvs-<?php echo esc_attr($device); ?>-anchor-label">
                                <?php
                                fvs_render_radio_group($s, $device . '_anchor', array(
                                    'top'    => '<span class="dashicons dashicons-arrow-up-alt" aria-hidden="true"></span> ' . esc_html__('Topo', 'floating-video-stories'),
                                    'bottom' => '<span class="dashicons dashicons-arrow-down-alt" aria-hidden="true"></span> ' . esc_html__('Base', 'floating-video-stories'),
                                ));
                                ?>
                            </div>
                        </div>
                        <div class="fvs-row">
                            <label class="fvs-label" for="fvs-<?php echo esc_attr($device); ?>_offset_y"><?php esc_html_e('Distância vertical', 'floating-video-stories'); ?></label>
                            <?php fvs_render_number_with_unit($s, $device, 'offset_y'); ?>
                        </div>
                        <p class="description"><?php esc_html_e('Dica: se o site tem botão de WhatsApp ou carrinho num canto, coloque a bolha do lado oposto.', 'floating-video-stories'); ?></p>

                        <h3><?php esc_html_e('Tamanho', 'floating-video-stories'); ?></h3>
                        <div class="fvs-row">
                            <label class="fvs-label" for="fvs-<?php echo esc_attr($device); ?>_size"><?php esc_html_e('Diâmetro', 'floating-video-stories'); ?></label>
                            <?php fvs_render_number_with_unit($s, $device, 'size'); ?>
                        </div>
                        <p class="description"><?php esc_html_e('Em % da largura da tela, a bolha acompanha o tamanho do aparelho.', 'floating-video-stories'); ?></p>
                    </section>
                <?php endforeach; ?>
            </div>

            <h2><?php esc_html_e('Avançado', 'floating-video-stories'); ?></h2>
            <table class="form-table" role="presentation">
                <tr>
                    <th scope="row"><label for="fvs-breakpoint"><?php esc_html_e('Largura de tela do mobile', 'floating-video-stories'); ?></label></th>
                    <td>
                        <input type="number" id="fvs-breakpoint" name="<?php echo esc_attr(FVS_SETTINGS_OPTION . '[breakpoint]'); ?>" value="<?php echo esc_attr($s['breakpoint']); ?>" min="320" max="1200" step="1" class="small-text" required> px
                        <p class="description"><?php esc_html_e('Telas com até esta largura usam a configuração de mobile (vale também para o layout do player). Padrão: 640px. Use 1024px para incluir tablets.', 'floating-video-stories'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="fvs-z_index"><?php esc_html_e('Camada (z-index)', 'floating-video-stories'); ?></label></th>
                    <td>
                        <input type="number" id="fvs-z_index" name="<?php echo esc_attr(FVS_SETTINGS_OPTION . '[z_index]'); ?>" value="<?php echo esc_attr($s['z_index']); ?>" min="1" max="2147483000" step="1" class="regular-text" required>
                        <p class="description"><?php esc_html_e('Aumente se a bolha ficar atrás de outro elemento flutuante, ou diminua se ela cobrir um menu. Padrão: 9999. O player de vídeo sempre fica acima da bolha.', 'floating-video-stories'); ?></p>
                    </td>
                </tr>
            </table>

            <?php submit_button(); ?>
        </form>
    </div>
    <?php
}
