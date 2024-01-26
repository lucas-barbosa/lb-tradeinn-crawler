<?php

namespace LucasBarbosa\LbTradeinnCrawler\Core\Usecases;

use Exception;
use LucasBarbosa\LbTradeinnCrawler\Core\Entities\ProductEntity;
use LucasBarbosa\LbTradeinnCrawler\Core\Entities\ProductVariationEntity;
use LucasBarbosa\LbTradeinnCrawler\Infrastructure\Data\CrawlerPostMetaData;
use LucasBarbosa\LbTradeinnCrawler\Infrastructure\Data\IdMapper;
use LucasBarbosa\LbTradeinnCrawler\Infrastructure\Data\SettingsData;

class CreateProduct {
  private array $taxonomies = [];
  private string $stock = '';
  
  public function setHooks() {
    add_action( 'lb_tradeinn_crawler_product_loaded', array( $this, 'execute' ) );
  }

	protected function loadParams() {
		$this->stock = SettingsData::getStock();
	}

  public function execute( ProductEntity $productData ) {
		if ( $productData->getInvalid() ) {
			$productId = $productData->getId();
			$storeName = $productData->getStoreName();

			$url = "https://www.tradeinn.com/" . $storeName . "/" . $productId;
			
			do_action( 'lb_crawler_block_url', $url );

			$this->deleteProductIfExists( $productData );
			return;
		}

		$this->loadParams();
			
    $result = $this->getWoocommerceProduct( $productData->getId(), $productData->getStoreName(), $productData->isVariable() );
		$product = $result[0];
		$is_new_product = $result[1];
		
    $product = $this->setRequiredData( $product, $productData, $is_new_product );

    $this->saveProduct( $product, true, $productData->getPrice(), $productData->getAvailability() );

    $product = $this->setAdditionalData( $product, $productData );

		do_action( 'lb_tradeinn_product_created', [ $product->get_id(), $productData->getParentStoreProps() ]);
  }

  private function addBrand( $brandName ) {
		$terms_to_add = [];

		$matchingBrand = get_term_by( 'name', $brandName, 'product_brand' );

    if ( ! empty( $matchingBrand->term_id ) ) {
      $terms_to_add[] = (int) $matchingBrand->term_id;
    } else {
			$id = wp_insert_term( $brandName, 'product_brand' );

      if ( ! is_wp_error( $id ) ) {
        $terms_to_add[] = $id['term_id'];
			}
		}

		return $terms_to_add;
	}

  private function addCategories( $tradeinnCategories ) {
		if ( ! is_array( $tradeinnCategories ) ) {
			$tradeinnCategories = [];
		}
		
    $parentId = SettingsData::getParentCategory();
		$categoryIds = array();

    if ( empty( $parentId ) ) {
      $parentId = 0;
    } else {
      $categoryIds[] = $parentId;
    }
    
		foreach ( $tradeinnCategories as $tradeInnCategory ) {
			$categoryName = $tradeInnCategory['name'];
			$parentName = $parentId !== 0 ? "$parentId-" : '';

			$categoryCacheName = $parentName . $categoryName;
			$categoryExists = IdMapper::getTermId( $categoryCacheName );
			$parentName .= $categoryName . '_';

      if ( $categoryExists ) {
        $parentId = $categoryExists;
        $categoryIds[] = $parentId;
        continue;
      }

      $terms = get_terms( 'product_cat', array(
        'name' => $categoryName,
        'parent' => $parentId,
        'hide_empty' => false,
      ) );
				
      if ( ! is_wp_error( $terms ) && count( $terms ) > 0 ) {
        $category = array(
          'term_id' => $terms[0]->term_id,
          'name' 		=> $terms[0]->name,
          'slug' 		=> $terms[0]->slug,
          'parent' 	=> $terms[0]->parent,
        );
      } else {
        $category = wp_insert_term(
          $categoryName,
          'product_cat',
          array(
            'description' => '',
            'parent' => $parentId
          )
        );
      }

      if ( ! is_wp_error( $category ) && isset( $category['term_id'] ) ) {
				IdMapper::setTermId( $category['term_id'], $tradeInnCategory['id'] );
        update_term_meta( $category['term_id'], '_tradeinn_term_name_' . $categoryCacheName, $categoryName );

        $parentId = $category['term_id'];
        $categoryIds[] = $parentId;
			}
		}
		
		return array_unique( $categoryIds );
	}

