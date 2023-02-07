<?php

namespace LucasBarbosa\LbTradeinnCrawler\Core\Usecases;

use LucasBarbosa\LbTradeinnCrawler\Core\Entities\ProductEntity;
use LucasBarbosa\LbTradeinnCrawler\Infrastructure\Data\IdMapper;

class RefreshStock extends CreateProduct {
  public function setHooks() {
    add_action( 'lb_tradeinn_crawler_update_product_stock', array( $this, 'handleUpdateStock' ), 10, 2 );
  }

  public function handleUpdateStock( ProductEntity $productData, string $productId ) {
    $product = wc_get_product( $productId );

    if ( ! $product ) {
      return;
    }

    parent::loadParams();

    if ( $product->is_type( 'simple' ) ) {
      $price = $productData->getPrice();
      $availability = $productData->getAvailability();

			$changed = parent::setPriceAndStock( $product, $price, $availability );
			parent::saveProduct( $product, $changed, $price, $availability );

      return;
		}

    foreach ( $productData->getVariations() as $variation ) {
      $variationId = IdMapper::getVariationId( $variation->getId(), $productData->getStoreName() );

      if ( empty( $variationId ) ) {
        continue;
      }

      $product = wc_get_product( $variationId );

      if ( ! $product ) {
        continue;
      }

      $price = $variation->getPrice();
      $availability = $variation->getAvailability();

			$changed = parent::setPriceAndStock( $product, $price, $availability );
			parent::saveProduct( $product, $changed, $price, $availability );
    }
  }
}