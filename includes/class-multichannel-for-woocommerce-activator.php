<?php

/**
 * Fired during plugin activation
 *
 * @link       https://woocommerce.com/vendor/cedcommerce/
 * @since      1.0.0
 *
 * @package    Multichannel_By_Cedcommerce
 * @subpackage Multichannel_By_Cedcommerce/includes
 */

/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @since      1.0.0
 * @package    Multichannel_By_Cedcommerce
 * @subpackage Multichannel_By_Cedcommerce/includes
 */
class Multichannel_By_Cedcommerce_Activator {

	/**
	 * Short Description. (use period)
	 *
	 * Long Description.
	 *
	 * @since    1.0.0
	 */
	public static function activate() {

		global $wpdb;
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		// Etsy Profile
		$tableName            = $wpdb->prefix . 'ced_etsy_profiles';
		$create_profile_table =
		"CREATE TABLE $tableName (
		id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
		profile_name VARCHAR(255) NOT NULL,
		profile_status VARCHAR(255) NOT NULL,
		shop_name VARCHAR(255) DEFAULT NULL,
		profile_data TEXT DEFAULT NULL,
		woo_categories TEXT DEFAULT NULL,
		PRIMARY KEY (id)
		);";
		dbDelta( $create_profile_table );

		// Ebay Profile
		$tableName = $wpdb->prefix . 'ced_ebay_profiles';

		$create_profile_table =
			"CREATE TABLE $tableName (
			id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			profile_name VARCHAR(255) NOT NULL,
			profile_status VARCHAR(255) NOT NULL,
			ebay_user TEXT NOT NULL,
			profile_data TEXT DEFAULT NULL,
			woo_categories TEXT DEFAULT NULL,
			ebay_site TEXT DEFAULT NULL,
			PRIMARY KEY (id)
		);";
		dbDelta( $create_profile_table );

		// Ebay Shipping
		$tableName = $wpdb->prefix . 'ced_ebay_shipping';

		$create_shipping_table =
			"CREATE TABLE $tableName (
			id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			shipping_name VARCHAR(255) NOT NULL,
			weight_range VARCHAR(255) NOT NULL,
			user_id VARCHAR(255) DEFAULT NULL,
			shipping_data TEXT DEFAULT NULL,
			PRIMARY KEY (id)
		);";
		dbDelta( $create_shipping_table );

		// Ebay Upload
		$tableName = $wpdb->prefix . 'ced_ebay_bulk_upload';

		$create_bulk_upload_table =
				"CREATE TABLE $tableName (
        id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
		profile_id INT NOT NULL,
        product_id INT NOT NULL,
		bulk_action_type  VARCHAR(255) NOT NULL,
        operation_status  VARCHAR(255) NOT NULL,
        error  LONGTEXT,
        user_id VARCHAR(255) NOT NULL,
		site_id INT NOT NULL,
		scheduled_time VARCHAR(255) NOT NULL,
        PRIMARY KEY (id)
        );";

		dbDelta( $create_bulk_upload_table );

		// Amazon Profile
		$tableName = $wpdb->prefix . 'ced_amazon_profiles';

		$create_profile_table =
			"CREATE TABLE $tableName (
			id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			profile_name VARCHAR(255) NOT NULL,
			primary_category VARCHAR(255) NOT NULL,
			secondary_category VARCHAR(255) NOT NULL,
			browse_nodes DOUBLE NOT NULL,
			browse_nodes_name VARCHAR(255) NOT NULL,
			amazon_categories_name LONGTEXT NOT NULL,
			category_attributes_response LONGTEXT DEFAULT NULL,
			wocoommerce_category TEXT(255) DEFAULT NULL,
			category_attributes_structure LONGTEXT DEFAULT NULL,
			category_attributes_data LONGTEXT DEFAULT NULL,
			template_type TEXT(255) DEFAULT NULL,
			file_url LONGTEXT DEFAULT NULL,
			seller_id VARCHAR(255) DEFAULT NULL,
			PRIMARY KEY (id) );";

		// Amazon Feed
		dbDelta( $create_profile_table );

		$feedTableName     = $wpdb->prefix . 'ced_amazon_feeds';
		$create_feed_table =
			"CREATE TABLE $feedTableName (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			feed_id DOUBLE NOT NULL,
			feed_action VARCHAR(255) NOT NULL,
			feed_location VARCHAR(255) NOT NULL,
			feed_date_time DATETIME NOT NULL, 
			sku JSON DEFAULT NULL,
			response TEXT DEFAULT NULL, 
			PRIMARY KEY (id) );";

		dbDelta( $create_feed_table );

		// Auth info
		if ( ! get_option( 'ced_etsy_auth_info', '' ) || is_null( get_option( 'ced_etsy_auth_info', '' ) ) || empty( get_option( 'ced_etsy_auth_info', '' ) ) || '' == get_option( 'ced_etsy_auth_info', '' ) ) {
			update_option(
				'ced_etsy_auth_info',
				array(
					'scrt' => 'LA1tU+0AQ7PNGjcMmeSvVjCabqB9Lcqt',
					'ky'   => base64_encode( 'Q2VkRXRzeUBXb29AIyQlXiYqS2V5' ),
				)
			);
		}
	}
}
