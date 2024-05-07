<?php
// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	die;
}
$file = CED_EBAY_DIRPATH . 'admin/partials/header.php';
if ( file_exists( $file ) ) {
	require_once $file;
}
$ebay_templates_dir = WP_CONTENT_DIR . '/uploads/ced-ebay/templates/';
if ( ! file_exists( $ebay_templates_dir ) ) {
	wp_mkdir_p( $ebay_templates_dir );
}
$do_template_edit = false;
$user_id          = isset( $_GET['user_id'] ) ? sanitize_text_field( wp_unslash( $_GET['user_id'] ) ) : false;
$site_id          = isset( $_GET['sid'] ) ? sanitize_text_field( wp_unslash( $_GET['sid'] ) ) : false;

$ced_ebay_plugin_url = get_site_url( null, '/wp-content/plugins/ebay-integration-for-woocommerce/', 'https' );
$allowed_html        = array(
	'a'          => array(
		'class' => array(),
		'href'  => array(),
		'rel'   => array(),
		'title' => array(),
	),
	'font'       => array(
		'rxr'   => array(),
		'size'  => array(),
		'style' => array(),
	),
	'section'    => array(
		'class' => array(),
		'id'    => array(),
		'style' => array(),
	),
	'link'       => array(
		'rel'  => array(),
		'href' => array(),
	),
	'body'       => array(
		'class' => array(),
		'id'    => array(),
	),
	'abbr'       => array(
		'title' => array(),
	),
	'b'          => array(),
	'blockquote' => array(
		'cite' => array(),
	),
	'cite'       => array(
		'title' => array(),
	),
	'code'       => array(),
	'del'        => array(
		'datetime' => array(),
		'title'    => array(),
	),
	'dd'         => array(),
	'div'        => array(
		'class'  => array(),
		'id'     => array(),
		'title'  => array(),
		'style'  => array(),
		'vocab'  => array(),
		'typeof' => array(),
	),
	'dl'         => array(),
	'dt'         => array(),
	'em'         => array(),
	'h1'         => array(
		'class' => array(),
		'id'    => array(),
		'style' => array(),
	),
	'h2'         => array(
		'class' => array(),
		'id'    => array(),
		'style' => array(),
	),
	'h3'         => array(
		'class' => array(),
		'id'    => array(),
		'style' => array(),
	),
	'h4'         => array(
		'class' => array(),
		'id'    => array(),
		'style' => array(),
	),
	'h5'         => array(
		'class' => array(),
		'id'    => array(),
		'style' => array(),
	),
	'h6'         => array(
		'class' => array(),
		'id'    => array(),
		'style' => array(),
	),
	'i'          => array(),
	'img'        => array(
		'alt'    => array(),
		'class'  => array(),
		'height' => array(),
		'src'    => array(),
		'width'  => array(),
		'style'  => array(),
	),
	'li'         => array(
		'class' => array(),
		'style' => array(),
	),
	'ol'         => array(
		'class' => array(),
	),
	'p'          => array(
		'class' => array(),
		'style' => array(),
		'align' => array(),
	),
	'q'          => array(
		'cite'  => array(),
		'title' => array(),
	),
	'span'       => array(
		'class'    => array(),
		'title'    => array(),
		'style'    => array(),
		'property' => array(),
	),
	'br'         => array(),
	'strike'     => array(),
	'strong'     => array(),
	'ul'         => array(
		'class' => array(),
	),
	'html'       => array(
		'lang'  => array(),
		'dir'   => array(),
		'class' => array(),
	),
	'head'       => array(
		'title' => array(),
		'link'  => array(
			'rel'  => array(),
			'type' => array(),
			'href' => array(),
		),
		'meta'  => array(
			'charset'    => array(),
			'name'       => array(),
			'content'    => array(),
			'http-equiv' => array(),
		),
	),
	'input'      => array(
		'class'       => array(),
		'id'          => array(),
		'name'        => array(),
		'type'        => array(),
		'value'       => array(),
		'placeholder' => array(),
		'checked'     => array(),
		'style'       => array(),
	),
	'label'      => array(
		'for'   => array(),
		'class' => array(),
		'style' => array(),
	),
	'title'      => array(),
	'figure'     => array(
		'class' => array(),
		'id'    => array(),
		'style' => array(),
	),
	'header'     => array(
		'class' => array(),
		'id'    => array(),
		'style' => array(),
	),
	'footer'     => array(
		'class' => array(),
		'id'    => array(),
		'style' => array(),
	),
	'nav'        => array(
		'class' => array(),
		'id'    => array(),
		'style' => array(),
	),
	'http-equiv' => array(),
);



