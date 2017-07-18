<?php
/**
 * EasyPack Shipment Manager
*
* @author      WPDesk
* @category    Admin
* @package     EasyPack/Admin
* @version     2.1.0
*/

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( ! class_exists( 'EasyPack_Shipment_Manager' ) ) :

/**
 * EasyPack_Shipment_Manager
*/
class EasyPack_Shipment_Manager {

	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'admin_menu' ) );
	}

	public static function admin_menu() {
		global $menu;
		$menu_pos = 56;
		while ( isset( $menu[$menu_pos] ) ) {
			$menu_pos++;
		}
		if ( EasyPack_API()->api_country() != '--' ) {
			$icon_svg = 'data:image/svg+xml;base64,PD94bWwgdmVyc2lvbj0iMS4wIj8+Cjxzdmcgd2lkdGg9IjI0Ni45OTk5OTk5OTk5OTk5NyIgaGVpZ2h0PSIyMjEiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyIgeG1sbnM6c3ZnPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+CiA8Zz4KICA8dGl0bGU+TGF5ZXIgMTwvdGl0bGU+CiAgPGcgaWQ9InN2Z18xIiBzdHJva2U9Im51bGwiPgogICA8cGF0aCBpZD0ic3ZnXzciIGQ9Im0xMDEuNTYxMDQsMTEwLjY3NDkyYzAsMCAtMTEuNjQ2MzcsNC41MDMxIC0yNi4wMTU5LDQuNTAzMWMtMTQuMzY4MTQsMCAtMjYuMDE1OSwtNC41MDMxIC0yNi4wMTU5LC00LjUwMzFzMTEuNjQ3NzUsLTQuNTAwMzMgMjYuMDE1OSwtNC41MDAzM2MxNC4zNjk1MywwIDI2LjAxNTksNC41MDAzMyAyNi4wMTU5LDQuNTAwMzMiIGZpbGw9IiNGRkNDMDAiIHN0cm9rZT0ibnVsbCIvPgogICA8cGF0aCBpZD0ic3ZnXzgiIGQ9Im0xMzcuNTM0NjUsNDQuNDYwM2MwLDAgLTEwLjMyMDA2LC02Ljk0OTY1IC0xOC4zNTM5OSwtMTguNjI3ODNjLTguMDMzOTQsLTExLjY3NjggLTEwLjc0MDUsLTIzLjY1OTI0IC0xMC43NDA1LC0yMy42NTkyNHMxMC4zMTg2OCw2Ljk0ODI3IDE4LjM1Mzk5LDE4LjYyNTA3YzguMDMzOTQsMTEuNjc5NTYgMTAuNzQwNSwyMy42NjIwMSAxMC43NDA1LDIzLjY2MjAxIiBmaWxsPSIjRkZDQzAwIiBzdHJva2U9Im51bGwiLz4KICAgPHBhdGggaWQ9InN2Z185IiBkPSJtMTExLjE4NjgzLDczLjAzMjAxYzAsMCAtMTIuNDM4ODQsLTEuMzg1NzggLTI1LjEyNTI0LC03Ljk5OTM2Yy0xMi42ODY0LC02LjYxMjIgLTIwLjgxNDM4LC0xNS45NDc1NSAtMjAuODE0MzgsLTE1Ljk0NzU1czEyLjQzODg0LDEuMzg1NzggMjUuMTI1MjQsNy45OTkzNmMxMi42ODY0LDYuNjEyMiAyMC44MTQzOCwxNS45NDc1NSAyMC44MTQzOCwxNS45NDc1NSIgZmlsbD0iI0ZGQ0MwMCIgc3Ryb2tlPSJudWxsIi8+CiAgIDxwYXRoIGlkPSJzdmdfMTAiIGQ9Im0xMzUuNzU4ODYsMTMwLjc2ODc1YzcuNTI5MTMsLTIuMjg0NzQgMTMuOTA0ODMsLTUuNjE5MTkgMTMuOTA0ODMsLTUuNjE5MTlzLTE3LjczNTc5LC00LjkzMDQ1IC0xNi40MzU3NSwtMjMuNDU0NTVjNC4wNzI5OCwtMzAuMzk3MjkgMjguNjgzNzQsLTU0LjI2MTIyIDU5LjQ5NDU1LC01OC4xMDMyM2MtMy4yNjgwNiwtMC40NTA4NiAtNi42MDUyOCwtMC42ODczNiAtMTAuMDAxOTcsLTAuNjcyMTVjLTM4LjEwODk4LDAuMTY4NzMgLTY4Ljg2MzA5LDMwLjU5NTA2IC02OC42OTE2LDY3Ljk1NzIyYzAuMTcwMTEsMzcuMzYwNzcgMzEuMjA0OTcsNjcuNTEwNSA2OS4zMTI1Nyw2Ny4zNDMxNmMzLjE3ODE3LC0wLjAxMzgzIDYuMzAxMDIsLTAuMjU3MjQgOS4zNjMwMSwtMC42Nzc2OGMtMjcuMDQwNzEsLTMuMzc4NzEgLTQ5LjA1MDAyLC0yMi4wNzE1NCAtNTYuOTQ1NjUsLTQ2Ljc3MzU3bDAuMDAwMDEsLTAuMDAwMDF6IiBmaWxsPSIjRkZDQzAwIi8+CiAgIDxwYXRoIGlkPSJzdmdfMTEiIGQ9Im0xMzcuNTM0NjUsMTc2LjYzMjNjMCwwIC0xMC4zMjAwNiw2Ljk0OTY1IC0xOC4zNTM5OSwxOC42MjkyMWMtOC4wMzM5NCwxMS42NzU0MSAtMTAuNzQwNSwyMy42NjA2MiAtMTAuNzQwNSwyMy42NjA2MnMxMC4zMTg2OCwtNi45NDk2NSAxOC4zNTM5OSwtMTguNjI3ODNjOC4wMzM5NCwtMTEuNjc2OCAxMC43NDA1LC0yMy42NjIwMSAxMC43NDA1LC0yMy42NjIwMSIgZmlsbD0iI0ZGQ0MwMCIgc3Ryb2tlPSJudWxsIi8+CiAgIDxwYXRoIGlkPSJzdmdfMTIiIGQ9Im0xMTEuMTg2ODMsMTQ4LjA2MTk3YzAsMCAtMTIuNDM4ODQsMS4zODU3OCAtMjUuMTI1MjQsNy45OTkzNmMtMTIuNjg2NCw2LjYxMDgxIC0yMC44MTQzOCwxNS45NDYxNyAtMjAuODE0MzgsMTUuOTQ2MTdzMTIuNDM4ODQsLTEuMzg1NzggMjUuMTI1MjQsLTcuOTk3OThzMjAuODE0MzgsLTE1Ljk0NzU1IDIwLjgxNDM4LC0xNS45NDc1NSIgZmlsbD0iI0ZGQ0MwMCIgc3Ryb2tlPSJudWxsIi8+CiAgPC9nPgogPC9nPgo8L3N2Zz4=';
			add_menu_page( __('InPost', EasyPack::$text_domain ), __('InPost', EasyPack::$text_domain ), 'manage_options', 'inpost', null, $icon_svg, $menu_pos );
			add_submenu_page( 'inpost', __( 'Shipments', EasyPack::$text_domain ), __( 'Shipments', EasyPack::$text_domain ), 'manage_options', 'easypack_shipment', array( __CLASS__, 'easypack_shipment' ) );
			if ( EasyPack_API()->api_country() == 'PL' ) {
				add_submenu_page( 'inpost', __( 'Dispatch Orders', EasyPack::$text_domain ), __( 'Dispatch Orders', EasyPack::$text_domain ), 'manage_options', 'edit.php?post_type=dispatch_order' );
			}
			remove_submenu_page( 'inpost', 'inpost' );
		}
	}

	public static function easypack_shipment() {

		require_once('class-shipment-manager-list-table.php' );

		$dispatch_points = EasyPack_API()->customer_dispatch_points_options();

		$dispatch_point = get_option( 'easypack_default_dispatch_point' );

		if ( EasyPack_API()->api_country() == 'PL' /* || EasyPack_API()->api_country() == 'CA' */ ) {
			$send_methods = array(
					'all' 				=> __( 'All', EasyPack::$text_domain ),
					'parcel_machine' 	=> __( 'Parcel Locker', EasyPack::$text_domain ),
					'courier' 			=> __( 'Courier', EasyPack::$text_domain ),
			);
		}
		else {
			$send_methods = array(
					'courier' 			=> __( 'Courier', EasyPack::$text_domain ),
			);
		}


		if ( isset( $_POST['easypack_create_manifest_input'] ) && $_POST['easypack_create_manifest_input'] == 1 ) {
			try {
				$dispatch_order_id = EasyPack_Dispatch_Orders()->create_dispatch_order( $_POST['easypack_dispatch_point'] );
				$manifests = EasyPack_Dispatch_Orders()->create_manifest( $_POST['easypack_dispatch_point'], $dispatch_order_id );
				$message = __('Created manifests ', EasyPack::$text_domain );
				foreach ( $manifests as $manifest ) {
					if ( $manifest['api'] == 'crossborder' ) {
						$url = site_url( '?easypack_download=1&crossborder_manifest=' . $manifest['post_id'] );
					}
					else {
						$url = site_url( '?easypack_download=1&easypack_manifest=' . $manifest['post_id'] );
					}
					$link = ' <a href="' . $url . '" target="blank" class="">' . $manifest['post_id'] . '</a>, ';
					$message .= $link;
				}
				$message = trim( trim( $message ), ',' );
				?>
					<div class="updated">
						<p><?php echo $message; ?></p>
					</div>
				<?php
			}
			catch ( Exception $e ) {
				$class = "error";
				$message = __( 'Error while creating manifest: ', EasyPack::$text_domain ) . $e->getMessage();
				echo "<div class=\"$class\"> <p>$message</p></div>";
			}
		}

		$send_method = 'courier';

		if ( isset( $_GET['send_method'] ) ) {
			$send_method = $_GET['send_method'];
		}

		$shipment_manager_list_table = new EasyPack_Shipment_Manager_List_Table( $send_method );

		include( 'views/html-shipment-manager.php' );
	}

}

endif;

EasyPack_Shipment_Manager::init();