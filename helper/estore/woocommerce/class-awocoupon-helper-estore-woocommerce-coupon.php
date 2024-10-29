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

AC()->helper->add_class( 'AwoCoupon_Library_Coupon' );

class AwoCoupon_Helper_Estore_Woocommerce_Coupon extends AwoCoupon_Library_Coupon {

	var $params = null;

	var $cart = null;
	var $o_cart = null;
	var $coupon_code = null;
	var $posted_order = null;

	var $product_total = 0;
	var $product_qty = 0;
	var $default_err_msg = '';
	var $_disable_couponprocess = false;

	public static function instance( $class = null ) {
		return parent::instance( get_class() );
	}

	public function __construct() {
		parent::__construct();

		$this->estore = 'woocommerce';
		$this->default_err_msg = AC()->lang->__( 'Coupon not found' );
	}

	public function init() {
		if ( $this->_disable_couponprocess ) {
			return false;
		}
		if ( ! AC()->is_request( 'frontend' ) ) {
			return false;
		}
		$this->o_cart = WC()->cart;
		if ( empty( $this->o_cart ) ) {
			return false;
		}
		if ( $this->o_cart->is_empty() ) {
			return false;
		}
		$order_id = absint( WC()->session->get( 'order_awaiting_payment' ) );
		if ( $order_id > 0 ) {
			$order = wc_get_order( $order_id );
			if ( $order && $order->get_id() > 0 ) {
				return false;
			}
		}
		return true;
	}

	public function cart_coupon_validate( $coupon_code ) {
		if ( is_numeric( $coupon_code ) ) {
			return;
		}

		if ( $this->init() === false ) {
			return;
		}
		$this->coupon_code = $coupon_code;

		//------START STORE COUPON SYSTEM ----------------------------------------------------------------------------------------------
		if ( $this->is_coupon_only_in_store( $coupon_code ) ) {
			return false;
		}
		//------END   STORE COUPON SYSTEM ----------------------------------------------------------------------------------------------

		$return_coupon = new stdClass();
		if ( $this->process_coupon_helper() ) {
			$awosess = $this->session_get( 'coupon' );

			foreach ( $awosess->processed_coupons as $coupon ) {
				if ( $coupon->coupon_code != $coupon_code ) {
					continue;
				}

				$return_coupon->id = $coupon->coupon_entered_id;
			}
		}

		return $return_coupon;
	}

	public function cart_coupon_validate_auto() {
		if ( $this->init() === false ) {
			return;
		}

		$codes = $this->process_autocoupon_helper();
		if ( empty( $codes ) ) {
			return;
		}

		foreach ( $codes as $coupon ) {
			if ( $this->o_cart->has_discount( $coupon->coupon_code ) ) {
				continue;
			}
			$this->o_cart->add_discount( $coupon->coupon_code );
		}
	}

