<?php
/**
 * Plugin Name: WPForms Repeater
 * Plugin URI: https://github.com/WPCanny/wpforms-repeater
 * Description: Repeater for WPForms provides users with flexibility of repeating any number of fields in the form from the frontend.
 * Version: 1.2.0
 * Author: WPCanny
 * Author URI: https://wpcanny.com
 * Text Domain: wpforms-repeater
 * Domain Path: /languages/
 * Requires at least: 5.4
 * Requires PHP: 5.6.20
 *
 * @package WPForms\Repeater
 */

// Exit if access directly.
defined( 'ABSPATH' ) || exit;

// WPForms Repeater version.
if ( ! defined( 'WPFORMS_REPEATER_VERSION' ) ) {
	define( 'WPFORMS_REPEATER_VERSION', '1.2.0' );
}

// WPForms Repeater root file.
if ( ! defined( 'WPFORMS_REPEATER_PLUGIN_FILE' ) ) {
	define( 'WPFORMS_REPEATER_PLUGIN_FILE', __FILE__ );
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
				esc_html__( 'Your installation of the WPForms Repeater plugin is incomplete. Please run %1$s within the %2$s directory.', 'wpforms-repeater' ),
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
					esc_html__( 'Your installation of the WPForms Repeater plugin is incomplete. Please run %1$s within the %2$s directory.', 'wpforms-repeater' ),
					'<code>composer install</code>',
					'<code>' . esc_html( str_replace( ABSPATH, '', __DIR__ ) ) . '</code>'
				)
			);
		}
	);
	return;
}

// Initialize the plugin.
add_action( 'plugins_loaded', [ 'WPForms\Repeater\Plugin', 'instance' ], 0 );
