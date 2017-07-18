<?php
/**
 * EasyPack Shipping Method Parcel Machines
 *
 * @author      WPDesk
 * @category    Admin
 * @package     EasyPack/Admin
 * @version     2.1.0
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( ! class_exists( 'EasyPack_Shippng_Parcel_Machines' ) ) {
	class EasyPack_Shippng_Parcel_Machines extends WC_Shipping_Method {


		/**
		 * Constructor for shipping class
		 *
		 * @access public
		 * @return void
		 */
		public function __construct() {
			$this->id              	= 'easypack_parcel_machines';
			$this->method_title     = __( 'InPost Locker 24/7', EasyPack::$text_domain );
			$this->init();
		}


		/**
		 * Init your settings
		 *
		 *
		 * @access public
		 * @return void
		 */
		function init() {
			$this->init_form_fields(); // This is part of the settings API. Override the method to add your own settings			
			$this->init_settings(); // This is part of the settings API. Loads settings you previously init.

			// Define user set variables
			$this->title               	= $this->get_option( 'title' );
			$this->free_shipping_cost  	= $this->get_option( 'free_shipping_cost' );
			$this->flat_rate           	= $this->get_option( 'flat_rate' );
			$this->cost_per_order      	= $this->get_option( 'cost_per_order' );
			$this->based_on            	= $this->get_option( 'based_on' );

			$this->tax_status			= get_option( 'easypack_tax_status' );
				
			add_action( 'woocommerce_update_options_shipping_' . $this->id, array( $this, 'process_admin_options' ) );

			add_action( 'woocommerce_review_order_after_shipping', array( $this, 'woocommerce_review_order_after_shipping') );

			add_action( 'woocommerce_checkout_update_order_meta', array( $this, 'woocommerce_checkout_update_order_meta') );

			add_action( 'woocommerce_checkout_process', array($this, 'woocommerce_checkout_process' ) );

			add_action( 'save_post', array( $this, 'save_post' ) );

			add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ), 10, 2 );

			add_filter( 'woocommerce_cart_shipping_method_full_label', array( $this, 'woocommerce_cart_shipping_method_full_label' ), 10, 2 );

			add_filter( 'woocommerce_order_shipping_to_display_shipped_via', array( $this, 'woocommerce_order_shipping_to_display_shipped_via' ), 10, 2 );

			add_filter( 'woocommerce_my_account_my_orders_actions', array( $this, 'woocommerce_my_account_my_orders_actions' ), 10, 2 );

			add_action( 'woocommerce_email_after_order_table', array( $this, 'woocommerce_email_after_order_table' ), 10, 3 );

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
						jQuery('.easypack_based_on').closest('tr').css('display','none');
						jQuery('.easypack_rates').closest('tr').css('display','none');
						jQuery('#woocommerce_easypack_parcel_machines_1').css('display','none');
						jQuery('#woocommerce_easypack_parcel_machines_cod_1').css('display','none');
					}
					else {
						jQuery('.easypack_cost_per_order').closest('tr').css('display','none');
						jQuery('.easypack_based_on').closest('tr').css('display','table-row');
						jQuery('.easypack_rates').closest('tr').css('display','table-row');
						jQuery('#woocommerce_easypack_parcel_machines_1').css('display','block');
						jQuery('#woocommerce_easypack_parcel_machines_cod_1').css('display','block');
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
			include( 'views/html-rates.php' );
			return ob_get_clean();
		}


		public function init_form_fields() {
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
							'default'			=> __( 'InPost Locker 24/7', EasyPack::$text_domain ),
							'desc_tip'			=> false
					),
					'free_shipping_cost' => array(
							'title' 			=> __( 'Free shipping', EasyPack::$text_domain ),
							'type' 				=> 'number',
							'custom_attributes' => array(
													'step'	=> 'any',
													'min' 	=> '0'
							),
							'default' 			=> '',
							'desc_tip' 			=> __( 'Enter the amount of the order from which the shipping will be free (does not include virtual products). ', EasyPack::$text_domain ),
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
							'desc_tip' 			=> 'Set a flat-rate shipping for all orders.',
							'placeholder' 		=> '0.00'
							),

					array(
						'title'     			=> __( 'Rates table', EasyPack::$text_domain ),
						'type'     				=> 'title',
						'description'    	 	=> '',
						'id' 					=> 'section_general_settings',
					),
					'based_on' => array(
							'title' 			=> __( 'Based on', EasyPack::$text_domain ),
							'type' 				=> 'select',
							'desc_tip' 			=> __( 'Select the method of calculating shipping cost. If the cost of shipping is to be calculated based on the weight of the cart and the products do not have a defined weight, the cost will be calculated incorrectly.', EasyPack::$text_domain ),
							'class'    			=> 'wc-enhanced-select easypack_based_on',
							'options' => array(
											'price' 	=> __( 'Price', EasyPack::$text_domain ),
											'weight' 	=> __( 'Weight', EasyPack::$text_domain )
							)
					),
					'rates' => array(
							'title' 			=> '',
							'type' 				=> 'rates',
							'class'    			=> 'easypack_rates',
							'default' 			=> '',
							'desc_tip' 			=> '',
					),

			);
			$this->form_fields = $settings;
		}

		public function process_admin_options()
		{
			parent::process_admin_options();
			$rates = $_POST['rates'];
			update_option('woocommerce_' . $this->id . '_rates', $rates );
		}

		public function calculate_shipping_free_shipping( $package ) {
			if ( !empty($this->free_shipping_cost) && $this->free_shipping_cost <= $package['contents_cost'] )
			{
				$add_rate = array(
					'id' 		=> $this->id,
					'label' 	=> $this->title,
					'cost' 		=> 0,
				);
				$this->add_rate( $add_rate );
				return true;
			}
			return false;
		}

		public function calculate_shipping_flat( $package ) {
			if ( $this->flat_rate == 'yes' )
			{
				$add_rate = array(
						'id' 		=> $this->id,
						'label' 	=> $this->title,
						'cost' 		=> $this->cost_per_order,
				);
				$this->add_rate( $add_rate );
				return true;
			}
			return false;
		}

		public function package_weight( $items ) {
			$weight = 0;
			foreach( $items as $item )
				$weight += $item['data']->weight * $item['quantity'];
			return $weight;
		}

		public function package_subtotal( $items )	{
			$subtotal = 0;
			foreach( $items as $item )
				$subtotal += $item['line_subtotal'] + $item['line_subtotal_tax'];
				return $subtotal;
		}

		/**
		 * @param unknown $package
		 *
		 */
		public function calculate_shipping_table_rate( $package ) {
			$rates = get_option('woocommerce_' . $this->id . '_rates', array() );
			foreach ( $rates as $key => $rate) {
				if ( empty($rates[$key]['min']) || trim( $rates[$key]['min'] ) == '' ) {
					$rates[$key]['min'] = 0;
				}
				if ( empty($rates[$key]['max']) || trim( $rates[$key]['max'] ) == '' ) {
					$rates[$key]['max'] = PHP_INT_MAX;
				}
			}
			$value = 0;
			if ( $this->based_on == 'price' ) {
				$value = $this->package_subtotal( $package['contents'] );
			}
			if ( $this->based_on == 'weight' ) {
				$value = $this->package_weight( $package['contents'] );
			}
			foreach ( $rates as $rate ) {
				if	( floatval($rate['min']) <= $value && floatval($rate['max']) >= $value )
				{
					$add_rate = array(
							'id' 		=> $this->id,
							'label' 	=> $this->title,
							'cost' 		=> $rate['cost'],
					);
					$this->add_rate( $add_rate );
					return;
				}
			}
		}

		public function calculate_shipping( $package = array() ) {
			if ( strtoupper( $package['destination']['country'] ) == strtoupper( str_replace( 'test-', '', get_option( 'easypack_api_country', 'pl' ) ) ) ) {
				if ( ! $this->calculate_shipping_free_shipping( $package ) )
				{
					if ( ! $this->calculate_shipping_flat( $package ) )
					{
						$this->calculate_shipping_table_rate( $package );
					}
				}
			}
		}

		public function woocommerce_review_order_after_shipping() {
			if ( in_array( $this->id, WC()->session->get('chosen_shipping_methods') ) ) {
				$parcel_machines = EasyPack_API()->machines_options();
				$args = array( 'parcel_machines' => $parcel_machines );
				$args['parcel_machine_id'] = WC()->session->get( 'parcel_machine_id' );
				$args['geowidget_src'] = EasyPack_Helper()->get_geowidget_src();
				wc_get_template( 'checkout/easypack-review-order-after-shipping.php', $args, '', plugin_dir_path( EasyPack()->getPluginFilePath() ) . 'templates/' );
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
				$billing_phone = $_POST['billing_phone'];
				$validate_phone = EasyPack_API()->validate_phone( $billing_phone );
				if ( $validate_phone !== true ) {
					wc_add_notice( $validate_phone, 'error' );
				}
			}

		}

		public function woocommerce_checkout_update_order_meta( $order_id ) {
			if ($_POST['parcel_machine_id']) {
				update_post_meta($order_id, '_parcel_machine_id', esc_attr($_POST['parcel_machine_id']));
			}
		}

		public function save_post( $post_id ) {
			// Check if our nonce is set.
			if ( ! isset( $_POST['easypack_box_data_parcel_machines'] ) ) {
				return;
			}
			// Verify that the nonce is valid.
			if ( ! wp_verify_nonce( $_POST['easypack_box_data_parcel_machines'], 'easypack_box_data_parcel_machines' ) ) {
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
					$easypack_pacels[] = array( 'package_size' => $parcel );
				}
				update_post_meta( $post_id, '_easypack_parcels', $easypack_pacels );

				$easypack_send_method = $_POST['send_method'];
				update_post_meta( $post_id, '_easypack_send_method', $easypack_send_method );
			}

		}

		public function get_logo() {
			return '<img style="height:22px; float:right;" src="' . untrailingslashit( EasyPack()->getPluginUrl() ). '/assets/images/logo/small/white.png"/>';
		}

 		public function add_meta_boxes( $post_type, $post ) {
			if ( $post->post_type == 'shop_order' )
			{
				$order = wc_get_order( $post->ID );
				if ( $order->has_shipping_method($this->id) ) {
					add_meta_box( 'easypack_parcel_machines'
								, __('InPost', EasyPack::$text_domain ) . $this->get_logo()
								, array( $this, 'order_metabox' )
								, 'shop_order'
								, 'side'
								, 'default'
								);
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

			$parcel_machines = EasyPack_API()->machines_options();

			$parcel_machine_id = get_post_meta( $post->ID, '_parcel_machine_id', true );
			$parcels = get_post_meta( $post->ID, '_easypack_parcels', true );
			$package_sizes = EasyPack()->get_package_sizes();
			$package_sizes_display = EasyPack()->get_package_sizes_display();
			if ( $parcels == '' ) {
				$parcels = array();
				$parcel = array( 'package_size' => get_option( 'easypack_default_package_size', 'A' ) );
				$parcels[] = $parcel;
			}
			$send_methods = array( 'parcel_machine' => __( 'Parcel locker', EasyPack::$text_domain ), 'courier' => __( 'Courier', EasyPack::$text_domain ) );
			$send_method = get_post_meta( $post->ID, '_easypack_send_method', true );
			if ( $send_method == '' ) {
				$send_method = get_option( 'easypack_default_send_method', 'parcel_machine' );
			}

			$stickers_url = site_url('?easypack_download=1&easypack_parcel_machines_stickers=1&order_id=' . $order_id . '&security=' . wp_create_nonce( 'easypack_nonce' ) );

			$tracking_url = false;
			if ( $status != 'new' ) {
				$tracking_url = EasyPack_Helper()->get_tracking_url();
				foreach ( $parcels as $parcel ) {
					$tracking_url .= $parcel['easypack_data']['id'] . ',';
				}
				$tracking_url = trim( $tracking_url, ',' );
			}

			$disabled = false;
			if ( $status != 'new' ) $disabled = true;

			$send_method_disabled = false;
			if ( EasyPack_API()->api_country() == 'PL' /* || EasyPack_API()->api_country() == 'CA' */ ) {
				$send_methods = array( 'parcel_machine' => __( 'Parcel locker', EasyPack::$text_domain ), 'courier' => __( 'Courier', EasyPack::$text_domain ) );
			}
			else {
				$send_methods = array( 'courier' => __( 'Courier', EasyPack::$text_domain ) );
			}
			include( 'views/html-order-matabox-parcel-machines.php' );

			wp_nonce_field( 'easypack_box_data_parcel_machines', 'easypack_box_data_parcel_machines' );
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
			$parcel_machine_id = $_POST['parcel_machine_id'];
			$send_method = $_POST['send_method'];

			$order_parcels = array();

			foreach ( $parcels as $parcel )
			{
				$args = array();
				$args['target_machine_id'] = $parcel_machine_id;
				$args['size'] = $parcel;
				$args['receiver_email'] = $order->billing_email;
				$args['receiver_phone'] = $order->billing_phone;
				if ( $send_method == 'parcel_machine' )
					$args['source_machine_id'] = get_option( 'easypack_default_machine_id' );
				$args['customer_reference'] = sprintf( __( 'Order %s', EasyPack::$text_domain ), $order->get_order_number() );

				if ( EasyPack_API()->api_country() == 'FR') {

					$args['receiver_phone'] = ltrim( $args['receiver_phone'], "0" );

					$args['receiver'] = array();
					$args['receiver']['first_name'] = $order->shipping_first_name;
					$args['receiver']['last_name'] = $order->shipping_last_name;
					$args['receiver']['address'] = array();
					$args['receiver']['address']['street'] = $order->shipping_address_1;
					$args['receiver']['address']['building_no'] = $order->shipping_address_2;
					$args['receiver']['address']['post_code'] = $order->shipping_postcode;
					$args['receiver']['address']['city'] = $order->shipping_city;
					if ( $order->shipping_company && trim( $order->shipping_company ) != '' ) {
						$args['receiver']['company_name'] = $order->shipping_company;
					}
				}

				if ( EasyPack_API()->api_country() == 'IT') {
					$args['receiver'] = array();
					$args['receiver']['first_name'] = $order->shipping_first_name;
					$args['receiver']['last_name'] = $order->shipping_last_name;
				}

				if ( EasyPack_API()->api_country() == 'FR' || EasyPack_API()->api_country() == 'IT' ) {
					$args['sender_address'] = array();
					$args['sender_address']['first_name'] = get_option('easypack_sender_first_name');
					$args['sender_address']['last_name'] = get_option('easypack_sender_last_name');
					$args['sender_address']['company_name'] = get_option('easypack_sender_company_name');
					$args['sender_address']['street'] = get_option('easypack_sender_street');
					$args['sender_address']['building_no'] = get_option('easypack_sender_building_no');
					$args['sender_address']['flat_no'] = get_option('easypack_sender_flat_no');
					$args['sender_address']['post_code'] = get_option('easypack_sender_post_code');
					$args['sender_address']['city'] = get_option('easypack_sender_city');
				}

				try {
//update_post_meta( $order_id, '_easypack_parcel_create_args', $args );
					$easypack_data = EasyPack_API()->customer_parcel_create($args);
					$order_parcels[] = array( 'package_size' => $parcel, 'easypack_data' => $easypack_data );
				}
				catch ( Exception $e ) {
					$ret['status'] = 'error';
					$ret['message'] = __( 'There are some errors. Please fix it:' ) . $e->getMessage();
					break;
				}
			}
			if ( $ret['status'] == 'ok' ) {
				update_post_meta( $order_id, '_easypack_parcels', $order_parcels );
				update_post_meta( $order_id, '_easypack_status', 'created' );
				update_post_meta( $order_id, '_easypack_send_method', $send_method );
				$order->add_order_note( __( 'Shipment created', EasyPack::$text_domain ), false);
				$ret['content'] = self::order_metabox_content( $post, false );
			}
			echo json_encode( $ret );
			wp_die();
		}

		public static function ajax_cancel_package() {

			$ret = array( 'status' => 'ok', 'message' => '' );

			$order_id = $_POST['order_id'];
			$order = wc_get_order($order_id);
			$post = get_post( $order_id );

			$easypack_parcels = get_post_meta( $order_id, '_easypack_parcels', true );

			if ( $easypack_parcels ) {
				foreach ( $easypack_parcels as $key => $parcel ) {
					try {
						$cancelled_parcel = EasyPack_API()->customer_parcel_cancel( $parcel['easypack_data']['id'] );
						if ( $cancelled_parcel['status'] != 'cancelled') {
							throw new Exception( sprintf( __('Cannot cancel package %s'), $parcel['easypack_data']['id'] ) );
						}
						unset( $easypack_parcels[$key]['easypack_data'] );
					}
					catch ( Exception $e ) {
						$ret['status'] = 'error';
						$ret['message'] .= $e->getMessage();
					}
				}
			}

			if ( $ret['status'] == 'ok' ) {
				update_post_meta( $order_id, '_easypack_parcels', $order_parcels );
				update_post_meta( $order_id, '_easypack_status', 'new' );
				$order->add_order_note( __( 'Shipment canceled', EasyPack::$text_domain ), false);
				$ret['content'] = self::order_metabox_content( $post, false );
			}
			echo json_encode( $ret );
			wp_die();
		}

		public static function ajax_dispatch_order() {

			$ret = array( 'status' => 'ok', 'message' => '' );

			$parcels = $_POST['parcels'];
			$ret['parcels'] = $parcels;

			$dispatch_point = $_POST['dispatch_point'];
			$ret['dispatch_point'] = $dispatch_point;

			$parcel_ids = array();
			foreach ( $parcels as $parcel ) {
				$parcel_exploded = explode( '.', $parcel );
				$parcel_ids[] = $parcel_exploded[2];
			}

			$args = array( 'parcel_ids' => $parcel_ids );

			try {
				$response = EasyPack_API()->dispatch_order( $dispatch_point, $args );
				$ret['id'] = $response['id'];
			}
			catch ( Exception $e ) {
				$ret['status'] = 'error';
				$ret['message'] .= $e->getMessage();
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
						$easypack_data = EasyPack_API()->customer_parcel( $parcel['easypack_data']['id'] );
						if ( $parcel['easypack_data']['status'] != $easypack_data['status'] ) {
							$parcel['easypack_data'] = $easypack_data;
							$easypack_parcels[$key] = $parcel;
							update_post_meta( $order_id, '_easypack_parcels', $easypack_parcels );
						}
						if ( $parcel['easypack_data']['status'] == 'created' ) {
							$easypack_data = EasyPack_API()->customer_parcel_pay( $parcel['easypack_data']['id'] );
							$easypack_parcels[$key]['easypack_data'] = $easypack_data;
							update_post_meta( $order_id, '_easypack_parcels', $easypack_parcels );
						}
						$stickers[] = EasyPack_API()->customer_parcel_sticker( $parcel['easypack_data']['id'] );
					}
					catch ( Exception $e ) {
						echo $e->getMessage();
						return;
					}
				}
			}

			$file = EasyPack_Helper()->write_stickers_to_file( $stickers );
			if ( $status == 'created' ) {
				update_post_meta( $order_id, '_easypack_status', 'prepared' );
			}
			EasyPack_Helper()->get_file($file, __( 'stickers', EasyPack::$text_domain ) . '_' . $order->id . '.pdf', 'application/pdf' );
		}

		function woocommerce_cart_shipping_method_full_label( $label, $method ) {
			if ( $method->id == $this->id ) {
				$img = ' <span class="easypack-shipping-method-logo"><img style="" src="' . EasyPack()->getPluginUrl(). '/assets/images/logo/small/white.png" /><span>';
				$label .= $img;
			}
			return $label;
		}

		function woocommerce_order_shipping_to_display_shipped_via( $via, $order ) {
			if ( $order->has_shipping_method( $this->id ) ) {
				$img = ' <span class="easypack-shipping-method-logo" style="display: inline;"><img style="max-width: 100; max-height: 40px;	display: inline; border:none;" src="' . EasyPack()->getPluginUrl(). '/assets/images/logo/small/white.png" /><span>';
				$via .= $img;
			}
			return $via;
		}

		function woocommerce_my_account_my_orders_actions( $actions, $order ) {
			if ( $order->has_shipping_method($this->id) ) {
				$status = get_post_meta( $order->id, '_easypack_status', true );

				$tracking_url = false;
				if ( $status != 'new' ) {
					$tracking_url = EasyPack_Helper()->get_tracking_url();
					$parcels = get_post_meta( $order->id, '_easypack_parcels', true );
					if ( $parcels != '' ) {
						foreach ( $parcels as $parcel ) {
							$tracking_url .= $parcel['easypack_data']['id'] . ',';
						}
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

				if ( $status != '' && $status != 'new' ) {
					$tracking_url = false;
					$package_numbers = '';
					if ( $status != 'new' ) {
						$tracking_url = EasyPack_Helper()->get_tracking_url();
						$parcels = get_post_meta( $order->id, '_easypack_parcels', true );
						foreach ( $parcels as $parcel ) {
							$tracking_url .= $parcel['easypack_data']['id'] . ',';
							$package_numbers = $parcel['easypack_data']['id'] . ', ';
						}
						$package_numbers = trim( trim ( $package_numbers ) , ',' );
						$tracking_url = trim( $tracking_url, ',' );
					}
					if ( $tracking_url ) {
						$args['tracking_url'] = $tracking_url;
						$args['package_numbers'] = $package_numbers;
						$args['logo'] = untrailingslashit( EasyPack()->getPluginUrl() ). '/assets/images/logo/small/white.png';
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

	}
}