	public function cart_calculate_totals( & $cart = null ) {
		if ( $this->init() === false ) {
			return;
		}
		$this->process_coupon_helper();

		$coupon_awo_entered_coupon_ids = array();
		$coupon_session = $this->session_get( 'coupon', '' );
		if ( empty( $coupon_session ) ) {
			return;
		}

		if ( is_null( $cart ) ) {
			$cart = $this->o_cart;
		}

		if ( $force !== true && ! empty( $cart->awocoupon_uniquecartstring ) && $cart->awocoupon_uniquecartstring == $coupon_session->uniquecartstring ) {
			return;
		}

		$total_tax_to_add_to_discount_total = 0;
		foreach ( $coupon_session->processed_coupons as $coupon ) {
			$code = $coupon->coupon_code;

			$key = false === AC()->coupon->is_case_sensitive() ? array_search( strtolower( $code ), array_map( 'strtolower', $cart->applied_coupons ), true ) : array_search( $code, $cart->applied_coupons, true );
			if ( $key !== false ) {
				$code = $cart->applied_coupons[ $key ];
				unset( $cart->applied_coupons[ $key ] ); // remove awocoupon coupons so the can be re-added in order they are entered
			}
			$cart->applied_coupons[] = $code;
			if ( ! isset( $cart->coupon_discount_amounts[ $code ] ) ) {
				$cart->coupon_discount_amounts[ $code ] = 0;
			}
			if ( ! isset( $cart->coupon_discount_tax_amounts[ $code ] ) ) {
				$cart->coupon_discount_tax_amounts[ $code ] = 0;
			}
			//$cart->coupon_discount_amounts[ $code ] += $coupon->product_discount_notax + $coupon->shipping_discount_notax;
			//$cart->coupon_discount_tax_amounts[ $code ] += ( $coupon->product_discount - $coupon->product_discount_notax ) + ( $coupon->shipping_discount - $coupon->shipping_discount_notax );
			$cart->coupon_discount_amounts[ $code ] += $coupon->product_discount_notax + $coupon->shipping_discount_notax;
			if ( 1 === (int) $coupon->is_discount_before_tax ) {
				$cart->coupon_discount_tax_amounts[ $code ] += ( $coupon->product_discount - $coupon->product_discount_notax ) + ( $coupon->shipping_discount - $coupon->shipping_discount_notax );
			} else {
				$cart->coupon_discount_amounts[ $code ] += ( $coupon->product_discount - $coupon->product_discount_notax ) + ( $coupon->shipping_discount - $coupon->shipping_discount_notax );
				$total_tax_to_add_to_discount_total += ( $coupon->product_discount - $coupon->product_discount_notax ) + ( $coupon->shipping_discount - $coupon->shipping_discount_notax );
			}
		}
		$cart->applied_coupons = array_values( $cart->applied_coupons );

		foreach ( $coupon_session->cart_items as $item ) {
			$cart->cart_contents[ $item->cartpricekey ]['line_total'] = max( 0, $cart->cart_contents[ $item->cartpricekey ]['line_total'] - round( $item->totaldiscount_notax, $cart->dp ) );
			$cart->cart_contents[ $item->cartpricekey ]['line_tax'] = max( 0, $cart->cart_contents[ $item->cartpricekey ]['line_tax'] - round( $item->totaldiscount_tax, $cart->dp ) );
			if ( isset( $cart->cart_contents[ $item->cartpricekey ]['line_tax_data']['total'] ) && count( $cart->cart_contents[ $item->cartpricekey ]['line_tax_data']['total'] ) > 0 ) {
				foreach ( $cart->cart_contents[ $item->cartpricekey ]['line_tax_data']['total'] as $j => $v ) {
					$cart->cart_contents[ $item->cartpricekey ]['line_tax_data']['total'][ $j ] = max( 0, $v - round( $item->totaldiscount_tax, $cart->dp ) );
					break;
				}
			}
		}

		if ( isset( $cart->discount_cart ) ) {
			// the coupon discount excluding tax
			$cart->discount_cart = $cart->discount_cart + round( $coupon_session->product_discount_notax + $coupon_session->shipping_discount_notax + $total_tax_to_add_to_discount_total, $cart->dp );
		}
		if ( isset( $cart->discount_cart_tax ) ) {
			// the coupon tax discount
			$cart->discount_cart_tax = $cart->discount_cart_tax + round( $coupon_session->product_discount_tax - $coupon_session->shipping_discount_tax, $cart->dp );
		}
		if ( isset( $cart->cart_contents_total ) ) {
			// product subtotal including discount
			$cart->cart_contents_total = max( 0, $cart->cart_contents_total - round( $coupon_session->product_discount_notax, $cart->dp ) );
		}
		if ( isset( $cart->tax_total ) ) {
			// cart tax total
			$cart->tax_total = max( 0, $cart->tax_total - round( $coupon_session->product_discount_tax + $coupon_session->shipping_discount_tax, $cart->dp ) );
		}
		if ( version_compare( WC_VERSION, '3.2.0', '>=' ) ) {
			$taxes = $cart->get_cart_contents_taxes();
			if ( count( $taxes ) > 0 ) {
				foreach ( $taxes as $j => $v ) {
					$taxes[ $j ] = max( 0, $v - round( $coupon_session->product_discount_tax + $coupon_session->shipping_discount_tax, $cart->dp ) );
					break;
				}
				$cart->set_cart_contents_taxes( $taxes );
			}
		}
		else {
			if ( isset( $cart->taxes ) && count( $cart->taxes ) > 0 ) {
				foreach ( $cart->taxes as $j => $v ) {
					$cart->taxes[ $j ] = max( 0, $v - round( $coupon_session->product_discount_tax + $coupon_session->shipping_discount_tax, $cart->dp ) );
					break;
				}
			}
		}
		/*
		if ( isset( $cart->shipping_total ) ) {
			// shipping without tax
			$cart->shipping_total = max( 0, $cart->shipping_total - round( $coupon_session->shipping_discount_notax, $cart->dp ) );
		}
		if ( isset( $cart->shipping_tax_total ) ) {
			// shipping tax
			$cart->shipping_tax_total = max( 0, $cart->shipping_tax_total - round( $coupon_session->shipping_discount_tax, $cart->dp ) );
		}
		if ( isset( $cart->shipping_taxes ) && count( $cart->shipping_taxes ) > 0 ) {
			foreach ( $cart->shipping_taxes as $j => $v ) {
				$cart->shipping_taxes[ $j ] = max( 0, $v - round( $coupon_session->shipping_discount_tax, $cart->dp ) );
				break;
			}
		}
		*/

		if ( isset( $cart->total ) ) {
			// the cart total
			$total_discount = 0;
			foreach ( $coupon_session->processed_coupons as $coupon ) {
				if ( $coupon->is_discount_before_tax == 1) {
					$total_discount += $coupon->product_discount_notax + $coupon->product_discount_tax + $coupon->shipping_discount_notax + $coupon->shipping_discount_tax;
				}
				else {
					$total_discount += $coupon->product_discount + $coupon->shipping_discount;
				}
			}
			$cart->total = max( 0, $cart->total - $total_discount );

			// recalculate shipping, in case options changed due to discount
			$total_shipping = $cart->get_shipping_total() + $cart->get_shipping_tax();
			$this->disable_coupon_processing( $cart );
			$cart->calculate_shipping();
			$this->enable_coupon_processing();
			$cart->total += $cart->get_shipping_total() + $cart->get_shipping_tax() - $total_shipping;
		}
//printr($coupon_session);
		$cart->awocoupon_uniquecartstring = $coupon_session->uniquecartstring;
		return;
	}

