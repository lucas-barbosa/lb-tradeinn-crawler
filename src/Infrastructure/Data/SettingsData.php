<?php

namespace LucasBarbosa\LbTradeinnCrawler\Infrastructure\Data;

class SettingsData {
  private static $options = [
    'available_categories' => 'lb_tradeinn_categories'
  ];

  static function getCategories() {
    return get_option( self::$options['available_categories'], [] );
  }
  
  static function saveCategories( $categories ) {
    update_option( self::$options['available_categories'], $categories, false );
  }
}