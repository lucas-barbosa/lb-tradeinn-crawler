<?php

namespace LucasBarbosa\LbTradeinnCrawler\Core;

use LucasBarbosa\LbTradeinnCrawler\Core\Usecases\CreateProduct;

class InitCore {
  public function load() {
    $createProduct = new CreateProduct();
    $createProduct->setHooks();
  }
}