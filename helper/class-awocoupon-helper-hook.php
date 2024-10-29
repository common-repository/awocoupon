<?php
/**
 * AwoCoupon
 *
 * @package WordPress AwoCoupon
 * @copyright Copyright (C) Seyi Awofadeju - All rights reserved.
 * @Website : http://awodev.com
 **/

if ( ! defined( '_AWO_' ) ) {
	exit;
}

class AwoCoupon_Helper_Hook {

	public function register( $plugin_file ) {

		if ( ! class_exists( 'AwoCoupon_Helper_Install' ) ) {
			require dirname( $plugin_file ) . '/helper/class-awocoupon-helper-install.php';
		}
		register_activation_hook( $plugin_file, array( 'AwoCoupon_Helper_Install', 'start_install' ) );
		register_deactivation_hook( $plugin_file, array( 'AwoCoupon_Helper_Install', 'start_disable' ) );
		register_uninstall_hook( $plugin_file, array( 'AwoCoupon_Helper_Install', 'start_uninstall' ) );

		add_action( 'admin_menu', function() {
			//function add_submenu_page( $parent_slug, $page_title, $menu_title, $capability, $menu_slug, $function = '' ) {
			add_submenu_page(
				'woocommerce',
				__( 'AwoCoupon', 'awocoupon' ),
				__( 'AwoCoupon', 'awocoupon' ) ,
				'manage_woocommerce',
				'awocoupon',
				array( 'AwoCoupon_Admin_Admin', 'display' )
			);
		} );
		add_action( 'wp_ajax_awocoupon_ajax_call', function() {

			$type = AC()->helper->get_request( 'type' );
			if ( empty( $type ) ) {
				exit;
			}
			switch ( $type ) {
				case 'admin':
					if ( ! empty( $_GET['parameters'] ) ) {
						parse_str( $_GET['parameters'], $_GET['parameters'] );
						$_REQUEST['parameters'] = $_GET['parameters'];
					}
					if ( ! empty( $_POST['parameters'] ) ) {
						parse_str( $_POST['parameters'], $_POST['parameters'] );
						$_REQUEST['parameters'] = $_POST['parameters'];
					}

					AC()->helper->route( array(
						'type' => $type,
						'view' => AC()->helper->get_request( 'view' ),
						'task' => AC()->helper->get_request( 'task' ),
						'layout' => AC()->helper->get_request( 'layout', 'default' ),
					) );

					exit;

				case 'ajax':
					$class = AC()->helper->new_class( 'Awocoupon_Helper_Ajax' );
					$task = AC()->helper->get_request( 'task' );
					if ( ! method_exists( $class, $task ) ) {
						exit;
					}

					$class->$task();
					exit;
			}
		} );

		add_filter( 'plugin_action_links_awocoupon/awocoupon.php', function ( $links ) {
			$action_links = array(
				'settings' => '<a href="' . admin_url( 'admin.php?page=awocoupon#/config' ) . '" >' . AC()->lang->__( 'Settings' ) . '</a>',
			);
			return array_merge( $action_links, $links );
		} );

		add_action( 'awocoupon_cron_action', function() {
			AC()->helper->add_class( 'AwoCoupon_Helper_Cron' );
			AwoCoupon_Helper_Cron::process();
		} );

		add_action( 'admin_init', function() {
			if ( ! defined( 'IFRAME_REQUEST' ) && get_option( 'awocoupon_version' ) !== AC()->version ) {
				$installer = AC()->helper->new_class( 'AwoCoupon_Helper_Install' );
				$installer->install();
			}
		}, 5 );

		add_action( 'admin_enqueue_scripts', function( $page ) {
			if ( 'woocommerce_page_awocoupon' != $page ) {
				return;
			}

			add_thickbox();

			wp_register_style( 'jquery-ui', '//ajax.googleapis.com/ajax/libs/jqueryui/1.8/themes/base/jquery-ui.css' );
			wp_enqueue_style( 'jquery-ui' );
			wp_enqueue_style( 'awocoupon-style', AWOCOUPON_ASEET_URL . '/css/style.css', array(), AWOCOUPON_VERSION );
			wp_enqueue_style( 'awocoupon-tab', AWOCOUPON_ASEET_URL . '/css/tab.css', array(), AWOCOUPON_VERSION );
			wp_enqueue_style( 'awocoupon-btn', AWOCOUPON_ASEET_URL . '/css/buttons.css', array(), AWOCOUPON_VERSION );
			wp_enqueue_style( 'awocoupon-select2', AWOCOUPON_ASEET_URL . '/css/select2.css', array(), AWOCOUPON_VERSION );
			wp_enqueue_style( 'awocoupon-language', AWOCOUPON_ASEET_URL . '/css/language.css', array(), AWOCOUPON_VERSION );
			wp_enqueue_style( 'awocoupon-pagination', AWOCOUPON_ASEET_URL . '/css/pagination.css', array(), AWOCOUPON_VERSION );

			wp_enqueue_script( 'jquery-ui-dialog' );
			wp_enqueue_script( 'jquery-ui-datepicker' );
			wp_enqueue_script( 'jquery-ui-widget' );
			wp_enqueue_script( 'awocoupon-sammy', AWOCOUPON_ASEET_URL . '/js/sammy-min.js', array( 'jquery' ), AWOCOUPON_VERSION );
			wp_enqueue_script( 'awocoupon-validator', AWOCOUPON_ASEET_URL . '/js/jquery.validate.min.js', array( 'jquery' ), AWOCOUPON_VERSION );
			wp_enqueue_script( 'awocoupon-awocoupon', AWOCOUPON_ASEET_URL . '/js/awocoupon.js', array( 'jquery', 'awocoupon-validator' ), AWOCOUPON_VERSION );
			wp_enqueue_script( 'awocoupon-select2', AWOCOUPON_ASEET_URL . '/js/select2.min.js', array( 'jquery' ), AWOCOUPON_VERSION );
			wp_enqueue_script( 'awocoupon-bootstrap', AWOCOUPON_ASEET_URL . '/js/bootstrap.min.js', array( 'jquery' ), AWOCOUPON_VERSION );

			// admin menu
			wp_enqueue_style( 'awocoupon-style', AWOCOUPON_ASEET_URL . '/css/style.css', array(), AWOCOUPON_VERSION );
			wp_enqueue_style( 'awocoupon-menucss', AWOCOUPON_ASEET_URL . '/css/menu.css', array(), AWOCOUPON_VERSION );

			wp_enqueue_script( 'awocoupon-bootstrap', AWOCOUPON_ASEET_URL . '/js/bootstrap.min.js', array( 'jquery' ), AWOCOUPON_VERSION );
			wp_enqueue_script( 'awocoupon-menujs', AWOCOUPON_ASEET_URL . '/js/menu.js', array( 'jquery' ), AWOCOUPON_VERSION );
		} );
		
		
		
		
		
		
		// ===================================================
		// frontend my account pages coupon
		// ===================================================
		add_action( 'woocommerce_init', function() {
			add_rewrite_endpoint( 'awocoupon-coupons', EP_ROOT | EP_PAGES );
		} );
		add_filter( 'woocommerce_get_query_vars', function( $query_vars ) {
			$query_vars['awocoupon-coupons'] = 'awocoupon-coupons';
			return $query_vars;
		} );
		add_filter( 'woocommerce_account_menu_items', function( $items ) {
			$logout = $items['customer-logout'];
			unset( $items['customer-logout'] );

			$items['awocoupon-coupons'] = AC()->lang->__( 'Coupons' );
			$items['customer-logout'] = $logout;
			return $items;
		} );
		add_action( 'woocommerce_account_awocoupon-coupons_endpoint', function() {
			add_thickbox();
			wp_enqueue_style( 'awocoupon-pagination', AWOCOUPON_ASEET_URL . '/css/pagination.css', array(), AWOCOUPON_VERSION );
			AC()->helper->route( array(
				'type' => 'public',
				'view' => 'coupon',
				'task' => AC()->helper->get_request( 'task' ),
				'layout' => AC()->helper->get_request( 'layout', 'default' ),
			) );
		} );
		add_filter( 'the_title', function ( $title ) {
			global $wp_query;
			if ( ! is_null( $wp_query ) && ! is_admin() && is_main_query() && in_the_loop() && is_page() && is_wc_endpoint_url() ) {
				$endpoint = WC()->query->get_current_endpoint();
				switch ( $endpoint ) {
					case 'awocoupon-coupons':
						$title = AC()->lang->__( 'Coupons' );
						break;
				}
			}
			return $title;
		} );

		// ===================================================
		// coupon processing
		// ===================================================
		add_action( 'woocommerce_init', function() {
			// make coupons case sensitive.
			remove_filter( 'woocommerce_coupon_code', 'strtolower' );
			remove_filter( 'woocommerce_coupon_code', 'wc_strtolower' );
		} );
		add_action( 'wc_ajax_apply_coupon', function() {
			if ( ! isset( $_POST['coupon_code'] ) ) {
				return;
			}
			if ( AC()->storecoupon->is_coupon_only_in_store( $_POST['coupon_code'] ) ) {
				$_POST['coupon_code'] = wc_strtolower( $_POST['coupon_code'] );
			}
		} );
		add_filter( 'woocommerce_get_shop_coupon_data', function( $x, $data ) {
			return AC()->storecoupon->cart_coupon_validate( $data );
		}, 10, 2 );
		add_action( 'woocommerce_after_calculate_totals', function( $cart ) {
			AC()->storecoupon->cart_calculate_totals( $cart );
		} );
		add_action( 'woocommerce_removed_coupon', function( $coupon_code ) {
			AC()->storecoupon->cart_coupon_delete( $coupon_code );
		} );
		add_filter( 'woocommerce_cart_totals_coupon_label', function( $label, $coupon ) {
			// change the text displayed in the cart if need be
			$alabel = AC()->storecoupon->cart_coupon_displayname( $coupon->get_code() );
			return empty( $alabel ) ? $label : $alabel;
		}, 10, 2 );
add_filter( 'rest_request_before_callbacks', function( $response, $handler, $request ) {
//ini_set("display_errors", 1); error_reporting(E_ALL); restore_error_handler(); trigger_error(	'__route___: ' . $request->get_route() );

	if ( $request->get_route() == '/wc/store/v1/cart/apply-coupon' ) {
		AC()->storecoupon->disable_finalize_coupon_recalc = true;
	}
	if ( $request->get_route() == '/wc/store/v1/cart/remove-coupon' ) {
		$post = $request->get_body_params();
		if ( ! empty( $post['code'] ) ) {
			$coupon_session = AC()->storecoupon->get_coupon_session();
			if ( ! empty( $coupon_session->processed_coupons ) ) {
				foreach ( $coupon_session->processed_coupons as $coupon ) {
					if ( $coupon->display_text == $post['code'] ) {
						$post['code'] = $coupon->coupon_code;
						$request->set_body_params( $post );
						break;
					}
				}
			}
		}
	}
	return $response;
}, 10, 3 );

add_filter( 'woocommerce_hydration_request_after_callbacks', function( $response, $handler, $request ) {
	// woo 893
	if ( ! empty( $response->data['coupons'] ) ) {
		foreach ( $response->data['coupons'] as $i => $coupon ) {
			$label = AC()->storecoupon->cart_coupon_displayname( $coupon['code'], $only_code = true );
			if ( empty( $label ) ) {
				continue;
			}
			$response->data['coupons'][ $i ]['code'] = $label;
		}
	}
	return $response;
}, 10, 3 );
add_filter( 'rest_request_after_callbacks', function( $response, $handler, $request ) {
	// woo 883
	if ( ! empty( $response->data['coupons'] ) ) {
		foreach ( $response->data['coupons'] as $i => $coupon ) {
			$label = AC()->storecoupon->cart_coupon_displayname( $coupon['code'], $only_code = true );
			if ( empty( $label ) ) {
				continue;
			}
			$response->data['coupons'][ $i ]['code'] = $label;
		}
	}
	return $response;
}, 10, 3 );
		add_action( 'woocommerce_new_order', function( $order_id, $order ) {
			if ( $order->get_status() == 'checkout-draft' ) {
				return;
			}
			AC()->storecoupon->order_new( $order_id );
		}, 10, 2 );
		add_action( 'woocommerce_order_status_changed', function( $order_id, $status_from, $status_to ) {
			if ( $status_from == 'checkout-draft' ) {
				AC()->storecoupon->order_new( $order_id );
			}
			AC()->storecoupon->order_status_changed( $order_id, $status_from, $status_to );
		}, 10, 3 );
		add_filter( 'woocommerce_coupon_error', function( $err, $err_code, $coupon ) {
			$errors = AC()->storecoupon->error_msgs;
			if ( ! empty( $errors ) ) {
				return '<div>' . implode( '</div><div>', $errors ) . '</div>';
			}
			return $err;
		}, 10, 3 );
		add_filter( 'woocommerce_coupon_is_valid', function( $bool, $coupon, $discount = null ) {
			if ( ! method_exists( $coupon, 'get_virtual' ) ) {
				return true;
			}
			if ( ! $coupon->get_virtual() ) {
				return true;
			}
			return false !== AC()->storecoupon->is_couponcode_in_session( $coupon->get_code() ) ? true : false;
		}, 10, 3 );

		// ===================================================
		// automatic coupon trigger
		// ===================================================
		add_action( 'woocommerce_add_to_cart', function() {
			AC()->storecoupon->cart_coupon_validate_auto();
		}, 30, 0);
		add_action( 'woocommerce_cart_item_removed', function() {
			AC()->storecoupon->cart_coupon_validate_auto();
		} );
		add_action( 'woocommerce_cart_item_restored', function() {
			AC()->storecoupon->cart_coupon_validate_auto();
		} );
		add_action( 'woocommerce_before_cart_item_quantity_zero', function() {
			AC()->storecoupon->cart_coupon_validate_auto();
		} );
		add_action( 'woocommerce_after_cart_item_quantity_update', function() {
			AC()->storecoupon->cart_coupon_validate_auto();
		} );

	}

}