if ( 'ced_ebay_edit_template' === sanitize_text_field( wp_unslash( isset( $_REQUEST['action'] ) ) ? wp_unslash( $_REQUEST['action'] ) : false ) ) {
	$add_new_template = false;
	$do_template_edit = true;
	$upload_dir       = wp_upload_dir();
	$foldername       = isset( $_REQUEST['template'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['template'] ) ) : false;
	$folderpath       = realpath( $upload_dir['basedir'] . '/ced-ebay/templates/' . $foldername );
	if ( false !== $folderpath && strpos( $folderpath, $upload_dir['basedir'] . '/ced-ebay/templates/' ) === 0 ) {
		$template_custom_html = @file_get_contents( $folderpath . '/template.html' );
		$template_custom_css  = @file_get_contents( $folderpath . '/style.css' );
	}
	if ( file_exists( $folderpath . '/info.txt' ) ) {
		$template_header = array(
			'Template' => 'Template',
		);
		$template_data   = get_file_data( $folderpath . '/info.txt', $template_header, 'theme' );
		$template_name   = $template_data['Template'];
	}
} elseif ( 'ced_ebay_edit_and_update_template' == sanitize_text_field( wp_unslash( isset( $_REQUEST['action'] ) ) ? wp_unslash( $_REQUEST['action'] ) : false ) ) {
	if ( ! isset( $_REQUEST['ced_ebay_edit_and_update_template_nonce_field'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_REQUEST['ced_ebay_edit_and_update_template_nonce_field'] ) ), 'ced_ebay_edit_and_update_template_action' ) ) {
		return;
	}
	$upload_dir          = wp_upload_dir();
	$old_foldername      = isset( $_REQUEST['template'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['template'] ) ) : false;
	$new_foldername      = isset( $_REQUEST['ced_ebay_template_name'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['ced_ebay_template_name'] ) ) : false;
	$temp_new_foldername = '';
	if ( ! empty( $new_foldername ) ) {
		$temp_new_foldername = trim( strtolower( sanitize_file_name( $new_foldername ) ), '.' );
	}
	if ( $old_foldername != $temp_new_foldername ) {
		$old_folderpath = $upload_dir['basedir'] . '/ced-ebay/templates/' . $old_foldername;
		$new_folderpath = $upload_dir['basedir'] . '/ced-ebay/templates/' . $new_foldername;
		rename( $old_folderpath, $new_folderpath );
		$folderpath     = $new_folderpath;
		$template_info  = "/* \n";
		$template_info .= "Template: $new_foldername\n";
		$template_info .= "*/\n";
		file_put_contents( $folderpath . '/info.txt', $template_info );

	} else {
		$foldername = $old_foldername;
		$folderpath = $upload_dir['basedir'] . '/ced-ebay/templates/' . $foldername;
	}
	if ( file_exists( $folderpath . '/info.txt' ) ) {
		$template_header = array(
			'Template' => 'Template',
		);
		$template_data   = get_file_data( $folderpath . '/info.txt', $template_header, 'theme' );
		$template_name   = $template_data['Template'];
		$template_info   = "/* \n";
		$template_info  .= "Template: $template_name\n";
		$template_info  .= "*/\n";
		$custom_html     = ! empty( $_REQUEST['ced_ebay_custom_html'] ) ? stripslashes_deep( wp_kses( $_REQUEST['ced_ebay_custom_html'], $allowed_html ) ) : false;
		$custom_css      = isset( $_REQUEST['ced_ebay_template_custom_css'] ) ? stripslashes( wp_kses( $_REQUEST['ced_ebay_template_custom_css'], array() ) ) : false; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		file_put_contents( $folderpath . '/template.html', $custom_html );
		file_put_contents( $folderpath . '/style.css', $custom_css );
		file_put_contents( $folderpath . '/info.txt', $template_info );
		$template_custom_html = @file_get_contents( $folderpath . '/template.html' );
		wp_redirect( get_admin_url() . 'admin.php?page=sales_channel&channel=ebay&section=view-description-templates&user_id=' . $user_id . '&sid=' . $site_id . '&rsid=' . $rsid );
		exit();
	}
} elseif ( 'ced_ebay_add_new_template' == sanitize_text_field( wp_unslash( isset( $_REQUEST['action'] ) ) ? wp_unslash( $_REQUEST['action'] ) : false ) ) {
	if ( ! empty( $_REQUEST['ced_ebay_add_new_template_nonce_field'] ) ) {
		if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_REQUEST['ced_ebay_add_new_template_nonce_field'] ) ), 'ced_ebay_add_new_template_action' ) ) {
			return;
		}
	}


	$add_new_template = true;
	$template_name    = isset( $_REQUEST['ced_ebay_template_name'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['ced_ebay_template_name'] ) ) : false;

	if ( ! empty( $template_name ) ) {
		$upload_dir     = wp_upload_dir();
		$templates_dir  = $upload_dir['basedir'] . '/ced-ebay/templates/';
		$template_info  = "/* \n";
		$template_info .= "Template: $template_name\n";
		$template_info .= "*/\n";
		$custom_html    = ! empty( $_REQUEST['ced_ebay_custom_html'] ) ? stripslashes_deep( wp_kses( $_REQUEST['ced_ebay_custom_html'], $allowed_html ) ) : false;
		$custom_css     = isset( $_REQUEST['ced_ebay_template_custom_css'] ) ? stripslashes( wp_kses( $_REQUEST['ced_ebay_template_custom_css'], array() ) ) : false; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$dirname        = trim( strtolower( sanitize_file_name( $template_name ) ), '.' );
		// TODO: if $dirname is empty, don't create a template.
		$ced_template_dir = $templates_dir . $dirname;
		// if ( is_dir( $ced_template_dir ) ) {
		// TODO: if folder exists, show error message
		// }
		if ( ! is_dir( $ced_template_dir ) ) {
			$result = mkdir( $ced_template_dir );
		}

			file_put_contents( $ced_template_dir . '/template.html', $custom_html );
			file_put_contents( $ced_template_dir . '/style.css', $custom_css );
			file_put_contents( $ced_template_dir . '/info.txt', $template_info );
			wp_redirect( get_admin_url() . 'admin.php?page=sales_channel&channel=ebay&section=settings&user_id=' . $user_id . '&sid=' . $site_id . '&rsid=' . $rsid );
			exit();

	}
} else {
	$template_custom_html = '';
}




