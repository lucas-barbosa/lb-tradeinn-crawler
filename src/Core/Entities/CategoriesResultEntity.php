<?php

namespace LucasBarbosa\LbCrawlerTemplate\Core\Entities;

class CategoriesResultEntity {
  protected array $categories;

  public function __construct() {
    $this->categories = [];
  }

  public function getCategories() {
    return $this->categories;
  }

  public function setCategories( array $categories ) {
    $this->categories = $categories;
    return $this;
  }
}