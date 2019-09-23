<?php
/**
 * An extension for the Connections Business Directory Link extension which adds a link action link in the list actions.
 *
 * @package   Connections CSV Export Buttons
 * @category  Extension
 * @author    Steven A. Zahm
 * @license   GPL-2.0+
 * @link      http://connections-pro.com
 * @copyright 2017 Steven A. Zahm
 *
 * @wordpress-plugin
 * Plugin Name:       Connections CSV Export Buttons
 * Plugin URI:        http://connections-pro.com
 * Description:       An extension for the Connections Business Directory Link extension which adds a link action link in the list actions.
 * Version:           1.0
 * Author:            Steven A. Zahm
 * Author URI:        http://connections-pro.com
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       connections-csv-export-shortcodes
 * Domain Path:       /languages
 */

if ( ! class_exists( 'Connections_CSV_Export_Shortcodes' ) ) :

	final class Connections_CSV_Export_Shortcodes {

		/**
		 * @since 1.0
		 */
		const VERSION = '1.0';

		/**
		 * @since 1.0
		 * @var string
		 */
		public static $url = '';

		/**
		 * Stores the instance of this class.
		 *
		 * @var $instance Connections_CSV_Export_Shortcodes
		 *
		 * @access private
		 * @static
		 * @since  1.0
		 */
		private static $instance;

		/**
		 * A dummy constructor to prevent the class from being loaded more than once.
		 *
		 * @access public
		 * @since  1.0
		 */
		public function __construct() { /* Do nothing here */ }

		/**
		 * The main plugin instance.
		 *
		 * @access private
		 * @since  1.0
		 * @static
		 *
		 * @return object self
		 */
		public static function instance() {

			if ( ! isset( self::$instance ) && ! ( self::$instance instanceof Connections_CSV_Export_Shortcodes ) ) {

				self::$instance = new Connections_CSV_Export_Shortcodes;
				self::$url      = plugin_dir_url( __FILE__ );

				self::hooks();
			}

			return self::$instance;
		}

		/**
		 * Register the plugin's hooks.
		 *
		 * @access private
		 * @since  1.0
		 * @static
		 */
		private static function hooks() {

			add_shortcode( 'cn_csv_export_button', array( __CLASS__, 'shortcode' ) );
			add_action( 'wp_enqueue_scripts', array( __CLASS__, 'registerScripts' ) );
			add_action( 'cn_csv_batch_export_download_headers', array( __CLASS__, 'addCookieHeader' ) );
		}

		/**
		 * Add the required cookie to properly support the jQuery fileDownload plugin.
		 *
		 * @link http://johnculviner.com/jquery-file-download-plugin-for-ajax-like-feature-rich-file-downloads/
		 *
		 * @access private
		 * @since  1.0
		 * @static
		 */
		public static function addCookieHeader() {

			header( 'Set-Cookie: fileDownload=true; path=/' );
		}

		/**
		 * @access private
		 * @since  1.0
		 * @static
		 */
		public static function registerScripts() {

			wp_register_script( 'jquery-filedownload',
			                    self::$url . 'vendor/jQuery-filedownload/jquery.fileDownload.js',
			                    array( 'jquery' ),
			                    self::VERSION,
			                    TRUE );

			wp_register_script( 'cn-csv-export-button',
			                    self::$url . 'assets/js/cn-csv-export-button.js',
			                    array( 'wp-util', 'jquery-filedownload' ),
			                    self::VERSION,
			                    TRUE );
		}

		/**
		 * @access private
		 * @since  1.0
		 * @static
		 */
		private static function enqueueScripts() {

			//wp_localize_script(
			//	'cn-csv-export-button',
			//	'cnCSVExortButton',
			//	array(
			//		'action' => 'export_csv_all',
			//		'nonce'  => wp_create_nonce( 'export_csv_all' ),
			//		'url'    => admin_url( 'admin-ajax.php' ),
			//	)
			//);

			wp_enqueue_script( 'cn-csv-export-button' );
		}

		/**
		 * @param array  $atts    Shortcode attributes array,
		 * @param null   $content Content between shortcode open/close tags.
		 * @param string $tag     Shortcode name.
		 *
		 * @return string
		 */
		public static function shortcode( $atts, $content = null, $tag ) {

			if ( ! is_user_logged_in() || ! current_user_can( 'export') ) return '';

			self::enqueueScripts();

			$defaults = array(
				'container_class' => '',
				'button_class'    => '',
				//'icon'            => 'download',
				'text'            => 'Download CSV',
				'type'            => 'all',
				'wait'            => 'Please wait, processing...',
				'done'            => 'Processing complete, preparing file for download...',
			    'mobile'          => 'CSV Export not available on mobile device.',
			);

			$atts = shortcode_atts( $defaults, $atts, $tag );

			// Ensure the set type is one of the support export types.
			$type = in_array( $atts['type'], array( 'address', 'phone', 'email', 'date', 'term', 'all' ) ) ? $atts['type'] : 'all';

			$containerClasses = array( 'cn-csv-export-button', 'cn-csv-export-button-' . sanitize_html_class( $type ) );
			$buttonClasses    = array();

			$customContainerClass = cnFunction::parseStringList( $atts['container_class'] );
			$customButtonClass    = cnFunction::parseStringList( $atts['button_class'] );

			if ( ! empty( $customContainerClass ) ) {

				$customContainerClass = array_map( 'sanitize_html_class', $customContainerClass );
				$containerClasses     = array_merge( $containerClasses, $customContainerClass );
			}

			if ( ! empty( $customButtonClass ) ) {

				$customButtonClass = array_map( 'sanitize_html_class', $customButtonClass );
				$buttonClasses     = array_merge( $containerClasses, $customButtonClass );
			}

			if ( wp_is_mobile() ) {

				$html = '<span>' . esc_html( $atts['mobile'] ) . '</span>';

			} else {

				$html = '<a ' . ( ! empty( $buttonClasses ) ? 'class="' . implode( ' ', $buttonClasses ) . '"' : '' ) . ' href="javascript:void(0)" download>' . esc_html( $atts['text'] ) . '</a>';
			}

			return '<div class="' . implode( ' ', $containerClasses ) . '" 
			             data-action="' . self::getActionByType( $type ) . '"
			             data-nonce="' . self::getNonceByType( $type ) . '"
			             data-wait="' . esc_attr( $atts['wait'] ) . '"
			             data-text="' . esc_attr( $atts['text'] ) . '">' . $html . '</div>';
		}

		/**
		 * @access private
		 * @since  1.0
		 * @static
		 *
		 * @param string $type
		 *
		 * @return string
		 */
		private static function getActionByType( $type ) {

			switch ( $type ) {

				case 'address':
					$action = 'export_csv_addresses';
					break;

				case 'phone':
					$action = 'export_csv_phone_numbers';
					break;

				case 'email':
					$action = 'export_csv_email';
					break;

				case 'date':
					$action = 'export_csv_dates';
					break;

				case 'term':
					$action = 'export_csv_term';
					break;

				default:
					$action = 'export_csv_all';
			}

			return $action;
		}

		/**
		 * @access private
		 * @since  1.0
		 * @static
		 *
		 * @param string $type
		 *
		 * @return string
		 */
		private static function getNonceByType( $type ) {

			switch ( $type ) {

				case 'address':
					$nonce = wp_create_nonce( 'export_csv_addresses' );
					break;

				case 'phone':
					$nonce = wp_create_nonce( 'export_csv_phone_numbers' );
					break;

				case 'email':
					$nonce = wp_create_nonce( 'export_csv_email' );
					break;

				case 'date':
					$nonce = wp_create_nonce( 'export_csv_dates' );
					break;

				case 'term':
					$nonce = wp_create_nonce( 'export_csv_term' );
					break;

				default:
					$nonce = wp_create_nonce( 'export_csv_all' );
			}

			return $nonce;
		}
	}

	/**
	 * Start up the extension.
	 *
	 * @access                public
	 * @since                 1.0
	 * @return mixed (object)|(bool)
	 */
	function Connections_CSV_Export_Shortcodes() {

		if ( class_exists( 'connectionsLoad' ) ) {

			return Connections_CSV_Export_Shortcodes::instance();

		} else {

			add_action(
				'admin_notices',
				create_function(
					'',
					'echo \'<div id="message" class="error"><p><strong>ERROR:</strong> Connections must be installed and active in order use the Connections CSV Export Shortcodes Extension.</p></div>\';'
				)
			);

			return FALSE;
		}
	}

	/**
	 * We'll load the extension on `plugins_loaded` so we know Connections will be loaded and ready first.
	 */
	add_action( 'plugins_loaded', 'Connections_CSV_Export_Shortcodes' );

endif;