  private function addImages( $imageUrls ) {
		static $images = array();
		
		$imageIds = array();
		
		if ( $imageUrls ) {
			foreach ( $imageUrls as $imageUrl ) {
				$key = base64_encode( $imageUrl );

				if ( isset( $images[$key] ) ) {
					$imageIds[] = $images[$key];
				} else {				
					$id = Utils::uploadAttachment( $imageUrl, $key );

					if ( $id ) {
						$images[$key] = $id;
						$imageIds[] = $id;
					}					
				}
			}
		}
				
		return $imageIds;
	}

  protected function addTaxonomyIfNotExists( $id, $taxonomyLabel, $taxonomySlug, $values = array() ) {
		$attribute_id = $this->getAttributeTaxonomyId( $id, $taxonomyLabel, $taxonomySlug );

		if ( ! is_wp_error( $attribute_id ) && $values ) {
			$taxonomy = wc_attribute_taxonomy_name_by_id( (int) $attribute_id );

			foreach ( $values as $item ) {
        $value = is_array( $item ) ? $item['value'] : $item;
				$originalValue = '';

				if ( is_array( $item ) && isset( $item['translated_value'] ) && ! empty( $item['translated_value'] ) ) {
					$originalValue = $value;
					$value = $item['translated_value'];
				}

        if ( ! empty( $item['id'] ) && IdMapper::getTermId( $item['id'] ) ) {
          continue;
        }

        $term = term_exists( $value, $taxonomy );

				if ( ! $term ) {
					$term = wp_insert_term( $value, $taxonomy );
				}

        if ( ! is_wp_error( $term ) &&  isset( $term['term_id'] ) && ! empty( $item['id'] ) ) {
          update_term_meta( $term['term_id'], '_tradeinn_term_name_' . $item['id'], $item['id'] );
					do_action( 'lb_multi_language_translate_term', $term['term_id'], 'ingles', trim( $originalValue ), '', sanitize_title( $originalValue ) );
        }        
			}

      $this->taxonomies[ $taxonomySlug ] = $taxonomy;
		}

		return $taxonomy;
	}

  private function createVariations( $product, array $variations, string $storeName ) {
    $existentVariations = $product->get_children( array(
			'fields'      => 'ids',
			'post_status' => array( 'publish', 'private' )
		), ARRAY_A );

		$syncedVariations = [];

		try {
			foreach ( $variations as $i => $variation ) {
				if ( $variation->getInvalid() ) {
					continue;
				}

				$syncedVariations[] = $this->createVariation( $i, $product, $variation, $storeName );
			}
		} catch ( Exception $e ) {
			/**
			 * TO-DO: add log
			 */
		}

		$this->deleteNonUsedVariations( $existentVariations, $syncedVariations );
	}

	public function createVariation( $i, $product, $variation, $storeName ) {
		$sku = $product->get_sku();
		
		$attributes = $this->getWoocommerceVariationAttributes( $variation );
		$productVariation = $this->getWoocommerceVariation( $product, $attributes );
		$productVariation->set_parent_id( $product->get_id() );
		
		if ( $sku ) {
			$variationSku = $sku . '-' . (string)$i . time();
			$productVariation->set_sku( $variationSku );
		}

		CrawlerPostMetaData::insert( $productVariation->get_id(), '_tradeinn_variation_id_' . $variation->getId(), $storeName );
		$productVariation->set_attributes( $attributes );

		$price = $variation->getPrice();
		$stock = $variation->getAvailability();

		$this->setPriceAndStock( $productVariation, $price, $stock );

		$productVariation = $this->setDimensions( $productVariation, $variation->getDimensions(), $variation->getWeight() );

		$this->saveProduct( $productVariation, true, $price, $stock );

		return $productVariation->get_id();
	}

