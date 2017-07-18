<?php
/**
 * EasyPack Post Types
*
* @author      WPDesk
* @category    Admin
* @package     EasyPack/Admin
* @version     2.1.0
*/

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( ! class_exists( 'EasyPack_Post_Types' ) ) :

/**
 * EasyPack_Post_Types
*/
class EasyPack_Post_Types {

	public static function init() {
		add_action( 'woocommerce_register_post_type', array( __CLASS__, 'register_post_types' ), 5 );
	}

	public static function register_post_types() {

		$labels = array(
				'name'               => _x( 'Dispatch Orders', 'post type general name', 'easypack' ),
				'singular_name'      => _x( 'Dispatch Order', 'post type singular name', 'easypack' ),
				'menu_name'          => _x( 'Dispatch Orders', 'admin menu', 'easypack' ),
				'name_admin_bar'     => _x( 'Dispatch Order', 'add new on admin bar', 'easypack' ),
				'all_items'          => __( 'All Dispatch Orders', 'easypack' ),
				'search_items'       => __( 'Search Dispatch Orders', 'easypack' ),
				'not_found'          => __( 'No Dispatch Orders found.', 'easypack' ),
				'not_found_in_trash' => __( 'No Dispatch Orders found in Trash.', 'easypack' )
		);

		$args = array(
				'labels'             => $labels,
				'description'        => __( 'Dispatch Orders.', 'easypack' ),
				'public'             => false,
				'publicly_queryable' => false,
				'show_ui'            => true,
				'show_in_menu'       => false,
				'query_var'          => true,
				'rewrite'            => false,
				'capability_type'    => 'post',
				'capabilities' => array(
						'create_posts' => false,
						'edit_post'		=> false,
						'delete_post'        => false,

				),
				'has_archive'        => true,
				'hierarchical'       => false,
				'menu_position'      => null,
				'supports'           => array( 'title' )
		);

		register_post_type( 'dispatch_order', $args );
	}

}

endif;

EasyPack_Post_Types::init();