	public function cart_coupon_delete( $coupon_code = '' ) {
		$this->init();
		$coupon_id = (int) AC()->db->get_value( 'SELECT id FROM #__awocoupon WHERE coupon_code="' . AC()->db->escape( $coupon_code ) . '"' );
		parent::delete_coupon_from_session( $coupon_id );
	}

	public function cart_coupon_displayname( $coupon_code, $only_code = false ) {
		if ( $this->init() === false ) {
			return;
		}

		$coupon_session = $this->session_get( 'coupon' );
		if ( empty( $coupon_session->processed_coupons ) ) {
			return;
		}
		foreach ( $coupon_session->processed_coupons as $coupon ) {
			if ( $coupon->coupon_code == $coupon_code ) {
				if ( $only_code === true ) {
					return $coupon->display_text;
				}
				// translators: coupon code
				$default_text = sprintf( esc_html__( 'Coupon: %s', 'woocommerce' ), $coupon->display_text );
				return $coupon->display_text == $coupon->coupon_code ? $default_text : $coupon->display_text;
			}
		}
		return;
	}

	public function order_new( $order_id ) {
		$this->save_coupon_history( (int) $order_id );
		return true;
	}

	public function order_status_changed( $order_id, $status_from, $status_to ) {
		$status_to = 'wc-' === substr( $status_to, 0, 3 ) ? $status_to : 'wc-' . $status_to;
		$this->cleanup_ordercancel_helper( $order_id, $status_to );
		return true;
	}

