<?php

namespace LucasBarbosa\LbTradeinnCrawler\Infrastructure\Data;

class Products {
  private static function checkUrlExistsInMeta( $storeName, $productId ) {
    $meta = CrawlerPostMetaData::getByMetaLike( '_tradeinn_props', '%"storeName"%"' . $storeName . '"%"productId"%"' . $productId . '"%' );
		return !empty( $meta ) && !is_null( $meta );
  }

  static function isAlreadyCrawled( $storeName, $productId ) {
    $url = "https://www.tradeinn.com/" . $storeName . "/" . $productId;

    if ( apply_filters( 'lb_crawler_check_block', false, $url ) ) {
      return true;
    }

    return self::checkUrlExistsInMeta( $storeName, $productId );
  }
}