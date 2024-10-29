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

class Awocoupon_Library_Pagination_Object {

	public $text;
	public $base;
	public $link;
	public $prefix;
	public $active;

	public function init( $text, $prefix = '', $base = null, $link = null, $active = false ) {
		$this->text   = $text;
		$this->prefix = $prefix;
		$this->base   = $base;
		$this->link   = $link;
		$this->active = $active;
	}

}
