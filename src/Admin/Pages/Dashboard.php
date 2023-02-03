<?php

namespace QuickBooks\InvoiceManager\Admin\Pages;

use QuickBooks\InvoiceManager\Api\Api;

defined( 'ABSPATH' ) || exit;

/**
 * UI Class.
 *
 * @since 1.0.0
 */
class Dashboard extends Page {

	/**
	 * Constrctor.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		$this->title       = __( 'Dashboard', 'qim' );
		$this->description = __( 'Find the summary of QuickBooks invoices', 'qim' );
	}

	/**
	 * Conten.
	 *
	 * @since 1.0.0
	 */
	public function content() {
	}
}
