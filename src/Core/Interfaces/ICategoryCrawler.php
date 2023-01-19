<?php

namespace LucasBarbosa\LbTradeinnCrawler\Core\Interfaces;

use LucasBarbosa\LbTradeinnCrawler\Core\Entities\CategoryResultEntity;

interface ICategoryCrawler {
  public function __construct( ICategoryParser $parser );
  public function execute( $props ) : CategoryResultEntity;
  public function requestData( array $props );
  public function onProductsFound( array $products );
}