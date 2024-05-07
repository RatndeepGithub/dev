<?php
namespace Ced\Ebay;

use WP_Error;
use WC_Product;
use WC_Product_Attribute;
use WC_Cache_Helper;
use WC_Product_Factory;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


if ( ! class_exists( 'Import_Listing' ) ) {
    class Import_Listing{


        private $ebayItemId;
        private $ebayUserId;
        private $ebaySiteId;
        private $wooProductId;
        private $wooProduct;

        public function setEbayUser($ebayUserId){
            $this->ebayUserId = $ebayUserId;
        }

        public function setEbaySite($ebaySiteId){
            $this->ebaySiteId = $ebaySiteId;
        }

        public function setWooProduct($wooProductId, $product_type){
            if(!empty($wooProductId)){
                $this->wooProductId = $wooProductId;
                $classname = WC_Product_Factory::get_product_classname( $wooProductId, $product_type );
                $this->wooProduct   = new $classname( $wooProductId );
                $this->wooProduct->save();
                if(!$this->wooProduct instanceof $classname){
                    return new WP_Error('invalid_product', 'Invalid Woo Product');
                }
            }
        }
        
        public function createProduct($title, $status){
            if(!empty($title)){
                $all_product_status = array_merge( array_keys( get_post_statuses() ), array( 'future', 'auto-draft', 'trash' ) );
                if(!in_array($status, $all_product_status)){
                    return new WP_Error('invalid_status', 'Invalid Product Status');
                }
                $title = apply_filters('ced/ebay/modify_title_before_import', $title);
                $product_id = wp_insert_post(
                    array(
                        'post_title'   => $title,
                        'post_status'  => $status,
                        'post_type'    => 'product',
                    ), true
                );
                return $product_id;
            }
        }

        public function getWooProduct(){
            if(!empty($this->wooProduct) && $this->wooProduct instanceof WC_Product){
                return $this->wooProduct;
            } else {
                return new WP_Error('invalid_product', 'Invalid Woo Product');
            }
        }

        public function importDescription($description){
            if(!is_wp_error($this->getWooProduct())){
                $description = apply_filters('ced/ebay/modify_description_before_import', $description);
                $wc_product = $this->getWooProduct();
                $wc_product->set_description($description);
                $this->wooProduct = $wc_product;
                $this->wooProduct->save();
            }
        }

        public function importTitle($title){
            if(!empty($title) && !is_wp_error($this->getWooProduct())){
                $title = apply_filters('ced/ebay/modify_title_before_import', $title);
                $wc_product = $this->getWooProduct();
                $wc_product->set_name($title);
                $this->wooProduct = $wc_product;
                $this->wooProduct->save();
            }
        }
        

        public function importPrice($price){
            if(!empty($price) && !is_wp_error($this->getWooProduct())){
                $wc_product = $this->getWooProduct();
                $price = apply_filters('ced/ebay/modify_price_before_import', $price);
                $wc_product->set_regular_price($price);
                $this->wooProduct = $wc_product;
                $this->wooProduct->save();
            }
        }

        public function setEbayItemId($item_id){
            if(!empty($item_id)){
                $this->ebayItemId = $item_id;
            }
        }

        public function getEbayItemId(){
            if(!empty($this->ebayItemId)){
                return $this->ebayItemId;
            } else {
                return new WP_Error('invalid_item_id', 'Invalid or empty eBay Item Id');
            }
        }

        public function importStock($available_stock){
            if(null !== $available_stock && !is_wp_error($this->getWooProduct())){
                $wc_product = $this->getWooProduct();
                $wc_product->set_manage_stock(true);
                $available_stock = apply_filters('ced/ebay/modify_stock_before_import', $available_stock);
                if($available_stock > 0){
                    $wc_product->set_stock_status('instock');
                    $wc_product->set_stock_quantity($available_stock);
                } else {
                    $wc_product->set_stock_status('outofstock');
                    $wc_product->set_stock_quantity(0);
                }
                $wc_product->set_manage_stock(true);
                $this->wooProduct = $wc_product;
                $this->wooProduct->save();
            }

        }

        public function importSku($sku){
            if(!empty($sku) && !is_wp_error($this->getWooProduct())){
                $sku = apply_filters('ced/ebay/modify_sku_before_import', $sku);
                $wc_product = $this->getWooProduct();
                $wc_product->set_sku($sku);
                $this->wooProduct = $wc_product;
                $this->wooProduct->save();
            }
        
        }

        public function setProductStatus($status){
            if(!empty($status) && !is_wp_error($this->getWooProduct())){
                $all_product_status = array_merge( array_keys( get_post_statuses() ), array( 'future', 'auto-draft', 'trash' ) );
                if(!in_array($status, $all_product_status)){
                    return new WP_Error('invalid_status', 'Invalid Product Status');
                }
                $wc_product = $this->getWooProduct();
                $wc_product->set_status($status);
                $this->wooProduct = $wc_product;
                $this->wooProduct->save();
            }
        }

        public function importProductAttributes($ebay_item_specifics, $is_variation = false){
            if ( isset( $ebay_item_specifics['NameValueList'] ) && ! isset( $ebay_item_specifics['NameValueList'][0] ) ) {
                $tempNameValueList = array();
                $tempNameValueList = $ebay_item_specifics['NameValueList'];
                unset( $ebay_item_specifics['NameValueList'] );
                $ebay_item_specifics['NameValueList'][] = $tempNameValueList;
                }
                if ( ! empty( $ebay_item_specifics['NameValueList'] ) && !is_wp_error($this->getWooProduct())){
                    $wc_product = $this->getWooProduct();
                    foreach ( $ebay_item_specifics['NameValueList'] as $key => $attributes ) {

                        delete_transient( 'wc_attribute_taxonomies' );
                        WC_Cache_Helper::incr_cache_prefix( 'woocommerce-attributes' );
                        $attributeName = $attributes['Name'];

                        if ( 'Year' == $attributes['Name'] ) {
                            $attributeSlug = $attributes['Name'] . '-attr';
                        } elseif ( 'Type' == $attributes['Name'] ) {
                            $attributeSlug = $attributes['Name'] . '-attr';
                        } else {
                            $attributeSlug = $attributes['Name'];
                        }
                        if ( 28 < strlen( $attributeSlug ) ) {
                            $attributeSlug = substr( $attributeSlug, 0, 10 );
                        }
                        $attributeLabels = wp_list_pluck( wc_get_attribute_taxonomies(), 'attribute_label', 'attribute_name' );
                        $attributeWCName = array_search( $attributeSlug, $attributeLabels, true );

                        if ( ! $attributeWCName ) {
                            $attributeWCName = wc_sanitize_taxonomy_name( $attributeSlug );
                        }

                        $attributeId = wc_attribute_taxonomy_id_by_name( $attributeWCName );
                        if ( ! $attributeId ) {
                            $taxonomyName = wc_attribute_taxonomy_name( $attributeWCName );
                            unregister_taxonomy( $taxonomyName );
                            $attributeId = wc_create_attribute(
                                array(
                                    'name'         => $attributeName,
                                    'slug'         => $attributeSlug,
                                    'type'         => 'select',
                                    'order_by'     => 'menu_order',
                                    'has_archives' => 0,
                                )
                            );

                            register_taxonomy(
                                $taxonomyName,
                                /**
                                *
                                * Woocommerce_taxonomy_objects
                                *
                                * @since 1.0.0
                                */
                                apply_filters(
                                    'woocommerce_taxonomy_objects_' . $taxonomyName,
                                    array(
                                        'product',
                                    )
                                ),
                                /**
                                *
                                * Woocommerce_taxonomy_args
                                *
                                * @since 1.0.0
                                */
                                apply_filters(
                                    'woocommerce_taxonomy_args_' . $taxonomyName,
                                    array(
                                        'labels'       => array(
                                            'name' => $attributeSlug,
                                        ),
                                        'hierarchical' => false,
                                        'show_ui'      => false,
                                        'query_var'    => true,
                                        'rewrite'      => false,
                                    )
                                )
                            );
                        }

                        $taxonomy = $attributes['Name'];
                        if ( strlen( $taxonomy ) > 28 ) {
                            $taxonomy = substr( $taxonomy, 0, 10 );
                        }
                        $taxonomyName = wc_attribute_taxonomy_name( $taxonomy );
                        if ( 'Year' == $taxonomy ) {
                            $taxonomyName = $taxonomyName . '-attr';
                        }
                        if ( 'Type' == $taxonomy ) {
                            $taxonomyName = $taxonomyName . '-attr';
                        }
                        if ( strlen( $taxonomyName ) > 28 ) {
                            $taxonomyName = substr( $taxonomyName, 0, 10 );
                        }
                        if ( ! empty( $attributes['Value'] ) && is_array( $attributes['Value'] ) ) {
                            $term_id = array();
                            foreach ( $attributes['Value'] as $key => $attr_terms ) {
                                $termArrayName = $attr_terms;
                                $termArraySlug = $attr_terms;
                                $term          = get_term_by( 'slug', $termArrayName, $taxonomyName );
                                if ( ! $term ) {
                                    $term = wp_insert_term(
                                        $termArrayName,
                                        $taxonomyName,
                                        array(
                                            'slug' => $termArraySlug,
                                        )
                                    );
                                    if ( isset( $term->error_data['term_exists'] ) ) {
                                        $term_id = $term->error_data['term_exists'];
                                    } elseif ( is_array( $term ) ) {
                                            $term_id[] = $term['term_id'];
                                    } else {
                                        $term_id[] = $term->term_id;
                                    }
                                } else {
                                    $term_id[] = $term->term_id;
                                }
                            }
                        }
                        if ( ! empty( $attributes['Value'] ) && ! is_array( $attributes['Value'] ) ) {
                            $termName = $attributes['Value'];
                            $termSlug = $attributes['Value'];
                            $term     = get_term_by( 'slug', $termSlug, $taxonomyName );
                            if ( ! $term ) {
                                $term = wp_insert_term(
                                    $termName,
                                    $taxonomyName,
                                    array(
                                        'slug' => $termSlug,
                                    )
                                );
                                if ( isset( $term->error_data['term_exists'] ) ) {
                                    $term_id = $term->error_data['term_exists'];
                                } elseif ( is_array( $term ) ) {
                                        $term_id = $term['term_id'];
                                } else {
                                    $term_id = $term->term_id;
                                }
                            } else {
                                $term_id = $term->term_id;
                            }
                        }
                        $product_attributes = (array) $wc_product->get_attributes();
                        if ( array_key_exists( $taxonomy, $product_attributes ) ) {
                            foreach ( $product_attributes as $key1 => $product_attribute ) {
                                if ( $key1 == $taxonomy ) {
                                    if ( is_array( $term_id ) ) {
                                        $product_attribute->set_options( $term_id );
                                    } else {
                                        $product_attribute->set_options( array( $term_id ) );
                                    }
                                    $product_attributes[ $key1 ] = $product_attribute;
                                    break;
                                }
                            }
                            $wc_product->set_attributes( $product_attributes );
                        } else {
                            $prod_attribute = new WC_Product_Attribute();

                            $prod_attribute->set_id( count( $product_attributes ) + 1 );
                            $prod_attribute->set_name( $taxonomyName );
                            if ( is_array( $term_id ) ) {
                                $prod_attribute->set_options( $term_id );
                            } else {
                                $prod_attribute->set_options( array( $term_id ) );
                            }
                            $prod_attribute->set_position( count( $product_attributes ) + 1 );
                            $prod_attribute->set_visible( true );
                            $prod_attribute->set_variation( $is_variation );
                            $product_attributes[] = $prod_attribute;

                            $wc_product->set_attributes( $product_attributes );
                        }
                    }

                    $this->wooProduct = $wc_product;
                    $this->wooProduct->save();
                }
        }

        public function importVariations($variations){



    $variations_pictures = array();
    if ( ! empty( $variations['Pictures'] ) ) {
        $variations_pictures['Pictures'] = $variations['Pictures'];
        unset( $variations['Pictures'] );
    }

    if ( ! isset( $variations['Variation'][0] ) ) {
        $tempVariationList = array();
        $tempVariationList = $variations['Variation'];
        unset( $variations['Variation'] );
        $variations['Variation'][] = $tempVariationList;
    }

    if ( ! isset( $variations_pictures['Pictures'][0] ) && ! empty( $variations_pictures['Pictures'] ) ) {
        $tempVariationPicturesList = array();
        $tempVariationPicturesList = $variations_pictures['Pictures'];
        unset( $variations_pictures['Pictures'] );
        $variations_pictures['Pictures'][] = $tempVariationPicturesList;
    }

    foreach ( $variations['Variation'] as $key => $prod_variation ) {
        if ( ! isset( $prod_variation['VariationSpecifics'][0]['NameValueList'] ) ) {
            $tempNameValueList = array();
            $tempNameValueList = $prod_variation['VariationSpecifics'][0]['NameValueList'];
            unset( $variations['Variation'][ $key ]['VariationSpecifics'][0]['NameValueList'] );
            $variations['Variation'][ $key ]['VariationSpecifics'][0]['NameValueList'][] = $tempNameValueList;
        }
    }

    if ( isset( $variations['Variation'][0] ) ) {
        foreach ( $variations['Variation'] as $index => $variation ) {
            $variation_post = array( // Setup the post data for the variation

                'post_name'   => 'product-' . $this->wooProductId . '-variation-' . $index,
                'post_status' => 'publish',
                'post_parent' => $this->wooProductId,
                'post_type'   => 'product_variation',
                'guid'        => home_url() . '/?product_variation=product-' . $this->wooProductId . '-variation-' . $index,
            );

            $variation_post_id = wp_insert_post( $variation_post ); // Insert the variation

            // Get product attribute
            $values = array();
            if ( ! empty( $variation['VariationSpecifics'][0]['NameValueList'] ) ) {
                foreach ( $variation['VariationSpecifics']['NameValueList'] as $k2 => $ebay_attr_values ) {
                    $attr_name = $ebay_attr_values['Name'];
                    $values[]  = $ebay_attr_values['Value'];
                    wp_set_object_terms( $variation_post_id, $values, $attr_name );
                    $attribute = sanitize_title( $attr_name );
                    update_post_meta( $variation_post_id, 'attribute_' . $attribute, $ebay_attr_values['Value'] );
                    $thedata = array(
                        $attribute => array(
                            'name'         => $attr_name,
                            'value'        => '',
                            'is_visible'   => '1',
                            'is_variation' => '1',
                            'is_taxonomy'  => '1',
                        ),
                    );
                    update_post_meta( $variation_post_id, '_product_attributes', $thedata );
                    if ( ! empty( $variations_pictures ) ) {
                        foreach ( $variations_pictures['Pictures'] as $key => $wc_var_ebay_pic ) {

                            if ( isset( $wc_var_ebay_pic['VariationSpecificPictureSet'] ) ) {
                                if ( ! isset( $wc_var_ebay_pic['VariationSpecificPictureSet'][0] ) ) {
                                    $temp_var_picture_list = array();
                                    $temp_var_picture_list = $wc_var_ebay_pic['VariationSpecificPictureSet'];
                                    unset( $wc_var_ebay_pic['VariationSpecificPictureSet'] );
                                    $wc_var_ebay_pic['VariationSpecificPictureSet'][] = $temp_var_picture_list;
                                }
                                foreach ( $wc_var_ebay_pic['VariationSpecificPictureSet'] as $key => $wc_var_picture ) {
                                    if ( $wc_var_picture['VariationSpecificValue'] == $ebay_attr_values['Value'] ) {
                                        $image_url = is_array( $wc_var_picture['PictureURL'] ) ? $wc_var_picture['PictureURL'][0] : $wc_var_picture['PictureURL'];
                                        $image_url = remove_query_arg( array( 'set_id' ), $image_url );

                                        $image_name = basename( $image_url );
                                        $upload_dir = wp_upload_dir();
                                        $image_url  = str_replace( 'https', 'http', $image_url );
                                        $upload = wc_rest_upload_image_from_url( esc_url_raw( $image_url ) );
                                        if ( ! is_wp_error( $upload ) ) {
                                            $attach_id = wc_rest_set_uploaded_image_as_attachment( $upload, $variation_post_id );
                                            if ( wp_attachment_is_image( $attach_id ) ) {
                                                $wc_product->set_image_id( $attach_id );
                                                $wc_product->save();
                                                wp_update_post(
                                                    array(
                                                        'ID'         => $attach_id,
                                                        'post_title' => $this->getEbayItemId().'-var-image-'.$key,
                                                    )
                                                );
                                            }
                                        }

                                        if ( $attach_id ) {
                                            $wc_var_product = wc_get_product( $variation_post_id );
                                            $wc_var_product->set_image_id( $attach_id );
                                            $wc_var_product->save();
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
            if ( $variation['Quantity'] - $variation['SellingStatus']['QuantitySold'] > 0 ) {
                update_post_meta( $variation_post_id, '_stock_status', 'instock' );
                update_post_meta( $variation_post_id, '_stock', $variation['Quantity'] - $variation['SellingStatus']['QuantitySold'] );
                update_post_meta( $variation_post_id, '_manage_stock', 'yes' );
                update_post_meta( $this->wooProductId, '_stock_status', 'instock' );
            } else {
                update_post_meta( $variation_post_id, '_stock_status', 'outofstock' );
            }
            $variation_price = isset($variation['StartPrice']['value']) ? $variation['StartPrice']['value'] : $variation['StartPrice'];
            update_post_meta( $variation_post_id, '_sku', $variation['SKU'] );
            update_post_meta( $variation_post_id, '_price', $variation_price );
            update_post_meta( $variation_post_id, '_regular_price', $variation_price );

            $variation_prod = wc_get_product( $variation_post_id );
            $variation_prod->save();
        }
    } else {
        foreach ( $variations['Variation']['VariationSpecifics'][0]['NameValueList'] as $index => $variation_attr ) {
            $attr_name         = $variation_attr['Name'];
            $variation         = $variations['Variation'];
            $variation_post    = array( // Setup the post data for the variation

                'post_title'  => $variation['VariationTitle'],
                'post_name'   => 'product-' . $this->wooProductId . '-variation-' . $index,
                'post_status' => 'publish',
                'post_parent' => $this->wooProductId,
                'post_type'   => 'product_variation',
                'guid'        => home_url() . '/?product_variation=product-' . $this->wooProductId . '-variation-' . $index,
            );
            $variation_post_id = wp_insert_post( $variation_post ); // Insert the variation
            $values            = array();
            $attr_values[]     = $variation_attr['Value'];
            $attr_values       = array_unique( $values );

            wp_set_object_terms( $variation_post_id, $values, $attr_name );

            $attribute = sanitize_title( $attr_name );

            update_post_meta( $variation_post_id, 'attribute_' . $attribute, $variation_attr['Value'] );
            $thedata = array(
                $attribute => array(
                    'name'         => $variation_attr['Value'],
                    'value'        => '',
                    'is_visible'   => '1',
                    'is_variation' => '1',
                    'is_taxonomy'  => '1',
                ),
            );

            update_post_meta( $variation_post_id, '_product_attributes', $thedata );
            if ( ! empty( $variations_pictures ) ) {
                foreach ( $variations_pictures['Pictures'] as $key => $wc_var_ebay_pic ) {
                    if ( isset( $wc_var_ebay_pic['VariationSpecificPictureSet'] ) ) {
                        foreach ( $wc_var_ebay_pic['VariationSpecificPictureSet'] as $key => $wc_var_picture ) {
                            if ( $wc_var_picture['VariationSpecificValue'] == $variation_attr['Value'] ) {
                                $image_url = $wc_var_picture['PictureURL'][0];
                                $image_url = remove_query_arg( array( 'set_id' ), $image_url );
                                $upload = wc_rest_upload_image_from_url( esc_url_raw( $image_url ) );
                                if ( ! is_wp_error( $upload ) ) {
                                    $attach_id = wc_rest_set_uploaded_image_as_attachment( $upload, $variation_post_id );
                                    if ( wp_attachment_is_image( $attach_id ) ) {
                                        $wc_product->set_image_id( $attach_id );
                                        $wc_product->save();
                                        wp_update_post(
                                            array(
                                                'ID'         => $attach_id,
                                                'post_title' => $this->getEbayItemId().'-var-image-'.$key,
                                            )
                                        );
                                    }
                                }
                                

                                if ( $attach_id ) {
                                        $wc_var_product = wc_get_product( $variation_post_id );
                                        $wc_var_product->set_image_id( $attach_id );
                                        $wc_var_product->save();
                                }
                            }
                        }
                    }
                }
            }
        }
        if ( $variation['Quantity'] - $variation['SellingStatus']['QuantitySold'] > 0 ) {
            update_post_meta( $variation_post_id, '_stock_status', 'instock' );
            update_post_meta( $variation_post_id, '_stock', $variation['Quantity'] - $variation['SellingStatus']['QuantitySold'] );
            update_post_meta( $variation_post_id, '_manage_stock', 'yes' );
            update_post_meta( $this->wooProductId, '_stock_status', 'instock' );
        } else {
            update_post_meta( $variation_post_id, '_stock_status', 'outofstock' );
        }
        $variation_price = isset($variation['StartPrice']['value']) ? $variation['StartPrice']['value'] : $variation['StartPrice'];
        update_post_meta( $variation_post_id, '_sku', $variation['SKU'] );
        update_post_meta( $variation_post_id, '_price', $variation_price );
        update_post_meta( $variation_post_id, '_regular_price', $variation_price );

        $variation_prod = wc_get_product( $variation_post_id );
        $variation_prod->save();
    }

}

        public function importListingImages($ebay_images){
            if(is_array($ebay_images) && !is_wp_error($this->getWooProduct())){
                if ( is_array( $ebay_images['PictureDetails']['PictureURL'] ) ) {
                    $image_url = $ebay_images['PictureDetails']['PictureURL'][0];
                } else {
                    $image_url = $ebay_images['PictureDetails']['PictureURL'];
                }
                $wc_product = $this->getWooProduct();
                $product_id = $wc_product->get_id();
                $upload = wc_rest_upload_image_from_url( esc_url_raw( $image_url ) );
					if ( ! is_wp_error( $upload ) ) {
						$attachment_id = wc_rest_set_uploaded_image_as_attachment( $upload, $product_id );
						if ( wp_attachment_is_image( $attachment_id ) ) {
							$wc_product->set_image_id( $attachment_id );
							$wc_product->save();
							wp_update_post(
								array(
									'ID'         => $attachment_id,
									'post_title' => $this->getEbayItemId().'-image-0',
								)
							);
						}
					}

                    if ( is_array( $ebay_images['PictureDetails']['PictureURL'] ) ) {
                        if ( count( $ebay_images['PictureDetails']['PictureURL'] ) != 1 ) {
                            if ( isset( $ebay_images['PictureDetails']['PictureURL'] ) ) {
                                unset( $ebay_images['PictureDetails']['PictureURL'][0] );
    
                                $ebay_images['PictureDetails']['PictureURL'] = array_values( $ebay_images['PictureDetails']['PictureURL'] );
                                if ( isset( $itemDetails['Item']['PictureDetails']['PictureURL'][0] ) ) {
                                    $attach_ids = array();
    
                                    foreach ( $ebay_images['PictureDetails']['PictureURL'] as $key11 => $value11 ) {
                                        $image_url = $value11;
    
                                        if ( ! empty( $image_url ) ) {
    
                                            $upload = wc_rest_upload_image_from_url( esc_url_raw( $image_url ) );
                                            if ( ! is_wp_error( $upload ) ) {
                                                $attachment_id = wc_rest_set_uploaded_image_as_attachment( $upload, $product_id );
                                                if ( wp_attachment_is_image( $attachment_id ) ) {
                                                    $attach_ids[] = $attachment_id;
    
                                                    wp_update_post(
                                                        array(
                                                            'ID'         => $attachment_id,
                                                            'post_title' => $this->getEbayItemId().'-image-' . ($key11 + 1),
                                                        )
                                                    );
    
                                                }
                                            }
                                        }
                                    }
    
                                    $wc_product->set_gallery_image_ids( $attach_ids );
                                    $this->wooProduct = $wc_product;
                                    $wc_product->save();
    
                                } else {
    
                                    $attach_ids   = array();
                                    $attach_ids[] = $ebay_images['PictureDetails']['PictureURL'];
                                    update_post_meta( $product_id, '_product_image_gallery', implode( ',', $attach_ids ) );
                                }
                            }
                        }
                    }
            }
            
        }

        public function importCategory($cateogry){

        }

        public function importProductMeta($product_meta){
            if(!empty($product_meta) && !is_wp_error($this->getWooProduct())){
                $wc_product = $this->getWooProduct();
                foreach($product_meta as $meta_key => $meta_value){
                    $wc_product->update_meta_data($meta_key, $meta_value);
                }
                $this->wooProduct = $wc_product;
                $this->wooProduct->save();
            }
        }
        


    }
}
