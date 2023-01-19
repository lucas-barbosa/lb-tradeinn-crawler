<?php

namespace LucasBarbosa\LbCrawlerTemplate\Core\Interfaces;

use LucasBarbosa\LbCrawlerTemplate\Core\Entities\ProductEntity;

interface IProductParser {
  public function getProduct( array $data ) : ProductEntity;
}