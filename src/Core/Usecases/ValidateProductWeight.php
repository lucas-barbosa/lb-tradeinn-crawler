<?php

namespace LucasBarbosa\LbTradeinnCrawler\Core\Usecases;

use LucasBarbosa\LbTradeinnCrawler\Core\Entities\ProductEntity;
use LucasBarbosa\LbTradeinnCrawler\Infrastructure\Data\SettingsData;

class ValidateProductWeight {
  public function setHooks() {
    add_action( 'lb_tradeinn_crawler_product_loaded', array( $this, 'execute' ), 5 );
  }

  public function execute( ProductEntity $product ) {
    if ( ! $product->isVariable() ) {
      $isValid = $this->checkIsValid( $product );

      if ( ! $isValid ) {
        $product->setInvalid();
      }

      return;
    }

    $valid = false;
    $variations = $product->getVariations();

    foreach ( $variations as $variation ) {
      $isValid = $this->checkIsValid( $variation );

      if ( ! $isValid ) {
        $variation->setInvalid();
      } else {
        $valid = true;
      }
    }

    if ( ! $valid ) {
      $product->setInvalid();
    }
  }

  private function checkIsValid( $product ) {
    $price = $product->getPrice();

    if ( empty( $price ) ){
      return false;
    }

    $weight = $product->getWeight();

    if ( empty( $weight ) ) {
      return true;
    }

    $minPrice = SettingsData::getMinPrice();

    if ( is_numeric( $minPrice ) && ( round( $price, 2 ) < round( $minPrice, 2 ) ) ) {
      return false;
    }      

    if ( ! $this->validateMaxWeight( $weight ) ) {
      return false;
    }

    return $this->validateWeight( $price, $weight );
  }

  private function getWeightInGrams( $weight ) {
    return $weight * 1000;
  }

  private function validateMaxWeight( $weight ) {
    $maxWeight = SettingsData::getMaxWeight();

    if ( is_null( $maxWeight ) || empty( $maxWeight ) ) {
      return true;
    }

    $productWeight = $this->getWeightInGrams( $weight );

    return $maxWeight >= $productWeight;
  }

  private function validateWeight( $productPrice, $productWeight ) {
    $productWeight = $this->getWeightInGrams( $productWeight );
    $weightRules = SettingsData::getWeightSettings();

    if ( !is_array( $weightRules ) || count ( $weightRules ) === 0 ) {
      return true;
    }

    foreach ( $weightRules as $rule ) {
      if ( $productWeight >= $rule['min_weight'] && ( $productWeight <= $rule['max_weight'] || empty( $rule['max_weight'] ) ) ) {
        return round( $productPrice, 2 ) >= round( $rule['min_price'], 2 );
      }
    }

    return false;
  }
}