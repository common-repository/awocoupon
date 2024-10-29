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

class AwoCoupon_Library_Controller {

	public function render( $file, $data = null ) {

		// Check possible overrides, and build the full path to layout file
		$path = AWOCOUPON_DIR;
		$tmp = explode( '.', $file );
		foreach ( $tmp as $tmp2 ) {
			$path .= '/' . $tmp2;
		}
		$path .= '.php';

		// Nothing to show
		if ( ! file_exists( $path ) ) {
			return '';
		}
		if ( ! empty( $data ) && ! is_object( $data ) ) {
			$data = (object) $data;
		}

		ob_start();
		include $path;
		$html = ob_get_contents();
		ob_end_clean();

		echo $html;
	}

}
