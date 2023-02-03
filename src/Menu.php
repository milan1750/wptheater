<?php

namespace QuickBooks\InvoiceManager;

use QuickBooks\InvoiceManager\Admin\CustomersTable;

defined( 'ABSPATH' ) || exit;

/**
 * Main plugin class.
 *
 * @since 1.0.0
 */
class Menu {

	/**
	 * Register admin menus.
	 *
	 * @since 1.0.0
	 */
	public function init() {
		add_filter( 'qim_admin_pages', [ $this, 'admin_pages' ] );
		add_action( 'admin_menu', [ $this, 'register_admin_menus' ], 9 );
		add_filter( 'qim_pages', [ $this, 'pages' ] );

	}

	/**
	 * Validate screen options on update.
	 *
	 * @param bool|int $status Screen option value. Default false to skip.
	 * @param string   $option The option name.
	 * @param int      $value  The number of rows to use.
	 */
	public function set_screen_option( $status, $option, $value ) {
		if ( in_array( $option, [ 'qim_customers_per_page', 'qim_invoices_per_page' ], true ) ) {
			return $value;
		}
		return $status;
	}

	/**
	 * Register our menus.
	 *
	 * @since 1.0.0
	 */
	public function register_admin_menus() {

		// Default top level menu item.
		add_menu_page(
			esc_html__( 'Quickbox Invoice Manager', 'qim' ),
			esc_html__( 'Invoice Manager', 'qim' ),
			'administrator',
			'qim',
			[ $this, 'load_admin_page' ],
			'dashicons-media-default',
			apply_filters( 'qim_menu_position', '58.9' )
		);

		do_action( 'qim_before_admin_sub_menu', $this );

		$pages = apply_filters( 'qim_admin_pages', [] );
		// All sub menu item.
		foreach ( $pages as $slug => $page ) {
			if ( isset( $page['show_in_menu'] ) && true === $page['show_in_menu'] ) {
				// Dashboard sub menu item.
				$qim_page = add_submenu_page(
					'qim',
					esc_html__( 'Quickbox Invoice Manager', 'qim' ),
					esc_html( $page['title'] ),
					isset( $page['cap'] ) ? sanitize_text_field( $page['cap'] ) : 'manage_qim',
					$slug,
					[ $this, 'load_admin_page' ]
				);

				add_action( 'load-' . $qim_page, [ $this, 'qim_screen_options' ] );
			}
		}
		do_action( 'qim_after_admin_sub_menu', $this );
	}

	/**
	 * Screen options.
	 *
	 * @since 1.0.0
	 */
	public function qim_screen_options() {
		global $table;

		$screen = get_current_screen();

		// get out of here if we are not on our settings page.
		if ( ! is_object( $screen ) || 'invoice-manager_page_qim-customers' !== $screen->id ) {
			return;
		}

		add_screen_option(
			'per_page',
			[
				'default' => 20,
				'option'  => 'qim_customers_per_page',
			]
		);

		$table = new CustomersTable();
	}

	/**
	 * Load dashboard view.
	 *
	 * @since 1.0.0
	 */
	public function load_admin_page() {
		if ( isset( $_GET['page'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$pages = apply_filters(
				'qim_admin_pages',
				[]
			);

			$page = sanitize_text_field( wp_unslash( $_GET['page'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$view = isset( $pages[ $page ] ) && isset( $pages[ $page ]['src'] ) && class_exists( $pages[ $page ]['src'] ) ? $pages[ $page ]['src'] : __NAMESPACE__ . '\\PageNotFound';
			( new $view() )->render();
		}
	}

	/**
	 * Admin pages.
	 *
	 * @param array $pages Pages.
	 *
	 * @since 1.0.0
	 */
	public function admin_pages( $pages ) {
		return array_merge(
			$pages,
			[
				'qim'           => [
					'src'          => __NAMESPACE__ . '\\Admin\\Pages\\Dashboard',
					'title'        => __( 'Dashboard', 'qim' ),
					'show_in_menu' => true,
				],
				'qim-customers' => [
					'src'          => __NAMESPACE__ . '\\Admin\\Pages\\Customers',
					'title'        => __( 'Customers', 'qim' ),
					'cap'          => 'qim_view_customers',
					'show_in_menu' => true,
				],
				'qim-invoices'  => [
					'src'          => __NAMESPACE__ . '\\Admin\\Pages\\Invoices',
					'title'        => __( 'Invoices', 'qim' ),
					'cap'          => 'qim_view_invoices',
					'show_in_menu' => true,
				],
				'qim-settings'  => [
					'src'          => __NAMESPACE__ . '\\Admin\\Pages\\Settings',
					'title'        => __( 'Settings', 'qim' ),
					'cap'          => 'manage_qim',
					'show_in_menu' => true,
				],
				'qim-404'       => [
					'src'          => __NAMESPACE__ . '\\PageNotFound',
					'title'        => __( '404', 'qim' ),
					'cap'          => 'manage_qim',
					'show_in_menu' => false,
				],
			]
		);
	}
}
