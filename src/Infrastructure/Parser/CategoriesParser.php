<?php

namespace LucasBarbosa\LbTradeinnCrawler\Infrastructure\Parser;

use DOMXPath;
use LucasBarbosa\LbTradeinnCrawler\Core\Entities\CategoriesResultEntity;
use LucasBarbosa\LbTradeinnCrawler\Core\Interfaces\ICategoriesParser;

class CategoriesParser implements ICategoriesParser {
  public function getCategories( string $data ) : CategoriesResultEntity {
    $xpath = Utils::getXPath( $data );
    $result = new CategoriesResultEntity();

    $categories = $this->getParentCategories( $xpath );
    $categories = $this->getChildCategories( $xpath, $categories );

    $result->setCategories( $categories );

    return $result;
  }

  public function getStores( string $data ) : array {
    $xpath = Utils::getXPath( $data );

    $stores = [];
    $storeItems = $xpath->query( '//ul[@id="headertiendas_bricoinn"]/li/a' );
    
    foreach ( $storeItems as $storeItem ) {
      $storeName = explode( '/', trim( $storeItem->getAttribute( 'href' ), '/' ) )[0];
      $storeId = $storeItem->getAttribute( 'data-shop' );
      
      $stores[] = [
        'name' => $storeName,
        'id'   => $storeId
      ];
    }

    return $stores;
  }
  
  private function getParentCategories ( DOMXPath $xpath ) {
    $categories = [];
    $parentCategoriesItems = $xpath->query( '//li[starts-with(@id, "cat-padre")]');

    foreach ( $parentCategoriesItems as $parentCategoryItem ) {
      $categoryName = $parentCategoryItem->nodeValue;
      $categoryId = str_ireplace( 'cat-padre', '', $parentCategoryItem->getAttribute( 'id' ) );
      
      $categories[] = [
        'name'  => $categoryName,
        'id'    => $categoryId
      ];
    }

    return $categories;
  }

  private function getChildCategories( DOMXPath $xpath, $categories ) {
    $result = [];

    foreach ( $categories as $category ) {
      $childs = [];

      $childCategoriesItems = $xpath->query( "//div[@id = 'cat_all{$category['id']}']//li/a" );
      
      foreach ( $childCategoriesItems as $childCategoryItem ) {
        $categoryName = $childCategoryItem->nodeValue;
        $categoryUrl  = $childCategoryItem->getAttribute( 'href' );

        $childs[] = [
          'name'  => $categoryName,
          'url'   => $categoryUrl
        ];
      }

      $result[] = [
        'name'    => $category['name'],
        'id'      => $category['id'],
        'childs'  => $childs
      ];
    }

    return $result;
  }
}