	public function is_coupon_only_in_store( $coupon_code ) {
		if ( empty( $coupon_code ) ) {
			return false;
		}
		if ( (int) $this->params->get( 'enable_store_coupon', 0 ) !== 1 ) {
			return false;
		}
		$tmp = AC()->db->get_value( 'SELECT id FROM #__awocoupon WHERE estore="' . $this->estore . '" AND coupon_code="' . AC()->db->escape( trim( $coupon_code ) ) . '"' );
		if ( ! empty( $tmp ) ) {
			return false;
		}
		return $this->is_coupon_in_store( $coupon_code );
	}

	protected function initialize_coupon() {
		$coupon_session = $this->session_get( 'coupon' );
		if ( ! empty( $coupon_session->processed_coupons ) && ! empty( $this->o_cart->applied_coupons ) ) {
			foreach ( $coupon_session->processed_coupons as $coupon ) {
				$coupon_code = wc_format_coupon_code( $coupon->coupon_code );
				$position = array_search( $coupon_code, $this->o_cart->applied_coupons );
				if ( false !== $position ) {
					unset( $this->o_cart->applied_coupons[ $position ] );
				}
				WC()->session->set( 'applied_coupons', $this->o_cart->applied_coupons );
			}
		}
		parent::initialize_coupon();
	}

	protected function finalize_coupon( $master_output ) {
		$session_array = $this->save_discount_to_session( $master_output );
		if ( empty( $session_array ) ) {
			return false;
		}
		$this->finalize_coupon_store( $session_array );

		if ( isset( $this->disable_finalize_coupon_recalc ) && $this->disable_finalize_coupon_recalc === true ) {
		// only skip this step the first time so src\StoreApi\Utilities\CartController.php CartController::apply_coupon $this->has_coupon check works
			$this->disable_finalize_coupon_recalc = false;
		}
		else {
			$this->cart_calculate_totals();
		}

		return true;
	}

	protected function finalize_coupon_store( $coupon_session ) {
	}

	protected function finalize_autocoupon( $coupon_codes ) {
		foreach ( $coupon_codes as $coupon ) {
			$this->coupon_code = $coupon->coupon_code;
			$this->process_coupon_helper();
		}
	}

	protected function getuniquecartstring( $coupon_code = null ) {
		if ( empty( $coupon_code ) ) {
			$coupon_code = isset( $this->o_cart->coupons ) ? json_encode( $this->o_cart->coupons ) : '';
		}
		if ( ! empty( $coupon_code ) ) {

			//$order_total = $this->o_cart->total;
			// order total excluding discount
			if ( $this->o_cart->get_shipping_total() <= 0 && empty( $this->o_cart->shipping_methods ) ) {
				$this->disable_coupon_processing();
				$this->o_cart->calculate_shipping();
				$this->enable_coupon_processing();
			}
			$order_total = $this->o_cart->get_subtotal() + $this->o_cart->get_subtotal_tax() + $this->o_cart->get_shipping_total() + $this->o_cart->get_shipping_tax() + $this->o_cart->get_fee_total() + $this->o_cart->get_fee_tax();

			$user = AC()->helper->get_user();
			$user_email = isset( $this->posted_order['billing_email'] ) ? $this->posted_order['billing_email'] : WC()->checkout->get_value( 'billing_email' );
			if ( empty( $user_email ) ) {
				$user_email = $user->email;
			}
			$shipping_id = json_encode( WC()->session->get( 'chosen_shipping_methods' ) );
			$payment_id = WC()->session->get( 'chosen_payment_method' );
			$address = $this->get_customeraddress();
			$string = $order_total . '|' . $coupon_code . '|' . $user->id . '|' . $user_email;
			foreach ( $this->o_cart->get_cart() as $k => $product ) {
				$string .= '|' . $k . '|' . $product['product_id'] . '|' . $product['quantity'];
			}
			return $string . '|ship|' . $shipping_id . '|' . $address->country_id . '|' . $address->state_id . '|' . $payment_id . '|currency|' . get_woocommerce_currency();
		}
		return;
	}

