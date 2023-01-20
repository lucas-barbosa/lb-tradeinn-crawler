<?php

namespace LucasBarbosa\LbTradeinnCrawler\Infrastructure\Parser;

use LucasBarbosa\LbTradeinnCrawler\Core\Entities\CategoryResultEntity;
use LucasBarbosa\LbTradeinnCrawler\Core\Interfaces\ICategoryParser;

class CategoryParser implements ICategoryParser {
  public function getProducts( $data ) : CategoryResultEntity {
    $result = new CategoryResultEntity();
    $json = json_decode( $data['body'], true );

    if ( $json === null && json_last_error() !== JSON_ERROR_NONE ) {
      $result->setHasNextPage( false );
      return $result;
    }

    $hasNextPage = $this->hasNextPage( [
      'json'  => $json,
      'currentPage' => $data['currentPage'],
      'productQuantity' => $data['productQuantity']
    ] );

    $products = $this->getProductsFromJson( $json );
    
    $result->setHasNextPage( $hasNextPage )->setProducts( $products );

    return $result;
  }
  
  public function getProductsFromJson( $json ) {
    if ( ! isset( $json['id_modelos'] ) ) {
      return [];
    }
  
    return array_map( function ( $item ) {
      return $item['id_modelo'];
    }, $json['id_modelos'] );
  }

  public function hasNextPage( $data ) : bool {
    $json = $data['json'];
    $currentPage = $data['currentPage'];
    $productQuantity = $data['productQuantity'];

    if ( ! isset( $json['total'] ) || ! isset( $json['total']['value'] ) ) {
      $hasNext = false;
    } else {
      $totalProducts = $json['total']['value'];
      $hasNext = ( $currentPage + 1 ) * $productQuantity < $totalProducts;
    }

    if ( isset( $json['id_modelos'] ) ) {
      if ( is_array( $json['id_modelos'] ) && count( $json['id_modelos'] ) === 0 ) {
        $hasNext = false;
      }
    }

    return $hasNext;
  }
}