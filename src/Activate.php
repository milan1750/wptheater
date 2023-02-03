<?php

namespace QuickBooks\InvoiceManager;

/**
 * Activate the plugin.
 *
 * @since 1.0.0
 *
 * @package QuickBooks\InvoiceManager
 */
class Activate {

	/**
	 * Activation init.
	 *
	 * @since 1.0.0
	 */
	public static function init() {
		if ( version_compare( get_option( 'qim_version', '0.0.0' ), QIM_VERSION, '<' ) ) {
			self::install();
		}
	}

	/**
	 * Install plugin.
	 *
	 * @since 1.0.0
	 */
	public static function install() {
		self::create_roles();

		// Finally update version to latest.
		update_option( 'qim_version', QIM_VERSION );
	}

	/**
	 * Create roles and capabilities.
	 *
	 * @since 1.0.0
	 */
	public static function create_roles() {
		global $wp_roles;

		if ( ! class_exists( 'WP_Roles' ) ) {
			return;
		}

		if ( ! isset( $wp_roles ) ) {
			$wp_roles = new WP_Roles(); // @codingStandardsIgnoreLine
		}

		/* Create SEO Manager User Role */
		add_role(
			'qim_customer', // System name of the role.
			__( 'QuickBox Customer', 'qim' ), // Display name of the role.
			[
				'read' => true,
			]
		);

		$capabilities = self::get_core_capabilities();

		foreach ( $capabilities as $cap_group ) {
			foreach ( $cap_group as $cap ) {
				$wp_roles->add_cap( 'administrator', $cap );
			}
		}
	}

	/**
	 * Get the core capabilities.
	 *
	 * Core capabilities are assigned to admin during installation or reset.
	 *
	 * @since 1.0.0
	 *
	 * @return array $capabilities Core capabilities.
	 */
	private static function get_core_capabilities() {
		$capabilities = [];

		$capabilities['core'] = [
			'manage_qim',
		];

		$capability_types = [ 'customers', 'invoices', 'sales_orders', 'products' ];

		foreach ( $capability_types as $capability_type ) {

			foreach ( [ 'view', 'edit', 'delete' ] as $context ) {
				$capabilities[ $capability_type ][] = "qim_{$context}_{$capability_type}";
				$capabilities[ $capability_type ][] = "qim_{$context}_others_{$capability_type}";
			}
		}

		return $capabilities;
	}
}
