<?php
/**
 * EasyPack General Settings
*
* @author      WPDesk
* @category    Admin
* @package     EasyPack/Admin
* @version     2.1.0
*/

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( ! class_exists( 'EasyPack_Settings_General' ) ) :

/**
 * EasyPack_Settings_General
*/
class EasyPack_Settings_General extends WC_Settings_Page {
	/**
	 * Constructor.
	 */
	public function __construct() {

		$this->id    = 'easypack_general';
		$this->label = __( 'InPost', EasyPack::$text_domain );

		add_filter( 'woocommerce_settings_tabs_array', array( $this, 'add_settings_page' ), 20 );
		add_action( 'woocommerce_settings_' . $this->id, array( $this, 'output' ) );
		add_action( 'woocommerce_settings_save_' . $this->id, array( $this, 'save' ) );

		add_action( 'woocommerce_admin_field_button', array( $this, 'woocommerce_admin_field_button' ) );

	}

	public function woocommerce_admin_field_button( $value ) {
		$tooltip_html = '<img class="help_tip" data-tip="' . esc_attr( $value['desc_tip'] ) . '" src="' . WC()->plugin_url() . '/assets/images/help.png" height="16" width="16" />';
		?>
		<tr valign="top">
			<th scope="row" class="titledesc">
				<label for="<?php echo esc_attr( $value['id'] ); ?>"><?php echo esc_html( $value['title'] ); ?></label>
				<?php echo $tooltip_html; ?>
			</th>
			<td class="forminp forminp-<?php echo sanitize_title( $value['type'] ) ?>">
				<button
					name="<?php echo esc_attr( $value['id'] ); ?>"
					id="<?php echo esc_attr( $value['id'] ); ?>"
					type="button"
					style="<?php echo esc_attr( $value['css'] ); ?>"
					class="<?php echo esc_attr( $value['class'] ); ?>"
				/><?php echo esc_attr( $value['content'] ); ?></button>
				&nbsp;<span id="<?php echo esc_attr( $value['id'] ); ?>_message"></span>
				<?php wp_nonce_field( $value['id'], 'nonce_'.$value['id'] ); ?>
			</td>
		</tr>
		<?php
	}

