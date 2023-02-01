<?php

namespace LucasBarbosa\LbTradeinnCrawler\Infrastructure\Parser;

use DOMDocument;
use DOMXPath;

class Utils {
	public static function convertWeightUnitToWoocommerce( $weight ) {
		$convertedWeight = $weight;
		$woocommerceUnit = get_option( 'woocommerce_weight_unit' );

		if ( $woocommerceUnit === 'g' ) {
			$convertedWeight = $weight * 1000;
		}

		$convertedWeight = round( $convertedWeight, 3 );

		return max( $convertedWeight, 0 );
	}

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

  static function getPropertyValue( DOMXPath $xpath, $query, $attribute = 'content' ) {
    $items = $xpath->query( $query );

    $response = array();
    
    foreach ( $items as $item ) {
      $element = $item->getAttribute( $attribute );
      $response[] = trim($element);
    }
  
    return $response;
  }

  static function getValue( DOMXPath $xpath, $selector, $html = false ) {
		if ( empty( $selector ) ) {
			return array();
		}
  
		$items = $xpath->query($selector);
		$response = array();
    
		foreach ($items as $item) {
			if ($html) {
				$element = $xpath->document->saveHTML($item);
			} else {
				$element = $item->nodeValue;
			}

			$response[] = trim($element);
		}

		return $response;
	}

	static function purifyHTML( $html ) {    		
		$html = self::removeElements(
			$html,
			array(
				'//select[@name = "tipo_traduccion"]',
				'//p[contains(@class, "select-coment")]',
				'//a[@href="javascript:void(0)"]',
				'//iframe',
				'//script',
				'//style',
				'//form',
				'//object',
				'//embed',
				'//select',
				'//input',
				'//textarea',
				'//button',
				'//noscript',
				'//li[contains(text(), "Garantía") or contains(text(), "Garantia") or contains(text(), "Warranty")]'
			)
		);
		
		return trim($html);
	}

	static function removeUnnecessaryChars( $html ) {
		$html = preg_replace('/<!--(.|\s)*?-->/i', '', $html);
		$html = preg_replace('/\s(id|class|itemprop|align|width|style|margin|margin-left|margin-top|margin-bottom|margin-right)="[^"]{0,}"/', '', $html);
		$html = preg_replace('/\>\s+\</', '><', $html);
		$html = preg_replace('/\s+/', ' ', $html);
		$html = preg_replace('/javascript:/', '#', $html); // Remove javascript.
		$html = preg_replace('/max-height:/', 'a:', $html); // Remove max height attribute.
		$html = preg_replace('#<a.*?>(.*?)</a>#i', '\1', $html); // Remove Link
		return $html;
	}

	private static function removeElements( $html, $selectors) {
		if (!$html) {
			return $html;
		}
		
		$dom = self::getDomObject( $html );
		$xpath = new \DOMXPath($dom);
	
		foreach ($selectors as $selector) {
			
			$nodes = $xpath->query($selector);
			
			foreach ($nodes as $node) {
				$node->parentNode->removeChild($node);
			}
		}
		
		return $dom->saveHTML();
	}
}