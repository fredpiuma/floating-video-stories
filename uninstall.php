<?php

/**
 * Floating Video Stories - Desinstalação
 * Remove só as configurações do plugin. As metas dos produtos e páginas (vídeos) ficam.
 */

if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

delete_option('fvs_settings');
