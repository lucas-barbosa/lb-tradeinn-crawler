<?php

namespace LucasBarbosa\LbTradeinnCrawler\Infrastructure\Data;

class SettingsData {
  private static $options = [
    'available_categories'  => 'lb_tradeinn_categories',
    'categoriesDimension'   => '_lb_tradeinn_cat_dimension',
    'categoriesWeight'      => '_lb_tradeinn_cat_weight',
    'min_price'             => 'lb_tradeinn_min_price',
    'max_size'              => 'lb_tradeinn_max_size',
    'max_weight'            => 'lb_tradeinn_max_weight',
    'multiplicator'         => 'lb_tradeinn_multiplicator',
    'parent_category'       => 'lb_tradeinn_parent_category',
    'stock'                 => 'lb_tradeinn_stock',
    'selected_categories'   => 'lb_tradeinn_selected_categories',
    'weight_settings'       => 'lb_tradeinn_weight_settings',
    'override_weight_categories' => '_lb_tradeinn_override_weight_categories',
    'viewed_categories'     => '_lb_tradeinn_viewed_categories',
  ];

  static function getCategories() {
    return get_option( self::$options['available_categories'], [] );
  }

  static function getCategoriesDimension() {
    return get_option( self::$options['categoriesDimension'], [] );
  }

  static function getCategoriesOverrideWeight() {
    return get_option( self::$options['override_weight_categories'], [] );
  }

  static function getCategoriesWeight() {
    return get_option( self::$options['categoriesWeight'], [] );
  }

  static function getMinPrice() {
    return get_option( self::$options['min_price'], 0 );
  }

  static function getMaxSize() {
    return get_option( self::$options['max_size'], null );
  }

  static function getMaxWeight() {
    return get_option( self::$options['max_weight'], null );
  }

  static function getMultiplicator() {
    $value = (float)get_option( self::$options['multiplicator'], '1' );
    return is_numeric( $value ) ? $value : 1;
  }

  static function getStock() {
    return get_option( self::$options['stock'], '' );
  }

  static function getParentCategory() {
    return get_option( self::$options['parent_category'], '0' );
  }

  static function getViewedCategories() {
    return get_option( self::$options['viewed_categories'], [] );
  }

  static function getWeightSettings() {
    return get_option( self::$options['weight_settings'], [] );
  }

  static function getSelectedCategories() {
    return get_option( self::$options['selected_categories'], [] );
  }

  static function saveCategories( $categories ) {
    update_option( self::$options['available_categories'], $categories, false );
  }

  static function saveCategoriesDimension( $categories ) {
    update_option( self::$options['categoriesDimension'], $categories, false );
  }
  
  static function saveCategoriesWeight( $categories ) {
    update_option( self::$options['categoriesWeight'], $categories, false );
  }

  static function saveOverrideWeightCategories( $categories ) {
    update_option( self::$options['override_weight_categories'], $categories, false );
  }

  static function saveViewedCategories( $categories ) {
    update_option( self::$options['viewed_categories'], $categories, false );
  }

  static function saveMinPrice( $price ) {
    update_option( self::$options['min_price'], $price, false );
  }

  static function saveMaxSize( $size ) {
    update_option( self::$options['max_size'], $size, false );
  }

  static function saveMaxWeight( $weight ) {
    update_option( self::$options['max_weight'], $weight, false );
  }

  static function saveMultiplicator( $value ) {
    update_option( self::$options['multiplicator'], $value, false );
  }
  
  static function saveParentCategory( $value ) {
    update_option( self::$options['parent_category'], $value, false );
  }

  static function saveSelectedCategories( $categories ) {
    update_option( self::$options['selected_categories'], $categories, false );
  }

  static function saveStock( $stock ) {
    update_option( self::$options['stock'], $stock, false );
  }

  static function saveWeightSettings( $data ) {
    update_option( self::$options['weight_settings'], $data, false );
  }

  static function deleteOldActions( $selectedCategories ) {
    global $wpdb;

    $placeholders = implode(',', array_fill(0, count( $selectedCategories ), '%s') );
    
    $values = array_map( function( $category ) {
      $categoryData = explode( '|', $category );

      if ( count( $categoryData ) < 3 ) return '';

      $storeName      = $categoryData[0] ?? '';
      $storeId        = $categoryData[1] ?? '';
      $categoryId     = $categoryData[2] ?? '';
      $subcategoryId  = $categoryData[3] ?? '';

      return '[{"categoryId":"' . $categoryId . '","subcategoryId":"' . $subcategoryId . '","page":0,"language":"por","store":{"id":"' . $storeId . '","name":"' . $storeName . '"}}]';
    }, $selectedCategories );
    
    $query = $wpdb->prepare("DELETE FROM {$wpdb->prefix}actionscheduler_actions WHERE status = 'pending' and hook = 'lb_tradeinn_category_crawler' and args NOT IN ($placeholders) and schedule NOT LIKE '%ActionScheduler_NullSchedule%'", $values);

    $wpdb->query( $query, 'ARRAY_A' ) ;
  }
}