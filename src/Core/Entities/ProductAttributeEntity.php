<?php

namespace LucasBarbosa\LbTradeinnCrawler\Core\Entities;

class ProductAttributeEntity {
  private string $name;
  private array $value;

  public function __construct( $name, $value ) {
    $this->name = $name;
    $this->value = $value;
  }

  public function getName() : string {
    return $this->name;
  }

  public function getValue() : array {
    return $this->value;
  }
}
