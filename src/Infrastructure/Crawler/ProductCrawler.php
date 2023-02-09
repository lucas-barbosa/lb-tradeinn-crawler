<?php

namespace LucasBarbosa\LbTradeinnCrawler\Infrastructure\Crawler;

use LucasBarbosa\LbTradeinnCrawler\Core\Entities\ProductEntity;
use LucasBarbosa\LbTradeinnCrawler\Core\Interfaces\IProductCrawler;
use LucasBarbosa\LbTradeinnCrawler\Core\Interfaces\IProductParser;
use LucasBarbosa\LbTradeinnCrawler\Infrastructure\Data\Products;

class ProductCrawler extends Crawler implements IProductCrawler {
  protected static $HOOK_NAME = 'lb_tradeinn_product_crawler';
  protected IProductParser $parser;

  public function __construct( IProductParser $parser ) {
    $this->parser = $parser;
  }

  public function setHooks() {
    add_action( self::$HOOK_NAME, array( $this, 'execute' ) );
    add_action( 'lb_tradeinn_crawler_product_found', array( $this, 'enqueueProduct' ) );
  }

  public function enqueueProduct( $params, $hook = '' ) {
    if ( empty( $hook ) ) {
      $hook = self::$HOOK_NAME;
    }

    if ( Products::isAlreadyCrawled( $params['storeName'], $params['productId'] ) ) {
      return;
    }
    
    $has_action = as_has_scheduled_action( $hook, array( $params ), $this->groupSlug );

    if ( $has_action ) return;

    as_enqueue_async_action( $hook, array( $params ), $this->groupSlug );
  }

  public function execute( array $productParams ) {
    $data = $this->requestData( $productParams );

    if ( is_null( $data ) ) {
      return;
    }

    $product = $this->parser->getProduct( $productParams, $data );

    $this->onProductFound( $product );
  }

  public function requestData( array $productParams ) {
    $jsonData = $this->getJsonData( $productParams );

    if ( is_null( $jsonData ) ) {
      return null;
    }

    $siteData = $this->getSiteData( $productParams );

    return [
      'json' => $jsonData,
      'site' => $siteData
    ];
  }

  public function onProductFound( ProductEntity $productData ) {
    do_action( 'lb_tradeinn_crawler_product_loaded', $productData );
  }

  protected function getJsonData( $props ) {
    $client = $this->getClient();
    
    $response = $client->get( $this->baseUrl . 'index.php', [
      'query' => [
        'action'     => 'get_datos_producto',
        'idioma'     => $props['language'],
        'id_tienda'  => $props['storeId'],
        'id_modelo'  => $props['productId'],
        'solo_altas' => 1
      ]
    ] );

    $json = json_decode( $response->getBody(), true );

    if ( $json === null && json_last_error() !== JSON_ERROR_NONE ) {
      return null;
    }

    return $json;
  }

  private function getSiteData( $props ) {
    $client = $this->getClient();
  
    $language = ! isset( $props['language'] ) || $props['language'] === 'por' ? 'pt' :
      ( $props['language'] === 'eng' ? 'en' :
        ( $props['language'] === 'spa' ? 'es' : $props['language'] ) );
        
    $productUrl = $this->baseUrl . $props['storeName']  . '/' . $language . '/-/' . $props['productId'] . '/p';
    
    $response = $client->get( $productUrl );

    return $response->getBody();
  }
}