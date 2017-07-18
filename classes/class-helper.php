<?php
/**
 * EasyPack Helper
*
* @author      WPDesk
* @category
* @package     EasyPack
* @version     2.1.0
*/

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( ! class_exists( 'EasyPack_Helper' ) ) :

class EasyPack_Helper {

	protected static $instance;

	public function __construct()
	{
		add_filter( 'query_vars', array( $this, 'query_vars' ) );
		add_action( 'parse_request', array( $this, 'parse_request' ) );

		add_action( 'woocommerce_before_my_account', array( $this, 'woocommerce_before_my_account' ) );

		add_filter( 'woocommerce_screen_ids', array( $this, 'woocommerce_screen_ids' ) );

		add_action( 'admin_notices', array( $this, 'admin_notices') );

	}

	public static function EasyPack_Helper() {
		if (self::$instance === null) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	public function write_stickers_to_file( $stickers = array() ) {
		$temp_dir = trailingslashit( get_temp_dir() );
		if ( sizeof( $stickers ) == 1 ) {
			$temp_file = tempnam( $temp_dir, 'ep' );
			$fp = fopen( $temp_file, 'w');
			fwrite( $fp, $stickers[0] );
			fclose( $fp );
		}
		else {
			$files = array();
			foreach ( $stickers as $sticker ) {
				$temp_file = tempnam( $temp_dir, 'ep' );
				$fp = fopen( $temp_file, 'w');
				fwrite( $fp, $sticker );
				fclose( $fp );
				$files[] = $temp_file;
			}

			$temp_file = tempnam( $temp_dir, 'ep' );
			$pdf = new ConcatPdf();
			$pdf->setFiles( $files );
			$pdf->concat();
			$pdf->Output( $temp_file, 'F' );
			foreach ( $files as $file ) {
				unlink( $file );
			}
		}

		return $temp_file;
	}

	public function get_file( $file, $file_name, $content_type = '' ) {

		header( 'Content-type: ' . $content_type );
		header( 'Content-Disposition: attachment; filename="' . $file_name . '"' );
		header( 'Content-Transfer-Encoding: binary' );
		header( 'Content-Length: ' . filesize($file) );
		header( 'Accept-Ranges: bytes' );

		@readfile($file);

		unlink( $file );

	}

	public function ajax_create_stickers() {
		$ret = array( 'status' => 'ok' );

		$parcels = $_POST['parcels'];
		$ret['parcels'] = $parcels;

		$stickers = array();

		foreach ( $parcels as $parcel ) {
			$order_parcel = explode( '.', $parcel );
			$api = $order_parcel[0];
			$order_id = $order_parcel[1];
			$parcel_id = $order_parcel[2];
			$easypack_parcels = get_post_meta( $order_id, '_easypack_parcels', true );
			try {
				foreach ( $easypack_parcels as $key => $easypack_parcel ) {
					if ( $api == 'crossborder' ) {
						if ( $easypack_parcel['crossborder_data']['id'] == $parcel_id ) {
							$stickers[] = CrossBorder_API()->pdf_label( $easypack_parcel['crossborder_data']['id'], true );
						}
					}
					else {
						if ( $easypack_parcel['easypack_data']['id'] == $parcel_id ) {
							if ( $easypack_parcel['easypack_data']['status'] == 'created' ) {
								$easypack_data = EasyPack_API()->customer_parcel( $easypack_parcel['easypack_data']['id'] );
								if ( $easypack_data['status'] == 'created' ) {
									$easypack_data = EasyPack_API()->customer_parcel_pay( $easypack_data['id'] );
									$easypack_parcels[$key]['easypack_data'] = $easypack_data;
									$easypack_parcel['easypack_data'] = $easypack_data;
									update_post_meta( $order_id, '_easypack_parcels', $easypack_parcels );
									update_post_meta( $order_id, '_easypack_status', 'prepared' );
								}
							}
							$stickers[] = EasyPack_API()->customer_parcel_sticker( $easypack_parcel['easypack_data']['id'] );
						}
					}
				}
			}
			catch ( Exception $e ) {
				$ret['status'] = 'error';
				$ret['message'] = $e->getMessage();
			}
			$tmp_file = $this->write_stickers_to_file( $stickers );
		}

		$ret['file'] = basename($tmp_file);

		echo json_encode( $ret );
		wp_die();
	}

	/**
	* Allow for custom query variables
	*/
	public function query_vars( $query_vars )	{
		$query_vars[] = 'easypack_download';
		return $query_vars;
	}

	/**
	* Parse the request
	*/
	public function parse_request(&$wp)	{
		if( array_key_exists( 'easypack_download', $wp->query_vars))
		{
			if ( $_GET['easypack_parcel_machines_stickers'] == '1' ) {
				$cross_border = 0;
				if ( isset( $_GET['cross_border'] ) ) {
					$cross_border = $_GET['cross_border'];
				}
				if ( $cross_border ) {
					EasyPack_Shippng_Cross_Border_Parcel_Machines::get_stickers();
				}
				else {
					EasyPack_Shippng_Parcel_Machines::get_stickers();
				}
			}
			if ( isset( $_GET['easypack_file'] ) ) {
				$temp_dir = trailingslashit( get_temp_dir() );
				$file = $temp_dir . $_GET['easypack_file'];
				$this->get_file( $file, __( 'stickers', EasyPack::$text_domain ) . '_' . time() . '.pdf', 'application/pdf' );
			}
			if ( isset( $_GET['easypack_manifest'] ) ) {
				EasyPack_Dispatch_Orders()->create_easypack_manifest( $_GET['easypack_manifest'] );
			}
			if ( isset( $_GET['crossborder_manifest'] ) ) {
				EasyPack_Dispatch_Orders()->get_crossborder_manifest( $_GET['crossborder_manifest'] );
			}
			exit;
		}
	}

	public function get_api_url( $country = FALSE ) {
		if ( ! $country ) {
			$country = get_option('easypack_api_country');
		}
		$api_url = 'https://api-' . $country . '.easypack24.net';
		$api_url = str_replace( 'api-test', 'test-api', $api_url );
		return $api_url;
	}

	public function get_geowidget_src( $cod = FALSE, $country = FALSE ) {
		if ( ! $country ) {
			$country = get_option('easypack_api_country');
		}
		/* RM 11107 */
		if ( EasyPack_API()->api_country() == 'CA' ) {
			$cod = TRUE;
		}
		$cod_string = '&cod=FALSE';
		if ( $cod ) {
			$cod_string = '&cod=TRUE';
		}
		$geowidget_src = 'https://geowidget-' . $country . '.easypack24.net/dropdown.php?dropdown_name=parcel_machine_id' . $cod_string;
		$geowidget_src = str_replace( '-test-', '-', $geowidget_src );
		$easypack_geowidget_url = get_option( 'easypack_geowidget_url', '' );
		if ( EasyPack_API()->api_country() == 'CA' && $easypack_geowidget_url != '' ) {
			$geowidget_src = trailingslashit( $easypack_geowidget_url ) . 'dropdown.php?dropdown_name=parcel_machine_id' . $cod_string;
		}
		return $geowidget_src;
	}

	public function get_tracking_url( $country = FALSE ) {
		$urls = array(
						'pl' 	=> 'https://twoj.inpost.pl/pl/znajdz-przesylke?parcel=',
						'fr' 	=> 'https://inpost24.fr/fr/suivi-du-colis?parcel=',
						'it' 	=> 'https://inpost24.it/it/rintraccia-il-pacco?parcel=',
						'ca'	=> 'https://inpost24.ca/en/track-parcel?parcel=',
						'pl-cb'	=> 'https://twoj.inpost.pl/pl/przesylki/cross-border?parcel=', /* cross border */
		);
		if ( ! $country ) {
			$country = get_option('easypack_api_country');
		}
		$country = str_replace( 'test-', '', $country );
		return $urls[$country];
	}

	public function get_order_weight( $order ) {
		$weight = 0;
		if ( sizeof( $order->get_items() ) > 0 ) {
			foreach( $order->get_items() as $item ) {
				if ( $item['product_id'] > 0 ) {
					$_product = $order->get_product_from_item( $item );
					if ( ! $_product->is_virtual() ) {
						$weight += $_product->get_weight() * $item['qty'];
					}
				}
			}
		}
		return $weight;
	}

	public function get_weight_option( $weight, $options ) {
		$ret = -1;
		$options = array_reverse( $options, true );
		foreach ( $options as $val => $option ) {
			if ( floatval( $weight ) <= floatval( $val ) ) {
				$ret = $val;
			}
		}
		return $ret;
	}

	function get_easypack_parcel_data( $parcels, $id ) {
		$ret = false;
		foreach ( $parcels as $parcel ) {
			if ( $parcel['easypack_data']['id'] == $id ) {
				$ret = $parcel['easypack_data'];
			}
		}
		return $ret;
	}

	function get_crossborder_parcel_data( $parcels, $id ) {
		$ret = false;
		foreach ( $parcels as $parcel ) {
			if ( $parcel['crossborder_data']['id'] == $id ) {
				$ret = $parcel['crossborder_data'];
			}
		}
		return $ret;
	}

	function woocommerce_before_my_account() {
		if ( get_option( 'easypack_returns_page' ) && trim ( get_option( 'easypack_returns_page' ) ) != '' ) {
			$page = get_page( get_option( 'easypack_returns_page' ) );
			if ( $page ) {
				$img_src = EasyPack()->getPluginUrl(). '/assets/images/logo/small/white.png';
				$args = array( 'returns_page' => get_page_link( get_option( 'easypack_returns_page' ) ), 'returns_page_title' => $page->post_title, 'img_src' => $img_src );
				wc_get_template( 'myaccount/before-my-account.php', $args, '', plugin_dir_path( EasyPack()->getPluginFilePath() ) . 'templates/' );
			}
		}
	}

	function woocommerce_screen_ids( $screen_ids ) {
		$screen_ids[] = 'inpost_page_easypack_shipment';
		return $screen_ids;
	}

	function admin_notices() {
		$easypack_api_error_message = get_option( 'easypack_api_error_message', false );
		if ( $easypack_api_error_message && ! isset( $_POST['easypack_api_country'] ) ) {
			$screen = get_current_screen();
			$in_settings = false;
			if ( $screen->id == 'woocommerce_page_wc-settings' && isset( $_GET['tab'] ) && $_GET['tab'] == 'easypack_general' ) {
				$in_settings = true;
			}
			?>
			<div class="error">
				<p>
					<?php _e( 'InPost API configuration error: ', EasyPack::$text_domain ); ?>
					<b><?php echo $easypack_api_error_message; ?></b>.<br/>
					<?php if ( ! $in_settings ) : ?>
					<?php printf( __( 'Go to %sconfiguration%s to fix it.', EasyPack::$text_domain ), '<a href="' . admin_url('admin.php?page=wc-settings&tab=easypack_general') . '">', '</a>' ); ?>
					<?php endif; ?>
				</p>
			</div>
			<?php
		}

		$crossborder_api_error_message = get_option( 'crossborder_api_error_message', false );
		if ( $crossborder_api_error_message && ! isset( $_POST['easypack_api_country'] ) ) {
			$screen = get_current_screen();
			$in_settings = false;
			if ( $screen->id == 'woocommerce_page_wc-settings' && isset( $_GET['tab'] ) && $_GET['tab'] == 'easypack_general' ) {
				$in_settings = true;
			}
			?>
			<div class="error">
				<p>
					<?php _e( 'InPost Cross Border API configuration error: ', EasyPack::$text_domain ); ?>
					<b><?php echo $crossborder_api_error_message; ?></b>.<br/>
					<?php if ( ! $in_settings ) : ?>
					<?php printf( __( 'Go to %sconfiguration%s to fix it.', EasyPack::$text_domain ), '<a href="' . admin_url('admin.php?page=wc-settings&tab=easypack_general') . '">', '</a>' ); ?>
					<?php endif; ?>
				</p>
			</div>
			<?php
		}
	}

}

function EasyPack_Helper() {
	return EasyPack_Helper::EasyPack_Helper();
}

EasyPack_Helper();

endif;