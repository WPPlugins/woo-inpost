<?php
/**
 * EasyPack Dispatch Orders
*
* @author      WPDesk
* @category    Admin
* @package     EasyPack/Admin
* @version     2.1.0
*/

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( ! class_exists( 'EasyPack_Dispatch_Orders' ) ) :

/**
 * EasyPack_Dispatch_Orders
*/
class EasyPack_Dispatch_Orders {

	protected static $instance;

	public function __construct() {
		add_action( 'woocommerce_register_post_type', array( $this, 'register_post_types' ), 5 );

		add_action( 'manage_dispatch_order_posts_custom_column', array( $this, 'manage_dispatch_order_posts_custom_column' ), 10, 2 );

		add_filter( 'manage_edit-dispatch_order_columns', array( $this, 'manage_edit_dispatch_order_columns' ) ) ;

		add_filter( 'bulk_actions-' . 'edit-dispatch_order', '__return_empty_array' );

		add_action( 'admin_notices', array( $this, 'admin_notices' ) );
	}


	public static function EasyPack_Dispatch_Orders() {
		if (self::$instance === null) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	public function create_dispatch_order( $dispatch_point ) {

		$parcels = $_POST['easypack_parcel'];
		$parcel_ids = array();
		foreach ( $parcels as $parcel ) {
			$parcel_exploded = explode( '.', $parcel );
			if ( $parcel_exploded[0] == 'easypack' ) {
				$parcel_ids[] = $parcel_exploded[2];
			}
			if ( $parcel_exploded[0] == 'crossborder' ) {
				$parcel_id = $parcel_exploded[2];
				$order_id = $parcel_exploded[1];
				$easypack_parcels = get_post_meta( $order_id, '_easypack_parcels', true );
				foreach ( $easypack_parcels as $easypack_parcel ) {
					if ( $easypack_parcel['crossborder_data']['id'] == $parcel_id ) {
						$parcel_ids[] = $easypack_parcel['crossborder_data']['tracking_number'];
					}
				}
			}
		}
		$args = array( 'parcel_ids' => $parcel_ids );

		try {
			$response = EasyPack_API()->dispatch_order( $dispatch_point, $args );
			return $response['id'];
		}
		catch ( Exception $e ) {
			throw $e;
		}
	}

	public function create_manifest( $easypack_dispatch_point, $easypack_dispatch_number ) {

		$ret = array();

		$easypack_parcel_ids = $_POST['easypack_parcel'];

		$all_parcels = array( 'crossborder' => array(), 'easypack' => array() );

		foreach ( $easypack_parcel_ids as $easypack_parcel_id ) {
			$order_parcel = explode( '.', $easypack_parcel_id );
			$api = $order_parcel[0];
			$order_id = $order_parcel[1];
			$parcel_id = $order_parcel[2];

			$all_parcels[$api][] = array( 'parcel_id' => $parcel_id, 'order_id' => $order_id );
		}
		foreach ( $all_parcels as $api => $api_parcels ) {
			if ( count( $api_parcels ) ) {
				try {
					if ( $api == 'crossborder' ) {
						$args = array( 'shipments' => array() );
						foreach ( $api_parcels as $api_parcel ) {
							$args['shipments'][] = array( 'id' => $api_parcel['parcel_id'] );
						}
						$manifest = CrossBorder_API()->manifest( $args );
					}

					$post = array( 'post_type' => 'dispatch_order', 'post_status' => 'publish' );
					$post['post_title'] = $api . ' - ' . date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ),  current_time( 'timestamp' ) );
					$post_id = wp_insert_post( $post );
					$post = get_post( $post_id );
					$post->post_title = $post_id . ' - ' . $post->post_title;
					wp_update_post( $post );
					update_post_meta( $post_id, '_parcels', $api_parcels );

					if ( $api == 'crossborder' ) {
						update_post_meta( $post_id, '_manifest', $manifest );
					}

					$sender = get_option( 'easypack_sender_email' );
					if ( get_option( 'easypack_sender_company_name' ) ) {
						$sender .= "\n" . get_option( 'easypack_sender_company_name' );
					}
					$sender .= "\n" . get_option( 'easypack_sender_first_name' ) . ' ' . get_option( 'easypack_sender_last_name' );
					$sender .= "\n" . get_option( 'easypack_sender_street' ) . ' ' . get_option( 'easypack_sender_building_no' );
					if ( get_option( 'easypack_sender_flat_no' ) ) {
						$sender .= "/" . get_option( 'easypack_sender_flat_no' );
					}
					$sender .= "\n" . get_option( 'easypack_sender_post_code' ) . ' ' . get_option( 'easypack_sender_city' );
					$sender .= "\nTel.:" . get_option( 'easypack_sender_phone' );
					update_post_meta( $post_id, '_sender', $sender );

					update_post_meta( $post_id, '_dispatch_number', $easypack_dispatch_number );

					if ( $api == 'easypack' ) {
						$dispatch_point = EasyPack_API()->dispatch_point( $easypack_dispatch_point );
						update_post_meta( $post_id, '_dispatch_point', $dispatch_point );
					}
					update_post_meta( $post_id, '_api', $api );

					update_post_meta( $post_id, '_parcel_ids', $api_parcels );

					$ret[] = array( 'post_id' => $post_id, 'api' => $api ) ;

				}
				catch ( Exception $e ) {
					throw $e;
				}
			}
		}

