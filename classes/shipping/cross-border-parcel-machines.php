<?php
/**
 * EasyPack Shipping Method Cross Border Parcel Machines
 *
 * @author      WPDesk
 * @category    Admin
 * @package     EasyPack/Admin
 * @version     2.1.0
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( ! class_exists( 'EasyPack_Shippng_Cross_Border_Parcel_Machines' ) ) {
	class EasyPack_Shippng_Cross_Border_Parcel_Machines extends EasyPack_Shippng_Parcel_Machines {

		/**
		 * Constructor for shipping class
		 *
		 * @access public
		 * @return void
		 */
		public function __construct() {
			$this->id              	= 'easypack_cross_border_parcel_machines';
			$this->method_title     = __( 'Cross Border Parcel Locker', EasyPack::$text_domain );
			$this->init();

			add_action( 'woocommerce_update_options_shipping_' . $this->id, array( $this, 'process_admin_options' ) );

			add_action( 'wp_enqueue_scripts', array( $this, 'wp_enqueue_scripts' ), 1 );

		}

		public function admin_options()
		{
			?>
			<table class="form-table">
				<?php $this->generate_settings_html(); ?>
			</table>
			<script type="text/javascript">
				function display_rates() {
					if ( jQuery('.easypack_flat_rate').prop('checked') ) {
						jQuery('.easypack_cost_per_order').closest('tr').css('display','table-row');
						jQuery('.easypack_rates').closest('tr').css('display','none');
					}
					else {
						jQuery('.easypack_cost_per_order').closest('tr').css('display','none');
						jQuery('.easypack_rates').closest('tr').css('display','table-row');
					}
				}
				jQuery('.easypack_flat_rate').change(function() {
					display_rates();
				});
				display_rates();
			</script>
			<?php
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
						if ( $method['recipient_method'] == 'ToParcelMachine' ) {
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
			include( 'views/html-rates-cross-border-parcel-machines.php' );
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
							'description'     	=> '',
							'id'	 			=> 'section_general_settings',
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
							'default'  			=> __( 'Cross Border Parcel Locker', EasyPack::$text_domain ),
							'desc_tip'			=> false
					),
					'free_shipping_cost' => array(
							'title' 			=> __('Free shipping', EasyPack::$text_domain ),
							'type' 				=> 'number',
							'custom_attributes' => array(
													'step' 	=> 'any',
													'min' 	=> '0'
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
													'step' 	=> 'any',
													'min' 	=> '0'
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
					if ( $weight <= 20 ) {
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

		public function calculate_shipping( $package = array() ) {
			$available = false;
			$rates = get_option('woocommerce_' . $this->id . '_rates', array() );
			foreach ( $rates as $country => $rate ) {
				if ( $package['destination']['country'] == $country ) {
					$available = true;
				}
			}
			if ( $available ) {
				if ( ! $this->calculate_shipping_free_shipping( $package ) ) {
					if ( ! $this->calculate_shipping_flat( $package ) )	{
						$this->calculate_shipping_table_rate( $package );
					}
				}
			}
		}


		public function woocommerce_review_order_after_shipping() {
			if ( in_array( $this->id, WC()->session->get('chosen_shipping_methods') ) ) {
				$shipping_country = WC()->session->customer['shipping_country'];
				if ( isset( $_REQUEST['country'] ) && trim( $_REQUEST['country'] ) != '' ) {
					$shipping_country = $_REQUEST['country'];
				}
				if ( isset( $_REQUEST['s_country'] ) && trim( $_REQUEST['s_country'] ) != '' ) {
					$shipping_country = $_REQUEST['s_country'];
				}
				$parcel_machines = CrossBorder_API()->machines_options( $shipping_country );
				$args = array( 'parcel_machines' => $parcel_machines );
				$args['parcel_machine_id'] = WC()->session->get( 'parcel_machine_id' );

				$geowidget_src = false;
				try {
				 	$geowidget_keys = CrossBorder_API()->geowidget_keys();
				 	$sheepla_api_key = $geowidget_keys['public_key'];
				 	$geowidget_src = '//widget-xborder-inpost.sheepla.com/js/SheeplaLib.js';
				 	$geowidget_css = '//widget-xborder-inpost.sheepla.com/css/SheeplaCrossBorder.css';
				}
				catch ( Exception $e ) {
				}

				$args['geowidget_src'] = $geowidget_src;
				$args['geowidget_css'] = $geowidget_css;
				$args['sheepla_api_key'] = $sheepla_api_key;

				wc_get_template( 'checkout/crossborder-review-order-after-shipping.php', $args, '', plugin_dir_path( EasyPack()->getPluginFilePath() ) . 'templates/' );
			}
		}

		public function woocommerce_checkout_process() {
			if ( in_array( $this->id, WC()->session->get('chosen_shipping_methods') ) ) {
				if ( empty( $_POST['parcel_machine_id'] ) ) {
					wc_add_notice( __( 'Parcel locker must be choosen.', EasyPack::$text_domain ), 'error' );
				}
				else {
					WC()->session->set( 'parcel_machine_id', $_POST['parcel_machine_id'] );
				}
			}
		}

		public function woocommerce_checkout_update_order_meta( $order_id ) {
			if ($_POST['parcel_machine_id']) {
				update_post_meta($order_id, '_parcel_machine_id', esc_attr($_POST['parcel_machine_id']));
				$weight = WC()->cart->cart_contents_weight;
				update_post_meta( $order_id, '_cart_weight', $weight );
			}
		}


		public function save_post( $post_id ) {
			// Check if our nonce is set.
			if ( ! isset( $_POST['easypack_box_data_crossborder_parcel_machines'] ) ) {
				return;
			}
			// Verify that the nonce is valid.
			if ( ! wp_verify_nonce( $_POST['easypack_box_data_crossborder_parcel_machines'], 'easypack_box_data_crossborder_parcel_machines' ) ) {
				return;
			}
			// If this is an autosave, our form has not been submitted, so we don't want to do anything.
			if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
				return;
			}

			$status = get_post_meta( $post_id, '_easypack_status', true );
			if ( $status == '' ) $status = 'new';

			if ( $status == 'new' ) {

				$parcel_machine_id = $_POST['parcel_machine_id'];

				update_post_meta( $post_id, '_parcel_machine_id', $parcel_machine_id );

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
			$package_weights = EasyPack()->get_package_weights_parcel_machines();
			if ( $parcels == '' ) {
				$order_weight = EasyPack_Helper()->get_order_weight( $order );
				$weight = EasyPack_Helper()->get_weight_option( $order_weight, $package_weights );
				$parcels = array();
				$parcel = array( 'package_weight' => $weight );
				$parcel['package_width'] = get_option( 'easypack_package_width', 64 );
				$parcel['package_height'] = get_option( 'easypack_package_height', 41 );
				$parcel['package_length'] = get_option( 'easypack_package_length', 38 );
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

			include( 'views/html-order-matabox-crossborder-parcel-machines.php' );

			wp_nonce_field( 'easypack_box_data_crossborder_parcel_machines', 'easypack_box_data_crossborder_parcel_machines' );

			if ( ! $output )
			{
				$out = ob_get_clean();
				return $out;
			}
		}

		public static function ajax_create_package( $courier = false ) {
			$ret = array( 'status' => 'ok' );

			$order_id = $_POST['order_id'];
			$order = wc_get_order($order_id);
			$post = get_post( $order_id );

			$parcels = $_POST['parcels'];
			
			$parcel_machine_id = '';
			if ( isset( $_POST['parcel_machine_id'] ) ) {
				$parcel_machine_id = $_POST['parcel_machine_id'];
			}
			
			$send_method = $_POST['send_method'];

			$order_parcels = array();

			$total_amount = 0;

			foreach ( $parcels as $key => $parcel )
			{
				$country = strtoupper( str_replace( 'test-', '', get_option( 'easypack_api_country' ,'PL' ) ) );

				$args = array();

				$args['declared_weight'] = array( 'amount' => $parcel['weight'], 'unit' => 'kg' );
				
				$args['declared_dimensions'] = array( 'unit' => 'cm' );
				$args['declared_dimensions']['width'] = $parcel['width'];
				$args['declared_dimensions']['height'] = $parcel['height'];
				$args['declared_dimensions']['length'] = $parcel['length'];
				

				if ( $courier ) {
					update_option( 'easypack_package_width_courier', $parcel['width'] );
					update_option( 'easypack_package_height_courier', $parcel['height'] );
					update_option( 'easypack_package_length_courier', $parcel['length'] );
				}
				else {
					update_option( 'easypack_package_width', $parcel['width'] );
					update_option( 'easypack_package_height', $parcel['height'] );
					update_option( 'easypack_package_length', $parcel['length'] );
				}
				

				$args['recipient'] = array();
				$args['recipient']['country_code'] 	= $order->shipping_country;
				$args['recipient']['email'] 		= $order->billing_email;
				$args['recipient']['first_name'] 	= $order->shipping_first_name;
				$args['recipient']['last_name'] 	= $order->shipping_last_name;
				$args['recipient']['phone'] 		= $order->billing_phone;
				if ( ! $courier ) {
					$args['recipient']['pop'] 		= array( 'id' => $parcel_machine_id, 'size' => 'C' );
				}
				else {
					$args['recipient']['address'] 						= array();
					$args['recipient']['address']['zip_code'] 			= $order->shipping_postcode;
					$args['recipient']['address']['street'] 			= $order->shipping_address_1;
					$args['recipient']['address']['building_number'] 	= $order->shipping_address_2;
					$args['recipient']['address']['flat_number'] 		= '';
					$args['recipient']['address']['city'] 				= $order->shipping_city;
				}

				$args['sender'] = array();
				if ( $send_method == 'parcel_machine' ) {
					$sender_machine_id = false;
					$sender_machine = CrossBorder_API()->get_machine_by_name( get_option( 'easypack_default_machine_id' ), $country );
					if ( $sender_machine ) $sender_machine_id = $sender_machine['id'];
					$args['sender']['pop'] = array( 'id' => $sender_machine_id, 'size' => 'C' );
				}
				else {
					$args['sender']['address'] = array();
					$args['sender']['address']['zip_code'] 			= get_option( 'easypack_sender_post_code' );
					$args['sender']['address']['street'] 			= get_option( 'easypack_sender_city' );
					$args['sender']['address']['building_number'] 	= get_option( 'easypack_sender_building_no' );
					$args['sender']['address']['flat_number'] 		= get_option( 'easypack_sender_flat_no' );
					$args['sender']['address']['city'] 				= get_option( 'easypack_sender_city' );
				}
				$args['sender']['country_code'] = $country;
				$args['sender']['email'] 		= get_option( 'easypack_sender_email' );
				$args['sender']['company_name'] = get_option( 'easypack_sender_company_name' );
				$args['sender']['first_name'] 	= get_option( 'easypack_sender_first_name' );
				$args['sender']['last_name'] 	= get_option( 'easypack_sender_last_name' );
				$args['sender']['phone'] 		= get_option( 'easypack_sender_phone' );

				$package_status = 'ReadyToBeSent';
				try {
					$crossborder_data = CrossBorder_API()->shipments($args);
					if ( isset( $crossborder_data['id'] ) ) {
						$package_data = CrossBorder_API()->shipment( $crossborder_data['id'] );
						$package_status = $package_data['status']['code'];
						$order_parcels[] = array( 
								'package_weight' => $parcel['weight'], 
								'package_width' => $parcel['width'],
								'package_height' => $parcel['height'],
								'package_length' => $parcel['length'],
								'crossborder_data' => $package_data 							
						);
					}
					else {
						$ret['status'] = 'error';
						$ret['message'] = '';
						foreach ( $crossborder_data as $error ) {
							$ret['message'] .= $error['message'] . ', ';
						}
						$ret['message'] = trim( $ret['message'] );
						$ret['message'] = trim( $ret['message'], ',' );
						break;
					}
				}
				catch ( Exception $e ) {
					$ret['status'] = 'error';
					$ret['message'] = __( 'There are some errors. Please fix it:', EasyPack::$text_domain ) . $e->getMessage();
					break;
				}
			}
			if ( $ret['status'] == 'ok' ) {
				update_post_meta( $order_id, '_easypack_parcels', $order_parcels );
				update_post_meta( $order_id, '_easypack_status', $package_status );
				update_post_meta( $order_id, '_easypack_send_method', $send_method );
				$ret['content'] = self::order_metabox_content( $post, false );
			}

			echo json_encode( $ret );
			wp_die();
		}


		public static function ajax_processing() {
			$ret = array( 'status' => 'ok' );

			$order_id = $_POST['order_id'];
			$order = wc_get_order($order_id);
			$post = get_post( $order_id );

			$easypack_parcels = get_post_meta( $order_id, '_easypack_parcels', true );

			$package_status = 'Processing';

			if ( $easypack_parcels ) {
				foreach ( $easypack_parcels as $key => $parcel ) {
					$package_data = CrossBorder_API()->shipment( $parcel['crossborder_data']['id'] );
					$package_status = $package_data['status']['code'];
					$easypack_parcels[$key]['crossborder_data'] = $package_data;
					if ( $package_data['status']['code'] == 'Rejected' ) {
						unset( $easypack_parcels[$key]['crossborder_data'] );
						$ret['status'] = 'error';
						$ret['message'] = CrossBorder_API()->translate_error( $package_data['status']['comments'] );
					}
				}
				update_post_meta( $order_id, '_easypack_parcels', $easypack_parcels );
			}

			if ( $ret['status'] == 'ok' ) {
				$order->add_order_note( __( 'Shipment created', EasyPack::$text_domain ), false);
				update_post_meta( $order_id, '_easypack_status', $package_status );
				$ret['content'] = self::order_metabox_content( $post, false );
			}
			else {
				delete_post_meta( $order_id, '_easypack_status' );
				$ret['content'] = self::order_metabox_content( $post, false );
			}

			echo json_encode( $ret );
			wp_die();
		}

		public static function get_stickers() {

			$nonce = $_GET['security'];
			if ( ! wp_verify_nonce( $nonce, 'easypack_nonce' ) ) {
				echo __( 'Security check - bad nonce!', EasyPack::$text_domain );
				return;
			}

			$order_id = $_GET['order_id'];
			$order = wc_get_order($order_id);
			$post = get_post( $order_id );

			$status = get_post_meta( $order_id, '_easypack_status', true );

			$easypack_parcels = get_post_meta( $order_id, '_easypack_parcels', true );


			$stickers = array();

			if ( $easypack_parcels ) {
				foreach ( $easypack_parcels as $key => $parcel ) {
					try {
						$sticker = CrossBorder_API()->pdf_label( $parcel['crossborder_data']['id'] );
						$stickers[] = $sticker;
					}
					catch ( Exception $e ) {
						echo $e->getMessage();
						return;
					}
				}
			}

			if ( count( $stickers ) == 1 ) {
				if ( isset( $stickers[0][0]['pdf_url'] ) && trim( $stickers[0][0]['pdf_url'] ) != '' ) {
					header('Location: ' . $stickers[0][0]['pdf_url'] );
					die();
				}
			}
			else {
				$file = EasyPack_Helper()->write_stickers_to_file( $stickers );
				if ( $status == 'created' ) {
					update_post_meta( $order_id, '_easypack_status', 'prepared' );
				}
				EasyPack_Helper()->get_file($file, __( 'stickers', EasyPack::$text_domain ) . '_' . $order->id . '.pdf', 'application/pdf' );
			}
		}

		function woocommerce_my_account_my_orders_actions( $actions, $order ) {
			if ( $order->has_shipping_method($this->id) ) {
				$status = get_post_meta( $order->id, '_easypack_status', true );

				$tracking_url = false;
				if ( $status != 'new' ) {
					$tracking_url = EasyPack_Helper()->get_tracking_url();
					$parcels = get_post_meta( $order->id, '_easypack_parcels', true );
					foreach ( $parcels as $parcel ) {
						$tracking_url .= $parcel['crossborder_data']['tracking_number'] . ',';
					}
					$tracking_url = trim( $tracking_url, ',' );
				}

				if ( $tracking_url ) {
					$actions['easypack_tracking'] = array(
							'url'  => $tracking_url,
							'name' => __( 'Track shipment', EasyPack::$text_domain )
					);
				}
			}

			return $actions;
		}

		function woocommerce_email_after_order_table( $order, $is_admin, $plain_text ) {

			if ( $order->has_shipping_method($this->id) ) {
				$status = get_post_meta( $order->id, '_easypack_status', true );

				if ( $status != '' ) {
					$tracking_url = false;
					$package_numbers = '';
					if ( $status != 'new' ) {
						$tracking_url = EasyPack_Helper()->get_tracking_url();
						$parcels = get_post_meta( $order->id, '_easypack_parcels', true );
						foreach ( $parcels as $parcel ) {
							$tracking_url .= $parcel['crossborder_data']['tracking_number'] . ',';
							$package_numbers = $parcel['crossborder_data']['tracking_number'] . ', ';
						}
						$package_numbers = trim( trim ( $package_numbers ) , ',' );
						$tracking_url = trim( $tracking_url, ',' );
					}
					if ( $tracking_url ) {
						$args['tracking_url'] = $tracking_url;
						$args['package_numbers'] = $package_numbers;
						$args['logo'] = untrailingslashit( EasyPack()->getPluginUrl() ) . '/assets/images/logo/small/white.png';
						if ( $plain_text ) {
							wc_get_template( 'emails/plain/after-order-table.php', $args, '', plugin_dir_path( EasyPack()->getPluginFilePath() ) . 'templates/' );
						}
						else {
							wc_get_template( 'emails/after-order-table.php', $args, '', plugin_dir_path( EasyPack()->getPluginFilePath() ) . 'templates/' );
						}
					}
				}
			}

		}

		function wp_enqueue_scripts() {
			if ( is_checkout() ) {
				$geowidget_src = '//widget-xborder-inpost.sheepla.com/js/SheeplaLib.js';
				wp_enqueue_script(
						'crossborder-geowidget',
						$geowidget_src,
						array( 'jquery' )
						);

				$geowidget_css = '//widget-xborder-inpost.sheepla.com/css/SheeplaCrossBorder.css';
				wp_enqueue_style( 'crossborder-geowidget', $geowidget_css );
			}
		}

	}
}
