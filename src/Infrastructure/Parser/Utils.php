<?php

namespace LucasBarbosa\LbTradeinnCrawler\Infrastructure\Parser;

use DOMXPath;

class Utils {
  private static function getDomObject( $content ) {
		$dom = new \DOMDocument('1.0', 'UTF-8');
		
    libxml_use_internal_errors( true );
		$dom->loadHTML( mb_convert_encoding( $content, 'HTML-ENTITIES', 'UTF-8' ) );
		libxml_use_internal_errors( false );

		return $dom;
	}

  static function getXPath( $responseBody ) {
    $responseBody = str_replace( '\n', '', $responseBody );
		$responseBody = preg_replace( '/\s+/', ' ', $responseBody );
		
    $dom = self::getDomObject( $responseBody );
    $xpath = new DOMXPath( $dom );

    return $xpath;
  }
}