	protected function getuniquecartstringauto() {
		$user = AC()->helper->get_user();
		$shipping_id = json_encode( WC()->session->get( 'chosen_shipping_methods' ) );
		$payment_id = WC()->session->get( 'chosen_payment_method' );
		$address = $this->get_customeraddress();
		$string = $this->o_cart->total . '|' . $user->id;
		foreach ( $this->o_cart->get_cart() as $k => $product ) {
			$string .= '|' . $k . '|' . $product['product_id'] . '|' . $product['quantity'];
		}
		return $string . '|ship|' . $shipping_id . '|' . $address->country_id . '|' . $address->state_id . '|' . $payment_id . '|currency|' . get_woocommerce_currency();
	}

	protected function get_storeshoppergroupids( $user_id ) {
		return AC()->store->get_group_ids( $user_id );
	}

	protected function get_storecategory( $ids ) {
		$sql = 'SELECT t.term_id AS category_id,t.term_id AS asset_id,p.ID AS product_id
				  FROM #__terms t
				  JOIN #__term_taxonomy tx ON tx.term_id=t.term_id
				  JOIN #__term_relationships r ON r.term_taxonomy_id=tx.term_taxonomy_id
				  JOIN #__posts p ON p.ID=r.object_id
				 WHERE tx.taxonomy="product_cat" AND p.post_type="product" AND p.ID IN (' . $ids . ')';
		return AC()->db->get_objectlist( $sql );
	}

	protected function get_storemanufacturer( $ids ) {
		return array();
	}

	protected function get_storevendor( $ids ) {
		return array();
	}

	protected function get_storeshipping() {
		$this->disable_coupon_processing();
		$this->o_cart->calculate_shipping();
		$this->enable_coupon_processing();

		$shipping_id = 0;
		$chosen_shipping_methods = WC()->session->get( 'chosen_shipping_methods' );
		if ( ! empty( $chosen_shipping_methods[0] ) ) {
			if ( strpos( $chosen_shipping_methods[0], ':' ) !== false ) {
				list( $x, $shipping_id ) = explode( ':', $chosen_shipping_methods[0] );
			} else {
				$shipping_id = $chosen_shipping_methods[0];
			}
		}
		$shippings = array();
		$shippings[] = (object) array(
			'shipping_id' => $shipping_id,
			'total_notax' => $this->o_cart->shipping_total,
			'total' => $this->o_cart->shipping_total + $this->o_cart->shipping_tax_total,
			'tax_rate' => empty( round( $this->o_cart->shipping_total, 10 ) ) ? 0 : ( $this->o_cart->shipping_tax_total ) / $this->o_cart->shipping_total,
			'totaldiscount' => 0,
			'totaldiscount_notax' => 0,
			'totaldiscount_tax' => 0,
			'coupons' => array(),
		);

		$shipping = (object) array(
			'shipping_id' => $shipping_id,
			'total_notax' => $this->o_cart->shipping_total,
			'total' => $this->o_cart->shipping_total + $this->o_cart->shipping_tax_total,
			'shippings' => $shippings,
		);

		return $shipping;
	}

	protected function get_storepayment() {
		$payment = (object) array(
			'payment_id' => WC()->session->get( 'chosen_payment_method' ),
			'total_notax' => 0,
			'total' => 0,
		);
		return $payment;
	}

	protected function get_customeraddress() {
		$address = (object) array(
			'email' => isset( $this->posted_order['billing_email'] ) ? $this->posted_order['billing_email'] : WC()->checkout->get_value( 'billing_email' ),
			'state_id' => 0,
			'state_name' => '',
			'country_id' => 0,
			'country_name' => '',
		);

		$country_id = WC()->checkout->get_value( 'billing_country' );
		$state_id = WC()->checkout->get_value( 'billing_state' );
		if ( ! empty( $country_id ) ) {
			$address->country_id = $country_id;
			$address->country_name = $country_id;
			if ( ! empty( $state_id ) ) {
				$address->state_id = $country_id . '-' . $state_id;
				$address->state_name = $country_id . '-' . $state_id;
			}
		}

		return $address;
	}

	protected function get_submittedcoupon() {
		$coupon_code = $this->coupon_code;
		if ( empty( $coupon_code ) ) {
			$coupon_code = AC()->helper->get_request( 'coupon_code' );
		}
		return $coupon_code;
	}

