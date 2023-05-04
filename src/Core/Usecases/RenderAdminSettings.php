<?php

namespace LucasBarbosa\LbTradeinnCrawler\Core\Usecases;

use LucasBarbosa\LbTradeinnCrawler\Infrastructure\Data\SettingsData;

class RenderAdminSettings {
  private string $page_name = 'lb-tradeinn-crawler';
  private string $plugin_name;
  private string $version;

  function __construct( $plugin_name, $version ) {
    $this->plugin_name = $plugin_name;
    $this->version = $version;
  }

  function setHooks() {
    if ( is_admin() ) {
      add_action( 'admin_action_run_categories_crawler', array( $this, 'run_crawler_get_categories' ) );
      add_action( 'admin_action_run_products_crawler', array( $this, 'run_crawler_get_products' ) );
      add_action( 'admin_post_lb_tradeinn_crawler_available_categories', array( $this, 'handle_set_selected_categories' ) );
      add_action( 'admin_post_lb_tradeinn_crawler_stock', array( $this, 'handle_set_stock' ) );
      add_action( 'admin_post_lb_tradeinn_crawler_weight_settings', array( $this, 'handle_set_weight_settings' ) );
      add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
      add_action( 'admin_menu', array( $this, 'add_menu_option' ) );
      add_filter( 'plugin_action_links_' . LB_TRADEINN_CRAWLER, array( $this, 'add_plugin_settings_link' ) );
    }
  }

  function add_menu_option() {
    add_submenu_page(
      'woocommerce',
      'TradeInn Crawler',
      'TradeInn Crawler',
      'manage_woocommerce',
      $this->page_name,
      array( $this, 'render_page' )
    );
  }

  function add_plugin_settings_link( $links ) {
    $plugin_links   = array();
    $plugin_links[] = '<a href="' . esc_url( admin_url( 'admin.php?page=' . $this->page_name ) ) . '">' . __( 'Settings', 'lb-tradeinn-crawler' ) . '</a>';
    return array_merge( $plugin_links, $links );
  }

  function enqueue_assets() {
    if ( ! isset( $_GET['page'] ) || $_GET['page'] !== $this->page_name ) {
      return;
    }

    wp_register_script( $this->plugin_name, plugins_url( 'assets/admin.js', LB_TRADEINN_CRAWLER_FILE ), [ 'jquery' ], $this->version );

    wp_localize_script( $this->plugin_name, $this->plugin_name, array(
      'weight_settings' => SettingsData::getWeightSettings(),
      'available_categories'  => SettingsData::getCategories(),
      'selected_categories'   => SettingsData::getSelectedCategories(),
      'viewed_categories'     => SettingsData::getViewedCategories(),
      'categories_dimension'  => SettingsData::getCategoriesDimension(),
      'categories_weight'     => SettingsData::getCategoriesWeight(),
      'override_weight'       => SettingsData::getCategoriesOverrideWeight()
    ) );
      
    wp_enqueue_script( $this->plugin_name );
  }

  function handle_set_stock() {
    if( ! current_user_can( 'manage_woocommerce' ) || ! isset( $_POST['lb-nonce'] ) || ! wp_verify_nonce( $_POST['lb-nonce'], 'lb_tradeinn_crawler_nonce' ) ) {
      wp_die( __( 'Invalid nonce specified', $this->plugin_name ), __( 'Error', $this->plugin_name ), array(
        'response' 	=> 403,
        'back_link' => 'admin.php?page=' . $this->page_name,
      ) );

      return;
    }

    SettingsData::saveStock( $_POST['lb_tradeinn_stock'] );
    SettingsData::saveMultiplicator( sanitize_text_field( $_POST['lb_tradeinn_multiplicator'] ) );
    SettingsData::saveParentCategory( sanitize_text_field( $_POST['lb_tradeinn_category'] ) );
    
    wp_redirect( admin_url( 'admin.php?page=' . $this->page_name ) );
    exit;
  }

