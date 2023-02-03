<?php

namespace QuickBooks\InvoiceManager;

use QuickBooks\InvoiceManager\Page;
use QuickBooks\InvoiceManager\Menu;
use QuickBooks\InvoiceManager\UI;

defined( 'ABSPATH' ) || exit;

/**
 * Main plugin class.
 *
 * @since 1.0.0
 */
class InvoiceManager {

	/**
	 * The single instance of the class.
	 *
	 * @since 1.0.0
	 * @var object
	 */
	protected static $instance;

	/**
	 * Prevent cloning.
	 *
	 * @since 1.0.0
	 */
	private function __clone() {
		_doing_it_wrong( __FUNCTION__, esc_html__( 'Cloning is forbidden.', 'qim' ), '1.0.0' );
	}

	/**
	 * Prevent unserializing.
	 *
	 * @since 1.0.0
	 */
	final public function __wakeup() {
		_doing_it_wrong( __FUNCTION__, esc_html__( 'Unserializing instances of this class is forbidden.', 'qim' ), '1.0.0' );
	}

	/**
	 * Main plugin class instance.
	 *
	 * Ensures only one instance of the plugin is loaded or can be loaded.
	 *
	 * @since 1.0.0
	 *
	 * @return object Main instance of the class.
	 */
	final public static function instance() {
		if ( is_null( static::$instance ) ) {
			static::$instance = new static();
		}
		return static::$instance;
	}

	/**
	 * Plugin Constructor.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		// Load plugin text domain.
		add_action( 'init', [ $this, 'load_plugin_textdomain' ], 0 );
		// Check minified css/js.
		add_action( 'admin_notices', [ $this, 'build_dependencies_notice' ] );
		// Plugin description.
		add_filter( 'plugin_row_meta', [ $this, 'plugin_row_meta' ], 20, 2 );

		add_action( 'admin_enqueue_scripts', [ $this, 'admin_builder_enqueues' ] );

		$this->init();
	}

	/**
	 * Initialize plugin.
	 *
	 * @since 1.0.0
	 */
	public function init() {
		if ( is_admin() ) {
			( new Menu() )->init();
		}
	}

	/**
	 * Load Localisation files.
	 *
	 * Note: the first-loaded translation file overrides any following ones if the same translation is present.
	 *
	 * Locales found in:
	 *      - WP_LANG_DIR/qim/qim-LOCALE.mo
	 *      - WP_LANG_DIR/plugins/qim-LOCALE.mo
	 *
	 * @since 1.0.0
	 */
	public function load_plugin_textdomain() {
		$locale = apply_filters( 'plugin_locale', get_locale(), 'qim' );

		load_textdomain( 'qim', WP_LANG_DIR . '/qim/qim-' . $locale . '.mo' );
		load_plugin_textdomain( 'qim', false, plugin_basename( dirname( QIM_PLUGIN_FILE ) ) . '/languages' );
	}

	/**
	 * Display row meta in the Plugins list table.
	 *
	 * @param array  $plugin_meta Plugin Row Meta.
	 * @param string $plugin_file Plugin Base file.
	 * @return array Array of modified plugin row meta.
	 */
	public function plugin_row_meta( $plugin_meta, $plugin_file ) {
		if ( plugin_basename( QIM_PLUGIN_FILE ) === $plugin_file ) {
			$new_plugin_meta = [
				'docs' => '<a href="' . esc_url( '#' ) . '" aria-label="' . esc_attr__( 'View QuickBooks Invoice Manager documentation', 'qim' ) . '">' . esc_html__( 'Docs', 'qim' ) . '</a>',
			];

			return array_merge( $plugin_meta, $new_plugin_meta );
		}

		return (array) $plugin_meta;
	}

	/**
	 * Enqueues for the admin form builder.
	 *
	 * @since 1.0.0
	 */
	public function admin_builder_enqueues() {
		$min = defined( 'QIM_DEBUG' ) ? '' : '.min';

		// Stylesheet.
		wp_register_style(
			'qim-admin-page',
			plugins_url( 'assets/css/admin-qim.css', QIM_PLUGIN_FILE ),
			[],
			QIM_VERSION
		);

		// JavaScript.
		wp_register_script(
			'qim-admin-page',
			plugins_url( "assets/js/admin-qim{$min}.js", QIM_PLUGIN_FILE ),
			[ 'jquery' ],
			QIM_VERSION,
			true
		);
	}

	/**
	 * Check if the plugin assets are built and minified.
	 *
	 * @since 1.0.0
	 *
	 * @return bool
	 */
	private function check_build_dependencies() {
		// Check if we have compiled CSS.
		if ( ! file_exists( plugin_dir_path( QIM_PLUGIN_FILE ) . 'assets/css/admin-qim.css' ) ) {
			return false;
		}

		// Check if we have minified JS.
		if ( ! file_exists( plugin_dir_path( QIM_PLUGIN_FILE ) . 'assets/js/admin-qim.min.js' ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Output an admin notice if build dependencies has not been met.
	 *
	 * @since 1.0.0
	 */
	public function build_dependencies_notice() {
		if ( $this->check_build_dependencies() ) {
			return;
		}

		printf(
			'<div class="notice notice-error"><p>%s</p></div>',
			sprintf(
				/* translators: 1: grunt command. 2: URL of the GitHub Repository releases page */
				esc_html__( 'You have installed a development version of QuickBooks Invoice Manager which requires files to be built and minified. From the plugin directory, run %1$s to build and minify assets. Or you can download a pre-built version of the plugin from the %2$s.', 'qim' ),
				'<code>grunt assets</code>',
				'<a href="https://github.com/milan1750/qim/releases">GitHub Repository releases page</a>'
			)
		);
	}
}
