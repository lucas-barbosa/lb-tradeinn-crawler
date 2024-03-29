<?php

namespace LucasBarbosa\LbTradeinnCrawler\Core;

use LucasBarbosa\LbTradeinnCrawler\Core\Usecases\CreateProduct;
use LucasBarbosa\LbTradeinnCrawler\Core\Usecases\CreateTranslation;
use LucasBarbosa\LbTradeinnCrawler\Core\Usecases\RefreshStock;
use LucasBarbosa\LbTradeinnCrawler\Core\Usecases\RenderAdminSettings;
use LucasBarbosa\LbTradeinnCrawler\Core\Usecases\SaveSettingsAjax;
use LucasBarbosa\LbTradeinnCrawler\Core\Usecases\ValidateProductBrand;
use LucasBarbosa\LbTradeinnCrawler\Core\Usecases\ValidateProductWeight;

class InitCore {
  private string $plugin_name;
  private string $plugin_version;

  public function __construct( $plugin_name, $plugin_version ) {
    $this->plugin_name = $plugin_name;
    $this->plugin_version = $plugin_version;
  }
  
  public function load() {
    $createProduct = new CreateProduct();
    $createProduct->setHooks();

    $createTranslation = new CreateTranslation();
    $createTranslation->setHooks();

    $refreshStock = new RefreshStock();
    $refreshStock->setHooks();
    
    $validateProductBrand = new ValidateProductBrand();
    $validateProductBrand->setHooks();

    $validateProduct = new ValidateProductWeight();
    $validateProduct->setHooks();
    
    if ( is_admin() ) {
      $adminSettings = new RenderAdminSettings( $this->plugin_name, $this->plugin_version );
      $adminSettings->setHooks();
    }

    new SaveSettingsAjax();
  }
}