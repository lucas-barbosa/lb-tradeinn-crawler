<?php

namespace LucasBarbosa\LbCrawlerTemplate\Core\Interfaces;

use LucasBarbosa\LbCrawlerTemplate\Core\Entities\CategoryResultEntity;

interface ICategoryCrawler {
  public function __construct( ICategoryParser $parser );
  public function execute( $props ) : CategoryResultEntity;
  public function requestData( array $props );
  public function onProductsFound( array $products );
}