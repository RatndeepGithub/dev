<?php

namespace Ced\Ebay\WC;

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	die;
}


if(!class_exists('Listings_Data')){
    class Listings_Data {
        private $ebay_item = [];

        public function setTitle($title){
            $this->ebay_item['title'] = $title;
        }

        public function setPolicyData($policyData){
            $this->ebay_item['policyData'] = $policyData;
        }

        public function setSourceProductId($source_product_id){
            $this->ebay_item['source_product_id'] = $source_product_id;
        }

        public function setCategory($category){
            $this->ebay_item['category'][]['id'] = (string) $category;
        }

        public function setCategoryCondition($category_feature){
            $this->ebay_item['category_feature'] = $category_feature;
        }

        public function setConditionDescription($condition_description = ''){
            $this->ebay_item['condition_description'] = $condition_description;
        }

        public function setItemSpecifics($item_specifics){
            $this->ebay_item['item_specifics'] = $item_specifics;
        }

        public function setBestOfferEnabled($bestofferenabled = false){
            $this->ebay_item['bestofferenabled'] = $bestofferenabled;
        }

        public function setDescription($description){
            $this->ebay_item['description'] = $description;
        }

        public function setPictureUrl($picture_url){
            $this->ebay_item['picture_url'] = $picture_url;
        }

        public function setCurrencey($currencey){
            $this->ebay_item['currency'] = $currencey;
        }

        public function setSite($site){
            $this->ebay_item['site'] = $site;
        }

        public function setCountry($country){
            $this->ebay_item['country'] = $country;
        }

        public function setLocation($location = ''){
            $this->ebay_item['location'] = $location;
        }

        public function setPostalCode($postal_code){
            $this->ebay_item['postal_code'] = $postal_code;
        }

        public function setPrice($price){
            $this->ebay_item['price'] = $price;
        }

        public function setQuantity($quantity){
            $this->ebay_item['quantity'] = $quantity;
        }

        public function setBrand($brand){
            $this->ebay_item['brand'] = $brand;
        }
        public function setBrandMPN($mpn){
            $this->ebay_item['mpn'] = $mpn;
        }

        public function setWarningLevel($WarningLevel){
            $this->ebay_item['WarningLevel'] = $WarningLevel;
        }

        public function setSiteId($site_id){
            $this->ebay_item['site_id'] = $site_id;
        }

        public function setEan($ean){
            $this->ebay_item['ean'] = $ean;
        }

        public function setUpc($upc){
            $this->ebay_item['upc'] = $upc;
        }

        public function setIsbn($isbn){
            $this->ebay_item['isbn'] = $isbn;
        }

        public function setSku($sku = ''){
            $this->ebay_item['sku'] = $sku;
        }

        public function setWeight($weight){
            $this->ebay_item['weight'] = $weight;
        }

        public function setWeightUnit($weight_unit){
            $this->ebay_item['weight_unit'] = $weight_unit;
        }

        public function setVatPercentage($vat_percentage = 0.0){
            $this->ebay_item['vat_percentage'] = $vat_percentage;
        }

        public function setUseEbayTaxRateTable($use_ebay_tax_rate_table){
            $this->ebay_item['use_ebay_tax_rate_table'] = $use_ebay_tax_rate_table;
        }

        public function setVariants($variants){
            $this->ebay_item['variants'] = $variants;
        }

        public function setVariantAttributes($variant_attributes){
            $this->ebay_item['variant_attributes'] = $variant_attributes;
        }
        public function setEbayListingId($listing_id){
            $this->ebay_item['listing_id'] = $listing_id;
        }

        public function setAutoPay($auto_pay = true){
            $this->ebay_item['auto_pay'] = $auto_pay;
        }
        public function getEbayItem() : array{
            return $this->ebay_item;
        }
    }
}
