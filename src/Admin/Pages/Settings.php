<?php

namespace QuickBooks\InvoiceManager\Admin\Pages;

use Exception;
use QuickBooks\InvoiceManager\Api\Api;
use QuickBooksOnline\API\DataService\DataService;

defined( 'ABSPATH' ) || exit;

/**
 * UI Class.
 *
 * @since 1.0.0
 */
class Settings extends Page {

	/**
	 * Page URL.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	protected $page_url;

	/**
	 * Constrctor.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		$this->title       = __( 'Settings', 'qim' );
		$this->description = __( 'Manage various QuickBooks Invoice Manager options', 'qim' );
		$this->slug        = 'settings';
		$this->page_url    = admin_url( 'admin.php?page=qim-settings' );

		$this->api = new Api();
	}

	/**
	 * Conten.
	 *
	 * @since 1.0.0
	 */
	public function content() {
		echo '<div class="' . esc_attr( $this->slug ) . '">';
		$this->quickbooks_connection();
		echo '</div>';
	}

	/**
	 * QuickBooks connection.
	 *
	 * @since 1.0.0
	 */
	public function quickbooks_connection() {
		echo '<div class="quickbooks-connection">';

		if ( isset( $_GET['action'] ) && 'disconnect-quickbooks' === sanitize_text_field( wp_unslash( $_GET['action'] ) ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
			update_option( 'qim_client_id', '' );
			update_option( 'qim_client_secret', '' );
			update_option( 'qim_realm_id', '' );
			update_option( 'qim_access_token', '' );
			update_option( 'qim_refresh_token', '' );
		}

		if ( isset( $_POST['action'] ) && 'connect-to-quickbooks' === sanitize_text_field( wp_unslash( $_POST['action'] ) ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
			if ( isset( $_POST['qim-quickbooks-connect-nonce'] ) && wp_verify_nonce( sanitize_key( wp_unslash( $_POST['qim-quickbooks-connect-nonce'] ) ), 'qim-quickbooks-connect-nonce' ) ) {
				$client_id          = isset( $_POST['client_id'] ) ? sanitize_text_field( wp_unslash( $_POST['client_id'] ) ) : '';
				$client_secret      = isset( $_POST['client_secret'] ) ? sanitize_text_field( wp_unslash( $_POST['client_secret'] ) ) : '';
				$authorization_code = isset( $_POST['authorization_code'] ) ? sanitize_text_field( wp_unslash( $_POST['authorization_code'] ) ) : '';
				$realm_id           = isset( $_POST['realm_id'] ) ? sanitize_text_field( wp_unslash( $_POST['realm_id'] ) ) : '';
				$mode               = isset( $_POST['mode'] ) ? sanitize_text_field( wp_unslash( $_POST['mode'] ) ) : 'development';

				if ( empty( $client_id ) || empty( $client_secret ) ) {
					echo '<p class="error">' . esc_html__( 'Client ID and Client secret are required', 'qim' ) . '</p>';
				} else {
					$data_service         = DataService::Configure(
						[
							'auth_mode'    => 'oauth2',
							'ClientID'     => $client_id,
							'ClientSecret' => $client_secret,
							'RedirectURI'  => admin_url( 'admin.php?page=qim-settings' ),
							'scope'        => 'com.intuit.quickbooks.accounting',
							'baseUrl'      => $mode,
						]
					);
					$oauth_2_login_helper = $data_service->getOAuth2LoginHelper();
					if ( isset( $_POST['submit'] ) && 'Connect' === sanitize_text_field( wp_unslash( $_POST['submit'] ) ) ) {
						update_option( 'qim_client_id', $client_id );
						update_option( 'qim_client_secret', $client_secret );
						$auth_url = $oauth_2_login_helper->getAuthorizationCodeURL();
					} else {
						if ( empty( $authorization_code ) || empty( $realm_id ) ) {
							echo '<p class="error">' . esc_html__( 'Authorization Code and Realm Id are required', 'qim' ) . '</p>';
						} else {
							try {
								$access_token         = $oauth_2_login_helper->exchangeAuthorizationCodeForToken( $authorization_code, $realm_id );
								$access_token_value   = $access_token->getAccessToken();
								$refresh_token_valuie = $access_token->getRefreshToken();
								update_option( 'qim_access_token', $access_token_value );
								update_option( 'qim_refresh_token', $refresh_token_valuie );
								update_option( 'qim_realm_id', $realm_id );
								echo '<p class="success">' . esc_html__( 'Succefully connected to QuickBooks', 'qim' ) . '</p>';
							} catch ( Exception $e ) {
								echo '<p class="error">' . esc_html__( 'Please check Client ID and Client Secret are valid', 'qim' ) . '</p>';
							}
						}
					}
				}
			} else {
				echo '<p class="error">' . esc_html__( 'Couldnot connect to the QuickBooks, Please try again', 'qim' ) . '</p>';
			}
		}

		if ( $this->api->has_connection() ) {

			echo '<p><a class="disconnect" href="' . esc_url_raw( $this->page_url . '&action=disconnect-quickbooks' ) . '">' . esc_html__( 'Disconnect QuickBooks', 'qim' ) . '</a></p>';
		} else {
			$client_id     = get_option( 'qim_client_id', false );
			$client_secret = get_option( 'qim_client_secret', false );
			$access_token  = get_option( 'qim_access_token', false );
			$realm_id      = get_option( 'qim_realm_id', false );
			$mode          = get_option( 'qim_mode', false );
			?>
			<h3>Connect to QuickBooks</h3>
			<form action="" method="POST">
				<input type="hidden" name="qim-quickbooks-connect-nonce" value="<?php echo esc_attr( wp_create_nonce( 'qim-quickbooks-connect-nonce' ) ); ?>">
				<input type="hidden" name="action" value="connect-to-quickbooks">
				<div class="form-group">
					<label for="mode"><?php esc_html( $mode ); ?></label>
					<select name="mode" id="mode">
						<option value="development" <?php selected( 'development' === $mode, true, true ); ?>>Development</option>
						<option value="production" <?php selected( 'production' === $mode, true, true ); ?>>Production</option>
					</select>
				</div>
				<div class="form-group">
					<label for="client_id"><?php esc_html_e( 'Client ID', 'qim' ); ?></label>
					<input type="text" name="client_id" id="client_id" value="<?php echo esc_attr( $client_id ); ?>">
				</div>
				<div class="form-group">
				<label for="client_secret"><?php esc_html_e( 'Client Secret', 'qim' ); ?></label>
					<input type="text" name="client_secret" id="client_secret" value="<?php echo esc_attr( $client_secret ); ?>">
				</div>
				<?php
				if ( isset( $_GET['code'] ) && isset( $_GET['realmId'] ) ) {
					?>
				<div class="form-group">
					<label for="authorization_code"><?php esc_html_e( 'Authorization Code', 'qim' ); ?></label>
					<input type="text" name="authorization_code" id="authorization_code" value="<?php echo esc_attr( sanitize_text_field( wp_unslash( $_GET['code'] ) ) ); ?>">
				</div>
				<div class="form-group">
					<label for="realm_id"><?php esc_html_e( 'Realm ID', 'qim' ); ?></label>
					<input type="text" name="realm_id" id="realm_id" value="<?php echo esc_attr( sanitize_text_field( wp_unslash( $_GET['realmId'] ) ) ); ?>">
				</div>
					<?php

				}
				?>
				<div class="form-group">
					<?php
					if ( isset( $auth_url ) ) {
						echo '<a class="get-auth-code" href="' . esc_url_raw( $auth_url ) . '">Get Authorized Keys</a>';
					} elseif ( isset( $_GET['code'] ) && isset( $_GET['realmId'] ) ) {
						echo '<input type="submit" name="submit" value="Save Connection">';
					} else {
						echo '<input type="submit" name="submit" value="Connect">';
					}
					?>
				</div>
			</form>
			<?php
		}
		echo '</div>';
	}
}
