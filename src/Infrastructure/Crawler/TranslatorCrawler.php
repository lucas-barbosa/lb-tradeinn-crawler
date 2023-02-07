<?php

namespace LucasBarbosa\LbTradeinnCrawler\Infrastructure\Crawler;

use LucasBarbosa\LbTradeinnCrawler\Core\Entities\ProductEntity;

class TranslatorCrawler extends ProductCrawler {
  protected static $HOOK_NAME = 'lb_tradeinn_translator_crawler';

  private string $productId;
  private string $currentLanguage;

  public function setHooks() {
    if ( ! defined( 'LB_MULTI_LANGUAGES' ) ) {
      return;
    }
    
    add_action( self::$HOOK_NAME, array( $this, 'execute' ) );
    add_action( 'lb_tradeinn_product_created', array( $this, 'enqueueProduct' ) );
  }  

  public function enqueueProduct( $params, $hook = ''  ) {
    parent::enqueueProduct( $params, self::$HOOK_NAME );
  }

  public function execute( array $params ) {
    $languages = [
      [ 'name' => 'ingles', 'symbol' => 'eng' ],
      [ 'name' => 'espanhol', 'symbol' => 'spa' ]
    ];

    $productId = $params[0];
    $this->productId = $productId;

    foreach ( $languages as $language ) {
      if ( apply_filters( 'lb_multi_language_product_has_translation', false, $productId, $language['name'] ) ) {
        // continue;
      }

      $productParams = $params[1];
      $productParams['language'] = $language['symbol'];
      $this->currentLanguage = $language['name'];

      parent::execute( $productParams );
    }
  }

  public function onProductFound( ProductEntity $productData ) {
    do_action( 'lb_tradeinn_crawler_translation_loaded', $productData, $this->productId, $this->currentLanguage );
  }
}