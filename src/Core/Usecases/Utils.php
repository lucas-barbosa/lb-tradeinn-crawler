<?php

namespace LucasBarbosa\LbTradeinnCrawler\Core\Usecases;

use LucasBarbosa\LbTradeinnCrawler\Infrastructure\Data\IdMapper;

class Utils {
	static function convert_weight_to_woocommerce_unit( $weight, $unit ) {
		$convertedWeight = 0;
		$sanitizedUnit = trim( strtolower( $unit ) );
		$woocommerceUnit = get_option( 'woocommerce_weight_unit' );

		if ( in_array( $sanitizedUnit, [ 'g', 'gramas', 'gramos', 'grams', 'grammi', 'グラム', '克' ] ) ) {
			if ( $woocommerceUnit === 'kg' ) {
				$convertedWeight = $weight / 1000;
			} else {
				$convertedWeight = $weight;
			}
		} else {
			if ( $woocommerceUnit === 'g' ) {
				$convertedWeight = $weight * 1000;
			} else {
				$convertedWeight = $weight;
			}
		}

		$convertedWeight = round( $convertedWeight, 3 );

		return array( 'value' => max( $convertedWeight, 0 ), 'unit' => $woocommerceUnit );
	}

	static function ensureMediaUploadIsLoaded() {
		if ( ! function_exists( 'wp_read_image_metadata' ) ) {
			require_once( ABSPATH  . '/wp-admin/includes/file.php' );
			require_once( ABSPATH  . '/wp-admin/includes/image.php' );
		}
	}

	static function replaceDescriptionImage( $html ) {
    // TODO
    return $html;
		// if ( !$html ) {
		// }
		
		// $dom = self::getDomObject($html);
		// $images = $dom->getElementsByTagName('img');
		
		// foreach ( $images as $image ) {
		// 	$img = $image->getAttribute('src');
			
		// 	if ($img) {
		// 		$key = base64_encode( $img );
		// 		$id = self::uploadAttachment( $img, $key );

		// 		if ($id) {
		// 			$img = wp_get_attachment_url($id);
		// 		}
		// 	}
			
		// 	if ($img) {
		// 		$image->setAttribute('src', $img);
		// 	}
		// }

		// return $dom->saveHTML();
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