<?php

namespace LucasBarbosa\LbCrawlerTemplate\Core\Interfaces;

use LucasBarbosa\LbCrawlerTemplate\Core\Entities\CategoriesResultEntity;

interface ICategoriesCrawler {
  public function __construct( ICategoriesParser $parser );
  public function execute() : CategoriesResultEntity;
  public function requestData();
  public function onCategoriesFound( CategoriesResultEntity $categories );
}