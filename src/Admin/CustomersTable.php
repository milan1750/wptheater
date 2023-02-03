<?php

namespace QuickBooks\InvoiceManager\Admin;

/**
 * Qim Table.
 *
 * @package QuickBooks\InvoiceManager\Admin
 * @since   1.0.0
 */

use QuickBooks\InvoiceManager\Api\Api;

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

/**
 * CustomersTable table list class.
 */
class CustomersTable extends \WP_List_Table {

	/**
	 * Table Data.
	 *
	 * @since 1.0.0
	 * @var mixed
	 */
	private $table_data;

	/**
	 * Get table columns.
	 *
	 * @since 1.0.0
	 */
	public function get_columns() {
		$columns = [
			'cb'       => '<input type="checkbox" />',
			'name'     => __( 'Customer Name', 'qim' ),
			'username' => __( 'Username', 'qim' ),
			'email'    => __( 'Email', 'qim' ),
			'role'     => __( 'Role', 'qim' ),
		];
		return $columns;
	}

	/**
	 * Bulk Actions.
	 *
	 * @since 1.0.0
	 */
	public function get_bulk_actions() {
		$actions = [
			'delete_all' => __( 'Delete', 'qim' ),
			'trash_all'  => __( 'Move to Trash', 'qim' ),
		];
		return $actions;
	}