	private function deleteNonUsedVariations( $existentVariations, $newVariations ) {
		if ( ! empty( $existentVariations ) ) {
			foreach ( $existentVariations as $variationId ) {
				if ( ! in_array( $variationId, $newVariations ) ) {
					wp_delete_post( $variationId, true );
				}
			}
		}
	}

	private function deleteProductIfExists( ProductEntity $product ) {
		$productId = IdMapper::getProductId( $product->getId(), $product->getStoreName() );

		if ( $productId ) {
			wp_delete_post( $productId, true );
			do_action( 'lb_barrabes_product_deleted', $productId );
		}
	}

  private function getAttributeTaxonomyId( $id, $taxonomyLabel, $taxonomySlug ) {
		$storedValue = empty( $id ) ? '' : get_option( 'tradeinn_attribute_' . $id, '' );

		if ( ! empty( $storedValue ) ) {
			return $storedValue;
		}

		$optionName = 'tradeinn_' . $taxonomyLabel;
		$attributeIdFromCache = get_option( $optionName, '' );

		if ( ! empty( $attributeIdFromCache ) && ! is_null( wc_get_attribute( $attributeIdFromCache ) ) ) {
			IdMapper::setAttributeId( $id, $attributeIdFromCache );
			return $attributeIdFromCache;
		}

		$attributeIdFromCache = get_option( 'sp_tradeinn_' . $taxonomyLabel, '' );

		if ( ! empty( $attributeIdFromCache ) && ! is_null( wc_get_attribute( $attributeIdFromCache ) ) ) {
			IdMapper::setAttributeId( $id, $attributeIdFromCache );
			return $attributeIdFromCache;
		}

		$attributeFromSlug = wc_attribute_taxonomy_id_by_name( $taxonomySlug );

		if ( $attributeFromSlug ) {
			IdMapper::setAttributeId( $id, $attributeFromSlug );
			update_option( 'sp_tradeinn_' . $taxonomyLabel, $attributeFromSlug, false );
			return $attributeFromSlug;
		}

		$attribute_labels = wp_list_pluck( wc_get_attribute_taxonomies(), 'attribute_label', 'attribute_name' );
		$attribute_name   = array_search( $taxonomyLabel, $attribute_labels, true );

		$attributeFromLabel = wc_attribute_taxonomy_id_by_name( $attribute_name );

		if ( $attributeFromLabel ) {
			IdMapper::setAttributeId( $id, $attributeFromLabel );
			update_option( 'sp_tradeinn_' . $taxonomyLabel, $attributeFromLabel, false );
			return $attributeFromLabel;
		}
		
		$attribute_name = wc_sanitize_taxonomy_name( trim(substr($taxonomyLabel, 0, 27)) );

		$attribute_id = wc_create_attribute(
			array(
				'name'         => $taxonomyLabel,
				'slug'         => $attribute_name,
				'type'         => 'select',
				'order_by'     => 'menu_order',
				'has_archives' => false,
			)
		);

		if ( !is_wp_error( $attribute_id ) ) {
			$taxonomy_name = wc_attribute_taxonomy_name( $attribute_name );

			register_taxonomy(
				$taxonomy_name,
				apply_filters( 'woocommerce_taxonomy_objects_' . $taxonomy_name, array( 'product' ) ),
				apply_filters(
					'woocommerce_taxonomy_args_' . $taxonomy_name,
					array(
						'label' 			 => $taxonomyLabel,
						'hierarchical' => false,
						'show_ui'      => false,
						'query_var'    => true,
						'rewrite'      => false,
					)
				)
			);
		}

		IdMapper::setAttributeId( $id, $attribute_id );
		update_option( $optionName, $attribute_id, false );
		
		return $attribute_id;
	}

  private function getVariationId( $product, $variationAttributes ) {
		$data_store = \WC_Data_Store::load( 'product' );

    $attributes = [];

    foreach ( $variationAttributes as $key => $value ) {
      $attributes[ 'attribute_' . $key ] = $value;
    }

		return $data_store->find_matching_product_variation( $product, $attributes );
	}

