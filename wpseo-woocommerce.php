<?php
/**
 * WooCommerce Yoast SEO plugin.
 *
 * @package WPSEO/WooCommerce
 *
 * @wordpress-plugin
 * Plugin Name: Yoast SEO: WooCommerce
 * Version:     16.2
 * Plugin URI:  https://yoa.st/4fu
 * Description: This extension to WooCommerce and Yoast SEO makes sure there's perfect communication between the two plugins.
 * Author:      Team Yoast
 * Author URI:  https://yoa.st/team-yoast-woocommerce
 * Depends:     Yoast SEO, WooCommerce
 * Text Domain: yoast-woo-seo
 * Domain Path: /languages/
 * Requires at least: 6.3
 * Requires PHP: 7.2.5
 *
 * WC requires at least: 7.1
 * WC tested up to: 8.7
 *
 * Copyright 2014-2024 Yoast BV (email: support@yoast.com)
 */

if ( ! function_exists( 'add_filter' ) ) {
	header( 'Status: 403 Forbidden' );
	header( 'HTTP/1.1 403 Forbidden' );
	exit();
}

if ( file_exists( __DIR__ . '/vendor/autoload.php' ) ) {
	require __DIR__ . '/vendor/autoload.php';
}

define( 'WPSEO_WOO_PLUGIN_FILE', __FILE__ );
define( 'WPSEO_WOO_VERSION', '16.2' );

/**
 * Initializes the plugin class, to make sure all the required functionality is loaded, do this after plugins_loaded.
 *
 * @since 1.0
 *
 * @return void
 */
function initialize_yoast_woocommerce_seo() {

	load_plugin_textdomain( 'yoast-woo-seo', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );

	global $wp_version;

	$dependency_check = new Yoast_WooCommerce_Dependencies();
	if ( $dependency_check->check_dependencies( $wp_version ) ) {
		global $yoast_woo_seo;

		// Initializes the plugin.
		$yoast_woo_seo = new Yoast_WooCommerce_SEO();
	}
}

if ( ! wp_installing() ) {
	add_action( 'plugins_loaded', 'initialize_yoast_woocommerce_seo', 20 );
}

// Activation hook.
if ( is_admin() ) {
	register_activation_hook( __FILE__, [ 'Yoast_WooCommerce_SEO', 'install' ] );
}
