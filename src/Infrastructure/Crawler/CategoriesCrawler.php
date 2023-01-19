<?php

namespace LucasBarbosa\LbTradeinnCrawler\Infrastructure\Crawler;

use LucasBarbosa\LbTradeinnCrawler\Core\Entities\CategoriesResultEntity;
use LucasBarbosa\LbTradeinnCrawler\Core\Interfaces\ICategoriesCrawler;
use LucasBarbosa\LbTradeinnCrawler\Core\Interfaces\ICategoriesParser;

class CategoriesCrawler implements ICategoriesCrawler {
  private ICategoriesParser $parser;

  public function __construct( ICategoriesParser $parser ) {
    $this->parser = $parser;
  }

  public function execute() : CategoriesResultEntity {
    $data = $this->requestData();

    $categories = $this->parser->getCategories( $data );

    $this->onCategoriesFound( $categories );

    return $categories;
  }

  public function requestData() {
    return [];
  }

  public function onCategoriesFound( $categories ) {
    foreach ( $categories as $categoy ) {

    }
  }
}