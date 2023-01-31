<?php

namespace LucasBarbosa\LbTradeinnCrawler\Infrastructure\Crawler;

use GuzzleHttp\Client;
use GuzzleHttp\Cookie\CookieJar;

abstract class Crawler {
  protected $baseUrl = 'https://www.tradeinn.com/';
  protected $groupSlug = 'lb_tradeinn_crawler';
  
  protected function getClient() {
    $cookies = CookieJar::fromArray([
      'id_pais' => 159
    ], 'www.tradeinn.com' );

    $client = new Client([ 'cookies' => $cookies ]);

    return $client;
  }
}