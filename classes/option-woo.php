<?php
/**
 * WooCommerce Yoast SEO plugin file.
 *
 * @package    Internals
 * @since      1.1.0
 * @version    1.1.0
 */

// Avoid direct calls to this file.
if ( ! class_exists( 'Yoast_WooCommerce_SEO' ) ) {
	header( 'Status: 403 Forbidden' );
	header( 'HTTP/1.1 403 Forbidden' );
	exit();
}


/**
 * ****************************************************************
 * Option: wpseo_woo
 */
if ( ! class_exists( 'WPSEO_Option_Woo' ) && class_exists( 'WPSEO_Option' ) ) {

	/**
	 * Class WPSEO_Option_Woo
	 */
	class WPSEO_Option_Woo extends WPSEO_Option {

		/**
		 * Option name.
		 *
		 * @var string
		 */
		public $option_name = 'wpseo_woo';

		/**
		 * Option group name for use in settings forms.
		 *
		 * @var string
		 */
		public $group_name = 'wpseo_woo_options';

		/**
		 * Database version to check whether the plugins options need updating.
		 *
		 * @var int
		 */
		public $db_version = 4;

		/**
		 * Array of defaults for the option.
		 *
		 * Shouldn't be requested directly, use $this->get_defaults().
		 *
		 * @var array<string, int|string|bool>
		 */
		protected $defaults = [
			// Non-form fields, set via validation routine.
			'woo_dbversion'           => 0, // Leave default as 0 to ensure activation/upgrade works.

			// Form fields.
			'woo_schema_brand'        => '',
			'woo_schema_manufacturer' => '',
			'woo_schema_color'        => '',
			'woo_schema_pattern'      => '',
			'woo_schema_material'     => '',
			'woo_breadcrumbs'         => true,
			'woo_metabox_top'         => true,
		];

		/**
		 * Get the singleton instance of this class
		 *
		 * @return self
		 */
		public static function get_instance() {
			if ( ! ( self::$instance instanceof self ) ) {
				self::$instance = new self();
			}

			return self::$instance;
		}

		/**
		 * Registers the option to the WPSEO Options framework.
		 *
		 * @return void
		 */
		public static function register_option() {
			WPSEO_Options::register_option( self::get_instance() );
		}

		/**
		 * WPSEO_Option_Woo constructor.
		 */
		protected function __construct() {
			parent::__construct();

			$this->upgrade();
		}

		/**
		 * Validates the option.
		 *
		 * @todo remove code using $short, there is no "short form" anymore.
		 *
		 * @param array $dirty New value for the option.
		 * @param array $clean Clean value for the option, normally the defaults.
		 * @param array $old   Old value of the option.
		 *
		 * @return array Validated clean value for the option to be saved to the database.
		 */
		protected function validate_option( $dirty, $clean, $old ) {

			// Have we receive input from a short (license only) form.
			$short = ( isset( $dirty['short_form'] ) && $dirty['short_form'] === 'on' );

			// Prepare an array of valid data types and taxonomies to validate against.
			$valid_taxonomies = $this->get_taxonomies();

			foreach ( $clean as $key => $value ) {
				switch ( $key ) {
					case 'woo_dbversion':
						$clean[ $key ] = $this->db_version;
						break;

					case 'woo_schema_brand':
					case 'woo_schema_manufacturer':
					case 'woo_schema_color':
					case 'woo_schema_pattern':
					case 'woo_schema_material':
						if ( isset( $dirty[ $key ] ) ) {
							if ( in_array( $dirty[ $key ], $valid_taxonomies, true ) ) {
								$clean[ $key ] = $dirty[ $key ];
							}
							elseif ( sanitize_title_with_dashes( $dirty[ $key ] ) === $dirty[ $key ] ) {
								// Allow taxonomies which may not be registered yet.
								$clean[ $key ] = $dirty[ $key ];
							}
						}
						elseif ( $short && isset( $old[ $key ] ) ) {
							if ( in_array( $old[ $key ], $valid_taxonomies, true ) ) {
								$clean[ $key ] = $old[ $key ];
							}
							elseif ( sanitize_title_with_dashes( $old[ $key ] ) === $old[ $key ] ) {
								// Allow taxonomies which may not be registered yet.
								$clean[ $key ] = $old[ $key ];
							}
						}
						break;

					/* boolean (checkbox) field - may not be in form */
					case 'woo_breadcrumbs':
					case 'woo_metabox_top':
						if ( isset( $dirty[ $key ] ) ) {
							$clean[ $key ] = WPSEO_Utils::validate_bool( $dirty[ $key ] );
						}
						else {
							$clean[ $key ] = false;
							if ( $short && isset( $old[ $key ] ) ) {
								$clean[ $key ] = WPSEO_Utils::validate_bool( $old[ $key ] );
							}
						}
						break;
				}
			}

			return $clean;
		}

		/**
		 * Returns a list of lower cased taxonomies.
		 *
		 * @return array The found taxonomies.
		 */
		protected function get_taxonomies() {
			$taxonomies = get_object_taxonomies( 'product', 'objects' );

			if ( ! is_array( $taxonomies ) || empty( $taxonomies ) ) {
				return [];
			}

			$processed_taxonomies = [];
			foreach ( $taxonomies as $taxonomy ) {
				$processed_taxonomies[] = strtolower( $taxonomy->name );
			}

			unset( $taxonomies );

			return $processed_taxonomies;
		}

		/**
		 * Performs the upgrade of the option.
		 *
		 * @return void
		 */
		private function upgrade() {
			$option = get_option( $this->option_name );

			if ( ! empty( $option['dbversion'] ) ) {
				$option['woo_dbversion'] = $option['dbversion'];
			}

			// Check if the options need updating.
			if ( $this->db_version <= $option['woo_dbversion'] ) {
				return;
			}

			// Convert to the new prefixed option names.
			if ( $this->db_version === 3 ) {
				$fields_to_convert = [
					'schema_brand'        => 'woo_schema_brand',
					'schema_manufacturer' => 'woo_schema_manufacturer',
					'breadcrumbs'         => 'woo_breadcrumbs',
					'hide_columns'        => 'woo_hide_columns',
					'metabox_woo_top'     => 'woo_metabox_top',
				];

				foreach ( $fields_to_convert as $current_field => $new_field ) {
					if ( ! isset( $option[ $current_field ] ) ) {
						continue;
					}

					$option[ $new_field ] = $option[ $current_field ];
				}

				update_option( $this->option_name, $option );
			}

			$this->clean();
		}
	}
}
