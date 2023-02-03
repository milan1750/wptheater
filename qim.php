<?php
/**
 * Plugin Name: QuickBooks Invoice Manager
 * Plugin URI: https://github.com/milan1750/qim
 * Description:
 * Version: 1.0.0
 * Author: Milan Malla
 * Author URI: https://github.com/milan1750
 * Text Domain: qim
 * Domain Path: /languages/
 * Requires at least: 5.4
 * Requires PHP: 5.6.20
 *
 * @package QuickBooks\InvoiceManager
 */

// Exit if access directly.
defined( 'ABSPATH' ) || exit;

// QuickBooks Invoice Manager version.
if ( ! defined( 'QIM_VERSION' ) ) {
	define( 'QIM_VERSION', '1.2.0' );
}

// QuickBooks Invoice Manager root file.
if ( ! defined( 'QIM_PLUGIN_FILE' ) ) {
	define( 'QIM_PLUGIN_FILE', __FILE__ );
}

/**
 * Autoload packages.
 *
 * We want to fail gracefully if `composer install` has not been executed yet, so we are checking for the autoloader.
 * If the autoloader is not present, let's log the failure and display a nice admin notice.
 */
$autoloader = __DIR__ . '/vendor/autoload.php';
if ( is_readable( $autoloader ) ) {
	include $autoloader;
} else {
	if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
		error_log( // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			sprintf(
			/* translators: 1: composer command. 2: plugin directory */
				esc_html__( 'Your installation of the QuickBooks Invoice Manager plugin is incomplete. Please run %1$s within the %2$s directory.', 'qim' ),
				'`composer install`',
				'`' . esc_html( str_replace( ABSPATH, '', __DIR__ ) ) . '`'
			)
		);
	}

	/**
	 * Outputs an admin notice if composer install has not been ran.
	 *
	 * @since 1.0.0
	 */
	add_action(
		'admin_notices',
		function () {
			printf(
				'<div class="notice notice-error"><p>%s</p></div>',
				sprintf(
					/* translators: 1: composer command. 2: plugin directory */
					esc_html__( 'Your installation of the QuickBooks Invoice Manager plugin is incomplete. Please run %1$s within the %2$s directory.', 'qim' ),
					'<code>composer install</code>',
					'<code>' . esc_html( str_replace( ABSPATH, '', __DIR__ ) ) . '</code>'
				)
			);
		}
	);
	return;
}

// Activate the plugin.
register_activation_hook( __FILE__, [ 'QuickBooks\InvoiceManager\Activate', 'init' ] );

// Initialize the plugin.
add_action( 'plugins_loaded', [ 'QuickBooks\InvoiceManager\InvoiceManager', 'instance' ] );
