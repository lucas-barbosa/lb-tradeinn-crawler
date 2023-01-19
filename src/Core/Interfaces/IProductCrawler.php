<?php

namespace LucasBarbosa\LbCrawlerTemplate\Core\Interfaces;

use LucasBarbosa\LbCrawlerTemplate\Core\Entities\ProductEntity;

interface IProductCrawler {
  public function __construct( IProductParser $parser );
  public function execute( array $productParams );
  public function requestData( array $productParams );
  public function onProductFound( ProductEntity $productData );
}