?>

<style>
	#TemplateSettingsBox label {
		display: block;
	float: left;
	width: 33%;
	margin: 1px;
	padding: 3px
	}
</style>
<form method="post">
<?php wp_nonce_field( 'ced_ebay_template', 'ced_ebay_template_nonce' ); ?>
<div class="wrap">

	<div id="icon-options-general" class="icon32"></div>


	<div id="poststuff">

		<div id="post-body" class="metabox-holder columns-2">

			<!-- main content -->
			<div id="post-body-content">

				<div class="meta-box-sortables ui-sortable">

				<div class="postbox" id="TemplateSettingsBox">
						<h3 class="hndle"><span></span></h3>
						<div class="inside">
							<div id="titlediv" style="margin-bottom:5px;">
								<div id="titlewrap">
									<label for="ced_ebay_template_name" class="text_label">Name:</label>
									<input type="text" name="ced_ebay_template_name" size="24" value="<?php echo ! empty( $template_name ) ? esc_attr( $template_name ) : ''; ?>" id="ced_ebay_template_name" autocomplete="off" style="width:65%;">
								</div>
							</div>

						</div>
					</div>
					<div class="postbox" id="TemplateCustomHtml">
						<h3 class="hndle">Add Custom HTML<span></span></h3>
						<div class="inside">
							<div id="titlediv" style="margin-bottom:5px;">
								<div id="titlewrap">

								<?php

								if ( $add_new_template ) {
									$stylesheet_url = $ced_ebay_plugin_url . '/admin/css/default_template.css';
								} else {
									$stylesheet_url = get_site_url( null, '/wp-content/uploads/ced-ebay/templates/' . $foldername, 'https' ) . '/style.css';
								}

								add_filter(
									'mce_css',
									function () use ( $stylesheet_url ) {
										return $stylesheet_url;
									}
								);
								$settings             = array(
									'wpautop'       => false,
									'media_buttons' => true,
									'teeny'         => false,
									'textarea_name' => 'ced_ebay_custom_html',
									'tinymce'       => false,
								);
								$template_custom_html = isset( $template_custom_html ) ? $template_custom_html : '';
								$template_custom_css  = isset( $template_custom_css ) ? $template_custom_css : '';
								wp_editor( $template_custom_html, 'custom_html', $settings );
								?>

								</div>
							</div>

						</div>
					</div>
					<h1><?php esc_attr_e( 'Enter Custom CSS', 'ebay-integration-for-woocommerce' ); ?></h1>
					<textarea name="ced_ebay_template_custom_css"><?php echo esc_html( $template_custom_css ); ?></textarea>
						<div id="styles_editor"></div>

					<!-- .postbox -->

				</div>
				<!-- .meta-box-sortables .ui-sortable -->

			</div>
			<!-- post-body-content -->

			<!-- sidebar -->
			<div id="postbox-container-1" class="postbox-container">

				<div class="meta-box-sortables">

				<div class="postbox" id="submitdiv">
						<h3 class="hndle"><span>Update</span></h3>
						<div class="inside">
							<div id="submitpost" class="submitbox">
								<div id="misc-publishing-actions">
										<div class="misc-pub-section">
											<p>To update already uploaded items you need to update them after saving the template.</p>
										</div>
								</div>
								<div id="major-publishing-actions">
									<?php wp_nonce_field( 'ced_ebay_edit_and_update_template_action', 'ced_ebay_edit_and_update_template_nonce_field' ); ?>

									<div id="publishing-action">
									<input type="hidden" name="action" value=<?php echo esc_attr( sanitize_text_field( $_REQUEST['action'] ) ); ?> />

									<?php
									if ( $do_template_edit ) {
										?>
											<input type="hidden" name="action" value="ced_ebay_edit_and_update_template" />
										<?php
									}
									?>

									<input type="submit" value="Save template" id="publish" class="button-primary" name="save"> 
									<a class="components-button is-secondary" href="<?php echo esc_attr( wp_nonce_url( admin_url( 'admin.php?page=sales_channel&channel=ebay&section=view-description-templates&user_id=' . $user_id . '&sid=' . $site_id . '&rsid=' . $rsid ), 'ced_ebay_add_new_template_action', 'ced_ebay_add_new_template_nonce' ) ); ?>">
			Cancel
		</a>
									</div>
									<div class="clear"></div>
								</div>

							</div>


						</div>
					</div>
					<!-- .postbox -->
					<div class="postbox"  id="TemplateFieldsBox">
						<h3 class="hndle"><span>Template Shortcodes</span></h3>
						<div class="inside">

					<div class="postbox" id="HelpBox">
						<div class="inside">
							<p>
							<p style="font-weight:bold;white-space: pre-wrap;margin:0px;padding:0px;">[woo_ebay_product_title]</p>
								WooCommerce Product Title<br><br>
							</p>
							<p>
								<p style="font-weight:bold;white-space: pre-wrap;margin:0px;padding:0px;">[woo_ebay_product_description]</p>
								WooCommerce Main Description<br><br>
							</p>
							<p>
								<p style="font-weight:bold;white-space: pre-wrap;margin:0px;padding:0px;">[woo_ebay_product_price]</p>
								WooCommerce Product Price<br><br>
							</p>
							<p>
								<p style="font-weight:bold;white-space: pre-wrap;margin:0px;padding:0px;">[woo_ebay_product_sku]</p>
								WooCommerce Product SKU<br><br>
							</p>
							<p>
								<p style="font-weight:bold;white-space: pre-wrap;margin:0px;padding:0px;">[woo_ebay_product_main_image]</p>
								Main Product Image as HTML tag<br><br>
							</p>
							<p>
								<p style="font-weight:bold;white-space: pre-wrap;margin:0px;padding:0px;">[woo_ebay_product_category]</p>
								WooCommerce Product Category Name<br><br>
							</p>
							<p>
								<p style="font-weight:bold;white-space: pre-wrap;margin:0px;padding:0px;">[woo_ebay_product_type]</p>
								WooCommerce Product Type<br><br>
							</p>

							<p>
								<p style="font-weight:bold;white-space: pre-wrap;margin:0px;padding:0px;">[woo_ebay_product_short_description]</p>
								WooCommerce Product Short Description<br><br>
							</p>
							<p>
								<p style="font-weight:bold;white-space: pre-wrap;margin:0px;padding:0px;">[woo_ebay_product_description]</p>
								WooCommerce Product Main Description<br><br>
							</p>

						</div>
					</div>


				</div>

				</div>
				<!-- .meta-box-sortables -->


			</div>
			<!-- #postbox-container-1 .postbox-container -->

		</div>
		<!-- #post-body .metabox-holder .columns-2 -->

		<br class="clear">
	</div>
	<!-- #poststuff -->