		foreach ( $easypack_parcel_ids as $easypack_parcel_id ) {

			$order_parcel = explode( '.', $easypack_parcel_id );
			$api = $order_parcel[0];
			$order_id = $order_parcel[1];
			$parcel_id = $order_parcel[2];

			$easypack_parcels = get_post_meta( $order_id, '_easypack_parcels', true );

			foreach ( $easypack_parcels as $key => $easypack_parcel ) {
				if ( $api == 'crossborder' ) {
					if ( $easypack_parcel['crossborder_data']['id'] == $parcel_id ) {
						$easypack_parcels[$key]['dispatch_order'] = $post_id;
					}
				}
				else {
					if ( $easypack_parcel['easypack_data']['id'] == $parcel_id ) {
						$easypack_parcels[$key]['dispatch_order'] = $post_id;
					}
				}
			}
			update_post_meta( $order_id, '_easypack_parcels', $easypack_parcels );

			$dispatched = true;
			foreach ( $easypack_parcels as $key => $easypack_parcel ) {
				if ( empty( $easypack_parcels[$key]['dispatch_order'] ) ) $dispatched = false;
			}
			if ( $dispatched ) {
				update_post_meta( $order_id, '_easypack_dispatched', '1' );
			}

		}

		return $ret;

	}

	public function get_crossborder_manifest( $post_id ) {
		$manifest = get_post_meta( $post_id, '_manifest' );
		header( 'Location: ' . $manifest[0]['pdf_url'] );
	}

	public function create_easypack_manifest( $post_id ) {
		$post = get_post( $post_id );

		require_once( 'class-manifest-pdf.php' );

		$width_full = 186;
		$width2 = array( $width_full/2, $width_full/2);
		$width3 = array( $width_full/3, $width_full/3, $width_full/3);
		$width_col_size = 10;
		$width3_2 = array( $width_col_size, ($width_full-$width_col_size)/2, ($width_full-$width_col_size)/2);
		$width4 = array( $width_full/4, $width_full/4, $width_full/4, $width_full/4);
		$width_col_size = 10;
		$width4_2 = array( $width_col_size, $width_full/3 - $width_col_size, $width_full/3, $width_full/3);


		$pdf = new EasyPack_Manifest_PDF();
		$pdf->AliasNbPages();
		$pdf->AddPage();
		$pdf->Ln(5);
		$pdf->AddFont('arialpl','','arialpl.php');
		$pdf->SetFont('arialpl','', 11);
		$text = iconv( 'utf-8', 'iso-8859-2', 'Potwierdzenie odebrania paczek nr ' . $post->ID );
		$pdf->Cell( 0, 0, $text, 0, 0 , 'C' );
		$pdf->Ln(5);
		$dispatch_number = get_post_meta( $post_id, '_dispatch_number', true );
		$text = iconv( 'utf-8', 'iso-8859-2', 'do zamówienia Odbioru nr ' . $dispatch_number );
		$pdf->Cell( 0, 0, $text, 0, 0 , 'C' );
		$pdf->Ln(15);
		$text = iconv( 'utf-8', 'iso-8859-2', 'Data' );
		$text .= ' ' . date_i18n( 'd/m/Y', strtotime( $post->post_date ) );
		$pdf->Cell( 0, 0, $text, 0, 0 );
		$pdf->Ln(5);
		$text = iconv( 'utf-8', 'iso-8859-2', 'Oddział InPost' );
		$pdf->Cell( $width3[0], 8, $text, 1, 'L' );
		$text = iconv( 'utf-8', 'iso-8859-2', 'Nadawca' );
		$pdf->Cell( $width3[1], 8, $text, 1, 'L' );
		$text = iconv( 'utf-8', 'iso-8859-2', 'Adres odbioru' );
		$pdf->Cell( $width3[2], 8, $text, 1, 'L' );
		$pdf->Ln(8);
		$pdf->Cell( $width3[0], 30, '', 1, 'L' );
		$sender = get_post_meta( $post_id, '_sender', true );
		$y = $pdf->GetY();
		$x = $pdf->GetX();
		$pdf->Cell( $width3[1], 30, '', 1, 'L' );
		$pdf->SetXY( $x, $y );
		$pdf->MultiCell( $width3[1], 5, $pdf->iconv( $sender ), 0, 'L' );
		$pdf->SetXY( $x+$width3[1], $y );
		$dispatch_point = get_post_meta( $post_id, '_dispatch_point', true );
		$dispatch_address = '';
		if ( $dispatch_point ) {
			$dispatch_address = $dispatch_point['email'];
			$dispatch_address .= "\n" . $dispatch_point['name'];
			$dispatch_address .= "\n" . $dispatch_point['address']['street'] . ' ' . $dispatch_point['address']['building_no'];
			if ( isset( $dispatch_point['address']['flat_no'] ) && trim($dispatch_point['address']['flat_no']) != '' ) {
				$dispatch_address .= '/' . $dispatch_point['address']['flat_no'];
			}
			$dispatch_address .= "\n" . $dispatch_point['address']['post_code'] . ' ' . $dispatch_point['address']['city'];
			$dispatch_address .= "\nTel.: " . $dispatch_point['phone'];
		}
		$y = $pdf->GetY();
		$x = $pdf->GetX();
		$pdf->Cell( $width3[1], 30, '', 1, 'L' );
		$pdf->SetXY( $x, $y );
		$pdf->MultiCell( $width3[2], 5, $pdf->iconv( $dispatch_address ), 0, 'L' );
		$pdf->SetXY( $x+$width3[2], $y );
		$pdf->Ln(30);
		$pdf->Cell( $width_full, 16, $pdf->iconv( 'Uwagi:' ), 1, 'L' );
		$pdf->Ln(16);
		//
		$pdf->Cell( $width4_2[0], 8, $pdf->iconv( 'Typ' ), 1, 0, 'C' );
		$pdf->Cell( $width4_2[1], 8, $pdf->iconv( 'Kod paczki' ), 1, 0, 'C' );
		$pdf->Cell( $width4_2[2], 8, $pdf->iconv( 'Numer referencyjny' ), 1, 0, 'C' );
		$pdf->Cell( $width4_2[3], 8, $pdf->iconv( 'Odbiorca' ), 1, 0, 'C' );
		$pdf->Ln(8);
		//
		$parcels = get_post_meta( $post_id, '_parcels', true );

		$parcel_sizes = array( 'A' => 0, 'B' => 0, 'C' => 0 );

		foreach ( $parcels as $parcel ) {

			$order_id = $parcel['order_id'];
			$parcel_id = $parcel['parcel_id'];

			$easypack_parcels = get_post_meta( $order_id, '_easypack_parcels', true );

			$easypack_data = EasyPack_Helper()->get_easypack_parcel_data( $easypack_parcels, $parcel_id );

			$parcel_sizes[$easypack_data['size']]++;

			$pdf->Cell( $width4_2[0], 25, $easypack_data['size'], 1, 0, 'C' );
			$pdf->Code128($pdf->GetX()+5,$pdf->GetY()+1,$easypack_data['id'],$width4_2[1]-10,15);
			$pdf->Cell( $width4_2[1], 25, '', 1, 0, 'C' );
			$pdf->SetXY($pdf->GetX()-$width4_2[1], $pdf->GetY()+15);
			$pdf->SetFont('arialpl','', 10);
			$pdf->Cell( $width4_2[1], 10, $easypack_data['id'], 0, 0, 'C' );
			$pdf->SetFont('arialpl','', 11);
			$pdf->SetXY($pdf->GetX(), $pdf->GetY()-15);
			$pdf->Cell( $width4_2[2], 25, '', 1, 0, 'C' );
			$pdf->Cell( $width4_2[3], 25, $easypack_data['receiver_email'], 1, 0, 'C' );
			$pdf->Ln(25);
		}

		$pdf->Ln(10);

		$pdf->SetFont('arialpl','', 10);

		if ( $pdf->GetY() > 140 ) {
			$pdf->AddPage();
			$pdf->Ln(10);
		}

		$pdf->Cell( $width4[0], 8, $pdf->iconv( 'Paczka gabarytu A' ), 1, 0, 'C' );
		$pdf->Cell( $width4[1], 8, $pdf->iconv( 'Paczka gabarytu B' ), 1, 0, 'C' );
		$pdf->Cell( $width4[2], 8, $pdf->iconv( 'Paczka gabarytu C' ), 1, 0, 'C' );
		$pdf->Cell( $width4[2], 8, $pdf->iconv( 'Suma' ), 1, 0, 'C' );
		$pdf->Ln(8);
		$pdf->Cell( $width4[0], 8, $parcel_sizes['A'], 1, 0, 'C' );
		$pdf->Cell( $width4[1], 8, $parcel_sizes['B'], 1, 0, 'C' );
		$pdf->Cell( $width4[2], 8, $parcel_sizes['C'], 1, 0, 'C' );
		$pdf->Cell( $width4[2], 8, $parcel_sizes['A'] + $parcel_sizes['B'] + $parcel_sizes['C'], 1, 0, 'C' );
		$pdf->Ln(16);

		$y = $pdf->GetY();
		$pdf->Cell( 3, 3, '', 1, 0, 'C' );
		$pdf->SetXY($pdf->GetX()+2, $pdf->GetY()-2);
		$pdf->MultiCell( 80, 6, $pdf->iconv( "sprawdzono numery wszystkich paczek\n zgadza się / nie zgadza się - Uwagi\n\n.....................................................................\n\n....................................................................."
						 ), 0, 'L' );

		$pdf->SetXY( $pdf->GetX()+86, $y );
		$pdf->Cell( 3, 3, '', 1, 0, 'C' );
		$pdf->SetXY($pdf->GetX()+2, $pdf->GetY()-2);
		$pdf->MultiCell( 100, 6, $pdf->iconv( "przeliczono ilość sztuk w poszczególnych gabarytach\n zgadza się / nie zgadza się - Uwagi\n\n..........................................................................\n\n.........................................................................."
				 ), 0, 'L' );

		$y = $pdf->GetY();
		$pdf->Ln(5);
		$pdf->Cell( 3, 3, '', 1, 0, 'C' );
		$pdf->SetXY($pdf->GetX()+2, $pdf->GetY()-2);
		$pdf->MultiCell( 100, 6, $pdf->iconv( "nie dokonano weryfikacji ilości i numerów przesyłek - rzeczywista liczba sztuk zostanie potwierdzona przez InPost dopiero po ich rejestracji w systemie śledzenia. Tylko przesyłki zarejestrowane w sytemie śledzenia są uważane za nadane.\nUwagi\n\n.....................................................................\n\n.....................................................................\n\nPodpis kuriera"
				 ), 0, 'L' );

		$pdf->SetXY( $pdf->GetX()+111, $y+5 );
		$pdf->MultiCell( 100, 6, $pdf->iconv( "Podpis i pieczęć nadawcy"
				 ), 0, 'L' );
		//
		$pdf->Output( 'D', 'manifest_' . $post_id . '.pdf' );
	}

	public function register_post_types() {

		$labels = array(
				'name'               => _x( 'Dispatch Orders', 'post type general name', EasyPack::$text_domain ),
				'singular_name'      => _x( 'Dispatch Order', 'post type singular name', EasyPack::$text_domain ),
				'menu_name'          => _x( 'Dispatch Orders', 'admin menu', EasyPack::$text_domain ),
				'name_admin_bar'     => _x( 'Dispatch Order', 'add new on admin bar', EasyPack::$text_domain ),
				'all_items'          => __( 'All Dispatch Orders', EasyPack::$text_domain ),
				'search_items'       => __( 'Search Dispatch Orders', EasyPack::$text_domain ),
				'not_found'          => __( 'No Dispatch Orders found.', EasyPack::$text_domain ),
				'not_found_in_trash' => __( 'No Dispatch Orders found in Trash.', EasyPack::$text_domain )
		);

		$args = array(
				'labels'             	=> $labels,
				'description'        	=> __( 'Dispatch Orders.', EasyPack::$text_domain ),
				'public'             	=> false,
				'publicly_queryable' 	=> false,
				'show_ui'            	=> true,
				'show_in_menu'       	=> false,
				'query_var'          	=> true,
				'rewrite'            	=> false,
				'capability_type'    	=> 'post',
				'capabilities' 		 	=> array(
						'create_posts'		=> 'do_not_allow',
						'edit_post'			=> false,
						'delete_post'       => false,
				),
				'map_meta_cap'       	=> true,
				'has_archive'        	=> true,
				'hierarchical'       	=> false,
				'menu_position'      	=> null,
				'supports'           	=> array( 'title' )
		);

		register_post_type( 'dispatch_order', $args );
	}

	//public function manage_posts_custom_column

	public function manage_edit_dispatch_order_columns( $columns ) {
		unset($columns['date']);
		unset($columns['cb']);
		$columns['created'] = __( 'Created', EasyPack::$text_domain );
//		$columns['status'] = __( 'Status', EasyPack::$text_domain );
		$columns['action'] = __( 'Action', EasyPack::$text_domain );
		return $columns;
	}

	function manage_dispatch_order_posts_custom_column( $column, $post_id ) {
		global $post;

		$api = get_post_meta( $post_id, '_api', true );
		$status = get_post_meta( $post_id, '_status', true );

		switch( $column ) {
			case 'created' :
				echo $post->post_date;
				break;
			case 'status' :
				if ( $status == 'cancelled' )
					_e( 'Cancelled', EasyPack::$text_domain );
				break;
			case 'action' :
				if ( $status != 'cancelled' ) {
					if ( $api == 'crossborder' ) {
						$url = site_url( '?easypack_download=1&crossborder_manifest=' . $post_id );
					}
					else {
						$url = site_url( '?easypack_download=1&easypack_manifest=' . $post_id );
					}
					$link = '<a href="' . $url . '" target="blank" class="button-primary">' . __( 'Print', EasyPack::$text_domain ) . '</a>';
					echo $link;
					echo ' ';
					$url = admin_url( 'edit.php?post_type=dispatch_order&cancel=' . $post_id );
					$link = '<a href="' . $url . '" class="button easypack_cancel_dispatch_order">' . __( 'Cancel', EasyPack::$text_domain ) . '</a>';
//					echo $link;
				}
				break;
			default		:
				break;
		}
	}

	function cancel_dispatch_order( $post ) {
		$order_parcel_ids = get_post_meta( $post->ID, '_parcel_ids', true );
		$api = get_post_meta( $post->ID, '_api', true );

		foreach ( $order_parcel_ids as $order_parcel_id ) {
			$order_id = $order_parcel_id['order_id'];
			$parcel_id = $order_parcel_id['parcel_id'];

			$easypack_parcels = get_post_meta( $order_id, '_easypack_parcels', true );
			foreach ( $easypack_parcels as $key => $easypack_parcel ) {
				if ( $api == 'crossborder' ) {
					if ( $easypack_parcel['crossborder_data']['id'] == $parcel_id ) {
						unset( $easypack_parcels[$key]['dispatch_order'] );
					}
				}
				else {
					if ( $easypack_parcel['easypack_data']['id'] == $parcel_id ) {
						unset( $easypack_parcels[$key]['dispatch_order'] );
					}
				}
			}
			update_post_meta( $order_id, '_easypack_parcels', $easypack_parcels );

			delete_post_meta( $order_id, '_easypack_dispatched' );

		}
		update_post_meta( $post->ID, '_status', 'cancelled' );
	}

	function admin_notices() {
		global $pagenow;
		if ( $pagenow == 'edit.php' && isset( $_GET['post_type'] ) && $_GET['post_type'] == 'dispatch_order' && isset( $_GET['cancel'] ) ) {
			$post = get_post( $_GET['cancel'] );
			if ( $post ) {
				if ( get_post_meta( $post->ID, '_status' ) == 'cancelled' ) {
					?>
						<div class="error">
							<p><?php echo sprintf( __( 'Dispatch order %d already cancelled.', EasyPack::$text_domain ), $post->ID ); ?></p>
						</div>
					<?php
				}
				else {
					$this->cancel_dispatch_order( $post );
					?>
						<div class="updated">
							<p><?php echo sprintf( __( 'Dispatch order %d cancelled.', EasyPack::$text_domain ), $post->ID ); ?></p>
						</div>
					<?php
				}
			}
		}
	}

}


function EasyPack_Dispatch_Orders() {
	return EasyPack_Dispatch_Orders::EasyPack_Dispatch_Orders();
}

EasyPack_Dispatch_Orders();

endif;
