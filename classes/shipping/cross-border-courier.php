<?php
/**
 * EasyPack Shipping Method Cross Border Courier
 *
 * @author      WPDesk
 * @category    Admin
 * @package     EasyPack/Admin
 * @version     2.1.0
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( ! class_exists( 'EasyPack_Shippng_Cross_Border_Courier' ) ) {
	class EasyPack_Shippng_Cross_Border_Courier extends EasyPack_Shippng_Cross_Border_Parcel_Machines {

		/**
		 * Constructor for shipping class
		 *
		 * @access public
		 * @return void
		 */
		public function __construct() {
			$this->id              	= 'easypack_cross_border_courier';
			$this->method_title     = __( 'Cross Border Courier', EasyPack::$text_domain );
			$this->init();

			add_action( 'woocommerce_update_options_shipping_' . $this->id, array( $this, 'process_admin_options' ) );

		}

			/**
		 * Init your settings
		 *
		 *
		 * @access public
		 * @return void
		 */
		function init() {

			parent::init();

			remove_action( 'woocommerce_review_order_after_shipping', array( $this, 'woocommerce_review_order_after_shipping') );

			remove_action( 'woocommerce_checkout_update_order_meta', array( $this, 'woocommerce_checkout_update_order_meta') );

			remove_action( 'woocommerce_checkout_process', array($this, 'woocommerce_checkout_process' ) );

		}

		public function rate_sort( $a, $b ) {
			return strcmp( $a['country_name'], $b['country_name'] );
		}

		public function generate_rates_html( $key, $data )
		{
			$rates = get_option('woocommerce_' . $this->id . '_rates', array() );
			ob_start();
			$routes = CrossBorder_API()->me_routes();
			$parcel_routes = array();
			foreach ( $routes as $route ) {
				if ( strtoupper( $route['recipient_country_code'] ) != strtoupper( str_replace( 'test-', '', get_option( 'easypack_api_country', 'pl' ) ) ) ) {
					foreach ( $route['routes'] as $method ) {
						if ( $method['recipient_method'] == 'ToDoor' ) {
							$parcel_routes[$route['recipient_country_code']] = $route['recipient_country_code'];
						}
					}
				}
			}
			foreach ( $rates as $country => $rate ) {
				if ( isset( $parcel_routes[$country] ) ) {
					unset( $parcel_routes[$country] );
				}
				else {
					unset( $rates[$country] );
				}
			}
			foreach ( $parcel_routes as $parcel_route ) {
				$rates[$parcel_route] = array( 'kg_1' => '', 'kg_2' => '', 'kg_5' => '', 'kg_10' => '', 'kg_15' => '', 'kg_20' => '' );
			}
			$countries = WC()->countries->get_countries();
			foreach ( $rates as $country => $rate ) {
				$rates[$country]['country_name'] = $countries[$country];
				$rates[$country]['country_code'] = $country;
			}
			// remove Poland
			unset($rates['PL']);
			usort( $rates, array( $this, 'rate_sort' ) );
			include( 'views/html-rates-cross-border-courier.php' );
			return ob_get_clean();
		}

		public function init_form_fields() {
			$bank_accounts = array();
			//$bacs = new WC_Gateway_BACS();
			//$accounts = $bacs->account_details;
			$accounts = get_option( 'woocommerce_bacs_accounts', array() );
			foreach ( $accounts as $account )
			{
				$bank_accounts[$account['account_number']] = $account['account_number'] . ' ' . $account['bank_name'];
			}

			$settings = array(
					array(
							'title'     		=> __( 'General settings', EasyPack::$text_domain ),
							'type'     			=> 'title',
							'description'   	=> '',
							'id' 				=> 'section_general_settings',
					),
					'enabled' => array(
							'title' 			=> __( 'Enable/disable', EasyPack::$text_domain ),
							'type' 				=> 'checkbox',
							'label' 			=> __( 'Enable this shipping metod', EasyPack::$text_domain ),
							'default' 			=> 'no',
					),
					'title' => array(
							'title' 			=> __( 'Method title', EasyPack::$text_domain ),
							'type' 				=> 'text',
							'default'  			=> __( 'Cross Border Courier', EasyPack::$text_domain ),
							'desc_tip'			=> false
					),
					'free_shipping_cost' 		=> array(
							'title' 			=> __('Free shipping', EasyPack::$text_domain ),
							'type' 				=> 'number',
							'custom_attributes' => array(
													'step' => 'any',
													'min' => '0'
							),
							'default' 			=> '',
							'desc_tip' 			=> __('Enter the amount of the contract, from which shipping will be free (does not include virtual products).', EasyPack::$text_domain ),
							'placeholder' 		=> '0.00'
					),
					'flat_rate' => array(
							'title' 			=> __( 'Flat rate', EasyPack::$text_domain ),
							'type' 				=> 'checkbox',
							'label' 			=> __( 'Set a flat-rate shipping fee for the entire order.', EasyPack::$text_domain ),
							'class'				=> 'easypack_flat_rate',
							'default' 			=> 'yes',
					),
					'cost_per_order' => array(
							'title' 			=> __( 'Cost per order', EasyPack::$text_domain ),
							'type' 				=> 'number',
							'custom_attributes' => array(
													'step' => 'any',
													'min' => '0'
							),
							'class'				=> 'easypack_cost_per_order',
							'default' 			=> '',
							'desc_tip' 			=> __( 'Set a flat-rate shipping for all orders.', EasyPack::$text_domain ),
							'placeholder' 		=> '0.00'
					),
					'rates' => array(
							'title' 			=> __('Rates table', EasyPack::$text_domain ),
							'type' 				=> 'rates',
							'default' 			=> '',
							'desc_tip' 			=> 'The default pricing information for your account is below the edit field. You can edit and set your own prices that will be seen when the customer is placing the order.',
					),

			);
			$this->form_fields = $settings;
		}


		/**
		 * @param unknown $package
		 *
		 */
		public function calculate_shipping_table_rate( $package ) {
			$rates = get_option('woocommerce_' . $this->id . '_rates', array() );
			$cart = WC()->cart;
			$value = 0;
			$weight = $this->package_weight( $package['contents'] );
			foreach ( $rates as $country => $rate ) {
				$cost = false;
				if ( $package['destination']['country'] == $country ) {
					if ( $weight <= 25 ) {
						if ( $weight <= 1 ) {
							if ( isset( $rate['kg_1'] ) && trim( $rate['kg_1'] ) != '' ) {
								$cost = floatval( $rate['kg_1'] );
							}
						}
						else if ( $weight <= 2 ) {
							if ( isset( $rate['kg_2'] ) && trim( $rate['kg_2'] ) != '' ) {
								$cost = floatval( $rate['kg_2'] );
							}
						}
						else if ( $weight <= 5 ) {
							if ( isset( $rate['kg_5'] ) && trim( $rate['kg_5'] ) != '' ) {
								$cost = floatval( $rate['kg_5'] );
							}
						}
						else if ( $weight <= 10 ) {
							if ( isset( $rate['kg_10'] ) && trim( $rate['kg_10'] ) != '' ) {
								$cost = floatval( $rate['kg_10'] );
							}
						}
						else if ( $weight <= 15 ) {
							if ( isset( $rate['kg_15'] ) && trim( $rate['kg_15'] ) != '' ) {
								$cost = floatval( $rate['kg_15'] );
							}
						}
						else if ( $weight <= 20 ) {
							if ( isset( $rate['kg_20'] ) && trim( $rate['kg_20'] ) != '' ) {
								$cost = floatval( $rate['kg_20'] );
							}
						}
						else if ( $weight <= 25 ) {
							if ( isset( $rate['kg_25'] ) && trim( $rate['kg_25'] ) != '' ) {
								$cost = floatval( $rate['kg_25'] );
							}
						}
					}
				}
				if	( $cost !== false )
				{
					$add_rate = array(
							'id' 		=> $this->id,
							'label' 	=> $this->title,
							'cost' 		=> $cost,
					);
					$this->add_rate( $add_rate );
					return;
				}
			}
		}

		public function save_post( $post_id ) {
			// Check if our nonce is set.
			if ( ! isset( $_POST['easypack_box_data_crossborder_courier'] ) ) {
				return;
			}
			// Verify that the nonce is valid.
			if ( ! wp_verify_nonce( $_POST['easypack_box_data_crossborder_courier'], 'easypack_box_data_crossborder_courier' ) ) {
				return;
			}
			// If this is an autosave, our form has not been submitted, so we don't want to do anything.
			if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
				return;
			}

			$status = get_post_meta( $post_id, '_easypack_status', true );
			if ( $status == '' ) $status = 'new';

			if ( $status == 'new' ) {

				$parcels = $_POST['parcel'];
				$easypack_pacels = array();
				foreach ( $parcels as $parcel )
				{
					$parcel_data = array( 'package_weight' => $parcel['weight'] );
					$parcel_data['package_width'] = $parcel['width'];
					$parcel_data['package_height'] = $parcel['height'];
					$parcel_data['package_length'] = $parcel['length'];
					$easypack_pacels[] = $parcel_data;
				}
				update_post_meta( $post_id, '_easypack_parcels', $easypack_pacels );

				$easypack_send_method = $_POST['easypack_send_method'];
				update_post_meta( $post_id, '_easypack_send_method', $easypack_send_method );
			}

		}


		public function add_meta_boxes( $post_type, $post ) {
			if ( $post->post_type == 'shop_order' )
			{
				$order = wc_get_order( $post->ID );
				if ( $order->has_shipping_method($this->id) ) {
					add_meta_box( 'easypack_parcel_machines', __('InPost', EasyPack::$text_domain ) . $this->get_logo(), array( $this, 'order_metabox' ), 'shop_order', 'side', 'default' );
				}
			}
		}

		public function order_metabox( $post ) {
			self::order_metabox_content( $post );
		}

		public static function order_metabox_content( $post, $output = true ) {
			if ( ! $output ) ob_start();

			$order_id = $post->ID;

			$order = wc_get_order( $order_id );

			$status = get_post_meta( $order_id, '_easypack_status', true );
			if ( $status == '' ) $status = 'new';

			$parcel_machines = CrossBorder_API()->machines_options( $order->shipping_country );

			$parcel_machine_id = get_post_meta( $post->ID, '_parcel_machine_id', true );
			$parcels = get_post_meta( $post->ID, '_easypack_parcels', true );
			$package_weights = EasyPack()->get_package_weights_courier();
			if ( $parcels == '' ) {
				$order_weight = EasyPack_Helper()->get_order_weight( $order );
				$weight = EasyPack_Helper()->get_weight_option( $order_weight, $package_weights );
				$parcels = array();
				$parcel = array( 'package_weight' => $weight );
				$parcel['package_width'] = get_option( 'easypack_package_width_courier', 60 );
				$parcel['package_height'] = get_option( 'easypack_package_height_courier', 40 );
				$parcel['package_length'] = get_option( 'easypack_package_length_courier', 40 );
				$parcels[] = $parcel;
			}
			$send_methods = array( 'parcel_machine' => __( 'Parcel locker', EasyPack::$text_domain ), 'courier' => __( 'Courier', EasyPack::$text_domain ) );
			$send_method = get_post_meta( $post->ID, '_easypack_send_method', true );
			if ( $send_method == '' ) {
				$send_method = get_option( 'easypack_default_send_method', 'parcel_machine' );
			}

			$stickers_url = site_url('?easypack_download=1&easypack_parcel_machines_stickers=1&cross_border=1&order_id=' . $order_id . '&security=' . wp_create_nonce( 'easypack_nonce' ) );

			$tracking_url = false;
			if ( $status != 'new' ) {
				$tracking_url = EasyPack_Helper()->get_tracking_url('pl-cb');
				foreach ( $parcels as $parcel ) {
					$tracking_url .= $parcel['crossborder_data']['tracking_number'] . ',';
				}
				$tracking_url = trim( $tracking_url, ',' );
			}

			$disabled = false;
			if ( $status != 'new' ) $disabled = true;

			echo '<input id="easypack_status" type="hidden" name="easypack_status" value="' . $status . '">';

			include( 'views/html-order-matabox-crossborder-courier.php' );

			wp_nonce_field( 'easypack_box_data_crossborder_courier', 'easypack_box_data_crossborder_courier' );

			if ( ! $output )
			{
				$out = ob_get_clean();
				return $out;
			}
		}

		public static function ajax_create_package( $courier = true ) {
			parent::ajax_create_package( $courier );
		}

	}
}