</div> <!-- .wrap -->
</form>
<style>

	/* hide warnings in css editor */
	#styles_editor .ace_gutter-cell.ace_warning {
		background-image: none;
	}
	#styles_editor {
		height: 240px;
		width: 100%;
		position: relative;
		border: 1px solid #ccc;
	}
	#styles_editor {
		height: 420px;
	}
	.ace_editor, .ace_editor *{
font-family: "Monaco", "Menlo", "Ubuntu Mono", "Droid Sans Mono", "Consolas", monospace !important;
font-size: 12px !important;
font-weight: 400 !important;
letter-spacing: 0 !important;
}
</style>

<script type="text/javascript">
jQuery(document).ready(function(){
	var styles_textarea = jQuery('textarea[name="ced_ebay_template_custom_css"]').hide();
	var styles_editor = ace.edit("styles_editor");
	styles_editor.setTheme("ace/theme/chrome");
	styles_editor.setShowPrintMargin( false );
	var CssMode = require("ace/mode/css").Mode;
	styles_editor.getSession().setMode(new CssMode());
	styles_editor.getSession().setValue(styles_textarea.val());
	styles_editor.getSession().on('change', function(){
	styles_textarea.val(styles_editor.getSession().getValue());
	console.log(styles_textarea.val());
	});
})
								</script>
