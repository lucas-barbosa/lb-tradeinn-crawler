<?php

namespace LucasBarbosa\LbTradeinnCrawler\Core\Entities;

class ProductAttributeEntity {
  private string $id;
  private string $name;
  private array $value;

  public function __construct( $id, $name, $value ) {
    $this->id = $id;
    $this->name = $name;
    $this->value = is_array( $value ) ? $value : [$value];
  }

  public function getId() : string {
    return $this->id;
  }
  
  public function getName() : string {
    return $this->name;
  }

  public function getValue() : array {
    return $this->value;
  }

  public function isVariation() {
    return count( $this->value ) > 1 ? '1' : '0';
  }
}