	/**
	 * Output the settings
	 */
	public function output() {
		$easypack_api_change = get_option( 'easypack_api_error_login', 0 );
		?>
		<input id="easypack_api_change" type="hidden" name="easypack_api_change" value="<?php echo $easypack_api_change; ?>">
		<style>
			.form-table {
				border-bottom: 1px solid #ccc;
			}
		</style>
		<?php
		$settings = $this->get_settings();
		WC_Admin_Settings::output_fields( $settings );
		?>
		<script type="text/javascript">
			jQuery('#easypack_api_url').closest('tr').css('display','none');
			jQuery('#easypack_geowidget_url').closest('tr').css('display','none');
			jQuery('#easypack_crossborder_api_url').closest('tr').css('display','none');
			jQuery('#easypack_api_country').closest('td').append('<button id="easypack_api_url_button" class="button"><?php _e( 'API URL', EasyPack::$text_domain ) ?></butto/>');
			jQuery('#easypack_default_dispatch_point').closest('td').append('<button id="easypack_default_dispatch_point_add_button" class="button"><?php _e( 'New dispatch point', EasyPack::$text_domain ) ?></butto/>');
			jQuery('#easypack_default_dispatch_point').closest('td').append('<img class="help_tip" data-tip="<?php _e( 'Click Save Changes to add a new Dispatch point.', EasyPack::$text_domain ) ?>" src="<?php echo plugins_url('/woocommerce/assets/images/help.png' ); ?>" height="16" width="16" />');

			jQuery('#easypack_api_url_button').click(function(){
				if (jQuery('#easypack_api_url').closest('tr').css('display') == 'none' ) {
					jQuery('#easypack_api_url').closest('tr').css('display','table-row');
					if ( jQuery('#easypack_api_country').val() == 'ca' || jQuery('#easypack_api_country').val() == 'test-ca' ) {
						jQuery('#easypack_geowidget_url').closest('tr').css('display','table-row');
					}
					if ( jQuery('#easypack_api_country').val() == 'pl' || jQuery('#easypack_api_country').val() == 'test-pl' ) {
						jQuery('#easypack_crossborder_api_url').closest('tr').css('display','table-row');
					}
				}
				else {
					jQuery('#easypack_api_url').closest('tr').css('display','none');
					jQuery('#easypack_geowidget_url').closest('tr').css('display','none');
					jQuery('#easypack_crossborder_api_url').closest('tr').css('display','none');
				}
				return false;
			})
			jQuery('#easypack_default_dispatch_point_add_button').click(function(){
				jQuery('#easypack_dispatch_point_name').val('');
				jQuery('#easypack_dispatch_point_name').focus();
				jQuery('#easypack_dispatch_point_email').val('');
				jQuery('#easypack_dispatch_point_phone').val('');
				jQuery('#easypack_dispatch_point_office_hours').val('');
				jQuery('#easypack_dispatch_point_street').val('');
				jQuery('#easypack_dispatch_point_building_no').val('');
				jQuery('#easypack_dispatch_point_flat_no').val('');
				jQuery('#easypack_dispatch_point_post_code').val('');
				jQuery('#easypack_dispatch_point_city').val('');
				return false;
			})
			function easypack_address_fields() {
				if ( jQuery('#easypack_api_country').val() == 'ca' || jQuery('#easypack_api_country').val() == 'test-ca' ) {
					jQuery('#easypack_sender_street').attr('required',false);
					jQuery('#easypack_sender_building_no').attr('required',false);
					jQuery('#easypack_sender_flat_no').attr('required',false);
					jQuery('#easypack_sender_post_code').attr('required',false);

					jQuery('#easypack_sender_street').closest('tr').css('display','none');
					jQuery('#easypack_sender_building_no').closest('tr').css('display','none');
					jQuery('#easypack_sender_flat_no').closest('tr').css('display','none');
					jQuery('#easypack_sender_post_code').closest('tr').css('display','none');

					jQuery('#easypack_sender_address1').attr('required',true);
					jQuery('#easypack_sender_postal_code').attr('required',true);

					jQuery('#easypack_sender_address1').closest('tr').css('display','table-row');
					jQuery('#easypack_sender_address2').closest('tr').css('display','table-row');
					jQuery('#easypack_sender_postal_code').closest('tr').css('display','table-row');
				}
				else {
					jQuery('#easypack_sender_street').attr('required',true);
					jQuery('#easypack_sender_building_no').attr('required',true);
					jQuery('#easypack_sender_flat_no').attr('required',false);
					jQuery('#easypack_sender_post_code').attr('required',true);

					jQuery('#easypack_sender_street').closest('tr').css('display','table-row');
					jQuery('#easypack_sender_building_no').closest('tr').css('display','table-row');
					jQuery('#easypack_sender_flat_no').closest('tr').css('display','table-row');
					jQuery('#easypack_sender_post_code').closest('tr').css('display','table-row');

					jQuery('#easypack_sender_address1').attr('required',false);
					jQuery('#easypack_sender_postal_code').attr('required',false);

					jQuery('#easypack_sender_address1').closest('tr').css('display','none');
					jQuery('#easypack_sender_address2').closest('tr').css('display','none');
					jQuery('#easypack_sender_postal_code').closest('tr').css('display','none');
				}
			}
			function easypack_returns() {
				if ( jQuery('#easypack_api_country').val() == 'ca' || jQuery('#easypack_api_country').val() == 'test-ca' ) {
					jQuery('#easypack_returns_page').closest('table').prev().css('display','none');
					jQuery('#easypack_returns_page').closest('table').css('display','none');					
				}
				else {
					jQuery('#easypack_returns_page').closest('table').prev().css('display','block');
					jQuery('#easypack_returns_page').closest('table').css('display','table');					
				}
			}
			function easypack_send_options() {
				if (/* jQuery('#easypack_api_country').val() == 'ca' || jQuery('#easypack_api_country').val() == 'test-ca'
					|| */ jQuery('#easypack_api_country').val() == 'pl' || jQuery('#easypack_api_country').val() == 'test-pl'
				) {
					jQuery('#easypack_default_send_method').closest('table').prev().css('display','block');
					jQuery('#easypack_default_send_method').closest('table').css('display','table');
					//jQuery('#easypack_default_machine_id').attr('required',true);
				}
				else {
					jQuery('#easypack_default_send_method').closest('table').prev().css('display','none');
					jQuery('#easypack_default_send_method').closest('table').css('display','none');
					jQuery('#easypack_default_machine_id').attr('required',false);
				}
			}
			jQuery('#easypack_api_country').change(function() {
			});
			function easypack_country_change() {
				if ( jQuery('#easypack_api_country').val() == '--' ) {
					jQuery('#easypack_login').closest('table').prev().css('display','none');
					jQuery('#easypack_login').closest('table').css('display','none');
					jQuery('#easypack_tax_status').closest('table').prev().css('display','none');
					jQuery('#easypack_tax_status').closest('table').css('display','none');
					jQuery('#easypack_returns_page').closest('table').prev().css('display','none');
					jQuery('#easypack_returns_page').closest('table').css('display','none');
					jQuery('#easypack_default_send_method').closest('table').prev().css('display','none');
					jQuery('#easypack_default_send_method').closest('table').css('display','none');
					jQuery('#easypack_sender_first_name').closest('table').prev().css('display','none');
					jQuery('#easypack_sender_first_name').closest('table').css('display','none');
					jQuery('.button-primary').attr('disabled',true);
				}
				else {
					jQuery('#easypack_login').closest('table').prev().css('display','block');
					jQuery('#easypack_login').closest('table').css('display','table');
					jQuery('#easypack_tax_status').closest('table').prev().css('display','block');
					jQuery('#easypack_tax_status').closest('table').css('display','table');
					jQuery('#easypack_returns_page').closest('table').prev().css('display','block');
					jQuery('#easypack_returns_page').closest('table').css('display','table');
					jQuery('#easypack_default_send_method').closest('table').prev().css('display','block');
					jQuery('#easypack_default_send_method').closest('table').css('display','table');
					jQuery('#easypack_sender_first_name').closest('table').prev().css('display','block');
					jQuery('#easypack_sender_first_name').closest('table').css('display','table');
					jQuery('.button-primary').attr('disabled',false);
					easypack_address_fields();
					easypack_returns();
					easypack_send_options();
				}
				if ( jQuery('#easypack_api_country').val() == 'pl' || jQuery('#easypack_api_country').val() == 'test-pl' ) {
					jQuery('#easypack_dispatch_point_name').attr('required',true);
					jQuery('#easypack_dispatch_point_email').attr('required',true);
					jQuery('#easypack_dispatch_point_phone').attr('required',true);
					jQuery('#easypack_dispatch_point_office_hours').attr('required',false);
					jQuery('#easypack_dispatch_point_street').attr('required',true);
					jQuery('#easypack_dispatch_point_building_no').attr('required',true);
					jQuery('#easypack_dispatch_point_flat_no').attr('required',false);
					jQuery('#easypack_dispatch_point_post_code').attr('required',true);
					jQuery('#easypack_dispatch_point_city').attr('required',true);
				}
				else {
					jQuery('#easypack_dispatch_point_name').attr('required',false);
					jQuery('#easypack_dispatch_point_email').attr('required',false);
					jQuery('#easypack_dispatch_point_phone').attr('required',false);
					jQuery('#easypack_dispatch_point_office_hours').attr('required',false);
					jQuery('#easypack_dispatch_point_street').attr('required',false);
					jQuery('#easypack_dispatch_point_building_no').attr('required',false);
					jQuery('#easypack_dispatch_point_flat_no').attr('required',false);
					jQuery('#easypack_dispatch_point_post_code').attr('required',false);
					jQuery('#easypack_dispatch_point_city').attr('required',false);
					jQuery('#easypack_default_machine_id').attr('required',false);
				}
				if ( jQuery('#easypack_api_country').val() == 'pl' || jQuery('#easypack_api_country').val() == 'test-pl' ) {
					if ( jQuery('#easypack_api_country').val() == 'pl' ) {
						jQuery('#easypack_crossborder_api_url').val('https://api-xborder-inpost.sheepla.com');
					}
					else {
						jQuery('#easypack_crossborder_api_url').val('https://test-api-xborder-inpost.sheepla.com');
					}
				}
			}

			function easypack_api_change() {
				if ( jQuery('#easypack_api_change').val() == '1' ) {
					jQuery('#easypack_returns_page').closest('table').prev().css('display','none');
					jQuery('#easypack_returns_page').closest('table').css('display','none');
					jQuery('#easypack_sender_first_name').closest('table').prev().css('display','none');
					jQuery('#easypack_sender_first_name').closest('table').css('display','none');
					jQuery('#easypack_default_send_method').closest('table').prev().css('display','none');
					jQuery('#easypack_default_send_method').closest('table').css('display','none');
					jQuery('#easypack_dispatch_point_name').closest('table').prev().css('display','none');
					jQuery('#easypack_dispatch_point_name').closest('table').css('display','none');
					jQuery('#easypack_tax_status').closest('table').prev().css('display','none');
					jQuery('#easypack_tax_status').closest('table').css('display','none');
					jQuery("#mainform :input").each(function(){
						 var input = jQuery(this);
						 if ( !input.is(":visible") ) {
							 input.attr('required',false);
						 }
					});
				}
				else {
				}
			}

			var api_country = jQuery('#easypack_api_country').val();
			jQuery('#easypack_api_country').change(function(){
				if (api_country != jQuery('#easypack_api_country').val() ) {
					if ( api_country != '--' && !confirm("<?php _e('Are you sure to change the country?', EasyPack::$text_domain ); ?>") ) {
						jQuery('#easypack_api_country').val(api_country);
//						jQuery('#easypack_api_country').change();
					}
					jQuery('#easypack_api_change').val('1');
					api_country = jQuery('#easypack_api_country').val();
					easypack_country_change();
					easypack_api_change();
				}
			})
			jQuery('#easypack_login').change(function(){
				jQuery('#easypack_api_change').val('1');
				easypack_api_change();
			})
			jQuery('#easypack_token').change(function(){
				jQuery('#easypack_api_change').val('1');
				easypack_api_change();
			})
			jQuery('#easypack_login').keyup(function(){
				if ( easypack_login != jQuery('#easypack_login').val() ) {
					jQuery('#easypack_api_change').val('1');
					easypack_api_change();
				}
			})
			jQuery('#easypack_token').keyup(function(){
				if ( easypack_token != jQuery('#easypack_token').val() ) {
					jQuery('#easypack_api_change').val('1');
					easypack_api_change();
				}
			})
			var easypack_token = jQuery('#easypack_token').val();
			var easypack_login = jQuery('#easypack_login').val();
			easypack_country_change();
			easypack_api_change();
			jQuery(document).ready(function() {
				easypack_country_change();
				easypack_api_change();
			});
//			easypack_address_fields();
//			easypack_send_options();
		</script>
		<?php
	}

