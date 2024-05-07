<?php

namespace Ced\Ebay\WC;

if(!class_exists('Product_Data')){
    class Product_Data{
        private $wc_product_id;

        private $ebay_user;

        private $ebay_site;

        private $loadInstance;

        private $wc_product;

        private $product_type;


        private function loadDependency($file, $className) {

			if ( file_exists( $file ) ) {
				require_once $file;
                $this->loadInstance = $className::get_instance();
			}
		}


        public function setEbayUser($ebay_user){
            $this->ebay_user = $ebay_user;
        }

        public function setEbaySite($ebay_site){
            $this->ebay_site = $ebay_site;
        }
        
        public function setProductId($wc_product_id) : void{
            $this->wc_product_id = $wc_product_id;
            $this->setWcProductObject($wc_product_id);
        }

        public function setWcProductObject($wc_product_id){
            $this->wc_product = wc_get_product( $wc_product_id);
            if(!($this->wc_product instanceof \WC_Product)){
                return new \WP_Error( 'wc_product', 'Invalid Product' );
            }
            $this->product_type = $this->wc_product->get_type();
        }
        private function getDataInGlobalSettings($index){
            $dataInGlobalSettings = get_option( 'ced_ebay_global_settings', false );
            if(isset($dataInGlobalSettings[$this->ebay_user][$this->ebay_site])){
                return $dataInGlobalSettings[$this->ebay_user][$this->ebay_site][$index];
            } else {
                return new \WP_Error( 'global_settings', 'The specified data is not found in the global settings' );
            }
        }

        private function getProfileAssignedData($meta_key){
            $this->loadDependency(CED_EBAY_DIRPATH . 'admin/ebay/lib/ebayProducts.php', '\Ced\Ebay\Class_Ced_EBay_Products');
                $meta_value = $this->loadInstance->fetchMetaValueOfProduct($this->wc_product_id, $meta_key);
                return $meta_value;
        }

        private function getPriceWithMarkup($price, $markup_type, $markup_value){
            if ( 'Fixed_Increase' == $markup_type ) {
                $price += $markup_value;
            } elseif ( 'Percentage_Increase' == $markup_type ) {
                $price += ( $price * $markup_value ) / 100 ;
            } elseif ( 'Percentage_Decrease' == $markup_type ) {
                $price -= ( $price * $markup_value ) / 100 ;
            } elseif ( 'Fixed_Decrease' == $markup_type ) {
                $price -= $markup_value;
            }

            return $price;
        }       

        public function getStock(){
            $listing_stock_type    = !empty($this->getDataInGlobalSettings('ced_ebay_product_stock_type')) ? $this->getDataInGlobalSettings('ced_ebay_product_stock_type') : false;
            $listing_stock    = !empty($this->getDataInGlobalSettings('ced_ebay_listing_stock')) ? $this->getDataInGlobalSettings('ced_ebay_listing_stock') : false;
            switch($this->product_type){
                case 'simple' :
                    $stock = null;
                    $is_managing_stock = $this->wc_product->managing_stock();
                    $stock_status = $this->wc_product->get_stock_status();
                    $stock = $this->wc_product->get_stock_quantity();
                    if(is_null($stock) && $is_managing_stock){
                        return new \WP_Error( 'stock', 'Invalid stock value' );
                    }
                    if(!$is_managing_stock && 'instock' == $stock_status){
                        if ( ! empty( $listing_stock_type ) && ! empty( $listing_stock ) && 'MaxStock' == $listing_stock_type ){
                            $stock = $listing_stock;
                        } else {
                            $stock = 1;
                        }
                    } elseif ( 'outofstock' != $stock_status ) {
                        if ( ! empty( $listing_stock_type ) && ! empty( $listing_stock ) && 'MaxStock' == $listing_stock_type ) {
                            if ( $stock > $listing_stock ) {
                                $stock = $listing_stock;
                            } else {
                                if ( $stock < 1 ) {
                                    $stock = '0';
                                }
                            }
                        } else {
                            if ( $stock < 1 ) {
                                $stock = '0';
                            }
                        }
                    } else {
                        $stock = 0;
                    }
                    return $stock;
                case 'variable' :
                    $allVariations       = $this->wc_product->get_children();
                    $variations_stock_levels = [];
                    if(!empty($allVariations) && is_array($allVariations)){
                        $listing_stock_type    = !empty($this->getDataInGlobalSettings('ced_ebay_product_stock_type')) ? $this->getDataInGlobalSettings('ced_ebay_product_stock_type') : false;
                        $listing_stock    = !empty($this->getDataInGlobalSettings('ced_ebay_listing_stock')) ? $this->getDataInGlobalSettings('ced_ebay_listing_stock') : false;
                        foreach($allVariations as $key => $variation_id){
                            $stock = null;
                            $wc_var_prod = wc_get_product($variation_id);
                            if(! $wc_var_prod instanceof \WC_Product_Variation){
                                continue;
                            }
                            $is_managing_stock = $wc_var_prod->managing_stock();
                            $stock_status = $wc_var_prod->get_stock_status();
                            $stock = $wc_var_prod->get_stock_quantity();
                            if(!$is_managing_stock && 'instock' == $stock_status){
                                if ( ! empty( $listing_stock_type ) && ! empty( $listing_stock ) && 'MaxStock' == $listing_stock_type ){
                                    $stock = $listing_stock;
                                } else {
                                    $stock = 1;
                                }
                            } elseif ( 'outofstock' != $stock_status ) {
                                if ( ! empty( $listing_stock_type ) && ! empty( $listing_stock ) && 'MaxStock' == $listing_stock_type ) {
                                    if ( $stock > $listing_stock ) {
                                        $stock = $listing_stock;
                                    } else {
                                        if ( $stock < 1 ) {
                                            $stock = '0';
                                        }
                                    }
                                } else {
                                    if ( $stock < 1 ) {
                                        $stock = '0';
                                    }
                                }
                            } else {
                                $stock = 0;
                            }
                            $variations_stock_levels[$variation_id] = $stock;
                            if(!empty($variations_stock_levels)){
                                return $variations_stock_levels;
                            } else {
                                return new \WP_Error( 'stock', 'No variations stock found for this product' );
                            }
                        }
                    }

                    case 'variation':
                        $stock = null;
                        $is_managing_stock = $this->wc_product->managing_stock();
                        $stock_status = $this->wc_product->get_stock_status();
                        $stock = $this->wc_product->get_stock_quantity();
                        if(is_null($stock) && $is_managing_stock){
                            return new \WP_Error( 'stock', 'Invalid stock value' );
                        }
                        if(!$is_managing_stock && 'instock' == $stock_status){
                            if ( ! empty( $listing_stock_type ) && ! empty( $listing_stock ) && 'MaxStock' == $listing_stock_type ){
                                $stock = $listing_stock;
                            } else {
                                $stock = 1;
                            }
                        } elseif ( 'outofstock' != $stock_status ) {
                            if ( ! empty( $listing_stock_type ) && ! empty( $listing_stock ) && 'MaxStock' == $listing_stock_type ) {
                                if ( $stock > $listing_stock ) {
                                    $stock = $listing_stock;
                                } else {
                                    if ( $stock < 1 ) {
                                        $stock = '0';
                                    }
                                }
                            } else {
                                if ( $stock < 1 ) {
                                    $stock = '0';
                                }
                            }
                        } else {
                            $stock = 0;
                        }
                        return $stock;
            }
        }
        public function getPrice(){

            $this->product_type = $this->wc_product->get_type();
            switch($this->product_type){
                case 'simple' :
                    $price = $this->wc_product->get_price();
                    if($price > 0){
                        
                        $price_markup_type    = !empty($this->getDataInGlobalSettings('ced_ebay_product_markup_type')) ? $this->getDataInGlobalSettings('ced_ebay_product_markup_type') : '';
                        $price_markup_value    = !empty($this->getDataInGlobalSettings('ced_ebay_product_markup')) ? $this->getDataInGlobalSettings('ced_ebay_product_markup') : 0;
                        if ( ! empty( $price_markup_type ) && ! empty( $price_markup_value ) ) {
                            $price = $this->getPriceWithMarkup($price, $price_markup_type, $price_markup_value);
                        }
                        $profile_price_markup_type = !empty($this->getProfileAssignedData( '_umb_ebay_profile_price_markup_type' )) ? $this->getProfileAssignedData( '_umb_ebay_profile_price_markup_type' ) : '';
                        $profile_price_markup      = !empty($this->getProfileAssignedData( '_umb_ebay_profile_price_markup' )) ? $this->getProfileAssignedData( '_umb_ebay_profile_price_markup' ) : 0;
                        if ( ! empty( $profile_price_markup_type ) && ! empty( $profile_price_markup ) ){
                            $price = $this->getPriceWithMarkup($price, $profile_price_markup_type, $profile_price_markup);
                        }
                        return $price;
                    } else {
                        return new \WP_Error( 'price', 'Invalid product price' );
                    }
                case 'variable':
                    $allVariations       = $this->wc_product->get_children();
                    $variations_prices = [];
                    if(!empty($allVariations) && is_array($allVariations)){
                        
                        $price_markup_type    = !empty($this->getDataInGlobalSettings('ced_ebay_product_markup_type')) ? $this->getDataInGlobalSettings('ced_ebay_product_markup_type') : '';
                        $price_markup_value    = !empty($this->getDataInGlobalSettings('ced_ebay_product_markup')) ? $this->getDataInGlobalSettings('ced_ebay_product_markup') : 0;

                        $profile_price_markup_type = !empty($this->getProfileAssignedData( '_umb_ebay_profile_price_markup_type' )) ? $this->getProfileAssignedData( '_umb_ebay_profile_price_markup_type' ) : '';
                        $profile_price_markup      = !empty($this->getProfileAssignedData( '_umb_ebay_profile_price_markup' )) ? $this->getProfileAssignedData( '_umb_ebay_profile_price_markup' ) : 0;

                        foreach($allVariations as $key => $variation_id){
                            $variation_price = 0;
                            $wc_var_prod = wc_get_product($variation_id);
                            if(! $wc_var_prod instanceof \WC_Product_Variation){
                                continue;
                            }
                            $variation_price = $wc_var_prod->get_price();
                            if(!empty($variation_price)){
                                if ( ! empty( $price_markup_type ) && ! empty( $price_markup_value ) ) {
                                    $variation_price = $this->getPriceWithMarkup($variation_price, $price_markup_type, $price_markup_value);
                                }
                                if ( ! empty( $profile_price_markup_type ) && ! empty( $profile_price_markup ) ){
                                    $variation_price = $this->getPriceWithMarkup($variation_price, $profile_price_markup_type, $profile_price_markup);
                                }
                                
                                $variations_prices[$variation_id] = $variation_price;
                            }
                        }
                        
                        if(!empty($variation_prices)){
                            return $variation_prices;
                        } else {
                            return new \WP_Error( 'price', 'No variations prices found for this product' );
                        }
                    } else {
                        return new \WP_Error( 'price', 'No variations found for this product' );
                    }

                    case 'variation':
                        $price = $this->wc_product->get_price();
                        if($price > 0){
                            $price_markup_type    = !empty($this->getDataInGlobalSettings('ced_ebay_product_markup_type')) ? $this->getDataInGlobalSettings('ced_ebay_product_markup_type') : '';
                            $price_markup_value   = !empty($this->getDataInGlobalSettings('ced_ebay_product_markup')) ? $this->getDataInGlobalSettings('ced_ebay_product_markup') : 0;
                            if ( ! empty( $price_markup_type ) && ! empty( $price_markup_value ) ) {
                                $price = $this->getPriceWithMarkup($price, $price_markup_type, $price_markup_value);
                            }
                            $profile_price_markup_type = !empty($this->getProfileAssignedData( '_umb_ebay_profile_price_markup_type' )) ? $this->getProfileAssignedData( '_umb_ebay_profile_price_markup_type' ) : '';
                            $profile_price_markup      = !empty($this->getProfileAssignedData( '_umb_ebay_profile_price_markup' )) ? $this->getProfileAssignedData( '_umb_ebay_profile_price_markup' ) : 0;
                            if ( ! empty( $profile_price_markup_type ) && ! empty( $profile_price_markup ) ){
                                $price = $this->getPriceWithMarkup($price, $profile_price_markup_type, $profile_price_markup);
                            }
                            return $price;
                        } else {
                            return new \WP_Error( 'price', 'Invalid product price' );
                        }                        
                default :
                    return new \WP_Error( 'price', 'Invalid product type' );
            }

            }
        }
        
    }
