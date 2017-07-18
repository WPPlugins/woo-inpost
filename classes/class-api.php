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

	public function __construct()
	{
		$this->login 	= get_option( 'easypack_login' );
		$this->token 	= get_option( 'easypack_token' );
		$this->api_url 	= get_option( 'easypack_api_url' );
	}

	public static function EasyPack_API() {
		if (self::$instance === null) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	public function clear_cache() {
		delete_option( 'easypack_cache_machines' );
		delete_option( 'easypack_cache_machines_options' );
		delete_option( 'easypack_cache_machines_cod_options' );
		delete_option( 'easypack_cache_machines_time' );
	}

	function get_error( $errors ) {
		$ret = '';
		if ( is_array( $errors ) ) {
			$first = true;
			foreach ( $errors as $key => $error ) {
				if ( ! $first ) {
					$ret .= ', ';
				}
				else {
					$ret = ': ';
				}
				$ret .= $key;
				if ( is_array( $error ) ) {
					$ret .= ': ';
					foreach ($error as $e ) {
						$ret .= $e;
					}
				}
				else {
					$ret .= ': ' . $error;
				}
				$first = false;
			}
		}
		else {
			$ret = $errors;
		}
		return $ret;
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
			if ( isset( $ret['status_code']) ) {
				$errors = '';
				if ( isset( $ret['errors'] ) ) {
					$errors = $this->get_error( $ret['errors'] );
				}
				throw new Exception( $ret['message'] . $errors, $ret['status_code'] );
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
			}
			else {
				$ret = json_decode( $response['body'], true );
				if ( isset( $ret['status_code']) ) {
					$errors = '';
					if ( isset( $ret['errors'] ) ) {
						$errors = ': ' . implode(', ', $ret['errors'] );
					}
					throw new Exception( $ret['message'] . $errors, $ret['status_code'] );
				}
			}
			return $ret;
		}

	}

	public function ping() {

	}

	public function machines( $throw_excetion = false ) {
		$machines = get_option( 'easypack_cache_machines' );

		if ( ! $machines || intval( get_option( 'easypack_cache_machines_time' ) ) < time() ) {
			try {
				$response = $this->get( '/v4/machines' );
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
					$machines_options[$machine['id']] = '[' . $machine['id'] . '] ' . $machine['address_str'];
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
/* machine is cod? */
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
		$response = $this->get( '/v4/customers/' . $this->login );
		$customer = $response;
		return $customer;
	}

	public function update_customer( $args ) {
		$response = $this->put( '/v4/customers/' . $this->login, $args );
		$customer = $response;
		return $customer;
	}

	public function dispatch_point( $dispatch_point_name ) {
		$response = $this->get( '/v4/dispatch_points/' . $dispatch_point_name );
		$dispatch_point = $response;
		return $dispatch_point;
	}

	public function customer_dispatch_points() {
		$response = $this->get( '/v4/customers/' . $this->login . '/dispatch_points' );
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
			$response = $this->put( '/v4/dispatch_points/' . $args['name'], $args );
		}
		catch ( Exception $e ) {
			if ( $e->getCode() == '404' ) { /* dispatch point not found - create new */
				$response = $this->post( '/v4/customers/' . $this->login . '/dispatch_points', $args );
				update_option('easypack_default_dispatch_point', $args['name']);
			}
			else {
				throw $e;
			}
		}
	}

	public function customer_parcel_create( $args ) {
		$response = $this->post( '/v4/customers/' . $this->login . '/parcels', $args );
		return $response;
	}

	public function customer_parcel_cancel( $parcel_id ) {
		$response = $this->post( '/v4/parcels/' . $parcel_id . '/cancel', $args );
		return $response;
	}

	public function customer_parcel_pay( $parcel_id ) {
		$response = $this->post( '/v4/parcels/' . $parcel_id . '/pay', $args );
		return $response;
	}

	public function customer_parcel_sticker( $parcel_id ) {
		$response = $this->get( '/v4/parcels/' . $parcel_id . '/sticker', $args );
		return $response;
	}

}

function EasyPack_API() {
	return EasyPack_API::EasyPack_API();
}

endif;