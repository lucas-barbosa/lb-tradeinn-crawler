<?php

namespace LucasBarbosa\LbTradeinnCrawler\Core\Entities;

use LucasBarbosa\LbTradeinnCrawler\Infrastructure\Data\SettingsData;

class ProductEntity {
  protected array $attributes = [];
  protected string $availability = 'outofstock';
  protected string $brand = '';
  protected array $categories = [];
  protected string $description = '';
  protected array $dimensions = [];
  protected string $id = '';
  protected bool $invalid = false;
  protected array $images = [];
  protected array $parentStoreProps = [];
  protected float $price = 0;
  protected string $sku = '';
  protected string $title = '';
  protected ?bool $variable = null;
  protected array $variations = [];

  public function __construct( string $title, string $id ) {
    $this->id = $id;
    $this->title = $title;
    return $this;
  }

  public function getAttributes() {
    return $this->attributes;
  }
  
  public function getAvailability() {
    if ( count( $this->variations ) === 1 ) {
      return $this->variations[0]->getAvailability();
    }

    return $this->availability;
  }
  
  public function getBrand() {
    return $this->brand;
  }
  
  public function getCategories() {
    return $this->categories;
  }

  public function getCategoryId() {
    $props = $this->getParentStoreProps();

    if ( isset( $props['categoryId'] ) ) {
      return $props['categoryId'];
    }

    return '';
  }
  
  public function getDescription() {
    return $this->description;
  }
  
  public function getDimensions() {
    if ( count( $this->variations ) === 1 ) {
      return $this->variations[0]->getDimensions();
    }

    return $this->dimensions;
  }

  public function getEan() {
    if ( count( $this->variations ) === 0 ) {
      return '';
    }

    return $this->variations[0]->getEan();
  }
    
  public function getId() {
    return $this->id;
  }

  public function getInvalid() {
    return $this->invalid;
  }
  
  public function getImages() {
    return $this->images;
  }
  
  public function getLargestSide() {
    $dimensions = $this->getDimensions();

    if ( ! is_array( $dimensions )
      || ! isset( $dimensions['height'] )
      || ! isset( $dimensions['length'] )
      || ! isset( $dimensions['width'] )
    ) {
      return [
        'value' => 0,
        'unit'  => 'cm'
      ];
    }

    $productUnit = isset( $dimensions['unit'] ) ? $dimensions['unit'] : 'cm';
    
    $sides = [
      $dimensions['height'],
      $dimensions['length'],
      $dimensions['width']
    ];
    
    $largestSide = max( $sides );

    return [
      'value' => $largestSide,
      'unit'  => $productUnit
    ];
  }

  public function getParentStoreProps() {
    return $this->parentStoreProps;
  }
  
  public function getStoreName() {
    if ( isset( $this->parentStoreProps['storeName'] ) ) {
      return $this->parentStoreProps['storeName'];
    }

    return '';
  }

  public function getPrice() {
    if ( count( $this->variations ) === 1 ) {
      return $this->variations[0]->getPrice();
    }

    $multiplicator = SettingsData::getMultiplicator();
    return round( $this->price * $multiplicator, 2 );
  }
   
  public function getSize() {
    $dimensions = $this->getDimensions();

    if ( ! is_array( $dimensions )
      || ! isset( $dimensions['height'] )
      || ! isset( $dimensions['length'] )
      || ! isset( $dimensions['width'] )
    ) {
      return [
        'value' => 0,
        'unit'  => 'cm'
      ];
    }

    $productUnit = isset( $dimensions['unit'] ) ? $dimensions['unit'] : 'cm';
    $size = $dimensions['height'] + $dimensions['length'] + $dimensions['width'];
    
    return [
      'value' => $size,
      'unit'  => $productUnit
    ];
  }
  
  public function getSku() {
    return empty( $this->sku ) ? '' : 'TT-' . $this->sku;
  }
  
  public function getTitle() {
    return $this->title;
  }
  
  public function getVariations() {
    return $this->variations;
  }

  public function getWeight() {
    if ( isset( $this->dimensions ) && is_array( $this->dimensions ) && isset ( $this->dimensions['weight'] ) && $this->dimensions['weight'] > 0 ) {
      return $this->dimensions['weight'] ?? 0;
    }

    $dimensions = $this->getDimensions();
    return $dimensions['weight'] ?? 0;
  }
  
  public function isVariable() {
    if ( ! is_null( $this->variable ) ) {
      return $this->variable;
    }

    if ( count( $this->variations ) > 1 ) {
      $this->variable = true;
      return true;
    }

    $attributes = $this->attributes;

    if ( empty( $attributes ) ) {
      $this->variable = false;
      return false;
    }

    foreach ( $attributes as $attribute ) {
      $attributeName = $attribute->getName();

      if ( strtoupper( $attributeName ) === 'TAMANHO' ) {
        $values = array_column( $attribute->getValue(), 'value' );

        if ( in_array( 'Tamanho Único', $values ) || in_array( 'One Size', $values ) ) {
          $this->variable = false;
          return false;
        }

        $this->variable = true;
        return true;
      }
    }

    $this->variable = false;
    return false;
  }

  public function setAttributes( $attributes ) {
    $this->attributes = $attributes;
    return $this;
  }

  public function setAvailability( $availability ) {
    $this->availability = $availability;
    return $this;
  }

  public function setBrand( $brand ) {
    $this->brand = $brand;
    return $this;
  }

  public function setCategories( $categories ) {
    $this->categories = $categories;
    return $this;
  }

  public function setDescription( $description ) {
    $this->description = $description;
    return $this;
  }

  public function setDimensions( $dimensions ) {
    $this->dimensions = $dimensions;
    return $this;
  }

  public function setId( $id ) {
    $this->id = $id;
    return $this;
  }

  public function setInvalid() {
    $this->invalid = true;
    return $this;
  }

  public function setImages( $images ) {
    $this->images = $images;
    return $this;
  }

  public function setParentStoreProps( $parentStoreProps ) {
    $props = [];

    if ( isset ( $parentStoreProps['storeId'] ) ) {
      $props['storeId'] = $parentStoreProps['storeId'];
    }
    
    if ( isset ( $parentStoreProps['storeName'] ) ) {
      $props['storeName'] = $parentStoreProps['storeName'];
    }

    if ( isset ( $parentStoreProps['productId'] ) ) {
      $props['productId'] = $parentStoreProps['productId'];
    }

    if ( isset( $parentStoreProps['categoryId'] ) ) {
      $props['categoryId'] = $parentStoreProps['categoryId'];
    }

    $this->parentStoreProps = $props;
    return $this;
  }

  public function setPrice( $price ) {
    $this->price = $this->sanitizePrice( $price );
    return $this;
  }

  public function setSku( $sku ) {
    if ( is_null( $sku ) ) {
      $sku = '';
    }

    $this->sku = $sku;
    return $this;
  }

  public function setTitle( $title ) {
    $this->title = $title;
    return $this;
  }

  public function setVariations( $variations ) {
    $this->variations = $variations;
    return $this;
  }

  public function setWeight( $weight ) {
    $this->dimensions['weight'] = $weight;
    return $this;
  }

  private function sanitizePrice( string $priceString ) : float {
    $price = (float) $priceString;//sanitize_text_field( $priceString );
		return round( max( $price, 0 ), 2 );
  }
}