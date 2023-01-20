<?php

namespace LucasBarbosa\LbTradeinnCrawler\Infrastructure\Crawler;

use LucasBarbosa\LbTradeinnCrawler\Core\Entities\CategoriesResultEntity;
use LucasBarbosa\LbTradeinnCrawler\Core\Interfaces\ICategoriesCrawler;
use LucasBarbosa\LbTradeinnCrawler\Core\Interfaces\ICategoriesParser;
use LucasBarbosa\LbTradeinnCrawler\Infrastructure\Data\SettingsData;

class CategoriesCrawler extends Crawler implements ICategoriesCrawler  {
  private ICategoriesParser $parser;

  public function __construct( ICategoriesParser $parser ) {
    $this->parser = $parser;
  }

  public function execute() : CategoriesResultEntity {
    $data = $this->requestData();

    $categories = [];
    $stores = $this->parser->getStores( $data );

    foreach ( $stores as $store ) {
      $storeResponse = $this->requestStoreCategories( $store );
      $storeCategories = $this->parser->getCategories( $storeResponse );
      
      $categories[] = [
        'name'   => $store['name'],
        'id'     => $store['id'],
        'childs' => $storeCategories->getCategories()
      ];
    }

    $result = new CategoriesResultEntity();
    $result->setCategories( $categories );

    $this->onCategoriesFound( $result );

    return $result;
  }

  public function requestData() {
    $client = $this->getClient();

    $response = $client->get( $this->baseUrl );

    return $response->getBody();
  }

  public function requestStoreCategories( $store ) {
    $client = $this->getClient();

    $response = $client->request( 'GET', $this->baseUrl . 'index.php', [
      'query' => [
        'action'  => 'menu_categorias',
        'idioma'  => 'por',
        'id_tienda' => $store['id'],
        'tiendas' => 1
      ]
    ] );

    return $response->getBody();
  }

  public function onCategoriesFound( CategoriesResultEntity $result ) {
    $categories = $result->getCategories();
    SettingsData::saveCategories( $categories );
  }
}