	/**
	 * Get settings array
	 *
	 * @return array
	 */
	public function get_settings() {

		$parcel_machines = EasyPack_API()->machines_options();

		$dispatch_points = EasyPack_API()->customer_dispatch_points_options();

		$send_methods = array(
							'parcel_machine'	=> __( 'Parcel Locker', EasyPack::$text_domain ),
							'courier'			=> __( 'Courier', 		EasyPack::$text_domain ),
						);

		$settings =

			array(

				array( 'title' => __( 'Country', EasyPack::$text_domain ), 'type' => 'title', 'desc' => '', 'id' => 'country_options' ),

				array(
					'title'    => __( 'Country', EasyPack::$text_domain ),
					'id'       => 'easypack_api_country',
					'default'  => '--',
					'type'     => 'select',
//					'class'    => 'wc-enhanced-select',
					'css'      => 'min-width: 300px;',
					'desc_tip' => __( 'To edit click API URL', EasyPack::$text_domain ),
					'options'  => array(
							'--'		=> __( 'Select country',	EasyPack::$text_domain ),
							'ca'		=> __( 'Canada', 			EasyPack::$text_domain ),
							'fr'		=> __( 'France', 			EasyPack::$text_domain ),
							'it'		=> __( 'Italy', 			EasyPack::$text_domain ),
							'pl'		=> __( 'Poland', 			EasyPack::$text_domain ),
/*							
							'test-ca'	=> __( 'Test - Canada', 	EasyPack::$text_domain ),
							'test-fr'	=> __( 'Test - France', 	EasyPack::$text_domain ),
							'test-it'	=> __( 'Test - Italy', 		EasyPack::$text_domain ),
							'test-pl'	=> __( 'Test - Poland', 	EasyPack::$text_domain ),
*/							
					)
				),

				array( 'type' => 'sectionend', 'id' => 'country_options'),

				array( 'title' => __( 'Logging in', EasyPack::$text_domain ), 'type' => 'title', 'desc' => '', 'id' => 'general_options' ),

				array(
					'title'    			=> __( 'API URL', EasyPack::$text_domain ),
					'id'       			=> 'easypack_api_url',
					'css'      			=> 'min-width:300px;',
					'default'  			=> 'https://api-pl.easypack24.net/v4',
					'type'     			=> 'text',
					'desc_tip' 			=>  false,
					'class'				=> 'easypack-api-url',
					'custom_attributes' => array( 'required' => 'required' ),
				),

				array(
					'title'    			=> __( 'GEO widget URL', EasyPack::$text_domain ),
					'id'       			=> 'easypack_geowidget_url',
					'css'      			=> 'min-width:300px;',
					'default'  			=> '',
					'type'     			=> 'text',
					'desc_tip' 			=>  __( 'Leave blank for standard GEO widget', EasyPack::$text_domain ),
					'class'				=> 'easypack-geowidget-url',
				),

				array(
					'title'    	=> __( 'API URL Cross Border', EasyPack::$text_domain ),
					'id'       	=> 'easypack_crossborder_api_url',
					'css'      	=> 'min-width:300px;',
					'default'  	=> 'https://test-api-xborder-inpost.sheepla.com',
					'type'     	=> 'text',
					'desc_tip' 	=>  false,
				),

				array(
					'title'    			=> __( 'Login', EasyPack::$text_domain ),
					'id'       			=> 'easypack_login',
					'css'      			=> 'min-width:300px;',
					'default'  			=> '',
					'type'     			=> 'text',
					'desc_tip' 			=>  false,
					'custom_attributes' => array( 'required' => 'required' ),
				),

				array(
					'title'				=> __( 'Token', EasyPack::$text_domain ),
					'id'       			=> 'easypack_token',
					'css'      			=> 'min-width:300px;',
					'default'  			=> '',
					'type'     			=> 'text',
					'desc_tip' 			=>  false,
					'custom_attributes' => array( 'required' => 'required' ),
				),

				array(
					'title'    	=> __( 'Password', EasyPack::$text_domain ),
					'id'       	=> 'easypack_crossborder_password',
					'css'      	=> 'min-width:300px;',
					'default'  	=> '',
					'type'     	=> 'text',
					'desc_tip' 	=>  __( 'Required for Cross Border', EasyPack::$text_domain ) ,
				),

				array( 'type' => 'sectionend', 'id' => 'general_options'),

/*
				array( 'title' => __( 'Cross Border', EasyPack::$text_domain ), 'type' => 'title', 'desc' => '', 'id' => 'crossborder_options' ),

				array(
					'title'    	=> __( 'Login', EasyPack::$text_domain ),
					'id'       	=> 'easypack_crossborder_login',
					'css'      	=> 'min-width:300px;',
					'default'  	=> '',
					'type'     	=> 'text',
					'desc_tip' 	=>  false,
				),
*/

/*
				array(
					'title'    	=> __( 'Client ID', EasyPack::$text_domain ),
					'id'       	=> 'easypack_crossborder_client_id',
					'css'      	=> 'min-width:300px;',
					'default'  	=> '',
					'type'     	=> 'text',
					'desc_tip' 	=>  false,
				),

				array(
					'title'    	=> __( 'Client secret', EasyPack::$text_domain ),
					'id'       	=> 'easypack_crossborder_client_secret',
					'css'      	=> 'min-width:300px;',
					'default'  	=> '',
					'type'     	=> 'text',
					'desc_tip' 	=>  false,
				),

				array(
					'title'		=> __( 'Token', EasyPack::$text_domain ),
					'id'       	=> 'easypack_crossborder_token',
					'css'      	=> 'min-width:300px;',
					'default'  	=> '',
					'type'     	=> 'text',
					'desc_tip' 	=>  false,
				),

*/

//				array( 'type' => 'sectionend', 'id' => 'crossborder_options'),

				array( 'title' => __( 'Tax', EasyPack::$text_domain ), 'type' => 'title', 'desc' => '', 'id' => 'tax_options' ),

				array(
					'title'		=> __( 'Tax Status', EasyPack::$text_domain ),
					'id'       	=> 'easypack_tax_status',
					'type'		=> 'select',
					'css'      	=> 'min-width:300px;',
					'class'    	=> 'wc-enhanced-select',
					'default' 	=> 'taxable',
					'options'	=> array(
							'taxable' 	=> __( 'Taxable', EasyPack::$text_domain ),
							'none' 		=> _x( 'None', 'Tax status', EasyPack::$text_domain )
					)
				),

				array( 'type' => 'sectionend', 'id' => 'tax_options'),

				array( 'title' => __( 'Returns', EasyPack::$text_domain ), 'type' => 'title', 'desc' => '', 'id' => 'returns_options' ),

				array(
					'title'		=> __( 'Select page', EasyPack::$text_domain ),
					'id'       	=> 'easypack_returns_page',
					'type'		=> 'single_select_page',
					'css'      	=> 'min-width:300px;',
					'class'    	=> 'wc-enhanced-select',
					'args'		=> array( 'option_none_value' => -1, 'show_option_none' => __( 'None', EasyPack::$text_domain ) ),
					'desc_tip'	=> __( 'If a returns page is not selected then this option will not be available in the client\'s "My Account".', EasyPack::$text_domain ),
				),

				array(
					'title'		=> '',
					'id'       	=> 'easypack_returns_page_create',
					'desc'		=> __( 'Create new page', EasyPack::$text_domain ),
					'desc_tip' 	=> __( 'Create a new page to deal with returns.', EasyPack::$text_domain ),
					'type'		=> 'button',
					'class'    	=> 'button',
					'content'	=> __( 'Create new page', EasyPack::$text_domain ),
				),

				array( 'type' => 'sectionend', 'id' => 'returns_options'),

				array( 'title' => __( 'Send options', EasyPack::$text_domain ), 'type' => 'title', 'desc' => '', 'id' => 'send_options' ),

				array(
					'title'    	=> __( 'Default package size', EasyPack::$text_domain ),
					'id'       	=> 'easypack_default_package_size',
					'type'     	=> 'select',
					'class'    	=> 'wc-enhanced-select',
					'css'      	=> 'min-width: 300px;',
					'desc_tip' 	=> false,
					'default'	=> 'A',
					'options'  	=> EasyPack()->get_package_sizes()
				),

				array(
					'title'    	=> __( 'Default send method', EasyPack::$text_domain ),
					'id'       	=> 'easypack_default_send_method',
					'type'     	=> 'select',
					'class'    	=> 'wc-enhanced-select',
					'css'      	=> 'min-width: 300px;',
					'desc_tip' 	=> false,
					'default'	=> 'P',
					'options'  	=> $send_methods
				),

				array(
					'title'    	=> __( 'Default send parcel locker', EasyPack::$text_domain ),
					'id'       	=> 'easypack_default_machine_id',
					'type'     	=> 'select',
					'class'    	=> 'wc-enhanced-select',
					'css'      	=> 'min-width: 300px;',
					'desc_tip' 	=> false,
					'default'	=> 'P',
					'options'  	=> $parcel_machines
				),

				array( 'type' => 'sectionend', 'id' => 'send_options'),

				array( 'title' => __( 'Dispatch point', EasyPack::$text_domain ), 'type' => 'title', 'desc' => '', 'id' => 'dispatch_point_options' ),

				array(
					'title'    	=> __( 'Default dispatch point', EasyPack::$text_domain ),
					'id'       	=> 'easypack_default_dispatch_point',
					'type'     	=> 'select',
					'class'    	=> 'wc-enhanced-select1',
					'css'      	=> 'min-width: 300px;',
					'desc_tip' 	=> false,
					'default'	=> 'P',
					'options'  	=> $dispatch_points
				),

				array(
					'title'    			=> __( 'Dispatch point name', EasyPack::$text_domain ),
					'id'       			=> 'easypack_dispatch_point_name',
					'css'      			=> 'min-width:300px;',
					'default'  			=> '',
					'type'     			=> 'text',
					'desc_tip' 			=>  false,
					'custom_attributes' => array( 'required' => 'required' ),
				),

				array(
					'title'    			=> __( 'Email', EasyPack::$text_domain ),
					'id'       			=> 'easypack_dispatch_point_email',
					'css'      			=> 'min-width:300px;',
					'default'  			=> '',
					'type'     			=> 'email',
					'desc_tip' 			=>  false,
					'custom_attributes' => array( 'required' => 'required' ),
				),

				array(
					'title'    			=> __( 'Phone', EasyPack::$text_domain ),
					'id'       			=> 'easypack_dispatch_point_phone',
					'css'      			=> 'min-width:300px;',
					'default'  			=> '',
					'type'     			=> 'text',
					'desc_tip' 			=>  false,
					'custom_attributes' => array( 'required' => 'required' ),
				),

				array(
					'title'    	=> __( 'Office hours', EasyPack::$text_domain ),
					'id'       	=> 'easypack_dispatch_point_office_hours',
					'css'      	=> 'min-width:300px;',
					'default'  	=> '',
					'type'     	=> 'text',
					'desc_tip' 	=>  __( 'Example: 09:00-05:00' , EasyPack::$text_domain ),
				),

				array(
					'title'    			=> __( 'Street', EasyPack::$text_domain ),
					'id'       			=> 'easypack_dispatch_point_street',
					'css'      			=> 'min-width:300px;',
					'default'  			=> '',
					'type'     			=> 'text',
					'desc_tip' 			=>  false,
					'custom_attributes' => array( 'required' => 'required' ),
				),

				array(
					'title'    			=> __( 'Building no', EasyPack::$text_domain ),
					'id'       			=> 'easypack_dispatch_point_building_no',
					'css'      			=> 'min-width:300px;',
					'default'  			=> '',
					'type'     			=> 'text',
					'desc_tip' 			=>  false,
					'custom_attributes' => array( 'required' => 'required' ),
				),

				array(
					'title'    	=> __( 'Flat no', EasyPack::$text_domain ),
					'id'       	=> 'easypack_dispatch_point_flat_no',
					'css'      	=> 'min-width:300px;',
					'default'  	=> '',
					'type'     	=> 'text',
					'desc_tip' 	=>  false,
				),

				array(
					'title'    			=> __( 'Post code', EasyPack::$text_domain ),
					'id'       			=> 'easypack_dispatch_point_post_code',
					'css'      			=> 'min-width:300px;',
					'default'  			=> '',
					'type'     			=> 'text',
//					'desc_tip' 			=>  __( 'Example: 01-011', EasyPack::$text_domain ),
					'custom_attributes' => array( 'required' => 'required' ),
				),

				array(
					'title'    			=> __( 'City', EasyPack::$text_domain ),
					'id'       			=> 'easypack_dispatch_point_city',
					'css'      			=> 'min-width:300px;',
					'default'  			=> '',
					'type'     			=> 'text',
					'desc_tip' 			=>  false,
					'custom_attributes' => array( 'required' => 'required' ),
				),

				array( 'type' => 'sectionend', 'id' => 'dispatch_point_options'),

				array( 'title' => __( 'Sender', EasyPack::$text_domain ), 'type' => 'title', 'desc' => '', 'id' => 'sender_options' ),

				array(
					'title'    			=> __( 'First Name', EasyPack::$text_domain ),
					'id'       			=> 'easypack_sender_first_name',
					'css'      			=> 'min-width:300px;',
					'default'  			=> '',
					'type'     			=> 'text',
					'desc_tip' 			=>  false,
					'custom_attributes' => array( 'required' => 'required' ),
				),

				array(
					'title'    			=> __( 'Last Name', EasyPack::$text_domain ),
					'id'       			=> 'easypack_sender_last_name',
					'css'      			=> 'min-width:300px;',
					'default'  			=> '',
					'type'     			=> 'text',
					'desc_tip' 			=>  false,
					'custom_attributes' => array( 'required' => 'required' ),
				),

				array(
					'title'    	=> __( 'Company Name', EasyPack::$text_domain ),
					'id'       	=> 'easypack_sender_company_name',
					'css'      	=> 'min-width:300px;',
					'default'  	=> '',
					'type'     	=> 'text',
					'desc_tip' 	=>  false,
				),

				array(
					'title'    			=> __( 'Street', EasyPack::$text_domain ),
					'id'       			=> 'easypack_sender_street',
					'css'      			=> 'min-width:300px;',
					'default'  			=> '',
					'type'     			=> 'text',
					'desc_tip' 			=>  false,
//					'custom_attributes' => array( 'required' => 'required' ),
				),

				array(
					'title'    			=> __( 'Building no', EasyPack::$text_domain ),
					'id'       			=> 'easypack_sender_building_no',
					'css'      			=> 'min-width:300px;',
					'default'  			=> '',
					'type'     			=> 'text',
					'desc_tip' 			=>  false,
//					'custom_attributes' => array( 'required' => 'required' ),
				),

				array(
					'title'    	=> __( 'Flat no', EasyPack::$text_domain ),
					'id'       	=> 'easypack_sender_flat_no',
					'css'      	=> 'min-width:300px;',
					'default'  	=> '',
					'type'     	=> 'text',
					'desc_tip' 	=>  false,
				),

				array(
					'title'    			=> __( 'Post code', EasyPack::$text_domain ),
					'id'       			=> 'easypack_sender_post_code',
					'css'      			=> 'min-width:300px;',
					'default'  			=> '',
					'type'     			=> 'text',
//					'desc_tip' 	=>  __( 'Example: 01-011', EasyPack::$text_domain ),
//					'custom_attributes' => array( 'required' => 'required' ),
				),


				array(
					'title'    			=> __( 'Address 1', EasyPack::$text_domain ),
					'id'       			=> 'easypack_sender_address1',
					'css'      			=> 'min-width:300px;',
					'default'  			=> '',
					'type'     			=> 'text',
					'desc_tip' 			=>  false,
//					'custom_attributes' => array( 'required' => 'required' ),
				),

				array(
					'title'    			=> __( 'Address 2', EasyPack::$text_domain ),
					'id'       			=> 'easypack_sender_address2',
					'css'      			=> 'min-width:300px;',
					'default'  			=> '',
					'type'     			=> 'text',
					'desc_tip' 			=>  false,
//					'custom_attributes' => array( 'required' => 'required' ),
				),

				array(
					'title'    			=> __( 'Post code', EasyPack::$text_domain ),
					'id'       			=> 'easypack_sender_postal_code',
					'css'      			=> 'min-width:300px;',
					'default'  			=> '',
					'type'     			=> 'text',
//					'desc_tip' 	=>  __( 'Example: 01-011', EasyPack::$text_domain ),
//					'custom_attributes' => array( 'required' => 'required' ),
				),

				array(
					'title'    			=> __( 'City', EasyPack::$text_domain ),
					'id'       			=> 'easypack_sender_city',
					'css'      			=> 'min-width:300px;',
					'default'  			=> '',
					'type'     			=> 'text',
					'desc_tip' 			=>  false,
					'custom_attributes' => array( 'required' => 'required' ),
				),

				array(
					'title'    			=> __( 'Email', EasyPack::$text_domain ),
					'id'       			=> 'easypack_sender_email',
					'css'      			=> 'min-width:300px;',
					'default'  			=> '',
					'type'     			=> 'email',
					'desc_tip' 			=>  false,
					'custom_attributes' => array( 'required' => 'required' ),
				),

				array(
					'title'    			=> __( 'Phone', EasyPack::$text_domain ),
					'id'       			=> 'easypack_sender_phone',
					'css'      			=> 'min-width:300px;',
					'default'  			=> '',
					'type'     			=> 'text',
					'desc_tip' 			=>  false,
					'custom_attributes' => array( 'required' => 'required' ),
				),

				array( 'type' => 'sectionend', 'id' => 'sender_options'),
		);

		return $settings;
	}

