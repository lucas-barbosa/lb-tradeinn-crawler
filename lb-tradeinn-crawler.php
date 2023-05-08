<?php

/**
 * @wordpress-plugin
 * Plugin Name:       LB TradeInn Crawler
 * Description:       LB TradeInn Crawler copy TradeInn products to your ecommerce.
 * Version:           1.3.0
 * Author:            Lucas Barbosa
 * Author URI:        https://github.com/lucas-barbosa
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 */

use LucasBarbosa\LbTradeinnCrawler\Core\InitCore;
use LucasBarbosa\LbTradeinnCrawler\Infrastructure\InitInfra;

// If this file is called directly, abort.

// if (!defined('WPINC') || !defined('ABSPATH')) {
//   die;
// }

require_once __DIR__ . '/vendor/autoload.php';

/*
 * Check if Woocommerce is installed
 */
$plugin_path = trailingslashit( WP_PLUGIN_DIR ) . 'woocommerce/woocommerce.php';

if ( !in_array($plugin_path, wp_get_active_and_valid_plugins()) && !in_array($plugin_path, wp_get_active_network_plugins()) ) {
  exit;
}


define( 'LB_TRADEINN_CRAWLER', plugin_basename( __FILE__ ) );
define( 'LB_TRADEINN_CRAWLER_FILE', __FILE__ );
define( 'LB_TRADEINN_CRAWLER_DIR', plugin_dir_path( __FILE__ ) );

$core = new InitCore( 'lb_tradeinn_crawler', '1.2.2' );
$core->load();

$infra = new InitInfra();
$infra->load();