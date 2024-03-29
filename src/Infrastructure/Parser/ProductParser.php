<?php

namespace LucasBarbosa\LbTradeinnCrawler\Infrastructure\Parser;

use DOMXPath;
use LucasBarbosa\LbTradeinnCrawler\Core\Entities\ProductAttributeEntity;
use LucasBarbosa\LbTradeinnCrawler\Core\Entities\ProductEntity;
use LucasBarbosa\LbTradeinnCrawler\Core\Entities\ProductVariationEntity;
use LucasBarbosa\LbTradeinnCrawler\Core\Interfaces\IProductParser;

class ProductParser implements IProductParser {
  private ?DOMXPath $xpath;
  private $translatedColors = [];

  public function getProduct( $itemProps, array $data ) : ProductEntity {
    $this->xpath = Utils::getXPath( $data['site'] );

    $product = (new ProductEntity( $this->getTitle( $data ), $this->getId( $data ) ))
      ->setAttributes( $this->getAttributes( $data ) )
      ->setBrand( $this->getBrand( $data ) )
      ->setCategories( $this->getCategories( $itemProps['storeId'], $itemProps['storeName'], $data ) )
      ->setDescription( $this->getDescription() )
      ->setImages( $this->getImages() )
      ->setSku( $this->getSku( $data ) )
      ->setVariations( $this->getVariations( $data ) )
      ->setParentStoreProps( $itemProps );
    
    return $product;
  }

  private function getAttributes( $data ) {
    if ( ! isset( $data['json']['atributos'] ) || ! is_array( $data['json']['atributos'] ) ) {
      return [];
    }

    $rawAttributes = [];

    foreach ( $data['json']['atributos'] as $attribute ) {
      $id = $attribute['id_atributo'];
      $name = $attribute['nombre_atributo'];
      $values = [];

      foreach ( $attribute['atributos_valores'] as $value ) {
        $values[] = [
          'id' => $value['id_atributo_valor'],
          'value' => $value['nombre_atributo_valor']
        ];
      }

      if ( empty( $values ) ) {
        continue;
      }

      $rawAttributes[$name] = [ 'id' => $id, 'values' => $values ];
    }

    $colorIsVariable = false;
    $lastColor = '';
    
    foreach ( $data['json']['id_productes'] as $product ) {
      $offers = array_filter( $product['sellers'], function ( $offer ) {
        return $offer['id_seller'] == 1;
      } );

      if ( count( $offers ) === 0 ) {
        continue;
      }

      if ( ! isset( $rawAttributes['Cor'] ) ) {
        $rawAttributes['Cor'] = [ 'id' => '' ];
      }

      if ( ! isset( $rawAttributes['Tamanho'] ) ) {
        $rawAttributes['Tamanho'] = [ 'id' => '' ];
      }

      $translatedColor = isset( $this->translatedColors[$product['color']] ) 
        ? $this->translatedColors[$product['color']]
        : Utils::translate( $product['color'], 'en', 'pt-br', false, 'term', 'title' );

      if ( ! empty( $translatedColor ) ) {
        $this->translatedColors[$product['color']] = $translatedColor;
      }

      $rawAttributes['Cor']['values'][] = [ 'id' => '', 'value' => $product['color'], 'variationId' => $product['id_producte'], 'translated_value' => $translatedColor ];
      $rawAttributes['Tamanho']['values'][] = [ 'id' => '', 'value' => $product['talla'] === 'One Size' ? 'Tamanho Único' : $product['talla'], 'variationId' => $product['id_producte'] ];

      if ( ! empty( $lastColor ) && $lastColor !== $product['color'] ) {
        $colorIsVariable = true;
      }

      $lastColor = $product['color'];
    }
    
    $result = [];

    foreach ( $rawAttributes as $name => $attribute ) {
      $item = new ProductAttributeEntity( $attribute['id'], $name, $attribute['values'] );

      if ( strtoupper( $name ) === 'COR' ) {
        $item->setVariable( $colorIsVariable );
      }
      
      $result[] = $item;
    }

    return $result;
  }

