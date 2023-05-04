<?php

namespace LucasBarbosa\LbTradeinnCrawler\Core\Usecases;

use LucasBarbosa\LbTradeinnCrawler\Infrastructure\Data\IdMapper;

class Utils {
	static function convertDimensionToUnit( $value, $productUnit, $desirableUnit = '' ) {
		if ( empty( $value ) ) {
			return 0;
		}

		$woocommerceUnit = empty( $desirableUnit ) ? get_option( 'woocommerce_dimension_unit' ) : $desirableUnit;

		if ( $woocommerceUnit === $productUnit ) {
			return $value;
		}

		$conversion_factors = array(
			'cm' => 1.0,
			'mm' => 0.1,
			'm' => 100.0
		);

		$system_value = $value * $conversion_factors[$productUnit];
  	$output_value = $system_value / $conversion_factors[$woocommerceUnit];

		return $output_value;
	}
	
	static function convert_weight_to_woocommerce_unit( $weight ) {
		if ( $weight === 0 ) {
			return 0;
		}
		
		$woocommerceUnit = get_option( 'woocommerce_weight_unit' );
    $convertedWeight = $woocommerceUnit === 'g' ? $weight * 1000 : $weight;

    return round( $convertedWeight, 3 );
	}

	static function ensureMediaUploadIsLoaded() {
		if ( ! function_exists( 'wp_read_image_metadata' ) ) {
			require_once( ABSPATH  . '/wp-admin/includes/file.php' );
			require_once( ABSPATH  . '/wp-admin/includes/image.php' );
		}
	}

	private static function getDomObject( $content ) {
		$dom = new \DOMDocument('1.0', 'UTF-8');
		
    libxml_use_internal_errors( true );
		$dom->loadHTML( mb_convert_encoding( $content, 'HTML-ENTITIES', 'UTF-8' ) );
		libxml_use_internal_errors( false );

		return $dom;
	}

	static function purifyHTML( $html ) {    		
		$html = self::removeElements(
			$html,
			array(
				'//div[contains(attribute::class, "a-expander-header")]',
				'//div[contains(attribute::class, "apm-tablemodule")]',
				'//div[contains(attribute::class, "-comparison-table")]',
				'//div[contains(attribute::class, "-carousel")]',
				'//*[@data-action="a-expander-toggle"]',
				'//a[@href="javascript:void(0)"]',
				'//div[@id="boxCaracteristicas"]',
				'//./table[@align="right"]',
				'//ul[contains(@class, "listaArticulos")]/parent::div/preceding-sibling::div[1]',
				'//ul[contains(@class, "listaArticulos")]/parent::div',
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

	static function replaceDescriptionImage( $html ) {
		if ( !$html ) {
    	return $html;
		}
		
		$dom = self::getDomObject( $html );
		$images = $dom->getElementsByTagName('img');
		
		foreach ( $images as $image ) {
			$img = $image->getAttribute('src');
			
			if ($img) {
				$key = base64_encode( $img );
				$id = self::uploadAttachment( $img, $key );

				if ($id) {
					$img = wp_get_attachment_url($id);
				}
			}
			
			if ($img) {
				$image->setAttribute('src', $img);
			}
		}

		return $dom->saveHTML();
	}

  static function set_uploaded_image_as_attachment( $upload, $id = 0 ) {
		if ( is_wp_error( $upload ) ) {
			return $upload;
		}
		
		$info    = wp_check_filetype( $upload['file'] );
		$title   = '';
		$content = '';

		if ( $image_meta = \wp_read_image_metadata( $upload['file'] ) ) {
			if ( trim( $image_meta['title'] ) && ! is_numeric( sanitize_title( $image_meta['title'] ) ) ) {
				$title = wc_clean( $image_meta['title'] );
			}
			if ( trim( $image_meta['caption'] ) ) {
				$content = wc_clean( $image_meta['caption'] );
			}
		}

		$attachment = array(
			'post_mime_type' => $info['type'],
			'guid'           => $upload['url'],
			'post_parent'    => $id,
			'post_title'     => $title,
			'post_content'   => $content,
		);

		$attachment_id = wp_insert_attachment( $attachment, $upload['file'], $id );
    
		if ( ! is_wp_error( $attachment_id ) ) {
			wp_update_attachment_metadata( $attachment_id, wp_generate_attachment_metadata( $attachment_id, $upload['file'] ) );
		}

		return $attachment_id;
	}

  static function slugify( $value ) {
    $text = preg_replace( '~[^\pL\d]+~u', '-', $value );

    // trim
    $text = trim($text, '-');

    // remove duplicate -
    $text = preg_replace('~-+~', '-', $text);

    // lowercase
    $text = strtolower($text);

    if ( empty($text) ) {
      return '';
    }
    
    return $text;
  }

	static function translate( $content, $from, $to, $html = false, $objectType = '', $field = '' ) {
		if ( function_exists( 'lb_translate_text' ) ) {
			return lb_translate_text( $content, $from, $to, $html, $objectType, $field );
		}

		return null;
	}

	static function uploadAttachment( $url, $key, $upload_for = 'product_image' ) {
		if ( empty( $url ) ) {
			return 0;
		}
		
		set_time_limit( 300 );
		$id = IdMapper::getAttachmentId( $key );

		if ( $id ) {
			return $id;
		}

		self::ensureMediaUploadIsLoaded();
		
		$upload = wc_rest_upload_image_from_url( $url );
		do_action( 'woocommerce_api_uploaded_image_from_url', $upload, $url, $upload_for );
		$id = Utils::set_uploaded_image_as_attachment( $upload );

		if ( $id === false || empty( $id ) || is_wp_error( $id ) ) {
			return 0;
		}

		update_post_meta( $id, '_barrabes_attachment_' . $key,  $key );

		return $id;
  }
}