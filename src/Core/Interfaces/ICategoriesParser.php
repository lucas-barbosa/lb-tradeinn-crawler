<?php

namespace LucasBarbosa\LbTradeinnCrawler\Core\Interfaces;

use LucasBarbosa\LbTradeinnCrawler\Core\Entities\CategoriesResultEntity;

interface ICategoriesParser {
  public function getCategories( array $data ) : CategoriesResultEntity;
}