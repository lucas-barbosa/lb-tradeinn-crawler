<?php

namespace LucasBarbosa\LbTradeinnCrawler\Infrastructure\Crawler;

use LucasBarbosa\LbTradeinnCrawler\Core\Entities\ProductEntity;

class RefreshStockCrawler extends ProductCrawler {
  protected static $HOOK_NAME = 'lb_tradeinn_product_update';

  private string $productId;

  public function setHooks() {
    add_action( self::$HOOK_NAME, array( $this, 'execute' ) );
    add_action( 'lb_tradeinn_product_created', array( $this, 'enqueueProduct' ) );
  }  

  public function enqueueProduct( $params, $hook = ''  ) {
    $has_action = as_has_scheduled_action( self::$HOOK_NAME, array( $params ), $this->groupSlug );

    if ( $has_action ) {
      return;
    }

    as_schedule_recurring_action( strtotime('+2 days'), DAY_IN_SECONDS * 2, self::$HOOK_NAME, array( $params ), $this->groupSlug );
  }

  public function execute( array $params ) {
    $this->productId = $params[0];

    $product = wc_get_product( $this->productId );

    if ( ! $product ) {
      as_unschedule_all_actions( self::$HOOK_NAME, array( $params ), $this->groupSlug );
      return;
    }

    $productParams = $params[1];
    $productParams['language'] = 'por';

    $data = [
      'json' => parent::getJsonData( $productParams ),
      'site' => ''
    ];

    if ( empty( $data['json'] ) ) {
      return;
    }

    $product = $this->parser->getProduct( $productParams, $data );
    
    $this->onProductFound( $product );
  }

  public function onProductFound( ProductEntity $productData ) {
    do_action( 'lb_tradeinn_crawler_update_product_stock', $productData, $this->productId );
  }
}