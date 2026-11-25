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
