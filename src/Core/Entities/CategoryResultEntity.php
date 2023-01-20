<?php

namespace LucasBarbosa\LbTradeinnCrawler\Core\Entities;

class CategoryResultEntity {
  protected array $products;
  protected bool $hasNextPage;

  public function __construct() {
    $this->products = [];
  }

  public function addProduct( array $product ) {
    $this->products[] = $product;
  }

  public function getHasNextPage() {
    return $this->hasNextPage;
  }

  public function getProducts() {
    return $this->products;
  }

  public function setHasNextPage( bool $hasNextPage ) {
    $this->hasNextPage = $hasNextPage;
    return $this;
  }

  public function setProducts( array $products ) {
    $this->products = $products;
    return $this;
  }
}