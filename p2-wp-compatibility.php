<?php 
/*
Plugin Name: ProPhoto2 compatibility patches
Plugin URI: http://www.prophotoblogs.com/support/about/p2-compatibility-patch-plugin/
Description: Only for users of version 2 of the ProPhoto theme. Contains patches required for compatibility with WordPress 3.0+
Author: ProPhotoBlogs
Version: 1.3
*/


if ( get_option( 'template' ) == 'prophoto2' ) {
	add_action( 'wp_loaded', 'p2_update_img_protection_opts' );
	add_action( 'wp_loaded', 'p2_update_img_protection_js' );
	add_action( 'after_setup_theme', 'p2_fix_iframed_upload_windows' );
}


function p2_fix_iframed_upload_windows() {
	if ( p2_wp_version() > 229 ) {
		if ( $GLOBALS['pagenow'] == 'popup.php' ) {
			wp_enqueue_style( 'p2-compat-popup-css', plugin_dir_url( __FILE__ ) .'p2-compat-popup.css' );
			add_action( 'admin_head', create_function( '', "remove_action( 'post-upload-ui', 'media_upload_text_after', 5 );" ) );
		}
	}
}



function p2_update_img_protection_opts() {
	if ( !function_exists( 'p2_test' ) ) {
		return;
	}
	
	global $p2;
	$storeNew = false;


	if ( p2_test( 'no_right_click', ' ondragstart="return false" onselectstart="return false"') ) {
		$p2['options']['settings']['no_right_click'] = ' ondragstart="return false" onselectstart="return false" id="wp-pp"';
		$p2['options']['settings']['updated_no_right_click'] = 'true';
		$storeNew = true;
	}
	
	if ( p2_test( 'no_left_click', 'on' ) ) {
		$p2['options']['settings']['no_left_click'] = 'off';
		$p2['options']['settings']['updated_no_left_click'] = 'true';
		$storeNew = true;
	}
	
	if ( $storeNew ) {
		p2_store_options();
	}
}

function p2_update_img_protection_js() {
	if ( !function_exists( 'p2_test' ) ) {
		return;
	}
	
	$newJS = null;
	
	if ( p2_test( 'updated_no_right_click', 'true' ) ) {
		$newJS = "jQuery('.alignnone, .aligncenter, .alignright, .alignleft, .gallery img, a[href$=\".jpg\"], a[href$=\".gif\"], a[href$=\".png\"], a[href$=\".JPG\"], a[href$=\".GIF\"], a[href$=\".PNG\"]')";
	}
	
	if ( $newJS && p2_test( 'updated_no_left_click', 'true' ) ) {
		$newJS .= ".css('cursor', 'default').click(function(){return false;})";
	}
	
	if ( $newJS ) {
		$newJS .= ".bind('contextmenu', function(){return false;});";
	}
	
	if ( $newJS ) {
		$newJS = addslashes( 'jQuery(document).ready(function($){ ' . $newJS . ' });' );
		add_action( 'wp_head', create_function( '', "echo '<script> $newJS </script>';" ) );
	}
}


if ( !function_exists( 'p2_insert_upload_fields' ) ) {


	/* get custom P2 fields inside of upload form */
	function p2_insert_upload_fields() {

		// add additional fields
		echo '<input type="hidden" name="shortname" value="' . $_GET['p2_image'] . '" />';
		echo '<input type="hidden" name="formurl" value="' . $_SERVER['REQUEST_URI'] . '" />';

		// Are we in a frame ? A 'standalone' (not iframed) page has an URL with TB_iframe=true in the GET
		parse_str( $_SERVER['REQUEST_URI'] );
		$iframe = ( $TB_iframe ) ? 'false' : 'true' ;
		echo '<input type="hidden" name="iframed" value="' . $iframe . '" />';
	}


	/* die if in multi-user mode */
	function p2_mu_fail() {
		echo '<div class="error below-h2"><p>Sorry, the ProPhoto theme <strong>cannot be used in <span style="text-decoration:underline;">Multi-user</span> mode</strong>.</p></div>';
	}


	/* make presence known to P2 */
	function p2_patched_notify() {
		echo <<<HTML
		<script type="text/javascript" charset="utf-8">
		var p2_compat_patch = true;
		</script>
HTML;
	}


	/* leave something for the doctor */
	function p2_compat_doctor_mark() {
		echo "\n<!-- p2-compat-plugin-installed -->\n";
	}


	/* add actions */
	if ( get_option( 'template' ) == 'prophoto2' ) {		
		if ( !is_admin() ) add_action( 'wp_head', 'p2_compat_doctor_mark' );
		if ( $pagenow == 'popup.php' ) add_action( 'pre-upload-ui', 'p2_insert_upload_fields' );
		if ( $pagenow == 'themes.php' ) add_action( 'admin_head', 'p2_patched_notify' );
		if ( function_exists( 'is_multisite' ) && is_multisite() ) {
			if ( is_admin() ) add_action( 'admin_notices', 'p2_mu_fail' );
			else exit( p2_mu_fail() );
		}
	}
}



function p2_wp_version() {
	$version = str_pad( intval( str_replace( '.', '', $GLOBALS['wp_version'] ) ), 3, '0' );
	return ( $version == '000' ) ? 999 : intval( $version );
}



