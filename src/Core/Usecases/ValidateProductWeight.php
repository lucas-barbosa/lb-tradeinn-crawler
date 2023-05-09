<?php

namespace LucasBarbosa\LbTradeinnCrawler\Core\Usecases;

use LucasBarbosa\LbTradeinnCrawler\Core\Entities\ProductEntity;
use LucasBarbosa\LbTradeinnCrawler\Infrastructure\Data\SettingsData;

class ValidateProductWeight {
  public function setHooks() {
    add_action( 'lb_tradeinn_crawler_product_loaded', array( $this, 'execute' ), 5 );
  }

  public function execute( ProductEntity $product ) {
    if ( $product->getInvalid() ) {
      return;
    }
    
    $categoryId = $product->getCategoryId();

    $overrideDimensions = $this->checkCategoryShouldOverrideWeight( $categoryId );
    $defaultWeight = $this->getCategoryDefaultWeight( $categoryId );
    $defaultSize = $this->getCategoryDefaultSize( $categoryId );

    if ( ! $product->isVariable() ) {
      $isValid = $this->checkIsValid( $product, $defaultWeight, $defaultSize, $overrideDimensions );

      if ( ! $isValid ) {
        $product->setInvalid();
      }

      return;
    }

    $valid = false;
    $variations = $product->getVariations();

    foreach ( $variations as $variation ) {
      $isValid = $this->checkIsValid( $variation, $defaultWeight, $defaultSize, $overrideDimensions );

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

  private function checkIsValid( $product, $defaultWeight, $defaultSize, $overrideDimensions ) {
    $price = $product->getPrice();

    if ( empty( $price ) ){
      return false;
    }

    $size = $product->getSize();

    if ( empty( $size ) || $overrideDimensions ) {
      $size = $defaultSize;
    } else {
      $size = Utils::convertDimensionToUnit( $size['value'], $size['unit'], 'cm' );
    }
    
    if ( ! $this->validateMaxSize( $size ) ) {
      return false;
    }

    $largestSide = $product->getLargestSide();

    if ( empty( $largestSide ) || $overrideDimensions ) {
      $largestSide = 0;
    } else {
      $largestSide = Utils::convertDimensionToUnit( $largestSide['value'], $largestSide['unit'], 'cm' );
    }

    if ( $largestSide > 140 ) {
      return false;
    }

    if ( ! $this->validateMinPrice( $price ) ) {
      return false;
    }

    $weight = $product->getWeight();

    if ( empty( $weight ) || $overrideDimensions ) {
      $weight = $this->getWeightInKg( $defaultWeight );
      $product->setWeight( $weight );
    }

    if ( empty( $weight ) ) {
      return true;
    }

    if ( ! $this->validateMaxWeight( $weight ) ) {
      return false;
    }

    return $this->validateWeight( $price, $weight, $size );
  }

  private function checkCategoryShouldOverrideWeight( $categoryId ) {
    if ( empty( $categoryId ) ) {
      return false;
    }

    $shouldOverrideCategories = SettingsData::getCategoriesOverrideWeight();

    if ( empty( $shouldOverrideCategories ) || ! is_array( $shouldOverrideCategories ) ) {
      return false;
    }

    return in_array( $categoryId, $shouldOverrideCategories );
  }

  private function getCategoryDefaultWeight( $categoryId ) {
    if ( empty( $categoryId ) ) {
      return 0;
    }

    $defaultWeight = SettingsData::getCategoriesWeight();

    if ( empty( $defaultWeight ) ) {
      return 0;
    }

    $categoryWeight = isset( $defaultWeight[$categoryId] ) ? $defaultWeight[$categoryId] : 0;

    return $categoryWeight;
  }

  private function getCategoryDefaultSize( $categoryId ) {
    if ( empty( $categoryId ) ) {
      return 0;
    }
    
    $defaultDimension = SettingsData::getCategoriesDimension();

    if ( empty( $defaultDimension ) ) {
      return 0;
    }

    $categoryDimension = isset( $defaultDimension[$categoryId] ) ? $defaultDimension[$categoryId] : 0;

    return $categoryDimension;
  }

  private function getWeightInGrams( $weight ) {
    return $weight * 1000;
  }

  private function getWeightInKg( $weight ) {
    if ( empty( $weight ) || ! is_numeric( $weight ) ) {
      return 0;
    }

    return $weight / 1000;
  }

  private function validateMaxSize( $productSize ) {
    $maxAllowedSize = SettingsData::getMaxSize();

    if ( is_null( $maxAllowedSize ) || empty( $maxAllowedSize ) || empty( $productSize ) ) {
      return true;
    }

    return $maxAllowedSize >= $productSize;
  }

  private function validateMaxWeight( $weight ) {
    $maxWeight = SettingsData::getMaxWeight();

    if ( is_null( $maxWeight ) || empty( $maxWeight ) ) {
      return true;
    }

    $productWeight = $this->getWeightInGrams( $weight );

    return $maxWeight >= $productWeight;
  }

  private function validateMinPrice( $price ) {
    $minPrice = SettingsData::getMinPrice();
    return ! is_numeric( $minPrice ) || ( round( $price, 2 ) >= round( $minPrice, 2 ) );
  }

  private function validateWeight( $productPrice, $productWeight, $productSize ) {
    $productWeight = $this->getWeightInGrams( $productWeight );
    $weightRules = SettingsData::getWeightSettings();

    if ( !is_array( $weightRules ) || count ( $weightRules ) === 0 ) {
      return true;
    }

    $productWeightIsAllowed =  false;

    foreach ( $weightRules as $rule ) {
      if ( $productWeightIsAllowed && $rule['max_size'] >= $productSize ) {
        return round( $productPrice, 2 ) >= round( $rule['min_price'], 2 );
      }

      if ( $productWeight >= $rule['min_weight'] && ( $productWeight <= $rule['max_weight'] || empty( $rule['max_weight'] ) ) ) {
        if ( empty( $rule['max_size'] ) || $rule['max_size'] >= $productSize ) {
          return round( $productPrice, 2 ) >= round( $rule['min_price'], 2 );
        }

        $productWeightIsAllowed = true;
      }
    }

    return false;
  }
}