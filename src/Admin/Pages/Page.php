<?php

namespace QuickBooks\InvoiceManager\Admin\Pages;

defined( 'ABSPATH' ) || exit;

/**
 * UI Class.
 *
 * @since 1.0.0
 */
class Page {

	/**
	 * Page title.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	protected $title;

	/**
	 * Page description.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	protected $description;

	/**
	 * Page slug.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	protected $slug;

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 */
	public function init() {
		add_action( 'qim_load_admin_page', [ $this, 'load_admin_page' ], 9 );
	}

	/**
	 * Load admin page.
	 *
	 * @since 1.0.0
	 */
	public function load_admin_page() {
		if ( isset( $_GET['page'] ) && 'qim' === $_GET['page'] ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$view = isset( $_GET['view'] ) ? sanitize_text_field( wp_unslash( $_GET['view'] ) ) : 'dashboard'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$this->render( $view );
		}
	}

	/**
	 * Render page.
	 *
	 * @since 1.0.0
	 */
	public function render() {
		wp_enqueue_style( 'qim-admin-page' );
		wp_enqueue_script( 'qim-admin-page' );
		echo '<div id="qim-admin-page">';
		$this->header();
		$this->content();
		$this->footer();
		echo '</div>';
	}

	/**
	 * Render header.
	 *
	 * @since 1.0.0
	 */
	public function header() {
		do_action( 'qim_after_page_header' );
	}

	/**
	 * Render content.
	 *
	 * @since 1.0.0
	 */
	public function content() {
	}

	/**
	 * Render footer.
	 *
	 * @since 1.0.0
	 */
	public function footer() {
		do_action( 'qim_after_page_footer' );
	}
}
