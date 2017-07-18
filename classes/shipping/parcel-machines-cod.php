<?php
/**
 * EasyPack Shipping Method Parcel Machines COD
 *
 * @author      WPDesk
 * @category    Admin
 * @package     EasyPack/Admin
 * @version     2.1.0
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( ! class_exists( 'EasyPack_Shippng_Parcel_Machines_COD' ) ) {
	class EasyPack_Shippng_Parcel_Machines_COD extends EasyPack_Shippng_Parcel_Machines {

		/**
		 * Constructor for shipping class
		 *
		 * @access public
		 * @return void
		 */
		public function __construct() {
			$this->id              	= 'easypack_parcel_machines_cod';
			$this->method_title     = __( 'InPost Locker 24/7 COD', EasyPack::$text_domain );
			$this->init();
		}

		public function generate_rates_html( $key, $data )
		{
			$rates = get_option('woocommerce_' . $this->id . '_rates', array() );
			$commission = '';
			try {
				$pricelists = EasyPack_API()->customer_pricelists();
				$commission = floatval($pricelists['_embedded']['standard']['cod']['commission']);
			}
			catch ( Exception $e ) {
				?>
					<div class="error">
						<p><?php echo $e->getMessage(); ?></p>
					</div>
				<?php
			}
			ob_start();
			include( 'views/html-rates-cod.php' );
			return ob_get_clean();
		}

		public function init_form_fields() {
			$bank_accounts = array();
			//$bacs = new WC_Gateway_BACS();			 
			//$accounts = $bacs->account_details;
			$accounts = get_option( 'woocommerce_bacs_accounts', array() );
			foreach ( $accounts as $account )
			{
				if ( isset( $account['iban'] ) && $account['iban'] != '' ) {
					$bank_accounts[$account['iban']] = $account['iban'] . ' ' . $account['bank_name'];
				}
			}

			$settings = array(
					array(
							'title'     	=> __( 'General settings', EasyPack::$text_domain ),
							'type'     		=> 'title',
							'description'   => '',
							'id' 			=> 'section_general_settings',
					),
					'enabled' => array(
							'title' 		=> __( 'Enable/disable', EasyPack::$text_domain ),
							'type' 			=> 'checkbox',
							'label' 		=> __( 'Enable this shipping metod', EasyPack::$text_domain ),
							'default' 		=> 'no',
					),
					'title' => array(
							'title' 			=> __( 'Method title', EasyPack::$text_domain ),
							'type' 				=> 'text',
							'default'  			=> __( 'InPost Locker 24/7 COD', EasyPack::$text_domain ),
							'custom_attributes' => array( 'required' => 'required' ),
							'desc_tip'			=> false
					),
/* RM: 11101
					'bank_account' => array(
							'title' 			=> __( 'Bank account', EasyPack::$text_domain ),
							'type' 				=> 'select',
							'desc_tip'	 		=> __( 'Select the account to which the cost of COD will be transferred. If the list is empty, add the account number in the Settings.', EasyPack::$text_domain ),
							'class'    			=> 'wc-enhanced-select1',
							'custom_attributes' => array( 'required' => 'required' ),
							'options' 			=> $bank_accounts
					),
*/					
					'free_shipping_cost' => array(
							'title' 			=> __('Free shipping', EasyPack::$text_domain ),
							'type' 				=> 'number',
							'custom_attributes' => array( 'step' => 'any', 'min' => '0' ),
							'default' 			=> '',
							'desc_tip' 			=> __('Enter the amount of the contract, from which shipping will be free (does not include virtual products).', EasyPack::$text_domain ),
							'placeholder' 		=> '0.00'
					),
					'flat_rate' => array(
							'title' 		=> __( 'Flat rate', EasyPack::$text_domain ),
							'type' 			=> 'checkbox',
							'label' 		=> __( 'Set a flat-rate shipping fee for the entire order.', EasyPack::$text_domain ),
							'class'			=> 'easypack_flat_rate',
							'default' 		=> 'yes',
					),
					'cost_per_order' => array(
							'title' 			=> __( 'Cost per order', EasyPack::$text_domain ),
							'type' 				=> 'number',
							'custom_attributes' => array( 'step' => 'any', 'min' => '0' ),
							'class'				=> 'easypack_cost_per_order',
							'default' 			=> '',
							'desc_tip' 			=> 'Set a flat-rate shipping for all orders.',
							'placeholder' 		=> '0.00'
					),

					array(
					'title'    		=> __( 'Rates table', EasyPack::$text_domain ),
					'type'     		=> 'title',
					'description'   => '',
					'id' 			=> 'section_general_settings',
					),

					'based_on' => array(
							'title' 		=> __( 'Based on', EasyPack::$text_domain ),
							'type' 			=> 'select',
							'desc_tip' 			=> __( 'Select the method of calculating shipping cost. If the cost of shipping is to be calculated based on the weight of the cart and the products do not have a defined weight, the cost will be calculated incorrectly.', EasyPack::$text_domain ),
							'class'    		=> 'wc-enhanced-select easypack_based_on',
							'options' 		=> array(
									'price' 	=> __( 'Price', EasyPack::$text_domain ),
									'weight' 	=> __( 'Weight', EasyPack::$text_domain )
							)
					),
					'rates' => array(
							'title' 	=> '',
							'type' 		=> 'rates',
							'class'    	=> 'easypack_rates',
							'default' 	=> '',
							'desc_tip' 	=> '',
					),

			);
			$this->form_fields = $settings;
		}

		public function process_admin_options()
		{
			parent::process_admin_options();
			EasyPack_API()->clear_cache();

/* RM 11101			
			$args = array();
			$args['iban'] = $this->get_option( 'bank_account' );

			try {
				EasyPack_API()->update_customer( $args );
			}
			catch ( Exception $e ) {
				WC_Admin_Settings::add_error( strip_tags( $e->getMessage() ) );
			}
*/			

		}

		public function save_post( $post_id ) {
			// Check if our nonce is set.
			if ( ! isset( $_POST['easypack_box_data_parcel_machines_cod'] ) ) {
				return;
			}
			// Verify that the nonce is valid.
			if ( ! wp_verify_nonce( $_POST['easypack_box_data_parcel_machines_cod'], 'easypack_box_data_parcel_machines_cod' ) ) {
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
				$cod_amounts = $_POST['cod_amount'];
				$easypack_pacels = array();
				foreach ( $parcels as $key => $parcel )
				{
					$easypack_pacels[] = array( 'package_size' => $parcel, 'cod_amount' => $cod_amounts[$key] );
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

			$parcel_machines = EasyPack_API()->machines_cod_options();

			$parcel_machine_id = get_post_meta( $post->ID, '_parcel_machine_id', true );
			$parcels = get_post_meta( $post->ID, '_easypack_parcels', true );
			$package_sizes = EasyPack()->get_package_sizes();
			$package_sizes_display = EasyPack()->get_package_sizes_display();
			if ( $parcels == '' ) {
				$parcels = array();
				$parcel = array( 'package_size' => get_option( 'easypack_default_package_size', 'A' ), 'cod_amount' => $order->get_total() );
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
			if ( EasyPack_API()->api_country() == 'PL' || EasyPack_API()->api_country() == 'CA' ) {
				$send_methods = array( 'parcel_machine' => __( 'Parcel locker', EasyPack::$text_domain ), 'courier' => __( 'Courier', EasyPack::$text_domain ) );
			}
			else {
				$send_methods = array( 'courier' => __( 'Courier', EasyPack::$text_domain ) );
			}
			include( 'views/html-order-matabox-parcel-machines_cod.php' );

			wp_nonce_field( 'easypack_box_data_parcel_machines_cod', 'easypack_box_data_parcel_machines_cod' );
			if ( ! $output )
			{
				$out = ob_get_clean();
				return $out;
			}
		}

		public function woocommerce_review_order_after_shipping() {
			if ( in_array( $this->id, WC()->session->get('chosen_shipping_methods') ) ) {
				$parcel_machines = EasyPack_API()->machines_cod_options();
				$args = array( 'parcel_machines' => $parcel_machines );
				$args['parcel_machine_id'] = WC()->session->get( 'parcel_machine_id' );
				$args['geowidget_src'] = EasyPack_Helper()->get_geowidget_src( $cod = TRUE );
				wc_get_template( 'checkout/easypack-review-order-after-shipping.php', $args, '', plugin_dir_path( EasyPack()->getPluginFilePath() ) . 'templates/' );
			}
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
					$cost = 0;
					if ( isset( $rate['percent'] ) && floatval( $rate['percent']) != 0 ) {
						$cost = $package['contents_cost'] * ( floatval( $rate['percent'] ) / 100);
					}
					$cost = $cost + floatval( $rate['cost'] );
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



		public static function ajax_create_package( $courier = false ) {
			$ret = array( 'status' => 'ok' );

			$order_id = $_POST['order_id'];
			$order = wc_get_order($order_id);
			$post = get_post( $order_id );

			$parcels = $_POST['parcels'];
			$cod_amounts = $_POST['cod_amounts'];
			$parcel_machine_id = $_POST['parcel_machine_id'];
			$send_method = $_POST['send_method'];

			$order_parcels = array();

			$total_amount = 0;
			foreach ( $cod_amounts as $amount ) {
				$total_amount = $total_amount+floatval($amount);
			}

			if ( $total_amount != $order->get_total() ) {
				$ret['status'] = 'error';
				$ret['message'] = sprintf( __( 'Order total %s do not equals total COD amounts %s.' ), $order->get_total(), $total_amount );
			}
			else {
				foreach ( $parcels as $key => $parcel )
				{
					$args = array();
					$args['target_machine_id'] = $parcel_machine_id;
					$args['size'] = $parcel;
					$args['receiver_email'] = $order->billing_email;
					$args['receiver_phone'] = $order->billing_phone;
					if ( $send_method == 'parcel_machine' )
						$args['source_machine_id'] = get_option( 'easypack_default_machine_id' );
					$args['customer_reference'] = sprintf( __( 'Order %s', EasyPack::$text_domain ), $order->get_order_number() );

					$args['cod_amount'] = $cod_amounts[$key];
					$args['additional1'] = __( 'Order ', EasyPack::$text_domain ) . $order->get_order_number();

					try {
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

		public static function ajax_get_payment_status() {

			$ret = array( 'status' => 'ok', 'message' => '' );

			$paecel_id = $_POST['parcel_id'];

			$order_id = $_POST['order_id'];
			$order = wc_get_order($order_id);
			$post = get_post( $order_id );

			$status = get_post_meta( $order_id, '_easypack_status', true );

			$easypack_parcels = get_post_meta( $order_id, '_easypack_parcels', true );
			$stickers = array();

			if ( $easypack_parcels ) {
				foreach ( $easypack_parcels as $key => $parcel ) {
					try {
						if ( $parcel['easypack_data']['id'] == $parcel_id ) {
							$easypack_parcel = EasyPack_API()->customer_parcel( $parcel_id );
						}
					}
					catch ( Exception $e ) {
						$ret['status'] = 'error';
						$ret['message'] .= $e->getMessage();
					}
				}
			}

			if ( $ret['status'] == 'ok' ) {
			}
			echo json_encode( $ret );
			wp_die();
		}


	}
}
