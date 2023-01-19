<?php

namespace LucasBarbosa\LbTradeinnCrawler\Core\Interfaces;

use LucasBarbosa\LbTradeinnCrawler\Core\Entities\CategoryResultEntity;

interface ICategoryParser {
  public function getProducts( $data ) : CategoryResultEntity;
  public function hasNextPage( $data ) : bool;
}