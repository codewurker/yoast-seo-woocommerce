<?php
/**
 * WooCommerce Yoast SEO plugin file.
 *
 * @package WPSEO/WooCommerce
 */

/**
 * The Yoast_Woocommerce_Import_Export class.
 * This class adds the GTIN8, GTIN12, GTIN13, GTIN14, ISBN, and MPN columns to the WooCommerce Product Import/Export screens.
 */
class Yoast_Woocommerce_Import_Export {

	/**
	 * Initializes the integration.
	 *
	 * This is the place to register hooks and filters.
	 *
	 * @return void
	 */
	public function register_hooks() {
		add_filter( 'woocommerce_product_export_column_names', [ $this, 'add_columns' ] );
		add_filter( 'woocommerce_product_export_product_default_columns', [ $this, 'add_columns' ] );

		add_filter( 'woocommerce_csv_product_import_mapping_options', [ $this, 'add_columns' ] );
		add_filter( 'woocommerce_csv_product_import_mapping_default_columns', [ $this, 'add_column_to_mapping_screen' ] );

		add_filter( 'woocommerce_product_import_pre_insert_product_object', [ $this, 'process_import' ], 10, 2 );

		add_filter( 'woocommerce_product_export_product_column_gtin8', [ $this, 'add_export_data_global_identifier_values' ], 10, 2 );
		add_filter( 'woocommerce_product_export_product_column_gtin12', [ $this, 'add_export_data_global_identifier_values' ], 10, 2 );
		add_filter( 'woocommerce_product_export_product_column_gtin13', [ $this, 'add_export_data_global_identifier_values' ], 10, 2 );
		add_filter( 'woocommerce_product_export_product_column_gtin14', [ $this, 'add_export_data_global_identifier_values' ], 10, 2 );
		add_filter( 'woocommerce_product_export_product_column_isbn', [ $this, 'add_export_data_global_identifier_values' ], 10, 2 );
		add_filter( 'woocommerce_product_export_product_column_mpn', [ $this, 'add_export_data_global_identifier_values' ], 10, 2 );
	}

	/**
	 * Add automatic mapping support for wpseo_global_identifier_values options.
	 * This will automatically select the correct mapping for columns of wpseo_global_identifier_values options.
	 *
	 * @param array $columns The column names.
	 *
	 * @return array The updated column names with the custom potential names.
	 */
	public function add_column_to_mapping_screen( $columns ) {
		$columns['GTIN8'] = 'gtin8';
		$columns['gtin8'] = 'gtin8';

		$columns['GTIN12 / UPC'] = 'gtin12';
		$columns['GTIN12/UPC']   = 'gtin12';
		$columns['gtin12 / upc'] = 'gtin12';
		$columns['gtin12/upc']   = 'gtin12';
		$columns['GTIN12']       = 'gtin12';
		$columns['gtin12']       = 'gtin12';
		$columns['UPC']          = 'gtin12';
		$columns['upc']          = 'gtin12';

		$columns['GTIN13 / EAN'] = 'gtin13';
		$columns['GTIN13/EAN']   = 'gtin13';
		$columns['gtin13 / ean'] = 'gtin13';
		$columns['gtin13/ean']   = 'gtin13';
		$columns['GTIN13']       = 'gtin13';
		$columns['gtin13']       = 'gtin13';
		$columns['EAN']          = 'gtin13';
		$columns['ean']          = 'gtin13';

		$columns['GTIN14 / ITF-14'] = 'gtin14';
		$columns['GTIN14/ITF-14']   = 'gtin14';
		$columns['gtin14 / itf-14'] = 'gtin14';
		$columns['gtin14/itf-14']   = 'gtin14';
		$columns['GTIN14']          = 'gtin14';
		$columns['gtin14']          = 'gtin14';
		$columns['ITF-14']          = 'gtin14';
		$columns['itf-14']          = 'gtin14';

		$columns['ISBN'] = 'isbn';
		$columns['isbn'] = 'isbn';

		$columns['MPN'] = 'mpn';
		$columns['mpn'] = 'mpn';

		return $columns;
	}

	/**
	 * Process the data read from the CSV file.
	 * Adds the global identifiers values to the corespondent meta field.
	 *
	 * @param WC_Product $product Product being imported or updated.
	 * @param array      $data    CSV data read for the product.
	 *
	 * @return WC_Product
	 */
	public function process_import( $product, $data ) {
		$global_identifier_values = $this->get_global_identifier_values( $product );
		$values                   = array_intersect_key( $data, $global_identifier_values );
		$meta_name                = $this->get_global_identifier_meta_name( $product->get_type() );

		if ( $values ) {
			$values = array_map( 'sanitize_text_field', $values );
			$merged = array_merge( $global_identifier_values, $values );
			update_post_meta( $product->get_id(), $meta_name, $merged );
		}

		return $product;
	}

	/**
	 * Adds the global identifier columns.
	 *
	 * @param array $columns The column names.
	 *
	 * @return array The updated column names.
	 */
	public function add_columns( $columns ) {
		// Column slug => Column name.
		$columns['gtin8']  = 'GTIN8';
		$columns['gtin12'] = 'GTIN12 / UPC';
		$columns['gtin13'] = 'GTIN13 / EAN';
		$columns['gtin14'] = 'GTIN14 / ITF-14';
		$columns['isbn']   = 'ISBN';
		$columns['mpn']    = 'MPN';

		return $columns;
	}

	/**
	 * Provide the data to be exported for one item in a column of the wpseo global identifier values.
	 *
	 * @param mixed      $value   Default: ''.
	 * @param WC_Product $product The product object.
	 *
	 * @return mixed Should be in a format that can be output into a text file (string, numeric, etc).
	 */
	public function add_export_data_global_identifier_values( $value, $product ) {
		$current_hook = current_filter();
		if ( strpos( $current_hook, 'woocommerce_product_export_product_column_' ) === 0 ) {
			$global_identifier              = str_replace( 'woocommerce_product_export_product_column_', '', $current_hook );
			$wpseo_global_identifier_values = $this->get_global_identifier_values( $product );
			if ( array_key_exists( $global_identifier, $wpseo_global_identifier_values ) ) {
				return $wpseo_global_identifier_values[ $global_identifier ];
			}
		}

		return '';
	}

	/**
	 * This function gets the global identifier values from the product meta.
	 *
	 * @param WC_Product $product The product.
	 *
	 * @return array The global identifier values.
	 */
	private function get_global_identifier_values( $product ) {
		$meta_name = $this->get_global_identifier_meta_name( $product->get_type() );

		$global_identifier_values   = get_post_meta( $product->get_id(), $meta_name, true );
		$global_identifier_defaults = [
			'gtin8'  => '',
			'gtin12' => '',
			'gtin13' => '',
			'gtin14' => '',
			'isbn'   => '',
			'mpn'    => '',
		];

		if ( $global_identifier_values && is_array( $global_identifier_values ) ) {
			return array_merge( $global_identifier_defaults, $global_identifier_values );
		}

		return $global_identifier_defaults;
	}

	/**
	 * Gets the meta name to use, depending on whether it's a product or a variation.
	 *
	 * @param string $product_type The product type.
	 *
	 * @return string The meta name to use.
	 */
	private function get_global_identifier_meta_name( $product_type ) {
		if ( $product_type === 'variation' ) {
			return 'wpseo_variation_global_identifiers_values';
		}
		return 'wpseo_global_identifier_values';
	}
}
