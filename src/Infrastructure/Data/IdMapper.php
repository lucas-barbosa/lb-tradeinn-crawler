<?php

namespace LucasBarbosa\LbTradeinnCrawler\Infrastructure\Data;

class IdMapper {
  static function getAttachmentId( $key ) {
		$meta = CrawlerPostMetaData::getByMetaKey( '_tradeinn_attachment_' . $key );

		if ( ! empty( $meta ) && isset( $meta['post_id'] ) ) {
			return $meta['post_id'];
		}

		return false;
  }

	static function getAttributeId( $id ) {
    global $wpdb;

		$meta = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT option_value FROM {$wpdb->prefix}options WHERE option_name = %s",
				'tradeinn_attribute_' . $id
			)
		);

		if ( ! empty( $meta->option_value ) ) {
			return $meta->option_value;
		}

		return false;
  }

	static function getTermId( $name ) {
    global $wpdb;

		$meta = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$wpdb->prefix}termmeta WHERE meta_key = %s",
				'_tradeinn_term_name_' . $name
			)
		);

		if ( ! empty( $meta->term_id ) ) {
			return $meta->term_id;
		}

		return false;
  }

	static function getTermByKey( $key ) {
    global $wpdb;

		$meta = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$wpdb->prefix}termmeta WHERE meta_key = %s",
				$key
			)
		);

		if ( ! empty( $meta->term_id ) ) {
			return $meta->term_id;
		}

		return '';
  }

	static function getTermById( $id ) {
    global $wpdb;

		$meta = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$wpdb->prefix}termmeta WHERE meta_key = %s",
				'_tradeinn_term_id_' . $id
			)
		);

		if ( ! empty( $meta->term_id ) ) {
			return $meta->term_id;
		}

		return false;
  }

	static function getProductId( $id, $store ) {
		$meta = CrawlerPostMetaData::getByMeta( '_tradeinn_product_id_' . $id, $store );

		if ( ! empty( $meta ) && isset( $meta['post_id'] ) ) {
			return $meta['post_id'];
		}

		return null;
	}

	static function getVariationId( $id, $store ) {
		$meta = CrawlerPostMetaData::getByMeta( '_tradeinn_variation_id_' . $id, $store );

		if ( ! empty( $meta ) && isset( $meta['post_id'] ) ) {
			return $meta['post_id'];
		}

		return null;
	}

	static function setTermId( $term, $id ) {
		update_term_meta( $term, '_tradeinn_term_id_' . $id, $id );
	}

	static function setTermKey( $term, $key ) {
		update_term_meta( $term, $key, $key );
	}

	static function setAttributeId( $tradeinnId, $id ) {
		if ( empty( $tradeinnId ) ) return;
		update_option( 'tradeinn_attribute_' . $tradeinnId, $id );
	}
}