<?php

namespace QuickBooks\InvoiceManager\Admin\Pages;

use PHP_CodeSniffer\Reports\Diff;
use QuickBooks\InvoiceManager\Admin\CustomersTable;
use QuickBooks\InvoiceManager\Api\Api;

defined( 'ABSPATH' ) || exit;

/**
 * Customers Class.
 *
 * @since 1.0.0
 */
class Customers extends Page {

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
		$this->title       = __( 'Customers', 'qim' );
		$this->description = __( 'Manager Customers', 'qim' );
		$this->slug        = 'customers';

		// Initilize actions.
		add_action( 'admin_init', [ $this, 'actions' ] );
		// Check new customers.
		add_filter( 'heartbeat_received', [ $this, 'check_new_customers' ], 10, 3 );
		$this->api = new Api();
		// $this->pull_customers();
	}

	/**
	 * Pull customers from QuickBooks and store in database.
	 *
	 * @since 1.0.0
	 */
	public function pull_customers() {
		$customers = $this->api->get_users();
		foreach ( $customers as $customer ) {
			$customer = [
				'nice_name'    => $customer->FullyQualifiedName, // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
				'first_name'   => $customer->GivenName, // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
				'last_name'    => $customer->FamilyName, // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
				'middle_name'  => $customer->MiddleName, // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
				'display_name' => $customer->DisplayName, // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
				'company_name' => $customer->CompanyName, // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
				'email'        => isset( $customer->PrimaryEmailAddr ) ? $customer->PrimaryEmailAddr->Address : '', // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
				'ID'           => $customer->Id, // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
			];

			$user_id = username_exists( $customer['email'] );

			// check that the email address does not belong to a registered user.
			if ( ! $user_id && false === email_exists( $customer['email'] ) ) {
				// create a random password.
				$random_password = wp_generate_password( 12, false );
				// create the user.
				$user_id = wp_insert_user(
					[
						'user_login'    => $customer['email'],
						'user_pass'     => $random_password,
						'user_email'    => $customer['email'],
						'first_name'    => $customer['first_name'],
						'last_name'     => $customer['last_name'],
						'display_name'  => $customer['display_name'],
						'user_nicename' => $customer['nice_name'],
						'role'          => 'qim_customer',
					]
				);

				add_user_meta(
					$user_id,
					'qim-customer-id',
					$customer['ID'],
					true
				);
			}
		}
	}

	/**
	 * Actions.
	 *
	 * @since 1.0.0
	 */
	public function actions() {
		// Trash customer.
		if ( isset( $_GET['trash'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			$this->trash_customer();
		}

		// Untrash customer.
		if ( isset( $_GET['untrash'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			$this->untrash_customer();
		}

		// Delete customer.
		if ( isset( $_GET['delete'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			$this->delete_customer();
		}

		// Export CSV.
		if ( isset( $_REQUEST['export_action'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			$this->export_csv();
		}

		// Empty Trash.
		if ( isset( $_REQUEST['delete_all'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			$this->empty_trash();
		}
	}

	/**
	 * Trash customer.
	 */
	private function trash_customer() {
		check_admin_referer( 'trash-customer' );

		$customer_id = isset( $_GET['trash'] ) ? absint( $_GET['trash'] ) : ''; //phpcs:ignore WordPress.Security.NonceVerification

		if ( $customer_id ) {
			wp_update_user(
				[
					'ID'     => $customer_id,
					'status' => 0,
				]
			);
		}

		wp_safe_redirect(
			esc_url_raw(
				add_query_arg(
					[
						'trashed' => 1,
					],
					admin_url( 'admin.php?page=qim-customers' )
				)
			)
		);
		exit();
	}

	/**
	 * Untrash customer.
	 */
	private function untrash_customer() {
		check_admin_referer( 'untrash-customer' );

		$customer_id = isset( $_GET['untrash'] ) ? absint( $_GET['untrash'] ) : ''; //phpcs:ignore WordPress.Security.NonceVerification

		if ( $customer_id ) {
			wp_update_user(
				[
					'ID'     => $customer_id,
					'status' => 1,
				]
			);
		}

		wp_safe_redirect(
			esc_url_raw(
				add_query_arg(
					[
						'untrashed' => 1,
					],
					admin_url( 'admin.php?page=qim-customers' )
				)
			)
		);
		exit();
	}

	/**
	 * Delete customer.
	 */
	private function delete_customer() {
		check_admin_referer( 'delete-customer' );

		if ( isset( $_GET['delete'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			$customer_id = absint( $_GET['delete'] ); // phpcs:ignore WordPress.Security.NonceVerification

			if ( $customer_id ) {
				wp_delete_user( $customer_id );
			}
		}

		wp_safe_redirect(
			esc_url_raw(
				add_query_arg(
					[
						'deleted' => 1,
					],
					admin_url( 'admin.php?page=qim-customers' )
				)
			)
		);
		exit();
	}

	/**
	 * Empty Trash.
	 */
	public function empty_trash() {
		check_admin_referer( 'bulk-customers' );
		// Todo later.
	}

	/**
	 * Do the customers export.
	 *
	 * @since 1.3.0
	 */
	public function export_csv() {
		check_admin_referer( 'bulk-customers' );
		// Todo later.
	}

	/**
	 * Conten.
	 *
	 * @since 1.0.0
	 */
	public function content() {
		echo '<div class="' . esc_attr( $this->slug ) . '">';

		self::table_list_output();
		echo '</div>';
	}

	/**
	 * Table list output.
	 */
	private static function table_list_output() {
		global $table;
		echo '<div class="wrap">';

		echo '<h1 class="wp-heading-inline">' . esc_html__( 'All Customers', 'qim' ) . '</h1>';

		echo '<form method="post">';

		// Prepare table.
		$table->prepare_items();

		echo '<div class="access-bar">';
		$table->views();

		$table->search_box( 'search', 'search_id' );

		echo '</div>';

		// Display table.
		$table->display();
		echo '</div>';

		echo '</div></form>';
	}

	/**
	 * List invoices.
	 *
	 * @since 1.0.0
	 */
	public function list_users() {
		$users = $this->api->get_users();
		?>
		<table class="qim-invoices-table">
			<thead>
				<tr>
					<th><?php esc_html_e( 'Customer/Company', 'qim' ); ?></th>
					<th><?php esc_html_e( 'Phone Number', 'qim' ); ?></th>
					<th><?php esc_html_e( 'Balance', 'qim' ); ?></th>
					<th><?php esc_html_e( 'Actions', 'qim' ); ?></th>
				</tr>
			</thead>
			<tbody>
			<?php
			foreach ( $users as $user ) {
				$user  = (array) $user;
				$phone = (array) $user['PrimaryPhone'];
				echo '<tr>';
				echo '<td>' . esc_html( $user['FullyQualifiedName'] ) . '</td>';
				echo '<td>' . esc_html( isset( $phone['FreeFormNumber'] ) ? $phone['FreeFormNumber'] : '' ) . '</td>';
				echo '<td>' . esc_html( $user['Balance'] ) . '</td>';
				echo '<td>Edit | View</td>';
			}
			?>
			</tbody>
		</table>
		<?php
	}

	/**
	 * Check new customers with heartbeat API.
	 *
	 * @since 1.5.0
	 *
	 * @param  array  $response  The Heartbeat response.
	 * @param  array  $data      The $_POST data sent.
	 * @param  string $screen_id The screen id.
	 * @return array The Heartbeat response.
	 */
	public function check_new_customers( $response, $data, $screen_id ) {
		if ( 'qim_page_customers' === $screen_id ) {
			$last_customer_id = ! empty( $data['qim_new_customers_last_customer_id'] ) ? absint( $data['qim_new_customers_last_customer_id'] ) : 0;

			// Count new customers.
			$customers_count = $this->get_count_customers_by_last_customer( $last_customer_id );

			if ( ! empty( $customers_count ) ) {
				/* translators: %d - New form customers count. */
				$response['qim_new_customers_notification'] = esc_html( sprintf( _n( '%d new customer since you last checked.', '%d new customers since you last checked.', $customers_count, 'qim' ), $customers_count ) );
			}
		}

		return $response;
	}

	/**
	 * Check Invoice Status.
	 *
	 * @since 1.0.0
	 * add_filter
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
