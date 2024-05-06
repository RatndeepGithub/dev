<?php
/**
 * Header of the extensiom
 *
 * @package  Walmart_Woocommerce_Integration
 * @version  1.0.0
 * @link     https://woocommerce.com/vendor/cedcommerce/
 * @since    1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	die;
}

$section = get_active_section();


?>
<div id="ced_walmart_notices"></div>

<div class="ced-menu-container">
	<?php

	$parts = parse_url( sanitize_text_field( isset( $_SERVER['REQUEST_URI'] ) ? $_SERVER['REQUEST_URI'] : '' ) );
	parse_str( $parts['query'], $query );

	?>

	<ul class="subsubsub">
		<?php
		$navigation_menus = get_navigation_menus();
		$count            = 0;
		$total_items      = count( $navigation_menus );
		foreach ( $navigation_menus as $label => $href ) {
			$count++;
			$href  = $href . '&store_id=' . ced_walmart_get_current_active_store();
			$class = '';
			if ( $label == $section ) {
				$class = 'current';
			}
			$label = str_replace( '_', ' ', $label );
			echo '<li class="all">';
			echo "<a href='" . esc_url( $href ) . "' class='" . esc_attr( $class ) . "' aria-current='page'> " . esc_html( __( ucwords( $label ), 'walmart-woocommerce-integration' ) ) . '</a>';
			echo ( ( $count < $total_items ? '|' : '' ) );
			echo ' </li>';
		}
		?>
	</ul>



	<div class="ced-right">
		<select style="min-width: 160px;" class="attachment-filters" id="ced_walmart_switch_account">
			<?php
			foreach ( ced_walmart_return_partner_detail_option() as $key => $value ) {
				$query['store_id'] = $key;
				?>
				<option value="<?php echo esc_url( ced_get_navigation_url( 'walmart', $query ) ); ?>" <?php echo ( ced_walmart_get_current_active_store() == $key ? 'selected' : '' ); ?>><?php echo esc_attr( ced_walmart_get_store_name_by_id( $key ) ); ?></option>
				<?php
			}
			?>
			<option value="<?php echo esc_url( ced_get_navigation_url( 'walmart', array( 'add-new-account' => 'yes' ) ) ); ?>">+ Add another account</option>
		</select>
	</div>
</div>
