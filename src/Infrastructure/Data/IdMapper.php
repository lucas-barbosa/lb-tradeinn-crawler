<?php

namespace LucasBarbosa\LbTradeinnCrawler\Infrastructure\Data;

class IdMapper {
  static function getAttachmentId( $key ) {
    global $wpdb;

		$meta = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$wpdb->prefix}postmeta WHERE meta_key = %s",
				'_tradeinn_attachment_' . $key
			)
		);

		if ( ! empty( $meta->post_id ) ) {
			return $meta->post_id;
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

	static function getProductId( $id, $store ) {
		global $wpdb;

		$meta = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$wpdb->prefix}postmeta WHERE meta_key = %s and meta_value = %s",
				'_tradeinn_product_id_' . $id,
				$store
			)
		);

		if ( ! empty( $meta->post_id ) ) {
			return $meta->post_id;
		}

		return null;
	}
}