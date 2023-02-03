<?php

namespace QuickBooks\InvoiceManager\Api;

use Exception;
use QuickBooksOnline\API\Core\OAuth\OAuth2\OAuth2LoginHelper;
use QuickBooksOnline\API\DataService\DataService;
use QuickBooksOnline\API\Diagnostics\Logger;

defined( 'ABSPATH' ) || exit;

/**
 * Api Class.
 *
 * @since 1.0.0
 */
class Api {

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		$this->client_id = get_option( 'qim_client_id', false );

		$this->client_secret       = get_option( 'qim_client_secret', false );
		$this->access_token_value  = get_option( 'qim_access_token', false );
		$this->refresh_token_value = get_option( 'qim_refresh_token', false );
		$this->realm_id            = get_option( 'qim_realm_id', false );

		if ( ! empty( $this->access_token_value ) ) {
			$login_helper              = new OAuth2LoginHelper( $this->client_id, $this->client_secret );
			$access_token              = $login_helper->
					refreshAccessTokenWithRefreshToken( $this->refresh_token_value );
			$this->access_token_value  = $access_token->getAccessToken();
			$this->refresh_token_value = $access_token->getRefreshToken();
			update_option( 'qim_access_token', $this->access_token_value );
			update_option( 'qim_refresh_token', $this->refresh_token_value );
		}

		// Prep Data Services.
		try {
			$this->data_service = DataService::Configure(
				[
					'auth_mode'       => 'oauth2',
					'ClientID'        => $this->client_id,
					'ClientSecret'    => $this->client_secret,
					'accessTokenKey'  => $this->access_token_value,
					'refreshTokenKey' => $this->refresh_token_value,
					'QBORealmID'      => $this->realm_id,
					'baseUrl'         => 'development',
				]
			);
		} catch ( Exception $e ) {
			error_log( print_r( $e->getMessage(), true ) );
		}
	}

	/**
	 * Has api connection.
	 *
	 * @since 1.0.0
	 *
	 * @return bool
	 */
	public function has_connection() {
		$client_id     = get_option( 'qim_client_id', false );
		$client_secret = get_option( 'qim_client_secret', false );
		$access_token  = get_option( 'qim_access_token', false );
		$refresh_token = get_option( 'qim_refresh_token', false );
		$realm_id      = get_option( 'qim_realm_id', false );
		return ( empty( $client_id ) || empty( $client_secret ) || empty( $access_token ) || empty( $refresh_token ) || empty( $realm_id ) ) ? false : true;
	}

	/**
	 * Get Invoices
	 *
	 * @param string $user_id User ID.
	 * @param string $invoice_id Invoice ID.
	 *
	 * @since 1.0.0
	 */
	public function get_invoices( $user_id = null, $invoice_id = null ) {
		if ( empty( $user_id ) && empty( $invoice_id ) ) {
			return $this->data_service->Query( 'SELECT * FROM invoice ORDERBY TxnDate desc STARTPOSITION 0 MAXRESULTS 1000' );
		}
	}

	/**
	 * Get Users.
	 *
	 * @since 1.0.0
	 *
	 * @param  string $user_id User ID.
	 */
	public function get_users( $user_id = null ) {
		if ( empty( $user_id ) ) {
			return $this->data_service->Query( 'SELECT * FROM customer STARTPOSITION 0 MAXRESULTS 1000' );
		}
	}
}
