<?php
/**
 * Plugin Name: Integration of Yoast wordpress SEO module with mqtranslate module
 * Plugin URI: http://wordpress.org
 * Description: This module has the aim to make compatible the wordpress SEO by Yoast and mqtranslate module.
 * Version: 0.1.1
 * Author: Koldo Gonzalez (rufein)
 * Author URI: http:/funkydrop.net
 * License: GPL2
 */

define('IYWSM', 'wp-seo-yoast-integration-mqtranslate');
define('IYWSM_PATH', dirname(__FILE__)); // Path of the module
define('PLUGINS_PATH', dirname(IYWSM_PATH)); // PLugins directory


/******************************
 * 
 *    ---  HOOKS  ---
 * 
 *****************************/

if ( ! defined( 'WP_INSTALLING' ) || WP_INSTALLING === false ) {
		
	// If no Wp seo module in plugins directory, the include statement will send a warning
	
	// Include plugins if the are deactivated
	include_once PLUGINS_PATH . '/wordpress-seo/wp-seo.php';
	include_once PLUGINS_PATH . '/mqtranslate/mqtranslate.php';
	// Manual load if SEO module is deactivate to avoid an exception for no finding parent class
	include_once PLUGINS_PATH . '/wordpress-seo/inc/class-wpseo-meta.php';
	include_once PLUGINS_PATH . '/wordpress-seo/admin/class-metabox.php';
	include_once PLUGINS_PATH . '/wordpress-seo/frontend/class-frontend.php';	
	
	// Load all classes to overwrite
	include_once IYWSM_PATH . '/admin/class-metabox-wpSEOyoast-integr.php';
	include_once IYWSM_PATH . '/inc/class-sitemaps-mqtranslate-integr.php';
	include_once IYWSM_PATH . '/frontend/class-frontend-mqtranslate-integr.php';
	
	// Hooks
	add_action( 'plugins_loaded', 'wp_seo_yoast_integr_init', 16 );	
	add_action( 'init', 'wp_seo_yoast_integr_frontend_init', 16 );

	// Load admin classes
	if ( is_admin() ) {
		// Overwrite the class that controls the admin interface.
		// Priority: 16 -> IN the original SEO module by Yoast the piority is set to 15.
		add_action( 'plugins_loaded', 'wp_seo_yoast_integr_admin_init', 16 );
	}	
	
}



/**
 * hook => plugins_loaded
 * 
 * Overwrite the sitemaps class
 */
function wp_seo_yoast_integr_init(){
	
	$options = WPSEO_Options::get_all();
  
	if ( $options['enablexmlsitemap'] === true && isset($GLOBALS['wpseo_sitemaps']) ) {
		$GLOBALS['old_wpseo_sitemaps'] = $GLOBALS['wpseo_sitemaps'];
		$GLOBALS['wpseo_sitemaps'] = new WPSEO_Sitemaps_Mqtranslate_Integr();
	}
	
}


/**
 *  hook => plugins_loaded
 *  
 *  Overwrite the class that controls the meta admin class.
 *  
 */
function wp_seo_yoast_integr_admin_init(){
	
	global $pagenow;
	
	if ( in_array( $pagenow, array( 'edit.php', 'post.php', 'post-new.php' ) ) || apply_filters( 'wpseo_always_register_metaboxes_on_admin', false ) ) {
		
		$GLOBALS['old_wpseo_metabox'] = $GLOBALS['wpseo_metabox'];
		$GLOBALS['wpseo_metabox'] = new WPSEO_Metabox_wpSEOyoast_integr();
	}

}

/**
 *  hook => plugins_loaded
 *
 *  Overwrite the class that controls the frontend class.
 *
 */
function wp_seo_yoast_integr_frontend_init(){
	if (isset($GLOBALS['wpseo_front'])){
		$GLOBALS['old_wpseo_front'] = $GLOBALS['wpseo_front'];
		$GLOBALS['wpseo_front'] = new WPSEO_Frontend_mqtranslate_intgr();
	}
}

/**
 *  hook => plugins_loaded
 *
 *  Check if the wp SEO by Yoast and (m)qtranslated are active.
 *
 */
function wp_seo_yoast_integ_check_dependencies(){
	
	return true;
	return ( is_plugin_active( PLUGINS_PATH . '\wordpress-seo\wp-seo.php') 
  	       && ( is_plugin_active( PLUGINS_PATH . '\mqtranslate\mqtranslate.php')  
  	  	        || is_plugin_active( PLUGINS_PATH . '\qtranslateºqtranslate.php')
	          ) 
	     );
	
}