  private function getBrand( $data ) {
    if ( isset( $data['json']['marca'] ) ) {
      return $data['json']['marca'];
    }

    return '';
  }

  private function getCategories( $storeId, $storeName, $data ) {
    if ( ! isset( $data['json']['nombre_familia'] ) && ! isset( $data['json']['nombre_subfamilia'] ) ) {
      return [
        [ 'name' => $storeName, 'id' => $storeId ]
      ];
    }

    $categories = [
      [ 'name' => $storeName, 'id' => $storeId ],
      [ 'name' => $data['json']['nombre_familia'], 'id' => $data['json']['id_familia'] ],
      [ 'name' => $data['json']['nombre_subfamilia'], 'id' => $data['json']['id_subfamilia'] ],
    ];
    
    return array_filter( $categories );
  }

  private function getDescription() {
    $value = $this->getValue( '//span[@id="desc"]', true );

    if ( empty( $value) ) {
      return '';
    }

    return Utils::purifyHTML( array_shift( $value ) );
  }

  private function getId( $data ) {
    return $data['json']['id_modelo'];
  }

  private function getImages() {
    $images = Utils::getPropertyValue( $this->xpath, '//div[@id = "bigImg"]//p[contains(@class, "swiper-slide")]//img', 'src' );
    $images = array_slice( $images, 0, apply_filters( 'lb_crawler_image_quantity', 7 ) );
    
    return array_map( function( $image ) {
      return 'https://www.tradeinn.com/' . $image;
    }, $images );
  }

  private function getSku( $data ) {
    $value = Utils::getPropertyValue( $this->xpath, '//meta[@itemprop = "sku"]' );

    if ( is_array( $value ) && count( $value ) > 0) {
      return $value[0];
    }

    if ( empty( $value ) ) {
      $id = $data['json']['id_modelo'];
      return empty( $id ) ? '' : $id;
    }

    return $value;
  }

  private function getTitle( $data ) {
    if ( isset( $data['json']['modelo'] ) ) {
      return $data['json']['modelo'];
    }

    return '';
  }

  private function getValue( $selector, $html = false ) {
    return Utils::getValue( $this->xpath, $selector, $html );
  }

  private function getVariations( $data ) {
    if ( ! isset( $data['json']['id_productes'] ) || ! is_array( $data['json']['id_productes'] ) || count( $data['json']['id_productes'] ) === 0 ) {
      return [];
    }

    $variations = [];

    foreach ( $data['json']['id_productes'] as $product ) {
      $offers = array_filter( $product['sellers'], function ( $offer ) {
        return $offer['id_seller'] == 1;
      } );

      if ( count( $offers ) === 0 ) {
        continue;
      }

      $offer = array_shift( $offers );

      $color = isset( $this->translatedColors[$product['color']] ) ? $this->translatedColors[$product['color']] : $product['color'];

      $variation = (new ProductVariationEntity())
        ->setId( $product['id_producte'] )
        ->setAttributes( [
          new ProductAttributeEntity( '695', 'Cor', $color, $product['id_producte'] ),
          new ProductAttributeEntity( '', 'Tamanho', $product['talla'], $product['id_producte'] ),
        ])
        ->setAvailability( $offer['plazo_entrega'], $product['exist'], $product['stock_reservat'] )
        ->setDimensions( [
          'width'  => $product['width'],
          'height' => $product['height'],
          'length' => $product['lenght'],
          'weight' => Utils::convertWeightUnitToWoocommerce( $product['peso'] )
        ])
        ->setEan( $product['ean'] )
        ->setEstimateDate( [
          $offer['plazo_entrega'],
          $offer['plazo_entrega2']
        ] )
        ->setPrice( $offer['precio'] );

      $variations[] = $variation;
    }

    return $variations;
  }
}