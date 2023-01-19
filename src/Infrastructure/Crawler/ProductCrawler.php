<?php

namespace LucasBarbosa\LbTradeinnCrawler\Infrastructure\Crawler;

use LucasBarbosa\LbTradeinnCrawler\Core\Entities\ProductEntity;
use LucasBarbosa\LbTradeinnCrawler\Core\Interfaces\IProductCrawler;
use LucasBarbosa\LbTradeinnCrawler\Core\Interfaces\IProductParser;

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