  function handle_set_weight_settings() {
    if( ! current_user_can( 'manage_woocommerce' ) || ! isset( $_POST['lb-nonce'] ) || ! wp_verify_nonce( $_POST['lb-nonce'], 'lb_tradeinn_crawler_nonce' ) ) {
      wp_die( __( 'Invalid nonce specified', $this->plugin_name ), __( 'Error', $this->plugin_name ), array(
        'response' 	=> 403,
        'back_link' => 'admin.php?page=' . $this->page_name,
      ) );

      return;
    }

    $data = array();

    if ( isset( $_POST['_min_price'] ) && is_array( $_POST['_min_price'] ) ) {
      for ( $i = 0; $i < count( $_POST['_min_price'] ); $i++ ) {
        $min_weight = sanitize_text_field( $_POST['_min_weight'][$i] );
        $max_weight = sanitize_text_field( $_POST['_max_weight'][$i] );
        $price = sanitize_text_field( $_POST['_min_price'][$i] );

        if ( empty( $price ) || ( empty( $min_weight ) && empty( $max_weight ) ) ) {
          continue;
        }

        $data[] = array(
          'min_weight' => empty( $min_weight ) ? 0 : $min_weight,
          'max_weight' => $max_weight,
          'min_price'  => $price
        );
      }
    }

    $min_prices = array_column( $data, 'min_price' );
    $min_price = 0;

    if ( count( $min_prices ) > 0 ) {
      sort( $min_prices );
      $min_price = $min_prices[0];
    }

    $max_weights = array_column( $data, 'max_weight' );
    $max_weight = null;

    if ( count( $max_weights ) > 0 ) {
      rsort( $max_weights );
      $max_weight = $max_weights[0];
    }

    SettingsData::saveWeightSettings( $data );
    SettingsData::saveMinPrice( $min_price );
    SettingsData::saveMaxWeight( $max_weight );

    wp_redirect( admin_url( 'admin.php?page=' . $this->page_name ) );
    exit;
  }

  function handle_set_selected_categories() {
    if( ! current_user_can( 'manage_woocommerce' ) || ! isset( $_POST['lb-nonce'] ) || ! wp_verify_nonce( $_POST['lb-nonce'], 'lb_tradeinn_crawler_nonce' ) ) {
      wp_die( __( 'Invalid nonce specified', $this->plugin_name ), __( 'Error', $this->plugin_name ), array(
        'response' 	=> 403,
        'back_link' => 'admin.php?page=' . $this->page_name,
      ) );

      return;
    }

    $data = array();
    $categoriesWeight = array();
    $categoriesDimension = array();
    $viewedCategories = array();
    $overrideWeightCategories = array();

    if ( isset( $_POST['selected_tradeinn_categories'] ) && is_array( $_POST['selected_tradeinn_categories'] ) ) {
      $data = $_POST['selected_tradeinn_categories'];
      $data = array_filter( $data );
      $data = array_values( array_unique( $data ) );
    }

    if ( isset( $_POST['viewed_categories'] ) && is_array( $_POST['viewed_categories'] ) ) {
      $viewedCategories = $_POST['viewed_categories'];
      $viewedCategories = array_filter( $viewedCategories );
      $viewedCategories = array_values( array_unique( $viewedCategories ) );
    }

    if ( isset(  $_POST['override_weight_categories'] ) && is_array( $_POST['override_weight_categories'] ) ) {
      $overrideWeightCategories = $_POST['override_weight_categories'];
      $overrideWeightCategories = array_filter( $overrideWeightCategories );
      $overrideWeightCategories = array_values( array_unique( $overrideWeightCategories ) );
    }

    foreach ( $_POST['lb-tradeinn-weight'] as $key => $value ) {
      if ( $value > 0 ) $categoriesWeight[$key] = sanitize_text_field( $value );
    }

    foreach ( $_POST['lb-tradeinn-dimension'] as $key => $value ) {
      if ( $value > 0 ) $categoriesDimension[$key] = sanitize_text_field( $value );
    }
    
    SettingsData::saveSelectedCategories( $data );
    SettingsData::saveCategoriesDimension( $categoriesDimension );
    SettingsData::saveCategoriesWeight( $categoriesWeight );
    SettingsData::saveViewedCategories( $viewedCategories );
    SettingsData::saveOverrideWeightCategories( $overrideWeightCategories );

    wp_redirect( admin_url( 'admin.php?page=' . $this->page_name ) );
    exit;
  }

  function render_page() {
    if ( ! empty( $_REQUEST['action'] ) ) {
      $action = $_REQUEST['action'];

      do_action( "admin_action_{$action}" );
    }

    wc_get_template( 'tradeinn-settings.php', array(), 'woocommerce/tradeinn-crawler/', LB_TRADEINN_CRAWLER_DIR . 'templates/' );
  }

  function run_crawler_get_categories() {
    check_admin_referer( 'lb-tradeinn-crawler' );

		do_action( 'lb_tradein_categories_crawler' );

		wp_redirect( admin_url( 'admin.php?page=' . $this->page_name ) );
		exit;
  }

  function run_crawler_get_products() {
    check_admin_referer( 'lb-tradeinn-crawler' );

		do_action( 'lb_tradeinn_selected_categories_crawler' );
		
		wp_redirect( admin_url( 'admin.php?page=' . $this->page_name ) );
		exit;
  }
}
