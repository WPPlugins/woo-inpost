<?php
/**
 * CrossBorder API
*
* @author      WPDesk
* @category    Admin
* @package     EasyPack
* @version     2.1.0
*/

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( ! class_exists( 'CrossBorder_API' ) ) :

class CrossBorder_API {

	protected static $instance;

	protected $login;
	protected $token;

	protected $cache_period = DAY_IN_SECONDS;

	public function __construct()
	{
		$this->login 			= get_option( 'easypack_crossborder_login' );
		$this->password 		= get_option( 'easypack_crossborder_password' );
		$this->client_id 		= get_option( 'easypack_crossborder_client_id' );
		$this->client_secret	= get_option( 'easypack_crossborder_client_secret' );
		$this->api_url 			= get_option( 'easypack_crossborder_api_url' );
		$this->token 			= get_option( 'easypack_crossborder_token' );
		$this->token_expires	= get_option( 'easypack_crossborder_token_expires', 0 );
		$this->token_refresh 	= get_option( 'easypack_crossborder_token_refresh', 0 );
	}

	public static function CrossBorder_API() {
		if (self::$instance === null) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	public function clear_cache() {
		delete_option( 'easypack_crossborder_token' );
		delete_option( 'easypack_crossborder_token_expires');
		delete_option( 'easypack_cache_machines_crossborder' );
		delete_option( 'easypack_cache_machines_crossborder_time' );
		delete_option( 'easypack_crossborder_geowidget_keys' );
		$this->login 			= get_option( 'easypack_crossborder_login' );
		$this->password 		= get_option( 'easypack_crossborder_password' );
		$this->client_id 		= get_option( 'easypack_crossborder_client_id' );
		$this->client_secret	= get_option( 'easypack_crossborder_client_secret' );
		$this->api_url 			= get_option( 'easypack_crossborder_api_url' );
		$this->token 			= get_option( 'easypack_crossborder_token' , '' );
		$this->token_expires	= get_option( 'easypack_crossborder_token_expires', 0 );
		$this->token_refresh 	= get_option( 'easypack_crossborder_token_refresh', 0 );
	}

	function translate_error( $error ) {
		$errors = array(
			'XBorderV1.RecipientEmailInvalid' 				=> __( 'recipient e-mail is invalid', EasyPack::$text_domain ),
			'XBorderV1.RecipientPhoneIsEmpty' 				=> __( 'recipient phone is empty', EasyPack::$text_domain ),
			'XBorderV1.RecipientPhoneIsInvalid' 			=> __( 'recipient phone is invalid', EasyPack::$text_domain ),
			'XBorderV1.RecipientFirstNameEmpty' 			=> __( 'recipient first name is empty', EasyPack::$text_domain ),
			'XBorderV1.RecipientLastNameEmpty' 				=> __( 'recipient last name is empty', EasyPack::$text_domain ),
			'XBorderV1.RecipientPhoneIsEmpty' 				=> __( 'recipient phone is empty', EasyPack::$text_domain ),
			'XBorderV1.RecipientPhoneIsInvalid' 			=> __( 'recipient phone is invalid', EasyPack::$text_domain ),
			'XBorderV1.RecipientCountryCodeIsInvalid' 		=> __( 'recipient country is empty', EasyPack::$text_domain ),
			'XBorderV1.RecipientEmailIsEmpty' 				=> __( 'recipient e-mail is empty', EasyPack::$text_domain ),
			'XBorderV1.RecipientZipCodeIsEmpty' 			=> __( 'recipient postcode is empty', EasyPack::$text_domain ),
			'XBorderV1.RecipientZipCodeIsInvalid' 			=> __( 'recipient postcode is invalid', EasyPack::$text_domain ),
			'XBorderV1.RecipientBuildingNumberIsEmpty' 		=> __( 'recipient building number is empty', EasyPack::$text_domain ),
			'XBorderV1.RecipientBuildingNumberIsInvalid' 	=> __( 'recipient building number is invalid', EasyPack::$text_domain ),
			'XBorderV1.RecipientCityIsEmpty' 				=> __( 'recipient city is empty', EasyPack::$text_domain ),
			'XBorderV1.RecipientCityIsInvalid' 				=> __( 'recipient city is invalid', EasyPack::$text_domain ),
			'XBorderV1.RecipientStreetIsEmpty' 				=> __( 'recipient street is empty', EasyPack::$text_domain ),
			'XBorderV1.RecipientStreetIsInvalid' 			=> __( 'recipient street is invalid', EasyPack::$text_domain ),
			'XBorderV1.NotSupportedRoute' 					=> __( 'recipient country not supported', EasyPack::$text_domain ),
			'XBorderV1.InvalidPackSize' 					=> __( 'invalid pack size', EasyPack::$text_domain ),
				
			'AccountBalanceNotSufficient'					=> __( 'You have not enough funds to pay for this parcel.', EasyPack::$text_domain ),

			'invalid_grant'									=> __( 'Invalid Password', EasyPack::$text_domain ),
			'invalid_request'								=> __( 'Invalid Login', EasyPack::$text_domain ),
		);

		if ( isset( $errors[$error] ) ) return $errors[$error];
		if ( isset( $errors['XBorderV1.' . $error] ) ) return $errors['XBorderV1.' . $error];
		return $error;
	}

	function get_error_array( $errors ) {
		$ret = ' ';
		if ( is_array( $errors ) ) {
			foreach ( $errors as $key => $value ) {
				if ( is_integer( $key ) ) {
					$ret .= $this->get_error_array( $value ) . ', ';
				}
				else {
					$ret .= $key . ': ' . $this->get_error_array( $value ) . ', ';
				}
			}
		}
		else {
			$ret .= $errors;
		}
		return ' ' . $ret = trim( trim( $ret ), ',' );;
	}

	function get_error( $errors ) {
		$ret = '';
		foreach ( $errors as $error ) {
			$ret .= '<li>' . $this->translate_error( $error['code'] ) . '</li>';
		}
		if ( $ret != '' ) {
			$ret = '<ul>' . $ret . '</ul>';
		}
		return trim( trim( $ret ), ',' );
	}

	public function post( $path, $args = array(), $method = 'POST' ) {
		$url = untrailingslashit( $this->api_url ) . str_replace( ' ', '%20', str_replace( '@', '%40', $path ) );
		$request_args = array( 'timeout' => 30, 'method' => $method );

		$request_args['headers'] = array( 'Authorization' => 'Bearer ' . $this->access_token(), 'Content-Type' => 'application/json' );

		$request_args['body'] = $args;
		$request_args['body'] = json_encode($args);
		$response = wp_remote_post( $url, $request_args );
		if ( is_wp_error( $response ) ) {
			throw new Exception( $response->get_error_message() );
		}
		else {
			if ( intval( $response['response']['code'] ) >= 400 ) {
				$ret = json_decode( $response['body'], true );
				$message = $this->get_error( $ret );
				throw new Exception( $message );
			}
			else {
				$ret = json_decode( $response['body'], true );
				return $ret;
			}
		}

	}

	public function put( $path, $args = array(), $method = 'PUT' ) {
		return $this->post( $path, $args, 'PUT' );
	}

	public function get( $path, $args = array() ) {
		$url = untrailingslashit( $this->api_url ) . str_replace( ' ', '%20', str_replace( '@', '%40', $path ) );
		$request_args = array( 'timeout' => 30 );

		$request_args['headers'] = array( 'Authorization' => 'Bearer ' . $this->access_token(), 'Content-Type' => 'application/json' );

		$response = wp_remote_get( $url, $request_args );
		if ( is_wp_error( $response ) ) {
			throw new Exception( $response->get_error_message() );
		}
		else {
			if ( intval( $response['response']['code'] ) >= 400 ) {
				$ret = json_decode( $response['body'], true );
				$message = $this->get_error( $ret );
				throw new Exception( $message );
			}
			else {
				$ret = json_decode( $response['body'], true );
			}
			return $ret;
		}

	}

	public function ping() {
		$me = $this->me_inpost_data();
		return $me;
	}

	public function access_token() {

		if ( $this->token_expires < time() ) {
			$data = array(
						'password' 		=> $this->password,
						'username' 		=> $this->login,
						'client_id' 	=> $this->client_id,
						'client_secret' => $this->client_secret,
						'grant_type' 	=> 'password',
					);

			$request_args = array( 'timeout' => 30, 'method' => 'POST' );

			$request_args['headers'] = array( 'Content-Type' => 'application/json' );

			$request_args['body'] = json_encode($data);
			$url = untrailingslashit( $this->api_url ). '/access-token';
			$response = wp_remote_post( $url, $request_args );
			if ( is_wp_error( $response ) ) {
				throw new Exception( $response->get_error_message() );
			}
			else {
				$ret = json_decode( $response['body'], true );
				if ( isset( $ret['error'] ) ) {
					throw new Exception( $this->translate_error( $ret['error'] ) );
				}
				else {
					if ( $response['response']['code'] != '200' ) {
						throw new Exception( $response['response']['status'] . ' ' . $response['response']['message'] );
					}
					else {
						$this->token = $ret['access_token'];
						$this->token_expires = time() + intval( $ret['expires_in'] );
						$this->token_refresh = $ret['refresh_token'];
						update_option( 'easypack_crossborder_token', $this->token );
						update_option( 'easypack_crossborder_token_expires', $this->token_expires );
						update_option( 'easypack_crossborder_token_refresh', $this->token_refresh );
					}
				}
			}
		}
		return $this->token;
	}

	public function me_routes() {
		$response = $this->get( '/users/me/routes' );
		return $response;
	}

	public function me() {
		$response = $this->get( '/users/me' );
		return $response;
	}

	public function me_inpost_data() {
		$response = $this->get( '/users/me/inpost-data' );
		return $response;
	}

	public function machines( $throw_excetion = false ) {
		$machines = get_option( 'easypack_cache_machines_crossborder' );
		if ( ! $machines || intval( get_option( 'easypack_cache_machines_crossborder_time' ) ) < time() ) {
			try {
				$response = $this->get( '/users/me/pops' );
				$machines = $response;
				delete_option( 'easypack_cache_machines_crossborder' );
				add_option( 'easypack_cache_machines_crossborder', $machines, '', false );
				update_option( 'easypack_cache_machines_crossborder_time', time()+$this->cache_period  );
			}
			catch ( Exception $e ) {
				if ( $throw_excetion ) throw $e;
				if ( ! $machines ) {
					$machines = array();
				}
			}
		}
		return $machines;
	}


	public function machines_options( $country ) {
		$machines_options = get_option( 'easypack_cache_machines_crossborder_options_' . $country );

		if ( ! $machines_options || intval( get_option( 'easypack_cache_machines_crossborder_time' ) ) < time() ) {
			try {
				$machines = $this->machines( true );
				$machines_options = array();
				foreach ( $machines as $machine ) {
					if ( $machine['country_code'] == $country ) {
						$machines_options[$machine['id']] = '[' . $machine['name'] . '] ' . $machine['address']['street'] . ' ' . $machine['address']['building_number'] . ', ' . $machine['address']['zip_code'] . ' ' . $machine['address']['city'];
					}
				}
				delete_option( 'easypack_cache_machines_crossborder_options_' . $country );
				add_option( 'easypack_cache_machines_crossborder_options_' . $country, $machines_options, '', false );
			}
			catch ( Exception $e ) {
				if ( ! $machines_options ) {
					$machine_options = array( '9' => $e->getMessage() );
				}
			}
		}

		return $machines_options;
	}

	public function get_machine_by_name( $name, $country ) {
		$machines = $this->machines();
		foreach ( $machines as $machine ) {
			if ( $machine['name'] == $name && $machine['country_code'] == $country )
				return $machine;
		}
		return false;
	}

	public function shipments( $args ) {
		$response = $this->post( '/shipments' , $args);
		return $response;
	}

	public function shipment( $id ) {
		$response = $this->get( '/shipments/' . $id );
		return $response;
	}

	public function pdf_label( $id, $get_content = false ) {
		$response = $this->get( '/shipments/' . $id . '/pdf-labels' );
		if ( $get_content ) {
			$response = wp_remote_get( $response[0]['pdf_url'] );
			if ( is_wp_error( $response )) {
				throw new Exception( $response->get_error_message() );
			}
			else {
				return $response['body'];
			}
		}
		return $response;
	}

	public function manifest( $args ) {
		$response = $this->post( '/manifests', $args );
		return $response;
	}

	public function geowidget_keys() {
		$keys = get_option( 'easypack_crossborder_geowidget_keys', false );
		if ( ! $keys ) {
			$data = 'login=' . urlencode( $this->login ) . '&password=' . urlencode( $this->password );
			$args = array(
						'timeout' 	=> 30,
						'headers' 	=> array(
											'Content-Type' 		=> 'application/x-www-form-urlencoded',
											'Content-Length'	=> strlen($data)
						),
						'body'		=>	$data,
			);
			$response = wp_remote_post( 'http://widget-xborder-inpost.sheepla.com/get_keys', $args );
			if ( is_wp_error( $response ) ) {
				throw new Exception( $response->get_error_message() );
			}
			else {
				$keys = json_decode( $response['body'], true );
				update_option( 'easypack_crossborder_geowidget_keys', $keys );
			}
		}
		return $keys;
	}

}

function CrossBorder_API() {
	return CrossBorder_API::CrossBorder_API();
}

endif;