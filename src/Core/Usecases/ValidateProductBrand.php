<?php

namespace LucasBarbosa\LbTradeinnCrawler\Core\Usecases;

use LucasBarbosa\LbTradeinnCrawler\Core\Entities\ProductEntity;
use LucasBarbosa\LbTradeinnCrawler\Infrastructure\Data\SettingsData;

class ValidateProductBrand {
  public function setHooks() {
    add_action( 'lb_tradeinn_crawler_product_loaded', array( $this, 'execute' ), 4 );
  }

  public function execute( ProductEntity $product ) {
    if ( $product->getInvalid() ) {
      return;
    }

    $deniedBrands = SettingsData::getDeniedBrands();
    $deniedBrands = preg_split('/\r\n|[\r\n]/', $deniedBrands );

    if ( empty( $deniedBrands ) ) {
      return;
    }

    $deniedBrands = array_map( 'strtolower', $deniedBrands );
    $productBrand = strtolower( $product->getBrand() ?? '' );

    if ( in_array( $productBrand, $deniedBrands ) ) {
      $product->setInvalid();
    }
  }
}