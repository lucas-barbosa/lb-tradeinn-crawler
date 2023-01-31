<?php

namespace LucasBarbosa\LbTradeinnCrawler\Infrastructure\Crawler;

use LucasBarbosa\LbTradeinnCrawler\Core\Entities\CategoryResultEntity;
use LucasBarbosa\LbTradeinnCrawler\Core\Interfaces\ICategoryCrawler;
use LucasBarbosa\LbTradeinnCrawler\Core\Interfaces\ICategoryParser;
use LucasBarbosa\LbTradeinnCrawler\Infrastructure\Data\SettingsData;

class CategoryCrawler extends Crawler implements ICategoryCrawler  {
  public static $HOOK_NAME = 'lb_tradeinn_category_crawler';

  private $productQuantity = 48;
  private ICategoryParser $parser;

  public function __construct( ICategoryParser $parser ) {
    $this->parser = $parser;
  }

  public function setHooks() {
    add_action( self::$HOOK_NAME, array( $this, 'execute' ) );
    add_action( 'lb_tradeinn_selected_categories_crawler', array( $this, 'enqueueCategories' ) );
  }

  function enqueueCategories() {
    $selectedCategories = SettingsData::getSelectedCategories();
    $selectedCategories = array_filter( $selectedCategories );

    foreach ( $selectedCategories as $category ) {
      $categoryData = explode( '|', $category );

      if ( count( $categoryData ) < 3 ) continue;

      $params = [ [
        'categoryId'    => $categoryData[2] ?? '',
        'subcategoryId' => $categoryData[3] ?? '',
        'page'  => 0,
        'language' => 'por',
        'store' => [
          'id'    => $categoryData[1] ?? '',
          'name'  => $categoryData[0] ?? ''
        ]
      ] ];

      $has_action = as_has_scheduled_action( self::$HOOK_NAME, $params, $this->groupSlug );

      if ( $has_action ) continue;

      as_schedule_recurring_action( strtotime('+60 seconds'), DAY_IN_SECONDS * 30, self::$HOOK_NAME, $params, $this->groupSlug );
    }
  }

  public function execute( $props ) : CategoryResultEntity {
    $data = $this->requestData( $props );

    $result = $this->parser->getProducts( [
      'body' => $data,
      'currentPage' => $props['page'],
      'productQuantity' => $this->productQuantity
    ] );

    $this->handleNextPage( $result->getHasNextPage(), $props['page'], $props );

    $this->onProductsFound( [
      'language'  => $props['language'],
      'products'  => $result->getProducts(),
      'storeId'   => $props['store']['id'],
      'storeName' => $props['store']['name'],
    ] );
    
    return $result;
  }

  public function requestData( array $props ) {
    $params = [
      'vars' => [
        'id_familia=' . $props['categoryId'],
        'atributos_e=5091,6017',
        'model.por;model.eng;video_mp4;id_marca;precio_tachado;sostenible;productes.talla2;productes.talla_usa;productes.talla_jp;productes.talla_uk;tres_sesenta;atributos_padre.atributos.id_atribut_valor;productes.v360;productes.v180;productes.v90;productes.v30;productes.exist;productes.stock_reservat;productes.pmp;productes.id_producte;productes.color;productes.referencia;productes.brut;productes.desc_brand;image_created;id_modelo;familias.eng;familias.por;familias.id_familia;familias.subfamilias.eng;familias.subfamilias.por;familias.subfamilias.id_tienda;familias.subfamilias.id_subfamilia;productes.talla;productes.baja;productes.rec;precio_win_159;productes.sellers.id_seller;productes.sellers.precios_paises.precio;productes.sellers.precios_paises.id_pais;fecha_descatalogado;marca',
        'v30_sum;desc@tm1;asc',
        $this->productQuantity,
        'productos',
        'search',
        'id_subfamilia=' . $props['subcategoryId'],
        $props['page'] * $this->productQuantity
      ],
      'texto_search'  => ''
    ];

    $client = $this->getClient();

    $response = $client->request( 'POST', 'https://www.tradeinn.com/index.php', [
      'query' => [
        'action'  => 'get_info_elastic_listado',
        'idioma'  => $props['language'],
        'id_tienda' => $props['store']['id'],
      ],
      'form_params' => $params
    ]);

    return $response->getBody();
  }

  public function onProductsFound( array $result ) {
    foreach ( $result['products'] as $product ) {
      $params = [
        'storeId' => $result['storeId'],
        'storeName' => $result['storeName'],
        'language'  => $result['language'],
        'productId' => $product        
      ];

      do_action( 'lb_tradeinn_crawler_product_found', $params );
    }
  }

  private function handleNextPage( bool $hasNextPage, int $currentPage, $params ) {
    if ( ! $hasNextPage ) {
      return;
    }

    $nextPage = $currentPage + 1;
    $props['page'] = $nextPage;

    as_enqueue_async_action( self::$HOOK_NAME, array( $props ), $this->groupSlug );  
  }
}