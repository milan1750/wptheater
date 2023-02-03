<?php

namespace QuickBooks\InvoiceManager\Admin\Pages;

use PHP_CodeSniffer\Reports\Diff;
use QuickBooks\InvoiceManager\Api\Api;

defined( 'ABSPATH' ) || exit;

/**
 * UI Class.
 *
 * @since 1.0.0
 */
class Invoices extends Page {

	/**
	 * APi.
	 *
	 * @since 1.0.0
	 * @var \QuickBooks\InvoiceManager\Api\Api Api Object.
	 */
	protected $api;

	/**
	 * Constrctor.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		$this->title       = __( 'Invoices', 'qim' );
		$this->description = __( 'My Invoices', 'qim' );
		$this->slug        = 'invoices';
		$this->api         = new Api();
	}

	/**
	 * Conten.
	 *
	 * @since 1.0.0
	 */
	public function content() {
		echo '<div class="' . esc_attr( $this->slug ) . '">';
		$this->list_my_invoices();
		echo '</div>';
	}

	/**
	 * List invoices.
	 *
	 * @since 1.0.0
	 */
	public function list_my_invoices() {
		$invoices = $this->api->get_invoices();
		?>
		<table class="qim-invoices-table">
			<thead>
				<tr>
					<th><?php esc_html_e( 'SO#', 'qim' ); ?></th>
					<th><?php esc_html_e( 'Date', 'qim' ); ?></th>
					<th><?php esc_html_e( 'Total', 'qim' ); ?></th>
					<th><?php esc_html_e( 'Status', 'qim' ); ?></th>
					<th><?php esc_html_e( 'Actions', 'qim' ); ?></th>
				</tr>
			</thead>
			<tbody>
			<?php
			foreach ( $invoices as $invoice ) {
				$invoice = (array) $invoice;
				echo '<tr>';
				echo '<td>' . esc_html( $invoice['DocNumber'] ) . '</td>';
				echo '<td>' . esc_html(
					gmdate(
						get_option( 'date_format' ) . ' ' . get_option( 'time_format' ),
						strtotime( $invoice['TxnDate'] )
					)
				) . '</td>';
				echo '<td>' . esc_html( $invoice['TotalAmt'] ) . '</td>';
				echo '<td>';
				$this->check_status( $invoice );
				echo '</td>';
				echo '<td>Edit | View</td>';
			}
			?>
			</tbody>
		</table>

			<?php
	}

	/**
	 * Check Invoice Status.
	 *
	 * @since 1.0.0
	 *
	 * @param array $invoice Invoice.
	 */
	public function check_status( $invoice = null ) {
		if ( empty( $invoice ) ) {
			return;
		}
		if ( $invoice['TotalAmt'] > 0 && 0 === (int) $invoice['Balance'] ) {
			echo '<span class="status status-paid"><span class="dashicons dashicons-saved"></span> ' . esc_html__( 'Paid', 'qim' ) . '</span>';
		} else {
			$date1      = new \DateTime( $invoice['DueDate'] );
			$date2      = new \DateTime();
			$difference = $date1->diff( $date2 )->days;
			if ( $difference <= 45 ) {
				$overdue = __( 'Overdue', 'qim' ) . ' ' . $difference . ' days';
			} else {
				$overdue = __( 'Overdue on', 'qim' ) . ' ' . gmdate( get_option( 'date_format' ), strtotime( $invoice['DueDate'] ) );
			}
			echo '<span class="status status-pending"><span class="dashicons dashicons-info"></span>&nbsp;&nbsp;' . esc_html( $overdue ) . ' </span>';
		}

	}
}
