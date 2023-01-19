<?php

namespace LucasBarbosa\LbCrawlerTemplate\Core\Interfaces;

use LucasBarbosa\LbCrawlerTemplate\Core\Entities\CategoriesResultEntity;

interface ICategoriesParser {
  public function getCategories( array $data ) : CategoriesResultEntity;
}