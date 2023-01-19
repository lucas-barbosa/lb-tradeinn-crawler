<?php

/**
 * @wordpress-plugin
 * Plugin Name:       LB TradeInn Crawler
 * Description:       Plugin Description
 * Version:           1.0.0
 * Author:            Lucas Barbosa
 * Author URI:        https://github.com/lucas-barbosa
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 */


// If this file is called directly, abort.

if (!defined('WPINC') || !defined('ABSPATH')) {
  die;
}

require_once __DIR__ . '/vendor/autoload.php';

/*
 * Check if Woocommerce is installed
 */
$plugin_path = trailingslashit( WP_PLUGIN_DIR ) . 'woocommerce/woocommerce.php';

if ( !in_array($plugin_path, wp_get_active_and_valid_plugins()) && !in_array($plugin_path, wp_get_active_network_plugins()) ) {
  exit;
}