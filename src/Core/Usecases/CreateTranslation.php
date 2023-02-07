<?php

namespace LucasBarbosa\LbTradeinnCrawler\Core\Usecases;

use LucasBarbosa\LbTradeinnCrawler\Core\Entities\ProductEntity;
use LucasBarbosa\LbTradeinnCrawler\Infrastructure\Data\IdMapper;
use LucasBarbosa\LbTradeinnCrawler\Infrastructure\Data\SettingsData;

class CreateTranslation {
  public function setHooks() {
    add_action( 'lb_tradeinn_crawler_translation_loaded', array( $this, 'execute' ), 10, 3 );
  }

  public function execute( ProductEntity $productData, string $productId, string $language ) {
		$title = $productData->getBrand() . ' ' . $productData->getTitle();
    $description = $productData->getDescription();

    do_action( 'lb_multi_language_translate_product', $productId, $language, $title, $description, '' );

    $this->translateAttributes( $productData->getAttributes(), $language, $productData->getStoreName() );
    $this->translateCategories( $productData->getCategories(), $language );
  }

  private function translateAttributes( array $attributes, $language, $storeName ) {
    foreach ( $attributes as $attribute ) {
      $id = $attribute->getId();

      if ( ! empty( $id ) ) {
        $attributeId = IdMapper::getAttributeId( $id );

        if ( ! empty( $attributeId ) && ! apply_filters( 'lb_multi_language_term_has_translation', false, $attributeId, $language ) ) {
          do_action( 'lb_multi_language_translate_term', $attributeId, $language, trim( $attribute->getName() ), '', sanitize_title( $attribute->getName() ) );
        }
      }

      $values = $attribute->getValue();

      foreach ( $values as $value ) {
        $attributeIds = [];

        if ( empty( $value['id'] ) ) {
          if (isset( $value['variationId'] ) && in_array( $attribute->getName(), ['Cor', 'Tamanho'] ) ) {
            $variationId = IdMapper::getVariationId( $value['variationId'], $storeName );

            if ( empty( $variationId ) ) {
              continue;
            }

            $attributeIds = wc_get_product_terms( $variationId, 'pa_' . strtolower( $attribute->getName() ), array( 'fields' => 'ids' ) );
          } else {
            continue;
          }
        } else {
          $attributeIds[] = IdMapper::getTermId( $value['id'] );
        }

        foreach ( $attributeIds as $attributeId ) {
          if ( ! empty( $attributeId ) && ! apply_filters( 'lb_multi_language_term_has_translation', false, $attributeId, $language ) ) {
            do_action( 'lb_multi_language_translate_term', $attributeId, $language, trim( $value['value'] ), '', sanitize_title( $value['value'] ) );
          }
        }
      }
    }
  }

  private function translateCategories( array $categories, $language ) {
    if ( ! is_array( $categories ) ) {
			return;
		}
    
		foreach ( $categories as $category ) {
			$categoryId = IdMapper::getTermById( $category['id'] );

      if ( $categoryId ) {
        if ( apply_filters( 'lb_multi_language_term_has_translation', false, $categoryId, $language ) ) {
          continue;
        }

        do_action( 'lb_multi_language_translate_term', $categoryId, $language, trim( $category['name'] ), '', sanitize_title( $category['name'] ) );
      }
    }
  }
}