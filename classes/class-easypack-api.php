<?php
/**
 * EasyPack API
*
* @author      WPDesk
* @category    Admin
* @package     EasyPack
* @version     2.1.0
*/

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( ! class_exists( 'EasyPack_API' ) ) :

class EasyPack_API {

	protected static $instance;

	protected $login;
	protected $token;

	protected $cache_period = DAY_IN_SECONDS;

	public function __construct() {
		$this->login 	= get_option( 'easypack_login' );
		$this->token 	= get_option( 'easypack_token' );
		$this->api_url 	= $this->make_api_url( get_option( 'easypack_api_url' ) );
	}

	public static function EasyPack_API() {
		if (self::$instance === null) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	public function make_api_url( $url ) {
		$url = untrailingslashit( $url );
		$parsed_url = parse_url( $url );
		if ( !isset( $parsed_url['path'] ) || trim( $parsed_url['path'] ) == '' ) {
			$url .= '/v4';
		}
		return $url;
	}

	public function clear_cache() {
		$this->login 	= get_option( 'easypack_login' );
		$this->token 	= get_option( 'easypack_token' );
		$this->api_url 	= $this->make_api_url( get_option( 'easypack_api_url' ) );
		//
		delete_option( 'easypack_cache_machines' );
		delete_option( 'easypack_cache_machines_options' );
		delete_option( 'easypack_cache_machines_cod_options' );
		delete_option( 'easypack_cache_machines_time' );
		//delete_option( 'easypack_cod_enabled' );
	}

	function translate_error( $error ) {
		$errors = array(
			'receiver_email' 			=> __( 'Recipient e-mail', EasyPack::$text_domain ),
			'receiver_phone' 			=> __( 'Recipient phone', EasyPack::$text_domain ),
			'address' 					=> __( 'Address', EasyPack::$text_domain ),
			'phone' 					=> __( 'Phone', EasyPack::$text_domain ),
			'email' 					=> __( 'Email', EasyPack::$text_domain ),
			'post_code' 				=> __( 'Post code', EasyPack::$text_domain ),
			'postal_code' 				=> __( 'Post code', EasyPack::$text_domain ),
			'default_machine_id'		=> __( 'Default parcel locker', EasyPack::$text_domain ),

			'not_an_email' 				=> __( 'not valid', EasyPack::$text_domain ),
			'invalid' 					=> __( 'invalid', EasyPack::$text_domain ),
			'not_found'					=> __( 'not found', EasyPack::$text_domain ),
			'invalid_format'			=> __( 'invalid format', EasyPack::$text_domain ),
			'required, invalid_format'	=> __( 'required', EasyPack::$text_domain ),
			'too_many_characters'		=> __( 'too many characters', EasyPack::$text_domain ),

			'You have not enough funds to pay for this parcel' => __( 'Can not create sticker. You have not enough funds to pay for this parcel', EasyPack::$text_domain ),

			'Access to this resource is forbidden' => __( 'Invalid login or token', EasyPack::$text_domain ),
			'Sorry, access to this resource is forbidden' => __( 'Invalid login', EasyPack::$text_domain ),
			'Token is missing or invalid' => __( 'Token is missing or invalid', EasyPack::$text_domain ),
			'Box machine name cannot be empty' => __( 'Parcel Locker is empty. Please fill in this field.', EasyPack::$text_domain ),
			'Default parcel machine' => __( 'Default send parcel locker: ', EasyPack::$text_domain ),
			'The transaction can not be completed due to the balance of your account' => __( 'The transaction can not be completed due to the balance of your account', EasyPack::$text_domain ),
		);

		if ( isset( $errors[$error] ) ) return $errors[$error];
		return $error;
	}

	function get_error_array( $errors ) {
		$ret = '';
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
		if ( $ret != '' ) {
			$ret = '<ul>' . $ret . '</ul>';
		}
		return trim( trim( $ret ), ',' );
	}

	function get_error( $errors ) {
		$ret = '';
		foreach ( $errors as $key => $error ) {
			$ret .= '<li>' . $this->translate_error( $key ) . ': ';
			foreach ( $error as $error_detail ) {
				if ( is_array( $error_detail ) ) {
					$ret .= $this->get_error( $error_detail);
				}
				else {
					$ret .= $this->translate_error( $error_detail ) . ', ';
				}
			}
			$ret = trim( trim( $ret ) , ',');
			$ret .= '</li>';
		}
		if ( $ret != '' ) {
			$ret = '<ul class="easypack_error">' . $ret . '</ul>';
		}
		return trim( trim( $ret ), ',' );
	}

	public function post( $path, $args = array(), $method = 'POST' ) {
		$url = untrailingslashit( $this->api_url ) . str_replace( ' ', '%20', str_replace( '@', '%40', $path ) );
		$request_args = array( 'timeout' => 30, 'method' => $method );

		$request_args['headers'] = array( 'Authorization' => 'Bearer ' . $this->token, 'Content-Type' => 'application/json' );

		$request_args['body'] = $args;
		$request_args['body'] = json_encode($args);

		$response = wp_remote_post( $url, $request_args );
		if ( is_wp_error( $response ) ) {
			throw new Exception( $response->get_error_message() );
		}
		else {
			$ret = json_decode( $response['body'], true );
			if ( ! is_array( $ret ) ) {
				throw new Exception( __('Bad API response. Check API URL', EasyPack::$text_domain ), 503 );
			}
			else {
				if ( isset( $ret['status_code']) ) {
					$errors = '';
					if ( isset( $ret['errors'] ) && count( $ret['errors'] ) ) {
						if ( is_array( $ret['errors'] ) ) {
							if ( count( $ret['errors'] ) )
								$errors = $this->get_error( $ret['errors'] );
						}
						else {
							$errors = ': ' . $ret['errors'];
						}
					}
					else {
						$errors = $this->translate_error( $ret['message'] );
					}
					throw new Exception( $errors, $ret['status_code'] );
				}
			}
			return $ret;
		}

	}

	public function put( $path, $args = array(), $method = 'PUT' ) {
		return $this->post( $path, $args, 'PUT' );
	}

	public function get( $path, $args = array() ) {
		$url = untrailingslashit( $this->api_url ) . str_replace( ' ', '%20', str_replace( '@', '%40', $path ) );
		$request_args = array( 'timeout' => 30 );

		$request_args['headers'] = array( 'Authorization' => 'Bearer ' . $this->token, 'Content-Type' => 'application/json' );

		$response = wp_remote_get( $url, $request_args );
		if ( is_wp_error( $response ) ) {
			throw new Exception( $response->get_error_message() );
		}
		else {
			if ( $response['headers']['content-type'] == 'application/pdf' ) {
				$ret = $response['body'];
				return $ret;
			}
			else {
				$ret = json_decode( $response['body'], true );
				if ( ! is_array( $ret ) ) {
					throw new Exception( __('Bad API response. Check API URL', EasyPack::$text_domain ), 503 );
				}
				else {
					if ( isset( $ret['status_code']) ) {
						$errors = '';
						if ( isset( $ret['errors'] ) && count( $ret['errors'] ) ) {
							if ( is_array( $ret['errors'] ) ) {
								if ( count( $ret['errors'] ) )
									$errors = $this->get_error( $ret['errors'] );
							}
							else {
								$errors = ': ' . $ret['errors'];
							}
						}
						else {
							$errors = $this->translate_error( $ret['message'] );
							if ( $errors == '-' ) {
								$res = $response['response'];
								$errors = $this->translate_error( $res['message'] );
								//$errors = $this->translate_error( $res['message'] . ' ' . $url );
							}
						}
						throw new Exception( $errors, $ret['status_code'] );
					}
				}
				return $ret;
			}
		}

	}

	public function ping() {
		$this->parcels();
	}

	public function machines( $throw_excetion = false ) {
		$machines = get_option( 'easypack_cache_machines' );

		if ( ! $machines || intval( get_option( 'easypack_cache_machines_time' ) ) < time() ) {
			try {
				$response = $this->get( '/machines' );
				$machines = $response['_embedded']['machines'];
				delete_option( 'easypack_cache_machines' );
				add_option( 'easypack_cache_machines', $machines, '', false );
				update_option( 'easypack_cache_machines_time', time()+$this->cache_period  );
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

	public function machines_options() {
		$machines_options = get_option( 'easypack_cache_machines_options' );

		if ( ! $machines_options || intval( get_option( 'easypack_cache_machines_time' ) ) < time() ) {
			try {
				$machines = $this->machines( true );
				$machines_options = array();
				foreach ( $machines as $machine ) {
					//$machines_options[$machine['id']] = '[' . $machine['id'] . '] ' . $machine['address_str'];
					//$machines_options[$machine['id']] = $machine['address_str'] . ', ' . $machine['id'];
					if ( $this->api_country() == 'CA' ) {
						$machines_options[$machine['id']] = $machine['address']['address1'];
						if ( isset( $machine['address']['address2'] ) )
							$machines_options[$machine['id']] .= ', ' . $machine['address']['address2'];
						if ( isset( $machine['address']['city'] ) )
							$machines_options[$machine['id']] .= ', ' . $machine['address']['city'];
						if ( isset( $machine['address']['province'] ) )
							$machines_options[$machine['id']] .= ', ' . $machine['address']['province'];
						if ( isset( $machine['address']['postal_code'] ) )
							$machines_options[$machine['id']] .= ', ' . $machine['address']['postal_code'];
					}
					else {
						$machines_options[$machine['id']] = $machine['address']['street'];
						if ( isset( $machine['address']['building_no'] ) )
							$machines_options[$machine['id']] .= ' ' . $machine['address']['building_no'];
						if ( isset( $machine['address']['flat_no'] ) )
							$machines_options[$machine['id']] .= '/' . $machine['address']['flat_no'];
						if ( isset( $machine['address']['city'] ) )
							$machines_options[$machine['id']] .= ', ' . $machine['address']['city'];
						if ( isset( $machine['address']['province'] ) )
							$machines_options[$machine['id']] .= ', ' . $machine['address']['province'];
						if ( isset( $machine['address']['post_code'] ) )
							$machines_options[$machine['id']] .= ', ' . $machine['address']['post_code'];
					}
					$machines_options[$machine['id']] .= ', ' . $machine['id'];
				}
				delete_option( 'easypack_cache_machines_options' );
				add_option( 'easypack_cache_machines_options', $machines_options, '', false );
			}
			catch ( Exception $e ) {
				if ( ! $machines_options ) {
					$machine_options = array( '9' => $e->getMessage() );
				}
			}
		}

		return $machines_options;
	}

	public function machines_cod_options() {
		$machines_cod_options = get_option( 'easypack_cache_machines_cod_options' );

		if ( ! $machines_cod_options || intval( get_option( 'easypack_cache_machines_time' ) ) < time() ) {
			try {
				$machines = $this->machines( true );
				$machines_cod_options = array();
				foreach ( $machines as $machine ) {
					if ( $machine['payment_type'] != '0' ) /* 0 - payment not avaliable */
						$machines_cod_options[$machine['id']] = '[' . $machine['id'] . '] ' . $machine['address_str'];
				}
				delete_option( 'easypack_cache_machines_cod_options' );
				add_option( 'easypack_cache_machines_cod_options', $machines_cod_options, '', false );
			}
			catch ( Exception $e ) {
				if ( ! $machines_cod_options ) {
					$machine_cod_options = array( '9' => $e->getMessage() );
				}
			}
		}

		return $machines_cod_options;
	}

	public function customer() {
		$response = $this->get( '/customers/' . $this->login );
		$customer = $response;
		return $customer;
	}

	public function update_customer( $args ) {
		$response = $this->put( '/customers/' . $this->login, $args );
		$customer = $response;
		return $customer;
	}

	public function dispatch_point( $dispatch_point_name ) {
		$response = $this->get( '/dispatch_points/' . $dispatch_point_name );
		$dispatch_point = $response;
		return $dispatch_point;
	}

	public function customer_dispatch_points() {
		$response = $this->get( '/customers/' . $this->login . '/dispatch_points' );
		$dispatch_points = $response['_embedded']['dispatch_points'];
		while ( isset( $response['_links']['next'] ) && isset( $response['_links']['next']['href'] ) && $response['_links']['next']['href'] != '' ) {
			$response = $this->get( $response['_links']['next']['href'] );
			$dispatch_points = array_merge( $dispatch_points, $response['_embedded']['dispatch_points']);
		}
		return $dispatch_points;
	}

	public function customer_dispatch_points_options() {
		$customer_dispatch_points_options = array();
		try {
			$customer_dispatch_points = $this->customer_dispatch_points();
			foreach ( $customer_dispatch_points as $dispatch_point ) {
				$customer_dispatch_points_options[$dispatch_point['name']] = '[' . $dispatch_point['name'] . '] ' . $dispatch_point['address_str'];
			}
		}
		catch ( Exception $e ) {
			$customer_dispatch_points_options['-1'] = $e->getMessage();
		}

		return $customer_dispatch_points_options;
	}

	public function update_default_dispatch_point( $args ) {
		try {
			$dispatch_point = $this->dispatch_point( $args['name'] );
			/* update dispatch point */
			$response = $this->put( '/dispatch_points/' . $args['name'], $args );
		}
		catch ( Exception $e ) {
			if ( $e->getCode() == '404' ) { /* dispatch point not found - create new */
				$response = $this->post( '/customers/' . $this->login . '/dispatch_points', $args );
				update_option('easypack_default_dispatch_point', $args['name']);
			}
			else {
				throw $e;
			}
		}
	}

	public function dispatch_order( $dispatch_point, $args ) {
		$response = $this->post( '/dispatch_points/' . $dispatch_point . '/dispatch_orders', $args );
		return $response;
	}

	public function parcels() {
		$response = $this->get( '/customers/' . $this->login . '/parcels' );
		return $response;
	}

	public function customer_parcel_create( $args ) {
		$response = $this->post( '/customers/' . $this->login . '/parcels', $args );
		return $response;
	}

	public function customer_parcel_cancel( $parcel_id ) {
		$response = $this->post( '/parcels/' . $parcel_id . '/cancel', $args );
		return $response;
	}

	public function customer_parcel_pay( $parcel_id ) {
		$response = $this->post( '/parcels/' . $parcel_id . '/pay', $args );
		return $response;
	}

	public function customer_parcel_sticker( $parcel_id ) {
		if ( $this->api_country() == 'IT' || $this->api_country() == 'FR' ) {
			$response = $this->get( '/parcels/' . $parcel_id . '/sticker?type=A6P', $args );
		}
		else {
			$response = $this->get( '/parcels/' . $parcel_id . '/sticker', $args );
		}
		return $response;
	}

	public function customer_parcel( $parcel_id ) {
		$response = $this->get( '/parcels/' . $parcel_id );
		$parcel = $response;
		return $parcel;
	}

	public function customer_pricelists() {
		$response = $this->get( '/customers/' . $this->login . '/pricelists' );
		return $response;
	}

	public function dispatch_points_dispatch_orders( $dispatch_point, $args ) {
		$response = $this->put( '/dispatch_point/' . $dispatch_point . '/dispatch_orders' );
		return $response;
	}

	public function api_country() {
		return strtoupper( str_replace( 'test-', '', get_option( 'easypack_api_country', '--' ) ) );
	}

	public function validate_phone( $phone ) {

		if ( $this->api_country() == 'IT' ) {
			if ( preg_match("/\A3\d{8,9}\z/", $phone) ) {
				return true;
			}
			else {
				return __('Invalid phone number. Valid phone number must contains 9 or 10 digits and must begins with 3.', EasyPack::$text_domain);
			}
		}
		if ( $this->api_country() == 'FR' ) {
			//if ( preg_match("/\A(6|7)\d{8}\z/", $phone) ) {
			if ( preg_match("/\A0?(6|7)\d{8}\z/", $phone) ) {
				return true;
			}
			else {
				return __('Invalid phone number. Valid phone number must contains 9 digits and must begins with 6 or 7.', EasyPack::$text_domain);
			}
		}
		if ( $this->api_country() == 'CA' ) {
			if ( preg_match("/\A\d{10}\z/", $phone) ) {
				return true;
			}
			else {
				return __('Invalid phone number. Valid phone number must contains 10 digits.', EasyPack::$text_domain);
			}
		}
		if ( $this->api_country() == 'PL' ) {
			if ( preg_match("/\A[1-9]\d{8}\z/", $phone) ) {
				return true;
			}
			else {
				return __('Invalid phone number. Valid phone number must contains 9 digits and must not begins with 0.', EasyPack::$text_domain);
			}
		}
		return __('Invalid phone number.', EasyPack::$text_domain);

	}


}

function EasyPack_API() {
	return EasyPack_API::EasyPack_API();
}

endif;