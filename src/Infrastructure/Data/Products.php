<?php

namespace LucasBarbosa\LbTradeinnCrawler\Infrastructure\Data;

class Products {
  private static function checkUrlExistsInMeta( $storeName, $productId ) {
    global $wpdb;

    $query = "SELECT * from {$wpdb->prefix}postmeta where meta_key = '_tradeinn_props' and meta_value like %s";

    $query = $wpdb->prepare( $query, '%"storeName"%"' . $storeName . '"%"productId"%"' . $productId . '"%' );

    $results = $wpdb->get_results( $query, ARRAY_A );

    return count( $results ) > 0;
  }

  static function isAlreadyCrawled( $storeName, $productId ) {
    // TODO: check product was rejected before
    return self::checkUrlExistsInMeta( $storeName, $productId );
  }
}