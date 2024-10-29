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

class Awocoupon_Helper_Estore_Woocommerce_Currency {

	public function get_list() {
		return (object) array(
			'EUR' => (object) array(
				'code' => 'EUR',
				'rate' => 1.1,
			),
			'GBP' => (object) array(
				'code' => 'GBP',
				'rate' => 1,
			),
			'USD' => (object) array(
				'code' => 'USD',
				'rate' => 1.3,
			),
		);
	}

	public function format( $amount, $currency_code = null ) {
		if ( ! function_exists( 'wc_price' ) ) {
			require AWOCOUPON_DIR . '/../woocommerce/includes/wc-formatting-functions.php';
		}

		if ( empty( $currency_code ) ) {
			$currency_code = get_option( 'woocommerce_currency' ); // set to default currency
		}

		$args = array(
			'currency' => $currency_code,
		);
		$price = wc_price( $amount, $args ); // returns amount formatted in store selected quantity
											 // does not take into account $options['convert_to_selected_currency']

		$price = strip_tags( $price );
		return $price;
	}

	public function convert_to_default_format( $amount, $currency_code = null ) {
		$default_currency_code = get_option( 'woocommerce_currency' );
		$amount = $this->convert_to_default( $amount, $currency_code );
		return $this->format( $amount, $default_currency_code );
	}

	public function convert_from_default_format( $amount, $currency_code = null ) {
		$amount = $this->convert_from_default( $amount, $currency_code );
		return $this->format( $amount, $currency_code );
	}

	public function convert_to_default( $amount, $currency_code = null ) {
		if ( empty( $currency_code ) ) {
			$currency_code = get_woocommerce_currency();
		}
		$default_currency_code = get_option( 'woocommerce_currency' );
		if ( $default_currency_code == $currency_code ) {
			return $amount;
		}

		$currency_rate = 1;
		$amount = $amount / $currency_rate;

		return $amount;
	}

	public function convert_from_default( $amount, $currency_code = null ) {
		if ( empty( $currency_code ) ) {
			$currency_code = get_woocommerce_currency();
		}
		$default_currency_code = get_option( 'woocommerce_currency' );
		if ( $default_currency_code == $currency_code ) {
			return $amount;
		}

		$currency_rate = 1;
		$amount = $amount * $currency_rate;

		return $amount;
	}

}