  private function getWoocommerceProduct( string $id, string $store, bool $isVariable ) {
    $productId = IdMapper::getProductId( $id, $store );
		$new_product = true;

		if ( $productId ) {
			$new_product = false;
			$product = wc_get_product( $productId );		

			if ( $product && 0 < $product->get_parent_id() ) {
				$productId = $product->get_parent_id();
			}
		}

    if ( $isVariable ) {
			return [new \WC_Product_Variable((int) $productId ), $new_product];
		}

		return [new \WC_Product((int) $productId ), $new_product];
  }

  private function getWoocommerceVariation( $product, $variationAttributes ) {
		$variation_id = $this->getVariationId( $product, $variationAttributes );
		$variation = new \WC_Product_Variation( $variation_id );
		return $variation;
	}

  private function getWoocommerceVariationAttributes( ProductVariationEntity $product ) {
    $formattedAttributes = [];
    $attributes = $product->getAttributes();

    foreach ( $attributes as $attribute ) {
      $taxName =  wc_attribute_taxonomy_name( wc_sanitize_taxonomy_name( stripslashes( $attribute->getName() ) ) );

      if ( isset( $this->taxonomies[ $taxName ] ) ) {
        $taxName = $this->taxonomies[ $taxName ];
      }

      $value = $attribute->getValue()[0];

      $attrValSlug = wc_sanitize_taxonomy_name( sanitize_title( stripslashes( $value ) ) );
      $formattedAttributes[$taxName] = $attrValSlug;
    }

    return $formattedAttributes;
  }

	protected function sanitizeDescription( $description ) {
		$descriptionWithReplacedImage = Utils::replaceDescriptionImage( $description );
    return Utils::purifyHTML( $descriptionWithReplacedImage );
	}

  protected function saveProduct( $product, bool $changed, $price, $availability ) {
    do_action( 'lb_multi_inventory_remove_stock_hooks' );

		if ( $changed ) $product->save();

		$this->setMultinventoryData( $product, $price, $availability );

		do_action( 'lb_multi_inventory_add_stock_hooks' );
  }

  private function setAdditionalData( $product, ProductEntity $productData ) {
    $product = $this->setBrand( $product, $productData->getBrand() );
    $product = $this->setAttributes( $product, $productData->getAttributes() );
    
    $variations = $productData->getVariations();

    if ( $productData->isVariable() ) {
      $this->createVariations( $product, $variations, $productData->getStoreName() );
    } else if ( count( $variations ) === 1 ) {
			CrawlerPostMetaData::insert( $product->get_id(), '_tradeinn_variation_id_' . $variations[0]->getId(), $productData->getStoreName() );
		}

    return $product;
  }

  private function setAttributes( $product, array $attributes ) {
    $position = 0;

		if ($attributes) {
			$productAttributes = [];

			foreach ( $attributes as $attribute ) {
        $attributeName = $attribute->getName();
        $values = $attribute->getValue();
				$isVariation = $attribute->isVariation();

				$name = wc_sanitize_taxonomy_name( stripslashes( $attributeName ) );
				$taxonomy = wc_attribute_taxonomy_name($name); // woocommerce prepend pa_ to each attribute name

				$taxonomy = $this->addTaxonomyIfNotExists( $attribute->getId(), $attributeName, $taxonomy, $values );

        $values = array_map( function ( $value ) {
          if ( is_array( $value ) ) {
						if ( isset( $value['translated_value'] ) && ! empty( $value['translated_value'] ) ) {
							return $value['translated_value'];
						}

            return $value['value'];
          }

          return $value;
        }, $values );

				if ( $values ) {
					wp_set_post_terms( $product->get_id(), $values, $taxonomy, false );
				}

				$productAttributes[ $taxonomy ] = array(
					'name' => $taxonomy,
					'value' => $values,
					'position' => $position++,
					'is_visible' => 1,
					'is_variation' => $isVariation,
					'is_taxonomy' => '1'
				);
			}

			update_post_meta( $product->get_id(), '_product_attributes', $productAttributes );
		}

    return $product;
  }

