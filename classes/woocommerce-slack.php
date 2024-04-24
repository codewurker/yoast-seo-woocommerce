<?php
/**
 * WooCommerce Yoast SEO plugin file.
 *
 * @package WPSEO/WooCommerce
 */

use Yoast\WP\SEO\Presentations\Indexable_Presentation;

/**
 * Class WPSEO_WooCommerce_Slack
 */
class WPSEO_WooCommerce_Slack {

	/**
	 * Registers the hooks.
	 *
	 * @return void
	 */
	public function register_hooks() {
		add_filter( 'wpseo_enhanced_slack_data', [ $this, 'filter_enhanced_data' ], 10, 2 );
	}

	/**
	 * Replaces the default enhanced data (author, reading time) with custom data.
	 *
	 * @param array                  $data         The array of labels => value.
	 * @param Indexable_Presentation $presentation The indexable presentation.
	 *
	 * @return array The filtered array.
	 */
	public function filter_enhanced_data( $data, $presentation ) {
		$object  = $presentation->model;
		$product = wc_get_product( $object->object_id );

		if ( $product ) {
			$data         = [];
			$product_type = WPSEO_WooCommerce_Utils::get_product_type( $product );
			// Omit the price amount for variable and grouped products.
			$show_price = apply_filters( 'Yoast\WP\Woocommerce\og_price', true ) && ! ( $product_type === 'variable' || $product_type === 'grouped' );

			$availability = __( 'Out of stock', 'yoast-woo-seo' );

			// Should be checked before backorder because it's true for both cases.
			if ( $product->is_in_stock() ) {
				$availability = __( 'In stock', 'yoast-woo-seo' );
			}

			if ( $product->is_on_backorder() ) {
				$availability = __( 'On backorder', 'yoast-woo-seo' );
			}

			if ( $show_price ) {
				// We're recreating the WC_Product::get_price_html() but with the logic of the products on sale removed.
				if ( $product->get_price() === '' ) {
					// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals -- Using WooCommerce hook.
					$price = apply_filters( 'woocommerce_empty_price_html', '', $product );
				}
				else {
					$price = wc_price( wc_get_price_to_display( $product ) ) . $product->get_price_suffix();
				}

				$data[ __( 'Price', 'yoast-woo-seo' ) ] = wp_strip_all_tags( $price );
			}
			$data[ __( 'Availability', 'yoast-woo-seo' ) ] = $availability;
		}

		return $data;
	}
}
