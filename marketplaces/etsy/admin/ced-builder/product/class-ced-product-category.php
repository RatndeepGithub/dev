<?php
/**
 * Product Category Class.
 *
 * @version 2.1.1
 * @package category-fetch-for-woocommerce-categories.
 */

namespace Cedcommerce\Product;

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	die;
}

if ( ! class_exists( 'Ced_Product_Category' ) ) {
	/**
	 * Get Etsy Category.
	 */
	class Ced_Product_Category {


		public static $_instance;

		/**
		 * Ced_Etsy_Config Instance.
		 *
		 * Ensures only one instance of Ced_Etsy_Config is loaded or can be loaded.
		 *
		 * @since 1.0.0
		 * @static
		 */
		public static function get_instance() {
			if ( is_null( self::$_instance ) ) {
				self::$_instance = new self();
			}
			return self::$_instance;
		}

		/**
		 * Etsy getting seller taxonomies
		 *
		 * @since    1.0.0
		 */
		public function get_etsy_categories( $shop_name = '' ) {
			$categories = etsy_request()->ced_etsy_remote_req( 'taxonomy/nodes', array(), array( 'shop_id' => get_etsy_shop_id( $shop_name ) ), 'GET' );
			return $categories;
		}

		/**
		 * Etsy Storing Categories
		 *
		 * @since    1.0.0
		 */
		public function ced_etsy_store_categories( $fetchedCategories, $ajax = '' ) {
			foreach ( $fetchedCategories['results'] as $key => $value ) {
				if ( count( $value['children'] ) > 0 ) {
					$arr1[] = array(
						'id'       => $value['id'],
						'name'     => $value['name'],
						'path'     => $value['full_path_taxonomy_ids'],
						'children' => count( $value['children'] ),
					);
				} else {
					$arr1[] = array(
						'id'       => $value['id'],
						'name'     => $value['name'],
						'path'     => $value['full_path_taxonomy_ids'],
						'children' => 0,
					);
				}
				foreach ( $value['children'] as $key1 => $value1 ) {
					if ( count( $value1['children'] ) > 0 ) {
						$arr2[] = array(
							'parent_id' => $value['parent_id'],
							'id'        => $value1['id'],
							'name'      => $value1['name'],
							'path'      => $value1['full_path_taxonomy_ids'],
							'children'  => count( $value1['children'] ),
						);
					} else {
						$arr2[] = array(
							'parent_id' => $value['parent_id'],
							'id'        => $value1['id'],
							'name'      => $value1['name'],
							'path'      => $value1['full_path_taxonomy_ids'],
							'children'  => 0,
						);
					}
					foreach ( $value1['children'] as $key2 => $value2 ) {
						if ( count( $value2['children'] ) > 0 ) {
							$arr3[] = array(
								'parent_id' => $value1['parent_id'],
								'id'        => $value2['id'],
								'name'      => $value2['name'],
								'path'      => $value2['full_path_taxonomy_ids'],
								'children'  => count( $value2['children'] ),
							);
						} else {
							$arr3[] = array(
								'parent_id' => $value1['parent_id'],
								'id'        => $value2['id'],
								'name'      => $value2['name'],
								'path'      => $value2['full_path_taxonomy_ids'],
								'children'  => 0,
							);
						}
						foreach ( $value2['children'] as $key3 => $value3 ) {
							if ( count( $value3['children'] ) > 0 ) {
								$arr4[] = array(
									'parent_id' => $value2['parent_id'],
									'id'        => $value3['id'],
									'name'      => $value3['name'],
									'path'      => $value3['full_path_taxonomy_ids'],
									'children'  => count( $value3['children'] ),
								);
							} else {
								$arr4[] = array(
									'parent_id' => $value2['parent_id'],
									'id'        => $value3['id'],
									'name'      => $value3['name'],
									'path'      => $value3['full_path_taxonomy_ids'],
									'children'  => 0,
								);
							}
							foreach ( $value3['children'] as $key4 => $value4 ) {
								if ( count( $value4['children'] ) > 0 ) {
									$arr5[] = array(
										'parent_id' => $value3['parent_id'],
										'id'        => $value4['id'],
										'name'      => $value4['name'],
										'path'      => $value4['full_path_taxonomy_ids'],
										'children'  => count( $value4['children'] ),
									);
								} else {
									$arr5[] = array(
										'parent_id' => $value3['parent_id'],
										'id'        => $value4['id'],
										'name'      => $value4['name'],
										'path'      => $value4['full_path_taxonomy_ids'],
										'children'  => 0,
									);
								}
								foreach ( $value4['children'] as $key5 => $value5 ) {
									if ( count( $value5['children'] ) > 0 ) {
										$arr6[] = array(
											'parent_id' => $value4['parent_id'],
											'id'        => $value5['id'],
											'name'      => $value5['name'],
											'path'      => $value5['full_path_taxonomy_ids'],
											'children'  => count( $value5['children'] ),
										);
									} else {
										$arr6[] = array(
											'parent_id' => $value4['parent_id'],
											'id'        => $value5['id'],
											'name'      => $value5['name'],
											'path'      => $value5['full_path_taxonomy_ids'],
											'children'  => 0,
										);
									}
									foreach ( $value5['children'] as $key6 => $value6 ) {
										if ( is_array( $value6['children'] ) && ! empty( $value6['children'] ) ) {

											$arr7[] = array(
												'parent_id' => $value5['parent_id'],
												'id'       => $value6['id'],
												'name'     => $value6['name'],
												'path'     => $value6['full_path_taxonomy_ids'],
												'children' => count( $value6['children'] ),
											);

										} else {
											$arr7[] = array(
												'parent_id' => $value5['parent_id'],
												'id'       => $value6['id'],
												'name'     => $value6['name'],
												'path'     => $value6['full_path_taxonomy_ids'],
												'children' => 0,
											);
										}
									}
								}
							}
						}
					}
				}
			}

			$folderName        = CED_ETSY_DIRPATH . 'admin/lib/json/';
			$catFirstLevelFile = $folderName . 'category.json';
			file_put_contents( $catFirstLevelFile, json_encode( $fetchedCategories['results'] ) );

			$catFirstLevelFile = $folderName . 'categoryLevel-1.json';
			file_put_contents( $catFirstLevelFile, json_encode( $arr1 ) );
			$catSecondLevelFile = $folderName . 'categoryLevel-2.json';
			file_put_contents( $catSecondLevelFile, json_encode( $arr2 ) );

			$catThirdLevelFile = $folderName . 'categoryLevel-3.json';
			file_put_contents( $catThirdLevelFile, json_encode( $arr3 ) );
			$catFourthLevelFile = $folderName . 'categoryLevel-4.json';
			file_put_contents( $catFourthLevelFile, json_encode( $arr4 ) );

			$catFifthLevelFile = $folderName . 'categoryLevel-5.json';
			file_put_contents( $catFifthLevelFile, json_encode( $arr5 ) );
			$catSixthLevelFile = $folderName . 'categoryLevel-6.json';
			file_put_contents( $catSixthLevelFile, json_encode( $arr6 ) );

			$catSeventhLevelFile = $folderName . 'categoryLevel-7.json';
			file_put_contents( $catSeventhLevelFile, json_encode( $arr7 ) );

			update_option( 'ced_etsy_categories_fetched', 'Yes' );
			if ( $ajax ) {
				return 'true';
				die;
			}
		}
	}
}
