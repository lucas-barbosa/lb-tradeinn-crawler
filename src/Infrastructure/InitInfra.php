<?php

namespace LucasBarbosa\LbTradeinnCrawler\Infrastructure;

use LucasBarbosa\LbTradeinnCrawler\Infrastructure\Crawler\CategoriesCrawler;
use LucasBarbosa\LbTradeinnCrawler\Infrastructure\Crawler\CategoryCrawler;
use LucasBarbosa\LbTradeinnCrawler\Infrastructure\Crawler\ProductCrawler;
use LucasBarbosa\LbTradeinnCrawler\Infrastructure\Parser\CategoriesParser;
use LucasBarbosa\LbTradeinnCrawler\Infrastructure\Parser\CategoryParser;
use LucasBarbosa\LbTradeinnCrawler\Infrastructure\Parser\ProductParser;

class InitInfra {
  public function load() {
    $categoriesCrawler = new CategoriesCrawler( new CategoriesParser() );
    $categoriesCrawler->setHooks();
    
    $categoryCrawler = new CategoryCrawler( new CategoryParser() );
    $categoryCrawler->setHooks();

    $productCrawler = new ProductCrawler( new ProductParser() );
    $productCrawler->setHooks();
  }
}