  private function setMetaData( $product, ProductEntity $productData ) {
    $product->update_meta_data( '_lb_gf_gtin', $productData->getEan() );
    CrawlerPostMetaData::insert( $product->get_id(), '_tradeinn_product_id_' . $productData->getId(), $productData->getStoreName() );
    CrawlerPostMetaData::insert( $product->get_id(), '_tradeinn_props', $productData->getParentStoreProps() );
    return $product;
  }

  private function setMultinventoryData( $product, $price, $stockStatus ) {
		if ( empty( $this->stock ) || $product->is_type( 'variable' ) ) {
			return;
		}

		do_action( 'lb_multi_inventory_set_stock', $product->get_id(), $this->stock, $price, $stockStatus, $product );
	}

  private function setRequiredData( $product, ProductEntity $productData, $is_new_product ) {
    $title = $productData->getBrand() . ' ' . $productData->getTitle();

    $product->set_name( trim( $title ) );

		$categories = $this->addCategories( $productData->getCategories() );
		$existentCategories = $product->get_category_ids();
		$product->set_category_ids( array_merge( $categories, $existentCategories ) );
		
		$description = $this->sanitizeDescription( $productData->getDescription() );
    $product->set_description( $description );
    $product->set_sku( $productData->getSku() );
    
    $product = $this->setDimensions( $product, $productData->getDimensions(), $productData->getWeight() );
    $product = $this->setImages( $product, $productData->getImages() );
    $product = $this->setMetaData( $product, $productData );

    $this->setPriceAndStock( $product, $productData->getPrice(), $productData->getAvailability() );
		$this->setSyncData( $product, $is_new_product );

    return $product;
  }

	private function setSyncData( $product, $is_new_product ) {
		if ( $product->get_weight() > 0 && $is_new_product ) {
			$sync_to = apply_filters( '_lb_sync_created_product_to', [] );

			if ( ! empty( $sync_to ) ) {
				$product->update_meta_data( '_lb_woo_multistore_reply_to', $sync_to );
				$product->update_meta_data( '_lb_woo_multistore_manage_child_stock', $sync_to );
				$product->update_meta_data( '_lb_woo_multistore_should_enqueue', 'yes' );
			}
			
			do_action( 'lb_crawler_creating_product', $product );
		}
	}

  private function setBrand( $product, $brand ) {
		$id = $product->get_id();
    $brandId = $this->addBrand( $brand );

    wp_set_post_terms( $id, $brandId, 'product_brand' );

		return $product;
	}

  private function setDimensions( $product, array $dimensions, $weight ) {
    if ( isset( $weight ) ) {
			$convertedWeight = Utils::convert_weight_to_woocommerce_unit( $weight );
      $product->set_weight( $convertedWeight );
    }

    if ( isset( $dimensions['height'] ) ) {
      $product->set_height( $dimensions['height'] );
    }

    if ( isset( $dimensions['length'] ) ) {
      $product->set_length( $dimensions['length'] );
    }

    if ( isset( $dimensions['width'] ) ) {
      $product->set_width( $dimensions['width'] );
    }

    return $product;
  }

  private function setImages( $product, array $images ) {
    $imageIds = $this->addImages( array_unique( $images ) );
    $product->set_image_id( count( $imageIds ) > 0 ? array_shift( $imageIds ) : '');
    $product->set_gallery_image_ids( $imageIds );

    return $product;
  }

  protected function setPriceAndStock( $product, $price, $stockStatus ) {
		if ( empty ( $this->stock ) ) {
			$currentPrice = $product->get_meta( '_main_price' );
      $currentStockAvailability = $product->get_meta( '_main_stock_status' );

			$product->set_regular_price($price);
			$product->set_stock_status( $stockStatus );
			$product->update_meta_data( '_main_price', $price );
			$product->update_meta_data( '_main_stock_status', $stockStatus );

			if ( $currentPrice != $price || $currentStockAvailability != $stockStatus ) {
				return true;
			}
		}

		return false;
	}
}