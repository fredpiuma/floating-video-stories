<?php

/**
 * Plugin Name: Floating Video Stories for WooCommerce
 * Description: Plugin para adicionar vídeos aos produtos WooCommerce e páginas.
 * Author: Frederico de Castro
 * Author URI: https://www.fredericodecastro.com.br/links
 * Version: 1.4.2
 * Text Domain: floating-video-stories
 */

if (!defined('ABSPATH')) {
    exit;
}

define('FVS_VERSION', '1.4.2');
define('FVS_PATH', plugin_dir_path(__FILE__));
define('FVS_URL', plugin_dir_url(__FILE__));

// Funcionalidades comuns (dados, renderização e assets do front)
require_once FVS_PATH . 'includes/fred-video-common.php';

// Configurações (posição, tamanho e camada da bolha)
require_once FVS_PATH . 'includes/fred-video-settings.php';

// Componente de administração (lista de stories)
require_once FVS_PATH . 'includes/fred-video-admin-field.php';

// Módulo de vídeos para produtos
require_once FVS_PATH . 'includes/fred-video-produtos.php';

// Módulo de vídeos para páginas
require_once FVS_PATH . 'includes/fred-video-pages.php';
