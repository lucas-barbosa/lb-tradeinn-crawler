<?php

/**
 * @wordpress-plugin
 * Plugin Name:       LB TradeInn Crawler
 * Description:       LB TradeInn Crawler copy TradeInn products to your ecommerce.
 * Version:           1.0.2
 * Author:            Lucas Barbosa
 * Author URI:        https://github.com/lucas-barbosa
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 */

use LucasBarbosa\LbTradeinnCrawler\Core\InitCore;
use LucasBarbosa\LbTradeinnCrawler\Infrastructure\Crawler\CategoriesCrawler;
use LucasBarbosa\LbTradeinnCrawler\Infrastructure\Crawler\CategoryCrawler;
use LucasBarbosa\LbTradeinnCrawler\Infrastructure\Crawler\ProductCrawler;
use LucasBarbosa\LbTradeinnCrawler\Infrastructure\InitInfra;
use LucasBarbosa\LbTradeinnCrawler\Infrastructure\Parser\CategoriesParser;
use LucasBarbosa\LbTradeinnCrawler\Infrastructure\Parser\CategoryParser;
use LucasBarbosa\LbTradeinnCrawler\Infrastructure\Parser\ProductParser;

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

$core = new InitCore( 'lb_tradeinn_crawler', '1.0.0' );
$core->load();

$infra = new InitInfra();
$infra->load();

// add_action( 'shutdown', 'lb_test_fn');

function lb_test_fn() {
  do_action( 'lb_tradein_product_crawler', 
  [
    'storeId'   => '3',
    'storeName' => 'trekkinn',
    'productId' => '137059647',
    'language'  => 'por'
  ] ); 

  remove_action( 'shutdown', 'lb_test_fn');
}