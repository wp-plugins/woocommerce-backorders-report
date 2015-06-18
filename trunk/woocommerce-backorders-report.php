<?php
/*
Plugin Name: WooCommerce - Backorders Report
Plugin URI:  https://filament-studios.com/downloads/woocommerce-category-fees
Description: Show a of backordered items in the WooCommerce Dashboard
Version:     1.0
Author:      Filament Studios
Author URI:  https://filament-studios.com
License:     GPL-2.0+
Tested up to: 4.3
*/

if ( ! class_exists( 'WC_Backorders_Report' ) ) {

class WC_Backorders_Report {

	protected static $_instance = null;

	/**
	 * Start up the plugin
	 *
	 * @since  1.0
	 */
	private function __construct() {

		$this->setup_constants();
		$this->hooks();
		$this->filters();

	}

	/**
	 * Singleton instance
	 *
	 * @since  1.0
	 * @return class The one true instance
	 */
	public static function instance() {

		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}

		return self::$_instance;
	}

	/**
	 * Define some constants befor we get started
	 *
	 * @since  1.0
	 */
	private function setup_constants() {
		// Plugin version
		if ( ! defined( 'WCBR_VER' ) ) {
			define( 'WCBR_VER', '1.0' );
		}

		// Plugin path
		if ( ! defined( 'WCBR_DIR' ) ) {
			define( 'WCBR_DIR', plugin_dir_path( __FILE__ ) );
		}

		// Plugin URL
		if ( ! defined( 'WCBR_URL' ) ) {
			define( 'WCBR_URL', plugin_dir_url( __FILE__ ) );
		}

		// Plugin Root File
		if ( ! defined( 'WCBR_FILE' ) ) {
			define( 'WCBR_FILE', __FILE__ );
		}
	}

	/**
	 * Register our hooks
	 *
	 * @since  1.0
	 */
	private function hooks() {

	}

	/**
	 * Register into the licensing system
	 *
	 * @since  1.0
	 */
	private function filters() {
		add_filter( 'woocommerce_admin_reports', array( $this, 'register_backorder_report' ), 10, 1 );
	}

	public function register_backorder_report( $reports ) {

		$report_info = array(
			'title'       => __( 'On Backorder', 'wcbr' ),
			'description' => '',
			'hide_title'  => true,
			'callback'    => array( $this, 'get_backorder_report' ),
		);

		$reports['stock']['reports']['wcbr_backorders'] = $report_info;

		return $reports;
	}

	public function get_backorder_report() {
		include_once( 'class-wc-report-backorders.php' );

		if ( ! class_exists( 'WC_Report_Backorders' ) )
			return;

		$report = new WC_Report_Backorders;
		$report->output_report();
	}

}

} // End Class Exists


/**
 * Returns the main instance of WC to prevent the need to use globals.
 *
 * @since  2.1
 * @return WC_Backorder_Report
 */
function WC_Backorders_Report() {
	return WC_Backorders_Report::instance();
}

// Global for backwards compatibility.
$GLOBALS['wc_backorders_report'] = WC_Backorders_Report();
