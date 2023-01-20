<?php

namespace LucasBarbosa\LbTradeinnCrawler\Core\Interfaces;

use LucasBarbosa\LbTradeinnCrawler\Core\Entities\CategoriesResultEntity;

interface ICategoriesParser {
  public function getCategories( string $data ) : CategoriesResultEntity;
  public function getStores( string $data ) : array;
}