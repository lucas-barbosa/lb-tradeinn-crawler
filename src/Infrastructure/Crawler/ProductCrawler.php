<?php

namespace LucasBarbosa\LbCrawlerTemplate\Infrastructure\Crawler;

use LucasBarbosa\LbCrawlerTemplate\Core\Entities\ProductEntity;
use LucasBarbosa\LbCrawlerTemplate\Core\Interfaces\IProductCrawler;
use LucasBarbosa\LbCrawlerTemplate\Core\Interfaces\IProductParser;

class ProductCrawler implements IProductCrawler {
  private IProductParser $parser;

  public function __construct( IProductParser $parser ) {
    $this->parser = $parser;
  }

  public function execute( array $productParams ) {
    $data = $this->requestData( $productParams );
    
    $product = $this->parser->getProduct( $data );

    $this->onProductFound( $product );
  }

  public function requestData( array $productParams ) {
    return [];
  }

  public function onProductFound( ProductEntity $productData ) {
    // CREATE PRODUCT
  }
}