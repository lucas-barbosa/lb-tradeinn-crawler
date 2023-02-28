<?php

namespace LucasBarbosa\LbTradeinnCrawler\Core\Usecases;

use LucasBarbosa\LbTradeinnCrawler\Core\Entities\ProductEntity;
use LucasBarbosa\LbTradeinnCrawler\Core\Entities\ProductVariationEntity;
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

    $existentVariations = $product->get_children( array(
			'fields'      => 'ids',
			'post_status' => array( 'publish', 'private' )
		), ARRAY_A );

		$syncedVariations = [];

    foreach ( $productData->getVariations() as $i => $variation ) {
      $variationId = IdMapper::getVariationId( $variation->getId(), $productData->getStoreName() );

      if ( empty( $variationId ) ) {
        $this->appendVariation( $i, $product, $variation, $productData->getStoreName() );
        continue;
      }

      $syncedVariations[] = $variationId;
      $variationProduct = wc_get_product( $variationId );

      if ( ! $variationProduct ) {
        continue;
      }

      $price = $variation->getPrice();
      $availability = $variation->getAvailability();

			$changed = parent::setPriceAndStock( $variationProduct, $price, $availability );
			parent::saveProduct( $variationProduct, $changed, $price, $availability );
    }

    $this->setNotFoundVariationsOutStock( $existentVariations, $syncedVariations );
  }

  private function appendAttribute( $product, $attribute_id, $attribute_name, $attribute_value ) {
    $attribute = $product->get_attribute( $attribute_name );

    if ( ! $attribute ) {
      return;
    }

    $name = wc_sanitize_taxonomy_name( stripslashes( $attribute_name ) );
    $taxonomy = wc_attribute_taxonomy_name($name);
    $taxonomy = $this->addTaxonomyIfNotExists( $attribute_id, $attribute_name, $taxonomy, [ $attribute_value ] );

    wp_set_post_terms( $product->get_id(), $attribute_value, $taxonomy, true );
  }
  
  private function appendVariation( $i, $product, $variation, $storeName ) {
    $attributes = $variation->getAttributes();

    foreach( $attributes as $attribute ) {
      $this->appendAttribute( $product, $attribute->getId(), $attribute->getName(), $attribute->getValue()[0] );
    }

    $this->createVariation( $i, $product, $variation, $storeName );
  }

  private function setNotFoundVariationsOutStock( $existentVariations, $foundVariations ) {
		if ( empty( $existentVariations ) ) {
			return;
		}

		foreach ( $existentVariations as $variationId ) {
			if ( ! in_array( $variationId, $foundVariations ) ) {
				$product = wc_get_product( $variationId );

        if ( ! $product ) {
          continue;
        }

        $price = '';
        $availability = 'outofstock';

        $changed = parent::setPriceAndStock( $product, $price, $availability );
        parent::saveProduct( $product, $changed, $price, $availability );
			}
		}		
	}
}