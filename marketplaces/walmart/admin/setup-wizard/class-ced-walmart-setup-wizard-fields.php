<?php



class Ced_Walmart_Setup_Wizard_Fields {



	public function __construct() {
	}

	public function ced_walmart_account_config_fields() {

		$ced_walmart_account_config_fields = array(
			array(
				'type'     => 'text',
				'id'       => 'ced_walmart_client_id',
				'fields'   => array(
					'id'    => 'ced_walmart_client_id',
					'label' => __( 'Client Id', 'walmart-woocommerce-integration' ),
				),
				'required' => true,
			),
			array(
				'type'     => 'text',
				'id'       => 'ced_walmart_client_secret',
				'fields'   => array(
					'id'    => 'ced_walmart_client_secret',
					'label' => __( 'Client Secret', 'walmart-woocommerce-integration' ),
				),
				'required' => true,
			),
			array(
				'type'     => 'dropdown',
				'id'       => 'ced_walmart_environment',
				'fields'   => array(
					'id'       => 'ced_walmart_environment',
					'label'    => __( 'Environment', 'walmart-woocommerce-integration' ),
					'desc_tip' => true,
					'options'  => array(
						'production' => __( 'Production', 'walmart-woocommerce-integration' ),
					),
					'class'    => 'wc_input_price',
				),
				'required' => true,
			),
		);

		/**
		 * Filter for getting config fields
		 *
		 * @since  1.0.0
		 */
		$ced_walmart_account_config_fields = apply_filters( 'ced_walmart_account_config_fields', $ced_walmart_account_config_fields );

		return $ced_walmart_account_config_fields;
	}


