<?php

namespace LucasBarbosa\LbTradeinnCrawler\Core\Interfaces;

use LucasBarbosa\LbTradeinnCrawler\Core\Entities\ProductEntity;

interface IProductCrawler {
  public function __construct( IProductParser $parser );
  public function execute( array $productParams );
  public function requestData( array $productParams );
  public function onProductFound( ProductEntity $productData );
}