<?php

namespace LucasBarbosa\LbCrawlerTemplate\Infrastructure\Crawler;

use LucasBarbosa\LbCrawlerTemplate\Core\Entities\CategoryResultEntity;
use LucasBarbosa\LbCrawlerTemplate\Core\Interfaces\ICategoryCrawler;
use LucasBarbosa\LbCrawlerTemplate\Core\Interfaces\ICategoryParser;

class CategoryCrawler implements ICategoryCrawler {
  private ICategoryParser $parser;

  public function __construct( ICategoryParser $parser ) {
    $this->parser = $parser;
  }

  public function execute( $props ) : CategoryResultEntity {
    $data = $this->requestData( $props );
    $result = $this->parser->getProducts( $data );

    $this->handleNextPage( $result->getHasNextPage() );
    $this->onProductsFound( $result->getProducts() );
    
    return $result;
  }

  public function requestData( array $props ) {
    return [];
  }

  public function onProductsFound( array $products ) {
    foreach ( $products as $product ) {
      // ENQUEUE ACTION
    }
  }

  private function handleNextPage( bool $hasNextPage ) {
    if ( ! $hasNextPage ) {
      return;
    }

    // ENQUEUE ACTION
  }
}