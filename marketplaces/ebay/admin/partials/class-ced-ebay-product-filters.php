<?php
namespace Ced\Ebay;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


class Product_Filters {
    public function getFilteredData($per_page, $page_number) {
        $args = ['post_type' => 'product', 'posts_per_page' => $per_page, 'paged' => $page_number];

        $this->applyStandardFilters($args);

        if (empty($_REQUEST['searchTerm'])) {
            $this->applyConditionalFilters($args);
        }

        return $args;
    }

    private function applyStandardFilters(&$args) {
        $filters = [
            'pro_cat_sorting' => function($value) use (&$args) { $args['tax_query'][] = $this->createTaxQuery('product_cat', $value); },
            'pro_type_sorting' => function($value) use (&$args) { $args['tax_query'][] = $this->createTaxQuery('product_type', $value); },
            'pro_stock_sorting' => function($value) use (&$args) { $args['meta_query'][] = $this->createMetaQuery('_stock_status', $value); },
            'status_sorting' => function($value) use (&$args) { $args['meta_query'][] = $this->createStatusMetaQuery($value); }
        ];

        foreach ($filters as $key => $func) {
            if (isset($_REQUEST[$key]) && !empty($_REQUEST[$key]) && "-1" != $_REQUEST[$key]) {
                $value = sanitize_text_field($_REQUEST[$key]);
                $func($value);
            }
        }
    }

    private function applyConditionalFilters($args) {
        if ( ! empty( $_REQUEST['prodID'] ) ) {
            $prodID = isset( $_REQUEST['prodID'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['prodID'] ) ) : '';
            if ( ! empty( $prodID ) ) {
                $args['prodID'] = $prodID;
            }
        }

        if ( ! empty( $_REQUEST['profileID'] ) ) {
            $profileID = isset( $_GET['profileID'] ) ? sanitize_text_field( wp_unslash( $_GET['profileID'] ) ) : '';
            if ( ! empty( $profileID ) ) {
                $args['profileID'] = $profileID;
            }
        }

        return $args;


    }

    private function createMetaQuery($key, $value) {
        return [
            'key' => $key,
            'value' => $value,
            'compare' => '='
        ];
    }

    private function createTaxQuery($taxonomy, $term) {
        return [
            'taxonomy' => $taxonomy,
            'field' => 'id',
            'terms' => [$term]
        ];
    }

    private function createStatusMetaQuery($status) {
        if ($status === 'Uploaded') {
            return [
                'key' => '_ced_ebay_listing_id_' . sanitize_text_field(wp_unslash($_REQUEST['user_id'])).'>'.sanitize_text_field(wp_unslash($_REQUEST['sid'])),
                'value' => '',
                'compare' => '!='
            ];
        } elseif ($status === 'NotUploaded') {
            return [
                'key' => '_ced_ebay_listing_id_' . sanitize_text_field(wp_unslash($_REQUEST['user_id'])).'>'.sanitize_text_field(wp_unslash($_REQUEST['sid'])),
                'compare' => 'NOT EXISTS'
            ];
        }
    }
}