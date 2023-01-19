<?php

namespace LucasBarbosa\LbTradeinnCrawler\Core\Entities;

class ProductEntity {
  protected array $attributes;
  protected string $availability;
  protected string $brand;
  protected array $categories;
  protected string $description;
  protected array $dimensions;
  protected array $features;
  protected string $id;
  protected array $images;
  protected array $parentStoreProps;
  protected float $price;
  protected string $shortDescription;
  protected string $sku;
  protected string $title;
  protected array $variations;

  public function __construct( string $title, string $price, string $id ) {
    $this->id = $id;
    $this->title = $title;
    $this->price = $this->sanitizePrice( $price );
    return $this;
  }

  public function getAttributes() {
    return $this->attributes;
  }
  
  public function getAvailability() {
    return $this->availability;
  }
  
  public function getBrand() {
    return $this->brand;
  }
  
  public function getCategories() {
    return $this->categories;
  }
  
  public function getDescription() {
    return $this->description;
  }
  
  public function getDimensions() {
    return $this->dimensions;
  }
  
  public function getFeatures() {
    return $this->features;
  }
  
  public function getId() {
    return $this->id;
  }
  
  public function getImages() {
    return $this->images;
  }
  
  public function getParentStoreProps() {
    return $this->parentStoreProps;
  }
  
  public function getPrice() {
    return $this->price;
  }
  
  public function getShortDescription() {
    return $this->shortDescription;
  }
  
  public function getSku() {
    return $this->sku;
  }
  
  public function getTitle() {
    return $this->title;
  }
  
  public function getVariations() {
    return $this->variations;
  }
  
  public function setAttributes( $attributes ) {
    $this->attributes = $attributes;
    return $this;
  }

  public function setAvailability( $availability ) {
    $this->availability = $availability;
    return $this;
  }

  public function setBrand( $brand ) {
    $this->brand = $brand;
    return $this;
  }

  public function setCategories( $categories ) {
    $this->categories = $categories;
    return $this;
  }

  public function setDescription( $description ) {
    $this->description = $description;
    return $this;
  }

  public function setDimensions( $dimensions ) {
    $this->dimensions = $dimensions;
    return $this;
  }

  public function setFeatures( $features ) {
    $this->features = $features;
    return $this;
  }

  public function setId( $id ) {
    $this->id = $id;
    return $this;
  }

  public function setImages( $images ) {
    $this->images = $images;
    return $this;
  }

  public function setParentStoreProps( $parentStoreProps ) {
    $this->parentStoreProps = $parentStoreProps;
    return $this;
  }

  public function setPrice( $price ) {
    $this->price = $price;
    return $this;
  }

  public function setShortDescription( $shortDescription ) {
    $this->shortDescription = $shortDescription;
    return $this;
  }

  public function setSku( $sku ) {
    $this->sku = $sku;
    return $this;
  }

  public function setTitle( $title ) {
    $this->title = $title;
    return $this;
  }

  public function setVariations( $variations ) {
    $this->variations = $variations;
    return $this;
  }


  private function sanitizePrice( string $priceString ) : float {
    $price = (float) sanitize_text_field( $priceString );
		return round( max( $price, 0 ), 2 );
  }
}