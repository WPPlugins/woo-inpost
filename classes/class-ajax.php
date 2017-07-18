<?php
/**
 * EasyPack AJAX
*
* @author      WPDesk
* @category    Admin
* @package     EasyPack
* @version     2.1.0
*/

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( ! class_exists( 'EasyPack_AJAX' ) ) :

class EasyPack_AJAX {

	/**
	 * Ajax handler
	 */
	public static function init() {
		add_action( 'wp_ajax_easypack', array( __CLASS__, 'ajax_easypack' ) );
		add_action( 'admin_head', array( __CLASS__, 'wp_footer_easypack_nonce' ) );
	}

	public static function wp_footer_easypack_nonce() {
		?>
		<script type="text/javascript">
			var easypack_nonce = '<?php echo wp_create_nonce('easypack_nonce'); ?>';
		</script>
		<?php
	}

	public static function ajax_easypack() {
		check_ajax_referer( 'easypack_nonce', 'security' );

		if ( isset( $_POST['easypack_action'] ) ) {
			$action = $_POST['easypack_action'];
			if ( $action == 'returns_page_create' ) {
				self::create_returns_page();
			}
			if ( $action == 'dispatch_point' ) {
				self::dispatch_point();
			}
			if ( $action == 'parcel_machines_create_package' ) {
				self::parcel_machines_create_package();
			}
			if ( $action == 'parcel_machines_cancel_package' ) {
				self::parcel_machines_cancel_package();
			}
			if ( $action == 'parcel_machines_get_stickers' ) {
				self::parcel_machines_get_stickers();
			}
			if ( $action == 'parcel_machines_cod_get_payment_status' ) {
				self::parcel_machines_cod_get_payment_status();
			}
			if ( $action == 'parcel_machines_cod_create_package' ) {
				self::parcel_machines_cod_create_package();
			}
			if ( $action == 'parcel_machines_cod_cancel_package' ) {
				self::parcel_machines_cod_cancel_package();
			}
			if ( $action == 'crossborder_parcel_machines_create_package' ) {
				self::crossborder_parcel_machines_create_package();
			}
			if ( $action == 'crossborder_parcel_machines_processing' ) {
				self::crossborder_parcel_machines_processing();
			}
			if ( $action == 'crossborder_courier_create_package' ) {
				self::crossborder_courier_create_package();
			}
			if ( $action == 'crossborder_courier_processing' ) {
				self::crossborder_courier_processing();
			}
			if ( $action == 'easypack_dispatch_order' ) {
				self::easypack_dispatch_order();
			}
			if ( $action == 'easypack_create_stickers' ) {
				self::create_stickers();
			}
		}
	}

	public static function create_returns_page() {

		$postarr = array( 'post_type' => 'page' );
		$postarr['post_status'] = 'publish';

		$post_content = __( 'Returns page content here', EasyPack::$text_domain );;

		$postarr['post_title'] = __( 'InPost Returns', EasyPack::$text_domain );

		$country = $_POST['country'];

		$country = strtoupper( str_replace( 'test-', '', $country ) );

		if ( $country == 'CA' ) {
			$postarr['post_title'] = 'InPost Returns';
			$post_content = '';
			$post_content .= 'Returns available 24/7 from ANY parcel locker.<br/><br/>';
			$post_content .= 'Simple, convenient and no line-ups!<br/><br/>';
			$post_content .= '<a href="https://returns.inpost24.ca/" class="inpost_returns_button" target="_blank">' . 'Return now' . '</a>';
		}
		if ( $country == 'PL' ) {
			$postarr['post_title'] = 'Szybkie zwroty InPost';
			$post_content = '';
			$post_content .= 'Zwracaj zamówienia przez ponad 1 100 Paczkomatów w całej Polsce.<br/><br/>';
			$post_content .= 'Szybko, wygodnie, bez kolejek!<br/><br/>';
			$post_content .= '<a href="https://www.szybkiezwroty.pl/" class="inpost_returns_button" target="_blank">' . 'Przejdź do szybkich zwrotów' . '</a>';
		}

		$postarr['post_content'] = '<img style="border:none; float:right;" src="' . trailingslashit( EasyPack()->getPluginUrl() ) . 'assets/images/logo/small/white.png'  . '" />';
		$postarr['post_content'] .= $post_content;

		$page_id = wp_insert_post( $postarr );

		if ( $page_id ) {
			$page = get_page( $page_id );
			$ret = array( 'page_id' => $page_id );
			$ret['page_title'] = $page->post_title;
			$ret['message'] = __( 'Returns page created.', EasyPack::$text_domain );
			echo json_encode( $ret );
		}
		else {
			echo 0;
		}

		wp_die();
	}

	public static function dispatch_point() {
		$dispatch_point_name = $_POST['dispatch_point_name'];
		try {
			$dispatch_point = EasyPack_API()->dispatch_point( $dispatch_point_name );
			echo json_encode( $dispatch_point );
		}
		catch ( Exception $e ) {
			echo 0;
		}
		wp_die();
	}

	public static function parcel_machines_create_package() {
		EasyPack_Shippng_Parcel_Machines::ajax_create_package();
	}

	public static function parcel_machines_cancel_package() {
		EasyPack_Shippng_Parcel_Machines::ajax_cancel_package();
	}

	public static function parcel_machines_get_stickers() {
		EasyPack_Shippng_Parcel_Machines::ajax_get_stickers();
	}

	public static function parcel_machines_cod_get_payment_status() {
		EasyPack_Shippng_Parcel_Machines_COD::ajax_get_payment_status();
	}

	public static function parcel_machines_cod_create_package() {
		EasyPack_Shippng_Parcel_Machines_COD::ajax_create_package();
	}

	public static function parcel_machines_cod_cancel_package() {
		EasyPack_Shippng_Parcel_Machines_COD::ajax_cancel_package();
	}

	public static function crossborder_parcel_machines_create_package() {
		EasyPack_Shippng_Cross_Border_Parcel_Machines::ajax_create_package();
	}

	public static function crossborder_parcel_machines_processing() {
		EasyPack_Shippng_Cross_Border_Parcel_Machines::ajax_processing();
	}

	public static function crossborder_courier_create_package() {
		EasyPack_Shippng_Cross_Border_Courier::ajax_create_package();
	}

	public static function crossborder_courier_processing() {
		EasyPack_Shippng_Cross_Border_Courier::ajax_processing();
	}

	public static function easypack_dispatch_order() {
		EasyPack_Shippng_Parcel_Machines::ajax_dispatch_order();
	}

	public static function create_stickers() {
		EasyPack_Helper()->ajax_create_stickers();
	}

}

endif;

EasyPack_AJAX::init();