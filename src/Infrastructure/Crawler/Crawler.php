<?php

namespace LucasBarbosa\LbTradeinnCrawler\Infrastructure\Crawler;

use GuzzleHttp\Client;

abstract class Crawler {
  protected $baseUrl = 'https://www.tradeinn.com/';
  protected $groupSlug = 'lb_tradeinn_crawler';
  
  protected function getClient() {
    $client = new Client();
    return $client;
  }
}