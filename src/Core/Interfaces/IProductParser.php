<?php

namespace LucasBarbosa\LbTradeinnCrawler\Core\Interfaces;

use LucasBarbosa\LbTradeinnCrawler\Core\Entities\ProductEntity;

interface IProductParser {
  public function getProduct( array $data ) : ProductEntity;
}