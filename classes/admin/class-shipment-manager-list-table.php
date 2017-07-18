<?php
/**
 * EasyPack Shipment Manager List Table
*
* @author      WPDesk
* @category    Admin
* @package     EasyPack/Admin
* @version     2.1.0
*/

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( ! class_exists( 'EasyPack_Shipment_Manager_List_Table' ) ) :

if( ! class_exists( 'WP_List_Table' ) ) {
	require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

/**
 * EasyPack_Shipment_Manager_List_Table
*/
class EasyPack_Shipment_Manager_List_Table extends WP_List_Table {

	protected $data = array();

	function __construct( $send_method ) {
		parent::__construct();
		global $post;
		$args = array(
				'post_type'  	=> 'shop_order',
				'post_status' 	=> 'any',
				'meta_query' 	=> array(
						array(
								'key' 		=> '_easypack_status',
								'value' 	=> array( 'prepared', 'created', 'ReadyToBeSent' ),
								'compare' 	=> 'IN',
						),
						array(
								'key' 		=> '_easypack_dispatched',
								'value' 	=> '',
								'compare' 	=> 'NOT EXISTS',
						),
				)
		);
		if ( $send_method != 'all' ) {
			$args['meta_query'][] = array(
										'key' 		=> '_easypack_send_method',
										'value' 	=> $send_method,
										'compare' 	=> '=',
									);
		}

		$query = new WP_Query($args);
		while ( $query->have_posts() ) {
			$query->the_post();
			if ( $post->post_status == 'wc-cancelled' ) {
				/* skip cancelled orders */
				continue;
			}
			$order = wc_get_order( $post->ID );
			$order_id = $order->id;
			$easypack_parcels = get_post_meta( $order_id, '_easypack_parcels', true );
			if ( $easypack_parcels ) {
				foreach ( $easypack_parcels as $key => $parcel ) {
					$data = array();
					if ( isset($parcel['easypack_data']) ) {
						$data['package_number'] = $parcel['easypack_data']['id'];
						if ( isset( $parcel['easypack_data']['tracking_number'] ) ) {
							$data['package_number'] = $parcel['easypack_data']['tracking_number'];
						}
						$data['parcel'] = $parcel;
						$data['send_method_display'] = __( 'Courier', EasyPack::$text_domain );
						$send_method = get_post_meta( $order_id, '_easypack_send_method', true );
						$data['send_method'] = $send_method;
						if ( $send_method == 'parcel_machine' )
							$data['send_method_display'] = __( 'Parcel Locker', EasyPack::$text_domain );
						$data['status'] = $parcel['easypack_data']['status'];
						if ( is_array( $parcel['easypack_data']['status'] ) ) {
							$data['status'] = $parcel['easypack_data']['status']['code'];
						}
						$data['order'] = $order_id;
						$data['shipping_address'] = $order->get_formatted_shipping_address();
						if ( isset( $parcel['easypack_data']['target_machine_id'] ) ) {
							$data['shipping_address'] = __( 'Parcel Locker ', EasyPack::$text_domain ) . ' ' . $parcel['easypack_data']['target_machine_id'];
						}
						$data['parcel_id'] = $parcel['easypack_data']['id'];
						$data['order_id'] = $order_id;
						$data['api'] = 'easypack';
					}
					if ( isset($parcel['crossborder_data']) ) {
						$data['package_number'] = $parcel['crossborder_data']['id'];
						$data['package_number'] = $parcel['crossborder_data']['tracking_number'];
						$data['parcel'] = $parcel;
						$data['send_method_display'] = __( 'Courier', EasyPack::$text_domain );
						$send_method = get_post_meta( $order_id, '_easypack_send_method', true );
						$data['send_method'] = $send_method;
						if ( $send_method == 'parcel_machine' )
							$data['send_method_display'] = __( 'Parcel Locker', EasyPack::$text_domain );
						$data['status'] = $parcel['crossborder_data']['status']['code'];
						$data['order'] = $order_id;
						$data['shipping_address'] = $order->get_formatted_shipping_address();
						if ( isset( $parcel['crossborder_data']['target_machine_id'] ) ) {
							$data['shipping_address'] = __( 'Parcel Locker ', EasyPack::$text_domain ) . ' ' . $parcel['crossborder_data']['target_machine_id'];
						}
						$data['parcel_id'] = $parcel['crossborder_data']['id'];
						$data['order_id'] = $order_id;
						$data['api'] = 'crossborder';
					}
					$this->data[] = $data;
				}
			}
		}
		wp_reset_postdata();
	}

	function bulk_actions( $which = '' ) {

	}

	function column_cb($item) {
		return sprintf(
				'<input class="easypack_parcel" type="checkbox" name="easypack_parcel[]" value="%s" />', $item['api'] . '.' . $item['order_id'] . '.' . $item['parcel_id'] . '.' . $item['send_method'] . '.' . $item['status']
				);
	}

	function column_order( $item ) {
		$link = '<a href="'. admin_url( 'post.php?post=' . $item['order'] . '&action=edit' ) .'" >';
		$link .= '#' . $item['order'];
		$link .= '</a>';
		return $link;
	}

	function column_default( $item, $column_name ) {
		switch( $column_name ) {
			case 'package_number':
			case 'send_method':
			case 'send_method_display':
			case 'status':
			case 'order':
			case 'shipping_address':
				return $item[ $column_name ];
			default:
				return print_r( $item, true ) ; //Show the whole array for troubleshooting purposes
		}
	}

	function get_columns(){
		$columns = array(
				'cb'        			=> '<input type="checkbox" />',
				'package_number' 		=> __( 'Package', EasyPack::$text_domain ),
				'send_method_display' 	=> __( 'Send method', EasyPack::$text_domain ),
				'status' 				=> __( 'Status', EasyPack::$text_domain ),
				'order' 				=> __( 'Order', EasyPack::$text_domain ),
				'shipping_address' 		=> __( 'Shipping address', EasyPack::$text_domain ),
//				'parcel' => __( 'Parcel', EasyPack::$text_domain ),
		);
		return $columns;
	}

	function get_hidden_columns() {
		return array();
	}

	function prepare_items() {

		$columns  = $this->get_columns();
		$hidden   = $this->get_hidden_columns();
		$sortable = $this->get_sortable_columns();
		$this->_column_headers = array( $columns, $hidden, $sortable );

		$per_page = 5000000;
		$current_page = $this->get_pagenum();
		$total_items = count( $this->data );

		$this->found_data = $this->data;

		$this->set_pagination_args( array(
				'total_items' => $total_items,
				'per_page'    => $per_page
		) );

		$this->items = $this->data;
	}

}

endif;