	protected function get_orderemail( $order_id ) {
		if ( version_compare( WC_VERSION, '8', '>=' ) ) {
			return AC()->db->get_value( 'SELECT billing_email FROM #__wc_orders WHERE id=' . (int) $order_id );
		}
		else {
			return AC()->db->get_value( 'SELECT meta_value FROM #__postmeta WHERE post_id=' . (int) $order_id . ' AND meta_key="_billing_email"' );
		}
	}

	protected function is_customer_num_uses( $coupon_id, $max_num_uses, $customer_num_uses ) {
		$email = isset( $this->posted_order['billing_email'] ) ? $this->posted_order['billing_email'] : WC()->checkout->get_value( 'billing_email' );
		if ( empty( $email ) ) {
			$user = AC()->helper->get_user();
			$email = $user->email;
		}

		$customer_num_uses = (int) $customer_num_uses;
		$max_num_uses = (int) $max_num_uses;

		if ( ! empty( $email ) ) {
			$sql = 'SELECT COUNT(id) FROM #__awocoupon_history
					 WHERE estore="' . $this->estore . '" AND coupon_id=' . $coupon_id . ' AND user_email="' . AC()->db->escape( $email ) . '"
					 GROUP BY coupon_id';
			$customer_num_uses += (int) AC()->db->get_value( $sql );
		}

		if ( ! empty( $customer_num_uses ) && $customer_num_uses >= $max_num_uses ) {
			// per user: already used max number of times
			return false;
		}

		return true;
	}

	protected function is_coupon_in_store( $coupon_code ) {
		if ( empty( $coupon_code ) ) {
			return false;
		}
		$coupon_id = (int) AC()->db->get_value( 'SELECT ID FROM #__posts WHERE post_type="shop_coupon" AND post_title="' . AC()->db->escape( trim( $coupon_code ) ) . '"' );
		return $coupon_id > 0 ? true : false;
	}

	protected function define_cart_items() {
		// retreive cart items
		$this->cart = new stdClass();
		$this->cart->items = array();
		$this->cart->items_def = array();
		$this->product_total = 0;
		$this->product_qty  = 0;

		$this->disable_coupon_processing();
		$this->o_cart->calculate_totals();
		$this->enable_coupon_processing();
		$cart_contents = $this->o_cart->get_cart();
		foreach ( $cart_contents as $cartpricekey => $product ) {
			$product_id = $product['product_id'];

			if ( empty( $product_id ) ) {
				continue;
			}
			if ( empty( $product['quantity'] ) ) {
				continue;
			}

			if ( ! isset( $product['line_tax'] ) ) {
				$product['line_tax'] = 0;
			}
			$price_notax = $product['line_subtotal'] / $product['quantity'];
			$price = $price_notax + $product['line_subtotal_tax'] / $product['quantity'];

			$product_discount = max( 0, $product['data']->get_regular_price() - $product['data']->get_price() );

			$this->cart->items_def[ $product_id ]['product'] = $product_id;
			$this->cart->items[ $cartpricekey ] = array(
				'product_id' => $product_id,
				'cartpricekey' => $cartpricekey,
				'discount' => $product_discount,
				'product_price' => $price,
				'product_price_notax' => $price_notax,
				'product_price_tax' => $price - $price_notax,
				'tax_rate' => ( $price - $price_notax ) / $price_notax,
				'qty' => $product['quantity'],
				'is_special' => 0,
				'is_discounted' => ! empty( $product_discount ) ? 1 : 0,
			);
			$this->product_total += $product['quantity'] * $price;
			$this->product_qty += $product['quantity'];
		}

		parent::define_cart_items();
	}

	private function disable_coupon_processing( $cart = null ) {
		if ( is_null( $cart ) ) {
			$cart = $this->o_cart;
		}
		unset( $cart->awocoupon_uniquecartstring );
		$this->_disable_couponprocess = true;
	}
	private function enable_coupon_processing() {
		$this->_disable_couponprocess = false;
	}

}

