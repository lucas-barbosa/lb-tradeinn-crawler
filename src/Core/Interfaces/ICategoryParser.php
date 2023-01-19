<?php

namespace LucasBarbosa\LbCrawlerTemplate\Core\Interfaces;

use LucasBarbosa\LbCrawlerTemplate\Core\Entities\CategoryResultEntity;

interface ICategoryParser {
  public function getProducts( $data ) : CategoryResultEntity;
  public function hasNextPage( $data ) : bool;
}