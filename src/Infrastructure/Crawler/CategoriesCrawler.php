<?php

namespace LucasBarbosa\LbCrawlerTemplate\Infrastructure\Crawler;

use LucasBarbosa\LbCrawlerTemplate\Core\Entities\CategoriesResultEntity;
use LucasBarbosa\LbCrawlerTemplate\Core\Interfaces\ICategoriesCrawler;
use LucasBarbosa\LbCrawlerTemplate\Core\Interfaces\ICategoriesParser;

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