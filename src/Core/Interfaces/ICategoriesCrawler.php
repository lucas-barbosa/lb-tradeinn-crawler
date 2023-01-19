<?php

namespace LucasBarbosa\LbTradeinnCrawler\Core\Interfaces;

use LucasBarbosa\LbTradeinnCrawler\Core\Entities\CategoriesResultEntity;

interface ICategoriesCrawler {
  public function __construct( ICategoriesParser $parser );
  public function execute() : CategoriesResultEntity;
  public function requestData();
  public function onCategoriesFound( CategoriesResultEntity $categories );
}