<?php
/*
	Plugin Name: InPost for WooCommerce
	Plugin URI: https://wordpress.org/plugins/woo-inpost/
	Description: InPost is an international network of fully automated parcel lockers that are accessible 24/7, meaning no more queues or waiting in, enabling customers to collect, send and return parcels.
	Version: 1.0.2
	Author: InPost
	Author URI: https://inpost.pl/
	Text Domain: woo-inpost
	Domain Path: /lang/
	Tested up to: 4.5.3

	Copyright 2016 Inspire Labs sp. z o.o.

	This program is free software; you can redistribute it and/or modify
	it under the terms of the GNU General Public License as published by
	the Free Software Foundation; either version 3 of the License, or
	(at your option) any later version.

	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU General Public License for more details.

	You should have received a copy of the GNU General Public License
	along with this program; if not, write to the Free Software
	Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

*/

	if (!defined('ABSPATH'))
		exit; // Exit if accessed directly

	if (!class_exists('inspire_Plugin4')) {
		require_once('classes/inspire/plugin4.php');
	}

	if ( in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) )
	{
		class EasyPack extends inspire_Plugin4
		{

			public static $instance;

			public static $text_domain = 'woo-inpost';

			protected $_pluginNamespace = "woo-inpost";

			protected $shipping_methods = array();

			protected $settings;

			public function __construct()
			{
				parent::__construct();
				add_action('plugins_loaded', array($this, 'init_easypack'), 100);
			}

			public static function EasyPack()
			{
				if (self::$instance === null) {
					self::$instance = new self();
				}
				return self::$instance;
			}

			public function init_easypack()
			{
				include('classes/admin/class-dispatch-orders.php');
				include('classes/admin/class-shipment-manager.php');
				include('classes/class-helper.php');
				include('classes/class-ajax.php');
				include('classes/class-easypack-api.php');
				include('classes/class-crossborder-api.php');

				require_once('lib/fpdf/fpdf.php');
				require_once('lib/ConcatPdf.php');
				require_once('lib/code128.php');

				$this->init_shipping_methods();

				add_filter( 'woocommerce_get_settings_pages', array( $this, 'woocommerce_get_settings_pages' ) );

				add_action( 'admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'), 75 );

				add_action( 'wp_enqueue_scripts', array($this, 'enqueue_scripts'), 75 );

				add_filter( 'woocommerce_shipping_methods', array( $this, 'woocommerce_shipping_methods' ) );
			}

			public function init_shipping_methods() {

				if ( EasyPack_API()->api_country() != '--' ) {
					include('classes/shipping/parcel-machines.php');
					$this->shipping_methods[] = new EasyPack_Shippng_Parcel_Machines();

					if ( EasyPack_API()->api_country() == 'PL' || EasyPack_API()->api_country() == 'IT') {
						include('classes/shipping/parcel-machines-cod.php');
						$this->shipping_methods[] = new EasyPack_Shippng_Parcel_Machines_COD();
					}

					if ( EasyPack_API()->api_country() == 'PL' ) {
						include('classes/shipping/cross-border-parcel-machines.php');
						$this->shipping_methods[] = new EasyPack_Shippng_Cross_Border_Parcel_Machines();

						include('classes/shipping/cross-border-courier.php');
						$this->shipping_methods[] = new EasyPack_Shippng_Cross_Border_Courier();
					}
				}

			}

			public function woocommerce_shipping_methods( $methods ) {

				foreach ( $this->shipping_methods as $shipping_method ) {
					$methods[] = $shipping_method;
				}

				return $methods;
			}

			public function woocommerce_get_settings_pages( $woocommerce_settings )
			{

				$settings = include( 'classes/admin/class-settings-general.php' );

				$woocommerce_settings[] = $settings;

				return $woocommerce_settings;
			}

			public function get_package_sizes() {
				return 	array(
						'A'	=> __( 'A 8 x 38 x 64 cm', 	EasyPack::$text_domain ),
						'B'	=> __( 'B 19 x 38 x 64 cm',	EasyPack::$text_domain ),
						'C'	=> __( 'C 41 x 38 x 64 cm',	EasyPack::$text_domain ),
				);
			}

			public function get_package_sizes_display() {
				return 	array(
						'A'	=> __( 'A',	EasyPack::$text_domain ),
						'B'	=> __( 'B',	EasyPack::$text_domain ),
						'C'	=> __( 'C',	EasyPack::$text_domain ),
				);
			}

			public function get_package_weights_parcel_machines() {
				return 	array(
						'1'		=> __( '1 kg', 	EasyPack::$text_domain ),
						'2'		=> __( '2 kg',	EasyPack::$text_domain ),
						'5'		=> __( '5 kg',	EasyPack::$text_domain ),
						'10'	=> __( '10 kg',	EasyPack::$text_domain ),
						'15'	=> __( '15 kg',	EasyPack::$text_domain ),
						'20'	=> __( '20 kg',	EasyPack::$text_domain ),
				);
			}

			public function get_package_weights_courier() {
				return 	array(
						'1'		=> __( '1 kg', 	EasyPack::$text_domain ),
						'2'		=> __( '2 kg',	EasyPack::$text_domain ),
						'5'		=> __( '5 kg',	EasyPack::$text_domain ),
						'10'	=> __( '10 kg',	EasyPack::$text_domain ),
						'15'	=> __( '15 kg',	EasyPack::$text_domain ),
						'20'	=> __( '20 kg',	EasyPack::$text_domain ),
						'25'	=> __( '25 kg',	EasyPack::$text_domain ),
				);
			}

			public function loadPluginTextDomain()
			{
				parent::loadPluginTextDomain();
				$ret = load_plugin_textdomain( EasyPack::$text_domain, FALSE, basename( dirname( __FILE__ ) ) . '/languages' );
			}

			public static function getTextDomain() {
				return EasyPack::$text_domain;
			}

			function getTemplatePathFull()
			{
				return implode( '/', array($this->_pluginPath, $this->getTemplatePath() ) );
			}

			function enqueue_scripts()
			{
				wp_enqueue_style( 'easypack-front', $this->getPluginUrl() . 'assets/css/front.css' );
			}

			function enqueue_admin_scripts()
			{
				wp_enqueue_style( 'easypack-admin', $this->getPluginUrl() . 'assets/css/admin.css' );
				wp_enqueue_script( 'easypack-admin', $this->getPluginUrl() . 'assets/js/admin.js', array( 'jquery' ) );
			}

			function admin_footer()
			{
			}

			/**
			 * action_links function.
			 *
			 * @access public
			 * @param mixed $links
			 * @return void
			 */

			 public function linksFilter( $links ) {
			 	$plugin_links = array(
			 		'<a href="' . admin_url( 'admin.php?page=wc-settings&tab=easypack_general') . '">' . __( 'Settings', EasyPack::$text_domain ) . '</a>',
			 		'<a href="https://wordpress.org/plugins/woo-inpost/">' . __( 'Documentation', EasyPack::$text_domain ) . '</a>',
			 		'<a href="https://wordpress.org/support/plugin/woo-inpost">' . __( 'Support', EasyPack::$text_domain ) . '</a>',
			 	);

			 	return array_merge( $plugin_links, $links );
			 }

		}

		function EasyPack() {
			return EasyPack::Easypack();
		}

		$_GLOBALS['EasyPack'] = EasyPack();

	}

