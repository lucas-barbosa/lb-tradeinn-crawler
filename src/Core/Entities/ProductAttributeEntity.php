<?php

namespace LucasBarbosa\LbTradeinnCrawler\Core\Entities;

class ProductAttributeEntity {
  private ?bool $variable = null;
  private string $id = '';
  private string $name = '';
  private string $variationId = '';
  private array $value;

  public function __construct( $id, $name, $value, $variationId = '' ) {
    $this->id = $id;
    $this->name = $name;
    $this->value = is_array( $value ) ? $value : [$value];
    $this->variationId = $variationId;
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

  public function getVariationId() : string {
    return $this->variationId;
  }

  public function isVariation() {
    if ( ! is_null( $this->variable ) ) {
      return $this->variable;
    }

    $isVariableAttributeName = in_array( strtoupper( $this->getName() ), [ 'COR', 'TAMANHO' ] );
    return $isVariableAttributeName && count( $this->value ) > 1 ? '1' : '0';
  }

  public function setVariable( $variable ) {
    $this->variable = $variable;
  }
}