	public function ced_walmart_setup_wizard_global_fields() {

		$ced_walmart_account_global_fields = array(
			array(
				'type'        => 'text',
				'id'          => 'sku',
				'label'       => __( 'SKU', 'walmart-woocommerce-integration' ),
				'description' => __( 'SKU', 'walmart-woocommerce-integration' ),
				'required'    => true,
			),
			array(
				'type'        => 'dropdown',
				'id'          => 'identifier_type',
				'label'       => __( 'Identifier Type', 'walmart-woocommerce-integration' ),
				'options'     => array(
					'GTIN' => __( 'GTIN', 'walmart-woocommerce-integration' ),
					'UPC'  => __( 'UPC', 'walmart-woocommerce-integration' ),
					'EAN'  => __( 'EAN', 'walmart-woocommerce-integration' ),
					'ISBN' => __( 'ISBN', 'walmart-woocommerce-integration' ),
				),
				'description' => __( 'Product ID Type', 'walmart-woocommerce-integration' ),

			),
			array(
				'type'        => 'text',
				'id'          => 'identifier_value',
				'label'       => __( 'Identifier Value', 'walmart-woocommerce-integration' ),
				'description' => __( 'Product ID (Barcode)', 'walmart-woocommerce-integration' ),
				'required'    => true,
			),

			array(
				'type'        => 'text',
				'id'          => 'title',
				'label'       => __( 'Product Title', 'walmart-woocommerce-integration' ),
				'description' => __( 'Product Name ( Max Char : 200)', 'walmart-woocommerce-integration' ),
				'required'    => true,
			),

			array(
				'type'        => 'text',
				'id'          => 'product_brand',
				'label'       => __( 'Brand', 'walmart-woocommerce-integration' ),
				'description' => __( 'Product Brand', 'walmart-woocommerce-integration' ),
				'required'    => true,
			),

			array(
				'type'        => 'text',
				'id'          => 'price',
				'label'       => __( 'Price', 'walmart-woocommerce-integration' ),
				'description' => __( 'Product Selling Price', 'walmart-woocommerce-integration' ),
				'required'    => true,
			),

			array(
				'type'        => 'text',
				'id'          => 'package_weight',
				'label'       => __( 'Shipping Weight', 'walmart-woocommerce-integration' ),
				'description' => __( 'Shipping Weight', 'walmart-woocommerce-integration' ),
				'required'    => true,
			),

			array(
				'type'        => 'text',
				'id'          => 'description',
				'label'       => __( 'Short Description', 'walmart-woocommerce-integration' ),
				'description' => __( 'Short Description ( Max Char : 4000)', 'walmart-woocommerce-integration' ),
				'required'    => true,
			),

			array(
				'type'        => 'dropdown',
				'id'          => 'mpaaRating',
				'options'     => array(
					'G'         => __( 'G', 'walmart-woocommerce-integration' ),
					'NC-17'     => __( 'NC-17', 'walmart-woocommerce-integration' ),
					'PG-13'     => __( 'PG-13', 'walmart-woocommerce-integration' ),
					'R'         => __( 'R', 'walmart-woocommerce-integration' ),
					'Not Rated' => __( 'Not Rated', 'walmart-woocommerce-integration' ),
					'PG'        => __( 'PG', 'walmart-woocommerce-integration' ),
				),
				'label'       => __( 'MPAA Rating', 'walmart-woocommerce-integration' ),
				'description' => __( 'Motion Picture Association of America Rating ', 'walmart-woocommerce-integration' ),
				'required'    => true,
			),

			array(
				'type'        => 'text',
				'id'          => 'tvRating',
				'label'       => __( 'TV Rating', 'walmart-woocommerce-integration' ),
				'description' => __( 'TV Rating', 'walmart-woocommerce-integration' ),
				'required'    => true,
			),
			array(
				'type'        => 'text',
				'id'          => 'tireSize',
				'label'       => __( 'Tire Size', 'walmart-woocommerce-integration' ),
				'description' => __( 'Tire Size', 'walmart-woocommerce-integration' ),
				'required'    => true,
			),
			array(
				'type'        => 'text',
				'id'          => 'tireWidth',
				'label'       => __( 'Tire Width', 'walmart-woocommerce-integration' ),
				'description' => __( 'Tire Width', 'walmart-woocommerce-integration' ),
				'required'    => true,
			),

			array(
				'type'        => 'dropdown',
				'id'          => 'fitType',
				'options'     => array(
					'Specific'  => __( 'Specific', 'walmart-woocommerce-integration' ),
					'Universal' => __( 'Universal', 'walmart-woocommerce-integration' ),
				),
				'label'       => __( 'Fit Type ( Cat : Vehicle )', 'walmart-woocommerce-integration' ),
				'description' => __( 'Fit Type', 'walmart-woocommerce-integration' ),
				'required'    => true,
			),

			array(
				'type'        => 'dropdown',
				'id'          => 'esrbRating',
				'options'     => array(
					'Teen'            => __( 'Teen', 'walmart-woocommerce-integration' ),
					'Everyone'        => __( 'Everyone', 'walmart-woocommerce-integration' ),
					'Early Childhood' => __( 'Early Childhood', 'walmart-woocommerce-integration' ),
					'Adult'           => __( 'Adult', 'walmart-woocommerce-integration' ),
					'Mature'          => __( 'Mature', 'walmart-woocommerce-integration' ),
					'Unrated'         => __( 'Unrated', 'walmart-woocommerce-integration' ),
					'Pending'         => __( 'Pending', 'walmart-woocommerce-integration' ),
					'Everyone 10+'    => __( 'Everyone 10+', 'walmart-woocommerce-integration' ),
				),
				'label'       => __( 'ESRB Rating', 'walmart-woocommerce-integration' ),
				'description' => __( 'ESRB Rating ( Video Games )', 'walmart-woocommerce-integration' ),
				'required'    => true,
			),
		);

/**
		 * Filter for getting global fields
		 *
		 * @since  1.0.0
		 */
		$ced_walmart_account_global_fields = apply_filters( 'ced_walmart_account_global_fields', $ced_walmart_account_global_fields );

		return $ced_walmart_account_global_fields;
	}
}