	/**
	 * Prepare table items.
	 *
	 * @since 1.0.0
	 */
	public function prepare_items() {
		if ( isset( $_POST['s'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
			$this->table_data = $this->get_customers_data( sanitize_text_field( wp_unslash( $_POST['s'] ) ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
		} else {
			$this->table_data = $this->get_customers_data();
		}
		$columns               = $this->get_columns();
		$hidden                = $this->hidden();
		$sortable              = $this->get_sortable_columns();
		$this->_column_headers = [ $columns, $hidden, $sortable ];

		usort( $this->table_data, [ &$this, 'usort_reorder' ] );
		/* pagination */
		$per_page     = $this->get_items_per_page( 'per_page', 10 );
		$current_page = $this->get_pagenum();
		$total_items  = count( $this->table_data );

		$this->table_data = array_slice( $this->table_data, ( ( $current_page - 1 ) * $per_page ), $per_page );

		$this->set_pagination_args(
			[
				'total_items' => $total_items, // total number of items.
				'per_page'    => $per_page, // items to show on a page.
				'total_pages' => ceil( $total_items / $per_page ), // use ceil to round up.
			]
		);

		$this->items = $this->table_data;
	}

	/**
	 * Hidden.
	 *
	 * @since 1.0.0
	 */
	public function hidden() {
		if ( is_array( get_user_meta( get_current_user_id(), 'managetoplevel_page_qim_tablecolumnshidden', true ) ) ) {
			return get_user_meta( get_current_user_id(), 'managetoplevel_page_qim_tablecolumnshidden', true );
		}
		return [];
	}

	/**
	 * Table list views.
	 *
	 * @return array
	 */
	protected function get_views() {
		$class           = '';
		$status_links    = [];
		$num_posts       = [];
		$total_customers = count( $this->items );
		$all_args        = [ 'page' => 'qim-customers' ];

		if ( empty( $class ) && empty( $_REQUEST['status'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			$class = 'current';
		}

		$all_inner_html = sprintf(
			/* translators: %s: count */
			_nx(
				'All <span class="count">(%s)</span>',
				'All <span class="count">(%s)</span>',
				$total_customers,
				'posts',
				'qim'
			),
			number_format_i18n( $total_customers )
		);

		$status_links['all'] = $this->get_edit_link( $all_args, $all_inner_html, $class );

		foreach ( get_post_stati( [ 'show_in_admin_status_list' => true ], 'objects' ) as $status ) {
			$class                     = '';
			$status_name               = $status->name;
			$num_posts[ $status_name ] = count( get_users( [ 'status' => $status_name ] ) );

			if ( ! in_array( $status_name, [], true ) || empty( $num_posts[ $status_name ] ) ) {
				continue;
			}

			if ( isset( $_REQUEST['status'] ) && $status_name === $_REQUEST['status'] ) { // phpcs:ignore WordPress.Security.NonceVerification
				$class = 'current';
			}

			$status_args = [
				'page'   => 'qim-customers',
				'status' => $status_name,
			];

			$status_label = sprintf(
				translate_nooped_plural( $status->label_count, $num_posts[ $status_name ] ),
				number_format_i18n( $num_posts[ $status_name ] )
			);

			$status_links[ $status_name ] = $this->get_edit_link( $status_args, $status_label, $class );
		}

		return $status_links;
	}

	/**
	 * Helper to create links to admin.php with params.
	 *
	 * @since 1.5.3
	 *
	 * @param string[] $args  Associative array of URL parameters for the link.
	 * @param string   $label Link text.
	 * @param string   $class Optional. Class attribute. Default empty string.
	 * @return string  The formatted link string.
	 */
	protected function get_edit_link( $args, $label, $class = '' ) {
		$url = add_query_arg( $args, 'admin.php' );

		$class_html   = '';
		$aria_current = '';

		if ( ! empty( $class ) ) {
			$class_html = sprintf(
				' class="%s"',
				esc_attr( $class )
			);

			if ( 'current' === $class ) {
				$aria_current = ' aria-current="page"';
			}
		}

		return sprintf(
			'<a href="%s"%s%s>%s</a>',
			esc_url( $url ),
			$class_html,
			$aria_current,
			$label
		);
	}

	/**
	 * Get customers data.
	 *
	 * @param string $search Search.
	 *
	 * @since 1.0.0
	 */
	private function get_customers_data( $search = '' ) {
		$args       = [
			'search' => $search,
		];
		$users      = get_users( $args );
		$user_array = [];
		foreach ( $users as $user ) {
			$user_array [] = [
				'ID'       => $user->ID,
				'name'     => $user->display_name,
				'username' => $user->user_login,
				'email'    => $user->user_email,
				'role'     => implode( ', ', array_map( [ $this, 'get_user_role' ], $user->roles ) ),
			];
		}
		return $user_array;
	}

	/**
	 * Get User Role.
	 *
	 * @since 1.0.0
	 *
	 * @param  string $role Role.
	 */
	public function get_user_role( $role ) {
		switch ( $role ) {
			case 'qim_customer':
				$role = 'QuickBox Customer';
				break;

			default:
				$role = '';
				break;
		}

		return $role;
	}

	/**
	 * Column default.
	 *
	 * @since 1.0.0
	 *
	 * @param  mixed  $item Item.
	 * @param  string $column_name Column name.
	 */
	public function column_default( $item, $column_name ) {
		switch ( $column_name ) {
			case 'id':
			case 'username':
			case 'name':
			case 'description':
			case 'status':
			case 'order':
			default:
				return $item[ $column_name ];
		}
	}

	/**
	 * Get customers ids.
	 *
	 * @since 1.0.0
	 */
	public function get_ids() {
		$args = [
			'fields' => [ 'ID' ],
			'role'   => 'administrator',
		];
		return wp_list_pluck( get_users( $args ), 'ID' );
	}

	/**
	 * Column cb.
	 *
	 * @since 1.0.0
	 *
	 * @param array $item Item.
	 */
	public function column_cb( $item ) {
		return sprintf(
			'<input type="checkbox" name="element[]" value="%s" />',
			$item['ID']
		);
	}

	/**
	 * Get sortable columns.
	 *
	 * @since 1.0.0
	 */
	protected function get_sortable_columns() {
		$sortable_columns = [
			'username' => [ 'username', true ],
			'name'     => [ 'name', true ],
			'email'    => [ 'email', true ],
			'role'     => [ 'role', true ],
		];
		return $sortable_columns;
	}

	/**
	 * Sorting function.
	 *
	 * @since 1.0.0
	 *
	 * @param  array $a A.
	 * @param  array $b B.
	 */
	public function usort_reorder( $a, $b ) {
		// If no sort, default to username.
		$orderby = ( ! empty( $_GET['orderby'] ) ) ? sanitize_text_field( wp_unslash( $_GET['orderby'] ) ) : 'username'; //phpcs:ignore WordPress.Security.NonceVerification.Recommended

		// If no order, default to asc.
		$order = ( ! empty( $_GET['order'] ) ) ? sanitize_text_field( wp_unslash( $_GET['order'] ) ) : 'asc'; //phpcs:ignore WordPress.Security.NonceVerification.Recommended

		// Determine sort order.
		$result = strcmp( $a[ $orderby ], $b[ $orderby ] );

		// Send final sort direction to usort.
		return ( 'asc' === $order ) ? $result : -$result;
	}

	/**
	 * Action links.
	 *
	 * @since 1.0.0
	 *
	 * @param array $item Item.
	 */
	public function column_name( $item ) {
		$actions = [
			'edit'   => sprintf(
				'<a href="?page=%s&action=%s&element=%s">' . __( 'Edit', 'qim' ) . '</a>',
				( isset( $_REQUEST['page'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['page'] ) ) : '' ), //phpcs:ignore WordPress.Security.NonceVerification.Recommended
				'edit',
				$item['ID']
			),
			'delete' => sprintf(
				'<a href="?page=%s&action=%s&element=%s">' . __( 'Delete', 'qim' ) . '</a>',
				( isset( $_REQUEST['page'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['page'] ) ) : '' ), //phpcs:ignore WordPress.Security.NonceVerification.Recommended
				'delete',
				$item['ID']
			),
		];

		return sprintf( '%1$s %2$s', $item['name'], $this->row_actions( $actions ) );
	}
}
