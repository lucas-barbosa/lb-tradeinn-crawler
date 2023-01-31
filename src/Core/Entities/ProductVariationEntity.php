<?php

namespace LucasBarbosa\LbTradeinnCrawler\Core\Entities;

class ProductVariationEntity {
  protected array $attributes;
  protected string $availability;
  protected array $dimensions;
  protected string $ean;
  protected string $id;
  protected array $estimatedDate;
  protected float $price;

  public function getAttributes() {
    return $this->attributes;
  }
  
  public function getAvailability() {
    return $this->availability;
  }
  
  public function getDimensions() {
    return $this->dimensions;
  }

  public function getEan() {
    return $this->ean;
  }

  public function getEstimateDate() {
    return $this->estimatedDate;
  }
  
  public function getId() {
    return $this->id;
  }
  
  public function getPrice() {
    return $this->price;
  }

  public function setAttributes( $attributes ) {
    $this->attributes = $attributes;
    return $this;
  }

  public function setAvailability( $availability ) {
    $this->availability = $availability;
    return $this;
  }

  public function setDimensions( $dimensions ) {
    $this->dimensions = $dimensions;
    return $this;
  }

  public function setEan( $ean ) {
    $this->ean = $ean;
    return $this;
  }

  public function setEstimateDate( $estimateDate ) {
    $this->estimatedDate = $estimateDate;
    return $this;
  }

  public function setId( $id ) {
    $this->id = $id;
    return $this;
  }

  public function setPrice( $price ) {
    $this->price = $this->sanitizePrice( $price );
    return $this;
  }

  private function sanitizePrice( string $priceString ) : float {
    $price = (float) $priceString;//sanitize_text_field( $priceString );
		return round( max( $price, 0 ), 2 );
  }
}