	/**
	 * Save settings
	 */
	public function save() {

		$settings = $this->get_settings();

		WC_Admin_Settings::save_fields( $settings );

		EasyPack_API()->clear_cache();

		CrossBorder_API()->clear_cache();

		$easypack_api_change = $_REQUEST['easypack_api_change'];

		delete_option( 'easypack_api_error_login' );

		delete_option( 'crossborder_api_error_message' );

		if ( $easypack_api_change == '1' ) {
			try {
				EasyPack_API()->ping();
				$customer = EasyPack_API()->customer();
				update_option( 'easypack_sender_first_name', $customer['first_name'] );
				update_option( 'easypack_sender_last_name', $customer['last_name'] );
				update_option( 'easypack_sender_company_name', $customer['company_name'] );
				update_option( 'easypack_sender_email', $customer['email'] );
				update_option( 'easypack_sender_phone', $customer['phone'] );
				update_option( 'easypack_default_machine_id', $customer['default_machine_id'] );
				if ( EasyPack_API()->api_country() == 'CA' ) {
					update_option( 'easypack_sender_address1', $customer['address']['address1'] );
					update_option( 'easypack_sender_address2', $customer['address']['address2'] );
					update_option( 'easypack_sender_postal_code', $customer['address']['postal_code'] );
					update_option( 'easypack_sender_city', $customer['address']['city'] );
				}
				else {
					update_option( 'easypack_sender_street', $customer['address']['street'] );
					update_option( 'easypack_sender_building_no', $customer['address']['building_no'] );
					update_option( 'easypack_sender_flat_no', $customer['address']['flat_no'] );
					update_option( 'easypack_sender_post_code', $customer['address']['post_code'] );
					update_option( 'easypack_sender_city', $customer['address']['city'] );
				}
				if (EasyPack_API()->api_country() == 'PL' ) {
					$easypack_default_dispatch_point = get_option( 'easypack_default_dispatch_point', '' );
					$dispatch_point = false;
					if ( $easypack_default_dispatch_point != '' && $easypack_default_dispatch_point != '-1' ) {
						$dispatch_point = EasyPack_API()->dispatch_point( $easypack_default_dispatch_point );
					}
					else {
						$dispatch_points = EasyPack_API()->customer_dispatch_points();
						$dispatch_point = $dispatch_point[0];
					}
					if ( $dispatch_point ) {
						update_option( 'easypack_dispatch_point_name', $dispatch_point['name'] );
						update_option( 'easypack_dispatch_point_email', $dispatch_point['email'] );
						update_option( 'easypack_dispatch_point_phone', $dispatch_point['phone'] );
						update_option( 'easypack_dispatch_point_office_hours', $dispatch_point['office_hours'] );
						update_option( 'easypack_dispatch_point_street', $dispatch_point['address']['street'] );
						update_option( 'easypack_dispatch_point_building_no', $dispatch_point['address']['building_no'] );
						if ( isset( $dispatch_point['address']['flat_no'] ) ) {
							update_option( 'easypack_dispatch_point_flat_no', $dispatch_point['address']['flat_no'] );
						}
						else {
							update_option( 'easypack_dispatch_point_flat_no', '' );
						}
						update_option( 'easypack_dispatch_point_post_code', $dispatch_point['address']['post_code'] );
						update_option( 'easypack_dispatch_point_city', $dispatch_point['address']['city'] );
					}
				}
			}
			catch ( Exception $e ) {
				update_option( 'easypack_api_error_message', $e->getMessage() );
				update_option( 'easypack_api_error_login', '1' );
				?>
				<div class="error">
					<p>
						<?php _e( 'InPost API configuration error: ', EasyPack::$text_domain ); ?>
						<b><?php echo $e->getMessage(); ?></b>.<br/>
					</p>
				</div>
				<?php
			}
		}


		update_option( 'easypack_crossborder_login', get_option( 'easypack_login' ) );

		update_option( 'easypack_crossborder_client_id', 'f42596c5' );
		update_option( 'easypack_crossborder_client_secret', '4c8a5b3054e54dc9bb28040f4d537857' );

		CrossBorder_API()->clear_cache();

		if ( $easypack_api_change == '0' ) {

			$country = strtoupper( str_replace( 'test-', '', get_option( 'easypack_api_country' ,'PL' ) ) );

			$crossborder_password = get_option( 'easypack_crossborder_password', false );

			delete_option( 'crossborder_api_error_message' );
			if ( $crossborder_password && $country == 'PL' ) {

				try {
					$me = CrossBorder_API()->ping();
					delete_option( 'crossborder_api_error_message' );
				}
				catch ( Exception $e ) {
					update_option( 'crossborder_api_error_message', $e->getMessage() );
					?>
					<div class="error">
						<p>
							<?php _e( 'InPost Cross Border API configuration error: ', EasyPack::$text_domain ); ?>
							<b><?php echo $e->getMessage(); ?></b>.<br/>
						</p>
					</div>
					<?php
				}
			}

			try {
				EasyPack_API()->ping();
				delete_option( 'easypack_api_error_message' );
			}
			catch ( Exception $e ) {
				update_option( 'easypack_api_error_message', $e->getMessage() );
				?>
				<div class="error">
					<p>
						<?php _e( 'InPost API configuration error: ', EasyPack::$text_domain ); ?>
						<b><?php echo $e->getMessage(); ?></b>.<br/>
					</p>
				</div>
				<?php
				return;
			}

			$args = array();
			$args['first_name'] 			= get_option('easypack_sender_first_name');
			$args['last_name'] 				= get_option('easypack_sender_last_name');
			$args['company_name'] 			= get_option('easypack_sender_company_name');
			$args['phone'] 					= get_option('easypack_sender_phone');
			$args['email'] 					= get_option('easypack_sender_email');
			$args['default_machine_id'] 	= get_option( 'easypack_default_machine_id' );
			$args['address'] 				= array();
			if ( EasyPack_API()->api_country() == 'CA' ) {
				$args['address']['address1']    = get_option('easypack_sender_address1');
				$args['address']['address2']    = get_option('easypack_sender_address2');
				$args['address']['city'] 		= get_option('easypack_sender_city');
				$args['address']['postal_code']	= get_option('easypack_sender_postal_code');
			}
			else {
				$args['address']['building_no'] = get_option('easypack_sender_building_no');
				$args['address']['flat_no'] 	= get_option('easypack_sender_flat_no');
				$args['address']['street'] 		= get_option('easypack_sender_street');
				$args['address']['city'] 		= get_option('easypack_sender_city');
				$args['address']['post_code'] 	= get_option('easypack_sender_post_code');
			}

			if ( EasyPack_API()->api_country() != 'PL' ) {
				unset( $args['default_machine_id'] );
			}
			if ( $args['default_machine_id'] == '' ) {
				unset( $args['default_machine_id'] );
			}


			try {
				EasyPack_API()->update_customer( $args );
			}
			catch ( Exception $e ) {
				?>
				<div class="error">
					<p><?php _e( 'There are some validation errors on sender data:', EasyPack::$text_domain ); ?></p>
					<p><?php echo $e->getMessage(); ?></p>
				</div>
				<?php
			}

			if ( strtoupper( str_replace( 'test-', '', get_option( 'easypack_api_country', 'pl' ) ) ) == 'PL' ) {

				$args = array();
				$args['name'] 					= get_option('easypack_dispatch_point_name');
				$args['phone'] 					= get_option('easypack_dispatch_point_phone');
				$args['email'] 					= get_option('easypack_dispatch_point_email');
				$args['office_hours'] 			= get_option('easypack_dispatch_point_office_hours');
				$args['address'] 				= array();
				$args['address']['building_no'] = get_option('easypack_dispatch_point_building_no');
				$args['address']['flat_no'] 	= get_option('easypack_dispatch_point_flat_no');
				$args['address']['street'] 		= get_option('easypack_dispatch_point_street');
				$args['address']['city'] 		= get_option('easypack_dispatch_point_city');
				$args['address']['post_code'] 	= get_option('easypack_dispatch_point_post_code');

				try {
					EasyPack_API()->update_default_dispatch_point( $args );
				}
				catch ( Exception $e ) {
				?>
					<div class="error">
						<p><?php _e( 'There are some validation errors on default dispatch point data:', EasyPack::$text_domain ); ?></p>
						<p><?php echo $e->getMessage(); ?></p>
					</div>
				<?php
				}
			}
		}
	}

}

endif;

return new EasyPack_Settings_General();